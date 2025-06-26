<?php
/**
 * ClassTypesController.php
 *
 * Controller for handling class types and durations
 * Extracted from WeCoza theme for standalone plugin
 */

namespace WeCozaClasses\Controllers;

class ClassTypesController {
    /**
     * Get all class types (main categories)
     *
     * @return array List of class types
     */
    public static function getClassTypes() {
        return [
            ['id' => 'AET', 'name' => 'AET Communication & Numeracy'],
            ['id' => 'GETC', 'name' => 'GETC AET'],
            ['id' => 'REALLL', 'name' => 'REALLL'],
            ['id' => 'BA2', 'name' => 'Business Admin NQF 2'],
            ['id' => 'BA3', 'name' => 'Business Admin NQF 3'],
            ['id' => 'BA4', 'name' => 'Business Admin NQF 4'],
            ['id' => 'SKILL', 'name' => 'Skill Packages'],
            ['id' => 'SOFT', 'name' => 'Soft Skill Courses'],
        ];
    }

    /**
     * Get all class subjects based on class type
     *
     * @param string $classTypeId Class type ID
     * @return array List of subjects for the given class type
     */
    public static function getClassSubjects($classTypeId = '') {
        $allSubjects = [
            'AET' => [
                ['id' => 'COMM', 'name' => 'Communication (separate)', 'duration' => 120],
                ['id' => 'NUM', 'name' => 'Numeracy (separate)', 'duration' => 120],
                ['id' => 'COMM_NUM', 'name' => 'Communication & Numeracy (both)', 'duration' => 240],
            ],
            'GETC' => [
                ['id' => 'CL4', 'name' => 'Communication level 4', 'duration' => 120],
                ['id' => 'NL4', 'name' => 'Numeracy level 4', 'duration' => 120],
                ['id' => 'LO4', 'name' => 'Life Orientation level 4', 'duration' => 90],
                ['id' => 'HSS4', 'name' => 'Human & Social Sciences level 4', 'duration' => 80],
                ['id' => 'EMS4', 'name' => 'Economic & Management Sciences level 4', 'duration' => 94],
                ['id' => 'NS4', 'name' => 'Natural Sciences level 4', 'duration' => 60],
                ['id' => 'SMME4', 'name' => 'Small Micro Medium Enterprises level 4', 'duration' => 60],
            ],
            'REALLL' => [
                ['id' => 'RLC', 'name' => 'Communication', 'duration' => 160],
                ['id' => 'RLN', 'name' => 'Numeracy', 'duration' => 160],
                ['id' => 'RLF', 'name' => 'Finance', 'duration' => 40],
            ],
            'BA2' => [
                ['id' => 'BA2LP9', 'name' => 'LP9', 'duration' => 80],
                ['id' => 'BA2LP10', 'name' => 'LP10', 'duration' => 64],
                ['id' => 'BA2LP1', 'name' => 'LP1', 'duration' => 72],
                ['id' => 'BA2LP2', 'name' => 'LP2', 'duration' => 56],
                ['id' => 'BA2LP3', 'name' => 'LP3', 'duration' => 40],
                ['id' => 'BA2LP4', 'name' => 'LP4', 'duration' => 20],
                ['id' => 'BA2LP5', 'name' => 'LP5', 'duration' => 56],
                ['id' => 'BA2LP6', 'name' => 'LP6', 'duration' => 60],
                ['id' => 'BA2LP7', 'name' => 'LP7', 'duration' => 40],
                ['id' => 'BA2LP8', 'name' => 'LP8', 'duration' => 32],
            ],
            'BA3' => [
                ['id' => 'BA3LP2', 'name' => 'LP2', 'duration' => 52],
                ['id' => 'BA3LP4', 'name' => 'LP4', 'duration' => 40],
                ['id' => 'BA3LP5', 'name' => 'LP5', 'duration' => 36],
                ['id' => 'BA3LP6', 'name' => 'LP6', 'duration' => 44],
                ['id' => 'BA3LP1', 'name' => 'LP1', 'duration' => 60],
                ['id' => 'BA3LP7', 'name' => 'LP7', 'duration' => 40],
                ['id' => 'BA3LP8', 'name' => 'LP8', 'duration' => 44],
                ['id' => 'BA3LP9', 'name' => 'LP9', 'duration' => 28],
                ['id' => 'BA3LP10', 'name' => 'LP10', 'duration' => 48],
                ['id' => 'BA3LP11', 'name' => 'LP11', 'duration' => 36],
                ['id' => 'BA3LP3', 'name' => 'LP3', 'duration' => 44],
            ],
            'BA4' => [
                ['id' => 'BA4LP2', 'name' => 'LP2', 'duration' => 104],
                ['id' => 'BA4LP3', 'name' => 'LP3', 'duration' => 80],
                ['id' => 'BA4LP4', 'name' => 'LP4', 'duration' => 64],
                ['id' => 'BA4LP1', 'name' => 'LP1', 'duration' => 88],
                ['id' => 'BA4LP6', 'name' => 'LP6', 'duration' => 84],
                ['id' => 'BA4LP5', 'name' => 'LP5', 'duration' => 76],
                ['id' => 'BA4LP7', 'name' => 'LP7', 'duration' => 88],
            ],
            'SKILL' => [
                ['id' => 'WALK', 'name' => 'Walk Package', 'duration' => 120],
                ['id' => 'HEXA', 'name' => 'Hexa Package', 'duration' => 120],
                ['id' => 'RUN', 'name' => 'Run Package', 'duration' => 120],
            ],
            'SOFT' => [
                ['id' => 'IPC', 'name' => 'Introduction to Computers', 'duration' => 20],
                ['id' => 'EQ', 'name' => 'Email Etiquette', 'duration' => 6],
                ['id' => 'TM', 'name' => 'Time Management', 'duration' => 12],
                ['id' => 'SS', 'name' => 'Supervisory Skills', 'duration' => 40],
                ['id' => 'EEPDL', 'name' => 'EEP Digital Literacy', 'duration' => 40],
                ['id' => 'EEPPF', 'name' => 'EEP Personal Finance', 'duration' => 40],
                ['id' => 'EEPWI', 'name' => 'EEP Workplace Intelligence', 'duration' => 40],
                ['id' => 'EEPEI', 'name' => 'EEP Emotional Intelligence', 'duration' => 40],
                ['id' => 'EEPBI', 'name' => 'EEP Business Intelligence', 'duration' => 40],
            ],
        ];

        // If no class type specified, return all subjects
        if (empty($classTypeId)) {
            return $allSubjects;
        }

        // Return subjects for the specified class type
        return isset($allSubjects[$classTypeId]) ? $allSubjects[$classTypeId] : [];
    }

    /**
     * Get class duration by subject ID
     *
     * @param string $subjectId Subject ID
     * @return int Duration in hours
     */
    public static function getClassDuration($subjectId) {
        $allSubjects = self::getClassSubjects();

        // Flatten the array of subjects
        $subjects = [];
        foreach ($allSubjects as $typeSubjects) {
            $subjects = array_merge($subjects, $typeSubjects);
        }

        // Find the subject by ID
        foreach ($subjects as $subject) {
            if ($subject['id'] === $subjectId) {
                return $subject['duration'];
            }
        }

        // Default duration if subject not found
        return 120;
    }
}
