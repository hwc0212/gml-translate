<?php
if(getenv('GML_DATABASE_TESTS')!=='1')exit(2);
require getenv('GML_TEST_WP_ROOT').'/wp-load.php';
if(DB_NAME!=='gml_regression_pagination_20261124')exit(2);
$f=get_option('gml_pagination_fixture');if(!$f)throw new RuntimeException('Fixture missing');
update_option('gml_switcher_show_names',true);
update_option('gml_ai_translation_enabled',false);update_option('gml_translation_paused',true);
update_option('gml_source_lang','en');update_option('gml_languages',array_map(static function($l){return ['code'=>$l,'enabled'=>true];},['es','de','ru']));
$u=get_user_by('login','pagination-admin');wp_set_current_user($u->ID);
kses_remove_filters();
wp_update_term($f['product_cats']['equipment'],'product_cat',['name'=>'Equipment & Parts']);
$p=wc_get_product($f['product_ids'][0]);$p->set_name('Synthetic equipment & test');
$p->set_description('<table><tr><th>Operating condition</th><td id="condition">5–8 ppm at inlet water 15°C, ambient 20°C and inlet conductivity <500 µS/cm; confirm sampling method and location</td></tr></table>');
$p->save();
if(strpos(get_post_field('post_content',$p->get_id()),'conductivity <500')===false)throw new RuntimeException('Fixture comparison was escaped before renderer');
copy(__DIR__.'/breadcrumb-theme.php',ABSPATH.'wp-content/themes/pagination-fixture/index.php');
update_option('gml_breadcrumb_fixture',true);
echo json_encode(['wordpress'=>get_bloginfo('version'),'woocommerce'=>WC_VERSION,'seopress'=>SEOPRESS_VERSION,'gml'=>GML_VERSION,'product_id'=>$p->get_id(),'product_url'=>get_permalink($p->get_id()),'raw_comparison_source'=>true])."\n";
