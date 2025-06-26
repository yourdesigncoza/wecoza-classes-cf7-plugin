<?php
/**
 * Single Class Display View - Modern Layout
 *
 * This view displays detailed information for a single class from the database in a modern Bootstrap 5 layout.
 * Used by the [wecoza_display_single_class] shortcode.
 *
 * Available Variables:
 *   - $class: Array of class data from the database
 *   - $show_loading: Boolean indicating whether to show loading indicator
 *   - $error_message: String containing error message if class not found or invalid
 *
 * Database Fields Displayed:
 *   - class_id, class_code, class_subject, class_type
 *   - original_start_date, delivery_date, class_duration
 *   - client information (name, ID)
 *   - agent information (name, ID)
 *   - supervisor information (name, ID)
 *   - SETA funding status and details
 *   - exam class status and type
 *   - class address information
 *
 * @package WeCoza
 * @see \WeCoza\Controllers\ClassController::displaySingleClassShortcode() For the controller method that renders this view
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

// Ensure we have the class data
$class = $class ?? null;
$show_loading = $show_loading ?? true;
$error_message = $error_message ?? '';
?>

<div class="wecoza-single-class-display">
    <!-- Loading Indicator -->
    <?php if ($show_loading): ?>
    <div id="single-class-loading" class="d-flex justify-content-center align-items-center py-4">
        <div class="spinner-border text-primary me-3" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <span class="text-muted">Loading class details...</span>
    </div>
    <?php endif; ?>

    <!-- Class Content -->
    <div id="single-class-content" class="<?php echo $show_loading ? 'd-none' : ''; ?>">

        <?php if (!empty($error_message)): ?>
            <!-- Error Message -->
            <div class="alert alert-danger d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
                <div>
                    <h6 class="alert-heading mb-1">Error Loading Class</h6>
                    <p class="mb-0"><?php echo esc_html($error_message); ?></p>
                </div>
            </div>
        <?php elseif (empty($class)): ?>
            <!-- No Class Found -->
            <div class="alert alert-warning d-flex align-items-center">
                <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                <div>
                    <h6 class="alert-heading mb-1">Class Not Found</h6>
                    <p class="mb-0">The requested class could not be found in the database.</p>
                </div>
            </div>
        <?php else: ?>
            <!-- Class Details -->


            <!-- Action Buttons -->
            <?php if (current_user_can('edit_posts') || current_user_can('manage_options')): ?>
            <div class="d-flex justify-content-end mb-4">
                <div class="col-12 col-md-auto d-flex">
                    <?php if (current_user_can('edit_posts') || current_user_can('manage_options')): ?>
                    <button class="btn btn-phoenix-secondary px-3 px-sm-5 me-2" onclick="editClass(<?php echo esc_js($class['class_id']); ?>)">
                        <i class="bi bi-pencil-square me-sm-2"></i>
                        <span class="d-none d-sm-inline">Edit</span>
                    </button>
                    <?php endif; ?>

                    <?php if (current_user_can('manage_options')): ?>
                    <button class="btn btn-phoenix-danger me-2" onclick="deleteClass(<?php echo esc_js($class['class_id']); ?>)">
                        <i class="bi bi-trash me-2"></i>
                        <span>Delete Class</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Top Summary Cards -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-4 justify-content-between">
                        <!-- Client Card -->
                        <div class="col-sm-auto">
                            <div class="d-flex align-items-center">
                                <div class="d-flex bg-primary-subtle rounded flex-center me-3" style="width:32px; height:32px">
                                    <i class="bi bi-building text-primary"></i>
                                </div>
                                <div>
                                    <p class="fw-bold mb-1">Client</p>
                                    <h5 class="fw-bolder text-nowrap">
                                        <?php if (!empty($class['client_name'])): ?>
                                            <?php echo esc_html($class['client_name']); ?>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </h5>
                                </div>
                            </div>
                        </div>
                        <!-- Class Type Card -->
                        <div class="col-sm-auto">
                            <div class="d-flex align-items-center border-start-sm ps-sm-5">
                                <div class="d-flex bg-primary-subtle rounded flex-center me-3" style="width:32px; height:32px">
                                    <i class="bi bi-layers text-primary"></i>
                                </div>
                                <div>
                                    <p class="fw-bold mb-1">Class Type</p>
                                    <h5 class="fw-bolder text-nowrap"><?php echo esc_html($class['class_type'] ?? 'Unknown Type'); ?></h5>
                                </div>
                            </div>
                        </div>
                        <!-- Class Subject Card -->
                        <div class="col-sm-auto">
                            <div class="d-flex align-items-center border-start-sm ps-sm-5">
                                <div class="d-flex bg-success-subtle rounded flex-center me-3" style="width:32px; height:32px">
                                    <i class="bi bi-book text-success"></i>
                                </div>
                                <div>
                                    <p class="fw-bold mb-1">Class Subject</p>
                                    <h5 class="fw-bolder text-nowrap"><?php echo esc_html($class['class_subject'] ?? 'N/A'); ?></h5>
                                </div>
                            </div>
                        </div>
                        <!-- Class Code Card -->
                        <div class="col-sm-auto">
                            <div class="d-flex align-items-center border-start-sm ps-sm-5">
                                <div class="d-flex bg-info-subtle rounded flex-center me-3" style="width:32px; height:32px">
                                    <i class="bi bi-tag text-info"></i>
                                </div>
                                <div>
                                    <p class="fw-bold mb-1">Class Code</p>
                                    <h5 class="fw-bolder text-nowrap"><?php echo esc_html($class['class_code'] ?? 'N/A'); ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Tables -->
            <div class="px-xl-4 mb-7">
                <div class="row mx-0">
                    <!-- Left Column - Basic Information -->
                    <div class="col-sm-12 col-xxl-6 border-bottom border-end-xxl py-3">
                        <table class="w-100 table-stats table table-hover table-sm fs-9 mb-0">
                            <tbody>
                                <tr>
                                    <td class="py-2 ydcoza-w-150">
                                        <div class="d-inline-flex align-items-center">
                                            <div class="d-flex bg-primary-subtle rounded-circle flex-center me-3" style="width:24px; height:24px">
                                                <i class="bi bi-hash text-primary" style="font-size: 12px;"></i>
                                            </div>
                                            <p class="fw-bold mb-0">Class ID : </p>
                                        </div>
                                    </td>
                                    <td class="py-2">
                                        <p class="fw-semibold mb-0">#<?php echo esc_html($class['class_id']); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex bg-warning-subtle rounded-circle flex-center me-3" style="width:24px; height:24px">
                                                <i class="bi bi-clock text-warning" style="font-size: 12px;"></i>
                                            </div>
                                            <p class="fw-bold mb-0">Duration :</p>
                                        </div>
                                    </td>
                                    <td class="py-2">
                                        <p class="fw-semibold mb-0">
                                            <?php if (!empty($class['class_duration'])): ?>
                                                <?php echo esc_html($class['class_duration']); ?> hours
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex bg-success-subtle rounded-circle flex-center me-3" style="width:24px; height:24px">
                                                <i class="bi bi-geo-alt text-success" style="font-size: 12px;"></i>
                                            </div>
                                            <p class="fw-bold mb-0">Address : </p>
                                        </div>
                                    </td>                                    
                                    <td class="py-2">
                                        <p class="fw-semibold mb-0"><?php echo esc_html($class['class_address_line'] ?? 'N/A'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2 ydcoza-w-150">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex bg-success-subtle rounded-circle flex-center me-3" style="width:24px; height:24px">
                                                <i class="bi bi-person-badge text-success" style="font-size: 12px;"></i>
                                            </div>
                                            <p class="fw-bold mb-0">Agent : </p>
                                        </div>
                                    </td>
                                    <td class="py-2">
                                        <div class="fw-semibold mb-0">
                                            <?php if (!empty($class['agent_name'])): ?>
                                                <?php echo esc_html($class['agent_name']); ?>
                                                <div class="fs-9 text-muted">ID: <?php echo esc_html($class['class_agent']); ?></div>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex bg-warning-subtle rounded-circle flex-center me-3" style="width:24px; height:24px">
                                                <i class="bi bi-person-gear text-warning" style="font-size: 12px;"></i>
                                            </div>
                                            <p class="fw-bold mb-0">Supervisor : </p>
                                        </div>
                                    </td>
                                    <td class="py-2">
                                        <div class="fw-semibold mb-0">
                                            <?php if (!empty($class['supervisor_name'])): ?>
                                                <?php echo esc_html($class['supervisor_name']); ?>
                                                <div class="fs-9 text-muted">ID: <?php echo esc_html($class['project_supervisor_id']); ?></div>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Right Column - Dates & Schedule -->
                    <div class="col-sm-12 col-xxl-6 border-bottom py-3">
                        <table class="w-100 table-stats table table-hover table-sm fs-9 mb-0">
                            <tbody>
                                <tr>
                                    <td class="py-2 ydcoza-w-150">
                                        <div class="d-inline-flex align-items-center">
                                            <div class="d-flex bg-info-subtle rounded-circle flex-center me-3" style="width:24px; height:24px">
                                                <i class="bi bi-calendar-plus text-info" style="font-size: 12px;"></i>
                                            </div>
                                            <p class="fw-bold mb-0">Start Date : </p>
                                        </div>
                                    </td>
                                    <td class="py-2">
                                        <p class="fw-semibold mb-0">
                                            <?php if (!empty($class['original_start_date'])): ?>
                                                <?php echo esc_html(date('M j, Y', strtotime($class['original_start_date']))); ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex bg-warning-subtle rounded-circle flex-center me-3" style="width:24px; height:24px">
                                                <i class="bi bi-truck text-warning" style="font-size: 12px;"></i>
                                            </div>
                                            <p class="fw-bold mb-0">Delivery Date : </p>
                                        </div>
                                    </td>
                                    <td class="py-2">
                                        <p class="fw-semibold mb-0">
                                            <?php if (!empty($class['delivery_date'])): ?>
                                                <?php echo esc_html(date('M j, Y', strtotime($class['delivery_date']))); ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex bg-secondary-subtle rounded-circle flex-center me-3" style="width:24px; height:24px">
                                                <i class="bi bi-calendar-check text-secondary" style="font-size: 12px;"></i>
                                            </div>
                                            <p class="fw-bold mb-0">Created : </p>
                                        </div>
                                    </td>
                                    <td class="py-2">
                                        <p class="fw-semibold mb-0">
                                            <?php if (!empty($class['created_at'])): ?>
                                                <?php echo esc_html(date('M j, Y g:i A', strtotime($class['created_at']))); ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex bg-primary-subtle rounded-circle flex-center me-3" style="width:24px; height:24px">
                                                <i class="bi bi-calendar-event text-primary" style="font-size: 12px;"></i>
                                            </div>
                                            <p class="fw-bold mb-0">Last Updated : </p>
                                        </div>
                                    </td>
                                    <td class="py-2">
                                        <p class="fw-semibold mb-0">
                                            <?php if (!empty($class['updated_at'])): ?>
                                                <?php echo esc_html(date('M j, Y g:i A', strtotime($class['updated_at']))); ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex bg-info-subtle rounded-circle flex-center me-3" style="width:24px; height:24px">
                                                <i class="bi bi-calendar3 text-info" style="font-size: 12px;"></i>
                                            </div>
                                            <p class="fw-bold mb-0">QA Visit Dates : </p>
                                        </div>
                                    </td>
                                    <td class="py-2">
                                        <p class="fw-semibold mb-0">
                                            <?php if (!empty($class['qa_visit_dates'])): ?>
                                                <?php echo esc_html($class['qa_visit_dates']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Bottom Left - People & Staff -->
                    <div class="col-sm-12 col-xxl-6 border-end-xxl py-3">
                        <table class="w-100 table-stats table table-hover table-sm fs-9 mb-0">
                            <tbody>

                                <tr>
                                    <td class="py-2">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex bg-info-subtle rounded-circle flex-center me-3" style="width:24px; height:24px">
                                                <i class="bi bi-people text-info" style="font-size: 12px;"></i>
                                            </div>
                                            <p class="fw-bold mb-0">Learners : </p>
                                        </div>
                                    </td>
                                    <td class="py-2">
                                        <div class="fw-semibold mb-0">
                                            <?php
                                            // Get learner_ids data (should already be decoded by controller)
                                            $learners = $class['learner_ids'] ?? [];

                                            if (!empty($learners) && is_array($learners)):
                                                $learnerCount = count($learners);
                                            ?>
                                                <span class="badge bg-primary me-2"><?php echo $learnerCount; ?> Learner<?php echo $learnerCount !== 1 ? 's' : ''; ?></span>
                                                <div class="mt-2">
                                                    <?php foreach ($learners as $index => $learner): ?>
                                                        <?php if ($index < 3): // Show first 3 learners ?>
                                                            <div class="fs-9 mb-1">
                                                                <i class="bi bi-person-fill me-1"></i>
                                                                <?php echo esc_html($learner['name'] ?? 'Unknown'); ?>
                                                                <?php if (!empty($learner['status'])): ?>
                                                                    <span class="text-secondary text-muted ">(<?php echo esc_html($learner['status']); ?>)</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                    <?php if ($learnerCount > 3): ?>
                                                        <div class="fs-9 text-muted mb-2">
                                                            <i class="bi bi-three-dots me-1"></i>
                                                            and <?php echo ($learnerCount - 3); ?> more learner<?php echo ($learnerCount - 3) !== 1 ? 's' : ''; ?>
                                                        </div>
                                                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#learnersModal">
                                                            <i class="bi bi-eye me-1"></i>View All Learners
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">No learners assigned</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bottom Right - SETA & Exam Information -->
                    <div class="col-sm-12 col-xxl-6 py-3">
                        <table class="w-100 table-stats table table-hover table-sm fs-9 mb-0">
                            <tbody>
                                <tr>
                                    <td class="py-2 ydcoza-w-150">
                                        <div class="d-inline-flex align-items-center">
                                            <div class="d-flex bg-success-subtle rounded-circle flex-center me-3" style="width:24px; height:24px">
                                                <i class="bi bi-check-circle text-success" style="font-size: 12px;"></i>
                                            </div>
                                            <p class="fw-bold mb-0">SETA Funded : </p>
                                        </div>
                                    </td>
                                    <td class="py-2">
                                        <div class="fw-semibold mb-0">
                                            <?php if ($class['seta_funded']): ?>
                                                <span>Yes</span>
                                            <?php else: ?>
                                                <span>No</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex bg-info-subtle rounded-circle flex-center me-3" style="width:24px; height:24px">
                                                <i class="bi bi-building-gear text-info" style="font-size: 12px;"></i>
                                            </div>
                                            <p class="fw-bold mb-0">SETA Name : </p>
                                        </div>
                                    </td>
                                    <td class="py-2">
                                        <div class="fw-semibold mb-0">
                                            <?php echo esc_html($class['seta'] ?? 'N/A'); ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex bg-warning-subtle rounded-circle flex-center me-3" style="width:24px; height:24px">
                                                <i class="bi bi-mortarboard text-warning" style="font-size: 12px;"></i>
                                            </div>
                                            <p class="fw-bold mb-0">Exam Class : </p>
                                        </div>
                                    </td>
                                    <td class="py-2">
                                        <div class="fw-semibold mb-0">
                                            <?php if ($class['exam_class']): ?>
                                                <span>Yes</span>
                                            <?php else: ?>
                                                <span>No</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex bg-primary-subtle rounded-circle flex-center me-3" style="width:24px; height:24px">
                                                <i class="bi bi-clipboard-check text-primary" style="font-size: 12px;"></i>
                                            </div>
                                            <p class="fw-bold mb-0">Exam Type : </p>
                                        </div>
                                    </td>
                                    <td class="py-2">
                                        <div class="fw-semibold mb-0">
                                            <?php echo esc_html($class['exam_type'] ?? 'N/A'); ?>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Class Calendar Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="bi bi-calendar3 me-2"></i>Class Schedule Calendar
                    </h4>
                </div>
                <div class="card-body">
                    <!-- View Toggle Navigation -->
                    <div class="mb-3">
                        <ul class="nav nav-tabs" id="scheduleViewTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="calendar-view-tab" data-bs-toggle="tab" data-bs-target="#calendar-view" type="button" role="tab" aria-controls="calendar-view" aria-selected="true">
                                    <i class="bi bi-calendar3 me-2"></i>Calendar View
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="list-view-tab" data-bs-toggle="tab" data-bs-target="#list-view" type="button" role="tab" aria-controls="list-view" aria-selected="false">
                                    <i class="bi bi-list-ul me-2"></i>List View
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Tab Content Container -->
                    <div class="tab-content" id="scheduleViewContent">
                        <!-- Calendar View Tab Pane -->
                        <div class="tab-pane fade show active" id="calendar-view" role="tabpanel" aria-labelledby="calendar-view-tab">
                            <!-- Calendar Container -->
                            <div id="classCalendar" class="mb-4">
                                <!-- FullCalendar will be rendered here -->
                            </div>

                            <!-- Calendar Loading State -->
                            <div id="calendar-loading" class="text-center py-4">
                                <div class="spinner-border text-primary me-2" role="status">
                                    <span class="visually-hidden">Loading calendar...</span>
                                </div>
                                <span class="text-muted">Loading class schedule...</span>
                            </div>

                            <!-- Calendar Error State -->
                            <div id="calendar-error" class="alert alert-warning d-none">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Calendar Unavailable:</strong>
                                <span id="calendar-error-message">Unable to load class schedule data.</span>
                            </div>

                            <!-- Calendar Legend -->
                            <div class="calendar-legend">
                                <div class="legend-item">
                                    <div class="legend-color class-event"></div>
                                    <span>Class Sessions</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color public-holiday"></div>
                                    <span>Public Holidays</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color exception"></div>
                                    <span>Exception Dates</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color stop-restart"></div>
                                    <span>Stop/Restart Dates</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color stop-period"></div>
                                    <span>Stop Period Days</span>
                                </div>
                            </div>
                        </div>

                        <!-- List View Tab Pane -->
                        <div class="tab-pane fade" id="list-view" role="tabpanel" aria-labelledby="list-view-tab">
                            <!-- List View Filters -->
                            <div id="listViewFilters" class="mb-3 d-none">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label for="eventTypeFilter" class="form-label text-muted small">Filter by Event Type</label>
                                        <select id="eventTypeFilter" class="form-select form-select-sm">
                                            <option value="">All Event Types</option>
                                            <option value="class_session">Class Sessions</option>
                                            <option value="public_holiday">Public Holidays</option>
                                            <option value="exception">Exception Dates</option>
                                            <option value="stop_date">Stop/Restart Dates</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="dateFromFilter" class="form-label text-muted small">From Date</label>
                                        <input type="date" id="dateFromFilter" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="dateToFilter" class="form-label text-muted small">To Date</label>
                                        <input type="date" id="dateToFilter" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" id="clearFilters" class="btn btn-outline-secondary btn-sm w-100">
                                            <i class="bi bi-x-circle me-1"></i>Clear
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- List View Container -->
                            <div id="classScheduleList">
                                <!-- List view content will be rendered here -->
                            </div>

                            <!-- List View Loading State -->
                            <div id="list-loading" class="text-center py-4 d-none">
                                <div class="spinner-border text-primary me-2" role="status">
                                    <span class="visually-hidden">Loading schedule list...</span>
                                </div>
                                <span class="text-muted">Loading class schedule list...</span>
                            </div>

                            <!-- List View Error State -->
                            <div id="list-error" class="alert alert-warning d-none">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>List View Unavailable:</strong>
                                <span id="list-error-message">Unable to load class schedule data.</span>
                            </div>

                            <!-- List View Empty State -->
                            <div id="list-empty" class="text-center py-5 d-none">
                                <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                                <h5 class="text-muted mt-3">No Schedule Events Found</h5>
                                <p class="text-muted">There are no scheduled events to display for this class.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>

<!-- Learners Modal -->
<?php if (!empty($learners) && is_array($learners) && count($learners) > 0): ?>
<div class="modal fade" id="learnersModal" tabindex="-1" aria-labelledby="learnersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="learnersModalLabel">
                    <i class="bi bi-people me-2"></i>Class : <?php echo esc_html($class['class_code'] ?? 'N/A'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">

                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="badge bg-primary fs-9"><?php echo count($learners); ?> Total Learner<?php echo count($learners) !== 1 ? 's' : ''; ?></span>
                            <small class="text-muted">Class: <?php echo esc_html($class['class_subject'] ?? 'N/A'); ?></small>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm fs-9 mb-0">
                        <thead>
                            <tr class="bg-body-highlight">
                                <th class="border-top border-translucent ps-3">Learner Name</th>
                                <th class="border-top border-translucent">Status</th>
                                <th class="border-top border-translucent text-end pe-3">Level/Module</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($learners as $index => $learner): ?>
                                <tr>
                                    <td class="align-middle ps-3">
                                        <?php
                                        // Handle different learner data formats
                                        $learnerName = 'Unknown Learner';
                                        if (is_array($learner)) {
                                            // New format: array with name field
                                            if (isset($learner['name'])) {
                                                $learnerName = $learner['name'];
                                            }
                                            // Legacy format: might have first_name/surname
                                            elseif (isset($learner['first_name']) || isset($learner['surname'])) {
                                                $learnerName = trim(($learner['first_name'] ?? '') . ' ' . ($learner['surname'] ?? ''));
                                            }
                                            // ID only format
                                            elseif (isset($learner['id'])) {
                                                $learnerName = 'Learner ID: ' . $learner['id'];
                                            }
                                        } elseif (is_numeric($learner)) {
                                            // Legacy format: just an ID
                                            $learnerName = 'Learner ID: ' . $learner;
                                        }

                                        echo esc_html($learnerName);
                                        ?>
                                    </td>
                                    <td class="align-middle">
                                        <?php
                                        $learnerStatus = '';
                                        if (is_array($learner) && isset($learner['status'])) {
                                            $learnerStatus = $learner['status'];
                                        }

                                        if (!empty($learnerStatus)): ?>
                                            <?php
                                            $statusClass = 'secondary';
                                            $statusIcon = 'bi-person';
                                            if ($learnerStatus === 'CIC - Currently in Class') {
                                                $statusClass = 'success';
                                                $statusIcon = 'bi-check';
                                            } elseif (strpos(strtolower($learnerStatus), 'walk') !== false) {
                                                $statusClass = 'warning';
                                                $statusIcon = 'bi-bars-staggered';
                                            }
                                            ?>
                                            <div class="badge bg-<?php echo $statusClass; ?> fs-10">
                                                <span class="fw-bold"><?php echo esc_html($learnerStatus); ?></span>
                                                <i class="<?php echo $statusIcon; ?> ms-1"></i>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle text-end py-3 pe-3">
                                        <?php
                                        $learnerLevel = '';
                                        if (is_array($learner) && isset($learner['level'])) {
                                            $learnerLevel = $learner['level'];
                                        }

                                        if (!empty($learnerLevel)): ?>
                                            <span class="text-body-secondary"><?php echo esc_html($learnerLevel); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination info (static for now) -->
                <div class="d-flex justify-content-between mt-3">
                    <span class="d-none d-sm-inline-block">
                        1 to <?php echo count($learners); ?> <span class="text-body-tertiary">Items of</span> <?php echo count($learners); ?>
                    </span>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- JavaScript for functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hide loading indicator and show content after a brief delay
    <?php if ($show_loading): ?>
    setTimeout(function() {
        const loading = document.getElementById('single-class-loading');
        const content = document.getElementById('single-class-content');

        if (loading) loading.classList.add('d-none');
        if (content) content.classList.remove('d-none');
    }, 500);
    <?php endif; ?>

    // Initialize FullCalendar if the calendar container exists
    if (document.getElementById('classCalendar')) {
        console.log('Calendar container found, initializing...');
        initializeClassCalendar();
    } else {
        console.log('Calendar container not found');
    }

    // Initialize view toggle functionality
    initializeViewToggle();
});

/**
 * Initialize FullCalendar for the class schedule
 * Following WordPress best practices
 */
