<?php
namespace Gutenbricks;

class Render_Context
{
	private $rendered_template_ids = array();

	public static $rendered_elements = array();

	public static $current_gutenberg_attributes;

	private static $current_fields = array();

	public static $current_element;

	public static $current_elements;

	public static $current_template_id;

	public static $current_template_settings;

	private static $extracted_css_cached = array();

	private $current_custom_classes;

	private static $current_custom_ids = array();

	public $element_index;

	public static $current_block_content = '';

	public static $current_block_global_class_css = '';

	private $integration;

	private $generated_template_inline_css = array();

	private $elements_style_processed = array();

	private $block_data_processed = array();

	private static $is_ssr_request = false;

	public static $current_page_styles = [];

	private static $block_in_styling = null;

	private static $global_classes = array();


	public function __construct($integration)
	{
		self::$is_ssr_request = gutenbricks_is_ssr_request();
		$this->integration = $integration;
	}

	public function get_integration()
	{
		return $this->integration;
	}

	// CORE: this is where rendering Gutenberg blocks happens
	// also the inline CSS and @import style sheets are loaded
	// this is called from Block_Registry 
	public function render_block($attr, $content, $block)
	{
		$template_id = $attr['template_id'];

		// PerformanceMonitor::start('wp/render_block', array('template_id' => $template_id));

		$this->renew_rendering_cycle($attr);

		if (self::$is_ssr_request) {
			$this->prepare_block_for_ssr();
		}

		self::$current_block_content = $content;

		$rendered = '';

		$post_action = $_POST['action'] ?? null;

		// @since 1.1.16 moved from process_elements_style() to here
		if (class_exists('\Bricks\Database') && isset(\Bricks\Database::$global_data['globalClasses'])) {
			self::$global_classes = \Bricks\Database::$global_data['globalClasses'];
		} else {
			self::$global_classes = array();
		}

		// @since 1.0.6
		// HACK: using $post_action !== 'editpost' to prevent rendering do_shortcode()
		// because...
		// when editor loads the block, we don't render the template
		// this method is called by new \WP_Query($args) within load_templates()
		// which triggers get_tag_value() that runs get_tag_value() in both
		// Bricks\Provider and BricksExtrasProviders. However $this->providers['wp']
		// in those instances are not set, throwing critical error
		// TODO: better solution is to set $this->providers['wp'] in Gutenbricks
		// and process {cf...} tags before BricksExtrasProvider
		if (!gutenbricks_if_gutenberg_editor() && $post_action !== 'editpost') {
			add_filter('get_post_metadata', array($this, 'inject_post_metadata'), 90, 4);

			// PerformanceMonitor::start('bricks/do_shortcode');
			// render the template
			$rendered = do_shortcode("[bricks_template id=" . $template_id . "]");
			// PerformanceMonitor::end('bricks/do_shortcode');

			remove_filter('get_post_metadata', array($this, 'inject_post_metadata'), 90);
		}

		// @since 1.1.21: 
		// START: generate inline css for looped elements
		if (class_exists('\Bricks\Templates')) {

			PerformanceMonitor::start('gutenbricks/render_block/generate_inline_css_for_looped_elements');
			foreach (self::$current_elements as $key => $element) {
				self::$current_elements[$key] = (array) $element;
			}
			
			$generated_template_css = \Bricks\Templates::generate_inline_css(
				$template_id,
				self::$current_elements
			);

			PerformanceMonitor::end('gutenbricks/render_block/generate_inline_css_for_looped_elements');
		}
		// END

		if (self::$is_ssr_request) {
			$rendered = $this->process_block_for_ssr($template_id, $block, $rendered);
		}
		// PerformanceMonitor::end('wp/render_block');

		return $rendered;
	}

	private function prepare_block_for_ssr()
	{
		// only for editor
		if (!self::$is_ssr_request) {
			return;
		}

		// STEP: Register Bricks frontend scripts to be enqueued by elements
		Bricks_Bridge::register_bricks_frontend_scripts();
	}


	private function process_block_for_ssr($template_id, $block, $rendered)
	{
		// only for editor
		if (!self::$is_ssr_request) {
			return $rendered;
		}

		$elements = Bricks_Bridge::get_elements($template_id, $this);

		do_action('gutenbricks/editor/block/pre_process', $template_id, $elements, $block);

		$inline_style = $this->get_block_style_for_ssr($template_id, $elements, $block);
		$rendered = $inline_style . $rendered;

		return $rendered;
	}


	// CORE: The magic.
	// We inject meta data into the element
	// ACF and Metabox are handled separately
	public function inject_post_metadata($null, $post_id, $meta_key, $single)
	{
		if ($meta_key === GUTENBRICKS_DB_PAGE_CONTENT) {
			$template_id = $post_id;

			remove_filter('get_post_metadata', array($this, 'inject_post_metadata'), 90);
			$elements = get_post_meta($template_id, $meta_key, $single);
			add_filter('get_post_metadata', array($this, 'inject_post_metadata'), 90, 4);

			// @since 1.1.0
			// This condition is true when there is a nested template
			// We need to return the elements as it is without processing them
			// TODO: multi-level processing. Right now, the process is a single level
			// it shouldn't be != because one can be float another can be int or string
			if (!empty(self::$current_template_id) && $template_id != self::$current_template_id) {
				return array($elements);
			}

			if (!empty($elements)) {
				return $this->process_elements($elements, $template_id);
			}
		}

		// CLASS: Gutenbricks\Dynamic_Data_Provider
		// Providing dynamic data from self::$current_gutenberg_attributes
		$possible_dynamic_data = $this->get_gutenberg_dynamic_value($meta_key);
		if (!empty($possible_dynamic_data)) {
			return array($possible_dynamic_data);
		}

		return $null;
	}

