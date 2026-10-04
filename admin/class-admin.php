<?php
/**
 * Admin settings and assets.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_Admin
 */
class SPPA_Admin {

	/**
	 * Instance.
	 *
	 * @var SPPA_Admin|null
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return SPPA_Admin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'menu' ), 64 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'menu_mark' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'plugin_action_links_' . SPPA_BASENAME, array( $this, 'links' ) );
	}

	/**
	 * Settings link.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public function links( $links ) {
		$url = admin_url( 'admin.php?page=sppa-settings' );
		$pro = admin_url( 'admin.php?page=sppa-go-pro' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'optivo' ) . '</a>' );
		$links[] = '<a class="sppa-go-pro-link" href="' . esc_url( $pro ) . '">' . esc_html__( 'Go Pro', 'optivo' ) . '</a>';
		return $links;
	}

	/**
	 * SP mark in the admin menu on every screen.
	 */
	public function menu_mark() {
		wp_enqueue_style( 'sppa-menu', SPPA_URL . 'admin/assets/menu.css', array(), SPPA_VERSION );
	}

	/**
	 * Menu.
	 */
	public function menu() {
		$cap  = 'manage_woocommerce';
		$icon = SPPA_URL . 'assets/optivo-icon.png';

		add_menu_page(
			__( 'Optivo – Product Options for WooCommerce', 'optivo' ),
			__( 'Optivo', 'optivo' ),
			$cap,
			'sppa-addons',
			array( $this, 'redirect_to_groups' ),
			$icon,
			56
		);

		add_submenu_page(
			'sppa-addons',
			__( 'Product Options', 'optivo' ),
			__( 'Product Options', 'optivo' ),
			$cap,
			'edit.php?post_type=sppa_addon_group'
		);

		add_submenu_page(
			'sppa-addons',
			__( 'Go Pro', 'optivo' ),
			__( 'Go Pro', 'optivo' ),
			$cap,
			'sppa-go-pro',
			array( $this, 'go_pro_page' )
		);

		add_submenu_page(
			'sppa-addons',
			__( 'Settings', 'optivo' ),
			__( 'Settings', 'optivo' ),
			$cap,
			'sppa-settings',
			array( $this, 'settings_page' )
		);

		add_submenu_page(
			'sppa-addons',
			__( 'Import / Export', 'optivo' ),
			__( 'Import / Export', 'optivo' ),
			$cap,
			'sppa-transfer',
			array( SPPA_Import_Export::instance(), 'page' )
		);

		remove_submenu_page( 'sppa-addons', 'sppa-addons' );
	}

	/**
	 * Top-level fallback.
	 */
	public function redirect_to_groups() {
		wp_safe_redirect( admin_url( 'edit.php?post_type=sppa_addon_group' ) );
		exit;
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			'sppa_settings_group',
			'sppa_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$out = SPPA_Helpers::settings();
		$checks = array( 'show_in_cart', 'show_in_checkout', 'show_in_order', 'show_in_emails', 'show_prices_in_cart', 'show_summary', 'live_price' );
		foreach ( $checks as $key ) {
			$out[ $key ] = ! empty( $input[ $key ] ) ? 'yes' : 'no';
		}
		$out['upload_max_mb']         = absint( $input['upload_max_mb'] ?? 10 );
		$out['allowed_mime_types']    = sanitize_text_field( $input['allowed_mime_types'] ?? 'jpg,jpeg,png,gif,webp,pdf' );
		return $out;
	}

	/**
	 * Enqueue builder assets.
	 *
	 * @param string $hook Hook.
	 */
	public function assets( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$load   = false;

		$ok_screens = array(
			'product',
			'sppa_addon_group',
			'edit-sppa_addon_group',
			'toplevel_page_sppa-addons',
			'sppa-addons_page_sppa-go-pro',
			'sppa-addons_page_sppa-settings',
			'sppa-addons_page_sppa-transfer',
			'woocommerce_page_sppa-settings',
		);
		if ( $screen && in_array( $screen->id, $ok_screens, true ) ) {
			$load = true;
		}
		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], array( 'sppa-go-pro', 'sppa-settings', 'sppa-addons', 'sppa-transfer' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$load = true;
		}

