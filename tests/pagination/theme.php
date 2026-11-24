<?php
?><!doctype html><html <?php language_attributes();?>><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head();?></head><body><?php wp_body_open();?>
<header><?php echo do_shortcode('[gml_language_switcher]');?></header>
<?php $resource=GML_Resource_Identity::current_public();global $wp_query;?>
<main data-paged="<?php echo (int)get_query_var('paged',1);?>" data-max="<?php echo (int)$wp_query->max_num_pages;?>" data-resource="<?php echo esc_attr($resource?$resource->get_key():'');?>">
<?php if(is_404()):?><h1>Fixture not found</h1><?php else:?>
<h1>Pagination fixture</h1>
<?php while(have_posts()):the_post();?><article data-item-id="<?php echo (int)get_the_ID();?>"><h2><?php the_title();?></h2><?php the_content();?></article><?php endwhile;?>
<?php endif;?></main>
<footer><?php wp_footer();?></footer></body></html>
