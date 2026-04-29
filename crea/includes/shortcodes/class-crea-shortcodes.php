<?php
/**
 * Archivo: wp-content/plugins/crea/includes/shortcodes/class-crea-shortcodes.php
 * Descripción: Controlador para la gestión y renderizado de shortcodes en el frontend.
 */
if ( ! defined( 'WPINC' ) ) { die; }

class CREA_Shortcodes {

    public function __construct() {
        // Registro de shortcodes dinámicos por perfil de acceso durante la inicialización
        add_action( 'init', array( $this, 'register_dynamic_shortcodes' ) );
        
        // Interceptor de envíos POST para la captura y procesamiento de registros
        add_action( 'template_redirect', array( $this, 'process_form_submission' ) );
    }

    /**
     * Registra dinámicamente los shortcodes basándose en las bases de datos existentes.
     */
    public function register_dynamic_shortcodes() {
        global $wpdb;
        $table_forms = $wpdb->prefix . 'crea_forms';
        
        if ( $wpdb->get_var("SHOW TABLES LIKE '$table_forms'") !== $table_forms ) return;

        $base_ids = $wpdb->get_col("SELECT id FROM $table_forms");
        
        if ( !empty($base_ids) ) {
            foreach ( $base_ids as $id ) {
                add_shortcode( "crea_table_a_{$id}", array( $this, 'render_placeholder' ) );
                add_shortcode( "crea_table_er_{$id}", array( $this, 'render_placeholder' ) );
                add_shortcode( "crea_table_ar_{$id}", array( $this, 'render_form_add' ) ); 
                // ☀️ Enlazamos el perfil de Analista (vr) a su nueva función de visor
                add_shortcode( "crea_table_vr_{$id}", array( $this, 'render_table_view' ) );
            }
        }
    }

    /**
     * Procesa los datos del formulario, calcula identificadores estadísticos y guarda el registro.
     */
    public function process_form_submission() {
        if ( isset($_POST['crea_action']) && $_POST['crea_action'] === 'save_new_record' ) {
            $base_id = intval($_POST['base_id']);
            
            // Validación del token de seguridad (Nonce)
            if ( ! isset($_POST['crea_record_nonce']) || ! wp_verify_nonce($_POST['crea_record_nonce'], 'crea_submit_record_' . $base_id) ) {
                wp_die('Error de validación. La sesión ha expirado o la solicitud es inválida.');
            }

            global $wpdb;
            $table_forms = $wpdb->prefix . 'crea_forms';
            $table_fields = $wpdb->prefix . 'crea_fields';
            
            $form = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_forms WHERE id = %d", $base_id ), ARRAY_A );
            if ( ! $form ) wp_die('Referencia a base de datos no encontrada.');
            
            $physical_table = $wpdb->prefix . "crea_data_" . $form['form_slug'];
            
            // Extracción de la estructura de variables configuradas para captura
            $fields = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_fields WHERE form_id = %d AND is_system = 0", $base_id ), ARRAY_A );
            
            $insert_data = array();
            $crea_data = isset($_POST['crea_data']) ? (array) $_POST['crea_data'] : array();

            foreach ( $fields as $field ) {
                $slug = $field['field_slug'];
                $val = isset($crea_data[$slug]) ? $crea_data[$slug] : '';
                
                // Normalización de datos en formato array (múltiples selecciones)
                if ( is_array($val) ) {
                    $val = implode(', ', array_map('sanitize_text_field', $val));
                } else {
                    $val = sanitize_textarea_field($val);
                }
                
                $insert_data[$slug] = $val;

                // Procesamiento de codificación estadística para variables categóricas
                $config = json_decode($field['config'], true) ?: array();
                
                if ( in_array($field['field_type'], ['select', 'radio', 'checkbox']) && isset($config['id_type']) && in_array($config['id_type'], ['auto', 'manual']) ) {
                    
                    $options_array = isset($config['options']) ? array_map('trim', explode("\n", $config['options'])) : array();
                    
                    $selected_texts = is_array(isset($crea_data[$slug]) ? $crea_data[$slug] : '') ? $crea_data[$slug] : array($val);
                    if($val === '') $selected_texts = array();

                    $calculated_ids = array();

                    foreach($selected_texts as $sel_text) {
                        $index = array_search(trim($sel_text), $options_array);
                        if ( $index !== false ) {
                            if ( $config['id_type'] === 'auto' ) {
                                $calculated_ids[] = $index + 1;
                            } elseif ( $config['id_type'] === 'manual' ) {
                                $manual_codes = isset($config['manual_codes']) ? array_map('trim', explode("\n", $config['manual_codes'])) : array();
                                $calculated_ids[] = isset($manual_codes[$index]) ? $manual_codes[$index] : null;
                            }
                        }
                    }

                    // Asignación del valor codificado a la columna de sistema correspondiente
                    $insert_data['id_' . $slug] = implode(', ', $calculated_ids);
                }
            }

            // Asignación de metadatos de trazabilidad
            $current_time = gmdate('Y-m-d H:i:s');
            $current_user_id = get_current_user_id();
            
            $insert_data['created_at'] = $current_time;
            $insert_data['updated_at'] = $current_time;
            $insert_data['created_by'] = $current_user_id ?: null;
            $insert_data['updated_by'] = $current_user_id ?: null;

            // Inserción en la estructura física
            $inserted = $wpdb->insert( $physical_table, $insert_data );

            if ( $inserted ) {
                $redirect_url = remove_query_arg( array('crea_msg') );
                $redirect_url = add_query_arg( 'crea_msg', 'success', $redirect_url );
                wp_safe_redirect( $redirect_url );
                exit;
            } else {
                wp_die('Fallo en la inserción de datos: ' . $wpdb->last_error);
            }
        }
    }

