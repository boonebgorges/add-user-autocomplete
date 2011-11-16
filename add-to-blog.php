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
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		$this->base_url = add_query_arg( 'page', 'add-to-blog', admin_url( 'users.php' ) );
		
		add_action( 'wp_ajax_add_to_blog_find_user', array( &$this, 'autocomplete_results' ) );
	}
	
	function admin_menu() {
		$page = add_users_page( __( 'Add To Blog', 'a2b' ), __( 'Add To Blog', 'a2b' ), 'create_users', 'add-to-blog', array( $this, 'admin_panel_main' ) );
		add_action( "admin_print_styles-$page", array( $this, 'add_admin_styles' ) );
		add_action( "admin_print_scripts-$page", array( $this, 'add_admin_scripts' ) );

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
			'blog_id' => $_REQUEST['blog_id'],
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
	
	function admin_panel_main() {
		?>
		
		<div class="wrap">
		<form action="<?php echo $this->base_url ?>" method="post">
			
			<div id="icon-users" class="icon32">
			<br>
			</div>
			<h2><?php _e( 'Add To Blog', 'a2b' ) ?></h2>
			
			<br />
			
			<input id="adduser-email" type="text" name="email" value="" />
			<input id="adduser-blog-id" type="hidden" value="<?php echo get_current_blog_id() ?>" />
			
		
			
		</form>
		</div>
		<?php
	}
}

function add_to_blog_init() {
	if ( is_admin() ) {
		$add_to_blog = new Add_To_Blog;
	}
}
add_action( 'init', 'add_to_blog_init' );




?>