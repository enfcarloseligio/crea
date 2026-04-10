<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/admin/partials/builder-tabs/builder_bases.php
 *
 * Contenido de la pestaña "Mis Bases" (Versión Optimizada con Roles, Comentarios y Contadores Reales).
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
                    <td><textarea name="form_source" id="form_source" rows="3" placeholder="Ej. SIARHE / Censo Interno" style="width: 100%; max-width: 400px;"></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="form_comments">Comentarios Internos</label></th>
                    <td><textarea name="form_comments" id="form_comments" rows="3" style="width: 100%; max-width: 400px;"></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="audit_records">Auditoría de Registros</label></th>
                    <td>
                        <select name="audit_records" id="audit_records" style="width: 100%; max-width: 400px;">
                            <option value="1" selected>Sí, registrar cambios en datos capturados (Recomendado)</option>
                            <option value="0">No, solo auditar la estructura de la base</option>
                        </select>
                    </td>
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
    
    <div class="crea-toolbar" style="padding: 15px; border-bottom: 1px solid var(--crea-th-bg); background: transparent; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div class="crea-table-controls">
            <label style="font-size: 13px;">Mostrar 
                <select id="crea-items-per-page" style="margin: 0 5px; font-size: 13px;">
                    <option value="25">25</option><option value="50">50</option><option value="100">100</option><option value="all">Todos</option>
                </select> registros
            </label>
        </div>
        <div class="crea-pagination"><div id="crea-bases-table-pagination-top" style="display: flex; gap: 5px;"></div></div>
        <div class="crea-search-box" style="position: relative; width: 100%; max-width: 300px;">
            <span class="dashicons dashicons-search" style="position: absolute; left: 10px; top: 6px; color: inherit; opacity: 0.5;"></span>
            <input type="text" id="crea-search-bases" placeholder="Buscar base..." style="width: 100%; padding: 4px 8px 4px 35px;">
        </div>
    </div>

    <table id="crea-bases-table" class="crea-table" style="table-layout: fixed; width: 100%; border: none;">
        <thead>
            <tr>
                <th style="width: 7%;" class="crea-sortable" data-sort-type="number">ID <span class="dashicons dashicons-sort"></span></th>
                <th style="width: 18%;" class="crea-sortable" data-sort-type="string">Nombre Base <span class="dashicons dashicons-sort"></span></th>
                <th style="width: 15%;" class="crea-sortable" data-sort-type="string">Nombre Sistema <span class="dashicons dashicons-sort"></span></th>
                <th style="width: 23%;" class="crea-sortable" data-sort-type="number">Auditoría <span class="dashicons dashicons-sort"></span></th>
                <th style="width: 12%;">Tamaño</th>
                <th style="width: 25%;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $bases ) : foreach ($bases as $base) : 
                $id_format = str_pad($base['id'], 2, "0", STR_PAD_LEFT);
                
                $timezone = wp_timezone();
                $created_timestamp = date_create($base['created_at'], $timezone)->getTimestamp();
                $updated_timestamp = date_create($base['updated_at'], $timezone)->getTimestamp();
                $sort_timestamp = max($created_timestamp, $updated_timestamp);
                
                $created_str = wp_date('d F Y | H:i:s', $created_timestamp);
                $updated_str = wp_date('d F Y | H:i:s', $updated_timestamp);
                $cut_date_str = !empty($base['cut_date']) ? wp_date('d M Y', date_create($base['cut_date'], $timezone)->getTimestamp()) : 'N/A';
                
                $author = get_userdata($base['created_by']);
                $author_name = $author ? $author->display_name : 'Usuario Desconocido';
                $editor = get_userdata($base['updated_by']);
                $editor_name = $editor ? $editor->display_name : 'Usuario Desconocido';
            ?>
                <tr class="crea-data-row">
                    <td data-label="ID" data-sort-val="<?php echo $base['id']; ?>"><strong><?php echo $id_format; ?></strong></td>
                    
                    <td data-label="Nombre Base" data-mobile-role="primary" data-sort-val="<?php echo esc_attr($base['form_name']); ?>">
                        <strong><?php echo esc_html($base['form_name']); ?></strong><br>
                        <span style="font-size: 11px; opacity: 0.8;">Año: <?php echo esc_html($base['data_year'] ?: 'N/A'); ?> | Corte: <?php echo esc_html($cut_date_str); ?></span>
                        <?php if ( !empty($base['description']) ) : ?>
                            <br><span style="font-size: 11px; opacity: 0.65; display: inline-block; margin-top: 4px;"><em><?php echo esc_html($base['description']); ?></em></span>
                        <?php endif; ?>
                    </td>
                    
                    <td data-label="Slug" data-sort-val="<?php echo esc_attr($base['form_slug']); ?>"><code style="background: transparent; color: inherit; padding: 0; font-size: 13px; font-weight: 600; opacity: 0.9;"><?php echo esc_html($base['form_slug']); ?></code></td>
                    
                    <td data-label="Auditoría" data-sort-val="<?php echo $sort_timestamp; ?>" style="font-size: 11px;">
                        <div style="margin-bottom: 6px;">
                            <span style="opacity: 0.7;">Creado por:</span><br>
                            <strong><?php echo esc_html($author_name); ?></strong><br>
                            <span style="opacity: 0.9;"><?php echo $created_str; ?></span>
                        </div>
                        
                        <?php if ( $created_timestamp !== $updated_timestamp ) : ?>
                        <div style="border-top: 1px dashed currentColor; padding-top: 6px; margin-top: 6px; opacity: 0.8;">
                            <span style="opacity: 0.7;">Última Edición por:</span><br>
                            <strong><?php echo esc_html($editor_name); ?></strong><br>
                            <span style="opacity: 0.9;"><?php echo $updated_str; ?></span>
                        </div>
                        <?php endif; ?>
                    </td>
                    
                    <td data-label="Tamaño" style="font-size: 12px;">
                        <?php 
                            // ☀️ CÁLCULO DINÁMICO DE COLUMNAS Y FILAS
                            $table_fields = $wpdb->prefix . 'crea_fields';
                            $count_cols = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_fields WHERE form_id = %d", $base['id']));
                            
                            $physical_table = $wpdb->prefix . "crea_data_" . $base['form_slug'];
                            $count_rows = 0;
                            // Verificamos si la tabla física ya fue creada
                            if ($wpdb->get_var("SHOW TABLES LIKE '$physical_table'") === $physical_table) {
                                $count_rows = $wpdb->get_var("SELECT COUNT(*) FROM $physical_table");
                            }
                        ?>
                        <div><strong><?php echo intval($count_cols); ?></strong> columnas</div>
                        <div><strong><?php echo intval($count_rows); ?></strong> filas</div>
                        <div style="opacity: 0.7;">0 KB</div>
                    </td>
                    <td data-label="Acciones">
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <button type="button" class="button button-small crea-icon-btn crea-open-edit" 
                                data-id="<?php echo $base['id']; ?>" data-name="<?php echo esc_attr($base['form_name']); ?>" data-slug="<?php echo esc_attr($base['form_slug']); ?>" 
                                data-year="<?php echo esc_attr($base['data_year']); ?>" data-cutdate="<?php echo esc_attr($base['cut_date']); ?>" data-source="<?php echo esc_attr($base['data_source']); ?>" data-comments="<?php echo esc_attr($base['description']); ?>" 
                                data-audit="<?php echo isset($base['audit_records']) ? $base['audit_records'] : 1; ?>" title="Editar Metadatos">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            
                            <button type="button" class="button button-small crea-icon-btn crea-open-shortcode" data-id="<?php echo $id_format; ?>" data-name="<?php echo esc_attr($base['form_name']); ?>" title="Ver Shortcodes">
                                <span class="dashicons dashicons-admin-links"></span>
                            </button>
                            
                            <button type="button" class="button button-small crea-icon-btn crea-open-delete" style="color: var(--crea-danger); border-color: var(--crea-danger);" 
                                data-id="<?php echo $base['id']; ?>" data-slug="<?php echo esc_attr($base['form_slug']); ?>" data-cols="<?php echo intval($count_cols); ?>" data-rows="<?php echo intval($count_rows); ?>" data-size="0 KB" title="Eliminar Base">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; else : echo '<tr class="crea-empty-row"><td colspan="6" style="text-align:center; padding: 30px; opacity: 0.7;"><span class="dashicons dashicons-database" style="font-size: 40px; width: 40px; height: 40px; margin-bottom: 10px; opacity: 0.5;"></span><br>Aún no has creado ninguna base de datos.</td></tr>'; endif; ?>
        </tbody>
    </table>

    <div class="crea-pagination" style="padding: 15px; background: transparent; border-top: 1px solid var(--crea-th-bg); display: flex; justify-content: space-between; align-items: center;">
        <div id="crea-bases-table-count" style="font-size: 13px; opacity: 0.8;"></div>
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
                    <td><textarea name="edit_source" id="edit_source" rows="3" style="width:100%;"></textarea></td>
                </tr>
                <tr>
                    <th>Comentarios Internos</th>
                    <td><textarea name="edit_comments" id="edit_comments" rows="3" style="width:100%;"></textarea></td>
                </tr>
                <tr>
                    <th>Auditoría de Registros</th>
                    <td>
                        <select name="edit_audit_records" id="edit_audit_records" style="width: 100%;">
                            <option value="1">Sí, registrar cambios en datos capturados</option>
                            <option value="0">No, solo auditar la estructura de la base</option>
                        </select>
                    </td>
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

