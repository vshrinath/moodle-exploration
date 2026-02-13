# Release Notes - Moodle Fellowship Training System

This document tracks all significant changes to the codebase. Each entry includes what changed, why, and which files were affected.

---

## [2026-02-13] — Security Hardening and Code Quality Improvements

**Branch**: `front-end-explorations`

### What changed
- Moved all Docker passwords to environment variables
- Added database transaction handling to multi-step operations
- Consolidated duplicate code in rules management pages
- Added null checks to dashboard SQL queries
- Organized 100+ root-level scripts into structured directories
- Created comprehensive security documentation
- Added session message size limits
- Enhanced error handling in rules engine

### Why
Security audit identified hardcoded credentials in docker-compose.yml and missing transaction protection in badge creation. Code quality review found significant duplication between attendance_rules.php and roster_rules.php, and missing null checks that could cause division by zero errors. File organization was needed to reduce root directory clutter from 100+ files to 10.

### Files touched
- `docker-compose.yml` — Replaced hardcoded passwords with environment variables, disabled debug mode by default
- `.env.example` — Created template for secure environment configuration
- `scripts/generate-env.sh` — Created automated secure password generation script
- `docs/DOCKER_SECURITY.md` — Added comprehensive security documentation
- `README.md` — Created project overview with security-first setup instructions
- `.gitignore` — Added .env files to ignore list
- `scripts/config/configure_badge_system.php` — Added transaction handling for badge creation
- `local_sceh_rules/classes/helper/transaction_helper.php` — Created reusable transaction helper class
- `local_sceh_rules/classes/helper/rules_table_renderer.php` — Created shared table rendering helper
- `local_sceh_rules/attendance_rules.php` — Refactored to use shared table renderer
- `local_sceh_rules/roster_rules.php` — Refactored to use shared table renderer, added proper escaping
- `local_kirkpatrick_dashboard/index.php` — Added null checks to all SQL queries
- `block_sceh_dashboard/block_sceh_dashboard.php` — Removed unused get_activity_id() method
- `local_sceh_rules/classes/observer/attendance_observer.php` — Added session message size limit
- `local_sceh_rules/classes/rules/attendance_rule.php` — Added try-catch error handling
- `scripts/README.md` — Created documentation for script organization
- `scripts/config/` — Moved 23 configure_*.php scripts
- `scripts/verify/` — Moved 26 verify_*.php scripts
- `scripts/test/` — Moved 19 test and property test scripts
- `CONVENTIONS.md` — Updated to reflect Moodle PHP project instead of Django

---

## [2026-01-17] — Dashboard Block and Rules Engine

**Branch**: `front-end-explorations`

### What changed
- Created SCEH Dashboard block plugin with role-based cards
- Implemented Rules Engine for attendance and roster automation
- Added Kirkpatrick Level 4 ROI tracking plugin
- Created unified Kirkpatrick dashboard for Levels 1-3

### Why
Fellowship programs needed a unified navigation interface and automated rule enforcement for attendance-based competency access. Kirkpatrick evaluation framework required consolidated dashboard for training effectiveness measurement.

### Files touched
- `block_sceh_dashboard/` — Complete dashboard block plugin (7 trainee cards, 8 admin cards)
- `local_sceh_rules/` — Rules engine with attendance and roster automation
- `local_kirkpatrick_level4/` — ROI calculation and organizational impact tracking
- `local_kirkpatrick_dashboard/` — Unified evaluation dashboard

---

## [2025-12-15] — Fellowship-Specific Features

**Branch**: `front-end-explorations`

### What changed
- Configured case logbook database activity
- Configured credentialing sheet database activity
- Created research publications tracking template
- Added fellowship-specific custom profile fields

### Why
Medical fellowship programs require specialized tracking for clinical cases, credentials, and research output. Standard Moodle profiles needed extension with fellowship-specific fields.

