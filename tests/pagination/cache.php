<?php
if(getenv('GML_DATABASE_TESTS')!=='1')exit(2);
require getenv('GML_TEST_WP_ROOT').'/wp-load.php';
if(DB_NAME!=='gml_regression_pagination_20261124')exit(2);
update_option('pagination_lab_cookie_free_cache',true);
$mu=ABSPATH.'wp-content/mu-plugins/pagination-guard.php';
$text=file_get_contents($mu);
if(strpos($text,'pagination_lab_cookie_free_cache')===false){
 $extra=<<<'PHP'
add_action('plugins_loaded',static function(){
 if(!get_option('pagination_lab_cookie_free_cache'))return;
 global $wp_filter;
 foreach(($wp_filter['template_redirect']->callbacks[0]??[]) as $entry){
  $fn=$entry['function'];
  if(is_array($fn) && is_object($fn[0]) && $fn[0] instanceof GML_Language_Detector && $fn[1]==='set_language_cookie')remove_action('template_redirect',$fn,0);
 }
},PHP_INT_MAX);
PHP;
 file_put_contents($mu,$text."\n".$extra."\n");
}
$base=home_url('/');
$r=[];
foreach(['es','de','ru'] as $lang){
 $url=$base.$lang.'/product-category/equipment/page/2/';
 for($i=0;$i<2;$i++){
  $h=file_get_contents($url);$headers=$http_response_header;
  $key=GML_Page_Cache::key($lang,'/'.$lang.'/product-category/equipment/page/2/');
  $cache=get_transient($key);
  $dom=new DOMDocument();@$dom->loadHTML($h);$xp=new DOMXPath($dom);
  $buttons=[];foreach($xp->query('//a[contains(@class,"gml-lang-button")]') as $a)$buttons[$a->getAttribute('hreflang')]=$a->getAttribute('href');
  $r[]=['language'=>$lang,'repeat'=>$i,'set_cookie'=>(bool)preg_grep('/^Set-Cookie:/i',$headers),'cache_present'=>$cache!==false,'cache_equals_response'=>$cache===$h,'paged'=>$xp->query('//main')->item(0)->getAttribute('data-paged'),'buttons'=>$buttons,'hreflang'=>$xp->query('//head/link[@hreflang]')->length];
  if($cache===false || $cache!==$h || $r[count($r)-1]['paged']!=='2' || $r[count($r)-1]['hreflang']!==0)throw new RuntimeException('Controlled safe-cache fixture did not prove real cache storage and retrieval');
 }
}
echo json_encode(['controlled_cookie_writer_disabled'=>true,'cache_guard_unchanged'=>true,'responses'=>$r])."\n";
