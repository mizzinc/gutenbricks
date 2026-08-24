<?php
namespace Gutenbricks\Integrators;

class Integrator_Acf extends Base_Integrator
{
  public static $plugin_path = 'advanced-custom-fields/acf.php';
  
  public function is_active()
  {
    if (function_exists('get_field') && function_exists('acf_get_field_groups')) {
      return true;
    } else {
      return false;
    }
  }

  public function get_version()
  {
    if (defined('ACF_VERSION')) {
      return ACF_VERSION;
    }

    return '';
  }
  
  public function add_hooks()
  {
    add_filter('acf/pre_load_value', array($this, 'pre_load_value'), 10, 3);
    add_filter('acf/load_reference', array($this, 'acf_load_reference_for_repeater'), 10, 3);
    add_filter('gutenbricks/gb_get_field', array($this, 'gb_get_field'), 10, 4);
    add_filter('gutenbricks/dynamic_fields', array($this, 'extract_acf_fields'), 10, 2);
  }

  // in ACF, pre_load_value is called before the value is loaded from the database and load_value filter.
  // pre_load_value short-circuits the loading process and returns a value to be used instead.
  // @since RC5.1,
  // if we don't use pre_load_value, the blocks will all have the same value as the previous block
  public function pre_load_value($original_value, $post_id, $field)
  {
    $field_name = $field['name'];

    $value = \GutenBricks\Render_Context::$current_gutenberg_attributes['_gutenbricks_meta_data'][$field_name] ?? null;

    // DEBUG:
    // gb_console($field_name, $value);

    if (empty($value)) {
      return $original_value;
    }

    if ($field['type'] === 'repeater') {
      $sub_fields_per_name = array();
      foreach ($field['sub_fields'] as $sub_field) {
        $sub_fields_per_name[$sub_field['name']] = $sub_field;
      }

      foreach ($value as $index => $row) {
        foreach ($row as $name => $sub_value) {
          if (isset($sub_fields_per_name[$name])) {
            $value[$index][$name] = self::format_value($sub_value, $sub_fields_per_name[$name]);
          }
        }
      }

      return $value;
    }

    $field = \GutenBricks\Render_Context::get_current_field($field_name);

    return self::format_value($value, $field);
  }


  public function gb_get_field($value, $key, $post_id, $args)
  {
    // for ACF
    if (strpos($key, 'acf_') === 0) {
      $field_id = str_replace('acf_', '', $key);
      $field = \GutenBricks\Render_Context::get_current_field($field_id);
      $value = get_field($field_id, $post_id);

      return self::format_value($value, $field);
    }

    return $value;
  }

  // Extracts ACF fields within Bricks elements 
  public function extract_acf_fields($fields = array(), $template_id = null)
  {
    if (empty($template_id)) {
      return $fields;
    }

    $field_array = self::get_acf_field_groups($template_id);

    if (empty($field_array)) {
      return $fields;
    }

    foreach ($field_array as $group) {
      $fields[$group['key']] = $group;
    }

    return $fields;
  }


	private static function get_acf_field_groups($template_id)
	{
		$field_groups = acf_get_field_groups(array('post_id' => $template_id)); // Get all field groups

		$all_fields = array();

		foreach ($field_groups as $field_group) {
			$fields = acf_get_fields($field_group['key']);

			if (is_array($fields)) {
				foreach ($fields as $key => $field) {
					$fields[$key]['path'] = $field['name'];
					$all_fields[] = $fields[$key];
				}
				$all_fields = array_merge($all_fields, $fields);
			}
		}

		return $all_fields;
	}




  public static function format_value($value, $field = array(), $context = null)
  {
    // CASE: checking field specific data
    $field_type = $field['type'] ?? '';

    if (empty($field) || empty($field_type)) {
      return $value;
    }

    // STEP: Process all the fields
    $settings = \GutenBricks\Render_Context::$current_element->settings ?? array();

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

      // the way the value is formatted is based on
      // class-acf-field-image::format_value
      switch ($return_format) {
        case 'id':
          if (is_array($value)) {
            return $value['id'] ?? null;
          }

          if (is_object($value)) {
            return $value->ID ?? null;
          }

          return $value;
        case 'url':
          // the value is saved as an int
          // if value is not an int, we return it as is
          if (is_int($value) === false) {
            return $value;
          }

          $value = wp_get_attachment_url($value);

          return $value;
        case 'array':
          // the value is saved as an int
          // if value is not an int, we return it as is
          if (is_int($value) === false) {
            return $value;
          }

          if (function_exists('acf_get_attachment')) {
            $value = acf_get_attachment($value);
          }

          return $value;
      }
    }

