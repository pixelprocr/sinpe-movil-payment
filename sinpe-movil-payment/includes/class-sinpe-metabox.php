<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SINPE_Metabox {

	/**
	 * Constructor: registra el metabox en el hook "add_meta_boxes".
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_sinpe_metabox' ) );
	}

	/**
	 * Registra el metabox en la pantalla de edición de pedidos ("shop_order")
	 * con prioridad "low" para que se muestre debajo de "Acciones de pedido".
	 */
	public function add_sinpe_metabox() {
		add_meta_box(
			'sinpe_payment_info',                                           // ID del metabox.
			__( 'Información de pagos SINPE', 'sinpe-movil-payment' ),       // Título.
			array( $this, 'display_sinpe_metabox' ),                         // Callback.
			'shop_order',                                                   // Pantalla (tipo de post).
			'side',                                                         // Ubicación.
			'low'                                                           // Prioridad.
		);
	}

	/**
	 * Muestra el contenido del metabox.
	 *
	 * @param WP_Post $post Objeto del pedido.
	 */
	public function display_sinpe_metabox( $post ) {
		$order = wc_get_order( $post->ID );
		
		$nombre           = $order->get_meta( 'sinpe_nombre' );
		$apellido         = $order->get_meta( 'sinpe_apellido' );
		$cedula_encrypted = $order->get_meta( 'sinpe_cedula' );
		$autorizacion     = $order->get_meta( 'sinpe_autorizacion' );
		$monto            = $order->get_meta( 'sinpe_monto' );
		$captura          = $order->get_meta( 'sinpe_captura' );

		$encryption_key = get_option( 'sinpe_movil_encryption_key' );
		if ( ! empty( $cedula_encrypted ) && ! empty( $encryption_key ) ) {
			$cedula = openssl_decrypt( $cedula_encrypted, 'AES-128-ECB', $encryption_key );
		} else {
			$cedula = '';
		}

		echo '<div class="sinpe-metabox-content">';
			echo '<p><strong>' . esc_html__( 'Nombre:', 'sinpe-movil-payment' ) . '</strong> ' . esc_html( $nombre ) . '</p>';
			echo '<p><strong>' . esc_html__( 'Apellido:', 'sinpe-movil-payment' ) . '</strong> ' . esc_html( $apellido ) . '</p>';
			echo '<p><strong>' . esc_html__( 'Cédula:', 'sinpe-movil-payment' ) . '</strong> ' . esc_html( $cedula ) . '</p>';
			echo '<p><strong>' . esc_html__( 'Número de Autorización:', 'sinpe-movil-payment' ) . '</strong> ' . esc_html( $autorizacion ) . '</p>';
			echo '<p><strong>' . esc_html__( 'Monto Transferido:', 'sinpe-movil-payment' ) . '</strong> ' . wc_price( $monto ) . '</p>';

			if ( ! empty( $captura ) ) {
				echo '<p><strong>' . esc_html__( 'Captura de Pantalla:', 'sinpe-movil-payment' ) . '</strong> <a href="' . esc_url( $captura ) . '" target="_blank">' . esc_html__( 'Ver imagen', 'sinpe-movil-payment' ) . '</a></p>';
			} else {
				echo '<p><strong>' . esc_html__( 'Captura de Pantalla:', 'sinpe-movil-payment' ) . '</strong> ' . esc_html__( 'No disponible', 'sinpe-movil-payment' ) . '</p>';
			}
		echo '</div>';
	}
}

new SINPE_Metabox();