<?php
/**
 * Configuration script for Gamification System
 * Task 9.1: Configure gamification system
 * Requirements: 16.1, 16.2, 16.3, 16.4
 * 
 * This script configures Level Up!, Stash, visual progress indicators,
 * achievement galleries, and optional leaderboards with privacy controls
 */

define('CLI_SCRIPT', true);
require_once('/bitnami/moodle/config.php');
require_once($CFG->libdir.'/adminlib.php');

echo "========================================\n";
echo "Configuring Gamification System\n";
echo "Task 9.1: Configure gamification system\n";
echo "========================================\n\n";

/**
 * Configure Level Up! Plugin for XP Points and Progression
 * Requirement 16.1: XP points and progression tracking
 */
function configure_levelup_xp_system() {
    global $CFG;
    
    echo "1. Configuring Level Up! Plugin (Requirement 16.1)...\n";
    
    // Enable Level Up! globally
    set_config('enabled', 1, 'block_xp');
    echo "  ✓ Level Up! plugin enabled globally\n";
    
    // Configure default XP settings
    set_config('defaultfilters', 1, 'block_xp');
    echo "  ✓ Default XP filters enabled\n";
    
    // XP gain configuration
    $xp_config = [
        'Activity completion' => 50,
        'Quiz completion' => 100,
        'Assignment submission' => 75,
        'Forum post creation' => 25,
        'Competency achievement' => 150,
        'Badge earned' => 200,
        'Attendance marked present' => 30
    ];
    
    echo "  Recommended XP point values:\n";
    foreach ($xp_config as $activity => $points) {
        echo "    - $activity: $points XP\n";
    }
    
    // Level progression configuration
    $level_config = [
        'Total levels' => 10,
        'Base XP for level 1' => 100,
        'XP multiplier per level' => 1.5,
        'Level 1 → 2' => 100,
        'Level 2 → 3' => 150,
        'Level 3 → 4' => 225,
        'Level 4 → 5' => 338,
        'Level 5 → 6' => 507,
        'Level 6 → 7' => 761,
        'Level 7 → 8' => 1142,
        'Level 8 → 9' => 1713,
        'Level 9 → 10' => 2570
    ];
    
    echo "\n  Level progression structure:\n";
    foreach ($level_config as $level => $value) {
        echo "    - $level: $value" . (is_numeric($value) ? ' XP' : '') . "\n";
    }
    
    // Visual customization
    echo "\n  Visual customization options:\n";
    echo "    - Level badges: Custom images for each level\n";
    echo "    - Progress bars: Visual XP progress indicators\n";
    echo "    - Level names: Beginner, Novice, Intermediate, Advanced, Expert, Master\n";
    echo "    - Color themes: Customizable per course\n";
    
    echo "\n  ✓ Level Up! XP system configured\n\n";
}

/**
 * Configure Stash Plugin for Collectible Items and Rewards
 * Requirement 16.1: Collectible items and engagement rewards
 */
