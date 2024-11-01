<?php
/**
 * Plugin Name: Clean Coupons for Woocommerce by TheCartPress
 * Plugin URI:
 * Description: TCP Clean Coupons
 * Version: 1.1.0
 * Stable tag: 1.1.0
 * Requires PHP: 5.6
 * Requires at least: 5.5
 * Tested up to: 5.8
 * Author: TCP Team
 * Author URI: https://www.thecartpress.com
 * WC tested up to: 5.9.0
 */
defined('ABSPATH') or exit;

class TCP_clean_coupons {

	public function __construct() {
		$tcp_f = __DIR__ . '/tcp.php';
		if (file_exists($tcp_f)) {
			require_once $tcp_f;
		}
		require_once __DIR__ . '/admin.php';
		register_activation_hook(__FILE__, array($this, 'wp_schedule_delete_expired_coupons'));
		register_deactivation_hook(__FILE__, array($this, 'wp_remove_scheduled_delete_expired_coupons'));
		add_action('delete_expired_coupons', array($this, 'wp_delete_expired_coupons'));
		add_filter('pre_update_option_woocommerce_coupon_cleanup_option_name', array($this, 'update_hook_value'), 10, 2);
	}

	public function wp_schedule_delete_expired_coupons() {
		if (!wp_next_scheduled('delete_expired_coupons')) {
			$woocommerce_coupon_cleanup_options = get_option('woocommerce_coupon_cleanup_option_name');
			$freq = is_array($woocommerce_coupon_cleanup_options) && isset($woocommerce_coupon_cleanup_options['frequency_1']) ? $woocommerce_coupon_cleanup_options['frequency_1'] : 'daily';
			wp_schedule_event(time(), $freq, 'delete_expired_coupons');
		}
	}

	public function wp_remove_scheduled_delete_expired_coupons() {
		wp_clear_scheduled_hook('delete_expired_coupons');
	}

	public function update_hook_value($value, $old_value) {
		if ($value['frequency_1'] && $old_value['frequency_1'] != $value['frequency_1']) {
			wp_clear_scheduled_hook('delete_expired_coupons');
			wp_schedule_event(time(), $value['frequency_1'], 'delete_expired_coupons');
		}
		return $value;
	}

	public function wp_delete_expired_coupons() {
		$args = array(
			'numberposts' => 10,
			'post_type' => 'shop_coupon',
			'post_status' => 'publish',
			'meta_query' => array(
				array(
					'key' => 'date_expires',
					'value' => time(),
					'compare' => '<='
				),
				array(
					'key' => 'date_expires',
					'value' => '',
					'compare' => '!='
				)
			)
		);
		$coupons = get_posts($args);
		$woocommerce_coupon_cleanup_options = get_option('woocommerce_coupon_cleanup_option_name');
		if (!is_array($woocommerce_coupon_cleanup_options)) {
			$woocommerce_coupon_cleanup_options = [];
		}
		if (!empty($coupons)) {
			// $current_time = current_time('timestamp');
			foreach ($coupons as $coupon) {
				if (!isset($woocommerce_coupon_cleanup_options['removal_type_0']) || $woocommerce_coupon_cleanup_options['removal_type_0'] == 'trash') {
					wp_trash_post($coupon->ID);
				} else {
					wp_delete_post($coupon->ID, true);
				}
			}
		}
	}

}

new TCP_clean_coupons();
