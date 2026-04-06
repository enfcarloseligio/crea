<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/crea.php
 *
 * @package           CREA
 * @author            Juan Carlos De La Cruz Eligio | @enfcarloseligio
 * @copyright         2026 Juan Carlos De La Cruz Eligio
 * @license           GPL-2.0-or-later
 *
 * Plugin Name:       CREA - Custom Database Builder
 * Description:       Constructor Relacional de Entornos Analíticos. Plataforma dinámica para la creación de bases de datos y análisis estadístico.
 * Version:           0.0.20
 * Author:            Juan Carlos De La Cruz Eligio | @enfcarloseligio
 * Text Domain:       crea
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Definición de constantes globales.
 */
define( 'CREA_VERSION', '0.0.20' );
define( 'CREA_PATH', plugin_dir_path( __FILE__ ) );
define( 'CREA_URL', plugin_dir_url( __FILE__ ) );

/**
 * Lógica de activación segura.
 */
function activate_crea_plugin() {
	$db_file = CREA_PATH . 'includes/class-crea-db.php';
	
	if ( file_exists( $db_file ) ) {
		require_once $db_file;
		if ( class_exists( 'CREA_DB' ) ) {
			$crea_db = new CREA_DB();
			$crea_db->create_master_tables();
		}
	}

	$directorios = array( CREA_PATH . 'api', CREA_PATH . 'uploads' );
	foreach ( $directorios as $dir ) {
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'activate_crea_plugin' );

/**
 * Carga del Orquestador.
 */
$includes_crea = CREA_PATH . 'includes/class-crea.php';
if ( file_exists( $includes_crea ) ) {
	require_once $includes_crea;
	if ( class_exists( 'CREA' ) ) {
		$crea_core = new CREA();
		$crea_core->run();
	}
}