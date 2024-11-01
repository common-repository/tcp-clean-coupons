<?php
defined('ABSPATH') or exit;

class TCP_WooCommerceCouponCleanupAdmin {

	private $woocommerce_coupon_cleanup_options;

	public function __construct() {
		if (class_exists('TCP_Menu') && method_exists('TCP_Menu', 'add_submenu')) {
			TCP_Menu::add_submenu([
				'plugin_id' => 'tcp-clean-coupons',
				'page_title' => 'Coupon Cleanup',
				'menu_title' => 'Coupon Cleanup',
				'menu_slug' => 'tcp-clean-coupons',
				'function' => [$this, 'woocommerce_coupon_cleanup_create_admin_page'],
			]);
		} else {
			add_action('admin_menu', array($this, 'woocommerce_coupon_cleanup_add_plugin_page'), 99);
		}
		add_action('admin_init', array($this, 'woocommerce_coupon_cleanup_page_init'));
		$plugin = plugin_basename(__FILE__);
		add_filter('plugin_action_links_' . str_replace('-admin', '', $plugin), array(&$this, 'woo_clean_coupon_plugin_settings_links'));
	}

	public function woo_clean_coupon_plugin_settings_links($links) {
		$plugin_links = array(
			'<a href="' . esc_url(admin_url('/admin.php?page=tcp-clean-coupons')) . '">' . __('Settings', 'tcp-clean-coupons') . '</a>'
		);
		return array_merge((array) $plugin_links, $links);
	}

	public function woocommerce_coupon_cleanup_add_plugin_page() {
		add_submenu_page('thecartpress',
			'Coupon Cleanup',
			'Coupon Cleanup',
			'manage_options',
			'tcp-clean-coupons',
			array($this, 'woocommerce_coupon_cleanup_create_admin_page')
		);
	}

	public function woocommerce_coupon_cleanup_create_admin_page() {
		$this->woocommerce_coupon_cleanup_options = get_option('woocommerce_coupon_cleanup_option_name');
		?>

		<div class="wrap">
			<h2>WooCommerce Coupon Cleanup</h2>
			<p>Next cleanup scheduled at: <?php echo date('d-m-Y, H:i', wp_next_scheduled('delete_expired_coupons')); ?></p>
				<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
				settings_fields('woocommerce_coupon_cleanup_option_group');
				do_settings_sections('tcp-clean-coupons-admin');
				submit_button();
				?>
			</form>
		</div>
	<?php
	}

	public function woocommerce_coupon_cleanup_page_init() {
		register_setting(
			'woocommerce_coupon_cleanup_option_group', // option_group
			'woocommerce_coupon_cleanup_option_name', // option_name
			array($this, 'woocommerce_coupon_cleanup_sanitize') // sanitize_callback
		);

		add_settings_section(
			'woocommerce_coupon_cleanup_setting_section', // id
			'Settings', // title
			array($this, 'woocommerce_coupon_cleanup_section_info'), // callback
			'tcp-clean-coupons-admin' // page
		);

		add_settings_field(
			'removal_type_0', // id
			'Removal Type', // title
			array($this, 'removal_type_0_callback'), // callback
			'tcp-clean-coupons-admin', // page
			'woocommerce_coupon_cleanup_setting_section' // section
		);

		add_settings_field(
			'frequency_1', // id
			'Frequency', // title
			array($this, 'frequency_1_callback'), // callback
			'tcp-clean-coupons-admin', // page
			'woocommerce_coupon_cleanup_setting_section' // section
		);
	}

	public function woocommerce_coupon_cleanup_sanitize($input) {
		$sanitary_values = array();
		if (isset($input['removal_type_0'])) {
			$sanitary_values['removal_type_0'] = $input['removal_type_0'];
		}

		if (isset($input['frequency_1'])) {
			$sanitary_values['frequency_1'] = $input['frequency_1'];
		}

		return $sanitary_values;
	}

	public function woocommerce_coupon_cleanup_section_info() {

	}

	public function removal_type_0_callback() { ?>
		<fieldset><?php $checked = (!isset($this->woocommerce_coupon_cleanup_options['removal_type_0']) || $this->woocommerce_coupon_cleanup_options['removal_type_0'] === 'trash' ) ? 'checked' : ''; ?>
		<label for="removal_type_0-0"><input type="radio" name="woocommerce_coupon_cleanup_option_name[removal_type_0]" id="removal_type_0-0" value="trash" <?php echo $checked; ?>> Trash</label><br>
		<?php $checked = ( isset($this->woocommerce_coupon_cleanup_options['removal_type_0']) && $this->woocommerce_coupon_cleanup_options['removal_type_0'] === 'delete' ) ? 'checked' : ''; ?>
		<label for="removal_type_0-1"><input type="radio" name="woocommerce_coupon_cleanup_option_name[removal_type_0]" id="removal_type_0-1" value="delete" <?php echo $checked; ?>> Permanently remove</label></fieldset> <?php
	}

	public function frequency_1_callback() { ?>
		<fieldset><?php $checked = ( isset($this->woocommerce_coupon_cleanup_options['frequency_1']) && $this->woocommerce_coupon_cleanup_options['frequency_1'] === 'hourly' ) ? 'checked' : ''; ?>
		<label for="frequency_1-0"><input type="radio" name="woocommerce_coupon_cleanup_option_name[frequency_1]" id="frequency_1-0" value="hourly" <?php echo $checked; ?>> Hourly</label><br>
		<?php $checked = ( isset($this->woocommerce_coupon_cleanup_options['frequency_1']) && $this->woocommerce_coupon_cleanup_options['frequency_1'] === 'twicedaily' ) ? 'checked' : ''; ?>
		<label for="frequency_1-1"><input type="radio" name="woocommerce_coupon_cleanup_option_name[frequency_1]" id="frequency_1-1" value="twicedaily" <?php echo $checked; ?>> Twice a Day</label><br>
		<?php $checked = (!isset($this->woocommerce_coupon_cleanup_options['frequency_1']) || $this->woocommerce_coupon_cleanup_options['frequency_1'] === 'daily' ) ? 'checked' : ''; ?>
		<label for="frequency_1-2"><input type="radio" name="woocommerce_coupon_cleanup_option_name[frequency_1]" id="frequency_1-2" value="daily" <?php echo $checked; ?>> Daily</label><br>
		<?php
		/* $checked = ( isset( $this->woocommerce_coupon_cleanup_options['frequency_1'] ) && $this->woocommerce_coupon_cleanup_options['frequency_1'] === 'weekly' ) ? 'checked' : '' ; ?>
			<label for="frequency_1-3"><input type="radio" name="woocommerce_coupon_cleanup_option_name[frequency_1]" id="frequency_1-3" value="weekly" <?php echo $checked; ?>> Weekly</label></fieldset><p class="description">Frequency to check and remove</p> <?php
		 */
	}

}

new TCP_WooCommerceCouponCleanupAdmin();
