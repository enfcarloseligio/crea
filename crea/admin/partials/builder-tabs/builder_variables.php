<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/admin/partials/builder-tabs/builder_variables.php
 *
 * ☀️ Pestaña de Variables: Diccionario de Datos con "Súper Modal" de Edición Inteligente.
 */
if ( ! defined( 'WPINC' ) ) { die; }

global $wpdb;
$table_forms = $wpdb->prefix . 'crea_forms';
$table_fields = $wpdb->prefix . 'crea_fields';

$bases = $wpdb->get_results("SELECT id, form_name, form_slug FROM $table_forms ORDER BY form_name ASC", ARRAY_A);
$selected_base_id = isset( $_GET['base_id'] ) ? intval( $_GET['base_id'] ) : 0;

$selected_base_name = '';
$selected_base_slug = '';
$count_rows = 0;

if ($selected_base_id > 0) {
    $base_info = $wpdb->get_row($wpdb->prepare("SELECT form_name, form_slug FROM $table_forms WHERE id = %d", $selected_base_id));
    if ($base_info) {
        $selected_base_name = $base_info->form_name;
        $selected_base_slug = $base_info->form_slug;
        
        // ☀️ Contar registros físicos para la Regla de "Cero Registros"
        $physical_table = $wpdb->prefix . "crea_data_" . $selected_base_slug;
        if ($wpdb->get_var("SHOW TABLES LIKE '$physical_table'") === $physical_table) {
            $count_rows = intval($wpdb->get_var("SELECT COUNT(*) FROM $physical_table"));
        }
    }
}

$variables = [];
if ($selected_base_id > 0) {
    $variables = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_fields WHERE form_id = %d ORDER BY id ASC", $selected_base_id), ARRAY_A);
}

$msg = isset($_GET['msg']) ? sanitize_text_field($_GET['msg']) : '';
if ( $msg === 'var_created' ) echo '<div class="notice notice-success is-dismissible"><p>Variable creada y columna añadida a la tabla exitosamente.</p></div>';
if ( $msg === 'var_updated' ) echo '<div class="notice notice-success is-dismissible"><p>Configuración de la variable actualizada correctamente.</p></div>';
if ( $msg === 'var_deleted' ) echo '<div class="notice notice-success is-dismissible"><p>Variable eliminada. La columna SQL y sus datos han sido destruidos permanentemente.</p></div>';

$human_types = [
    'text_short' => 'Texto Corto', 'text_long' => 'Texto Largo', 'text_html' => 'Editor HTML',
    'num_discrete' => 'Numérico Discreto', 'num_continuous' => 'Numérico Continuo',
    'date' => 'Fecha', 'time' => 'Hora',
    'select' => 'Menú Desplegable', 'radio' => 'Botones de Radio', 'checkbox' => 'Casillas Múltiples',
    'relation' => 'Base Relacional'
];
?>

