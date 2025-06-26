<?php
/**
 * ClassModel.php
 *
 * Class model for the WeCoza Classes Plugin
 * Extracted from WeCoza theme for standalone plugin
 */

namespace WeCozaClasses\Models;

use WeCozaClasses\Services\Database\DatabaseService;

class ClassModel {
    /**
     * Class properties - mapped to optimized schema
     */
    private $id;
    private $clientId;
    private $siteId;
    private $classAddressLine;
    private $classType;
    private $classSubject;
    private $classCode;
    private $classDuration;
    private $originalStartDate;
    private $setaFunded;
    private $seta;
    private $examClass;
    private $examType;
    private $qaVisitDates;
    private $qaReports = [];
    private $classAgent;
    private $initialClassAgent;
    private $initialAgentStartDate;
    private $projectSupervisorId;
    private $deliveryDate;
    private $learnerIds = [];
    private $backupAgentIds = [];
    private $scheduleData = [];
    private $stopRestartDates = [];
    private $classNotesData = [];
    private $createdAt;
    private $updatedAt;

    // Additional properties for enriched data (not stored in database)
    public $client_name;
    public $agent_name;
    public $supervisor_name;
    public $site_name;

    /**
     * Constructor
     */
    public function __construct($data = null) {
        if ($data) {
            $this->hydrate($data);
        }
    }

    /**
     * Hydrate model from database row or form data
     */
    private function hydrate($data) {
        // Map database fields to properties
        $this->setId($data['class_id'] ?? $data['id'] ?? null);
        $this->setClientId($data['client_id'] ?? null);
        $this->setSiteId($data['site_id'] ?? null);
        $this->setClassAddressLine($data['class_address_line'] ?? $data['site_address'] ?? null);
        $this->setClassType($data['class_type'] ?? null);
        $this->setClassSubject($data['class_subject'] ?? null);
        $this->setClassCode($data['class_code'] ?? null);
        $this->setClassDuration($data['class_duration'] ?? null);
        $this->setOriginalStartDate($data['original_start_date'] ?? $data['class_start_date'] ?? null);
        $this->setSetaFunded($data['seta_funded'] ?? null);
        $this->setSeta($data['seta'] ?? $data['seta_id'] ?? null);
        $this->setExamClass($data['exam_class'] ?? null);
        $this->setExamType($data['exam_type'] ?? null);
        $this->setQaVisitDates($data['qa_visit_dates'] ?? null);
        $this->setQaReports($this->parseJsonField($data['qa_reports'] ?? []));
        $this->setClassAgent($data['class_agent'] ?? null);
        $this->setInitialClassAgent($data['initial_class_agent'] ?? null);
        $this->setInitialAgentStartDate($data['initial_agent_start_date'] ?? null);
        $this->setProjectSupervisorId($data['project_supervisor_id'] ?? $data['project_supervisor'] ?? null);
        $this->setDeliveryDate($data['delivery_date'] ?? null);
        $this->setCreatedAt($data['created_at'] ?? null);
        $this->setUpdatedAt($data['updated_at'] ?? null);

        // Handle JSONB arrays - support both snake_case and camelCase
        $this->setLearnerIds($this->parseJsonField($data['learner_ids'] ?? $data['learnerIds'] ?? $data['add_learner'] ?? []));
        $this->setBackupAgentIds($this->parseJsonField($data['backup_agent_ids'] ?? $data['backupAgentIds'] ?? $data['backup_agent'] ?? []));
        $this->setScheduleData($this->parseJsonField($data['schedule_data'] ?? $data['scheduleData'] ?? []));
        $this->setStopRestartDates($this->parseJsonField($data['stop_restart_dates'] ?? []));
        $this->setClassNotesData($this->parseJsonField($data['class_notes_data'] ?? $data['classNotes'] ?? $data['class_notes'] ?? []));
    }

    /**
     * Parse JSON field from database or form data
     */
    private function parseJsonField($field) {
        if (is_string($field)) {
            return json_decode($field, true) ?: [];
        }
        return is_array($field) ? $field : [];
    }

