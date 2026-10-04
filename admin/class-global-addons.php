<?php
/**
 * Global add-on groups CPT.
 *
 * @package AdvanceProductAddons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SPPA_Global_Addons
 */
class SPPA_Global_Addons {

	/**
	 * Instance.
	 *
	 * @var SPPA_Global_Addons|null
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return SPPA_Global_Addons
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
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'metaboxes' ) );
		add_action( 'save_post_sppa_addon_group', array( $this, 'save' ), 10, 2 );
		add_filter( 'manage_sppa_addon_group_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_sppa_addon_group_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_action( 'admin_action_sppa_duplicate_group', array( $this, 'duplicate' ) );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
	}

	/**
	 * Register CPT.
	 */
	public static function register_post_type() {
		register_post_type(
			'sppa_addon_group',
			array(
				'labels'              => array(
					'name'               => __( 'Product Options', 'optivo' ),
					'singular_name'      => __( 'Product Field Group', 'optivo' ),
					'add_new'            => __( 'Add Group', 'optivo' ),
					'add_new_item'       => __( 'Add Add-On Group', 'optivo' ),
					'edit_item'          => __( 'Edit Add-On Group', 'optivo' ),
					'new_item'           => __( 'New Add-On Group', 'optivo' ),
					'view_item'          => __( 'View Add-On Group', 'optivo' ),
					'search_items'       => __( 'Search Groups', 'optivo' ),
					'not_found'          => __( 'No groups found', 'optivo' ),
					'not_found_in_trash' => __( 'No groups in trash', 'optivo' ),
					'menu_name'          => __( 'Product Options', 'optivo' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'capability_type'     => 'product',
				'map_meta_cap'        => true,
				'supports'            => array( 'title', 'page-attributes' ),
				'has_archive'         => false,
				'rewrite'             => false,
			)
		);
	}

	/**
	 * Metaboxes.
	 */
	public function metaboxes() {
		add_meta_box(
			'sppa_group_assignment',
			__( 'Assignment rules', 'optivo' ),
			array( $this, 'box_assignment' ),
			'sppa_addon_group',
			'side',
			'high'
		);
		add_meta_box(
			'sppa_group_fields',
			__( 'Fields', 'optivo' ),
			array( $this, 'box_fields' ),
			'sppa_addon_group',
			'normal',
			'high'
		);
	}

	/**
	 * Assignment box.
	 *
	 * @param WP_Post $post Post.
	 */
	public function box_assignment( $post ) {
		$group = SPPA_Repository::hydrate_global( $post );
		wp_nonce_field( 'sppa_save_global', 'sppa_global_nonce' );
		?>
		<p>
			<label for="sppa_apply"><strong><?php esc_html_e( 'Apply to', 'optivo' ); ?></strong></label>
			<select name="sppa_apply" id="sppa_apply" class="widefat">
				<option value="all" <?php selected( $group['apply'], 'all' ); ?>><?php esc_html_e( 'All products', 'optivo' ); ?></option>
				<option value="products" <?php selected( $group['apply'], 'products' ); ?>><?php esc_html_e( 'Specific products', 'optivo' ); ?></option>
				<option value="categories" <?php selected( $group['apply'], 'categories' ); ?>><?php esc_html_e( 'Product categories', 'optivo' ); ?></option>
				<option value="tags" <?php selected( $group['apply'], 'tags' ); ?>><?php esc_html_e( 'Product tags', 'optivo' ); ?></option>
				<option value="types" <?php selected( $group['apply'], 'types' ); ?>><?php esc_html_e( 'Product types', 'optivo' ); ?></option>
			</select>
		</p>
		<p>
			<label><?php esc_html_e( 'Include product IDs (comma separated)', 'optivo' ); ?></label>
			<input type="text" class="widefat" name="sppa_include_ids" value="<?php echo esc_attr( implode( ',', $group['include_ids'] ) ); ?>" />
		</p>
		<p>
			<label><?php esc_html_e( 'Include category IDs', 'optivo' ); ?></label>
			<input type="text" class="widefat" name="sppa_include_cats" value="<?php echo esc_attr( implode( ',', $group['include_cats'] ) ); ?>" />
		</p>
		<p>
			<label><?php esc_html_e( 'Include tag IDs', 'optivo' ); ?></label>
			<input type="text" class="widefat" name="sppa_include_tags" value="<?php echo esc_attr( implode( ',', $group['include_tags'] ) ); ?>" />
		</p>
		<p>
			<label><?php esc_html_e( 'Product types', 'optivo' ); ?></label><br />
			<?php
			$types = array( 'simple', 'variable', 'grouped', 'external', 'subscription', 'variable-subscription', 'booking' );
			foreach ( $types as $type ) :
				?>
				<label><input type="checkbox" name="sppa_include_types[]" value="<?php echo esc_attr( $type ); ?>" <?php checked( in_array( $type, $group['include_types'], true ) ); ?> /> <?php echo esc_html( $type ); ?></label><br />
			<?php endforeach; ?>
			<span class="description"><?php esc_html_e( 'If any types are checked, this group only runs on those product types — even when Apply to is “All products”.', 'optivo' ); ?></span>
		</p>
		<p>
			<label><?php esc_html_e( 'Exclude product IDs', 'optivo' ); ?></label>
			<input type="text" class="widefat" name="sppa_exclude_ids" value="<?php echo esc_attr( implode( ',', $group['exclude_ids'] ) ); ?>" />
		</p>
		<p>
			<label><?php esc_html_e( 'Exclude category IDs', 'optivo' ); ?></label>
			<input type="text" class="widefat" name="sppa_exclude_cats" value="<?php echo esc_attr( implode( ',', $group['exclude_cats'] ) ); ?>" />
		</p>
		<p>
			<label><?php esc_html_e( 'Layout', 'optivo' ); ?></label>
			<select name="sppa_layout" class="widefat">
				<option value="vertical" <?php selected( $group['layout'], 'vertical' ); ?>><?php esc_html_e( 'Vertical', 'optivo' ); ?></option>
				<option value="horizontal" <?php selected( $group['layout'], 'horizontal' ); ?>><?php esc_html_e( 'Horizontal', 'optivo' ); ?></option>
				<option value="grid" <?php selected( $group['layout'], 'grid' ); ?>><?php esc_html_e( 'Grid', 'optivo' ); ?></option>
				<option value="two-columns" <?php selected( $group['layout'], 'two-columns' ); ?>><?php esc_html_e( 'Two columns', 'optivo' ); ?></option>
			</select>
		</p>
		<p>
			<label><?php esc_html_e( 'Style', 'optivo' ); ?></label>
			<select name="sppa_style" class="widefat">
				<option value="default" <?php selected( $group['style'], 'default' ); ?>><?php esc_html_e( 'Default', 'optivo' ); ?></option>
				<option value="cards" <?php selected( $group['style'], 'cards' ); ?>><?php esc_html_e( 'Cards', 'optivo' ); ?></option>
				<option value="pills" <?php selected( $group['style'], 'pills' ); ?>><?php esc_html_e( 'Pills', 'optivo' ); ?></option>
				<option value="swatches" <?php selected( $group['style'], 'swatches' ); ?>><?php esc_html_e( 'Swatches', 'optivo' ); ?></option>
			</select>
		</p>
		<?php
	}

	/**
	 * Fields box.
	 *
	 * @param WP_Post $post Post.
	 */
	public function box_fields( $post ) {
		$stored = get_post_meta( $post->ID, '_sppa_groups', true );
		$legacy = get_post_meta( $post->ID, '_sppa_fields', true );
		if ( is_array( $stored ) && ! empty( $stored ) ) {
			$groups = $stored;
		} elseif ( is_array( $legacy ) && ! empty( $legacy ) ) {
			$groups = array( SPPA_Repository::hydrate_global( $post ) );
		} else {
			$groups = array();
		}
		SPPA_Admin::render_builder( $groups, 'sppa_groups' );
	}

	/**
	 * Save CPT.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST['sppa_global_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sppa_global_nonce'] ) ), 'sppa_save_global' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$groups = isset( $_POST['sppa_groups'] ) ? wp_unslash( $_POST['sppa_groups'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! is_array( $groups ) ) {
			$groups = array();
		}
		$groups = array_values( $groups );

		$layout = isset( $_POST['sppa_layout'] ) ? sanitize_key( wp_unslash( $_POST['sppa_layout'] ) ) : 'vertical';
		$style  = isset( $_POST['sppa_style'] ) ? sanitize_key( wp_unslash( $_POST['sppa_style'] ) ) : 'default';
		if ( ! empty( $groups[0]['layout'] ) ) {
			$layout = $groups[0]['layout'];
		}
		if ( ! empty( $groups[0]['style'] ) ) {
			$style = $groups[0]['style'];
		}

		SPPA_Repository::save_global_meta(
			$post_id,
			array(
				'groups'        => $groups,
				'fields'        => $groups[0]['fields'] ?? array(),
				'name'          => $groups[0]['name'] ?? '',
				'description'   => $groups[0]['description'] ?? '',
				'status'        => $groups[0]['status'] ?? 'active',
				'priority'      => $groups[0]['priority'] ?? 10,
				'apply'         => isset( $_POST['sppa_apply'] ) ? sanitize_key( wp_unslash( $_POST['sppa_apply'] ) ) : 'all',
				'include_ids'   => sanitize_text_field( wp_unslash( $_POST['sppa_include_ids'] ?? '' ) ),
				'include_cats'  => sanitize_text_field( wp_unslash( $_POST['sppa_include_cats'] ?? '' ) ),
				'include_tags'  => sanitize_text_field( wp_unslash( $_POST['sppa_include_tags'] ?? '' ) ),
				'exclude_ids'   => sanitize_text_field( wp_unslash( $_POST['sppa_exclude_ids'] ?? '' ) ),
				'exclude_cats'  => sanitize_text_field( wp_unslash( $_POST['sppa_exclude_cats'] ?? '' ) ),
				'include_types' => isset( $_POST['sppa_include_types'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['sppa_include_types'] ) ) : array(),
				'layout'        => sanitize_key( $layout ),
				'style'         => sanitize_key( $style ),
			)
		);
	}

	/**
	 * Columns.
	 *
	 * @param array $cols Columns.
	 * @return array
	 */
	public function columns( $cols ) {
		$new = array();
		foreach ( $cols as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['sppa_status'] = __( 'Status', 'optivo' );
				$new['sppa_apply']  = __( 'Applies to', 'optivo' );
				$new['sppa_fields'] = __( 'Fields', 'optivo' );
			}
		}
		return $new;
	}

	/**
	 * Column content.
	 *
	 * @param string $col Column.
	 * @param int    $id  Post ID.
	 */
	public function column_content( $col, $id ) {
		$group = SPPA_Repository::get_global_group( $id );
		if ( ! $group ) {
			return;
		}
		if ( 'sppa_status' === $col ) {
			echo 'inactive' === ( $group['status'] ?? 'active' )
				? esc_html__( 'Inactive', 'optivo' )
				: esc_html__( 'Active', 'optivo' );
		}
		if ( 'sppa_apply' === $col ) {
			echo esc_html( $group['apply'] );
		}
		if ( 'sppa_fields' === $col ) {
			echo esc_html( count( $group['fields'] ) );
		}
	}

	/**
	 * Row actions.
	 *
	 * @param array   $actions Actions.
	 * @param WP_Post $post    Post.
	 * @return array
	 */
	public function row_actions( $actions, $post ) {
		if ( 'sppa_addon_group' !== $post->post_type ) {
			return $actions;
		}
		$url = wp_nonce_url(
			admin_url( 'admin.php?action=sppa_duplicate_group&post=' . $post->ID ),
			'sppa_duplicate_' . $post->ID
		);
		$actions['sppa_duplicate'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Duplicate', 'optivo' ) . '</a>';
		return $actions;
	}

	/**
	 * Duplicate a group.
	 */
	public function duplicate() {
		$post_id = absint( $_GET['post'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $post_id || ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'optivo' ) );
		}
		check_admin_referer( 'sppa_duplicate_' . $post_id );

		$post = get_post( $post_id );
		if ( ! $post || 'sppa_addon_group' !== $post->post_type ) {
			wp_die( esc_html__( 'Invalid group.', 'optivo' ) );
		}

		$new_id = wp_insert_post(
			array(
				'post_type'   => 'sppa_addon_group',
				'post_status' => 'draft',
				'post_title'  => $post->post_title . ' ' . __( '(Copy)', 'optivo' ),
				'menu_order'  => $post->menu_order,
			)
		);

		if ( $new_id ) {
			foreach ( get_post_meta( $post_id ) as $key => $values ) {
				foreach ( $values as $value ) {
					update_post_meta( $new_id, $key, maybe_unserialize( $value ) );
				}
			}
			wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
			exit;
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=sppa_addon_group' ) );
		exit;
	}
}
