<?php
/** Deterministic interleaving on synthetic state-clarity data only. */
require dirname(__DIR__).'/fixtures/state-clarity.php';
echo "MARKER wordpress_bootstrap_loaded\n";
global $wpdb;
gml_db_assert(get_option('active_plugins',[])===[],'no active plugins in source-only fixture');
$f=get_option('gml_state_fixture');
gml_db_assert(is_array($f),'synthetic fixture mapping loaded');
$m=GML_Resource_Manifest_Store::get_by_key($f['key']);
$index=$wpdb->prefix.'gml_index';
$tm=$wpdb->get_row($wpdb->prepare("SELECT * FROM $index WHERE source_hash=%s AND target_lang='fr'",md5('Synthetic inquiry')),ARRAY_A);
gml_db_assert((bool)$tm,'synthetic title translation exists');
gml_db_assert(GML_Public_Eligibility::get_status($f['key'],'fr')['public_eligible'],'fixture starts eligible');
$changed=false;$race=null;$ui='';
$race=static function($sql)use(&$changed,$tm,$m){
    if(!$changed && strpos($sql,'AS current_manifest_generation')!==false){
        $changed=true;
        if(!GML_Translation_Memory::delete_by_id($tm['id']) || GML_Resource_Readiness::recalculate_resources([$m->id],['fr'])!==1)throw new RuntimeException('Synthetic TM interleaving did not complete');
    }
    return $sql;
};
try{
    wp_set_current_user(1);
    add_filter('query',$race);
    ob_start();
    (new GML_Page_Workflow_Admin())->render('pages');
    $ui=ob_get_clean();
    remove_filter('query',$race);
    $latest=GML_Public_Eligibility::get_status($f['key'],'fr');
    $dom=new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">'.$ui);
    $xp=new DOMXPath($dom);
    $row='';
    foreach($xp->query('//tr[td/code]') as $tr){
        if($tr->getElementsByTagName('code')->item(0)->textContent===$f['key']
            && strpos($tr->getElementsByTagName('td')->item(0)->textContent,'FR')!==false){$row=$tr->textContent;break;}
    }
    $result=['interleaving_ran'=>$changed,'latest_reason'=>$latest['reason'],'latest_eligible'=>$latest['public_eligible'],'ui_pending'=>strpos($row,'Current-version readiness needs evaluation.')!==false,'ui_says_ready'=>strpos($row,'SEO ready')!==false];
}finally{
    remove_filter('query',$race);
    $wpdb->replace($index,$tm);
    GML_Resource_Readiness::recalculate_resources([$m->id],['fr']);
}
echo json_encode($result,JSON_UNESCAPED_SLASHES)."\n";
gml_db_assert($result['interleaving_ran'] && $result['latest_reason']==='critical_missing' && !$result['latest_eligible'],'critical missing remains behind the original public gate');
gml_db_assert($result['ui_pending'] && !$result['ui_says_ready'],'admin cannot display old ready result after a newer TM snapshot was recalculated');
echo "OK admin snapshot race\n";
