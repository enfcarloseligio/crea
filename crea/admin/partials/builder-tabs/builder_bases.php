<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/admin/partials/builder-tabs/builder_bases.php
 *
 * ☀️ Contenido de la pestaña "Mis Bases".
 */
if ( ! defined( 'WPINC' ) ) { die; }

global $wpdb;
$table_forms = $wpdb->prefix . 'crea_forms';
$bases = $wpdb->get_results("SELECT * FROM $table_forms ORDER BY id DESC", ARRAY_A);

$msg = isset($_GET['msg']) ? sanitize_text_field($_GET['msg']) : '';
if ( $msg === 'created' ) echo '<div class="notice notice-success is-dismissible"><p>Base de datos creada exitosamente.</p></div>';
if ( $msg === 'updated' ) echo '<div class="notice notice-success is-dismissible"><p>Metadatos actualizados correctamente.</p></div>';
if ( $msg === 'deleted' ) echo '<div class="notice notice-success is-dismissible"><p>Base de datos y registros eliminados permanentemente.</p></div>';
?>

<div class="crea-card" style="border-top: 4px solid var(--crea-primary);">
	<div class="crea-card-header"><h2>Definición de Estructura</h2></div>
	<form method="post" action="" id="crea-new-base-form">
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="form_name">Nombre de la Base *</label></th>
					<td><input name="form_name" type="text" id="form_name" class="regular-text" required style="width: 100%; max-width: 400px;"></td>
				</tr>
				<tr>
					<th scope="row"><label for="form_slug">Identificador Interno (Slug) *</label></th>
					<td><input name="form_slug" type="text" id="form_slug" class="regular-text" required style="width: 100%; max-width: 400px;"></td>
				</tr>
				<tr>
					<th scope="row"><label for="form_year">Año de los Datos</label></th>
					<td><input name="form_year" type="number" id="form_year" class="regular-text" placeholder="Ej. 2026" style="width: 100%; max-width: 150px;"></td>
				</tr>
				<tr>
					<th scope="row"><label for="form_cut_date">Fecha de Corte</label></th>
					<td><input name="form_cut_date" type="date" id="form_cut_date" class="regular-text" style="width: 100%; max-width: 150px;"></td>
				</tr>
				<tr>
					<th scope="row"><label for="form_source">Fuente / Referencia</label></th>
					<td><input name="form_source" type="text" id="form_source" class="regular-text" placeholder="Ej. SIARHE / Censo Interno" style="width: 100%; max-width: 400px;"></td>
				</tr>
				<tr>
					<th scope="row"><label for="form_comments">Comentarios Internos</label></th>
					<td><textarea name="form_comments" id="form_comments" rows="3" style="width: 100%; max-width: 400px;"></textarea></td>
				</tr>
			</tbody>
		</table>
		<div style="margin-top: 15px;">
			<?php wp_nonce_field( 'crea_save_base_action', 'crea_save_base_nonce' ); ?>
			<input type="hidden" name="create_base" value="1">
			<input type="submit" class="button button-primary" value="Crear Base de Datos">
		</div>
	</form>
</div>

