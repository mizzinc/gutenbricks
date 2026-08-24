<?php
namespace Gutenbricks;

/**
 * The admin-specific functionality of the plugin.
 *
 * Handles integrations with Bricks builder add-ons and plugins. 
 *
 * @package    Gutenbricks
 * @author     WiredWP <ryan@wiredwp.com>
 */
class Integration
{
  private $activated_plugins = array();

  private static $supported_integrators = array(
    'acf',
    'metabox',
    'acss',
    'bricksextras',
    'coreframework',
    'polylang',
    'wpml',
    'if_so',
  );

  private static $integrators = array();

  public static $active_integrators = array();
  
  public function __construct()
  {
    $this->require_built_in_integrators();
  }

  public function require_built_in_integrators()
  {
    require_once GUTENBRICKS_PLUGIN_DIR . 'includes/integrators/class-integrator.php';
    
    foreach (self::$supported_integrators as $integrator) {
      require_once GUTENBRICKS_PLUGIN_DIR . 'includes/integrators/class-integrator-' . $integrator . '.php';
    }

  }

  public function load_integrators($integrators = array())
  {
    $all_integrators = array_merge(self::$supported_integrators, $integrators);

    $all_integrators = apply_filters('gutenbricks/integrators/register', $all_integrators);
      
    // instantiate the integrators
    foreach ($all_integrators as $integrator) {
      $class_name = 'Gutenbricks\\Integrators\\Integrator_' . ucfirst($integrator);
      if (class_exists($class_name)) {
        $this_integrator = new $class_name();
        self::$integrators[$integrator] = $this_integrator;
        if ($this_integrator->is_active) {
          self::$active_integrators[$integrator] = $this_integrator;
        }
      }
    }
  }

  // Modify third party assets to work with Gutenberg editor
  public function generate_integration_assets()
  {
    $new_generated = array();

    foreach (self::$active_integrators as $integrator) {
      if (method_exists($integrator, 'generate_admin_assets') && $integrator->is_active) {
        $new_generated = $integrator->generate_admin_assets($new_generated);
      }
    }

    return $new_generated;
  }

  public static function settings($setting_tab) {
    foreach (self::$active_integrators as $integrator) {
      if (method_exists($integrator, 'settings') && $integrator->is_active) {
        $integrator->settings($setting_tab);
      }
    }
  }

  public static function is_active($name) {
    return isset(self::$active_integrators[$name]);
  }
}