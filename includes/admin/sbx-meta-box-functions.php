<?php
/**
 * Sealed Box meta functions
 *
 *
 *
 * @version  1.0.0
 * @package  SealedBox/Admin/Functions
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Output a text input box.
 *
 * @param array $field
 */
function sealed_box_wp_paragraph_input( $field ) {

	$field['class']         = $field['class'] ?? 'short';
	$field['style']         = $field['style'] ?? '';
	$field['id']            = $field['id'] ?? $field['name'] ?? '';

    echo '<p class="' . esc_attr( $field['class'] ) . ' description">

        <strong>' . wp_kses_post( $field['label'] ) . '</strong><br/>

        <span id="' . esc_attr( $field['id'] ) . '">';

		if ( ! empty( $field['description'] ) ) {
            echo wp_kses_post( $field['description'] );;
        }

    echo '</span>

    </p>';
}

/**
 * Output a text input box.
 *
 * @param array $field
 */
function sealed_box_wp_text_input( $field ) {
	global $post_id, $post;

	$post_id                = empty( $post_id ) ? $post->ID : $post_id;
	$field['placeholder']   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'short';
	$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $post_id, sbx_prefix( $field['id'] ), true );
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['input_type']    = isset( $field['input_type'] ) ? $field['input_type'] : 'text';
	$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;
	$data_type              = empty( $field['data_type'] ) ? '' : $field['data_type'];

	switch ( $data_type ) {
		case 'url':
			$field['class'] .= ' sbx_input_url';
			$field['value']  = esc_url( $field['value'] );
			break;

		default:
			break;
	}

	echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
		<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

	if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
		echo sbx_help_tip( $field['description'] );
	}

	echo '<input type="' . esc_attr( $field['input_type'] ) . '" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['value'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" ' . sbx_implode_html_attributes( (array) sbx_get_var( $field['custom_attributes'], array() ) ) . ' /> ';

	if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
		echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
	}

	echo '</p>';
}

/**
 * Output a hidden input box.
 *
 * @param array $field
 */
function sealed_box_wp_hidden_input( $field ) {
	global $post_id, $post;

	$post_id        = empty( $post_id ) ? $post->ID : $post_id;
	$field['value'] = isset( $field['value'] ) ? $field['value'] : get_post_meta( $post_id, sbx_prefix( $field['id'] ), true );
	$field['class'] = isset( $field['class'] ) ? $field['class'] : '';

	echo '<input type="hidden" class="' . esc_attr( $field['class'] ) . '" name="' . esc_attr( $field['id'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['value'] ) . '" /> ';
}

/**
 * Output a textarea input box.
 *
 * @param array $field
 */
function sealed_box_wp_textarea_input( $field ) {
	global $post_id, $post;

	$post_id                = empty( $post_id ) ? $post->ID : $post_id;
	$field['placeholder']   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'short';
	$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $post_id, sbx_prefix( $field['id'] ), true );
	$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['rows']          = isset( $field['rows'] ) ? $field['rows'] : 2;
	$field['cols']          = isset( $field['cols'] ) ? $field['cols'] : 20;

	echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
		<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

	if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
		echo sbx_help_tip( $field['description'] );
	}

	echo '<textarea class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '"  name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" rows="' . esc_attr( $field['rows'] ) . '" cols="' . esc_attr( $field['cols'] ) . '" ' . sbx_implode_html_attributes( (array) sbx_get_var( $field['custom_attributes'], array() ) ) . '>' . esc_textarea( $field['value'] ) . '</textarea> ';

	if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
		echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
	}

	echo '</p>';
}

/**
 * Output a checkbox input box.
 *
 * @param array $field
 */
function sealed_box_wp_checkbox_input( $field ) {
	global $post_id, $post;

	$post_id                = empty( $post_id ) ? $post->ID : $post_id;
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'checkbox';
	$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $post_id, sbx_prefix( $field['id'] ), true );
	$field['cbvalue']       = isset( $field['cbvalue'] ) ? $field['cbvalue'] : 'yes';
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;

	echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
		<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

	if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
		echo sbx_help_tip( $field['description'] );
	}

	echo '<input type="checkbox" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['cbvalue'] ) . '" ' . checked( $field['value'], $field['cbvalue'], false ) . '  ' . sbx_implode_html_attributes( (array) sbx_get_var( $field['custom_attributes'], array() ) ) . '/> ';

	if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
		echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
	}

	echo '</p>';
}

