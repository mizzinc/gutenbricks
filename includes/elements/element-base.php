<?php
namespace GutenBricks\Element;

/*
@since 1.1.0
It serve as the base class for element classes.
*/
class Element_Base
{
  public $name;

  public $editor_fields = array();

  public $is_container;
  public $template_edit_disabled;
  public $disable_gutenberg_controls;
  public static $supported_bricks_elements = array();

  public function __construct()
  {
    $this->init_hooks();

    // TODO: move this somewhere else
    if (method_exists($this, 'get_element_native_fields')) {
      self::$supported_bricks_elements[] = $this->name; // keep track of the elements that have native fields
    }
  }

  public function is_container()
  {
    return $this->is_container;
  }

  public function init_hooks()
  {
    add_filter('gutenbricks/element/' . $this->name . '/attributes_settings', array($this, 'get_attributes_settings'), 10, 2);
    add_filter('gutenbricks/element/' . $this->name . '/block_settings', array($this, 'get_block_settings'), 10, 3);
    add_filter('gutenbricks/element/' . $this->name . '/filter_settings', array($this, 'filter_settings'), 10, 3);
    add_filter('gutenbricks/element/' . $this->name . '/inject_attributes_to_settings', array($this, 'inject_attributes_to_settings'), 10, 3);
    add_filter('gutenbricks/element/' . $this->name . '/render_attributes', array($this, 'get_render_attributes'), 10, 3);
    add_filter('gutenbricks/element/' . $this->name . '/set_root_attributes', array($this, 'get_root_attributes'), 10, 2);
    add_filter('gutenbricks/element/' . $this->name . '/get_attribute_from_dom', array($this, 'get_attribute_from_dom'), 10, 3);

    add_filter('bricks/elements/' . $this->name . '/control_groups', array($this, 'get_control_groups'), 30, 1);
    add_filter('bricks/elements/' . $this->name . '/controls', array($this, 'get_controls'), 30, 1);

    if (method_exists($this, 'init_element_hooks')) {
      $this->init_element_hooks();
    }
  }

  // Every element has different attributes according to element->settings.
  // We could have put everything inside settings object, but this way we can 
  // keep the settings object clean and add additional attributes settings for 
  // elements that need them without having to merge arrays.
  public function get_attributes_settings($attributes_settings, $element)
  {
    if (!method_exists($this, 'get_element_attributes_settings')) {
      return array();
    }

    if (is_object($element)) {
      $element = (array) $element;
    }

    $gb_id = \GutenBricks\Render_Context::get_gb_id($element);
    $settings = $element['settings'] ?? array();
    if (method_exists($this, 'get_element_attributes_settings')) {
      $attributes_settings = $this->get_element_attributes_settings($attributes_settings, $element);
    }

    // STEP: link
    // STEP: For elements wrapped in a link
    if (!empty($settings['link'])) {
      if (!isset($attributes_settings[$gb_id])) {
        $attributes_settings[$gb_id] = array(
          'type' => 'object',
          'default' => array(),
        );
      }
      $attributes_settings[$gb_id]['default']['link'] = $settings['link'];
    }

    // STEP: visibility toggle
    if (
      isset($settings['_gb_enable_show_hide']) &&
      $settings['_gb_enable_show_hide'] === true &&
      isset($attributes_settings[$gb_id])
    ) {
      if (!isset($attributes_settings[$gb_id]['default'])) {
        $attributes_settings[$gb_id]['default'] = array();
      }

      $attributes_settings[$gb_id]['default']['_gb_show_element'] = $settings['_gb_show_element'] ?? $settings['_gb_show_hide_default'] ?? false;
    }

    return $attributes_settings;
  }

  // This is a hook that allows you to modify the settings of an element.
  // Primarily used to inject $attributes into settings.
  public function inject_attributes_to_settings($settings, $element, $element_attributes)
  {
    // BREAKING: If you remove this, empty values will be injected into settings
    if (empty($element_attributes)) {
      return $settings;
    }

    if (method_exists($this, 'inject_element_attributes_to_settings')) {
      $settings = $this->inject_element_attributes_to_settings($settings, $element, $element_attributes);
    }

    return $settings;
  }

  public function filter_settings($settings, $element, $attributes)
  {
    if (method_exists($this, 'filter_element_settings')) {
      $settings = $this->filter_element_settings($settings, $element, $attributes);
    }
    return $settings;
  }

