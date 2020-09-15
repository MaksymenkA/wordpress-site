<?php
namespace Photonic_Plugin\Lightboxes;

require_once('Lightbox.php');

class Fancybox2 extends Lightbox {
	protected function __construct() {
		$this->library = 'fancybox2';
		parent::__construct();
		$this->class = ['photonic-launch-gallery', 'launch-gallery-fancybox', 'fancybox'];
	}

	function get_photo_attributes($photo_data, $module) {
		if ($module->provider == 'google') {
			if (empty($photo_data['video'])) {
				return " data-fancybox='{type: \"image\"}' ";
			}
		}
		return !empty($photo_data['video']) ? ' data-html5-href="'.$photo_data['video'].'" ': '';
	}
}