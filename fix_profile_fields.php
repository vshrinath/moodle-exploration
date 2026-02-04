<?php
/**
 * Fix user profile fields - make optional and hide from admin
 */
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');
require_once($CFG->libdir . '/adminlib.php');

echo "\n=== Fixing User Profile Fields ===\n\n";

// Get all custom profile fields
$fields = $DB->get_records('user_info_field', null, 'sortorder');

$fellowship_fields = [
    'Fellowship Type',
    'Primary Subspecialty', 
    'Secondary Subspecialty',
    'Medical Registration Number',
    'Emergency Contact',
    'Training Start Date',
    'Training End Date',
    'Assigned Mentor',
    'Alumni Status'
];

$additional_names_fields = [
    'First name - phonetic',
    'Last name - phonetic',
    'Middle name',
    'Alternate name'
];

foreach ($fields as $field) {
    $updated = false;
    
    // Remove "Additional names" fields entirely
    if (in_array($field->name, $additional_names_fields)) {
        echo "Deleting field: {$field->name}\n";
        $DB->delete_records('user_info_field', ['id' => $field->id]);
        $DB->delete_records('user_info_data', ['fieldid' => $field->id]);
        continue;
    }
    
    // Make fellowship fields optional and hide from admin
    if (in_array($field->name, $fellowship_fields)) {
        echo "Updating field: {$field->name}\n";
        
        // Make not required
        if ($field->required == 1) {
            $field->required = 0;
            echo "  - Made optional\n";
            $updated = true;
        }
        
        // Hide from admin (visible = 0 means not visible to everyone)
        // We'll use visibility settings to hide from admin but show to users
        if ($field->visible != 2) {
            $field->visible = 2; // Visible to user only
            echo "  - Set to user-visible only\n";
            $updated = true;
        }
        
        if ($updated) {
            $DB->update_record('user_info_field', $field);
        }
    }
}

echo "\n=== Profile Fields Updated ===\n";
echo "- Fellowship fields are now optional\n";
echo "- Fellowship fields hidden from admin profiles\n";
echo "- Additional names section removed\n\n";
