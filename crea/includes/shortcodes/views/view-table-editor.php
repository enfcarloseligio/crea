<?php
/**
 * Archivo: wp-content/plugins/crea/includes/shortcodes/views/view-table-editor.php
 * Descripción: Interfaz DRY que reutiliza la tabla maestra y añade el modal de edición.
 */
if ( ! defined( 'WPINC' ) ) { die; }
?>

<?php if ( isset($_GET['crea_msg']) && $_GET['crea_msg'] === 'edit_success' ) : ?>
    <style>
        .crea-success-banner { 
            background-color: #dcfce7; 
            border-left: 4px solid #22c55e; 
            color: #166534; 
            padding: 15px 20px; 
            margin-bottom: 20px; 
            font-size: 0.95em; 
            border-radius: 4px; 
            font-family: inherit;
        }
    </style>
    <div class="crea-success-banner">
        <strong>Actualización Exitosa:</strong> El registro fue corregido y guardado de forma segura en la base de datos.
    </div>
<?php endif; ?>

<?php 
$is_editor_mode = true;
include plugin_dir_path( __FILE__ ) . 'view-table.php'; 
?>

<style>
    .crea-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.7); z-index: 999999; display: none; align-items: center; justify-content: center; backdrop-filter: blur(2px); font-family: inherit; }
    .crea-modal-box { background: #fff; width: 90%; max-width: 800px; max-height: 90vh; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); display: flex; flex-direction: column; overflow: hidden; }
    .crea-modal-header { padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
    .crea-modal-header h3 { margin: 0; font-size: 1.3em; color: #0f172a; }
    .crea-btn-close { background: transparent; border: none; font-size: 24px; cursor: pointer; color: #64748b; line-height: 1; padding: 0; outline: none; transition: color 0.2s; }
    .crea-btn-close:hover { color: #e11d48; }
    
    .crea-modal-body { padding: 25px; overflow-y: auto; flex: 1; }
    
    .crea-form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px 25px; }
    .crea-col-span-full { grid-column: 1 / -1; }
    .crea-field-group { display: flex; flex-direction: column; }
    .crea-main-label { font-weight: 600; margin-bottom: 6px; font-size: 0.95em; display: flex; align-items: center; color: #334155; }
    .crea-req-mark { color: #e11d48; margin-left: 4px; font-weight: bold; }
    
    .crea-field-group input[type="text"], .crea-field-group input[type="number"], .crea-field-group input[type="date"],
    .crea-field-group input[type="time"], .crea-field-group select, .crea-field-group textarea { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 4px; outline: none; transition: border-color 0.2s; font-family: inherit; font-size: 1em; box-sizing: border-box; }
    .crea-field-group input:focus, .crea-field-group select:focus, .crea-field-group textarea:focus { border-color: var(--crea-front-primary); }
    
    .crea-cat-group { display: flex; flex-direction: column; gap: 8px; margin-top: 4px; }
    .crea-cat-group label { font-weight: normal; font-size: 0.95em; display: flex; align-items: center; gap: 8px; cursor: pointer; color: #334155; }
    .crea-cat-group input { margin: 0 !important; cursor: pointer; }

    .crea-modal-footer { padding: 20px 25px; border-top: 1px solid #e2e8f0; background: #f8fafc; text-align: right; }
    .crea-btn-save { background: var(--crea-front-primary); color: #fff; border: none; padding: 10px 24px; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 1em; transition: opacity 0.2s; }
    .crea-btn-save:hover { opacity: 0.85; }
    
    @media (max-width: 992px) { .crea-form-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .crea-form-grid { grid-template-columns: 1fr; } }
</style>

<div id="crea-modal-form-<?php echo $base_id; ?>" class="crea-modal-overlay">
    <div class="crea-modal-box">
        <div class="crea-modal-header">
            <h3>Corrección de Datos - Folio #<span id="crea-display-folio"></span></h3>
            <button type="button" class="crea-btn-close">&times;</button>
        </div>
        
        <form action="" method="POST" id="crea-form-edit-<?php echo esc_attr($form['form_slug']); ?>">
            <?php wp_nonce_field( 'crea_submit_record_' . $form['id'], 'crea_record_nonce' ); ?>
            <input type="hidden" name="crea_action" value="edit_existing_record">
            <input type="hidden" name="base_id" value="<?php echo esc_attr($form['id']); ?>">
            <input type="hidden" name="record_id" id="crea-input-record-id" value="">

            <div class="crea-modal-body">
                <div class="crea-form-grid">
                    <?php 
                    foreach ( $fields as $field ) : 
                        $config = json_decode($field['config'], true) ?: [];
                        $is_req = $field['is_required'] ? 'required' : '';
                        $req_mark = $field['is_required'] ? '<span class="crea-req-mark" title="Obligatorio">*</span>' : '';
                        $name_attr = 'crea_data[' . esc_attr($field['field_slug']) . ']';
                        
                        $wrapper_class = 'crea-field-group';
                        if ( in_array($field['field_type'], ['text_long', 'text_html']) ) $wrapper_class .= ' crea-col-span-full';
                    ?>
                    <div class="<?php echo $wrapper_class; ?>">
                        <label class="crea-main-label" for="edit_<?php echo esc_attr($field['field_slug']); ?>">
                            <?php echo esc_html($field['field_name']) . $req_mark; ?>
                        </label>
                        <?php 
                        switch ( $field['field_type'] ) {
                            case 'text_short':
                                $max = isset($config['max_length']) ? 'maxlength="'.esc_attr($config['max_length']).'"' : '';
                                echo '<input type="text" name="'.$name_attr.'" id="edit_'.esc_attr($field['field_slug']).'" '.$max.' '.$is_req.'>';
                                break;
                            case 'num_discrete':
                                echo '<input type="number" step="1" name="'.$name_attr.'" id="edit_'.esc_attr($field['field_slug']).'" '.$is_req.'>';
                                break;
                            case 'num_continuous':
                                $step = 'any';
                                if(isset($config['decimals']) && intval($config['decimals']) > 0) $step = '0.' . str_repeat('0', intval($config['decimals']) - 1) . '1';
                                echo '<input type="number" step="'.$step.'" name="'.$name_attr.'" id="edit_'.esc_attr($field['field_slug']).'" '.$is_req.'>';
                                break;
                            case 'date':
                                echo '<input type="date" name="'.$name_attr.'" id="edit_'.esc_attr($field['field_slug']).'" '.$is_req.'>';
                                break;
                            case 'time':
                                echo '<input type="time" name="'.$name_attr.'" id="edit_'.esc_attr($field['field_slug']).'" '.$is_req.'>';
                                break;
                            case 'text_long':
                            case 'text_html':
                                $rows = $field['field_type'] === 'text_html' ? '8' : '4';
                                echo '<textarea name="'.$name_attr.'" id="edit_'.esc_attr($field['field_slug']).'" rows="'.$rows.'" '.$is_req.'></textarea>';
                                break;
                            case 'select':
                            case 'relation':
                                $options = isset($config['options']) ? explode("\n", $config['options']) : [];
                                echo '<select name="'.$name_attr.'" id="edit_'.esc_attr($field['field_slug']).'" '.$is_req.'>';
                                echo '<option value="">-- Seleccionar --</option>';
                                foreach($options as $opt) {
                                    $opt = trim($opt);
                                    if(!empty($opt)) echo '<option value="'.esc_attr($opt).'">'.esc_html($opt).'</option>';
                                }
                                echo '</select>';
                                break;
                            case 'radio':
                                $options = isset($config['options']) ? explode("\n", $config['options']) : [];
                                echo '<div class="crea-cat-group">';
                                foreach($options as $index => $opt) {
                                    $opt = trim($opt);
                                    if(empty($opt)) continue;
                                    $radio_id = 'edit_' . esc_attr($field['field_slug']) . '_' . $index;
                                    echo '<label for="'.$radio_id.'"><input type="radio" name="'.$name_attr.'" id="'.$radio_id.'" value="'.esc_attr($opt).'" '.$is_req.'> '.esc_html($opt).'</label>';
                                }
                                echo '</div>';
                                break;
                            case 'checkbox':
                                $options = isset($config['options']) ? explode("\n", $config['options']) : [];
                                echo '<div class="crea-cat-group">';
                                foreach($options as $index => $opt) {
                                    $opt = trim($opt);
                                    if(empty($opt)) continue;
                                    $chk_id = 'edit_' . esc_attr($field['field_slug']) . '_' . $index;
                                    echo '<label for="'.$chk_id.'"><input type="checkbox" name="'.$name_attr.'[]" id="'.$chk_id.'" value="'.esc_attr($opt).'"> '.esc_html($opt).'</label>';
                                }
                                echo '</div>';
                                break;
                        }
                        ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="crea-modal-footer">
                <button type="submit" class="crea-btn-save">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableWrapper = document.getElementById('crea-visor-<?php echo $base_id; ?>');
    const modal = document.getElementById('crea-modal-form-<?php echo $base_id; ?>');
    if (!tableWrapper || !modal) return;

    const btnClose = modal.querySelector('.crea-btn-close');
    const formEdit = modal.querySelector('form');
    const displayFolio = document.getElementById('crea-display-folio');
    const inputRecordId = document.getElementById('crea-input-record-id');

    // Motor Inteligente de Auto-Llenado (Parsea el JSON de la fila)
    function openEditModal(recordId, rowData) {
        displayFolio.textContent = recordId;
        inputRecordId.value = recordId;
        
        for (const [key, value] of Object.entries(rowData)) {
            // Buscar los inputs correspondientes a esta variable SQL
            const inputs = formEdit.querySelectorAll(`[name="crea_data[${key}]"], [name="crea_data[${key}][]"]`);
            if (inputs.length === 0) continue;

            const type = inputs[0].type;
            
            if (type === 'radio' || type === 'checkbox') {
                // Rellenar selecciones múltiples y simples
                const arrValues = value ? String(value).split(',').map(s => s.trim()) : [];
                inputs.forEach(inp => { inp.checked = arrValues.includes(inp.value); });
            } else {
                // Rellenar inputs de texto, números, fechas y selects
                inputs[0].value = value;
            }
        }
        
        // Mostrar el modal y bloquear scroll de la página de fondo
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; 
    }

    // Funciones para cerrar modal
    btnClose.addEventListener('click', () => { 
        modal.style.display = 'none'; 
        document.body.style.overflow = 'auto'; 
    });
    
    modal.addEventListener('click', (e) => {
        if (e.target === modal) { 
            modal.style.display = 'none'; 
            document.body.style.overflow = 'auto'; 
        }
    });

    // Delegación dinámica: Intercepta el clic en el botón "Editar" sin importar paginación o filtros
    tableWrapper.addEventListener('click', function(e) {
        const btn = e.target.closest('.crea-btn-edit');
        if (btn) {
            const row = btn.closest('.crea-data-row');
            const recordId = btn.getAttribute('data-id');
            const rowData = JSON.parse(row.getAttribute('data-json'));
            openEditModal(recordId, rowData);
        }
    });
});
</script>