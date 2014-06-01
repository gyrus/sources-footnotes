<?php
/**
 * Sources and Footnotes
 *
 * @package   Sources_Footnotes
 * @author    Steve Taylor
 * @license   GPL-2.0+
 */

/**
 * Plugin class
 *
 * @package Sources_Footnotes
 * @author  Steve Taylor
 */
class Sources_Footnotes {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   0.1
	 *
	 * @var     string
	 */
	protected $version = '0.1';

	/**
	 * Unique identifier for your plugin.
	 *
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 *
	 * @since    0.1
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'sources-footnotes';

	/**
	 * Instance of this class.
	 *
	 * @since    0.1
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    0.1
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * The plugin's settings.
	 *
	 * @since    0.1
	 *
	 * @var      array
	 */
	protected $settings = null;

	/**
	 * Custom fields for the current post.
	 *
	 * @since    0.1
	 *
	 * @var      array
	 */
	protected $custom_fields = null;

	/**
	 * Post types that footnotes are available for
	 *
	 * @since    0.1
	 *
	 * @var      array
	 */
	protected $footnotes_post_types = null;

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     0.1
	 */
	private function __construct() {

		// Set the settings
		//$this->settings = $this->get_settings();

		// Global init
		add_action( 'init', array( $this, 'init' ) );

		// Admin init
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// Add the settings page and menu item.
		//add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		//add_action( 'admin_init', array( $this, 'process_plugin_admin_settings' ) );

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Load public-facing style sheet and JavaScript.
		//add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		//add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'init', array( $this, 'register_custom_post_types' ), 0 );
		add_action( 'init', array( $this, 'register_custom_taxonomies' ), 0 );
		add_action( 'init', array( $this, 'register_custom_fields' ) );
		add_action( 'slt_cf_check_scope', array( $this, 'slt_cf_check_scope' ), 10, 7 );
		add_action( 'admin_head', array( $this, 'tinymce_button_init' ) );
		add_action( 'admin_footer', array( $this, 'tinymce_modal_markup' ) );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.1
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    0.1
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		// Trigger taxonomies first - this runs before init hook
		self::register_custom_taxonomies();

		// Add default source types
		if ( taxonomy_exists( 'sf_source_type' ) ) {
			wp_insert_term( 'Book', 'sf_source_type' );
			wp_insert_term( 'Article', 'sf_source_type' );
			wp_insert_term( 'Film', 'sf_source_type' );
			wp_insert_term( 'Web page', 'sf_source_type' );
			wp_insert_term( 'Song', 'sf_source_type' );
			wp_insert_term( 'Audio', 'sf_source_type' );
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    0.1
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

	}

	/**
	 * Initialize
	 *
	 * @since    0.1
	 */
	public function init() {

		// Load plugin text domain
		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		// Post types footnotes are available for
		$public_cpts = get_post_types( array( 'public' => true, '_builtin' => false ), 'names', 'and' );
		$this->footnotes_post_types = array( 'page', 'post' );
		foreach ( $public_cpts as $public_cpt ) {
			$this->footnotes_post_types[] = $public_cpt;
		}
		$this->footnotes_post_types = apply_filters( 'sf_footnotes_post_types', $this->footnotes_post_types );

	}

	/**
	 * Initialize admin
	 *
	 * @since	0.1
	 * @return	void
	 */
	public function admin_init() {

		// Output dependency notices
		if ( ! defined( 'SLT_CF_VERSION' ) ) {
			add_action( 'admin_notices', array( $this, 'output_dcf_dependency_notice' ) );
		}

	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     0.1
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {
		$screen = get_current_screen();

		if ( in_array( $screen->id, $this->footnotes_post_types ) ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'css/admin.css', __FILE__ ), array( 'dashicons' ), $this->version );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     0.1
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {
		$screen = get_current_screen();

		if ( in_array( $screen->id, $this->footnotes_post_types ) ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), $this->version );
		}

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    0.1
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'css/public.css', __FILE__ ), array(), $this->version );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    0.1
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'js/public.js', __FILE__ ), array( 'jquery' ), $this->version );
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    0.1
	 */
	public function add_plugin_admin_menu() {

		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Sources and footnotes', $this->plugin_slug ),
			__( 'Sources and footnotes', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    0.1
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Get the plugin's settings
	 *
	 * @since    0.1
	 */
	public function get_settings() {

		$settings = get_option( $this->plugin_slug . '_settings' );

		if ( ! $settings ) {
			// Defaults
			$settings = array();
		}

		return $settings;
	}

	/**
	 * Set the plugin's settings
	 *
	 * @since    0.1
	 */
	public function set_settings( $settings ) {
		return update_option( $this->plugin_slug . '_settings', $settings );
	}

	/**
	 * Process the settings page for this plugin.
	 *
	 * @since    0.1
	 */
	public function process_plugin_admin_settings() {

		// Submitted?
		if ( isset( $_POST[ $this->plugin_slug . '_settings_admin_nonce' ] ) && check_admin_referer( $this->plugin_slug . '_settings', $this->plugin_slug . '_settings_admin_nonce' ) ) {

			// Gather into array
			$settings = array();

			// Save as option
			$this->set_settings( $settings );

			// Redirect
			wp_redirect( admin_url( 'options-general.php?page=' . $this->plugin_slug . '&done=1' ) );

		}

	}

	/**
	 * Output Developer's Custom Fields dependency notice
	 *
	 * @since	0.1
	 * @return	void
	 */
	public function output_dcf_dependency_notice() {
		echo '<div class="error"><p>' . __( 'The Sources and Footnotes plugin depends on the <a href="http://wordpress.org/plugins/developers-custom-fields/">Developer\'s Custom Fields</a> plugin, which isn\'t currently activated', $this->plugin_slug ) . '</p></div>';
	}

	/**
	 * Initialize custom TinyMCE button
	 *
	 * @since	0.1
	 * @link	http://www.wpexplorer.com/wordpress-tinymce-tweaks/
	 */
	public function tinymce_button_init() {

		// Check user permission
		if ( ( get_post_type() == 'post' && current_user_can( 'edit_posts' ) ) || ( get_post_type() == 'page' && current_user_can( 'edit_pages' ) ) ) {

			// Check if WYSIWYG is enabled
			if ( get_user_option( 'rich_editing' ) == 'true' ) {
				add_filter( 'mce_external_plugins', array( $this, 'add_tinymce_plugin' ) );
				add_filter( 'mce_buttons', array( $this, 'register_tinymce_button' ) );
			}

		}

	}

	/**
	 * Add TinyMCE button JS
	 *
	 * @since	0.1
	 * @link	http://www.wpexplorer.com/wordpress-tinymce-tweaks/
	 */
	public function add_tinymce_plugin( $plugin_array ) {
		$plugin_array['sf_tinymce_button'] = plugins_url( 'js/tinymce-button.js', __FILE__ );
	    return $plugin_array;
	}

	/**
	 * Register TinyMCE buttons
	 *
	 * @since	0.1
	 * @link	http://www.wpexplorer.com/wordpress-tinymce-tweaks/
	 */
	public function register_tinymce_button( $buttons ) {
		array_push( $buttons, 'sf_tinymce_button' );
	    return $buttons;
	}

	/**
	 * Add the TinyMCE modal markup
	 *
	 * @since	0.1
	 */
	public function tinymce_modal_markup() {
		$screen = get_current_screen();

		if ( in_array( $screen->id, $this->footnotes_post_types ) ) {
			include_once( 'views/tinymce-modal.php' );
		}

	}

	/**
	 * Register custom post types
	 *
	 * @since	0.1
	 */
	public function register_custom_post_types() {

		// Sources
		register_post_type(
			'sf_source', apply_filters( 'sf_source_post_type_args', array(
				'labels'				=> array(
					'name'					=> __( 'Sources', $this->plugin_slug ),
					'singular_name'			=> __( 'Source', $this->plugin_slug ),
					'add_new'				=> __( 'Add New', $this->plugin_slug ),
					'add_new_item'			=> __( 'Add New Source', $this->plugin_slug ),
					'edit'					=> __( 'Edit', $this->plugin_slug ),
					'edit_item'				=> __( 'Edit Source', $this->plugin_slug ),
					'new_item'				=> __( 'New Source', $this->plugin_slug ),
					'view'					=> __( 'View Source', $this->plugin_slug ),
					'view_item'				=> __( 'View Source', $this->plugin_slug ),
					'search_items'			=> __( 'Search Sources', $this->plugin_slug ),
					'not_found'				=> __( 'No Sources found', $this->plugin_slug ),
					'not_found_in_trash'	=> __( 'No Sources found in Trash', $this->plugin_slug )
				),
				'public'			=> false,
				'show_ui'			=> true,
				'menu_position'		=> 20,
				'menu_icon'			=> 'dashicons-book-alt',
				'supports'			=> array( 'title', 'editor', 'custom-fields', 'thumbnail', 'revisions' ),
				'taxonomies'		=> array( 'sf_source_type', 'sf_author', 'sf_translator' ),
				'rewrite'			=> false
			) )
		);

	}

	/**
	 * Register custom taxonomies
	 *
	 * @since	0.1
	 */
	public function register_custom_taxonomies() {

		// Source types
		// Need to check existence in case this has already been called on plugin activation
		if ( ! taxonomy_exists( 'sf_source_type' ) ) {
			register_taxonomy(
				'sf_source_type', 'sf_source',
				array(
					'hierarchical'		=> true,
					'query_var'			=> false,
					'rewrite'			=> false,
					'show_admin_column'	=> true,
					'labels'			=> array(
						'name'				=> __( 'Source types', $this->plugin_slug ),
						'singular_name'		=> __( 'Source type', $this->plugin_slug ),
						'search_items'		=> __( 'Search Source types', $this->plugin_slug ),
						'all_items'			=> __( 'All Source types', $this->plugin_slug ),
						'edit_item'			=> __( 'Edit Source type', $this->plugin_slug ),
						'update_item'		=> __( 'Update Source type', $this->plugin_slug ),
						'add_new_item'		=> __( 'Add New Source type', $this->plugin_slug ),
						'new_item_name'		=> __( 'New Source type Name', $this->plugin_slug ),
					)
				)
			);
		}

		// Authors
		register_taxonomy(
			'sf_author', 'sf_source',
			array(
				'hierarchical'		=> false,
				'query_var'			=> false,
				'rewrite'			=> false,
				'show_admin_column'	=> true,
				'labels'			=> array(
					'name'				=> __( 'Authors', $this->plugin_slug ),
					'singular_name'		=> __( 'Author', $this->plugin_slug ),
					'search_items'		=> __( 'Search Authors', $this->plugin_slug ),
					'all_items'			=> __( 'All Authors', $this->plugin_slug ),
					'edit_item'			=> __( 'Edit Author', $this->plugin_slug ),
					'update_item'		=> __( 'Update Author', $this->plugin_slug ),
					'add_new_item'		=> __( 'Add New Author', $this->plugin_slug ),
					'new_item_name'		=> __( 'New Author Name', $this->plugin_slug ),
				)
			)
		);

		// Translators
		register_taxonomy(
			'sf_translator', 'sf_source',
			array(
				'hierarchical'		=> false,
				'query_var'			=> false,
				'rewrite'			=> false,
				'labels'			=> array(
					'name'				=> __( 'Translators', $this->plugin_slug ),
					'singular_name'		=> __( 'Translator', $this->plugin_slug ),
					'search_items'		=> __( 'Search Translators', $this->plugin_slug ),
					'all_items'			=> __( 'All Translators', $this->plugin_slug ),
					'edit_item'			=> __( 'Edit Translator', $this->plugin_slug ),
					'update_item'		=> __( 'Update Translator', $this->plugin_slug ),
					'add_new_item'		=> __( 'Add New Translator', $this->plugin_slug ),
					'new_item_name'		=> __( 'New Translator Name', $this->plugin_slug ),
				)
			)
		);

	}

	/**
	 * Register custom fields
	 *
	 * @since	0.1
	 */
	public function register_custom_fields() {
		global $pagenow;

		if ( function_exists( 'slt_cf_register_box' ) ) {

			// Main details
			$args = apply_filters( 'sf_custom_field_details_box_args', array(
				'type'			=> 'post',
				'title'			=> 'Details',
				'id'			=> 'sources-footnotes-details-box',
				'context'		=> 'above-content',
				'priority'		=> 'high',
				'description'	=> ( $pagenow == 'post-new.php' ) ? '<span class="sf-nb">' . __( 'Select the source type and author using the taxonomy boxes. Then save to populate type-specific fields.', $this->plugin_slug ) . '</span>' : '',
				'fields'	=> array(
					array(
						'name'			=> 'sf-source-subtitle',
						'label'			=> __( 'Subtitle', $this->plugin_slug ),
						'type'			=> 'text',
						'scope'			=> array( 'sf_source_book', 'sf_source_article', 'sf_source_web-page' ),
						'capabilities'	=> array( 'edit_posts', 'edit_pages' )
					),
					array(
						'name'			=> 'sf-source-anthology',
						'label'			=> __( 'Anthology?', $this->plugin_slug ),
						'type'			=> 'checkbox',
						'default'		=> false,
						'description'	=> __( 'If checked, authors are editors' ),
						'scope'			=> array( 'sf_source_book' ),
						'capabilities'	=> array( 'edit_posts', 'edit_pages' )
					),
					array(
						'name'			=> 'sf-source-year',
						'label'			=> __( 'Year', $this->plugin_slug ),
						'type'			=> 'text',
						'width'			=> 12,
						'description'	=> __( 'The year of original publication' ),
						'scope'			=> array( 'sf_source_book', 'sf_source_article', 'sf_source_film', 'sf_source_song' ),
						'capabilities'	=> array( 'edit_posts', 'edit_pages' )
					),
					array(
						'name'			=> 'sf-source-edition-year',
						'label'			=> __( 'Edition year', $this->plugin_slug ),
						'type'			=> 'text',
						'width'			=> 12,
						'description'	=> __( 'If different from year of original publication' ),
						'scope'			=> array( 'sf_source_book' ),
						'capabilities'	=> array( 'edit_posts', 'edit_pages' )
					),
					array(
						'name'			=> 'sf-source-publisher',
						'label'			=> __( 'Publisher', $this->plugin_slug ),
						'type'			=> 'text',
						'width'			=> 24,
						'scope'			=> array( 'sf_source_book' ),
						'capabilities'	=> array( 'edit_posts', 'edit_pages' )
					),
					array(
						'name'			=> 'sf-source-publisher-location',
						'label'			=> __( 'Publisher location', $this->plugin_slug ),
						'type'			=> 'text',
						'width'			=> 24,
						'scope'			=> array( 'sf_source_book' ),
						'capabilities'	=> array( 'edit_posts', 'edit_pages' )
					),
					array(
						'name'			=> 'sf-source-article-origin-title',
						'label'			=> __( 'Article origin title', $this->plugin_slug ),
						'type'			=> 'text',
						'description'	=> __( '' ),
						'scope'			=> array( 'sf_source_article' ),
						'capabilities'	=> array( 'edit_posts', 'edit_pages' )
					),
					array(
						'name'			=> 'sf-source-article-origin-volume',
						'label'			=> __( 'Article origin volume details', $this->plugin_slug ),
						'type'			=> 'text',
						'description'	=> __( 'e.g. Vol. 42, No. 19, pp. 100-120' ),
						'scope'			=> array( 'sf_source_article' ),
						'capabilities'	=> array( 'edit_posts', 'edit_pages' )
					),
					array(
						'name'			=> 'sf-source-url',
						'label'			=> __( 'URL', $this->plugin_slug ),
						'type'			=> 'text',
						'description'	=> __( 'For non-web sources, you can provide a URL if they\'re also available online' ),
						'scope'			=> array( 'sf_source_book', 'sf_source_article', 'sf_source_web-page' ),
						'capabilities'	=> array( 'edit_posts', 'edit_pages' )
					),
					array(
						'name'			=> 'sf-source-url-accessed',
						'label'			=> __( 'URL accessed date', $this->plugin_slug ),
						'type'			=> 'date',
						'scope'			=> array( 'sf_source_book', 'sf_source_article', 'sf_source_web-page' ),
						'capabilities'	=> array( 'edit_posts', 'edit_pages' )
					),
				)
			));
			slt_cf_register_box( $args );

		}

	}

	/**
	 * Custom scope checking for custom fields
	 *
	 * @since	0.1
	 */
	public function slt_cf_check_scope( $scope_match, $request_type, $scope, $object_id, $scope_key, $scope_value, $field ) {
		$scope_parts = explode( '_', $scope_value );

		// Check the prefix first
		if ( $scope_parts[0] == 'sf' ) {

			// Custom source scopes
			if ( $request_type == 'post' && get_post_type( $object_id ) == 'sf_source' ) {

				// Get source type terms
				$source_type_terms = get_terms( 'sf_source_type', array(
					'hide_empty'	=> false
				));
				$source_types = array();
				foreach ( $source_type_terms as $source_type_term ) {
					$source_types[] = $source_type_term->slug;
				}

				if ( count( $scope_parts ) > 2 && in_array( $scope_parts[2], $source_types ) ) {

					// Get source type for this source
					$the_source_type_terms = get_the_terms( $object_id, 'sf_source_type' );
					if ( $the_source_type_terms ) {
						$the_source_type = $the_source_type_terms[0]->slug;

						if ( $scope_value == 'sf_source_' . $the_source_type ) {
							$scope_match = true;
						}

					}

				}

			}

		}

		return $scope_match;
	}

}