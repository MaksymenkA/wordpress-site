<?php
namespace Photonic_Plugin\Admin\Wizard;

use Photonic_Plugin\Core\Photonic;

/**
 * Class Wizard
 * This is the core module for displaying the gallery builder. This is used with two other files:
 *  - Screen_Flow.php: contains the top-level markup for the gallery builder
 *  - Screen_Fields.php: contains all the fields by screen
 *
 * @since 2.00
 */
class Wizard {
	var $flow_fields, $display_types, $shortcode_attributes, $aka_attributes, $date_parts, $date_part_hints, $check_for_blanks,
		$error_mandatory, $error_no_response, $error_not_found, $error_no_data_returned, $error_not_permitted, $error_authentication, $error_missing_api,
		$force_next_screen, $force_previous_screen, $is_gutenberg;

	function __construct() {
		require_once("Screen_Fields.php");
		$this->flow_fields = new Screen_Fields();

		$this->force_next_screen = -1;
		$this->force_previous_screen = -1;

		$this->error_mandatory = esc_html__('Please fill the mandatory fields. Mandatory fields are marked with a red "*".', 'photonic');
		$this->error_no_response = esc_html__('No response from server.', 'photonic');
		$this->error_not_found = esc_html__('Not found.', 'photonic');
		$this->error_no_data_returned = esc_html__('No data was returned for the user you provided. Please verify that the user has the content you are looking for.', 'photonic');
		$this->error_not_permitted = esc_html__('Incorrect value passed for "%1$s": %2$s', 'photonic');
		$this->error_missing_api = esc_html__('Please set up your %1$s and secret under %2$s', 'photonic');
		$this->error_authentication = esc_html__('Please set up your %1$s Authentication from %2$s', 'photonic');

		$this->date_parts = [0 => esc_html__('Year', 'photonic'), 1 => esc_html__('Month', 'photonic'), 2 => esc_html__('Date', 'photonic')];
		$this->date_part_hints = [0 => esc_html__('Year (0 - 9999)', 'photonic'), 1 => esc_html__('Month (0 - 12)', 'photonic'), 2 => esc_html__('Date (0 - 31)', 'photonic')];

		$this->display_types = [
			'single-photo' => 0,
			'multi-photo' => 1,
			'album-photo' => 1,
			'folder-photo' => 1,
			'user-photo' => 1,
			'gallery-photo' => 1,
			'collection-photo' => 1,
			'current-post' => 1,
			'another-post' => 1,
			'multi-album' => 2,
			'multi-gallery' => 2,
			'multi-collection' => 2,
			'multi-gallery-collection' => 2,
			'collection' => 3,
			'collections' => 3,
			'folder' => 3,
			'tree' => 3,
			'group' => 3,
			'group-hierarchy' => 3,
		];

		$this->shortcode_attributes = [
			'common' => [
				'columns', 'count', 'more', 'photo_count', 'photo_more', 'photo_layout', 'title_position', 'caption', 'media', 'main_size', 'thumb_size', 'tile_size', 'video_size', 'popup', 'thumbnail_effect', 'show_gallery', // All lightbox
				'speed', 'timeout', 'fx', 'pause', 'strip-style', 'controls', // Slideshow
			],
			'flickr' => ['user_id', 'group_id', 'collections_display', 'tags', 'tag_mode', 'text', 'sort', 'privacy_filter',],
			'smugmug' => ['nick_name', 'password', 'text', 'keywords', 'sort_order', 'sort_method', 'album_sort_order',],
			'google' => ['user_id', 'access', 'protection', 'crop_thumb', 'content_filters',],
			'zenfolio' => ['login_name', 'text', 'category_code', 'sort_order', 'structure', 'password'],
			'instagram' => ['embed_type', 'carousel_handling'],
			'wp' => [],
		];
		$this->aka_attributes = [
			'flickr' => ['per_page' => 'count'],
			'zenfolio' => ['limit' => 'count'],
			'wp' => ['slide_size' => 'main_size'],
		];
	}

	/**
	 * Gets the content to be displayed on a flow screen. This is invoked by an AJAX call. The current screen number is passed to this function.
	 * The function performs validations behind the scenes and if everything looks OK it returns the content for the next screen, otherwise
	 * an error is returned.
	 *
	 * @return string
	 */
	function get_screen() {
		$screen = isset($_POST['screen']) ? sanitize_text_field($_POST['screen']) : 0;
		$provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
		$display_type = isset($_POST['display_type']) ? sanitize_text_field($_POST['display_type']) : '';

		$raw_shortcode = !empty($_POST['photonic-editor-shortcode-raw']) ? sanitize_text_field($_POST['photonic-editor-shortcode-raw']) : '';
		if (!empty($raw_shortcode)) {
			$input = base64_decode($raw_shortcode);
			$input = json_decode($input);
			if (!empty($input->shortcode) && !empty($input->shortcode->attrs) && !empty($input->shortcode->attrs->named)) {
				$input = $input->shortcode->attrs->named;
			}
		}
		else {
			$raw_shortcode = !empty($_POST['photonic-editor-json']) ? stripslashes_deep($_POST['photonic-editor-json']) : '';
			$input = json_decode($raw_shortcode);
			if (!empty($_POST['photonic-gutenberg-active'])) {
				$this->is_gutenberg = true;
			}
		}

		$deconstructed = $this->deconstruct_shortcode($input);

		$output = $this->validate($screen, $provider, $deconstructed);
		if (!empty($output['error'])) {
			return '<div class="photonic-flow-error">' . $output['error'] . "</div>\n";
		}

		$screen = ((int)$screen) + 1;
		if ($this->force_next_screen > -1) {
			$screen = $this->force_next_screen;
		}

		$ret = '';

		if ($screen == 2 || $screen == '2') {
			/*
			 * The display_type screen. This gathers inputs about what the user wants to show: photos from an album, a collection of
			 * albums, user trees, single photos etc., along with who the photos are from - the default user, a different user, any user etc.
			 */
			$screen_fields = $this->flow_fields->get_screen_2_fields($provider);
			$fields = $screen_fields['display'];// $screen_fields[$provider]['display'];
			$ret .= $this->render_all_fields($fields, $deconstructed);
			$ret = (empty($screen_fields['header']) ? '' : "<h1>" . $screen_fields['header'] . "</h1>\n") .
				(empty($screen_fields['desc']) ? '' : "<p>" . $screen_fields['desc'] . "</p>\n") .
				$ret;
		}
		else if ($screen == 3) {
			/*
			 * The Gallery Builder screen, where all the photos / albums / folders are displayed
			 */
			$screen_fields = $this->flow_fields->get_screen_3_fields($provider);
			$fields = $screen_fields[$display_type]['display'];
			$ret .= $this->render_all_fields($fields, $deconstructed);

			$ret = (empty($screen_fields[$display_type]['header']) ? '' : "<h1>" . $screen_fields[$display_type]['header'] . "</h1>\n") .
				(empty($screen_fields[$display_type]['desc']) ? '' : "<p>" . $screen_fields[$display_type]['desc'] . "</p>\n") .
				str_replace('{{placeholder_value}}', $output['success'], $ret);
		}
		else if ($screen == 4) {
			/*
			 * The layout selection screen
			 */
			$ret .= $output['success'];
		}
		else {
			$ret .= $output['success'];
		}

		return $ret;
	}

