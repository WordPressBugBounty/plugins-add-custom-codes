<?php
/**
 * Plugin Name: Add Custom Codes - Insert Header, Footer, Custom Code Snippets
 * Description: Light-weight plugin to add Custom CSS, Javascript, Google Analytics, Search console verification tags and other custom code snippets to your Wordpress website. Go to <em>Appearance -> Add Custom Codes</em> after installing the plugin.
 * Version: 4.7
 * Author: Saifudheen Mak
 * Author URI: https://maktalseo.com
 * License: GPL2
 * Text Domain: add-custom-codes
 */
 
// If this file was called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

 add_action( 'init', 'accodes_load_textdomain' );
function accodes_load_textdomain() {
  load_plugin_textdomain( 'add-custom-codes', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
}

/*----------------
plugin links 'Plugins' page
------------------*/
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'accodes_action_links' );
 
function accodes_action_links ( $actions ) {
   $mylinks = array(
      '<a href="' . admin_url( 'themes.php?page=add-custom-codes' ) . '">Settings</a>',
   );
   $actions = array_merge( $actions, $mylinks );
   return $actions;
}

add_filter( 'plugin_row_meta', 'accodes_row_meta', 10, 2 );

function accodes_row_meta( $links, $file ) {    
    if ( plugin_basename( __FILE__ ) == $file ) {
        $row_meta = array(		
          'wrt-review'    => '<a href="' . esc_url( 'https://wordpress.org/support/plugin/add-custom-codes/reviews/#new-post' ) . '" target="_blank" style="">' . esc_html__( 'Rate this plugin', 'add-custom-codes' ) . '</a>',	
			 'acc-buy-coffee'    => '<a href="' . esc_url( 'https://maktalseo.com' ) . '" target="_blank" style="color:green;">' . esc_html__( 'Hire us!', 'add-custom-codes' ) . '</a>'				
        );
        return array_merge( $links, $row_meta );
    }
    return (array) $links;
}

/*---------------------------------
styles and scripts for plugin page
-----------------------------------------*/

add_action('admin_enqueue_scripts', 'accodes_codemirror_scripts');
 
function accodes_codemirror_scripts($hook) {
	$cm_settings['codeEditor'] = wp_enqueue_code_editor(array('type' => 'htmlmixed'));
	wp_localize_script('jquery', 'cm_settings', $cm_settings);
	
	wp_enqueue_style('wp-codemirror');
	
	wp_register_style( 'accodes-css', plugins_url( 'add-custom-codes/css/style43.css' ), '', '4.193' );
		wp_enqueue_style( 'accodes-css' );
	
	wp_register_script( 'accodes-js', plugins_url( 'add-custom-codes/js/scripts.js' ),array(),'4.32', true);
	wp_enqueue_script( 'accodes-js' );
	
}

/*------------------------------
add menu link for plugin settings page
------------------------*/
add_action('admin_menu', 'accodes_show_menu');

function accodes_show_menu() {
	add_theme_page('Add Custom Codes', 'Add Custom Codes', 'administrator', 'add-custom-codes', 'accodes_settings_page');
}

function accodes_settings_page() {
	if (!current_user_can('administrator')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'add-custom-codes'));
    }

    include('global-form.php');
}

/*---------------------------
define items in settings page
-------------------------------*/

function accodes_sanitize_raw_input($input) {
    // Return raw input safely — meant for HTML, CSS, JS
    //return is_string($input) ? wp_kses_post($input) : '';
    return $input;
}

// Register individual settings with static callbacks
add_action('admin_init', 'accodes_settings');

