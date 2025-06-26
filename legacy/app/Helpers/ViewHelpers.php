<?php
/**
 * ViewHelpers.php
 *
 * Helper functions for common view patterns in WeCoza Classes Plugin
 * Extracted from WeCoza theme for standalone plugin
 */

namespace WeCozaClasses\Helpers;

/**
 * Generate a select dropdown
 *
 * @param string $name Field name
 * @param array $options Options array with 'id' and 'name' keys
 * @param array $attributes Additional attributes (id, class, required, etc.)
 * @param string $selected Currently selected value
 * @param string $empty_label Label for the empty option (default: "Select")
 * @return string HTML for the select dropdown
 */
function select_dropdown($name, $options, $attributes = [], $selected = '', $empty_label = 'Select') {
    $id = isset($attributes['id']) ? $attributes['id'] : $name;
    $class = isset($attributes['class']) ? $attributes['class'] : 'form-select form-select-sm';
    $required = isset($attributes['required']) && $attributes['required'] ? 'required' : '';
    $multiple = isset($attributes['multiple']) && $attributes['multiple'] ? 'multiple' : '';
    $size = isset($attributes['size']) ? 'size="' . intval($attributes['size']) . '"' : '';
    
    // Remove processed attributes
    unset($attributes['id'], $attributes['class'], $attributes['required'], $attributes['multiple'], $attributes['size']);
    
    // Process any remaining attributes
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
    }
    
    $html = "<select id=\"{$id}\" name=\"{$name}\" class=\"{$class}\" {$required} {$multiple} {$size}{$attr_string}>\n";
    
    // Add empty option if not multiple
    if (!$multiple) {
        $html .= "   <option value=\"\">{$empty_label}</option>\n";
    }
    
    // Add options
    foreach ($options as $option) {
        // Handle selected state for both single and multiple selects
        $selected_attr = '';
        if (is_array($selected) && in_array($option['id'], $selected)) {
            $selected_attr = 'selected';
        } elseif (!is_array($selected) && $selected == $option['id']) {
            $selected_attr = 'selected';
        }
        
        $html .= "   <option value=\"" . esc_attr($option['id']) . "\" {$selected_attr}>" . esc_html($option['name']) . "</option>\n";
    }
    
    $html .= "</select>\n";
    
    return $html;
}

/**
 * Generate a select dropdown with optgroups
 *
 * @param string $name Field name
 * @param array $optgroups Array of optgroups with 'label' and 'options' keys
 * @param array $attributes Additional attributes
 * @param string $selected Currently selected value
 * @param string $empty_label Label for the empty option
 * @return string HTML for the select dropdown with optgroups
 */
function select_dropdown_with_optgroups($name, $optgroups, $attributes = [], $selected = '', $empty_label = 'Select') {
    $id = isset($attributes['id']) ? $attributes['id'] : $name;
    $class = isset($attributes['class']) ? $attributes['class'] : 'form-select form-select-sm';
    $required = isset($attributes['required']) && $attributes['required'] ? 'required' : '';
    
    // Remove processed attributes
    unset($attributes['id'], $attributes['class'], $attributes['required']);
    
    // Process any remaining attributes
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
    }
    
    $html = "<select id=\"{$id}\" name=\"{$name}\" class=\"{$class}\" {$required}{$attr_string}>\n";
    $html .= "   <option value=\"\">{$empty_label}</option>\n";
    
    foreach ($optgroups as $group) {
        $html .= "   <optgroup label=\"" . esc_attr($group['label']) . "\">\n";
        
        foreach ($group['options'] as $option) {
            $selected_attr = $selected == $option['id'] ? 'selected' : '';
            $html .= "      <option value=\"" . esc_attr($option['id']) . "\" {$selected_attr}>" . esc_html($option['name']) . "</option>\n";
        }
        
        $html .= "   </optgroup>\n";
    }
    
    $html .= "</select>\n";
    
    return $html;
}

