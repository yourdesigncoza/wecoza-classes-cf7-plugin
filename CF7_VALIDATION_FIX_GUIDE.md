# Contact Form 7 Validation Fix Guide

## Problem Description

The WeCoza CF7 Plugin was experiencing validation errors with Contact Form 7 when using dynamically populated select fields. The specific issues were:

1. **"Undefined value was submitted through this field" error**
2. **`aria-invalid="true"` attribute persisting even with valid selections**
3. **Form submission failures despite legitimate user selections**

## Root Cause Analysis

### Primary Issue: CF7 5.9+ Schema-based Validation (SWV)
Contact Form 7 version 5.9 introduced Schema-based Validation with enum validation that creates a validation schema at form render time. This schema includes only the options present when the form is initially rendered. Dynamic options added via AJAX are not included in this schema, causing validation failures.

### Secondary Issue: Traditional Validation Hooks
Contact Form 7 validates select field options against the original form definition. Since our plugin populates options dynamically via AJAX after form rendering, CF7 doesn't recognize these dynamically added options as valid choices.

### Client-Side Issue
CF7's JavaScript validation sets `aria-invalid="true"` during client-side validation because it doesn't find the selected values in the original form definition.

## Solution Implementation

### 1. CF7 5.9+ Schema-based Validation (SWV) Fix

**File:** `includes/class-cf7-integration.php`

This is the **primary fix** for the "undefined value" error in CF7 5.9+:

```php
// Hook into schema creation to modify validation for dynamic fields
add_action('wpcf7_swv_create_schema', array($this, 'modify_swv_schema'), 10, 2);

// Replace default enum validation with custom validation
add_action('init', array($this, 'replace_enum_validation_with_custom'), 20);
```

**Key Features:**
- **Schema Modification**: Removes enum constraints for dynamic fields (`client_id`, `site_id`)
- **Custom Enum Validation**: Replaces CF7's enum validation with our own that skips dynamic fields
- **Selective Application**: Only affects forms containing our dynamic fields
- **Backward Compatibility**: Preserves normal validation for other fields

**Implementation Details:**
```php
public function modify_swv_schema($schema, $contact_form) {
    // Remove enum validation for dynamic fields
    foreach ($dynamic_field_names as $field_name) {
        if (isset($schema['properties'][$field_name]['enum'])) {
            unset($schema['properties'][$field_name]['enum']);
            // Replace with flexible pattern validation
            $schema['properties'][$field_name]['pattern'] = '^.+$';
        }
    }
    return $schema;
}
```

### 2. Server-Side Validation Hooks (Backup/Legacy)

**File:** `includes/class-cf7-integration.php`

Added comprehensive validation hooks:
```php
// Primary validation hooks for select fields
add_filter('wpcf7_validate_select', array($this, 'validate_dynamic_select'), 10, 2);
add_filter('wpcf7_validate_select*', array($this, 'validate_dynamic_select'), 10, 2);

// Backup validation hooks
add_filter('wpcf7_validate', array($this, 'validate_form_submission'), 10, 2);
add_filter('wpcf7_form_elements', array($this, 'modify_form_elements'), 10, 1);
```

**Key Features:**
- Database validation instead of form definition validation
- Comprehensive logging for debugging
- Proper handling of required vs optional fields
- Type conversion from strings to integers for database queries

### 3. Client-Side Validation Fixes

**File:** `assets/js/cf7-integration.js`

Added multiple client-side fixes:

#### A. Pre-submission Validation Fix
```javascript
$(document).on('wpcf7:beforesubmit', function(event) {
    // Fix validation before CF7 processes the form
    $form.find('select[data-wecoza-dynamic="true"], select[name="client_id"], select[name="site_id"]').each(function() {
        var $field = $(this);
        var fieldValue = $field.val();
        
        if (fieldValue && fieldValue !== '') {
            fixCF7ValidationState($field);
            ensureOptionExists($field, fieldValue);
        }
    });
});
```

#### B. Post-validation Fix
```javascript
$(document).on('wpcf7:invalid', function(event) {
    // Override CF7's validation for dynamic fields
    $form.find('select[name="client_id"], select[name="site_id"]').each(function() {
        var $field = $(this);
        var fieldValue = $field.val();
        
        if (fieldValue && fieldValue !== '') {
            fixCF7ValidationState($field);
        }
    });
});
```

