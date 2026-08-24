<?php

namespace GutenBricks\Element;
class Code extends Element_Base
{
  public $name = 'code';

  public function filter_element_settings($settings, $element, $attributes)
  {
    $run_script_only_in_gb = $settings['_gb_run_script_only_in_gb'] ?? false;
    $disable_script_in_gb = $settings['_gb_disable_script_in_gb'] ?? false;
    $should_execute_code = $settings['executeCode'] ?? false;
    $is_ssr_request = gutenbricks_is_ssr_request();

    if ($is_ssr_request) {
      // gutenberg
      if ($disable_script_in_gb || !$should_execute_code) {
        $this->clear_code_settings($settings);
      }
    } else {
      // frontend
      if ($run_script_only_in_gb || !$should_execute_code) {
        $this->clear_code_settings($settings);
      }
    }

    return $settings;
  }

  private function clear_code_settings(&$settings)
  {
    $settings['code'] = null;
    $settings['cssCode'] = null;
    $settings['javascriptCode'] = null;
  }

  public function get_element_controls($options)
  {
    $name = $this->name;
    $template_edit_disabled = $this->template_edit_disabled;
    $is_container = $this->is_container;

    $options['_gb_run_script_title'] = [
      'group' => 'gutenbricks_group',
      'label' => esc_html__('Code Options', 'gutenbricks'),
      'description' => __('Configure the code execution options for this element within Gutenberg Editor. <a href="https://docs.gutenbricks.com/resources/developer-api/gutenbricks-events-for-gutenberg-editor" target="_blank">Learn More</a>', 'gutenbricks'),
      'type' => 'separator',
    ];

    $options['_gb_disable_script_in_gb'] = [
      'group' => 'gutenbricks_group',
      'label' => esc_html__('Disable code execution inside Gutenberg Editor', 'gutenbricks'),
      'type' => 'checkbox',
      'default' => false,
      'description' => 'Please note that CSS and JavaScript intended for frontend might cause unexpected issues within Gutenberg Editor. We recommend to create a code section for Gutenberg Editor.',
    ];
    $options['_gb_run_script_only_in_gb'] = [
      'group' => 'gutenbricks_group',
      'label' => esc_html__('Gutenberg Only: Execute code ONLY in Gutenberg Editor and NOT in frontend', 'gutenbricks'),
      'type' => 'checkbox',
      'default' => false,
      'description' => 'When enabled, the code will only be executed in Gutenberg Editor and not in the frontend, overriding the Execute Code option above.',
      'required' => [
        ['_gb_disable_script_in_gb', '=', false],
      ],
    ];

    include __DIR__ . '/controls/element-show-hide.php';
    include __DIR__ . '/controls/element-rendering.php';

    return $options;
  }

}