	/**
	 * Checks all inputs amongst themselves. If the inputs are valid, then the content for the next screen is generated by this call.
	 * All heavy logic, including API calls and processing of the responses happens here.
	 *
	 * @param $screen
	 * @param $provider
	 * @param array $existing
	 * @return array|string
	 */
	function validate($screen, $provider, $existing = []) {
		if (empty($screen) || !is_numeric($screen) || (is_numeric($screen) && (intval($screen) <= 0 || intval($screen) > 5))) {
			return ['error' => sprintf(esc_html__('Invalid screen value: %s', 'photonic'), $screen)];
		}
		if (!in_array($provider, ['wp', 'flickr', 'picasa', 'google', 'smugmug', 'zenfolio', 'instagram'])) {
			return ['error' => sprintf(esc_html__('Invalid photo provider: %s', 'photonic'), $provider)];
		}

		$screen = intval($screen);
		$display_type = isset($_POST['display_type']) ? sanitize_text_field($_POST['display_type']) : '';

		if ($screen == 1) {
			switch ($provider) {
				case 'flickr':
					global $photonic_flickr_api_key, $photonic_flickr_api_secret;
					if (empty($photonic_flickr_api_key) || empty($photonic_flickr_api_secret)) {
						return ['error' => sprintf($this->error_missing_api, 'Flickr API key', '<em>Photonic &rarr; Settings &rarr; Flickr &rarr; Flickr Settings</em>')];
					}
					break;

				case 'picasa':
					return ['error' => esc_html__('Google has deprecated the Picasa API with effect from January 2019. Please consider using the Google Photos module.', 'photonic')];

				case 'google':
					global $photonic_google_client_id, $photonic_google_client_secret, $photonic_google_refresh_token;
//					if (!empty($photonic_google_use_own_keys) && (empty($photonic_google_client_id) || empty($photonic_google_client_secret))) {
					if (empty($photonic_google_client_id) || empty($photonic_google_client_secret)) {
						return ['error' => sprintf($this->error_missing_api, 'Google Client ID', '<em>Photonic &rarr; Settings &rarr; Google Photos &rarr; Google Photos Settings</em>')];
					}
					else if (empty($photonic_google_refresh_token)) {
						return ['error' => sprintf($this->error_authentication, 'Google Photos', '<em>Photonic &rarr; Authentication</em>')];
					}
					break;

				case 'smugmug':
				case 'zenfolio':
					return '';

				case 'instagram':
					global $photonic_instagram_access_token;
					if (empty($photonic_instagram_access_token)) {
						return ['error' => sprintf($this->error_authentication, 'Instagram', '<em>Photonic &rarr; Authentication</em>')];
					}
					break;

				default:    // wp
					return '';
			}
		}
		else if ($screen == 2) {
			$screen_fields = $this->flow_fields->get_screen_2_fields($provider);

			$fields = $screen_fields['display'];
			$flattened_fields = [];
			foreach ($fields as $id => $field) {
				if (!empty($field['type']) && $field['type'] != 'field_list') {
					$flattened_fields[$id] = $field;
				}
				else if (!empty($field['type']) && $field['type'] == 'field_list') {
					$flattened_fields = array_merge($field['list'], $flattened_fields);
				}
			}

			if (empty($display_type) || (empty($_POST['for']) && in_array($provider, ['flickr', 'smugmug', 'zenfolio']) && empty($existing))) {
				return ['error' => $this->error_mandatory];
			}

			if (empty($_POST['for']) && in_array($provider, ['flickr', 'smugmug', 'zenfolio']) && !empty($existing)) {
				return ['error' => esc_html__('While the "For whom?" setting may not be required for the shortcode to function in the front-end, it is required to edit this shortcode in this editor. Please specify a value.', 'photonic')];
			}

			if (!in_array($display_type, array_keys($flattened_fields['display_type']['options']))) {
				return ['error' => sprintf(esc_html__('Invalid display type: %s', 'photonic'), $display_type)];
			}

			$for = !empty($_POST['for']) ? sanitize_text_field($_POST['for']) : null;

			$gallery = $this->flow_fields->get_source($provider);
			$response = $gallery->make_request($display_type, $for, $flattened_fields);
			if (empty($response)) { // WP
				$this->force_next_screen = 4;
				$this->force_previous_screen = 2;
				return ['success' => $this->get_layout_selector($display_type, $existing)];
			}
			else if (!empty($response['error'])) { // Error occurred
				return $response;
			}
			else {
				list($https, $form_parameters, $url) = $response;
				return $this->process_response($https, $provider, $display_type, $form_parameters, $existing, $url);
			}
		}
		else if ($screen == 3) {
			//Check for display_type
			$screen_fields = $this->flow_fields->get_screen_3_fields($provider); // $this->field_list['screen-' . $screen];
			$provider_fields = $screen_fields; //$screen_fields[$provider];
			$fields = $provider_fields[$display_type]['display'];
			foreach ($fields as $id => $field) {
				$checks = $this->do_basic_option_check($id, $field, true);
				if (!empty($checks)) {
					return $checks;
				}

				if ($id == 'selection' && sanitize_text_field($_POST['selection']) == 'selected' && empty($_POST['selected_data'])) {
					return ['error' => esc_html__('Please select what you want to show.', 'photonic')];
				}

				if (in_array($display_type, ['single-photo', 'album-photo', 'gallery-photo', 'folder-photo', 'collection-photo']) && empty($_POST['selected_data'])) {
					return ['error' => esc_html__('Please select what you want to show.', 'photonic')];
				}
			}

			// All OK? Get next screen
			if ($display_type != 'single-photo') {
				$output = $this->get_layout_selector($display_type, $existing, $provider);
			}
			else {
				$this->force_next_screen = 6;
				$this->force_previous_screen = 3;
				$output = $this->construct_shortcode();
			}
			return ['success' => $output];
		}
		else if ($screen == 4) {
			$layout = isset($_POST['layout']) ? sanitize_text_field($_POST['layout']) : '';
			if (empty($layout)) {
				return ['error' => $this->error_mandatory];
			}
			$layout_options = $this->flow_fields->get_layout_options();
			if (!array_key_exists($layout, $layout_options)) {
				return ['error' => sprintf(esc_html__('Invalid layout: %s', 'photonic'), $layout)];
			}

			// All good. Next screen:
			return ['success' => $this->get_layout_options($provider, $display_type, $layout, $existing)];
		}
		else {
			$passworded = isset($_POST['selection_passworded']) ? sanitize_text_field($_POST['selection_passworded']) : '';
			$password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : '';
			if (!empty($passworded) && empty($password)) {
				return ['error' => $this->error_mandatory];
			}
			return ['success' => $this->construct_shortcode()];
		}
		return '';
	}

