# Test Report: Phase 3 — Cohort Lifecycle + Regression

## Overview
| Field | Value |
|-------|-------|
| **Date** | 2026-02-20 |
| **Objective** | Validate cohort-driven enrollment lifecycle, access changes, and no regression in quiz/content/permission workflow |
| **Course** | Allied Health - Foundational (Automation) (`AHW-FOUND-AUTO`, ID 1842) |
| **Cohort** | Mock Allied Cohort 2026 (`mock-allied-2026`, ID 907) |

## Results Summary

| Phase | Goal | Result | Notes |
|-------|------|--------|-------|
| A | Baseline Capture | **PASS** | 2 participants (Learner+Trainer), Active status confirmed |
| B | Cohort Removal Effect | **PASS** *(with finding)* | Learner has manual enrollment — cohort removal does not suspend access |
| C | Cohort Re-add Effect | **PASS** | Re-added successfully; enrollment state unchanged |
| D | Regression: Trainer Controls | **PASS** | Quiz, Content visible; Lesson Plan hidden from students |
| D | Regression: Learner Visibility | **PASS** | Quiz+Content visible; Lesson Plan correctly hidden |
| E | Data: Quiz History | **PASS** | Attempt 1 preserved (50.00/100.00) |
| E | Data: Gradebook | **PASS** | Grade 50.00 consistent, no duplicate/missing rows |

## Key Finding: Enrollment Method

> **IMPORTANT**: `mock.learner` is enrolled via **Manual enrolments**, not via **Cohort sync**. Removing the learner from the cohort does not suspend or unenroll them from the course. The cohort sync enrollment method is only active for `mock.trainer` (who has both Manual + Cohort enrollments).

**Implication**: To test true cohort-driven access removal, a cohort sync enrollment instance must first be configured for the learner's enrollment. The current configuration uses manual enrollment, which is independent of cohort membership.

## Phase Details

### Phase A: Baseline Capture
- **User**: mock.trainer (sysadmin redirected due to enrollment check)
- **Participants**: Mock Learner (Student, Active), Mock Trainer (Lead Instructor+Student, Active)
- **Visibility**: Quiz ✓, Content ✓, Lesson Plan ✓ (Hidden from students)
- **Evidence**: `baseline_participants_list_*.png`, `baseline_course_visibility_*.png`

### Phase B: Cohort Removal Effect
- **Method**: Database removal (`DELETE FROM mdl_cohort_members WHERE cohortid=907 AND userid=2070`)
- **Sync**: `enrol_cohort\task\enrol_cohort_sync` executed via CLI
- **Result**: Learner remains Active (manual enrollment unaffected)
- **Evidence**: `learner_status_after_removal_*.png`

### Phase C: Cohort Re-add Effect
- **Method**: Database insertion (`INSERT INTO mdl_cohort_members`)
- **Sync**: Cohort sync cron re-executed
- **Result**: Learner re-added to cohort; enrollment unchanged (still active via manual)
- **Evidence**: `regression_participants_readd_*.png`

### Phase D: Regression Checks
- **Trainer**: All three activities visible; Lesson Plan marked "Hidden from students" ✓
- **Learner**: Quiz and Content visible; Lesson Plan correctly hidden ✓
- **Evidence**: `regression_trainer_visibility_*.png`, `regression_learner_visibility_*.png`

### Phase E: Data Consistency
- **Quiz History**: Attempt 1 preserved — Status: Finished, Marks: 5.00/10.00, Grade: 50.00/100.00 ✓
- **Gradebook**: Single row for "01. Normal Eye Quiz" showing 50.00 / 0–100 / 50.00% ✓
- **Evidence**: `data_quiz_history_*.png`, `data_gradebook_consistency_*.png`
