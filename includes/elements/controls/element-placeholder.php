<?php
// PLACEHOLDER
$options['_gb_sep_placeholder'] = [
  'tab' => 'content',
  'group' => 'gutenbricks_group',
  'label' => esc_html__('Placeholder', 'gutenbricks'),
  'description' => 'If you don\'t want this element to be rendered in editor due to technical limitations, you can use a placeholder instead.. <a style="display:none;" href="#" target="_blank">Learn More</a>',
  'type' => 'separator',
];
$options['_gb_custom_placeholder'] = [
  'label' => 'Custom Placeholder',
  'type' => 'select',
  'group' => 'gutenbricks_group',
  'placeholder' => esc_html__('None', 'gutenbricks'),
  'tab' => 'content',
  'options' => [
    'text_box' => 'Text Box',
    'image' => 'Image',
  ],
];
