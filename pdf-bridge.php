<?php
/*
Plugin Name: PDF Bridge
Plugin URI: 
Description: This is a plugin for developers. It lets you convert your HTML content to PDF and then do whatever you want with it (save it as file or output it). Uses the <a href="http://www.mpdf1.com/mpdf/index.php" target="_blank">MPDF library</a>
Author: Kiboko Labs
Version: 2.0.2
Author URI: http://calendarscripts.info/
License: GPLv2 or later
*/

define( 'PDF_BRIDGE_PATH', dirname( __FILE__ ) );
// register_activation_hook(__FILE__, 'pdf_bridge_init');

add_action('init', 'pdf_bridge_init');

function pdf_bridge_init() {
	global $wpdb;
	
	add_filter('pdf-bridge-convert', 'pdfbridge_html2pdf', 10, 2);	
	add_action('template_redirect', 'pdf_bridge_test');	
	add_action('watupro-certificate-pdf-settings', 'pdf_bridge_certificate_settings');	
	add_action('watupro-quiz-pdf-settings', 'pdf_bridge_quiz_settings');
	add_action('watupro-certificate-saved', 'pdf_bridge_certificate_saved');
	add_action('namaste-certificate-pdf-settings', 'pdf_bridge_namaste_certificate_settings');
	add_action('namaste-certificate-saved', 'pdf_bridge_namaste_certificate_saved');
}

// param $settings is optional array of PDF settings, same like in WatuPRO or Namaste
// $pdf_settings is optional array that can be passed to the function instead of it reading it from $content
function pdfbridge_html2pdf($content, $pdf_settings = null) {	
	include_once(PDF_BRIDGE_PATH.'/lib/vendor/autoload.php');
	
	// extract the ID from contents
	if(strstr($content, '-watupro-certificate-id-')) {
		$parts = explode('-watupro-certificate-id-', $content);
		$cid = $parts[1];
		$settings = get_option('watupro_certificates_pdf');
	}	
	if(strstr($content, '-namaste-certificate-id-')) {
		$parts = explode('-namaste-certificate-id-', $content);
		$cid = $parts[1];
		$settings = get_option('namaste_certificates_pdf');
	}	

	// load settings if not passed to the function	
   if(empty($pdf_settings)) $pdf_settings = @$settings[$cid];
   
   $paper_size = empty($pdf_settings['paper_size']) ? 'Letter' : $pdf_settings['paper_size'];
   $orientation = @$pdf_settings['orientation'];
      
	$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => $paper_size.$orientation, 'setAutoBottomMargin' => 'stretch', 'setAutoTopMargin' => 'stretch']);
	
	// false by default
	$mpdf->curlAllowUnsafeSslRequests = false; // switching to true may help sometimes to load images
	$mpdf->showImageErrors = false;
	if(get_option('pdf_bridge_show_image_errors') == 1) $mpdf->showImageErrors = true;	
	
	$content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
   
   if(!empty($pdf_settings['background_image'])) {
      $content = '<style type="text/css">
         body {background-image:url('.$pdf_settings['background_image'].'); background-image-resize:'.intval($pdf_settings['background_resize']).'}
       </style>'."\n".$content;
   }	
   
   $file_name = "certificate-".intval($_POST['watupro_current_taking_id'] ?? 0).'.pdf';
   
   //  force download with dynamic file data from other plugins
   if(!empty($_GET['download_file_name'])) {   
   	$_GET['download_file_name'] = sanitize_file_name($_GET['download_file_name']);
   	if(empty($pdf_settings) or !is_array($pdf_settings)) $pdf_settings = array();
   	$pdf_settings['file_name'] = $file_name = sanitize_text_field($_GET['download_file_name']);
   	$pdf_settings['force_download'] = 1;
   }   
   
   // add stylesheets
   if(!empty($pdf_settings['stylesheets']) and is_array($pdf_settings['stylesheets'])) {
   	foreach($pdf_settings['stylesheets'] as $css) {
   		$stylesheet = @file_get_contents($css);
   		if(!empty($stylesheet)) $mpdf->WriteHTML($stylesheet, 1);
   	}
   }

	// header and footer
	if(!empty($pdf_settings['pdf_header'])) $mpdf->SetHTMLHeader($pdf_settings['pdf_header']);
	if(!empty($pdf_settings['pdf_footer'])) $mpdf->SetHTMLFooter($pdf_settings['pdf_footer']);
	
	$mpdf->WriteHTML($content);
	if(!empty($_GET['certificate_as_attachment'])) $pdf_settings['certificate_as_attachment'] = $_GET['certificate_as_attachment'];
	if(!empty($pdf_settings['force_download']) and empty($pdf_settings['certificate_as_attachment'])) $mpdf->Output($pdf_settings['file_name'], 'D');
	elseif(!empty($pdf_settings['certificate_as_attachment'])) {
		$path = WP_CONTENT_DIR . "/uploads/watupro/";
		if(!file_exists($path)) {
			mkdir($path, 0755, true);
		}
		$mpdf->Output($path . $file_name, 'F');
	}
	else $mpdf->Output(); 
}

