<?php
if(getenv('GML_DATABASE_TESTS')!=='1')exit(2);
require getenv('GML_TEST_WP_ROOT').'/wp-load.php';
if(DB_NAME!=='gml_regression_pagination_20261124')exit(2);
$f=get_option('gml_pagination_fixture');if(!$f)throw new RuntimeException('Fixture not ready');
$resources=[];
foreach($f['product_cats'] as $id){$r=GML_Resource_Identity::for_term($id,'product_cat');$resources[$r->get_key()]=$r;}
$r=GML_Resource_Identity::for_term($f['sector'],'fixture_sector');$resources[$r->get_key()]=$r;
$r=GML_Resource_Identity::for_archive('fixture_item');$resources[$r->get_key()]=$r;
$r=GML_Resource_Identity::for_post($f['product_ids'][0]);$resources[$r->get_key()]=$r;
$source=get_option('gml_source_lang','en');$targets=GML_Language_Utils::enabled_local_target_codes();
function native_get($url){
 if(strpos($url,home_url('/'))!==0 && $url!==rtrim(home_url('/'),'/'))throw new RuntimeException('Non-fixture URL');
 $c=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>20]]);
 $body=file_get_contents($url,false,$c);
 if($body===false)throw new RuntimeException('Native HTTP fixture failed');
 return $body;
}
$parser=new GML_HTML_Parser();$parsed=[];$all=[];global $wp_rewrite;$pagination_base=$wp_rewrite->pagination_base;
foreach($resources as $key=>$r){
 $base=$r->get_source_url();$parsed[$key]=$parser->parse(native_get($base));
 foreach($parsed[$key]['nodes'] as $node)$all[$node['hash']]=$node;
 if($r->get_type()!=='post')for($n=2;$n<=4;$n++){
  $html=native_get(trailingslashit($base).$pagination_base.'/'.$n.'/');
  foreach($parser->parse($html)['nodes'] as $node)$all[$node['hash']]=$node;
 }
}
foreach($targets as $lang){
 $records=[];foreach($all as $node)$records[]=['source_hash'=>$node['hash'],'source_text'=>$node['text'],'source_lang'=>$source,'target_lang'=>$lang,'translated_text'=>strtoupper($lang).' '.$node['text'],'context_type'=>$node['context_type'],'status'=>'manual'];
 foreach(array_chunk($records,50) as $chunk)if(GML_Translation_Memory::upsert_batch($chunk)===false)throw new RuntimeException('Synthetic TM write failed');
}
foreach($resources as $key=>$r)if(GML_Resource_Manifest_Store::save_complete($r,$parsed[$key]['nodes'])!==true)throw new RuntimeException('Synthetic manifest seed failed');
update_option(GML_Resource_Backfill::OPTION,['status'=>'complete','phase'=>'complete','cursor'=>0,'updated_at'=>time()]);
delete_option(GML_Resource_Manifest_Manager::DIRTY_OPTION);
$cluster=GML_Public_Eligibility::get_cluster(reset($resources));
echo json_encode(['resources'=>array_map(static function($r){return ['key'=>$r->get_key(),'url'=>$r->get_source_url()];},array_values($resources)),'source_strings'=>count($all),'eligible'=>array_keys($cluster['eligible_urls'])])."\n";
