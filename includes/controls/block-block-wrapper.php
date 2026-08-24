<?php

if (\Gutenbricks\Bricks_Bridge::bricks_is_builder()) {
  $data['controls']['_gb_block_wrapper_separator'] = [
    'group' => '_gutenbricks_block',
    'label' => esc_html__('Block Style & Behavior', 'gutenbricks'),
    'description' => 'Configure the style and behavior of this block within the block editor. This will not affect the frontend output.',
    'type' => 'separator',
  ];

  $data['controls']['_gb_block_wrapper_dynamic_class'] = [
    'group' => '_gutenbricks_block',
    'label' => esc_html__('Block Wrapper Dynamic Class', 'gutenbricks'),
    'type' => 'text',
    'tab' => 'content',
    'hasDynamicData' => true,
    'placeholder' => "class-name {dynamic_data} {another_dynamic_data}",
    'description' => 'Add custom classes to the outermost wrapper of this block to control styles and layout during editing. <a href="https://docs.gutenbricks.com/features/tips-and-advanced/block-wrapper-dynamic-class" target="_blank">Learn More</a>.',
  ];
}
