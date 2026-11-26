<?php
if(getenv('GML_DATABASE_TESTS')!=='1')exit(2);
require getenv('GML_TEST_WP_ROOT').'/wp-load.php';
if(DB_NAME!=='gml_regression_pagination_20261124')exit(2);
$mode=$argv[1]??'';
if($mode==='theme'){
 copy(__DIR__.'/guard.php',ABSPATH.'wp-content/mu-plugins/pagination-guard.php');
 echo "Updated native query theme support\n";
}elseif($mode==='index' || $mode==='noindex'){
 $v=get_option('seopress_titles_option_name',[]);
 if($mode==='index')unset($v['seopress_titles_paged_noindex']);else $v['seopress_titles_paged_noindex']='1';
 update_option('seopress_titles_option_name',$v);
 echo "Changed disposable fixture paginated robots policy\n";
}elseif($mode==='snapshot'){
 $tables=['gml_resource_manifests','gml_resource_readiness','gml_index','gml_queue'];
 $hashes=[];foreach($tables as $t)$hashes[$t]=hash('sha256',serialize($wpdb->get_results('SELECT * FROM '.$wpdb->prefix.$t.' ORDER BY id',ARRAY_A)));
 echo json_encode(['hashes'=>$hashes,'resources'=>$wpdb->get_col('SELECT resource_key FROM '.GML_Resource_Manifest_Store::manifest_table().' ORDER BY id')])."\n";
}elseif($mode==='cache'){
 $key=GML_Page_Cache::key('de','/de/product-category/equipment/page/2/');
 $html=get_transient($key);
 echo json_encode(['cache_present'=>$html!==false,'cached_page_two'=>$html!==false && strpos($html,'data-paged="2"')!==false,'cached_noindex'=>$html!==false && strpos($html,'noindex')!==false])."\n";
}elseif($mode==='cookie'){
 $user=get_user_by('login','pagination-admin');
 echo json_encode(['name'=>LOGGED_IN_COOKIE,'value'=>wp_generate_auth_cookie($user->ID,time()+3600,'logged_in'),'domain'=>'gml-pagination.test','path'=>'/'])."\n";
}elseif($mode==='variants'){
 global $wp_rewrite;
 $base=getenv('GML_TEST_HOME')?:'http://gml-pagination.test:8080';
 update_option('gml_source_lang',$argv[2]??'en');
 $targets=array_values(array_diff(['en','es','de','ru'],[get_option('gml_source_lang')]));
 update_option('gml_languages',array_map(static function($l){return ['code'=>$l,'enabled'=>true,'paused'=>false];},$targets));
 $wp_rewrite->pagination_base=$argv[3]??'page';
 update_option('pagination_lab_pagination_base',$wp_rewrite->pagination_base);
 GML_Translation_Rewrite::register();flush_rewrite_rules(false);
 echo json_encode(['home'=>$base,'source'=>get_option('gml_source_lang'),'pagination_base'=>$wp_rewrite->pagination_base])."\n";
}else throw new RuntimeException('Unknown fixture operation');