	/**
	 * Utility method that takes an array of fields, determines if each member is a "field list" or a "field". If it is a field
	 * list it processes it as a field list, otherwise it processes it as a field.
	 *
	 * @param array $fields
	 * @param array $existing
	 * @return String
	 */
	private function render_all_fields($fields, $existing = []) {
		$output = '';
		foreach ($fields as $id => $field) {
			if (!empty($field['type']) && $field['type'] == 'field_list') {
				$output .= $this->process_field_list($id, $field, $existing);
			}
			else if (!empty($field['type'])) {
				$output .= $this->process_field($id, $field, 0, null, $existing);
			}
		}
		return $output;
	}

	/**
	 * Takes a "field list" and processes each field in it individually. A "field list" can contain an interdependent sequence
	 * of fields, in which case each member gets a sequential number assigned to it. The actual logic of sequencing is handled on the
	 * front-end.
	 * E.g. If, for Flickr, you select "Multiple Photos", then "Another User", the front-end will show a "User" text field.
	 * But if "Group" is selected, it shows a "Group" text field
	 *
	 * @param $field_list_name
	 * @param array $field_list
	 * @param array $existing
	 * @return string
	 */
	function process_field_list($field_list_name, $field_list, $existing = []) {
		if (!is_array($field_list) || empty($field_list['type']) || $field_list['type'] != 'field_list' || empty($field_list['list'])) {
			return '';
		}
		else {
			$ret = '';
			$counter = 0;
			$sequence_group = null;
			foreach ($field_list['list'] as $id => $field) {
				if ($field_list['list_type'] == 'sequence') {
					$counter++;
					$sequence_group = $field_list_name;
				}
				$ret .= $this->process_field($id, $field, $counter, $sequence_group, $existing);
			}
			return $ret;
		}
	}

