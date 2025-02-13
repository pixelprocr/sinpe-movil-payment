<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SINPE_Movil_DB {

    public static function init() {
        add_action( 'woocommerce_thankyou', array( __CLASS__, 'insert_payment_record' ), 10, 1 );
        add_action( 'plugins_loaded', array( __CLASS__, 'check_and_update_table' ) );
    }

    public static function create_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'woosinpe';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            nombre tinytext NOT NULL,
            monto float NOT NULL,
            autorizacion tinytext NOT NULL,
            captura text NOT NULL,
            estado tinytext NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function insert_payment_record( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( 'sinpe_movil' !== $order->get_payment_method() ) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'woosinpe';

        $nombre = sanitize_text_field( $order->get_meta( 'sinpe_nombre' ) );
        $monto = floatval( $order->get_meta( 'sinpe_monto' ) );
        $autorizacion = sanitize_text_field( $order->get_meta( 'sinpe_autorizacion' ) );
        $captura = esc_url( $order->get_meta( 'sinpe_captura' ) );
        $estado = 'Pendiente';

        $wpdb->insert(
            $table_name,
            array(
                'order_id'      => $order_id,
                'nombre'        => $nombre,
                'monto'         => $monto,
                'autorizacion'  => $autorizacion,
                'captura'       => $captura,
                'estado'        => $estado,
            ),
            array( '%d', '%s', '%f', '%s', '%s', '%s' )
        );
    }

    public static function check_and_update_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'woosinpe';

        // Verificar si la columna order_id existe
        $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM $table_name LIKE 'order_id'" );

        if ( empty( $column_exists ) ) {
            // Si la columna no existe, agregarla
            $wpdb->query( "ALTER TABLE $table_name ADD COLUMN order_id bigint(20) NOT NULL" );
        }
    }
}