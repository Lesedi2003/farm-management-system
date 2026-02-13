<?php
/**
 * Tasks CPT: meta box, list columns, filters, quick-add.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Tasks
 */
class FMP_Tasks {

	const NONCE_ACTION = 'fmp_save_task';
	const QUICK_ADD_NONCE = 'fmp_quick_add_task';

	/**
	 * Constructor. Hooks into WordPress.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ), 10 );
		add_action( 'save_post_fmp_task', array( $this, 'save_meta' ), 10, 2 );
		add_filter( 'manage_fmp_task_posts_columns', array( $this, 'set_columns' ), 10 );
		add_action( 'manage_fmp_task_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'render_filters' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'filter_query' ), 10, 1 );
		add_action( 'all_admin_notices', array( $this, 'render_quick_add_form' ), 10 );
		add_action( 'admin_post_fmp_quick_add_task', array( $this, 'handle_quick_add' ), 10 );
	}

	/**
	 * Register Task details meta box.
	 */
	public function register_meta_box() {
		add_meta_box(
			'fmp_task_details',
			__( 'Task details', 'farm-management' ),
			array( $this, 'render_meta_box' ),
			'fmp_task',
			'normal',
			'default'
		);
	}

	/**
	 * Render Task details meta box.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( self::NONCE_ACTION, 'fmp_task_nonce' );

		$assigned_to   = (int) get_post_meta( $post->ID, '_fmp_assigned_to', true );
		$priority      = get_post_meta( $post->ID, '_fmp_priority', true );
		$due_date      = get_post_meta( $post->ID, '_fmp_due_date', true );
		$status        = get_post_meta( $post->ID, '_fmp_status', true );
		$related_animal = (int) get_post_meta( $post->ID, '_fmp_related_animal', true );
		$notes         = get_post_meta( $post->ID, '_fmp_notes', true );

		$users = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );
		$animals = get_posts( array(
			'post_type'      => 'fmp_animal',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		?>
		<div class="fmp-meta-box fmp-task-details">
			<div class="fmp-meta-row">
				<label for="fmp_assigned_to"><?php esc_html_e( 'Assigned to', 'farm-management' ); ?></label>
				<select id="fmp_assigned_to" name="fmp_assigned_to">
					<option value=""><?php esc_html_e( '— Select user —', 'farm-management' ); ?></option>
					<?php foreach ( $users as $user ) : ?>
						<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $assigned_to, $user->ID ); ?>>
							<?php echo esc_html( $user->display_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_priority"><?php esc_html_e( 'Priority', 'farm-management' ); ?></label>
				<select id="fmp_priority" name="fmp_priority">
					<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
					<option value="low" <?php selected( $priority, 'low' ); ?>><?php esc_html_e( 'Low', 'farm-management' ); ?></option>
					<option value="med" <?php selected( $priority, 'med' ); ?>><?php esc_html_e( 'Medium', 'farm-management' ); ?></option>
					<option value="high" <?php selected( $priority, 'high' ); ?>><?php esc_html_e( 'High', 'farm-management' ); ?></option>
				</select>
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_due_date"><?php esc_html_e( 'Due date', 'farm-management' ); ?></label>
				<input type="date" id="fmp_due_date" name="fmp_due_date" value="<?php echo esc_attr( $due_date ); ?>" />
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_status"><?php esc_html_e( 'Status', 'farm-management' ); ?></label>
				<select id="fmp_status" name="fmp_status">
					<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
					<option value="open" <?php selected( $status, 'open' ); ?>><?php esc_html_e( 'Open', 'farm-management' ); ?></option>
					<option value="in-progress" <?php selected( $status, 'in-progress' ); ?>><?php esc_html_e( 'In progress', 'farm-management' ); ?></option>
					<option value="done" <?php selected( $status, 'done' ); ?>><?php esc_html_e( 'Done', 'farm-management' ); ?></option>
				</select>
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_related_animal"><?php esc_html_e( 'Related animal', 'farm-management' ); ?></label>
				<select id="fmp_related_animal" name="fmp_related_animal">
					<option value=""><?php esc_html_e( '— None —', 'farm-management' ); ?></option>
					<?php foreach ( $animals as $animal ) : ?>
						<option value="<?php echo esc_attr( $animal->ID ); ?>" <?php selected( $related_animal, $animal->ID ); ?>>
							<?php echo esc_html( get_the_title( $animal->ID ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="fmp-meta-row fmp-meta-row-full">
				<label for="fmp_notes"><?php esc_html_e( 'Notes', 'farm-management' ); ?></label>
				<textarea id="fmp_notes" name="fmp_notes" rows="4" class="large-text"><?php echo esc_textarea( $notes ); ?></textarea>
			</div>
		</div>
		<?php
	}

	/**
	 * Save task meta.
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
		if ( ! isset( $_POST['fmp_task_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_task_nonce'] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
			return;
		}

		$assigned_to    = isset( $_POST['fmp_assigned_to'] ) ? absint( $_POST['fmp_assigned_to'] ) : 0;
		$priority       = isset( $_POST['fmp_priority'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_priority'] ) ) : '';
		$due_date       = isset( $_POST['fmp_due_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_due_date'] ) ) : '';
		$status         = isset( $_POST['fmp_status'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_status'] ) ) : '';
		$related_animal = isset( $_POST['fmp_related_animal'] ) ? absint( $_POST['fmp_related_animal'] ) : 0;
		$notes          = isset( $_POST['fmp_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_notes'] ) ) : '';

		update_post_meta( $post_id, '_fmp_assigned_to', $assigned_to );
		update_post_meta( $post_id, '_fmp_priority', $priority );
		update_post_meta( $post_id, '_fmp_due_date', $due_date );
		update_post_meta( $post_id, '_fmp_status', $status );
		update_post_meta( $post_id, '_fmp_related_animal', $related_animal );
		update_post_meta( $post_id, '_fmp_notes', $notes );
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
				$new['fmp_priority']   = __( 'Priority', 'farm-management' );
				$new['fmp_due_date']   = __( 'Due Date', 'farm-management' );
				$new['fmp_status']     = __( 'Status', 'farm-management' );
				$new['fmp_assigned_to'] = __( 'Assigned To', 'farm-management' );
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
			case 'fmp_priority':
				$priority = get_post_meta( $post_id, '_fmp_priority', true );
				$labels = array(
					'low'  => __( 'Low', 'farm-management' ),
					'med'  => __( 'Medium', 'farm-management' ),
					'high' => __( 'High', 'farm-management' ),
				);
				echo esc_html( isset( $labels[ $priority ] ) ? $labels[ $priority ] : ( $priority ?: '—' ) );
				break;
			case 'fmp_due_date':
				echo esc_html( get_post_meta( $post_id, '_fmp_due_date', true ) ?: '—' );
				break;
			case 'fmp_status':
				$status = get_post_meta( $post_id, '_fmp_status', true );
				$labels = array(
					'open'        => __( 'Open', 'farm-management' ),
					'in-progress' => __( 'In progress', 'farm-management' ),
					'done'        => __( 'Done', 'farm-management' ),
				);
				echo esc_html( isset( $labels[ $status ] ) ? $labels[ $status ] : ( $status ?: '—' ) );
				break;
			case 'fmp_assigned_to':
				$user_id = (int) get_post_meta( $post_id, '_fmp_assigned_to', true );
				if ( $user_id ) {
					$user = get_userdata( $user_id );
					echo $user ? esc_html( $user->display_name ) : '—';
				} else {
					echo '—';
				}
				break;
		}
	}

	/**
	 * Render status and priority filter dropdowns above the list table.
	 *
	 * @param string $post_type Post type.
	 */
	public function render_filters( $post_type ) {
		if ( $post_type !== 'fmp_task' ) {
			return;
		}

		$filter_status   = isset( $_GET['fmp_filter_status'] ) ? sanitize_text_field( wp_unslash( $_GET['fmp_filter_status'] ) ) : '';
		$filter_priority = isset( $_GET['fmp_filter_priority'] ) ? sanitize_text_field( wp_unslash( $_GET['fmp_filter_priority'] ) ) : '';
		?>
		<select name="fmp_filter_status" id="fmp_filter_status">
			<option value=""><?php esc_html_e( 'All statuses', 'farm-management' ); ?></option>
			<option value="open" <?php selected( $filter_status, 'open' ); ?>><?php esc_html_e( 'Open', 'farm-management' ); ?></option>
			<option value="in-progress" <?php selected( $filter_status, 'in-progress' ); ?>><?php esc_html_e( 'In progress', 'farm-management' ); ?></option>
			<option value="done" <?php selected( $filter_status, 'done' ); ?>><?php esc_html_e( 'Done', 'farm-management' ); ?></option>
		</select>
		<select name="fmp_filter_priority" id="fmp_filter_priority">
			<option value=""><?php esc_html_e( 'All priorities', 'farm-management' ); ?></option>
			<option value="low" <?php selected( $filter_priority, 'low' ); ?>><?php esc_html_e( 'Low', 'farm-management' ); ?></option>
			<option value="med" <?php selected( $filter_priority, 'med' ); ?>><?php esc_html_e( 'Medium', 'farm-management' ); ?></option>
			<option value="high" <?php selected( $filter_priority, 'high' ); ?>><?php esc_html_e( 'High', 'farm-management' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Filter the main query by status and priority when on Tasks list.
	 *
	 * @param WP_Query $query Query object.
	 */
	public function filter_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || $screen->id !== 'edit-fmp_task' ) {
			return;
		}

		$filter_status   = isset( $_GET['fmp_filter_status'] ) ? sanitize_text_field( wp_unslash( $_GET['fmp_filter_status'] ) ) : '';
		$filter_priority = isset( $_GET['fmp_filter_priority'] ) ? sanitize_text_field( wp_unslash( $_GET['fmp_filter_priority'] ) ) : '';

		$meta_query = array();
		if ( $filter_status !== '' ) {
			$meta_query[] = array(
				'key'   => '_fmp_status',
				'value' => $filter_status,
			);
		}
		if ( $filter_priority !== '' ) {
			$meta_query[] = array(
				'key'   => '_fmp_priority',
				'value' => $filter_priority,
			);
		}
		if ( ! empty( $meta_query ) ) {
			$meta_query['relation'] = 'AND';
			$query->set( 'meta_query', $meta_query );
		}
	}

