<?php
/**
 * Settings page using the Settings API.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Settings
 */
class FMP_Settings {

	const OPTION_GROUP = 'farm-management-settings';
	const OPTION_NAME  = 'fmp_settings';

	const KEY_VACCINATION_DAYS = 'vaccination_due_days';
	const KEY_DUE_SOON_DAYS    = 'due_soon_days';
	const KEY_LOW_STOCK_DEFAULT = 'low_stock_threshold_default';
	const KEY_DEMO_MODE        = 'demo_mode';
	const KEY_REMINDERS_ENABLED = 'reminders_enabled';
	const KEY_REMINDER_HOUR     = 'reminder_hour';
	const KEY_CONTACT_ADDRESS  = 'contact_address';
	const KEY_CONTACT_PHONE    = 'contact_phone';
	const KEY_CONTACT_EMAIL    = 'contact_email';

	const DEFAULT_VACCINATION_DAYS = 14;
	const DEFAULT_DUE_SOON_DAYS    = 14;
	const DEFAULT_LOW_STOCK        = 10;
	const DEFAULT_DEMO_MODE       = 0;
	const DEFAULT_REMINDERS_ENABLED = 1;
	const DEFAULT_REMINDER_HOUR    = 7;
	const DEFAULT_CONTACT_ADDRESS  = '';
	const DEFAULT_CONTACT_PHONE    = '';
	const DEFAULT_CONTACT_EMAIL    = '';

	const DUE_SOON_DAYS_MIN = 1;
	const DUE_SOON_DAYS_MAX = 120;