#### C. Comprehensive State Fixing
```javascript
function fixCF7ValidationState($field) {
    var fieldValue = $field.val();
    if (fieldValue && fieldValue !== '') {
        // Remove CF7's validation classes and attributes
        $field.removeClass('wpcf7-not-valid wpcf7-validates-as-required')
              .attr('aria-invalid', 'false')
              .attr('aria-describedby', '');
        
        // Remove error messages and styling
        var $errorSpan = $field.siblings('.wpcf7-not-valid-tip');
        if ($errorSpan.length) {
            $errorSpan.remove();
        }
        
        // Clear CF7's internal validation tracking
        if (typeof wpcf7 !== 'undefined' && wpcf7.validation) {
            var fieldName = $field.attr('name');
            if (fieldName && wpcf7.validation.errors) {
                delete wpcf7.validation.errors[fieldName];
            }
        }
    }
}
```

#### D. Option Existence Guarantee
```javascript
function ensureOptionExists($field, value) {
    // Check if option already exists
    var $existingOption = $field.find('option[value="' + value + '"]');
    if ($existingOption.length > 0) {
        return; // Option already exists
    }
    
    // Add temporary option for validation
    var $option = $('<option></option>')
        .attr('value', value)
        .text(optionText)
        .attr('data-wecoza-temp', 'true');
    
    $field.append($option);
}
```

### 4. Form Element Modifications

Added data attributes to help with client-side identification:
```php
public function modify_form_elements($form_elements) {
    $form_elements = str_replace(
        'name="client_id"',
        'name="client_id" data-wecoza-dynamic="true"',
        $form_elements
    );
    
    $form_elements = str_replace(
        'name="site_id"',
        'name="site_id" data-wecoza-dynamic="true"',
        $form_elements
    );
    
    return $form_elements;
}
```

## Multi-Layer Approach

The plugin implements **multiple validation fixes** to ensure maximum compatibility:

### Layer 1: SWV Schema Modification (Primary)
- **Target**: CF7 5.9+ with Schema-based Validation
- **Method**: Modifies validation schema to remove enum constraints for dynamic fields
- **Priority**: Highest - addresses root cause

### Layer 2: Custom Enum Validation Replacement
- **Target**: CF7 5.9+ with complete enum validation control
- **Method**: Removes default enum validation and replaces with custom implementation
- **Priority**: High - comprehensive solution

### Layer 3: Traditional Validation Hooks (Backup)
- **Target**: All CF7 versions, especially pre-5.9
- **Method**: Uses `wpcf7_validate_select` hooks for field-level validation
- **Priority**: Medium - backward compatibility

### Layer 4: Client-Side Fixes
- **Target**: Browser-side validation issues
- **Method**: JavaScript fixes for `aria-invalid` and validation state
- **Priority**: Medium - user experience enhancement

### Layer 5: Form Element Modification
- **Target**: Form rendering and identification
- **Method**: Adds data attributes for better field identification
- **Priority**: Low - supporting functionality

## Testing and Verification

### Debug Logging
Added comprehensive logging throughout the validation process:
- Server-side validation method calls
- Field values being validated
- Validation results (pass/fail)
- Client-side state changes

### Test Coverage
The test file (`test-cf7-integration.php`) now includes:
- Validation method testing with real data
- Invalid value testing
- Integration status verification

## Usage Instructions

### For Developers

1. **Enable Debug Logging:**
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. **Check Validation Logs:**
   Look for entries starting with "WeCoza CF7:" in your debug log.

3. **Browser Console:**
   Check for validation fix messages in the browser console.

### For Users

The fix is automatic and transparent. Users should now experience:
- ✅ No "undefined value" errors for valid selections
- ✅ Proper `aria-invalid="false"` attributes after valid selections
- ✅ Successful form submissions with legitimate data
- ✅ Proper validation feedback for invalid selections

## Troubleshooting

### If Issues Persist

1. **Check Plugin Activation:**
   Ensure the WeCoza CF7 Plugin is active and properly loaded.

2. **Verify Field Names:**
   Confirm field names are exactly `client_id` and `site_id`.

