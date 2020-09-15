<?php
namespace Photonic_Plugin\Modules;

interface Level_One_Module {
	function build_level_1_objects($response, $shortcode, $module_parameters = [], $options = []);
}
