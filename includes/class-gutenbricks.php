<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://gutenbricks.com
 * @since      1.0.0
 *
 * @package    Gutenbricks
 * @subpackage Gutenbricks/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Gutenbricks
 * @subpackage Gutenbricks/includes
 * @author     WiredWP <ryan@wiredwp.com>
 */
class Gutenbricks_Core
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Gutenbricks_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	public $client;

	/*
	 * @since 1.0.0
	 */
	public static $plugin_admin;
	public static $block_registry;
	public static $gutenberg_editor;
	public static $gutenberg_block;
	public static $rest_api;
	public static $render_context;
	public static $integration;


	public function __construct()
	{
		// FOR DEV ONLY
		// $this->turn_on_error_reporting();

		if (defined('GUTENBRICKS_VERSION')) {
			$this->version = GUTENBRICKS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'gutenbricks';


		$this->load_dependencies();
		$this->load_elements();

		self::$integration = new Gutenbricks\Integration();
		self::$render_context = new Gutenbricks\Render_Context(self::$integration);
		self::$block_registry = new Gutenbricks\Block_Registry(self::$render_context);
		self::$plugin_admin = new Gutenbricks_Admin($this->get_plugin_name(), $this->get_version(), $this);
		self::$gutenberg_editor = new Gutenbricks\Gutenberg_Editor(self::$render_context, self::$block_registry);
		self::$gutenberg_block = new GutenBricks\GutenBricks_Block();

		self::$rest_api = new Gutenbricks\Rest_Api(
			self::$render_context,
			self::$block_registry,
			self::$gutenberg_editor,
		);

		$this->set_locale();
		$this->define_common_hooks();

		if (is_admin()) {
			$this->define_admin_hooks();
		}

		if (!is_admin() && !gutenbricks_is_ssr_request()) {
			$this->define_public_hooks();
		}
	}

	public function setup_client()
	{
		$this->client = new \GutenBricks\SureCart\Licensing\Client('GutenBricks', 'pt_UkWhZPJjM59eKoLA72m6Bq7e', GUTENBRICKS_PLUGIN_FILE);

		$this->client->set_textdomain('gutenbricks');

		$this->client->settings()->add_page(
			[
				'type' => 'submenu', // Can be: menu, options, submenu.
				'parent_slug' => 'gutenbricks', // add your plugin menu slug.
				'page_title' => 'Manage License',
				'menu_title' => 'Manage License',
				'capability' => 'manage_options',
				'menu_slug' => 'gutenbricks-manage-license',
				'icon_url' => '',
				'position' => null,
				'parent_slug' => '',
				'activated_redirect' => admin_url('admin.php?page=gutenbricks'),
				'deactivated_redirect' => admin_url('admin.php'),
			]
		);
	}

	public function init()
	{
		Gutenbricks\Bricks_Bridge::setup_dynamic_data_provider_ssr();
	}

	private function turn_on_error_reporting()
	{
		// Enable all error messages for WordPress
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);

		// Enable WordPress debug mode
		if (!defined('WP_DEBUG')) {
			define('WP_DEBUG', true);
		}
		if (!defined('WP_DEBUG_DISPLAY')) {
			define('WP_DEBUG_DISPLAY', true);
		}
		if (!defined('WP_DEBUG_LOG')) {
			define('WP_DEBUG_LOG', true);
		}

		// Disable WordPress fatal error handler
		if (!defined('WP_DISABLE_FATAL_ERROR_HANDLER')) {
			define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
		}
	}

	private function load_dependencies()
	{
		// vendor
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/vendor/vite-for-wp.php';

		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-gutenbricks-loader.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-gutenbricks-i18n.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-gutenbricks-admin.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-gutenbricks-public.php';

		// Core
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/core/class-integration.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/core/class-render-context.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/core/class-block-registry.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/core/class-block-value.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/core/class-gutenberg-editor.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/core/class-injector.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/core/class-rest-api.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/core/class-block.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/core/class-style-editor.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/core/class-element-should-render.php';

		// Utilities
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/utilities/class-css-scoper.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/utilities/class-performance-monitor.php';

		$this->loader = new Gutenbricks_Loader();
	}

	private function load_elements()
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/elements/element-base.php';

		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/elements/image.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/elements/text-basic.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/elements/text.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/elements/heading.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/elements/button.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/elements/text-link.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/elements/code.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/elements/video.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/elements/image-gallery.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/elements/gb-inner-block.php';

		// container elements
		// all extend to Div
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/elements/div.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/elements/section.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/elements/block.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/elements/container.php';


		new GutenBricks\Element\TextBasic();
		new GutenBricks\Element\Text();
		new GutenBricks\Element\Heading();
		new GutenBricks\Element\Image();
		new GutenBricks\Element\Button();
		new GutenBricks\Element\TextLink();
		new GutenBricks\Element\Code();
		new GutenBricks\Element\Video();
		new GutenBricks\Element\ImageGallery();
		new GutenBricks\Element\Section();
		new GutenBricks\Element\Container();
		new GutenBricks\Element\Block();
		new GutenBricks\Element\Div();
		new GutenBricks\Element\InnerBlock();
	}

	private function set_locale()
	{
		$plugin_i18n = new Gutenbricks_i18n();

		$this->loader->add_action('init', $plugin_i18n, 'load_plugin_textdomain');
	}

	private function define_common_hooks()
	{

		

		$this->loader->add_filter('theme_page_templates', self::$block_registry, 'register_template', 10, 1);
		$this->loader->add_filter('template_include', self::$block_registry, 'load_page_template', 99);
		$this->loader->add_filter('block_categories_all', self::$block_registry, 'filter__register_categories', 10, 1);

		$this->loader->add_action('rest_api_init', self::$rest_api, 'register_rest_route', 10);

		// CORE: intercepting the rest api call and inject frontend assets into wp/v2/block_renderer/{block_name}
		$this->loader->add_filter('rest_pre_dispatch', self::$rest_api, 'inject_wp_v2_block_renderer', 10, 3);
		
		// Register blocks for REST API requests (SSR)
		$this->loader->add_action('rest_api_init', $this, 'register_blocks_for_rest', 10);

		$this->loader->add_filter('bricks/setup/control_options', $this, 'register_gutenbricks_template_types', 10, 5);

		$this->loader->add_action('init', $this, 'setup_client', 9, 0);

		$this->loader->add_action('init', self::$integration, 'load_integrators', 10, 0);

		// $this->loader->add_action('init', self::$block_registry, 'register_blocks', 10, 0);

		// InnerBlock: Start
		// @since 1.1
		$this->loader->add_action('init', $this, 'setup_widgets', 19);
		// InnerBlock: End

		// @since 1.2
		$this->loader->add_action('init', self::$plugin_admin, 'enqueue_gb_builder_scripts', 90);

		$this->loader->add_action('wp_loaded', $this, 'register_blocks', 90);
		$this->loader->add_action('init', $this, 'init', 10003);
		$this->loader->add_filter('builder/settings/template/controls_data', $this, 'add_template_setting_controls', 10, 1);
		$this->loader->add_filter('bricks/dynamic_data/register_providers', $this, 'add_dynamic_data_provider', 10001, 1);

		// Rendering hooks. In order of execution: START
		$this->loader->add_filter('bricks/element/settings', self::$render_context, 'filter__inject_element_settings', 10, 2);
		$this->loader->add_filter('bricks/element/render', self::$render_context, 'should_render_element', 10, 2);
		$this->loader->add_filter('bricks/element/set_root_attributes', self::$render_context, 'inject_html_attributes', 10, 2);
		// :END

		// fire before the post is saved
		$this->loader->add_filter('content_save_pre', $this, 'debug_content_save_pre', 10, 1);


		// @since 1.0.6 
		// For Gutenberg editor
		if (gutenbricks_is_ssr_request()) {
			$this->loader->add_filter('gutenbricks/template/block_settings', self::$gutenberg_block, 'get_template_block_settings', 10, 2);
			$this->loader->add_filter('bricks/element/set_root_attributes', self::$gutenberg_editor, 'filter__set_root_attributes', 9999, 2); // we can't remove this

			// IMPORTANT: set_root_attributes is working on some elements so we need to filter /render_attributes
			// to modify attributes such as lightbox
			$this->loader->add_filter('bricks/element/render_attributes', self::$gutenberg_editor, 'filter__render_attributes', 9999, 3);
		}

	}

	public function debug_content_save_pre($content)
	{
		if (isset($_GET['action']) && $_GET['action'] === 'bricks_duplicate_content') {
			$content = str_replace('u003c', "\\\\\u003c", $content);
			$content = str_replace('u003e', "\\\\\u003e", $content);
		}

		// Handle Unicode characters in Gutenberg block data
		if (strpos($content, 'u0022') !== false) {
			// Convert u0022 to \" within block data
			$content = preg_replace('/u0022/', '\\"', $content);
		}

		return $content;
	}

	// TODO: move this to a separate class
	public function add_dynamic_data_provider($providers)
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/provider/class-gutenbricks-provider.php';
		$providers[] = 'gutenbricks';
		return $providers;
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{
		$plugin_public = new Gutenbricks_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_head', $plugin_public, 'wp_head_styles', 10);
		$this->loader->add_action('wp_footer', $plugin_public, 'wp_footer_render_css', 11); // BREAK: order important
		$this->loader->add_action('wp_footer', $plugin_public, 'wp_footer_fouc_fix', PHP_INT_MAX);
	}

	public function add_debug_dummy_dom()
	{
		?>
		<div id="maintenanceRenderFooter"></div>
		<div id="maintenanceRenderHeader"></div>
		<?php
	}

	private function define_admin_hooks()
	{
		$plugin_admin = self::$plugin_admin;
		$gutenberg_editor = self::$gutenberg_editor;

		$this->loader->add_filter('plugin_action_links_gutenbricks/gutenbricks.php', $this, 'plugin_add_settings_link', 10, 1);

		// @since 1.1.0
		// Bricks' bricksAdminMaintenanceTemplateListener function throws an error
		// We fix it by adding a dummy DOM
		$this->loader->add_action('admin_footer', $this, 'add_debug_dummy_dom'); // BREAK: order important
		$this->loader->add_action('wp_insert_post', $this, 'set_custom_page_template_with_option', 10, 1);
		$this->loader->add_action('admin_footer', $gutenberg_editor, 'admin_footer', 10);
		$this->loader->add_action('admin_head', $gutenberg_editor, 'admin_head', 10);

		$this->loader->add_filter('allowed_block_types_all', $gutenberg_editor, 'allowed_block_types', 999, 2);

		// Editor Specific hooks
		// we load this on the top to make sure the scripts are first
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'admin_enqueue_scripts', 90);
		$this->loader->add_action('current_screen', $gutenberg_editor, 'init_for_gutenberg_editor', 90);
		$this->loader->add_action('current_screen', $this, 'register_blocks_for_editor', 90);
		$this->loader->add_action('admin_init', $gutenberg_editor, 'remove_default_block_patterns', 5);
		$this->loader->add_action('wp_ajax_get_taxonomy_list', $gutenberg_editor, 'get_taxonomy_list', 10);
		$this->loader->add_action('wp_ajax_serve_gutenberg_editor_js', $gutenberg_editor, 'serve_gutenberg_editor_js', 10);
		$this->loader->add_action('wp_ajax_serve_admin_block_js', $gutenberg_editor, 'serve_admin_block_js', 10);

		// Admin hooks
		$this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_page', 99);
		$this->loader->add_action('admin_init', $plugin_admin, 'gutenbricks_register_options', 90);
		$this->loader->add_action('admin_init', $plugin_admin, 'process_options_submission', 90);
		$this->loader->add_action('admin_notices', $plugin_admin, 'admin_notices', 90);
		$this->loader->add_action('wp_ajax_dismiss_bricks_version_notice', $this, 'dismiss_bricks_version_warning', 10);
		$this->loader->add_action('save_post_bricks_template', $plugin_admin, 'bricks_template_save_postdata', 90);
		$this->loader->add_action('wp_ajax_gb_fetch_editor_posts', $plugin_admin, 'fetch_editor_posts', 10);

		if (is_post_gutenbricks_template()) {
			$this->loader->add_action('add_meta_boxes', $plugin_admin, 'add_meta_boxes', 90);
		}
	}

	// Called by wp_loaded action
	public function register_blocks()
	{
		// @since 1.1.20, we load all the blocks
		self::$block_registry->register_blocks();
	}

	// Called by current_screen action - only registers blocks when in Gutenberg editor
	public function register_blocks_for_editor()
	{
		// Only register blocks if we're in the Gutenberg editor
		if (gutenbricks_if_gutenberg_editor()) {
			self::register_specific_blocks();
		}
	}

	// Called by rest_api_init action - registers blocks for REST API requests
	public function register_blocks_for_rest()
	{
		// Only register blocks for SSR requests
		if (gutenbricks_is_ssr_request()) {
			self::register_specific_blocks();
		}
	}

	// @since 1.1.20
	public function register_specific_blocks()
	{
		// Get the raw request body
		$raw_data = file_get_contents('php://input');
        
		// Decode the JSON data
		$request_data = json_decode($raw_data, true);
		
		// Now you can access the attributes
		if (isset($request_data['attributes'])) {
			$attributes = $request_data['attributes'];
			if (isset($attributes['template_id'])) {
				self::$block_registry->register_blocks($attributes['template_id']);
			}
		}

	}


	/*
	 * Register GutenBricks block setting under the template settings
	 * 
	 * @since 1.0
	 */
	public function add_template_setting_controls($data)
	{
		if (\Gutenbricks\Bricks_Bridge::bricks_is_builder()) {
			if (!is_post_gutenbricks_template()) {
				return $data;
			}
		}

		$data['controlGroups']['_gutenbricks_block'] = [
			'title' => esc_html__('GutenBricks:Block', 'gutenbricks'),
		];

		$data['controlGroups']['_gutenbricks_meta'] = [
			'title' => esc_html__('GutenBricks:Meta', 'gutenbricks'),
		];

		$data['controls']['_gb_disable_template_edit'] = [
			'type' => 'checkbox',
			'group' => '_gutenbricks_block',
			'label' => 'Disable Template Editing',
			'description' => 'If checked, the template will not be editable in the block editor. Please reload to editor after making changes. <a>Learn More.</a>',
		];


		$data['controls']['_gb_setting_separator'] = [
			'group' => '_gutenbricks_block',
			'label' => esc_html__('Editing', 'gutenbricks'),
			'type' => 'separator',
			'required' => [
				'_gb_disable_template_edit',
				'!=',
				true
			],
		];

		$data['controls']['_gb_enable_inline_form'] = [
			'type' => 'checkbox',
			'group' => '_gutenbricks_block',
			'label' => 'Enable Inline Form',
			'description' => 'This will enable inline editing for the block with that pencil icon toggle.',
		];

		include __DIR__ . '/controls/block-settings.php';
		include __DIR__ . '/controls/block-branding-icon.php';
		include __DIR__ . '/controls/block-innerblock.php';
		include __DIR__ . '/controls/block-block-wrapper.php';
		include __DIR__ . '/controls/block-variant.php';

		// @since v1.0-RC5.5.0
		include __DIR__ . '/controls/block-meta-fields.php';

		return $data;
	}

	// add post content controls that controls element width
	public function add_post_content_controls($options)
	{
		$options['_gb_non_gb_content_width'] = [
			'tab' => 'content',
			'group' => 'gutenbricks_group',
			'label' => esc_html__('Non GutenBricks content width', 'gutenbricks'),
			'type' => 'number',
			'units' => true,
			'css' => [
				[
					'property' => 'padding',
					'selector' => '.brxe-div',
					'media' => 'desktop',
				],
			],
		];
		return $options;
	}

	public function add_control_groups($control_groups, $name)
	{
		if (!\Gutenbricks\Bricks_Bridge::bricks_is_builder()) {
			return $control_groups;
		}

		if (is_post_gutenbricks_template()) {
			if ($name !== 'post-content') {
				$control_groups['gutenbricks_group'] = [
					'tab' => 'content', // or 'style'
					'title' => esc_html__('GutenBricks', 'gutenbricks'),
				];
			}
		} else {
			if ($name === 'post-content') {
				$control_groups['gutenbricks_group'] = [
					'tab' => 'content', // or 'style'
					'title' => esc_html__('GutenBricks', 'gutenbricks'),
				];
			}
		}

		return $control_groups;
	}

	/**
	 * Add button next to "Deactivate" on Plugins page
	 */
	public function plugin_add_settings_link($links)
	{
		if ($this->client->license()->is_valid()) {
			$settings_link = '<a href="' . admin_url('admin.php?page=gutenbricks') . '">' . __('Settings') . '</a>';
		} else {
			$settings_link = '<a href="' . admin_url('admin.php?page=gutenbricks-manage-license') . '">' . __('Activate License') . '</a>';
		}

		// Add your settings link to the beginning of the links array
		array_unshift($links, $settings_link);

		return $links;
	}

	public function setup_widgets()
	{
		if (!class_exists('\Bricks\Elements')) {
			return;
		}

		// @since 1.1.18 not showing inner block in the builder if it's not a GutenBricks template
		if (\Gutenbricks\Bricks_Bridge::bricks_is_builder()) {
			if (!is_post_gutenbricks_template()) {
				return;
			}
		}

		$element_files = [
			__DIR__ . '/widgets/gutenbricks-inner-block.php',
		];

		foreach ($element_files as $file) {
			\Bricks\Elements::register_element($file);
		}
	}


	public static function register_gutenbricks_template_types($control_options)
	{
		$control_options['templateTypes'][GUTENBRICKS_TEMPLATE_TYPE_BLOCK_PAGE] = 'GutenBricks - Page Template';
		$control_options['templateTypes'][GUTENBRICKS_TEMPLATE_TYPE_BLOCK] = 'GutenBricks - Block';

		return $control_options;
	}


	function dismiss_bricks_version_warning()
	{
		check_ajax_referer('dismiss_bricks_version_notice_nonce', 'security');
		update_option('_gb_bricks_version_warning_ignored_for', GUTENBRICKS_VERSION);
		wp_die(); // Terminate AJAX request.
	}

	function set_custom_page_template_with_option($post_id)
	{
		// Check if it's a page and if the post is being inserted (not updated)
		if (get_post_type($post_id) !== 'page' || wp_is_post_revision($post_id)) {
			return;
		}

		// Check if the page is newly created
		if (get_post_status($post_id) === 'auto-draft') {
			// Get the selected template from the options
			$selected_template = get_option('_gutenbricks_default_page_template', '');

			if (!empty($selected_template)) {
				update_post_meta($post_id, '_wp_page_template', $selected_template);
			}
		}
	}

	public function run()
	{
		$this->loader->run();
	}

	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	public function get_loader()
	{
		return $this->loader;
	}

	public function get_version()
	{
		return $this->version;
	}



}
