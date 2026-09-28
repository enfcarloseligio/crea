<?php
/**
 * Archivo: wp-content/plugins/crea/includes/shortcodes/views/view-table.php
 * Descripción: Interfaz principal para la visualización, filtrado y exportación de la matriz de datos.
 */
if ( ! defined( 'WPINC' ) ) { die; }

$tzlist = timezone_identifiers_list();
$system_tz = wp_timezone_string();

$default_front_colors = [
    'primary'     => '#2563EB', 'th_bg'       => '#F8FAFC', 'th_text'     => '#0F172A',
    'odd_bg'      => '#FFFFFF', 'odd_text'    => '#334155', 'even_bg'     => '#F8FAFC', 'even_text'   => '#334155',
];
$front_colors = wp_parse_args( get_option( 'crea_front_colors', [] ), $default_front_colors );

$is_editor_mode = isset($is_editor_mode) ? $is_editor_mode : false;
$is_logged_in = is_user_logged_in();

// Extracción de identificadores únicos para listados de filtrado
$capturistas = [];
if ( $is_logged_in ) {
    foreach ( $records as $r ) {
        $uid = $r['created_by'];
        if ( $uid && !isset($capturistas[$uid]) ) {
            $u = get_userdata($uid);
            $capturistas[$uid] = $u ? $u->display_name . ' (@' . $u->user_login . ')' : 'ID: ' . $uid;
        }
    }
}

$dynamic_filters = [];
foreach ( $fields as $f ) {
    $conf = json_decode($f['config'], true) ?: [];
    if ( isset($conf['is_filterable']) && $conf['is_filterable'] == 1 ) {
        $dynamic_filters[] = $f;
    }
}
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<style id="crea-dynamic-styles-<?php echo $base_id; ?>"></style>

