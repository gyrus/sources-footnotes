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
	protected $version = '0.2.1';

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
	 * Stores the footnotes collected from current post
	 *
	 * @since    0.1
	 *
	 * @var      array
	 */
	protected $the_footnotes = null;

	/**
	 * Keeps track of footnote instances
	 *
	 * @since    0.1
	 *
	 * @var      array
	 */
	protected $footnote_instance = 0;

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     0.1
	 */
	private function __construct() {

		// Global init
		add_action( 'init', array( $this, 'init' ) );

		// Admin init
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// Add the settings page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'process_plugin_admin_settings' ) );

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Other hooks
		add_action( 'init', array( $this, 'register_custom_post_types' ), 0 );
		add_action( 'init', array( $this, 'register_custom_taxonomies' ), 0 );
		add_action( 'init', array( $this, 'register_custom_fields' ) );
		add_action( 'slt_cf_check_scope', array( $this, 'slt_cf_check_scope' ), 10, 7 );
		add_action( 'admin_head', array( $this, 'tinymce_button_init' ) );
		add_action( 'admin_footer', array( $this, 'tinymce_modal_markup' ) );
		add_action( 'save_post', array( $this, 'store_sort_title' ), 10, 2 );
		add_action( 'the_content', array( $this, 'list_footnotes_after_content' ), 999999 );
		add_filter( 'get_the_terms', array( $this, 'get_the_terms' ), 10, 3 );
		add_action( 'the_post', array( $this, 'post_init' ) );
		add_filter( 'post_class', array( $this, 'post_class' ) );

		// Shortcodes
		add_shortcode( 'sf_footnote', array( $this, 'footnote_shortcode' ) );
		add_shortcode( 'sf_list_footnotes', array( $this, 'list_footnotes_shortcode' ) );
		add_shortcode( 'sf_list_sources', array( $this, 'list_sources_shortcode' ) );

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

		// This is a trick used to get around the difficulty of adding hooks and calling non-static methods here
		// The actual activation stuff is done in admin_init
		// @link http://codex.wordpress.org/Function_Reference/register_activation_hook#Process_Flow
		add_option( __CLASS__ . '_activating', 1 );

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

		// Set the settings
		$this->settings = $this->get_settings();

		// Load plugin text domain
		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

	}

	/**
	 * Initialize admin
	 *
	 * @since	0.1
	 * @return	void
	 */
	public function admin_init() {

		// Any activation stuff to do?
		if ( get_option( __CLASS__ . '_activating' ) ) {

			// Clear activation flag
			delete_option( __CLASS__ . '_activating' );

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

		if ( in_array( $screen->id, array_merge( $this->settings['footnotes_post_types'], array( 'sf_source' ) ) ) ) {
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

		if ( in_array( $screen->id, array_merge( $this->settings['footnotes_post_types'], array( 'sf_source' ) ) ) ) {
			$script = defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV ? plugins_url( 'js/admin.js', __FILE__ ) : plugins_url( 'js/admin.min.js', __FILE__ );
			wp_enqueue_script( $this->plugin_slug . '-admin-script', $script, array( 'jquery' ), $this->version );
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
		$script = defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV ? plugins_url( 'js/public.js', __FILE__ ) : plugins_url( 'js/public.min.js', __FILE__ );
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', $script, array( 'jquery', 'jquery-ui-tooltip' ), $this->version );
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
	 * Gets the post types that might have footnotes
	 *
	 * @since    0.1
	 */
	protected function get_eligible_post_types() {

		$post_types = array( 'page' => 'Page', 'post' => 'Post' );
		$public_cpts = get_post_types( array( 'public' => true, '_builtin' => false ), 'objects', 'and' );
		foreach ( $public_cpts as $public_cpt ) {
			$post_types[ $public_cpt->name ] = $public_cpt->label;
		}

		return $post_types;
	}

	/**
	 * Get the plugin's settings
	 *
	 * @since    0.1
	 */
	public function get_settings() {

		$settings = get_option( $this->plugin_slug . '_settings' );

		// Defaults
		if ( ! $settings ) {
			$settings = array();
		}
		$settings = array_merge(
			array(
				'footnotes_post_types'		=> array_keys( $this->get_eligible_post_types() ),
				'auto_list_footnotes'		=> true,
				'auto_link_note_urls'		=> true,
				'footnotes_wrapper_tag'		=> 'aside',
				'list_footnotes_heading'	=> __( 'Notes', $this->plugin_slug ),
				'before_number'				=> '',
				'after_number'				=> '',
				'ibid'						=> false,
			),
			$settings
		);

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
			$settings = array(
				'footnotes_post_types'		=> $_REQUEST['footnotes_post_types'],
				'auto_list_footnotes'		=> isset( $_REQUEST['auto_list_footnotes'] ),
				'auto_link_note_urls'		=> isset( $_REQUEST['auto_link_note_urls'] ),
				'footnotes_wrapper_tag'		=> preg_replace( '/[^a-z]/', '', $_REQUEST['footnotes_wrapper_tag'] ),
				'list_footnotes_heading'	=> wp_strip_all_tags( $_REQUEST['list_footnotes_heading'] ),
				'before_number'				=> wp_strip_all_tags( $_REQUEST['before_number'] ),
				'after_number'				=> wp_strip_all_tags( $_REQUEST['after_number'] ),
				'ibid'						=> isset( $_REQUEST['ibid'] ),
			);

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
		$screen = get_current_screen();

		// Check user permission
		if ( in_array( $screen->id, $this->settings['footnotes_post_types'] ) ) {

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
		$plugin_array['sf_tinymce_button'] = defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV ? plugins_url( 'js/tinymce-button.js', __FILE__ ) : plugins_url( 'js/tinymce-button.min.js', __FILE__ );
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

		if ( in_array( $screen->id, $this->settings['footnotes_post_types'] ) ) {
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
	 * Filter terms to sort authors and translators by last name
	 *
	 * @since	0.1
	 * @param	array	$terms
	 * @param	int		$post_id
	 * @param	string	$taxonomy
	 * @param	array
	 */
	public function get_the_terms( $terms, $post_id, $taxonomy ) {

		// Authors or translators taxonomy?
		if ( in_array( $taxonomy, array( 'sf_author', 'sf_translator' ) ) ) {

			// Do the sorting
			usort( $terms, array( $this, 'sort_terms_by_last_name' ) );

		}

		return $terms;
	}

	/**
	 * Comparison to sort term objects by last name
	 *
	 * @since	0.1
	 * @param	object	$a
	 * @param	object	$b
	 * @return	int
	 */
	public function sort_terms_by_last_name( $a, $b ) {

		// Split by spaces
		$a_name_parts = explode( ' ', $a->name );
		$a_name_last = end( $a_name_parts );
		$b_name_parts = explode( ' ', $b->name );
		$b_name_last = end( $b_name_parts );

		// Compare
		return strcasecmp( $a_name_last, $b_name_last );

	}

	/**
	 * Last name first format
	 *
	 * @since	0.1
	 * @param	string	$name
	 * @return	string
	 */
	public function last_name_first( $name ) {

		if ( strpos( $name, ' ' ) !== false ) {
			$name_parts = explode( ' ', $name );
			$name = array_pop( $name_parts ) . ', ' . implode( ' ', $name_parts );
		}

		return $name;
	}

	/**
	 * List names
	 *
	 * @since	0.1
	 * @param	mixed	$names		Array of strings or object
	 * @param	string	$name_prop	If $names is an array of objects, this is the property in each object
	 * 								that contains the name
	 * @return	string
	 */
	public function list_names( $names, $name_prop = 'name' ) {
		$list = '';
		$n = 1;

		foreach ( $names as $name ) {

			// Object?
			if ( is_object( $name ) ) {
				$name = $name->{$name_prop};
			}

			// Add to list
			$list .= $name;

			// Separator?
			if ( count( $names ) > 1 && $n < count( $names ) ) {
				if ( $n == ( count( $names ) - 1 ) ) {
					$list .= ' &amp; ';
				} else {
					$list .= ', ';
				}
			}

			$n++;
		}

		return $list;
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
						'name'			=> 'sf-source-recommended',
						'label'			=> __( 'Recommended?', $this->plugin_slug ),
						'type'			=> 'checkbox',
						'scope'			=> array( 'sf_source' ),
						'capabilities'	=> array( 'edit_posts', 'edit_pages' )
					),
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

		if ( is_string( $scope_value ) ) {
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

		}

		return $scope_match;
	}

	/**
	 * Hooked to save_post to store the title for sorting
	 *
	 * @since	0.2
	 */
	public function store_sort_title( $post_id, $post ) {

		// Check the post is a source
		if ( get_post_type( $post_id ) == 'sf_source' ) {

			// Default to normal title
			$sort_title = $post->post_title;

			// Strip articles from start
			$sort_title_parts = explode( ' ', $sort_title );
			if ( in_array( $sort_title_parts[0], array( 'A', 'An', 'The' ) ) ) {
				array_shift( $sort_title_parts );
				$sort_title = implode( ' ', $sort_title_parts );
			}

			// Store as custom field
			update_post_meta( $post_id, '_sf_sort_title', $sort_title );

		}

	}

	/**
	 * Initialise at the start of each post in the loop
	 *
	 * @since	0.1
	 */
	public function post_init( $post ) {

		// Empty the footnotes array
		$this->the_footnotes = array();

		// Increment instance count
		$this->footnote_instance++;

	}

	/**
	 * Add class to each post
	 *
	 * @since	0.1
	 */
	public function post_class( $classes ) {

		// Flag that this is the post
		$classes[] = 'sf-post';

		// Instance
		$classes[] = 'sf-instance-' . $this->footnote_instance;

		return $classes;
	}

	/**
	 * The [sf_footnote] shortcode handler
	 *
	 * @since	0.1
	 */
	public function footnote_shortcode( $atts = array(), $note = null ) {
		$output = '';

		// Init attributes
		$a = shortcode_atts( array(
			'source'				=> null,
			'page'					=> null,
			'quoted'				=> false,
			'note_before_source'	=> false,
		), $atts );

		// Add the footnote
		$this->the_footnotes[] = array(
			'source_id'				=> $a['source'],
			'page'					=> $a['page'],
			'quoted'				=> $a['quoted'],
			'note'					=> $note,
			'note_before_source'	=> $a['note_before_source'],
		);
		$footnote_number = count( $this->the_footnotes );

		// Build the footnote number
		$output = '<span class="sf-number" id="sf-number-' . $footnote_number . '">' . $this->settings['before_number'] . '<a title="" rel="footnote" href="#sf-instance-' . $this->footnote_instance . '-note-' . $footnote_number . '">' . $footnote_number . '</a>' . $this->settings['after_number'] . '</span> ';

		return $output;
	}

	/**
	 * The [sf_list_footnotes] shortcode handler
	 *
	 * @since	0.2
	 */
	public function list_footnotes_shortcode() {
		return $this->list_footnotes( false );
	}

	/**
	 * The [sf_list_sources] shortcode handler
	 *
	 * @since	0.2
	 */
	public function list_sources_shortcode( $atts = array() ) {
		$output = '';

		// Init attributes
		$a = shortcode_atts( array(
			'type'						=> 'book',
			'author'					=> null,
			'recommended'				=> null,
			'list_type'					=> 'ul',
			'format'					=> 'listing',
			'listing_heading_level'		=> 3,
			'thumbnail_size'			=> 'post-thumbnail',
		), $atts );

		// Build taxonomy query
		$tax_query = array();
		$tax_query[] = array(
			'taxonomy'		=> 'sf_source_type',
			'field'			=> 'slug',
			'terms'			=> $a['type']
		);

		if ( is_string( $a['author'] ) ) {
			$tax_query[] = array(
				'taxonomy'		=> 'sf_source_author',
				'field'			=> 'slug',
				'terms'			=> $a['author']
			);
		}

		// Build meta query
		$meta_query = array();
		if ( ! is_null( $a['recommended'] ) && function_exists( 'slt_cf_field_key' ) ) {
			$meta_query[] = array(
				'key'		=> slt_cf_field_key( 'sf-source-recommended' ),
				'value'		=> $a['recommended']
			);
		}

		// Get sources
		$sources = new WP_Query( array(
			'post_type'			=> 'sf_source',
			'posts_per_page'	=> -1,
			'meta_key'			=> '_sf_sort_title',
			'orderby'			=> 'meta_value',
			'order'				=> 'ASC',
			'tax_query'			=> $tax_query,
			'meta_query'		=> $meta_query,
		));

		// Generate output
		if ( $sources->have_posts() ) {

			$output .= '<' . $a['list_type'] . ' class="sf-sources">';

			while ( $sources->have_posts() ) {
				$sources->the_post();
				$classes = array( $a['type'] );
				if ( has_post_thumbnail() ) {
					$classes[] = 'has-thumb';
				}
				$output .= '<li class="' . implode( ' ', $classes ) . '">' . $this->compile_source( $this->get_source_details( get_the_ID() ), $a['format'], $a['listing_heading_level'], $a['thumbnail_size'], apply_filters( 'the_content', get_the_content() ) ) . '</li>';
			}

			$output .= '</' . $a['list_type'] . '>';

		}

		// Reset query
		wp_reset_postdata();

		return $output;
	}

	/**
	 * List footnotes after content
	 *
	 * @since	0.1
	 */
	public function list_footnotes_after_content( $content ) {

		if ( $this->settings['auto_list_footnotes'] ) {
			$content = $content . $this->list_footnotes( false );
		}

		return $content;
	}

	/**
	 * List footnotes
	 *
	 * @since	0.1
	 * @param	bool	$echo
	 * @return	mixed
	 */
	public function list_footnotes( $echo = true ) {
		$output = '';

		if ( $this->the_footnotes ) {

			// Open wrapper
			$output .= '<' . $this->settings['footnotes_wrapper_tag'] . ' id="sf-footnotes">';

			// Heading
			$output .= '<h2>' . esc_html( $this->settings['list_footnotes_heading'] ) . '</h2>';

			// Open list
			$output .= '<ol>';

			// List footnotes
			$n = 1;
			$sources_cache = array();
			$last_source_id = null; // Keep track for ibid.
			$last_source_ids_by_author = array(); // Keep track for op. cit.
			foreach ( $this->the_footnotes as $footnote ) {
				$footnote_output = '';

				// Quoted?
				if ( $footnote['quoted'] ) {
					$footnote_output .= __( 'Quoted in', $this->plugin_slug ) . ' ';
				}

				// The source
				if ( $footnote['source_id'] ) {

					// Has the source been used before?
					if ( ! array_key_exists( $footnote['source_id'], $sources_cache ) ) {

						// Add details to cache
						$sources_cache[ $footnote['source_id'] ] = $this->get_source_details( $footnote['source_id'] );
						// Easy reference
						$source_details = &$sources_cache[ $footnote['source_id'] ];

						// Compile the source
						$compiled_source = $this->compile_source( $source_details );

						// Store compiled source in cache
						$source_details['compiled_source'] = $compiled_source;

					} else {

						// Pass compiled source through
						$compiled_source = $sources_cache[ $footnote['source_id'] ]['compiled_source'];
						$source_details = &$sources_cache[ $footnote['source_id'] ]; // Easy reference

					}

					// To keep track for op. cit., we need a way of identifying by multiple authors
					if ( $source_details['authors'] ) {
						$authors_ids = array();
						foreach ( $source_details['authors'] as $author ) {
							$authors_ids[] = $author->term_id;
						}
						$authors_id = implode( '-', $authors_ids );
					} else {
						$authors_id = null;
					}

					// Handle ibid. and op. cit.
					if ( $this->settings['ibid'] && $last_source_id == $footnote['source_id'] ) {

						// Ibid.
						$footnote_output .= '<i>Ibid.</i>';

					} else if ( $this->settings['ibid'] && array_key_exists( $authors_id, $last_source_ids_by_author ) ) {

						// Op. cit.
						if ( $last_source_ids_by_author[ $authors_id ] == $footnote['source_id'] ) {

							// Same source
							$footnote_output .= $this->list_names( $source_details['authors'] ) . ', <i>Op. cit.</i>';

						} else {

							// Different source
							$footnote_output .= $this->list_names( $source_details['authors'] ) . ' (' . $source_details['meta']['sf-source-year'] . '), <i>Op. cit.</i>';
						}

					} else {

						// Full source
						$footnote_output .= $compiled_source;

					}

					// Page reference?
					if ( ! empty( $footnote['page'] ) ) {

						if ( strpos( $footnote['page'], '-' ) !== false ) {
							$footnote_output .= ', pp. ';
						} else {
							$footnote_output .= ', p. ';
						}
						$footnote_output .= $footnote['page'] . '.';

					} else {

						$footnote_output .= '. ';

					}

					// Keep track for ibid.
					$last_source_id = $footnote['source_id'];

					// And for op. cit.
					if ( $authors_id ) {
						$last_source_ids_by_author[ $authors_id ] = $footnote['source_id'];
					}

				}

				// The note
				if ( $footnote['note'] ) {

					// Automatically link URLs?
					if ( $this->settings['auto_link_note_urls'] ) {
						$footnote['note'] = preg_replace( '/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/', '<a href="\0">\0</a>', $footnote['note'] );
					}

					// Add note
					if ( $footnote['note_before_source'] ) {
						$footnote_output = $footnote['note'] . ' ' . $footnote_output;
					} else {
						$footnote_output .= ' ' . $footnote['note'];
					}

				}

				// Jump back
				// @link http://daringfireball.net/2005/07/footnotes
				$footnote_output .= ' <a rev="footnote" href="#sf-number-' . $n . '" class="sf-jump-back" title="' . __( 'Jump back to the text for this note', $this->plugin_slug ) . '">' . apply_filters( 'sf_jump_back_link_text', '&#8617;' ) . '</a>';

				// Wrap up
				$footnote_output = '<li id="sf-instance-' . $this->footnote_instance . '-note-' . $n . '">' . $footnote_output . '</li>';

				// Filter and append to output
				$output .= apply_filters( 'sf_footnote', $footnote_output, $footnote['source_id'] );

				$n++;
			}

			// Close list
			$output .= '</ol>';

			// Close wrapper
			$output .= '</' . $this->settings['footnotes_wrapper_tag'] . '>';

		}

		if ( $echo ) {
			echo $output;
		} else {
			return $output;
		}
	}

	/**
	 * Get all source details
	 *
	 * @since	0.1
	 * @param	int		$source_id
	 * @return	array
	 */
	public function get_source_details( $source_id ) {

		$types = get_the_terms( $source_id, 'sf_source_type' );
		$details = array(
			'id'			=> $source_id,
			'title'			=> get_the_title( $source_id ),
			'meta'			=> array(),
			'authors'		=> get_the_terms( $source_id, 'sf_author' ),
			'translators'	=> get_the_terms( $source_id, 'sf_translator' ),
			'type'			=> array_shift( $types )
		);

		if ( function_exists( 'slt_cf_all_field_values' ) ) {
			$details['meta'] = slt_cf_all_field_values( 'post', $source_id );
		}

		return $details;
	}

	/**
	 * Compiles a source's details for output
	 *
	 * @since	0.1
	 * @param	array		$source_details
	 * @param	string		$format						'citation' | 'listing'
	 * @param	int			$listing_heading_level
	 * @param	string		$thumb_size
	 * @param	string		$description
	 * @return	array
	 */
	public function compile_source( $source_details, $format = 'citation', $listing_heading_level = 3, $thumb_size = 'post-thumbnail', $description = null ) {
		$compiled_source = '';

		/*
		 * First, build up each component
		 */
		$authors_year = '';

		// Authors / translators
		if ( $source_details['authors'] ) {

			// List authors
			$authors_year .= $this->list_names( $source_details['authors'] );

			// Film director?
			if ( $source_details['type']->slug == 'film' ) {
				$authors_year .= ' (' . __( 'dir.', $this->plugin_slug ) . ')';
			}

			// Editor(s)?
			if ( isset( $source_details['meta']['sf-source-anthology'] ) && $source_details['meta']['sf-source-anthology'] ) {
				if ( count( $source_details['authors'] ) > 1 ) {
					$authors_year .= ' (' . __( 'eds.', $this->plugin_slug ) . ')';
				} else {
					$authors_year .= ' (' . __( 'ed.', $this->plugin_slug ) . ')';
				}
			}

			// List translators
			if ( $source_details['translators'] ) {
				$authors_year .= ', ' . $this->list_names( $source_details['translators'] ) . ' (' . __( 'trans.', $this->plugin_slug ) . ')';
			}

		}

		// Add year on
		if ( isset( $source_details['meta']['sf-source-year'] ) && $source_details['meta']['sf-source-year'] ) {
			if ( $authors_year ) {
				$authors_year .= ' (' . $source_details['meta']['sf-source-year'] . ')';
			} else {
				$authors_year = $source_details['meta']['sf-source-year'];
			}
		}

		// Meta stuff according to type
		$meta = array();
		switch ( $source_details['type']->slug ) {

			case 'book': {
				// Publication details
				$publication_details = '';
				if ( ! empty( $source_details['meta']['sf-source-publisher'] ) ) {
					if ( $format == 'citation' && ! empty( $source_details['meta']['sf-source-publisher-location'] ) ) {
						$publication_details .= $source_details['meta']['sf-source-publisher-location'] . ': ';
					}
					$publication_details .= $source_details['meta']['sf-source-publisher'];
					if ( $format == 'citation' && ! empty( $source_details['meta']['sf-source-edition-year'] ) && strcmp( $source_details['meta']['sf-source-edition-year'], $source_details['meta']['sf-source-year'] ) ) {
						$publication_details .= ', ' . $source_details['meta']['sf-source-edition-year'];
					}
				}
				if ( $publication_details ) {
					$meta[] = $publication_details;
				}

				// URL
				if ( $url = $this->source_url( $source_details ) ) {
					$meta[] = $url;
				}

				break;
			}

			case 'article': {

				// Origin details
				$origin_details = '';
				if ( ! empty( $source_details['meta']['sf-source-article-origin-title'] ) ) {
					if ( $format == 'citation' ) {
						$origin_details .= __( 'in', $this->plugin_slug );
					}
					$origin_details .= ' <i>' . $source_details['meta']['sf-source-article-origin-title'] . '</i>';
					if ( ! empty( $source_details['meta']['sf-source-article-origin-volume'] ) ) {
						$origin_details .= ' (' . $source_details['meta']['sf-source-article-origin-volume'] . ')';
					}
				}
				if ( $origin_details ) {
					$meta[] = $origin_details;
				}

				// URL
				if ( $url = $this->source_url( $source_details ) ) {
					$meta[] = $url;
				}

				break;
			}

			case 'web-page': {

				// Accessed date
				if ( ! empty( $source_details['meta']['sf-source-url-accessed'] ) ) {
					$meta[] = ' (' . __( 'accessed', $this->plugin_slug ) . ' ' . apply_filters( 'sf_date_format', $source_details['meta']['sf-source-url-accessed'], $source_details['meta']['sf-source-url-accessed'] ) . ')';
				}

				break;
			}

		}

		/*
		 * Now put it all together according to format
		 */
		if ( $format == 'citation' ) {

			// Citation
			$compiled_source .= $authors_year;
			if ( $compiled_source ) {
				$compiled_source .= ', ';
			}
			$compiled_source .= $this->format_source_title( $source_details['title'], $source_details );
			if ( $meta ) {
				$compiled_source .= ', ' . implode( ', ', $meta );
			}

		} else {

			// Thumbnail
			if ( has_post_thumbnail( $source_details['id'] ) ) {
				$compiled_source .= '<figure class="sf-thumb">' . get_the_post_thumbnail( $source_details['id'], $thumb_size ) . '</figure>';
			}

			// Enclose the text so it can be positioned separately from the thumb
			$compiled_source .= '<div class="sf-text">';

			// Title
			$compiled_source .= '<h' . $listing_heading_level . ' class="sf-title">' . $this->format_source_title( $source_details['title'], $source_details ) . '</h' . $listing_heading_level . '>';

			// Meta
			if ( $authors_year || $meta ) {
				$compiled_source .= '<ul class="sf-meta">';
				if ( $authors_year ) {
					$compiled_source .= '<li>' . $authors_year . '</li>';
				}
				if ( $meta ) {
					$compiled_source .= '<li>' . implode( '</li><li>', $meta ) . '</li>';
				}
				$compiled_source .= '</ul>';
			}

			// Description
			if ( $description ) {
				$compiled_source .= '<div class="sf-description">' . $description . '</div>';
			}

			// Close the text
			$compiled_source .= '</div>';

		}

		// Anything else can be added using this hook
		$compiled_source = apply_filters( 'sf_compiled_source', $compiled_source, $source_details, $format );

		return $compiled_source;
	}

	/**
	 * Generate source URL for output
	 *
	 * @since	0.1
	 * @param	array		$source_details
	 * @return	string
	 */
	protected function source_url( $source_details ) {
		$output = '';

		if ( ! empty( $source_details['meta']['sf-source-url'] ) ) {
			$output .= '<a href="' . esc_url( $source_details['meta']['sf-source-url'] ) . '">' . esc_url( $source_details['meta']['sf-source-url'] ) . '</a>';
			if ( ! empty( $source_details['meta']['sf-source-url-accessed'] ) ) {
				$output .= ' (' . __( 'accessed', $this->plugin_slug ) . ' ' . apply_filters( 'sf_date_format', $source_details['meta']['sf-source-url-accessed'], $source_details['meta']['sf-source-url-accessed'] ) . ')';
			}
		}

		return $output;
	}

	/**
	 * Format a source's title
	 *
	 * @since	0.1
	 * @param	string		$title
	 * @param	array		$source_details
	 * @param	bool		$include_subtitle
	 * @return	string
	 */
	public function format_source_title( $title, $source_details, $include_subtitle = true ) {
		$formatted_title = '';

		// Subtitle?
		if ( $include_subtitle && ! empty( $source_details['meta']['sf-source-subtitle'] ) ) {
			$title .= ': ' . $source_details['meta']['sf-source-subtitle'];
		}

		switch ( $source_details['type']->slug ) {

			case 'article':
			case 'song': {
				$formatted_title = '&#8216;' . $title . '&#8217;';
				break;
			}

			case 'web-page': {
				$formatted_title = $title;
				if ( ! empty( $source_details['meta']['sf-source-url'] ) ) {
					$formatted_title = '<a href="' . esc_url( $source_details['meta']['sf-source-url'] ) . '">' . $title . '</a>';
				}
				break;
			}

			default: {
				$formatted_title = '<i>' . $title . '</i>';
				break;
			}

		}

		// Apply filters
		$formatted_title = apply_filters( 'sf_source_title', $formatted_title, $title, $source_details );

		return $formatted_title;
	}

}