function initializeClassCalendar() {
    console.log('initializeClassCalendar called');

    // Check if FullCalendar is loaded
    console.log('FullCalendar available:', typeof FullCalendar !== 'undefined');
    console.log('WeCozaCalendar available:', typeof window.WeCozaCalendar !== 'undefined');

    // Pass class data to JavaScript
    const classData = {
        id: <?php echo json_encode($class['class_id'] ?? null); ?>,
        code: <?php echo json_encode($class['class_code'] ?? ''); ?>,
        subject: <?php echo json_encode($class['class_subject'] ?? ''); ?>,
        startDate: <?php echo json_encode($class['original_start_date'] ?? ''); ?>,
        deliveryDate: <?php echo json_encode($class['delivery_date'] ?? ''); ?>,
        duration: <?php echo json_encode($class['class_duration'] ?? ''); ?>,
        scheduleData: <?php echo json_encode($class['schedule_data'] ?? null); ?>,
        ajaxUrl: <?php echo json_encode(admin_url('admin-ajax.php')); ?>,
        nonce: <?php echo json_encode(wp_create_nonce('wecoza_calendar_nonce')); ?>
    };

    console.log('Class data:', classData);

    // Initialize the calendar with the class data
    if (typeof window.WeCozaCalendar !== 'undefined') {
        console.log('Initializing WeCoza Calendar...');
        window.WeCozaCalendar.init(classData);
    } else {
        console.warn('WeCoza Calendar library not loaded');
        showCalendarError('Calendar library not available');
    }
}

