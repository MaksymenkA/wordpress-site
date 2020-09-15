<?php
namespace Photonic_Plugin\Lightboxes;

use Photonic_Plugin\Modules\Core;

require_once('Lightbox.php');

class PrettyPhoto extends Lightbox {
	protected function __construct() {
		$this->library = 'prettyphoto';
		parent::__construct();
	}

	/**
	 * @param $rel_id
	 * @param Core $module
	 * @return array
	 */
	function get_gallery_attributes($rel_id, $module) {
		$rel = 'lightbox-photonic-'.$module->provider.'-stream-'.(empty($rel_id) ? $module->gallery_index : $rel_id);
		return [
			'class' => $this->class,
			'rel' => ["photonic-prettyPhoto[{$rel}]"],
			'specific' => [],
		];
	}
}