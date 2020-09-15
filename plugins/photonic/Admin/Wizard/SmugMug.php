<?php
namespace Photonic_Plugin\Admin\Wizard;

use Photonic_Plugin\Core\Photonic;
use Photonic_Plugin\Core\Utilities;

class SmugMug extends Source {
	private static $instance;

	protected function __construct() {
		parent::__construct();
		$this->provider = 'smugmug';

		global $photonic_smug_thumb_size, $photonic_smug_tile_size, $photonic_smug_main_size, $photonic_smug_video_size;
		$this->allowed_image_sizes['smugmug'] = [
			'thumb_size' => [
				'' => $this->default_from_settings,
				'Tiny' => esc_html__('Tiny', 'photonic'),
				'Thumb' => esc_html__('Thumb', 'photonic'),
				'Small' => esc_html__('Small', 'photonic'),
				'custom' => esc_html__('Custom', 'photonic'),
			],
			'tile_size' => [
				'' => $this->default_from_settings,
				'same' => esc_html__('Same as Main image size', 'photonic'),
				'4K' => esc_html__('4K (not always available)', 'photonic'),
				'5K' => esc_html__('5K (not always available)', 'photonic'),
				'Medium' => esc_html__('Medium', 'photonic'),
				'Original' => esc_html__('Original (not always available)', 'photonic'),
				'Large' => esc_html__('Large', 'photonic'),
				'Largest' => esc_html__('Largest available', 'photonic'),
				'XLarge' => esc_html__('XLarge (not always available)', 'photonic'),
				'X2Large' => esc_html__('X2Large (not always available)', 'photonic'),
				'X3Large' => esc_html__('X3Large (not always available)', 'photonic'),
				'X4Large' => esc_html__('X4Large (not always available)', 'photonic'),
				'X5Large' => esc_html__('X5Large (not always available)', 'photonic'),
			],
			'main_size' => [
				'' => $this->default_from_settings,
				'4K' => esc_html__('4K (not always available)', 'photonic'),
				'5K' => esc_html__('5K (not always available)', 'photonic'),
				'Medium' => esc_html__('Medium', 'photonic'),
				'Original' => esc_html__('Original (not always available)', 'photonic'),
				'Large' => esc_html__('Large', 'photonic'),
				'Largest' => esc_html__('Largest available', 'photonic'),
				'XLarge' => esc_html__('XLarge (not always available)', 'photonic'),
				'X2Large' => esc_html__('X2Large (not always available)', 'photonic'),
				'X3Large' => esc_html__('X3Large (not always available)', 'photonic'),
				'X4Large' => esc_html__('X4Large (not always available)', 'photonic'),
				'X5Large' => esc_html__('X5Large (not always available)', 'photonic'),
			],
			'video_size' => [
				'' => $this->default_from_settings,
				'110' => esc_html__('110px along longest side', 'photonic'),
				'200' => esc_html__('200px along longest side', 'photonic'),
				'320' => esc_html__('320px along longest side', 'photonic'),
				'640' => esc_html__('640px along longest side', 'photonic'),
				'1280' => esc_html__('1280px along longest side', 'photonic'),
				'1920' => esc_html__('1920px along longest side', 'photonic'),
				'Largest' => esc_html__('Largest available', 'photonic'),
			],
		];

		$this->allowed_image_sizes['smugmug']['thumb_size'][''] .= ' - '.$this->allowed_image_sizes['smugmug']['thumb_size'][$photonic_smug_thumb_size];
		$this->allowed_image_sizes['smugmug']['tile_size'][''] .= ' - '.$this->allowed_image_sizes['smugmug']['tile_size'][$photonic_smug_tile_size];
		$this->allowed_image_sizes['smugmug']['main_size'][''] .= ' - '.$this->allowed_image_sizes['smugmug']['main_size'][$photonic_smug_main_size];
		$this->allowed_image_sizes['smugmug']['video_size'][''] .= ' - '.$this->allowed_image_sizes['smugmug']['video_size'][$photonic_smug_video_size];
	}

