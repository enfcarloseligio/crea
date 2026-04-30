<?php
/**
 * Archivo: wp-content/plugins/crea/includes/shortcodes/views/view-table.php
 * Descripción: Interfaz gráfica moderna y dinámica para la matriz de datos (Perfil Analista).
 */
if ( ! defined( 'WPINC' ) ) { die; }

// Generar lista segura de zonas horarias para el frontend
$tzlist = timezone_identifiers_list();
$system_tz = wp_timezone_string();

// ☀️ Obtener configuración de colores para el Frontend
$default_front_colors = [
    'primary'     => '#2563EB',
    'th_bg'       => '#F8FAFC',
    'th_text'     => '#0F172A',
    'odd_bg'      => '#FFFFFF',
    'odd_text'    => '#334155',
    'even_bg'     => '#F8FAFC',
    'even_text'   => '#334155',
];
$front_colors = wp_parse_args( get_option( 'crea_front_colors', [] ), $default_front_colors );
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* Variables dinámicas desde la configuración del plugin */
    #crea-visor-<?php echo $base_id; ?> {
        --crea-front-primary: <?php echo esc_attr($front_colors['primary']); ?>;
        --crea-front-th-bg: <?php echo esc_attr($front_colors['th_bg']); ?>;
        --crea-front-th-text: <?php echo esc_attr($front_colors['th_text']); ?>;
        --crea-front-tr-odd-bg: <?php echo esc_attr($front_colors['odd_bg']); ?>;
        --crea-front-tr-odd-text: <?php echo esc_attr($front_colors['odd_text']); ?>;
        --crea-front-tr-even-bg: <?php echo esc_attr($front_colors['even_bg']); ?>;
        --crea-front-tr-even-text: <?php echo esc_attr($front_colors['even_text']); ?>;
    }

    /* CONTENEDOR Y DISEÑO GENERAL */
    .crea-table-wrapper {
        background: transparent;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        margin-bottom: 30px;
        overflow: hidden;
    }

    .crea-table-header {
        padding: 20px 25px;
        border-bottom: 1px solid #e2e8f0;
        background: transparent;
    }
    
    /* ☀️ Titulares limpios: Heredan estilos del tema activo */
    .crea-table-header h2 { margin: 0 0 5px 0; }
    .crea-table-header p { margin: 0; opacity: 0.8; }

    /* TOOLBAR: Alineación horizontal perfecta */
    .crea-table-toolbar {
        padding: 15px 25px;
        border-bottom: 1px solid #e2e8f0;
        background: transparent;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .crea-table-controls { 
        display: flex; 
        gap: 20px; 
        align-items: center; 
        flex-wrap: wrap;
    }
    
    .crea-control-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .crea-table-controls select, 
    .crea-search-box input {
        padding: 5px 10px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        background: #fff;
        outline: none;
        height: 30px; 
        box-sizing: border-box;
        transition: border-color 0.2s;
    }
    .crea-table-controls select:focus, .crea-search-box input:focus { border-color: var(--crea-front-primary); }

    /* Select2 Ajustes para igualar alturas y tipografía en todos sus estados */
    .select2-container .select2-selection--single { height: 30px !important; border-color: #cbd5e1 !important; display: flex; align-items: center; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: normal !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 28px !important; }

    .crea-search-box { position: relative; width: 100%; max-width: 250px; display: flex; align-items: center; }
    .crea-search-icon {
        position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
        width: 14px; height: 14px; opacity: 0.4; pointer-events: none;
    }
    .crea-search-box input { width: 100%; padding-left: 32px; box-sizing: border-box; }

    /* ESTRUCTURA DE LA TABLA */
    .crea-table-responsive {
        width: 100%;
        overflow-x: auto;
        max-height: 600px;
    }

    .crea-frontend-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        white-space: nowrap;
        margin: 0; /* Anula márgenes del tema */
    }
    
    .crea-frontend-table th,
    .crea-frontend-table td {
        padding: 12px 20px;
        border-bottom: 1px solid #e2e8f0;
    }

    /* ☀️ Colores dinámicos para la tabla */
    .crea-frontend-table th {
        background-color: var(--crea-front-th-bg);
        color: var(--crea-front-th-text);
        position: sticky;
        top: 0;
        z-index: 10;
        cursor: pointer;
        user-select: none;
    }
    
    .crea-frontend-table tbody tr:nth-child(odd) {
        background-color: var(--crea-front-tr-odd-bg);
        color: var(--crea-front-tr-odd-text);
    }
    
    .crea-frontend-table tbody tr:nth-child(even) {
        background-color: var(--crea-front-tr-even-bg);
        color: var(--crea-front-tr-even-text);
    }

    .crea-frontend-table th .sort-icon { opacity: 0.5; margin-left: 5px; }
    .crea-frontend-table tbody tr { transition: opacity 0.1s; }
    .crea-frontend-table tbody tr:hover { opacity: 0.85; } /* Efecto hover neutro */

    .crea-frontend-table td.crea-cell-id { font-weight: 600; }
    .crea-frontend-table td.crea-cell-longtext { max-width: 350px; white-space: normal; word-wrap: break-word; line-height: 1.5; }

    /* PIE DE TABLA Y PAGINACIÓN */
    .crea-table-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 25px;
        background: transparent;
        border-top: 1px solid #e2e8f0;
        opacity: 0.9;
    }

    .crea-pagination-controls { display: flex; gap: 5px; align-items: center; }
    .crea-pagination-controls button {
        background: transparent;
        border: 1px solid #cbd5e1;
        color: inherit;
        padding: 4px 10px;
        border-radius: 4px;
        cursor: pointer;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    .crea-pagination-controls button:hover:not(:disabled) { border-color: var(--crea-front-primary); color: var(--crea-front-primary); }
    .crea-pagination-controls button:disabled { opacity: 0.4; cursor: not-allowed; }
    .crea-pagination-controls button.active { background: var(--crea-front-primary); color: #fff; border-color: var(--crea-front-primary); }

    .crea-empty-state { padding: 50px; text-align: center; opacity: 0.7; }
</style>

<div class="crea-table-wrapper" id="crea-visor-<?php echo $base_id; ?>">
    <div class="crea-table-header">
        <h2><?php echo esc_html( $form['form_name'] ); ?></h2>
        <p>Matriz de Datos Estructurada</p>
    </div>

    <?php if ( empty($records) ) : ?>
        <div class="crea-empty-state">
            <span style="font-size: 40px; display: block; margin-bottom: 10px; opacity: 0.3;">📭</span>
            No existen registros almacenados en esta base de datos actualmente.
        </div>
    <?php else : ?>
        
        <div class="crea-table-toolbar">
            <div class="crea-table-controls">
                
                <div class="crea-control-item">
                    <span>Mostrar</span>
                    <select class="crea-per-page">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="all">Todos</option>
                    </select>
                    <span>registros</span>
                </div>
                
                <div class="crea-control-item">
                    <span>Zona Horaria:</span>
                    <select class="crea-tz-select" style="width: 200px;">
                        <option value="UTC">UTC (Servidor)</option>
                        <?php foreach($tzlist as $tz) : ?>
                            <option value="<?php echo esc_attr($tz); ?>"><?php echo esc_html($tz); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label style="display: flex; align-items: center; gap: 4px; margin-left: 5px; cursor: pointer; opacity: 0.8;">
                        <input type="checkbox" id="crea-save-tz" style="margin:0; cursor:pointer;"> Guardar
                    </label>
                </div>

            </div>
            
            <div class="crea-pagination">
                <div id="crea-pagination-top" class="crea-pagination-controls"></div>
            </div>

            <div class="crea-search-box">
                <svg class="crea-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" class="crea-search-input" placeholder="Buscar en todos los campos..." autocomplete="off" data-lpignore="true" data-form-type="other" spellcheck="false" role="presentation">
            </div>
        </div>

        <div class="crea-table-responsive">
            <table class="crea-frontend-table">
                <thead>
                    <tr>
                        <th data-sort="number" data-col="0">ID <span class="sort-icon">↕</span></th>
                        <th data-sort="number" data-col="1">Fecha de Captura <span class="sort-icon">↕</span></th>
                        <?php 
                        $col_idx = 2;
                        foreach ( $fields as $field ) {
                            $sort_type = in_array($field['field_type'], ['num_discrete', 'num_continuous']) ? 'number' : 'string';
                            echo '<th data-sort="'.$sort_type.'" data-col="'.$col_idx.'">' . esc_html( $field['field_name'] ) . ' <span class="sort-icon">↕</span></th>';
                            $col_idx++;
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $records as $row ) : ?>
                    <tr class="crea-data-row">
                        <td class="crea-cell-id" data-val="<?php echo esc_attr($row['id']); ?>">
                            <?php echo esc_html( $row['id'] ); ?>
                        </td>
                        
                        <?php $timestamp_utc = strtotime($row['created_at']); ?>
                        <td class="crea-cell-date" data-val="<?php echo $timestamp_utc; ?>" data-timestamp="<?php echo $timestamp_utc; ?>"></td>
                        
                        <?php 
                        foreach ( $fields as $field ) {
                            $slug = $field['field_slug'];
                            $value = isset($row[$slug]) ? $row[$slug] : '';
                            $cell_class = in_array($field['field_type'], ['text_long', 'text_html']) ? ' class="crea-cell-longtext"' : '';
                            
                            echo '<td' . $cell_class . ' data-val="' . esc_attr($value) . '">' . nl2br(esc_html($value)) . '</td>';
                        }
                        ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="crea-table-footer">
            <div class="crea-table-info">Mostrando 0 a 0 de 0 registros</div>
            <div class="crea-pagination">
                <div id="crea-pagination-bottom" class="crea-pagination-controls"></div>
            </div>
        </div>

    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.getElementById('crea-visor-<?php echo $base_id; ?>');
    if (!wrapper) return;

    if (typeof jQuery !== 'undefined') {
        jQuery('.crea-tz-select').select2();
        jQuery('.crea-tz-select').on('change', updateDates);
    }

    const searchInput = wrapper.querySelector('.crea-search-input');
    const perPageSelect = wrapper.querySelector('.crea-per-page');
    const tzSelect = wrapper.querySelector('.crea-tz-select');
    const saveTzCheckbox = document.getElementById('crea-save-tz');
    const tbody = wrapper.querySelector('tbody');
    const allRows = Array.from(wrapper.querySelectorAll('.crea-data-row'));
    const infoText = wrapper.querySelector('.crea-table-info');
    const paginationTop = wrapper.querySelector('#crea-pagination-top');
    const paginationBottom = wrapper.querySelector('#crea-pagination-bottom');
    const headers = wrapper.querySelectorAll('th[data-sort]');

    let filteredRows = [...allRows];
    let currentPage = 1;
    let rowsPerPage = 25;
    let sortCol = -1;
    let sortAsc = true;
    let systemTz = '<?php echo esc_js($system_tz); ?>';

    // 1. LÓGICA DE ZONA HORARIA Y LOCAL STORAGE
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

    function formatDate(timestamp, timezone) {
        try {
            const date = new Date(timestamp * 1000);
            return new Intl.DateTimeFormat('es-MX', {
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit', hour12: false,
                timeZone: timezone
            }).format(date);
        } catch (e) {
            return new Date(timestamp * 1000).toLocaleString('es-MX'); 
        }
    }

    function updateDates() {
        const selectedTz = tzSelect.value;
        
        if (saveTzCheckbox.checked) {
            localStorage.setItem('crea_tz_preference', selectedTz);
        } else {
            localStorage.removeItem('crea_tz_preference');
        }

        allRows.forEach(row => {
            const dateCell = row.querySelector('.crea-cell-date');
            if(dateCell) {
                const ts = dateCell.getAttribute('data-timestamp');
                dateCell.innerHTML = formatDate(ts, selectedTz) + ` <span style="font-size:10px; opacity:0.5; margin-left:4px;">${selectedTz}</span>`;
            }
        });
    }

    if (typeof jQuery === 'undefined') { tzSelect.addEventListener('change', updateDates); }
    saveTzCheckbox.addEventListener('change', updateDates);
    updateDates(); 

    // 2. MOTOR DE BÚSQUEDA
    function applyFilter() {
        const query = searchInput.value.toLowerCase().trim();
        if (query === '') {
            filteredRows = [...allRows];
        } else {
            filteredRows = allRows.filter(row => row.textContent.toLowerCase().includes(query));
        }
        currentPage = 1;
        applySort();
    }

    searchInput.addEventListener('input', applyFilter);

    // 3. MOTOR DE ORDENAMIENTO
    function applySort() {
        if (sortCol >= 0) {
            const type = headers[sortCol].getAttribute('data-sort');
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

    headers.forEach((th, index) => {
        th.addEventListener('click', () => {
            if (sortCol === index) {
                sortAsc = !sortAsc;
            } else {
                sortCol = index;
                sortAsc = true;
            }
            headers.forEach(h => h.querySelector('.sort-icon').textContent = '↕');
            th.querySelector('.sort-icon').textContent = sortAsc ? '↓' : '↑';
            applySort();
        });
    });

    // 4. PAGINACIÓN
    function renderTable() {
        tbody.innerHTML = '';
        const total = filteredRows.length;
        
        if (total === 0) {
            tbody.innerHTML = '<tr><td colspan="100%" class="crea-empty-state">No se encontraron resultados para tu búsqueda.</td></tr>';
            infoText.textContent = 'Mostrando 0 registros';
            paginationTop.innerHTML = '';
            paginationBottom.innerHTML = '';
            return;
        }

        let start = 0;
        let end = total;

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
        nav.style.display = 'flex';
        nav.style.gap = '5px';

        const prevBtn = document.createElement('button');
        prevBtn.textContent = '«';
        prevBtn.title = 'Anterior';
        prevBtn.disabled = (currentPage === 1);
        prevBtn.onclick = () => { currentPage--; renderTable(); };
        nav.appendChild(prevBtn);

        for (let i = 1; i <= totalPages; i++) {
            if (totalPages > 7) {
                if (i !== 1 && i !== totalPages && (i < currentPage - 1 || i > currentPage + 1)) {
                    if (i === 2 || i === totalPages - 1) {
                        const dots = document.createElement('span');
                        dots.textContent = '...';
                        dots.style.padding = '0 5px';
                        dots.style.opacity = '0.5';
                        nav.appendChild(dots);
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
        nextBtn.textContent = '»';
        nextBtn.title = 'Siguiente';
        nextBtn.disabled = (currentPage === totalPages);
        nextBtn.onclick = () => { currentPage++; renderTable(); };
        nav.appendChild(nextBtn);

        return nav;
    }

    function renderPagination(total) {
        paginationTop.innerHTML = '';
        paginationBottom.innerHTML = '';
        if (rowsPerPage === 'all' || total <= rowsPerPage) return;

        const totalPages = Math.ceil(total / rowsPerPage);
        
        paginationTop.appendChild(createPaginationNav(totalPages));
        paginationBottom.appendChild(createPaginationNav(totalPages));
    }

    perPageSelect.addEventListener('change', function() {
        const val = this.value;
        rowsPerPage = val === 'all' ? 'all' : parseInt(val);
        currentPage = 1;
        renderTable();
    });

    renderTable();
});
</script>