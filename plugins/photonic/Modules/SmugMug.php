<?php
namespace Photonic_Plugin\Modules;

use Photonic_Plugin\Core\Photonic;
use WP_Error;

require_once('OAuth1.php');
require_once('Level_One_Module.php');

class SmugMug extends OAuth1 implements Level_One_Module {
	var $highlights_processed;
	private static $instance = null;

	protected function __construct() {
		parent::__construct();
		global $photonic_smug_api_key, $photonic_smug_api_secret, $photonic_smug_access_token, $photonic_smug_disable_title_link, $photonic_smug_token_secret, $photonic_smug_show_buy_link;
//		$this->api_key = trim($photonic_smug_api_key);
		$this->api_key = !empty($photonic_smug_api_key) ? trim($photonic_smug_api_key) : '86MZ8N8TqJf5x2fQ4FRWXRtJ3C6Jm7XV';
		$this->api_secret = trim($photonic_smug_api_secret);
		$this->token = trim($photonic_smug_access_token);
		$this->token_secret = trim($photonic_smug_token_secret);
		$this->provider = 'smug';
		$this->link_lightbox_title = empty($photonic_smug_disable_title_link);
		$this->oauth_version = '1.0a';
		$this->base_url = 'https://api.smugmug.com/api/v2/';
		$this->show_buy_link = !empty($photonic_smug_show_buy_link);

		$this->doc_links = [
			'general' => 'https://aquoid.com/plugins/photonic/smugmug/',
			'photos' => 'https://aquoid.com/plugins/photonic/smugmug/smugmug-photos/',
			'albums' => 'https://aquoid.com/plugins/photonic/smugmug/smugmug-albums/',
			'tree' => 'https://aquoid.com/plugins/photonic/smugmug/smugmug-tree/',
			'folders' => 'https://aquoid.com/plugins/photonic/smugmug/folders/',
		];

		$this->set_oauth_done();
		$this->highlights_processed = [];
	}

	public static function get_instance() {
		if (self::$instance == null) {
			self::$instance = new SmugMug();
		}
		return self::$instance;
	}