    /**
     * Renderiza un contenedor temporal para módulos en desarrollo.
     */
    public function render_placeholder($atts, $content = null, $tag = '') {
        return '<div style="padding: 20px; border: 1px dashed #cbd5e1; text-align: center; color: #64748b; background: #f8fafc; border-radius: 6px;">Módulo pendiente de implementación: <strong>[' . esc_html($tag) . ']</strong></div>';
    }

    /**
     * Renderiza la interfaz de captura de registros (Perfil Capturista).
     */
    public function render_form_add( $atts, $content = null, $tag = '' ) {
        $base_id = intval( str_replace( 'crea_table_ar_', '', $tag ) );

        if ( $base_id <= 0 ) {
            return '<p style="color:var(--crea-danger);">Error: Identificador de base de datos no reconocido.</p>';
        }

        global $wpdb;
        $table_forms = $wpdb->prefix . 'crea_forms';
        $table_fields = $wpdb->prefix . 'crea_fields';

        $form = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_forms WHERE id = %d", $base_id ), ARRAY_A );
        if ( ! $form ) {
            return '<p style="color:var(--crea-danger);">Error: Estructura de base de datos inexistente.</p>';
        }

        $fields = $wpdb->get_results( $wpdb->prepare( 
            "SELECT * FROM $table_fields WHERE form_id = %d AND is_system = 0 ORDER BY field_order ASC, id ASC", 
            $base_id 
        ), ARRAY_A );

        if ( empty( $fields ) ) {
            return '<div style="padding: 20px; border: 1px dashed #cbd5e1; text-align: center; color: #64748b; background: #f8fafc; border-radius: 6px;">El diccionario de datos no contiene variables configuradas para captura.</div>';
        }

        ob_start();
        include plugin_dir_path( __FILE__ ) . 'views/view-form.php';
        return ob_get_clean();
    }

    /**
     * ☀️ NUEVO: Renderiza la tabla de visualización de datos (Perfil Analista).
     */
    public function render_table_view( $atts, $content = null, $tag = '' ) {
        // Extrae el ID del shortcode (Ej. crea_table_vr_16 -> 16)
        $base_id = intval( str_replace( 'crea_table_vr_', '', $tag ) );

        if ( $base_id <= 0 ) {
            return '<p style="color:var(--crea-danger);">Error: Identificador de base de datos no reconocido.</p>';
        }

        global $wpdb;
        $table_forms = $wpdb->prefix . 'crea_forms';
        $table_fields = $wpdb->prefix . 'crea_fields';

        $form = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_forms WHERE id = %d", $base_id ), ARRAY_A );
        if ( ! $form ) {
            return '<p style="color:var(--crea-danger);">Error: Estructura de base de datos inexistente.</p>';
        }

        $physical_table = $wpdb->prefix . "crea_data_" . $form['form_slug'];

        // Validación de integridad de tabla física
        if ($wpdb->get_var("SHOW TABLES LIKE '$physical_table'") !== $physical_table) {
            return '<p style="color:var(--crea-danger);">Error: Estructura física de datos no localizada en el servidor.</p>';
        }

        // Obtener la estructura de columnas (excluyendo IDs de sistema para visualización limpia)
        $fields = $wpdb->get_results( $wpdb->prepare( 
            "SELECT * FROM $table_fields WHERE form_id = %d AND is_system = 0 ORDER BY field_order ASC, id ASC", 
            $base_id 
        ), ARRAY_A );

        // Obtener registros ordenados por creación reciente
        $records = $wpdb->get_results( "SELECT * FROM $physical_table ORDER BY created_at DESC", ARRAY_A );

        ob_start();
        include plugin_dir_path( __FILE__ ) . 'views/view-table.php';
        return ob_get_clean();
    }
}