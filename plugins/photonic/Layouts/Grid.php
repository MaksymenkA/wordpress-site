<?php
namespace Photonic_Plugin\Layouts;

use Photonic_Plugin\Layouts\Features\Can_Use_Lightbox;
use Photonic_Plugin\Modules\Core;

require_once('Level_One_Gallery.php');
require_once('Level_Two_Gallery.php');

require_once('Features/Can_Use_Lightbox.php');

class Grid extends Core_Layout implements Level_One_Gallery, Level_Two_Gallery {
	use Can_Use_Lightbox;

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
	function generate_level_1_gallery($photos, $options, $short_code, $module) {
		$module->push_to_stack('Generate level 1 gallery');

		$lightbox = self::get_lightbox();

		$layout = $short_code['layout'];
		$columns = !empty($short_code['columns']) && ($layout !== 'random' && $layout !== 'mosaic') ? $short_code['columns'] : 'auto';
		$display = !empty($short_code['display']) ? $short_code['display'] : 'in-page';
		$more = !empty($short_code['more']) ? esc_attr($short_code['more']) : '';
		$more = (empty($more) && !empty($short_code['photo_more'])) ? esc_attr($short_code['photo_more']) : $more;
		$panel = !empty($short_code['panel']) ? $short_code['panel'] : '';

		$title_position = empty($short_code['title_position']) ? $options['title_position'] : $short_code['title_position'];
		$row_constraints = isset($options['row_constraints']) && is_array($options['row_constraints']) ? $options['row_constraints'] : [];
		$sizes = isset($options['sizes']) && is_array($options['sizes']) ? $options['sizes'] : [];
		$type = !empty($options['type']) ? $options['type'] : 'photo';
		$parent = !empty($options['parent']) ? $options['parent'] : 'stream';
		$pagination = isset($options['level_2_meta']) && is_array($options['level_2_meta']) ? $options['level_2_meta'] : [];
		$indent = !isset($options['indent']) ? "\t" : $options['indent'];

		list($container_id, $container_end) = $this->get_container_details($short_code, $module);

		$non_standard = $layout == 'random' || $layout == 'masonry' || $layout == 'mosaic';

		$col_class = '';
		if (absint($columns)) {
			$col_class = 'photonic-gallery-'.$columns.'c';
		}

		if ($col_class == '' && $row_constraints['constraint-type'] == 'padding') {
			$col_class = 'photonic-pad-photos';
		}
		else if ($col_class == '') {
			$col_class = 'photonic-gallery-'.$row_constraints['count'].'c';
		}
		$col_class .= ' photonic-level-1 photonic-thumb photonic-thumb-'.$layout;

		$link_attributes = $lightbox->get_gallery_attributes($panel, $module);
		$link_attributes_text = $this->get_text_from_link_attributes($link_attributes);

		$effect = $this->get_thumbnail_effect($short_code, $layout, $title_position);
		$ul_class = "class='title-display-$title_position photonic-level-1-container ".($non_standard ? 'photonic-'.$layout.'-layout' : 'photonic-standard-layout')." photonic-thumbnail-effect-$effect'";
		if ($display == 'popup') {
			$ul_class = "class='slideshow-grid-panel lib-{$this->library} photonic-level-1-container title-display-$title_position'";
		}

		$ret = '';
		if (!$non_standard && $display != 'popup') {
			$container_tag = 'ul';
			$element_tag = 'li';
		}
		else {
			$container_tag = 'div';
			$element_tag = 'div';
		}

		list($pagination_data, $pagination, $columns_data) = $this->get_gallery_data_attributes($short_code, $module, $pagination, $columns);

		$start_with = "$indent<$container_tag $container_id $ul_class $pagination_data $columns_data>\n";
		$ret .= $start_with;

		global $photonic_external_links_in_new_tab;
		if (!empty($photonic_external_links_in_new_tab)) {
			$target = " target='_blank' ";
		}
		else {
			$target = '';
		}

		$counter = 0;
		$thumbnail_class = " class='$layout' ";

		$element_start = "$indent\t<".$element_tag.' class="photonic-'.$module->provider.'-image photonic-'.$module->provider.'-'.$type.' '.$col_class.'">'."\n";

		$default_lightbox_text = apply_filters('photonic_default_lightbox_text', esc_attr__('View', 'photonic'));
		foreach ($photos as $photo) {
			$counter++;

			$thumb = ($non_standard && $display == 'in-page') ? (isset($photo['tile_image']) ? $photo['tile_image'] : $photo['main_image']) : $photo['thumbnail'];
			$orig = empty($photo['video']) ? $photo['main_image'] : $photo['video'];
			$url = isset($photo['main_page']) ? $photo['main_page'] : '';
			$title = esc_attr($photo['title']);
			$description = esc_attr($photo['description']);
			$alt = esc_attr($photo['alt_title']);
			$orig = $this->library == 'none' ? $url : $orig;

			$title = empty($title) ? ((empty($alt) && $module->link_lightbox_title && $this->library != 'thickbox') ? $default_lightbox_text : $alt) : $title;
			$ret .= $element_start;

			$deep_value = 'gallery[photonic-'.$module->provider.'-'.$parent.'-'.(empty($panel) ? $module->gallery_index : $panel).']/'.(empty($photo['id']) ? $counter : $photo['id']).'/';
			$deep_link = ' data-photonic-deep="'.$deep_value.'" ';

			$buy = '';
			if (!empty($photo['buy_link']) && $module->show_buy_link) {
				$buy = ' data-photonic-buy="'.$photo['buy_link'].'" ';
			}

			$style = [];
			if (!empty($sizes['thumb-width'])) $style[] = 'width:'.$sizes['thumb-width'].'px';
			if (!empty($sizes['thumb-height'])) $style[] = 'height:'.$sizes['thumb-height'].'px';
			if (!empty($style)) $style = 'style="'.implode(';', $style).'"'; else $style = '';
			if ($module->link_lightbox_title && $this->library != 'thickbox' && !empty($url)) {
				$title_link_start = esc_attr("<a href='$url' $target>");
				$title_link_end = esc_attr("</a>");
			}
			else {
				$title_link_start = '';
				$title_link_end = '';
			}

			if (!empty($short_code['caption']) && ($short_code['caption'] == 'desc' || ($short_code['caption'] == 'title-desc' && empty($title)) || ($short_code['caption'] == 'desc-title' && !empty($description)))) {
				$title = $description;
			}
			else if (empty($short_code['caption']) || (($short_code['caption'] == 'desc-title' && empty($title)) || $short_code['caption'] == 'none')) {
				$title = '';
			}

			if (!empty($title)) {
				$title_markup = $title_link_start.esc_attr($title).$title_link_end;
				$title_markup = apply_filters('photonic_lightbox_title_markup', $title_markup);
			}
			else {
				$title_markup = '';
			}

			if ($module->link_lightbox_title && $this->library != 'thickbox' && !empty($photo['buy_link']) && $module->show_buy_link) {
				$buy_link = esc_attr('<a class="photonic-buy-link" href="'.$photo['buy_link'].'" target="_blank" title="'.__('Buy', 'photonic').'"><div class="icon-buy"></div></a>');
				$title_markup .= $buy_link;
			}

			$shown_title = '';
			if (in_array($title_position, ['below', 'hover-slideup-show', 'hover-slidedown-show', 'slideup-stick']) && !empty($title)) {
				$shown_title = '<div class="photonic-title-info"><div class="photonic-photo-title photonic-title">'.wp_specialchars_decode($title, ENT_QUOTES).'</div></div>';
			}

			$photo_data = ['title' => $title_markup, 'deep' => $deep_value, 'raw_title' => $title, 'href' => $orig];
			if (!empty($photo['download'])) {
				$photo_data['download'] = $photo['download'];
			}
			if (!empty($photo['video'])) {
				$photo_data['video'] = $photo['video'];
			}
			else {
				$photo_data['image'] = $photo['main_image'];
			}
			$photo_data['provider'] = $module->provider;
			$photo_data['gallery_index'] = $module->gallery_index;
			$photo_data['id'] = $photo['id'];

			$lb_specific_data = $lightbox->get_photo_attributes($photo_data, $module);
			if (!empty($photo['video'])) {
				$lb_specific_data .= ' data-photonic-media-type="video" ';
			}
			else {
				$lb_specific_data .= ' data-photonic-media-type="image" ';
			}
			if (!empty($photo['video']) && $this->library == 'lightgallery') {
				$video_id = $module->provider.'-'.$module->gallery_index.'-'.$photo['id'];
				$ret .= $indent."\t\t".'<div style="display:none;" id="photonic-video-'.$video_id.'">'."\n";
				$ret .= $indent."\t\t\t".'<video class="lg-video-object lg-html5 photonic" controls preload="none">'."\n";
				$ret .= $indent."\t\t\t\t".'<source src="'.$photo['video'].'" type="'.(!empty($photo['mime']) ? $photo['mime']: 'video/mp4').'">'."\n";
				$ret .=	$indent."\t\t\t\t".esc_html__('Your browser does not support HTML5 videos.', 'photonic')."\n";
				$ret .= $indent."\t\t\t".'</video>'."\n";
				$ret .= $indent."\t\t".'</div>'."\n";

				$orig = ''; // href should be blank
			}
			else if (!empty($photo['video']) && (in_array($this->library, ['colorbox', 'fancybox', 'fancybox2', 'magnific', 'photoswipe', 'swipebox',])
					|| (in_array($this->library, ['fancybox3']) && in_array($module->provider, ['flickr', 'google']))
				)) {
				$video_id = $module->provider.'-'.$module->gallery_index.'-'.$photo['id'];
				$ret .= $indent."\t\t".'<div class="photonic-html5-external" id="photonic-video-'.$video_id.'">'."\n";
				$ret .= $indent."\t\t\t".'<video class="photonic" controls preload="none">'."\n";
				$ret .= $indent."\t\t\t\t".'<source src="'.$photo['video'].'" type="'.(!empty($photo['mime']) ? $photo['mime']: 'video/mp4').'">'."\n";
				$ret .=	$indent."\t\t\t\t".esc_html__('Your browser does not support HTML5 videos.', 'photonic')."\n";
				$ret .= $indent."\t\t\t".'</video>'."\n";
				$ret .= $indent."\t\t".'</div>'."\n";

				$orig = '#photonic-video-'.$video_id;
			}

			if ($this->library == 'magnific') {
				$magnific = !empty($photo['video']) ? 'mfp-inline' : 'mfp-image';
				$link_attributes['class']['magnific'] = $magnific;
				$link_attributes_text = $this->get_text_from_link_attributes($link_attributes);
			}

			if ($title_position == 'tooltip') {
				$tooltip = 'data-photonic-tooltip="'.esc_attr($title).'" ';
			}
			else {
				$tooltip = '';
			}
			$ret .= $indent."\t\t".'<a '.$link_attributes_text.' href="'.$orig.'" title="'.($title_position != 'none' ? esc_attr($title) : '').'" data-title="'.$title_markup.'" '.$tooltip.' '.$lb_specific_data.' '.$target.$deep_link.$buy.">\n";
			$ret .= $indent."\t\t\t".'<img alt="'.$alt.'" src="'.$thumb.'" '.$style.$thumbnail_class." loading='eager'/>\n";
			$ret .= $indent."\t\t\t".$shown_title."\n";
			$ret .= $indent."\t\t"."</a>\n";
			$ret .= $indent."\t"."</$element_tag>\n";
		}

		$ret = trim($ret);
		if ($ret != $start_with) {
			$trailing = strlen($element_tag) + 3;
			if (substr($ret, -$trailing) != "</$element_tag>" && $short_code['popup'] == 'show' && !$non_standard) {
				$ret .= "\n$indent</$element_tag><!-- last $element_tag.photonic-pad-photos -->";
			}

			$ret .= "\n$indent</$container_tag> <!-- ./photonic-level-1-container -->\n";
			$ret .= "<span id='$container_end'></span>";

			if (!empty($pagination) && isset($pagination['end']) && isset($pagination['total']) && $pagination['total'] > $pagination['end']) {
				$ret .= !empty($more) ? "<a href='#' class='photonic-more-button photonic-more-dynamic'>$more</a>\n" : '';
			}
		}
		else {
			$ret = '';
		}

		$module->pop_from_stack();
		return $ret;
	}

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
	function generate_level_2_gallery($objects, $options, $short_code, $module) {
		$module->push_to_stack('Generate Level 2 Gallery');
		$row_constraints = isset($options['row_constraints']) && is_array($options['row_constraints']) ? $options['row_constraints'] : [];
		$type = $options['type'];
		$singular_type = $options['singular_type'];
		$title_position = empty($short_code['title_position']) ? $options['title_position'] : $short_code['title_position'];
		$level_1_count_display = $options['level_1_count_display'];
		$indent = !isset($options['indent']) ? '' : $options['indent'];
		$provider = $module->provider;

		$columns = $short_code['columns'];
		$layout = !isset($short_code['layout']) ? 'square' : $short_code['layout'];
		$popup = ' data-photonic-popup="'.$short_code['popup'].'"';

		$non_standard = $layout == 'random' || $layout == 'masonry' || $layout == 'mosaic';
		$effect = $this->get_thumbnail_effect($short_code, $layout, $title_position);
		$ul_class = "class='title-display-$title_position photonic-level-2-container ".($non_standard ? 'photonic-'.$layout.'-layout' : 'photonic-standard-layout')." photonic-thumbnail-effect-$effect'";

		list($container_id, $container_end) = $this->get_container_details($short_code, $module);

		$pagination = isset($options['pagination']) && is_array($options['pagination']) ? $options['pagination'] : [];
		$more = !empty($short_code['more']) ? esc_attr($short_code['more']) : '';

		list($pagination_data, $pagination, $columns_data) = $this->get_gallery_data_attributes($short_code, $module, $pagination, $columns);

		$ret = "\n$indent<ul $container_id $ul_class $pagination_data $columns_data>";
		if ($non_standard) {
			$ret = "\n$indent<div $container_id $ul_class $pagination_data $columns_data>";
		}

		if ($columns != 'auto') {
			$col_class = 'photonic-gallery-'.$columns.'c';
		}
		else if ($row_constraints['constraint-type'] == 'padding') {
			$col_class = 'photonic-pad-'.$type;
		}
		else {
			$col_class = 'photonic-gallery-'.$row_constraints['count'].'c';
		}

		$col_class .= ' photonic-level-2 photonic-thumb';

		$counter = 0;
		foreach ($objects as $object) {
			$data_attributes = isset($object['data_attributes']) && is_array($object['data_attributes']) ? $object['data_attributes'] : [];
			$data_attributes['provider'] = $provider;
			$data_attributes['singular'] = $singular_type;

			$data_array = [];
			foreach ($data_attributes as $attr => $value) {
				$data_array[] = 'data-photonic-'.$attr.'="'.$value.'"';
			}
			$data_array = implode(' ', $data_array);


			$id = empty($object['id_1']) ? '' : $object['id_1'].'-';
			$id = $id.$module->gallery_index;
			$id = empty($object['id_2']) ? $id : ($id.'-'.$object['id_2']);
			$title = esc_attr($object['title']);
			$image = "<img src='".(($non_standard && isset($object['tile_image'])) ? $object['tile_image'] : $object['thumbnail'])."' alt='".$title."' class='$layout' loading='eager'/>";
			$additional_classes = !empty($object['classes']) ? implode(' ', $object['classes']) : '';
			$realm_class = '';
			if (!empty($object['classes'])) {
				foreach ($object['classes'] as $class) {
					if (stripos($class, 'photonic-'.$provider.'-realm') !== FALSE) {
						$realm_class = $class;
					}
				}
			}
			if ($title_position == 'tooltip') {
				$tooltip = "data-photonic-tooltip='".esc_attr($title)."' ";
			}
			else {
				$tooltip = '';
			}

			if (empty($object['gallery_url'])) {
				$anchor = "\n{$indent}\t\t<a href='{$object['main_page']}' class='photonic-{$provider}-{$singular_type}-thumb photonic-level-2-thumb $additional_classes' id='photonic-{$provider}-$singular_type-thumb-$id' title='".($title_position != 'none' ? esc_attr($title) : '')."' data-title='".$title."' $tooltip $data_array$popup>\n$indent\t\t\t".$image;
			}
			else {
				$anchor = "\n{$indent}\t\t<a href='{$object['gallery_url']}' class='photonic-{$provider}-{$singular_type}-thumb photonic-level-2-thumb gallery-page $additional_classes' id='photonic-{$provider}-$singular_type-thumb-$id' title='".($title_position != 'none' ? esc_attr($title) : '')."' data-title='".$title."' $tooltip $data_array$popup>\n$indent\t\t\t".$image;
			}
			$text = '';
			if (in_array($title_position, ['below', 'hover-slideup-show', 'hover-slidedown-show', 'slideup-stick'])) {
				$text = "\n{$indent}\t\t\t<div class='photonic-title-info'>\n{$indent}\t\t\t\t<div class='photonic-$singular_type-title photonic-title'>".$title."";
				if (!$level_1_count_display && !empty($object['counter'])) {
					$text .= '<span class="photonic-title-photo-count photonic-'.$singular_type.'-photo-count">'.sprintf(esc_html__('%s photos', 'photonic'), $object['counter']).'</span>';
				}
			}
			if ($text != '') {
				$text .= "</div>\n{$indent}\t\t\t</div>";
			}

			$anchor .= $text."\n{$indent}\t\t</a>";
			$password_prompt = '';
			if (!empty($object['passworded'])) {
				$prompt_title = esc_attr__('Protected Content', 'photonic');
				$prompt_submit = esc_attr__('Access', 'photonic');
				$password_type = " type='password' ";
				$prompt_type = 'password';
				$prompt_text = esc_attr__('This album is password-protected. Please provide a valid password.', 'photonic');
				if (in_array("photonic-$provider-passworded-authkey", $object['classes'])) {
					$prompt_text = esc_attr__('This album is protected. Please provide a valid authorization key.', 'photonic');
				}
				else if (in_array("photonic-$provider-passworded-link", $object['classes'])) {
					$prompt_text = esc_attr__('This album is protected. Please provide the short-link for it.', 'photonic');
					$password_type = '';
					$prompt_type = 'link';
				}

				$password_prompt = "
							<div class='photonic-password-prompter $realm_class' id='photonic-{$provider}-$singular_type-prompter-$id' title='$prompt_title' data-photonic-prompt='$prompt_type'>
								<div class='photonic-password-prompter-content'>
									<div class='photonic-prompt-head'>
										<h3>
											<span class='title'>$prompt_title</span>
											<button class='close'>&times;</button>
										</h3>
									</div>
									<div class='photonic-prompt-body'>
										<p>$prompt_text</p>
										<input $password_type name='photonic-{$provider}-password' />
										<button class='photonic-{$provider}-submit photonic-password-submit confirm'>$prompt_submit</button>
									</div>
								</div>
							</div>";
			}

			if ($non_standard) {
				$ret .= "\n$indent\t<div class='photonic-{$provider}-image photonic-{$provider}-$singular_type-thumb $col_class' id='photonic-{$provider}-$singular_type-$id'>{$anchor}{$password_prompt}\n$indent\t</div>";
			}
			else {
				$ret .= "\n$indent\t<li class='photonic-{$provider}-image photonic-{$provider}-$singular_type-thumb $col_class' id='photonic-{$provider}-$singular_type-$id'>{$anchor}{$password_prompt}\n$indent\t</li>";
			}
			$counter++;
		}

		if ($ret != "\n$indent<ul $container_id $ul_class $pagination_data $columns_data>" && !$non_standard) {
			$ret .= "\n$indent</ul>\n";
		}
		else if ($non_standard) {
			$ret .= "\n$indent</div>\n";
		}
		else {
			$ret = '';
		}

		if (!empty($ret)) {
			$ret .= "<span id='$container_end'></span>";
			if (!empty($pagination) && isset($pagination['end']) && isset($pagination['total']) && $pagination['total'] > $pagination['end']) {
				$ret .= !empty($more) ? "<a href='#' class='photonic-more-button photonic-more-dynamic'>$more</a>\n" : '';
			}
		}

		$module->pop_from_stack();
		return $ret;
	}