	/**
	 * The main gallery builder for SmugMug. SmugMug takes the following parameters:
	 *    - nick_name = The nickname of the user. This is mandatory for SmugMug.
	 *    - view = tree | albums | album | images. If left blank, a value of 'tree' is assumed.
	 *    - columns = The number of columns to show the output in
	 *    - album or album_key = The album slug, which is the AlbumKey. This is needed if view='album' or 'images'. Prior to version 1.57 album_id was required
	 *    - empty = true | false. If true, empty albums and categories are returned in the response, otherwise they are ignored.
	 *    - columns = The number of columns to return the output in. Optional.
	 *
	 * @param array $attr
	 * @param array $gallery_meta
	 * @return string
	 */
	public function get_gallery_images($attr = [], &$gallery_meta = []) {
		global $photonic_smug_title_caption, $photonic_smug_album_sort_order;
		global $photonic_smug_thumb_size, $photonic_smug_main_size, $photonic_smug_tile_size, $photonic_smug_video_size, $photonic_smug_media, $photonic_smug_default_user;
		$this->gallery_index++;
		$this->push_to_stack('Get Gallery Images');

		if (empty($this->api_key)) {
			$this->pop_from_stack();
			return $this->error(esc_html__('SmugMug API Key not defined.', 'photonic').Photonic::doc_link($this->doc_links['general']));
		}

		$attr = array_merge(
			$this->common_parameters,
			[
				'caption' => $photonic_smug_title_caption,
				'thumb_size' => $photonic_smug_thumb_size,
				'main_size' => $photonic_smug_main_size,
				'tile_size' => $photonic_smug_tile_size,
				'video_size' => $photonic_smug_video_size,

				'empty' => 'false',
				'view' => 'tree',
				'nick_name' => $photonic_smug_default_user,
				'start' => 1,
				'count' => 500,
				'media' => $photonic_smug_media,
				'album_sort_order' => $photonic_smug_album_sort_order,
			],
			$attr);

/*		if ($attr['main_size'] == 'custom') {
			if (!empty($attr['custom_main_size'])) {
				$attr['main_size'] = $attr['custom_main_size'];
			}
			else {
				$attr['main_size'] = 'Largest';
			}
		}
		if ($attr['thumb_size'] == 'custom') {
			if (!empty($attr['custom_thumb_size'])) {
				$attr['thumb_size'] = $attr['custom_thumb_size'];
			}
			else {
				$attr['thumb_size'] = 'Tiny';
			}
		}
		if ($attr['tile_size'] == 'custom' && !empty($attr['custom_tile_size'])) {
			if (!empty($attr['custom_tile_size'])) {
				$attr['tile_size'] = $attr['custom_tile_size'];
			}
			else {
				$attr['tile_size'] = 'same';
			}
		}*/

		$attr = array_map('trim', $attr);
		extract($attr);

		$args = [
			'APIKey' => $this->api_key,
			'_accept' => 'application/json'
		];

		$chained_calls = [];

		$args['_expandmethod'] = 'inline';
		$args['_verbosity'] = '1';

		if ($attr['view'] == 'tree' || $attr['view'] == 'albums') {
			if (empty($attr['nick_name'])) {
				$this->pop_from_stack();
				return $this->error(sprintf(esc_html__('The %1$s attribute is required for the %2$s and %3$s views.', 'photonic'), '<code>nick_name</code>', '<code>tree</code>', '<code>albums</code>'));
			}
		}

		switch ($attr['view']) {
			case 'albums':
				if (!empty($attr['site_password'])) {
					$chained_calls[] = $this->base_url.'user/'.$attr['nick_name'].'!unlock';
					$args['Password'] = $attr['site_password'];
				}

				$chained_calls[] = $this->base_url.'user/'.$attr['nick_name'].'!albums';
				$args['_expand'] = 'HighlightImage.ImageSizes';
				$args['Order'] = $attr['album_sort_order'];

				break;

			case 'album':
			case 'images':
				$album_field = '';
				$is_album = true;
				if (!empty($attr['album_key'])) {
					$album_field = $attr['album_key'];
				}
				else if (!empty($attr['album'])) {
					if (stripos($attr['album'], '_') === FALSE) {
						$album_field = $attr['album'];
					}
					else {
						$album_field = substr($attr['album'], stripos($attr['album'], '_') + 1);
					}
				}
				else {
					$is_album = false;
				}

				if (!empty($attr['password']) || !empty($attr['site_password'])) {
					$args['Password'] = !empty($attr['password']) ? $attr['password'] : $attr['site_password'];
					$chained_calls[] = $this->base_url.'album/'.$album_field.'!unlock';
				}

				if (!empty($attr['text'])) {
					$args['Text'] = $attr['text'];
				}
				if (!empty($attr['keywords'])) {
					$keywords = explode(',', $attr['keywords']);
					$keywords = json_encode($keywords);
					$args['Keywords'] = $keywords;
				}
				if (!empty($attr['sort_method'])) {
					$args['SortMethod'] = $attr['sort_method'];
				}
				if (!empty($attr['sort_order'])) {
					$args['SortDirection'] = $attr['sort_order'];
				}

				/*
					Weird issue: If _expand is called with a comma-separated list, and is passed in $args[], it doesn't expand everything.
					So, for comma-separated values, _expand is appended to the URL.
					However, if we do this, and OAuth is being used, the signature generation gets messed up, as the signature is generated with the "?_expand=...",
					which doesn't match the signature for when we make a call with $signed_args, because SmugMug internally parses out the URL and puts _expand=...
					somewhere in the middle. So, to fix, for authenticated calls we are separating out the _expand parameter and making two calls. Additionally we are
					moving the parameter from the URL to the array in the generate_signature method.

					Update, v1.59 - Using _expand and combining both the expansions prevents the usage of the <code>start</code> and <code>count</code>
					attributes from the album node. So, splitting the call anyway, to introduce the album!images node...
				*/
				if ($is_album) {
					$chained_calls[] = $this->base_url.'album/'.$album_field.'?_expand=HighlightImage.ImageSizes';
					if ($attr['view'] == 'images') {
						$args['Scope'] = 'https://api.smugmug.com/api/v2/album/'.$album_field;
					}
					else {
						$chained_calls[] = $this->base_url.'album/'.$album_field.'!images?_expand=ImageSizes';
					}
				}
				else if (!empty($attr['nick_name']) || !empty($attr['folder'])) {
					if (!empty($attr['folder'])) {
						$args['Scope'] = 'https://api.smugmug.com/api/v2/node/'.$attr['folder'];
					}
					else {
						$args['Scope'] = 'https://api.smugmug.com/api/v2/user/'.$attr['nick_name'];
					}
				}

				if (!empty($args['Scope'])) {
					// Only search if you have view='images'
					$chained_calls[] = 'https://api.smugmug.com/api/v2/image!search?_expand=ImageSizes';
				}

				break;

			case 'folder':
				if (!empty($attr['folder'])) {
					$this->prepare_chained_calls_for_folder($chained_calls, $attr['folder'], $attr['count']);
				}
				break;

			case 'tree':
			default:
				$this->push_to_stack('Initial user tree');
				$initial_call = $this->base_url.'user/'.$attr['nick_name'];
				$initial_response = Photonic::http($initial_call, 'GET', $args);

				if (!empty($attr['site_password'])) {
					$chained_calls[] = $this->base_url.'user/'.$attr['nick_name'].'!unlock';
					$args['Password'] = $attr['site_password'];
				}

				if (is_array($initial_response['response']) && isset($initial_response['response']['code']) && $initial_response['response']['code'] === 200) {
					$body = $initial_response['body'];
					$body = json_decode($body);
					$body = $body->Response;
					$node = $body->User->Uris->Node->Uri;
					$pieces = explode('/', $node);
					$node = array_pop($pieces);
					$this->prepare_chained_calls_for_folder($chained_calls, $node, $attr['count']);
				}
				$this->pop_from_stack();
			break;
		}

		if (!empty($attr['nick_name'])) {
			$args['NickName'] = $attr['nick_name'];
		}

		if (!empty($attr['start'])) {
			$args['start'] = $attr['start'];
		}

		if (!empty($attr['count'])) {
			$args['count'] = $attr['count'];
		}
		$attr['photo_count'] = empty($attr['photo_count']) ? $attr['count'] : $attr['photo_count'];

		$attr['overlay_size'] = empty($attr['overlay_size']) ? $attr['thumb_size'] : $attr['overlay_size'];
		$attr['overlay_video_size'] = empty($attr['overlay_video_size']) ? $attr['video_size'] : $attr['overlay_video_size'];

		$ret = '';
		$header_display = $this->get_header_display($attr);
		$attr['header_display'] = $header_display;

		$ret .= $this->make_chained_calls($chained_calls, $args, $attr, $gallery_meta);
		$this->pop_from_stack();
		return $ret.$this->get_stack_markup();
	}

