<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/includes/class-crea.php
 *
 * Clase orquestadora que coordina los módulos de administración y frontend.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class CREA {

	public function __construct() {
		$this->load_dependencies();
	}

	/**
	 * Carga de archivos necesarios.
	 */
	private function load_dependencies() {
		$admin_file = CREA_PATH . 'admin/class-crea-admin.php';
		if ( file_exists( $admin_file ) ) {
			require_once $admin_file;
		}
	}

	/**
	 * Registro de hooks principales.
	 */
	public function run() {
		if ( class_exists( 'CREA_Admin' ) ) {
			$admin = new CREA_Admin( 'crea' );
			add_action( 'admin_menu', array( $admin, 'add_menu' ) );
		}
	}
}