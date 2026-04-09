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
		add_action( 'wp_ajax_crea_check_slug', array( $this, 'ajax_check_slug' ) );
	}

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
		$table_audit = $wpdb->prefix . 'crea_audit_log';
		$current_user_id = get_current_user_id();
		
		$col_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_forms LIKE 'audit_records'");
		if (empty($col_exists)) {
			$wpdb->query("ALTER TABLE $table_forms ADD COLUMN audit_records TINYINT(1) DEFAULT 1");
		}
		
		$current_time = gmdate('Y-m-d H:i:s'); // GMT 0
		
		$current_wp_user = wp_get_current_user();
		$user_snapshot = array(
			'ID'       => $current_wp_user->ID,
			'username' => $current_wp_user->user_login,
			'name'     => $current_wp_user->display_name
		);

		// 0. MOTOR DE EXPORTACIÓN DINÁMICO (Excel/CSV)
		if ( isset($_GET['crea_export']) && isset($_GET['base_slug']) && current_user_can('manage_options') ) {
			$selected_slug = sanitize_text_field($_GET['base_slug']);
			if (!empty($selected_slug)) {
				$filter_user   = isset( $_GET['filter_user'] ) ? intval( $_GET['filter_user'] ) : 0;
				$filter_action = isset( $_GET['filter_action'] ) ? sanitize_text_field( $_GET['filter_action'] ) : 'all';
				$filter_year   = isset( $_GET['filter_year'] ) ? sanitize_text_field( $_GET['filter_year'] ) : 'all';

				$like_query = '%"base_slug":"' . $wpdb->esc_like($selected_slug) . '"%';
				$current_form_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_forms WHERE form_slug = %s", $selected_slug));
				
				$base_where = $current_form_id ? $wpdb->prepare("(form_id = %d OR changes_json LIKE %s)", $current_form_id, $like_query) : $wpdb->prepare("(changes_json LIKE %s)", $like_query);
				$query_where = "WHERE " . $base_where;
				if ( $filter_user > 0 ) $query_where .= $wpdb->prepare(" AND user_id = %d", $filter_user);
				if ( $filter_action !== 'all' ) $query_where .= $wpdb->prepare(" AND action_type = %s", $filter_action);
				if ( $filter_year !== 'all' ) $query_where .= $wpdb->prepare(" AND YEAR(created_at) = %d", $filter_year);

				$total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_audit $query_where");
				
				if ($total_items > 5000) {
					$redirect_url = remove_query_arg('crea_export');
					$redirect_url = add_query_arg('msg', 'export_limit', $redirect_url);
					wp_redirect($redirect_url);
					exit;
				}

				$logs = $wpdb->get_results("SELECT * FROM $table_audit $query_where ORDER BY created_at DESC", ARRAY_A);
				$export_type = $_GET['crea_export'];
				$filename = "auditoria_{$selected_slug}_" . date('Ymd_His');

				if ($export_type === 'csv') {
					// Agregar cabeceras BOM para que Excel lea los acentos UTF-8 correctamente
					header('Content-Type: text/csv; charset=utf-8');
					header('Content-Disposition: attachment; filename=' . $filename . '.csv');
					$output = fopen('php://output', 'w');
					fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
					
					// Nuevas cabeceras amigables
					fputcsv($output, array('Fecha (UTC)', 'ID Usuario', 'Username', 'Nombre Completo', 'Accion', 'Slug Base', 'Nombre Base', 'Valores Anteriores', 'Nuevos Valores'));
					
					foreach ($logs as $log) {
						$payload = json_decode($log['changes_json'], true);
						$username  = isset($payload['user']['username']) ? $payload['user']['username'] : 'N/A';
						$name      = isset($payload['user']['name']) ? $payload['user']['name'] : 'N/A';
						$base_slug = isset($payload['base_slug']) ? $payload['base_slug'] : 'N/A';
						$base_name = isset($payload['base_name']) ? $payload['base_name'] : 'N/A';
						
						$old_str = [];
						$new_str = [];
						
						if (isset($payload['diff']) && is_array($payload['diff'])) {
							foreach ($payload['diff'] as $campo => $valores) {
								$o_val = isset($valores['old']) ? $valores['old'] : '';
								$n_val = isset($valores['new']) ? $valores['new'] : '';
								$old_str[] = "$campo: $o_val";
								$new_str[] = "$campo: $n_val";
							}
						}
						
						$old_final = implode(' | ', $old_str);
						$new_final = implode(' | ', $new_str);
						
						// Acciones más legibles
						$action_label = $log['action_type'];
						if ($action_label === 'create') $action_label = 'Creación';
						if ($action_label === 'update_meta') $action_label = 'Edición';
						if ($action_label === 'delete') $action_label = 'Eliminación';

						fputcsv($output, array($log['created_at'], $log['user_id'], $username, $name, $action_label, $base_slug, $base_name, $old_final, $new_final));
					}
					fclose($output);
					exit;
				} elseif ($export_type === 'json') {
					header('Content-Type: application/json; charset=utf-8');
					header('Content-Disposition: attachment; filename=' . $filename . '.json');
					echo wp_json_encode($logs);
					exit;
				}
			}
		}

		$labels_map = array('form_name' => 'Nombre Base', 'form_slug' => 'Nombre Sistema', 'data_year' => 'Año de Datos', 'cut_date' => 'Fecha de Corte', 'data_source' => 'Fuente / Referencia', 'description' => 'Comentarios', 'audit_records' => 'Auditoría de Registros');

		// 1. CREAR BASE
		if ( isset( $_POST['create_base'] ) && isset( $_POST['crea_save_base_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_POST['crea_save_base_nonce'], 'crea_save_base_action' ) ) wp_die( 'Error de seguridad.' );
			
			$form_slug = str_replace('-', '_', sanitize_title( $_POST['form_slug'] ));
			$data = array(
				'form_name'     => sanitize_text_field( $_POST['form_name'] ),
				'form_slug'     => $form_slug,
				'data_year'     => sanitize_text_field( $_POST['form_year'] ),
				'data_source'   => sanitize_textarea_field( $_POST['form_source'] ),
				'description'   => sanitize_textarea_field( $_POST['form_comments'] ),
				'audit_records' => isset($_POST['audit_records']) ? intval($_POST['audit_records']) : 1,
				'created_by'    => $current_user_id,
				'updated_by'    => $current_user_id,
				'created_at'    => $current_time,
				'updated_at'    => $current_time
			);
			if ( !empty( $_POST['form_cut_date'] ) ) $data['cut_date'] = sanitize_text_field( $_POST['form_cut_date'] );

			$inserted = $wpdb->insert( $table_forms, $data );
			if ( false === $inserted ) wp_die( 'Error SQL al crear la base.' );
			$new_id = $wpdb->insert_id;
			
			$diff = array();
			foreach ($data as $key => $val) {
				if (in_array($key, ['created_by', 'updated_by', 'created_at', 'updated_at', 'form_id', 'id'])) continue;
				$label = isset($labels_map[$key]) ? $labels_map[$key] : ucwords(str_replace('_', ' ', $key));
				
				$v_new = ($val === '') ? 'Vacío' : $val;
				if ($key === 'audit_records') $v_new = $val == 1 ? 'Activada' : 'Desactivada';
				
				$diff[$label] = array('old' => 'N/A', 'new' => $v_new);
			}
			
			$log_payload = array( 'user' => $user_snapshot, 'base_slug' => $form_slug, 'base_name' => $data['form_name'], 'diff' => $diff );
			$wpdb->insert( $table_audit, array('form_id' => $new_id, 'action_type' => 'create', 'changes_json' => wp_json_encode($log_payload), 'user_id' => $current_user_id, 'created_at' => $current_time) );
			wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_name . '-builder&tab=bases&msg=created' ) );
			exit;
		}

		// 2. EDITAR METADATOS
		if ( isset( $_POST['edit_base'] ) && isset( $_POST['crea_edit_base_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_POST['crea_edit_base_nonce'], 'crea_edit_base_action' ) ) wp_die( 'Error de seguridad.' );
			
			$id = intval( $_POST['edit_id'] );
			$old_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_forms WHERE id = %d", $id ), ARRAY_A );
			
			$data = array(
				'form_name'     => sanitize_text_field( $_POST['edit_name'] ),
				'data_year'     => sanitize_text_field( $_POST['edit_year'] ),
				'data_source'   => sanitize_textarea_field( $_POST['edit_source'] ),
				'description'   => sanitize_textarea_field( $_POST['edit_comments'] ),
				'audit_records' => isset($_POST['edit_audit_records']) ? intval($_POST['edit_audit_records']) : 1,
				'updated_by'    => $current_user_id,
				'updated_at'    => $current_time
			);
			$data['cut_date'] = !empty( $_POST['edit_cut_date'] ) ? sanitize_text_field( $_POST['edit_cut_date'] ) : null;

			$diff = array();
			foreach ($data as $key => $new_val) {
				if (in_array($key, ['created_by', 'updated_by', 'created_at', 'updated_at'])) continue;
				$old_val = isset($old_data[$key]) ? (string)$old_data[$key] : '';
				$new_val_str = (string)$new_val;
				
				if ($old_val !== $new_val_str) {
					$label = isset($labels_map[$key]) ? $labels_map[$key] : ucwords(str_replace('_', ' ', $key));
					$v_old = ($old_val === '') ? 'Vacío' : $old_val;
					$v_new = ($new_val_str === '') ? 'Vacío' : $new_val_str;
					
					if ($key === 'audit_records') {
						$v_old = $old_val == '1' ? 'Activada' : 'Desactivada';
						$v_new = $new_val_str == '1' ? 'Activada' : 'Desactivada';
					}
					$diff[$label] = array( 'old' => $v_old, 'new' => $v_new );
				}
			}

			$updated = $wpdb->update( $table_forms, $data, array( 'id' => $id ) );

			if ( $updated !== false && !empty($diff) ) {
				$log_payload = array( 'user' => $user_snapshot, 'base_slug' => $old_data['form_slug'], 'base_name' => $data['form_name'], 'diff' => $diff );
				$wpdb->insert( $table_audit, array('form_id' => $id, 'action_type' => 'update_meta', 'changes_json' => wp_json_encode($log_payload), 'user_id' => $current_user_id, 'created_at' => $current_time) );
			}
			wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_name . '-builder&tab=bases&msg=updated' ) );
			exit;
		}

		// 3. ELIMINAR BASE
		if ( isset( $_POST['delete_base'] ) && isset( $_POST['crea_delete_base_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_POST['crea_delete_base_nonce'], 'crea_delete_base_action' ) ) wp_die( 'Error de seguridad.' );
			
			$id = intval( $_POST['delete_id'] );
			$slug = sanitize_title( $_POST['delete_slug'] );
			
			$old_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_forms WHERE id = %d", $id ), ARRAY_A );
			$base_name = $old_data ? $old_data['form_name'] : $slug;
			
			$diff = array();
			if ($old_data) {
				foreach ($old_data as $key => $val) {
					if (in_array($key, ['id', 'created_by', 'updated_by', 'created_at', 'updated_at'])) continue;
					$label = isset($labels_map[$key]) ? $labels_map[$key] : ucwords(str_replace('_', ' ', $key));
					
					$v_old = ($val === '' || $val === null) ? 'Vacío' : $val;
					if ($key === 'audit_records') $v_old = $val == 1 ? 'Activada' : 'Desactivada';
					
					$diff[$label] = array('old' => $v_old, 'new' => 'N/A (Eliminado)');
				}
			}
			$diff['Estado Crítico'] = array('old' => 'Base Activa', 'new' => 'Base y registros eliminados permanentemente');

			$physical_table = $wpdb->prefix . "crea_data_" . $slug;
			$wpdb->query("DROP TABLE IF EXISTS $physical_table");
			
			$table_fields = $wpdb->prefix . 'crea_fields';
			$wpdb->delete( $table_fields, array( 'form_id' => $id ), array( '%d' ) );
			$wpdb->delete( $table_forms, array( 'id' => $id ), array( '%d' ) );
			
			$log_payload = array( 'user' => $user_snapshot, 'base_slug' => $slug, 'base_name' => $base_name, 'diff' => $diff );
			$wpdb->insert( $table_audit, array('form_id' => $id, 'action_type' => 'delete', 'changes_json' => wp_json_encode($log_payload), 'user_id' => $current_user_id, 'created_at' => $current_time) );
			wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_name . '-builder&tab=bases&msg=deleted' ) );
			exit;
		}

		// 4. GUARDAR APARIENCIA
		if ( isset( $_POST['crea_save_appearance'] ) && isset( $_POST['crea_save_appearance_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_POST['crea_save_appearance_nonce'], 'crea_save_appearance_action' ) ) wp_die( 'Error de seguridad.' );
			$admin_colors = array(
				'th_bg' => sanitize_hex_color( $_POST['admin_th_bg'] ), 'th_text' => sanitize_hex_color( $_POST['admin_th_text'] ),
				'odd_bg' => sanitize_hex_color( $_POST['admin_odd_bg'] ), 'odd_text' => sanitize_hex_color( $_POST['admin_odd_text'] ),
				'even_bg' => sanitize_hex_color( $_POST['admin_even_bg'] ), 'even_text' => sanitize_hex_color( $_POST['admin_even_text'] ),
			);
			$front_colors = array(
				'primary' => sanitize_hex_color( $_POST['front_primary'] ), 'th_bg' => sanitize_hex_color( $_POST['front_th_bg'] ), 'th_text' => sanitize_hex_color( $_POST['front_th_text'] ),
			);
			update_option( 'crea_admin_colors', $admin_colors ); update_option( 'crea_front_colors', $front_colors );
			wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_name . '-settings&tab=apariencia&msg=appearance_saved' ) );
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
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0' );
		wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true );
		wp_enqueue_style( $this->plugin_name . '-admin-css', CREA_URL . 'admin/assets/css/crea-admin.css', array(), CREA_VERSION, 'all' );
		
		$default_admin_colors = [ 'th_bg' => '#F8FAFC', 'th_text' => '#0F172A', 'odd_bg' => '#FFFFFF', 'odd_text' => '#475569', 'even_bg' => '#F1F5F9', 'even_text' => '#475569' ];
		$admin_colors = wp_parse_args( get_option( 'crea_admin_colors', [] ), $default_admin_colors );
		
		$custom_css = "
			:root {
				--crea-th-bg: {$admin_colors['th_bg']}; --crea-th-text: {$admin_colors['th_text']};
				--crea-tr-odd-bg: {$admin_colors['odd_bg']}; --crea-tr-odd-text: {$admin_colors['odd_text']};
				--crea-tr-even-bg: {$admin_colors['even_bg']}; --crea-tr-even-text: {$admin_colors['even_text']};
			}
			.select2-container .select2-selection--single { height: 30px; border-color: #8c8f94; }
			.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 30px; color: #2c3338; }
			.select2-container--default .select2-selection--single .select2-selection__arrow { height: 30px; }
		";
		wp_add_inline_style( $this->plugin_name . '-admin-css', $custom_css );
		wp_enqueue_script( $this->plugin_name . '-admin-js', CREA_URL . 'admin/assets/js/crea-admin.js', array('jquery', 'wp-color-picker', 'select2'), CREA_VERSION, true );
		wp_localize_script( $this->plugin_name . '-admin-js', 'crea_ajax_obj', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'crea_ajax_nonce' ) ));
	}

	public function display_dashboard() { echo '<div class="wrap"><h2>Dashboard</h2></div>'; }
	public function display_builder() { include_once CREA_PATH . 'admin/partials/crea-builder.php'; }
	public function display_settings() { 
		$partial_file = CREA_PATH . 'admin/partials/crea-settings.php';
		if ( file_exists( $partial_file ) ) include_once $partial_file;
	}
}