	/**
	 * Constructor. Hooks into WordPress.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ), 10 );
	}

	/**
	 * Register setting, section, and fields.
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'description'       => __( 'Farm Management settings.', 'farm-management' ),
				'sanitize_callback' => array( $this, 'sanitize_options' ),
				'default'           => array(
					self::KEY_VACCINATION_DAYS => self::DEFAULT_VACCINATION_DAYS,
					self::KEY_DUE_SOON_DAYS   => self::DEFAULT_DUE_SOON_DAYS,
					self::KEY_LOW_STOCK_DEFAULT => self::DEFAULT_LOW_STOCK,
					self::KEY_REMINDERS_ENABLED => self::DEFAULT_REMINDERS_ENABLED,
					self::KEY_REMINDER_HOUR   => self::DEFAULT_REMINDER_HOUR,
					self::KEY_CONTACT_ADDRESS  => self::DEFAULT_CONTACT_ADDRESS,
					self::KEY_CONTACT_PHONE    => self::DEFAULT_CONTACT_PHONE,
					self::KEY_CONTACT_EMAIL    => self::DEFAULT_CONTACT_EMAIL,
				),
			)
		);

		add_settings_section(
			'fmp_settings_main',
			__( 'General', 'farm-management' ),
			array( $this, 'render_section' ),
			self::OPTION_GROUP
		);

		add_settings_field(
			'fmp_due_soon_days',
			__( 'Due Soon Days', 'farm-management' ),
			array( $this, 'render_field_due_soon_days' ),
			self::OPTION_GROUP,
			'fmp_settings_main',
			array(
				'label_for' => 'fmp_due_soon_days',
			)
		);

		add_settings_field(
			'fmp_low_stock_threshold_default',
			__( 'Low stock threshold (default)', 'farm-management' ),
			array( $this, 'render_field_low_stock_default' ),
			self::OPTION_GROUP,
			'fmp_settings_main',
			array(
				'label_for' => 'fmp_low_stock_threshold_default',
			)
		);

		add_settings_field(
			'fmp_demo_mode',
			__( 'Demo Mode', 'farm-management' ),
			array( $this, 'render_field_demo_mode' ),
			self::OPTION_GROUP,
			'fmp_settings_main',
			array(
				'label_for' => 'fmp_demo_mode',
			)
		);

		add_settings_section(
			'fmp_settings_reminders',
			__( 'Email Reminders', 'farm-management' ),
			array( $this, 'render_section_reminders' ),
			self::OPTION_GROUP
		);

		add_settings_field(
			'fmp_reminders_enabled',
			__( 'Enable reminders', 'farm-management' ),
			array( $this, 'render_field_reminders_enabled' ),
			self::OPTION_GROUP,
			'fmp_settings_reminders',
			array(
				'label_for' => 'fmp_reminders_enabled',
			)
		);

		add_settings_field(
			'fmp_reminder_hour',
			__( 'Reminder hour', 'farm-management' ),
			array( $this, 'render_field_reminder_hour' ),
			self::OPTION_GROUP,
			'fmp_settings_reminders',
			array(
				'label_for' => 'fmp_reminder_hour',
			)
		);

		add_settings_field(
			'fmp_reminders_actions',
			__( 'Test & run', 'farm-management' ),
			array( $this, 'render_field_reminders_actions' ),
			self::OPTION_GROUP,
			'fmp_settings_reminders'
		);

		add_settings_section(
			'fmp_settings_contact',
			__( 'Contact info', 'farm-management' ),
			array( $this, 'render_section_contact' ),
			self::OPTION_GROUP
		);

		add_settings_field(
			'fmp_contact_address',
			__( 'Address', 'farm-management' ),
			array( $this, 'render_field_contact_address' ),
			self::OPTION_GROUP,
			'fmp_settings_contact',
			array( 'label_for' => 'fmp_contact_address' )
		);

		add_settings_field(
			'fmp_contact_phone',
			__( 'Phone', 'farm-management' ),
			array( $this, 'render_field_contact_phone' ),
			self::OPTION_GROUP,
			'fmp_settings_contact',
			array( 'label_for' => 'fmp_contact_phone' )
		);

		add_settings_field(
			'fmp_contact_email',
			__( 'Email', 'farm-management' ),
			array( $this, 'render_field_contact_email' ),
			self::OPTION_GROUP,
			'fmp_settings_contact',
			array( 'label_for' => 'fmp_contact_email' )
		);
	}

	/**
	 * Sanitize options array.
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized values.
	 */
	public function sanitize_options( $input ) {
		if ( ! is_array( $input ) ) {
			return $this->get_defaults();
		}
		$out = array();
		$out[ self::KEY_VACCINATION_DAYS ] = isset( $input[ self::KEY_VACCINATION_DAYS ] )
			? absint( $input[ self::KEY_VACCINATION_DAYS ] )
			: self::DEFAULT_VACCINATION_DAYS;
		$out[ self::KEY_VACCINATION_DAYS ] = max( 1, min( 365, $out[ self::KEY_VACCINATION_DAYS ] ) );

		$out[ self::KEY_DUE_SOON_DAYS ] = isset( $input[ self::KEY_DUE_SOON_DAYS ] )
			? absint( $input[ self::KEY_DUE_SOON_DAYS ] )
			: self::DEFAULT_DUE_SOON_DAYS;
		$out[ self::KEY_DUE_SOON_DAYS ] = max( self::DUE_SOON_DAYS_MIN, min( self::DUE_SOON_DAYS_MAX, $out[ self::KEY_DUE_SOON_DAYS ] ) );

		$out[ self::KEY_LOW_STOCK_DEFAULT ] = isset( $input[ self::KEY_LOW_STOCK_DEFAULT ] )
			? absint( $input[ self::KEY_LOW_STOCK_DEFAULT ] )
			: self::DEFAULT_LOW_STOCK;
		$out[ self::KEY_LOW_STOCK_DEFAULT ] = max( 0, $out[ self::KEY_LOW_STOCK_DEFAULT ] );

		$out[ self::KEY_REMINDERS_ENABLED ] = isset( $input[ self::KEY_REMINDERS_ENABLED ] ) ? 1 : 0;
		$out[ self::KEY_REMINDER_HOUR ] = isset( $input[ self::KEY_REMINDER_HOUR ] )
			? absint( $input[ self::KEY_REMINDER_HOUR ] )
			: self::DEFAULT_REMINDER_HOUR;
		$out[ self::KEY_REMINDER_HOUR ] = max( 0, min( 23, $out[ self::KEY_REMINDER_HOUR ] ) );

		$out[ self::KEY_CONTACT_ADDRESS ] = isset( $input[ self::KEY_CONTACT_ADDRESS ] )
			? sanitize_textarea_field( wp_unslash( $input[ self::KEY_CONTACT_ADDRESS ] ) )
			: self::DEFAULT_CONTACT_ADDRESS;
		$out[ self::KEY_CONTACT_PHONE ] = isset( $input[ self::KEY_CONTACT_PHONE ] )
			? sanitize_text_field( wp_unslash( $input[ self::KEY_CONTACT_PHONE ] ) )
			: self::DEFAULT_CONTACT_PHONE;
		$out[ self::KEY_CONTACT_EMAIL ] = isset( $input[ self::KEY_CONTACT_EMAIL ] )
			? sanitize_email( wp_unslash( $input[ self::KEY_CONTACT_EMAIL ] ) )
			: self::DEFAULT_CONTACT_EMAIL;

		return $out;
	}

