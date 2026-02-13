<?php
/**
 * Vaccinations admin page: list + add/edit form.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Admin_Vaccinations_Page
 */
class FMP_Admin_Vaccinations_Page {

	const PAGE_SLUG     = 'fmp-vaccinations';
	const SAVE_ACTION  = 'fmp_save_vaccination';
	const DELETE_ACTION = 'fmp_delete_vaccination';
	const SAVE_NONCE   = 'fmp_save_vaccination';
	const DELETE_NONCE = 'fmp_delete_vaccination';
	const POST_TYPE    = 'fmp_vaccination';
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
					wp_die( esc_html__( 'Invalid vaccination.', 'farm-management' ) );
				}
			}
			self::render_form( $post_id );
			return;
		}

		self::render_list();
	}

	/**
	 * Render list: title, Add button, paginated table.
	 * Supports filter=overdue and filter=due_soon (from dashboard "View all" links).
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

		$today = gmdate( 'Y-m-d' );
		if ( $filter === 'overdue' ) {
			$query_args['orderby']  = 'meta_value';
			$query_args['meta_key'] = '_fmp_next_due_date';
			$query_args['order']    = 'ASC';
			$query_args['meta_query'] = array(
				array(
					'key'     => '_fmp_next_due_date',
					'value'   => $today,
					'compare' => '<',
					'type'    => 'DATE',
				),
			);
		} elseif ( $filter === 'due_soon' ) {
			$days = (int) FMP_Settings::get( FMP_Settings::KEY_DUE_SOON_DAYS );
			if ( $days < 1 ) {
				$days = (int) FMP_Settings::get( FMP_Settings::KEY_VACCINATION_DAYS );
			}
			$days = $days >= 1 ? $days : FMP_Settings::DEFAULT_DUE_SOON_DAYS;
			$end  = gmdate( 'Y-m-d', strtotime( '+' . $days . ' days' ) );
			$query_args['orderby']  = 'meta_value';
			$query_args['meta_key'] = '_fmp_next_due_date';
			$query_args['order']    = 'ASC';
			$query_args['meta_query'] = array(
				array(
					'key'     => '_fmp_next_due_date',
					'value'   => array( $today, $end ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			);
		} else {
			$query_args['post_status'] = 'any';
		}

		$query  = new WP_Query( $query_args );
		$items  = $query->posts;
		$add_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=new' );
		$base_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		if ( $filter ) {
			$base_url .= '&filter=' . rawurlencode( $filter );
		}
		$delete_nonce = self::DELETE_NONCE;
		?>
		<div class="wrap fmp-admin-wrap fmp-vaccinations-list">
			<h1 class="wp-heading-inline"><?php
			if ( $filter === 'overdue' ) {
				esc_html_e( 'Overdue Vaccinations', 'farm-management' );
			} elseif ( $filter === 'due_soon' ) {
				esc_html_e( 'Vaccinations Due Soon', 'farm-management' );
			} else {
				esc_html_e( 'Vaccinations', 'farm-management' );
			}
			?></h1>
			<a href="<?php echo esc_url( $add_url ); ?>" class="page-title-action"><?php esc_html_e( 'Add Vaccination', 'farm-management' ); ?></a>
			<hr class="wp-header-end" />

			<?php
			if ( isset( $_GET['updated'] ) && $_GET['updated'] === '1' ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Vaccination saved.', 'farm-management' ) . '</p></div>';
			}
			if ( isset( $_GET['deleted'] ) && $_GET['deleted'] === '1' ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Vaccination deleted.', 'farm-management' ) . '</p></div>';
			}
			if ( isset( $_GET['error'] ) && $_GET['error'] === 'validation' && isset( $_GET['message'] ) ) {
				$msg = sanitize_text_field( wp_unslash( $_GET['message'] ) );
				if ( $msg ) {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
				}
			}
			?>

			<?php if ( empty( $items ) ) : ?>
				<p><?php esc_html_e( 'No vaccinations yet.', 'farm-management' ); ?></p>
				<p><a href="<?php echo esc_url( $add_url ); ?>" class="button button-primary"><?php esc_html_e( 'Add Vaccination', 'farm-management' ); ?></a></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Animal', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Vaccine', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Date Given', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Next Due', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Location', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Actions', 'farm-management' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $items as $post ) : ?>
							<?php
							$animal_id   = (int) get_post_meta( $post->ID, '_fmp_animal_id', true );
							$animal_name = $animal_id ? get_the_title( $animal_id ) : '—';
							$vaccine     = get_post_meta( $post->ID, '_fmp_vaccine_name', true );
							$date_given  = get_post_meta( $post->ID, '_fmp_date_given', true );
							$next_due    = get_post_meta( $post->ID, '_fmp_next_due_date', true );
							$status      = FMP_Vaccinations::get_vaccination_status( $next_due );
							$location    = get_post_meta( $post->ID, '_fmp_vaccination_location', true );
							$edit_url   = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=edit&id=' . $post->ID );
							$delete_url = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::DELETE_ACTION . '&id=' . $post->ID ), $delete_nonce, '_wpnonce' );
							?>
							<tr>
								<td><?php echo esc_html( $animal_name ); ?></td>
								<td><?php echo esc_html( $vaccine ?: get_the_title( $post->ID ) ?: '—' ); ?></td>
								<td><?php echo esc_html( $date_given ?: '—' ); ?></td>
								<td><?php echo esc_html( $next_due ?: '—' ); ?></td>
								<td>
									<?php
									if ( $status === 'overdue' ) {
										echo '<span style="color: #b32d2e; font-weight: 600;">' . esc_html__( 'Overdue', 'farm-management' ) . '</span>';
									} elseif ( $status === 'due_soon' ) {
										echo '<span style="color: #d63638;">' . esc_html__( 'Due Soon', 'farm-management' ) . '</span>';
									} else {
										echo esc_html__( 'OK', 'farm-management' );
									}
									?>
								</td>
								<td><?php echo esc_html( $location ?: '—' ); ?></td>
								<td>
									<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'farm-management' ); ?></a>
									<?php if ( current_user_can( FMP_Capabilities::DELETE_RECORDS ) ) : ?>
										| <a href="<?php echo esc_url( $delete_url ); ?>" class="fmp-delete-row" data-confirm="<?php echo esc_attr__( 'Delete this vaccination?', 'farm-management' ); ?>"><?php esc_html_e( 'Delete', 'farm-management' ); ?></a>
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
		if ( $is_edit ) {
			$animal_id = (int) get_post_meta( $post_id, '_fmp_animal_id', true );
		} else {
			// Preselect animal when coming from Animals → "Add Vaccination for this animal".
			$animal_id = isset( $_GET['animal_id'] ) ? absint( $_GET['animal_id'] ) : 0;
		}
		$vaccine_name = $is_edit ? get_post_meta( $post_id, '_fmp_vaccine_name', true ) : '';
		$date_given  = $is_edit ? get_post_meta( $post_id, '_fmp_date_given', true ) : '';
		$next_due    = $is_edit ? get_post_meta( $post_id, '_fmp_next_due_date', true ) : '';
		$location    = $is_edit ? get_post_meta( $post_id, '_fmp_vaccination_location', true ) : '';
		$notes       = $is_edit ? get_post_meta( $post_id, '_fmp_notes', true ) : '';
		if ( $is_edit && $vaccine_name === '' ) {
			$vaccine_name = get_the_title( $post_id );
		}

		$validation_error = isset( $_GET['error'] ) && $_GET['error'] === 'validation' && isset( $_GET['message'] )
			? sanitize_text_field( wp_unslash( $_GET['message'] ) )
			: '';

		$animals = get_posts( array(
			'post_type'      => 'fmp_animal',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		$form_action = admin_url( 'admin-post.php' );
		$list_url    = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		$page_heading = $is_edit ? __( 'Edit Vaccination', 'farm-management' ) : __( 'Add Vaccination', 'farm-management' );
		?>
		<div class="wrap fmp-admin-wrap fmp-vaccinations-form">
			<h1><?php echo esc_html( $page_heading ); ?></h1>
			<p><a href="<?php echo esc_url( $list_url ); ?>">&larr; <?php esc_html_e( 'Back to Vaccinations', 'farm-management' ); ?></a></p>

			<?php if ( $validation_error ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $validation_error ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( $form_action ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SAVE_ACTION ); ?>" />
				<?php wp_nonce_field( self::SAVE_NONCE, 'fmp_vaccination_nonce' ); ?>
				<?php if ( $post_id > 0 ) : ?>
					<input type="hidden" name="fmp_vaccination_id" value="<?php echo esc_attr( $post_id ); ?>" />
				<?php endif; ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="fmp_animal_search"><?php esc_html_e( 'Animal', 'farm-management' ); ?></label></th>
						<td>
							<input type="text" id="fmp_animal_search" class="fmp-animal-search regular-text" placeholder="<?php esc_attr_e( 'Search animals…', 'farm-management' ); ?>" autocomplete="off" />
							<select id="fmp_animal_id" name="fmp_animal_id" class="fmp-animal-select" required="required">
								<option value=""><?php esc_html_e( '— Select Animal —', 'farm-management' ); ?></option>
								<?php foreach ( $animals as $animal ) : ?>
									<option value="<?php echo esc_attr( $animal->ID ); ?>" <?php selected( $animal_id, $animal->ID ); ?> data-label="<?php echo esc_attr( get_the_title( $animal->ID ) ); ?>"><?php echo esc_html( get_the_title( $animal->ID ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_vaccine_name"><?php esc_html_e( 'Vaccine name', 'farm-management' ); ?></label></th>
						<td><input type="text" id="fmp_vaccine_name" name="fmp_vaccine_name" value="<?php echo esc_attr( $vaccine_name ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_date_given"><?php esc_html_e( 'Date given', 'farm-management' ); ?></label></th>
						<td><input type="date" id="fmp_date_given" name="fmp_date_given" value="<?php echo esc_attr( $date_given ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_next_due_date"><?php esc_html_e( 'Next due date', 'farm-management' ); ?></label></th>
						<td><input type="date" id="fmp_next_due_date" name="fmp_next_due_date" value="<?php echo esc_attr( $next_due ); ?>" required="required" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_vaccination_location"><?php esc_html_e( 'Location', 'farm-management' ); ?></label></th>
						<td><input type="text" id="fmp_vaccination_location" name="fmp_vaccination_location" value="<?php echo esc_attr( $location ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Where the animal will be vaccinated', 'farm-management' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_notes"><?php esc_html_e( 'Notes', 'farm-management' ); ?></label></th>
						<td><textarea id="fmp_notes" name="fmp_notes" rows="4" class="large-text"><?php echo esc_textarea( $notes ); ?></textarea></td>
					</tr>
				</table>

				<p class="submit">
					<?php submit_button( $is_edit ? __( 'Update Vaccination', 'farm-management' ) : __( 'Add Vaccination', 'farm-management' ), 'primary', 'fmp_submit_vaccination', false ); ?>
					<a href="<?php echo esc_url( $list_url ); ?>" class="button"><?php esc_html_e( 'Cancel', 'farm-management' ); ?></a>
				</p>
			</form>
		</div>
		<?php
		$search_script = "jQuery(function($){
			$('.fmp-animal-search').on('input', function(){
				var q = $(this).val().toLowerCase();
				var sel = $(this).siblings('.fmp-animal-select').first();
				sel.find('option').each(function(){
					var opt = $(this);
					if (opt.val() === '') { opt.show(); return; }
					var label = (opt.data('label') || opt.text()).toLowerCase();
					opt.toggle(label.indexOf(q) !== -1);
				});
			});
		});";
		wp_add_inline_script( 'jquery', $search_script, 'after' );
	}

	/**
	 * Handle save: create or update CPT + meta.
	 */
	public static function handle_save() {
		if ( ! isset( $_POST['fmp_vaccination_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_vaccination_nonce'] ) ), self::SAVE_NONCE ) ) {
			wp_die( esc_html__( 'Security check failed.', 'farm-management' ) );
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'farm-management' ) );
		}

		$post_id = isset( $_POST['fmp_vaccination_id'] ) ? absint( $_POST['fmp_vaccination_id'] ) : 0;
		$is_edit = $post_id > 0;

		if ( $is_edit ) {
			$post = get_post( $post_id );
			if ( ! $post || $post->post_type !== self::POST_TYPE ) {
				wp_die( esc_html__( 'Invalid vaccination.', 'farm-management' ) );
			}
			if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
				wp_die( esc_html__( 'You do not have permission to edit this vaccination.', 'farm-management' ) );
			}
		}

		$animal_id   = isset( $_POST['fmp_animal_id'] ) ? absint( $_POST['fmp_animal_id'] ) : 0;
		$vaccine_name = isset( $_POST['fmp_vaccine_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_vaccine_name'] ) ) : '';
		$date_given  = isset( $_POST['fmp_date_given'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_date_given'] ) ) : '';
		$next_due    = isset( $_POST['fmp_next_due_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_next_due_date'] ) ) : '';
		$location    = isset( $_POST['fmp_vaccination_location'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_vaccination_location'] ) ) : '';
		$notes       = isset( $_POST['fmp_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_notes'] ) ) : '';

		$animal_valid = $animal_id > 0;
		if ( $animal_valid ) {
			$animal_post = get_post( $animal_id );
			$animal_valid = $animal_post && $animal_post->post_type === 'fmp_animal';
		}
		$next_due_valid = FMP_Vaccinations::is_valid_date( $next_due );

		if ( ! $animal_valid ) {
			$args = array(
				'page'    => self::PAGE_SLUG,
				'action'  => $is_edit ? 'edit' : 'new',
				'error'   => 'validation',
				'message' => rawurlencode( __( 'Please select a valid animal.', 'farm-management' ) ),
			);
			if ( $is_edit ) {
				$args['id'] = $post_id;
			}
			wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
			exit;
		}
		if ( ! $next_due_valid ) {
			$args = array(
				'page'    => self::PAGE_SLUG,
				'action'  => $is_edit ? 'edit' : 'new',
				'error'   => 'validation',
				'message' => rawurlencode( __( 'Next due date is required and must be in Y-m-d format.', 'farm-management' ) ),
			);
			if ( $is_edit ) {
				$args['id'] = $post_id;
			}
			wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
			exit;
		}

		$title = $vaccine_name !== '' ? $vaccine_name : __( 'Vaccination (no name)', 'farm-management' );

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
				wp_die( esc_html__( 'Failed to create vaccination.', 'farm-management' ) );
			}
		}

		update_post_meta( $post_id, '_fmp_animal_id', $animal_id );
		update_post_meta( $post_id, '_fmp_vaccine_name', $vaccine_name );
		update_post_meta( $post_id, '_fmp_date_given', $date_given );
		update_post_meta( $post_id, '_fmp_next_due_date', $next_due );
		update_post_meta( $post_id, '_fmp_vaccination_location', $location );
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
			wp_die( esc_html__( 'You do not have permission to delete this vaccination.', 'farm-management' ) );
		}

		wp_trash_post( $post_id );

		$redirect = add_query_arg( array( 'page' => self::PAGE_SLUG, 'deleted' => '1' ), admin_url( 'admin.php' ) );
		wp_safe_redirect( $redirect );
		exit;
	}
}
