<?php
/**
 * Reports page data and CSV export handlers.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Reports
 */
class FMP_Reports {

	const EXPORT_NONCE_ANIMALS   = 'fmp_export_report_animals_species';
	const EXPORT_NONCE_VACC      = 'fmp_export_report_vaccinations_due';
	const EXPORT_NONCE_EXPENSES  = 'fmp_export_report_expenses_category';

	/**
	 * Constructor. Hooks into WordPress.
	 */
	public function __construct() {
		add_action( 'admin_post_fmp_export_report_animals_species', array( $this, 'export_csv_animals_species' ), 10 );
		add_action( 'admin_post_fmp_export_report_vaccinations_due', array( $this, 'export_csv_vaccinations_due' ), 10 );
		add_action( 'admin_post_fmp_export_report_expenses_category', array( $this, 'export_csv_expenses_category' ), 10 );
	}

	/**
	 * Report A: Vaccinations due in next N days (including overdue). Ordered by next due date.
	 *
	 * @return WP_Post[]
	 */
	public static function get_vaccinations_due_including_overdue() {
		$days  = (int) FMP_Settings::get( FMP_Settings::KEY_DUE_SOON_DAYS );
		if ( $days < 1 ) {
			$days = (int) FMP_Settings::get( FMP_Settings::KEY_VACCINATION_DAYS );
		}
		$days  = $days >= 1 ? $days : FMP_Settings::DEFAULT_DUE_SOON_DAYS;
		$end   = gmdate( 'Y-m-d', strtotime( '+' . $days . ' days' ) );
		$q = new WP_Query( array(
			'post_type'      => 'fmp_vaccination',
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'orderby'        => 'meta_value',
			'meta_key'       => '_fmp_next_due_date',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_fmp_next_due_date',
					'value'   => $end,
					'compare' => '<=',
					'type'    => 'DATE',
				),
			),
		) );
		return $q->posts;
	}

	/**
	 * Report B: Animals grouped by species and status (counts).
	 *
	 * @return array[] Array of [ 'species' => string, 'status' => string, 'count' => int ].
	 */
	public static function get_animals_by_species_and_status() {
		$posts = get_posts( array(
			'post_type'      => 'fmp_animal',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		) );
		$grid = array();
		$status_labels = array(
			'alive' => __( 'Alive', 'farm-management' ),
			'sold'  => __( 'Sold', 'farm-management' ),
			'dead'  => __( 'Dead', 'farm-management' ),
		);
		foreach ( $posts as $post ) {
			$species = get_post_meta( $post->ID, '_fmp_species', true );
			$species = $species !== '' ? $species : __( '(Unspecified)', 'farm-management' );
			$status  = get_post_meta( $post->ID, '_fmp_status', true );
			$status  = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : ( $status ?: __( '(Unspecified)', 'farm-management' ) );
			if ( ! isset( $grid[ $species ][ $status ] ) ) {
				$grid[ $species ][ $status ] = 0;
			}
			$grid[ $species ][ $status ]++;
		}
		ksort( $grid );
		$rows = array();
		foreach ( $grid as $species => $by_status ) {
			foreach ( $by_status as $status => $count ) {
				$rows[] = array( 'species' => $species, 'status' => $status, 'count' => $count );
			}
		}
		return $rows;
	}

	/**
	 * Report B: Animals by species only (for backward compatibility / simple view).
	 *
	 * @return array[] Array of [ 'species' => string, 'count' => int ].
	 */
	public static function get_animals_by_species() {
		$posts = get_posts( array(
			'post_type'      => 'fmp_animal',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		) );
		$by_species = array();
		foreach ( $posts as $post ) {
			$species = get_post_meta( $post->ID, '_fmp_species', true );
			$species = $species !== '' ? $species : __( '(Unspecified)', 'farm-management' );
			if ( ! isset( $by_species[ $species ] ) ) {
				$by_species[ $species ] = 0;
			}
			$by_species[ $species ]++;
		}
		ksort( $by_species );
		$rows = array();
		foreach ( $by_species as $species => $count ) {
			$rows[] = array( 'species' => $species, 'count' => $count );
		}
		return $rows;
	}

	/**
	 * Report C: Expenses by category for a given month.
	 *
	 * @param int $year  Year (e.g. 2025).
	 * @param int $month Month 1–12.
	 * @return array[] Array of [ 'category' => string, 'total' => float ].
	 */
	public static function get_expenses_by_category_for_month( $year, $month ) {
		if ( ! post_type_exists( 'fmp_expense' ) ) {
			return array();
		}
		$year  = max( 2000, min( 2100, (int) $year ) );
		$month = max( 1, min( 12, (int) $month ) );
		$start = sprintf( '%04d-%02d-01', $year, $month );
		$end   = gmdate( 'Y-m-t', strtotime( $start ) );
		$posts = get_posts( array(
			'post_type'      => 'fmp_expense',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
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
		$by_category = array();
		$labels = array(
			'feed'    => __( 'Feed', 'farm-management' ),
			'vet'     => __( 'Vet', 'farm-management' ),
			'fuel'    => __( 'Fuel', 'farm-management' ),
			'labour'  => __( 'Labour', 'farm-management' ),
			'repairs' => __( 'Repairs', 'farm-management' ),
			'other'   => __( 'Other', 'farm-management' ),
		);
		foreach ( $posts as $post ) {
			$cat   = get_post_meta( $post->ID, '_fmp_category', true );
			$label = isset( $labels[ $cat ] ) ? $labels[ $cat ] : ( $cat ?: __( 'Other', 'farm-management' ) );
			if ( ! isset( $by_category[ $label ] ) ) {
				$by_category[ $label ] = 0.0;
			}
			$by_category[ $label ] += (float) get_post_meta( $post->ID, '_fmp_amount', true );
		}
		$rows = array();
		foreach ( $by_category as $category => $total ) {
			$rows[] = array( 'category' => $category, 'total' => $total );
		}
		return $rows;
	}

	/**
	 * Report 3 (legacy): Expenses this month by category.
	 *
	 * @return array[] Array of [ 'category' => string, 'total' => float ].
	 */
	public static function get_expenses_by_category_this_month() {
		return self::get_expenses_by_category_for_month( (int) gmdate( 'Y' ), (int) gmdate( 'n' ) );
	}

	/**
	 * Export CSV: Animals by species + status. Nonce + capability check, then output CSV.
	 */
	public function export_csv_animals_species() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), self::EXPORT_NONCE_ANIMALS ) ) {
			wp_die( esc_html__( 'Security check failed.', 'farm-management' ), 403 );
		}
		if ( ! current_user_can( FMP_Capabilities::EXPORT_REPORTS ) ) {
			wp_die( esc_html__( 'You do not have permission to export reports.', 'farm-management' ), 403 );
		}
		$rows = self::get_animals_by_species_and_status();
		$filename = 'farm-report-animals-by-species-status-' . gmdate( 'Y-m-d' ) . '.csv';
		$this->send_csv_headers( $filename );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( __( 'Species', 'farm-management' ), __( 'Status', 'farm-management' ), __( 'Count', 'farm-management' ) ) );
		foreach ( $rows as $row ) {
			fputcsv( $out, array( $row['species'], $row['status'], $row['count'] ) );
		}
		fclose( $out );
		exit;
	}

	/**
	 * Export CSV: Vaccinations due in next N days (including overdue). Columns: Animal Tag/Name, Vaccine, Next Due, Status, Location.
	 */
	public function export_csv_vaccinations_due() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), self::EXPORT_NONCE_VACC ) ) {
			wp_die( esc_html__( 'Security check failed.', 'farm-management' ), 403 );
		}
		if ( ! current_user_can( FMP_Capabilities::EXPORT_REPORTS ) ) {
			wp_die( esc_html__( 'You do not have permission to export reports.', 'farm-management' ), 403 );
		}
		$posts = self::get_vaccinations_due_including_overdue();
		$filename = 'farm-report-vaccinations-due-' . gmdate( 'Y-m-d' ) . '.csv';
		$this->send_csv_headers( $filename );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array(
			__( 'Animal Tag/Name', 'farm-management' ),
			__( 'Vaccine', 'farm-management' ),
			__( 'Next Due', 'farm-management' ),
			__( 'Status', 'farm-management' ),
			__( 'Location', 'farm-management' ),
		) );
		foreach ( $posts as $post ) {
			$animal_id   = (int) get_post_meta( $post->ID, '_fmp_animal_id', true );
			$animal_name = $animal_id ? get_the_title( $animal_id ) : '—';
			$animal_tag  = $animal_id ? get_post_meta( $animal_id, '_fmp_tag', true ) : '';
			$animal_label = ( $animal_tag !== '' ? $animal_tag : $animal_name );
			if ( $animal_label === '' ) {
				$animal_label = '—';
			}
			$vaccine  = get_post_meta( $post->ID, '_fmp_vaccine_name', true );
			$next_due = get_post_meta( $post->ID, '_fmp_next_due_date', true );
			$status   = FMP_Vaccinations::get_vaccination_status( $next_due );
			$status_label = ( $status === 'overdue' ) ? __( 'Overdue', 'farm-management' ) : ( ( $status === 'due_soon' ) ? __( 'Due Soon', 'farm-management' ) : __( 'OK', 'farm-management' ) );
			$location = get_post_meta( $post->ID, '_fmp_vaccination_location', true );
			if ( $location === '' ) {
				$location = '—';
			}
			fputcsv( $out, array( $animal_label, $vaccine ?: '—', $next_due ?: '—', $status_label, $location ) );
		}
		fclose( $out );
		exit;
	}

	/**
	 * Export CSV: Expenses by category for selected month. Nonce + capability check. Month from request (year, month) or current month.
	 */
	public function export_csv_expenses_category() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), self::EXPORT_NONCE_EXPENSES ) ) {
			wp_die( esc_html__( 'Security check failed.', 'farm-management' ), 403 );
		}
		if ( ! current_user_can( FMP_Capabilities::EXPORT_REPORTS ) ) {
			wp_die( esc_html__( 'You do not have permission to export reports.', 'farm-management' ), 403 );
		}
		$year  = isset( $_REQUEST['year'] ) ? absint( $_REQUEST['year'] ) : (int) gmdate( 'Y' );
		$month = isset( $_REQUEST['month'] ) ? absint( $_REQUEST['month'] ) : (int) gmdate( 'n' );
		$year  = max( 2000, min( 2100, $year ) );
		$month = max( 1, min( 12, $month ) );
		$rows  = self::get_expenses_by_category_for_month( $year, $month );
		$filename = 'farm-report-expenses-by-category-' . sprintf( '%04d-%02d', $year, $month ) . '.csv';
		$this->send_csv_headers( $filename );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( __( 'Category', 'farm-management' ), __( 'Total (ZAR)', 'farm-management' ) ) );
		foreach ( $rows as $row ) {
			fputcsv( $out, array( $row['category'], 'R ' . number_format( $row['total'], 2 ) ) );
		}
		fclose( $out );
		exit;
	}

	/**
	 * Send CSV download headers.
	 *
	 * @param string $filename Suggested filename.
	 */
	private function send_csv_headers( $filename ) {
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
	}
}
