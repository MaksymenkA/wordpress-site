<?php
namespace Photonic_Plugin\Core;

use Photonic_Plugin\Admin\Admin_Menu;
use Photonic_Plugin\Admin\Authentication;
use Photonic_Plugin\Admin\Helper;
use Photonic_Plugin\Options\Defaults;
use Photonic_Plugin\Options\Options;
use WP_Error;

class Photonic {
	var $defaults, $localized, $provider_map, $admin_menu;
	static $library;

	function __construct() {
//		$start = microtime(true);
		global $photonic_options, $photonic_is_IE;

		//WP provides a global $is_IE, but we specifically need to find IE6x (or, heaven forbid, IE5x). Note that older versions of Opera used to identify themselves as IE6, so we exclude Opera.
		$photonic_is_IE = preg_match('/MSIE [56789]/i', $_SERVER['HTTP_USER_AGENT']);

		require_once(PHOTONIC_PATH."/Options/Options.php");
		add_action('admin_init', [Options::get_instance(), 'prepare_options'], 20); // Setting to 20 so that CPTs can be picked up - Utilities are loaded with a priority 10

		$this->localized = false;
		$this->provider_map = [
			'flickr' => 'Flickr',
			'smug' => 'SmugMug',
			'smugmug' => 'SmugMug',
			'google' => 'Google',
			'zenfolio' => 'Zenfolio',
			'instagram' => 'Instagram',
		];

		add_action('admin_menu', [&$this, 'add_admin_menu']);
		add_action('admin_init', [&$this, 'admin_init']);

		$photonic_options = get_option('photonic_options');
		$set_options = isset($photonic_options) && is_array($photonic_options) ? $photonic_options : [];

		$defaults = Defaults::get_options();
		$all_options = array_merge($defaults, $set_options);

		foreach ($all_options as $key => $value) {
			$mod_key = 'photonic_'.$key;
			global ${$mod_key};
			${$mod_key} = $value;
		}

		define('PHOTONIC_SSL_VERIFY', empty($photonic_ssl_verify_off));
		define('PHOTONIC_DEBUG', !empty($photonic_debug_on));

		if (!empty($photonic_script_dev_mode)) {
			define('PHOTONIC_DEV_MODE', '');
		}
		else {
			define('PHOTONIC_DEV_MODE', '.min');
		}

		if (!empty($photonic_curl_timeout) && is_numeric($photonic_curl_timeout)) {
			define('PHOTONIC_CURL_TIMEOUT', $photonic_curl_timeout);
		}
		else {
			define('PHOTONIC_CURL_TIMEOUT', 10);
		}

		global $photonic_slideshow_library, $photonic_custom_lightbox;
		if ($photonic_slideshow_library != 'custom') {
			self::$library = $photonic_slideshow_library;
		}
		else {
			self::$library = $photonic_custom_lightbox;
		}

		// Gallery
		if (!empty($photonic_alternative_shortcode)) {
			add_shortcode($photonic_alternative_shortcode, [&$this, 'modify_gallery']);
			add_filter('shortcode_atts_'.$photonic_alternative_shortcode, [&$this, 'native_gallery_attributes'], 10, 3);
		}
		else {
			add_filter('post_gallery', [&$this, 'modify_gallery'], 20, 2);
			add_filter('shortcode_atts_gallery', [&$this, 'native_gallery_attributes'], 10, 3);
		}

		add_shortcode('photonic_helper', [&$this, 'helper_shortcode']);

		add_action('wp_enqueue_scripts', [&$this, 'always_add_styles'], 20);
		if (!empty($photonic_always_load_scripts)) {
			add_action('wp_enqueue_scripts', [&$this, 'conditionally_add_scripts'], 20);
		}
		add_action('wp_head', [&$this, 'print_scripts'], 20);

		add_action('wp_ajax_photonic_display_level_2_contents', [&$this, 'display_level_2_contents']);
		add_action('wp_ajax_nopriv_photonic_display_level_2_contents', [&$this, 'display_level_2_contents']);

		add_action('wp_ajax_photonic_display_level_3_contents', [&$this, 'display_level_3_contents']);
		add_action('wp_ajax_nopriv_photonic_display_level_3_contents', [&$this, 'display_level_3_contents']);

		add_action('wp_ajax_photonic_load_more', [&$this, 'load_more']);
		add_action('wp_ajax_nopriv_photonic_load_more', [&$this, 'load_more']);

		add_action('wp_ajax_photonic_lazy_load', [&$this, 'lazy_load']);
		add_action('wp_ajax_nopriv_photonic_lazy_load', [&$this, 'lazy_load']);

		add_action('wp_ajax_photonic_helper_shortcode_more', [&$this, 'helper_shortcode_more']);
		add_action('wp_ajax_nopriv_photonic_helper_shortcode_more', [&$this, 'helper_shortcode_more']);

		add_action('wp_ajax_photonic_invoke_helper', [&$this, 'invoke_helper']);
		add_action('wp_ajax_photonic_obtain_token', [&$this, 'obtain_token']);
		add_action('wp_ajax_photonic_save_token', [&$this, 'save_token_in_options']);
		add_action('wp_ajax_photonic_delete_token', [&$this, 'delete_token_from_options']);

		$this->add_extensions();
		$this->add_gutenberg_support();

		add_action('wp_ajax_photonic_dismiss_warning', [&$this, 'dismiss_warning']);

		add_action('http_api_curl', [&$this, 'curl_timeout'], 100, 1);

		add_action('plugins_loaded', [&$this, 'enable_translations']);

		add_filter('body_class', [&$this, 'body_class']);

//		$end = microtime(true);
//		print_r("<!-- Photonic initialization: ".($end - $start)." -->\n");

		add_action('widgets_init', [&$this, 'load_widget']);

		add_action('elementor/common/after_register_scripts', [&$this, 'enqueue_widget_scripts']);

		require_once(PHOTONIC_PATH."/Core/Template.php");
	}

	/**
	 * @param string $provider
	 * @param array $auth_token
	 */
	public static function save_provider_authentication($provider, $auth_token) {
		$photonic_authentication = get_option('photonic_authentication');
		if (empty($photonic_authentication)) {
			$photonic_authentication = [];
		}
		if (empty($photonic_authentication[$provider])) {
			$photonic_authentication[$provider] = [];
		}
		$photonic_authentication[$provider] = $auth_token;
		update_option('photonic_authentication', $photonic_authentication);
	}