	/**
	 * Runs a sequence of web-service calls to get information. Most often a single web-service call with the "Extras" parameter suffices for SmugMug.
	 * But there are some scenarios, e.g. clicking on an album to get a popup of all images in that album, where you need to chain the calls for the header.
	 *
	 * @param $chained_calls
	 * @param $smug_args
	 * @param $short_code
	 * @param array $gallery_meta
	 * @return string
	 */
	function make_chained_calls($chained_calls, $smug_args, $short_code, &$gallery_meta = []) {
		$this->push_to_stack('Make chained calls');
		$header_done = false;

		if (is_array($chained_calls) && count($chained_calls) > 0) {
			extract($short_code);

			$ret = '';

			$cookies = [];
			$insert = '';

			foreach ($chained_calls as $chained_call) {
				$this->push_to_stack("Chained call: $chained_call");
				if (stripos($chained_call, '!unlock') !== FALSE) {
					$response = Photonic::http($chained_call, 'POST', $smug_args);
					if (!is_wp_error($response)) {
						if (is_array($response['response']) && isset($response['response']['code']) && $response['response']['code'] === 200) {
							$cookies = $response['cookies'];
							foreach ($response['cookies'] as $cookie) {
								$cookies[$cookie->name] = $cookie->value;
							}
						}
					}
					else {
						$this->pop_from_stack(); // 'Chained call'
						$this->pop_from_stack(); // 'Make chained calls'
						return $this->wp_error_message($response);
					}
				}

				// The following is NOT an "else" because we are modifying the headers with the cookies, if required
				if (stripos($chained_call, '!unlock') === FALSE) {
					$this->push_to_stack('Making call');
					if ($this->oauth_done && empty($cookies)) {
						$signed_args = $this->sign_call($chained_call, 'GET', $smug_args);
						$response = Photonic::http($chained_call, 'GET', $signed_args, null, 90, false, [], $cookies);
					}
					else {
						$response = Photonic::http($chained_call, 'GET', $smug_args, null, 90, false, [], $cookies);
					}

					$this->pop_from_stack();

					if (!is_wp_error($response)) {
						$body = $response['body'];
						$body = json_decode($body);

						if (isset($body->Code) && $body->Code === 200) {
							$body = $body->Response;
							if (stripos($chained_call, '!albums') !== FALSE) {
								$albums = $body->Album;
								if (is_array($albums) && count($albums) > 0) {
									$filter = [];
									if (!empty($short_code['filter'])) {
										$filter = explode(',', $short_code['filter']);
									}
									$album_text = $this->process_albums($albums, '', $filter, $short_code, $body->Pages);
									if (!empty($album_text)) {
										$ret .= $this->finalize_markup($album_text, $short_code);
									}
								}
							}
							else if ((stripos($chained_call, '/album/') !== FALSE || (stripos($chained_call, '/image!search') !== FALSE && stripos($chained_call, urlencode('/api/v2/album/')) !== FALSE)) && stripos($chained_call, '!unlock') === FALSE) {
								$password_check_passed = true;
								if (isset($body->Album)) {
									$album = $body->Album;
									$password_check_passed = ($album->SecurityType == 'Password' && isset($album->Uris->AlbumImages)) || $album->SecurityType != 'Password';

									$header_object = [];
									$header_object['title'] = $album->Name;
									$header_object['link_url'] = $album->WebUri;
									$header_object['description'] = $album->Description;

									if (isset($album->Uris->HighlightImage->Image)) {
										$header_object['thumb_url'] = $album->Uris->HighlightImage->Image->ThumbnailUrl;
									}
									else if (isset($album->Uris->AlbumImages->AlbumImage) && is_array($album->Uris->AlbumImages->AlbumImage)) {
										$rand = rand(0, $album->ImageCount - 1);
										$header_object['thumb_url'] = $album->Uris->AlbumImages->AlbumImage[$rand]->ThumbnailUrl;
									}

									global $photonic_smug_disable_title_link, $photonic_smug_hide_album_thumbnail, $photonic_smug_hide_album_title, $photonic_smug_hide_album_photo_count;
									$hidden = [
										'thumbnail' => !empty($photonic_smug_hide_album_thumbnail),
										'title' => !empty($photonic_smug_hide_album_title),
										'counter' => !empty($photonic_smug_hide_album_photo_count),
									];
									$counters = ['photos' => !isset($album->ImageCount) ? 0 : $album->ImageCount];

									if (empty($display)) {
										$display = 'in-page';
									}

									if (!$header_done) {
										$insert = $this->process_object_header($header_object,
											[
												'type' => 'album',
												'hidden' => $this->get_hidden_headers($short_code['header_display'], $hidden),
												'counters' => $counters,
												'link' => empty($photonic_smug_disable_title_link),
												'display' => $display,
											],
											$gallery_meta
										);
										$header_done = true;
									}
								}
								if ($password_check_passed) {
									$ret .= $this->process_images($response, $short_code, $insert);
								}
								else {
									$ret .= $this->password_protected;
								}
							}
							else if (stripos($chained_call, '/user/') !== FALSE) {
								$root_node = $body->User->Uris->Node->Node;
								$ret .= $this->process_node($root_node, $short_code);
							}
							else if (stripos($chained_call, '/node/') !== FALSE) {
								$ret .= $this->process_node($body->Node, $short_code);
							}
							else if (stripos($chained_call, '/image!search') !== FALSE) {
								$ret .= $this->process_images($response, $short_code, $insert);
							}
						}
					}
					else {
						$this->pop_from_stack(); // 'Chained call'
						$this->pop_from_stack(); // 'Make chained calls'
						return $this->wp_error_message($response);
					}
				}
				$this->pop_from_stack();
			}
			$this->pop_from_stack();
			return $ret;
		}
		$this->pop_from_stack();
		return '';
	}

