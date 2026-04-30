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
        add_action( 'wp_ajax_crea_reorder_vars', array( $this, 'ajax_reorder_vars' ) );
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

    public function ajax_reorder_vars() {
        check_ajax_referer( 'crea_ajax_nonce', 'security' );
        if (!current_user_can('manage_options')) wp_send_json_error('Permisos insuficientes.');
        
        global $wpdb;
        $table_fields = $wpdb->prefix . 'crea_fields';
        $order = isset($_POST['order']) ? $_POST['order'] : [];
        $base_id = isset($_POST['base_id']) ? intval($_POST['base_id']) : 0;
        
        if (empty($order) || $base_id === 0) wp_send_json_error('Datos inválidos.');
        
        $pos = 10; 
        foreach ($order as $var_id) {
            $var_id = intval($var_id);
            $wpdb->update($table_fields, ['field_order' => $pos], ['id' => $var_id]);
            $pos += 10;
        }
        
        wp_send_json_success('Orden actualizado correctamente.');
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
            is_system tinyint(1) DEFAULT 0,
            parent_slug varchar(255) DEFAULT NULL,
            field_order int(11) DEFAULT 0,
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

        // ☀️ NUEVO: Diccionario de etiquetas actualizado para Auditoría
        $config_labels = [
            'max_length' => 'Caracteres Máximos', 'digits' => 'Dígitos Enteros',
            'integers' => 'Dígitos Enteros', 'decimals' => 'Decimales',
            'time_zone' => 'Zona Horaria', 'time_format' => 'Formato de Visualización (Hora)',
            'options' => 'Opciones de Catálogo', 'default' => 'Selección por Defecto', 
            'id_type' => 'Tipo de Codificación', 'manual_codes' => 'Códigos Manuales', 
            'rel_base' => 'Base Maestra', 'rel_field' => 'Variable a Extraer', 
            'rel_output_format' => 'Formato de Salida (Visualización)',
            'rel_cond_field' => 'Campo Condicional', 'rel_cond_value' => 'Valor Condicional',
            'is_filterable' => 'Filtro Analítico Habilitado'
        ];

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
            
            // ☀️ NUEVO: Atrapamos el Filtro al crear
            if (isset($_POST['is_filterable'])) {
                $config['is_filterable'] = intval($_POST['is_filterable']);
            }
            
            $diff = [
                'Nombre Variable' => ['old' => 'N/A', 'new' => $field_name],
                'Slug SQL (Columna)' => ['old' => 'N/A', 'new' => $field_slug],
                'Tipo de Dato' => ['old' => 'N/A', 'new' => isset($human_types[$field_type]) ? $human_types[$field_type] : $field_type],
                'Dato Obligatorio' => ['old' => 'N/A', 'new' => $is_required ? 'Sí' : 'No']
            ];

            if (in_array($field_type, ['text_short', 'text_long'])) {
                $config['max_length'] = intval($_POST['text_max_length']);
            } elseif ($field_type === 'num_discrete') {
                $config['digits'] = intval($_POST['num_disc_digits']);
            } elseif ($field_type === 'num_continuous') {
                $config['integers'] = intval($_POST['num_cont_integers']);
                $config['decimals'] = intval($_POST['num_cont_decimals']);
            } elseif ($field_type === 'time') {
                $config['time_zone'] = sanitize_text_field($_POST['time_zone_default']);
                $config['time_format'] = sanitize_text_field($_POST['time_format']); // ☀️ NUEVO
            } elseif (in_array($field_type, ['select', 'radio', 'checkbox'])) {
                $config['options'] = sanitize_textarea_field($_POST['categorical_options']);
                $config['default'] = isset($_POST['categorical_default']) ? array_map('sanitize_text_field', $_POST['categorical_default']) : [];
                $config['id_type'] = sanitize_text_field($_POST['categorical_id_type']);
                if ($config['id_type'] === 'manual') {
                    $config['manual_codes'] = sanitize_textarea_field($_POST['categorical_manual_codes']);
                }
            } elseif ($field_type === 'relation') {
                $config['rel_base'] = sanitize_text_field($_POST['rel_base_slug']);
                $config['rel_field'] = sanitize_text_field($_POST['rel_field_slug']);
                $config['rel_output_format'] = sanitize_text_field($_POST['rel_output_format']); // ☀️ NUEVO
                $config['rel_cond_field'] = sanitize_text_field($_POST['rel_cond_field']);
                $config['rel_cond_value'] = sanitize_text_field($_POST['rel_cond_value']);
            }

            foreach($config as $k => $v) {
                $lbl = isset($config_labels[$k]) ? $config_labels[$k] : ucwords(str_replace('_', ' ', $k));
                if (is_array($v)) $v = empty($v) ? 'Ninguna' : implode(", ", $v);
                $v = str_replace("\n", ", ", (string)$v);
                
                // Formateos especiales para auditoría legible
                if ($k === 'is_filterable') $v = ($v == 1) ? 'Sí (Habilitado)' : 'No (Deshabilitado)';
                
                $diff[$lbl] = ['old' => 'N/A', 'new' => ($v === '') ? 'Vacío' : $v];
            }

            $max_order = $wpdb->get_var($wpdb->prepare("SELECT MAX(field_order) FROM $table_fields WHERE form_id = %d", $base_id));
            $next_order = intval($max_order) + 10;

            $has_twin = false;
            if (in_array($field_type, ['select', 'radio', 'checkbox']) && isset($config['id_type']) && in_array($config['id_type'], ['auto', 'manual'])) {
                $has_twin = true;
            }

            $inserted_field = $wpdb->insert($table_fields, [
                'form_id' => $base_id, 'field_name' => $field_name, 'field_slug' => $field_slug,
                'field_type' => $field_type, 'is_required' => $is_required, 'config' => wp_json_encode($config),
                'is_system' => 0, 'field_order' => $next_order, 'created_by' => $current_user_id,
                'updated_by' => $current_user_id, 'created_at' => $current_time, 'updated_at' => $current_time
            ]);

            if ( false === $inserted_field ) wp_die( 'Error crítico al guardar la variable: ' . $wpdb->last_error );

            if ($has_twin) {
                $twin_type = ($config['id_type'] === 'auto') ? 'num_discrete' : 'text_short';
                $twin_conf = ($config['id_type'] === 'auto') ? wp_json_encode(['digits'=>20]) : wp_json_encode(['max_length'=>255]);
                $wpdb->insert($table_fields, [
                    'form_id' => $base_id, 'field_name' => 'ID ' . $field_name, 'field_slug' => 'id_' . $field_slug,
                    'field_type' => $twin_type, 'is_required' => 0, 'config' => $twin_conf, 'is_system' => 1,
                    'parent_slug' => $field_slug, 'field_order' => $next_order + 1, 'created_by' => $current_user_id,
                    'updated_by' => $current_user_id, 'created_at' => $current_time, 'updated_at' => $current_time
                ]);
                $diff['Columna de Sistema (ID)'] = ['old' => 'N/A', 'new' => 'Generada automáticamente (id_'.$field_slug.')'];
            }

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
            } 
            elseif ($field_type === 'num_discrete') { 
                $dig = (isset($config['digits']) && $config['digits'] > 0) ? intval($config['digits']) : 20;
                $sql_type = "DECIMAL($dig, 0)"; 
            } 
            elseif ($field_type === 'num_continuous') { 
                $intg = (isset($config['integers']) && $config['integers'] > 0) ? intval($config['integers']) : 20;
                $deci = isset($config['decimals']) ? intval($config['decimals']) : 8;
                $tot = $intg + $deci;
                $sql_type = "DECIMAL($tot, $deci)"; 
            } 
            elseif ($field_type === 'date') { $sql_type = "DATE"; } 
            elseif ($field_type === 'time') { $sql_type = "TIME"; }

            $col_check = $wpdb->get_results("SHOW COLUMNS FROM $physical_table LIKE '$field_slug'");
            if (empty($col_check)) {
                $wpdb->query("ALTER TABLE $physical_table ADD COLUMN $field_slug $sql_type");
                if ($has_twin) {
                    $twin_sql_type = ($config['id_type'] === 'auto') ? 'DECIMAL(20,0)' : 'VARCHAR(255)';
                    $wpdb->query("ALTER TABLE $physical_table ADD COLUMN id_$field_slug $twin_sql_type");
                }
            }

            $log_payload = array('user' => $user_snapshot, 'base_slug' => $base_info->form_slug, 'base_name' => $base_info->form_name, 'diff' => $diff);
            $wpdb->insert( $table_audit, array('form_id' => $base_id, 'action_type' => 'add_col', 'changes_json' => wp_json_encode($log_payload), 'user_id' => $current_user_id, 'created_at' => $current_time) );

            wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_name . '-builder&tab=variables&base_id=' . $base_id . '&msg=var_created' ) );
            exit;
        }

        if ( isset( $_POST['edit_variable_advanced'] ) && isset( $_POST['crea_edit_var_nonce'] ) ) {
            if ( ! wp_verify_nonce( $_POST['crea_edit_var_nonce'], 'crea_edit_var_action' ) ) wp_die( 'Error de seguridad.' );
            
            $var_id = intval($_POST['edit_var_id']);
            $old_field = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_fields WHERE id = %d", $var_id), ARRAY_A);
            if (!$old_field || $old_field['is_system'] == 1) wp_die('Variable inválida o protegida por el sistema.');
            
            $base_id = $old_field['form_id'];
            $base_info = $wpdb->get_row($wpdb->prepare("SELECT form_name, form_slug FROM $table_forms WHERE id = %d", $base_id));
            
            $physical_table = $wpdb->prefix . "crea_data_" . $base_info->form_slug;
            $count_rows = 0;
            if ($wpdb->get_var("SHOW TABLES LIKE '$physical_table'") === $physical_table) {
                $count_rows = intval($wpdb->get_var("SELECT COUNT(*) FROM $physical_table"));
            }

            if ($count_rows > 0) {
                $new_slug = $old_field['field_slug'];
                $new_type = $old_field['field_type'];
            } else {
                $new_slug = str_replace('-', '_', sanitize_title($_POST['edit_field_slug']));
                $new_type = sanitize_text_field($_POST['edit_field_type']);
            }

            $new_name = sanitize_text_field($_POST['edit_field_name']);
            $new_req = isset($_POST['edit_is_required']) ? 1 : 0;

            $config = [];
            
            // ☀️ NUEVO: Atrapamos el Filtro en edición
            if (isset($_POST['edit_is_filterable'])) {
                $config['is_filterable'] = intval($_POST['edit_is_filterable']);
            }

            if (in_array($new_type, ['text_short', 'text_long'])) {
                $config['max_length'] = intval($_POST['edit_text_max_length']);
            } elseif ($new_type === 'num_discrete') {
                $config['digits'] = intval($_POST['edit_num_disc_digits']);
            } elseif ($new_type === 'num_continuous') {
                $config['integers'] = intval($_POST['edit_num_cont_integers']);
                $config['decimals'] = intval($_POST['edit_num_cont_decimals']);
            } elseif ($new_type === 'time') {
                $config['time_zone'] = sanitize_text_field($_POST['edit_time_zone_default']);
                $config['time_format'] = sanitize_text_field($_POST['edit_time_format']); // ☀️ NUEVO
            } elseif (in_array($new_type, ['select', 'radio', 'checkbox'])) {
                $config['options'] = sanitize_textarea_field($_POST['edit_categorical_options']);
                $config['default'] = isset($_POST['edit_categorical_default']) ? array_map('sanitize_text_field', $_POST['edit_categorical_default']) : [];
                $config['id_type'] = sanitize_text_field($_POST['edit_categorical_id_type']);
                if ($config['id_type'] === 'manual') $config['manual_codes'] = sanitize_textarea_field($_POST['edit_categorical_manual_codes']);
            } elseif ($new_type === 'relation') {
                $config['rel_base'] = sanitize_text_field($_POST['edit_rel_base_slug']);
                $config['rel_field'] = sanitize_text_field($_POST['edit_rel_field_slug']);
                $config['rel_output_format'] = sanitize_text_field($_POST['edit_rel_output_format']); // ☀️ NUEVO
                $config['rel_cond_field'] = sanitize_text_field($_POST['edit_rel_cond_field']);
                $config['rel_cond_value'] = sanitize_text_field($_POST['edit_rel_cond_value']);
            }

            $new_config_json = wp_json_encode($config);
            $human_types = ['text_short'=>'Texto Corto', 'text_long'=>'Texto Largo', 'text_html'=>'Editor HTML', 'num_discrete'=>'Numérico Discreto', 'num_continuous'=>'Numérico Continuo', 'date'=>'Fecha', 'time'=>'Hora', 'select'=>'Menú Desplegable', 'radio'=>'Botones de Radio', 'checkbox'=>'Casillas Múltiples', 'relation'=>'Base Relacional'];

            $diff = [];
            if ($old_field['field_name'] !== $new_name) $diff['Nombre Variable'] = ['old' => $old_field['field_name'], 'new' => $new_name];
            if ($old_field['field_slug'] !== $new_slug) $diff['Slug SQL'] = ['old' => $old_field['field_slug'], 'new' => $new_slug];
            if ($old_field['field_type'] !== $new_type) {
                $old_t = isset($human_types[$old_field['field_type']]) ? $human_types[$old_field['field_type']] : $old_field['field_type'];
                $new_t = isset($human_types[$new_type]) ? $human_types[$new_type] : $new_type;
                $diff['Tipo de Dato'] = ['old' => $old_t, 'new' => $new_t];
            }
            if ($old_field['is_required'] != $new_req) $diff['Dato Obligatorio'] = ['old' => $old_field['is_required'] ? 'Sí' : 'No', 'new' => $new_req ? 'Sí' : 'No'];
            
            $old_conf_arr = json_decode($old_field['config'], true) ?: [];
            $all_keys = array_unique(array_merge(array_keys($old_conf_arr), array_keys($config)));
            
            foreach($all_keys as $k) {
                $v_old = isset($old_conf_arr[$k]) ? $old_conf_arr[$k] : '';
                $v_new = isset($config[$k]) ? $config[$k] : '';
                
                if (is_array($v_old)) $v_old = empty($v_old) ? 'Ninguna' : implode(", ", $v_old);
                if (is_array($v_new)) $v_new = empty($v_new) ? 'Ninguna' : implode(", ", $v_new);
                
                $v_old = str_replace("\n", ", ", (string)$v_old);
                $v_new = str_replace("\n", ", ", (string)$v_new);
                
                if ($v_old !== $v_new) {
                    $lbl = isset($config_labels[$k]) ? $config_labels[$k] : ucwords(str_replace('_', ' ', $k));
                    
                    if ($k === 'is_filterable') {
                        $v_old = ($v_old == 1) ? 'Sí (Habilitado)' : 'No (Deshabilitado)';
                        $v_new = ($v_new == 1) ? 'Sí (Habilitado)' : 'No (Deshabilitado)';
                    }
                    
                    $diff[$lbl] = ['old' => ($v_old === '') ? 'Vacío' : $v_old, 'new' => ($v_new === '') ? 'Vacío' : $v_new];
                }
            }

            if (!empty($diff)) {
                if ($count_rows == 0) {
                    $old_id_type = isset($old_conf_arr['id_type']) ? $old_conf_arr['id_type'] : 'none';
                    $new_id_type = isset($config['id_type']) ? $config['id_type'] : 'none';

                    $sql_type = "TEXT";
                    if (in_array($new_type, ['text_short', 'select', 'radio', 'relation'])) { 
                        $sql_type = "VARCHAR(" . (isset($config['max_length']) ? $config['max_length'] : 255) . ")"; 
                    } 
                    elseif ($new_type === 'num_discrete') { 
                        $dig = (isset($config['digits']) && $config['digits'] > 0) ? intval($config['digits']) : 20;
                        $sql_type = "DECIMAL($dig, 0)"; 
                    } 
                    elseif ($new_type === 'num_continuous') { 
                        $intg = (isset($config['integers']) && $config['integers'] > 0) ? intval($config['integers']) : 20;
                        $deci = isset($config['decimals']) ? intval($config['decimals']) : 8;
                        $tot = $intg + $deci;
                        $sql_type = "DECIMAL($tot, $deci)"; 
                    } 
                    elseif ($new_type === 'date') { $sql_type = "DATE"; } 
                    elseif ($new_type === 'time') { $sql_type = "TIME"; }

                    if ($old_field['field_slug'] !== $new_slug || $old_field['field_type'] !== $new_type || $old_field['config'] !== $new_config_json) {
                        $wpdb->query("ALTER TABLE $physical_table CHANGE COLUMN {$old_field['field_slug']} $new_slug $sql_type");
                    }

                    $twin_slug_old = 'id_' . $old_field['field_slug'];
                    $twin_slug_new = 'id_' . $new_slug;
                    $twin_name_new = 'ID ' . $new_name;

                    if ($new_id_type === 'none' && in_array($old_id_type, ['auto', 'manual'])) {
                        $wpdb->query("ALTER TABLE $physical_table DROP COLUMN $twin_slug_old");
                        $wpdb->delete($table_fields, ['parent_slug' => $old_field['field_slug'], 'form_id' => $base_id]);
                        $diff['Columna de Sistema (ID)'] = ['old' => 'Activa ('.$twin_slug_old.')', 'new' => 'Eliminada permanentemente por cambio de codificación'];
                    } 
                    elseif ($old_id_type === 'none' && in_array($new_id_type, ['auto', 'manual'])) {
                        $twin_sql_type = ($new_id_type === 'auto') ? 'DECIMAL(20,0)' : 'VARCHAR(255)';
                        $twin_type_str = ($new_id_type === 'auto') ? 'num_discrete' : 'text_short';
                        $twin_conf_str = ($new_id_type === 'auto') ? wp_json_encode(['digits'=>20]) : wp_json_encode(['max_length'=>255]);
                        
                        $wpdb->query("ALTER TABLE $physical_table ADD COLUMN $twin_slug_new $twin_sql_type");
                        $wpdb->insert($table_fields, [
                            'form_id' => $base_id, 'field_name' => $twin_name_new, 'field_slug' => $twin_slug_new,
                            'field_type' => $twin_type_str, 'config' => $twin_conf_str, 'is_system' => 1, 'parent_slug' => $new_slug,
                            'field_order' => $old_field['field_order'] + 1, 'created_by' => $current_user_id, 'updated_by' => $current_user_id,
                            'created_at' => $current_time, 'updated_at' => $current_time
                        ]);
                        $diff['Columna de Sistema (ID)'] = ['old' => 'N/A', 'new' => 'Generada automáticamente ('.$twin_slug_new.')'];
                    }
                    elseif (in_array($old_id_type, ['auto', 'manual']) && in_array($new_id_type, ['auto', 'manual'])) {
                        $twin_sql_type = ($new_id_type === 'auto') ? 'DECIMAL(20,0)' : 'VARCHAR(255)';
                        $twin_type_str = ($new_id_type === 'auto') ? 'num_discrete' : 'text_short';
                        $twin_conf_str = ($new_id_type === 'auto') ? wp_json_encode(['digits'=>20]) : wp_json_encode(['max_length'=>255]);
                        
                        $wpdb->query("ALTER TABLE $physical_table CHANGE COLUMN $twin_slug_old $twin_slug_new $twin_sql_type");
                        $wpdb->update($table_fields, [
                            'field_name' => $twin_name_new, 'field_slug' => $twin_slug_new, 'field_type' => $twin_type_str,
                            'config' => $twin_conf_str, 'parent_slug' => $new_slug
                        ], ['parent_slug' => $old_field['field_slug'], 'form_id' => $base_id]);
                    }
                }

                $wpdb->update($table_fields, [
                    'field_name' => $new_name, 'field_slug' => $new_slug, 'field_type' => $new_type,
                    'is_required' => $new_req, 'config' => $new_config_json,
                    'updated_by' => $current_user_id, 'updated_at' => $current_time
                ], ['id' => $var_id]);
                
                $log_payload = array('user' => $user_snapshot, 'base_slug' => $base_info->form_slug, 'base_name' => $base_info->form_name, 'diff' => $diff);
                $wpdb->insert( $table_audit, array('form_id' => $base_id, 'action_type' => 'edit_col', 'changes_json' => wp_json_encode($log_payload), 'user_id' => $current_user_id, 'created_at' => $current_time) );
            }
            
            wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_name . '-builder&tab=variables&base_id=' . $base_id . '&msg=var_updated' ) );
            exit;
        }

        if ( isset( $_POST['delete_variable'] ) && isset( $_POST['crea_delete_var_nonce'] ) ) {
            if ( ! wp_verify_nonce( $_POST['crea_delete_var_nonce'], 'crea_delete_var_action' ) ) wp_die( 'Error de seguridad.' );
            
            $var_id = intval($_POST['delete_var_id']);
            $base_id = intval($_POST['base_id']);
            
            $old_field = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_fields WHERE id = %d", $var_id), ARRAY_A);
            if (!$old_field || $old_field['is_system'] == 1) wp_die('Variable protegida o no encontrada.');
            
            $base_info = $wpdb->get_row($wpdb->prepare("SELECT form_name, form_slug FROM $table_forms WHERE id = %d", $base_id));
            $physical_table = $wpdb->prefix . "crea_data_" . $base_info->form_slug;
            $field_slug = $old_field['field_slug'];
            
            $diff = [ 'Destrucción de Estructura' => ['old' => 'Columna Activa (' . $old_field['field_name'] . ')', 'new' => 'Columna SQL Eliminada Permanentemente'] ];

            $col_check = $wpdb->get_results("SHOW COLUMNS FROM $physical_table LIKE '$field_slug'");
            if (!empty($col_check)) {
                $wpdb->query("ALTER TABLE $physical_table DROP COLUMN $field_slug");
                
                $col_id_check = $wpdb->get_results("SHOW COLUMNS FROM $physical_table LIKE 'id_$field_slug'");
                if (!empty($col_id_check)) {
                    $wpdb->query("ALTER TABLE $physical_table DROP COLUMN id_$field_slug");
                    $diff['Columna de Sistema (ID)'] = ['old' => 'Activa (id_'.$field_slug.')', 'new' => 'Eliminada permanentemente en conjunto con la principal'];
                }
            }
            
            $wpdb->delete($table_fields, ['id' => $var_id], ['%d']);
            $wpdb->delete($table_fields, ['parent_slug' => $field_slug, 'form_id' => $base_id]);
            
            $log_payload = array('user' => $user_snapshot, 'base_slug' => $base_info->form_slug, 'base_name' => $base_info->form_name, 'diff' => $diff);
            
            $wpdb->insert( $table_audit, array('form_id' => $base_id, 'action_type' => 'delete_col', 'changes_json' => wp_json_encode($log_payload), 'user_id' => $current_user_id, 'created_at' => $current_time) );
            
            wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_name . '-builder&tab=variables&base_id=' . $base_id . '&msg=var_deleted' ) );
            exit;
        }

        if ( isset( $_POST['crea_save_appearance'] ) && isset( $_POST['crea_save_appearance_nonce'] ) ) {
            if ( ! wp_verify_nonce( $_POST['crea_save_appearance_nonce'], 'crea_save_appearance_action' ) ) wp_die( 'Error de seguridad.' );
            $admin_colors = array(
                'th_bg' => sanitize_hex_color( $_POST['admin_th_bg'] ), 'th_text' => sanitize_hex_color( $_POST['admin_th_text'] ),
                'odd_bg' => sanitize_hex_color( $_POST['admin_odd_bg'] ), 'odd_text' => sanitize_hex_color( $_POST['admin_odd_text'] ),
                'even_bg' => sanitize_hex_color( $_POST['admin_even_bg'] ), 'even_text' => sanitize_hex_color( $_POST['admin_even_text'] ),
            );
            $front_colors = array(
                'primary'   => sanitize_hex_color( $_POST['front_primary'] ), 
                'th_bg'     => sanitize_hex_color( $_POST['front_th_bg'] ), 
                'th_text'   => sanitize_hex_color( $_POST['front_th_text'] ),
                'odd_bg'    => sanitize_hex_color( $_POST['front_odd_bg'] ), 
                'odd_text'  => sanitize_hex_color( $_POST['front_odd_text'] ),
                'even_bg'   => sanitize_hex_color( $_POST['front_even_bg'] ), 
                'even_text' => sanitize_hex_color( $_POST['front_even_text'] ),
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
        wp_enqueue_script( 'jquery-ui-sortable' );
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