<?php
/*
Plugin Name: Add To Blog
Author: Boone Gorges
Version: 1.0
Network: true
*/

if ( !defined( 'ABSPATH' ) ) 
	return;


class Add_To_Blog {
	var $base_url;
	
	function __construct() {
		add_action( 'admin_print_styles', array( $this, 'add_admin_styles' ) );
		add_action( 'admin_print_scripts', array( $this, 'add_admin_scripts' ) );
		add_action( 'wp_ajax_add_to_blog_find_user', array( &$this, 'autocomplete_results' ) );
	}
	
	function admin_menu() {

	}
	
	function add_admin_styles() {
		wp_enqueue_style( 'add-to-blog-css', plugins_url() . '/add-to-blog/css/add-to-blog.css' );
	}
	
	function add_admin_scripts() {
		wp_enqueue_script( 'jquery.autocomplete', plugins_url() . '/add-to-blog/js/jquery.autocomplete/jquery.autocomplete.js', array( 'jquery' ) );
		wp_enqueue_script( 'add-to-blog-js', plugins_url() . '/add-to-blog/js/add-to-blog.js', array( 'jquery', 'jquery.autocomplete' ) );
	
	}
	
	function autocomplete_results() {
	
		$return = array(
			'query' 	=> $_REQUEST['query'],
			'data' 		=> array(),
			'suggestions' 	=> array()
		);
	
		$users = new WP_User_Query( array(
			'blog_id' => get_current_blog_id(),
			'search'  => '*' . $_REQUEST['query'] . '*'
		) );
		
		if ( !empty( $users->results ) ) {
			$suggestions = array();
			$data 	     = array();
	
			foreach ( $users->results as $user ) {
				$suggestions[] 	= $user->display_name . ' (' . $user->user_login . ')';
				$data[] 	= $user->ID;
			}
	
			$return['suggestions'] = $suggestions;
			$return['data']	       = $data;
		}
	
		echo json_encode( $return );
		die();
	}
}

function add_to_blog_init() {
	if ( is_admin() ) {
		$add_to_blog = new Add_To_Blog;
	}
}
add_action( 'init', 'add_to_blog_init' );




?>