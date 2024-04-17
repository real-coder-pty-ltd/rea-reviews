<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://realcoder.com.au
 * @since      1.0.0
 *
 * @package    Rcreviews
 * @subpackage Rcreviews/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Rcreviews
 * @subpackage Rcreviews/admin
 * @author     Julius Genetia <julius@stafflink.com.au>
 */
class Rcreviews_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// Register custom post types
		add_action( 'init', array( $this, 'register_custom_post_types' ) );

		// Register custom taxonomies
		add_action( 'init', array( $this, 'register_custom_taxonomies' ) );

		// Add the admin menu
		add_action('admin_menu', array( $this, 'display_plugin_admin_menu' ), 9);

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Rcreviews_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Rcreviews_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/rcreviews-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Rcreviews_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Rcreviews_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/rcreviews-admin.js', array( 'jquery' ), $this->version, false );

	}

	// Register Custom Post Types
	public function register_custom_post_types() {

		$labels = array(
			'name'                  => _x( 'Reviews', 'Post Type General Name', 'text_domain' ),
			'singular_name'         => _x( 'Review', 'Post Type Singular Name', 'text_domain' ),
			'menu_name'             => __( 'Reviews', 'text_domain' ),
			'name_admin_bar'        => __( 'Reviews', 'text_domain' ),
			'archives'              => __( 'Review Archives', 'text_domain' ),
			'attributes'            => __( 'Review Attributes', 'text_domain' ),
			'parent_item_colon'     => __( 'Parent Review:', 'text_domain' ),
			'all_items'             => __( 'All Reviews', 'text_domain' ),
			'add_new_item'          => __( 'Add New Review', 'text_domain' ),
			'add_new'               => __( 'Add New', 'text_domain' ),
			'new_item'              => __( 'New Review', 'text_domain' ),
			'edit_item'             => __( 'Edit Review', 'text_domain' ),
			'update_item'           => __( 'Update Review', 'text_domain' ),
			'view_item'             => __( 'View Review', 'text_domain' ),
			'view_items'            => __( 'View Reviews', 'text_domain' ),
			'search_items'          => __( 'Search Review', 'text_domain' ),
			'not_found'             => __( 'Not found', 'text_domain' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
			'featured_image'        => __( 'Featured Image', 'text_domain' ),
			'set_featured_image'    => __( 'Set featured image', 'text_domain' ),
			'remove_featured_image' => __( 'Remove featured image', 'text_domain' ),
			'use_featured_image'    => __( 'Use as featured image', 'text_domain' ),
			'insert_into_item'      => __( 'Insert into Review', 'text_domain' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Review', 'text_domain' ),
			'items_list'            => __( 'Reviews list', 'text_domain' ),
			'items_list_navigation' => __( 'Reviews list navigation', 'text_domain' ),
			'filter_items_list'     => __( 'Filter Reviews list', 'text_domain' ),
		);
		$args   = array(
			'label'               => __( 'Review', 'text_domain' ),
			'description'         => __( 'Post Type DescriptionSync Reviews from realestate.com.au to WordPress.', 'text_domain' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'custom-fields', 'page-attributes' ),
			'taxonomies'          => array( 'suburb', 'state' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-format-quote',
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'rewrite'             => false,
			'capability_type'     => 'post',
		);
		register_post_type( 'rcreviews', $args );

	}

	// Register Custom Taxonomies
	function register_custom_taxonomies() {

		$labels_suburb = array(
			'name'                       => _x( 'Suburbs', 'Taxonomy General Name', 'text_domain' ),
			'singular_name'              => _x( 'Suburb', 'Taxonomy Singular Name', 'text_domain' ),
			'menu_name'                  => __( 'Suburbs', 'text_domain' ),
			'all_items'                  => __( 'Suburbs', 'text_domain' ),
			'parent_item'                => __( 'Parent Suburb', 'text_domain' ),
			'parent_item_colon'          => __( 'Parent Suburb:', 'text_domain' ),
			'new_item_name'              => __( 'New Suburb', 'text_domain' ),
			'add_new_item'               => __( 'Add New Suburb', 'text_domain' ),
			'edit_item'                  => __( 'Edit Suburb', 'text_domain' ),
			'update_item'                => __( 'Update Suburb', 'text_domain' ),
			'view_item'                  => __( 'View Suburb', 'text_domain' ),
			'separate_items_with_commas' => __( 'Separate suburbs with commas', 'text_domain' ),
			'add_or_remove_items'        => __( 'Add or remove suburbs', 'text_domain' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'text_domain' ),
			'popular_items'              => __( 'Popular Suburbs', 'text_domain' ),
			'search_items'               => __( 'Search Suburbs', 'text_domain' ),
			'not_found'                  => __( 'Not Found', 'text_domain' ),
			'no_terms'                   => __( 'No suburbs', 'text_domain' ),
			'items_list'                 => __( 'Suburbs list', 'text_domain' ),
			'items_list_navigation'      => __( 'Suburbs list navigation', 'text_domain' ),
		);
		$args_suburb   = array(
			'labels'            => $labels_suburb,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => true,
			'rewrite'           => false,
		);
		$labels_state = array(
			'name'                       => _x( 'States', 'Taxonomy General Name', 'text_domain' ),
			'singular_name'              => _x( 'State', 'Taxonomy Singular Name', 'text_domain' ),
			'menu_name'                  => __( 'States', 'text_domain' ),
			'all_items'                  => __( 'States', 'text_domain' ),
			'parent_item'                => __( 'Parent State', 'text_domain' ),
			'parent_item_colon'          => __( 'Parent State:', 'text_domain' ),
			'new_item_name'              => __( 'New State', 'text_domain' ),
			'add_new_item'               => __( 'Add New State', 'text_domain' ),
			'edit_item'                  => __( 'Edit State', 'text_domain' ),
			'update_item'                => __( 'Update State', 'text_domain' ),
			'view_item'                  => __( 'View State', 'text_domain' ),
			'separate_items_with_commas' => __( 'Separate states with commas', 'text_domain' ),
			'add_or_remove_items'        => __( 'Add or remove states', 'text_domain' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'text_domain' ),
			'popular_items'              => __( 'Popular States', 'text_domain' ),
			'search_items'               => __( 'Search States', 'text_domain' ),
			'not_found'                  => __( 'Not Found', 'text_domain' ),
			'no_terms'                   => __( 'No states', 'text_domain' ),
			'items_list'                 => __( 'States list', 'text_domain' ),
			'items_list_navigation'      => __( 'States list navigation', 'text_domain' ),
		);
		$args_state   = array(
			'labels'            => $labels_state,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => true,
			'rewrite'           => false,
		);
		register_taxonomy( 'suburb', array( 'rcreviews' ), $args_suburb );
		register_taxonomy( 'state', array( 'rcreviews' ), $args_state );

	}

	public function display_plugin_admin_menu() {
		add_menu_page( 'RC Reviews', 'RC Reviews', 'administrator', $this->plugin_name, array( $this, 'display_plugin_admin_dashboard' ), 'dashicons-star-filled', 26 );
	}

	public function display_plugin_admin_dashboard() {
		require_once 'partials/rcreviews-admin-display.php';
	}

}
