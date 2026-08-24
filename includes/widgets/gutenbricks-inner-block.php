<?php
class Gutenbricks_Inner_Block extends \Bricks\Element_Container
{

  /**
   * Element properties
   * @since 1.0.0
   * @access public
   *
   */
  public $category = 'GutenBricks'; // Use predefined element category 'general'
  public $name = 'gb-inner-blocks'; // Make sure to prefix your elements
  public $icon = 'fa-brands fa-wordpress-simple'; // Themify icon font class
  public $nestable = true;
  public $vue_component = 'bricks-nestable';         // Set specific Vue component to render element in builder (e.g. 'bricks-nestable' for Section, Container, Div)

  public static $core_blocks = [];

  public static $gutenbricks_blocks = [];


  /**
   * Get widget label.
   * @since 1.0.0
   * @access public
   *
   * @return string Widget Label.
   */
  public function get_label()
  {
    return esc_html__('GutenBricks InnerBlock', 'gutenbricks');
  }

  /**
   * Register widget control groups.
   * @since 1.0.0
   * @access protected
   */
  public function set_control_groups()
  {
    /* 
     $this->control_groups['_gutenbricks_block'] = [
      'title' => esc_html__('InnerBlock Settings', 'gutenbricks'),
      'tab' => 'content',
    ];
    */
  }

  /**
   * Register widget controls.
   * @since 1.0.0
   * @access protected
   */
  public function set_controls()
  {
    parent::set_controls();

    if (\Gutenbricks\Bricks_Bridge::bricks_is_builder()) {
      self::load_blocks();
    }

		$this->controls['section_title'] = [
			'label' => esc_html__('InnerBlock', 'gutenbricks'),
			'type' => 'separator',
      'description' => 'Create an InnerBlock area. <a href="https://docs.gutenbricks.com/core-features/innerblock" target="_blank">Learn More</a>',
		];
    
    $this->controls['_description'] = [
      'type' => 'info',
      'content' => __('IMPORTANT: The Gutenberg editor only allows one InnerBlock per block. GutenBricks will ignore any additional InnerBlocks.', 'gutenbricks'),
    ];

    $this->controls['allowed_core_blocks'] = [
      // 'group' => '_gutenbricks_block',
      'label' => __('Allowed <b>Core/Non-GutenBricks</b> Blocks', 'gutenbricks'),
      'type' => 'select',
      'placeholder' => __('All Blocks Allowed', 'gutenbricks'),
      'description' => 'Select which core/non-GutenBricks blocks are allowed. <a style="display:none;" href="#" target="_blank">Learn More</a>',
      'multiple' => true,
      'searchable' => true,
      'clearable' => true,
      'options' => array_merge([
        '__disable_core_blocks' => 'Disable All Core/Non-GutenBricks Blocks',
      ], self::$core_blocks),
    ];

    $this->controls['allowed_gb_blocks'] = [
      // 'group' => '_gutenbricks_block',
      'label' => __('Allowed <b>GutenBricks</b> Blocks', 'gutenbricks'),
      'type' => 'select',
      'placeholder' => __('All GutenBricks Blocks Allowed', 'gutenbricks'),
      'description' => 'Select which core blocks are allowed. <a style="display:none;" href="#" target="_blank">Learn More</a>',
      'multiple' => true,
      'searchable' => true,
      'clearable' => true,
      'options' => array_merge([
        '__disable_gb_blocks' => 'Disable All GutenBricks Blocks',
      ], self::$gutenbricks_blocks),
    ];

    $this->controls['default_blocks'] = [
      'label' => __('Default Blocks', 'gutenbricks'),
      'type' => 'textarea',
      'placeholder' => "<!-- wp:paragraph -->\n  <p>This is an example.</p>\n<!-- /wp:paragraph -->",
      'description' => 'You can copy and paste the block here. <a style="display:none;" href="#" target="_blank">Learn More</a>',
      'rows' => 15,
    ];

    $this->controls['max_blocks'] = [
      'label' => __('Max Blocks', 'gutenbricks'),
      'type' => 'number',
      'description' => 'The maximum number of immediate child blocks allowed in this InnerBlock. Leave blank for unlimited.',
      'hasDynamicData' => true,
    ];

    $this->controls['__disable_gb_wrapper'] = [
      'label' => __('Innerblocks Wrapper', 'gutenbricks'),
      'type' => 'checkbox',
      'hasDynamicData' => false,
      'label' => 'Disable <b>DIV Wrapper</b> on frontend',
      'description' => 'Check this if you want to remove the wrapping div of InnerBlocks e.g. for use inside of a nestable element like a slider.',
    ];

  }

