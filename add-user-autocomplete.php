<?php
/*
Plugin Name: Add User Autocomplete
Plugin URI: http://github.com/boonebgorges/add-user-autocomplete
Description: Enables autocomplete for the Add Existing User field at Dashboard > Users > Add New
Author: Boone Gorges
Version: 1.1
Author URI: http://boonebgorges.com
*/

if ( !defined( 'ABSPATH' ) )
	return;


class Add_User_Autocomplete {
	var $base_url;

	function __construct() {
		add_action( 'admin_print_styles-user-new.php', array( $this, 'add_admin_styles' ) );
		add_action( 'admin_print_scripts-user-new.php', array( $this, 'add_admin_scripts' ) );
		add_action( 'wp_ajax_add_to_blog_find_user', array( &$this, 'autocomplete_results' ) );
		add_action( 'admin_init', array( &$this, 'catch_submit' ) );
		add_action( 'admin_notices', array( &$this, 'admin_notices' ) );
	}

	function add_admin_styles() {
		wp_enqueue_style( 'add-user-autocomplete-css', plugins_url() . '/add-user-autocomplete/css/add-user-autocomplete.css' );
	}

	function add_admin_scripts() {
		// Dequeue WP 3.4's autocomplete
		wp_dequeue_script( 'user-search' );

		wp_enqueue_script( 'jquery.autocomplete', plugins_url() . '/add-user-autocomplete/js/jquery.autocomplete/jquery.autocomplete.js', array( 'jquery' ) );
		wp_enqueue_script( 'add-user-autocomplete-js', plugins_url() . '/add-user-autocomplete/js/add-user-autocomplete.js', array( 'jquery', 'jquery.autocomplete' ) );

	}

	function autocomplete_results() {

		$return = array(
			'query' 	=> $_REQUEST['query'],
			'data' 		=> array(),
			'suggestions' 	=> array()
		);

		// Exclude current users of this blog
		$this_blog_users = new WP_User_Query( array(
			'blog_id' => get_current_blog_id()
		) );

		$tbu_ids = array();
		if ( !empty( $this_blog_users->results ) ) {
			foreach( $this_blog_users->results as $this_blog_user ) {
				$tbu_ids[] = $this_blog_user->ID;
			}
		}

		$users = new A2B_User_Query( array(
			'blog_id' => false,
			'search'  => '*' . $_REQUEST['query'] . '*',
			'exclude' => $tbu_ids
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

	function catch_submit() {
		if ( isset( $_POST['add_ids'] ) ) {
			$redirect    = 'user-new.php';
			foreach( (array)$_POST['add_ids'] as $user_id ) {
				if ( isset( $_POST[ 'noconfirmation' ] ) && is_super_admin() ) {
					add_existing_user_to_blog( array( 'user_id' => $user_id, 'role' => $_REQUEST[ 'role' ] ) );
					$users_added[] = (int)$user_id;
				} else {
					$user_details = get_userdata( $user_id );
					$newuser_key = substr( md5( $user_id ), 0, 5 );
					add_option( 'new_user_' . $newuser_key, array( 'user_id' => $user_id, 'email' => $user_details->user_email, 'role' => $_REQUEST[ 'role' ] ) );
					$message = __("Hi,\n\nYou have been invited to join '%s' at\n%s as a %s.\nPlease click the following link to confirm the invite:\n%s\n");
					wp_mail( $user_details->user_email, sprintf( __( '[%s] Joining confirmation' ), get_option( 'blogname' ) ),  sprintf($message, get_option('blogname'), site_url(), $_REQUEST[ 'role' ], site_url("/newbloguser/$newuser_key/")));
					$users_invited[] = (int)$user_id;
				}
			}

			if ( !empty( $users_added ) ) {
				$redirect = add_query_arg( 'users_added', implode( ',', $users_added ), $redirect );
			}

			if ( !empty( $users_invited ) ) {
				$redirect = add_query_arg( 'users_invited', implode( ',', $users_invited ), $redirect );
			}

			unset( $_POST );
			wp_redirect( $redirect );
		}
	}

	function admin_notices() {
		$type = '';
		if ( isset( $_GET['users_added'] ) ) {
			$user_ids = explode( ',', $_GET['users_added'] );
			$type = 'added';
		} else if ( isset( $_GET['users_invited'] ) ) {
			$user_ids = explode( ',', $_GET['users_invited'] );
			$type = 'invited';
		}

		if ( $type ) {
			$ustring = '<ul style="list-style:disc; padding-left: 20px;">';
			foreach( $user_ids as $user_id ) {
				$userdata = get_userdata( $user_id );
				$ustring .= '<li>' . sprintf( __( '%1$s (%2$s)', 'a2b' ), $userdata->display_name, $userdata->user_login ) . '</li>';
			}
			$ustring .= '</ul>';

			switch ( $type ) {
				case 'added' :
					$message = sprintf( __( 'The following users were successfully added to your site: %s', 'a2b' ), $ustring );
					break;

				case 'invited' :
					$message = sprintf( __( 'Invitation emails sent to the following users: %s A confirmation link must be clicked for them to be added to your site.', 'a2b' ), $ustring );
					break;
			}

			echo '<div id="message" class="updated"><p>' . $message . '</p></div>';
		}
	}
}

function aua_init() {
	if ( is_admin() ) {
		$add_user_autocomplete = new Add_User_Autocomplete;
	}
}
add_action( 'init', 'aua_init' );

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