	/**
	 * Main code to render an input element on the flow-screen. Almost all types of inputs have switches for display here.
	 *
	 * @param string $id
	 * @param array $field
	 * @param $sequence
	 * @param null $sequence_group
	 * @param array $existing
	 * @return string
	 */
	function process_field($id, $field, $sequence, $sequence_group = null, $existing = []) {
		if (!is_array($field) || empty($field['type'])) {
			return '';
		}

		if (!empty($field['post_condition']) && is_array($field['post_condition'])) {
			foreach ($field['post_condition'] as $var => $permitted_values) {
				$pass = false;
				foreach ($permitted_values as $permitted) {
					if (isset($_POST[$var]) && $_POST[$var] === $permitted) {
						$pass = true;
						break;
					}
				}
				if (!$pass) {
					// Variable has not been set in a different screen. Hide this field.
					return '';
				}
			}
		}

		$req = empty($field['req']) ? '' : '<span class="photonic-required"><abbr title="'.esc_html__('Required', 'photonic').'">*</abbr></span>';
		$default = !empty($existing[$id]) ? $existing[$id] : (isset($field['std']) ? $field['std'] : '');
		$hint = '';
		$hint_in = '';
		if (!empty($field['hint'])) {
			$hint = "<div class='photonic-flow-hint' role='tooltip' id='{$id}-hint'>{$field['hint']}</div>\n";
			$hint_in = "aria-describedby='{$id}-hint'";
		}

		switch ($field['type']) {
			case 'text':
				$ret = "<label class='photonic-flow-option-name'>{$field['desc']}$req<input type='text' name='{$id}' value='" . $default . "' $hint_in/>$hint</label>";
				break;

			case 'radio':
				$ret = !empty($field['desc']) ? '<div class="photonic-flow-option-name">' . $field['desc'] . $req . '</div>' : '';
				foreach ($field['options'] as $option_value => $option_description) {
					$option_condition = (empty($field['option-conditions']) || empty($field['option-conditions'][$option_value])) ? '' :
						"data-photonic-option-condition='" . json_encode($field['option-conditions'][$option_value]) . "'";
					$checked = checked($default, $option_value, false);
					$ret .= "\t<div class='photonic-flow-field-radio'><label><input type='radio' name='{$id}' value='$option_value' $checked $option_condition/>" . $option_description . "</label></div>\n";
				}
				break;

			case 'select':
				$ret = "<label class='photonic-flow-option-name'>{$field['desc']}$req\n\t<select name='{$id}' $hint_in>\n";
				foreach ($field['options'] as $option_value => $option_description) {
					$option_condition = (empty($field['option-conditions']) || empty($field['option-conditions'][$option_value])) ? '' :
						"data-photonic-option-condition='" . json_encode($field['option-conditions'][$option_value]) . "'";
					$selected = selected($default, $option_value, false);
					$ret .= "\t\t<option value='$option_value' $selected $option_condition>" . esc_attr($option_description) . "</option>\n";
				}
				$ret .= "\t</select>\n$hint</label>\n";
				break;

			case 'image-select':
				$ret = '';
				$ret .= '<div class="photonic-flow-option-name">' . $field['desc'] . '</div>';
				if (!empty($default)) {
					$selection = !in_array($default, $field['options']) ? array_keys($field['options'])[0] : $default;
				}
				else {
					$selection = array_keys($field['options'])[0];
				}
				foreach ($field['options'] as $option_name => $desc) {
					$esc_desc = esc_attr($desc);
					$selected = ($option_name == $selection) ? 'selected' : '';
					$ret .= "<div class=\"photonic-flow-selector photonic-flow-$id-$option_name $selected\" title=\"$esc_desc\">\n\t<span class=\"photonic-flow-selector-inner photonic-$id\" data-photonic-selection-id=\"$option_name\">&nbsp;</span>\n\t<div class='photonic-flow-selector-info'>$desc</div>\n</div>\n";
				}
				if (!empty($ret)) {
					$ret = "<div class='photonic-flow-selector-container photonic-flow-$id' data-photonic-flow-selector-mode='single-no-plus' data-photonic-flow-selector-for=\"$id\">\n<input type=\"hidden\" id=\"$id\" name=\"$id\" value='$selection'/>\n$ret</div>\n";
				}
				break;

			case 'multi-select':
				$ret = '';
				$ret .= '<div class="photonic-flow-option-name">' . $field['desc'] . '</div>';
				$selection = explode(',', $default);
				foreach ($field['options'] as $option_value => $desc) {
					$checked = in_array($option_value, $selection) ? 'checked' : '';
					$ret .= "\t<label class='photonic-multi-select-item'><input type='checkbox' name='{$id}[]' value=\"$option_value\" $checked />$desc</label>\n";
				}
				if (!empty($ret)) {
					$ret = "<div class='photonic-flow-multi-select-container'>\n$ret</div>\n";
				}
				break;

			case 'date-filter':
				$ret = '';
				$ret .= '<div class="photonic-flow-option-name">' . $field['desc'] . '</div>';
				$dates = !empty($default) ? explode(',', $default) : [];
				$count = isset($field['count']) && is_numeric($field['count']) ? intval($field['count']) : 1;
				$ret .= "<ol data-photonic-date-filter='$id' data-photonic-filter-count='$count'>\n";
				$ctr = 0;
				foreach ($dates as $didx => $date) {
					$y_m_d = explode('/', $date);
					$ret .= "\t<li>\n";
					$ret .= "\t\t<div class='photonic-single-date'>\n";
					for ($pidx = 0; $pidx < 3; $pidx++) {
						$lower = strtolower($this->date_parts[$pidx]);
						$ret .= "\t\t\t<label class='photonic-date-filter'>\n" .
							"\t\t\t\t".substr($this->date_parts[$pidx], 0, 1)."<input type='text' class='photonic-date-$lower' name='{$id}_{$lower}[]' value=\"" . (isset($y_m_d[$pidx]) ? $y_m_d[$pidx] : '') . "\" aria-describedby='{$id}-{$didx}_{$lower}-hint'/>\n" .
							"\t\t\t\t<div class='photonic-flow-hint' role='tooltip' id='{$id}-{$didx}_{$lower}-hint'>" . $this->date_part_hints[$pidx] . "</div>\n" .
							"\t\t\t</label> \n";
					}
					$ret .= "\t\t</div>\n";
					$ret .= "\t\t<a href='#' class='photonic-remove-date-filter' title='Remove filter'><span class=\"dashicons dashicons-no\"> </span></a>\n";
					$ret .= "\t</li>\n";
					$ctr++;
					if ($ctr >= $count) {
						break;
					}
				}
				$ret .= "</ol>\n";
				$ret .= "<input type='hidden' name='$id' value='$default'/>\n";
				if ($ctr < $count) {
					$ret .= "<a href='#' class='photonic-add-date-filter' data-photonic-add-date='$id'><span class=\"dashicons dashicons-plus-alt\"> </span> Add filter</a>\n";
				}

				break;

			case 'date-range-filter':
				$ret = '';
				$ret .= '<div class="photonic-flow-option-name">' . $field['desc'] . '</div>';
				$date_ranges = !empty($default) ? explode(',', $default) : [];
				$count = isset($field['count']) && is_numeric($field['count']) ? intval($field['count']) : 1;
				$ret .= "<ol data-photonic-date-range-filter='$id' data-photonic-filter-count='$count'>\n";
				$ctr = 0;
				foreach ($date_ranges as $date_range) {
					$from_to = explode('-', $date_range);
					if (count($from_to) != 2) {
						continue;
					}
					$ret .= "\t<li>\n";
					foreach ($from_to as $didx => $date) {
						$ret .= "\t\t<div class='photonic-single-date'>\n";
						$y_m_d = explode('/', $date);
						$from_or_to = $didx === 0 ? 'start' : 'end';
						for ($pidx = 0; $pidx < 3; $pidx++) {
							$lower = strtolower($this->date_parts[$pidx]);
							$ret .= "\t\t\t<label class='photonic-date-filter'>\n" .
								"\t\t\t\t".substr($this->date_parts[$pidx], 0, 1)."<input type='text' class='photonic-date-$lower' name='{$id}_{$from_or_to}_{$lower}[]' value=\"" . (isset($y_m_d[$pidx]) ? $y_m_d[$pidx] : '') . "\" aria-describedby='{$id}-{$didx}_{$from_or_to}_{$lower}-hint'/>\n" .
								"\t\t\t\t<div class='photonic-flow-hint' role='tooltip' id='{$id}-{$didx}_{$from_or_to}_{$lower}-hint'>" . $this->date_part_hints[$pidx] . "</div>\n" .
								"\t\t\t</label> \n";
						}
						$ret .= "\t\t</div>\n";
					}
					$ret .= "\t\t<a href='#' class='photonic-remove-date-range-filter' title='Remove filter'><span class=\"dashicons dashicons-no\"> </span></a>\n";
					$ret .= "\t</li>\n";
					$ctr++;
					if ($ctr >= $count) {
						break;
					}
				}
				$ret .= "</ol>\n";
				$ret .= "<input type='hidden' name='$id' value='$default'/>\n";
				if ($ctr < $count) {
					$ret .= "<a href='#' class='photonic-add-date-range-filter' data-photonic-add-date-range='$id'><span class=\"dashicons dashicons-plus-alt\"> </span> Add filter</a>\n";
				}

				break;

			case 'thumbnail-selector':
				$ret = "<div class=\"photonic-flow-selector-container\" data-photonic-flow-selector-mode=\"{$field['mode']}\" data-photonic-flow-selector-for=\"{$field['for']}\">\n{{placeholder_value}}</div>\n";

				$controls = "<div class='thumb-controls'>\n";
				if ($field['mode'] != 'none') {
					$controls .= "<input type='text' class='search-thumbs' name='thumb-search' id='thumb-search'/>\n";
				}

				if ($field['mode'] == 'multi') {
					$controls .= esc_html__('Mark:', 'photonic').
						sprintf(esc_html__('%1$sAll%2$s', 'photonic'), "<a href='#' class='photonic-mark photonic-mark-all' data-photonic-mark-for='{$field['for']}'>", '</a>').'|'.
						sprintf(esc_html__('%1$sNone%2$s', 'photonic'), "<a href='#' class='photonic-mark photonic-mark-none' data-photonic-mark-for='{$field['for']}'>", '</a>');
				}
				$controls .= "</div>\n";
				$ret = $controls.$ret;
				break;

			default:
				return '';
		}

		if (!empty($ret)) {
			$sequence_str = '';
			if ($sequence !== 0) {
				$sequence_str = 'data-photonic-flow-sequence="' . $sequence . '"';
			}

			$sequence_group_str = '';
			if (!is_null($sequence_group)) {
				$sequence_group_str = 'data-photonic-flow-sequence-group="' . $sequence_group . '"';
			}

			$condition = '';
			if (!empty($field['conditions'])) {
				$condition = "data-photonic-condition='" . json_encode($field['conditions']) . "'";
			}

			$ret = "<div class='photonic-flow-field' $sequence_str $condition $sequence_group_str>\n" . $ret . "</div>\n";
		}
		return $ret;
	}

