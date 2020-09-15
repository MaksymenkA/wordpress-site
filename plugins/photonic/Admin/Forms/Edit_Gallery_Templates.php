<?php
namespace Photonic_Plugin\Admin\Forms;

class Edit_Gallery_Templates {
	private $fields, $providers;
	private static $instance = null;

	private function __construct() {
		require_once(PHOTONIC_PATH.'/Admin/Forms/Vanilla_Form.php');
		$form = Vanilla_Form::get_instance();
		$this->fields = $form->get_fields();

		$this->providers = ['default', 'flickr', 'google', 'smugmug', 'zenfolio', 'instagram'];
	}

	public static function get_instance() {
		if (self::$instance == null) {
			self::$instance = new Edit_Gallery_Templates();
		}
		return self::$instance;
	}

	function render() {
		foreach ($this->providers as $provider) {
			?>
			<script type="text/html" id="tmpl-photonic-editor-<?php echo $provider; ?>">
				<?php
				$field_list = $this->fields[$provider]['fields'];
				echo "<div class='photonic-form'>\n";
				echo "<h2>Photonic ".($provider == 'default' ? 'WP' : $provider)." Gallery Settings</h2>\n";
				foreach ($field_list as $field) {
					echo "\t<label class='setting'>\n";
					echo "\t\t<span class='label'>{$field['name']} " . (isset($field['req']) && $field['req'] ? '(*)' : '') . " </span>\n";
					$alt_id = !empty($field['alt_id']) ? " alt_id='{$field['alt_id']}' " : '';
					switch ($field['type']) {
						case 'text':
							echo "\t\t<input type='text' name='{$field['id']}' value='" . (isset($field['std']) ? $field['std'] : '') . "' $alt_id/>\n";
							break;

						case 'select':
							echo "\t\t<select name='{$field['id']}' $alt_id>\n";
							$default = isset($field['std']) ? $field['std'] : '';
							foreach ($field['options'] as $option_name => $option_value) {
								if ($option_name == $default) {
									$selected = 'selected';
								}
								else {
									$selected = '';
								}
								echo "\t\t\t<option value='$option_name' $selected>" . esc_attr($option_value) . "</option>\n";
							}
							echo "\t\t</select>\n";
							break;

						case 'raw':
							echo "\t\t" . $field['std'] . "\n";
							break;
					}
					echo "\t\t<span class='hint'>" . (isset($field['hint']) ? $field['hint'] : '') . "</span>\n";
					echo "\t</label>\n";
				}
				echo "</div>\n";
				?>
			</script>
			<?php
		}
	}
}

$edit_gallery = Edit_Gallery_Templates::get_instance();
$edit_gallery->render();