<div class="crea-card" style="padding: 0; overflow: hidden;">
	
	<div class="crea-toolbar" style="padding: 15px; border-bottom: 1px solid #c3c4c7; background: #f8fafc; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
		<div class="crea-table-controls">
			<label style="font-size: 13px;">Mostrar 
				<select id="crea-items-per-page" style="margin: 0 5px; font-size: 13px;">
					<option value="25">25</option>
					<option value="50">50</option>
					<option value="100">100</option>
					<option value="all">Todos</option>
				</select> registros
			</label>
		</div>
		<div class="crea-pagination"><div id="crea-bases-table-pagination-top" style="display: flex; gap: 5px;"></div></div>
		<div class="crea-search-box" style="position: relative; width: 100%; max-width: 300px;">
			<span class="dashicons dashicons-search" style="position: absolute; left: 10px; top: 6px; color: #8c8f94;"></span>
			<input type="text" id="crea-search-bases" placeholder="Buscar base..." style="width: 100%; padding: 4px 8px 4px 35px;">
		</div>
	</div>

	<table id="crea-bases-table" class="crea-table" style="table-layout: fixed; width: 100%; border: none;">
		<thead>
			<tr>
				<th style="width: 6%;">ID</th>
				<th style="width: 18%;">Nombre Base</th>
				<th style="width: 14%;">Nombre Sistema</th>
				<th style="width: 25%;">Auditoría</th>
				<th style="width: 12%;">Tamaño</th>
				<th style="width: 25%;">Acciones</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $bases ) : foreach ($bases as $base) : 
				$id_format = str_pad($base['id'], 2, "0", STR_PAD_LEFT);
				
				$created_timestamp = strtotime($base['created_at']);
				$updated_timestamp = strtotime($base['updated_at']);
				
				$created_str = wp_date('d F Y | H:i:s', $created_timestamp);
				$updated_str = wp_date('d F Y | H:i:s', $updated_timestamp);
				
				$author = get_userdata($base['created_by']);
				$author_name = $author ? $author->display_name : 'Usuario Desconocido';
				
				$editor = get_userdata($base['updated_by']);
				$editor_name = $editor ? $editor->display_name : 'Usuario Desconocido';
			?>
				<tr class="crea-data-row">
					<td data-label="ID"><strong><?php echo $id_format; ?></strong></td>
					<td data-label="Nombre Base">
						<strong><?php echo esc_html($base['form_name']); ?></strong><br>
						<span style="font-size: 11px; color: #8c8f94;">Año: <?php echo esc_html($base['data_year'] ?: 'N/A'); ?> | Fuente: <?php echo esc_html($base['data_source'] ?: 'N/A'); ?></span>
					</td>
					<td data-label="Slug"><code style="background: #f0f0f1;"><?php echo esc_html($base['form_slug']); ?></code></td>
					
					<td data-label="Auditoría" style="font-size: 11px; color: #646970;">
						<div style="margin-bottom: 6px;">
							<span style="color: #8c8f94;">Creado por:</span><br>
							<strong><?php echo esc_html($author_name); ?></strong><br>
							<?php echo $created_str; ?>
						</div>
						
						<?php if ( $created_timestamp !== $updated_timestamp ) : ?>
						<div style="border-top: 1px dashed #c3c4c7; padding-top: 6px;">
							<span style="color: #8c8f94;">Última Edición por:</span><br>
							<strong><?php echo esc_html($editor_name); ?></strong><br>
							<?php echo $updated_str; ?>
						</div>
						<?php endif; ?>
					</td>
					
					<td data-label="Tamaño" style="font-size: 12px;">
						<div><strong>0</strong> columnas</div>
						<div><strong>0</strong> filas</div>
						<div style="color: #8c8f94;">0 KB</div>
					</td>
					<td data-label="Acciones">
						<div style="display: flex; gap: 5px; flex-wrap: wrap;">
							<button type="button" class="button button-small crea-icon-btn crea-open-edit" 
								data-id="<?php echo $base['id']; ?>" data-name="<?php echo esc_attr($base['form_name']); ?>" data-slug="<?php echo esc_attr($base['form_slug']); ?>" 
								data-year="<?php echo esc_attr($base['data_year']); ?>" data-cutdate="<?php echo esc_attr($base['cut_date']); ?>" data-source="<?php echo esc_attr($base['data_source']); ?>" data-comments="<?php echo esc_attr($base['description']); ?>" title="Editar Metadatos">
								<span class="dashicons dashicons-edit"></span>
							</button>
							
							<button type="button" class="button button-small crea-icon-btn crea-open-shortcode" data-id="<?php echo $id_format; ?>" data-name="<?php echo esc_attr($base['form_name']); ?>" title="Ver Shortcodes">
								<span class="dashicons dashicons-admin-links"></span>
							</button>
							
							<button type="button" class="button button-small crea-icon-btn crea-open-delete" style="color: #d63638; border-color: #d63638;" 
								data-id="<?php echo $base['id']; ?>" data-slug="<?php echo esc_attr($base['form_slug']); ?>" data-cols="0" data-rows="0" data-size="0 KB" title="Eliminar Base">
								<span class="dashicons dashicons-trash"></span>
							</button>
						</div>
					</td>
				</tr>
			<?php endforeach; else : echo '<tr class="crea-empty-row"><td colspan="6" style="text-align:center; padding: 30px; color:#8c8f94;"><span class="dashicons dashicons-database" style="font-size: 40px; width: 40px; height: 40px; color: #dcdcde; margin-bottom: 10px;"></span><br>Aún no has creado ninguna base de datos.</td></tr>'; endif; ?>
		</tbody>
	</table>

	<div class="crea-pagination" style="padding: 15px; background: #f8fafc; border-top: 1px solid #c3c4c7; display: flex; justify-content: space-between; align-items: center;">
		<div id="crea-bases-table-count" style="font-size: 13px; color: #64748b;"></div>
		<div id="crea-bases-table-pagination-bottom" style="display: flex; gap: 5px;"></div>
	</div>
</div>

<div id="crea-edit-modal" class="crea-modal-overlay">
	<div class="crea-modal-content">
		<span class="dashicons dashicons-no-alt crea-modal-close"></span>
		<h2 style="margin-top:0;">Editar Metadatos</h2>
		<form method="post" action="">
			<input type="hidden" name="edit_id" id="edit_id">
			<table class="form-table">
				<tr>
					<th>Nombre de la Base *</th>
					<td><input type="text" name="edit_name" id="edit_name" class="regular-text" required></td>
				</tr>
				<tr>
					<th>Identificador Interno (Slug) *</th>
					<td><input type="text" id="edit_slug" class="regular-text" disabled> <span class="description">No editable tras creación.</span></td>
				</tr>
				<tr>
					<th>Año de los Datos</th>
					<td><input type="number" name="edit_year" id="edit_year" class="regular-text"></td>
				</tr>
				<tr>
					<th>Fecha de Corte</th>
					<td><input type="date" name="edit_cut_date" id="edit_cut_date" class="regular-text"></td>
				</tr>
				<tr>
					<th>Fuente / Referencia</th>
					<td><input type="text" name="edit_source" id="edit_source" class="regular-text"></td>
				</tr>
				<tr>
					<th>Comentarios Internos</th>
					<td><textarea name="edit_comments" id="edit_comments" rows="3" style="width:100%;"></textarea></td>
				</tr>
			</table>
			<div style="margin-top: 15px; text-align: right;">
				<?php wp_nonce_field( 'crea_edit_base_action', 'crea_edit_base_nonce' ); ?>
				<input type="hidden" name="edit_base" value="1">
				<button type="button" class="button crea-cancel-modal">Cancelar</button>
				<input type="submit" class="button button-primary" value="Guardar Cambios">
			</div>
		</form>
	</div>
