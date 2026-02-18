# Workflow Simulation - Golden Test Suite

Last updated: 2026-02-18

## Purpose

Shared execution log for workflow simulations with consistent pass/fail criteria and reproducible evidence.

## Execution Rules

- Run workflows in sequence from `WF-01` to `WF-13` unless a blocker requires skipping.
- Log only observed results (no assumptions).
- Mark status as `Pass`, `Fail`, or `Blocked`.
- Include concrete evidence (commands, IDs, and outputs).

## Workflow Sequence

1. `WF-01` Sysadmin identity and access bootstrap
2. `WF-02` Sysadmin cohort model setup
3. `WF-03` Sysadmin category governance
4. `WF-04` Program Owner draft creation and content review
5. `WF-05` Program Owner learning design configuration
6. `WF-06` Program Owner pre-publish validation
7. `WF-07` Program Owner publish workflow
8. `WF-08` Sysadmin delivery activation
9. `WF-09` Trainer execution workflow
10. `WF-10` Learner journey workflow (desktop + mobile)
11. `WF-11` Intervention loop workflow
12. `WF-12` Reporting and workflow queue operations
13. `WF-13` Optional Trainer Coach workflow

## Test Case Template

**Workflow ID/Name:**  
**Run date:**  
**Tester:**  
**Environment:**  
**Status:** Pass | Fail | Blocked

**Pass Criteria**
- [ ] Criterion 1
- [ ] Criterion 2

**Execution Notes**
1. Step performed
2. Step performed

**Evidence**
- Command/UI path:
- Observed output:

**Defects/Blockers**
- ID:
- Severity:
- Owner:
- Notes:

---

## Execution Log

### WF-01: Sysadmin Identity and Access Bootstrap

**Run date:** 2026-02-18  
**Tester:** Codex + Shri  
**Environment:** Local Docker (`moodlehq-dev-moodle-1`, `moodlehq-dev-mysql-1`)  
**Status:** Fail

**Pass Criteria**
- [x] Users exist for target roles (`mock.sysadmin`, `mock.programowner`, `mock.trainer`, `mock.learner`).
- [ ] Users have unique non-empty business IDs (`idnumber`).
- [x] Role assignments exist for Sysadmin, Program Owner, and Trainer.
- [ ] Learner has explicit learner role assignment (or approved equivalent documented for this suite).

**Execution Notes**
1. Verified running containers and Moodle app availability via Docker.
2. Queried mock users for `id`, `username`, `idnumber`, `email`, and system-role assignments.
3. Queried ID-number uniqueness for all `mock.*` users.
4. Performed a capability sample check at system context to collect baseline access evidence.

**Evidence**
- Command: `docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Image}}'`
- Observed output: Moodle and MySQL containers are `Up`.
- Command: role/user query through `docker exec moodlehq-dev-moodle-1 php -r '...'`
- Observed output:
  - `mock.sysadmin` -> role `sceh_system_admin`
  - `mock.programowner` -> role `sceh_program_owner`
  - `mock.trainer` -> role `sceh_trainer`
  - `mock.learner` -> no role at system context
  - all four users have empty `idnumber`
- Command: ID-number uniqueness query through `docker exec moodlehq-dev-moodle-1 php -r '...'`
- Observed output:
  - `MOCK_USER_COUNT 4`
  - `IDNUMBER_ISSUES` for all four users due to empty ID numbers
- Command: capability sample check through `docker exec moodlehq-dev-moodle-1 php -r '...'`
- Observed output (system-context snapshot):
  - Sysadmin sample capabilities: allow
  - Program Owner sample: mixed
  - Trainer/Learner sample: mostly deny at system context (likely context-scoped capabilities, requires later workflow-level verification)

**Defects/Blockers**
- ID: `WF01-001`
- Severity: High
- Owner: Sysadmin setup flow
- Notes: Business IDs (`idnumber`) are empty for all test users, which fails the agreed requirement for role users with different IDs.

- ID: `WF01-002`
- Severity: Medium
- Owner: Workflow definition decision
- Notes: Learner currently uses fallback behavior with no explicit system role assignment. Golden suite needs one explicit decision: either require explicit learner role assignment or formally accept this fallback as pass condition.

### WF-01: Sysadmin Identity and Access Bootstrap (Re-run After Fix)

**Run date:** 2026-02-18  
**Tester:** Codex + Shri  
**Environment:** Local Docker (`moodlehq-dev-moodle-1`, `moodlehq-dev-mysql-1`)  
**Status:** Pass

**Pass Criteria**
- [x] Users exist for target roles (`mock.sysadmin`, `mock.programowner`, `mock.trainer`, `mock.learner`).
- [x] Users have unique non-empty business IDs (`idnumber`).
- [x] Role assignments exist for Sysadmin, Program Owner, and Trainer.
- [x] Learner has explicit learner role assignment (`student` at system context).

**Execution Notes**
1. Queried available SCEH roles and confirmed no `sceh_learner` role exists in this environment.
2. Applied data fix for mock users:
   - set unique `idnumber` for each `mock.*` user
   - assigned `student` role to `mock.learner` at system context
3. Re-ran user/role checks and ID-number uniqueness checks.
4. Re-ran a system-level capability guard check (`moodle/site:config`) for role separation baseline.

