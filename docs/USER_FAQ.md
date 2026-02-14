# User FAQ - Getting Work Done in SCEH LMS

Last updated: 2026-02-14

Audience: Program Owners, System Admins, Trainers, Trainer Coaches, Learners

Purpose: Practical answers to "How do I do X?" using the current system.

---

## Quick Navigation

1. Getting Started
2. What each role can do
3. Program setup (end-to-end)
4. Competency mapping
5. Cohorts and enrollments
6. Streams and learner flow
7. Reporting and monitoring
8. Workflow Queue questions
9. Troubleshooting
10. What is configured vs what still needs setup

---

## 1) Getting Started

### Q: Where do I log in?
A: Use your Moodle URL (current dev stack typically `http://127.0.0.1:8081`) and log in with your user account.

### Q: I see "SCEH" branding. Is this expected?
A: Yes. Site name and theme are set to SCEH.

### Q: I am new. Which page should I use first?
A:
- Learner: Dashboard (`/my/`) and course page.
- Trainer: Dashboard (`/my/`) and assigned course cards.
- Program Owner: Dashboard (`/my/`) then program/category management and competency framework.
- System Admin: Dashboard (`/my/`) then users/cohorts/reports.

### Q: What is the "Workflow Queue"?
A: A role-based task panel with 3 buckets:
- `Do Now` (urgent)
- `This Week` (planned)
- `Watchlist` (monitoring/risk)

---

## 2) What Each Role Can Do Right Now

### System Admin
- Manage cohorts
- Access competency framework
- View attendance reports
- View training evaluation dashboard
- Access badge management (site badges)
- Access program structure and custom reports
- Manage roster rules (if capability assigned)
- Use workflow queue for operational tasks

### Program Owner
- Access competency framework
- Access custom reports
- Run stream setup check
- Access assigned program categories
- Use workflow queue for design and quality tasks

### Trainer
- Access attendance reports
- Open assigned cohort course(s)
- Open stream-specific course sections
- (If in trainer-coaches cohort) access training evaluation dashboard
- Use workflow queue for grading/delivery follow-up

### Trainer Coach (enhanced trainer)
- All trainer capabilities
- Additional evaluation oversight card(s)
- Workflow queue includes monitoring/intervention-style tasks

### Learner
- Access case logbook/course areas
- View my competencies
- Track attendance/badges/progress
- See dynamic "Your Stream: ..." card after stream selection
- Use workflow queue for next steps and watchlist
- Timeline remains visible for learners

---

## 3) Program Setup (End-to-End)

### Q: How do I create a new program?
A (Program Owner):
1. Create or choose course category for the program.
2. Create the program course.
3. Structure sections:
- `Common Foundation`
- `STREAM - Front Desk Management` (example)
- `STREAM - Doctor Assistance`
- `STREAM - Medical Records`
4. Add a stream choice activity (for learner stream selection).
5. Add activities/resources in common and stream sections.
6. Map activities to competencies.
7. Set completion/assessment/badge criteria.
8. Run stream setup check and fix issues before launch.

### Q: How do I know program setup is valid?
A:
- Use the `Stream Setup Check` page/card.
- It validates:
- common section naming,
- stream section presence,
- stream choice activity with options.

### Q: Who launches the program?
A:
- Program Owner prepares structure/content/rules.
- System Admin completes users/cohorts/enrollment/reporting setup.
- Trainer begins delivery for assigned cohort(s).

---

## 4) Competency Mapping

### Q: How does competency mapping work here?
A: Hybrid model.
- Core Moodle LP is primary for competency framework and activity mappings.
- `local_sceh_rules` adds rule automation:
- attendance threshold rules for competency access checks,
- roster-to-competency evidence automation.

### Q: How do I map an activity to a competency?
A (Program Owner):
1. Open course and activity settings.
2. Go to the Competencies section/tab in activity settings.
3. Select relevant competency(ies).
4. Save.

### Q: Where do learners see competencies?
A:
- Learners can open "My Competencies" card.
- Under the hood this routes to Moodle LP plans (`/admin/tool/lp/plans.php?userid=...`).

