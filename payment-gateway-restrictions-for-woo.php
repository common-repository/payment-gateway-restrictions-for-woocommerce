<?php

/*
* Plugin Name:  Payment Gateway Restrictions for WooCommerce
* Plugin URI:   https://superwpheroes.io/product/payment-gateway-restrictions-for-woocommerce/
* Description:  A nice little plugin that lets you select which payment gateway is available for individual customers on the WooCommerce checkout. Please note that the user role must be "Customer" in order for it to apply changes.
* Version:      1.0.3
* Author:       Super WP Heroes
* Author URI:   https://superwpheroes.io/
* License:      GPL2
* Text Domain:  payment-gateway-restrictions-for-woo
* Domain Path:  /languages
* Requires at least: 		4.0
* Tested up to: 		6.1.1
* WC requires at least: 3.0
* WC tested up to:      7.1.0
*/

defined( 'ABSPATH' ) or die( 'No script stuff please!' );

add_action( 'init', 'swph_load_textdomain_pgrwoo' );
function swph_load_textdomain_pgrwoo() {
  load_plugin_textdomain( 'payment-gateway-restrictions-for-woo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
}

// Plugin activation
function swph_pgrwoo_activate() {
	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) and current_user_can( 'activate_plugins' ) ) {
		$PluginsURL = admin_url( 'plugins.php' );
    wp_die(sprintf(__('Sorry, but this plugin requires the WooCommerce Plugin to be installed and active. <br><a href="%s">&laquo; Return to Plugins</a>', 'payment-gateway-restrictions-for-woo'), $PluginsURL));
	}
}
register_activation_hook( __FILE__, 'swph_pgrwoo_activate' );

// Plugin deactivation
function swph_pgrwoo_deactivate() {
  //
}
register_uninstall_hook( __FILE__, 'swph_pgrwoo_deactivate' );

// Render Woocommerce payment restriction from
function swph_pgrwoo_render_payment_restriction() {
	global $user_id;
	$user_meta = get_userdata($user_id);
	$user_roles = $user_meta->roles;

	$output = "<h2>".__('Restrict payment methods', 'payment-gateway-restrictions-for-woo')."</h2>
            <table class='form-table wph-table'>";
  $output .= '<div class="swph_pro_banner"><a href="https://superwpheroes.io/product/payment-gateway-restrictions-for-woocommerce/" target="_blank"><img src="'.plugins_url( 'assets/img/payment-gateway-restrictions-for-woo-go-pro-banner.png', __FILE__ ).'" /></a></div>';
	if(current_user_can('administrator') && in_array('customer', $user_roles)) {
		$user_restrictions = get_user_meta($user_id,'_swph-pgrwoo-restictions', true);
		$methods = WC()->payment_gateways->get_available_payment_gateways();
		foreach($methods as $method) {
			if(empty($user_restrictions)) {
	      $output .="<tr><th>".$method->title."</td><td><input type='checkbox' name='swph_pgrwoo_disabled_payments[]' value='".$method->id."'></th></tr>";
			}	else {
				if(in_array($method->id,$user_restrictions)) {
					$output .="<tr><th>".$method->title."</td><td><input type='checkbox' checked name='swph_pgrwoo_disabled_payments[]' value='".$method->id."'></th></tr>";
				}	else {
					$output .="<tr><th>".$method->title."</td><td><input type='checkbox' name='swph_pgrwoo_disabled_payments[]' value='".$method->id."'></th></tr>";
				}
			}
    }
		$output .= "</table>";

		echo $output;
	}
}
add_action( 'edit_user_profile', 'swph_pgrwoo_render_payment_restriction', 10, 1 ); 

// Update user payment restrictions as user meta
function swph_pgrwoo_update_user_restriction() {
	global $user_id;
	$disabled_payments = array();
	//$disabled_payments = sanitize_text_field($_POST['swph_pgrwoo_disabled_payments']);

	foreach($_POST['swph_pgrwoo_disabled_payments'] as $payment) {
		$disabled_payments[] = sanitize_text_field($payment);
	}

	update_user_meta( $user_id, '_swph-pgrwoo-restictions', $disabled_payments );
}
add_action('edit_user_profile_update','swph_pgrwoo_update_user_restriction');

// Filter payment options
function swph_pgrwoo_filter_payment_options($gateways) {
	$current_user = get_current_user_id();
	$restrictions = get_user_meta($current_user, '_swph-pgrwoo-restictions', true);

	//var_dump($restrictions);
	if(!empty($restrictions))	{
		foreach($gateways as $gt)	{
			if(in_array($gt->id,$restrictions)) {
				unset($gateways[$gt->id]);
			}
		}
  }

	return $gateways;
}
add_filter('woocommerce_available_payment_gateways', 'swph_pgrwoo_filter_payment_options');