	// CORE:
	private function process_elements($elements, $template_id)
	{
		// @since RC4.2.1
		// We only process the block_id once
		// This is to prevent duplicate CSS and also endless loop
		$block_id = self::$current_gutenberg_attributes['block_id'] ?? null;

		if (empty($block_id)) {
			return array($elements);
		}

		/*
			if (!empty($this->elements_style_processed[$block_id])) {
				return array($this->elements_style_processed[$block_id]);
			}
		*/

		// if (!isset($this->elements_style_processed[$block_id])) {
		// @since 1.1.16 (Vigh's duplicated	elements)
		// change $this->elements_style_processed TO $this->elements_style_processed
		// @since 1.0.8
		// STEP: Inject Gutenberg content into the element
		// This is to make sure the content is injected before the element is processed
		// WIRED-428
		// STEP 1: inject data into the element

		if (empty($this->block_data_processed[$block_id])) {
			$elements = $this->process_elements_data($elements, $block_id);
			$this->block_data_processed[$block_id] = $elements;
		} else {
			$elements = $this->block_data_processed[$block_id];
		}

		// @since 1.1.16
		// we need to update it in every step
		$this->elements_style_processed[$block_id] = $elements;

		// @since 1.1.19
		// preventing infinite loop of query
		// 
		if (self::$block_in_styling !== $block_id) {
			self::$block_in_styling = $block_id;

			// @since 3.7.4
			// preventing duplicate CSS for the first ID
			// STEP: inject styles into elements
			$elements = $this->process_elements_style($elements, $block_id, $template_id);

			// @since 1.1.16
			// we need to update it in every step
			$this->elements_style_processed[$block_id] = $elements;
		}

		return array($elements);
	}




	private function inject_unique_id($elements, $element_ids_to_change, $block_id)
	{
		try {
			// Convert elements to JSON string
			$elements_json = json_encode($elements);
			if ($elements_json === false) {
				throw new Exception("Failed to encode elements to JSON");
			}

			// Create a regex pattern to match IDs that need to be changed
			$id_pattern = implode('|', array_map('preg_quote', $element_ids_to_change));
			$patterns = [
				'id' => '/"id":"(' . $id_pattern . ')"/',
				'parent' => '/"parent":"(' . $id_pattern . ')"/',
				'children' => '/"children":\[((?:[^][]|\[(?:[^][]|\[(?:[^][]|\[[^][]]*\])*\])*\])*)\]/'
			];

			// Replace IDs
			foreach ($patterns as $type => $pattern) {
				$new_json = preg_replace_callback($pattern, function ($matches) use ($type, $block_id, $element_ids_to_change) {
					if ($type === 'children') {
						return '"children":[' . preg_replace_callback('/"(' . implode('|', array_map('preg_quote', $element_ids_to_change)) . ')"/', function ($child_match) use ($block_id) {
							return '"' . $child_match[1] . GUTENBRICKS_UNIQUE_ID_CONNECTOR . $block_id . '"';
						}, $matches[1]) . ']';
					} else {
						$old_id = $matches[1];
						if (in_array($old_id, $element_ids_to_change)) {
							$new_id = $old_id . GUTENBRICKS_UNIQUE_ID_CONNECTOR . $block_id;
							return '"' . $type . '":"' . $new_id . '"';
						}
						return $matches[0];
					}
				}, $elements_json);

				// Check if preg_replace_callback failed
				if ($new_json === null) {
					throw new Exception("Regex replacement failed for $type");
				}

				$elements_json = $new_json;
			}

			// Convert back to array
			$elements = json_decode($elements_json, true);
			if ($elements === null) {
				throw new Exception("Failed to decode JSON back to array");
			}

		} catch (Exception $e) {
			// Log the error
			error_log("Error in inject_unique_id: " . $e->getMessage());
			// In case of any error, we return the original elements without modification
		}


		return $elements;
	}

