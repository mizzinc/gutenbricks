<?php

namespace Gutenbricks;

class Injector
{
  /*
   * Inject the style values to the settings.
   * 
   * We are using Render_Context::append_gutenbricks_obj_value() to inject
   * custom dynamic value. This is to take advantage of how Bricks process
   * the dynamic data because this way, it generates the correct dynamic CSS
   * in the footer.
   */
  public static function InjectStyle($new_id, $settings, $style)
  {
    $elem_fields = $settings['_gb_elem_fields'];

    foreach ($elem_fields as $elem_field) {
      if (empty($elem_field['target'])) {
        continue;
      }

      switch ($elem_field['target']) {
        case '_background.image':
          $tags = self::get_style_editor_tag($new_id, '_background.image');
          $key = $tags['key'];
          $tag = $tags['tag'];

          if (!empty($style['_background.image']['_v'])) {
            if (empty($settings['_background'])) {
              $settings['_background'] = array();
            }

            $value_id = $style['_background.image']['_v'];
            $size = $settings['_background']['image']['size'] ?? 'full';

            $full_value = wp_get_attachment_image_src($value_id, 'full');
            $value = wp_get_attachment_image_src($value_id, $size);

            if (empty($value)) {
              $src = wp_get_attachment_url($value_id);

              if (empty($src)) {
                return $settings;
              }
            } else {
              $src = $value[0];
            }

            Render_Context::append_gutenbricks_obj_value($key, $src);

            if (!empty($src)) {
              $settings['_background']['image'] = array(
                'useDynamicData' => $tag,
                'size' => $size,
              );
            }
          }
          break;
        case '_background.color':
          $tags = self::get_style_editor_tag($new_id, '_background_color');
          $key = $tags['key'];
          $tag = $tags['tag'];

          if (!empty($style['_background.color']['_v'])) {
            if (empty($settings['_background'])) {
              $settings['_background'] = array();
            }

            $value = $style['_background.color']['_v'];

            Render_Context::append_gutenbricks_obj_value($key, $value);

            $settings['_background']['color'] = array(
              'raw' => $tag
            );
          }
          break;
        case '_border.color':
          $tags = self::get_style_editor_tag($new_id, '_border.color');
          $key = $tags['key'];
          $tag = $tags['tag'];

          // Border Color
          if (!empty($style['_border.color']['_v'])) {
            if (empty($settings['_border'])) {
              $settings['_border'] = array();
            }

            $value = $style['_border.color']['_v'];

            Render_Context::append_gutenbricks_obj_value($key, $value);

            $settings['_border']['color'] = array(
              'raw' => $tag
            );
          }
          break;
        case '_typography.color':
          $tags = self::get_style_editor_tag($new_id, '_typography.color');
          $key = $tags['key'];
          $tag = $tags['tag'];

          if (!empty($style['_typography.color']['_v'])) {
            if (empty($settings['_typography'])) {
              $settings['_typography'] = array();
            }

            $value = $style['_typography.color']['_v'];

            Render_Context::append_gutenbricks_obj_value($key, $value);

            $settings['_typography']['color'] = array(
              'raw' => $tag
            );
          }
          break;
      }
    }

    return array(
      'settings' => $settings,
    );
  }

  private static function get_style_editor_tag($new_id, $target)
  {
    $key = $new_id . '_' . $target;
    return array(
      'key' => $key,
      'tag' => '{' . GUTENBRICKS_META_PREFIX . $key . '}',
    );
  }

  private static function assign_key_to_color($color, $key)
  {
    if (isset($color['hex'])) {
      $color['hex'] = '{' . $key . '}';
    }

    if (isset($color['rgb'])) {
      $color['rgb'] = '{' . $key . '}';
    }

    if (isset($color['hsl'])) {
      $color['hsl'] = '{' . $key . '}';
    }

    if (isset($color['raw'])) {
      $color['raw'] = '{' . $key . '}';
    }

    return $color;
  }

  private static function extract_color($color)
  {
    if (isset($color['color']['hex'])) {
      return $color['color']['hex'];
    }

    if (isset($color['color']['rgb'])) {
      return $color['color']['rgb'];
    }

    if (isset($color['color']['hsl'])) {
      return $color['color']['hsl'];
    }

    if (isset($color['color']['raw'])) {
      return $color['color']['raw'];
    }

    return '';
  }

