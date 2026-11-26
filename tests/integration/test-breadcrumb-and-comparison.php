<?php
require __DIR__.'/../bootstrap-mock.php';
$core=getenv('GML_CORE_UNDER_TEST')?:dirname(__DIR__,2).'/includes/vendor/gml-translation-core';
require $core.'/src/class-language-utils.php';
require $core.'/src/class-url-helper.php';
require $core.'/src/class-output-buffer.php';
$checks=0;
function bc_check($ok,$label){global $checks;$checks++;if(!$ok)throw new RuntimeException($label);}
class BC_Buffer extends GML_Translation_Output_Buffer {
 public function links($html,$lang='de'){$this->source_lang=get_option('gml_source_lang','en');$this->target_lang=$lang;return $this->rewrite_internal_links($html);}
 public function protect($html){return $this->extract_no_translate_blocks($html);}
}
$p=new GML_HTML_Parser();
$b=new BC_Buffer();
GML_Translate_Test_State::$options=['gml_source_lang'=>'en','gml_languages'=>array_map(static function($s){return ['code'=>$s,'enabled'=>true];},['es','de','ru'])];
foreach(['<500','< 500','<-5','<+5','&lt;500','&#60;500','&#x3c;500','>500','&gt;500','≤500','≥500','<500 and >100'] as $limit){
 $raw='5–8 ppm at water 15°C and ambient 20°C, conductivity '.$limit.' µS/cm; confirm sample location';
 $source=html_entity_decode($raw,ENT_QUOTES|ENT_HTML5,'UTF-8');
 $target=str_replace(['at water','and ambient','conductivity','confirm sample location'],['bei Wasser','und Umgebung','Leitfähigkeit','Messstelle bestätigen'],$source);
 foreach(['td','p','span'] as $tag){
  $html='<'.$tag.'>'.$raw.'</'.$tag.'><a href="/next/">Next</a>';
  $parsed=$p->parse($html);
  bc_check(in_array($source,array_column($parsed['nodes'],'text'),true),'whole comparison sentence extracted');
  $parsed['replacements']=[$source=>$target,'Next'=>'Weiter'];$output=$p->rebuild($parsed);
  bc_check(strpos($output,htmlspecialchars($target,ENT_QUOTES|ENT_HTML5,'UTF-8'))!==false,'whole comparison sentence replaced without changing units or sign');
  bc_check(strpos($b->links($output),'href="/de/next/"')!==false,'navigation following comparison remains localizable');
  bc_check($p->rebuild(['html'=>$html,'replacements'=>[]])===$html,'missing translation preserves exact source');
 }
}
foreach(['code','pre','textarea','script','style','svg'] as $tag){
 $text='Temperature <500 µS/cm';$html='<'.$tag.'>'.$text.'</'.$tag.'><p>'.$text.'</p>';
 $out=$p->rebuild(['html'=>$html,'replacements'=>[$text=>'Temperatur <500 µS/cm']]);
 bc_check(strpos($out,'<'.$tag.'>'.$text.'</'.$tag.'>')!==false,'raw technical blocks remain byte-identical');
 bc_check(strpos($out,'<p>Temperatur &lt;500 µS/cm</p>')!==false,'outside identical text still translates');
}
$protected='<div translate="no">Temperature <500 µS/cm</div>';
list($clean,$tokens)=$b->protect($protected.'<a href="/after/">Next</a>');
bc_check(in_array($protected,$tokens,true),'comparison inside opt-out does not swallow closing tag');
bc_check(strpos($b->links($clean),'href="/de/after/"')!==false,'link after opt-out still localizes');

