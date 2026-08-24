<?php
namespace Gutenbricks\Integrators;

class Integrator_Metabox extends Base_Integrator
{
  public static $plugin_path = 'meta-box/meta-box.php';

  private static $all_tabs = array();
  private static $setting_cached = false;

  // @since 5.5.1
  // $group_restricted_by_template_id and $group_id_per_field_id
  // store data to filter fields based on the template_id 
  private static $group_restricted_by_template_id = array();
  private static $group_id_per_field_id = array();

  public function add_hooks()
  {
    add_filter('gutenbricks/gb_get_field', array($this, 'gb_get_field'), 10, 4);
    add_filter('gutenbricks/dynamic_fields', array($this, 'extract_mb_fields'), 10, 2);
    add_filter('bricks/dynamic_data/format_value', array($this, 'bricks_dynamic_data_format_value'), 10, 5);
  }

  public function is_active()
  {
    if (function_exists('rwmb_get_value')) {
      return true;
    } else {
      return false;
    }
  }


  public function gb_get_field($value, $key, $post_id, $args)
  {
    // for meta box
    if (strpos($key, 'mb_bricks_template_') === 0) {
      $current_attr = \GutenBricks\Render_Context::get_current_gutenberg_attributes();
      $field_id = str_replace('mb_bricks_template_', '', $key);
      $original_value = rwmb_get_value($field_id, $args, $post_id);
      $value = $this->execute_bricks_dynamic_data_format_value(
        $current_attr,
        $original_value,
        $field_id,
      );

      if (!empty($value)) {
        return $value;
      }
    }
  }


  public function bricks_dynamic_data_format_value($value, $tag, $post_id, $filters, $context)
  {
    // CORE: for meta box
    // Meta Box doesn't offer filters to intercept the value
    // This is a gateway to get into 
    if (strpos($tag, 'mb_bricks_template_') === 0) {

      $current_attr = \GutenBricks\Render_Context::get_current_gutenberg_attributes();
      $field_id = str_replace('mb_bricks_template_', '', $tag);
      $value = $this->execute_bricks_dynamic_data_format_value(
        $current_attr,
        $value,
        $field_id,
        array(),
        $post_id,
        $context
      );
    }

    return $value;
  }


  // CLASS: Gutenbricks\Adapter\Mb_Adapter
  public function execute_bricks_dynamic_data_format_value($current_attr, $value, $field_id, $args = array(), $original_id = null, $context = null)
  {
    if (!function_exists('rwmb_get_field_settings')) {
      return $value;
    }

    $template_id = $current_attr['template_id'] ?? null;

    $field = rwmb_get_field_settings($field_id, $args, $template_id);

    // CASE 1: value does not exist within _gutenbricks_meta_data
    // so we return the unformatted raw original value
    if (!isset($current_attr['_gutenbricks_meta_data'])) {
      if (!empty($value)) {
        return $value;
      } else {
        if (isset($field['std'])) {
          return $field['std'];
        }
      }
      return $value;
    }

    $attr = $current_attr['_gutenbricks_meta_data'] ?? array();

    if (!empty($field['id'])) {
      $field_id = $field['id'];
    }

    // CASE 2: value exists within _gutenbricks_meta_data
    // so we return the formatted value
    if (!empty($field_id) && isset($attr[$field_id])) {
      return self::format_value($attr[$field_id], $field, $context);
    }

    if (empty($value)) {
      if (isset($field['std'])) {
        return self::format_value($field['std'], $field, $context);
      }
    }

    $value = self::format_value($value, $field, $context);

    return $value;
  }



  public static function format_value($value, $field = array(), $context = null, $args = array())
  {
    if (empty($value)) {
      if ($context === 'text') {
        // otherwise it throws error
        return '';
      }

      return $value;
    }

    if ($context === 'text' && is_array($value)) {
      return implode(', ', $value);
    }

    // CASE: checking field specific data
    $field_type = $field['type'] ?? '';

    if (empty($field_type) && empty($context)) {
      return $value;
    }

    // STEP: Process all the fields
    $settings = self::$current_element->settings ?? array();

    switch ($field_type) {
      case 'file':
        $return_format = $field['return_format'] ?? 'url';
        if ($return_format === 'url') {
          return $value['url'] ?? null;
        } else if ($return_format === 'array') {
          return $value;
        } else if ($return_format === 'id') {
          return $value['id'] ?? null;
        }
        break;
    }

    if ($field_type === 'image' || $field_type === 'image_select' || $context === 'image') {
      $image_size = $settings['image']['size'] ?? 'full';
      $return_format = $field['return_format'] ?? 'url';

      if (!empty($context) && !empty($value)) {
        // CASE: use context to return value
        switch ($context) {
          case 'text':
            $value = wp_get_attachment_image_src($value, $image_size);
            return $value[0];
          case 'image':
            if (is_array($value) && !empty($value['id'])) {
              return array($value['id']);
            } else if (is_string($value)) {
              return array($value);
            } else if (is_int($value)) {
              $value = wp_get_attachment_image_src($value, $image_size);
            }
            return $value;
        }
      }


      switch ($return_format) {
        case 'id':
          return $value;
        case 'url':
          // the value is saved as an int
          // if value is not an int, we return it as is
          if (is_int($value) === false) {
            return $value;
          }

          $value = wp_get_attachment_image_src($value, $image_size);

          return $value[0];
        case 'array':
          // the value is saved as an int
          // if value is not an int, we return it as is
          if (is_int($value) === false) {
            return $value;
          }

          $value = wp_get_attachment_image_src($value, $image_size);
          return $value;
      }
    }

    return $value;
  }


