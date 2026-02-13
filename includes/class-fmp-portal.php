<?php
/**
 * SaaS-style front-end portal: dashboard + add forms. No wp-admin.
 * Data isolated by post_author = current_user_id.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Portal
 */
class FMP_Portal {

	const OPTION_PAGES   = 'fmp_portal_pages';
	const NONCE_ANIMAL   = 'fmp_portal_add_animal';
	const NONCE_CROP     = 'fmp_portal_add_crop';
	const NONCE_TASK     = 'fmp_portal_add_task';
	const NONCE_INVENTORY = 'fmp_portal_add_inventory';
	const NONCE_EXPENSE  = 'fmp_portal_add_expense';
	const NONCE_VACC     = 'fmp_portal_add_vaccination';

	public static function init() {
		add_shortcode( 'fmp_portal_dashboard', array( __CLASS__, 'render_dashboard' ) );
		add_shortcode( 'fmp_add_animal', array( __CLASS__, 'render_add_animal' ) );
		add_shortcode( 'fmp_add_crop', array( __CLASS__, 'render_add_crop' ) );
		add_shortcode( 'fmp_add_task', array( __CLASS__, 'render_add_task' ) );
		add_shortcode( 'fmp_add_inventory', array( __CLASS__, 'render_add_inventory' ) );
		add_shortcode( 'fmp_add_expense', array( __CLASS__, 'render_add_expense' ) );
		add_shortcode( 'fmp_add_vaccination', array( __CLASS__, 'render_add_vaccination' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_css' ), 10 );
	}

	public static function enqueue_css() {
		global $post;
		if ( ! $post || ! is_singular() ) {
			return;
		}
		$content = isset( $post->post_content ) ? $post->post_content : '';
		$shortcodes = array( 'fmp_portal_dashboard', 'fmp_add_animal', 'fmp_add_crop', 'fmp_add_task', 'fmp_add_inventory', 'fmp_add_expense', 'fmp_add_vaccination' );
		$has = false;
		foreach ( $shortcodes as $sc ) {
			if ( has_shortcode( $content, $sc ) ) {
				$has = true;
				break;
			}
		}
		if ( ! $has ) {
			return;
		}
		wp_enqueue_style(
			'fmp-portal-saas',
			FMP_PLUGIN_URL . 'assets/css/portal.css',
			array(),
			FMP_VERSION
		);
		wp_enqueue_style(
			'fmp-farmer-ui',
			FMP_PLUGIN_URL . 'assets/css/fmp-farmer-ui.css',
			array( 'fmp-portal-saas' ),
			FMP_VERSION
		);
	}

	private static function get_pages() {
		$pages = get_option( self::OPTION_PAGES, array() );
		return is_array( $pages ) ? $pages : array();
	}

	public static function get_dashboard_url() {
		$pages = self::get_pages();
		$id   = isset( $pages['dashboard'] ) ? (int) $pages['dashboard'] : 0;
		return $id ? get_permalink( $id ) : home_url( '/' );
	}

	/**
	 * URL of the actual farm dashboard (page with [fmp_farm-dashboard] – Quick Add, stats). Used after form submit.
	 *
	 * @return string
	 */
	private static function get_farm_dashboard_url() {
		$page = get_page_by_path( 'farm-dashboard' );
		if ( ! $page ) {
			$page = get_page_by_path( 'dashboard' );
		}
		if ( ! $page ) {
			$page = get_page_by_path( 'portal' );
		}
		return $page ? get_permalink( $page ) : self::get_dashboard_url();
	}

	/**
	 * Handle animal photo upload; create attachment and attach to post. Called after animal post is created.
	 *
	 * @param int $post_id Animal post ID (parent for attachment).
	 * @return int Attachment ID or 0 on failure.
	 */
	private static function handle_animal_image_upload( $post_id ) {
		if ( empty( $_FILES['fmp_animal_image']['tmp_name'] ) || ! is_uploaded_file( $_FILES['fmp_animal_image']['tmp_name'] ) ) {
			return 0;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$file   = $_FILES['fmp_animal_image'];
		$upload = wp_handle_upload( $file, array( 'test_form' => false ) );
		if ( isset( $upload['error'] ) ) {
			return 0;
		}
		$file_path = isset( $upload['file'] ) ? $upload['file'] : '';
		$file_url  = isset( $upload['url'] ) ? $upload['url'] : '';
		if ( ! $file_path || ! $file_url ) {
			return 0;
		}
		$wp_filetype = wp_check_filetype( basename( $file_path ), null );
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => sanitize_file_name( pathinfo( $file_path, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
			'post_author'    => get_current_user_id(),
			'post_parent'    => $post_id,
		);
		$attachment_id = wp_insert_attachment( $attachment, $file_path, $post_id );
		if ( is_wp_error( $attachment_id ) ) {
			return 0;
		}
		$meta = wp_generate_attachment_metadata( $attachment_id, $file_path );
		if ( ! empty( $meta ) ) {
			wp_update_attachment_metadata( $attachment_id, $meta );
		}
		return (int) $attachment_id;
	}

	public static function get_add_url( $key ) {
		$pages = self::get_pages();
		$id   = isset( $pages[ $key ] ) ? (int) $pages[ $key ] : 0;
		return $id ? get_permalink( $id ) : self::get_dashboard_url();
	}

	/**
	 * URL for editing a record on the front-end (add page with ?id=). Used so dashboard Edit links stay in the portal.
	 *
	 * @param string $record_type One of: animal, crop, task, expense, inventory, vaccination.
	 * @param int    $post_id     Post ID to edit.
	 * @return string URL with ?id= or empty if no portal add page for that type.
	 */
	public static function get_edit_url( $record_type, $post_id ) {
		$allowed = array( 'animal', 'crop', 'task', 'expense', 'inventory', 'vaccination' );
		if ( ! in_array( $record_type, $allowed, true ) ) {
			return '';
		}
		$pages = self::get_pages();
		$key   = 'add_' . $record_type;
		if ( empty( $pages[ $key ] ) || ! (int) $pages[ $key ] ) {
			return '';
		}
		return add_query_arg( 'id', (int) $post_id, get_permalink( (int) $pages[ $key ] ) );
	}

	private static function login_required_message() {
		$login_url = wp_login_url( get_permalink() );
		return '<div class="fmp-portal-wrap"><div class="fmp-portal-card fmp-portal-login-required">' .
			'<h2 class="fmp-portal-title">' . esc_html__( 'Login required', 'farm-management' ) . '</h2>' .
			'<p>' . esc_html__( 'Please log in to access the Farm Portal.', 'farm-management' ) . '</p>' .
			'<a href="' . esc_url( $login_url ) . '" class="fmp-btn fmp-btn-primary">' . esc_html__( 'Log in', 'farm-management' ) . '</a>' .
			'</div></div>';
	}

	private static function get_count( $post_type ) {
		$uid = get_current_user_id();
		if ( ! $uid ) {
			return 0;
		}
		$q = new WP_Query( array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'author'         => $uid,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		) );
		return (int) $q->found_posts;
	}

	private static function redirect_dashboard( $success = true ) {
		$url = self::get_farm_dashboard_url();
		$url = add_query_arg( $success ? 'fmp_success' : 'fmp_error', '1', $url );
		wp_safe_redirect( $url );
		exit;
	}

	private static function notice_success() {
		if ( isset( $_GET['fmp_success'] ) && $_GET['fmp_success'] === '1' ) {
			return '<div class="fmp-portal-notice fmp-portal-notice-success">' . esc_html__( 'Saved successfully.', 'farm-management' ) . '</div>';
		}
		return '';
	}

	private static function notice_error() {
		if ( isset( $_GET['fmp_error'] ) && $_GET['fmp_error'] === '1' ) {
			return '<div class="fmp-portal-notice fmp-portal-notice-error">' . esc_html__( 'Something went wrong. Please try again.', 'farm-management' ) . '</div>';
		}
		return '';
	}

	/** Dashboard */
	public static function render_dashboard( $atts ) {
		if ( ! is_user_logged_in() ) {
			return self::login_required_message();
		}

		$pages = self::get_pages();
		$dashboard_url = self::get_dashboard_url();
		$counts = array(
			'animals'    => self::get_count( 'fmp_animal' ),
			'crops'      => self::get_count( 'fmp_crop' ),
			'tasks'      => self::get_count( 'fmp_task' ),
			'inventory'  => self::get_count( 'fmp_inventory_item' ),
			'expenses'   => self::get_count( 'fmp_expense' ),
			'vaccinations' => self::get_count( 'fmp_vaccination' ),
		);

		$add_animal_url     = self::get_add_url( 'add_animal' );
		$add_crop_url       = self::get_add_url( 'add_crop' );
		$add_task_url       = self::get_add_url( 'add_task' );
		$add_inventory_url  = self::get_add_url( 'add_inventory' );
		$add_expense_url    = self::get_add_url( 'add_expense' );
		$add_vaccination_url = self::get_add_url( 'add_vaccination' );

		ob_start();
		echo self::notice_success();
		echo self::notice_error();
		?>
		<div class="fmp-portal-wrap">
			<div class="fmp-portal-header">
				<h1 class="fmp-portal-heading"><?php esc_html_e( 'Farm Portal', 'farm-management' ); ?></h1>
				<p class="fmp-portal-subheading"><?php esc_html_e( 'Quick add &amp; overview', 'farm-management' ); ?></p>
			</div>

			<div class="fmp-portal-quick-add">
				<h2 class="fmp-portal-section-title"><?php esc_html_e( 'Quick Add', 'farm-management' ); ?></h2>
				<div class="fmp-portal-quick-add-grid">
					<a href="<?php echo esc_url( $add_animal_url ); ?>" class="fmp-portal-card fmp-portal-quick-add-card"><?php esc_html_e( 'Add Animal', 'farm-management' ); ?></a>
					<a href="<?php echo esc_url( $add_crop_url ); ?>" class="fmp-portal-card fmp-portal-quick-add-card"><?php esc_html_e( 'Add Crop', 'farm-management' ); ?></a>
					<a href="<?php echo esc_url( $add_task_url ); ?>" class="fmp-portal-card fmp-portal-quick-add-card"><?php esc_html_e( 'Add Task', 'farm-management' ); ?></a>
					<a href="<?php echo esc_url( $add_inventory_url ); ?>" class="fmp-portal-card fmp-portal-quick-add-card"><?php esc_html_e( 'Add Inventory', 'farm-management' ); ?></a>
					<a href="<?php echo esc_url( $add_expense_url ); ?>" class="fmp-portal-card fmp-portal-quick-add-card"><?php esc_html_e( 'Add Expense', 'farm-management' ); ?></a>
					<a href="<?php echo esc_url( $add_vaccination_url ); ?>" class="fmp-portal-card fmp-portal-quick-add-card"><?php esc_html_e( 'Add Vaccination', 'farm-management' ); ?></a>
				</div>
			</div>

			<div class="fmp-portal-stats">
				<h2 class="fmp-portal-section-title"><?php esc_html_e( 'Your counts', 'farm-management' ); ?></h2>
				<div class="fmp-portal-stats-grid">
					<div class="fmp-portal-card fmp-portal-stat-card">
						<span class="fmp-portal-stat-value"><?php echo absint( $counts['animals'] ); ?></span>
						<span class="fmp-portal-stat-label"><?php esc_html_e( 'Animals', 'farm-management' ); ?></span>
					</div>
					<div class="fmp-portal-card fmp-portal-stat-card">
						<span class="fmp-portal-stat-value"><?php echo absint( $counts['crops'] ); ?></span>
						<span class="fmp-portal-stat-label"><?php esc_html_e( 'Crops', 'farm-management' ); ?></span>
					</div>
					<div class="fmp-portal-card fmp-portal-stat-card">
						<span class="fmp-portal-stat-value"><?php echo absint( $counts['tasks'] ); ?></span>
						<span class="fmp-portal-stat-label"><?php esc_html_e( 'Tasks', 'farm-management' ); ?></span>
					</div>
					<div class="fmp-portal-card fmp-portal-stat-card">
						<span class="fmp-portal-stat-value"><?php echo absint( $counts['inventory'] ); ?></span>
						<span class="fmp-portal-stat-label"><?php esc_html_e( 'Inventory items', 'farm-management' ); ?></span>
					</div>
					<div class="fmp-portal-card fmp-portal-stat-card">
						<span class="fmp-portal-stat-value"><?php echo absint( $counts['expenses'] ); ?></span>
						<span class="fmp-portal-stat-label"><?php esc_html_e( 'Expenses', 'farm-management' ); ?></span>
					</div>
					<div class="fmp-portal-card fmp-portal-stat-card">
						<span class="fmp-portal-stat-value"><?php echo absint( $counts['vaccinations'] ); ?></span>
						<span class="fmp-portal-stat-label"><?php esc_html_e( 'Vaccinations', 'farm-management' ); ?></span>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** Add Animal form + handler (supports ?id= for edit). */
	public static function render_add_animal( $atts ) {
		if ( ! is_user_logged_in() ) {
			return self::login_required_message();
		}

		$edit_id  = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$is_edit  = false;
		$edit_post = null;
		if ( $edit_id ) {
			$edit_post = get_post( $edit_id );
			$can_edit  = $edit_post && $edit_post->post_type === 'fmp_animal' && ( (int) $edit_post->post_author === get_current_user_id() || ( class_exists( 'FMP_Capabilities' ) && current_user_can( FMP_Capabilities::MANAGE_FARM ) ) );
			if ( $can_edit ) {
				$is_edit = true;
			}
		}

		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['fmp_animal_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_animal_nonce'] ) ), self::NONCE_ANIMAL ) ) {
			$tag     = isset( $_POST['fmp_tag'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_tag'] ) ) : '';
			$species = isset( $_POST['fmp_species'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_species'] ) ) : '';
			$breed   = isset( $_POST['fmp_breed'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_breed'] ) ) : '';
			$sex     = isset( $_POST['fmp_sex'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_sex'] ) ) : '';
			$dob     = isset( $_POST['fmp_dob'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_dob'] ) ) : '';
			$acquired = isset( $_POST['fmp_acquired_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_acquired_date'] ) ) : '';
			$status  = isset( $_POST['fmp_status'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_status'] ) ) : '';
			$weight  = isset( $_POST['fmp_weight'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_weight'] ) ) : '';
			$notes   = isset( $_POST['fmp_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_notes'] ) ) : '';

			$title   = $tag ? $tag : ( $species ? $species : __( 'Animal', 'farm-management' ) );
			$post_id = isset( $_POST['fmp_edit_id'] ) ? (int) $_POST['fmp_edit_id'] : 0;
			if ( $post_id ) {
				$p = get_post( $post_id );
				if ( $p && $p->post_type === 'fmp_animal' && ( (int) $p->post_author === get_current_user_id() || ( class_exists( 'FMP_Capabilities' ) && current_user_can( FMP_Capabilities::MANAGE_FARM ) ) ) ) {
					wp_update_post( array( 'ID' => $post_id, 'post_title' => $title ) );
					update_post_meta( $post_id, '_fmp_tag', $tag );
					update_post_meta( $post_id, '_fmp_species', $species );
					update_post_meta( $post_id, '_fmp_breed', $breed );
					update_post_meta( $post_id, '_fmp_sex', $sex );
					update_post_meta( $post_id, '_fmp_date_of_birth', $dob );
					update_post_meta( $post_id, '_fmp_acquired_date', $acquired );
					update_post_meta( $post_id, '_fmp_status', $status );
					update_post_meta( $post_id, '_fmp_weight', $weight );
					update_post_meta( $post_id, '_fmp_notes', $notes );
					if ( ! empty( $_FILES['fmp_animal_image']['name'] ) && ! empty( $_FILES['fmp_animal_image']['tmp_name'] ) ) {
						$attachment_id = self::handle_animal_image_upload( $post_id );
						if ( $attachment_id ) {
							set_post_thumbnail( $post_id, $attachment_id );
						}
					}
					self::redirect_dashboard( true );
				}
			} else {
				$post_id = wp_insert_post( array(
					'post_type'   => 'fmp_animal',
					'post_status' => 'publish',
					'post_author' => get_current_user_id(),
					'post_title'  => $title,
				) );
				if ( ! is_wp_error( $post_id ) ) {
					update_post_meta( $post_id, '_fmp_tag', $tag );
					update_post_meta( $post_id, '_fmp_species', $species );
					update_post_meta( $post_id, '_fmp_breed', $breed );
					update_post_meta( $post_id, '_fmp_sex', $sex );
					update_post_meta( $post_id, '_fmp_date_of_birth', $dob );
					update_post_meta( $post_id, '_fmp_acquired_date', $acquired );
					update_post_meta( $post_id, '_fmp_status', $status );
					update_post_meta( $post_id, '_fmp_weight', $weight );
					update_post_meta( $post_id, '_fmp_notes', $notes );
					if ( ! empty( $_FILES['fmp_animal_image']['name'] ) && ! empty( $_FILES['fmp_animal_image']['tmp_name'] ) ) {
						$attachment_id = self::handle_animal_image_upload( $post_id );
						if ( $attachment_id ) {
							set_post_thumbnail( $post_id, $attachment_id );
						}
					}
					self::redirect_dashboard( true );
				}
			}
			self::redirect_dashboard( false );
		}

		$tag_val = $species_val = $breed_val = $sex_val = $dob_val = $acquired_val = $status_val = $weight_val = $notes_val = '';
		if ( $is_edit && $edit_post ) {
			$tag_val     = get_post_meta( $edit_post->ID, '_fmp_tag', true );
			$species_val = get_post_meta( $edit_post->ID, '_fmp_species', true );
			$breed_val   = get_post_meta( $edit_post->ID, '_fmp_breed', true );
			$sex_val     = get_post_meta( $edit_post->ID, '_fmp_sex', true );
			$dob_val     = get_post_meta( $edit_post->ID, '_fmp_date_of_birth', true );
			$acquired_val = get_post_meta( $edit_post->ID, '_fmp_acquired_date', true );
			$status_val  = get_post_meta( $edit_post->ID, '_fmp_status', true );
			$weight_val  = get_post_meta( $edit_post->ID, '_fmp_weight', true );
			$notes_val   = get_post_meta( $edit_post->ID, '_fmp_notes', true );
		}

		$dashboard_url = self::get_farm_dashboard_url();
		ob_start();
		echo self::notice_error();
		?>
		<div class="fmp-portal-wrap">
			<div class="fmp-portal-header">
				<h1 class="fmp-portal-heading"><?php echo $is_edit ? esc_html__( 'Edit Animal', 'farm-management' ) : esc_html__( 'Add Animal', 'farm-management' ); ?></h1>
				<a href="<?php echo esc_url( $dashboard_url ); ?>" class="fmp-btn fmp-btn-secondary fmp-portal-back"><?php esc_html_e( '&larr; Dashboard', 'farm-management' ); ?></a>
			</div>
			<div class="fmp-portal-card fmp-portal-form-card">
				<form method="post" action="" class="fmp-portal-form" enctype="multipart/form-data">
					<?php wp_nonce_field( self::NONCE_ANIMAL, 'fmp_animal_nonce' ); ?>
					<?php if ( $is_edit ) : ?><input type="hidden" name="fmp_edit_id" value="<?php echo esc_attr( (string) $edit_post->ID ); ?>" /><?php endif; ?>
					<div class="fmp-form-row">
						<label for="fmp_tag"><?php esc_html_e( 'Tag/ID', 'farm-management' ); ?></label>
						<input type="text" id="fmp_tag" name="fmp_tag" class="fmp-input" value="<?php echo esc_attr( $tag_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_animal_image"><?php esc_html_e( 'Photo', 'farm-management' ); ?></label>
						<input type="file" id="fmp_animal_image" name="fmp_animal_image" class="fmp-input fmp-input-file" accept="image/*" />
						<span class="fmp-form-hint"><?php esc_html_e( 'Optional. JPG, PNG or GIF.', 'farm-management' ); ?></span>
					</div>
					<div class="fmp-form-row">
						<label for="fmp_species"><?php esc_html_e( 'Species', 'farm-management' ); ?></label>
						<input type="text" id="fmp_species" name="fmp_species" class="fmp-input" value="<?php echo esc_attr( $species_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_breed"><?php esc_html_e( 'Breed', 'farm-management' ); ?></label>
						<input type="text" id="fmp_breed" name="fmp_breed" class="fmp-input" value="<?php echo esc_attr( $breed_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_sex"><?php esc_html_e( 'Sex', 'farm-management' ); ?></label>
						<select id="fmp_sex" name="fmp_sex" class="fmp-input">
							<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
							<option value="male" <?php selected( $sex_val, 'male' ); ?>><?php esc_html_e( 'Male', 'farm-management' ); ?></option>
							<option value="female" <?php selected( $sex_val, 'female' ); ?>><?php esc_html_e( 'Female', 'farm-management' ); ?></option>
						</select>
					</div>
					<div class="fmp-form-row">
						<label for="fmp_dob"><?php esc_html_e( 'Date of birth', 'farm-management' ); ?></label>
						<input type="date" id="fmp_dob" name="fmp_dob" class="fmp-input" value="<?php echo esc_attr( $dob_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_acquired_date"><?php esc_html_e( 'Acquired date', 'farm-management' ); ?></label>
						<input type="date" id="fmp_acquired_date" name="fmp_acquired_date" class="fmp-input" value="<?php echo esc_attr( $acquired_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_status"><?php esc_html_e( 'Status', 'farm-management' ); ?></label>
						<select id="fmp_status" name="fmp_status" class="fmp-input">
							<option value="alive" <?php selected( $status_val, 'alive' ); ?>><?php esc_html_e( 'Active', 'farm-management' ); ?></option>
							<option value="sold" <?php selected( $status_val, 'sold' ); ?>><?php esc_html_e( 'Sold', 'farm-management' ); ?></option>
							<option value="dead" <?php selected( $status_val, 'dead' ); ?>><?php esc_html_e( 'Deceased', 'farm-management' ); ?></option>
						</select>
					</div>
					<div class="fmp-form-row">
						<label for="fmp_weight"><?php esc_html_e( 'Weight (kg)', 'farm-management' ); ?></label>
						<input type="number" id="fmp_weight" name="fmp_weight" step="0.01" min="0" class="fmp-input" value="<?php echo esc_attr( $weight_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_notes"><?php esc_html_e( 'Notes', 'farm-management' ); ?></label>
						<textarea id="fmp_notes" name="fmp_notes" rows="4" class="fmp-input"><?php echo esc_textarea( $notes_val ); ?></textarea>
					</div>
					<div class="fmp-form-actions">
						<button type="submit" class="fmp-btn fmp-btn-primary"><?php echo $is_edit ? esc_html__( 'Update Animal', 'farm-management' ) : esc_html__( 'Save Animal', 'farm-management' ); ?></button>
						<a href="<?php echo esc_url( $dashboard_url ); ?>" class="fmp-btn fmp-btn-secondary"><?php esc_html_e( 'Cancel', 'farm-management' ); ?></a>
					</div>
				</form>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** Add Crop form + handler (supports ?id= for edit). */
	public static function render_add_crop( $atts ) {
		if ( ! is_user_logged_in() ) {
			return self::login_required_message();
		}

		$edit_id  = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$is_edit  = false;
		$edit_post = null;
		if ( $edit_id ) {
			$edit_post = get_post( $edit_id );
			$can_edit  = $edit_post && $edit_post->post_type === 'fmp_crop' && ( (int) $edit_post->post_author === get_current_user_id() || ( class_exists( 'FMP_Capabilities' ) && current_user_can( FMP_Capabilities::MANAGE_FARM ) ) );
			if ( $can_edit ) {
				$is_edit = true;
			}
		}

		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['fmp_crop_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_crop_nonce'] ) ), self::NONCE_CROP ) ) {
			$name     = isset( $_POST['fmp_crop_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_crop_name'] ) ) : '';
			$type     = isset( $_POST['fmp_crop_type'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_crop_type'] ) ) : '';
			$location = isset( $_POST['fmp_crop_location'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_crop_location'] ) ) : '';
			$planting = isset( $_POST['fmp_planting_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_planting_date'] ) ) : '';
			$harvest  = isset( $_POST['fmp_harvest_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_harvest_date'] ) ) : '';
			$status   = isset( $_POST['fmp_crop_status'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_crop_status'] ) ) : '';
			$notes    = isset( $_POST['fmp_crop_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_crop_notes'] ) ) : '';

			$title   = $name ? $name : __( 'Crop', 'farm-management' );
			$post_id = isset( $_POST['fmp_edit_id'] ) ? (int) $_POST['fmp_edit_id'] : 0;
			if ( $post_id ) {
				$p = get_post( $post_id );
				if ( $p && $p->post_type === 'fmp_crop' && ( (int) $p->post_author === get_current_user_id() || ( class_exists( 'FMP_Capabilities' ) && current_user_can( FMP_Capabilities::MANAGE_FARM ) ) ) ) {
					wp_update_post( array( 'ID' => $post_id, 'post_title' => $title ) );
					update_post_meta( $post_id, '_fmp_crop_name', $name );
					update_post_meta( $post_id, '_fmp_crop_type', $type );
					update_post_meta( $post_id, '_fmp_field_location', $location );
					update_post_meta( $post_id, '_fmp_planting_date', $planting );
					update_post_meta( $post_id, '_fmp_expected_harvest_date', $harvest );
					update_post_meta( $post_id, '_fmp_crop_status', $status );
					update_post_meta( $post_id, '_fmp_crop_notes', $notes );
					self::redirect_dashboard( true );
				}
			} else {
				$post_id = wp_insert_post( array(
					'post_type'   => 'fmp_crop',
					'post_status' => 'publish',
					'post_author' => get_current_user_id(),
					'post_title'  => $title,
				) );
				if ( ! is_wp_error( $post_id ) ) {
					update_post_meta( $post_id, '_fmp_crop_name', $name );
					update_post_meta( $post_id, '_fmp_crop_type', $type );
					update_post_meta( $post_id, '_fmp_field_location', $location );
					update_post_meta( $post_id, '_fmp_planting_date', $planting );
					update_post_meta( $post_id, '_fmp_expected_harvest_date', $harvest );
					update_post_meta( $post_id, '_fmp_crop_status', $status );
					update_post_meta( $post_id, '_fmp_crop_notes', $notes );
					self::redirect_dashboard( true );
				}
			}
			self::redirect_dashboard( false );
		}

		$name_val = $type_val = $location_val = $planting_val = $harvest_val = $status_val = $notes_val = '';
		if ( $is_edit && $edit_post ) {
			$name_val     = get_post_meta( $edit_post->ID, '_fmp_crop_name', true );
			$type_val     = get_post_meta( $edit_post->ID, '_fmp_crop_type', true );
			$location_val = get_post_meta( $edit_post->ID, '_fmp_field_location', true );
			$planting_val = get_post_meta( $edit_post->ID, '_fmp_planting_date', true );
			$harvest_val  = get_post_meta( $edit_post->ID, '_fmp_expected_harvest_date', true );
			$status_val   = get_post_meta( $edit_post->ID, '_fmp_crop_status', true );
			$notes_val    = get_post_meta( $edit_post->ID, '_fmp_crop_notes', true );
		}

		$dashboard_url = self::get_farm_dashboard_url();
		ob_start();
		echo self::notice_error();
		?>
		<div class="fmp-portal-wrap">
			<div class="fmp-portal-header">
				<h1 class="fmp-portal-heading"><?php echo $is_edit ? esc_html__( 'Edit Crop', 'farm-management' ) : esc_html__( 'Add Crop', 'farm-management' ); ?></h1>
				<a href="<?php echo esc_url( $dashboard_url ); ?>" class="fmp-btn fmp-btn-secondary fmp-portal-back"><?php esc_html_e( '&larr; Dashboard', 'farm-management' ); ?></a>
			</div>
			<div class="fmp-portal-card fmp-portal-form-card">
				<form method="post" action="" class="fmp-portal-form">
					<?php wp_nonce_field( self::NONCE_CROP, 'fmp_crop_nonce' ); ?>
					<?php if ( $is_edit ) : ?><input type="hidden" name="fmp_edit_id" value="<?php echo esc_attr( (string) $edit_post->ID ); ?>" /><?php endif; ?>
					<div class="fmp-form-row">
						<label for="fmp_crop_name"><?php esc_html_e( 'Crop name', 'farm-management' ); ?></label>
						<input type="text" id="fmp_crop_name" name="fmp_crop_name" class="fmp-input" value="<?php echo esc_attr( $name_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_crop_type"><?php esc_html_e( 'Crop type', 'farm-management' ); ?></label>
						<select id="fmp_crop_type" name="fmp_crop_type" class="fmp-input">
							<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
							<option value="veg" <?php selected( $type_val, 'veg' ); ?>><?php esc_html_e( 'Vegetable', 'farm-management' ); ?></option>
							<option value="fruit" <?php selected( $type_val, 'fruit' ); ?>><?php esc_html_e( 'Fruit', 'farm-management' ); ?></option>
							<option value="grain" <?php selected( $type_val, 'grain' ); ?>><?php esc_html_e( 'Grain', 'farm-management' ); ?></option>
						</select>
					</div>
					<div class="fmp-form-row">
						<label for="fmp_crop_location"><?php esc_html_e( 'Field/Location', 'farm-management' ); ?></label>
						<input type="text" id="fmp_crop_location" name="fmp_crop_location" class="fmp-input" value="<?php echo esc_attr( $location_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_planting_date"><?php esc_html_e( 'Planting date', 'farm-management' ); ?></label>
						<input type="date" id="fmp_planting_date" name="fmp_planting_date" class="fmp-input" value="<?php echo esc_attr( $planting_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_harvest_date"><?php esc_html_e( 'Expected harvest date', 'farm-management' ); ?></label>
						<input type="date" id="fmp_harvest_date" name="fmp_harvest_date" class="fmp-input" value="<?php echo esc_attr( $harvest_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_crop_status"><?php esc_html_e( 'Status', 'farm-management' ); ?></label>
						<select id="fmp_crop_status" name="fmp_crop_status" class="fmp-input">
							<option value="" <?php selected( $status_val, '' ); ?>><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
							<option value="planned" <?php selected( $status_val, 'planned' ); ?>><?php esc_html_e( 'Planned', 'farm-management' ); ?></option>
							<option value="planted" <?php selected( $status_val, 'planted' ); ?>><?php esc_html_e( 'Planted', 'farm-management' ); ?></option>
							<option value="growing" <?php selected( $status_val, 'growing' ); ?>><?php esc_html_e( 'Growing', 'farm-management' ); ?></option>
							<option value="harvested" <?php selected( $status_val, 'harvested' ); ?>><?php esc_html_e( 'Harvested', 'farm-management' ); ?></option>
						</select>
					</div>
					<div class="fmp-form-row">
						<label for="fmp_crop_notes"><?php esc_html_e( 'Notes', 'farm-management' ); ?></label>
						<textarea id="fmp_crop_notes" name="fmp_crop_notes" rows="4" class="fmp-input"><?php echo esc_textarea( $notes_val ); ?></textarea>
					</div>
					<div class="fmp-form-actions">
						<button type="submit" class="fmp-btn fmp-btn-primary"><?php echo $is_edit ? esc_html__( 'Update Crop', 'farm-management' ) : esc_html__( 'Save Crop', 'farm-management' ); ?></button>
						<a href="<?php echo esc_url( $dashboard_url ); ?>" class="fmp-btn fmp-btn-secondary"><?php esc_html_e( 'Cancel', 'farm-management' ); ?></a>
					</div>
				</form>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** Add Task form + handler (supports ?id= for edit). */
	public static function render_add_task( $atts ) {
		if ( ! is_user_logged_in() ) {
			return self::login_required_message();
		}

		$edit_id  = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$is_edit  = false;
		$edit_post = null;
		if ( $edit_id ) {
			$edit_post = get_post( $edit_id );
			$can_edit  = $edit_post && $edit_post->post_type === 'fmp_task' && ( (int) $edit_post->post_author === get_current_user_id() || ( class_exists( 'FMP_Capabilities' ) && current_user_can( FMP_Capabilities::MANAGE_FARM ) ) );
			if ( $can_edit ) {
				$is_edit = true;
			}
		}

		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['fmp_task_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_task_nonce'] ) ), self::NONCE_TASK ) ) {
			$title    = isset( $_POST['fmp_task_title'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_task_title'] ) ) : '';
			$due      = isset( $_POST['fmp_due_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_due_date'] ) ) : '';
			$priority = isset( $_POST['fmp_priority'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_priority'] ) ) : '';
			$status   = isset( $_POST['fmp_task_status'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_task_status'] ) ) : '';
			$notes    = isset( $_POST['fmp_task_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_task_notes'] ) ) : '';

			$title   = $title ? $title : __( 'Task', 'farm-management' );
			$post_id = isset( $_POST['fmp_edit_id'] ) ? (int) $_POST['fmp_edit_id'] : 0;
			if ( $post_id ) {
				$p = get_post( $post_id );
				if ( $p && $p->post_type === 'fmp_task' && ( (int) $p->post_author === get_current_user_id() || ( class_exists( 'FMP_Capabilities' ) && current_user_can( FMP_Capabilities::MANAGE_FARM ) ) ) ) {
					wp_update_post( array( 'ID' => $post_id, 'post_title' => $title ) );
					update_post_meta( $post_id, '_fmp_due_date', $due );
					update_post_meta( $post_id, '_fmp_priority', $priority );
					update_post_meta( $post_id, '_fmp_status', $status );
					update_post_meta( $post_id, '_fmp_notes', $notes );
					self::redirect_dashboard( true );
				}
			} else {
				$post_id = wp_insert_post( array(
					'post_type'   => 'fmp_task',
					'post_status' => 'publish',
					'post_author' => get_current_user_id(),
					'post_title'  => $title,
				) );
				if ( ! is_wp_error( $post_id ) ) {
					update_post_meta( $post_id, '_fmp_due_date', $due );
					update_post_meta( $post_id, '_fmp_priority', $priority );
					update_post_meta( $post_id, '_fmp_status', $status );
					update_post_meta( $post_id, '_fmp_notes', $notes );
					self::redirect_dashboard( true );
				}
			}
			self::redirect_dashboard( false );
		}

		$title_val = $due_val = $priority_val = $status_val = $notes_val = '';
		if ( $is_edit && $edit_post ) {
			$title_val    = $edit_post->post_title;
			$due_val      = get_post_meta( $edit_post->ID, '_fmp_due_date', true );
			$priority_val = get_post_meta( $edit_post->ID, '_fmp_priority', true );
			$status_val   = get_post_meta( $edit_post->ID, '_fmp_status', true );
			$notes_val    = get_post_meta( $edit_post->ID, '_fmp_notes', true );
		}

		$dashboard_url = self::get_farm_dashboard_url();
		ob_start();
		echo self::notice_error();
		?>
		<div class="fmp-portal-wrap">
			<div class="fmp-portal-header">
				<h1 class="fmp-portal-heading"><?php echo $is_edit ? esc_html__( 'Edit Task', 'farm-management' ) : esc_html__( 'Add Task', 'farm-management' ); ?></h1>
				<a href="<?php echo esc_url( $dashboard_url ); ?>" class="fmp-btn fmp-btn-secondary fmp-portal-back"><?php esc_html_e( '&larr; Dashboard', 'farm-management' ); ?></a>
			</div>
			<div class="fmp-portal-card fmp-portal-form-card">
				<form method="post" action="" class="fmp-portal-form">
					<?php wp_nonce_field( self::NONCE_TASK, 'fmp_task_nonce' ); ?>
					<?php if ( $is_edit ) : ?><input type="hidden" name="fmp_edit_id" value="<?php echo esc_attr( (string) $edit_post->ID ); ?>" /><?php endif; ?>
					<div class="fmp-form-row">
						<label for="fmp_task_title"><?php esc_html_e( 'Title', 'farm-management' ); ?></label>
						<input type="text" id="fmp_task_title" name="fmp_task_title" class="fmp-input" value="<?php echo esc_attr( $title_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_due_date"><?php esc_html_e( 'Due date', 'farm-management' ); ?></label>
						<input type="date" id="fmp_due_date" name="fmp_due_date" class="fmp-input" value="<?php echo esc_attr( $due_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_priority"><?php esc_html_e( 'Priority', 'farm-management' ); ?></label>
						<select id="fmp_priority" name="fmp_priority" class="fmp-input">
							<option value="low" <?php selected( $priority_val, 'low' ); ?>><?php esc_html_e( 'Low', 'farm-management' ); ?></option>
							<option value="medium" <?php selected( $priority_val, 'medium' ); ?>><?php esc_html_e( 'Medium', 'farm-management' ); ?></option>
							<option value="high" <?php selected( $priority_val, 'high' ); ?>><?php esc_html_e( 'High', 'farm-management' ); ?></option>
						</select>
					</div>
					<div class="fmp-form-row">
						<label for="fmp_task_status"><?php esc_html_e( 'Status', 'farm-management' ); ?></label>
						<select id="fmp_task_status" name="fmp_task_status" class="fmp-input">
							<option value="open" <?php selected( $status_val, 'open' ); ?>><?php esc_html_e( 'Pending', 'farm-management' ); ?></option>
							<option value="in-progress" <?php selected( $status_val, 'in-progress' ); ?>><?php esc_html_e( 'In Progress', 'farm-management' ); ?></option>
							<option value="done" <?php selected( $status_val, 'done' ); ?>><?php esc_html_e( 'Done', 'farm-management' ); ?></option>
						</select>
					</div>
					<div class="fmp-form-row">
						<label for="fmp_task_notes"><?php esc_html_e( 'Notes', 'farm-management' ); ?></label>
						<textarea id="fmp_task_notes" name="fmp_task_notes" rows="4" class="fmp-input"><?php echo esc_textarea( $notes_val ); ?></textarea>
					</div>
					<div class="fmp-form-actions">
						<button type="submit" class="fmp-btn fmp-btn-primary"><?php echo $is_edit ? esc_html__( 'Update Task', 'farm-management' ) : esc_html__( 'Save Task', 'farm-management' ); ?></button>
						<a href="<?php echo esc_url( $dashboard_url ); ?>" class="fmp-btn fmp-btn-secondary"><?php esc_html_e( 'Cancel', 'farm-management' ); ?></a>
					</div>
				</form>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** Add Inventory form + handler (supports ?id= for edit). */
	public static function render_add_inventory( $atts ) {
		if ( ! is_user_logged_in() ) {
			return self::login_required_message();
		}

		$edit_id  = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$is_edit  = false;
		$edit_post = null;
		if ( $edit_id ) {
			$edit_post = get_post( $edit_id );
			$can_edit  = $edit_post && $edit_post->post_type === 'fmp_inventory_item' && ( (int) $edit_post->post_author === get_current_user_id() || ( class_exists( 'FMP_Capabilities' ) && current_user_can( FMP_Capabilities::MANAGE_FARM ) ) );
			if ( $can_edit ) {
				$is_edit = true;
			}
		}

		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['fmp_inventory_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_inventory_nonce'] ) ), self::NONCE_INVENTORY ) ) {
			$name    = isset( $_POST['fmp_item_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_item_name'] ) ) : '';
			$cat     = isset( $_POST['fmp_inv_category'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_inv_category'] ) ) : '';
			$qty     = isset( $_POST['fmp_quantity'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_quantity'] ) ) : '';
			$unit    = isset( $_POST['fmp_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_unit'] ) ) : '';
			$reorder = isset( $_POST['fmp_reorder_level'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_reorder_level'] ) ) : '';
			$notes   = isset( $_POST['fmp_inv_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_inv_notes'] ) ) : '';

			$title   = $name ? $name : __( 'Inventory item', 'farm-management' );
			$post_id = isset( $_POST['fmp_edit_id'] ) ? (int) $_POST['fmp_edit_id'] : 0;
			if ( $post_id ) {
				$p = get_post( $post_id );
				if ( $p && $p->post_type === 'fmp_inventory_item' && ( (int) $p->post_author === get_current_user_id() || ( class_exists( 'FMP_Capabilities' ) && current_user_can( FMP_Capabilities::MANAGE_FARM ) ) ) ) {
					wp_update_post( array( 'ID' => $post_id, 'post_title' => $title ) );
					update_post_meta( $post_id, '_fmp_item_name', $name );
					update_post_meta( $post_id, '_fmp_category', $cat );
					update_post_meta( $post_id, '_fmp_quantity', $qty );
					update_post_meta( $post_id, '_fmp_unit', $unit );
					update_post_meta( $post_id, '_fmp_reorder_level', $reorder );
					update_post_meta( $post_id, '_fmp_notes', $notes );
					self::redirect_dashboard( true );
				}
			} else {
				$post_id = wp_insert_post( array(
					'post_type'   => 'fmp_inventory_item',
					'post_status' => 'publish',
					'post_author' => get_current_user_id(),
					'post_title'  => $title,
				) );
				if ( ! is_wp_error( $post_id ) ) {
					update_post_meta( $post_id, '_fmp_item_name', $name );
					update_post_meta( $post_id, '_fmp_category', $cat );
					update_post_meta( $post_id, '_fmp_quantity', $qty );
					update_post_meta( $post_id, '_fmp_unit', $unit );
					update_post_meta( $post_id, '_fmp_reorder_level', $reorder );
					update_post_meta( $post_id, '_fmp_notes', $notes );
					self::redirect_dashboard( true );
				}
			}
			self::redirect_dashboard( false );
		}

		$name_val = $cat_val = $qty_val = $unit_val = $reorder_val = $notes_val = '';
		if ( $is_edit && $edit_post ) {
			$name_val    = get_post_meta( $edit_post->ID, '_fmp_item_name', true );
			if ( ! $name_val ) {
				$name_val = $edit_post->post_title;
			}
			$cat_val     = get_post_meta( $edit_post->ID, '_fmp_category', true );
			$qty_val     = get_post_meta( $edit_post->ID, '_fmp_quantity', true );
			$unit_val    = get_post_meta( $edit_post->ID, '_fmp_unit', true );
			$reorder_val = get_post_meta( $edit_post->ID, '_fmp_reorder_level', true );
			$notes_val   = get_post_meta( $edit_post->ID, '_fmp_notes', true );
		}

		$dashboard_url = self::get_farm_dashboard_url();
		ob_start();
		echo self::notice_error();
		?>
		<div class="fmp-portal-wrap">
			<div class="fmp-portal-header">
				<h1 class="fmp-portal-heading"><?php echo $is_edit ? esc_html__( 'Edit Inventory', 'farm-management' ) : esc_html__( 'Add Inventory', 'farm-management' ); ?></h1>
				<a href="<?php echo esc_url( $dashboard_url ); ?>" class="fmp-btn fmp-btn-secondary fmp-portal-back"><?php esc_html_e( '&larr; Dashboard', 'farm-management' ); ?></a>
			</div>
			<div class="fmp-portal-card fmp-portal-form-card">
				<form method="post" action="" class="fmp-portal-form">
					<?php wp_nonce_field( self::NONCE_INVENTORY, 'fmp_inventory_nonce' ); ?>
					<?php if ( $is_edit ) : ?><input type="hidden" name="fmp_edit_id" value="<?php echo esc_attr( (string) $edit_post->ID ); ?>" /><?php endif; ?>
					<div class="fmp-form-row">
						<label for="fmp_item_name"><?php esc_html_e( 'Item name', 'farm-management' ); ?></label>
						<input type="text" id="fmp_item_name" name="fmp_item_name" class="fmp-input" value="<?php echo esc_attr( $name_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_inv_category"><?php esc_html_e( 'Category', 'farm-management' ); ?></label>
						<select id="fmp_inv_category" name="fmp_inv_category" class="fmp-input">
							<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
							<option value="feed" <?php selected( $cat_val, 'feed' ); ?>><?php esc_html_e( 'Feed', 'farm-management' ); ?></option>
							<option value="medicine" <?php selected( $cat_val, 'medicine' ); ?>><?php esc_html_e( 'Medicine', 'farm-management' ); ?></option>
							<option value="equipment" <?php selected( $cat_val, 'equipment' ); ?>><?php esc_html_e( 'Equipment', 'farm-management' ); ?></option>
							<option value="other" <?php selected( $cat_val, 'other' ); ?>><?php esc_html_e( 'Other', 'farm-management' ); ?></option>
						</select>
					</div>
					<div class="fmp-form-row">
						<label for="fmp_quantity"><?php esc_html_e( 'Quantity', 'farm-management' ); ?></label>
						<input type="number" id="fmp_quantity" name="fmp_quantity" min="0" step="1" class="fmp-input" value="<?php echo esc_attr( $qty_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_unit"><?php esc_html_e( 'Unit', 'farm-management' ); ?></label>
						<select id="fmp_unit" name="fmp_unit" class="fmp-input">
							<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
							<option value="kg" <?php selected( $unit_val, 'kg' ); ?>><?php esc_html_e( 'kg', 'farm-management' ); ?></option>
							<option value="l" <?php selected( $unit_val, 'l' ); ?>><?php esc_html_e( 'L', 'farm-management' ); ?></option>
							<option value="pcs" <?php selected( $unit_val, 'pcs' ); ?>><?php esc_html_e( 'pcs', 'farm-management' ); ?></option>
						</select>
					</div>
					<div class="fmp-form-row">
						<label for="fmp_reorder_level"><?php esc_html_e( 'Reorder level', 'farm-management' ); ?></label>
						<input type="number" id="fmp_reorder_level" name="fmp_reorder_level" min="0" step="1" class="fmp-input" value="<?php echo esc_attr( $reorder_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_inv_notes"><?php esc_html_e( 'Notes', 'farm-management' ); ?></label>
						<textarea id="fmp_inv_notes" name="fmp_inv_notes" rows="4" class="fmp-input"><?php echo esc_textarea( $notes_val ); ?></textarea>
					</div>
					<div class="fmp-form-actions">
						<button type="submit" class="fmp-btn fmp-btn-primary"><?php echo $is_edit ? esc_html__( 'Update Inventory', 'farm-management' ) : esc_html__( 'Save Inventory', 'farm-management' ); ?></button>
						<a href="<?php echo esc_url( $dashboard_url ); ?>" class="fmp-btn fmp-btn-secondary"><?php esc_html_e( 'Cancel', 'farm-management' ); ?></a>
					</div>
				</form>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** Add Expense form + handler (supports ?id= for edit). */
	public static function render_add_expense( $atts ) {
		if ( ! is_user_logged_in() ) {
			return self::login_required_message();
		}

		$edit_id  = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$is_edit  = false;
		$edit_post = null;
		if ( $edit_id ) {
			$edit_post = get_post( $edit_id );
			$can_edit  = $edit_post && $edit_post->post_type === 'fmp_expense' && ( (int) $edit_post->post_author === get_current_user_id() || ( class_exists( 'FMP_Capabilities' ) && current_user_can( FMP_Capabilities::MANAGE_FARM ) ) );
			if ( $can_edit ) {
				$is_edit = true;
			}
		}

		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['fmp_expense_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_expense_nonce'] ) ), self::NONCE_EXPENSE ) ) {
			$title  = isset( $_POST['fmp_expense_title'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_expense_title'] ) ) : '';
			$amount = isset( $_POST['fmp_amount'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_amount'] ) ) : '';
			$date   = isset( $_POST['fmp_expense_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_expense_date'] ) ) : '';
			$cat    = isset( $_POST['fmp_expense_category'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_expense_category'] ) ) : '';
			$notes  = isset( $_POST['fmp_expense_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_expense_notes'] ) ) : '';

			$title   = $title ? $title : __( 'Expense', 'farm-management' );
			$post_id = isset( $_POST['fmp_edit_id'] ) ? (int) $_POST['fmp_edit_id'] : 0;
			if ( $post_id ) {
				$p = get_post( $post_id );
				if ( $p && $p->post_type === 'fmp_expense' && ( (int) $p->post_author === get_current_user_id() || ( class_exists( 'FMP_Capabilities' ) && current_user_can( FMP_Capabilities::MANAGE_FARM ) ) ) ) {
					wp_update_post( array( 'ID' => $post_id, 'post_title' => $title ) );
					update_post_meta( $post_id, '_fmp_amount', $amount );
					update_post_meta( $post_id, '_fmp_date', $date );
					update_post_meta( $post_id, '_fmp_category', $cat );
					update_post_meta( $post_id, '_fmp_notes', $notes );
					self::redirect_dashboard( true );
				}
			} else {
				$post_id = wp_insert_post( array(
					'post_type'   => 'fmp_expense',
					'post_status' => 'publish',
					'post_author' => get_current_user_id(),
					'post_title'  => $title,
				) );
				if ( ! is_wp_error( $post_id ) ) {
					update_post_meta( $post_id, '_fmp_amount', $amount );
					update_post_meta( $post_id, '_fmp_date', $date );
					update_post_meta( $post_id, '_fmp_category', $cat );
					update_post_meta( $post_id, '_fmp_notes', $notes );
					self::redirect_dashboard( true );
				}
			}
			self::redirect_dashboard( false );
		}

		$title_val = $amount_val = $date_val = $cat_val = $notes_val = '';
		if ( $is_edit && $edit_post ) {
			$title_val  = $edit_post->post_title;
			$amount_val = get_post_meta( $edit_post->ID, '_fmp_amount', true );
			$date_val   = get_post_meta( $edit_post->ID, '_fmp_date', true );
			$cat_val    = get_post_meta( $edit_post->ID, '_fmp_category', true );
			$notes_val  = get_post_meta( $edit_post->ID, '_fmp_notes', true );
		}

		$dashboard_url = self::get_farm_dashboard_url();
		ob_start();
		echo self::notice_error();
		?>
		<div class="fmp-portal-wrap">
			<div class="fmp-portal-header">
				<h1 class="fmp-portal-heading"><?php echo $is_edit ? esc_html__( 'Edit Expense', 'farm-management' ) : esc_html__( 'Add Expense', 'farm-management' ); ?></h1>
				<a href="<?php echo esc_url( $dashboard_url ); ?>" class="fmp-btn fmp-btn-secondary fmp-portal-back"><?php esc_html_e( '&larr; Dashboard', 'farm-management' ); ?></a>
			</div>
			<div class="fmp-portal-card fmp-portal-form-card">
				<form method="post" action="" class="fmp-portal-form">
					<?php wp_nonce_field( self::NONCE_EXPENSE, 'fmp_expense_nonce' ); ?>
					<?php if ( $is_edit ) : ?><input type="hidden" name="fmp_edit_id" value="<?php echo esc_attr( (string) $edit_post->ID ); ?>" /><?php endif; ?>
					<div class="fmp-form-row">
						<label for="fmp_expense_title"><?php esc_html_e( 'Title', 'farm-management' ); ?></label>
						<input type="text" id="fmp_expense_title" name="fmp_expense_title" class="fmp-input" value="<?php echo esc_attr( $title_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_amount"><?php esc_html_e( 'Amount', 'farm-management' ); ?></label>
						<input type="number" id="fmp_amount" name="fmp_amount" step="0.01" min="0" class="fmp-input" value="<?php echo esc_attr( $amount_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_expense_date"><?php esc_html_e( 'Date', 'farm-management' ); ?></label>
						<input type="date" id="fmp_expense_date" name="fmp_expense_date" class="fmp-input" value="<?php echo esc_attr( $date_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_expense_category"><?php esc_html_e( 'Category', 'farm-management' ); ?></label>
						<select id="fmp_expense_category" name="fmp_expense_category" class="fmp-input">
							<option value="feed" <?php selected( $cat_val, 'feed' ); ?>><?php esc_html_e( 'Feed', 'farm-management' ); ?></option>
							<option value="vet" <?php selected( $cat_val, 'vet' ); ?>><?php esc_html_e( 'Vet', 'farm-management' ); ?></option>
							<option value="fuel" <?php selected( $cat_val, 'fuel' ); ?>><?php esc_html_e( 'Fuel', 'farm-management' ); ?></option>
							<option value="labour" <?php selected( $cat_val, 'labour' ); ?>><?php esc_html_e( 'Labour', 'farm-management' ); ?></option>
							<option value="other" <?php selected( $cat_val, 'other' ); ?>><?php esc_html_e( 'Other', 'farm-management' ); ?></option>
						</select>
					</div>
					<div class="fmp-form-row">
						<label for="fmp_expense_notes"><?php esc_html_e( 'Notes', 'farm-management' ); ?></label>
						<textarea id="fmp_expense_notes" name="fmp_expense_notes" rows="4" class="fmp-input"><?php echo esc_textarea( $notes_val ); ?></textarea>
					</div>
					<div class="fmp-form-actions">
						<button type="submit" class="fmp-btn fmp-btn-primary"><?php echo $is_edit ? esc_html__( 'Update Expense', 'farm-management' ) : esc_html__( 'Save Expense', 'farm-management' ); ?></button>
						<a href="<?php echo esc_url( $dashboard_url ); ?>" class="fmp-btn fmp-btn-secondary"><?php esc_html_e( 'Cancel', 'farm-management' ); ?></a>
					</div>
				</form>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** Add Vaccination form + handler (supports ?id= for edit). */
	public static function render_add_vaccination( $atts ) {
		if ( ! is_user_logged_in() ) {
			return self::login_required_message();
		}

		$uid = get_current_user_id();
		$animals = get_posts( array(
			'post_type'      => 'fmp_animal',
			'post_status'    => 'publish',
			'author'         => $uid,
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		$edit_id  = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$is_edit  = false;
		$edit_post = null;
		if ( $edit_id ) {
			$edit_post = get_post( $edit_id );
			$can_edit  = $edit_post && $edit_post->post_type === 'fmp_vaccination' && ( (int) $edit_post->post_author === get_current_user_id() || ( class_exists( 'FMP_Capabilities' ) && current_user_can( FMP_Capabilities::MANAGE_FARM ) ) );
			if ( $can_edit ) {
				$is_edit = true;
			}
		}

		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['fmp_vacc_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_vacc_nonce'] ) ), self::NONCE_VACC ) ) {
			$animal_id  = isset( $_POST['fmp_animal_id'] ) ? absint( $_POST['fmp_animal_id'] ) : 0;
			$vaccine    = isset( $_POST['fmp_vaccine_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_vaccine_name'] ) ) : '';
			$date_given = isset( $_POST['fmp_date_given'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_date_given'] ) ) : '';
			$next_due   = isset( $_POST['fmp_next_due_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_next_due_date'] ) ) : '';
			$notes      = isset( $_POST['fmp_vax_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_vax_notes'] ) ) : '';

			$valid_animal = false;
			foreach ( $animals as $a ) {
				if ( (int) $a->ID === $animal_id ) {
					$valid_animal = true;
					break;
				}
			}
			if ( $valid_animal && $next_due ) {
				$post_id = isset( $_POST['fmp_edit_id'] ) ? (int) $_POST['fmp_edit_id'] : 0;
				if ( $post_id ) {
					$p = get_post( $post_id );
					if ( $p && $p->post_type === 'fmp_vaccination' && ( (int) $p->post_author === get_current_user_id() || ( class_exists( 'FMP_Capabilities' ) && current_user_can( FMP_Capabilities::MANAGE_FARM ) ) ) ) {
						wp_update_post( array( 'ID' => $post_id, 'post_title' => $vaccine ? $vaccine : __( 'Vaccination', 'farm-management' ) ) );
						update_post_meta( $post_id, '_fmp_animal_id', $animal_id );
						update_post_meta( $post_id, '_fmp_vaccine_name', $vaccine );
						update_post_meta( $post_id, '_fmp_date_given', $date_given );
						update_post_meta( $post_id, '_fmp_next_due_date', $next_due );
						update_post_meta( $post_id, '_fmp_notes', $notes );
						self::redirect_dashboard( true );
					}
				} else {
					$post_id = wp_insert_post( array(
						'post_type'   => 'fmp_vaccination',
						'post_status' => 'publish',
						'post_author' => $uid,
						'post_title'  => $vaccine ? $vaccine : __( 'Vaccination', 'farm-management' ),
					) );
					if ( ! is_wp_error( $post_id ) ) {
						update_post_meta( $post_id, '_fmp_animal_id', $animal_id );
						update_post_meta( $post_id, '_fmp_vaccine_name', $vaccine );
						update_post_meta( $post_id, '_fmp_date_given', $date_given );
						update_post_meta( $post_id, '_fmp_next_due_date', $next_due );
						update_post_meta( $post_id, '_fmp_notes', $notes );
						self::redirect_dashboard( true );
					}
				}
			}
			self::redirect_dashboard( false );
		}

		$animal_val = $vaccine_val = $date_given_val = $next_due_val = $notes_val = '';
		if ( $is_edit && $edit_post ) {
			$animal_val     = (int) get_post_meta( $edit_post->ID, '_fmp_animal_id', true );
			$vaccine_val    = get_post_meta( $edit_post->ID, '_fmp_vaccine_name', true );
			$date_given_val = get_post_meta( $edit_post->ID, '_fmp_date_given', true );
			$next_due_val   = get_post_meta( $edit_post->ID, '_fmp_next_due_date', true );
			$notes_val      = get_post_meta( $edit_post->ID, '_fmp_notes', true );
		}

		$dashboard_url = self::get_farm_dashboard_url();
		ob_start();
		echo self::notice_error();
		?>
		<div class="fmp-portal-wrap">
			<div class="fmp-portal-header">
				<h1 class="fmp-portal-heading"><?php echo $is_edit ? esc_html__( 'Edit Vaccination', 'farm-management' ) : esc_html__( 'Add Vaccination', 'farm-management' ); ?></h1>
				<a href="<?php echo esc_url( $dashboard_url ); ?>" class="fmp-btn fmp-btn-secondary fmp-portal-back"><?php esc_html_e( '&larr; Dashboard', 'farm-management' ); ?></a>
			</div>
			<div class="fmp-portal-card fmp-portal-form-card">
				<?php if ( empty( $animals ) && ! $is_edit ) : ?>
					<p class="fmp-portal-muted"><?php esc_html_e( 'Add at least one animal first, then you can record vaccinations.', 'farm-management' ); ?></p>
					<a href="<?php echo esc_url( self::get_add_url( 'add_animal' ) ); ?>" class="fmp-btn fmp-btn-primary"><?php esc_html_e( 'Add Animal', 'farm-management' ); ?></a>
				<?php else : ?>
				<form method="post" action="" class="fmp-portal-form">
					<?php wp_nonce_field( self::NONCE_VACC, 'fmp_vacc_nonce' ); ?>
					<?php if ( $is_edit ) : ?><input type="hidden" name="fmp_edit_id" value="<?php echo esc_attr( (string) $edit_post->ID ); ?>" /><?php endif; ?>
					<div class="fmp-form-row">
						<label for="fmp_animal_id"><?php esc_html_e( 'Animal', 'farm-management' ); ?></label>
						<select id="fmp_animal_id" name="fmp_animal_id" class="fmp-input" required="required">
							<option value=""><?php esc_html_e( '— Select Animal —', 'farm-management' ); ?></option>
							<?php foreach ( $animals as $a ) :
								$label = get_post_meta( $a->ID, '_fmp_tag', true );
								$label = $label ? $label : $a->post_title;
								?>
								<option value="<?php echo esc_attr( $a->ID ); ?>" <?php selected( $animal_val, $a->ID ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="fmp-form-row">
						<label for="fmp_vaccine_name"><?php esc_html_e( 'Vaccine name', 'farm-management' ); ?></label>
						<input type="text" id="fmp_vaccine_name" name="fmp_vaccine_name" class="fmp-input" value="<?php echo esc_attr( $vaccine_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_date_given"><?php esc_html_e( 'Date given', 'farm-management' ); ?></label>
						<input type="date" id="fmp_date_given" name="fmp_date_given" class="fmp-input" value="<?php echo esc_attr( $date_given_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_next_due_date"><?php esc_html_e( 'Next due date', 'farm-management' ); ?></label>
						<input type="date" id="fmp_next_due_date" name="fmp_next_due_date" class="fmp-input" required="required" value="<?php echo esc_attr( $next_due_val ); ?>" />
					</div>
					<div class="fmp-form-row">
						<label for="fmp_vax_notes"><?php esc_html_e( 'Notes', 'farm-management' ); ?></label>
						<textarea id="fmp_vax_notes" name="fmp_vax_notes" rows="4" class="fmp-input"><?php echo esc_textarea( $notes_val ); ?></textarea>
					</div>
					<div class="fmp-form-actions">
						<button type="submit" class="fmp-btn fmp-btn-primary"><?php echo $is_edit ? esc_html__( 'Update Vaccination', 'farm-management' ) : esc_html__( 'Save Vaccination', 'farm-management' ); ?></button>
						<a href="<?php echo esc_url( $dashboard_url ); ?>" class="fmp-btn fmp-btn-secondary"><?php esc_html_e( 'Cancel', 'farm-management' ); ?></a>
					</div>
				</form>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Create portal pages and store IDs in option. Call on activation or from admin Setup button.
	 *
	 * @return array Map of key => page_id.
	 */
	public static function setup_pages() {
		$pages = array(
			'dashboard'   => array( 'title' => __( 'Farm Portal', 'farm-management' ), 'slug' => 'farm-portal', 'shortcode' => '[fmp_portal_dashboard]' ),
			'add_animal'  => array( 'title' => __( 'Add Animal', 'farm-management' ), 'slug' => 'add-animal', 'shortcode' => '[fmp_add_animal]' ),
			'add_crop'    => array( 'title' => __( 'Add Crop', 'farm-management' ), 'slug' => 'add-crop', 'shortcode' => '[fmp_add_crop]' ),
			'add_task'    => array( 'title' => __( 'Add Task', 'farm-management' ), 'slug' => 'add-task', 'shortcode' => '[fmp_add_task]' ),
			'add_inventory' => array( 'title' => __( 'Add Inventory', 'farm-management' ), 'slug' => 'add-inventory', 'shortcode' => '[fmp_add_inventory]' ),
			'add_expense' => array( 'title' => __( 'Add Expense', 'farm-management' ), 'slug' => 'add-expense', 'shortcode' => '[fmp_add_expense]' ),
			'add_vaccination' => array( 'title' => __( 'Add Vaccination', 'farm-management' ), 'slug' => 'add-vaccination', 'shortcode' => '[fmp_add_vaccination]' ),
		);
		$ids = array();
		foreach ( $pages as $key => $config ) {
			$slug = sanitize_title( $config['slug'] );
			$existing = get_page_by_path( $slug );
			if ( $existing ) {
				$ids[ $key ] = (int) $existing->ID;
				continue;
			}
			$id = wp_insert_post( array(
				'post_title'   => $config['title'],
				'post_name'    => $slug,
				'post_content' => $config['shortcode'],
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_author'  => 1,
			) );
			if ( ! is_wp_error( $id ) ) {
				$ids[ $key ] = (int) $id;
			}
		}
		update_option( self::OPTION_PAGES, $ids );
		return $ids;
	}
}