**Evidence**
- Command: `docker exec moodlehq-dev-moodle-1 php -r '... roles shortname like sceh% ...'`
- Observed output: `sceh_system_admin`, `sceh_program_owner`, `sceh_trainer`; no `sceh_learner`.
- Command: `docker exec moodlehq-dev-moodle-1 php -r '... set idnumber + role_assign(student) ...'`
- Observed output:
  - `SET_IDNUMBER mock.sysadmin MOCK-SYSADMIN-001`
  - `SET_IDNUMBER mock.programowner MOCK-PROGRAMOWNER-001`
  - `SET_IDNUMBER mock.trainer MOCK-TRAINER-001`
  - `SET_IDNUMBER mock.learner MOCK-LEARNER-001`
  - `ASSIGNED_ROLE mock.learner student`
- Command: `docker exec moodlehq-dev-moodle-1 php -r '... verify users + system roles ...'`
- Observed output:
  - `mock.sysadmin` -> `sceh_system_admin`
  - `mock.programowner` -> `sceh_program_owner`
  - `mock.trainer` -> `sceh_trainer`
  - `mock.learner` -> `student`
  - all four users have non-empty unique `idnumber`
- Command: `docker exec moodlehq-dev-moodle-1 php -r '... IDNUMBER_ISSUES ...'`
- Observed output: `IDNUMBER_ISSUES NONE`
- Command: `docker exec moodlehq-dev-moodle-1 php -r '... CAP_CHECK moodle/site:config ...'`
- Observed output:
  - `mock.sysadmin` -> `ALLOW`
  - `mock.programowner` -> `DENY`
  - `mock.trainer` -> `DENY`
  - `mock.learner` -> `DENY`

**Defects/Blockers**
- `WF01-001`: Resolved in this run.
- `WF01-002`: Resolved for test-suite purposes by enforcing explicit learner assignment (`student`).

### WF-02: Sysadmin Cohort Model Setup

**Run date:** 2026-02-18  
**Tester:** Codex + Shri  
**Environment:** Local Docker (`moodlehq-dev-moodle-1`, `moodlehq-dev-mysql-1`)  
**Status:** Pass

**Pass Criteria**
- [x] Cohort exists and is populated for workflow users.
- [x] Cohort membership is correctly reflected for intended members.
- [x] Non-members are excluded from cohort membership.

**Execution Notes**
1. Queried all cohorts and found existing test cohort `mock-allied-2026` (context `1`).
2. Queried mock-user membership mapping and confirmed cohort assignments.
3. Executed idempotent cohort membership sync for `mock.trainer` and `mock.learner` against `mock-allied-2026`.
4. Verified membership boundary matrix for all four mock users.

**Evidence**
- Command: `docker exec moodlehq-dev-moodle-1 php -r '... get_records(\"cohort\") ...'`
- Observed output: `COHORT 907 mock-allied-2026 Mock Allied Cohort 2026 CTX=1`
- Command: `docker exec moodlehq-dev-moodle-1 php -r '... cohort membership query ...'`
- Observed output:
  - `MEMBERSHIP mock-allied-2026 mock.learner`
  - `MEMBERSHIP mock-allied-2026 mock.trainer`
- Command: `docker exec moodlehq-dev-moodle-1 php -r '... cohort_add_member ...'`
- Observed output:
  - `EXISTS mock-allied-2026 mock.trainer`
  - `EXISTS mock-allied-2026 mock.learner`
- Command: `docker exec moodlehq-dev-moodle-1 php -r '... boundary matrix ...'`
- Observed output:
  - `mock.trainer` and `mock.learner` => `IN`
  - `mock.sysadmin` and `mock.programowner` => `OUT`
  - totals: trainer/learner in 1 cohort each, sysadmin/programowner in 0

**Defects/Blockers**
- None for DB-level cohort setup and membership boundaries.
- Note: UI-level cohort-scoped page visibility is not validated in this run; cover this in a later UI test pass.

### WF-03: Sysadmin Category Governance

**Run date:** 2026-02-18  
**Tester:** Codex + Shri  
**Environment:** Local Docker (`moodlehq-dev-moodle-1`, `moodlehq-dev-mysql-1`)  
**Status:** Pass (after fix)

**Pass Criteria**
- [x] Program Owner is assigned to target category context.
- [x] Program Owner can create courses in assigned category.
- [x] Program Owner cannot create courses in non-assigned categories.
- [x] Program Owner cannot update courses outside assigned category.

**Execution Notes**
1. Queried categories and role assignments for `mock.programowner`.
2. Found governance defect: `mock.programowner` had `sceh_program_owner` at system context (`CTX=1`) and category context (`instance=7`), which granted create access everywhere.
3. Applied minimal fix: removed only system-context assignment, retained category-context assignment.
4. Re-ran capability checks for `moodle/course:create` across all categories and `moodle/course:update` across existing courses.

**Evidence**
- Command: `docker exec moodlehq-dev-moodle-1 php -r '... role assignments for mock.programowner ...'`
- Observed output before fix:
  - `RA ... CTX=1 LEVEL=10 ROLE=sceh_program_owner`
  - `RA ... CTX=2608 LEVEL=40 INSTANCE=7 ROLE=sceh_program_owner`
