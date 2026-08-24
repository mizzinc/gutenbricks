<?php

namespace GutenBricks\Element;
class Video extends Element_Base
{
  public $name = 'video';

  public function get_element_attributes_settings($attributes_settings, $element)
  {
    $attributes_settings = array_merge($attributes_settings, array(
      'type' => 'object',
    ));

    return $attributes_settings;
  }

  public function get_element_native_fields($native_fields, $gb_id, $element)
  {
    $settings = $element['settings'];

    // other potential controls	
    // ["youtubeControls"]=> bool(true)
    // ["youtubeShowinfo"]=> bool(true)
    // ["vimeoByline"]=> bool(true)
    // ["vimeoTitle"]=> bool(true)
    // ["vimeoPortrait"]=> bool(true)
    // ["fileControls"]=> bool(true)
    $videoType = $settings['videoType'] ?? 'youtube';
    $default_value = array(
      'videoType' => $videoType,
    );
    $previewImage = null;

    switch ($videoType) {
      case 'youtube':
        $label = $label ?? 'YouTube Video';
        $previewImage = 'custom';
        $default_value['youTubeId'] = $settings['youTubeId'] ?? '';
        $default_value['previewImageCustom'] = $settings['previewImageCustom'] ?? null;
        break;
      case 'vimeo':
        $label = $label ?? 'Vimeo Video';
        $previewImage = 'custom';
        $default_value['vimeoId'] = $settings['vimeoId'] ?? '';
        $default_value['vimeoHash'] = $settings['vimeoHash'] ?? '';
        $default_value['previewImageCustom'] = $settings['previewImageCustom'] ?? null;
        break;
      case 'media':
        $label = $label ?? 'Media Video';
        $default_value['media'] = $settings['media'] ?? '';
        $default_value['videoPoster'] = $settings['videoPoster'] ?? null;
        break;
      case 'file':
        $label = $label ?? 'Video File URL';
        $default_value['fileUrl'] = $settings['fileUrl'] ?? '';
        $default_value['videoPoster'] = $settings['videoPoster'] ?? null;
        break;
    }

    return array(
      'gb_id' => $gb_id,
      'default_value' => $default_value,
      'type' => 'bricks_video',
      'group' => 'Videos',
      'original_type' => 'video',
      'previewImage' => $previewImage,
      'videoType' => $videoType,
    );
  }

  public function inject_element_attributes_to_settings($settings, $element, $attributes)
  {
    $settings['previewImageCustom'] = $attributes['previewImageCustom'] ?? null;
    $settings['youTubeId'] = $attributes['youTubeId'] ?? null;
    $settings['vimeoId'] = $attributes['vimeoId'] ?? null;
    $settings['vimeoHash'] = $attributes['vimeoHash'] ?? null;
    $settings['fileUrl'] = $attributes['fileUrl'] ?? null;
    $settings['videoType'] = $attributes['videoType'] ?? 'youtube';
    $settings['videoPoster'] = $attributes['videoPoster'] ?? null;
    $settings['media'] = $attributes['media'] ?? null;
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