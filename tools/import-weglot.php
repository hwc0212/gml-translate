#!/usr/bin/env php
<?php
/**
 * Import Weglot translations into GML Translate.
 *
 * Usage (WP-CLI):
 *   wp eval-file wp-content/plugins/gml-translate/tools/import-weglot.php /path/to/weglot-export.csv en ru
 *
 * Usage (standalone, from WordPress root):
 *   php wp-content/plugins/gml-translate/tools/import-weglot.php /path/to/weglot-export.csv en ru
 *
 * Arguments:
 *   1. Path to Weglot CSV export file
 *   2. Source language code (e.g. en)
 *   3. Target language code (e.g. ru, de, es)
 *
 * Weglot CSV format (exported from Dashboard > Translations > Languages > Export):
 *   Column 1: word_from (original text)
 *   Column 2: word_to   (translated text)
 *   Column 3: type      (1=text, 2=attribute, 3=external link, etc.)
 *   Column 4: url       (page URL where the text was found)
 *
 * The script:
 *   - Reads the CSV file
 *   - Deduplicates by source text (keeps the first occurrence)
 *   - Skips empty translations and identical source/target
 *   - Inserts into wp_gml_index with status='auto'
 *   - Does NOT overwrite existing 'manual' translations
 *   - Reports: imported, skipped (duplicate), skipped (exists), errors
 *
 * @package GML_Translate
 */

// ── Bootstrap WordPress ──────────────────────────────────────────────────────
// Detect if running under WP-CLI (WordPress already loaded) or standalone.
if ( ! defined( 'ABSPATH' ) ) {
    // Standalone mode — find and load WordPress
    $wp_load = null;
    $dir = __DIR__;
    // Walk up from tools/ → gml-translate/ → plugins/ → wp-content/ → WordPress root
    for ( $i = 0; $i < 8; $i++ ) {
        $dir = dirname( $dir );
        if ( file_exists( $dir . '/wp-load.php' ) ) {
            $wp_load = $dir . '/wp-load.php';
            break;
        }
    }
    if ( ! $wp_load ) {
        fwrite( STDERR, "Error: Cannot find wp-load.php. Run this script from the WordPress root directory.\n" );
        exit( 1 );
    }
    // Suppress output during bootstrap
    define( 'WP_USE_THEMES', false );
    require_once $wp_load;
}

// ── Parse arguments ──────────────────────────────────────────────────────────
// WP-CLI passes args via $args global; standalone uses $argv.
$cli_args = isset( $args ) ? $args : ( isset( $argv ) ? array_slice( $argv, 1 ) : [] );

if ( count( $cli_args ) < 3 ) {
    $usage = <<<EOT
GML Translate — Import Weglot Translations

Usage:
  wp eval-file wp-content/plugins/gml-translate/tools/import-weglot.php <csv_file> <source_lang> <target_lang>

  OR (standalone):
  php wp-content/plugins/gml-translate/tools/import-weglot.php <csv_file> <source_lang> <target_lang>

Arguments:
  csv_file     Path to Weglot CSV export file (UTF-8 encoded)
  source_lang  Source language code (e.g. en)
  target_lang  Target language code (e.g. ru, de, es, zh)

Example:
  wp eval-file wp-content/plugins/gml-translate/tools/import-weglot.php ~/weglot-en-ru.csv en ru

Steps to export from Weglot:
  1. Go to Weglot Dashboard > Translations > Languages
  2. Click Actions > Export
  3. Choose CSV format, select the language pair
  4. Download the CSV file
  5. Run this script

EOT;
    echo $usage;
    exit( 1 );
}

$csv_file    = $cli_args[0];
$source_lang = strtolower( trim( $cli_args[1] ) );
$target_lang = strtolower( trim( $cli_args[2] ) );

if ( ! file_exists( $csv_file ) ) {
    fwrite( STDERR, "Error: File not found: {$csv_file}\n" );
    exit( 1 );
}

echo "=== GML Translate — Weglot Import ===\n";
echo "File:   {$csv_file}\n";
echo "From:   {$source_lang}\n";
echo "To:     {$target_lang}\n\n";

// ── Read CSV ─────────────────────────────────────────────────────────────────
$handle = fopen( $csv_file, 'r' );
if ( ! $handle ) {
    fwrite( STDERR, "Error: Cannot open file: {$csv_file}\n" );
    exit( 1 );
}

// Detect BOM and skip it
$bom = fread( $handle, 3 );
if ( $bom !== "\xEF\xBB\xBF" ) {
    rewind( $handle );
}

// Read header row
$header = fgetcsv( $handle );
if ( ! $header ) {
    fwrite( STDERR, "Error: CSV file is empty or invalid.\n" );
    fclose( $handle );
    exit( 1 );
}

// Normalize header names (lowercase, trim)
$header = array_map( function( $h ) {
    return strtolower( trim( $h ) );
}, $header );

// Find column indices — Weglot uses "word_from" and "word_to"
// Also support "source" / "translation" as fallback
$col_from = null;
$col_to   = null;
foreach ( $header as $i => $name ) {
    if ( in_array( $name, [ 'word_from', 'source', 'original', 'from' ], true ) ) {
        $col_from = $i;
    }
    if ( in_array( $name, [ 'word_to', 'translation', 'translated', 'to' ], true ) ) {
        $col_to = $i;
    }
}