/**
 * Output a select input box.
 *
 * @param array $field Data about the field to render.
 */
function sealed_box_wp_select_input( $field ) {
	global $post_id, $post;

	$post_id = empty( $post_id ) ? $post->ID : $post_id;
	$field   = wp_parse_args(
		$field, array(
			'class'             => 'select short',
			'style'             => '',
			'wrapper_class'     => '',
			'value'             => get_post_meta( $post_id, sbx_prefix( $field['id'] ), true ),
			'name'              => $field['id'],
			'desc_tip'          => false,
			'custom_attributes' => array(),
		)
	);

	$wrapper_attributes = array(
		'class' => "{$field['wrapper_class']} form-field {$field['id']}_field",
	);

	$label_attributes = array(
		'for' => $field['id'],
	);

	$field_attributes          = (array) sbx_get_var( $field['custom_attributes'], array() );
	$field_attributes['style'] = $field['style'];
	$field_attributes['id']    = $field['id'];
	$field_attributes['name']  = $field['name'];
	$field_attributes['class'] = $field['class'];

	$tooltip     = ! empty( $field['description'] ) && false !== $field['desc_tip'] ? $field['description'] : '';
	$description = ! empty( $field['description'] ) && false === $field['desc_tip'] ? $field['description'] : '';
	?>
	<p <?php echo sbx_implode_html_attributes( $wrapper_attributes ); // WPCS: XSS ok. ?>>
		<label <?php echo sbx_implode_html_attributes( $label_attributes ); // WPCS: XSS ok. ?>><?php echo wp_kses_post( $field['label'] ); ?></label>
		<?php if ( $tooltip ) : ?>
			<?php echo sbx_help_tip( $tooltip ); // WPCS: XSS ok. ?>
		<?php endif; ?>
		<select <?php echo sbx_implode_html_attributes( $field_attributes ); // WPCS: XSS ok. ?>>
			<?php
			foreach ( $field['options'] as $key => $value ) {
				echo '<option value="' . esc_attr( $key ) . '"' . sbx_selected( $key, $field['value'] ) . '>' . esc_html( $value ) . '</option>';
			}
			?>
		</select>
		<?php if ( $description ) : ?>
			<span class="description"><?php echo wp_kses_post( $description ); ?></span>
		<?php endif; ?>
	</p>
	<?php
}

/**
 * Output a select input box.
 *
 * @requires wp_enqueue_script( 'sbx-term-input' );
 * @requires wp_nonce_field( "radio_nonce-{$tax_name}", "_radio_nonce-{$tax_name}" );
 * @param array $field Data about the field to render.
 */
