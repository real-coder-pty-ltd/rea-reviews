<?php
/**
 * Admin Specific Class for the plugin.
 */
class rcreviews_admin {

	private $plugin_name;

	private $version;

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$actions = [
			'admin_menu' => ['display_plugin_admin_menu'],
			'admin_init' => [
				'register_and_build_fields',
				'rcreviews_ajax_handler_function',
			],
			'init'       => [
				'register_custom_post_types',
				'register_custom_taxonomies',
				'rcreviews_move_posts_from_previous_post_type',
				'rcreviews_cron_refresh',
			],
			'rcreviews_cron_hook' => ['rcreviews_cron_exec'],
			'update_option_rcreviews_sync_interval' => ['rcreviews_cron_refresh'],
		];

		foreach ( $actions as $action => $functions ) {
			foreach ( $functions as $function ) {
				add_action( $action, [$this, $function] );
			}
		}

		add_shortcode( 'rcreviews', array( $this, 'rcreviews_shortcode_function' ) );
		add_filter( 'cron_schedules', array( $this, 'rcreviews_cron_schedules' ) );
	}

	public function refresh_access_token_if_needed() {
    
		$existing_token      = get_option( 'rcreviews_access_token' );
		$existing_expires_at = (int) get_option( 'rcreviews_access_token_expires' );
	
		if ( ! empty( $existing_token ) && time() < $existing_expires_at ) {
			return;
		}
	
		$url           = 'https://api.realestate.com.au/oauth/token';
		$client_id     = get_option( 'rcreviews_client_id' );
		$client_secret = get_option( 'rcreviews_client_secret' );
	
		$args = [
			'method'  => 'POST',
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( "{$client_id}:{$client_secret}" ),
			],
			'body'    => [ 'grant_type' => 'client_credentials' ],
			'timeout' => 15,
		];
	
		$response = wp_remote_post( $url, $args );
	
		if ( is_wp_error( $response ) ) {
			update_option( 'rcreviews_access_token', '' );
			update_option( 'rcreviews_access_token_expires', 0 );
			return;
		}
	
		$json_body = wp_remote_retrieve_body( $response );
		$data      = json_decode( $json_body, true );
	
		if ( isset( $data['access_token'] ) ) {
			update_option( 'rcreviews_access_token', $data['access_token'] );
	
			$expires_in = isset( $data['expires_in'] ) ? (int) $data['expires_in'] : 3600;
			update_option( 'rcreviews_access_token_expires', time() + $expires_in );
		} else {
			update_option( 'rcreviews_access_token', '' );
			update_option( 'rcreviews_access_token_expires', 0 );
		}
	}

	public function enqueue_styles(): void
	{
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/rcreviews-admin.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts(): void
	{
		$agency_id = get_option( 'rcreviews_agency_id' );
		$minimum_star_rating = $this->build_rating_param( get_option( 'rcreviews_minimum_star_rating' ) );
		
		$url_first = 'https://api.realestate.com.au/customer-profile/v1/ratings-reviews/agencies/' . $agency_id . '?since=2010-09-06T12%3A27%3A00.1Z&order=DESC' . $minimum_star_rating;

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/rcreviews-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'rcreviews-ajax', plugin_dir_url( __FILE__ ) . '/js/rcreviews-ajax.js', array( 'jquery' ), '1.0', true );
		wp_localize_script(
			'rcreviews-ajax',
			'ajax_object',
			array(
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
				'url_first' => $url_first,
			)
		);
		
	}

	public function register_custom_post_types(): void
	{

		if ( ! post_type_exists( get_option( 'rcreviews_custom_post_type_slug' ) ) ) {
			$post_type_slug = get_option( 'rcreviews_custom_post_type_slug' ) ? : 'rcreviews';
			$labels         = array(
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
			$args           = array(
				'label'               => __( 'Review', 'text_domain' ),
				'description'         => __( 'Sync Reviews from realestate.com.au to WordPress.', 'text_domain' ),
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
			register_post_type( $post_type_slug, $args );
		}
	}

	function register_custom_taxonomies()
	{
		$post_type_slug = get_option( 'rcreviews_custom_post_type_slug' ) ? : 'rcreviews';

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

		$labels_agency_id  = array(
			'name'                       => _x( 'Agency ID', 'Taxonomy General Name', 'text_domain' ),
			'singular_name'              => _x( 'Agency ID', 'Taxonomy Singular Name', 'text_domain' ),
			'menu_name'                  => __( 'Agency IDs', 'text_domain' ),
			'all_items'                  => __( 'Agency IDs', 'text_domain' ),
			'parent_item'                => __( 'Parent Agency ID', 'text_domain' ),
			'parent_item_colon'          => __( 'Parent Agency ID:', 'text_domain' ),
			'new_item_name'              => __( 'New Agency ID', 'text_domain' ),
			'add_new_item'               => __( 'Add New Agency ID', 'text_domain' ),
			'edit_item'                  => __( 'Edit Agency ID', 'text_domain' ),
			'update_item'                => __( 'Update Agency ID', 'text_domain' ),
			'view_item'                  => __( 'View Agency ID', 'text_domain' ),
			'separate_items_with_commas' => __( 'Separate states with commas', 'text_domain' ),
			'add_or_remove_items'        => __( 'Add or remove states', 'text_domain' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'text_domain' ),
			'popular_items'              => __( 'Popular Agency IDs', 'text_domain' ),
			'search_items'               => __( 'Search Agency IDs', 'text_domain' ),
			'not_found'                  => __( 'Not Found', 'text_domain' ),
			'no_terms'                   => __( 'No states', 'text_domain' ),
			'items_list'                 => __( 'Agency IDs list', 'text_domain' ),
			'items_list_navigation'      => __( 'Agency IDs list navigation', 'text_domain' ),
		);

		$args_agency_id    = array(
			'labels'            => $labels_agency_id,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => true,
			'rewrite'           => false,
		);


		$labels_agent_id  = array(
			'name'                       => _x( 'Agent ID', 'Taxonomy General Name', 'text_domain' ),
			'singular_name'              => _x( 'Agent ID', 'Taxonomy Singular Name', 'text_domain' ),
			'menu_name'                  => __( 'Agent IDs', 'text_domain' ),
			'all_items'                  => __( 'Agent IDs', 'text_domain' ),
			'parent_item'                => __( 'Parent Agent ID', 'text_domain' ),
			'parent_item_colon'          => __( 'Parent Agent ID:', 'text_domain' ),
			'new_item_name'              => __( 'New Agent ID', 'text_domain' ),
			'add_new_item'               => __( 'Add New Agent ID', 'text_domain' ),
			'edit_item'                  => __( 'Edit Agent ID', 'text_domain' ),
			'update_item'                => __( 'Update Agent ID', 'text_domain' ),
			'view_item'                  => __( 'View Agent ID', 'text_domain' ),
			'separate_items_with_commas' => __( 'Separate states with commas', 'text_domain' ),
			'add_or_remove_items'        => __( 'Add or remove states', 'text_domain' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'text_domain' ),
			'popular_items'              => __( 'Popular Agent IDs', 'text_domain' ),
			'search_items'               => __( 'Search Agent IDs', 'text_domain' ),
			'not_found'                  => __( 'Not Found', 'text_domain' ),
			'no_terms'                   => __( 'No states', 'text_domain' ),
			'items_list'                 => __( 'Agent IDs list', 'text_domain' ),
			'items_list_navigation'      => __( 'Agent IDs list navigation', 'text_domain' ),
		);

		$args_agent_id    = array(
			'labels'            => $labels_agent_id,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => true,
			'rewrite'           => false,
		);

		$labels_agent  = array(
			'name'                       => _x( 'Agent', 'Taxonomy General Name', 'text_domain' ),
			'singular_name'              => _x( 'Agent', 'Taxonomy Singular Name', 'text_domain' ),
			'menu_name'                  => __( 'Agents', 'text_domain' ),
			'all_items'                  => __( 'Agents', 'text_domain' ),
			'parent_item'                => __( 'Parent Agent', 'text_domain' ),
			'parent_item_colon'          => __( 'Parent Agent:', 'text_domain' ),
			'new_item_name'              => __( 'New Agent', 'text_domain' ),
			'add_new_item'               => __( 'Add New Agent', 'text_domain' ),
			'edit_item'                  => __( 'Edit Agent', 'text_domain' ),
			'update_item'                => __( 'Update Agent', 'text_domain' ),
			'view_item'                  => __( 'View Agent', 'text_domain' ),
			'separate_items_with_commas' => __( 'Separate states with commas', 'text_domain' ),
			'add_or_remove_items'        => __( 'Add or remove states', 'text_domain' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'text_domain' ),
			'popular_items'              => __( 'Popular Agents', 'text_domain' ),
			'search_items'               => __( 'Search Agents', 'text_domain' ),
			'not_found'                  => __( 'Not Found', 'text_domain' ),
			'no_terms'                   => __( 'No states', 'text_domain' ),
			'items_list'                 => __( 'Agents list', 'text_domain' ),
			'items_list_navigation'      => __( 'Agents list navigation', 'text_domain' ),
		);

		$args_agent    = array(
			'labels'            => $labels_agent,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => true,
			'rewrite'           => false,
		);


		$labels_agency  = array(
			'name'                       => _x( 'Agency', 'Taxonomy General Name', 'text_domain' ),
			'singular_name'              => _x( 'Agency', 'Taxonomy Singular Name', 'text_domain' ),
			'menu_name'                  => __( 'Agencies', 'text_domain' ),
			'all_items'                  => __( 'Agencies', 'text_domain' ),
			'parent_item'                => __( 'Parent Agency', 'text_domain' ),
			'parent_item_colon'          => __( 'Parent Agency:', 'text_domain' ),
			'new_item_name'              => __( 'New Agency', 'text_domain' ),
			'add_new_item'               => __( 'Add New Agency', 'text_domain' ),
			'edit_item'                  => __( 'Edit Agency', 'text_domain' ),
			'update_item'                => __( 'Update Agency', 'text_domain' ),
			'view_item'                  => __( 'View Agency', 'text_domain' ),
			'separate_items_with_commas' => __( 'Separate states with commas', 'text_domain' ),
			'add_or_remove_items'        => __( 'Add or remove states', 'text_domain' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'text_domain' ),
			'popular_items'              => __( 'Popular Agencies', 'text_domain' ),
			'search_items'               => __( 'Search Agencies', 'text_domain' ),
			'not_found'                  => __( 'Not Found', 'text_domain' ),
			'no_terms'                   => __( 'No states', 'text_domain' ),
			'items_list'                 => __( 'Agencies list', 'text_domain' ),
			'items_list_navigation'      => __( 'Agencies list navigation', 'text_domain' ),
		);

		$args_agency    = array(
			'labels'            => $labels_agency,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => true,
			'rewrite'           => false,
		);

		// register_taxonomy( 'rcreviews_suburb', array( $post_type_slug ), $args_suburb );
		// register_taxonomy( 'rcreviews_state', array( $post_type_slug ), $args_state );
		// register_taxonomy( 'rcreviews_agency_id', array( $post_type_slug ), $args_agency_id );
		// register_taxonomy( 'rcreviews_agent_id', array( $post_type_slug ), $args_agent_id );
		register_taxonomy( 'rcreviews_agent_name', array( $post_type_slug ), $args_agent );
		register_taxonomy( 'rcreviews_agency_name', array( $post_type_slug ), $args_agency );

		// Register term meta so we can store a URL for each term and expose to REST API
        if ( function_exists( 'register_term_meta' ) ) {
            register_term_meta( 'rcreviews_agent_name',  'rcreview_agent_url',  [
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
                'description'  => 'Agent profile URL',
            ] );
            register_term_meta( 'rcreviews_agency_name', 'rcreview_agency_url', [
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
                'description'  => 'Agency profile URL',
            ] );
        }

        // Add admin form fields and save handlers for the taxonomies
        add_action( 'rcreviews_agent_name_add_form_fields',   [ $this, 'rcreviews_agent_url_add_field' ] );
        add_action( 'rcreviews_agent_name_edit_form_fields',  [ $this, 'rcreviews_agent_url_edit_field' ], 10, 2 );
        add_action( 'created_rcreviews_agent_name',          [ $this, 'rcreviews_agent_url_save' ] );
        add_action( 'edited_rcreviews_agent_name',           [ $this, 'rcreviews_agent_url_save' ] );

        add_action( 'rcreviews_agency_name_add_form_fields',  [ $this, 'rcreviews_agency_url_add_field' ] );
        add_action( 'rcreviews_agency_name_edit_form_fields', [ $this, 'rcreviews_agency_url_edit_field' ], 10, 2 );
        add_action( 'created_rcreviews_agency_name',         [ $this, 'rcreviews_agency_url_save' ] );
        add_action( 'edited_rcreviews_agency_name',          [ $this, 'rcreviews_agency_url_save' ] );
	}

	// Move posts from previous custom post type to new custom post type
	public function rcreviews_move_posts_from_previous_post_type() {
		// Define the old and new post types
		$prev_post_type    = get_option( 'rcreviews_prev_post_type_slug' );
		$current_post_type = get_option( 'rcreviews_custom_post_type_slug' ) ? : 'rcreviews';

		if ( $prev_post_type && ( $prev_post_type !== $current_post_type ) ) {
			// Get all posts of the old post type
			$args       = array(
				'post_type'      => $prev_post_type,
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'meta_query'     => array(
					array(
						'key'     => 'rcreview_unique_id',
						'value'   => '',
						'compare' => '!=',
					),
				),
			);
			$prev_posts = new WP_Query( $args );

			// Loop through the posts and change their post type
			if ( $prev_posts->have_posts() ) {
				while ( $prev_posts->have_posts() ) {
					$prev_posts->the_post();
					$post_id      = get_the_ID();
					$current_post = array(
						'ID'        => $post_id,
						'post_type' => $current_post_type,
					);
					wp_update_post( $current_post );
				}
				wp_reset_postdata();
			}
		}
	}

	public function display_plugin_admin_menu(): void
	{
		$hook_suffix = add_menu_page(
			$this->plugin_name,
			'RC Reviews',
			'administrator',
			$this->plugin_name,
			[ $this, 'display_plugin_admin_dashboard' ],
			'dashicons-star-filled',
			26
		);
	
		$sub_hook_suffix = add_submenu_page(
			$this->plugin_name,
			'RC Reviews Settings',
			'Settings',
			'administrator',
			$this->plugin_name . '-settings',
			[ $this, 'display_plugin_admin_settings' ]
		);
	
		// Ensure environment variables are synced, if you want
		add_action( 'load-' . $hook_suffix, [ $this, 'rcreviews_maybe_update_options_from_env' ] );
		add_action( 'load-' . $sub_hook_suffix, [ $this, 'rcreviews_maybe_update_options_from_env' ] );
	
		// Ensure token is fresh
		add_action( 'load-' . $hook_suffix, [ $this, 'refresh_access_token_if_needed' ] );
		add_action( 'load-' . $sub_hook_suffix, [ $this, 'refresh_access_token_if_needed' ] );
	}
	

	public function display_plugin_admin_dashboard(): void
	{
		require_once 'partials/' . $this->plugin_name . '-admin-display.php';
	}

	public function display_plugin_admin_settings(): void
	{
		$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';

		if ( isset( $_GET['error_message'] ) ) {
			add_action( 'admin_notices', array( $this, 'rcreviews_settings_messages' ) );
			do_action( 'admin_notices', $_GET['error_message'] );
		}
		require_once 'partials/' . $this->plugin_name . '-admin-settings-display.php';
	}

	public function rcreviews_settings_messages( $error_message ): void
	{
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

	public function register_and_build_fields(): void
	{
		add_settings_section(
			'rcreviews_settings_section',
			'Client Credentials',
			array( $this, 'rcreviews_settings_account' ),
			'rcreviews_settings'
		);

		add_settings_section(
			'rcreviews_main_settings_section',
			'Import Details',
			array( $this, 'rcreviews_main_settings_account' ),
			'rcreviews_main_settings'
		);

		$disabled_id     = '';
		$disabled_secret = '';
		$disabled_agent  = '';
		$disabled_type   = '';

		if ( getenv( 'REA_CLIENT_ID' ) ) {
			$disabled_id = 'disabled';
		}
		if ( getenv( 'REA_CLIENT_SECRET' ) ) {
			$disabled_secret = 'disabled';
		}
		if ( getenv( 'REA_AGENCY_ID' ) ) {
			$disabled_agent = 'disabled';
		}
		if ( getenv( 'REA_POST_TYPE_SLUG' ) ) {
			$disabled_type = 'disabled';
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
				'type'             => 'input',
				'subtype'          => 'hidden',
				'id'               => 'rcreviews_last_import',
				'name'             => 'rcreviews_last_import',
				'required'         => 'true',
				'disabled'         => '',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_main_settings',
			'rcreviews_last_import'
		);

		add_settings_field(
			'rcreviews_minimum_star_rating',
			'Minimum Star Rating',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_settings',
			'rcreviews_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'text',
				'id'               => 'rcreviews_minimum_star_rating',
				'name'             => 'rcreviews_minimum_star_rating',
				'required'         => 'true',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_settings',
			'rcreviews_minimum_star_rating'
		);

		add_settings_field(
			'rcreviews_sync_interval',
			'Sync Interval in Hours',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_settings',
			'rcreviews_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'text',
				'id'               => 'rcreviews_sync_interval',
				'name'             => 'rcreviews_sync_interval',
				'required'         => 'true',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_settings',
			'rcreviews_sync_interval'
		);

		add_settings_field(
			'rcreviews_prev_post_type_slug',
			'Previous Post Type Slug',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_main_settings',
			'rcreviews_main_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'text',
				'id'               => 'rcreviews_prev_post_type_slug',
				'name'             => 'rcreviews_prev_post_type_slug',
				'required'         => 'true',
				'disabled'         => '',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_main_settings',
			'rcreviews_prev_post_type_slug'
		);

		add_settings_field(
			'rcreviews_current_post_type_slug',
			'Current Post Type Slug',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_main_settings',
			'rcreviews_main_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'text',
				'id'               => 'rcreviews_current_post_type_slug',
				'name'             => 'rcreviews_current_post_type_slug',
				'required'         => 'true',
				'disabled'         => '',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_main_settings',
			'rcreviews_current_post_type_slug'
		);

		add_settings_field(
			'rcreviews_custom_post_type_slug',
			'Custom Post Type Slug',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_settings',
			'rcreviews_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'text',
				'id'               => 'rcreviews_custom_post_type_slug',
				'name'             => 'rcreviews_custom_post_type_slug',
				'required'         => 'true',
				$disabled_type     => '',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_settings',
			'rcreviews_custom_post_type_slug'
		);
	}

	function rcreviews_maybe_update_options_from_env() {
		static $has_synced = false;

		// Only run this sync once per request, even if it's called multiple times.
		if ( $has_synced ) {
			return;
		}
		$has_synced = true;

		$mapping = array(
			'rcreviews_client_id'          => 'REA_CLIENT_ID',
			'rcreviews_client_secret'      => 'REA_CLIENT_SECRET',
			'rcreviews_agency_id'          => 'REA_AGENCY_ID',
			'rcreviews_custom_post_type_slug' => 'REA_POST_TYPE_SLUG',
		);

		foreach ( $mapping as $wp_option_key => $env_key ) {
			$env_value = getenv( $env_key );
			if ( false !== $env_value ) {
				$current_value = get_option( $wp_option_key );
				if ( $env_value !== $current_value ) {
					// Only update if changed
					update_option( $wp_option_key, $env_value );
				}
			}
		}

		// Handle the fallback for rcreviews_current_post_type_slug
		$current_post_type = get_option( 'rcreviews_current_post_type_slug' );
		if ( '' === $current_post_type ) {
			update_option( 'rcreviews_current_post_type_slug', 'rcreviews' );
		}
		
		// If the newly-set custom slug differs from the current, record the previous
		$custom_slug = get_option( 'rcreviews_custom_post_type_slug', 'rcreviews' );
		if ( $custom_slug !== get_option( 'rcreviews_current_post_type_slug' ) ) {
			update_option( 'rcreviews_prev_post_type_slug', $current_post_type );
			update_option( 'rcreviews_current_post_type_slug', $custom_slug );
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
						echo $prependStart . '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '_disabled" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '_disabled" size="200" disabled value="' . esc_attr( $value ) . '" /><input type="hidden" id="' . $args['id'] . '" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '" size="200" value="' . esc_attr( $value ) . '" />' . $prependEnd;
					} else {
						echo $prependStart . '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '" "' . $args['required'] . '" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '" size="200" value="' . esc_attr( $value ) . '" />' . $prependEnd;
					}
					/*<input required="required" '.$disabled.' type="number" step="any" id="'.$this->plugin_name.'_cost2" name="'.$this->plugin_name.'_cost2" value="' . esc_attr( $cost ) . '" size="25" /><input type="hidden" id="'.$this->plugin_name.'_cost" step="any" name="'.$this->plugin_name.'_cost" value="' . esc_attr( $cost ) . '" />*/

				} else {
					$checked = ( $value ) ? 'checked' : '';
					echo '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '" "' . $args['required'] . '" name="' . $args['name'] . '" size="200" value="1" ' . $checked . ' />';
				}
				break;
			default:
				// code...
				break;
		}
	}

	public function rcreviews_ajax_handler_function() {
		function rcreviews_process_reviews_ajax_handler() {
			$url          = $_POST['url'];
			$item_counter = $_POST['item_counter'];
			$access_token = get_option( 'rcreviews_access_token' );
			$post_type    = get_option( 'rcreviews_custom_post_type_slug' ) ? : 'rcreviews';

			$ch = curl_init();

			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );

			$headers   = array();
			$headers[] = 'Accept: application/hal+json';
			$headers[] = 'Authorization: Bearer ' . $access_token;

			curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
			$result = curl_exec( $ch );

			if ( curl_errno( $ch ) ) {
				echo 'Error:' . curl_error( $ch );
			}

			curl_close( $ch );

			$data         = json_decode( $result, true );
			$rating       = 0;
			$role         = 'Seller';
			$name         = '';
			$created_date = '';
			$content      = '';
			$agent_id     = 0;
			$agent_name   = '';
			$agent_url     = 0;
			$agency_id     = 0;
			$agency_name   = '';
			$agency_url   = '';
			$listing_id   = 0;
			$unique_id    = 0;

			foreach ( $data['result'] as $review ) {
				if ( isset( $review['rating'] ) ) {
					$rating = $review['rating'];
				}
				if ( isset( $review['reviewer']['role'] ) ) {
					$role = ucfirst( $review['reviewer']['role'] );
				}
				if ( isset( $review['reviewer']['name'] ) ) {
					$name = ucfirst( $review['reviewer']['name'] );
				}
				if ( isset( $review['createdDate'] ) ) {
					$created_date            = $review['createdDate'];
					$created_date_as_post_id = strtotime( $review['createdDate'] );
				}
				if ( isset( $review['content'] ) ) {
					$content = $review['content'];
				}
				if ( isset( $review['agent']['profileId'] ) ) {
					$agent_id = $review['agent']['profileId'];
				}
				if ( isset( $review['agent']['name'] ) ) {
					$agent_name = $review['agent']['name'];
				}
				if ( isset( $review['agent']['_links']['self']['href'] ) ) {
					$agent_url = $review['agent']['_links']['self']['href'];
				}
				if ( isset( $review['agency']['id'] ) ) {
					$agency_id = $review['agency']['id'];
				}
				if ( isset( $review['agency']['name'] ) ) {
					$agency_name = $review['agency']['name'];
				}
				if ( isset( $review['agency']['_links']['self']['href'] ) ) {
					$agency_url = $review['agency']['_links']['self']['href'];
				}
				if ( isset( $review['listing']['id'] ) ) {
					$listing_id = $review['listing']['id'];
				}
				$unique_id = $listing_id . '-' . $agent_id . '-' . $created_date_as_post_id;

				// Insert post
				$current_post = array(
					'post_title'   => $role . ' of house',
					'post_content' => $content,
					'post_status'  => 'publish',
					'post_author'  => 1,
					'post_date'    => $created_date,
					'post_type'    => $post_type,
					'meta_input'   => array(
						'rcreview_reviewer_rating' => $rating,
						'rcreview_reviewer_role'   => $role,
						'rcreview_reviewer_name'   => $name,
						'rcreview_agent_id'        => $agent_id,
						'rcreview_agent_name'      => $agent_name,
						'rcreview_agent_url'      => $agent_url,
						'rcreview_agency_id'       => $agency_id,
						'rcreview_agency_name'     => $agency_name,
						'rcreview_agency_url'     => $agency_url,
						'rcreview_listing_id'      => $listing_id,
						'rcreview_unique_id'       => $unique_id,
					),
				);

				$args_by_unique_id = array(
					'post_type'  => $post_type,
					'meta_query' => array(
						array(
							'key'   => 'rcreview_unique_id',
							'value' => $unique_id,
						),
					),
				);

				// Insert post
				$posts = get_posts( $args_by_unique_id );

				if ( ! empty( $posts ) ) {
					$current_post['ID'] = $posts[0]->ID;
					$post_id = $posts[0]->ID;
					wp_update_post( $current_post );
				} else {
					wp_insert_post( $current_post );
					$post_id = get_posts( $args_by_unique_id )[0]->ID;
				}

				if ( $post_id && ! is_wp_error( $post_id ) && ! empty( $agent_id ) && ! empty( $agent_name ) ) {
					// Ensure agency term exists and save agency URL as term meta
					$agency_term = term_exists( (string) $agency_name, 'rcreviews_agency_name' );
					if ( $agency_term === 0 || $agency_term === null ) {
						$res = wp_insert_term(
							(string) $agency_name,
							'rcreviews_agency_name',
							[
								'slug' => $agency_id,
								'description' => '',
							]
						);
						if ( ! is_wp_error( $res ) && isset( $res['term_id'] ) ) {
							update_term_meta( (int) $res['term_id'], 'rcreview_agency_url', esc_url_raw( $agency_url ) );
						}
					} else {
						$term_id = is_array( $agency_term ) ? (int) $agency_term['term_id'] : (int) $agency_term;
						update_term_meta( $term_id, 'rcreview_agency_url', esc_url_raw( $agency_url ) );
					}

					// Ensure agent term exists and save agent URL + description
					$agent_term = term_exists( (string) $agent_name, 'rcreviews_agent_name' );
					if ( $agent_term === 0 || $agent_term === null ) {
						$res = wp_insert_term(
							(string) $agent_name,
							'rcreviews_agent_name',
							[
								'slug'        => $agent_id,
								'description' => $agency_name,
							]
						);
						if ( ! is_wp_error( $res ) && isset( $res['term_id'] ) ) {
							update_term_meta( (int) $res['term_id'], 'rcreview_agent_url', esc_url_raw( $agent_url ) );
						}
					} else {
						$term_id = is_array( $agent_term ) ? (int) $agent_term['term_id'] : (int) $agent_term;
						update_term_meta( $term_id, 'rcreview_agent_url', esc_url_raw( $agent_url ) );
					}

					// wp_set_object_terms( $post_id, (string) $agent_id, 'rcreviews_agent_id', false );
					wp_set_object_terms( $post_id, (string) $agent_name, 'rcreviews_agent_name', false );
					// wp_set_object_terms( $post_id, (string) $agency_id, 'rcreviews_agency_id', false );
					wp_set_object_terms( $post_id, (string) $agency_name, 'rcreviews_agency_name', false );
				}

				++$item_counter;
			}

			$url_next = $data['_links']['next']['href'];

			update_option( 'rcreviews_last_import', date( 'd F Y H:i:s' ) );

			$args = array(
				'post_type'      => $post_type,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => 'rcreview_unique_id',
						'value'   => '',
						'compare' => '!=',
					),
				),
			);

			$query       = new WP_Query( $args );
			$total_posts = $query->found_posts;

			$response = array(
				'url_next'     => $url_next,
				'last_import'  => get_option( 'rcreviews_last_import' ),
				'item_counter' => $item_counter,
				'total_posts'  => $total_posts,
			);

			header( 'Content-Type: application/json' );
			echo json_encode( $response );

			wp_die();
		}
		add_action( 'wp_ajax_rcreviews_process_reviews', 'rcreviews_process_reviews_ajax_handler' );
		add_action( 'wp_ajax_rcreviews_nopriv_process_reviews', 'rcreviews_process_reviews_ajax_handler' );

		function rcreviews_empty_reviews_ajax_handler() {
			$post_type = get_option( 'rcreviews_custom_post_type_slug' ) ? : 'rcreviews';

			$args = array(
				'post_type'      => $post_type,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => 'rcreview_unique_id',
						'value'   => '',
						'compare' => '!=',
					),
				),
			);

			$reviews = get_posts( $args );

			foreach ( $reviews as $review_id ) {
				wp_delete_post( $review_id, true );
			}

			$query       = new WP_Query( $args );
			$total_posts = $query->found_posts;

			$response = array(
				'total_posts' => $total_posts,
			);

			header( 'Content-Type: application/json' );
			echo json_encode( $response );

			wp_die();
		}
		add_action( 'wp_ajax_rcreviews_empty_reviews', 'rcreviews_empty_reviews_ajax_handler' );
		add_action( 'wp_ajax_nopriv_rcreviews_empty_reviews', 'rcreviews_empty_reviews_ajax_handler' );
	}

	public function rcreviews_shortcode_function( $atts ) {
		$output           = '';
		$badge            = file_get_contents( plugin_dir_path( __FILE__ ) . '../assets/images/badge.svg' );
		$class_visibility = ' shown-review';
		$post_type        = get_option( 'rcreviews_custom_post_type_slug' ) ? : 'rcreviews';

		// Set default values for the attributes
		$atts = shortcode_atts(
			array(
				'max_reviews'             => -1,
				'shown_reviews'           => 3,
				'min_stars'               => 5,
				'agency_id'               => '',
				'agency_name'             => '',
				'agent_id'                => '',
				'agent_name'              => '',
				'role'              	  => '',
				'view'                    => 'list',
				'read_more'               => '',
				'read_more_text'          => 'Read more of our reviews on',
				'listing_type'            => 'agent',
				'class_section'           => '',
				'class_container'         => 'container',
				'class_row'               => 'row',
				'class_article'           => 'col-12 mb-3',
				'class_card'              => 'bg-light rounded p-3',
				'class_inner_row'         => 'row align-items-center justify-content-between',
				'class_rating'            => 'col d-flex align-items-center',
				'class_rating_stars'      => 'd-flex align-items-center',
				'class_rating_number'     => 'ps-1',
				'class_badge'             => 'col text-end',
				'class_title'             => '',
				'class_date'              => '',
				'class_content'           => 'mt-2',
				'class_agent'             => 'mt-3 d-flex align-items-center',
				'class_agent_img-wrapper' => 'rounded-circle overflow-hidden me-1',
				'class_agent_img'         => '',
				'class_agent_name'        => '',
				'class_btn_wrapper'       => 'd-flex justify-content-center',
				'class_btn'               => 'btn btn-outline-dark fw-semibold py-3 px-4',
				'class_no_results'        => '',
				'class_read_more_wrapper' => 'd-flex align-content-center justify-content-center min-h-42 fs-3 mt-4 mb-3',
				'class_read_more_link'    => 'text-dark d-flex flex-column flex-sm-row align-items-center rg-16 text-center text-decoration-none mx-auto',
				'class_read_more_img'     => 'img-fluid ms-0 ms-md-2',
				'class_read_more_icon'    => 'text-dark bi bi-box-arrow-up-right',
			),
			$atts,
			'rcreviews'
		);

		$meta_query = array(
			'relation' => 'AND',
			array(
				'key'     => 'rcreview_reviewer_rating',
				'value'   => $atts['min_stars'],
				'compare' => '>=',
			),
		);

		if ( ! empty( $atts['role'] ) ) {
			$meta_query = array(
				'relation' => 'AND',
				array(
					'key'     => 'rcreview_reviewer_role',
					'value'   => $atts['role'],
					'compare' => '==',
				),
			);
		}

		if ( ! empty( $atts['agency_id'] ) && ! empty( $atts['agency_name'] ) ) {
			$agency_names  = explode( ',', $atts['agency_name'] );
			$agency_ids    = explode( ',', $atts['agency_id'] );
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => 'rcreview_agency_id',
					'value'   => $agency_ids,
					'compare' => 'IN',
				),
				array(
					'key'     => 'rcreview_agency_name',
					'value'   => $agency_names,
					'compare' => 'IN',
				),
			);
		} elseif ( ! empty( $atts['agency_id'] ) && empty( $atts['agency_name'] ) ) {
			$agency_ids    = explode( ',', $atts['agency_id'] );
			$meta_query[] = array(
				array(
					'key'     => 'rcreview_agency_id',
					'value'   => $agency_ids,
					'compare' => 'IN',
				),
			);
		} elseif ( ! empty( $atts['agency_name'] ) && empty( $atts['agency_id'] ) ) {
			$agency_names  = explode( ',', $atts['agency_name'] );
			$meta_query[] = array(
				array(
					'key'     => 'rcreview_agency_name',
					'value'   => $agency_names,
					'compare' => 'IN',
				),
			);
		}

		if ( ! empty( $atts['agent_id'] ) && ! empty( $atts['agent_name'] ) ) {
			$agent_names  = explode( ',', $atts['agent_name'] );
			$agent_ids    = explode( ',', $atts['agent_id'] );
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => 'rcreview_agent_id',
					'value'   => $agent_ids,
					'compare' => 'IN',
				),
				array(
					'key'     => 'rcreview_agent_name',
					'value'   => $agent_names,
					'compare' => 'IN',
				),
			);
		} elseif ( ! empty( $atts['agent_id'] ) && empty( $atts['agent_name'] ) ) {
			$agent_ids    = explode( ',', $atts['agent_id'] );
			$meta_query[] = array(
				array(
					'key'     => 'rcreview_agent_id',
					'value'   => $agent_ids,
					'compare' => 'IN',
				),
			);
		} elseif ( ! empty( $atts['agent_name'] ) && empty( $atts['agent_id'] ) ) {
			$agent_names  = explode( ',', $atts['agent_name'] );
			$meta_query[] = array(
				array(
					'key'     => 'rcreview_agent_name',
					'value'   => $agent_names,
					'compare' => 'IN',
				),
			);
		}

		$args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $atts['max_reviews'],
			'meta_query'     => $meta_query,
		);

		$query = new WP_Query( $args );

		function rcreviews_rating( $rating ) {
			$star   = file_get_contents( plugin_dir_path( __FILE__ ) . '../assets/images/star.svg' );
			$output = '';

			$rating = intval( $rating );
			for ( $i = 0; $i < $rating; $i++ ) {
				$output .= $star;
			}
			return $output;
		}
		function rcreviews_check_class( $string, $view ) {

			if ( 'unstyled' != $view ) {
				if ( '' != $string ) {
					return ' ' . $string;
				} else {
					return '';
				}
			} else {
				return '';
			}
		}

		if ( $query->have_posts() ) {


			$rea   = file_get_contents( plugin_dir_path( __FILE__ ) . '../assets/images/realestatecomau.svg' );

			$output .= '<section class="rcreviews--section' . rcreviews_check_class( $atts['class_section'], $atts['view'] ) . ' rcreviews--listing-type-' . $atts['listing_type'] . '">';
			$output .= '<div class="rcreviews--container' . rcreviews_check_class( $atts['class_container'], $atts['view'] ) . '"> ';
			$output .= '<div class="rcreviews--row' . rcreviews_check_class( $atts['class_row'], $atts['view'] ) . '">';

			while ( $query->have_posts() ) {
				$query->the_post();

				if ( $query->current_post <= ( $atts['shown_reviews'] - 1 ) ) {
					$class_visibility = ' rcreviews--shown-review';
				} else {
					$class_visibility = ' rcreviews--hidden-review d-none';
				}

				$output .= '<article class="rcreviews--article col' . $class_visibility . rcreviews_check_class( $atts['class_article'], $atts['view'] ) . '" id="rcreviews-' . get_the_ID() . '" data-agent-id="' . get_post_meta( get_the_ID(), 'rcreview_agent_id', true ) . '">';
				$output .= '<div class="rcreviews--card' . rcreviews_check_class( $atts['class_card'], $atts['view'] ) . '">';
				$output .= '<div class="rcreviews--inner-row' . rcreviews_check_class( $atts['class_inner_row'], $atts['view'] ) . '">';
				$output .= '<div class="rcreviews--rating' . rcreviews_check_class( $atts['class_rating'], $atts['view'] ) . '">';
				$output .= '<div class="rcreviews--rating-stars' . rcreviews_check_class( $atts['class_rating_stars'], $atts['view'] ) . '">' . rcreviews_rating( get_post_meta( get_the_ID(), 'rcreview_reviewer_rating', true ) ) . '</div>';
				$output .= '<div class="rcreviews-rating-number' . rcreviews_check_class( $atts['class_rating_number'], $atts['view'] ) . '">' . number_format( get_post_meta( get_the_ID(), 'rcreview_reviewer_rating', true ), 1 ) . '</div>';
				$output .= '</div>';
				$output .= '<div class="rcreviews--badge' . rcreviews_check_class( $atts['class_badge'], $atts['view'] ) . '">' . $badge . 'Verified review</div>';
				$output .= '</div>';
				$output .= '<div class="rcreviews--title' . rcreviews_check_class( $atts['class_title'], $atts['view'] ) . '"><strong>' . get_the_title() . '</strong></div>';
				$output .= '<div class="rcreviews--date' . rcreviews_check_class( $atts['class_date'], $atts['view'] ) . '"><small>' . human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) . ' ago</small></div>';
				$output .= '<div class="rcreviews--content' . rcreviews_check_class( $atts['class_content'], $atts['view'] ) . '">' . get_the_content() . '</div>';

				if ( 'agency' == $atts['listing_type'] ) {
					$agent_name = '';
					$agent_img  = '';

					$output .= '<div class="rcreviews--agent' . rcreviews_check_class( $atts['class_agent'], $atts['view'] ) . '">';

					$users = get_users(
						array(
							'role'           => 'author',
							'search'         => '*' . get_post_meta( get_the_ID(), 'rcreview_agent_name', true ) . '*',
							'search_columns' => array(
								'display_name',
							),
						)
					);

					if ( ! empty( $users ) ) {
						$user       = $users[0];
						$agent_name = get_user_meta( $user->ID, 'first_name', true ) . ' ' . get_user_meta( $user->ID, 'last_name', true );
						$agent_img  = get_field( 'static_profile_image', 'user_' . $user->ID )['sizes']['thumbnail'];

					} else {
						$agent_name = get_post_meta( get_the_ID(), 'rcreview_agent_name', true );
					}

					if ( $agent_img ) {
						$output .= '<span class="rcreviews--agent-img-wrapper' . rcreviews_check_class( $atts['class_agent_img-wrapper'], $atts['view'] ) . '">';
						$output .= '<img class="rcreviews--agent-img' . rcreviews_check_class( $atts['class_agent_img-wrapper'], $atts['view'] ) . '" src="' . $agent_img . '" width="24" width="24">';
						$output .= '</span>';
					}

					$output .= '<span class="rcreviews--agent-name' . rcreviews_check_class( $atts['class_agent_name'], $atts['view'] ) . '">' . $agent_name . '</span>';
					$output .= '</div>';
				}

				$output .= '</div>';
				$output .= '</article>';
			}

			$output .= '</div>';

			if ( ! empty( $atts['max_reviews'] ) && $atts['max_reviews'] > 0 ) {
				if ( $atts['max_reviews'] > $atts['shown_reviews'] ) {
					$output .= '<div class="rcreviews--btn-wrapper' . rcreviews_check_class( $atts['class_btn_wrapper'], $atts['view'] ) . '">';
					$output .= '<button class="rcreviews--btn' . rcreviews_check_class( $atts['class_btn'], $atts['view'] ) . '"><span class="rcreviews--label">Show</span> <span class="rcreviews--count">' . $atts['max_reviews'] - $atts['shown_reviews'] . '</span> reviews</button>';
					$output .= '</div>';
				}
			} elseif ( $query->found_posts > $atts['shown_reviews'] ) {
				$output .= '<div class="rcreviews--btn-wrapper' . rcreviews_check_class( $atts['class_btn_wrapper'], $atts['view'] ) . '">';
				$output .= '<button class="rcreviews--btn' . rcreviews_check_class( $atts['class_btn'], $atts['view'] ) . '"><span class="rcreviews--label">Show</span> <span class="rcreviews--count">' . $query->found_posts - $atts['shown_reviews'] . '</span> reviews</button>';
				$output .= '</div>';
			}
			$output .= '</div>';
			$output .= '</section>';

			$read_more_url = '';

			if ( ! empty( $atts['read_more'] ) ) {
				if ( $atts['read_more'] == 'agency' && ! empty($atts['agency_id'])  ) {
					$agency_name = $atts['agency_id'];
					$term = get_term_by( 'slug', $agency_name, 'rcreviews_agency_name' );

					if ( $term && ! is_wp_error( $term ) ) {
						$term_url = get_term_meta( $term->term_id, 'rcreview_agency_url', true );
						if ( $term_url ) {
							$read_more_url = esc_url( $term_url );
						} 
					}
				} elseif ( $atts['read_more'] == 'agent' &&  ! empty($atts['agent_name']) ) {
					$agent_name = $atts['agent_name'];
					$term = get_term_by( 'name', $agent_name, 'rcreviews_agent_name' );

					if ( $term && ! is_wp_error( $term ) ) {
						$term_url = get_term_meta( $term->term_id, 'rcreview_agent_url', true );
						if ( $term_url ) {
							$read_more_url = esc_url( $term_url );
						}
					}
				} else {
					$read_more_url = $atts['read_more'];
				}

				if ( $read_more_url ) {
					$output .= 
					'<div class="'. rcreviews_check_class( $atts['class_read_more_wrapper'], $atts['view'] ) .'">
						<a href="' . $read_more_url . '" target="_blank" class="' . $atts['class_read_more_link'] . '">
							<span>' . $atts['read_more_text'] . '</span>
							<span>
								<span class="' . $atts['class_read_more_img'] . '">' . $rea . '</span>
								<i class="' . $atts['class_read_more_icon'] . '"></i>
							</span>
						</a>
					</div>';
				}
			}

			// Restore original Post Data
			wp_reset_postdata();
		} else {
			// No posts found
			$output .= '<div class="rcreviews--no-results' . rcreviews_check_class( $atts['class_no_results'], $atts['view'] ) . '">';
			$output .= 'No reviews found.';
			$output .= '</div>';
		}

		return $output;
	}

	public function rcreviews_cron_exec(): void
	{
		$this->refresh_access_token_if_needed();

		$agency_id           = get_option( 'rcreviews_agency_id' );
		$minimum_star_rating = get_option( 'rcreviews_minimum_star_rating' );
		$rating_param = $this->build_rating_param( $minimum_star_rating );
	
		$date = new DateTime();
		$date->modify( '-30 days' );
		$since       = urlencode( $date->format( 'Y-m-d\TH:i:s\Z' ) ); 
		$base_url    = 'https://api.realestate.com.au/customer-profile/v1/ratings-reviews/agencies/';
		$url_first   = "{$base_url}{$agency_id}?since={$since}&order=DESC{$rating_param}";
		
		$this->fetch_and_process_reviews( $url_first );
	}
	
	private function build_rating_param( $minimum_star_rating ) {
		if ( empty( $minimum_star_rating ) ) {
			return '';
		}
	
		$numbers = array();
		for ( $i = $minimum_star_rating; $i <= 5; $i++ ) {
			$numbers[] = $i;
		}
		return '&ratings=' . implode( ',', $numbers );
	}
	
	private function fetch_and_process_reviews( $url ): void
	{
		$access_token = get_option( 'rcreviews_access_token' );
	
		$args = array(
			'headers' => array(
				'Accept'        => 'application/hal+json',
				'Authorization' => 'Bearer ' . $access_token,
			),
			'timeout' => 20,
		);
	
		$response = wp_remote_get( $url, $args );
	
		if ( is_wp_error( $response ) ) {
			error_log( 'rcreviews_cron_exec WP Error: ' . $response->get_error_message() );
			return;
		}
	
		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code >= 400 ) {
			error_log( "rcreviews_cron_exec HTTP Error: Received status code {$status_code}" );
			return;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			error_log( 'rcreviews_cron_exec: Empty response body.' );
			return;
		}
	
		$data = json_decode( $body, true );
		if ( ! isset( $data['result'] ) || ! is_array( $data['result'] ) ) {
			error_log( 'rcreviews_cron_exec: Invalid or missing "result" data.' );
			return;
		}
	
		foreach ( $data['result'] as $review ) {
			$this->upsert_review_post( $review );
		}
	
		update_option( 'rcreviews_last_import', date( 'd F Y H:i:s' ) );
	
		if ( ! empty( $data['_links']['next']['href'] ) ) {
			$this->fetch_and_process_reviews( $data['_links']['next']['href'] );
		}
	}
	
	
	/**
	 * Inserts or updates a single “review” post based on unique_id.
	 */
	private function upsert_review_post( array $review ) {
		$post_type = get_option( 'rcreviews_custom_post_type_slug' ) ?: 'rcreviews';
	
		// Extract fields, fallback to sensible defaults
		$rating        = $review['rating']                     ?? 0;
		$role          = isset( $review['reviewer']['role'] ) 
							? ucfirst( $review['reviewer']['role'] ) 
							: 'Seller';
		$name          = isset( $review['reviewer']['name'] ) 
							? ucfirst( $review['reviewer']['name'] ) 
							: '';
		$created_date  = $review['createdDate'] ?? '';
		$content       = $review['content'] ?? '';
		$agent_id      = $review['agent']['profileId'] ?? 0;
		$agent_name    = $review['agent']['name'] ?? '';
		$agent_url    = $review['agent']['_links']['self']['href'] ?? '';
		$agency_id     = $review['agency']['id'] ?? 0;
		$agency_name   = $review['agency']['name'] ?? 0;
		$agency_url   = $review['agency']['_links']['self']['href'] ?? 0;
		$listing_id    = $review['listing']['id'] ?? 0;
	
		// A unique ID to detect if we already have this review
		$created_ts = $created_date ? strtotime( $created_date ) : '';
		$unique_id  = "{$listing_id}-{$agent_id}-{$created_ts}";
	
		$current_post = array(
			'post_title'   => $role . ' of house',
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_author'  => 1,  // or use a filter if you prefer
			'post_date'    => $created_date,
			'post_type'    => $post_type,
			'meta_input'   => array(
				'rcreview_reviewer_rating' => $rating,
				'rcreview_reviewer_role'   => $role,
				'rcreview_reviewer_name'   => $name,
				'rcreview_agent_id'        => $agent_id,
				'rcreview_agent_name'      => $agent_name,
				'rcreview_agent_url'      => $agent_url,
				'rcreview_agency_id'      => $agency_id,
				'rcreview_agency_name'      => $agency_name,
				'rcreview_agency_url'      => $agency_url,
				'rcreview_listing_id'      => $listing_id,
				'rcreview_unique_id'       => $unique_id,
			),
		);
	
		// Search for existing post by this unique meta key
		$existing = get_posts( array(
			'post_type'  => $post_type,
			'meta_query' => array(
				array(
					'key'   => 'rcreview_unique_id',
					'value' => $unique_id,
				),
			),
		) );
	
		if ( ! empty( $existing ) ) {
			$current_post['ID'] = $existing[0]->ID;
			wp_update_post( $current_post );
		} else {
			wp_insert_post( $current_post );
		}
	}

	public function rcreviews_cron_schedules( $schedules ): array
	{
		$hour = get_option( 'rcreviews_sync_interval' ) ? : 24;
		$interval = $hour * 60 * 60;

		$schedules['rcreviews_interval'] = [
			'interval' => $interval,
			'display'  => esc_html__( 'Every ' . $hour . ' Hour(s)' ),
		];
		return $schedules;
	}

	public function rcreviews_cron_refresh(): void
	{
		$timestamp = wp_next_scheduled( 'rcreviews_cron_hook' );
		
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'rcreviews_cron_hook' );
		}
		
		wp_schedule_event( time(), 'rcreviews_interval', 'rcreviews_cron_hook' );
	}

    /**
     * Render "Agent URL" field on Add Term screen.
     */
    public function rcreviews_agent_url_add_field() {
        ?>
        <div class="form-field term-group">
            <label for="rcreview_agent_url"><?php esc_html_e( 'Agent URL', 'text_domain' ); ?></label>
            <input name="rcreview_agent_url" id="rcreview_agent_url" type="url" value="" />
            <p class="description"><?php esc_html_e( 'Link to the Agent Profile.', 'text_domain' ); ?></p>
        </div>
        <?php
    }

    /**
     * Render "Agent URL" field on Edit Term screen.
     *
     * @param WP_Term $term
     * @param string  $taxonomy
     */
    public function rcreviews_agent_url_edit_field( $term, $taxonomy ) {
        $value = get_term_meta( $term->term_id, 'rcreview_agent_url', true );
        ?>
        <tr class="form-field term-group-wrap">
            <th scope="row"><label for="rcreview_agent_url"><?php esc_html_e( 'Agent URL', 'text_domain' ); ?></label></th>
            <td>
                <input name="rcreview_agent_url" id="rcreview_agent_url" type="url" value="<?php echo esc_attr( $value ); ?>" />
                <p class="description"><?php esc_html_e( 'Link to the Agent Profile.', 'text_domain' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Render "Agency URL" field on Add Term screen.
     */
    public function rcreviews_agency_url_add_field() {
        ?>
        <div class="form-field term-group">
            <label for="rcreview_agency_url"><?php esc_html_e( 'Agency URL', 'text_domain' ); ?></label>
            <input name="rcreview_agency_url" id="rcreview_agency_url" type="url" value="" />
            <p class="description"><?php esc_html_e( 'Link to the Agency Profile.', 'text_domain' ); ?></p>
        </div>
        <?php
    }

    /**
     * Render "Agency URL" field on Edit Term screen.
     *
     * @param WP_Term $term
     * @param string  $taxonomy
     */
    public function rcreviews_agency_url_edit_field( $term, $taxonomy ) {
        $value = get_term_meta( $term->term_id, 'rcreview_agency_url', true );
        ?>
        <tr class="form-field term-group-wrap">
            <th scope="row"><label for="rcreview_agency_url"><?php esc_html_e( 'Agency URL', 'text_domain' ); ?></label></th>
            <td>
                <input name="rcreview_agency_url" id="rcreview_agency_url" type="url" value="<?php echo esc_attr( $value ); ?>" />
                <p class="description"><?php esc_html_e( 'Link to the Agency Profile.', 'text_domain' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save term meta for both agent and agency.
     *
     * @param int $term_id
     */
    public function rcreviews_agent_url_save( $term_id ) {
        // Agent URL
        if ( isset( $_POST['rcreview_agent_url'] ) ) {
            $val = esc_url_raw( wp_unslash( $_POST['rcreview_agent_url'] ) );
            if ( $val ) {
                update_term_meta( $term_id, 'rcreview_agent_url', $val );
            } else {
                delete_term_meta( $term_id, 'rcreview_agent_url' );
            }
        }

        // Agency URL
        if ( isset( $_POST['rcreview_agency_url'] ) ) {
            $val = esc_url_raw( wp_unslash( $_POST['rcreview_agency_url'] ) );
            if ( $val ) {
                update_term_meta( $term_id, 'rcreview_agency_url', $val );
            } else {
                delete_term_meta( $term_id, 'rcreview_agency_url' );
            }
        }
    }
}
