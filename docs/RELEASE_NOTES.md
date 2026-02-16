# Release Notes - Moodle Fellowship Training System

This document tracks all significant changes to the codebase. Each entry includes what changed, why, and which files were affected.

---

## [2026-02-16] — Add package-importer intake MVP (zip + spreadsheet to validated manifest preview)

**Commit**: `PENDING` on branch `front-end-explorations`

### What changed
- Added a new local plugin, `local_sceh_importer`, as the first runnable slice of the package-import workflow.
- Added an upload page for Program Manager/Program Owner roles to submit a package zip and optional quiz spreadsheet CSV.
- Implemented package scanning to auto-draft sections and activities from folder structure for:
  - resources (`assets/common`, `assets/streams/...`)
  - assignments (`assignments/`)
  - quizzes (`quizzes/*.xml`, `quizzes/*.gift`, optional inline rows from CSV)
  - trainer-only lesson plans (`lesson_plans/`)
  - trainer-only roleplay guidance/assets (`roleplay/`)
- Implemented validation and preview with explicit errors/warnings and generated manifest YAML output.
- Mounted the new importer plugin in both Moodle web and cron containers so it is available in the active MoodleHQ stack.

### Why
Before building full import execution, we need a safe intake and validation stage that non-technical content creators can use. This MVP verifies package structure early, surfaces issues clearly, and gives a deterministic manifest preview before any write operations happen.

### Files touched
- `local_sceh_importer/version.php` — Plugin metadata/version for Moodle 5.1 stack
- `local_sceh_importer/db/access.php` — Added manage capability (`local/sceh_importer:manage`)
- `local_sceh_importer/lang/en/local_sceh_importer.php` — UI strings and validation error messages
- `local_sceh_importer/settings.php` — Added admin entry/link under local plugins
- `local_sceh_importer/index.php` — Upload + validate + preview controller page
- `local_sceh_importer/classes/form/upload_form.php` — Upload form for zip, optional quiz CSV, import mode, and dry-run
- `local_sceh_importer/classes/local/package_scanner.php` — Package extraction and folder-to-activity scan logic
- `local_sceh_importer/classes/local/quiz_sheet_parser.php` — Non-technical quiz CSV parser
- `local_sceh_importer/classes/local/manifest_builder.php` — Draft manifest build, validation, and YAML rendering
- `docker-compose.moodlehq.yml` — Mounted `local_sceh_importer` for web and cron services
- `docs/RELEASE_NOTES.md` — Added this release entry

---

## [2026-02-14] — Define course package import blueprint and non-technical authoring path

**Commit**: `PENDING` on branch `front-end-explorations`

### What changed
- Added a dedicated blueprint for folder + manifest course imports, covering intent, process, role ownership, schema structure, import modes, and phased rollout.
- Added non-technical quiz authoring guidance (spreadsheet/form intake with automatic conversion to import-ready quiz payloads).
- Added required quality-gate policy for package imports, including critical blockers and warning-level checks.
- Added required rollback strategy by import job with scoped rollback and safety constraints for learner data.
- Cross-linked the blueprint from user and system FAQs.

### Why
Course creators are often non-technical, while the platform still needs strict import governance. This blueprint aligns both needs by defining a creator-friendly intake flow with strong validation, auditability, and recovery controls before implementation starts.

### Files touched
- `docs/COURSE_PACKAGE_IMPORT_BLUEPRINT.md` — New implementation reference for package import architecture and policy
- `docs/USER_FAQ.md` — Added user-facing pointer and FAQ coverage for package import path
- `docs/SYSTEM_FAQ.md` — Added system-level pointer to blueprint and implementation context
- `docs/RELEASE_NOTES.md` — Added release entry for this documentation update

---

## [2026-02-14] — Add user-facing FAQs and composite workflow guidance

**Commit**: `PENDING` on branch `front-end-explorations`

### What changed
- Added a new user-focused FAQ covering practical "How do I..." tasks across Program Owner, System Admin, Trainer, Trainer Coach, and Learner roles.
- Added a system FAQ for architecture-level questions (competency mapping model, workflow queue data sources, stream behavior, and configuration boundaries).
- Expanded workflow documentation with composite end-to-end lifecycle flows and role handoff guidance.
- Linked workflow docs to the new FAQ references for faster onboarding and support handoffs.

### Why
As workflows became cross-role and more feature-rich, new users needed a clear operational reference that is easier to navigate than implementation notes. This improves onboarding speed, support consistency, and readiness for upcoming real-content trials.

### Files touched
- `docs/USER_FAQ.md` — New detailed role/task FAQ for everyday operations
- `docs/SYSTEM_FAQ.md` — New architecture/system-behavior FAQ
- `docs/USER_WORKFLOWS.md` — Added composite workflow guidance and FAQ cross-links
- `docs/RELEASE_NOTES.md` — Added release entry for this documentation update

---

## [2026-02-14] — Introduce role-based workflow queue and dashboard layout improvements

**Commit**: `PENDING` on branch `front-end-explorations`