	/**
	 * Adds a menu item to the "Settings" section of the admin page.
	 *
	 * @return void
	 */
	function add_admin_menu() {
		if (current_user_can('edit_theme_options')) {
			$parent_slug = 'photonic-options-manager';
		}
		else if (current_user_can('edit_posts')) {
			$parent_slug = 'photonic-getting-started';
		}

		if (!empty($parent_slug)) {
			add_menu_page('Photonic', 'Photonic', 'edit_posts', $parent_slug, [&$this->admin_menu, 'settings'], PHOTONIC_URL.'include/images/Photonic-20-gr.png');
			add_submenu_page($parent_slug, esc_html__('Settings', 'photonic'), esc_html__('Settings', 'photonic'), 'edit_theme_options', 'photonic-options-manager', [&$this->admin_menu, 'settings']);
			add_submenu_page($parent_slug, 'Getting Started', 'Getting Started', 'edit_posts', 'photonic-getting-started', [&$this->admin_menu, 'getting_started']);
			add_submenu_page($parent_slug, 'Authentication', 'Authentication', 'edit_theme_options', 'photonic-auth', [&$this->admin_menu, 'authentication']);
			add_submenu_page($parent_slug, esc_html__('Switch to Gutenberg', 'photonic'), esc_html__('Switch to Gutenberg', 'photonic'), 'edit_posts', 'photonic-gutenberg', [&$this->admin_menu, 'gutenberg']);
			add_submenu_page($parent_slug, 'Helpers', 'Helpers', 'edit_posts', 'photonic-helpers', [&$this->admin_menu, 'helpers']);
		}
	}

	/**
	 * Adds all scripts and their dependencies to the end of the <body> element only on pages using Photonic.
	 *
	 * @param array $attr
	 * @return void
	 */
	function conditionally_add_scripts($attr = []) {
		global $photonic_slideshow_library, $photonic_custom_lightbox_js, $photonic_is_IE, $photonic_custom_lightbox, $photonic_always_load_scripts,
		       $photonic_disable_photonic_lightbox_scripts, $photonic_disable_photonic_slider_scripts, $photonic_js_in_header, $photonic_thumbnail_style;

		if (isset($attr['style'])) {
			$layout = $attr['style'];
		}
		else if (isset($attr['layout'])) {
			$layout = $attr['layout'];
		}
		else if (isset($photonic_thumbnail_style)) {
			$layout = $photonic_thumbnail_style;
		}
		else {
			$layout = 'square';
		}

		$photonic_dependencies = ['jquery'];

		if ($photonic_is_IE && $layout == 'masonry') {
			wp_enqueue_script('photonic-ie', PHOTONIC_URL.'include/scripts/front-end/src/photonic-ie.js', ['jquery-masonry', 'photonic'], $this->get_version(PHOTONIC_PATH.'/include/scripts/front-end/src/photonic-ie.js'), !($photonic_always_load_scripts && $photonic_js_in_header));
		}

		if ($photonic_slideshow_library == 'thickbox') {
			wp_enqueue_script('thickbox');
			$photonic_dependencies[] = 'thickbox';
		}
		else if ($photonic_slideshow_library == 'custom') {
			$counter = 1;
			$dependencies = ['jquery'];
			foreach(preg_split("/((\r?\n)|(\r\n?))/", $photonic_custom_lightbox_js) as $line){
				wp_enqueue_script('photonic-lightbox-'.$counter, trim($line), $dependencies, PHOTONIC_VERSION, !($photonic_always_load_scripts && $photonic_js_in_header));
				$photonic_dependencies[] = 'photonic-lightbox-'.$counter;
				$counter++;
			}
		}
		else if ($photonic_slideshow_library != 'none') {
			if (empty($photonic_slideshow_library)) {
				$photonic_slideshow_library = 'swipebox';
			}

			if (empty($photonic_disable_photonic_lightbox_scripts)) {
				$lb_deps = ['jquery'];
				if ($photonic_slideshow_library == 'fluidbox') {
					$lb_deps[] = 'imagesloaded';
				}
				if ($photonic_slideshow_library == 'lightgallery') {
					global $photonic_enable_lg_zoom, $photonic_enable_lg_thumbnail, $photonic_enable_lg_fullscreen, $photonic_enable_lg_autoplay;
					$lightgallery_plugins = [];
					if (!empty($photonic_enable_lg_autoplay)) { $lightgallery_plugins[] = 'autoplay'; }
					if (!empty($photonic_enable_lg_fullscreen)) { $lightgallery_plugins[] = 'fullscreen'; }
					if (!empty($photonic_enable_lg_thumbnail)) { $lightgallery_plugins[] = 'thumbnail'; }
					if (!empty($photonic_enable_lg_zoom)) { $lightgallery_plugins[] = 'zoom'; }
					if (!empty($lightgallery_plugins)) {
						wp_enqueue_script('photonic-lightbox', PHOTONIC_URL.'include/scripts/third-party/'.$photonic_slideshow_library.'/'.$photonic_slideshow_library.PHOTONIC_DEV_MODE.'.js', $lb_deps, $this->get_version(PHOTONIC_PATH.'/include/scripts/third-party/'.$photonic_slideshow_library.'/'.$photonic_slideshow_library.PHOTONIC_DEV_MODE.'.js'), !($photonic_always_load_scripts && $photonic_js_in_header));
						$photonic_dependencies[] = 'photonic-lightbox';
					}
					if (PHOTONIC_DEV_MODE) {
						foreach ($lightgallery_plugins as $plugin) {
							wp_enqueue_script('photonic-lightbox-'.$plugin, PHOTONIC_URL.'include/scripts/third-party/'.$photonic_slideshow_library.'/lg-plugin-'.$plugin.'.min.js', ['photonic-lightbox'], $this->get_version(PHOTONIC_PATH.'/include/scripts/third-party/'.$photonic_slideshow_library.'/lg-plugin-'.$plugin.'.min.js'), !($photonic_always_load_scripts && $photonic_js_in_header));
						}
					}
					else {
						wp_enqueue_script('photonic-lightbox-plugins', PHOTONIC_URL.'include/scripts/third-party/'.$photonic_slideshow_library.'/lightgallery-plugins.js', ['photonic-lightbox'], $this->get_version(PHOTONIC_PATH.'/include/scripts/third-party/'.$photonic_slideshow_library.'/lightgallery-plugins.js'), !($photonic_always_load_scripts && $photonic_js_in_header));
					}
				}
			}
		}

		$slideshow_library = $photonic_slideshow_library == 'custom' ? $photonic_custom_lightbox : $photonic_slideshow_library;
		$slideshow_library = empty($slideshow_library) ? 'swipebox' : $slideshow_library;

		if (empty($photonic_disable_photonic_lightbox_scripts) && !($slideshow_library == 'lightgallery' && !empty($lightgallery_plugins))) {
			$script_type = 'combo';
		}
		else {
			$script_type = 'solo';
		}

		if (empty($photonic_disable_photonic_slider_scripts)) {
			$script_type .= '-slider';
		}

		wp_enqueue_script('photonic', PHOTONIC_URL."include/scripts/front-end/jq/$script_type/photonic-".$slideshow_library.PHOTONIC_DEV_MODE.'.js', $photonic_dependencies, $this->get_version(PHOTONIC_PATH."/include/scripts/front-end/jq/$script_type/photonic-".$slideshow_library.PHOTONIC_DEV_MODE.'.js'), !($photonic_always_load_scripts && $photonic_js_in_header));

		$this->localize_variables_once();
	}

