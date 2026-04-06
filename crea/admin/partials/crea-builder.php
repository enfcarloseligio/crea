<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/admin/partials/crea-builder.php
 *
 * ☀️ Vista principal que actúa como enrutador de las pestañas del Constructor.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

// Obtener la pestaña activa desde la URL (por defecto: 'bases')
$active_tab = isset( $_GET['tab'] ) ? sanitize_file_name( $_GET['tab'] ) : 'bases';
?>

<div class="wrap">
	<h1 class="wp-heading-inline">Gestor de Bases de Datos</h1>
	<hr class="wp-header-end">

	<h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
		<a href="?page=crea-builder&tab=bases" class="nav-tab <?php echo $active_tab == 'bases' ? 'nav-tab-active' : ''; ?>">Mis Bases</a>
		<a href="?page=crea-builder&tab=variables" class="nav-tab <?php echo $active_tab == 'variables' ? 'nav-tab-active' : ''; ?>">Variables (Columnas)</a>
	</h2>

	<?php
	/**
	 * ☀️ Inclusión Dinámica del Contenido de la Pestaña
	 * Busca el archivo correspondiente dentro de la carpeta builder-tabs/
	 */
	$tab_file = CREA_PATH . 'admin/partials/builder-tabs/builder_' . $active_tab . '.php';

	if ( file_exists( $tab_file ) ) {
		include_once $tab_file;
	} else {
		// Mensaje de seguridad por si falta el archivo
		echo '<div class="notice notice-error"><p><strong>Error:</strong> El archivo de vista para esta pestaña no se encuentra disponible en <code>builder-tabs/</code> (<code>' . esc_html( basename( $tab_file ) ) . '</code>).</p></div>';
	}
	?>
</div>