	private function process_elements_data($elements, $block_id)
	{
		// PerformanceMonitor::start('gutenbricks/process_elements/process_elements_data');
		$is_ssr = gutenbricks_is_ssr_request();

		$to_filter_out = [];

		foreach ($elements as $key => $element) {
			$gb_id = self::get_gb_id($element);
			$element_id = $element['id'];

			$skip_render = false;

			// STEP: Check if this element or any of its ancestors should be filtered out
			// instead of filter: bricks/element/render, we filter out non-rendering elements here
			if (
				in_array($element_id, $to_filter_out)
				|| (in_array($element['parent'], $to_filter_out) && $element['parent'] !== 0)
				|| !Element_Should_Render::check(true, $element)
			) {
				// If this element shouldn't be rendered, add its children to the filter list
				if (isset($element['children']) && is_array($element['children'])) {
					$to_filter_out = array_merge($to_filter_out, $element['children']);
				}

				$skip_render = true;
			}

			// STEP 1: Collect all the elements that are rendered
			// BREAKING: this should be here to collect the block settings for the element
			// before we collect the elements that are rendered
			if (!$skip_render) {
				self::$rendered_elements[] = $element_id;
			}

			// STEP 2: 
			// FOR SSR, we collect the block settings for the element even if it's not rendered
			// BREAKING: this should be here to collect the block settings for the element
			// before we skip the element. We pass the skip_render flag to prevent collecting 
			// blocks settings for the element that is not being rendered @since 1.1.11
			if ($is_ssr) {
				Gutenberg_Editor::ssr_collect_editor_block_settings($elements[$key], $skip_render);
			}

			if ($skip_render) {
				continue;
			}

			$attr = self::$current_gutenberg_attributes[$gb_id] ?? array();
			$elements[$key]['settings'] = Injector::inject_gutenberg_content($elements[$key]['settings'], $element, $attr);

	
			// Clear up the query loop element from the history
			if (isset($elements[$key]['settings']['query']) 
				&& class_exists('\Bricks\Query')
				&& isset(\Bricks\Query::$query_history)
			) {
				$element_id = $elements[$key]['id'];
				// loop through \Bricks\Query::$query_history[$element_id]
				// to remove any keys that contain $element_id
				foreach (\Bricks\Query::$query_history as $key => $value) {
					if (strpos($key, $element_id) !== false) {
						unset(\Bricks\Query::$query_history[$key]);
					}
				}
			}
		}

		// PerformanceMonitor::end('gutenbricks/process_elements/process_elements_data');

		return $elements;
	}


	// CLASS: Gutenbricks\Dynamic_Data_Provider
	public function get_gutenberg_dynamic_value($field_id)
	{
		if (!isset(self::$current_gutenberg_attributes['_gutenbricks_meta_data'])) {
			return;
		}

		$dynamic_data = self::$current_gutenberg_attributes['_gutenbricks_meta_data'];

		if (empty($dynamic_data[$field_id])) {
			return;
		}

		return $dynamic_data[$field_id];
	}


	public static function get_gb_id($element)
	{
		if (is_object($element)) {
			$element = (array) $element;
		}

		$id = $new_id_override ?? $element['id'];

		$settings = $element['settings'] ?? array();
		if (!empty($settings['_gb_binding_name'])) {
			$gb_id = 'bind_' . $settings['_gb_binding_name'];
		} else {
			$block_id = self::$current_gutenberg_attributes['block_id'] ?? '';
			$gb_id = gutenbricks_get_gb_id($id, $block_id);
		}

		return $gb_id;
	}