  public function get_block_settings($block_settings, $element, $skip_render)
  {
    $block_settings['native_fields'] = $this->get_native_fields($block_settings['native_fields'] ?? array(), $element);
   
    // @since 1.1.11
    // if we don't skip render, the elements in the last variant will override the editor rules
    // which causes issues
    if (!$skip_render) {
      $block_settings['editor_rules'] = $this->get_editor_rules($block_settings['editor_rules'] ?? array(), $element, \GutenBricks\Render_Context::$current_gutenberg_attributes);
    }
   
    $block_settings['rendered_elements'] = \GutenBricks\Render_Context::$rendered_elements;

    if (method_exists($this, 'get_element_block_settings')) {
      $block_settings = $this->get_element_block_settings($block_settings, $element, $skip_render);
    }

    return $block_settings;
  }

  public function get_editor_rules($editor_rules, $element, $attributes)
  {
    $settings = $element['settings'];
    $gb_id = \GutenBricks\Render_Context::get_gb_id($element);

    if (!isset($editor_rules[$gb_id])) {
      $editor_rules[$gb_id] = array();
    }

    $editor_rules[$gb_id]['gbId'] = $gb_id;
    $editor_rules[$gb_id]['name'] = $element['name'];
    $editor_rules[$gb_id]['subfield'] = array();
    $editor_rules[$gb_id]['originalId'] = $element['id'];

    // we disable edit for dynamic data on canvas
    $settings_has_dynamic_data = self::check_settings_has_dynamic_data($settings);

    // @since rc.4.0
    // Check if template is disabled for editing, if so, we don't inject the editing attributes at all
    if (($settings['_gb_disable_edit'] ?? false) === true || $settings_has_dynamic_data) {
      $editor_rules[$gb_id]['disableEdit'] = true;
      return $editor_rules;
    }

    // STEP: Binding name
    // if (isset($settings['_gb_binding_name'])) {
    //   $trimmed = trim($settings['_gb_binding_name']);
    //   if (!empty($trimmed)) {
    //     $editor_rules[$gb_id]['bindingName'] = 'bind_' . $trimmed;
    //   }
    // }

    if (!empty($settings['separator'])) {
      $editor_rules[$gb_id]['separator'] = $settings['separator'];
    }

    // STEP: For elements wrapped in a link
    if (isset($settings['tag']) && $settings['tag'] === 'a' && !in_array('link', $this->editor_fields)) {
      $this->editor_fields[] = 'link';
    }

    if (method_exists($this, 'get_element_editor_rules')) {
      $editor_rules[$gb_id] = $this->get_element_editor_rules($editor_rules[$gb_id], $element, $attributes);
    }

    if (!empty($this->editor_fields)) {
      $editor_rules[$gb_id]['editorFields'] = $this->editor_fields;
    }

    if (get_option('_gutenbricks_adv_text_fallback') == '1') {
      if (!empty($settings['text'])) {
        $editor_rules[$gb_id]['originalValue'] = $settings['text'];
      }
    }

    // place at the end
    if (!empty($element_editor_rules)) {
      $editor_rules[$gb_id] = $element_editor_rules;
    }

    // make sure subfield has gbId of its parent
    if (!empty($editor_rules[$gb_id]['subfield'])) {
      foreach ($editor_rules[$gb_id]['subfield'] as $subfield_name => $subfield_value) {
        $subfield_value['gbId'] = $gb_id;
        $editor_rules[$gb_id]['subfield'][$subfield_name] = $subfield_value;
      }
    }

    return $editor_rules;
  }

  public function get_render_attributes($render_attr, $key, $element)
  {
    if (method_exists($this, 'get_element_render_attributes')) {
      $render_attr = $this->get_element_render_attributes($render_attr, $key, $element);
    }

    return $render_attr;
  }

  // @since 1.1.0
  // This is a hook that allows you to modify the attributes of an element inside 
  // Gutenberg editor. 
  public function get_root_attributes($root_attr, $element)
  {
    $settings = $element['settings'];

    // STEP: adding element id and name
    $root_attr['data-gbrx-id'] = \GutenBricks\Render_Context::get_gb_id($element);
    $root_attr['data-gbrx-name'] = $element['name'];

    if (method_exists($this, 'get_element_root_attributes')) {
      $root_attr = $this->get_element_root_attributes($root_attr, $element, \GutenBricks\Render_Context::$current_gutenberg_attributes);
    }

    // For now we print out the _gb_popup_text tooltip text using html attribute
    // We will use this to display the tooltip text in the editor
		if (!empty($settings['_gb_popup_text'])) {
      $root_attr['data-gb-popup-text'] = $settings['_gb_popup_text'];
    }

    $settings_has_dynamic_data = self::check_settings_has_dynamic_data($settings);

    // @since RC5.5
    // Check if the element has dynamic data
    if ($settings_has_dynamic_data) {
      if (empty($root_attr['data-gb-popup-text'])) {
        $root_attr['data-gb-popup-text'] = __('You cannot edit this Dynamic Field here.', 'gutenbricks');
      }
    }

    return $root_attr;
  }

