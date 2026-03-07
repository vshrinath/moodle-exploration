# Plugin Development Guide — Custom Moodle Plugins

**Authoritative guide for creating and modifying custom plugins**

This document defines mandatory patterns for all custom plugins in this repository. Following these patterns ensures plugins are maintainable, upgradeable, and compatible with Moodle core updates.

---

## Table of Contents

1. [Plugin Types](#plugin-types)
2. [Directory Structure](#directory-structure)
3. [Required Files](#required-files)
4. [Naming Conventions](#naming-conventions)
5. [Version Management](#version-management)
6. [Language Strings](#language-strings)
7. [Database Schema](#database-schema)
8. [Capabilities](#capabilities)
9. [Renderer Patterns](#renderer-patterns)
10. [Anti-Patterns](#anti-patterns)
11. [Examples](#examples)

---

## Plugin Types

### Custom Plugins in This Repository

| Plugin Type | Directory | Purpose | Examples |
|-------------|-----------|---------|----------|
| Block | `block_<name>/` | Dashboard widgets | `block_sceh_dashboard` |
| Local | `local_<name>/` | Custom functionality | `local_sceh_rules`, `local_sceh_importer` |
| Theme | `theme_<name>/` | Visual customization | `theme_sceh` |
| Activity Module | `mod/<name>/` | Third-party activities | `mod/attendance`, `mod/questionnaire` |

### When to Create Each Type

**Block Plugin** - Use when:
- Creating dashboard cards or widgets
- Displaying role-specific information
- Adding navigation shortcuts

**Local Plugin** - Use when:
- Adding custom pages or workflows
- Implementing business logic
- Creating reusable libraries
- Adding custom capabilities

**Theme Plugin** - Use when:
- Customizing visual appearance
- Modifying layouts
- Adding custom CSS/SCSS

**Activity Module** - Use when:
- Creating gradeable activities
- Adding course content types
- (Usually third-party, not custom)

---

## Directory Structure

### Block Plugin Structure

```
block_sceh_dashboard/
├── block_sceh_dashboard.php    # Main block class (REQUIRED)
├── version.php                  # Version info (REQUIRED)
├── lang/                        # Language strings (REQUIRED)
│   └── en/
│       └── block_sceh_dashboard.php
├── db/                          # Database definitions
│   ├── access.php              # Capabilities
│   └── upgrade.php             # Schema upgrades
├── classes/                     # Autoloaded classes
│   └── output/
│       └── renderer.php
├── styles.css                   # Block-specific CSS
├── amd/                         # JavaScript modules
│   └── src/
│       └── module.js
└── README.md                    # Documentation
```

### Local Plugin Structure

```
local_sceh_rules/
├── version.php                  # Version info (REQUIRED)
├── lang/                        # Language strings (REQUIRED)
│   └── en/
│       └── local_sceh_rules.php
├── db/                          # Database definitions
│   ├── access.php              # Capabilities
│   └── upgrade.php             # Schema upgrades
├── classes/                     # Autoloaded classes
│   ├── output/
│   │   ├── sceh_card.php       # Reusable renderers
│   │   └── renderer.php
│   └── privacy/
│       └── provider.php        # GDPR compliance
├── lib.php                      # Plugin functions
├── <page>.php                   # Custom pages
├── styles/                      # CSS files
│   └── sceh_card_system.css
└── README.md                    # Documentation
```

### Theme Plugin Structure

```
theme_sceh/
├── config.php                   # Theme config (REQUIRED)
├── version.php                  # Version info (REQUIRED)
├── lang/                        # Language strings (REQUIRED)
│   └── en/
│       └── theme_sceh.php
├── scss/                        # SCSS files
│   ├── _variables.scss
│   └── _custom.scss
├── lib.php                      # Theme functions
├── layout/                      # Layout templates
│   └── login.php
├── templates/                   # Mustache templates
└── README.md                    # Documentation
```

---

## Required Files

### 1. version.php (ALL PLUGINS)

**Purpose**: Defines plugin metadata and version

**Template**:
```php
<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = '<type>_<name>';     // e.g., 'block_sceh_dashboard'
$plugin->version = YYYYMMDDXX;            // e.g., 2026030700
$plugin->requires = 2022041900;           // Moodle 4.0+
$plugin->maturity = MATURITY_STABLE;      // or MATURITY_BETA
$plugin->release = 'X.Y.Z';               // e.g., '1.0.0'
$plugin->dependencies = [                 // Optional
    'mod_attendance' => ANY_VERSION,
];
```

**Critical Rules**:
- ALWAYS increment version on ANY code change
- ALWAYS use format YYYYMMDDXX (year, month, day, sequence)
- ALWAYS set `requires` to minimum Moodle version
- NEVER decrease version number

**Version Increment Examples**:
```php
// First release on March 7, 2026
$plugin->version = 2026030700;

// Second release same day
$plugin->version = 2026030701;

// Bug fix next day
$plugin->version = 2026030800;
```

### 2. Language Strings (ALL PLUGINS)

**Location**: `lang/en/<component>.php`

**Template**:
```php
<?php
defined('MOODLE_INTERNAL') || die();

// Plugin name (REQUIRED)
$string['pluginname'] = 'Human Readable Name';

// Capabilities (if defined in db/access.php)
$string['<component>:capability'] = 'Capability description';

// Custom strings
$string['customkey'] = 'Custom value';
$string['customkey_help'] = 'Help text for customkey';
```

**Critical Rules**:
- ALWAYS define `pluginname` string
- ALWAYS use lowercase keys with underscores
- ALWAYS add `_help` suffix for help text
- NEVER hardcode user-facing text in PHP

**String Usage**:
```php
// In PHP code
$title = get_string('pluginname', 'block_sceh_dashboard');

// In templates (Mustache)
{{#str}}pluginname, block_sceh_dashboard{{/str}}
```

### 3. Block Class (BLOCK PLUGINS ONLY)

**Location**: `block_<name>.php`

**Template**:
```php
<?php
defined('MOODLE_INTERNAL') || die();

class block_<name> extends block_base {
    
    public function init() {
        $this->title = get_string('pluginname', 'block_<name>');
    }
    
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }
        
        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';
        
        // Add your content here
        $this->content->text = $this->render_dashboard();
        
        return $this->content;
    }
    
    public function applicable_formats() {
        return [
            'site' => true,
            'course' => false,
            'my' => true,
        ];
    }
    
    public function has_config() {
        return false;
    }
    
    private function render_dashboard() {
        // Implementation
        return '';
    }
}
```

**Critical Rules**:
- ALWAYS extend `block_base`
- ALWAYS implement `init()` and `get_content()`
- ALWAYS check `$this->content !== null` in `get_content()`
- ALWAYS use `get_string()` for title

---

## Naming Conventions

### Plugin Component Names

**Format**: `<type>_<name>`

**Rules**:
- Use lowercase only
- Use underscores to separate words
- Prefix with plugin type
- Keep names short but descriptive

**Examples**:
```
block_sceh_dashboard       ✓ Correct
local_sceh_rules           ✓ Correct
theme_sceh                 ✓ Correct
block_SCEHDashboard        ✗ Wrong (uppercase)
block_sceh-dashboard       ✗ Wrong (hyphen)
sceh_dashboard             ✗ Wrong (missing type prefix)
```

### Class Names

**Format**: `<component>_<class_name>`

**Rules**:
- Use lowercase with underscores
- Prefix with component name
- Use descriptive names

**Examples**:
```php
// Correct
class block_sceh_dashboard extends block_base {}
class local_sceh_rules_output_sceh_card {}

// Wrong
class SCEHDashboard extends block_base {}
class sceh_card {}
```

### Function Names

**Format**: `<component>_<function_name>`

**Rules**:
- Use lowercase with underscores
- Prefix with component name (for global functions)
- Use verb-noun pattern

**Examples**:
```php
// Correct
function local_sceh_rules_get_user_stream($userid) {}
function theme_sceh_get_main_scss_content($theme) {}

// Wrong
function getUserStream($userid) {}
function get_stream($userid) {}
```

### Database Table Names

**Format**: `<component>_<table_name>`

**Rules**:
- Use lowercase with underscores
- Prefix with component name
- Use plural nouns for tables

**Examples**:
```
local_sceh_rules_streams       ✓ Correct
local_sceh_rules_enrollments   ✓ Correct
sceh_streams                   ✗ Wrong (missing component)
local_sceh_rules_stream        ✗ Wrong (singular)
```

---

## Version Management

### Version Number Format

**Format**: `YYYYMMDDXX`

- `YYYY` = 4-digit year
- `MM` = 2-digit month (01-12)
- `DD` = 2-digit day (01-31)
- `XX` = 2-digit sequence (00-99)

**Examples**:
```php
2026030700  // March 7, 2026, first release
2026030701  // March 7, 2026, second release
2026030800  // March 8, 2026, first release
```

### When to Increment Version

**ALWAYS increment when**:
- Adding new features
- Fixing bugs
- Changing database schema
- Modifying language strings
- Updating CSS/JavaScript
- Changing capabilities

**Increment sequence (XX) when**:
- Multiple releases same day
- Hot fixes
- Minor tweaks

**Increment date when**:
- New day
- Major releases
- Scheduled updates

### Release Version Format

**Format**: `X.Y.Z`

- `X` = Major version (breaking changes)
- `Y` = Minor version (new features)
- `Z` = Patch version (bug fixes)

**Examples**:
```php
$plugin->release = '1.0.0';  // Initial release
$plugin->release = '1.1.0';  // New features
$plugin->release = '1.1.1';  // Bug fix
$plugin->release = '2.0.0';  // Breaking changes
```

---

## Language Strings

### String Key Patterns

**Common Patterns**:
```php
// Plugin name
$string['pluginname'] = 'Plugin Name';

// Capabilities
$string['<component>:capability'] = 'Capability description';

// Page titles
$string['pagetitle'] = 'Page Title';

// Button labels
$string['buttonlabel'] = 'Button Label';

// Help text
$string['key_help'] = 'Help text for key';

// Error messages
$string['error_<type>'] = 'Error message';

// Success messages
$string['success_<type>'] = 'Success message';
```

### Placeholder Syntax

**Single Placeholder**:
```php
$string['yourstream'] = 'Your Stream: {$a}';

// Usage
$text = get_string('yourstream', 'block_sceh_dashboard', $streamname);
```

**Multiple Placeholders**:
```php
$string['enrollmentinfo'] = 'Enrolled {$a->count} users in {$a->cohort}';

// Usage
$data = new stdClass();
$data->count = 25;
$data->cohort = 'Cohort A';
$text = get_string('enrollmentinfo', 'local_sceh_rules', $data);
```

### String Organization

**Group by Feature**:
```php
// Dashboard cards
$string['caselogbook'] = 'Case Logbook';
$string['mycompetencies'] = 'My Competencies';
$string['attendance'] = 'Attendance';

// Workflow queue
$string['workflowqueue'] = 'Workflow Queue';
$string['workflownow'] = 'Do Now';
$string['workflowweek'] = 'This Week';

// Error messages
$string['error_notfound'] = 'Resource not found';
$string['error_permission'] = 'Permission denied';
```

---

## Database Schema

### Schema Definition (db/install.xml)

**Location**: `db/install.xml`

**Template**:
```xml
<?xml version="1.0" encoding="UTF-8" ?>
<XMLDB PATH="<type>/<name>/db" VERSION="YYYYMMDDXX" COMMENT="XMLDB file for <component>">
  <TABLES>
    <TABLE NAME="<component>_<table>" COMMENT="Description">
      <FIELDS>
        <FIELD NAME="id" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="true"/>
        <FIELD NAME="userid" TYPE="int" LENGTH="10" NOTNULL="true" DEFAULT="0"/>
        <FIELD NAME="name" TYPE="char" LENGTH="255" NOTNULL="true"/>
        <FIELD NAME="timecreated" TYPE="int" LENGTH="10" NOTNULL="true" DEFAULT="0"/>
        <FIELD NAME="timemodified" TYPE="int" LENGTH="10" NOTNULL="true" DEFAULT="0"/>
      </FIELDS>
      <KEYS>
        <KEY NAME="primary" TYPE="primary" FIELDS="id"/>
        <KEY NAME="userid" TYPE="foreign" FIELDS="userid" REFTABLE="user" REFFIELDS="id"/>
      </KEYS>
      <INDEXES>
        <INDEX NAME="name" UNIQUE="false" FIELDS="name"/>
      </INDEXES>
    </TABLE>
  </TABLES>
</XMLDB>
```

**Critical Rules**:
- ALWAYS include `id` field as primary key
- ALWAYS include `timecreated` and `timemodified` for audit
- ALWAYS use foreign keys for user/course references
- ALWAYS add indexes for frequently queried fields

### Schema Upgrades (db/upgrade.php)

**Location**: `db/upgrade.php`

**Template**:
```php
<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_<component>_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();
    
    if ($oldversion < 2026030701) {
        // Add new field
        $table = new xmldb_table('<component>_<table>');
        $field = new xmldb_field('newfield', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
        
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        upgrade_plugin_savepoint(true, 2026030701, '<type>', '<name>');
    }
    
    if ($oldversion < 2026030702) {
        // Add new table
        $table = new xmldb_table('<component>_newtable');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        upgrade_plugin_savepoint(true, 2026030702, '<type>', '<name>');
    }
    
    return true;
}
```

**Critical Rules**:
- ALWAYS check if field/table exists before creating
- ALWAYS call `upgrade_plugin_savepoint()` after each change
- ALWAYS use version numbers from `version.php`
- NEVER modify existing data without backup

---

## Capabilities

### Capability Definition (db/access.php)

**Location**: `db/access.php`

**Template**:
```php
<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    '<component>:capability' => [
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/course:manageactivities',
    ],
];
```

**Capability Types**:
- `read` - View-only operations
- `write` - Create/modify operations
- `view` - Display content

**Risk Levels**:
- `RISK_SPAM` - Can send messages
- `RISK_XSS` - Can inject HTML/JS
- `RISK_CONFIG` - Can change site config
- `RISK_DATALOSS` - Can delete data
- `RISK_MANAGETRUST` - Can assign roles

**Context Levels**:
- `CONTEXT_SYSTEM` - Site-wide
- `CONTEXT_COURSECAT` - Category level
- `CONTEXT_COURSE` - Course level
- `CONTEXT_MODULE` - Activity level
- `CONTEXT_USER` - User level

**Critical Rules**:
- ALWAYS define appropriate risk levels
- ALWAYS set correct context level
- ALWAYS use descriptive capability names
- NEVER grant capabilities without risk assessment

### Capability Checking

```php
// Check capability
if (has_capability('local/sceh_rules:systemadmin', $context)) {
    // User has capability
}

// Require capability (throws exception if missing)
require_capability('local/sceh_rules:programowner', $context);

// Check at system level
$sysctx = context_system::instance();
if (has_capability('local/sceh_rules:trainer', $sysctx, $userid)) {
    // User has capability
}
```

---

## Renderer Patterns

### Reusable Renderer Class

**Location**: `classes/output/<renderer_name>.php`

**Template**:
```php
<?php
namespace <component>\output;

defined('MOODLE_INTERNAL') || die();

class <renderer_name> {
    
    /**
     * Render a card component
     *
     * @param array $config Configuration array
     * @return string HTML output
     */
    public static function render_card(array $config): string {
        $title = (string)($config['title'] ?? '');
        $icon = (string)($config['icon'] ?? 'fa-circle');
        $url = $config['url'] ?? '#';
        
        $html = \html_writer::start_div('card');
        $html .= \html_writer::tag('i', '', ['class' => "fa {$icon}"]);
        $html .= \html_writer::tag('h3', $title);
        
        if (!empty($url)) {
            $html = \html_writer::link($url, $html);
        }
        
        $html .= \html_writer::end_div();
        
        return $html;
    }
}
```

**Critical Rules**:
- ALWAYS use `\html_writer` for HTML generation
- ALWAYS escape user input with `s()` or `format_string()`
- ALWAYS validate config parameters
- NEVER echo directly - return strings

### HTML Writer Usage

```php
// Div
$html = \html_writer::div($content, 'classname', ['id' => 'myid']);

// Link
$html = \html_writer::link($url, $text, ['class' => 'btn']);

// Tag
$html = \html_writer::tag('h3', $title, ['class' => 'heading']);

// Start/End
$html = \html_writer::start_div('container');
$html .= $content;
$html .= \html_writer::end_div();
```

---

## Anti-Patterns

### ❌ NEVER: Hardcode Strings

```php
// WRONG
$this->title = 'Fellowship Training Dashboard';

// CORRECT
$this->title = get_string('pluginname', 'block_sceh_dashboard');
```

### ❌ NEVER: Direct Database Access Without Checking

```php
// WRONG
$user = $DB->get_record('user', ['id' => $userid]);
$name = $user->firstname; // May be null!

// CORRECT
$user = $DB->get_record('user', ['id' => $userid], '*', IGNORE_MISSING);
if (!$user) {
    return '';
}
$name = $user->firstname;
```

### ❌ NEVER: Echo in Renderers

```php
// WRONG
public function render_card() {
    echo '<div class="card">';
    echo $this->title;
    echo '</div>';
}

// CORRECT
public function render_card() {
    $html = \html_writer::start_div('card');
    $html .= $this->title;
    $html .= \html_writer::end_div();
    return $html;
}
```

### ❌ NEVER: Skip Version Increment

```php
// WRONG - Same version after code change
$plugin->version = 2026030700;
// ... make code changes ...
$plugin->version = 2026030700; // Still same!

// CORRECT
$plugin->version = 2026030700;
// ... make code changes ...
$plugin->version = 2026030701; // Incremented!
```

### ❌ NEVER: Use Global Variables

```php
// WRONG
global $myvar;
$myvar = 'value';

// CORRECT
class my_class {
    private $myvar;
    
    public function __construct() {
        $this->myvar = 'value';
    }
}
```

---

## Examples

### Example 1: Simple Block Plugin

```php
<?php
// block_sceh_dashboard/block_sceh_dashboard.php
defined('MOODLE_INTERNAL') || die();

class block_sceh_dashboard extends block_base {
    
    public function init() {
        $this->title = get_string('pluginname', 'block_sceh_dashboard');
    }
    
    public function get_content() {
        global $USER;
        
        if ($this->content !== null) {
            return $this->content;
        }
        
        $this->content = new stdClass();
        $this->content->text = '';
        
        $context = context_system::instance();
        
        if (has_capability('local/sceh_rules:systemadmin', $context)) {
            $this->content->text = $this->render_admin_dashboard();
        } else {
            $this->content->text = $this->render_learner_dashboard();
        }
        
        return $this->content;
    }
    
    public function applicable_formats() {
        return [
            'my' => true,
            'site' => true,
        ];
    }
    
    private function render_admin_dashboard() {
        $html = \html_writer::start_div('sceh-admin-dashboard');
        $html .= \html_writer::tag('h3', get_string('admindashboard', 'block_sceh_dashboard'));
        // Add admin cards
        $html .= \html_writer::end_div();
        return $html;
    }
    
    private function render_learner_dashboard() {
        $html = \html_writer::start_div('sceh-learner-dashboard');
        $html .= \html_writer::tag('h3', get_string('learnerdashboard', 'block_sceh_dashboard'));
        // Add learner cards
        $html .= \html_writer::end_div();
        return $html;
    }
}
```

### Example 2: Local Plugin with Custom Page

```php
<?php
// local_sceh_rules/stream_progress.php
require_once('../../config.php');
require_once($CFG->libdir . '/completionlib.php');

require_login();

$context = context_system::instance();
require_capability('local/sceh_rules:viewprogress', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/sceh_rules/stream_progress.php'));
$PAGE->set_title(get_string('streamprogress', 'local_sceh_rules'));
$PAGE->set_heading(get_string('streamprogress', 'local_sceh_rules'));

echo $OUTPUT->header();

// Get user's stream
$stream = local_sceh_rules_get_user_stream($USER->id);

if (!$stream) {
    echo \html_writer::div(
        get_string('nostream', 'local_sceh_rules'),
        'alert alert-warning'
    );
} else {
    // Render progress
    echo local_sceh_rules_render_stream_progress($stream);
}

echo $OUTPUT->footer();
```

### Example 3: Reusable Renderer

```php
<?php
// local_sceh_rules/classes/output/sceh_card.php
namespace local_sceh_rules\output;

defined('MOODLE_INTERNAL') || die();

class sceh_card {
    
    /**
     * Render a simple card
     *
     * @param array $config Card configuration
     * @return string HTML
     */
    public static function simple(array $config): string {
        $title = s($config['title'] ?? '');
        $icon = s($config['icon'] ?? 'fa-circle');
        $color = s($config['color'] ?? 'blue');
        $url = $config['url'] ?? null;
        
        $inner = self::render_icon($icon);
        $inner .= \html_writer::div($title, 'sceh-card-title');
        
        if ($url) {
            $inner = \html_writer::link($url, $inner, ['class' => 'sceh-card-link']);
        }
        
        return \html_writer::div($inner, "sceh-card sceh-card-{$color}");
    }
    
    /**
     * Render an icon
     *
     * @param string $icon Font Awesome icon class
     * @return string HTML
     */
    private static function render_icon(string $icon): string {
        return \html_writer::tag('i', '', ['class' => "fa {$icon}"]);
    }
}
```

---

## Verification Checklist

Before committing a new plugin, verify:

- [ ] `version.php` exists with correct component name
- [ ] Version number incremented from previous version
- [ ] Language strings defined in `lang/en/<component>.php`
- [ ] `pluginname` string defined
- [ ] All user-facing text uses `get_string()`
- [ ] Database tables prefixed with component name
- [ ] Capabilities defined in `db/access.php` (if needed)
- [ ] Capability strings defined in language file
- [ ] All HTML generated with `\html_writer`
- [ ] No direct `echo` statements in renderers
- [ ] README.md exists with usage documentation
- [ ] Code follows Moodle coding style

---

## Related Documentation

- [SCRIPT_PATTERNS.md](./SCRIPT_PATTERNS.md) - Configuration script patterns
- [CUSTOM_API_REFERENCE.md](./CUSTOM_API_REFERENCE.md) - Custom plugin APIs
- [DATABASE_SCHEMA.md](./DATABASE_SCHEMA.md) - Database schema reference
- [Moodle Plugin Development](https://docs.moodle.org/dev/Plugin_development) - Official docs
