<?php
namespace Photonic_Plugin\Admin;

if (!defined('ABSPATH')) {
	echo '<h1>WordPress not loaded!</h1>';
	exit;
}

require_once('Admin_Page.php');

class Gutenberg extends Admin_Page {
	private static $instance;

	static function get_instance() {
		if (self::$instance == null) {
			self::$instance = new Gutenberg();
		}
		return self::$instance;
	}

	function render_content() {
		?>
		<form method="post" id="photonic-helper-form" name="photonic-helper-form">
			<div class="photonic-form-body fix">
				<h2 class="photonic-section">What is Gutenberg?</h2>
				<p>
					WordPress 5.0 introduced a new editor codenamed Gutenberg. Gutenberg features a different style of
					creating content, which introduces a concept called a <code>block</code>.
				</p>

				<h2 class="photonic-section">How does it impact Photonic on your site?</h2>
				<p>
					If you have configured Photonic not to use the standard <code>gallery</code> shortcode, and instead have
					a custom shortcode via <em>Photonic &rarr; Settings &rarr; Generic Options &rarr; Generic Settings &rarr; Custom Shortcode</em>,
					you are fine!
				</p>
				<p>
					<strong>But if you are using the <code>gallery</code> shortcode for Photonic and you click on
						"Convert to Blocks" using Gutenberg, your post will be in trouble!</strong> <span class="warning">All your instances of the
								<code>gallery</code> shortcode will be replaced by the native WordPress Gallery Block.</span>
				</p>
				<p>
					To avoid this you can find and replace all instances of the <code>gallery</code> shortcode
					used for Photonic and replace them with a custom shortcode of your choosing.
				</p>
				<div style="text-align: center">
					<img src="<?php echo PHOTONIC_URL.'include/images/Gutenberg.jpg'; ?>" alt="Gutenberg Flow" title="Gutenberg Flow"/>
				</div>

				<h2 class="photonic-section">Am I using the Gallery Shortcode with Photonic?</h2>
				<div id="photonic-shortcode-results">
					<?php
					require_once("Shortcode_Usage.php");
					$usage = new Shortcode_Usage();
					echo sprintf(esc_html__('%2$sThe following instances were found on your site for Photonic with the %4$s%1$s%5$s shortcode. %6$sPlease verify the instances below before replacing the shortcodes. It is strongly recommended to back up the posts listed below before the shortcode replacement.%7$s%3$s', 'photonic'),
						$usage->tag, '<p>', '</p>', '<code>', '</code>', '<strong>', '</strong>');
					$usage->prepare_items();
					$usage->display();
					?>
				</div>
			</div>
		</form>
		<?php
	}
}