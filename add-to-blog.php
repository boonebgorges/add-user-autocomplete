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
	
		$users = new A2B_User_Query( array(
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

/**
 * Extends WP_User_Query to make search better
 */
class A2B_User_Query extends WP_User_Query {
	/**
	 * @see WP_User_Query::get_search_sql()
	 */
	function get_search_sql( $string, $cols, $wild = false ) {
		$string = esc_sql( $string );

		// Always search all columns
		$cols = array(
			'user_email',
			'user_login',
			'user_nicename',
			'user_url',
			'display_name'
		);

		// Always do 'both' for trailing_wild
		$wild = 'both';

		$searches = array();
		$leading_wild = ( 'leading' == $wild || 'both' == $wild ) ? '%' : '';
		$trailing_wild = ( 'trailing' == $wild || 'both' == $wild ) ? '%' : '';
		foreach ( $cols as $col ) {
			if ( 'ID' == $col )
				$searches[] = "$col = '$string'";
			else
				$searches[] = "$col LIKE '$leading_wild" . like_escape($string) . "$trailing_wild'";
		}

		return ' AND (' . implode(' OR ', $searches) . ')';
	}
}




?>