/**
 * Show calendar error message
 */
function showCalendarError(message) {
    const loadingEl = document.getElementById('calendar-loading');
    const errorEl = document.getElementById('calendar-error');
    const messageEl = document.getElementById('calendar-error-message');

    if (loadingEl) loadingEl.style.display = 'none';
    if (errorEl) {
        errorEl.classList.remove('d-none');
        if (messageEl) messageEl.textContent = message;
    }
}

/**
 * Initialize view toggle functionality
 * Handles switching between calendar and list views with state persistence
 */
function initializeViewToggle() {
    console.log('Initializing view toggle functionality...');

    // Get tab elements
    const calendarTab = document.getElementById('calendar-view-tab');
    const listTab = document.getElementById('list-view-tab');

    if (!calendarTab || !listTab) {
        console.warn('View toggle tabs not found');
        return;
    }

    // Load saved view preference from localStorage
    const savedView = localStorage.getItem('wecoza_schedule_view_preference');
    if (savedView === 'list') {
        // Switch to list view if it was the last selected view
        setTimeout(() => {
            listTab.click();
        }, 100);
    }

    // Add event listeners for tab switching
    calendarTab.addEventListener('shown.bs.tab', function(e) {
        console.log('Switched to calendar view');
        localStorage.setItem('wecoza_schedule_view_preference', 'calendar');

        // Refresh calendar when switching to calendar view
        if (typeof window.WeCozaCalendar !== 'undefined' && window.WeCozaCalendar.refreshEvents) {
            window.WeCozaCalendar.refreshEvents();
        }
    });

    listTab.addEventListener('shown.bs.tab', function(e) {
        console.log('Switched to list view');
        localStorage.setItem('wecoza_schedule_view_preference', 'list');

        // Load list view data when switching to list view
        loadListViewData();
    });
}

