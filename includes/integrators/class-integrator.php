<?php
namespace Gutenbricks\Integrators;

class Base_Integrator
{
  public static $plugin_path;

  public $version;
  public $is_active;

  public function __construct() {
    if (!isset(static::$plugin_path)) {
      return;
    }

    $this->is_active = $this->is_active();

    if ($this->is_active) {
      $this->version = $this->get_version();
      $this->add_default_hooks();
      if (method_exists($this, 'setup')) {
        $this->setup();
      }
    }
  }

  protected function add_default_hooks() {
    add_action('wp_head', [$this, 'wp_head_styles']);
    add_action('gutenbricks/editor/block/pre_process', [$this, 'action__pre_process_block'], 10, 3);
    add_filter('gutenbricks/editor/integration/enqueue_styles', [$this, 'filter__enqueue_admin_styles']);
    add_filter('gutenbricks/editor/integration/add_options', [$this, 'filter__add_editor_options'], 10, 2);
    add_action('gutenbricks/block/attributes_settings', [$this, 'add_attributes_settings'], 10, 3);
    add_action('gutenbricks/register_options', [$this, 'action__register_options']);
    add_action('gutenbricks/integration/render_options', [$this, 'action__render_options']);

    if (method_exists($this, 'add_hooks')) {
      $this->add_hooks();
    }
  }

  public function action__register_options() {
    // do nothing
  }

  public function action__render_options() {
    // do nothing
  }

  public function add_attributes_settings($attributes_settings, $elements, $template) {
    return $attributes_settings;
  }

  protected function get_version() {
    if (empty(static::$plugin_path)) return '';

    if (!file_exists(WP_PLUGIN_DIR . '/' . static::$plugin_path)) {
      return '';
    }

    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . static::$plugin_path);
    return $plugin_data['Version'] ?? '';
  }

  public function is_active() {
    // Check if the plugin file exists
    if (!file_exists(WP_PLUGIN_DIR . '/' . static::$plugin_path)) {
        return false;
    }

    $is_active = is_plugin_active(static::$plugin_path);

    return $is_active;
  }

  public function wp_head_styles() {
    // do nothing
  }

  public function filter__enqueue_admin_styles($styles = []) {
    return $styles;
  }

  public function action__pre_process_block($template_id, $elements, $block) {
    // do nothing
  }

  public function filter__add_editor_options($options = '', $templates = array()) {
    return $options;
  }

  public function settings($setting_tab) {
    // do nothing
  }
}
