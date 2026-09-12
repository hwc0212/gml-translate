<?php
/** Page progress and bounded, snapshot-safe failed-item operations. */
if (!defined('ABSPATH')) exit;
final class GML_Page_Workflow_Admin {
    public function __construct() {
        static $registered=false;
        if($registered) return;
        $registered=true;
        add_action('wp_ajax_gml_page_workflow',[$this,'action']);
        add_action('admin_enqueue_scripts',[$this,'assets']);
    }
    public function assets() {
        if(($_GET['page']??'')!=='gml-translate' || !in_array($_GET['tab']??'',['pages','failures'],true)) return;
        wp_enqueue_script('gml-page-workflow',GML_PLUGIN_URL.'assets/js/page-workflow.js',[],GML_VERSION,true);
        wp_enqueue_style('gml-page-workflow',GML_PLUGIN_URL.'assets/css/page-workflow.css',[],GML_VERSION);
        wp_localize_script('gml-page-workflow','gmlPageWorkflow',['url'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('gml_page_workflow')]);
    }
    public function action() {
        check_ajax_referer('gml_page_workflow','nonce');
        if(!current_user_can('manage_options')) wp_send_json_error(['message'=>'Permission denied.'],403);
        $operation=sanitize_key($_POST['operation']??'');
        $id=absint($_POST['id']??0);
        $expected=sanitize_text_field(wp_unslash($_POST['snapshot']??''));
        if($operation==='status') {
            $job=GML_Manual_Translation::job();
            if(($job['expires']??0)<time() && in_array($job['state']??'',['accepted','waiting','generating'],true)) $job['state']='expired';
            $status=['id'=>$job['id']??0,'state'=>$job['state']??'idle','candidate'=>$job['candidate']??'',
                'error'=>$job['error']??'','next_run'=>wp_next_scheduled(GML_Queue_Processor::CRON_HOOK),
                'cooldown'=>GML_Queue_Processor::get_backoff(),'circuit'=>GML_Queue_Processor::circuit_is_open()];
            wp_send_json_success($status);
        }
        if($operation==='ai') {
            $result=GML_Manual_Translation::request($id,$expected);
            if(is_wp_error($result)) wp_send_json_error(['message'=>$result->get_error_message()],409);
            wp_send_json_success($result);
        }
        if($operation==='save') {
            $saved=GML_Manual_Translation::save_manual($id,$expected,wp_unslash($_POST['translation']??''),!empty($_POST['release_hold']));
            if(!$saved) wp_send_json_error(['message'=>__('Not saved: source/translation conflict, invalid text, or held content requires explicit release. Refresh and review.','gml-translate')],409);
            wp_send_json_success(['state'=>'saved','snapshot'=>GML_Manual_Translation::token(GML_Manual_Translation::snapshot($id)),'message'=>__('Manual translation saved. Related pages are being refreshed.','gml-translate')]);
        }
        if($operation==='defer') {
            $lock=GML_Atomic_Option_Lock::acquire(GML_Queue_Processor::LOCK_OPTION,10);
            if(!$lock) wp_send_json_error(['message'=>'Worker busy.'],409);
            try {
                $snapshot=GML_Manual_Translation::snapshot($id);
                $conflict=!$snapshot || !hash_equals($expected,GML_Manual_Translation::token($snapshot));
                global $wpdb;
                $ok=$conflict?false:$wpdb->update($wpdb->prefix.'gml_queue',['status'=>'failed','attempts'=>3,'priority'=>-1],['id'=>$id]);
            } finally { GML_Atomic_Option_Lock::release(GML_Queue_Processor::LOCK_OPTION,$lock); }
            if($conflict) wp_send_json_error(['message'=>'Source conflict.'],409);
            if($ok===false) wp_send_json_error(['message'=>'Could not defer item.'],500);
            GML_Translation_Activity::record('item_deferred',['queue_id'=>$id,'actor'=>get_current_user_id()]);
            wp_send_json_success(['state'=>'deferred','message'=>__('Deferred; this text remains required for page completion.','gml-translate')]);
        }
        if($operation==='settings') {
            $old=GML_Page_Readiness_Policy::threshold();
            $old_demand=(bool)get_option('gml_page_demand_enabled',false);
            $percent=max(1,min(100,(int)($_POST['threshold']??98)));
            update_option('gml_page_ready_percent',$percent,false);
            update_option('gml_page_demand_enabled',!empty($_POST['demand']),false);
            update_option('gml_page_demand_days',max(1,min(30,(int)($_POST['days']??7))),false);
            $locations=get_registered_nav_menus();
            $location=sanitize_key($_POST['menu']??'primary');
            if(isset($locations[$location])) update_option('gml_priority_menu_location',$location,false);
            $keys=[];
            foreach(array_slice(preg_split('/\s+/',trim(wp_unslash($_POST['priorities']??''))),0,100) as $key) {
                $resource=GML_Resource_Identity::resolve($key);
                if($resource && $resource->is_eligible()) $keys[]=$resource->get_key();
            }
            update_option('gml_priority_resource_keys',array_values(array_unique($keys)),false);
            if(($old!==$percent || $old_demand!==!empty($_POST['demand'])) && !GML_Page_Cache::invalidate_all_clusters()) {
                update_option('gml_page_ready_percent',$old,false);
                update_option('gml_page_demand_enabled',$old_demand,false);
                wp_send_json_error(['message'=>'Cache invalidation could not be recorded. Previous readiness and demand settings were restored.'],500);
            }
            wp_send_json_success(['state'=>'saved','message'=>__('Page workflow settings saved.','gml-translate')]);
        }
        if($operation==='priority') {
            $key=sanitize_text_field(wp_unslash($_POST['resource']??''));
            $lang=sanitize_key($_POST['language']??'');
            $resource=GML_Resource_Identity::resolve($key);
            if(!$resource || !$resource->is_eligible() || !in_array($lang,GML_Language_Utils::enabled_local_target_codes(),true)) wp_send_json_error(['message'=>'Invalid resource or language.'],400);
            global $wpdb;
            $manifest=GML_Resource_Manifest_Store::get_by_key($key);
            if(!$manifest || $manifest->discovery_state!=='complete') wp_send_json_error(['message'=>'Scan the current page content first.'],409);
            $relations=GML_Resource_Manifest_Store::relation_table();
            $count=$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}gml_queue q INNER JOIN $relations s ON s.source_hash=q.source_hash
                SET q.priority=1000 WHERE s.resource_id=%d AND s.manifest_generation=%d AND q.target_lang=%s AND q.status='pending'",
                $manifest->id,$manifest->manifest_generation,$lang));
            if($count===false) wp_send_json_error(['message'=>'Priority update failed.'],500);
            wp_send_json_success(['state'=>'prioritized','message'=>__('Pending page text is prioritized. The background pause setting is unchanged.','gml-translate')]);
        }
        wp_send_json_error(['message'=>'Unknown action.'],400);
    }
    private function form_start($operation,array $fields=[]) {
        echo '<form class="gml-workflow-form" method="post"><input type="hidden" name="operation" value="'.esc_attr($operation).'">';
        foreach($fields as $key=>$value) echo '<input type="hidden" name="'.esc_attr($key).'" value="'.esc_attr($value).'">';
    }
    public function render($tab) {
        if(!current_user_can('manage_options')) return;
        echo '<section class="gml-page-workflow"><div id="gml-workflow-status" role="status" aria-live="polite"></div>';
        if($tab==='pages') $this->pages(); else $this->failures();
        echo '</section>';
    }
    private function pages() {
        echo '<h2>'.esc_html__('Page Translation Progress','gml-translate').'</h2>';
        $cache=(array)get_option('gml_resource_cache_worker_status',[]);
        if(($cache['state']??'adapter_required')==='adapter_required') echo '<p>'.esc_html__('GML page cache updates are active. External server/CDN cache needs an exact-URL adapter; external purge is not yet confirmed.','gml-translate').'</p>';
        $this->form_start('settings');
        echo '<table class="form-table"><tr><th><label for="gml-page-threshold">'.esc_html__('SEO readiness threshold (%)','gml-translate').'</label></th><td><input id="gml-page-threshold" name="threshold" type="number" min="1" max="100" value="'.esc_attr(GML_Page_Readiness_Policy::threshold()).'"></td></tr>';
        echo '<tr><th>'.esc_html__('First-party page demand','gml-translate').'</th><td><label><input type="checkbox" name="demand" value="1" '.checked(get_option('gml_page_demand_enabled',false),true,false).'> '.esc_html__('Enable anonymous aggregate counts after site consent','gml-translate').'</label> <input aria-label="Retention days" type="number" min="1" max="30" name="days" value="'.esc_attr(GML_Page_Demand::retention_days()).'"> '.esc_html__('days','gml-translate').'</td></tr>';
        echo '<tr><th>'.esc_html__('Primary navigation','gml-translate').'</th><td><select name="menu">';
        foreach(get_registered_nav_menus() as $key=>$label) echo '<option value="'.esc_attr($key).'" '.selected(get_option('gml_priority_menu_location','primary'),$key,false).'>'.esc_html($label).'</option>';
        echo '</select></td></tr><tr><th>'.esc_html__('Priority resource keys','gml-translate').'</th><td><textarea name="priorities" rows="3" class="large-text">'.esc_textarea(implode("\n",(array)get_option('gml_priority_resource_keys',[]))).'</textarea></td></tr></table>';
        submit_button(__('Save Page Workflow','gml-translate')); echo '</form>';
        $page=max(1,absint($_GET['workflow_page']??1));
        $result=GML_Resource_Approval::list_resources(['page'=>$page,'per_page'=>25]);
        $resources=[];
        foreach($result['rows'] as $row) $resources[]=$row['resource_key'];
        $clusters=GML_Public_Eligibility::get_clusters_bulk($resources);
        echo '<table class="widefat striped"><thead><tr><th>'.esc_html__('Page / Language','gml-translate').'</th><th>'.esc_html__('Translated / Required','gml-translate').'</th><th>'.esc_html__('SEO readiness','gml-translate').'</th><th>'.esc_html__('Actions','gml-translate').'</th></tr></thead><tbody>';
        foreach($result['rows'] as $row) {
            $status=$clusters[$row['resource_key']]['languages'][$row['target_lang']]??[];
            $policy=$status['page_readiness']??[];
            echo '<tr><td><code>'.esc_html($row['resource_key']).'</code><br>'.esc_html(strtoupper($row['target_lang'])).'</td><td>'.esc_html($row['translated_count'].' / '.$row['required_count']).'<br>'.esc_html(($policy['percent']??0).'%').'</td><td>'.esc_html($status['reason']??'unknown').'<br>'.esc_html__('Critical missing:','gml-translate').' '.esc_html($row['critical_missing_count']).'</td><td>';
            $this->form_start('priority',['resource'=>$row['resource_key'],'language'=>$row['target_lang']]);
            echo '<button type="submit" class="button">'.esc_html__('Prioritize This Page','gml-translate').'</button></form> ';
            echo '<a href="'.esc_url(add_query_arg(['page'=>'gml-translate','tab'=>'failures','resource'=>$row['resource_id'],'language'=>$row['target_lang']],admin_url('admin.php'))).'">'.esc_html__('Review Items','gml-translate').'</a></td></tr>';
        }
        echo '</tbody></table>';
        $this->pagination($page,$result['pages']);
    }
    private function failures() {
        global $wpdb;
        $lang=sanitize_key($_GET['language']??'');
        $history=($_GET['scope']??'current')==='history';
        $resource=absint($_GET['resource']??0);
        $reason=sanitize_text_field(wp_unslash($_GET['reason']??''));
        $page=max(1,absint($_GET['workflow_page']??1));
        $scope=GML_Translation_Readiness::current_queue_scope_sql('q');
        if($scope==='') $scope='1=1'; // Unknown inventory remains visible, never silently obsolete.
        $scope="(($scope) OR q.error_message LIKE '[candidate_ready]%')";
        $where=$history?"((q.status='failed' AND NOT ($scope)) OR (q.status='completed' AND COALESCE(q.error_message,'')<>''))":"q.status='failed' AND ($scope)";
        if($lang!=='') $where.=$wpdb->prepare(' AND q.target_lang=%s',$lang);
        if($reason!=='') $where.=$wpdb->prepare(' AND q.error_message LIKE %s','%'.$wpdb->esc_like($reason).'%');
        if($resource) $where.=$wpdb->prepare(' AND EXISTS(SELECT 1 FROM '.GML_Resource_Manifest_Store::relation_table().' s INNER JOIN '.GML_Resource_Manifest_Store::manifest_table().' m ON m.id=s.resource_id AND m.manifest_generation=s.manifest_generation WHERE s.source_hash=q.source_hash AND m.id=%d)',$resource);
        $table=$wpdb->prefix.'gml_queue';
        $total=(int)$wpdb->get_var("SELECT COUNT(*) FROM $table q WHERE $where");
        $rows=$wpdb->get_results($wpdb->prepare("SELECT q.* FROM $table q WHERE $where ORDER BY q.processed_at DESC,q.id DESC LIMIT 20 OFFSET %d",($page-1)*20));
        echo '<h2>'.esc_html__('Failed / Needs Attention','gml-translate').'</h2><form method="get"><input type="hidden" name="page" value="gml-translate"><input type="hidden" name="tab" value="failures"><select name="scope">';
        foreach(['current'=>__('Current','gml-translate'),'history'=>__('History','gml-translate')] as $key=>$label) echo '<option value="'.esc_attr($key).'" '.selected($history?'history':'current',$key,false).'>'.esc_html($label).'</option>';
        echo '</select> <input name="language" placeholder="Language" value="'.esc_attr($lang).'"> <input name="resource" type="number" placeholder="Resource ID" value="'.esc_attr($resource?:'').'"> <input name="reason" placeholder="Error category" value="'.esc_attr($reason).'"> <button class="button">'.esc_html__('Filter','gml-translate').'</button></form>';
        echo '<table class="widefat striped"><thead><tr><th>'.esc_html__('Source / Context','gml-translate').'</th><th>'.esc_html__('Error','gml-translate').'</th><th>'.esc_html__('Translation / Actions','gml-translate').'</th></tr></thead><tbody>';
        foreach($rows as $row) {
            $snapshot=GML_Manual_Translation::snapshot($row->id);
            $token=$snapshot?GML_Manual_Translation::token($snapshot):'';
            echo '<tr data-queue-id="'.esc_attr($row->id).'"><td style="max-width:380px;overflow-wrap:anywhere;">'.esc_html($row->source_text).'<p>'.esc_html(strtoupper($row->target_lang).' / '.$row->context_type).'</p><details><summary>'.esc_html__('Affected pages','gml-translate').' ('.count($snapshot['resources']??[]).')</summary>';
            foreach($snapshot['resources']??[] as $ref) echo '<div><code>'.esc_html($ref['resource_key']).'</code></div>';
            echo '</details></td><td style="max-width:260px;overflow-wrap:anywhere;">'.esc_html(GML_AI_HTTP_Transport::redact($row->error_message)).'<p>'.esc_html($row->processed_at?:$row->created_at).' / '.esc_html($row->attempts).' '.esc_html__('attempts','gml-translate').'</p></td><td>';
            if(!$history && $snapshot) {
                $fields=['id'=>$row->id,'snapshot'=>$token];
                $this->form_start('save',$fields);
                if(!empty($snapshot['tm'])) echo '<details><summary>'.esc_html__('Saved translation','gml-translate').' ('.esc_html($snapshot['tm']['status']).')</summary><pre style="white-space:pre-wrap;overflow-wrap:anywhere;">'.esc_html($snapshot['tm']['translated_text']).'</pre></details>';
                echo '<textarea name="translation" class="large-text" rows="4" aria-label="Translation">'.esc_textarea($snapshot['tm']['translated_text']??'').'</textarea>';
                $candidate=(array)get_option('gml_translation_candidate_'.(int)$row->id,[]);
                if(!empty($candidate['text'])) echo '<details><summary>'.esc_html__('AI candidate (not saved)','gml-translate').'</summary><pre style="white-space:pre-wrap;overflow-wrap:anywhere;">'.esc_html($candidate['text']).'</pre></details>';
                if(($snapshot['tm']['status']??'')==='pending') echo '<label><input type="checkbox" name="release_hold" value="1"> '.esc_html__('I reviewed this held text and explicitly release it as manual.','gml-translate').'</label><br>';
                echo '<button class="button">'.esc_html__('Save Manual Translation','gml-translate').'</button></form>';
                $this->form_start('ai',$fields);
                echo '<button class="button button-primary">'.esc_html__('AI Translate This Item','gml-translate').'</button></form>';
                $this->form_start('defer',$fields);
                echo '<button class="button">'.esc_html__('Defer','gml-translate').'</button></form>';
            } else echo esc_html__('Historical or stale source: rescan current content before translating.','gml-translate');
            echo '</td></tr>';
        }
        echo '</tbody></table>';
        if(!$rows) echo '<p>'.esc_html__('No matching failed items.','gml-translate').'</p>';
        $this->pagination($page,(int)ceil($total/20));
    }
    private function pagination($page,$pages) {
        echo '<p>'.esc_html($page.' / '.max(1,$pages)).' ';
        if($page>1) echo '<a class="button" href="'.esc_url(add_query_arg('workflow_page',$page-1)).'">'.esc_html__('Previous','gml-translate').'</a> ';
        if($page<$pages) echo '<a class="button" href="'.esc_url(add_query_arg('workflow_page',$page+1)).'">'.esc_html__('Next','gml-translate').'</a>';
        echo '</p>';
    }
}
