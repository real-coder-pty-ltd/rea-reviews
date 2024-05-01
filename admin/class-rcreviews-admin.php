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
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// Register custom post types
		add_action( 'init', array( $this, 'register_custom_post_types' ) );

		// Register custom taxonomies
		add_action( 'init', array( $this, 'register_custom_taxonomies' ) );

		// Register meta boxes
		add_action( 'init', array( $this, 'register_meta_boxes' ) );

		// Add the admin menu
		add_action( 'admin_menu', array( $this, 'display_plugin_admin_menu' ), 9 );

		// Register and build settings fields
		add_action( 'admin_init', array( $this, 'register_and_build_fields' ) );

		// Register default values for settings field
		add_action( 'admin_init', array( $this, 'register_default_values_for_settings_field' ) );
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
			'supports'            => array( 'title', 'editor', 'page-attributes' ),
			'taxonomies'          => array( 'rcreviews_suburb', 'rcreviews_state' ),
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
		$labels_state  = array(
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
		$args_state    = array(
			'labels'            => $labels_state,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => true,
			'rewrite'           => false,
		);
		register_taxonomy( 'rcreviews_suburb', array( 'rcreviews' ), $args_suburb );
		register_taxonomy( 'rcreviews_state', array( 'rcreviews' ), $args_state );
	}

	// Register Meta Boxes
	function register_meta_boxes() {

		function rcreviews_add_meta_boxes() {

			add_meta_box(
				'rcreview_reviewer_rating',
				'Review Rating',
				'rcreviews_reviewer_rating_callback',
				'rcreviews' 
			);
			add_meta_box(
				'rcreview_reviewer_role',
				'Reviewer Role',
				'rcreviews_reviewer_role_callback',
				'rcreviews' 
			);
			add_meta_box(
				'rcreview_reviewer_name',
				'Reviewer Name',
				'rcreviews_reviewer_name_callback',
				'rcreviews' 
			);
			add_meta_box(
				'rcreview_agent_id',
				'Agent ID',
				'rcreviews_agent_id_callback',
				'rcreviews' 
			);
			add_meta_box(
				'rcreview_agent_name',
				'Agent Name',
				'rcreviews_agent_name_callback',
				'rcreviews' 
			);
			add_meta_box(
				'rcreview_listing_id',
				'Listing ID',
				'rcreviews_listing_id_callback',
				'rcreviews' 
			);
			add_meta_box(
				'rcreview_unique_id',
				'Unique ID',
				'rcreviews_unique_id_callback',
				'rcreviews' 
			);
		}
		add_action('add_meta_boxes', 'rcreviews_add_meta_boxes');

		function rcreviews_reviewer_rating_callback( $post ) {
			$value = esc_html( get_post_meta( $post->ID, 'rcreview_reviewer_rating', true ) );
			echo '<input type="text" name="rcreview_reviewer_rating" id="rcreview_reviewer_rating" value="' . $value . '">';
		}
		function rcreviews_reviewer_role_callback( $post ) {
			$value = esc_html( get_post_meta( $post->ID, 'rcreview_reviewer_role', true ) );
			echo '<input type="text" name="rcreview_reviewer_role" id="rcreview_reviewer_role" value="' . $value . '">';
		}
		function rcreviews_reviewer_name_callback( $post ) {
			$value = esc_html( get_post_meta( $post->ID, 'rcreview_reviewer_name', true ) );
			echo '<input type="text" name="rcreview_reviewer_name" id="rcreview_reviewer_name" value="' . $value . '">';
		}
		function rcreviews_agent_id_callback( $post ) {
			$value = esc_html( get_post_meta( $post->ID, 'rcreview_agent_id', true ) );
			echo '<input type="text" name="rcreview_agent_id" id="rcreview_agent_id" value="' . $value . '">';
		}
		function rcreviews_agent_name_callback( $post ) {
			$value = esc_html( get_post_meta( $post->ID, 'rcreview_agent_name', true ) );
			echo '<input type="text" name="rcreview_agent_name" id="rcreview_agent_name" value="' . $value . '">';
		}
		function rcreviews_listing_id_callback( $post ) {
			$value = esc_html( get_post_meta( $post->ID, 'rcreview_listing_id', true ) );
			echo '<input type="text" name="rcreview_listing_id" id="rcreview_listing_id" value="' . $value . '">';
		}
		function rcreviews_unique_id_callback( $post ) {
			$value = esc_html( get_post_meta( $post->ID, 'rcreview_unique_id', true ) );
			echo '<input type="text" name="rcreview_unique_id" id="rcreview_unique_id" value="' . $value . '">';
		}

		function save_post_rcreviews_meta_boxes( $post_id ) {
			if ( !current_user_can( 'edit_post', $post_id )){
				return;
			}
			if ( 'rcreviews' == get_post_type() ) {
				if ( isset( $_POST['rcreview_reviewer_rating'] ) && $_POST['rcreview_reviewer_rating'] != '' ) {
					update_post_meta( $post_id, 'rcreview_reviewer_rating', $_POST['rcreview_reviewer_rating'] );
				}
				if ( isset( $_POST['rcreview_reviewer_role'] ) && $_POST['rcreview_reviewer_role'] != '' ) {
					update_post_meta( $post_id, 'rcreview_reviewer_role', $_POST['rcreview_reviewer_role'] );
				}

				if ( isset( $_POST['rcreview_reviewer_name'] ) && $_POST['rcreview_reviewer_name'] != '' ) {
					update_post_meta( $post_id, 'rcreview_reviewer_name', $_POST['rcreview_reviewer_name'] );
				}

				if ( isset( $_POST['rcreview_agent_id'] ) && $_POST['rcreview_agent_id'] != '' ) {
					update_post_meta( $post_id, 'rcreview_agent_id', $_POST['rcreview_agent_id'] );
				}

				if ( isset( $_POST['rcreview_agent_name'] ) && $_POST['rcreview_agent_name'] != '' ) {
					update_post_meta( $post_id, 'rcreview_agent_name', $_POST['rcreview_agent_name'] );
				}

				if ( isset( $_POST['rcreview_listing_id'] ) && $_POST['rcreview_listing_id'] != '' ) {
					update_post_meta( $post_id, 'rcreview_listing_id', $_POST['rcreview_listing_id'] );
				}

				if ( isset( $_POST['rcreview_unique_id'] ) && $_POST['rcreview_unique_id'] != '' ) {
					update_post_meta( $post_id, 'rcreview_unique_id', $_POST['rcreview_unique_id'] );
				}
			}
		}
		add_action( 'save_post', 'save_post_rcreviews_meta_boxes' );
	}

	public function display_plugin_admin_menu() {
		// add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
		add_menu_page( $this->plugin_name, 'RC Reviews', 'administrator', $this->plugin_name, array( $this, 'display_plugin_admin_dashboard' ), 'dashicons-star-filled', 26 );

		// add_submenu_page( '$parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
		add_submenu_page( $this->plugin_name, 'RC Reviews Settings', 'Settings', 'administrator', $this->plugin_name . '-settings', array( $this, 'display_plugin_admin_settings' ) );
	}

	public function display_plugin_admin_dashboard() {
		require_once 'partials/' . $this->plugin_name . '-admin-display.php';
	}
	public function display_plugin_admin_settings() {
		// set this var to be used in the settings-display view
		$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';

		if ( isset( $_GET['error_message'] ) ) {
			add_action( 'admin_notices', array( $this, 'rcreviews_settings_messages' ) );
			do_action( 'admin_notices', $_GET['error_message'] );
		}
		require_once 'partials/' . $this->plugin_name . '-admin-settings-display.php';
	}
	public function rcreviews_settings_messages( $error_message ) {
		switch ( $error_message ) {
			case '1':
				$message       = __( 'There was an error adding this setting. Please try again.  If this persists, shoot us an email.', 'my-text-domain' );
				$err_code      = esc_attr( 'rcreviews_example_setting' );
				$setting_field = 'rcreviews_example_setting';
				break;
		}
		$type = 'error';
		add_settings_error(
			$setting_field,
			$err_code,
			$message,
			$type
		);
	}
	public function register_and_build_fields() {
		/**
		 * First, we add_settings_section. This is necessary since all future settings must belong to one.
		 * Second, add_settings_field
		 * Third, register_setting
		 */
		add_settings_section(
			// ID used to identify this section and with which to register options
			'rcreviews_settings_section',
			// Title to be displayed on the administration page
			'Client Credentials',
			// Callback used to render the description of the section
			array( $this, 'rcreviews_settings_account' ),
			// Page on which to add this section of options
			'rcreviews_settings'
		);

		add_settings_section(
			// ID used to identify this section and with which to register options
			'rcreviews_main_settings_section',
			// Title to be displayed on the administration page
			'Import Details',
			// Callback used to render the description of the section
			array( $this, 'rcreviews_main_settings_account' ),
			// Page on which to add this section of options
			'rcreviews_main_settings'
		);

		$disabled_id     = '';
		$disabled_secret = '';
		$disabled_agent  = '';

		if ( getenv( 'REA_CLIENT_ID' ) ) {
			$disabled_id = 'disabled';
		}
		if ( getenv( 'REA_CLIENT_SECRET' ) ) {
			$disabled_secret = 'disabled';
		}
		if ( getenv( 'REA_AGENCY_ID' ) ) {
			$disabled_agent = 'disabled';
		}

		add_settings_field(
			'rcreviews_client_id',
			'Client ID',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_settings',
			'rcreviews_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'text',
				'id'               => 'rcreviews_client_id',
				'name'             => 'rcreviews_client_id',
				'required'         => 'true',
				$disabled_id       => '',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_settings',
			'rcreviews_client_id'
		);

		add_settings_field(
			'rcreviews_client_secret',
			'Client Secret',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_settings',
			'rcreviews_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'password',
				'id'               => 'rcreviews_client_secret',
				'name'             => 'rcreviews_client_secret',
				'required'         => 'true',
				$disabled_secret   => '',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_settings',
			'rcreviews_client_secret'
		);

		add_settings_field(
			'rcreviews_access_token',
			'Access Token',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_settings',
			'rcreviews_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'password',
				'id'               => 'rcreviews_access_token',
				'name'             => 'rcreviews_access_token',
				'required'         => 'true',
				'disabled'         => 'true',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_settings',
			'rcreviews_access_token'
		);

		add_settings_field(
			'rcreviews_agency_id',
			'Agent ID',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_settings',
			'rcreviews_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'text',
				'id'               => 'rcreviews_agency_id',
				'name'             => 'rcreviews_agency_id',
				'required'         => 'true',
				$disabled_agent    => '',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_settings',
			'rcreviews_agency_id'
		);

		add_settings_field(
			'rcreviews_last_import',
			'Last Import',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_main_settings',
			'rcreviews_main_settings_section',
			array(
				'type'             => 'hidden',
				'subtype'          => 'text',
				'id'               => 'rcreviews_last_import',
				'name'             => 'rcreviews_last_import',
				'required'         => 'true',
				'disabled'    => '',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_main_settings',
			'rcreviews_last_import'
		);
	}
	public function register_default_values_for_settings_field() {
		if ( getenv( 'REA_CLIENT_ID' ) ) {
			update_option( 'rcreviews_client_id', getenv( 'REA_CLIENT_ID' ) );
		}
		if ( getenv( 'REA_CLIENT_SECRET' ) ) {
			update_option( 'rcreviews_client_secret', getenv( 'REA_CLIENT_SECRET' ) );
		}
		if ( getenv( 'REA_AGENCY_ID' ) ) {
			update_option( 'rcreviews_agency_id', getenv( 'REA_AGENCY_ID' ) );
		}

		$url           = 'https://api.realestate.com.au/oauth/token';
		$client_id     = get_option( 'rcreviews_client_id' );
		$client_secret = get_option( 'rcreviews_client_secret' );
		$data          = array( 'grant_type' => 'client_credentials' );

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_USERPWD, "$client_id:$client_secret" );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $data ) );

		$output = curl_exec( $ch );

		// if ($output === FALSE) {
		// echo "cURL Error: " . curl_error($ch);
		// }

		curl_close( $ch );

		// Now you can process the output
		$response = json_decode( $output, true );

		if ( isset( $response['access_token'] ) ) {
			update_option( 'rcreviews_access_token', $response['access_token'] );
		} else {
			update_option( 'rcreviews_access_token', '' );
		}
	}

	public function rcreviews_settings_account() {
		echo '<p>Please add the correct API credentials on .env file.</p>';
	}
	public function rcreviews_settings_main_account() {
		echo '<p>Please add the agency ID on .env file.</p>';
	}
	public function rcreviews_render_settings_field( $args ) {
		if ( $args['wp_data'] == 'option' ) {
			$wp_data_value = get_option( $args['name'] );
		} elseif ( $args['wp_data'] == 'post_meta' ) {
			$wp_data_value = get_post_meta( $args['post_id'], $args['name'], true );
		}

		switch ( $args['type'] ) {
			case 'input':
				$value = ( $args['value_type'] == 'serialized' ) ? serialize( $wp_data_value ) : $wp_data_value;
				if ( $args['subtype'] != 'checkbox' ) {
					$prependStart = ( isset( $args['prepend_value'] ) ) ? '<div class="input-prepend"> <span class="add-on">' . $args['prepend_value'] . '</span>' : '';
					$prependEnd   = ( isset( $args['prepend_value'] ) ) ? '</div>' : '';
					$step         = ( isset( $args['step'] ) ) ? 'step="' . $args['step'] . '"' : '';
					$min          = ( isset( $args['min'] ) ) ? 'min="' . $args['min'] . '"' : '';
					$max          = ( isset( $args['max'] ) ) ? 'max="' . $args['max'] . '"' : '';
					if ( isset( $args['disabled'] ) ) {
						// hide the actual input bc if it was just a disabled input the info saved in the database would be wrong - bc it would pass empty values and wipe the actual information
						echo $prependStart . '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '_disabled" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '_disabled" size="40" disabled value="' . esc_attr( $value ) . '" /><input type="hidden" id="' . $args['id'] . '" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '" size="40" value="' . esc_attr( $value ) . '" />' . $prependEnd;
					} else {
						echo $prependStart . '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '" "' . $args['required'] . '" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '" size="40" value="' . esc_attr( $value ) . '" />' . $prependEnd;
					}
					/*<input required="required" '.$disabled.' type="number" step="any" id="'.$this->plugin_name.'_cost2" name="'.$this->plugin_name.'_cost2" value="' . esc_attr( $cost ) . '" size="25" /><input type="hidden" id="'.$this->plugin_name.'_cost" step="any" name="'.$this->plugin_name.'_cost" value="' . esc_attr( $cost ) . '" />*/

				} else {
					$checked = ( $value ) ? 'checked' : '';
					echo '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '" "' . $args['required'] . '" name="' . $args['name'] . '" size="40" value="1" ' . $checked . ' />';
				}
				break;
			default:
				// code...
				break;
		}
	}
}
