<?php
if(getenv('GML_DATABASE_TESTS')!=='1')exit(2);
require getenv('GML_TEST_WP_ROOT').'/wp-load.php';
if(DB_NAME!=='gml_regression_pagination_20261124')exit(2);
$f=get_option('gml_pagination_fixture');$resources=[];
foreach($f['product_cats'] as $id){$r=GML_Resource_Identity::for_term($id,'product_cat');$resources[$r->get_key()]=$r;}
$r=GML_Resource_Identity::for_post($f['product_ids'][0]);$resources[$r->get_key()]=$r;
$parser=new GML_HTML_Parser();$parsed=[];$all=[];
foreach($resources as $key=>$r){
 $urls=[$r->get_source_url()];if($r->get_type()!=='post')for($n=2;$n<=4;$n++)$urls[]=trailingslashit($r->get_source_url()).'page/'.$n.'/';
 foreach($urls as $n=>$url){
  if(strpos($url,home_url('/'))!==0)throw new RuntimeException('Non-fixture URL');
  $html=file_get_contents($url,false,stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>20]]));
  if($html===false)throw new RuntimeException('Fixture HTTP failure');
  $p=$parser->parse($html);if($n===0)$parsed[$key]=$p;
  foreach($p['nodes'] as $node)$all[$node['hash']]=$node;
 }
}
$condition='5–8 ppm at inlet water 15°C, ambient 20°C and inlet conductivity <500 µS/cm; confirm sampling method and location';
$labels=[
 'de'=>['Home'=>'Startseite','Equipment & Parts'=>'Ausrüstung & Teile','Components'=>'Bauteile','Synthetic equipment & test'=>'Manuell geprüfter Gerätetitel','Page 2'=>'Seite 2','Operating condition'=>'Betriebsbedingungen',$condition=>'5–8 ppm bei Zulaufwasser 15°C, Umgebung 20°C und Zulaufleitfähigkeit <500 µS/cm; Messverfahren und Messstelle bestätigen'],
 'es'=>['Home'=>'Inicio','Equipment & Parts'=>'Equipos y repuestos','Components'=>'Componentes','Synthetic equipment & test'=>'Título del equipo revisado manualmente','Page 2'=>'Página 2','Operating condition'=>'Condiciones de funcionamiento',$condition=>'5–8 ppm con agua de entrada a 15°C, ambiente a 20°C y conductividad de entrada <500 µS/cm; confirmar el método y el punto de muestreo'],
 'ru'=>['Home'=>'Главная','Equipment & Parts'=>'Оборудование и запчасти','Components'=>'Компоненты','Synthetic equipment & test'=>'Название оборудования проверено вручную','Page 2'=>'Страница 2','Operating condition'=>'Условия эксплуатации',$condition=>'5–8 ppm при температуре входящей воды 15°C, окружающего воздуха 20°C и проводимости входящей воды <500 µS/cm; подтвердить метод и место отбора проб']
];
foreach($labels as $lang=>$map){
 $records=[];foreach($all as $node)$records[]=['source_hash'=>$node['hash'],'source_text'=>$node['text'],'source_lang'=>'en','target_lang'=>$lang,'translated_text'=>$map[$node['text']]??strtr($node['text'],$map),'context_type'=>$node['context_type'],'status'=>'manual'];
 foreach(array_chunk($records,50) as $chunk)if(GML_Translation_Memory::upsert_batch($chunk)===false)throw new RuntimeException('Fixture manual TM seed failed');
}
foreach($resources as $key=>$r)if(GML_Resource_Manifest_Store::save_complete($r,$parsed[$key]['nodes'])!==true)throw new RuntimeException('Fixture manifest failed');
update_option(GML_Resource_Backfill::OPTION,['status'=>'complete','phase'=>'complete','cursor'=>0,'updated_at'=>time()]);delete_option(GML_Resource_Manifest_Manager::DIRTY_OPTION);
echo json_encode(['resources'=>array_keys($resources),'source_strings'=>count($all),'manual_titles'=>array_map(static function($m){return $m['Synthetic equipment & test'];},$labels),'product_url'=>get_permalink($f['product_ids'][0])],JSON_UNESCAPED_UNICODE)."\n";
