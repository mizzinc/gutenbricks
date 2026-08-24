<?php
/**
 *
 * @since    1.0.0
 */
if (!function_exists('gutenbricks_is_option_page')) {
  function gutenbricks_is_option_page()
  {
    return isset($_GET['page']) && strpos($_GET['page'], 'gutenbricks') !== false;
  }
}

/**
 * check if the current post type is enabled for gutenbricks
 *
 * @since    1.0.0
 */
if (!function_exists('if_gutenbricks_enabled_for_post_type')) {
  function if_gutenbricks_enabled_for_post_type()
  {
    /*
     * Hotfix for the issue where the editor is not loading for the specified post types
     * we keep it true until we add an option to enable/disable the editor for the specified post types
     * @since 1.0.0-rc.2.16 
     */
    return true;
  }
}

if (!function_exists('is_post_gutenbricks_template')) {
  function is_post_gutenbricks_template()
  {
    $post_id = isset($_GET['post']) ? $_GET['post'] : false;

    if (empty($post_id)) {
      global $post;
      $post_id = empty($post) ? false : $post->ID;
    }

    if (!$post_id) {
      // Try to get post ID from URL
      $current_url = $_SERVER['REQUEST_URI'];
      
      // Extract post ID from URL if it's a template URL
      if (preg_match('/\/template\/([^\/\?]+)/', $current_url, $matches)) {
        $template_slug = $matches[1];
        
        // Try to get template by slug
        $template = get_page_by_path($template_slug, OBJECT, 'bricks_template');
        if ($template) {
          $post_id = $template->ID;
        } else {
          // If not found by slug, try to get by name
          $template = get_page_by_title($template_slug, OBJECT, 'bricks_template');
          if ($template) {
            $post_id = $template->ID;
          }
        }
      }
    }

    if (!$post_id) {
      return false;
    }

    $template_type = get_post_meta($post_id, GUTENBRICKS_DB_TEMPLATE_TYPE, true);
    
    return $template_type === GUTENBRICKS_TEMPLATE_TYPE_BLOCK;
  }
}

if (!function_exists('gutenbricks_namespaced_name')) {
  function gutenbricks_namespaced_name($category, $name)
  {
    $category = preg_replace('/[^a-zA-Z0-9]+/', '-', $category);
    $name = preg_replace('/[^a-zA-Z0-9]+/', '-', $name);
    return $category . "/" . GUTENBRICKS_PREFIX . $name;
  }
}


if (!function_exists('gutenbricks_prefixed_name')) {
  function gutenbricks_prefixed_name($name)
  {
    return GUTENBRICKS_PREFIX . $name;
  }
}


if (!function_exists('gutenbricks_get_gb_id')) {
  function gutenbricks_get_gb_id($attribute_id, $block_id = '')
  {
    // this means it is a unique ID where we need to remove the suffix
    if (strlen($attribute_id) > 6) {
      if (empty($block_id)) {
        $parts = explode('_', $attribute_id);
        $attribute_id = $parts[0];
      } else {
        $attribute_id = str_replace(GUTENBRICKS_UNIQUE_ID_CONNECTOR . $block_id, '', $attribute_id);
      }
    }
    return "gb-" . $attribute_id;
  }
}


if (!function_exists('gutenbricks_is_rest_call')) {
  function gutenbricks_is_rest_call()
  {
    return defined('REST_REQUEST') && REST_REQUEST;
  }
}




if (!function_exists('gutenbricks_is_ssr_request')) {
  function gutenbricks_is_ssr_request()
  {
    if (strpos($_SERVER['REQUEST_URI'], 'wp/v2/block-renderer')) {
      // The current request is a REST API request
      return true;
    } else {
      // The current request is not a REST API request
      return false;
    }
  }
}

if (!function_exists('gutenbricks_strip_off_id_prefix')) {
  function gutenbricks_strip_off_id_prefix($attribute_id)
  {
    return substr($attribute_id, 3);
  }
}

if (!function_exists('gutenbricks_if_registering_frontend_assets')) {
  function gutenbricks_if_registering_frontend_assets()
  {
    $post_id = esc_html($_GET['gutenbricks-post-id'] ?? '');

    if (!wp_verify_nonce(esc_html($_GET['nonce'] ?? ''), 'gutenbricks-nonce-' . $post_id)) {
      return false;
    }

    if (!function_exists('current_user_can')) {
      return false;
    }

    if (
      !empty($post_id)
      && (current_user_can('edit_page', $post_id)
        || current_user_can('edit_post', $post_id))
    ) {
      return true;
    }
    return false;
  }
}

