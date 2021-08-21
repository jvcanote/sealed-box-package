<?php

/**
 * Product data meta box.
 *
 * @package SealedBox/Admin
 */

if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="panel-wrap service_route_data">

	<span id="route_box" class="hidden code">
		<small><?php echo rest_url(); ?></small>
		<span class="route_box-accessibility-group-wrapper">
			<select id="<?php echo esc_attr(sbx_unprefix(str_replace('_', '-', $route_namespace_object->name))); ?>" name="<?php echo esc_attr('radio_tax_input[' . $route_namespace_object->name . '][]'); ?>" class="tips" data-wp-lists="list:<?php echo esc_attr($route_namespace_object->name); ?>" data-tip="<?php echo esc_attr($route_namespace_object->labels->singular_name); ?>">
				<optgroup label="<?php echo esc_attr($route_namespace_object->labels->name); ?>">
					<?php foreach (sbx_get_namespace_terms() as $term) : ?>
						<option value="<?php echo esc_attr($term->term_id); ?>" data-value="<?php echo esc_attr($term->slug); ?>" data-title="<?php echo esc_attr($term->name); ?>" title="<?php echo esc_attr($term->description); ?>" <?php selected(sbx_get_route_term_id($route_object->ID, $route_namespace_object->name), $term->term_id, true); ?>><?php echo esc_html($term->name); ?></option>
					<?php endforeach; ?>
				</optgroup>
			</select>
			<small class="show_if_versioned_route">/</small>
			<select id="<?php echo esc_attr(sbx_unprefix(str_replace('_', '-', $route_version_object->name))); ?>" name="<?php echo esc_attr('radio_tax_input[' . $route_version_object->name . '][]'); ?>" class="tips show_if_versioned_route" data-wp-lists="list:<?php echo esc_attr($route_version_object->name); ?>" data-tip="<?php echo esc_attr($route_version_object->labels->singular_name); ?>">
				<optgroup label="<?php echo esc_attr($route_version_object->labels->name); ?>">
					<?php foreach (sbx_get_route_version_terms() as $term) : ?>
						<option value="<?php echo esc_attr($term->term_id); ?>" data-value="<?php echo esc_attr($term->slug); ?>" data-title="<?php echo esc_attr($term->name); ?>" title="<?php echo esc_attr($term->description); ?>" <?php selected(sbx_get_route_term_id($route_object->ID, $route_version_object->name), $term->term_id, true); ?>><?php echo esc_html($term->name); ?></option>
					<?php endforeach; ?>
				</optgroup>
			</select>
			<small>/service/</small>
			<select id="<?php echo esc_attr(sbx_unprefix(str_replace('_', '-', $route_type_object->name))); ?>" name="<?php echo esc_attr('radio_tax_input[' . $route_type_object->name . '][]'); ?>" class="tips" data-wp-lists="list:<?php echo esc_attr($route_type_object->name); ?>" data-tip="<?php echo esc_attr($route_type_object->labels->singular_name); ?>">
				<optgroup label="<?php echo esc_attr($route_type_object->labels->name); ?>">
					<?php foreach (sbx_get_route_type_terms() as $term) : ?>
						<option value="<?php echo esc_attr($term->term_id); ?>" data-value="<?php echo esc_attr($term->slug); ?>" data-title="<?php echo esc_attr($term->name); ?>" title="<?php echo esc_attr($term->description); ?>" <?php selected(sbx_get_route_term_id($route_object->ID, $route_type_object->name), $term->term_id, true); ?>><?php echo esc_html($term->name); ?></option>
					<?php endforeach; ?>
				</optgroup>
			</select>
			<small>.</small> <label for="post-name"> <input type="text" name="post-name" id="post-name" value="<?php echo esc_attr($route_object->name); ?>" autocomplete="off" spellcheck="false" data-tip="Route name" class="tips" /> </label> <small class="error-message">?</small>
		</span>
		<span class="route_box-accessibility-group-wrapper">
			<label for="message-param"> <input type="text" name="message-param" id="message-param" value="<?php echo esc_attr($route_object->message_param); ?>" autocomplete="off" spellcheck="false" data-tip="Encrypted parameter" class="tips" /> </label>
			<small><small>=&nbsp;</small>message</small><small class="error-message show_if_restricted_route">&nbsp;&amp;&nbsp;</small><small class="show_if_restricted_route"><small>_method</small>&nbsp;=</small>
			<select id="request-method" name="request-method" class="tips show_if_restricted_route" data-tip="Request method"><?php $endpoint_methods = (array) $route_object->request_method; $endpoint_methods['OPTIONS'] = 'OPTIONS'; $endpoint_methods = implode(',', $endpoint_methods); ?>
				<option value="<?php echo esc_attr($endpoint_methods); ?>" selected="selected"><?php echo esc_html($endpoint_methods); ?></option>
			</select>
		</span>
	</span>


	<ul class="service_route_data_tabs sbx-tabs">
		<?php foreach (self::get_data_meta_box_tab_settings() as $key => $tab) : ?>
			<li class="<?php echo esc_attr($key); ?>_options <?php echo esc_attr($key); ?>_tab <?php echo esc_attr(isset($tab['class']) ? implode(' ', (array) $tab['class']) : ''); ?>">
				<a href="#<?php echo esc_attr($tab['target']); ?>"><span><?php echo esc_html($tab['label']); ?></span></a>
			</li>
		<?php endforeach; ?>
		<?php do_action('sealed_box_service_route_write_panel_tabs'); ?>
	</ul>

	<?php
	self::output_tabs();
	do_action('sealed_box_service_route_data_panels');
	?>

	<div class="clear"></div>
</div>