<?php
/** Synthetic source-only fixture. Never shipped, activated or connected to a live service. */
ob_start();
require rtrim(getenv('GML_TEST_CORE_DIR'),'/').'/tests/database/bootstrap.php';
ob_end_clean();
gml_test_product_init();
require_once ABSPATH.'wp-admin/includes/template.php';
require_once GML_PLUGIN_DIR.'includes/class-output-buffer.php';
require_once GML_PLUGIN_DIR.'admin/class-page-workflow-admin.php';
require_once GML_PLUGIN_DIR.'includes/class-publication-gate.php';
require_once GML_PLUGIN_DIR.'includes/class-seo-hreflang.php';
add_filter('pre_wp_mail',static function(){throw new RuntimeException('Mail is forbidden in state-clarity tests');});
add_shortcode('contact-form-7',static function(){
    return '<div class="wpcf7"><textarea placeholder="Your synthetic request"></textarea><input type="submit" value="Send synthetic request"></div>';
});
function gml_state_html($id) {
    $post=get_post($id);
    return '<!doctype html><html><head><title>'.esc_html($post->post_title).'</title><meta name="description" content="Synthetic inquiry page"><meta name="robots" content="index, follow"></head><body>'.do_shortcode($post->post_content).'</body></html>';
}
$GLOBALS['gml_state_blocked_http']=0;
$GLOBALS['gml_state_render_mode']='success';
$GLOBALS['gml_state_during_render']=null;
add_filter('pre_http_request',static function($pre,$args,$url){
    if(parse_url($url,PHP_URL_HOST)!=='gml-state.test' || strpos($url,'gml_crawl=1')===false){
        $GLOBALS['gml_state_blocked_http']++;
        return new WP_Error('gml_state_network_forbidden');
    }
    if($GLOBALS['gml_state_during_render']){
        $callback=$GLOBALS['gml_state_during_render'];$GLOBALS['gml_state_during_render']=null;$callback();
    }
    if($GLOBALS['gml_state_render_mode']==='failure')return new WP_Error('gml_state_synthetic_timeout','Synthetic offline renderer failure');
    $path=trim(parse_url($url,PHP_URL_PATH),'/');
    $post=get_page_by_path($path);
    if(!$post)return new WP_Error('gml_state_missing_fixture');
    return ['response'=>['code'=>200],'headers'=>['content-type'=>'text/html'],'body'=>gml_state_html($post->ID)];
},999,3);
class GML_State_HTML_Protection extends GML_Translation_Output_Buffer {
    public function apply($html,$resource,$language) {
        $this->source_lang='en';$this->target_lang=$language;
        return $this->publication_is_index_ready(true,$resource)?$html:$this->protect_incomplete_translation($html);
    }
}
function gml_state_front_html($id,$language) {
    $resource=GML_Resource_Identity::for_post($id);
    $provider=new GML_Translation_Provider();
    $saved=$_SERVER['REQUEST_URI']??'/';
    $_SERVER['REQUEST_URI']=($language==='en'?'':'/'.$language).'/'.get_post_field('post_name',$id).'/';
    $old_query=$GLOBALS['wp_query'];$old_post=$GLOBALS['post']??null;
    $GLOBALS['wp_query']=new WP_Query(['page_id'=>$id]);$GLOBALS['post']=get_post($id);
    if(!isset($GLOBALS['gml_state_seo']))$GLOBALS['gml_state_seo']=new GML_SEO_Hreflang($provider);
    ob_start();$GLOBALS['gml_state_seo']->inject_multilingual_meta();$head=ob_get_clean();
    $html=str_replace('</head>',$head.'</head>',gml_state_html($id));
    if($language!=='en')$html=(new GML_State_HTML_Protection())->apply($html,$resource,$language);
    $_SERVER['REQUEST_URI']=$saved;
    $GLOBALS['wp_query']=$old_query;$GLOBALS['post']=$old_post;
    return $html;
}
function gml_state_snapshot($label) {
    global $wpdb;
    $f=get_option('gml_state_fixture');
    $key=$f['key'];$m=GML_Resource_Manifest_Store::get_by_key($key);
    $cluster=GML_Public_Eligibility::get_cluster($key);
    $rows=$wpdb->get_results($wpdb->prepare('SELECT * FROM '.GML_Resource_Manifest_Store::readiness_table().' WHERE resource_id=%d ORDER BY target_lang',$m->id),ARRAY_A);
    $languages=[];
    foreach(['de','es','fr','ru'] as $language){
        $r=$cluster['languages'][$language];
        $p=$r['page_readiness'];
        $languages[$language]=['machine'=>$r['machine_status'],'reason'=>$r['reason'],'seo_ready'=>$r['public_eligible'],'translation_percent'=>$p['percent'],'translated'=>$p['translated_count'],'required'=>$p['required_count'],'unresolved'=>$p['unresolved_count'],'critical_unresolved'=>$p['critical_unresolved_count'],'unknown_source_count'=>$p['unknown_source_count']??null,'length_percent'=>$p['source_bytes']?floor($p['translated_bytes']*1000/$p['source_bytes'])/10:null];
    }
    $snapshot=['stage'=>$label,'at_utc'=>gmdate('c'),'unix'=>microtime(true),'elapsed_ms'=>round((microtime(true)-$f['saved_at'])*1000),'resource_key'=>$key,'resource_id'=>(int)$m->id,'manifest_state'=>$m->discovery_state,'manifest_generation'=>(int)$m->manifest_generation,'manifest_fingerprint'=>$m->manifest_fingerprint,'source_revision'=>$m->source_revision,'ai_queue'=>GML_Translation_Controls::queue_status('fr'),'inventory'=>GML_Resource_Manifest_Manager::diagnostics($key),'languages'=>$languages,'readiness_rows'=>$rows,'front'=>[]];
    foreach(['en','fr'] as $language){
        $html=gml_state_front_html($f['id'],$language);
        $snapshot['front'][$language]=['hreflang_count'=>substr_count($html,'rel="alternate"'),'noindex'=>strpos($html,'content="noindex, follow"')!==false];
    }
    $run=preg_replace('/[^a-z0-9_-]/i','',getenv('GML_STATE_RUN')?:'baseline');
    $dir=getenv('GML_TEST_WP_ROOT').'/state-evidence/'.$run;
    if(!is_dir($dir))mkdir($dir,0777,true);

    file_put_contents($dir.'/'.$label.'-fr.html',gml_state_front_html($f['id'],'fr'));
    wp_set_current_user(1);
    $before_queries=count($wpdb->queries);$render_start=microtime(true);
    ob_start();(new GML_Page_Workflow_Admin())->render('pages');$ui=ob_get_clean();
    $snapshot['ui_queries']=count($wpdb->queries)-$before_queries;$snapshot['ui_render_ms']=round((microtime(true)-$render_start)*1000);
    file_put_contents($dir.'/'.$label.'.json',wp_json_encode($snapshot,JSON_PRETTY_PRINT));
    wp_set_current_user(0);
    file_put_contents($dir.'/'.$label.'-admin.html',$ui);
    echo 'STATE '.wp_json_encode(['stage'=>$label,'elapsed_ms'=>$snapshot['elapsed_ms'],'manifest'=>$m->discovery_state,'fr'=>$languages['fr'],'queue'=>$snapshot['ai_queue']['state']])."\n";
    return $snapshot;
}
function gml_state_dispatch($early=false) {
    $hook=GML_Resource_Manifest_Manager::DIRTY_HOOK;
    $when=wp_next_scheduled($hook);
    gml_db_assert($when!==false,'inventory event is recorded');
    gml_db_assert($early || $when<=time(),'normal fixture dispatch waits until the scheduled time');
    wp_unschedule_event($when,$hook);
    echo 'DISPATCH '.wp_json_encode(['hook'=>$hook,'scheduled_unix'=>$when,'actual_unix'=>microtime(true),'early_fixture_dispatch'=>$early])."\n";
    do_action($hook);
}
