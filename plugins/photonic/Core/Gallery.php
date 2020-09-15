<?php
namespace Photonic_Plugin\Core;

use Photonic_Plugin\Layouts\Core_Layout;
use Photonic_Plugin\Layouts\Grid;
use Photonic_Plugin\Layouts\Slideshow;
use Photonic_Plugin\Modules\Core;
use Photonic_Plugin\Modules\Flickr;
use Photonic_Plugin\Modules\Google_Photos;
use Photonic_Plugin\Modules\Instagram;
use Photonic_Plugin\Modules\Native;
use Photonic_Plugin\Modules\SmugMug;
use Photonic_Plugin\Modules\Zenfolio;

class Gallery {
	private $attr;
	/** @var Core */
	private $module;

	/** @var Core_Layout */
	private $layout;

	function __construct($attr) {
		$this->attr = $attr;
		$type = $this->attr['type'];

		$this->set_module($type);

		if ((!empty($attr['layout']) && in_array($type, ['flickr', 'smugmug', 'google', 'zenfolio', 'instagram']) && in_array($attr['layout'], ['strip-above', 'strip-below', 'strip-right', 'no-strip'])) ||
			(!empty($attr['style']) && $type == 'default' && in_array($attr['style'], ['default', 'strip-above', 'strip-below', 'strip-right', 'no-strip']))) {
			require_once(PHOTONIC_PATH.'/Layouts/Slideshow.php');
			$this->layout = Slideshow::get_instance();
		}
		else {
			require_once(PHOTONIC_PATH.'/Layouts/Grid.php');
			$this->layout = Grid::get_instance();
		}
	}

	private function set_module($type) {
		if ($type == 'flickr') {
			require_once(PHOTONIC_PATH."/Modules/Flickr.php");
			$this->module = Flickr::get_instance();
		}
		else if ($type == 'smugmug' || $type == 'smug') {
			require_once(PHOTONIC_PATH."/Modules/SmugMug.php");
			$this->module = SmugMug::get_instance();
		}
		else if ($type == 'google') {
			require_once(PHOTONIC_PATH."/Modules/Google_Photos.php");
			$this->module = Google_Photos::get_instance();
		}
		else if ($type == 'instagram') {
			require_once(PHOTONIC_PATH."/Modules/Instagram.php");
			$this->module = Instagram::get_instance();
		}
		else if ($type == 'zenfolio') {
			require_once(PHOTONIC_PATH."/Modules/Zenfolio.php");
			$this->module = Zenfolio::get_instance();
		}
		else {
			require_once(PHOTONIC_PATH."/Modules/Native.php");
			$this->module = Native::get_instance();
		}
	}

	function get_contents() {
		return $this->module->get_gallery_images($this->attr);
	}

	function get_helper_contents() {
		return $this->module->execute_helper($this->attr);
	}
}