	// Priority: After the element gets its unique id. So that the value can stay unique
	// CORE: HACK: 
	public function process_elements_style($elements, $block_id, $template_id)
	{

		$is_for_ssr = gutenbricks_is_ssr_request();

		// @since 1.1.15
		// templates with a filter and has settings filterQueryId
		// will not have a unique ID
		$skip_unique_id = array();

		foreach ($elements as $key => $element) {
			if (isset($element['settings']['filterQueryId'])) {
				$skip_unique_id[] = $element['settings']['filterQueryId'];
			}

			if (isset($element['settings']['hasLoop']) && $element['settings']['hasLoop'] === true) {
				$skip_unique_id[] = $element['id'];
			}

			if (isset($element['name']) && strpos($element['name'], 'filter-') === 0) {
				$skip_unique_id[] = $element['id'];
			}

			// @since 1.1.16 skip unique ID for facetWP
			// since the filter doesn't work for facetWP
			if ($elements[$key]['settings']['usingFacetWP'] ?? false == true) {
				$skip_unique_id[] = $element['id'];
			}
		}

		if (method_exists('\Bricks\Templates', 'generate_inline_css')) {
			// HACK: empty out $css_looping_elements[] so we can render again
			// otherwise it won't render within generate_inline_css_from_element()
			\Bricks\Assets::$css_looping_elements = [];

			$template_css = '';
			if (!empty($this->generated_template_inline_css[$template_id])) {
				$template_css = $this->generated_template_inline_css[$template_id];
			} else {
				// it only generate once
				// we rerun it just to generate \Bricks\Assets::$inlince_css_dynamic_data again
				// PerformanceMonitor::start('bricks/generate_inline_css');
				$generated_template_css
					= \Bricks\Templates::generate_inline_css(
						$template_id,
						$elements
					);
				// PerformanceMonitor::end('bricks/generate_inline_css');

				if (!empty($generated_template_css)) {
					$this->generated_template_inline_css[$template_id] = $generated_template_css;
				}

				$template_css = $generated_template_css;
			}

			// HACK: save it to \Gutenbricks_Public::$dynamic_inline_css 
			// otherwise it will keep accumulating
			$dynamic_css = \Bricks\Assets::$inline_css_dynamic_data;
			\Bricks\Assets::$inline_css_dynamic_data = '';
		}

		// STEP: get the existing inline dynamic styles
		// only for SSR
		$existing_css_dynamic_data = '';
		if ($is_for_ssr) {
			if (class_exists('\Bricks\Assets')) {
				if (!empty(\Bricks\Assets::$inline_css_dynamic_data)) {
					$existing_css_dynamic_data = \Bricks\Assets::$inline_css_dynamic_data ?? '';
				}
			}
		}

		$element_ids_to_change = array();

		// PerformanceMonitor::start('gutenbricks/process_elements/inject_custom_id');
		foreach ($elements as $key => $element) {
			if (isset($element['settings']['_gb_disable_rendering']) && $element['settings']['_gb_disable_rendering'] === true) {
				continue;
			}

			// BREAKING: This must be here
			$original_id = 'brxe-' . $elements[$key]['id'];

			if ($is_for_ssr) {
				Bricks_Bridge::enqueue_setting_specific_scripts($element['settings']);
			}

			if (in_array($elements[$key]['id'], $skip_unique_id)) {
				continue;
			}

			// @since 1.1.0
			// CASE: Preparing creating a unique ID for the element
			// HACK: We inject a unqiue ID to the element itself, if there is a query to work around the Bricks query cache
			// BREAKING: There will be a side effect if the element was referred by its previous ID
			// BREAKING: Unqiue ID must be generated AFTER CSS is done
			// @since 1.0.8.5 inject custom attribute to $settings['query] to work around the Bricks query cache
			// @since 1.1.11: this should be here before the hasLoop check
			if (
				isset($elements[$key]['settings']['query']) && is_array($elements[$key]['settings']['query'])
				// @since 1.1.4: carousel will have query later during render() and needs to have a unique ID 
				// DO NOT use $skip_unique_id[] = $element['id'];
				|| (!empty($elements[$key]['name']) && $elements[$key]['name'] === 'carousel')
			) {
				$elements[$key]['settings']['query']['gutenBricksBlockId'] = $block_id ?? null;
				$elements[$key]['_gbOriginalId'] = $elements[$key]['id'];
				$element_ids_to_change[] = $elements[$key]['id'];
			}

			// STEP: inject custom ID
			$new_id = null;

			// @since 1.1.14 we don't want ID when there is no CSS
			if (
				strpos($template_css, $original_id) !== false
				|| strpos($dynamic_css, $original_id) !== false
				|| gutenbricks_is_ssr_request()
				|| $elements[$key]['parent'] === 0
			) {
				$custom_id_result = $this->inject_custom_css_id($block_id, $elements[$key]);

				$new_id = $custom_id_result['new_id'];
				$elements[$key] = $custom_id_result['element'];
			}

			// we don't assign id to elements that are in loop
			// because they are not rendered
			// and they are not visible in the frontend
			// for it's children we check Query::is_looping() to skip ID changing
			if (
				$element['settings']['hasLoop'] ?? false === true
				|| (class_exists('\Bricks\Query') && \Bricks\Query::is_looping($element['id']))
			) {
				// CASE: it's in loop

				// @since 1.1.14.1
				// CSS: if the id has _{block_id} we replace new_id from 'b-...' to 'brxe-...'
				if (strpos($original_id, '_' . $block_id) === false && !empty($new_id)) {
					$new_id = str_replace('b-', 'brxe-', $new_id);
				}

				// @since 1.1.12.1 
				// CSS: if it's looping, we're dealing with classes
				if (strpos($template_css, $original_id) !== false) {
					$template_css = self::convert_css_by_id($template_css, $original_id, $new_id, '.');
				}

				if (strpos($dynamic_css, $original_id) !== false) {
					$dynamic_css = self::convert_css_by_id($dynamic_css, $original_id, $new_id, '.');
				}

				continue;
			}


			// STEP: inject style and temporaily store the value swap
			if (!empty($elements[$key]['settings']['_gb_elem_fields'])) {

				// TODO: untangle this
				// This cannot be replaced with get_gb_id()
				if (isset($elements[$key]['settings']['_gb_binding_name'])) {
					$new_id_to_use = 'bind_' . $elements[$key]['settings']['_gb_binding_name'];
					$id_to_use = $new_id_to_use;
				} else {
					$new_id_to_use = $new_id;
					$id_to_use = 'gb-' . $elements[$key]['id'];
				}

				$style = self::$current_gutenberg_attributes[$id_to_use]['_gb_style'] ?? array(); // Note: this is different from new id

				$injected = Injector::InjectStyle($new_id_to_use, $elements[$key]['settings'], $style);

				$elements[$key]['settings'] = $injected['settings'];
			}

			if (strpos($template_css, $original_id) !== false) {
				$template_css = self::convert_css_by_id($template_css, $original_id, $new_id, '#');
			}

			// STEP: inject new ID into the CSS by replacing the old ID
			if (strpos($dynamic_css, $original_id) !== false) {
				$dynamic_css = self::convert_css_by_id($dynamic_css, $original_id, $new_id, '.');
			}



			// @since 1.1.14 save _cssId to the cache
			if (!empty($new_id)) {
				if (!empty($this->elements_style_processed[$block_id][$key])) {
					$this->elements_style_processed[$block_id][$key]['settings']['_cssId'] = $new_id;
				}
			}

		} // end foreach
		// PerformanceMonitor::end('gutenbricks/process_elements/inject_custom_id');

		if (!empty($element_ids_to_change)) {
			$elements = $this->inject_unique_id($elements, $element_ids_to_change, $block_id);
		}


		// Render CSS
		if ($is_for_ssr) {
			// PerformanceMonitor::start('gutenbricks/process_elements/generate_inline_css_for_ssr');

			if (class_exists('\Bricks\Assets')) {
				// CASE: it's rendered for Gutenberg editor 
				\Bricks\Assets::$inline_css_dynamic_data =
					$template_css
					. $existing_css_dynamic_data
					. self::convert_css_by_current_custom_ids(\Bricks\Assets::$inline_css_dynamic_data)
					. $dynamic_css; // <- this needs to be here to override the inline CSS
			}
			// PerformanceMonitor::start('gutenbricks/process_elements/generate_inline_css_for_ssr');
		}

		\Gutenbricks_Public::$frontend_inline_css .= $template_css;
		\Gutenbricks_Public::$dynamic_inline_css .= $dynamic_css;

		// @since 1.1.0
		// Reset the unique inline CSS
		// it will stop generating CSS for templates that have been cloned
		// which has the same ID which was already generated
		// we're already caching by template ID which is a workaround for this
		\Bricks\Assets::$unique_inline_css = [];

		return $elements;
	}

