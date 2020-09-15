<?php
namespace Photonic_Plugin\Lightboxes;

require_once('Lightbox.php');

class None extends Lightbox {
	function __construct() {
		$this->library = 'none';
		parent::__construct();
	}
}