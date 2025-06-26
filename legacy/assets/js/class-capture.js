/**
 * Class Capture JavaScript for WeCoza Classes Plugin
 *
 * Handles the client-side functionality for the class capture form
 * Extracted from WeCoza theme for standalone plugin
 */

/**
 * Helper function to get day index from day name
 * @param {string} dayName - The name of the day (e.g., 'Monday')
 * @returns {number} - The index of the day (0-6, where 0 is Sunday)
 */
function getDayIndex(dayName) {
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    return days.indexOf(dayName);
}

/**
 * Helper function to get day of week from date
 * @param {Date} date - The date object
 * @returns {string} - The name of the day
 */
function getDayOfWeek(date) {
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    return days[date.getDay()];
}

/**
 * Helper function to format date as YYYY-MM-DD
 * @param {Date} date - The date object
 * @returns {string} - The formatted date string
 */
function formatDate(date) {
    const d = new Date(date);
    let month = '' + (d.getMonth() + 1);
    let day = '' + d.getDate();
    const year = d.getFullYear();

    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;

    return [year, month, day].join('-');
}

/**
 * Helper function to format time in 12-hour format
 * @param {Date|number} dateOrHour - Either a Date object or an hour (0-23)
 * @param {number} [minute] - The minute (0-59), only used if dateOrHour is a number
 * @returns {string} - The formatted time string (e.g., "6:30 AM")
 */
function formatTime(dateOrHour, minute) {
    let hours, minutes;

    if (dateOrHour instanceof Date) {
        // If a Date object is passed
        hours = dateOrHour.getHours();
        minutes = dateOrHour.getMinutes();
    } else {
        // If hour and minute are passed separately
        hours = dateOrHour;
        minutes = minute;
    }

    const ampm = hours >= 12 ? 'PM' : 'AM';
    const hour12 = hours % 12 || 12; // Convert 0 to 12 for 12 AM
    const minuteStr = minutes < 10 ? '0' + minutes : minutes;

    return hour12 + ':' + minuteStr + ' ' + ampm;
}

/**
 * Show a custom alert dialog instead of using the browser's native alert
 * @param {string} message - The message to display
 */