	function localize_variables_once() {
		if ($this->localized) {
			return;
		}
		// Technically JS, but needs to happen here, otherwise the script is repeated multiple times, once for each time
		// <code>conditionally_add_scripts</code> is called.
		$js_array = $this->get_localized_js_variables();
		wp_localize_script('photonic', 'Photonic_JS', $js_array);
		$this->localized = true;
	}

	/**
	 * Adds all styles to all pages because styles, if not added in the header can cause issues.
	 *
	 * @return void
	 */
	function always_add_styles() {
		global $photonic_slideshow_library, $photonic_custom_lightbox_css, $photonic_disable_photonic_lightbox_scripts, $photonic_disable_photonic_slider_scripts;

		if ($photonic_slideshow_library == 'custom') {
			$counter = 1;
			foreach(preg_split("/((\r?\n)|(\r\n?))/", $photonic_custom_lightbox_css) as $line){
				wp_enqueue_style('photonic-lightbox-'.$counter, trim($line), [], PHOTONIC_VERSION);
				$counter++;
			}
		}

		if ($photonic_slideshow_library != 'none') {
			if (empty($photonic_slideshow_library)) {
				$photonic_slideshow_library = 'swipebox';
			}
		}

		global $photonic_custom_lightbox;
		$slideshow_library = !empty($photonic_disable_photonic_lightbox_scripts) ? 'none' :
			($photonic_slideshow_library == 'custom' ? $photonic_custom_lightbox : $photonic_slideshow_library);

		$this->enqueue_lightbox_styles($slideshow_library, empty($photonic_disable_photonic_slider_scripts));

		global $photonic_css_in_file;
		$file = trailingslashit(PHOTONIC_UPLOAD_DIR).'custom-styles.css';
		if (@file_exists($file) && !empty($photonic_css_in_file)) {
			wp_enqueue_style('photonic-custom', trailingslashit(PHOTONIC_UPLOAD_URL).'custom-styles.css', ['photonic'], $this->get_version($file));
		}

		if (class_exists('\FLBuilderModel') && \FLBuilderModel::is_builder_active()) {
			$this->enqueue_widget_scripts();
		}
	}

	function enqueue_lightbox_styles($slideshow_library = 'swipebox', $combine_slider = true) {
		$template_directory = get_template_directory();
		$stylesheet_directory = get_stylesheet_directory();

		$folder = $combine_slider ? 'combo-slider' : 'combo';

		if ($slideshow_library == 'colorbox') {
			global $photonic_cbox_theme;
			if ($photonic_cbox_theme == 'theme' && @file_exists($stylesheet_directory.'/scripts/colorbox/colorbox.css')) {
				wp_enqueue_style('photonic-lightbox', get_stylesheet_directory_uri().'/scripts/colorbox/colorbox.css', [], PHOTONIC_VERSION);
			}
			else if ($photonic_cbox_theme == 'theme' && @file_exists($template_directory.'/scripts/colorbox/colorbox.css')) {
				wp_enqueue_style('photonic-lightbox', get_template_directory_uri().'/scripts/colorbox/colorbox.css', [], PHOTONIC_VERSION);
			}
			else if ($photonic_cbox_theme == 'theme') {
				wp_enqueue_style('photonic-lightbox', PHOTONIC_URL.'include/scripts/third-party/colorbox/style-1/colorbox.css', [], $this->get_version(PHOTONIC_PATH.'/include/scripts/third-party/colorbox/style-1/colorbox.css'));
			}
			else {
				wp_enqueue_style('photonic-lightbox', PHOTONIC_URL.'include/scripts/third-party/colorbox/style-'.$photonic_cbox_theme.'/colorbox.css', [], $this->get_version(PHOTONIC_PATH.'/include/scripts/third-party/colorbox/style-'.$photonic_cbox_theme.'/colorbox.css'));
			}
		}
		else if ($slideshow_library == 'lightgallery') {
			global $photonic_enable_lg_transitions;
			if (!empty($photonic_enable_lg_transitions)) {
				wp_enqueue_style('photonic-lightbox-lg-transitions', PHOTONIC_URL.'include/scripts/third-party/lightgallery/lightgallery-transitions.min.css', [], $this->get_version(PHOTONIC_PATH.'/include/scripts/third-party/lightgallery/lightgallery-transitions.min.css'));
			}
		}
		else if ($slideshow_library == 'thickbox') {
			wp_enqueue_style('thickbox');
		}

		wp_enqueue_style('photonic', PHOTONIC_URL."include/css/front-end/$folder/photonic-$slideshow_library.min.css", [], $this->get_version(PHOTONIC_PATH."/include/css/front-end/$folder/photonic-$slideshow_library.min.css"));
	}

	/**
	 * Prints the custom CSS directly in the header if the option is not set to include it as a file
	 */
	function print_scripts() {
		global $photonic_css_in_file;
		$file = trailingslashit(PHOTONIC_UPLOAD_DIR).'custom-styles.css';
		if (!@file_exists($file) || empty($photonic_css_in_file)) {
			$this->generate_css();
		}
	}

