<?php
/**
 * Crops admin page: list + add/edit form.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Admin_Crops_Page
 */
class FMP_Admin_Crops_Page {

	const PAGE_SLUG     = 'fmp-crops';
	const SAVE_ACTION  = 'fmp_save_crop';
	const DELETE_ACTION = 'fmp_delete_crop';
	const SAVE_NONCE   = 'fmp_save_crop';
	const DELETE_NONCE = 'fmp_delete_crop';
	const POST_TYPE    = 'fmp_crop';
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
					wp_die( esc_html__( 'Invalid crop.', 'farm-management' ) );
				}
			}
			self::render_form( $post_id );
			return;
		}

		self::render_list();
	}

	/**
	 * Render list: title, Add button, paginated table.
	 */
	public static function render_list() {
		$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$query  = new WP_Query( array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => self::PER_PAGE,
			'paged'          => $paged,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		$items  = $query->posts;
		$add_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=new' );
		$base_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		$delete_nonce = self::DELETE_NONCE;
		?>
		<div class="wrap fmp-admin-wrap fmp-crops-list">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Crops', 'farm-management' ); ?></h1>
			<a href="<?php echo esc_url( $add_url ); ?>" class="page-title-action"><?php esc_html_e( 'Add Crop', 'farm-management' ); ?></a>
			<hr class="wp-header-end" />

			<?php
			if ( isset( $_GET['updated'] ) && $_GET['updated'] === '1' ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Crop saved.', 'farm-management' ) . '</p></div>';
			}
			if ( isset( $_GET['deleted'] ) && $_GET['deleted'] === '1' ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Crop deleted.', 'farm-management' ) . '</p></div>';
			}
			?>

			<?php if ( empty( $items ) ) : ?>
				<p><?php esc_html_e( 'No crops yet.', 'farm-management' ); ?></p>
				<p><a href="<?php echo esc_url( $add_url ); ?>" class="button button-primary"><?php esc_html_e( 'Add Crop', 'farm-management' ); ?></a></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Crop', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Type', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Location', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Expected harvest', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Actions', 'farm-management' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $items as $post ) : ?>
							<?php
							$name     = get_post_meta( $post->ID, '_fmp_crop_name', true );
							$name     = $name ?: get_the_title( $post->ID );
							$type     = get_post_meta( $post->ID, '_fmp_crop_type', true );
							$location = get_post_meta( $post->ID, '_fmp_field_location', true );
							$status   = get_post_meta( $post->ID, '_fmp_crop_status', true );
							$harvest  = get_post_meta( $post->ID, '_fmp_expected_harvest_date', true );
							$edit_url   = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=edit&id=' . $post->ID );
							$delete_url = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::DELETE_ACTION . '&id=' . $post->ID ), $delete_nonce, '_wpnonce' );
							?>
							<tr>
								<td><?php echo esc_html( $name ?: '—' ); ?></td>
								<td><?php echo esc_html( $type ?: '—' ); ?></td>
								<td><?php echo esc_html( $location ?: '—' ); ?></td>
								<td><?php echo esc_html( $status ?: '—' ); ?></td>
								<td><?php echo esc_html( $harvest ?: '—' ); ?></td>
								<td>
									<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'farm-management' ); ?></a>
									<?php if ( current_user_can( FMP_Capabilities::DELETE_RECORDS ) ) : ?>
										| <a href="<?php echo esc_url( $delete_url ); ?>" class="fmp-delete-row" data-confirm="<?php echo esc_attr__( 'Delete this crop?', 'farm-management' ); ?>"><?php esc_html_e( 'Delete', 'farm-management' ); ?></a>
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
					'total'     => $query->max_num_pages,
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
		$crop_name     = $is_edit ? get_post_meta( $post_id, '_fmp_crop_name', true ) : '';
		$crop_type     = $is_edit ? get_post_meta( $post_id, '_fmp_crop_type', true ) : '';
		$planting_date = $is_edit ? get_post_meta( $post_id, '_fmp_planting_date', true ) : '';
		$expected_harvest = $is_edit ? get_post_meta( $post_id, '_fmp_expected_harvest_date', true ) : '';
		$field_location = $is_edit ? get_post_meta( $post_id, '_fmp_field_location', true ) : '';
		$status        = $is_edit ? get_post_meta( $post_id, '_fmp_crop_status', true ) : '';
		$notes         = $is_edit ? get_post_meta( $post_id, '_fmp_crop_notes', true ) : '';
		if ( $is_edit && $crop_name === '' ) {
			$crop_name = get_the_title( $post_id );
		}

		$form_action = admin_url( 'admin-post.php' );
		$list_url    = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		$page_heading = $is_edit ? __( 'Edit Crop', 'farm-management' ) : __( 'Add Crop', 'farm-management' );
		?>
		<div class="wrap fmp-admin-wrap fmp-crops-form">
			<h1><?php echo esc_html( $page_heading ); ?></h1>
			<p><a href="<?php echo esc_url( $list_url ); ?>">&larr; <?php esc_html_e( 'Back to Crops', 'farm-management' ); ?></a></p>

			<form method="post" action="<?php echo esc_url( $form_action ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SAVE_ACTION ); ?>" />
				<?php wp_nonce_field( self::SAVE_NONCE, 'fmp_save_crop_nonce' ); ?>
				<?php if ( $post_id > 0 ) : ?>
					<input type="hidden" name="fmp_crop_id" value="<?php echo esc_attr( $post_id ); ?>" />
				<?php endif; ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="fmp_crop_name"><?php esc_html_e( 'Crop name', 'farm-management' ); ?></label></th>
						<td><input type="text" id="fmp_crop_name" name="fmp_crop_name" value="<?php echo esc_attr( $crop_name ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_crop_type"><?php esc_html_e( 'Crop type', 'farm-management' ); ?></label></th>
						<td>
							<select id="fmp_crop_type" name="fmp_crop_type">
								<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
								<option value="veg" <?php selected( $crop_type, 'veg' ); ?>><?php esc_html_e( 'Vegetable', 'farm-management' ); ?></option>
								<option value="fruit" <?php selected( $crop_type, 'fruit' ); ?>><?php esc_html_e( 'Fruit', 'farm-management' ); ?></option>
								<option value="grain" <?php selected( $crop_type, 'grain' ); ?>><?php esc_html_e( 'Grain', 'farm-management' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_planting_date"><?php esc_html_e( 'Planting date', 'farm-management' ); ?></label></th>
						<td><input type="date" id="fmp_planting_date" name="fmp_planting_date" value="<?php echo esc_attr( $planting_date ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_expected_harvest_date"><?php esc_html_e( 'Expected harvest date', 'farm-management' ); ?></label></th>
						<td><input type="date" id="fmp_expected_harvest_date" name="fmp_expected_harvest_date" value="<?php echo esc_attr( $expected_harvest ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_field_location"><?php esc_html_e( 'Field / location', 'farm-management' ); ?></label></th>
						<td><input type="text" id="fmp_field_location" name="fmp_field_location" value="<?php echo esc_attr( $field_location ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_crop_status"><?php esc_html_e( 'Status', 'farm-management' ); ?></label></th>
						<td>
							<select id="fmp_crop_status" name="fmp_crop_status">
								<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
								<option value="planned" <?php selected( $status, 'planned' ); ?>><?php esc_html_e( 'Planned', 'farm-management' ); ?></option>
								<option value="planted" <?php selected( $status, 'planted' ); ?>><?php esc_html_e( 'Planted', 'farm-management' ); ?></option>
								<option value="harvested" <?php selected( $status, 'harvested' ); ?>><?php esc_html_e( 'Harvested', 'farm-management' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_crop_notes"><?php esc_html_e( 'Notes', 'farm-management' ); ?></label></th>
						<td><textarea id="fmp_crop_notes" name="fmp_crop_notes" rows="4" class="large-text"><?php echo esc_textarea( $notes ); ?></textarea></td>
					</tr>
				</table>

				<p class="submit">
					<?php submit_button( $is_edit ? __( 'Update Crop', 'farm-management' ) : __( 'Add Crop', 'farm-management' ), 'primary', 'fmp_submit_crop', false ); ?>
					<a href="<?php echo esc_url( $list_url ); ?>" class="button"><?php esc_html_e( 'Cancel', 'farm-management' ); ?></a>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle save: create or update CPT + meta.
	 */
	public static function handle_save() {
		if ( ! isset( $_POST['fmp_save_crop_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_save_crop_nonce'] ) ), self::SAVE_NONCE ) ) {
			wp_die( esc_html__( 'Security check failed.', 'farm-management' ) );
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'farm-management' ) );
		}

		$post_id  = isset( $_POST['fmp_crop_id'] ) ? absint( $_POST['fmp_crop_id'] ) : 0;
		$is_edit  = $post_id > 0;

		if ( $is_edit ) {
			$post = get_post( $post_id );
			if ( ! $post || $post->post_type !== self::POST_TYPE ) {
				wp_die( esc_html__( 'Invalid crop.', 'farm-management' ) );
			}
			if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
				wp_die( esc_html__( 'You do not have permission to edit this crop.', 'farm-management' ) );
			}
		}

		$crop_name         = isset( $_POST['fmp_crop_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_crop_name'] ) ) : '';
		$crop_type         = isset( $_POST['fmp_crop_type'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_crop_type'] ) ) : '';
		$planting_date     = isset( $_POST['fmp_planting_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_planting_date'] ) ) : '';
		$expected_harvest  = isset( $_POST['fmp_expected_harvest_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_expected_harvest_date'] ) ) : '';
		$field_location    = isset( $_POST['fmp_field_location'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_field_location'] ) ) : '';
		$status            = isset( $_POST['fmp_crop_status'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_crop_status'] ) ) : '';
		$notes             = isset( $_POST['fmp_crop_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_crop_notes'] ) ) : '';

		$title = $crop_name !== '' ? $crop_name : __( 'Crop (no name)', 'farm-management' );

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
				wp_die( esc_html__( 'Failed to create crop.', 'farm-management' ) );
			}
		}

		update_post_meta( $post_id, '_fmp_crop_name', $crop_name );
		update_post_meta( $post_id, '_fmp_crop_type', $crop_type );
		update_post_meta( $post_id, '_fmp_planting_date', $planting_date );
		update_post_meta( $post_id, '_fmp_expected_harvest_date', $expected_harvest );
		update_post_meta( $post_id, '_fmp_field_location', $field_location );
		update_post_meta( $post_id, '_fmp_crop_status', $status );
		update_post_meta( $post_id, '_fmp_crop_notes', $notes );

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
		if ( ! current_user_can( 'delete_posts' ) ) {
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
			wp_die( esc_html__( 'You do not have permission to delete this crop.', 'farm-management' ) );
		}

		wp_trash_post( $post_id );

		$redirect = add_query_arg( array( 'page' => self::PAGE_SLUG, 'deleted' => '1' ), admin_url( 'admin.php' ) );
		wp_safe_redirect( $redirect );
		exit;
	}
}