	/**
	 * Performs basic checks against whitelists. More advanced checks are handled in the <code>validate</code> function
	 *
	 * @param $id
	 * @param $field
	 * @param bool $check_required
	 * @return array|bool
	 */
	private function do_basic_option_check($id, $field, $check_required = false) {
		if (empty($field['type']) || ($field['type'] != 'select' && $field['type'] != 'radio')) {
			return false;
		}

		if ($check_required && !empty($field['req']) && (!isset($_POST[$id]) || trim($_POST[$id]) === '')) {
			return ['error' => $this->error_mandatory];
		}

		if (isset($_POST[$id]) && !in_array(sanitize_text_field($_POST[$id]), array_keys($field['options']))) {
			return ['error' => sprintf($this->error_not_permitted, $field['name'], $_POST[$id])];
		}
		return false;
	}

	/**
	 * A special type of selector not handled by the <code>process_field</code> call. Layouts are used only on one screen,
	 * and are used by almost all types of providers. This displays the available layouts as icons to pick from.
	 *
	 * @param $display_type
	 * @param array $existing
	 * @param null $provider
	 * @return string
	 */
	private function get_layout_selector($display_type, $existing = [], $provider = null) {
		global $photonic_thumbnail_style;
		$output = '';
		$level = empty($this->display_types[$display_type]) ? -1 : $this->display_types[$display_type];
//		$layout_from_option = empty($existing['layout']) ? (in_array($photonic_thumbnail_style, ['strip-below', 'strip-above', 'strip-right', 'no-strip']) ? 'slideshow' : $photonic_thumbnail_style) : $existing['layout'];
		if (empty($existing['layout'])) {
			if (in_array($photonic_thumbnail_style, ['strip-below', 'strip-above', 'strip-right', 'no-strip'])) {
				$layout_from_option = 'slideshow';
			}
			else if ($photonic_thumbnail_style == 'square' && !empty($provider) && $provider == 'instagram') {
				$layout_from_option = 'random';
			}
			else {
				$layout_from_option = $photonic_thumbnail_style;
			}
		}
		else {
			$layout_from_option = $existing['layout'];
		}

		if ($level > 0) {
			$layout_options = $this->flow_fields->get_layout_options();
			foreach ($layout_options as $layout => $desc) {
				$selected = $layout == $layout_from_option ? 'selected' : '';
				if (($layout == 'slideshow' && $level == 1) || $layout != 'slideshow') {
					$esc_desc = esc_attr($desc);
					$output .= "<div class=\"photonic-flow-selector photonic-flow-layout-$layout $selected\" title=\"$esc_desc\">\n\t<span class=\"photonic-flow-selector-inner photonic-layout\" data-photonic-selection-id=\"$layout\">&nbsp;</span>\n\t<div class='photonic-flow-selector-info'>$desc</div>\n</div>\n";
				}
			}
			if (!empty($output)) {
				$output = "<div class='photonic-flow-selector-container photonic-flow-layout' data-photonic-flow-selector-mode='single-no-plus' data-photonic-flow-selector-for=\"layout\">\n<input type=\"hidden\" id=\"layout\" name=\"layout\" value='$layout_from_option'/>\n$output</div>\n";
			}
		}
		$output = '<h1>' . esc_html__('Pick Your Layout', 'photonic') . '</h1>' .
			"<p>" . sprintf(esc_html__('You can configure the default settings from %s.', 'photonic'), '<strong>Photonic &rarr; Settings &rarr; Generic Options &rarr; Generic Settings &rarr; Layouts</strong>') . "</p>\n".
			$output;

		if ($this->force_next_screen > -1) {
			$output .= "\n<input type='hidden' name='force_next_screen' value='{$this->force_next_screen}'/>\n";
		}
		if ($this->force_previous_screen > -1) {
			$output .= "\n<input type='hidden' name='force_previous_screen' value='{$this->force_previous_screen}'/>\n";
		}

		return $output;
	}

