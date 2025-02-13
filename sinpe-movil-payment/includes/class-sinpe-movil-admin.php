<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SINPE_Movil_Admin {

	/**
	 * Initialize admin components.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
		add_action( 'admin_post_sinpe_update_order_status', array( __CLASS__, 'handle_update_order_status' ) );
	}

	/**
	 * Adds a new menu page for SINPE Móvil payments.
	 */
	public static function add_menu_page() {
		add_menu_page(
			'Pagos SINPE Móvil',                        // Page title
			'Pagos SINPE Móvil',                        // Menu title
			'manage_woocommerce',                       // Capability required
			'pagos-sinpe-movil',                        // Menu slug
			array( __CLASS__, 'display_payments' ),     // Callback function
			'dashicons-money-alt',                      // Icon
			56                                          // Position in the admin menu
		);
	}

	/**
	 * Handler for updating order status.
	 */
	public static function handle_update_order_status() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'No tienes permisos suficientes para realizar esta acción.' );
		}

		// Verify nonce.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'sinpe_update_order_status' ) ) {
			wp_die( 'Nonce verification failed.' );
		}

		$order_id   = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$new_status = isset( $_POST['new_status'] ) ? sanitize_text_field( $_POST['new_status'] ) : '';

		// Map the custom statuses to WooCommerce statuses.
		$status_mapping = array(
			'en_espera' => 'on-hold',
			'aprobado'  => 'processing',
			'rechazado' => 'cancelled',
		);

		if ( $order_id && array_key_exists( $new_status, $status_mapping ) ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$order->update_status( $status_mapping[ $new_status ], 'Actualización realizada por el administrador mediante Pagos SINPE Móvil.' );
				// Redirect back with a success message.
				wp_redirect( add_query_arg( 'sinpe_msg', 'updated', wp_get_referer() ) );
				exit;
			}
		}

		// In case of failure, redirect back with an error message.
		wp_redirect( add_query_arg( 'sinpe_msg', 'error', wp_get_referer() ) );
		exit;
	}

	/**
	 * Displays the SINPE Móvil payments.
	 */
	public static function display_payments() {
		// Display any messages.
		$message = '';
		if ( isset( $_GET['sinpe_msg'] ) ) {
			if ( 'updated' === $_GET['sinpe_msg'] ) {
				$message = '<div id="message" class="updated notice is-dismissible"><p>Pedido actualizado correctamente.</p></div>';
			} elseif ( 'error' === $_GET['sinpe_msg'] ) {
				$message = '<div id="message" class="error notice is-dismissible"><p>Error al actualizar el pedido.</p></div>';
			}
		}

		// Query orders with payment method "sinpe_movil".
		$args = array(
			'payment_method' => 'sinpe_movil',
			'limit'          => -1,  // Retrieve all orders
			'orderby'        => 'date',
			'order'          => 'DESC'
		);
		$orders = wc_get_orders( $args );

		echo '<div class="wrap">';
		echo '<h1>Pagos SINPE Móvil</h1>';
		echo $message;

		if ( empty( $orders ) ) {
			echo '<p>No se encontraron pagos pendientes de revisión.</p>';
		} else {
			echo '<table class="wp-list-table widefat fixed striped">';
			echo '<thead><tr>';
			echo '<th>ID Pedido</th>';
			echo '<th>Fecha</th>';
			echo '<th>Total</th>';
			echo '<th>Estado</th>';
			echo '<th>Cliente</th>';
			echo '<th>Actualizar Pedido</th>';
			echo '</tr></thead>';
			echo '<tbody>';

			foreach ( $orders as $order ) {
				$order_id   = $order->get_id();
				$edit_link  = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
				$date_created   = $order->get_date_created();
				$date_formatted = $date_created ? $date_created->date( 'Y-m-d H:i' ) : 'N/A';
				$total          = $order->get_formatted_order_total();
				$status         = ucfirst( $order->get_status() );
				$billing_email  = $order->get_billing_email() ? $order->get_billing_email() : 'N/A';

				echo '<tr>';
					// ID Pedido as a link to the order edit page.
					echo '<td><a href="' . esc_url( $edit_link ) . '">' . esc_html( $order_id ) . '</a></td>';
					echo '<td>' . esc_html( $date_formatted ) . '</td>';
					echo '<td>' . wp_kses_post( $total ) . '</td>';
					echo '<td>' . esc_html( $status ) . '</td>';
					echo '<td>' . esc_html( $billing_email ) . '</td>';
					echo '<td>';
						// Form for updating order status.
						echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
						echo '<input type="hidden" name="action" value="sinpe_update_order_status">';
						echo '<input type="hidden" name="order_id" value="' . esc_attr( $order_id ) . '">';
						wp_nonce_field( 'sinpe_update_order_status' );
						// Dropdown to select new status.
						echo '<select name="new_status">';
						echo '<option value="en_espera">En espera</option>';
						echo '<option value="aprobado">Aprobado</option>';
						echo '<option value="rechazado">Rechazado</option>';
						echo '</select> ';
						echo '<input type="submit" class="button" value="Actualizar">';
						echo '</form>';
					echo '</td>';
				echo '</tr>';
			}

			echo '</tbody>';
			echo '</table>';
		}
		echo '</div>';
	}
}
?>