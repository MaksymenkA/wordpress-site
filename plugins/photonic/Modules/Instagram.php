<?php
namespace Photonic_Plugin\Modules;

use WP_Error;

require_once('OAuth2.php');
require_once('Level_One_Module.php');

/**
 * Module to handle Instagram. Instagram uses OAuth2 authentication, and authentication is mandatory to display content.
 * Instagram first issues a short-lived token. This is exchanged for a long-lived token right on an external site.
 * The long-lived token is valid for 60 days, though, to be safe, Photonic swaps it out for a new token if there are less then
 * 30 days of validity left on it.
 *
 */
class Instagram extends OAuth2 implements Level_One_Module {
	var $response_type, $scope, $cached_token, $field_list, $token_valid;
	private static $instance = null;

	protected function __construct() {
		parent::__construct();
		global $photonic_instagram_disable_title_link, $photonic_instagram_access_token;
		$this->provider = 'instagram';
		$this->oauth_version = '2.0';
		$this->response_type = 'token';
		$this->scope = 'basic';
		$this->api_key = 'not-required-but-not-empty';
		$this->api_secret = 'not-required-but-not-empty';
		$this->token = $photonic_instagram_access_token; // Used in the Authentication page to see if a token is set
		$this->access_token = $photonic_instagram_access_token; // Used everywhere else. This is updated later based on the cached value in memory
		$this->field_list = 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username';
		$this->soon_limit = 30;

		$this->link_lightbox_title = empty($photonic_instagram_disable_title_link);
		$this->doc_links = [
			'general' => 'https://aquoid.com/plugins/photonic/instagram/',
		];
		$this->authenticate($photonic_instagram_access_token);

		$this->cached_token = $this->get_cached_token();
		if (!empty($this->cached_token)) {
			$this->access_token = $this->cached_token['oauth_token'];
		}
	}

	public static function get_instance() {
		if (self::$instance == null) {
			self::$instance = new Instagram();
		}
		return self::$instance;
	}

	/**
	 * Main function that fetches the images associated with the shortcode.
	 *
	 * @param array $attr
	 * @param array $gallery_meta
	 * @return string
	 */
	public function get_gallery_images($attr = [], &$gallery_meta = []) {
		global $photonic_instagram_main_size, $photonic_instagram_tile_size, $photonic_instagram_media, $photonic_thumbnail_style;
		$this->gallery_index++;
		$this->push_to_stack('Get Gallery Images');
		$attr = array_merge(
			$this->common_parameters,
			[
				// Common overrides ...
				'caption' => 'title',
				'thumb_size' => 75,
				'main_size' => $photonic_instagram_main_size,
				'tile_size' => $photonic_instagram_tile_size,

				// Instagram-specific ...
				'count' => 25,
				'layout' => (empty($photonic_thumbnail_style) || $photonic_thumbnail_style == 'square') ? 'random' : $photonic_thumbnail_style,
				'media' => $photonic_instagram_media,
				'embed_type' => 'embed',
				'carousel_handling' => 'expand',
			], $attr);

		if ($attr['tile_size'] == 'same') {
			$attr['tile_size'] = $attr['main_size'];
		}
		$attr = array_map('trim', $attr);

		extract($attr);

		if (empty($this->token) || empty($this->cached_token) || !$this->token_valid) {
			return $this->error(esc_html__("Instagram Access Token not valid. Please reauthenticate.", 'photonic'));
		}

		if (empty($user_id)) {
			$user_id = 'me';
		}

		$base_url = 'https://graph.instagram.com/';

		$display_what = 'media';
		if (!empty($media_id)) {// Trumps all else. A single photo will be shown.
			$id_format = preg_match('/^[A-Z][A-Z0-9_-]+/i', $media_id) ? 'old' : 'new';
			if ($id_format == 'old') {
				$query_url = 'http://api.instagram.com/oembed?url='.urlencode('http://instagram.com/p/'.$media_id.'/');
				$attr['embed_type'] = 'embed';
			}
			else {
				$query_url = $base_url.$media_id.'?fields='.$this->field_list;
			}
			$display_what = 'single-media';
		}
		else if (!empty($carousel)) {
			$query_url = $base_url.$carousel.'/children?fields=id,media_type,media_url,permalink,thumbnail_url';
			$display_what = 'carousel';
		}
		else if (!empty($user_id)) {
			$query_url = $base_url.$user_id.'/media?fields='.$this->field_list; // Doesn't matter what the other values are. User's photos will be shown.
		}
		else {
			if (empty($view)) {
				return $this->error(sprintf(esc_html__('The %s parameter has to be defined.', 'photonic'), '<code>view</code>'));
			}
			else {
				return $this->error(sprintf(esc_html__('Malformed shortcode. Either %1$s or %2$s or %3$s has to be defined.', 'photonic'),
					'<code>media_id</code>', '<code>carousel</code>', '<code>view</code>'));
			}
		}

		if (isset($count)) {
			$query_url = add_query_arg(['limit' => $count], $query_url);
		}

		if (isset($after)) {
			$query_url = add_query_arg(['after' => $after], $query_url);
		}

		$ret = $this->make_call($query_url, $display_what, $attr);
		$this->pop_from_stack();
		return $this->finalize_markup($ret, $attr).$this->get_stack_markup();
	}

