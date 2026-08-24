<?php
namespace Gutenbricks;

class Block_Registry
{
	private $active_bundles = array();

	private $active_wrap_patterns = array();

	private $bundle_post_types = array();

	private $templates = array();

	private $template_bundles = array();

	private $render_context;

	public $registered_blocks = array();

	public function __construct($render_context)
	{
		$this->render_context = $render_context;

		$this->active_bundles = get_option('_gutenbricks_active_bundles');
		if (empty($this->active_bundles)) {
			$this->active_bundles = array();
		}

		$this->active_wrap_patterns = get_option('_gutenbricks_active_wrap_patterns');
		if (empty($this->active_wrap_patterns)) {
			$this->active_wrap_patterns = array();
		}

		$bundle_post_types_json = get_option('_gutenbricks_bundle_post_types');
		if (!empty($bundle_post_types_json)) {
			$this->bundle_post_types = json_decode($bundle_post_types_json, true);
		}
	}

	/**
	 * Retrieves the template post terms that are applicable for the given template and post.
	 *
	 * @param int $template_id The ID of the template to check.
	 * @param int|null $post_id Optional. The ID of the post to check against.
	 * @return array An array of term objects that are applicable for the given template and post.
	 */
	public function get_template_post_term($template_id, $post_id = null)
	{
		// @since RC5.3.0: load bundles per post type
		if (!empty($post_id)) {
			$current_post_type = get_post_type($post_id);
		} else {
			$current_post_type = null;
		}

		$bundle_post_types = $this->bundle_post_types;
		$active_bundles = $this->active_bundles;

		// STEP: Register per template per bundle
		$post_terms = wp_get_post_terms($template_id, GUTENBRICKS_DB_TEMPLATE_TAX_BUNDLE);

		// IMPLICIT: if $post_terms is empty array it implies that the block belongs to default
		if (empty($post_terms)) {
			// if user toggled default, we will artificially create a category
			if (in_array('default', $active_bundles)) {
				$post_terms = array((object) array('slug' => 'default'));
			}
		}

		// if current post type is null and post id is null
		// we are returning everything
		if (empty($current_post_type) && empty($post_id)) {
			return $post_terms;
		}

		// @since RC5.3.0 check if the template's bunlde(term) is included in bundle_post_types
		$included = array();

		foreach ($post_terms as $term) {
			// @since RC5.3.0: load bundles per post type
			if (
				empty($bundle_post_types[$term->slug])
				|| in_array($current_post_type, $bundle_post_types[$term->slug])
			) { // if it's empty, we include it because it's default
				$included[] = $term;
			}
		}

		return $included;
	}

	public function get_active_bundles()
	{
		return $this->active_bundles;
	}

	public function get_bundle_post_types()
	{
		return $this->bundle_post_types;
	}

	public function get_registered_blocks()
	{
		return $this->registered_blocks;
	}

	public function get_templates()
	{
		return $this->templates;
	}

	public function register_for_admin()
	{
		// BREAKING: Order is important
		// PerformanceMonitor::start('gutenbricks/load_blocks/register_for_admin');
		$this->load_bundles();
		$this->register_post_meta();
		// PerformanceMonitor::end('gutenbricks/load_blocks/register_for_admin');
	}