<style>
    /* Asignación de variables CSS al entorno delimitado */
    #crea-visor-<?php echo $base_id; ?> {
        --crea-front-primary: <?php echo esc_attr($front_colors['primary']); ?>;
        --crea-front-th-bg: <?php echo esc_attr($front_colors['th_bg']); ?>;
        --crea-front-th-text: <?php echo esc_attr($front_colors['th_text']); ?>;
        --crea-front-tr-odd-bg: <?php echo esc_attr($front_colors['odd_bg']); ?>;
        --crea-front-tr-odd-text: <?php echo esc_attr($front_colors['odd_text']); ?>;
        --crea-front-tr-even-bg: <?php echo esc_attr($front_colors['even_bg']); ?>;
        --crea-front-tr-even-text: <?php echo esc_attr($front_colors['even_text']); ?>;
    }

    #crea-visor-<?php echo $base_id; ?>.crea-table-wrapper { 
        background: transparent; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); margin-bottom: 30px; position: relative; 
    }
    
    /* Configuración estructural de API Fullscreen */
    #crea-visor-<?php echo $base_id; ?>.crea-table-wrapper:fullscreen { 
        background: #fff; width: 100vw; height: 100vh; margin: 0; padding: 40px; border: none; border-radius: 0; display: flex; flex-direction: column; overflow-y: auto; 
    }
    #crea-visor-<?php echo $base_id; ?>.crea-table-wrapper:fullscreen .crea-table-responsive { flex: 1; max-height: none; }
    #crea-visor-<?php echo $base_id; ?>.crea-table-wrapper:-webkit-full-screen { 
        background: #fff; padding: 40px; display: flex; flex-direction: column; overflow-y: auto; border: none; border-radius: 0;
    }
    #crea-visor-<?php echo $base_id; ?>.crea-table-wrapper:-webkit-full-screen .crea-table-responsive { flex: 1; max-height: none; }

    #crea-visor-<?php echo $base_id; ?> .crea-table-header { padding: 20px 25px; border-bottom: 1px solid #e2e8f0; background: transparent; display: flex; justify-content: space-between; align-items: flex-end;}
    #crea-visor-<?php echo $base_id; ?> .crea-table-header h2 { margin: 0 0 5px 0; }
    #crea-visor-<?php echo $base_id; ?> .crea-table-header p { margin: 0; opacity: 0.8; }

    #crea-visor-<?php echo $base_id; ?> .crea-header-actions { display: flex; gap: 10px; align-items: center; }
    #crea-visor-<?php echo $base_id; ?> .crea-btn-fullscreen { background: transparent; border: none; cursor: pointer; color: #64748b; padding: 5px; transition: color 0.2s; display: flex; align-items: center; justify-content: center; }
    #crea-visor-<?php echo $base_id; ?> .crea-btn-fullscreen:hover { color: var(--crea-front-primary); }

    #crea-visor-<?php echo $base_id; ?> .crea-btn-toggle-filters { background: transparent; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-family: inherit; font-size: 13px; transition: all 0.2s; display: flex; align-items: center; gap: 5px; color: inherit; height: 32px; box-sizing: border-box; }
    #crea-visor-<?php echo $base_id; ?> .crea-btn-toggle-filters:hover { border-color: var(--crea-front-primary); color: var(--crea-front-primary); }
    
    /* Configuración de cuadrículas de filtrado */
    #crea-visor-<?php echo $base_id; ?> .crea-filters-panel { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 20px 25px; display: none; }
    #crea-visor-<?php echo $base_id; ?> .crea-filter-section { margin-bottom: 20px; }
    #crea-visor-<?php echo $base_id; ?> .crea-filter-section:last-child { margin-bottom: 0; }
    #crea-visor-<?php echo $base_id; ?> .crea-filter-title { font-size: 14px; color: #334155; margin: 0 0 12px 0; font-weight: 700; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; }
    #crea-visor-<?php echo $base_id; ?> .crea-system-filters-grid, #crea-visor-<?php echo $base_id; ?> .crea-dynamic-filters-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; align-items: end; }
    #crea-visor-<?php echo $base_id; ?> .crea-filter-col { display: flex; flex-direction: column; }
    #crea-visor-<?php echo $base_id; ?> .crea-flt-lbl { font-size: 12px; font-weight: 600; margin-bottom: 6px; color: #334155; }
    #crea-visor-<?php echo $base_id; ?> .crea-filter-col input[type="date"] { width: 100%; padding: 0 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-family: inherit; font-size: 13px; height: 30px; box-sizing: border-box; outline: none; background: #fff; }
    #crea-visor-<?php echo $base_id; ?> .crea-filter-col input[type="date"]:focus { border-color: var(--crea-front-primary); }

    /* Modificadores de estado de filtros (Switch) */
    #crea-visor-<?php echo $base_id; ?> .crea-switch-container { display: flex; align-items: center; gap: 10px; height: 30px; }
    #crea-visor-<?php echo $base_id; ?> .crea-switch { position: relative; display: inline-block; width: 36px; height: 20px; margin: 0; }
    #crea-visor-<?php echo $base_id; ?> .crea-switch input { opacity: 0; width: 0; height: 0; margin: 0; }
    #crea-visor-<?php echo $base_id; ?> .crea-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 20px; }
    #crea-visor-<?php echo $base_id; ?> .crea-slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
    #crea-visor-<?php echo $base_id; ?> .crea-switch input:checked + .crea-slider { background-color: var(--crea-front-primary); }
    #crea-visor-<?php echo $base_id; ?> .crea-switch input:checked + .crea-slider:before { transform: translateX(16px); }
    #crea-visor-<?php echo $base_id; ?> .crea-switch-lbl { font-size: 13px; color: #64748b; cursor: pointer; user-select: none; }
    #crea-visor-<?php echo $base_id; ?> .crea-switch-lbl.active { color: var(--crea-front-primary); font-weight: 600; }

    /* Especificidad estructural para control de librerías externas (Select2) */
    #crea-visor-<?php echo $base_id; ?> .select2-container .select2-selection--single { height: 30px; border-color: #cbd5e1; display: flex; align-items: center; background: #fff; border-radius: 4px; }
    #crea-visor-<?php echo $base_id; ?> .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: normal; font-size: 13px; color: #334155; padding-left: 10px; }
    #crea-visor-<?php echo $base_id; ?> .select2-container--default .select2-selection--single .select2-selection__arrow { height: 28px; }
    
    @media (max-width: 900px) {
        #crea-visor-<?php echo $base_id; ?> .crea-system-filters-grid { grid-template-columns: 1fr; gap: 15px; }
        #crea-visor-<?php echo $base_id; ?> .crea-dynamic-filters-grid { grid-template-columns: repeat(2, 1fr); gap: 15px; }
    }
    @media (max-width: 600px) {
        #crea-visor-<?php echo $base_id; ?> .crea-dynamic-filters-grid { grid-template-columns: 1fr; }
    }

    #crea-visor-<?php echo $base_id; ?> .crea-table-toolbar { padding: 15px 25px; border-bottom: 1px solid #e2e8f0; background: transparent; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
    #crea-visor-<?php echo $base_id; ?> .crea-table-controls { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
    #crea-visor-<?php echo $base_id; ?> .crea-control-item { display: flex; align-items: center; gap: 8px; font-size: 13px; }
    #crea-visor-<?php echo $base_id; ?> .crea-table-controls select, #crea-visor-<?php echo $base_id; ?> .crea-search-box input { padding: 0 10px; border: 1px solid #cbd5e1; border-radius: 4px; background: #fff; outline: none; height: 30px; box-sizing: border-box; transition: border-color 0.2s; font-family: inherit; font-size: 13px; }
    #crea-visor-<?php echo $base_id; ?> .crea-table-controls select:focus, #crea-visor-<?php echo $base_id; ?> .crea-search-box input:focus { border-color: var(--crea-front-primary); }

    #crea-visor-<?php echo $base_id; ?> .crea-export-group { display: flex; gap: 5px; align-items: center; border-left: 1px solid #e2e8f0; padding-left: 15px; margin-left: 5px; }
    #crea-visor-<?php echo $base_id; ?> .crea-btn-export { background: #f8fafc; border: 1px solid #cbd5e1; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600; color: #334155; transition: all 0.2s; display: flex; align-items: center; font-family: inherit; }
    #crea-visor-<?php echo $base_id; ?> .crea-btn-export:hover { background: #f1f5f9; border-color: var(--crea-front-primary); color: var(--crea-front-primary); }

    #crea-visor-<?php echo $base_id; ?> .crea-col-selector { position: relative; }
    #crea-visor-<?php echo $base_id; ?> .crea-col-dropdown { position: absolute; top: 100%; left: 0; background: #fff; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-radius: 4px; padding: 10px; z-index: 100; display: none; min-width: 200px; max-height: 300px; overflow-y: auto; margin-top: 5px; }
    #crea-visor-<?php echo $base_id; ?> .crea-col-dropdown label { display: flex; align-items: center; gap: 8px; font-size: 13px; padding: 5px 0; cursor: pointer; }
    #crea-visor-<?php echo $base_id; ?> .crea-col-dropdown label:hover { background: #f8fafc; }

    #crea-visor-<?php echo $base_id; ?> .crea-search-box { position: relative; width: 100%; max-width: 250px; display: flex; align-items: center; }
    #crea-visor-<?php echo $base_id; ?> .crea-search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; opacity: 0.4; pointer-events: none; }
    #crea-visor-<?php echo $base_id; ?> .crea-search-box input { width: 100%; padding-left: 32px; box-sizing: border-box; }

    /* Formato de matriz de datos general */
    #crea-visor-<?php echo $base_id; ?> .crea-table-responsive { width: 100%; overflow-x: auto; max-height: 600px; }
    #crea-visor-<?php echo $base_id; ?> .crea-frontend-table { width: 100%; border-collapse: collapse; text-align: left; white-space: nowrap; margin: 0; }
    #crea-visor-<?php echo $base_id; ?> .crea-frontend-table th, #crea-visor-<?php echo $base_id; ?> .crea-frontend-table td { padding: 12px 20px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
    #crea-visor-<?php echo $base_id; ?> .crea-frontend-table th { background-color: var(--crea-front-th-bg); color: var(--crea-front-th-text); position: sticky; top: 0; z-index: 10; cursor: pointer; user-select: none; }
    #crea-visor-<?php echo $base_id; ?> .crea-frontend-table tbody tr:nth-child(odd) { background-color: var(--crea-front-tr-odd-bg); color: var(--crea-front-tr-odd-text); }
    #crea-visor-<?php echo $base_id; ?> .crea-frontend-table tbody tr:nth-child(even) { background-color: var(--crea-front-tr-even-bg); color: var(--crea-front-tr-even-text); }
    #crea-visor-<?php echo $base_id; ?> .crea-frontend-table th .sort-icon { opacity: 0.5; margin-left: 5px; font-size: 11px; }
    #crea-visor-<?php echo $base_id; ?> .crea-frontend-table tbody tr { transition: opacity 0.1s; }
    #crea-visor-<?php echo $base_id; ?> .crea-frontend-table tbody tr:hover { opacity: 0.85; }

    #crea-visor-<?php echo $base_id; ?> .crea-frontend-table td.crea-cell-sys { font-weight: 600; opacity: 0.8; }
    #crea-visor-<?php echo $base_id; ?> .crea-frontend-table td.crea-cell-longtext { max-width: 350px; white-space: normal; word-wrap: break-word; line-height: 1.5; }
    #crea-visor-<?php echo $base_id; ?> .crea-time-sys { font-weight: 600; }
    #crea-visor-<?php echo $base_id; ?> .crea-time-user { font-size: 0.85em; opacity: 0.6; margin-top: 2px; }

    /* Modificadores de estado para columnas fijas */
    #crea-visor-<?php echo $base_id; ?> .crea-column-sticky-right {
        position: sticky; right: 0; z-index: 5; border-left: 1px solid #e2e8f0; box-shadow: -2px 0 5px rgba(0,0,0,0.02);
    }
    #crea-visor-<?php echo $base_id; ?> .crea-column-sticky-left {
        position: sticky; left: 0; z-index: 5; border-right: 1px solid #e2e8f0; box-shadow: 2px 0 5px rgba(0,0,0,0.02);
    }
    #crea-visor-<?php echo $base_id; ?> .crea-frontend-table tbody tr:nth-child(odd) td.crea-column-sticky-right,
    #crea-visor-<?php echo $base_id; ?> .crea-frontend-table tbody tr:nth-child(odd) td.crea-column-sticky-left { 
        background-color: var(--crea-front-tr-odd-bg); 
    }
    #crea-visor-<?php echo $base_id; ?> .crea-frontend-table tbody tr:nth-child(even) td.crea-column-sticky-right,
    #crea-visor-<?php echo $base_id; ?> .crea-frontend-table tbody tr:nth-child(even) td.crea-column-sticky-left { 
        background-color: var(--crea-front-tr-even-bg); 
    }
    #crea-visor-<?php echo $base_id; ?> .crea-frontend-table th.crea-column-sticky-right,
    #crea-visor-<?php echo $base_id; ?> .crea-frontend-table th.crea-column-sticky-left { z-index: 15; }

    #crea-visor-<?php echo $base_id; ?> .crea-btn-action { background: var(--crea-front-primary); color: #fff; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: opacity 0.2s; }
    #crea-visor-<?php echo $base_id; ?> .crea-btn-action:hover { opacity: 0.8; }
    #crea-visor-<?php echo $base_id; ?> .crea-btn-action svg { width: 14px; height: 14px; }
    #crea-visor-<?php echo $base_id; ?> .crea-btn-cell { background: transparent; border: 1px solid #cbd5e1; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; transition: all 0.2s; color: #334155; }
    #crea-visor-<?php echo $base_id; ?> .crea-btn-cell:hover { background: var(--crea-front-primary); color: #fff; border-color: var(--crea-front-primary); }

    #crea-visor-<?php echo $base_id; ?> .crea-table-footer { display: flex; justify-content: space-between; align-items: center; padding: 15px 25px; background: transparent; border-top: 1px solid #e2e8f0; opacity: 0.9; font-size: 13px; }
    #crea-visor-<?php echo $base_id; ?> .crea-pagination-controls { display: flex; gap: 5px; align-items: center; }
    #crea-visor-<?php echo $base_id; ?> .crea-pagination-controls button { background: transparent; border: 1px solid #cbd5e1; color: inherit; padding: 4px 10px; border-radius: 4px; cursor: pointer; height: 30px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; font-family: inherit; }
    #crea-visor-<?php echo $base_id; ?> .crea-pagination-controls button:hover:not(:disabled) { border-color: var(--crea-front-primary); color: var(--crea-front-primary); }
    #crea-visor-<?php echo $base_id; ?> .crea-pagination-controls button:disabled { opacity: 0.4; cursor: not-allowed; }
    #crea-visor-<?php echo $base_id; ?> .crea-pagination-controls button.active { background: var(--crea-front-primary); color: #fff; border-color: var(--crea-front-primary); }
    #crea-visor-<?php echo $base_id; ?> .crea-empty-state { padding: 50px; text-align: center; opacity: 0.7; }

    /* Contenedores Modales y Superposiciones */
    .crea-modal-view-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15,23,42,0.8); z-index: 9999999; display: none; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
    .crea-modal-view-box { background: #fff; border-radius: 8px; max-width: 800px; width: 90%; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
    .crea-modal-view-header { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
    .crea-modal-view-header h3 { margin: 0; font-size: 1.2em; color: #0f172a; }
    
    /* ☀️ Botón de cierre vectorial con dimensiones explícitas y alineación Flexbox */
    .crea-modal-view-close { 
        background: transparent; border: none; cursor: pointer; color: #64748b; 
        width: 32px; height: 32px; border-radius: 4px; padding: 0; 
        display: flex; align-items: center; justify-content: center; 
        transition: background-color 0.2s, color 0.2s; 
    }
    .crea-modal-view-close:hover { color: #e11d48; background-color: #f1f5f9; }
    .crea-modal-view-close svg { width: 18px; height: 18px; }

    .crea-modal-view-body { padding: 25px; overflow-y: auto; flex: 1; font-size: 14px; color: #334155; }
    
    .crea-view-grid { display: grid; grid-template-columns: 200px 1fr; gap: 15px; }
    .crea-view-label { font-weight: 700; color: #64748b; font-size: 13px; text-transform: uppercase; }
    .crea-view-value { line-height: 1.6; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9; }

    /* ☀️ Canvas de renderizado HTML con soporte completo para estilos inline, listas, tablas y medios */
    .crea-html-preview-canvas {
        line-height: 1.6;
        color: #1e293b;
    }
    .crea-html-preview-canvas ul, .crea-html-preview-canvas ol {
        margin: 1em 0;
        padding-left: 2em;
    }
    .crea-html-preview-canvas ul { list-style-type: disc; }
    .crea-html-preview-canvas ol { list-style-type: decimal; }
    .crea-html-preview-canvas strong, .crea-html-preview-canvas b { font-weight: 700; }
    .crea-html-preview-canvas em, .crea-html-preview-canvas i { font-style: italic; }
    .crea-html-preview-canvas u { text-decoration: underline; }
    .crea-html-preview-canvas table { border-collapse: collapse; width: 100%; margin: 1em 0; }
    .crea-html-preview-canvas table td, .crea-html-preview-canvas table th { border: 1px solid #cbd5e1; padding: 8px 12px; }
    .crea-html-preview-canvas iframe, .crea-html-preview-canvas video {
        max-width: 100%;
        display: block;
        margin: 1em 0;
        border-radius: 4px;
    }
</style>

<div class="crea-table-wrapper" id="crea-visor-<?php echo $base_id; ?>">
    <div class="crea-table-header">
        <div>
            <h2><?php echo esc_html( $form['form_name'] ); ?></h2>
            <p><?php echo $is_editor_mode ? 'Panel de Gestión de Registros' : 'Matriz de Datos Estructurada'; ?></p>
        </div>
        
        <div class="crea-header-actions">
            <button type="button" class="crea-btn-toggle-filters" id="btn-toggle-filters-<?php echo $base_id; ?>">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                Filtros Avanzados
            </button>
            <button type="button" class="crea-btn-fullscreen" id="btn-fullscreen-<?php echo $base_id; ?>" title="Activar Pantalla Completa">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg>
            </button>
        </div>
    </div>

    <?php if ( empty($records) ) : ?>
        <div class="crea-empty-state">
            <span style="font-size: 40px; display: block; margin-bottom: 10px; opacity: 0.3;">📭</span>
            No existen registros almacenados en esta base de datos actualmente.
        </div>
    <?php else : ?>
        
        <div class="crea-filters-panel" id="crea-filters-panel-<?php echo $base_id; ?>">
            
            <?php if ($is_logged_in) : ?>
            <div class="crea-filter-section">
                <h4 class="crea-filter-title">Filtros del sistema</h4>
                <div class="crea-system-filters-grid">
                    
                    <div class="crea-filter-col" style="grid-column: span 2;">
                        <label class="crea-flt-lbl">Fecha de Captura</label>
                        <div class="crea-switch-container">
                            <span class="crea-switch-lbl active" id="lbl-switch-month">Año/Mes</span>
                            <label class="crea-switch">
                                <input type="checkbox" id="date_filter_mode_switch">
                                <span class="crea-slider"></span>
                            </label>
                            <span class="crea-switch-lbl" id="lbl-switch-range">Rango</span>
                        </div>
                        
                        <div id="filter-date-month-group" style="display: flex; gap: 10px; margin-top: 5px;">
                            <div style="flex:1;">
                                <select id="flt-year" class="crea-filter-select2"><option value="">-- Todos los Años --</option></select>
                            </div>
                            <div style="flex:1;">
                                <select id="flt-month" class="crea-filter-select2" disabled><option value="">-- Todos los Meses --</option></select>
                            </div>
                        </div>

                        <div id="filter-date-range-group" style="display: none; gap: 10px; margin-top: 5px;">
                            <div style="flex:1; display:flex; align-items:center; gap:6px;">
                                <span style="font-size:12px; color:#64748b; font-weight:600;">Desde</span>
                                <input type="date" id="flt-start-date">
                            </div>
                            <div style="flex:1; display:flex; align-items:center; gap:6px;">
                                <span style="font-size:12px; color:#64748b; font-weight:600;">Hasta</span>
                                <input type="date" id="flt-end-date">
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($capturistas)) : ?>
                    <div class="crea-filter-col">
                        <label class="crea-flt-lbl">Capturista</label>
                        <select id="flt-user" class="crea-filter-select2">
                            <option value="">-- Todos --</option>
                            <?php foreach($capturistas as $uid => $uname): ?>
                                <option value="<?php echo esc_attr($uid); ?>"><?php echo esc_html($uname); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($dynamic_filters)) : ?>
            <div class="crea-filter-section">
                <h4 class="crea-filter-title">Filtros avanzados</h4>
                <div class="crea-dynamic-filters-grid">
                    <?php foreach($dynamic_filters as $f) : ?>
                    <div class="crea-filter-col">
                        <label class="crea-flt-lbl"><?php echo esc_html($f['field_name']); ?></label>
                        <select class="flt-dynamic crea-filter-select2" data-slug="<?php echo esc_attr($f['field_slug']); ?>">
                            <option value="">-- Todos --</option>
                        </select>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$is_logged_in && empty($dynamic_filters)) : ?>
                <div style="font-size: 13px; color: #64748b; padding: 10px 0;">No hay filtros públicos configurados para esta tabla.</div>
            <?php endif; ?>
        </div>

        <div class="crea-table-toolbar">
            <div class="crea-table-controls">
                
                <div class="crea-control-item crea-col-selector">
                    <button type="button" class="crea-btn-toggle-filters" id="btn-toggle-cols-<?php echo $base_id; ?>">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        Mostrar Columnas
                    </button>
                    <div class="crea-col-dropdown" id="crea-col-dropdown-<?php echo $base_id; ?>"></div>
                </div>

                <div class="crea-control-item">
                    <span>Mostrar</span>
                    <select class="crea-per-page">
                        <option value="25">25</option><option value="50">50</option><option value="100">100</option><option value="all">Todos</option>
                    </select>
                </div>
                
                <div class="crea-control-item">
                    <span>Mi Zona Horaria:</span>
                    <select class="crea-tz-select" style="width: 150px;">
                        <option value="UTC">UTC (Servidor)</option>
                        <?php foreach($tzlist as $tz) : ?><option value="<?php echo esc_attr($tz); ?>"><?php echo esc_html($tz); ?></option><?php endforeach; ?>
                    </select>
                    <label style="display: flex; align-items: center; gap: 4px; margin-left: 5px; cursor: pointer; opacity: 0.8; font-size: 13px;">
                        <input type="checkbox" id="crea-save-tz" style="margin:0; cursor:pointer;"> Guardar
                    </label>
                </div>

                <div class="crea-export-group">
                    <span style="font-size:12px; color:#64748b; font-weight:600;">Exportar:</span>
                    <button type="button" class="crea-btn-export" data-format="csv" title="Descargar en formato CSV">CSV</button>
                    <button type="button" class="crea-btn-export" data-format="xlsx" title="Descargar en formato Excel">XLSX</button>
                    <button type="button" class="crea-btn-export" data-format="pdf" title="Descargar en formato PDF">PDF</button>
                    <button type="button" class="crea-btn-export" data-format="json" title="Descargar datos en formato JSON">JSON</button>
                </div>

            </div>
            
            <div class="crea-search-box">
                <svg class="crea-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" class="crea-search-input" placeholder="Buscar general..." autocomplete="off" data-lpignore="true" data-form-type="other" spellcheck="false" role="presentation">
            </div>
        </div>

        <div class="crea-table-responsive">
            <table class="crea-frontend-table" id="crea-main-table-<?php echo $base_id; ?>">
                <thead>
                    <tr>
                        <?php if ($is_editor_mode) : ?>
                            <th class="crea-column-sticky-left" style="width: 80px; text-align: center;" data-always-visible="true">Acciones</th>
                        <?php endif; ?>
                        
                        <?php if ($is_logged_in) : ?>
                            <th data-sort="number" data-sys="true">ID <span class="sort-icon">↕</span></th>
                            <th data-sort="number" data-sys="true">Fecha de Captura <span class="sort-icon">↕</span></th>
                            <th data-sort="string" data-sys="true">Capturista <span class="sort-icon">↕</span></th>
                        <?php endif; ?>
                        
                        <?php 
                        foreach ( $fields as $field ) {
                            $sort_type = in_array($field['field_type'], ['num_discrete', 'num_continuous']) ? 'number' : 'string';
                            echo '<th data-sort="'.$sort_type.'" data-name="'.esc_attr($field['field_name']).'">' . esc_html( $field['field_name'] ) . ' <span class="sort-icon">↕</span></th>';
                        }
                        ?>
                        <th class="crea-column-sticky-right" style="width: 80px; text-align: center;" data-always-visible="true">Detalles</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $records as $row ) : 
                        $json_attr = ' data-json="'.esc_attr(wp_json_encode($row)).'"';
                    ?>
                    <tr class="crea-data-row"<?php echo $json_attr; ?>>
                        
                        <?php if ($is_editor_mode) : ?>
                        <td class="crea-column-sticky-left" style="text-align: center;">
                            <button type="button" class="crea-btn-action crea-btn-edit" data-id="<?php echo esc_attr($row['id']); ?>" title="Editar Registro">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                Editar
                            </button>
                        </td>
                        <?php endif; ?>

                        <?php if ($is_logged_in) : ?>
                            <td class="crea-cell-sys" data-val="<?php echo esc_attr($row['id']); ?>"><?php echo esc_html( $row['id'] ); ?></td>
                            <?php $timestamp_utc = strtotime($row['created_at']); ?>
                            <td class="crea-cell-date" data-val="<?php echo $timestamp_utc; ?>" data-timestamp="<?php echo $timestamp_utc; ?>"></td>
                            <td class="crea-cell-user crea-cell-sys" data-val="<?php echo esc_attr($row['created_by']); ?>">
                                <?php echo isset($capturistas[$row['created_by']]) ? esc_html($capturistas[$row['created_by']]) : 'Desconocido'; ?>
                            </td>
                        <?php endif; ?>
                        
                        <?php 
                        foreach ( $fields as $field ) {
                            $slug = $field['field_slug'];
                            $value = isset($row[$slug]) ? $row[$slug] : '';
                            $conf = json_decode($field['config'], true) ?: [];
                            $cell_class = in_array($field['field_type'], ['text_long', 'text_html']) ? 'crea-cell-longtext' : '';
                            
                            if ($field['field_type'] === 'time') {
                                $cell_class .= ' crea-cell-time';
                                $sys_tz = isset($conf['time_zone']) ? $conf['time_zone'] : 'UTC';
                                $sys_format = isset($conf['time_format']) ? $conf['time_format'] : '24h';
                                echo '<td class="'.trim($cell_class).'" data-slug="'.esc_attr($slug).'" data-val="'.esc_attr($value).'" data-raw="'.esc_attr($value).'" data-tz="'.esc_attr($sys_tz).'" data-format="'.esc_attr($sys_format).'"></td>';
                            } else {
                                $is_html = ($field['field_type'] === 'text_html');
                                $display_val = $value;
                                
                                if ($is_html) {
                                    $display_val = '<button type="button" class="crea-btn-cell crea-btn-view-html" data-slug="'.esc_attr($slug).'">Ver Diseño</button>';
                                } elseif (mb_strlen($value) > 255) {
                                    $display_val = mb_substr(strip_tags($value), 0, 255) . ' [...]';
                                } else {
                                    $display_val = nl2br(esc_html($value));
                                }

                                echo '<td class="'.trim($cell_class).'" data-slug="'.esc_attr($slug).'" data-val="'.esc_attr($value).'">' . $display_val . '</td>';
                            }
                        }
                        ?>

                        <td class="crea-column-sticky-right" style="text-align: center;">
                            <button type="button" class="crea-btn-cell crea-btn-view-full">Ver</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="crea-table-footer">
            <div class="crea-table-info">Mostrando 0 a 0 de 0 registros</div>
            <div class="crea-pagination"><div id="crea-pagination-bottom" class="crea-pagination-controls"></div></div>
        </div>

    <?php endif; ?>
</div>

<!-- Estructura del modal de límite de exportación masiva -->
<div id="crea-export-limit-modal-<?php echo $base_id; ?>" class="crea-modal-view-overlay">
    <div class="crea-modal-view-box" style="max-width: 450px; text-align: center; padding: 40px 30px;">
        <svg viewBox="0 0 24 24" width="60" height="60" fill="none" stroke="#f59e0b" stroke-width="2" style="margin: 0 auto 20px auto; display:block;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        <h3 style="margin: 0 0 15px 0; font-size: 1.5em; color: #0f172a;">Límite Superado</h3>
        <p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 25px;">
            Estás intentando exportar <strong id="crea-export-count-<?php echo $base_id; ?>" style="color: #0f172a; font-size: 16px;">0</strong> registros.<br><br>
            Esto supera la capacidad segura de <strong>5,000 registros</strong> para el entorno cliente. Para extracciones masivas, contacta al administrador del sistema.
        </p>
        <button type="button" class="crea-btn-action crea-btn-close-limit" style="margin: 0 auto;">Entendido</button>
    </div>
</div>

<!-- Estructura del modal de visualización de registros -->
<div id="crea-modal-view-<?php echo $base_id; ?>" class="crea-modal-view-overlay">
    <div class="crea-modal-view-box">
        <div class="crea-modal-view-header">
            <h3 id="crea-modal-title-<?php echo $base_id; ?>">Detalles</h3>
            <button type="button" class="crea-modal-view-close" aria-label="Cerrar modal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="crea-modal-view-body" id="crea-modal-content-<?php echo $base_id; ?>"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.getElementById('crea-visor-<?php echo $base_id; ?>');
    if (!wrapper) return;

    const baseId = '<?php echo $base_id; ?>';
    const baseName = '<?php echo esc_js($form['form_slug']); ?>';
    const baseNameHuman = '<?php echo esc_js($form['form_name']); ?>';
    const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
    const isEditorMode = <?php echo $is_editor_mode ? 'true' : 'false'; ?>;
    
    const btnFullscreen = document.getElementById(`btn-fullscreen-${baseId}`);
    if (btnFullscreen) {
        btnFullscreen.addEventListener('click', () => {
            if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                if (wrapper.requestFullscreen) {
                    wrapper.requestFullscreen();
                } else if (wrapper.webkitRequestFullscreen) {
                    wrapper.webkitRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
            }
        });

        document.addEventListener('fullscreenchange', handleFullscreenChange);
        document.addEventListener('webkitfullscreenchange', handleFullscreenChange);

        function handleFullscreenChange() {
            if (document.fullscreenElement || document.webkitFullscreenElement) {
                btnFullscreen.innerHTML = `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"></path></svg>`;
            } else {
                btnFullscreen.innerHTML = `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg>`;
            }
        }
    }

    // Inicialización de Modal de Visualización Detallada
    const modalView = document.getElementById(`crea-modal-view-${baseId}`);
    const modalViewContent = document.getElementById(`crea-modal-content-${baseId}`);
    const modalViewTitle = document.getElementById(`crea-modal-title-${baseId}`);
    
    if (modalView) {
        modalView.querySelector('.crea-modal-view-close').addEventListener('click', () => modalView.style.display = 'none');
        modalView.addEventListener('click', (e) => { if(e.target === modalView) modalView.style.display = 'none'; });
    }

    wrapper.addEventListener('click', function(e) {
        const btnViewFull = e.target.closest('.crea-btn-view-full');
        const btnViewHtml = e.target.closest('.crea-btn-view-html');
        
        if (btnViewFull || btnViewHtml) {
            const row = e.target.closest('tr');
            if (!row) return;
            const rowData = JSON.parse(row.getAttribute('data-json') || '{}');

            if (btnViewFull) {
                modalViewTitle.textContent = `Detalles del Registro #${rowData.id || ''}`;
                let html = '<div class="crea-view-grid">';
                
                if(rowData.created_at) html += `<div class="crea-view-label">Fecha de Captura:</div><div class="crea-view-value">${rowData.created_at}</div>`;
                
                const headers = Array.from(document.querySelectorAll(`#crea-main-table-${baseId} thead th[data-name]`));
                headers.forEach(th => {
                    const name = th.getAttribute('data-name');
                    const cellIndex = th.cellIndex;
                    const td = row.children[cellIndex];
                    if (td && td.hasAttribute('data-slug')) {
                        const slug = td.getAttribute('data-slug');
                        const val = rowData[slug] || '<em>Vacío</em>';
                        // ☀️ Inserción estructurada dentro de canvas para visualización coherente en ficha completa
                        const formattedVal = (td.getAttribute('data-type') === 'text_html' || td.classList.contains('crea-cell-longtext'))
                            ? `<div class="crea-html-preview-canvas">${val}</div>`
                            : val;
                        html += `<div class="crea-view-label">${name}:</div><div class="crea-view-value">${formattedVal}</div>`;
                    }
                });
                
                html += '</div>';
                modalViewContent.innerHTML = html;
                modalView.style.display = 'flex';
            } 
            else if (btnViewHtml) {
                const slug = btnViewHtml.getAttribute('data-slug');
                modalViewTitle.textContent = 'Vista Previa de Diseño HTML';
                modalViewContent.innerHTML = `<div class="crea-html-preview-canvas">${rowData[slug] || ''}</div>`;
                modalView.style.display = 'flex';
            }
        }
    });

    const btnToggleCols = document.getElementById(`btn-toggle-cols-${baseId}`);
    const colDropdown = document.getElementById(`crea-col-dropdown-${baseId}`);
    const mainTable = document.getElementById(`crea-main-table-${baseId}`);
    const dynamicStyles = document.getElementById(`crea-dynamic-styles-${baseId}`);
    const ths = Array.from(mainTable.querySelectorAll('thead th'));
    
    if (btnToggleCols) {
        ths.forEach((th, index) => {
            if (th.hasAttribute('data-always-visible')) return; 
            let name = th.textContent.replace('↕', '').trim();
            let isSys = th.hasAttribute('data-sys');
            let isChecked = isSys ? '' : 'checked';
            
            let lbl = document.createElement('label');
            lbl.innerHTML = `<input type="checkbox" value="${index}" ${isChecked}> ${name}`;
            colDropdown.appendChild(lbl);
        });

        btnToggleCols.addEventListener('click', () => { colDropdown.style.display = colDropdown.style.display === 'block' ? 'none' : 'block'; });
        document.addEventListener('click', (e) => { if (!e.target.closest('.crea-col-selector')) colDropdown.style.display = 'none'; });
        colDropdown.addEventListener('change', function(e) { if (e.target.tagName === 'INPUT') applyColumnVisibility(); });
    }

    // Inyección de CSS dinámico para visibilidad de columnas con alta especificidad
    function applyColumnVisibility() {
        if (!colDropdown) return;
        const checkboxes = colDropdown.querySelectorAll('input[type="checkbox"]');
        let cssStr = '';
        checkboxes.forEach(chk => {
            if (!chk.checked) {
                const nth = parseInt(chk.value) + 1;
                cssStr += `#crea-visor-${baseId} table.crea-frontend-table th:nth-child(${nth}), #crea-visor-${baseId} table.crea-frontend-table td:nth-child(${nth}) { display: none; }\n`;
                ths[parseInt(chk.value)].classList.add('crea-hidden-col-export');
            } else {
                ths[parseInt(chk.value)].classList.remove('crea-hidden-col-export');
            }
        });
        dynamicStyles.innerHTML = cssStr;
    }
    applyColumnVisibility(); 

    if (typeof jQuery !== 'undefined') {
        jQuery('.crea-tz-select').select2();
        jQuery('.crea-tz-select').on('change', updateDatesAndTimes);
    }

    const tzSelect = wrapper.querySelector('.crea-tz-select');
    const saveTzCheckbox = document.getElementById('crea-save-tz');
    const allRows = Array.from(wrapper.querySelectorAll('.crea-data-row'));
    let systemTz = '<?php echo esc_js($system_tz); ?>';

    const savedTz = localStorage.getItem('crea_tz_preference');
    if (savedTz) {
        tzSelect.value = savedTz;
        saveTzCheckbox.checked = true;
        if (typeof jQuery !== 'undefined') jQuery(tzSelect).trigger('change.select2');
    } else {
        tzSelect.value = systemTz;
        saveTzCheckbox.checked = false;
        if (typeof jQuery !== 'undefined') jQuery(tzSelect).trigger('change.select2');
    }

    function formatTimeField(timeString, tzTarget, format12h24h) {
        if (!timeString) return 'Vacío';
        const d = new Date(`1970-01-01T${timeString}Z`);
        if (isNaN(d.getTime())) return timeString;
        const options = { hour: '2-digit', minute: '2-digit', timeZone: tzTarget, hour12: (format12h24h === '12h') };
        return new Intl.DateTimeFormat('es-MX', options).format(d);
    }

    function updateDatesAndTimes() {
        const selectedTz = tzSelect.value;
        if (saveTzCheckbox.checked) localStorage.setItem('crea_tz_preference', selectedTz);
        else localStorage.removeItem('crea_tz_preference');

        allRows.forEach(row => {
            try {
                const dateCell = row.querySelector('.crea-cell-date');
                if(dateCell) {
                    const ts = parseInt(dateCell.getAttribute('data-timestamp'));
                    if (!ts || isNaN(ts)) {
                        dateCell.innerHTML = '<span style="opacity:0.5;">N/A</span>';
                    } else {
                        const d = new Date(ts * 1000);
                        const str = new Intl.DateTimeFormat('es-MX', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false, timeZone: selectedTz }).format(d);
                        dateCell.innerHTML = str + ` <span style="font-size:10px; opacity:0.5; margin-left:4px;">${selectedTz}</span>`;
                    }
                }

                const timeCells = row.querySelectorAll('.crea-cell-time');
                timeCells.forEach(tc => {
                    const rawTime = tc.getAttribute('data-raw');
                    if(!rawTime) return;
                    const sysTz = tc.getAttribute('data-tz');
                    const format = tc.getAttribute('data-format');
                    const timeSysStr = formatTimeField(rawTime, sysTz, format);
                    const timeUserStr = formatTimeField(rawTime, selectedTz, format);
                    tc.innerHTML = `<div class="crea-time-sys" title="Hora del Sistema">${timeSysStr} <span style="font-size:10px; opacity:0.5; font-weight:normal;">${sysTz}</span></div><div class="crea-time-user" title="Tu Hora Local">Tu Hora: ${timeUserStr} <span style="font-size:10px;">${selectedTz}</span></div>`;
                });
            } catch(e) {}
        });
    }

    if (typeof jQuery === 'undefined') { tzSelect.addEventListener('change', updateDatesAndTimes); }
    saveTzCheckbox.addEventListener('change', updateDatesAndTimes);
    updateDatesAndTimes(); 

    const btnToggleFilters = document.getElementById(`btn-toggle-filters-${baseId}`);
    const filtersPanel = document.getElementById(`crea-filters-panel-${baseId}`);
    const searchInput = wrapper.querySelector('.crea-search-input');
    
    const dateSwitch = document.getElementById('date_filter_mode_switch');
    const lblMonth = document.getElementById('lbl-switch-month');
    const lblRange = document.getElementById('lbl-switch-range');
    const groupMonth = document.getElementById('filter-date-month-group');
    const groupRange = document.getElementById('filter-date-range-group');
    
    const fltYear = document.getElementById('flt-year');
    const fltMonth = document.getElementById('flt-month');
    const fltStartDate = document.getElementById('flt-start-date');
    const fltEndDate = document.getElementById('flt-end-date');
    const fltUser = document.getElementById('flt-user'); 
    const fltDynamics = document.querySelectorAll('.flt-dynamic');
    
    const monthNames = {'01':'Enero','02':'Febrero','03':'Marzo','04':'Abril','05':'Mayo','06':'Junio','07':'Julio','08':'Agosto','09':'Septiembre','10':'Octubre','11':'Noviembre','12':'Diciembre'};

    if (btnToggleFilters) {
        btnToggleFilters.addEventListener('click', () => { 
            filtersPanel.style.display = filtersPanel.style.display === 'block' ? 'none' : 'block'; 
        });
    }

    if (dateSwitch) {
        dateSwitch.addEventListener('change', (e) => {
            const mode = e.target.checked ? 'range' : 'month';
            if (mode === 'month') {
                groupMonth.style.display = 'flex'; groupRange.style.display = 'none';
                lblMonth.classList.add('active'); lblRange.classList.remove('active');
            } else {
                groupMonth.style.display = 'none'; groupRange.style.display = 'flex';
                lblRange.classList.add('active'); lblMonth.classList.remove('active');
            }
            applyFilters();
        });
    }

    if (fltYear) {
        const dateMap = {}; 
        allRows.forEach(row => {
            const dateCell = row.querySelector('.crea-cell-date');
            if (dateCell) {
                const ts = parseInt(dateCell.getAttribute('data-timestamp'));
                if(ts && !isNaN(ts)) {
                    const d = new Date(ts * 1000);
                    const y = d.getUTCFullYear().toString();
                    const m = (d.getUTCMonth() + 1).toString().padStart(2, '0');
                    if(!dateMap[y]) dateMap[y] = new Set();
                    dateMap[y].add(m);
                }
            }
        });

        Object.keys(dateMap).sort().reverse().forEach(y => { fltYear.appendChild(new Option(y, y)); });

        const handleYearChange = (e) => {
            fltMonth.innerHTML = '<option value="">-- Todos los Meses --</option>';
            const val = e.target ? e.target.value : e; 
            if (val) {
                fltMonth.disabled = false;
                Array.from(dateMap[val]).sort().forEach(m => fltMonth.appendChild(new Option(`Mes ${m} ${monthNames[m]}`, m)));
            } else {
                fltMonth.disabled = true;
            }
            if (typeof jQuery !== 'undefined') jQuery(fltMonth).trigger('change.select2');
            applyFilters();
        };

        if (typeof jQuery !== 'undefined') jQuery(fltYear).on('change', handleYearChange);
        else fltYear.addEventListener('change', handleYearChange);
        
        fltStartDate.addEventListener('change', applyFilters);
        fltEndDate.addEventListener('change', applyFilters);
    }
    
    fltDynamics.forEach(select => {
        const slug = select.getAttribute('data-slug');
        const uniqueVals = new Set();
        allRows.forEach(r => {
            const cell = r.querySelector(`td[data-slug="${slug}"]`);
            if (cell) {
                const val = cell.getAttribute('data-val').trim();
                if(val) uniqueVals.add(val);
            }
        });
        Array.from(uniqueVals).sort().forEach(v => select.appendChild(new Option(v, v)));
    });

    if (typeof jQuery !== 'undefined') {
        jQuery('.crea-filter-select2').select2({ width: '100%', dropdownAutoWidth: true });
        jQuery('#flt-month, #flt-user, .flt-dynamic').on('change', applyFilters);
    } else {
        if(fltMonth) fltMonth.addEventListener('change', applyFilters);
        if(fltUser) fltUser.addEventListener('change', applyFilters);
        fltDynamics.forEach(sel => sel.addEventListener('change', applyFilters));
    }
    
    searchInput.addEventListener('input', applyFilters);

    let filteredRows = [...allRows];
    function applyFilters() {
        const query = searchInput.value.toLowerCase().trim();
        const mode = (dateSwitch && dateSwitch.checked) ? 'range' : 'month';
        const selY = fltYear ? fltYear.value : '';
        const selM = fltMonth ? fltMonth.value : '';
        const selStart = (fltStartDate && fltStartDate.value) ? new Date(fltStartDate.value + 'T00:00:00').getTime() : null;
        const selEnd = (fltEndDate && fltEndDate.value) ? new Date(fltEndDate.value + 'T23:59:59').getTime() : null;
        const selUser = fltUser ? fltUser.value : '';

        const dynActive = [];
        fltDynamics.forEach(sel => { if(sel.value) dynActive.push({slug: sel.getAttribute('data-slug'), val: sel.value}); });

        filteredRows = allRows.filter(row => {
            if (query && !row.textContent.toLowerCase().includes(query)) return false;

            const dateCell = row.querySelector('.crea-cell-date');
            if (dateCell && isLoggedIn) {
                const tsMs = parseInt(dateCell.getAttribute('data-timestamp')) * 1000;
                if(tsMs && !isNaN(tsMs)){
                    if (mode === 'month') {
                        if (selY) {
                            const d = new Date(tsMs);
                            if (d.getUTCFullYear().toString() !== selY) return false;
                            if (selM && (d.getUTCMonth() + 1).toString().padStart(2, '0') !== selM) return false;
                        }
                    } else {
                        if (selStart && tsMs < selStart) return false;
                        if (selEnd && tsMs > selEnd) return false;
                    }
                }
            }

            if (selUser) {
                const uCell = row.querySelector('.crea-cell-user');
                if (uCell && uCell.getAttribute('data-val') !== selUser) return false;
            }

            for (let filter of dynActive) {
                const td = row.querySelector(`td[data-slug="${filter.slug}"]`);
                if (!td) return false;
                const cellVal = td.getAttribute('data-val');
                if (!cellVal.includes(filter.val)) return false;
            }

            return true;
        });

        currentPage = 1;
        applySort();
    }

    let sortCol = -1;
    let sortAsc = true;

    function applySort() {
        if (sortCol >= 0) {
            const targetTh = wrapper.querySelector(`th:nth-child(${sortCol + 1})`);
            const type = targetTh ? targetTh.getAttribute('data-sort') : 'string';
            
            filteredRows.sort((a, b) => {
                const cellA = a.children[sortCol].getAttribute('data-val') || a.children[sortCol].textContent;
                const cellB = b.children[sortCol].getAttribute('data-val') || b.children[sortCol].textContent;
                
                if (type === 'number') {
                    return sortAsc ? (parseFloat(cellA) - parseFloat(cellB)) : (parseFloat(cellB) - parseFloat(cellA));
                } else {
                    return sortAsc ? String(cellA).localeCompare(String(cellB)) : String(cellB).localeCompare(String(cellA));
                }
            });
        }
        renderTable();
    }

    ths.forEach((th) => {
        if(th.hasAttribute('data-always-visible')) return;
        th.addEventListener('click', () => {
            const domIndex = th.cellIndex; 
            if (sortCol === domIndex) sortAsc = !sortAsc;
            else { sortCol = domIndex; sortAsc = true; }
            ths.forEach(h => {
                const icon = h.querySelector('.sort-icon');
                if(icon) icon.textContent = '↕';
            });
            const activeIcon = th.querySelector('.sort-icon');
            if(activeIcon) activeIcon.textContent = sortAsc ? '↓' : '↑';
            applySort();
        });
    });

    const btnExports = wrapper.querySelectorAll('.crea-btn-export');
    const limitModal = document.getElementById(`crea-export-limit-modal-${baseId}`);
    
    if (limitModal) {
        limitModal.querySelector('.crea-btn-close-limit').addEventListener('click', () => limitModal.style.display = 'none');
    }
    
    btnExports.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const format = e.currentTarget.getAttribute('data-format');
            exportFilteredData(format);
        });
    });

    function exportFilteredData(format) {
        if (filteredRows.length > 5000) {
            document.getElementById(`crea-export-count-${baseId}`).textContent = filteredRows.length.toLocaleString();
            limitModal.style.display = 'flex';
            return;
        }

        const headersArr = [];
        const validColIndices = [];
        
        ths.forEach((th, index) => {
            if (th.hasAttribute('data-always-visible')) return; 
            if (th.classList.contains('crea-hidden-col-export')) return; 
            headersArr.push(th.textContent.replace('↕', '').trim());
            validColIndices.push(index);
        });

        const dataArr = [];
        filteredRows.forEach(row => {
            const rowData = [];
            validColIndices.forEach(idx => {
                const cell = row.children[idx];
                let val = cell.getAttribute('data-raw') || cell.getAttribute('data-val') || cell.textContent.trim();
                rowData.push(val);
            });
            dataArr.push(rowData);
        });

        const dNow = new Date();
        const strDate = `${dNow.getFullYear()}${(dNow.getMonth()+1).toString().padStart(2,'0')}${dNow.getDate().toString().padStart(2,'0')}`;
        const fileName = `Exportacion_${baseName}_${strDate}`;

        if (format === 'csv') {
            let csvContent = headersArr.join(',') + '\n';
            dataArr.forEach(r => { csvContent += r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',') + '\n'; });
            const blob = new Blob(["\ufeff", csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob); link.download = fileName + ".csv"; link.click();
        } 
        else if (format === 'json') {
            const jsonArray = dataArr.map(r => {
                const obj = {};
                headersArr.forEach((h, i) => obj[h] = r[i]);
                return obj;
            });
            const blob = new Blob([JSON.stringify(jsonArray, null, 2)], { type: 'application/json' });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob); link.download = fileName + ".json"; link.click();
        } 
        else if (format === 'xlsx') {
            if (typeof XLSX === 'undefined') return alert('La librería de Excel aún no ha cargado. Por favor, intenta en un segundo.');
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet([headersArr, ...dataArr]);
            XLSX.utils.book_append_sheet(wb, ws, "Datos Extraídos");
            XLSX.writeFile(wb, fileName + ".xlsx");
        } 
        else if (format === 'pdf') {
            if (typeof window.jspdf === 'undefined') return alert('La librería de PDF aún no ha cargado.');
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'pt', 'a4'); 
            doc.setFontSize(14);
            doc.text(`Reporte de Datos: ${baseNameHuman}`, 40, 40);
            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text(`Generado el: ${dNow.toLocaleString('es-MX')} | Total registros: ${filteredRows.length}`, 40, 55);
            
            doc.autoTable({
                head: [headersArr], body: dataArr, startY: 70,
                styles: { fontSize: 8, cellPadding: 4 },
                headStyles: { fillColor: [15, 23, 42] }
            });
            doc.save(fileName + ".pdf");
        }
    }

    const tbody = wrapper.querySelector('tbody');
    const infoText = wrapper.querySelector('.crea-table-info');
    const paginationTop = wrapper.querySelector('#crea-pagination-top');
    const paginationBottom = wrapper.querySelector('#crea-pagination-bottom');
    const perPageSelect = wrapper.querySelector('.crea-per-page');
    let currentPage = 1;
    let rowsPerPage = 25;

    function renderTable() {
        tbody.innerHTML = '';
        const total = filteredRows.length;
        
        if (total === 0) {
            tbody.innerHTML = '<tr><td colspan="100%" class="crea-empty-state">No se encontraron resultados para tu búsqueda.</td></tr>';
            infoText.textContent = 'Mostrando 0 registros';
            paginationTop.innerHTML = ''; paginationBottom.innerHTML = '';
            return;
        }

        let start = 0; let end = total;
        if (rowsPerPage !== 'all') {
            start = (currentPage - 1) * rowsPerPage;
            end = start + rowsPerPage;
            if (end > total) end = total;
        }

        const rowsToShow = filteredRows.slice(start, end);
        rowsToShow.forEach(row => tbody.appendChild(row));

        infoText.innerHTML = `Mostrando <strong>${start + 1}</strong> a <strong>${end}</strong> de <strong>${total}</strong> registros`;
        renderPagination(total);
    }

    function createPaginationNav(totalPages) {
        const nav = document.createElement('div');
        nav.style.display = 'flex'; nav.style.gap = '5px';

        const prevBtn = document.createElement('button');
        prevBtn.textContent = '«'; prevBtn.disabled = (currentPage === 1);
        prevBtn.onclick = () => { currentPage--; renderTable(); };
        nav.appendChild(prevBtn);

        for (let i = 1; i <= totalPages; i++) {
            if (totalPages > 7) {
                if (i !== 1 && i !== totalPages && (i < currentPage - 1 || i > currentPage + 1)) {
                    if (i === 2 || i === totalPages - 1) {
                        const dots = document.createElement('span'); dots.textContent = '...'; dots.style.padding = '0 5px'; dots.style.opacity = '0.5'; nav.appendChild(dots);
                    }
                    continue;
                }
            }
            const pageBtn = document.createElement('button');
            pageBtn.textContent = i;
            if (i === currentPage) pageBtn.classList.add('active');
            pageBtn.onclick = () => { currentPage = i; renderTable(); };
            nav.appendChild(pageBtn);
        }

        const nextBtn = document.createElement('button');
        nextBtn.textContent = '»'; nextBtn.disabled = (currentPage === totalPages);
        nextBtn.onclick = () => { currentPage++; renderTable(); };
        nav.appendChild(nextBtn);

        return nav;
    }

    function renderPagination(total) {
        paginationTop.innerHTML = ''; paginationBottom.innerHTML = '';
        if (rowsPerPage === 'all' || total <= rowsPerPage) return;
        const totalPages = Math.ceil(total / rowsPerPage);
        paginationTop.appendChild(createPaginationNav(totalPages));
        paginationBottom.appendChild(createPaginationNav(totalPages));
    }

    perPageSelect.addEventListener('change', function() {
        const val = this.value;
        rowsPerPage = val === 'all' ? 'all' : parseInt(val);
        currentPage = 1; renderTable();
    });

    renderTable();
});
</script>