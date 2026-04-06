<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/includes/class-crea-db.php
 */
if ( ! defined( 'WPINC' ) ) { die; }

class CREA_DB {
	public function create_master_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Tabla de formularios
		$table_forms = $wpdb->prefix . 'crea_forms';
		$sql_forms = "CREATE TABLE $table_forms (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			form_name varchar(100) NOT NULL,
			form_slug varchar(100) NOT NULL,
			data_year varchar(4) DEFAULT '',
			cut_date date DEFAULT NULL,
			data_source text DEFAULT '',
			description text DEFAULT '',
			created_by bigint(20) unsigned DEFAULT 0,
			updated_by bigint(20) unsigned DEFAULT 0,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY form_slug (form_slug)
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

		// NUEVA TABLA: Historial de Auditoría
		$table_audit = $wpdb->prefix . 'crea_audit_log';
		$sql_audit = "CREATE TABLE $table_audit (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			form_id mediumint(9) NOT NULL,
			action_type varchar(50) NOT NULL,
			changes_json longtext DEFAULT '',
			user_id bigint(20) unsigned DEFAULT 0,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql_forms );
		dbDelta( $sql_fields );
		dbDelta( $sql_audit );
	}
}