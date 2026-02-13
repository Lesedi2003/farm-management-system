<?php
/**
 * Admin page render classes for Farm Management.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard page.
 */
class FMP_Admin_Dashboard_Page {

	public static function render() {
		include FMP_PLUGIN_DIR . 'includes/views/dashboard.php';
	}
}

/**
 * Animals: includes/admin/pages/class-fmp-admin-animals-page.php
 * Crops: includes/admin/pages/class-fmp-admin-crops-page.php
 * Tasks: includes/admin/pages/class-fmp-admin-tasks-page.php
 * Inventory: includes/admin/pages/class-fmp-admin-inventory-page.php
 * Expenses: includes/admin/pages/class-fmp-admin-expenses-page.php
 * Vaccinations: includes/admin/pages/class-fmp-admin-vaccinations-page.php
 */

/**
 * Reports page.
 */
class FMP_Admin_Reports_Page {

	public static function render() {
		include FMP_PLUGIN_DIR . 'includes/views/reports.php';
	}
}

/**
 * Settings page.
 */
class FMP_Admin_Settings_Page {

	public static function render() {
		FMP_Settings::render_page();
	}
}