	function get_config_object($count = 500) {
		$config = [
			'expand' => [
				'HighlightImage' => [
					'expand' => [
						'ImageSizes' => []
					],
				],
				'NodeCoverImage' => [
					'expand' => [
						'ImageSizes' => []
					],
				],
				'ChildNodes' => [
					'args' => [
						'count' => $count
					],
					'expand' => [
						'HighlightImage' => [
							'expand' => [
								'ImageSizes' => []
							],
						],
						'NodeCoverImage' => [
							'expand' => [
								'ImageSizes' => []
							],
						],
						'ChildNodes' => [
							'args' => [
								'count' => $count
							],
							'expand' => [
								'HighlightImage' => [
									'expand' => [
										'ImageSizes' => []
									],
								],
								'NodeCoverImage' => [
									'expand' => [
										'ImageSizes' => []
									],
								],
								'ChildNodes' => [
									'args' => [
										'count' => $count
									],
									'expand' => [
										'HighlightImage' => [
											'expand' => [
												'ImageSizes' => []
											],
										],
										'NodeCoverImage' => [
											'expand' => [
												'ImageSizes' => []
											],
										],
										'ChildNodes' => [
											'args' => [
												'count' => $count
											],
											'expand' => [
												'HighlightImage' => [
													'expand' => [
														'ImageSizes' => []
													],
												],
												'NodeCoverImage' => [
													'expand' => [
														'ImageSizes' => []
													],
												],
												'ChildNodes' => [
													'args' => [
														'count' => $count
													],
												],
											],
										],
									],
								],
							],
						],
					],
				],
			],
		];
		return $config;
	}

