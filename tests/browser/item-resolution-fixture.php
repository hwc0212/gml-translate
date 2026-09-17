<?php
/** Disposable Docker-only browser fixture. Never included in a release ZIP. */
if(getenv('GML_DATABASE_TESTS')!=='1') exit;
if(($_SERVER['REQUEST_METHOD']??'')==='POST') define('DOING_AJAX',true);
require getenv('GML_TEST_WP_ROOT').'/wp-load.php';
if(strpos(DB_NAME,'gml_regression')!==0 || $wpdb->prefix!=='test_') exit;
add_filter('pre_http_request',static function(){return new WP_Error('test_offline','External requests blocked');});
require_once getenv('GML_TEST_PRODUCT_DIR').'/gml-translate.php';
GML_Translate::get_instance()->init_components();
wp_set_current_user(($_GET['anonymous']??'')==='1'?0:1);
if(($_GET['locale']??'')==='zh_CN') {
    unload_textdomain('gml-translate');
    load_textdomain('gml-translate',getenv('GML_TEST_PRODUCT_DIR').'/languages/gml-translate-zh_CN.mo');
}
$ui=new GML_Page_Workflow_Admin();
require_once ABSPATH.'wp-admin/includes/template.php';
if($_SERVER['REQUEST_METHOD']==='POST') { $ui->action(); exit; }
if(isset($_GET['setup'])) {
    GML_Installer::activate();
    update_option('gml_source_lang','en');
    update_option('gml_languages',[['code'=>'de','enabled'=>true],['code'=>'es','enabled'=>true]]);
    update_option('gml_translation_paused',true);
    update_option('gml_ai_translation_enabled',false);
    update_option('gml_multilingual_enabled',true);
    update_option('blog_public','1');
    $items=[];
    foreach(['text','seo_title'] as $context) {
        $text=($context==='text'?'Technical label 90*45*30mm ':'Payment recipient example ').wp_generate_uuid4();
        $post=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Browser '.$context,'post_content'=>$text]);
        $resource=GML_Resource_Identity::for_post($post);
        GML_Resource_Manifest_Store::save_complete($resource,[['text'=>$text,'context_type'=>$context]]);
        $manifest=GML_Resource_Manifest_Store::get_by_key($resource->get_key());
        $wpdb->insert($wpdb->prefix.'gml_queue',['source_hash'=>md5($text),'source_text'=>$text,'source_lang'=>'en','target_lang'=>'de','context_type'=>$context,'status'=>'failed','attempts'=>3,'error_message'=>'format_validation_failed','created_at'=>current_time('mysql')]);
        $items[$context]=['id'=>(int)$wpdb->insert_id,'resource'=>(int)$manifest->id,'source'=>$text];
        GML_Resource_Readiness::recalculate_resources([$manifest->id],['de']);
    }
    update_option('gml_browser_resolution_fixture',$items,false);
    wp_send_json_success($items);
}
if(isset($_GET['state'])) {
    $items=get_option('gml_browser_resolution_fixture',[]);
    foreach($items as &$item) {
        $snapshot=GML_Item_Resolution::snapshot($item['id'],$item['resource']);
        $item['decision']=$snapshot['decision'];
        $item['tm']=$snapshot['asset']['tm'];
    }
    wp_send_json_success(['items'=>$items,'paused'=>get_option('gml_translation_paused')]);
}
$_GET['page']='gml-translate'; $_GET['tab']=$_GET['tab']??'failures';
$ui->assets();
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/wp-admin/css/common.min.css"><link rel="stylesheet" href="/wp-admin/css/forms.min.css"><link rel="stylesheet" href="/wp-admin/css/list-tables.min.css"><link rel="stylesheet" href="/wp-includes/css/buttons.min.css">
<?php wp_print_styles('gml-page-workflow'); ?>
<style>body{padding:20px;background:#f0f0f1}.gml-page-workflow{overflow-x:auto}button,input,select,textarea{font:inherit}</style>
</head><body><?php $ui->render($_GET['tab']); wp_print_scripts('gml-page-workflow'); ?>
<script>gmlPageWorkflow.url=location.pathname+location.search;</script>
</body></html>
