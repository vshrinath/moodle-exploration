# Workflow Simulation Sequence and Checklist

Last updated: 2026-02-18

## Purpose

Define the execution order for workflow simulations and capture pass/fail outcomes before broader rollout.

## Recommended Simulation Order

1. Sysadmin identity and access bootstrap
- Create users with unique IDs.
- Assign roles: Program Owner, Trainer, Learner, optional Trainer Coach.
- Verify role capabilities match expected permissions.

2. Sysadmin cohort model setup
- Create cohorts.
- Add users to cohorts.
- Verify cohort membership and visibility boundaries.

3. Sysadmin category governance
- Create course categories.
- Assign Program Owners to categories.
- Verify cross-category isolation.

4. Program Owner program draft creation and content review
- Create a new program/course container.
- Set initial structure (foundation plus streams).
- Add at least one course/learning unit and one quiz.
- Verify Program Owner can review courses and quizzes they added.

5. Program Owner learning design configuration
- Configure competencies, assessments, completion rules, badges, and stream-choice logic.

6. Program Owner pre-publish validation
- Validate conditional access, competency mapping, grading rules, completion criteria, and badge criteria.

7. Program Owner publish workflow
- Publish program.
- Verify it is enrollment-ready and visible in the expected published state.

8. Sysadmin delivery activation
- Enroll cohorts with cohort sync.
- Assign trainer access.
- Verify trainer and learner enrollments.

9. Trainer execution workflow
- Mark attendance.
- Review submissions, grade, and provide feedback.
- Award manual badges where required.
- Monitor cohort progress.

10. Learner journey workflow (desktop + mobile)
- Complete onboarding and foundation content.
- Select stream.
- Complete stream activities and assessments.
- Verify key journey steps work on desktop and mobile.

11. Intervention loop workflow
- Trigger risk signals (for example overdue work, grading lag, inactive learners).
- Assign owner by role.
- Execute intervention.
- Recheck that risk signal reduces or clears.

12. Reporting and workflow queue operations
- Validate role-based Workflow Queue (Do Now, This Week, Watchlist).
- Validate scheduled reports and recipient targeting.

13. Optional Trainer Coach workflow
- Verify enhanced dashboard access for trainer-coach cohort members.
- Validate cross-trainer visibility and coaching actions.
- Verify read-only boundaries for grading/content/admin actions.

## Execution Checklist Template (Pass/Fail)

Use one section per workflow execution run.

### Template

**Workflow ID/Name:**  
**Run date:**  
**Tester:**  
**Environment:**  
**Status:** Not started | Pass | Fail | Blocked

**Preconditions**
- [ ] Required users/roles exist
- [ ] Required course/program objects exist
- [ ] Required plugin/config flags enabled

**Test Steps**
1.  
2.  
3.  

**Pass Criteria**
- [ ] Expected UI/action is available to the role
- [ ] Expected data is persisted correctly
- [ ] Expected visibility boundaries are enforced
- [ ] No permission error or unexpected warning

**Fail Criteria**
- [ ] Required action is missing or inaccessible for valid role
- [ ] Incorrect data write/update behavior
- [ ] Incorrect visibility (overexposed or hidden data)
- [ ] Blocking errors, broken navigation, or inconsistent state

**Evidence**
- URLs/pages visited:
- User IDs/roles used:
- Program/Course/Cohort IDs used:
- Screenshots or logs:

**Defects/Notes**
- Severity:
- Repro steps:
- Owner:

## Workflow-Specific Pass/Fail Criteria

### WF-01: Sysadmin identity and access bootstrap
Pass if:
- [ ] Users are created with unique IDs and expected profile fields.
- [ ] Roles are assigned without conflict.
- [ ] Each role can access expected areas and cannot access restricted areas.
Fail if:
- [ ] Duplicate or missing IDs are accepted without warning.
- [ ] Assigned role cannot access required functions.
- [ ] Role can access pages/actions outside intended scope.

