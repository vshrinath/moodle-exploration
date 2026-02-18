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