### Files touched
- `database_templates/case_logbook_template.xml` — Case tracking structure
- `database_templates/credentialing_sheet_template.xml` — Credential tracking structure
- `database_templates/research_publications_template.xml` — Research tracking structure
- `scripts/config/configure_case_logbook.php` — Case logbook setup automation
- `scripts/config/configure_credentialing_sheet.php` — Credentialing setup automation
- `scripts/verify/verify_case_logbook.php` — Validation script
- `scripts/verify/verify_credentialing_sheet.php` — Validation script

---

## [2025-11-20] — Gamification and Engagement System

**Branch**: `front-end-explorations`

### What changed
- Installed and configured Block XP (gamification)
- Installed and configured Block Stash (collectible items)
- Configured engagement tracking and leaderboards
- Integrated gamification with attendance system

### Why
Increase trainee engagement through game mechanics. Research shows gamification improves learning outcomes and course completion rates in medical education.

### Files touched
- `plugin-source/block_stash_moodle51_2025100800.zip` — Stash plugin package
- `scripts/config/configure_gamification_system.php` — Gamification setup
- `scripts/config/configure_engagement_tracking.php` — Engagement metrics
- `scripts/config/configure_attendance_gamification.php` — Attendance integration
- `scripts/verify/verify_gamification_system.php` — Validation script
- `install_attendance_gamification.sh` — Installation automation

---

## [2025-10-15] — Badge and Certificate System

**Branch**: `front-end-explorations`

### What changed
- Configured Open Badges 2.0 compliant badge system
- Created 5 competency-based badge templates (Bronze, Silver, Gold, Learning Path, Program)
- Configured automated badge awarding based on competency achievement
- Set up certificate system with custom templates

### Why
Digital credentials provide portable proof of competency achievement. Open Badges 2.0 compliance enables sharing on LinkedIn and other platforms. Automated awarding reduces administrative burden.

### Files touched
- `scripts/config/configure_badge_system.php` — Badge system setup with transaction handling
- `scripts/config/configure_certificate_system.php` — Certificate configuration
- `scripts/verify/verify_badge_system.php` — Badge validation
- `scripts/verify/verify_certificate_system.php` — Certificate validation
- `scripts/test/property_test_automated_badge_awarding.php` — Automated awarding tests

---

## [2025-09-10] — Attendance Tracking System

**Branch**: `front-end-explorations`

### What changed
- Configured attendance tracking module
- Enabled mobile attendance capture
- Integrated attendance with competency framework
- Created attendance-based competency access rules

### Why
Fellowship programs require strict attendance monitoring for accreditation. Mobile capture enables real-time attendance recording. Integration with competencies enforces prerequisite attendance requirements.

### Files touched
- `scripts/config/configure_attendance_tracking.php` — Attendance setup
- `scripts/config/configure_mobile_attendance.php` — Mobile features
- `scripts/verify/verify_attendance_tracking.php` — Validation
- `scripts/verify/verify_mobile_attendance.php` — Mobile validation
- `scripts/test/property_test_attendance_competency_integration.php` — Integration tests

---

## [2025-08-05] — Content and Assessment System

**Branch**: `front-end-explorations`

### What changed
- Configured video repositories with YouTube integration
- Set up competency-mapped assessments (quizzes and assignments)
- Configured rubric-based assessment
- Enabled immediate feedback mechanisms

### Why
Video content is essential for medical training. Competency-mapped assessments ensure learning activities align with framework. Rubrics provide structured feedback aligned to competency criteria.

### Files touched
- `scripts/config/configure_content_asset_management.php` — Content setup
- `scripts/config/configure_video_repositories.php` — Video integration
- `scripts/config/configure_competency_assessments.php` — Assessment configuration
- `enable_youtube_repository.php` — YouTube integration
- `scripts/verify/verify_content_asset_management.php` — Validation
- `scripts/verify/verify_competency_assessments.php` — Assessment validation

---

## [2025-07-01] — Learning Plans and Progress Tracking

**Branch**: `front-end-explorations`

### What changed
- Created 4 learning plan templates (Core Clinical, Surgical, Diagnostic, Professional)
- Configured learning path automation
- Set up progress tracking with milestone support
- Enabled progress preservation across program years

