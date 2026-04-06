<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/admin/class-crea-admin.php
 *
 * ☀️ Gestión del menú y submenús en el panel de administración.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class CREA_Admin {

	private $plugin_name;

	public function __construct( $plugin_name ) {
		$this->plugin_name = $plugin_name;
	}

	/**
	 * ☀️ Registra el menú principal y los submenús en la barra lateral.
	 */
	public function add_menu() {
		// Menú Principal (Dashboard)
		add_menu_page(
			'CREA Dashboard',
			'CREA Builder',
			'manage_options',
			$this->plugin_name,
			array( $this, 'display_dashboard' ),
			'dashicons-database',
			30
		);

		// Submenú: Constructor de Bases
		add_submenu_page(
			$this->plugin_name,
			'Constructor de Bases',
			'Mis Bases',
			'manage_options',
			$this->plugin_name . '-builder',
			array( $this, 'display_builder' )
		);

		// Submenú: Configuración
		add_submenu_page(
			$this->plugin_name,
			'Configuración General',
			'Configuración',
			'manage_options',
			$this->plugin_name . '-settings',
			array( $this, 'display_settings' )
		);
	}

	/**
	 * ☀️ Renderiza la vista del Dashboard.
	 */
	public function display_dashboard() {
		echo '<div class="wrap"><h1>CREA - Dashboard Global</h1><p>Aquí mostraremos las estadísticas globales de tus bases de datos.</p></div>';
	}

	/**
	 * ☀️ Renderiza la vista del Constructor (La herramienta principal).
	 */
	public function display_builder() {
		// Llamamos a un archivo externo (partial) para mantener el código limpio.
		$partial_file = CREA_PATH . 'admin/partials/crea-builder.php';
		if ( file_exists( $partial_file ) ) {
			include_once $partial_file;
		} else {
			echo '<div class="notice notice-error"><p>Falta el archivo de vista del constructor.</p></div>';
		}
	}

	/**
	 * ☀️ Renderiza la vista de Configuración.
	 */
	public function display_settings() {
		echo '<div class="wrap"><h1>Configuración de CREA</h1><p>Ajustes de exportación y permisos en construcción.</p></div>';
	}
}