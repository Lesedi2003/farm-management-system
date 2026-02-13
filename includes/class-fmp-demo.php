<?php
/**
 * Guided Demo Mode: create and delete sample data.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Demo
 */
class FMP_Demo {

	const SEEDED_META_KEY = '_fmp_seeded';
	const CREATE_NONCE    = 'fmp_demo_create_sample';
	const DELETE_NONCE    = 'fmp_demo_delete_sample';

	/**
	 * Constructor. Hooks into WordPress.
	 */
	public function __construct() {
		add_action( 'admin_post_fmp_demo_create_sample', array( $this, 'handle_create_sample' ), 10 );
		add_action( 'admin_post_fmp_demo_delete_sample', array( $this, 'handle_delete_sample' ), 10 );
	}

	/**
	 * Check if demo mode is enabled.
	 *
	 * @return bool
	 */
	public static function is_demo_mode() {
		return (int) FMP_Settings::get( FMP_Settings::KEY_DEMO_MODE ) === 1;
	}

	/**
	 * Create sample data. Nonce + manage_options. Seeds with _fmp_seeded=1.
	 */
	public function handle_create_sample() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), self::CREATE_NONCE ) ) {
			wp_die( esc_html__( 'Security check failed.', 'farm-management' ), 403 );
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_SETTINGS ) ) {
			wp_die( esc_html__( 'You do not have permission to create sample data.', 'farm-management' ), 403 );
		}

		$today = gmdate( 'Y-m-d' );
		$user_id = get_current_user_id();

		// Animals (need IDs for vaccinations/tasks)
		$animals = array(
			array( 'tag' => 'DEMO-001', 'species' => 'Cattle', 'breed' => 'Angus', 'sex' => 'female', 'status' => 'alive', 'weight' => 450 ),
			array( 'tag' => 'DEMO-002', 'species' => 'Cattle', 'breed' => 'Hereford', 'sex' => 'male', 'status' => 'alive', 'weight' => 520 ),
			array( 'tag' => 'DEMO-003', 'species' => 'Sheep', 'breed' => 'Merino', 'sex' => 'female', 'status' => 'alive', 'weight' => 55 ),
		);
		$animal_ids = array();
		foreach ( $animals as $a ) {
			$id = wp_insert_post( array(
				'post_title'  => $a['tag'],
				'post_type'   => 'fmp_animal',
				'post_status' => 'publish',
				'post_author' => $user_id,
			) );
			if ( $id && ! is_wp_error( $id ) ) {
				update_post_meta( $id, '_fmp_tag', $a['tag'] );
				update_post_meta( $id, '_fmp_species', $a['species'] );
				update_post_meta( $id, '_fmp_breed', $a['breed'] );
				update_post_meta( $id, '_fmp_sex', $a['sex'] );
				update_post_meta( $id, '_fmp_status', $a['status'] );
				update_post_meta( $id, '_fmp_weight', (string) $a['weight'] );
				update_post_meta( $id, self::SEEDED_META_KEY, 1 );
				$animal_ids[] = $id;
			}
		}

		// Crops
		$crops = array(
			array( 'name' => 'Demo Maize North', 'type' => 'grain', 'location' => 'North Field', 'status' => 'planted', 'planting' => gmdate( 'Y-m-d', strtotime( '-30 days' ) ), 'harvest' => gmdate( 'Y-m-d', strtotime( '+90 days' ) ) ),
			array( 'name' => 'Demo Wheat East', 'type' => 'grain', 'location' => 'East Field', 'status' => 'planned', 'planting' => gmdate( 'Y-m-d', strtotime( '+14 days' ) ), 'harvest' => gmdate( 'Y-m-d', strtotime( '+120 days' ) ) ),
		);
		foreach ( $crops as $c ) {
			$id = wp_insert_post( array(
				'post_title'  => $c['name'],
				'post_type'   => 'fmp_crop',
				'post_status' => 'publish',
				'post_author' => $user_id,
			) );
			if ( $id && ! is_wp_error( $id ) ) {
				update_post_meta( $id, '_fmp_crop_name', $c['name'] );
				update_post_meta( $id, '_fmp_crop_type', $c['type'] );
				update_post_meta( $id, '_fmp_field_location', $c['location'] );
				update_post_meta( $id, '_fmp_crop_status', $c['status'] );
				update_post_meta( $id, '_fmp_planting_date', $c['planting'] );
				update_post_meta( $id, '_fmp_expected_harvest_date', $c['harvest'] );
				update_post_meta( $id, self::SEEDED_META_KEY, 1 );
			}
		}

		// Tasks: some due soon, one overdue-style (past due), one done
		$tasks = array(
			array( 'title' => 'Demo: Vaccinate North herd', 'due' => gmdate( 'Y-m-d', strtotime( '+3 days' ) ), 'status' => 'todo', 'priority' => 'high' ),
			array( 'title' => 'Demo: Order feed supplies', 'due' => gmdate( 'Y-m-d', strtotime( '+5 days' ) ), 'status' => 'doing', 'priority' => 'medium' ),
			array( 'title' => 'Demo: Fence repair', 'due' => gmdate( 'Y-m-d', strtotime( '-2 days' ) ), 'status' => 'todo', 'priority' => 'high' ),
			array( 'title' => 'Demo: Completed check', 'due' => gmdate( 'Y-m-d', strtotime( '-7 days' ) ), 'status' => 'done', 'priority' => 'low' ),
		);
		foreach ( $tasks as $t ) {
			$id = wp_insert_post( array(
				'post_title'  => $t['title'],
				'post_type'   => 'fmp_task',
				'post_status' => 'publish',
				'post_author' => $user_id,
			) );
			if ( $id && ! is_wp_error( $id ) ) {
				update_post_meta( $id, '_fmp_due_date', $t['due'] );
				update_post_meta( $id, '_fmp_status', $t['status'] );
				update_post_meta( $id, '_fmp_priority', $t['priority'] );
				update_post_meta( $id, self::SEEDED_META_KEY, 1 );
			}
		}

		// Inventory: some low stock
		$items = array(
			array( 'name' => 'Demo Cattle Feed', 'category' => 'feed', 'qty' => 50, 'unit' => 'kg', 'reorder' => 100 ),
			array( 'name' => 'Demo Vaccine A', 'category' => 'medicine', 'qty' => 5, 'unit' => 'pcs', 'reorder' => 10 ),
			array( 'name' => 'Demo Fence Wire', 'category' => 'equipment', 'qty' => 2, 'unit' => 'rolls', 'reorder' => 5 ),
		);
		foreach ( $items as $item ) {
			$id = wp_insert_post( array(
				'post_title'  => $item['name'],
				'post_type'   => 'fmp_inventory_item',
				'post_status' => 'publish',
				'post_author' => $user_id,
			) );
			if ( $id && ! is_wp_error( $id ) ) {
				$qty = $item['qty'];
				$reorder = $item['reorder'];
				$low = ( $reorder > 0 && $qty <= $reorder ) ? 1 : 0;
				update_post_meta( $id, '_fmp_item_name', $item['name'] );
				update_post_meta( $id, '_fmp_category', $item['category'] );
				update_post_meta( $id, '_fmp_quantity', (string) $qty );
				update_post_meta( $id, '_fmp_unit', $item['unit'] );
				update_post_meta( $id, '_fmp_reorder_level', (string) $reorder );
				update_post_meta( $id, '_fmp_is_low_stock', $low );
				update_post_meta( $id, self::SEEDED_META_KEY, 1 );
			}
		}

		// Vaccinations: overdue + due soon (need animal IDs)
		if ( ! empty( $animal_ids ) ) {
			$vaccs = array(
				array( 'animal_id' => $animal_ids[0], 'vaccine' => 'Clostridial 7-in-1', 'date_given' => gmdate( 'Y-m-d', strtotime( '-60 days' ) ), 'next_due' => gmdate( 'Y-m-d', strtotime( '-5 days' ) ), 'location' => 'Main shed' ),
				array( 'animal_id' => $animal_ids[1], 'vaccine' => 'Clostridial 7-in-1', 'date_given' => gmdate( 'Y-m-d', strtotime( '-50 days' ) ), 'next_due' => gmdate( 'Y-m-d', strtotime( '+7 days' ) ), 'location' => 'North pen' ),
				array( 'animal_id' => $animal_ids[2], 'vaccine' => 'Orf vaccine', 'date_given' => gmdate( 'Y-m-d', strtotime( '-20 days' ) ), 'next_due' => gmdate( 'Y-m-d', strtotime( '+12 days' ) ), 'location' => 'Sheep yard' ),
			);
			foreach ( $vaccs as $v ) {
				$id = wp_insert_post( array(
					'post_title'  => $v['vaccine'] . ' (Demo)',
					'post_type'   => 'fmp_vaccination',
					'post_status' => 'publish',
					'post_author' => $user_id,
				) );
				if ( $id && ! is_wp_error( $id ) ) {
					update_post_meta( $id, '_fmp_animal_id', $v['animal_id'] );
					update_post_meta( $id, '_fmp_vaccine_name', $v['vaccine'] );
					update_post_meta( $id, '_fmp_date_given', $v['date_given'] );
					update_post_meta( $id, '_fmp_next_due_date', $v['next_due'] );
					update_post_meta( $id, '_fmp_vaccination_location', $v['location'] );
					update_post_meta( $id, self::SEEDED_META_KEY, 1 );
				}
			}
		}

		// Expenses this month (ZAR)
		$expenses = array(
			array( 'amount' => 4500, 'category' => 'feed', 'date' => gmdate( 'Y-m-d', strtotime( '-5 days' ) ), 'vendor' => 'Demo Feed Co' ),
			array( 'amount' => 1200, 'category' => 'vet', 'date' => gmdate( 'Y-m-d', strtotime( '-10 days' ) ), 'vendor' => 'Demo Vet' ),
			array( 'amount' => 800, 'category' => 'fuel', 'date' => $today, 'vendor' => 'Demo Fuel' ),
		);
		foreach ( $expenses as $e ) {
			$id = wp_insert_post( array(
				'post_title'  => 'Demo - R ' . number_format_i18n( $e['amount'], 2 ),
				'post_type'   => 'fmp_expense',
				'post_status' => 'publish',
				'post_author' => $user_id,
			) );
			if ( $id && ! is_wp_error( $id ) ) {
				update_post_meta( $id, '_fmp_amount', (string) $e['amount'] );
				update_post_meta( $id, '_fmp_category', $e['category'] );
				update_post_meta( $id, '_fmp_date', $e['date'] );
				update_post_meta( $id, '_fmp_vendor', $e['vendor'] );
				update_post_meta( $id, self::SEEDED_META_KEY, 1 );
			}
		}

		$redirect = add_query_arg( array( 'page' => 'farm_management', 'fmp_demo' => 'created' ), admin_url( 'admin.php' ) );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Delete all posts with _fmp_seeded=1. Nonce + manage_options.
	 */
	public function handle_delete_sample() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), self::DELETE_NONCE ) ) {
			wp_die( esc_html__( 'Security check failed.', 'farm-management' ), 403 );
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_SETTINGS ) ) {
			wp_die( esc_html__( 'You do not have permission to delete sample data.', 'farm-management' ), 403 );
		}

		$types = array( 'fmp_animal', 'fmp_crop', 'fmp_task', 'fmp_inventory_item', 'fmp_vaccination', 'fmp_expense' );
		foreach ( $types as $post_type ) {
			$posts = get_posts( array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => self::SEEDED_META_KEY,
						'value' => '1',
					),
				),
			) );
			foreach ( $posts as $pid ) {
				wp_delete_post( $pid, true );
			}
		}

		$redirect = add_query_arg( array( 'page' => 'farm_management', 'fmp_demo' => 'deleted' ), admin_url( 'admin.php' ) );
		wp_safe_redirect( $redirect );
		exit;
	}
}
