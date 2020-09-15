<?php
namespace Photonic_Plugin\Modules;

use Photonic_Plugin\Layouts\Grid;
use Photonic_Plugin\Layouts\Slideshow;
use WP_Error;

/**
 * Gallery processor class to be extended by individual processors. This class has an abstract method called <code>get_gallery_images</code>
 * that has to be defined by each inheriting processor.
 *
 * This is also where the OAuth support is implemented. The URLs are defined using abstract functions, while a handful of utility functions are defined.
 * Most utility functions have been adapted from the OAuth PHP package distributed here: https://code.google.com/p/oauth-php/.
 *
 */

abstract class Core {
	public $library, $api_key, $api_secret, $provider, $nonce, $oauth_timestamp, $signature_parameters, $link_lightbox_title,
		$oauth_version, $oauth_done, $show_more_link, $is_server_down, $is_more_required, $gallery_index, $bypass_popup, $common_parameters,
		$doc_links, $password_protected, $token, $token_secret, $show_buy_link, $stack_trace;

	protected function __construct() {
		global $photonic_slideshow_library, $photonic_custom_lightbox, $photonic_enable_popup, $photonic_thumbnail_style;
		if ($photonic_slideshow_library != 'custom') {
			$this->library = $photonic_slideshow_library;
		}
		else {
			$this->library = $photonic_custom_lightbox;
		}
		$this->nonce = self::nonce();
		$this->oauth_timestamp = time();
		$this->oauth_version = '1.0';
		$this->show_more_link = false;
		$this->is_server_down = false;
		$this->is_more_required = true;
		$this->gallery_index = 0;
		$this->bypass_popup = empty($photonic_enable_popup) || $photonic_enable_popup == 'off' || $photonic_enable_popup == 'hide';
		$this->common_parameters = [
			'columns'    => 'auto',
			'layout' => !empty($photonic_thumbnail_style) ? $photonic_thumbnail_style : 'square',
			'more' => '',
			'display' => 'in-page',
			'panel' => '',
			'filter' => '',
			'filter_type' => 'include',
			'fx' => 'slide', 	// LightSlider effects: fade and slide
			'timeout' => 4000, 	// Time between slides in ms
			'speed' => 1000,	// Time for each transition
			'pause' => true,	// Pause on hover
			'strip-style' => 'thumbs',
			'controls' => 'show',
			'popup' => $this->bypass_popup ? 'hide' : $photonic_enable_popup,

			'custom_classes' => '',
			'alignment' => '',
		];
		$this->common_parameters['photo_layout'] = $this->common_parameters['layout'];

		$this->doc_links = [];
		$this->password_protected = esc_html__('This album is password-protected. Please provide a valid password.', 'photonic');
		$this->show_buy_link = false;
		$this->stack_trace = [];
		$this->add_hooks();
	}

	/**
	 * Main function that fetches the images associated with the shortcode. This is implemented by all sub-classes.
	 *
	 * @abstract
	 * @param array $attr
	 * @param array $gallery_meta
	 */
	abstract public function get_gallery_images($attr = [], &$gallery_meta = []);

	/**
	 * Generates a nonce for use in signing calls.
	 *
	 * @static
	 * @return string
	 */
	public static function nonce() {
		$mt = microtime();
		$rand = mt_rand();
		return md5($mt . $rand);
	}

	/**
	 * Takes a string of parameters in an HTML encoded string, then returns an array of name-value pairs, with the parameter
	 * name and the associated value.
	 *
	 * @static
	 * @param $input
	 * @return array
	 */
	public static function parse_parameters($input) {
		if (!isset($input) || !$input) return [];

		$pairs = explode('&', $input);

		$parsed_parameters = [];
		foreach ($pairs as $pair) {
			$split = explode('=', $pair, 2);
			$parameter = urldecode($split[0]);
			$value = isset($split[1]) ? urldecode($split[1]) : '';

			if (isset($parsed_parameters[$parameter])) {
				// We have already recieved parameter(s) with this name, so add to the list
				// of parameters with this name
				if (is_scalar($parsed_parameters[$parameter])) {
					// This is the first duplicate, so transform scalar (string) into an array
					// so we can add the duplicates
					$parsed_parameters[$parameter] = [$parsed_parameters[$parameter]];
				}

				$parsed_parameters[$parameter][] = $value;
			}
			else {
				$parsed_parameters[$parameter] = $value;
			}
		}
		return $parsed_parameters;
	}