### What changed
- Added a dynamic, role-aware **Workflow Queue** on dashboard with three buckets: `Do Now`, `This Week`, and `Watchlist`.
- Populated queue items from Moodle-native signals (events, tasks, cohorts, grading backlog, stream setup checks) with lightweight derived rules.
- Added role-specific timeline visibility: kept Timeline for learners and hid it for system admin, program owner, trainer, and trainer coach.
- Replaced dashboard calendar blocks with workflow-first layout in the SCEH theme.
- Improved dashboard readability with wider responsive content area for `/my/`.
- Polished workflow card alignment (left-aligned content, consistent icon placement, action button anchoring).
- Centralized dashboard/status color usage through theme tokens and token-backed card styles.
- Updated site naming defaults to `SCEH` for new stack installs and aligned current runtime name.
- Documented workflow-queue model and data-source strategy in the workflow reference.

### Why
The dashboard needed to prioritize operational next actions over generic calendar widgets. This change makes the role journey clearer, improves scanability on desktop/mobile, and establishes a maintainable design-token foundation for future UI updates.

### Files touched
- `block_sceh_dashboard/block_sceh_dashboard.php` — Added dynamic workflow queue generation and rendering logic
- `block_sceh_dashboard/lang/en/block_sceh_dashboard.php` — Added workflow queue strings and labels
- `block_sceh_dashboard/styles.css` — Refined dashboard/workflow presentation and token-based gradient usage
- `theme_sceh/classes/output/core_renderer.php` — Added role body classes for role-based dashboard behavior
- `theme_sceh/scss/internal.scss` — Added timeline/calendar visibility rules and dashboard width/header polish
- `theme_sceh/scss/tokens.scss` — Added semantic status and gradient tokens
- `local_sceh_rules/styles/sceh_card_system.css` — Improved workflow card/item alignment and tokenized status colors
- `docs/USER_WORKFLOWS.md` — Added workflow queue model, role flows, and implementation scope
- `docker-compose.moodlehq.yml` — Updated default site naming to `SCEH`

---

## [2026-02-14] — Standardize AI collaboration and release-note writing conventions

**Commit**: `PENDING` on branch `front-end-explorations`

### What changed
- Updated `AGENTS.md` to a cleaner, consolidated rule set for AI-assisted development behavior.
- Updated `CONVENTIONS.md` to align project conventions with the agreed release-note style and collaboration expectations.
- Preserved the customer-facing release-note structure as the default format for future entries.

### Why
We needed one consistent operating contract for AI tools and one consistent documentation style for release communication. This reduces ambiguity during implementation and keeps change history easier for non-technical stakeholders to follow.

### Files touched
- `AGENTS.md` — Reorganized and simplified agent operating rules
- `CONVENTIONS.md` — Updated project conventions and release-note expectations

---

## [2026-02-14] — Deliver branded SCEH login page and simplify sign-in actions

**Commit**: `PENDING` on branch `front-end-explorations`

### What changed
- Added a custom Moodle theme (`theme_sceh`) with a branded, responsive logged-out experience and a dedicated login layout.
- Set logged-out `/` to route users to the login screen and logged-in users to their dashboard for a cleaner default journey.
- Removed guest-access and cookies-action clutter from the login panel to keep sign-in focused on authenticated users.
- Updated the login heading copy to “Log in to SCEH LMS”.
- Added theme implementation notes and visual-library guidance docs for follow-on UI work.

### Why
The default Moodle login and home flow was noisy and did not reflect the product identity. This update creates a clearer first impression, reduces decision friction at sign-in, and establishes a maintainable base for future UI improvements.

### Files touched
- `theme_sceh/config.php` — Theme configuration, layouts, and navigation adjustments
- `theme_sceh/layout/login.php` — Custom login layout wiring
- `theme_sceh/templates/login.mustache` — Branded login page shell and content structure
- `theme_sceh/templates/core/loginform.mustache` — Theme override of Moodle login form to simplify actions and heading
- `theme_sceh/classes/output/core_renderer.php` — Logged-in/logged-out homepage redirect behavior
- `theme_sceh/scss/login.scss` — Responsive login styling and component-level polish
- `theme_sceh/scss/internal.scss` — Internal page visual adjustments
- `theme_sceh/scss/components.scss` — Shared theme component styling
- `theme_sceh/scss/tokens.scss` — Theme color/spacing/shadow tokens
- `theme_sceh/lib.php` — Theme SCSS assembly hook
- `theme_sceh/lang/en/theme_sceh.php` — Theme copy strings including login heading text
- `theme_sceh/version.php` — Theme plugin metadata
- `docker-compose.moodlehq.yml` — Theme mount for web/cron containers
- `docs/LOGIN_BRANDING_THEME.md` — Operational guide for branded login theme
- `docs/THEME_VISUAL_LIBRARY.md` — Visual system notes for consistent implementation

---

## [2026-02-14] — Add workflow guardrails and automated card/page regression tests

**Commit**: `PENDING` on branch `front-end-explorations`

### What changed
- Added automated PHPUnit coverage for shared card rendering (`simple`, `metric`, `list`, `detail`) and rules page card output.
- Added dashboard card-link regression tests to prevent known broken routes (missing required URL params).
- Added Trainer Coach cohort helper and enabled Trainer Coach-specific access path:
  - Trainer users in the `trainer-coaches` cohort now get a Training Evaluation card.
  - Trainer Coach path can open Kirkpatrick dashboard without granting broad access to all trainers.
- Hardened trainer attendance fallback link to a safe route (`My courses`) when no assigned cohort course is available.
- Fixed rules renderer string usage to plugin-local strings to avoid debugging notices in tests/runtime.
- Updated workflow status notes to reflect implemented Trainer Coach support and reporting setup expectations.

