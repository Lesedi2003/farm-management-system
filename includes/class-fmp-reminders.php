<?php
/**
 * Vaccination email reminders: daily cron, summary email, test and run-now actions.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Reminders
 */
class FMP_Reminders {

	const CRON_HOOK       = 'fmp_daily_reminders';
	const OPTION_LAST_YMD = 'fmp_last_reminder_sent_ymd';

	const TEST_EMAIL_ACTION   = 'fmp_send_test_email';
	const RUN_REMINDERS_ACTION = 'fmp_run_reminders_now';
	const TEST_EMAIL_NONCE    = 'fmp_test_email_nonce';
	const RUN_REMINDERS_NONCE = 'fmp_run_reminders_nonce';
	const TRANSIENT_RUN_RESULT = 'fmp_reminders_run_result';

	/** Setting keys (stored in fmp_settings option). */
	const KEY_REMINDERS_ENABLED = 'reminders_enabled';
	const KEY_REMINDER_HOUR     = 'reminder_hour';

	const DEFAULT_REMINDER_HOUR = 7;

	/**
	 * Constructor. Hooks into WordPress.
	 */
	public function __construct() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_daily_reminders' ), 10 );
		add_action( 'admin_post_' . self::TEST_EMAIL_ACTION, array( __CLASS__, 'handle_send_test_email' ), 10 );
		add_action( 'admin_post_' . self::RUN_REMINDERS_ACTION, array( __CLASS__, 'handle_run_reminders_now' ), 10 );
		add_action( 'update_option_' . FMP_Settings::OPTION_NAME, array( __CLASS__, 'maybe_reschedule_on_settings_save' ), 10, 3 );
	}

	/**
	 * Schedule daily cron at configured hour (site timezone). Call on activation.
	 */
	public static function schedule_event() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
		$timestamp = self::get_next_run_timestamp();
		if ( $timestamp ) {
			wp_schedule_event( $timestamp, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Clear scheduled cron. Call on deactivation.
	 */
	public static function unschedule_event() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Get next run timestamp (next occurrence of reminder hour in site timezone).
	 *
	 * @return int|false Unix timestamp or false.
	 */
	public static function get_next_run_timestamp() {
		if ( ! function_exists( 'wp_timezone' ) ) {
			return strtotime( 'tomorrow 7:00' );
		}
		$tz   = wp_timezone();
		$now  = new DateTime( 'now', $tz );
		$hour = (int) self::get_reminder_hour();
		$next = clone $now;
		$next->setTime( $hour, 0, 0 );
		if ( $next <= $now ) {
			$next->modify( '+1 day' );
		}
		return $next->getTimestamp();
	}

	/**
	 * Reschedule cron when reminder hour or enabled state might have changed.
	 *
	 * @param mixed $old_value Old option value.
	 * @param mixed $value     New option value.
	 * @param string $option   Option name.
	 */
	public static function maybe_reschedule_on_settings_save( $old_value, $value, $option ) {
		if ( $option !== FMP_Settings::OPTION_NAME || ! is_array( $value ) ) {
			return;
		}
		$old_enabled = isset( $old_value[ self::KEY_REMINDERS_ENABLED ] ) ? (int) $old_value[ self::KEY_REMINDERS_ENABLED ] : 1;
		$new_enabled = isset( $value[ self::KEY_REMINDERS_ENABLED ] ) ? (int) $value[ self::KEY_REMINDERS_ENABLED ] : 1;
		$old_hour    = isset( $old_value[ self::KEY_REMINDER_HOUR ] ) ? (int) $old_value[ self::KEY_REMINDER_HOUR ] : self::DEFAULT_REMINDER_HOUR;
		$new_hour    = isset( $value[ self::KEY_REMINDER_HOUR ] ) ? (int) $value[ self::KEY_REMINDER_HOUR ] : self::DEFAULT_REMINDER_HOUR;
		if ( $old_enabled !== $new_enabled || $old_hour !== $new_hour ) {
			self::schedule_event();
		}
	}

	/**
	 * Check if reminders are enabled (from settings).
	 *
	 * @return bool
	 */
	public static function reminders_enabled() {
		$v = FMP_Settings::get( self::KEY_REMINDERS_ENABLED );
		if ( $v === null ) {
			return true;
		}
		return (int) $v === 1;
	}

	/**
	 * Get reminder hour (0–23) from settings.
	 *
	 * @return int
	 */
	public static function get_reminder_hour() {
		$v = FMP_Settings::get( self::KEY_REMINDER_HOUR );
		if ( $v === null ) {
			return self::DEFAULT_REMINDER_HOUR;
		}
		return max( 0, min( 23, (int) $v ) );
	}

	/**
	 * Cron callback: run reminder logic. Exits early if disabled or already sent today (anti-spam).
	 */
	public static function run_daily_reminders() {
		if ( ! self::reminders_enabled() ) {
			return;
		}
		$today_ymd = self::get_today_ymd();
		$last_sent = get_option( self::OPTION_LAST_YMD, '' );
		if ( $last_sent === $today_ymd ) {
			return;
		}
		self::send_reminder_email();
		update_option( self::OPTION_LAST_YMD, $today_ymd );
	}

	/**
	 * Get today's date in site timezone (Y-m-d).
	 *
	 * @return string
	 */
	public static function get_today_ymd() {
		if ( function_exists( 'wp_timezone' ) ) {
			$tz = wp_timezone();
			$dt = new DateTime( 'now', $tz );
			return $dt->format( 'Y-m-d' );
		}
		return gmdate( 'Y-m-d' );
	}

	/**
	 * Build overdue and due_soon vaccination rows and send one summary email to managers.
	 *
	 * @param bool $skip_antispam If true, do not check last_sent_ymd (for "Run Now").
	 * @return array|null Stats from send_reminder_email, or null if not run.
	 */
	public static function run_reminders_now( $skip_antispam = true ) {
		if ( ! self::reminders_enabled() ) {
			return null;
		}
		if ( ! $skip_antispam ) {
			$today_ymd = self::get_today_ymd();
			$last_sent = get_option( self::OPTION_LAST_YMD, '' );
			if ( $last_sent === $today_ymd ) {
				return null;
			}
		}
		$stats = self::send_reminder_email();
		update_option( self::OPTION_LAST_YMD, self::get_today_ymd() );
		return $stats;
	}

	/**
	 * Query vaccinations with next_due_date, group into overdue/due_soon, send HTML email.
	 *
	 * @return array{overdue: int, due_soon: int, recipients: int, emails_sent: int, wp_mail_success: bool}
	 */
	protected static function send_reminder_email() {
		$due_soon_days = (int) FMP_Settings::get( FMP_Settings::KEY_DUE_SOON_DAYS );
		if ( $due_soon_days < 1 ) {
			$due_soon_days = (int) FMP_Settings::get( FMP_Settings::KEY_VACCINATION_DAYS );
		}
		$due_soon_days = $due_soon_days >= 1 ? $due_soon_days : FMP_Settings::DEFAULT_DUE_SOON_DAYS;

		$today_ymd = self::get_today_ymd();
		$today_ts  = strtotime( $today_ymd );
		$end_ymd   = gmdate( 'Y-m-d', strtotime( '+' . $due_soon_days . ' days', $today_ts ) );

		$query = new WP_Query( array(
			'post_type'      => 'fmp_vaccination',
			'post_status'    => 'publish',
			'posts_per_page' => 500,
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

		$overdue  = array();
		$due_soon = array();
		foreach ( $query->posts as $post ) {
			$next_due = get_post_meta( $post->ID, '_fmp_next_due_date', true );
			if ( ! $next_due || ! FMP_Vaccinations::is_valid_date( $next_due ) ) {
				continue;
			}
			$animal_id = (int) get_post_meta( $post->ID, '_fmp_animal_id', true );
			$animal_label = self::get_animal_label( $animal_id );
			$vaccine  = get_post_meta( $post->ID, '_fmp_vaccine_name', true );
			$location = get_post_meta( $post->ID, '_fmp_vaccination_location', true );
			$location = $location !== '' ? $location : '—';
			$status   = FMP_Vaccinations::get_vaccination_status( $next_due );
			$status_label = ( $status === 'overdue' ) ? __( 'Overdue', 'farm-management' ) : ( ( $status === 'due_soon' ) ? __( 'Due Soon', 'farm-management' ) : __( 'OK', 'farm-management' ) );
			$row = array(
				'animal'   => $animal_label,
				'vaccine'  => $vaccine ?: '—',
				'next_due' => $next_due,
				'status'   => $status_label,
				'location' => $location,
			);
			if ( $status === 'overdue' ) {
				$overdue[] = $row;
			} else {
				$due_soon[] = $row;
			}
		}

		$recipients = self::get_reminder_recipients();
		$recipients_count = count( $recipients );
		$stats = array(
			'overdue'         => count( $overdue ),
			'due_soon'        => count( $due_soon ),
			'recipients'      => $recipients_count,
			'emails_sent'     => 0,
			'wp_mail_success' => false,
			'last_error'      => '',
			'from_email'      => class_exists( 'FMP_Smtp' ) ? FMP_Smtp::get_effective_from_email() : get_option( 'admin_email' ),
		);

		if ( empty( $recipients ) ) {
			return $stats;
		}

		$last_error = '';
		$capture_error = function ( $wp_error ) use ( &$last_error ) {
			if ( $wp_error && is_wp_error( $wp_error ) ) {
				$last_error = $wp_error->get_error_message();
			}
		};
		add_action( 'wp_mail_failed', $capture_error, 10, 1 );

		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] Vaccination reminders', 'farm-management' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);
		$body    = self::build_email_body( $overdue, $due_soon );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$all_ok = true;
		foreach ( $recipients as $to ) {
			$sent = wp_mail( $to, $subject, $body, $headers );
			if ( $sent ) {
				$stats['emails_sent']++;
			} else {
				$all_ok = false;
			}
		}

		remove_action( 'wp_mail_failed', $capture_error, 10 );
		$stats['wp_mail_success'] = $all_ok && $stats['emails_sent'] === $recipients_count;
		$stats['last_error'] = $last_error;
		if ( $last_error !== '' && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( '[FMP Reminders] wp_mail failed: ' . $last_error );
		}
		return $stats;
	}

	/**
	 * Get animal display label (tag or title).
	 *
	 * @param int $animal_id Animal post ID.
	 * @return string
	 */
	protected static function get_animal_label( $animal_id ) {
		if ( ! $animal_id ) {
			return '—';
		}
		$tag = get_post_meta( $animal_id, '_fmp_tag', true );
		if ( $tag !== '' ) {
			return $tag;
		}
		$post = get_post( $animal_id );
		return $post ? $post->post_title : '—';
	}

	/**
	 * Get list of email addresses to receive reminders (farm_manager users; fallback admin_email).
	 *
	 * @return string[]
	 */
	protected static function get_reminder_recipients() {
		$users = get_users( array(
			'role'   => FMP_Capabilities::ROLE_MANAGER,
			'fields' => array( 'user_email' ),
			'number' => 50,
		) );
		$emails = array();
		foreach ( $users as $u ) {
			if ( ! empty( $u->user_email ) && is_email( $u->user_email ) ) {
				$emails[] = $u->user_email;
			}
		}
		if ( empty( $emails ) ) {
			$admin = get_option( 'admin_email' );
			if ( is_email( $admin ) ) {
				$emails[] = $admin;
			}
		}
		return array_unique( $emails );
	}

	/**
	 * Build HTML email body with Overdue and Due Soon tables.
	 *
	 * @param array[] $overdue  Rows for overdue vaccinations.
	 * @param array[] $due_soon Rows for due soon vaccinations.
	 * @return string
	 */
	protected static function build_email_body( $overdue, $due_soon ) {
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$today     = self::get_today_ymd();
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<style type="text/css">
				body { font-family: sans-serif; line-height: 1.5; color: #1e1e1e; }
				table { border-collapse: collapse; width: 100%; margin-bottom: 1.5em; }
				th, td { border: 1px solid #c3c4c7; padding: 8px 12px; text-align: left; }
				th { background: #f0f0f1; }
				h2 { margin-top: 1.5em; margin-bottom: 0.5em; font-size: 1.1em; }
				.fmp-overdue { color: #b32d2e; }
			</style>
		</head>
		<body>
			<p><?php echo esc_html( sprintf( __( 'Daily vaccination reminder for %s (%s).', 'farm-management' ), $site_name, $today ) ); ?></p>
			<?php if ( ! empty( $overdue ) ) : ?>
				<h2><?php esc_html_e( 'Overdue', 'farm-management' ); ?></h2>
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Animal', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Vaccine', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Next Due Date', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Status', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Location', 'farm-management' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $overdue as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['animal'] ); ?></td>
								<td><?php echo esc_html( $row['vaccine'] ); ?></td>
								<td><?php echo esc_html( $row['next_due'] ); ?></td>
								<td class="fmp-overdue"><?php echo esc_html( $row['status'] ); ?></td>
								<td><?php echo esc_html( $row['location'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			<?php if ( ! empty( $due_soon ) ) : ?>
				<h2><?php esc_html_e( 'Due Soon', 'farm-management' ); ?></h2>
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Animal', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Vaccine', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Next Due Date', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Status', 'farm-management' ); ?></th>
							<th><?php esc_html_e( 'Location', 'farm-management' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $due_soon as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['animal'] ); ?></td>
								<td><?php echo esc_html( $row['vaccine'] ); ?></td>
								<td><?php echo esc_html( $row['next_due'] ); ?></td>
								<td><?php echo esc_html( $row['status'] ); ?></td>
								<td><?php echo esc_html( $row['location'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			<?php if ( empty( $overdue ) && empty( $due_soon ) ) : ?>
				<p><?php esc_html_e( 'No vaccinations are overdue or due soon.', 'farm-management' ); ?></p>
			<?php endif; ?>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Send test email to current user. Requires fmp_manage_settings.
	 */
	public static function handle_send_test_email() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), self::TEST_EMAIL_NONCE ) ) {
			wp_die( esc_html__( 'Security check failed.', 'farm-management' ), '', array( 'response' => 403 ) );
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_SETTINGS ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'farm-management' ), '', array( 'response' => 403 ) );
		}
		$user = wp_get_current_user();
		if ( ! $user->ID || ! is_email( $user->user_email ) ) {
			wp_die( esc_html__( 'No valid email address for current user.', 'farm-management' ), '', array( 'response' => 400 ) );
		}
		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] Vaccination reminder test', 'farm-management' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);
		$body = self::build_email_body( array(), array() );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		wp_mail( $user->user_email, $subject, $body, $headers );
		wp_safe_redirect( add_query_arg( 'fmp_test_email_sent', '1', admin_url( 'admin.php?page=fmp-settings' ) ) );
		exit;
	}

	/**
	 * Run reminders immediately (demo). Requires fmp_manage_farm.
	 */
	public static function handle_run_reminders_now() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), self::RUN_REMINDERS_NONCE ) ) {
			wp_die( esc_html__( 'Security check failed.', 'farm-management' ), '', array( 'response' => 403 ) );
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'farm-management' ), '', array( 'response' => 403 ) );
		}
		$stats = self::run_reminders_now( true );
		$redirect_url = admin_url( 'admin.php?page=fmp-settings' );
		if ( is_array( $stats ) ) {
			$message = self::build_run_result_log_message( $stats );
			set_transient( self::TRANSIENT_RUN_RESULT, $stats, 60 );
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( '[FMP Reminders] ' . $message );
			}
		}
		wp_safe_redirect( add_query_arg( 'fmp_reminders_run', '1', $redirect_url ) );
		exit;
	}

	/**
	 * Build the run-result log line (same format for debug.log).
	 *
	 * @param array $stats Stats from send_reminder_email.
	 * @return string
	 */
	public static function build_run_result_log_message( $stats ) {
		$msg = sprintf(
			'Reminders complete — Overdue: %d, Due Soon: %d, Recipients: %d, Emails sent: %d, wp_mail success: %s.',
			$stats['overdue'],
			$stats['due_soon'],
			$stats['recipients'],
			$stats['emails_sent'],
			$stats['wp_mail_success'] ? 'true' : 'false'
		);
		if ( ! empty( $stats['last_error'] ) ) {
			$msg .= ' Error: ' . $stats['last_error'];
		}
		if ( ! empty( $stats['from_email'] ) ) {
			$msg .= ' From: ' . $stats['from_email'];
		}
		return $msg;
	}

	/**
	 * Build the run-result notice message from stats (for admin notice).
	 *
	 * @param array $stats Stats from send_reminder_email.
	 * @return string
	 */
	public static function format_run_result_message( $stats ) {
		$msg = sprintf(
			/* translators: %d: overdue count, %d: due soon count, %d: recipients, %d: emails sent, %s: true/false */
			__( 'Reminders complete — Overdue: %d, Due Soon: %d, Recipients: %d, Emails sent: %d, wp_mail success: %s.', 'farm-management' ),
			$stats['overdue'],
			$stats['due_soon'],
			$stats['recipients'],
			$stats['emails_sent'],
			$stats['wp_mail_success'] ? 'true' : 'false'
		);
		if ( ! empty( $stats['last_error'] ) ) {
			$msg .= ' ' . __( 'Error:', 'farm-management' ) . ' ' . $stats['last_error'];
		}
		if ( ! empty( $stats['from_email'] ) ) {
			$msg .= ' ' . __( 'From:', 'farm-management' ) . ' ' . $stats['from_email'];
		}
		return $msg;
	}
}
