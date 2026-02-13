<?php
/**
 * Expenses CPT: meta box, list columns, month filter, total.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Expenses
 */
class FMP_Expenses {

	const NONCE_ACTION = 'fmp_save_expense';

	/**
	 * Constructor. Hooks into WordPress.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ), 10 );
		add_action( 'save_post_fmp_expense', array( $this, 'save_meta' ), 10, 2 );
		add_filter( 'manage_fmp_expense_posts_columns', array( $this, 'set_columns' ), 10 );
		add_action( 'manage_fmp_expense_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'render_month_filter' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'filter_by_month' ), 10, 1 );
		add_action( 'all_admin_notices', array( $this, 'render_month_total' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_media_script' ), 10 );
	}

	/**
	 * Enqueue media uploader for receipt field on expense edit screen.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_media_script( $hook_suffix ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || $screen->post_type !== 'fmp_expense' || $screen->base !== 'post' ) {
			return;
		}
		wp_enqueue_media();
		wp_add_inline_script( 'jquery', $this->get_receipt_uploader_script() );
	}

	/**
	 * Inline script for receipt upload button.
	 *
	 * @return string
	 */
	private function get_receipt_uploader_script() {
		return "
		(function($){
			$(function(){
				var frame;
				$(document).on('click', '.fmp-receipt-upload', function(e){
					e.preventDefault();
					var btn = $(this);
					var input = btn.siblings('input[name=fmp_receipt_id]');
					if (frame) { frame.open(); return; }
					frame = wp.media({
						title: 'Select or Upload Receipt',
						button: { text: 'Use this file' },
						library: { type: '' },
						multiple: false
					});
					frame.on('select', function(){
						var att = frame.state().get('selection').first().toJSON();
						input.val(att.id);
						btn.siblings('.fmp-receipt-name').text(att.filename || att.url).show();
					});
					frame.open();
				});
				$(document).on('click', '.fmp-receipt-remove', function(e){
					e.preventDefault();
					$(this).siblings('input[name=fmp_receipt_id]').val('');
					$(this).siblings('.fmp-receipt-name').hide().text('');
				});
			});
		})(jQuery);
		";
	}

	/**
	 * Register Expense details meta box.
	 */
	public function register_meta_box() {
		add_meta_box(
			'fmp_expense_details',
			__( 'Expense details', 'farm-management' ),
			array( $this, 'render_meta_box' ),
			'fmp_expense',
			'normal',
			'default'
		);
	}

