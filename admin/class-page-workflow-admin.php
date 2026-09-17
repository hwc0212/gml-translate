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
        wp_localize_script('gml-page-workflow','gmlPageWorkflow',['url'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('gml_page_workflow'),'i18n'=>[
            'failed'=>__('Request failed. Refresh and try again.','gml-translate'),
            'changed'=>__('The active task changed. Refresh to review.','gml-translate'),
            'task'=>__('Task','gml-translate'),'submitting'=>__('Submitting...','gml-translate'),
            'provider'=>__('Provider configuration requires attention.','gml-translate'),
            'candidate'=>__('Candidate ready. Compare with the saved translation, then explicitly save your choice.','gml-translate'),
            'waiting'=>__('Check WordPress Cron and provider cooldown; refresh for current status.','gml-translate'),
            'states'=>['accepted'=>__('Accepted','gml-translate'),'waiting'=>__('Waiting','gml-translate'),'generating'=>__('Generating','gml-translate'),'candidate'=>__('Candidate','gml-translate'),'saved'=>__('Saved','gml-translate'),'failed'=>__('Failed','gml-translate'),'expired'=>__('Expired','gml-translate'),'idle'=>__('Idle','gml-translate')]
        ]]);
    }
    public function action() {
        check_ajax_referer('gml_page_workflow','nonce');
        if(!current_user_can('manage_options')) wp_send_json_error(['message'=>__('Permission denied.','gml-translate')],403);
        $operation=sanitize_key($_POST['operation']??'');
        $id=absint($_POST['id']??0);
        $expected=sanitize_text_field(wp_unslash($_POST['snapshot']??''));
        if(in_array($operation,['keep_source','revoke_keep_source'],true)) {
            $result=GML_Item_Resolution::decide($id,absint($_POST['resource_id']??0),sanitize_text_field(wp_unslash($_POST['resolution_snapshot']??'')),
                $operation==='keep_source'?'keep_source':'revoke',($_POST['critical_confirmation']??'')==='KEEP SOURCE');
            if(is_wp_error($result)) wp_send_json_error(['message'=>$result->get_error_message(),'code'=>$result->get_error_code()],409);
            wp_send_json_success(['state'=>'resolved','reload'=>true,'message'=>__('Page decision saved. Translation assets and background pause are unchanged.','gml-translate')]);
        }
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
            if(is_wp_error($result)) {
                $messages=[
                    'ai_disabled'=>__('AI translation is disabled or its key is unavailable.','gml-translate'),
                    'provider_circuit'=>__('Test and repair the provider configuration first.','gml-translate'),
                    'worker_busy'=>__('A worker request is already running. Retry when it finishes.','gml-translate'),
                    'manual_busy'=>__('Another explicit item is being processed.','gml-translate'),
                    'source_conflict'=>__('The source or translation changed. Refresh and review it again.','gml-translate'),
                    'rate_limited'=>__('Wait 30 seconds before another explicit request.','gml-translate'),
                    'language_disabled'=>__('This local language is disabled.','gml-translate'),
                    'transaction_unavailable'=>__('Transactional storage is required.','gml-translate'),
                    'queue_write'=>__('The request could not be saved.','gml-translate'),
                    'job_write'=>__('The request could not be recorded.','gml-translate')
                ];
                wp_send_json_error(['message'=>$messages[$result->get_error_code()]??$result->get_error_message(),'code'=>$result->get_error_code()],409);
            }
            wp_send_json_success($result);
        }
        if($operation==='save') {
            $saved=GML_Manual_Translation::save_manual($id,$expected,wp_unslash($_POST['translation']??''),!empty($_POST['release_hold']));
            if(!$saved) wp_send_json_error(['message'=>__('Not saved: source/translation conflict, invalid text, or held content requires explicit release. Refresh and review.','gml-translate')],409);
            wp_send_json_success(['state'=>'saved','reload'=>true,'snapshot'=>GML_Manual_Translation::token(GML_Manual_Translation::snapshot($id)),'message'=>__('Manual translation saved. Related pages are being refreshed.','gml-translate')]);
        }
        if($operation==='defer') {
            $lock=GML_Atomic_Option_Lock::acquire(GML_Queue_Processor::LOCK_OPTION,10);
            if(!$lock) wp_send_json_error(['message'=>__('Worker busy. Try again after the current request.','gml-translate')],409);
            try {
                $snapshot=GML_Manual_Translation::snapshot($id);
                $conflict=!$snapshot || !hash_equals($expected,GML_Manual_Translation::token($snapshot));
                global $wpdb;
                $ok=$conflict?false:$wpdb->update($wpdb->prefix.'gml_queue',['status'=>'failed','attempts'=>3,'priority'=>-1],['id'=>$id]);
            } finally { GML_Atomic_Option_Lock::release(GML_Queue_Processor::LOCK_OPTION,$lock); }
            if($conflict) wp_send_json_error(['message'=>__('The source or translation changed. Refresh and review it again.','gml-translate')],409);
            if($ok===false) wp_send_json_error(['message'=>__('Could not defer item.','gml-translate')],500);
            GML_Translation_Activity::record('item_deferred',['queue_id'=>$id,'actor'=>get_current_user_id()]);
            wp_send_json_success(['state'=>'deferred','reload'=>true,'message'=>__('Deferred; this text remains required for page completion.','gml-translate')]);
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
            // This explicit page/language request, unlike inventory-only discovery,
            // authorizes the missing-set enqueue but never resumes paused AI work.
            $discovered=(new GML_Resource_Manifest_Discovery())->discover($resource,$lang);
            if(is_wp_error($discovered) || $discovered!==true) wp_send_json_error(['message'=>is_wp_error($discovered)?$discovered->get_error_message():'Page discovery failed.'],409);
            $manifest=GML_Resource_Manifest_Store::get_by_key($key);
            $relations=GML_Resource_Manifest_Store::relation_table();
            $count=$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}gml_queue q INNER JOIN $relations s ON s.source_hash=q.source_hash
                SET q.priority=1000 WHERE s.resource_id=%d AND s.manifest_generation=%d AND q.target_lang=%s AND q.status='pending'",
                $manifest->id,$manifest->manifest_generation,$lang));
            if($count===false) wp_send_json_error(['message'=>'Priority update failed.'],500);
            if(GML_Translation_State::work_enabled()) GML_Queue_Processor::ensure_scheduled();
            wp_send_json_success(['state'=>'prioritized','message'=>__('Missing page text was queued and prioritized. Existing failures require explicit recovery. The background pause setting is unchanged.','gml-translate')]);
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
        global $wpdb;
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
        $schedules=[];
        echo '<table class="widefat striped"><thead><tr><th>'.esc_html__('Page / Language','gml-translate').'</th><th>'.esc_html__('Translated / Required','gml-translate').'</th><th>'.esc_html__('SEO readiness','gml-translate').'</th><th>'.esc_html__('Actions','gml-translate').'</th></tr></thead><tbody>';
        foreach($result['rows'] as $row) {
            $status=$clusters[$row['resource_key']]['languages'][$row['target_lang']]??[];
            $policy=$status['page_readiness']??[];
            $critical=$policy['critical_fields']??[];
            $bytes=(int)($policy['source_bytes']??0);
            $length=$bytes?floor((int)($policy['translated_bytes']??0)*1000/$bytes)/10:null;
            if(!isset($schedules[$row['target_lang']])) $schedules[$row['target_lang']]=GML_Translation_Controls::queue_status($row['target_lang']);
            $schedule=$schedules[$row['target_lang']];
            $detail=__('Length coverage:','gml-translate').' '.($length===null?__('Not measured','gml-translate'):$length.'%')
                .' / '.__('Missing fields:','gml-translate').' '.($critical?implode(', ',$critical):__('None','gml-translate'))
                .' / '.__('Queue:','gml-translate').' '.($schedule['state']??'unknown');
            echo '<tr><td><code>'.esc_html($row['resource_key']).'</code><br>'.esc_html(strtoupper($row['target_lang'])).'</td><td>';
            echo esc_html__('Translation coverage','gml-translate').': '.esc_html(($policy['percent']??0).'%').' ('.esc_html(($policy['translated_count']??0).' / '.$row['required_count']).')<br>';
            echo esc_html__('Resolved coverage','gml-translate').': '.esc_html(($policy['resolved_percent']??0).'%').'<br>';
            foreach(['auto_count'=>__('Auto','gml-translate'),'manual_count'=>__('Manual','gml-translate'),'keep_source_count'=>__('Keep source','gml-translate'),'unresolved_count'=>__('Unresolved','gml-translate')] as $key=>$label) echo esc_html($label).': '.esc_html($policy[$key]??0).' ';
            echo '</td><td>'.esc_html(self::reason_label($status['reason']??'unknown')).'<br>'.esc_html__('Critical unresolved:','gml-translate').' '.esc_html($policy['critical_unresolved_count']??$row['critical_missing_count']).'</td><td>';
            $this->form_start('priority',['resource'=>$row['resource_key'],'language'=>$row['target_lang']]);
            echo '<p>'.esc_html($detail).'</p>';
            echo '<button type="submit" class="button">'.esc_html__('Queue Missing Text and Prioritize','gml-translate').'</button></form> ';
            echo '<a href="'.esc_url(add_query_arg(['page'=>'gml-translate','tab'=>'failures','resource'=>$row['resource_id'],'language'=>$row['target_lang']],admin_url('admin.php'))).'">'.esc_html__('Review Items','gml-translate').'</a></td></tr>';
        }
        echo '</tbody></table>';
        $this->pagination($page,$result['pages']);
    }
    private function failures() {
        global $wpdb;
        $lang=sanitize_key($_GET['language']??'');
        $history=($_GET['scope']??'current')==='history';
        $resolved=($_GET['scope']??'current')==='resolved';
        $deferred=($_GET['scope']??'current')==='deferred';
        $resource=absint($_GET['resource']??0);
        $reason=sanitize_text_field(wp_unslash($_GET['reason']??''));
        $page=max(1,absint($_GET['workflow_page']??1));
        $scope=GML_Translation_Readiness::current_queue_scope_sql('q');
        if($scope==='') $scope='1=1'; // Unknown inventory remains visible, never silently obsolete.
        $scope="(($scope) OR q.error_message LIKE '[candidate_ready]%')";
        $where=$history?"((q.status='failed' AND NOT ($scope)) OR (q.status='completed' AND COALESCE(q.error_message,'')<>''))":"q.status='failed' AND ($scope)";
        if(!$history) {
            $valid="EXISTS(SELECT 1 FROM {$wpdb->prefix}gml_index ti WHERE ti.source_hash=q.source_hash AND ti.source_lang=q.source_lang AND ti.target_lang=q.target_lang AND ti.status IN ('auto','manual'))";
            $held="EXISTS(SELECT 1 FROM {$wpdb->prefix}gml_index ti WHERE ti.source_hash=q.source_hash AND ti.source_lang=q.source_lang AND ti.target_lang=q.target_lang AND ti.status NOT IN ('auto','manual'))";
            $join=GML_Item_Resolution::join_sql('m','s','q.target_lang','q.source_lang');
            $local=$resource?$wpdb->prepare(' AND m.id=%d',$resource):'';
            $relation="SELECT 1 FROM ".GML_Resource_Manifest_Store::relation_table().' s INNER JOIN '.GML_Resource_Manifest_Store::manifest_table()." m ON m.id=s.resource_id AND m.manifest_generation=s.manifest_generation $join
                WHERE s.source_hash=q.source_hash AND s.context_type=q.context_type AND m.discovery_state='complete'
                AND m.global_generation=".(int)GML_Resource_Manifest_Manager::global_generation().$local;
            $where=$resolved?"NOT $held AND EXISTS($relation AND k.id IS NOT NULL)":"(q.status IN ('failed','pending','processing') OR $held) AND ($scope) AND (NOT $valid OR q.error_message LIKE '[candidate_ready]%') AND EXISTS($relation AND (k.id IS NULL OR $held))";
            if(!$resolved) $where.=$deferred?' AND q.priority<0':' AND q.priority>=0';
        }
        if($lang!=='') $where.=$wpdb->prepare(' AND q.target_lang=%s',$lang);
        if($reason!=='') $where.=$wpdb->prepare(' AND q.error_message LIKE %s','%'.$wpdb->esc_like($reason).'%');
        if($resource) $where.=$wpdb->prepare(' AND EXISTS(SELECT 1 FROM '.GML_Resource_Manifest_Store::relation_table().' s INNER JOIN '.GML_Resource_Manifest_Store::manifest_table().' m ON m.id=s.resource_id AND m.manifest_generation=s.manifest_generation WHERE s.source_hash=q.source_hash AND m.id=%d)',$resource);
        $table=$wpdb->prefix.'gml_queue';
        $group="SELECT MAX(q.id) AS id,COUNT(*) AS failure_records FROM $table q WHERE $where GROUP BY q.source_hash,q.source_lang,q.target_lang,q.context_type,BINARY q.source_text";
        $total=(int)$wpdb->get_var("SELECT COUNT(*) FROM ($group) assets");
        $rows=$wpdb->get_results($wpdb->prepare("SELECT q.*,assets.failure_records FROM $table q INNER JOIN ($group) assets ON assets.id=q.id ORDER BY q.processed_at DESC,q.id DESC LIMIT 20 OFFSET %d",($page-1)*20));
        echo '<h2>'.esc_html__('Failed / Needs Attention','gml-translate').'</h2><form method="get"><input type="hidden" name="page" value="gml-translate"><input type="hidden" name="tab" value="failures"><select name="scope">';
        foreach(['current'=>__('Current actionable','gml-translate'),'resolved'=>__('Kept source decisions','gml-translate'),'deferred'=>__('Deferred','gml-translate'),'history'=>__('History','gml-translate')] as $key=>$label) echo '<option value="'.esc_attr($key).'" '.selected($history?'history':($resolved?'resolved':($deferred?'deferred':'current')),$key,false).'>'.esc_html($label).'</option>';
        echo '</select> <input name="language" placeholder="'.esc_attr__('Language','gml-translate').'" value="'.esc_attr($lang).'"> <input name="resource" type="number" placeholder="'.esc_attr__('Resource ID','gml-translate').'" value="'.esc_attr($resource?:'').'"> <input name="reason" placeholder="'.esc_attr__('Error category','gml-translate').'" value="'.esc_attr($reason).'"> <button class="button">'.esc_html__('Filter','gml-translate').'</button></form>';
        echo '<table class="widefat striped"><thead><tr><th>'.esc_html__('Source / Context','gml-translate').'</th><th>'.esc_html__('Error','gml-translate').'</th><th>'.esc_html__('Translation / Actions','gml-translate').'</th></tr></thead><tbody>';
        foreach($rows as $row) {
            $snapshot=GML_Manual_Translation::snapshot($row->id);
            $token=$snapshot?GML_Manual_Translation::token($snapshot):'';
            if((int)$row->failure_records>1) {
                $history_rows=$wpdb->get_results($wpdb->prepare("SELECT id,processed_at,created_at,attempts,error_message FROM $table WHERE source_hash=%s AND source_lang=%s AND target_lang=%s AND context_type=%s AND BINARY source_text=BINARY %s ORDER BY id DESC LIMIT 25",$row->source_hash,$row->source_lang,$row->target_lang,$row->context_type,$row->source_text));
            } else $history_rows=[];
            echo '<tr data-queue-id="'.esc_attr($row->id).'"><td style="max-width:380px;overflow-wrap:anywhere;">'.esc_html($row->source_text).'<p>'.esc_html(strtoupper($row->target_lang).' / '.$row->context_type).'</p><details><summary>'.esc_html__('Affected pages','gml-translate').' ('.count($snapshot['resources']??[]).')</summary>';
            foreach($snapshot['resources']??[] as $ref) echo '<div><code>'.esc_html($ref['resource_key']).'</code></div>';
            echo '</details></td><td style="max-width:260px;overflow-wrap:anywhere;">'.esc_html(GML_AI_HTTP_Transport::redact($row->error_message)).'<p>'.esc_html($row->processed_at?:$row->created_at).' / '.esc_html($row->attempts).' '.esc_html__('attempts','gml-translate').'</p></td><td>';
            $diagnostic=GML_Translation_Error::diagnostic($row);
            if($diagnostic) {
                echo '<details class="gml-protected-diagnostic"><summary>'.esc_html__('Protected content difference (not saved)','gml-translate').'</summary><dl>';
                foreach (['rule'=>__('Rule','gml-translate'),'source_token'=>__('Source token','gml-translate'),'candidate_token'=>__('Candidate token','gml-translate'),'source_hash'=>__('Source hash','gml-translate')] as $key=>$label)
                    echo '<dt>'.esc_html($label).'</dt><dd><code>'.esc_html($diagnostic[$key]??__('Missing token','gml-translate')).'</code></dd>';
                echo '</dl><p>'.esc_html__('This rejected candidate was not written to Translation Memory. Sensitive URL parts are replaced with identity digests. Review before editing.','gml-translate').'</p><pre style="white-space:pre-wrap;overflow-wrap:anywhere;max-width:560px;">'.esc_html($diagnostic['candidate']??'').'</pre>';
                if(!empty($diagnostic['candidate_truncated'])) echo '<p>'.esc_html__('Diagnostic candidate was truncated at the storage limit.','gml-translate').'</p>';
                echo '</details>';
            } elseif(strpos((string)$row->error_message,'[protected_term]')===0) {
                echo '<p>'.esc_html__('No candidate diagnostic was retained for this attempt. The old message alone cannot prove what the provider changed.','gml-translate').'</p>';
            }
            if($history_rows) {
                echo '<details><summary>'.esc_html(sprintf(__('%d stored records for this asset','gml-translate'),$row->failure_records)).'</summary>';
                foreach($history_rows as $entry) echo '<p>'.esc_html(($entry->processed_at?:$entry->created_at).' / '.$entry->attempts.' / '.GML_AI_HTTP_Transport::redact($entry->error_message)).'</p>';
                echo '</details>';
            }
            if(!$history && $snapshot) {
                $fields=['id'=>$row->id,'snapshot'=>$token];
                echo '<p>'.esc_html__('Manual translation updates this shared asset on all affected pages. Keep source text applies only to the selected page and language.','gml-translate').'</p>';
                foreach($snapshot['resources'] as $ref) {
                    if($resource && (int)$ref['id']!==$resource) continue;
                    $decision=GML_Item_Resolution::snapshot($row->id,$ref['id']);
                    if(!$decision) continue;
                    $active=!empty($decision['decision']);
                    $this->form_start($active?'revoke_keep_source':'keep_source',['id'=>$row->id,'resource_id'=>$ref['id'],'resolution_snapshot'=>GML_Item_Resolution::token($decision)]);
                    echo '<p><code>'.esc_html($ref['resource_key']).'</code> / '.esc_html(strtoupper($row->target_lang)).'</p>';
                    if(!$active && !empty($decision['relation']['critical'])) echo '<p class="gml-critical-warning">'.esc_html__('Critical content: keeping source may publish untranslated SEO, product, payment or safety information. Verify this exact source and page before confirming.','gml-translate').'</p><label><input type="checkbox" name="critical_confirmation" value="KEEP SOURCE" required> '.esc_html__('I explicitly approve this critical item in its original language on this page.','gml-translate').'</label><br>';
                    if(!$active) echo '<p>'.esc_html__('This resolves the item for this page; it does not count as translated. A changed source snapshot requires a new decision.','gml-translate').'</p>';
                    echo '<button class="button">'.esc_html($active?__('Revoke Keep source','gml-translate'):__('Keep source text','gml-translate')).'</button></form>';
                }
                if($resolved) {
                    echo '<p>'.esc_html__('Revoke the page decision before requesting a replacement translation for that page.','gml-translate').'</p></td></tr>';
                    continue;
                }
                $this->form_start('save',$fields);
                if(!empty($snapshot['tm'])) echo '<details><summary>'.esc_html__('Saved translation','gml-translate').' ('.esc_html($snapshot['tm']['status']).')</summary><pre style="white-space:pre-wrap;overflow-wrap:anywhere;">'.esc_html($snapshot['tm']['translated_text']).'</pre></details>';
                echo '<textarea name="translation" class="large-text" rows="4" aria-label="'.esc_attr__('Translation','gml-translate').'">'.esc_textarea($snapshot['tm']['translated_text']??'').'</textarea>';
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
        if(!$rows) echo '<p>'.esc_html__('No matching current items. Historical failures are retained in History.','gml-translate').'</p>';
        $this->pagination($page,(int)ceil($total/20));
    }
    private function pagination($page,$pages) {
        echo '<p>'.esc_html($page.' / '.max(1,$pages)).' ';
        if($page>1) echo '<a class="button" href="'.esc_url(add_query_arg('workflow_page',$page-1)).'">'.esc_html__('Previous','gml-translate').'</a> ';
        if($page<$pages) echo '<a class="button" href="'.esc_url(add_query_arg('workflow_page',$page+1)).'">'.esc_html__('Next','gml-translate').'</a>';
        echo '</p>';
    }
    public static function reason_label($reason) {
        $labels=['ready'=>__('SEO ready','gml-translate'),'eligible'=>__('SEO ready','gml-translate'),'eligible_partial'=>__('SEO ready under the page policy','gml-translate'),
            'critical_missing'=>__('Critical items still need a translation or an explicit source decision.','gml-translate'),
            'below_page_threshold'=>__('Resolved count or length coverage is below the page threshold.','gml-translate'),
            'quality_hold'=>__('A quality hold must be reviewed before publication.','gml-translate'),'rejected'=>__('The current page review is rejected.','gml-translate'),
            'stale'=>__('The source or translation changed; readiness is being refreshed.','gml-translate'),'resource_noindex'=>__('The source page is noindex or excluded.','gml-translate'),
            'unknown'=>__('The current page has not been measured.','gml-translate')];
        return $labels[$reason]??$reason;
    }
}
