<?php
/**
 * Scheduled task to correlate learner training with organizational outcomes
 *
 * @package    local_kirkpatrick_level4
 * @copyright  2025 Competency-Based Learning System
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kirkpatrick_level4\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Correlate learner outcomes task
 */
class correlate_learner_outcomes extends \core\task\scheduled_task {
    
    /**
     * Get task name
     */
    public function get_name() {
        return get_string('correlatelearneroutcomes', 'local_kirkpatrick_level4');
    }
    
    /**
     * Execute the task
     */
    public function execute() {
        global $DB;
        
        mtrace('Starting learner-outcome correlation...');
        
        // Get all Level 4 results
        $results = $DB->get_records('kirkpatrick_level4_results', ['sync_status' => 'completed']);
        
        foreach ($results as $result) {
            mtrace("Correlating learners for program ID: {$result->programid}");
            
            try {
                $correlations = $this->correlate_program_learners($result);
                
                mtrace("  ✓ Correlated {$correlations} learners");
            } catch (\Exception $e) {
                mtrace("  ✗ Error correlating learners: " . $e->getMessage());
            }
        }
        
        mtrace('Learner-outcome correlation complete');
    }
    
    /**
     * Correlate learners with organizational outcomes
     */
    private function correlate_program_learners($result) {
        global $DB;
        
        // Get all learners who completed this program
        $sql = "SELECT DISTINCT userid 
                FROM {kirkpatrick_level2_learning}
                WHERE courseid = ?
                AND certification_achieved = 1";
        
        $learners = $DB->get_records_sql($sql, [$result->programid]);
        
        $correlation_count = 0;
        
        foreach ($learners as $learner) {
            // Calculate contribution score based on:
            // - Learning gains (Level 2)
            // - Behavior changes (Level 3)
            // - Time since training completion
            
            $contribution = $this->calculate_contribution_score($learner->userid, $result->programid);
            $correlation_strength = $this->determine_correlation_strength($contribution);
            
            // Store correlation
            $correlation = new \stdClass();
            $correlation->userid = $learner->userid;
            $correlation->programid = $result->programid;
            $correlation->level4_resultid = $result->id;
            $correlation->contribution_score = $contribution;
            $correlation->correlation_strength = $correlation_strength;
            $correlation->timecreated = time();
            
            // Check if correlation already exists
            $existing = $DB->get_record('kirkpatrick_learner_outcomes', [
                'userid' => $learner->userid,
                'programid' => $result->programid,
                'level4_resultid' => $result->id
            ]);
            
            if ($existing) {
                $correlation->id = $existing->id;
                $DB->update_record('kirkpatrick_learner_outcomes', $correlation);
            } else {
                $DB->insert_record('kirkpatrick_learner_outcomes', $correlation);
            }
            
            $correlation_count++;
        }
        
        return $correlation_count;
    }
    
    /**
     * Calculate learner contribution score
     */
    private function calculate_contribution_score($userid, $programid) {
        global $DB;
        
        $score = 0;
        
        // Factor 1: Learning gains (Level 2) - 40% weight
        $level2 = $DB->get_record('kirkpatrick_level2_learning', [
            'userid' => $userid,
            'courseid' => $programid
        ]);
        
        if ($level2) {
            $learning_score = ($level2->knowledge_gain ?? 0) * 0.4;
            $score += $learning_score;
        }
        
        // Factor 2: Behavior changes (Level 3) - 40% weight
        $level3 = $DB->get_record('kirkpatrick_level3_behavior', [
            'userid' => $userid,
            'courseid' => $programid
        ]);
        
        if ($level3) {
            $behavior_score = ($level3->performance_rating ?? 0) * 4; // Scale to 40
            $score += $behavior_score;
        }
        
        // Factor 3: Time decay - 20% weight
        // More recent training has higher contribution
        if ($level2) {
            $months_since_training = (time() - $level2->date_assessed) / (30 * 24 * 60 * 60);
            $time_factor = max(0, 20 - ($months_since_training * 2)); // Decay over time
            $score += $time_factor;
        }
        
        return min(100, max(0, $score)); // Normalize to 0-100
    }
    
    /**
     * Determine correlation strength category
     */
    private function determine_correlation_strength($score) {
        if ($score >= 80) {
            return 'strong';
        } elseif ($score >= 60) {
            return 'moderate';
        } elseif ($score >= 40) {
            return 'weak';
        } else {
            return 'minimal';
        }
    }
}
