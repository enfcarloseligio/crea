<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/includes/class-crea-db.php
 *
 * Creación de la infraestructura SQL necesaria.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class CREA_DB {

	/**
	 * Crea las tablas maestras de formularios y campos.
	 */
	public function create_master_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Tabla de formularios
		$table_forms = $wpdb->prefix . 'crea_forms';
		$sql_forms = "CREATE TABLE $table_forms (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			form_name varchar(100) NOT NULL,
			form_slug varchar(100) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// Tabla de campos
		$table_fields = $wpdb->prefix . 'crea_fields';
		$sql_fields = "CREATE TABLE $table_fields (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			form_id mediumint(9) NOT NULL,
			field_label varchar(100) NOT NULL,
			field_type varchar(50) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql_forms );
		dbDelta( $sql_fields );
	}
}