	// CORE:
	public static function append_gutenbricks_obj_value($key, $value)
	{
		if (empty(self::$current_gutenberg_attributes['_gutenbricks_meta_data'])) {
			self::$current_gutenberg_attributes['_gutenbricks_meta_data'] = array();
		}

		self::$current_gutenberg_attributes['_gutenbricks_meta_data'][$key] = $value;
	}

	private function add_element_attr($settings, $attr_id, $attr_name, $attr_value)
	{
		if (!isset($settings['_attributes'])) {
			$settings['_attributes'] = array();
		}

		if (!empty($settings['_attributes'][$attr_name]['value'])) {
			$settings['_attributes'][$attr_name]['value'] .= $attr_value;
		} else {
			$settings['_attributes'][$attr_name] = array(
				'id' => $attr_id,
				'name' => $attr_name,
				'value' => $attr_value
			);
		}

		return $settings;
	}


	private function inject_custom_css_id($block_id, $element)
	{
		// STEP: unique ID				
		if ($element['parent'] === 0 && !empty(self::$current_gutenberg_attributes['gb_block_dom_id'])) {
			$new_id = self::$current_gutenberg_attributes['gb_block_dom_id'];
		} else {

			// @since 1.1.0
			// if the element has loop or anything that has cache issue
			// we add _ $block_id to make sure the ID is unique before this method is run

			$element_id = $element['id'];

			if (strpos($element_id, '_' . $block_id) !== false) {
				$new_id = 'b-' . $element_id;
			} else {
				$new_id = 'b-' . $element_id . '_' . $block_id;
			}
		}

		if (!isset($element['settings']['_cssId']) && !empty($block_id)) {
			if (empty(self::$current_custom_ids[$block_id])) {
				self::$current_custom_ids[$block_id] = array();
			}

			$element['settings']['_cssId'] = $new_id;

			self::$current_custom_ids[$block_id][$element['id']] = $new_id;
		}

		return array(
			'new_id' => $new_id,
			'element' => $element,
		);
	}


	// Dynamic Classes
	// this is injected in render_attributes() called by bricks/element/render_attributes filter
	private function inject_dynamic_class($block_id, $element_id, $settings)
	{
		if (!empty($block_id) && !isset($this->current_custom_classes[$block_id])) {
			$this->current_custom_classes[$block_id] = array();
		}

		if (is_object($settings)) {
			$settings = (array) $settings;
		}

		if (isset($settings['_gb_dynamic_class'])) {
			$this->current_custom_classes[$block_id][$element_id] = array();
			$dynamic_classes = bricks_render_dynamic_data($settings['_gb_dynamic_class']);


			$dynamic_classes = explode(' ', $dynamic_classes);

			foreach ($dynamic_classes as $class) {

				if (empty($class)) {
					continue;
				}

				$global_class_index = array_search($class, array_column(self::$global_classes, 'name'));

				// @since 1.1.21
				// We register all classes as custom classes
				$this->current_custom_classes[$block_id][$element_id][] = $class;

				$global_class = !empty(self::$global_classes[$global_class_index]) ? self::$global_classes[$global_class_index] : false;

				if ($global_class && !empty($global_class['id'])) {
					// @since RC4.5.2
					// BUGFIX: ACSS class is not being added as a dynamic class
					// If ACSS is active, we need to check if the $global_class[id] start with "acss"
					// If it does, we skip adding it to the global class 
					if (strpos($global_class['id'], 'acss_') === 0) {
						$this->current_custom_classes[$block_id][$element_id][] = $class;
					} else {
						if (!isset($settings['_cssGlobalClasses'])) {
							$settings['_cssGlobalClasses'] = array();
						}
						$settings['_cssGlobalClasses'][] = $global_class['id'];
					}
				}
			}
		}

		return $settings;
	}


	// set as current_element in the rendering cycle
	private static function set_global_element($element)
	{
		self::$current_element = $element;
	}

