<p>&nbsp;</p>
<h3><?php _e('PDF Settings', 'pdf-bridge')?></h3>
<p><?php _e('Paper size:', 'pdf-bridge')?> <select name="pdf_bridge_paper_size">
	<?php foreach($paper_sizes as $size):
		if(!empty($pdf_settings['paper_size']) and $pdf_settings['paper_size'] == $size) $selected = ' selected';
		else $selected = ''; ?>
		<option value="<?php echo $size?>"<?php echo $selected?>><?php echo $size;?></option>
	<?php endforeach;?>
</select>
&nbsp;
<?php _e('Orientation:', 'pdf-bridge')?> <select name="pdf_bridge_orientation">
	<option value="-P" <?php if(empty($pdf_settings['orientation']) or $pdf_settings['orientation'] ==  '-P') echo 'selected'?>><?php _e('Portrait', 'pdf-bridge')?></option>
	<option value="-L" <?php if(!empty($pdf_settings['orientation']) and $pdf_settings['orientation'] == '-L') echo 'selected'?>><?php _e('Landscape', 'pdf-bridge')?></option>
</select></p>
<p><input type="checkbox" name="pdf_bridge_force_download" value="1" <?php if(!empty($pdf_settings['force_download'])) echo 'checked'?> onclick="this.checked ? jQuery('#forceDownloadOption').show():jQuery('#forceDownloadOption').hide();" > <?php _e('Force file download', 'pdf-bridge');?> &nbsp;
<span id="forceDownloadOption" style='display:<?php echo (!empty($pdf_settings['force_download'])) ? 'inline':'none';?>'><?php _e('File name:', 'pdf-bridge')?> <input type="text" name="pdf_bridge_file_name" value="<?php echo empty($pdf_settings['file_name']) ? '' : stripslashes($pdf_settings['file_name']);?>"></span></p>

<?php if(!empty($quiz)): // these are only for quizzes?>
<p><a href="#" onclick="jQuery('#pdfBridgePDFHeader').toggle();return false;"><?php _e('+ Set PDF Header', 'pdf-bridge');?></a></p>
<div id="pdfBridgePDFHeader" style='display:<?php echo empty($pdf_settings['pdf_header']) ? 'none' : 'block';?>'>
	<textarea name="pdf_header" rows="5" cols="80"><?php if(!empty($pdf_settings['pdf_header'])) echo htmlentities(stripslashes($pdf_settings['pdf_header']));?></textarea><br />
	<?php _e('Insert HTML code for the header. It will be shown in each page of the generated PDF file.', 'pdf-bridge');?>
</div>

<p><a href="#" onclick="jQuery('#pdfBridgePDFFooter').toggle();return false;"><?php _e('+Set PDF Footer', 'pdf-bridge');?></a></p>
<div id="pdfBridgePDFFooter" style='display:<?php echo empty($pdf_settings['pdf_footer']) ? 'none' : 'block';?>'>
	<textarea name="pdf_footer" rows="5" cols="80"><?php if(!empty($pdf_settings['pdf_footer'])) echo htmlentities(stripslashes($pdf_settings['pdf_footer']));?></textarea><br />
	<?php _e('Insert HTML code for the footer. It will be shown in each page of the generated PDF file.', 'pdf-bridge');?>
</div>
<?php endif;?>

<?php if(empty($quiz)): // for now these are only for certificates?>
<p><?php _e('Optional background image:', 'pdf-bridge');?> <input type="text" size="60" value="<?php echo @$pdf_settings['background_image']?>" name="pdf_bridge_background_image"> <?php printf(__('(Enter URL or relative path of the .jpg or .png image (not .gif). You can upload it on your <a href="%s" target="_blank">media library</a> and get the URL from it.)', 'pdf-bridge'), 'upload.php');?><br>
<?php _e('Resize:', 'pdf-bridge');?> <select name="pdf_bridge_background_resize">
   <option value="0" <?php if(empty($pdf_settings['background_resize'])) echo 'selected'?>><?php _e('No resize', 'pdf-bridge');?></option>
   <option value="1" <?php if(!empty($pdf_settings['background_resize']) and $pdf_settings['background_resize'] == 1) echo 'selected'?>><?php _e('Shrink to fit width', 'pdf-bridge');?></option>
   <option value="2" <?php if(!empty($pdf_settings['background_resize']) and $pdf_settings['background_resize'] == 2) echo 'selected'?>><?php _e('Shrink to fit height', 'pdf-bridge');?></option>
   <option value="3" <?php if(!empty($pdf_settings['background_resize']) and $pdf_settings['background_resize'] == 3) echo 'selected'?>><?php _e('Shrink to fit width and height', 'pdf-bridge');?></option>
   <option value="4" <?php if(!empty($pdf_settings['background_resize']) and $pdf_settings['background_resize'] == 4) echo 'selected'?>><?php _e('Resize to fit width', 'pdf-bridge');?></option>
   <option value="5" <?php if(!empty($pdf_settings['background_resize']) and $pdf_settings['background_resize'] == 5) echo 'selected'?>><?php _e('Resize to fit height', 'pdf-bridge');?></option>
   <option value="6" <?php if(!empty($pdf_settings['background_resize']) and $pdf_settings['background_resize'] == 6) echo 'selected'?>><?php _e('Resize to fit width and height', 'pdf-bridge');?></option>
</select></p>
<?php endif;?>
