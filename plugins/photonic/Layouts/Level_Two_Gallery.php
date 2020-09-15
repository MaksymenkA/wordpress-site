<?php
namespace Photonic_Plugin\Layouts;

use Photonic_Plugin\Modules\Core;

interface Level_Two_Gallery {
	/**
	 * Generates the HTML for a group of level-2 items, i.e. Photosets (Albums) and Galleries for Flickr, Albums for Google Photos,
	 * Albums for SmugMug, and Photosets (Galleries and Collections) for Zenfolio. No concept of albums
	 * exists in native WP and Instagram.
	 *
	 * @param $objects
	 * @param $options
	 * @param $short_code
	 * @param $module Core
	 * @return string
	 */
	function generate_level_2_gallery($objects, $options, $short_code, $module);
}