### Why
We had repeated UI regressions caused by invalid links and role-path drift. This update adds a reliable automated safety net for card/page behavior and closes the main remaining code-backed workflow gap (optional Trainer Coach flow) while keeping access scoped and explicit.

### Files touched
- `local_sceh_rules/tests/sceh_card_test.php` — New renderer unit tests for card templates
- `local_sceh_rules/tests/rules_table_renderer_test.php` — New rules page card output tests
- `block_sceh_dashboard/tests/card_links_test.php` — New dashboard link regression tests and Trainer Coach card test
- `local_sceh_rules/classes/helper/trainer_coach_helper.php` — New cohort-based Trainer Coach detection helper
- `block_sceh_dashboard/block_sceh_dashboard.php` — Safe trainer attendance fallback and Trainer Coach evaluation card injection
- `local_kirkpatrick_dashboard/index.php` — Trainer Coach cohort access path for evaluation dashboard
- `local_sceh_rules/classes/helper/rules_table_renderer.php` — Replaced invalid core string keys with plugin-local strings
- `local_sceh_rules/lang/en/local_sceh_rules.php` — Added `disabled` and rule-deletion confirmation strings
- `docs/USER_WORKFLOWS.md` — Updated status markers for Trainer Coach and reporting dependencies

---

## [2026-02-14] — Migrate stream and Kirkpatrick summary pages to shared card system

**Commit**: `PENDING` on branch `front-end-explorations`

### What changed
- Migrated **Stream Setup Check** from checklist table to card-based status tiles.
- Migrated **Stream Progress** from section tables to card-based section/activity views.
- Migrated **Kirkpatrick dashboard summary metrics** (Levels 1-4) to shared metric cards.
- Kept filters, chart placeholders, permissions, and export actions unchanged.

### Why
This completes the planned Phase 2 usage rollout so major program-owner, learner, and sysadmin evaluation surfaces all use the same card foundation. The result is a more consistent UI model and lower maintenance cost for future layout changes.

### Files touched
- `local_sceh_rules/stream_setup_check.php` — Replaced checklist table rendering with shared detail cards
- `local_sceh_rules/stream_progress.php` — Replaced section tables with shared list/detail cards
- `local_kirkpatrick_dashboard/index.php` — Replaced metric tiles with shared metric cards

---

## [2026-02-13] — Implement Phase 1 card system and migrate rules pages

**Commit**: `PENDING` on branch `front-end-explorations`

### What changed
- Implemented shared Phase 1 card renderer (`simple`, `metric`, `list`, `detail`) in `local_sceh_rules`.
- Added reusable card-system stylesheet for consistent card layouts, status states, stats, lists, and actions.
- Migrated Attendance Rules and Roster Rules from table rendering to card-based rendering.
- Updated roster rules page to use shared rules renderer (matching attendance rules behavior).
- Wired dashboard card rendering to use shared `sceh_card::simple()` with fallback.

### Why
This creates one reusable card foundation for the platform and removes duplicated ad-hoc card/table rendering. It also aligns rules-management UI with the approved card-system direction while preserving existing actions and permissions.

### Files touched
- `local_sceh_rules/classes/output/sceh_card.php` — New shared card renderer with helper methods and Phase 1 templates
- `local_sceh_rules/styles/sceh_card_system.css` — New shared card-system styles
- `local_sceh_rules/classes/helper/rules_table_renderer.php` — Replaced table output with card output for attendance/roster rules
- `local_sceh_rules/attendance_rules.php` — Added card-system stylesheet include
- `local_sceh_rules/roster_rules.php` — Switched to shared renderer and added stylesheet include
- `block_sceh_dashboard/block_sceh_dashboard.php` — Uses shared simple card renderer with fallback

---

## [2026-02-13] — Standardize development on MoodleHQ 5.1 and scrub legacy stack paths

**Commit**: `PENDING` on branch `front-end-explorations`

### What changed
- Removed the legacy Bitnami compose stack from the repository entrypoint (`docker-compose.yml`).
- Updated environment template and env-generation script to MoodleHQ-only variables.
- Updated core setup and script docs to use the active MoodleHQ container names, paths, and URL (`127.0.0.1:8081`).
- Kept runtime validation on the new stack after cleanup (restart + version + mock-user checks).

### Why
Having two parallel stack definitions was causing confusion about which environment is authoritative. This cleanup makes MoodleHQ 5.1 the single development baseline and removes legacy setup paths from the active workflow.

### Files touched
- `docker-compose.yml` — Removed legacy stack definition
- `.env.example` — Removed legacy env keys, kept MoodleHQ-only configuration
- `scripts/generate-env.sh` — Generates only MoodleHQ-related credentials/settings
- `README.md` — Updated setup/run/test commands to MoodleHQ stack only
- `scripts/README.md` — Updated script execution examples to new container/path
- `docs/MOCK_USERS_SETUP.md` — Updated operational commands to new container/path

---

## [2026-02-13] — Store attendance plugin as normal repository files

**Commit**: `PENDING` on branch `front-end-explorations`

### What changed
- Replaced an accidental gitlink/submodule-style entry for `mod/attendance` with regular tracked files.
- Ensured the attendance plugin is fully contained in this repository for local setup and future deployment.

### Why
The first attendance parity commit referenced the plugin as an embedded repository pointer, which can break fresh clones. This correction makes setup reproducible without extra submodule steps.

