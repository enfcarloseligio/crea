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
                add_shortcode( "crea_table_er_{$id}", array( $this, 'render_table_editor' ) );
                add_shortcode( "crea_table_ar_{$id}", array( $this, 'render_form_add' ) ); 
                add_shortcode( "crea_table_vr_{$id}", array( $this, 'render_table_view' ) );
            }
        }
    }

    /**
     * Procesa los datos del formulario, calcula identificadores estadísticos y guarda el registro.
     */
    public function process_form_submission() {
        if ( isset($_POST['crea_action']) && in_array($_POST['crea_action'], array('save_new_record', 'edit_existing_record')) ) {
            $base_id = intval($_POST['base_id']);
            $is_edit = ($_POST['crea_action'] === 'edit_existing_record');
            $record_id = $is_edit ? intval($_POST['record_id']) : 0;
            
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
                
                if ( is_array($val) ) {
                    $val = implode(', ', array_map('sanitize_text_field', $val));
                } elseif ( $field['field_type'] === 'text_html' ) {
                    // Sanitización controlada para preservar estilos inline e incrustaciones
                    $val = $this->sanitize_rich_html($val);
                } else {
                    $val = sanitize_textarea_field($val);
                }
                
                $insert_data[$slug] = $val;

                // Procesamiento de codificación estadística para variables categóricas
                $config = json_decode($field['config'], true) ?: array();
                
                if ( in_array($field['field_type'], array('select', 'radio', 'checkbox')) && isset($config['id_type']) && in_array($config['id_type'], array('auto', 'manual')) ) {
                    
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
            
            $insert_data['updated_at'] = $current_time;
            $insert_data['updated_by'] = $current_user_id ?: null;

            // Inserción o Actualización en la estructura física
            if ( $is_edit ) {
                $processed = $wpdb->update( $physical_table, $insert_data, array('id' => $record_id) );
                $msg_param = 'edit_success';
            } else {
                $insert_data['created_at'] = $current_time;
                $insert_data['created_by'] = $current_user_id ?: null;
                $processed = $wpdb->insert( $physical_table, $insert_data );
                $msg_param = 'success';
            }

            if ( $processed !== false ) {
                $redirect_url = remove_query_arg( array('crea_msg') );
                $redirect_url = add_query_arg( 'crea_msg', $msg_param, $redirect_url );
                wp_safe_redirect( $redirect_url );
                exit;
            } else {
                wp_die('Fallo en la inserción/actualización de datos: ' . $wpdb->last_error);
            }
        }
    }

    /**
     * Sanitiza código HTML preservando atributos de diseño, colores inline y elementos de video.
     */
    private function sanitize_rich_html( $html ) {
        // ☀️ Eliminación de etiquetas peligrosas antes del procesamiento
        $html = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $html );
        $html = preg_replace( '/on[a-z]+\s*=\s*(["\']).*?\1/is', '', $html );
        $html = preg_replace( '/javascript\s*:/is', '', $html );

        // ☀️ Preservación de atributos de estilo mediante codificación temporal controlada
        $html = preg_replace_callback( '/\bstyle=(["\'])(.*?)\1/is', function( $matches ) {
            $cleaned_style = preg_replace( '/[<>\(\)\;]/', '', $matches[2] );
            // Se restaura el formato rgb/rgba de forma segura
            $safe_style = preg_replace_callback( '/rgb\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)/i', function($m) {
                return sprintf( 'rgb(%d,%d,%d)', min(255, $m[1]), min(255, $m[2]), min(255, $m[3]) );
            }, $matches[2] );
            return 'style="' . esc_attr( $safe_style ) . '"';
        }, $html );

        $allowed = wp_kses_allowed_html( 'post' );

        $common_attributes = array(
            'style' => true,
            'class' => true,
            'id'    => true,
            'title' => true,
        );

        $extended_tags = array(
            'span', 'p', 'div', 'mark', 'font', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 
            'strong', 'b', 'em', 'i', 'u', 's', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 
            'ul', 'ol', 'li', 'blockquote', 'code', 'pre', 'br'
        );

        foreach ( $extended_tags as $tag ) {
            if ( ! isset( $allowed[$tag] ) ) {
                $allowed[$tag] = array();
            }
            $allowed[$tag] = array_merge( $allowed[$tag], $common_attributes );
        }

        $allowed['iframe'] = array(
            'src'             => true,
            'width'           => true,
            'height'          => true,
            'frameborder'     => true,
            'allow'           => true,
            'allowfullscreen' => true,
            'loading'         => true,
            'title'           => true,
            'style'           => true,
            'class'           => true,
            'id'              => true,
        );

        $allowed['video'] = array(
            'src'      => true,
            'width'    => true,
            'height'   => true,
            'controls' => true,
            'autoplay' => true,
            'muted'    => true,
            'loop'     => true,
            'poster'   => true,
            'style'    => true,
            'class'    => true,
        );

        $allowed['source'] = array(
            'src'  => true,
            'type' => true,
        );

        // ☀️ Desactivación de safecss durante la ejecución de wp_kses para conservar declaraciones de color
        add_filter( 'safecss_filter_attr_allow_css', '__return_true' );
        $clean_html = wp_kses( $html, $allowed );
        remove_filter( 'safecss_filter_attr_allow_css', '__return_true' );

        return $clean_html;
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
     * Renderiza la tabla de visualización de datos (Perfil Analista).
     */
    public function render_table_view( $atts, $content = null, $tag = '' ) {
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

        if ($wpdb->get_var("SHOW TABLES LIKE '$physical_table'") !== $physical_table) {
            return '<p style="color:var(--crea-danger);">Error: Estructura física de datos no localizada en el servidor.</p>';
        }

        $fields = $wpdb->get_results( $wpdb->prepare( 
            "SELECT * FROM $table_fields WHERE form_id = %d AND is_system = 0 ORDER BY field_order ASC, id ASC", 
            $base_id 
        ), ARRAY_A );

        $records = $wpdb->get_results( "SELECT * FROM $physical_table ORDER BY created_at DESC", ARRAY_A );

        ob_start();
        include plugin_dir_path( __FILE__ ) . 'views/view-table.php';
        return ob_get_clean();
    }

    /**
     * Renderiza la tabla con permisos de edición (Perfil Editor).
     */
    public function render_table_editor( $atts, $content = null, $tag = '' ) {
        $base_id = intval( str_replace( 'crea_table_er_', '', $tag ) );

        if ( $base_id <= 0 ) return '<p style="color:var(--crea-danger);">Error: Identificador de base de datos no reconocido.</p>';

        global $wpdb;
        $table_forms = $wpdb->prefix . 'crea_forms';
        $table_fields = $wpdb->prefix . 'crea_fields';

        $form = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_forms WHERE id = %d", $base_id ), ARRAY_A );
        if ( ! $form ) return '<p style="color:var(--crea-danger);">Error: Estructura de base de datos inexistente.</p>';

        $physical_table = $wpdb->prefix . "crea_data_" . $form['form_slug'];

        if ($wpdb->get_var("SHOW TABLES LIKE '$physical_table'") !== $physical_table) {
            return '<p style="color:var(--crea-danger);">Error: Estructura física de datos no localizada en el servidor.</p>';
        }

        $fields = $wpdb->get_results( $wpdb->prepare( 
            "SELECT * FROM $table_fields WHERE form_id = %d AND is_system = 0 ORDER BY field_order ASC, id ASC", 
            $base_id 
        ), ARRAY_A );

        $records = $wpdb->get_results( "SELECT * FROM $physical_table ORDER BY created_at DESC", ARRAY_A );

        ob_start();
        include plugin_dir_path( __FILE__ ) . 'views/view-table-editor.php';
        return ob_get_clean();
    }
}