	/**
	 * Prints the dynamically generated CSS based on option selections.
	 *
	 * @param bool $header
	 * @return string
	 */
	function generate_css($header = true) {
		global $photonic_flickr_collection_set_constrain_by_padding, $photonic_flickr_photos_constrain_by_padding, $photonic_flickr_photos_pop_constrain_by_padding, $photonic_flickr_galleries_constrain_by_padding;
		global $photonic_smug_photos_constrain_by_padding, $photonic_smug_photos_pop_constrain_by_padding, $photonic_smug_albums_album_constrain_by_padding, $photonic_instagram_photos_constrain_by_padding;
		global $photonic_zenfolio_photos_constrain_by_padding, $photonic_zenfolio_sets_constrain_by_padding, $photonic_tile_spacing, $photonic_masonry_tile_spacing, $photonic_mosaic_tile_spacing, $photonic_masonry_min_width;
		global $photonic_google_photos_constrain_by_padding;

		$css = '';
		if ($header) {
			$css .= '<style type="text/css">'."\n";
		}

		$saved_css = get_option('photonic_css');

		if ($header && !empty($saved_css)) {
			$css .= "/* Retrieved from saved CSS */\n";
			$css .= $saved_css;
		}
		else {
			if ($header) {
				$css .= "/* Dynamically generated CSS */\n";
			}
			$css .= ".photonic-panel { ".
				$this->get_bg_css('photonic_flickr_gallery_panel_background').
				$this->get_border_css('photonic_flickr_set_popup_thumb_border').
				" }\n";

			$css .= ".photonic-flickr-stream .photonic-pad-photosets { margin: {$photonic_flickr_collection_set_constrain_by_padding}px; }\n";
			$css .= ".photonic-flickr-stream .photonic-pad-galleries { margin: {$photonic_flickr_galleries_constrain_by_padding}px; }\n";
			$css .= ".photonic-flickr-stream .photonic-pad-photos { padding: 5px {$photonic_flickr_photos_constrain_by_padding}px; }\n";

			$css .= ".photonic-google-stream .photonic-pad-photos { padding: 5px {$photonic_google_photos_constrain_by_padding}px; }\n";

			$css .= ".photonic-zenfolio-stream .photonic-pad-photos { padding: 5px {$photonic_zenfolio_photos_constrain_by_padding}px; }\n";
			$css .= ".photonic-zenfolio-stream .photonic-pad-photosets { margin: 5px {$photonic_zenfolio_sets_constrain_by_padding}px; }\n";

			$css .= ".photonic-instagram-stream .photonic-pad-photos { padding: 5px {$photonic_instagram_photos_constrain_by_padding}px; }\n";

			$css .= ".photonic-smug-stream .photonic-pad-albums { margin: {$photonic_smug_albums_album_constrain_by_padding}px; }\n";
			$css .= ".photonic-smug-stream .photonic-pad-photos { padding: 5px {$photonic_smug_photos_constrain_by_padding}px; }\n";

			$css .= ".photonic-flickr-panel .photonic-pad-photos { padding: 10px {$photonic_flickr_photos_pop_constrain_by_padding}px; box-sizing: border-box; }\n";
			$css .= ".photonic-smug-panel .photonic-pad-photos { padding: 10px {$photonic_smug_photos_pop_constrain_by_padding}px; box-sizing: border-box; }\n";

			$css .= ".photonic-random-layout .photonic-thumb { padding: {$photonic_tile_spacing}px}\n";
			$css .= ".photonic-masonry-layout .photonic-thumb { padding: {$photonic_masonry_tile_spacing}px}\n";
			$css .= ".photonic-mosaic-layout .photonic-thumb { padding: {$photonic_mosaic_tile_spacing}px}\n";

			$css .= ".photonic-ie .photonic-masonry-layout .photonic-level-1, .photonic-ie .photonic-masonry-layout .photonic-level-2 { width: ".(absint($photonic_masonry_min_width) ? absint($photonic_masonry_min_width) : 200)."px; }\n";
		}

		if ($header) {
			$css .= "\n</style>\n";
			echo $css;
		}
		return $css;
	}

	public static function get_version($file) {
		return date("Ymd-Gis", @filemtime($file));
	}

	function admin_init() {
		require_once(PHOTONIC_PATH."/Admin/Admin.php");

		if (!empty($_REQUEST['page']) &&
			in_array($_REQUEST['page'], ['photonic-options-manager', 'photonic-options', 'photonic-helpers', 'photonic-getting-started', 'photonic-auth', 'photonic-gutenberg'])) {
			require_once(PHOTONIC_PATH."/Admin/Admin_Menu.php");
			$this->admin_menu = new Admin_Menu(__FILE__, $this);
		}
	}

	function add_extensions() {
		require_once("Gallery.php");
		require_once(PHOTONIC_PATH."/Modules/Core.php");
		require_once(PHOTONIC_PATH.'/Layouts/Core_Layout.php');

		do_action('photonic_register_extensions');
	}

	/**
	 * Overrides the native gallery short code, and does a lot more.
	 *
	 * @param $content
	 * @param array $attr
	 * @return string
	 */
	function modify_gallery($content, $attr = []) {
		global $photonic_alternative_shortcode;

		// If an alternative shortcode is used, then $content has the shortcode attributes
		if (!empty($photonic_alternative_shortcode)) {
			$attr = $content;
		}
		if ($attr == null) {
			$attr = [];
		}

		$this->conditionally_add_scripts($attr);
		$images = $this->get_gallery_images($attr);

		if (isset($images) && !is_array($images)) {
			return $images;
		}

		return $content;
	}

	/**
	 * Adds Photonic attributes to the native WP galleries. This cannot be called in <code>Photonic_Plugin\Modules\Native</code> because
	 * that class is not initialised until a gallery of the native type is encountered
	 *
	 * @param $out
	 * @param $pairs
	 * @param $attributes
	 * @return mixed
	 */
	function native_gallery_attributes($out, $pairs, $attributes) {
		global $photonic_wp_title_caption, $photonic_enable_popup, $photonic_thumbnail_style, $photonic_alternative_shortcode;

		$bypass = empty($photonic_enable_popup) || $photonic_enable_popup == 'off';
		$defaults = [
			'layout' => !empty($photonic_thumbnail_style) ? $photonic_thumbnail_style : 'square',
			'more' => '',
			'display' => 'in-page',
			'panel' => '',
			'filter' => '',
			'filter_type' => 'include',
			'fx' => 'slide', 	// LightSlider effects: fade and slide
			'timeout' => 4000, 	// Time between slides in ms
			'speed' => 1000,	// Time for each transition
			'pause' => true,	// Pause on hover
			'strip-style' => 'thumbs',
			'controls' => 'show',
			'popup' => $bypass ? 'hide' : 'show',

			'custom_classes' => '',
			'alignment' => '',

			'caption' => $photonic_wp_title_caption,
			'page' => 1,
			'count' => -1,
			'thumb_width' => 75,
			'thumb_height' => 75,
			'thumb_size' => 'thumbnail',
			'slide_size' => 'large',
			'slideshow_height' => 500,
		];

		$attributes = array_merge($defaults, $attributes);
		if (empty($attributes['style']) || ($attributes['style'] == 'default' && !empty($photonic_alternative_shortcode) && $photonic_alternative_shortcode != 'gallery')) {
			$attributes['style'] = $attributes['layout'];
		}

		foreach ($attributes as $key => $value) {
			$out[$key] = $value;
		}
		return $out;
	}

	/**
	 * @param array $attr
	 * @return string
	 */
	function helper_shortcode($attr = []) {
		if ($attr == null) {
			$attr = [];
		}

		$this->conditionally_add_scripts($attr);

		if (empty($attr['type']) || !in_array(strtolower($attr['type']), ['google', 'flickr', 'smugmug', 'zenfolio'])) {
			return sprintf(esc_html__('Please specify a value for %1%s. Accepted values are %2$s, %3$s, %4$s, %5$s', 'photonic'), '<code>type</code>', '<code>google</code>', '<code>flickr</code>', '<code>smugmug</code>', '<code>zenfolio</code>');
		}

		$gallery = new Gallery($attr);
		return $gallery->get_helper_contents();
	}