### Files touched
- `mod/attendance` — Added full plugin file tree as regular tracked files

---

## [2026-02-13] — Fix Attendance Reports parity on MoodleHQ stack

**Commit**: `PENDING` on branch `front-end-explorations`

### What changed
- Added the Attendance activity plugin to the new MoodleHQ stack so existing attendance URLs resolve.
- Updated Sysadmin dashboard Attendance Reports card routing to open an enrolled course first.
- Kept a safe fallback to `My courses` when no enrolled course is available.

### Why
After migration, Attendance Reports card navigation showed missing-file and enrollment issues on the new stack. This change restores behavior parity with the earlier environment and keeps card navigation usable for mock role testing.

### Files touched
- `mod/attendance` — Added attendance module used by existing dashboard links
- `docker-compose.moodlehq.yml` — Mounted attendance module into Moodle web and cron containers
- `block_sceh_dashboard/block_sceh_dashboard.php` — Updated sysadmin attendance link selection to use enrolled courses

---

## [2026-02-13] — Add maintained MoodleHQ dev stack (Moodle 5.1 + MySQL 8)

**Commit**: `PENDING` on branch `front-end-explorations`

### What changed
- Added a new Docker stack based on MoodleHQ images for ongoing development on Moodle 5.1.
- Added startup scripts to bootstrap core, run install/upgrade safely, and run cron separately.
- Added setup documentation for the new stack and updated repository docs/examples to use it.
- Added `.gitignore` protection for local Moodle core checkout to avoid accidental commits.
- Kept the previous stack available so development can continue while migration is validated.

### Why
The previous image path was not ideal for long-term updates. Moving to a maintained Moodle distribution now reduces upgrade risk later and keeps the project aligned with current Moodle releases while development is still in mock-data phase.

This also keeps the path to Azure cleaner: the new setup is closer to a standard production-style split (web + DB + cron), which is easier to carry forward when infrastructure is moved.

### Files touched
- `docker-compose.moodlehq.yml` — New MoodleHQ-based development stack
- `scripts/moodlehq/bootstrap-core.sh` — Core bootstrap script for Moodle source checkout
- `scripts/moodlehq/start-web.sh` — Web container startup/install/upgrade flow
- `scripts/moodlehq/start-cron.sh` — Dedicated cron container startup
- `scripts/moodlehq/entrypoint.d/00-noop.sh` — Entrypoint placeholder hook
- `docs/MOODLEHQ_MYSQL_DEV_STACK.md` — Setup and usage guide for the new stack
- `.env.example` — Added MoodleHQ environment variables and defaults
- `scripts/generate-env.sh` — Added secure variable generation for MoodleHQ stack
- `README.md` — Updated local run guidance for the new stack
- `.gitignore` — Ignore local `moodle-core/` checkout
- `docker-compose.yml` — Updated to align with current local development setup

---

## [2026-02-13] — Align card system docs to one practical Phase 1 path

**Commit**: `PENDING` on branch `front-end-explorations`

### What changed
- Updated the card system specification and pragmatic guide so both describe the same Phase 1 implementation.
- Standardized Phase 1 as one renderer class (`sceh_card`) with internal helper methods.
- Marked advanced card types (chart and activity) as intentionally deferred, not part of Phase 1.
- Updated badge styling guidance to match Moodle 5 / Bootstrap 5 examples.

### Why
The two documents had conflicting implementation guidance, which could lead to rework and inconsistent builds. This update gives one clear path for delivery and keeps implementation scope focused on what is needed now.

### Files touched
- `docs/CARD_SYSTEM_SPECIFICATION.md` — Aligned architecture, scope, and phased plan
- `docs/CARD_SYSTEM_PRAGMATIC_IMPLEMENTATION.md` — Updated practical guidance and Moodle 5 badge examples

---

## [2026-02-13] — Add stage 4 program-owner stream setup checklist

**Commit**: `825e3cb` on branch `front-end-explorations`

### What changed
- Added a new Program Owner page: `Stream Setup Check`.
- The page validates stream readiness for a selected course with pass/fail checks:
  - named Common Foundation section exists
  - at least one stream section exists (`STREAM - ...`)
  - stream Choice activity exists with options
- Added a Program Owner dashboard card to open this checklist directly.
- Added labels/messages for checklist items, status, and details.

### Why
Stage 4 helps Program Owners verify stream configuration before delivery starts. A read-only checklist gives quick visibility into setup quality without making automatic changes to course structure.

We reused `stream_helper` for shared section checks so setup validation stays consistent with trainer and learner stream behavior.

### Files touched
- `local_sceh_rules/stream_setup_check.php` — New read-only stream setup checklist page
- `local_sceh_rules/classes/helper/stream_helper.php` — Added common-foundation presence check helper
- `local_sceh_rules/lang/en/local_sceh_rules.php` — Added checklist labels and status strings
- `block_sceh_dashboard/block_sceh_dashboard.php` — Added Program Owner `Stream Setup Check` card
- `block_sceh_dashboard/lang/en/block_sceh_dashboard.php` — Added `streamsetupcheck` card label

---

## [2026-02-13] — Add stage 3 stream-filtered learner progress view

**Commit**: `fa1ba09` on branch `front-end-explorations`

### What changed
- Added a new learner page: `Stream Progress`.
- The page now shows progress only for:
  - Common Foundation sections
  - the learner’s selected stream section
