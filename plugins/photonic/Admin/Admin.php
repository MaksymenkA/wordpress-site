<?php
namespace Photonic_Plugin\Admin;

use Photonic_Plugin\Admin\Wizard\Wizard;
use Photonic_Plugin\Core\Photonic;
use Photonic_Plugin\Modules\SmugMug;

class Admin {
	function __construct() {
		global $photonic_disable_flow_editor_global;

		// General
		add_action('admin_head', [$this, 'admin_head']);
		add_action('admin_enqueue_scripts', [&$this, 'add_admin_scripts']);

		// Vanilla Editor
		add_filter('media_upload_tabs', [&$this, 'media_upload_tabs']);
		add_action('media_upload_photonic', [&$this, 'media_upload_photonic']);

		add_action('print_media_templates', [&$this, 'edit_gallery']);

		// Gutenberg
		add_action('admin_notices', [&$this, 'admin_notices']);
		add_action('enqueue_block_editor_assets', [&$this, 'enqueue_gutenberg_assets']);

		if (empty($photonic_disable_flow_editor_global)) {
			add_action('media_buttons', [&$this, 'add_photonic_button']);
			add_action('admin_action_photonic_wizard', [&$this, 'open_wizard']);
			add_action('wp_ajax_photonic_wizard_next_screen', [&$this, 'wizard_next_screen']);
			add_action('wp_ajax_nopriv_photonic_wizard_next_screen', [&$this, 'wizard_next_screen']);
			add_action('wp_ajax_photonic_wizard_more', [&$this, 'flow_more']);
			add_action('wp_ajax_nopriv_photonic_wizard_more', [&$this, 'flow_more']);
		}
	}

	function admin_head() {
		// check user permissions
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
			return;
		}

