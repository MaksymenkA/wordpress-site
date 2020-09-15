<?php
namespace Photonic_Plugin\Lightboxes;

require_once('Lightbox.php');

class Lightgallery extends Lightbox {
	protected function __construct() {
		$this->library = 'lightgallery';
		parent::__construct();
	}

	function get_photo_attributes($photo_data, $module) {
		$download = !empty($photo_data['download']) ? 'data-download-url="'.$photo_data['download'].'" ' : '';
		$video = !empty($photo_data['video']) ? ' data-html="#photonic-video-'.$module->provider.'-'.$module->gallery_index.'-'.$photo_data['id'].'" ' : '';
		return ' data-sub-html="'.$photo_data['title'].'" '.$video.$download;
	}
}