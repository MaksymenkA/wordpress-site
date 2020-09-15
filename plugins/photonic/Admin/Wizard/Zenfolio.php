<?php
namespace Photonic_Plugin\Admin\Wizard;

use Photonic_Plugin\Core\Photonic;
use Photonic_Plugin\Core\Utilities;
use Requests;

class Zenfolio extends Source {
	private static $instance;

	protected function __construct() {
		parent::__construct();
		$this->provider = 'zenfolio';

		global $photonic_zenfolio_thumb_size, $photonic_zenfolio_tile_size, $photonic_zenfolio_main_size, $photonic_zenfolio_video_size;
		$this->allowed_image_sizes['zenfolio'] = [
			'thumb_size' => [
				'' => $this->default_from_settings,
				"1" => esc_html__("Square thumbnail, 60 &times; 60px, cropped square", 'photonic'),
				"0" => esc_html__("Small thumbnail, upto 80 &times; 80px", 'photonic'),
				"10" => esc_html__("Medium thumbnail, upto 120 &times; 120px", 'photonic'),
				"11" => esc_html__("Large thumbnail, upto 120 &times; 120px", 'photonic'),
				"2" => esc_html__("Small image, upto 400 &times; 400px", 'photonic'),
			],
			'tile_size' => [
				'' => $this->default_from_settings,
				'same' => esc_html__('Same as Main image size', 'photonic'),
				'2' => esc_html__('Small image, upto 400 &times; 400px', 'photonic'),
				'3' => esc_html__('Medium image, upto 580 &times; 450px', 'photonic'),
				'4' => esc_html__('Large image, upto 800 &times; 630px', 'photonic'),
				'5' => esc_html__('X-Large image, upto 1100 &times; 850px', 'photonic'),
				'6' => esc_html__('XX-Large image, upto 1550 &times; 960px', 'photonic'),
			],
			'main_size' => [
				'' => $this->default_from_settings,
				'2' => esc_html__('Small image, upto 400 &times; 400px', 'photonic'),
				'3' => esc_html__('Medium image, upto 580 &times; 450px', 'photonic'),
				'4' => esc_html__('Large image, upto 800 &times; 630px', 'photonic'),
				'5' => esc_html__('X-Large image, upto 1100 &times; 850px', 'photonic'),
				'6' => esc_html__('XX-Large image, upto 1550 &times; 960px', 'photonic'),
			],
			'video_size' => [
				'' => $this->default_from_settings,
				'220' => esc_html__('360p resolution (MP4)', 'photonic'),
				'215' => esc_html__('480p resolution (MP4)', 'photonic'),
				'210' => esc_html__('720p resolution (MP4)', 'photonic'),
				'200' => esc_html__('1080p resolution (MP4)', 'photonic'),
			],
		];
		$this->allowed_image_sizes['zenfolio']['thumb_size'][''] .= ' - '.$this->allowed_image_sizes['zenfolio']['thumb_size'][$photonic_zenfolio_thumb_size];
		$this->allowed_image_sizes['zenfolio']['tile_size'][''] .= ' - '.$this->allowed_image_sizes['zenfolio']['tile_size'][$photonic_zenfolio_tile_size];
		$this->allowed_image_sizes['zenfolio']['main_size'][''] .= ' - '.$this->allowed_image_sizes['zenfolio']['main_size'][$photonic_zenfolio_main_size];
		$this->allowed_image_sizes['zenfolio']['video_size'][''] .= ' - '.$this->allowed_image_sizes['zenfolio']['video_size'][$photonic_zenfolio_video_size];
	}

