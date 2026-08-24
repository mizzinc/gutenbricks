<?php
namespace Gutenbricks;

/**
 * 
 *
 * @package    Gutenbricks
 * @author     WiredWP <ryan@wiredwp.com>
 */
class Gutenbricks_Block 
{
  public function __construct()
  {
  }

  public function get_template_block_settings($block_settings, $template_id) {
    $template_settings = Render_Context::get_template_settings($template_id);

    $block_settings['template'] = array(
      'id' => $template_id,
    );
    
    if (isset($template_settings['_gb_block_wrapper_dynamic_class'])) {
      $block_settings['template']['block_wrapper_dynamic_class'] = bricks_render_dynamic_data($template_settings['_gb_block_wrapper_dynamic_class']);
    }

    return $block_settings;
  }


}