	/**
	 * Render Expense details meta box.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( self::NONCE_ACTION, 'fmp_expense_nonce' );

		$amount   = get_post_meta( $post->ID, '_fmp_amount', true );
		$date     = get_post_meta( $post->ID, '_fmp_date', true );
		$category = get_post_meta( $post->ID, '_fmp_category', true );
		$vendor   = get_post_meta( $post->ID, '_fmp_vendor', true );
		$receipt_id = (int) get_post_meta( $post->ID, '_fmp_receipt_id', true );
		$notes    = get_post_meta( $post->ID, '_fmp_notes', true );

		$receipt_name = '';
		if ( $receipt_id ) {
			$file = get_attached_file( $receipt_id );
			$receipt_name = $file ? basename( $file ) : __( 'Attachment', 'farm-management' );
		}
		?>
		<div class="fmp-meta-box fmp-expense-details">
			<div class="fmp-meta-row">
				<label for="fmp_amount"><?php esc_html_e( 'Amount (ZAR)', 'farm-management' ); ?></label>
				<input type="number" id="fmp_amount" name="fmp_amount" value="<?php echo esc_attr( $amount ); ?>" min="0" step="0.01" class="small-text" /> <span class="description"><?php esc_html_e( 'South African Rand', 'farm-management' ); ?></span>
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_date"><?php esc_html_e( 'Date', 'farm-management' ); ?></label>
				<input type="date" id="fmp_date" name="fmp_date" value="<?php echo esc_attr( $date ); ?>" />
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_category"><?php esc_html_e( 'Category', 'farm-management' ); ?></label>
				<select id="fmp_category" name="fmp_category">
					<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
					<option value="feed" <?php selected( $category, 'feed' ); ?>><?php esc_html_e( 'Feed', 'farm-management' ); ?></option>
					<option value="vet" <?php selected( $category, 'vet' ); ?>><?php esc_html_e( 'Vet', 'farm-management' ); ?></option>
					<option value="fuel" <?php selected( $category, 'fuel' ); ?>><?php esc_html_e( 'Fuel', 'farm-management' ); ?></option>
					<option value="labour" <?php selected( $category, 'labour' ); ?>><?php esc_html_e( 'Labour', 'farm-management' ); ?></option>
					<option value="repairs" <?php selected( $category, 'repairs' ); ?>><?php esc_html_e( 'Repairs', 'farm-management' ); ?></option>
					<option value="other" <?php selected( $category, 'other' ); ?>><?php esc_html_e( 'Other', 'farm-management' ); ?></option>
				</select>
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_vendor"><?php esc_html_e( 'Vendor', 'farm-management' ); ?></label>
				<input type="text" id="fmp_vendor" name="fmp_vendor" value="<?php echo esc_attr( $vendor ); ?>" class="regular-text" />
			</div>
			<div class="fmp-meta-row">
				<label><?php esc_html_e( 'Receipt', 'farm-management' ); ?></label>
				<input type="hidden" name="fmp_receipt_id" value="<?php echo esc_attr( $receipt_id ); ?>" />
				<button type="button" class="button fmp-receipt-upload"><?php esc_html_e( 'Select / Upload receipt', 'farm-management' ); ?></button>
				<button type="button" class="button fmp-receipt-remove"><?php esc_html_e( 'Remove', 'farm-management' ); ?></button>
				<span class="fmp-receipt-name" <?php echo $receipt_name ? '' : ' style="display:none;"'; ?>><?php echo esc_html( $receipt_name ); ?></span>
			</div>
			<div class="fmp-meta-row fmp-meta-row-full">
				<label for="fmp_notes"><?php esc_html_e( 'Notes', 'farm-management' ); ?></label>
				<textarea id="fmp_notes" name="fmp_notes" rows="4" class="large-text"><?php echo esc_textarea( $notes ); ?></textarea>
			</div>
		</div>
		<?php
	}

	/**
	 * Save expense meta.
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
		if ( ! isset( $_POST['fmp_expense_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_expense_nonce'] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
			return;
		}

		$amount = isset( $_POST['fmp_amount'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_amount'] ) ) : '';
		$date   = isset( $_POST['fmp_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_date'] ) ) : '';
		$category = isset( $_POST['fmp_category'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_category'] ) ) : '';
		$vendor = isset( $_POST['fmp_vendor'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_vendor'] ) ) : '';
		$receipt_id = isset( $_POST['fmp_receipt_id'] ) ? absint( $_POST['fmp_receipt_id'] ) : 0;
		$notes  = isset( $_POST['fmp_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_notes'] ) ) : '';

		update_post_meta( $post_id, '_fmp_amount', $amount );
		update_post_meta( $post_id, '_fmp_date', $date );
		update_post_meta( $post_id, '_fmp_category', $category );
		update_post_meta( $post_id, '_fmp_vendor', $vendor );
		update_post_meta( $post_id, '_fmp_receipt_id', $receipt_id );
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
				$new['fmp_date']     = __( 'Date', 'farm-management' );
				$new['fmp_category'] = __( 'Category', 'farm-management' );
				$new['fmp_amount']   = __( 'Amount (ZAR)', 'farm-management' );
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
			case 'fmp_date':
				echo esc_html( get_post_meta( $post_id, '_fmp_date', true ) ?: '—' );
				break;
			case 'fmp_category':
				$category = get_post_meta( $post_id, '_fmp_category', true );
				$labels = array(
					'feed'    => __( 'Feed', 'farm-management' ),
					'vet'     => __( 'Vet', 'farm-management' ),
					'fuel'    => __( 'Fuel', 'farm-management' ),
					'labour'  => __( 'Labour', 'farm-management' ),
					'repairs' => __( 'Repairs', 'farm-management' ),
					'other'   => __( 'Other', 'farm-management' ),
				);
				echo esc_html( isset( $labels[ $category ] ) ? $labels[ $category ] : ( $category ?: '—' ) );
				break;
			case 'fmp_amount':
				$amount = get_post_meta( $post_id, '_fmp_amount', true );
				echo $amount !== '' ? esc_html( number_format_i18n( (float) $amount, 2 ) ) : '—';
				break;
		}
	}

	/**
	 * Render month/year filter dropdowns above the list table.
	 *
	 * @param string $post_type Post type.
	 */
	public function render_month_filter( $post_type ) {
		if ( $post_type !== 'fmp_expense' ) {
			return;
		}

		$current_month = isset( $_GET['fmp_month'] ) ? absint( $_GET['fmp_month'] ) : (int) gmdate( 'n' );
		$current_year  = isset( $_GET['fmp_year'] ) ? absint( $_GET['fmp_year'] ) : (int) gmdate( 'Y' );

		$years = array();
		for ( $y = (int) gmdate( 'Y' ); $y >= (int) gmdate( 'Y' ) - 5; $y-- ) {
			$years[] = $y;
		}
		?>
		<select name="fmp_month" id="fmp_month">
			<?php for ( $m = 1; $m <= 12; $m++ ) : ?>
				<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $current_month, $m ); ?>>
					<?php echo esc_html( gmdate( 'F', mktime( 0, 0, 0, $m, 1 ) ) ); ?>
				</option>
			<?php endfor; ?>
		</select>
		<select name="fmp_year" id="fmp_year">
			<?php foreach ( $years as $y ) : ?>
				<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $current_year, $y ); ?>><?php echo esc_html( $y ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Filter expenses list by selected month.
	 *
	 * @param WP_Query $query Query object.
	 */
	public function filter_by_month( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || $screen->id !== 'edit-fmp_expense' ) {
			return;
		}

		$month = isset( $_GET['fmp_month'] ) ? absint( $_GET['fmp_month'] ) : 0;
		$year  = isset( $_GET['fmp_year'] ) ? absint( $_GET['fmp_year'] ) : 0;
		if ( ! $month || ! $year ) {
			$month = (int) gmdate( 'n' );
			$year  = (int) gmdate( 'Y' );
		}

		$start = sprintf( '%04d-%02d-01', $year, $month );
		$end   = gmdate( 'Y-m-t', strtotime( $start ) );

		$query->set( 'meta_query', array(
			array(
				'key'     => '_fmp_date',
				'value'   => array( $start, $end ),
				'compare' => 'BETWEEN',
				'type'    => 'DATE',
			),
		) );
		$query->set( 'meta_key', '_fmp_date' );
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'order', 'DESC' );
	}

	/**
	 * Output total for selected month above the list table.
	 */
	public function render_month_total() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || $screen->id !== 'edit-fmp_expense' ) {
			return;
		}

		$month = isset( $_GET['fmp_month'] ) ? absint( $_GET['fmp_month'] ) : (int) gmdate( 'n' );
		$year  = isset( $_GET['fmp_year'] ) ? absint( $_GET['fmp_year'] ) : (int) gmdate( 'Y' );
		$start = sprintf( '%04d-%02d-01', $year, $month );
		$end   = gmdate( 'Y-m-t', strtotime( $start ) );

		$posts = get_posts( array(
			'post_type'      => 'fmp_expense',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'     => '_fmp_date',
					'value'   => array( $start, $end ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		) );

		$total = 0.0;
		foreach ( $posts as $post_id ) {
			$total += (float) get_post_meta( $post_id, '_fmp_amount', true );
		}

		$month_name = gmdate( 'F Y', strtotime( $start ) );
		?>
		<div class="fmp-expenses-total notice notice-info" style="margin: 1rem 0; padding: 0.75rem 1rem;">
			<strong><?php esc_html_e( 'Total for', 'farm-management' ); ?> <?php echo esc_html( $month_name ); ?> (<?php esc_html_e( 'ZAR', 'farm-management' ); ?>):</strong>
			R <?php echo esc_html( number_format_i18n( $total, 2 ) ); ?>
		</div>
		<?php
	}
}
