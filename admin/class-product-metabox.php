<?php
/**
 * Product data tab.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_Product_Metabox
 */
class SPPA_Product_Metabox {

	/**
	 * Instance.
	 *
	 * @var SPPA_Product_Metabox|null
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return SPPA_Product_Metabox
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
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'panel' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_object' ) );
	}

	/**
	 * Register tab.
	 *
	 * @param array $tabs Tabs.
	 * @return array
	 */
	public function tab( $tabs ) {
		$tabs['sppa_addons'] = array(
			'label'    => __( 'Product Options', 'optivo' ),
			'target'   => 'sppa_product_data',
			'class'    => array(),
			'priority' => 75,
		);
		return $tabs;
	}

	/**
	 * Panel markup.
	 */
	public function panel() {
		global $post;
		$product_id = $post ? $post->ID : 0;
		$groups     = SPPA_Repository::get_product_groups( $product_id );
		$exclude    = SPPA_Repository::excludes_global( $product_id );
		?>
		<div id="sppa_product_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<p class="form-field">
					<label for="sppa_exclude_global"><?php esc_html_e( 'Global add-ons', 'optivo' ); ?></label>
					<input type="checkbox" name="sppa_exclude_global" id="sppa_exclude_global" value="yes" <?php checked( $exclude ); ?> />
					<span class="description"><?php esc_html_e( 'Exclude this product from all global add-on groups.', 'optivo' ); ?></span>
				</p>
				<p class="form-field">
					<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sppa_export&scope=product&id=' . $product_id ), 'sppa_export' ) ); ?>"><?php esc_html_e( 'Export JSON', 'optivo' ); ?></a>
				</p>
				<form class="form-field" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" style="padding:0 12px 12px">
					<?php wp_nonce_field( 'sppa_import' ); ?>
					<input type="hidden" name="action" value="sppa_import" />
					<input type="hidden" name="scope" value="product" />
					<input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>" />
					<input type="file" name="sppa_import_file" accept=".json,application/json" />
					<button type="submit" class="button"><?php esc_html_e( 'Import JSON onto this product', 'optivo' ); ?></button>
				</form>
				<form class="form-field" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="padding:0 12px 12px">
					<?php wp_nonce_field( 'sppa_copy_groups' ); ?>
					<input type="hidden" name="action" value="sppa_copy_groups" />
					<input type="hidden" name="from_product" value="<?php echo esc_attr( $product_id ); ?>" />
					<label><?php esc_html_e( 'Copy these groups to product ID', 'optivo' ); ?>
						<input type="number" name="to_product" min="1" style="width:120px" />
					</label>
					<button type="submit" class="button"><?php esc_html_e( 'Copy', 'optivo' ); ?></button>
				</form>
			</div>
			<div class="options_group" style="padding:12px;">
				<?php SPPA_Admin::render_builder( $groups, 'sppa_groups' ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Save from classic product meta.
	 *
	 * @param int $product_id Product ID.
	 */
	public function save( $product_id ) {
		$this->persist( $product_id );
	}

	/**
	 * Save via CRUD object.
	 *
	 * @param WC_Product $product Product.
	 */
	public function save_object( $product ) {
		$this->persist( $product->get_id() );
	}

	/**
	 * Persist groups.
	 *
	 * @param int $product_id Product ID.
	 */
	protected function persist( $product_id ) {
		static $saved = array();
		if ( isset( $saved[ $product_id ] ) ) {
			return;
		}
		if ( ! isset( $_POST['sppa_groups'] ) && ! isset( $_POST['sppa_exclude_global'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		$saved[ $product_id ] = true;

		$groups = isset( $_POST['sppa_groups'] ) ? wp_unslash( $_POST['sppa_groups'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
		if ( ! is_array( $groups ) ) {
			$groups = array();
		}
		SPPA_Repository::save_product_groups( $product_id, $groups );

		$exclude = ! empty( $_POST['sppa_exclude_global'] ) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_post_meta( $product_id, SPPA_META_EXCLUDE_GLOBAL, $exclude );
	}
}
