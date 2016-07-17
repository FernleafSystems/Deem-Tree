
<div class="wrap">
	<h2>
		<div id="icon-options-deemtree" class="icon32">&nbsp;</div>
		Deemtree Settings
		<a href="http://www.deemtree.com/features" class="deemtree-visit" target="_blank">Take a Tour</a>
		<a href="http://www.deemtree.com/demo/" class="deemtree-visit" target="_blank">View Demo</a>
		<a href="https://deemtree.helpdocs.com/" class="deemtree-visit" target="_blank">Support</a>
	</h2>
	<form name="deemtree-settings-form" method="post" action="options.php">
		<?php settings_fields( $sSettingsOptionKey ); ?>
		<?php do_settings_sections( $sSettingsNamespace ); ?>
		<?php submit_button(); ?>
	</form>
</div>