	/**
	 * Layouts use similar constructs such as <code>count</code> and <code>more</code> but also have differences.
	 * E.g. <code>columns</code> are not applicable to the justified grid or mosaic layouts etc. Similarly size options
	 * vary from provider to provider. So in the Photonic_Screen_Fields we have a hierarchy for this screen, by level, provider and layout
	 *
	 * @param $provider
	 * @param $display_type
	 * @param $layout
	 * @param array $existing
	 * @return string
	 */
	private function get_layout_options($provider, $display_type, $layout, $existing = []) {
		// All levels, all layouts - media
		// L1, L2 All layouts - title position
		// L1 All layouts - count, more
		// L1, L2, L3 basic lightbox layouts - # of columns, constrain by etc., thumbnail size, full size
		// L3 Flickr - auto-expand
		$level = $this->display_types[$display_type];
		$output = '<h1>' . esc_html__('Configure Your Layout', 'photonic') . '</h1>';

		$extract = [];
		$screen_fields = $this->flow_fields->get_screen_5_fields($provider);

		if (!empty($screen_fields[$provider]['L' . $level])) {
			$extract = array_merge($screen_fields[$provider]['L' . $level], $extract);
		}

		if (!empty($screen_fields[$layout][$provider])) {
			$extract = array_merge($screen_fields[$layout][$provider], $extract);
		}

		if (!empty($screen_fields[$provider])) {
			$extract = array_merge($screen_fields[$provider], $extract);
		}

		if (!empty($screen_fields['L' . $level])) {
			$extract = array_merge($screen_fields['L' . $level], $extract);
		}

		if (!empty($screen_fields[$layout])) {
			$extract = array_merge($screen_fields[$layout], $extract);
		}

		$output .= $this->render_all_fields($extract, $existing);
		return $output;
	}