- Command: `docker exec moodlehq-dev-moodle-1 php -r '... has_capability(moodle/course:create) by category ...'`
- Observed output before fix:
  - `ALLOW` for categories `1..7` (over-permissive)
- Command: `docker exec moodlehq-dev-moodle-1 php -r '... role_unassign at system context ...'`
- Observed output:
  - `UNASSIGNED ROLE=sceh_program_owner USER=mock.programowner CTX=system`
- Command: `docker exec moodlehq-dev-moodle-1 php -r '... recheck course:create by category ...'`
- Observed output after fix:
  - category `7` (`Allied Health Programs`) => `ALLOW`
  - categories `1,2,3,4,5,6` => `DENY`
- Command: `docker exec moodlehq-dev-moodle-1 php -r '... has_capability(moodle/course:update) by course ...'`
- Observed output:
  - course in category `7` => `ALLOW`
  - course in category `2` => `DENY`

**Defects/Blockers**
- ID: `WF03-001`
- Severity: High
- Owner: Role-assignment governance
- Notes: Program Owner was over-permissioned via system-context role assignment, bypassing category isolation. Resolved by removing system assignment and retaining category-scoped assignment.

### WF-04: Program Owner Draft Creation and Content Review

**Run date:** 2026-02-18  
**Tester:** Codex + Shri  
**Environment:** Local Docker (`moodlehq-dev-moodle-1`, `moodlehq-dev-mysql-1`)  
**Status:** Pass (after fix)

**Pass Criteria**
- [x] Program Owner can create a draft course in assigned category.
- [x] Program Owner can add a quiz activity in that draft course.
- [x] Program Owner can review/view and update created course + quiz.
- [x] Evidence shows creation actions executed by Program Owner user.

**Execution Notes**
1. Baseline capability check at assigned category found a gap: Program Owner could create courses, but could not view/manage activities or add quizzes.
2. Applied role capability fix for `sceh_program_owner`:
   - allowed `moodle/course:view`
   - allowed `moodle/course:manageactivities`
   - allowed `moodle/question:add` and `moodle/question:editall`
   - allowed `mod/quiz:addinstance`, `mod/quiz:view`, `mod/quiz:manage`
3. Created draft course while impersonating `mock.programowner`:
   - `WF04_DRAFT_20260218-160045-R1` (`idnumber` `WF04-PROGRAM-20260218-160045-R1`)
4. Added quiz while impersonating `mock.programowner`:
   - quiz instance `10`, course module `223`
5. Verified course/module context capabilities for Program Owner and validated audit log entries for course and course-module creation by `mock.programowner`.

**Evidence**
- Command: category capability check for `mock.programowner`
- Observed output before fix:
  - `moodle/course:create` => `ALLOW`
  - `moodle/course:view` => `DENY`
  - `moodle/question:add` => `DENY`
  - `mod/quiz:add` (invalid cap name in this Moodle) => `DENY`
- Command: role capability assignment for `sceh_program_owner`
- Observed output:
  - `ALLOW moodle/course:view`
  - `ALLOW moodle/course:manageactivities`
  - `ALLOW mod/quiz:addinstance`
  - `ALLOW mod/quiz:view`
  - `ALLOW mod/quiz:manage`
  - `ALLOW moodle/question:add`
- Command: create course as Program Owner (`create_course` with `$USER=mock.programowner`)
- Observed output:
  - `COURSE_CREATED_AS_PO ID=1841 SHORTNAME=WF04_DRAFT_20260218-160045-R1 IDNUMBER=WF04-PROGRAM-20260218-160045-R1`
- Command: add quiz as Program Owner (`add_moduleinfo` with `$USER=mock.programowner`)
- Observed output:
  - `QUIZ_CREATED_AS_PO CMID=223 INSTANCE=10 COURSE=1841`
- Command: capability recheck in course/module context
- Observed output:
  - `moodle/course:view` => `ALLOW`
  - `moodle/course:update` => `ALLOW`
  - `moodle/course:manageactivities` => `ALLOW`
  - `mod/quiz:addinstance` => `ALLOW`
  - `mod/quiz:view` => `ALLOW`
- Command: audit log query for `mock.programowner` and `courseid=1841`
- Observed output includes:
  - `created course course:1841`
  - `created course_module course_modules:223`

**Defects/Blockers**
- ID: `WF04-001`
- Severity: High
- Owner: Program Owner role definition
- Notes: Program Owner role initially lacked course/quiz review and activity-management capabilities required by workflow. Resolved by updating role capabilities listed above.

- ID: `WF04-002`
- Severity: Low
- Owner: Test automation scripts
- Notes: For CLI `add_moduleinfo` quiz creation in this environment, using `quizpassword` is required; setting `password` directly still produced `NULL` on insert. UI flow is expected to set the correct field.

### WF-05: Program Owner Learning Design Configuration

**Run date:** 2026-02-18  
**Tester:** Codex + Shri  
**Environment:** Local Docker (`moodlehq-dev-moodle-1`, `moodlehq-dev-mysql-1`)  
**Status:** Pass (after fix)

