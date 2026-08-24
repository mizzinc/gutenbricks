<?php

if (!function_exists('gb_get_field')) {
  function gb_get_field($key, $post_id = null, $args = array())
  {
    // picked up by Integrators
    $value = apply_filters('gutenbricks/gb_get_field', null, $key, $post_id, $args);

    if (!is_null($value)) {
      return $value;
    }
  
    $value = \GutenBricks\Render_Context::gb_get_field($key);

    return $value;
  }
}

