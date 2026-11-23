<?php
/** GET-only router on an internal Docker network. No plugin activation or forms. */
if(getenv('GML_DATABASE_TESTS')!=='1'){http_response_code(404);exit;}
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(preg_match('#^/(wp-admin/css|wp-includes/css|wp-includes/fonts)/[a-z0-9_-]+\.(?:css|woff2?|ttf|eot)$#i',$path) && is_file(getenv('GML_TEST_WP_ROOT').$path))return false;
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);exit;}
require dirname(__DIR__).'/fixtures/state-clarity.php';
$f=get_option('gml_state_fixture');
if(!$f){http_response_code(503);exit;}
if($path==='/__state_admin'){
    wp_set_current_user(($_GET['anonymous']??'')==='1'?0:1);
    if(($_GET['locale']??'')==='zh_CN'){
        unload_textdomain('gml-translate');
        load_textdomain('gml-translate',GML_PLUGIN_DIR.'languages/gml-translate-zh_CN.mo','zh_CN');
    }
    ?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/wp-admin/css/common.min.css"><link rel="stylesheet" href="/wp-admin/css/forms.min.css"><link rel="stylesheet" href="/wp-admin/css/list-tables.min.css"><link rel="stylesheet" href="/wp-includes/css/buttons.min.css">
    <style>body{margin:0;padding:20px;background:#f0f0f1}<?php echo file_get_contents(GML_PLUGIN_DIR.'assets/css/page-workflow.css');?></style>
    </head><body data-gml-main-resource="<?php echo esc_attr($f['key']);?>"><?php (new GML_Page_Workflow_Admin())->render('pages');?></body></html><?php
    exit;
}
$language=preg_match('#^/(de|es|fr|ru)/#',$path,$match)?$match[1]:'en';
wp_set_current_user(0);
$GLOBALS['wp_query']=new WP_Query(['page_id'=>$f['id']]);$GLOBALS['post']=get_post($f['id']);
(new GML_Publication_Gate())->enforce();
header('Content-Type: text/html; charset=UTF-8');
echo gml_state_front_html($f['id'],$language);
