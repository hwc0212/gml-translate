<?php
/** Synthetic constrained headers with the real renderer and exact asset bytes. */
if(PHP_SAPI!=='cli') exit(1);
require __DIR__.'/switcher-bootstrap.php';
$layout=($argv[1]??'cnxhe')==='ozon'?'ozon':'cnxhe';
$prefix=($argv[2]??'root')==='subdirectory'?'/staging':'';
$current=($argv[3]??'en')==='de'?'de':'en';
$switcher=gml_switcher_fixture('all','https://example.com'.$prefix);
$_SERVER['REQUEST_URI']=$prefix.($current==='de'?'/de/':'/');
$menu=$switcher->render_component(['is_dropdown'=>true,'menu_context'=>true]);
$assets=getenv('GML_TEST_ASSET_ROOT')?:dirname(__DIR__,2);
$css=file_get_contents($assets.'/assets/css/language-switcher.css');
$js=file_get_contents($assets.'/assets/js/language-switcher.js');
?><!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>GML synthetic header regression</title><style>
*{box-sizing:border-box}body{margin:0;font:16px/1.5 Arial,sans-serif;color:#20272a;background:#fff}
header{overflow:hidden;position:relative;border-bottom:1px solid #ddd;background:white;height:84px}
.inner{max-width:1260px;margin:auto;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px}
.brand{font-size:24px;font-weight:700;white-space:nowrap}.links{display:flex;gap:20px;align-items:center;margin-left:auto}
.quote{display:inline-block;background:#007ea1;color:white;padding:12px;white-space:nowrap;text-decoration:none}
main{padding:28px;min-height:500px}h1{font-size:28px}a{color:inherit}.mobile-nav{display:none}
@media(max-width:900px){.links{display:none}.brand{font-size:16px}.inner{gap:8px;padding:12px}.quote{font-size:12px;padding:10px}.mobile-nav{display:block}header{height:70px}}
<?php echo $css; ?></style>
<body><header><div class="inner"><span class="brand"><?php echo $layout==='cnxhe'?'SIHON':'OzonGenerators'; ?></span>
<nav class="links">Products &nbsp; Applications &nbsp; Engineering &nbsp; Resources</nav>
<a class="quote" href="https://example.com/quote/">Request Quote</a><?php echo $menu; ?></div></header>
<main><h1>Synthetic <?php echo $layout; ?> header</h1><p>Root/subdirectory, clipped parent, keyboard and mobile regression.</p>
<p id="outside">Outside target</p></main><script><?php echo $js; ?></script></body></html>
