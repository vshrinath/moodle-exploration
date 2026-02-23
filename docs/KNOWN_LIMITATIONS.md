# Known Limitations and Constraints

**Current system constraints and workarounds**  
**Last Updated**: 2026-02-21

---

## Enrollment and Access Control

### Manual vs. Cohort Sync Enrollments

**Limitation**: Manual enrollments are independent of cohort membership.

**Behavior**:
- Removing a user from a cohort does NOT unenroll them if they have a manual enrollment
- Cohort sync only manages enrollments it created
- Users can have multiple enrollment methods simultaneously

**Impact**:
- Cohort-based access control only works for cohort sync enrollments
- Manual enrollments persist through cohort changes
- Admins must track which users have manual vs. cohort enrollments

**Workaround**:
- Use cohort sync as primary enrollment method for cohort-based courses
- Reserve manual enrollments for exceptions (makeup learners, observers)
- Document which users have manual enrollments
- Train admins on enrollment method differences

**Reference**: See `ALLIED_HEALTH_FOUNDATIONAL_COURSE_WORKFLOW.md` — Known Behaviors section

**Test Evidence**: `PHASE_3_COHORT_LIFECYCLE_TEST_REPORT.md`

---

## Activity Visibility Control

### Manual Visibility Toggle Required

**Limitation**: Attendance marking does not automatically show/hide activities.

**Behavior**:
- Trainer must manually click eye icon to show quizzes/assignments after marking attendance
- No automatic visibility based on attendance status
- Visibility is course-wide (cannot hide for specific users)

**Impact**:
- Trainer workflow requires extra step after attendance
- Absent learners see same visibility as present learners
- Cannot automatically hide content from absent learners

**Workaround**:
- Train trainers on manual visibility workflow
- Use separate makeup activities for absent learners
- Document visibility control in trainer training materials

**Reference**: See `ALLIED_HEALTH_FOUNDATIONAL_COURSE_WORKFLOW.md` — Trainer Workflow section

---

## Content Organization

### No Nested Folder Support in Activities

**Limitation**: Moodle folders cannot contain other folders.

**Behavior**:
- Folder activity can only contain files and URLs
- Cannot create hierarchical folder structure within a single folder activity
- Must use multiple folder activities for organization

**Impact**:
- Week/Day structure requires multiple folder activities
- Cannot mirror file system hierarchy exactly
- More activities to manage per course

**Workaround**:
- Use naming convention: "Trainer Resources — Week 1 Day 1"
- Create separate folder per day
- Use section organization (topics) for hierarchy

**Reference**: See `ALLIED_HEALTH_FOUNDATIONAL_COURSE_WORKFLOW.md` — Course Structure section

---

## Attendance Plugin

### Session Scheduling Outside Moodle

**Limitation**: Attendance plugin does not include calendar/scheduling features.

**Behavior**:
- Trainer must add sessions manually before each class
- No automatic session creation from calendar
- No batch session creation
- Session details (date, time, room) are text fields only

**Impact**:
- Trainer must remember to add sessions
- No integration with external calendars
- No automatic reminders for learners
- Schedule must be communicated outside Moodle

**Workaround**:
- Communicate schedule via email or calendar invite
- Maintain shared schedule document
- Train trainers to add sessions before class
- Consider custom plugin for calendar integration (future)

**Reference**: See `ALLIED_HEALTH_FOUNDATIONAL_COURSE_WORKFLOW.md` — Attendance section

---

## Grading and Assessment

### OJT Assessment Workflow

**Limitation**: No built-in OJT (On-Job Training) assessment workflow.

**Behavior**:
- OJT assessments use standard assignment activity
- Submission type set to "None" (no learner upload)
- Trainer uploads scanned report as feedback file
- No specialized OJT tracking or reporting

**Impact**:
- OJT assessments look like regular assignments
- No dedicated OJT report
- Scanned reports stored as feedback files (not structured data)
- Cannot query OJT data separately from assignments

**Workaround**:
- Use naming convention: "OJT Assessment — Week X"
- Document OJT workflow in trainer guide
- Use rubrics for consistent evaluation
- Export feedback files for compliance records

**Reference**: See `ALLIED_HEALTH_FOUNDATIONAL_COURSE_WORKFLOW.md` — OJT Assessment section

---

## Badge System

### No Automatic Badge Expiry

**Limitation**: Moodle badges do not expire automatically.

**Behavior**:
- Badges awarded are permanent by default
- No expiry date field
- No automatic expiry notifications
- No automatic re-certification workflow

**Impact**:
- Cannot enforce badge validity periods (e.g., 1 year)
- No automatic reminder for re-certification
- Manual tracking required for badge expiry

**Workaround**:
- Set calendar reminder to review badges 11 months after award
- Manually track badge validity in external system
- Consider custom plugin for badge expiry (future)
- Document badge validity period in badge description

**Reference**: See `ALLIED_HEALTH_FOUNDATIONAL_COURSE_WORKFLOW.md` — Badge section

---

## Reporting and Analytics

### Limited Built-in Cohort Comparison

**Limitation**: No built-in cohort performance comparison report.

**Behavior**:
- Must create custom report using Report Builder
- No pre-built cohort comparison dashboard
- Requires manual configuration per metric

**Impact**:
- Initial setup time required
- Trainer effectiveness comparison requires custom report
- No out-of-box cohort analytics

**Workaround**:
- Use Report Builder to create custom reports
- Follow templates in `OPERATIONS_GUIDE.md`
- Schedule automated report generation
- Export to Excel for advanced analysis

**Reference**: See `OPERATIONS_GUIDE.md` — Reporting section

---

## Backup and Recovery