3. **Database Connectivity:**
   Use the test page to verify database connectivity.

4. **JavaScript Errors:**
   Check browser console for JavaScript errors that might prevent proper operation.

5. **CF7 Version Compatibility:**
   Ensure you're using a compatible version of Contact Form 7.

### Debug Steps

1. **Enable Logging:**
   Turn on WordPress debug logging to see validation attempts.

2. **Check Network Tab:**
   Verify AJAX requests are completing successfully.

3. **Inspect Form Elements:**
   Confirm data attributes are being added to form fields.

4. **Test Validation Methods:**
   Use the test page to verify validation methods work correctly.

## Benefits

### User Experience
- Seamless form submission without validation errors
- Proper accessibility attributes
- Clear validation feedback

### Developer Experience
- Comprehensive debugging information
- Maintainable code structure
- Extensible validation framework

### System Integrity
- Database validation ensures data integrity
- Proper error handling prevents system failures
- Backward compatibility with existing forms

## GitHub Commit Analysis Insights

### CF7 Enum Validation Source Code Analysis

**Reference**: [CF7 Commit ad49e80](https://github.com/rocklobster-in/contact-form-7/commit/ad49e8085cdcb7aba60ef9afd364394b7774b5d2)

This commit introduced the exact enum validation function causing our issues:

```php
// The problematic function from CF7's commit
add_action('wpcf7_swv_create_schema', 'wpcf7_swv_add_select_enum_rules', 20, 2);

function wpcf7_swv_add_select_enum_rules($schema, $contact_form) {
    $tags = $contact_form->scan_form_tags(array('basetype' => array('select')));

    // Problem: Only collects values present at form render time
    $tag_values = array_merge(
        (array) $tag->values,           // Static form definition values
        (array) $tag->get_data_option() // Data options from form
    );

    // Creates enum validation that rejects our dynamic values
    $schema->add_rule(wpcf7_swv_create_rule('enum', array(
        'accept' => array_values($field_values), // Missing our AJAX values!
        'error' => "Undefined value was submitted through this field."
    )));
}
```

### Key Insights from Commit Analysis

1. **Exact Function Target**: Our fix targets the precise function (`wpcf7_swv_add_select_enum_rules`) causing the issue
2. **Correct Hook and Priority**: We're using the exact hook (`wpcf7_swv_create_schema`) and priority (`20`) from the original
3. **Timing Issue Confirmed**: Function runs at form render time, before our AJAX populates options
4. **Error Message Match**: "Undefined value was submitted through this field." - exact match with our error

### Enhanced Implementation

Based on the commit analysis, our solution now uses **CF7's exact implementation approach** but skips dynamic fields:

```php
public function add_custom_select_enum_rules($schema, $contact_form) {
    // Use CF7's exact tag scanning approach
    $tags = $contact_form->scan_form_tags(array('basetype' => array('select')));

    // Use CF7's exact value collection logic
    $values = array_reduce($tags, function ($values, $tag) {
        // Skip our dynamic fields - validated by database instead
        if (in_array($tag->name, array('client_id', 'site_id'))) {
            return $values;
        }

        // Apply CF7's exact logic for non-dynamic fields
        $tag_values = array_merge(
            (array) $tag->values,
            (array) $tag->get_data_option()
        );

        if ($tag->has_option('first_as_label')) {
            $tag_values = array_slice($tag_values, 1);
        }

        return $values;
    }, array());

    // Apply CF7's exact validation rule creation
    foreach ($values as $field => $field_values) {
        $schema->add_rule(wpcf7_swv_create_rule('enum', array(
            'field' => $field,
            'accept' => array_values($field_values),
            'error' => $contact_form->filter_message(
                __("Undefined value was submitted through this field.", 'contact-form-7')
            ),
        )));
    }
}
```

This approach ensures **maximum compatibility** with CF7's validation system while solving our dynamic field issues.

## Conclusion

This comprehensive fix addresses both the server-side and client-side validation issues that were preventing proper form submission with dynamically populated select fields. The solution maintains CF7's validation framework while extending it to properly handle dynamic content.

**The GitHub commit analysis confirms our approach is targeting the exact root cause and using the most compatible implementation method.**