</div>

<div id="crea-delete-step1" class="crea-modal-overlay crea-modal-warning">
	<div class="crea-modal-content" style="text-align: center;">
		<span class="dashicons dashicons-warning" style="font-size: 50px; width: 50px; height: 50px; color: #dba617;"></span>
		<h2>Advertencia de Eliminación</h2>
		<p style="font-size: 16px;">Estás a punto de borrar la base de datos con todo su conjunto de datos.</p>
		<div style="background: #f8fafc; padding: 15px; border: 1px solid #c3c4c7; border-radius: 4px; display: inline-block; margin-bottom: 20px;">
			<strong><span id="del-info-cols"></span></strong> Columnas | 
			<strong><span id="del-info-rows"></span></strong> Filas | 
			<strong><span id="del-info-size"></span></strong>
		</div>
		<div>
			<button type="button" class="button button-large crea-cancel-modal">Cancelar</button>
			<button type="button" class="button button-large button-primary" id="crea-continue-delete">Continuar</button>
		</div>
	</div>
</div>

<div id="crea-delete-step2" class="crea-modal-overlay crea-modal-danger">
	<div class="crea-modal-content" style="text-align: center;">
		<span class="dashicons dashicons-dismiss" style="font-size: 50px; width: 50px; height: 50px; color: #d63638;"></span>
		<h2>Acción Irremediable</h2>
		<p style="font-size: 16px;">No hay forma de recuperar los datos una vez eliminados.</p>
		<p>Para proceder, por favor escribe <strong>ELIMINAR</strong> en el siguiente recuadro:</p>
		
		<form method="post" action="">
			<input type="hidden" name="delete_id" id="delete_id">
			<input type="hidden" name="delete_slug" id="delete_slug">
			<input type="text" id="crea-confirm-delete-input" class="crea-input-danger regular-text" autocomplete="off" placeholder="ELIMINAR">
			
			<div style="margin-top: 25px;">
				<?php wp_nonce_field( 'crea_delete_base_action', 'crea_delete_base_nonce' ); ?>
				<input type="hidden" name="delete_base" value="1">
				<button type="button" class="button button-large crea-cancel-modal">Cancelar</button>
				<input type="submit" id="crea-submit-delete-btn" class="button button-large" style="background: #d63638; color: #fff; border-color: #b32d2e;" value="Aceptar" disabled>
			</div>
		</form>
	</div>
</div>

<div id="crea-shortcode-modal" class="crea-modal-overlay">
	<div class="crea-modal-content">
		<span class="dashicons dashicons-no-alt crea-modal-close"></span>
		<h2 style="margin-top:0;">Shortcodes de Integración</h2>
		<p>Copia y pega estos códigos en cualquier página o entrada para mostrar la base de datos: <strong id="modal-base-name"></strong></p>
		<hr>
		<h4>1. Versión Completa (Ver, Añadir, Editar, Borrar)</h4>
		<div class="crea-shortcode-box"><code id="sc-all"></code><button class="button button-secondary crea-copy-btn" data-target="sc-all"><span class="dashicons dashicons-clipboard"></span> Copiar</button></div>
		<h4>2. Solo Captura (Formulario para añadir registros)</h4>
		<div class="crea-shortcode-box"><code id="sc-add"></code><button class="button button-secondary crea-copy-btn" data-target="sc-add"><span class="dashicons dashicons-clipboard"></span> Copiar</button></div>
		<h4>3. Solo Lectura (Tabla de datos pública)</h4>
		<div class="crea-shortcode-box"><code id="sc-view"></code><button class="button button-secondary crea-copy-btn" data-target="sc-view"><span class="dashicons dashicons-clipboard"></span> Copiar</button></div>
	</div>
</div>

<div id="crea-slug-error-modal" class="crea-modal-overlay crea-modal-warning">
	<div class="crea-modal-content" style="text-align: center;">
		<span class="dashicons dashicons-warning" style="font-size: 50px; width: 50px; height: 50px; color: #dba617;"></span>
		<h2>Identificador Duplicado</h2>
		<p style="font-size: 16px;">El Identificador Interno <strong id="crea-duplicate-slug-name"></strong> ya existe en el sistema.</p>
		<p>Por favor, elige un identificador diferente para evitar conflictos en la base de datos.</p>
		<div style="margin-top: 25px;">
			<button type="button" class="button button-large button-primary crea-cancel-modal">Entendido</button>
		</div>
	</div>
</div>