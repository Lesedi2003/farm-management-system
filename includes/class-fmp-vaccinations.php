<?php
/**
 * Vaccinations CPT and meta.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Vaccinations
 */
class FMP_Vaccinations {

	/**
	 * Nonce action for saving vaccination meta.
	 */
	const NONCE_ACTION = 'fmp_save_vaccination';

	/**
	 * Transient key for validation error (value: post ID).
	 */
	const TRANSIENT_VALIDATION_ERROR = 'fmp_vaccination_validation_error';

	/**
	 * Constructor. Hooks into WordPress.
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ), 10 );
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ), 10 );
		add_action( 'save_post_fmp_vaccination', array( $this, 'save_meta' ), 10, 2 );
		add_filter( 'manage_fmp_vaccination_posts_columns', array( $this, 'set_columns' ), 10 );
		add_action( 'manage_fmp_vaccination_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'show_validation_error' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_animal_search_script' ), 10 );
	}

	/**
	 * Register the fmp_vaccination post type.
	 */
	public static function register_cpt() {
		$labels = array(
			'name'                  => _x( 'Vaccinations', 'Post type general name', 'farm-management' ),
			'singular_name'         => _x( 'Vaccination', 'Post type singular name', 'farm-management' ),
			'menu_name'             => _x( 'Vaccinations', 'Admin Menu text', 'farm-management' ),
			'add_new'               => __( 'Add New', 'farm-management' ),
			'add_new_item'          => __( 'Add New Vaccination', 'farm-management' ),
			'new_item'              => __( 'New Vaccination', 'farm-management' ),
			'edit_item'             => __( 'Edit Vaccination', 'farm-management' ),
			'view_item'             => __( 'View Vaccination', 'farm-management' ),
			'all_items'             => __( 'All Vaccinations', 'farm-management' ),
			'search_items'          => __( 'Search Vaccinations', 'farm-management' ),
			'not_found'              => __( 'No vaccinations found.', 'farm-management' ),
			'not_found_in_trash'    => __( 'No vaccinations found in Trash.', 'farm-management' ),
			'item_published'        => __( 'Vaccination published.', 'farm-management' ),
			'item_updated'          => __( 'Vaccination updated.', 'farm-management' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'query_var'           => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' ),
		);

		register_post_type( 'fmp_vaccination', $args );
	}

	/**
	 * Register the Vaccination Details meta box.
	 */
	public function register_meta_box() {
		add_meta_box(
			'fmp_vaccination_details',
			__( 'Vaccination Details', 'farm-management' ),
			array( $this, 'render_meta_box' ),
			'fmp_vaccination',
			'normal',
			'default'
		);
	}

	/**
	 * Render the Vaccination Details meta box.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( self::NONCE_ACTION, 'fmp_vaccination_nonce' );

		$animal_id    = (int) get_post_meta( $post->ID, '_fmp_animal_id', true );
		$vaccine_name = get_post_meta( $post->ID, '_fmp_vaccine_name', true );
		$date_given   = get_post_meta( $post->ID, '_fmp_date_given', true );
		$next_due     = get_post_meta( $post->ID, '_fmp_next_due_date', true );
		$location     = get_post_meta( $post->ID, '_fmp_vaccination_location', true );
		$notes        = get_post_meta( $post->ID, '_fmp_notes', true );

		$animals = get_posts( array(
			'post_type'      => 'fmp_animal',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		?>
		<div class="fmp-meta-box fmp-vaccination-details">
			<div class="fmp-meta-row">
				<label for="fmp_animal_search"><?php esc_html_e( 'Animal', 'farm-management' ); ?></label>
				<input type="text" id="fmp_animal_search" class="fmp-animal-search regular-text" placeholder="<?php esc_attr_e( 'Search animals…', 'farm-management' ); ?>" autocomplete="off" />
				<select id="fmp_animal_id" name="fmp_animal_id" class="fmp-animal-select" required="required">
					<option value=""><?php esc_html_e( '— Select Animal —', 'farm-management' ); ?></option>
					<?php foreach ( $animals as $animal ) : ?>
						<option value="<?php echo esc_attr( $animal->ID ); ?>" <?php selected( $animal_id, $animal->ID ); ?> data-label="<?php echo esc_attr( get_the_title( $animal->ID ) ); ?>">
							<?php echo esc_html( get_the_title( $animal->ID ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_vaccine_name"><?php esc_html_e( 'Vaccine Name', 'farm-management' ); ?></label>
				<input type="text" id="fmp_vaccine_name" name="fmp_vaccine_name" value="<?php echo esc_attr( $vaccine_name ); ?>" class="regular-text" />
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_date_given"><?php esc_html_e( 'Date Given', 'farm-management' ); ?></label>
				<input type="date" id="fmp_date_given" name="fmp_date_given" value="<?php echo esc_attr( $date_given ); ?>" />
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_next_due_date"><?php esc_html_e( 'Next Due Date', 'farm-management' ); ?></label>
				<input type="date" id="fmp_next_due_date" name="fmp_next_due_date" value="<?php echo esc_attr( $next_due ); ?>" required="required" />
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_vaccination_location"><?php esc_html_e( 'Location', 'farm-management' ); ?></label>
				<input type="text" id="fmp_vaccination_location" name="fmp_vaccination_location" value="<?php echo esc_attr( $location ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Where the animal will be vaccinated', 'farm-management' ); ?>" />
			</div>
			<div class="fmp-meta-row fmp-meta-row-full">
				<label for="fmp_notes"><?php esc_html_e( 'Notes', 'farm-management' ); ?></label>
				<textarea id="fmp_notes" name="fmp_notes" rows="4" class="large-text"><?php echo esc_textarea( $notes ); ?></textarea>
			</div>
		</div>
		<?php
	}

	/**
	 * Save vaccination meta.
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
		if ( ! isset( $_POST['fmp_vaccination_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_vaccination_nonce'] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
			return;
		}

		$animal_id   = isset( $_POST['fmp_animal_id'] ) ? absint( $_POST['fmp_animal_id'] ) : 0;
		$vaccine_name = isset( $_POST['fmp_vaccine_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_vaccine_name'] ) ) : '';
		$date_given   = isset( $_POST['fmp_date_given'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_date_given'] ) ) : '';
		$next_due     = isset( $_POST['fmp_next_due_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_next_due_date'] ) ) : '';
		$location     = isset( $_POST['fmp_vaccination_location'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_vaccination_location'] ) ) : '';
		$notes        = isset( $_POST['fmp_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_notes'] ) ) : '';

		$animal_valid = $animal_id > 0;
		if ( $animal_valid ) {
			$animal_post = get_post( $animal_id );
			$animal_valid = $animal_post && $animal_post->post_type === 'fmp_animal';
		}
		$next_due_valid = self::is_valid_date( $next_due );

		if ( ! $animal_valid ) {
			set_transient( self::TRANSIENT_VALIDATION_ERROR . '_' . $post_id, __( 'Please select a valid animal.', 'farm-management' ), 45 );
			return;
		}
		if ( ! $next_due_valid ) {
			set_transient( self::TRANSIENT_VALIDATION_ERROR . '_' . $post_id, __( 'Next due date is required and must be in Y-m-d format.', 'farm-management' ), 45 );
			return;
		}

		update_post_meta( $post_id, '_fmp_animal_id', $animal_id );
		update_post_meta( $post_id, '_fmp_vaccine_name', $vaccine_name );
		update_post_meta( $post_id, '_fmp_date_given', $date_given );
		update_post_meta( $post_id, '_fmp_next_due_date', $next_due );
		update_post_meta( $post_id, '_fmp_vaccination_location', $location );
		update_post_meta( $post_id, '_fmp_notes', $notes );
	}

	/**
	 * Check if a string is a valid Y-m-d date.
	 *
	 * @param string $date Date string.
	 * @return bool
	 */
	public static function is_valid_date( $date ) {
		if ( $date === '' || ! is_string( $date ) ) {
			return false;
		}
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}
		$ts = strtotime( $date );
		return $ts !== false;
	}

