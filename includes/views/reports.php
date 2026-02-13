<?php
/**
 * Reports page view – Report A (Vaccinations due), Report B (Animals by species & status), Report C (Monthly expenses).
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to view reports.', 'farm-management' ), '', array( 'response' => 403 ) );
}

$due_soon_days = (int) FMP_Settings::get( FMP_Settings::KEY_DUE_SOON_DAYS );
if ( $due_soon_days < 1 ) {
	$due_soon_days = (int) FMP_Settings::get( FMP_Settings::KEY_VACCINATION_DAYS );
}
$due_soon_days = $due_soon_days >= 1 ? $due_soon_days : 14;

$vaccinations_due   = FMP_Reports::get_vaccinations_due_including_overdue();
$animals_species_status = FMP_Reports::get_animals_by_species_and_status();

$report_year  = isset( $_GET['report_year'] ) ? absint( $_GET['report_year'] ) : (int) gmdate( 'Y' );
$report_month = isset( $_GET['report_month'] ) ? absint( $_GET['report_month'] ) : (int) gmdate( 'n' );
$report_year  = max( 2000, min( 2100, $report_year ) );
$report_month = max( 1, min( 12, $report_month ) );
$expenses_by_cat = FMP_Reports::get_expenses_by_category_for_month( $report_year, $report_month );

$export_vacc_url = wp_nonce_url( admin_url( 'admin-post.php?action=fmp_export_report_vaccinations_due' ), FMP_Reports::EXPORT_NONCE_VACC );
$export_animals_url = wp_nonce_url( admin_url( 'admin-post.php?action=fmp_export_report_animals_species' ), FMP_Reports::EXPORT_NONCE_ANIMALS );
$export_expenses_url = wp_nonce_url( admin_url( 'admin-post.php?action=fmp_export_report_expenses_category&year=' . $report_year . '&month=' . $report_month ), FMP_Reports::EXPORT_NONCE_EXPENSES );

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
$reports_base = admin_url( 'admin.php?page=fmp-reports' );
?>
<div class="wrap fmp-admin-wrap fmp-reports">
	<h1><?php esc_html_e( 'Reports', 'farm-management' ); ?></h1>

	<!-- Report A: Vaccinations due in next N days (including overdue) -->
	<div class="fmp-report-section">
		<h2 class="fmp-report-title"><?php echo esc_html( sprintf( /* translators: %d: number of days */ _n( 'Vaccinations due in next %d day (including overdue)', 'Vaccinations due in next %d days (including overdue)', $due_soon_days, 'farm-management' ), $due_soon_days ) ); ?></h2>
		<p class="fmp-report-desc"><?php esc_html_e( 'Animal tag/name, vaccine, next due date, status, and location.', 'farm-management' ); ?></p>
		<table class="wp-list-table widefat fixed striped">
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
						$animal_name = $animal_id ? get_the_title( $animal_id ) : '—';
						$animal_tag  = $animal_id ? get_post_meta( $animal_id, '_fmp_tag', true ) : '';
						$animal_label = ( $animal_tag !== '' ? $animal_tag : $animal_name );
						if ( $animal_label === '' ) {
							$animal_label = '—';
						}
						$vaccine   = get_post_meta( $v->ID, '_fmp_vaccine_name', true );
						$next_due  = get_post_meta( $v->ID, '_fmp_next_due_date', true );
						$status    = FMP_Vaccinations::get_vaccination_status( $next_due );
						$location  = get_post_meta( $v->ID, '_fmp_vaccination_location', true );
						if ( $location === '' ) {
							$location = '—';
						}
						?>
						<tr>
							<td><?php echo esc_html( $animal_label ); ?></td>
							<td><?php echo esc_html( $vaccine ?: '—' ); ?></td>
							<td><?php echo esc_html( $next_due ?: '—' ); ?></td>
							<td>
								<?php
								if ( $status === 'overdue' ) {
									echo '<span class="fmp-badge fmp-badge-overdue">' . esc_html__( 'Overdue', 'farm-management' ) . '</span>';
								} elseif ( $status === 'due_soon' ) {
									echo '<span class="fmp-badge fmp-badge-due-soon">' . esc_html__( 'Due Soon', 'farm-management' ) . '</span>';
								} else {
									echo '<span class="fmp-badge fmp-badge-ok">' . esc_html__( 'OK', 'farm-management' ) . '</span>';
								}
								?>
							</td>
							<td><?php echo esc_html( $location ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="5"><?php echo esc_html( sprintf( /* translators: %d: number of days */ _n( 'No vaccinations due or overdue in the next %d day.', 'No vaccinations due or overdue in the next %d days.', $due_soon_days, 'farm-management' ), $due_soon_days ) ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		<p class="fmp-report-actions">
			<a href="<?php echo esc_url( $export_vacc_url ); ?>" class="button"><?php esc_html_e( 'Export CSV', 'farm-management' ); ?></a>
		</p>
	</div>

	<!-- Report B: Animals by species and status -->
	<div class="fmp-report-section">
		<h2 class="fmp-report-title"><?php esc_html_e( 'Animals by species & status', 'farm-management' ); ?></h2>
		<p class="fmp-report-desc"><?php esc_html_e( 'Count of animals grouped by species and status (Alive / Sold / Dead).', 'farm-management' ); ?></p>
		<table class="wp-list-table widefat fixed striped">
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
		<?php if ( current_user_can( FMP_Capabilities::EXPORT_REPORTS ) ) : ?>
		<p class="fmp-report-actions">
			<a href="<?php echo esc_url( $export_animals_url ); ?>" class="button"><?php esc_html_e( 'Export CSV', 'farm-management' ); ?></a>
		</p>
		<?php endif; ?>
	</div>

	<!-- Report C: Monthly expenses summary (selected month) -->
	<div class="fmp-report-section">
		<h2 class="fmp-report-title"><?php esc_html_e( 'Monthly expenses summary', 'farm-management' ); ?></h2>
		<p class="fmp-report-desc"><?php esc_html_e( 'Expenses by category for the selected month.', 'farm-management' ); ?></p>
		<form method="get" action="<?php echo esc_url( $reports_base ); ?>" class="fmp-report-month-form">
			<input type="hidden" name="page" value="fmp-reports" />
			<label for="fmp-report-month"><?php esc_html_e( 'Month', 'farm-management' ); ?></label>
			<select name="report_month" id="fmp-report-month">
				<?php foreach ( $months as $num => $label ) : ?>
					<option value="<?php echo esc_attr( $num ); ?>" <?php selected( $report_month, $num ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<label for="fmp-report-year"><?php esc_html_e( 'Year', 'farm-management' ); ?></label>
			<input type="number" name="report_year" id="fmp-report-year" value="<?php echo esc_attr( $report_year ); ?>" min="2000" max="2100" style="width: 5em;" />
			<?php submit_button( __( 'Apply', 'farm-management' ), 'secondary', 'apply_month', false ); ?>
		</form>
		<p class="fmp-report-desc"><?php echo esc_html( sprintf( __( 'Total expenses for %s by category (South African Rand, ZAR).', 'farm-management' ), $months[ $report_month ] . ' ' . $report_year ) ); ?></p>
		<table class="wp-list-table widefat fixed striped">
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
					<tr><td colspan="2"><?php echo esc_html( sprintf( __( 'No expenses for %s.', 'farm-management' ), $months[ $report_month ] . ' ' . $report_year ) ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php if ( current_user_can( FMP_Capabilities::EXPORT_REPORTS ) ) : ?>
		<p class="fmp-report-actions">
			<a href="<?php echo esc_url( $export_expenses_url ); ?>" class="button"><?php esc_html_e( 'Export CSV', 'farm-management' ); ?></a>
		</p>
		<?php endif; ?>
	</div>
</div>