	public static function get_instance() {
		if (self::$instance == null) {
			self::$instance = new SmugMug();
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
								'album-photo' => esc_html__('Photos from an Album', 'photonic'),
								'folder-photo' => esc_html__('Photos from a Folder', 'photonic'),
								'user-photo' => esc_html__('Photos from a User', 'photonic'),
								'multi-album' => esc_html__('Multiple Albums', 'photonic'),
								'folder' => esc_html__('Albums in a Folder', 'photonic'),
								'tree' => esc_html__('User Tree', 'photonic'),
							],
							'req' => 1,
						],
						'for' => [
							'desc' => esc_html__('For whom?', 'photonic'),
							'type' => 'radio',
							'options' => [
								'current' => sprintf(esc_html__('Current user (Defined under %s)', 'photonic'), '<em>Photonic &rarr; Settings &rarr; SmugMug &rarr; SmugMug Settings &rarr; Default user</em>'),
								'other' => esc_html__('Another user', 'photonic'),
							],
							'req' => 1,
						],
						'user' => [
							'desc' => sprintf(esc_html__('User name, e.g. %s', 'photonic'), 'https://<span style="text-decoration: underline">username</span>.smugmug.com/'),
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
			'album-photo' => [
				'header' => esc_html__('Pick your album', 'photonic'),
				'desc' => esc_html__('From the list below pick the album whose photos you wish to display. Photos from that album will show up as thumbnails.', 'photonic'),
				'display' => [
					'text' => [
						'desc' => esc_html__('Only show photos with this text', 'photonic'),
						'type' => 'text',
						'std' => '',
						'hint' => esc_html__('Comma-separated list of values. Filters will be applied on the front-end, not on the display below', 'photonic'),
					],
					'keywords' => [
						'desc' => esc_html__('Only show photos with these keywords', 'photonic'),
						'type' => 'text',
						'std' => '',
						'hint' => esc_html__('Comma-separated list of values. Filters will be applied on the front-end, not on the display below', 'photonic'),
					],
					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'single',
						'for' => 'selected_data',
					],
				],
			],
			'folder-photo' => [
				'header' => esc_html__('Pick your folder', 'photonic'),
				'desc' => esc_html__('From the list below pick the folder whose photos you wish to display. Photos from that folder will show up as thumbnails.', 'photonic'),
				'display' => [
					'text' => [
						'desc' => esc_html__('Only show photos with this text', 'photonic'),
						'type' => 'text',
						'std' => '',
						'hint' => esc_html__('Comma-separated list of values. Filters will be applied on the front-end, not on the display below', 'photonic'),
					],
					'keywords' => [
						'desc' => esc_html__('Only show photos with these keywords', 'photonic'),
						'type' => 'text',
						'std' => '',
						'hint' => esc_html__('Comma-separated list of values. Filters will be applied on the front-end, not on the display below.', 'photonic'),
					],
					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'single',
						'for' => 'selected_data',
					],
				],
			],
			'user-photo' => [
				'header' => esc_html__('Photos for a User', 'photonic'),
				'desc' => esc_html__('The following lists the top-level folders and albums for the selected user. All photos from these folders will show up as thumbnails.', 'photonic'),
				'display' => [
					'text' => [
						'desc' => esc_html__('Only show photos with this text', 'photonic'),
						'type' => 'text',
						'std' => '',
						'hint' => esc_html__('Comma-separated list of values', 'photonic'),
					],
					'keywords' => [
						'desc' => esc_html__('Only show photos with these keywords', 'photonic'),
						'type' => 'text',
						'std' => '',
						'hint' => esc_html__('Comma-separated list of values', 'photonic'),
					],
					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'none',
						'for' => 'selected_data',
					],
				],
			],
			'multi-album' => [
				'header' => esc_html__('Pick your albums', 'photonic'),
				'desc' => esc_html__('From the list below pick the albums you wish to display. Each album will show up as a single thumbnail.', 'photonic'),
				'display' => [
					'selection' => [
						'desc' => esc_html__('What do you want to show?', 'photonic'),
						'type' => 'select',
						'options' => [
							'all' => esc_html__('Automatic all (will automatically add new albums)', 'photonic'),
							'selected' => esc_html__('Selected albums', 'photonic'),
							'not-selected' => esc_html__('All except selected albums', 'photonic'),
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
			'folder' => [
				'header' => esc_html__('Pick your folder', 'photonic'),
				'desc' => esc_html__('From the list below pick the folder you wish to display. The albums within the folder will show up as single thumbnails.', 'photonic'),
				'display' => [
					'container' => [
						'type' => 'thumbnail-selector',
						'mode' => 'single',
						'for' => 'selected_data',
					],
				],
			],
			'tree' => [
				'header' => esc_html__('User Tree', 'photonic'),
				'desc' => esc_html__('The following user tree will be displayed on your site. Only top level folders and albums are shown here. The albums within the folders will show up as single thumbnails and can be clicked to show the images within.', 'photonic'),
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
		global $photonic_smug_media, $photonic_smug_title_caption;
		return [
			'smugmug' => [
				'L1' => [
					'media' => [
						'desc' => esc_html__('Media to Show', 'photonic'),
						'type' => 'select',
						'options' => Utilities::media_options(true, $photonic_smug_media),
						'std' => '',
						'hint' => sprintf($this->default_under, '<em>Photonic &rarr; Settings &rarr; SmugMug &rarr; SmugMug Settings &rarr; Media to show</em>'),
					],
					'caption' => [
						'desc' => esc_html__('Photo titles and captions', 'photonic'),
						'type' => 'select',
						'options' => Utilities::title_caption_options(true, $photonic_smug_title_caption),
						'std' => '',
						'hint' => sprintf($this->default_under, '<em>Photonic &rarr; Settings &rarr; SmugMug &rarr; SmugMug Settings &rarr; Photo titles and captions</em>'),
					],
					'password' => [
						'desc' => esc_html__('Password for password-protected album', 'photonic'),
						'type' => 'text',
						'req' => 1,
						'hint' => esc_html__('You are trying to display photos from a password-protected album. The password is mandatory for such an album.', 'photonic'),
						'conditions' => ['selection_passworded' => ['1']],
					],
					'sort_method' => [
						'desc' => esc_html__('Sort photos by', 'photonic'),
						'type' => 'select',
						'options' => [
							'' => '',
							'DateTaken' => esc_html__('Date Taken', 'photonic'),
							'DateUploaded' => esc_html__('Date Uploaded', 'photonic'),
							'Popular' => esc_html__('Popular', 'photonic'),
						],
					],
					'sort_order' => [
						'desc' => esc_html__('Sort order', 'photonic'),
						'type' => 'select',
						'options' => [
							'' => '',
							'Ascending' => esc_html__('Ascending', 'photonic'),
							'Descending' => esc_html__('Descending', 'photonic'),
						],
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
				'L2' => [
					'site_password' => [
						'desc' => esc_html__('Site Password for password-protected sites', 'photonic'),
						'type' => 'text',
						'hint' => esc_html__('If you SmugMug site is password-protected you will need to provide the password to be able to show your photos.', 'photonic'),
					],
					'album_sort_order' => [
						'desc' => esc_html__('Album sort order', 'photonic'),
						'type' => 'select',
						'options' => [
							'' => $this->default_from_settings,
							'Last Updated (Descending)' => esc_html__('Last Updated (Descending)', 'photonic'),
							'Last Updated (Ascending)' => esc_html__('Last Updated (Ascending)', 'photonic'),
							'Date Added (Descending)' => esc_html__('Date Added (Descending)', 'photonic'),
							'Date Added (Ascending)' => esc_html__('Date Added (Ascending)', 'photonic'),
						],
					],
				],
				'L3' => [
					'site_password' => [
						'desc' => esc_html__('Site Password for password-protected sites', 'photonic'),
						'type' => 'text',
						'hint' => esc_html__('If you SmugMug site is password-protected you will need to provide the password to be able to show your photos.', 'photonic'),
					],
				],
				'main_size' => [
					'desc' => esc_html__('Main image size', 'photonic'),
					'type' => 'select',
					'options' => $this->allowed_image_sizes['smugmug']['main_size'],
					'std' => '',
					'hint' => sprintf($this->default_under, '<em>Photonic &rarr; Settings &rarr; SmugMug &rarr; SmugMug Settings &rarr; Main image size</em>'),
				],
				'video_size' => [
					'desc' => esc_html__('Main video size', 'photonic'),
					'type' => 'select',
					'options' => $this->allowed_image_sizes['smugmug']['video_size'],
					'std' => '',
					'hint' => sprintf($this->default_under, '<em>Photonic &rarr; Settings &rarr; SmugMug &rarr; SmugMug Settings &rarr; Video size</em>'),
				],
			]
		];
	}

	function get_square_size_options() {
		return [
			'thumb_size' => [
				'desc' => esc_html__('Thumbnail size', 'photonic'),
				'type' => 'select',
				'options' => $this->allowed_image_sizes['smugmug']['thumb_size'],
				'std' => '',
				'hint' => sprintf($this->default_under, '<em>Photonic &rarr; Settings &rarr; SmugMug &rarr; SmugMug Settings &rarr; Thumbnail size</em>'),
			],
			'custom_thumb_size' => [
				'desc' => esc_html__('Size of custom thumbnail', 'photonic'),
				'type' => 'text',
				'hint' => esc_html__('E.g. 300x300 for an image that is 300px on the longest side and 300x300! (with the "!") for a square image', 'photonic'),
				'conditions' => ['thumb_size' => ['custom']],
			],
		];
	}

	function get_random_size_options() {
		return [
			'tile_size' => [
				'desc' => esc_html__('Tile size', 'photonic'),
				'type' => 'select',
				'options' => $this->allowed_image_sizes['smugmug']['tile_size'],
				'std' => '',
				'hint' => sprintf($this->default_under, '<em>Photonic &rarr; Settings &rarr; SmugMug &rarr; SmugMug Settings &rarr; Tile image size</em>'),
			],
		];
	}

	function make_request($display_type, $for, $flattened_fields) {
		global $photonic_smug_access_token, $photonic_smug_default_user;
		require_once(PHOTONIC_PATH.'/Modules/SmugMug.php');
		$module = \Photonic_Plugin\Modules\SmugMug::get_instance();

		if ($for == 'current' && empty($photonic_smug_default_user)) {
			return ['error' => sprintf(esc_html__('Default user not defined under %1$s. %2$sSelect "Another user" and put in your user id.', 'photonic'), '<em>Photonic &rarr; Settings &rarr; SmugMug &rarr; SmugMug Settings &rarr; Default User</em>', '<br/>')];
		}

		if ($for == 'other' && empty($_POST['user'])) {
			return ['error' => $this->error_mandatory];
		}

		$nick_name = sanitize_text_field($_POST['user']);
		$user = $for == 'current' ? $photonic_smug_default_user : ($for == 'other' ? $nick_name : '');
		$args = [
			'APIKey' => $module->api_key,
			'_accept' => 'application/json',
			'_expandmethod' => 'inline',
			'_verbosity' => '1',
		];

		if ($display_type == 'album-photo' || $display_type == 'multi-album') {
			$call = "https://api.smugmug.com/api/v2/user/$user!albums";
			$args['_expand'] = 'HighlightImage.ImageSizes';
			$args['count'] = 500;
		}
		else {
			$call = "https://api.smugmug.com/api/v2/user/$user";
		}

		if (!empty($photonic_smug_access_token)) {
			$args = $module->sign_call($call, 'GET', $args);
		}

		if ($display_type == 'folder' || $display_type == 'tree' || $display_type == 'folder-photo' || $display_type == 'user-photo') {
			// Not able to use wp_remote_request - getting a signature_invalid response
			$temp = Photonic::http($call, 'GET', $args, null, 300);
			$temp = $this->process_response($temp, 'smugmug', 'user');

			if (!empty($temp['success'])) {
				$node = $temp['success'];
				$call = 'https://api.smugmug.com' . $node . '!children';
				if ($display_type == 'tree' || $display_type == 'user-photo') {
					$args['_expand'] = 'NodeCoverImage.ImageSizes';
				}
				else {
					$config = $module->get_config_object(500);
					$config_str = json_encode($config);
					$args['_config'] = $config_str;
				}

				// Sign call again because we made an interim call
				if (!empty($photonic_smug_access_token)) {
					unset($args['oauth_consumer_key']);
					unset($args['oauth_nonce']);
					unset($args['oauth_signature']);
					unset($args['oauth_signature_method']);
					unset($args['oauth_timestamp']);
					unset($args['oauth_version']);
					unset($args['oauth_token']);
					unset($args['oauth_verifier']);
					$args = $module->sign_call($call, 'GET', $args);
				}
			}
			else {
				return $temp;
			}
		}

		$response = Photonic::http($call, 'GET', $args, null, 300);
		return [$response, ['nick_name' => $user], $call];
	}

	/**
	 * Processes a response from SmugMug to build it out into a gallery of thumbnails. SmugMug has L1, L2 and L3 displays in the flow.
	 *
	 * @param $response
	 * @param $display_type
	 * @param null $url
	 * @param array $pagination
	 * @return array
	 */
	function process_response($response, $display_type, $url = null, &$pagination = []) {
		$objects = [];
		$body = json_decode(wp_remote_retrieve_body($response));
		if (isset($body->Response) && isset($body->Response->Album)) {
			$albums = $body->Response->Album;
			foreach ($albums as $album) {
				$object = [];
				if (isset($album->AlbumKey)) {
					$object['id'] = $album->AlbumKey;
				}
				else {
					$uri = $album->Uris->Album->Uri;
					$uri = explode('/', $uri);
					$object['id'] = $uri[count($uri) - 1];
				}
				$object['title'] = !empty($album->Name) ? esc_attr($album->Name) : '';
				if (isset($album->ImageCount)) {
					$object['counters'] = [esc_html(sprintf(_n('%s media item', '%s media items', $album->ImageCount, 'photonic'), $album->ImageCount))];
				}

				$highlight = $album->Uris->HighlightImage;
				if (isset($highlight->Image)) {
					$thumbURL = $highlight->Image->Uris->ImageSizes->ImageSizes->ThumbImageUrl;
				}
				else {
					$thumbURL = PHOTONIC_URL.'include/images/placeholder-Th.png';
				}

				$object['thumbnail'] = $thumbURL;
				if (isset($album->SecurityType) && $album->SecurityType == 'Password') {
					$object['passworded'] = 1;
				}

				$objects[] = $object;
			}

			if (isset($body->Response->Pages)) {
				$pages = $body->Response->Pages;
				if ($pages->Start + $pages->Count - 1 < $pages->Total) {
					$pagination['url'] = add_query_arg(['start' => $pages->Start + $pages->Count, 'count' => $pages->Count], remove_query_arg(['start', 'count'], $url));
				}
			}
		}
		else if (isset($body->Response) && isset($body->Response->Node)) {
			$nodes = $body->Response->Node;
			foreach ($nodes as $node) {
				if ($display_type == 'folder' && $node->Type == 'Album') {
					continue;
				}
				else if ($display_type == 'folder') {
					$this->get_smugmug_folders($objects, $node);
				}
				else {
					$object = [];
					if ($node->Type == 'Album') {
						$uri = $node->Uris->Album->Uri;
						$uri = explode('/', $uri);
						$uri = $uri[count($uri) - 1];
						$object['id'] = $uri;
					}
					else if ($node->Type == 'Folder') {
						$object['id'] = $node->NodeID;
					}
					$object['title'] = !empty($node->Name) ? esc_attr($node->Name) : '';
					if ($display_type == 'tree') {
						$object['title'] .= " ({$node->Type})";
					}
					if (isset($node->Uris->NodeCoverImage->Image->Uris->ImageSizes->ImageSizes->ThumbImageUrl)) {
						$object['thumbnail'] = $node->Uris->NodeCoverImage->Image->Uris->ImageSizes->ImageSizes->ThumbImageUrl;
					}
					else {
						$object['thumbnail'] = PHOTONIC_URL.'include/images/placeholder-Th.png';
					}
					$objects[] = $object;
				}
			}
		}
		else if (isset($body->Response) && isset($body->Response->User)) {
			$user = $body->Response->User;
			return ['success' => $user->Uris->Node->Uri];
		}
		else {
			Photonic::log($body);
			return ['error' => $this->error_not_found];
		}

		return $objects;
	}

	/**
	 * A recursive call to traverse a SmugMug node and generate a list of objects, with each object corresponding to a folder.
	 * Actually a node is used instead of the folder because the folder object is deprecated by SmugMug, but nodes are only being
	 * used for folders in Photonic.
	 *
	 * @param array $objects
	 * @param $node
	 */
	private function get_smugmug_folders(&$objects, $node) {
		$object = [];
		if ($node->Type == 'Folder') {
			$object['id'] = $node->NodeID;
			$object['title'] = !empty($node->Name) ? esc_attr($node->Name) : '';

			if (isset($node->Uris->NodeCoverImage->Image->Uris->ImageSizes->ImageSizes->ThumbImageUrl)) {
				$object['thumbnail'] = $node->Uris->NodeCoverImage->Image->Uris->ImageSizes->ImageSizes->ThumbImageUrl;
			}
			else {
				$object['thumbnail'] = PHOTONIC_URL.'include/images/placeholder-Th.png';
			}

			if (isset($node->Uris->ChildNodes->Node)) {
				$child_nodes = $node->Uris->ChildNodes->Node;
				if (is_array($child_nodes)) {
					foreach ($child_nodes as $child_node) {
						$this->get_smugmug_folders($objects, $child_node);
					}
				}
			}
			$objects[] = $object;
		}
	}

	/**
	 * @param $display_type
	 * @return array|mixed
	 */
	function construct_shortcode_from_screen_selections($display_type) {
		$short_code = [];

		if ($display_type == 'album-photo') {
			$short_code['view'] = 'album';
			$short_code['album'] = sanitize_text_field($_POST['selected_data']);
		}
		else if ($display_type == 'folder-photo') {
			$short_code['view'] = 'images';
			$short_code['folder'] = sanitize_text_field($_POST['selected_data']);
		}
		else if ($display_type == 'user-photo') {
			$short_code['view'] = 'images';
		}
		else if ($display_type == 'multi-album') {
			$short_code['view'] = 'albums';
		}
		else if ($display_type == 'tree') {
			$short_code['view'] = 'tree';
		}
		else if ($display_type == 'folder') {
			$short_code['view'] = 'folder';
			$short_code['folder'] = sanitize_text_field($_POST['selected_data']);
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
			if ($input->view == 'tree') {
				$deconstructed['display_type'] = 'tree';
			}
			else if ($input->view == 'folder') {
				$deconstructed['display_type'] = 'folder';
			}
			else if ($input->view == 'albums') {
				$deconstructed['display_type'] = 'multi-album';
			}
			else if ($input->view == 'images' || $input->view == 'album') {
				if (!empty($input->album) || !empty($input->album_key) || !empty($input->album_id)) {
					$deconstructed['display_type'] = 'album-photo';
				}
				else if (!empty($input->folder)) {
					$deconstructed['display_type'] = 'folder-photo';
				}
				else if (!empty($input->nick_name)) {
					$deconstructed['display_type'] = 'user-photo';
				}
			}

			if (!empty($input->folder)) {
				$deconstructed['selected_data'] = trim($input->folder);
			}
			else if (!empty($input->album_key)) { // old syntax
				$deconstructed['selected_data'] = $input->album_key;
			}
			else if (!empty($input->album)) {
				$album = explode('_', $input->album);
				if (count($album) == 2) {
					$deconstructed['selected_data'] = $album[1];
				}
				else if (count($album) == 1) {
					$deconstructed['selected_data'] = $album[0];
				}
			}

			if (!empty($input->nick_name)) {
				$deconstructed['for'] = 'other';
				$deconstructed['user'] = $input->nick_name;
			}
		}

		return $deconstructed;
	}
}