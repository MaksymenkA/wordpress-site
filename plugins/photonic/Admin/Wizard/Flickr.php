<?php
namespace Photonic_Plugin\Admin\Wizard;

use Photonic_Plugin\Core\Photonic;
use Photonic_Plugin\Core\Utilities;

class Flickr extends Source {
	private static $instance;

	protected function __construct() {
		parent::__construct();
		$this->provider = 'flickr';

		global $photonic_flickr_thumb_size, $photonic_flickr_tile_size, $photonic_flickr_main_size, $photonic_flickr_video_size;
		$this->allowed_image_sizes['flickr'] = [
			'thumb_size' => [
				'' => $this->default_from_settings,
				's' => esc_html__('Small square, 75x75px', 'photonic'),
				'q' => esc_html__('Large square, 150x150px', 'photonic'),
				't' => esc_html__('Thumbnail, 100px on longest side', 'photonic'),
				'm' => esc_html__('Small, 240px on longest side', 'photonic'),
				'n' => esc_html__('Small, 320px on longest side', 'photonic'),
			],
			'tile_size' => [
				'' => $this->default_from_settings,
				'same' => esc_html__('Same as Main image size', 'photonic'),
				'n' => esc_html__('Small, 320px on longest side', 'photonic'),
				'none' => esc_html__('Medium, 500px on the longest side', 'photonic'),
				'z' => esc_html__('Medium, 640px on longest side', 'photonic'),
				'c' => esc_html__('Medium, 800px on longest side', 'photonic'),
				'b' => esc_html__('Large, 1024px on longest side', 'photonic'),
				'h' => esc_html__('Large, 1600px on longest side', 'photonic'),
				'k' => esc_html__('Large, 2048px on longest side', 'photonic'),
				'o' => esc_html__('Original', 'photonic'),
			],
			'main_size' => [
				'' => $this->default_from_settings,
				'none' => esc_html__('Medium, 500px on the longest side', 'photonic'),
				'z' => esc_html__('Medium, 640px on longest side', 'photonic'),
				'c' => esc_html__('Medium, 800px on longest side', 'photonic'),
				'b' => esc_html__('Large, 1024px on longest side', 'photonic'),
				'h' => esc_html__('Large, 1600px on longest side', 'photonic'),
				'k' => esc_html__('Large, 2048px on longest side', 'photonic'),
				'o' => esc_html__('Original', 'photonic'),
			],
			'video_size' => [
				'' => $this->default_from_settings,
				'Site MP4' => esc_html__('Site MP4', 'photonic'),
				'Mobile MP4' => esc_html__('Mobile MP4', 'photonic'),
				'HD MP4' => esc_html__('HD MP4', 'photonic'),
				'Video Original' => esc_html__('Video Original', 'photonic'),
			],
		];
		$this->allowed_image_sizes['flickr']['thumb_size'][''] .= ' - '.$this->allowed_image_sizes['flickr']['thumb_size'][$photonic_flickr_thumb_size];
		$this->allowed_image_sizes['flickr']['tile_size'][''] .= ' - '.$this->allowed_image_sizes['flickr']['tile_size'][$photonic_flickr_tile_size];
		$this->allowed_image_sizes['flickr']['main_size'][''] .= ' - '.$this->allowed_image_sizes['flickr']['main_size'][$photonic_flickr_main_size];
		$this->allowed_image_sizes['flickr']['video_size'][''] .= ' - '.$this->allowed_image_sizes['flickr']['video_size'][$photonic_flickr_video_size];

	}

	public static function get_instance() {
		if (self::$instance == null) {
			self::$instance = new Flickr();
		}
		return self::$instance;
	}