	/**
	 * Builds out the shortcode based on inputs from all previous screens. Some attributes are passed as they are, e.g. <code>count</code>.
	 * Others are used to determine other attributes, e.g. <code>display_type</code>
	 *
	 * @return string
	 */
	private function construct_shortcode() {
		global $photonic_alternative_shortcode;

		$provider = sanitize_text_field($_POST['provider']);
		$display_type = sanitize_text_field($_POST['display_type']);

		$short_code = [];
		if ($provider !== 'wp') {
			$short_code['type'] = $provider;
		}

		// Get specific attributes for a provider
		$gallery = $this->flow_fields->get_source($provider);
		$gallery_attr = $gallery->construct_shortcode_from_screen_selections($display_type);
		$short_code = array_merge($short_code, $gallery_attr);

		if (!empty($_POST['selection'])) {
			if ($_POST['selection'] != 'all') {
				$short_code['filter'] = sanitize_text_field($_POST['selected_data']);
			}
			if ($_POST['selection'] == 'not-selected') {
				$short_code['filter_type'] = 'exclude';
			}
		}

		if (isset($_POST['headers']) && trim($_POST['headers'] !== '')) {
			if (trim($_POST['headers']) == 'none') {
				$short_code['headers'] = '';
			}
			else {
				$short_code['headers'] = sanitize_text_field($_POST['headers']);
			}
		}

		$additional_attrs = array_merge(
			$this->shortcode_attributes[$provider],
			$this->shortcode_attributes['common']
		);
		foreach ($additional_attrs as $attr) {
			if (!empty($_POST[$attr]) && is_array($_POST[$attr])) {
				$short_code[$attr] = implode(',', $_POST[$attr]);
			}
			else if (!empty($_POST[$attr])) {
				$short_code[$attr] = $_POST[$attr];
			}
		}

		if (!empty($_POST['layout'])) {
			$key = $provider == 'wp' ? 'style' : 'layout';
			if ($_POST['layout'] != 'slideshow') {
				$short_code[$key] = sanitize_text_field($_POST['layout']);
			}
			else {
				$short_code[$key] = sanitize_text_field($_POST['slideshow-style']);
			}
		}
		// layout

		$raw_shortcode = !empty($_POST['photonic-editor-shortcode-raw']) ? sanitize_text_field($_POST['photonic-editor-shortcode-raw']) : '';
		if (!empty($raw_shortcode)) {
			$input = base64_decode($raw_shortcode);
			$input = json_decode($input);
			if (!empty($input->shortcode) && !empty($input->shortcode->attrs) && !empty($input->shortcode->attrs->named)) {
				$input = $input->shortcode->attrs->named;
			}
		}
		else {
			$raw_shortcode = !empty($_POST['photonic-editor-json']) ? stripslashes_deep($_POST['photonic-editor-json']) : '';
			$input = json_decode($raw_shortcode);
		}

		if (!empty($input)) {
			$attr_array = (array)$input;

			// If the type changes, regardless of everything else blank out the attributes
			if (!(empty($short_code['type']) && (empty($attr_array['type']) || in_array($attr_array['type'], ['wp', 'default']))) &&
				($attr_array['type'] != $short_code['type'])) {
				$attr_array = [];
			}
			else {
				foreach ($short_code as $key => $value) {
					unset($attr_array[$key]);
				}
			}

			if (!empty($this->aka_attributes[$provider])) {
				$aka = $this->aka_attributes[$provider];
				foreach ($aka as $key => $value) {
					if (isset($short_code[$key]) || isset($short_code[$value])) unset($attr_array[$key]);
				}
			}

			// Others ...
			if (!empty($short_code['type']) && $short_code['type'] == 'instagram') {
				if (!empty($short_code['media_id'])) {
					unset($attr_array['view']);
					unset($attr_array['media']);
				}
				else if (!empty($short_code['media'])) {
					unset($attr_array['media_id']);
				}
			}

			foreach ($attr_array as $key => $value) {
				if (in_array($key, ['more', 'photo_more', 'count', 'photo_count', 'photo_layout', 'show_gallery']) && empty($short_code[$key])) {
					unset($attr_array[$key]);
				}
			}
			$short_code = array_merge($short_code, $attr_array);
		}

		if (!$this->is_gutenberg) {
			$output = '<h1>' . esc_html__('Your shortcode', 'photonic') . '</h1>';
			$output .= "<code id='photonic_shortcode'>[" . (empty($photonic_alternative_shortcode) ? 'gallery' : $photonic_alternative_shortcode).' ';
			$shortcode_attrs = [];
			foreach ($short_code as $attr => $value) {
				$shortcode_attrs[] = $attr . "='" . esc_attr($value) . "'";
			}

			$output .= implode(' ', $shortcode_attrs);
			$output .= ']</code>';
			$output .= '<p>'.esc_html__('The above shortcode was generated based on your selections. You can either copy the above and paste it manually into your post, or click on the buttons below to insert it into or update your post', 'photonic').'</p>';
		}
		else {
			$output = '<h1>' . esc_html__('Your Gallery', 'photonic') . '</h1>';
			$output .= '<p>'.esc_html__('Based on your selections a gallery with the following attributes will be generated. Please click on the buttons below to insert it into or update your post:', 'photonic').'</p>';
			$shortcode_attrs = [];
			foreach ($short_code as $attr => $value) {
				$shortcode_attrs[] = '<code>'.$attr . ": " . esc_attr($value).'</code>';
			}
			$output .= implode("<br/>\n", $shortcode_attrs);

			$output .= '<p>'.esc_html__('The following is the corresponding shortcode for this block. If for some reason you are unable to create a Photonic block you can paste this into a Shortcode block:', 'photonic').'</p>';
			$output .= "<code>[" . (empty($photonic_alternative_shortcode) ? 'gallery' : $photonic_alternative_shortcode).' ';
			$shortcode_attrs = [];
			foreach ($short_code as $attr => $value) {
				$shortcode_attrs[] = $attr . "='" . esc_attr($value) . "'";
			}

			$output .= implode(' ', $shortcode_attrs);
			$output .= ']</code>';

			$output .= "<input id='photonic_shortcode' name='photonic_shortcode' type='hidden' value='".esc_attr(json_encode($short_code))."' />\n";
		}

		if ($this->force_next_screen > -1) {
			$output .= "\n<input type='hidden' name='force_next_screen' value='{$this->force_next_screen}'/>\n";
		}
		if ($this->force_previous_screen > -1) {
			$output .= "\n<input type='hidden' name='force_previous_screen' value='{$this->force_previous_screen}'/>\n";
		}
		return $output;
	}

	/**
	 * This is the inverse of the <code>construct_shortcode</code> method. If the Editor has a shortcode selected, this method
	 * splits it out into the relevant screens.
	 *
	 * @param $input
	 * @return array
	 */
	private function deconstruct_shortcode($input) {
		$deconstructed = [];
		if (!empty($input)) {
			if ((!empty($input->type) && in_array($input->type, ['wp', 'default', 'flickr', 'smugmug', 'picasa', 'google', 'zenfolio', 'instagram'])) ||
				((empty($input->type) && !empty($input->style)) && in_array($input->style, ['square', 'circle', 'random', 'masonry', 'mosaic', 'strip-above', 'strip-below', 'strip-right', 'no-strip']))
			) {
				$deconstructed['provider'] = !empty($input->type) ? $input->type : 'wp';

				$gallery = $this->flow_fields->get_source($deconstructed['provider']);
				$deconstructed_selections = $gallery->deconstruct_shortcode_to_screen_selections($input);
				$deconstructed = array_merge($deconstructed, $deconstructed_selections);

				if (!empty($input->filter)) {
					$deconstructed['selected_data'] = $input->filter;
					if (empty($input->filter_type) && in_array($input->filter_type, ['include', 'exclude'])) {
						$deconstructed['selection'] = $input->filter_type;
					}
					else {
						$deconstructed['selection'] = 'selected';
					}
				}
				else {
					$deconstructed['selection'] = 'all';
				}

				if (isset($this->aka_attributes[$deconstructed['provider']])) {
					$aka_attributes = $this->aka_attributes[$deconstructed['provider']];
					foreach ($aka_attributes as $attr => $aka) {
						if (isset($input->{$attr}) && !isset($deconstructed[$aka])) {
							$deconstructed[$aka] = sanitize_text_field($input->{$attr});
						}
					}
				}

				$layout = empty($input->layout) ? (empty($input->style) ? '' : $input->style) : $input->layout;
				if (!empty($layout)) {
					if (in_array($layout, ['square', 'circle', 'random', 'masonry', 'mosaic',])) {
						$deconstructed['layout'] = $layout;
					}
					else if (in_array($layout, ['strip-above', 'strip-below', 'strip-right', 'no-strip'])) {
						$deconstructed['layout'] = 'slideshow';
						$deconstructed['slideshow-style'] = $layout;
					}
				}

				$same_name_attrs = array_merge($this->shortcode_attributes['common'], $this->shortcode_attributes[$deconstructed['provider']]);
				foreach ($same_name_attrs as $attr) {
					if (isset($input->{$attr}) && !isset($deconstructed[$attr])) {
						$deconstructed[$attr] = $input->{$attr};
					}
				}
			}
		}
		return $deconstructed;
	}