<div class="crea-card" style="border-top: 4px solid var(--crea-ink);">
    <div class="crea-card-header">
        <h2>Diccionario de Datos (Estructura de Variables)</h2>
    </div>
    
    <form method="get" action="admin.php" style="margin-bottom: 25px; padding: 20px; background: #F8FAFC; border-radius: 8px; border: 1px solid #E2E8F0; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
        <input type="hidden" name="page" value="crea-builder">
        <input type="hidden" name="tab" value="variables">
        
        <div style="flex: 1; min-width: 300px;">
            <label for="base_id" style="font-weight: 600; display: block; margin-bottom: 8px;">1. Selecciona la base de datos que deseas estructurar:</label>
            <select name="base_id" id="base_id" class="crea-searchable-select" onchange="this.form.submit()">
                <option value="0">-- Buscar y seleccionar una base --</option>
                <?php foreach ( $bases as $b ) : ?>
                    <option value="<?php echo $b['id']; ?>" <?php selected( $selected_base_id, $b['id'] ); ?>>
                        <?php echo esc_html( $b['form_name'] ) . ' (' . esc_html( $b['form_slug'] ) . ')'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if ( $selected_base_id > 0 ) : ?>
        
        <div style="background: #fff; border: 1px solid var(--crea-primary); border-radius: 8px; padding: 20px; margin-bottom: 25px;">
            <h3 style="margin-top: 0; color: var(--crea-primary); border-bottom: 1px solid #E2E8F0; padding-bottom: 10px;">
                Añadir Nueva Variable a: <strong><?php echo esc_html($selected_base_name); ?></strong>
            </h3>
            
            <form method="post" action="" id="crea-new-variable-form">
                <?php wp_nonce_field( 'crea_save_variable_action', 'crea_save_variable_nonce' ); ?>
                <input type="hidden" name="create_variable" value="1">
                <input type="hidden" name="base_id" value="<?php echo esc_attr($selected_base_id); ?>">

                <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px;">
                        <div style="margin-bottom: 15px;">
                            <label for="field_name" style="font-weight: 600; display:block; margin-bottom:5px;">Nombre de la Variable (Etiqueta) *</label>
                            <input type="text" name="field_name" id="field_name" class="regular-text" style="width: 100%;" required placeholder="Ej. Talla del Paciente (cm)">
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label for="field_slug" style="font-weight: 600; display:block; margin-bottom:5px;">Identificador Interno (Slug) *</label>
                            <input type="text" name="field_slug" id="field_slug" class="regular-text" style="width: 100%;" required placeholder="Ej. talla_paciente">
                            <span style="font-size: 11px; color: #64748b; display: block; margin-top: 4px;">Nombre de la columna en SQL. Solo minúsculas, números y guiones bajos (_). No uses espacios.</span>
                        </div>
                        
                        <div style="margin-bottom: 15px; padding: 10px; background: #F8FAFC; border-radius: 4px; border: 1px solid #E2E8F0;">
                            <label style="font-weight: 600; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="is_required" value="1" checked> 
                                Variable Obligatoria
                            </label>
                            <span style="font-size: 11px; color: #64748b; display: block; margin-top: 4px; margin-left: 24px;">El capturista no podrá guardar el registro si este campo está vacío.</span>
                        </div>
                    </div>

                    <div style="flex: 1; min-width: 350px;">
                        <div style="margin-bottom: 15px;">
                            <label for="field_type" style="font-weight: 600; display:block; margin-bottom:5px;">Tipo de Dato *</label>
                            <select name="field_type" id="field_type" style="width: 100%;" required>
                                <option value="">-- Selecciona un tipo de dato --</option>
                                <optgroup label="Datos de Texto">
                                    <option value="text_short">Texto Corto (Nombres, apellidos, folios)</option>
                                    <option value="text_long">Texto Largo (Observaciones, notas clínicas)</option>
                                    <option value="text_html">Editor HTML (Texto enriquecido con formato)</option>
                                </optgroup>
                                <optgroup label="Datos Numéricos y Temporales">
                                    <option value="num_discrete">Numérico Discreto (Ej. Edad en años: 25, 30)</option>
                                    <option value="num_continuous">Numérico Continuo (Ej. Talla: 1.75, Peso: 68.5)</option>
                                    <option value="date">Fecha (Calendario)</option>
                                    <option value="time">Hora</option>
                                </optgroup>
                                <optgroup label="Variables Categóricas (Selección)">
                                    <option value="select">Menú Desplegable (Opción Única)</option>
                                    <option value="radio">Botones de Radio (Opción Única visible)</option>
                                    <option value="checkbox">Casillas de Verificación (Opción Múltiple)</option>
                                </optgroup>
                                <optgroup label="Bases Relacionales (Catálogos)">
                                    <option value="relation">Vincular con otra Base de Datos</option>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div id="config_wrapper" style="background: #F1F5F9; padding: 15px; border-radius: 6px; border: 1px solid #cbd5e1; display: none;">
                            <h4 style="margin-top: 0; margin-bottom: 15px; color: #334155; border-bottom: 1px solid #cbd5e1; padding-bottom: 5px;">Configuración Específica</h4>
                            
                            <div id="conf_text" style="display: none;">
                                <label style="font-weight: 600; font-size: 13px;">Límite de Caracteres Máximos:</label>
                                <input type="number" name="text_max_length" id="text_max_length" class="regular-text" style="width: 100%; margin-top: 4px;" value="255">
                                <span id="text_max_desc" style="font-size: 11px; color: #64748b; display: block; margin-top: 4px;">Rango sugerido: 1 a 255.</span>
                            </div>

                            <div id="conf_html" style="display: none;">
                                <span style="font-size: 13px; color: #334155; display: block; line-height: 1.4;">
                                    <strong>Capacidad Extendida:</strong> El formato HTML requiere espacio adicional para guardar etiquetas de estilo.<br>
                                    <span style="color: #64748b; font-size: 12px; margin-top: 5px; display: inline-block;">Límite técnico: 65,535 caracteres (Aprox. 15 a 20 hojas de texto).</span>
                                </span>
                            </div>

                            <div id="conf_num_discrete" style="display: none;">
                                <label style="font-weight: 600; font-size: 13px;">Máximo de dígitos enteros permitidos:</label>
                                <input type="number" name="num_disc_digits" id="num_disc_digits" class="regular-text" style="width: 100%; margin-top: 4px;" value="2" min="1" max="11">
                            </div>

                            <div id="conf_num_continuous" style="display: none;">
                                <div style="display: flex; gap: 15px;">
                                    <div style="flex: 1;">
                                        <label style="font-weight: 600; font-size: 13px;">Dígitos enteros:</label>
                                        <input type="number" name="num_cont_integers" id="num_cont_integers" class="regular-text" style="width: 100%; margin-top: 4px;" value="2" min="1" max="11">
                                    </div>
                                    <div style="flex: 1;">
                                        <label style="font-weight: 600; font-size: 13px;">Decimales:</label>
                                        <input type="number" name="num_cont_decimals" id="num_cont_decimals" class="regular-text" style="width: 100%; margin-top: 4px;" value="2" min="0" max="6">
                                    </div>
                                </div>
                            </div>

                            <div id="conf_date" style="display: none;">
                                <span style="font-size: 13px; color: #334155; display: block; line-height: 1.4;"><strong>Calendario Estándar:</strong> El sistema utiliza el calendario Gregoriano.</span>
                            </div>

                            <div id="conf_time" style="display: none;">
                                <label style="font-weight: 600; font-size: 13px;">Zona Horaria de Visualización:</label>
                                <select name="time_zone_default" style="width: 100%; margin-top: 4px;" class="crea-searchable-select">
                                    <?php echo wp_timezone_choice( wp_timezone_string() ); ?>
                                </select>
                                <span style="font-size: 11px; color: #64748b; display: block; margin-top: 4px;">Se guardará en UTC (GMT 0) por integridad.</span>
                            </div>

                            <div id="conf_categorical" style="display: none;">
                                <label style="font-weight: 600; font-size: 13px;">Opciones Disponibles:</label>
                                <textarea name="categorical_options" id="categorical_options" rows="5" style="width: 100%; margin-top: 4px;" placeholder="Lácteos&#10;Carnes&#10;Vegetales"></textarea>
                                
                                <div style="margin-top: 15px;">
                                    <label style="font-weight: 600; font-size: 13px;">Opción(es) por defecto (Opcional):</label>
                                    <select name="categorical_default[]" id="categorical_default" style="width: 100%; margin-top: 4px;">
                                        <option value="">-- Ninguna por defecto --</option>
                                    </select>
                                </div>

                                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #cbd5e1;">
                                    <label style="font-weight: 600; font-size: 13px;">Codificación Estadística (IDs):</label>
                                    <select name="categorical_id_type" id="categorical_id_type" style="width: 100%; margin-top: 4px;">
                                        <option value="none">No codificar (Guardar texto plano)</option>
                                        <option value="auto">Codificación Automática (1, 2, 3...)</option>
                                        <option value="manual">Codificación Manual (Asignar códigos)</option>
                                    </select>
                                </div>

                                <div id="box_manual_codes" style="display: none; margin-top: 10px;">
                                    <label style="font-weight: 600; font-size: 13px; color: var(--crea-danger);">Códigos Manuales Asignados:</label>
                                    <textarea name="categorical_manual_codes" id="categorical_manual_codes" rows="3" style="width: 100%; margin-top: 4px;" placeholder="1&#10;2&#10;88&#10;99"></textarea>
                                    <div id="warning_manual_codes" style="display: none; margin-top: 10px; padding: 10px; background: #FEF2F2; border-left: 3px solid var(--crea-danger); color: var(--crea-danger); font-size: 12px;">
                                        <strong>⚠️ Precaución:</strong> Desfase detectado. La cantidad de opciones no coincide con la cantidad de códigos.
                                    </div>
                                </div>
                            </div>

                            <div id="conf_relation" style="display: none;">
                                <label style="font-weight: 600; font-size: 13px;">1. Selecciona la Base Maestra:</label>
                                <select name="rel_base_slug" id="rel_base_slug" style="width: 100%; margin-top: 4px;">
                                    <option value="">-- Elige una base --</option>
                                    <?php foreach ( $bases as $b ) : if ($b['id'] == $selected_base_id) continue; ?>
                                        <option value="<?php echo esc_attr($b['form_slug']); ?>"><?php echo esc_html( $b['form_name'] ) . ' (' . esc_html( $b['form_slug'] ) . ')'; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <div style="margin-top: 15px;">
                                    <label style="font-weight: 600; font-size: 13px;">2. Variable a extraer:</label>
                                    <select name="rel_field_slug" id="rel_field_slug" style="width: 100%; margin-top: 4px;"><option value="">-- Selecciona primero la base --</option></select>
                                </div>

                                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #cbd5e1;">
                                    <label style="font-weight: 600; font-size: 13px;">3. Condicional de Filtrado (Opcional):</label>
                                    <div style="display: flex; gap: 5px; align-items: center; margin-top: 4px;">
                                        <span style="font-size: 12px;">Mostrar SÓLO SI</span>
                                        <select name="rel_cond_field" style="width: 120px; font-size: 12px;"><option value="">(Variable)</option></select>
                                        <span style="font-size: 12px;">=</span>
                                        <input type="text" name="rel_cond_value" style="width: 80px; font-size: 12px;" placeholder="Valor">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div style="margin-top: 15px; border-top: 1px solid #E2E8F0; padding-top: 15px; text-align: right;">
                    <input type="submit" class="button button-primary button-large" value="Guardar Variable">
                </div>
            </form>
        </div>

        <div class="crea-card" style="padding: 0; overflow: hidden; border: 1px solid #E2E8F0; border-top: none; border-radius: 8px;">
            <div class="crea-toolbar" style="padding: 15px; border-bottom: 1px solid var(--crea-th-bg); background: transparent; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div class="crea-table-controls">
                    <label style="font-size: 13px;">Mostrar 
                        <select id="crea-vars-per-page" style="margin: 0 5px; font-size: 13px;">
                            <option value="25">25</option><option value="50">50</option><option value="100">100</option><option value="all">Todos</option>
                        </select> registros
                    </label>
                </div>
                <div class="crea-pagination"><div id="crea-vars-table-pagination-top" style="display: flex; gap: 5px;"></div></div>
                <div class="crea-search-box" style="position: relative; width: 100%; max-width: 300px;">
                    <span class="dashicons dashicons-search" style="position: absolute; left: 10px; top: 6px; color: inherit; opacity: 0.5;"></span>
                    <input type="text" id="crea-search-vars" placeholder="Buscar variable..." style="width: 100%; padding: 4px 8px 4px 35px;">
                </div>
            </div>

            <table id="crea-vars-table" class="crea-table" style="table-layout: auto; width: 100%; border: none;">
                <thead>
                    <tr>
                        <th style="width: 7%;" class="crea-sortable" data-sort-type="number">ID <span class="dashicons dashicons-sort"></span></th>
                        <th style="width: 25%;" class="crea-sortable" data-sort-type="string">Nombre (Etiqueta) <span class="dashicons dashicons-sort"></span></th>
                        <th style="width: 20%;" class="crea-sortable" data-sort-type="string">Slug SQL <span class="dashicons dashicons-sort"></span></th>
                        <th style="width: 20%;" class="crea-sortable" data-sort-type="string">Tipo de Dato <span class="dashicons dashicons-sort"></span></th>
                        <th style="width: 10%; text-align: center;">Obligatorio</th>
                        <th style="width: 18%;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty($variables) ) : ?>
                        <tr class="crea-empty-row">
                            <td colspan="6" style="text-align:center; padding: 40px; opacity: 0.7;">
                                <span class="dashicons dashicons-layout" style="font-size: 40px; width: 40px; height: 40px; margin-bottom: 10px; opacity: 0.5;"></span><br>
                                <span style="font-size: 16px;">Estructura Limpia</span><br>
                                <span style="font-size: 13px;">Aún no has definido ninguna variable.</span>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($variables as $v) : 
                            $id_format = str_pad($v['id'], 2, "0", STR_PAD_LEFT);
                            $tipo_legible = isset($human_types[$v['field_type']]) ? $human_types[$v['field_type']] : $v['field_type'];
                            $safe_config = esc_attr(wp_json_encode($v)); 
                        ?>
                        <tr class="crea-data-row">
                            <td data-label="ID" data-sort-val="<?php echo $v['id']; ?>"><strong><?php echo $id_format; ?></strong></td>
                            <td data-label="Nombre" data-sort-val="<?php echo esc_attr($v['field_name']); ?>"><strong><?php echo esc_html($v['field_name']); ?></strong></td>
                            <td data-label="Slug" data-sort-val="<?php echo esc_attr($v['field_slug']); ?>"><code><?php echo esc_html($v['field_slug']); ?></code></td>
                            <td data-label="Tipo" data-sort-val="<?php echo esc_attr($tipo_legible); ?>"><?php echo esc_html($tipo_legible); ?></td>
                            <td data-label="Obligatorio" style="text-align: center;">
                                <?php if ($v['is_required']) : ?>
                                    <span class="dashicons dashicons-yes" style="color: #16A34A;"></span>
                                <?php else: ?>
                                    <span class="dashicons dashicons-minus" style="opacity: 0.3;"></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Acciones">
                                <div style="display: flex; gap: 5px;">
                                    <button type="button" class="button button-small crea-icon-btn crea-open-view-var" data-config="<?php echo $safe_config; ?>" title="Ver Configuración"><span class="dashicons dashicons-visibility"></span></button>
                                    
                                    <button type="button" class="button button-small crea-icon-btn crea-open-edit-var" 
                                        data-config="<?php echo $safe_config; ?>" 
                                        data-records="<?php echo $count_rows; ?>" 
                                        title="Editar Configuración"><span class="dashicons dashicons-edit"></span></button>
                                        
                                    <button type="button" class="button button-small crea-icon-btn crea-open-delete-var" style="color: var(--crea-danger); border-color: var(--crea-danger);" data-id="<?php echo $v['id']; ?>" data-name="<?php echo esc_attr($v['field_name']); ?>" title="Eliminar Variable"><span class="dashicons dashicons-trash"></span></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="crea-pagination" style="padding: 15px; background: transparent; border-top: 1px solid var(--crea-th-bg); display: flex; justify-content: space-between; align-items: center;">
                <div id="crea-vars-table-count" style="font-size: 13px; opacity: 0.8;"></div>
                <div id="crea-vars-table-pagination-bottom" style="display: flex; gap: 5px;"></div>
            </div>
        </div>

    <?php elseif( isset($_GET['base_id']) ): ?>
        <div class="notice notice-info inline"><p>Por favor selecciona una base de datos válida para gestionar su estructura.</p></div>
    <?php endif; ?>
