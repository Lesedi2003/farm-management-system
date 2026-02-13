<?php
/**
 * Dashboard data: stats and tables.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Dashboard
 */
class FMP_Dashboard {

	/** Post types used for dashboard stat counts. */
	const STAT_POST_TYPES = array( 'fmp_animal', 'fmp_task', 'fmp_inventory_item', 'fmp_expense', 'fmp_vaccination' );

	/**
	 * Get publish and draft counts for a post type using wp_count_posts.
	 *
	 * @param string $post_type Post type name.
	 * @return array{ 'publish' => int, 'draft' => int } Counts; 0 if post type does not exist.
	 */
	public static function get_post_type_counts( $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			return array( 'publish' => 0, 'draft' => 0 );
		}
		$counts = wp_count_posts( $post_type );
		$publish = isset( $counts->publish ) ? (int) $counts->publish : 0;
		$draft   = isset( $counts->draft ) ? (int) $counts->draft : 0;
		return array( 'publish' => $publish, 'draft' => $draft );
	}

	/**
	 * Count published animals (uses wp_count_posts).
	 *
	 * @return int
	 */
	public static function get_animals_count() {
		$c = self::get_post_type_counts( 'fmp_animal' );
		return $c['publish'];
	}

	/**
	 * Count published tasks (uses wp_count_posts).
	 *
	 * @return int
	 */
	public static function get_tasks_count() {
		$c = self::get_post_type_counts( 'fmp_task' );
		return $c['publish'];
	}

	/**
	 * Count published inventory items (uses wp_count_posts).
	 *
	 * @return int
	 */
	public static function get_inventory_count() {
		$c = self::get_post_type_counts( 'fmp_inventory_item' );
		return $c['publish'];
	}

	/**
	 * Count inventory items where quantity <= reorder_level.
	 *
	 * @return int
	 */
	public static function get_inventory_low_count() {
		if ( ! post_type_exists( 'fmp_inventory_item' ) ) {
			return 0;
		}
		$q = new WP_Query( array(
			'post_type'      => 'fmp_inventory_item',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		) );
		$count = 0;
		foreach ( $q->posts as $post ) {
			$qty    = (int) get_post_meta( $post->ID, '_fmp_quantity', true );
			$reorder = (int) get_post_meta( $post->ID, '_fmp_reorder_level', true );
			if ( $reorder !== 0 && $qty <= $reorder ) {
				$count++;
			} elseif ( $reorder === 0 && $qty <= 0 ) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Count published expenses (uses wp_count_posts).
	 *
	 * @return int
	 */
	public static function get_expenses_count() {
		$c = self::get_post_type_counts( 'fmp_expense' );
		return $c['publish'];
	}

	/**
	 * Count published vaccinations (uses wp_count_posts).
	 *
	 * @return int
	 */
	public static function get_vaccinations_count() {
		$c = self::get_post_type_counts( 'fmp_vaccination' );
		return $c['publish'];
	}

	/**
	 * Sum expense amounts for the current month.
	 *
	 * @return float
	 */
	public static function get_expenses_this_month() {
		if ( ! post_type_exists( 'fmp_expense' ) ) {
			return 0.0;
		}
		$start = gmdate( 'Y-m-01' );
		$end   = gmdate( 'Y-m-t' );
		$q = new WP_Query( array(
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
		$total = 0.0;
		foreach ( $q->posts as $post ) {
			$total += (float) get_post_meta( $post->ID, '_fmp_amount', true );
		}
		return $total;
	}

	/**
	 * Overdue vaccinations (next_due_date < today). Max 8 for dashboard widget.
	 *
	 * @return WP_Post[]
	 */
	public static function get_overdue_vaccinations() {
		$today = gmdate( 'Y-m-d' );
		$q = new WP_Query( array(
			'post_type'      => 'fmp_vaccination',
			'post_status'    => 'publish',
			'posts_per_page' => 8,
			'orderby'        => 'meta_value',
			'meta_key'       => '_fmp_next_due_date',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_fmp_next_due_date',
					'value'   => $today,
					'compare' => '<',
					'type'    => 'DATE',
				),
			),
		) );
		return $q->posts;
	}

	/**
	 * Vaccinations with next_due_date within Due Soon Days (today through today+N). Max 8 for dashboard widget.
	 *
	 * @return WP_Post[]
	 */
	public static function get_vaccinations_due_soon() {
		$days  = (int) FMP_Settings::get( FMP_Settings::KEY_DUE_SOON_DAYS );
		if ( $days < 1 ) {
			$days = (int) FMP_Settings::get( FMP_Settings::KEY_VACCINATION_DAYS );
		}
		$days  = $days >= 1 ? $days : FMP_Settings::DEFAULT_DUE_SOON_DAYS;
		$today = gmdate( 'Y-m-d' );
		$end   = gmdate( 'Y-m-d', strtotime( '+' . $days . ' days' ) );
		$q = new WP_Query( array(
			'post_type'      => 'fmp_vaccination',
			'post_status'    => 'publish',
			'posts_per_page' => 8,
			'orderby'        => 'meta_value',
			'meta_key'       => '_fmp_next_due_date',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_fmp_next_due_date',
					'value'   => array( $today, $end ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		) );
		return $q->posts;
	}

	/**
	 * Vaccinations with next_due_date within Due Soon Days (setting). Used by reports (more items).
	 *
	 * @return WP_Post[]
	 */
	public static function get_upcoming_vaccinations() {
		$days  = (int) FMP_Settings::get( FMP_Settings::KEY_DUE_SOON_DAYS );
		if ( $days < 1 ) {
			$days = (int) FMP_Settings::get( FMP_Settings::KEY_VACCINATION_DAYS );
		}
		$days  = $days >= 1 ? $days : FMP_Settings::DEFAULT_DUE_SOON_DAYS;
		$today = gmdate( 'Y-m-d' );
		$end   = gmdate( 'Y-m-d', strtotime( '+' . $days . ' days' ) );
		$q = new WP_Query( array(
			'post_type'      => 'fmp_vaccination',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'meta_value',
			'meta_key'       => '_fmp_next_due_date',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_fmp_next_due_date',
					'value'   => array( $today, $end ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		) );
		return $q->posts;
	}

	/**
	 * Tasks with due_date in the next 7 days (including today), status != done. Max 8 for dashboard widget.
	 *
	 * @return WP_Post[]
	 */
	public static function get_tasks_due_soon() {
		if ( ! post_type_exists( 'fmp_task' ) ) {
			return array();
		}
		$today = gmdate( 'Y-m-d' );
		$end   = gmdate( 'Y-m-d', strtotime( '+7 days' ) );
		$q = new WP_Query( array(
			'post_type'      => 'fmp_task',
			'post_status'    => 'publish',
			'posts_per_page' => 8,
			'orderby'        => 'meta_value',
			'meta_key'       => '_fmp_due_date',
			'order'          => 'ASC',
			'meta_query'     => array(
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
			),
		) );
		return $q->posts;
	}

	/**
	 * Inventory items where quantity <= reorder_level (low stock). Max 8 for dashboard widget.
	 *
	 * @return WP_Post[]
	 */
	public static function get_low_stock_items() {
		if ( ! post_type_exists( 'fmp_inventory_item' ) ) {
			return array();
		}
		$q = new WP_Query( array(
			'post_type'      => 'fmp_inventory_item',
			'post_status'    => 'publish',
			'posts_per_page' => 8,
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_fmp_is_low_stock',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'   => '_fmp_is_low_stock',
					'value' => '1',
				),
			),
		) );
		return $q->posts;
	}
}
