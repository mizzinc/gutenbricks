<?php
namespace Gutenbricks\Integrators;

class Integrator_Polylang extends Base_Integrator
{
	public static $plugin_path = 'polylang/polylang.php';

	public function is_active()
	{
		if (
			file_exists(WP_PLUGIN_DIR . '/' . 'polylang/polylang.php') 
			&& is_plugin_active('polylang/polylang.php')
		) {
			return true;
		}

		if (
			file_exists(WP_PLUGIN_DIR . '/' . 'polylang-pro/polylang.php') 
			&& is_plugin_active('polylang-pro/polylang.php')
		) {
			return true;
		}

		// Check if Polylang is active by looking for its main class or constant
		if (class_exists('Polylang') && defined('POLYLANG_VERSION')) {
			return true;
		}

		return false;
	}

	public function add_hooks()
	{
		add_filter('rest_dispatch_request', [$this, 'rest_dispatch_request'], 9, 4);
    add_action('pll_save_post', [$this, 'pll_save_post'], 99999, 3);
		add_filter('gutenbricks/register_templates/query_args', [$this, 'filter__register_templates_query_args'], 10, 2);
		add_filter('gutenbricks/load_templates/query_args', [$this, 'filter__load_templates_query_args'], 10, 1);
		add_filter('gutenbricks/load_template/template_id', [$this, 'filter__load_template_template_id'], 10, 1);
		add_filter('bricks/get_templates/query_vars', [$this, 'filter__bricks_get_templates_query_vars'], 10, 1);
	}


  public function pll_save_post($post_id, $post, $update)
  {
		remove_action( 'pll_save_post', [$this, 'pll_save_post'], 99999 );
    $updater = new \GutenBricks\Block_Value($post_id);
    $updater->update_post_content();
		add_action( 'pll_save_post', [$this, 'pll_save_post'], 99999, 3 );
  }

	public function rest_dispatch_request($result, $request, $route, $handler)
	{
		if (strpos($route, '/wp/v2/block-renderer/') !== false) {
			if (!empty($_GET['_locale'])) {
				global $polylang;
				if (isset($polylang) && is_object($polylang)) {
					$polylang->curlang = $polylang->model->get_language(sanitize_text_field($_GET['_locale']));
				}
			}
		}

		return null;
	}

	public function filter__add_editor_options($options = '', $templates = array())
	{
		if ($_GET['_locale'] ?? false) {
			$options .= "\ngutenBricksClient.addOption({\n" .
				"	polylangCurrentLanguage: '" . sanitize_text_field($_GET['_locale']) . "',\n" .
				"});\n";
		}

		return $options;
	}

	public function filter__register_templates_query_args($args, $templates)
	{
		if (function_exists('pll_current_language')) {
			$args['lang'] = pll_current_language();
		}
		return $args;
	}

	public function filter__load_templates_query_args($args)
	{
		$args['lang'] = '';
		return $args;
	}

	public function filter__load_template_template_id($template_id)
	{
		if (function_exists('pll_current_language')) {
			$template_id = pll_get_post($template_id, pll_current_language());
		}
		return $template_id;
	}


	public function filter__bricks_get_templates_query_vars($args)
	{
		$args['lang'] = '';
		return $args;
	}
}

