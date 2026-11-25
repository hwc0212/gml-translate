<?php
if(getenv('GML_DATABASE_TESTS')!=='1')exit(2);
require getenv('GML_TEST_WP_ROOT').'/wp-load.php';
if(DB_NAME!=='gml_regression_pagination_20261124')exit(2);
$mode=$argv[1]??'';$source='5–8 ppm at inlet water 15°C, ambient 20°C and inlet conductivity <500 µS/cm; confirm sampling method and location';
$table=$wpdb->prefix.'gml_index';$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE source_hash=%s AND source_lang='en' AND target_lang='de'",md5($source)),ARRAY_A);
if(!$row && $mode!=='restore')throw new RuntimeException('Fixture translation missing');
if($mode==='missing'){
 update_option('gml_breadcrumb_saved_record',$row,false);
 if(!GML_Translation_Memory::delete_by_id((int)$row['id']))throw new RuntimeException('Fixture delete failed');
}elseif($mode==='restore'){
 $saved=get_option('gml_breadcrumb_saved_record');unset($saved['id']);
 if(GML_Translation_Memory::upsert_batch([$saved])===false)throw new RuntimeException('Fixture restore failed');
}elseif($mode==='manual'){
 $source='Synthetic equipment & test';$saved=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE source_hash=%s AND source_lang='en' AND target_lang='de'",md5($source)),ARRAY_A);unset($saved['id']);$saved['translated_text']='Zweite manuelle Fassung des Gerätetitels';$saved['status']='manual';
 if(GML_Translation_Memory::upsert_batch([$saved])===false)throw new RuntimeException('Fixture manual update failed');
 $automatic=$saved;$automatic['status']='auto';$automatic['translated_text']='Automatic overwrite candidate';
 $guard=GML_Translation_Memory::upsert_batch([$automatic]);
 if(!is_array($guard)||$guard['skipped_manual']!==1)throw new RuntimeException('Manual record not protected');
}else throw new RuntimeException('Unknown test state');
$f=get_option('gml_pagination_fixture');$r=GML_Resource_Identity::for_post($f['product_ids'][0]);
$html=file_get_contents($r->get_source_url());$parsed=(new GML_HTML_Parser())->parse($html);
if(GML_Resource_Manifest_Store::save_complete($r,$parsed['nodes'])!==true)throw new RuntimeException('Fixture current manifest refresh failed');
echo json_encode(['mode'=>$mode,'manual_only'=>true,'ai_enabled'=>GML_Translation_State::ai_available(),'queue_rows'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix.'gml_queue')])."\n";
