<?php
if(getenv('GML_DATABASE_TESTS')!=='1')exit(2);
require getenv('GML_TEST_WP_ROOT').'/wp-load.php';
if(DB_NAME!=='gml_regression_pagination_20261124')exit(2);
global $wp_rewrite;
$patterns=[];foreach((array)get_option('rewrite_rules') as $p=>$q)if(strpos($p,'product-category')!==false || strpos($p,'industry')!==false)$patterns[$p]=$q;
$r=['home'=>home_url('/'),'base'=>$wp_rewrite->pagination_base,'patterns'=>$patterns,'requests'=>[]];
foreach(['product-category/components/','product-category/components/seite/2/','product-category/components/page/2/'] as $path){
 $html=file_get_contents(home_url('/'.$path),false,stream_context_create(['http'=>['ignore_errors'=>true]]));
 preg_match('/<main[^>]*>/',$html,$m);
 $r['requests'][]=['path'=>$path,'status'=>array_values(preg_grep('/^HTTP\//',$http_response_header)),'main'=>$m[0]??'','error_title'=>(bool)strpos($html,'Fixture not found')];
}
echo json_encode($r)."\n";
