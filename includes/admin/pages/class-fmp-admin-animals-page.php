<?php
/**
 * Animals admin page: list + add/edit form.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Admin_Animals_Page
 */
class FMP_Admin_Animals_Page {

	const PAGE_SLUG   = 'fmp-animals';
	const SAVE_ACTION = 'fmp_save_animal';
	const DELETE_ACTION = 'fmp_delete_animal';
	const SAVE_NONCE  = 'fmp_save_animal';
	const DELETE_NONCE = 'fmp_delete_animal';
	const POST_TYPE   = 'fmp_animal';
	const PER_PAGE    = 20;

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
					wp_die( esc_html__( 'Invalid animal.', 'farm-management' ) );
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
		$paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$query = new WP_Query( array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => self::PER_PAGE,
			'paged'          => $paged,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		$animals   = $query->posts;
		$total     = $query->found_posts;
		$add_url   = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=new' );
		$base_url  = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		$delete_nonce = self::DELETE_NONCE;
		?>
		<div class="wrap fmp-admin-wrap fmp-animals-list">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Animals', 'farm-management' ); ?></h1>
			<a href="<?php echo esc_url( $add_url ); ?>" class="page-title-action"><?php esc_html_e( 'Add Animal', 'farm-management' ); ?></a>
			<hr class="wp-header-end" />

			<?php
			if ( isset( $_GET['updated'] ) && $_GET['updated'] === '1' ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Animal saved.', 'farm-management' ) . '</p></div>';
			}
			if ( isset( $_GET['deleted'] ) && $_GET['deleted'] === '1' ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Animal deleted.', 'farm-management' ) . '</p></div>';
			}
			?>

			<?php if ( empty( $animals ) ) : ?>
				<p><?php esc_html_e( 'No animals yet.', 'farm-management' ); ?></p>
				<p><a href="<?php echo esc_url( $add_url ); ?>" class="button button-primary"><?php esc_html_e( 'Add Animal', 'farm-management' ); ?></a></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Tag/ID', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Species', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Breed', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Weight', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Actions', 'farm-management' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $animals as $post ) : ?>
							<?php
							$tag    = get_post_meta( $post->ID, '_fmp_tag', true );
							$species = get_post_meta( $post->ID, '_fmp_species', true );
							$breed  = get_post_meta( $post->ID, '_fmp_breed', true );
							$status = get_post_meta( $post->ID, '_fmp_status', true );
							$weight = get_post_meta( $post->ID, '_fmp_weight', true );
							$edit_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=edit&id=' . $post->ID );
							$delete_url = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::DELETE_ACTION . '&id=' . $post->ID ), $delete_nonce, '_wpnonce' );
							?>
							<tr>
								<td><?php echo esc_html( $tag ?: get_the_title( $post->ID ) ?: '—' ); ?></td>
								<td><?php echo esc_html( $species ?: '—' ); ?></td>
								<td><?php echo esc_html( $breed ?: '—' ); ?></td>
								<td><?php echo esc_html( $status ?: '—' ); ?></td>
								<td><?php echo esc_html( $weight !== '' ? $weight . ' kg' : '—' ); ?></td>
								<td>
									<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'farm-management' ); ?></a>
									<?php if ( current_user_can( FMP_Capabilities::DELETE_RECORDS ) ) : ?>
										| <a href="<?php echo esc_url( $delete_url ); ?>" class="fmp-delete-animal" data-confirm="<?php echo esc_attr__( 'Delete this animal?', 'farm-management' ); ?>"><?php esc_html_e( 'Delete', 'farm-management' ); ?></a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php
				$big = 999999999;
				$links = paginate_links( array(
					'base'    => str_replace( $big, '%#%', esc_url( $base_url . '&paged=' . $big ) ),
					'format'  => '?paged=%#%',
					'current' => $paged,
					'total'   => $query->max_num_pages,
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
		if ( ! empty( $animals ) ) {
			wp_add_inline_script( 'jquery', "jQuery(function($){ $('.fmp-delete-animal').on('click', function(e){ if (! confirm($(this).data('confirm'))) e.preventDefault(); }); });" );
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
			$post = get_post( $post_id );
			$title = get_the_title( $post_id );
		} else {
			$post  = null;
			$title = '';
		}

		$tag           = $is_edit ? get_post_meta( $post_id, '_fmp_tag', true ) : '';
		$species       = $is_edit ? get_post_meta( $post_id, '_fmp_species', true ) : '';
		$breed         = $is_edit ? get_post_meta( $post_id, '_fmp_breed', true ) : '';
		$sex           = $is_edit ? get_post_meta( $post_id, '_fmp_sex', true ) : '';
		$date_of_birth = $is_edit ? get_post_meta( $post_id, '_fmp_date_of_birth', true ) : '';
		$acquired_date = $is_edit ? get_post_meta( $post_id, '_fmp_acquired_date', true ) : '';
		$status        = $is_edit ? get_post_meta( $post_id, '_fmp_status', true ) : '';
		$weight        = $is_edit ? get_post_meta( $post_id, '_fmp_weight', true ) : '';
		$notes         = $is_edit ? get_post_meta( $post_id, '_fmp_notes', true ) : '';
		$thumbnail_id  = $is_edit ? get_post_thumbnail_id( $post_id ) : 0;

		$form_action = admin_url( 'admin-post.php' );
		$list_url    = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		wp_enqueue_media();

		$page_heading = $is_edit ? __( 'Edit Animal', 'farm-management' ) : __( 'Add Animal', 'farm-management' );
		?>
		<div class="wrap fmp-admin-wrap fmp-animals-form">
			<h1><?php echo esc_html( $page_heading ); ?></h1>
			<p><a href="<?php echo esc_url( $list_url ); ?>">&larr; <?php esc_html_e( 'Back to Animals', 'farm-management' ); ?></a></p>

			<form method="post" action="<?php echo esc_url( $form_action ); ?>" id="fmp-animal-form">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SAVE_ACTION ); ?>" />
				<?php wp_nonce_field( self::SAVE_NONCE, 'fmp_save_animal_nonce' ); ?>
				<?php if ( $post_id > 0 ) : ?>
					<input type="hidden" name="fmp_animal_id" value="<?php echo esc_attr( $post_id ); ?>" />
				<?php endif; ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="fmp_tag"><?php esc_html_e( 'Tag/ID', 'farm-management' ); ?></label></th>
						<td><input type="text" id="fmp_tag" name="fmp_tag" value="<?php echo esc_attr( $tag ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_species"><?php esc_html_e( 'Species', 'farm-management' ); ?></label></th>
						<td><input type="text" id="fmp_species" name="fmp_species" value="<?php echo esc_attr( $species ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_breed"><?php esc_html_e( 'Breed', 'farm-management' ); ?></label></th>
						<td><input type="text" id="fmp_breed" name="fmp_breed" value="<?php echo esc_attr( $breed ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_sex"><?php esc_html_e( 'Sex', 'farm-management' ); ?></label></th>
						<td>
							<select id="fmp_sex" name="fmp_sex">
								<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
								<option value="male" <?php selected( $sex, 'male' ); ?>><?php esc_html_e( 'Male', 'farm-management' ); ?></option>
								<option value="female" <?php selected( $sex, 'female' ); ?>><?php esc_html_e( 'Female', 'farm-management' ); ?></option>
								<option value="other" <?php selected( $sex, 'other' ); ?>><?php esc_html_e( 'Other', 'farm-management' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_date_of_birth"><?php esc_html_e( 'Date of birth', 'farm-management' ); ?></label></th>
						<td><input type="date" id="fmp_date_of_birth" name="fmp_date_of_birth" value="<?php echo esc_attr( $date_of_birth ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_acquired_date"><?php esc_html_e( 'Acquired date', 'farm-management' ); ?></label></th>
						<td><input type="date" id="fmp_acquired_date" name="fmp_acquired_date" value="<?php echo esc_attr( $acquired_date ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_status"><?php esc_html_e( 'Status', 'farm-management' ); ?></label></th>
						<td>
							<select id="fmp_status" name="fmp_status">
								<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
								<option value="alive" <?php selected( $status, 'alive' ); ?>><?php esc_html_e( 'Alive', 'farm-management' ); ?></option>
								<option value="sold" <?php selected( $status, 'sold' ); ?>><?php esc_html_e( 'Sold', 'farm-management' ); ?></option>
								<option value="dead" <?php selected( $status, 'dead' ); ?>><?php esc_html_e( 'Dead', 'farm-management' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_weight"><?php esc_html_e( 'Weight (kg)', 'farm-management' ); ?></label></th>
						<td><input type="number" id="fmp_weight" name="fmp_weight" value="<?php echo esc_attr( $weight ); ?>" step="0.01" min="0" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fmp_notes"><?php esc_html_e( 'Notes', 'farm-management' ); ?></label></th>
						<td><textarea id="fmp_notes" name="fmp_notes" rows="4" class="large-text"><?php echo esc_textarea( $notes ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Featured image', 'farm-management' ); ?></th>
						<td>
							<input type="hidden" id="fmp_thumbnail_id" name="fmp_thumbnail_id" value="<?php echo esc_attr( $thumbnail_id ); ?>" />
							<div id="fmp-thumbnail-preview">
								<?php if ( $thumbnail_id ) : ?>
									<?php echo wp_get_attachment_image( $thumbnail_id, 'thumbnail' ); ?>
								<?php endif; ?>
							</div>
							<button type="button" class="button" id="fmp-upload-thumbnail"><?php esc_html_e( 'Select image', 'farm-management' ); ?></button>
							<button type="button" class="button" id="fmp-remove-thumbnail" <?php echo $thumbnail_id ? '' : ' style="display:none;"'; ?>><?php esc_html_e( 'Remove image', 'farm-management' ); ?></button>
						</td>
					</tr>
				</table>

				<p class="submit">
					<?php submit_button( $is_edit ? __( 'Update Animal', 'farm-management' ) : __( 'Add Animal', 'farm-management' ), 'primary', 'fmp_submit_animal', false ); ?>
					<a href="<?php echo esc_url( $list_url ); ?>" class="button"><?php esc_html_e( 'Cancel', 'farm-management' ); ?></a>
				</p>
			</form>

			<?php
			if ( $is_edit && $post_id > 0 ) {
				self::render_vaccinations_section( $post_id );
			}
			?>
		</div>
		<?php
		$media_script = "jQuery(function($){
			var frame;
			$('#fmp-upload-thumbnail').on('click', function(e) {
				e.preventDefault();
				if (typeof wp === 'undefined' || typeof wp.media === 'undefined') { return; }
				if (frame) { frame.open(); return; }
				frame = wp.media({
					title: '" . esc_js( __( 'Select or upload image', 'farm-management' ) ) . "',
					library: { type: 'image' },
					button: { text: '" . esc_js( __( 'Use this image', 'farm-management' ) ) . "' },
					multiple: false
				});
				frame.on('select', function() {
					var att = frame.state().get('selection').first().toJSON();
					$('#fmp_thumbnail_id').val(att.id);
					var imgUrl = (att.sizes && att.sizes.thumbnail && att.sizes.thumbnail.url) ? att.sizes.thumbnail.url : att.url;
					$('#fmp-thumbnail-preview').html($('<img>').attr('src', imgUrl).css('max-width', '150px'));
					$('#fmp-remove-thumbnail').show();
				});
				frame.open();
			});
			$('#fmp-remove-thumbnail').on('click', function(e) {
				e.preventDefault();
				$('#fmp_thumbnail_id').val('');
				$('#fmp-thumbnail-preview').empty();
				$(this).hide();
			});
		});";
		wp_add_inline_script( 'media-editor', $media_script, 'after' );
	}

	/**
	 * Render Vaccinations section for this animal (edit only): history table + "Add Vaccination" button.
	 *
	 * @param int $animal_id Animal post ID.
	 */
	public static function render_vaccinations_section( $animal_id ) {
		$vaccinations = new WP_Query( array(
			'post_type'      => 'fmp_vaccination',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => 'meta_value',
			'meta_key'       => '_fmp_next_due_date',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'   => '_fmp_animal_id',
					'value' => (int) $animal_id,
					'type'  => 'NUMERIC',
				),
			),
		) );
		$items = $vaccinations->posts;
		$add_vaccination_url = admin_url( 'admin.php?page=fmp-vaccinations&action=new&animal_id=' . (int) $animal_id );
		?>
		<div class="fmp-animal-vaccinations-section" style="margin-top: 2rem;">
			<h2 class="fmp-section-title"><?php esc_html_e( 'Vaccination history', 'farm-management' ); ?></h2>
			<p>
				<a href="<?php echo esc_url( $add_vaccination_url ); ?>" class="button button-primary"><?php esc_html_e( 'Add Vaccination for this animal', 'farm-management' ); ?></a>
			</p>
			<?php if ( empty( $items ) ) : ?>
				<p class="description"><?php esc_html_e( 'No vaccination records yet for this animal.', 'farm-management' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Vaccine', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Date given', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Next due date', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'farm-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Actions', 'farm-management' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $items as $v ) : ?>
							<?php
							$vaccine    = get_post_meta( $v->ID, '_fmp_vaccine_name', true );
							$vaccine    = $vaccine ?: get_the_title( $v->ID );
							$date_given = get_post_meta( $v->ID, '_fmp_date_given', true );
							$next_due   = get_post_meta( $v->ID, '_fmp_next_due_date', true );
							$status     = FMP_Vaccinations::get_vaccination_status( $next_due );
							$edit_url   = admin_url( 'admin.php?page=fmp-vaccinations&action=edit&id=' . $v->ID );
							?>
							<tr>
								<td><?php echo esc_html( $vaccine ?: '—' ); ?></td>
								<td><?php echo esc_html( $date_given ?: '—' ); ?></td>
								<td><?php echo esc_html( $next_due ?: '—' ); ?></td>
								<td>
									<?php
									if ( $status === 'overdue' ) {
										echo '<span class="fmp-badge fmp-badge-overdue">' . esc_html__( 'Overdue', 'farm-management' ) . '</span>';
									} elseif ( $status === 'due_soon' ) {
										echo '<span class="fmp-badge fmp-badge-due-soon">' . esc_html__( 'Due Soon', 'farm-management' ) . '</span>';
									} else {
										echo '<span class="fmp-badge fmp-badge-ok">' . esc_html__( 'OK', 'farm-management' ) . '</span>';
									}
									?>
								</td>
								<td><a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'farm-management' ); ?></a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handle save: create or update CPT + meta + featured image.
	 */
	public static function handle_save() {
		if ( ! isset( $_POST['fmp_save_animal_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_save_animal_nonce'] ) ), self::SAVE_NONCE ) ) {
			wp_die( esc_html__( 'Security check failed.', 'farm-management' ) );
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'farm-management' ) );
		}

		$post_id = isset( $_POST['fmp_animal_id'] ) ? absint( $_POST['fmp_animal_id'] ) : 0;
		$is_edit = $post_id > 0;

		if ( $is_edit ) {
			$post = get_post( $post_id );
			if ( ! $post || $post->post_type !== self::POST_TYPE ) {
				wp_die( esc_html__( 'Invalid animal.', 'farm-management' ) );
			}
			if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
				wp_die( esc_html__( 'You do not have permission to edit this animal.', 'farm-management' ) );
			}
		}

		$tag           = isset( $_POST['fmp_tag'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_tag'] ) ) : '';
		$species       = isset( $_POST['fmp_species'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_species'] ) ) : '';
		$breed         = isset( $_POST['fmp_breed'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_breed'] ) ) : '';
		$sex           = isset( $_POST['fmp_sex'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_sex'] ) ) : '';
		$date_of_birth = isset( $_POST['fmp_date_of_birth'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_date_of_birth'] ) ) : '';
		$acquired_date = isset( $_POST['fmp_acquired_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_acquired_date'] ) ) : '';
		$status        = isset( $_POST['fmp_status'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_status'] ) ) : '';
		$weight        = isset( $_POST['fmp_weight'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_weight'] ) ) : '';
		$notes         = isset( $_POST['fmp_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_notes'] ) ) : '';
		$thumbnail_id  = isset( $_POST['fmp_thumbnail_id'] ) ? absint( $_POST['fmp_thumbnail_id'] ) : 0;

		$title = $tag !== '' ? $tag : __( 'Animal (no tag)', 'farm-management' );

		// Custom page save: not inside save_post_*. New = wp_insert_post() once; edit = wp_update_post() once.
		if ( $is_edit ) {
			wp_update_post( array(
				'ID'         => $post_id,
				'post_title' => $title,
				'post_type'  => self::POST_TYPE,
			) );
		} else {
			$post_id = wp_insert_post( array(
				'post_title'   => $title,
				'post_type'    => self::POST_TYPE,
				'post_status'  => 'publish',
				'post_author'  => get_current_user_id(),
			) );
			if ( is_wp_error( $post_id ) ) {
				wp_die( esc_html__( 'Failed to create animal.', 'farm-management' ) );
			}
		}

		update_post_meta( $post_id, '_fmp_tag', $tag );
		update_post_meta( $post_id, '_fmp_species', $species );
		update_post_meta( $post_id, '_fmp_breed', $breed );
		update_post_meta( $post_id, '_fmp_sex', $sex );
		update_post_meta( $post_id, '_fmp_date_of_birth', $date_of_birth );
		update_post_meta( $post_id, '_fmp_acquired_date', $acquired_date );
		update_post_meta( $post_id, '_fmp_status', $status );
		update_post_meta( $post_id, '_fmp_weight', $weight );
		update_post_meta( $post_id, '_fmp_notes', $notes );

		if ( $thumbnail_id > 0 ) {
			set_post_thumbnail( $post_id, $thumbnail_id );
		} else {
			delete_post_thumbnail( $post_id );
		}

		$redirect = add_query_arg( array(
			'page'   => self::PAGE_SLUG,
			'updated' => '1',
		), admin_url( 'admin.php' ) );
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
			wp_die( esc_html__( 'You do not have permission to delete this animal.', 'farm-management' ) );
		}

		wp_trash_post( $post_id );

		$redirect = add_query_arg( array(
			'page'     => self::PAGE_SLUG,
			'deleted'  => '1',
		), admin_url( 'admin.php' ) );
		wp_safe_redirect( $redirect );
		exit;
	}
}