**Pass Criteria**
- [x] Competencies can be configured and linked to course context.
- [x] Completion settings can be configured and persisted.
- [x] Badge rules can be configured and persisted.
- [x] Stream-choice logic can be configured (choice activity created).

**Execution Notes**
1. Baseline capability checks showed Program Owner could not configure competencies, badges, or stream-choice logic.
2. Applied missing Program Owner capabilities for WF-05:
   - competency management (`moodle/competency:*` subset)
   - choice activity creation (`mod/choice:addinstance`)
   - badge configuration (`moodle/badges:*` subset)
3. Encountered competency linking blocker:
   - `add_competency_to_course` returned `Course or activity not accessible`.
4. Applied minimal fix path:
   - Created narrow system role `sceh_program_owner_competency` with only competency capabilities.
   - Assigned that role to `mock.programowner` at system context.
   - Assigned `editingteacher` role to `mock.programowner` at test course context for API access checks.
5. Configured WF-05 artifacts on course `WF04_DRAFT_20260218-160045-R1` (`courseid=1841`):
   - enabled course completion
   - linked competency to course
   - created stream-choice activity
   - created course badge with criteria

**Evidence**
- Capability baseline:
  - Before fix: `DENY` for key WF-05 actions (`competencymanage`, `mod/choice:addinstance`, badge config caps).
  - After fix: `ALLOW` at course context for:
    - `moodle/competency:coursecompetencymanage`
    - `moodle/competency:coursecompetencyconfigure`
    - `mod/choice:addinstance`
    - `moodle/badges:createbadge`
    - `moodle/badges:configurecriteria`
- Completion:
  - `COURSE_COMPLETION_UPDATED COURSE=1841`
  - persisted: `ENABLECOMPL=1`, `NOTIFY=1`
- Competency link:
  - initial error: `LINK_ERR Course or activity not accessible.`
  - after role-context fix: `COURSE_COMP_LINKED COURSE=1841 COMP=1`
  - persisted: `COURSE_COMP_COUNT 1` (`COMP=1`)
- Stream-choice logic:
  - first attempt failed due missing module visibility field
  - fixed payload and created choice:
    - `CHOICE_CREATED CMID=224 INSTANCE=2`
  - persisted: `CHOICE_COUNT 1` (recent choice activity in course)
- Badge rules:
  - created course badge + criteria:
    - `BADGE_CREATED ID=104 CRIT=161 COURSE=1841`
  - persisted: `BADGE_COUNT 1` and `CRIT=1`

**Defects/Blockers**
- ID: `WF05-001`
- Severity: High
- Owner: Role/context model for Program Owner competency workflow
- Notes: Category-scoped Program Owner role alone was insufficient for competency linking APIs; required additional system-level competency capability path and course-context access.

- ID: `WF05-002`
- Severity: Medium
- Owner: Test harness payload defaults
- Notes: `choice` module creation via CLI failed initially due missing `visible` in module payload; fixed by setting explicit visibility fields.

### WF-05: Program Owner Learning Design Configuration (Re-run: Competency Creation Verification)

**Run date:** 2026-02-18  
**Tester:** Codex + Shri  
**Environment:** Local Docker (`moodlehq-dev-moodle-1`, `moodlehq-dev-mysql-1`)  
**Status:** Pass

**Pass Criteria**
- [x] Program Owner can create a competency framework.
- [x] Program Owner can create competencies under that framework.
- [x] Program Owner can link created competencies to managed course.

**Execution Notes**
1. Verified `mock.programowner` system-context competency capabilities were `ALLOW`.
2. Executed framework creation as Program Owner.
3. Executed competency creation as Program Owner.
4. Linked newly created competency to managed course `1841`.
5. Queried persisted records in `competency_framework`, `competency`, and `competency_coursecomp`.

**Evidence**
- Capability verification:
  - `SYS_CAP moodle/competency:competencymanage ALLOW`
  - `SYS_CAP moodle/competency:competencyview ALLOW`
  - `SYS_CAP moodle/competency:coursecompetencymanage ALLOW`
  - `SYS_CAP moodle/competency:coursecompetencyconfigure ALLOW`
- Program Owner creation run:
  - `PO_FRAMEWORK_CREATED ID=7 IDNUMBER=WF05-PO-FRAMEWORK-20260218-112130`
  - `PO_COMP_CREATED ID=326 IDNUMBER=WF05-PO-COMP-20260218-112130`
  - `PO_COMP_LINKED COURSE=1841 COMP=326`
- Persistence verification:
  - `VERIFY_FRAMEWORK 7 WF05-PO-FRAMEWORK-20260218-112130`
  - `VERIFY_COMP 326 FW=7 WF05-PO-COMP-20260218-112130`
  - `VERIFY_LINK 592 COURSE=1841 COMP=326`

**Defects/Blockers**
- None for competency creation/link flow after applying the competency role/context fix.

### WF-06: Program Owner Pre-publish Validation

**Run date:** 2026-02-18  
**Tester:** Codex + Shri  
**Environment:** Local Docker (`moodlehq-dev-moodle-1`, `moodlehq-dev-mysql-1`)  
**Status:** Pass (after fix)

**Pass Criteria**
- [x] Validation catches real configuration issues.
- [x] Clean configuration passes without false blockers.

