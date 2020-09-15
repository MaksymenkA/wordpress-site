<?php
namespace Photonic_Plugin\Layouts;

use Photonic_Plugin\Modules\Core;

/**
 * Layout Manager to generate the grid layouts and the "Justified Grid" layout, all of which use the same markup. The Justified Grid layout is
 * modified by JS on the front-end, however the base markup for it is similar to the square and circular thumbnails layout.
 *
 * All other layout managers extend this, and might implement their own versions of generate_level_1_gallery and generate_level_2_gallery
 *
 */
abstract class Core_Layout {
	protected $library;

	protected function __construct() {
		global $photonic_slideshow_library, $photonic_custom_lightbox;
		if ($photonic_slideshow_library != 'custom') {
			$this->library = $photonic_slideshow_library;
		}
		else {
			$this->library = $photonic_custom_lightbox;
		}
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
	 * Generates the markup for a single photo.
	 *
	 * @param $data array Pertinent pieces of information about the photo - the source (src), the photo page (href), title and caption
	 * @param $module Core The object calling this. A CSS class is created in the header, photonic-single-<code>$module->provider</code>-photo-header
	 * @return string
	 */
	function generate_single_photo_markup($data, $module) {
		$module->push_to_stack('Generate single photo markup');
		$ret = '';
		$photo = array_merge(
			['src' => '', 'href' => '', 'title' => '', 'caption' => ''],
			$data
		);

		if (empty($photo['src'])) {
			$module->pop_from_stack();
			return $ret;
		}

		global $photonic_external_links_in_new_tab;
		if (!empty($photo['title'])) {
			$ret .= "\t".'<h3 class="photonic-single-photo-header photonic-single-'.$module->provider.'-photo-header">'.$photo['title']."</h3>\n";
		}

		$img = '<img src="'.$photo['src'].'" alt="'.esc_attr(empty($photo['caption']) ? $photo['title'] : $photo['caption']).'" loading="eager"/>';
		if (!empty($photo['href'])) {
			$img = '<a href="'.esc_url($photo['href']).'" title="'.esc_attr(empty($photo['caption']) ? $photo['title'] : $photo['caption']).'" '.
				(!empty($photonic_external_links_in_new_tab) ? ' target="_blank" ' : '').'>'.$img.'</a>';
		}

		if (!empty($photo['caption'])) {
			$ret .= "\t".'<div class="wp-caption">'."\n\t\t".$img."\n\t\t".'<div class="wp-caption-text">'.$photo['caption']."</div>\n\t</div><!-- .wp-caption -->\n";
		}
		else {
			$ret .= $img;
		}

		$module->pop_from_stack();
		return $ret;
	}
}
