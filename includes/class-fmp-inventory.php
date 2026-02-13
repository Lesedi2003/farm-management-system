<?php
/**
 * Inventory CPT: meta box, list columns, low-stock sorting.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Inventory
 */
class FMP_Inventory {

	const NONCE_ACTION = 'fmp_save_inventory';

	/**
	 * Constructor. Hooks into WordPress.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ), 10 );
		add_action( 'save_post_fmp_inventory_item', array( $this, 'save_meta' ), 10, 2 );
		add_filter( 'manage_fmp_inventory_item_posts_columns', array( $this, 'set_columns' ), 10 );
		add_action( 'manage_fmp_inventory_item_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'sort_low_stock_first' ), 10, 1 );
		add_action( 'load-edit.php', array( $this, 'maybe_sync_low_stock_meta' ), 10 );
	}

	/**
	 * Ensure all inventory items have _fmp_is_low_stock set (for ordering and legacy items).
	 */
	public function maybe_sync_low_stock_meta() {
		if ( ! isset( $_GET['post_type'] ) || sanitize_key( wp_unslash( $_GET['post_type'] ) ) !== 'fmp_inventory_item' ) {
			return;
		}
		$posts = get_posts( array(
			'post_type'      => 'fmp_inventory_item',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_fmp_is_low_stock',
					'compare' => 'NOT EXISTS',
				),
			),
		) );
		foreach ( $posts as $post_id ) {
			$qty     = (float) get_post_meta( $post_id, '_fmp_quantity', true );
			$reorder = (float) get_post_meta( $post_id, '_fmp_reorder_level', true );
			$is_low  = ( $reorder !== 0.0 && $qty <= $reorder ) || ( $reorder === 0.0 && $qty <= 0 );
			update_post_meta( $post_id, '_fmp_is_low_stock', $is_low ? 1 : 0 );
		}
	}

	/**
	 * Register Inventory item details meta box.
	 */
	public function register_meta_box() {
		add_meta_box(
			'fmp_inventory_details',
			__( 'Item details', 'farm-management' ),
			array( $this, 'render_meta_box' ),
			'fmp_inventory_item',
			'normal',
			'default'
		);
	}

	/**
	 * Render Inventory item details meta box.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( self::NONCE_ACTION, 'fmp_inventory_nonce' );

		$item_name          = get_post_meta( $post->ID, '_fmp_item_name', true );
		$category           = get_post_meta( $post->ID, '_fmp_category', true );
		$quantity           = get_post_meta( $post->ID, '_fmp_quantity', true );
		$unit               = get_post_meta( $post->ID, '_fmp_unit', true );
		$reorder_level      = get_post_meta( $post->ID, '_fmp_reorder_level', true );
		$supplier           = get_post_meta( $post->ID, '_fmp_supplier', true );
		$last_purchase_date = get_post_meta( $post->ID, '_fmp_last_purchase_date', true );
		$notes              = get_post_meta( $post->ID, '_fmp_notes', true );

		if ( $item_name === '' && $post->post_title !== '' ) {
			$item_name = $post->post_title;
		}
		?>
		<div class="fmp-meta-box fmp-inventory-details">
			<div class="fmp-meta-row">
				<label for="fmp_item_name"><?php esc_html_e( 'Item name', 'farm-management' ); ?></label>
				<input type="text" id="fmp_item_name" name="fmp_item_name" value="<?php echo esc_attr( $item_name ); ?>" class="regular-text" />
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_category"><?php esc_html_e( 'Category', 'farm-management' ); ?></label>
				<select id="fmp_category" name="fmp_category">
					<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
					<option value="feed" <?php selected( $category, 'feed' ); ?>><?php esc_html_e( 'Feed', 'farm-management' ); ?></option>
					<option value="medicine" <?php selected( $category, 'medicine' ); ?>><?php esc_html_e( 'Medicine', 'farm-management' ); ?></option>
					<option value="equipment" <?php selected( $category, 'equipment' ); ?>><?php esc_html_e( 'Equipment', 'farm-management' ); ?></option>
					<option value="other" <?php selected( $category, 'other' ); ?>><?php esc_html_e( 'Other', 'farm-management' ); ?></option>
				</select>
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_quantity"><?php esc_html_e( 'Quantity', 'farm-management' ); ?></label>
				<input type="number" id="fmp_quantity" name="fmp_quantity" value="<?php echo esc_attr( $quantity ); ?>" min="0" step="any" class="small-text" />
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_unit"><?php esc_html_e( 'Unit', 'farm-management' ); ?></label>
				<select id="fmp_unit" name="fmp_unit">
					<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
					<option value="kg" <?php selected( $unit, 'kg' ); ?>><?php esc_html_e( 'kg', 'farm-management' ); ?></option>
					<option value="l" <?php selected( $unit, 'l' ); ?>><?php esc_html_e( 'L', 'farm-management' ); ?></option>
					<option value="pcs" <?php selected( $unit, 'pcs' ); ?>><?php esc_html_e( 'pcs', 'farm-management' ); ?></option>
				</select>
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_reorder_level"><?php esc_html_e( 'Reorder level', 'farm-management' ); ?></label>
				<input type="number" id="fmp_reorder_level" name="fmp_reorder_level" value="<?php echo esc_attr( $reorder_level ); ?>" min="0" step="any" class="small-text" />
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_supplier"><?php esc_html_e( 'Supplier', 'farm-management' ); ?></label>
				<input type="text" id="fmp_supplier" name="fmp_supplier" value="<?php echo esc_attr( $supplier ); ?>" class="regular-text" />
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_last_purchase_date"><?php esc_html_e( 'Last purchase date', 'farm-management' ); ?></label>
				<input type="date" id="fmp_last_purchase_date" name="fmp_last_purchase_date" value="<?php echo esc_attr( $last_purchase_date ); ?>" />
			</div>
			<div class="fmp-meta-row fmp-meta-row-full">
				<label for="fmp_notes"><?php esc_html_e( 'Notes', 'farm-management' ); ?></label>
				<textarea id="fmp_notes" name="fmp_notes" rows="4" class="large-text"><?php echo esc_textarea( $notes ); ?></textarea>
			</div>
		</div>
		<?php
	}

	/**
	 * Save inventory meta and compute low-stock flag.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_meta( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( get_post_type( $post_id ) !== 'fmp_inventory_item' ) {
			return;
		}
		if ( ! isset( $_POST['fmp_inventory_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_inventory_nonce'] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
			return;
		}

		// Guard: do not call wp_update_post() inside save_post_* without guards (avoids re-entry loop).
		static $saving = false;
		if ( $saving ) {
			return;
		}
		$saving = true;

		try {
			$item_name          = isset( $_POST['fmp_item_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_item_name'] ) ) : '';
			$category           = isset( $_POST['fmp_category'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_category'] ) ) : '';
			$quantity           = isset( $_POST['fmp_quantity'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_quantity'] ) ) : '';
			$unit               = isset( $_POST['fmp_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_unit'] ) ) : '';
			$reorder_level      = isset( $_POST['fmp_reorder_level'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_reorder_level'] ) ) : '';
			$supplier           = isset( $_POST['fmp_supplier'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_supplier'] ) ) : '';
			$last_purchase_date = isset( $_POST['fmp_last_purchase_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_last_purchase_date'] ) ) : '';
			$notes              = isset( $_POST['fmp_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_notes'] ) ) : '';

			update_post_meta( $post_id, '_fmp_item_name', $item_name );
			update_post_meta( $post_id, '_fmp_category', $category );
			update_post_meta( $post_id, '_fmp_quantity', $quantity );
			update_post_meta( $post_id, '_fmp_unit', $unit );
			update_post_meta( $post_id, '_fmp_reorder_level', $reorder_level );
			update_post_meta( $post_id, '_fmp_supplier', $supplier );
			update_post_meta( $post_id, '_fmp_last_purchase_date', $last_purchase_date );
			update_post_meta( $post_id, '_fmp_notes', $notes );

			$qty_num     = is_numeric( $quantity ) ? (float) $quantity : 0;
			$reorder_num = is_numeric( $reorder_level ) ? (float) $reorder_level : 0;
			$is_low_stock = ( $qty_num <= $reorder_num ) ? 1 : 0;
			update_post_meta( $post_id, '_fmp_is_low_stock', $is_low_stock );

			if ( $item_name !== '' ) {
				remove_action( 'save_post_fmp_inventory_item', array( $this, 'save_meta' ), 10 );
				wp_update_post( array(
					'ID'         => $post_id,
					'post_title' => $item_name,
				) );
				add_action( 'save_post_fmp_inventory_item', array( $this, 'save_meta' ), 10, 2 );
			}
		} finally {
			$saving = false;
		}
	}

	/**
	 * Set list table columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function set_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( $key === 'title' ) {
				$new['fmp_category']      = __( 'Category', 'farm-management' );
				$new['fmp_quantity']       = __( 'Quantity', 'farm-management' );
				$new['fmp_reorder_level']  = __( 'Reorder Level', 'farm-management' );
				$new['fmp_low_stock']      = __( 'Low Stock', 'farm-management' );
			}
		}
		if ( isset( $new['date'] ) ) {
			unset( $new['date'] );
		}
		return $new;
	}

	/**
	 * Output list table column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'fmp_category':
				$category = get_post_meta( $post_id, '_fmp_category', true );
				$labels = array(
					'feed'      => __( 'Feed', 'farm-management' ),
					'medicine'  => __( 'Medicine', 'farm-management' ),
					'equipment' => __( 'Equipment', 'farm-management' ),
					'other'     => __( 'Other', 'farm-management' ),
				);
				echo esc_html( isset( $labels[ $category ] ) ? $labels[ $category ] : ( $category ?: '—' ) );
				break;
			case 'fmp_quantity':
				$quantity = get_post_meta( $post_id, '_fmp_quantity', true );
				$unit     = get_post_meta( $post_id, '_fmp_unit', true );
				echo esc_html( $quantity !== '' ? $quantity . ( $unit ? ' ' . $unit : '' ) : '—' );
				break;
			case 'fmp_reorder_level':
				echo esc_html( get_post_meta( $post_id, '_fmp_reorder_level', true ) ?: '—' );
				break;
			case 'fmp_low_stock':
				$qty   = (float) get_post_meta( $post_id, '_fmp_quantity', true );
				$reorder = (float) get_post_meta( $post_id, '_fmp_reorder_level', true );
				$is_low = ( $reorder !== 0.0 && $qty <= $reorder ) || ( $reorder === 0.0 && $qty <= 0 );
				echo $is_low ? esc_html__( 'Yes', 'farm-management' ) : esc_html__( 'No', 'farm-management' );
				break;
		}
	}

	/**
	 * Sort inventory list so low-stock items appear first.
	 *
	 * @param WP_Query $query Query object.
	 */
	public function sort_low_stock_first( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || $screen->id !== 'edit-fmp_inventory_item' ) {
			return;
		}

		$query->set( 'meta_key', '_fmp_is_low_stock' );
		$query->set( 'orderby', 'meta_value_num title' );
		$query->set( 'order', 'DESC' );
	}
}
