<?php
namespace Photonic_Plugin\Layouts;

use Photonic_Plugin\Modules\Core;

interface Level_One_Gallery {
	/**
	 * Generates the HTML for the lowest level gallery, i.e. the photos. This is used for both, in-page and popup displays.
	 * The code for the random layouts is handled in JS, but just the HTML markers for it are provided here.
	 *
	 * @param $photos
	 * @param array $options
	 * @param $short_code
	 * @param $module Core
	 * @return string
	 */
	function generate_level_1_gallery($photos, $options, $short_code, $module);
}