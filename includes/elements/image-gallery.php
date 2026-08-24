<?php

namespace GutenBricks\Element;
class ImageGallery extends Element_Base
{
  public $name = 'image-gallery';

  public function get_element_attributes_settings($attributes_settings, $element)
  {
    $attributes_settings = array_merge($attributes_settings, array(
      'type' => 'object',
      'default' => array(
        "items" => $element['settings']['items'] ?? null,
      ),
    ));

    return $attributes_settings;
  }

  public function get_element_native_fields($native_fields, $gb_id, $element) {
    return array(
      'gb_id' => $gb_id,
      'default_value' => $settings['items']['images'] ?? array(),
      'type' => 'gallery',
      'group' => 'Image Galleries',
      'original_type' => 'image-gallery',
    );
  }

  public function inject_element_attributes_to_settings($settings, $element, $attributes)
  {
    $settings['items']['images'] = $attributes['items']['images'] ?? null;
    return $settings;
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

