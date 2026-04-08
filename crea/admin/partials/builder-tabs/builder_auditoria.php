<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/admin/partials/builder-tabs/builder_auditoria.php
 *
 * Pestaña de Auditoría: Búsqueda dinámica y trazabilidad retrospectiva.
 */
if ( ! defined( 'WPINC' ) ) { die; }

global $wpdb;
$table_forms = $wpdb->prefix . 'crea_forms';
$table_audit = $wpdb->prefix . 'crea_audit_log';
$current_user_id = get_current_user_id();

// 1. Manejo de la Zona Horaria
$global_wp_tz = wp_timezone_string();
$saved_user_tz = get_user_meta( $current_user_id, 'crea_audit_timezone', true );

if ( isset( $_GET['crea_timezone_submitted'] ) ) {
	$selected_tz = sanitize_text_field( $_GET['crea_timezone'] );
	if ( isset( $_GET['save_timezone'] ) && $_GET['save_timezone'] === '1' ) {
		update_user_meta( $current_user_id, 'crea_audit_timezone', $selected_tz );
		$saved_user_tz = $selected_tz;
	} else {
		delete_user_meta( $current_user_id, 'crea_audit_timezone' );
		$saved_user_tz = '';
	}
	$display_tz_string = $selected_tz;
} else {
	$display_tz_string = !empty( $saved_user_tz ) ? $saved_user_tz : $global_wp_tz;
}

try {
	$display_tz_obj = new DateTimeZone( $display_tz_string );
} catch (Exception $e) {
	$display_tz_obj = wp_timezone();
	$display_tz_string = $display_tz_obj->getName();
}

// 2. Construir lista unificada de Bases (Activas + Eliminadas) usando el SLUG
$dropdown_options = [];

// A. Obtener bases activas
$active_bases = $wpdb->get_results("SELECT form_name, form_slug FROM $table_forms", ARRAY_A);
if($active_bases) {
	foreach($active_bases as $ab) {
		$dropdown_options[$ab['form_slug']] = array(
			'name'   => $ab['form_name'],
			'status' => 'Activa'
		);
	}
}

// B. Obtener bases eliminadas desde el registro JSON de la auditoría
$all_jsons = $wpdb->get_col("SELECT changes_json FROM $table_audit");
if($all_jsons) {
	foreach($all_jsons as $json_str) {
		$payload = json_decode($json_str, true);
		if ( isset($payload['base_slug']) && !isset($dropdown_options[$payload['base_slug']]) ) {
			$dropdown_options[$payload['base_slug']] = array(
				'name'   => isset($payload['base_name']) ? $payload['base_name'] : $payload['base_slug'],
				'status' => 'Eliminada'
			);
		}
	}
}
ksort($dropdown_options); // Ordenamos alfabéticamente por Slug

// 3. Filtrar registros por el Slug (Nombre Sistema) seleccionado
$selected_slug = isset( $_GET['base_slug'] ) ? sanitize_text_field( $_GET['base_slug'] ) : '';
$logs = [];

if ( !empty($selected_slug) ) {
	// Buscamos cualquier registro que contenga este slug en su payload
	$like_query = '%"base_slug":"' . $wpdb->esc_like($selected_slug) . '"%';
	
	// Como soporte legacy (para las bases creadas antes de esta actualización)
	$current_form_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_forms WHERE form_slug = %s", $selected_slug));
	
	if ($current_form_id) {
		$logs = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $table_audit WHERE changes_json LIKE %s OR form_id = %d ORDER BY created_at DESC", $like_query, $current_form_id), ARRAY_A );
	} else {
		$logs = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $table_audit WHERE changes_json LIKE %s ORDER BY created_at DESC", $like_query), ARRAY_A );
	}
}

$action_labels = [
	'create'      => '<span class="dashicons dashicons-database" style="color:var(--crea-primary);"></span> Creación Base',
	'update_meta' => '<span class="dashicons dashicons-edit" style="color:var(--crea-warning);"></span> Edición Metadatos',
	'delete'      => '<span class="dashicons dashicons-trash" style="color:var(--crea-danger);"></span> Eliminación',
	'add_col'     => '<span class="dashicons dashicons-plus-alt2" style="color:#16A34A;"></span> Nueva Variable',
	'edit_col'    => '<span class="dashicons dashicons-admin-generic" style="color:var(--crea-warning);"></span> Edición Variable',
];
?>

