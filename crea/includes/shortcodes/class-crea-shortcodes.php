<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/includes/shortcodes/class-crea-shortcodes.php
 */
if ( ! defined( 'WPINC' ) ) { die; }

class CREA_Shortcodes {

	public function __construct() {
		// Registramos los shortcodes dinámicos durante la inicialización de WordPress
		add_action( 'init', array( $this, 'register_dynamic_shortcodes' ) );
	}

	public function register_dynamic_shortcodes() {
		global $wpdb;
		$table_forms = $wpdb->prefix . 'crea_forms';
		
		// Prevención de errores si el plugin se acaba de instalar y la tabla aún no existe
		if ( $wpdb->get_var("SHOW TABLES LIKE '$table_forms'") !== $table_forms ) {
			return;
		}

		// Obtenemos todos los IDs de las bases creadas
		$base_ids = $wpdb->get_col("SELECT id FROM $table_forms");
		
		if ( !empty($base_ids) ) {
			foreach ( $base_ids as $id ) {
				// Registramos los 4 roles para cada ID
				add_shortcode( "crea_table_a_{$id}", array( $this, 'render_placeholder' ) );
				add_shortcode( "crea_table_er_{$id}", array( $this, 'render_placeholder' ) );
				add_shortcode( "crea_table_ar_{$id}", array( $this, 'render_form_add' ) ); // ☀️ Capturista (Llenado)
				add_shortcode( "crea_table_vr_{$id}", array( $this, 'render_placeholder' ) );
			}
		}
	}

	public function render_placeholder($atts, $content = null, $tag = '') {
		return '<div style="padding: 20px; border: 1px dashed #cbd5e1; text-align: center; color: #64748b; background: #f8fafc; border-radius: 6px;">Módulo en construcción: <strong>[' . esc_html($tag) . ']</strong></div>';
	}

	public function render_form_add( $atts, $content = null, $tag = '' ) {
		// Extraer el ID numérico de la etiqueta (Ej. crea_table_ar_19 -> 19)
		$base_id = intval( str_replace( 'crea_table_ar_', '', $tag ) );

		if ( $base_id <= 0 ) {
			return '<p style="color:var(--crea-danger);">Error: Identificador de base de datos inválido.</p>';
		}

		global $wpdb;
		$table_forms = $wpdb->prefix . 'crea_forms';
		$table_fields = $wpdb->prefix . 'crea_fields';

		// 1. Obtener Metadatos de la Base
		$form = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_forms WHERE id = %d", $base_id ), ARRAY_A );
		
		if ( ! $form ) {
			return '<p style="color:var(--crea-danger);">Error: No se encontró la base de datos en el sistema.</p>';
		}

		// 2. Extraer Variables (Ordenadas por "field_order" y omitiendo los "is_system" como los IDs)
		$fields = $wpdb->get_results( $wpdb->prepare( 
			"SELECT * FROM $table_fields WHERE form_id = %d AND is_system = 0 ORDER BY field_order ASC, id ASC", 
			$base_id 
		), ARRAY_A );

		if ( empty( $fields ) ) {
			return '<div style="padding: 20px; border: 1px dashed #cbd5e1; text-align: center; color: #64748b; background: #f8fafc; border-radius: 6px;">Esta base de datos aún no tiene variables configuradas para captura.</div>';
		}

		// 3. Cargar la vista HTML
		ob_start();
		include plugin_dir_path( __FILE__ ) . 'views/view-form.php';
		return ob_get_clean();
	}
}