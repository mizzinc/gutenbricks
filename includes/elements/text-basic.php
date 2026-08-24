<?php

namespace GutenBricks\Element;
class TextBasic extends Element_Base
{
  public $name = 'text-basic';
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

  public function get_element_native_fields($native_fields, $gb_id, $element)
  {
    return array(
      'gb_id' => $gb_id,
      'default_value' => $element['settings']['text'] ?? '',
      'type' => 'textarea',
      'group' => 'Texts',
      'original_type' => 'text',
    );
  }

  public function inject_element_attributes_to_settings($settings, $element, $attributes)
  {
    $settings['text'] = $attributes['text'] ?? '';
    return $settings;
  }

  public function get_element_attribute_from_dom($attribute, $innerHTML, $dom_element)
  {
    return array(
      'text' => $innerHTML,
    );
  }

  public function get_element_block_settings($block_settings, $element, $skip_render)
  {
    return $block_settings;
  }

  public static function get_element_editor_rules($editor_rules, $settings, $name)
  {
    if (!empty($settings['_gb_allowed_formats'])) {
      if (!in_array('disable_all', $settings['_gb_allowed_formats'])) {
        $editor_rules['allowedFormats'] = $settings['_gb_allowed_formats'];
      }
    } else {
      if ($name === 'text') {
        $editor_rules['allowedFormats'] = array('bold', 'italic', 'underline', 'strike', 'link', 'list_ordered', 'list_bullet', 'align');
      } else {
        // Defaut values
        $editor_rules['allowedFormats'] = array('core/bold', 'core/italic', 'core/strikethrough', 'core/underline', 'core/text-color');
        if ($name !== 'text-link' && $name !== 'button') {
          $editor_rules['allowedFormats'][] = 'core/link';
        }
      }
    }

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