// allow to test the plugin installation with a very simple output
// by passing param pdf-bridge-test=1 in the URL
function pdf_bridge_test() {
	
	if(!empty($_GET['pdf-bridge-test'])) {
		$content = "<h1>Very simple test</h1><p>Hello PDF!</p>";
		$pdf =  pdfbridge_html2pdf($content);
		die($pdf);
	}
}

// displays extra options in the add/edit certificate form
function pdf_bridge_certificate_settings($cid = 0, $plugin = 'watupro') {
  // get the current certificate settings
  $settings = ($plugin == 'watupro') ? get_option('watupro_certificates_pdf') : get_option('namaste_certificates_pdf'); 
  $pdf_settings = @$settings[$cid];
  
  // paper sizes
  $paper_sizes = array('Letter', 'A5', 'A4', 'A3', 'A2', 'A1', 'A0', 'B4', 'B3', 'B2', 'B1', 'B0');
 	
 	include(PDF_BRIDGE_PATH."/views/certificate-settings.html.php");  	
}

// displays extra options in final screen tab in WatuPRO
function pdf_bridge_quiz_settings($pdf_settings = null) {	
  // paper sizes
  $paper_sizes = array('Letter', 'A5', 'A4', 'A3', 'A2', 'A1', 'A0', 'B4', 'B3', 'B2', 'B1', 'B0');
  $quiz = true;
 	
 	include(PDF_BRIDGE_PATH."/views/certificate-settings.html.php");  	
}

// same but in Namaste! LMS
function pdf_bridge_namaste_certificate_settings($cid = 0) {
	pdf_bridge_certificate_settings($cid, 'namaste');
}

// when saving a certificate store the settings
function pdf_bridge_certificate_saved($cid, $plugin = 'watupro') {
	
	$option_name = ($plugin == 'watupro') ? 'watupro_certificates_pdf' : 'namaste_certificates_pdf';
	$settings = get_option($option_name);
	
	if(!empty($_POST['pdf_bridge_file_name']) and !preg_match("/\.pdf$/i", $_POST['pdf_bridge_file_name']))	 $_POST['pdf_bridge_file_name'] .= ".pdf";
	
	$header = empty($_POST['pdf_header']) ? '' : wp_kses_post($_POST['pdf_header']);
	$footer = empty($_POST['pdf_footer']) ? '' : wp_kses_post($_POST['pdf_footer']);
	
	$settings[$cid] = array('paper_size' => sanitize_text_field($_POST['pdf_bridge_paper_size']), 
		'orientation' => sanitize_text_field($_POST['pdf_bridge_orientation']), 'force_download' => intval(@$_POST['pdf_bridge_force_download']),
		'file_name' => sanitize_text_field($_POST['pdf_bridge_file_name']), 
		'background_image' => sanitize_text_field($_POST['pdf_bridge_background_image']),
		'background_resize' => intval($_POST['pdf_bridge_background_resize']),
		'pdf_header' => $header, 'pdf_footer' => $footer);

	update_option($option_name, $settings);
}

// same but in Namaste! LMS
function pdf_bridge_namaste_certificate_saved($cid) {
	pdf_bridge_certificate_saved($cid, 'namaste'); 
}

function pdf_bridge_options() {
	if(!empty($_POST['ok']) and check_admin_referer('pdf_bridge_options')) {
		$show_image_errors = empty($_POST['show_image_errors']) ? 0 : 1;
		update_option('pdf_bridge_show_image_errors', $show_image_errors);
	}
	
	$show_image_errors = get_option('pdf_bridge_show_image_errors');
	include(PDF_BRIDGE_PATH."/views/options.html.php");  	
}

// common settings
function pdf_bridge_add_settings_page() {
    add_options_page( 'PDF Bridge', 'PDF Bridge', 'manage_options', 'pdf_bridge', 'pdf_bridge_options' );
}
add_action( 'admin_menu', 'pdf_bridge_add_settings_page' );
