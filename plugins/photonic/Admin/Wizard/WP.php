<?php
namespace Photonic_Plugin\Admin\Wizard;

use Photonic_Plugin\Core\Utilities;

class WP extends Source {
	private static $instance;

	protected function __construct() {
		parent::__construct();
		$this->provider = 'wp';
		$this->allowed_image_sizes['wp'] = [
			'thumb_size' => Utilities::get_wp_image_sizes(false, true),
			'tile_size' => Utilities::get_wp_image_sizes(true, true),
			'main_size' => Utilities::get_wp_image_sizes(true, true),
		];
	}

	public static function get_instance() {
		if (self::$instance == null) {
			self::$instance = new WP();
		}
		return self::$instance;
	}

	function get_screen_2() {
		return [
			'header' => esc_html__('Choose Type of Gallery', 'photonic'),
			'display' => [
				'display_type' => [
					'desc' => esc_html__('What do you want to show?', 'photonic'),
					'type' => 'select',
					'options' => [
						'' => '',
						'current-post' => esc_html__('Gallery attached to the current post', 'photonic'),
						'multi-photo' => esc_html__('Photos from Media Library', 'photonic'),
					],
					'req' => 1,
				],
			],
		];
	}

	function get_screen_3() {
		return [];
	}

	function get_screen_4() {
		return [];
	}

	function get_screen_5() {
		return [
			'wp' => [
				'count' => [
					'desc' => esc_html__('Number of photos to show', 'photonic'),
					'type' => 'text',
					'hint' => esc_html__('Numeric values only. Shows all photos by default.', 'photonic'),
				],
				'main_size' => [
					'desc' => esc_html__('Main image size', 'photonic'),
					'type' => 'select',
					'options' => $this->allowed_image_sizes['wp']['main_size'],
					'std' => 'full',
				],
			]
		];
	}

	function get_square_size_options() {
		return [
			'thumb_size' => [
				'desc' => esc_html__('Thumbnail size', 'photonic'),
				'type' => 'select',
				'options' => $this->allowed_image_sizes['wp']['thumb_size'],
				'std' => 'thumbnail',
			],
		];
	}

	function get_random_size_options() {
		return [
			'tile_size' => [
				'desc' => esc_html__('Tile size', 'photonic'),
				'type' => 'select',
				'options' => $this->allowed_image_sizes['wp']['tile_size'],
				'std' => 'full',
			],
		];
	}

	function make_request($display_type, $for, $flattened_fields) {
		return null;
	}

	/**
	 * Blank for WP, since there is nothing to do.
	 *
	 * @param $response
	 * @param $display_type
	 * @param null $url
	 * @param array $pagination
	 * @return mixed|null
	 */
	function process_response($response, $display_type, $url = null, &$pagination = []) {
		return null;
	}

	/**
	 * @param $display_type
	 * @return array|mixed
	 */
	function construct_shortcode_from_screen_selections($display_type) {
		$short_code = [];

		if ($display_type == 'current-post' && !empty($_POST['post_id'])) {
			$short_code['id'] = sanitize_text_field($_POST['post_id']);
		}
		else if (!empty($_POST['selected_data'])) {
			$short_code['ids'] = sanitize_text_field($_POST['selected_data']);
		}

		return $short_code;
	}

	/**
	 * @param $input
	 * @return array|mixed
	 */
	function deconstruct_shortcode_to_screen_selections($input) {
		$deconstructed = [];

		if (empty($input->id) && empty($input->ids) && empty($input->include)) {
			$deconstructed['display_type'] = 'current-post';
		}
		else if (!empty($input->ids) || !empty($input->include)) {
			$deconstructed['display_type'] = 'multi-photo';
			$deconstructed['selected_data'] = !empty($input->ids) ? $input->ids : $input->include;
		}

		return $deconstructed;
	}
}