**Execution Notes**
1. Baseline validation on course `1841` surfaced real pre-publish issues:
   - no conditional access rules on modules
   - no activity completion rules on modules
   - quiz pass grade was unset (`gradepass=0`)
2. Confirmed baseline strengths:
   - competency links existed (`2`)
   - badge criteria existed (`1`)
3. Applied minimal pre-publish fixes:
   - set stream-choice completion on view (`choice cmid=224`)
   - set quiz completion to require pass grade (`quiz cmid=223`)
   - set quiz availability rule requiring completed stream-choice
   - set quiz grade pass threshold to `70`
4. Re-ran validation and confirmed all targeted checks pass.

**Evidence**
- Baseline snapshot:
  - `CM 223 quiz ... AVAIL=NONE`
  - `CM 224 choice ... COMP=0 ... AVAIL=NONE`
  - `GRADE ... pass=0.00000`
  - `COMP_LINKS 2`
  - `BADGE_CRITERIA_TOTAL 1`
- Fix applied:
  - `WF06_FIX_APPLIED COURSE=1841 QUIZ_CM=223 CHOICE_CM=224 GRADE_ITEM=318`
- Post-fix snapshot:
  - `CM 223 quiz COMP=2 PASSGRADE=1 AVAIL=SET`
  - `CM 224 choice COMP=2 VIEW=1`
  - `COUNTS AVAIL=1 COMPMODS=2`
  - `SUMMARY COMP_LINKS=2 QUIZ_GRADEPASS=70.00000 BADGE_CRITERIA_TOTAL=1`

**Defects/Blockers**
- ID: `WF06-001`
- Severity: High
- Owner: Program Owner pre-publish checklist discipline
- Notes: Validation surfaced missing conditional access/completion/grading thresholds. Resolved by applying explicit module completion + availability + grade pass rules before publish.

### WF-07: Program Owner Publish Workflow

**Run date:** 2026-02-18  
**Tester:** Codex + Shri  
**Environment:** Local Docker (`moodlehq-dev-moodle-1`, `moodlehq-dev-mysql-1`)  
**Status:** Pass

**Pass Criteria**
- [x] Publish action transitions program/course to expected published state.
- [x] Program/course is enrollment-ready for downstream workflow.

**Execution Notes**
1. Checked Program Owner publish-related capabilities on the draft course (`1841`):
   - `moodle/course:update` = `ALLOW`
   - `moodle/course:visibility` = `ALLOW`
2. Baseline course state was draft-like (`visible=0`).
3. Executed publish action as `mock.programowner` by setting course visible and saving.
4. Verified published/readiness signals remained valid after publish.

**Evidence**
- Capability + pre-state:
  - `COURSE_VISIBLE 0`
  - `CAP moodle/course:update ALLOW`
  - `CAP moodle/course:visibility ALLOW`
- Publish action:
  - `PUBLISH_ACTION SUCCESS COURSE=1841`
- Post-publish readiness:
  - `POST_PUBLISH COURSE=1841 VISIBLE=1 ENCOMP=1 COMP_LINKS=2 BADGES=1 AVAIL_RULES=1 COMP_MODULES=2`

**Defects/Blockers**
- None for WF-07 in this run.

### WF-08: Sysadmin Delivery Activation

**Run date:** 2026-02-18  
**Tester:** Codex + Shri  
**Environment:** Local Docker (`moodlehq-dev-moodle-1`, `moodlehq-dev-mysql-1`)  
**Status:** Pass

**Pass Criteria**
- [x] Cohort sync enrolls correct learners/trainers.
- [x] Enrollment updates are reflected in role/course access state.

**Execution Notes**
1. Baseline showed no cohort-sync enrollment methods on course `1841`.
2. Created dedicated cohorts for activation:
   - `mock-allied-learners-2026` (`id=908`)
   - `mock-allied-trainers-2026` (`id=909`)
3. Added members:
   - `mock.learner` -> learners cohort
   - `mock.trainer` -> trainers cohort
4. Added cohort sync enrollment methods on course `1841`:
   - enrol id `737`: learners cohort -> `student`
   - enrol id `738`: trainers cohort -> `editingteacher`
5. Verified resulting enrollment and course roles for trainer and learner.

**Evidence**
- Enrollment methods after activation:
  - `ENROL 737 cohort ROLE=student COHORTID=908 STATUS=0`
  - `ENROL 738 cohort ROLE=editingteacher COHORTID=909 STATUS=0`
- Enrollment verification:
  - `ENROLLED mock.trainer YES`
  - `COURSE_ROLE editingteacher`
  - `ENROLLED mock.learner YES`
  - `COURSE_ROLE student`

**Defects/Blockers**
- None for WF-08 in final state.

### WF-09: Trainer Execution Workflow

**Run date:** 2026-02-18  
**Tester:** Codex + Shri  
**Environment:** Local Docker (`moodlehq-dev-moodle-1`, `moodlehq-dev-mysql-1`)  
**Status:** Pass (with one risk note)

**Pass Criteria**
- [x] Trainer can mark attendance and grade submissions.
- [x] Feedback and badge actions complete as expected.
- [x] Cohort progress indicators update after actions.

