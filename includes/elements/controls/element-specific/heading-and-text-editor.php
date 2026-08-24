<?php

$heading_options = [
  'group' => 'gutenbricks_group',
  'label' => esc_html__('Allowed Headings', 'gutenbricks'),
  'type' => 'select',
  'placeholder' => esc_html__('No other headings are allowed.', 'gutenbricks'),
  'description' => 'If not specified, no other headings are allowed. <a style="display:none;" href="#" target="_blank">Learn More</a>',
  'multiple' => true,
  'searchable' => true,
  'clearable' => true,
  'options' => [
    1 => 'H1',
    2 => 'H2',
    3 => 'H3',
    4 => 'H4',
    5 => 'H5',
    6 => 'H6',
  ],
  'required' => [
    ['_gb_disable_edit', '!=', true],
  ],
];

// Allowed Tools
$gutenberg_format_options = [
  'group' => 'gutenbricks_group',
  'label' => esc_html__('Allowed Formats', 'gutenbricks'),
  'type' => 'select',
  'placeholder' => esc_html__('All formats are allowed.', 'gutenbricks'),
  'description' => 'If not specified, all formats allowed. <a style="display:none;" href="#" target="_blank">Learn More</a>',
  'multiple' => true,
  'searchable' => true,
  'clearable' => true,
  'options' => [
    'disable_all' => 'Disable All Formats',
    'core/bold' => 'Bold',
    'core/italic' => 'Italic',
    'core/underline' => 'Underline',
    'core/link' => 'Link',
    'core/strikethrough' => 'Strikethrough',
    'core/text-color' => 'Text Color',
  ],
  'required' => [
    ['_gb_disable_edit', '!=', true],
  ],
];

if ($name === 'heading') {
  // this will be passed as "data-gb-allowed-headings" into editor
  $options['_gb_allowed_headings'] = $heading_options;
} 

if ($name === 'heading' || $name === 'text-basic' || $name === 'text') {
  $options['_gb_allowed_formats'] = $gutenberg_format_options;
}

if ($name === 'text-link' || $name === 'button') {
  $options['_gb_allowed_formats'] = $gutenberg_format_options;
  unset($options['_gb_allowed_formats']['options']['core/link']);
}

if ($name === 'text') {
  $options['_gb_allowed_headings'] = $heading_options;
  $options['_gb_allowed_formats'] = [
    'group' => 'gutenbricks_group',
    'label' => esc_html__('Allowed Formats', 'gutenbricks'),
    'type' => 'select',
    'placeholder' => esc_html__('All formats are allowed.', 'gutenbricks'),
    'description' => 'If not specified, all formats allowed. <a style="display:none;" href="#" target="_blank">Learn More</a>',
    'multiple' => true,
    'searchable' => true,
    'clearable' => true,
    'options' => [
      'bold' => 'Bold',
      'italic' => 'Italic',
      'underline' => 'Underline',
      'strike' => 'Strikethrough',
      'link' => 'Link',
      'list_ordered' => 'List: Ordered',
      'list_bullet' => 'List: Bullet',
      'align' => 'Align',
      'text_color' => 'Text Color',
    ], 
    'required' => [
      ['_gb_disable_edit', '!=', true],
    ],
  ];
}

