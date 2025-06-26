<!-- Class Details Display Section -->
<?php
// Validate and prepare data for update mode
if (isset($data['class_data']) && $data['class_data']):
    // Ensure we have proper data structure for clients and sites
    $clients = $data['clients'] ?? [];
    $sites = $data['sites'] ?? [];
    $classData = $data['class_data'];

    // Find client name if not already in class data
    if (empty($classData['client_name']) && !empty($classData['client_id'])) {
        foreach ($clients as $client) {
            if ((int)$client['id'] === (int)$classData['client_id']) {
                $classData['client_name'] = $client['name'];
                break;
            }
        }
    }

    // Find site name if not already available
    if (empty($classData['site_name']) && !empty($classData['site_id'])) {
        foreach ($sites as $clientSites) {
            foreach ($clientSites as $site) {
                if ((int)$site['id'] === (int)$classData['site_id']) {
                    $classData['site_name'] = $site['name'];
                    // Also get address if not already in class data
                    if (empty($classData['class_address_line']) && !empty($site['address'])) {
                        $classData['class_address_line'] = $site['address'];
                    }
                    break 2;
                }
            }
        }
    }

    // Update the class data with enriched information
    $data['class_data'] = $classData;
?>
<div class="wecoza-class-details-display mb-4">
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
                                <?php if (!empty($data['class_data']['client_name'])): ?>
                                    <?php echo esc_html($data['class_data']['client_name']); ?>
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
                            <h5 class="fw-bolder text-nowrap"><?php echo esc_html($data['class_data']['class_type'] ?? 'Unknown Type'); ?></h5>
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
                            <h5 class="fw-bolder text-nowrap"><?php echo esc_html($data['class_data']['class_subject'] ?? 'N/A'); ?></h5>
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
                            <h5 class="fw-bolder text-nowrap"><?php echo esc_html($data['class_data']['class_code'] ?? 'N/A'); ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Classes Capture Form -->