	/**
	 * Render quick-add form above the Tasks list table.
	 */
	public function render_quick_add_form() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || $screen->id !== 'edit-fmp_task' ) {
			return;
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
			return;
		}

		$users = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );
		$added = isset( $_GET['fmp_added'] ) ? 1 : 0;
		$error = isset( $_GET['fmp_error'] ) ? 1 : 0;
		?>
		<div class="fmp-quick-add-wrap wrap" style="margin-bottom: 1em;">
			<div class="fmp-quick-add-box" style="background: #fff; border: 1px solid #c3c4c7; padding: 1rem 1.5rem; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,0.04); max-width: 800px;">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Quick-add task', 'farm-management' ); ?></h2>
				<?php if ( $added ) : ?>
					<p class="notice notice-success" style="margin: 0 0 1rem 0;"><?php esc_html_e( 'Task added.', 'farm-management' ); ?></p>
				<?php endif; ?>
				<?php if ( $error ) : ?>
					<p class="notice notice-error" style="margin: 0 0 1rem 0;"><?php esc_html_e( 'Please enter a title.', 'farm-management' ); ?></p>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="fmp_quick_add_task" />
					<?php wp_nonce_field( self::QUICK_ADD_NONCE, 'fmp_quick_add_nonce' ); ?>
					<div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 0.5rem 1rem; align-items: end; flex-wrap: wrap;">
						<div>
							<label for="fmp_quick_title" style="display: block; font-weight: 600; margin-bottom: 4px;"><?php esc_html_e( 'Title', 'farm-management' ); ?></label>
							<input type="text" id="fmp_quick_title" name="fmp_quick_title" class="regular-text" required />
						</div>
						<div>
							<label for="fmp_quick_assigned_to" style="display: block; font-weight: 600; margin-bottom: 4px;"><?php esc_html_e( 'Assigned to', 'farm-management' ); ?></label>
							<select id="fmp_quick_assigned_to" name="fmp_quick_assigned_to">
								<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
								<?php foreach ( $users as $user ) : ?>
									<option value="<?php echo esc_attr( $user->ID ); ?>"><?php echo esc_html( $user->display_name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div>
							<label for="fmp_quick_priority" style="display: block; font-weight: 600; margin-bottom: 4px;"><?php esc_html_e( 'Priority', 'farm-management' ); ?></label>
							<select id="fmp_quick_priority" name="fmp_quick_priority">
								<option value="low"><?php esc_html_e( 'Low', 'farm-management' ); ?></option>
								<option value="med" selected><?php esc_html_e( 'Medium', 'farm-management' ); ?></option>
								<option value="high"><?php esc_html_e( 'High', 'farm-management' ); ?></option>
							</select>
						</div>
						<div>
							<label for="fmp_quick_due_date" style="display: block; font-weight: 600; margin-bottom: 4px;"><?php esc_html_e( 'Due date', 'farm-management' ); ?></label>
							<input type="date" id="fmp_quick_due_date" name="fmp_quick_due_date" />
						</div>
						<div>
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Task', 'farm-management' ); ?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle quick-add form submission.
	 */
	public function handle_quick_add() {
		if ( ! isset( $_POST['fmp_quick_add_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_quick_add_nonce'] ) ), self::QUICK_ADD_NONCE ) ) {
			wp_die( esc_html__( 'Security check failed.', 'farm-management' ) );
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
			wp_die( esc_html__( 'You do not have permission to add tasks.', 'farm-management' ) );
		}

		$title = isset( $_POST['fmp_quick_title'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_quick_title'] ) ) : '';
		if ( $title === '' ) {
			wp_safe_redirect( add_query_arg( 'fmp_error', '1', admin_url( 'edit.php?post_type=fmp_task' ) ) );
			exit;
		}

		$assigned_to = isset( $_POST['fmp_quick_assigned_to'] ) ? absint( $_POST['fmp_quick_assigned_to'] ) : 0;
		$priority    = isset( $_POST['fmp_quick_priority'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_quick_priority'] ) ) : 'med';
		$due_date    = isset( $_POST['fmp_quick_due_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_quick_due_date'] ) ) : '';

		$post_id = wp_insert_post( array(
			'post_type'   => 'fmp_task',
			'post_title'  => $title,
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
		) );

		if ( is_wp_error( $post_id ) ) {
			wp_safe_redirect( add_query_arg( 'fmp_error', '1', admin_url( 'edit.php?post_type=fmp_task' ) ) );
			exit;
		}

		update_post_meta( $post_id, '_fmp_assigned_to', $assigned_to );
		update_post_meta( $post_id, '_fmp_priority', $priority );
		update_post_meta( $post_id, '_fmp_due_date', $due_date );
		update_post_meta( $post_id, '_fmp_status', 'open' );

		wp_safe_redirect( add_query_arg( 'fmp_added', '1', admin_url( 'edit.php?post_type=fmp_task' ) ) );
		exit;
	}
}