function accodes_settings() {
    // Custom CSS field
    register_setting(
        'accodes-settings-group',
        'custom-css-codes-input',
        [
            'type' => 'string',
            'sanitize_callback' => 'accodes_sanitize_raw_input',
            'default' => ''
        ]
    );

    // Custom footer codes field
    register_setting(
        'accodes-settings-group',
        'custom-footer-codes-input',
        [
            'type' => 'string',
            'sanitize_callback' => 'accodes_sanitize_raw_input',
            'default' => ''
        ]
    );

    // Custom header codes field
    register_setting(
        'accodes-settings-group',
        'custom-header-codes-input',
        [
            'type' => 'string',
            'sanitize_callback' => 'accodes_sanitize_raw_input',
            'default' => ''
        ]
    );

    // Boolean option for global CSS in footer
    register_setting(
        'accodes-settings-group',
        'accodes_global_css_on_footer',
        [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ]
    );
}


/*----------------
 functions
---------------*/
function get_current_page_id()
{
	global $post;
	$current_page_id = false;
	if ( ! isset( $post ) ) {
		return false;
	}
	// if woocommerce product, get id
	if ( class_exists( 'WooCommerce' ) && is_shop() ) {
		$current_page_id = wc_get_page_id( 'shop' );
	} 
	else {
		// get page id of individual only
		if ( is_singular() ) {
			$current_page_id = $post->ID;
		}
	}
	return $current_page_id;
}

function get_current_taxonomy_id() {
    if (is_tax() || is_category() || is_tag()) {
        $term = get_queried_object();
        if (!empty($term) && !is_wp_error($term)) {
            return $term->term_id;
        }
    }
    return false;
}

/*---------------------------------
output Global CSS
----------------*/
function get_global_custom_css()
{
		$accodes_global_css = '';
		$options  = get_option( 'custom-css-codes-input' );
		if($options!='')
		{	
			$accodes_global_css = $options;
		} 
	return $accodes_global_css;
}


add_action( 'wp_head', 'accodes_css_output_header' );
function accodes_css_output_header() {
	//check if global css to be added before footer
	$css_on_footer = (bool) get_option('accodes_global_css_on_footer', false);
	//put css in footer not ticked
	if(!$css_on_footer)
	{
		$accodes_global_css = get_global_custom_css();
		//escape
		echo '<!-- Global CSS by Add Custom Codes --> <style type="text/css"> '.esc_html($accodes_global_css).' </style> <!-- End - Global CSS by Add Custom Codes -->';
	}
}

add_action( 'wp_footer', 'accodes_css_output_footer' );
function accodes_css_output_footer() {
	//check if global css to be added before footer
	$css_on_footer = (bool) get_option('accodes_global_css_on_footer', false);
	//put css in footer ticked
	if($css_on_footer)
	{
		$accodes_global_css = get_global_custom_css();
		//escape
		echo '<!-- Global CSS by Add Custom Codes --> <style type="text/css"> '.esc_html($accodes_global_css).' </style> <!-- End - Global CSS by Add Custom Codes -->';
	}
}
/*---------------------------------
output Global Header Codes
----------------*/
add_action( 'wp_head', 'accodes_header_output' );
function accodes_header_output() {
	
	$hide_global_header = '';
	$current_page_id = get_current_page_id();
	$current_taxonomy_id =  get_current_taxonomy_id();
	$global_header_codes ='';
	$get_single_header_codes = '';
	$single_header_codes = '';
	$get_taxonomy_header_codes = '';
	$taxonomy_header_codes = '';
	$output = '';
	
	//check if single page
	if ( $current_page_id ) {
		//value is 'on' if checked
		$hide_global_header = get_post_meta( $current_page_id, 'accodes_hide_header', true );
		//get Single Header Codes
		$get_single_header_codes = get_post_meta( $current_page_id , '_accodes_header_metabox', true );
		if($get_single_header_codes !='')
		{
			$single_header_codes = PHP_EOL.'<!-- Single header Scripts by Add Custom Codes -->'. PHP_EOL;
			$single_header_codes .= $get_single_header_codes;
			$single_header_codes .= PHP_EOL.'<!-- End of Single header Scripts by Add Custom Codes -->'. PHP_EOL;
		} 
	}
	
	//check if taxonomy page
	if ( $current_taxonomy_id ) {
		//value is 'on' if checked
		$hide_global_header = get_term_meta( $current_taxonomy_id, 'accodes_hide_header', true );
		//get Single Header Codes
		$get_taxonomy_header_codes = get_term_meta( $current_taxonomy_id , '_accodes_header_metabox', true );
		if($get_taxonomy_header_codes !='')
		{
			$taxonomy_header_codes = PHP_EOL.'<!-- Taxonomy header Scripts by Add Custom Codes -->'. PHP_EOL;
			$taxonomy_header_codes .= $get_taxonomy_header_codes;
			$taxonomy_header_codes .= PHP_EOL.'<!-- End of Taxonomy header Scripts by Add Custom Codes -->'. PHP_EOL;
		} 
	}
	
	//get global header - if not set to hide
	if($hide_global_header != 'on')
	{
		//get Global Header Codes
		$get_global_header_codes  = get_option( 'custom-header-codes-input' );
		if($get_global_header_codes!='')
		{
			$global_header_codes = ' <!-- Global Header Scripts by Add Custom Codes --> ';
			$global_header_codes .= $get_global_header_codes;
			$global_header_codes .= ' <!-- End - Global Header Scripts by Add Custom Codes --> ';
		}  
	}
	$output .= $global_header_codes;
	$output .= $single_header_codes;
	$output .= $taxonomy_header_codes;
	
	if($output !='')
	{
		//escape
		echo $output;
	}
	
}



