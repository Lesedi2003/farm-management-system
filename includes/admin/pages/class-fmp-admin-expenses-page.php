<?php
/**
 * Expenses admin page: list + add/edit form.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Admin_Expenses_Page
 */
class FMP_Admin_Expenses_Page {

	const PAGE_SLUG     = 'fmp-expenses';
	const SAVE_ACTION  = 'fmp_save_expense';
	const DELETE_ACTION = 'fmp_delete_expense';
	const SAVE_NONCE   = 'fmp_save_expense';
	const DELETE_NONCE = 'fmp_delete_expense';
	const POST_TYPE    = 'fmp_expense';
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
					wp_die( esc_html__( 'Invalid expense.', 'farm-management' ) );
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
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		$items  = $query->posts;
		$add_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=new' );
		$base_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		$delete_nonce = self::DELETE_NONCE;
		?>
		<div class="wrap fmp-admin-wrap fmp-expenses-list">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Expenses', 'farm-management' ); ?></h1>
			<a href="<?php echo esc_url( $add_url ); ?>" class="page-title-action"><?php esc_html_e( 'Add Expense', 'farm-management' ); ?></a>
			<hr class="wp-header-end" />

			<?php
			if ( isset( $_GET['updated'] ) && $_GET['updated'] === '1' ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Expense saved.', 'farm-management' ) . '</p></div>';
			}
			if ( isset( $_GET['deleted'] ) && $_GET['deleted'] === '1' ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Expense deleted.', 'farm-management' ) . '</p></div>';
			}
			?>

