<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure WooCommerce is loaded before using its classes.
if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	return;
}

class WC_Sinpe_Movil_Gateway extends WC_Payment_Gateway {
	private $max_file_size;
	private $sinpe_mobile_numbers;

	public function __construct() {
		$this->id                 = 'sinpe_movil';
		$this->icon               = SINPE_PLUGIN_URL . 'assets/sinpe-icon.png';
		$this->has_fields         = true;
		$this->method_title       = 'SINPE Móvil';
		$this->method_description = 'Permite recibir pagos mediante SINPE Móvil';

		$this->init_form_fields();
		$this->init_settings();

		$this->title                = $this->get_option( 'title' );
		$this->description          = $this->get_option( 'description' );
		$this->max_file_size        = $this->get_option( 'max_file_size' );
		$this->sinpe_mobile_numbers = $this->get_option( 'sinpe_mobile_numbers' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
	}

	/**
	 * Initialize admin fields for this gateway.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => 'Enable/Disable',
				'label'   => 'Enable SINPE Móvil Payment',
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			'title' => array(
				'title'       => 'Title',
				'type'        => 'text',
				'description' => 'This controls the title which the user sees during checkout.',
				'default'     => 'SINPE Móvil',
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => 'Description',
				'type'        => 'textarea',
				'description' => 'This controls the description which the user sees during checkout.',
				'default'     => 'Paga usando SINPE Móvil.',
			),
			'max_file_size' => array(
				'title'       => 'Max File Size (in KB)',
				'type'        => 'number',
				'description' => 'Máximo tamaño permitido para la carga de archivos.',
				'default'     => '2048',
			),
			'sinpe_mobile_numbers' => array(
				'title'       => 'SINPE Móvil Numbers',
				'type'        => 'textarea',
				'description' => 'Ingresa los números de SINPE Móvil permitidos, uno por línea.',
				'default'     => '',
			),
		);
	}

	/**
	 * Outputs front-end payment fields during checkout.
	 */
	public function payment_fields() {
		?>
		<div>
			<p><?php echo esc_html( $this->description ); ?></p>
			<fieldset>
				<p class="form-row form-row-wide">
					<label for="sinpe_nombre">Nombre <span class="required">*</span></label>
					<input type="text" class="input-text" name="sinpe_nombre" id="sinpe_nombre" required>
				</p>
				<p class="form-row form-row-wide">
					<label for="sinpe_apellido">Apellido <span class="required">*</span></label>
					<input type="text" class="input-text" name="sinpe_apellido" id="sinpe_apellido" required>
				</p>
				<p class="form-row form-row-wide">
					<label for="sinpe_cedula">Cédula <span class="required">*</span></label>
					<input type="text" class="input-text" name="sinpe_cedula" id="sinpe_cedula" required>
				</p>
				<p class="form-row form-row-wide">
					<label for="sinpe_autorizacion">Número de Autorización <span class="required">*</span></label>
					<input type="text" class="input-text" name="sinpe_autorizacion" id="sinpe_autorizacion" required>
				</p>
				<p class="form-row form-row-wide">
					<label for="sinpe_monto">Monto Transferido <span class="required">*</span></label>
					<input type="number" class="input-text" name="sinpe_monto" id="sinpe_monto" required>
				</p>
				<p class="form-row form-row-wide">
					<label for="sinpe_captura">Captura de Pantalla <span class="required">*</span></label>
					<input type="file" class="input-text" name="sinpe_captura" id="sinpe_captura" accept="image/*,application/pdf" required>
				</p>
			</fieldset>
		</div>
		<?php
	}

	/**
	 * Validates payment fields input.
	 */
	public function validate_fields() {
		if ( empty( $_POST['sinpe_nombre'] ) ||
		     empty( $_POST['sinpe_apellido'] ) ||
		     empty( $_POST['sinpe_cedula'] ) ||
		     empty( $_POST['sinpe_autorizacion'] ) ||
		     empty( $_POST['sinpe_monto'] ) ) {
			wc_add_notice( 'Por favor completa todos los campos.', 'error' );
			return false;
		}
		return true;
	}

	/**
	 * Process the payment and create order meta.
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		$order->update_status( 'on-hold', 'Pago mediante SINPE Móvil. Pendiente de confirmación.' );

		$order->update_meta_data( 'sinpe_nombre', sanitize_text_field( $_POST['sinpe_nombre'] ) );
		$order->update_meta_data( 'sinpe_apellido', sanitize_text_field( $_POST['sinpe_apellido'] ) );
		$order->update_meta_data( 'sinpe_cedula', $this->encrypt_cedula( sanitize_text_field( $_POST['sinpe_cedula'] ) ) );
		$order->update_meta_data( 'sinpe_autorizacion', sanitize_text_field( $_POST['sinpe_autorizacion'] ) );
		$order->update_meta_data( 'sinpe_monto', sanitize_text_field( $_POST['sinpe_monto'] ) );

		// Debug: Log the entire $_FILES array.
		SINPE_Debug::log( 'Contenido de $_FILES: ' . print_r( $_FILES, true ) );

		// Handle file upload for screenshot.
		if ( isset( $_FILES['sinpe_captura'] ) && ! empty( $_FILES['sinpe_captura']['name'] ) ) {
			$assets_dir = SINPE_PLUGIN_DIR . 'assets/';
			// Create the assets folder if it doesn't exist.
			if ( ! file_exists( $assets_dir ) ) {
				if ( ! mkdir( $assets_dir, 0755, true ) ) {
					wc_add_notice( 'Error creando el directorio para subir la captura.', 'error' );
					SINPE_Debug::log( 'No se pudo crear el directorio de assets: ' . $assets_dir );
					return;
				}
			}

			$file_ext    = pathinfo( $_FILES['sinpe_captura']['name'], PATHINFO_EXTENSION );
			$file_name   = 'sinpe_' . time() . '.' . $file_ext;
			$target_file = $assets_dir . $file_name;

			SINPE_Debug::log( 'Intentando mover el archivo a: ' . $target_file );

			if ( move_uploaded_file( $_FILES['sinpe_captura']['tmp_name'], $target_file ) ) {
				$file_url = SINPE_PLUGIN_URL . 'assets/' . $file_name;
				// Encrypt the file URL.
				$encrypted_url = $this->encrypt_text( $file_url );
				$order->update_meta_data( 'sinpe_captura', $encrypted_url );

				SINPE_Debug::log( 'Captura subida y URL encriptada: ' . $encrypted_url );
			} else {
				wc_add_notice( 'Error subiendo la captura de pantalla.', 'error' );
				SINPE_Debug::log( 'Error al mover el archivo subido a: ' . $target_file );
				return;
			}
		} else {
			SINPE_Debug::log( 'No se encontró archivo en $_FILES[sinpe_captura].' );
		}

		$order->save();
		wc_reduce_stock_levels( $order_id );
		WC()->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Outputs a thank you message on the order thank you page.
	 */
	public function thankyou_page() {
		echo '<p>Gracias por tu pedido. Está pendiente de confirmación.</p>';
	}

	/**
	 * Helper method to encrypt the cedula.
	 *
	 * @param string $cedula
	 * @return string Encrypted text.
	 */
	private function encrypt_cedula( $cedula ) {
		$encryption_key = get_option( 'sinpe_movil_encryption_key' );
		return openssl_encrypt( $cedula, 'AES-128-ECB', $encryption_key );
	}

	/**
	 * Helper method to encrypt a given text (in this case, the file URL).
	 *
	 * @param string $text
	 * @return string Encrypted text.
	 */
	private function encrypt_text( $text ) {
		$encryption_key = get_option( 'sinpe_movil_encryption_key' );
		return openssl_encrypt( $text, 'AES-128-ECB', $encryption_key );
	}

	/**
	 * Initialize hooks for order status changes.
	 */
	public static function init() {
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'order_status_changed' ), 10, 4 );
	}

	/**
	 * Callback for order status change.
	 */
	public static function order_status_changed( $order_id, $old_status, $new_status, $order ) {
		if ( 'on-hold' === $new_status && 'sinpe_movil' === $order->get_payment_method() ) {
			$admin_email = get_option( 'admin_email' );
			$subject     = 'Nuevo pedido pendiente de confirmación';
			$message     = 'Tienes un nuevo pedido pendiente de confirmación. ID del pedido: ' . $order_id;
			wp_mail( $admin_email, $subject, $message );

			$customer_email   = $order->get_billing_email();
			$customer_subject = 'Pago recibido, pendiente de confirmación';
			$customer_message = 'Hemos recibido tu pago y está pendiente de confirmación. Te notificaremos una vez el pago sea procesado.';
			wp_mail( $customer_email, $customer_subject, $customer_message );
		}
	}
}