	/**
	 * @param $attr
	 * @return array|bool|string
	 */
	function get_gallery_images($attr) {
		global $photonic_nested_shortcodes;
		$attr = array_merge([
			// Especially for Photonic
			'type' => 'default',  //default, flickr, smugmug, google, zenfolio, instagram
			'style' => 'default',   //default, strip-below, strip-above, strip-right, strip-left, no-strip, launch
//			'id' => $post->ID,
//			'layout' => isset($photonic_thumbnail_style) ? $photonic_thumbnail_style : 'square',
		], $attr);

		if ($photonic_nested_shortcodes) {
			$attr = array_map('do_shortcode', $attr);
		}

		extract($attr);

		$type = strtolower($attr['type']);

		if ($type == 'picasa') {
			$message = esc_html__('Google has deprecated the Picasa API. Please consider switching over to Google Photos', 'photonic');
			return "<div class='photonic-error'>\n\t<span class='photonic-error-icon photonic-icon'>&nbsp;</span>\n\t<div class='photonic-message'>\n\t\t$message\n\t</div>\n</div>\n";
		}

		if ($type == '500px') {
			$message = esc_html__('The API for 500px.com is no longer available for public use.', 'photonic');
			return "<div class='photonic-error'>\n\t<span class='photonic-error-icon photonic-icon'>&nbsp;</span>\n\t<div class='photonic-message'>\n\t\t$message\n\t</div>\n</div>\n";
		}

		if (!empty($attr['show_gallery']) && in_array($type, ['flickr', 'smugmug', 'google', 'zenfolio', 'instagram'])) { // Lazy button not for WP galleries
			$images = $this->get_show_gallery_button($attr);
		}
		else {
			$gallery = new Gallery($attr);
			$images = $gallery->get_contents();
		}

		return $images;
	}

	/**
	 * Clicking on a level 2 object (i.e. an Album / Set / Gallery) triggers this. This will fetch the contents of the level 2 object and generate the markup for it.
	 *
	 * @return void
	 */
	function display_level_2_contents() {
		$panel = esc_attr($_POST['panel_id']);
		$components = explode('-', $panel);

		if (count($components) <= 5) {
			die();
		}
		$panel = implode('-', array_slice($components, 4, 10, true));
		$query = sanitize_text_field($_POST['query']);
		$query = wp_parse_args($query);

		$args = [
			'display' => 'popup',
			'layout' => 'square',
			'panel' => $panel,
			'password' => !empty($_POST['password']) ? sanitize_text_field($_POST['password']) : '',
			'count' => sanitize_text_field($_POST['photo_count']),
			'photo_more' => sanitize_text_field($_POST['photo_more']),
			'main_size' => $query['main_size'],
			'type' => $components[1]
		];

		$provider = $components[1];
		$type = $components[2];
		if (in_array($provider, ['smug', 'smugmug', 'zenfolio', 'google', 'flickr'])) {
			if ($provider == 'smug') {
				$args['view'] = 'album';
				$args['album_key'] = $components[4];
			}
			else if ($provider == 'zenfolio') {
				$args['view'] = 'photosets';
				$args['object_id'] = $components[4];
				$args['thumb_size'] = sanitize_text_field($_POST['overlay_size']);
				$args['video_size'] = sanitize_text_field($_POST['overlay_video_size']);
				$args['realm_id'] = sanitize_text_field($_POST['realm_id']);
			}
			else if ($provider == 'google') {
				$args['view'] = 'photos';
				$args['album_id'] = implode('-', array_slice($components, 4, (count($components) - 1) - 4));
				$args['thumb_size'] = sanitize_text_field($_POST['overlay_size']);
				$args['video_size'] = sanitize_text_field($_POST['overlay_video_size']);
				$args['crop_thumb'] = sanitize_text_field($_POST['overlay_crop']);
			}
			else if ($provider == 'flickr') {
				if ($type == 'gallery') {
					$args['gallery_id'] = $components[4].'-'.$components[5];
					$args['gallery_id_computed'] = true;
				}
				else if ($type = 'set') {
					$args['photoset_id'] = $components[4];
				}
				$args['thumb_size'] = sanitize_text_field($_POST['overlay_size']);
				$args['video_size'] = sanitize_text_field($_POST['overlay_video_size']);
			}

			$gallery = new Gallery($args);
			echo $gallery->get_contents();
		}
		die();
	}

	function display_level_3_contents() {
		$node = esc_attr($_POST['node']);
		$components = explode('-', $node);

		if (count($components) <= 3) {
			die();
		}

		$args = ['display' => 'in-page', 'headers' => '', 'layout' => esc_attr($_POST['layout'])];

		$provider = $components[0];
		if ($provider == 'flickr') {
			$args['collection_id'] = implode('-', array_slice($components, 2, 2, true));
			$args['user_id'] = $components[4];
			$args['type'] = 'flickr';
			$gallery = new Gallery($args);
			echo $gallery->get_contents();
		}
		die();
	}

	/**
	 * Constructs the CSS for a "background" option
	 *
	 * @param $option
	 * @return string
	 */
	function get_bg_css($option) {
		global ${$option};
		$option_val = ${$option};
		if (!is_array($option_val)) {
			$val_array = [];
			$vals = explode(';', $option_val);
			foreach ($vals as $val) {
				if (trim($val) == '') { continue; }
				$pair = explode('=', $val);
				$val_array[$pair[0]] = $pair[1];
			}
			$option_val = $val_array;
		}
		$bg_string = "";
		$bg_rgba_string = "";
		if (!isset($option_val['colortype']) || $option_val['colortype'] == 'transparent') {
			$bg_string .= " transparent ";
		}
		else {
			if (isset($option_val['color'])) {
				if (substr($option_val['color'], 0, 1) == '#') {
					$color_string = substr($option_val['color'],1);
				}
				else {
					$color_string = $option_val['color'];
				}
				$rgb_str_array = [];
				if (strlen($color_string)==3) {
					$rgb_str_array[] = substr($color_string, 0, 1).substr($color_string, 0, 1);
					$rgb_str_array[] = substr($color_string, 1, 1).substr($color_string, 1, 1);
					$rgb_str_array[] = substr($color_string, 2, 1).substr($color_string, 2, 1);
				}
				else {
					$rgb_str_array[] = substr($color_string, 0, 2);
					$rgb_str_array[] = substr($color_string, 2, 2);
					$rgb_str_array[] = substr($color_string, 4, 2);
				}
				$rgb_array = [];
				$rgb_array[] = hexdec($rgb_str_array[0]);
				$rgb_array[] = hexdec($rgb_str_array[1]);
				$rgb_array[] = hexdec($rgb_str_array[2]);
				$rgb_string = implode(',',$rgb_array);
				$rgb_string = ' rgb('.$rgb_string.') ';

				if (isset($option_val['trans'])) {
					$bg_rgba_string = $bg_string;
					$transparency = (int)$option_val['trans'];
					if ($transparency != 0) {
						$trans_dec = $transparency/100;
						$rgba_string = implode(',', $rgb_array);
						$rgba_string = ' rgba('.$rgba_string.','.$trans_dec.') ';
						$bg_rgba_string .= $rgba_string;
					}
				}

				$bg_string .= $rgb_string;
			}
		}
		if (isset($option_val['image']) && trim($option_val['image']) != '') {
			$bg_string .= " url(".$option_val['image'].") ";
			$bg_string .= $option_val['position']." ".$option_val['repeat'];

			if (trim($bg_rgba_string) != '') {
				$bg_rgba_string .= " url(".$option_val['image'].") ";
				$bg_rgba_string .= $option_val['position']." ".$option_val['repeat'];
			}
		}

		if (trim($bg_string) != '') {
			$bg_string = "background: ".$bg_string." !important;\n";
			if (trim($bg_rgba_string) != '') {
				$bg_string .= "\tbackground: ".$bg_rgba_string." !important;\n";
			}
		}
		return $bg_string;
	}

