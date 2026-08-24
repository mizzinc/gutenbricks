<?php
namespace Gutenbricks;

class Gutenberg_Editor
{
	private $block_registry;
	private $render_context;
	public static $current_block_settings = array();

	public function __construct($render_context, $block_registry)
	{
		$this->render_context = $render_context;
		$this->block_registry = $block_registry;
	}

	public function init_for_gutenberg_editor()
	{
		if (gutenbricks_is_option_page() || if_gutenbricks_enabled_for_post_type()) {
			$this->block_registry->register_for_admin();
		}
	}


	public function remove_default_block_patterns()
	{
		if (get_option('_gutenbricks_hide_wp_patterns')) {
			remove_theme_support('core-block-patterns');
		}
	}

	public static function get_template_preview_image($template_id, $size = 'large')
	{
		$thumbnail_id = get_post_thumbnail_id($template_id);

		$image = "null";

		if ($thumbnail_id) {
			$image = wp_get_attachment_image_src($thumbnail_id, $size);
			if ($image) {
				$image = $image[0];
			}
		}

		return $image;
	}


	private function get_gbmeta_fields($template_settings, $post_id)
	{
		if (empty($template_settings['_gb_meta_fields'])) {
			return null;
		}

		$fields = $template_settings['_gb_meta_fields'];

		$gbmeta_fields = array();
		foreach ($fields as $field) {
			if (!empty($field['choices'])) {
				if (function_exists('bricks_render_dynamic_data')) {
					$field['choices'] = bricks_render_dynamic_data($field['choices'], $post_id);
				}

				// $field['choices] is a string
				// each item is separated by a new line
				// and should be parsed as label:value
				// if there is only one value without : then it's used as both label and value
				$items = explode("\n", $field['choices']);

				$options = array();

				foreach ($items as $item) {
					if (empty(trim($item))) {
						continue;
					}

					$parts = explode(':', $item);

					$value = trim($parts[0]);

					if (count($parts) === 1) {
						$options[$value] = $value;
					} else {
						$label = trim($parts[1]);
						$options[$value] = $label;
					}
				}
			}


			$default_value = Block_Registry::get_gb_meta_field_default_value($field);

			$gbmeta_fields[$field['name']] = array(
				'label' => $field['label'],
				'name' => $field['name'],
				'path' => $field['name'], // @since 1.1.0 
				'type' => $field['type'] ?? 'text',
				'default_value' => $default_value,
				'instructions' => $field['instructions'] ?? '',
				'choices' => empty($options) ? null : $options,
				'enable_opacity' => empty($field['enable_opacity']) ? false : $field['enable_opacity'],

				'min' => $field['min'] ?? null,
				'max' => $field['max'] ?? null,
				'step' => $field['step'] ?? null,

				'true_value' => $field['true_value'] ?? 1,
				'false_value' => $field['false_value'] ?? 0,
			);
		}

		return $gbmeta_fields;
	}

	function allowed_block_types($allowed_blocks, $editor_context)
	{
		if (get_option('_gutenbricks_hide_other_blocks') === '1') {
			// If $allowed_blocks is true, get all registered blocks
			$allowed_blocks = array_keys(\WP_Block_Type_Registry::get_instance()->get_all_registered());

			// Filter out blocks that start with "core/"
			$allowed_blocks = array_values(array_filter($allowed_blocks, function ($block_name) {
				return strpos($block_name, 'core/') !== 0;
			}));
		}

		return $allowed_blocks;
	}

	// for admin-ajax.php
	public function get_taxonomy_list()
	{
		// Check for nonce security
		check_ajax_referer(GUTENBRICKS_NONCE, 'security');

		$taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';

		if (!$taxonomy) {
			wp_send_json_error('No taxonomy provided');
			wp_die();
		}

		$terms = get_terms(array(
			'taxonomy' => $taxonomy,
			'hide_empty' => false,
		));

		if (is_wp_error($terms)) {
			wp_send_json_error($terms->get_error_message());
		} else {
			wp_send_json_success($terms);
		}

		wp_die();
	}