	/**
	 * Get default options.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			self::KEY_VACCINATION_DAYS => self::DEFAULT_VACCINATION_DAYS,
			self::KEY_DUE_SOON_DAYS   => self::DEFAULT_DUE_SOON_DAYS,
			self::KEY_LOW_STOCK_DEFAULT => self::DEFAULT_LOW_STOCK,
			self::KEY_DEMO_MODE       => self::DEFAULT_DEMO_MODE,
			self::KEY_REMINDERS_ENABLED => self::DEFAULT_REMINDERS_ENABLED,
			self::KEY_REMINDER_HOUR   => self::DEFAULT_REMINDER_HOUR,
			self::KEY_CONTACT_ADDRESS  => self::DEFAULT_CONTACT_ADDRESS,
			self::KEY_CONTACT_PHONE    => self::DEFAULT_CONTACT_PHONE,
			self::KEY_CONTACT_EMAIL    => self::DEFAULT_CONTACT_EMAIL,
		);
	}

	/**
	 * Get option value with default.
	 *
	 * @param string $key Option key (e.g. KEY_DUE_SOON_DAYS, KEY_LOW_STOCK_DEFAULT).
	 * @return mixed
	 */
	public static function get( $key ) {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$defaults = self::get_defaults();
		if ( ! is_array( $options ) ) {
			$options = $defaults;
		}
		return isset( $options[ $key ] ) ? $options[ $key ] : ( isset( $defaults[ $key ] ) ? $defaults[ $key ] : null );
	}

	/**
	 * Render section description (optional).
	 */
	public function render_section() {
		echo '<p class="description">' . esc_html__( 'Configure default thresholds used in the dashboard and reports.', 'farm-management' ) . '</p>';
	}

	/**
	 * Render Due Soon Days field (fmp_due_soon_days).
	 */
	public function render_field_due_soon_days() {
		$value = self::get( self::KEY_DUE_SOON_DAYS );
		$value = $value !== null ? (int) $value : self::DEFAULT_DUE_SOON_DAYS;
		$name  = self::OPTION_NAME . '[' . self::KEY_DUE_SOON_DAYS . ']';
		?>
		<input type="number"
			id="fmp_due_soon_days"
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			min="<?php echo esc_attr( self::DUE_SOON_DAYS_MIN ); ?>"
			max="<?php echo esc_attr( self::DUE_SOON_DAYS_MAX ); ?>"
			class="small-text"
		/>
		<p class="description"><?php esc_html_e( 'Number of days ahead to consider vaccinations as "due soon" (e.g. 7, 14, or 30). Used in vaccination status, dashboard, and reports.', 'farm-management' ); ?></p>
		<?php
	}