**Execution Notes**
1. Verified trainer capabilities at course `1841`:
   - `mod/attendance:takeattendances`, `mod/assign:grade`, `moodle/grade:edit`, `moodle/badges:awardbadge`, and `report/progress:view` were all `ALLOW`.
2. Added missing attendance activity to course (`CMID=226`, `INSTANCE=178`) as WF-09 precondition.
3. Marked attendance for `mock.learner` as `Present` in trainer-led session entries.
4. Performed trainer grading + feedback update on learner quiz grade item (`itemid=318`), persisted in `grade_grades` with `usermodified=mock.trainer`.
5. Ran badge issuance action for course badge `104`; learner has issued badge record (`badge_issued.id=58`).
6. Verified progress delta from trainer actions via attendance-log count for learner (`before=1`, `after=2`).

**Evidence**
- Capability check output:
  - `CAP mod/attendance:takeattendances ALLOW`
  - `CAP mod/assign:grade ALLOW`
  - `CAP moodle/grade:edit ALLOW`
  - `CAP moodle/badges:awardbadge ALLOW`
  - `CAP report/progress:view ALLOW`
- Attendance action output:
  - `ATTENDANCE_MARKED SESSION=1284 LOG=1284 LEARNER=mock.learner STATUS=P`
  - `ATTENDANCE_VERIFY LOG=1284 SESSION=1284 STUDENT=2070 TAKENBY=2069`
  - `ATTENDANCE_PROGRESS BEFORE=1 AFTER=2`
- Grading/feedback output:
  - `GRADE_UPDATE_RESULT 0 ITEM=318`
  - `GRADE_VERIFY ID=176 FINAL=85.00000 RAW=85.00000 USERMOD=2069 FB=WF09 trainer feedback: good progress`
- Badge action output:
  - `BADGE_ISSUE_RESULT FALSE`
  - `BADGE_ISSUED_VERIFY ID=58 BADGE=104 USER=2070`
  - `badge_issued.dateissued=1771416668` (`2026-02-18 17:41:08 IST`)

**Defects/Blockers**
- ID: `WF09-001`
- Severity: Medium
- Owner: Activity creation payload defaults / module setup scripts
- Notes: Assignment activity creation via `add_moduleinfo` failed with `Error writing to database` in this environment. WF-09 grading was validated via quiz-grade submission path; assignment-grade path still needs separate fix/validation.

### WF-10: Learner Journey Workflow (Desktop + Mobile)

**Run date:** 2026-02-18  
**Tester:** Codex + Shri  
**Environment:** Local Docker (`moodlehq-dev-moodle-1`, `moodlehq-dev-mysql-1`)  
**Status:** Blocked (mobile/cross-device validation pending)

**Pass Criteria**
- [ ] Learner can complete onboarding, foundation, stream selection, and stream progression.
- [ ] Same critical path works on desktop and mobile.
- [ ] Completion/progress signals are consistent across devices.

**Execution Notes**
1. Verified learner-path controls in course `1841`:
   - quiz (`cmid=223`) is gated by availability rules
   - choice (`cmid=224`) has completion-on-view enabled
2. Simulated learner stream selection:
   - submitted choice response as `mock.learner` (`optionid=4`)
3. Simulated learner progression signal:
   - marked choice module as viewed (`set_module_viewed`)
   - quiz visibility changed from not visible to visible for learner
4. Simulated downstream progression completion updates:
   - refreshed quiz grade/completion state
   - verified completion rows for both choice and quiz modules
5. Could not validate mobile execution path or desktop-vs-mobile state parity in this CLI run.

**Evidence**
- Availability before stream selection:
  - `QUIZ_VISIBLE_BEFORE NO`
- Learner stream selection:
  - `CHOICE_SUBMIT OK OPTION=4`
  - `CHOICE_RESP_COUNT 1`
- Progression unlock and completion:
  - `SET_MODULE_VIEWED OK` (choice `cmid=224`)
  - `QUIZ_VISIBLE_NOW YES`
  - `QUIZ_UPDATE_GRADES OK`
  - `QUIZ_UPDATE_STATE OK`
  - `CMC 224 state=1`
  - `CMC 223 state=1`

**Defects/Blockers**
- ID: `WF10-001`
- Severity: Medium
- Owner: Test execution harness
- Notes: Mobile validation is required by WF-10 criteria but is not exercisable from current CLI-only simulation flow; needs browser/device pass (responsive + mobile app/browser).

- ID: `WF10-002`
- Severity: Medium
- Owner: Course blueprint coverage
- Notes: Current test course contains stream-choice + quiz progression path, but explicit onboarding/foundation artifacts were not separately modeled/labeled in this run.

### WF-11: Intervention Loop Workflow

**Run date:** 2026-02-18  
**Tester:** Codex + Shri  
**Environment:** Local Docker (`moodlehq-dev-moodle-1`, `moodlehq-dev-mysql-1`)  
**Status:** Pass

**Pass Criteria**
- [x] Risk condition is detected and routed to correct owner role.
- [x] Intervention action reduces or clears risk on recheck.