		if ( ! $load ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'sppa-admin', SPPA_URL . 'admin/assets/admin.css', array(), SPPA_VERSION );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'sppa-admin', SPPA_URL . 'admin/assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), SPPA_VERSION, true );
		wp_localize_script(
			'sppa-admin',
			'sppaAdmin',
			array(
				'types'      => SPPA_Field_Types::all(),
				'operators'  => SPPA_Conditions::operators(),
				'priceTypes' => array(
					'none'       => __( 'No extra price', 'optivo' ),
					'flat'       => __( 'Fixed', 'optivo' ),
					'percentage' => __( 'Percentage of product', 'optivo' ),
					'quantity'   => __( 'Quantity × price', 'optivo' ),
				),
				'i18n'       => array(
					'group'         => __( 'Option group', 'optivo' ),
					'field'         => __( 'New field', 'optivo' ),
					'option'        => __( 'Option', 'optivo' ),
					'confirmDel'    => __( 'Remove this item?', 'optivo' ),
					'removeField'   => __( 'Remove this field?', 'optivo' ),
					'removeGroup'   => __( 'Remove this group?', 'optivo' ),
					'removeFieldTx' => __( 'The field and its options will be deleted from this group after you save.', 'optivo' ),
					'removeGroupTx' => __( 'This group and every field inside it will be removed after you save.', 'optivo' ),
					'keep'          => __( 'Keep it', 'optivo' ),
					'remove'        => __( 'Remove', 'optivo' ),
				),
			)
		);
	}

	/**
	 * Go Pro comparison. Premium is a separate plugin. Nothing here is locked.
	 */
	public function go_pro_page() {
		include SPPA_PATH . 'admin/views/go-pro.php';
	}

	/**
	 * Settings page markup.
	 */
	public function settings_page() {
		$s = SPPA_Helpers::settings();
		?>
		<div class="wrap sppa-settings">
			<div class="sppa-app-hero">
				<div>
					<h1><?php esc_html_e( 'Optivo – Product Options for WooCommerce', 'optivo' ); ?></h1>
					<p><?php esc_html_e( 'Add text, choice, and heading fields to WooCommerce products. Every feature in this plugin works with no license.', 'optivo' ); ?></p>
				</div>
				<span class="sppa-pill"><?php echo esc_html( 'v' . SPPA_VERSION ); ?></span>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'sppa_settings_group' ); ?>
				<div class="sppa-settings-grid">
					<div class="sppa-card">
						<h2><?php esc_html_e( 'Experience', 'optivo' ); ?></h2>
						<p class="sppa-card-sub"><?php esc_html_e( 'Control where selected options appear and how prices update.', 'optivo' ); ?></p>
						<div class="sppa-toggle-list">
							<label class="sppa-toggle"><span><?php esc_html_e( 'Show selections in cart', 'optivo' ); ?></span><span class="sppa-switch"><input type="checkbox" name="sppa_settings[show_in_cart]" value="yes" <?php checked( $s['show_in_cart'], 'yes' ); ?> /><span class="sppa-switch-ui"></span></span></label>
							<label class="sppa-toggle"><span><?php esc_html_e( 'Show selections at checkout', 'optivo' ); ?></span><span class="sppa-switch"><input type="checkbox" name="sppa_settings[show_in_checkout]" value="yes" <?php checked( $s['show_in_checkout'], 'yes' ); ?> /><span class="sppa-switch-ui"></span></span></label>
							<label class="sppa-toggle"><span><?php esc_html_e( 'Show on order received / My Account', 'optivo' ); ?></span><span class="sppa-switch"><input type="checkbox" name="sppa_settings[show_in_order]" value="yes" <?php checked( $s['show_in_order'], 'yes' ); ?> /><span class="sppa-switch-ui"></span></span></label>
							<label class="sppa-toggle"><span><?php esc_html_e( 'Include in emails', 'optivo' ); ?></span><span class="sppa-switch"><input type="checkbox" name="sppa_settings[show_in_emails]" value="yes" <?php checked( $s['show_in_emails'], 'yes' ); ?> /><span class="sppa-switch-ui"></span></span></label>
							<label class="sppa-toggle"><span><?php esc_html_e( 'Show add-on prices in cart', 'optivo' ); ?></span><span class="sppa-switch"><input type="checkbox" name="sppa_settings[show_prices_in_cart]" value="yes" <?php checked( $s['show_prices_in_cart'], 'yes' ); ?> /><span class="sppa-switch-ui"></span></span></label>
							<label class="sppa-toggle"><span><?php esc_html_e( 'Live price as options change', 'optivo' ); ?></span><span class="sppa-switch"><input type="checkbox" name="sppa_settings[live_price]" value="yes" <?php checked( $s['live_price'], 'yes' ); ?> /><span class="sppa-switch-ui"></span></span></label>
							<label class="sppa-toggle"><span><?php esc_html_e( 'Show configuration summary', 'optivo' ); ?></span><span class="sppa-switch"><input type="checkbox" name="sppa_settings[show_summary]" value="yes" <?php checked( $s['show_summary'], 'yes' ); ?> /><span class="sppa-switch-ui"></span></span></label>
						</div>
						<div style="margin-top:18px">
							<?php submit_button( __( 'Save settings', 'optivo' ) ); ?>
						</div>
					</div>
					<div class="sppa-card">
						<h2><?php esc_html_e( 'Setup', 'optivo' ); ?></h2>
						<p class="sppa-card-sub"><?php esc_html_e( 'Two places to attach options.', 'optivo' ); ?></p>
						<ul class="sppa-help-list">
							<li><?php echo wp_kses_post( __( 'Per product: edit a product → <strong>Product Options</strong> tab.', 'optivo' ) ); ?></li>
							<li><?php
							/* translators: %s: URL of the global add-on groups screen. */
							echo wp_kses_post( sprintf( __( 'Store-wide: <a href="%s">Global Add-On Groups</a>.', 'optivo' ), esc_url( admin_url( 'edit.php?post_type=sppa_addon_group' ) ) ) );
							?></li>
							<li><?php esc_html_e( 'Dropdown and radio fields use the Choices list under Settings.', 'optivo' ); ?></li>
						</ul>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Shared builder root used by product tab and global CPT.
	 *
	 * @param array  $groups Groups.
	 * @param string $name   Input name prefix, e.g. sppa_groups.
	 */
	public static function render_builder( $groups, $name = 'sppa_groups' ) {
		$groups = is_array( $groups ) ? $groups : array();
		include SPPA_PATH . 'admin/views/builder.php';
	}
}