/**
 * Generate a form input field with label and validation feedback
 *
 * @param string $type Input type (text, email, date, etc.)
 * @param string $name Field name
 * @param string $label Field label
 * @param array $attributes Additional attributes
 * @param string $value Current value
 * @param bool $required Whether the field is required
 * @param string $invalid_feedback Invalid feedback message
 * @param string $valid_feedback Valid feedback message
 * @return string HTML for the form input field
 */
function form_input($type, $name, $label, $attributes = [], $value = '', $required = false, $invalid_feedback = 'Please fill out this field.', $valid_feedback = 'Looks good!') {
    $id = isset($attributes['id']) ? $attributes['id'] : $name;
    $class = isset($attributes['class']) ? $attributes['class'] : 'form-control form-control-sm';
    $required_attr = $required ? 'required' : '';
    $required_span = $required ? ' <span class="text-danger">*</span>' : '';
    
    // Remove processed attributes
    unset($attributes['id'], $attributes['class']);
    
    // Process any remaining attributes
    $attr_string = '';
    foreach ($attributes as $key => $val) {
        $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($val) . '"';
    }
    
    $html = "<label for=\"{$id}\" class=\"form-label\">{$label}{$required_span}</label>\n";
    $html .= "<input type=\"{$type}\" id=\"{$id}\" name=\"{$name}\" class=\"{$class}\" value=\"" . esc_attr($value) . "\" {$required_attr}{$attr_string}>\n";
    $html .= "<div class=\"invalid-feedback\">{$invalid_feedback}</div>\n";
    $html .= "<div class=\"valid-feedback\">{$valid_feedback}</div>\n";
    
    return $html;
}

/**
 * Generate a textarea field with label and validation feedback
 *
 * @param string $name Field name
 * @param string $label Field label
 * @param array $attributes Additional attributes
 * @param string $value Current value
 * @param bool $required Whether the field is required
 * @param string $invalid_feedback Invalid feedback message
 * @param string $valid_feedback Valid feedback message
 * @return string HTML for the textarea field
 */
function form_textarea($name, $label, $attributes = [], $value = '', $required = false, $invalid_feedback = 'Please fill out this field.', $valid_feedback = 'Looks good!') {
    $id = isset($attributes['id']) ? $attributes['id'] : $name;
    $class = isset($attributes['class']) ? $attributes['class'] : 'form-control form-control-sm';
    $required_attr = $required ? 'required' : '';
    $required_span = $required ? ' <span class="text-danger">*</span>' : '';
    $rows = isset($attributes['rows']) ? $attributes['rows'] : '3';
    
    // Remove processed attributes
    unset($attributes['id'], $attributes['class'], $attributes['rows']);
    
    // Process any remaining attributes
    $attr_string = '';
    foreach ($attributes as $key => $val) {
        $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($val) . '"';
    }
    
    $html = "<label for=\"{$id}\" class=\"form-label\">{$label}{$required_span}</label>\n";
    $html .= "<textarea id=\"{$id}\" name=\"{$name}\" class=\"{$class}\" rows=\"{$rows}\" {$required_attr}{$attr_string}>" . esc_textarea($value) . "</textarea>\n";
    $html .= "<div class=\"invalid-feedback\">{$invalid_feedback}</div>\n";
    $html .= "<div class=\"valid-feedback\">{$valid_feedback}</div>\n";
    
    return $html;
}

/**
 * Generate a form group with label, input, and validation feedback
 *
 * @param string $type Input type (text, email, date, etc.)
 * @param string $name Field name
 * @param string $label Field label
 * @param string $col_class Column class (e.g., 'col-md-4')
 * @param array $attributes Additional attributes
 * @param string $value Current value
 * @param bool $required Whether the field is required
 * @param string $invalid_feedback Invalid feedback message
 * @param string $valid_feedback Valid feedback message
 * @return string HTML for the form group
 */
