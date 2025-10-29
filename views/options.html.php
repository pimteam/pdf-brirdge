<div class="wrap">
	<h1>PDF Bridge Global Settings</h1>
	
	<form method="post">
		<p>Show image errors: <select name="show_image_errors">
			<option value="0" <?php if(empty($show_image_errors)) echo 'selected';?>>Off</option>
			<option value="1" <?php if(!empty($show_image_errors)) echo 'selected';?>>On</option>
		</select><br>
		<i>Set this to On in case you are having problems with displaying images in the PDF. It will display the errors on the screen or will write them to your server error log (depending on your<a href="https://wordpress.org/support/article/debugging-in-wordpress/" target="_blank"> Debug settings</a>)</i></p>	
		
		<p><input type="submit" value="Save Settings" class="button button-primary"></p>
	
		<?php wp_nonce_field('pdf_bridge_options');?>
		<input type="hidden" name="ok" value="1">
	</form>
</div>