	function get_screen_2() {
		return [
			'header' => esc_html__('Choose Type of Gallery', 'photonic'),
			'display' => [
				'kind' => [
					'type' => 'field_list',
					'list_type' => 'sequence',
					'list' => [
						'display_type' => [
							'desc' => esc_html__('What do you want to show?', 'photonic'),
							'type' => 'select',
							'options' => [
								'' => '',
								'single-photo' => esc_html__('A Single Photo', 'photonic'),
								'multi-photo' => esc_html__('Multiple Photos', 'photonic'),
								'album-photo' => esc_html__('Photos from an Album / Photoset', 'photonic'),
								'gallery-photo' => esc_html__('Photos from a Gallery', 'photonic'),
								'multi-album' => esc_html__('Multiple Albums', 'photonic'),
								'multi-gallery' => esc_html__('Multiple Galleries', 'photonic'),
								'collection' => esc_html__('Albums from a single collection', 'photonic'),
								'collections' => esc_html__('Multiple collections', 'photonic'),
							],
							'req' => 1,
						],
						'for' => [
							'desc' => esc_html__('For whom?', 'photonic'),
							'type' => 'radio',
							'options' => [
								'current' => sprintf(esc_html__('Current user (Defined under %s)', 'photonic'), '<em>Photonic &rarr; Settings &rarr; Flickr &rarr; Flickr Settings &rarr; Default user</em>'),
								'other' => esc_html__('Another user', 'photonic'),
								'group' => esc_html__('Group', 'photonic'),
								'any' => esc_html__('All users', 'photonic'),
							],
							'option-conditions' => [
								'group' => ['display_type' => ['multi-photo']],
								'any' => ['display_type' => ['multi-photo']],
							],
							'req' => 1,
						],
						'login' => [
							'desc' => sprintf(esc_html__('User name, e.g. %s', 'photonic'), 'https://www.flickr.com/photos/<span style="text-decoration: underline">username</span>/'),
							'type' => 'text',
							'std' => '',
							'conditions' => ['for' => ['other']],
							'req' => 1,
						],
						'group' => [
							'desc' => sprintf(esc_html__('Group name, e.g. %s', 'photonic'), 'https://www.flickr.com/groups/<span style="text-decoration: underline">groupname</span>/'),
							'type' => 'text',
							'std' => '',
							'conditions' => ['for' => ['group']],
							'req' => 1,
						],
					],
				],
			],
		];
	}

