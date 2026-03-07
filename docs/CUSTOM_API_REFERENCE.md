# Custom API Reference — Plugin APIs and Helpers

**Complete reference for custom plugin APIs and helper functions**

This document provides detailed API documentation for all custom plugins and reusable components in this repository. Use this as a reference when extending or integrating with existing functionality.

---

## Table of Contents

1. [sceh_card Renderer](#sceh_card-renderer)
2. [config_helper Functions](#config_helper-functions)
3. [Dashboard Block API](#dashboard-block-api)
4. [Role Helper Functions](#role-helper-functions)
5. [Stream Management API](#stream-management-api)
6. [Importer API](#importer-api)
7. [Custom Capabilities](#custom-capabilities)

---

## sceh_card Renderer

**Location**: `local_sceh_rules/classes/output/sceh_card.php`

**Purpose**: Reusable card component system for consistent UI across custom pages

### Class: `local_sceh_rules\output\sceh_card`

#### Method: `simple(array $config): string`

Renders a simple navigation card with icon, title, and optional link.

**Parameters**:
```php
$config = [
    'title' => string,        // Card title (REQUIRED)
    'icon' => string,         // Font Awesome icon class (default: 'fa-circle')
    'color' => string,        // Color theme: 'blue', 'green', 'orange', 'red' (default: 'blue')
    'url' => moodle_url|string, // Link URL (optional)
    'subtitle' => string,     // Subtitle text (optional)
    'value' => string,        // Stat value (optional)
    'label' => string,        // Stat label (optional)
    'size' => string,         // Size: 'small', 'medium', 'large' (default: 'small')
];
```

**Returns**: `string` - HTML markup

**Example**:
```php
use local_sceh_rules\output\sceh_card;

$html = sceh_card::simple([
    'title' => 'My Competencies',
    'icon' => 'fa-graduation-cap',
    'color' => 'blue',
    'url' => new moodle_url('/admin/tool/lp/coursecompetencies.php', ['courseid' => $courseid]),
    'value' => '12',
    'label' => 'Completed',
]);

echo $html;
```

**Output**:
```html
<div class="sceh-card sceh-card-blue sceh-card-small">
    <a href="..." class="sceh-card-link">
        <i class="fa fa-graduation-cap fa-3x"></i>
        <div class="sceh-card-title">My Competencies</div>
        <div class="sceh-stat">
            <div class="sceh-stat-value">12</div>
            <div class="sceh-stat-label">Completed</div>
        </div>
    </a>
</div>
```

---

#### Method: `metric(array $config): string`

Renders a metric card with value, trend, and status indicator.

**Parameters**:
```php
$config = [
    'title' => string,        // Card title (REQUIRED)
    'value' => string,        // Metric value (REQUIRED)
    'icon' => string,         // Font Awesome icon (default: 'fa-chart-bar')
    'trend' => string,        // Trend indicator (optional)
    'details' => string,      // Status details (optional)
    'status' => string,       // Status: 'success', 'warning', 'danger', 'info' (default: 'info')
    'url' => moodle_url|string, // Link URL (optional)
    'size' => string,         // Size: 'small', 'medium', 'large' (default: 'small')
];
```

**Returns**: `string` - HTML markup

**Example**:
```php
$html = sceh_card::metric([
    'title' => 'Active Users',
    'value' => '245',
    'icon' => 'fa-users',
    'trend' => '+12% this week',
    'status' => 'success',
    'details' => 'All systems operational',
]);
```

---

#### Method: `list(array $config): string`

Renders a list card with items and optional footer actions.

**Parameters**:
```php
$config = [
    'title' => string,        // Card title (REQUIRED)
    'icon' => string,         // Font Awesome icon (default: 'fa-list')
    'count' => string,        // Item count badge (optional)
    'items' => array,         // List items (REQUIRED)
    'footer_actions' => array, // Footer buttons (optional)
    'badges' => array,        // Header badges (optional)
    'status' => string,       // Status: 'success', 'warning', 'danger', 'info' (default: 'info')
    'status_text' => string,  // Status text (optional)
    'size' => string,         // Size: 'small', 'medium', 'large' (default: 'medium')
];

// Item structure
$items = [
    [
        'text' => string,     // Item text (REQUIRED)
        'url' => moodle_url|string, // Item link (optional)
        'icon' => string,     // Item icon (optional)
        'badge' => string,    // Item badge (optional)
        'meta' => string,     // Item metadata (optional)
    ],
];

// Footer action structure
$footer_actions = [
    [
        'text' => string,     // Button text (REQUIRED)
        'url' => moodle_url|string, // Button URL (REQUIRED)
        'style' => string,    // Button style: 'primary', 'secondary' (default: 'secondary')
        'attributes' => array, // Additional HTML attributes (optional)
    ],
];
```

**Returns**: `string` - HTML markup

**Example**:
```php
$html = sceh_card::list([
    'title' => 'Upcoming Deadlines',
    'icon' => 'fa-calendar',
    'count' => '5',
    'items' => [
        [
            'text' => 'Quiz 1: Anatomy Basics',
            'url' => new moodle_url('/mod/quiz/view.php', ['id' => 123]),
            'badge' => 'Due Today',
            'meta' => 'Week 1',
        ],
        [
            'text' => 'Assignment: Case Study',
            'url' => new moodle_url('/mod/assign/view.php', ['id' => 456]),
            'badge' => 'Due Tomorrow',
            'meta' => 'Week 2',
        ],
    ],
    'footer_actions' => [
        [
            'text' => 'View All',
            'url' => new moodle_url('/calendar/view.php'),
            'style' => 'primary',
        ],
    ],
    'status' => 'warning',
    'status_text' => '2 items overdue',
]);
```

---

#### Method: `detail(array $config): string`

Renders a detailed card with stats, sections, and actions.

**Parameters**:
```php
$config = [
    'title' => string,        // Card title (REQUIRED)
    'subtitle' => string,     // Card subtitle (optional)
    'icon' => string,         // Font Awesome icon (default: 'fa-info-circle')
    'badges' => array,        // Header badges (optional)
    'stats' => array,         // Stat grid (optional)
    'sections' => array,      // Content sections (optional)
    'actions' => array,       // Action buttons (optional)
    'status' => string,       // Status: 'success', 'warning', 'danger', 'info' (default: 'info')
    'size' => string,         // Size: 'small', 'medium', 'large' (default: 'large')
];

// Stat structure
$stats = [
    [
        'value' => string,    // Stat value (REQUIRED)
        'label' => string,    // Stat label (REQUIRED)
    ],
];

// Section structure
$sections = [
    [
        'title' => string,    // Section title (optional)
        'content' => string,  // Section content (REQUIRED)
    ],
];
```

**Returns**: `string` - HTML markup

**Example**:
```php
$html = sceh_card::detail([
    'title' => 'Course Overview',
    'subtitle' => 'Allied Health Foundational Course',
    'icon' => 'fa-book',
    'badges' => ['Active', 'Week 3'],
    'stats' => [
        ['value' => '24', 'label' => 'Enrolled'],
        ['value' => '18', 'label' => 'Active'],
        ['value' => '75%', 'label' => 'Completion'],
    ],
    'sections' => [
        [
            'title' => 'Description',
            'content' => 'Foundational course for allied health professionals...',
        ],
        [
            'title' => 'Schedule',
            'content' => 'Mondays and Wednesdays, 9:00 AM - 12:00 PM',
        ],
    ],
    'actions' => [
        [
            'text' => 'View Course',
            'url' => new moodle_url('/course/view.php', ['id' => $courseid]),
            'style' => 'primary',
        ],
    ],
]);
```

---

### Helper Methods

#### `render_icon(string $icon, string $size = '2x'): string`

Renders a Font Awesome icon.

**Parameters**:
- `$icon` - Font Awesome class (e.g., 'fa-user')
- `$size` - Icon size: 'lg', '2x', '3x', '4x', '5x' (default: '2x')

**Returns**: `string` - HTML markup

**Example**:
```php
$html = sceh_card::render_icon('fa-graduation-cap', '3x');
// Output: <i class="fa fa-graduation-cap fa-3x"></i>
```

---

#### `render_stat(string $value, string $label): string`

Renders a stat display (value + label).

**Parameters**:
- `$value` - Stat value
- `$label` - Stat label

**Returns**: `string` - HTML markup

**Example**:
```php
$html = sceh_card::render_stat('24', 'Enrolled');
```

---

#### `status_indicator(string $status, string $text): string`

Renders a status indicator with icon and text.

**Parameters**:
- `$status` - Status type: 'success', 'warning', 'danger', 'info'
- `$text` - Status text

**Returns**: `string` - HTML markup

**Example**:
```php
$html = sceh_card::status_indicator('warning', '2 items need attention');
```

---

## config_helper Functions

**Location**: `scripts/lib/config_helper.php`

**Purpose**: Environment-agnostic config loading for CLI scripts

### Function: `require_moodle_config(): string`

Finds and loads Moodle config.php across different environments.

**Parameters**: None

**Returns**: `string` - Path to config.php that was loaded

**Throws**: `Exception` if config.php not found

**Supported Paths**:
- `__DIR__ . '/../../config.php'` (MoodleHQ Docker)
- `/bitnami/moodle/config.php` (Azure/Bitnami)
- `/opt/bitnami/moodle/config.php` (Alternative Bitnami)

**Example**:
```php
<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../lib/config_helper.php');

try {
    $config_path = require_moodle_config();
    echo "Loaded config from: {$config_path}\n";
} catch (Exception $e) {
    fwrite(STDERR, "ERROR: {$e->getMessage()}\n");
    exit(1);
}
```

---

### Function: `init_cli_admin(string $capability = 'moodle/site:config'): stdClass`

Initializes CLI script with admin user context and capability check.

**Parameters**:
- `$capability` - Capability to check (default: 'moodle/site:config')
  - Pass `null` to skip capability check

**Returns**: `stdClass` - Admin user object

**Throws**: `Exception` if no admin user found or capability check fails

**Example**:
```php
<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../lib/config_helper.php');
require_moodle_config();

global $DB;

// Initialize with default capability (moodle/site:config)
$admin = init_cli_admin();

// Initialize with custom capability
$admin = init_cli_admin('moodle/course:create');

// Initialize without capability check
$admin = init_cli_admin(null);

// Now you can use $DB and other Moodle APIs
$users = $DB->get_records('user', ['deleted' => 0]);
```

---

## Dashboard Block API

**Location**: `block_sceh_dashboard/block_sceh_dashboard.php`

**Purpose**: Role-based dashboard rendering

### Class: `block_sceh_dashboard`

#### Method: `get_content(): stdClass`

Main entry point for block rendering. Automatically detects user role and renders appropriate dashboard.

**Returns**: `stdClass` with properties:
- `text` - HTML content
- `footer` - Footer HTML (optional)

**Role Detection Order**:
1. System Admin (`local/sceh_rules:systemadmin`)
2. Trainer (`local/sceh_rules:trainer`)
3. Program Owner (`local/sceh_rules:programowner` or category-level assignment)
4. Learner (default)

**Example**:
```php
// In block class
public function get_content() {
    if ($this->content !== null) {
        return $this->content;
    }
    
    $this->content = new stdClass();
    $this->content->text = '';
    
    $context = context_system::instance();
    
    if (has_capability('local/sceh_rules:systemadmin', $context)) {
        $this->content->text = $this->render_sysadmin_dashboard();
    } else if (has_capability('local/sceh_rules:trainer', $context)) {
        $this->content->text = $this->render_trainer_dashboard();
    } else if ($this->is_program_owner_user($USER->id)) {
        $this->content->text = $this->render_program_owner_dashboard();
    } else {
        $this->content->text = $this->render_learner_dashboard();
    }
    
    return $this->content;
}
```

---

#### Method: `is_program_owner_user(int $userid): bool`

Checks if user should see Program Owner dashboard.

**Parameters**:
- `$userid` - User ID to check

**Returns**: `bool` - True if user is Program Owner

**Logic**:
1. Returns false if user is System Admin
2. Returns false if user is Trainer
3. Returns true if user has `local/sceh_rules:programowner` capability
4. Returns true if user has category-level Program Owner role assignment

**Example**:
```php
if ($this->is_program_owner_user($USER->id)) {
    // Show Program Owner dashboard
}
```

---

#### Method: `get_program_owner_categories(int $userid): array`

Gets list of categories where user has Program Owner role.

**Parameters**:
- `$userid` - User ID

**Returns**: `array` - Array of category objects with properties:
- `id` - Category ID
- `name` - Category name
- `idnumber` - Category ID number

**Example**:
```php
$categories = $this->get_program_owner_categories($USER->id);
foreach ($categories as $cat) {
    echo "Category: {$cat->name} (ID: {$cat->id})\n";
}
```

---

## Role Helper Functions

**Location**: Various (block_sceh_dashboard, local_sceh_rules)

### Function: `has_capability(string $capability, context $context, int $userid = null): bool`

Checks if user has a specific capability in a context.

**Parameters**:
- `$capability` - Capability name (e.g., 'local/sceh_rules:trainer')
- `$context` - Context object
- `$userid` - User ID (default: current user)

**Returns**: `bool` - True if user has capability

**Example**:
```php
$context = context_system::instance();

if (has_capability('local/sceh_rules:systemadmin', $context)) {
    // User is system admin
}

if (has_capability('local/sceh_rules:trainer', $context, $userid)) {
    // Specific user is trainer
}
```

---

### Function: `require_capability(string $capability, context $context, int $userid = null)`

Requires user to have capability, throws exception if not.

**Parameters**:
- `$capability` - Capability name
- `$context` - Context object
- `$userid` - User ID (default: current user)

**Throws**: `required_capability_exception` if user lacks capability

**Example**:
```php
$context = context_system::instance();

// Throws exception if user is not system admin
require_capability('local/sceh_rules:systemadmin', $context);

// Continue with admin-only code
```

---

### Function: `role_assign(int $roleid, int $userid, int $contextid): int`

Assigns a role to a user in a context.

**Parameters**:
- `$roleid` - Role ID
- `$userid` - User ID
- `$contextid` - Context ID

**Returns**: `int` - Role assignment ID

**Example**:
```php
$role = $DB->get_record('role', ['shortname' => 'sceh_trainer'], '*', MUST_EXIST);
$user = $DB->get_record('user', ['username' => 'mock.trainer'], '*', MUST_EXIST);
$context = context_system::instance();

$raid = role_assign($role->id, $user->id, $context->id);
echo "Role assigned: {$raid}\n";
```

---

### Function: `assign_capability(string $capability, int $permission, int $roleid, int $contextid, bool $overwrite = false)`

Assigns a capability to a role in a context.

**Parameters**:
- `$capability` - Capability name
- `$permission` - Permission: `CAP_ALLOW`, `CAP_PREVENT`, `CAP_PROHIBIT`, `CAP_INHERIT`
- `$roleid` - Role ID
- `$contextid` - Context ID
- `$overwrite` - Whether to overwrite existing assignment (default: false)

**Example**:
```php
$role = $DB->get_record('role', ['shortname' => 'sceh_trainer'], '*', MUST_EXIST);
$context = context_system::instance();

// Grant capability
assign_capability('moodle/course:activityvisibility', CAP_ALLOW, $role->id, $context->id, true);

// Prevent capability
assign_capability('moodle/course:delete', CAP_PREVENT, $role->id, $context->id, true);
```

---

## Stream Management API

**Location**: `local_sceh_rules/lib.php`

### Function: `local_sceh_rules_get_user_stream(int $userid): ?stdClass`

Gets the stream (cohort) assigned to a user.

**Parameters**:
- `$userid` - User ID

**Returns**: `stdClass|null` - Stream object or null if not assigned

**Stream Object Properties**:
- `id` - Cohort ID
- `name` - Stream name
- `idnumber` - Stream ID number
- `description` - Stream description

**Example**:
```php
$stream = local_sceh_rules_get_user_stream($USER->id);

if ($stream) {
    echo "Your stream: {$stream->name}\n";
} else {
    echo "No stream assigned\n";
}
```

---

### Function: `local_sceh_rules_get_stream_courses(int $cohortid): array`

Gets all courses associated with a stream.

**Parameters**:
- `$cohortid` - Cohort/stream ID

**Returns**: `array` - Array of course objects

**Course Object Properties**:
- `id` - Course ID
- `fullname` - Course full name
- `shortname` - Course short name
- `visible` - Visibility (0 or 1)

**Example**:
```php
$stream = local_sceh_rules_get_user_stream($USER->id);

if ($stream) {
    $courses = local_sceh_rules_get_stream_courses($stream->id);
    
    foreach ($courses as $course) {
        echo "Course: {$course->fullname}\n";
    }
}
```

---

## Importer API

**Location**: `local_sceh_importer/classes/importer.php`

### Class: `local_sceh_importer\importer`

#### Method: `queue_import(string $filepath, int $categoryid, int $userid): int`

Queues a course package for asynchronous import.

**Parameters**:
- `$filepath` - Path to .mbz package file
- `$categoryid` - Target category ID
- `$userid` - User ID initiating import

**Returns**: `int` - Job ID for tracking

**Example**:
```php
use local_sceh_importer\importer;

$importer = new importer();
$jobid = $importer->queue_import('/path/to/package.mbz', $categoryid, $USER->id);

echo "Import queued: Job ID {$jobid}\n";
```

---

#### Method: `get_job_status(int $jobid): stdClass`

Gets the status of an import job.

**Parameters**:
- `$jobid` - Job ID

**Returns**: `stdClass` - Job status object

**Status Object Properties**:
- `id` - Job ID
- `status` - Status: 'queued', 'running', 'completed', 'failed'
- `progress` - Progress percentage (0-100)
- `message` - Status message
- `courseid` - Created course ID (if completed)
- `timecreated` - Job creation timestamp
- `timemodified` - Last update timestamp

**Example**:
```php
$status = $importer->get_job_status($jobid);

echo "Status: {$status->status}\n";
echo "Progress: {$status->progress}%\n";

if ($status->status === 'completed') {
    echo "Course created: {$status->courseid}\n";
}
```

---

## Custom Capabilities

### System Admin Capabilities

**Capability**: `local/sceh_rules:systemadmin`

**Description**: Full system administration access

**Risk**: `RISK_CONFIG | RISK_DATALOSS`

**Grants**:
- Access to all admin pages
- Manage all courses and users
- Configure system settings
- View all reports

**Check**:
```php
$context = context_system::instance();
if (has_capability('local/sceh_rules:systemadmin', $context)) {
    // System admin access
}
```

---

### Program Owner Capabilities

**Capability**: `local/sceh_rules:programowner`

**Description**: Manage courses and content within assigned categories

**Risk**: `RISK_SPAM | RISK_XSS`

**Grants**:
- Create and edit courses in assigned categories
- Manage course content
- View course reports
- Manage enrollments

**Check**:
```php
$context = context_coursecat::instance($categoryid);
if (has_capability('local/sceh_rules:programowner', $context)) {
    // Program owner access for this category
}
```

---

### Trainer Capabilities

**Capability**: `local/sceh_rules:trainer`

**Description**: Teach courses and manage learner progress

**Risk**: `RISK_SPAM`

**Grants**:
- View assigned courses
- Grade assignments and quizzes
- Manage attendance
- View learner progress
- Show/hide activities

**Check**:
```php
$context = context_course::instance($courseid);
if (has_capability('local/sceh_rules:trainer', $context)) {
    // Trainer access for this course
}
```

---

### Learner Capabilities

**Capability**: `local/sceh_rules:learner`

**Description**: Access learning content and track progress

**Risk**: None

**Grants**:
- View enrolled courses
- Submit assignments
- Take quizzes
- View own progress
- View own competencies

**Check**:
```php
$context = context_course::instance($courseid);
if (has_capability('local/sceh_rules:learner', $context)) {
    // Learner access for this course
}
```

---

## Usage Examples

### Example 1: Custom Page with Role-Based Content

```php
<?php
require_once('../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/sceh_rules/mypage.php'));
$PAGE->set_title('My Page');

echo $OUTPUT->header();

if (has_capability('local/sceh_rules:systemadmin', $context)) {
    // Admin view
    echo sceh_card::simple([
        'title' => 'System Administration',
        'icon' => 'fa-cog',
        'color' => 'red',
        'url' => new moodle_url('/admin/index.php'),
    ]);
} else if (has_capability('local/sceh_rules:trainer', $context)) {
    // Trainer view
    echo sceh_card::simple([
        'title' => 'My Courses',
        'icon' => 'fa-book',
        'color' => 'blue',
        'url' => new moodle_url('/my/courses.php'),
    ]);
} else {
    // Learner view
    echo sceh_card::simple([
        'title' => 'My Progress',
        'icon' => 'fa-chart-line',
        'color' => 'green',
        'url' => new moodle_url('/local/sceh_rules/stream_progress.php'),
    ]);
}

echo $OUTPUT->footer();
```

---

### Example 2: Configuration Script with Verification

```php
<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../lib/config_helper.php');
require_moodle_config();

global $DB;

init_cli_admin('moodle/site:config');

$dryrun = in_array('--dry-run', $argv);

echo "=== Configure Custom Feature ===\n";
echo "MODE\t" . ($dryrun ? 'DRY-RUN' : 'APPLY') . "\n\n";

// Get role
$role = $DB->get_record('role', ['shortname' => 'sceh_trainer'], '*', IGNORE_MISSING);
if (!$role) {
    fwrite(STDERR, "ERROR: sceh_trainer role not found\n");
    exit(1);
}

// Check current state
$context = context_system::instance();
$has_cap = has_capability('moodle/course:activityvisibility', $context, null, false);

echo "CURRENT\t" . ($has_cap ? 'CONFIGURED' : 'NOT_CONFIGURED') . "\n";

// Apply change
if (!$dryrun && !$has_cap) {
    assign_capability('moodle/course:activityvisibility', CAP_ALLOW, $role->id, $context->id, true);
    echo "APPLY\tCapability granted\n";
} else {
    echo "SKIP\tNo changes needed\n";
}

echo "\nDONE\n";
```

---

## Related Documentation

- [PLUGIN_DEVELOPMENT_GUIDE.md](./PLUGIN_DEVELOPMENT_GUIDE.md) - Plugin development patterns
- [SCRIPT_PATTERNS.md](./SCRIPT_PATTERNS.md) - Script development patterns
- [DATABASE_SCHEMA.md](./DATABASE_SCHEMA.md) - Database schema reference