	/**
	 * @param $short_code
	 * @param $module
	 * @return array
	 */
	private function get_container_details($short_code, $module) {
		if ($short_code['display'] != 'popup') {
			$container_id = "id='photonic-{$module->provider}-stream-{$module->gallery_index}-container'";
			$container_end = "photonic-{$module->provider}-stream-{$module->gallery_index}-container-end";
		} else {
			$container_id = "id='photonic-{$module->provider}-panel-" . $short_code['panel'] . "-container'";
			$container_end = "photonic-{$module->provider}-panel-{$short_code['panel']}-container-end";
		}
		return [$container_id, $container_end];
	}

	/**
	 * @param array $link_attributes
	 * @return string
	 */
	private function get_text_from_link_attributes($link_attributes) {
		$class = '';
		$rel = '';
		$specific = '';
		if (!empty($link_attributes['class'])) {
			$class = " class='".implode(' ', array_values($link_attributes['class']))."' ";
		}

		if (!empty($link_attributes['rel'])) {
			$rel = " rel='".implode(' ', $link_attributes['rel'])."' ";
		}

		if (!empty($link_attributes['specific'])) {
			foreach ($link_attributes['specific'] as $key => $val) {
				$specific .= $key.'="'.implode(' ', $val).'" ';
			}
		}
		return $class.$rel.$specific;
	}