	/**
	 * Generates the CSS for borders. Each border, top, right, bottom and left is generated as a separate line.
	 *
	 * @param $option
	 * @return string
	 */
	function get_border_css($option) {
		global ${$option};
		$option_val = ${$option};
		if (!is_array($option_val)) {
			$option_val = stripslashes($option_val);
			$edge_array = $this->build_edge_array($option_val);
			$option_val = $edge_array;
		}
		$border_string = '';
		foreach ($option_val as $edge => $selections) {
			$border_string .= "\tborder-$edge: ";
			if (!isset($selections['style'])) {
				$selections['style'] = 'none';
			}
			if ($selections['style'] == 'none') {
				$border_string .= "none";
			}
			else {
				if (isset($selections['border-width'])) {
					$border_string .= $selections['border-width'];
				}
				if (isset($selections['border-width-type'])) {
					$border_string .= $selections['border-width-type'];
				}
				else {
					$border_string .= "px";
				}
				$border_string .= " ".$selections['style']." ";
				if ($selections['colortype'] == 'transparent') {
					$border_string .= "transparent";
				}
				else {
					if (substr($selections['color'], 0, 1) == '#') {
						$border_string .= $selections['color'];
					}
					else {
						$border_string .= '#'.$selections['color'];
					}
				}
			}
			$border_string .= ";\n";
		}
		return "\n".$border_string;
	}

	public function build_edge_array($option_val) {
		$edge_array = [];
		$edges = explode('||', $option_val);
		foreach ($edges as $edge_val) {
			if (trim($edge_val) != '') {
				$edge_options = explode('::', trim($edge_val));
				if (is_array($edge_options) && count($edge_options) > 1) {
					$val_array = [];
					$vals = explode(';', $edge_options[1]);
					foreach ($vals as $val) {
						$pair = explode('=', $val);
						if (is_array($pair) && count($pair) > 1) {
							$val_array[$pair[0]] = $pair[1];
						}
					}
					$edge_array[$edge_options[0]] = $val_array;
				}
			}
		}
		return $edge_array;
	}

	/**
	 * Make an HTTP request
	 *
	 * @static
	 * @param $url
	 * @param string $method GET | POST | DELETE
	 * @param null $post_fields
	 * @param string $user_agent
	 * @param int $timeout
	 * @param bool $ssl_verify_peer
	 * @param array $headers
	 * @param array $cookies
	 * @return array|WP_Error
	 */
	static function http($url, $method = 'POST', $post_fields = NULL, $user_agent = null, $timeout = 90, $ssl_verify_peer = false, $headers = [], $cookies = []) {
		$curl_args = [
			'user-agent' => $user_agent,
			'timeout' => $timeout,
			'sslverify' => $ssl_verify_peer,
			'headers' => array_merge(['Expect:'], $headers),
			'method' => $method,
			'body' => $post_fields,
			'cookies' => $cookies,
		];

		switch ($method) {
			case 'DELETE':
				if (!empty($post_fields)) {
					$url = "{$url}?{$post_fields}";
				}
				break;
		}

		$response = wp_remote_request($url, $curl_args);
		return $response;
	}

	function obtain_token() {
		require_once(PHOTONIC_PATH."/Admin/Authentication.php");
		$auth = Authentication::get_instance();
		$auth->obtain_token();
		die();
	}

	/**
	 * Invoked via AJAX in the "Authentication" page, when the user clicks on "Save Token"
	 */
	function save_token_in_options() {
		$provider = strtolower(sanitize_text_field($_POST['provider']));
		$token = esc_attr($_POST['token']);
		$secret = esc_attr($_POST['secret']);
		if (!empty($_POST['expires_in'])) {
			$expires_in = esc_attr($_POST['expires_in']);
		}

		$options = get_option('photonic_options');
		if (empty($options)) {
			$options = [];
		}
		$option_set = false;
		if (in_array($provider, ['flickr', 'smug', 'zenfolio'])) {
			$options[$provider.'_access_token'] = $token;
			$options[$provider.'_token_secret'] = $secret;
			$option_set = true;
		}
		else if (in_array($provider, ['google'])) {
			$options[$provider.'_refresh_token'] = $token;
			$option_set = true;
		}
		else if (in_array($provider, ['instagram'])) {
			$client_id = esc_attr($_POST['client_id']);
			$user = esc_attr($_POST['user']);

			$options[$provider.'_access_token'] = $token;

			$auth_token = [];
			$auth_token['oauth_token'] = $token;
			$auth_token['oauth_token_created'] = time();
			if (!empty($expires_in)) {
				$auth_token['oauth_token_expires'] = $expires_in;
			}
			$auth_token['client_id'] = $client_id;
			$auth_token['user'] = $user;

			self::save_provider_authentication($provider, $auth_token);

			$option_set = true;
		}

		if ($option_set) {
			update_option('photonic_options', $options);
			echo admin_url('admin.php?page=photonic-options-manager&tab='.$this->provider_map[$provider].'.php');
		}
		die();
	}

	function delete_token_from_options() {
		$provider = strtolower(sanitize_text_field($_POST['provider']));
		$photonic_authentication = get_option('photonic_authentication');
		if ($provider == 'zenfolio') {
			if (isset($photonic_authentication[$provider])) {
				unset($photonic_authentication[$provider]);
			}
		}
		update_option('photonic_authentication', $photonic_authentication);
		die();
	}

	function invoke_helper() {
		require_once(PHOTONIC_PATH."/Admin/Helper.php");
		$helper = new Helper();
		$helper->invoke_helper();
	}