	// CLASS: Gutenbricks\Dynamic_Data_Provider
	private function renew_rendering_cycle($attr)
	{
		// since @1.0.5 
		// MIGRATION 
		// convert the legacy _gutenbricks into attr[_gutenbricks_meta_data] 
		if (!empty($attr['_gutenbricks']) && is_string($attr['_gutenbricks'])) {
			try {
				$legacy_meta_data = json_decode($attr['_gutenbricks'], true);

				// this order ensures that the legacy meta data is merged with the new meta data
				$attr['_gutenbricks_meta_data'] = array_merge($legacy_meta_data ?? array(), $attr['_gutenbricks_meta_data'] ?? array());
			} catch (Exception $e) {
			}
		}

		$template_id = $attr['template_id'];

		// STEP: assign default ACF/Meta Box values to _gutenbricks_meta_data
		// @since RC-3.6.1
		if (!empty($template_id)) {
			$fields = array();

			// Warning: ACF and MB fields with the same name will be overwritten by MB fields
			$fields = apply_filters('gutenbricks/dynamic_fields', $fields, $template_id);

			$field_per_tag = array();

			foreach ($fields as $field) {
				$id = $field['name'] ?? $field['id'] ?? ''; // ACF uses name, MB uses id

				if (!empty($id)) {
					$field_per_tag[$id] = $field;
				}

				if (empty($id) || !isset($field['default_value'])) {
					continue;
				}

				if (!isset($attr['_gutenbricks_meta_data'][$id])) {
					$attr['_gutenbricks_meta_data'][$id] = $field['default_value'];
				}
			}

			self::$current_template_settings = self::get_template_settings($template_id);

			self::$current_template_id = $template_id;
		}
		// STEP: Set the current values

		// @since 1.1.21
		self::$current_elements = array();

		// @since RC-4.2.0
		// assign current_gutenberg_attributes as static
		// so other utility functions can access it
		self::$current_gutenberg_attributes = $attr;

		Element_Should_Render::set_current_gutenberg_attributes($attr);

		// @since 1.1.0
		// we track the elements that are rendered inside shoukd_render_element
		// and return them to Gutenberg Editor
		self::$rendered_elements = array();

		// @since RC-5.3.1
		// assign current_fields as a static variable
		// so other utility functions can access it
		self::$current_fields = $field_per_tag;

		// when current_gutenberg_attributes is set, we reset the element_index
		$this->element_index = 0;
	}

	//
	// This is a REST API endpoint to get the inline CSS for a template
	//
	public function get_block_style_for_ssr($template_id, $elements, $block)
	{
		// PerformanceMonitor::start('gutenbricks/process_elements/get_template_style_for_ssr');

		$block_id = $block->attributes['block_id'] ?? '';

		// REMINDER:
		// because it's SSR, we don't need to renew the rendering cycle
		// because we are using the same attributes all over again
		// $this->renew_rendering_cycle($attributes); <-- not needed

		\Gutenbricks\Bricks_Bridge::get_bricks_inline_styles(get_the_ID());

		$page_styles = [];

		// Allowing other add-ons to enqueue styles on plugin level
		// for example, ACSS will enqueue its styles here
		$page_styles = apply_filters('gutenbricks/editor/integration/enqueue_styles', $page_styles);

		Bricks_Bridge::enqueue_bricks_frontend_scripts_from_gb();

		$page_styles = self::get_enqueued_styles($page_styles);

		// Allowing other add-ons to enqueue styles on block level
		// This will not load styles on the frontend until the block is rendered
		$page_styles = apply_filters('gutenbricks/editor/block/enqueue', $page_styles, $elements, $template_id, $block);

		// STEP: store it and later pass it to the frontend via API Class
		self::$current_page_styles = $page_styles;

		$import = '/* Loading External CSS for block previews and pattern previews */';
		$is_example = false;

		// Block style is loaded in 3 methods
		// 1. For example block, we load the style as @import
		// 2. For normal block, we load the style as <link> skiping the duplicate, it is passsed to the frontend as "enqeueu_style" in API Class
		// 3. But for pattern preview, since we can't distinguish if it's loaded as pattern preview
		//    We do a trick in the frontend code refer to <RenderServerBlock />
		if (isset($block->attributes['is_example']) && $block->attributes['is_example'] === true) {
			$is_example = true;
		}

		foreach ($page_styles as $script) {
			if ($is_example) {
				$import .= '@import url("' . $script . '");';
			} else {
				$import .= '/* @import url("' . $script . '"); */';
			}
		}

		// TODO: Put it somewhere else
		// 1. to remove the margin-top and bottom of wp-block
		// 2. For BricksTemplate's p, we remove Gutenberg's default margin  
		$no_margin = "[data-type='" . $block->name . "'].wp-block { margin-top: 0; margin-bottom: 0; }\n";
		// $no_margin .= "[data-type='" . $block->name . "'].wp-block p { margin: inherit; }\n"; // removed since 1.1.4

		// -----
		$template_style = "";

		if (empty(self::$current_gutenberg_attributes)) {
			// WARNING: hidden dependency self::$current_gutenberg_attributes = $attr;
			$template_style .= "/* GUTENBRICKS WARNING: Missing block attributes. Dynamic data will not render properly */";
		}

		$template_style .= Bricks_Bridge::get_template_inline_style_ssr($template_id, $elements);
		// -----

		// scope the template style
		$template_style = Utilities\CSSScoper::scope_css($template_style);

		// Gutenberg will strip out the style tag when it's saved
		// so we need to add it back
		if ($is_example) {
			$inline_style = '<style id="gutenbricks-preview-style">' . $import . $template_style . '</style>';
		} else {
			$inline_style = '<div style="display: none;" data-gb-rendering-style="' . $block_id . '" data-style-content="' . esc_attr($import . $template_style . $no_margin) . '"></div>';
		}

		// PerformanceMonitor::end('gutenbricks/process_elements/get_template_style_for_ssr');

		return $inline_style;
	}

