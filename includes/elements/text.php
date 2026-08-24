<?php

namespace GutenBricks\Element;
class Text extends Element_Base
{
  public $name = 'text';
  public $editor_fields = array('text');

  public function get_element_attributes_settings($attributes_settings, $element)
  {
    $attributes_settings = array_merge($attributes_settings, array(
      'type' => 'object',
      'default' => array(
        "text" => $element['settings']['text'] ?? null,
      ),
    ));

    return $attributes_settings;
  }

  public function get_element_native_fields($native_fields, $gb_id, $element) {
    return array(
      'gb_id' => $gb_id,
      'default_value' => $element['settings']['text'] ?? '',
      'type' => 'textarea',
      'group' => 'Texts',
      'original_type' => 'text',
    );
  }

  public function inject_element_attributes_to_settings($settings, $element, $attributes) {
    $settings['text'] = $attributes['text'] ?? '';
    return $settings;
  }

  public function get_element_attribute_from_dom($attribute, $innerHTML, $dom_element) {
    return array(
      'text' => $innerHTML,
    );
  }

  /*
  public function get_element_root_attributes($editor_attr, $element, $attributes) {
    $settings = $element['settings'] ?? array();

    if (!empty($settings['_gb_allowed_headings'])) {
      // get the integer part of $settings['tag']
      if (isset($settings['_original_tag'])) {
        $tag = str_replace('h', '', $settings['_original_tag']);

        // if $tag is not in the allowed headings, we add it
        if (!in_array($tag, $settings['_gb_allowed_headings'])) {
          $settings['_gb_allowed_headings'][] = $tag;
        }
      }

      $editor_attr['data-gb-allowed-headings'] = implode(',', $settings['_gb_allowed_headings']);
    }

    $editor_attr = TextBasic::get_text_editing_attributes($settings, $editor_attr, $this->name);

    return $editor_attr;
  }
  */

  public function get_element_editor_rules($editor_rules, $element, $attributes) {
    $settings = $element['settings'] ?? array();

    $editor_rules['allowedHeadings'] = Heading::get_allowed_headings($settings);
    $editor_rules = TextBasic::get_element_editor_rules($editor_rules, $settings, $this->name);

    return $editor_rules;
  }

  public function get_element_controls($options)
  {
    $name = $this->name;
    $template_edit_disabled = $this->template_edit_disabled;
    $is_container = $this->is_container;
    
    include __DIR__ . '/controls/element-content-editor.php';
    include __DIR__ . '/controls/element-specific/heading-and-text-editor.php'; 
 
    include __DIR__ . '/controls/element-binding-name.php';
		include __DIR__ . '/controls/element-dynamic-class.php';
		include __DIR__ . '/controls/element-style-editor.php';
		include __DIR__ . '/controls/element-show-hide.php';
		include __DIR__ . '/controls/element-rendering.php';
		include __DIR__ . '/controls/element-client-support.php';

    return $options;
  }

}