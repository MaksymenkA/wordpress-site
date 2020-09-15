<?php
namespace Photonic_Plugin\Options;

abstract class Option_Tab {
	var $options;

	/**
	 * @return array
	 */
	public function get_options() {
		return $this->options;
	}

	function title_styles() {
		$ret = [
			"regular" => "<img src='".trailingslashit(PHOTONIC_URL).'include/images/title-regular.png'."' />Normal title display using the HTML \"title\" attribute",
			"below" => "<img src='".trailingslashit(PHOTONIC_URL).'include/images/title-below.png'."' />Below the thumbnail (Doesn't work for Random Justified Gallery and Mosaic Layout)",
			"tooltip" => "<img src='".trailingslashit(PHOTONIC_URL).'include/images/title-jq-tooltip.png'."' />Using a JavaScript tooltip",
			"hover-slideup-show" => "<img src='".trailingslashit(PHOTONIC_URL).'include/images/title-slideup.png'."' />Slide up from bottom upon hover",
			"slideup-stick" => "<img src='".trailingslashit(PHOTONIC_URL).'include/images/title-slideup.png'."' />Cover the lower portion always",
			'none' => 'No title'
		];
		return $ret;
	}

	function selection_range($min, $max) {
		$ret = [];
		for ($i = $min; $i <= $max; $i++) {
			$ret[$i] = $i;
		}
		return $ret;
	}
}