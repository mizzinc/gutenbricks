<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://gutenbricks.com
 * @since      1.0.0
 *
 * @package    Gutenbricks
 * @subpackage Gutenbricks/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Gutenbricks
 * @subpackage Gutenbricks/public
 * @author     WiredWP <ryan@wiredwp.com>
 */
class Gutenbricks_Public
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	private static $is_rendering_footer = false;

	private static $styles_move_to_head = '';

	private static $post_styles_move_to_head = '';

	public static $frontend_inline_css = '';

	public static $dynamic_inline_css = '';

	// Primarily used to remove in the footer
	public static $frontend_dynamic_css_to_remove = '';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function wp_head_styles()
	{
		// @since 5.5.3 hotfix - the position of the inline CSS is not overriding properly
		?>
		<style id="gbrx-control">
			.has-text-align-justify {
				text-align: justify;
			}
		</style>

		<?php if (get_option('_gutenbricks_fouc_fix') && !\Gutenbricks\Bricks_Bridge::bricks_is_builder()) { ?>
			<style>
				.gbrx-block-content,
				#brx-content {
					visibility: hidden;
				}
			</style>
			
			<noscript>
				<style>
					.gbrx-block-content,
					#brx-content {
						visibility: visible !important;
					}
				</style>
			</noscript>
		<?php }
	}
	public function wp_footer_render_css()
	{
		$this->load_webfonts();

		// @since Bricks 1.9.9
		if (!empty(self::$frontend_inline_css)) {
			if (class_exists('Bricks\Assets') && method_exists('Bricks\Assets', 'minify_css')) {
				self::$frontend_inline_css = \Bricks\Assets::minify_css(self::$frontend_inline_css);
			}
			?>
			<style id="gbrx-block-inline-css">
				<?php echo self::$frontend_inline_css; ?>
			</style>
			<?php
		}

		if (!empty(self::$dynamic_inline_css)) {
			if (class_exists('Bricks\Assets') && method_exists('Bricks\Assets', 'minify_css') && !empty(self::$dynamic_inline_css)) {
				self::$dynamic_inline_css = \Bricks\Assets::minify_css(self::$dynamic_inline_css);
			}
			?>
			<style id="gbrx-dynamic-inline-css">
				<?php echo self::$dynamic_inline_css; ?>
			</style>
			<?php
		}

		// since 1.1.0
		// For development, we output the performance summary.
		// show_gutenbricks_performance_summary is a true/false value set in the ACF options page.
		if (function_exists('get_field') && get_field('show_gutenbricks_performance_summary', 'option')) {
			$summary = \Gutenbricks\PerformanceMonitor::getReport('gutenbricks');
			gb_console('[GutenBricks] performance Summary', $summary);
			gb_console('[GutenBricks] summary by label', \Gutenbricks\PerformanceMonitor::getTotalMeasurements());
			$total_overhead = $summary['gutenbricks']['total']['total_time_ms'] ?? 0;
			gb_console('[GutenBricks] total GutenBricks overhead (ms)', intval(ceil($total_overhead)));
		}
	}

	public function wp_footer_fouc_fix()
	{
		if (get_option('_gutenbricks_fouc_fix')) {
			?>
			<script>
				(function () {
					var blocks = document.querySelectorAll('.gbrx-block-content, #brx-content');
					if (blocks.length) {
						blocks.forEach(function (block) {
							block.style.visibility = 'visible';
						});
					}
				})();
			</script>
			<?php
		}
	}

	public function load_webfonts()
	{
		// HACK: @since RC5.5.1 for Bricks >= 1.9.8
		// we need to load the webfonts for template global classes
		// because Bricks' enqueue_footer_inline_css() does generate global classes
		// but it does not load_webfonts() for them
		if (class_exists('Bricks\Assets')) {

			$frontend_css = self::$frontend_inline_css;
			$global_class_css = \Bricks\Assets::$inline_css['global_classes'] ?? '';
			$dynamic_css = \Bricks\Assets::$inline_css_dynamic_data;

			if (empty($global_class_css)) {
				// we run this to generate the global classes
				\Bricks\Assets::generate_global_classes();
			}

			\Bricks\Assets::load_webfonts($frontend_css . $global_class_css . $dynamic_css);

			// HACK: Here we take care of dynamic CSS
			// self::$dynamic_inline_css = \Bricks\Assets::$inline_css_dynamic_data;
		}
	}
}
