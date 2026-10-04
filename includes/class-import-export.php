<?php
/**
 * JSON import / export.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_Import_Export
 */
class SPPA_Import_Export {

	/**
	 * Instance.
	 *
	 * @var SPPA_Import_Export|null
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return SPPA_Import_Export
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
		add_action( 'admin_post_sppa_export', array( $this, 'export' ) );
		add_action( 'admin_post_sppa_import', array( $this, 'import' ) );
		add_action( 'admin_post_sppa_copy_groups', array( $this, 'copy_groups' ) );
	}

	/**
	 * Export product or global groups as JSON.
	 */
	public function export() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'optivo' ) );
		}
		check_admin_referer( 'sppa_export' );

		$scope = sanitize_key( $_GET['scope'] ?? 'product' );
		$id    = absint( $_GET['id'] ?? 0 );

		if ( 'global' === $scope ) {
			$payload = array(
				'type'   => 'sppa_global_groups',
				'groups' => SPPA_Repository::get_global_groups(),
			);
			$filename = 'sppa-global-addons.json';
		} else {
			$payload = array(
				'type'           => 'sppa_product_groups',
				'product_id'     => $id,
				'exclude_global' => SPPA_Repository::excludes_global( $id ),
				'groups'         => SPPA_Repository::get_product_groups( $id ),
			);
			$filename = 'sppa-product-' . $id . '-addons.json';
		}

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Import JSON onto a product.
	 */
	public function import() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'optivo' ) );
		}
		check_admin_referer( 'sppa_import' );

		$product_id = absint( $_POST['product_id'] ?? 0 );
		$scope      = sanitize_key( $_POST['scope'] ?? 'product' );
		$file = $_FILES['sppa_import_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			wp_safe_redirect( wp_get_referer() );
			exit;
		}
		if ( ! empty( $file['size'] ) && (int) $file['size'] > 1024 * 1024 ) {
			wp_safe_redirect( add_query_arg( 'sppa_import', 'fail', wp_get_referer() ) );
			exit;
		}

		$json = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = json_decode( $json, true );
		if ( ! is_array( $data ) || empty( $data['groups'] ) || ! is_array( $data['groups'] ) ) {
			wp_safe_redirect( add_query_arg( 'sppa_import', 'fail', wp_get_referer() ) );
			exit;
		}

		if ( 'global' === $scope || 'sppa_global_groups' === ( $data['type'] ?? '' ) ) {
			$post_id = wp_insert_post(
				array(
					'post_type'   => 'sppa_addon_group',
					'post_status' => 'publish',
					'post_title'  => sanitize_text_field( $data['title'] ?? __( 'Imported add-ons', 'optivo' ) ),
				)
			);
			if ( $post_id ) {
				SPPA_Repository::save_global_meta(
					$post_id,
					array(
						'groups' => $data['groups'],
						'name'   => $data['groups'][0]['name'] ?? '',
					)
				);
			}
			wp_safe_redirect( admin_url( 'edit.php?post_type=sppa_addon_group&sppa_import=ok' ) );
			exit;
		}

		if ( ! $product_id ) {
			wp_safe_redirect( add_query_arg( 'sppa_import', 'fail', wp_get_referer() ) );
			exit;
		}

		$existing = SPPA_Repository::get_product_groups( $product_id );
		$incoming = SPPA_Helpers::regenerate_ids( $data['groups'] );
		SPPA_Repository::save_product_groups( $product_id, array_merge( $existing, $incoming ) );
		if ( isset( $data['exclude_global'] ) ) {
			update_post_meta( $product_id, SPPA_META_EXCLUDE_GLOBAL, $data['exclude_global'] ? 'yes' : 'no' );
		}

		wp_safe_redirect( add_query_arg( 'sppa_import', 'ok', wp_get_referer() ) );
		exit;
	}

	/**
	 * Copy one product's groups onto another product.
	 */
	public function copy_groups() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'optivo' ) );
		}
		check_admin_referer( 'sppa_copy_groups' );

		$from = absint( $_POST['from_product'] ?? 0 );
		$to   = absint( $_POST['to_product'] ?? 0 );
		if ( ! $from || ! $to || $from === $to || 'product' !== get_post_type( $to ) ) {
			wp_safe_redirect( add_query_arg( 'sppa_copy', 'fail', wp_get_referer() ) );
			exit;
		}

		$groups = SPPA_Helpers::regenerate_ids( SPPA_Repository::get_product_groups( $from ) );
		$dest   = SPPA_Repository::get_product_groups( $to );
		SPPA_Repository::save_product_groups( $to, array_merge( $dest, $groups ) );

		wp_safe_redirect( add_query_arg( 'sppa_copy', 'ok', get_edit_post_link( $to, 'raw' ) ) );
		exit;
	}

	/**
	 * Tools screen.
	 */
	public function page() {
		$export = wp_nonce_url( admin_url( 'admin-post.php?action=sppa_export&scope=global' ), 'sppa_export' );
		$ok     = isset( $_GET['sppa_import'] ) ? sanitize_key( wp_unslash( $_GET['sppa_import'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap sppa-settings">
			<div class="sppa-app-hero">
				<div>
					<h1><?php esc_html_e( 'Import and export', 'optivo' ); ?></h1>
					<p><?php esc_html_e( 'Move global groups between sites, or copy a product’s groups onto another product.', 'optivo' ); ?></p>
				</div>
			</div>
			<?php if ( 'ok' === $ok ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Import finished.', 'optivo' ); ?></p></div>
			<?php elseif ( 'fail' === $ok ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'That file could not be imported.', 'optivo' ); ?></p></div>
			<?php endif; ?>
			<div class="sppa-card">
				<h2><?php esc_html_e( 'Global groups', 'optivo' ); ?></h2>
				<p><a class="button button-primary" href="<?php echo esc_url( $export ); ?>"><?php esc_html_e( 'Download global add-ons JSON', 'optivo' ); ?></a></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<?php wp_nonce_field( 'sppa_import' ); ?>
					<input type="hidden" name="action" value="sppa_import" />
					<input type="hidden" name="scope" value="global" />
					<p>
						<input type="file" name="sppa_import_file" accept="application/json,.json" required />
					</p>
					<p><button type="submit" class="button"><?php esc_html_e( 'Import as a new global group', 'optivo' ); ?></button></p>
				</form>
			</div>
			<div class="sppa-card" style="margin-top:16px">
				<h2><?php esc_html_e( 'Copy product groups', 'optivo' ); ?></h2>
				<p class="sppa-card-sub"><?php esc_html_e( 'Paste two product IDs. Groups are appended to the destination with new field IDs.', 'optivo' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'sppa_copy_groups' ); ?>
					<input type="hidden" name="action" value="sppa_copy_groups" />
					<p>
						<label><?php esc_html_e( 'From product ID', 'optivo' ); ?>
							<input type="number" name="from_product" min="1" required />
						</label>
						<label><?php esc_html_e( 'To product ID', 'optivo' ); ?>
							<input type="number" name="to_product" min="1" required />
						</label>
					</p>
					<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Copy groups', 'optivo' ); ?></button></p>
				</form>
			</div>
		</div>
		<?php
	}
}
