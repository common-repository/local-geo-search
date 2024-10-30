<div>
	<h2>GEO SEO</h2>
	Log in and choose website

	<form action="options.php" method="post">

		<?php settings_fields('geo_seo_options'); ?>

		<?php do_settings_sections('geo_seo'); ?>

		<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
	</form>
</div>