function showCustomAlert(message) {
    // Use jQuery instead of $ in the global scope
    // Create the modal HTML if it doesn't exist
    if (jQuery('#custom-alert-modal').length === 0) {
        const modalHTML = `
            <div class="modal fade" id="custom-alert-modal" tabindex="-1" aria-labelledby="custom-alert-modal-label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="custom-alert-modal-label">WeCoza Classes</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="custom-alert-message">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        jQuery('body').append(modalHTML);
    }

    // Set the message and show the modal
    jQuery('#custom-alert-message').text(message);
    const modal = new bootstrap.Modal(document.getElementById('custom-alert-modal'));
    modal.show();
}

(function($) {
    'use strict';

    // Global variables for holiday overrides
    var holidayOverrides = {};

    /**
     * Initialize the class capture form
     */
    window.initClassCaptureForm = function() {
        // Initialize the client-site relationship
        initializeClientSiteRelationship();

        // Initialize the site address lookup
        initializeSiteAddressLookup();

        // Initialize day of week restriction for start date
        initializeDayOfWeekRestriction();

        // Initialize the exam type toggle
        initializeExamTypeToggle();

        // Initialize the SETA field toggle
        initializeSetaToggle();

        // Initialize the date history functionality
        initializeDateHistory();

        // Initialize the QA visit dates functionality
        initializeQAVisits();

        // Initialize the class learners functionality
        initializeClassLearners();

        // Initialize the backup agents functionality
        initializeBackupAgents();

        // Set up synchronization listeners for exam learner options
        classes_setup_synchronization_listeners();

        // Initialize the agent replacements functionality
        initializeAgentReplacements();

        // Initialize form submission
        initializeFormSubmission();
    }

    /**
     * Initialize day of week restriction for start date
     */
    function initializeDayOfWeekRestriction() {
        const $scheduleDay = $('#schedule_day');
        const $startDate = $('#schedule_start_date');

        // Function to restrict start date based on selected day
        function restrictStartDateByDay() {
            const selectedDay = $scheduleDay.val();

            if (selectedDay && $scheduleDay.is(':visible')) {
                // Get the current date value
                const currentDate = $startDate.val();

                if (currentDate) {
                    const date = new Date(currentDate);
                    const dayIndex = getDayIndex(selectedDay);

                    // If the current date is not the selected day, find the next occurrence
                    if (date.getDay() !== dayIndex) {
                        // Find the next occurrence of the selected day
                        while (date.getDay() !== dayIndex) {
                            date.setDate(date.getDate() + 1);
                        }

                        // Update the start date
                        $startDate.val(date.toISOString().split('T')[0]);
                    }
                }
            }
        }

        // Apply restriction when day changes
        $scheduleDay.on('change', function() {
            restrictStartDateByDay();
        });

        // Apply restriction when date changes
        $startDate.on('change', function() {
            const selectedDay = $scheduleDay.val();

            if (selectedDay && $scheduleDay.is(':visible')) {
                const date = new Date($(this).val());
                const dayIndex = getDayIndex(selectedDay);

                if (date.getDay() !== dayIndex) {
                    // Find the next occurrence of the selected day
                    while (date.getDay() !== dayIndex) {
                        date.setDate(date.getDate() + 1);
                    }

                    // Update the start date
                    $(this).val(date.toISOString().split('T')[0]);
                }
            }
        });

        // Initial check
        restrictStartDateByDay();
    }

    /**
     * Initialize the client-site relationship
     * Filters the site_id dropdown based on the selected client_id
     */
    function initializeClientSiteRelationship() {
        // Get the client and site dropdowns
        const $clientDropdown = $("#client_id");
        const $siteDropdown = $("#site_id");

        // Add event listener to client dropdown
        $clientDropdown.on("change", function() {
            const selectedClientId = $(this).val();
            const selectedClientName = $(this).find("option:selected").text();

            // Reset site selection
            $siteDropdown.val("");

            // Show all optgroups and options initially
            $siteDropdown.find("optgroup").show();
            $siteDropdown.find("option").prop("disabled", false);

            // If a client is selected, hide other optgroups and disable their options
            if (selectedClientId) {
                $siteDropdown.find("optgroup").each(function() {
                    if ($(this).attr("label") !== selectedClientName) {
                        $(this).hide();
                        $(this).find("option").prop("disabled", true);
                    }
                });
            }

            // Trigger change event on site dropdown to update any dependent fields
            $siteDropdown.trigger("change");
        });

        // Initial filtering on page load if a client is already selected
        if ($clientDropdown.val()) {
            $clientDropdown.trigger("change");
        }
    }

    /**
     * Initialize the site address lookup
     */
    function initializeSiteAddressLookup() {
        // Get site addresses from localized script
        const siteAddresses = wecozaClass.siteAddresses || {};

        // On change of the select, look up the address and show/hide accordingly
        $("#site_id").on("change", function() {
            var selectedValue = $(this).val();
            var $addressWrapper = $("#address-wrapper");
            var $addressInput = $("#site_address");

            // If there's a matching address, populate and show
            if (siteAddresses[selectedValue]) {
                $addressInput.val(siteAddresses[selectedValue]);
                $addressWrapper.show();
            } else {
                // Otherwise clear and hide
                $addressInput.val("");
                $addressWrapper.hide();
            }
        });
    }

    /**
     * Initialize the SETA field toggle
     */
    function initializeSetaToggle() {
        // Handle SETA Funded selection change
        const setaFundedElement = document.getElementById('seta_funded');
        if (setaFundedElement) {
            setaFundedElement.addEventListener('change', function() {
            var setaContainer = document.getElementById('seta_container');
            var setaSelect = document.getElementById('seta_id');

            if (this.value === 'Yes') {
                // Show SETA field and make it required
                setaContainer.style.display = 'block';
                setaSelect.setAttribute('required', 'required');
            } else {
                // Hide SETA field and remove required attribute
                setaContainer.style.display = 'none';
                setaSelect.removeAttribute('required');
                // Reset the SETA selection
                setaSelect.value = '';
            }
            });
        }

        // Check initial state on page load
        var setaFunded = document.getElementById('seta_funded');
        if (setaFunded && setaFunded.value === 'Yes') {
            var setaContainer = document.getElementById('seta_container');
            var setaSelect = document.getElementById('seta_id');
            if (setaContainer && setaSelect) {
                setaContainer.style.display = 'block';
                setaSelect.setAttribute('required', 'required');
            }
        }
    }

    /**
     * Initialize the exam type toggle and exam learners functionality
     */
    function initializeExamTypeToggle() {
        // References to DOM elements
        const $examTypeContainer = $('#exam_type_container');
        const $examLearnersContainer = $('#exam_learners_container');
        const $examLearnerSelect = $('#exam_learner_select');
        const $addSelectedExamLearnersBtn = $('#add-selected-exam-learners-btn');
        const $examLearnersTable = $('#exam-learners-table');
        const $examLearnersTbody = $('#exam-learners-tbody');
        const $noExamLearnersMessage = $('#no-exam-learners-message');
        const $examLearnersData = $('#exam_learners');

        // Array to store exam learner data
        let examLearners = [];

        // Handle exam class selection change
        const examClassElement = document.getElementById('exam_class');
        if (examClassElement) {
            examClassElement.addEventListener('change', function() {
            if (this.value === 'Yes') {
                // Show exam type field and make it required
                $examTypeContainer.show();
                document.getElementById('exam_type').setAttribute('required', 'required');

                // Show exam learners container
                $examLearnersContainer.show();

                // Update the exam learner select options based on class learners
                updateExamLearnerOptions();

                // Also trigger global synchronization to ensure consistency
                setTimeout(function() {
                    if (typeof window.classes_sync_exam_learner_options === 'function') {
                        window.classes_sync_exam_learner_options();
                    }
                }, 100);

                // Validate exam learners immediately
                if (typeof validateExamLearners === 'function') {
                    validateExamLearners();
                }
            } else {
                // Hide exam type field and remove required attribute
                $examTypeContainer.hide();
                document.getElementById('exam_type').removeAttribute('required');

                // Hide exam learners container
                $examLearnersContainer.hide();

                // Clear exam learners data
                examLearners = [];
                updateExamLearnersData();

                // Remove any validation styling
                $('#exam_learners_container').removeClass('border-danger');
                $('#no-exam-learners-message').removeClass('alert-danger').addClass('alert-info');
            }
            });
        }

        // Function to update the exam learner select options based on class learners
        function updateExamLearnerOptions() {
            // Get the current class learners
            const classLearnersData = JSON.parse($('#class_learners_data').val() || '[]');

            // Clear the current options
            $examLearnerSelect.empty();

            // Add options for each class learner
            classLearnersData.forEach(function(learner) {
                // Skip learners that are already in the exam learners list - ensure both IDs are strings for comparison
                if (!examLearners.some(el => String(el.id) === String(learner.id))) {
                    $examLearnerSelect.append(`<option value="${learner.id}">${learner.name}</option>`);
                }
            });
        }

        // Function to update the hidden field with exam learner data
        function updateExamLearnersData() {
            // Stringify the exam learners array and store in hidden field
            const jsonData = JSON.stringify(examLearners);
            $examLearnersData.val(jsonData);

            // Update the exam learner count hidden input if it exists
            if ($('#exam_learner_count').length) {
                $('#exam_learner_count').val(examLearners.length);
            } else {
                // Create a hidden input for exam learner count if it doesn't exist
                $('<input>').attr({
                    type: 'hidden',
                    id: 'exam_learner_count',
                    name: 'exam_learner_count',
                    value: examLearners.length
                }).appendTo('#classes-form');
            }
        }

        // Function to update the exam learners table display
        function updateExamLearnersDisplay() {
            // Clear existing rows
            $examLearnersTbody.empty();

            if (examLearners.length === 0) {
                // Show no exam learners message and hide table
                $noExamLearnersMessage.removeClass('d-none');
                $examLearnersTable.addClass('d-none');
                return;
            }

            // Hide no exam learners message and show table
            $noExamLearnersMessage.addClass('d-none');
            $examLearnersTable.removeClass('d-none');

            // Add each exam learner to the table
            examLearners.forEach(function(learner) {
                const row = `
                    <tr>
                        <td>${learner.name}</td>
                        <td>
                            <button type="button" class="btn btn-outline-danger btn-sm remove-exam-learner-btn" data-learner-id="${learner.id}">
                                <i data-feather="trash-2" style="height:12.8px;width:12.8px;"></i>
                                Remove
                            </button>
                        </td>
                    </tr>
                `;
                $examLearnersTbody.append(row);
            });

            // Re-initialize feather icons for new buttons
            if (typeof feather !== 'undefined') {
                feather.replace();
            }

            console.log('Updated exam learners display with', examLearners.length, 'learners');
        }

        // Handle add selected exam learners button click
        $addSelectedExamLearnersBtn.on('click', function() {
            const selectedOptions = $examLearnerSelect.find('option:selected');

            if (selectedOptions.length === 0) {
                alert('Please select at least one learner to add for exams.');
                return;
            }

            console.log('Adding', selectedOptions.length, 'selected exam learners');

            // Add each selected learner
            selectedOptions.each(function() {
                const learnerId = $(this).val();
                const learnerName = $(this).text();

                // Convert to string to ensure consistent comparison
                const learnerIdStr = String(learnerId);

                // Check if learner is already added to exam learners - ensure both IDs are strings for comparison
                if (examLearners.some(learner => String(learner.id) === learnerIdStr)) {
                    console.log('Exam learner', learnerName, 'already added, skipping');
                    return;
                }

                // Add learner to exam learners array (store as string for consistency)
                const examLearnerData = {
                    id: learnerIdStr,
                    name: learnerName
                };

                examLearners.push(examLearnerData);
                console.log('Added exam learner:', examLearnerData);
            });

            // Update the display and data
            updateExamLearnersDisplay();
            updateExamLearnersData();
            updateExamLearnerOptions(); // Refresh dropdown to remove selected learners

            // Clear the selection
            $examLearnerSelect.val([]);
        });

        // Handle remove exam learner
        $(document).on('click', '.remove-exam-learner-btn', function() {
            const learnerId = $(this).data('learner-id');

            // Convert to string to ensure consistent comparison
            const learnerIdStr = String(learnerId);

            // Remove from exam learners array - ensure both IDs are strings for comparison
            examLearners = examLearners.filter(learner => String(learner.id) !== learnerIdStr);

            // Update display and data
            updateExamLearnersDisplay();
            updateExamLearnersData();
            updateExamLearnerOptions(); // Refresh dropdown to add back removed learner

            console.log('Removed exam learner', learnerIdStr);
        });

        // Load existing exam learner data if available (for editing)
        const existingExamData = $examLearnersData.val();
        if (existingExamData) {
            try {
                examLearners = JSON.parse(existingExamData);
                updateExamLearnersDisplay();
                console.log('Loaded existing exam learners:', examLearners);
            } catch (e) {
                console.error('Error parsing existing exam learner data:', e);
            }
        }
    }

    /**
     * Initialize placeholder functions for features not yet implemented
     */
    function initializeDateHistory() {
        // Placeholder for date history functionality
        console.log('Date history functionality initialized (placeholder)');
    }

    function initializeQAVisits() {
        // Placeholder for QA visits functionality
        console.log('QA visits functionality initialized (placeholder)');
    }

    function initializeClassLearners() {
        // Placeholder for class learners functionality
        console.log('Class learners functionality initialized (placeholder)');
    }

    function initializeBackupAgents() {
        // Placeholder for backup agents functionality
        console.log('Backup agents functionality initialized (placeholder)');
    }

    function initializeAgentReplacements() {
        // Placeholder for agent replacements functionality
        console.log('Agent replacements functionality initialized (placeholder)');
    }

    /**
     * Initialize form submission
     */
    function initializeFormSubmission() {
        const $form = $('#classes-form');
        const $submitButton = $form.find('button[type="submit"]');

        $form.on('submit', function(e) {
            e.preventDefault();

            // Show loading state
            const originalButtonText = $submitButton.html();
            $submitButton.html('<i class="bi bi-spinner-border me-2"></i>Saving...').prop('disabled', true);

            // Clear previous messages
            $('#form-messages').empty();

            // Prepare form data
            const formData = new FormData(this);

            // Add AJAX action
            formData.append('action', 'save_class');

            // Submit via AJAX
            $.ajax({
                url: wecozaClass.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showSuccessMessage(response.data.message || 'Class saved successfully!');

                        // Redirect if URL provided
                        const redirectUrl = $('#redirect_url').val();
                        if (redirectUrl) {
                            setTimeout(function() {
                                window.location.href = redirectUrl;
                            }, 1500);
                        } else {
                            // Reset form for new entry
                            $form[0].reset();
                        }
                    } else {
                        // Show error message
                        showErrorMessage(response.data || 'An error occurred while saving the class.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    showErrorMessage('A network error occurred. Please try again.');
                },
                complete: function() {
                    // Restore button state
                    $submitButton.html(originalButtonText).prop('disabled', false);
                }
            });
        });
    }

    /**
     * Show success message
     */
    function showSuccessMessage(message) {
        const alertHtml = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Success!</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $('#form-messages').html(alertHtml);

        // Scroll to message
        $('html, body').animate({
            scrollTop: $('#form-messages').offset().top - 100
        }, 500);
    }

    /**
     * Show error message
     */
    function showErrorMessage(message) {
        const alertHtml = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Error!</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $('#form-messages').html(alertHtml);

        // Scroll to message
        $('html, body').animate({
            scrollTop: $('#form-messages').offset().top - 100
        }, 500);
    }

    /**
     * Global function to synchronize exam learner options with class learners
     * This function can be called from other scripts when class learners change
     */
    window.classes_sync_exam_learner_options = function() {
        // Check if exam learner functionality is initialized and visible
        const $examLearnersContainer = $('#exam_learners_container');
        const $examLearnerSelect = $('#exam_learner_select');

        if (!$examLearnersContainer.is(':visible') || !$examLearnerSelect.length) {
            return; // Exit if exam learners section is not visible or doesn't exist
        }

        // Get the current class learners data
        const classLearnersData = JSON.parse($('#class_learners_data').val() || '[]');

        // Get current exam learners data to exclude them from options
        const examLearnersData = JSON.parse($('#exam_learners').val() || '[]');

        // Clear current options
        $examLearnerSelect.empty();

        // Add options for each class learner that's not already in exam learners - ensure both IDs are strings for comparison
        classLearnersData.forEach(function(learner) {
            if (!examLearnersData.some(el => String(el.id) === String(learner.id))) {
                $examLearnerSelect.append(`<option value="${learner.id}">${learner.name}</option>`);
            }
        });

        console.log('Synchronized exam learner options with', classLearnersData.length, 'class learners');
    };

    /**
     * Global function to remove a learner from exam learners when they're removed from class learners
     * This function can be called from other scripts for cascading removal
     */
    window.classes_remove_exam_learner = function(learnerId) {
        // Check if exam learner functionality is initialized
        const $examLearnersData = $('#exam_learners');

        if (!$examLearnersData.length) {
            return; // Exit if exam learners functionality doesn't exist
        }

        // Get current exam learners data
        let examLearnersData = JSON.parse($examLearnersData.val() || '[]');

        // Convert to string to ensure consistent comparison
        const learnerIdStr = String(learnerId);

        // Check if the learner is in the exam learners list
        const initialLength = examLearnersData.length;
        examLearnersData = examLearnersData.filter(learner => String(learner.id) !== learnerIdStr);

        if (examLearnersData.length < initialLength) {
            // Learner was found and removed, update the data
            $examLearnersData.val(JSON.stringify(examLearnersData));

            // Update exam learner count if it exists
            if ($('#exam_learner_count').length) {
                $('#exam_learner_count').val(examLearnersData.length);
            }

            // Refresh the exam learners display if the table is visible
            const $examLearnersTable = $('#exam-learners-table');
            if ($examLearnersTable.is(':visible')) {
                // Trigger a refresh of the exam learners display
                // This will be handled by the local updateExamLearnersDisplay function if available
                $(document).trigger('examLearnersChanged', [examLearnersData]);
            }

            console.log('Removed learner', learnerIdStr, 'from exam learners (cascading removal)');
        }
    };

    /**
     * Set up event listeners for automatic synchronization
     */
    function classes_setup_synchronization_listeners() {
        // Listen for custom event when class learners change
        $(document).on('classLearnersChanged', function(event, classLearners) {
            console.log('Class learners changed event received, synchronizing exam learner options');
            if (typeof window.classes_sync_exam_learner_options === 'function') {
                window.classes_sync_exam_learner_options();
            }
        });

        // Also listen for changes to the class_learners_data hidden field directly
        $(document).on('change', '#class_learners_data', function() {
            console.log('Class learners data field changed, synchronizing exam learner options');
            if (typeof window.classes_sync_exam_learner_options === 'function') {
                window.classes_sync_exam_learner_options();
            }
        });

        // Listen for custom event when exam learners change (for cascading removal display updates)
        $(document).on('examLearnersChanged', function(event, examLearners) {
            console.log('Exam learners changed event received, refreshing display');
            // Trigger a refresh of the exam learners display if the container is visible
            const $examLearnersContainer = $('#exam_learners_container');
            if ($examLearnersContainer.is(':visible')) {
                // Find and trigger the local updateExamLearnersDisplay function if available
                // This is a bit of a hack, but necessary since the function is scoped within initializeExamTypeToggle
                const $examLearnersTable = $('#exam-learners-table');
                const $examLearnersTbody = $('#exam-learners-tbody');
                const $noExamLearnersMessage = $('#no-exam-learners-message');

                if (examLearners.length === 0) {
                    // Show no exam learners message and hide table
                    $noExamLearnersMessage.removeClass('d-none');
                    $examLearnersTable.addClass('d-none');
                } else {
                    // Clear existing rows and rebuild
                    $examLearnersTbody.empty();

                    // Hide no exam learners message and show table
                    $noExamLearnersMessage.addClass('d-none');
                    $examLearnersTable.removeClass('d-none');

                    // Add each exam learner to the table
                    examLearners.forEach(function(learner) {
                        const row = `
                            <tr>
                                <td>${learner.name}</td>
                                <td>
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-exam-learner-btn" data-learner-id="${learner.id}">
                                        <i data-feather="trash-2" style="height:12.8px;width:12.8px;"></i>
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        `;
                        $examLearnersTbody.append(row);
                    });

                    // Re-initialize feather icons for new buttons
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                }
            }
        });
    }

    // Initialize when document is ready
    $(document).ready(function() {
        // Check if we're on a page with the class capture form
        if ($('#classes-form').length > 0) {
            initClassCaptureForm();
        }
    });

})(jQuery);