function sealed_box_wp_select_term_input( $field ) {
	global $post_id, $post;

	// print_r($field);
	$post_id  = empty( $post_id ) ? $post->ID : $post_id;
	$route    = sbx_get_route( $post_id );
	$tax_name = $field['taxonomy']->name ?? $field['taxonomy'];
	$taxonomy = get_taxonomy( $tax_name );
	$field    = wp_parse_args(
		$field, array(
			'class'             => 'select short',
			'style'             => '',
			'wrapper_class'     => '',
			'value'             => sbx_get_term_nicename( $route->__get( $tax_name ) ),
			'name'              => $field['id'],
			'desc_tip'          => false,
			'custom_attributes' => array(),
			'taxonomy'          => 'category',
		)
	);

	$wrapper_attributes = array(
		'id'    => "taxonomy-{$tax_name}",
		'class' => "{$field['wrapper_class']} form-field {$field['id']}_field option-buttons-for-taxonomies categorydiv",
	);

	$label_attributes = array(
		'for' => $field['id'],
	);

	$field_attributes                  = (array) sbx_get_var( $field['custom_attributes'], array() );
	$field_attributes['id']            = $field['id'];
	$field_attributes['name']          = $field['name'];
	$field_attributes['class']         = $field['class'];

	$order_attributes['id']            = "{$tax_name}checklist";
	$group_attributes['class']         = "{$tax_name}checklist";
	$order_attributes['data-wp-lists'] = "list:{$tax_name}";

	$tooltip     = ! empty( $field['description'] ) && false !== $field['desc_tip'] ? $field['description'] : '';
	$description = ! empty( $field['description'] ) && false === $field['desc_tip'] ? $field['description'] : '';
	?>
	<section <?php echo sbx_implode_html_attributes( $wrapper_attributes ); // WPCS: XSS ok. ?>>
		<p class="form-field">
			<label <?php echo sbx_implode_html_attributes( $label_attributes ); // WPCS: XSS ok. ?>><?php echo wp_kses_post( $field['label'] ); ?></label>
			<?php if ( $tooltip ) : ?>
				<?php echo sbx_help_tip( $tooltip ); // WPCS: XSS ok. ?>
			<?php endif; ?>
			<span id="<?php echo esc_attr( $tax_name ); ?>-all">
				<select <?php echo sbx_implode_html_attributes( $field_attributes ); // WPCS: XSS ok. ?>>
					<optgroup <?php echo sbx_implode_html_attributes( $order_attributes ); // WPCS: XSS ok. ?>>
					<?php foreach ( $field['options'] as $term) : ?>
						<option value="<?php echo esc_attr( $term->slug ); ?>" data-value="<?php echo esc_attr( $term->term_id ); ?>" data-title="<?php echo esc_attr( $term->name ); ?>" <?php echo sbx_selected( $field['value'], (array) $term ); ?>><?php echo esc_html( $term->name ); ?></option>
					<?php endforeach; ?>
					</optgroup>
				</select>
			</span>
		<?php if ( $description ) : ?>
			<span class="description"><?php echo wp_kses_post( $description ); ?></span>
		<?php endif; ?>
		<?
		wp_nonce_field( 'radio_nonce-' . $tax_name, '_radio_nonce-' . $tax_name ); if ( true || current_user_can( $taxonomy->cap->edit_terms ) ) : ?>
			<span id="<?php echo esc_attr( $tax_name ); ?>-adder" class="wp-hidden-children form-field">
				<a id="<?php echo esc_attr( $tax_name ); ?>-add-toggle" href="#<?php echo esc_attr( $tax_name ); ?>-add" class="hide-if-no-js taxonomy-add-new">
					<?php
						/* translators: %s: add new taxonomy label */
						printf( __( '+ %s' ), $taxonomy->labels->add_new_item );
					?>
				</a>
				<span id="<?php echo esc_attr( $tax_name ); ?>-add" class="category-add wp-hidden-child options_group">
					<label class="screen-reader-text" for="new<?php echo esc_attr( $tax_name ); ?>"><?php echo $taxonomy->labels->add_new_item; ?></label>
					<input type="text" name="new<?php echo esc_attr( $tax_name ); ?>" id="new<?php echo esc_attr( $tax_name ); ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $taxonomy->labels->new_item_name ); ?>" aria-required="true" />
					<label class="screen-reader-text" for="new<?php echo esc_attr( $tax_name ); ?>_parent">
						<?php echo esc_html( $taxonomy->labels->parent_item_colon ); ?>
					</label>
					<?php

					// Only add parent option for hierarchical taxonomies.
					if ( is_taxonomy_hierarchical( $tax_name ) ) :

						$parent_dropdown_args = array(
							'taxonomy'         => $tax_name,
							'hide_empty'       => 0,
							'name'             => "new{$tax_name}_parent",
							'orderby'          => 'name',
							'hierarchical'     => 1,
							'show_option_none' => "&mdash; {$taxonomy->labels->parent_item} &mdash;",
						);

						/**
						 * Filters the arguments for the taxonomy parent dropdown on the Post Edit page.
						 *
						 * @since 4.4.0
						 *
						 * @param array $parent_dropdown_args {
						 *     Optional. Array of arguments to generate parent dropdown.
						 *
						 *     @type string   $taxonomy         Name of the taxonomy to retrieve.
						 *     @type bool     $hide_if_empty    True to skip generating markup if no
						 *                                      categories are found. Default 0.
						 *     @type string   $tax_name         Value for the 'name' attribute
						 *                                      of the select element.
						 *                                      Default "new{$tax_name}_parent".
						 *     @type string   $orderby          Which column to use for ordering
						 *                                      terms. Default 'name'.
						 *     @type bool|int $hierarchical     Whether to traverse the taxonomy
						 *                                      hierarchy. Default 1.
						 *     @type string   $show_option_none Text to display for the "none" option.
						 *                                      Default "&mdash; {$parent} &mdash;",
						 *                                      where `$parent` is 'parent_item'
						 *                                      taxonomy label.
						 * }
						 */
						$parent_dropdown_args = apply_filters( 'post_edit_category_parent_dropdown_args', $parent_dropdown_args );

						wp_dropdown_categories( $parent_dropdown_args );

					endif;

					?>
					<input type="button" id="<?php echo esc_attr( $tax_name ); ?>-add-submit" data-wp-lists="add:<?php echo esc_attr( $tax_name ); ?>checklist:<?php echo esc_attr( $tax_name ); ?>-add" class="button category-add-submit" value="<?php echo esc_attr( $taxonomy->labels->add_new_item ); ?>" />
					<?php wp_nonce_field( 'add-' . $tax_name, '_ajax_nonce-add-' . $tax_name, false ); ?>
					<span id="<?php echo esc_attr( $tax_name ); ?>-ajax-response"></span>
				</span>
			</span>
		<?php endif; ?>
		</p>
	</section>
	<?php
}

