<?php
/* @wordpress-plugin
 * Plugin Name:       WooCommerce Kryptova Direct Payment Gateway
 * Plugin URI:        https://kryptova.vip/
 * Description:       Kryptova Direct Card Payment is help to do payment using third party debit card/credit card payment getway.
 * Version:           1.3.4
 * WC requires at least: 3.0
 * WC tested up to: 5.3
 * Author:            Kryptova
 * Author URI:        https://kryptova.vip/
 * Text Domain:       woocommerce-kryptova-payment-gateway
 * Domain Path: /languages
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
if(kryptova_custom_payment_is_woocommerce_active()){
	add_filter('woocommerce_payment_gateways', 'add_kryptova_payment_gateway');
	function add_kryptova_payment_gateway( $gateways ){
		$gateways[] = 'WC_kryptova_Payment_Gateway';
		return $gateways;
	}
	
	add_action('plugins_loaded', 'init_kryptova_payment_gateway');
	function init_kryptova_payment_gateway(){
		require 'class-woocommerce-kryptova-payment-gateway.php';
	}

	add_action( 'plugins_loaded', 'kryptova_payment_load_plugin_textdomain' );
	function kryptova_payment_load_plugin_textdomain() {
	  load_plugin_textdomain( 'woocommerce-kryptova-payment-gateway', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

}


/**
 * @return bool
 */
function kryptova_custom_payment_is_woocommerce_active()
{
	$active_plugins = (array) get_option('active_plugins', array());

	if (is_multisite()) {
		$active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
	}

	return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}