			<?php if ( empty( $items ) ) : ?>
				<p><?php esc_html_e( 'No expenses yet.', 'farm-management' ); ?></p>
				<p><a href="<?php echo esc_url( $add_url ); ?>" class="button button-primary"><?php esc_html_e( 'Add Expense', 'farm-management' ); ?></a></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Title', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Date', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Category', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Amount (ZAR)', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Actions', 'farm-management' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $items as $post ) : ?>
							<?php
							$date     = get_post_meta( $post->ID, '_fmp_date', true );
							$category = get_post_meta( $post->ID, '_fmp_category', true );
							$amount   = get_post_meta( $post->ID, '_fmp_amount', true );
							$edit_url   = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=edit&id=' . $post->ID );
							$delete_url = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::DELETE_ACTION . '&id=' . $post->ID ), $delete_nonce, '_wpnonce' );
							?>
							<tr>
								<td><?php echo esc_html( get_the_title( $post->ID ) ?: '—' ); ?></td>
								<td><?php echo esc_html( $date ?: '—' ); ?></td>
								<td><?php echo esc_html( $category ?: '—' ); ?></td>
								<td><?php echo esc_html( $amount !== '' ? number_format_i18n( (float) $amount, 2 ) : '—' ); ?></td>
								<td>
									<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'farm-management' ); ?></a>
									<?php if ( current_user_can( FMP_Capabilities::DELETE_RECORDS ) ) : ?>
										| <a href="<?php echo esc_url( $delete_url ); ?>" class="fmp-delete-row" data-confirm="<?php echo esc_attr__( 'Delete this expense?', 'farm-management' ); ?>"><?php esc_html_e( 'Delete', 'farm-management' ); ?></a>
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
		$amount   = $is_edit ? get_post_meta( $post_id, '_fmp_amount', true ) : '';
		$date     = $is_edit ? get_post_meta( $post_id, '_fmp_date', true ) : '';
		$category = $is_edit ? get_post_meta( $post_id, '_fmp_category', true ) : '';
		$vendor   = $is_edit ? get_post_meta( $post_id, '_fmp_vendor', true ) : '';
		$receipt_id = $is_edit ? (int) get_post_meta( $post_id, '_fmp_receipt_id', true ) : 0;
		$notes    = $is_edit ? get_post_meta( $post_id, '_fmp_notes', true ) : '';

		$receipt_name = '';
		if ( $receipt_id ) {
			$file = get_attached_file( $receipt_id );
			$receipt_name = $file ? basename( $file ) : __( 'Attachment', 'farm-management' );
		}

		$form_action = admin_url( 'admin-post.php' );
		$list_url    = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		$page_heading = $is_edit ? __( 'Edit Expense', 'farm-management' ) : __( 'Add Expense', 'farm-management' );
		wp_enqueue_media();
		?>
		<div class="wrap fmp-admin-wrap fmp-expenses-form">
			<h1><?php echo esc_html( $page_heading ); ?></h1>
			<p><a href="<?php echo esc_url( $list_url ); ?>">&larr; <?php esc_html_e( 'Back to Expenses', 'farm-management' ); ?></a></p>

			<form method="post" action="<?php echo esc_url( $form_action ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SAVE_ACTION ); ?>" />
				<?php wp_nonce_field( self::SAVE_NONCE, 'fmp_expense_nonce' ); ?>
				<?php if ( $post_id > 0 ) : ?>
					<input type="hidden" name="fmp_expense_id" value="<?php echo esc_attr( $post_id ); ?>" />
				<?php endif; ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="fmp_amount"><?php esc_html_e( 'Amount (ZAR)', 'farm-management' ); ?></label></th>
						<td><input type="number" id="fmp_amount" name="fmp_amount" value="<?php echo esc_attr( $amount ); ?>" min="0" step="0.01" class="small-text" /> <span class="description"><?php esc_html_e( 'South African Rand', 'farm-management' ); ?></span></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_date"><?php esc_html_e( 'Date', 'farm-management' ); ?></label></th>
						<td><input type="date" id="fmp_date" name="fmp_date" value="<?php echo esc_attr( $date ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_category"><?php esc_html_e( 'Category', 'farm-management' ); ?></label></th>
						<td>
							<select id="fmp_category" name="fmp_category">
								<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
								<option value="feed" <?php selected( $category, 'feed' ); ?>><?php esc_html_e( 'Feed', 'farm-management' ); ?></option>
								<option value="vet" <?php selected( $category, 'vet' ); ?>><?php esc_html_e( 'Vet', 'farm-management' ); ?></option>
								<option value="fuel" <?php selected( $category, 'fuel' ); ?>><?php esc_html_e( 'Fuel', 'farm-management' ); ?></option>
								<option value="labour" <?php selected( $category, 'labour' ); ?>><?php esc_html_e( 'Labour', 'farm-management' ); ?></option>
								<option value="repairs" <?php selected( $category, 'repairs' ); ?>><?php esc_html_e( 'Repairs', 'farm-management' ); ?></option>
								<option value="other" <?php selected( $category, 'other' ); ?>><?php esc_html_e( 'Other', 'farm-management' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_vendor"><?php esc_html_e( 'Vendor', 'farm-management' ); ?></label></th>
						<td><input type="text" id="fmp_vendor" name="fmp_vendor" value="<?php echo esc_attr( $vendor ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Receipt', 'farm-management' ); ?></th>
						<td>
							<input type="hidden" name="fmp_receipt_id" id="fmp_receipt_id" value="<?php echo esc_attr( $receipt_id ); ?>" />
							<button type="button" class="button fmp-receipt-upload"><?php esc_html_e( 'Select / Upload receipt', 'farm-management' ); ?></button>
							<button type="button" class="button fmp-receipt-remove"><?php esc_html_e( 'Remove', 'farm-management' ); ?></button>
							<span class="fmp-receipt-name" <?php echo $receipt_name ? '' : ' style="display:none;"'; ?>><?php echo esc_html( $receipt_name ); ?></span>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_notes"><?php esc_html_e( 'Notes', 'farm-management' ); ?></label></th>
						<td><textarea id="fmp_notes" name="fmp_notes" rows="4" class="large-text"><?php echo esc_textarea( $notes ); ?></textarea></td>
					</tr>
				</table>

				<p class="submit">
					<?php submit_button( $is_edit ? __( 'Update Expense', 'farm-management' ) : __( 'Add Expense', 'farm-management' ), 'primary', 'fmp_submit_expense', false ); ?>
					<a href="<?php echo esc_url( $list_url ); ?>" class="button"><?php esc_html_e( 'Cancel', 'farm-management' ); ?></a>
				</p>
			</form>
		</div>
		<?php
		$receipt_script = "(function($){
			$(function(){
				var frame;
				$(document).on('click', '.fmp-receipt-upload', function(e){
					e.preventDefault();
					var input = $('#fmp_receipt_id');
					var span = $('.fmp-receipt-name');
					if (frame) { frame.open(); return; }
					frame = wp.media({
						title: '" . esc_js( __( 'Select or Upload Receipt', 'farm-management' ) ) . "',
						button: { text: '" . esc_js( __( 'Use this file', 'farm-management' ) ) . "' },
						library: { type: '' },
						multiple: false
					});
					frame.on('select', function(){
						var att = frame.state().get('selection').first().toJSON();
						input.val(att.id);
						span.text(att.filename || att.url).show();
					});
					frame.open();
				});
				$(document).on('click', '.fmp-receipt-remove', function(e){
					e.preventDefault();
					$('#fmp_receipt_id').val('');
					$('.fmp-receipt-name').hide().text('');
				});
			});
		})(jQuery);";
		wp_add_inline_script( 'jquery', $receipt_script, 'after' );
	}

	/**
	 * Handle save: create or update CPT + meta.
	 */
	public static function handle_save() {
		if ( ! isset( $_POST['fmp_expense_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_expense_nonce'] ) ), self::SAVE_NONCE ) ) {
			wp_die( esc_html__( 'Security check failed.', 'farm-management' ) );
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'farm-management' ) );
		}

		$post_id = isset( $_POST['fmp_expense_id'] ) ? absint( $_POST['fmp_expense_id'] ) : 0;
		$is_edit = $post_id > 0;

		if ( $is_edit ) {
			$post = get_post( $post_id );
			if ( ! $post || $post->post_type !== self::POST_TYPE ) {
				wp_die( esc_html__( 'Invalid expense.', 'farm-management' ) );
			}
			if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
				wp_die( esc_html__( 'You do not have permission to edit this expense.', 'farm-management' ) );
			}
		}

		$amount     = isset( $_POST['fmp_amount'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_amount'] ) ) : '';
		$date       = isset( $_POST['fmp_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_date'] ) ) : '';
		$category   = isset( $_POST['fmp_category'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_category'] ) ) : '';
		$vendor     = isset( $_POST['fmp_vendor'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_vendor'] ) ) : '';
		$receipt_id = isset( $_POST['fmp_receipt_id'] ) ? absint( $_POST['fmp_receipt_id'] ) : 0;
		$notes      = isset( $_POST['fmp_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_notes'] ) ) : '';

		$title = $date ? sprintf( __( 'Expense %s', 'farm-management' ), $date ) : __( 'Expense', 'farm-management' );
		if ( $amount !== '' ) {
			$title .= ' - R ' . number_format_i18n( (float) $amount, 2 ) . ' (ZAR)';
		}

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
				wp_die( esc_html__( 'Failed to create expense.', 'farm-management' ) );
			}
		}

		update_post_meta( $post_id, '_fmp_amount', $amount );
		update_post_meta( $post_id, '_fmp_date', $date );
		update_post_meta( $post_id, '_fmp_category', $category );
		update_post_meta( $post_id, '_fmp_vendor', $vendor );
		update_post_meta( $post_id, '_fmp_receipt_id', $receipt_id );
		update_post_meta( $post_id, '_fmp_notes', $notes );

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
			wp_die( esc_html__( 'You do not have permission to delete this expense.', 'farm-management' ) );
		}

		wp_trash_post( $post_id );

		$redirect = add_query_arg( array( 'page' => self::PAGE_SLUG, 'deleted' => '1' ), admin_url( 'admin.php' ) );
		wp_safe_redirect( $redirect );
		exit;
	}
}
