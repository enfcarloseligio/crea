<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/admin/partials/builder-tabs/builder_variables.php
 */
if ( ! defined( 'WPINC' ) ) { die; }

global $wpdb;
$table_forms = $wpdb->prefix . 'crea_forms';
$table_fields = $wpdb->prefix . 'crea_fields';

$bases = $wpdb->get_results("SELECT id, form_name, form_slug FROM $table_forms ORDER BY form_name ASC", ARRAY_A);
$selected_base_id = isset( $_GET['base_id'] ) ? intval( $_GET['base_id'] ) : 0;

$selected_base_name = '';
if ($selected_base_id > 0) {
    $base_info = $wpdb->get_row($wpdb->prepare("SELECT form_name FROM $table_forms WHERE id = %d", $selected_base_id));
    if ($base_info) $selected_base_name = $base_info->form_name;
}

// ☀️ Obtener las variables REALES de la base de datos
$variables = [];
if ($selected_base_id > 0) {
	$variables = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_fields WHERE form_id = %d ORDER BY id ASC", $selected_base_id), ARRAY_A);
}

$msg = isset($_GET['msg']) ? sanitize_text_field($_GET['msg']) : '';
if ( $msg === 'var_created' ) echo '<div class="notice notice-success is-dismissible"><p>Variable creada y columna añadida a la tabla exitosamente.</p></div>';

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
                                    <span style="color: #64748b; font-size: 12px; margin-top: 5px; display: inline-block;">Límite técnico: 65,535 caracteres.</span>
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

                            <div id="conf_time" style="display: none;">
                                <label style="font-weight: 600; font-size: 13px;">Zona Horaria por defecto:</label>
                                <select name="time_zone_default" style="width: 100%; margin-top: 4px;">
                                    <option value="local">Hora Local (Configurada en WP: <?php echo wp_timezone_string(); ?>)</option>
                                    <option value="utc">Hora Absoluta (UTC / GMT 0)</option>
                                </select>
                            </div>

                            <div id="conf_categorical" style="display: none;">
                                <label style="font-weight: 600; font-size: 13px;">Opciones Disponibles:</label>
                                <textarea name="categorical_options" id="categorical_options" rows="5" style="width: 100%; margin-top: 4px;" placeholder="Lácteos&#10;Carnes&#10;Vegetales"></textarea>
                                
                                <div style="margin-top: 15px;">
                                    <label style="font-weight: 600; font-size: 13px;">Opción(es) por defecto (Opcional):</label>
                                    <select name="categorical_default[]" id="categorical_default" style="width: 100%; margin-top: 4px;" multiple="multiple">
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

        <h3 style="margin-top: 30px;">Variables Configuradas en: <?php echo esc_html($selected_base_name); ?></h3>
        <table class="crea-table" style="table-layout: auto; border: 1px solid #E2E8F0;">
            <thead>
                <tr>
                    <th style="width: 7%;">ID</th>
                    <th style="width: 25%;">Nombre de Variable</th>
                    <th style="width: 20%;">Identificador (Slug)</th>
                    <th style="width: 20%;">Tipo de Dato</th>
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
                            <span style="font-size: 13px;">Aún no has definido ninguna variable para esta base de datos. Usa el panel superior para comenzar.</span>
                        </td>
                    </tr>
                <?php else : ?>
					<?php foreach ($variables as $v) : 
						$id_format = str_pad($v['id'], 2, "0", STR_PAD_LEFT);
						$tipo_legible = isset($human_types[$v['field_type']]) ? $human_types[$v['field_type']] : $v['field_type'];
					?>
					<tr>
                        <td><strong><?php echo $id_format; ?></strong></td>
                        <td><strong><?php echo esc_html($v['field_name']); ?></strong></td>
                        <td><code><?php echo esc_html($v['field_slug']); ?></code></td>
                        <td><?php echo esc_html($tipo_legible); ?></td>
                        <td style="text-align: center;">
							<?php if ($v['is_required']) : ?>
								<span class="dashicons dashicons-yes" style="color: #16A34A;"></span>
							<?php else: ?>
								<span class="dashicons dashicons-minus" style="opacity: 0.3;"></span>
							<?php endif; ?>
						</td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button type="button" class="button button-small crea-icon-btn" title="Ver Configuración"><span class="dashicons dashicons-visibility"></span></button>
                                <button type="button" class="button button-small crea-icon-btn" title="Editar"><span class="dashicons dashicons-edit"></span></button>
                                <button type="button" class="button button-small crea-icon-btn" style="color: var(--crea-danger); border-color: var(--crea-danger);" title="Eliminar"><span class="dashicons dashicons-trash"></span></button>
                            </div>
                        </td>
                    </tr>
					<?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php elseif( isset($_GET['base_id']) ): ?>
        <div class="notice notice-info inline"><p>Por favor selecciona una base de datos válida para gestionar su estructura.</p></div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var fName = document.getElementById('field_name');
    var fSlug = document.getElementById('field_slug');
    if (fName && fSlug) {
        fName.addEventListener('keyup', function() {
            if (fSlug.getAttribute('data-manual') !== 'true') {
                var val = this.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^a-z0-9]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
                fSlug.value = val;
            }
        });
        fSlug.addEventListener('keyup', function() { this.setAttribute('data-manual', 'true'); });
    }

    var fType = document.getElementById('field_type');
    var wrapConfig = document.getElementById('config_wrapper');
    var confText = document.getElementById('conf_text');
    var confHtml = document.getElementById('conf_html');
    var confNumDisc = document.getElementById('conf_num_discrete');
    var confNumCont = document.getElementById('conf_num_continuous');
    var confTime = document.getElementById('conf_time');
    var confCateg = document.getElementById('conf_categorical');
    var confRel = document.getElementById('conf_relation');

    if (fType) {
        fType.addEventListener('change', function() {
            var val = this.value;
            wrapConfig.style.display = 'none'; confText.style.display = 'none'; confHtml.style.display = 'none';
            confNumDisc.style.display = 'none'; confNumCont.style.display = 'none'; confTime.style.display = 'none';
            confCateg.style.display = 'none'; confRel.style.display = 'none';

            if (!val) return;
            wrapConfig.style.display = 'block';

            if (val === 'text_short' || val === 'text_long') {
                confText.style.display = 'block';
                document.getElementById('text_max_length').value = (val === 'text_short') ? 255 : 2000;
                document.getElementById('text_max_desc').innerText = (val === 'text_short') ? "Rango sugerido: 1 a 255." : "Rango sugerido: Hasta 5000.";
            } else if (val === 'text_html') { confHtml.style.display = 'block';
            } else if (val === 'num_discrete') { confNumDisc.style.display = 'block';
            } else if (val === 'num_continuous') { confNumCont.style.display = 'block';
            } else if (val === 'time') { confTime.style.display = 'block';
            } else if (val === 'select' || val === 'radio' || val === 'checkbox') {
                confCateg.style.display = 'block';
                var defSelect = document.getElementById('categorical_default');
                if (val === 'checkbox') { defSelect.setAttribute('multiple', 'multiple'); } else { defSelect.removeAttribute('multiple'); }
                if (jQuery && jQuery(defSelect).hasClass("select2-hidden-accessible")) jQuery(defSelect).select2('destroy').select2({width: '100%'});
            } else if (val === 'relation') { confRel.style.display = 'block'; }
        });
    }

    var txtOptions = document.getElementById('categorical_options');
    var selDefault = document.getElementById('categorical_default');
    if (txtOptions && selDefault) {
        txtOptions.addEventListener('input', function() {
            var lines = this.value.split('\n').filter(line => line.trim() !== '');
            var currentSelected = Array.from(selDefault.selectedOptions).map(opt => opt.value);
            
            selDefault.innerHTML = '';
            var optNone = document.createElement('option');
            optNone.value = ""; optNone.text = "-- Ninguna por defecto --";
            selDefault.appendChild(optNone);

            if (lines.length > 0) {
                lines.forEach(function(line) {
                    var opt = document.createElement('option');
                    var safeVal = line.trim();
                    opt.value = safeVal; opt.text = safeVal;
                    if (currentSelected.includes(safeVal)) opt.selected = true;
                    selDefault.appendChild(opt);
                });
            }
            if (jQuery && jQuery(selDefault).hasClass("select2-hidden-accessible")) jQuery(selDefault).trigger('change');
            validateManualCodes();
        });
    }

    var idTypeSel = document.getElementById('categorical_id_type');
    var boxManual = document.getElementById('box_manual_codes');
    var txtManualCodes = document.getElementById('categorical_manual_codes');
    var warningCodes = document.getElementById('warning_manual_codes');

    function validateManualCodes() {
        if (!idTypeSel || idTypeSel.value !== 'manual') {
            if (warningCodes) warningCodes.style.display = 'none';
            return;
        }
        var optsCount = txtOptions.value.split('\n').filter(line => line.trim() !== '').length;
        var codesCount = txtManualCodes.value.split('\n').filter(line => line.trim() !== '').length;
        if (warningCodes) warningCodes.style.display = (optsCount > 0 && optsCount !== codesCount) ? 'block' : 'none';
    }

    if (idTypeSel && boxManual) {
        idTypeSel.addEventListener('change', function() {
            boxManual.style.display = (this.value === 'manual') ? 'block' : 'none';
            validateManualCodes();
        });
    }
    if (txtManualCodes) txtManualCodes.addEventListener('input', validateManualCodes);
});
</script>