<?php
namespace Photonic_Plugin\Lightboxes;

use Photonic_Plugin\Modules\Core;

require_once('Lightbox.php');

class Fancybox3 extends Lightbox {
	protected function __construct() {
		$this->library = 'fancybox3';
		parent::__construct();
	}

	/**
	 * @param $rel_id
	 * @param Core $module
	 * @return array
	 */
	function get_gallery_attributes($rel_id, $module) {
		return [
			'class' => $this->class = ['photonic-launch-gallery', 'launch-gallery-fancybox', 'fancybox'],
			'rel' => ['lightbox-photonic-'.$module->provider.'-stream-'.(empty($rel_id) ? $module->gallery_index : $rel_id)],
			'specific' => [
				'data-fancybox' => ['lightbox-photonic-'.$module->provider.'-stream-'.(empty($rel_id) ? $module->gallery_index : $rel_id)]
			],
		];
	}

	function get_photo_attributes($photo_data, $module) {
		if (in_array($module->provider, ['google', 'flickr'])) {
			return !empty($photo_data['video']) ? ' data-html5-href="'.$photo_data['video'].'" ': '';
		}
		return '';
	}
}