    /**
     * Get class by ID
     */
    public static function getById($id) {
        try {
            $db = DatabaseService::getInstance();
            $stmt = $db->query("SELECT * FROM classes WHERE class_id = ?", [$id]);

            if ($row = $stmt->fetch()) {
                return new self($row);
            }

            return null;
        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Error fetching class: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Save class data to optimized schema
     */
    public function save() {
        try {
            $db = DatabaseService::getInstance();
            $db->beginTransaction();

            $now = date('Y-m-d H:i:s');
            $this->setCreatedAt($now);
            $this->setUpdatedAt($now);

            // Prepare stop/restart dates as JSONB
            $stopRestartJson = $this->prepareStopRestartDates();

            // Insert into single classes table
            $sql = "INSERT INTO classes (
                client_id, site_id, class_address_line, class_type, class_subject,
                class_code, class_duration, original_start_date, seta_funded, seta,
                exam_class, exam_type, qa_visit_dates, qa_reports, class_agent, initial_class_agent,
                initial_agent_start_date, project_supervisor_id, delivery_date,
                learner_ids, backup_agent_ids, schedule_data, stop_restart_dates,
                class_notes_data, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $this->getClientId(),
                $this->getSiteId(),
                $this->getClassAddressLine(),
                $this->getClassType(),
                $this->getClassSubject(),
                $this->getClassCode(),
                $this->getClassDuration(),
                $this->getOriginalStartDate(),
                $this->getSetaFunded(),
                $this->getSeta(),
                $this->getExamClass(),
                $this->getExamType(),
                $this->getQaVisitDates(),
                json_encode($this->getQaReports()),
                $this->getClassAgent(),
                $this->getInitialClassAgent(),
                $this->getInitialAgentStartDate(),
                $this->getProjectSupervisorId(),
                $this->getDeliveryDate(),
                json_encode($this->getLearnerIds()),
                json_encode($this->getBackupAgentIds()),
                json_encode($this->getScheduleData()),
                $stopRestartJson,
                json_encode($this->getClassNotesData()),
                $this->getCreatedAt(),
                $this->getUpdatedAt()
            ];

            $db->query($sql, $params);
            $classId = $db->lastInsertId();
            $this->setId($classId);

            $db->commit();
            return true;
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            error_log('WeCoza Classes Plugin: Error saving class: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update class data
     */
    public function update() {
        try {
            $db = DatabaseService::getInstance();
            $db->beginTransaction();

            $this->setUpdatedAt(date('Y-m-d H:i:s'));
            $stopRestartJson = $this->prepareStopRestartDates();

            $sql = "UPDATE classes SET
                client_id = ?, site_id = ?, class_address_line = ?, class_type = ?,
                class_subject = ?, class_code = ?, class_duration = ?, original_start_date = ?,
                seta_funded = ?, seta = ?, exam_class = ?, exam_type = ?, qa_visit_dates = ?, qa_reports = ?,
                class_agent = ?, initial_class_agent = ?, initial_agent_start_date = ?,
                project_supervisor_id = ?, delivery_date = ?, learner_ids = ?, backup_agent_ids = ?,
                schedule_data = ?, stop_restart_dates = ?, class_notes_data = ?, updated_at = ?
                WHERE class_id = ?";

            $params = [
                $this->getClientId(), $this->getSiteId(), $this->getClassAddressLine(),
                $this->getClassType(), $this->getClassSubject(), $this->getClassCode(),
                $this->getClassDuration(), $this->getOriginalStartDate(), $this->getSetaFunded(),
                $this->getSeta(), $this->getExamClass(), $this->getExamType(),
                $this->getQaVisitDates(), json_encode($this->getQaReports()), $this->getClassAgent(), $this->getInitialClassAgent(),
                $this->getInitialAgentStartDate(), $this->getProjectSupervisorId(),
                $this->getDeliveryDate(), json_encode($this->getLearnerIds()),
                json_encode($this->getBackupAgentIds()), json_encode($this->getScheduleData()),
                $stopRestartJson, json_encode($this->getClassNotesData()),
                $this->getUpdatedAt(), $this->getId()
            ];

            $db->query($sql, $params);
            $db->commit();
            return true;
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            error_log('WeCoza Classes Plugin: Error updating class: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete class
     */
    public function delete() {
        try {
            $db = DatabaseService::getInstance();
            $db->query("DELETE FROM classes WHERE class_id = ?", [$this->getId()]);
            return true;
        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Error deleting class: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Prepare stop/restart dates for JSONB storage
     */
    private function prepareStopRestartDates() {
        $stopDates = $this->getStopDates();
        $restartDates = $this->getRestartDates();
        $combined = [];

        if (!empty($stopDates) && !empty($restartDates)) {
            for ($i = 0; $i < max(count($stopDates), count($restartDates)); $i++) {
                if (!empty($stopDates[$i]) || !empty($restartDates[$i])) {
                    $combined[] = [
                        'stop_date' => $stopDates[$i] ?? null,
                        'restart_date' => $restartDates[$i] ?? null
                    ];
                }
            }
        }

        return json_encode($combined);
    }

    // Getters and Setters for all properties
    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; return $this; }

    public function getClientId() { return $this->clientId; }
    public function setClientId($clientId) {
        $this->clientId = is_numeric($clientId) ? intval($clientId) : null;
        return $this;
    }

    public function getSiteId() { return $this->siteId; }
    public function setSiteId($siteId) {
        $this->siteId = is_string($siteId) || is_numeric($siteId) ? $siteId : null;
        return $this;
    }

    public function getClassAddressLine() { return $this->classAddressLine; }
    public function setClassAddressLine($classAddressLine) { $this->classAddressLine = $classAddressLine; return $this; }

    public function getClassType() { return $this->classType; }
    public function setClassType($classType) { $this->classType = $classType; return $this; }

    public function getClassSubject() { return $this->classSubject; }
    public function setClassSubject($classSubject) { $this->classSubject = $classSubject; return $this; }

    public function getClassCode() { return $this->classCode; }
    public function setClassCode($classCode) { $this->classCode = $classCode; return $this; }

    public function getClassDuration() { return $this->classDuration; }
    public function setClassDuration($classDuration) { $this->classDuration = $classDuration; return $this; }

    public function getOriginalStartDate() { return $this->originalStartDate; }
    public function setOriginalStartDate($originalStartDate) { $this->originalStartDate = $originalStartDate; return $this; }

    public function getSetaFunded() { return $this->setaFunded; }
    public function setSetaFunded($setaFunded) {
        // Convert Yes/No to boolean for database
        if ($setaFunded === 'Yes') {
            $this->setaFunded = true;
        } elseif ($setaFunded === 'No') {
            $this->setaFunded = false;
        } else {
            $this->setaFunded = is_bool($setaFunded) ? $setaFunded : null;
        }
        return $this;
    }

    public function getSeta() { return $this->seta; }
    public function setSeta($seta) {
        $this->seta = is_string($seta) ? $seta : null;
        return $this;
    }

    public function getExamClass() { return $this->examClass; }
    public function setExamClass($examClass) {
        // Convert Yes/No to boolean for database
        if ($examClass === 'Yes') {
            $this->examClass = true;
        } elseif ($examClass === 'No') {
            $this->examClass = false;
        } else {
            $this->examClass = is_bool($examClass) ? $examClass : null;
        }
        return $this;
    }

    public function getExamType() { return $this->examType; }
    public function setExamType($examType) { $this->examType = $examType; return $this; }

    public function getQaVisitDates() { return $this->qaVisitDates; }
    public function setQaVisitDates($qaVisitDates) { $this->qaVisitDates = $qaVisitDates; return $this; }

    public function getQaReports() { return $this->qaReports; }
    public function setQaReports($qaReports) { $this->qaReports = is_array($qaReports) ? $qaReports : []; return $this; }

    public function getClassAgent() { return $this->classAgent; }
    public function setClassAgent($classAgent) {
        $this->classAgent = is_numeric($classAgent) ? intval($classAgent) : null;
        return $this;
    }

    public function getInitialClassAgent() { return $this->initialClassAgent; }
    public function setInitialClassAgent($initialClassAgent) {
        $this->initialClassAgent = is_numeric($initialClassAgent) ? intval($initialClassAgent) : null;
        return $this;
    }

    public function getInitialAgentStartDate() { return $this->initialAgentStartDate; }
    public function setInitialAgentStartDate($initialAgentStartDate) {
        $this->initialAgentStartDate = is_string($initialAgentStartDate) ? $initialAgentStartDate : null;
        return $this;
    }

    public function getProjectSupervisorId() { return $this->projectSupervisorId; }
    public function setProjectSupervisorId($projectSupervisorId) {
        $this->projectSupervisorId = is_numeric($projectSupervisorId) ? intval($projectSupervisorId) : null;
        return $this;
    }

    public function getDeliveryDate() { return $this->deliveryDate; }
    public function setDeliveryDate($deliveryDate) { $this->deliveryDate = $deliveryDate; return $this; }

    public function getLearnerIds() { return $this->learnerIds; }
    public function setLearnerIds($learnerIds) { $this->learnerIds = is_array($learnerIds) ? $learnerIds : []; return $this; }

    public function getBackupAgentIds() { return $this->backupAgentIds; }
    public function setBackupAgentIds($backupAgentIds) { $this->backupAgentIds = is_array($backupAgentIds) ? $backupAgentIds : []; return $this; }

    public function getScheduleData() { return $this->scheduleData; }
    public function setScheduleData($scheduleData) { $this->scheduleData = is_array($scheduleData) ? $scheduleData : []; return $this; }

    public function getStopRestartDates() { return $this->stopRestartDates; }
    public function setStopRestartDates($stopRestartDates) { $this->stopRestartDates = is_array($stopRestartDates) ? $stopRestartDates : []; return $this; }

    // Helper methods for stop/restart dates (for backward compatibility)
    public function getStopDates() {
        return array_column($this->stopRestartDates, 'stop_date');
    }

    public function setStopDates($stopDates) {
        // This will be handled by prepareStopRestartDates()
        return $this;
    }

    public function getRestartDates() {
        return array_column($this->stopRestartDates, 'restart_date');
    }

    public function setRestartDates($restartDates) {
        // This will be handled by prepareStopRestartDates()
        return $this;
    }

    public function getClassNotesData() { return $this->classNotesData; }
    public function setClassNotesData($classNotesData) { $this->classNotesData = is_array($classNotesData) ? $classNotesData : []; return $this; }

    public function getCreatedAt() { return $this->createdAt; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt() { return $this->updatedAt; }
    public function setUpdatedAt($updatedAt) { $this->updatedAt = $updatedAt; return $this; }

    /**
     * Get learner data in the new format (array of objects with id, name, status, level)
     * Handles backward compatibility with old format (array of IDs)
     */
    public function getLearnerData() {
        if (empty($this->learnerIds)) {
            return [];
        }

        // Check if we have the new format (array of objects) or old format (array of IDs)
        $firstItem = reset($this->learnerIds);

        if (is_array($firstItem) && isset($firstItem['id'])) {
            // New format - return as is
            return $this->learnerIds;
        } else {
            // Old format - convert IDs to minimal object structure
            return array_map(function($id) {
                return [
                    'id' => intval($id),
                    'name' => 'Legacy Learner',
                    'status' => 'CIC - Currently in Class',
                    'level' => ''
                ];
            }, $this->learnerIds);
        }
    }

    /**
     * Get only the learner IDs (for backward compatibility)
     * Extracts IDs from both old format (array of IDs) and new format (array of objects)
     */
    public function getLearnerIdsOnly() {
        if (empty($this->learnerIds)) {
            return [];
        }

        // Check if we have the new format (array of objects) or old format (array of IDs)
        $firstItem = reset($this->learnerIds);

        if (is_array($firstItem) && isset($firstItem['id'])) {
            // New format - extract IDs from objects
            return array_map(function($learner) {
                return intval($learner['id']);
            }, $this->learnerIds);
        } else {
            // Old format - return as is (already just IDs)
            return array_map('intval', $this->learnerIds);
        }
    }

    /**
     * Get validation rules for class data - DEPRECATED
     * Server-side validation has been removed. All validation is handled on the frontend.
     * This method is kept for backward compatibility but returns empty array.
     */
    public static function getValidationRules() {
        // Server-side validation disabled - using frontend validation only
        return [];
    }

    /**
     * Validate class data - DEPRECATED
     * Server-side validation has been removed. All validation is handled on the frontend.
     * This method always returns true for backward compatibility.
     */
    public static function validate($data) {
        // Server-side validation disabled - using frontend validation only
        return true;
    }

    /**
     * Get all classes (for listing)
     */
    public static function getAll($limit = null, $offset = 0) {
        try {
            $db = DatabaseService::getInstance();
            $sql = "SELECT * FROM classes ORDER BY created_at DESC";

            if ($limit) {
                $sql .= " LIMIT ? OFFSET ?";
                $stmt = $db->query($sql, [$limit, $offset]);
            } else {
                $stmt = $db->query($sql);
            }

            $classes = [];
            while ($row = $stmt->fetch()) {
                $classes[] = new self($row);
            }

            return $classes;
        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Error fetching classes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Search classes by criteria
     */
    public static function search($criteria = []) {
        try {
            $db = DatabaseService::getInstance();
            $where = [];
            $params = [];

            if (!empty($criteria['client_id'])) {
                $where[] = "client_id = ?";
                $params[] = $criteria['client_id'];
            }

            if (!empty($criteria['class_type'])) {
                $where[] = "class_type = ?";
                $params[] = $criteria['class_type'];
            }

            if (!empty($criteria['class_agent'])) {
                $where[] = "class_agent = ?";
                $params[] = $criteria['class_agent'];
            }

            $sql = "SELECT * FROM classes";
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            $sql .= " ORDER BY created_at DESC";

            $stmt = $db->query($sql, $params);
            $classes = [];

            while ($row = $stmt->fetch()) {
                $classes[] = new self($row);
            }

            return $classes;
        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Error searching classes: ' . $e->getMessage());
            return [];
        }
    }
}