$crumb=['@context'=>'https://schema.org','@type'=>['Thing','BreadcrumbList'],'@id'=>'https://example.com/#breadcrumb','name'=>'Do not change container name','extra'=>['name'=>'Equipment','id'=>98],'itemListElement'=>[
 ['@type'=>'ListItem','position'=>1,'item'=>['@id'=>'https://example.com/','name'=>'Home']],
 ['@type'=>'ListItem','position'=>2,'item'=>['id'=>'https://example.com/category/equipment/#webpage','url'=>'https://example.com/category/equipment/','name'=>'Equipment &amp;#038; Parts']],
 ['@type'=>'ListItem','position'=>3,'name'=>'Product title','item'=>'https://example.com/product/demo/'],
 ['@type'=>'ListItem','position'=>4,'item'=>['@id'=>'https://example.com/category/equipment/page/2/','name'=>'Page 2']]
]];
$translations=['Home'=>'Startseite','Equipment & Parts'=>'Ausrüstung & Teile','Product title'=>'Manuell bearbeiteter Titel','Page 2'=>'Seite 2'];
foreach(['object','graph','array'] as $shape){
 $json=json_encode($crumb,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
 $foreign='{"@type":"Product","name":"Product title","url":"https://example.com/product/demo/","sku":"ABC","price":123.4500,"big":123456789012345678901234567890,"empty":{},"arr":[]}';
 if($shape==='graph')$json='{"@context":"https://schema.org","@graph":['.$foreign.','.$json.']}';
 if($shape==='array')$json='['.$json.','.$foreign.']';
 $html='<script type="application/ld+json">'.$json.'</script>';
 $parsed=$p->parse($html);
 foreach(array_keys($translations) as $text)bc_check(in_array($text,array_column($parsed['nodes'],'text'),true),'only schema labels enter existing text pipeline');
 bc_check(!in_array('Do not change container name',array_column($parsed['nodes'],'text'),true),'schema container name is not translation input');
 $parsed['replacements']=$translations;$out=$b->links($p->rebuild($parsed));
 preg_match('~<script[^>]*>(.*)</script>~s',$out,$m);$value=json_decode($m[1],true);
 bc_check(is_array($value),'result remains valid JSON');
 if($shape==='graph')$value=$value['@graph'][1];elseif($shape==='array')$value=$value[0];
 bc_check($value['@id']===$crumb['@id'] && $value['extra']===$crumb['extra'],'graph identity and unrelated breadcrumb fields unchanged');
 bc_check($value['itemListElement'][0]['item']['name']==='Startseite','home name translated');
 bc_check($value['itemListElement'][0]['item']['@id']==='https://example.com/de/','home URL localized');
 bc_check($value['itemListElement'][1]['item']['name']==='Ausrüstung & Teile','entity-bearing category uses decoded existing TM key');
 bc_check($value['itemListElement'][1]['item']['id']==='https://example.com/de/category/equipment/#webpage','SEOPress id and fragment retained');
 bc_check($value['itemListElement'][2]['name']==='Manuell bearbeiteter Titel','manual product name reused');
 bc_check($value['itemListElement'][2]['item']==='https://example.com/de/product/demo/','string item URL localized');
 bc_check($value['itemListElement'][3]['item']['@id']==='https://example.com/de/category/equipment/page/2/','page number preserved');
 bc_check($value['itemListElement'][3]['item']['name']==='Seite 2','page label translated');
 if($shape!=='object')bc_check(strpos($m[1],$foreign)!==false,'non-breadcrumb graph object bytes and numeric precision preserved');
 bc_check($p->rebuild(['html'=>$html,'replacements'=>[]])===$html,'absent translations preserve complete schema bytes');
 $twice=$b->links($p->rebuild(['html'=>$out,'replacements'=>$translations]));
 bc_check($twice===$out,'second pass does not cascade or double-prefix');
}
foreach(['<script type="application/json">','<script>','<script data-type="application/ld+json">','<script type="application/ld+json" translate="NO">','<script type="application/ld+json" class="notranslate">','<script type="application/ld+json" id="excluded">'] as $tag){
 GML_Translate_Test_State::$options['gml_exclude_selectors']=['#excluded'];
 $html=$tag.json_encode($crumb).'</script>';
 bc_check($p->parse($html)['nodes']===[],'ordinary and excluded scripts not extracted');
 bc_check($b->links($p->rebuild(['html'=>$html,'replacements'=>$translations]))===$html,'ordinary and excluded script bytes protected');
}
GML_Translate_Test_State::$options['gml_exclude_selectors']=[];
foreach(['bad json','{"@type":"BreadcrumbList","itemListElement":[}','{"@type":"BreadcrumbList","@type":"BreadcrumbList","itemListElement":[{"name":"Home"}]}',str_repeat(' ',GML_Breadcrumb_JSONLD::MAX_BYTES).json_encode($crumb)] as $json){
 bc_check(GML_Breadcrumb_JSONLD::map($json,static function($s){return 'REPLACED';},static function($s){return '/bad/';})===$json,'invalid ambiguous or oversized JSON untouched');
 bc_check(GML_Breadcrumb_JSONLD::texts($json)===[],'invalid ambiguous or oversized JSON not queued');
}
$escaped='{"@context":"https://schema.org","@type":"Bread\u0063rumbList","itemListElement":[{"name":"Home","item":"/"}]}';
bc_check(strpos(GML_Breadcrumb_JSONLD::map($escaped,static function($s){return 'Startseite';}),'"Startseite"')!==false,'escaped JSON keys and type decoded safely');
$attack='</script><script>alert("test")</script>';
$out=GML_Breadcrumb_JSONLD::map(json_encode($crumb),static function($s)use($attack){return $attack;});
bc_check(strpos($out,'</script>')===false && json_decode($out)!==null,'JSON string replacements cannot close script tag');
$foreign_context=$crumb;$foreign_context['@context']='https://outside.test/vocabulary';
$raw=json_encode($foreign_context);
bc_check(GML_Breadcrumb_JSONLD::map($raw,static function($s){return 'changed';})===$raw,'foreign JSON-LD vocabulary not rewritten');
foreach(['https://outside.test/item/','https://example.com/wp-content/image.jpg','https://example.com/wp-json/','https://example.com/?wc-ajax=test','#node','mailto:test@example.test'] as $url){
 $v=$crumb;$v['itemListElement'][0]['item']['@id']=$url;
 $html='<script type="application/ld+json">'.json_encode($v).'</script>';
 $o=$b->links($html);preg_match('~<script[^>]*>(.*)</script>~s',$o,$m);
 bc_check(json_decode($m[1],true)['itemListElement'][0]['item']['@id']===$url,'external service media and fragments not rewritten');
}
foreach(['','/catalogue'] as $install){
 GML_Translate_Test_State::$home_url='https://example.com'.$install;
 foreach(['en','es'] as $source){
  GML_Translate_Test_State::$options['gml_source_lang']=$source;
  GML_Translate_Test_State::$options['gml_languages']=array_map(static function($s){return ['code'=>$s,'enabled'=>true];},array_diff(['en','es','de','ru'],[$source]));
  foreach(['de','ru'] as $target){
   $v=$crumb;$v['itemListElement'][0]['item']['@id']=home_url('/');
   $out=$b->links('<script type="APPLICATION/LD+JSON">'.json_encode($v).'</script>',$target);
   preg_match('~<script[^>]*>(.*)</script>~s',$out,$m);
   bc_check(json_decode($m[1],true)['itemListElement'][0]['item']['@id']===home_url('/'.$target.'/'),'subdirectory and non-English source URL mapping');
  }
 }
}
GML_Translate_Test_State::$home_url='https://example.com';
foreach(['title','p'] as $tag){
 $source='Condition <500 µS/cm';$html='<'.$tag.'>'.$source.'</'.$tag.'>';
 $out=$p->rebuild(['html'=>$html,'replacements'=>[$source=>'Bedingung <500 µS/cm']]);
 bc_check(strpos($out,'Bedingung &lt;500 µS/cm')!==false,'title and body preserve raw technical sign');
}

// Context scope must be established at every allowlisted object, not by an
// unrelated schema prefix or a parent whose context was explicitly reset.
GML_Translate_Test_State::$options['gml_source_lang']='en';
$base=['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>[
 ['@type'=>'ListItem','name'=>'Home','item'=>['name'=>'Equipment','@id'=>'https://example.com/category/']]
]];
$foreign_contexts=[
 null, 'https://foreign.test/vocab',
 (object)['@vocab'=>'https://foreign.test/','schema'=>'https://schema.org/'],
 (object)['@vocab'=>'https://schema.org/','name'=>'https://foreign.test/name'],
 (object)['@base'=>'https://foreign.test/'],
 ['https://schema.org',(object)['item'=>null]],
 [(object)['name'=>'https://foreign.test/name'],'https://schema.org'],
];
foreach($foreign_contexts as $context){
 $v=$base;$v['@context']=$context;$json=json_encode($v);
 bc_check(GML_Breadcrumb_JSONLD::texts($json)===[],'foreign or ambiguous root context never becomes translation input');
 bc_check(GML_Breadcrumb_JSONLD::map($json,static function($s){return 'changed';},static function($s){return '/changed/';})===$json,'foreign or ambiguous root context stays byte-identical');
 $v=$base;unset($v['@context']);$v['@context']=$context;
 $json=json_encode(['@context'=>'https://schema.org','@graph'=>[$v]]);
 bc_check(GML_Breadcrumb_JSONLD::texts($json)===[],'child root context overrides inherited schema context');
 foreach(['list','item'] as $level){
  $v=$base;
  if($level==='list')$v['itemListElement'][0]['@context']=$context;
  else $v['itemListElement'][0]['item']['@context']=$context;
  $json=json_encode($v);$before=json_encode($level==='list'?$v['itemListElement'][0]:$v['itemListElement'][0]['item']);
  $out=GML_Breadcrumb_JSONLD::map($json,static function($s){return 'changed';},static function($s){return '/changed/';});
  bc_check(strpos($out,$before)!==false,'local foreign/null context protects its complete subtree');
  bc_check(GML_Breadcrumb_JSONLD::texts($json)===($level==='list'?[]:['Home']),'only still-in-scope names are extracted');
 }
}
foreach(['https://schema.org','http://schema.org/',(object)['@vocab'=>'https://schema.org/'],['https://schema.org',(object)['@language'=>'de','@version'=>1.1]],[null,'https://schema.org']] as $context){
 $v=$base;$v['@context']=$context;$json=json_encode($v);
 bc_check(GML_Breadcrumb_JSONLD::texts($json)===['Home','Equipment'],'supported schema contexts keep breadcrumb extraction');
 bc_check(strpos(GML_Breadcrumb_JSONLD::map($json,static function($s){return 'changed';}),'"changed"')!==false,'supported schema contexts keep translation');
}