</div>

<div id="crea-view-var-modal" class="crea-modal-overlay">
    <div class="crea-modal-content">
        <span class="dashicons dashicons-no-alt crea-modal-close"></span>
        <h2 style="margin-top:0;">Configuración de Variable</h2>
        <div id="view-var-content" style="background: #F8FAFC; padding: 15px; border-radius: 6px; border: 1px solid #E2E8F0;"></div>
        <div style="margin-top: 15px; text-align: right;">
            <button type="button" class="button crea-cancel-modal">Cerrar</button>
        </div>
    </div>
</div>

<div id="crea-edit-var-modal" class="crea-modal-overlay" style="z-index: 99999;">
    <div class="crea-modal-content" style="max-width: 800px; width: 90%;">
        <span class="dashicons dashicons-no-alt crea-modal-close"></span>
        <h2 style="margin-top:0; border-bottom: 1px solid #E2E8F0; padding-bottom: 10px;">Editar Variable</h2>
        
        <div id="edit-var-warning-banner" style="background: #FFFBEB; border-left: 4px solid var(--crea-warning); padding: 10px 15px; margin-bottom: 15px; font-size: 13px; display: none;">
            <strong>Base con Registros:</strong> Esta base ya contiene datos capturados. Por seguridad, el "Slug SQL" y el "Tipo de Dato" están bloqueados para evitar corrupción de datos. Puedes modificar toda la demás configuración.
        </div>

        <form method="post" action="" id="crea-edit-variable-form">
            <input type="hidden" name="edit_var_id" id="edit_var_id">
            <input type="hidden" name="base_id" value="<?php echo esc_attr($selected_base_id); ?>">
            <input type="hidden" name="edit_field_slug_hidden" id="edit_field_slug_hidden">
            <input type="hidden" name="edit_field_type_hidden" id="edit_field_type_hidden">

            <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 300px;">
                    <div style="margin-bottom: 15px;">
                        <label for="edit_field_name" style="font-weight: 600; display:block; margin-bottom:5px;">Nombre de la Variable (Etiqueta) *</label>
                        <input type="text" name="edit_field_name" id="edit_field_name" class="regular-text" style="width: 100%;" required>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label for="edit_field_slug" style="font-weight: 600; display:block; margin-bottom:5px;">Identificador Interno (Slug) *</label>
                        <input type="text" name="edit_field_slug" id="edit_field_slug" class="regular-text" style="width: 100%;" required>
                    </div>
                    
                    <div style="margin-bottom: 15px; padding: 10px; background: #F8FAFC; border-radius: 4px; border: 1px solid #E2E8F0;">
                        <label style="font-weight: 600; display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="edit_is_required" id="edit_is_required" value="1"> 
                            Variable Obligatoria
                        </label>
                    </div>
                </div>

                <div style="flex: 1; min-width: 350px;">
                    <div style="margin-bottom: 15px;">
                        <label for="edit_field_type" style="font-weight: 600; display:block; margin-bottom:5px;">Tipo de Dato *</label>
                        <select name="edit_field_type" id="edit_field_type" style="width: 100%;" required>
                            <option value="">-- Selecciona un tipo de dato --</option>
                            <optgroup label="Datos de Texto">
                                <option value="text_short">Texto Corto</option>
                                <option value="text_long">Texto Largo</option>
                                <option value="text_html">Editor HTML</option>
                            </optgroup>
                            <optgroup label="Datos Numéricos y Temporales">
                                <option value="num_discrete">Numérico Discreto</option>
                                <option value="num_continuous">Numérico Continuo</option>
                                <option value="date">Fecha (Calendario)</option>
                                <option value="time">Hora</option>
                            </optgroup>
                            <optgroup label="Variables Categóricas (Selección)">
                                <option value="select">Menú Desplegable</option>
                                <option value="radio">Botones de Radio</option>
                                <option value="checkbox">Casillas de Verificación</option>
                            </optgroup>
                            <optgroup label="Bases Relacionales (Catálogos)">
                                <option value="relation">Vincular con otra Base de Datos</option>
                            </optgroup>
                        </select>
                    </div>
                    
                    <div id="edit_config_wrapper" style="background: #F1F5F9; padding: 15px; border-radius: 6px; border: 1px solid #cbd5e1; display: none;">
                        <h4 style="margin-top: 0; margin-bottom: 15px; color: #334155; border-bottom: 1px solid #cbd5e1; padding-bottom: 5px;">Configuración Específica</h4>
                        
                        <div id="edit_conf_text" style="display: none;">
                            <label style="font-weight: 600; font-size: 13px;">Límite de Caracteres Máximos:</label>
                            <input type="number" name="edit_text_max_length" id="edit_text_max_length" class="regular-text" style="width: 100%; margin-top: 4px;">
                        </div>

                        <div id="edit_conf_html" style="display: none;">
                            <span style="font-size: 13px; color: #334155;">Capacidad Extendida activa (hasta 65,535 caracteres).</span>
                        </div>

                        <div id="edit_conf_num_discrete" style="display: none;">
                            <label style="font-weight: 600; font-size: 13px;">Máximo de dígitos enteros:</label>
                            <input type="number" name="edit_num_disc_digits" id="edit_num_disc_digits" class="regular-text" style="width: 100%; margin-top: 4px;" min="1" max="11">
                        </div>

                        <div id="edit_conf_num_continuous" style="display: none;">
                            <div style="display: flex; gap: 15px;">
                                <div style="flex: 1;"><label style="font-size: 13px;">Enteros:</label><input type="number" name="edit_num_cont_integers" id="edit_num_cont_integers" style="width: 100%;"></div>
                                <div style="flex: 1;"><label style="font-size: 13px;">Decimales:</label><input type="number" name="edit_num_cont_decimals" id="edit_num_cont_decimals" style="width: 100%;"></div>
                            </div>
                        </div>

                        <div id="edit_conf_date" style="display: none;"><span style="font-size: 13px;">Calendario Estándar activo.</span></div>

                        <div id="edit_conf_time" style="display: none;">
                            <label style="font-weight: 600; font-size: 13px;">Zona Horaria de Visualización:</label>
                            <select name="edit_time_zone_default" id="edit_time_zone_default" style="width: 100%; margin-top: 4px;">
                                <?php echo wp_timezone_choice( wp_timezone_string() ); ?>
                            </select>
                        </div>

                        <div id="edit_conf_categorical" style="display: none;">
                            <label style="font-weight: 600; font-size: 13px;">Opciones Disponibles:</label>
                            <textarea name="edit_categorical_options" id="edit_categorical_options" rows="5" style="width: 100%; margin-top: 4px;"></textarea>
                            
                            <div style="margin-top: 15px;">
                                <label style="font-weight: 600; font-size: 13px;">Opción por defecto:</label>
                                <select name="edit_categorical_default[]" id="edit_categorical_default" style="width: 100%; margin-top: 4px;"></select>
                            </div>

                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #cbd5e1;">
                                <label style="font-weight: 600; font-size: 13px;">Codificación Estadística (IDs):</label>
                                <select name="edit_categorical_id_type" id="edit_categorical_id_type" style="width: 100%; margin-top: 4px;">
                                    <option value="none">No codificar</option>
                                    <option value="auto">Codificación Automática</option>
                                    <option value="manual">Codificación Manual</option>
                                </select>
                            </div>

                            <div id="edit_box_manual_codes" style="display: none; margin-top: 10px;">
                                <label style="font-weight: 600; font-size: 13px; color: var(--crea-danger);">Códigos Manuales:</label>
                                <textarea name="edit_categorical_manual_codes" id="edit_categorical_manual_codes" rows="3" style="width: 100%; margin-top: 4px;"></textarea>
                            </div>
                        </div>

                        <div id="edit_conf_relation" style="display: none;">
                            <label style="font-weight: 600; font-size: 13px;">1. Base Maestra:</label>
                            <select name="edit_rel_base_slug" id="edit_rel_base_slug" style="width: 100%; margin-top: 4px;">
                                <option value="">-- Elige una base --</option>
                                <?php foreach ( $bases as $b ) : if ($b['id'] == $selected_base_id) continue; ?>
                                    <option value="<?php echo esc_attr($b['form_slug']); ?>"><?php echo esc_html( $b['form_name'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            
                            <div style="margin-top: 15px;">
                                <label style="font-weight: 600; font-size: 13px;">2. Variable a extraer:</label>
                                <input type="text" name="edit_rel_field_slug" id="edit_rel_field_slug" style="width: 100%;" placeholder="Slug de la variable maestra">
                            </div>

                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #cbd5e1;">
                                <label style="font-weight: 600; font-size: 13px;">3. Condicional de Filtrado (Opcional):</label>
                                <div style="display: flex; gap: 5px; align-items: center; margin-top: 4px;">
                                    <input type="text" name="edit_rel_cond_field" id="edit_rel_cond_field" style="width: 120px; font-size: 12px;" placeholder="Variable"> = 
                                    <input type="text" name="edit_rel_cond_value" id="edit_rel_cond_value" style="width: 80px; font-size: 12px;" placeholder="Valor">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 15px; text-align: right; border-top: 1px solid #E2E8F0; padding-top: 15px;">
                <?php wp_nonce_field( 'crea_edit_var_action', 'crea_edit_var_nonce' ); ?>
                <input type="hidden" name="edit_variable_advanced" value="1">
                <button type="button" class="button crea-cancel-modal">Cancelar</button>
                <input type="submit" class="button button-primary button-large" value="Guardar Cambios y Recodificar">
            </div>
        </form>
    </div>
</div>

<div id="crea-delete-var-step1" class="crea-modal-overlay crea-modal-alert">
    <div class="crea-modal-content" style="text-align: center;">
        <span class="dashicons dashicons-warning" style="font-size: 50px; width: 50px; height: 50px;"></span>
        <h2>Advertencia de Eliminación</h2>
        <p style="font-size: 16px;">Estás a punto de borrar la variable: <strong id="del-var-name-display"></strong>.</p>
        <p style="color: var(--crea-danger); font-size: 13px;">Esto destruirá permanentemente esta columna en SQL y <strong>TODOS los datos</strong> que los usuarios hayan capturado en ella.</p>
        <div style="margin-top: 20px;">
            <button type="button" class="button button-large crea-cancel-modal">Cancelar</button>
            <button type="button" class="button button-large button-primary" id="btn-delete-var-continue">Continuar</button>
        </div>
    </div>
</div>

<div id="crea-delete-var-step2" class="crea-modal-overlay crea-modal-danger">
    <div class="crea-modal-content" style="text-align: center;">
        <span class="dashicons dashicons-dismiss" style="font-size: 50px; width: 50px; height: 50px;"></span>
        <h2>Acción Irremediable</h2>
        <p style="font-size: 16px;">Para confirmar la destrucción de esta columna, escribe <strong>ELIMINAR</strong>:</p>
        <form method="post" action="">
            <input type="hidden" name="delete_var_id" id="delete_var_id">
            <input type="hidden" name="base_id" value="<?php echo esc_attr($selected_base_id); ?>">
            <input type="text" id="confirm-var-delete" class="crea-input-danger regular-text" autocomplete="off" placeholder="ELIMINAR">
            <div style="margin-top: 25px;">
                <?php wp_nonce_field( 'crea_delete_var_action', 'crea_delete_var_nonce' ); ?>
                <input type="hidden" name="delete_variable" value="1">
                <button type="button" class="button button-large crea-cancel-modal">Cancelar</button>
                <button type="submit" id="btn-submit-var-delete" class="button button-large" style="background: var(--crea-danger); color: #fff; border-color: var(--crea-danger);" disabled>Aceptar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    if(window.CreaAdmin && typeof window.CreaAdmin.initDynamicTable === 'function') {
        window.CreaAdmin.initDynamicTable('crea-vars-table', 'crea-search-vars', 'crea-vars-per-page');
    }

    // ========== MOTOR JS DEL FORMULARIO DE CREACIÓN ========== 
    // (Omito la repetición del código de JS de creación para ahorrar espacio, asume que está intacto igual que antes)
    var fType = document.getElementById('field_type');
    var wrapConfig = document.getElementById('config_wrapper');
    if (fType) {
        fType.addEventListener('change', function() {
            var val = this.value;
            wrapConfig.style.display = 'none'; 
            document.getElementById('conf_text').style.display = 'none'; 
            document.getElementById('conf_html').style.display = 'none';
            document.getElementById('conf_num_discrete').style.display = 'none'; 
            document.getElementById('conf_num_continuous').style.display = 'none'; 
            document.getElementById('conf_time').style.display = 'none';
            document.getElementById('conf_date').style.display = 'none'; 
            document.getElementById('conf_categorical').style.display = 'none'; 
            document.getElementById('conf_relation').style.display = 'none';

            if (!val) return;
            wrapConfig.style.display = 'block';

            if (val === 'text_short' || val === 'text_long') { document.getElementById('conf_text').style.display = 'block'; } 
            else if (val === 'text_html') { document.getElementById('conf_html').style.display = 'block'; } 
            else if (val === 'num_discrete') { document.getElementById('conf_num_discrete').style.display = 'block'; } 
            else if (val === 'num_continuous') { document.getElementById('conf_num_continuous').style.display = 'block'; } 
            else if (val === 'date') { document.getElementById('conf_date').style.display = 'block'; } 
            else if (val === 'time') { document.getElementById('conf_time').style.display = 'block'; } 
            else if (val === 'select' || val === 'radio' || val === 'checkbox') {
                document.getElementById('conf_categorical').style.display = 'block';
                var defSelect = document.getElementById('categorical_default');
                if (val === 'checkbox') { defSelect.setAttribute('multiple', 'multiple'); } else { defSelect.removeAttribute('multiple'); }
            } 
            else if (val === 'relation') { document.getElementById('conf_relation').style.display = 'block'; }
        });
    }

    var txtOptions = document.getElementById('categorical_options');
    var selDefault = document.getElementById('categorical_default');
    if (txtOptions && selDefault) {
        txtOptions.addEventListener('input', function() {
            var lines = this.value.split('\n').filter(line => line.trim() !== '');
            var isMultiple = selDefault.hasAttribute('multiple');
            var currentSelected = isMultiple ? Array.from(selDefault.selectedOptions).map(opt => opt.value) : [selDefault.value];
            
            selDefault.innerHTML = '<option value="">-- Ninguna por defecto --</option>';
            if (lines.length > 0) {
                lines.forEach(function(line) {
                    var opt = document.createElement('option');
                    var safeVal = line.trim();
                    opt.value = safeVal; opt.text = safeVal;
                    if (currentSelected.includes(safeVal)) opt.selected = true;
                    selDefault.appendChild(opt);
                });
            }
        });
    }

    var idTypeSel = document.getElementById('categorical_id_type');
    var boxManual = document.getElementById('box_manual_codes');
    if (idTypeSel && boxManual) {
        idTypeSel.addEventListener('change', function() {
            boxManual.style.display = (this.value === 'manual') ? 'block' : 'none';
        });
    }

    // ========== MOTOR JS DEL SÚPER MODAL DE EDICIÓN ========== 

    // Lógica para mostrar las configuraciones correctas en el modal
    function applyEditTypeChange(val) {
        document.getElementById('edit_config_wrapper').style.display = 'none';
        ['edit_conf_text', 'edit_conf_html', 'edit_conf_num_discrete', 'edit_conf_num_continuous', 'edit_conf_date', 'edit_conf_time', 'edit_conf_categorical', 'edit_conf_relation'].forEach(id => document.getElementById(id).style.display = 'none');
        
        if (!val) return;
        document.getElementById('edit_config_wrapper').style.display = 'block';

        if (val === 'text_short' || val === 'text_long') document.getElementById('edit_conf_text').style.display = 'block';
        else if (val === 'text_html') document.getElementById('edit_conf_html').style.display = 'block';
        else if (val === 'num_discrete') document.getElementById('edit_conf_num_discrete').style.display = 'block';
        else if (val === 'num_continuous') document.getElementById('edit_conf_num_continuous').style.display = 'block';
        else if (val === 'date') document.getElementById('edit_conf_date').style.display = 'block';
        else if (val === 'time') document.getElementById('edit_conf_time').style.display = 'block';
        else if (val === 'select' || val === 'radio' || val === 'checkbox') {
            document.getElementById('edit_conf_categorical').style.display = 'block';
            var defSelect = document.getElementById('edit_categorical_default');
            if (val === 'checkbox') defSelect.setAttribute('multiple', 'multiple'); 
            else defSelect.removeAttribute('multiple');
        }
        else if (val === 'relation') document.getElementById('edit_conf_relation').style.display = 'block';
    }

    document.getElementById('edit_field_type').addEventListener('change', function() { applyEditTypeChange(this.value); });
    document.getElementById('edit_categorical_options').addEventListener('input', function() {
        var lines = this.value.split('\n').filter(line => line.trim() !== '');
        var selDefault = document.getElementById('edit_categorical_default');
        var isMultiple = selDefault.hasAttribute('multiple');
        var currentSelected = isMultiple ? Array.from(selDefault.selectedOptions).map(opt => opt.value) : [selDefault.value];
        
        selDefault.innerHTML = '<option value="">-- Ninguna por defecto --</option>';
        lines.forEach(function(line) {
            var opt = document.createElement('option');
            var safeVal = line.trim();
            opt.value = safeVal; opt.text = safeVal;
            if (currentSelected.includes(safeVal)) opt.selected = true;
            selDefault.appendChild(opt);
        });
    });
    document.getElementById('edit_categorical_id_type').addEventListener('change', function() {
        document.getElementById('edit_box_manual_codes').style.display = (this.value === 'manual') ? 'block' : 'none';
    });

    // Abrir Modal de Edición y rellenar datos JSON
    document.querySelectorAll('.crea-open-edit-var').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var data = JSON.parse(this.dataset.config);
            var parsedConfig = JSON.parse(data.config);
            var records = parseInt(this.dataset.records);
            
            document.getElementById('edit_var_id').value = data.id;
            document.getElementById('edit_field_name').value = data.field_name;
            document.getElementById('edit_field_slug').value = data.field_slug;
            document.getElementById('edit_field_type').value = data.field_type;
            document.getElementById('edit_is_required').checked = (data.is_required === '1');
            
            // ☀️ Regla de Cero Registros
            var banner = document.getElementById('edit-var-warning-banner');
            if (records > 0) {
                document.getElementById('edit_field_slug').setAttribute('disabled', 'disabled');
                document.getElementById('edit_field_type').setAttribute('disabled', 'disabled');
                document.getElementById('edit_field_slug_hidden').value = data.field_slug;
                document.getElementById('edit_field_type_hidden').value = data.field_type;
                banner.style.display = 'block';
            } else {
                document.getElementById('edit_field_slug').removeAttribute('disabled');
                document.getElementById('edit_field_type').removeAttribute('disabled');
                document.getElementById('edit_field_slug_hidden').value = '';
                document.getElementById('edit_field_type_hidden').value = '';
                banner.style.display = 'none';
            }

            // Aplicar vista de configuración
            applyEditTypeChange(data.field_type);

            // Rellenar configuraciones desde el JSON
            if (parsedConfig) {
                if (parsedConfig.max_length) document.getElementById('edit_text_max_length').value = parsedConfig.max_length;
                if (parsedConfig.digits) document.getElementById('edit_num_disc_digits').value = parsedConfig.digits;
                if (parsedConfig.integers) document.getElementById('edit_num_cont_integers').value = parsedConfig.integers;
                if (parsedConfig.decimals) document.getElementById('edit_num_cont_decimals').value = parsedConfig.decimals;
                if (parsedConfig.time_zone) document.getElementById('edit_time_zone_default').value = parsedConfig.time_zone;
                
                if (parsedConfig.options) {
                    var txtOptions = document.getElementById('edit_categorical_options');
                    txtOptions.value = parsedConfig.options;
                    // Forzar trigger input para llenar el select default
                    txtOptions.dispatchEvent(new Event('input'));
                    
                    if (parsedConfig.default && parsedConfig.default.length > 0) {
                        var selDefault = document.getElementById('edit_categorical_default');
                        Array.from(selDefault.options).forEach(opt => {
                            if (parsedConfig.default.includes(opt.value)) opt.selected = true;
                        });
                    }
                    
                    if (parsedConfig.id_type) {
                        document.getElementById('edit_categorical_id_type').value = parsedConfig.id_type;
                        document.getElementById('edit_categorical_id_type').dispatchEvent(new Event('change'));
                    }
                    if (parsedConfig.manual_codes) document.getElementById('edit_categorical_manual_codes').value = parsedConfig.manual_codes;
                }

                if (parsedConfig.rel_base) document.getElementById('edit_rel_base_slug').value = parsedConfig.rel_base;
                if (parsedConfig.rel_field) document.getElementById('edit_rel_field_slug').value = parsedConfig.rel_field;
                if (parsedConfig.rel_cond_field) document.getElementById('edit_rel_cond_field').value = parsedConfig.rel_cond_field;
                if (parsedConfig.rel_cond_value) document.getElementById('edit_rel_cond_value').value = parsedConfig.rel_cond_value;
            }

            document.getElementById('crea-edit-var-modal').style.display = 'block';
        });
    });

    // Ver y Eliminar (Lógica estándar intacta)
    document.querySelectorAll('.crea-open-view-var').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var data = JSON.parse(this.dataset.config);
            var parsedConfig = JSON.parse(data.config);
            
            var html = `<p><strong>Nombre:</strong> ${data.field_name}</p>`;
            html += `<p><strong>Slug SQL:</strong> <code>${data.field_slug}</code></p>`;
            html += `<p><strong>Tipo:</strong> ${data.field_type}</p>`;
            html += `<p><strong>Requerido:</strong> ${data.is_required === '1' ? 'Sí' : 'No'}</p>`;
            html += `<hr><p><strong>Configuración Interna (JSON):</strong></p><pre style="background:#fff; padding:10px; border:1px solid #ccc; max-height:200px; overflow:auto;">${JSON.stringify(parsedConfig, null, 2)}</pre>`;
            
            document.getElementById('view-var-content').innerHTML = html;
            document.getElementById('crea-view-var-modal').style.display = 'block';
        });
    });

    var delStep1 = document.getElementById('crea-delete-var-step1');
    var delStep2 = document.getElementById('crea-delete-var-step2');
    var delInput = document.getElementById('confirm-var-delete');
    var delBtn = document.getElementById('btn-submit-var-delete');

    document.querySelectorAll('.crea-open-delete-var').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('delete_var_id').value = this.dataset.id;
            document.getElementById('del-var-name-display').innerText = this.dataset.name;
            delStep1.style.display = 'block';
        });
    });

    var btnCont = document.getElementById('btn-delete-var-continue');
    if(btnCont) {
        btnCont.addEventListener('click', function() {
            delStep1.style.display = 'none';
            delInput.value = '';
            delBtn.disabled = true;
            delStep2.style.display = 'block';
        });
    }

    if(delInput) {
        delInput.addEventListener('input', function() {
            delBtn.disabled = (this.value !== 'ELIMINAR');
        });
    }

    document.querySelectorAll('.crea-modal-close, .crea-cancel-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.crea-modal-overlay').forEach(m => m.style.display = 'none');
        });
    });
});
</script>