	/**
	 * Render low stock threshold default field.
	 */
	public function render_field_low_stock_default() {
		$value = self::get( self::KEY_LOW_STOCK_DEFAULT );
		$value = $value !== null ? (int) $value : self::DEFAULT_LOW_STOCK;
		$name  = self::OPTION_NAME . '[' . self::KEY_LOW_STOCK_DEFAULT . ']';
		?>
		<input type="number"
			id="fmp_low_stock_threshold_default"
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			min="0"
			class="small-text"
		/>
		<p class="description"><?php esc_html_e( 'Default reorder level suggestion for new inventory items. Existing items use their own reorder level.', 'farm-management' ); ?></p>
		<?php
	}

	/**
	 * Render Demo Mode checkbox (ON/OFF).
	 */
	public function render_field_demo_mode() {
		$value = self::get( self::KEY_DEMO_MODE );
		$checked = (int) $value === 1;
		$name = self::OPTION_NAME . '[' . self::KEY_DEMO_MODE . ']';
		?>
		<label for="fmp_demo_mode">
			<input type="checkbox"
				id="fmp_demo_mode"
				name="<?php echo esc_attr( $name ); ?>"
				value="1"
				<?php checked( $checked ); ?>
			/>
			<?php esc_html_e( 'Enable Demo Mode (show banner and sample data buttons on Dashboard)', 'farm-management' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'When ON, the Dashboard shows a demo banner and buttons to create or delete sample data.', 'farm-management' ); ?></p>
		<?php
	}

	/**
	 * Render Email Reminders section description.
	 */
	public function render_section_reminders() {
		echo '<p class="description">' . esc_html__( 'Send a daily summary email of vaccinations that are overdue or due soon. Due soon uses the "Due Soon Days" value from General above. Recipients are users with the Farm Manager role (or the site admin email if none).', 'farm-management' ) . '</p>';
	}

	/**
	 * Render Enable reminders checkbox.
	 */
	public function render_field_reminders_enabled() {
		$value   = self::get( self::KEY_REMINDERS_ENABLED );
		$checked = $value === null ? true : ( (int) $value === 1 );
		$name    = self::OPTION_NAME . '[' . self::KEY_REMINDERS_ENABLED . ']';
		?>
		<label for="fmp_reminders_enabled">
			<input type="checkbox"
				id="fmp_reminders_enabled"
				name="<?php echo esc_attr( $name ); ?>"
				value="1"
				<?php checked( $checked ); ?>
			/>
			<?php esc_html_e( 'Enable vaccination email reminders', 'farm-management' ); ?>
		</label>
		<?php
	}

	/**
	 * Render Reminder hour field (0–23).
	 */
	public function render_field_reminder_hour() {
		$value = self::get( self::KEY_REMINDER_HOUR );
		$value = $value !== null ? (int) $value : self::DEFAULT_REMINDER_HOUR;
		$name  = self::OPTION_NAME . '[' . self::KEY_REMINDER_HOUR . ']';
		?>
		<input type="number"
			id="fmp_reminder_hour"
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			min="0"
			max="23"
			class="small-text"
		/>
		<p class="description"><?php esc_html_e( 'Hour to send the daily reminder (site timezone), 0–23. Default 7 (7:00 AM).', 'farm-management' ); ?></p>
		<?php
	}

