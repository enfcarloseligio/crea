<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/admin/class-crea-admin.php
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class CREA_Admin {

	private $plugin_name;

	public function __construct( $plugin_name ) {
		$this->plugin_name = $plugin_name;
	}

	public function add_menu() {
		$hook_dashboard = add_menu_page( 'CREA Dashboard', 'CREA Builder', 'manage_options', $this->plugin_name, array( $this, 'display_dashboard' ), 'dashicons-database', 30 );
		$hook_builder   = add_submenu_page( $this->plugin_name, 'Constructor de Bases', 'Mis Bases', 'manage_options', $this->plugin_name . '-builder', array( $this, 'display_builder' ) );
		$hook_settings  = add_submenu_page( $this->plugin_name, 'Configuración General', 'Configuración', 'manage_options', $this->plugin_name . '-settings', array( $this, 'display_settings' ) );

		/**
		 * Registra la carga de recursos estáticos solo en las páginas de CREA.
		 */
		add_action( "admin_print_scripts-{$hook_dashboard}", array( $this, 'enqueue_admin_assets' ) );
		add_action( "admin_print_scripts-{$hook_builder}", array( $this, 'enqueue_admin_assets' ) );
		add_action( "admin_print_scripts-{$hook_settings}", array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Encola los estilos y scripts globales del área administrativa.
	 */
	public function enqueue_admin_assets() {
		wp_enqueue_style( $this->plugin_name . '-admin-css', CREA_URL . 'admin/assets/css/crea-admin.css', array(), CREA_VERSION, 'all' );
		wp_enqueue_script( $this->plugin_name . '-admin-js', CREA_URL . 'admin/assets/js/crea-admin.js', array(), CREA_VERSION, true );
	}

	public function display_dashboard() {
		echo '<div class="wrap"><div class="crea-card"><h2>CREA Dashboard</h2><p>Estadísticas globales.</p></div></div>';
	}

	public function display_builder() {
		$partial_file = CREA_PATH . 'admin/partials/crea-builder.php';
		if ( file_exists( $partial_file ) ) {
			include_once $partial_file;
		}
	}

	public function display_settings() {
		echo '<div class="wrap"><div class="crea-card"><h2>Configuración</h2><p>En construcción.</p></div></div>';
	}
}