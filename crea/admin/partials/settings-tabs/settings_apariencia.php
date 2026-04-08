<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/admin/partials/settings-tabs/settings_apariencia.php
 *
 * ☀️ Pestaña de Apariencia: Selector nativo de colores y vista previa en vivo.
 */
if ( ! defined( 'WPINC' ) ) { die; }

// ☀️ Notificación de éxito al guardar
$msg = isset($_GET['msg']) ? sanitize_text_field($_GET['msg']) : '';
if ( $msg === 'appearance_saved' ) {
	echo '<div class="notice notice-success is-dismissible"><p><span class="dashicons dashicons-yes-alt"></span> Ajustes de apariencia guardados correctamente.</p></div>';
}

// ☀️ Obtención real de colores desde la base de datos con Valores por Defecto
$default_admin_colors = [
	'th_bg'       => '#F8FAFC',
	'th_text'     => '#0F172A',
	'odd_bg'      => '#FFFFFF',
	'odd_text'    => '#475569',
	'even_bg'     => '#F1F5F9',
	'even_text'   => '#475569',
];
$admin_colors = wp_parse_args( get_option( 'crea_admin_colors', [] ), $default_admin_colors );

$default_front_colors = [
	'primary'     => '#0A66C2',
	'th_bg'       => '#0F172A',
	'th_text'     => '#FFFFFF',
];
$front_colors = wp_parse_args( get_option( 'crea_front_colors', [] ), $default_front_colors );
?>

<style>
	.crea-tema-flex { display: flex; gap: 25px; flex-wrap: wrap; }
	.crea-tema-col label { display: block; margin-bottom: 5px; font-weight: 600; color: #50575e; }

	@media screen and (max-width: 767px) {
		.crea-tema-flex { flex-direction: column; gap: 15px; }
		
		/* ☀️ Elevamos la especificidad uniendo ID y Clase para anular el CSS global sin usar !important */
		#crea-preview-table.crea-table {
			display: table;
			border: 1px solid #c3c4c7;
			box-shadow: 0 1px 1px rgba(0,0,0,0.04);
			background: transparent;
			width: 100%; 
			min-width: 0;
		}
		#crea-preview-table.crea-table thead { display: table-header-group; }
		
		#crea-preview-table.crea-table tr {
			display: table-row;
			margin-bottom: 0;
			border: none;
			border-radius: 0;
			box-shadow: none;
			cursor: default;
		}
		#crea-preview-table.crea-table tr::after { display: none; }
		
		#crea-preview-table.crea-table th,
		#crea-preview-table.crea-table td {
			display: table-cell;
			padding: 10px;
			text-align: left;
			border-bottom: 1px solid #f0f0f1;
			font-size: 13px;
		}
		#crea-preview-table.crea-table td::before { display: none; }
		#crea-preview-table.crea-table td:first-child { 
			color: inherit; 
			font-weight: normal; 
			font-size: inherit; 
		}
	}
</style>

<form method="post" action="">
	
	<div class="crea-card" style="border-top: 4px solid var(--crea-primary);">
		<div class="crea-card-header">
			<h2>Panel de Administración (Backend)</h2>
		</div>
		<p class="description" style="margin-bottom: 20px;">Personaliza los colores de las tablas que ves tú y los administradores aquí dentro de WordPress.</p>
		
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">Cabecera de Tabla (Header)</th>
				<td>
					<div class="crea-tema-flex">
						<div class="crea-tema-col">
							<label>Fondo:</label>
							<input type="text" name="admin_th_bg" value="<?php echo esc_attr($admin_colors['th_bg']); ?>" class="crea-color-field" data-variable="--crea-th-bg">
						</div>
						<div class="crea-tema-col">
							<label>Texto:</label>
							<input type="text" name="admin_th_text" value="<?php echo esc_attr($admin_colors['th_text']); ?>" class="crea-color-field" data-variable="--crea-th-text">
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row">Filas Impares (1, 3, 5...)</th>
				<td>
					<div class="crea-tema-flex">
						<div class="crea-tema-col">
							<label>Fondo:</label>
							<input type="text" name="admin_odd_bg" value="<?php echo esc_attr($admin_colors['odd_bg']); ?>" class="crea-color-field" data-variable="--crea-tr-odd-bg">
						</div>
						<div class="crea-tema-col">
							<label>Texto:</label>
							<input type="text" name="admin_odd_text" value="<?php echo esc_attr($admin_colors['odd_text']); ?>" class="crea-color-field" data-variable="--crea-tr-odd-text">
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row">Filas Pares (2, 4, 6...)</th>
				<td>
					<div class="crea-tema-flex">
						<div class="crea-tema-col">
							<label>Fondo:</label>
							<input type="text" name="admin_even_bg" value="<?php echo esc_attr($admin_colors['even_bg']); ?>" class="crea-color-field" data-variable="--crea-tr-even-bg">
						</div>
						<div class="crea-tema-col">
							<label>Texto:</label>
							<input type="text" name="admin_even_text" value="<?php echo esc_attr($admin_colors['even_text']); ?>" class="crea-color-field" data-variable="--crea-tr-even-text">
						</div>
					</div>
				</td>
			</tr>
		</table>

		<hr style="margin: 30px 0;">
		<h3>Vista Previa (Backend)</h3>
		<div style="overflow-x: auto;">
			<table id="crea-preview-table" class="crea-table" style="min-width: 400px; max-width: 100%;">
				<thead>
					<tr>
						<th>ID</th>
						<th>Nombre del Registro</th>
						<th>Estado</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>01</td>
						<td>Ejemplo Impar (Fila 1)</td>
						<td>Activo</td>
					</tr>
					<tr>
						<td>02</td>
						<td>Ejemplo Par (Fila 2)</td>
						<td>Revisión</td>
					</tr>
					<tr>
						<td>03</td>
						<td>Ejemplo Impar (Fila 3)</td>
						<td>Completado</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<div class="crea-card" style="border-top: 4px solid var(--crea-warning, #F59E0B);">
		<div class="crea-card-header">
			<h2>Tablas Públicas (Sitio Web)</h2>
		</div>
		<p class="description" style="margin-bottom: 20px;">Define los colores que verán tus visitantes cuando insertes los shortcodes en tus páginas públicas.</p>
		
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">Color Primario de la Base</th>
				<td>
					<div class="crea-tema-col">
						<input type="text" name="front_primary" value="<?php echo esc_attr($front_colors['primary']); ?>" class="crea-color-field">
					</div>
					<p class="description">Se utiliza para botones de acciones, paginación y enlaces activos.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">Cabecera de Tabla (Pública)</th>
				<td>
					<div class="crea-tema-flex">
						<div class="crea-tema-col">
							<label>Fondo:</label>
							<input type="text" name="front_th_bg" value="<?php echo esc_attr($front_colors['th_bg']); ?>" class="crea-color-field">
						</div>
						<div class="crea-tema-col">
							<label>Texto:</label>
							<input type="text" name="front_th_text" value="<?php echo esc_attr($front_colors['th_text']); ?>" class="crea-color-field">
						</div>
					</div>
				</td>
			</tr>
		</table>
	</div>

	<div style="margin-top: 20px;">
		<?php wp_nonce_field( 'crea_save_appearance_action', 'crea_save_appearance_nonce' ); ?>
		<input type="hidden" name="crea_save_appearance" value="1">
		
		<input type="submit" class="button button-primary button-large" value="Guardar Ajustes de Apariencia">
	</div>
</form>