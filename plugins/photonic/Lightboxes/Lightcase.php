<?php
namespace Photonic_Plugin\Lightboxes;

use Photonic_Plugin\Modules\Core;

require_once('Lightbox.php');

class Lightcase extends Lightbox {
	protected function __construct() {
		$this->library = 'lightcase';
		parent::__construct();
	}

	/**
	 * @param $rel_id
	 * @param Core $module
	 * @return array
	 */
	function get_gallery_attributes($rel_id, $module) {
		global $photonic_slideshow_mode;
		return [
			'class' => $this->class,
			'rel' => ['lightbox-photonic-'.$module->provider.'-stream-'.(empty($rel_id) ? $module->gallery_index : $rel_id)],
			'specific' => [
				'data-rel' => ['lightcase:lightbox-photonic-'.$module->provider.'-stream-'.(empty($rel_id) ? $module->gallery_index : $rel_id).((isset($photonic_slideshow_mode) && $photonic_slideshow_mode == 'on') ? ':slideshow' : '')]
			],
		];
	}

	function get_photo_attributes($photo_data, $module) {
		if ($module->provider == 'google') {
			if (empty($photo_data['video'])) {
				return " data-lc-options='{\"type\": \"image\"}' ";
			}
			else {
				return " data-lc-options='{\"type\": \"video\"}' ";
			}
		}
		else if ($module->provider == 'flickr') {
			return !empty($photo_data['video']) ? ' data-html5-href="'.$photo_data['video'].'" ': '';
		}
		return '';
	}
}