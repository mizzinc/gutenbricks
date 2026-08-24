<?php
namespace Gutenbricks\Integrators;

class Integrator_Acss extends Base_Integrator
{
  public static $plugin_path = 'automaticcss-plugin/automaticcss-plugin.php';

  public function action__register_options()
  {
    register_setting('gutenbricks_options_group', '_gutenbricks_acss_preserve_section_padding');
  }

  public function action__render_options()
  {
    ?>
    <tr>
      <td style="min-width: 150px;vertical-align:top;">
        <label class="bundle-item">
          <b>ACSS</b>
        </label>
      </td>
      <td>
        <label class="bundle-item">
          <input type="checkbox" name="_gutenbricks_acss_preserve_section_padding" value="1" <?php checked(get_option('_gutenbricks_acss_preserve_section_padding'), "1"); ?> />
          ACSS 2.8.0 - <b>Preserve section padding</b> rendered within the Post Content element. Find out more
          detail <a
            href="https://docs.gutenbricks.com/third-party-integrations/acss-integration-with-gutenbricks/acss-2.8.0-section-padding-missing"
            target="_blank">here.</a>
          <span class="info-icon" title="Special thanks to Dave Foy and Chad Botha">C</span>
        </label>
      </td>
      <td>
      </td>
    </tr>
    <?php
  }

  // Modify ACSS assets to work with Gutenberg editor and GutenBricks 
  public function generate_admin_assets($new_generated = [])
  {
    $files = $this->get_acss_files();
    $acss_generated = false;
    foreach ($files as $file_path) {
      $file_name = basename($file_path);
      $result = \Gutenbricks\Utilities\CSSScoper::scope_css_file('/automatic-css', $file_path, $file_name);
      if ($result) {
        $acss_generated = true;
      }
    }
    if ($acss_generated) {
      $new_generated[] = 'Automatic CSS';
    }

    return $new_generated;
  }

  // in ACSS, when you save the settings, 
  // it generates css files inside /wp-content/uploads/automatic-css/
  public function filter__enqueue_admin_styles($styles = [])
  {
    $files = $this->get_acss_files();
    $uploads_dir = wp_upload_dir();
    foreach ($files as $key => $file_path) {
      // we need to add version to the file to avoid caching
      $version = filemtime($file_path);
      // Order of the files is important to make specific styles work
      $styles["gutenbricks-acss-css"] = $uploads_dir['baseurl'] . '/gutenbricks/automatic-css/automatic.css?ver=' . $version;
      $styles["gutenbricks-acss-bricks-css"] = $uploads_dir['baseurl'] . '/gutenbricks/automatic-css/automatic-bricks.css?ver=' . $version;

      // NOTE: This must be loaded conditionally?
      // $styles["gutenbricks-acss-variables"] = $uploads_dir['baseurl'] . '/gutenbricks/automatic-css/automatic-variables.css?ver=' . $version;
    }

    return $styles;
  }


  public function wp_head_styles()
  {
    if (get_option('_gutenbricks_acss_preserve_section_padding') === '1') {
      if ($this->version >= '2.8.0') {
        // Nested section patch for version 2.8.0
        ?>
        <style id="gutenbricks-acss-nested-section-patch">
          /* GutenBricks ACSS nested section patch */
          :where(.brxe-post-content section:not(.brxe-post-content section section)) {
            padding-block: var(--section-space-m);
            padding-inline: var(--section-padding-x);
          }

          :where(.gutenbricks-post-content section:not(.gutenbricks-post-content section section)) {
            padding-block: var(--section-space-m);
            padding-inline: var(--section-padding-x);
          }
        </style>
        <?php
      }
    }
  }

  // Get ACSS files
  // suggested class: Gutenbricks\Integration\ACSS
  private function get_acss_files()
  {
    $result_files = array();
    $uploads_dir = wp_upload_dir();
    $acss_dir = $uploads_dir['basedir'] . '/automatic-css/';

    if (file_exists($acss_dir)) {
      $files = scandir($acss_dir);
      foreach ($files as $file) {
        if (strpos($file, '.css') !== false) {
          // @since rc-2.8, reported by Dave Foy
          // we remove Gutenberg files from the list and let ACSS handles it
          if (strpos($file, 'gutenberg') !== false || strpos($file, 'block-editor') !== false) {
            continue;
          } else {
            $result_files[] = $acss_dir . $file;
          }
        }
      }
    }

    return $result_files;
  }

}