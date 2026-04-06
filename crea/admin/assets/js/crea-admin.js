/**
 * Ruta del archivo: wp-content/plugins/crea/admin/assets/js/crea-admin.js
 *
 * ☀️ Scripts globales para la interfaz administrativa de CREA.
 */

window.CreaAdmin = {
    
    initMobileTables: function() {
        document.querySelectorAll('.crea-table tbody tr').forEach(row => {
            row.removeEventListener('click', window.CreaAdmin._handleRowClick);
            row.addEventListener('click', window.CreaAdmin._handleRowClick);
        });
    },

    _handleRowClick: function(e) {
        if (window.innerWidth > 767) return; 
        if (e.target.closest('button') || e.target.closest('a') || e.target.closest('input') || e.target.closest('select')) {
            return;
        }
        this.classList.toggle('is-open');
    },

    /**
     * Lógica Global para Tablas Dinámicas (Búsqueda y Paginación)
     */
    initDynamicTable: function(tableId, searchInputId, perPageId) {
        const searchInput = document.getElementById(searchInputId);
        const itemsPerPageSelect = document.getElementById(perPageId);
        if (!searchInput || !itemsPerPageSelect) return;

        const tbody = document.querySelector(`#${tableId} tbody`);
        if(!tbody) return;

        const allRows = Array.from(tbody.querySelectorAll('.crea-data-row'));
        const emptyRow = tbody.querySelector('.crea-empty-row');
        
        const paginationTop = document.getElementById(`${tableId}-pagination-top`);
        const paginationBottom = document.getElementById(`${tableId}-pagination-bottom`);
        const countDisplay = document.getElementById(`${tableId}-count`);

        let currentPage = 1;
        let itemsPerPage = 25;
        let matchedRows = allRows;

        function render() {
            const term = searchInput.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            matchedRows = allRows.filter(row => {
                const text = row.textContent.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                const match = text.includes(term);
                row.style.display = 'none';
                return match;
            });

            const totalItems = matchedRows.length;
            if(emptyRow) emptyRow.style.display = totalItems === 0 ? '' : 'none';

            let totalPages = 1;
            if (itemsPerPage === 'all') {
                matchedRows.forEach(row => row.style.display = '');
            } else {
                totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
                if (currentPage > totalPages) currentPage = totalPages;
                const start = (currentPage - 1) * itemsPerPage;
                matchedRows.slice(start, start + itemsPerPage).forEach(row => row.style.display = '');
            }

            updateUI(totalItems, totalPages);
        }

        function updateUI(totalItems, totalPages) {
            if (totalItems === 0) {
                if(countDisplay) countDisplay.innerHTML = 'No hay registros.';
                if(paginationTop) paginationTop.innerHTML = '';
                if(paginationBottom) paginationBottom.innerHTML = '';
                return;
            }

            let startRange = itemsPerPage !== 'all' ? ((currentPage - 1) * itemsPerPage) + 1 : 1;
            let endRange = itemsPerPage !== 'all' ? Math.min(startRange + itemsPerPage - 1, totalItems) : totalItems;

            if(countDisplay) countDisplay.innerHTML = `Mostrando <strong>${startRange}</strong> al <strong>${endRange}</strong> de <strong>${totalItems}</strong>`;

            let html = '';
            if (totalPages > 1) {
                html += `<a href="#" class="crea-page-btn ${currentPage === 1 ? 'disabled' : ''}" data-page="prev">«</a>`;
                for (let i = 1; i <= totalPages; i++) {
                    html += `<a href="#" class="crea-page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</a>`;
                }
                html += `<a href="#" class="crea-page-btn ${currentPage === totalPages ? 'disabled' : ''}" data-page="next">»</a>`;
            }

            [paginationTop, paginationBottom].forEach(el => {
                if(el) {
                    el.innerHTML = html;
                    el.querySelectorAll('a').forEach(a => a.addEventListener('click', e => {
                        e.preventDefault();
                        if (e.target.classList.contains('disabled')) return;
                        const p = e.target.getAttribute('data-page');
                        if (p === 'prev') currentPage--;
                        else if (p === 'next') currentPage++;
                        else currentPage = parseInt(p);
                        render();
                    }));
                }
            });
        }

        searchInput.addEventListener('input', () => { currentPage = 1; render(); });
        itemsPerPageSelect.addEventListener('change', (e) => {
            itemsPerPage = e.target.value === 'all' ? 'all' : parseInt(e.target.value, 10);
            currentPage = 1;
            render();
        });

        render();
    },

    /**
     * Lógica para el Modal de Shortcodes
     */
    initShortcodeModals: function() {
        const modal = document.getElementById('crea-shortcode-modal');
        if(!modal) return;

        document.querySelectorAll('.crea-open-shortcode').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                
                document.getElementById('modal-base-name').innerText = name;
                document.getElementById('sc-all').innerText = `[crea_table_a_${id}]`;
                document.getElementById('sc-add').innerText = `[crea_table_ar_${id}]`;
                document.getElementById('sc-view').innerText = `[crea_table_vr_${id}]`;
                
                modal.style.display = 'block';
            });
        });

        document.querySelectorAll('.crea-copy-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const text = document.getElementById(targetId).innerText;
                
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(() => {
                        const originalText = this.innerHTML;
                        this.innerHTML = '<span class="dashicons dashicons-yes"></span> Copiado';
                        setTimeout(() => { this.innerHTML = originalText; }, 2000);
                    });
                } else {
                    alert('Por favor copia este texto: ' + text);
                }
            });
        });
    },

    /**
     * Lógica de Modales de Edición y Eliminación
     */
    initActionModals: function() {
        // --- EDITAR METADATOS ---
        const editModal = document.getElementById('crea-edit-modal');
        document.querySelectorAll('.crea-open-edit').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('edit_id').value = this.dataset.id;
                document.getElementById('edit_name').value = this.dataset.name;
                document.getElementById('edit_slug').value = this.dataset.slug;
                document.getElementById('edit_year').value = this.dataset.year;
                document.getElementById('edit_cut_date').value = this.dataset.cutdate;
                document.getElementById('edit_source').value = this.dataset.source;
                document.getElementById('edit_comments').value = this.dataset.comments;
                editModal.style.display = 'block';
            });
        });

        // --- ELIMINAR (Flujo de 2 pasos) ---
        const deleteStep1 = document.getElementById('crea-delete-step1');
        const deleteStep2 = document.getElementById('crea-delete-step2');
        const deleteConfirmInput = document.getElementById('crea-confirm-delete-input');
        const deleteSubmitBtn = document.getElementById('crea-submit-delete-btn');

        document.querySelectorAll('.crea-open-delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('delete_id').value = this.dataset.id;
                document.getElementById('delete_slug').value = this.dataset.slug;
                
                document.getElementById('del-info-cols').innerText = this.dataset.cols;
                document.getElementById('del-info-rows').innerText = this.dataset.rows;
                document.getElementById('del-info-size').innerText = this.dataset.size;
                
                deleteStep1.style.display = 'block';
            });
        });

        const btnContinueDelete = document.getElementById('crea-continue-delete');
        if(btnContinueDelete) {
            btnContinueDelete.addEventListener('click', function() {
                deleteStep1.style.display = 'none';
                deleteConfirmInput.value = '';
                deleteSubmitBtn.disabled = true;
                deleteStep2.style.display = 'block';
            });
        }

        if(deleteConfirmInput) {
            deleteConfirmInput.addEventListener('input', function() {
                deleteSubmitBtn.disabled = (this.value !== 'ELIMINAR');
            });
        }

        // --- CERRAR TODOS LOS MODALES ---
        document.querySelectorAll('.crea-modal-close, .crea-cancel-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.crea-modal-overlay').forEach(m => m.style.display = 'none');
            });
        });
    },

    /**
     * Validación AJAX del Slug antes de enviar el formulario de creación
     */
    initSlugValidation: function() {
        const form = document.getElementById('crea-new-base-form');
        if(!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = form.querySelector('input[type="submit"]');
            const originalText = submitBtn.value;
            submitBtn.value = 'Validando...';
            submitBtn.disabled = true;

            const slugInput = document.getElementById('form_slug').value;

            const data = new URLSearchParams();
            data.append('action', 'crea_check_slug');
            data.append('security', crea_ajax_obj.nonce);
            data.append('slug', slugInput);

            fetch(crea_ajax_obj.ajax_url, {
                method: 'POST',
                body: data
            })
            .then(res => res.json())
            .then(response => {
                if(response.exists) {
                    // ☀️ CORRECCIÓN: Usar Modal personalizado en lugar de alert()
                    const errorModal = document.getElementById('crea-slug-error-modal');
                    if (errorModal) {
                        document.getElementById('crea-duplicate-slug-name').innerText = `"${response.sanitized}"`;
                        errorModal.style.display = 'block';
                    } else {
                        // Respaldo de seguridad
                        alert(`⚠️ El Identificador Interno "${response.sanitized}" ya existe en el sistema.\nPor favor, elige uno diferente.`);
                    }
                    
                    submitBtn.value = originalText;
                    submitBtn.disabled = false;
                } else {
                    HTMLFormElement.prototype.submit.call(form);
                }
            }).catch(err => {
                console.error(err);
                HTMLFormElement.prototype.submit.call(form); 
            });
        });
    }
};

/**
 * Inicialización centralizada de todas las funciones al cargar el DOM.
 */
document.addEventListener('DOMContentLoaded', function() {
    window.CreaAdmin.initMobileTables();
    window.CreaAdmin.initDynamicTable('crea-bases-table', 'crea-search-bases', 'crea-items-per-page');
    window.CreaAdmin.initShortcodeModals();
    window.CreaAdmin.initActionModals();
    window.CreaAdmin.initSlugValidation();
});