	protected function make_call($query_url, $display_what, &$shortcode_attr) {
		$this->push_to_stack("Make call $query_url");
		$ret = '';

		if (!empty($this->cached_token) && $this->token_valid) {
			$query = add_query_arg(['access_token' => $this->access_token], $query_url);
		}
		else {
			$this->pop_from_stack(); // Make call, error encountered
			return $this->error(esc_html__("Instagram Access Token not valid. Please reauthenticate.", 'photonic'));
		}

		$this->push_to_stack('Send request');
		$response = wp_remote_request($query, [
			'sslverify' => PHOTONIC_SSL_VERIFY,
		]);
		$this->pop_from_stack(); // Send request

		$this->push_to_stack('Process response');
		if (!is_wp_error($response)) {
			if (isset($response['response']) && isset($response['response']['code'])) {
				if ($response['response']['code'] == 200) {
					$body = json_decode($response['body']);

					if (isset($body->paging) && isset($body->paging->next) && isset($body->paging->cursors) && isset($body->paging->cursors->after)) {
						$shortcode_attr['after'] = $body->paging->cursors->after;
						if (empty($shortcode_attr['more'])) {
							$shortcode_attr['more'] = esc_html__('More', 'photonic');
						}
					}
					else {
						if (isset($shortcode_attr['after'])) {
							unset($shortcode_attr['after']);
						}
					}

					if (isset($body->data) && $display_what != 'single-media') {
						$data = $body->data;
						$ret .= $this->process_media($data, $shortcode_attr);
					}
					else if ($display_what == 'single-media') {
						if (!empty($body->html)) { // Old-style id, can only operate in "embed" mode. Directly show the "embed"
							$ret .= $body->html;
						}
						else if (!empty($body->permalink)) { // New style id; need to re-execute the call with the permalink to embed, or display single-photo markup for "integrate"
							if ($shortcode_attr['embed_type'] == 'embed') {
								$query_url = 'http://api.instagram.com/oembed?url='.urlencode($body->permalink);
								$embed_response = wp_remote_request($query_url);
								if (!is_wp_error($embed_response) && isset($embed_response['response']) &&
									isset($embed_response['response']['code']) && $embed_response['response']['code'] == 200) {
									$embed_response = json_decode($embed_response['body']);
									if (!empty($embed_response->html)) {
										$ret .= $embed_response->html;
									}
									else {
										$this->pop_from_stack(); // 'Process response'
										$this->pop_from_stack(); // 'Make call'
										return $this->error(esc_html__('No data returned. Unknown error', 'photonic'));
									}
								}
								else {
									$this->pop_from_stack(); // 'Process response'
									$this->pop_from_stack(); // 'Make call'
									return $this->error(esc_html__('No data returned. Unknown error', 'photonic'));
								}
							}
							else {
								$this->pop_from_stack(); // 'Process response'
								$this->pop_from_stack(); // 'Make call'
								return $this->generate_single_photo_markup([
										'src' => $body->media_url,
										'href' => $body->permalink,
										'title' => '',
										'caption' => isset($body->caption) ? $body->caption : '',
									]
								);
							}
						}
					}
					else {
						$this->pop_from_stack(); // 'Process response'
						$this->pop_from_stack(); // 'Make call'
						return $this->error(esc_html__('No data returned. Unknown error', 'photonic'));
					}
				}
				else if (isset($response['body'])) {
					$body = json_decode($response['body']);
					if (isset($body->meta) && isset($body->meta->error_message)) {
						$this->pop_from_stack(); // 'Process response'
						$this->pop_from_stack(); // 'Make call'
						return $body->meta->error_message;
					}
					else {
						$this->pop_from_stack(); // 'Process response'
						$this->pop_from_stack(); // 'Make call'
						return $this->error(esc_html__('Unknown error', 'photonic'));
					}
				}
				else if (isset($response['response']['message'])) {
					$this->pop_from_stack(); // 'Process response'
					$this->pop_from_stack(); // 'Make call'
					return $this->error($response['response']['message']);
				}
				else {
					$this->pop_from_stack(); // 'Process response'
					$this->pop_from_stack(); // 'Make call'
					return $this->error(esc_html__('Unknown error', 'photonic'));
				}
			}
		}
		else {
			$this->pop_from_stack(); // 'Process response'
			$this->pop_from_stack(); // 'Make call'
			return $this->wp_error_message($response);
		}

		$this->pop_from_stack(); // 'Process response'
		$this->pop_from_stack(); // 'Make call'
		return $ret;
	}

