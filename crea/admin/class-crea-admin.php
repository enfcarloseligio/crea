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
		$table_fields = $wpdb->prefix . 'crea_fields';
		$current_user_id = get_current_user_id();
		
		$col_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_forms LIKE 'audit_records'");
		if (empty($col_exists)) {
			$wpdb->query("ALTER TABLE $table_forms ADD COLUMN audit_records TINYINT(1) DEFAULT 1");
		}
		
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		}
		
		$sql_fields = "CREATE TABLE $table_fields (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			form_id bigint(20) NOT NULL,
			field_name varchar(255) NOT NULL,
			field_slug varchar(255) NOT NULL,
			field_type varchar(50) NOT NULL,
			is_required tinyint(1) DEFAULT 0,
			config longtext,
			created_by bigint(20),
			updated_by bigint(20),
			created_at datetime,
			updated_at datetime,
			PRIMARY KEY  (id)
		) {$wpdb->get_charset_collate()};";
		dbDelta( $sql_fields );

		$current_time = gmdate('Y-m-d H:i:s');
		
		$current_wp_user = wp_get_current_user();
		$user_snapshot = array(
			'ID'       => $current_wp_user->ID,
			'username' => $current_wp_user->user_login,
			'name'     => $current_wp_user->display_name
		);

		// EXPORTACIÓN DINÁMICA
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
					header('Content-Type: text/csv; charset=utf-8');
					header('Content-Disposition: attachment; filename=' . $filename . '.csv');
					$output = fopen('php://output', 'w');
					fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
					
					fputcsv($output, array('Fecha (UTC)', 'ID Usuario', 'Username', 'Nombre Real', 'Accion', 'Slug Base', 'Nombre Base', 'Valores Anteriores', 'Nuevos Valores'));
					
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
						
						// Etiquetas limpias para CSV
						$action_label = $log['action_type'];
						if ($action_label === 'create') $action_label = 'Creación de Base';
						if ($action_label === 'update_meta') $action_label = 'Edición de Base';
						if ($action_label === 'delete') $action_label = 'Eliminación de Base';
						if ($action_label === 'add_col') $action_label = 'Creación de Variable';
						if ($action_label === 'edit_col') $action_label = 'Edición de Variable';
						if ($action_label === 'delete_col') $action_label = 'Eliminación de Variable';

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
			
			$wpdb->delete( $table_fields, array( 'form_id' => $id ), array( '%d' ) );
			$wpdb->delete( $table_forms, array( 'id' => $id ), array( '%d' ) );
			
			$log_payload = array( 'user' => $user_snapshot, 'base_slug' => $slug, 'base_name' => $base_name, 'diff' => $diff );
			$wpdb->insert( $table_audit, array('form_id' => $id, 'action_type' => 'delete', 'changes_json' => wp_json_encode($log_payload), 'user_id' => $current_user_id, 'created_at' => $current_time) );
			wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_name . '-builder&tab=bases&msg=deleted' ) );
			exit;
		}

		// 4. CREAR VARIABLE (COLUMNA)
		if ( isset( $_POST['create_variable'] ) && isset( $_POST['crea_save_variable_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_POST['crea_save_variable_nonce'], 'crea_save_variable_action' ) ) wp_die( 'Error de seguridad.' );
			
			$base_id = intval($_POST['base_id']);
			$base_info = $wpdb->get_row($wpdb->prepare("SELECT form_name, form_slug FROM $table_forms WHERE id = %d", $base_id));
			if (!$base_info) wp_die('Base de datos no encontrada.');

			$field_name = sanitize_text_field($_POST['field_name']);
			$field_slug = str_replace('-', '_', sanitize_title($_POST['field_slug']));
			$field_type = sanitize_text_field($_POST['field_type']);
			$is_required = isset($_POST['is_required']) ? 1 : 0;
			
			$human_types = [
				'text_short' => 'Texto Corto', 'text_long' => 'Texto Largo', 'text_html' => 'Editor HTML',
				'num_discrete' => 'Numérico Discreto', 'num_continuous' => 'Numérico Continuo',
				'date' => 'Fecha', 'time' => 'Hora',
				'select' => 'Menú Desplegable', 'radio' => 'Botones de Radio', 'checkbox' => 'Casillas Múltiples',
				'relation' => 'Base Relacional'
			];

			$config = [];
			$diff = [
				'Nombre Variable' => ['old' => 'N/A', 'new' => $field_name],
				'Slug SQL (Columna)' => ['old' => 'N/A', 'new' => $field_slug],
				'Tipo de Dato' => ['old' => 'N/A', 'new' => isset($human_types[$field_type]) ? $human_types[$field_type] : $field_type],
				'Dato Obligatorio' => ['old' => 'N/A', 'new' => $is_required ? 'Sí' : 'No']
			];

			if (in_array($field_type, ['text_short', 'text_long'])) {
				$config['max_length'] = intval($_POST['text_max_length']);
				$diff['Caracteres Máximos'] = ['old' => 'N/A', 'new' => $config['max_length']];
			} elseif ($field_type === 'num_discrete') {
				$config['digits'] = intval($_POST['num_disc_digits']);
				$diff['Máx. Dígitos Enteros'] = ['old' => 'N/A', 'new' => $config['digits']];
			} elseif ($field_type === 'num_continuous') {
				$config['integers'] = intval($_POST['num_cont_integers']);
				$config['decimals'] = intval($_POST['num_cont_decimals']);
				$diff['Máx. Enteros'] = ['old' => 'N/A', 'new' => $config['integers']];
				$diff['Máx. Decimales'] = ['old' => 'N/A', 'new' => $config['decimals']];
			} elseif ($field_type === 'time') {
				$config['time_zone'] = sanitize_text_field($_POST['time_zone_default']);
				$diff['Zona Horaria'] = ['old' => 'N/A', 'new' => $config['time_zone'] === 'utc' ? 'UTC Absoluta' : 'Local del Sistema'];
			} elseif (in_array($field_type, ['select', 'radio', 'checkbox'])) {
				$config['options'] = sanitize_textarea_field($_POST['categorical_options']);
				$config['default'] = isset($_POST['categorical_default']) ? array_map('sanitize_text_field', $_POST['categorical_default']) : [];
				$config['id_type'] = sanitize_text_field($_POST['categorical_id_type']);
				
				$diff['Opciones de Catálogo'] = ['old' => 'N/A', 'new' => str_replace("\n", ", ", $config['options'])];
				$diff['Selección por Defecto'] = ['old' => 'N/A', 'new' => empty($config['default']) ? 'Ninguna' : implode(", ", $config['default'])];
				
				$id_labels = ['none' => 'Texto Plano (Sin IDs)', 'auto' => 'Automática (1, 2, 3...)', 'manual' => 'Manual (Definida por usuario)'];
				$diff['Codificación (Estadística)'] = ['old' => 'N/A', 'new' => $id_labels[$config['id_type']]];

				if ($config['id_type'] === 'manual') {
					$config['manual_codes'] = sanitize_textarea_field($_POST['categorical_manual_codes']);
					$diff['Códigos Manuales Asignados'] = ['old' => 'N/A', 'new' => str_replace("\n", ", ", $config['manual_codes'])];
				}
			} elseif ($field_type === 'relation') {
				$config['rel_base'] = sanitize_text_field($_POST['rel_base_slug']);
				$config['rel_field'] = sanitize_text_field($_POST['rel_field_slug']);
				$config['rel_cond_field'] = sanitize_text_field($_POST['rel_cond_field']);
				$config['rel_cond_value'] = sanitize_text_field($_POST['rel_cond_value']);

				$diff['Base Maestra Vinculada'] = ['old' => 'N/A', 'new' => empty($config['rel_base']) ? 'Ninguna' : $config['rel_base']];
				$diff['Variable a Extraer'] = ['old' => 'N/A', 'new' => empty($config['rel_field']) ? 'Ninguna' : $config['rel_field']];
				if (!empty($config['rel_cond_field'])) {
					$diff['Condición de Filtrado'] = ['old' => 'N/A', 'new' => "SÓLO SI " . $config['rel_cond_field'] . " = " . $config['rel_cond_value']];
				}
			}

			$inserted_field = $wpdb->insert($table_fields, [
				'form_id' => $base_id,
				'field_name' => $field_name,
				'field_slug' => $field_slug,
				'field_type' => $field_type,
				'is_required' => $is_required,
				'config' => wp_json_encode($config),
				'created_by' => $current_user_id,
				'updated_by' => $current_user_id,
				'created_at' => $current_time,
				'updated_at' => $current_time
			]);

			if ( false === $inserted_field ) wp_die( 'Error crítico al guardar la variable: ' . $wpdb->last_error );

			$physical_table = $wpdb->prefix . "crea_data_" . $base_info->form_slug;
			$sql_physical = "CREATE TABLE IF NOT EXISTS $physical_table (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				created_at datetime DEFAULT NULL,
				created_by bigint(20) DEFAULT NULL,
				updated_at datetime DEFAULT NULL,
				updated_by bigint(20) DEFAULT NULL,
				PRIMARY KEY  (id)
			) {$wpdb->get_charset_collate()};";
			dbDelta( $sql_physical );

			$sql_type = "TEXT";
			if (in_array($field_type, ['text_short', 'select', 'radio', 'relation'])) {
				$sql_type = "VARCHAR(" . (isset($config['max_length']) ? $config['max_length'] : 255) . ")";
			} elseif ($field_type === 'num_discrete') {
				$sql_type = "INT";
			} elseif ($field_type === 'num_continuous') {
				$sql_type = "DECIMAL(15,4)";
			} elseif ($field_type === 'date') {
				$sql_type = "DATE";
			} elseif ($field_type === 'time') {
				$sql_type = "TIME";
			}

			$col_check = $wpdb->get_results("SHOW COLUMNS FROM $physical_table LIKE '$field_slug'");
			if (empty($col_check)) {
				$alter1 = $wpdb->query("ALTER TABLE $physical_table ADD COLUMN $field_slug $sql_type");
				if ( false === $alter1 ) wp_die('Error al crear columna en tabla física: ' . $wpdb->last_error);
				
				if (in_array($field_type, ['select', 'radio', 'checkbox']) && isset($config['id_type']) && in_array($config['id_type'], ['auto', 'manual'])) {
					$alter2 = $wpdb->query("ALTER TABLE $physical_table ADD COLUMN id_$field_slug INT");
					if ( false === $alter2 ) wp_die('Error al crear columna de ID estadístico: ' . $wpdb->last_error);
				}
			}

			$log_payload = array('user' => $user_snapshot, 'base_slug' => $base_info->form_slug, 'base_name' => $base_info->form_name, 'diff' => $diff);
			$wpdb->insert( $table_audit, array('form_id' => $base_id, 'action_type' => 'add_col', 'changes_json' => wp_json_encode($log_payload), 'user_id' => $current_user_id, 'created_at' => $current_time) );

			wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_name . '-builder&tab=variables&base_id=' . $base_id . '&msg=var_created' ) );
			exit;
		}

		// 5. EDITAR VARIABLE (Etiqueta y estado)
		if ( isset( $_POST['edit_variable'] ) && isset( $_POST['crea_edit_var_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_POST['crea_edit_var_nonce'], 'crea_edit_var_action' ) ) wp_die( 'Error de seguridad.' );
			
			$var_id = intval($_POST['edit_var_id']);
			$old_field = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_fields WHERE id = %d", $var_id), ARRAY_A);
			if (!$old_field) wp_die('Variable no encontrada.');
			
			$base_id = $old_field['form_id'];
			$base_info = $wpdb->get_row($wpdb->prepare("SELECT form_name, form_slug FROM $table_forms WHERE id = %d", $base_id));
			
			$new_name = sanitize_text_field($_POST['edit_var_name']);
			$new_req = isset($_POST['edit_var_req']) ? 1 : 0;
			
			$diff = [];
			if ($old_field['field_name'] !== $new_name) {
				$diff['Nombre Variable'] = ['old' => $old_field['field_name'], 'new' => $new_name];
			}
			if ($old_field['is_required'] != $new_req) {
				$diff['Dato Obligatorio'] = ['old' => $old_field['is_required'] ? 'Sí' : 'No', 'new' => $new_req ? 'Sí' : 'No'];
			}
			
			if (!empty($diff)) {
				$wpdb->update($table_fields, [
					'field_name' => $new_name,
					'is_required' => $new_req,
					'updated_by' => $current_user_id,
					'updated_at' => $current_time
				], ['id' => $var_id]);
				
				$log_payload = array('user' => $user_snapshot, 'base_slug' => $base_info->form_slug, 'base_name' => $base_info->form_name, 'diff' => $diff);
				$wpdb->insert( $table_audit, array('form_id' => $base_id, 'action_type' => 'edit_col', 'changes_json' => wp_json_encode($log_payload), 'user_id' => $current_user_id, 'created_at' => $current_time) );
			}
			
			wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_name . '-builder&tab=variables&base_id=' . $base_id . '&msg=var_updated' ) );
			exit;
		}

		// 6. ELIMINAR VARIABLE (Destrucción de columna MySQL)
		if ( isset( $_POST['delete_variable'] ) && isset( $_POST['crea_delete_var_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_POST['crea_delete_var_nonce'], 'crea_delete_var_action' ) ) wp_die( 'Error de seguridad.' );
			
			$var_id = intval($_POST['delete_var_id']);
			$base_id = intval($_POST['base_id']);
			
			$old_field = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_fields WHERE id = %d", $var_id), ARRAY_A);
			if (!$old_field) wp_die('Variable no encontrada.');
			
			$base_info = $wpdb->get_row($wpdb->prepare("SELECT form_name, form_slug FROM $table_forms WHERE id = %d", $base_id));
			
			$physical_table = $wpdb->prefix . "crea_data_" . $base_info->form_slug;
			$field_slug = $old_field['field_slug'];
			
			$col_check = $wpdb->get_results("SHOW COLUMNS FROM $physical_table LIKE '$field_slug'");
			if (!empty($col_check)) {
				$wpdb->query("ALTER TABLE $physical_table DROP COLUMN $field_slug");
				
				$col_id_check = $wpdb->get_results("SHOW COLUMNS FROM $physical_table LIKE 'id_$field_slug'");
				if (!empty($col_id_check)) {
					$wpdb->query("ALTER TABLE $physical_table DROP COLUMN id_$field_slug");
				}
			}
			
			$wpdb->delete($table_fields, ['id' => $var_id], ['%d']);
			
			$diff = [
				'Destrucción de Estructura' => ['old' => 'Columna Activa (' . $old_field['field_name'] . ')', 'new' => 'Columna SQL Eliminada Permanentemente']
			];
			$log_payload = array('user' => $user_snapshot, 'base_slug' => $base_info->form_slug, 'base_name' => $base_info->form_name, 'diff' => $diff);
			
			$wpdb->insert( $table_audit, array('form_id' => $base_id, 'action_type' => 'delete_col', 'changes_json' => wp_json_encode($log_payload), 'user_id' => $current_user_id, 'created_at' => $current_time) );
			
			wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_name . '-builder&tab=variables&base_id=' . $base_id . '&msg=var_deleted' ) );
			exit;
		}

		// GUARDAR APARIENCIA
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