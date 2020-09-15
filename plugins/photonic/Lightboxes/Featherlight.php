<?php
namespace Photonic_Plugin\Lightboxes;

require_once('Lightbox.php');

class Featherlight extends Lightbox {
	protected function __construct() {
		$this->library = 'featherlight';
		parent::__construct();
		$this->class = ['photonic-launch-gallery', 'launch-gallery-featherlight'];
	}

	function get_photo_attributes($photo_data, $module) {
		$mime = (!empty($photo['mime']) ? $photo['mime']: 'video/mp4');
		if ($module->provider == 'google' && empty($photo_data['video'])) {
			return " data-featherlight-type='image' ";
		}
		else if ($module->provider == 'google' && !empty($photo_data['video'])) {
			return " data-featherlight='<video class=\"photonic\" controls preload=\"none\"><source src=\"".$photo_data['video']."\" type=\"".$mime."\">".esc_html__('Your browser does not support HTML5 videos.', 'photonic')."</video>' data-featherlight-type='html'";
		}

		return !empty($photo_data['video']) ? " data-featherlight='<video class=\"photonic\" controls preload=\"none\"><source src=\"".$photo_data['video']."\" type=\"".$mime."\">".esc_html__('Your browser does not support HTML5 videos.', 'photonic')."</video>' data-featherlight-type='video'" : '';
	}
}