// If no header match, assume first two columns
if ( $col_from === null || $col_to === null ) {
    echo "Warning: Could not detect column headers. Assuming column 1 = source, column 2 = translation.\n";
    $col_from = 0;
    $col_to   = 1;
    // Re-read — the "header" row might actually be data
    rewind( $handle );
    // Skip BOM again
    $bom = fread( $handle, 3 );
    if ( $bom !== "\xEF\xBB\xBF" ) {
        rewind( $handle );
    }
}

echo "Columns: source={$col_from}, translation={$col_to}\n\n";

// ── Process rows ─────────────────────────────────────────────────────────────
global $wpdb;
$table = $wpdb->prefix . 'gml_index';

$imported  = 0;
$skipped_dup   = 0;  // duplicate source text in CSV
$skipped_exist = 0;  // already exists in DB (manual)
$skipped_empty = 0;  // empty or identical
$skipped_short = 0;  // too short to be useful
$errors    = 0;
$total     = 0;
$seen      = [];     // track seen source hashes to deduplicate

$now = current_time( 'mysql' );

// Batch insert for performance
$batch = [];
$batch_size = 100;

$flush_batch = function() use ( &$batch, &$imported, &$errors, $wpdb, $table, $now ) {
    if ( empty( $batch ) ) return;

    foreach ( $batch as $item ) {
        $result = $wpdb->replace( $table, [
            'source_hash'     => $item['hash'],
            'source_text'     => $item['source'],
            'source_lang'     => $item['source_lang'],
            'target_lang'     => $item['target_lang'],
            'translated_text' => $item['translated'],
            'context_type'    => $item['context_type'],
            'status'          => 'auto',
            'created_at'      => $now,
            'updated_at'      => $now,
        ] );
        if ( $result === false ) {
            $errors++;
        } else {
            $imported++;
        }
    }
    $batch = [];
};

while ( ( $row = fgetcsv( $handle ) ) !== false ) {
    $total++;

    // Get source and translation
    $source     = isset( $row[ $col_from ] ) ? trim( $row[ $col_from ] ) : '';
    $translated = isset( $row[ $col_to ] )   ? trim( $row[ $col_to ] )   : '';

    // Skip empty
    if ( $source === '' || $translated === '' ) {
        $skipped_empty++;
        continue;
    }

    // Skip identical (not actually translated)
    if ( $source === $translated ) {
        $skipped_empty++;
        continue;
    }

    // Skip very short strings (single char, pure numbers)
    if ( mb_strlen( $source ) < 2 || is_numeric( $source ) ) {
        $skipped_short++;
        continue;
    }

    $hash = md5( $source );

    // Deduplicate within CSV
    if ( isset( $seen[ $hash ] ) ) {
        $skipped_dup++;
        continue;
    }
    $seen[ $hash ] = true;

    // Check if manual translation exists in DB — don't overwrite
    $existing_status = $wpdb->get_var( $wpdb->prepare(
        "SELECT status FROM $table WHERE source_hash = %s AND source_lang = %s AND target_lang = %s",
        $hash, $source_lang, $target_lang
    ) );
    if ( $existing_status === 'manual' ) {
        $skipped_exist++;
        continue;
    }

    // Determine context type from Weglot's type column (if available)
    $context_type = 'text';
    // Weglot type: 1=text, 2=attribute (alt, placeholder, etc.), 3=external link
    // We map: 1→text, 2→attribute, others→text
    $type_col = null;
    foreach ( $header as $i => $name ) {
        if ( in_array( $name, [ 'type', 't' ], true ) ) {
            $type_col = $i;
            break;
        }
    }
    if ( $type_col !== null && isset( $row[ $type_col ] ) ) {
        $wtype = (int) $row[ $type_col ];
        if ( $wtype === 2 ) {
            $context_type = 'attribute';
        }
    }

    $batch[] = [
        'hash'         => $hash,
        'source'       => $source,
        'source_lang'  => $source_lang,
        'target_lang'  => $target_lang,
        'translated'   => $translated,
        'context_type' => $context_type,
    ];

    if ( count( $batch ) >= $batch_size ) {
        $flush_batch();
        // Progress indicator
        if ( $total % 500 === 0 ) {
            echo "  Processed {$total} rows... ({$imported} imported)\n";
        }
    }
}

// Flush remaining
$flush_batch();
fclose( $handle );

// ── Invalidate caches ────────────────────────────────────────────────────────
// Clear dictionary cache so new translations are picked up immediately
$cache_key = "gml_dict_{$source_lang}_{$target_lang}";
wp_cache_delete( $cache_key, 'gml_translate' );

// Clear page-level HTML caches
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_gml_page_%' OR option_name LIKE '_transient_timeout_gml_page_%'"
);

// ── Report ───────────────────────────────────────────────────────────────────
echo "\n=== Import Complete ===\n";
echo "Total CSV rows:        {$total}\n";
echo "Imported:              {$imported}\n";
echo "Skipped (duplicate):   {$skipped_dup}\n";
echo "Skipped (manual):      {$skipped_exist}\n";
echo "Skipped (empty/same):  {$skipped_empty}\n";
echo "Skipped (too short):   {$skipped_short}\n";
echo "Errors:                {$errors}\n";
echo "\nDone! The imported translations will be used immediately on the next page load.\n";
