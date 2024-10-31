<?php
/*
  Plugin Name: Express Payments: EPOS
  Plugin URI: https://express-pay.by/cms-extensions/wordpress
  Description: Express Payments: EPOS - is a plugin for integration with the «Express Payments» (express-pay.by) via API. The plugin allows you to issue an invoice in the EPOS system, receive and process a payment notification in the ERIP system, issue invoices for payment by bank cards, receive and process notifications of payment by a bank card. The plugin description is available at: <a target="blank" href="https://express-pay.by/cms-extensions/wordpress">https://express-pay.by/cms-extensions/wordpress</a>
  Version: 1.1.0
  Author: LLC "TriInkom"
  Author URI: https://express-pay.by/
  License: GPLv2 or later
  License URI: http://www.gnu.org/licenses/gpl-2.0.html
  WC requires at least: 8.0
  WC tested up to: 8.6
  Text Domain: wordpress_epos_expresspay
  Domain Path: /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


define("EXPRESSPAY_EPOS_VERSION", "1.1.0");

/**
 * WC ExpressPay_Epos Payment gateway plugin class.
 *
 * @class WC_ExpressPay_Epos_Payments
 */
class WC_ExpressPay_Epos_Payments {

	/**
	 * Plugin bootstrapping.
	 */
	public static function init() {

		add_action( 'plugins_loaded', array( __CLASS__, 'includes' ), 0 );
		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'add_gateway' ) );
		add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'woocommerce_gateway_expresspay_epos_block_support' ) );
		add_action( 'before_woocommerce_init', array( __CLASS__, 'before_gateway_expresspay_epos' ) );
		
		load_plugin_textdomain("wordpress_epos_expresspay", false, dirname( plugin_basename( __FILE__ ) ) . '/languages');

	}

	/**
	 * Add the ExpressPay_Epos Payment gateway to the list of available gateways.
	 *
	 * @param array
	 */
	public static function add_gateway( $gateways ) {

		$options = get_option( 'woocommerce_expresspay_epos_settings', array() );

		if ( isset( $options['hide_for_non_admin_users'] ) ) {
			$hide_for_non_admin_users = $options['hide_for_non_admin_users'];
		} else {
			$hide_for_non_admin_users = 'no';
		}

		if ( ( 'yes' === $hide_for_non_admin_users && current_user_can( 'manage_options' ) ) || 'no' === $hide_for_non_admin_users ) {
			$gateways[] = 'WC_Gateway_ExpressPay_Epos';
		}
		return $gateways;
	}

	/**
	 * Plugin includes.
	 */
	public static function includes() {

		// Make the WC_Gateway_ExpressPay_Epos class available.
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			require_once 'includes/class-wc-gateway-expresspay-epos.php';
		}
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_abspath() {
		return trailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Registers WooCommerce Blocks integration.
	 *
	 */
	public static function woocommerce_gateway_expresspay_epos_block_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once 'includes/blocks/class-wc-expresspay-epos-payments-blocks.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new WC_Gateway_ExpressPay_Epos_Blocks_Support() );
				}
			);
		}
	}


	public static function before_gateway_expresspay_epos() { 
		if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
		   \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true); 
		   \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
		}
	}
}

WC_ExpressPay_Epos_Payments::init();
