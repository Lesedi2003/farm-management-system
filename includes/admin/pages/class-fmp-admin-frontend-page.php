<?php
/**
 * Frontend pages instructions: which shortcodes to paste where.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Admin_Frontend_Page
 */
class FMP_Admin_Frontend_Page {

	const PAGE_SLUG = 'fmp-frontend';
	const SETUP_ACTION = 'fmp_setup_portal_pages';

	/**
	 * Register admin_post handler for Setup Portal Pages.
	 */
	public static function init() {
		add_action( 'admin_post_' . self::SETUP_ACTION, array( __CLASS__, 'handle_setup_portal_pages' ), 10 );
	}

	/**
	 * Create SaaS portal pages (Farm Portal, Add Animal, etc.) and redirect back.
	 */
	public static function handle_setup_portal_pages() {
		if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'farm-management' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::SETUP_ACTION );
		FMP_Portal::setup_pages();
		$url = add_query_arg( array( 'page' => self::PAGE_SLUG, 'fmp_portal_setup' => '1' ), admin_url( 'admin.php' ) );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Render the Frontend instructions page.
	 */
	public static function render() {
		if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'farm-management' ), '', array( 'response' => 403 ) );
		}
		if ( isset( $_GET['fmp_portal_setup'] ) && $_GET['fmp_portal_setup'] === '1' ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Portal pages created or updated. You can now open the Farm Portal page on the front end.', 'farm-management' ) . '</p></div>';
		}
		$setup_url = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::SETUP_ACTION ), self::SETUP_ACTION );
		?>
		<div class="wrap fmp-admin-wrap fmp-frontend-page">
			<h1><?php esc_html_e( 'Frontend Pages', 'farm-management' ); ?></h1>

			<div class="fmp-frontend-card" style="max-width: 640px; margin: 1em 0; padding: 1.5em; background: #f0f6fc; border: 1px solid #c3c4c7;">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'SaaS Portal (no wp-admin)', 'farm-management' ); ?></h2>
				<p><?php esc_html_e( 'Create pages for the front-end Farm Portal: Dashboard, Add Animal, Add Crop, Add Task, Add Inventory, Add Expense, Add Vaccination. Each user only sees and creates their own records.', 'farm-management' ); ?></p>
				<p><a href="<?php echo esc_url( $setup_url ); ?>" class="button button-primary"><?php esc_html_e( 'Setup Portal Pages', 'farm-management' ); ?></a></p>
				<p class="description"><?php esc_html_e( 'Creates or reuses pages with shortcodes [fmp_portal_dashboard], [fmp_add_animal], etc. Dashboard links point to these pages.', 'farm-management' ); ?></p>
			</div>

			<p class="description"><?php esc_html_e( 'Two worlds: (1) <strong>Public site</strong> — marketing, credibility, no farm data. Use [fmp_public_home] and [fmp_contact] on normal pages; add to a public menu (Home, Features, Pricing, Contact, Login). (2) <strong>Farmer portal</strong> — after login, pages with [fmp_home], [fmp_farm-dashboard], etc. use portal tabs (Home, Dashboard, Animals, Crops, Vaccinations, Reports, Support, Logout) and fmp-portal.css. Do not add portal pages to the public menu.', 'farm-management' ); ?></p>

			<h2 style="margin-top: 1.5em;"><?php esc_html_e( 'Public site (landing, no login)', 'farm-management' ); ?></h2>
			<div class="fmp-frontend-card" style="max-width: 640px; margin: 1.5em 0; padding: 1.5em; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
				<h3 style="margin-top: 0;"><?php esc_html_e( 'Public Home (site front page)', 'farm-management' ); ?></h3>
				<ol style="margin: 1em 0;">
					<li><?php esc_html_e( 'Create a Page with slug "home" (or use any slug).', 'farm-management' ); ?></li>
					<li><?php esc_html_e( 'Paste:', 'farm-management' ); ?></li>
				</ol>
				<code class="fmp-shortcode-block" style="display: block; padding: 12px 16px; background: #f0f0f1; border: 1px solid #c3c4c7; font-size: 14px;">[fmp_public_home]</code>
				<p style="margin: 1em 0 0;"><?php esc_html_e( 'Marketing intro: title, subtitle, "View Demo" (links to Contact), "Login to Portal" (links to portal or login). No portal tabs, no farm data. Set as Front page in Settings → Reading.', 'farm-management' ); ?></p>
			</div>

			<h2 style="margin-top: 1.5em;"><?php esc_html_e( 'Farmer portal (after login)', 'farm-management' ); ?></h2>
			<div class="fmp-frontend-card" style="max-width: 640px; margin: 1.5em 0; padding: 1.5em; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
				<h3 style="margin-top: 0;"><?php esc_html_e( 'Portal Home (farmer landing)', 'farm-management' ); ?></h3>
				<ol style="margin: 1em 0;">
					<li><?php esc_html_e( 'Create a Page with slug "portal" (e.g. title "Portal" or "My Farm").', 'farm-management' ); ?></li>
					<li><?php esc_html_e( 'Paste:', 'farm-management' ); ?></li>
				</ol>
				<code class="fmp-shortcode-block" style="display: block; padding: 12px 16px; background: #f0f0f1; border: 1px solid #c3c4c7; font-size: 14px;">[fmp_home]</code>
				<p style="margin: 1em 0 0;"><?php esc_html_e( 'Dashboard-style: KPIs, feature cards, "What\'s due next". Requires login. Shows portal tabs (Home, Dashboard, Animals, Crops, Vaccinations, Reports, Support, Logout). Link "Login to Portal" from public home to this page (or to wp-login with redirect here).', 'farm-management' ); ?></p>
			</div>

			<div class="fmp-frontend-card" style="max-width: 640px; margin: 1.5em 0; padding: 1.5em; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Farm Dashboard page', 'farm-management' ); ?></h2>
				<ol style="margin: 1em 0;">
					<li><?php esc_html_e( 'Go to Pages → Add New.', 'farm-management' ); ?></li>
					<li><?php esc_html_e( 'Set the title to "Farm Dashboard" (or any title you like).', 'farm-management' ); ?></li>
					<li><?php esc_html_e( 'In the content area, paste exactly:', 'farm-management' ); ?></li>
				</ol>
				<code class="fmp-shortcode-block" style="display: block; padding: 12px 16px; background: #f0f0f1; border: 1px solid #c3c4c7; font-size: 14px;">[fmp_farm-dashboard]</code>
				<p style="margin: 1em 0 0;"><?php esc_html_e( 'Shows the full dashboard: all vaccinations and all crops in one page. Only logged-in users can see it.', 'farm-management' ); ?></p>
				<p style="margin: 0.5em 0 0;"><?php esc_html_e( 'Publish the page. Add it to your menu under Appearance → Menus.', 'farm-management' ); ?></p>
			</div>

			<div class="fmp-frontend-card" style="max-width: 640px; margin: 1.5em 0; padding: 1.5em; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Animals page', 'farm-management' ); ?></h2>
				<ol style="margin: 1em 0;">
					<li><?php esc_html_e( 'Go to Pages → Add New.', 'farm-management' ); ?></li>
					<li><?php esc_html_e( 'Set the title to "Animals" (or any title you like).', 'farm-management' ); ?></li>
					<li><?php esc_html_e( 'In the content area, paste exactly:', 'farm-management' ); ?></li>
				</ol>
				<code class="fmp-shortcode-block" style="display: block; padding: 12px 16px; background: #f0f0f1; border: 1px solid #c3c4c7; font-size: 14px;">[fmp_animals]</code>
				<p style="margin: 1em 0 0;"><?php esc_html_e( 'Publish the page. Add it to your menu under Appearance → Menus.', 'farm-management' ); ?></p>
			</div>

			<div class="fmp-frontend-card" style="max-width: 640px; margin: 1.5em 0; padding: 1.5em; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Vaccinations due page', 'farm-management' ); ?></h2>
				<ol style="margin: 1em 0;">
					<li><?php esc_html_e( 'Go to Pages → Add New.', 'farm-management' ); ?></li>
					<li><?php esc_html_e( 'Set the title to "Vaccinations Due" (or any title you like).', 'farm-management' ); ?></li>
					<li><?php esc_html_e( 'In the content area, paste exactly:', 'farm-management' ); ?></li>
				</ol>
				<code class="fmp-shortcode-block" style="display: block; padding: 12px 16px; background: #f0f0f1; border: 1px solid #c3c4c7; font-size: 14px;">[fmp_vaccinations_due]</code>
				<p style="margin: 1em 0 0;"><?php esc_html_e( 'This shows vaccinations due in the next 14 days (including overdue). Optional: use [fmp_vaccinations_due days="30"] for 30 days.', 'farm-management' ); ?></p>
				<p style="margin: 0.5em 0 0;"><?php esc_html_e( 'Publish the page. Add it to your menu under Appearance → Menus.', 'farm-management' ); ?></p>
			</div>

			<div class="fmp-frontend-card" style="max-width: 640px; margin: 1.5em 0; padding: 1.5em; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'All vaccinations (farmer dashboard)', 'farm-management' ); ?></h2>
				<ol style="margin: 1em 0;">
					<li><?php esc_html_e( 'Go to Pages → Add New.', 'farm-management' ); ?></li>
					<li><?php esc_html_e( 'Set the title to "Vaccinations" or "Farmer Dashboard" (or any title you like).', 'farm-management' ); ?></li>
					<li><?php esc_html_e( 'In the content area, paste exactly:', 'farm-management' ); ?></li>
				</ol>
				<code class="fmp-shortcode-block" style="display: block; padding: 12px 16px; background: #f0f0f1; border: 1px solid #c3c4c7; font-size: 14px;">[fmp_vaccinations]</code>
				<p style="margin: 1em 0 0;"><?php esc_html_e( 'Shows all vaccination records in a table (Animal Name, Vaccine Name, Date Given, Next Due Date, Status). Status: Due Soon (within 14 days), Overdue, or OK. Only logged-in users can see the table.', 'farm-management' ); ?></p>
				<p style="margin: 0.5em 0 0;"><?php esc_html_e( 'Publish the page. Add it to your menu under Appearance → Menus.', 'farm-management' ); ?></p>
			</div>

			<div class="fmp-frontend-card" style="max-width: 640px; margin: 1.5em 0; padding: 1.5em; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Crops page (farmer dashboard)', 'farm-management' ); ?></h2>
				<ol style="margin: 1em 0;">
					<li><?php esc_html_e( 'Go to Pages → Add New.', 'farm-management' ); ?></li>
					<li><?php esc_html_e( 'Set the title to "Crops" (or any title you like).', 'farm-management' ); ?></li>
					<li><?php esc_html_e( 'In the content area, paste exactly:', 'farm-management' ); ?></li>
				</ol>
				<code class="fmp-shortcode-block" style="display: block; padding: 12px 16px; background: #f0f0f1; border: 1px solid #c3c4c7; font-size: 14px;">[fmp_crops]</code>
				<p style="margin: 1em 0 0;"><?php esc_html_e( 'Shows all crops in a table (Crop Name, Type, Location, Planting Date, Harvest Date, Status). Only logged-in users can see the table.', 'farm-management' ); ?></p>
				<p style="margin: 0.5em 0 0;"><?php esc_html_e( 'Publish the page. Add it to your menu under Appearance → Menus.', 'farm-management' ); ?></p>
			</div>

			<div class="fmp-frontend-card" style="max-width: 640px; margin: 1.5em 0; padding: 1.5em; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Reports page', 'farm-management' ); ?></h2>
				<ol style="margin: 1em 0;">
					<li><?php esc_html_e( 'Go to Pages → Add New.', 'farm-management' ); ?></li>
					<li><?php esc_html_e( 'Set the title to "Reports" (or any title you like).', 'farm-management' ); ?></li>
					<li><?php esc_html_e( 'In the content area, paste exactly:', 'farm-management' ); ?></li>
				</ol>
				<code class="fmp-shortcode-block" style="display: block; padding: 12px 16px; background: #f0f0f1; border: 1px solid #c3c4c7; font-size: 14px;">[fmp_reports]</code>
				<p style="margin: 1em 0 0;"><?php esc_html_e( 'Shows reports: Vaccinations due (including overdue), Animals by species & status, Monthly expenses summary (current month). Only logged-in users can see reports. Users with farm management access get a link to full reports in admin.', 'farm-management' ); ?></p>
				<p style="margin: 0.5em 0 0;"><?php esc_html_e( 'Publish the page. Add it to your menu under Appearance → Menus.', 'farm-management' ); ?></p>
			</div>

			<div class="fmp-frontend-card" style="max-width: 640px; margin: 1.5em 0; padding: 1.5em; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Contact page', 'farm-management' ); ?></h2>
				<ol style="margin: 1em 0;">
					<li><?php esc_html_e( 'Go to Pages → Add New.', 'farm-management' ); ?></li>
					<li><?php esc_html_e( 'Set the title to "Contact" (or any title you like).', 'farm-management' ); ?></li>
					<li><?php esc_html_e( 'In the content area, paste exactly:', 'farm-management' ); ?></li>
				</ol>
				<code class="fmp-shortcode-block" style="display: block; padding: 12px 16px; background: #f0f0f1; border: 1px solid #c3c4c7; font-size: 14px;">[fmp_contact]</code>
				<p style="margin: 1em 0 0;"><?php esc_html_e( 'Shows farm contact details (address, phone, email) from Farm Management → Settings → Contact info, plus a contact form. Visitors can send a message; it is emailed to the contact email (or site admin if not set). Visible to everyone.', 'farm-management' ); ?></p>
				<p style="margin: 0.5em 0 0;"><?php esc_html_e( 'Publish the page. Add it to your menu under Appearance → Menus.', 'farm-management' ); ?></p>
			</div>

			<p><strong><?php esc_html_e( 'Summary: pages to create and shortcodes to paste', 'farm-management' ); ?></strong></p>
			<table class="widefat striped" style="max-width: 640px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Page title (suggested)', 'farm-management' ); ?></th>
						<th><?php esc_html_e( 'Paste this shortcode in the page content', 'farm-management' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php esc_html_e( 'Public Home (front page)', 'farm-management' ); ?></td>
						<td><code>[fmp_public_home]</code></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Portal Home (slug: portal)', 'farm-management' ); ?></td>
						<td><code>[fmp_home]</code></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Farm Dashboard', 'farm-management' ); ?></td>
						<td><code>[fmp_farm-dashboard]</code></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Animals', 'farm-management' ); ?></td>
						<td><code>[fmp_animals]</code></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Crops', 'farm-management' ); ?></td>
						<td><code>[fmp_crops]</code></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Vaccinations', 'farm-management' ); ?></td>
						<td><code>[fmp_vaccinations]</code></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Reports', 'farm-management' ); ?></td>
						<td><code>[fmp_reports]</code></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Support (portal)', 'farm-management' ); ?></td>
						<td><code>[fmp_support]</code></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Contact (public)', 'farm-management' ); ?></td>
						<td><code>[fmp_contact]</code></td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}
}