  private static function load_blocks()
  {
    self::$core_blocks = self::get_core_wordpress_blocks();

    self::$gutenbricks_blocks = GutenBricks_Core::$block_registry->get_registered_blocks();
  }

  private static function get_core_wordpress_blocks()
  {

    // Check if the WP_Block_Type_Registry class exists
    if (!class_exists('WP_Block_Type_Registry')) {
      return [];
    }

    $common_blocks = [
      'core/paragraph' => 'Paragraph',
      'core/image' => 'Image',
      'core/heading' => 'Heading',
      'core/gallery' => 'Gallery',
      'core/list' => 'List',
      'core/quote' => 'Quote',
      'core/audio' => 'Audio',
      'core/cover' => 'Cover',
      'core/file' => 'File',
      'core/video' => 'Video',
      'core/code' => 'Code',
      'core/freeform' => 'Classic',
      'core/html' => 'Custom HTML',
      'core/preformatted' => 'Preformatted',
      'core/pullquote' => 'Pullquote',
      'core/table' => 'Table',
      'core/verse' => 'Verse',
      'core/button' => 'Button',
      'core/buttons' => 'Buttons',
      'core/columns' => 'Columns',
      'core/group' => 'Group',
      'core/media-text' => 'Media & Text',
      'core/separator' => 'Separator',
      'core/spacer' => 'Spacer',
      'core/shortcode' => 'Shortcode',
      'core/archives' => 'Archives',
      'core/categories' => 'Categories',
      'core/latest-comments' => 'Latest Comments',
      'core/latest-posts' => 'Latest Posts',
      'core/calendar' => 'Calendar',
      'core/rss' => 'RSS',
      'core/search' => 'Search',
    ];

    $core_blocks = [];

    // Get the block registry instance
    $block_registry = WP_Block_Type_Registry::get_instance();

    // Get all registered blocks
    $all_blocks = $block_registry->get_all_registered();

    // Add common blocks first
    foreach ($common_blocks as $common_block_name => $block_title) {
      if (isset($all_blocks[$common_block_name])) {
        $title = $all_blocks[$common_block_name]->title;
        if (empty(trim($title))) {
          $title = $common_block_name;
        } 
        $core_blocks[$common_block_name] = $all_blocks[$common_block_name]->category . ': <span>' . esc_html($title) . '</span>';
        unset($all_blocks[$common_block_name]); // Remove from all_blocks to avoid duplication
      }
    }

    // Add the remaining blocks if it's not GutenBricks block
    foreach ($all_blocks as $block_name => $block_type) {
      if (strpos($block_name, '/gutenb-') === false && !empty($block_type->title)) {
        $core_blocks[$block_name] = $block_type->category . ': <span>' . esc_html($block_type->title) . '</span>';
      }
    }

    return $core_blocks;
  }


  public function add_filters()
  {
  }

  public function render()
  {

    $html = \GutenBricks\Render_Context::$current_block_content;

    // Remove the opening and closing tags from the HTML content
    $pattern = '/<!-- gutenbricks_block_content:start \{.*?\} -->.*?<!-- gutenbricks_block_content:end -->/s';
    $html = preg_replace($pattern, '', $html);

    $settings = $this->settings;
    if ( (did_action( 'get_header' ) || did_action( 'get_footer' )) && (isset($settings['__disable_gb_wrapper']) && $settings['__disable_gb_wrapper']) ) {
      echo $html;
    } else {
      echo "<{$this->tag} {$this->render_attributes('_root')}>";
      echo $html;
      echo "</{$this->tag}>";
    }

    // IMPORTANT: we must clear up the current block content after rendering
    // because we don't want the InnerBlock content to render mulitple times
    // since one InnerBlock per Block. 
    \GutenBricks\Render_Context::$current_block_content = '';
  }

}

