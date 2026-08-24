<?php
namespace Gutenbricks\Integrators;

class Integrator_Wpml extends Base_Integrator
{
  public static $plugin_path = 'sitepress-multilingual-cms/sitepress.php';

  public static $load_template_all_lang = false;

  public function setup()
  {
    self::$load_template_all_lang = get_option('_gutenbricks_wpml_load_template_all_lang');
  }


  public function add_hooks()
  {
    add_action('wpml_pro_translation_completed', [$this, 'wpml_pro_translation_completed'], 9999, 3); // the priority must be low, otherwise it runs before the post and its blocks are updated
    add_filter('gutenbricks/register_templates/query_args', [$this, 'filter__register_templates_query_args'], 10, 2);
    add_filter('gutenbricks/load_templates/query_args', [$this, 'filter__load_templates_query_args'], 10, 1);
    add_filter('gutenbricks/load_template/template_id', [$this, 'filter__load_template_template_id'], 10, 1);
    add_filter('bricks/get_templates/query_vars', [$this, 'filter__bricks_get_templates_query_vars'], 10, 1);
  }


  public function action__register_options()
  {
    register_setting('gutenbricks_options_group', '_gutenbricks_wpml_load_template_all_lang');
  }

  public function action__render_options()
  {
    ?>
    <tr>
      <td style="min-width: 150px;vertical-align:top;">
        <label class="bundle-item">
          <b>WPML</b>
        </label>
      </td>
      <td>
        <label class="bundle-item">
          <input type="checkbox" name="_gutenbricks_wpml_load_template_all_lang" value="1" <?php checked(get_option('_gutenbricks_wpml_load_template_all_lang'), "1"); ?> />
          Load blocks for all languages.
        </label>
      </td>
      <td>
      </td>
    </tr>
    <?php
  }


  // Experimental
  public function wpml_pro_translation_completed($post_id, $data_fields, $job)
  {
    $updater = new \GutenBricks\Block_Value($post_id);
    $updater->update_post_content();
  }

  public function add_attributes_settings($attributes_settings, $elements, $template)
  {
    $attributes_settings['translatedWithWPMLTM'] = [
      'type' => 'string',
    ];

    return $attributes_settings;
  }

  public function filter__register_templates_query_args($args, $templates)
  {
    if (self::$load_template_all_lang) {
      // When loading all languages, suppress WPML's language filters
      $args['suppress_filters'] = true;
    } else {
      // When loading current language only, let WPML handle the filtering
      $args['suppress_filters'] = false;
      
      // Add WPML's language parameter
      if (defined('ICL_LANGUAGE_CODE')) {
        $args['lang'] = ICL_LANGUAGE_CODE;
      }
    }
    return $args;
  }

  public function filter__load_templates_query_args($args)
  {
    if (self::$load_template_all_lang) {
      $args['suppress_filters'] = true;
    }
    return $args;
  }

  public function filter__load_template_template_id($template_id)
  {
    if (self::$load_template_all_lang) {
      $template_id = apply_filters('wpml_object_id', $template_id, GUTENBRICKS_DB_TEMPLATE_SLUG, true);
    }
    return $template_id;
  }

  public function filter__bricks_get_templates_query_vars($args)
  {
    if (self::$load_template_all_lang) {
      $args['suppress_filters'] = true;
    }
    return $args;
  }

}

