<?php
namespace Photonic_Plugin\Lightboxes;

require_once('Lightbox.php');

class Colorbox extends Lightbox {
	protected function __construct() {
		$this->library = 'colorbox';
		parent::__construct();
	}

	function get_photo_attributes($photo_data, $module) {
		return !empty($photo_data['video']) ? ' data-html5-href="'.$photo_data['video'].'" ': '';
	}
}