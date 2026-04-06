<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/admin/partials/crea-builder.php
 *
 * Vista HTML para el Constructor utilizando el layout de tarjetas al 100%.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div class="wrap">
	<h1 class="wp-heading-inline">Constructor de Bases de Datos</h1>
	<hr class="wp-header-end">

	<form method="post" action="" id="crea-builder-form">
		
		<div class="crea-card">
			<div class="crea-card-header">
				<h2>1. Definición de la Estructura</h2>
			</div>
			
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="form_name">Nombre de la Base (Ej. Inventario Quirúrgico)</label></th>
						<td>
							<input name="form_name" type="text" id="form_name" class="regular-text" required style="width: 100%; max-width: 400px;">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="form_slug">Identificador Interno (Slug)</label></th>
						<td>
							<input name="form_slug" type="text" id="form_slug" class="regular-text" placeholder="ej_inventario_qx" required style="width: 100%; max-width: 400px;">
							<p class="description">Se usará como nombre físico en la tabla SQL (letras minúsculas y guiones bajos).</p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="crea-card">
			<div class="crea-card-header">
				<h2>2. Variables de Captura (Campos)</h2>
			</div>
			
			<table class="crea-table" id="crea-fields-table">
				<thead>
					<tr>
						<th>Orden</th>
						<th>Nombre de Variable</th>
						<th>Tipo de Dato</th>
						<th>Obligatorio</th>
						<th>Acciones</th>
					</tr>
				</thead>
				<tbody id="crea-fields-container">
					<tr>
						<td data-label="Orden" data-mobile-role="secondary">1</td>
						<td data-label="Variable" data-mobile-role="primary">
							<input type="text" name="fields[0][label]" value="Fecha de Registro" style="width: 100%;">
						</td>
						<td data-label="Tipo" data-mobile-role="secondary">
							<select name="fields[0][type]" style="width: 100%;">
								<option value="date">Fecha</option>
								<option value="text">Texto Corto</option>
								<option value="number">Numérico</option>
							</select>
						</td>
						<td data-label="Obligatorio" data-mobile-role="secondary">
							<input type="checkbox" name="fields[0][required]" value="1" checked>
						</td>
						<td data-label="Acciones" data-mobile-role="secondary">
							<button type="button" class="button button-link-delete">Eliminar</button>
						</td>
					</tr>
				</tbody>
			</table>

			<div style="margin-top: 15px;">
				<button type="button" class="button button-secondary" id="crea-add-field">+ Añadir Nueva Variable</button>
			</div>
		</div>

		<div class="crea-card" style="background: #f8fafc; text-align: right;">
			<input type="submit" name="save_form" class="button button-primary button-large" value="Procesar y Crear Base de Datos SQL">
		</div>

	</form>
</div>