<?php
/**
 * Synchronize and verify the vendored GML Translation Core.
 *
 * Usage:
 *   php bin/translation-core.php verify [--source=/path/to/core]
 *   php bin/translation-core.php sync --source=/path/to/core [--update-lock]
 */

if ( PHP_SAPI !== 'cli' ) {
    fwrite( STDERR, "This tool must run from the command line.\n" );
    exit( 2 );
}

$root    = dirname( __DIR__ );
$command = isset( $argv[1] ) ? strtolower( trim( $argv[1] ) ) : '';
$args    = parse_arguments( array_slice( $argv, 2 ) );
$lock_file = $root . '/translation-core.lock.json';

if ( ! in_array( $command, [ 'verify', 'sync', 'ref' ], true ) ) {
    fail( 'Expected command: verify, sync, or ref.', 2 );
}

$lock = is_file( $lock_file ) ? read_json( $lock_file ) : [];
$vendor_root_relative = isset( $lock['vendorRoot'] )
    ? normalize_relative_path( $lock['vendorRoot'] )
    : default_vendor_root( $root );
$vendor_root = $root . '/' . $vendor_root_relative;
$source      = isset( $args['source'] ) ? rtrim( realpath( $args['source'] ) ?: '', '/\\' ) : '';
$update_lock = isset( $args['update-lock'] );

if ( $command === 'ref' ) {
    validate_lock( $lock );
    fwrite( STDOUT, $lock['source']['commit'] . "\n" );
    exit( 0 );
}

if ( $command === 'sync' ) {
    if ( $source === '' || ! is_file( $source . '/core.json' ) || ! is_dir( $source . '/src' ) ) {
        fail( 'sync requires --source=/absolute/path/to/gml-translation-core.' );
    }

    $manifest = source_manifest( $source );
    if ( ! $update_lock ) {
        assert_lock_matches_source( $lock, $manifest );
    }

    sync_files( $source, $vendor_root, array_keys( $manifest['files'] ) );

    if ( $update_lock ) {
        $lock = [
            'schemaVersion' => 1,
            'package'       => 'gml-translation-core',
            'version'       => $manifest['version'],
            'source'        => [
                'repository' => 'https://github.com/hwc0212/gml-translation-core',
                'commit'     => $manifest['commit'],
            ],
            'vendorRoot'    => $vendor_root_relative,
            'files'         => $manifest['files'],
        ];
        write_json( $lock_file, $lock );
    }
}

verify_vendor( $lock, $vendor_root );
if ( $source !== '' ) {
    assert_lock_matches_source( $lock, source_manifest( $source ) );
}

fwrite(
    STDOUT,
    sprintf(
        "OK gml-translation-core %s (%s)\n",
        isset( $lock['version'] ) ? $lock['version'] : 'unknown',
        isset( $lock['source']['commit'] ) ? substr( $lock['source']['commit'], 0, 12 ) : 'unlocked'
    )
);

function parse_arguments( array $tokens ) {
    $result = [];
    foreach ( $tokens as $token ) {
        if ( strpos( $token, '--' ) !== 0 ) {
            fail( 'Unknown argument: ' . $token, 2 );
        }
        $pair = explode( '=', substr( $token, 2 ), 2 );
        $key  = trim( $pair[0] );
        if ( $key === '' ) {
            fail( 'Invalid empty argument.', 2 );
        }
        $result[ $key ] = isset( $pair[1] ) ? $pair[1] : true;
    }
    return $result;
}

function default_vendor_root( $root ) {
    if ( is_file( $root . '/gml-seo.php' ) ) {
        return 'includes/modules/translate/vendor/gml-translation-core';
    }
    if ( is_file( $root . '/gml-translate.php' ) ) {
        return 'includes/vendor/gml-translation-core';
    }
    fail( 'Unable to identify the product repository.' );
}

function normalize_relative_path( $path ) {
    $path = str_replace( '\\', '/', trim( (string) $path ) );
    $path = trim( $path, '/' );
    if ( $path === '' || preg_match( '#(?:^|/)\.\.(?:/|$)#', $path ) ) {
        fail( 'Unsafe vendorRoot in translation-core.lock.json.' );
    }
    return $path;
}

function source_manifest( $source ) {
    $metadata = read_json( $source . '/core.json' );
    $version  = isset( $metadata['version'] ) ? trim( (string) $metadata['version'] ) : '';
    if ( $version === '' ) {
        fail( 'Core source has no version in core.json.' );
    }

    $files = list_files( $source . '/src', $source );
    if ( empty( $files ) ) {
        fail( 'Core source contains no files.' );
    }

    $hashes = [];
    foreach ( $files as $relative ) {
        $hashes[ $relative ] = hash_file( 'sha256', $source . '/' . $relative );
    }

    $commit = source_commit( $source );
    return [
        'version' => $version,
        'commit'  => $commit,
        'files'   => $hashes,
    ];
}

