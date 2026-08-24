<?php
  $options['_gb_sep_rendering'] = [
    'tab' => 'content',
    'group' => 'gutenbricks_group',
    'label' => esc_html__('Rendering', 'gutenbricks'),
    'description' => 'Configure rendering behaviour of this element.',
    'type' => 'separator',
  ];

  $options['_gb_disable_rendering'] = [
    'type' => 'checkbox',
    'group' => 'gutenbricks_group',
    'tab' => 'content',
    'label' => 'Turn Off Rendering',
    'description' => 'When enabled, this element will not be rendered on both the frontend and the Gutenberg editor. <a href="https://docs.gutenbricks.com/features/editing-elements-in-bricks-builder/turn-off-the-rendering-of-an-element" target="_blank">Learn More</a>',
  ];

  /*
  $options['_gb_allowed_post_type'] = [
    'label' => esc_html__('Allowed Post Type', 'gutenbricks'),
    'type' => 'select',
    'placeholder' => esc_html__('Empty: Allowing All Post Type', 'gutenbricks'),
    'description' => 'Useful when you want to create variations based on post type.. <a style="display:none;" href="#" target="_blank">Learn More</a>',
    'group' => 'gutenbricks_group',
    'multiple' => true,
    'searchable' => true,
    'clearable' => true,
    'tab' => 'content',
    'options' => [],
  ];
  */
