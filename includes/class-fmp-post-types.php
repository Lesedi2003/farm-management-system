<?php
/**
 * Custom Post Type registration.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Post_Types
 */
class FMP_Post_Types {

	/**
	 * Register custom post types.
	 */
	public static function register() {
		self::register_animal();
		self::register_crop();
		self::register_task();
		self::register_inventory_item();
		self::register_expense();
	}

	/**
	 * Register fmp_animal post type.
	 */
	private static function register_animal() {
		$labels = array(
			'name'                  => _x( 'Animals', 'Post type general name', 'farm-management' ),
			'singular_name'         => _x( 'Animal', 'Post type singular name', 'farm-management' ),
			'menu_name'             => _x( 'Animals', 'Admin Menu text', 'farm-management' ),
			'add_new'               => __( 'Add New', 'farm-management' ),
			'add_new_item'          => __( 'Add New Animal', 'farm-management' ),
			'new_item'              => __( 'New Animal', 'farm-management' ),
			'edit_item'             => __( 'Edit Animal', 'farm-management' ),
			'view_item'             => __( 'View Animal', 'farm-management' ),
			'all_items'             => __( 'All Animals', 'farm-management' ),
			'search_items'          => __( 'Search Animals', 'farm-management' ),
			'not_found'              => __( 'No animals found.', 'farm-management' ),
			'not_found_in_trash'    => __( 'No animals found in Trash.', 'farm-management' ),
			'item_published'        => __( 'Animal published.', 'farm-management' ),
			'item_updated'          => __( 'Animal updated.', 'farm-management' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'animal' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'thumbnail' ),
			'menu_icon'          => 'dashicons-heart',
		);

		register_post_type( 'fmp_animal', $args );
	}

	/**
	 * Register fmp_crop post type (admin UI only).
	 */
	private static function register_crop() {
		$labels = array(
			'name'                  => _x( 'Crops', 'Post type general name', 'farm-management' ),
			'singular_name'         => _x( 'Crop', 'Post type singular name', 'farm-management' ),
			'menu_name'             => _x( 'Crops', 'Admin Menu text', 'farm-management' ),
			'add_new'               => __( 'Add Crop', 'farm-management' ),
			'add_new_item'          => __( 'Add Crop', 'farm-management' ),
			'new_item'              => __( 'New Crop', 'farm-management' ),
			'edit_item'             => __( 'Edit Crop', 'farm-management' ),
			'view_item'             => __( 'View Crop', 'farm-management' ),
			'all_items'             => __( 'All Crops', 'farm-management' ),
			'search_items'          => __( 'Search Crops', 'farm-management' ),
			'not_found'              => __( 'No crops found.', 'farm-management' ),
			'not_found_in_trash'    => __( 'No crops found in Trash.', 'farm-management' ),
			'item_published'        => __( 'Crop published.', 'farm-management' ),
			'item_updated'          => __( 'Crop updated.', 'farm-management' ),
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

		register_post_type( 'fmp_crop', $args );
	}

	/**
	 * Register fmp_task post type (admin UI only).
	 */
	private static function register_task() {
		$labels = array(
			'name'                  => _x( 'Tasks', 'Post type general name', 'farm-management' ),
			'singular_name'         => _x( 'Task', 'Post type singular name', 'farm-management' ),
			'menu_name'             => _x( 'Tasks', 'Admin Menu text', 'farm-management' ),
			'add_new'               => __( 'Add Task', 'farm-management' ),
			'add_new_item'          => __( 'Add Task', 'farm-management' ),
			'new_item'              => __( 'New Task', 'farm-management' ),
			'edit_item'             => __( 'Edit Task', 'farm-management' ),
			'view_item'             => __( 'View Task', 'farm-management' ),
			'all_items'             => __( 'All Tasks', 'farm-management' ),
			'search_items'          => __( 'Search Tasks', 'farm-management' ),
			'not_found'              => __( 'No tasks found.', 'farm-management' ),
			'not_found_in_trash'    => __( 'No tasks found in Trash.', 'farm-management' ),
			'item_published'        => __( 'Task published.', 'farm-management' ),
			'item_updated'          => __( 'Task updated.', 'farm-management' ),
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

		register_post_type( 'fmp_task', $args );
	}

	/**
	 * Register fmp_inventory_item post type (admin UI only).
	 */
	private static function register_inventory_item() {
		$labels = array(
			'name'                  => _x( 'Inventory', 'Post type general name', 'farm-management' ),
			'singular_name'         => _x( 'Inventory Item', 'Post type singular name', 'farm-management' ),
			'menu_name'             => _x( 'Inventory', 'Admin Menu text', 'farm-management' ),
			'add_new'               => __( 'Add Item', 'farm-management' ),
			'add_new_item'          => __( 'Add Item', 'farm-management' ),
			'new_item'              => __( 'New Item', 'farm-management' ),
			'edit_item'             => __( 'Edit Item', 'farm-management' ),
			'view_item'             => __( 'View Item', 'farm-management' ),
			'all_items'             => __( 'All Inventory', 'farm-management' ),
			'search_items'          => __( 'Search Inventory', 'farm-management' ),
			'not_found'              => __( 'No items found.', 'farm-management' ),
			'not_found_in_trash'    => __( 'No items found in Trash.', 'farm-management' ),
			'item_published'        => __( 'Item published.', 'farm-management' ),
			'item_updated'          => __( 'Item updated.', 'farm-management' ),
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

		register_post_type( 'fmp_inventory_item', $args );
	}

	/**
	 * Register fmp_expense post type (admin UI only).
	 */
	private static function register_expense() {
		$labels = array(
			'name'                  => _x( 'Expenses', 'Post type general name', 'farm-management' ),
			'singular_name'         => _x( 'Expense', 'Post type singular name', 'farm-management' ),
			'menu_name'             => _x( 'Expenses', 'Admin Menu text', 'farm-management' ),
			'add_new'               => __( 'Add Expense', 'farm-management' ),
			'add_new_item'          => __( 'Add Expense', 'farm-management' ),
			'new_item'              => __( 'New Expense', 'farm-management' ),
			'edit_item'             => __( 'Edit Expense', 'farm-management' ),
			'view_item'             => __( 'View Expense', 'farm-management' ),
			'all_items'             => __( 'All Expenses', 'farm-management' ),
			'search_items'          => __( 'Search Expenses', 'farm-management' ),
			'not_found'              => __( 'No expenses found.', 'farm-management' ),
			'not_found_in_trash'    => __( 'No expenses found in Trash.', 'farm-management' ),
			'item_published'        => __( 'Expense published.', 'farm-management' ),
			'item_updated'          => __( 'Expense updated.', 'farm-management' ),
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

		register_post_type( 'fmp_expense', $args );
	}
}
