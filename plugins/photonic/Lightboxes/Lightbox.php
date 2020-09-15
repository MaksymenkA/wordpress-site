<?php
namespace Photonic_Plugin\Lightboxes;

use Photonic_Plugin\Modules\Core;

abstract class Lightbox {
	/** @var array */
	var $class;

	var $supports_video;

	var $library;

	/**
	 * Lightbox constructor.
	 */
	protected function __construct() {
		require_once(PHOTONIC_PATH.'/Modules/Core.php');
		$this->class = ['photonic-launch-gallery', 'launch-gallery-'.$this->library, $this->library];
	}

	final public static function get_instance() {
		static $instances = array();
		$called_class = get_called_class();

		if (!isset($instances[$called_class])) {
			$instances[$called_class] = new $called_class();
		}
		return $instances[$called_class];
	}

	/**
	 * @param $rel_id
	 * @param Core $module
	 * @return array
	 */
	function get_gallery_attributes($rel_id, $module) {
		return [
			'class' => $this->class,
			'rel' => ['lightbox-photonic-'.$module->provider.'-stream-'.(empty($rel_id) ? $module->gallery_index : $rel_id)],
			'specific' => [],
		];
	}

	/**
	 * Some lightboxes require some additional attributes for individual photos. E.g. Lightgallery requires something to show the title etc.
	 * This method returns such additional information. Not to be confused with <code>get_lightbox_attributes</code>, which
	 * returns information for the gallery as a whole.
	 *
	 * @param $photo_data
	 * @param Core $module
	 * @return string
	 */
	function get_photo_attributes($photo_data, $module) {
		return '';
	}
}