/*---------------------------------
output Global Footer Codes
----------------*/
add_action( 'wp_footer', 'accodes_footer_output' );
function accodes_footer_output() {
	
	$hide_global_footer = '';
	$current_page_id = get_current_page_id();
	$current_taxonomy_id = get_current_taxonomy_id();
	$global_footer_codes ='';
	$single_footer_codes = '';
	$taxonomy_footer_codes = '';
	$output = '';
	
	if ( $current_page_id ) {
		//value is 'on' if checked
		$hide_global_footer = get_post_meta( $current_page_id, 'accodes_hide_footer', true );
		//get Footer Codes for Single
		$get_single_footer_codes = get_post_meta( $current_page_id , '_accodes_footer_metabox', true );
		if($get_single_footer_codes !='')
		{
			$single_footer_codes = PHP_EOL.'<!-- Single Footer Scripts by Add Custom Codes -->'. PHP_EOL;
			$single_footer_codes .= $get_single_footer_codes;
			$single_footer_codes .= PHP_EOL.'<!-- End - Single Footer Scripts by Add Custom Codes -->'. PHP_EOL;
		} 
	}
	
	//check if taxonomy page
	if ( $current_taxonomy_id ) {
		//value is 'on' if checked
		$hide_global_footer = get_term_meta( $current_taxonomy_id, 'accodes_hide_footer', true );
		//get Single Header Codes
		$get_taxonomy_footer_codes = get_term_meta( $current_taxonomy_id , '_accodes_footer_metabox', true );
		if($get_taxonomy_footer_codes !='')
		{
			$taxonomy_footer_codes = PHP_EOL.'<!-- Taxonomy footer Scripts by Add Custom Codes -->'. PHP_EOL;
			$taxonomy_footer_codes .= $get_taxonomy_footer_codes;
			$taxonomy_footer_codes .= PHP_EOL.'<!-- End of Taxonomy footer Scripts by Add Custom Codes -->'. PHP_EOL;
		} 
	}
	
	//get global footer - if not set to hide
	if($hide_global_footer != 'on')
	{
		//get Global Footer codes
		$get_global_footer_codes  = get_option( 'custom-footer-codes-input' );
		if($get_global_footer_codes!='')
		{
			$global_footer_codes = PHP_EOL.'<!-- Global Footer Scripts by Add Custom Codes -->'. PHP_EOL;
			$global_footer_codes .= $get_global_footer_codes;
			$global_footer_codes .= PHP_EOL.'<!-- End - Global Footer Scripts by Add Custom Codes -->'. PHP_EOL;
		}  
	}
	$output .= $global_footer_codes;
	$output .= $single_footer_codes;
	$output .= $taxonomy_footer_codes;
	
	if($output !='')
	{
		//escape
		echo $output;
	}
	
}



