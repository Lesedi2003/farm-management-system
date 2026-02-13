<?php
/**
 * Admin menu and subpages.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Admin_Menu
 */
class FMP_Admin_Menu {

	/**
	 * Top-level menu slug.
	 *
	 * @var string
	 */
	const MENU_SLUG = 'farm_management';

	/**
	 * Register top-level menu and subpages.
	 * Visibility: fmp_manage_farm for dashboard and records; fmp_manage_settings for Settings only.
	 */
	public static function register() {
		add_menu_page(
			__( 'Farm Management', 'farm-management' ),
			__( 'Farm Management', 'farm-management' ),
			FMP_Capabilities::MANAGE_FARM,
			self::MENU_SLUG,
			array( 'FMP_Admin_Dashboard_Page', 'render' ),
			'dashicons-store',
			30
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Dashboard', 'farm-management' ),
			__( 'Dashboard', 'farm-management' ),
			FMP_Capabilities::MANAGE_FARM,
			self::MENU_SLUG,
			array( 'FMP_Admin_Dashboard_Page', 'render' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Animals', 'farm-management' ),
			__( 'Animals', 'farm-management' ),
			FMP_Capabilities::MANAGE_FARM,
			'fmp-animals',
			array( 'FMP_Admin_Animals_Page', 'render' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Crops', 'farm-management' ),
			__( 'Crops', 'farm-management' ),
			FMP_Capabilities::MANAGE_FARM,
			'fmp-crops',
			array( 'FMP_Admin_Crops_Page', 'render' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Tasks', 'farm-management' ),
			__( 'Tasks', 'farm-management' ),
			FMP_Capabilities::MANAGE_FARM,
			'fmp-tasks',
			array( 'FMP_Admin_Tasks_Page', 'render' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Inventory', 'farm-management' ),
			__( 'Inventory', 'farm-management' ),
			FMP_Capabilities::MANAGE_FARM,
			'fmp-inventory',
			array( 'FMP_Admin_Inventory_Page', 'render' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Expenses', 'farm-management' ),
			__( 'Expenses', 'farm-management' ),
			FMP_Capabilities::MANAGE_FARM,
			'fmp-expenses',
			array( 'FMP_Admin_Expenses_Page', 'render' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Vaccinations', 'farm-management' ),
			__( 'Vaccinations', 'farm-management' ),
			FMP_Capabilities::MANAGE_FARM,
			'fmp-vaccinations',
			array( 'FMP_Admin_Vaccinations_Page', 'render' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Reports', 'farm-management' ),
			__( 'Reports', 'farm-management' ),
			FMP_Capabilities::MANAGE_FARM,
			'fmp-reports',
			array( 'FMP_Admin_Reports_Page', 'render' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Frontend Pages', 'farm-management' ),
			__( 'Frontend Pages', 'farm-management' ),
			FMP_Capabilities::MANAGE_FARM,
			'fmp-frontend',
			array( 'FMP_Admin_Frontend_Page', 'render' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Email / SMTP', 'farm-management' ),
			__( 'Email / SMTP', 'farm-management' ),
			FMP_Capabilities::MANAGE_SETTINGS,
			'fmp-email-smtp',
			array( 'FMP_Admin_Smtp_Page', 'render' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings', 'farm-management' ),
			__( 'Settings', 'farm-management' ),
			FMP_Capabilities::MANAGE_SETTINGS,
			'fmp-settings',
			array( 'FMP_Admin_Settings_Page', 'render' )
		);
	}
}
