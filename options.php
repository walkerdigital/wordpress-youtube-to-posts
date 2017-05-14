<?php
	defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
	function plugin_options_page() { ?>
	<div>
		<h2>YouTube to Posts</h2>
		<p>Provide the YouTube Channel URL</p>
		<form action="options.php" method="post">
		<?php settings_fields('plugin_options'); ?>
		<?php do_settings_sections('plugin'); ?>
		<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
		</form>
	</div>
<?php } ?>