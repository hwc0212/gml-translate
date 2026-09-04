<?php
/** Regression: language catalogs follow the actual WordPress text domain. */

function language_pack_assert( $condition, $label ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$label}\n" );
		exit( 1 );
	}
}

$root    = dirname( __DIR__, 2 );
$locales = [ 'zh_CN', 'zh_TW', 'de_DE', 'fr_FR', 'es_ES', 'pt_BR', 'ja', 'ko_KR', 'ru_RU', 'ar' ];

foreach ( $locales as $locale ) {
	$file = $root . '/languages/gml-translate-' . $locale . '.mo';
	language_pack_assert( is_file( $file ), $locale . ' compiled catalog exists' );
	$data = file_get_contents( $file );
	language_pack_assert( is_string( $data ) && strlen( $data ) > 28, $locale . ' catalog is non-empty' );
	language_pack_assert( substr( $data, 0, 4 ) === pack( 'V', 0x950412de ), $locale . ' catalog has GNU MO magic' );
	$po = file_get_contents( $root . '/languages/gml-translate-' . $locale . '.po' );
	language_pack_assert( strpos( $po, 'Project-Id-Version: GML Translate 2.11.1-rc.16' ) !== false, $locale . ' catalog identifies the current plugin version' );
}

$zh = file_get_contents( $root . '/languages/gml-translate-zh_CN.po' );
language_pack_assert( strpos( $zh, "msgid \"Human Review\"\nmsgstr \"人工审核\"" ) !== false, 'Simplified Chinese catalog translates the Phase 2C review workflow' );

language_pack_assert( is_file( $root . '/languages/gml-translate.pot' ), 'POT uses the gml-translate text domain name' );
language_pack_assert( ! file_exists( $root . '/languages/gemini-translate.pot' ), 'obsolete Gemini catalog name is removed' );

$plugin = file_get_contents( $root . '/gml-translate.php' );
language_pack_assert( strpos( $plugin, 'Plugin Name: GML Translate' ) !== false, 'public plugin name is provider-neutral' );
language_pack_assert( strpos( $plugin, "load_plugin_textdomain(\n            'gml-translate'" ) !== false, 'plugin loads the matching text domain' );

echo "OK test-language-packs\n";