<div class="crea-card" style="border-top: 4px solid var(--crea-ink);">
	<div class="crea-card-header">
		<h2>Auditoría de Control de Cambios</h2>
	</div>
	
	<form method="get" action="admin.php" style="margin-bottom: 25px; padding: 15px; background: #F8FAFC; border-radius: 8px; border: 1px solid #E2E8F0;">
		<input type="hidden" name="page" value="crea-builder">
		<input type="hidden" name="tab" value="auditoria">
		<input type="hidden" name="crea_timezone_submitted" value="1">
		
		<div style="display: flex; gap: 30px; flex-wrap: wrap;">
			<div style="flex: 1; min-width: 250px;">
				<label for="base_slug" style="font-weight: 600; display: block; margin-bottom: 8px;">1. Selecciona la base de datos:</label>
				<select name="base_slug" id="base_slug" class="crea-searchable-select">
					<option value="">-- Escribe o selecciona una base --</option>
					<?php foreach ( $dropdown_options as $slug => $data ) : ?>
						<option value="<?php echo esc_attr($slug); ?>" <?php selected( $selected_slug, $slug ); ?>>
							<?php echo esc_html( $slug ) . ' (' . esc_html( $data['name'] ) . ')'; ?>
							<?php if ($data['status'] === 'Eliminada') echo ' [ELIMINADA]'; ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div style="flex: 1; min-width: 250px; border-left: 1px dashed #cbd5e1; padding-left: 30px;">
				<label for="crea_timezone" style="font-weight: 600; display: block; margin-bottom: 8px;">2. Mi Zona Horaria de visualización:</label>
				<select name="crea_timezone" id="crea_timezone" class="crea-searchable-select">
					<?php echo wp_timezone_choice( $display_tz_string ); ?>
				</select>
				
				<label style="font-size: 13px; display: flex; align-items: center; gap: 5px; margin-top: 8px;">
					<input type="checkbox" name="save_timezone" value="1" <?php checked( !empty($saved_user_tz) ); ?>>
					Guardar esta zona horaria para mi perfil
				</label>
			</div>
		</div>

		<div style="margin-top: 15px; text-align: right;">
			<button type="submit" class="button button-primary button-large">Ver Historial de Auditoría</button>
		</div>
	</form>

	<?php if ( !empty($selected_slug) ) : ?>
		
		<?php if ( empty($logs) ) : ?>
			<p style="color: #64748b; font-style: italic;">No hay registros de auditoría para esta base.</p>
		<?php else : ?>
			
			<table class="crea-table" style="table-layout: auto; border: 1px solid #E2E8F0;">
				<thead>
					<tr>
						<th style="width: 18%;">Fecha y Hora</th>
						<th style="width: 22%;">Usuario</th>
						<th style="width: 20%;">Acción</th>
						<th style="width: 40%;">Detalle de Cambios</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $logs as $log ) : 
						$payload = json_decode($log['changes_json'], true);
						$user_snap = isset($payload['user']) ? $payload['user'] : ['ID' => '?', 'username' => '?', 'name' => '?'];
						$diffs = isset($payload['diff']) ? $payload['diff'] : [];
						
						$dt = date_create($log['created_at'], new DateTimeZone('UTC'));
						$dt->setTimezone($display_tz_obj);
						$date_str = $dt->format('d M Y | H:i:s');
						
						$action_label = isset($action_labels[$log['action_type']]) ? $action_labels[$log['action_type']] : $log['action_type'];
					?>
					<tr>
						<td>
							<strong><?php echo $date_str; ?></strong><br>
							<span style="font-size: 11px; opacity: 0.6;"><?php echo esc_html($display_tz_string); ?></span>
						</td>
						
						<td style="font-size: 13px;">
							<div style="font-weight: 600;"><?php echo esc_html($user_snap['name']); ?></div>
							<div style="opacity: 0.7; font-family: monospace;">@<?php echo esc_html($user_snap['username']); ?> (ID: <?php echo esc_html($user_snap['ID']); ?>)</div>
						</td>
						
						<td style="font-size: 13px; font-weight: 600;"><?php echo $action_label; ?></td>
						
						<td style="font-size: 13px;">
							<?php if ( !empty($diffs) ) : ?>
								<ul style="margin: 0; padding-left: 15px; list-style-type: square;">
								<?php foreach ( $diffs as $campo => $valores ) : ?>
									<li style="margin-bottom: 6px;">
										<strong><?php echo esc_html($campo); ?>:</strong><br>
										<span style="color: var(--crea-danger); text-decoration: line-through; margin-right: 5px;"><?php echo esc_html($valores['old']); ?></span>
										<span class="dashicons dashicons-arrow-right-alt" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle; opacity: 0.5;"></span>
										<span style="color: #16A34A; margin-left: 5px; font-weight: 600;"><?php echo esc_html($valores['new']); ?></span>
									</li>
								<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			
		<?php endif; ?>

	<?php elseif( isset($_GET['base_slug']) ): ?>
		<div class="notice notice-info inline"><p>Por favor selecciona una base de datos válida para ver su auditoría.</p></div>
	<?php endif; ?>
</div>