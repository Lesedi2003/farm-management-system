<?php
/**
 * Frontend shortcodes: vaccinations and crops tables (farmer dashboard).
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Frontend
 */
class FMP_Frontend {

	const SHORTCODE_PORTAL         = 'fmp_portal';
	const SHORTCODE_VACCINATIONS   = 'fmp_vaccinations';
	const SHORTCODE_CROPS          = 'fmp_crops';
	const SHORTCODE_FARM_DASHBOARD = 'fmp_farm-dashboard';
	const SHORTCODE_REPORTS       = 'fmp_reports';
	const SHORTCODE_CONTACT       = 'fmp_contact';
	const SHORTCODE_HOME          = 'fmp_home';
	const SHORTCODE_PUBLIC_HOME   = 'fmp_public_home';
	const SHORTCODE_SUPPORT       = 'fmp_support';

	/** Shortcodes that trigger portal protection (login required). */
	const PORTAL_SHORTCODES = array( 'fmp_portal', 'fmp_farm-dashboard', 'fmp_animals', 'fmp_crops', 'fmp_vaccinations', 'fmp_reports', 'fmp_support' );

	/** Page slugs that trigger portal protection/CSS (safe: use with post_name ?? ''). */
	const PORTAL_SLUGS = array( 'portal', 'portal-2', 'dashboard', 'farm-dashboard', 'animals', 'crops', 'vaccinations', 'reports', 'support' );
	const CSS_HANDLE               = 'fmp-frontend';
	const PORTAL_CSS_HANDLE       = 'fmp-portal';
	const CONTACT_FORM_NONCE_ACTION = 'fmp_contact_form';
	const SUPPORT_FORM_NONCE_ACTION = 'fmp_support_form';

	/** Portal tab keys and page slugs. Used only inside the farmer portal (after login). */
	const PORTAL_TABS = array(
		'home'         => array( 'slug' => 'portal', 'label' => 'Home', 'fallback_slug' => 'dashboard' ),
		'dashboard'    => array( 'slug' => 'farm-dashboard', 'label' => 'Dashboard', 'fallback_slug' => 'dashboard' ),
		'animals'      => array( 'slug' => 'animals', 'label' => 'Animals' ),
		'crops'        => array( 'slug' => 'crops', 'label' => 'Crops' ),
		'vaccinations' => array( 'slug' => 'vaccinations', 'label' => 'Vaccinations' ),
		'reports'      => array( 'slug' => 'reports', 'label' => 'Reports' ),
		'support'      => array( 'slug' => 'support', 'label' => 'Support' ),
		'logout'       => array( 'label' => 'Logout', 'is_logout' => true ),
	);

	/** Page slugs that trigger portal CSS (no shortcode needed). */
	const PORTAL_PAGE_SLUGS = array( 'portal', 'dashboard', 'farm-dashboard', 'animals', 'crops', 'vaccinations', 'reports', 'support' );

