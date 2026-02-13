<?php
/**
 * Plugin Name: Farm Management
 * Plugin URI: https://example.com/farm-management
 * Description: Turns WordPress into a simple farm-operations dashboard: animals, crops, tasks, expenses, inventory, health/vet records, and basic reports.
 * Version: 1.0.0
 * Author: Farm Management
 * Author URI: https://example.com
 * Text Domain: farm-management
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FMP_VERSION', '1.0.0' );
define( 'FMP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FMP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FMP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once FMP_PLUGIN_DIR . 'includes/class-fmp-loader.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-post-types.php';
require_once FMP_PLUGIN_DIR . 'includes/admin/class-fmp-admin-pages.php';
require_once FMP_PLUGIN_DIR . 'includes/admin/pages/class-fmp-admin-animals-page.php';
require_once FMP_PLUGIN_DIR . 'includes/admin/pages/class-fmp-admin-crops-page.php';
require_once FMP_PLUGIN_DIR . 'includes/admin/pages/class-fmp-admin-tasks-page.php';
require_once FMP_PLUGIN_DIR . 'includes/admin/pages/class-fmp-admin-inventory-page.php';
require_once FMP_PLUGIN_DIR . 'includes/admin/pages/class-fmp-admin-expenses-page.php';
require_once FMP_PLUGIN_DIR . 'includes/admin/pages/class-fmp-admin-vaccinations-page.php';
require_once FMP_PLUGIN_DIR . 'includes/admin/pages/class-fmp-admin-smtp-page.php';
require_once FMP_PLUGIN_DIR . 'includes/admin/pages/class-fmp-admin-frontend-page.php';
require_once FMP_PLUGIN_DIR . 'includes/admin/class-fmp-admin-menu.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-meta-boxes.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-vaccinations.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-dashboard.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-tasks.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-inventory.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-expenses.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-reports.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-settings.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-demo.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-capabilities.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-reminders.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-smtp.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-shortcodes.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-frontend.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-portal.php';

register_activation_hook( __FILE__, 'fmp_activate' );
register_deactivation_hook( __FILE__, 'fmp_deactivate' );
add_action( 'plugins_loaded', 'fmp_load_textdomain' );
add_action( 'init', array( 'FMP_Post_Types', 'register' ), 10 );
add_action( 'admin_menu', array( 'FMP_Admin_Menu', 'register' ), 10 );
add_action( 'add_meta_boxes', array( 'FMP_Meta_Boxes', 'register' ), 10 );
add_action( 'save_post_fmp_animal', array( 'FMP_Meta_Boxes', 'save_animal_meta' ), 10, 2 );
add_action( 'save_post_fmp_crop', array( 'FMP_Meta_Boxes', 'save_crop_meta' ), 10, 2 );
add_filter( 'manage_fmp_crop_posts_columns', array( 'FMP_Meta_Boxes', 'crop_columns' ), 10 );
add_action( 'manage_fmp_crop_posts_custom_column', array( 'FMP_Meta_Boxes', 'crop_column_content' ), 10, 2 );
add_action( 'admin_enqueue_scripts', array( 'FMP_Loader', 'enqueue_admin_assets' ), 10 );
add_filter( 'map_meta_cap', array( 'FMP_Capabilities', 'map_meta_cap' ), 10, 4 );

FMP_Admin_Animals_Page::init();
FMP_Admin_Crops_Page::init();
FMP_Admin_Tasks_Page::init();
FMP_Admin_Inventory_Page::init();
FMP_Admin_Expenses_Page::init();
FMP_Admin_Vaccinations_Page::init();
FMP_Admin_Smtp_Page::init();
FMP_Admin_Frontend_Page::init();

new FMP_Vaccinations();
new FMP_Tasks();
new FMP_Inventory();
new FMP_Expenses();
new FMP_Reports();
new FMP_Settings();
new FMP_Demo();
new FMP_Reminders();
new FMP_Smtp();
new FMP_Shortcodes();
new FMP_Frontend();
FMP_Portal::init();

/**
 * Create a WordPress page by slug if it doesn't exist. Used for portal pages so nav links work.
 *
 * @param string $slug   Page slug (e.g. 'support').
 * @param string $title  Page title.
 * @param string $content Shortcode or content to insert.
 * @return int|false Post ID if created or already exists, false on failure.
 */
function fmp_ensure_page( $slug, $title, $content = '' ) {
	$slug = sanitize_title( $slug );
	if ( $slug === '' ) {
		return false;
	}
	$page = get_page_by_path( $slug );
	if ( $page ) {
		return (int) $page->ID;
	}
	$post_id = wp_insert_post( array(
		'post_title'   => $title,
		'post_name'   => $slug,
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_author'  => 1,
	) );
	return is_wp_error( $post_id ) ? false : (int) $post_id;
}

/**
 * Flush rewrite rules, add FMP roles/capabilities, and schedule reminder cron on plugin activation.
 */
function fmp_activate() {
	require_once FMP_PLUGIN_DIR . 'includes/class-fmp-post-types.php';
	require_once FMP_PLUGIN_DIR . 'includes/class-fmp-capabilities.php';
	require_once FMP_PLUGIN_DIR . 'includes/class-fmp-settings.php';
	require_once FMP_PLUGIN_DIR . 'includes/class-fmp-reminders.php';
	FMP_Post_Types::register();
	FMP_Vaccinations::register_cpt();
	FMP_Capabilities::add_roles();
	FMP_Reminders::schedule_event();
	// Ensure Support portal page exists so the Support tab link works.
	fmp_ensure_page( 'support', _x( 'Support', 'Portal page title', 'farm-management' ), '[fmp_support]' );
	// Create SaaS portal pages (Farm Portal, Add Animal, etc.) and store IDs.
	FMP_Portal::setup_pages();
	flush_rewrite_rules();
}

/**
 * Clear reminder cron and flush rewrite rules on plugin deactivation.
 */
function fmp_deactivate() {
	require_once FMP_PLUGIN_DIR . 'includes/class-fmp-reminders.php';
	FMP_Reminders::unschedule_event();
	flush_rewrite_rules();
}

/**
 * Load plugin text domain for translations.
 */
function fmp_load_textdomain() {
	load_plugin_textdomain(
		'farm-management',
		false,
		dirname( FMP_PLUGIN_BASENAME ) . '/languages'
	);
}