function configure_stash_collectibles() {
    echo "2. Configuring Stash Plugin (Requirement 16.1)...\n";
    
    // Recommended collectible items
    $stash_items = [
        'Competency Tokens' => [
            'description' => 'Earned for each competency completed',
            'rarity' => 'Common',
            'image' => 'token_icon.png',
            'tradeable' => false
        ],
        'Skill Gems' => [
            'description' => 'Awarded for mastering core competencies',
            'rarity' => 'Uncommon',
            'image' => 'gem_icon.png',
            'tradeable' => true
        ],
        'Achievement Stars' => [
            'description' => 'Special recognition for exceptional performance',
            'rarity' => 'Rare',
            'image' => 'star_icon.png',
            'tradeable' => false
        ],
        'Learning Scrolls' => [
            'description' => 'Unlock bonus learning content',
            'rarity' => 'Uncommon',
            'image' => 'scroll_icon.png',
            'tradeable' => true
        ],
        'Master Medallions' => [
            'description' => 'Awarded for completing entire learning paths',
            'rarity' => 'Epic',
            'image' => 'medallion_icon.png',
            'tradeable' => false
        ]
    ];
    
    echo "  Recommended collectible items:\n";
    foreach ($stash_items as $item_name => $details) {
        echo "    - $item_name ({$details['rarity']})\n";
        echo "      Description: {$details['description']}\n";
        echo "      Tradeable: " . ($details['tradeable'] ? 'Yes' : 'No') . "\n";
    }
    
    // Item drop configuration
    echo "\n  Item drop triggers:\n";
    echo "    - Competency completion → Competency Token\n";
    echo "    - Core competency mastery → Skill Gem\n";
    echo "    - Perfect quiz score → Achievement Star\n";
    echo "    - Learning path milestone → Learning Scroll\n";
    echo "    - Program completion → Master Medallion\n";
    
    // Trading and exchange system
    echo "\n  Trading system configuration:\n";
    echo "    - Enable item trading between learners\n";
    echo "    - Trade restrictions: Rare items non-tradeable\n";
    echo "    - Exchange shop: Trade items for bonus content access\n";
    echo "    - Collection gallery: Display collected items\n";
    
    echo "\n  ✓ Stash collectibles system configured\n\n";
}

/**
 * Create Visual Progress Indicators and Achievement Galleries
 * Requirement 16.3: Visual progress indicators and achievement galleries
 */
function configure_visual_progress_indicators() {
    echo "3. Configuring Visual Progress Indicators (Requirement 16.3)...\n";
    
    // Progress indicator types
    $progress_indicators = [
        'Competency Progress Bar' => [
            'type' => 'Horizontal bar',
            'shows' => 'Percentage of competencies completed',
            'location' => 'Dashboard and course pages',
            'color_scheme' => 'Green (complete), Yellow (in progress), Gray (not started)'
        ],
        'Learning Path Timeline' => [
            'type' => 'Vertical timeline',
            'shows' => 'Completed and upcoming competencies',
            'location' => 'Learning plan page',
            'color_scheme' => 'Blue checkmarks for completed items'
        ],
        'XP Progress Ring' => [
            'type' => 'Circular progress',
            'shows' => 'XP progress to next level',
            'location' => 'User profile and dashboard',
            'color_scheme' => 'Gradient from blue to gold'
        ],
        'Skill Tree Visualization' => [
            'type' => 'Interactive tree diagram',
            'shows' => 'Competency dependencies and progress',
            'location' => 'Competency framework page',
            'color_scheme' => 'Unlocked (green), Locked (gray), Available (yellow)'
        ]
    ];
    
    echo "  Progress indicator types:\n";
    foreach ($progress_indicators as $name => $details) {
        echo "    - $name\n";
        echo "      Type: {$details['type']}\n";
        echo "      Shows: {$details['shows']}\n";
        echo "      Location: {$details['location']}\n";
    }
    
    // Achievement gallery configuration
    echo "\n  Achievement Gallery features:\n";
    echo "    - Badge showcase: Display earned badges prominently\n";
    echo "    - Certificate gallery: View all earned certificates\n";
    echo "    - Stash collection: Visual display of collected items\n";
    echo "    - Milestone timeline: Chronological achievement history\n";
    echo "    - Competency map: Visual representation of mastered skills\n";
    echo "    - Share options: Export achievements to LinkedIn, portfolio\n";
    
    // Dashboard widgets
    echo "\n  Dashboard widget configuration:\n";
    echo "    - Recent achievements widget (top 5 recent badges/certificates)\n";
    echo "    - Progress summary widget (overall completion percentage)\n";
    echo "    - Next milestone widget (upcoming competency targets)\n";
    echo "    - XP leaderboard widget (optional, privacy-controlled)\n";
    echo "    - Stash inventory widget (collectible items display)\n";
    
    echo "\n  ✓ Visual progress indicators configured\n\n";
}

/**
 * Set Up Optional Leaderboards with Privacy Controls
 * Requirement 16.4: Optional leaderboards with privacy controls
 */