	function process_media($data, $short_code) {
		global $photonic_instagram_photos_per_row_constraint, $photonic_instagram_photos_constrain_by_padding, $photonic_instagram_photos_constrain_by_count, $photonic_instagram_photo_title_display;

		$photo_objects = $this->build_level_1_objects($data, $short_code);
		$row_constraints = ['constraint-type' => $photonic_instagram_photos_per_row_constraint, 'padding' => $photonic_instagram_photos_constrain_by_padding, 'count' => $photonic_instagram_photos_constrain_by_count];

		$ret = $this->layout_gallery($photo_objects,
			[
				'title_position' => $photonic_instagram_photo_title_display,
				'row_constraints' => $row_constraints,
				'parent' => 'stream',
				'level_2_meta' => ['end' => 0, 'total' => empty($short_code['after']) ? 0 : $short_code['count']],
			],
			$short_code,
			1
		);
		return $ret;
	}

	function build_level_1_objects($data, $short_code, $module_parameters = [], $options = []) {
		$level_1_objects = [];

		$media = explode(',', $short_code['media']);
		$videos_ok = in_array('videos', $media) || in_array('all', $media);
		$photos_ok = in_array('photos', $media) || in_array('all', $media);

		foreach ($data as $photo) {
			if (isset($photo->media_type) && (((strtolower($photo->media_type) == 'image' || strtolower($photo->media_type) == 'carousel_album') && $photos_ok) || (strtolower($photo->media_type) == 'video' && $videos_ok)) && isset($photo->media_url)) {
				if (strtolower($photo->media_type) == 'carousel_album' && $short_code['carousel_handling'] == 'expand') {
					$query_url = 'https://graph.instagram.com/'.$photo->id.'/children?fields=id,media_type,media_url,permalink,thumbnail_url&access_token='.$this->access_token;
					$carousel_contents = $this->get_carousel_contents($query_url);
					$carousel_caption = empty($photo->caption) ? null : $photo->caption;

					foreach ($carousel_contents as $carousel_photo) {
						$this->process_single_item($carousel_photo, $level_1_objects, $photos_ok, $videos_ok, $carousel_photo->permalink, $carousel_photo->id, $carousel_caption); // Carousel photos have no caption, so use the post caption
					}
				}
				else {
					$this->process_single_item($photo, $level_1_objects, $photos_ok, $videos_ok, $photo->permalink, $photo->id);
				}
			}
		}
		return $level_1_objects;
	}

	function renew_token($current_token, $save) {
		$token = [];
		$error = '';
		$soon = $this->is_token_expiring_soon($this->soon_limit);

		if (is_null($soon)) {
			// No token exists. Do nothing, because the API secret is needed and authentication will need the back-end.
			// The call to is_token_expired will return true, prompting validation.
		}
		else {
			$photonic_authentication = get_option('photonic_authentication');
			$instagram_token = $photonic_authentication['instagram'];
			if ($soon > 0) {
				if (!empty($current_token)) {
					$response = wp_remote_request('https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token='.$current_token, [
						'sslverify' => PHOTONIC_SSL_VERIFY,
					]);

					if (!is_wp_error($response)) {
						$token = $this->parse_token($response);
						if (!empty($token)) {
							$token['client_id'] = $instagram_token['client_id'];
							$user_response = wp_remote_request('https://graph.instagram.com/me?fields=id,username&access_token='.$token['oauth_token'], [
								'sslverify' => PHOTONIC_SSL_VERIFY,
							]);
							if (!is_wp_error($user_response)) {
								$user_response = $user_response['body'];
								$user_response = json_decode($user_response);
								$token['user'] = $user_response->username;
							}
						}
						if ($save) {
							$this->save_token($token);
						}
					}
					else {
						$error = $response->get_error_message();
					}
				}
			}
			else if ($soon < 0) {
				// Token has expired. Do nothing, because the API secret is needed and authentication will need the back-end.
				// The call to is_token_expired will return true, prompting validation.
			}
			else {
				$token = $instagram_token;
			}
		}
		return [$token, $error];
	}