	/**
	 * @param $short_code
	 * @param $module
	 * @param $pagination
	 * @param $columns
	 * @return array
	 */
	private function get_gallery_data_attributes($short_code, $module, $pagination, $columns) {
		$pagination_data = '';
		if (!empty($pagination)) {
			$pagination_data = [];
			// Should have total, start, end, per-page
			foreach ($pagination as $meta => $value) {
				$pagination_data[] = 'data-photonic-stream-' . $meta . '="' . $value . '"';
			}

			$pagination_data = implode(' ', $pagination_data);
			if (empty($pagination['provider'])) {
				$pagination_data .= ' data-photonic-stream-provider="' . $module->provider . '"';
			}
		}

		$to_be_glued = '';
		if (!empty($short_code)) {
			$to_be_glued = [];
			foreach ($short_code as $name => $value) {
				if (is_scalar($value)) {
					$to_be_glued[] = $name . '=' . $value;
				}
			}
			if (!empty($pagination['next-token'])) {
				$to_be_glued[] = 'next_token=' . $pagination['next-token'];
			}
			$to_be_glued = implode('&', $to_be_glued);
			$to_be_glued = esc_attr($to_be_glued);
		}

		$pagination_data .= ' data-photonic-stream-query="' . $to_be_glued . '"';
		$columns_data = ' data-photonic-gallery-columns="' . $columns . '"';
		return [$pagination_data, $pagination, $columns_data];
	}

