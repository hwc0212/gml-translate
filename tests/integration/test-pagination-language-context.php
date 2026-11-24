<?php
require_once __DIR__.'/../bootstrap-mock.php';
$product_under_test=getenv('GML_PRODUCT_UNDER_TEST')?:dirname(__DIR__,2);
$GLOBALS['pag_test']=['page'=>2,'archive'=>true,'plain'=>false,'base'=>''];
function is_paged(){return $GLOBALS['pag_test']['page']>1;}
function is_archive(){return $GLOBALS['pag_test']['archive'];}
function is_home(){return false;}
function get_query_var($name,$default=''){return $name==='paged'?$GLOBALS['pag_test']['page']:$default;}
function user_trailingslashit($value,$type=''){return trailingslashit($value);}
if(!function_exists('add_query_arg')){
 function add_query_arg($name,$value,$url){$a=explode('?',$url,2);$q=[];parse_str($a[1]??'',$q);$q[$name]=$value;return $a[0].'?'.http_build_query($q);}
}
class GML_Resource_Identity {
 public static function current_public(){return GML_Translate_Test_State::$is_404?null:new self();}
 public function get_source_url(){return home_url('/product-category/equipment/').($GLOBALS['pag_test']['plain']?'?product_cat=equipment':'');}
 public function is_eligible(){return true;}
}
require_once $product_under_test.'/includes/vendor/gml-translation-core/src/class-language-utils.php';
require_once $product_under_test.'/includes/vendor/gml-translation-core/src/class-url-helper.php';
require_once $product_under_test.'/includes/vendor/gml-translation-core/src/class-translation-provider.php';
require_once $product_under_test.'/includes/class-seo-router.php';
require_once $product_under_test.'/includes/class-seo-hreflang.php';
class Pagination_Provider extends GML_Translation_Provider {
 public $eligible=true;
 public function get_languages(){return ['en','es','de','ru'];}
 public function get_alternate_urls($url){
  if(!$this->eligible)return [];
  $out=[];foreach($this->get_languages() as $lang)$out[$lang]=$this->get_translated_url((new GML_Resource_Identity())->get_source_url(),$lang);
  $out['x-default']=$out['en'];return $out;
 }
 public function get_public_status($subject,$lang,array $context=[]){return ['route_valid'=>true,'url'=>$this->get_translated_url($subject->get_source_url(),$lang)];}
}
define('SEOPRESS_VERSION','fixture');
GML_Translate_Test_State::$options=['gml_source_lang'=>'en','gml_multilingual_enabled'=>true,'permalink_structure'=>'/%postname%/','gml_languages'=>array_map(static function($l){return ['code'=>$l,'enabled'=>true];},['es','de','ru'])];
$GLOBALS['wp_rewrite']=(object)['pagination_base'=>'page'];
$checks=0;
function page_check($ok,$label){global $checks;$checks++;if(!$ok)throw new RuntimeException($label);}
foreach(['','/staging','/caf%C3%A9'] as $base){
 GML_Translate_Test_State::$home_url='https://example.test'.$base;
 foreach([2,3,4] as $n){
  $GLOBALS['pag_test']['page']=$n;
  foreach(['en','es','de','ru'] as $current){
   $_SERVER['REQUEST_URI']=$base.($current==='en'?'':'/'.$current).'/product-category/equipment/page/'.$n.'/?utm_source=fixture';
   $provider=new Pagination_Provider();$seo=new GML_SEO_Hreflang($provider);
   $urls=GML_SEO_Router::get_language_urls();
   foreach($provider->get_languages() as $target){
    $expected=home_url(($target==='en'?'':'/'.$target).'/product-category/equipment/page/'.$n.'/');
    page_check($urls[$target]===$expected,'navigation preserves exact page and installation path');
   }
   ob_start();$seo->inject_multilingual_meta();$head=ob_get_clean();
   page_check(substr_count($head,'hreflang=')===5,'eligible indexable pagination maps all language equivalents');
   foreach($urls as $url)page_check(strpos($head,'href="'.$url.'"')!==false,'alternate points to the same page');
   page_check($seo->filter_canonical_url('unused')===$urls[$current],'canonical uses current page and language');
   foreach([['noindex'=>true],['follow','noindex'],'noindex, follow','<meta name="robots" content="follow, noindex">'] as $robots){
    $blocked=new GML_SEO_Hreflang($provider);
    page_check($blocked->observe_robots($robots)===$robots,'observer preserves owner value exactly');
    ob_start();$blocked->inject_multilingual_meta();$h=ob_get_clean();
    page_check(strpos($h,'hreflang=')===false,'noindex pagination emits no alternates');
   }
   foreach([['max-image-preview'=>'none'],'max-image-preview:none','<meta name="robots" content="index, follow, max-image-preview:none">',['noindex'=>false]] as $robots){
    $allowed=new GML_SEO_Hreflang($provider);$allowed->observe_robots($robots);
    ob_start();$allowed->inject_multilingual_meta();$h=ob_get_clean();
    page_check(substr_count($h,'hreflang=')===5,'parameter value none and disabled flags are not global noindex');
   }
   page_check(count(GML_SEO_Router::get_language_urls())===4,'noindex does not suppress navigation');
   page_check($seo->filter_seopress_canonical('')==='','disabled canonical stays disabled');
  }
 }
}
$GLOBALS['pag_test']['page']=2;$provider=new Pagination_Provider();$provider->eligible=false;
$seo=new GML_SEO_Hreflang($provider);ob_start();$seo->inject_multilingual_meta();$head=ob_get_clean();
page_check(strpos($head,'hreflang=')===false,'readiness still gates alternates');
page_check(count(GML_SEO_Router::get_language_urls())===4,'incomplete translations still have navigation');
$GLOBALS['wp_rewrite']->pagination_base='seite';
page_check(strpos(GML_SEO_Router::get_language_urls()['de'],'/seite/2/')!==false,'custom WordPress pagination base');
$GLOBALS['pag_test']['plain']=true;GML_Translate_Test_State::$options['permalink_structure']='';
page_check(strpos(GML_SEO_Router::get_language_urls()['de'],'?product_cat=equipment&paged=2')!==false,'plain routing preserves query identity and page');
GML_Translate_Test_State::$is_404=true;
page_check(GML_SEO_Router::get_language_urls()===[],'invalid pagination cannot advertise first-page navigation');
echo "OK test-pagination-language-context ($checks checks)\n";
