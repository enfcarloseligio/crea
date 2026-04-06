<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/admin/partials/crea-builder.php
 *
 * ☀️ Vista HTML para el Constructor de Bases de Datos.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div class="wrap">
	<h1 class="wp-heading-inline">Constructor de Bases de Datos</h1>
	<a href="#" class="page-title-action">Crear Nueva Base</a>
	<hr class="wp-header-end">

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			
			<div id="post-body-content">
				<div class="postbox">
					<h2 class="hndle"><span>Diseñador de Estructura</span></h2>
					<div class="inside">
						<p>Define el nombre de tu nueva tabla y añade las variables (campos) que necesitas capturar.</p>
						
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row"><label for="form_name">Nombre de la Base (Ej. Inventario Quirúrgico)</label></th>
									<td>
										<input name="form_name" type="text" id="form_name" value="" class="regular-text" required>
										<p class="description">Este será el título público de la base de datos.</p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="form_slug">Identificador Interno (Slug)</label></th>
									<td>
										<input name="form_slug" type="text" id="form_slug" value="" class="regular-text" placeholder="ej_inventario_qx" required>
										<p class="description">Solo letras minúsculas y guiones bajos. Se usará para la tabla SQL.</p>
									</td>
								</tr>
							</tbody>
						</table>

						<hr>
						<h3>Variables de Captura</h3>
						<div style="background: #f0f0f1; padding: 15px; border: 1px dashed #c3c4c7; text-align: center;">
							<p><em>En el próximo paso conectaremos JavaScript para agregar campos dinámicos (Texto, Fecha, Coordenadas) aquí.</em></p>
							<button type="button" class="button button-secondary"> + Añadir Nuevo Campo</button>
						</div>

					</div>
				</div>
			</div>

			<div id="postbox-container-1" class="postbox-container">
				<div class="postbox">
					<h2 class="hndle"><span>Guardar Estructura</span></h2>
					<div class="inside">
						<p>Al guardar, el sistema generará una tabla SQL independiente para esta base de datos.</p>
						<div class="submitbox">
							<div id="major-publishing-actions" style="background: transparent; border-top: 0; padding: 0;">
								<div id="publishing-action">
									<span class="spinner"></span>
									<input type="submit" name="save_form" id="publish" class="button button-primary button-large" value="Crear Base de Datos">
								</div>
								<div class="clear"></div>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
</div>