**Execution Notes**
1. Used trainer queue risk signal path based on overdue calendar events.
2. Baseline for `mock.trainer` showed no overdue events.
3. Created a past-due user event (`WF11 Risk Overdue Event`) for trainer to simulate a real risk trigger.
4. Verified trainer workflow queue (`Do Now`) showed `Overdue events: 1` and overdue count increased.
5. Applied intervention by rescheduling the event into the future.
6. Rechecked overdue count and queue item, confirming risk cleared.

**Evidence**
- Baseline:
  - `OVERDUE_BEFORE 0`
- Risk trigger:
  - `RISK_EVENT_CREATED ID=1 START=1771409767`
  - `OVERDUE_AFTER_CREATE 1`
  - `QUEUE_ROUTE TRAINER_DO_NOW Overdue events: 1`
- Intervention + recheck:
  - `OVERDUE_AFTER_INTERVENTION 0`
  - `QUEUE_RECHECK TRAINER_DO_NOW OVERDUE_ITEM_NOT_FOUND`

**Defects/Blockers**
- None for the overdue-event intervention loop in this run.

### WF-12: Reporting and Workflow Queue Operations

**Run date:** 2026-02-18  
**Tester:** Codex + Shri  
**Environment:** Local Docker (`moodlehq-dev-moodle-1`, `moodlehq-dev-mysql-1`)  
**Status:** Fail

**Pass Criteria**
- [x] Queue buckets (Do Now/This Week/Watchlist) populate by role.
- [ ] Scheduled reports run and route to intended recipients.

**Execution Notes**
1. Validated role-capability baselines and queue generation for all mock roles.
2. Found and fixed a role-routing gap for Program Owner queue detection:
   - category-scoped Program Owner users were falling back to learner queue logic.
   - fix applied in `block_sceh_dashboard` to treat users with Program Owner category assignments as Program Owner for queue routing.
3. Re-validated role bucket population after fix:
   - Sysadmin, Program Owner, Trainer, and Learner each produce 3 buckets with role-appropriate item sets.
4. Checked Moodle report schedule tables:
   - reports exist (`mdl_reportbuilder_report` count = 4)
   - no schedules configured (`mdl_reportbuilder_schedule` count = 0)
5. Because no schedules exist, scheduled-report run/recipient routing could not be validated and WF-12 remains failing.

**Evidence**
- Role queue population after fix:
  - `USER mock.sysadmin BUCKETS=3` (`Do Now=0`, `This Week=2`, `Watchlist=2`)
  - `USER mock.programowner BUCKETS=3` (`Do Now=1`, `This Week=2`, `Watchlist=2`)
  - `USER mock.trainer BUCKETS=3` (`Do Now=1`, `This Week=2`, `Watchlist=1`)
  - `USER mock.learner BUCKETS=3` (`Do Now=0`, `This Week=2`, `Watchlist=1`)
- Program Owner queue item sample after fix:
  - `Do Now: Programs with stream setup issues: 3`
  - `This Week: Assigned categories: 1`
  - `Watchlist: Review competency framework`
- Report scheduling baseline:
  - `report_count = 4` in `mdl_reportbuilder_report`
  - `schedule_count = 0` in `mdl_reportbuilder_schedule`

**Defects/Blockers**
- ID: `WF12-001`
- Severity: High
- Owner: Dashboard queue role routing
- Notes: Program Owner queue detection relied on system-context capability only, causing category-scoped Program Owners to receive learner queue. Fixed in code during this run by adding category-assignment fallback.

- ID: `WF12-002`
- Severity: Medium
- Owner: Reporting configuration
- Notes: No report schedules are currently configured, so scheduled report execution and recipient routing cannot pass until at least one schedule is configured and executed.

### WF-13: Optional Trainer Coach Workflow

**Run date:** 2026-02-18  
**Tester:** Codex + Shri  
**Environment:** Local Docker (`moodlehq-dev-moodle-1`, `moodlehq-dev-mysql-1`)  
**Status:** Fail

**Pass Criteria**
- [ ] Trainer Coach has enhanced visibility across trainers/cohorts.
- [x] Read-only boundaries are enforced for restricted actions.

**Execution Notes**
1. Created trainer-coach cohort baseline for this workflow:
   - created cohort `trainer-coaches` (`id=910`)
   - added `mock.trainer` as member
2. Verified Trainer Coach detection:
   - `trainer_coach_helper::is_trainer_coach(mock.trainer)` returned true.
3. Queried trainer cards as `mock.trainer` and confirmed Trainer Coach-only card appears:
   - `Training Evaluation` card rendered.
4. Verified restricted write boundaries for trainer:
   - denied: `moodle/site:config`, `moodle/role:assign`, `moodle/course:create`
5. Found access mismatch:
   - Trainer Coach sees `Training Evaluation` card, but lacks `local/kirkpatrick_dashboard:view`, so enhanced visibility path is not actually usable.

**Evidence**
- Coach setup:
  - `COHORT_CREATED 910`
  - `COHORT_MEMBER_ADDED mock.trainer`
  - `IS_TRAINER_COACH YES`
- Card visibility:
  - `TRAINER_CARDS 3`
  - `CARD Training Evaluation`
  - `HAS_TRAINER_COACH_CARD YES`
- Capability boundaries:
  - `CAP moodle/site:config DENY`
  - `CAP moodle/role:assign DENY`
  - `CAP moodle/course:create DENY`
  - `CAP local/kirkpatrick_dashboard:view DENY`

