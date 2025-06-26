<?php
/**
 * Database migration to create the classes table
 * Extracted from classes_schema_1.sql for WeCoza Classes Plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create the classes table with PostgreSQL schema
 * This migration creates the main classes table for the WeCoza Classes Plugin
 */
function wecoza_classes_create_classes_table() {
    try {
        // Get database service
        $db = \WeCozaClasses\Services\Database\DatabaseService::getInstance();
        $pdo = $db->getPdo();

        // Check if table already exists
        $tableExists = $pdo->query("SELECT to_regclass('public.classes')")->fetchColumn();
        
        if ($tableExists) {
            error_log('WeCoza Classes Plugin: Classes table already exists, skipping creation');
            return true;
        }

        // Create the classes table
        $sql = "
            CREATE TABLE public.classes (
                class_id SERIAL PRIMARY KEY,
                client_id INTEGER,
                class_address_line VARCHAR(100),
                class_type VARCHAR(50),
                original_start_date DATE,
                seta_funded BOOLEAN,
                seta VARCHAR(100),
                exam_class BOOLEAN,
                exam_type VARCHAR(50),
                project_supervisor_id INTEGER,
                delivery_date DATE,
                created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
                updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
                site_id INTEGER,
                class_subject VARCHAR(100),
                class_code VARCHAR(50),
                class_duration INTEGER,
                qa_visit_dates TEXT,
                class_agent INTEGER,
                learner_ids JSONB DEFAULT '[]'::jsonb,
                backup_agent_ids JSONB DEFAULT '[]'::jsonb,
                schedule_data JSONB DEFAULT '[]'::jsonb,
                stop_restart_dates JSONB DEFAULT '[]'::jsonb,
                class_notes_data JSONB DEFAULT '[]'::jsonb,
                initial_class_agent INTEGER,
                initial_agent_start_date DATE,
                qa_reports JSONB DEFAULT '[]'::jsonb
            );
        ";

        $pdo->exec($sql);

        // Add comments to columns
        $comments = [
            "COMMENT ON COLUMN public.classes.learner_ids IS 'JSON array storing learner IDs assigned to this class'",
            "COMMENT ON COLUMN public.classes.backup_agent_ids IS 'JSON array storing backup agent IDs for this class'",
            "COMMENT ON COLUMN public.classes.schedule_data IS 'JSON array storing class schedule information including dates, times, and recurrence'",
            "COMMENT ON COLUMN public.classes.stop_restart_dates IS 'JSON array storing dates when class was stopped and restarted'",
            "COMMENT ON COLUMN public.classes.class_notes_data IS 'JSON array storing class notes and comments'",
            "COMMENT ON COLUMN public.classes.qa_reports IS 'JSON array storing QA report file paths and metadata'"
        ];

        foreach ($comments as $comment) {
            $pdo->exec($comment);
        }

        // Create indexes for better performance
        $indexes = [
            "CREATE INDEX idx_classes_client_id ON public.classes(client_id)",
            "CREATE INDEX idx_classes_class_type ON public.classes(class_type)",
            "CREATE INDEX idx_classes_original_start_date ON public.classes(original_start_date)",
            "CREATE INDEX idx_classes_class_agent ON public.classes(class_agent)",
            "CREATE INDEX idx_classes_created_at ON public.classes(created_at)",
            "CREATE INDEX idx_classes_class_code ON public.classes(class_code)"
        ];

        foreach ($indexes as $index) {
            $pdo->exec($index);
        }

        error_log('WeCoza Classes Plugin: Classes table created successfully');
        return true;

    } catch (\Exception $e) {
        error_log('WeCoza Classes Plugin: Error creating classes table: ' . $e->getMessage());
        return false;
    }
}

/**
 * Drop the classes table (for uninstall)
 */
function wecoza_classes_drop_classes_table() {
    try {
        // Get database service
        $db = \WeCozaClasses\Services\Database\DatabaseService::getInstance();
        $pdo = $db->getPdo();

        // Drop the table if it exists
        $sql = "DROP TABLE IF EXISTS public.classes CASCADE";
        $pdo->exec($sql);

        error_log('WeCoza Classes Plugin: Classes table dropped successfully');
        return true;

    } catch (\Exception $e) {
        error_log('WeCoza Classes Plugin: Error dropping classes table: ' . $e->getMessage());
        return false;
    }
}