  public function get_editing_attributes($editing_attributes, $element, $attributes)
  {
    $editing_attributes = array();
    if (method_exists($this, 'get_element_editing_attributes')) {
      $editing_attributes = $this->get_element_editing_attributes($editing_attributes, $element, $attributes);
    }
    return $editing_attributes;
  }

  public function get_attribute_from_dom($attribute, $innerHTML, $dom_element)
  {
    if (method_exists($this, 'get_element_attribute_from_dom')) {
      $attribute_result = $this->get_element_attribute_from_dom($attribute, $innerHTML, $dom_element);

      if (!empty($attribute_result)) {
        $attribute = $attribute_result;
      }
    }
    return $attribute;
  }

  public function get_native_fields($native_fields, $element)
  {
    $gb_id = \GutenBricks\Render_Context::get_gb_id($element);
    $settings = $element['settings'];

    if ($settings['_gb_enable_native_editor'] ?? false === true && method_exists($this, 'get_element_native_fields')) {
      if (method_exists($this, 'get_element_native_fields')) {
        $field = $this->get_element_native_fields($native_fields, $gb_id, $element);

        // Path to the form
        $path = $field['path'] ?? $gb_id;
        $field['path'] = $path;

        // STEP: Group
        $group_to_use = $settings['_gb_native_editor_group'] ?? $field['group'];
        if (!isset($native_fields[$group_to_use])) {
          $native_fields[$group_to_use] = array();
        }

        $parent_id = $element['parent'] ?? null;
        // STEP: some default setups
        if (!empty($parent_id)) {
          $field['parent_id'] = [$parent_id];
        }

        if (empty($fields['element_id'])) {
          $field['element_id'] = [$element['id']];
        }

        $field['label'] = $settings['_gb_native_editor_label'] ?? $element['label'] ?? null;
        $field['instructions'] = $settings['_gb_native_editor_instructions'] ?? '';

        // STEP: if the field is not set, we set it
        if (empty($native_fields[$group_to_use][$path])) {
          $native_fields[$group_to_use][$path] = $field;
        } else {
          // fields can have multiple element_id and parent_id because of binding_name
          if (!in_array($element['id'], $native_fields[$group_to_use][$path]['element_id'])) {
            $native_fields[$group_to_use][$path]['element_id'][] = $element['id'];
          }

          if (!in_array($parent_id, $native_fields[$group_to_use][$path]['parent_id'])) {
            $native_fields[$group_to_use][$path]['parent_id'][] = $parent_id;
          }
        }
      }
    }

    // STEP: Get variants fields
    // NOTE: vairants and toggles are part of native fields
    $native_fields = $this->get_variant_native_fields($native_fields, $element);

    // STEP: Get toggles
    $native_fields = $this->get_toggle_native_fields($native_fields, $element);


    return $native_fields;
  }

  public static function check_settings_has_dynamic_data($settings)
  {
    // check if $settings['text'] has dynamic data
    if (isset($settings['text']) && is_string($settings['text'])) {
      // so the dynamic data is wrapped in curly braces
      // it can contain any word character and symbols such as _, -, :, and |
      // for eg. acf_repeater_imagelink_repeater_linktext:array_value|title
      if (preg_match('/\{([\w\-:|]+)\}/', $settings['text'])) {
        return true;
      }
    }

    // check if $settings['image']['useDynamicData'] is true
    if (isset($settings['image']['useDynamicData']) && $settings['image']['useDynamicData']) {
      return true;
    }

    return false;
  }

  private function get_variant_native_fields($native_fields, $element)
  {
    $settings = $element['settings'];
    if (($settings['_gb_enable_variant'] ?? false) !== true) {
      return $native_fields;
    }

    $variant_options = array();
    $default_value = '';
    $template_settings = \GutenBricks\Render_Context::$current_template_settings ?? array();
    $type = $template_settings['_gb_variant_field_type'] ?? 'radio';

    if (!empty($settings['_gb_variant_name'])) {
      $label = $settings['_gb_variant_label'] ?? '';

      if ($type === 'radio' && !empty($settings['_gb_variant_thumbnail']['url'])) {
        $variant_options[$settings['_gb_variant_name']] = $label . '>>image>>' . $settings['_gb_variant_thumbnail']['url'];
      } else {
        $variant_options[$settings['_gb_variant_name']] = $label;
      }

      if (empty($default_value)) {
        $default_value = $settings['_gb_variant_name'];
      }
    }

    if (empty($variant_options)) {
      return $native_fields;
    }

    $label = $template_settings['_gb_variant_field_label'] ?? 'Variants';
    $group = $template_settings['_gb_variant_field_group'] ?? 'Variants';

    if (!isset($native_fields[$group])) {
      $native_fields[$group] = array();
    }

    $path = '_gb_current_variant';

    if (empty($native_fields[$group][$path])) {
      $native_fields[$group][$path] = array(
        'path' => $path,
        'label' => $label,
        'type' => $type,
        'default_value' => $default_value,
        'original_type' => '_gb_current_variant',
        'choices' => $variant_options,
        'element_id' => [$element['id']],
      );
    } else {
      // $variant_options are accumulated from other elements
      $native_fields[$group][$path]['choices'] = array_merge(
        $native_fields[$group][$path]['choices'],
        $variant_options
      );

      // Although variants are per block, we just add the element_id to follow the convention
      if (!in_array($element['id'], $native_fields[$group][$path]['element_id'])) {
        $native_fields[$group][$path]['element_id'][] = $element['id'];
      }
    }

    return $native_fields;
  }