	function is_token_expired($token) {
		if (empty($token)) {
			return true;
		}

		if (!isset($token['oauth_token']) || !isset($token['oauth_token_created']) || !isset($token['oauth_token_expires'])) {
			return true;
		}

		$current = time();
		if ($token['oauth_token_created'] + $token['oauth_token_expires'] < $current) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if a token will expire soon. This is used to trigger a refresh for sources such as Instagram. Google uses a separate "Refresh Token",
	 * so this is not applicable to it. The <code>soon_limit</code> defines how many days is "soon", and a refresh is triggered if the current date
	 * is in the "soon" range. E.g. If you have a soon limit of 30 days, and your token expires in 15 days when you load the page, this method will
	 * return <code>true</code>.
	 *
	 * For cases where the token does not exist yet, the method returns <code>null</code>.
	 *
	 * @param $soon_limit int Number of days to check the expiry limit for.
	 * @return int|null If there is no token, return null. Otherwise, if there are < $soon_limit days left, return 1, if token is expired return -1, and if there is time return 0.
	 */
	function is_token_expiring_soon($soon_limit) {
		$photonic_authentication = get_option('photonic_authentication');
		if (empty($photonic_authentication) || empty($photonic_authentication[$this->provider]) ||
			empty($photonic_authentication[$this->provider]['oauth_token']) || empty($photonic_authentication[$this->provider]['oauth_token_created']) || empty($photonic_authentication[$this->provider]['oauth_token_expires'])) {
			return null; // There is no token!
		}

		$token = $photonic_authentication[$this->provider];
		$token_expiry = $token['oauth_token_created'] + $token['oauth_token_expires'];

		$current = time();
		$test_expiry = $current + $soon_limit * 24 * 60 * 60;

		$time_left = $token_expiry - $test_expiry;

		if ($current >= $token_expiry) {
			return -1; // already expired
		}
		else if ($time_left <= 0) {
			return 1; // Expiring soon
		}
		else {
			return 0; // There is still time
		}
	}

	/**
	 * @param $photo
	 * @param array $level_1_objects
	 * @param $photos_ok
	 * @param $videos_ok
	 * @param null $photo_link
	 * @param null $photo_id
	 * @param null $photo_caption
	 */
	private function process_single_item($photo, array &$level_1_objects, $photos_ok, $videos_ok, $photo_link = null, $photo_id = null, $photo_caption = null) {
		if (isset($photo->media_type) && (((strtolower($photo->media_type) == 'image' || strtolower($photo->media_type) == 'carousel_album') && $photos_ok) || (strtolower($photo->media_type) == 'video' && $videos_ok)) && isset($photo->media_url)) {
			$photo_object = [];
			$main_image = $photo->media_url;
			if (!empty($photo->thumbnail_url)) {
				$photo_object['thumbnail'] = $photo->thumbnail_url;
				$photo_object['tile_image'] = $photo->thumbnail_url;
			}
			else {
				$photo_object['thumbnail'] = $main_image;
				$photo_object['tile_image'] = $main_image;
			}

			$photo_object['main_image'] = $main_image;

			if (isset($photo->caption)) {
				$photo_object['title'] = esc_attr($photo->caption);
			}
			else if (!empty($photo_caption)) {
				$photo_object['title'] = esc_attr($photo_caption);
			}
			else {
				$photo_object['title'] = '';
			}

			$photo_object['alt_title'] = $photo_object['title'];
			$photo_object['description'] = $photo_object['title'];
			$photo_object['main_page'] = $photo_link;
			$photo_object['id'] = $photo_id;

			if (strtolower($photo->media_type) == 'video') {
				$photo_object['video'] = $photo->media_url;
				$parse = wp_parse_url($photo_object['video']);
				$parse = explode('.', $parse['path']);
				$photo_object['mime'] = 'video/' . $parse[count($parse) - 1];
			}
			$photo_object['provider'] = $this->provider;

			$level_1_objects[] = $photo_object;
		}
	}

	public function authentication_url() {
		// TODO: Implement authentication_url() method.
	}

	public function access_token_url() {
		// TODO: Implement access_token_url() method.
	}

	protected function set_token_validity($validity) {
		$this->token_valid = $validity;
	}

	/**
	 * @param string $query_url
	 * @return array|WP_Error
	 */
	private function get_carousel_contents($query_url) {
		$this->push_to_stack('Fetch carousel');
		$response = wp_remote_request($query_url, [
			'sslverify' => PHOTONIC_SSL_VERIFY,
		]);

		$carousel_contents = [];

		if (!is_wp_error($response)) {
			if (isset($response['response']) && isset($response['response']['code'])) {
				if ($response['response']['code'] == 200) {
					$body = json_decode($response['body']);
					if (isset($body->data)) {
						$carousel_contents = $body->data;
					}
				}
			}
		}
		$this->pop_from_stack(); // Fetch carousel
		return $carousel_contents;
	}
}