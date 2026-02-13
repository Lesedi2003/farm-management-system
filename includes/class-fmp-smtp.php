<?php
/**
 * SMTP configuration via phpmailer_init. Reads from fmp_smtp_settings option.
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Smtp
 */
class FMP_Smtp {

	const OPTION_NAME = 'fmp_smtp_settings';

	const KEY_ENABLED     = 'smtp_enabled';
	const KEY_HOST        = 'smtp_host';
	const KEY_PORT        = 'smtp_port';
	const KEY_ENCRYPTION  = 'smtp_encryption';
	const KEY_USERNAME    = 'smtp_username';
	const KEY_PASSWORD    = 'smtp_password';
	const KEY_FROM_NAME   = 'smtp_from_name';
	const KEY_FROM_EMAIL  = 'smtp_from_email';

	const DEFAULT_PORT       = 587;
	const DEFAULT_ENCRYPTION = 'tls';
	const DEFAULT_FROM_NAME  = 'Farm Management';

	/**
	 * Constructor. Hooks into WordPress.
	 */
	public function __construct() {
		add_action( 'phpmailer_init', array( __CLASS__, 'configure_phpmailer' ), 10, 1 );
		add_filter( 'wp_mail_from', array( __CLASS__, 'filter_wp_mail_from' ), 10, 1 );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'filter_wp_mail_from_name' ), 10, 1 );
		add_action( 'wp_mail_failed', array( __CLASS__, 'log_mail_failed' ), 10, 1 );
	}

	/**
	 * Get default option values.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		$admin = get_option( 'admin_email' );
		return array(
			self::KEY_ENABLED    => 0,
			self::KEY_HOST      => '',
			self::KEY_PORT      => self::DEFAULT_PORT,
			self::KEY_ENCRYPTION => self::DEFAULT_ENCRYPTION,
			self::KEY_USERNAME  => '',
			self::KEY_PASSWORD  => '',
			self::KEY_FROM_NAME => self::DEFAULT_FROM_NAME,
			self::KEY_FROM_EMAIL => is_email( $admin ) ? $admin : '',
		);
	}

	/**
	 * Get a single SMTP option value.
	 *
	 * @param string $key Option key.
	 * @return mixed
	 */
	public static function get_option( $key ) {
		$opts = get_option( self::OPTION_NAME, self::get_defaults() );
		$def  = self::get_defaults();
		if ( ! is_array( $opts ) ) {
			$opts = $def;
		}
		return isset( $opts[ $key ] ) ? $opts[ $key ] : ( isset( $def[ $key ] ) ? $def[ $key ] : '' );
	}

	/**
	 * Check if SMTP is enabled.
	 *
	 * @return bool
	 */
	public static function is_smtp_enabled() {
		return (int) self::get_option( self::KEY_ENABLED ) === 1;
	}

	/**
	 * Get effective From email (validated; fallback admin_email).
	 *
	 * @return string
	 */
	public static function get_effective_from_email() {
		$from = self::get_option( self::KEY_FROM_EMAIL );
		if ( $from !== '' && is_email( $from ) ) {
			return $from;
		}
		$admin = get_option( 'admin_email' );
		return is_email( $admin ) ? $admin : '';
	}

	/**
	 * Get effective From name.
	 *
	 * @return string
	 */
	public static function get_effective_from_name() {
		$name = self::get_option( self::KEY_FROM_NAME );
		return $name !== '' ? $name : self::DEFAULT_FROM_NAME;
	}

	/**
	 * Filter wp_mail_from to enforce valid From (fix wordpress@localhost).
	 *
	 * @param string $from_email Default from.
	 * @return string
	 */
	public static function filter_wp_mail_from( $from_email ) {
		if ( $from_email !== '' && $from_email !== 'wordpress@localhost' && is_email( $from_email ) ) {
			return $from_email;
		}
		$effective = self::get_effective_from_email();
		return $effective !== '' ? $effective : $from_email;
	}

	/**
	 * Filter wp_mail_from_name when SMTP enabled.
	 *
	 * @param string $from_name Default from name.
	 * @return string
	 */
	public static function filter_wp_mail_from_name( $from_name ) {
		if ( self::is_smtp_enabled() ) {
			return self::get_effective_from_name();
		}
		return $from_name;
	}

	/**
	 * Configure PHPMailer only when Enable SMTP is on.
	 * Sets Host, Port, SMTPSecure (only if encryption != none), Username, Password, SMTPAuth=true.
	 * Sets From via setFrom($from_email, $from_name, false).
	 *
	 * @param object $phpmailer PHPMailer instance (by reference).
	 */
	public static function configure_phpmailer( $phpmailer ) {
		if ( ! self::is_smtp_enabled() ) {
			$effective_email = self::get_effective_from_email();
			$effective_name  = self::get_effective_from_name();
			if ( $effective_email !== '' ) {
				$current = isset( $phpmailer->From ) ? $phpmailer->From : '';
				if ( $current === '' || $current === 'wordpress@localhost' || ! is_email( $current ) ) {
					if ( method_exists( $phpmailer, 'setFrom' ) ) {
						$phpmailer->setFrom( $effective_email, $effective_name, false );
					} else {
						$phpmailer->From     = $effective_email;
						$phpmailer->FromName = $effective_name;
					}
				}
			}
			return;
		}

		$host = self::get_option( self::KEY_HOST );
		if ( $host === '' ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host = $host;
		$port = (int) self::get_option( self::KEY_PORT );
		$phpmailer->Port = $port >= 1 ? $port : self::DEFAULT_PORT;

		$enc = self::get_option( self::KEY_ENCRYPTION );
		if ( $enc === 'ssl' ) {
			$phpmailer->SMTPSecure = 'ssl';
		} elseif ( $enc === 'tls' ) {
			$phpmailer->SMTPSecure = 'tls';
		}
		// If encryption = none, do not set SMTPSecure (leave default/empty).

		$user = self::get_option( self::KEY_USERNAME );
		$pass = self::get_option( self::KEY_PASSWORD );
		$phpmailer->SMTPAuth = true;
		$phpmailer->Username = $user !== null ? $user : '';
		$phpmailer->Password = $pass !== null ? $pass : '';

		$from_email = self::get_effective_from_email();
		$from_name  = self::get_effective_from_name();
		if ( $from_email !== '' && method_exists( $phpmailer, 'setFrom' ) ) {
			$phpmailer->setFrom( $from_email, $from_name, false );
		} elseif ( $from_email !== '' ) {
			$phpmailer->From     = $from_email;
			$phpmailer->FromName = $from_name;
		}
	}

	/**
	 * Log wp_mail failure to error_log with [FMP Mail] prefix.
	 *
	 * @param WP_Error $error Error from wp_mail_failed.
	 */
	public static function log_mail_failed( $error ) {
		if ( ! $error || ! is_wp_error( $error ) ) {
			return;
		}
		$msg = $error->get_error_message();
		$data = $error->get_error_data();
		$log = '[FMP Mail] wp_mail failed: ' . $msg;
		if ( is_array( $data ) && ! empty( $data ) ) {
			$safe = array();
			foreach ( $data as $k => $v ) {
				if ( $k === 'phpmailer_exception_code' || is_scalar( $v ) ) {
					$safe[ $k ] = $v;
				}
			}
			if ( ! empty( $safe ) ) {
				$log .= ' | data: ' . wp_json_encode( $safe );
			}
		}
		error_log( $log );
	}
}