	/**
	 * @return array
	 */
	public function get_localized_js_variables() {
		global $photonic_lightbox_no_loop, $photonic_slideshow_mode, $photonic_slideshow_interval, $photonic_slideshow_library, $photonic_custom_lightbox,
		       $photonic_cb_transition_effect, $photonic_cb_transition_speed,
		       $photonic_fbox_title_position, $photonic_fb3_transition_effect, $photonic_fb3_transition_speed, $photonic_fb3_show_fullscreen, $photonic_enable_fb3_fullscreen,
		       $photonic_fb3_hide_thumbs, $photonic_enable_fb3_thumbnail, $photonic_fb3_disable_zoom, $photonic_fb3_disable_slideshow, $photonic_fb3_enable_download, $photonic_fb3_disable_right_click,
		       $photonic_lc_transition_effect, $photonic_lc_transition_speed_in, $photonic_lc_transition_speed_out, $photonic_lc_enable_shrink,
		       $photonic_lg_transition_effect, $photonic_lg_transition_speed, $photonic_disable_lg_download, $photonic_lg_hide_bars_delay,
		       $photonic_tile_spacing, $photonic_tile_min_height, $photonic_pphoto_theme, $photonic_pp_animation_speed,
		       $photonic_enable_swipebox_mobile_bars, $photonic_sb_hide_mobile_close, $photonic_sb_hide_bars_delay,
		       $photonic_lightbox_for_all, $photonic_lightbox_for_videos, $photonic_popup_panel_width, $photonic_deep_linking, $photonic_social_media,
		       $photonic_slideshow_prevent_autostart, $photonic_wp_slide_adjustment, $photonic_masonry_min_width, $photonic_mosaic_trigger_width,
		       $photonic_is_IE, $photonic_debug_on;

		global $photonic_js_variables;

		$slideshow_library = $photonic_slideshow_library == 'custom' ? $photonic_custom_lightbox : $photonic_slideshow_library;
		$js_array = [
			'ajaxurl' => admin_url('admin-ajax.php'),
			'plugin_url' => PHOTONIC_URL,
			'is_old_IE' => $photonic_is_IE,
			'debug_on' => !empty($photonic_debug_on),

			'fbox_show_title' => $photonic_fbox_title_position == 'none' ? false : true,
			'fbox_title_position' => $photonic_fbox_title_position == 'none' ? 'outside' : $photonic_fbox_title_position,

			'slide_adjustment' => empty($photonic_wp_slide_adjustment) ? 'adapt-height-width' : $photonic_wp_slide_adjustment,

			'deep_linking' => isset($photonic_deep_linking) ? $photonic_deep_linking : 'none',
			'social_media' => isset($photonic_deep_linking) ? $photonic_deep_linking != 'none' && empty($photonic_social_media) : '',

			'slideshow_library' => $slideshow_library,
			'tile_spacing' => (empty($photonic_tile_spacing) || !absint($photonic_tile_spacing)) ? 0 : absint($photonic_tile_spacing),
			'tile_min_height' => (empty($photonic_tile_min_height) || !absint($photonic_tile_min_height)) ? 200 : absint($photonic_tile_min_height),
			'masonry_min_width' => (empty($photonic_masonry_min_width) || !absint($photonic_masonry_min_width)) ? 200 : absint($photonic_masonry_min_width),
			'mosaic_trigger_width' => (empty($photonic_mosaic_trigger_width) || !absint($photonic_mosaic_trigger_width)) ? 200 : absint($photonic_mosaic_trigger_width),

			'slideshow_mode' => (isset($photonic_slideshow_mode) && $photonic_slideshow_mode == 'on') ? true : false,
			'slideshow_interval' => (isset($photonic_slideshow_interval) && absint($photonic_slideshow_interval)) ? absint($photonic_slideshow_interval) : 5000,
			'lightbox_loop' => empty($photonic_lightbox_no_loop),

			'gallery_panel_width' => (empty($photonic_popup_panel_width) || !absint($photonic_popup_panel_width) || absint($photonic_popup_panel_width) > 100) ? 80 : absint($photonic_popup_panel_width),

			'cb_transition_effect' => empty($photonic_cb_transition_effect) ? 'elastic' : $photonic_cb_transition_effect,
			'cb_transition_speed' => (isset($photonic_cb_transition_speed) && absint($photonic_cb_transition_speed)) ? absint($photonic_cb_transition_speed) : 350,

			'fb3_transition_effect' => empty($photonic_fb3_transition_effect) ? 'zoom' : $photonic_fb3_transition_effect,
			'fb3_transition_speed' => (isset($photonic_fb3_transition_speed) && absint($photonic_fb3_transition_speed)) ? absint($photonic_fb3_transition_speed) : 366,
			'fb3_fullscreen_button' => !empty($photonic_fb3_show_fullscreen),
			'fb3_fullscreen' => isset($photonic_enable_fb3_fullscreen) && $photonic_enable_fb3_fullscreen == 'on' ? true : false,
			'fb3_thumbs_button' => empty($photonic_fb3_hide_thumbs),
			'fb3_thumbs' => isset($photonic_enable_fb3_thumbnail) && $photonic_enable_fb3_thumbnail == 'on' ? true : false,
			'fb3_zoom' => empty($photonic_fb3_disable_zoom),
			'fb3_slideshow' => empty($photonic_fb3_disable_slideshow),
			'fb3_download' => !empty($photonic_fb3_enable_download),
			'fb3_disable_right_click' => !empty($photonic_fb3_disable_right_click),

			'lc_transition_effect' => empty($photonic_lc_transition_effect) ? 'scrollHorizontal' : $photonic_lc_transition_effect,
			'lc_transition_speed_in' => (isset($photonic_lc_transition_speed_in) && absint($photonic_lc_transition_speed_in)) ? absint($photonic_lc_transition_speed_in) : 350,
			'lc_transition_speed_out' => (isset($photonic_lc_transition_speed_out) && absint($photonic_lc_transition_speed_out)) ? absint($photonic_lc_transition_speed_out) : 250,
			'lc_disable_shrink' => empty($photonic_lc_enable_shrink),

			'lg_transition_effect' => empty($photonic_lg_transition_effect) ? 'lg-slide' : $photonic_lg_transition_effect,
			'lg_enable_download' => empty($photonic_disable_lg_download),
			'lg_hide_bars_delay' => (isset($photonic_lg_hide_bars_delay) && absint($photonic_lg_hide_bars_delay)) ? absint($photonic_lg_hide_bars_delay) : 6000,
			'lg_transition_speed' => (isset($photonic_lg_transition_speed) && absint($photonic_lg_transition_speed)) ? absint($photonic_lg_transition_speed) : 600,

			'pphoto_theme' => isset($photonic_pphoto_theme) ? $photonic_pphoto_theme : 'pp_default',
			'pphoto_animation_speed' => empty($photonic_pp_animation_speed) ? 'fast' : $photonic_pp_animation_speed,

			'enable_swipebox_mobile_bars' => !empty($photonic_enable_swipebox_mobile_bars),
			'sb_hide_mobile_close' => !empty($photonic_sb_hide_mobile_close),
			'sb_hide_bars_delay' => (isset($photonic_sb_hide_bars_delay) && absint($photonic_sb_hide_bars_delay)) ? absint($photonic_sb_hide_bars_delay) : 0,

			'lightbox_for_all' => !empty($photonic_lightbox_for_all),
			'lightbox_for_videos' => !empty($photonic_lightbox_for_videos),

			'slideshow_autostart' => !(isset($photonic_slideshow_prevent_autostart) && $photonic_slideshow_prevent_autostart == 'on'),

			'password_failed' => esc_attr__('This album is password-protected. Please provide a valid password.', 'photonic'),
			'incorrect_password' => esc_attr__('Incorrect password.', 'photonic'),
			'maximize_panel' => esc_attr__('Show', 'photonic'),
			'minimize_panel' => esc_attr__('Hide', 'photonic'),
		];
		$photonic_js_variables = $js_array;
		return $js_array;
	}

