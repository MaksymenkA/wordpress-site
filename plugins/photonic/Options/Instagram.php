<?php
namespace Photonic_Plugin\Options;
use Photonic_Plugin\Core\Utilities;

class Instagram extends Option_Tab {
	private static $instance;

	private function __construct() {
		$this->options = [
			['name' => "Instagram settings",
				'desc' => "Control settings for Instagram",
				'category' => "instagram-settings",
				'type' => 'section',],

			['name' => "Instagram Access Token",
				'desc' => "Enter your Instagram Access Token. You can get this from <em>Photonic &rarr; Authentication</em> by clicking on <em>Login and get Access Token</em>",
				'id' => "instagram_access_token",
				'grouping' => "instagram-settings",
				'type' => 'text'],

			['name' => 'Media to show',
				'desc' => 'You can choose to include photos as well as videos in your output. This can be overridden by the <code>media</code> parameter in the shortcode:',
				'id' => "instagram_media",
				'grouping' => "instagram-settings",
				'type' => 'select',
				'options' => Utilities::media_options()],

			['name' => "Disable lightbox linking",
				'desc' => "Check this to disable linking the photo title in the lightbox to the original photo page.",
				'id' => "instagram_disable_title_link",
				'grouping' => "instagram-settings",
				'type' => 'checkbox'],

			['name' => "Title Display",
				'desc' => "How do you want the title of the photo thumbnail?",
				'id' => "instagram_photo_title_display",
				'grouping' => "instagram-settings",
				'type' => 'radio',
				'options' => $this->title_styles()],

			['name' => "Constrain Photos Per Row",
				'desc' => "How do you want the control the number of photo thumbnails per row by default? This can be overridden by adding the '<code>columns</code>' parameter to the '<code>gallery</code>' shortcode.",
				'id' => "instagram_photos_per_row_constraint",
				'grouping' => "instagram-settings",
				'type' => 'select',
				'options' => ["padding" => "Fix the padding around the thumbnails",
					"count" => "Fix the number of thumbnails per row",
				]],

			['name' => "Constrain by padding",
				'desc' => " If you have constrained by padding above, enter the number of pixels here to pad the thumbs by",
				'id' => "instagram_photos_constrain_by_padding",
				'grouping' => "instagram-settings",
				'type' => 'text',
				'hint' => "Enter the number of pixels here (don't enter 'px'). Non-integers will be ignored."],

			['name' => "Constrain by number of thumbnails",
				'desc' => " If you have constrained by number of thumbnails per row above, enter the number of thumbnails",
				'id' => "instagram_photos_constrain_by_count",
				'grouping' => "instagram-settings",
				'type' => 'select',
				'options' => $this->selection_range(1, 25)],
		];
	}

	public static function get_instance() {
		if (self::$instance == null) {
			self::$instance = new Instagram();
		}
		return self::$instance;
	}
}