/**
 * Output a checkboxes input box.
 *
 * @param array $field
 */
function sealed_box_wp_checkboxes_input( $field ) {
	global $post_id, $post;

	$post_id                = empty( $post_id ) ? $post->ID : $post_id;
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'checkbox';
	$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $post_id, sbx_prefix( $field['id'] ), true );
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;
	$fields_attributes      = (array) sbx_get_var( $field['custom_attributes'], array() );

	echo '<fieldset id="' . esc_attr( $field['id'] ) . '_field" class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><legend>' . wp_kses_post( $field['label'] ) . '</legend>';

	if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
		echo sbx_help_tip( $field['description'] );
	}

	echo '<ul class="sbx-checkboxes">';
	$val = is_array( $field['value'] ) ? array_map( 'esc_attr', $field['value'] ) : (array) esc_attr( $field['value'] );
	foreach ( $field['options'] as $key => $value ) {
		$readonly = false;
		$custom_attributes = array();
		if ( array_key_exists( $key, $fields_attributes ) ) {
			$custom_attributes = $fields_attributes[ $key ];
			$readonly = isset( $custom_attributes['readonly'] ) && 'readonly' === $custom_attributes['readonly'];
			if ( $readonly ) {
				$custom_attributes['disabled'] = 'disabled';
			}
		}

		echo '<li><label><input type="checkbox" name="' . esc_attr( $field['name'] ) . '[' . esc_attr( $key ) . ']" value="' . esc_attr( $key ) . '" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" ' . checked( in_array( esc_attr( $key ), $val ), true, false ) . ' ' . sbx_implode_html_attributes( $custom_attributes ) . ' /> ' . esc_html( $value ) . '</label> </li>'; // WPCS: XSS ok.
		if ($readonly) {
			sealed_box_wp_hidden_input(
				array(
					'id'   => $field['name'] . '[' . $key . ']',
					'value'  =>  $key
				)
			);
		}
	}
	echo '</ul>';

	if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
		echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
	}

	echo '</fieldset>';
}

/**
 * Output a radio input box.
 *
 * @param array $field
 */
