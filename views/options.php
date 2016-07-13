
<div class="wrap">
	<div id="icon-options-deemtree" class="icon32"><br></div>
	<h2>Deemtree Settings
		<a href="http://www.deemtree.com/tour" class="deemtree-visit" target="_blank">Take a Tour</a>
		<a href="http://www.deemtree.com/contact" class="deemtree-visit" target="_blank">Support</a>
	</h2>
	<form name="deemtree-settings-form" method="post" action="options.php">
		<?php settings_fields( $sSettingsOptionKey ); ?>
		<?php do_settings_sections( $sSettingsNamespace ); ?>
		<?php submit_button(); ?>
	</form>
</div>