<?php
/**
 * Archivo: wp-content/plugins/crea/includes/shortcodes/views/view-form.php
 * Descripción: Interfaz de captura de registros.
 */
if ( ! defined( 'WPINC' ) ) { die; }

$default_front_colors = [
    'primary' => '#2563EB',
];
$front_colors = wp_parse_args( get_option( 'crea_front_colors', [] ), $default_front_colors );
?>

<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Montserrat:wght@400;700&family=Open+Sans&family=Lato&family=Poppins&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jodit/3.24.2/jodit.min.css"/>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jodit/3.24.2/jodit.min.js"></script>

<style>
    #crea-form-wrapper-<?php echo $base_id; ?> {
        --crea-front-primary: <?php echo esc_attr($front_colors['primary']); ?>;
    }

    /* Estructura del contenedor principal */
    .crea-frontend-wrapper {
        background: transparent;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        margin-bottom: 30px;
        position: relative;
    }

    /* Implementación de API Fullscreen */
    .crea-frontend-wrapper:fullscreen {
        background: #fff; width: 100vw; height: 100vh; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; border: none; border-radius: 0;
    }
    .crea-frontend-wrapper:-webkit-full-screen { background: #fff; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; border: none; border-radius: 0; }

    /* Cabecera de la interfaz */
    .crea-frontend-header {
        padding: 20px 25px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .crea-frontend-header-text h2 { margin: 0 0 5px 0; }
    .crea-frontend-header-text p { margin: 0; opacity: 0.8; }

    .crea-header-actions { display: flex; gap: 10px; align-items: center; }
    .crea-btn-fs { 
        background: transparent; border: none; cursor: pointer; color: #64748b; 
        width: 36px; height: 36px; border-radius: 4px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; 
    }
    .crea-btn-fs:hover { color: var(--crea-front-primary); background: #f1f5f9; }

    /* Notificaciones de sistema */
    .crea-success-banner {
        background-color: #dcfce7; border-left: 4px solid #22c55e; color: #166534; padding: 15px 20px; margin: 25px 25px 0 25px; border-radius: 4px; font-size: 0.95em;
    }

    /* Disposición de campos del formulario */
    .crea-form-body { padding: 25px; flex: 1; }
    .crea-form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
    .crea-col-full { grid-column: 1 / -1; }
    
    .crea-field-group { display: flex; flex-direction: column; }
    .crea-label { display: block; font-weight: 600; margin-bottom: 8px; opacity: 0.9; cursor: pointer; }
    .crea-req { color: #e11d48; margin-left: 4px; font-weight: bold; }
    
    .crea-field-group input[type="text"], .crea-field-group input[type="number"], 
    .crea-field-group input[type="date"], .crea-field-group input[type="time"], 
    .crea-field-group select, .crea-field-group textarea {
        width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 4px; outline: none;
        background: #fff; transition: border-color 0.2s; box-sizing: border-box; font-family: inherit; font-size: inherit;
    }
    .crea-field-group input:focus, .crea-field-group select:focus, .crea-field-group textarea:focus { border-color: var(--crea-front-primary); }

    /* Componentes de selección tradicionales */
    .crea-radio-group, .crea-checkbox-group { display: flex; flex-direction: column; gap: 8px; margin-top: 4px; }
    .crea-option-label { display: flex; align-items: center; gap: 8px; font-weight: normal; margin: 0; cursor: pointer; opacity: 0.9; }
    #crea-form-wrapper-<?php echo $base_id; ?> .crea-option-label input { margin: 0; cursor: pointer; width: auto; }

    /* Normalización de Select2 mediante especificidad de ID */
    #crea-form-wrapper-<?php echo $base_id; ?> .crea-field-group .select2-container .select2-selection--single,
    #crea-form-wrapper-<?php echo $base_id; ?> .crea-field-group .select2-container .select2-selection--multiple {
        min-height: 40px; border-color: #cbd5e1; border-radius: 4px; background: #fff; display: flex; align-items: center;
    }
    #crea-form-wrapper-<?php echo $base_id; ?> .crea-field-group .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: normal; padding-left: 12px;
    }
    #crea-form-wrapper-<?php echo $base_id; ?> .crea-field-group .select2-container--default .select2-selection--single .select2-selection__arrow { height: 38px; }

    /* Estilos del editor Jodit mediante especificidad de ID */
    #crea-form-wrapper-<?php echo $base_id; ?> .jodit-container { border: 1px solid #cbd5e1; border-radius: 4px; font-family: inherit; }
    #crea-form-wrapper-<?php echo $base_id; ?> .jodit-container .jodit-toolbar__box { background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
    
    #crea-form-wrapper-<?php echo $base_id; ?> .jodit-container .jodit-toolbar-button__button {
        background-color: transparent; color: #334155; border: none; box-shadow: none; border-radius: 4px; min-height: 32px;
    }
    #crea-form-wrapper-<?php echo $base_id; ?> .jodit-container .jodit-toolbar-button__button:hover { background-color: #e2e8f0; }
    
    #crea-form-wrapper-<?php echo $base_id; ?> .jodit-container .jodit-icon {
        display: inline-block; width: 14px; height: 14px; fill: #334155;
    }
    
    #crea-form-wrapper-<?php echo $base_id; ?> .jodit-container .jodit-status-bar-link { display: none; }
    #crea-form-wrapper-<?php echo $base_id; ?> .jodit-container .jodit-workplace { background: #fff; }
    #crea-form-wrapper-<?php echo $base_id; ?> .jodit-container .jodit-wysiwyg { padding: 15px; font-size: 15px; line-height: 1.6; }

    /* Pie de formulario y acciones de guardado */
    .crea-form-footer { padding: 20px 25px; border-top: 1px solid #e2e8f0; background: transparent; text-align: right; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; }
    .crea-btn-save { background: var(--crea-front-primary); color: #fff; border: none; padding: 10px 24px; border-radius: 4px; font-weight: 600; cursor: pointer; transition: opacity 0.2s; display: inline-flex; align-items: center; gap: 8px; }
    .crea-btn-save:hover { opacity: 0.9; }

    @media (max-width: 900px) { .crea-form-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .crea-form-grid { grid-template-columns: 1fr; } }
</style>

<div class="crea-frontend-wrapper" id="crea-form-wrapper-<?php echo $base_id; ?>">
    <div class="crea-frontend-header">
        <div class="crea-frontend-header-text">
            <h2><?php echo esc_html( $form['form_name'] ); ?></h2>
            <p><?php echo esc_html( !empty($form['description']) ? $form['description'] : 'Formulario de Captura' ); ?></p>
        </div>
        <div class="crea-header-actions">
            <button type="button" class="crea-btn-fs" id="btn-fs-<?php echo $base_id; ?>" title="Pantalla Completa">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg>
            </button>
        </div>
    </div>

    <?php if ( isset($_GET['crea_msg']) && $_GET['crea_msg'] === 'success' ) : ?>
        <div class="crea-success-banner">
            <strong>Registro almacenado:</strong> La información se ha guardado correctamente en la base de datos.
        </div>
    <?php endif; ?>

    <form action="" method="POST" id="crea-form-<?php echo $base_id; ?>" class="crea-capture-form">
        <?php wp_nonce_field( 'crea_submit_record_' . $form['id'], 'crea_record_nonce' ); ?>
        <input type="hidden" name="crea_action" value="save_new_record">
        <input type="hidden" name="base_id" value="<?php echo esc_attr($form['id']); ?>">

        <div class="crea-form-body">
            <div class="crea-form-grid">
                <?php 
                foreach ( $fields as $field ) : 
                    $config = json_decode($field['config'], true) ?: [];
                    $is_req = $field['is_required'] ? 'required' : '';
                    $name_attr = 'crea_data[' . esc_attr($field['field_slug']) . ']';
                    $is_wide = in_array($field['field_type'], ['text_long', 'text_html']);
                ?>
                <div class="crea-field-group <?php echo $is_wide ? 'crea-col-full' : ''; ?>">
                    <label class="crea-label" for="<?php echo esc_attr($field['field_slug']); ?>">
                        <?php echo esc_html($field['field_name']); ?> <?php if($field['is_required']) echo '<span class="crea-req">*</span>'; ?>
                    </label>
                    
                    <?php 
                    switch ( $field['field_type'] ) {
                        
                        case 'text_short':
                            $max = isset($config['max_length']) ? intval($config['max_length']) : 255;
                            echo '<input type="text" name="'.$name_attr.'" id="'.esc_attr($field['field_slug']).'" data-type="text_short" data-max="'.$max.'" maxlength="'.$max.'" '.$is_req.'>';
                            break;

                        case 'text_long':
                            echo '<textarea name="'.$name_attr.'" id="'.esc_attr($field['field_slug']).'" rows="4" '.$is_req.'></textarea>';
                            break;

                        case 'text_html':
                            echo '<textarea name="'.$name_attr.'" id="'.esc_attr($field['field_slug']).'" class="crea-jodit-editor" '.$is_req.'></textarea>';
                            break;

                        case 'num_discrete':
                            $max_d = isset($config['digits']) ? intval($config['digits']) : 20;
                            echo '<input type="number" step="1" name="'.$name_attr.'" id="'.esc_attr($field['field_slug']).'" data-type="discrete" data-max-digits="'.$max_d.'" '.$is_req.'>';
                            break;

                        case 'num_continuous':
                            $max_i = isset($config['integers']) ? intval($config['integers']) : 20;
                            $max_d = isset($config['decimals']) ? intval($config['decimals']) : 8;
                            echo '<input type="number" step="any" name="'.$name_attr.'" id="'.esc_attr($field['field_slug']).'" data-type="continuous" data-max-int="'.$max_i.'" data-max-dec="'.$max_d.'" '.$is_req.'>';
                            break;

                        case 'date':
                            echo '<input type="date" name="'.$name_attr.'" id="'.esc_attr($field['field_slug']).'" '.$is_req.'>';
                            break;

                        case 'time':
                            echo '<input type="time" name="'.$name_attr.'" id="'.esc_attr($field['field_slug']).'" '.$is_req.'>';
                            break;

                        case 'select':
                        case 'relation':
                            echo '<select name="'.$name_attr.'" id="'.esc_attr($field['field_slug']).'" class="crea-search-select" '.$is_req.' data-placeholder="-- Buscar o Seleccionar --">';
                            echo '<option value=""></option>';
                            
                            if ($field['field_type'] === 'select') {
                                $options = isset($config['options']) ? explode("\n", $config['options']) : [];
                                $defaults = isset($config['default']) && is_array($config['default']) ? $config['default'] : [];
                                foreach($options as $opt) {
                                    $opt = trim($opt); if(empty($opt)) continue;
                                    $sel = in_array($opt, $defaults) ? 'selected' : '';
                                    echo '<option value="'.esc_attr($opt).'" '.$sel.'>'.esc_html($opt).'</option>';
                                }
                            } else {
                                if (isset($config['rel_base']) && isset($config['rel_field'])) {
                                    $rel_table = $wpdb->prefix . "crea_data_" . $config['rel_base'];
                                    if ($wpdb->get_var("SHOW TABLES LIKE '$rel_table'") === $rel_table) {
                                        $cond_sql = "";
                                        if (!empty($config['rel_cond_field']) && !empty($config['rel_cond_value'])) {
                                            $cond_sql = $wpdb->prepare(" WHERE " . sanitize_key($config['rel_cond_field']) . " = %s", $config['rel_cond_value']);
                                        }
                                        $rel_col = sanitize_key($config['rel_field']);
                                        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $rel_table LIKE '$rel_col'");
                                        if (!empty($col_exists)) {
                                            $rel_data = $wpdb->get_col("SELECT DISTINCT $rel_col FROM $rel_table" . $cond_sql . " ORDER BY $rel_col ASC");
                                            foreach($rel_data as $rd) {
                                                if($rd === null || $rd === '') continue;
                                                echo '<option value="'.esc_attr($rd).'">'.esc_html($rd).'</option>';
                                            }
                                        }
                                    }
                                }
                            }
                            echo '</select>';
                            break;

                        case 'radio':
                            $options = isset($config['options']) ? explode("\n", $config['options']) : [];
                            $defaults = isset($config['default']) && is_array($config['default']) ? $config['default'] : [];
                            echo '<div class="crea-radio-group">';
                            foreach($options as $index => $opt) {
                                $opt = trim($opt); if(empty($opt)) continue;
                                $chk = in_array($opt, $defaults) ? 'checked' : '';
                                $radio_id = esc_attr($field['field_slug']) . '_' . $index;
                                echo '<label for="'.$radio_id.'" class="crea-option-label"><input type="radio" name="'.$name_attr.'" id="'.$radio_id.'" value="'.esc_attr($opt).'" '.$is_req.' '.$chk.'> '.esc_html($opt).'</label>';
                            }
                            echo '</div>';
                            break;

                        case 'checkbox':
                            $options = isset($config['options']) ? explode("\n", $config['options']) : [];
                            $defaults = isset($config['default']) && is_array($config['default']) ? $config['default'] : [];
                            echo '<div class="crea-checkbox-group">';
                            foreach($options as $index => $opt) {
                                $opt = trim($opt); if(empty($opt)) continue;
                                $chk = in_array($opt, $defaults) ? 'checked' : '';
                                $chk_id = esc_attr($field['field_slug']) . '_' . $index;
                                echo '<label for="'.$chk_id.'" class="crea-option-label"><input type="checkbox" name="'.$name_attr.'[]" id="'.$chk_id.'" value="'.esc_attr($opt).'" '.$chk.'> '.esc_html($opt).'</label>';
                            }
                            echo '</div>';
                            break;
                    }
                    ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="crea-form-footer">
            <button type="submit" class="crea-btn-save">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                Guardar Información
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.getElementById('crea-form-wrapper-<?php echo $base_id; ?>');
    const btnFs = document.getElementById('btn-fs-<?php echo $base_id; ?>');
    const form = document.getElementById('crea-form-<?php echo $base_id; ?>');

    // Inicialización de modo pantalla completa nativo
    if (btnFs) {
        btnFs.addEventListener('click', () => {
            if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                if (wrapper.requestFullscreen) wrapper.requestFullscreen();
                else if (wrapper.webkitRequestFullscreen) wrapper.webkitRequestFullscreen();
            } else {
                if (document.exitFullscreen) document.exitFullscreen();
                else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
            }
        });
    }

    // Inicialización de componentes Select2
    if (typeof jQuery !== 'undefined') {
        jQuery('.crea-search-select').select2({ width: '100%', allowClear: true });
    }

    // Configuración e instanciación de editores Jodit
    if (typeof Jodit !== 'undefined') {
        const editors = document.querySelectorAll('.crea-jodit-editor');
        editors.forEach(textarea => {
            Jodit.make(textarea, {
                language: 'es',
                height: 350,
                toolbarAdaptive: false,
                removeButtons: ['about'],
                buttons: [
                    'source', '|',
                    'undo', 'redo', '|',
                    'bold', 'italic', 'underline', 'strikethrough', '|',
                    'font', 'fontsize', 'brush', '|',
                    'ul', 'ol', 'align', '|',
                    'outdent', 'indent', '|',
                    'table', 'link', 'image', 'video', '|',
                    'hr', 'eraser', '|',
                    'fullsize'
                ],
                controls: {
                    font: {
                        list: {
                            'Roboto': 'Roboto',
                            'Montserrat': 'Montserrat',
                            'Open Sans': 'Open Sans',
                            'Lato': 'Lato',
                            'Poppins': 'Poppins',
                            'Arial': 'Arial',
                            'Courier New': 'Courier New'
                        }
                    }
                }
            });
        });
    }

    // Lógica de validación de integridad de datos en cliente
    const inputsToValidate = form.querySelectorAll('input[data-type]');
    
    inputsToValidate.forEach(input => {
        input.addEventListener('input', function() {
            this.setCustomValidity(''); 
            
            const type = this.getAttribute('data-type');
            const val = this.value;

            if (!val) return; 

            if (type === 'discrete') {
                const maxD = parseInt(this.getAttribute('data-max-digits'));
                if (val.includes('.') || val.includes(',')) {
                    this.setCustomValidity(`Este campo solo admite números enteros.`);
                } else if (val.replace('-', '').length > maxD) {
                    this.setCustomValidity(`La parte entera tiene ${val.replace('-', '').length} dígitos. El máximo permitido es de ${maxD}.`);
                }
            } 
            else if (type === 'continuous') {
                const maxI = parseInt(this.getAttribute('data-max-int'));
                const maxD = parseInt(this.getAttribute('data-max-dec'));
                const parts = val.replace('-', '').split('.');
                
                if (parts[0] && parts[0].length > maxI) {
                    this.setCustomValidity(`La parte entera tiene ${parts[0].length} dígitos. El máximo permitido es de ${maxI}.`);
                } else if (parts[1] && parts[1].length > maxD) {
                    this.setCustomValidity(`Has ingresado ${parts[1].length} decimales. El máximo permitido es de ${maxD}.`);
                }
            }
            else if (type === 'text_short') {
                const maxL = parseInt(this.getAttribute('data-max'));
                if (val.length > maxL) {
                    this.setCustomValidity(`El límite de caracteres es ${maxL}. Has escrito ${val.length}.`);
                }
            }
        });
    });
});
</script>