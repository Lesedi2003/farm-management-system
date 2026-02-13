<?php
/**
 * Tasks admin page: list + add/edit form.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Admin_Tasks_Page
 */
class FMP_Admin_Tasks_Page {

	const PAGE_SLUG     = 'fmp-tasks';
	const SAVE_ACTION  = 'fmp_save_task';
	const DELETE_ACTION = 'fmp_delete_task';
	const SAVE_NONCE   = 'fmp_save_task';
	const DELETE_NONCE = 'fmp_delete_task';
	const POST_TYPE    = 'fmp_task';
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
					wp_die( esc_html__( 'Invalid task.', 'farm-management' ) );
				}
			}
			self::render_form( $post_id );
			return;
		}

		self::render_list();
	}

	/**
	 * Render list: title, Add button, paginated table.
	 * Supports filter=due_soon (tasks due within 7 days, status != done).
	 */
	public static function render_list() {
		$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$filter = isset( $_GET['filter'] ) ? sanitize_text_field( wp_unslash( $_GET['filter'] ) ) : '';

		$query_args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => self::PER_PAGE,
			'paged'          => $paged,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( $filter === 'due_soon' ) {
			$today = gmdate( 'Y-m-d' );
			$end   = gmdate( 'Y-m-d', strtotime( '+7 days' ) );
			$query_args['orderby']  = 'meta_value';
			$query_args['meta_key'] = '_fmp_due_date';
			$query_args['order']    = 'ASC';
			$query_args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'     => '_fmp_due_date',
					'value'   => array( $today, $end ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
				array(
					'key'     => '_fmp_status',
					'value'   => 'done',
					'compare' => '!=',
				),
			);
		} else {
			$query_args['post_status'] = 'any';
		}

		$query   = new WP_Query( $query_args );
		$items   = $query->posts;
		$add_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=new' );
		$base_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		if ( $filter ) {
			$base_url .= '&filter=' . rawurlencode( $filter );
		}
		$delete_nonce = self::DELETE_NONCE;

		$users = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );
		$users_by_id = array();
		foreach ( $users as $u ) {
			$users_by_id[ $u->ID ] = $u->display_name;
		}
		?>
		<div class="wrap fmp-admin-wrap fmp-tasks-list">
			<h1 class="wp-heading-inline"><?php
			if ( $filter === 'due_soon' ) {
				esc_html_e( 'Tasks Due Soon (next 7 days)', 'farm-management' );
			} else {
				esc_html_e( 'Tasks', 'farm-management' );
			}
			?></h1>
			<a href="<?php echo esc_url( $add_url ); ?>" class="page-title-action"><?php esc_html_e( 'Add Task', 'farm-management' ); ?></a>
			<hr class="wp-header-end" />

			<?php
			if ( isset( $_GET['updated'] ) && $_GET['updated'] === '1' ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Task saved.', 'farm-management' ) . '</p></div>';
			}
			if ( isset( $_GET['deleted'] ) && $_GET['deleted'] === '1' ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Task deleted.', 'farm-management' ) . '</p></div>';
			}
			?>

			<?php if ( empty( $items ) ) : ?>
				<p><?php esc_html_e( 'No tasks yet.', 'farm-management' ); ?></p>
				<p><a href="<?php echo esc_url( $add_url ); ?>" class="button button-primary"><?php esc_html_e( 'Add Task', 'farm-management' ); ?></a></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Task', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Priority', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Due date', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Assigned to', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Actions', 'farm-management' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $items as $post ) : ?>
							<?php
							$priority   = get_post_meta( $post->ID, '_fmp_priority', true );
							$due_date   = get_post_meta( $post->ID, '_fmp_due_date', true );
							$status     = get_post_meta( $post->ID, '_fmp_status', true );
							$assigned   = (int) get_post_meta( $post->ID, '_fmp_assigned_to', true );
							$assigned_name = isset( $users_by_id[ $assigned ] ) ? $users_by_id[ $assigned ] : '—';
							$edit_url   = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=edit&id=' . $post->ID );
							$delete_url = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::DELETE_ACTION . '&id=' . $post->ID ), $delete_nonce, '_wpnonce' );
							?>
							<tr>
								<td><?php echo esc_html( get_the_title( $post->ID ) ?: '—' ); ?></td>
								<td><?php echo esc_html( $priority ?: '—' ); ?></td>
								<td><?php echo esc_html( $due_date ?: '—' ); ?></td>
								<td><?php echo esc_html( $status ?: '—' ); ?></td>
								<td><?php echo esc_html( $assigned_name ); ?></td>
								<td>
									<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'farm-management' ); ?></a>
									<?php if ( current_user_can( FMP_Capabilities::DELETE_RECORDS ) ) : ?>
										| <a href="<?php echo esc_url( $delete_url ); ?>" class="fmp-delete-row" data-confirm="<?php echo esc_attr__( 'Delete this task?', 'farm-management' ); ?>"><?php esc_html_e( 'Delete', 'farm-management' ); ?></a>
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
		$title_val  = $is_edit ? get_the_title( $post_id ) : '';
		$assigned_to = $is_edit ? (int) get_post_meta( $post_id, '_fmp_assigned_to', true ) : 0;
		$priority   = $is_edit ? get_post_meta( $post_id, '_fmp_priority', true ) : '';
		$due_date   = $is_edit ? get_post_meta( $post_id, '_fmp_due_date', true ) : '';
		$status     = $is_edit ? get_post_meta( $post_id, '_fmp_status', true ) : '';
		$related_animal = $is_edit ? (int) get_post_meta( $post_id, '_fmp_related_animal', true ) : 0;
		$notes      = $is_edit ? get_post_meta( $post_id, '_fmp_notes', true ) : '';

		$users   = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );
		$animals = get_posts( array(
			'post_type'      => 'fmp_animal',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		$form_action = admin_url( 'admin-post.php' );
		$list_url    = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		$page_heading = $is_edit ? __( 'Edit Task', 'farm-management' ) : __( 'Add Task', 'farm-management' );
		?>
		<div class="wrap fmp-admin-wrap fmp-tasks-form">
			<h1><?php echo esc_html( $page_heading ); ?></h1>
			<p><a href="<?php echo esc_url( $list_url ); ?>">&larr; <?php esc_html_e( 'Back to Tasks', 'farm-management' ); ?></a></p>

			<form method="post" action="<?php echo esc_url( $form_action ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SAVE_ACTION ); ?>" />
				<?php wp_nonce_field( self::SAVE_NONCE, 'fmp_task_nonce' ); ?>
				<?php if ( $post_id > 0 ) : ?>
					<input type="hidden" name="fmp_task_id" value="<?php echo esc_attr( $post_id ); ?>" />
				<?php endif; ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="fmp_task_title"><?php esc_html_e( 'Title', 'farm-management' ); ?></label></th>
						<td><input type="text" id="fmp_task_title" name="fmp_task_title" value="<?php echo esc_attr( $title_val ); ?>" class="large-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_assigned_to"><?php esc_html_e( 'Assigned to', 'farm-management' ); ?></label></th>
						<td>
							<select id="fmp_assigned_to" name="fmp_assigned_to">
								<option value=""><?php esc_html_e( '— Select user —', 'farm-management' ); ?></option>
								<?php foreach ( $users as $user ) : ?>
									<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $assigned_to, $user->ID ); ?>><?php echo esc_html( $user->display_name ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_priority"><?php esc_html_e( 'Priority', 'farm-management' ); ?></label></th>
						<td>
							<select id="fmp_priority" name="fmp_priority">
								<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
								<option value="low" <?php selected( $priority, 'low' ); ?>><?php esc_html_e( 'Low', 'farm-management' ); ?></option>
								<option value="med" <?php selected( $priority, 'med' ); ?>><?php esc_html_e( 'Medium', 'farm-management' ); ?></option>
								<option value="high" <?php selected( $priority, 'high' ); ?>><?php esc_html_e( 'High', 'farm-management' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_due_date"><?php esc_html_e( 'Due date', 'farm-management' ); ?></label></th>
						<td><input type="date" id="fmp_due_date" name="fmp_due_date" value="<?php echo esc_attr( $due_date ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_status"><?php esc_html_e( 'Status', 'farm-management' ); ?></label></th>
						<td>
							<select id="fmp_status" name="fmp_status">
								<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
								<option value="open" <?php selected( $status, 'open' ); ?>><?php esc_html_e( 'Open', 'farm-management' ); ?></option>
								<option value="in-progress" <?php selected( $status, 'in-progress' ); ?>><?php esc_html_e( 'In progress', 'farm-management' ); ?></option>
								<option value="done" <?php selected( $status, 'done' ); ?>><?php esc_html_e( 'Done', 'farm-management' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_related_animal"><?php esc_html_e( 'Related animal', 'farm-management' ); ?></label></th>
						<td>
							<select id="fmp_related_animal" name="fmp_related_animal">
								<option value=""><?php esc_html_e( '— None —', 'farm-management' ); ?></option>
								<?php foreach ( $animals as $animal ) : ?>
									<option value="<?php echo esc_attr( $animal->ID ); ?>" <?php selected( $related_animal, $animal->ID ); ?>><?php echo esc_html( get_the_title( $animal->ID ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_notes"><?php esc_html_e( 'Notes', 'farm-management' ); ?></label></th>
						<td><textarea id="fmp_notes" name="fmp_notes" rows="4" class="large-text"><?php echo esc_textarea( $notes ); ?></textarea></td>
					</tr>
				</table>

				<p class="submit">
					<?php submit_button( $is_edit ? __( 'Update Task', 'farm-management' ) : __( 'Add Task', 'farm-management' ), 'primary', 'fmp_submit_task', false ); ?>
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
		if ( ! isset( $_POST['fmp_task_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_task_nonce'] ) ), self::SAVE_NONCE ) ) {
			wp_die( esc_html__( 'Security check failed.', 'farm-management' ) );
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'farm-management' ) );
		}

		$post_id = isset( $_POST['fmp_task_id'] ) ? absint( $_POST['fmp_task_id'] ) : 0;
		$is_edit = $post_id > 0;

		if ( $is_edit ) {
			$post = get_post( $post_id );
			if ( ! $post || $post->post_type !== self::POST_TYPE ) {
				wp_die( esc_html__( 'Invalid task.', 'farm-management' ) );
			}
			if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
				wp_die( esc_html__( 'You do not have permission to edit this task.', 'farm-management' ) );
			}
		}

		$title_val      = isset( $_POST['fmp_task_title'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_task_title'] ) ) : '';
		$assigned_to   = isset( $_POST['fmp_assigned_to'] ) ? absint( $_POST['fmp_assigned_to'] ) : 0;
		$priority      = isset( $_POST['fmp_priority'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_priority'] ) ) : '';
		$due_date      = isset( $_POST['fmp_due_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_due_date'] ) ) : '';
		$status        = isset( $_POST['fmp_status'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_status'] ) ) : '';
		$related_animal = isset( $_POST['fmp_related_animal'] ) ? absint( $_POST['fmp_related_animal'] ) : 0;
		$notes         = isset( $_POST['fmp_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_notes'] ) ) : '';

		$title = $title_val !== '' ? $title_val : __( 'Task (no title)', 'farm-management' );

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
				wp_die( esc_html__( 'Failed to create task.', 'farm-management' ) );
			}
		}

		update_post_meta( $post_id, '_fmp_assigned_to', $assigned_to );
		update_post_meta( $post_id, '_fmp_priority', $priority );
		update_post_meta( $post_id, '_fmp_due_date', $due_date );
		update_post_meta( $post_id, '_fmp_status', $status );
		update_post_meta( $post_id, '_fmp_related_animal', $related_animal );
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
			wp_die( esc_html__( 'You do not have permission to delete this task.', 'farm-management' ) );
		}

		wp_trash_post( $post_id );

		$redirect = add_query_arg( array( 'page' => self::PAGE_SLUG, 'deleted' => '1' ), admin_url( 'admin.php' ) );
		wp_safe_redirect( $redirect );
		exit;
	}
}