	function prepare_chained_calls_for_folder(&$chained_calls, $folder_node, $count) {
		$config = $this->get_config_object($count);
		$config_str = json_encode($config);

		if (!empty($folder_node)) {
			$chained_calls[] = $this->base_url.'node/'.$folder_node.'?_config='.$config_str;
		}
	}

	function process_node($node, $short_code, $indent = '', $level = 0) {
		$start = $indent."<ul class='photonic-tree'>\n";
		$ret = $start;

		$albums = [];
		$folders = [];

		if (is_array($node)) {
			foreach($node as $child) {
				if ($child->Type == 'Album') {
					if (!in_array($child->NodeID.'-'.$this->gallery_index, $this->highlights_processed)) {
						$this->highlights_processed[] = $child->NodeID.'-'.$this->gallery_index;
						$albums[] = $child;
					}
				}
				else if ($child->Type == 'Folder') {
					$folders[] = $child;
				}
			}
		}
		else if ($node->Type == 'Folder') {
			if (isset($node->Uris->ChildNodes->Node)) {
				$child_nodes = $node->Uris->ChildNodes->Node;
				foreach ($child_nodes as $child) {
					if ($child->Type == 'Album'){
						if (!in_array($child->NodeID.'-'.$this->gallery_index, $this->highlights_processed)) {
							$this->highlights_processed[] = $child->NodeID.'-'.$this->gallery_index;
						}
						else {
							continue;
						}

						if (isset($child->Uris->Album->Album)) {
							$albums[] = $child->Uris->Album->Album; // Tree?
						}
						else {
							$albums[] = $child; // Node?
						}
					}
					else if ($child->Type == 'Folder') {
						$folders[] = $child;
					}
				}
			}
		}

		if (count($albums) > 0 || count($folders) > 0) {
			if (!is_array($node)) {
				$header_object = [
					'title' => $node->Name,
				];
				$hidden = ['thumbnail' => true, 'title' => false, 'counter' => false,];
				$insert = $this->process_object_header($header_object,
					[
						'type' => 'folder',
						'hidden' => $this->get_hidden_headers($short_code['header_display'], $hidden),
						'counters' => [],
						'link' => false,
						'display' => 'in-page',
					]
				);
			}
		}

		if (count($albums) > 0) {
			$album_text = $this->process_albums($albums, $indent . "\t\t", [], $short_code, null);
			if (!empty($album_text)) {
				$ret .= $indent."\t<li>\n";
				$ret .= empty($insert) ? '' : $insert;
				$ret .= $album_text;
				$ret .= $indent."\t</li>\n";
			}
		}

		if (count($folders) > 0) {
			$folder_tree = '';
			foreach ($folders as $folder) {
				$folder_tree .= $this->process_node($folder, $short_code, $indent."\t\t", $level + 1);
			}
			if (!empty($folder_tree)) {
				$ret .= $indent."\t<li>\n";
				$ret .= empty($insert) ? '' : $insert;
				$ret .= $folder_tree;
				$ret .= $indent."\t</li>\n";
			}
		}

		if ($ret != $start) {
			$ret .= $indent."</ul>\n";
		}
		else {
			// Nothing was found ...
			$ret = "";
		}
		return $ret;
	}

