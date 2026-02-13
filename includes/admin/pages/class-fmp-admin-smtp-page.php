<?php
/**
 * Email / SMTP settings page under Farm Management menu.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Admin_Smtp_Page
 */
class FMP_Admin_Smtp_Page {

	const PAGE_SLUG       = 'fmp-email-smtp';
	const OPTION_GROUP    = 'fmp-smtp-settings';
	const TEST_ACTION     = 'fmp_smtp_send_test_email';
	const TEST_NONCE_NAME = 'fmp_smtp_test_nonce';

	/**
	 * Register menu, settings, and test-email handler.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ), 10 );
		add_action( 'admin_post_' . self::TEST_ACTION, array( __CLASS__, 'handle_send_test_email' ), 10 );
	}

	/**
	 * Register setting and sections/fields.
	 */
	public static function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			FMP_Smtp::OPTION_NAME,
			array(
				'type'              => 'array',
				'description'       => __( 'Farm Management SMTP / email settings.', 'farm-management' ),
				'sanitize_callback' => array( __CLASS__, 'sanitize_options' ),
				'default'           => FMP_Smtp::get_defaults(),
			)
		);

		add_settings_section(
			'fmp_smtp_main',
			__( 'SMTP Configuration', 'farm-management' ),
			array( __CLASS__, 'render_section_smtp' ),
			self::OPTION_GROUP
		);

		add_settings_field(
			'fmp_smtp_enabled',
			__( 'Enable SMTP', 'farm-management' ),
			array( __CLASS__, 'render_field_enabled' ),
			self::OPTION_GROUP,
			'fmp_smtp_main',
			array( 'label_for' => 'fmp_smtp_enabled' )
		);
		add_settings_field(
			'fmp_smtp_host',
			__( 'SMTP Host', 'farm-management' ),
			array( __CLASS__, 'render_field_host' ),
			self::OPTION_GROUP,
			'fmp_smtp_main',
			array( 'label_for' => 'fmp_smtp_host' )
		);
		add_settings_field(
			'fmp_smtp_port',
			__( 'SMTP Port', 'farm-management' ),
			array( __CLASS__, 'render_field_port' ),
			self::OPTION_GROUP,
			'fmp_smtp_main',
			array( 'label_for' => 'fmp_smtp_port' )
		);
		add_settings_field(
			'fmp_smtp_encryption',
			__( 'Encryption', 'farm-management' ),
			array( __CLASS__, 'render_field_encryption' ),
			self::OPTION_GROUP,
			'fmp_smtp_main',
			array( 'label_for' => 'fmp_smtp_encryption' )
		);
		add_settings_field(
			'fmp_smtp_username',
			__( 'SMTP Username', 'farm-management' ),
			array( __CLASS__, 'render_field_username' ),
			self::OPTION_GROUP,
			'fmp_smtp_main',
			array( 'label_for' => 'fmp_smtp_username' )
		);
		add_settings_field(
			'fmp_smtp_password',
			__( 'SMTP Password', 'farm-management' ),
			array( __CLASS__, 'render_field_password' ),
			self::OPTION_GROUP,
			'fmp_smtp_main',
			array( 'label_for' => 'fmp_smtp_password' )
		);
		add_settings_field(
			'fmp_smtp_from_name',
			__( 'From Name', 'farm-management' ),
			array( __CLASS__, 'render_field_from_name' ),
			self::OPTION_GROUP,
			'fmp_smtp_main',
			array( 'label_for' => 'fmp_smtp_from_name' )
		);
		add_settings_field(
			'fmp_smtp_from_email',
			__( 'From Email', 'farm-management' ),
			array( __CLASS__, 'render_field_from_email' ),
			self::OPTION_GROUP,
			'fmp_smtp_main',
			array( 'label_for' => 'fmp_smtp_from_email' )
		);
	}

	/**
	 * Sanitize SMTP options.
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized values.
	 */
	public static function sanitize_options( $input ) {
		if ( ! is_array( $input ) ) {
			return FMP_Smtp::get_defaults();
		}
		$defaults = FMP_Smtp::get_defaults();
		$out      = array();

		$out[ FMP_Smtp::KEY_ENABLED ] = isset( $input[ FMP_Smtp::KEY_ENABLED ] ) ? 1 : 0;
		$out[ FMP_Smtp::KEY_HOST ]    = isset( $input[ FMP_Smtp::KEY_HOST ] )
			? sanitize_text_field( wp_unslash( $input[ FMP_Smtp::KEY_HOST ] ) )
			: ( isset( $defaults[ FMP_Smtp::KEY_HOST ] ) ? $defaults[ FMP_Smtp::KEY_HOST ] : '' );
		$out[ FMP_Smtp::KEY_PORT ]    = isset( $input[ FMP_Smtp::KEY_PORT ] )
			? absint( $input[ FMP_Smtp::KEY_PORT ] )
			: ( isset( $defaults[ FMP_Smtp::KEY_PORT ] ) ? $defaults[ FMP_Smtp::KEY_PORT ] : 587 );
		$out[ FMP_Smtp::KEY_PORT ]    = max( 1, min( 65535, $out[ FMP_Smtp::KEY_PORT ] ) );
		$enc = isset( $input[ FMP_Smtp::KEY_ENCRYPTION ] ) ? sanitize_text_field( wp_unslash( $input[ FMP_Smtp::KEY_ENCRYPTION ] ) ) : '';
		$out[ FMP_Smtp::KEY_ENCRYPTION ] = in_array( $enc, array( 'none', 'ssl', 'tls' ), true ) ? $enc : 'tls';
		$out[ FMP_Smtp::KEY_USERNAME ] = isset( $input[ FMP_Smtp::KEY_USERNAME ] )
			? sanitize_text_field( wp_unslash( $input[ FMP_Smtp::KEY_USERNAME ] ) )
			: '';
		$out[ FMP_Smtp::KEY_FROM_NAME ]  = isset( $input[ FMP_Smtp::KEY_FROM_NAME ] )
			? sanitize_text_field( wp_unslash( $input[ FMP_Smtp::KEY_FROM_NAME ] ) )
			: ( isset( $defaults[ FMP_Smtp::KEY_FROM_NAME ] ) ? $defaults[ FMP_Smtp::KEY_FROM_NAME ] : '' );
		$out[ FMP_Smtp::KEY_FROM_EMAIL ] = isset( $input[ FMP_Smtp::KEY_FROM_EMAIL ] )
			? sanitize_email( wp_unslash( $input[ FMP_Smtp::KEY_FROM_EMAIL ] ) )
			: '';

		$pass_input = isset( $input[ FMP_Smtp::KEY_PASSWORD ] ) ? wp_unslash( $input[ FMP_Smtp::KEY_PASSWORD ] ) : '';
		if ( $pass_input !== '' ) {
			$out[ FMP_Smtp::KEY_PASSWORD ] = $pass_input;
		} else {
			$existing = get_option( FMP_Smtp::OPTION_NAME, array() );
			$out[ FMP_Smtp::KEY_PASSWORD ] = is_array( $existing ) && isset( $existing[ FMP_Smtp::KEY_PASSWORD ] )
				? $existing[ FMP_Smtp::KEY_PASSWORD ]
				: '';
		}

		return $out;
	}

	/**
	 * Render section description.
	 */
	public static function render_section_smtp() {
		echo '<p class="description">' . esc_html__( 'Configure SMTP so that emails (reminders, test mail) are sent reliably instead of PHP mail(). Leave Enable SMTP off to use the server default.', 'farm-management' ) . '</p>';
	}

	/**
	 * Render Enable SMTP checkbox.
	 */
	public static function render_field_enabled() {
		$val  = FMP_Smtp::get_option( FMP_Smtp::KEY_ENABLED );
		$name = FMP_Smtp::OPTION_NAME . '[' . FMP_Smtp::KEY_ENABLED . ']';
		?>
		<label for="fmp_smtp_enabled">
			<input type="checkbox" id="fmp_smtp_enabled" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( (int) $val, 1 ); ?> />
			<?php esc_html_e( 'Enable SMTP', 'farm-management' ); ?>
		</label>
		<?php
	}

	/**
	 * Render SMTP Host.
	 */
	public static function render_field_host() {
		$val  = FMP_Smtp::get_option( FMP_Smtp::KEY_HOST );
		$name = FMP_Smtp::OPTION_NAME . '[' . FMP_Smtp::KEY_HOST . ']';
		?>
		<input type="text" id="fmp_smtp_host" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $val ); ?>" class="regular-text" placeholder="smtp.example.com" />
		<?php
	}

	/**
	 * Render SMTP Port.
	 */
	public static function render_field_port() {
		$val  = FMP_Smtp::get_option( FMP_Smtp::KEY_PORT );
		$name = FMP_Smtp::OPTION_NAME . '[' . FMP_Smtp::KEY_PORT . ']';
		?>
		<input type="number" id="fmp_smtp_port" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $val ); ?>" min="1" max="65535" class="small-text" />
		<p class="description"><?php esc_html_e( 'Default 587 (TLS). Common: 465 (SSL), 25 (none).', 'farm-management' ); ?></p>
		<?php
	}

	/**
	 * Render Encryption select.
	 */
	public static function render_field_encryption() {
		$val  = FMP_Smtp::get_option( FMP_Smtp::KEY_ENCRYPTION );
		$name = FMP_Smtp::OPTION_NAME . '[' . FMP_Smtp::KEY_ENCRYPTION . ']';
		?>
		<select id="fmp_smtp_encryption" name="<?php echo esc_attr( $name ); ?>">
			<option value="none" <?php selected( $val, 'none' ); ?>><?php esc_html_e( 'None', 'farm-management' ); ?></option>
			<option value="ssl" <?php selected( $val, 'ssl' ); ?>>SSL</option>
			<option value="tls" <?php selected( $val, 'tls' ); ?>>TLS</option>
		</select>
		<?php
	}

	/**
	 * Render SMTP Username.
	 */
	public static function render_field_username() {
		$val  = FMP_Smtp::get_option( FMP_Smtp::KEY_USERNAME );
		$name = FMP_Smtp::OPTION_NAME . '[' . FMP_Smtp::KEY_USERNAME . ']';
		?>
		<input type="text" id="fmp_smtp_username" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $val ); ?>" class="regular-text" autocomplete="off" />
		<?php
	}

	/**
	 * Render SMTP Password (hidden in UI; leave blank to keep).
	 */
	public static function render_field_password() {
		$name = FMP_Smtp::OPTION_NAME . '[' . FMP_Smtp::KEY_PASSWORD . ']';
		$has  = FMP_Smtp::get_option( FMP_Smtp::KEY_PASSWORD ) !== '';
		?>
		<input type="password" id="fmp_smtp_password" name="<?php echo esc_attr( $name ); ?>" value="" class="regular-text" autocomplete="new-password" />
		<p class="description"><?php esc_html_e( 'Leave blank to keep current password.', 'farm-management' ); ?><?php echo $has ? ' ' . esc_html__( 'Password is set.', 'farm-management' ) : ''; ?></p>
		<?php
	}

	/**
	 * Render From Name.
	 */
	public static function render_field_from_name() {
		$val  = FMP_Smtp::get_option( FMP_Smtp::KEY_FROM_NAME );
		$name = FMP_Smtp::OPTION_NAME . '[' . FMP_Smtp::KEY_FROM_NAME . ']';
		?>
		<input type="text" id="fmp_smtp_from_name" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $val ); ?>" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Default: Farm Management.', 'farm-management' ); ?></p>
		<?php
	}

	/**
	 * Render From Email.
	 */
	public static function render_field_from_email() {
		$val  = FMP_Smtp::get_option( FMP_Smtp::KEY_FROM_EMAIL );
		$name = FMP_Smtp::OPTION_NAME . '[' . FMP_Smtp::KEY_FROM_EMAIL . ']';
		?>
		<input type="email" id="fmp_smtp_from_email" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $val ); ?>" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Default: site admin email.', 'farm-management' ); ?></p>
		<?php
	}

	/**
	 * Render the Email / SMTP page (form + test section).
	 */
	public static function render() {
		if ( ! current_user_can( FMP_Capabilities::MANAGE_SETTINGS ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'farm-management' ), '', array( 'response' => 403 ) );
		}
		$page_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		?>
		<div class="wrap fmp-admin-wrap fmp-email-smtp">
			<h1><?php esc_html_e( 'Email / SMTP', 'farm-management' ); ?></h1>

			<?php if ( isset( $_GET['fmp_smtp_test_sent'] ) && sanitize_text_field( wp_unslash( $_GET['fmp_smtp_test_sent'] ) ) === '1' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Test email sent successfully.', 'farm-management' ); ?></p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['fmp_smtp_test_fail'] ) ) : ?>
				<?php
				$err = sanitize_text_field( wp_unslash( $_GET['fmp_smtp_test_fail'] ) );
				$err = $err !== '' ? urldecode( $err ) : __( 'Send failed.', 'farm-management' );
				?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html__( 'Test email failed:', 'farm-management' ) . ' ' . esc_html( $err ); ?></p></div>
			<?php endif; ?>

			<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::OPTION_GROUP );
				submit_button( __( 'Save Settings', 'farm-management' ) );
				?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Send Test Email', 'farm-management' ); ?></h2>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::TEST_ACTION ); ?>" />
				<?php wp_nonce_field( self::TEST_NONCE_NAME, self::TEST_NONCE_NAME ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="fmp_smtp_test_recipient"><?php esc_html_e( 'Recipient email', 'farm-management' ); ?></label></th>
						<td>
							<input type="email" id="fmp_smtp_test_recipient" name="fmp_smtp_test_recipient" value="" class="regular-text" required />
						</td>
					</tr>
				</table>
				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Send', 'farm-management' ); ?></button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle Send Test Email: nonce, capability, send wp_mail(), redirect with notice.
	 */
	public static function handle_send_test_email() {
		if ( ! isset( $_POST[ self::TEST_NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::TEST_NONCE_NAME ] ) ), self::TEST_NONCE_NAME ) ) {
			wp_die( esc_html__( 'Security check failed.', 'farm-management' ), '', array( 'response' => 403 ) );
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_SETTINGS ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'farm-management' ), '', array( 'response' => 403 ) );
		}
		$to = isset( $_POST['fmp_smtp_test_recipient'] ) ? sanitize_email( wp_unslash( $_POST['fmp_smtp_test_recipient'] ) ) : '';
		if ( ! is_email( $to ) ) {
			wp_safe_redirect( add_query_arg( 'fmp_smtp_test_fail', rawurlencode( __( 'Invalid recipient email.', 'farm-management' ) ), admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ) );
			exit;
		}
		$subject = sprintf( __( '[%s] SMTP test', 'farm-management' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
		$message = sprintf( __( 'This is a test email from %s sent at %s.', 'farm-management' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), wp_date( 'Y-m-d H:i:s' ) );
		$headers  = array( 'Content-Type: text/plain; charset=UTF-8' );
		$sent     = wp_mail( $to, $subject, $message, $headers );
		$redirect = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		if ( $sent ) {
			wp_safe_redirect( add_query_arg( 'fmp_smtp_test_sent', '1', $redirect ) );
		} else {
			$err = __( 'wp_mail returned false.', 'farm-management' );
			wp_safe_redirect( add_query_arg( 'fmp_smtp_test_fail', rawurlencode( $err ), $redirect ) );
		}
		exit;
	}
}
