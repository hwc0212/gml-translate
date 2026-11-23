<?php
require dirname(__DIR__).'/fixtures/state-clarity.php';
echo "MARKER wordpress_bootstrap_loaded\n";
$mode=$argv[1]??'';
global $wpdb;
$index=$wpdb->prefix.'gml_index';$queue=$wpdb->prefix.'gml_queue';
if($mode==='prepare'){
    wp_set_current_user(1);
    gml_db_assert(GML_Installer::activate()===true,'isolated fixture tables initialized');
    update_option('blog_public',1);update_option('gml_multilingual_enabled',true);
    update_option('gml_source_lang','en');
    update_option('gml_languages',array_map(static function($code){return ['code'=>$code,'enabled'=>true,'paused'=>false];},['de','es','fr','ru']));
    update_option('gml_ai_translation_enabled',true);update_option('gml_translation_paused',false);
    GML_Gemini_API::save_api_key('synthetic-offline-no-provider','gemini');
    update_option(GML_Resource_Backfill::OPTION,['status'=>'complete','phase'=>'complete','cursor'=>0,'updated_at'=>time()]);
    update_option('permalink_structure','/%postname%/');$wp_rewrite->init();
    $script='<script>document.addEventListener("wpcf7mailsent",function(){setTimeout(function(){location.href="/synthetic-thanks/";},300);});</script>';
    $content='';
    for($n=0;$n<159;$n++)$content.='<p>Synthetic specification '.sprintf('%03d',$n).'.</p>';
    $content.='[contact-form-7 id="synthetic"]'.$script;
    $id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Synthetic inquiry','post_name'=>'synthetic-inquiry','post_content'=>$content]);
    $other=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Unrelated synthetic page','post_name'=>'unrelated-synthetic','post_content'=>'<p>Unrelated content.</p>']);
    foreach([$id,$other] as $post){
        $resource=GML_Resource_Identity::for_post($post);
        gml_db_assert((new GML_Resource_Manifest_Discovery())->discover($resource)===true,'synthetic page inventory discovered');
        $nodes=(new GML_HTML_Parser())->parse(gml_state_html($post))['nodes'];
        if($post===$id)echo 'SOURCE_COUNT '.wp_json_encode(['count'=>count(array_unique(array_column($nodes,'hash'))),'contexts'=>array_count_values(array_column($nodes,'context_type'))])."\n";
        if($post===$id)gml_db_assert(count(array_unique(array_column($nodes,'hash')))===163,'synthetic page has 163 distinct translatable items');
        $wpdb->query('START TRANSACTION');
        foreach(['de','es','fr','ru'] as $lang)foreach($nodes as $node){
            $wpdb->replace($index,['source_hash'=>$node['hash'],'source_text'=>$node['text'],'source_lang'=>'en','target_lang'=>$lang,'translated_text'=>strtoupper($lang).' '.$node['text'],'context_type'=>$node['context_type'],'status'=>'manual','created_at'=>current_time('mysql'),'updated_at'=>current_time('mysql')]);
        }
        gml_db_assert($wpdb->query('COMMIT')!==false,'synthetic manual translation seed committed');
        $m=GML_Resource_Manifest_Store::get_by_key($resource->get_key());
        GML_Resource_Readiness::recalculate_resources([$m->id]);
    }
    delete_option(GML_Resource_Manifest_Manager::DIRTY_OPTION);
    wp_clear_scheduled_hook(GML_Resource_Manifest_Manager::DIRTY_HOOK);
    $m=GML_Resource_Manifest_Store::get_by_key(GML_Resource_Identity::for_post($id)->get_key());
    $other_key=GML_Resource_Identity::for_post($other)->get_key();
    $other_m=GML_Resource_Manifest_Store::get_by_key($other_key);
    update_option('gml_state_fixture',['id'=>$id,'other'=>$other,'other_key'=>$other_key,'other_manifest'=>(array)$other_m,'other_readiness'=>$wpdb->get_results($wpdb->prepare('SELECT * FROM '.GML_Resource_Manifest_Store::readiness_table().' WHERE resource_id=%d ORDER BY target_lang',$other_m->id),ARRAY_A),'key'=>$m->resource_key,'script'=>$script,'original_content'=>$content,'saved_at'=>microtime(true),'tm_hash'=>hash('sha256',serialize($wpdb->get_results("SELECT * FROM $index ORDER BY id",ARRAY_A))),'queue_hash'=>hash('sha256',serialize($wpdb->get_results("SELECT * FROM $queue ORDER BY id",ARRAY_A)))]);
    $snapshot=gml_state_snapshot('01-ready');
    foreach($snapshot['languages'] as $state)gml_db_assert($state['seo_ready'] && $state['translation_percent']==100 && $state['length_percent']==100,'all languages start ready at 100 percent');
    gml_db_assert($snapshot['ai_queue']['state']==='idle' && $snapshot['front']['fr']['hreflang_count']===6,'AI idle and complete hreflang are recorded');
    echo "OK state clarity prepare\n";
    exit;
}
$f=get_option('gml_state_fixture');gml_db_assert(is_array($f),'synthetic fixture mapping loaded');
if($mode==='remove-script'){
    $before=(new GML_HTML_Parser())->parse(gml_state_html($f['id']))['nodes'];
    $content=str_replace($f['script'],'',$f['original_content']);
    gml_db_assert($content!==$f['original_content'] && strpos($content,'[contact-form-7 id="synthetic"]')!==false,'only the synthetic nontranslatable script is removed; shortcode retained');
    $f['saved_at']=microtime(true);update_option('gml_state_fixture',$f);
    wp_update_post(['ID'=>$f['id'],'post_content'=>$content]);
    $after=(new GML_HTML_Parser())->parse(gml_state_html($f['id']))['nodes'];
    gml_db_assert(array_column($before,'hash')===array_column($after,'hash'),'script-only edit leaves the complete source text set unchanged');
    $snapshot=gml_state_snapshot('02-saved-stale');
    foreach($snapshot['languages'] as $state)gml_db_assert($state['machine']==='stale' && !$state['seo_ready'] && $state['translation_percent']==100 && $state['length_percent']===null,'stored 100 percent coexists with pending current-version readiness');
    gml_db_assert($snapshot['ai_queue']['state']==='idle','AI translation queue is idle while inventory work is pending');
    gml_db_assert($snapshot['inventory']['resource_pending'] && $snapshot['inventory']['next_dirty_run']>0,'save has durable pending work and a scheduled inventory event');
    gml_db_assert($snapshot['front']['en']['hreflang_count']===2 && !$snapshot['front']['en']['noindex'] && $snapshot['front']['fr']['hreflang_count']===0 && $snapshot['front']['fr']['noindex'],'source/target SEO fixture matches the reported temporary state');
}elseif($mode==='delayed'){
    $snapshot=gml_state_snapshot('03-due-dispatcher-held');
    gml_db_assert($snapshot['inventory']['next_dirty_run']<=time() && !$snapshot['languages']['fr']['seo_ready'],'an intentionally held due event leaves the current version gated');
}elseif($mode==='recover'){
    $GLOBALS['gml_state_during_render']=static function(){gml_state_snapshot('04-worker-rendering');};
    gml_state_dispatch(false);
    $snapshot=gml_state_snapshot('05-recovered');
    foreach($snapshot['languages'] as $state)gml_db_assert($state['seo_ready'] && $state['translation_percent']==100 && $state['length_percent']==100,'all languages recover without changing translations');
    gml_db_assert($snapshot['front']['fr']['hreflang_count']===6 && !$snapshot['front']['fr']['noindex'],'target alternates and index policy recover');
}elseif($mode==='text-edit'){
    $content=str_replace('Synthetic specification 000.',str_repeat('A genuinely new synthetic specification needs a translation. ',30),str_replace($f['script'],'',$f['original_content']));
    wp_update_post(['ID'=>$f['id'],'post_content'=>$content]);
    $snapshot=gml_state_snapshot('06-real-text-stale');
    foreach($snapshot['languages'] as $state)gml_db_assert(!$state['seo_ready'],'old complete rows never authorize an edited current version');
    gml_state_dispatch(true);
    $snapshot=gml_state_snapshot('07-real-text-missing');
    foreach($snapshot['languages'] as $state)gml_db_assert(!$state['seo_ready'] && $state['translated']===162 && $state['required']===163 && $state['reason']==='below_page_threshold','unrecorded source length keeps real untranslated text behind the original gate');
}elseif($mode==='critical'){
    wp_update_post(['ID'=>$f['id'],'post_title'=>'A new synthetic SEO title','post_content'=>str_replace($f['script'],'',$f['original_content'])]);
    gml_state_dispatch(true);
    $snapshot=gml_state_snapshot('07b-critical-missing');
    foreach($snapshot['languages'] as $state)gml_db_assert(!$state['seo_ready'] && $state['critical_unresolved']===1,'a missing current SEO title remains behind the critical gate');
}elseif($mode==='failure'){
    wp_update_post(['ID'=>$f['id'],'post_title'=>'Synthetic inquiry','post_content'=>str_replace($f['script'],'',$f['original_content'])]);
    $GLOBALS['gml_state_render_mode']='failure';
    gml_state_dispatch(true);
    $snapshot=gml_state_snapshot('08-render-failed');
    gml_db_assert($snapshot['inventory']['resource_pending'] && !empty($snapshot['inventory']['last_failure']) && !$snapshot['languages']['fr']['seo_ready'],'renderer failure remains pending with a recorded reason');
    gml_state_dispatch(true);
    $snapshot=gml_state_snapshot('09-retry-wait');
    gml_db_assert($snapshot['inventory']['resource_retry_after']>time(),'unchanged repeated failure records a retry delay');
}elseif($mode==='failure-recover'){
    // Test-only clock injection: make the recorded retry due without waiting a minute.
    $retry='gml_manifest_retry_'.md5($f['key']);$value=get_transient($retry);$value['after']=time()-1;set_transient($retry,$value,DAY_IN_SECONDS);
    gml_state_dispatch(true);
    $snapshot=gml_state_snapshot('10-failure-recovered');
    foreach($snapshot['languages'] as $state)gml_db_assert($state['seo_ready'],'recorded failure recovers through the original worker');
}elseif($mode==='reschedule'){
    wp_update_post(['ID'=>$f['id'],'post_content'=>str_replace($f['script'],'',$f['original_content'])]);
    $when=wp_next_scheduled(GML_Resource_Manifest_Manager::DIRTY_HOOK);
    wp_unschedule_event($when,GML_Resource_Manifest_Manager::DIRTY_HOOK);
    gml_db_assert(!wp_next_scheduled(GML_Resource_Manifest_Manager::DIRTY_HOOK),'test-only fault removes the recorded event');
    GML_Resource_Manifest_Manager::maybe_schedule();
    gml_db_assert(wp_next_scheduled(GML_Resource_Manifest_Manager::DIRTY_HOOK)!==false,'existing wp_loaded scheduling callback restores a missing event');
    $snapshot=gml_state_snapshot('11-event-restored');
    gml_db_assert(!$snapshot['languages']['fr']['seo_ready'],'scheduling callback alone does not mark readiness complete');
    gml_state_dispatch(true);
    $snapshot=gml_state_snapshot('12-event-recovered');
    gml_db_assert($snapshot['languages']['fr']['seo_ready'],'restored event recovers through the original worker');
}elseif($mode==='ui-race'){
    wp_set_current_user(1);$changed=false;
    $race=static function($sql)use(&$changed,$f){
        if(!$changed && strpos($sql,'AS current_manifest_generation')!==false){
            $changed=true;
            wp_update_post(['ID'=>$f['id'],'post_content'=>str_replace($f['script'],'',$f['original_content'])]);
        }
        return $sql;
    };
    add_filter('query',$race);ob_start();(new GML_Page_Workflow_Admin())->render('pages');$ui=ob_get_clean();remove_filter('query',$race);
    gml_db_assert($changed && strpos($ui,'Current-version readiness needs evaluation.')!==false,'a save during admin rendering cannot reuse its earlier ready label');
    $snapshot=gml_state_snapshot('13-ui-race-pending');
    gml_db_assert(!$snapshot['languages']['fr']['seo_ready'],'concurrent admin save retains the original SEO gate');
    gml_state_dispatch(true);gml_state_snapshot('14-ui-race-recovered');
}elseif($mode==='tm-signal'){
    $table=GML_Resource_Manifest_Store::readiness_table();$manifest=GML_Resource_Manifest_Store::get_by_key($f['key']);
    $other_languages=$wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE resource_id=%d AND target_lang<>'fr' ORDER BY target_lang",$manifest->id),ARRAY_A);
    // A synthetic TM invalidation notification; no translation content is changed.
    GML_Resource_Readiness::translation_changed(md5('Synthetic specification 000.'),'fr');
    $snapshot=gml_state_snapshot('15-tm-invalidated');
    gml_db_assert($snapshot['languages']['fr']['machine']==='stale','TM invalidation uses the existing durable stale path');
    $claimed=false;$capture=static function()use(&$claimed){$claimed=true;gml_state_snapshot('16-readiness-claimed');};
    add_action('gml_resource_readiness_batch_claimed',$capture);
    GML_Resource_Readiness::run_rebuild_batch('drain');
    remove_action('gml_resource_readiness_batch_claimed',$capture);
    $snapshot=gml_state_snapshot('17-tm-ready');
    gml_db_assert($claimed && $snapshot['languages']['fr']['seo_ready'],'the original claimed readiness batch restores the unchanged TM snapshot');
    gml_db_assert($wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE resource_id=%d AND target_lang<>'fr' ORDER BY target_lang",$manifest->id),ARRAY_A)===$other_languages,'one target-language invalidation preserves other language rows');
}elseif($mode==='verify'){
    gml_db_assert(hash('sha256',serialize($wpdb->get_results("SELECT * FROM $index ORDER BY id",ARRAY_A)))===$f['tm_hash'],'every existing TM field remains unchanged');
    gml_db_assert(hash('sha256',serialize($wpdb->get_results("SELECT * FROM $queue ORDER BY id",ARRAY_A)))===$f['queue_hash'],'every translation queue row remains unchanged');
    gml_db_assert((array)GML_Resource_Manifest_Store::get_by_key($f['other_key'])===$f['other_manifest'],'unrelated resource manifest remains byte-for-byte unchanged');
    $other=GML_Resource_Manifest_Store::get_by_key($f['other_key']);
    gml_db_assert($wpdb->get_results($wpdb->prepare('SELECT * FROM '.GML_Resource_Manifest_Store::readiness_table().' WHERE resource_id=%d ORDER BY target_lang',$other->id),ARRAY_A)===$f['other_readiness'],'unrelated multilingual readiness rows remain unchanged');
    gml_db_assert($GLOBALS['gml_state_blocked_http']===0,'no external or provider HTTP was attempted');
    gml_db_assert(get_option('active_plugins',[])===[],'source tests never activate or install the plugin');
}else throw new RuntimeException('Unknown state clarity test mode');
echo "OK state clarity ".$mode."\n";