	/**
	 * Parse an array of album objects returned by the SmugMug API, then return an appropriate response.
	 *
	 * @param $albums
	 * @param string $indent
	 * @param array $album_filter
	 * @param $short_code
	 * @param $pages
	 * @return string
	 */
	function process_albums($albums, $indent, $album_filter, $short_code, $pages) {
		global $photonic_smug_albums_album_per_row_constraint, $photonic_smug_albums_album_constrain_by_count, $photonic_smug_albums_album_constrain_by_padding, $photonic_smug_albums_album_title_display, $photonic_smug_hide_albums_album_photos_count_display;
		$objects = $this->build_level_2_objects($albums, $short_code, $album_filter);
		$pagination = [];
		if (!empty($pages)) {
			$pagination['total'] = $pages->Total;
			$pagination['start'] = $pages->Start;
			$pagination['end'] = $pages->Start + $pages->Count - 1;
			$pagination['per-page'] = $pages->RequestedCount;
			$pagination['provider'] = 'smug';
		}

		$row_constraints = ['constraint-type' => $photonic_smug_albums_album_per_row_constraint, 'padding' => $photonic_smug_albums_album_constrain_by_padding, 'count' => $photonic_smug_albums_album_constrain_by_count];
		$ret = $this->layout_gallery($objects,
			[
				'row_constraints' => $row_constraints,
				'type' => 'albums',
				'singular_type' => 'album',
				'title_position' => $photonic_smug_albums_album_title_display,
				'level_1_count_display' => $photonic_smug_hide_albums_album_photos_count_display,
				'pagination' => $pagination,
				'indent' => $indent,
			],
			$short_code,
			2
		);
		return $ret;
	}

	/**
	 * Takes a response, then parses out the images from that response and returns a set of thumbnails for it. This method handles
	 * both, in-page images as well as images in a popup panel.
	 *
	 * @param $response
	 * @param array $short_code
	 * @param null $header
	 * @return string
	 */
	function process_images($response, $short_code, $header) {
		global $photonic_smug_photos_per_row_constraint, $photonic_smug_photos_constrain_by_count, $photonic_smug_photos_constrain_by_padding,
			   $photonic_smug_photos_pop_per_row_constraint, $photonic_smug_photos_pop_constrain_by_count,
			   $photonic_smug_photos_pop_constrain_by_padding, $photonic_smug_photo_title_display, $photonic_smug_photo_pop_title_display;
		$body = $response['body'];
		$body = json_decode($body);

		if ($body->Message == 'Ok') {
			$body = $body->Response;
			if (isset($body->Album)) {
				$album = $body->Album;
				$images = $album->Uris->AlbumImages;
			}
			else if (isset($body->Image)) {
				$images = $body->Image;
			}
			else {
				$images = $body;
			}

			$photo_objects = [];
			if (isset($images->AlbumImage)) {
				$photo_objects = $this->build_level_1_objects($images->AlbumImage, $short_code);
			}
			if (is_array($images)) {
				$photo_objects = $this->build_level_1_objects($images, $short_code);
			}
			$level_2_meta = [];
			$pages = isset($images->Pages) ? $images->Pages : (is_array($images) && isset($body->Pages) ? $body->Pages : null);
			if (isset($pages)) {
				$level_2_meta['total'] = $pages->Total;
				$level_2_meta['start'] = $pages->Start;
				$level_2_meta['end'] = $pages->Start + $pages->Count - 1;
				$level_2_meta['per-page'] = $pages->RequestedCount;
				$level_2_meta['provider'] = 'smug';
			}

			$ret = "";
			if (!empty($photo_objects)) {
				if (isset($short_code['display']) && $short_code['display'] == 'popup') {
					$ret .= $header;

					$row_constraints = ['constraint-type' => $photonic_smug_photos_pop_per_row_constraint, 'padding' => $photonic_smug_photos_pop_constrain_by_padding, 'count' => $photonic_smug_photos_pop_constrain_by_count];
					$ret .= $this->layout_gallery($photo_objects,
						[
							'title_position' => $photonic_smug_photo_pop_title_display,
							'row_constraints' => $row_constraints,
							'parent' => 'album',
							'level_2_meta' =>  $level_2_meta,
						],
						$short_code,
						1
					);
				}
				else {
					$ret .= $header;

					$row_constraints = ['constraint-type' => $photonic_smug_photos_per_row_constraint, 'padding' => $photonic_smug_photos_constrain_by_padding, 'count' => $photonic_smug_photos_constrain_by_count];
					$ret .= $this->layout_gallery($photo_objects,
						[
							'title_position' => $photonic_smug_photo_title_display,
							'row_constraints' => $row_constraints,
							'parent' => 'album',
							'level_2_meta' =>  $level_2_meta,
						],
						$short_code,
						1
					);
				}
				return $this->finalize_markup($ret, $short_code);
			}
		}
		return '';
	}