  private function get_toggle_native_fields($native_fields, $element)
  {
    $settings = $element['settings'];
    $element_id = $element['id'];
    $gb_id = \GutenBricks\Render_Context::get_gb_id($element);

    $path = $gb_id . '._gb_show_element';

    // STEP: For Show Hide
    if (isset($settings['_gb_enable_show_hide']) && $settings['_gb_enable_show_hide']) {
      $group_to_use = $settings['_gb_show_hide_group'] ?? 'Visibility';

      $parent_id = $element['parent'] ?? null;

      if (!isset($native_fields[$group_to_use])) {
        $native_fields[$group_to_use] = array();
      }

      if (!isset($native_fields[$group_to_use][$path])) {
        $native_fields[$group_to_use][$path] = array(
          'path' => $path, // <- Refer to onChange function of NativeFormEditor. 
          'element_id' => [$element_id],
          'label' => $settings['_gb_show_hide_label'] ?? $element['label'] ?? '',
          'gb_id' => $gb_id,
          // 	 As we are updating only part of the field. 
          // 	 NOTE: if it's a binding value, we modify the binding value
          'parentName' => $gb_id,
          'subfield' => '_gb_show_element',
          'default_value' => $settings['_gb_show_hide_default'] ?? false,
          'type' => 'true_false',
          'original_type' => 'show-hide',
          'instructions' => $settings['_gb_show_hide_instructions'] ?? '',
          'parent_id' => [$parent_id],
        );
      } else {
        // @since RC5.5.1 
        // In the case of binding_name, the same binding name might have multiple parents
        // So we must use an array to store the parent_id
        $native_fields[$group_to_use][$path]['parent_id'][] = $parent_id;

        // element_id must be array as the same field with the same binding_name might have multiple element
        if (!in_array($element_id, $native_fields[$group_to_use][$path]['element_id'])) {
          $native_fields[$group_to_use][$path]['element_id'][] = $element_id;
        }
      }
    }

    return $native_fields;
  }

  public function get_control_groups($control_groups)
  {
    if (!\Gutenbricks\Bricks_Bridge::bricks_is_builder() || !is_post_gutenbricks_template()) {
      return $control_groups;
    }

    if ($this->disable_gutenberg_controls) {
      return $control_groups;
    }

    $control_groups['gutenbricks_group'] = [
      'tab' => 'content', // or 'style'
      'title' => esc_html__('GutenBricks', 'gutenbricks'),
    ];
    //if ($this->name === 'post-content') {
    //	$control_groups['gutenbricks_group'] = [
    //		'tab' => 'content', // or 'style'
    //		'title' => esc_html__('GutenBricks', 'gutenbricks'),
    //	];
    //}

    return $control_groups;
  }

  public function get_controls($options)
  {
    if (!\Gutenbricks\Bricks_Bridge::bricks_is_builder() || !is_post_gutenbricks_template()) {
      return $options;
    }

    $this->template_edit_disabled = \Gutenbricks\Bricks_Bridge::get_template_settings('_gb_disable_template_edit');

    if ($this->template_edit_disabled) {
      $options['_gb_template_disabled_info'] = [
        'type' => 'info',
        'group' => 'gutenbricks_group',
        'tab' => 'content',
        'description' => 'The template is disabled for editing. To change it, open Settings > Template Settings > GutenBricks Block.. <a style="display:none;" href="#" target="_blank">Learn More</a>',
      ];
    }

    if (!$this->template_edit_disabled && !$this->is_container) {
      $options['_gb_disable_edit'] = [
        'type' => 'checkbox',
        'group' => 'gutenbricks_group',
        'tab' => 'content',
        'label' => 'Disable Element Editing',
      ];
    }

    if (method_exists($this, 'get_element_controls')) {
      $options = $this->get_element_controls($options);
    }


    return $options;
  }
}
