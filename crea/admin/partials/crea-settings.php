<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/admin/partials/crea-settings.php
 *
 * Vista principal que actúa como enrutador de las pestañas de Configuración.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

// Obtener la pestaña activa desde la URL (por defecto: 'general')
$active_tab = isset( $_GET['tab'] ) ? sanitize_file_name( $_GET['tab'] ) : 'general';
?>

<div class="wrap">
	<h1 class="wp-heading-inline">Configuración Global</h1>
	<hr class="wp-header-end">

	<h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
		<a href="?page=crea-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">Ajustes Generales</a>
		<a href="?page=crea-settings&tab=apariencia" class="nav-tab <?php echo $active_tab == 'apariencia' ? 'nav-tab-active' : ''; ?>">Apariencia</a>
		<a href="?page=crea-settings&tab=permisos" class="nav-tab <?php echo $active_tab == 'permisos' ? 'nav-tab-active' : ''; ?>">Permisos y Accesos</a>
	</h2>

	<?php
	/**
	 * Inclusión Dinámica del Contenido de la Pestaña
	 * Busca el archivo correspondiente dentro de la carpeta settings-tabs/
	 */
	$tab_file = CREA_PATH . 'admin/partials/settings-tabs/settings_' . $active_tab . '.php';

	if ( file_exists( $tab_file ) ) {
		include_once $tab_file;
	} else {
		echo '<div class="notice notice-error"><p><strong>Error:</strong> El archivo de vista para esta pestaña no se encuentra (<code>' . esc_html( basename( $tab_file ) ) . '</code>).</p></div>';
	}
	?>
</div>