	function get_screen_3() {
		return [
			'header' => esc_html__('Build your gallery', 'photonic'),
			'single-photo' => [
				'header' => esc_html__('Pick a photo', 'photonic'),
				'desc' => esc_html__('From the list below pick the single photo you wish to display.', 'photonic'),
				'display' => [
					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'single',
						'for' => 'selected_data',
					],
				],
			],
			'multi-photo' => [
				'header' => esc_html__('All your photos', 'photonic'),
				'desc' => esc_html__('You can show all your photos, or apply tags to show some of them.', 'photonic'),
				'display' => [
					'tags' => [
						'desc' => esc_html__('Tags', 'photonic'),
						'type' => 'text',
						'hint' => esc_html__('Comma-separated list of tags', 'photonic')
					],

					'tag_mode' => [
						'desc' => esc_html__('Tag mode', 'photonic'),
						'type' => 'select',
						'options' => [
							'any' => esc_html__('Any tag', 'photonic'),
							'all' => esc_html__('All tags', 'photonic'),
						],
					],

					'text' => [
						'desc' => esc_html__('With text', 'photonic'),
						'type' => 'text',
					],

					'privacy_filter' => [
						'desc' => esc_html__('Privacy filter', 'photonic'),
						'type' => 'select',
						'options' => [
							'' => esc_html__('None', 'photonic'),
							'1' => esc_html__('Public photos', 'photonic'),
							'2' => esc_html__('Private photos visible to friends', 'photonic'),
							'3' => esc_html__('Private photos visible to family', 'photonic'),
							'4' => esc_html__('Private photos visible to friends & family', 'photonic'),
							'5' => esc_html__('Completely private photos', 'photonic'),
						],
						'hint' => sprintf(esc_html__('Applicable only if Flickr private photos are turned on (%1$s) and Back-end authentication is off (%2$s)', 'photonic'), '<em>Photonic &rarr; Settings &rarr; Flickr &rarr; Flickr Settings &rarr; Allow User Login</em>', '<em>Photonic &rarr; Settings &rarr; Flickr &rarr; Flickr Settings &rarr; Access Token</em>'),
					],

					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'none',
						'for' => 'selected_data',
					],
				],
			],
			'album-photo' => [
				'header' => esc_html__('Pick your album', 'photonic'),
				'desc' => esc_html__('From the list below pick the album whose photos you wish to display. Photos from that album will show up as thumbnails.', 'photonic'),
				'display' => [
					'privacy_filter' => [
						'desc' => esc_html__('Privacy filter', 'photonic'),
						'type' => 'select',
						'options' => [
							'' => esc_html__('None', 'photonic'),
							'1' => esc_html__('Public photos', 'photonic'),
							'2' => esc_html__('Private photos visible to friends', 'photonic'),
							'3' => esc_html__('Private photos visible to family', 'photonic'),
							'4' => esc_html__('Private photos visible to friends & family', 'photonic'),
							'5' => esc_html__('Completely private photos', 'photonic'),
						],
						'hint' => sprintf(esc_html__('Applicable only if Flickr private photos are turned on (%1$s) and Back-end authentication is off (%2$s)', 'photonic'), '<em>Photonic &rarr; Settings &rarr; Flickr &rarr; Flickr Settings &rarr; Allow User Login</em>', '<em>Photonic &rarr; Settings &rarr; Flickr &rarr; Flickr Settings &rarr; Access Token</em>'),
					],

					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'single',
						'for' => 'selected_data',
					],
				],
			],
			'gallery-photo' => [
				'header' => esc_html__('Pick your gallery', 'photonic'),
				'desc' => esc_html__('From the list below pick the gallery whose photos you wish to display. Photos from that gallery will show up as thumbnails.', 'photonic'),
				'display' => [
					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'single',
						'for' => 'selected_data',
					],
				],
			],
			'multi-album' => [
				'header' => esc_html__('Pick your albums / photosets', 'photonic'),
				'desc' => esc_html__('From the list below pick the albums / photosets you wish to display. Each album will show up as a single thumbnail.', 'photonic'),
				'display' => [
					'selection' => [
						'desc' => esc_html__('What do you want to show?', 'photonic'),
						'type' => 'select',
						'options' => [
							'all' => esc_html__('Automatic all (will automatically add new albums)', 'photonic'),
							'selected' => esc_html__('Selected albums / photosets', 'photonic'),
							'not-selected' => esc_html__('All except selected albums / photosets', 'photonic'),
						],
						'hint' => esc_html__('If you pick "Automatic all" your selections below will be ignored.', 'photonic'),
						'req' => 1,
					],
					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'multi',
						'for' => 'selected_data',
					],
				],
			],
			'multi-gallery' => [
				'header' => esc_html__('Pick your galleries', 'photonic'),
				'desc' => esc_html__('From the list below pick the galleries you wish to display. Each album will show up as a single thumbnail.', 'photonic'),
				'display' => [
					'selection' => [
						'desc' => esc_html__('What do you want to show?', 'photonic'),
						'type' => 'select',
						'options' => [
							'all' => esc_html__('Automatic all (will automatically add new galleries)', 'photonic'),
							'selected' => esc_html__('Selected galleries', 'photonic'),
							'not-selected' => esc_html__('All except selected galleries', 'photonic'),
						],
						'req' => 1,
						'hint' => esc_html__('If you pick "Automatic all" your selections below will be ignored.', 'photonic'),
					],
					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'multi',
						'for' => 'selected_data',
					],
				],
			],
			'collection' => [
				'header' => esc_html__('Pick your collection', 'photonic'),
				'desc' => esc_html__('From the list below pick the collection you wish to display. The albums within the collections will show up as single thumbnails.', 'photonic'),
				'display' => [
					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'single',
						'for' => 'selected_data',
					],
				],
			],
			'collections' => [
				'header' => esc_html__('Pick your collections', 'photonic'),
				'desc' => esc_html__('From the list below pick the collections you wish to display. The albums within the collections will show up as single thumbnails.', 'photonic'),
				'display' => [
					'selection' => [
						'desc' => esc_html__('What do you want to show?', 'photonic'),
						'type' => 'select',
						'options' => [
							'all' => esc_html__('Automatic all (will automatically add new collections)', 'photonic'),
							'selected' => esc_html__('Selected collections', 'photonic'),
							'not-selected' => esc_html__('All except selected collections', 'photonic'),
						],
						'req' => 1,
						'hint' => esc_html__('If you pick "Automatic all" your selections below will be ignored.', 'photonic'),
					],
					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'multi',
						'for' => 'selected_data',
					],
				],
			],
		];
	}

	function get_screen_4() {
		return [];
	}

	function get_screen_5() {
		global $photonic_flickr_media, $photonic_flickr_title_caption;
		$output = [
			'flickr' => [
				'L1' => [
					'sort' => [
						'desc' => esc_html__('Sort by', 'photonic'),
						'type' => 'select',
						'options' => [
							'' => '',
							'date-posted-desc' => esc_html__('Date posted, descending', 'photonic'),
							'date-posted-asc' => esc_html__('Date posted, ascending', 'photonic'),
							'date-taken-asc' => esc_html__('Date taken, ascending', 'photonic'),
							'date-taken-desc' => esc_html__('Date taken, descending', 'photonic'),
							'interestingness-desc' => esc_html__('Interestingness, descending', 'photonic'),
							'interestingness-asc' => esc_html__('Interestingness, ascending', 'photonic'),
							'relevance' => esc_html__('Relevance', 'photonic'),
						],
					],
					'media' => [
						'desc' => esc_html__('Media to Show', 'photonic'),
						'type' => 'select',
						'options' => Utilities::media_options(true, $photonic_flickr_media),
						'std' => '',
						'hint' => sprintf($this->default_under, '<em>Photonic &rarr; Settings &rarr; Flickr &rarr; Flickr Settings &rarr; Media to show</em>'),
					],
					'caption' => [
						'desc' => esc_html__('Photo titles and captions', 'photonic'),
						'type' => 'select',
						'options' => Utilities::title_caption_options(true, $photonic_flickr_title_caption),
						'std' => '',
						'hint' => sprintf($this->default_under, '<em>Photonic &rarr; Settings &rarr; Flickr &rarr; Flickr Settings &rarr; Photo titles and captions</em>'),
					],
					'headers' => [
						'desc' => esc_html__('Show Header', 'photonic'),
						'type' => 'select',
						'options' => [
							'' => $this->default_from_settings,
							'none' => esc_html__('No header', 'photonic'),
							'title' => esc_html__('Title only', 'photonic'),
							'thumbnail' => esc_html__('Thumbnail only', 'photonic'),
							'counter' => esc_html__('Counts only', 'photonic'),
							'title,counter' => esc_html__('Title and counts', 'photonic'),
							'thumbnail,counter' => esc_html__('Thumbnail and counts', 'photonic'),
							'thumbnail,title' => esc_html__('Thumbnail and title', 'photonic'),
							'thumbnail,title,counter' => esc_html__('Thumbnail, title and counts', 'photonic'),
						],
						'conditions' => ['display_type' => ['album-photo', 'gallery-photo']],
					],
				],
				'L2' => [],
				'L3' => [
					'collections_display' => [
						'desc' => esc_html__('Expand Collections', 'photonic'),
						'type' => 'select',
						'options' => [
							'' => '',
							'lazy' => esc_html__('Lazy loading', 'photonic'),
							'expanded' => esc_html__('Expanded upfront', 'photonic'),
						],
						'hint' => sprintf(esc_html__('The Collections API is slow, so, if you are displaying collections, pick %1$slazy loading%2$s if your collections have many albums / photosets.', 'photonic'),
							'<a href="https://aquoid.com/plugins/photonic/flickr/flickr-collections/" target="_blank">', '</a>'),
					],
					'headers' => [
						'desc' => esc_html__('Show Header', 'photonic'),
						'type' => 'select',
						'options' => [
							'' => $this->default_from_settings,
							'none' => esc_html__('No header', 'photonic'),
							'title' => esc_html__('Title only', 'photonic'),
							'thumbnail' => esc_html__('Thumbnail only', 'photonic'),
							'counter' => esc_html__('Counts only', 'photonic'),
							'title,counter' => esc_html__('Title and counts', 'photonic'),
							'thumbnail,counter' => esc_html__('Thumbnail and counts', 'photonic'),
							'thumbnail,title' => esc_html__('Thumbnail and title', 'photonic'),
							'thumbnail,title,counter' => esc_html__('Thumbnail, title and counts', 'photonic'),
						],
					],
				],
				'main_size' => [
					'desc' => esc_html__('Main image size', 'photonic'),
					'type' => 'select',
					'options' => $this->allowed_image_sizes['flickr']['main_size'],
					'std' => '',
					'hint' => sprintf($this->default_under, '<em>Photonic &rarr; Settings &rarr; Flickr &rarr; Flickr Settings &rarr; Main image size</em>'),
				],
				'video_size' => [
					'desc' => esc_html__('Main video size', 'photonic'),
					'type' => 'select',
					'options' => $this->allowed_image_sizes['flickr']['video_size'],
					'std' => '',
					'hint' => sprintf($this->default_under, '<em>Photonic &rarr; Settings &rarr; Flickr &rarr; Flickr Settings &rarr; Video size</em>'),
				],
			]
		];
//		$output['slideshow'] = $this->get_slideshow_options();
		return $output;
	}

	function get_square_size_options() {
		return [
			'thumb_size' => [
				'desc' => esc_html__('Thumbnail size', 'photonic'),
				'type' => 'select',
				'options' => $this->allowed_image_sizes['flickr']['thumb_size'],
				'std' => '',
				'hint' => sprintf($this->default_under, '<em>Photonic &rarr; Settings &rarr; Flickr &rarr; Flickr Settings &rarr; Thumbnail size</em>'),
			],
		];
	}

	function get_random_size_options() {
		return [
			'tile_size' => [
				'desc' => esc_html__('Tile size', 'photonic'),
				'type' => 'select',
				'options' => $this->allowed_image_sizes['flickr']['tile_size'],
				'std' => '',
				'hint' => sprintf($this->default_under, '<em>Photonic &rarr; Settings &rarr; Flickr &rarr; Flickr Settings &rarr; Tile image size</em>'),
			],
		];
	}

	function make_request($display_type, $for, $flattened_fields) {
		if ($display_type != 'multi-photo' && in_array($for, ['group', 'any'])) {
			$err = esc_html__('Incompatible selections:', 'photonic') . "<br/>\n";
			$err .= $flattened_fields['display_type']['desc'] . ": " . $flattened_fields['display_type']['options'][$display_type] . "<br/>\n";
			$err .= $flattened_fields['for']['desc'] . ": " . $flattened_fields['for']['options'][$for] . "<br/>\n";
			return ['error' => $err];
		}

		$group = sanitize_text_field($_POST['group']);
		$login = sanitize_text_field($_POST['login']);
		global $photonic_flickr_default_user, $photonic_flickr_api_key;
		if ($for == 'current' && empty($photonic_flickr_default_user)) {
			return ['error' => sprintf(esc_html__('Default user not defined under %1$s. %2$sSelect "Another user" and put in your user id.', 'photonic'),
				'<em>Photonic &rarr; Settings &rarr; Flickr &rarr; Flickr Settings &rarr; Default User</em>', '<br/>')];
		}

		if (($for == 'group' && empty($group)) || ($for == 'other' && empty($login))) {
			return ['error' => $this->error_mandatory];
		}

		$parameters = [];
		$user = $for == 'current' ? $photonic_flickr_default_user : ($for == 'other' ? $login : '');
		if (($for == 'other' || $for == 'current') && !empty($user)) {
			$url = 'https://api.flickr.com/services/rest/?format=json&nojsoncallback=1&api_key=' . $photonic_flickr_api_key . '&method=flickr.urls.lookupUser&url=' . urlencode('https://www.flickr.com/photos/') . $user;
			$response = wp_remote_request($url, ['sslverify' => PHOTONIC_SSL_VERIFY]);
			$response = $this->process_response($response, 'flickr', 'user');
			if (!empty($response['error'])) {
				// Maybe the user provided the full URL instead of just the user name?
				$url = 'https://api.flickr.com/services/rest/?format=json&nojsoncallback=1&api_key=' . $photonic_flickr_api_key . '&method=flickr.urls.lookupUser&url=' . urlencode($user);
				$response = wp_remote_request($url, ['sslverify' => PHOTONIC_SSL_VERIFY]);
				$response = $this->process_response($response, 'flickr', 'user');
				if (!empty($response['error'])) {
					return $response;
				}
				$parameters = array_merge($response['success'], $parameters);
			}
			else {
				$parameters = array_merge($response['success'], $parameters);
			}
		}

		if ($for == 'group' && !empty($group)) {
			$url = 'https://api.flickr.com/services/rest/?format=json&nojsoncallback=1&api_key=' . $photonic_flickr_api_key . '&method=flickr.urls.lookupGroup&url=' . urlencode('https://www.flickr.com/groups/') . $group;
			$response = wp_remote_request($url, ['sslverify' => PHOTONIC_SSL_VERIFY]);
			$response = $this->process_response($response, 'flickr', 'group');
			if (!empty($response['error'])) {
				// Maybe the user provided the full URL instead of just the group name?
				$url = 'https://api.flickr.com/services/rest/?format=json&nojsoncallback=1&api_key=' . $photonic_flickr_api_key . '&method=flickr.urls.lookupGroup&url=' . urlencode($user);
				$response = wp_remote_request($url, ['sslverify' => PHOTONIC_SSL_VERIFY]);
				$response = $this->process_response($response, 'flickr', 'group');
				if (!empty($response['error'])) {
					return $response;
				}
				$parameters = array_merge($response['success'], $parameters);
			}
			else {
				$parameters = array_merge($response['success'], $parameters);
			}
		}

		// All OK so far. Let's try to get the data for the next screen
		$parameters['api_key'] = $photonic_flickr_api_key;
		$parameters['format'] = 'json';
		$parameters['nojsoncallback'] = 1;

		if ($display_type == 'single-photo') {
			$parameters['view'] = 'photo';
			$parameters['method'] = 'flickr.photos.search';
			$parameters['per_page'] = 500;
		}
		else if ($display_type == 'multi-photo') {
			$parameters['view'] = 'photos';
			$parameters['method'] = 'flickr.photos.search';
		}
		else if ($display_type == 'multi-album' || $display_type == 'album-photo') {
			$parameters['view'] = 'photosets';
			$parameters['method'] = 'flickr.photosets.getList';
		}
		else if ($display_type == 'multi-gallery' || $display_type == 'gallery-photo') {
			$parameters['view'] = 'galleries';
			$parameters['method'] = 'flickr.galleries.getList';
			$parameters['per_page'] = 500;
		}
		else if ($display_type == 'collection' || $display_type == 'collections') {
			$parameters['view'] = 'collections';
			$parameters['method'] = 'flickr.collections.getTree';
		}

		global $photonic_flickr_access_token;
		require_once(PHOTONIC_PATH.'/Modules/Flickr.php');
		$module = \Photonic_Plugin\Modules\Flickr::get_instance();
		if (!empty($photonic_flickr_access_token)) {
			$parameters = $module->sign_call('https://api.flickr.com/services/rest/', 'GET', $parameters);
		}

		$url = add_query_arg($parameters, 'https://api.flickr.com/services/rest/');
		$response = wp_remote_request($url, ['sslverify' => PHOTONIC_SSL_VERIFY]);

		$hidden = [];
		if (isset($parameters['user_id'])) $hidden['user_id'] = $parameters['user_id'];
		if (isset($parameters['group_id'])) $hidden['group_id'] = $parameters['group_id'];

		return [$response, $hidden, $url];
	}

	/**
	 * Processes a response from Flickr to build it out into a gallery of thumbnails. Flickr has L1, L2 and L3 displays in the flow.
	 *
	 * @param $response
	 * @param $display_type
	 * @param null $url
	 * @param array $pagination
	 * @return mixed|void
	 */
	function process_response($response, $display_type, $url = null, &$pagination = []) {
		$objects = [];
		$body = json_decode(wp_remote_retrieve_body($response));

		if (isset($body->stat) && $body->stat == 'fail') {
			Photonic::log($response);
			return ['error' => $body->message];
		}

		if (isset($body->photosets) && isset($body->photosets->photoset)) {
//			$page = (int)($body->photosets->page); // Not needed because apparently the photosets.getList call returns all photosets for now
//			$pages = (int)($body->photosets->pages); // Not needed because apparently the photosets.getList call returns all photosets for now
			$photosets = $body->photosets->photoset;
			foreach ($photosets as $flickr_object) {
				$object = [];
				$object['id'] = $flickr_object->id;
				$object['title'] = esc_attr($flickr_object->title->_content);
				$object['counters'] = [];
				if (!empty($flickr_object->photos)) $object['counters'][] = esc_html(sprintf(_n('%s photo', '%s photos', $flickr_object->photos, 'photonic'), $flickr_object->photos));
				if (!empty($flickr_object->videos)) $object['counters'][] = esc_html(sprintf(_n('%s video', '%s videos', $flickr_object->videos, 'photonic'), $flickr_object->videos));
				$object['thumbnail'] = "https://farm" . $flickr_object->farm . ".staticflickr.com/" . $flickr_object->server . "/" . $flickr_object->primary . "_" . $flickr_object->secret . "_q.jpg";
				$objects[] = $object;
			}
		}
		else if (isset($body->galleries) && isset($body->galleries->gallery)) {
			$page = (int)($body->galleries->page);
			$pages = (int)($body->galleries->pages);
			$galleries = $body->galleries->gallery;
			foreach ($galleries as $flickr_object) {
				$object = [];
				$object['id'] = substr($flickr_object->id, strpos($flickr_object->id, '-') + 1); //$flickr_object->id;
				$object['title'] = esc_attr($flickr_object->title->_content);
				$object['counters'] = [];
				if (!empty($flickr_object->count_photos)) $object['counters'][] = esc_html(sprintf(_n('%s photo', '%s photos', $flickr_object->count_photos, 'photonic'), $flickr_object->count_photos));
				if (!empty($flickr_object->count_videos)) $object['counters'][] = esc_html(sprintf(_n('%s video', '%s videos', $flickr_object->count_videos, 'photonic'), $flickr_object->count_videos));
				$object['thumbnail'] = "https://farm" . $flickr_object->primary_photo_farm . ".staticflickr.com/" . $flickr_object->primary_photo_server . "/" . $flickr_object->primary_photo_id . "_" . $flickr_object->primary_photo_secret . "_q.jpg";
				$objects[] = $object;
			}
		}
		else if (isset($body->photos) && isset($body->photos->photo)) {
			if ($display_type == 'single-photo') {
				$page = (int)($body->photos->page);
				$pages = (int)($body->photos->pages);
				if ($page < $pages && !empty($url)) {
					$url = remove_query_arg('page', $url);
					$pagination['url'] = add_query_arg(['page' => $page + 1], $url);
				}
			}
			$photos = $body->photos->photo;
			foreach ($photos as $flickr_object) {
				$object = [];
				$object['id'] = $flickr_object->id;
				$object['title'] = esc_attr($flickr_object->title);
				$object['thumbnail'] = 'https://farm' . $flickr_object->farm . '.staticflickr.com/' . $flickr_object->server . '/' . $flickr_object->id . '_' . $flickr_object->secret . '_q.jpg';
				$objects[] = $object;
			}
		}
		else if (isset($body->collections) && isset($body->collections->collection)) {
			$collections = $body->collections->collection;
			foreach ($collections as $flickr_object) {
				$object = [];
				$object['id'] =  substr($flickr_object->id, strpos($flickr_object->id, '-') + 1);
				$object['title'] = esc_attr($flickr_object->title);
				$object['counters'] = [];
				if (!empty($flickr_object->set)) $object['counters'][] = esc_html(sprintf(_n('%s album', '%s albums', count($flickr_object->set), 'photonic'), count($flickr_object->set)));
				$object['thumbnail'] = $flickr_object->iconlarge;
				$objects[] = $object;
			}
		}
		else if (isset($body->user)) {
			return ['success' => ['user_id' => $body->user->id]];
		}
		else if (isset($body->group)) {
			return ['success' => ['group_id' => $body->group->id]];
		}

		if (!empty($page) && !empty($pages) && $page < $pages && !empty($url)) {
			$url = remove_query_arg('page', $url);
			$pagination['url'] = add_query_arg(['page' => $page + 1], $url);
		}
		return $objects;
	}

	/**
	 * @param $display_type
	 * @return array|mixed
	 */
	function construct_shortcode_from_screen_selections($display_type) {
		$short_code = [];

		if ($display_type == 'single-photo') {
			$short_code['view'] = 'photo';
			$short_code['photo_id'] = sanitize_text_field($_POST['selected_data']);
		}
		else if ($display_type == 'multi-photo') {
			$short_code['view'] = 'photos';
		}
		else if ($display_type == 'album-photo') {
			$short_code['view'] = 'photosets';
			$short_code['photoset_id'] = sanitize_text_field($_POST['selected_data']);
		}
		else if ($display_type == 'gallery-photo') {
			$short_code['view'] = 'galleries';
			$short_code['gallery_id'] = sanitize_text_field($_POST['selected_data']);
		}
		else if ($display_type == 'multi-album') {
			$short_code['view'] = 'photosets';
		}
		else if ($display_type == 'multi-gallery') {
			$short_code['view'] = 'galleries';
		}
		else if ($display_type == 'collection') {
			$short_code['view'] = 'collections';
			$short_code['collection_id'] = sanitize_text_field($_POST['selected_data']);
		}
		else if ($display_type == 'collections') {
			$short_code['view'] = 'collections';
		}

		return $short_code;
	}

	/**
	 * @param $input
	 * @return array|mixed
	 */
	function deconstruct_shortcode_to_screen_selections($input) {
		$deconstructed = [];

		// Potential for an existing (old) shortcode to not have a user_id. E.g. Just defining photoset_id.
		// Rather than defaulting to the default user, we will make the user put it in this time.
		if (!empty($input->user_id)) {
			$deconstructed['for'] = 'other';
			$deconstructed['login'] = $input->user_id;
		}
		else if (!empty($input->group_id)) {
			$deconstructed['for'] = 'group';
			$deconstructed['group'] = $input->group_id;
		}

		if (!empty($input->view)) {
			if ($input->view == 'collections' && empty($input->collection_id)) {
				$deconstructed['display_type'] = 'collections';
			}
			else if ($input->view == 'galleries' && empty($input->gallery_id)) {
				$deconstructed['display_type'] = 'multi-gallery';
			}
			else if ($input->view == 'photosets' && empty($input->photoset_id)) {
				$deconstructed['display_type'] = 'multi-album';
			}
			else if ($input->view == 'photos') {
				$deconstructed['display_type'] = 'multi-photo';
			}
		}

		if (!empty($input->collection_id)) {
			$deconstructed['display_type'] = 'collection';
			$deconstructed['selected_data'] = $input->collection_id;
		}
		else if (!empty($input->gallery_id)) {
			$deconstructed['display_type'] = 'gallery-photo';
			$deconstructed['selected_data'] = $input->gallery_id;
		}
		else if (!empty($input->photoset_id)) {
			$deconstructed['display_type'] = 'album-photo';
			$deconstructed['selected_data'] = $input->photoset_id;
		}
		else if (!empty($input->photo_id)) {
			$deconstructed['display_type'] = 'single-photo';
			$deconstructed['selected_data'] = $input->photo_id;
		}

		return $deconstructed;
	}
}