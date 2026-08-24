<?php

namespace GutenBricks\Element;
class Image extends Element_Base
{
  public $name = 'image';

  public $editor_fields = array('image_selector');
  
  public function get_element_attributes_settings($attributes_settings, $element)
  {
    $attributes_settings = array_merge($attributes_settings, array(
      'type' => 'object',
      'default' => array(
        "image" => $element['settings']['image'] ?? null,
      ),
    ));

    return $attributes_settings;
  }

  public function get_element_native_fields($native_fields, $gb_id, $element)
  {
    return array(
      'gb_id' => $gb_id,
      'default_value' => $element['settings']['image'] ?? '',
      'type' => 'image',
      'group' => 'Images',
      'original_type' => 'image',
      'image_size' => $settings['image']['size'] ?? '', // used by frontend
    );
  }



  public function inject_element_attributes_to_settings($settings, $element, $attributes)
  {
    if (empty($settings['image']['useDynamicData'])) {
      $settings['image'] = $attributes['image'];
    }
    return $settings;
  }

  public function get_element_attribute_from_dom($attribute, $innerHTML, $dom_element)
  {
    $image = array();

    if ($dom_element->hasAttribute('src')) {
      $image['url'] = $dom_element->getAttribute('src');
    }

    if ($dom_element->hasAttribute('alt')) {
      $image['alt'] = $dom_element->getAttribute('alt');
    }

    if ($dom_element->hasAttribute('title')) {
      $image['title'] = $dom_element->getAttribute('title');
    }

    return array(
      'image' => $image,
    );
  }


  public function get_element_editor_rules($editor_rules, $element, $attributes)
  {
    $settings = $element['settings'] ?? array();
   
    // if image uses dynamic data we just use dynamic data
    // and don't use image selector
    if (!isset($settings['image']['useDynamicData'])) {
      // $editor_attr['data-bricks-editor'] = 'image_selector';
      $editor_rules['editorFields'] = ['image_selector'];

      if (isset($settings['image']['size'])) {
        $editor_rules['imageSize'] = $settings['image']['size'];
      }

      // When image element has a tag, <img /> will be wrapped inside the tag
      // it can be figure, picture, div and custom tag
      if (!empty($settings['tag'])) {
        $editor_rules['parentTag'] = $settings['tag'];
      }

      // When image element has a link, <img /> will be wrapped inside the <a> tag
      // @since 3.6.6
      if (!empty($settings['link'])) {
        $editor_rules['parentTag'] = 'A';
      }

      // if there is no tag, but there is a caption, we assume Bricks is wrapping it as figure
      // @since Bricks 1.9.3
      // 
      // we will double-check if it's indeed wrapped in a figure tag in the Gutenberg editor
      // @since RC4.2.3
      // @tag CASE551
      if (empty($settings['tag']) && !empty($settings['caption'])) {
        $editor_rules['parentTag'] = 'figure';
      }

      if (!empty($settings['image']['id'])) {
        $editor_rules['originalValue'] = $settings['image']['id'];
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
 
    include __DIR__ . '/controls/element-binding-name.php';
		include __DIR__ . '/controls/element-dynamic-class.php';
		include __DIR__ . '/controls/element-style-editor.php';
		include __DIR__ . '/controls/element-show-hide.php';
		include __DIR__ . '/controls/element-rendering.php';
		include __DIR__ . '/controls/element-client-support.php';

    return $options;
  }
}