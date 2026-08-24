<?php
/**
 * @link              https://gutenbricks.com
 * @since             1.0.0
 * @package           GutenBricks
 *
 * @wordpress-plugin
 * Plugin Name:       GutenBricks
 * Plugin URI:        https://gutenbricks.com
 * Description:       Bridging Bricks Builder and Gutenberg Editor for a better developer and client experience.
 * Version:           1.1.26.1
 * Requires at least: 6.3
 * Tested up to:      6.5.5
 * Requires PHP:      7.0
 * Author:            WiredWP
 * Author URI:        https://wiredwp.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gutenbricks
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die;
}

define('GUTENBRICKS_VERSION', '1.1.26.1');
define('GUTENBRICKS_PLUGIN_NAME', 'gutenbricks');
define('GUTENBRICKS_PLUGIN_FILE', __FILE__);
define('GUTENBRICKS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GUTENBRICKS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GUTENBRICKS_DB_TEMPLATE_SLUG', 'bricks_template');
define('GUTENBRICKS_DB_TEMPLATE_TYPE', '_bricks_template_type');
define('GUTENBRICKS_DB_TEMPLATE_TAX_BUNDLE', 'template_bundle');
define('GUTENBRICKS_PREFIX', 'gutenb-');
define('GUTENBRICKS_META_PREFIX', 'gbmeta_');
define('GUTENBRICKS_PREFIX_TEMPLATE', 'gutenb-template-');
define('GUTENBRICKS_DB_PAGE_CONTENT', '_bricks_page_content_2');
define('GUTENBRICKS_TEMPLATE_TYPE_BLOCK_PAGE', 'gutenbricks_block_page');
define('GUTENBRICKS_TEMPLATE_TYPE_BLOCK', 'gutenbricks_block');
define('GUTENBRICKS_DB_TEMPLATE_SETTINGS', '_bricks_template_settings');
define('GUTENBRICKS_DB_PAGE_SETTINGS', '_bricks_page_settings' );
define('GUTENBRICKS_UNIQUE_ID_CONNECTOR', '_');
define('GUTENBRICKS_DEFAULT_PAGE_TEMPLATE_NAME', 'Default Page Template');
define('GUTENBRICKS_NONCE', 'gutenbricks_nonce');

if (GUTENBRICKS_VERSION === '1.1.26.1') {
  define('GUTENBRICKS_MODE', 'dev');
}

/**
 * Turning off error and warning message when serving dynamic Javascript 
 */

if (!empty ($_GET['gutenbricks-post-id'])) {
  ini_set('display_errors', 0);
  ini_set('display_startup_errors', 0);
  error_reporting(0);
}

function activate_gutenbricks()
{
  require_once plugin_dir_path(__FILE__) . 'includes/class-gutenbricks-activator.php';
  Gutenbricks_Activator::activate();
}

function deactivate_gutenbricks()
{
  require_once plugin_dir_path(__FILE__) . 'includes/class-gutenbricks-deactivator.php';
  Gutenbricks_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_gutenbricks');
register_deactivation_hook(__FILE__, 'deactivate_gutenbricks');

require plugin_dir_path(__FILE__) . 'includes/class-gutenbricks.php';
require plugin_dir_path(__FILE__) . 'includes/utilities.php';
require plugin_dir_path(__FILE__) . 'includes/gb-get-field.php';

function prepare_gutenbricks()
{
  add_action('init', 'run_gutenbricks');

  $gutenbricks = new Gutenbricks_Core();
  $gutenbricks->run();
}

function run_gutenbricks()
{
  if (is_admin()) {
    if (gutenbricks_is_bricks_active() !== true) {
      add_action('admin_notices', function () {
        ?>
        <div class="notice notice-error is-dismissible">
          <p>
            <?php _e('GutenBricks requires Bricks Builder theme to be installed and activated.', 'gutenbricks'); ?>
          </p>
        </div>
        <?php
      });
      return;
    }
  }

  require_once plugin_dir_path(__FILE__) . 'includes/core/class-bricks-bridge.php';


  if (Gutenbricks\Bricks_Bridge::bricks_exists() !== true) {
    add_action('admin_notices', function () {
      ?>
      <div class="notice notice-error is-dismissible">
        <p>
          <?php _e('GutenBricks requires Bricks Builder theme to be installed and activated.', 'gutenbricks'); ?>
        </p>
      </div>
      <?php
    });
    return;

  }

  if (
    Gutenbricks\Bricks_Bridge::is_bricks_valid_version() !== true
  ) {
    add_action('admin_notices', function () {
      ?>
        <script>
          console.warn(
            '[GutenBricks]',
            <?php echo json_encode('Gutenbricks is not tested for the current Bricks Builder version (' . BRICKS_VERSION . '). Supported versions: ' . join(', ', Gutenbricks\Bricks_Bridge::$supported_bricks_versions)); ?>
          );
        </script>
      <?php
    });
    // Removed @since 1.0.0-rc.2.17
    // We will allow the plugin to run even if the Bricks version is not supported.
    // return;
  }


}

function gutenbricks_is_bricks_active()
{
  $theme = wp_get_theme();
  $theme_folder = basename($theme->get_stylesheet_directory());
  $parent_theme_folder = basename($theme->get_template_directory());
 
  // Since RC 5.3.4
  // We are allowing the theme to be activated even if the theme name is not exactly "bricks"
  // This is to support child themes and other themes that are based on Bricks
  // Requested by Michael
  $bricks_theme_name = apply_filters('gutenbricks_bricks_theme_name', 'bricks');

  if (
    $theme_folder === $bricks_theme_name
    || $parent_theme_folder === $bricks_theme_name
    || strpos($theme_folder, $bricks_theme_name) !== false
    || strpos($parent_theme_folder, $bricks_theme_name) !== false
  ) {
    return true;
  }

  return false;
}

if (!class_exists('GutenBricks\SureCart\Licensing\Client')) {
  require_once __DIR__ . '/includes/sdk/src/Client.php';
}



prepare_gutenbricks();



