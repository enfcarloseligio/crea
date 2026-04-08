<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/admin/partials/builder-tabs/builder_auditoria.php
 *
 * ☀️ Pestaña de Auditoría: Estándar Global UTC con selector dinámico de Zona Horaria por usuario.
 */
if ( ! defined( 'WPINC' ) ) { die; }

global $wpdb;
$table_forms = $wpdb->prefix . 'crea_forms';
$table_audit = $wpdb->prefix . 'crea_audit_log';
$current_user_id = get_current_user_id();

// ☀️ LÓGICA DE ZONA HORARIA PERSONALIZADA
$global_wp_tz = wp_timezone_string();
$saved_user_tz = get_user_meta( $current_user_id, 'crea_audit_timezone', true );

// Procesar el formulario si se envió un cambio de zona horaria
if ( isset( $_GET['crea_timezone_submitted'] ) ) {
	$selected_tz = sanitize_text_field( $_GET['crea_timezone'] );
	
	// Si marcó la casilla "Guardar para mí"
	if ( isset( $_GET['save_timezone'] ) && $_GET['save_timezone'] === '1' ) {
		update_user_meta( $current_user_id, 'crea_audit_timezone', $selected_tz );
		$saved_user_tz = $selected_tz;
	} else {
		// Si la desmarcó, borramos su preferencia para que vuelva al default de WP
		delete_user_meta( $current_user_id, 'crea_audit_timezone' );
		$saved_user_tz = '';
	}
	$display_tz_string = $selected_tz;
} else {
	// Si no envió el formulario, usa la guardada o, si no hay, la global de WP
	$display_tz_string = !empty( $saved_user_tz ) ? $saved_user_tz : $global_wp_tz;
}

// Intentar instanciar el objeto DateTimeZone, fallback si falla
try {
	$display_tz_obj = new DateTimeZone( $display_tz_string );
} catch (Exception $e) {
	$display_tz_obj = wp_timezone();
	$display_tz_string = $display_tz_obj->getName();
}

// Obtener todas las bases para el selector
$bases = $wpdb->get_results("SELECT id, form_name, form_slug FROM $table_forms ORDER BY form_name ASC", ARRAY_A);
$selected_base = isset( $_GET['base_id'] ) ? intval( $_GET['base_id'] ) : 0;
$logs = [];

if ( $selected_base > 0 ) {
	$logs = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $table_audit WHERE form_id = %d ORDER BY created_at DESC", $selected_base), ARRAY_A );
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
	
	<form method="get" action="admin.php" style="margin-bottom: 25px; padding: 15px; background: var(--crea-th-bg); border-radius: 8px; border: 1px solid rgba(128,128,128,0.2);">
		<input type="hidden" name="page" value="crea-builder">
		<input type="hidden" name="tab" value="auditoria">
		<input type="hidden" name="crea_timezone_submitted" value="1">
		
		<div style="display: flex; gap: 30px; flex-wrap: wrap;">
			<div style="flex: 1; min-width: 250px;">
				<label for="base_id" style="font-weight: 600; display: block; margin-bottom: 8px;">1. Selecciona la base de datos:</label>
				<select name="base_id" id="base_id" style="width: 100%;">
					<option value="0">-- Desplegar lista de bases --</option>
					<?php foreach ( $bases as $b ) : ?>
						<option value="<?php echo $b['id']; ?>" <?php selected( $selected_base, $b['id'] ); ?>>
							<?php echo esc_html( $b['form_name'] ) . ' (' . esc_html( $b['form_slug'] ) . ')'; ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div style="flex: 1; min-width: 250px; border-left: 1px dashed rgba(128,128,128,0.3); padding-left: 30px;">
				<label for="crea_timezone" style="font-weight: 600; display: block; margin-bottom: 8px;">2. Mi Zona Horaria de visualización:</label>
				<select name="crea_timezone" id="crea_timezone" style="width: 100%; margin-bottom: 8px;">
					<?php echo wp_timezone_choice( $display_tz_string ); ?>
				</select>
				
				<label style="font-size: 13px; display: flex; align-items: center; gap: 5px;">
					<input type="checkbox" name="save_timezone" value="1" <?php checked( !empty($saved_user_tz) ); ?>>
					Guardar esta zona horaria para mi perfil
				</label>
			</div>
		</div>

		<div style="margin-top: 15px; text-align: right;">
			<button type="submit" class="button button-primary button-large">Ver Historial de Auditoría</button>
		</div>
	</form>

	<?php if ( $selected_base > 0 ) : ?>
		
		<?php if ( empty($logs) ) : ?>
			<p style="opacity: 0.7; font-style: italic;">No hay registros de auditoría para esta base.</p>
		<?php else : ?>
			
			<table class="crea-table" style="table-layout: auto; border: 1px solid rgba(128,128,128,0.2);">
				<thead>
					<tr>
						<th style="width: 18%;">Fecha y Hora</th>
						<th style="width: 22%;">Usuario (Inmutable)</th>
						<th style="width: 20%;">Acción</th>
						<th style="width: 40%;">Detalle de Cambios (Diff)</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $logs as $log ) : 
						$payload = json_decode($log['changes_json'], true);
						$user_snap = isset($payload['user']) ? $payload['user'] : ['ID' => '?', 'username' => '?', 'name' => '?'];
						$diffs = isset($payload['diff']) ? $payload['diff'] : [];
						
						// ☀️ LÓGICA MUNDIAL: Lee UTC Absoluto y lo traduce a la zona horaria que elegiste en el menú superior
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

	<?php elseif( isset($_GET['base_id']) ): ?>
		<div class="notice notice-info inline"><p>Por favor selecciona una base de datos válida para ver su auditoría.</p></div>
	<?php endif; ?>
</div>