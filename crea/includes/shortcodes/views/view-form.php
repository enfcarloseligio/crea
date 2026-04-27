<?php
/**
 * Archivo: wp-content/plugins/crea/includes/shortcodes/views/view-form.php
 * Descripción: Interfaz gráfica para la captura de registros (Contexto de shortcode).
 */
if ( ! defined( 'WPINC' ) ) { die; }
?>

<style>
	/* Herencia de estilos estructurales */
	.crea-frontend-wrapper {
		background: transparent;
		font-family: inherit;
		color: inherit;
		margin-bottom: 40px;
	}

	/* Encabezado del formulario */
	.crea-frontend-header {
		margin-bottom: 25px;
		border-bottom: 2px solid rgba(0,0,0,0.05);
		padding-bottom: 15px;
	}
	.crea-frontend-header h2 { margin: 0 0 8px 0; font-size: 1.6em; }
	.crea-frontend-header p { margin: 0; opacity: 0.75; font-size: 0.95em; line-height: 1.5; }
	
	/* Indicadores de retroalimentación */
	.crea-success-banner {
		background-color: #dcfce7;
		border-left: 4px solid #22c55e;
		color: #166534;
		padding: 15px 20px;
		margin-bottom: 25px;
		border-radius: 4px;
		font-size: 0.95em;
	}

	/* Cuadrícula responsiva (CSS Grid) */
	.crea-form-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 20px 25px;
	}

	/* Utilidad para abarcar el ancho completo del contenedor */
	.crea-col-span-full { grid-column: 1 / -1; }

	/* Componentes de entrada */
	.crea-field-group {
		display: flex;
		flex-direction: column;
	}
	.crea-field-group label.crea-main-label {
		font-weight: 600;
		margin-bottom: 6px;
		font-size: 0.95em;
		display: flex;
		align-items: center;
	}
	.crea-req-mark { color: #e11d48; margin-left: 4px; font-weight: bold; }
	
	.crea-field-group input[type="text"],
	.crea-field-group input[type="number"],
	.crea-field-group input[type="date"],
	.crea-field-group input[type="time"],
	.crea-field-group select,
	.crea-field-group textarea {
		width: 100%;
		padding: 10px 12px;
		border: 1px solid rgba(0,0,0,0.15);
		border-radius: 4px;
		font-size: 1em;
		background-color: rgba(255,255,255,0.8);
		color: inherit;
		font-family: inherit;
		transition: border-color 0.2s, box-shadow 0.2s;
	}
	
	.crea-field-group input:focus,
	.crea-field-group select:focus,
	.crea-field-group textarea:focus {
		border-color: var(--crea-primary, #2563eb);
		box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
		outline: none;
	}

	/* Estilos para selecciones categóricas */
	.crea-cat-group { display: flex; flex-direction: column; gap: 8px; margin-top: 4px; }
	.crea-cat-group label { font-weight: normal; font-size: 0.95em; display: flex; align-items: center; gap: 8px; cursor: pointer; }
	.crea-cat-group input { margin: 0 !important; cursor: pointer; }

	/* Controles de acción */
	.crea-form-actions {
		margin-top: 30px;
		padding-top: 20px;
		border-top: 1px solid rgba(0,0,0,0.05);
		text-align: right;
	}
	.crea-btn-submit {
		background-color: var(--crea-primary, #2563eb);
		color: #ffffff;
		border: none;
		padding: 12px 28px;
		border-radius: 4px;
		font-weight: 600;
		font-size: 1em;
		cursor: pointer;
		transition: opacity 0.2s;
	}
	.crea-btn-submit:hover { opacity: 0.9; }

	/* Reglas de responsividad (Breakpoints) */
	@media (max-width: 992px) { .crea-form-grid { grid-template-columns: repeat(2, 1fr); } }
	@media (max-width: 600px) { .crea-form-grid { grid-template-columns: 1fr; } }
</style>

<div class="crea-frontend-wrapper">
	
	<?php if ( isset($_GET['crea_msg']) && $_GET['crea_msg'] === 'success' ) : ?>
		<div class="crea-success-banner">
			<strong>Aviso del sistema:</strong> El registro se ha procesado y almacenado correctamente.
		</div>
	<?php endif; ?>

	<div class="crea-frontend-header">
		<h2><?php echo esc_html( $form['form_name'] ); ?></h2>
		<?php if(!empty($form['description'])): ?>
			<p><?php echo nl2br(esc_html( $form['description'] )); ?></p>
		<?php endif; ?>
	</div>

	<form action="" method="POST" id="crea-form-<?php echo esc_attr($form['form_slug']); ?>" class="crea-capture-form">
		<?php wp_nonce_field( 'crea_submit_record_' . $form['id'], 'crea_record_nonce' ); ?>
		<input type="hidden" name="crea_action" value="save_new_record">
		<input type="hidden" name="base_id" value="<?php echo esc_attr($form['id']); ?>">

		<div class="crea-form-grid">
			<?php 
			foreach ( $fields as $field ) : 
				$config = json_decode($field['config'], true) ?: [];
				$is_req = $field['is_required'] ? 'required' : '';
				$req_mark = $field['is_required'] ? '<span class="crea-req-mark" title="Campo obligatorio">*</span>' : '';
				$name_attr = 'crea_data[' . esc_attr($field['field_slug']) . ']';
				
				// Asignación de clase estructural según la longitud de captura requerida
				$wrapper_class = 'crea-field-group';
				if ( in_array($field['field_type'], ['text_long', 'text_html']) ) {
					$wrapper_class .= ' crea-col-span-full';
				}
			?>
			
			<div class="<?php echo $wrapper_class; ?>">
				<label class="crea-main-label" for="<?php echo esc_attr($field['field_slug']); ?>">
					<?php echo esc_html($field['field_name']) . $req_mark; ?>
				</label>
				
				<?php 
				switch ( $field['field_type'] ) {
					case 'text_short':
						$max = isset($config['max_length']) ? 'maxlength="'.esc_attr($config['max_length']).'"' : '';
						echo '<input type="text" name="'.$name_attr.'" id="'.esc_attr($field['field_slug']).'" '.$max.' '.$is_req.'>';
						break;
					
					case 'num_discrete':
						$max_d = isset($config['digits']) ? 'max="'.(str_repeat('9', $config['digits'])).'"' : '';
						echo '<input type="number" step="1" name="'.$name_attr.'" id="'.esc_attr($field['field_slug']).'" '.$max_d.' '.$is_req.'>';
						break;

					case 'num_continuous':
						$step = 'any';
						if(isset($config['decimals']) && intval($config['decimals']) > 0) {
							$step = '0.' . str_repeat('0', intval($config['decimals']) - 1) . '1';
						}
						echo '<input type="number" step="'.$step.'" name="'.$name_attr.'" id="'.esc_attr($field['field_slug']).'" '.$is_req.'>';
						break;

					case 'date':
						echo '<input type="date" name="'.$name_attr.'" id="'.esc_attr($field['field_slug']).'" '.$is_req.'>';
						break;
					
					case 'time':
						echo '<input type="time" name="'.$name_attr.'" id="'.esc_attr($field['field_slug']).'" '.$is_req.'>';
						break;

					case 'text_long':
						echo '<textarea name="'.$name_attr.'" id="'.esc_attr($field['field_slug']).'" rows="4" '.$is_req.'></textarea>';
						break;

					case 'text_html':
						echo '<textarea name="'.$name_attr.'" id="'.esc_attr($field['field_slug']).'" rows="8" '.$is_req.'></textarea>';
						break;

					case 'select':
						$options = isset($config['options']) ? explode("\n", $config['options']) : [];
						$defaults = isset($config['default']) && is_array($config['default']) ? $config['default'] : [];
						echo '<select name="'.$name_attr.'" id="'.esc_attr($field['field_slug']).'" '.$is_req.'>';
						echo '<option value="">-- Seleccionar --</option>';
						foreach($options as $opt) {
							$opt = trim($opt);
							if(empty($opt)) continue;
							$sel = in_array($opt, $defaults) ? 'selected' : '';
							echo '<option value="'.esc_attr($opt).'" '.$sel.'>'.esc_html($opt).'</option>';
						}
						echo '</select>';
						break;

					case 'radio':
						$options = isset($config['options']) ? explode("\n", $config['options']) : [];
						$defaults = isset($config['default']) && is_array($config['default']) ? $config['default'] : [];
						echo '<div class="crea-cat-group">';
						foreach($options as $index => $opt) {
							$opt = trim($opt);
							if(empty($opt)) continue;
							$chk = in_array($opt, $defaults) ? 'checked' : '';
							$radio_id = esc_attr($field['field_slug']) . '_' . $index;
							echo '<label for="'.$radio_id.'"><input type="radio" name="'.$name_attr.'" id="'.$radio_id.'" value="'.esc_attr($opt).'" '.$is_req.' '.$chk.'> '.esc_html($opt).'</label>';
						}
						echo '</div>';
						break;

					case 'checkbox':
						$options = isset($config['options']) ? explode("\n", $config['options']) : [];
						$defaults = isset($config['default']) && is_array($config['default']) ? $config['default'] : [];
						echo '<div class="crea-cat-group">';
						foreach($options as $index => $opt) {
							$opt = trim($opt);
							if(empty($opt)) continue;
							$chk = in_array($opt, $defaults) ? 'checked' : '';
							$chk_id = esc_attr($field['field_slug']) . '_' . $index;
							echo '<label for="'.$chk_id.'"><input type="checkbox" name="'.$name_attr.'[]" id="'.$chk_id.'" value="'.esc_attr($opt).'" '.$chk.'> '.esc_html($opt).'</label>';
						}
						echo '</div>';
						break;
						
					case 'relation':
						echo '<select name="'.$name_attr.'" id="'.esc_attr($field['field_slug']).'" '.$is_req.'>';
						echo '<option value="">-- Consultar catálogo ('.esc_html($config['rel_base']).') --</option>';
						echo '</select>';
						break;
				}
				?>
			</div>
			<?php endforeach; ?>
		</div>

		<div class="crea-form-actions">
			<button type="submit" class="crea-btn-submit">Guardar Registro</button>
		</div>
	</form>
</div>