	public static function get_instance() {
		if (self::$instance == null) {
			self::$instance = new Zenfolio();
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
								'single-photo' => esc_html__('Single Photo', 'photonic'),
								'multi-photo' => esc_html__('Multiple Photos', 'photonic'),
								'gallery-photo' => esc_html__('Photos from a Gallery or Collection', 'photonic'),
//										'collection-photo' => esc_html__('Photos from a Collection', 'photonic'),
								'multi-gallery' => esc_html__('Multiple Galleries', 'photonic'),
								'multi-collection' => esc_html__('Multiple Collections', 'photonic'),
								'multi-gallery-collection' => esc_html__('Multiple Galleries and Collections', 'photonic'),
								'group' => esc_html__('Single Group', 'photonic'),
								'group-hierarchy' => esc_html__('Group Hierarchy', 'photonic'),
							],
							'req' => 1,
						],
						'for' => [
							'desc' => esc_html__('For whom?', 'photonic'),
							'type' => 'radio',
							'options' => [
								'current' => sprintf(esc_html__('Current user (Defined under %s)', 'photonic'), '<em>Photonic &rarr; Settings &rarr; Zenfolio &rarr; Zenfolio Photo Settings &rarr; Default user</em>'),
								'other' => esc_html__('Another user', 'photonic'),
								'any' => esc_html__('All users', 'photonic'),
							],
							'option-conditions' => [
								'current' => ['display_type' => ['single-photo', 'gallery-photo', 'collection-photo', 'multi-gallery', 'multi-collection', 'multi-gallery-collection', 'group', 'group-hierarchy']],
								'other' => ['display_type' => ['single-photo', 'gallery-photo', 'collection-photo', 'multi-gallery', 'multi-collection', 'multi-gallery-collection', 'group', 'group-hierarchy']],
								'any' => ['display_type' => ['multi-photo', /*'multi-gallery', 'multi-collection', */]],
							],
							'req' => 1,
						],
						'login_name' => [
							'desc' => sprintf(esc_html__('User name, e.g. %s', 'photonic'), 'https://<span style="text-decoration: underline">username</span>.zenfolio.com/'),
							'type' => 'text',
							'std' => '',
							'conditions' => ['for' => ['other']],
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
				'header' => esc_html__('Photos from all users', 'photonic'),
				'desc' => esc_html__('You can show photos from all users, and apply text or category filters to show some of them.', 'photonic'),
				'display' => [
					'text' => [
						'desc' => esc_html__('With text', 'photonic'),
						'type' => 'text',
					],

					'category_code' => [
						'desc' => esc_html__('Category', 'photonic'),
						'type' => 'select',
						'options' => $this->get_zenfolio_categories(),
					],

					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'none',
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
			'collection-photo' => [
				'header' => esc_html__('Pick your collection', 'photonic'),
				'desc' => esc_html__('From the list below pick the collection whose photos you wish to display. Photos from that collection will show up as thumbnails.', 'photonic'),
				'display' => [
					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'single',
						'for' => 'selected_data',
					],
				],
			],
			'multi-gallery' => [
				'header' => esc_html__('Pick your galleries', 'photonic'),
				'desc' => esc_html__('From the list below pick the galleries you wish to display. Each album will show up as a single thumbnail. Note that text and category filters are not applied here but will be applied on the front-end.', 'photonic'),
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

					'text' => [
						'desc' => esc_html__('With text', 'photonic'),
						'type' => 'text',
					],

					'category_code' => [
						'desc' => esc_html__('Category', 'photonic'),
						'type' => 'select',
						'options' => $this->get_zenfolio_categories(),
					],

					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'multi',
						'for' => 'selected_data',
					],
				],
			],
			'multi-collection' => [
				'header' => esc_html__('Pick your collections', 'photonic'),
				'desc' => esc_html__('From the list below pick the collections you wish to display. Each collection will show up as a single thumbnail. Note that text and category filters are not applied here but will be applied on the front-end.', 'photonic'),
				'display' => [
					'selection' => [
						'desc' => esc_html__('What do you want to show?', 'photonic'),
						'type' => 'select',
						'options' => [
							'all' => esc_html__('Automatic all (will automatically add new collections)', 'photonic'),
							'selected' => esc_html__('Selected collections', 'photonic'),
							'not-selected' => esc_html__('All except selected collections', 'photonic'),
						],
						'hint' => esc_html__('If you pick "Automatic all" your selections below will be ignored.', 'photonic'),
						'req' => 1,
					],

					'text' => [
						'desc' => esc_html__('With text', 'photonic'),
						'type' => 'text',
					],

					'category_code' => [
						'desc' => esc_html__('Category', 'photonic'),
						'type' => 'select',
						'options' => $this->get_zenfolio_categories(),
					],

					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'multi',
						'for' => 'selected_data',
					],
				],
			],
			'multi-gallery-collection' => [
				'header' => esc_html__('Pick your galleries and collections', 'photonic'),
				'desc' => esc_html__('From the list below pick the galleries and collections you wish to display. Each gallery and collection will show up as a single thumbnail. Note that text and category filters are not applied here but will be applied on the front-end.', 'photonic'),
				'display' => [
					'selection' => [
						'desc' => esc_html__('What do you want to show?', 'photonic'),
						'type' => 'select',
						'options' => [
							'all' => esc_html__('Automatic all (will automatically add new galleries and collections)', 'photonic'),
							'selected' => esc_html__('Selected galleries and collections', 'photonic'),
							'not-selected' => esc_html__('All except selected galleries and collections', 'photonic'),
						],
						'hint' => esc_html__('If you pick "Automatic all" your selections below will be ignored.', 'photonic'),
						'req' => 1,
					],

					'text' => [
						'desc' => esc_html__('With text', 'photonic'),
						'type' => 'text',
					],

					'category_code' => [
						'desc' => esc_html__('Category', 'photonic'),
						'type' => 'select',
						'options' => $this->get_zenfolio_categories(),
					],

					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'multi',
						'for' => 'selected_data',
					],
				],
			],
			'group' => [
				'header' => esc_html__('Pick your group', 'photonic'),
				'desc' => esc_html__('From the list below pick the group you wish to display. The galleries / collections within the group will show up as single thumbnails.', 'photonic'),
				'display' => [
					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'single',
						'for' => 'selected_data',
					],
				],
			],
			'group-hierarchy' => [
				'header' => esc_html__('Your group hierarchy', 'photonic'),
				'desc' => esc_html__('The following group hierarchy will be displayed on your site. Only top level groups and galleries / collections are shown here. The galleries / collections within the groups will show up as single thumbnails and can be clicked to show the images within.', 'photonic'),
				'display' => [
					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'none',
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
		global $photonic_zenfolio_media, $photonic_zenfolio_title_caption;
		return [
			'zenfolio' => [
				'L1' => [
					'media' => [
						'desc' => esc_html__('Media to Show', 'photonic'),
						'type' => 'select',
						'options' => Utilities::media_options(true, $photonic_zenfolio_media),
						'std' => '',
						'hint' => sprintf($this->default_under, '<em>Photonic &rarr; Settings &rarr; Zenfolio &rarr; Zenfolio Photo Settings &rarr; Media to show</em>'),
					],
					'caption' => [
						'desc' => esc_html__('Photo titles and captions', 'photonic'),
						'type' => 'select',
						'options' => Utilities::title_caption_options(true, $photonic_zenfolio_title_caption),
						'std' => '',
						'hint' => sprintf($this->default_under, '<em>Photonic &rarr; Settings &rarr; Zenfolio &rarr; Zenfolio Photo Settings &rarr; Photo titles and captions</em>'),
					],
					'sort_order' => [
						'desc' => esc_html__('Search results sort order', 'photonic'),
						'type' => 'select',
						'options' => [
							'' => '',
							'Date' => esc_html__('Date', 'photonic'),
							'Popularity' => esc_html__('Popularity', 'photonic'),
							'Rank' => esc_html__('Rank (for searching by text only)', 'photonic'),
						],
					],
					'password' => [
						'desc' => esc_html__('Password for password-protected album', 'photonic'),
						'type' => 'text',
						'req' => 1,
						'hint' => esc_html__('You are trying to display photos from a password-protected album. The password is mandatory for such an album.', 'photonic'),
						'conditions' => ['selection_passworded' => ['1']],
					],
				],
				'L2' => [
					'sort_order' => [
						'desc' => esc_html__('Search results sort order', 'photonic'),
						'type' => 'select',
						'options' => [
							'' => '',
							'Date' => esc_html__('Date', 'photonic'),
							'Popularity' => esc_html__('Popularity', 'photonic'),
							'Rank' => esc_html__('Rank (for searching by text only)', 'photonic'),
						],
					],
				],
				'L3' => [
					'structure' => [
						'desc' => esc_html__('Group / Hierarchy structure', 'photonic'),
						'type' => 'select',
						'options' => [
							'' => '',
							'flat' => esc_html__('All photosets shown in single level', 'photonic'),
							'nested' => esc_html__('Photosets shown nested within groups', 'photonic'),
						],
						'hint' => sprintf(esc_html__('See examples %1$shere%2$s.', 'photonic'), '<a href="https://aquoid.com/plugins/photonic/zenfolio/group-hierarchy/" target="_blank">', '</a>'),
					],
					'headers' => [
						'desc' => esc_html__('Show Group Header', 'photonic'),
						'type' => 'select',
						'options' => [
							'' => $this->default_from_settings,
							'none' => esc_html__('No header', 'photonic'),
							'title' => esc_html__('Title only', 'photonic'),
							'counter' => esc_html__('Counts only', 'photonic'),
							'title,counter' => esc_html__('Title and counts', 'photonic'),
						],
					],
				],
				'main_size' => [
					'desc' => esc_html__('Main image size', 'photonic'),
					'type' => 'select',
					'options' => $this->allowed_image_sizes['zenfolio']['main_size'],
					'std' => '',
					'hint' => sprintf($this->default_under, '<em>Photonic &rarr; Settings &rarr; Zenfolio &rarr; Zenfolio Photo Settings &rarr; Main image size</em>'),
				],
				'video_size' => [
					'desc' => esc_html__('Main video size', 'photonic'),
					'type' => 'select',
					'options' => $this->allowed_image_sizes['zenfolio']['video_size'],
					'std' => '',
					'hint' => sprintf($this->default_under, '<em>Photonic &rarr; Settings &rarr; Zenfolio &rarr; Zenfolio Photo Settings &rarr; Video size</em>'),
				],
			]
		];
	}

	private function get_zenfolio_categories() {
		$response = wp_remote_request('https://api.zenfolio.com/api/1.8/zfapi.asmx/GetCategories', ['sslverify' => PHOTONIC_SSL_VERIFY]);
		$category_list = ['' => ''];

		if (!is_wp_error($response)) {
			if (isset($response['response']) && isset($response['response']['code'])) {
				if ($response['response']['code'] == 200) {
					if (isset($response['body'])) {
						$response = simplexml_load_string($response['body']);
						if (!empty($response->Category)) {
							$categories = $response->Category;
							foreach ($categories as $category) {
								$category_list[esc_attr($category->Code)] = $category->DisplayName;
							}
						}
					}
				}
			}
		}
		asort($category_list);
		return $category_list;
	}

	function get_square_size_options() {
		return [
			'thumb_size' => [
				'desc' => esc_html__('Thumbnail size', 'photonic'),
				'type' => 'select',
				'options' => $this->allowed_image_sizes['zenfolio']['thumb_size'],
				'std' => '',
				'hint' => sprintf($this->default_under, '<em>Photonic &rarr; Settings &rarr; Zenfolio &rarr; Zenfolio Photo Settings &rarr; Thumbnail size</em>'),
			],
		];
	}

	function get_random_size_options() {
		return [
			'tile_size' => [
				'desc' => esc_html__('Tile size', 'photonic'),
				'type' => 'select',
				'options' => $this->allowed_image_sizes['zenfolio']['tile_size'],
				'std' => '',
				'hint' => sprintf($this->default_under, '<em>Photonic &rarr; Settings &rarr; Zenfolio &rarr; Zenfolio Photo Settings &rarr; Tile image size</em>'),
			],
		];
	}

	function make_request($display_type, $for, $flattened_fields) {
		if ((!in_array($display_type, ['multi-photo', /*'multi-gallery', 'multi-collection'*/]) && $for == 'any') ||
			(!in_array($display_type, ['single-photo', 'gallery-photo', 'collection-photo', 'multi-gallery', 'multi-collection', 'multi-gallery-collection', 'group', 'group-hierarchy']) && in_array($for, ['current', 'other']))) {
			$err = esc_html__('Incompatible selections:', 'photonic') . "<br/>\n";
			$err .= $flattened_fields['display_type']['desc'] . ": " . $flattened_fields['display_type']['options'][$display_type] . "<br/>\n";
			$err .= $flattened_fields['for']['desc'] . ": " . $flattened_fields['for']['options'][$for] . "<br/>\n";
			return ['error' => $err];
		}

		if ($for == 'other' && empty($_POST['login_name'])) {
			return ['error' => $this->error_mandatory];
		}

		$login_name = sanitize_text_field($_POST['login_name']);
		global $photonic_zenfolio_default_user;
		if ($for == 'current' && empty($photonic_zenfolio_default_user)) {
			return ['error' => sprintf(esc_html__('Default user not defined under %1$s. %2$sSelect "Another user" and put in your user id.', 'photonic'), '<em>Photonic &rarr; Settings &rarr; Zenfolio &rarr; Zenfolio Photo Settings &rarr; Default User</em>', '<br/>')];
		}

		$parameters = [];
		$user = $for == 'current' ? $photonic_zenfolio_default_user : ($for == 'other' ? $login_name : '');

		if ($display_type == 'multi-photo') {
			$url = 'https://api.zenfolio.com/api/1.8/zfapi.asmx/SearchPhotoByCategory';
			$parameters['searchId'] = '5';
			$parameters['sortOrder'] = 'Popularity';
			$parameters['categoryCode'] = '1018000';
			$parameters['offset'] = 0;
			$parameters['limit'] = 500;
		}
		else if (in_array($display_type, ['multi-gallery', 'multi-collection']) && $for == 'any') {
			$url = 'https://api.zenfolio.com/api/1.8/zfapi.asmx/SearchSetByCategory';
			$parameters['searchId'] = '5';
			$parameters['sortOrder'] = 'Popularity';
			$parameters['categoryCode'] = '1018000';
			$parameters['offset'] = 0;
			$parameters['limit'] = 500;
		}
		else {
			$url = 'https://api.zenfolio.com/api/1.8/zfapi.asmx/LoadGroupHierarchy';
			$parameters['loginName'] = $user;
		}

		$response = wp_remote_request($url, ['sslverify' => PHOTONIC_SSL_VERIFY, 'body' => $parameters]);
		return [$response, ['login_name' => $user], $url];
	}

	/**
	 * Processes a response from Zenfolio to build it out into a gallery of thumbnails. Zenfolio has both, L1 and L2 displays in the flow.
	 *
	 * @param $response
	 * @param $display_type
	 * @param $url
	 * @param array $pagination
	 * @return array
	 */
	function process_response($response, $display_type, $url = null, &$pagination = []) {
		$body = wp_remote_retrieve_body($response);
		$body = preg_replace('/"Id":(\d+)/', '"Id":"$1"', $body);
		$body = simplexml_load_string($body);
		$objects = [];
		if ($display_type != 'multi-photo') {
			if (!empty($body->Elements)) {
				$elements = $body->Elements;
				$this->get_zenfolio_groups($objects, $elements, $display_type);
			}
			else if (!empty($body->PhotoSets)) {
				$photosets = $body->PhotoSets;
				$this->get_zenfolio_groups($objects, $photosets, $display_type);
			}

			if ($display_type == 'single-photo') {
				require_once(PHOTONIC_PATH.'/Modules/Zenfolio.php');

				$photo_array = [];
				$gallery = \Photonic_Plugin\Modules\Zenfolio::get_instance();
				$requests = [];
				foreach ($objects as $object) {
					$parameters = [];
					$parameters['photoSetId'] = substr($object['id'], 1);
					$parameters['level'] = 'Level1';
					$parameters['includePhotos'] = 'true';

					$request = $gallery->prepare_request('LoadPhotoSet', $parameters);
					$requests[] = [
						'url' => 'https://api.zenfolio.com/api/1.8/zfapi.asmx',
						'type' => 'POST',
						'headers' => $request['headers'],
						'data' => $request['body'],
					];
				}
				$responses = Requests::request_multiple($requests);
				if (!empty($responses)) {
					foreach ($responses as $ps_response) {
						if (is_a($ps_response, 'Requests_Response')) {
							$ps_response = json_decode($ps_response->body);
							if (!empty($ps_response->result)) {
								$ps_response = $ps_response->result;
								if (!empty($ps_response->Photos)) {
									$photo_array['psid'.$ps_response->Id] = $ps_response->Title;
									foreach ($ps_response->Photos as $ps_photo) {
										if (array_key_exists($ps_photo->Id, $photo_array)) {
											continue;
										}
										$photo = [];
										$photo['id'] = $ps_photo->Id;
										$url_parts = explode('/', $ps_photo->UrlCore);
										$photo['alt_id'] = 'h'.dechex((int)substr($url_parts[count($url_parts) - 1], 1));
										$photo['alt_id2'] = $url_parts[count($url_parts) - 1];
										$photo['title'] = esc_attr($ps_photo->Title);
										$photo['thumbnail'] = 'https://' . $ps_photo->UrlHost . $ps_photo->UrlCore . '-1.jpg';
										$photo_array[$ps_photo->Id] = $photo;
									}
								}
							}
						}
					}
				}
				$objects = array_values($photo_array);
			}
			return $objects;
		}
		else {
			$photos = $body->Photos;
			foreach ($photos->Photo as $photo) {
				$object = [];
				$object['id'] = $photo->Id;
				$object['title'] = esc_attr($photo->Title);
				$object['thumbnail'] = 'https://' . $photo->UrlHost . $photo->UrlCore . '-1.jpg';
				$objects[] = $object;
			}
			return $objects;
		}
	}

	/**
	 * A recursive call to traverse a Zenfolio group and generate a list of objects, with each object corresponding to a photoset.
	 *
	 * @param $objects
	 * @param $elements
	 * @param $display_type
	 */
	private function get_zenfolio_groups(&$objects, $elements, $display_type) {
		if (!empty($elements->PhotoSet) && $display_type != 'group') {
			foreach ($elements->PhotoSet as $photoset) {
				if ((($display_type == 'gallery-photo' || $display_type == 'multi-gallery') && $photoset->Type == 'Gallery') ||
					(($display_type == 'collection-photo' || $display_type == 'multi-collection') && $photoset->Type == 'Collection') ||
					$display_type == 'multi-gallery-collection' || $display_type == 'group-hierarchy' || $display_type == 'single-photo'
				) {
					$object = [];
					$page_url = parse_url($photoset->PageUrl);
					$page_url = $page_url['path'];
					$page_url = explode('/', $page_url);
					if (count($page_url) > 1) {
						$page_url = $page_url[1];
					}

					if (!is_array($page_url) && preg_match('/^p\d{9}/', $page_url) === 0) {
						$page_url = [];
					}

					$object['id'] = !is_array($page_url) ? $page_url : $photoset->Id;
					$object['alt_id'] = $photoset->Id;
					$object['title'] = esc_attr($photoset->Title);
					$object['counters'] = [esc_html(sprintf(_n('%s media item', '%s media items', $photoset->PhotoCount, 'photonic'), $photoset->PhotoCount))];

					$photo = $photoset->TitlePhoto;
					$object['thumbnail'] = 'https://' . $photo->UrlHost . $photo->UrlCore . '-1.jpg';

					if (!empty($photoset->AccessDescriptor) && !empty($photoset->AccessDescriptor->AccessType) && $photoset->AccessDescriptor->AccessType == 'Password') {
						$object['passworded'] = 1;
					}

					$objects[] = $object;
				}
			}
		}

		if (!empty($elements->Group)) {
			foreach ($elements->Group as $group) {
				if ($display_type == 'group' || $display_type == 'group-hierarchy') {
					$object = [];
					$object['id'] = $group->Id;
					$object['title'] = $group->Title;
					$object['thumbnail'] = PHOTONIC_URL.'include/images/placeholder-Ti.png';
					$objects[] = $object;
				}

				if ($display_type != 'group-hierarchy' && !empty($group->Elements)) {
					$this->get_zenfolio_groups($objects, $group->Elements, $display_type);
				}
			}
		}
	}

	/**
	 * @param $display_type
	 * @return array|mixed
	 */
	function construct_shortcode_from_screen_selections($display_type) {
		$short_code = [];

		if ($display_type == 'multi-photo') {
			$short_code['view'] = 'photos';
		}
		else if ($display_type == 'single-photo') {
			$short_code['view'] = 'photos';
			$short_code['object_id'] = sanitize_text_field($_POST['selected_data']);
		}
		else if ($display_type == 'gallery-photo'/* || $display_type == 'collection-photo'*/) {
			$short_code['view'] = 'photosets';
			$short_code['object_id'] = sanitize_text_field($_POST['selected_data']);
		}
		else if ($display_type == 'multi-gallery' || $display_type == 'multi-collection' || $display_type == 'multi-gallery-collection') {
			$short_code['view'] = 'photosets';
			if ($display_type == 'multi-gallery') {
				$short_code['photoset_type'] = 'Gallery';
			}
			else if ($display_type == 'multi-collection') {
				$short_code['photoset_type'] = 'Collection';
			}
		}
		else if ($display_type == 'group') {
			$short_code['view'] = 'group';
			$short_code['object_id'] = sanitize_text_field($_POST['selected_data']);
		}
		else if ($display_type == 'group-hierarchy') {
			$short_code['view'] = 'hierarchy';
		}

		return $short_code;
	}

	/**
	 * @param $input
	 * @return array|mixed
	 */
	function deconstruct_shortcode_to_screen_selections($input) {
		$deconstructed = [];

		if (!empty($input->view)) {
			if ($input->view == 'photos' && empty($input->object_id)) {
				$deconstructed['display_type'] = 'multi-photo';
			}
			else if ($input->view == 'photos' && !empty($input->object_id)) {
				$deconstructed['display_type'] = 'single-photo';
				$deconstructed['selected_data'] = $input->object_id;
			}
			else if ($input->view == 'photosets') {
				if (!empty($input->object_id)) {
					$deconstructed['display_type'] = 'gallery-photo';
					$deconstructed['selected_data'] = $input->object_id;
				}
				else if (empty($input->photoset_type)) {
					$deconstructed['display_type'] = 'multi-gallery-collection';
				}
				else if (strtolower($input->photoset_type) == 'collection') {
					$deconstructed['display_type'] = 'multi-collection';
				}
				else if (strtolower($input->photoset_type) == 'gallery') {
					$deconstructed['display_type'] = 'multi-gallery';
				}
			}
			else if ($input->view == 'hierarchy') {
				$deconstructed['display_type'] = 'group-hierarchy';
			}
			else if ($input->view == 'group') {
				$deconstructed['display_type'] = 'group';
				if (!empty($input->object_id)) {
					$deconstructed['selected_data'] = $input->object_id;
				}
			}

			if ($input->view == 'photosets' || $input->view == 'hierarchy' || $input->view == 'group' ||
				$deconstructed['display_type'] == 'single-photo') {
				global $photonic_zenfolio_default_user;
				if (!isset($input->login_name) && !empty($photonic_zenfolio_default_user)) {
					$deconstructed['for'] = 'current';
				}
				else if (!empty($input->login_name)) {
					$deconstructed['login_name'] = $input->login_name;
					$deconstructed['for'] = 'other';
				}
			}
		}

		return $deconstructed;
	}
}