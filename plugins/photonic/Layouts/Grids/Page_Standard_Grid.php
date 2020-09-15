<?php
namespace Photonic_Plugin\Layouts;

class Page_Standard_Grid {
	function get_level_one_container_start($module, $short_code, $options, $indent = '') {
		$layout = $short_code['layout'];
		return "$indent<ul id='photonic-{$module->provider}-stream-{$module->gallery_index}-container' class='title-display-$title_position photonic-level-1-container photonic-standard-layout photonic-thumbnail-effect-$effect' $pagination_data $columns_data>\n";
	}
}