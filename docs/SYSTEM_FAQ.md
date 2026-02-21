# System FAQ - SCEH LMS

Last updated: 2026-02-21

## 1) How does competency mapping work in this system?
Hybrid model:
- Core Moodle LP is the primary model. Program Owners map activities to competencies in Moodle LP.
- `local_sceh_rules` adds rule-based automation: attendance threshold rules linked to competencies and roster rules for auto evidence.

## 2) Is competency progression fully controlled by custom code?
No. Core Moodle LP remains the source of truth for competency structures, plans, and activity mapping. Custom rules add operational behavior (attendance checks, roster evidence automation).

## 3) What is the source of truth for stream selection?
Learner stream choice is read from Moodle Choice responses in-course. The selected option is mapped to a `STREAM - ...` section by name.

## 4) How does the navigation work?
- The **SCEH logo** in the top-left is the primary home link (goes to `/my/`).
- "Dashboard" and "My courses" are hidden from the primary nav to reduce clutter.
- Each role sees only relevant cards on their dashboard — no generic Moodle nav.

## 5) What about Timeline and Workflow Queue?
- **Timeline**: Visible for all roles as a Moodle core activity feed. Styled with reduced visual weight.
- **Workflow Queue**: Currently hidden for initial rollout. Will be re-enabled once users are comfortable with the core system. Uncomment line 37 in `block_sceh_dashboard.php` to restore.

## 6) How does completion tracking work?
Configured via `scripts/config/configure_completion_tracking.php`:
- Site-level toggle: enabled.
- Per-course enablement: auto-enabled on all courses.
- Smart defaults by activity type:
  - **Quiz** → automatic (receive a grade)
  - **Resource/URL** → automatic (view the activity)
  - **Assignment** → automatic (submit for grading)
  - **All others** → manual (student marks as done)
- New courses need default completion configured in course settings or re-run the script.

## 7) Where is the color scheme defined?
Theme tokens in:
- `theme_sceh/scss/tokens.scss`

Card/status styles consume those tokens in:
- `local_sceh_rules/styles/sceh_card_system.css`
- `block_sceh_dashboard/styles.css`

## 8) Where should process and role-flow questions be documented?
- User-facing workflows: `docs/USER_FAQ.md` and in-app Help page (`/local/sceh_rules/help.php`)
- System/technical decisions: this file (`docs/SYSTEM_FAQ.md`)
- Course import: `docs/COURSE_PACKAGE_IMPORT_BLUEPRINT.md`

## 9) What is still configuration work vs feature development?
Mostly configuration/governance:
- Report schedules and recipients
- Communication defaults (forums/messages)
- Backup/restore policy
- Competency evidence standards
- Badge criteria per program
- KPI threshold tuning for future Workflow Queue

## 10) How do we extend the help system?
The in-app Help page at `/local/sceh_rules/help.php` renders role-aware FAQ content. To update:
1. Edit the FAQ arrays in `help.php`.
2. Copy to container and purge caches.
3. Content is immediately available to all logged-in users.
