<?php
namespace Gutenbricks\Integrators;

class Integrator_Coreframework extends Base_Integrator
{
  public static $plugin_path = 'core-framework/core-framework.php';

  public function filter__enqueue_admin_styles($styles = [])
  {
    // if (CORE_FRAMEWORK_VERSION < )
    if (is_multisite()) {
      $styles["gutenbricks-core-framework"] = plugins_url() . '/core-framework/assets/public/css/core_framework_' . get_current_blog_id() . '.css';
    } else {
      $styles["gutenbricks-core-framework"] = plugins_url() . '/core-framework/assets/public/css/core_framework.css';
    }

    return $styles;
  }
  
}