<?php
/**
 * Loader: hooks and admin asset enqueue.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Loader
 */
class FMP_Loader {

	/**
	 * Enqueue admin CSS and JS on Farm Management screens and fmp_animal edit screens.
	 */
	public static function enqueue_admin_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return;
		}

		$is_fmp_page    = ( strpos( $screen->id, 'farm-management' ) !== false || strpos( $screen->id, 'farm_management' ) !== false );
		$is_animal      = $screen->post_type === 'fmp_animal';
		$is_vaccination = $screen->post_type === 'fmp_vaccination';
		$is_crop        = $screen->post_type === 'fmp_crop';
		$is_task         = $screen->post_type === 'fmp_task';
		$is_inventory    = $screen->post_type === 'fmp_inventory_item';
		$is_expense      = $screen->post_type === 'fmp_expense';

		if ( ! $is_fmp_page && ! $is_animal && ! $is_vaccination && ! $is_crop && ! $is_task && ! $is_inventory && ! $is_expense ) {
			return;
		}

		// Enqueue media library on Animals add/edit (custom page) so "Select image" works.
		if ( $is_fmp_page && strpos( $screen->id, 'fmp-animals' ) !== false ) {
			wp_enqueue_media();
		}

		wp_enqueue_style(
			'farm-management-admin',
			FMP_PLUGIN_URL . 'assets/css/farm-management-admin.css',
			array(),
			FMP_VERSION
		);

		wp_enqueue_script(
			'farm-management-admin',
			FMP_PLUGIN_URL . 'assets/js/farm-management-admin.js',
			array( 'jquery' ),
			FMP_VERSION,
			true
		);
	}
}
