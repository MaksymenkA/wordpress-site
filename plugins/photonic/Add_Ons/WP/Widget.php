<?php
namespace Photonic_Plugin\Add_Ons\WP;

use WP_Widget;

class Widget extends WP_Widget {
	private $empty_shortcode, $invalid_shortcode, $edit_shortcode;
	function __construct() {
		$widget_ops = ['classname' => 'widget-photonic',
			'description' => __("A widget for displaying a Photonic Gallery.", 'photonic')
		];

		$control_ops = [];
		$this->empty_shortcode = esc_html__('Click on the icon to start creating a new gallery.', 'photonic');
		$this->invalid_shortcode = esc_html__('The current saved data does not correspond to a Photonic gallery. A new one will be created.', 'photonic');
		$this->edit_shortcode = esc_html__('Click on the icon to edit your gallery.', 'photonic');

		parent::__construct("photonic-widget", __("Photonic Gallery", 'photonic'), $widget_ops, $control_ops);
	}

	function widget($args, $instance) {
		extract($args);

		$title = empty($instance['title']) ? '' : $instance['title'];
		$shortcode = empty($instance['shortcode']) ? '' : $instance['shortcode'];

		echo $before_widget;
		if ($title != '') {
			echo $before_title.$title.$after_title;
		}

		$output = do_shortcode($shortcode);
		echo $output;

		echo $after_widget;
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = esc_attr($new_instance['title']);
		$instance['shortcode'] = $new_instance['shortcode'];
		return $instance;
	}

	function form($instance) {
		global $photonic_alternative_shortcode;
		$tag = empty($photonic_alternative_shortcode) ? 'gallery' : $photonic_alternative_shortcode;

		$defaults = [
			'title' => '',
			'custom_class' => '',
			'shortcode' => ''
		];
		$instance = wp_parse_args((array)$instance, $defaults);

		add_thickbox();
		$url = add_query_arg([
			'action'    => 'photonic_wizard',
			'class'		=> 'photonic-flow',
			'post_id'   => '',
			'width'     => '1000',
			'height'    => '600',
			'TB_iframe' => 'true',
		], admin_url( 'admin.php' ) );

		$shortcode = $instance['shortcode'];
		$types = ['default', 'wp', 'flickr', 'smugmug', 'picasa', 'google', 'zenfolio', 'instagram'];
		$layouts = ['square', 'circle', 'random', 'masonry', 'mosaic', 'strip-above', 'strip-below', 'strip-right', 'no-strip'];

		$pattern = get_shortcode_regex([$tag]);
		preg_match_all('/' . $pattern . '/s', $shortcode, $matches, PREG_OFFSET_CAPTURE);
		$type = 'photonic';

		$message = $this->edit_shortcode;
		if (empty($shortcode)) {
			$message = $this->empty_shortcode;
		}
		else if (!empty($matches) && !empty($matches[0]) && !empty($matches[1]) && !empty($matches[2]) && !empty($matches[3])) {
			foreach ($matches[1] as $instance => $start) {
				if ($start[0] === '') {
					if (!empty($matches[3][$instance])) {
						$shortcode_attr = shortcode_parse_atts($matches[3][$instance][0]);
						if (!empty($shortcode_attr['type']) && in_array($shortcode_attr['type'], $types)) {
							$type = $shortcode_attr['type'];
						}
						else if (empty($shortcode_attr['type']) && !empty($shortcode_attr['style']) && in_array($shortcode_attr['style'], $layouts)) {
							$type = 'wp';
						}
						else {
							$message = $this->invalid_shortcode;
							$shortcode = '';
						}
					}
				}
			}
		}
		else {
			$message = $this->invalid_shortcode;
			$shortcode = '';
		}
		?>
		<div class="photonic-widget">
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'photonic'); ?></label>
				<input id="<?php echo $this->get_field_id('title'); ?>" value="<?php echo $instance['title']; ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" class="widefat" />
			</p>

			<input id="<?php echo $this->get_field_id('shortcode'); ?>" value="<?php echo $shortcode; ?>" type="hidden" name="<?php echo $this->get_field_name('shortcode'); ?>" class="photonic-shortcode"/>

			<div class="photonic-source">
				<a class="photonic-wizard <?php echo $type; ?>" href="<?php echo $url; ?>"></a>
				<p>
					<?php echo $message; ?>
				</p>
			</div>

			<div class="photonic-shortcode-display">
			<?php
			if ($shortcode !== '') {
				?>
				<h4><?php echo esc_html__('Current shortcode', 'photonic'); ?></h4>
				<code><?php echo $shortcode; ?></code>
				<?php
			}
			?>
			</div>
		</div>
		<?php
	}

}