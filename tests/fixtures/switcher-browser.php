<?php
/** Run with php -S 0.0.0.0:8080 tests/fixtures/switcher-browser.php. */
$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
if ( in_array( $path, [ '/assets/css/language-switcher.css', '/assets/js/language-switcher.js' ], true ) ) {
    header( 'Content-Type: ' . ( substr( $path, -4 ) === '.css' ? 'text/css' : 'application/javascript' ) );
    readfile( dirname( __DIR__, 2 ) . $path );
    return;
}
require_once __DIR__ . '/switcher-bootstrap.php';
$case = $_GET['case'] ?? 'all';
if ( ! in_array( $case, [ 'all', 'none', 'incomplete', 'complete' ], true ) ) $case = 'none';
$base = 'http://127.0.0.1:18824' . ( strpos( $path, '/staging/' ) === 0 ? '/staging' : '' );
$switcher = gml_switcher_fixture( $case, $base );
header( 'Cache-Control: no-store' );
?>
<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Switcher regression fixture</title>
<link rel="stylesheet" href="/assets/css/language-switcher.css">
<style>
body { margin:0; font:16px Arial,sans-serif; background:#f5f5f5; }
header { height:64px; overflow:hidden; background:white; position:relative; z-index:10; }
nav { display:flex; justify-content:flex-end; align-items:center; height:64px; gap:24px; padding:0 20px; }
nav a { color:#222; text-decoration:none; font-weight:bold; }
main { padding:24px; } button { font:inherit; }
@media(max-width:600px) { nav { gap:12px; padding:0 12px; } }
@media(max-width:360px) { .gml-language-switcher { display:none; } }
</style>
<header><nav><a href="/#products">Products</a><a href="/#quote">Request Quote</a>
<?php echo $switcher->render_component( [ 'menu_context' => true ] ); ?>
<a href="/#contact">Contact</a></nav></header>
<main><h1>Switcher fixture</h1><button id="outside">Outside control</button><p>Case: <?php echo esc_html( $case ); ?></p></main>
<script src="/assets/js/language-switcher.js"></script></html>