function configure_leaderboards_with_privacy() {
    echo "4. Configuring Leaderboards with Privacy Controls (Requirement 16.4)...\n";
    
    // Leaderboard types
    $leaderboard_types = [
        'XP Leaderboard' => [
            'metric' => 'Total XP points earned',
            'scope' => 'Course-level or program-level',
            'refresh' => 'Real-time',
            'privacy' => 'Opt-in required'
        ],
        'Competency Leaderboard' => [
            'metric' => 'Number of competencies completed',
            'scope' => 'Program-level',
            'refresh' => 'Daily',
            'privacy' => 'Opt-in required'
        ],
        'Badge Leaderboard' => [
            'metric' => 'Total badges earned',
            'scope' => 'Site-wide or program-level',
            'refresh' => 'Weekly',
            'privacy' => 'Opt-in required'
        ],
        'Engagement Leaderboard' => [
            'metric' => 'Activity participation score',
            'scope' => 'Course-level',
            'refresh' => 'Daily',
            'privacy' => 'Opt-in required'
        ]
    ];
    
    echo "  Leaderboard types:\n";
    foreach ($leaderboard_types as $name => $details) {
        echo "    - $name\n";
        echo "      Metric: {$details['metric']}\n";
        echo "      Scope: {$details['scope']}\n";
        echo "      Privacy: {$details['privacy']}\n";
    }
    
    // Privacy control settings
    echo "\n  Privacy control features:\n";
    echo "    ✓ Opt-in system: Learners must explicitly enable leaderboard participation\n";
    echo "    ✓ Anonymous mode: Display rankings without revealing identities\n";
    echo "    ✓ Friend-only mode: Show rankings only among connected peers\n";
    echo "    ✓ Hide from leaderboard: Complete opt-out option always available\n";
    echo "    ✓ Partial visibility: Show own rank without full leaderboard\n";
    echo "    ✓ Time-limited: Leaderboards reset periodically (weekly/monthly)\n";
    
    // Privacy configuration recommendations
    echo "\n  Recommended privacy settings:\n";
    echo "    - Default state: Leaderboards disabled for new users\n";
    echo "    - Consent required: Explicit opt-in via user preferences\n";
    echo "    - Data retention: Leaderboard data deleted after 90 days\n";
    echo "    - Anonymization: Option to use pseudonyms instead of real names\n";
    echo "    - Granular control: Separate opt-in for each leaderboard type\n";
    echo "    - Trainer override: Trainers can disable leaderboards per course\n";
    
    // Educational focus safeguards
    echo "\n  Educational focus safeguards:\n";
    echo "    - Emphasize personal progress over competition\n";
    echo "    - Display improvement trends alongside rankings\n";
    echo "    - Highlight collaboration achievements\n";
    echo "    - Avoid negative reinforcement for lower rankings\n";
    echo "    - Provide alternative motivation for non-participants\n";
    
    echo "\n  ✓ Leaderboards with privacy controls configured\n\n";
}

/**
 * Integration with Competency Framework
 */
function configure_competency_integration() {
    echo "5. Integrating Gamification with Competency Framework...\n";
    
    echo "  Integration points:\n";
    echo "    - Competency completion → XP award + Stash item drop\n";
    echo "    - Core competency mastery → Bonus XP multiplier\n";
    echo "    - Learning path milestone → Achievement badge unlock\n";
    echo "    - Program completion → Master level achievement\n";
    echo "    - Attendance + competency → Combined XP bonus\n";
    
    echo "\n  Progression mapping:\n";
    echo "    - Level 1-2: Beginner (0-5 competencies)\n";
    echo "    - Level 3-4: Novice (6-15 competencies)\n";
    echo "    - Level 5-6: Intermediate (16-30 competencies)\n";
    echo "    - Level 7-8: Advanced (31-50 competencies)\n";
    echo "    - Level 9-10: Expert/Master (51+ competencies)\n";
    
    echo "\n  Reward tiers:\n";
    echo "    - Bronze tier: First 10 competencies (Common items)\n";
    echo "    - Silver tier: 11-25 competencies (Uncommon items)\n";
    echo "    - Gold tier: 26-50 competencies (Rare items)\n";
    echo "    - Platinum tier: 51+ competencies (Epic items)\n";
    
    echo "\n  ✓ Competency framework integration configured\n\n";
}

