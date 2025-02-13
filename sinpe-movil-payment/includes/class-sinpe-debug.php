<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SINPE_Debug
 *
 * This class provides a static method to write debug messages to a file
 * located in the plugin's assets folder.
 */
class SINPE_Debug {

	/**
	 * Logs a debug message to assets/debug.log if SINPE_DEBUG is enabled.
	 *
	 * @param string $message The debug message.
	 */
	public static function log( $message ) {
		if ( defined( 'SINPE_DEBUG' ) && SINPE_DEBUG ) {
			// Ensure that SINPE_PLUGIN_DIR is defined.
			if ( ! defined( 'SINPE_PLUGIN_DIR' ) ) {
				return;
			}

			// Ensure the assets folder exists.
			$assets_dir = SINPE_PLUGIN_DIR . 'assets/';
			if ( ! file_exists( $assets_dir ) ) {
				mkdir( $assets_dir, 0755, true );
			}

			// Debug log file path.
			$debug_file = $assets_dir . 'debug.log';

			// Format the message with a timestamp.
			$date = date( 'Y-m-d H:i:s' );
			$formatted_message = "[{$date}] {$message}\n";

			// Append the message to the log file.
			file_put_contents( $debug_file, $formatted_message, FILE_APPEND | LOCK_EX );
		}
	}
}