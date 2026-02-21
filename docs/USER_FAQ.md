# User FAQ - Getting Work Done in SCEH LMS

Last updated: 2026-02-21

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
8. Course content import
9. Troubleshooting
10. What is configured vs what still needs setup

---

## 1) Getting Started

### Q: Where do I log in?
A: Go to your Moodle URL and log in with your account. Click the **SCEH** logo in the top-left corner to return to your dashboard at any time.

### Q: I see "SCEH" branding. Is this expected?
A: Yes. Site name and theme are set to SCEH (Shroff Charitable Eye Hospital).

### Q: I am new. Which page should I use first?
A: Your **Dashboard** (`/my/`) is your home base. It shows cards relevant to your role:
- **Learner**: Your Stream, Progress, Deadlines, Competencies, Badges.
- **Trainer**: My Courses (expandable), Attendance Reports, Training Evaluation.
- **Program Owner**: Competency Framework, Reports, Stream Setup Check, Category courses.
- **System Admin**: Cohorts, Program Structure, Reports, Training Evaluation, Badges, Competency Framework.

### Q: How do I navigate? I don't see "Dashboard" or "My courses" links.
A: Click the **SCEH** logo in the top-left corner — that's your home button. The header was simplified to reduce clutter.

---

## 2) What Each Role Can Do Right Now

### System Admin
- Manage cohorts (create cohorts, add/remove users)
- Access program structure (course categories)
- Access custom reports via report builder
- View training evaluation dashboard
- Manage site badges
- Access competency framework
- Monitor system health: Cron Tasks, Active Users, Overdue Events

### Program Owner
- Access competency framework
- Access custom reports
- Run stream setup check
- Manage courses in assigned categories
- Bulk import course content

### Trainer
- Access assigned courses (expandable card if multiple courses)
- View attendance reports
- (If trainer-coach) Access training evaluation dashboard

### Learner
- View your stream course content
- Track progress via **My Progress** card (clickable activities)
- See upcoming deadlines with count badge
- View course competencies
- Track earned badges with count badge
- Timeline shows upcoming activities

---

## 3) Program Setup (End-to-End)

### Q: How do I create a new program?
A (Program Owner):
1. Create or choose a course category for the program.
2. Create the program course.
3. Structure sections:
   - `Common Foundation`
   - `STREAM - Front Desk Management` (example)
   - `STREAM - Doctor Assistance`
   - `STREAM - Medical Records`
4. Add a stream choice activity (for learner stream selection).
5. Add activities/resources in common and stream sections.
6. Map activities to competencies.
7. Run stream setup check and fix issues before launch.

### Q: How do I know program setup is valid?
A: Use the **Stream Setup Check** page/card. It validates:
- Common section naming
- Stream section presence
- Stream choice activity with options

### Q: Who launches the program?
A:
- Program Owner prepares structure/content/rules.
- System Admin completes users/cohorts/enrollment/reporting setup.
- Trainer begins delivery for assigned cohort(s).

---

## 4) Competency Mapping

### Q: How does competency mapping work?
A: Hybrid model.
- Core Moodle LP is primary for competency framework and activity mappings.
- `local_sceh_rules` adds rule automation: attendance threshold rules and roster-to-competency evidence automation.

### Q: How do I map an activity to a competency?
A (Program Owner):
1. Open course and activity settings.
2. Go to the Competencies section/tab in activity settings.
3. Select relevant competency(ies).
4. Save.

### Q: Where do learners see competencies?
A: Learners click the **My Competencies** card on the dashboard. This opens the competency view for their enrolled course.

---

## 5) Cohorts and Enrollments

### Q: How do I add people to a cohort?
A (System Admin):
1. Click **Manage Cohorts** on the dashboard.
2. Create or select a cohort.
3. Click the members icon to add users to that cohort.

### Q: How do I connect a cohort to a course?
A (System Admin):
1. Go to the course via **Program Structure**.
2. Navigate to course → Participants → Enrollment methods.
3. Add "Cohort sync" and select the cohort + role (Student or Teacher).

