/**
 * Class Schedule Form JavaScript for WeCoza Classes Plugin
 *
 * Handles the client-side functionality for the class schedule form.
 * Extracted from WeCoza theme for standalone plugin
 */
(function($) {
    'use strict';

    /**
     * Initialize the class schedule form
     */
    function initClassScheduleForm() {
        // Initialize schedule pattern selection
        initSchedulePatternSelection();

        // Initialize time selection and duration calculation
        initTimeSelection();

        // Initialize time validation for existing data
        validateAllTimeSelections();

        // Initialize date fields
        initDateFields();

        // Initialize per-day time controls based on current selection
        updatePerDayTimeControls();

        // Initialize exception dates
        initExceptionDates();

        // Initialize date history (stop/restart dates)
        initDateHistory();

        // Initialize holiday overrides
        initHolidayOverrides();

        // Initialize schedule data updates
        initScheduleDataUpdates();

        // Initialize exam class toggle functionality
        initExamClassToggle();

        // Initialize learner selection functionality
        initLearnerSelection();

        // Initialize backup agents functionality
        initBackupAgents();

        // Load existing schedule data for editing (backward compatibility)
        loadExistingScheduleData();

        // Initial auto-population and calculations after all initialization is complete
        setTimeout(function() {
            // Auto-populate schedule start date if class start date exists but schedule start date is empty
            initAutoPopulateScheduleStartDate();

            recalculateEndDate();

            // Check for holidays on initial load
            const startDate = $('#schedule_start_date').val();
            const endDate = $('#schedule_end_date').val();
            if (startDate) {
                checkForHolidays(startDate, endDate);
            }
        }, 100);
    }

    /**
     * Initialize auto-population of schedule start date on page load
     */
    function initAutoPopulateScheduleStartDate() {
        const $classStartDate = $('#class_start_date');
        const $scheduleStartDate = $('#schedule_start_date');

        const classStartDate = $classStartDate.val();
        const scheduleStartDate = $scheduleStartDate.val();

        // Auto-populate if class start date exists but schedule start date is empty
        if (classStartDate && !scheduleStartDate) {
            $scheduleStartDate.val(classStartDate);
        }
    }

    /**
     * Initialize schedule pattern selection
     */
    function initSchedulePatternSelection() {
        const $schedulePattern = $('#schedule_pattern');
        const $daySelection = $('#day-selection-container');
        const $dayOfMonth = $('#day-of-month-container');

        $schedulePattern.on('change', function() {
            const pattern = $(this).val();

            // Reset visibility
            $daySelection.addClass('d-none');
            $dayOfMonth.addClass('d-none');

            // Show appropriate fields based on pattern
            if (pattern === 'weekly' || pattern === 'biweekly') {
                $daySelection.removeClass('d-none');
                $('#schedule_day_of_month').removeAttr('required');

                // Update per-day time controls based on current day selection
                updatePerDayTimeControls();
            } else if (pattern === 'monthly') {
                $dayOfMonth.removeClass('d-none');
                $('#schedule_day_of_month').attr('required', 'required');

                // For monthly pattern, always show single time controls
                // since monthly scheduling doesn't use multiple days
                resetToSingleTimeControls();
            } else {
                // For custom or no pattern, reset to single time controls
                resetToSingleTimeControls();
            }

            // Update schedule data
            updateScheduleData();

            // Check for holidays that conflict with the new pattern
            const startDate = $('#schedule_start_date').val();
            const endDate = $('#schedule_end_date').val();
            if (startDate) {
                checkForHolidays(startDate, endDate);
            }

            // Recalculate end date when pattern changes
            recalculateEndDate();
        });

        // Initialize day selection buttons
        $('#select-all-days').on('click', function() {
            $('.schedule-day-checkbox').prop('checked', true);
            validateDaySelection(); // Update required attribute only
            updatePerDayTimeControls(); // Add conditional display logic
            updateScheduleData();
            restrictStartDateBySelectedDays();
            recalculateEndDate();
        });

        $('#clear-all-days').on('click', function() {
            $('.schedule-day-checkbox').prop('checked', false);
            validateDaySelection(); // Update required attribute only
            updatePerDayTimeControls(); // Add conditional display logic
            updateScheduleData();
        });

        // Handle day checkbox changes - using event delegation in case checkboxes are loaded dynamically
        $(document).on('change', '.schedule-day-checkbox', function() {
            validateDaySelection(); // Update required attribute only
            updatePerDayTimeControls(); // Add conditional display logic
            updateScheduleData();
            restrictStartDateBySelectedDays();

            // Check for holidays that conflict with the new day selection
            const startDate = $('#schedule_start_date').val();
            const endDate = $('#schedule_end_date').val();
            if (startDate) {
                checkForHolidays(startDate, endDate);
            }

            // Recalculate end date when day changes
            recalculateEndDate();
        });

        // Handle day of month selection changes
        $('#schedule_day_of_month').on('change', function() {
            updateScheduleData();

            // Check for holidays that conflict with the new day selection
            const startDate = $('#schedule_start_date').val();
            const endDate = $('#schedule_end_date').val();
            if (startDate) {
                checkForHolidays(startDate, endDate);
            }

            // Recalculate end date when day changes
            recalculateEndDate();
        });
    }

    /**
     * Validate that at least one day is selected
     * Returns validation result without showing immediate feedback
     */
    function validateDaySelection() {
        const anyDaySelected = $('.schedule-day-checkbox:checked').length > 0;
        const $daySelectionContainer = $('#day-selection-container');

        // Manage required attribute on first checkbox for Bootstrap validation
        if (!$daySelectionContainer.hasClass('d-none')) {
            const $firstCheckbox = $('.schedule-day-checkbox').first();
            if ($firstCheckbox.length > 0) {
                if (anyDaySelected) {
                    // Remove required attribute when any day is selected
                    $firstCheckbox.removeAttr('required');
                    $firstCheckbox[0].setCustomValidity('');
                } else {
                    // Add required attribute when no days are selected
                    $firstCheckbox.attr('required', 'required');
                    $firstCheckbox[0].setCustomValidity('Please select at least one day.');
                }
            }
        }

        return anyDaySelected;
    }

    /**
     * Show validation feedback for day selection when form is validated
     */
    function showDaySelectionValidationFeedback() {
        const anyDaySelected = $('.schedule-day-checkbox:checked').length > 0;
        const $daySelectionContainer = $('#day-selection-container');

        if (!$daySelectionContainer.hasClass('d-none')) {
            const $invalidFeedback = $daySelectionContainer.find('.invalid-feedback');
            const $validFeedback = $daySelectionContainer.find('.valid-feedback');

            if (anyDaySelected) {
                // Show valid feedback, hide invalid feedback
                $invalidFeedback.addClass('d-none');
                $validFeedback.removeClass('d-none');
            } else {
                // Show invalid feedback, hide valid feedback
                $invalidFeedback.removeClass('d-none');
                $validFeedback.addClass('d-none');
            }
        }
    }

    /**
     * Get all selected days
     */
    function getSelectedDays() {
        const selectedDays = [];
        $('.schedule-day-checkbox:checked').each(function() {
            selectedDays.push($(this).val());
        });
        return selectedDays;
    }

    /**
     * Helper function to get day name from day index
     */
    function getDayName(dayIndex) {
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return days[dayIndex];
    }

    /**
     * Helper function to get day index from day name
     */
    function getDayIndex(dayName) {
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return days.indexOf(dayName);
    }

    /**
     * Update per-day time controls based on selected days
     */
    function updatePerDayTimeControls() {
        const selectedDays = getSelectedDays();
        const $singleTimeControls = $('#single-time-controls');
        const $perDayTimeControls = $('#per-day-time-controls');
        const $perDaySectionsContainer = $('#per-day-sections-container');

        // Show/hide controls based on number of selected days
        if (selectedDays.length === 0) {
            // No days selected: hide both controls
            $singleTimeControls.addClass('d-none');
            $perDayTimeControls.addClass('d-none');

            // Clear per-day sections
            $perDaySectionsContainer.empty();
        } else {
            // Any days selected: show per-day time controls (even for single day)
            $singleTimeControls.addClass('d-none');
            $perDayTimeControls.removeClass('d-none');

            // Generate per-day sections
            generatePerDaySections(selectedDays);
        }
    }

    /**
     * Generate per-day time sections for selected days
     */
    function generatePerDaySections(selectedDays) {
        const $container = $('#per-day-sections-container');
        const $template = $('#day-time-section-template');

        // Clear existing sections
        $container.empty();

        // Create section for each selected day
        selectedDays.forEach(function(day, index) {
            const $section = $template.clone();
            $section.removeClass('d-none').removeAttr('id');
            $section.attr('data-day', day);

            // Update day name in header
            $section.find('.day-name').text(day);

            // Update data-day attributes for form elements
            $section.find('.day-start-time').attr('data-day', day).attr('name', 'day_start_time[' + day + ']');
            $section.find('.day-end-time').attr('data-day', day).attr('name', 'day_end_time[' + day + ']');

            // Show copy button only on first day
            if (index === 0) {
                $section.find('.copy-to-all-btn').show();
            } else {
                $section.find('.copy-to-all-btn').hide();
            }

            $container.append($section);
        });

        // Initialize event handlers for new sections
        initPerDayTimeHandlers();

        // Initialize time selection for each new section
        initPerDayTimeSections();

        // Initialize validation indicators
        setTimeout(() => {
            updateTimeValidationIndicators();
        }, 100);
    }

    /**
     * Initialize time selection for per-day sections
     * Ensures each section has proper time selection behavior
     */
    function initPerDayTimeSections() {
        $('.per-day-time-section').each(function() {
            const day = $(this).attr('data-day');
            const $startTime = $(this).find('.day-start-time');
            const $endTime = $(this).find('.day-end-time');
            const $durationContainer = $(this).find('.day-duration-display');

            // Initially hide duration display
            $durationContainer.addClass('d-none');

            // Calculate initial duration if times are already set
            if ($startTime.val() && $endTime.val()) {
                calculatePerDayDuration(day);
            }
        });
    }

    /**
     * Initialize event handlers for per-day time controls
     * Enhanced to use unified time validation and duration calculation
     */
    function initPerDayTimeHandlers() {
        // Handle time changes for validation and duration calculation
        $('.day-start-time, .day-end-time').off('change.perday').on('change.perday', function() {
            const day = $(this).attr('data-day');
            const $section = $('.per-day-time-section[data-day="' + day + '"]');
            const $startTime = $section.find('.day-start-time');
            const $endTime = $section.find('.day-end-time');

            // Validate individual time selection
            validateTimeSelection($startTime, $endTime);

            // Calculate duration using unified calculation
            calculatePerDayDuration(day);

            // Perform comprehensive validation across all days
            setTimeout(() => {
                validateAllTimeSelections();
                updateTimeValidationIndicators();
            }, 100); // Small delay to ensure all DOM updates are complete

            // Update schedule data
            updateScheduleData();

            // Recalculate end date when per-day times change
            recalculateEndDate();
        });

        // Handle copy to all days functionality
        $('.copy-to-all-btn').off('click.perday').on('click.perday', function() {
            const $section = $(this).closest('.per-day-time-section');
            const sourceDay = $section.attr('data-day');
            const startTime = $section.find('.day-start-time').val();
            const endTime = $section.find('.day-end-time').val();

            if (startTime && endTime) {
                // Validate source times first
                const $startTimeElement = $section.find('.day-start-time');
                const $endTimeElement = $section.find('.day-end-time');

                if (validateTimeSelection($startTimeElement, $endTimeElement)) {
                    // Copy times to all other day sections
                    $('.per-day-time-section').not($section).each(function() {
                        const $targetStartTime = $(this).find('.day-start-time');
                        const $targetEndTime = $(this).find('.day-end-time');

                        $targetStartTime.val(startTime);
                        $targetEndTime.val(endTime);

                        // Trigger change events to update validation and duration
                        $targetStartTime.trigger('change');
                        $targetEndTime.trigger('change');
                    });
                }
            }
        });
    }

    /**
     * Calculate duration for a specific day using unified calculation
     * Only shows duration display when both time fields are valid
     */
    function calculatePerDayDuration(day) {
        const $section = $('.per-day-time-section[data-day="' + day + '"]');
        const startTime = $section.find('.day-start-time').val();
        const endTime = $section.find('.day-end-time').val();
        const $durationDisplay = $section.find('.duration-value');
        const $durationContainer = $section.find('.day-duration-display');
        const $startTimeElement = $section.find('.day-start-time');
        const $endTimeElement = $section.find('.day-end-time');

        if (startTime && endTime) {
            // Validate the time selection first
            const isValid = validateTimeSelection($startTimeElement, $endTimeElement);

            if (isValid) {
                // Use unified duration calculation
                const duration = calculateTimeDuration(startTime, endTime);
                $durationDisplay.text(duration.toFixed(1));

                // Show duration display only when valid
                $durationContainer.removeClass('d-none');
            } else {
                // Hide duration display when invalid
                $durationContainer.addClass('d-none');
            }
        } else {
            // Hide duration display when fields are empty
            $durationContainer.addClass('d-none');
            $durationDisplay.text('-');
        }
    }

    /**
     * Reset to no time controls (for monthly/custom patterns)
     */
    function resetToSingleTimeControls() {
        const $singleTimeControls = $('#single-time-controls');
        const $perDayTimeControls = $('#per-day-time-controls');
        const $perDaySectionsContainer = $('#per-day-sections-container');

        // Hide both time control sections
        $singleTimeControls.addClass('d-none');
        $perDayTimeControls.addClass('d-none');

        // Clear per-day sections
        $perDaySectionsContainer.empty();
    }

    /**
     * Initialize time selection and duration calculation
     * Enhanced to work with both single-day and per-day time controls
     */
    function initTimeSelection() {
        const $startTime = $('#schedule_start_time');
        const $endTime = $('#schedule_end_time');
        const $duration = $('#schedule_duration');

        // Only initialize single time controls if the fields exist
        if ($startTime.length && $endTime.length) {
            // Initialize single time controls
            initSingleTimeControls($startTime, $endTime, $duration);

            // Calculate initial duration on page load for existing data
            if ($duration.length) {
                calculateSingleDuration($startTime, $endTime, $duration);
            }
        }

        // Initialize per-day time controls (will be called when sections are generated)
        // This is handled by initPerDayTimeHandlers() but we ensure consistency here
    }

    /**
     * Initialize single time controls with validation and duration calculation
     */
    function initSingleTimeControls($startTime, $endTime, $duration) {
        // Calculate duration when times change
        $startTime.add($endTime).on('change', function() {
            // Validate time selection
            validateTimeSelection($startTime, $endTime);

            // Calculate duration
            calculateSingleDuration($startTime, $endTime, $duration);

            // Update visual indicators
            setTimeout(() => {
                updateTimeValidationIndicators();
            }, 50);

            // Update schedule data
            updateScheduleData();

            // Recalculate end date when duration changes
            recalculateEndDate();
        });
    }

    /**
     * Calculate duration for single time controls
     */
    function calculateSingleDuration($startTime, $endTime, $duration) {
        const startTime = $startTime.val();
        const endTime = $endTime.val();

        if ($duration && $duration.length) {
            if (startTime && endTime) {
                const calculatedDuration = calculateTimeDuration(startTime, endTime);
                $duration.val(calculatedDuration.toFixed(1));
            } else {
                $duration.val('');
            }
        }
    }

    /**
     * Unified time duration calculation function
     * Used by both single and per-day time controls
     */
    function calculateTimeDuration(startTime, endTime) {
        if (!startTime || !endTime) {
            return 0;
        }

        // Parse times
        const [startHour, startMinute] = startTime.split(':').map(Number);
        const [endHour, endMinute] = endTime.split(':').map(Number);

        // Calculate duration in hours
        let durationHours = endHour - startHour;
        let durationMinutes = endMinute - startMinute;

        if (durationMinutes < 0) {
            durationHours--;
            durationMinutes += 60;
        }

        // Return duration as decimal hours
        return durationHours + (durationMinutes / 60);
    }

    /**
     * Validate time selection (start time must be before end time)
     * Enhanced with additional validation rules
     */
    function validateTimeSelection($startTimeElement, $endTimeElement) {
        const startTime = $startTimeElement.val();
        const endTime = $endTimeElement.val();

        // Clear any existing validation states
        $startTimeElement.removeClass('is-invalid is-valid');
        $endTimeElement.removeClass('is-invalid is-valid');

        if (startTime && endTime) {
            const [startHour, startMinute] = startTime.split(':').map(Number);
            const [endHour, endMinute] = endTime.split(':').map(Number);

            const startMinutes = startHour * 60 + startMinute;
            const endMinutes = endHour * 60 + endMinute;

            // Validation 1: Start time must be before end time
            if (startMinutes >= endMinutes) {
                $endTimeElement.addClass('is-invalid');
                $endTimeElement.siblings('.invalid-feedback').text('End time must be after start time.');
                return false;
            }

            // Validation 2: Minimum duration check (at least 30 minutes)
            const durationMinutes = endMinutes - startMinutes;
            if (durationMinutes < 30) {
                $endTimeElement.addClass('is-invalid');
                $endTimeElement.siblings('.invalid-feedback').text('Class duration must be at least 30 minutes.');
                return false;
            }

            // Validation 3: Maximum duration check (no more than 8 hours)
            if (durationMinutes > 480) { // 8 hours = 480 minutes
                $endTimeElement.addClass('is-invalid');
                $endTimeElement.siblings('.invalid-feedback').text('Class duration cannot exceed 8 hours.');
                return false;
            }

            // All validations passed
            $startTimeElement.addClass('is-valid');
            $endTimeElement.addClass('is-valid');
            $endTimeElement.siblings('.invalid-feedback').text('Please select an end time.');
            return true;
        }

        return true; // If either time is empty, don't show validation error yet
    }

    /**
     * Get all time data from the form (single or per-day)
     * Returns an object with the current time configuration
     */
    function getAllTimeData() {
        const selectedDays = getSelectedDays();

        if (selectedDays.length === 0) {
            // No days selected - return empty single mode
            return {
                mode: 'single',
                startTime: '',
                endTime: '',
                duration: ''
            };
        } else {
            // Any days selected: use per-day time mode (even for single day)
            const perDayTimes = {};
            $('.per-day-time-section').each(function() {
                const day = $(this).attr('data-day');
                const startTime = $(this).find('.day-start-time').val();
                const endTime = $(this).find('.day-end-time').val();

                if (startTime && endTime) {
                    perDayTimes[day] = {
                        startTime: startTime,
                        endTime: endTime,
                        duration: calculateTimeDuration(startTime, endTime)
                    };
                }
            });

            return {
                mode: 'per-day',
                perDayTimes: perDayTimes
            };
        }
    }

    /**
     * Validate all time selections (single or per-day)
     * Enhanced with overlap detection and comprehensive validation
     * Returns true if all times are valid, false otherwise
     */
    function validateAllTimeSelections() {
        const selectedDays = getSelectedDays();
        let allValid = true;

        if (selectedDays.length === 0) {
            // No days selected - no validation needed
            allValid = true;
        } else {
            // Any days selected: validate per-day time controls
            allValid = validatePerDayTimeSelections();
        }

        return allValid;
    }

    /**
     * Comprehensive validation for per-day time selections
     * Includes individual validation and overlap detection
     */
    function validatePerDayTimeSelections() {
        let allValid = true;
        const dayTimes = [];

        // First pass: validate each day individually and collect time data
        $('.per-day-time-section').each(function() {
            const day = $(this).attr('data-day');
            const $startTime = $(this).find('.day-start-time');
            const $endTime = $(this).find('.day-end-time');
            const startTime = $startTime.val();
            const endTime = $endTime.val();

            if (startTime && endTime) {
                // Validate individual day times
                if (!validateTimeSelection($startTime, $endTime)) {
                    allValid = false;
                }

                // Collect time data for overlap detection
                dayTimes.push({
                    day: day,
                    startTime: startTime,
                    endTime: endTime,
                    startMinutes: timeToMinutes(startTime),
                    endMinutes: timeToMinutes(endTime),
                    $section: $(this)
                });
            }
        });

        // Second pass: check for potential scheduling conflicts
        if (allValid && dayTimes.length > 1) {
            allValid = validateTimeConsistency(dayTimes);
        }

        return allValid;
    }

    /**
     * Convert time string to minutes since midnight
     */
    function timeToMinutes(timeStr) {
        const [hours, minutes] = timeStr.split(':').map(Number);
        return hours * 60 + minutes;
    }

    /**
     * Validate time consistency across multiple days
     * Checks for potential scheduling issues
     */
    function validateTimeConsistency(dayTimes) {
        let allValid = true;
        const warnings = [];

        // Check for extremely different durations that might indicate errors
        const durations = dayTimes.map(dt => dt.endMinutes - dt.startMinutes);
        const minDuration = Math.min(...durations);
        const maxDuration = Math.max(...durations);

        if (maxDuration - minDuration > 120) { // More than 2 hours difference
            warnings.push('Large duration differences between days detected. Please verify times are correct.');
        }

        // Check for very early or very late times that might be errors
        const veryEarly = dayTimes.some(dt => dt.startMinutes < 360); // Before 6:00 AM
        const veryLate = dayTimes.some(dt => dt.endMinutes > 1320); // After 10:00 PM

        if (veryEarly || veryLate) {
            warnings.push('Some classes are scheduled very early or very late. Please verify times are correct.');
        }

        // Display warnings if any
        if (warnings.length > 0) {
            displayTimeValidationWarnings(warnings);
        } else {
            clearTimeValidationWarnings();
        }

        return allValid;
    }

    /**
     * Display time validation warnings
     */
    function displayTimeValidationWarnings(warnings) {
        // Remove any existing warning
        $('#time-validation-warnings').remove();

        // Create warning container
        const warningHtml = `
            <div id="time-validation-warnings" class="alert alert-subtle-warning mt-4 alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Time Validation Warnings:</strong>
                <ul class="mb-0 mt-1">
                    ${warnings.map(warning => `<li>${warning}</li>`).join('')}
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        // Insert after per-day time controls
        $('#per-day-time-controls').after(warningHtml);
    }

    /**
     * Clear time validation warnings
     */
    function clearTimeValidationWarnings() {
        $('#time-validation-warnings').remove();
    }

    /**
     * Validate that all required time fields are filled
     * Returns validation result with details
     */
    function validateRequiredTimeFields() {
        const selectedDays = getSelectedDays();
        const result = {
            isValid: true,
            missingFields: [],
            message: ''
        };

        if (selectedDays.length === 0) {
            // No days selected - no validation needed
            result.isValid = true;
        } else {
            // Any days selected: check each selected day has times
            selectedDays.forEach(day => {
                const $section = $('.per-day-time-section[data-day="' + day + '"]');
                const startTime = $section.find('.day-start-time').val();
                const endTime = $section.find('.day-end-time').val();

                if (!startTime) {
                    result.missingFields.push(`${day} Start Time`);
                    $section.find('.day-start-time').addClass('is-invalid');
                }
                if (!endTime) {
                    result.missingFields.push(`${day} End Time`);
                    $section.find('.day-end-time').addClass('is-invalid');
                }
            });
        }

        if (result.missingFields.length > 0) {
            result.isValid = false;
            result.message = `Please fill in the following required fields: ${result.missingFields.join(', ')}`;
        }

        return result;
    }

    /**
     * Get validation summary for all time inputs
     * Useful for form submission validation
     */
    function getTimeValidationSummary() {
        const requiredFieldsResult = validateRequiredTimeFields();
        const allTimesValid = validateAllTimeSelections();

        return {
            isValid: requiredFieldsResult.isValid && allTimesValid,
            requiredFields: requiredFieldsResult,
            timeValidation: allTimesValid,
            canSubmit: requiredFieldsResult.isValid && allTimesValid
        };
    }

    /**
     * Update visual validation indicators for time controls
     */
    function updateTimeValidationIndicators() {
        const selectedDays = getSelectedDays();

        if (selectedDays.length === 0) {
            // No days selected - no indicators needed
            return;
        } else {
            // Any days selected: update per-day time control indicators
            updatePerDayTimeIndicators();
        }
    }

    /**
     * Update validation indicators for single time controls
     */
    function updateSingleTimeIndicators() {
        const $startTime = $('#schedule_start_time');
        const $endTime = $('#schedule_end_time');
        const $container = $('#single-time-controls');

        // Remove existing status indicators
        $container.find('.time-validation-status').remove();

        // Only show indicators if both fields exist and have values
        if ($startTime.length && $endTime.length && $startTime.val() && $endTime.val()) {
            const isValid = validateTimeSelection($startTime, $endTime);
            const statusClass = isValid ? 'text-success' : 'text-danger';
            const statusIcon = isValid ? 'bi-check-circle' : 'bi-exclamation-circle';
            const statusText = isValid ? 'Times are valid' : 'Please check time selection';

            const statusHtml = `
                <div class="time-validation-status ${statusClass} small mt-1">
                    <i class="bi ${statusIcon} me-1"></i>${statusText}
                </div>
            `;

            $container.append(statusHtml);
        }
    }

    /**
     * Update validation indicators for per-day time controls
     */
    function updatePerDayTimeIndicators() {
        $('.per-day-time-section').each(function() {
            const $section = $(this);
            const day = $section.attr('data-day');
            const $startTime = $section.find('.day-start-time');
            const $endTime = $section.find('.day-end-time');

            // Remove existing status indicators
            $section.find('.time-validation-status').remove();

            if ($startTime.val() && $endTime.val()) {
                const isValid = validateTimeSelection($startTime, $endTime);
                const statusClass = isValid ? 'text-success' : 'text-danger';
                const statusIcon = isValid ? 'bi-check-circle' : 'bi-exclamation-circle';
                const statusText = isValid ? 'Valid' : 'Invalid';

                const statusHtml = `
                    <div class="time-validation-status ${statusClass} small">
                        <i class="bi ${statusIcon} me-1"></i>${statusText}
                    </div>
                `;

                $section.find('.day-duration-display').after(statusHtml);
            }
        });
    }

    /**
     * Initialize date field handling
     */
    function initDateFields() {
        // Initialize date fields
        const $startDate = $('#schedule_start_date');
        const $classType = $('#class_type');
        const $classStartDate = $('#class_start_date');

        // Auto-populate schedule start date when class start date changes
        $classStartDate.on('change', function() {
            const classStartDate = $(this).val();
            if (classStartDate) {
                // Auto-populate schedule start date with the same value
                $startDate.val(classStartDate);

                // Clear any existing validation errors on schedule start date since it's now valid
                $startDate.removeClass('is-invalid');
                $startDate.siblings('.invalid-feedback').text('Please select a start date.');

                // Trigger change event on schedule start date to update dependent calculations
                $startDate.trigger('change');
            }
        });

        // Update end date when start date or class type changes
        $startDate.add($classType).on('change', function() {

            // If start date changed, validate against class original start date
            if ($(this).attr('id') === 'schedule_start_date') {
                const startDate = $(this).val();
                const originalStartDate = $('#class_start_date').val();

                // Validate schedule start date against original start date
                if (startDate && originalStartDate && startDate < originalStartDate) {
                    // Show validation error
                    $(this).addClass('is-invalid');
                    $(this).siblings('.invalid-feedback').text('Start date cannot be before the class original start date');
                } else {
                    // Clear validation error
                    $(this).removeClass('is-invalid');
                    $(this).siblings('.invalid-feedback').text('Please select a start date.');
                }
            }

            // Use recalculateEndDate instead of calculateEndDate to account for exception dates
            recalculateEndDate();
            updateScheduleData();
        });
    }

    /**
     * Helper function to get class type hours
     */
    function getClassTypeHours(classTypeId) {
        // This would typically come from the server
        // For now, we'll use a simple mapping based on the class types
        const classTypeHours = {
            'AET_COMM': 120,
            'AET_NUM': 120,
            'GETC': 120,
            'BA_NQF2': 120,
            'BA_NQF3': 120,
            'BA_NQF4': 120,
            'REALLL': 160,
            'SKILL_PACKAGE': 80,
            'SOFT_SKILL': 40
        };

        // Default to 120 hours if not found
        return classTypeHours[classTypeId] || 120;
    }

    /**
     * Initialize exception dates
     */
    function initExceptionDates() {
        const $container = $('#exception-dates-container');
        const $template = $('#exception-date-row-template');
        const $addButton = $('#add-exception-date-btn');

        // Add exception date row
        $addButton.on('click', function() {
            const $newRow = $template.clone();
            $newRow.removeClass('d-none').removeAttr('id');
            $container.append($newRow);

            // Initialize remove button
            $newRow.find('.remove-exception-btn').on('click', function() {
                $newRow.remove();
                updateScheduleData();
                // Ensure end date is recalculated when an exception date is removed
                recalculateEndDate();
            });

            // Update schedule data when date or reason changes
            $newRow.find('input, select').on('change', function() {

                // Validate exception date against class start date
                const exceptionDate = $newRow.find('input[name="exception_dates[]"]').val();
                const startDate = $('#schedule_start_date').val();

                if (exceptionDate && startDate && exceptionDate < startDate) {
                    // Show validation error
                    $newRow.find('input[name="exception_dates[]"]').addClass('is-invalid');
                    $newRow.find('.invalid-feedback').text('Exception date cannot be before the class start date');
                } else {
                    // Clear validation error
                    $newRow.find('input[name="exception_dates[]"]').removeClass('is-invalid');
                    $newRow.find('.invalid-feedback').text('Please select a valid date.');
                }

                updateScheduleData();
                // Ensure end date is recalculated when an exception date is changed
                setTimeout(function() {
                    recalculateEndDate();
                }, 100);
            });
        });
    }

    /**
     * Initialize date history (stop/restart dates) functionality
     */
    function initDateHistory() {
        const $container = $('#date-history-container');
        const $template = $('#date-history-row-template');
        const $addButton = $('#add-date-history-btn');

        // Add date history row
        $addButton.on('click', function() {
            const $newRow = $template.clone();
            $newRow.removeClass('d-none').removeAttr('id');
            $container.append($newRow);

            // Initialize remove button
            $newRow.find('.date-remove-btn').on('click', function() {
                $newRow.remove();
                updateScheduleData();
                // Recalculate end date when stop/restart dates are removed
                recalculateEndDate();
            });

            // Update schedule data when dates change
            $newRow.find('input[name="stop_dates[]"], input[name="restart_dates[]"]').on('change', function() {
                updateScheduleData();
                // Recalculate end date when stop/restart dates change
                recalculateEndDate();
            });
        });
    }

    /**
     * Initialize exam class toggle functionality
     * Shows/hides the exam type container based on exam class selection
     */
    function initExamClassToggle() {
        const $examClass = $('#exam_class');
        const $examTypeContainer = $('#exam_type_container');
        const $examType = $('#exam_type');
        const $examLearnersContainer = $('#exam_learners_container');

        if (!$examClass.length || !$examTypeContainer.length) {
            return; // Elements not found, skip initialization
        }

        // Handle exam class selection change
        $examClass.on('change', function() {
            const examClassValue = $(this).val();
            console.log('Exam class changed to:', examClassValue);

            if (examClassValue === 'Yes' || examClassValue === '1' || examClassValue === 1) {
                console.log('Showing exam type container');
                // Show exam type field and make it required
                $examTypeContainer.show();
                if ($examType.length) {
                    $examType.attr('required', 'required');
                }

                // Show exam learners container if it exists
                if ($examLearnersContainer.length) {
                    $examLearnersContainer.show();
                }
            } else {
                console.log('Hiding exam type container');
                // Hide exam type field and remove required attribute
                $examTypeContainer.hide();
                if ($examType.length) {
                    $examType.removeAttr('required');
                    $examType.val(''); // Clear the value
                }

                // Hide exam learners container if it exists
                if ($examLearnersContainer.length) {
                    $examLearnersContainer.hide();
                }
            }
        });

        // Trigger change event on page load to set initial state
        $examClass.trigger('change');
    }

    /**
     * Initialize learner selection functionality
     * Handles adding selected learners from multi-select to the learners table
     */
    function initLearnerSelection() {
        const $addLearnerSelect = $('#add_learner');
        const $addSelectedLearnersBtn = $('#add-selected-learners-btn');
        const $classLearnersContainer = $('#class-learners-container');
        const $classLearnersTable = $('#class-learners-table');
        const $classLearnersTbody = $('#class-learners-tbody');
        const $noLearnersMessage = $('#no-learners-message');
        const $classLearnersData = $('#class_learners_data');

        // Array to store class learner data
        let classLearners = [];

        // Check if required elements exist
        if (!$addLearnerSelect.length || !$addSelectedLearnersBtn.length || !$classLearnersContainer.length) {
            console.log('Learner selection elements not found, skipping initialization');
            return;
        }

        console.log('Initializing learner selection functionality');

        // Handle add selected learners button click
        $addSelectedLearnersBtn.on('click', function() {
            const selectedOptions = $addLearnerSelect.find('option:selected');

            if (selectedOptions.length === 0) {
                alert('Please select at least one learner to add.');
                return;
            }

            console.log('Adding', selectedOptions.length, 'selected learners');

            // Add each selected learner
            selectedOptions.each(function() {
                const learnerId = $(this).val();
                const learnerName = $(this).text();

                // Convert to string to ensure consistent comparison
                const learnerIdStr = String(learnerId);

                // Check if learner is already added - ensure both IDs are strings for comparison
                if (classLearners.some(learner => String(learner.id) === learnerIdStr)) {
                    console.log('Learner', learnerName, 'already added, skipping');
                    return;
                }

                // Add learner to array (store as string for consistency)
                const learnerData = {
                    id: learnerIdStr,
                    name: learnerName,
                    level: '', // Default level (empty, will be auto-populated when subject is selected)
                    status: 'CIC - Currently in Class' // Default status
                };

                classLearners.push(learnerData);
                console.log('Added learner:', learnerData);
            });

            // Update the display and data
            updateLearnersDisplay();
            updateLearnersData();

            // Synchronize exam learner options if the function exists
            if (typeof window.classes_sync_exam_learner_options === 'function') {
                window.classes_sync_exam_learner_options();
            }

            // Clear the selection
            $addLearnerSelect.val([]);
        });

        // Function to update the learners table display
        function updateLearnersDisplay() {
            // Clear existing rows
            $classLearnersTbody.empty();

            if (classLearners.length === 0) {
                // Show no learners message and hide table
                $noLearnersMessage.removeClass('d-none');
                $classLearnersTable.addClass('d-none');
                return;
            }

            // Hide no learners message and show table
            $noLearnersMessage.addClass('d-none');
            $classLearnersTable.removeClass('d-none');

            // Add rows for each learner
            classLearners.forEach(function(learner) {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${learner.name}</td>
                    <td>
                        ${classes_generate_learner_level_select_html(learner.id, learner.level)}
                    </td>
                    <td>
                        <select class="form-select form-select-sm learner-status-select" data-learner-id="${learner.id}">
                            <option value="CIC - Currently in Class" ${learner.status === 'CIC - Currently in Class' ? 'selected' : ''}>CIC - Currently in Class</option>
                            <option value="RBE - Removed by Employer" ${learner.status === 'RBE - Removed by Employer' ? 'selected' : ''}>RBE - Removed by Employer</option>
                            <option value="DRO - Drop Out" ${learner.status === 'DRO - Drop Out' ? 'selected' : ''}>DRO - Drop Out</option>
                        </select>
                    </td>
                    <td>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-learner-btn" data-learner-id="${learner.id}">Remove</button>
                    </td>
                `;
                $classLearnersTbody.append(row);
            });

            console.log('Updated learners display with', classLearners.length, 'learners');

            // Debug: Check if remove buttons were created correctly
            const removeButtons = $classLearnersTbody.find('.remove-learner-btn');
            console.log('Created', removeButtons.length, 'remove buttons for class learners');
            removeButtons.each(function(index) {
                const learnerId = $(this).data('learner-id');
                console.log('Remove button', index + 1, 'has learner-id:', learnerId);
            });

            // Auto-populate learner levels if a class subject is already selected
            const classSubjectSelect = document.getElementById('class_subject');
            if (classSubjectSelect && classSubjectSelect.value) {
                // Use setTimeout to ensure DOM is fully updated
                setTimeout(function() {
                    if (typeof classes_populate_learner_levels === 'function') {
                        classes_populate_learner_levels(classSubjectSelect.value);
                    } else if (typeof window.wecoza_auto_populate_learner_levels === 'function') {
                        window.wecoza_auto_populate_learner_levels(classSubjectSelect.value);
                    }
                }, 100); // Small delay to ensure DOM is ready
            }
        }

        // Function to update the hidden field with learner data
        function updateLearnersData() {
            const jsonData = JSON.stringify(classLearners);
            $classLearnersData.val(jsonData);

            // Trigger custom event for learner data change
            $(document).trigger('classLearnersChanged', [classLearners]);

            console.log('Updated learners data:', jsonData);
        }

        // Handle level/status changes
        $(document).on('change', '.learner-level-select, .learner-status-select', function() {
            const learnerId = $(this).data('learner-id');
            const field = $(this).hasClass('learner-level-select') ? 'level' : 'status';
            const value = $(this).val();

            // Convert to string to ensure consistent comparison
            const learnerIdStr = String(learnerId);

            // Update the learner data - ensure both IDs are strings for comparison
            const learner = classLearners.find(l => String(l.id) === learnerIdStr);
            if (learner) {
                learner[field] = value;
                updateLearnersData();
                console.log('Updated learner', learnerIdStr, field, 'to', value);
            } else {
                console.warn('Learner not found for ID:', learnerIdStr);
            }
        });

        // Handle remove learner
        $(document).on('click', '.remove-learner-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const learnerId = $(this).data('learner-id');
            console.log('Remove learner button clicked for learner ID:', learnerId, 'Type:', typeof learnerId);

            if (!learnerId) {
                console.error('No learner ID found on remove button');
                return;
            }

            // Convert to string to ensure consistent comparison (since HTML data attributes are strings)
            const learnerIdStr = String(learnerId);

            // Debug: Log current classLearners array
            console.log('Current classLearners array:', classLearners);
            console.log('Looking for learner with ID:', learnerIdStr);

            // Remove from array - ensure both IDs are strings for comparison
            const initialLength = classLearners.length;
            classLearners = classLearners.filter(learner => {
                const learnerIdInArray = String(learner.id);
                const shouldKeep = learnerIdInArray !== learnerIdStr;
                console.log('Comparing:', learnerIdInArray, '!==', learnerIdStr, '=', shouldKeep);
                return shouldKeep;
            });

            if (classLearners.length === initialLength) {
                console.warn('Learner', learnerIdStr, 'was not found in classLearners array');
                console.log('Available learner IDs in array:', classLearners.map(l => String(l.id)));
                return;
            }

            console.log('Successfully removed learner', learnerIdStr, 'from classLearners array');

            // Update display and data
            updateLearnersDisplay();
            updateLearnersData();

            // Remove from exam learners if they were selected for exams (cascading removal)
            if (typeof window.classes_remove_exam_learner === 'function') {
                window.classes_remove_exam_learner(learnerIdStr);
            }

            // Synchronize exam learner options if the function exists
            if (typeof window.classes_sync_exam_learner_options === 'function') {
                window.classes_sync_exam_learner_options();
            }

            console.log('Removed learner', learnerIdStr, 'from class learners with cascading removal');
        });

        // Load existing learner data if available (for editing)
        const existingData = $classLearnersData.val();
        if (existingData) {
            try {
                classLearners = JSON.parse(existingData);
                updateLearnersDisplay();
                console.log('Loaded existing learners:', classLearners);

                // Synchronize exam learner options after loading existing data
                setTimeout(function() {
                    if (typeof window.classes_sync_exam_learner_options === 'function') {
                        window.classes_sync_exam_learner_options();
                    }
                }, 200); // Delay to ensure exam learner functionality is initialized

                // Auto-populate learner levels if a class subject is already selected (for editing)
                const classSubjectSelect = document.getElementById('class_subject');
                if (classSubjectSelect && classSubjectSelect.value) {
                    // Use setTimeout to ensure DOM is fully updated
                    setTimeout(function() {
                        if (typeof classes_populate_learner_levels === 'function') {
                            classes_populate_learner_levels(classSubjectSelect.value);
                        } else if (typeof window.wecoza_auto_populate_learner_levels === 'function') {
                            window.wecoza_auto_populate_learner_levels(classSubjectSelect.value);
                        }
                    }, 100); // Small delay to ensure DOM is ready
                }
            } catch (e) {
                console.error('Error parsing existing learner data:', e);
            }
        }
    }

    /**
     * Initialize backup agents functionality
     * Handles adding and removing backup agent rows
     */
    function initBackupAgents() {
        const $container = $('#backup-agents-container');
        const $template = $('#backup-agent-row-template');
        const $addButton = $('#add-backup-agent-btn');

        // Check if required elements exist
        if (!$container.length || !$template.length || !$addButton.length) {
            console.log('Backup agents elements not found, skipping initialization');
            return;
        }

        console.log('Initializing backup agents functionality');

        // Handle add backup agent button click
        $addButton.on('click', function() {
            console.log('Adding new backup agent row');

            // Clone the template
            const $newRow = $template.clone();

            // Remove the d-none class and id to make it visible and unique
            $newRow.removeClass('d-none').removeAttr('id');

            // Append to container
            $container.append($newRow);

            // Initialize remove button for this row
            $newRow.find('.remove-backup-agent-btn, .date-remove-btn').on('click', function() {
                console.log('Removing backup agent row');
                $newRow.remove();

                // Update any form data if needed
                updateScheduleData();
            });

            // Focus on the first input in the new row
            $newRow.find('select').first().focus();
        });

        // Handle remove buttons for any existing rows (in case of editing)
        // Use event delegation but be more specific to avoid conflicts with other .date-remove-btn handlers
        $(document).on('click', '.backup-agent-row .remove-backup-agent-btn, .backup-agent-row .date-remove-btn', function() {
            // Check if this is a backup agent row (should always be true due to selector)
            const $row = $(this).closest('.backup-agent-row');
            if ($row.length && !$row.is('#backup-agent-row-template')) {
                console.log('Removing existing backup agent row');
                $row.remove();
                updateScheduleData();
            }
        });
    }

    /**
     * Initialize holiday override functionality
     */
    function initHolidayOverrides() {
        const $holidaysList = $('#holidays-list');
        const $overrideAllCheckbox = $('#override-all-holidays');
        const $skipAllBtn = $('#skip-all-holidays-btn');
        const $overrideAllBtn = $('#override-all-holidays-btn');
        const $holidayOverridesInput = $('#holiday_overrides');

        // Initialize holidayOverrides object if not already initialized
        if (typeof window.holidayOverrides !== 'object' || window.holidayOverrides === null) {
            window.holidayOverrides = {};
        }

        // Load existing overrides if available
        try {
            const existingOverrides = $holidayOverridesInput.val();
            if (existingOverrides) {
                const overrides = JSON.parse(existingOverrides);
                Object.assign(window.holidayOverrides, overrides);
            }
        } catch (e) {
            console.error('Error parsing existing holiday overrides:', e);
        }

        // Handle individual holiday override checkboxes
        $(document).on('change', '.holiday-override-checkbox', function() {
            const $checkbox = $(this);
            const date = $checkbox.data('date');
            const isChecked = $checkbox.is(':checked');
            const $row = $checkbox.closest('tr');

            if (isChecked) {
                window.holidayOverrides[date] = { override: true };
            } else {
                delete window.holidayOverrides[date];
            }

            // Update status badges in the row
            $row.find('.holiday-skipped').toggleClass('d-none', isChecked);
            $row.find('.holiday-overridden').toggleClass('d-none', !isChecked);

            // Update hidden field
            $holidayOverridesInput.val(JSON.stringify(window.holidayOverrides));

            // Update "Override All" checkbox state
            updateOverrideAllCheckbox();

            // Recalculate end date with the new overrides
            recalculateEndDate();
        });

        // Handle "Override All" checkbox
        if ($overrideAllCheckbox.length) {
            $overrideAllCheckbox.on('change', function() {
                const isChecked = $(this).is(':checked');
                $('.holiday-override-checkbox').prop('checked', isChecked).trigger('change');
            });
        }

        // Handle "Skip All" button
        if ($skipAllBtn.length) {
            $skipAllBtn.on('click', function() {
                $('.holiday-override-checkbox').each(function() {
                    $(this).prop('checked', false).trigger('change');
                });
            });
        }

        // Handle "Override All" button
        if ($overrideAllBtn.length) {
            $overrideAllBtn.on('click', function() {
                $('.holiday-override-checkbox').each(function() {
                    $(this).prop('checked', true).trigger('change');
                });
            });
        }

        // Check for holidays when start date changes
        $('#schedule_start_date').on('change', function() {
            const startDate = $(this).val();
            const endDate = $('#schedule_end_date').val();
            const pattern = $('#schedule_pattern').val();
            const selectedDays = getSelectedDays();

            if (startDate && pattern && selectedDays.length > 0) {
                checkForHolidays(startDate, endDate);
            }
        });

        // Check for holidays when end date changes
        $('#schedule_end_date').on('change', function() {
            const startDate = $('#schedule_start_date').val();
            const endDate = $(this).val();
            const pattern = $('#schedule_pattern').val();
            const selectedDays = getSelectedDays();

            if (startDate && pattern && selectedDays.length > 0) {
                checkForHolidays(startDate, endDate);
            }
        });

        // Update the "Override All" checkbox based on individual checkboxes
        function updateOverrideAllCheckbox() {
            const totalHolidays = $('.holiday-override-checkbox').length;
            const checkedHolidays = $('.holiday-override-checkbox:checked').length;

            if ($overrideAllCheckbox.length) {
                if (checkedHolidays === 0) {
                    $overrideAllCheckbox.prop('checked', false);
                    $overrideAllCheckbox.prop('indeterminate', false);
                } else if (checkedHolidays === totalHolidays) {
                    $overrideAllCheckbox.prop('checked', true);
                    $overrideAllCheckbox.prop('indeterminate', false);
                } else {
                    $overrideAllCheckbox.prop('checked', false);
                    $overrideAllCheckbox.prop('indeterminate', true);
                }
            }
        }
    }

    /**
     * Check for public holidays in date range and show only holidays that conflict with the schedule
     */
    function checkForHolidays(startDate, endDate) {
        // If no public holidays data, show no holidays message and return
        if (typeof window.wecozaPublicHolidays === 'undefined' || !window.wecozaPublicHolidays.events) {
            updateHolidaysDisplay([]);
            return;
        }

        // If no start date, show no holidays message and return
        if (!startDate) {
            updateHolidaysDisplay([]);
            return;
        }

        // If no end date, use 3 months from start date
        if (!endDate) {
            const date = new Date(startDate);
            date.setMonth(date.getMonth() + 3);
            endDate = date.toISOString().split('T')[0];
        }

        const pattern = $('#schedule_pattern').val();
        const selectedDays = getSelectedDays();

        // Only check for holidays if we have a weekly or biweekly pattern with selected days
        if ((pattern === 'weekly' || pattern === 'biweekly') && selectedDays.length === 0) {
            return;
        }

        // Convert dates to Date objects for comparison
        const startDateObj = new Date(startDate);
        const endDateObj = new Date(endDate);

        // Find holidays that conflict with the schedule
        const conflictingHolidays = [];

        window.wecozaPublicHolidays.events.forEach(holiday => {
            // Parse the date parts to ensure correct date (avoid timezone issues)
            const [year, month, day] = holiday.start.split('-').map(Number);
            const holidayDate = new Date(year, month - 1, day);

            if (holidayDate >= startDateObj && holidayDate <= endDateObj) {
                // Check if this holiday conflicts with the schedule
                let conflictsWithSchedule = false;

                if (pattern === 'weekly' || pattern === 'biweekly') {
                    const dayName = holidayDate.toLocaleDateString('en-US', { weekday: 'long' });
                    conflictsWithSchedule = selectedDays.includes(dayName);
                } else if (pattern === 'monthly') {
                    const dayOfMonth = $('#schedule_day_of_month').val();
                    if (dayOfMonth === 'last') {
                        // Check if it's the last day of the month
                        const lastDay = new Date(holidayDate.getFullYear(), holidayDate.getMonth() + 1, 0);
                        conflictsWithSchedule = holidayDate.getDate() === lastDay.getDate();
                    } else {
                        conflictsWithSchedule = holidayDate.getDate() === parseInt(dayOfMonth);
                    }
                }

                if (conflictsWithSchedule) {
                    conflictingHolidays.push({
                        date: holiday.start,
                        name: holiday.title,
                        dayName: holidayDate.toLocaleDateString('en-US', { weekday: 'long' })
                    });
                }
            }
        });

        // Update holidays display
        updateHolidaysDisplay(conflictingHolidays);
    }

    /**
     * Update holidays display with conflicting holidays
     */
    function updateHolidaysDisplay(conflictingHolidays) {
        const $holidaysList = $('#holidays-list');
        const $holidaysTableContainer = $('#holidays-table-container');
        const $noHolidaysMessage = $('#no-holidays-message');
        const $template = $('#holiday-row-template');

        if (!$holidaysList.length) {
            return; // No holidays display element found
        }

        // Clear existing holidays
        $holidaysList.empty();

        if (conflictingHolidays.length === 0) {
            // Hide holidays table container and show no holidays message
            if ($holidaysTableContainer.length) {
                $holidaysTableContainer.addClass('d-none');
            }
            if ($noHolidaysMessage.length) {
                $noHolidaysMessage.removeClass('d-none');
            }
            return;
        }

        // Show holidays table container and hide no holidays message
        if ($holidaysTableContainer.length) {
            $holidaysTableContainer.removeClass('d-none');
        }
        if ($noHolidaysMessage.length) {
            $noHolidaysMessage.addClass('d-none');
        }

        // Add each conflicting holiday using the template
        conflictingHolidays.forEach((holiday, index) => {
            // Get template content
            const templateHtml = $template.html();

            // Format the date for display
            const [year, month, day] = holiday.date.split('-').map(Number);
            const holidayDate = new Date(year, month - 1, day);
            const formattedDate = holidayDate.toLocaleDateString('en-ZA', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            // Replace placeholders in template
            let rowHtml = templateHtml
                .replace(/{id}/g, index)
                .replace(/{date}/g, holiday.date)
                .replace(/{formatted_date}/g, formattedDate)
                .replace(/{name}/g, holiday.name);

            // Add to holidays list
            $holidaysList.append(rowHtml);

            // Get the row we just added
            const $row = $holidaysList.find(`[data-date="${holiday.date}"]`).closest('tr');

            // Check if this holiday has an existing override
            let isOverridden = false;
            if (window.holidayOverrides[holiday.date]) {
                isOverridden = window.holidayOverrides[holiday.date].override;

                // Update checkbox
                $row.find('.holiday-override-checkbox').prop('checked', isOverridden);
            }

            // Update status badges based on override status
            $row.find('.holiday-skipped').toggleClass('d-none', isOverridden);
            $row.find('.holiday-overridden').toggleClass('d-none', !isOverridden);
        });
    }

    /**
     * Initialize schedule data updates system
     * Sets up event handlers and initial data collection
     */
    function initScheduleDataUpdates() {
        // Initialize statistics toggle
        initScheduleStatisticsToggle();

        // Perform initial data update
        updateScheduleData();

        // Set up form submission handler
        initFormSubmissionHandler();
    }

    /**
     * Initialize schedule statistics toggle functionality
     */
    function initScheduleStatisticsToggle() {
        $('#toggle-statistics-btn').on('click', function() {
            const $section = $('#schedule-statistics-section');
            const $button = $(this);

            if ($section.hasClass('d-none')) {
                // Show statistics
                $section.removeClass('d-none');
                $button.html('<i class="bi bi-eye-slash me-1"></i> Hide Schedule Statistics');

                // Update statistics when shown
                const scheduleData = collectScheduleData();
                updateScheduleStatistics(scheduleData);
            } else {
                // Hide statistics
                $section.addClass('d-none');
                $button.html('<i class="bi bi-bar-chart-line me-1"></i> View Schedule Statistics');
            }
        });
    }

    /**
     * Initialize form submission handler with validation
     */
    function initFormSubmissionHandler() {
        // Find the form containing the schedule data
        const $form = $('#schedule_pattern').closest('form');

        if ($form.length > 0) {
            $form.on('submit', function(e) {
                // Validate day selection for custom validation
                validateDaySelection();

                // Check if form is valid using Bootstrap validation
                if (!this.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Add Bootstrap validation class to trigger styling
                    $form.addClass('was-validated');

                    // Show validation feedback for day selection
                    showDaySelectionValidationFeedback();

                    return false;
                }

                // Add Bootstrap validation class to trigger styling
                $form.addClass('was-validated');

                // Show validation feedback for day selection
                showDaySelectionValidationFeedback();

                // If form is valid, proceed with custom validation
                if (this.checkValidity()) {
                    // Get submission data with additional validation
                    const submissionData = getFormSubmissionData();

                    if (!submissionData.isValid) {
                        e.preventDefault();

                        // Display validation errors
                        displayFormValidationErrors(submissionData.errors);

                        return false;
                    }

                    // Final data update before submission
                    updateScheduleData();
                }
            });
        }
    }

    /**
     * Display form validation errors to the user
     */
    function displayFormValidationErrors(errors) {
        // Remove existing error display
        $('#schedule-validation-errors').remove();

        if (errors.length > 0) {
            const errorHtml = `
                <div id="schedule-validation-errors" class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        ${errors.map(error => `<li>${error}</li>`).join('')}
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;

            // Insert after the schedule controls
            $('#per-day-time-controls').after(errorHtml);

            // Scroll to the error message
            $('#schedule-validation-errors')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    /**
     * Backward Compatibility System
     * Handles loading and converting legacy schedule data formats
     */

    /**
     * Load and process existing schedule data for editing
     * Handles both legacy and new data formats
     */
    function loadExistingScheduleData() {
        // Check if we're in edit mode (has existing data)
        const existingData = getExistingScheduleDataFromForm();

        if (existingData) {
            // Detect data format and convert if necessary
            const processedData = processLegacyScheduleData(existingData);

            // Populate form with processed data
            populateFormWithScheduleData(processedData);
        }
    }

    /**
     * Get existing schedule data from form/page context
     * This would typically come from server-side rendering or AJAX
     */
    function getExistingScheduleDataFromForm() {
        // Check for data in various possible locations

        // 1. Check for data in a hidden field (common pattern)
        const hiddenDataField = $('#existing_schedule_data').val();
        if (hiddenDataField) {
            try {
                return JSON.parse(hiddenDataField);
            } catch (e) {
                console.warn('Failed to parse hidden schedule data:', e);
            }
        }

        // 2. Check for data in window object (if localized by PHP)
        if (window.wecozaScheduleData) {
            return window.wecozaScheduleData;
        }

        // 3. Check for pre-filled form values (legacy approach)
        const $startTime = $('#schedule_start_time');
        const $endTime = $('#schedule_end_time');
        const pattern = $('#schedule_pattern').val();

        const startTime = $startTime.length ? $startTime.val() : '';
        const endTime = $endTime.length ? $endTime.val() : '';

        if (startTime && endTime && pattern) {
            // Construct basic legacy format
            return {
                format: 'legacy_form_values',
                pattern: pattern,
                start_time: startTime,
                end_time: endTime,
                start_date: $('#schedule_start_date').val(),
                end_date: $('#schedule_end_date').val()
            };
        }

        return null;
    }

    // Legacy format processing removed - V2.0 format only

    // Legacy conversion functions removed - V2.0 format only

    /**
     * Populate form with processed schedule data
     * Sets form values and triggers appropriate UI updates
     */
    function populateFormWithScheduleData(data) {
        try {
            // Set basic schedule fields
            if (data.pattern) {
                $('#schedule_pattern').val(data.pattern).trigger('change');
            }

            if (data.startDate) {
                $('#schedule_start_date').val(data.startDate);
            }

            if (data.endDate) {
                $('#schedule_end_date').val(data.endDate);
            }

            if (data.dayOfMonth) {
                $('#schedule_day_of_month').val(data.dayOfMonth);
            }

            // Set time data based on mode
            if (data.timeData) {
                if (data.timeData.mode === 'single') {
                    // Populate single time controls only if they exist
                    const $startTime = $('#schedule_start_time');
                    const $endTime = $('#schedule_end_time');

                    if (data.timeData.startTime && $startTime.length) {
                        $startTime.val(data.timeData.startTime);
                    }
                    if (data.timeData.endTime && $endTime.length) {
                        $endTime.val(data.timeData.endTime);
                    }
                    if (data.timeData.duration) {
                        const $duration = $('#schedule_duration');
                        if ($duration.length) {
                            $duration.val(data.timeData.duration);
                        }
                    }
                } else if (data.timeData.mode === 'per-day' && data.timeData.perDayTimes) {
                    // Handle per-day times - this will be set after day selection
                    // Store for later use when per-day sections are created
                    window.pendingPerDayTimes = data.timeData.perDayTimes;
                }
            }

            // Set selected days for weekly/biweekly patterns
            if (data.selectedDays && data.selectedDays.length > 0) {
                data.selectedDays.forEach(day => {
                    $(`.schedule-day-checkbox[value="${day}"]`).prop('checked', true);
                });

                // Trigger day selection update
                setTimeout(() => {
                    updatePerDayTimeControls();

                    // Apply pending per-day times if available
                    if (window.pendingPerDayTimes) {
                        applyPerDayTimes(window.pendingPerDayTimes);
                        delete window.pendingPerDayTimes;
                    }
                }, 100);
            }

            // Set exception dates
            if (data.exceptionDates && data.exceptionDates.length > 0) {
                data.exceptionDates.forEach(exception => {
                    addExceptionDateRow(exception.date, exception.reason);
                });
            }

            // Set holiday overrides
            if (data.holidayOverrides) {
                Object.keys(data.holidayOverrides).forEach(date => {
                    $(`.holiday-override-checkbox[data-date="${date}"]`).prop('checked', true);
                });
            }

            // Trigger validation and updates
            setTimeout(() => {
                validateAllTimeSelections();
                updateTimeValidationIndicators();
                updateScheduleData();
            }, 200);

        } catch (error) {
            console.error('Error populating form with schedule data:', error);
        }
    }

    /**
     * Apply per-day times to the generated sections
     */
    function applyPerDayTimes(perDayTimes) {

        Object.keys(perDayTimes).forEach(day => {
            const dayData = perDayTimes[day];
            const $section = $(`.per-day-time-section[data-day="${day}"]`);

            if ($section.length > 0) {
                $section.find('.day-start-time').val(dayData.startTime);
                $section.find('.day-end-time').val(dayData.endTime);

                // Trigger change events to update duration and validation
                $section.find('.day-start-time').trigger('change');
                $section.find('.day-end-time').trigger('change');
            }
        });
    }

    /**
     * Add an exception date row with pre-filled data
     */
    function addExceptionDateRow(date, reason) {
        // Trigger the add button to create a new row
        $('#add-exception-date-btn').trigger('click');

        // Fill the newly created row
        setTimeout(() => {
            const $lastRow = $('#exception-dates-container .exception-date-row').last();
            $lastRow.find('input[name="exception_dates[]"]').val(date);
            $lastRow.find('select[name="exception_reasons[]"]').val(reason);
        }, 50);
    }

    // Legacy compatibility function removed - V2.0 format only

    // Legacy compatibility functions removed - V2.0 format only

    /**
     * Global function to get schedule data in current format
     * Provides access to the full new data structure
     */
    window.getScheduleDataCurrent = function() {
        return collectScheduleData();
    };

    /**
     * Update schedule data - collect and format all schedule information
     * Creates hidden form fields with properly structured data for backend processing
     */
    function updateScheduleData() {
        try {
            // Collect all schedule data
            const scheduleData = collectScheduleData();

            // Update hidden form fields
            updateHiddenFormFields(scheduleData);

            // Update schedule statistics if visible
            updateScheduleStatistics(scheduleData);

        } catch (error) {
            console.error('Error updating schedule data:', error);
        }
    }

    /**
     * Collect comprehensive schedule data from the form
     * Returns structured data object ready for backend processing
     */
    function collectScheduleData() {
        const data = {
            // Basic schedule information
            pattern: $('#schedule_pattern').val(),
            startDate: $('#schedule_start_date').val(),
            endDate: $('#schedule_end_date').val(),
            dayOfMonth: $('#schedule_day_of_month').val(),

            // Time data (single or per-day)
            timeData: getAllTimeData(),

            // Selected days for weekly/biweekly patterns
            selectedDays: getSelectedDays(),

            // Exception dates
            exceptionDates: collectExceptionDates(),

            // Holiday overrides
            holidayOverrides: collectHolidayOverrides(),

            // Metadata
            lastUpdated: new Date().toISOString(),
            version: '2.0' // Version for backward compatibility tracking
        };

        return data;
    }

    /**
     * Collect exception dates from the form
     */
    function collectExceptionDates() {
        const exceptions = [];

        $('#exception-dates-container .exception-date-row').each(function() {
            const date = $(this).find('input[name="exception_dates[]"]').val();
            const reason = $(this).find('select[name="exception_reasons[]"]').val();

            if (date) {
                exceptions.push({
                    date: date,
                    reason: reason || 'No reason specified'
                });
            }
        });

        return exceptions;
    }

    /**
     * Collect holiday override data
     */
    function collectHolidayOverrides() {
        const overrides = {};

        $('.holiday-override-checkbox:checked').each(function() {
            const date = $(this).attr('data-date');
            if (date) {
                overrides[date] = true;
            }
        });

        return overrides;
    }

    /**
     * Update hidden form fields with collected schedule data
     * Creates the proper structure expected by the backend
     */
    function updateHiddenFormFields(scheduleData) {
        const $container = $('#schedule-data-container');

        // Clear existing hidden fields
        $container.empty();

        // Create hidden fields for different data types
        createHiddenField($container, 'schedule_data[pattern]', scheduleData.pattern);
        createHiddenField($container, 'schedule_data[start_date]', scheduleData.startDate);
        createHiddenField($container, 'schedule_data[end_date]', scheduleData.endDate);
        createHiddenField($container, 'schedule_data[day_of_month]', scheduleData.dayOfMonth);
        createHiddenField($container, 'schedule_data[version]', scheduleData.version);
        createHiddenField($container, 'schedule_data[last_updated]', scheduleData.lastUpdated);

        // Handle time data based on mode
        if (scheduleData.timeData.mode === 'single') {
            // Single time mode - backward compatible format
            createHiddenField($container, 'schedule_data[time_mode]', 'single');
            createHiddenField($container, 'schedule_data[start_time]', scheduleData.timeData.startTime);
            createHiddenField($container, 'schedule_data[end_time]', scheduleData.timeData.endTime);
            createHiddenField($container, 'schedule_data[duration]', scheduleData.timeData.duration);
        } else {
            // Per-day time mode - new format
            createHiddenField($container, 'schedule_data[time_mode]', 'per_day');

            // Create fields for each day's times
            Object.keys(scheduleData.timeData.perDayTimes).forEach(day => {
                const dayData = scheduleData.timeData.perDayTimes[day];
                createHiddenField($container, `schedule_data[per_day_times][${day}][start_time]`, dayData.startTime);
                createHiddenField($container, `schedule_data[per_day_times][${day}][end_time]`, dayData.endTime);
                createHiddenField($container, `schedule_data[per_day_times][${day}][duration]`, dayData.duration.toFixed(2));
            });
        }

        // Selected days for weekly/biweekly patterns
        if (scheduleData.selectedDays.length > 0) {
            scheduleData.selectedDays.forEach((day, index) => {
                createHiddenField($container, `schedule_data[selected_days][${index}]`, day);
            });
        }

        // Exception dates
        if (scheduleData.exceptionDates.length > 0) {
            scheduleData.exceptionDates.forEach((exception, index) => {
                createHiddenField($container, `schedule_data[exception_dates][${index}][date]`, exception.date);
                createHiddenField($container, `schedule_data[exception_dates][${index}][reason]`, exception.reason);
            });
        }

        // Holiday overrides
        Object.keys(scheduleData.holidayOverrides).forEach(date => {
            createHiddenField($container, `schedule_data[holiday_overrides][${date}]`, '1');
        });

        // Update the main holiday overrides field for backward compatibility
        $('#holiday_overrides').val(JSON.stringify(scheduleData.holidayOverrides));

        // Legacy compatibility fields removed - V2.0 format only
    }

    /**
     * Create a hidden form field
     */
    function createHiddenField($container, name, value) {
        if (value !== null && value !== undefined && value !== '') {
            const $field = $('<input>', {
                type: 'hidden',
                name: name,
                value: value
            });
            $container.append($field);
        }
    }

    /**
     * Update schedule statistics display
     */
    function updateScheduleStatistics(scheduleData) {
        // Only update if statistics section is visible
        if (!$('#schedule-statistics-section').hasClass('d-none')) {
            calculateAndDisplayStatistics(scheduleData);
        }
    }

    /**
     * Calculate and display schedule statistics
     */
    function calculateAndDisplayStatistics(scheduleData) {
        try {
            const stats = calculateScheduleStatistics(scheduleData);

            // Update statistics display
            $('#stat-total-days').text(stats.totalDays || '-');
            $('#stat-total-weeks').text(stats.totalWeeks || '-');
            $('#stat-total-months').text(stats.totalMonths || '-');
            $('#stat-total-classes').text(stats.totalClasses || '-');
            $('#stat-total-hours').text(stats.totalHours || '-');
            $('#stat-avg-hours-month').text(stats.avgHoursPerMonth || '-');
            $('#stat-holidays-affecting').text((stats.holidaysAffecting || 0) + ' holidays');
            $('#stat-exception-dates').text(stats.exceptionDates || '-');
            $('#stat-actual-days').text(stats.actualTrainingDays || '-');

        } catch (error) {
            console.error('Error calculating schedule statistics:', error);
        }
    }

    /**
     * Calculate schedule statistics from schedule data
     */
    function calculateScheduleStatistics(scheduleData) {
        const stats = {
            totalDays: 0,
            totalWeeks: 0,
            totalMonths: 0,
            totalClasses: 0,
            totalHours: 0,
            avgHoursPerMonth: 0,
            holidaysAffecting: 0,
            exceptionDates: scheduleData.exceptionDates.length,
            actualTrainingDays: 0
        };

        if (scheduleData.startDate && scheduleData.endDate) {
            const startDate = new Date(scheduleData.startDate);
            const endDate = new Date(scheduleData.endDate);
            const timeDiff = endDate.getTime() - startDate.getTime();

            stats.totalDays = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
            stats.totalWeeks = Math.ceil(stats.totalDays / 7);
            stats.totalMonths = Math.ceil(stats.totalDays / 30);

            // Calculate total hours based on time mode
            if (scheduleData.timeData.mode === 'single') {
                const duration = parseFloat(scheduleData.timeData.duration) || 0;
                const daysPerWeek = scheduleData.selectedDays.length || 1;

                if (scheduleData.pattern === 'weekly') {
                    stats.totalClasses = stats.totalWeeks * daysPerWeek;
                } else if (scheduleData.pattern === 'biweekly') {
                    stats.totalClasses = Math.ceil(stats.totalWeeks / 2) * daysPerWeek;
                } else if (scheduleData.pattern === 'monthly') {
                    stats.totalClasses = stats.totalMonths;
                }

                stats.totalHours = stats.totalClasses * duration;
            } else {
                // Per-day mode calculation
                const perDayTimes = scheduleData.timeData.perDayTimes;
                let totalWeeklyHours = 0;

                Object.values(perDayTimes).forEach(dayData => {
                    totalWeeklyHours += dayData.duration;
                });

                if (scheduleData.pattern === 'weekly') {
                    stats.totalHours = stats.totalWeeks * totalWeeklyHours;
                    stats.totalClasses = stats.totalWeeks * Object.keys(perDayTimes).length;
                } else if (scheduleData.pattern === 'biweekly') {
                    stats.totalHours = Math.ceil(stats.totalWeeks / 2) * totalWeeklyHours;
                    stats.totalClasses = Math.ceil(stats.totalWeeks / 2) * Object.keys(perDayTimes).length;
                }
            }

            stats.avgHoursPerMonth = stats.totalMonths > 0 ? (stats.totalHours / stats.totalMonths).toFixed(1) : 0;
            stats.actualTrainingDays = stats.totalClasses - stats.exceptionDates;

            // Calculate holidays affecting classes
            stats.holidaysAffecting = countHolidaysAffectingClasses(scheduleData);
        }

        return stats;
    }

    /**
     * Count holidays that affect class schedule
     */
    function countHolidaysAffectingClasses(scheduleData) {
        // Check if we have holidays data and selected days
        if (!scheduleData.selectedDays || scheduleData.selectedDays.length === 0) {
            return 0;
        }

        // Check if we have public holidays data
        if (typeof window.wecozaPublicHolidays === 'undefined' || !window.wecozaPublicHolidays.events) {
            return 0;
        }

        // Get holidays within the schedule date range
        if (!scheduleData.startDate || !scheduleData.endDate) {
            return 0;
        }

        const startDate = new Date(scheduleData.startDate);
        const endDate = new Date(scheduleData.endDate);

        // Filter holidays to only include those within the date range
        const holidaysInRange = window.wecozaPublicHolidays.events.filter(holiday => {
            const [year, month, day] = holiday.start.split('-').map(Number);
            const holidayDate = new Date(year, month - 1, day);
            return holidayDate >= startDate && holidayDate <= endDate;
        });

        let count = 0;
        const selectedDays = scheduleData.selectedDays;
        const dayIndices = selectedDays.map(day => getDayIndex(day));

        // Loop through each holiday in range
        holidaysInRange.forEach(holiday => {
            // Parse the date parts to ensure correct date (avoid timezone issues)
            const [year, month, day] = holiday.start.split('-').map(Number);
            const holidayDate = new Date(year, month - 1, day);
            const dayOfWeek = holidayDate.getDay();

            // Check if this holiday falls on a selected day and is not overridden
            const dateStr = holiday.start;
            let isOverridden = false;

            // Check if this holiday has been overridden
            if (typeof window.holidayOverrides === 'object' && window.holidayOverrides[dateStr]) {
                isOverridden = window.holidayOverrides[dateStr].override === true;
            }

            if (dayIndices.includes(dayOfWeek) && !isOverridden) {
                count++;
            }
        });

        return count;
    }

    /**
     * Get form data ready for submission
     * Includes validation and final data preparation
     */
    function getFormSubmissionData() {
        // Validate all time selections
        const timeValidation = getTimeValidationSummary();

        if (!timeValidation.canSubmit) {
            return {
                isValid: false,
                errors: ['Please fix time validation errors before submitting.'],
                data: null
            };
        }

        // Collect final schedule data
        const scheduleData = collectScheduleData();

        // Validate required fields
        const requiredFieldsValid = validateRequiredScheduleFields(scheduleData);

        if (!requiredFieldsValid.isValid) {
            return {
                isValid: false,
                errors: requiredFieldsValid.errors,
                data: null
            };
        }

        return {
            isValid: true,
            errors: [],
            data: scheduleData
        };
    }

    /**
     * Validate required schedule fields for submission
     */
    function validateRequiredScheduleFields(scheduleData) {
        const errors = [];

        if (!scheduleData.pattern) {
            errors.push('Schedule pattern is required.');
        }

        if (!scheduleData.startDate) {
            errors.push('Start date is required.');
        }

        if (scheduleData.pattern === 'monthly' && !scheduleData.dayOfMonth) {
            errors.push('Day of month is required for monthly pattern.');
        }

        if ((scheduleData.pattern === 'weekly' || scheduleData.pattern === 'biweekly') && scheduleData.selectedDays.length === 0) {
            errors.push('At least one day must be selected for weekly/biweekly patterns.');
        }

        // Validate time data
        if (scheduleData.timeData.mode === 'single') {
            if (!scheduleData.timeData.startTime || !scheduleData.timeData.endTime) {
                errors.push('Start time and end time are required.');
            }
        } else {
            if (Object.keys(scheduleData.timeData.perDayTimes).length === 0) {
                errors.push('Time data is required for all selected days.');
            }
        }

        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }



    /**
     * Check if a date falls within any stop/restart period (class is stopped)
     */
    function isDateInStopPeriod(dateStr, stopRestartPeriods) {
        for (const period of stopRestartPeriods) {
            const stopDate = new Date(period.stopDate);
            const restartDate = new Date(period.restartDate);
            const checkDate = new Date(dateStr);

            // Check if the date falls between stop date (inclusive) and restart date (exclusive)
            if (checkDate >= stopDate && checkDate < restartDate) {
                return true;
            }
        }
        return false;
    }

    /**
     * Recalculate end date based on class type, start date, selected days, and per-day durations
     */
    function recalculateEndDate() {
        const startDate = $('#schedule_start_date').val();
        const classType = $('#class_type').val();
        const pattern = $('#schedule_pattern').val();

        // Get session duration from per-day time data or fallback
        let sessionDuration = 0;
        const timeData = getAllTimeData();

        if (timeData.mode === 'per-day' && timeData.perDayTimes) {
            // Use average duration from per-day times
            const durations = Object.values(timeData.perDayTimes).map(day => day.duration);
            if (durations.length > 0) {
                sessionDuration = durations.reduce((sum, duration) => sum + duration, 0) / durations.length;
            }
        } else if (timeData.duration) {
            sessionDuration = parseFloat(timeData.duration);
        }

        if (startDate && classType && pattern && sessionDuration > 0) {
            // Get total hours for this class type
            const classHours = getClassTypeHours(classType);
            $('#schedule_total_hours').val(classHours);

            if (classHours > 0) {
                // Get exception dates
                const exceptionDates = [];
                const $exceptionRows = $('#exception-dates-container .exception-date-row');
                console.log('All exception date rows found:', $exceptionRows.length);

                $exceptionRows.each(function() {
                    const $row = $(this);
                    // Skip template row (has id)
                    if ($row.attr('id') === 'exception-date-row-template') {
                        return;
                    }

                    const date = $row.find('input[name="exception_dates[]"]').val();
                    console.log('Exception date value:', date, 'Row classes:', $row.attr('class'));
                    if (date) {
                        console.log('Adding exception date:', date);
                        exceptionDates.push(date);
                    }
                });

                console.log('Exception dates found:', exceptionDates);

                // Get stop/restart dates
                const stopRestartPeriods = [];
                $('#date-history-container .date-history-row:not(.d-none)').each(function() {
                    const stopDate = $(this).find('input[name="stop_dates[]"]').val();
                    const restartDate = $(this).find('input[name="restart_dates[]"]').val();

                    if (stopDate && restartDate) {
                        stopRestartPeriods.push({
                            stopDate: stopDate,
                            restartDate: restartDate
                        });
                    }
                });

                // Calculate number of sessions needed
                const sessionsNeeded = Math.ceil(classHours / sessionDuration);

                // Calculate end date based on schedule pattern and exception dates
                if (pattern && startDate) {
                    let date = new Date(startDate);
                    let sessionsScheduled = 0;

                    // Weekly pattern
                    if (pattern === 'weekly') {
                        const selectedDays = getSelectedDays();

                        if (selectedDays.length === 0) {
                            return; // Can't calculate without selected days
                        }

                        // Convert selected days to day indices
                        const dayIndices = selectedDays.map(day => getDayIndex(day));

                        // Debug logging for multiple day selection
                        console.log('🗓️ End Date Calculation - Weekly Pattern');
                        console.log('📅 Selected days:', selectedDays);
                        console.log('🔢 Day indices:', dayIndices);
                        console.log('⏰ Sessions needed:', sessionsNeeded);

                        // Set start date to the first occurrence of any selected day
                        const currentDayIndex = date.getDay();
                        if (!dayIndices.includes(currentDayIndex)) {
                            // Find the next occurrence of any selected day
                            let daysToAdd = 1;
                            let nextDate = new Date(date);
                            nextDate.setDate(nextDate.getDate() + daysToAdd);

                            while (!dayIndices.includes(nextDate.getDay())) {
                                daysToAdd++;
                                nextDate = new Date(date);
                                nextDate.setDate(nextDate.getDate() + daysToAdd);
                            }

                            date = nextDate;
                        }

                        // Add days until we have enough sessions
                        while (sessionsScheduled < sessionsNeeded) {
                            const dateStr = date.toISOString().split('T')[0];
                            const currentDayIndex = date.getDay();

                            // Check if this date is a public holiday
                            let isPublicHoliday = false;
                            let isHolidayOverridden = false;

                            if (typeof window.wecozaPublicHolidays !== 'undefined' && window.wecozaPublicHolidays.events) {
                                const matchingHoliday = window.wecozaPublicHolidays.events.find(holiday => {
                                    return holiday.start === dateStr;
                                });

                                if (matchingHoliday) {
                                    isPublicHoliday = true;
                                    // Check if this holiday has been overridden
                                    if (typeof window.holidayOverrides === 'object' && window.holidayOverrides[dateStr] && window.holidayOverrides[dateStr].override === true) {
                                        isHolidayOverridden = true;
                                    }
                                }
                            }

                            // Skip exception dates, stop periods, and public holidays (unless overridden)
                            // Only count days that are in our selected days list
                            const isExceptionDate = exceptionDates.includes(dateStr);
                            const isInStopPeriod = isDateInStopPeriod(dateStr, stopRestartPeriods);

                            if (isExceptionDate) {
                                console.log('Skipping exception date:', dateStr);
                            }

                            if (dayIndices.includes(currentDayIndex) &&
                                !isExceptionDate &&
                                !isInStopPeriod &&
                                (!isPublicHoliday || isHolidayOverridden)) {
                                sessionsScheduled++;
                                console.log('✅ Session scheduled on:', dateStr, 'Day:', getDayName(currentDayIndex), 'Sessions so far:', sessionsScheduled);
                            } else {
                                // Debug why this day was skipped
                                const dayName = getDayName(currentDayIndex);
                                const isSelectedDay = dayIndices.includes(currentDayIndex);
                                console.log('❌ Day skipped:', dateStr, 'Day:', dayName, 'Selected:', isSelectedDay, 'Exception:', isExceptionDate, 'Stop period:', isInStopPeriod, 'Holiday:', isPublicHoliday);
                            }

                            // Move to next day
                            date.setDate(date.getDate() + 1);
                        }
                    }
                    // Bi-weekly pattern
                    else if (pattern === 'biweekly') {
                        const selectedDays = getSelectedDays();

                        if (selectedDays.length === 0) {
                            return; // Can't calculate without selected days
                        }

                        // Convert selected days to day indices
                        const dayIndices = selectedDays.map(day => getDayIndex(day));

                        // Debug logging for multiple day selection
                        console.log('🗓️ End Date Calculation - Bi-weekly Pattern');
                        console.log('📅 Selected days:', selectedDays);
                        console.log('🔢 Day indices:', dayIndices);
                        console.log('⏰ Sessions needed:', sessionsNeeded);

                        // Set start date to the first occurrence of any selected day
                        const currentDayIndex = date.getDay();
                        if (!dayIndices.includes(currentDayIndex)) {
                            // Find the next occurrence of any selected day
                            let daysToAdd = 1;
                            let nextDate = new Date(date);
                            nextDate.setDate(nextDate.getDate() + daysToAdd);

                            while (!dayIndices.includes(nextDate.getDay())) {
                                daysToAdd++;
                                nextDate = new Date(date);
                                nextDate.setDate(nextDate.getDate() + daysToAdd);
                            }

                            date = nextDate;
                        }

                        // Track which week we're in (0 = first week, 1 = second week)
                        let weekCounter = 0;

                        // Add days until we have enough sessions
                        while (sessionsScheduled < sessionsNeeded) {
                            const dateStr = date.toISOString().split('T')[0];
                            const currentDayIndex = date.getDay();

                            // Check if this date is a public holiday
                            let isPublicHoliday = false;
                            let isHolidayOverridden = false;

                            if (typeof window.wecozaPublicHolidays !== 'undefined' && window.wecozaPublicHolidays.events) {
                                const matchingHoliday = window.wecozaPublicHolidays.events.find(holiday => {
                                    return holiday.start === dateStr;
                                });

                                if (matchingHoliday) {
                                    isPublicHoliday = true;
                                    // Check if this holiday has been overridden
                                    if (typeof window.holidayOverrides === 'object' && window.holidayOverrides[dateStr] && window.holidayOverrides[dateStr].override === true) {
                                        isHolidayOverridden = true;
                                    }
                                }
                            }

                            // Skip exception dates, stop periods, and public holidays (unless overridden)
                            // Only count days that are in our selected days list and in the first week of the biweek
                            if (dayIndices.includes(currentDayIndex) &&
                                weekCounter === 0 &&
                                !exceptionDates.includes(dateStr) &&
                                !isDateInStopPeriod(dateStr, stopRestartPeriods) &&
                                (!isPublicHoliday || isHolidayOverridden)) {
                                sessionsScheduled++;
                                console.log('✅ Bi-weekly session scheduled on:', dateStr, 'Day:', getDayName(currentDayIndex), 'Week:', weekCounter, 'Sessions so far:', sessionsScheduled);
                            } else {
                                // Debug why this day was skipped
                                const dayName = getDayName(currentDayIndex);
                                const isSelectedDay = dayIndices.includes(currentDayIndex);
                                const isFirstWeek = weekCounter === 0;
                                console.log('❌ Bi-weekly day skipped:', dateStr, 'Day:', dayName, 'Selected:', isSelectedDay, 'First week:', isFirstWeek, 'Week counter:', weekCounter);
                            }

                            // Move to next day
                            date.setDate(date.getDate() + 1);

                            // Update week counter (0 = first week, 1 = second week)
                            if (date.getDay() === 0) { // If it's Sunday
                                weekCounter = (weekCounter + 1) % 2;
                            }
                        }
                    }
                    // Monthly pattern
                    else if (pattern === 'monthly') {
                        const dayOfMonth = $('#schedule_day_of_month').val();

                        // Add months until we have enough sessions
                        while (sessionsScheduled < sessionsNeeded) {
                            let dateToUse = new Date(date);

                            if (dayOfMonth === 'last') {
                                // Set to last day of the month
                                dateToUse = new Date(date.getFullYear(), date.getMonth() + 1, 0);
                            } else {
                                // Set to specific day of month
                                dateToUse.setDate(parseInt(dayOfMonth));

                                // If day is beyond the end of the month, move to next month
                                if (dateToUse.getMonth() !== date.getMonth()) {
                                    date.setMonth(date.getMonth() + 1);
                                    date.setDate(1);
                                    continue;
                                }
                            }

                            const dateStr = dateToUse.toISOString().split('T')[0];

                            // Check if this date is a public holiday
                            let isPublicHoliday = false;
                            let isHolidayOverridden = false;

                            if (typeof window.wecozaPublicHolidays !== 'undefined' && window.wecozaPublicHolidays.events) {
                                const matchingHoliday = window.wecozaPublicHolidays.events.find(holiday => {
                                    return holiday.start === dateStr;
                                });

                                if (matchingHoliday) {
                                    isPublicHoliday = true;
                                    // Check if this holiday has been overridden
                                    if (typeof window.holidayOverrides === 'object' && window.holidayOverrides[dateStr] && window.holidayOverrides[dateStr].override === true) {
                                        isHolidayOverridden = true;
                                    }
                                }
                            }

                            // Skip exception dates, stop periods, and public holidays (unless overridden)
                            if (!exceptionDates.includes(dateStr) &&
                                !isDateInStopPeriod(dateStr, stopRestartPeriods) &&
                                (!isPublicHoliday || isHolidayOverridden)) {
                                sessionsScheduled++;
                            }

                            // Move to next month
                            date.setMonth(date.getMonth() + 1);
                            date.setDate(1);
                        }
                    }

                    // Format date as YYYY-MM-DD
                    const endDate = date.toISOString().split('T')[0];
                    $('#schedule_end_date').val(endDate);

                    // Debug summary
                    console.log('🎯 End Date Calculation Complete');
                    console.log('📊 Total sessions scheduled:', sessionsScheduled, 'of', sessionsNeeded, 'needed');
                    console.log('📅 Final end date:', endDate);
                    console.log('🗓️ Selected days used in calculation:', getSelectedDays());

                    // Update schedule tables
                    updateScheduleTables();
                }
            }
        }
    }

    /**
     * Update schedule tables with current data
     */
    function updateScheduleTables() {
        // Get schedule data
        const pattern = $('#schedule_pattern').val();
        const startDate = $('#schedule_start_date').val();
        const endDate = $('#schedule_end_date').val();

        // Get time data from current mode
        const timeData = getAllTimeData();
        let startTime = '';
        let endTime = '';

        if (timeData.mode === 'single') {
            startTime = timeData.startTime || '';
            endTime = timeData.endTime || '';
        } else if (timeData.mode === 'per-day' && timeData.perDayTimes) {
            // Use first day's times for display
            const firstDay = Object.keys(timeData.perDayTimes)[0];
            if (firstDay) {
                startTime = timeData.perDayTimes[firstDay].startTime || '';
                endTime = timeData.perDayTimes[firstDay].endTime || '';
            }
        }

        // Format pattern for display
        let patternDisplay = '';
        switch(pattern) {
            case 'weekly':
                patternDisplay = 'Weekly';
                break;
            case 'biweekly':
                patternDisplay = 'Bi-weekly';
                break;
            case 'monthly':
                patternDisplay = 'Monthly';
                break;
            case 'custom':
                patternDisplay = 'Custom';
                break;
            default:
                patternDisplay = pattern;
        }

        // Format dates for display
        const formatDate = function(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-ZA', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        };

        // Update schedule summary table if it exists
        $('#schedule-summary-pattern').text(patternDisplay);
        $('#schedule-summary-start-date').text(formatDate(startDate));
        $('#schedule-summary-end-date').text(formatDate(endDate));
        $('#schedule-summary-class-time').text(startTime && endTime ? `${startTime} - ${endTime}` : '');

        // Get selected days
        const selectedDays = getSelectedDays();
        $('#schedule-summary-days').text(selectedDays.join(', '));

        // Update exception dates table if function exists
        if (typeof updateExceptionDatesTable === 'function') {
            updateExceptionDatesTable();
        }

        // Update holidays table if function exists
        if (typeof updateHolidaysTable === 'function') {
            updateHolidaysTable(startDate, endDate);
        }
    }

    /**
     * Update exception dates table
     */
    function updateExceptionDatesTable() {
        const $exceptionDatesTable = $('#exception-dates-table');
        const $noExceptionDatesRow = $('#no-exception-dates-row');

        if (!$exceptionDatesTable.length) {
            return; // No exception dates table found
        }

        // Get all exception date rows
        const exceptionDates = [];
        $('#exception-dates-container .exception-date-row:not(.d-none)').each(function() {
            const $row = $(this);
            const date = $row.find('input[name="exception_dates[]"]').val();
            const reason = $row.find('select[name="exception_reasons[]"]').val();

            if (date) {
                exceptionDates.push({
                    date: date,
                    reason: reason || ''
                });
            }
        });

        // Clear existing rows except the "no exception dates" row
        $exceptionDatesTable.find('tr:not(#no-exception-dates-row)').remove();

        // Show/hide "no exception dates" row
        if (exceptionDates.length === 0) {
            $noExceptionDatesRow.show();
            return;
        } else {
            $noExceptionDatesRow.hide();
        }

        // Format dates for display
        const formatDate = function(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-ZA', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        };

        // Add a row for each exception date
        exceptionDates.forEach(function(exceptionDate) {
            const formattedDate = formatDate(exceptionDate.date);
            const reasonText = exceptionDate.reason || 'No reason specified';

            const $row = $('<tr></tr>');
            $row.append('<td>' + formattedDate + '</td>');
            $row.append('<td>' + reasonText + '</td>');

            $exceptionDatesTable.append($row);
        });
    }

    /**
     * Update holidays table
     */
    function updateHolidaysTable(startDate, endDate) {
        const $holidaysTable = $('#holidays-table');
        const $noHolidaysRow = $('#no-holidays-row');
        const selectedDays = getSelectedDays();
        const pattern = $('#schedule_pattern').val();

        if (!$holidaysTable.length) {
            return; // No holidays table found
        }

        // If no public holidays data, show "no holidays" row and return
        if (typeof window.wecozaPublicHolidays === 'undefined' || !window.wecozaPublicHolidays.events) {
            $holidaysTable.find('tr:not(#no-holidays-row)').remove();
            $noHolidaysRow.show();
            return;
        }

        // If no start date or end date, show "no holidays" row and return
        if (!startDate || !endDate) {
            $holidaysTable.find('tr:not(#no-holidays-row)').remove();
            $noHolidaysRow.show();
            return;
        }

        // Convert dates to Date objects for comparison
        const startDateObj = new Date(startDate);
        const endDateObj = new Date(endDate);

        // Filter holidays to only include those within the date range
        const holidaysInRange = window.wecozaPublicHolidays.events.filter(holiday => {
            // Parse the date parts to ensure correct date (avoid timezone issues)
            const [year, month, day] = holiday.start.split('-').map(Number);
            const holidayDate = new Date(year, month - 1, day);
            return holidayDate >= startDateObj && holidayDate <= endDateObj;
        });

        // Filter to only include holidays that conflict with the schedule
        const conflictingHolidays = holidaysInRange.filter(holiday => {
            // Parse the date parts to ensure correct date (avoid timezone issues)
            const [year, month, day] = holiday.start.split('-').map(Number);
            const holidayDate = new Date(year, month - 1, day);
            const dayName = holidayDate.toLocaleDateString('en-US', { weekday: 'long' });

            // For weekly/biweekly patterns, check if the holiday falls on a selected day
            return (pattern === 'weekly' || pattern === 'biweekly') && selectedDays.includes(dayName);
        });

        // Clear existing rows except the "no holidays" row
        $holidaysTable.find('tr:not(#no-holidays-row)').remove();

        // Show/hide "no holidays" row
        if (conflictingHolidays.length === 0) {
            $noHolidaysRow.show();
            return;
        } else {
            $noHolidaysRow.hide();
        }

        // Format dates for display
        const formatDate = function(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-ZA', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        };

        // Add a row for each holiday
        conflictingHolidays.forEach(function(holiday) {
            const formattedDate = formatDate(holiday.start);
            const holidayName = holiday.title;

            // Check if this holiday has been overridden
            let isOverridden = false;
            if (typeof window.holidayOverrides === 'object' && window.holidayOverrides[holiday.start]) {
                isOverridden = window.holidayOverrides[holiday.start].override;
            }

            const $row = $('<tr></tr>');
            $row.append('<td>' + formattedDate + '</td>');
            $row.append('<td>' + holidayName + '</td>');

            // Add status badge
            if (isOverridden) {
                $row.append('<td><span class="badge bg-warning text-dark">Included</span></td>');
            } else {
                $row.append('<td><span class="badge bg-danger">Skipped</span></td>');
            }

            $holidaysTable.append($row);
        });
    }

    function restrictStartDateBySelectedDays() {
        // Placeholder for start date restriction functionality
    }

    // Initialize when document is ready
    $(document).ready(function() {
        // Check if we're on a page with the class schedule form
        if ($('#schedule_pattern').length > 0) {
            initClassScheduleForm();
        } else {
            // Try again after a short delay in case of timing issues
            setTimeout(function() {
                if ($('#schedule_pattern').length > 0) {
                    initClassScheduleForm();
                }
            }, 1000);
        }
    });

})(jQuery);
