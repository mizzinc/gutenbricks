<?php

$data['controls']['_gb_section_branding'] = [
  'group' => '_gutenbricks_block',
  'label' => esc_html__('Block Info', 'gutenbricks'),
  'type' => 'separator',
  'description' => 'Set icon for your block in the Gutenberg editor.',
];

$data['controls']['_gb_block_icon_src_type'] = [
  'group' => '_gutenbricks_block',
  'label' => esc_html__('Custom Block Icon', 'gutenbricks'),
  'type' => 'select',
  'placeholder' => esc_html__('None', 'gutenbricks'),
  'options' => [
    'dashicons' => 'Dashicons',
    'image' => 'Image',
    'svg_string' => 'SVG',
  ],
];

$data['controls']['_gb_section_branding'] = [
  'group' => '_gutenbricks_block',
  'label' => esc_html__('Branding', 'gutenbricks'),
  'type' => 'separator',
  'description' => 'Create branding for your block in the Gutenberg editor. <a>Learn More.</a>',
];

$data['controls']['_gb_block_icon_image'] = [
  'group' => '_gutenbricks_block',
  'type' => 'image',
  'size' => 'thumbnail',
  'unsplash' => false,
  'externalUrl' => false,
  'hasExternalUrl' => false,
  'hasDynamicData' => false,
  'description' => 'The icon size is 20x20.',
  'required' => [
    '_gb_block_icon_src_type',
    '=',
    'image'
  ],
];

$data['controls']['_gb_block_icon_dashicons'] = [
  'group' => '_gutenbricks_block',
  'label' => esc_html__('Dashicon', 'gutenbricks'),
  'placeholder' => 'dashicons-paperclip',
  'description' => 'Please enter a dashicon name. <a href="https://developer.wordpress.org/resource/dashicons/" target="_blank">Browse Dashicons</a>',
  'type' => 'text',
  'required' => [
    '_gb_block_icon_src_type',
    '=',
    'dashicons'
  ],
];

$data['controls']['_gb_block_icon_svg'] = [
  'group' => '_gutenbricks_block',
  'label' => esc_html__('SVG', 'gutenbricks'),
  'type' => 'code',
  'description' => 'Please enter a valid SVG code.',
  'placeholder' => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">...</svg>',
  'mode' => 'xml',
  'executeCode' => false,
  'required' => [
    '_gb_block_icon_src_type',
    '=',
    'svg_string'
  ],
];

$data['controls']['_gb_block_icon_foreground'] = [
  'group' => '_gutenbricks_block',
  'label' => esc_html__('Icon Color (optional)', 'gutenbricks'),
  'type' => 'color',
  'required' => [
    '_gb_block_icon_src_type',
    '=',
    'svg_string'
  ],
];

$data['controls']['_gb_block_icon_background'] = [
  'group' => '_gutenbricks_block',
  'label' => esc_html__('Background (optional)', 'gutenbricks'),
  'type' => 'color',
  'required' => [
    '_gb_block_icon_src_type',
    '=',
    'svg_string'
  ],
];