/**
 * Load and display data for list view
 * Fetches the same event data used by the calendar
 */
function loadListViewData() {
    console.log('Loading list view data...');

    const listContainer = document.getElementById('classScheduleList');
    const listLoading = document.getElementById('list-loading');
    const listError = document.getElementById('list-error');
    const listEmpty = document.getElementById('list-empty');

    if (!listContainer) {
        console.error('List container not found');
        return;
    }

    // Show loading state
    listLoading.classList.remove('d-none');
    listError.classList.add('d-none');
    listEmpty.classList.add('d-none');
    listContainer.innerHTML = '';

    // Get class data (same as used for calendar)
    const classData = {
        id: <?php echo json_encode($class['class_id'] ?? null); ?>,
        code: <?php echo json_encode($class['class_code'] ?? ''); ?>,
        subject: <?php echo json_encode($class['class_subject'] ?? ''); ?>,
        ajaxUrl: <?php echo json_encode(admin_url('admin-ajax.php')); ?>,
        nonce: <?php echo json_encode(wp_create_nonce('wecoza_calendar_nonce')); ?>
    };

    // Fetch calendar events data
    Promise.all([
        // Fetch class events
        fetch(classData.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_calendar_events',
                class_id: classData.id,
                nonce: classData.nonce
            })
        }),
        // Fetch public holidays
        fetch(classData.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_public_holidays',
                year: new Date().getFullYear(),
                nonce: classData.nonce
            })
        })
    ])
    .then(responses => Promise.all(responses.map(r => r.json())))
    .then(([classEvents, holidays]) => {
        // Combine and process events
        const allEvents = [...(classEvents || []), ...(holidays || [])];

        // Hide loading state
        listLoading.classList.add('d-none');

        if (allEvents.length === 0) {
            listEmpty.classList.remove('d-none');
        } else {
            // Store events globally for filtering
            window.currentListViewEvents = allEvents;
            renderListView(allEvents, classData);
            initializeListViewFilters();
        }
    })
    .catch(error => {
        console.error('Error loading list view data:', error);
        listLoading.classList.add('d-none');
        listError.classList.remove('d-none');

        const errorMessage = document.getElementById('list-error-message');
        if (errorMessage) {
            errorMessage.textContent = 'Failed to load schedule data: ' + error.message;
        }
    });
}