	public static function ssr_collect_editor_block_settings($element, $skip_render)
	{
		self::$current_block_settings = apply_filters('gutenbricks/element/' . $element['name'] . '/block_settings', self::$current_block_settings, $element, $skip_render);
	}

	public function filter__set_root_attributes($html_attr, $element)
	{
		if (is_object($element)) {
			$element = (array) $element;
		}

		// if the template is disabled for editing, we don't inject the editing attributes at all
		if (Render_Context::$current_template_settings['_gb_disable_template_edit'] ?? false === true) {
			return $html_attr;
		}

		$html_attr = apply_filters('gutenbricks/element/' . $element['name'] . '/set_root_attributes', $html_attr ?? array(), $element);

		return $html_attr;
	}

	// NOTE: other than the filter__set_root_attributes(), we need this to modify attributes
	// such as lightbox
	public function filter__render_attributes($html_attr, $key, $element)
	{
		if (is_object($element)) {
			$element = (array) $element;
		}

		// if the template is disabled for editing, we don't inject the editing attributes at all
		if (Render_Context::$current_template_settings['_gb_disable_template_edit'] ?? false === true) {
			return $html_attr;
		}

		// IMPORTANT: it's important to use _root here
		$html_attr['_root'] = apply_filters('gutenbricks/element/' . $element['name'] . '/render_attributes', $html_attr['_root'] ?? array(), $key, $element);

		return $html_attr;
	}




	public function admin_head()
	{
		if (\Gutenbricks\Integration::is_active('acss') !== true) {
			?>
			<style id="gutenbricks-editor-font-size-fixer">
				.is-root-container .gbrx-edit-block p,
				.is-root-container .gbrx-edit-block span {
					font-size: inherit;
				}
			</style><?php
		}

	}


	public function admin_footer()
	{
		if (!gutenbricks_if_gutenberg_editor()) {
			return;
		}

		$footer_html = get_option('_gutenbricks_gutenberg_footer_html');
		if (!empty($footer_html)) {
			echo $footer_html;
		}
	}


	public static function enqueue_gutenberg_editor_scripts()
	{
		// STEP: Loading editor js		
		if (gutenbricks_if_gutenberg_editor() !== true) {
			return;
		}

		Bricks_Bridge::enqueue_bricks_frontend_scripts_from_gb();

		\GutenBricks\Kucrut\Vite\enqueue_asset(
			GUTENBRICKS_PLUGIN_DIR . 'admin/dist',
			'admin/editor.jsx',
			array(
				'handle' => GUTENBRICKS_PLUGIN_NAME,
				'dependencies' => array('jquery', 'wp-blocks', 'wp-i18n', 'wp-element'),
				'version' => GUTENBRICKS_VERSION,
			)
		);

		wp_localize_script(GUTENBRICKS_PLUGIN_NAME, 'gbAjaxObject', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'adminurl' => admin_url(),
			'nonce' => wp_create_nonce(GUTENBRICKS_NONCE)
		));

		$lang = gutenbricks_get_current_locale();

		$query_params = array(
			'action' => 'serve_gutenberg_editor_js',
			'_wpnonce' => wp_create_nonce('serve_gutenberg_editor_js'),
			'gutenbricks-post-id' => get_the_ID(),
			'_locale' => $lang,
		);

		// Loading main editor js
		wp_enqueue_script(
			'serve_gutenberg_editor_js',
			add_query_arg($query_params, admin_url('admin-ajax.php')),
			array(
				GUTENBRICKS_PLUGIN_NAME,
			),
			GUTENBRICKS_VERSION
		);