	function construct_custom_size($custom, $sizes) {
		if (isset($sizes->LargeImageUrl)) {
			$custom = str_replace('ImageUrl', '', $custom);
			$large = $sizes->LargeImageUrl;
			$split = explode('/', $large);
			$split[count($split) - 2] = $custom;
			$image_parts = explode('.', $split[count($split) -1]);
			$image_name_parts = explode('-', $image_parts[count($image_parts) - 2]);
			$image_name_parts[count($image_name_parts) - 1] = $custom;
			$image_parts[count($image_parts) - 2] = implode('-', $image_name_parts);
			$split[count($split) - 1] = implode('.', $image_parts);
			return implode('/', $split);
		}
		return '';
	}

	function build_level_1_objects($images, $short_code, $module_parameters = [], $options = []) {
		$thumb = "{$short_code['thumb_size']}ImageUrl";
		$main = "{$short_code['main_size']}ImageUrl";
		$tile = (empty($short_code['tile_size']) || $short_code['tile_size'] == 'same') ? $main : "{$short_code['tile_size']}ImageUrl";

		$media = explode(',', $short_code['media']);
		$videos_ok = in_array('videos', $media) || in_array('all', $media);
		$photos_ok = in_array('photos', $media) || in_array('all', $media);

		$photo_objects = [];
		if (is_array($images) && count($images) > 0) {
			foreach ($images as $image) {
				if ((empty($image->IsVideo) && !$photos_ok) || (!empty($image->IsVideo) && !$videos_ok)) {
					continue;
				}
				$image_sizes = $image->Uris->ImageSizes->ImageSizes;

				$photo_object = [];
				$photo_object['thumbnail'] = isset($image_sizes->{$thumb}) ? $image_sizes->{$thumb} : $this->construct_custom_size($thumb, $image_sizes);
				$photo_object['main_image'] = isset($image_sizes->{$main}) ? $image_sizes->{$main} : $this->construct_custom_size($main, $image_sizes);//$image_sizes->{$main};
				$photo_object['tile_image'] = isset($image_sizes->{$tile}) ? $image_sizes->{$tile} : $this->construct_custom_size($tile, $image_sizes);//$image_sizes->{$tile};
				$photo_object['title'] = $image->Title;
				$photo_object['alt_title'] = $image->Title;
				$photo_object['description'] = $image->Caption;
				if (isset($image->WebUri)) {
					$photo_object['main_page'] = $image->WebUri;
					$photo_object['buy_link'] = $image->WebUri.'/buy';
				}

				if (!empty($image->IsVideo)) {
					$photo_object['video'] = $image_sizes->{$short_code['video_size'].'VideoUrl'};
				}

				if (isset($image->ArchivedUri)) {
					$photo_object['download'] = $image->ArchivedUri;
				}
				$photo_object['id'] = $image->ImageKey;

				$photo_object['provider'] = $this->provider;

				$photo_objects[] = $photo_object;
			}
		}
		return $photo_objects;
	}

