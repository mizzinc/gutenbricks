<?php

$options['_gb_sep_data'] = [
  'tab' => 'content',
  'group' => 'gutenbricks_group',
  'label' => esc_html__('Element Value', 'gutenbricks'),
  'description' => '',
  'type' => 'separator',
  'required' => ['_gb_disable_edit', '!=', true],
];

$options['_gb_binding_name'] = [
  'label' => 'Value Binding Name',
  'type' => 'text',
  'hasDynamicData' => false,
  'description' => 'Useful when swapping element and working with conditional variations. Element with the same value binding name will share the same value.. <a style="display:none;" href="#" target="_blank">Learn More</a>',
  'group' => 'gutenbricks_group',
  'placeholder' => 'case_sensitive_name',
  'tab' => 'content',
  'required' => ['_gb_disable_edit', '!=', true],
];