/*----------------
 * Individual pages
 * -----------------------*/

/**-----------------------------
 * Create the meta boxes for Single post, page, product any other custom post type
 ------------------------------------*/
function _accodes_create_metabox_single() {
	
	$post_types = get_post_types( '', 'names' );
	$post_types = array_merge( $post_types, array( 'post', 'page' ) );

	foreach ( $post_types as $post_type ) {
		add_meta_box(
					'_accodes_metabox', 
					 'Add Custom Codes by Mak',
					 '_accodes_render_metabox', 
					 $post_type, 
					'normal', 
					 'default'
					);
	}

}
add_action( 'add_meta_boxes', '_accodes_create_metabox_single' );




/*-------------------------------
 * Display Meta Boxes for Single
 * ----------------------------*/
function _accodes_render_metabox() {
	// Variables
	global $post; // Get the current post data
	$header_script = get_post_meta( $post->ID, '_accodes_header_metabox', true ); // Get the saved values
	$footer_script = get_post_meta( $post->ID, '_accodes_footer_metabox', true ); // Get the saved values
	
	$hide_header  = get_post_meta( $post->ID, 'accodes_hide_header', true );
	$hide_footer  = get_post_meta( $post->ID, 'accodes_hide_footer', true );
	
	include('single-meta.php');	
	
}


/*-------------------------
 * update data on post save - Single
 ---------------------------*/
function _accodes_save_metabox_single( $post_id, $post ) {


	// Verify nonce for security
    if ( !isset($_POST['accodes_meta_nonce']) || !wp_verify_nonce($_POST['accodes_meta_nonce'], 'accodes_save_meta') ) {
        return $post_id;
    }
	
	// Verify user has permission to edit post
	if ( !current_user_can( 'edit_post', $post->ID )) {
		return $post->ID;
	}
	
	// Check if we're in the WordPress REST API request (used by Gutenberg)
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return $post_id;
    }

    // Check if it's an autosave or a revision. If so, return early.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

	
	$allowed_html = [
            'script' => ['type' => [], 'src' => [], 'async' => [], 'defer' => []],
            'style' => ['type' => []],
            'meta' => ['name' => [], 'content' => [], 'charset' => []],
            'link' => ['rel' => [], 'href' => [], 'type' => []],
            'div' => ['class' => [], 'id' => [], 'style' => []],
            'span' => ['class' => [], 'style' => []],
            'p' => ['class' => [], 'style' => []],
            'a' => ['href' => [], 'title' => [], 'target' => []],
            'img' => ['src' => [], 'alt' => [], 'width' => [], 'height' => []],
            'iframe' => ['src' => [], 'width' => [], 'height' => [], 'frameborder' => [], 'allowfullscreen' => []],
        ];
	
	
	$accodes_header_metabox = isset($_POST['_accodes_header_metabox']) 
    ? wp_kses(wp_unslash($_POST['_accodes_header_metabox']), $allowed_html) 
    : '';

$accodes_footer_metabox = isset($_POST['_accodes_footer_metabox']) 
    ? wp_kses(wp_unslash($_POST['_accodes_footer_metabox']), $allowed_html) 
    : '';
	
	// Get values of checkbox
	$hide_header   = isset( $_POST['accodes_hide_header'] ) ? sanitize_text_field(wp_unslash($_POST['accodes_hide_header']) ): '';
	$hide_footer   = isset( $_POST['accodes_hide_footer'] ) ? sanitize_text_field(wp_unslash($_POST['accodes_hide_footer']) ): '';
	
	//Update values of meta boxes
	update_post_meta( $post->ID, '_accodes_header_metabox', $accodes_header_metabox );
	update_post_meta( $post->ID, '_accodes_footer_metabox', $accodes_footer_metabox );
	
	//Update values of check boxes
	update_post_meta( $post->ID, 'accodes_hide_header', $hide_header );
	update_post_meta( $post->ID, 'accodes_hide_footer', $hide_footer );

}
add_action( 'save_post', '_accodes_save_metabox_single', 1, 2 );