### Q: Is competency access hard-blocked by attendance everywhere?
A: Not universally. Current rules evaluate and log attendance checks and show messages in relevant competency flows. Full hard enforcement depends on access path and should be validated in live content pilots.

---

## 5) Cohorts and Enrollments

### Q: How do I add people to a cohort?
A (System Admin):
1. Open `Manage Cohorts`.
2. Create/select a cohort.
3. Add users to that cohort.

### Q: How do I assign a trainer to a cohort?
A (System Admin):
1. Add trainer user to the cohort and assign trainer role (system/context as designed).
2. Confirm trainer can see assigned course cards on dashboard.

### Q: How do I enroll a cohort into a program?
A (System Admin):
1. Use cohort enrollment in the target course.
2. Select the cohort.
3. Verify learner and trainer dashboard views after enrollment.

### Q: What if users cannot access expected cards/pages?
A:
- Check role assignment and capabilities first.
- Re-check cohort enrollment.
- Verify page-level permissions for the target plugin/page.

---

## 6) Streams and Learner Flow

### Q: How does stream selection work?
A:
1. Learner completes stream choice activity in course.
2. System reads choice response.
3. Response text is matched to `STREAM - ...` section name.
4. Learner gets dynamic stream card linking to selected stream section.

### Q: What if stream card does not appear?
A:
- Check that stream choice activity exists and has options.
- Ensure learner submitted a choice.
- Ensure stream section names match expected naming pattern.

### Q: Can learners switch streams later?
A: Current workflow treats selection as fixed for progression. If your policy allows changes, handle through admin/program-owner process and re-validate downstream impact.

---

## 7) Reporting and Monitoring

### Q: How do I see reports?
A:
- System Admin/Program Owner: use `Custom Reports` card and report builder.
- Trainer Coach / eligible roles: use `Training Evaluation` dashboard.
- Trainer/Learner: role-specific cards and workflow queue provide operational visibility.

### Q: What reports should be configured first?
A:
1. Weekly cohort progress summary
2. Monthly program health summary
3. Trainer performance review
4. At-risk learner list

### Q: Are automated reports already built?
A: Supported via Moodle core report builder scheduling. They still need configuration (schedule, recipients, filters, delivery format).

---

## 8) Workflow Queue Questions

### Q: Is Workflow Queue static?
A: No. It is dynamic and role-based.

### Q: What drives queue items?
A:
- Moodle-native signals: events, cohorts, grading backlog, stream checks, task health.
- Lightweight derived rules for prioritization.

### Q: Why does learner still see Timeline?
A: By design. Timeline is retained for learner activity feed. Non-learner roles use workflow-first view.

### Q: Can we tune what appears in Do Now vs This Week vs Watchlist?
A: Yes. Thresholds and item logic can be adjusted in dashboard helper logic.

---

## 9) Troubleshooting

### Q: I see default blocks only / no SCEH cards.
A:
1. Confirm `theme_sceh` is active.
2. Confirm `block_sceh_dashboard` is present on dashboard.
3. Verify user role/capability assignment.
4. Purge caches.

### Q: Card click opens permission error.
A:
1. Confirm capability for destination page.
2. Confirm role assignment in correct context.
3. Confirm plugin exists and URL route is valid.

### Q: Card click opens missing parameter error.
A: This usually means required query params are missing from route. Use known-safe dashboard links and validate against regression tests.

### Q: Attendance/Badge/Rules pages show missing plugin/file.
A: Verify plugin/module is installed/mounted in current MoodleHQ stack and caches are purged.

---

## 10) What Is Configured vs What Still Needs Setup

### Configured in current build
- Role-based dashboard cards
- Workflow queue buckets
- Stream setup checks and stream progress pages
- Learner stream card behavior
- Trainer coach enhancement path
- Theme-based login/dashboard UX improvements

### Needs environment-specific setup before real rollout
- Final report schedules and recipients
- Communication defaults (forums/announcements/messages)
- Backup and restore runbooks for deployed infrastructure
- Competency evidence policy standards
- KPI threshold tuning for watchlists/interventions

---

## Related Docs

- `docs/USER_WORKFLOWS.md`
- `docs/SYSTEM_FAQ.md`
- `docs/OPERATIONS_GUIDE.md`
- `docs/MOCK_USERS_SETUP.md`
