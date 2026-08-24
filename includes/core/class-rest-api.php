<?php
namespace Gutenbricks;

class Rest_Api
{
	private $render_context;
	private $block_registry;
	private $gutenberg_editor;

	public function __construct(Render_Context $render_context, Block_Registry $block_registry, Gutenberg_Editor $gutenberg_editor)
	{
		$this->render_context = $render_context;
		$this->block_registry = $block_registry;
		$this->gutenberg_editor = $gutenberg_editor;
	}

	// inject extra object in the response of /wp/v2/block-renderer/...
	public static function inject_wp_v2_block_renderer($result, $server, $request)
	{
		$route = $request->get_route();

		if (strpos($route, '/wp/v2/block-renderer/') !== false) {

			if (defined('WP_ADMIN')) {
				define('WP_ADMIN', false);
			}

			add_filter('rest_post_dispatch', function ($response, $server, $request) {
				if (strpos($request->get_route(), '/wp/v2/block-renderer/') !== false) {
					$body = $request->get_json_params();
		
					if (!isset($body['attributes']['template_id'])) {
						return $response;
					}

					$template_id = $body['attributes']['template_id'];
					$response->data['enqueue_scripts'] = Render_Context::get_enqueued_scripts();
					$response->data['enqueue_styles'] = Render_Context::$current_page_styles;
					$response->data['block_settings'] = apply_filters('gutenbricks/template/block_settings', Gutenberg_Editor::$current_block_settings, $template_id);
					
					// debugging
					if (gutenbricks_is_dev_env()) {
						$response->data['runtime'] = PerformanceMonitor::getReport();
					}
				}
				return $response;
			}, 99, 3);
		}

		return $result; // Return the unmodified result if not a block renderer request
	}

	public function register_rest_route()
	{
		register_rest_route(
			'gutenbricks/v1',
			'/gutenbricks-block-documentation/',
			array(
				'methods' => 'GET',
				'callback' => array($this, 'get_gutenbricks_block_documentation'),
				'args' => array(
					'template_id' => array(
						'required' => true,
						'validate_callback' => function ($param, $request, $key) {
							return is_numeric($param);
						}
					),
				),
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		);

		register_rest_route(
			'gutenbricks/v1',
			'/search-posts/',
			array(
				'methods' => 'GET',
				'callback' => array($this, 'search_posts'),
				'args' => array(
					'search' => array(
						'required' => true,
						'type' => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'post_types' => array(
						'required' => true,
						'type' => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'per_page' => array(
						'required' => false,
						'type' => 'integer',
						'default' => 10,
					),
					'taxonomy' => array(
						'required' => false,
						'type' => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		);
	}
	
	public function get_gutenbricks_block_documentation($data)
	{
		$post_id = $data['template_id'];
		$meta_value = get_post_meta($post_id, '_gutenbricks_block_documentation', true);
		$response = new \WP_REST_Response($meta_value);
		$response->set_status(200);
		$response->header('Content-Type', 'text/html; charset=UTF-8');

		return $response;
	}

	public function search_posts($request) {
		$search = $request['search'];
		$post_types = explode(',', $request['post_types']);
		$per_page = min($request['per_page'], 50); // Limit max results to 50
		$taxonomy = $request['taxonomy'];

		// Split search terms
		$search_terms = explode(' ', $search);
		$search_terms = array_filter($search_terms); // Remove empty values

		$args = array(
			'post_type' => $post_types,
			'posts_per_page' => $per_page,
			'post_status' => 'publish',
			'orderby' => 'relevance',
		);

		if ($taxonomy) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => $taxonomy,
					'operator' => 'EXISTS'
				)
			);
		}

		// Add filter to modify search query for better partial matching and multi-word search
		add_filter('posts_where', function($where) use ($search_terms) {
			global $wpdb;
			
			$where_parts = array();
			foreach ($search_terms as $term) {
				$term = esc_sql($term);
				$where_parts[] = "({$wpdb->posts}.post_title LIKE '%{$term}%')";
			}
			
			// Replace the default WordPress search with our custom one
			$search_pattern = "AND (((({$wpdb->posts}.post_title LIKE '%";
			$search_position = strpos($where, $search_pattern);
			
			if ($search_position !== false) {
				// Find the end of the search clause
				$end_position = strpos($where, ")", $search_position);
				$where = substr_replace($where, 
					"AND (" . implode(' AND ', $where_parts) . ")", 
					$search_position,
					$end_position - $search_position + 1
				);
			}
			
			return $where;
		});

		// Add filter to modify ordering to prioritize exact matches and closer matches
		add_filter('posts_orderby', function($orderby) use ($search_terms) {
			global $wpdb;
			
			$order_parts = array();
			foreach ($search_terms as $term) {
				$term = esc_sql($term);
				// Exact matches first
				$order_parts[] = "CASE 
					WHEN {$wpdb->posts}.post_title LIKE '{$term}' THEN 1
					WHEN {$wpdb->posts}.post_title LIKE '{$term}%' THEN 2
					WHEN {$wpdb->posts}.post_title LIKE '% {$term}%' THEN 3
					ELSE 4
				END";
			}
			
			return implode(' + ', $order_parts) . ", {$wpdb->posts}.post_title ASC";
		});

		$query = new \WP_Query($args);
		$posts = array();

		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				$posts[] = array(
					'id' => get_the_ID(),
					'title' => array(
						'rendered' => get_the_title()
					),
					'type' => get_post_type()
				);
			}
			wp_reset_postdata();
		}

		return new \WP_REST_Response($posts, 200);
	}

}
	

