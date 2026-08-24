<?php

if (\Gutenbricks\Bricks_Bridge::bricks_is_builder()) {
  $data['controls']['_gb_innerblock_separator'] = [
    'group' => '_gutenbricks_block',
    'label' => esc_html__('InnerBlock', 'gutenbricks'),
    'description' => 'Configure how this block behaves inside InnerBlock. <a style="display:none;" href="#" target="_blank">Learn More</a>',
    'type' => 'separator',
  ];

  $block_registry = WP_Block_Type_Registry::get_instance();
  $all_blocks = $block_registry->get_all_registered();
  $registered_block = array();
  
  foreach ($all_blocks as $block_name => $block) {
    $registered_block[$block_name] = $block->title;
  }

  $data['controls']['_gb_innerblock_parent'] = [
    'group' => '_gutenbricks_block',
    'label' => esc_html__('InnerBlock Parent', 'gutenbricks'),
    'placeholder' => 'None',
    'hasDynamicData' => false,
    'type' => 'select',
    'description' => 'This block will only be used and inserted as a direct descendant of its parent. <a style="display:none;" href="#" target="_blank">Learn More</a>',
    'multiple' => true,
    'searchable' => true,
    'clearable' => true,
    'options' => $registered_block,
  ];
}