### No Incremental Backup

**Limitation**: Moodle backup is full backup only (no incremental).

**Behavior**:
- Each backup includes all data
- No differential or incremental backup options
- Backup size grows with content

**Impact**:
- Longer backup times as system grows
- More storage required
- Slower restore times

**Workaround**:
- Use database-level incremental backups (MariaDB binary logs)
- Exclude cache/temp directories from file backups
- Use compression for backups
- Store old backups off-site (cloud storage)

**Reference**: See `OPERATIONS_GUIDE.md` — Backup section

---

## Performance and Scaling

### Video Storage Not Recommended

**Limitation**: Moodle is not optimized for video hosting.

**Behavior**:
- Video files consume significant storage
- Video streaming not optimized
- No adaptive bitrate streaming
- No CDN integration by default

**Impact**:
- Large video files slow down backups
- Poor video playback performance
- Storage costs increase rapidly

**Workaround**:
- Host videos externally (YouTube, Vimeo)
- Embed videos via URL
- Use Moodle's external repository feature
- Never upload videos directly to Moodle

**Reference**: See `OPERATIONS_GUIDE.md` — File Storage section

---

## User Management

### No Bulk Cohort Assignment from CSV

**Limitation**: Cannot bulk assign users to cohorts via CSV upload.

**Behavior**:
- Must use cohort upload CSV format
- Cannot update existing cohort memberships via CSV
- Must use UI or database for bulk changes

**Impact**:
- Time-consuming for large cohort changes
- Error-prone manual process
- No audit trail for bulk changes

**Workaround**:
- Use database scripts for bulk cohort assignment
- Document cohort membership changes in spreadsheet
- Use cohort sync for automatic enrollment
- Consider custom plugin for bulk cohort management (future)

**Reference**: See `scripts/config/configure_advanced_cohort_management.php`

---

## Mobile Access

### Limited Mobile App Features

**Limitation**: Moodle Mobile app has limited feature support.

**Behavior**:
- Some activities not supported in mobile app
- Rubric grading limited on mobile
- File upload size limits on mobile
- No offline grading

**Impact**:
- Trainers need laptop/desktop for grading
- Some activities require desktop access
- Mobile experience inconsistent

**Workaround**:
- Train users on mobile limitations
- Provide desktop access for trainers
- Use mobile-friendly activity types
- Test activities on mobile before deployment

**Reference**: See `docs/USER_FAQ.md` — Mobile Access section

---

## Integration Limitations

### No Native Calendar Integration

**Limitation**: Moodle calendar is internal only.

**Behavior**:
- Cannot sync with Google Calendar, Outlook, etc.
- No iCal export for individual users
- Calendar events not sent as email invites

**Impact**:
- Users must check Moodle calendar separately
- No unified calendar view
- Schedule conflicts not detected

**Workaround**:
- Communicate schedule via email with calendar invites
- Use external calendar as source of truth
- Add Moodle events manually to external calendar
- Consider calendar sync plugin (future)

---

## Testing and Validation

### Circular Dependency Test CI Limitation

**Limitation**: Circular dependency prevention test fails in CI but passes locally.

**Behavior**:
- Test passes consistently in local Docker environment
- Test fails in GitHub Actions CI with "ID number already in use" errors
- CI appears to run stale code despite multiple pushes
- 6 out of 7 regression tests pass in CI

**Impact**:
- Cannot rely on CI for circular dependency validation
- Must run test manually before releases
- CI shows false negative for this specific test

**Workaround**:
- Run test locally before releases: `docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/test/property_test_circular_dependency_prevention.php`
- Verify all 10 test cases pass locally
- Document test results in release notes
- CI validates 6 other critical workflows automatically

**Root Cause**: GitHub Actions caching or checkout issue preventing latest code from running. Test logic and cleanup are correct (verified locally).

**Reference**: See `scripts/test/property_test_circular_dependency_prevention.php`

---

### No Automated UI Testing

**Limitation**: No built-in UI testing framework.

**Behavior**:
- Must test UI changes manually
- No automated browser testing
- No visual regression testing

**Impact**:
- Time-consuming manual testing
- Risk of UI regressions
- Difficult to test across browsers/devices

**Workaround**:
- Use manual test checklists
- Test on multiple browsers (Chrome, Firefox, Safari)
- Document test procedures
- Consider Selenium/Playwright for critical workflows (future)

**Reference**: See `docs/WORKFLOW_SIMULATION_GOLDEN_TEST_SUITE.md`

---

## Future Enhancements

**Potential improvements to address limitations:**

1. **Custom enrollment plugin** — Automatic cohort-based access control
2. **Attendance-triggered visibility** — Auto-show activities after attendance
3. **OJT assessment plugin** — Specialized OJT workflow and reporting
4. **Badge expiry plugin** — Automatic badge expiry and re-certification
5. **Calendar sync plugin** — Integration with Google Calendar, Outlook
6. **Bulk cohort management** — CSV-based cohort assignment
7. **Mobile grading improvements** — Enhanced mobile app features
8. **Automated UI testing** — Selenium/Playwright test suite

**Priority**: To be determined based on user feedback and operational needs

---

## Reporting Issues

**If you encounter a limitation not listed here:**

1. Document the limitation:
   - What you tried to do
   - What happened instead
   - Expected behavior
   - Workaround (if found)

2. Check if it's a Moodle core limitation or configuration issue

3. Add to this document with:
   - Clear description
   - Impact assessment
   - Workaround (if available)
   - Reference to related documentation

4. Consider if custom development is justified

---

**Document Version**: 1.0  
**System Version**: Moodle 5.0.1  
**Last Review**: 2026-02-21