	/**
	 * Prints the header for a section. Typically used for albums / photosets / groups, where some generic information about the album / photoset / group is available.
	 * The <code>$options</code> array accepts the following prarameters:
	 *    - string type Indicates what type of object is being displayed like gallery / photoset / album etc. This is added to the CSS class.
	 *    - array $hidden Contains the elements that should be hidden from the header display.
	 *    - array $counters Contains counts of the object that the header represents. In most cases this has just one value. Zenfolio objects have multiple values.
	 *    - string $link Should clicking on the thumbnail / title take you anywhere?
	 *    - string $display Indicates if this is on the page or in a popup
	 *    - bool $iterate_level_3 If this is a level 3 header, this field indicates whether an expansion icon should be shown. This is to improve performance for Flickr collections.
	 *    - string $provider What is the source of the data?
	 *
	 * @param array $header The header object, which contains the title, thumbnail source URL and the link where clicking on the thumb will take you
	 * @param array $options The options to display this header. Options contain the listed internal fields fields
	 * @param array $gallery_meta
	 * @return string
	 */
	function process_object_header($header, $options = [], &$gallery_meta = []) {
		$type = empty($options['type']) ? 'group' : $options['type'];
		$hidden = isset($options['hidden']) && is_array($options['hidden']) ? $options['hidden'] : [];
		$counters = isset($options['counters']) && is_array($options['counters']) ? $options['counters'] : [];
		$link = !isset($options['link']) ? true : $options['link'];
		$display = empty($options['display']) ? 'in-page' : $options['display'];
		$iterate_level_3 = !isset($options['iterate_level_3']) ? true : $options['iterate_level_3'];

		if ($this->bypass_popup && $display != 'in-page') {
			return '';
		}

		$ret = '';
		global $photonic_gallery_template_page, $photonic_page_content;
		if (!empty($photonic_gallery_template_page) && is_page($photonic_gallery_template_page) && in_array($photonic_page_content, ['replace-if-available', 'append-if-available'])) {
			$ret = wp_kses_post($header['description']);
		}
		else if ((empty($photonic_gallery_template_page) ||
				!is_page() ||
				(is_page() && !empty($photonic_gallery_template_page) && !is_page($photonic_gallery_template_page))) &&
			!empty($header['title'])) {
			global $photonic_external_links_in_new_tab;
			$title = esc_attr($header['title']);

			$gallery_meta['title'] = $title;
			$gallery_meta['type'] = $type;

			if (!empty($photonic_external_links_in_new_tab)) {
				$target = ' target="_blank" ';
			}
			else {
				$target = '';
			}

			$anchor = '';
			if (!empty($header['thumb_url'])) {
				$image = '<img src="'.esc_url($header['thumb_url']).'" alt="'.$title.'" />';

				if ($link) {
					$anchor = "<a href='".esc_url($header['link_url'])."' class='photonic-header-thumb photonic-{$this->provider}-$type-solo-thumb' title='".$title."' $target>".$image."</a>";
				}
				else {
					$anchor = "<div class='photonic-header-thumb photonic-{$this->provider}-$type-solo-thumb'>$image</div>";
				}
			}

			if (empty($hidden['thumbnail']) || empty($hidden['title']) || empty($hidden['counter']) || empty($iterate_level_3)) {
				$popup_header_class = '';
				if ($display == 'popup') {
					$popup_header_class = 'photonic-panel-header';
				}
				$ret .= "<div class='photonic-object-header photonic-{$this->provider}-$type $popup_header_class'>";

				if (empty($hidden['thumbnail'])) {
					$ret .= $anchor;
				}
				if (empty($hidden['title']) || empty($hidden['counter']) || empty($iterate_level_3)) {
					$ret .= "<div class='photonic-header-details photonic-$type-details'>";
					if (empty($hidden['title']) || empty($iterate_level_3)) {
						$provider = $this->provider;
						$expand = empty($iterate_level_3) ? '<a href="#" title="'.esc_attr__('Show', 'photonic').'" class="photonic-level-3-expand photonic-level-3-expand-plus" data-photonic-level-3="'.$provider.'-'.$type.'-'.$header['id'].'" data-photonic-layout="'.$options['layout'].'">&nbsp;</a>' : '';

						if ($link) {
							$ret .= "<div class='photonic-header-title photonic-$type-title'><a href='".esc_url($header['link_url'])."' $target>".$title.'</a>'.$expand.'</div>';
						}
						else {
							$ret .= "<div class='photonic-header-title photonic-$type-title'>".$title.$expand.'</div>';
						}
					}
					if (empty($hidden['counter'])) {
						$counter_texts = [];
						if (!empty($counters['groups'])) {
							$counter_texts[] = esc_html(sprintf(_n('%s group', '%s groups', $counters['groups'], 'photonic'), $counters['groups']));
						}
						if (!empty($counters['sets'])) {
							$counter_texts[] = esc_html(sprintf(_n('%s set', '%s sets', $counters['sets'], 'photonic'), $counters['sets']));
						}
						if (!empty($counters['photos'])) {
							$counter_texts[] = esc_html(sprintf(_n('%s photo', '%s photos', $counters['photos'], 'photonic'), $counters['photos']));
						}
						if (!empty($counters['videos'])) {
							$counter_texts[] = esc_html(sprintf(_n('%s video', '%s videos', $counters['videos'], 'photonic'), $counters['videos']));
						}

						apply_filters('photonic_modify_counter_texts', $counter_texts, $counters);

						if (!empty($counter_texts)) {
							$ret .= "<span class='photonic-header-info photonic-$type-photos'>".implode(', ', $counter_texts).'</span>';
						}
					}

					$ret .= "</div><!-- .photonic-$type-details -->";
				}
				$ret .= "</div>";
			}
		}

		return $ret;
	}