	public function load_templates($template_id = null)
	{

		// PerformanceMonitor::start('gutenbricks/load_templates');

		// we don't want to load the same templates twice
		if (!empty($this->templates)) {
			return;
		}

		$args = array(
			'taxonomy' => GUTENBRICKS_DB_TEMPLATE_TAX_BUNDLE,
			'hide_empty' => false,
		);

		$terms = get_terms($args);

		// Extract slugs from all terms
		$all_term_slugs = wp_list_pluck($terms, 'slug');

		// Determine terms to exclude by removing selected slugs from all term slugs
		$exclude_slugs = array_diff($all_term_slugs, array_merge($this->active_bundles, $this->active_wrap_patterns));

		$args = array(
			'post_type' => GUTENBRICKS_DB_TEMPLATE_SLUG,
			'post_status' => 'publish',
			'posts_per_page' => -1, // Retrieve all posts
			// 'lang' => '', // @since RC-4.5.3: Polylang integration, commented out since @1.1.7 moved to Polylang Integrator
			'tax_query' => array(
				'relation' => 'AND',
				array(
					'taxonomy' => GUTENBRICKS_DB_TEMPLATE_TAX_BUNDLE, // Your taxonomy name
					'field' => 'slug',
					// NOTE:
					// in the $exclude_slug there will be "default" but in template_type meta
					// there is no such value as "default" which means post with empty slug
					// will still be loaded. We will have exclude it when we load the items 
					'terms' => $exclude_slugs,
					'operator' => 'NOT IN',
				),
			),
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => GUTENBRICKS_DB_TEMPLATE_TYPE,
					'value' => array(GUTENBRICKS_TEMPLATE_TYPE_BLOCK),
					'compare' => 'IN',
				)
			),
		);

		if (!empty($template_id)) {
			$args['p'] = $template_id;
		}

		// Undocumented
		$args = apply_filters('gutenbricks/load_templates/query_args', $args);

		$the_query = new \WP_Query($args);

		foreach ($the_query->posts as $post) {
			$this->templates[$post->ID] = $post;
		}

		// PerformanceMonitor::end('gutenbricks/load_templates');
	}

	private function load_bundles()
	{
		// PerformanceMonitor::start('gutenbricks/load_bundles');
		$taxonomies = get_object_taxonomies(GUTENBRICKS_DB_TEMPLATE_SLUG);

		if (in_array(GUTENBRICKS_DB_TEMPLATE_TAX_BUNDLE, $taxonomies)) {
			// Fetch all terms from the 'template_bundles' taxonomy
			$terms = get_terms(
				array(
					'taxonomy' => GUTENBRICKS_DB_TEMPLATE_TAX_BUNDLE,
					'hide_empty' => false, // Set true if you want to hide empty terms
					'field' => 'slug',
					'terms' => $this->active_bundles, // Filter by these term slugs
				)
			);

			// exclude default if not toggled

			if (in_array('default', $this->active_bundles)) {
				$this->template_bundles = array(
					array(
						'slug' => 'default',
						'title' => $this->get_default_bundle_name(),
					)
				);
			}

			// Use $terms as needed
			foreach ($terms as $term) {
				$this->template_bundles[] = array(
					'slug' => $term->slug,
					'title' => $term->name,
				);
			}
		}
		// PerformanceMonitor::end('gutenbricks/load_bundles');
	}

	public function register_blocks($template_id = null)
	{
		// PerformanceMonitor::start('gutenbricks/register_blocks');

		// @since 1.1.20 if $template_id is set, we load only the templates for the given template id
		// otherwise we load all the templates
		$this->load_templates();
	
		if (empty($this->templates)) {
			// PerformanceMonitor::end('gutenbricks/register_blocks', [$template_id]);
			return;
		}

		// we don't want to register the same block twice
		if (!empty($this->registered_blocks)) {
			// PerformanceMonitor::end('gutenbricks/register_blocks', [$template_id]);
			return;
		}

		$registered_pattern_categories = array();

		foreach ($this->templates as $template) {
			// PerformanceMonitor::start('gutenbricks/register_blocks/load_one_block');
			$post_terms = wp_get_post_terms($template->ID, GUTENBRICKS_DB_TEMPLATE_TAX_BUNDLE);

			$elements_attr = $this->get_elements_attributes($template);

			// IMPLICIT: if $post_terms is empty array it implies that the block belongs to default
			if (empty($post_terms)) {
				$post_terms = array(
					array(
						'slug' => 'default', 
						'name' => $this->get_default_bundle_name()
					)
				);
			}

			foreach ($post_terms as $term) {

				// @since 1.1.21: ensure $term is an object
				if (is_object($term)) {
					$slug = $term->slug;
					$name = $term->name;
				} else if (is_array($term)) {
					$slug = $term['slug'] ?? 'default';
					$name = $term['name'] ?? $this->get_default_bundle_name();
				}

				
				$attributes = array_merge(
					array(
						'align' => array( // force default align to be full
							'type' => 'string',
							'default' => 'full'
						),
						'content' => array(
							'type' => 'object',
						),
						'template_id' => array(
							'type' => 'number',
							'default' => $template->ID,
						),
						'template_saved_at' => array(
							'type' => 'number',
							'default' => get_post_time('U', true, $template->ID),
						),
						'is_example' => array(
							'type' => 'boolean',
							'default' => false
						),
						'block_id' => array(
							'type' => 'string',
						),
						'gb_block_dom_id' => array(
							'type' => 'string',
						),
						// The GutenBricks version where the block was first inserted in the editor 
						'_gb_ver_at_insert' => array(
							'type' => 'string',
						),
						// When the block was last open and saved on editor
						'_gb_ver_at_save' => array(
							'type' => 'string',
						),
						'_gutenbricks_meta_data' => array(
							'type' => 'object',
							'default' => $this->get_default_dynamic_values($template->ID),
						),
						'_gutenbricks' => array( // DO NOT DELETE: legacy support 
							'type' => 'string',
							'default' => $this->get_default_dynamic_values($template->ID, true),
						),

						/*
						 * @since 1.0.0-rc.2.15
						 * Register className attribute when coreframework is active. Reported by Jan.
						 */
						'className' => array(
							'type' => 'string',
						),

						// @since 1.1.0
						// temporary value only used for rendering
						// To render InnerHTML in save() on the first render
						'_gb_rendering_temp' => array(
							'type' => 'object',
						),

						/*
						 * @since rc-4.1.0
						 */
						'_gb_current_variant' => array(
							'type' => 'string',
						),

						// solving thirdparty plugin conflict
						// @since 1.0.0-rc.3.7.3
						'simpleShopSpecificDateFrom' => array(
							'type' => 'string',
						),
					),
					$elements_attr
				);

				$block_name = gutenbricks_namespaced_name($slug, $template->post_name);

				$description = get_post_meta($template->ID, '_gutenbricks_block_description', true);

				if (!in_array($slug, $registered_pattern_categories)) {
					register_block_pattern_category(
						$slug,
						array(
							'label' => $name,
						)
					);
					$registered_pattern_categories[] = $slug;
				}

				// if the term->slug is in active_wrap_patterns, we register it as a pattern				
				if (in_array($slug, $this->active_wrap_patterns)) {
					register_block_pattern(
						$block_name,
						array(
							'title' => $template->post_name,
							'categories' => [$slug],
							'description' => $description,
							'attributes' => array(
								'template_id' => array(
									'type' => 'number',
									'default' => $template->ID,
								),
							),
							'content' => $this->get_block_as_pattern($block_name, $template),
						)
					);
				}

				$this->registered_blocks[$block_name] = $slug . ': ' . $template->post_title;

				register_block_type(
					$block_name,
					array(
						'title' => $template->post_title,
						'description' => $description,
						'render_callback' => array($this->render_context, 'render_block'),
						'attributes' => $attributes,
					)
				);
			}
			// PerformanceMonitor::end('gutenbricks/register_blocks/load_one_block');
		}
		
		// PerformanceMonitor::end('gutenbricks/register_blocks', [$template_id]);
	}

	private function get_default_dynamic_values($template_id, $as_string = false)
	{
		// STEP: set the default value for _gb_meta_fields
		$settings = get_post_meta($template_id, GUTENBRICKS_DB_TEMPLATE_SETTINGS, true);
		$default_values = array();

		if (!empty($settings['_gb_meta_fields'])) {
			foreach ($settings['_gb_meta_fields'] as $field) {
				$default_values[$field['name']] = self::get_gb_meta_field_default_value($field);
			}
		}

		if ($as_string) {
			if (!empty($default_values)) {
				return wp_json_encode($default_values);
			}
			return '';
		}

		return $default_values;

	}

	public static function get_gb_meta_field_default_value($field)
	{
		switch ($field['type'] ?? 'text') {
			case 'text':
			case 'color':
			case 'color_swatch':
			case 'textarea':
				return $field['default_value'] ?? null;
			case 'radio':
			case 'select':
				return $field['default_value_choice'] ?? null;
			case 'number':
				return $field['default_value_number'] ?? null;
			case 'true_false':
				if (isset($field['default_value_boolean']) && is_bool($field['default_value_boolean'])) {
					if ($field['default_value_boolean']) {
						return $field['true_value'] ?? true;
					} else {
						return $field['false_value'] ?? false;
					}
				}
				return $field['false_value'] ?? null;
			case 'image':
				return $field['default_value_image'] ?? null;
			default:
				return null;
		}
	}

	// Where you register Bricks element as attributes for your Gurenberg blocks
	// If not registered, when update attributes from frontend, it will return 400 forbidden
	// STEP 1: register 
	private function get_elements_attributes($template)
	{
		$elements_attr = array();

		$elements = Bricks_Bridge::get_elements($template->ID, $this->render_context, true);

		if (empty($elements)) {
			return $elements_attr;
		}

		foreach ($elements as $element) {
			if ($element['settings']['_gb_disable_rendering'] ?? false === true) {
				continue;
			}

			$gb_id = Render_Context::get_gb_id($element);
			$attributes_settings = apply_filters('gutenbricks/element/' . $element['name'] . '/attributes_settings', array(), $element);

			if (!empty($attributes_settings)) {
				$elements_attr[$gb_id] = $attributes_settings;
			}
		}

		foreach ($elements as $element) {
			$element_id = Render_Context::get_gb_id($element);

			if ($element['settings']['_gb_disable_rendering'] ?? false === true) {
				continue;
			}

			// Legacy
			// TODO: move the rest of the code to element.php
			switch ($element['name']) {
				case 'icon-box':
					$elements_attr[$element_id] = array(
						'type' => 'object',
						'default' => array(
							"content" => $element['settings']['content'] ?? null,
						),
					);
					break;
				case 'testimonials':
					$elements_attr[$element_id] = array(
						'type' => 'object',
						'default' => array(
							"items" => $element['settings']['items'] ?? null,
						),
					);
					break;
				case 'form':
				case 'tabs':
				case 'carousel':
					$elements_attr[$element_id] = array(
						'type' => 'object',
					);
					break;
			}

			// STEP: link
			// STEP: For elements wrapped in a link
			if (!empty($element['settings']['link'])) {
				if (!isset($elements_attr[$element_id])) {
					$elements_attr[$element_id] = array(
						'type' => 'object',
						'default' => array(),
					);
				}
				$elements_attr[$element_id]['default']['link'] = $element['settings']['link'];
			}

			// STEP: visibility toggle
			if (
				isset($element['settings']['_gb_enable_show_hide']) &&
				$element['settings']['_gb_enable_show_hide'] === true &&
				isset($elements_attr[$element_id])
			) {
				if (!isset($elements_attr[$element_id]['default'])) {
					$elements_attr[$element_id]['default'] = array();
				}

				$elements_attr[$element_id]['default']['_gb_show_element'] = $element['settings']['_gb_show_element'] ?? $element['settings']['_gb_show_hide_default'] ?? false;
			}
		}

		$elements_attr = apply_filters('gutenbricks/block/attributes_settings', $elements_attr, $element, $template);

		return $elements_attr;
	}


	private function get_default_bundle_name()
	{
		$default_bundle_name = get_option('_gutenbricks_default_bundle_name');

		if (empty($default_bundle_name)) {
			$default_bundle_name = 'Default';
		}

		return $default_bundle_name;
	}

	public function filter__register_categories($categories = array())
	{
		return array_merge(
			$this->template_bundles,
			$categories,
			// Add an empty category in gutenberg
			array(
				array(
					'slug' => 'gutenbricks',
					'title' => '         ',
				),
			),
		);
	}

	private function register_post_meta()
	{
		register_post_meta(
			GUTENBRICKS_DB_TEMPLATE_SLUG,
			'_gutenbricks_block_description',
			array(
				'show_in_rest' => true,
				'single' => true,
				'type' => 'string',
			)
		);

		register_post_meta(
			GUTENBRICKS_DB_TEMPLATE_SLUG,
			'_gutenbricks_block_documentation',
			array(
				'show_in_rest' => true,
				'single' => true,
				'type' => 'string',
			)
		);
	}

	public function load_page_template($template)
	{
		// PerformanceMonitor::start('gutenbricks/load_page_template');

		global $post;

		if (empty($post)) {
			// PerformanceMonitor::end('gutenbricks/load_page_template');
			return $template;
		}

		// @since 1.1.17
		if (function_exists('is_search') && is_search()) {
			return $template;
		}

		$current_template_slug = get_page_template_slug($post->ID);

		if (strpos($current_template_slug, 'gutenbricks-dynamic-template-') !== false) {
			// Remove the path prefix from $current_template_slug
			// MIGRATION: This is for migration purpose for old templates
			$current_template_slug = substr($current_template_slug, strpos($current_template_slug, 'templates/gutenbricks-dynamic-template-'));
		}

		$template_path = GUTENBRICKS_PLUGIN_DIR . 'includes/';

		$template_placeholder = 'templates/gutenbricks-dynamic-template-';

		// CASE: default template
		if ($current_template_slug === "templates/gutenbricks-dynamic-template-default.php") {
			get_header();
			?>
			<main id="brx-content" class="gbrx-block-content">
				<div class="brxe-post-content" data-source="bricks">
					<?php
					the_content();
					?>
				</div>
			</main>
			<?php
			get_footer();

			return $template_path . $current_template_slug;
		}

		// CASE: dynamic template
		if (strpos($current_template_slug, 'templates/gutenbricks-dynamic-template-') !== false) {
			// extract template id from template name
			$template_id = str_replace($template_placeholder, '', $current_template_slug);
			$template_id = str_replace('.php', '', $template_id);

			$template_id = apply_filters('gutenbricks/load_template/template_id', $template_id);

			// STEP: get the content html first and then render the header later
			get_header();

			$html = do_shortcode('[bricks_template id="' . $template_id . '"]');

			$html = str_replace('data-source="editor"', 'data-source="bricks"', $html);

			// @since 1.1.6
			// use gbrx-block-content to nullify brxe-post-content CSS
			echo '<main id="brx-content" class="gbrx-block-content">';
			echo $html;
			echo '</main>';

			get_footer();

			return $template_path . $template_placeholder . 'empty.php';
		}

		// PerformanceMonitor::end('gutenbricks/load_page_template');
		return $template;
	}

	public function register_template($templates)
	{

		$args = array(
			'post_type' => GUTENBRICKS_DB_TEMPLATE_SLUG,
			'posts_per_page' => -1,
			// 'lang' => '', // commented out since @1.1.7 moved to Polylang Integrator
			'meta_query' => [
				[
					'key' => GUTENBRICKS_DB_TEMPLATE_TYPE,
					'value' => GUTENBRICKS_TEMPLATE_TYPE_BLOCK_PAGE,
				],
			],
			'post_status' => 'publish',
		);

		$args = apply_filters('gutenbricks/register_templates/query_args', $args, $templates);

		$the_query = new \WP_Query($args);

		$post_id = get_the_ID();
		// Get the current page template
		$current_template_id = get_page_template_slug($post_id);

		// Insert
		foreach ($the_query->posts as $post) {
			$template_id = "templates/gutenbricks-dynamic-template-{$post->ID}.php";

			if (!empty($current_template_id) && strpos($current_template_id, $template_id) !== false) {
				$templates[$current_template_id] = $post->post_title;
			} else {
				$templates[$template_id] = $post->post_title;
			}
		}

		// GutenBricks default template
		$default_template_name = get_option('_gutenbricks_default_page_template_name');
		if (empty($default_template_name)) {
			$default_template_name = GUTENBRICKS_DEFAULT_PAGE_TEMPLATE_NAME;
		}

		$default_template_id = 'templates/gutenbricks-dynamic-template-default.php';

		if (!empty($current_template_id) && strpos($current_template_id, $default_template_id) !== false) {
			$templates[$current_template_id] = $default_template_name;
		} else {
			$templates[$default_template_id] = $default_template_name;
		}

		return $templates;
	}

	// wrap the block directly inside pattern
	// so that we can match template id and load correct CSS and JS for it
	private function get_block_as_pattern($block_name, $template)
	{
		return '<!-- wp:' . $block_name . ' { "template_id": ' . $template->ID . '} --><!-- /wp:' . $block_name . ' -->';
	}

}