<?php
/**
 * Inventory admin page: list + add/edit form.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Admin_Inventory_Page
 */
class FMP_Admin_Inventory_Page {

	const PAGE_SLUG     = 'fmp-inventory';
	const SAVE_ACTION  = 'fmp_save_inventory';
	const DELETE_ACTION = 'fmp_delete_inventory';
	const SAVE_NONCE   = 'fmp_save_inventory';
	const DELETE_NONCE = 'fmp_delete_inventory';
	const POST_TYPE    = 'fmp_inventory_item';
	const PER_PAGE     = 20;

	/**
	 * Register admin_post handlers.
	 */
	public static function init() {
		add_action( 'admin_post_' . self::SAVE_ACTION, array( __CLASS__, 'handle_save' ), 10 );
		add_action( 'admin_post_' . self::DELETE_ACTION, array( __CLASS__, 'handle_delete' ), 10 );
	}

	/**
	 * Entry: render list or form based on action.
	 */
	public static function render() {
		if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'farm-management' ) );
		}

		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( ( $action === 'new' ) || ( $action === 'edit' && $id > 0 ) ) {
			$post_id = ( $action === 'edit' ) ? $id : 0;
			if ( $post_id ) {
				$post = get_post( $post_id );
				if ( ! $post || $post->post_type !== self::POST_TYPE ) {
					wp_die( esc_html__( 'Invalid item.', 'farm-management' ) );
				}
			}
			self::render_form( $post_id );
			return;
		}

		self::render_list();
	}

	/**
	 * Render list: title, Add button, paginated table.
	 * Supports filter=low_stock (quantity <= reorder_level).
	 */
	public static function render_list() {
		$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$filter = isset( $_GET['filter'] ) ? sanitize_text_field( wp_unslash( $_GET['filter'] ) ) : '';

		$add_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=new' );
		$base_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		if ( $filter ) {
			$base_url .= '&filter=' . rawurlencode( $filter );
		}
		$delete_nonce = self::DELETE_NONCE;

		if ( $filter === 'low_stock' ) {
			$query = new WP_Query( array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => self::PER_PAGE,
				'paged'          => $paged,
				'orderby'        => 'meta_value_num',
				'meta_key'       => '_fmp_quantity',
				'order'          => 'ASC',
				'meta_query'     => array(
					array(
						'key'   => '_fmp_is_low_stock',
						'value' => '1',
					),
				),
			) );
			$items       = $query->posts;
			$total       = (int) $query->found_posts;
			$total_pages = (int) $query->max_num_pages;
		} else {
			$query  = new WP_Query( array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => self::PER_PAGE,
				'paged'          => $paged,
				'orderby'        => 'meta_value_num',
				'meta_key'       => '_fmp_is_low_stock',
				'order'          => 'DESC',
				'meta_query'     => array(
					array(
						'key'     => '_fmp_is_low_stock',
						'compare' => 'EXISTS',
					),
				),
			) );
			$items_with_meta = $query->posts;
			$items_without   = get_posts( array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'     => '_fmp_is_low_stock',
						'compare' => 'NOT EXISTS',
					),
				),
			) );
			$items = array_merge( $items_with_meta, $items_without );
			$total = count( $items );
			$per_page = self::PER_PAGE;
			$offset = ( $paged - 1 ) * $per_page;
			$items = array_slice( $items, $offset, $per_page );
			$total_pages = (int) ceil( $total / $per_page );
		}
		if ( $total_pages < 1 ) {
			$total_pages = 1;
		}
		?>
		<div class="wrap fmp-admin-wrap fmp-inventory-list">
			<h1 class="wp-heading-inline"><?php
			if ( $filter === 'low_stock' ) {
				esc_html_e( 'Low Stock Items', 'farm-management' );
			} else {
				esc_html_e( 'Inventory', 'farm-management' );
			}
			?></h1>
			<a href="<?php echo esc_url( $add_url ); ?>" class="page-title-action"><?php esc_html_e( 'Add Item', 'farm-management' ); ?></a>
			<hr class="wp-header-end" />

			<?php
			if ( isset( $_GET['updated'] ) && $_GET['updated'] === '1' ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Item saved.', 'farm-management' ) . '</p></div>';
			}
			if ( isset( $_GET['deleted'] ) && $_GET['deleted'] === '1' ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Item deleted.', 'farm-management' ) . '</p></div>';
			}
			?>

			<?php if ( empty( $items ) ) : ?>
				<p><?php esc_html_e( 'No inventory items yet.', 'farm-management' ); ?></p>
				<p><a href="<?php echo esc_url( $add_url ); ?>" class="button button-primary"><?php esc_html_e( 'Add Item', 'farm-management' ); ?></a></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Item', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Category', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Quantity', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Reorder level', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Low stock', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Actions', 'farm-management' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $items as $post ) : ?>
							<?php
							$item_name  = get_post_meta( $post->ID, '_fmp_item_name', true );
							$item_name  = $item_name ?: get_the_title( $post->ID );
							$category   = get_post_meta( $post->ID, '_fmp_category', true );
							$quantity   = get_post_meta( $post->ID, '_fmp_quantity', true );
							$reorder    = get_post_meta( $post->ID, '_fmp_reorder_level', true );
							$is_low     = (int) get_post_meta( $post->ID, '_fmp_is_low_stock', true );
							$edit_url   = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=edit&id=' . $post->ID );
							$delete_url = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::DELETE_ACTION . '&id=' . $post->ID ), $delete_nonce, '_wpnonce' );
							?>
							<tr>
								<td><?php echo esc_html( $item_name ?: '—' ); ?></td>
								<td><?php echo esc_html( $category ?: '—' ); ?></td>
								<td><?php echo esc_html( $quantity !== '' ? $quantity : '—' ); ?></td>
								<td><?php echo esc_html( $reorder !== '' ? $reorder : '—' ); ?></td>
								<td><?php echo $is_low ? esc_html__( 'Yes', 'farm-management' ) : esc_html__( 'No', 'farm-management' ); ?></td>
								<td>
									<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'farm-management' ); ?></a>
									<?php if ( current_user_can( FMP_Capabilities::DELETE_RECORDS ) ) : ?>
										| <a href="<?php echo esc_url( $delete_url ); ?>" class="fmp-delete-row" data-confirm="<?php echo esc_attr__( 'Delete this item?', 'farm-management' ); ?>"><?php esc_html_e( 'Delete', 'farm-management' ); ?></a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php
				$big   = 999999999;
				$links = paginate_links( array(
					'base'      => str_replace( $big, '%#%', esc_url( $base_url . '&paged=' . $big ) ),
					'format'    => '&paged=%#%',
					'current'   => $paged,
					'total'     => $total_pages,
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
				) );
				if ( $links ) {
					echo '<div class="tablenav"><div class="tablenav-pages">' . $links . '</div></div>';
				}
				?>
			<?php endif; ?>
		</div>
		<?php
		if ( ! empty( $items ) ) {
			wp_add_inline_script( 'jquery', "jQuery(function($){ $('.fmp-delete-row').on('click', function(e){ if (! confirm($(this).data('confirm'))) e.preventDefault(); }); });" );
		}
	}

	/**
	 * Render add/edit form.
	 *
	 * @param int $post_id Post ID (0 for new).
	 */
	public static function render_form( $post_id = 0 ) {
		$is_edit = $post_id > 0;
		$item_name   = $is_edit ? get_post_meta( $post_id, '_fmp_item_name', true ) : '';
		$category    = $is_edit ? get_post_meta( $post_id, '_fmp_category', true ) : '';
		$quantity    = $is_edit ? get_post_meta( $post_id, '_fmp_quantity', true ) : '';
		$unit        = $is_edit ? get_post_meta( $post_id, '_fmp_unit', true ) : '';
		$reorder_level = $is_edit ? get_post_meta( $post_id, '_fmp_reorder_level', true ) : '';
		$supplier    = $is_edit ? get_post_meta( $post_id, '_fmp_supplier', true ) : '';
		$last_purchase = $is_edit ? get_post_meta( $post_id, '_fmp_last_purchase_date', true ) : '';
		$notes       = $is_edit ? get_post_meta( $post_id, '_fmp_notes', true ) : '';
		if ( $is_edit && $item_name === '' ) {
			$item_name = get_the_title( $post_id );
		}

		$form_action = admin_url( 'admin-post.php' );
		$list_url    = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		$page_heading = $is_edit ? __( 'Edit Item', 'farm-management' ) : __( 'Add Item', 'farm-management' );
		?>
		<div class="wrap fmp-admin-wrap fmp-inventory-form">
			<h1><?php echo esc_html( $page_heading ); ?></h1>
			<p><a href="<?php echo esc_url( $list_url ); ?>">&larr; <?php esc_html_e( 'Back to Inventory', 'farm-management' ); ?></a></p>

			<form method="post" action="<?php echo esc_url( $form_action ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SAVE_ACTION ); ?>" />
				<?php wp_nonce_field( self::SAVE_NONCE, 'fmp_inventory_nonce' ); ?>
				<?php if ( $post_id > 0 ) : ?>
					<input type="hidden" name="fmp_inventory_id" value="<?php echo esc_attr( $post_id ); ?>" />
				<?php endif; ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="fmp_item_name"><?php esc_html_e( 'Item name', 'farm-management' ); ?></label></th>
						<td><input type="text" id="fmp_item_name" name="fmp_item_name" value="<?php echo esc_attr( $item_name ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_category"><?php esc_html_e( 'Category', 'farm-management' ); ?></label></th>
						<td>
							<select id="fmp_category" name="fmp_category">
								<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
								<option value="feed" <?php selected( $category, 'feed' ); ?>><?php esc_html_e( 'Feed', 'farm-management' ); ?></option>
								<option value="medicine" <?php selected( $category, 'medicine' ); ?>><?php esc_html_e( 'Medicine', 'farm-management' ); ?></option>
								<option value="equipment" <?php selected( $category, 'equipment' ); ?>><?php esc_html_e( 'Equipment', 'farm-management' ); ?></option>
								<option value="other" <?php selected( $category, 'other' ); ?>><?php esc_html_e( 'Other', 'farm-management' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_quantity"><?php esc_html_e( 'Quantity', 'farm-management' ); ?></label></th>
						<td><input type="number" id="fmp_quantity" name="fmp_quantity" value="<?php echo esc_attr( $quantity ); ?>" min="0" step="any" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_unit"><?php esc_html_e( 'Unit', 'farm-management' ); ?></label></th>
						<td>
							<select id="fmp_unit" name="fmp_unit">
								<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
								<option value="kg" <?php selected( $unit, 'kg' ); ?>><?php esc_html_e( 'kg', 'farm-management' ); ?></option>
								<option value="l" <?php selected( $unit, 'l' ); ?>><?php esc_html_e( 'L', 'farm-management' ); ?></option>
								<option value="pcs" <?php selected( $unit, 'pcs' ); ?>><?php esc_html_e( 'pcs', 'farm-management' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_reorder_level"><?php esc_html_e( 'Reorder level', 'farm-management' ); ?></label></th>
						<td><input type="number" id="fmp_reorder_level" name="fmp_reorder_level" value="<?php echo esc_attr( $reorder_level ); ?>" min="0" step="any" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_supplier"><?php esc_html_e( 'Supplier', 'farm-management' ); ?></label></th>
						<td><input type="text" id="fmp_supplier" name="fmp_supplier" value="<?php echo esc_attr( $supplier ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_last_purchase_date"><?php esc_html_e( 'Last purchase date', 'farm-management' ); ?></label></th>
						<td><input type="date" id="fmp_last_purchase_date" name="fmp_last_purchase_date" value="<?php echo esc_attr( $last_purchase ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_notes"><?php esc_html_e( 'Notes', 'farm-management' ); ?></label></th>
						<td><textarea id="fmp_notes" name="fmp_notes" rows="4" class="large-text"><?php echo esc_textarea( $notes ); ?></textarea></td>
					</tr>
				</table>

				<p class="submit">
					<?php submit_button( $is_edit ? __( 'Update Item', 'farm-management' ) : __( 'Add Item', 'farm-management' ), 'primary', 'fmp_submit_inventory', false ); ?>
					<a href="<?php echo esc_url( $list_url ); ?>" class="button"><?php esc_html_e( 'Cancel', 'farm-management' ); ?></a>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle save: create or update CPT + meta + low-stock flag.
	 */
	public static function handle_save() {
		if ( ! isset( $_POST['fmp_inventory_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_inventory_nonce'] ) ), self::SAVE_NONCE ) ) {
			wp_die( esc_html__( 'Security check failed.', 'farm-management' ) );
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'farm-management' ) );
		}

		$post_id = isset( $_POST['fmp_inventory_id'] ) ? absint( $_POST['fmp_inventory_id'] ) : 0;
		$is_edit = $post_id > 0;

		if ( $is_edit ) {
			$post = get_post( $post_id );
			if ( ! $post || $post->post_type !== self::POST_TYPE ) {
				wp_die( esc_html__( 'Invalid item.', 'farm-management' ) );
			}
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				wp_die( esc_html__( 'You do not have permission to edit this item.', 'farm-management' ) );
			}
		}

		$item_name   = isset( $_POST['fmp_item_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_item_name'] ) ) : '';
		$category    = isset( $_POST['fmp_category'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_category'] ) ) : '';
		$quantity    = isset( $_POST['fmp_quantity'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_quantity'] ) ) : '';
		$unit        = isset( $_POST['fmp_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_unit'] ) ) : '';
		$reorder_level = isset( $_POST['fmp_reorder_level'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_reorder_level'] ) ) : '';
		$supplier    = isset( $_POST['fmp_supplier'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_supplier'] ) ) : '';
		$last_purchase = isset( $_POST['fmp_last_purchase_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_last_purchase_date'] ) ) : '';
		$notes       = isset( $_POST['fmp_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_notes'] ) ) : '';

		$title = $item_name !== '' ? $item_name : __( 'Item (no name)', 'farm-management' );

		// Custom page save: not inside save_post_*. New = wp_insert_post() once; edit = wp_update_post() once.
		if ( $is_edit ) {
			wp_update_post( array(
				'ID'         => $post_id,
				'post_title' => $title,
				'post_type'  => self::POST_TYPE,
			) );
		} else {
			$post_id = wp_insert_post( array(
				'post_title'  => $title,
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_author' => get_current_user_id(),
			) );
			if ( is_wp_error( $post_id ) ) {
				wp_die( esc_html__( 'Failed to create item.', 'farm-management' ) );
			}
		}

		update_post_meta( $post_id, '_fmp_item_name', $item_name );
		update_post_meta( $post_id, '_fmp_category', $category );
		update_post_meta( $post_id, '_fmp_quantity', $quantity );
		update_post_meta( $post_id, '_fmp_unit', $unit );
		update_post_meta( $post_id, '_fmp_reorder_level', $reorder_level );
		update_post_meta( $post_id, '_fmp_supplier', $supplier );
		update_post_meta( $post_id, '_fmp_last_purchase_date', $last_purchase );
		update_post_meta( $post_id, '_fmp_notes', $notes );

		$qty_num     = is_numeric( $quantity ) ? (float) $quantity : 0;
		$reorder_num = is_numeric( $reorder_level ) ? (float) $reorder_level : 0;
		$is_low_stock = ( $qty_num <= $reorder_num ) ? 1 : 0;
		update_post_meta( $post_id, '_fmp_is_low_stock', $is_low_stock );

		$redirect = add_query_arg( array( 'page' => self::PAGE_SLUG, 'updated' => '1' ), admin_url( 'admin.php' ) );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle delete: trash post.
	 */
	public static function handle_delete() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), self::DELETE_NONCE ) ) {
			wp_die( esc_html__( 'Security check failed.', 'farm-management' ) );
		}
		if ( ! current_user_can( FMP_Capabilities::DELETE_RECORDS ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'farm-management' ) );
		}

		$post_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( ! $post_id ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
			exit;
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
			exit;
		}
		if ( ! current_user_can( FMP_Capabilities::DELETE_RECORDS ) ) {
			wp_die( esc_html__( 'You do not have permission to delete this item.', 'farm-management' ) );
		}

		wp_trash_post( $post_id );

		$redirect = add_query_arg( array( 'page' => self::PAGE_SLUG, 'deleted' => '1' ), admin_url( 'admin.php' ) );
		wp_safe_redirect( $redirect );
		exit;
	}
}
