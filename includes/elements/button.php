<?php

namespace GutenBricks\Element;
class Button extends Element_Base
{
  public $name = 'button';
  public $editor_fields = array('link');

  public function get_element_attributes_settings($attributes_settings, $element)
  {
    $default = array();

    if (isset($element['settings']['text'])) {
      $default['text'] = $element['settings']['text'];
    }

    if (isset($element['settings']['style'])) {
      $default['style'] = $element['settings']['style'];
    }

    $attributes_settings = array_merge($attributes_settings, array(
      'type' => 'object',
      'default' => $default,
    ));

    return $attributes_settings;
  }

  public function inject_element_attributes_to_settings($settings, $element, $attributes) {
    return self::inject_button_settings($settings, $element, $attributes);
  }

  public static function inject_button_settings($settings, $element, $attributes) {
    if (empty($settings['link'])) {
      $settings['link'] = array(
        'type' => 'external',
      );
    }
    if (!empty($attributes['text'])) {
      $settings['text'] = $attributes['text'] ?? '';
    }
    if (!empty($attributes['style'])) {
      $settings['style'] = $attributes['style'];
    }
    if (!empty($attributes['link'])) {
      $settings['link'] = array_merge($settings['link'] ?? array(), $attributes['link'] ?? array());

      $link_type = $settings['link']['type'] ?? '';

      // since @rc.4.6.1
      if (empty($link_type)) {
        $settings['link']['type'] = 'external';
      }
    }

    if (!empty($attributes['link']['ariaLabel'])) {
      $settings['link']['ariaLabel'] = $attributes['link']['ariaLabel'];
    }

    if (!empty($attributes['link']['newTab'])) {
      $settings['link']['newTab'] = $attributes['link']['newTab'];
    }

    if (!empty($attributes['link']['relNofollow'])) {
      if (!isset($settings['link']['rel'])) {
        $settings['link']['rel'] = '';
      }
      $settings['link']['rel'] .= 'nofollow';
    }

    return $settings;
  }

  public function get_element_attribute_from_dom($attribute, $innerHTML, $dom_element) {
    // @since 1.1.24-DEV4
    // we don't want to get the text from the button element
    if ($dom_element->hasAttribute('data-gbrx-subfield') && $dom_element->getAttribute('data-gbrx-subfield') === 'text') {
      return null;
    }

    return array(
      'text' => $innerHTML,
      'link' => array(
        'url' => $dom_element->getAttribute('href'),
        'ariaLabel' => $dom_element->getAttribute('aria-label'),
        'title' => $dom_element->getAttribute('title'),
        'rel' => $dom_element->getAttribute('rel'),
      ),
    );
  }

  public function get_element_render_attributes($render_attr, $key, $element) {
    // remove lightbox attributes by removing the class bricks-lightbox
    if (isset($render_attr['class'])) {
      $render_attr['class'] = array_diff($render_attr['class'], ['bricks-lightbox']);
    }

    return $render_attr;
  }

  // we use a temporary <span> to set the attributes
  // because the button element is a <button> and we need to set the attributes on the innerHTML
  public function filter_element_settings($settings, $element, $attributes) {
    $gb_id = \GutenBricks\Render_Context::get_gb_id($element);
  
    $dom_attributes = array(
      'data-gbrx-id' => $gb_id,
      'data-gbrx-subfield' => 'text',
    );
  
    $attributes_str = '';
    foreach ($dom_attributes as $key => $value) {
      $attributes_str .= $key . '="' . $value . '" ';
    }
  
    $text = $settings['text'] ?? '';
  
    $settings['text'] = "<span $attributes_str>" . $text . "</span>";
  
    return $settings;
  }

  public function get_element_block_settings($block_settings, $element, $skip_render)
  {
    return $block_settings;
  }

  public function get_element_editor_rules($editor_rules, $element, $attributes) {
    $settings = $element['settings'] ?? array();

    $editor_rules = TextBasic::get_element_editor_rules($editor_rules, $settings,$this->name);

    $editor_rules['editorFields'] = ['link', 'text'];

    $editor_rules['subfield'] = array(
      'text' => array(
        'editorFields' => ['text'],
        'path' => 'text',
      ),
    );

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