### Q: How do I add a trainer?
A (System Admin):
1. Add the trainer user to a cohort, OR
2. Go to the course → Participants → Enroll Users → select the person and assign the Teacher role.

### Q: What if users cannot access expected cards/pages?
A:
1. Check role assignment and capabilities first.
2. Re-check cohort enrollment.
3. Verify page-level permissions for the target plugin/page.
4. Purge caches if recently changed.

---

## 6) Streams and Learner Flow

### Q: How does stream selection work?
A:
1. Learner completes stream choice activity in course.
2. System reads choice response.
3. Response text is matched to `STREAM - ...` section name.
4. Learner gets a dynamic stream card on the dashboard linking to selected stream section.

### Q: What if the stream card does not appear?
A:
- Check that a stream choice activity exists and has options.
- Ensure the learner has submitted a choice.
- Ensure stream section names match the expected naming pattern (`STREAM - ...`).

### Q: Can learners switch streams later?
A: Currently, selection is treated as fixed for progression. If your policy allows changes, handle through admin/program-owner process.

---

## 7) Reporting and Monitoring

### Q: How do I see reports?
A:
- System Admin / Program Owner: click **Custom Reports** on the dashboard.
- Trainer Coach: click **Training Evaluation** card.

### Q: What reports should be configured first?
A:
1. Weekly cohort progress summary
2. Monthly program health summary
3. Trainer performance review
4. At-risk learner list

### Q: Are automated reports already built?
A: Supported via Moodle's report builder scheduling. They still need configuration (schedule, recipients, filters, delivery format).

---

## 8) Course Content Import

### Q: Can we create courses by uploading content packages?
A: Yes. Use the **Package Importer** (available to Program Owners and System Admins):
1. Upload a `.zip` file with the course content folder structure.
2. System validates and previews the content.
3. Select which activities to import (new items pre-selected, existing items unselected).
4. Confirm and import.

### Q: What goes in the ZIP file?
A: Organized folder structure with:
- Content files (PDF, Word, PowerPoint, media)
- Quiz CSV files
- Links CSV for YouTube/external URLs
- Download the template from the import page for the correct structure.

### Q: What about updating existing course content?
A: Use the Update page to replace individual files, or re-import via the Package Importer for bulk updates.

---

## 9) Troubleshooting

### Q: I see default blocks only / no SCEH cards.
A:
1. Confirm `theme_sceh` is active.
2. Confirm `block_sceh_dashboard` is present on the dashboard.
3. Verify user role/capability assignment.
4. Purge caches.

### Q: Card click opens permission error.
A:
1. Confirm capability for destination page.
2. Confirm role assignment in correct context.
3. Confirm plugin exists and URL route is valid.

### Q: My Progress shows "Not tracked" on activities.
A: Completion tracking may not be enabled for those activities. Run the completion tracking setup script:
```
php scripts/config/configure_completion_tracking.php --apply
```

### Q: I can't see a course I'm assigned to.
A: Check that the course is visible (not hidden) and that cohort enrollment is active. Trainers can see hidden courses; learners cannot.

---

## 10) What Is Configured vs What Still Needs Setup

### Configured in current build
- Role-based dashboard cards (cleaned up per role)
- Completion tracking (quiz→grade, resource→view, assign→submit)
- Stream setup checks and stream progress pages (with clickable activities)
- Learner stream card behavior
- Trainer expandable course card
- Simplified header navigation (SCEH logo = home)
- Course package importer with validation and preview
- Theme-based login/dashboard UX improvements

### Needs environment-specific setup before real rollout
- Final report schedules and recipients
- Communication defaults (forums/announcements/messages)
- Backup and restore runbooks for deployed infrastructure
- Competency evidence policy standards
- Badge criteria configuration
- Default activity completion rules for new courses (via course defaults)

---

## Related Docs

- `docs/SYSTEM_FAQ.md` — Technical system decisions
- `docs/COURSE_PACKAGE_IMPORT_BLUEPRINT.md` — Import system design
- `docs/ALLIED_HEALTH_FOUNDATIONAL_COURSE_WORKFLOW.md` — Allied Health workflow
- `docs/MOCK_USERS_SETUP.md` — Mock user configuration