		global $photonic_disable_editor, $photonic_disable_editor_post_type;
		$disabled_types = explode(',', $photonic_disable_editor_post_type);
		$screen = get_current_screen();
		$post_type = empty($_REQUEST['post_type']) ? 'post' : $_REQUEST['post_type'];
		// check if WYSIWYG is enabled
		if ('true' == get_user_option('rich_editing') && empty($photonic_disable_editor) && !in_array($post_type, $disabled_types) && $screen->base == 'post') {
			$this->prepare_mce_data();
			add_filter('mce_external_plugins', [$this ,'mce_photonic'], 5);
			add_filter('mce_buttons', [$this ,'mce_flow_button'], 5);
		}
	}

	function add_photonic_button() {
		add_thickbox();
		$url = $this->get_wizard_modal_url();

		printf('<a href="%1$s" class="button photonic-button thickbox" id="photonic-add-gallery" title="Photonic Gallery"><img class="wp-media-buttons-icon" src="'.PHOTONIC_URL.'include/images/Photonic-20.png'.'" alt="%2$s" /> %2$s</a>',
			$url, esc_html__( 'Add / Edit Photonic Gallery', 'photonic'));
	}

	function open_wizard() {
		define( 'IFRAME_REQUEST', true );
		$this->enqueue_wizard_scripts();
		iframe_header(esc_html__('Add / Edit Photonic Gallery', 'photonic'));
		require_once(PHOTONIC_PATH.'/Admin/Wizard/Screen_Flow.php');
		iframe_footer();
		exit;
	}

	function enqueue_wizard_scripts() {
		global $photonic_alternative_shortcode;
		$wizard_js = [
			'ajaxurl' => admin_url('admin-ajax.php'),
			'shortcode' => empty($photonic_alternative_shortcode) ? 'gallery' : $photonic_alternative_shortcode,
			'insert_gallery' => esc_html__('Insert Gallery', 'photonic'),
			'update_gallery' => esc_html__('Update Gallery', 'photonic'),
			'error_mandatory' => esc_html__('Please fill the mandatory fields. Mandatory fields are marked with a red "*".', 'photonic'),
			'media_library_title' => esc_html__('Select from WordPress Media Library', 'photonic'),
			'media_library_button' => esc_html__('Select', 'photonic'),
			'info_editor_not_shortcode' => esc_html__('The text selected in the editor is not a Photonic shortcode. Creating a new shortcode.', 'photonic'),
			'info_editor_block_select' => sprintf(esc_html__('%1$sHint:%2$s To edit an existing Photonic block simply click on the block.', 'photonic'), '<strong>', '</strong>'),
		];
		if (!empty($_REQUEST['shortcode'])) {
			$wizard_js['shortcode'] = $_REQUEST['shortcode'];
		}
		wp_enqueue_style('photonic-flow', PHOTONIC_URL.'include/css/admin/admin-flow.css', [], Photonic::get_version(PHOTONIC_PATH.'/include/css/admin/admin-flow.css'));
		wp_enqueue_script('photonic-flow-js', PHOTONIC_URL.'include/scripts/admin/flow.js', ['jquery'], Photonic::get_version(PHOTONIC_PATH.'/include/scripts/admin/flow.js'));
		wp_localize_script('photonic-flow-js', 'Photonic_Wizard_JS', $wizard_js);
	}

	function wizard_next_screen() {
		require_once(PHOTONIC_PATH."/Admin/Wizard/Wizard.php");
		if (isset($_POST['provider'])) {
			$wizard = new Wizard();
			echo $wizard->get_screen();
		}
		die();
	}

	function flow_more() {
		require_once(PHOTONIC_PATH."/Admin/Wizard/Wizard.php");
		if (isset($_POST['url']) && isset($_POST['provider']) && isset($_POST['display_type'])) {
			$url = base64_decode(sanitize_text_field($_POST['url']));

			$provider = sanitize_text_field($_POST['provider']);
			$display_type = sanitize_text_field($_POST['display_type']);
			$args = ['sslverify' => PHOTONIC_SSL_VERIFY];
			if ($provider == 'smugmug') {
				require_once(PHOTONIC_PATH."/Modules/SmugMug.php");
				$gallery = SmugMug::get_instance();

				$body = [
					'APIKey' => $gallery->api_key,
					'_accept' => 'application/json',
					'_expandmethod' => 'inline',
					'_verbosity' => '1',
				];

				if ($display_type == 'album-photo' || $display_type == 'multi-album') {
					$body['_expand'] = 'HighlightImage.ImageSizes';
				}

				$args['body'] = $body;
			}

			$response = wp_remote_request($url, $args);
			$wizard = new Wizard();
			$objects = $wizard->process_response($response, $provider, $display_type, [], [], $url, true);

			if (!empty($objects['success'])) {
				echo $objects['success'];
			}
			else if (!empty($objects['error'])) {
				echo $objects['error'];
			}
		}
		die();
	}

	/**
	 * Adds all scripts and their dependencies to the <head> of the Photonic administration page. This takes care to not add scripts on other admin pages.
	 *
	 * @param $hook
	 * @return void
	 */
	function add_admin_scripts($hook) {
		if ('media-upload-popup' == $hook) {
			wp_enqueue_script('jquery');
			wp_enqueue_style('photonic-upload', PHOTONIC_URL.'include/css/admin/admin-form.css', [], Photonic::get_version(PHOTONIC_PATH.'/include/css/admin/admin-form.css'));
		}
		else if ('post-new.php' == $hook || 'post.php' == $hook) {
			global $photonic_disable_editor, $photonic_disable_editor_post_type;
			$disabled_types = explode(',', $photonic_disable_editor_post_type);
			$post_type = empty($_REQUEST['post_type']) ? 'post' : $_REQUEST['post_type'];
			wp_enqueue_style('photonic-upload', PHOTONIC_URL.'include/css/admin/admin-form.css', [], Photonic::get_version(PHOTONIC_PATH.'/include/css/admin/admin-form.css'));
			if (empty($photonic_disable_editor) && !in_array($post_type, $disabled_types)) {
				$this->prepare_mce_data();

				add_editor_style(PHOTONIC_URL.'include/css/admin/admin-editor.css?'.Photonic::get_version(PHOTONIC_PATH.'/include/css/admin/admin-editor.css'));
			}
		}
		else if ('widgets.php' == $hook) {
			Photonic::enqueue_widget_scripts();
		}
	}

	function prepare_mce_data() {
		$url = $this->get_wizard_modal_url();
		$js_array = $this->get_wizard_js_parameters($url);

		wp_enqueue_script('photonic-admin-js', PHOTONIC_URL.'include/scripts/admin/gallery-settings.js', ['jquery', 'media-views', 'media-upload'], Photonic::get_version(PHOTONIC_PATH.'/include/scripts/admin/gallery-settings.js'));
		wp_localize_script('photonic-admin-js', 'Photonic_Admin_JS', $js_array);
	}

	function mce_photonic($plugin_array) {
		$plugin_array['photonic'] = PHOTONIC_URL.'include/scripts/admin/mce.js?'.Photonic::get_version(PHOTONIC_PATH.'/include/scripts/admin/mce.js');
		return $plugin_array;
	}

	function mce_flow_button($buttons) {
		array_push($buttons, 'photonic_wizard');
		return $buttons;
	}

	function enqueue_gutenberg_assets() {
		if (function_exists('register_block_type')) {
			wp_enqueue_script('photonic-gutenberg',
				PHOTONIC_URL.'include/scripts/admin/block.js',
				['jquery', 'wp-blocks', 'wp-i18n', 'wp-element', 'shortcode', 'thickbox'],
				Photonic::get_version(PHOTONIC_PATH.'/include/scripts/admin/block.js')
			);

			if (function_exists( 'gutenberg_get_jed_locale_data')) {
				$locale  = gutenberg_get_jed_locale_data('photonic');
				$content = 'wp.i18n.setLocaleData('.json_encode($locale).', "photonic");';
				wp_script_add_data( 'photonic-gutenberg', 'data', $content );
			}

			$url = $this->get_wizard_modal_url();
			$js_array = $this->get_wizard_js_parameters($url);
			wp_localize_script('photonic-gutenberg', 'Photonic_Gutenberg_JS', $js_array);

			wp_enqueue_style('photonic-gutenberg',
				PHOTONIC_URL.'include/css/admin/admin-block.css',
				['thickbox'],
				Photonic::get_version(PHOTONIC_PATH.'/include/css/admin/admin-block.css')
			);
		}
	}

	/**
	 * Adds a "Photonic" tab to the "Add Media" panel.
	 *
	 * @param $tabs
	 * @return array
	 */
	function media_upload_tabs($tabs) {
		if (!function_exists('is_gutenberg_page') || (function_exists('is_gutenberg_page') && !is_gutenberg_page())) {
			$tabs['photonic'] = 'Photonic';
		}
		return $tabs;
	}

	/**
	 * Invokes the form to display the photonic insertion screen in the "Add Media" panel. The call to wp_iframe ensures that the right CSS and JS are called.
	 *
	 * @return void
	 */
	function media_upload_photonic() {
		wp_iframe([&$this, 'media_upload_photonic_form']);
	}

	/**
	 * First prints the standard buttons for media upload, then shows the UI for Photonic.
	 *
	 * @return void
	 */
	function media_upload_photonic_form() {
		media_upload_header();
		require_once(PHOTONIC_PATH."/Admin/Forms/Add_Gallery.php");
	}

	function edit_gallery() {
		global $photonic_disable_editor, $photonic_disable_editor_post_type;
		$disabled_types = explode(',', $photonic_disable_editor_post_type);
		$post_type = empty($_REQUEST['post_type']) ? 'post' : $_REQUEST['post_type'];
		// check if WYSIWYG is enabled
		if ('true' == get_user_option('rich_editing') && empty($photonic_disable_editor) && !in_array($post_type, $disabled_types)) {
			require_once(PHOTONIC_PATH."/Admin/Forms/Edit_Gallery_Templates.php");
		}
	}

	/**
	 * @return string
	 */
	private function get_wizard_modal_url() {
		$url = add_query_arg([
			'action' => 'photonic_wizard',
			'class' => 'photonic-flow',
			'post_id' => empty($_REQUEST['post']) ? '' : $_REQUEST['post'],
			'width' => '1000',
			'height' => '600',
			'TB_iframe' => 'true',
		], admin_url('admin.php'));
		return $url;
	}

	/**
	 * @param string $url
	 * @return array
	 */
	private function get_wizard_js_parameters($url) {
		global $photonic_alternative_shortcode, $photonic_disable_flow_editor, $photonic_disable_flow_editor_global;
		$js_array = [
			'flow_url' => $url,
			'ajaxurl' => admin_url('admin-ajax.php'),
			'shortcode' => empty($photonic_alternative_shortcode) ? 'gallery' : $photonic_alternative_shortcode,
			'disable_flow' => !empty($photonic_disable_flow_editor) || !empty($photonic_disable_flow_editor_global),
			'default_gallery_type' => 'default',
			'plugin_dir' => plugin_dir_url(__FILE__),
		];
		return $js_array;
	}

	function admin_notices() {
		if (!empty($_REQUEST['page']) && in_array($_REQUEST['page'], ['photonic-options-manager', 'photonic-getting-started', 'photonic-auth', 'photonic-helpers'])){
			if (function_exists('register_block_type')) {
				$dismissed = get_user_meta(get_current_user_id(), 'photonic_gutenberg');
				if (!$dismissed) {
					echo "<div class='notice notice-warning'>\n<p>\n";
					echo sprintf(esc_html__('It looks like you are using the Gutenberg editor. Please ensure that you have followed the instructions under %s.', 'photonic'), '<em><a href="'.admin_url('admin.php?page=photonic-gutenberg').'">Photonic &rarr; Switch to Gutenberg</a></em>');
					echo "</p>\n";
					echo "<button type='button' data-photonic-dismissible='gutenberg' class='photonic-notice-dismiss'><span class='screen-reader-text'>Dismiss this notice.</span></button>";
					echo "</div>\n";
				}
			}
		}
	}
}

new Admin();