	/**
	 * Render Test Email and Run Reminders Now buttons (farm_manager / fmp_manage_settings).
	 */
	public function render_field_reminders_actions() {
		if ( ! current_user_can( FMP_Capabilities::MANAGE_SETTINGS ) ) {
			return;
		}
		$test_url = wp_nonce_url( admin_url( 'admin-post.php?action=' . FMP_Reminders::TEST_EMAIL_ACTION ), FMP_Reminders::TEST_EMAIL_NONCE, '_wpnonce' );
		$run_url  = wp_nonce_url( admin_url( 'admin-post.php?action=' . FMP_Reminders::RUN_REMINDERS_ACTION ), FMP_Reminders::RUN_REMINDERS_NONCE, '_wpnonce' );
		?>
		<p>
			<a href="<?php echo esc_url( $test_url ); ?>" class="button"><?php esc_html_e( 'Send Test Email', 'farm-management' ); ?></a>
			<span class="description"><?php esc_html_e( 'Sends a test reminder email to your account.', 'farm-management' ); ?></span>
		</p>
		<p>
			<a href="<?php echo esc_url( $run_url ); ?>" class="button"><?php esc_html_e( 'Run Reminders Now', 'farm-management' ); ?></a>
			<span class="description"><?php esc_html_e( 'Runs the reminder logic immediately and sends the summary to Farm Managers (useful for demo).', 'farm-management' ); ?></span>
		</p>
		<?php
		if ( isset( $_GET['fmp_test_email_sent'] ) && sanitize_text_field( wp_unslash( $_GET['fmp_test_email_sent'] ) ) === '1' ) {
			echo '<p class="notice notice-success inline"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Test email sent.', 'farm-management' ) . '</p>';
		}
		if ( isset( $_GET['fmp_reminders_run'] ) && sanitize_text_field( wp_unslash( $_GET['fmp_reminders_run'] ) ) === '1' ) {
			$run_result = get_transient( FMP_Reminders::TRANSIENT_RUN_RESULT );
			if ( is_array( $run_result ) ) {
				delete_transient( FMP_Reminders::TRANSIENT_RUN_RESULT );
				$message = FMP_Reminders::format_run_result_message( $run_result );
				echo '<p class="notice notice-success inline"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html( $message ) . '</p>';
			} else {
				echo '<p class="notice notice-success inline"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Reminders run completed.', 'farm-management' ) . '</p>';
			}
		}
	}

	/**
	 * Render Contact info section description.
	 */
	public function render_section_contact() {
		echo '<p class="description">' . esc_html__( 'Farm contact details shown on the frontend Contact page when using the [fmp_contact] shortcode.', 'farm-management' ) . '</p>';
	}

	/**
	 * Render Contact address field.
	 */
	public function render_field_contact_address() {
		$value = self::get( self::KEY_CONTACT_ADDRESS );
		$value = $value !== null ? $value : self::DEFAULT_CONTACT_ADDRESS;
		$name  = self::OPTION_NAME . '[' . self::KEY_CONTACT_ADDRESS . ']';
		?>
		<textarea id="fmp_contact_address"
			name="<?php echo esc_attr( $name ); ?>"
			rows="3"
			class="large-text"
			placeholder="<?php esc_attr_e( 'e.g. 123 Farm Road, Town, Province', 'farm-management' ); ?>"
		><?php echo esc_textarea( $value ); ?></textarea>
		<?php
	}

	/**
	 * Render Contact phone field.
	 */
	public function render_field_contact_phone() {
		$value = self::get( self::KEY_CONTACT_PHONE );
		$value = $value !== null ? $value : self::DEFAULT_CONTACT_PHONE;
		$name  = self::OPTION_NAME . '[' . self::KEY_CONTACT_PHONE . ']';
		?>
		<input type="text"
			id="fmp_contact_phone"
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'e.g. +27 12 345 6789', 'farm-management' ); ?>"
		/>
		<?php
	}

	/**
	 * Render Contact email field.
	 */
	public function render_field_contact_email() {
		$value = self::get( self::KEY_CONTACT_EMAIL );
		$value = $value !== null ? $value : self::DEFAULT_CONTACT_EMAIL;
		$name  = self::OPTION_NAME . '[' . self::KEY_CONTACT_EMAIL . ']';
		?>
		<input type="email"
			id="fmp_contact_email"
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'e.g. info@myfarm.co.za', 'farm-management' ); ?>"
		/>
		<?php
	}

	/**
	 * Render the settings page (form). Called from admin menu. Only for manage_options.
	 */
	public static function render_page() {
		if ( ! current_user_can( FMP_Capabilities::MANAGE_SETTINGS ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'farm-management' ), '', array( 'response' => 403 ) );
		}
		?>
		<div class="wrap fmp-admin-wrap fmp-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::OPTION_GROUP );
				submit_button( __( 'Save Settings', 'farm-management' ) );
				?>
			</form>
		</div>
		<?php
	}
}