- Updated the learner `My Progress` card to open this new stream-filtered progress page.
- Added user-facing labels/messages for stream progress status and empty-state handling.
- Fixed section-title rendering so progress page does not depend on course format plugin lookup.

### Why
Stage 3 focuses on keeping learner progress relevant. Learners should see only shared foundation work plus their chosen specialization, not unrelated stream sections.

We reused `stream_helper` to centralize section-selection logic, so trainer and learner stream behavior stays consistent across dashboard and progress pages.

### Files touched
- `local_sceh_rules/stream_progress.php` — New stream-filtered learner progress page
- `local_sceh_rules/classes/helper/stream_helper.php` — Added common/relevant section resolution helpers
- `local_sceh_rules/lang/en/local_sceh_rules.php` — Added stream progress labels and status strings
- `block_sceh_dashboard/block_sceh_dashboard.php` — Routed learner `My Progress` card to stream progress page

---

## [2026-02-13] — Standardize release notes writing style and policy source

**Commit**: `PENDING` on branch `front-end-explorations`

### What changed
- Added a dedicated documentation-style standard in `CONVENTIONS.md` for release notes and commit messages.
- Defined release notes as customer-facing, plain-language, and outcome-first.
- Defined commit messages as technical and implementation-focused.
- Updated `AGENTS.md` to enforce that release notes follow `CONVENTIONS.md`.
- Clarified that helper/utility additions should be explained in non-jargon terms in release notes.

### Why
Recent release-note entries needed a more consistent tone for non-technical stakeholders. Centralizing style rules in one source (`CONVENTIONS.md`) and making `AGENTS.md` enforce it ensures future notes stay readable while commit history remains technically precise.

### Files touched
- `CONVENTIONS.md` — Added release-notes and commit-message style standards
- `AGENTS.md` — Added enforcement note that release notes must follow `CONVENTIONS.md`
- `docs/RELEASE_NOTES.md` — Updated entries to match new plain-language format

---

## [2026-02-13] — Add stage 2 learner stream indicator from choice

**Commit**: `748f10a` on branch `front-end-explorations`

### What changed
- Learners now see a new dashboard card: **“Your Stream: …”** once they choose a specialization.
- Clicking that card takes the learner straight to the right section of their course.
- We added backend logic to:
  - read the learner’s stream choice,
  - find the matching course section,
  - build the correct link automatically.
- We added one new text label (`yourstream`) for this card.
- We also created mock test data so this could be verified end-to-end (`MOCK-AAP-2026`, `mock.learner`).

### Why
This makes the learner experience clearer: after choosing a stream, they can immediately see which stream they are in and go directly to it.

To keep this reliable, stream-matching logic is centralized in one shared helper (`stream_helper`) instead of being duplicated in different places. That makes behavior consistent and easier to maintain.

### Files touched
- `local_sceh_rules/classes/helper/stream_helper.php` — Stream lookup and section mapping logic
- `block_sceh_dashboard/block_sceh_dashboard.php` — Learner “Your Stream” card
- `block_sceh_dashboard/lang/en/block_sceh_dashboard.php` — Card label text (`yourstream`)

---

## [2026-02-13] — Add stage 1 stream support in trainer dashboard

**Commit**: `7210b9f` on branch `front-end-explorations`

### What changed
- Trainers now see stream information directly on their dashboard for assigned courses.
- Each course card shows how many streams are configured.
- Trainers also get direct cards for each stream (for example: `Stream: Front Desk Management`).
- Clicking a stream card opens that exact stream section in the course.
- We added small text labels for stream card titles and stream counts.

### Why
This helps trainers navigate faster. They can go directly to the stream they are teaching instead of opening the full course and searching manually.

To keep behavior consistent, stream detection is handled in one shared helper (`stream_helper`) rather than repeated in multiple dashboard methods.

### Files touched
- `local_sceh_rules/classes/helper/stream_helper.php` — Detects stream sections and normalizes stream names
- `block_sceh_dashboard/block_sceh_dashboard.php` — Adds trainer stream cards and stream counts
- `block_sceh_dashboard/lang/en/block_sceh_dashboard.php` — Adds stream-related card labels

---

## [2026-02-13] — Card System Specification

**Commit**: `PENDING` on branch `front-end-explorations`

### What changed
- Created comprehensive card system specification using atomic design principles
- Defined 3-layer component architecture: Atoms → Molecules → Organisms
- Specified 4 card sizes: small, medium, large, full-width (all responsive)
- Defined 6 card templates: simple, metric, list, detail, chart, activity
- Documented implementation roadmap and complexity analysis
- Added usage examples for rules pages, trainer dashboard, system admin dashboard

### Why
Consistent card-based UI across all pages improves usability and creates cohesive visual language. Atomic design approach makes system easy to build, test, and extend. Status-driven color coding (green/yellow/red) provides at-a-glance insights. Responsive grid ensures mobile compatibility.

### Files touched
- `docs/CARD_SYSTEM_SPECIFICATION.md` — Complete card system specification with atomic components, templates, and implementation guide

---

## [2026-02-13] — Fix dashboard runtime errors and add badge count UX

**Commit**: `PENDING` on branch `front-end-explorations`