	/**
	 * Generates the markup for a single photo.
	 *
	 * @param $data array Pertinent pieces of information about the photo - the source (src), the photo page (href), title and caption
	 * @return string
	 */
	function generate_single_photo_markup($data) {
		$layout_manager = $this->get_layout_manager('square');
		$ret = $layout_manager->generate_single_photo_markup($data, $this);
		return $ret;
	}

	/**
	 * Generates the HTML for the gallery, based on the level.
	 * Level 1 corresponds to a group of photos. This is used for both, in-page and popup displays.
	 * Level 2 corresponds to a group of albums.
	 * This calls an individual layout generator for rendering the gallery.
	 * The code for the random layouts is handled in JS, but just the HTML markers for it are provided here.
	 *
	 * @param $photos
	 * @param array $options
	 * @param $short_code
	 * @param $level
	 * @return string
	 */
	function layout_gallery($photos, $options, $short_code, $level) {
		global $photonic_thumbnail_style;
		$layout = !empty($short_code['layout']) ? $short_code['layout'] : $photonic_thumbnail_style;
		$layout_manager = $this->get_layout_manager($layout);
		$ret = '';
		if ($level == 1) {
			$ret = $layout_manager->generate_level_1_gallery($photos, $options, $short_code, $this);
		}
		else if ($level == 2) {
			$ret = $layout_manager->generate_level_2_gallery($photos, $options, $short_code, $this);
		}
		return $ret;
	}

