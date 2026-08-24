<?php

namespace GutenBricks\Element;
class Div extends Element_Base 
{
  public $name = 'div';
  public $is_container = true;  

  public function get_element_attributes_settings($attributes_settings, $element)
  {
    $attributes_settings = array_merge($attributes_settings, array(
      'type' => 'object',
    ));

    return $attributes_settings;
  }

  public function inject_element_attributes_to_settings($settings, $element, $attributes) {
    return Button::inject_button_settings($settings, $element, $attributes);
  }

  public function get_element_editor_rules($editor_rules, $element, $attributes) {
    return $editor_rules;
  }


  public function get_element_controls($options)
  {
    $name = $this->name;
    $template_edit_disabled = $this->template_edit_disabled;
    $is_container = $this->is_container;
    
    include __DIR__ . '/controls/element-binding-name.php';
		include __DIR__ . '/controls/element-dynamic-class.php';
		include __DIR__ . '/controls/element-style-editor.php';
		include __DIR__ . '/controls/element-variant.php';
		include __DIR__ . '/controls/element-show-hide.php';
		include __DIR__ . '/controls/element-rendering.php';
		include __DIR__ . '/controls/element-client-support.php';

    return $options;
  }
}