	function enable_translations() {
		load_plugin_textdomain('photonic', FALSE, FALSE);
	}

	function load_more() {
		$provider = esc_attr($_POST['provider']);
		$query = sanitize_text_field($_POST['query']);
		$attr = wp_parse_args($query);

		$attr['type'] = $provider;
		if ($provider == 'flickr') {
			$attr['page'] = isset($attr['page']) ? $attr['page'] + 1 : 0;
		}
		else if ($provider == 'google') {
		}
		else if ($provider == 'smug') {
			$attr['start'] = $attr['start'] + $attr['count'];
		}
		else if ($provider == 'zenfolio') {
			$attr['offset'] = $attr['offset'] + $attr['limit'];
		}
		else if ($provider == 'instagram') {
		}
		else if ($provider == 'wp') {
			$attr['page'] = $attr['page'] + 1;
		}
		else {
			unset($attr['type']);
		}

		if (!empty($attr['type'])) {
			$gallery = new Gallery($attr);
			echo $gallery->get_contents();
		}
		die();
	}

	function helper_shortcode_more() {
		if (!empty($_POST['provider'])) {
			$provider = sanitize_text_field($_POST['provider']);
			if (in_array($provider, ['google'])) {
				$attr = ['type' => $provider];
				if ($provider == 'google') {
					$attr['nextPageToken'] = sanitize_text_field($_POST['nextPageToken']);
					$gallery = new Gallery($attr);
					echo $gallery->get_helper_contents();
				}
			}
		}
		die();
	}

	/**
	 * @param string|array $classes
	 * @return array
	 */
	function body_class($classes = []) {
		if (!is_array($classes)) {
			$classes = explode(' ', $classes);
		}

		global $photonic_is_IE;
		if ($photonic_is_IE) {
			$classes[] = 'photonic-ie';
		}

		return $classes;
	}

	/**
	 * Used for handling the front-end for Gutenberg blocks
	 *
	 * @param $attributes
	 * @return array|bool|string
	 */
	function render_block($attributes) {
		if (!empty($attributes['shortcode'])) {
			$shortcode = (array)(json_decode($attributes['shortcode']));

			if (!empty($attributes['align'])) {
				$shortcode['alignment'] = $attributes['align'];
			}
			if (!empty($attributes['className'])) {
				$shortcode['custom_classes'] = $attributes['className'];
			}

			$this->conditionally_add_scripts($shortcode);
			return $this->get_gallery_images($shortcode);
		}
		return '';
	}

	private function add_gutenberg_support() {
		if (function_exists('register_block_type')) {
			register_block_type('photonic/gallery',
				[
					'attributes' => [
						'shortcode' => [
							'type' => 'string',
						],
					],
					'render_callback' => [&$this, 'render_block'],
				]
			);
		}
	}

	function dismiss_warning() {
		$user_id = get_current_user_id();
		$response = [];
		if (!empty($_POST['dismissible'])) {
			add_user_meta( $user_id, "photonic_".esc_sql($_POST['dismissible']), 'true', true );
			$response[$_POST['dismissible']] = 'true';
		}
		echo json_encode($response);
		die();
	}

	function curl_timeout($handle) {
		curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, PHOTONIC_CURL_TIMEOUT);
		curl_setopt($handle, CURLOPT_TIMEOUT, PHOTONIC_CURL_TIMEOUT);
	}

	static function log($element) {
		if (PHOTONIC_DEBUG) {
			print_r($element);
		}
	}

	static function doc_link($link) {
		return ' '.sprintf(esc_html__('See %1$shere%2$s for documentation.', 'photonic'), "<a href='$link'>", '</a>');
	}

	function get_show_gallery_button($attr = []) {
		$button = esc_attr($attr['show_gallery']);
		$button_attr = [];

		if (!empty($attr['show_gallery_button_type']) && $attr['show_gallery_button_type'] == 'image' && !empty($attr['show_gallery_button_image'])) {
			$button_attr['type'] = 'image';
			$button_attr['alt'] = $button;
			$button_attr['src'] = esc_url($attr['show_gallery_button_image']);
		}
		else {
			$button_attr['type'] = 'button';
			$button_attr['value'] = $button;
		}
		$button_attr['class'] = 'photonic-show-gallery-button';

		unset($attr['show_gallery']);
		unset($attr['show_gallery_button_type']);
		unset($attr['show_gallery_button_image']);

		$attr_str = http_build_query($attr);
		$button_attr['data-photonic-shortcode'] = $attr_str;

		$input_attr = [];
		foreach ($button_attr as $name => $value) {
			$input_attr[] = "$name='$value'";
		}
		$input_attr = implode(' ', $input_attr);

		return "<input $input_attr/>";
	}

	function lazy_load() {
		$shortcode = $_POST['shortcode'];
		parse_str($shortcode, $attr);
		$images = $this->get_gallery_images($attr);
		echo $images;
		die();
	}

	function load_widget() {
		require_once(PHOTONIC_PATH. '/Add_Ons/WP/Widget.php');
		register_widget("Photonic_Plugin\Add_Ons\WP\Widget");
	}

	static function enqueue_widget_scripts() {
		global $photonic_alternative_shortcode;
		$js_array = [
			'ajaxurl' => admin_url('admin-ajax.php'),
			'shortcode' => empty($photonic_alternative_shortcode) ? 'gallery' : $photonic_alternative_shortcode,
			'current_shortcode'  => esc_html__('Current shortcode', 'photonic'),
			'edit_message' => esc_html__('Click on the icon to edit your gallery.', 'photonic'),
		];
		wp_enqueue_script('photonic-widget', PHOTONIC_URL.'include/scripts/admin/widget.js', ['jquery'], Photonic::get_version(PHOTONIC_PATH.'/include/scripts/admin/widget.js'));
		wp_localize_script('photonic-widget', 'Photonic_Widget_JS', $js_array);
		wp_enqueue_style('photonic-widget', PHOTONIC_URL.'include/css/admin/widget.css', [], Photonic::get_version(PHOTONIC_PATH.'/include/css/admin/widget.css'));
	}
}