	/**
	 * Returns the thumbnail effect that should be used for a gallery. Not all effects can be used by all types of layouts.
	 *
	 * @param $short_code
	 * @param $layout
	 * @param $title_position
	 * @return string
	 */
	private function get_thumbnail_effect($short_code, $layout, $title_position) {
		if (!empty($short_code['thumbnail_effect'])) {
			$effect = $short_code['thumbnail_effect'];
		}
		else {
			global $photonic_standard_thumbnail_effect, $photonic_justified_thumbnail_effect, $photonic_mosaic_thumbnail_effect, $photonic_masonry_thumbnail_effect;
			$effect = $layout == 'mosaic' ? $photonic_mosaic_thumbnail_effect :
				($layout == 'masonry' ? $photonic_masonry_thumbnail_effect :
					($layout == 'random' ? $photonic_justified_thumbnail_effect :
						$photonic_standard_thumbnail_effect));
		}

		if ($layout == 'circle' && $effect != 'opacity') { // "Zoom" doesn't work for circle
			$thumbnail_effect = 'none';
		}
		else if (($layout == 'square' || $layout == 'launch' || $layout == 'masonry') && $title_position == 'below') { // For these combinations, Zoom doesn't work
			$thumbnail_effect = 'none';
		}
		else {
			$thumbnail_effect = $effect;
		}
		return apply_filters('photonic_thumbnail_effect', $thumbnail_effect, $short_code, $layout, $title_position);
	}
}