	/**
	 * Displays an array of L1, L2 or L3 objects as a series of selectable thumbnails. Titles are deliberately not displayed because they mess
	 * with the layout if they are too long.
	 *
	 * @param array $objects
	 * @param bool $provider
	 * @param array $existing
	 * @param array $present
	 * @param bool $more
	 * @return string
	 */
	function get_thumbnail_display($objects, $provider = false, $existing = [], &$present = [], $more = false) {
		$output = '';
		$selected_data = empty($existing['selected_data']) ? [] : explode(',', $existing['selected_data']);
		foreach ($objects as $object) {
			if (!is_array($object)) {
				$output .= '<h4>'.$object."</h4>\n";
				continue;
			}
			if (!isset($object['id'])) {
				continue;
			}
			$selected = '';
			if (in_array($object['id'], $selected_data) || (!empty($object['alt_id']) && in_array($object['alt_id'], $selected_data))
				|| (!empty($object['alt_id2']) && in_array($object['alt_id2'], $selected_data))) {
				$selected = 'selected';
				$present[] = $object['id'];
			}
			$passworded = !empty($object['passworded']) ? 'passworded' : '';

			$title = !empty($object['title']) ? esc_attr($object['title']) : '';
			$counts = !empty($object['counters']) ? ' (' . esc_attr(implode(', ', $object['counters'])) . ')' : '';
			$alt = !empty($object['alt_id']) ? "data-photonic-selection-alt-id='{$object['alt_id']}'" : '';
			$alt_2 = !empty($object['alt_id2']) ? "data-photonic-selection-alt-id-2='{$object['alt_id2']}'" : '';

			$output .= "<div class='photonic-flow-selector $provider $selected $passworded'>\n";
			$output .= "\t<div class='photonic-flow-selector-inner' data-photonic-selection-id='{$object['id']}' $alt $alt_2>\n";
			$output .= "\t\t<img src='{$object['thumbnail']}' alt='$title$counts' title='$title$counts' />\n";
			$output .= "\t</div>\n";
			$output .= "</div>\n";
		}
		if ($more) {
			return $output;
		}

		$output .= "<input type='hidden' name='existing_selection' id='existing_selection' value='" . (!empty($present) ? implode(',', $present) : '') . "'/>\n";
		return $output;
	}

	/**
	 * A wrapper function that invokes the individual <code>process_response</code> calls by provider.
	 *  -   If the call is successful the results are passed as a thumbnail grid in an array with a "success" key
	 *  -   If the call is unsuccessful an error message is passed in an array with an "error" key
	 *
	 * @param $response
	 * @param $provider
	 * @param null $display_type
	 * @param array $form_parameters
	 * @param array $existing
	 * @param null $url
	 * @param bool $more
	 * @return array
	 */
	function process_response($response, $provider, $display_type = null, $form_parameters = [], $existing = [], $url = null, $more = false) {
		if (!is_wp_error($response)) {
			if (isset($response['response']) && isset($response['response']['code'])) {
				if ($response['response']['code'] == 200) {
					$pagination = [];
					$source = $this->flow_fields->get_source($provider);
					$objects = $source->process_response($response, $display_type, $url, $pagination);

					if (empty($objects)) {
						return ['error' => $this->error_no_data_returned];
					}
					else if (!empty($objects['error']) || !empty($objects['success'])) {
						// Happens for "Find user" kind of calls
						return $objects;
					}

					$present = [];
					$output = $this->get_thumbnail_display($objects, $provider, $existing, $present, $more);

					$selected_data = empty($existing['selected_data']) ? [] : explode(',', $existing['selected_data']);
					$missing = array_diff($selected_data, $present);
					if (!empty($missing)) {
						//
					}

					if (!empty($pagination['url'])) {
						$data_display_type = empty($display_type) ? '' : $display_type;
						$output .= "<div class='photonic-more-wrapper'>\n".
										"\t<a href='#' class='photonic-flow-more' data-photonic-more-link='{$pagination['url']}' data-photonic-display-type='$data_display_type' data-photonic-provider='$provider'>".esc_html__('Load More', 'photonic')."</a>\n".
									"</div>";
					}

					if (!$more) {
						foreach ($form_parameters as $id => $value) {
							$output .= "<input type='hidden' name='$id' value='" . esc_attr($value) . "' />\n";
						}
					}
					return ['success' => $output];
				}
				else {
					Photonic::log($response['response']);
					return ['error' => sprintf(esc_html__('No data returned. Error code %s', 'photonic'), $response['response']['code'])];
				}
			}
			else {
				Photonic::log($response);
				return ['error' => esc_html__('No data returned. Empty response, or empty error code.', 'photonic')];
			}
		}
		else {
			return ['error' => $response->get_error_message()];
		}
	}
}
