<?php
/**
 * Frontend shortcodes for Farm Management data.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Shortcodes
 */
class FMP_Shortcodes {

	const SHORTCODE_ANIMALS         = 'fmp_animals';
	const SHORTCODE_VACCINATIONS_DUE = 'fmp_vaccinations_due';
	const CSS_HANDLE                = 'fmp-frontend';

	/**
	 * Constructor. Registers shortcodes and enqueues frontend CSS.
	 */
	public function __construct() {
		add_shortcode( self::SHORTCODE_ANIMALS, array( __CLASS__, 'render_animals' ) );
		add_shortcode( self::SHORTCODE_VACCINATIONS_DUE, array( __CLASS__, 'render_vaccinations_due' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_css' ), 10 );
	}

	/**
	 * Enqueue frontend table CSS on pages that may contain shortcodes.
	 */
	public static function enqueue_frontend_css() {
		global $post;
		if ( ! $post || ! is_singular() ) {
			return;
		}
		if ( has_shortcode( $post->post_content, self::SHORTCODE_ANIMALS ) || has_shortcode( $post->post_content, self::SHORTCODE_VACCINATIONS_DUE ) ) {
			wp_enqueue_style(
				self::CSS_HANDLE,
				FMP_PLUGIN_URL . 'assets/css/fmp-frontend.css',
				array(),
				FMP_VERSION
			);
		}
	}

	/**
	 * [fmp_animals] – portal-wrapped list of all animals (image, tag, species, status, age) with search.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string
	 */
	public static function render_animals( $atts ) {
		$query = new WP_Query( array(
			'post_type'      => 'fmp_animal',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		$animals = $query->posts;

		ob_start();
		?>
		<div class="fmp-shortcode fmp-animals fmp-animals-portal">
			<div class="fmp-portal-search">
				<label for="fmp-animals-search" class="screen-reader-text"><?php esc_html_e( 'Search animals', 'farm-management' ); ?></label>
				<input type="search" id="fmp-animals-search" class="fmp-animals-search-input" placeholder="<?php esc_attr_e( 'Search by tag, species, or status…', 'farm-management' ); ?>" autocomplete="off" />
			</div>
			<div class="fmp-table-wrap">
				<table class="fmp-table fmp-table-animals fmp-table-responsive" id="fmp-animals-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Image', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Tag', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Species', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Status', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Age', 'farm-management' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						if ( empty( $animals ) ) {
							echo '<tr><td colspan="5" class="fmp-portal-empty">' . esc_html__( 'No animals found.', 'farm-management' ) . '</td></tr>';
						} else {
							foreach ( $animals as $post ) {
								$tag     = self::get_animal_name( $post );
								$species = get_post_meta( $post->ID, '_fmp_species', true );
								$species = $species !== '' ? esc_html( $species ) : '—';
								$status  = get_post_meta( $post->ID, '_fmp_status', true );
								$age     = self::get_animal_age( $post->ID );
								$status_label = '';
								$status_class = 'fmp-badge-ok';
								if ( $status === 'alive' ) {
									$status_label = __( 'Alive', 'farm-management' );
									$status_class = 'fmp-badge-ok';
								} elseif ( $status === 'sold' ) {
									$status_label = __( 'Sold', 'farm-management' );
									$status_class = 'fmp-badge-due-soon';
								} elseif ( $status === 'dead' ) {
									$status_label = __( 'Dead', 'farm-management' );
									$status_class = 'fmp-badge-overdue';
								} else {
									$status_label = $status !== '' ? esc_html( $status ) : '—';
								}
								$thumb_id = get_post_thumbnail_id( $post->ID );
								$img_html = $thumb_id ? wp_get_attachment_image( $thumb_id, 'thumbnail', false, array( 'class' => 'fmp-animal-thumb' ) ) : '<span class="fmp-animal-no-image" aria-hidden="true">—</span>';
								$search_data = strtolower( $tag . ' ' . $species . ' ' . $status_label );
								?>
								<tr class="fmp-animal-row" data-search="<?php echo esc_attr( $search_data ); ?>">
									<td data-label="<?php esc_attr_e( 'Image', 'farm-management' ); ?>"><?php echo $img_html; ?></td>
									<td data-label="<?php esc_attr_e( 'Tag', 'farm-management' ); ?>"><?php echo esc_html( $tag ); ?></td>
									<td data-label="<?php esc_attr_e( 'Species', 'farm-management' ); ?>"><?php echo $species; ?></td>
									<td data-label="<?php esc_attr_e( 'Status', 'farm-management' ); ?>"><span class="fmp-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
									<td data-label="<?php esc_attr_e( 'Age', 'farm-management' ); ?>"><?php echo esc_html( $age ); ?></td>
								</tr>
								<?php
							}
						}
						?>
					</tbody>
				</table>
			</div>
			<script>
			(function() {
				var input = document.getElementById('fmp-animals-search');
				if (!input) return;
				var table = document.getElementById('fmp-animals-table');
				if (!table) return;
				var rows = table.querySelectorAll('tbody tr.fmp-animal-row');
				input.addEventListener('input', function() {
					var q = (this.value || '').toLowerCase().trim();
					rows.forEach(function(row) {
						var text = row.getAttribute('data-search') || '';
						row.style.display = (q === '' || text.indexOf(q) !== -1) ? '' : 'none';
					});
				});
			})();
			</script>
		</div>
		<?php
		$content = ob_get_clean();
		if ( class_exists( 'FMP_Frontend' ) && method_exists( 'FMP_Frontend', 'portal_wrap' ) ) {
			return FMP_Frontend::portal_wrap( __( 'Animals', 'farm-management' ), __( 'View and search your animals.', 'farm-management' ), 'animals', $content );
		}
		return $content;
	}

	/**
	 * [fmp_vaccinations_due] – vaccinations due in the next 14 days (including overdue).
	 *
	 * @param array $atts Shortcode attributes. Optional: days (default 14).
	 * @return string
	 */
	public static function render_vaccinations_due( $atts ) {
		$atts = shortcode_atts( array( 'days' => 14 ), $atts, self::SHORTCODE_VACCINATIONS_DUE );
		$days = max( 1, min( 365, (int) $atts['days'] ) );
		$today_ymd = gmdate( 'Y-m-d' );
		$end_ymd   = gmdate( 'Y-m-d', strtotime( '+' . $days . ' days' ) );
		$query = new WP_Query( array(
			'post_type'      => 'fmp_vaccination',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'meta_value',
			'meta_key'       => '_fmp_next_due_date',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_fmp_next_due_date',
					'value'   => $end_ymd,
					'compare' => '<=',
					'type'    => 'DATE',
				),
			),
		) );
		$vaccinations = $query->posts;
		ob_start();
		?>
		<div class="fmp-shortcode fmp-vaccinations-due">
			<table class="fmp-table fmp-table-vaccinations">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Animal', 'farm-management' ); ?></th>
						<th><?php esc_html_e( 'Vaccine', 'farm-management' ); ?></th>
						<th><?php esc_html_e( 'Next due', 'farm-management' ); ?></th>
						<th><?php esc_html_e( 'Status', 'farm-management' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( empty( $vaccinations ) ) {
						echo '<tr><td colspan="4">' . esc_html( sprintf( __( 'No vaccinations due in the next %d days.', 'farm-management' ), $days ) ) . '</td></tr>';
					} else {
						foreach ( $vaccinations as $post ) {
							$animal_id = (int) get_post_meta( $post->ID, '_fmp_animal_id', true );
							$animal    = self::get_animal_name_by_id( $animal_id );
							$vaccine   = get_post_meta( $post->ID, '_fmp_vaccine_name', true );
							$next_due  = get_post_meta( $post->ID, '_fmp_next_due_date', true );
							$status    = FMP_Vaccinations::get_vaccination_status( $next_due );
							$status_label = ( $status === 'overdue' ) ? __( 'Overdue', 'farm-management' ) : ( ( $status === 'due_soon' ) ? __( 'Due soon', 'farm-management' ) : __( 'OK', 'farm-management' ) );
							$status_class = ( $status === 'overdue' ) ? ' fmp-status-overdue' : ( ( $status === 'due_soon' ) ? ' fmp-status-due-soon' : '' );
							echo '<tr>';
							echo '<td>' . esc_html( $animal ) . '</td>';
							echo '<td>' . esc_html( $vaccine ?: '—' ) . '</td>';
							echo '<td>' . esc_html( $next_due ?: '—' ) . '</td>';
							echo '<td class="' . esc_attr( $status_class ) . '">' . esc_html( $status_label ) . '</td>';
							echo '</tr>';
						}
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get animal display name (tag or post title).
	 *
	 * @param WP_Post $post Animal post.
	 * @return string
	 */
	protected static function get_animal_name( $post ) {
		$tag = get_post_meta( $post->ID, '_fmp_tag', true );
		if ( $tag !== '' ) {
			return $tag;
		}
		return $post->post_title ?: '—';
	}

	/**
	 * Get animal display name by post ID.
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

	/**
	 * Get age from date of birth (e.g. "3 years" or "—").
	 *
	 * @param int $animal_id Animal post ID.
	 * @return string
	 */
	protected static function get_animal_age( $animal_id ) {
		$dob = get_post_meta( $animal_id, '_fmp_date_of_birth', true );
		if ( $dob === '' || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $dob ) ) {
			return '—';
		}
		$dob_ts = strtotime( $dob );
		if ( $dob_ts === false ) {
			return '—';
		}
		$years = (int) ( ( time() - $dob_ts ) / ( 365.25 * DAY_IN_SECONDS ) );
		if ( $years < 0 ) {
			return '—';
		}
		return sprintf(
			/* translators: %d: number of years */
			_n( '%d year', '%d years', $years, 'farm-management' ),
			$years
		);
	}
}