<form id="classes-form" class="needs-validation ydcoza-compact-form" novalidate method="POST" enctype="multipart/form-data">
   <!-- Hidden fields for update mode -->
   <input type="hidden" id="class_id" name="class_id" value="<?php echo esc_attr($data['class_id'] ?? $_GET['class_id'] ?? ''); ?>">
   <input type="hidden" id="redirect_url" name="redirect_url" value="<?php echo esc_attr($data['redirect_url'] ?? $_GET['redirect_url'] ?? ''); ?>">
   <input type="hidden" id="nonce" name="nonce" value="<?php echo wp_create_nonce('wecoza_class_nonce'); ?>">

   <!-- ===== Section: Basic Details ===== -->
   <div class="container container-md classes-form ps-0">
      <!-- ===== Section: Basic Details ===== -->
         <!-- UPDATE MODE: Client/site selection with database integration -->
         <!-- Uses the new database-driven client/site data structure with proper integer IDs -->
         <div class="row">
            <!-- Client Name (ID) -->
            <div class="col-md-3 mb-3">
               <div class="mb-3">
                  <label for="client_id" class="form-label">Client Name (ID) <span class="text-danger">*</span></label>
                  <select id="client_id" name="client_id" class="form-select" required>
                     <option value="">Select</option>
                     <?php foreach ($data['clients'] as $client): ?>
                        <option value="<?php echo esc_attr($client['id']); ?>" <?php echo (isset($data['class_data']['client_id']) && (int)$data['class_data']['client_id'] === (int)$client['id']) ? 'selected' : ''; ?>><?php echo esc_html($client['name']); ?></option>
                     <?php endforeach; ?>
                  </select>
                  <div class="invalid-feedback">Please select a client.</div>
                  <div class="valid-feedback">Looks good!</div>
               </div>
            </div>

            <!-- Class/Site Name -->
            <div class="col-md-3 mb-3">
               <div class="mb-3">
                  <label for="site_id" class="form-label">Class/Site Name <span class="text-danger">*</span></label>
                  <select id="site_id" name="site_id" class="form-select" required>
                     <option value="">Select Site</option>
                     <?php foreach ($data['clients'] as $client): ?>
                        <optgroup label="<?php echo esc_attr($client['name']); ?>">
                           <?php if (isset($data['sites'][$client['id']])): ?>
                              <?php foreach ($data['sites'][$client['id']] as $site): ?>
                                 <option value="<?php echo esc_attr($site['id']); ?>"
                                    <?php echo (isset($data['class_data']['site_id']) && (int)$data['class_data']['site_id'] === (int)$site['id']) ? 'selected' : ''; ?>
                                    data-address="<?php echo esc_attr($site['address'] ?? ''); ?>">
                                    <?php echo esc_html($site['name']); ?>
                                 </option>
                              <?php endforeach; ?>
                           <?php endif; ?>
                        </optgroup>
                     <?php endforeach; ?>
                  </select>
                  <div class="invalid-feedback">Please select a class/site name.</div>
                  <div class="valid-feedback">Looks good!</div>
               </div>
            </div>

            <!-- Single Address Field -->
            <div class="col-md-6 mb-3" id="address-wrapper" style="<?php echo !empty($data['class_data']['class_address_line']) ? 'display:block;' : 'display:none;'; ?>">
               <div class="mb-3">
                  <label for="site_address" class="form-label">Address</label>
                  <input
                     type="text"
                     id="site_address"
                     name="site_address"
                     class="form-control"
                     placeholder="Street, Suburb, Town, Postal Code"
                     value="<?php
                        // Try to get address from class data first, then from site data if available
                        $address = $data['class_data']['class_address_line'] ?? '';

                        // If no address in class data, try to get it from site data
                        if (empty($address) && !empty($data['class_data']['site_id'])) {
                            $siteId = (int)$data['class_data']['site_id'];
                            // Look through sites data to find the address
                            foreach ($data['sites'] as $clientSites) {
                                foreach ($clientSites as $site) {
                                    if ((int)$site['id'] === $siteId && !empty($site['address'])) {
                                        $address = $site['address'];
                                        break 2;
                                    }
                                }
                            }
                        }

                        echo esc_attr($address);
                     ?>"
                     readonly
                     />
               </div>
            </div>
         </div>

      <?php echo section_divider(); ?>

      <!-- ===== Section: Scheduling & Class Info ===== -->
      <div class="row mt-3">
         <!-- Class Type (Main Category) -->
         <div class="col-md-4 mb-3">
            <div class="mb-3">
               <label for="class_type" class="form-label">Class Type <span class="text-danger">*</span></label>
               <select id="class_type" name="class_type" class="form-select" required>
                  <option value="">Select</option>
                  <?php foreach ($data['class_types'] as $class_type): ?>
                     <option value="<?php echo esc_attr($class_type['id']); ?>" <?php echo (isset($data['class_data']['class_type']) && $data['class_data']['class_type'] == $class_type['id']) ? 'selected' : ''; ?>><?php echo esc_html($class_type['name']); ?></option>
                  <?php endforeach; ?>
               </select>
               <div class="invalid-feedback">Please select the class type.</div>
               <div class="valid-feedback">Looks good!</div>
            </div>
         </div>

         <!-- Class Subject (Specific Subject/Level/Module) -->
         <div class="col-md-4 mb-3">
            <div class="mb-3">
               <label for="class_subject" class="form-label">Class Subject <span class="text-danger">*</span></label>
               <select id="class_subject" name="class_subject" class="form-select" required>
                  <option value="">Select Class Type First</option>
                  <!-- Will be populated dynamically based on class type, but pre-populate current value -->
                  <?php if (isset($data['class_data']['class_subject']) && !empty($data['class_data']['class_subject'])): ?>
                     <option value="<?php echo esc_attr($data['class_data']['class_subject']); ?>" selected><?php echo esc_html($data['class_data']['class_subject']); ?></option>
                  <?php endif; ?>
               </select>
               <div class="invalid-feedback">Please select the class subject.</div>
               <div class="valid-feedback">Looks good!</div>
            </div>
         </div>

         <!-- Class Duration (Auto-calculated) -->
         <div class="col-md-4 mb-3">
            <div class="mb-3">
               <label for="class_duration" class="form-label">Duration (Hours)</label>
               <input type="number" id="class_duration" name="class_duration" class="form-control" placeholder="Duration" value="<?php echo esc_attr($data['class_data']['class_duration'] ?? ''); ?>" readonly>
               <div class="form-text">Automatically calculated based on class type and subject.</div>
            </div>
         </div>
      </div>

      <div class="row">
         <!-- Class Code (Auto-generated) -->
         <div class="col-md-4 mb-3">
            <div class="mb-3">
               <label for="class_code" class="form-label">Class Code</label>
               <input type="text" id="class_code" name="class_code" class="form-control" placeholder="Class Code" value="<?php echo esc_attr($data['class_data']['class_code'] ?? ''); ?>" readonly>
               <div class="form-text">Auto generated [ClientID]-[ClassType]-[SubjectID]-[YYYY]-[MM]-[DD]-[HH]-[MM] </div>
            </div>
         </div>

         <!-- Class Original Start Date -->
         <div class="col-md-4 mb-3">
            <div class="mb-3">
               <label for="class_start_date" class="form-label">Class Original Start Date <span class="text-danger">*</span></label>
               <input type="date" id="class_start_date" name="class_start_date" class="form-control" placeholder="YYYY-MM-DD" value="<?php echo esc_attr($data['class_data']['original_start_date'] ?? ''); ?>" required>
               <div class="invalid-feedback">Please select the start date.</div>
               <div class="valid-feedback">Looks good!</div>
            </div>
         </div>
      </div>

      <!-- Class Schedule Form Section -->
      <div class="mb-4 mt-3">
         <h5 class="mb-3">Class Schedule</h5>
            <!-- UPDATE MODE: Display existing schedule info -->
            <p class="text-muted small mb-3">Update the recurring schedule for this class.</p>

         <?php
         // Extract schedule data for pre-population
         $scheduleData = $data['class_data']['schedule_data'] ?? [];
         $schedulePattern = $scheduleData['pattern'] ?? '';
         $scheduleDays = $scheduleData['days'] ?? [];
         $scheduleStartTime = $scheduleData['start_time'] ?? '';
         $scheduleEndTime = $scheduleData['end_time'] ?? '';
         $scheduleStartDate = $scheduleData['start_date'] ?? '';
         $scheduleEndDate = $scheduleData['end_date'] ?? '';
         $holidayOverrides = $scheduleData['holiday_overrides'] ?? [];

         // Convert holiday overrides to JSON string for the hidden field
         $holidayOverridesJson = !empty($holidayOverrides) ? json_encode($holidayOverrides) : '';
         ?>

         <!-- Schedule Pattern Selection -->
         <div class="row mb-3">
            <div class="col-md-4 mb-3">
               <div class="mb-3">
                  <label for="schedule_pattern" class="form-label">Schedule Pattern <span class="text-danger">*</span></label>
                  <select id="schedule_pattern" name="schedule_pattern" class="form-select" required>
                     <option value="">Select</option>
                     <option value="weekly" <?php echo ($schedulePattern == 'weekly') ? 'selected' : ''; ?>>Weekly (Every Week)</option>
                     <option value="biweekly" <?php echo ($schedulePattern == 'biweekly') ? 'selected' : ''; ?>>Bi-Weekly (Every Two Weeks)</option>
                     <option value="monthly" <?php echo ($schedulePattern == 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                     <option value="custom" <?php echo ($schedulePattern == 'custom') ? 'selected' : ''; ?>>Custom</option>
                  </select>
                  <div class="invalid-feedback">Please select a schedule pattern.</div>
                  <div class="valid-feedback">Looks good!</div>
               </div>
            </div>

            <!-- Day Selection (for weekly/biweekly) -->
            <div class="col-md-12 mb-3" id="day-selection-container">
               <label class="form-label">Days of Week <span class="text-danger">*</span></label>
               <div class="days-checkbox-group">
                  <?php
                  $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                  foreach ($weekDays as $index => $day):
                     $isChecked = is_array($scheduleDays) && in_array($day, $scheduleDays);
                     $isFirst = ($index === 0);
                  ?>
                  <div class="form-check form-check-inline">
                     <input class="form-check-input schedule-day-checkbox" type="checkbox" id="schedule_day_<?php echo strtolower($day); ?>" name="schedule_days[]" value="<?php echo $day; ?>" <?php echo $isChecked ? 'checked' : ''; ?> <?php echo $isFirst ? 'required' : ''; ?>>
                     <label class="form-check-label" for="schedule_day_<?php echo strtolower($day); ?>"><?php echo $day; ?></label>
                  </div>
                  <?php endforeach; ?>
               </div>
               <div class="mt-2">
                  <button type="button" class="btn btn-sm btn-outline-primary" id="select-all-days">Select All</button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-all-days">Clear All</button>
               </div>
               <div class="invalid-feedback d-none">Please select at least one day.</div>
               <div class="valid-feedback d-none">Looks good!</div>
            </div>

            <!-- Day of Month (for monthly) -->
            <div class="col-md-4 mb-3 d-none" id="day-of-month-container">
               <div class="mb-3">
                  <label for="schedule_day_of_month" class="form-label">Day of Month <span class="text-danger">*</span></label>
                  <select id="schedule_day_of_month" name="schedule_day_of_month" class="form-select">
                     <option value="">Select</option>
                     <?php for ($i = 1; $i <= 31; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                     <?php endfor; ?>
                     <option value="last">Last Day</option>
                  </select>
                  <div class="invalid-feedback">Please select a day of the month.</div>
                  <div class="valid-feedback">Looks good!</div>
               </div>
            </div>
         </div>

         <!-- Time Selection -->
         <!-- Single Time Controls (shown when no days selected) -->
         <div id="single-time-controls" class="row mb-3 d-none">
            <!-- This section is now hidden and will be replaced by per-day controls when days are selected -->
         </div>

         <!-- Date Range -->
         <div class="row mb-3">
            <div class="col-md-4 mb-3">
               <div class="mb-3">
                  <label for="schedule_start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                  <input type="date" id="schedule_start_date" name="schedule_start_date" class="form-control" placeholder="YYYY-MM-DD" value="<?php echo esc_attr($scheduleStartDate); ?>" required>
                  <div class="invalid-feedback">Please select a start date.</div>
                  <div class="valid-feedback">Looks good!</div>
               </div>
            </div>

            <div class="col-md-4 mb-3">
               <div class="mb-3">
                  <label for="schedule_end_date" class="form-label">End Date</label>
                  <input type="date" id="schedule_end_date" name="schedule_end_date" class="form-control readonly-field" placeholder="YYYY-MM-DD" value="<?php echo esc_attr($scheduleEndDate); ?>" readonly>
                  <small class="text-muted">Automatically calculated based on class duration</small>
               </div>
            </div>

            <div class="col-md-4 d-none">
               <div class="mb-3">
                  <label for="schedule_total_hours" class="form-label">Total Hours</label>
                  <input type="text" id="schedule_total_hours" name="schedule_total_hours" class="form-control readonly-field" placeholder="Total Hours" readonly>
                  <small class="text-muted">Based on class type</small>
               </div>
            </div>
         </div>

         <!-- Exception Dates -->
         <div class="mb-4">
            <h6 class="mb-2">Exception Dates</h6>
            <p class="text-muted small mb-3">Add dates when classes will not occur (e.g. client closed).</p>

            <!-- Container for all exception date rows -->
            <div id="exception-dates-container"></div>

            <!-- Hidden Template Row (initially d-none) -->
            <div class="row exception-date-row align-items-center d-none" id="exception-date-row-template">
               <!-- Exception Date -->
               <div class="col-md-4 mb-2">
                  <div class="mb-3">
                     <label class="form-label">Date</label>
                     <input type="date" name="exception_dates[]" class="form-control" placeholder="YYYY-MM-DD">
                     <div class="invalid-feedback">Please select a valid date.</div>
                     <div class="valid-feedback">Looks good!</div>
                  </div>
               </div>

               <!-- Reason -->
               <div class="col-md-6 mb-2">
                  <div class="mb-3">
                     <label class="form-label">Reason</label>
                     <select name="exception_reasons[]" class="form-select">
                        <option value="">Select</option>
                        <option value="Client Cancelled">Client Cancelled</option>
                        <option value="Agent Absent">Agent Absent</option>
                        <option value="Public Holiday">Public Holiday</option>
                        <option value="Other">Other</option>
                     </select>
                     <div class="invalid-feedback">Please select a reason.</div>
                     <div class="valid-feedback">Looks good!</div>
                  </div>
               </div>

               <!-- Remove Button -->
               <div class="col-md-2 mb-2">
                  <div class="d-flex h-100 align-items-end">
                     <button type="button" class="btn btn-outline-danger btn-sm remove-exception-btn form-control date-remove-btn">Remove</button>
                  </div>
               </div>
            </div>

            <!-- Add Exception Button -->
            <button type="button" class="btn btn-outline-primary btn-sm" id="add-exception-date-btn">
            + Add Exception Date
            </button>
         </div>

         <!-- Public Holidays Section -->
         <div class="mb-4">
            <h6 class="mb-2">Public Holidays in Schedule</h6>
            <p class="text-muted small mb-3">By default, classes are not scheduled on public holidays. The system will only show holidays that conflict with your class schedule (when a holiday falls on a scheduled class day). You can override specific holidays to include them in the schedule.</p>

            <!-- No holidays message -->
            <div id="no-holidays-message" class="bd-callout bd-callout-info">
               No public holidays that conflict with your class schedule were found. Holidays are only shown when they fall on a scheduled class day.
            </div>

            <!-- Holidays table container -->
            <div id="holidays-table-container" class="card-body card-body card px-5 d-none" >
               <div class="table-responsive">
                  <table class="table table-sm fs-9 mb-0 table-hover">
                     <thead>
                        <tr>
                           <th style="width: 50px;">
                              <div class="form-check">&nbsp;</div>
                           </th>
                           <th>Date</th>
                           <th>Holiday</th>
                           <th>Override</th>
                        </tr>
                     </thead>
                     <tbody id="holidays-list">
                        <!-- Holidays will be populated here dynamically -->
                     </tbody>
                  </table>
               </div>

               <div class="d-flex justify-content-between mt-2">
                  <div>
                     <button type="button" class="btn btn-outline-secondary btn-sm" id="skip-all-holidays-btn">Skip All Holidays</button>
                     <button type="button" class="btn btn-outline-primary btn-sm" id="override-all-holidays-btn">Override All Holidays</button>
                  </div>
               </div>
            </div>
         </div>

         <!-- Holiday Row Template (for JavaScript) -->
         <template id="holiday-row-template">
            <tr class="holiday-row">
               <td>
                  <div class="form-check">
                     <input class="form-check-input holiday-override-checkbox" type="checkbox" id="override-holiday-{id}" data-date="{date}">
                     <label class="form-check-label" for="override-holiday-{id}"></label>
                  </div>
               </td>
               <td class="holiday-date">{formatted_date}</td>
               <td class="holiday-name">{name}</td>
               <td class="holiday-status">
                  <span class="badge bg-danger holiday-skipped">Skipped</span>
                  <span class="badge bg-info holiday-overridden d-none">Included</span>
               </td>
            </tr>
         </template>

         <!-- Hidden input to store holiday override data -->
         <input type="hidden" id="holiday_overrides" name="schedule_data[holiday_overrides]" value="<?php echo esc_attr($holidayOverridesJson); ?>">

         <!-- Hidden inputs to store schedule data in the format expected by the backend -->
         <div id="schedule-data-container">
            <!-- These will be populated dynamically via JavaScript -->
         </div>

         <!-- Schedule Statistics Section -->
         <!-- Schedule Statistics Toggle Button -->
         <div class="mt-3 mb-3">
            <button type="button" class="btn btn-outline-primary btn-sm" id="toggle-statistics-btn">
               <i class="bi bi-bar-chart-line me-1"></i> View Schedule Statistics
            </button>
            <div class="clearfix"></div>
            <small class="text-muted">Click to view detailed statistics about the training schedule</small>
         </div>

         <!-- Schedule Statistics Section (hidden by default) -->
         <div class="card shadow-none border mb-3 d-none" id="schedule-statistics-section" data-component-card="data-component-card">
         <div class="card-header p-3 border-bottom bg-body">
            <h4 class="text-body mb-0" data-anchor="schedule-statistics" id="schedule-statistics">
               Schedule Statistics
               <a class="anchorjs-link" aria-label="Anchor" data-anchorjs-icon="#" href="#schedule-statistics" style="margin-left:0.1875em; padding:0 0.1875em;"></a>
            </h4>
         </div>
         <div class="card-body p-4">
            <div class="table-responsive scrollbar mb-3">
               <table class="table table-sm fs-9 mb-0 overflow-hidden">
               <thead class="text-body">
                  <tr>
                     <th class="sort pe-1 align-middle white-space-nowrap">Category</th>
                     <th class="sort pe-1 align-middle white-space-nowrap">Metric</th>
                     <th class="sort pe-1 align-middle white-space-nowrap">Value</th>
                  </tr>
               </thead>
               <tbody id="schedule-statistics-table">
                  <!-- Training Duration Statistics -->
                  <tr class="ydcoza-table-subheader">
                     <th colspan="3">Training Duration</th>
                  </tr>
                  <tr>
                     <td rowspan="3" class="align-middle">Time Period</td>
                     <td>Total Calendar Days</td>
                     <td id="stat-total-days">-</td>
                  </tr>
                  <tr>
                     <td>Total Weeks</td>
                     <td id="stat-total-weeks">-</td>
                  </tr>
                  <tr>
                     <td>Total Months</td>
                     <td id="stat-total-months">-</td>
                  </tr>

                  <!-- Class Session Statistics -->
                  <tr class="ydcoza-table-subheader">
                     <th colspan="3">Class Sessions</th>
                  </tr>
                  <tr>
                     <td rowspan="3" class="align-middle">Sessions</td>
                     <td>Total Scheduled Classes</td>
                     <td id="stat-total-classes">-</td>
                  </tr>
                  <tr>
                     <td>Total Training Hours</td>
                     <td id="stat-total-hours">-</td>
                  </tr>
                  <tr>
                     <td>Average Hours per Month</td>
                     <td id="stat-avg-hours-month">-</td>
                  </tr>

                  <!-- Attendance Impact Statistics -->
                  <tr class="ydcoza-table-subheader">
                     <th colspan="3">Attendance Impact</th>
                  </tr>
                  <tr>
                     <td rowspan="3" class="align-middle">Adjustments</td>
                     <td>Holidays Affecting Classes</td>
                     <td id="stat-holidays-affecting">-</td>
                  </tr>
                  <tr>
                     <td>Exception Dates</td>
                     <td id="stat-exception-dates">-</td>
                  </tr>
                  <tr>
                     <td>Actual Training Days</td>
                     <td id="stat-actual-days">-</td>
                  </tr>
               </tbody>
               </table>
            </div>
         </div>
         </div>

      </div>

      <!-- Class Date History Section -->
      <div class="mb-4 mt-3">
         <h5 class="mb-3">Class Date History</h5>
         <p class="text-muted small mb-3">Add stop and restart dates for this class. A class can have multiple stop and restart dates.</p>

         <!-- Container for all date history rows -->
         <div id="date-history-container"></div>

         <!-- Hidden Template Row (initially d-none) -->
         <div class="row date-history-row d-none" id="date-history-row-template">
            <!-- Stop Date -->
            <div class="col-md-5 mb-2">
               <label class="form-label">Stop Date</label>
               <input type="date" name="stop_dates[]" class="form-control form-control-sm">
               <div class="invalid-feedback">Please select a valid date.</div>
               <div class="valid-feedback">Looks good!</div>
            </div>

            <!-- Restart Date -->
            <div class="col-md-5 mb-2">
               <label class="form-label">Restart Date</label>
               <input type="date" name="restart_dates[]" class="form-control form-control-sm">
               <div class="invalid-feedback">Please select a valid date.</div>
               <div class="valid-feedback">Looks good!</div>
            </div>

            <!-- Remove Button -->
            <div class="col-md-2 mb-2">
               <label class="form-label invisible">&nbsp;</label>
               <button type="button" class="btn btn-outline-danger btn-sm remove-date-row-btn form-control date-remove-btn">Remove</button>
            </div>
         </div>

         <!-- Add Row Button -->
         <button type="button" class="btn btn-outline-primary btn-sm" id="add-date-history-btn">
         + Add Stop/Restart Dates
         </button>
      </div>

      <?php echo section_divider(); ?>

      <!-- ===== Section: Funding & Exam Details ===== -->
      <?php echo section_header('Funding & Exam Details'); ?>
      <div class="row">
         <!-- SETA Funded -->
         <div class="col-md-3 mb-3">
            <div class="mb-3">
               <label for="seta_funded" class="form-label">SETA Funded? <span class="text-danger">*</span></label>
               <select id="seta_funded" name="seta_funded" class="form-select" required>
                  <option value="">Select</option>
                  <?php foreach ($data['yes_no_options'] as $option): ?>
                     <option value="<?php echo $option['id']; ?>" <?php echo (isset($data['class_data']['seta_funded']) && $data['class_data']['seta_funded'] == $option['id']) ? 'selected' : ''; ?>><?php echo $option['name']; ?></option>
                  <?php endforeach; ?>
               </select>
               <div class="invalid-feedback">Please select if the class is SETA funded.</div>
               <div class="valid-feedback">Looks good!</div>
            </div>
         </div>

         <!-- SETA (conditionally displayed) -->
         <?php
         $setaFunded = $data['class_data']['seta_funded'] ?? '';
         $showSeta = ($setaFunded == 'Yes' || $setaFunded == '1');
         ?>
         <div class="col-md-3 mb-3" id="seta_container" style="display: <?php echo $showSeta ? 'block' : 'none'; ?>;">
            <div class="mb-3">
               <label for="seta_id" class="form-label">SETA <span class="text-danger">*</span></label>
               <select id="seta_id" name="seta_id" class="form-select">
                  <option value="">Select</option>
                  <?php foreach ($data['setas'] as $seta): ?>
                     <option value="<?php echo $seta['id']; ?>" <?php echo (isset($data['class_data']['seta']) && $data['class_data']['seta'] == $seta['id']) ? 'selected' : ''; ?>><?php echo $seta['name']; ?></option>
                  <?php endforeach; ?>
               </select>
               <div class="invalid-feedback">Please select a SETA.</div>
               <div class="valid-feedback">Looks good!</div>
            </div>
         </div>

         <!-- Exam Class -->
         <div class="col-md-3 mb-3">
            <div class="mb-3">
               <label for="exam_class" class="form-label">Exam Class <span class="text-danger">*</span></label>
               <select id="exam_class" name="exam_class" class="form-select" required>
                  <option value="">Select</option>
                  <?php foreach ($data['yes_no_options'] as $option): ?>
                     <option value="<?php echo $option['id']; ?>" <?php echo (isset($data['class_data']['exam_class']) && $data['class_data']['exam_class'] == $option['id']) ? 'selected' : ''; ?>><?php echo $option['name']; ?></option>
                  <?php endforeach; ?>
               </select>
               <div class="invalid-feedback">Please select if this is an exam class.</div>
               <div class="valid-feedback">Looks good!</div>
            </div>
         </div>

         <!-- Exam Type (conditionally displayed) -->
         <?php
         $examClass = $data['class_data']['exam_class'] ?? '';
         $showExamType = ($examClass == 'Yes' || $examClass == '1');
         ?>
         <div class="col-md-3 mb-3">
            <div id="exam_type_container" style="display: <?php echo $showExamType ? 'block' : 'none'; ?>;">
               <div class="mb-3">
                  <label for="exam_type" class="form-label">Exam Type</label>
                  <input type="text" id="exam_type" name="exam_type" class="form-control" placeholder="Enter exam type" value="<?php echo esc_attr($data['class_data']['exam_type'] ?? ''); ?>">
                  <div class="invalid-feedback">Please provide the exam type.</div>
                  <div class="valid-feedback">Looks good!</div>
               </div>
            </div>
         </div>
      </div>

      <!-- Class Learners Section -->
      <?php echo section_header('Class Learners <span class="text-danger">*</span>', 'Select learners for this class and manage their status.'); ?>

      <div class="row mb-4">
         <!-- Learner Selection -->
         <div class="col-md-4">
            <!-- For multi-select with floating labels, we need a custom approach -->
            <div class="mb-3">
               <label for="add_learner" class="form-label">Select Learners</label>
               <select id="add_learner" name="add_learner[]" class="form-select" aria-label="Learner selection" multiple>
                  <?php foreach ($data['learners'] as $learner): ?>
                     <option value="<?php echo $learner['id']; ?>"><?php echo $learner['name']; ?></option>
                  <?php endforeach; ?>
               </select>
               <div class="form-text">Select multiple learners to add to this class. Hold Ctrl/Cmd to select multiple.</div>
               <div class="invalid-feedback">Please select at least one learner.</div>
               <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add-selected-learners-btn">
                  Add Selected Learners
               </button>
            </div>
         </div>

         <!-- Learners Table -->
         <div class="col-md-8">
            <div class="mb-3">
               <div class="form-label mb-2">Class Learners</div>
               <div id="class-learners-container" class="card-body card px-5">
                  <div class="bd-callout bd-callout-info" id="no-learners-message">
                     No learners added to this class yet. At least one learner is required. Select learners from the list and click "Add Selected Learners".
                  </div>
                  <table class="table table-sm fs-9 d-none" id="class-learners-table">
                     <thead>
                        <tr>
                           <th>Learner</th>
                           <th>Level/Module</th>
                           <th>Status</th>
                           <th>Actions</th>
                        </tr>
                     </thead>
                     <tbody id="class-learners-tbody">
                        <!-- Learner rows will be added here dynamically -->
                     </tbody>
                  </table>
               </div>
               <!-- Hidden field to store learner data -->
               <input type="hidden" id="class_learners_data" name="class_learners_data" value="">
            </div>
         </div>
      </div>

      <!-- Exam Learners (conditionally displayed) -->
      <div class="row mt-5" id="exam_learners_container" style="display: none;">
      <?php echo section_divider(); ?>
         <div class="col-12">
            <h5 class="mb-3">Select Learners Taking Exams</h5>
            <p class="text-muted small mb-3">Not all learners in an exam class necessarily take exams. Select which learners will take exams.</p>

            <div class="row mb-4">
               <!-- Exam Learner Selection -->
               <div class="col-md-4">
                  <!-- For multi-select with floating labels, we need a custom approach -->
                  <div class="mb-3">
                     <label for="add_learner" class="form-label">Select Learners</label>
                     <select id="exam_learner_select" name="exam_learner_select[]" class="form-select" aria-label="Exam learner selection" multiple>
                        <!-- Will be populated dynamically with class learners -->
                     </select>
                     <div class="form-text">Select learners who will take exams in this class. Hold Ctrl/Cmd to select multiple.</div>
                     <div class="invalid-feedback">Please select at least one learner for exams.</div>
                     <div class="valid-feedback">Looks good!</div>
                     <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add-selected-exam-learners-btn">
                        Add Selected Exam Learners
                     </button>
                  </div>
               </div>

               <!-- Exam Learners List -->
               <div class="col-md-8">
                  <div class="mb-3">
                     <div class="form-label mb-2">Learners Taking Exams</div>
                     <div id="exam-learners-list" class="card-body card px-5">
                        <div class="alert alert-info" id="no-exam-learners-message">
                           No exam learners added yet. Select learners from the list and click "Add Selected Exam Learners".
                        </div>
                        <table class="table table-sm fs-9 d-none" id="exam-learners-table">
                           <thead>
                              <tr>
                                 <th>Learner</th>
                                 <th>Actions</th>
                              </tr>
                           </thead>
                           <tbody id="exam-learners-tbody">
                              <!-- Exam learner rows will be added here dynamically -->
                           </tbody>
                        </table>
                     </div>
                  </div>
               </div>
            </div>

            <!-- Hidden field to store exam learners data -->
            <input type="hidden" id="exam_learners" name="exam_learners" value="">
         </div>
      </div>

      <?php echo section_divider(); ?>
      <?php echo section_header('Class Notes & QA', 'Add operational notes and quality assurance information for this class.'); ?>

      <!-- Class Notes & QA Information -->
      <div class="row">
         <!-- Class Notes (Multi-select) -->
         <div class="col-md-6">
            <!-- For multi-select with floating labels, we need a custom approach -->
            <div class="mb-3">
            <label for="class_notes" class="form-label">Class Notes</label>
            <select
               id="class_notes"
               name="class_notes[]"
               class="form-select"
               size="5"
               multiple
               aria-label="Class notes selection"
            >
               <?php
               $selectedNotes = $data['class_data']['class_notes_data'] ?? [];
               foreach ($data['class_notes_options'] as $option):
                  $isSelected = is_array($selectedNotes) && in_array($option['id'], $selectedNotes);
               ?>
                  <option value="<?= $option['id'] ?>" <?php echo $isSelected ? 'selected' : ''; ?>><?= $option['name'] ?></option>
               <?php endforeach; ?>
            </select>
            <div class="form-text">
               Select multiple operational notes that apply to this class. Hold Ctrl/Cmd to select.
            </div>
            <div class="invalid-feedback">Please select at least one note.</div>
            <div class="valid-feedback">Looks good!</div>
            </div>


         </div>
      </div>

      <!-- QA Visit Dates and Reports Section -->
      <div class="mt-4">
         <h6 class="mb-3">QA Visit Dates & Reports</h6>
         <p class="text-muted small mb-3">Add QA visit dates and upload corresponding reports for each visit.</p>

         <!-- Container for all QA visit date rows -->
         <div id="qa-visits-container"></div>

         <!-- Hidden Template Row (initially d-none) -->
         <div class="row qa-visit-row align-items-center d-none" id="qa-visit-row-template">
            <!-- Visit Date -->
            <div class="col-md-4 mb-2">
               <div class="mb-3">
                  <label class="form-label">Visit Date</label>
                  <input type="date" name="qa_visit_dates[]" class="form-control" placeholder="YYYY-MM-DD">
                  <div class="invalid-feedback">Please select a valid date.</div>
                  <div class="valid-feedback">Looks good!</div>
               </div>
            </div>

            <!-- Report Upload -->
            <div class="col-md-6 mb-2">
               <div class="mb-3 ydcoza-upload">
                  <label class="form-label">QA Report</label>
                  <input type="file" name="qa_reports[]" class="form-control" accept="application/pdf">
                  <div class="invalid-feedback">Please upload a report for this visit.</div>
                  <div class="valid-feedback">Looks good!</div>
               </div>
            </div>

            <!-- Remove Button -->
            <div class="col-md-2 mb-2">
               <div class="d-flex h-100 align-items-end">
                  <button type="button" class="btn btn-outline-danger btn-sm remove-qa-visit-btn form-control date-remove-btn">Remove</button>
               </div>
            </div>
         </div>

         <!-- Add Row Button -->
         <button type="button" class="btn btn-outline-primary btn-sm" id="add-qa-visit-btn">
         + Add QA Visit Date
         </button>
      </div>

      <?php echo section_divider(); ?>

      <!-- ===== Section: Assignments & Dates ===== -->
      <?php echo section_header('Assignments & Dates', 'Assign staff to this class and track agent changes.'); ?>

      <!-- Class Agents Section -->
      <div class="mb-4">
         <h5 class="mb-3">Class Agents</h5>
         <p class="text-muted small mb-3">Assign the primary class agent. If the agent changes during the class, the history will be tracked.</p>

         <!-- Initial Class Agent -->
         <div class="row mb-3">
            <div class="col-md-5 mb-3">
               <div class="mb-3">
                  <label for="initial_class_agent" class="form-label">Initial Class Agent <span class="text-danger">*</span></label>
                  <select id="initial_class_agent" name="initial_class_agent" class="form-select" required>
                     <option value="">Select</option>
                     <?php foreach ($data['agents'] as $agent): ?>
                        <option value="<?php echo $agent['id']; ?>" <?php echo (isset($data['class_data']['class_agent']) && $data['class_data']['class_agent'] == $agent['id']) ? 'selected' : ''; ?>><?php echo $agent['name']; ?></option>
                     <?php endforeach; ?>
                  </select>
                  <div class="invalid-feedback">Please select the initial class agent.</div>
                  <div class="valid-feedback">Looks good!</div>
               </div>
            </div>
            <div class="col-md-5 mb-3">
               <div class="mb-3">
                  <label for="initial_agent_start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                  <input type="date" id="initial_agent_start_date" name="initial_agent_start_date" class="form-control" placeholder="YYYY-MM-DD" value="<?php echo esc_attr($data['class_data']['initial_agent_start_date'] ?? ''); ?>" required>
                  <div class="invalid-feedback">Please select the start date.</div>
                  <div class="valid-feedback">Looks good!</div>
               </div>
            </div>
         </div>

         <!-- Agent Replacements -->
         <h6 class="mb-3">Agent Replacements</h6>
         <p class="text-muted small mb-3">If the class agent changes, add the replacement agent and takeover date here.</p>

         <!-- Container for all agent replacement rows -->
         <div id="agent-replacements-container"></div>

         <!-- Hidden Template Row (initially d-none) -->
         <div class="row agent-replacement-row d-none" id="agent-replacement-row-template">
            <!-- Replacement Agent -->
            <div class="col-md-5 mb-2">
               <div class="mb-3">
                  <label class="form-label">Replacement Agent</label>
                  <select name="replacement_agent_ids[]" class="form-select replacement-agent-select">
                     <option value="">Select</option>
                     <?php foreach ($data['agents'] as $agent): ?>
                        <option value="<?php echo $agent['id']; ?>"><?php echo $agent['name']; ?></option>
                     <?php endforeach; ?>
                  </select>
                  <div class="invalid-feedback">Please select a replacement agent.</div>
                  <div class="valid-feedback">Looks good!</div>
               </div>
            </div>

            <!-- Takeover Date -->
            <div class="col-md-5 mb-2">
               <div class="mb-3">
                  <label class="form-label">Takeover Date</label>
                  <input type="date" name="replacement_agent_dates[]" class="form-control" placeholder="YYYY-MM-DD">
                  <div class="invalid-feedback">Please select a valid takeover date.</div>
                  <div class="valid-feedback">Looks good!</div>
               </div>
            </div>

            <!-- Remove Button -->
            <div class="col-md-2 mb-2">
               <div class="d-flex h-100 align-items-end">
                  <button type="button" class="btn btn-outline-danger btn-sm remove-agent-replacement-btn form-control date-remove-btn">Remove</button>
               </div>
            </div>
         </div>

         <!-- Add Row Button -->
         <button type="button" class="btn btn-outline-primary btn-sm" id="add-agent-replacement-btn">
         + Add Agent Replacement
         </button>
      </div>

      <!-- Project Supervisor and Delivery Date -->
      <div class="row mb-4">
         <div class="col-md-5 mb-3">
            <div class="mb-3">
               <label for="project_supervisor" class="form-label">Project Supervisor <span class="text-danger">*</span></label>
               <select id="project_supervisor" name="project_supervisor" class="form-select" required>
                  <option value="">Select</option>
                  <?php foreach ($data['supervisors'] as $supervisor): ?>
                     <option value="<?php echo $supervisor['id']; ?>" <?php echo (isset($data['class_data']['project_supervisor_id']) && $data['class_data']['project_supervisor_id'] == $supervisor['id']) ? 'selected' : ''; ?>><?php echo $supervisor['name']; ?></option>
                  <?php endforeach; ?>
               </select>
               <div class="invalid-feedback">Please select a project supervisor.</div>
               <div class="valid-feedback">Looks good!</div>
            </div>
         </div>

         <div class="col-md-5 mb-3">
            <div class="mb-3">
               <label for="delivery_date" class="form-label">Delivery Date <span class="text-danger">*</span></label>
               <input type="date" id="delivery_date" name="delivery_date" class="form-control" placeholder="YYYY-MM-DD" value="<?php echo esc_attr($data['class_data']['delivery_date'] ?? ''); ?>" required>
               <div class="invalid-feedback">Please select the delivery date.</div>
               <div class="valid-feedback">Looks good!</div>
            </div>
         </div>
      </div>

      <!-- Backup Agents Section -->
      <div class="mt-4 mb-4">
         <h5 class="mb-3">Backup Agents</h5>
         <p class="text-muted small mb-3">Add backup agents with specific dates when they will be available.</p>

         <!-- Container for all backup agent rows -->
         <div id="backup-agents-container"></div>

         <!-- Hidden Template Row (initially d-none) -->
         <div class="row backup-agent-row align-items-center d-none" id="backup-agent-row-template">
            <!-- Backup Agent -->
            <div class="col-md-5 mb-2">
               <div class="mb-3">
                  <label class="form-label">Backup Agent</label>
                  <select name="backup_agent_ids[]" class="form-select backup-agent-select">
                     <option value="">Select</option>
                     <?php foreach ($data['agents'] as $agent): ?>
                        <option value="<?php echo $agent['id']; ?>"><?php echo $agent['name']; ?></option>
                     <?php endforeach; ?>
                  </select>
                  <div class="invalid-feedback">Please select a backup agent.</div>
                  <div class="valid-feedback">Looks good!</div>
               </div>
            </div>

            <!-- Backup Date -->
            <div class="col-md-5 mb-2">
               <div class="mb-3">
                  <label class="form-label">Backup Date</label>
                  <input type="date" name="backup_agent_dates[]" class="form-control" placeholder="YYYY-MM-DD">
                  <div class="invalid-feedback">Please select a valid date.</div>
                  <div class="valid-feedback">Looks good!</div>
               </div>
            </div>

            <!-- Remove Button -->
            <div class="col-md-2 mb-2">
               <div class="d-flex h-100 align-items-end">
                  <button type="button" class="btn btn-outline-danger btn-sm remove-backup-agent-btn form-control date-remove-btn">Remove</button>
               </div>
            </div>
         </div>

         <!-- Add Row Button -->
         <button type="button" class="btn btn-outline-primary btn-sm" id="add-backup-agent-btn">
         + Add Backup Agent
         </button>
      </div>

      <?php echo section_divider(); ?>
      <!-- Submit Button - Mode-aware text -->
      <div class="row mt-4">
         <div class="col-md-3">
               <?php echo button('Update Class', 'submit', 'primary'); ?>
         </div>
      </div>
   </div>
</form>

<!-- Alert container for form messages -->
<div id="form-messages" class="mt-3"></div>

<!-- Pre-populate form data for update mode -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pre-populate learner data if available
    <?php if (isset($data['class_data']['learner_ids']) && !empty($data['class_data']['learner_ids'])): ?>
    const learnerData = <?php echo json_encode($data['class_data']['learner_ids']); ?>;

    // Pre-populate the class learners table
    if (learnerData && Array.isArray(learnerData)) {
        const classLearnersData = document.getElementById('class_learners_data');
        const classLearnersTable = document.getElementById('class-learners-table');
        const classLearnersTbody = document.getElementById('class-learners-tbody');
        const noLearnersMessage = document.getElementById('no-learners-message');

        if (classLearnersData && classLearnersTable && classLearnersTbody) {
            // Set the hidden field value
            classLearnersData.value = JSON.stringify(learnerData);

            // Clear existing rows
            classLearnersTbody.innerHTML = '';

            // Add each learner to the table
            learnerData.forEach(function(learner, index) {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${learner.name || 'Unknown Learner'}</td>
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
                classLearnersTbody.appendChild(row);
            });

            // Show the table and hide the no learners message
            classLearnersTable.classList.remove('d-none');
            if (noLearnersMessage) {
                noLearnersMessage.classList.add('d-none');
            }
        }
    }
    <?php endif; ?>

    // Pre-populate QA visit dates if available
    <?php if (isset($data['class_data']['qa_visit_dates']) && !empty($data['class_data']['qa_visit_dates'])): ?>
    const qaVisitDates = <?php echo json_encode($data['class_data']['qa_visit_dates']); ?>;

    if (qaVisitDates && Array.isArray(qaVisitDates)) {
        const qaVisitsContainer = document.getElementById('qa-visits-container');
        const qaVisitTemplate = document.getElementById('qa-visit-row-template');

        if (qaVisitsContainer && qaVisitTemplate) {
            qaVisitDates.forEach(function(date) {
                const newRow = qaVisitTemplate.cloneNode(true);
                newRow.classList.remove('d-none');
                newRow.removeAttribute('id');

                const dateInput = newRow.querySelector('input[name="qa_visit_dates[]"]');
                if (dateInput) {
                    dateInput.value = date;
                }

                qaVisitsContainer.appendChild(newRow);
            });
        }
    }
    <?php endif; ?>

    // Pre-populate stop/restart dates if available
    <?php if (isset($data['class_data']['stop_restart_dates']) && !empty($data['class_data']['stop_restart_dates'])): ?>
    const stopRestartDates = <?php echo json_encode($data['class_data']['stop_restart_dates']); ?>;

    if (stopRestartDates && Array.isArray(stopRestartDates)) {
        const dateHistoryContainer = document.getElementById('date-history-container');
        const dateHistoryTemplate = document.getElementById('date-history-row-template');

        if (dateHistoryContainer && dateHistoryTemplate) {
            stopRestartDates.forEach(function(dateEntry) {
                const newRow = dateHistoryTemplate.cloneNode(true);
                newRow.classList.remove('d-none');
                newRow.removeAttribute('id');

                const stopDateInput = newRow.querySelector('input[name="stop_dates[]"]');
                const restartDateInput = newRow.querySelector('input[name="restart_dates[]"]');

                if (stopDateInput && dateEntry.stop_date) {
                    stopDateInput.value = dateEntry.stop_date;
                }
                if (restartDateInput && dateEntry.restart_date) {
                    restartDateInput.value = dateEntry.restart_date;
                }

                dateHistoryContainer.appendChild(newRow);
            });
        }
    }
    <?php endif; ?>

    // Pre-populate backup agent data if available
    <?php if (isset($data['class_data']['backup_agent_ids']) && !empty($data['class_data']['backup_agent_ids'])): ?>
    const backupAgentIds = <?php echo json_encode($data['class_data']['backup_agent_ids']); ?>;

    if (backupAgentIds && Array.isArray(backupAgentIds)) {
        const backupAgentsContainer = document.getElementById('backup-agents-container');
        const backupAgentTemplate = document.getElementById('backup-agent-row-template');

        if (backupAgentsContainer && backupAgentTemplate) {
            backupAgentIds.forEach(function(agentId) {
                const newRow = backupAgentTemplate.cloneNode(true);
                newRow.classList.remove('d-none');
                newRow.removeAttribute('id');

                const agentSelect = newRow.querySelector('select[name="backup_agent_ids[]"]');
                if (agentSelect) {
                    agentSelect.value = agentId;
                }

                backupAgentsContainer.appendChild(newRow);
            });
        }
    }
    <?php endif; ?>
});
</script>
