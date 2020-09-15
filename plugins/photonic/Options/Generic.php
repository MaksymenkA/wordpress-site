<?php
namespace Photonic_Plugin\Options;
use Photonic_Plugin\Core\Utilities;

class Generic extends Option_Tab {
	private static $instance;

	private function __construct() {
		$this->options = [
			['name' => "How To",
				'desc' => "Control generic settings for the plugin",
				'category' => 'generic-how-to',
				'buttons' => 'no-buttons',
				'type' => 'section',],

			['name' => "Creating a gallery",
				'desc' => "Photonic lets you create a gallery via a <strong><em>Gutenberg Block</em></strong>:<br/><br/>
			<img src='".PHOTONIC_URL."screenshot-1.png' style='max-width: 300px;' alt='Gutenberg Block'/><br/><br/>
			Alternatively, if you are using the <strong><em>Classic Editor</em></strong> you can use the <em>Add / Edit Photonic Gallery</em> button:<br/><br/>
			<img src='".PHOTONIC_URL."screenshot-2.jpg' style='max-width: 300px;' alt='Add / Edit Button'/><br/><br/>
			You will be presented with the following:<br/><br/>
			<img src='".PHOTONIC_URL."screenshot-3.png' style='max-width: 500px;' alt='Wizard' />",
				'grouping' => 'generic-how-to',
				'type' => 'blurb',],

			['name' => "Generic settings",
				'desc' => "Control generic settings for the plugin",
				'category' => 'generic-settings',
				'type' => 'section',],

			['name' => "Custom Shortcode",
				'desc' => "If you are using the <strong><em>Classic Editor</em></strong> by default Photonic uses the <code>gallery</code> shortcode, 
			so that your galleries stay safe if you stop using Photonic.
			But your theme or other plugins might be using the same shortcode too. In such a case define an explicit shortcode,
			and only this shortcode will show Photonic galleries",
				'id' => "alternative_shortcode",
				'grouping' => 'generic-settings',
				'type' => 'text'],

			['name' => "Inbuilt Lightbox libraries",
				'desc' => "Photonic lets you choose from the following JS libraries for Lightbox effects:",
				'id' => "slideshow_library",
				'grouping' => 'generic-settings',
				'type' => 'radio',
				'options' => [
					"colorbox" => "<a href='http://colorpowered.com/colorbox/'>Colorbox</a> &ndash; ~10KB JS, ~5KB CSS: Released under the MIT license.",
					"fancybox" => "<a href='http://fancybox.net/'>FancyBox</a> &ndash; ~16KB JS, ~9KB CSS: Released under MIT and GPL licenses.",
					"fancybox3" => "<a href='https://fancyapps.com/fancybox/3/'>FancyBox 3</a> &ndash; ~61KB JS, ~14KB CSS: Released under the GPL v3 license.",
					"featherlight" => "<a href='http://noelboss.github.io/featherlight/'>Featherlight</a> &ndash; ~12KB JS, ~4KB CSS: Released under the MIT license.",
//			"fluidbox" => "<a href='https://terrymun.github.io/Fluidbox/'>Fluidbox</a> &ndash; ~10KB JS, ~4KB CSS: Released under the MIT license.",
//			"galleria" => "<a href='https://galleria.io/'>Galleria</a> &ndash; ~72KB JS (+ Theme), ~5KB CSS: Released under the MIT license.",
					"imagelightbox" => "<a href='https://osvaldas.info/image-lightbox-responsive-touch-friendly'>Image Lightbox</a> &ndash; ~6KB JS, ~5KB CSS: Released under the MIT license. No video support.",
					"lightcase" => "<a href='http://cornel.bopp-art.com/lightcase/'>LightCase</a> &ndash; ~25KB JS, ~16KB CSS: Released under the GPL license.",
					"lightgallery" => "<a href='https://sachinchoolur.github.io/lightGallery/'>Lightgallery</a> &ndash; ~18KB JS (+ Optional plugins), ~20KB CSS, ~20KB fonts: Released under the GPL v3 license.",
					"magnific" => "<a href='http://dimsemenov.com/plugins/magnific-popup/'>Magnific Popup</a> &ndash; ~20KB JS, ~7KB CSS: Released under the MIT license.",
					"photoswipe" => "<a href='http://photoswipe.com/'>PhotoSwipe</a> &ndash; ~42KB JS, ~4KB CSS: Released under the MIT license. No video support for Flickr.",
					"prettyphoto" => "<a href='http://www.no-margin-for-errors.com/projects/prettyphoto-jquery-lightbox-clone/'>PrettyPhoto</a> &ndash; ~23KB JS, ~27KB CSS: Released under the GPL v2.0 license. YouTube and Vimeo supported, but no support for videos from Flickr etc.",
					"swipebox" => "<a href='http://brutaldesign.github.io/swipebox/'>Swipebox</a> &ndash; ~12KB, ~5KB CSS: Released under the MIT license.",
					"thickbox" => "Thickbox &ndash; ~12KB: Released under the MIT license. No video support.",
					"none" => "None",
					"custom" => "Non-bundled &nbsp; You have to provide the JS and CSS links. See <a href='https://aquoid.com/plugins/photonic/third-party-lightboxes/'>here</a> for instructions",
				]],

			['name' => "Non-bundled Lightbox libraries",
				'desc' => "If you don't like the above libraries, you can try one of the following. These are not distributed with the theme for various reasons,
			predominant being licensing restrictions. <strong>Photonic doesn't support installation of these scripts</strong>. If you want to use them,
			you will need to specify their JS and CSS files in subsequent options, unless they come bundled with your theme.",
				'id' => "custom_lightbox",
				'grouping' => 'generic-settings',
				'type' => 'radio',
				'options' => [
					"fancybox2" => "<a href='http://fancyapps.com/fancybox/'>FancyBox 2</a>: Released under the CC-BY-NC 3.0 license",
					"strip" => "<a href='http://www.stripjs.com/'>Strip</a>: Released under the CC-BY-NC-ND 3.0 license. YouTube and Vimeo supported, but no support for videos from Flickr etc.",
				]],

			['name' => "Non-bundled Lightbox JS",
				'desc' => "If you have chosen a custom lightbox library from the above, enter the full URLs of the JS files for each of them.
			<strong>Please enter one URL per line</strong>. Note that your URL should start with <code>http://...</code> and you should be able to visit that entry in a browser",
				'id' => "custom_lightbox_js",
				'grouping' => 'generic-settings',
				'type' => 'textarea'],

			['name' => "Custom Lightbox CSS",
				'desc' => "If you have chosen a custom lightbox library from the above, enter the full URLs of the CSS files for each of them.
			<strong>Please enter one URL per line</strong>. Note that your URL should start with <code>http://...</code> and you should be able to visit that entry in a browser",
				'id' => "custom_lightbox_css",
				'grouping' => 'generic-settings',
				'type' => 'textarea'],

			['name' => "Don't include third-party lightbox scripts",
				'desc' => "If your theme or another plugin is supplying a lightbox script from the list above, you have the option to disable loading the same script from Photonic. <strong>This will save you some bandwidth, but you will have to work with the support for your theme or the other plugin to resolve issues.</strong>",
				'id' => "disable_photonic_lightbox_scripts",
				'grouping' => 'generic-settings',
				'type' => 'checkbox'
			],

			['name' => "Don't include third-party slider scripts",
				'desc' => "If your theme or another plugin is supplying the <a href='https://sachinchoolur.github.io/lightslider/'>LightSlider script</a> (used for slideshow layouts), you have the option to disable loading the same script from Photonic. <strong>This will save you some bandwidth, but you will have to work with the support for your theme or the other plugin to resolve issues.</strong>",
				'id' => "disable_photonic_slider_scripts",
				'grouping' => 'generic-settings',
				'type' => 'checkbox'
			],

			['name' => "Photonic Lightbox for non-Photonic Images",
				'desc' => "Selecting this will let you use Photonic's lightbox for non-Photonic images. This eliminates the need for a separate lightbox plugin.",
				'id' => "lightbox_for_all",
				'grouping' => 'generic-settings',
				'type' => 'checkbox'
			],

			['name' => "Photonic Lightbox for non-Photonic videos (YouTube / Vimeo etc.)",
				'desc' => "Selecting this will let you use Photonic's lightbox for YouTube / Vimeo or self-hosted videos. This eliminates the need for a separate lightbox plugin.",
				'id' => "lightbox_for_videos",
				'grouping' => 'generic-settings',
				'type' => 'checkbox'
			],

			['name' => "Include Photonic JS for non-Photonic Images / Videos",
				'desc' => "By default Photonic's JavaScript is only loaded on pages that have Photonic galleries. This will cause issues on pages that have no Photonic galleries but are using Photonic for non-Photonic images. By checking this option you will be including the JS on all pages regardless of whether they have Photonic galleries. 
			Alternatively if you don't want to load the scripts on all pages, you can create a blank shortcode at the top of your post with the photos this way:
			<ol>
				<li>If you are using the <code>gallery</code> shortcode put in <code>[gallery style='square']</code></li>
				<li>If you are using a custom shortcode from the first option on this page, e.g. <code>photonic</code> put in <code>[photonic]</code></li>
			</ol>
			Creating a blank shortcode will ensure that this page will get the scripts, and will not load the script on other pages.",
				'id' => "always_load_scripts",
				'grouping' => 'generic-settings',
				'type' => 'checkbox'
			],

			['name' => "Force JS in header when possible",
				'desc' => "By default Photonic's JavaScript is loaded in the footer. For themes including RetinaJS this causes a conflict due to a <a href='https://github.com/strues/retinajs/issues/260'>bug in RetinaJS</a>. Selecting this option addresses this bug, <strong>however this requires the previous option (<em>Include Photonic JS for non-Photonic Images / Videos</em>) to be selected</strong>.",
				'id' => "js_in_header",
				'grouping' => 'generic-settings',
				'type' => 'checkbox'
			],

			['name' => "Disable shortcode editing in Visual Editor",
				'desc' => "Occasionally the shortcode editor might cause JavaScript conflicts with other plugins. If that happens, select this option. Note that even if this option is selected, <strong>you will see a \"No items found\" message in the visual editor. Your gallery will still work, and you can edit the shortcode via the \"Text Editor\"</strong>.",
				'id' => "disable_editor",
				'grouping' => 'generic-settings',
				'type' => 'checkbox'
			],

			['name' => "Disable Visual Editing for specific post types",
				'desc' => "If you have disabled the Visual Editor in the previous option, you can selectively disable the editor for specific post types. <strong>Not selecting anything will keep it disabled for all post types.</strong> Note that you can still edit the shortcode via the \"Text Editor\".",
				'id' => "disable_editor_post_type",
				'grouping' => 'generic-settings',
				'options' => Utilities::get_formatted_post_type_array(),
				'type' => "multi-select"
			],

			['name' => "Use traditional interface for editing in Visual Editor",
				'desc' => 'If shortcode editing in the visual editor is permitted (globally or for a post type in the above options), this option will show you a flat list of all attributes instead of a wizard for building your gallery.',
				'id' => "disable_flow_editor",
				'grouping' => 'generic-settings',
				'type' => 'checkbox'
			],

			['name' => "Globally turn off Gallery Wizard",
				'desc' => 'If selected, the only way to add galleries will be via <em>Add Media &rarr; Photonic</em>.',
				'id' => "disable_flow_editor_global",
				'grouping' => 'generic-settings',
				'type' => 'checkbox'
			],

			['name' => "Nested Shortcodes in parameters",
				'desc' => "Allow parameters of the gallery shortcode to use shortcodes themselves",
				'id' => "nested_shortcodes",
				'grouping' => 'generic-settings',
				'type' => 'checkbox'
			],

			['name' => "External Link Handling",
				'desc' => "Let the links to external sites (like Flickr or Instagram) open in a new tab/window.",
				'id' => "external_links_in_new_tab",
				'grouping' => 'generic-settings',
				'type' => 'checkbox'
			],

			['name' => "Custom CSS in its own file",
				'desc' => "When selected, Photonic will try to save the custom CSS generated through options to a file, <code>".trailingslashit(PHOTONIC_UPLOAD_DIR)."custom-styles.css</code>. You can use that file for caching.",
				'id' => "css_in_file",
				'grouping' => 'generic-settings',
				'type' => 'checkbox'
			],

			['name' => "Layouts",
				'desc' => "Set up your layouts",
				'category' => "layout-settings",
				'type' => 'section',],

			['name' => "Image layout",
				'desc' => "If no gallery layout is specified, the following selection will be used:",
				'id' => "thumbnail_style",
				'grouping' => "layout-settings",
				'type' => 'select',
				'options' => Utilities::layout_options(),
				'hint' => 'The first four options trigger a slideshow, the rest trigger a lightbox.'
			],

			['name' => "Square / Circle Grid - Thumbnail Effect",
				'desc' => "The following effect will be used for thumbnails in a square or circular thumbnail grid",
				'id' => "standard_thumbnail_effect",
				'grouping' => "layout-settings",
				'type' => 'radio',
				'options' => [
					'none' => 'Thumbnails will be displayed as they are',
					'opacity' => 'Thumbnails will show up opaque, opacity will clear upon hovering',
					'zoom' => 'Thumbnails will zoom in upon hovering - will not work for square thumbs with title shown below image, and for any circular thumbs',
				],
			],

			['name' => "Random Justified Gallery - Padding",
				'desc' => "How much spacing do you want around each photo? This is only applicable to <strong>Random Justified Galleries</strong>. The gap between two photos is double this value.",
				'id' => "tile_spacing",
				'grouping' => "layout-settings",
				'type' => 'text',
				'hint' => "Enter the number of pixels here (don't enter 'px').",
			],

			['name' => "Random Justified Gallery - Minimum Tile height",
				'desc' => "What is the minimum height in pixels you want to make your random tiles? By default Photonic tries to assign a height that is 1/4 of the browser window, or 200px, whichever is greater. This is only applicable to <strong>Random Tiled Galleries</strong>. Use lower values if your content window is narrow.",
				'id' => "tile_min_height",
				'grouping' => "layout-settings",
				'type' => 'text',
				'hint' => "Enter the number of pixels here (don't enter 'px').",
			],

			['name' => "Random Justified Gallery - Thumbnail Effect",
				'desc' => "The following effect will be used for tiles in a Random Justified Gallery",
				'id' => "justified_thumbnail_effect",
				'grouping' => "layout-settings",
				'type' => 'radio',
				'options' => [
					'none' => 'Tiles will be displayed as they are',
					'opacity' => 'Tiles will show up opaque, opacity will clear upon hovering',
					'zoom' => 'Tiles will zoom in upon hovering',
				],
			],

			['name' => "Masonry Layout - Padding",
				'desc' => "How much spacing do you want around each photo? This is only applicable to <strong>Masonry layouts</strong>. The gap between two photos is double this value.",
				'id' => "masonry_tile_spacing",
				'grouping' => "layout-settings",
				'type' => 'text',
				'hint' => "Enter the number of pixels here (don't enter 'px').",
			],

			['name' => "Masonry Layout - Minimum Column Width",
				'desc' => "What is the minimum width in pixels you want to make your columns in the <strong>Masonry</strong> layout? This drives responsive design.",
				'id' => "masonry_min_width",
				'grouping' => "layout-settings",
				'type' => 'text',
				'hint' => "Enter the number of pixels here (don't enter 'px').",
			],

			['name' => "Masonry Layout - Thumbnail Effect",
				'desc' => "The following effect will be used for tiles in a Masonry Layout",
				'id' => "masonry_thumbnail_effect",
				'grouping' => "layout-settings",
				'type' => 'radio',
				'options' => [
					'none' => 'Tiles will be displayed as they are',
					'opacity' => 'Tiles will show up opaque, opacity will clear upon hovering',
					'zoom' => 'Tiles will zoom in upon hovering (will not work if titles are displayed below the tile)',
				],
			],

			['name' => "Mosaic Layout - Padding",
				'desc' => "How much spacing do you want around each photo? This is only applicable to <strong>Mosaic layouts</strong>. The gap between two photos is double this value.",
				'id' => "mosaic_tile_spacing",
				'grouping' => "layout-settings",
				'type' => 'text',
				'hint' => "Enter the number of pixels here (don't enter 'px'). Set to &gt; 0 to avoid rounding errors in the layout.",
			],

			['name' => "Mosaic Layout - Trigger width",
				'desc' => "If your content is narrow, you might not want too many images in a row for the <strong>Mosaic Layout</strong>. The Trigger Width controls this behaviour. If your content is 600px wide, and you set the Trigger width to 150, Photonic will not try to fit more than 4 (= 600/150) tiles in a mosaic row.",
				'id' => "mosaic_trigger_width",
				'grouping' => "layout-settings",
				'type' => 'text',
				'hint' => "Enter the number of pixels here (don't enter 'px').",
			],

			['name' => "Mosaic Layout - Thumbnail Effect",
				'desc' => "The following effect will be used for tiles in a Mosaic layout",
				'id' => "mosaic_thumbnail_effect",
				'grouping' => "layout-settings",
				'type' => 'radio',
				'options' => [
					'none' => 'Tiles will be displayed as they are',
					'opacity' => 'Tiles will show up opaque, opacity will clear upon hovering',
					'zoom' => 'Tiles will zoom in upon hovering',
				],
			],

			['name' => "Native WP Galleries",
				'desc' => "Control settings for native WP gallieries, invoked by <code>[gallery id='abc']</code>",
				'category' => "wp-settings",
				'type' => 'section',],

			['name' => "Photo titles and captions",
				'desc' => "What do you want to show as the photo title in the tooltip and lightbox?",
				'id' => "wp_title_caption",
				'grouping' => "wp-settings",
				'type' => 'select',
				'options' => Utilities::title_caption_options(),],

			['name' => "Thumbnail Title Display",
				'desc' => "How do you want the title of the Thumbnails displayed?",
				'id' => "wp_thumbnail_title_display",
				'grouping' => "wp-settings",
				'type' => 'radio',
				'options' => $this->title_styles()],

			['name' => "Disable lightbox linking",
				'desc' => "Check this to disable linking the photo title in the lightbox to the original photo page on your site.",
				'id' => "wp_disable_title_link",
				'grouping' => "wp-settings",
				'type' => 'checkbox'],

			['name' => "Slideshow settings",
				'desc' => "Control settings for the slideshow layout",
				'category' => "sshow-settings",
				'type' => 'section',],

			['name' => "Prevent Slideshow Autostart",
				'desc' => "By default slideshows start playing automatically. Selecting this will prevent this behaviour.",
				'id' => "slideshow_prevent_autostart",
				'grouping' => "sshow-settings",
				'type' => 'checkbox',
			],

			['name' => "Centre slideshow images",
				'desc' => "If you pass the <code>layout</code> parameter (<code>style</code> for native WP galleries) to the <code>gallery</code> shortcode and the style is <code>strip-above</code>, <code>strip-below</code> or <code>no-strip</code> the image in the slide will be centered if you select this.",
				'id' => "wp_slide_align",
				'grouping' => "sshow-settings",
				'type' => 'checkbox',
			],

			['name' => "Slideshow Image Adjustment",
				'desc' => "If you are displaying a slideshow and your images are of uneven sizes, how do you want to handle the size differences?",
				'id' => "wp_slide_adjustment",
				'grouping' => "sshow-settings",
				'type' => 'select',
				'options' => [
					'side-white' => 'Show whitespace to the side for narrower images',
					'start-next' => 'Start next image to cover whitespace for narrower images',
					'adapt-height' => 'Dynamically change slideshow height according to the image size',
					'adapt-height-width' => 'Dynamically change slideshow height and stretch images',
				]],

			['name' => "Overlaid Popup Panel",
				'desc' => "Control settings for popup panel",
				'category' => "photos-pop",
				'type' => 'section',],

			['name' => "What is this section?",
				'desc' => "Options in this section are in effect when you click on a Photoset/album thumbnail to launch an overlaid gallery.",
				'grouping' => "photos-pop",
				'type' => "blurb",],

			['name' => "Enable Interim Popup for Album Thumbnails",
				'desc' => "When you click on an Album / Photoset / Gallery, the lightbox automatically starts showing the images in the album. You can, instead, show an interim popup with all thumbnails for that album, then launch the lightbox upon clicking a thumbnail. This same setting is controlled from the \"Photo Template\" section as well.",
				'id' => "enable_popup",
				'grouping' => "photos-pop",
				'type' => 'select',
				'options' => [
					'' => esc_html__('No overlay - start showing photos in a lightbox', 'photonic'),
					'show' => esc_html__('Overlay - show photos in an overlay first', 'photonic'),
					'page' => esc_html__('Page - Show photos in a separate page', 'photonic'),
				],
			],

			['name' => "Overlaid (popup) Gallery Panel Width",
				'desc' => "When you click on a gallery, it can launch a panel on top of your page. What is the width, <b>in percentage</b>, you want to assign to this gallery?",
				'id' => "popup_panel_width",
				'grouping' => "photos-pop",
				'type' => 'select',
				'options' => $this->selection_range(1, 100)],


			['name' => "Overlaid (popup) Gallery Panel background",
				'desc' => "Setup the background of the overlaid gallery (popup).",
				'id' => "flickr_gallery_panel_background",
				'grouping' => "photos-pop",
				'type' => "background",
				'options' => [],
			],

			['name' => "Overlaid (popup) Gallery Border",
				'desc' => "Setup the border of overlaid gallery (popup).",
				'id' => "flickr_set_popup_thumb_border",
				'grouping' => "photos-pop",
				'type' => 'border',
				'options' => [],
			],

			['name' => "Photo Template",
				'desc' => "Let Photonic use a standalone page as a template",
				'category' => "template-page",
				'type' => 'section',],

			['name' => "Enable separate page to show album photos",
				'desc' => "When you click on an Album / Photoset / Gallery, the lightbox automatically starts showing the images in the album. You can, instead, show a separate page with all thumbnails for that album, then launch the lightbox upon clicking a thumbnail. This same setting is controlled from the \"Overlaid Popup Panel\" section as well.",
				'id' => "enable_popup",
				'grouping' => "template-page",
				'type' => 'select',
				'options' => [
					'' => esc_html__('No overlay - start showing photos in a lightbox', 'photonic'),
					'show' => esc_html__('Overlay - show photos in an overlay first', 'photonic'),
					'page' => esc_html__('Page - Show photos in a separate page', 'photonic'),
				],
			],

			['name' => 'Dedicated page to use for gallery display',
				'desc' => "By default Photonic displays galleries on the page it is invoked. Upon clicking on album thumbnails you are shown the photos of that album either in an overlaid panel, or in a lightbox. 
					Upon setting this, you can redirect users to a dedicated page that shows the contents of an album. To use this, the gallery attribute <code>popup</code> should be set to show in a dedicated page, i.e. <code>popup='page'</code>",
				'id' => 'gallery_template_page',
				'grouping' => "template-page",
				'type' => 'select',
				'options' => Utilities::get_pages()
			],

			['name' => 'Page title display',
				'desc' => "Set the title to be displayed on the gallery page",
				'id' => 'page_title',
				'grouping' => "template-page",
				'type' => 'select',
				'options' => [
					'replace-if-available' => "Show album title if available, otherwise show the title of the WordPress page",
					'page' => "Show the title of the WordPress page",
				]
			],

			['name' => 'Page content display',
				'desc' => "Set the content to be displayed on the gallery page",
				'id' => 'page_content',
				'grouping' => "template-page",
				'type' => 'select',
				'options' => [
					'replace-if-available' => "Show album description if available, otherwise show the content of the WordPress page",
					'append-if-available' => "Append album description if available, otherwise show the content of the WordPress page",
					'page' => "Show the content of the WordPress page",
				]
			],

			['name' => "Advanced",
				'desc' => "Control advanced settings for the plugin",
				'category' => "advanced-settings",
				'type' => 'section',],

			['name' => 'Turn off SSL verification in calls',
				'desc' => "When selected, Photonic will not use SSL verification for secure calls. <strong>This is not recommended, and may only be used on development sites</strong>.",
				'id' => 'ssl_verify_off',
				'grouping' => "advanced-settings",
				'type' => 'checkbox',
			],

			['name' => 'Increase cURL timeout',
				'desc' => "By default cURL requests made by WordPress time out after 10 seconds. In some cases your hosting provider might be throttling the connection speed to external services such as Flickr. In such a case bump up the timeout to something like 30 in the option below.",
				'id' => "curl_timeout",
				'grouping' => "advanced-settings",
				'type' => 'text',
			],

			['name' => 'Script Dev Mode',
				'desc' => "By default Photonic loads minified versions of scripts. Select this option to load the full versions. This might help troubleshooting, or you may require this to play nice with minificaiton plugins.",
				'id' => 'script_dev_mode',
				'grouping' => "advanced-settings",
				'type' => 'checkbox',
			],

			['name' => 'Performance logging',
				'desc' => "When selected, Photonic will log performance statistics for various operations. This is useful for fine-tuning. Stats are printed as HTML comments under each gallery, invisible on the front-end.",
				'id' => 'performance_logging',
				'grouping' => "advanced-settings",
				'type' => 'checkbox',
			],

			['name' => 'Turn on debug logging',
				'desc' => "Turning this on helps troubleshoot error messages. <strong>This is not recommended, and may only be used on development sites</strong>.",
				'id' => 'debug_on',
				'grouping' => "advanced-settings",
				'type' => 'checkbox',
			],

		];
	}

	public static function get_instance() {
		if (self::$instance == null) {
			self::$instance = new Generic();
		}
		return self::$instance;
	}
}
