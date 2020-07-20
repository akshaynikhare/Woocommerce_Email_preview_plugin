<?php
/*
Plugin Name: Preview E-mails for WooCommerce by akshay
Description: WooCommerce plugin lets you Preview Emails, without send.
Plugin URI: https://
Author: akshay Nikhare
Author URI: https://
Version: 1.0.0
License: http://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
WC requires at least: 3.0.0
WC tested up to: 4.0.0
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if( !defined('WOO_PREVIEW_EMAILS_DIR') ){
	define('WOO_PREVIEW_EMAILS_DIR', dirname(__FILE__));
}

if( !defined('WOO_PREVIEW_EMAILS_FILE') ){
	define('WOO_PREVIEW_EMAILS_FILE', __FILE__);
}

if( !function_exists('is_woocommerce_active') || ! class_exists( 'WC_Dependencies' ) ){
	require_once('includes/class-wc-dependencies.php');
}

if( is_woocommerce_active() ){
	require_once('classes/class-woocommerce-preview-emails.php');
}


add_action( 'plugins_loaded', 'woo_preview_emails_load_text_domain' );