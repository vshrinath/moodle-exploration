# SCEH Rules Engine Plugin

## Overview

The SCEH Rules Engine is a Moodle local plugin that provides automated business logic for competency-based learning management. It consolidates custom rule processing that cannot be achieved with Moodle's core conditional access features.

## Features

### 1. Attendance-Based Competency Locking
- Automatically blocks competency progression when attendance falls below configured thresholds
- Cannot be achieved with core conditional access (which only supports activity completion)
- Configurable per competency and course
- Real-time evaluation on attendance updates

### 2. Automated Roster-to-Competency Progression
- Automatically awards competency evidence when rosters are completed
- Supports five roster types: morning class, night duty, training OT, satellite visits, postings
- Configurable mapping between roster types and competencies
- Automatic audit trail for all automated awards

### 3. Comprehensive Audit Logging
- Tracks all rule evaluations and actions
- Provides detailed audit trail for compliance
- Accessible to authorized administrators

## Installation

1. Copy the `local_sceh_rules` directory to your Moodle installation's `local/` directory
2. Visit Site Administration → Notifications to complete the installation
3. Configure the plugin at Site Administration → Plugins → Local plugins → SCEH Rules Engine

## Configuration

### Enable Rules Engine
1. Navigate to Site Administration → Plugins → Local plugins → SCEH Rules Engine
2. Enable the rules engine and specific rule types (attendance, roster)

### Configure Attendance Rules
1. Click "Attendance Rules" in the plugin settings
2. Add rules specifying:
   - Target competency
   - Course where attendance is tracked
   - Minimum attendance threshold (percentage)
3. Rules are evaluated automatically when attendance is taken

### Configure Roster Rules
1. Click "Roster Rules" in the plugin settings
2. Add rules specifying:
   - Roster type (morning, night, training, satellite, posting)
   - Target competency to award
   - Evidence description for audit trail
3. Rules are triggered automatically when rosters are completed

## Architecture

### Core Components

**Rule Evaluator** (`classes/engine/rule_evaluator.php`)
- Abstract base class for rule evaluation logic
- Provides audit logging functionality
- Extensible for future rule types

**Event Handler** (`classes/engine/event_handler.php`)
- Abstract base class for event processing
- Handles event validation and user extraction
- Ensures rules engine is enabled before processing

**Attendance Observer** (`classes/observer/attendance_observer.php`)
- Monitors attendance events
- Evaluates attendance rules
- Blocks competency access when thresholds not met

**Roster Observer** (`classes/observer/roster_observer.php`)
- Monitors roster completion events
- Evaluates roster rules
- Automatically creates competency evidence

### Database Schema

**local_sceh_attendance_rules**
- Stores attendance-based competency locking rules
- Links competencies to courses with threshold requirements

**local_sceh_roster_rules**
- Stores roster-to-competency progression rules
- Maps roster types to target competencies

**local_sceh_rules_audit**
- Comprehensive audit trail for all rule actions
- Tracks rule type, affected user, action taken, and details

## Capabilities

- `local/sceh_rules:managerules` - Manage rules engine configuration
- `local/sceh_rules:viewaudit` - View rules engine audit log

## Requirements

- Moodle 4.0 or higher
- Attendance plugin (for attendance rules)
- Scheduler plugin (for roster rules)
- Competency framework enabled

## Development

### Adding New Rule Types

1. Create a new rule evaluator class extending `\local_sceh_rules\engine\rule_evaluator`
2. Create a new observer class extending `\local_sceh_rules\engine\event_handler`
3. Add database tables in `db/install.xml`
4. Register event observers in `db/events.php`
5. Add admin interface pages for rule management

### Testing

Unit tests are located in `tests/` directory. Run tests with:

```bash
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --group local_sceh_rules
```

## License

GNU GPL v3 or later

## Credits

Developed for SCEH Competency-Based Learning Management System
Copyright 2026 SCEH