	// WARNING: May not be secure to introduce frontend Javascript
	// TASK: selectively add scripts and filter them
	public static function get_enqueued_scripts()
	{
		global $wp_scripts;

		if (!is_a($wp_scripts, 'WP_Scripts')) {
			return;
		}

		$core = array();
		$addon = array();

		foreach ($wp_scripts->registered as $script) {
			if (in_array($script->handle, $wp_scripts->queue)) {
				if (strpos($script->src, 'bricks/assets') !== false) {
					$core[$script->handle] = $script->src;
				} else {
					$addon[$script->handle] = $script->src;
				}
			}
		}

		return array(
			'core' => $core,
			'addon' => $addon
		);
	}

	public static function get_enqueued_styles($enqueued_and_registered = array())
	{
		global $wp_styles;

		if (!is_a($wp_styles, 'WP_Styles')) {
			return $enqueued_and_registered;
		}

		foreach ($wp_styles->registered as $style) {
			if (in_array($style->handle, $wp_styles->queue)) {
				$enqueued_and_registered[$style->handle . '-css'] = $style->src . '?ver=' . $style->ver;
			}
		}

		return $enqueued_and_registered;
	}


	// CORE: called as a filter once by bricks/element/render to determine if the element should be rendered
	// it is also called inside process_elements_data to get self::$rendered_elements and skip the unnecessary 
	// generation of inline styles
	public function should_render_element($should_render, $element)
	{
		return Element_Should_Render::check($should_render, $element);
	}


	public function inject_html_attributes($root_attrs, $element)
	{
		// @since 1.0.8
		// We must set the global element in this stage
		if (!is_object($element)) {
			$element = (object) $element;
		}

		// @since 1.1.21
		// We modify self::current_element
		$block_id = self::$current_gutenberg_attributes['block_id'] ?? '';
		$element->settings = $this->inject_dynamic_class($block_id, $element->id, $element->settings);

		// make sure to show className on the outer most element
		// @since v1.0.4
		if (
			$this->element_index === 0
			&& isset(self::$current_gutenberg_attributes['className'])
			&& is_array($root_attrs['class'])
		) {
			$root_attrs['class'][] = self::$current_gutenberg_attributes['className'];
		}

		$block_id = self::$current_gutenberg_attributes['block_id'] ?? null;

		// CASE: if block_id is not set, we return the root_attrs as is because it's not a block
		if (empty($block_id)) {
			return $root_attrs;
		}

		// inject custom classes
		if (!empty($block_id) && !empty($this->current_custom_classes[$block_id][$element->id])) {

			if (empty($root_attrs['class'])) {
				$root_attrs['class'] = array();
			}
			$root_attrs['class'] = array_merge($root_attrs['class'], $this->current_custom_classes[$block_id][$element->id]);
		}


		// STEP: Inject ID
		// if you enabled the option in Bricks to render only needed ID
		if (isset($html_attrs['id']) && !empty(self::$current_custom_ids[$block_id][$element->id])) {
			$new_id = self::$current_custom_ids[$block_id][$element->id];
			$root_attrs['id'] = $new_id;

			// CORE: HACK:
			// @since 5.5.5
			// We need to inject id as class. For dynamic data, we take care of them in the GutenBricks_Public
			// at the end of rendering cycle
			if (empty($root_attrs['class'])) {
				$root_attrs['class'] = array();
			}

			if (is_array($root_attrs['class'])) {
				$root_attrs['class'][] = 'gbrx-' . $element->id;
			} elseif (is_string($root_attrs['class'])) {
				$root_attrs['class'] = $root_attrs['class'] . 'gbrx-' . $element->id;
			}
		}
		// END


		// Since @1.0.4
		if (!empty($root_attrs['data-script-id'])) {
			$root_attrs['data-script-id'] = $root_attrs['data-script-id'] . '_' . $block_id;
		}


		// CORE
		// @since 1.1.21
		self::set_global_element($element);


		// CORE
		$this->element_index += 1;

		return $root_attrs;
	}

	// CORE:
	// Here we inject Gutenberg content into the element
	public function filter__inject_element_settings($settings, $element_obj)
	{
		// @since 1.1.21
		self::$current_elements[] = self::$current_element;

		if (is_object(self::$current_element)) {
			return self::$current_element->settings;
		}

		return $settings;
	}




	public function render_attributes($attributes, $key, $element)
	{
		$block_id = self::$current_gutenberg_attributes['block_id'] ?? '';

		if (!empty($block_id) && !empty($this->current_custom_classes[$block_id][$element->id])) {
			if (empty($attributes['_root']['class'])) {
				$attributes['_root']['class'] = array();
			}
			$attributes['_root']['class'] = array_merge($attributes['_root']['class'], $this->current_custom_classes[$block_id][$element->id]);
		}

		// only applies to the very first element which is the block outermost layer
		// which is where the block html id should be assigned
		if ($this->element_index === 0 && !empty(self::$current_gutenberg_attributes['gb_block_dom_id'])) {
			$new_id = self::$current_gutenberg_attributes['gb_block_dom_id'];
			$attributes['_root']['id'] = $new_id;
		}

		// Before Bricks 1.9.9
		// since @RC5.5.7
		// We need to inject dynamic link into attributes because Bricks does not support this
		// Reported to Bricks team Jun 21, 2024
		if (!empty($element->settings['link'])) {
		}

		// CORE
		$this->element_index += 1;

		return $attributes;
	}