function source_commit( $source ) {
    $command = 'git -C ' . escapeshellarg( $source ) . ' rev-parse HEAD 2>/dev/null';
    $commit  = trim( (string) shell_exec( $command ) );
    if ( ! preg_match( '/^[a-f0-9]{40}$/', $commit ) ) {
        fail( 'Core source must be a committed Git checkout.' );
    }
    return $commit;
}

function list_files( $directory, $base ) {
    if ( ! is_dir( $directory ) ) {
        return [];
    }
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS )
    );
    foreach ( $iterator as $item ) {
        if ( ! $item->isFile() ) {
            continue;
        }
        $relative = str_replace( '\\', '/', substr( $item->getPathname(), strlen( $base ) + 1 ) );
        $files[] = $relative;
    }
    sort( $files, SORT_STRING );
    return $files;
}

function sync_files( $source, $vendor_root, array $files ) {
    if ( is_dir( $vendor_root ) ) {
        remove_tree_contents( $vendor_root );
    } elseif ( ! mkdir( $vendor_root, 0775, true ) && ! is_dir( $vendor_root ) ) {
        fail( 'Unable to create vendor directory: ' . $vendor_root );
    }

    foreach ( $files as $relative ) {
        $target = $vendor_root . '/' . $relative;
        $dir    = dirname( $target );
        if ( ! is_dir( $dir ) && ! mkdir( $dir, 0775, true ) && ! is_dir( $dir ) ) {
            fail( 'Unable to create directory: ' . $dir );
        }
        if ( ! copy( $source . '/' . $relative, $target ) ) {
            fail( 'Unable to vendor: ' . $relative );
        }
    }

    $marker = $vendor_root . '/GENERATED.md';
    $notice = "# Generated Translation Core\n\n"
        . "Files below this directory are generated from the locked GML Translation Core source.\n"
        . "Do not edit them directly; update Core and run the vendoring command.\n";
    if ( file_put_contents( $marker, $notice ) === false ) {
        fail( 'Unable to write generated marker.' );
    }
}

function remove_tree_contents( $directory ) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ( $iterator as $item ) {
        if ( $item->isDir() ) {
            rmdir( $item->getPathname() );
        } else {
            unlink( $item->getPathname() );
        }
    }
}

function verify_vendor( array $lock, $vendor_root ) {
    validate_lock( $lock );
    $expected = $lock['files'];

    foreach ( $expected as $relative => $hash ) {
        if ( strpos( $relative, 'src/' ) !== 0 || preg_match( '#(?:^|/)\.\.(?:/|$)#', $relative ) ) {
            fail( 'Unsafe Core file path in lock: ' . $relative );
        }
        $file = $vendor_root . '/' . $relative;
        if ( ! is_file( $file ) ) {
            fail( 'Missing vendored Core file: ' . $relative );
        }
        $actual = hash_file( 'sha256', $file );
        if ( ! hash_equals( $hash, $actual ) ) {
            fail( 'Vendored Core drift detected: ' . $relative );
        }
    }

    $actual_files = list_files( $vendor_root . '/src', $vendor_root );
    $expected_files = array_keys( $expected );
    sort( $actual_files, SORT_STRING );
    sort( $expected_files, SORT_STRING );
    if ( $actual_files !== $expected_files ) {
        fail( 'Vendored Core contains missing or untracked files.' );
    }
}

function assert_lock_matches_source( array $lock, array $manifest ) {
    validate_lock( $lock );
    if ( $lock['version'] !== $manifest['version'] ) {
        fail( 'Core version differs from the lock.' );
    }
    if ( $lock['source']['commit'] !== $manifest['commit'] ) {
        fail( 'Core source commit differs from the lock.' );
    }
    if ( $lock['files'] !== $manifest['files'] ) {
        fail( 'Core source file hashes differ from the lock.' );
    }
}

function validate_lock( array $lock ) {
    if (
        ! isset( $lock['schemaVersion'], $lock['package'], $lock['version'], $lock['source']['commit'], $lock['files'] )
        || (int) $lock['schemaVersion'] !== 1
        || $lock['package'] !== 'gml-translation-core'
        || ! preg_match( '/^[a-f0-9]{40}$/', (string) $lock['source']['commit'] )
        || ! is_array( $lock['files'] )
        || empty( $lock['files'] )
    ) {
        fail( 'Invalid or missing translation-core.lock.json.' );
    }
}

function read_json( $file ) {
    $json = file_get_contents( $file );
    $data = json_decode( $json, true );
    if ( ! is_array( $data ) || json_last_error() !== JSON_ERROR_NONE ) {
        fail( 'Invalid JSON: ' . $file );
    }
    return $data;
}

function write_json( $file, array $data ) {
    $json = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    if ( $json === false || file_put_contents( $file, $json . "\n" ) === false ) {
        fail( 'Unable to write lock file: ' . $file );
    }
}

function fail( $message, $code = 1 ) {
    fwrite( STDERR, 'ERROR: ' . $message . "\n" );
    exit( $code );
}