		// load Bricks builder core scripts
		// @since RC-3.6.1
		Bricks_Bridge::enqueue_bricks_editor_scripts();
	}

	private function get_block_title($template)
	{
		$template_title = '';
		$custom_template_title = get_post_meta($template->ID, '_gutenbricks_block_custom_title', true);
		if ($custom_template_title) {
			$template_title = addslashes($custom_template_title);
		} else {
			$template_title = addslashes($template->post_title);
		}
		return $template_title;
	}

	// this is used by other plugins to get all the GutenBricks blocks
	public function serve_admin_block_js()
	{
		header('Content-Type: application/javascript');
		// PerformanceMonitor::start('gutenbricks/admin/register_gutenberg_blocks');

		$this->block_registry->register_for_admin();

		$templates = $this->block_registry->get_templates();

		$active_bundles = $this->block_registry->get_active_bundles();

		foreach ($templates as $template) {
			$template_id = $template->ID;

			$template_settings = Render_Context::get_template_settings($template_id);

			$post_terms = $this->block_registry->get_template_post_term($template_id);

			$innerblock_parents = $template_settings['_gb_innerblock_parent'] ?? null;

			foreach ($post_terms as $term) {
				$category_str = '';

				// STEP: hide bundles that are not active using [] category
				if (in_array($term->slug, $active_bundles)) {
					$category_str = $term->slug;
				} else {
					$category_str = 'gutenbricks';
				}

				$name = addslashes(gutenbricks_namespaced_name($term->slug, $template->post_name));

				// STEP get the template title. post_title vs _gutenbricks_block_custom_title
				$template_title = $this->get_block_title($template);

				echo "\ngutenBricksClient.registerBlockType({\n" .
					"	title: '$template_title',\n" .
					"	slug: '$category_str',\n" .
					"	name: '$name',\n" .
					"	parent: " . wp_json_encode($innerblock_parents) . ",\n" .
					"	templateSettings: " . wp_json_encode($template_settings) . ",\n" .
					"});\n";
			}
		}

		echo "setupGutenBricksBlocks();\n";

		// PerformanceMonitor::end('gutenbricks/admin/register_gutenberg_blocks');
	}


	/**
	 * @since 1.0.0-rc.2
	 * This method serves JavaScript for registering Blocks for Gutenberg editor
	 * 
	 * It only loads page-wide, global scripts and styles.
	 * 
	 * The template-wide scripts and styles are loaded via APIs
	 * 
	 * Code breakdown at DOC# 3. Do not refactor. Keep it this way. 
	 */
	public function serve_gutenberg_editor_js()
	{
		// Disable all PHP errors and warnings
		error_reporting(0);
		ini_set('display_errors', 0);

		header('Content-Type: application/javascript');

		$this->block_registry->register_for_admin();
		$this->block_registry->load_templates();

		// Verify nonce
		check_ajax_referer('serve_gutenberg_editor_js', '_wpnonce');

		// Check if user can access this
		if (!current_user_can('edit_posts')) {
			wp_die('Unauthorized', 'Unauthorized', array('response' => 401));
		}

		$post_id = esc_html($_GET['gutenbricks-post-id'] ?? '');

		echo "// post_id: $post_id \n";
		echo "function initGutenBricksEditor() {\n";

		// PerformanceMonitor::start('serve_gutenberg_editor_js');

		Bricks_Bridge::setup_dynamic_data_provider_ssr();

		$integration = \Gutenbricks_Core::$integration;
		$render_context = \Gutenbricks_Core::$render_context;
		$new_generated_assets = $integration->generate_integration_assets();

		if (is_array($new_generated_assets) && count($new_generated_assets) > 0) {
			echo "gutenBricksClient.notice('GutenBricks has optimized Add-on assets for Gutenberg Editor: " . implode(',', $new_generated_assets) . "', 'success');\n";
		}

		$inline_css = '';

		if (!Bricks_Bridge::bricks_exists()) {
			exit;
		}

		$templates = $this->block_registry->get_templates();
		$active_bundles = $this->block_registry->get_active_bundles();

		// STEP: apply dynamic data to inject into blocks and generate inline css
		// PerformanceMonitor::start('gutenbricks/editor/process_templtates_loop');

		foreach ($templates as $template) {
			$template_id = $template->ID;

			$post_terms = $this->block_registry->get_template_post_term($template_id, $post_id);

			// this means template is not enabled;
			if (empty($post_terms)) {
				continue;
			}

			$template_settings = Render_Context::get_template_settings($template_id);

			$innerblock_parents = $template_settings['_gb_innerblock_parent'] ?? null;

			foreach ($post_terms as $term) {
				$category_str = '';

				// STEP: hide bundles that are not active using [] category
				if (in_array($term->slug, $active_bundles)) {
					$category_str = $term->slug;
				} else {
					$category_str = 'gutenbricks';
				}

				$name = addslashes(gutenbricks_namespaced_name($term->slug, $template->post_name));

				// STEP get the template title. post_title vs _gutenbricks_block_custom_title
				$template_title = $this->get_block_title($template);

				echo "\ngutenBricksClient.registerBlockType({\n" .
					"	title: '$template_title',\n" .
					"	slug: '$category_str',\n" .
					"	name: '$name',\n" .
					"	parent: " . wp_json_encode($innerblock_parents) . ",\n" .
					"	templateSettings: " . wp_json_encode($template_settings) . ",\n" .
					"});\n";
			}

			$elements = Bricks_Bridge::get_elements($template_id, $render_context);
			$gbmeta_fields = $this->get_gbmeta_fields($template_settings, $post_id);
			// $native_fields = $this->get_variant_options($elements, $native_fields, $template_settings);

			// we use the same editing html attributes to generate Gutenberg sidebar controls
			$style_fields = array();

			if (!is_array($elements)) {
				continue;
			}

			foreach ($elements as $element) {
				$gb_id = Render_Context::get_gb_id($element);

				// STEP: set style fields with default value
				if (!empty($element['settings']['_gb_elem_fields'])) {
					$element['settings'] = Injector::prepareStyleFields($element, $gb_id);

					// @since 1.1.0
					$style_fields[$element['id']] = $element['settings']['_gb_elem_fields'];
				}

				// STEP: enqueue scripts and styles for the specific element
				// @since 5.5.0 loading icons correctly
				Bricks_Bridge::enqueue_setting_specific_scripts($element['settings']);
			}

			$has_block_doc = empty(get_post_meta($template_id, '_gutenbricks_block_documentation', true)) ? 'false' : 'true';
			foreach ($post_terms as $term) {
				// optional GutenBricks Specific 
				echo "\n" .
					"gutenBricksClient.updateBlockType('$name', {\n" .
					"	gbVersion: '" . GUTENBRICKS_VERSION . "',\n" .
					"	templateUrl: `" . get_permalink($template->ID) . "`,\n" .
					"	description: `" . addslashes(get_post_meta($template_id, '_gutenbricks_block_description', true)) . "`,\n" .
					"	hasDocumentation: $has_block_doc,\n" .
					"	gbMetaFields: " . wp_json_encode($gbmeta_fields) . ",\n" .
					"	styleFields: " . wp_json_encode($style_fields) . ",\n" .
					"	previewThumbnailUrl: '" . self::get_template_preview_image($template_id, 'large') . "',\n" .
					"	templateId: " . $template->ID . ",\n" .
					"});\n" .
					"	";
			}
		}

		// PerformanceMonitor::end('gutenbricks/editor/process_templtates_loop');
		// END: get all the blocks inside the post / page


		// STEP 
		// Passing options to frontend
		// we use name space instead of single object to avoid single point of failure
		echo "\ngutenBricksClient.setOptions({\n" .
			"	enableHiddenValues: " . (get_option('_gutenbricks_enable_hidden_values') === '1' ? 'true' : 'false') . ",\n" .
			"	dateFormat: '" . get_option('date_format') . "',\n" .
			"	timeFormat: '" . get_option('time_format') . "',\n" .
			"	removeOtherBlocks: " . (get_option('_gutenbricks_hide_other_blocks') === '1' ? 'true' : 'false') . ",\n" .
			"	acfSettingsName: '" . get_option('_gutenbricks_acf_settings_name') . "',\n" .
			"	mbSettingsName: '" . get_option('_gutenbricks_mb_settings_name') . "',\n" .
			"	styleTargets: " . wp_json_encode(array(
				'color' => Style_Editor::$color_targets,
				'numeral' => Style_Editor::$numeral_targets,
				'image' => Style_Editor::$image_targets,
				'other' => Style_Editor::$other_targets,
			)) . ",\n" .
			"});\n";



		// CORE:
		// --------------------
		// This is where we register options for all the integrations
		// This includes ACF, Meta Box, etc.
		echo apply_filters('gutenbricks/editor/integration/add_options', '', $templates);
		// --------------------




		// Set the capabilities: START
		$capabilities = Bricks_Bridge::get_capabilities();
		if (!$capabilities) {
			?>
			gutenBricksClient.addOption({
			postId: '<?php echo $post_id; ?>',
			userCanUseBuilder: false,
			})
			<?php
		} else {
			?>
			gutenBricksClient.addOption({
			postId: '<?php echo $post_id; ?>',
			userCanUseBuilder: <?php echo ($capabilities->current_user_can_use_builder($post_id) ? 'true' : 'false'); ?>,
			});
			<?php
		}
		// :END

		// STEP: process inline css
		// ORDER 1
		$inline_css = Bricks_Bridge::get_bricks_inline_styles($post_id);

		// @since RC3.6.2 not going to scope bricks css here
		// we are scoping them during the build
		$inline_css = Utilities\CSSScoper::scope_css($inline_css);

		// STEP END: process inline css

		// ORDER 2
		// BREAKING: Without it being here, it won't load google font properly in editor
		// We must make sure not to load Bricks related css outside of the editor.
		$styles_to_load = Render_Context::get_enqueued_styles(array());
		$styles_to_load = apply_filters('gutenbricks/editor/integration/enqueue_styles', $styles_to_load);

		$enqueued_styles = wp_json_encode($styles_to_load);

		// Collecting page-wide enqueued scripts and styles
		$enqueued_scripts = wp_json_encode(Render_Context::get_enqueued_scripts());


		// ORDER 3
		if (GUTENBRICKS_MODE === 'dev') {
			echo "console.log('[GutenBricks] Enqueued Scripts for Editor: ', $enqueued_scripts);";
			echo "console.log('[GutenBricks] Enqueued Styles for Editor: ', $enqueued_styles);";
		}

		// Load inline styles, enqueued styles and javascript from the frontend after blocks are loaded
		// because gutenberg blocks are loaded using javascript
		echo "gutenBricksClient.embedEnqueuedStyles($enqueued_styles);\n";
		echo "gutenBricksClient.embedEnqueuedScripts($enqueued_scripts);\n";
		$this->echo_embed_inline_css('gutenbricks-editor-style', $inline_css);


		// STEP: hide tabs 
		$hide_block_button_css = '';

		if (get_option('_gutenbricks_hide_block_tab') === '1') {
			$hide_block_button_css .= 'button[id$="-blocks"] { display: none !important; } \n';
		}

		if (get_option('_gutenbricks_hide_pattern_tab') === '1') {
			$hide_block_button_css .= 'button[id$="-patterns"] { display: none !important; } \n';
		}

		if (get_option('_gutenbricks_hide_media_tab') === '1') {
			$hide_block_button_css .= 'button[id$="-media"] { display: none !important; } \n';
		}

		$this->echo_embed_inline_css('gutenbricks-hidden-button-style', $hide_block_button_css);

		// @since 1.0.8
		$gutenberg_custom_css = get_option('_gutenbricks_gutenberg_custom_css');
		if (!empty($gutenberg_custom_css)) {
			$this->echo_embed_inline_css('gutenbricks-gutenberg-custom-css', $gutenberg_custom_css);
		}

		echo "gutenBricksClient.loadDefaultBricksAssets();\n";

		// CORE:
		echo "setupGutenBricksBlocks();\n";

		echo "}\n"; // END: initGutenBricksEditor

		echo "jQuery(document).ready(function($) { initGutenBricksEditor(); });\n";

		// PerformanceMonitor::end('serve_gutenberg_editor_js');

		?> console.log(<?php echo json_encode(PerformanceMonitor::getReport()); ?>);
		<?php

		exit;
	}

	private function echo_embed_inline_css($name, $css)
	{
		// handle an octal escape sequence such as \201E and all the other octal escape sequences
		$css = preg_replace('/\\\\([0-7]{1,3})/', '\\\\x$1', $css);

		echo "gutenBricksClient.embedInlineCss('$name', `$css`);\n";
	}
}

