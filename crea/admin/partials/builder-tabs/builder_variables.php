<?php
/**
 * Ruta del archivo: wp-content/plugins/crea/admin/partials/builder-tabs/builder_variables.php
 *
 * ☀️ Pestaña de Variables (Columnas): Diccionario de Datos Avanzado.
 */
if ( ! defined( 'WPINC' ) ) { die; }

global $wpdb;
$table_forms = $wpdb->prefix . 'crea_forms';
// La tabla de variables la usaremos en el backend más adelante: $table_fields = $wpdb->prefix . 'crea_fields';

// 1. Obtener todas las bases activas para el selector y para las relaciones
$bases = $wpdb->get_results("SELECT id, form_name, form_slug FROM $table_forms ORDER BY form_name ASC", ARRAY_A);
$selected_base_id = isset( $_GET['base_id'] ) ? intval( $_GET['base_id'] ) : 0;

$selected_base_name = '';
if ($selected_base_id > 0) {
    $base_info = $wpdb->get_row($wpdb->prepare("SELECT form_name FROM $table_forms WHERE id = %d", $selected_base_id));
    if ($base_info) $selected_base_name = $base_info->form_name;
}

// Variables simuladas para la vista (Fase UI)
$variables = [
    // array('id' => 1, 'name' => 'Edad del Paciente', 'slug' => 'edad_paciente', 'type' => 'Numérico Discreto', 'req' => 'Sí'),
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
                <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                    
                    <div style="flex: 1; min-width: 300px;">
                        <div style="margin-bottom: 15px;">
                            <label for="field_name" style="font-weight: 600; display:block; margin-bottom:5px;">Nombre de la Variable (Etiqueta) *</label>
                            <input type="text" name="field_name" id="field_name" class="regular-text" style="width: 100%;" required placeholder="Ej. Talla del Paciente (cm)">
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label for="field_slug" style="font-weight: 600; display:block; margin-bottom:5px;">Identificador Interno (Slug) *</label>
                            <input type="text" name="field_slug" id="field_slug" class="regular-text" style="width: 100%;" required placeholder="Ej. talla_paciente">
                            <span style="font-size: 11px; color: #64748b; display: block; margin-top: 4px;">Nombre de la columna en SQL (Base de datos). Solo minúsculas, números y guiones bajos (_). No uses espacios.</span>
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
                                    <option value="num_discrete">Numérico Discreto (Ej. Edad en años cumplidos: 25, 30)</option>
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
                                <span style="font-size: 11px; color: #64748b; display: block; margin-top: 4px;">Escribe una opción por línea (Presiona 'Enter' para separar).</span>
                                
                                <div style="margin-top: 15px;">
                                    <label style="font-weight: 600; font-size: 13px;">Opción(es) por defecto (Opcional):</label>
                                    <select name="categorical_default[]" id="categorical_default" style="width: 100%; margin-top: 4px;" multiple="multiple">
                                        </select>
                                </div>

                                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #cbd5e1;">
                                    <label style="font-weight: 600; font-size: 13px;">Codificación Estadística (Asignar IDs Numéricos):</label>
                                    <select name="categorical_id_type" id="categorical_id_type" style="width: 100%; margin-top: 4px;">
                                        <option value="none">No codificar (Guardar texto plano)</option>
                                        <option value="auto">Codificación Automática (1, 2, 3... correlativo)</option>
                                        <option value="manual">Codificación Manual (Asignar códigos específicos)</option>
                                    </select>
                                    <span style="font-size: 11px; color: #64748b; display: block; margin-top: 4px;">Se creará una columna SQL oculta (Ej. id_sexo) para análisis en SPSS/R.</span>
                                </div>

                                <div id="box_manual_codes" style="display: none; margin-top: 10px;">
                                    <label style="font-weight: 600; font-size: 13px; color: var(--crea-danger);">Códigos Manuales Asignados:</label>
                                    <textarea name="categorical_manual_codes" id="categorical_manual_codes" rows="3" style="width: 100%; margin-top: 4px;" placeholder="1&#10;2&#10;88&#10;99"></textarea>
                                    <span style="font-size: 11px; color: var(--crea-danger); display: block; margin-top: 4px;">Escribe el código numérico correspondiente a cada línea de arriba en el mismo orden. Usa valores como 88 o 99 para datos perdidos/exclusión.</span>
                                </div>
                            </div>

                            <div id="conf_relation" style="display: none;">
                                <label style="font-weight: 600; font-size: 13px;">1. Selecciona la Base Maestra (Catálogo):</label>
                                <select name="rel_base_slug" id="rel_base_slug" style="width: 100%; margin-top: 4px;">
                                    <option value="">-- Elige una base --</option>
                                    <?php foreach ( $bases as $b ) : if ($b['id'] == $selected_base_id) continue; /* No vincularse a sí misma */ ?>
                                        <option value="<?php echo esc_attr($b['form_slug']); ?>">
                                            <?php echo esc_html( $b['form_name'] ) . ' (' . esc_html( $b['form_slug'] ) . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <div style="margin-top: 15px;">
                                    <label style="font-weight: 600; font-size: 13px;">2. Variable a extraer (Columna a mostrar):</label>
                                    <select name="rel_field_slug" id="rel_field_slug" style="width: 100%; margin-top: 4px;">
                                        <option value="">-- Selecciona primero la base maestra --</option>
                                        </select>
                                    <span style="font-size: 11px; color: #64748b; display: block; margin-top: 4px;">Traerá dinámicamente los registros guardados en esta variable.</span>
                                </div>

                                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #cbd5e1;">
                                    <label style="font-weight: 600; font-size: 13px;">3. Condicional de Filtrado (Opcional):</label>
                                    <div style="display: flex; gap: 5px; align-items: center; margin-top: 4px;">
                                        <span style="font-size: 12px;">Mostrar SÓLO SI la variable</span>
                                        <select name="rel_cond_field" style="width: 120px; font-size: 12px;"><option value="">(Variable)</option></select>
                                        <span style="font-size: 12px;">=</span>
                                        <input type="text" name="rel_cond_value" style="width: 80px; font-size: 12px;" placeholder="Valor">
                                    </div>
                                    <span style="font-size: 11px; color: #64748b; display: block; margin-top: 4px;">Ej. Mostrar escuelas SÓLO SI id_entidad_federativa = 01.</span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div style="margin-top: 15px; border-top: 1px solid #E2E8F0; padding-top: 15px; text-align: right;">
                    <input type="submit" class="button button-primary button-large" value="Crear Variable">
                </div>
            </form>
        </div>

        <h3 style="margin-top: 30px;">Variables Configuradas en: <?php echo esc_html($selected_base_name); ?></h3>
        <table class="crea-table" style="table-layout: auto; border: 1px solid #E2E8F0;">
            <thead>
                <tr>
                    <th style="width: 7%;">ID <span class="dashicons dashicons-sort"></span></th>
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
                    <tr>
                        <td><strong>01</strong></td>
                        <td><strong>Edad del Paciente</strong></td>
                        <td><code>edad_paciente</code></td>
                        <td>Numérico Discreto</td>
                        <td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: #16A34A;"></span></td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button type="button" class="button button-small crea-icon-btn" title="Ver Configuración"><span class="dashicons dashicons-visibility"></span></button>
                                <button type="button" class="button button-small crea-icon-btn" title="Editar"><span class="dashicons dashicons-edit"></span></button>
                                <button type="button" class="button button-small crea-icon-btn" style="color: var(--crea-danger); border-color: var(--crea-danger);" title="Eliminar"><span class="dashicons dashicons-trash"></span></button>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    <?php elseif( isset($_GET['base_id']) ): ?>
        <div class="notice notice-info inline"><p>Por favor selecciona una base de datos válida para gestionar su estructura.</p></div>
    <?php endif; ?>
</div>

<div id="crea-delete-var-step1" class="crea-modal-overlay crea-modal-alert">
    <div class="crea-modal-content" style="text-align: center;">
        <span class="dashicons dashicons-warning" style="font-size: 50px; width: 50px; height: 50px;"></span>
        <h2>Advertencia de Eliminación</h2>
        <p style="font-size: 16px;">Estás a punto de borrar la variable: <strong>Edad del Paciente</strong>.</p>
        <p style="color: var(--crea-danger); font-size: 13px;">Esto destruirá permanentemente esta columna en SQL y todos los datos que los usuarios hayan capturado en ella.</p>
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
        <input type="text" id="confirm-var-delete" class="crea-input-danger regular-text" autocomplete="off" placeholder="ELIMINAR">
        <div style="margin-top: 25px;">
            <button type="button" class="button button-large crea-cancel-modal">Cancelar</button>
            <button type="button" id="btn-submit-var-delete" class="button button-large" style="background: var(--crea-danger); color: #fff; border-color: var(--crea-danger);" disabled>Aceptar</button>
        </div>
    </div>
</div>

<script>
// ☀️ LÓGICA DE LA INTERFAZ DE USUARIO (UI)
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Auto-Generador de Slug
    var fName = document.getElementById('field_name');
    var fSlug = document.getElementById('field_slug');
    if (fName && fSlug) {
        fName.addEventListener('keyup', function() {
            if (fSlug.getAttribute('data-manual') !== 'true') {
                var val = this.value.toLowerCase()
                            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
                            .replace(/[^a-z0-9]/g, '_')
                            .replace(/_+/g, '_')
                            .replace(/^_|_$/g, '');
                fSlug.value = val;
            }
        });
        fSlug.addEventListener('keyup', function() { this.setAttribute('data-manual', 'true'); });
    }

    // 2. Lógica Dinámica del Selector de Tipo de Dato
    var fType = document.getElementById('field_type');
    var wrapConfig = document.getElementById('config_wrapper');
    var confText = document.getElementById('conf_text');
    var confTime = document.getElementById('conf_time');
    var confCateg = document.getElementById('conf_categorical');
    var confRel = document.getElementById('conf_relation');

    if (fType) {
        fType.addEventListener('change', function() {
            var val = this.value;
            
            // Ocultar todo primero
            wrapConfig.style.display = 'none';
            confText.style.display = 'none';
            confTime.style.display = 'none';
            confCateg.style.display = 'none';
            confRel.style.display = 'none';

            if (!val) return;

            // Mostrar el wrapper principal
            wrapConfig.style.display = 'block';

            // Mostrar config según tipo
            if (val === 'text_short' || val === 'text_long') {
                confText.style.display = 'block';
                document.getElementById('text_max_length').value = (val === 'text_short') ? 255 : 2000;
                document.getElementById('text_max_desc').innerText = (val === 'text_short') ? "Rango sugerido: 1 a 255." : "Rango sugerido: Hasta 5000.";
            } 
            else if (val === 'time') {
                confTime.style.display = 'block';
            }
            else if (val === 'select' || val === 'radio' || val === 'checkbox') {
                confCateg.style.display = 'block';
                // Si es checkbox, el select default puede recibir múltiples; si no, solo 1.
                var defSelect = document.getElementById('categorical_default');
                if (val === 'checkbox') {
                    defSelect.setAttribute('multiple', 'multiple');
                } else {
                    defSelect.removeAttribute('multiple');
                }
                // En la UI, forzar Select2 a re-renderizar
                if (jQuery && jQuery(defSelect).hasClass("select2-hidden-accessible")) {
                    jQuery(defSelect).select2('destroy').select2({width: '100%'});
                }
            }
            else if (val === 'relation') {
                confRel.style.display = 'block';
            }
            else {
                // Tipos sin configuración extra (Numeros, Fechas, HTML)
                wrapConfig.style.display = 'none'; 
            }
        });
    }

    // 3. Lógica del Textarea a Menú Desplegable (Cachando los "Enters")
    var txtOptions = document.getElementById('categorical_options');
    var selDefault = document.getElementById('categorical_default');
    
    if (txtOptions && selDefault) {
        txtOptions.addEventListener('input', function() {
            var lines = this.value.split('\n').filter(line => line.trim() !== '');
            
            // Guardamos las selecciones actuales para no perderlas
            var currentSelected = Array.from(selDefault.selectedOptions).map(opt => opt.value);
            
            selDefault.innerHTML = ''; // Limpiamos
            
            if (lines.length === 0) {
                var opt = document.createElement('option');
                opt.value = "";
                opt.text = "-- Escribe opciones arriba --";
                selDefault.appendChild(opt);
            } else {
                lines.forEach(function(line) {
                    var opt = document.createElement('option');
                    var safeVal = line.trim();
                    opt.value = safeVal;
                    opt.text = safeVal;
                    if (currentSelected.includes(safeVal)) opt.selected = true;
                    selDefault.appendChild(opt);
                });
            }
            
            // Refrescar Select2 si está cargado
            if (jQuery && jQuery(selDefault).hasClass("select2-hidden-accessible")) {
                jQuery(selDefault).trigger('change');
            }
        });
    }

    // 4. Lógica de Codificación Manual vs Automática
    var idTypeSel = document.getElementById('categorical_id_type');
    var boxManual = document.getElementById('box_manual_codes');
    if (idTypeSel && boxManual) {
        idTypeSel.addEventListener('change', function() {
            if (this.value === 'manual') {
                boxManual.style.display = 'block';
            } else {
                boxManual.style.display = 'none';
            }
        });
    }
});
</script>