/**
 * Render list view with event data
 * Creates a responsive table/card layout for displaying schedule events
 */
function renderListView(events, classData) {
    console.log('Rendering list view with', events.length, 'events');

    const listContainer = document.getElementById('classScheduleList');
    if (!listContainer) return;

    // Sort events chronologically
    const sortedEvents = events.sort((a, b) => {
        const dateA = new Date(a.start || a.date);
        const dateB = new Date(b.start || b.date);
        return dateA - dateB;
    });

    // Group events by type for better organization
    const groupedEvents = {
        class_session: [],
        public_holiday: [],
        exception: [],
        stop_date: [],
        restart_date: [],
        stop_period: []
    };

    sortedEvents.forEach(event => {
        const eventType = event.extendedProps?.type || 'class_session';
        if (groupedEvents[eventType]) {
            groupedEvents[eventType].push(event);
        } else {
            groupedEvents.class_session.push(event);
        }
    });

    // Create list view HTML
    let listHTML = '<div class="row g-3">';

    // Render each event group
    Object.keys(groupedEvents).forEach(eventType => {
        const events = groupedEvents[eventType];
        if (events.length === 0) return;

        const groupInfo = getEventGroupInfo(eventType);

        listHTML += `
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0 d-flex align-items-center">
                            <i class="${groupInfo.icon} me-2 text-${groupInfo.color}"></i>
                            ${groupInfo.title}
                            <span class="badge bg-${groupInfo.color} ms-2">${events.length}</span>
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 ps-3">Date & Time</th>
                                        <th class="border-0">Details</th>
                                        <th class="border-0 text-end pe-3">Duration</th>
                                    </tr>
                                </thead>
                                <tbody>`;

        events.forEach(event => {
            listHTML += renderEventRow(event, eventType);
        });

        listHTML += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>`;
    });

    listHTML += '</div>';

    listContainer.innerHTML = listHTML;
}

/**
 * Get event group information for styling and display
 */
function getEventGroupInfo(eventType) {
    const groupInfo = {
        class_session: {
            title: 'Class Sessions',
            icon: 'bi-calendar-event',
            color: 'primary'
        },
        public_holiday: {
            title: 'Public Holidays',
            icon: 'bi-calendar-x',
            color: 'danger'
        },
        exception: {
            title: 'Exception Dates',
            icon: 'bi-exclamation-triangle',
            color: 'warning'
        },
        stop_date: {
            title: 'Stop Dates',
            icon: 'bi-stop-circle',
            color: 'danger'
        },
        restart_date: {
            title: 'Restart Dates',
            icon: 'bi-play-circle',
            color: 'success'
        },
        stop_period: {
            title: 'Stop Period Days',
            icon: 'bi-pause-circle',
            color: 'secondary'
        }
    };

    return groupInfo[eventType] || groupInfo.class_session;
}

/**
 * Render individual event row for list view
 */
function renderEventRow(event, eventType) {
    const startDate = new Date(event.start || event.date);
    const endDate = event.end ? new Date(event.end) : null;

    // Format date and time
    const dateStr = startDate.toLocaleDateString('en-ZA', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });

    const timeStr = event.allDay ? 'All Day' :
        startDate.toLocaleTimeString('en-ZA', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        }) + (endDate ? ' - ' + endDate.toLocaleTimeString('en-ZA', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        }) : '');

    // Get event details
    const title = event.title || 'Untitled Event';
    const subject = event.extendedProps?.classSubject || '';
    const notes = event.extendedProps?.notes || '';
    const reason = event.extendedProps?.reason || '';

    // Calculate duration
    let durationStr = '-';
    if (endDate && !event.allDay) {
        const durationMs = endDate - startDate;
        const hours = Math.floor(durationMs / (1000 * 60 * 60));
        const minutes = Math.floor((durationMs % (1000 * 60 * 60)) / (1000 * 60));
        durationStr = hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;
    }

    // Get event type styling
    const groupInfo = getEventGroupInfo(eventType);

    return `
        <tr>
            <td class="align-middle ps-3">
                <div class="d-flex flex-column">
                    <span class="fw-medium">${dateStr}</span>
                    <small class="text-muted">${timeStr}</small>
                </div>
            </td>
            <td class="align-middle">
                <div class="d-flex flex-column">
                    <span class="fw-medium">${title}</span>
                    ${subject ? `<small class="text-muted">${subject}</small>` : ''}
                    ${notes ? `<small class="text-body-secondary">${notes}</small>` : ''}
                    ${reason ? `<small class="text-warning">Reason: ${reason}</small>` : ''}
                </div>
            </td>
            <td class="align-middle text-end pe-3">
                <span class="badge bg-light text-dark">${durationStr}</span>
            </td>
        </tr>`;
}

/**
 * Initialize list view filtering functionality
 */
function initializeListViewFilters() {
    console.log('Initializing list view filters...');

    const filtersContainer = document.getElementById('listViewFilters');
    const eventTypeFilter = document.getElementById('eventTypeFilter');
    const dateFromFilter = document.getElementById('dateFromFilter');
    const dateToFilter = document.getElementById('dateToFilter');
    const clearFiltersBtn = document.getElementById('clearFilters');

    if (!filtersContainer || !eventTypeFilter || !dateFromFilter || !dateToFilter || !clearFiltersBtn) {
        console.warn('Filter elements not found');
        return;
    }

    // Show filters if we have events
    if (window.currentListViewEvents && window.currentListViewEvents.length > 0) {
        filtersContainer.classList.remove('d-none');
    }

    // Add event listeners
    eventTypeFilter.addEventListener('change', applyListViewFilters);
    dateFromFilter.addEventListener('change', applyListViewFilters);
    dateToFilter.addEventListener('change', applyListViewFilters);
    clearFiltersBtn.addEventListener('click', clearListViewFilters);
}

/**
 * Apply filters to list view
 */
function applyListViewFilters() {
    if (!window.currentListViewEvents) return;

    const eventTypeFilter = document.getElementById('eventTypeFilter').value;
    const dateFromFilter = document.getElementById('dateFromFilter').value;
    const dateToFilter = document.getElementById('dateToFilter').value;

    let filteredEvents = [...window.currentListViewEvents];

    // Filter by event type
    if (eventTypeFilter) {
        filteredEvents = filteredEvents.filter(event => {
            const eventType = event.extendedProps?.type || 'class_session';
            return eventType === eventTypeFilter;
        });
    }

    // Filter by date range
    if (dateFromFilter) {
        const fromDate = new Date(dateFromFilter);
        filteredEvents = filteredEvents.filter(event => {
            const eventDate = new Date(event.start || event.date);
            return eventDate >= fromDate;
        });
    }

    if (dateToFilter) {
        const toDate = new Date(dateToFilter);
        toDate.setHours(23, 59, 59, 999); // Include the entire day
        filteredEvents = filteredEvents.filter(event => {
            const eventDate = new Date(event.start || event.date);
            return eventDate <= toDate;
        });
    }

    console.log(`Filtered ${filteredEvents.length} events from ${window.currentListViewEvents.length} total`);

    // Re-render list view with filtered events
    const listContainer = document.getElementById('classScheduleList');
    const listEmpty = document.getElementById('list-empty');

    if (filteredEvents.length === 0) {
        listContainer.innerHTML = '';
        listEmpty.classList.remove('d-none');

        // Update empty state message for filtered results
        const emptyTitle = listEmpty.querySelector('h5');
        const emptyText = listEmpty.querySelector('p');
        if (emptyTitle && emptyText) {
            emptyTitle.textContent = 'No Events Match Your Filters';
            emptyText.textContent = 'Try adjusting your filter criteria to see more events.';
        }
    } else {
        listEmpty.classList.add('d-none');
        renderListView(filteredEvents, {
            id: <?php echo json_encode($class['class_id'] ?? null); ?>,
            code: <?php echo json_encode($class['class_code'] ?? ''); ?>,
            subject: <?php echo json_encode($class['class_subject'] ?? ''); ?>
        });
    }
}

/**
 * Clear all list view filters
 */
function clearListViewFilters() {
    document.getElementById('eventTypeFilter').value = '';
    document.getElementById('dateFromFilter').value = '';
    document.getElementById('dateToFilter').value = '';

    // Reset empty state message
    const listEmpty = document.getElementById('list-empty');
    const emptyTitle = listEmpty.querySelector('h5');
    const emptyText = listEmpty.querySelector('p');
    if (emptyTitle && emptyText) {
        emptyTitle.textContent = 'No Schedule Events Found';
        emptyText.textContent = 'There are no scheduled events to display for this class.';
    }

    // Re-render with all events
    if (window.currentListViewEvents) {
        renderListView(window.currentListViewEvents, {
            id: <?php echo json_encode($class['class_id'] ?? null); ?>,
            code: <?php echo json_encode($class['class_code'] ?? ''); ?>,
            subject: <?php echo json_encode($class['class_subject'] ?? ''); ?>
        });
    }
}

/**
 * Edit Class Function
 * Redirects to the edit page with the class ID
 */
function editClass(classId) {
    // Check if user has edit permissions
    const canEdit = <?php echo (current_user_can('edit_posts') || current_user_can('manage_options')) ? 'true' : 'false'; ?>;
    if (!canEdit) {
        alert('You do not have permission to edit classes.');
        return;
    }

    <?php
    // 1. Find the page object for "app/new-class" (or just "new-class", depending on where it lives)
    $page = get_page_by_path('app/new-class');
    // If your "new-class" page lives directly under /app/, use exactly that path.
    // If it's a top-level page called "new-class", you can just do get_page_by_path('new-class').

    // 2. Grab its permalink (so WP will automatically use the correct domain/child-theme slug, etc.)
    if ($page) {
        $base_url = get_permalink($page->ID);
    } else {
        // Fallback if page not found:
        $base_url = home_url('/app/new-class/');
    }

    // 3. Append ?mode=update&class_id=… with add_query_arg()
    $edit_url = add_query_arg(
        [
            'mode'     => 'update',
            'class_id' => $class['class_id'],
        ],
        $base_url
    );

    echo "const editUrl = '" . esc_url_raw($edit_url) . "';";
    ?>

    // Redirect to edit page with complete URL
    window.location.href = editUrl;
}

/**
 * Delete Class Function
 * Handles AJAX deletion with proper security checks
 */
function deleteClass(classId) {
    // Check if user is administrator
    const isAdmin = <?php echo current_user_can('manage_options') ? 'true' : 'false'; ?>;
    if (!isAdmin) {
        alert('Only administrators can delete classes.');
        return;
    }

    if (confirm('Are you sure you want to delete this class? This action cannot be undone.')) {
        // Show loading state
        const deleteButton = document.querySelector(`[onclick="deleteClass(${classId})"]`);
        const originalText = deleteButton.innerHTML;
        deleteButton.innerHTML = '<i class="bi bi-spinner-border me-2"></i>Deleting...';
        deleteButton.disabled = true;

        // Make AJAX request to delete class
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'delete_class',
                nonce: '<?php echo wp_create_nonce('wecoza_class_nonce'); ?>',
                class_id: classId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to classes list with success message
                <?php
                // Try to find the all-classes page using WordPress best practices
                $classes_page = get_page_by_path('app/all-classes');
                if ($classes_page) {
                    $classes_url = get_permalink($classes_page->ID);
                    echo "const classesUrl = '" . esc_url_raw($classes_url) . "';";
                } else {
                    // Fallback using home_url for proper domain handling
                    $fallback_url = home_url('/app/all-classes/');
                    echo "const classesUrl = '" . esc_url_raw($fallback_url) . "';";
                }
                ?>

                // Add success parameters to URL for notification (same as all-classes page)
                const successUrl = new URL(classesUrl);
                successUrl.searchParams.set('deleted', 'success');
                successUrl.searchParams.set('class_subject', data.data.class_subject || 'Unknown Class');
                successUrl.searchParams.set('class_code', data.data.class_code || '');
                window.location.href = successUrl.toString();
            } else {
                alert('Error deleting class: ' + (data.data || 'Unknown error'));
                // Restore button state
                deleteButton.innerHTML = originalText;
                deleteButton.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the class. Please try again.');
            // Restore button state
            deleteButton.innerHTML = originalText;
            deleteButton.disabled = false;
        });
    }
}

/**
 * Show Success Banner Function
 * Same implementation as all-classes page for consistency
 */
function showSuccessBanner(message) {
    // Create success banner
    const banner = document.createElement('div');
    banner.className = 'alert alert-subtle-success alert-dismissible fade show position-fixed';
    banner.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 300px;';
    banner.innerHTML = `
        <i class="bi bi-check-circle-fill me-2"></i>
        <strong>Success!</strong> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // Add to page
    document.body.appendChild(banner);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (banner.parentNode) {
            banner.remove();
        }
    }, 5000);
}
</script>