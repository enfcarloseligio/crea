<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/admin/partials/builder-tabs/builder_auditoria.php
 *
 * Pestaña de Auditoría (Base): Arquitectura Servidor con Exportación.
 */
if ( ! defined( 'WPINC' ) ) { die; }

global $wpdb;
$table_forms = $wpdb->prefix . 'crea_forms';
$table_audit = $wpdb->prefix . 'crea_audit_log';
$current_user_id = get_current_user_id();

// Mensajes de Alerta
$msg = isset($_GET['msg']) ? sanitize_text_field($_GET['msg']) : '';
if ( $msg === 'export_limit' ) {
	echo '<div class="notice notice-warning is-dismissible"><p><strong>La exportación en vivo falló:</strong> El conjunto de datos supera el límite de seguridad (5,000 registros). Por favor, utiliza la herramienta de exportación en segundo plano (próximamente) o ajusta los filtros.</p></div>';
}

// 1. ZONA HORARIA
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

try { $display_tz_obj = new DateTimeZone( $display_tz_string ); } 
catch (Exception $e) { $display_tz_obj = wp_timezone(); $display_tz_string = $display_tz_obj->getName(); }

// 2. LISTA UNIFICADA DE BASES
$dropdown_options = [];
$active_bases = $wpdb->get_results("SELECT form_name, form_slug FROM $table_forms", ARRAY_A);
if($active_bases) {
	foreach($active_bases as $ab) { $dropdown_options[$ab['form_slug']] = array('name' => $ab['form_name'], 'status' => 'Activa'); }
}
$all_jsons = $wpdb->get_col("SELECT changes_json FROM $table_audit");
if($all_jsons) {
	foreach($all_jsons as $json_str) {
		$payload = json_decode($json_str, true);
		if ( isset($payload['base_slug']) && !isset($dropdown_options[$payload['base_slug']]) ) {
			$dropdown_options[$payload['base_slug']] = array('name' => isset($payload['base_name']) ? $payload['base_name'] : $payload['base_slug'], 'status' => 'Eliminada');
		}
	}
}
ksort($dropdown_options);

// 3. CAPTURA DE FILTROS ACTUALES
$selected_slug = isset( $_GET['base_slug'] ) ? sanitize_text_field( $_GET['base_slug'] ) : '';
$filter_user   = isset( $_GET['filter_user'] ) ? intval( $_GET['filter_user'] ) : 0;
$filter_action = isset( $_GET['filter_action'] ) ? sanitize_text_field( $_GET['filter_action'] ) : 'all';
$filter_year   = isset( $_GET['filter_year'] ) ? sanitize_text_field( $_GET['filter_year'] ) : date('Y');

// Paginación
$per_page = isset( $_GET['per_page'] ) ? intval( $_GET['per_page'] ) : 50;
if (!in_array($per_page, [50, 100, 500])) $per_page = 50;
$paged = isset( $_GET['paged'] ) ? max(1, intval( $_GET['paged'] )) : 1;

$action_labels = [
	'create'      => '<span class="dashicons dashicons-database" style="color:var(--crea-primary);"></span> Creación Base',
	'update_meta' => '<span class="dashicons dashicons-edit" style="color:var(--crea-warning);"></span> Edición Metadatos',
	'delete'      => '<span class="dashicons dashicons-trash" style="color:var(--crea-danger);"></span> Eliminación',
	'add_col'     => '<span class="dashicons dashicons-plus-alt2" style="color:#16A34A;"></span> Nueva Variable',
	'edit_col'    => '<span class="dashicons dashicons-admin-generic" style="color:var(--crea-warning);"></span> Edición Variable',
];

$logs = [];
$total_items = 0;
$total_pages = 0;
$available_users = [];
$available_years = [date('Y')];

if ( !empty($selected_slug) ) {
	$like_query = '%"base_slug":"' . $wpdb->esc_like($selected_slug) . '"%';
	$current_form_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_forms WHERE form_slug = %s", $selected_slug));
	
	$base_where = $current_form_id ? $wpdb->prepare("(form_id = %d OR changes_json LIKE %s)", $current_form_id, $like_query) : $wpdb->prepare("(changes_json LIKE %s)", $like_query);
	
	$distinct_users = $wpdb->get_col("SELECT DISTINCT user_id FROM $table_audit WHERE $base_where");
	foreach ($distinct_users as $u_id) {
		$u_info = get_userdata($u_id);
		if ($u_info) $available_users[$u_id] = "[$u_id] " . $u_info->user_login;
	}
	
	$distinct_years = $wpdb->get_col("SELECT DISTINCT YEAR(created_at) FROM $table_audit WHERE $base_where ORDER BY 1 DESC");
	if (!empty($distinct_years)) $available_years = $distinct_years;
	if (!in_array($filter_year, $available_years) && $filter_year !== 'all') $filter_year = $available_years[0];

	$query_where = "WHERE " . $base_where;
	if ( $filter_user > 0 ) $query_where .= $wpdb->prepare(" AND user_id = %d", $filter_user);
	if ( $filter_action !== 'all' ) $query_where .= $wpdb->prepare(" AND action_type = %s", $filter_action);
	if ( $filter_year !== 'all' ) $query_where .= $wpdb->prepare(" AND YEAR(created_at) = %d", $filter_year);

	$total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_audit $query_where");
	$total_pages = ceil($total_items / $per_page);
	$offset = ($paged - 1) * $per_page;

	$logs = $wpdb->get_results("SELECT * FROM $table_audit $query_where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset", ARRAY_A);
}
?>

