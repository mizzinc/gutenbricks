<?php
namespace Gutenbricks;

/**
 * A bridge between Bricks and Gutenbricks providing specific business logics
 * using Bricks API. The class expicitly declares what kind of Bricks API we'll be using.
 * and also add a layer of fault proofing.
 *
 * @package    Gutenbricks
 * @author     WiredWP <ryan@wiredwp.com>
 */
class Bricks_Bridge
{
  public static $gutenbricks_provider;

  public static $supported_bricks_versions = ['1.10.3', '1.11', '1.11.1.1', '1.12.0', '1.12.1'];

  public function __construct()
  {
  }

  /**
   * Check if Bricks is installed and activated.
   *
   * @since 1.0.0 
   * @return bool 
   */
  public static function bricks_exists()
  {
    return class_exists('Bricks\Assets') && defined('BRICKS_VERSION');
  }

  public static function setup_dynamic_data_provider_ssr()
  {
    if (!self::bricks_exists()) {
      return false;
    }

    if (!class_exists('Bricks\Integrations\Dynamic_Data\Providers')) {
      return false;
    }

    if (!gutenbricks_is_ssr_request()) {
      return false;
    }

    $dynamic_data_provider = new \Bricks\Integrations\Dynamic_Data\Providers([
      'cmb2',
      'wp',
      'woo',
      'acf',
      'pods',
      'metabox',
      'toolset',
      'jetengine',
    ]);
    // we need to register tags manually since we can't use hooks to initiate them
    $dynamic_data_provider->register_providers();
    $dynamic_data_provider->register_tags();

    return true;
  }

  public static function is_bricks_valid_version()
  {
    if (!self::bricks_exists()) {
      return false;
    }

    if (in_array(BRICKS_VERSION, self::$supported_bricks_versions)) {
      return true;
    }
  }

  public static function get_query_index()
  {
    if (class_exists('Bricks\Query')) {
      if (\Bricks\Query::is_looping() ) {
        return \Bricks\Query::get_looping_unique_identifier();
      }
    }
    return null;
  }

  public static function get_bricks_element_names()
  {
    if (class_exists('Bricks\Elements')) {
      $elements = \Bricks\Elements::$elements;
      if (empty($elements)) {
        return [];
      }

      $names = array_keys($elements);
      return $names;
    }
  }

  public static function get_template_settings($name, $default = false)
  {
    if (class_exists('Bricks\Helpers')) {
      $settings = \Bricks\Helpers::get_template_settings(get_the_ID());
      if (isset($settings[$name])) {
        return $settings[$name];
      } else {
        return $default;
      }
    }

    return $default;
  }


  public static function get_bricks_inline_styles($post_id)
  {
    if (class_exists('Bricks\Theme_Styles') && class_exists('Bricks\Assets')) {
      // PerformanceMonitor::start('gutenbricks/editor/get_bricks_inline_styles');
      \Bricks\Theme_Styles::load_set_styles($post_id);

      // the reason we don't care about loading CSS file is that this method is being used during SSR
      // TODO: change the name to add SSR in it
      $inline_css = \Bricks\Assets::generate_inline_css($post_id);

      if (\Bricks\Database::get_setting('smoothScroll')) {
        $inline_css = "html {scroll-behavior: smooth}\n" . $inline_css;
      }

      return $inline_css;
      // PerformanceMonitor::end('gutenbricks/editor/get_bricks_inline_styles');
    }
    return '';
  }

  // we pass the settings to enqueue necessary scripts
  public static function enqueue_setting_specific_scripts($settings)
  {
    if (class_exists('Bricks\Assets')) {
      \Bricks\Assets::enqueue_setting_specific_scripts($settings);
    }
  }

  // it's called a single time during SSR
  public static function get_template_inline_style_ssr($template_id, $elements)
  {
    // so we should setup dynamic data provider
    self::setup_dynamic_data_provider_ssr();

    $raw_css = '';

    if (class_exists('Bricks\Templates') && class_exists('Bricks\Assets')) {
      $raw_css .= \Bricks\Templates::generate_inline_css($template_id, $elements);
      $raw_css .= \Bricks\Assets::$inline_css_dynamic_data;
      $raw_css .= \Bricks\Assets::$inline_css['global_classes'];
    }

    return $raw_css;
  }

  public static function bricks_is_builder()
  {
    if (function_exists('bricks_is_builder')) {
      return bricks_is_builder();
    }
    return false;
  }

  // Refer to bricks_is_builder() 
  // In order to load scripts and styles including the third party ones, we need to mimic the builder
  public static function mimic_builder()
  {
    if (defined('BRICKS_BUILDER_PARAM')) {
      $_GET[BRICKS_BUILDER_PARAM] = 'gutenbricks';
    }
  }

  // Refer to bricks_is_builder_iframe()
  // In order to load scripts and styles including the third party ones, we need to mimic the builder
  public static function mimic_builder_iframe()
  {
    if (defined('BRICKS_BUILDER_IFRAME_PARAM')) {
      $_GET[BRICKS_BUILDER_IFRAME_PARAM] = 'gutenbricks';
    }
  }