	/**
	 * Constructor. Registers shortcodes and enqueues frontend CSS when shortcode is present.
	 */
	public function __construct() {
		add_shortcode( self::SHORTCODE_PORTAL, array( __CLASS__, 'render_portal' ) );
		add_shortcode( self::SHORTCODE_VACCINATIONS, array( __CLASS__, 'render_vaccinations' ) );
		add_shortcode( self::SHORTCODE_CROPS, array( __CLASS__, 'render_crops' ) );
		add_shortcode( self::SHORTCODE_FARM_DASHBOARD, array( __CLASS__, 'render_farm_dashboard' ) );
		add_shortcode( self::SHORTCODE_REPORTS, array( __CLASS__, 'render_reports' ) );
		add_shortcode( self::SHORTCODE_CONTACT, array( __CLASS__, 'render_contact' ) );
		add_shortcode( self::SHORTCODE_HOME, array( __CLASS__, 'render_home' ) );
		add_shortcode( self::SHORTCODE_PUBLIC_HOME, array( __CLASS__, 'render_public_home' ) );
		add_shortcode( self::SHORTCODE_SUPPORT, array( __CLASS__, 'render_support' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_css' ), 10 );
		add_filter( 'wp_nav_menu_objects', array( __CLASS__, 'nav_menu_portal_link_to_login' ), 10, 2 );
		add_filter( 'render_block', array( __CLASS__, 'block_navigation_portal_link_to_login' ), 10, 2 );
	}

	/**
	 * Detect if the current request is a portal page (by shortcode presence or page slug).
	 * Used for CSS enqueue and consistent protection logic. Safe: no undefined array keys.
	 *
	 * @return bool
	 */
	private static function fmp_is_portal_request() {
		if ( ! is_singular() ) {
			return false;
		}
		$post = get_post();
		if ( ! $post ) {
			return false;
		}
		$content = isset( $post->post_content ) ? $post->post_content : '';
		if ( ! empty( $content ) ) {
			foreach ( self::PORTAL_SHORTCODES as $sc ) {
				if ( has_shortcode( $content, $sc ) ) {
					return true;
				}
			}
		}
		$slug = $post->post_name ?? '';
		return $slug !== '' && in_array( $slug, self::PORTAL_SLUGS, true );
	}

	/**
	 * Reusable login guard for portal shortcodes. Blocks access when not logged in.
	 * If headers not sent: redirects to wp_login_url and exits. Otherwise returns styled HTML (login card).
	 *
	 * @param string $redirect_to Optional. URL to redirect to after login. Empty = current page permalink.
	 * @return true|string True if user is logged in; otherwise HTML string to output (or redirect + exit).
	 */
	private static function fmp_require_login( $redirect_to = '' ) {
		if ( is_user_logged_in() ) {
			return true;
		}
		$redirect_to = $redirect_to !== '' ? $redirect_to : get_permalink();
		$login_url   = wp_login_url( $redirect_to );
		if ( ! headers_sent() ) {
			wp_safe_redirect( $login_url );
			exit;
		}
		$login_url = wp_login_url( $redirect_to );
		return '
		<div class="fmp-portal">
			<div class="fmp-container">
				<div class="fmp-card fmp-login-card">
					<h2 class="fmp-title">' . esc_html__( 'Login Required', 'farm-management' ) . '</h2>
					<p class="fmp-subtitle">' . esc_html__( 'Please log in to access the Farm Portal.', 'farm-management' ) . '</p>
					<a class="fmp-btn fmp-btn-primary" href="' . esc_url( $login_url ) . '">' . esc_html__( 'Log in', 'farm-management' ) . '</a>
				</div>
			</div>
		</div>';
	}

	/**
	 * Render the login-required message (Restricted Area card with Log in button).
	 * Uses WP native login URL; redirect back to current page after login.
	 *
	 * @return string
	 */
	private static function fmp_render_login_required() {
		$login_url = wp_login_url( get_permalink() );
		return '
		<div class="fmp-portal">
			<div class="fmp-container">
				<div class="fmp-card fmp-login-card">
					<h2 class="fmp-title">' . esc_html__( 'Restricted Area', 'farm-management' ) . '</h2>
					<p class="fmp-subtitle">' . esc_html__( 'This portal is only accessible to authorized farm staff. Please log in to continue.', 'farm-management' ) . '</p>
					<a class="fmp-btn fmp-btn-primary" href="' . esc_url( $login_url ) . '">' . esc_html__( 'Log in', 'farm-management' ) . '</a>
				</div>
			</div>
		</div>';
	}

	/**
	 * [fmp_portal] – alias for portal entry; same as dashboard (hard-locked, then full UI when logged in).
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string
	 */
	public static function render_portal( $atts ) {
		return self::render_farm_dashboard( $atts );
	}

	/**
	 * Get page URL by slug. Never passes null/empty to get_page_by_path.
	 *
	 * @param string $slug Page slug (can be empty).
	 * @return string home_url('/') for slug 'home', permalink if page exists, otherwise '#'.
	 */
	public static function get_page_url_by_slug( $slug ) {
		$slug = isset( $slug ) ? sanitize_title( (string) $slug ) : '';
		if ( $slug === '' ) {
			return '#';
		}
		if ( $slug === 'home' ) {
			return home_url( '/' );
		}
		$page = get_page_by_path( $slug );
		return $page ? get_permalink( $page ) : '#';
	}

	/**
	 * Get portal tab config with URLs. Uses page slug to resolve permalink. Defensive: no missing keys, no null to get_page_by_path.
	 *
	 * @return array[] Each item: key, label, url, active (set by caller).
	 */
	public static function get_portal_tabs( $active_tab = '' ) {
		$tabs   = array();
		$portal_url = self::get_portal_home_url();

		foreach ( self::PORTAL_TABS as $key => $config ) {
			$config = is_array( $config ) ? $config : array();
			if ( ! empty( $config['is_logout'] ) ) {
				$url = wp_logout_url( $portal_url );
			} elseif ( $key === 'home' ) {
				/* Home tab goes to the site front page (home page). */
				$front_id = (int) get_option( 'page_on_front' );
				$url      = $front_id ? get_permalink( $front_id ) : home_url( '/' );
				if ( ! $url ) {
					$url = home_url( '/' );
				}
			} else {
				$slug = sanitize_title( (string) ( $config['slug'] ?? '' ) );
				if ( $slug === '' ) {
					if ( isset( $config['url'] ) && is_string( $config['url'] ) && $config['url'] !== '' ) {
						$url = $config['url'];
					} else {
						continue;
					}
				} else {
					$page = get_page_by_path( $slug );
					$fallback_slug = sanitize_title( (string) ( $config['fallback_slug'] ?? '' ) );
					if ( ! $page && $fallback_slug !== '' ) {
						$page = get_page_by_path( $fallback_slug );
					}
					$url = $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
				}
			}
			$label = isset( $config['label'] ) ? $config['label'] : $key;
			$tabs[] = array(
				'key'    => $key,
				'label'  => $label,
				'url'    => $url,
				'active' => ( $active_tab === $key ),
			);
		}
		return $tabs;
	}

	/**
	 * URL of the portal home page (page with [fmp_home], e.g. /portal). Used for logout redirect and Login to Portal link.
	 *
	 * @return string
	 */
	public static function get_portal_home_url() {
		$page = get_page_by_path( 'portal' );
		if ( ! $page ) {
			$page = get_page_by_path( 'dashboard' );
		}
		if ( ! $page ) {
			$page = get_page_by_path( 'farm-dashboard' );
		}
		return $page ? get_permalink( $page ) : home_url( '/portal/' );
	}

	/**
	 * Get portal entry URLs (pages that lead into the staff portal). Used to detect "Portal" menu links.
	 *
	 * @return string[] Normalized URLs (no trailing slash).
	 */
	private static function get_portal_entry_urls() {
		$urls = array();
		foreach ( array( 'portal', 'dashboard', 'farm-dashboard' ) as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page ) {
				$urls[] = untrailingslashit( get_permalink( $page ) );
			}
		}
		if ( empty( $urls ) ) {
			$urls[] = untrailingslashit( home_url( '/portal/' ) );
		}
		return array_unique( $urls );
	}

	/**
	 * When user is logged out, make "Portal" menu items point to the login page (redirect back to portal after login).
	 *
	 * @param array $items Menu items.
	 * @param object $args Menu args.
	 * @return array
	 */
	public static function nav_menu_portal_link_to_login( $items, $args ) {
		if ( is_user_logged_in() || empty( $items ) || ! is_array( $items ) ) {
			return $items;
		}
		$portal_urls = self::get_portal_entry_urls();
		foreach ( $items as $item ) {
			if ( empty( $item->url ) ) {
				continue;
			}
			$item_url = untrailingslashit( $item->url );
			$is_portal_link = false;
			foreach ( $portal_urls as $portal_url ) {
				if ( $item_url === $portal_url || strpos( $item_url, $portal_url ) === 0 ) {
					$is_portal_link = true;
					break;
				}
			}
			if ( ! $is_portal_link ) {
				$title = isset( $item->title ) ? trim( $item->title ) : '';
				if ( $title === '' && isset( $item->post_title ) ) {
					$title = trim( $item->post_title );
				}
				if ( strtolower( $title ) === 'portal' ) {
					$is_portal_link = true;
				}
			}
			if ( $is_portal_link ) {
				$item->url = wp_login_url( $item->url );
			}
		}
		return $items;
	}

	/**
	 * When user is logged out, replace Portal links in Navigation block output with login URL.
	 *
	 * @param string $block_content Block HTML.
	 * @param array  $block Block data.
	 * @return string
	 */
	public static function block_navigation_portal_link_to_login( $block_content, $block ) {
		if ( is_user_logged_in() || empty( $block_content ) ) {
			return $block_content;
		}
		$block_name = isset( $block['blockName'] ) ? $block['blockName'] : '';
		if ( $block_name !== 'core/navigation' ) {
			return $block_content;
		}
		$portal_urls = self::get_portal_entry_urls();
		return preg_replace_callback(
			'#<a\s([^>]*href=)(["\'])([^"\']+)(["\'])([^>]*)>([^<]*)</a>#is',
			function ( $m ) use ( $portal_urls ) {
				$href  = $m[3];
				$label = isset( $m[6] ) ? trim( strip_tags( $m[6] ) ) : '';
				if ( strtolower( $label ) !== 'portal' ) {
					return $m[0];
				}
				$href_normalized = untrailingslashit( $href );
				foreach ( $portal_urls as $portal_url ) {
					if ( $href_normalized === $portal_url || strpos( $href_normalized, $portal_url . '/' ) === 0 ) {
						$login_url = wp_login_url( $href );
						return '<a ' . $m[1] . $m[2] . esc_url( $login_url ) . $m[4] . $m[5] . '>' . $m[6] . '</a>';
					}
				}
				return $m[0];
			},
			$block_content
		);
	}

	/**
	 * Wrap content in portal layout: header, nav tabs, container. Hard-lock: not logged in = login screen only.
	 *
	 * @param string $title       Page title.
	 * @param string $subtitle    Optional subtitle.
	 * @param string $active_tab  Key from PORTAL_TABS (e.g. 'dashboard', 'animals').
	 * @param string $content_html Inner HTML.
	 * @return string
	 */
	public static function portal_wrap( $title, $subtitle, $active_tab, $content_html ) {
		if ( ! is_user_logged_in() ) {
			return self::fmp_render_login_required();
		}

		$tabs = self::get_portal_tabs( $active_tab );
		ob_start();
		?>
		<div class="fmp-portal">
			<div class="fmp-container">
				<div class="fmp-page-head">
					<h1 class="fmp-title fmp-portal-title"><?php echo esc_html( $title ); ?></h1>
					<?php if ( $subtitle !== '' ) : ?>
						<p class="fmp-subtitle fmp-portal-subtitle"><?php echo esc_html( $subtitle ); ?></p>
					<?php endif; ?>
				</div>
				<nav class="fmp-portal-tabs" role="navigation" aria-label="<?php esc_attr_e( 'Portal sections', 'farm-management' ); ?>">
					<?php foreach ( $tabs as $tab ) : ?>
						<a href="<?php echo esc_url( $tab['url'] ); ?>" class="<?php echo $tab['active'] ? 'fmp-portal-tab-active' : ''; ?>"><?php echo esc_html( $tab['label'] ); ?></a>
					<?php endforeach; ?>
				</nav>
				<div class="fmp-page-body fmp-portal-content">
					<?php echo $content_html; ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Whether current request should load portal CSS (portal shortcode/slug or home shortcode).
	 * Uses safe $post->post_name ?? '' to avoid undefined array key.
	 *
	 * @return bool
	 */
	public static function should_enqueue_portal_css() {
		global $post;
		if ( ! $post || ! is_singular( 'page' ) ) {
			return false;
		}
		$content = isset( $post->post_content ) ? $post->post_content : '';
		if ( has_shortcode( $content, self::SHORTCODE_HOME ) ) {
			return true;
		}
		if ( has_shortcode( $content, self::SHORTCODE_PUBLIC_HOME ) ) {
			return true;
		}
		if ( is_front_page() ) {
			return true;
		}
		$slug = $post->post_name ?? '';
		if ( $slug === 'home' ) {
			return true;
		}
		if ( self::fmp_is_portal_request() ) {
			return true;
		}
		return $slug !== '' && in_array( $slug, self::PORTAL_SLUGS, true );
	}

	/**
	 * Enqueue frontend and portal CSS on pages that contain dashboard shortcodes or portal page slug.
	 */
	public static function enqueue_frontend_css() {
		global $post;
		if ( ! $post || ! is_singular() ) {
			return;
		}
		$has_shortcode = has_shortcode( $post->post_content, self::SHORTCODE_VACCINATIONS )
			|| has_shortcode( $post->post_content, self::SHORTCODE_CROPS )
			|| has_shortcode( $post->post_content, self::SHORTCODE_FARM_DASHBOARD )
			|| has_shortcode( $post->post_content, self::SHORTCODE_REPORTS )
			|| has_shortcode( $post->post_content, self::SHORTCODE_CONTACT )
			|| has_shortcode( $post->post_content, self::SHORTCODE_SUPPORT )
			|| has_shortcode( $post->post_content, self::SHORTCODE_HOME )
			|| has_shortcode( $post->post_content, self::SHORTCODE_PUBLIC_HOME );
		if ( ! $has_shortcode && ! self::should_enqueue_portal_css() ) {
			return;
		}
		wp_enqueue_style(
			self::CSS_HANDLE,
			FMP_PLUGIN_URL . 'assets/css/fmp-frontend.css',
			array(),
			FMP_VERSION
		);
		/* Portal CSS: full portal pages (tabs, dashboard) or Contact page (same design system). */
		if ( self::should_enqueue_portal_css() || has_shortcode( $post->post_content, self::SHORTCODE_CONTACT ) ) {
			wp_enqueue_style(
				self::PORTAL_CSS_HANDLE,
				FMP_PLUGIN_URL . 'assets/css/fmp-portal.css',
				array( self::CSS_HANDLE ),
				FMP_VERSION
			);
			wp_enqueue_style(
				'fmp-dm-sans',
				'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap',
				array(),
				null
			);
			/* Farmer UI only for full portal (tabs); Contact page uses portal.css design tokens only. */
			if ( self::should_enqueue_portal_css() ) {
				wp_enqueue_style(
					'fmp-farmer-ui',
					FMP_PLUGIN_URL . 'assets/css/fmp-farmer-ui.css',
					array( self::PORTAL_CSS_HANDLE ),
					FMP_VERSION
				);
			}
		}
	}

	/**
	 * [fmp_public_home] – public landing (no login). Light portal layout: hero, feature cards, screenshots, CTA.
	 * Use on the site front page (Home — Front Page). Attribute: screenshots="ID,ID,ID" (attachment IDs).
	 *
	 * @param array $atts Shortcode attributes. Optional: screenshots (comma-separated attachment IDs).
	 * @return string
	 */
	public static function render_public_home( $atts ) {
		$atts = shortcode_atts( array(
			'screenshots' => '',
		), $atts, 'fmp_public_home' );

		$portal_url  = self::get_portal_home_url();
		$login_url   = wp_login_url( $portal_url );
		$contact_url = self::get_page_url_by_slug( 'contact' );
		$contact_url = ( $contact_url !== '#' ) ? $contact_url : home_url( '/contact/' );
		$cta_primary  = is_user_logged_in() ? $portal_url : $login_url;
		$cta_primary_label = is_user_logged_in() ? __( 'Open Portal', 'farm-management' ) : __( 'Log in to Portal', 'farm-management' );

		$screenshot_ids = array();
		if ( isset( $atts['screenshots'] ) && is_string( $atts['screenshots'] ) && $atts['screenshots'] !== '' ) {
			$screenshot_ids = array_map( 'absint', array_filter( array_map( 'trim', explode( ',', $atts['screenshots'] ) ) ) );
		}

		$features = array(
			array( 'title' => __( 'Animals', 'farm-management' ), 'desc' => __( 'Track livestock, tags, and health', 'farm-management' ) ),
			array( 'title' => __( 'Vaccinations', 'farm-management' ), 'desc' => __( 'Due soon / overdue reminders', 'farm-management' ) ),
			array( 'title' => __( 'Crops', 'farm-management' ), 'desc' => __( 'Planting, harvesting, field tracking', 'farm-management' ) ),
			array( 'title' => __( 'Reports', 'farm-management' ), 'desc' => __( 'Insights and exports', 'farm-management' ) ),
		);

		ob_start();
		?>
		<div class="fmp-portal fmp-public-home">
			<div class="fmp-container">
				<section class="fmp-public-hero">
					<h1 class="fmp-public-hero-title"><?php esc_html_e( 'Farm Management System', 'farm-management' ); ?></h1>
					<p class="fmp-public-hero-subtitle"><?php esc_html_e( 'Manage livestock, crops, vaccinations, and reports — all in one place.', 'farm-management' ); ?></p>
					<div class="fmp-cta-row">
						<a href="<?php echo esc_url( $cta_primary ); ?>" class="fmp-btn fmp-btn-primary"><?php echo esc_html( $cta_primary_label ); ?></a>
						<a href="<?php echo esc_url( $contact_url ); ?>" class="fmp-btn fmp-btn-secondary"><?php esc_html_e( 'Contact Us', 'farm-management' ); ?></a>
					</div>
				</section>

				<section class="fmp-public-features">
					<h2 class="fmp-public-section-title"><?php esc_html_e( 'What you can do', 'farm-management' ); ?></h2>
					<div class="fmp-public-features-grid fmp-feature-grid">
						<?php foreach ( $features as $f ) : ?>
							<div class="fmp-portal-card fmp-public-feature-card fmp-feature-card">
								<span class="fmp-portal-card-label"><?php echo esc_html( $f['title'] ); ?></span>
								<p class="fmp-public-feature-desc"><?php echo esc_html( $f['desc'] ); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
				</section>

				<section class="fmp-public-screenshots">
					<h2 class="fmp-public-section-title"><?php esc_html_e( 'Screenshots', 'farm-management' ); ?></h2>
					<?php if ( ! empty( $screenshot_ids ) ) : ?>
						<div class="fmp-screenshot-grid fmp-screenshots-grid">
							<?php foreach ( $screenshot_ids as $id ) :
								if ( ! wp_attachment_is_image( $id ) ) {
									continue;
								}
								$full = wp_get_attachment_image_url( $id, 'full' );
								?>
								<div class="fmp-screenshot-card">
									<a href="<?php echo $full ? esc_url( $full ) : '#'; ?>" class="fmp-screenshot-link" target="_blank" rel="noopener"><?php echo wp_get_attachment_image( $id, 'large' ); ?></a>
								</div>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<p class="fmp-public-empty-state"><?php esc_html_e( 'Add screenshot attachment IDs to the shortcode, e.g. screenshots="123,124,125"', 'farm-management' ); ?></p>
					<?php endif; ?>
				</section>

				<section class="fmp-cta-row fmp-cta-row-bottom">
					<a href="<?php echo esc_url( $cta_primary ); ?>" class="fmp-btn fmp-btn-primary"><?php echo esc_html( $cta_primary_label ); ?></a>
					<a href="<?php echo esc_url( $contact_url ); ?>" class="fmp-btn fmp-btn-secondary"><?php esc_html_e( 'Contact Us', 'farm-management' ); ?></a>
				</section>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * [fmp_vaccinations] – table of all vaccination records (logged-in users only).
	 *
	 * Columns: Animal Name, Vaccine Name, Date Given, Next Due Date, Status.
	 * Status: Due Soon (within 14 days), Overdue, OK.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string
	 */
	public static function render_vaccinations( $atts ) {
		if ( ! is_user_logged_in() ) {
			return self::portal_wrap( __( 'Vaccinations', 'farm-management' ), '', 'vaccinations', '<p class="fmp-login-required">' . esc_html__( 'You must be logged in to view vaccination records.', 'farm-management' ) . '</p>' );
		}

		$can_manage = current_user_can( FMP_Capabilities::MANAGE_FARM );
		$portal_pages = class_exists( 'FMP_Portal' ) ? get_option( FMP_Portal::OPTION_PAGES, array() ) : array();
		$use_portal_edit_vacc = $can_manage && ! empty( $portal_pages['add_vaccination'] );
		$query = new WP_Query( array(
			'post_type'      => 'fmp_vaccination',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'meta_value',
			'meta_key'       => '_fmp_next_due_date',
			'order'          => 'ASC',
		) );
		$vaccinations = $query->posts;

		ob_start();
		?>
		<div class="fmp-shortcode fmp-vaccinations">
			<div class="fmp-table-wrap">
				<table class="fmp-table fmp-table-vaccinations fmp-table-responsive">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Animal Name', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Vaccine Name', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Date Given', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Next Due Date', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Status', 'farm-management' ); ?></th>
							<?php if ( $can_manage ) : ?><th></th><?php endif; ?>
						</tr>
					</thead>
					<tbody>
						<?php
						if ( empty( $vaccinations ) ) {
							echo '<tr><td colspan="' . ( $can_manage ? 6 : 5 ) . '">' . esc_html__( 'No vaccination records found.', 'farm-management' ) . '</td></tr>';
						} else {
							foreach ( $vaccinations as $post ) {
								$animal_id  = (int) get_post_meta( $post->ID, '_fmp_animal_id', true );
								$animal     = self::get_animal_name_by_id( $animal_id );
								$vaccine    = get_post_meta( $post->ID, '_fmp_vaccine_name', true );
								$date_given = get_post_meta( $post->ID, '_fmp_date_given', true );
								$next_due   = get_post_meta( $post->ID, '_fmp_next_due_date', true );
								$status     = FMP_Vaccinations::get_vaccination_status( $next_due );
								$status_label = ( $status === 'overdue' ) ? __( 'Overdue', 'farm-management' ) : ( ( $status === 'due_soon' ) ? __( 'Due Soon', 'farm-management' ) : __( 'OK', 'farm-management' ) );
								$badge_class = ( $status === 'overdue' ) ? 'fmp-badge-overdue' : ( ( $status === 'due_soon' ) ? 'fmp-badge-due-soon' : 'fmp-badge-ok' );
								$edit_link   = $can_manage ? ( $use_portal_edit_vacc && class_exists( 'FMP_Portal' ) ? ( FMP_Portal::get_edit_url( 'vaccination', $post->ID ) ?: get_edit_post_link( $post->ID ) ) : get_edit_post_link( $post->ID ) ) : '';
								echo '<tr>';
								echo '<td data-label="' . esc_attr__( 'Animal Name', 'farm-management' ) . '">' . esc_html( $animal ) . '</td>';
								echo '<td data-label="' . esc_attr__( 'Vaccine Name', 'farm-management' ) . '">' . esc_html( $vaccine ?: '—' ) . '</td>';
								echo '<td data-label="' . esc_attr__( 'Date Given', 'farm-management' ) . '">' . esc_html( $date_given ?: '—' ) . '</td>';
								echo '<td data-label="' . esc_attr__( 'Next Due Date', 'farm-management' ) . '">' . esc_html( $next_due ?: '—' ) . '</td>';
								echo '<td data-label="' . esc_attr__( 'Status', 'farm-management' ) . '"><span class="fmp-badge ' . esc_attr( $badge_class ) . '">' . esc_html( $status_label ) . '</span></td>';
								if ( $can_manage ) {
									echo '<td data-label="">';
									if ( $edit_link ) {
										echo '<a href="' . esc_url( $edit_link ) . '" class="fmp-btn fmp-btn-secondary fmp-btn-sm">' . esc_html__( 'Edit', 'farm-management' ) . '</a>';
									}
									echo '</td>';
								}
								echo '</tr>';
							}
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
		$content = ob_get_clean();
		return self::portal_wrap( __( 'Vaccinations', 'farm-management' ), __( 'All vaccination records.', 'farm-management' ), 'vaccinations', $content );
	}

	/**
	 * [fmp_farm-dashboard] – frontend dashboard matching admin: welcome, Quick Add, stat cards, Overdue/Due Soon vaccinations, Tasks Due Soon, Low Stock.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string
	 */
	public static function render_farm_dashboard( $atts ) {
		$guard = self::fmp_require_login();
		if ( $guard !== true ) {
			return $guard;
		}

		$can_manage = current_user_can( FMP_Capabilities::MANAGE_FARM );
		$animal_counts    = FMP_Dashboard::get_post_type_counts( 'fmp_animal' );
		$task_counts      = FMP_Dashboard::get_post_type_counts( 'fmp_task' );
		$inventory_counts = FMP_Dashboard::get_post_type_counts( 'fmp_inventory_item' );
		$expense_counts   = FMP_Dashboard::get_post_type_counts( 'fmp_expense' );
		$vacc_counts      = FMP_Dashboard::get_post_type_counts( 'fmp_vaccination' );
		$overdue_vaccinations  = FMP_Dashboard::get_overdue_vaccinations();
		$vaccinations_due_soon  = FMP_Dashboard::get_vaccinations_due_soon();
		$tasks_due_soon        = FMP_Dashboard::get_tasks_due_soon();
		$low_stock_items       = FMP_Dashboard::get_low_stock_items();
		$due_soon_days = (int) FMP_Settings::get( FMP_Settings::KEY_DUE_SOON_DAYS );
		if ( $due_soon_days < 1 ) {
			$due_soon_days = (int) FMP_Settings::get( FMP_Settings::KEY_VACCINATION_DAYS );
		}
		$due_soon_days = $due_soon_days >= 1 ? $due_soon_days : 14;

		// Quick Add: use front-end portal add pages when set up, else wp-admin for managers.
		$portal_pages = class_exists( 'FMP_Portal' ) ? get_option( FMP_Portal::OPTION_PAGES, array() ) : array();
		$use_portal_add = ! empty( $portal_pages['add_animal'] );
		if ( $use_portal_add ) {
			$add_animal_url     = FMP_Portal::get_add_url( 'add_animal' );
			$add_crop_url       = FMP_Portal::get_add_url( 'add_crop' );
			$add_task_url       = FMP_Portal::get_add_url( 'add_task' );
			$add_inventory_url  = FMP_Portal::get_add_url( 'add_inventory' );
			$add_expense_url    = FMP_Portal::get_add_url( 'add_expense' );
			$add_vaccination_url = FMP_Portal::get_add_url( 'add_vaccination' );
		} else {
			$add_animal_url     = $can_manage ? admin_url( 'admin.php?page=fmp-animals&action=new' ) : '';
			$add_crop_url       = $can_manage ? admin_url( 'admin.php?page=fmp-crops&action=new' ) : '';
			$add_task_url       = $can_manage ? admin_url( 'admin.php?page=fmp-tasks&action=new' ) : '';
			$add_inventory_url  = $can_manage ? admin_url( 'admin.php?page=fmp-inventory&action=new' ) : '';
			$add_expense_url    = $can_manage ? admin_url( 'admin.php?page=fmp-expenses&action=new' ) : '';
			$add_vaccination_url = $can_manage ? admin_url( 'admin.php?page=fmp-vaccinations&action=new' ) : '';
		}
		$edit_animals      = $can_manage ? admin_url( 'admin.php?page=fmp-animals' ) : '';
		$edit_tasks        = $can_manage ? admin_url( 'admin.php?page=fmp-tasks' ) : '';
		$edit_inventory    = $can_manage ? admin_url( 'admin.php?page=fmp-inventory' ) : '';
		$edit_expenses     = $can_manage ? admin_url( 'admin.php?page=fmp-expenses' ) : '';
		$edit_vaccinations = $can_manage ? admin_url( 'admin.php?page=fmp-vaccinations' ) : '';

		// When portal add pages exist, Edit links on the dashboard go to front-end edit forms (?id=) instead of wp-admin.
		$portal_edit_url = function( $record_type, $post_id ) use ( $use_portal_add ) {
			if ( ! $use_portal_add || ! class_exists( 'FMP_Portal' ) ) {
				return '';
			}
			return FMP_Portal::get_edit_url( $record_type, $post_id );
		};

		ob_start();
		?>
		<div class="fmp-frontend-dashboard">
			<?php
			if ( isset( $_GET['fmp_success'] ) && $_GET['fmp_success'] === '1' ) {
				echo '<div class="fmp-portal-notice fmp-portal-notice-success">' . esc_html__( 'Saved successfully.', 'farm-management' ) . '</div>';
			}
			if ( isset( $_GET['fmp_error'] ) && $_GET['fmp_error'] === '1' ) {
				echo '<div class="fmp-portal-notice fmp-portal-notice-error">' . esc_html__( 'Something went wrong. Please try again.', 'farm-management' ) . '</div>';
			}
			?>
			<?php if ( ( $use_portal_add || $can_manage ) && ( $add_animal_url || $add_vaccination_url ) ) : ?>
				<div class="fmp-portal-quick-add fmp-dashboard-quick-add">
					<h2 class="fmp-portal-section-title fmp-dashboard-section-title"><?php esc_html_e( 'Quick Add', 'farm-management' ); ?></h2>
					<div class="fmp-portal-quick-add-buttons fmp-dashboard-quick-add-buttons">
						<a href="<?php echo esc_url( $add_animal_url ); ?>" class="fmp-btn fmp-btn-primary fmp-dashboard-btn"><?php esc_html_e( 'Add Animal', 'farm-management' ); ?></a>
						<a href="<?php echo esc_url( $add_crop_url ); ?>" class="fmp-btn fmp-btn-primary fmp-dashboard-btn"><?php esc_html_e( 'Add Crop', 'farm-management' ); ?></a>
						<a href="<?php echo esc_url( $add_task_url ); ?>" class="fmp-btn fmp-btn-primary fmp-dashboard-btn"><?php esc_html_e( 'Add Task', 'farm-management' ); ?></a>
						<a href="<?php echo esc_url( $add_inventory_url ); ?>" class="fmp-btn fmp-btn-primary fmp-dashboard-btn"><?php esc_html_e( 'Add Inventory', 'farm-management' ); ?></a>
						<a href="<?php echo esc_url( $add_expense_url ); ?>" class="fmp-btn fmp-btn-primary fmp-dashboard-btn"><?php esc_html_e( 'Add Expense', 'farm-management' ); ?></a>
						<a href="<?php echo esc_url( $add_vaccination_url ); ?>" class="fmp-btn fmp-btn-primary fmp-dashboard-btn"><?php esc_html_e( 'Add Vaccination', 'farm-management' ); ?></a>
					</div>
				</div>
			<?php endif; ?>

			<div class="fmp-portal-cards fmp-dashboard-cards">
				<div class="fmp-portal-card fmp-dashboard-card fmp-dashboard-card-animals">
					<span class="fmp-portal-card-icon" aria-hidden="true">&#128046;</span>
					<span class="fmp-portal-card-value fmp-dashboard-card-value"><?php echo absint( $animal_counts['publish'] ); ?></span>
					<span class="fmp-portal-card-label fmp-dashboard-card-label"><?php esc_html_e( 'Animals', 'farm-management' ); ?></span>
					<?php if ( $edit_animals ) : ?><a href="<?php echo esc_url( $edit_animals ); ?>" class="fmp-portal-card-link fmp-dashboard-card-link"><?php esc_html_e( 'View all', 'farm-management' ); ?></a><?php endif; ?>
				</div>
				<div class="fmp-portal-card fmp-dashboard-card fmp-dashboard-card-tasks">
					<span class="fmp-portal-card-icon" aria-hidden="true">&#128203;</span>
					<span class="fmp-portal-card-value fmp-dashboard-card-value"><?php echo absint( $task_counts['publish'] ); ?></span>
					<span class="fmp-portal-card-label fmp-dashboard-card-label"><?php esc_html_e( 'Tasks', 'farm-management' ); ?></span>
					<?php if ( $edit_tasks ) : ?><a href="<?php echo esc_url( $edit_tasks ); ?>" class="fmp-portal-card-link fmp-dashboard-card-link"><?php esc_html_e( 'View all', 'farm-management' ); ?></a><?php endif; ?>
				</div>
				<div class="fmp-portal-card fmp-dashboard-card fmp-dashboard-card-inventory">
					<span class="fmp-portal-card-icon" aria-hidden="true">&#128230;</span>
					<span class="fmp-portal-card-value fmp-dashboard-card-value"><?php echo absint( $inventory_counts['publish'] ); ?></span>
					<span class="fmp-portal-card-label fmp-dashboard-card-label"><?php esc_html_e( 'Inventory', 'farm-management' ); ?></span>
					<?php if ( $edit_inventory ) : ?><a href="<?php echo esc_url( $edit_inventory ); ?>" class="fmp-portal-card-link fmp-dashboard-card-link"><?php esc_html_e( 'View all', 'farm-management' ); ?></a><?php endif; ?>
				</div>
				<div class="fmp-portal-card fmp-dashboard-card fmp-dashboard-card-expenses">
					<span class="fmp-portal-card-icon" aria-hidden="true">&#128176;</span>
					<span class="fmp-portal-card-value fmp-dashboard-card-value"><?php echo absint( $expense_counts['publish'] ); ?></span>
					<span class="fmp-portal-card-label fmp-dashboard-card-label"><?php esc_html_e( 'Expenses', 'farm-management' ); ?></span>
					<?php if ( $edit_expenses ) : ?><a href="<?php echo esc_url( $edit_expenses ); ?>" class="fmp-portal-card-link fmp-dashboard-card-link"><?php esc_html_e( 'View all', 'farm-management' ); ?></a><?php endif; ?>
				</div>
				<div class="fmp-portal-card fmp-dashboard-card fmp-dashboard-card-vaccinations">
					<span class="fmp-portal-card-icon" aria-hidden="true">&#128137;</span>
					<span class="fmp-portal-card-value fmp-dashboard-card-value"><?php echo absint( $vacc_counts['publish'] ); ?></span>
					<span class="fmp-portal-card-label fmp-dashboard-card-label"><?php esc_html_e( 'Vaccinations', 'farm-management' ); ?></span>
					<?php if ( $edit_vaccinations ) : ?><a href="<?php echo esc_url( $edit_vaccinations ); ?>" class="fmp-portal-card-link fmp-dashboard-card-link"><?php esc_html_e( 'View all', 'farm-management' ); ?></a><?php endif; ?>
				</div>
			</div>

			<div class="fmp-dashboard-widgets">
				<div class="fmp-dashboard-widget">
					<h2 class="fmp-dashboard-widget-title"><?php esc_html_e( 'Overdue Vaccinations', 'farm-management' ); ?></h2>
					<?php if ( ! empty( $overdue_vaccinations ) ) : ?>
						<div class="fmp-shortcode fmp-table-wrap">
							<table class="fmp-table fmp-table-responsive">
								<thead><tr><th><?php esc_html_e( 'Animal', 'farm-management' ); ?></th><th><?php esc_html_e( 'Vaccine', 'farm-management' ); ?></th><th><?php esc_html_e( 'Next due', 'farm-management' ); ?></th><?php if ( $can_manage ) : ?><th></th><?php endif; ?></tr></thead>
								<tbody>
									<?php foreach ( $overdue_vaccinations as $v ) : ?>
										<?php
										$animal_id   = (int) get_post_meta( $v->ID, '_fmp_animal_id', true );
										$animal_name = self::get_animal_name_by_id( $animal_id );
										$vaccine     = get_post_meta( $v->ID, '_fmp_vaccine_name', true );
										$next_due    = get_post_meta( $v->ID, '_fmp_next_due_date', true );
										$edit_link   = $can_manage ? ( $portal_edit_url( 'vaccination', $v->ID ) ?: get_edit_post_link( $v->ID ) ) : '';
										?>
										<tr>
											<td><?php echo esc_html( $animal_name ); ?></td>
											<td><?php echo esc_html( $vaccine ?: '—' ); ?></td>
											<td><span class="fmp-badge fmp-badge-overdue"><?php echo esc_html( $next_due ?: '—' ); ?></span></td>
											<?php if ( $can_manage && $edit_link ) : ?><td><a href="<?php echo esc_url( $edit_link ); ?>" class="fmp-btn fmp-btn-secondary fmp-btn-sm"><?php esc_html_e( 'Edit', 'farm-management' ); ?></a></td><?php endif; ?>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<?php if ( $edit_vaccinations ) : ?><p class="fmp-dashboard-view-all"><a href="<?php echo esc_url( $edit_vaccinations ); ?>?filter=overdue" class="fmp-portal-card-link"><?php esc_html_e( 'View all', 'farm-management' ); ?></a></p><?php endif; ?>
					<?php else : ?>
						<div class="fmp-portal-empty fmp-dashboard-empty"><p><?php esc_html_e( 'No overdue vaccinations.', 'farm-management' ); ?></p>
						<?php if ( $edit_vaccinations ) : ?><p class="fmp-dashboard-view-all"><a href="<?php echo esc_url( $edit_vaccinations ); ?>" class="fmp-portal-card-link"><?php esc_html_e( 'View all vaccinations', 'farm-management' ); ?></a></p><?php endif; ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="fmp-dashboard-widget">
					<h2 class="fmp-dashboard-widget-title"><?php echo esc_html( sprintf( /* translators: %d: number of days */ _n( 'Vaccinations Due Soon (next %d day)', 'Vaccinations Due Soon (next %d days)', $due_soon_days, 'farm-management' ), $due_soon_days ) ); ?></h2>
					<?php if ( ! empty( $vaccinations_due_soon ) ) : ?>
						<div class="fmp-shortcode fmp-table-wrap">
							<table class="fmp-table">
								<thead><tr><th><?php esc_html_e( 'Animal', 'farm-management' ); ?></th><th><?php esc_html_e( 'Vaccine', 'farm-management' ); ?></th><th><?php esc_html_e( 'Next due', 'farm-management' ); ?></th><?php if ( $can_manage ) : ?><th></th><?php endif; ?></tr></thead>
								<tbody>
									<?php foreach ( $vaccinations_due_soon as $v ) : ?>
										<?php
										$animal_id   = (int) get_post_meta( $v->ID, '_fmp_animal_id', true );
										$animal_name = self::get_animal_name_by_id( $animal_id );
										$vaccine     = get_post_meta( $v->ID, '_fmp_vaccine_name', true );
										$next_due    = get_post_meta( $v->ID, '_fmp_next_due_date', true );
										$edit_link   = $can_manage ? ( $portal_edit_url( 'vaccination', $v->ID ) ?: get_edit_post_link( $v->ID ) ) : '';
										?>
										<tr>
											<td><?php echo esc_html( $animal_name ); ?></td>
											<td><?php echo esc_html( $vaccine ?: '—' ); ?></td>
											<td><?php echo esc_html( $next_due ?: '—' ); ?></td>
											<?php if ( $can_manage && $edit_link ) : ?><td><a href="<?php echo esc_url( $edit_link ); ?>" class="fmp-btn fmp-btn-secondary fmp-btn-sm"><?php esc_html_e( 'Edit', 'farm-management' ); ?></a></td><?php endif; ?>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<?php if ( $edit_vaccinations ) : ?><p class="fmp-dashboard-view-all"><a href="<?php echo esc_url( $edit_vaccinations ); ?>?filter=due_soon" class="fmp-portal-card-link"><?php esc_html_e( 'View all', 'farm-management' ); ?></a></p><?php endif; ?>
					<?php else : ?>
						<div class="fmp-portal-empty fmp-dashboard-empty"><p><?php echo esc_html( sprintf( /* translators: %d: number of days */ _n( 'No vaccinations due in the next %d day.', 'No vaccinations due in the next %d days.', $due_soon_days, 'farm-management' ), $due_soon_days ) ); ?></p>
						<?php if ( $edit_vaccinations ) : ?><p class="fmp-dashboard-view-all"><a href="<?php echo esc_url( $edit_vaccinations ); ?>" class="fmp-portal-card-link"><?php esc_html_e( 'View all vaccinations', 'farm-management' ); ?></a></p><?php endif; ?></div>
					<?php endif; ?>
				</div>

				<?php if ( post_type_exists( 'fmp_task' ) ) : ?>
					<div class="fmp-dashboard-widget">
						<h2 class="fmp-dashboard-widget-title"><?php esc_html_e( 'Tasks Due Soon (next 7 days)', 'farm-management' ); ?></h2>
						<?php if ( ! empty( $tasks_due_soon ) ) : ?>
							<div class="fmp-shortcode fmp-table-wrap">
								<table class="fmp-table">
									<thead><tr><th><?php esc_html_e( 'Task', 'farm-management' ); ?></th><th><?php esc_html_e( 'Due date', 'farm-management' ); ?></th><th><?php esc_html_e( 'Status', 'farm-management' ); ?></th><?php if ( $can_manage ) : ?><th></th><?php endif; ?></tr></thead>
									<tbody>
										<?php foreach ( $tasks_due_soon as $t ) : ?>
											<?php
											$due_date  = get_post_meta( $t->ID, '_fmp_due_date', true );
											$status    = get_post_meta( $t->ID, '_fmp_status', true );
											$edit_link = $can_manage ? ( $portal_edit_url( 'task', $t->ID ) ?: get_edit_post_link( $t->ID ) ) : '';
											?>
											<tr>
												<td><?php echo esc_html( get_the_title( $t->ID ) ); ?></td>
												<td><?php echo esc_html( $due_date ?: '—' ); ?></td>
												<td><?php echo esc_html( $status ?: '—' ); ?></td>
												<?php if ( $can_manage && $edit_link ) : ?><td><a href="<?php echo esc_url( $edit_link ); ?>" class="fmp-btn fmp-btn-secondary fmp-btn-sm"><?php esc_html_e( 'Edit', 'farm-management' ); ?></a></td><?php endif; ?>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
							<?php if ( $edit_tasks ) : ?><p class="fmp-dashboard-view-all"><a href="<?php echo esc_url( $edit_tasks ); ?>?filter=due_soon" class="fmp-portal-card-link"><?php esc_html_e( 'View all', 'farm-management' ); ?></a></p><?php endif; ?>
						<?php else : ?>
							<div class="fmp-portal-empty fmp-dashboard-empty"><p><?php esc_html_e( 'No tasks due in the next 7 days.', 'farm-management' ); ?></p>
							<?php if ( $edit_tasks ) : ?><p class="fmp-dashboard-view-all"><a href="<?php echo esc_url( $edit_tasks ); ?>" class="fmp-portal-card-link"><?php esc_html_e( 'View all tasks', 'farm-management' ); ?></a></p><?php endif; ?></div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( post_type_exists( 'fmp_inventory_item' ) ) : ?>
					<div class="fmp-dashboard-widget">
						<h2 class="fmp-dashboard-widget-title"><?php esc_html_e( 'Low Stock Items', 'farm-management' ); ?></h2>
						<?php if ( ! empty( $low_stock_items ) ) : ?>
							<div class="fmp-shortcode fmp-table-wrap">
								<table class="fmp-table">
									<thead><tr><th><?php esc_html_e( 'Item', 'farm-management' ); ?></th><th><?php esc_html_e( 'Quantity', 'farm-management' ); ?></th><th><?php esc_html_e( 'Reorder level', 'farm-management' ); ?></th><?php if ( $can_manage ) : ?><th></th><?php endif; ?></tr></thead>
									<tbody>
										<?php foreach ( $low_stock_items as $item ) : ?>
											<?php
											$quantity   = get_post_meta( $item->ID, '_fmp_quantity', true );
											$reorder    = get_post_meta( $item->ID, '_fmp_reorder_level', true );
											$edit_url   = $can_manage ? ( $portal_edit_url( 'inventory', $item->ID ) ?: admin_url( 'admin.php?page=fmp-inventory&action=edit&id=' . $item->ID ) ) : '';
											?>
											<tr>
												<td><?php echo esc_html( get_the_title( $item->ID ) ); ?></td>
												<td><span class="fmp-badge fmp-badge-overdue"><?php echo esc_html( $quantity ); ?></span></td>
												<td><?php echo esc_html( $reorder ?: '—' ); ?></td>
												<?php if ( $can_manage && $edit_url ) : ?><td><a href="<?php echo esc_url( $edit_url ); ?>" class="fmp-btn fmp-btn-secondary fmp-btn-sm"><?php esc_html_e( 'Edit', 'farm-management' ); ?></a></td><?php endif; ?>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
							<?php if ( $edit_inventory ) : ?><p class="fmp-dashboard-view-all"><a href="<?php echo esc_url( $edit_inventory ); ?>?filter=low_stock" class="fmp-portal-card-link"><?php esc_html_e( 'View all', 'farm-management' ); ?></a></p><?php endif; ?>
						<?php else : ?>
							<div class="fmp-portal-empty fmp-dashboard-empty"><p><?php esc_html_e( 'No low stock items.', 'farm-management' ); ?></p>
							<?php if ( $edit_inventory ) : ?><p class="fmp-dashboard-view-all"><a href="<?php echo esc_url( $edit_inventory ); ?>" class="fmp-portal-card-link"><?php esc_html_e( 'View all inventory', 'farm-management' ); ?></a></p><?php endif; ?></div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		$content = ob_get_clean();
		return self::portal_wrap( __( 'Farm Management', 'farm-management' ), __( 'Welcome to your farm operations dashboard.', 'farm-management' ), 'dashboard', $content );
	}

	/**
	 * [fmp_crops] – table of all crops (logged-in users only).
	 *
	 * Columns: Crop Name, Type, Location, Planting Date, Harvest Date, Status.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string
	 */
	public static function render_crops( $atts ) {
		$query = new WP_Query( array(
			'post_type'      => 'fmp_crop',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		$crops = $query->posts;

		ob_start();
		?>
		<div class="fmp-shortcode fmp-crops">
			<div class="fmp-table-wrap">
				<table class="fmp-table fmp-table-crops fmp-table-responsive">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Crop Name', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Type', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Location', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Planting Date', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Harvest Date', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Status', 'farm-management' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						if ( empty( $crops ) ) {
							echo '<tr><td colspan="6">' . esc_html__( 'No crops found.', 'farm-management' ) . '</td></tr>';
						} else {
							foreach ( $crops as $post ) {
								$name     = get_post_meta( $post->ID, '_fmp_crop_name', true );
								$type     = get_post_meta( $post->ID, '_fmp_crop_type', true );
								$location = get_post_meta( $post->ID, '_fmp_field_location', true );
								$planting  = get_post_meta( $post->ID, '_fmp_planting_date', true );
								$harvest   = get_post_meta( $post->ID, '_fmp_expected_harvest_date', true );
								$status    = get_post_meta( $post->ID, '_fmp_crop_status', true );
								$name      = $name !== '' ? $name : $post->post_title;
								echo '<tr>';
								echo '<td data-label="' . esc_attr__( 'Crop Name', 'farm-management' ) . '">' . esc_html( $name ?: '—' ) . '</td>';
								echo '<td data-label="' . esc_attr__( 'Type', 'farm-management' ) . '">' . esc_html( $type ?: '—' ) . '</td>';
								echo '<td data-label="' . esc_attr__( 'Location', 'farm-management' ) . '">' . esc_html( $location ?: '—' ) . '</td>';
								echo '<td data-label="' . esc_attr__( 'Planting Date', 'farm-management' ) . '">' . esc_html( $planting ?: '—' ) . '</td>';
								echo '<td data-label="' . esc_attr__( 'Harvest Date', 'farm-management' ) . '">' . esc_html( $harvest ?: '—' ) . '</td>';
								echo '<td data-label="' . esc_attr__( 'Status', 'farm-management' ) . '">' . esc_html( $status ?: '—' ) . '</td>';
								echo '</tr>';
							}
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
		$content = ob_get_clean();
		return self::portal_wrap( __( 'Crops', 'farm-management' ), __( 'All crops.', 'farm-management' ), 'crops', $content );
	}

	/**
	 * [fmp_reports] – frontend reports: vaccinations due, animals by species & status, monthly expenses (logged-in users only).
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string
	 */
	public static function render_reports( $atts ) {
		$guard = self::fmp_require_login();
		if ( $guard !== true ) {
			return $guard;
		}

		$due_soon_days = (int) FMP_Settings::get( FMP_Settings::KEY_DUE_SOON_DAYS );
		if ( $due_soon_days < 1 ) {
			$due_soon_days = (int) FMP_Settings::get( FMP_Settings::KEY_VACCINATION_DAYS );
		}
		$due_soon_days = $due_soon_days >= 1 ? $due_soon_days : 14;

		$vaccinations_due = FMP_Reports::get_vaccinations_due_including_overdue();
		$animals_species_status = FMP_Reports::get_animals_by_species_and_status();
		$expenses_by_cat = FMP_Reports::get_expenses_by_category_this_month();
		$report_month = (int) gmdate( 'n' );
		$report_year = (int) gmdate( 'Y' );
		$months = array(
			1  => __( 'January', 'farm-management' ),
			2  => __( 'February', 'farm-management' ),
			3  => __( 'March', 'farm-management' ),
			4  => __( 'April', 'farm-management' ),
			5  => __( 'May', 'farm-management' ),
			6  => __( 'June', 'farm-management' ),
			7  => __( 'July', 'farm-management' ),
			8  => __( 'August', 'farm-management' ),
			9  => __( 'September', 'farm-management' ),
			10 => __( 'October', 'farm-management' ),
			11 => __( 'November', 'farm-management' ),
			12 => __( 'December', 'farm-management' ),
		);
		$reports_admin_url = current_user_can( FMP_Capabilities::MANAGE_FARM ) ? admin_url( 'admin.php?page=fmp-reports' ) : '';

		ob_start();
		?>
		<div class="fmp-frontend-reports">
			<?php if ( $reports_admin_url ) : ?>
				<p class="fmp-reports-admin-link"><a href="<?php echo esc_url( $reports_admin_url ); ?>"><?php esc_html_e( 'View full reports &amp; export CSV in admin', 'farm-management' ); ?></a></p>
			<?php endif; ?>

			<div class="fmp-dashboard-widgets">
				<div class="fmp-dashboard-widget">
					<h2 class="fmp-dashboard-widget-title"><?php echo esc_html( sprintf( /* translators: %d: number of days */ _n( 'Vaccinations due in next %d day (including overdue)', 'Vaccinations due in next %d days (including overdue)', $due_soon_days, 'farm-management' ), $due_soon_days ) ); ?></h2>
					<p class="fmp-report-desc"><?php esc_html_e( 'Animal tag/name, vaccine, next due date, status, and location.', 'farm-management' ); ?></p>
					<div class="fmp-shortcode">
						<table class="fmp-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Animal Tag/Name', 'farm-management' ); ?></th>
									<th><?php esc_html_e( 'Vaccine', 'farm-management' ); ?></th>
									<th><?php esc_html_e( 'Next due date', 'farm-management' ); ?></th>
									<th><?php esc_html_e( 'Status', 'farm-management' ); ?></th>
									<th><?php esc_html_e( 'Location', 'farm-management' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( ! empty( $vaccinations_due ) ) : ?>
									<?php foreach ( $vaccinations_due as $v ) : ?>
										<?php
										$animal_id   = (int) get_post_meta( $v->ID, '_fmp_animal_id', true );
										$animal_name = self::get_animal_name_by_id( $animal_id );
										$vaccine     = get_post_meta( $v->ID, '_fmp_vaccine_name', true );
										$next_due    = get_post_meta( $v->ID, '_fmp_next_due_date', true );
										$status      = FMP_Vaccinations::get_vaccination_status( $next_due );
										$badge_class  = ( $status === 'overdue' ) ? 'fmp-badge-overdue' : ( ( $status === 'due_soon' ) ? 'fmp-badge-due-soon' : 'fmp-badge-ok' );
										$status_label = ( $status === 'overdue' ) ? __( 'Overdue', 'farm-management' ) : ( ( $status === 'due_soon' ) ? __( 'Due Soon', 'farm-management' ) : __( 'OK', 'farm-management' ) );
										$location = get_post_meta( $v->ID, '_fmp_vaccination_location', true );
										?>
										<tr>
											<td><?php echo esc_html( $animal_name ); ?></td>
											<td><?php echo esc_html( $vaccine ?: '—' ); ?></td>
											<td><?php echo esc_html( $next_due ?: '—' ); ?></td>
											<td><span class="fmp-badge <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
											<td><?php echo esc_html( $location ?: '—' ); ?></td>
										</tr>
									<?php endforeach; ?>
								<?php else : ?>
									<tr><td colspan="5"><?php echo esc_html( sprintf( /* translators: %d: number of days */ _n( 'No vaccinations due or overdue in the next %d day.', 'No vaccinations due or overdue in the next %d days.', $due_soon_days, 'farm-management' ), $due_soon_days ) ); ?></td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>

				<div class="fmp-dashboard-widget">
					<h2 class="fmp-dashboard-widget-title"><?php esc_html_e( 'Animals by species &amp; status', 'farm-management' ); ?></h2>
					<p class="fmp-dashboard-empty fmp-report-desc"><?php esc_html_e( 'Count of animals grouped by species and status (Alive / Sold / Dead).', 'farm-management' ); ?></p>
					<div class="fmp-shortcode">
						<table class="fmp-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Species', 'farm-management' ); ?></th>
									<th><?php esc_html_e( 'Status', 'farm-management' ); ?></th>
									<th><?php esc_html_e( 'Count', 'farm-management' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( ! empty( $animals_species_status ) ) : ?>
									<?php foreach ( $animals_species_status as $row ) : ?>
										<tr>
											<td><?php echo esc_html( $row['species'] ); ?></td>
											<td><?php echo esc_html( $row['status'] ); ?></td>
											<td><?php echo esc_html( (string) $row['count'] ); ?></td>
										</tr>
									<?php endforeach; ?>
								<?php else : ?>
									<tr><td colspan="3"><?php esc_html_e( 'No data.', 'farm-management' ); ?></td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>

				<div class="fmp-dashboard-widget">
					<h2 class="fmp-dashboard-widget-title"><?php esc_html_e( 'Monthly expenses summary', 'farm-management' ); ?></h2>
					<p class="fmp-report-desc"><?php echo esc_html( sprintf( __( 'Expenses by category for %s %d (ZAR).', 'farm-management' ), $months[ $report_month ], $report_year ) ); ?></p>
					<div class="fmp-shortcode">
						<table class="fmp-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Category', 'farm-management' ); ?></th>
									<th><?php esc_html_e( 'Total (ZAR)', 'farm-management' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( ! empty( $expenses_by_cat ) ) : ?>
									<?php foreach ( $expenses_by_cat as $row ) : ?>
										<tr>
											<td><?php echo esc_html( $row['category'] ); ?></td>
											<td>R <?php echo esc_html( number_format_i18n( $row['total'], 2 ) ); ?></td>
										</tr>
									<?php endforeach; ?>
								<?php else : ?>
									<tr><td colspan="2"><?php echo esc_html( sprintf( __( 'No expenses for %s %d.', 'farm-management' ), $months[ $report_month ], $report_year ) ); ?></td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<?php
		$content = ob_get_clean();
		return self::portal_wrap( __( 'Reports', 'farm-management' ), __( 'Vaccinations due, animals by species &amp; status, monthly expenses.', 'farm-management' ), 'reports', $content );
	}

	/**
	 * [fmp_contact] – farm contact info from settings + contact form. Public, no login required.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string
	 */
	public static function render_contact( $atts ) {
		$address = (string) FMP_Settings::get( FMP_Settings::KEY_CONTACT_ADDRESS );
		$phone   = (string) FMP_Settings::get( FMP_Settings::KEY_CONTACT_PHONE );
		$email   = (string) FMP_Settings::get( FMP_Settings::KEY_CONTACT_EMAIL );
		$address = trim( $address );
		$phone   = trim( $phone );
		$email   = trim( $email );

		$form_sent  = false;
		$form_error = '';
		if ( isset( $_POST['fmp_contact_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_contact_nonce'] ) ), self::CONTACT_FORM_NONCE_ACTION ) ) {
			$name    = isset( $_POST['fmp_contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_contact_name'] ) ) : '';
			$sender  = isset( $_POST['fmp_contact_email'] ) ? sanitize_email( wp_unslash( $_POST['fmp_contact_email'] ) ) : '';
			$subject = isset( $_POST['fmp_contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_contact_subject'] ) ) : '';
			$message = isset( $_POST['fmp_contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_contact_message'] ) ) : '';
			$name    = trim( $name );
			$sender  = trim( $sender );
			$subject = trim( $subject );
			$message = trim( $message );

			if ( $name === '' || $sender === '' || $message === '' ) {
				$form_error = __( 'Please fill in your name, email, and message.', 'farm-management' );
			} elseif ( ! is_email( $sender ) ) {
				$form_error = __( 'Please enter a valid email address.', 'farm-management' );
			} else {
				$to      = $email !== '' ? $email : get_option( 'admin_email' );
				$subj   = $subject !== '' ? $subject : sprintf( __( 'Contact from %s', 'farm-management' ), get_bloginfo( 'name' ) );
				$body   = sprintf( __( 'Name: %s', 'farm-management' ), $name ) . "\n";
				$body  .= sprintf( __( 'Email: %s', 'farm-management' ), $sender ) . "\n\n";
				$body  .= $message;
				$headers = array(
					'Content-Type: text/plain; charset=UTF-8',
					'Reply-To: ' . $name . ' <' . $sender . '>',
				);
				$sent = wp_mail( $to, $subj, $body, $headers );
				if ( $sent ) {
					$form_sent = true;
				} else {
					$form_error = __( 'Your message could not be sent. Please try again or use the email address above.', 'farm-management' );
				}
			}
		}

		$home_url = home_url( '/' );
		$front_id = (int) get_option( 'page_on_front' );
		if ( $front_id ) {
			$home_url = get_permalink( $front_id );
		}
		if ( ! $home_url ) {
			$home_url = home_url( '/' );
		}

		ob_start();
		?>
		<p class="fmp-contact-back">
			<a href="<?php echo esc_url( $home_url ); ?>" class="fmp-btn"><?php esc_html_e( '&larr; Back to Home', 'farm-management' ); ?></a>
		</p>
		<?php
		if ( $address !== '' || $phone !== '' || $email !== '' ) {
			?>
			<div class="fmp-contact">
				<?php if ( $address !== '' ) : ?>
					<div class="fmp-contact-row fmp-contact-address">
						<strong class="fmp-contact-label"><?php esc_html_e( 'Address', 'farm-management' ); ?></strong>
						<span class="fmp-contact-value"><?php echo nl2br( esc_html( $address ) ); ?></span>
					</div>
				<?php endif; ?>
				<?php if ( $phone !== '' ) : ?>
					<div class="fmp-contact-row fmp-contact-phone">
						<strong class="fmp-contact-label"><?php esc_html_e( 'Phone', 'farm-management' ); ?></strong>
						<span class="fmp-contact-value"><a href="<?php echo esc_url( 'tel:' . preg_replace( '/\s+/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></span>
					</div>
				<?php endif; ?>
				<?php if ( $email !== '' ) : ?>
					<div class="fmp-contact-row fmp-contact-email">
						<strong class="fmp-contact-label"><?php esc_html_e( 'Email', 'farm-management' ); ?></strong>
						<span class="fmp-contact-value"><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></span>
					</div>
				<?php endif; ?>
			</div>
			<?php
		} elseif ( ! $form_sent ) {
			?>
			<p class="fmp-contact-empty"><?php esc_html_e( 'Contact details have not been set. You can still send a message using the form below; it will go to the site administrator.', 'farm-management' ); ?></p>
			<?php
		}

		if ( $form_sent ) {
			?>
			<div class="fmp-contact-form-success">
				<p><?php esc_html_e( 'Thank you. Your message has been sent.', 'farm-management' ); ?></p>
			</div>
			<p class="fmp-contact-actions">
				<a href="<?php echo esc_url( $home_url ); ?>" class="fmp-btn"><?php esc_html_e( '&larr; Back to Home', 'farm-management' ); ?></a>
			</p>
			<?php
		} else {
			?>
			<div class="fmp-contact-form-wrap">
				<h3 class="fmp-contact-form-title"><?php esc_html_e( 'Send a message', 'farm-management' ); ?></h3>
				<?php if ( $form_error !== '' ) : ?>
					<p class="fmp-contact-form-error"><?php echo esc_html( $form_error ); ?></p>
				<?php endif; ?>
				<form class="fmp-contact-form" method="post" action="">
					<?php wp_nonce_field( self::CONTACT_FORM_NONCE_ACTION, 'fmp_contact_nonce' ); ?>
					<p class="fmp-contact-form-field">
						<label for="fmp_contact_name"><?php esc_html_e( 'Your name', 'farm-management' ); ?> <span class="required">*</span></label>
						<input type="text" id="fmp_contact_name" name="fmp_contact_name" value="<?php echo esc_attr( isset( $_POST['fmp_contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_contact_name'] ) ) : '' ); ?>" required="required" class="fmp-contact-input" />
					</p>
					<p class="fmp-contact-form-field">
						<label for="fmp_contact_email"><?php esc_html_e( 'Your email', 'farm-management' ); ?> <span class="required">*</span></label>
						<input type="email" id="fmp_contact_email" name="fmp_contact_email" value="<?php echo esc_attr( isset( $_POST['fmp_contact_email'] ) ? sanitize_email( wp_unslash( $_POST['fmp_contact_email'] ) ) : '' ); ?>" required="required" class="fmp-contact-input" />
					</p>
					<p class="fmp-contact-form-field">
						<label for="fmp_contact_subject"><?php esc_html_e( 'Subject', 'farm-management' ); ?></label>
						<input type="text" id="fmp_contact_subject" name="fmp_contact_subject" value="<?php echo esc_attr( isset( $_POST['fmp_contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_contact_subject'] ) ) : '' ); ?>" class="fmp-contact-input" placeholder="<?php esc_attr_e( 'Optional', 'farm-management' ); ?>" />
					</p>
					<p class="fmp-contact-form-field">
						<label for="fmp_contact_message"><?php esc_html_e( 'Message', 'farm-management' ); ?> <span class="required">*</span></label>
						<textarea id="fmp_contact_message" name="fmp_contact_message" rows="5" required="required" class="fmp-contact-textarea"><?php echo esc_textarea( isset( $_POST['fmp_contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_contact_message'] ) ) : '' ); ?></textarea>
					</p>
					<p class="fmp-contact-form-submit">
						<button type="submit" class="fmp-contact-submit"><?php esc_html_e( 'Send message', 'farm-management' ); ?></button>
					</p>
				</form>
			</div>
			<?php
		}

		$content = ob_get_clean();
		/* Public Contact: same visual style as portal via .fmp-contact-page CSS; no portal tabs. */
		return '<div class="fmp-contact-page">' . $content . '</div>';
	}

	/**
	 * [fmp_support] – portal support form (logged-in). Same form logic as contact, different context: "Something's wrong / need help / report doesn't match".
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string
	 */
	public static function render_support( $atts ) {
		$email = (string) FMP_Settings::get( FMP_Settings::KEY_CONTACT_EMAIL );
		$email = trim( $email );
		$to_email = $email !== '' ? $email : get_option( 'admin_email' );

		$tabs = self::get_portal_tabs( 'support' );
		$dashboard_url = self::get_portal_home_url();
		foreach ( $tabs as $tab ) {
			if ( isset( $tab['key'] ) && $tab['key'] === 'dashboard' && ! empty( $tab['url'] ) ) {
				$dashboard_url = $tab['url'];
				break;
			}
		}

		$form_sent  = false;
		$form_error = '';
		$support_concern_options = array(
			''          => __( '— Select concern —', 'farm-management' ),
			'animals'   => __( 'Animals', 'farm-management' ),
			'vaccinations' => __( 'Vaccinations', 'farm-management' ),
			'crops'     => __( 'Crops', 'farm-management' ),
			'tasks'     => __( 'Tasks', 'farm-management' ),
			'inventory' => __( 'Inventory', 'farm-management' ),
			'expenses'  => __( 'Expenses', 'farm-management' ),
			'reports'   => __( 'Reports', 'farm-management' ),
			'other'     => __( 'Other', 'farm-management' ),
		);

		if ( isset( $_POST['fmp_support_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_support_nonce'] ) ), self::SUPPORT_FORM_NONCE_ACTION ) ) {
			$name    = isset( $_POST['fmp_support_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_support_name'] ) ) : '';
			$concern = isset( $_POST['fmp_support_concern'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_support_concern'] ) ) : '';
			$message = isset( $_POST['fmp_support_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_support_message'] ) ) : '';
			$name    = trim( $name );
			$message = trim( $message );
			$sender  = '';
			$user    = wp_get_current_user();
			if ( $user && $user->user_email ) {
				$sender = $user->user_email;
			}

			if ( $name === '' || $message === '' ) {
				$form_error = __( 'Please fill in your name and describe your concern.', 'farm-management' );
			} else {
				$concern_label = isset( $support_concern_options[ $concern ] ) ? $support_concern_options[ $concern ] : ( $concern !== '' ? $concern : __( 'Not specified', 'farm-management' ) );
				$subj = '[Farm Support] ' . $concern_label;
				$body = sprintf( __( 'Name: %s', 'farm-management' ), $name ) . "\n";
				$body .= sprintf( __( 'Concern: %s', 'farm-management' ), $concern_label ) . "\n";
				$body .= sprintf( __( 'User: %s (ID: %d)', 'farm-management' ), $user ? $user->user_login : '—', $user ? $user->ID : 0 ) . "\n";
				$body .= sprintf( __( 'Email: %s', 'farm-management' ), $sender ) . "\n\n";
				$body .= $message;
				$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
				if ( $sender ) {
					$headers[] = 'Reply-To: ' . $name . ' <' . $sender . '>';
				}
				$sent = wp_mail( $to_email, $subj, $body, $headers );
				if ( $sent ) {
					$form_sent = true;
				} else {
					$form_error = __( 'Your message could not be sent. Please try again.', 'farm-management' );
				}
			}
		}

		ob_start();
		if ( $form_sent ) {
			?>
			<div class="fmp-portal-card fmp-support-success-card">
				<div class="fmp-contact-form-success">
					<p><?php esc_html_e( 'Thank you. Your support request has been sent.', 'farm-management' ); ?></p>
				</div>
				<p class="fmp-support-actions">
					<a href="<?php echo esc_url( $dashboard_url ); ?>" class="fmp-btn fmp-btn-primary"><?php esc_html_e( '&larr; Back to Dashboard', 'farm-management' ); ?></a>
				</p>
			</div>
			<?php
		} else {
			?>
			<div class="fmp-portal-card fmp-portal-form-card fmp-support-form-card">
				<p class="fmp-support-back">
					<a href="<?php echo esc_url( $dashboard_url ); ?>" class="fmp-btn fmp-btn-secondary fmp-portal-back"><?php esc_html_e( '&larr; Back to Dashboard', 'farm-management' ); ?></a>
				</p>
				<div class="fmp-contact-form-wrap fmp-support-form-wrap">
					<h3 class="fmp-contact-form-title"><?php esc_html_e( 'Internal Support', 'farm-management' ); ?></h3>
					<p class="fmp-support-desc"><?php esc_html_e( 'Something went wrong on the farm or in the system? Tell us your name, what it’s about, and your concern. We’ll get back to you.', 'farm-management' ); ?></p>
					<?php if ( $form_error !== '' ) : ?>
						<p class="fmp-contact-form-error"><?php echo esc_html( $form_error ); ?></p>
					<?php endif; ?>
					<form class="fmp-contact-form fmp-support-form" method="post" action="">
						<?php wp_nonce_field( self::SUPPORT_FORM_NONCE_ACTION, 'fmp_support_nonce' ); ?>
						<p class="fmp-contact-form-field">
							<label for="fmp_support_name"><?php esc_html_e( 'Your name', 'farm-management' ); ?> <span class="required">*</span></label>
							<input type="text" id="fmp_support_name" name="fmp_support_name" value="<?php echo esc_attr( isset( $_POST['fmp_support_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_support_name'] ) ) : ( wp_get_current_user()->display_name ?: '' ) ); ?>" required="required" class="fmp-contact-input" />
						</p>
						<p class="fmp-contact-form-field">
							<label for="fmp_support_concern"><?php esc_html_e( 'What is your concern about?', 'farm-management' ); ?> <span class="required">*</span></label>
							<select id="fmp_support_concern" name="fmp_support_concern" required="required" class="fmp-contact-input">
								<?php foreach ( $support_concern_options as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( isset( $_POST['fmp_support_concern'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_support_concern'] ) ) : '', $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<p class="fmp-contact-form-field">
							<label for="fmp_support_message"><?php esc_html_e( 'Describe your concern', 'farm-management' ); ?> <span class="required">*</span></label>
							<textarea id="fmp_support_message" name="fmp_support_message" rows="5" required="required" class="fmp-contact-textarea" placeholder="<?php esc_attr_e( 'e.g. Vaccination due date is wrong, animal missing from list…', 'farm-management' ); ?>"><?php echo esc_textarea( isset( $_POST['fmp_support_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_support_message'] ) ) : '' ); ?></textarea>
						</p>
						<p class="fmp-contact-form-submit">
							<button type="submit" class="fmp-contact-submit fmp-btn fmp-btn-primary"><?php esc_html_e( 'Send', 'farm-management' ); ?></button>
						</p>
					</form>
				</div>
			</div>
			<?php
		}
		$content = ob_get_clean();
		return self::portal_wrap( __( 'Support', 'farm-management' ), __( 'Internal support for farmers — report an issue or describe what went wrong.', 'farm-management' ), 'support', $content );
	}

	/**
	 * [fmp_home] – portal-wrapped home page. Uses same portal_wrap() as dashboard for identical header, tabs, and container CSS.
	 * Content: hero card, feature cards (dashboard style), today at a glance KPIs, what's due next list.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string
	 */
	public static function render_home( $atts ) {
		$guard = self::fmp_require_login();
		if ( $guard !== true ) {
			return $guard;
		}

		$links = self::get_home_portal_links();
		$dashboard_url = $links['dashboard'];
		$animals_url   = $links['animals'];
		$add_animal_url = $animals_url;
		if ( class_exists( 'FMP_Portal' ) ) {
			$add_animal_url = FMP_Portal::get_add_url( 'add_animal' );
		}

		$animal_count   = 0;
		$overdue_count  = 0;
		$due_soon_count = 0;
		$open_tasks     = 0;
		$due_next_list  = array();
		if ( class_exists( 'FMP_Dashboard' ) ) {
			$animal_count   = (int) FMP_Dashboard::get_animals_count();
			$overdue        = FMP_Dashboard::get_overdue_vaccinations();
			$due_soon       = FMP_Dashboard::get_vaccinations_due_soon();
			$overdue_count  = is_array( $overdue ) ? count( $overdue ) : 0;
			$due_soon_count = is_array( $due_soon ) ? count( $due_soon ) : 0;
			$open_tasks     = (int) FMP_Dashboard::get_tasks_count();
		}
		if ( class_exists( 'FMP_Reports' ) ) {
			$all_due = FMP_Reports::get_vaccinations_due_including_overdue();
			$due_next_list = is_array( $all_due ) ? array_slice( $all_due, 0, 5 ) : array();
		}
		$has_data = $animal_count > 0 || $overdue_count > 0 || $due_soon_count > 0 || $open_tasks > 0;

		$due_soon_days = 14;
		if ( class_exists( 'FMP_Settings' ) ) {
			$due_soon_days = (int) FMP_Settings::get( FMP_Settings::KEY_DUE_SOON_DAYS );
			if ( $due_soon_days < 1 ) {
				$due_soon_days = (int) FMP_Settings::get( FMP_Settings::KEY_VACCINATION_DAYS );
			}
			$due_soon_days = $due_soon_days >= 1 ? $due_soon_days : 14;
		}

		$features = array(
			array( 'key' => 'animals', 'title' => __( 'Animals', 'farm-management' ), 'desc' => __( 'Track livestock, tags, and health records.', 'farm-management' ) ),
			array( 'key' => 'vaccinations', 'title' => __( 'Vaccinations', 'farm-management' ), 'desc' => __( 'Schedule and record vaccines with due reminders.', 'farm-management' ) ),
			array( 'key' => 'crops', 'title' => __( 'Crops', 'farm-management' ), 'desc' => __( 'Manage planting, harvest, and field locations.', 'farm-management' ) ),
			array( 'key' => 'expenses', 'title' => __( 'Expenses', 'farm-management' ), 'desc' => __( 'Log costs by category and view monthly totals.', 'farm-management' ) ),
			array( 'key' => 'reports', 'title' => __( 'Reports', 'farm-management' ), 'desc' => __( 'Vaccinations due, species counts, and expense summaries.', 'farm-management' ) ),
		);

		ob_start();
		?>
		<!-- Hero card (same card styling as dashboard) -->
		<div class="fmp-dashboard-widget fmp-home-hero-card fmp-portal-section">
			<h2 class="fmp-dashboard-widget-title"><?php esc_html_e( 'Farm Management System', 'farm-management' ); ?></h2>
			<p class="fmp-home-hero-subtitle"><?php esc_html_e( 'Manage livestock, crops, tasks, inventory and costs — from one dashboard.', 'farm-management' ); ?></p>
			<div class="fmp-home-hero-buttons">
				<a href="<?php echo esc_url( $dashboard_url ); ?>" class="fmp-btn fmp-btn-primary"><?php esc_html_e( 'Open Dashboard', 'farm-management' ); ?></a>
				<a href="<?php echo esc_url( $add_animal_url ); ?>" class="fmp-btn fmp-btn-secondary"><?php esc_html_e( 'Add Animal', 'farm-management' ); ?></a>
			</div>
		</div>

		<!-- Feature cards (same style as dashboard KPI cards) -->
		<div class="fmp-portal-section">
			<h2 class="fmp-portal-section-title"><?php esc_html_e( 'Features', 'farm-management' ); ?></h2>
			<div class="fmp-portal-cards fmp-dashboard-cards">
				<?php foreach ( $features as $f ) :
					$url = isset( $links[ $f['key'] ] ) ? $links[ $f['key'] ] : $dashboard_url;
					?>
					<div class="fmp-portal-card fmp-dashboard-card">
						<span class="fmp-portal-card-icon" aria-hidden="true"><?php echo self::get_home_link_icon( $f['key'] ); ?></span>
						<span class="fmp-portal-card-label fmp-dashboard-card-label"><?php echo esc_html( $f['title'] ); ?></span>
						<p class="fmp-home-feature-desc"><?php echo esc_html( $f['desc'] ); ?></p>
						<a href="<?php echo esc_url( $url ); ?>" class="fmp-btn fmp-btn-secondary fmp-btn-sm"><?php esc_html_e( 'View', 'farm-management' ); ?></a>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Today at a glance (same KPI card style) -->
		<div class="fmp-portal-section">
			<h2 class="fmp-portal-section-title"><?php esc_html_e( 'Today at a glance', 'farm-management' ); ?></h2>
			<?php if ( $has_data ) : ?>
				<div class="fmp-portal-cards fmp-dashboard-cards">
					<div class="fmp-portal-card fmp-dashboard-card">
						<span class="fmp-portal-card-icon" aria-hidden="true">&#128046;</span>
						<span class="fmp-portal-card-value fmp-dashboard-card-value"><?php echo absint( $animal_count ); ?></span>
						<span class="fmp-portal-card-label fmp-dashboard-card-label"><?php esc_html_e( 'Total Animals', 'farm-management' ); ?></span>
						<a href="<?php echo esc_url( $animals_url ); ?>" class="fmp-portal-card-link fmp-dashboard-card-link"><?php esc_html_e( 'View', 'farm-management' ); ?></a>
					</div>
					<div class="fmp-portal-card fmp-dashboard-card">
						<span class="fmp-portal-card-icon" aria-hidden="true">&#128137;</span>
						<span class="fmp-portal-card-value fmp-dashboard-card-value"><?php echo absint( $overdue_count ); ?></span>
						<span class="fmp-portal-card-label fmp-dashboard-card-label"><?php esc_html_e( 'Overdue Vaccinations', 'farm-management' ); ?></span>
						<a href="<?php echo esc_url( $links['vaccinations'] ); ?>" class="fmp-portal-card-link fmp-dashboard-card-link"><?php esc_html_e( 'View', 'farm-management' ); ?></a>
					</div>
					<div class="fmp-portal-card fmp-dashboard-card">
						<span class="fmp-portal-card-icon" aria-hidden="true">&#128197;</span>
						<span class="fmp-portal-card-value fmp-dashboard-card-value"><?php echo absint( $due_soon_count ); ?></span>
						<span class="fmp-portal-card-label fmp-dashboard-card-label"><?php echo esc_html( sprintf( /* translators: %d: number of days */ __( 'Due Soon (next %d days)', 'farm-management' ), $due_soon_days ) ); ?></span>
						<a href="<?php echo esc_url( $links['vaccinations'] ); ?>" class="fmp-portal-card-link fmp-dashboard-card-link"><?php esc_html_e( 'View', 'farm-management' ); ?></a>
					</div>
					<div class="fmp-portal-card fmp-dashboard-card">
						<span class="fmp-portal-card-icon" aria-hidden="true">&#128203;</span>
						<span class="fmp-portal-card-value fmp-dashboard-card-value"><?php echo absint( $open_tasks ); ?></span>
						<span class="fmp-portal-card-label fmp-dashboard-card-label"><?php esc_html_e( 'Open Tasks', 'farm-management' ); ?></span>
						<a href="<?php echo esc_url( $dashboard_url ); ?>" class="fmp-portal-card-link fmp-dashboard-card-link"><?php esc_html_e( 'View', 'farm-management' ); ?></a>
					</div>
				</div>
			<?php else : ?>
				<div class="fmp-portal-empty fmp-dashboard-empty">
					<p><?php esc_html_e( 'No data yet. Add your first animal or open the dashboard to get started.', 'farm-management' ); ?></p>
					<a href="<?php echo esc_url( $dashboard_url ); ?>" class="fmp-btn fmp-btn-primary"><?php esc_html_e( 'Open Dashboard', 'farm-management' ); ?></a>
					<a href="<?php echo esc_url( $add_animal_url ); ?>" class="fmp-btn fmp-btn-secondary"><?php esc_html_e( 'Add Animal', 'farm-management' ); ?></a>
				</div>
			<?php endif; ?>
		</div>

		<!-- What's due next (next 5 vaccinations) -->
		<div class="fmp-dashboard-widget fmp-portal-section">
			<h2 class="fmp-dashboard-widget-title"><?php esc_html_e( "What's due next", 'farm-management' ); ?></h2>
			<?php if ( ! empty( $due_next_list ) ) : ?>
				<div class="fmp-table-wrap">
					<table class="fmp-table fmp-table-responsive">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Animal', 'farm-management' ); ?></th>
								<th><?php esc_html_e( 'Vaccine', 'farm-management' ); ?></th>
								<th><?php esc_html_e( 'Next due', 'farm-management' ); ?></th>
								<th><?php esc_html_e( 'Status', 'farm-management' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $due_next_list as $v ) :
								$animal_id  = (int) get_post_meta( $v->ID, '_fmp_animal_id', true );
								$animal     = self::get_animal_name_by_id( $animal_id );
								$vaccine    = get_post_meta( $v->ID, '_fmp_vaccine_name', true );
								$next_due   = get_post_meta( $v->ID, '_fmp_next_due_date', true );
								$status     = class_exists( 'FMP_Vaccinations' ) ? FMP_Vaccinations::get_vaccination_status( $next_due ) : 'ok';
								$status_label = ( $status === 'overdue' ) ? __( 'Overdue', 'farm-management' ) : ( ( $status === 'due_soon' ) ? __( 'Due Soon', 'farm-management' ) : __( 'OK', 'farm-management' ) );
								$badge_class = ( $status === 'overdue' ) ? 'fmp-badge-overdue' : ( ( $status === 'due_soon' ) ? 'fmp-badge-due-soon' : 'fmp-badge-ok' );
								?>
								<tr>
									<td data-label="<?php esc_attr_e( 'Animal', 'farm-management' ); ?>"><?php echo esc_html( $animal ); ?></td>
									<td data-label="<?php esc_attr_e( 'Vaccine', 'farm-management' ); ?>"><?php echo esc_html( $vaccine ?: '—' ); ?></td>
									<td data-label="<?php esc_attr_e( 'Next due', 'farm-management' ); ?>"><?php echo esc_html( $next_due ?: '—' ); ?></td>
									<td data-label="<?php esc_attr_e( 'Status', 'farm-management' ); ?>"><span class="fmp-badge <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<p class="fmp-dashboard-view-all"><a href="<?php echo esc_url( $links['vaccinations'] ); ?>" class="fmp-portal-card-link"><?php esc_html_e( 'View all vaccinations', 'farm-management' ); ?></a></p>
			<?php else : ?>
				<div class="fmp-portal-empty fmp-dashboard-empty">
					<p><?php echo esc_html( sprintf( /* translators: %d: number of days */ _n( 'No vaccinations due or overdue in the next %d day.', 'No vaccinations due or overdue in the next %d days.', $due_soon_days, 'farm-management' ), $due_soon_days ) ); ?></p>
					<a href="<?php echo esc_url( $links['vaccinations'] ); ?>" class="fmp-portal-card-link"><?php esc_html_e( 'View vaccinations', 'farm-management' ); ?></a>
				</div>
			<?php endif; ?>
		</div>
		<?php
		$content = ob_get_clean();

		// Home is public: same CSS look (fmp-portal + fmp-container), no tabs, no login guard.
		return '<div class="fmp-portal fmp-home">' . "\n"
			. '<div class="fmp-container">' . "\n"
			. '<div class="fmp-page-head">' . "\n"
			. '<h1 class="fmp-title fmp-portal-title">' . esc_html__( 'Farm Management', 'farm-management' ) . '</h1>' . "\n"
			. '<p class="fmp-subtitle fmp-portal-subtitle">' . esc_html__( 'Manage your farm digitally. Track animals, crops, and vaccinations.', 'farm-management' ) . '</p>' . "\n"
			. '</div>' . "\n"
			. '<div class="fmp-page-body fmp-portal-content">' . "\n"
			. $content . "\n"
			. '</div>' . "\n"
			. '</div>' . "\n"
			. '</div>';
	}

	/**
	 * Get URLs for portal pages by key (for home page links). Defensive: no missing keys, no null to get_page_by_path.
	 *
	 * @return array Associative array key => url.
	 */
	private static function get_home_portal_links() {
		$out         = array();
		$portal_url  = self::get_portal_home_url();
		foreach ( self::PORTAL_TABS as $key => $config ) {
			$config = is_array( $config ) ? $config : array();
			if ( ! empty( $config['is_logout'] ) ) {
				$out[ $key ] = wp_logout_url( $portal_url );
			} elseif ( isset( $config['url'] ) && is_string( $config['url'] ) && $config['url'] !== '' ) {
				$out[ $key ] = $config['url'];
			} else {
				$slug = sanitize_title( (string) ( $config['slug'] ?? '' ) );
				if ( $slug === '' ) {
					$out[ $key ] = $portal_url;
				} else {
					$url = self::get_page_url_by_slug( $slug );
					$fallback_slug = sanitize_title( (string) ( $config['fallback_slug'] ?? '' ) );
					if ( $url === '#' && $fallback_slug !== '' ) {
						$url = self::get_page_url_by_slug( $fallback_slug );
					}
					$out[ $key ] = ( $url !== '#' ) ? $url : home_url( '/' . $slug . '/' );
				}
			}
		}
		return $out;
	}

	/**
	 * Simple icon character for home quick-access links.
	 *
	 * @param string $key Portal tab key.
	 * @return string Safe HTML (emoji or entity).
	 */
	private static function get_home_link_icon( $key ) {
		$icons = array(
			'dashboard'    => '&#128200;',
			'animals'      => '&#128046;',
			'crops'        => '&#127806;',
			'vaccinations' => '&#128137;',
			'reports'      => '&#128202;',
			'support'      => '&#9993;',
			'expenses'     => '&#128176;',
		);
		return isset( $icons[ $key ] ) ? $icons[ $key ] : '&#9733;';
	}

	/**
	 * Get animal display name by post ID (tag or title).
	 *
	 * @param int $animal_id Animal post ID.
	 * @return string
	 */
	protected static function get_animal_name_by_id( $animal_id ) {
		if ( ! $animal_id ) {
			return '—';
		}
		$tag = get_post_meta( $animal_id, '_fmp_tag', true );
		if ( $tag !== '' ) {
			return $tag;
		}
		$post = get_post( $animal_id );
		return $post && $post->post_title ? $post->post_title : '—';
	}
}

/**
 * Helper: get page URL by slug. Returns home_url('/') for 'home', permalink if page exists, else '#'.
 * Never passes null/empty to get_page_by_path.
 *
 * @param string $slug Page slug (can be empty).
 * @return string
 */
function fmp_get_page_url_by_slug( $slug ) {
	return FMP_Frontend::get_page_url_by_slug( $slug );
}