/*
 * single meta end
 * -----------------------------------------------*/

function get_current_term_id() {
    if (isset($_GET['tag_ID'])) {
        return intval($_GET['tag_ID']);
    }
    return 0;
}

// Register meta box
function accodes_render_taxonomy_meta_box() {
   $taxonomies = get_taxonomies(); 
    foreach ($taxonomies as $taxonomy) {
        add_action("{$taxonomy}_edit_form", function($tag) use ($taxonomy) {      
			$term_id = get_current_term_id();	
			$header_script = get_term_meta( $term_id, '_accodes_header_metabox', true ); 
			$footer_script = get_term_meta( $term_id, '_accodes_footer_metabox', true ); 
			$hide_header  = get_term_meta( $term_id, 'accodes_hide_header', true );
			$hide_footer  = get_term_meta( $term_id, 'accodes_hide_footer', true );		
			include('taxonomy-meta.php');
     	});
    }
	
}
add_action('admin_init', 'accodes_render_taxonomy_meta_box');

// Save meta box data of taxonomies
function accodes_save_taxonomy_meta_data($term_id) {
  	
	 // Verify nonce for security
    if (!isset($_POST['accodes_tax_meta_nonce']) || !wp_verify_nonce($_POST['accodes_tax_meta_nonce'], 'accodes_save_tax_meta')) {
        return $term_id;
    }
	
	$allowed_html = [
            'script' => ['type' => [], 'src' => [], 'async' => [], 'defer' => []],
            'style' => ['type' => []],
            'meta' => ['name' => [], 'content' => [], 'charset' => []],
            'link' => ['rel' => [], 'href' => [], 'type' => []],
            'div' => ['class' => [], 'id' => [], 'style' => []],
            'span' => ['class' => [], 'style' => []],
            'p' => ['class' => [], 'style' => []],
            'a' => ['href' => [], 'title' => [], 'target' => []],
            'img' => ['src' => [], 'alt' => [], 'width' => [], 'height' => []],
            'iframe' => ['src' => [], 'width' => [], 'height' => [], 'frameborder' => [], 'allowfullscreen' => []],
        ];
	
	
	//get value of meta boxes
	$accodes_header_metabox = isset($_POST['_accodes_header_metabox']) 
    ? wp_kses(wp_unslash($_POST['_accodes_header_metabox']), $allowed_html) 
    : '';

$accodes_footer_metabox = isset($_POST['_accodes_footer_metabox']) 
    ? wp_kses(wp_unslash($_POST['_accodes_footer_metabox']), $allowed_html) 
    : '';
	
	// Get values of checkbox
	$hide_header   = isset( $_POST['accodes_hide_header'] ) ? sanitize_text_field(wp_unslash($_POST['accodes_hide_header'])) : '';
	$hide_footer   = isset( $_POST['accodes_hide_footer'] ) ? sanitize_text_field(wp_unslash($_POST['accodes_hide_footer']) ): '';
	
	//Update values of meta boxes
	update_term_meta( $term_id, '_accodes_header_metabox', $accodes_header_metabox );
	update_term_meta( $term_id, '_accodes_footer_metabox', $accodes_footer_metabox );
	
	//Update values of check boxes
	update_term_meta( $term_id, 'accodes_hide_header', $hide_header );
	update_term_meta( $term_id, 'accodes_hide_footer', $hide_footer );
}
add_action('edited_term', 'accodes_save_taxonomy_meta_data');
add_action('create_term', 'accodes_save_taxonomy_meta_data');



?>