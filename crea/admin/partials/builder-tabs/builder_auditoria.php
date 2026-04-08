<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/admin/partials/builder-tabs/builder_auditoria.php
 *
 * ☀️ Pestaña de Auditoría: Historial forense de bases de datos.
 */
if ( ! defined( 'WPINC' ) ) { die; }

global $wpdb;
$table_forms = $wpdb->prefix . 'crea_forms';
$table_audit = $wpdb->prefix . 'crea_audit_log';

// Obtener todas las bases para el selector
$bases = $wpdb->get_results("SELECT id, form_name, form_slug FROM $table_forms ORDER BY form_name ASC", ARRAY_A);

$selected_base = isset( $_GET['base_id'] ) ? intval( $_GET['base_id'] ) : 0;
$logs = [];

if ( $selected_base > 0 ) {
	$logs = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $table_audit WHERE form_id = %d ORDER BY created_at DESC", $selected_base), ARRAY_A );
}

// Diccionario para etiquetas de tipo de acción
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
	
	<form method="get" action="admin.php" style="margin-bottom: 25px; padding: 15px; background: var(--crea-th-bg); border-radius: 8px; border: 1px solid #E2E8F0;">
		<input type="hidden" name="page" value="crea-builder">
		<input type="hidden" name="tab" value="auditoria">
		
		<label for="base_id" style="font-weight: 600; display: block; margin-bottom: 8px;">Selecciona la base de datos a auditar:</label>
		<div style="display: flex; gap: 10px; align-items: center;">
			<select name="base_id" id="base_id" style="max-width: 400px; width: 100%;">
				<option value="0">-- Desplegar lista de bases --</option>
				<?php foreach ( $bases as $b ) : ?>
					<option value="<?php echo $b['id']; ?>" <?php selected( $selected_base, $b['id'] ); ?>>
						<?php echo esc_html( $b['form_name'] ) . ' (' . esc_html( $b['form_slug'] ) . ')'; ?>
					</option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button button-primary">Ver Historial</button>
		</div>
	</form>

	<?php if ( $selected_base > 0 ) : ?>
		
		<?php if ( empty($logs) ) : ?>
			<p style="color: #64748b; font-style: italic;">No hay registros de auditoría para esta base.</p>
		<?php else : ?>
			
			<table class="crea-table" style="table-layout: auto; border: 1px solid #E2E8F0;">
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
						
						$date_str = wp_date('d M Y | H:i:s', strtotime($log['created_at']));
						$action_label = isset($action_labels[$log['action_type']]) ? $action_labels[$log['action_type']] : $log['action_type'];
					?>
					<tr>
						<td><strong><?php echo $date_str; ?></strong></td>
						
						<td style="font-size: 13px;">
							<div style="font-weight: 600;"><?php echo esc_html($user_snap['name']); ?></div>
							<div style="color: #64748b; font-family: monospace;">@<?php echo esc_html($user_snap['username']); ?> (ID: <?php echo esc_html($user_snap['ID']); ?>)</div>
						</td>
						
						<td style="font-size: 13px; font-weight: 600;"><?php echo $action_label; ?></td>
						
						<td style="font-size: 13px;">
							<?php if ( !empty($diffs) ) : ?>
								<ul style="margin: 0; padding-left: 15px; list-style-type: square;">
								<?php foreach ( $diffs as $campo => $valores ) : ?>
									<li style="margin-bottom: 6px;">
										<strong><?php echo esc_html($campo); ?>:</strong><br>
										<span style="color: var(--crea-danger); text-decoration: line-through; margin-right: 5px;"><?php echo esc_html($valores['old']); ?></span>
										<span class="dashicons dashicons-arrow-right-alt" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle; color: #a5b4c3;"></span>
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