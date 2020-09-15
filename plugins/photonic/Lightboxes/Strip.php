<?php
namespace Photonic_Plugin\Lightboxes;

use Photonic_Plugin\Modules\Core;

require_once('Lightbox.php');

class Strip extends Lightbox {
	function __construct() {
		$this->library = 'strip';
		parent::__construct();
	}

	/**
	 * @param $rel_id
	 * @param Core $module
	 * @return array
	 */
	function get_gallery_attributes($rel_id, $module) {
		global $photonic_lightbox_no_loop;
		$specific = [
			'data-strip-group' => ['lightbox-photonic-'.$module->provider.'-stream-'.(empty($rel_id) ? $module->gallery_index : $rel_id)]
		];
		if (!empty($photonic_lightbox_no_loop)) {
			$specific['data-strip-group-options'] = ["loop: false"];
		}

		return [
			'class' => $this->class,
			'rel' => ['lightbox-photonic-'.$module->provider.'-stream-'.(empty($rel_id) ? $module->gallery_index : $rel_id)],
			'specific' => $specific,
		];
	}

	function get_photo_attributes($photo_data, $module) {
		return ' data-strip-caption="'.$photo_data['title'].'" data-strip-options="onShow: function(a) { photonicStripSetHash('."'{$photo_data['deep']}'".'); }, afterHide: function() { photonicStripUnsetHash(); } " ';
	}
}