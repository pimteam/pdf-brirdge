<?php
/*
Plugin Name: PDF Bridge
Plugin URI: 
Description: This is a plugin for developers. It lets you convert your HTML content to PDF and then do whatever you want with it (save it as file or output it). Uses the <a href="http://www.mpdf1.com/mpdf/index.php" target="_blank">MPDF library</a>
Author: Kiboko Labs
Version: 2.0.3
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
	if (!class_exists('Mpdf\Mpdf')) {
		include_once(PDF_BRIDGE_PATH.'/lib/vendor/autoload.php');
	}
	
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
	
	if (!empty($pdf_settings['background_image'])) {
		$content = '<style type="text/css">
			body {
				background-image: url('
				. esc_url($pdf_settings['background_image'])
				. ');
				background-image-resize: '
				. intval($pdf_settings['background_resize'])
				. ';
			}
		</style>' . "\n" . $content;
	}

	// Convert relative WordPress resource paths to local files.
	$content = pdf_bridge_localize_resources($content);

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


/**
 * Convert WordPress content image URLs and relative paths
 * to local filesystem paths for mPDF.
 */
function pdf_bridge_localize_resources($html) {
    $uploads = wp_upload_dir();

    if (!empty($uploads['error'])) {
        error_log(
            'PDF Bridge: wp_upload_dir() error: ' . $uploads['error']
        );

        return $html;
    }

    $uploads_base_url = trailingslashit($uploads['baseurl']);
    $uploads_base_dir = trailingslashit(
        wp_normalize_path($uploads['basedir'])
    );

    $content_base_url = trailingslashit(content_url());
    $content_base_dir = trailingslashit(
        wp_normalize_path(WP_CONTENT_DIR)
    );

    $resolve_resource = static function ($resource) use (
        $uploads_base_url,
        $uploads_base_dir,
        $content_base_url,
        $content_base_dir
    ) {
        $resource = html_entity_decode(
            trim($resource),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        if ($resource === '') {
            return $resource;
        }

        // Do not modify inline images, anchors and special schemes.
        if (
            preg_match(
                '~^(?:data:|cid:|blob:|javascript:|mailto:|#)~i',
                $resource
            )
        ) {
            return $resource;
        }

        // Preserve query string for now, but don't use it in file paths.
        $resource_without_query = preg_split(
            '/[?#]/',
            $resource,
            2
        )[0];

        $resource_without_query = rawurldecode(
            $resource_without_query
        );

        $candidate = '';

        /*
         * Case 1:
         * https://example.com/wp-content/uploads/...
         */
        if (strpos($resource, $uploads_base_url) === 0) {
            $relative = substr(
                $resource_without_query,
                strlen($uploads_base_url)
            );

            $candidate = $uploads_base_dir
                . ltrim($relative, '/');
        }

        /*
         * Case 2:
         * https://example.com/wp-content/...
         */
        elseif (strpos($resource, $content_base_url) === 0) {
            $relative = substr(
                $resource_without_query,
                strlen($content_base_url)
            );

            $candidate = $content_base_dir
                . ltrim($relative, '/');
        }

        /*
         * Case 3:
         * wp-content/uploads/2025/08/logo2.webp
         * /wp-content/uploads/2025/08/logo2.webp
         */
        elseif (
            preg_match(
                '~^/?wp-content/(.+)$~i',
                $resource_without_query,
                $matches
            )
        ) {
            $candidate = $content_base_dir . $matches[1];
        }

        /*
         * Case 4:
         * Relative uploads path:
         * uploads/2025/08/logo2.webp
         */
        elseif (
            preg_match(
                '~^/?uploads/(.+)$~i',
                $resource_without_query,
                $matches
            )
        ) {
            $candidate = $uploads_base_dir . $matches[1];
        }

        /*
         * Do not modify other absolute URLs.
         */
        elseif (
            preg_match('~^[a-z][a-z0-9+.-]*://~i', $resource) ||
            strpos($resource, '//') === 0
        ) {
            return $resource;
        }

        /*
         * Generic root-relative resource.
         */
        elseif (strpos($resource_without_query, '/') === 0) {
            $candidate = wp_normalize_path(
                ABSPATH . ltrim($resource_without_query, '/')
            );
        }

        /*
         * Generic relative resource.
         */
        else {
            $candidate = wp_normalize_path(
                ABSPATH . ltrim($resource_without_query, '/')
            );
        }

        if ($candidate === '') {
            return $resource;
        }

        $candidate = wp_normalize_path($candidate);
        $real_file = realpath($candidate);

        if (!$real_file || !is_file($real_file)) {
            error_log(
                sprintf(
                    'PDF Bridge: Local resource not found. Resource: %s; Candidate: %s',
                    $resource,
                    $candidate
                )
            );

            return $resource;
        }

        return wp_normalize_path($real_file);
    };

    // Handle <img src="...">.
    $html = preg_replace_callback(
        '~(<img\b[^>]*\bsrc\s*=\s*)(["\'])(.*?)\2~is',
        static function ($matches) use ($resolve_resource) {
            return $matches[1]
                . $matches[2]
                . esc_attr($resolve_resource($matches[3]))
                . $matches[2];
        },
        $html
    );

    // Handle unquoted <img src=...>.
    $html = preg_replace_callback(
        '~(<img\b[^>]*\bsrc\s*=\s*)(?!["\'])([^\s>]+)~is',
        static function ($matches) use ($resolve_resource) {
            return $matches[1]
                . '"'
                . esc_attr($resolve_resource($matches[2]))
                . '"';
        },
        $html
    );

    // Handle CSS url(...), including background images.
    $html = preg_replace_callback(
        '~url\(\s*(["\']?)(.*?)\1\s*\)~is',
        static function ($matches) use ($resolve_resource) {
            $resolved = $resolve_resource($matches[2]);

            return 'url("' . esc_attr($resolved) . '")';
        },
        $html
    );

    return $html;
}