  // This method is being called during SSR. In order for the elements
  // to enqueue scripts and styles, we need to register them first.
  public static function register_bricks_frontend_scripts()
  {
    if (class_exists('Bricks\Setup')) {
      $bricks_setup = new \Bricks\Setup();
      $bricks_setup->enqueue_scripts();
    }

    if (class_exists('Bricks\Frontend')) {
      $bricks_frontend = new \Bricks\Frontend();
      $bricks_frontend->enqueue_scripts();
    }
  }

  /**
   * Enqueue scripts and styles for Bricks.
   *
   * @since 1.0.0 
   */
  public static function enqueue_bricks_frontend_scripts_from_gb()
  {
    self::register_bricks_frontend_scripts();

    global $wp_styles;

    if (isset($wp_styles->registered['bricks-frontend'])) {
      $wp_styles->registered['bricks-frontend']->src = self::get_bricks_frontend_css_path();
      $wp_styles->registered['bricks-frontend']->ver = GUTENBRICKS_VERSION;
    }
  }


  // CLASS: Gutenbricks\Bricks_Bridge
  public static function get_bricks_frontend_css_path()
  {
    if (!defined('BRICKS_VERSION') || \Gutenbricks\Bricks_Bridge::bricks_is_builder()) {
      return '';
    }

    $bricks_version = BRICKS_VERSION;

    // if the newest Bricks version doesn't have generated styles, we use 'fallback' version
    if (!file_exists(GUTENBRICKS_PLUGIN_DIR . 'admin/editor-assets/bricks-' . $bricks_version . '/frontend.min.css')) {
      $bricks_version = 'fallback';
    }

    if (function_exists('is_rtl') && is_rtl()) {
      return GUTENBRICKS_PLUGIN_URL . 'admin/editor-assets/bricks-' . $bricks_version . '/frontend-rtl.min.css';
    }

    if (\Bricks\Database::get_setting('cssLoading') !== 'file') {
      return GUTENBRICKS_PLUGIN_URL . 'admin/editor-assets/bricks-' . $bricks_version . '/frontend.min.css';
    } else {
      return GUTENBRICKS_PLUGIN_URL . 'admin/editor-assets/bricks-' . $bricks_version . '/frontend-light.min.css';
    }
  }


  public static function enqueue_bricks_editor_scripts()
  {

    if (!self::bricks_exists()) {
      return;
    }

    if (class_exists('Bricks\Helpers') && defined('BRICKS_PATH_ASSETS') && defined('BRICKS_URL_ASSETS')) {
      // Contains common JS libraries & Bricks-specific frontend.js init scripts
      wp_enqueue_script('bricks-scripts', BRICKS_URL_ASSETS . 'js/bricks.min.js', [], filemtime(BRICKS_PATH_ASSETS . 'js/bricks.min.js'), true);

      // Enqueue query filters JS (@since 1.9.6)
      if (method_exists('Bricks\Helpers', 'enabled_query_filters') && \Bricks\Helpers::enabled_query_filters()) {
        wp_enqueue_script('bricks-filters', BRICKS_URL_ASSETS . 'js/filters.min.js', ['bricks-scripts'], filemtime(BRICKS_PATH_ASSETS . 'js/filters.min.js'), true);
      }
    }
  }


  public static function get_capabilities()
  {
    if (class_exists('Bricks\Capabilities')) {
      return new \Bricks\Capabilities();
    }

    return null;
  }

  // not in use since @RC-4.0 
  public static function get_bricks_frontend_file_path()
  {
    if (function_exists('is_rtl') && is_rtl()) {
      return BRICKS_PATH_ASSETS . 'css/frontend-rtl.min.css';
    }
    return BRICKS_PATH_ASSETS . 'css/frontend.min.css';
  }


  // not in use since @RC-4.0 
  public static function get_frontend_css_body_editor()
  {
    $css = file_get_contents(self::get_bricks_frontend_file_path());

    $pattern = '/(?<=^|\s)body\s*{([^}]*)}/';
    preg_match_all($pattern, $css, $matches);

    $combined_css = '';
    foreach ($matches[1] as $match) {
      $combined_css .= $match;
    }

    return ".is-root-container { 
		$combined_css
	}";
  }

  public static function get_elements($template_id, $render_context, $filter_out_disabled = false)
  {
    add_filter('get_post_metadata', array($render_context, 'inject_post_metadata'), 90, 4);
    $elements = get_post_meta($template_id, BRICKS_DB_PAGE_CONTENT, true);
    remove_filter('get_post_metadata', array($render_context, 'inject_post_metadata'), 90);

    if (empty($elements)) {
      return $elements;
    }

    if ($filter_out_disabled === true) {
      $elements = self::filter_out_disabled_elements($elements);
    }

    return $elements;
  }

  public static function filter_out_disabled_elements($elements)
  {
    if (empty($elements)) {
      return $elements;
    }

    $rendered_elements = [];
    $removed_children = [];

    foreach ($elements as $element) {
      if (
        $element['settings']['_gb_disable_rendering'] ?? false === true
        || in_array($element['id'], $removed_children)
      ) {
        if (!empty($element['children'])) {
          $removed_children = array_merge($removed_children, $element['children']);
        }
        continue;
      }

      if (in_array($element['id'], $removed_children)) {
        continue;
      }

      $rendered_elements[] = $element;
    }

    return $rendered_elements;
  }

}