function form_group($type, $name, $label, $col_class = 'col-md-4', $attributes = [], $value = '', $required = false, $invalid_feedback = 'Please fill out this field.', $valid_feedback = 'Looks good!') {
    $html = "<div class=\"{$col_class}\">\n";
    
    if ($type === 'textarea') {
        $html .= form_textarea($name, $label, $attributes, $value, $required, $invalid_feedback, $valid_feedback);
    } elseif ($type === 'select') {
        $options = isset($attributes['options']) ? $attributes['options'] : [];
        unset($attributes['options']);
        $html .= "<label for=\"{$name}\" class=\"form-label\">{$label}" . ($required ? ' <span class="text-danger">*</span>' : '') . "</label>\n";
        $html .= select_dropdown($name, $options, array_merge($attributes, ['required' => $required]), $value);
        $html .= "<div class=\"invalid-feedback\">{$invalid_feedback}</div>\n";
        $html .= "<div class=\"valid-feedback\">{$valid_feedback}</div>\n";
    } else {
        $html .= form_input($type, $name, $label, $attributes, $value, $required, $invalid_feedback, $valid_feedback);
    }
    
    $html .= "</div>\n";
    
    return $html;
}

/**
 * Generate a form row with multiple form groups
 *
 * @param array $fields Array of field configurations
 * @return string HTML for the form row
 */
function form_row($fields) {
    $html = "<div class=\"row\">\n";
    
    foreach ($fields as $field) {
        $type = isset($field['type']) ? $field['type'] : 'text';
        $name = isset($field['name']) ? $field['name'] : '';
        $label = isset($field['label']) ? $field['label'] : '';
        $col_class = isset($field['col_class']) ? $field['col_class'] : 'col-md-4';
        $attributes = isset($field['attributes']) ? $field['attributes'] : [];
        $value = isset($field['value']) ? $field['value'] : '';
        $required = isset($field['required']) ? $field['required'] : false;
        $invalid_feedback = isset($field['invalid_feedback']) ? $field['invalid_feedback'] : 'Please fill out this field.';
        $valid_feedback = isset($field['valid_feedback']) ? $field['valid_feedback'] : 'Looks good!';
        
        $html .= form_group($type, $name, $label, $col_class, $attributes, $value, $required, $invalid_feedback, $valid_feedback);
    }
    
    $html .= "</div>\n";
    
    return $html;
}

/**
 * Generate a section divider
 *
 * @param string $classes Additional classes for the divider
 * @return string HTML for the section divider
 */
function section_divider($classes = '') {
    $default_classes = 'border-top border-opacity-25 border-3 border-discovery my-5 mx-1';
    $all_classes = $classes ? $default_classes . ' ' . $classes : $default_classes;
    
    return "<div class=\"{$all_classes}\"></div>\n";
}

/**
 * Generate a section header
 *
 * @param string $title Section title
 * @param string $description Section description
 * @param string $title_tag HTML tag for the title (h5, h4, etc.)
 * @return string HTML for the section header
 */
function section_header($title, $description = '', $title_tag = 'h5') {
    $html = "<{$title_tag} class=\"mb-3\">{$title}</{$title_tag}>\n";
    
    if ($description) {
        $html .= "<p class=\"text-muted small mb-3\">{$description}</p>\n";
    }
    
    return $html;
}

/**
 * Generate a button
 *
 * @param string $text Button text
 * @param string $type Button type (button, submit, reset)
 * @param string $style Button style (primary, secondary, danger, etc.)
 * @param array $attributes Additional attributes
 * @return string HTML for the button
 */
function button($text, $type = 'button', $style = 'primary', $attributes = []) {
    $class = isset($attributes['class']) ? 'btn btn-' . $style . ' ' . $attributes['class'] : 'btn btn-' . $style;
    $id = isset($attributes['id']) ? $attributes['id'] : '';
    
    // Remove processed attributes
    unset($attributes['class'], $attributes['id']);
    
    // Process any remaining attributes
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
    }
    
    return "<button type=\"{$type}\" " . ($id ? "id=\"{$id}\"" : "") . " class=\"{$class}\"{$attr_string}>{$text}</button>\n";
}
