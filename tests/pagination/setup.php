<?php
if(getenv('GML_DATABASE_TESTS')!=='1')exit(2);
define('WP_INSTALLING',true);
require getenv('GML_TEST_WP_ROOT').'/wp-load.php';
if(DB_NAME!=='gml_regression_pagination_20261124' || $wpdb->prefix!=='test_')exit(2);
require_once ABSPATH.'wp-admin/includes/upgrade.php';
require_once ABSPATH.'wp-admin/includes/plugin.php';
add_filter('pre_wp_mail','__return_false',PHP_INT_MAX);
add_filter('pre_http_request',static function(){return new WP_Error('test_network_blocked');},PHP_INT_MAX);
if(!is_blog_installed())wp_install('Pagination fixture','pagination-admin','admin@example.test',true,'',wp_generate_password(36));
$mu=ABSPATH.'wp-content/mu-plugins';if(!is_dir($mu))mkdir($mu);
copy(__DIR__.'/guard.php',$mu.'/pagination-guard.php');
$theme=ABSPATH.'wp-content/themes/pagination-fixture';if(!is_dir($theme))mkdir($theme);
file_put_contents($theme.'/style.css',"/*\nTheme Name: Pagination fixture\nVersion: 1.0\n*/\nbody{font-family:system-ui;max-width:1000px;margin:32px auto;padding:0 20px}header{margin-bottom:30px}article{padding:12px;border:1px solid #ddd}\n");
copy(__DIR__.'/theme.php',$theme.'/index.php');
foreach(['woocommerce/woocommerce.php','wp-seopress/seopress.php','gml-translate/gml-translate.php'] as $plugin){
 $r=activate_plugin($plugin);if(is_wp_error($r))throw new RuntimeException($r->get_error_message());
}
switch_theme('pagination-fixture');
update_option('blog_public',1);update_option('permalink_structure','/%postname%/');
update_option('posts_per_page',2);update_option('show_on_front','posts');
update_option('seopress_toggle_options',['titles'=>'1','social'=>'0','xml_sitemap'=>'0']);
update_option('seopress_titles_option_name',['seopress_titles_paged_noindex'=>'1','seopress_titles_paged_rel'=>'1']);
update_option('gml_source_lang','en');
update_option('gml_languages',array_map(static function($l){return ['code'=>$l,'enabled'=>true,'paused'=>false];},['es','de','ru']));
update_option('gml_multilingual_enabled',true);update_option('gml_ai_translation_enabled',false);update_option('gml_translation_paused',true);
update_option('gml_switcher_is_dropdown',false);update_option('gml_switcher_show_flags',false);update_option('gml_switcher_show_names',false);
GML_Translate::get_instance()->init_components();GML_Installer::activate();
GML_Translation_Rewrite::register();
register_post_type('fixture_item',['public'=>true,'has_archive'=>'industry','rewrite'=>['slug'=>'industry'],'supports'=>['title','editor']]);
register_taxonomy('fixture_sector','fixture_item',['public'=>true,'rewrite'=>['slug'=>'sector']]);
$cats=[];foreach(['equipment','components'] as $slug){$v=wp_insert_term(ucfirst($slug),'product_cat',['slug'=>$slug]);$cats[$slug]=is_wp_error($v)?(int)$v->get_error_data('term_exists'):$v['term_id'];}
$sector=wp_insert_term('Industrial','fixture_sector',['slug'=>'industrial']);$sector=is_wp_error($sector)?(int)$sector->get_error_data('term_exists'):$sector['term_id'];
$ids=[];
for($i=1;$i<=7;$i++){
 $p=new WC_Product_Simple();$p->set_name('Synthetic equipment '.$i);$p->set_status('publish');$p->set_regular_price('123.45');$p->set_description('Synthetic specification number '.$i.'.');$p->set_category_ids(array_values($cats));$ids[]=$p->save();
 $id=wp_insert_post(['post_type'=>'fixture_item','post_status'=>'publish','post_title'=>'Synthetic industry '.$i,'post_content'=>'Synthetic industrial detail '.$i.'.']);
 wp_set_object_terms($id,[$sector],'fixture_sector');
}
$mucontent=file_get_contents($mu.'/pagination-guard.php');
$mucontent.="\nadd_action('init',static function(){register_post_type('fixture_item',['public'=>true,'has_archive'=>'industry','rewrite'=>['slug'=>'industry'],'supports'=>['title','editor']]);register_taxonomy('fixture_sector','fixture_item',['public'=>true,'rewrite'=>['slug'=>'sector']]);},1);\n";
file_put_contents($mu.'/pagination-guard.php',$mucontent);
flush_rewrite_rules(false);
update_option('gml_pagination_fixture',['product_cats'=>$cats,'sector'=>$sector,'product_ids'=>$ids]);
echo json_encode(['wordpress'=>get_bloginfo('version'),'woocommerce'=>WC_VERSION,'seopress'=>SEOPRESS_VERSION,'gml'=>GML_VERSION,'products'=>count($ids)])."\n";
