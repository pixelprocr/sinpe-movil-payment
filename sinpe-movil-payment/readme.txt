=== SINPE Móvil Payment Gateway ===
Contributors: tu_nombre
Tags: woocommerce, payment, sinpe movil
Requires at least: 5.8
Tested up to: 6.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin para recibir pagos mediante SINPE Móvil en WooCommerce.

== Descripción ==
Este plugin permite a los clientes realizar pagos mediante SINPE Móvil en WooCommerce. Los pagos se marcan como 'Pendiente de confirmación' hasta que el administrador los valide desde el panel de WordPress.

== Instalación ==
1. Sube los archivos del plugin al directorio `/wp-content/plugins/sinpe-movil-payment-plugin`, o instala el plugin directamente desde el repositorio de plugins de WordPress.
2. Activa el plugin a través del menú 'Plugins' en WordPress.
3. Ve a WooCommerce > Ajustes > Pagos y activa 'SINPE Móvil'.
4. Configura los ajustes del plugin según tus necesidades.

== Changelog ==
= 1.0.0 =
* Versión inicial del plugin.

== Instrucciones de Prueba ==
1. Configura el plugin desde WooCommerce > Ajustes > Pagos > SINPE Móvil.
2. Realiza un pedido utilizando la pasarela de pago SINPE Móvil.
3. Verifica que el estado del pedido sea 'Pendiente de confirmación'.
4. Ve al panel de administración de WooCommerce y revisa los detalles del pedido.
5. Cambia el estado del pedido a 'Procesando' o 'Cancelado'.
6. Asegúrate de que se envíen notificaciones por correo tanto al administrador como al cliente sobre el estado del pago.
7. Verifica que los datos ingresados y la captura de pantalla se almacenen correctamente y sean visibles en el panel de administración.

== Notas de Seguridad ==
* Los datos de la cédula se cifran utilizando OpenSSL.
* Se realizan validaciones para asegurar que los archivos subidos cumplan con los formatos y tamaños permitidos.
* El plugin sigue las mejores prácticas de desarrollo en PHP y utiliza hooks y filtros nativos de WooCommerce.