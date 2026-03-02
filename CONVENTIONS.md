# Coding Conventions — SCEH Moodle Fellowship System

Project-specific coding standards. For behavioral rules (how AI assistants should think and act), see `AGENTS.md`.

---

## Tech Stack

- **Platform:** Moodle 5.0.1 (Build: 20250609)
- **Backend:** PHP (Bitnami Moodle image)
- **Database:** MySQL 8.4
- **Web Server:** Apache 2.4.64
- **Deployment:** Docker containers (Bitnami images)
- **Frontend:** Moodle templates + JavaScript (AMD modules)

---

## Code Style

### PHP (Moodle Plugins)
```php
// Follow Moodle coding standards
class block_sceh_dashboard extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_sceh_dashboard');
    }
    
    public function get_content() {
        global $CFG, $USER, $DB;
        // Implementation
    }
}
```
- Follow [Moodle Coding Style](https://moodledev.io/general/development/policies/codingstyle)
- Use `global $CFG, $USER, $DB` for Moodle globals
- Prefix all database tables with `mdl_`
- Use Moodle's `$DB` object for database queries
- Always use `required_param()` and `optional_param()` for input
- Use `get_string()` for all user-facing text (language strings)

### JavaScript (AMD Modules)
```javascript
// AMD module pattern for Moodle
define(['jquery', 'core/ajax'], function($, Ajax) {
    return {
        init: function() {
            // Implementation
        }
    };
});
```
- Use AMD module pattern (RequireJS)
- Place in `amd/src/` directory
- Build with `grunt amd` (generates minified versions)

### Naming
- **Classes:** PascalCase with plugin prefix (`block_sceh_dashboard`, `local_kirkpatrick_dashboard`)
- **Functions:** snake_case (`get_content`, `calculate_roi`)
- **Files:** snake_case (`attendance_rules.php`, `roster_observer.php`)
- **Database tables:** snake_case with `mdl_` prefix (`mdl_local_sceh_rules_attendance`)
- **Language strings:** lowercase with underscores (`pluginname`, `attendance_rule`)

---
---

## Project Structure

```
moodle-exploration/
├── block_sceh_dashboard/          # Dashboard block plugin
│   ├── block_sceh_dashboard.php  # Main block class
│   ├── version.php               # Plugin metadata
│   ├── styles.css                # Custom styling
│   ├── lang/en/                  # Language strings
│   └── db/access.php             # Capability definitions
│
├── local_kirkpatrick_dashboard/   # Kirkpatrick evaluation dashboard
│   ├── index.php                 # Main dashboard page
│   ├── amd/src/dashboard.js      # JavaScript for charts
│   ├── lang/en/                  # Language strings
│   └── db/access.php             # Permissions
│
├── local_kirkpatrick_level4/      # Level 4 ROI tracking
│   ├── classes/task/             # Scheduled tasks
│   ├── db/install.xml            # Database schema
│   └── db/tasks.php              # Task definitions
│
├── local_sceh_rules/              # Rules engine
│   ├── attendance_rules.php      # Attendance rule UI
│   ├── roster_rules.php          # Roster rule UI
│   ├── classes/rules/            # Rule logic
│   ├── classes/observer/         # Event observers
│   ├── classes/engine/           # Rule evaluation
│   ├── classes/form/             # Form definitions
│   ├── db/install.xml            # Database tables
│   ├── db/events.php             # Event subscriptions
│   └── tests/                    # PHPUnit tests
│
├── database_templates/            # Database activity templates
│   ├── case_logbook_template.xml
│   ├── credentialing_sheet_template.xml
│   └── research_publications_template.xml
│
├── plugin-source/                 # Third-party plugin zips
│   ├── block_configurable_reports_*.zip
│   └── block_stash_*.zip
│
├── configure_*.php                # Configuration scripts (30+)
├── verify_*.php                   # Verification scripts (25+)
├── property_test_*.php            # Property-based tests (12)
├── test_*_integration.php         # Integration tests (5)
├── docker-compose.yml             # Docker configuration
└── *.md                           # Documentation (30+)
```

---

## Moodle Plugin Conventions

### Plugin Types
- **block_**: Block plugins (appear in sidebars/pages)
- **local_**: Local plugins (custom functionality)
- **mod_**: Activity modules (course activities)
- **theme_**: Themes (appearance)

### Required Files
Every plugin must have:
- `version.php` - Plugin metadata (version, requires, component)
- `lang/en/[pluginname].php` - English language strings
- `db/access.php` - Capability definitions (if needed)
- `db/install.xml` - Database schema (if needed)

### Version File Format
```php
$plugin->component = 'block_sceh_dashboard';
$plugin->version = 2026020300;  // YYYYMMDDXX format
$plugin->requires = 2024100700; // Minimum Moodle version
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.1';
```

### Language Strings
```php
// lang/en/block_sceh_dashboard.php
$string['pluginname'] = 'SCEH Dashboard';
$string['attendance_rule'] = 'Attendance Rule';
```

### Capabilities
```php
// db/access.php
$capabilities = [
    'block/sceh_dashboard:addinstance' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => ['manager' => CAP_ALLOW]
    ]
];
```

---

## Database Conventions

### Table Naming
- Prefix: `mdl_` (Moodle standard)
- Plugin tables: `mdl_[plugintype]_[pluginname]_[tablename]`
- Example: `mdl_local_sceh_rules_attendance`

### Schema Definition (install.xml)
```xml
<TABLE NAME="local_sceh_rules_attendance">
  <FIELDS>
    <FIELD NAME="id" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="true"/>
    <FIELD NAME="name" TYPE="char" LENGTH="255" NOTNULL="true"/>
    <FIELD NAME="timecreated" TYPE="int" LENGTH="10" NOTNULL="true"/>
  </FIELDS>
  <KEYS>
    <KEY NAME="primary" TYPE="primary" FIELDS="id"/>
  </KEYS>
</TABLE>
```

### Database Queries
```php
// Use Moodle's $DB object
global $DB;

// Select
$record = $DB->get_record('user', ['id' => $userid]);
$records = $DB->get_records('course', ['visible' => 1]);

// Insert
$recordid = $DB->insert_record('tablename', $dataobject);

// Update
$DB->update_record('tablename', $dataobject);

// Delete
$DB->delete_records('tablename', ['id' => $id]);
```

---

## Configuration Scripts

### Script Patterns
All configuration scripts follow these patterns:

1. **Docker/Bitnami Path Detection**
```php
// Auto-detect Moodle config path
$config_paths = [
    '/bitnami/moodle/config.php',  // Docker/Bitnami
    __DIR__ . '/config.php'         // Standard
];
```

2. **CLI Admin User Setup**
```php
// Prevent email sending in CLI
$CFG->noemailever = true;

// Get admin user for CLI context
$admin = get_admin();
if (!$admin) {
    die("Error: No admin user found\n");
}
```

3. **Error Handling**
```php
try {
    // Operation
    echo "✓ Success\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
```

### Script Types
- `configure_*.php` - Set up features
- `verify_*.php` - Validate configurations
- `property_test_*.php` - Test invariants
- `test_*_integration.php` - End-to-end tests
- `create_*.php` - Create resources
- `fix_*.php` - Troubleshooting/repairs

---

## Testing

### PHPUnit Tests
```php
// tests/attendance_rule_test.php
class attendance_rule_test extends advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest(true);
    }
    
    public function test_rule_creation() {
        // Test implementation
    }
}
```

### Running Tests
```bash
# Inside Docker container
docker exec moodle-exploration-moodle-1 php admin/tool/phpunit/cli/init.php
docker exec moodle-exploration-moodle-1 vendor/bin/phpunit local/sceh_rules/tests/

# Property tests (from host)
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/property_test_*.php
```

### Integration Tests
```bash
# Run integration tests
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/test_competency_integration.php
```

---

## Docker Operations

### Common Commands
```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker-compose logs -f moodle

# Execute commands in container
docker exec moodle-exploration-moodle-1 [command]

# Fix permissions (recurring issue)
docker exec moodle-exploration-moodle-1 chown -R daemon:daemon /bitnami/moodledata
docker exec moodle-exploration-moodle-1 chmod -R 755 /bitnami/moodledata

# Purge caches
docker exec moodle-exploration-moodle-1 php admin/cli/purge_caches.php
```

### File Locations in Docker
- Moodle root: `/bitnami/moodle/`
- Moodle data: `/bitnami/moodledata/`
- Config: `/bitnami/moodle/config.php`
- Plugins: `/bitnami/moodle/[plugintype]/[pluginname]/`

---

## Known Issues & Workarounds

### 1. Docker Permissions
**Issue**: "Invalid permissions detected when trying to create a directory"
**Fix**: Run after cache purges or container restarts
```bash
docker exec moodle-exploration-moodle-1 chown -R daemon:daemon /bitnami/moodledata
docker exec moodle-exploration-moodle-1 chmod -R 755 /bitnami/moodledata
```

### 2. Plugin Name Corrections
- `block_level_up` → `block_xp` (correct plugin name)
- `local_stash` → `block_stash` (correct plugin type)
- `mod_portfolio` → `core_portfolio` (core feature, not plugin)

### 3. Database Table Names
- `competency_plan_template` → `competency_template` (correct table name)

---

## Documentation Standards

### File Naming
- User guides: `[FEATURE]_GUIDE.md` (e.g., `QUICK_START_GUIDE.md`)
- Technical docs: `[FEATURE]_[TYPE].md` (e.g., `DASHBOARD_INSTALLATION_SUCCESS.md`)
- Task reports: `TASK_[NUMBER]_COMPLETION_REPORT.md`
- Checkpoints: `CHECKPOINT_[NUMBER]_[TYPE].md`

### Documentation Location
- Plugin docs: Inside plugin directory (`README.md`)
- Database templates: `database_templates/` directory
- User guides: Project root
- Technical specs: `.kiro/specs/` directory

---

## Quick Reference

```bash
# Moodle CLI
docker exec moodle-exploration-moodle-1 php admin/cli/purge_caches.php
docker exec moodle-exploration-moodle-1 php admin/cli/upgrade.php
docker exec moodle-exploration-moodle-1 php admin/cli/install_database.php

# Plugin Installation
docker exec moodle-exploration-moodle-1 php admin/cli/uninstall_plugins.php
# Then upload plugin and visit /admin/ to install

# Database
docker exec moodle-exploration-mariadb-1 mysql -u root -p moodle

# Access URLs
# Homepage: http://localhost:8080
# Dashboard: http://localhost:8080/my/
# Admin: http://localhost:8080/admin/
```