	function finalize_markup($content, $short_code) {
		if ($short_code['display'] != 'popup') {
			$additional_classes = '';
			if (!empty($short_code['custom_classes'])) {
				$additional_classes = $short_code['custom_classes'];
			}
			if (!empty($short_code['alignment'])) {
				$additional_classes .= ' align'.$short_code['alignment'];
			}
			$ret = "<div class='photonic-{$this->provider}-stream photonic-stream $additional_classes' id='photonic-{$this->provider}-stream-{$this->gallery_index}'>\n";
		}
		else {
			$popup_id = "id='photonic-{$this->provider}-panel-" . $short_code['panel'] . "'";
			$ret = "<div class='photonic-{$this->provider}-panel photonic-panel' $popup_id>\n";
		}
		$ret .= $content."\n";
		$ret .= "</div><!-- .photonic-stream or .photonic-panel -->\n";
		return $ret;
	}

	function get_layout_manager($layout) {
		if (in_array($layout, ['strip-above', 'strip-below', 'strip-right', 'no-strip'])) {
			require_once(PHOTONIC_PATH.'/Layouts/Slideshow.php');
			$layout_manager = Slideshow::get_instance();
		}
		else {
			require_once(PHOTONIC_PATH.'/Layouts/Grid.php');
			$layout_manager = Grid::get_instance();
		}
		return $layout_manager;
	}

	function get_header_display($args) {
		if (!isset($args['headers'])) {
			return [
				'thumbnail' => 'inherit',
				'title' => 'inherit',
				'counter' => 'inherit',
			];
		}
		else if (empty($args['headers'])) {
			return [
				'thumbnail' => 'none',
				'title' => 'none',
				'counter' => 'none',
			];
		}
		else {
			$header_array = explode(',', $args['headers']);
			return [
				'thumbnail' => in_array('thumbnail', $header_array) ? 'show' : 'none',
				'title' => in_array('title', $header_array) ? 'show' : 'none',
				'counter' => in_array('counter', $header_array) ? 'show' : 'none',
			];
		}
	}

	function get_hidden_headers($arg_headers, $setting_headers) {
		return [
			'thumbnail' => $arg_headers['thumbnail'] === 'inherit' ? $setting_headers['thumbnail'] : ($arg_headers['thumbnail'] === 'none' ? true : false),
			'title' => $arg_headers['title'] === 'inherit' ? $setting_headers['title'] : ($arg_headers['title'] === 'none' ? true : false),
			'counter' => $arg_headers['counter'] === 'inherit' ? $setting_headers['counter'] : ($arg_headers['counter'] === 'none' ? true : false),
		];
	}

	/**
	 * Wraps an error message in appropriately-styled markup for display in the front-end
	 *
	 * @param $message
	 * @return string
	 */
	function error($message) {
		return "<div class='photonic-error photonic-{$this->provider}-error'>\n\t<span class='photonic-error-icon photonic-icon'>&nbsp;</span>\n\t<div class='photonic-message'>\n\t\t$message\n\t</div>\n</div>\n";
	}

	/**
	 * Retrieves the error messages from a WP_Response object and formats them in a display-ready markup.
	 *
	 * @param WP_Error $response
	 * @param bool $server_msg
	 * @return string
	 */
	function wp_error_message($response, $server_msg = true) {
		$ret = '';
		if ($server_msg) {
			$ret = $this->get_server_error()."<br/>\n";
		}
		if (is_wp_error($response)) {
			$messages = $response->get_error_messages();
			$ret .= '<strong>'.esc_html(sprintf(_n('%s Message:', '%s Messages:', count($messages), 'photonic'), count($messages)))."</strong><br/>\n";
			foreach ($messages as $message) {
				$ret .= $message."<br>\n";
			}
		}
		return $ret;
	}

	function push_to_stack($event) {
		global $photonic_performance_logging;
		if (empty($photonic_performance_logging)) {
			return;
		}

		if (!isset($this->stack_trace[$this->gallery_index])) {
			$events = [];
		}
		else {
			$events = $this->stack_trace[$this->gallery_index];
		}

		$this->add_to_first_open_event($events, $event);
		$this->stack_trace[$this->gallery_index] = $events;
	}