<div class="crea-card" style="border-top: 4px solid var(--crea-ink);">
	<div class="crea-card-header"><h2>Auditoría de Estructura de Base de Datos</h2></div>
	
	<form method="get" action="admin.php" style="margin-bottom: 25px; padding: 20px; background: #F8FAFC; border-radius: 8px; border: 1px solid #E2E8F0;">
		<input type="hidden" name="page" value="crea-builder">
		<input type="hidden" name="tab" value="auditoria">
		<input type="hidden" name="crea_timezone_submitted" value="1">
		
		<div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 15px;">
			<div style="flex: 2; min-width: 250px;">
				<label for="base_slug" style="font-weight: 600; display: block; margin-bottom: 8px;">Base de Datos a Auditar:</label>
				<select name="base_slug" id="base_slug" class="crea-searchable-select" onchange="this.form.submit()">
					<option value="">-- Selecciona una base --</option>
					<?php foreach ( $dropdown_options as $slug => $data ) : ?>
						<option value="<?php echo esc_attr($slug); ?>" <?php selected( $selected_slug, $slug ); ?>>
							<?php echo esc_html( $slug ) . ' (' . esc_html( $data['name'] ) . ')'; ?>
							<?php if ($data['status'] === 'Eliminada') echo ' [ELIMINADA]'; ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div style="flex: 1; min-width: 250px;">
				<label for="crea_timezone" style="font-weight: 600; display: block; margin-bottom: 8px;">Zona Horaria:</label>
				<select name="crea_timezone" id="crea_timezone" class="crea-searchable-select">
					<?php echo wp_timezone_choice( $display_tz_string ); ?>
				</select>
				<label style="font-size: 11px; display: block; margin-top: 5px;">
					<input type="checkbox" name="save_timezone" value="1" <?php checked( !empty($saved_user_tz) ); ?>> Guardar para mi perfil
				</label>
			</div>
		</div>

		<?php if ( !empty($selected_slug) ) : ?>
		<hr style="border: 0; border-top: 1px dashed #cbd5e1; margin: 15px 0;">
		<div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; justify-content: space-between;">
			<div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; flex: 1;">
				<div style="flex: 1; min-width: 120px;">
					<label style="font-size: 13px; font-weight:600; display:block; margin-bottom:4px;">Año:</label>
					<select name="filter_year" style="width: 100%;">
						<option value="all" <?php selected($filter_year, 'all'); ?>>Todos los Años</option>
						<?php foreach($available_years as $y) : ?>
							<option value="<?php echo $y; ?>" <?php selected($filter_year, $y); ?>><?php echo $y; ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				
				<div style="flex: 1; min-width: 150px;">
					<label style="font-size: 13px; font-weight:600; display:block; margin-bottom:4px;">Usuario:</label>
					<select name="filter_user" style="width: 100%;">
						<option value="0">Todos los Usuarios</option>
						<?php foreach($available_users as $id => $label) : ?>
							<option value="<?php echo $id; ?>" <?php selected($filter_user, $id); ?>><?php echo esc_html($label); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div style="flex: 1; min-width: 150px;">
					<label style="font-size: 13px; font-weight:600; display:block; margin-bottom:4px;">Acción:</label>
					<select name="filter_action" style="width: 100%;">
						<option value="all">Todas las Acciones</option>
						<option value="create" <?php selected($filter_action, 'create'); ?>>Creación</option>
						<option value="update_meta" <?php selected($filter_action, 'update_meta'); ?>>Edición de Metadatos</option>
						<option value="delete" <?php selected($filter_action, 'delete'); ?>>Eliminación</option>
					</select>
				</div>
				
				<div style="flex: 1; min-width: 100px;">
					<label style="font-size: 13px; font-weight:600; display:block; margin-bottom:4px;">Mostrar:</label>
					<select name="per_page" style="width: 100%;">
						<option value="50" <?php selected($per_page, 50); ?>>50 reg.</option>
						<option value="100" <?php selected($per_page, 100); ?>>100 reg.</option>
						<option value="500" <?php selected($per_page, 500); ?>>500 reg.</option>
					</select>
				</div>

				<div>
					<button type="submit" class="button button-secondary">Filtrar</button>
				</div>
			</div>
			
			<div style="display: flex; gap: 5px;">
				<button type="submit" name="crea_export" value="csv" class="button"><span class="dashicons dashicons-media-spreadsheet" style="margin-top:3px;"></span> CSV</button>
				<button type="submit" name="crea_export" value="json" class="button"><span class="dashicons dashicons-media-code" style="margin-top:3px;"></span> JSON</button>
			</div>
		</div>
		<?php endif; ?>
	</form>

	<?php if ( !empty($selected_slug) ) : ?>
		
		<?php if ( empty($logs) ) : ?>
			<div style="padding: 20px; text-align: center; border: 1px dashed #cbd5e1; color: #64748b;">No hay registros que coincidan con estos filtros.</div>
		<?php else : ?>
			
			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
				<span style="font-size: 13px; color: #475569;">Mostrando <strong><?php echo count($logs); ?></strong> de <strong><?php echo $total_items; ?></strong> registros.</span>
				<?php 
				if ( $total_pages > 1 ) {
					$page_links = paginate_links( array(
						'base' => add_query_arg( 'paged', '%#%' ),
						'format' => '',
						'prev_text' => '&laquo;',
						'next_text' => '&raquo;',
						'total' => $total_pages,
						'current' => $paged
					) );
					if ( $page_links ) echo '<div class="tablenav-pages" style="margin: 0;">' . $page_links . '</div>';
				}
				?>
			</div>

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