  public function filter__add_editor_options($options = '', $templates = array())
  {
    $mb_fields = array();
    $mb_field_keys = array();

    foreach ($templates as $template) {
      $template_mb_fields = $this->extract_mb_fields(array(), $template->ID);
      $mb_fields = array_merge($mb_fields, $template_mb_fields);
      if (!empty($template_mb_fields)) {
        $mb_field_keys[$template->ID] = array_keys($template_mb_fields);
      }
    }

    $options .= "\n
    // Meta Box fields " . self::$plugin_path . "\n" .
      "gutenBricksClient.addOption({\n" .
      "	mbFields: " . wp_json_encode($mb_fields) . ",\n" .
      "	mbFieldKeys: " . wp_json_encode($mb_field_keys) . ",\n" .
      "});\n";

    return $options;
  }

  public function extract_mb_fields($return_fields = array(), $template_id = null)
  {
    if (empty($template_id)) {
      return $return_fields;
    }

    self::get_field_settings();

    // tab is not in it, so must load tabs
    $fields = rwmb_get_object_fields($template_id);

    foreach ($fields as $field) {
      if (isset($field['tab']) && isset(self::$all_tabs[$field['tab']])) {
        $return_fields[$field['tab']] = self::$all_tabs[$field['tab']];
      }

      // filter out fields that are not associated the template_id
      // via advanced settings
      $group_id = self::$group_id_per_field_id[$field['id']] ?? null;
      if (!empty($group_id) && !empty(self::$group_restricted_by_template_id[$group_id])) {
        if (in_array($template_id, self::$group_restricted_by_template_id[$group_id]) !== true) {
          continue;
        }
      }

      $return_fields[$field['id']] = $field;
    }

    return $return_fields;
  }


  public function settings($setting_tab)
  {
    if ($setting_tab === 'client-experience') {
      ?>
      <table class="form-table" style="width: auto;">
        <tbody>
          <tr>
            <td style="min-width: 150px;vertical-align:top;">
              <label class="bundle-item">
                <b>Meta Box Settings</b>
              </label>
            </td>
            <td>
              <div style="margin: 0.9em 0px 1.25rem 0rem;">
                <label for="_gutenbricks_mb_settings_name"><b>Meta Box settings custom label</b>
                  <span class="info-icon" title="Suggested by Chad Botha">C</span>
                </label>
                <input type="text" name="_gutenbricks_mb_settings_name" id="_gutenbricks_mb_settings_name"
                  style="width:100%; min-width:350px;"
                  value="<?php echo esc_textarea(get_option('_gutenbricks_mb_settings_name')); ?>"
                  placeholder="Meta Box Settings" />
              </div>
            </td>
            <td>
            </td>
          </tr>
        </tbody>
      </table>
      <?php
    }
  }


  private static function get_field_settings()
  {
    if (self::$setting_cached) {
      return;
    }

    // get tab is a bit painful in Meta Box: START
    $all_tabs = array();
    $registry = rwmb_get_registry('meta_box');
    $all_registry = $registry->all();

    $group_restricted_by_template_id = array();
    $group_id_per_field_id = array();

    foreach ($all_registry as $reg) {

      // AND or OR
      // We don't need it yet
      // $relation = $reg->meta_box['include']['relation'] ?? null;

      $included_id = $reg->meta_box['include']['ID'] ?? null;
      $reg_id = $reg->meta_box['id'] ?? null;

      if (!empty($included_id)) {
        $group_restricted_by_template_id[$reg_id] = $included_id;
      }

      $fields = $reg->meta_box['fields'] ?? array();
      foreach ($fields as $field) {
        $field_id = $field['id'] ?? null;
        $group_id_per_field_id[$field_id] = $reg_id;
      }

      if (is_array($reg->tabs)) {
        foreach ($reg->tabs as $key => $tab) {
          $all_tabs[$key] = $tab;
          $all_tabs[$key]['type'] = 'tab'; // Manually inject type=tab, so it works inside <MetaDataSettings />
          $all_tabs[$key]['key'] = $key; // Manually inject key, so it works inside <MetaDataSettings /> 
        }
      }
    }

    self::$all_tabs = $all_tabs;
    self::$group_restricted_by_template_id = $group_restricted_by_template_id;
    self::$group_id_per_field_id = $group_id_per_field_id;
    self::$setting_cached = true;
  }

}

