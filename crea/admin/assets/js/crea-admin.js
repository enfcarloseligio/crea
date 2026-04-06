/**
 * Ruta del archivo: wp-content/plugins/crea/admin/assets/js/crea-admin.js
 *
 * Scripts globales para la interfaz administrativa de CREA.
 */

window.CreaAdmin = {
	/**
	 * Inicializa el comportamiento de acordeón para tablas en resoluciones móviles.
	 */
	initMobileTables: function() {
		document.querySelectorAll('.crea-table tbody tr').forEach(row => {
			row.removeEventListener('click', window.CreaAdmin._handleRowClick);
			row.addEventListener('click', window.CreaAdmin._handleRowClick);
		});
	},

	/**
	 * Previene la expansión del acordeón si se hace clic en botones, enlaces o inputs.
	 */
	_handleRowClick: function(e) {
		if (window.innerWidth > 767) return; 
		if (e.target.closest('button') || e.target.closest('a') || e.target.closest('input') || e.target.closest('select')) {
			return;
		}
		this.classList.toggle('is-open');
	}
};

document.addEventListener('DOMContentLoaded', function() {
	window.CreaAdmin.initMobileTables();
});