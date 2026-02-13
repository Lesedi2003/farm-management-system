<?php
/**
 * Role-based access: farm_manager, farm_staff and FMP capabilities.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Capabilities
 */
class FMP_Capabilities {

	/** Capability: access Farm Management (dashboard, list/add/edit records). */
	const MANAGE_FARM = 'fmp_manage_farm';

	/** Capability: export reports to CSV. */
	const EXPORT_REPORTS = 'fmp_export_reports';

	/** Capability: access Settings page. */
	const MANAGE_SETTINGS = 'fmp_manage_settings';

	/** Capability: delete animals, crops, tasks, inventory, expenses, vaccinations. */
	const DELETE_RECORDS = 'fmp_delete_records';

	/** Role: full access (edit, delete, settings, export). */
	const ROLE_MANAGER = 'farm_manager';

	/** Role: edit only (no delete, no export, no settings). */
	const ROLE_STAFF = 'farm_staff';

	/**
	 * Add roles and assign capabilities. Call on plugin activation.
	 */
	public static function add_roles() {
		$manager = get_role( self::ROLE_MANAGER );
		if ( ! $manager ) {
			add_role(
				self::ROLE_MANAGER,
				__( 'Farm Manager', 'farm-management' ),
				array(
					'read'                   => true,
					self::MANAGE_FARM       => true,
					self::EXPORT_REPORTS    => true,
					self::MANAGE_SETTINGS    => true,
					self::DELETE_RECORDS    => true,
				)
			);
		} else {
			foreach ( array( self::MANAGE_FARM, self::EXPORT_REPORTS, self::MANAGE_SETTINGS, self::DELETE_RECORDS ) as $cap ) {
				$manager->add_cap( $cap );
			}
		}

		$staff = get_role( self::ROLE_STAFF );
		if ( ! $staff ) {
			add_role(
				self::ROLE_STAFF,
				__( 'Farm Staff', 'farm-management' ),
				array(
					'read'             => true,
					self::MANAGE_FARM  => true,
				)
			);
		} else {
			$staff->add_cap( self::MANAGE_FARM );
			/* Farm staff can only edit; never grant delete. */
			$staff->remove_cap( self::DELETE_RECORDS );
			$staff->remove_cap( self::EXPORT_REPORTS );
			$staff->remove_cap( self::MANAGE_SETTINGS );
		}

		// Administrator: grant all FMP caps for backward compatibility.
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( array( self::MANAGE_FARM, self::EXPORT_REPORTS, self::MANAGE_SETTINGS, self::DELETE_RECORDS ) as $cap ) {
				$admin->add_cap( $cap );
			}
		}
	}

	/**
	 * Remove FMP capabilities from all roles. Call on plugin uninstall (optional).
	 */
	public static function remove_caps() {
		$roles = array( 'administrator', self::ROLE_MANAGER, self::ROLE_STAFF );
		$caps = array( self::MANAGE_FARM, self::EXPORT_REPORTS, self::MANAGE_SETTINGS, self::DELETE_RECORDS );
		foreach ( $roles as $role_slug ) {
			$role = get_role( $role_slug );
			if ( $role ) {
				foreach ( $caps as $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}
		remove_role( self::ROLE_STAFF );
		remove_role( self::ROLE_MANAGER );
	}

	/**
	 * Whether the current user can access the main Farm Management area (dashboard, records).
	 *
	 * @return bool
	 */
	public static function current_user_can_manage_farm() {
		return current_user_can( self::MANAGE_FARM );
	}

	/**
	 * Whether the current user can export reports.
	 *
	 * @return bool
	 */
	public static function current_user_can_export() {
		return current_user_can( self::EXPORT_REPORTS );
	}

	/**
	 * Whether the current user can manage settings.
	 *
	 * @return bool
	 */
	public static function current_user_can_manage_settings() {
		return current_user_can( self::MANAGE_SETTINGS );
	}

	/**
	 * Whether the current user can delete FMP records.
	 *
	 * @return bool
	 */
	public static function current_user_can_delete_records() {
		return current_user_can( self::DELETE_RECORDS );
	}

	/** FMP post types (for map_meta_cap). */
	const POST_TYPES = array( 'fmp_animal', 'fmp_crop', 'fmp_task', 'fmp_inventory_item', 'fmp_expense', 'fmp_vaccination' );

	/**
	 * Map edit/delete/read for FMP post types to our capabilities.
	 *
	 * @param array  $caps    Required capabilities.
	 * @param string $cap     Capability being checked.
	 * @param int    $user_id User ID.
	 * @param array  $args    Optional args (e.g. post ID).
	 * @return array
	 */
	public static function map_meta_cap( $caps, $cap, $user_id, $args ) {
		if ( empty( $args[0] ) ) {
			return $caps;
		}
		$post_id = is_numeric( $args[0] ) ? (int) $args[0] : 0;
		if ( ! $post_id ) {
			return $caps;
		}
		$post = get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_type, self::POST_TYPES, true ) ) {
			return $caps;
		}

		switch ( $cap ) {
			case 'edit_post':
			case 'read_post':
				return array( self::MANAGE_FARM );
			case 'delete_post':
				return array( self::DELETE_RECORDS );
			default:
				return $caps;
		}
	}
}