<div id="crea-delete-step1" class="crea-modal-overlay crea-modal-alert">
    <div class="crea-modal-content" style="text-align: center;">
        <span class="dashicons dashicons-warning" style="font-size: 50px; width: 50px; height: 50px;"></span>
        <h2>Advertencia de Eliminación</h2>
        <p style="font-size: 16px;">Estás a punto de borrar la base de datos con todo su conjunto de datos.</p>
        <div style="background: transparent; border: 1px dashed currentColor; opacity: 0.9; padding: 15px; border-radius: 4px; display: inline-block; margin-bottom: 20px;">
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
        <span class="dashicons dashicons-dismiss" style="font-size: 50px; width: 50px; height: 50px;"></span>
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
                <input type="submit" id="crea-submit-delete-btn" class="button button-large" style="background: var(--crea-danger); color: #fff; border-color: var(--crea-danger);" value="Aceptar" disabled>
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
        <h4>1. Perfil Administrador (Todo incluido)</h4>
        <div class="crea-shortcode-box"><code id="sc-all"></code><button class="button button-secondary crea-copy-btn" data-target="sc-all"><span class="dashicons dashicons-clipboard"></span> Copiar</button></div>
        
        <h4>2. Perfil Editor de Datos (Ver y Corregir)</h4>
        <div class="crea-shortcode-box"><code id="sc-edit"></code><button class="button button-secondary crea-copy-btn" data-target="sc-edit"><span class="dashicons dashicons-clipboard"></span> Copiar</button></div>
        
        <h4>3. Perfil Capturista (Solo añadir registros)</h4>
        <div class="crea-shortcode-box"><code id="sc-add"></code><button class="button button-secondary crea-copy-btn" data-target="sc-add"><span class="dashicons dashicons-clipboard"></span> Copiar</button></div>
        
        <h4>4. Perfil Analista (Solo lectura pública)</h4>
        <div class="crea-shortcode-box"><code id="sc-view"></code><button class="button button-secondary crea-copy-btn" data-target="sc-view"><span class="dashicons dashicons-clipboard"></span> Copiar</button></div>
    </div>
</div>

<div id="crea-slug-error-modal" class="crea-modal-overlay crea-modal-warning">
    <div class="crea-modal-content" style="text-align: center;">
        <span class="dashicons dashicons-warning" style="font-size: 50px; width: 50px; height: 50px;"></span>
        <h2>Identificador Duplicado</h2>
        <p style="font-size: 16px;">El Identificador Interno <strong id="crea-duplicate-slug-name"></strong> ya existe en el sistema.</p>
        <p>Por favor, elige un identificador diferente para evitar conflictos en la base de datos.</p>
        <div style="margin-top: 25px;">
            <button type="button" class="button button-large button-primary crea-cancel-modal">Entendido</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.crea-open-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            var auditVal = this.dataset.audit !== undefined ? this.dataset.audit : 1;
            document.getElementById('edit_audit_records').value = auditVal;
        });
    });
});
</script>