	/**
	 * Get vaccination status from next due date: overdue, due_soon, or ok.
	 *
	 * @param string $next_due_date Next due date (Y-m-d).
	 * @return string 'overdue'|'due_soon'|'ok'
	 */
	public static function get_vaccination_status( $next_due_date ) {
		if ( ! $next_due_date || ! self::is_valid_date( $next_due_date ) ) {
			return 'ok';
		}
		$today = gmdate( 'Y-m-d' );
		$due_ts = strtotime( $next_due_date );
		$today_ts = strtotime( $today );
		if ( $due_ts < $today_ts ) {
			return 'overdue';
		}
		$days = (int) FMP_Settings::get( FMP_Settings::KEY_DUE_SOON_DAYS );
		if ( $days < 1 ) {
			$days = (int) FMP_Settings::get( FMP_Settings::KEY_VACCINATION_DAYS );
		}
		$days = $days >= 1 ? $days : FMP_Settings::DEFAULT_DUE_SOON_DAYS;
		$threshold = strtotime( '+' . $days . ' days', $today_ts );
		if ( $due_ts <= $threshold ) {
			return 'due_soon';
		}
		return 'ok';
	}

	/**
	 * Show validation error notice after failed save.
	 */
	public function show_validation_error() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || $screen->post_type !== 'fmp_vaccination' || $screen->base !== 'post' ) {
			return;
		}
		global $post;
		if ( ! $post || $post->post_type !== 'fmp_vaccination' ) {
			return;
		}
		$key = self::TRANSIENT_VALIDATION_ERROR . '_' . $post->ID;
		$message = get_transient( $key );
		if ( $message ) {
			delete_transient( $key );
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
		}
	}

	/**
	 * Enqueue script for searchable animal dropdown on vaccination edit screen.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_animal_search_script( $hook_suffix ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || $screen->post_type !== 'fmp_vaccination' ) {
			return;
		}
		$script = "jQuery(function($){
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
		wp_add_inline_script( 'jquery', $script, 'after' );
	}

	/**
	 * Set list table columns for fmp_vaccination.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function set_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			if ( $key === 'title' ) {
				$new['title']         = $label;
				$new['fmp_animal']    = __( 'Animal', 'farm-management' );
				$new['fmp_vaccine']   = __( 'Vaccine', 'farm-management' );
				$new['fmp_date_given'] = __( 'Date Given', 'farm-management' );
				$new['fmp_next_due']  = __( 'Next Due', 'farm-management' );
				$new['fmp_status']    = __( 'Status', 'farm-management' );
				$new['fmp_location'] = __( 'Location', 'farm-management' );
			} elseif ( $key !== 'date' ) {
				$new[ $key ] = $label;
			}
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
			case 'fmp_animal':
				$animal_id = (int) get_post_meta( $post_id, '_fmp_animal_id', true );
				if ( $animal_id ) {
					$title = get_the_title( $animal_id );
					if ( get_post_status( $animal_id ) === 'publish' ) {
						echo '<a href="' . esc_url( get_edit_post_link( $animal_id ) ) . '">' . esc_html( $title ) . '</a>';
					} else {
						echo esc_html( $title ? $title : '—' );
					}
				} else {
					echo '—';
				}
				break;
			case 'fmp_vaccine':
				echo esc_html( get_post_meta( $post_id, '_fmp_vaccine_name', true ) ?: '—' );
				break;
			case 'fmp_date_given':
				$date_given = get_post_meta( $post_id, '_fmp_date_given', true );
				echo $date_given ? esc_html( $date_given ) : '—';
				break;
			case 'fmp_next_due':
				$next_due = get_post_meta( $post_id, '_fmp_next_due_date', true );
				echo $next_due ? esc_html( $next_due ) : '—';
				break;
			case 'fmp_location':
				echo esc_html( get_post_meta( $post_id, '_fmp_vaccination_location', true ) ?: '—' );
				break;
			case 'fmp_status':
				$next_due = get_post_meta( $post_id, '_fmp_next_due_date', true );
				$status   = self::get_vaccination_status( $next_due );
				if ( $status === 'overdue' ) {
					echo '<span style="color: #b32d2e; font-weight: 600;">' . esc_html__( 'Overdue', 'farm-management' ) . '</span>';
				} elseif ( $status === 'due_soon' ) {
					echo '<span style="color: #d63638;">' . esc_html__( 'Due Soon', 'farm-management' ) . '</span>';
				} else {
					echo esc_html__( 'OK', 'farm-management' );
				}
				break;
		}
	}
}