foreach([(object)['@vocab'=>'https://schema.org/'],['https://schema.org',(object)['@language'=>'de']]] as $context){
 $v=$base;$v['@context']=$context;
 $wrapped=json_encode(['@context'=>(object)['name'=>'https://foreign.test/name'],'@graph'=>[$v]]);
 bc_check(GML_Breadcrumb_JSONLD::texts($wrapped)===[],'unresolved inherited term overrides cannot be cleared by vocabulary-only context');
 bc_check(GML_Breadcrumb_JSONLD::map($wrapped,static function($s){return 'changed';},static function($s){return '/changed/';})===$wrapped,'ambiguous inherited vocabulary stays byte-identical');
 $v['@context']=[null,'https://schema.org'];
 $reset=json_encode(['@context'=>(object)['name'=>'https://foreign.test/name'],'@graph'=>[$v]]);
 bc_check(GML_Breadcrumb_JSONLD::texts($reset)===['Home','Equipment'],'explicit null resets inherited ambiguity before known schema context');
}
foreach(['code','pre'] as $tag){
 foreach([
  '<'.$tag.'>Inner</'.$tag.'> Secret text <a href="/technical/">Technical link</a>',
  '<'.$tag.' data-note="</'.$tag.'>">Inner</'.$tag.'><!-- </'.$tag.'> --> Secret text <a href="/technical/">Technical link</a>',
  '<script>var sample="</'.$tag.'>";</script><'.$tag.'>Inner</'.$tag.'> Secret text <a href="/technical/">Technical link</a>',
  '<textarea></'.$tag.'></textarea> Secret text <a href="/technical/">Technical link</a>',
  '<'.$tag.'/>Inner</'.$tag.'> Secret text <a href="/technical/">Technical link</a>',
 ] as $inside){
  $protected='<'.$tag.'>'.$inside.'</'.$tag.'>';
  $html=$protected.'<p>Secret text</p><a href="/after/">Technical link</a>';
  $out=$b->links($p->rebuild(['html'=>$html,'replacements'=>['Secret text'=>'Geheimer Text','Technical link'=>'Technischer Link']]));
  bc_check(strpos($out,$protected)===0,'complete nested technical block preserves exact bytes');
  bc_check(strpos($out,'<p>Geheimer Text</p>')!==false,'same text outside technical block still translates');
  bc_check(strpos($out,'href="/de/after/"')!==false,'normal link after balanced technical block still localizes');
 }
 foreach(['<'.$tag.'>Inner</'.$tag.'> Secret text <a href="/technical/">Technical link</a>','<script>var sample="</'.$tag.'>"; Secret text <a href="/technical/">Technical link</a>'] as $inside){
  $html='<'.$tag.'>'.$inside;
  bc_check($b->links($p->rebuild(['html'=>$html,'replacements'=>['Secret text'=>'changed','Technical link'=>'changed']]))===$html,'unterminated technical block fails closed');
 }
}


foreach(['code','pre','svg'] as $tag){
 foreach(['<!-- no closing comment </'.$tag.'>','<![CDATA[ no closing CDATA </'.$tag.'>'] as $inside){
  $html='<'.$tag.'>'.$inside.'<a href="/technical/">Secret text</a>';
  bc_check($b->links($p->rebuild(['html'=>$html,'replacements'=>['Secret text'=>'changed']]))===$html,'unclosed comment/CDATA inside protected element fails closed');
 }
}
foreach(['script','style','noscript','textarea','iframe'] as $tag){
 $html='<'.$tag.'>"<a href=\"/technical/\">Technical link</a>"';
 bc_check($b->links($html)===$html,'unterminated raw-text element does not expose apparent navigation');
}

echo "OK test-breadcrumb-and-comparison ($checks checks)\n";
