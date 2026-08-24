<?php
/**
 *
 * @package    Gutenbricks
 * @subpackage Gutenbricks/admin
 * @author     WiredWP <ryan@wiredwp.com>
 */
class Gutenbricks_Admin
{
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

	private $gutenbricks_core;

	private $template_bundles = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version, $gutenbricks_core)
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->gutenbricks_core = $gutenbricks_core;
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page()
	{
		add_submenu_page(
			'bricks',
			'GutenBricks',
			'GutenBricks',
			'manage_options',
			'gutenbricks',
			array($this, 'create_admin_page'),
			100
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page()
	{
		if (!$this->gutenbricks_core->client->license()->is_valid()) {
			$redirect_url = admin_url('admin.php?page=gutenbricks-manage-license');

			// redirect using Javascript
			?>
				<script>
					window.location.href = '<?php echo $redirect_url; ?>';
				</script>
			<?php
			return;
		}

		?>
		<div class="wrap">
			<?php include 'partials/gutenbricks-admin-settings.php'; ?>
		</div>
		<?php
	}

	/* 
	 * FOR_ADMIN
	 * Settings for Gutenberg Editor
	 */
	public function gutenbricks_register_options()
	{
		register_setting('gutenbricks_options_group', '_gutenbricks_active_bundles');
		register_setting('gutenbricks_options_group', '_gutenbricks_active_wrap_patterns');
		register_setting('gutenbricks_options_group', '_gutenbricks_hide_pattern_tab');
		register_setting('gutenbricks_options_group', '_gutenbricks_hide_block_tab');
		register_setting('gutenbricks_options_group', '_gutenbricks_hide_media_tab');
		register_setting('gutenbricks_options_group', '_gutenbricks_hide_wp_patterns');
		register_setting('gutenbricks_options_group', '_gutenbricks_hide_other_blocks');
		register_setting('gutenbricks_options_group', '_gutenbricks_use_template');
		register_setting('gutenbricks_options_group', '_gutenbricks_adv_text_fallback');
		register_setting('gutenbricks_options_group', '_gutenbricks_acf_settings_name');
		register_setting('gutenbricks_options_group', '_gutenbricks_mb_settings_name');
		register_setting('gutenbricks_options_group', '_gutenbricks_default_page_template');
		register_setting('gutenbricks_options_group', '_gutenbricks_gutenberg_custom_css');
		register_setting('gutenbricks_options_group', '_gutenbricks_gutenberg_footer_html');
		register_setting('gutenbricks_options_group', '_gutenbricks_default_bundle_name');
		register_setting('gutenbricks_options_group', '_gutenbricks_default_page_template_name');
		register_setting('gutenbricks_options_group', '_gutenbricks_bundle_post_types');
		register_setting('gutenbricks_options_group', '_gutenbricks_fouc_fix');
		register_setting('gutenbricks_options_group', '_gutenbricks_enable_hidden_values');

		do_action('gutenbricks/register_options');
	}

	// FOR_ADMIN
	public function process_options_submission()
	{
		if (isset($_POST['_gutenbricks_option_saved'])) { // Make sure to verify nonce for security
			set_transient('_gutenbricks_admin_message', 'Settings saved successfully.', 60);
		}
	}

	// FOR_ADMIN
	public function admin_notices()
	{
		if ($notice = get_transient('_gutenbricks_admin_message')) {
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php echo esc_html($notice); ?>
				</p>
			</div>
			<?php
			delete_transient('_gutenbricks_admin_message');
		}
	}


	// We have to enqueue it separately because admin_enqueue_scripts is not loading
	// in Bricks Builder
	public function enqueue_gb_builder_scripts()
	{
		if (\Gutenbricks\Bricks_Bridge::bricks_is_builder()) {
			\GutenBricks\Kucrut\Vite\enqueue_asset(
				GUTENBRICKS_PLUGIN_DIR . 'admin/dist',
				'admin/gb-builder.jsx',
				array(
					'handle' => 'gutenbricks_builder',
					'dependencies' => array(),
					'version' => GUTENBRICKS_VERSION,
				)
			);
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function admin_enqueue_scripts()
	{
		GutenBricks\Gutenberg_Editor::enqueue_gutenberg_editor_scripts();

		// Loading available blocks
		// This is intended for other apps
		/*
		// Turn off for now
		$query_params = array(
			'action' => 'serve_admin_block_js',
			'_wpnonce' => wp_create_nonce('serve_admin_block_js'),
			'_locale' => gutenbricks_get_current_locale(),
		);

		wp_enqueue_script(
			'gutenbricks_serve_admin_block_js',
			add_query_arg($query_params, admin_url('admin-ajax.php')),
			array(
				GUTENBRICKS_PLUGIN_NAME,
			),
			GUTENBRICKS_VERSION
		);
		*/


		// STEP: Loading setting js
		if (gutenbricks_if_settings_page()) {

			\GutenBricks\Kucrut\Vite\enqueue_asset(
				GUTENBRICKS_PLUGIN_DIR . 'admin/dist',
				'admin/settings.jsx',
				array(
					'handle' => 'gutenbricks_settings',
					'dependencies' => array('jquery', 'wp-blocks', 'wp-i18n', 'wp-element'),
					'version' => GUTENBRICKS_VERSION,
				)
			);
			
			wp_localize_script('gutenbricks_settings', 'gbAjaxObject', array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce($this->plugin_name . '_nonce')
			));
			return;
		}	
		
	}	

	public function add_meta_boxes()
	{
		add_meta_box(
			'bricks_template_description_meta_box', // ID of the meta box
			'Gutenberg Block Settings', // Title of the meta box
			array($this, 'bricks_template_meta_box_html'), // Function that fills the box with desired content
			GUTENBRICKS_DB_TEMPLATE_SLUG, // Post type
			'normal', // Context where the box should display
			'high' // Priority of the box
		);
	}

	public function bricks_template_meta_box_html($post)
	{
		$value = get_post_meta($post->ID, '_gutenbricks_block_custom_title', true);
		?>
		<div style="margin: 1.5em 0;">
			<label for="_gutenbricks_block_custom_title"><b>Custom Block Title for Gutenberg Users</b></label>
			<div style="margin-top: 0.5em; margin-bottom: 1em;">This is useful when your template has an internal name and you
				have more meaningful names for Gutenberg users.</div>
			<input type="text" name="_gutenbricks_block_custom_title" id="_gutenbricks_block_custom_title" rows="4"
				style="width:100%" value="<?php echo esc_textarea($value); ?>" />
		</div>
		<?php
		$value = get_post_meta($post->ID, '_gutenbricks_block_description', true);
		?>
		<div style="margin: 1.5em 0;">
			<label for="_gutenbricks_block_description"><b>Block Description</b></label>
			<div style="margin-top: 0.5em; margin-bottom: 1em;">This will be displayed under the block name on the right sidebar,
				ideally within 50 characters.</div>
			<textarea name="_gutenbricks_block_description" id="_gutenbricks_block_description" rows="4"
				style="width:100%"><?php echo esc_textarea($value); ?></textarea>
		</div>

		<div style="margin: 1.5em 0;">
			<label><b>Guideline and documentation</b></label>
			<div style="margin-top: 0.5em; margin-bottom: 1em;">You can add supporting documents and guidelines for the block.
			</div>
			<?php
			// New Support and Documentation Field
			$support_value = get_post_meta($post->ID, '_gutenbricks_block_documentation', true);
			wp_editor(
				$support_value,
				'_gutenbricks_block_documentation',
				array(
					'wpautop' => true,
					'media_buttons' => true,
					'textarea_name' => '_gutenbricks_block_documentation',
					'textarea_rows' => 10,
					'teeny' => true,
					'tinymce' => array(
						'toolbar1' => 'formatselect,bold,italic,underline,|,bullist,numlist,|,undo,redo,|,link,unlink,|,fullscreen',
						'toolbar2' => '', // Add more tools if needed
						'block_formats' => 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6;',
					)
				)
			);
			?>
		</div>

		<?php
	}

	function bricks_template_save_postdata($post_id)
	{

		if (array_key_exists('_gutenbricks_block_custom_title', $_POST)) {
			update_post_meta(
				$post_id,
				'_gutenbricks_block_custom_title',
				sanitize_textarea_field($_POST['_gutenbricks_block_custom_title'])
			);
		}

		if (array_key_exists('_gutenbricks_block_description', $_POST)) {
			update_post_meta(
				$post_id,
				'_gutenbricks_block_description',
				sanitize_textarea_field($_POST['_gutenbricks_block_description'])
			);
		}

		if (isset($_POST['_gutenbricks_block_documentation'])) {
			update_post_meta(
				$post_id,
				'_gutenbricks_block_documentation',
				wpautop($_POST['_gutenbricks_block_documentation']) // Sanitize for HTML content
			);
		}


	}


	public function render_with_gutenbricks_template_action($slug, $name)
	{
		// Prevent the original template part from loading.
		remove_all_actions('get_template_part_' . $slug);

		// Include your custom template.
		// Make sure the path is correct. This path is relative to your plugin directory.
		$custom_template = plugin_dir_path(__FILE__) . '../template-parts/page.php';
		if (file_exists($custom_template)) {
			include $custom_template;
		}
	}

	// for hook: wp_ajax_gb_fetch_editor_posts
	public function fetch_editor_posts()
	{
		// Check user capability
		if (!current_user_can('edit_posts')) {
			wp_send_json_error('No permission');
			return;
		}

		$ids = isset($_POST['ids']) ? explode(',', $_POST['ids']) : [];
		$posts_data = [];

		foreach ($ids as $id) {
			$post = get_post((int) $id);
			if ($post) {
				$posts_data[] = [
					'id' => $post->ID,
					'title' => $post->post_title,
					'type' => $post->post_type,
				];
			}
		}

		wp_send_json_success($posts_data);
	}


}
