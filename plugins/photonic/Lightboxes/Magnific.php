<?php
namespace Photonic_Plugin\Lightboxes;

require_once('Lightbox.php');

class Magnific extends Lightbox {
	protected function __construct() {
		$this->library = 'magnific';
		parent::__construct();
	}
}