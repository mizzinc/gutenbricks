<?php
namespace Gutenbricks\Integrators;

class Integrator_If_so extends Base_Integrator
{
  public static $plugin_path = 'if-so/if-so.php'; 

  public static $load_template_all_lang = false;

  public function is_active()
  {
    return is_plugin_active(self::$plugin_path);
  }


  public function add_hooks()
  {
  }

  public function add_attributes_settings($attributes_settings, $elements, $template)
  {
    $attributes_settings['ifso_condition_type'] = array(
      'type'    => 'string',
      'default' => '',
    );
    $attributes_settings['ifso_condition_rules'] = array(
      'type'    => 'object',
    );
    $attributes_settings['ifso_default_exists'] = array(
      'type'    => 'boolean',
      'default' => false,
    );
    $attributes_settings['ifso_default_content'] = array(
      'type'    => 'string',
      'default' => '',
    );
    $attributes_settings['ifso_aud_addrm'] = array(
      'type'    => 'object',
    );
    $attributes_settings['ifso_render_with_ajax'] = array(
      'type'=>'boolean',
      'default'=>false
    );
    $attributes_settings['ajax_loader_type'] = array(
      'type'    => 'string',
      'default' => 'same-as-global',
  );

    return $attributes_settings;
  }


}

