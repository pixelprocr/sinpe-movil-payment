<?php
/*
Plugin Name: SINPE Móvil Payment Gateway
Plugin URI: https://pixelprocr.com
Description: Plugin para recibir pagos mediante SINPE Móvil en WooCommerce.
Version: 1.0.10
Author: PixelPRO
Author URI: https://pixelprocr.com
License: GPL2
*/
<?php
/*
Plugin Name: Sinpe Movil Payment
Description: Integración de pago móvil para Sinpe.
Version: 1.0.0
Author: Tu Nombre
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Include the Freemius SDK.
if ( ! function_exists( 'smp_fs' ) ) {
    // Create a helper function for easy SDK access.
    function smp_fs() {
        global $smp_fs;

        if ( ! isset( $smp_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
            $smp_fs = fs_dynamic_init( array(
                'id'                  => '17926',
                'slug'                => 'sinpe-movil-payment',
                'type'                => 'plugin',
                'public_key'          => 'pk_199a682b117740e284302c2019b79',
                'is_premium'          => false,
                'has_addons'          => false,
                'has_paid_plans'      => false,
                'menu'                => array(
                    'account'        => false,
                    'support'        => false,
                ),
            ) );
        }

        return $smp_fs;
    }

    // Init Freemius.
    smp_fs();
    // Signal that SDK was initiated.
    do_action( 'smp_fs_loaded' );
}

// Your plugin code goes here.
// Enable debug logging for SINPE Móvil plugin. Set to true to enable debugging.
if ( ! defined( 'SINPE_DEBUG' ) ) {
	define( 'SINPE_DEBUG', true );
}

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'SINPE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SINPE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load the debug class.
require_once SINPE_PLUGIN_DIR . 'includes/class-sinpe-debug.php';

// Verify if WooCommerce is active.
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	// Initialize the payment gateway.
	add_filter( 'woocommerce_payment_gateways', 'sinpe_movil_add_gateway_class' );
	function sinpe_movil_add_gateway_class( $gateways ) {
		require_once SINPE_PLUGIN_DIR . 'includes/class-sinpe-movil-gateway.php';
		$gateways[] = 'WC_Sinpe_Movil_Gateway';
		return $gateways;
	}

	// Initialize admin settings, database, and other required classes.
	add_action( 'plugins_loaded', 'sinpe_movil_init_gateway_class' );
	function sinpe_movil_init_gateway_class() {
		require_once SINPE_PLUGIN_DIR . 'includes/class-sinpe-movil-gateway.php';
		require_once SINPE_PLUGIN_DIR . 'includes/class-sinpe-movil-admin.php';
		require_once SINPE_PLUGIN_DIR . 'includes/class-sinpe-movil-db.php';
		require_once SINPE_PLUGIN_DIR . 'includes/class-sinpe-metabox.php';

		WC_Sinpe_Movil_Gateway::init();
		SINPE_Movil_Admin::init();
		SINPE_Movil_DB::init();
	}

	// Create necessary custom DB tables on plugin activation.
	register_activation_hook( __FILE__, array( 'SINPE_Movil_DB', 'create_tables' ) );

	// Generate encryption key on plugin activation.
	register_activation_hook( __FILE__, 'sinpe_movil_create_encryption_key' );
	function sinpe_movil_create_encryption_key() {
		if ( ! get_option( 'sinpe_movil_encryption_key' ) ) {
			add_option( 'sinpe_movil_encryption_key', wp_generate_password( 32, false ) );
		}
	}

} else {
	add_action( 'admin_notices', 'sinpe_movil_missing_wc_notice' );
	function sinpe_movil_missing_wc_notice() {
		echo '<div class="error"><p><strong>SINPE Móvil Payment Gateway</strong> necesita WooCommerce para funcionar. Por favor, activa WooCommerce.</p></div>';
	}
}
