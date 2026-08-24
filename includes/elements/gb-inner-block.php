<?php

namespace GutenBricks\Element;
class InnerBlock extends Element_Base 
{
  public $name = 'gb-inner-blocks';

  public $is_container = true;

  // InnerBlock doesn't need Gutenberg Editor
  // public $disable_gutenberg_controls = true;

  function get_element_block_settings($block_settings, $element, $skip_render) {
    if ($skip_render) {
      return $block_settings;
    }

    $settings = $element['settings'];

    $block_settings['allowed_blocks'] = array(
      'allowed_core_blocks' => $settings['allowed_core_blocks'] ?? [],
      'allowed_gb_blocks' => $settings['allowed_gb_blocks'] ?? [],
    );
    $block_settings['default_blocks'] = $settings['default_blocks'] ?? '';

    if (isset($settings['max_blocks'])) {
      if (is_string($settings['max_blocks'])) {
        $dynamic_max_blocks = bricks_render_dynamic_data($settings['max_blocks']);
        if (is_numeric($dynamic_max_blocks)) {
          $block_settings['max_blocks'] = intval($dynamic_max_blocks);
        }
      } 
      if (is_numeric($settings['max_blocks'])) {
        $block_settings['max_blocks'] = intval($dynamic_max_blocks);
      }
    }

    $block_settings['has_inner_blocks'] = true;

    return $block_settings;
  }

  public function get_element_controls($options)
  {
    $name = $this->name;
    $template_edit_disabled = $this->template_edit_disabled;
    $is_container = $this->is_container;
    
		include __DIR__ . '/controls/element-dynamic-class.php';
		include __DIR__ . '/controls/element-style-editor.php';
		include __DIR__ . '/controls/element-client-support.php';

    return $options;
  } 
}