### Why
Structured learning paths guide trainees through competency development. Templates reduce administrative setup time. Progress tracking provides visibility into trainee advancement.

### Files touched
- `scripts/test/create_learning_plan_templates.php` — Template creation with transaction handling
- `scripts/config/configure_learning_path_automation.php` — Automation setup
- `scripts/config/configure_progress_tracking.php` — Progress configuration
- `scripts/verify/verify_learning_plan_templates.php` — Validation
- `scripts/verify/verify_progress_tracking.php` — Progress validation
- `scripts/test/property_test_progress_preservation.php` — Progress preservation tests

---

## [2025-06-15] — Program Structure and Cohort Management

**Branch**: `front-end-explorations`

### What changed
- Configured multi-year program structure
- Set up advanced cohort management with automated enrollment
- Implemented version isolation for program iterations
- Configured role-based access control

### Why
Fellowship programs span multiple years with distinct cohorts. Version isolation allows program updates without affecting current trainees. Automated enrollment reduces manual administrative work.

### Files touched
- `scripts/config/configure_program_structure.php` — Program setup
- `scripts/config/configure_advanced_cohort_management.php` — Cohort configuration
- `scripts/verify/verify_program_structure.php` — Validation
- `scripts/verify/verify_cohort_management.php` — Cohort validation
- `scripts/test/property_test_version_isolation.php` — Version isolation tests
- `scripts/test/property_test_role_based_access_control.php` — RBAC tests

---

## [2025-05-20] — Competency Framework Foundation

**Branch**: `front-end-explorations`

### What changed
- Created hierarchical competency framework for ophthalmology fellowship
- Implemented prerequisite relationships with circular dependency prevention
- Configured competency evidence collection
- Set up core vs allied competency classification

### Why
Competency-based education requires structured framework defining learning outcomes. Prerequisite enforcement ensures proper skill progression. Evidence collection provides proof of competency achievement.

### Files touched
- `scripts/test/create_competency_framework_structure.php` — Framework creation with transaction handling
- `scripts/config/configure_competency_evidence_collection.php` — Evidence setup
- `scripts/verify/verify_competency_framework_structure.php` — Validation
- `scripts/test/property_test_circular_dependency_prevention.php` — Circular dependency tests
- `scripts/test/property_test_competency_reusability.php` — Reusability tests

---

## [2025-04-10] — Initial Docker Setup

**Branch**: `front-end-explorations`

### What changed
- Created docker-compose.yml with Bitnami Moodle and MariaDB
- Configured persistent volumes for data storage
- Set up initial Moodle installation
- Configured basic admin access

### Why
Docker provides consistent development environment across team members. Bitnami images simplify Moodle deployment and maintenance.

### Files touched
- `docker-compose.yml` — Docker service definitions (later updated for security)
- `.gitignore` — Ignore Docker volumes and OS files

---

## Maintenance Notes

### Version Numbering
Plugin versions follow YYYYMMDDXX format:
- YYYY: Year
- MM: Month
- DD: Day
- XX: Revision number (00-99)

### Testing Requirements
Before each release:
1. Run PHPUnit tests: `vendor/bin/phpunit local/sceh_rules/tests/`
2. Run property tests: `php scripts/test/property_test_*.php`
3. Run integration tests: `php scripts/test/test_*_integration.php`
4. Verify all configuration scripts: `php scripts/verify/verify_*.php`

### Security Checklist
- [ ] All passwords in environment variables
- [ ] Debug mode disabled in production
- [ ] .env file not in version control
- [ ] File permissions set correctly (600 for .env)
- [ ] All user input validated with required_param/optional_param
- [ ] All database queries use $DB object (no raw SQL)
- [ ] All output properly escaped
- [ ] CSRF protection on all forms (sesskey)
- [ ] Capability checks on all pages

---

**Last Updated**: 2026-02-13  
**Maintained By**: Development Team  
**Format Version**: 1.0