	public static function get_template_settings($template_id = null, $key = null)
	{
		if (empty($template_id)) {
			$template_id = self::$current_gutenberg_attributes['template_id'] ?? null;
		}

		if (empty($template_id)) {
			return false;
		}

		$settings = get_post_meta($template_id, GUTENBRICKS_DB_TEMPLATE_SETTINGS, true);

		if (empty($settings)) {
			return false;
		}

		if (empty($key)) {
			return $settings;
		}

		if (isset($settings[$key])) {
			return $settings[$key];
		}

		return false;
	}

	//
	// @since RC5.5.5
	// extract CSS by ID
	//					
	private static function convert_css_by_id($original_css, $original_id, $new_id, $symbol = '#')
	{
		if (empty($original_css)) {
			return '';
		}

		// STEP: replace the ID
		$converted_css = str_replace(
			$symbol . $original_id,
			$symbol . $new_id,
			$original_css
		);

		// replace [data-id="$original_id"] with [data-id="$new_id"]
		$converted_css = str_replace('[data-id="' . $original_id . '"]', '[data-id="' . $new_id . '"]', $converted_css);

		return $converted_css;
	}

	public static function convert_css_by_current_custom_ids($original_css)
	{
		$converted_css = $original_css;
		foreach (self::$current_custom_ids as $block_id => $ids) {
			foreach ($ids as $element_id => $new_id) {
				$original_id = 'brxe-' . $element_id;
				// STEP: replace the ID
				$converted_css = str_replace('#' . $original_id, '#' . $new_id, $converted_css);

				// replace [data-id="$original_id"] with [data-id="$new_id"]
				$converted_css = str_replace('[data-id="' . $original_id . '"]', '[data-id="' . $new_id . '"]', $converted_css);
			}
		}

		return $converted_css;
	}



	public static function format_dynamic_value($value, $field = array(), $context = null, $args = array())
	{
		if (empty($value)) {
			if ($context === 'text') {
				// otherwise it throws error
				return '';
			}

			return $value;
		}

		if ($context === 'text' && is_array($value)) {
			return implode(', ', $value);
		}

		// CASE: checking field specific data
		$field_type = $field['type'] ?? '';

		if (empty($field_type) && empty($context)) {
			return $value;
		}

		// STEP: Process all the fields
		$settings = self::$current_element->settings ?? array();

		switch ($field_type) {
			case 'file':
				$return_format = $field['return_format'] ?? 'url';
				if ($return_format === 'url') {
					return $value['url'] ?? null;
				} else if ($return_format === 'array') {
					return $value;
				} else if ($return_format === 'id') {
					return $value['id'] ?? null;
				}
				break;
		}

		if ($field_type === 'image' || $field_type === 'image_select' || $context === 'image') {
			$image_size = $settings['image']['size'] ?? 'full';
			$return_format = $field['return_format'] ?? 'url';

      // @since 1.2.3 
      // GB:Meta image should be returned as value
      if (!empty($field['_provider']) && $field['_provider'] === 'gbmeta' && is_int($value)) {
        return array($value);
      }
			
			if (!empty($context) && !empty($value)) {
				// CASE: use context to return value
				switch ($context) {
					case 'text':
						$value = wp_get_attachment_image_src($value, $image_size);
						return $value[0];
					case 'image':
						if (is_array($value) && !empty($value['id'])) {
							return array($value['id']);
						} else if (is_string($value)) {
							return array($value);
						}
						$value = wp_get_attachment_image_src($value, $image_size);
						return $value;
				}
			}
		
			switch ($return_format) {
				case 'id':
					return $value;
				case 'url':
					// the value is saved as an int
					// if value is not an int, we return it as is
					if (is_int($value) === false) {
						return $value;
					}

					$value = wp_get_attachment_image_src($value, $image_size);

					return $value[0];
				case 'array':
					// the value is saved as an int
					// if value is not an int, we return it as is
					if (is_int($value) === false) {
						return $value;
					}

					$value = wp_get_attachment_image_src($value, $image_size);
					return $value;
			}
		}

		return $value;
	}

	public static function get_current_gutenberg_attributes()
	{
		return self::$current_gutenberg_attributes;
	}

	public static function get_current_fields()
	{
		return self::$current_fields;
	}

	public static function get_current_element_id()
	{
		if (!empty(self::$current_element)) {
			return self::$current_element->settings['_cssId'] ?? null;
		}

		return null;
	}

	public static function get_current_field($name)
	{
		return self::$current_fields[$name] ?? null;
	}

	public static function gb_get_field($name, $context = null)
	{
		$attr = self::$current_gutenberg_attributes;
		$field_value = $attr['_gutenbricks_meta_data'][$name] ?? null;

		if (empty($field_value)) {
			return $field_value;
		}

		$field = self::$current_fields[$name] ?? null;

		return self::format_dynamic_value($field_value, $field, $context);
	}

	public static function get_current_block_id()
	{
		return self::$current_gutenberg_attributes['block_id'] ?? null;
	}


}