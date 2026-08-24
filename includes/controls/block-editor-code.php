<?php

$data['controls']['_gb_block_editor_sep'] = [
  'group' => '_gutenbricks_block',
  'label' => esc_html__('Editor', 'gutenbricks'),
  'description' => 'Configure style and behaviour of the block in Gutenberg Editor. <a style="display:none;" href="#" target="_blank">Learn More</a>',
  'type' => 'separator',
];

$data['controls']['_gb_block_editor_css'] = [
  'group' => '_gutenbricks_block',
  'label' => esc_html__('Block Editor CSS', 'gutenbricks'),
  'type' => 'code',
  'mode' => 'css',
  'hasVariables' => true,
  'pasteStyles' => true,
  'description' => 'CSS code that will be applied to the block in the editor. %root% will be replaced with the root element of the block.',
  'placeholder'  => "%root% {\n  color: firebrick;\n}",
];