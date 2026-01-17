<?php
/**
 * Scheduled task to calculate ROI for training programs
 *
 * @package    local_kirkpatrick_level4
 * @copyright  2025 Competency-Based Learning System
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kirkpatrick_level4\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Calculate ROI task
 */
class calculate_roi extends \core\task\scheduled_task {
    
    /**
     * Get task name
     */
    public function get_name() {
        return get_string('calculateroi', 'local_kirkpatrick_level4');
    }
    
    /**
     * Execute the task
     */
    public function execute() {
        global $DB;
        
        mtrace('Starting ROI calculation...');
        
        // Get all programs with Level 4 data
        $sql = "SELECT DISTINCT programid 
                FROM {kirkpatrick_level4_results}
                WHERE sync_status = 'completed'";
        
        $programs = $DB->get_records_sql($sql);
        
        foreach ($programs as $program) {
            mtrace("Calculating ROI for program ID: {$program->programid}");
            
            try {
                $roi = $this->calculate_program_roi($program->programid);
                
                // Update Level 4 results with calculated ROI
                $this->update_roi_data($program->programid, $roi);
                
                mtrace("  ✓ ROI calculated: " . number_format($roi, 2) . "%");
            } catch (\Exception $e) {
                mtrace("  ✗ Error calculating ROI: " . $e->getMessage());
            }
        }
        
        mtrace('ROI calculation complete');
    }
    
    /**
     * Calculate ROI for a specific program
     */
    private function calculate_program_roi($programid) {
        global $DB;
        
        // Get training costs
        $training_costs = $this->get_training_costs($programid);
        
        // Get organizational benefits
        $benefits = $this->get_organizational_benefits($programid);
        
        // Calculate ROI: ((Benefits - Costs) / Costs) * 100
        if ($training_costs > 0) {
            $roi = (($benefits - $training_costs) / $training_costs) * 100;
        } else {
            $roi = 0;
        }
        
        return $roi;
    }
    
    /**
     * Get training costs for a program
     */
    private function get_training_costs($programid) {
        // This would calculate:
        // - Instructor time
        // - Materials costs
        // - Facility costs
        // - Learner time (opportunity cost)
        // - Technology costs
        
        // Placeholder calculation
        $base_cost_per_learner = 1000;
        $learner_count = $this->get_learner_count($programid);
        
        return $base_cost_per_learner * $learner_count;
    }
    
    /**
     * Get organizational benefits from Level 4 data
     */
    private function get_organizational_benefits($programid) {
        global $DB;
        
        $results = $DB->get_records('kirkpatrick_level4_results', ['programid' => $programid]);
        
        $total_benefits = 0;
        
        foreach ($results as $result) {
            // Sum up cost savings
            $total_benefits += $result->cost_savings ?? 0;
            
            // Add productivity improvements (converted to monetary value)
            $productivity_value = ($result->productivity_improvement ?? 0) * 1000;
            $total_benefits += $productivity_value;
        }
        
        return $total_benefits;
    }
    
    /**
     * Get learner count for a program
     */
    private function get_learner_count($programid) {
        global $DB;
        
        $sql = "SELECT COUNT(DISTINCT userid) 
                FROM {kirkpatrick_level2_learning}
                WHERE courseid = ?";
        
        return $DB->count_records_sql($sql, [$programid]);
    }
    
    /**
     * Update ROI data in Level 4 results
     */
    private function update_roi_data($programid, $roi) {
        global $DB;
        
        $results = $DB->get_records('kirkpatrick_level4_results', ['programid' => $programid]);
        
        foreach ($results as $result) {
            $result->roi_calculation = $roi;
            $result->timemodified = time();
            $DB->update_record('kirkpatrick_level4_results', $result);
        }
    }
}
