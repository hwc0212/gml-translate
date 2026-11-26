<?php
/** Loaded only by the disposable native pagination lab, never shipped. */
if(getenv('GML_DATABASE_TESTS')!=='1' || DB_NAME!=='gml_regression_pagination_20261124')exit;
add_filter('pre_http_request',static function(){return new WP_Error('pagination_lab_external_http_blocked');},PHP_INT_MAX);
add_filter('pre_wp_mail',static function(){return false;},PHP_INT_MAX);
add_filter('action_scheduler_allow_async_request_runner','__return_false',PHP_INT_MAX);
add_action('pre_get_posts',static function($q){
 if(!$q->is_main_query() || is_admin())return;
 if($q->is_archive() || $q->is_home()){
  $q->set('posts_per_page',2);$q->set('orderby','ID');$q->set('order','ASC');
 }
},99);
add_filter('loop_shop_per_page',static function(){return 2;},99);


add_action('after_setup_theme',static function(){add_theme_support('woocommerce');});
add_filter('template_include',static function($template){return ABSPATH.'wp-content/themes/pagination-fixture/index.php';},PHP_INT_MAX);
add_action('init',static function(){global $wp_rewrite;$wp_rewrite->pagination_base=get_option('pagination_lab_pagination_base','page');register_post_type('fixture_item',['public'=>true,'has_archive'=>'industry','rewrite'=>['slug'=>'industry'],'supports'=>['title','editor']]);register_taxonomy('fixture_sector','fixture_item',['public'=>true,'rewrite'=>['slug'=>'sector']]);},1);
