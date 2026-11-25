<?php
?><!doctype html><html <?php language_attributes();?>><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head();?></head><body><?php wp_body_open();?>
<header><?php echo do_shortcode('[gml_language_switcher]');?></header>
<?php
woocommerce_breadcrumb();
$resource=GML_Resource_Identity::current_public();global $wp_query;
$crumbs=[['@type'=>'ListItem','position'=>1,'item'=>['name'=>'Home','id'=>home_url('/').'#webpage','url'=>home_url('/')]]];
if(is_product()){$term=get_term(get_option('gml_pagination_fixture')['product_cats']['equipment'],'product_cat');$crumbs[]=['@type'=>'ListItem','position'=>2,'item'=>['name'=>$term->name,'id'=>get_term_link($term).'#webpage','url'=>get_term_link($term)]];}
$name=is_product()?get_the_title():get_queried_object()->name;
if(is_paged()){$term=get_queried_object();$crumbs[]=['@type'=>'ListItem','position'=>count($crumbs)+1,'item'=>['name'=>$term->name,'id'=>get_term_link($term).'#webpage','url'=>get_term_link($term)]];$name='Page '.get_query_var('paged');}
$url=GML_SEO_Router::get_current_source_url($resource);
$crumbs[]=['@type'=>'ListItem','position'=>count($crumbs)+1,'item'=>['name'=>$name,'id'=>$url.'#webpage','url'=>$url]];
// A contract fixture for SEOPress's modern item.id/item.url shape; not a second product feature.
$schema=['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>$crumbs];
$sentinel='{"@type":"Product","name":"Synthetic equipment & test","url":"https://example.test/untouched/","price":123.4500,"big":123456789012345678901234567890,"empty":{},"arr":[]}';
echo '<script id="contract-jsonld" type="application/ld+json">{"@context":"https://schema.org","@graph":['.$sentinel.','.wp_json_encode($schema).']}</script>';
?>
<main data-paged="<?php echo (int)get_query_var('paged',1);?>" data-max="<?php echo (int)$wp_query->max_num_pages;?>" data-resource="<?php echo esc_attr($resource?$resource->get_key():'');?>">
<h1><?php echo esc_html(is_product()?get_the_title():get_queried_object()->name);?></h1>
<?php while(have_posts()):the_post();?><article data-item-id="<?php echo (int)get_the_ID();?>"><h2><?php the_title();?></h2><?php the_content();?></article><?php endwhile;?>
</main><footer><?php wp_footer();?></footer></body></html>
