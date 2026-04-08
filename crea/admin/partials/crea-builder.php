<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/admin/partials/crea-builder.php
 *
 * Vista principal que actúa como enrutador del gestor de bases.
 */
if ( ! defined( 'WPINC' ) ) { die; }

// Pestaña por defecto: 'bases'
$active_tab = isset( $_GET['tab'] ) ? sanitize_file_name( $_GET['tab'] ) : 'bases';
?>

<div class="wrap">
	<h1 class="wp-heading-inline">Gestor de Bases de Datos</h1>
	<hr class="wp-header-end">

	<h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
		<a href="?page=crea-builder&tab=bases" class="nav-tab <?php echo $active_tab == 'bases' ? 'nav-tab-active' : ''; ?>">Mis Bases</a>
		<a href="?page=crea-builder&tab=variables" class="nav-tab <?php echo $active_tab == 'variables' ? 'nav-tab-active' : ''; ?>">Variables (Columnas)</a>
		<a href="?page=crea-builder&tab=auditoria" class="nav-tab <?php echo $active_tab == 'auditoria' ? 'nav-tab-active' : ''; ?>">Auditoría de Cambios</a>
	</h2>

	<?php
	/**
	 * Inclusión Dinámica del Contenido de la Pestaña
	 */
	$tab_file = CREA_PATH . 'admin/partials/builder-tabs/builder_' . $active_tab . '.php';

	if ( file_exists( $tab_file ) ) {
		include_once $tab_file;
	} else {
		// Por si la pestaña de variables aún no la creamos, mostramos un mensaje amigable
		echo '<div class="crea-card"><div class="crea-card-header"><h2>En construcción</h2></div><p>Esta sección estará disponible próximamente.</p></div>';
	}
	?>
</div>