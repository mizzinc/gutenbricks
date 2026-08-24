<?php

$options['_gb_sep_class'] = [
  'label' => esc_html__('Dynamic Class', 'gutenbricks'),
  'tab' => 'content',
  'group' => 'gutenbricks_group',
  'type' => 'separator',
  'description' => 'Use dynamic data to control classes.. <a style="display:none;" href="#" target="_blank">Learn More</a>',
];

$options['_gb_dynamic_class'] = [
  'label' => esc_html__('Dynamic Class', 'gutenbricks'),
  'group' => 'gutenbricks_group',
  'type' => 'text',
  'tab' => 'content',
  'hasDynamicData' => true,
  'placeholder' => "class-name {dynamic_data} {another_dynamic_data}",
];
