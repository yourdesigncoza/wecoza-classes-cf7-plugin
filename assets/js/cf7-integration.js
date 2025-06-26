/**
 * WeCoza CF7 Integration JavaScript
 *
 * Handles frontend functionality for dynamic CF7 select fields
 *
 * @package WeCozaClassesCF7
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Initialize CF7 integration
     */
    function initCF7Integration() {
        // Handle client select field changes
        handleClientSelectChanges();
        
        // Add refresh functionality if needed
        addRefreshFunctionality();
        
        // Initialize form validation enhancements
        initFormValidation();
    }
    
    /**
     * Handle client select field changes and cascading site updates
     */
    function handleClientSelectChanges() {
        $(document).on('change', 'select[name="client_id"]', function() {
            var $clientSelect = $(this);
            var clientId = $clientSelect.val();
            var $siteSelect = $('select[name="site_id"]');

            // Add visual feedback for selection
            if (clientId) {
                $clientSelect.removeClass('is-invalid').addClass('is-valid');

                // Fix CF7 client-side validation issue
                fixCF7ValidationState($clientSelect);

                // Update site dropdown based on client selection
                updateSiteDropdown(clientId, $siteSelect);

                // Trigger custom event for other scripts to listen to
                $(document).trigger('wecoza:client_selected', {
                    clientId: clientId,
                    clientName: $clientSelect.find('option:selected').text(),
                    selectElement: $clientSelect
                });
            } else {
                $clientSelect.removeClass('is-valid is-invalid');

                // Reset and disable site dropdown
                resetSiteDropdown($siteSelect);
            }
        });

        // Handle site select changes
        $(document).on('change', 'select[name="site_id"]', function() {
            var $siteSelect = $(this);
            var siteId = $siteSelect.val();
            var $addressField = $('input[name="site_address"]');

            // Add visual feedback for selection
            if (siteId) {
                $siteSelect.removeClass('is-invalid').addClass('is-valid');

                // Fix CF7 client-side validation issue
                fixCF7ValidationState($siteSelect);

                // Update address field based on site selection
                updateAddressField(siteId, $addressField);

                // Trigger custom event for other scripts to listen to
                $(document).trigger('wecoza:site_selected', {
                    siteId: siteId,
                    siteName: $siteSelect.find('option:selected').text(),
                    selectElement: $siteSelect
                });
            } else {
                $siteSelect.removeClass('is-valid is-invalid');

                // Clear address field when no site is selected
                clearAddressField($addressField);
            }
        });
    }
    
    /**
     * Add refresh functionality for client data
     */
    function addRefreshFunctionality() {
        // Add refresh button if debug mode is enabled
        if (typeof wecozaCF7 !== 'undefined' && wecozaCF7.debug) {
            $('select[data-wecoza-dynamic="client_id"]').each(function() {
                var $select = $(this);
                var $refreshBtn = $('<button type="button" class="btn btn-sm btn-outline-secondary wecoza-refresh-clients" title="Refresh client list">↻</button>');
                
                $refreshBtn.insertAfter($select);
                
                $refreshBtn.on('click', function(e) {
                    e.preventDefault();
                    refreshClientData($select, $refreshBtn);
                });
            });
        }
    }
    
    /**
     * Refresh client data via AJAX
     */
    function refreshClientData($select, $button) {
        if (typeof wecozaCF7 === 'undefined') {
            console.error('WeCoza CF7: Configuration not found');
            return;
        }
        
        var originalText = $button.text();
        $button.prop('disabled', true).text(wecozaCF7.strings.refreshing);
        
        $.ajax({
            url: wecozaCF7.ajax_url,
            type: 'POST',
            data: {
                action: 'wecoza_refresh_clients',
                nonce: wecozaCF7.nonce
            },
            success: function(response) {
                if (response.success && response.data.clients) {
                    // Store clients data globally for validation fixes
                    window.wecozaClients = response.data.clients;

                    updateSelectOptions($select, response.data.clients);

                    // Show success message
                    showMessage(response.data.message, 'success');
                } else {
                    showMessage(wecozaCF7.strings.error, 'error');
                }
            },
            error: function() {
                showMessage(wecozaCF7.strings.error, 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    }
    
    /**
     * Update site dropdown based on selected client
     */
    function updateSiteDropdown(clientId, $siteSelect) {
        if (typeof wecozaCF7 === 'undefined') {
            console.error('WeCoza CF7: Configuration not found');
            return;
        }

        // Show loading state
        $siteSelect.prop('disabled', true).html('<option value="">Loading sites...</option>');

        $.ajax({
            url: wecozaCF7.ajax_url,
            type: 'POST',
            data: {
                action: 'wecoza_get_sites_by_client',
                client_id: clientId,
                nonce: wecozaCF7.nonce
            },
            success: function(response) {
                if (response.success && response.data.sites) {
                    // Store sites data globally for validation fixes
                    window.wecozaSites = response.data.sites;

                    populateSiteOptions($siteSelect, response.data.sites);
                } else {
                    $siteSelect.html('<option value="">No sites available</option>');
                }
                $siteSelect.prop('disabled', false);
            },
            error: function() {
                $siteSelect.html('<option value="">Error loading sites</option>');
                $siteSelect.prop('disabled', false);
                showMessage(wecozaCF7.strings.error, 'error');
            }
        });
    }

    /**
     * Reset site dropdown to initial state
     */
    function resetSiteDropdown($siteSelect) {
        $siteSelect.html('<option value="">Select Site</option>')
                   .prop('disabled', true)
                   .removeClass('is-valid is-invalid');

        // Also clear the address field when site dropdown is reset
        var $addressField = $('input[name="site_address"]');
        clearAddressField($addressField);
    }

    /**
     * Populate site select options
     */
    function populateSiteOptions($siteSelect, sites) {
        // Clear existing options
        $siteSelect.empty();

        // Add default option
        $siteSelect.append('<option value="">Select Site</option>');

        // Add site options with address data stored as data attributes
        $.each(sites, function(index, site) {
            var $option = $('<option></option>')
                .attr('value', site.id)
                .attr('data-address', site.address || '')
                .text(site.name);

            $siteSelect.append($option);
        });
    }

    /**
     * Update address field based on selected site
     */
    function updateAddressField(siteId, $addressField) {
        if (!$addressField.length) {
            return; // Address field not present in form
        }

        // Get the selected option and its address data
        var $selectedOption = $('select[name="site_id"] option[value="' + siteId + '"]');
        var address = $selectedOption.attr('data-address') || '';

        // Update the address field
        $addressField.val(address);

        // Add visual feedback
        if (address) {
            $addressField.removeClass('is-invalid').addClass('is-valid');
        } else {
            $addressField.removeClass('is-valid is-invalid');
        }

        // Trigger custom event
        $(document).trigger('wecoza:address_updated', {
            siteId: siteId,
            address: address,
            addressElement: $addressField
        });
    }

    /**
     * Clear address field
     */
    function clearAddressField($addressField) {
        if (!$addressField.length) {
            return; // Address field not present in form
        }

        $addressField.val('')
                     .removeClass('is-valid is-invalid');

        // Trigger custom event
        $(document).trigger('wecoza:address_cleared', {
            addressElement: $addressField
        });
    }

    /**
     * Fix CF7 client-side validation state for dynamic fields
     *
     * CF7's client-side validation sets aria-invalid="true" because it doesn't
     * recognize dynamically populated options. This function fixes that.
     */
    function fixCF7ValidationState($field) {
        if (!$field.length) {
            return;
        }

        // Remove CF7's invalid state if field has a valid selection
        var fieldValue = $field.val();
        if (fieldValue && fieldValue !== '') {
            // Remove CF7's validation classes and attributes
            $field.removeClass('wpcf7-not-valid wpcf7-validates-as-required')
                  .attr('aria-invalid', 'false')
                  .attr('aria-describedby', '');

            // Remove any validation error messages
            var $errorSpan = $field.siblings('.wpcf7-not-valid-tip');
            if ($errorSpan.length) {
                $errorSpan.remove();
            }

            // Remove error styling from parent wrapper
            var $wrapper = $field.closest('.wpcf7-form-control-wrap');
            if ($wrapper.length) {
                $wrapper.removeClass('wpcf7-not-valid-tip-wrap');
            }

            // Add our own valid state
            $field.addClass('is-valid');

            // Force update the field's validation state in CF7's internal tracking
            if (typeof wpcf7 !== 'undefined' && wpcf7.validation) {
                // Clear any existing validation errors for this field
                var fieldName = $field.attr('name');
                if (fieldName && wpcf7.validation.errors) {
                    delete wpcf7.validation.errors[fieldName];
                }
            }

            console.log('WeCoza CF7: Fixed validation state for field:', $field.attr('name'), 'value:', fieldValue);
        }
    }

    /**
     * Ensure that a select field has the specified option value
     *
     * This prevents CF7 from rejecting valid selections that were
     * added dynamically after the form was rendered.
     */
    function ensureOptionExists($field, value) {
        if (!$field.length || !value) {
            return;
        }

        // Check if option already exists
        var $existingOption = $field.find('option[value="' + value + '"]');
        if ($existingOption.length > 0) {
            return; // Option already exists
        }

        // Get the text for the option from our cached data or use the value
        var optionText = value;
        var fieldName = $field.attr('name');

        if (fieldName === 'client_id' && window.wecozaClients) {
            var client = window.wecozaClients.find(function(c) { return c.id == value; });
            if (client) {
                optionText = client.name;
            }
        } else if (fieldName === 'site_id' && window.wecozaSites) {
            var site = window.wecozaSites.find(function(s) { return s.id == value; });
            if (site) {
                optionText = site.name;
            }
        }

        // Add the option temporarily
        var $option = $('<option></option>')
            .attr('value', value)
            .text(optionText)
            .attr('data-wecoza-temp', 'true');

        $field.append($option);

        console.log('WeCoza CF7: Added temporary option for validation:', fieldName, value, optionText);
    }

    /**
     * Update select field options (for client refresh)
     */
    function updateSelectOptions($select, clients) {
        var currentValue = $select.val();

        // Clear existing options except the first (blank) option
        $select.find('option:not(:first)').remove();

        // Add new options
        $.each(clients, function(index, client) {
            var $option = $('<option></option>')
                .attr('value', client.id)
                .text(client.name);

            $select.append($option);
        });

        // Restore previous selection if it still exists
        if (currentValue) {
            $select.val(currentValue);
        }

        // Trigger change event
        $select.trigger('change');
    }
    
    /**
     * Initialize form validation enhancements
     */
    function initFormValidation() {
        // Enhance CF7 validation for client and site selects
        $(document).on('wpcf7:invalid', function(event) {
            var $form = $(event.target);
            var $clientSelect = $form.find('select[name="client_id"]');
            var $siteSelect = $form.find('select[name="site_id"]');

            // Validate client selection
            if ($clientSelect.length && !$clientSelect.val()) {
                $clientSelect.addClass('is-invalid');

                // Add custom error message if not present
                if (!$clientSelect.siblings('.wpcf7-not-valid-tip').length) {
                    var $errorMsg = $('<span class="wpcf7-not-valid-tip">Please select a client.</span>');
                    $clientSelect.after($errorMsg);
                }
            }

            // Validate site selection
            if ($siteSelect.length && !$siteSelect.val()) {
                $siteSelect.addClass('is-invalid');

                // Add custom error message if not present
                if (!$siteSelect.siblings('.wpcf7-not-valid-tip').length) {
                    var $errorMsg = $('<span class="wpcf7-not-valid-tip">Please select a site.</span>');
                    $siteSelect.after($errorMsg);
                }
            }
        });

        // Handle CF7 form submission - fix validation before CF7 processes it
        $(document).on('wpcf7:beforesubmit', function(event) {
            var $form = $(event.target);

            // Pre-validate our dynamic fields before CF7's validation
            $form.find('select[data-wecoza-dynamic="true"], select[name="client_id"], select[name="site_id"]').each(function() {
                var $field = $(this);
                var fieldValue = $field.val();

                // If field has a valid selection, mark it as valid
                if (fieldValue && fieldValue !== '') {
                    fixCF7ValidationState($field);

                    // Also manually add the option to the select if it's not there
                    // This prevents CF7 from thinking the value is invalid
                    ensureOptionExists($field, fieldValue);
                }
            });
        });

        // Handle CF7 validation events for dynamic fields
        $(document).on('wpcf7:invalid', function(event) {
            var $form = $(event.target);

            // Fix validation for our dynamic fields after CF7 validation
            $form.find('select[name="client_id"], select[name="site_id"]').each(function() {
                var $field = $(this);
                var fieldValue = $field.val();

                // If field has a valid selection, override CF7's validation
                if (fieldValue && fieldValue !== '') {
                    fixCF7ValidationState($field);
                }
            });
        });

        // Clear validation on valid submission
        $(document).on('wpcf7:mailsent', function(event) {
            var $form = $(event.target);
            $form.find('select[name="client_id"], select[name="site_id"], input[name="site_address"]').removeClass('is-invalid is-valid');
        });

        // Custom validation before form submission
        $(document).on('wpcf7:beforesubmit', function(event) {
            var $form = $(event.target);
            var $clientSelect = $form.find('select[name="client_id"]');
            var $siteSelect = $form.find('select[name="site_id"]');
            var isValid = true;

            // Check if client is selected
            if ($clientSelect.length && !$clientSelect.val()) {
                $clientSelect.addClass('is-invalid');
                isValid = false;
            }

            // Check if site is selected (only if site field exists)
            if ($siteSelect.length && !$siteSelect.val()) {
                $siteSelect.addClass('is-invalid');
                isValid = false;
            }

            // If validation fails, prevent submission
            if (!isValid) {
                event.preventDefault();
                return false;
            }
        });
    }
    
    /**
     * Show message to user
     */
    function showMessage(message, type) {
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var $alert = $('<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
            '</div>');
        
        // Find a suitable container for the message
        var $container = $('.wpcf7-form').first();
        if (!$container.length) {
            $container = $('body');
        }
        
        $container.prepend($alert);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $alert.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Debug function to log client, site, and address events
     */
    function debugSelections() {
        $(document).on('wecoza:client_selected', function(event, data) {
            if (typeof console !== 'undefined' && console.log) {
                console.log('WeCoza CF7: Client selected', data);
            }
        });

        $(document).on('wecoza:site_selected', function(event, data) {
            if (typeof console !== 'undefined' && console.log) {
                console.log('WeCoza CF7: Site selected', data);
            }
        });

        $(document).on('wecoza:address_updated', function(event, data) {
            if (typeof console !== 'undefined' && console.log) {
                console.log('WeCoza CF7: Address updated', data);
            }
        });

        $(document).on('wecoza:address_cleared', function(event, data) {
            if (typeof console !== 'undefined' && console.log) {
                console.log('WeCoza CF7: Address cleared', data);
            }
        });
    }
    
    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        // Check if we're on a page with CF7 forms
        if ($('.wpcf7-form').length > 0) {
            initCF7Integration();
            
            // Enable debug logging if in debug mode
            if (typeof wecozaCF7 !== 'undefined' && wecozaCF7.debug) {
                debugSelections();
            }
        }
    });
    
    /**
     * Re-initialize after CF7 form updates (for AJAX forms)
     */
    $(document).on('wpcf7:ready', function() {
        initCF7Integration();
    });
    
})(jQuery);
