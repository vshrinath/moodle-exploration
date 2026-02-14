# System FAQ - SCEH LMS

Last updated: 2026-02-14

## 1) How does competency mapping work in this system?
It is hybrid:
- Core Moodle competency mapping is the primary model. Program Owners map activities to competencies in Moodle LP.
- `local_sceh_rules` adds rule-based automation:
- Attendance threshold rules linked to competencies (`competency + course -> threshold`).
- Roster rules linked to competencies (`rostertype -> competency`) for auto evidence.

## 2) Is competency progression fully controlled by custom code?
No. Core Moodle LP remains the source of truth for competency structures, plans, and activity mapping. Custom rules add operational behavior (attendance checks, roster evidence automation).

## 3) What is the source of truth for stream selection?
Learner stream choice is read from Moodle Choice responses in-course. The selected option is mapped to a `STREAM - ...` section by name.

## 4) Why do we have both Timeline and Workflow Queue?
- Timeline is Moodle core activity feed.
- Workflow Queue is SCEH role-based action prioritization (`Do Now`, `This Week`, `Watchlist`).
- Current policy: Timeline is shown for learners; hidden for non-learner operational roles.

## 5) What data drives Workflow Queue items?
Mostly Moodle-native data:
- Events (overdue/upcoming),
- cohorts/enrollments,
- grading backlog,
- stream setup checks,
- task health.
Some items are lightweight derived rules from this base data.

## 6) Where is the color scheme defined?
Theme tokens are centralized in:
- `theme_sceh/scss/tokens.scss`
Card/status styles consume those tokens in:
- `local_sceh_rules/styles/sceh_card_system.css`
- `block_sceh_dashboard/styles.css`

## 7) Why did the site title say "New Site" before?
That is Moodle default site course naming. It was updated to `SCEH` (shortname and fullname).

## 8) Where should process and role-flow questions be documented?
- Role workflows and composite lifecycle flows: `docs/USER_WORKFLOWS.md`
- Operations and reporting setup: `docs/OPERATIONS_GUIDE.md`
- This file (`docs/SYSTEM_FAQ.md`) for quick answers and onboarding clarity.

## 9) What is still configuration work vs feature development?
Mostly configuration/governance:
- report schedules,
- communication defaults,
- backup policy,
- competency evidence standards,
- KPI threshold tuning for watchlists.

## 10) How should we extend this FAQ?
Add short, decision-focused Q&A entries:
- what the behavior is,
- where it is configured,
- who owns it,
- and where to change it.