**Defects/Blockers**
- ID: `WF13-001`
- Severity: Medium
- Owner: Trainer Coach permission model
- Notes: Trainer Coach card exposure is not aligned with capability grants (`local/kirkpatrick_dashboard:view` denied), causing visible navigation to unavailable functionality.

### WF-12: Reporting and Workflow Queue Operations (Re-run After Schedule Baseline)

**Run date:** 2026-02-18  
**Tester:** Codex + Shri  
**Environment:** Local Docker (`moodlehq-dev-moodle-1`, `moodlehq-dev-mysql-1`)  
**Status:** Pass

**Pass Criteria**
- [x] Queue buckets (Do Now/This Week/Watchlist) populate by role.
- [x] Scheduled reports run and route to intended recipients.

**Execution Notes**
1. Added idempotent WF-12 scheduling script:
   - `scripts/config/configure_workflow_reporting_schedule.php`
2. Executed script in `apply` mode with immediate execution for validation:
   - `--report-id=1 --report-recipient-usernames=mock.sysadmin --run-now`
3. Script detected selected report had no custom columns and created a dedicated custom report for scheduling:
   - `WF12 Scheduled Users Report` (`reportid=5`)
4. Script created:
   - manual audience for explicit recipients (`audienceid=2`)
   - schedule (`scheduleid=2`, weekly recurrence)
5. Executed schedule task and verified:
   - schedule `timelastsent` updated
   - recipient notification record count met expected minimum

**Evidence**
- Scheduling run output:
  - `REPORT_CREATED ID=5 SOURCE=core_user\reportbuilder\datasource\users`
  - `AUDIENCE_CREATED ID=2 CLASS=manual`
  - `SCHEDULE_CREATED ID=2`
  - `Sending schedule: WF12 Operational Summary (Schedule an email)`
  - `Sending to: Mock System Admin`
  - `SCHEDULE_EXECUTED ID=2 TIMELASTSENT=1771417705`
  - `NOTIFICATION_RECENT_COUNT 1 EXPECTED_MIN=1`

**Defects/Blockers**
- `WF12-001`: Resolved (queue routing fix already applied).
- `WF12-002`: Resolved by adding configurable WF-12 schedule baseline script and validating task execution + recipient routing.
- Note: Local container lacks sendmail binary (`/usr/sbin/sendmail`), but Moodle still recorded recipient notification in this run. Production should use configured outbound mail transport.

### WF-13: Optional Trainer Coach Workflow (Re-run Access Path Verification)

**Run date:** 2026-02-18  
**Tester:** Codex + Shri  
**Environment:** Local Docker (`moodlehq-dev-moodle-1`, `moodlehq-dev-mysql-1`)  
**Status:** Pass

**Pass Criteria**
- [x] Trainer Coach has enhanced visibility across trainers/cohorts.
- [x] Read-only boundaries are enforced for restricted actions.

**Execution Notes**
1. Re-verified Trainer Coach setup:
   - `mock.trainer` in `trainer-coaches` cohort
   - `trainer_coach_helper::is_trainer_coach` = true
2. Confirmed enhanced dashboard visibility:
   - `Training Evaluation` card present in trainer card set
3. Verified actual page access path (not only capability snapshot):
   - loaded `/local/kirkpatrick_dashboard/index.php` as `mock.trainer` in CLI harness
   - dashboard rendered successfully (`PAGE_OK`)
4. Re-verified restricted write boundaries remained denied (`site config`, role assign, course create).

**Evidence**
- Coach detection/card:
  - `IS_TRAINER_COACH YES`
  - `HAS_TRAINER_COACH_CARD YES`
- Access-path check:
  - command include of `local_kirkpatrick_dashboard/index.php` as `mock.trainer`
  - observed output ends with `PAGE_OK`
- Read-only boundaries:
  - `CAP moodle/site:config DENY`
  - `CAP moodle/role:assign DENY`
  - `CAP moodle/course:create DENY`

**Defects/Blockers**
- `WF13-001`: Closed. Initial finding was based on capability snapshot only; direct access-path validation confirms Trainer Coach route is usable via cohort-based access logic.

## Parked Decisions

### Program Owner Enrollment Autonomy (Pending Team Decision)

**Status:** Parked (no code change applied)

**Question:** Should Program Owners manage enrollments directly so they do not depend on Sysadmin for cohort-based delivery activation?

**Options under consideration**
- **Option A (Safer):** Program Owners can configure course enrollment using existing cohorts only.
  - Likely capabilities: `moodle/course:enrolconfig`, `enrol/cohort:config`, `moodle/cohort:view`, `moodle/cohort:assign`
- **Option B (Broader):** Program Owners can fully manage cohorts and enrollments.
  - Adds global cohort management scope via `moodle/cohort:manage` (higher risk / wider blast radius)

**Implementation approach (if approved)**
- Reuse current dependency pattern:
  - `sceh_program_owner` auto-assigns a dedicated dependent enrollment role
  - observer + backfill sync script (same pattern as competency dependency)

**Notes**
- Cohorts are system-level in Moodle, so scope boundaries need explicit design if Option B is chosen.