	private function add_to_first_open_event(&$events, $new_event) {
		$found = false;
		foreach ($events as $id => $event) {
			if (isset($event['start']) && !isset($event['end'])) {
				// Ongoing event. Need to add to this.
				$found = true;
				if (!isset($event['children'])) {
					$children = [];
				}
				else {
					$children = $event['children'];
				}
				$this->add_to_first_open_event($children, $new_event);
				$event['children'] = $children;
				$events[$id] = $event;
			}
			if ($found) {
				break;
			}
		}
		if (!$found) {
			$events[] = [
				'event' => $new_event,
				'start' => microtime(true),
			];
		}
	}

	function pop_from_stack() {
		global $photonic_performance_logging;
		if (empty($photonic_performance_logging)) {
			return;
		}

		if (!isset($this->stack_trace[$this->gallery_index])) {
			return;
		}
		else {
			$events = $this->stack_trace[$this->gallery_index];
			$this->pop_from_first_open_event($events);
			$this->stack_trace[$this->gallery_index] = $events;
		}
	}

	private function pop_from_first_open_event(&$events) {
		$found = false;
		foreach ($events as $id => $event) {
			if (isset($event['start']) && !isset($event['end'])) {
				// Ongoing event. Need to pop this or its open child
				$found = true;
				$found_child = false;
				if (isset($event['children'])) {
					$children = $event['children'];
					$found_child = $this->pop_from_first_open_event($children);
					$event['children'] = $children;
				}
				if (!$found_child) {
					$event['end'] = microtime(true);
					$event['time'] = $event['end'] - $event['start'];
				}
				$events[$id] = $event;
			}
			if ($found) {
				break;
			}
		}
		return $found;
	}

	function get_stack_markup() {
		global $photonic_performance_logging;
		if (empty($photonic_performance_logging)) {
			return '';
		}

		$ret = '';
		if (!empty($this->stack_trace[$this->gallery_index])) {
			$ret = "<!--\n";
			$ret .= "Stats for Provider: {$this->provider}, Gallery: {$this->gallery_index}\n";
			$events = $this->stack_trace[$this->gallery_index];
			$ret .= $this->get_nested_element($events);
			$ret .= "-->\n";
		}
		return $ret;
	}

	private function get_nested_element($events, $indent = "\t") {
		$ret = '';
		foreach ($events as $trace) {
			$trace_items = [];
			foreach ($trace as $key => $trace_item) {
				if ($key != 'children') {
					$trace_items[] = strtoupper(substr($key, 0, 1)).substr($key, 1).': '.$trace_item;
				}
			}
			$ret .= $indent.implode(', ', $trace_items)."\n";
			if (!empty($trace['children'])) {
				$ret .= $this->get_nested_element($trace['children'], $indent."\t");
			}
		}
		return $ret;
	}

	protected function get_gallery_url($short_code, $meta) {
		global $photonic_alternative_shortcode, $photonic_gallery_template_page;

		$shortcode_tag = $photonic_alternative_shortcode ?: 'gallery';
		$shortcode_parts = [];
		foreach ($short_code as $attr => $value) {
			if (is_array($value)) {
				continue;
			}
			$shortcode_parts[] = $attr.'="'.esc_attr($value).'"';
		}
		$raw_shortcode = '['.$shortcode_tag.' '.implode(' ', $shortcode_parts).']';

		$gallery_url = add_query_arg([
			'photonic_gallery' => base64_encode($raw_shortcode),
			'photonic_gallery_title' => $meta['title'],
		], get_page_link($photonic_gallery_template_page));
		return $gallery_url;
	}

	function ssl_verify_peer(&$handle) {
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
	}

	function get_server_error() {
		return sprintf(esc_html__('There was an error connecting to %s. Please try again later.', 'photonic'), $this->provider);
	}

	/**
	 * Helper execution, implemented by child classes
	 *
	 * @param $args
	 * @return string
	 */
	function execute_helper($args) {
		// Blank method, to be overridden by child classes
		return '';
	}

	function add_hooks() {
		// Blank method, implemented by child classes, if required
	}
}