function sealed_box_wp_radio_input( $field ) {
	global $post_id, $post;

	$post_id                = empty( $post_id ) ? $post->ID : $post_id;
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'select short';
	$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $post_id, sbx_prefix( $field['id'] ), true );
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;

	echo '<fieldset id="' . esc_attr( $field['id'] ) . '_field" class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><legend>' . wp_kses_post( $field['label'] ) . '</legend>';

	if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
		echo sbx_help_tip( $field['description'] );
	}

	echo '<ul class="sbx-radios">';

	foreach ( $field['options'] as $key => $value ) {

		echo '<li><label><input type="radio" name="' . esc_attr( $field['name'] ) . '" value="' . esc_attr( $key ) . '" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" ' . checked( esc_attr( $field['value'] ), esc_attr( $key ), false ) . ' /> ' . esc_html( $value ) . '</label> </li>';
	}
	echo '</ul>';

	if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
		echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
	}

	echo '</fieldset>';
}

function sealed_box_wp_repeater_input( $field ) {
	global $post_id, $post;

	$post_id                = empty( $post_id ) ? $post->ID : $post_id;
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'select short';
	$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $post_id, sbx_prefix( $field['id'] ), true );
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;

	echo '<fieldset id="' . esc_attr( $field['id'] ) . '_field" class="form-table ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><legend>' . wp_kses_post( $field['label'] ) . '</legend>';

	if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
		echo sbx_help_tip( $field['description'] );
	}

	echo '<table class="sbx-repeater">';
		echo '<thead>';
		echo '<tr>';
		foreach ( $field['fields'] as $sub_field ) {
			$sub_field['head_class']    = sbx_get_var( $sub_field['head_class'] );
			echo '<th class="' . esc_attr( $sub_field['head_class'] ) . '">'. $sub_field['label'] . '</th>';
		}
		echo '<th class="th-full"></th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( (array) $field['value'] as $index => $values ) {

			if ( ! is_array( $values ) ) {
				// continue;
				$values = (array) $values;
			}
			echo '<tr>';

			foreach ( $field['fields'] as $sub_field ) {

				$sub_name = isset( $sub_field['name'] ) ? $sub_field['name'] : $sub_field['id'];
				$sub_field['name']          = $field['name'] . '[' . $index . '][' . $sub_name . ']';
				$sub_field['description']   = sbx_get_var( $sub_field['description'] );
				$sub_field['wrapper_class'] = sbx_get_var( $sub_field['wrapper_class'] );
				$sub_field['class']         = sbx_get_var( $sub_field['class'] );
				$sub_field['default']       = sbx_get_var( $sub_field['default'] );
				$sub_field_value            = sbx_get_var( $values[ $sub_name ] ) ?? sbx_get_var( $sub_field['value'] );

				echo '<td class="' . esc_attr( $sub_field['wrapper_class'] ) . '">';

				switch ( $sub_field['type'] ) {
					case 'select':
						echo '<select name="' . esc_attr( $sub_field['name'] ) . '" class="' . esc_attr( $sub_field['class'] ) . '">';
							foreach ( $sub_field['options'] as $key => $value ) {
								echo '<option value="' . esc_attr( $key ) . '"' . sbx_selected( $key, $sub_field_value ) . '>' . esc_html( $value ) . '</option>';
							}
						echo '</select>';
						break;
					case 'checkbox':
					case 'radio':
						echo '<input type="' . esc_attr( $sub_field['type'] ) . '" name="' . esc_attr( $sub_field['name'] ) . '" value="' . esc_attr( $sub_field['default'] ) . '" ' . checked( $sub_field_value, $sub_field['default'], false ) . ' class="' . esc_attr( $sub_field['class'] ) . '"/> ' . esc_html( $sub_field['description'] );
						break;
					default:
					case 'text':
						echo '<input type="' . esc_attr( $sub_field['type'] ) . '" name="' . esc_attr( $sub_field['name'] ) . '" value="' . esc_attr( $sub_field_value ) . '" class="' . esc_attr( $sub_field['class'] ) . '"/> ';
				}

				echo '</td>';
			}

			echo '<td class="td-full"><input type="button" class="button row-remove" value="-"></td>';
			echo '</tr>';
		}

		echo '<tr id="' . esc_attr( $field['id'] ) . '_field_row" class="hidden">';

		foreach ( $field['fields'] as $sub_field ) {

			$sub_name = isset( $sub_field['name'] ) ? $sub_field['name'] : $sub_field['id'];
			$sub_field['name']          = $field['name'] . '[' . -1 . '][' . $sub_name . ']';
			$sub_field['description']   = sbx_get_var( $sub_field['description'] );
			$sub_field['wrapper_class'] = sbx_get_var( $sub_field['wrapper_class'] );
			$sub_field['class']         = sbx_get_var( $sub_field['class'] );
			$sub_field['default']       = sbx_get_var( $sub_field['default'] );
			$sub_field['value']         = sbx_get_var( $sub_field['value'] );

			echo '<td class="' . esc_attr( $sub_field['wrapper_class'] ) . '">';

			switch ( $sub_field['type'] ) {
				case 'select':
					echo '<select name="' . esc_attr( $sub_field['name'] ) . '" class="' . esc_attr( $sub_field['class'] ) . '">';
						foreach ( $sub_field['options'] as $key => $value ) {
							echo '<option value="' . esc_attr( $key ) . '"' . sbx_selected( $key, $sub_field['value'] ) . '>' . esc_html( $value ) . '</option>';
						}
					echo '</select>';
					break;
				case 'checkbox':
				case 'radio':
					echo '<input type="' . esc_attr( $sub_field['type'] ) . '" name="' . esc_attr( $sub_field['name'] ) . '" value="' . esc_attr( $sub_field['default'] ) . '" ' . checked( $sub_field['value'], $sub_field['default'], false ) . ' class="' . esc_attr( $sub_field['class'] ) . '"/> ' . esc_html( $sub_field['description'] );
					break;
				default:
				case 'text':
					echo '<input type="' . esc_attr( $sub_field['type'] ) . '" name="' . esc_attr( $sub_field['name'] ) . '" value="' . esc_attr( $sub_field['value'] ) . '" class="' . esc_attr( $sub_field['class'] ) . '"/>';
			}

			echo '</td>';
		}
		echo '</tr>';
		echo '</tbody>';

		if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
			echo '<tfoot>';
				echo '<tr>';
					echo '<td class="description" colspan="2">';
						echo '<small>' . wp_kses_post( $field['description'] ) . '</small>';
					echo '</td>';

					echo '<td class="td-full"><input type="button" class="button row-add" value="+"></td>';
				echo '</tr>';
			echo '</tfoot>';
		}

		echo '</table>';


	echo '</fieldset>';
}