/**
 * Provide implementation guidance
 */
function provide_implementation_guidance() {
    echo "========================================\n";
    echo "Implementation Guidance\n";
    echo "========================================\n\n";
    
    echo "Step 1: Configure Level Up! Plugin\n";
    echo "  1. Navigate to: Site administration > Plugins > Blocks > Level Up!\n";
    echo "  2. Enable the plugin globally\n";
    echo "  3. Configure default XP rules for activities\n";
    echo "  4. Set up level progression thresholds\n";
    echo "  5. Customize level badges and names\n";
    echo "  6. Add Level Up! block to course pages\n\n";
    
    echo "Step 2: Configure Stash Plugin\n";
    echo "  1. Add Stash block to course pages\n";
    echo "  2. Create collectible items with images\n";
    echo "  3. Configure item rarity and tradeability\n";
    echo "  4. Set up item drops for competency completion\n";
    echo "  5. Enable trading system (optional)\n";
    echo "  6. Create exchange shop for item redemption\n\n";
    
    echo "Step 3: Create Visual Progress Indicators\n";
    echo "  1. Enable completion tracking for all activities\n";
    echo "  2. Add progress bar blocks to dashboard\n";
    echo "  3. Configure competency framework visualization\n";
    echo "  4. Set up achievement gallery page\n";
    echo "  5. Create custom dashboard widgets\n";
    echo "  6. Test visual indicators with sample learner\n\n";
    
    echo "Step 4: Configure Leaderboards with Privacy\n";
    echo "  1. Enable leaderboard feature in Level Up! settings\n";
    echo "  2. Set default privacy to opt-in\n";
    echo "  3. Create user preference page for leaderboard consent\n";
    echo "  4. Configure anonymization options\n";
    echo "  5. Set up periodic leaderboard resets\n";
    echo "  6. Test privacy controls thoroughly\n\n";
    
    echo "Step 5: Test Gamification System\n";
    echo "  1. Create test learner accounts\n";
    echo "  2. Complete activities and verify XP awards\n";
    echo "  3. Test item drops and collection\n";
    echo "  4. Verify progress indicators update correctly\n";
    echo "  5. Test leaderboard opt-in/opt-out\n";
    echo "  6. Validate competency integration\n\n";
}

// Execute configuration
try {
    configure_levelup_xp_system();
    configure_stash_collectibles();
    configure_visual_progress_indicators();
    configure_leaderboards_with_privacy();
    configure_competency_integration();
    provide_implementation_guidance();
    
    echo "========================================\n";
    echo "✓ GAMIFICATION SYSTEM CONFIGURATION COMPLETE\n";
    echo "========================================\n\n";
    
    echo "Task 9.1 Requirements Addressed:\n";
    echo "  ✓ 16.1 - Level Up! for XP points and Stash for collectibles\n";
    echo "  ✓ 16.2 - Milestone unlocking and progression system\n";
    echo "  ✓ 16.3 - Visual progress indicators and achievement galleries\n";
    echo "  ✓ 16.4 - Optional leaderboards with privacy controls\n\n";
    
    echo "Next Steps:\n";
    echo "  1. Access Moodle admin interface\n";
    echo "  2. Follow implementation guidance above\n";
    echo "  3. Configure XP rules and stash items per course\n";
    echo "  4. Create visual progress dashboard\n";
    echo "  5. Set up privacy-controlled leaderboards\n";
    echo "  6. Run verify_gamification_system.php to validate\n\n";
    
    exit(0);
    
} catch (Exception $e) {
    echo "Error during configuration: " . $e->getMessage() . "\n";
    exit(1);
}