### What changed
- Fixed multiple dashboard links that were failing due to missing required URL parameters.
- Updated System Admin cards so users only see cards they can actually access.
- Corrected competency capability checks to use valid Moodle capability names.
- Fixed a Kirkpatrick dashboard query that was causing runtime exceptions.
- Updated SCEH rules pages to use direct login + capability checks instead of fragile admin-section setup.
- Added a badge count in the Badge Management card title so admins can quickly see badge setup status.
- Added runbook notes for re-syncing mock sysadmin capabilities in local setup.

### Why
During role-based testing, users were hitting avoidable runtime errors (missing params, invalid capabilities, section setup failures). These changes make dashboard navigation safer and more predictable.

The badge count improves clarity for new environments by showing whether badges are configured yet.

### Files touched
- `block_sceh_dashboard/block_sceh_dashboard.php` — Safer card routing, capability checks, badge count
- `local_kirkpatrick_dashboard/index.php` — Program filter DB query fix (`get_records_select_menu`)
- `local_sceh_rules/roster_rules.php` — Replaced fragile admin setup flow
- `local_sceh_rules/edit_roster_rule.php` — Replaced fragile admin setup flow
- `local_sceh_rules/attendance_rules.php` — Replaced fragile admin setup flow
- `local_sceh_rules/edit_attendance_rule.php` — Replaced fragile admin setup flow
- `docs/MOCK_USERS_SETUP.md` — Added sysadmin capability re-sync instructions

---

## [2026-02-13] — Fix competency framework dashboard link context

**Commit**: `PENDING` on branch `front-end-explorations`

### What changed
- Updated the dashboard `Competency Framework` card links to include `pagecontextid` for:
- System Admin view in `get_system_admin_cards()`
- Program Owner view in `get_program_owner_cards()`

### Why
Moodle competency framework page requires `pagecontextid` in this flow. Without it, users hit a `missingparam` error from `required_param('pagecontextid')`.

### Files touched
- `block_sceh_dashboard/block_sceh_dashboard.php` — Added system context parameter to competency framework URLs

---

## [v1.3.0] — Week 1-2 Implementation: Role Separation & RBAC Foundation

**Date**: 2026-02-13  
**Branch**: `front-end-explorations`  
**Tag**: `v1.3.0-rbac-foundation`

### What changed
- Introduced three custom role paths for the dashboard:
  - System Admin
  - Program Owner
  - Trainer
- Added SCEH-specific capabilities to support explicit role detection and controlled access.
- Updated dashboard behavior so each role sees relevant cards and actions.
- Added category-based ownership support for Program Owners.
- Added cohort-based course filtering for Trainers.
- Created mock users and mock course/cohort data for repeatable local validation.

### Why

The goal was to separate responsibilities clearly:
1. **System Admin**: platform and user governance
2. **Program Owner**: program/course design ownership
3. **Trainer**: delivery for assigned cohorts

This reduces role overlap, prevents accidental over-permissioning, and aligns the product with the operating model in the implementation guide.

### Files touched
- `block_sceh_dashboard/block_sceh_dashboard.php` — Role-specific card routing and rendering
- `local_sceh_rules/db/access.php` — Added SCEH capability definitions
- `local_sceh_rules/lang/en/local_sceh_rules.php` — Added capability labels
- `local_sceh_rules/version.php` — Plugin version bump for capability updates
- `local_sceh_rules/classes/helper/cohort_filter.php` — Trainer cohort-to-course filtering
- `docs/MOCK_USERS_SETUP.md` — Mock user setup and verification runbook

### Testing completed
- ✅ All 6 capabilities registered in database
- ✅ All 3 custom roles created with correct capability matrix
- ✅ Dashboard role detection uses custom capabilities
- ✅ Cohort filter returns correct courses for mock.trainer
- ✅ Code synced to Docker container and caches purged
- ✅ Mock data created and verified

### Next steps
- Week 3-4: Stream Support via Sections
- Week 5: Dashboard Polish (terminology, mobile-responsive, attendance alerts)
- Week 6 (Optional): Trainer Coach capability

---

## [v1.2.0] — UX Simplification & Operations Documentation

**Date**: 2026-02-13  
**Branch**: `front-end-explorations`  
**Tag**: `v1.2.0-ux-operations-docs`

### What changed
- Created comprehensive UX simplification documentation
- Added pragmatic implementation guide (5-week plan)
- Added complete user workflows for all roles
- Added operations guide (backup, reporting, grading, audit, scaling)
- Added PRD with role architecture and 3-layer responsibility model
- Added 40 user stories with acceptance criteria
- Added attendance alerts to Week 5 implementation

### Why
Established complete documentation foundation for pragmatic UX simplification approach. Documents the 5-week implementation plan using Moodle's existing features rather than building custom systems. Provides step-by-step workflows, operations procedures, and clear role definitions.

### Files touched
- `docs/PRAGMATIC_IMPLEMENTATION_GUIDE.md` — Complete 5-week implementation plan
- `docs/USER_WORKFLOWS.md` — Workflows for all roles
- `docs/OPERATIONS_GUIDE.md` — Backup, reporting, grading, audit, scaling
- `docs/PRD - Role, architecture and more.md` — Role architecture and responsibility layers
- `docs/User stories and acceptance criteria.md` — 40 user stories
- `.kiro/specs/ux-simplification/requirements.md` — Requirements specification
- `.kiro/specs/ux-simplification/design.md` — Technical design
- `.kiro/specs/ux-simplification/pragmatic-approach.md` — Pragmatic vs comprehensive analysis

