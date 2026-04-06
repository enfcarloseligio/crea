<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/admin/class-crea-admin.php
 */
if ( ! defined( 'WPINC' ) ) { die; }

class CREA_Admin {

	private $plugin_name;

	public function __construct( $plugin_name ) {
		$this->plugin_name = $plugin_name;
		add_action( 'admin_init', array( $this, 'process_form_actions' ) );
		
		// Hook AJAX para validar el slug en tiempo real
		add_action( 'wp_ajax_crea_check_slug', array( $this, 'ajax_check_slug' ) );
	}

	/**
	 * Endpoint AJAX para validar que el slug no exista.
	 */
	public function ajax_check_slug() {
		check_ajax_referer( 'crea_ajax_nonce', 'security' );
		global $wpdb;
		
		$slug = sanitize_title( $_POST['slug'] );
		$slug = str_replace('-', '_', $slug);
		
		$table_forms = $wpdb->prefix . 'crea_forms';
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_forms WHERE form_slug = %s", $slug ) );
		
		wp_send_json( array( 'exists' => (bool) $exists, 'sanitized' => $slug ) );
	}

	public function process_form_actions() {
		global $wpdb;
		$table_forms = $wpdb->prefix . 'crea_forms';
		$current_user_id = get_current_user_id();
		$current_time = current_time('mysql');

		// ☀️ 1. CREAR BASE (Corregido para soportar fechas vacías)
		if ( isset( $_POST['create_base'] ) && isset( $_POST['crea_save_base_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_POST['crea_save_base_nonce'], 'crea_save_base_action' ) ) wp_die( 'Error de seguridad.' );
			
			$form_slug = str_replace('-', '_', sanitize_title( $_POST['form_slug'] ));
			
			// Armamos los datos dinámicamente
			$data = array(
				'form_name'   => sanitize_text_field( $_POST['form_name'] ),
				'form_slug'   => $form_slug,
				'data_year'   => sanitize_text_field( $_POST['form_year'] ),
				'data_source' => sanitize_text_field( $_POST['form_source'] ),
				'description' => sanitize_textarea_field( $_POST['form_comments'] ),
				'created_by'  => $current_user_id,
				'updated_by'  => $current_user_id,
				'created_at'  => $current_time,
				'updated_at'  => $current_time
			);

			// Solo inyectamos la fecha si no está vacía para evitar errores SQL
			if ( !empty( $_POST['form_cut_date'] ) ) {
				$data['cut_date'] = sanitize_text_field( $_POST['form_cut_date'] );
			}

			$inserted = $wpdb->insert( $table_forms, $data );

			// Verificamos si falló a nivel SQL
			if ( false === $inserted ) {
				wp_die( 'Error SQL al crear la base: ' . $wpdb->last_error . '.<br><strong>Solución:</strong> Asegúrate de Desactivar y Reactivar el plugin para que se apliquen las nuevas columnas.' );
			}

			wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_name . '-builder&tab=bases&msg=created' ) );
			exit;
		}

		// ☀️ 2. EDITAR METADATOS (Corregido y con auditoría del editor)
		if ( isset( $_POST['edit_base'] ) && isset( $_POST['crea_edit_base_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_POST['crea_edit_base_nonce'], 'crea_edit_base_action' ) ) wp_die( 'Error de seguridad.' );
			
			$id = intval( $_POST['edit_id'] );
			
			$data = array(
				'form_name'   => sanitize_text_field( $_POST['edit_name'] ),
				'data_year'   => sanitize_text_field( $_POST['edit_year'] ),
				'data_source' => sanitize_text_field( $_POST['edit_source'] ),
				'description' => sanitize_textarea_field( $_POST['edit_comments'] ),
				'updated_by'  => $current_user_id, // Se actualiza quién editó
				'updated_at'  => $current_time     // Se actualiza la hora de edición
			);

			if ( !empty( $_POST['edit_cut_date'] ) ) {
				$data['cut_date'] = sanitize_text_field( $_POST['edit_cut_date'] );
			} else {
				$data['cut_date'] = null; // Si borró la fecha, guardamos null
			}

			$updated = $wpdb->update( $table_forms, $data, array( 'id' => $id ) );

			if ( false === $updated ) {
				wp_die( 'Error SQL al actualizar la base: ' . $wpdb->last_error );
			}

			wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_name . '-builder&tab=bases&msg=updated' ) );
			exit;
		}

		// 3. ELIMINAR BASE
		if ( isset( $_POST['delete_base'] ) && isset( $_POST['crea_delete_base_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_POST['crea_delete_base_nonce'], 'crea_delete_base_action' ) ) wp_die( 'Error de seguridad.' );
			
			$id = intval( $_POST['delete_id'] );
			$slug = sanitize_title( $_POST['delete_slug'] );
			
			$physical_table = $wpdb->prefix . "crea_data_" . $slug;
			$wpdb->query("DROP TABLE IF EXISTS $physical_table");
			
			$table_fields = $wpdb->prefix . 'crea_fields';
			$wpdb->delete( $table_fields, array( 'form_id' => $id ), array( '%d' ) );
			$wpdb->delete( $table_forms, array( 'id' => $id ), array( '%d' ) );
			
			wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_name . '-builder&tab=bases&msg=deleted' ) );
			exit;
		}
	}

	public function add_menu() {
		$h1 = add_menu_page( 'CREA Builder', 'CREA Builder', 'manage_options', $this->plugin_name, array( $this, 'display_dashboard' ), 'dashicons-database', 30 );
		$h2 = add_submenu_page( $this->plugin_name, 'Mis Bases', 'Mis Bases', 'manage_options', $this->plugin_name . '-builder', array( $this, 'display_builder' ) );
		$h3 = add_submenu_page( $this->plugin_name, 'Configuración', 'Configuración', 'manage_options', $this->plugin_name . '-settings', array( $this, 'display_settings' ) );

		add_action( "admin_print_scripts-{$h1}", array( $this, 'enqueue_admin_assets' ) );
		add_action( "admin_print_scripts-{$h2}", array( $this, 'enqueue_admin_assets' ) );
		add_action( "admin_print_scripts-{$h3}", array( $this, 'enqueue_admin_assets' ) );
	}

	public function enqueue_admin_assets() {
		wp_enqueue_style( $this->plugin_name . '-admin-css', CREA_URL . 'admin/assets/css/crea-admin.css', array(), CREA_VERSION, 'all' );
		wp_enqueue_script( $this->plugin_name . '-admin-js', CREA_URL . 'admin/assets/js/crea-admin.js', array(), CREA_VERSION, true );
		
		wp_localize_script( $this->plugin_name . '-admin-js', 'crea_ajax_obj', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'crea_ajax_nonce' )
		));
	}

	public function display_dashboard() { echo '<div class="wrap"><h2>Dashboard</h2></div>'; }
	public function display_builder() { include_once CREA_PATH . 'admin/partials/crea-builder.php'; }
	public function display_settings() { echo '<div class="wrap"><h2>Configuración</h2></div>'; }
}