  public static function get_color($color_type, $value)
  {
    switch ($color_type) {
      case 'hex':
        return array(
          'hex' => $value,
        );
      case 'rgb':
        return array(
          'rgb' => $value,
        );
      case 'hsl':
        return array(
          'hsl' => $value,
        );
      case 'raw':
        return array(
          'raw' => bricks_render_dynamic_data($value),
        );
      default:
        return $value;
    }
  }

  // where you consume the attributes and apply gutenberg attributes to bricks element
  public static function inject_gutenberg_content($settings, $element, $gutenberg_attr)
  {
    if (is_object($element)) {
      $name = $element->name;
      $element_id = $element->id;
    } else {
      $name = $element['name'];
      $element_id = $element['id'];
    }

    $attributes = $gutenberg_attr;

    $settings = apply_filters('gutenbricks/element/' . $name . '/inject_attributes_to_settings', $settings, $element, $attributes);
    $settings = apply_filters('gutenbricks/element/' . $name . '/filter_settings', $settings, $element, $attributes);

    // TODO: Make these fields work
    switch ($name) {
      case 'testimonials':
        if (isset($attributes['items'])) {
          $settings['items'] = $attributes['items'];
        }
        break;
      case 'icon-box':
        if (isset($attributes['content'])) {
          $settings['content'] = $attributes['content'];
        }
        break;
      case 'tabs':
        if (isset($attributes['tabs'])) {
          $settings['tabs'] = $attributes['tabs'];
        }
        break;
      case 'form':
        foreach ($attributes as $key => $field) {
          if ($key === 'fields' && !empty($field)) { // such as fields
            foreach ($field as $field_key => $field_value) {
              $idx = intval(str_replace('i_', '', $field_key));
              if (empty($settings[$key][$idx])) {
                $settings[$key][$idx] = array();
              }
              if (is_array($settings[$key][$idx]) && is_array($field_value)) {
                $settings[$key][$idx] = array_merge($settings[$key][$idx], $field_value);
              }
            }

          } else {
            $settings[$key] = $field;
          }
        }
        break;
      case 'carousel':
        if (isset($settings['items']['images']) && is_array($attributes['items']['images'])) {
          foreach ($attributes['items']['images'] as $key => $image) {
            $idx = intval(str_replace('i_', '', $key));
            $settings['items']['images'][$idx] = $image;
          }
          if (is_array($settings['items']['images']) && is_array($attributes['items']['images'])) {
            $settings['items']['images'] = array_merge(
              $settings['items']['images'],
              $attributes['items']['images']
            );
          }
        }
        break;
    }

    if (isset($settings['link']) && isset($settings['_original_tag']) && $settings['_original_tag'] === 'a' && isset($attributes['link'])) {
      $settings['link'] = $attributes['link'];
    }

    return $settings;
  }


  public static function prepareStyleFields($element, $gb_id)
  {
    $settings = $element['settings'];

    /*
     * Default Value
     * When a style editor is loaded in Gutenberg editor,
     * we need to set the default value for the fields when the field is empty.
     * The default values are derived from the current settings.
     */
    foreach ($settings['_gb_elem_fields'] as $key => $field) {
      if (empty($field['target'])) {
        continue;
      }

      $target = $field['target'];

      // STEP: replace gb_id with bind_name
      $settings['_gb_elem_fields'][$key]['gbId'] = $gb_id;
      $settings['_gb_elem_fields'][$key]['elementId'] = $element['id'];

      switch ($target) {
        case '_background.image':
          $settings['_gb_elem_fields'][$key]['default_value'] = $settings['_background']['image']['id'] ?? null;
          break;
        case '_background.color':
          if (isset($field['color_value_type'])) {
            $settings['_gb_elem_fields'][$key]['default_value'] = $settings['_background']['color'][$field['color_value_type']] ?? null;
          }
          break;
        case '_border.color':
          if (isset($field['color_value_type'])) {
            $settings['_gb_elem_fields'][$key]['default_value'] = $settings['_border']['color'][$field['color_value_type']] ?? null;
          }
          break;
        case '_typography.color':
          if (isset($field['color_value_type'])) {
            $settings['_gb_elem_fields'][$key]['default_value'] = $settings['_typography']['color'][$field['color_value_type']] ?? null;
          }
          break;
      }
    }

    return $settings;
  }

}