---

## [2026-02-13] — Attendance Alerts Documentation for Trainer Dashboard

**Commit**: `df49823`  
**Branch**: `front-end-explorations`

### What changed
- Documented attendance alerts card design for trainer dashboard (Week 5 scope)
- Defined proactive monitoring for learners below 75% attendance threshold
- Mapped approach to existing `local_sceh_rules/classes/rules/attendance_rule.php` infrastructure
- Added trainer workflow for reviewing attendance alerts
- Updated Week 5 time estimate from 2 days to 2.5 days
- Marked attendance rules as resolved in dependencies section

### Why
Trainers need proactive visibility into attendance issues before they become critical. The existing attendance infrastructure tracks data and blocks competency access reactively, but trainers had no documented dashboard pattern for intervention. This update captures the intended dashboard behavior and workflow so implementation can be completed consistently against a clear spec.

### Files touched
- `docs/PRAGMATIC_IMPLEMENTATION_GUIDE.md` — Added attendance alerts design and implementation guidance to Week 5
- `docs/USER_WORKFLOWS.md` — Added "TRAINER: Review Attendance Alerts" workflow and marked dependency as resolved

---

## [2026-02-13] — PRD and User Stories Documentation

**Commit**: `5e95c4e`  
**Branch**: `front-end-explorations`  
**Tag**: `v1.2.0-ux-operations-docs`

### What changed
- Added Product Requirements Document (PRD) covering role architecture, streams, and learning paths
- Added 40 user stories with acceptance criteria across all roles
- Documented 3-layer responsibility model: Learning Design Authority, Delivery & Enablement, Oversight & Insight
- Defined 5 core roles with clear responsibilities and ownership mapping
- Explained structural distinctions: Cohort, Focus Stream, Learning Path
- Provided concrete examples across different program types (instructor-led, self-paced, hybrid)
- Included use cases for middle management programs, policy rollouts, and domain-specific upskilling

### Why
The PRD establishes the foundational architecture for the entire system. The 3-layer responsibility model prevents role confusion (trainer ≠ curriculum designer) and enables scale without quality loss. The user stories translate conceptual models into testable behaviors, providing a basis for detailed requirements, estimation, and implementation. This documentation ensures all stakeholders understand what each role can do and why the system is structured this way.

### Files touched
- `docs/PRD - Role, architecture and more.md` — Complete role architecture, responsibility layers, and structural distinctions
- `docs/User stories and acceptance criteria.md` — 40 user stories covering all roles with acceptance criteria

---

## [2026-02-13] — Operations Guide: Backup, Reporting, Grading & Audit

**Branch**: `front-end-explorations`

### What changed
- Created comprehensive operations guide covering backup/disaster recovery, reporting/analytics, assessment/grading, audit logs, and scaling
- Documented automated backup strategy (daily, weekly, monthly) with scripts and retention policies
- Defined 6 essential reports: cohort performance, trainer effectiveness, program health, learner progress, competency achievement, attendance
- Explained 3 grading scales (percentage, competency, pass/fail), rubric creation, and peer assessment workflow
- Documented audit log access, important events, compliance reporting, and retention policies
- Provided scaling guidance for 2000 users including performance optimization and capacity planning

### Why
Operational procedures are critical for system reliability and compliance. The guide addresses: (1) Backup & DR to prevent data loss with automated daily/weekly/monthly backups and recovery procedures, (2) Reporting to monitor program health and trainer effectiveness with 6 automated reports, (3) Grading to ensure consistent assessment with rubrics and peer review, (4) Audit logs for compliance and security monitoring with 180-day retention, (5) Scaling for 2000 users with caching, database optimization, and external video hosting.

### Files touched
- `docs/OPERATIONS_GUIDE.md` — Complete operational procedures for backup, reporting, grading, audit, and scaling (2000 users)

---

## [2026-02-13] — Category-Based Program Ownership

**Branch**: `front-end-explorations`

### What changed
- Added Week 1.5 to pragmatic implementation guide: Category-Based Program Ownership
- Documented how Program Owners can create programs autonomously in assigned categories
- Added System Admin workflows for creating categories and assigning Program Owners
- Added Program Owner workflows for creating programs in their category
- Included dashboard code for showing only assigned categories
- Explained benefits: Program Owners are autonomous, System Admin not a bottleneck

### Why
Without category-based permissions, System Admin must create every program, becoming a bottleneck. With categories, Program Owners can create programs in their assigned category (e.g., "Allied Health Programs") while remaining unable to see other categories (e.g., "Surgical Fellowships"). This enables autonomous program creation while maintaining clear separation between program areas. Critical for scalability.

### Files touched
- `docs/PRAGMATIC_IMPLEMENTATION_GUIDE.md` — Added Week 1.5 with category setup and dashboard code
- `docs/USER_WORKFLOWS.md` — Added System Admin category workflows and Program Owner category-aware workflows

---

## [2026-02-13] — Program Structure with Weekly Organization

**Branch**: `front-end-explorations`

### What changed
- Added comprehensive documentation for program structure with streams and weekly organization
- Illustrated Allied Assist Program as complete example with 3 streams
- Documented use of Labels for weekly organization within sections
- Explained competency mapping per stream with concrete examples
- Clarified when to use streams vs separate programs

