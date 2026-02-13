<?php
/**
 * Dashboard view – stat cards, upcoming vaccinations, tasks due soon.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access the dashboard.', 'farm-management' ), '', array( 'response' => 403 ) );
}

$animal_counts   = FMP_Dashboard::get_post_type_counts( 'fmp_animal' );
$task_counts     = FMP_Dashboard::get_post_type_counts( 'fmp_task' );
$inventory_counts = FMP_Dashboard::get_post_type_counts( 'fmp_inventory_item' );
$expense_counts  = FMP_Dashboard::get_post_type_counts( 'fmp_expense' );
$vacc_counts     = FMP_Dashboard::get_post_type_counts( 'fmp_vaccination' );

$inventory_low   = FMP_Dashboard::get_inventory_low_count();
$expenses_month  = FMP_Dashboard::get_expenses_this_month();

$overdue_vaccinations   = FMP_Dashboard::get_overdue_vaccinations();
$vaccinations_due_soon  = FMP_Dashboard::get_vaccinations_due_soon();
$tasks_due_soon        = FMP_Dashboard::get_tasks_due_soon();
$low_stock_items       = FMP_Dashboard::get_low_stock_items();

$due_soon_days = (int) FMP_Settings::get( FMP_Settings::KEY_DUE_SOON_DAYS );
if ( $due_soon_days < 1 ) {
	$due_soon_days = (int) FMP_Settings::get( FMP_Settings::KEY_VACCINATION_DAYS );
}
$due_soon_days = $due_soon_days >= 1 ? $due_soon_days : 14;

$view_all_overdue_vacc = admin_url( 'admin.php?page=fmp-vaccinations&filter=overdue' );
$view_all_due_soon_vacc = admin_url( 'admin.php?page=fmp-vaccinations&filter=due_soon' );
$view_all_tasks_due    = admin_url( 'admin.php?page=fmp-tasks&filter=due_soon' );
$view_all_low_stock    = admin_url( 'admin.php?page=fmp-inventory&filter=low_stock' );

$add_animal_url     = admin_url( 'admin.php?page=fmp-animals&action=new' );
$add_crop_url       = admin_url( 'admin.php?page=fmp-crops&action=new' );
$add_task_url       = admin_url( 'admin.php?page=fmp-tasks&action=new' );
$add_inventory_url  = admin_url( 'admin.php?page=fmp-inventory&action=new' );
$add_expense_url    = admin_url( 'admin.php?page=fmp-expenses&action=new' );
$add_vaccination_url = admin_url( 'admin.php?page=fmp-vaccinations&action=new' );

$demo_mode          = FMP_Demo::is_demo_mode();
$demo_create_url    = wp_nonce_url( admin_url( 'admin-post.php?action=fmp_demo_create_sample' ), FMP_Demo::CREATE_NONCE );
$demo_delete_url    = wp_nonce_url( admin_url( 'admin-post.php?action=fmp_demo_delete_sample' ), FMP_Demo::DELETE_NONCE );
$demo_created       = isset( $_GET['fmp_demo'] ) && $_GET['fmp_demo'] === 'created';
$demo_deleted       = isset( $_GET['fmp_demo'] ) && $_GET['fmp_demo'] === 'deleted';

$edit_animals     = admin_url( 'admin.php?page=fmp-animals' );
$edit_tasks       = admin_url( 'admin.php?page=fmp-tasks' );
$edit_inventory   = admin_url( 'admin.php?page=fmp-inventory' );
$edit_expenses    = admin_url( 'admin.php?page=fmp-expenses' );
$edit_vaccinations = admin_url( 'admin.php?page=fmp-vaccinations' );

function fmp_dashboard_stat_tooltip( $publish, $draft ) {
	if ( $draft > 0 ) {
		return sprintf( /* translators: 1: published count, 2: draft count */ _n( '%1$s published, %2$s draft', '%1$s published, %2$s drafts', $draft, 'farm-management' ), $publish, $draft );
	}
	return '';
}
?>
<div class="wrap fmp-admin-wrap fmp-dashboard">
	<h1><?php esc_html_e( 'Farm Management', 'farm-management' ); ?></h1>
	<p class="fmp-dashboard-welcome">
		<?php esc_html_e( 'Welcome to your farm operations dashboard.', 'farm-management' ); ?>
	</p>

	<?php if ( $demo_created ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Sample data created successfully.', 'farm-management' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $demo_deleted ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Sample data deleted.', 'farm-management' ); ?></p></div>
	<?php endif; ?>

	<?php if ( $demo_mode && current_user_can( FMP_Capabilities::MANAGE_SETTINGS ) ) : ?>
		<div class="fmp-demo-banner notice notice-info">
			<p><strong><?php esc_html_e( 'Demo Mode: Sample data enabled', 'farm-management' ); ?></strong></p>
			<p>
				<a href="<?php echo esc_url( $demo_create_url ); ?>" class="button button-primary"><?php esc_html_e( 'Create Sample Data', 'farm-management' ); ?></a>
				<a href="<?php echo esc_url( $demo_delete_url ); ?>" class="button" onclick="return confirm('<?php echo esc_js( __( 'Remove all sample data? This cannot be undone.', 'farm-management' ) ); ?>');"><?php esc_html_e( 'Delete Sample Data', 'farm-management' ); ?></a>
			</p>
		</div>
	<?php endif; ?>

	<div class="fmp-quick-add">
		<h2 class="fmp-quick-add-title"><?php esc_html_e( 'Quick Add', 'farm-management' ); ?></h2>
		<div class="fmp-quick-add-buttons">
			<a href="<?php echo esc_url( $add_animal_url ); ?>" class="button fmp-quick-add-btn"><span class="dashicons dashicons-heart" aria-hidden="true"></span> <?php esc_html_e( 'Add Animal', 'farm-management' ); ?></a>
			<a href="<?php echo esc_url( $add_crop_url ); ?>" class="button fmp-quick-add-btn"><span class="dashicons dashicons-admin-site-alt3" aria-hidden="true"></span> <?php esc_html_e( 'Add Crop', 'farm-management' ); ?></a>
			<a href="<?php echo esc_url( $add_task_url ); ?>" class="button fmp-quick-add-btn"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> <?php esc_html_e( 'Add Task', 'farm-management' ); ?></a>
			<a href="<?php echo esc_url( $add_inventory_url ); ?>" class="button fmp-quick-add-btn"><span class="dashicons dashicons-cart" aria-hidden="true"></span> <?php esc_html_e( 'Add Inventory', 'farm-management' ); ?></a>
			<a href="<?php echo esc_url( $add_expense_url ); ?>" class="button fmp-quick-add-btn"><span class="dashicons dashicons-money-alt" aria-hidden="true"></span> <?php esc_html_e( 'Add Expense', 'farm-management' ); ?></a>
			<a href="<?php echo esc_url( $add_vaccination_url ); ?>" class="button fmp-quick-add-btn"><span class="dashicons dashicons-plus-alt" aria-hidden="true"></span> <?php esc_html_e( 'Add Vaccination', 'farm-management' ); ?></a>
		</div>
	</div>

	<div class="fmp-dashboard-cards">
		<div class="fmp-stat-card">
			<?php
			$n = $animal_counts['publish'];
			$tooltip = fmp_dashboard_stat_tooltip( $animal_counts['publish'], $animal_counts['draft'] );
			?>
			<span class="fmp-stat-value" <?php echo $tooltip ? ' title="' . esc_attr( $tooltip ) . '"' : ''; ?>><?php echo absint( $n ); ?></span>
			<span class="fmp-stat-label"><?php esc_html_e( 'Animals', 'farm-management' ); ?></span>
			<a href="<?php echo esc_url( $edit_animals ); ?>" class="fmp-stat-link"><?php esc_html_e( 'View all', 'farm-management' ); ?></a>
			<?php if ( $n === 0 && $animal_counts['draft'] === 0 ) : ?>
				<a href="<?php echo esc_url( $add_animal_url ); ?>" class="button button-primary fmp-stat-add"><?php esc_html_e( 'Add Animal', 'farm-management' ); ?></a>
			<?php endif; ?>
		</div>
		<div class="fmp-stat-card">
			<?php
			$n = $task_counts['publish'];
			$tooltip = fmp_dashboard_stat_tooltip( $task_counts['publish'], $task_counts['draft'] );
			?>
			<span class="fmp-stat-value" <?php echo $tooltip ? ' title="' . esc_attr( $tooltip ) . '"' : ''; ?>><?php echo absint( $n ); ?></span>
			<span class="fmp-stat-label"><?php esc_html_e( 'Tasks', 'farm-management' ); ?></span>
			<?php if ( post_type_exists( 'fmp_task' ) ) : ?>
				<a href="<?php echo esc_url( $edit_tasks ); ?>" class="fmp-stat-link"><?php esc_html_e( 'View all', 'farm-management' ); ?></a>
				<a href="<?php echo esc_url( $add_task_url ); ?>" class="button button-primary fmp-stat-add"><?php esc_html_e( 'Add Task', 'farm-management' ); ?></a>
			<?php endif; ?>
		</div>
		<div class="fmp-stat-card">
			<?php
			$n = $inventory_counts['publish'];
			$tooltip = fmp_dashboard_stat_tooltip( $inventory_counts['publish'], $inventory_counts['draft'] );
			if ( $inventory_low > 0 ) {
				$tooltip = $tooltip ? $tooltip . ' — ' : '';
				$tooltip .= sprintf( /* translators: %d: low stock count */ _n( '%d low stock', '%d low stock', $inventory_low, 'farm-management' ), $inventory_low );
			}
			?>
			<span class="fmp-stat-value" <?php echo $tooltip ? ' title="' . esc_attr( $tooltip ) . '"' : ''; ?>><?php echo absint( $n ); ?></span>
			<span class="fmp-stat-label"><?php esc_html_e( 'Inventory', 'farm-management' ); ?></span>
			<?php if ( post_type_exists( 'fmp_inventory_item' ) ) : ?>
				<a href="<?php echo esc_url( $edit_inventory ); ?>" class="fmp-stat-link"><?php esc_html_e( 'View all', 'farm-management' ); ?></a>
				<a href="<?php echo esc_url( $add_inventory_url ); ?>" class="button button-primary fmp-stat-add"><?php esc_html_e( 'Add Item', 'farm-management' ); ?></a>
			<?php endif; ?>
		</div>
		<div class="fmp-stat-card">
			<?php
			$n = $expense_counts['publish'];
			$tooltip = fmp_dashboard_stat_tooltip( $expense_counts['publish'], $expense_counts['draft'] );
			if ( $expenses_month > 0 ) {
				$tooltip = $tooltip ? $tooltip . ' — ' : '';
				$tooltip .= sprintf( __( 'Sum this month: R %s (ZAR)', 'farm-management' ), number_format_i18n( $expenses_month, 2 ) );
			}
			?>
			<span class="fmp-stat-value" <?php echo $tooltip ? ' title="' . esc_attr( $tooltip ) . '"' : ''; ?>><?php echo absint( $n ); ?></span>
			<span class="fmp-stat-label"><?php esc_html_e( 'Expenses', 'farm-management' ); ?></span>
			<?php if ( post_type_exists( 'fmp_expense' ) ) : ?>
				<a href="<?php echo esc_url( $edit_expenses ); ?>" class="fmp-stat-link"><?php esc_html_e( 'View all', 'farm-management' ); ?></a>
				<a href="<?php echo esc_url( $add_expense_url ); ?>" class="button button-primary fmp-stat-add"><?php esc_html_e( 'Add Expense', 'farm-management' ); ?></a>
			<?php endif; ?>
		</div>
		<div class="fmp-stat-card">
			<?php
			$n = $vacc_counts['publish'];
			$tooltip = fmp_dashboard_stat_tooltip( $vacc_counts['publish'], $vacc_counts['draft'] );
			?>
			<span class="fmp-stat-value" <?php echo $tooltip ? ' title="' . esc_attr( $tooltip ) . '"' : ''; ?>><?php echo absint( $n ); ?></span>
			<span class="fmp-stat-label"><?php esc_html_e( 'Vaccinations', 'farm-management' ); ?></span>
			<a href="<?php echo esc_url( $edit_vaccinations ); ?>" class="fmp-stat-link"><?php esc_html_e( 'View all', 'farm-management' ); ?></a>
			<a href="<?php echo esc_url( $add_vaccination_url ); ?>" class="button button-primary fmp-stat-add"><?php esc_html_e( 'Add Vaccination', 'farm-management' ); ?></a>
		</div>
	</div>

	<div class="fmp-dashboard-widgets">
		<!-- Overdue Vaccinations (top priority) -->
		<div class="fmp-dashboard-widget fmp-widget-overdue-vacc">
			<h2 class="fmp-widget-title"><?php esc_html_e( 'Overdue Vaccinations', 'farm-management' ); ?></h2>
			<?php if ( ! empty( $overdue_vaccinations ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Animal', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Vaccine', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Next due', 'farm-management' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $overdue_vaccinations as $v ) : ?>
							<?php
							$animal_id   = (int) get_post_meta( $v->ID, '_fmp_animal_id', true );
							$animal_name = $animal_id ? get_the_title( $animal_id ) : '—';
							$vaccine     = get_post_meta( $v->ID, '_fmp_vaccine_name', true );
							$next_due    = get_post_meta( $v->ID, '_fmp_next_due_date', true );
							?>
							<tr>
								<td><?php echo esc_html( $animal_name ); ?></td>
								<td><?php echo esc_html( $vaccine ?: '—' ); ?></td>
								<td><span style="color: #b32d2e; font-weight: 600;"><?php echo esc_html( $next_due ?: '—' ); ?></span></td>
								<td><a href="<?php echo esc_url( get_edit_post_link( $v->ID ) ); ?>"><?php esc_html_e( 'Edit', 'farm-management' ); ?></a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="fmp-widget-view-all"><a href="<?php echo esc_url( $view_all_overdue_vacc ); ?>"><?php esc_html_e( 'View all', 'farm-management' ); ?></a></p>
			<?php else : ?>
				<div class="fmp-empty-state">
					<p><?php esc_html_e( 'No overdue vaccinations.', 'farm-management' ); ?></p>
					<p class="fmp-widget-view-all"><a href="<?php echo esc_url( $edit_vaccinations ); ?>"><?php esc_html_e( 'View all vaccinations', 'farm-management' ); ?></a></p>
				</div>
			<?php endif; ?>
		</div>

		<!-- Vaccinations Due Soon (next N days) -->
		<div class="fmp-dashboard-widget fmp-widget-due-soon-vacc">
			<h2 class="fmp-widget-title"><?php echo esc_html( sprintf( /* translators: %d: number of days */ _n( 'Vaccinations Due Soon (next %d day)', 'Vaccinations Due Soon (next %d days)', $due_soon_days, 'farm-management' ), $due_soon_days ) ); ?></h2>
			<?php if ( ! empty( $vaccinations_due_soon ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Animal', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Vaccine', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Next due', 'farm-management' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $vaccinations_due_soon as $v ) : ?>
							<?php
							$animal_id   = (int) get_post_meta( $v->ID, '_fmp_animal_id', true );
							$animal_name = $animal_id ? get_the_title( $animal_id ) : '—';
							$vaccine     = get_post_meta( $v->ID, '_fmp_vaccine_name', true );
							$next_due    = get_post_meta( $v->ID, '_fmp_next_due_date', true );
							?>
							<tr>
								<td><?php echo esc_html( $animal_name ); ?></td>
								<td><?php echo esc_html( $vaccine ?: '—' ); ?></td>
								<td><?php echo esc_html( $next_due ?: '—' ); ?></td>
								<td><a href="<?php echo esc_url( get_edit_post_link( $v->ID ) ); ?>"><?php esc_html_e( 'Edit', 'farm-management' ); ?></a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="fmp-widget-view-all"><a href="<?php echo esc_url( $view_all_due_soon_vacc ); ?>"><?php esc_html_e( 'View all', 'farm-management' ); ?></a></p>
			<?php else : ?>
				<div class="fmp-empty-state">
					<p><?php echo esc_html( sprintf( /* translators: %d: number of days */ _n( 'No vaccinations due in the next %d day.', 'No vaccinations due in the next %d days.', $due_soon_days, 'farm-management' ), $due_soon_days ) ); ?></p>
					<p class="fmp-widget-view-all"><a href="<?php echo esc_url( $view_all_due_soon_vacc ); ?>"><?php esc_html_e( 'View all vaccinations', 'farm-management' ); ?></a></p>
				</div>
			<?php endif; ?>
		</div>

		<!-- Tasks Due Soon (next 7 days), status != done -->
		<div class="fmp-dashboard-widget fmp-widget-tasks-due-soon">
			<h2 class="fmp-widget-title"><?php esc_html_e( 'Tasks Due Soon (next 7 days)', 'farm-management' ); ?></h2>
			<?php if ( post_type_exists( 'fmp_task' ) && ! empty( $tasks_due_soon ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Task', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Due date', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Status', 'farm-management' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $tasks_due_soon as $t ) : ?>
							<?php
							$due_date = get_post_meta( $t->ID, '_fmp_due_date', true );
							$status   = get_post_meta( $t->ID, '_fmp_status', true );
							?>
							<tr>
								<td><?php echo esc_html( get_the_title( $t->ID ) ); ?></td>
								<td><?php echo esc_html( $due_date ?: '—' ); ?></td>
								<td><?php echo esc_html( $status ?: '—' ); ?></td>
								<td><a href="<?php echo esc_url( get_edit_post_link( $t->ID ) ); ?>"><?php esc_html_e( 'Edit', 'farm-management' ); ?></a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="fmp-widget-view-all"><a href="<?php echo esc_url( $view_all_tasks_due ); ?>"><?php esc_html_e( 'View all', 'farm-management' ); ?></a></p>
			<?php elseif ( post_type_exists( 'fmp_task' ) ) : ?>
				<div class="fmp-empty-state">
					<p><?php esc_html_e( 'No tasks due in the next 7 days.', 'farm-management' ); ?></p>
					<p class="fmp-widget-view-all"><a href="<?php echo esc_url( $view_all_tasks_due ); ?>"><?php esc_html_e( 'View all tasks', 'farm-management' ); ?></a></p>
				</div>
			<?php else : ?>
				<div class="fmp-empty-state">
					<p><?php esc_html_e( 'Tasks will appear here once the Tasks module is set up.', 'farm-management' ); ?></p>
				</div>
			<?php endif; ?>
		</div>

		<!-- Low Stock Items (quantity <= reorder_level) -->
		<div class="fmp-dashboard-widget fmp-widget-low-stock">
			<h2 class="fmp-widget-title"><?php esc_html_e( 'Low Stock Items', 'farm-management' ); ?></h2>
			<?php if ( post_type_exists( 'fmp_inventory_item' ) && ! empty( $low_stock_items ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Item', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Quantity', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Reorder level', 'farm-management' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $low_stock_items as $item ) : ?>
							<?php
							$quantity   = get_post_meta( $item->ID, '_fmp_quantity', true );
							$reorder    = get_post_meta( $item->ID, '_fmp_reorder_level', true );
							$edit_url   = admin_url( 'admin.php?page=fmp-inventory&action=edit&id=' . $item->ID );
							?>
							<tr>
								<td><?php echo esc_html( get_the_title( $item->ID ) ); ?></td>
								<td><span style="color: #b32d2e; font-weight: 600;"><?php echo esc_html( $quantity ); ?></span></td>
								<td><?php echo esc_html( $reorder ?: '—' ); ?></td>
								<td><a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'farm-management' ); ?></a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="fmp-widget-view-all"><a href="<?php echo esc_url( $view_all_low_stock ); ?>"><?php esc_html_e( 'View all', 'farm-management' ); ?></a></p>
			<?php elseif ( post_type_exists( 'fmp_inventory_item' ) ) : ?>
				<div class="fmp-empty-state">
					<p><?php esc_html_e( 'No low stock items.', 'farm-management' ); ?></p>
					<p class="fmp-widget-view-all"><a href="<?php echo esc_url( $view_all_low_stock ); ?>"><?php esc_html_e( 'View all inventory', 'farm-management' ); ?></a></p>
				</div>
			<?php else : ?>
				<div class="fmp-empty-state">
					<p><?php esc_html_e( 'Inventory will appear here once the Inventory module is set up.', 'farm-management' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