/*
 * Check if the current page is a Gutenberg page
 */
if (!function_exists('gutenbricks_if_gutenberg_editor')) {
  function gutenbricks_if_gutenberg_editor()
  {
    if (function_exists('\Gutenbricks\Bricks_Bridge::bricks_is_builder') && \Gutenbricks\Bricks_Bridge::bricks_is_builder()) {
      return false;
    }

    if (function_exists('bricks_is_builder_iframe') && bricks_is_builder_iframe()) {
      return false;
    }

    if (!is_admin()) {
      return false;
    }

    if (function_exists('is_gutenberg_page') && is_gutenberg_page()) {
      return true;
    }

    if (!function_exists('get_current_screen')) {
      require_once ABSPATH . '/wp-admin/includes/screen.php';
    }

    $current_screen = get_current_screen();
    if (isset($current_screen) && method_exists($current_screen, 'is_block_editor') && $current_screen->is_block_editor()) {
      return true;
    }

    return false;
  }
}

/*
 * Check if the current page is a Gutenberg settings page
 */
if (!function_exists('gutenbricks_if_settings_page')) {
  function gutenbricks_if_settings_page()
  {
    if (!is_admin()) {
      return false;
    }

    $screen = get_current_screen();

    if (empty($screen)) {
      return false;
    }

    if (strpos($screen->id, 'bricks_page_gutenbricks') !== false) {
      return true;
    }

    return false;
  }
}

if (!function_exists('gb_dump')) {
  function gb_dump($name, $value)
  {
    echo '<pre>';
    echo '<b>' . $name . '</b><br/>';
    var_dump($value);
    echo '</pre>';
  }
}


if (!function_exists('gutenbricks_is_dev_env')) {
  function gutenbricks_is_dev_env()
  {
    return defined('GUTENBRICKS_MODE') && GUTENBRICKS_MODE === 'dev';
  }
}

if (!function_exists('gb_console')) {
  function gb_console($name, $value)
  {
    if (gutenbricks_is_ssr_request() || gutenbricks_is_rest_call()) {
      return;
    }
    
    if (!gutenbricks_is_dev_env()) {
      return;
    }
    
    ?>
    <script>
      console.log('<?php echo $name; ?>', <?php echo wp_json_encode($value); ?>);
    </script>
    <?php
  }
}

// FOR DEBUGGING ONLY
if (!function_exists('gb_log')) {
  function gb_log($dataObject, $url = "http://localhost:3168/log")
  {
    // Initialize curl
    $ch = curl_init($url);

    // Set curl options
    curl_setopt($ch, CURLOPT_POST, 1);                    // Set method to POST
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        // Return the response as a string
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']); // Set header for JSON
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataObject)); // Send JSON encoded data

    // Execute the request and fetch the response
    $response = curl_exec($ch);

    // Check for curl errors
    if (curl_errno($ch)) {
      // echo 'Error:' . curl_error($ch);
    } else {
      // echo 'Response from Node app: ' . $response;
    }

    // Close curl
    curl_close($ch);
  }

}


if (!function_exists('gb_debug_print_backtrace')) {
  function gb_debug_print_backtrace($startIndex = 1, $endIndex = 2)
  {
    $trace = debug_backtrace();
    $length = count($trace);
    $startIndex = max($startIndex, 0);  // Ensure start index is not below 0
    $endIndex = min($endIndex, $length - 1);  // Ensure end index does not exceed available stack

    $output = "=== GutenBricks Backtrace ===\n";

    for ($i = $startIndex; $i <= $endIndex; $i++) {
      if (isset($trace[$i])) {
        $output .= "Trace Index: $i\n";
        $output .= "Function: " . $trace[$i]['function'] . "\n";
        $output .= isset($trace[$i]['file']) ? "File: " . $trace[$i]['file'] . "\n" : "File: N/A\n";
        $output .= isset($trace[$i]['line']) ? "Line: " . $trace[$i]['line'] . "\n" : "Line: N/A\n";
        $output .= "\n";
      }
    }
    
    $output .= "=== End Backtrace ===\n";
    error_log($output);
  }
}


if (!function_exists('gb_current_element_id')) {
  function gb_current_element_id()
  {
    return \GutenBricks\Render_Context::get_current_element_id();
  }
}

if (!function_exists('gutenbricks_get_current_locale')) {
  function gutenbricks_get_current_locale()
  {
    if (function_exists('pll_current_language')) {
      return pll_current_language();
    }

    return get_locale();
  }
}