    return $value;
  }


  // for acf/load_reference filter 
  // Long story short, when it's a repeater field, we must return null in order for the field value to load correctly
  // Why? Because by some unknown reasons, if reference returns a valid value, the repeater field within
  // _gutenbricks_meta_data will be empty
  public static function acf_load_reference_for_repeater($reference, $field_name, $post_id)
  {
    $repeater_field = \GutenBricks\Render_Context::$current_gutenberg_attributes['_gutenbricks_meta_data'][$field_name] ?? null;

    if (empty($repeater_field)) {
      return $reference;
    }

    return null;
  }

  public function filter__add_editor_options($options = '', $templates = array())
  {
    $acf_fields = array();
    $acf_field_keys = array();

    foreach ($templates as $template) {
      $template_acf_fields = self::extract_acf_fields(array(),$template->ID);
      $acf_fields = array_merge($acf_fields, $template_acf_fields);
      if (!empty($template_acf_fields)) {
        $acf_field_keys[$template->ID] = array_keys($template_acf_fields);
      }
    }

    $options .= "\n
    // ACF fields " . self::$plugin_path . "\n" .
      "gutenBricksClient.addOption({\n" .
      "	acfFields: " . wp_json_encode($acf_fields) . ",\n" .
      "	acfFieldKeys: " . wp_json_encode($acf_field_keys) . ",\n" .
      "});\n";

    return $options;
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
                <b>ACF Settings</b>
              </label>
            </td>
            <td>
              <div style="margin: 0.9em 0px 1.25rem 0rem;">
                <label for="_gutenbricks_acf_settings_name"><b>ACF Settings custom label</b>
                  <span class="info-icon" title="Suggested by Chad Botha">C</span>
                </label>
                <input type="text" name="_gutenbricks_acf_settings_name" id="_gutenbricks_acf_settings_name"
                  style="width:100%; min-width:350px;"
                  value="<?php echo esc_textarea(get_option('_gutenbricks_acf_settings_name')); ?>"
                  placeholder="ACF Settings" />
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

  // Currently Not In Use @since 5.5.1
  public function bricks_dynamic_data_format_value($value, $tag, $post_id, $filters, $context)
  {
     // For ACF, ACF_Adapter::pre_load_value filter is enough return some contents
    // However, we still need this cover tags used within image and color etc
    // 
    // @since 5.5.1 commented out because Bricks convert the value format itself from
    // the value returned from ACF and meta box, which means as long as your values are
    // in the correct format, you don't need to do anything  
    //
    // if (empty($value) && strpos($tag, 'acf_') === 0 && Adapter\Acf_Adapter::acf_exists()) {
    //	$field_name = str_replace('acf_', '', $tag);
    //	$value = Adapter\Acf_Adapter::bricks_dynamic_data_format_value(
    //		self::$current_gutenberg_attributes,
    //		$value,
    //		$field_name,
    //		array(),
    //		$post_id,
    //		$context
    //	);
    // }
    //

    return $value;
  }

  // mainly for bricks/dynamic_data/format_value filter
  // Currently Not In Use @since 5.5.1
  public static function execute_bricks_dynamic_data_format_value(
    $current_attr,
    $original_value,
    $field_name,
    $args = array(),
    $original_id = null,
    $context = null
  ) {
    // WIP: when the value is from the page or post the $original_value will have a value
    if (!function_exists('get_field') || !empty($original_value)) {
      // if (!function_exists('get_field')) {
      return $original_value;
    }

    // STEP: when the value is from block, we need to get the value from the current attributes
    $value = get_field($field_name);

    $field = \GutenBricks\Render_Context::get_current_field($field_name);

    $value = \Gutenbricks\Render_Context::format_dynamic_value($value, $field, $context);

    return $value;
  }



}
