<?php
/** Real TM/output-buffer path, synthetic words, no provider, mail or theme. */
require dirname(__DIR__).'/fixtures/state-clarity.php';
echo "MARKER wordpress_bootstrap_loaded\n";
global $wpdb;
gml_db_assert(get_option('active_plugins',[])===[],'no active plugins in source-only fixture');
$f=get_option('gml_state_fixture');
gml_db_assert(is_array($f),'synthetic fixture mapping loaded');
$table=$wpdb->prefix.'gml_index';
$text='Synthetic specification 000.';
$translation=$wpdb->get_var($wpdb->prepare("SELECT translated_text FROM $table WHERE source_hash=%s AND target_lang='fr' AND status='manual'",md5($text)));
gml_db_assert((bool)$translation,'existing synthetic manual TM value loaded');
class GML_Audit_Exclusion_Buffer extends GML_Translation_Output_Buffer {
    public function __construct(){ $this->source_lang='en';$this->target_lang='fr'; }
}
wp_set_current_user(1);
$before=hash('sha256',serialize($wpdb->get_results("SELECT * FROM $table ORDER BY id",ARRAY_A)));
$queue_before=hash('sha256',serialize($wpdb->get_results("SELECT * FROM {$wpdb->prefix}gml_queue ORDER BY id",ARRAY_A)));
$prior=get_option('gml_exclude_selectors',[]);
try{
    update_option('gml_exclude_selectors',['.test-exclude','#skip-region']);
    foreach(['translate="no"','class="notranslate"','class="test-exclude"','id="skip-region"'] as $rule){
        $html='<!doctype html><html><head><meta charset="utf-8"></head><body><section '.$rule.'><p>'.$text.'</p><span aria-label="'.$text.'">'.$text.'</span></section><p>'.$text.'</p></body></html>';
        $out=(new GML_Audit_Exclusion_Buffer())->process_buffer($html);
        gml_db_assert(strpos($out,'<section '.$rule.'><p>'.$text.'</p><span aria-label="'.$text.'">'.$text.'</span></section>')!==false,'excluded body and attributes preserved in real output buffer: '.$rule);
        gml_db_assert(strpos($out,'</section><p>'.$translation.'</p>')!==false,'same text outside excluded region uses existing manual TM: '.$rule);
    }
}finally{
    update_option('gml_exclude_selectors',$prior);
}
gml_db_assert($before===hash('sha256',serialize($wpdb->get_results("SELECT * FROM $table ORDER BY id",ARRAY_A))),'real output checks preserve every TM field');
gml_db_assert($queue_before===hash('sha256',serialize($wpdb->get_results("SELECT * FROM {$wpdb->prefix}gml_queue ORDER BY id",ARRAY_A))),'real output checks preserve translation queue');
gml_db_assert($GLOBALS['gml_state_blocked_http']===0,'no provider or external HTTP attempted');
echo "OK parser exclusions\n";