### WF-02: Sysadmin cohort model setup
Pass if:
- [ ] Cohorts are created and populated correctly.
- [ ] Cohort membership reflects in enrollment-ready views.
- [ ] Non-members are excluded from cohort-scoped views/actions.
Fail if:
- [ ] Cohort member list diverges from expected assignments.
- [ ] Cohort boundaries are not enforced.

### WF-03: Sysadmin category governance
Pass if:
- [ ] Program Owner can create/view programs only in assigned category.
- [ ] Cross-category visibility is blocked by default.
Fail if:
- [ ] Program Owner can create/edit in unauthorized categories.
- [ ] Category scoping is inconsistent across screens.

### WF-04: Program Owner draft creation and content review
Pass if:
- [ ] Program draft is created with expected section structure.
- [ ] Program Owner can review courses/learning units and quizzes they added.
- [ ] Saved draft remains editable and stable after reload.
Fail if:
- [ ] Program Owner cannot review/edit added courses or quizzes.
- [ ] Structure is not persisted or reload causes missing items.

### WF-05: Program Owner learning design configuration
Pass if:
- [ ] Competencies, completion, badge rules, and stream logic can be configured.
- [ ] Configuration values persist and are visible in validation views.
Fail if:
- [ ] Required configuration cannot be set.
- [ ] Rules save partially or inconsistently.

### WF-06: Program Owner pre-publish validation
Pass if:
- [ ] Validation catches real configuration issues.
- [ ] Clean configuration passes without false blockers.
Fail if:
- [ ] Critical misconfigurations are not surfaced.
- [ ] Validation blocks a correctly configured program.

### WF-07: Program Owner publish workflow
Pass if:
- [ ] Publish action transitions program to expected published state.
- [ ] Program is available for downstream enrollment flow.
Fail if:
- [ ] Publish succeeds but leaves draft-only behavior.
- [ ] Published state is not visible to downstream roles.

### WF-08: Sysadmin delivery activation
Pass if:
- [ ] Cohort sync enrolls correct learners/trainers.
- [ ] Enrollment updates are reflected in role dashboards.
Fail if:
- [ ] Wrong users are enrolled.
- [ ] Enrollment appears correct in one view but not another.

### WF-09: Trainer execution workflow
Pass if:
- [ ] Trainer can mark attendance and grade submissions.
- [ ] Feedback and badge actions complete as expected.
- [ ] Cohort progress indicators update after actions.
Fail if:
- [ ] Trainer actions error or do not persist.
- [ ] Progress indicators do not reflect completed trainer actions.

### WF-10: Learner journey workflow (desktop + mobile)
Pass if:
- [ ] Learner can complete onboarding, foundation, stream selection, and stream progression.
- [ ] Same critical path works on desktop and mobile.
- [ ] Completion/progress signals are consistent across devices.
Fail if:
- [ ] Any critical learner step is blocked on mobile.
- [ ] Progress state diverges between desktop and mobile.

### WF-11: Intervention loop workflow
Pass if:
- [ ] Risk condition is detected and routed to correct owner role.
- [ ] Intervention action reduces or clears risk on recheck.
Fail if:
- [ ] Risk is not detected or owner routing is incorrect.
- [ ] Recheck does not reflect intervention outcome.

### WF-12: Reporting and workflow queue operations
Pass if:
- [ ] Queue buckets (Do Now/This Week/Watchlist) populate by role.
- [ ] Scheduled reports run and route to intended recipients.
Fail if:
- [ ] Queue items are missing/misclassified for role context.
- [ ] Reports fail silently or go to wrong recipients.

### WF-13: Optional Trainer Coach workflow
Pass if:
- [ ] Trainer Coach has enhanced visibility across trainers/cohorts.
- [ ] Read-only boundaries are enforced for restricted actions.
Fail if:
- [ ] Coach cannot access expected monitoring views.
- [ ] Coach can perform restricted write actions.