### Why
Users needed clarity on how to structure programs with specializations (streams) and how to organize content by weeks. The Label-based approach (Option B) provides flexibility to move content between weeks without affecting other sections, while maintaining clear visual progression for learners. The Allied Assist Program example demonstrates the complete hierarchy: Program → Streams → Weeks → Activities → Competencies.

### Files touched
- `docs/PRAGMATIC_IMPLEMENTATION_GUIDE.md` — Enhanced Weeks 3-4 section with complete structure examples and weekly organization
- `docs/USER_WORKFLOWS.md` — Added detailed structure workflow and complete Allied Assist Program example

---

## [2026-02-13] — Trainer Coach Capability Documentation

**Branch**: `front-end-explorations`

### What changed
- Added optional Trainer Coach capability to pragmatic implementation guide (Week 6)
- Updated user workflows with Trainer Coach setup and operational procedures
- Documented cohort-based approach (no new role needed)
- Added trainer performance monitoring views and metrics
- Included Trainer Training Program (meta-course) concept

### Why
Trainer quality oversight is critical for program success. Rather than creating a 4th role, we enhance the existing Trainer role with an optional coaching capability. Trainers in the "Trainer Coaches" cohort see additional dashboard sections showing all trainers' performance metrics, enabling them to identify struggling trainers and provide targeted coaching. This approach is simpler than a separate role and allows coaches to also deliver training.

### Files touched
- `docs/PRAGMATIC_IMPLEMENTATION_GUIDE.md` — Added Week 6 (optional) with Trainer Coach implementation
- `docs/USER_WORKFLOWS.md` — Added Trainer Coach workflows, setup procedures, and monitoring capabilities

---

## [2026-02-13] — User Workflows Documentation

**Branch**: `front-end-explorations`

### What changed
- Created comprehensive user workflows documentation covering all four roles
- Documented detailed step-by-step procedures for common tasks
- Added missing pieces: badge system, trainer performance monitoring, automated reporting
- Included critical dependencies and workflow sequences
- Provided time estimates for each workflow

### Why
Users need a complete reference for understanding how to use the system. This document serves as both training material and operational reference, covering everything from initial setup to daily operations. It addresses the complete user journey including badge awarding (trainers), trainer performance monitoring (system admin), and automated reporting setup.

### Files touched
- `docs/USER_WORKFLOWS.md` — Complete workflows for System Admin, Program Owner, Trainer, and Learner roles with detailed steps

---

## [2026-02-13] — Pragmatic Implementation Guide

**Branch**: `front-end-explorations`

### What changed
- Created comprehensive implementation guide for pragmatic approach
- Documented 5-week plan with detailed tasks for each week
- Provided code examples for role separation, cohort filtering, stream support, and dashboard polish
- Explained Moodle core concepts for developers new to Moodle
- Documented benefits, tradeoffs, and decision criteria

### Why
The pragmatic approach leverages Moodle's existing features (courses, sections, cohorts, roles) rather than building custom systems. This guide provides concrete implementation steps with code examples so developers can execute the 5-week plan. It explains what Moodle already provides and how to configure it correctly, making it accessible to developers unfamiliar with Moodle.

### Files touched
- `docs/PRAGMATIC_IMPLEMENTATION_GUIDE.md` — Complete implementation guide with code examples, tradeoffs, and decision criteria

---

## [2026-02-13] — Pragmatic Approach Analysis

**Branch**: `front-end-explorations`

### What changed
- Created pragmatic analysis of requirements vs. Moodle capabilities
- Identified what Moodle already provides (competency framework, cohorts, roles, courses)
- Proposed 5-week pragmatic path vs. 24-week comprehensive rebuild
- Defined 4 phases: Role Separation (1w), Trainer Filtering (1w), Stream Support (2w), Dashboard Polish (1w)

### Why
The comprehensive requirements propose building custom entities (Programs, Streams, Content Assets) that largely duplicate existing Moodle features. This analysis applies RULE 1 (Simplest Solution First) to identify what we can achieve by configuring and extending Moodle rather than rebuilding it. The pragmatic approach delivers 80% of the value in 20% of the time by using courses as programs, sections as streams, and custom roles for RBAC.

### Files touched
- `.kiro/specs/ux-simplification/pragmatic-approach.md` — Complete analysis with tradeoffs and recommendation

---

## [2026-02-13] — UX Simplification & RBAC Requirements Complete

**Branch**: `front-end-explorations`

### What changed
- Completed comprehensive requirements document for UX simplification and RBAC enhancement
- Aligned requirements with PRD (Role, architecture and more.md) and 40 user stories
- Defined 5 custom roles: System Admin, Program Owner, Trainer, Trainer Coach, Learner
- Specified Program/Stream/Learning Path architecture separate from Moodle courses
- Defined Content Asset Library for reusable, versioned content
- Created 12-sprint roadmap (24 weeks / 6 months)
- Added traceability matrix mapping all 40 user stories to functional requirements

### Why
The current Moodle implementation has fundamental architectural misalignment with the required learning system. Generic LMS roles (Manager, Teacher, Student) don't support the 3-layer responsibility model (Learning Design Authority, Delivery & Enablement, Oversight & Insight). This requirements document provides a complete specification for rebuilding the system to support Programs with Focus Streams, role-based dashboards, and reusable content assets.

### Files touched
- `.kiro/specs/ux-simplification/requirements.md` — Completed all sections: roles, functional requirements, success metrics, timeline, traceability matrix

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
