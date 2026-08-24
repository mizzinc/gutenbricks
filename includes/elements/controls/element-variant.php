<?php

if (!$template_edit_disabled) {
  // Show Variant: START
  if ($is_container) {
    $options['_gb_sep_variant'] = [
      'tab' => 'content',
      'group' => 'gutenbricks_group',
      'label' => esc_html__('Variant', 'gutenbricks'),
      'description' => 'Use variants to create different styles of blocks that users can choose from in the Gutenberg editor. <a href="https://docs.gutenbricks.com/core-features/variants" target="_blank" >Learn More.</a>',
      'type' => 'separator',
    ];

    $options['_gb_sep_variant_cannot'] = [
      'tab' => 'content',
      'group' => 'gutenbricks_group',
      'type' => 'info',
      'description' => 'Visibility toggle is enabled. It cannot be configured as a variant.',
      'required' => [
        ['_gb_enable_show_hide', '=', true],
      ],
    ];

    $options['_gb_enable_variant'] = [
      'type' => 'checkbox',
      'group' => 'gutenbricks_group',
      'tab' => 'content',
      'label' => 'Enable this element as a variant',
      'required' => [
        ['_gb_enable_show_hide', '!=', true],
      ],
    ];

    $options['_gb_variant_name'] = [
      'type' => 'text',
      'group' => 'gutenbricks_group',
      'tab' => 'content',
      'label' => 'Variant Name',
      'hasDynamicData' => false,
      'placeholder' => 'unique_name',
      'description' => 'Any <b>unique name</b> used to identify the variant.',
      'required' => [
        ['_gb_enable_show_hide', '!=', true],
        ['_gb_enable_variant', '=', true],
      ],
    ];

    $options['_gb_variant_label'] = [
      'type' => 'text',
      'group' => 'gutenbricks_group',
      'tab' => 'content',
      'label' => 'Variant Label',
      'hasDynamicData' => false,
      'placeholder' => 'Unique Label',
      'required' => [
        ['_gb_enable_show_hide', '!=', true],
        ['_gb_enable_variant', '=', true],
      ],
    ];

    $options['_gb_variant_thumbnail'] = [
      'type' => 'image',
      'group' => 'gutenbricks_group',
      'tab' => 'content',
      'label' => 'Variant Preview Thumbnail',
      'description' => 'Display as an image radio button.',
      'unsplash' => false,
      'size' => 'small',
      'required' => [
        ['_gb_enable_show_hide', '!=', true],
        ['_gb_enable_variant', '=', true],
      ],
    ];
  }

 
  // Show Variant: END 
}