	function build_level_2_objects($albums, $short_code, $album_filter = []) {
		global $photonic_smug_hide_password_protected_thumbnail, $photonic_gallery_template_page;

		$named_albums = $album_filter;
		foreach ($named_albums as $idx => $album) {
			if (stripos($album, '_') !== FALSE) {
				$named_albums[$idx] = substr($album, stripos($album, '_') + 1);
			}
		}

		$objects = [];
		if (is_array($albums) && count($albums) > 0) {
			foreach ($albums as $album) {
				if (!empty($photonic_smug_hide_password_protected_thumbnail) && isset($album->SecurityType) && $album->SecurityType != 'None') {
					continue;
				}

				$main = "{$short_code['main_size']}ImageUrl";
				$tile = (empty($short_code['tile_size']) || $short_code['tile_size'] == 'same') ? $main : "{$short_code['tile_size']}ImageUrl";

				$highlight = $album->Uris->HighlightImage;
				if ((!isset($album->ImageCount) || $album->ImageCount != 0) /*&& isset($highlight->Image)*/) {
					if (isset($highlight->Image)) {
						$thumbURL = $highlight->Image->Uris->ImageSizes->ImageSizes->{$short_code['thumb_size'].'ImageUrl'};
						$tileURL = $highlight->Image->Uris->ImageSizes->ImageSizes->$tile;
					}
					else {
						if (in_array($short_code['thumb_size'], ['Small', 'Thumb', 'Tiny', 'Sm', 'Th', 'Ti'])) {
							$thumbURL = trailingslashit(PHOTONIC_URL).'include/images/placeholder-'.substr($short_code['thumb_size'],0,2).'.png';
						}
						else {
							$thumbURL = trailingslashit(PHOTONIC_URL).'include/images/placeholder.png';
						}
						$tileURL = trailingslashit(PHOTONIC_URL).'include/images/placeholder.png';
					}

					$object = [];
					if (isset($album->AlbumKey)) {
						$object['id_1'] = $album->AlbumKey;
					}
					else {
						$uri = $album->Uris->Album->Uri;
						$uri = explode('/', $uri);
						$object['id_1'] = $uri[count($uri)-1];
					}
					$object['thumbnail'] = $thumbURL;
					$object['tile_image'] = $tileURL;
					$object['main_page'] = $album->WebUri;

					$object['title'] = esc_attr($album->Name);
					$object['description'] = !empty($album->Description) ? esc_attr($album->Description) : '';

					if ($short_code['popup'] == 'page' && !empty($photonic_gallery_template_page) && is_string(get_post_status($photonic_gallery_template_page))) {
						$internal_short_code = $short_code;
						$internal_short_code['view'] = 'album';
						$internal_short_code['album'] = $object['id_1'];
						$internal_short_code['layout'] = empty($short_code['photo_layout']) ? $short_code['layout'] : $short_code['photo_layout'];

						$object['gallery_url'] = $this->get_gallery_url($internal_short_code, ['title' => $object['title']]);
					}

					if (isset($album->ImageCount)) {
						$object['counter'] = $album->ImageCount;
					}

					$object['data_attributes'] = [
						'photo-count' => $short_code['photo_count'],
						'photo-more' => empty($short_code['photo_more']) ? '' : $short_code['photo_more'],
						'overlay-size' => $short_code['overlay_size'],
						'overlay-video-size' => $short_code['overlay_video_size'],
					];

					if (isset($album->SecurityType) && $album->SecurityType == 'Password') {
						$object['classes'] = ['photonic-smug-passworded'];
						$object['passworded'] = 1;
					}

					if (empty($named_albums) || (!empty($named_albums) && in_array($album->AlbumKey, $named_albums) && strtolower($short_code['filter_type']) !== 'exclude') ||
						(!empty($named_albums) && !in_array($album->AlbumKey, $named_albums) && strtolower($short_code['filter_type']) === 'exclude')) {
						$objects[] = $object;
					}
				}
			}
		}
		return $objects;
	}

	/**
	 * Access Token URL
	 *
	 * @return string
	 */
	public function access_token_URL() {
		return 'http://api.smugmug.com/services/oauth/1.0a/getAccessToken';
	}

	/**
	 * Authenticate URL
	 *
	 * @return string
	 */
	public function authenticate_URL() {
		return 'http://api.smugmug.com/services/oauth/1.0a/authorize';
	}

	/**
	 * Authorize URL
	 *
	 * @return string
	 */
	public function authorize_URL() {
		return 'http://api.smugmug.com/services/oauth/1.0a/authorize';
	}

	/**
	 * Request Token URL
	 *
	 * @return string
	 */
	public function request_token_URL() {
		return 'http://api.smugmug.com/services/oauth/1.0a/getRequestToken';
	}

	/**
	 * Tests to see if the OAuth Access Token that is cached is still valid. This is important because a user might have manually revoked
	 * access for your app through the provider's control panel.
	 *
	 * @param $token
	 * @return array|WP_Error
	 */
	function check_access_token($token) {
		$signed_parameters = $this->sign_call($this->base_url.'user/sayontan', 'GET', []);
		$end_point = $this->base_url.'user/sayontan?'.Authenticator::build_query($signed_parameters);
		$response = Photonic::http($end_point, 'GET', null);

		return $response;
	}

	/**
	 * Takes the response for the "Check access token", then tries to determine whether the check was successful or not.
	 *
	 * @param $response
	 * @return bool
	 */
	public function is_access_token_valid($response) {
		if (is_wp_error($response)) {
			return false;
		}

		$response = $response['response'];
		if ($response['code'] == 200) {
			return true;
		}
		return false;
	}
}