function sbx_register_meta_box_tab( string $name, array $args, string $post_type ) {
	if ( ! did_action( $post_type . '_register_meta_box_tabs' ) ) {
		return new WP_Error('post_type_mismatch', 'This regisration is open for a different post type.' );
	}
	$tab_field_registration = SBX_Meta_Box_Tabs::get_instance( $post_type );
	if ( is_wp_error( $tab_field_registration ) ) {
		return $tab_field_registration;
	}
	return $tab_field_registration->register_tab( $name, $args );
}

function sbx_register_meta_box_groups( string $tab, array $args, string $post_type ) {
	if ( ! did_action( $post_type . '_register_meta_box_groups' ) ) {
		return new WP_Error('post_type_mismatch', 'This regisration is open for a different post type.' );
	}
	$tab_field_registration = SBX_Meta_Box_Tabs::get_instance( $post_type );
	if ( is_wp_error( $tab_field_registration ) ) {
		return $tab_field_registration;
	}
	return $tab_field_registration->register_groups( $tab, $args );
}

function sbx_register_meta_box_field( string $name, array $args, string $post_type, string $tab, int $group = null ) {
	if ( ! did_action( $post_type . '_register_meta_box_fields' ) ) {
		return new WP_Error('post_type_mismatch', 'This regisration is open for a different post type.' );
	}
	$tab_field_registration = SBX_Meta_Box_Tabs::get_instance( $post_type );
	if ( is_wp_error( $tab_field_registration ) ) {
		return $tab_field_registration;
	}
	return $tab_field_registration->register_field( $name, $args, $tab, $group );
}
