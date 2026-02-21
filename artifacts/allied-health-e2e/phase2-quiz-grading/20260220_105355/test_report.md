# Test Report: Phase 2 - Quiz Attempt + Grading

## Overview
- **Date:** 2026-02-20
- **Test Objective:** Validate the E2E flow of a learner attempting a quiz, a trainer verifying the grade, and permission boundaries.
- **Course:** Allied Health - Foundational (Automation)
- **Quiz:** 01. Normal Eye Quiz

## Test Results Summary
| Phase | Goal | Result |
|-------|------|--------|
| A | Learner Quiz Attempt | **PASSED** |
| B | Trainer Grading Verification | **PASSED** |
| C | Permission Boundary Check | **PASSED** |

## Detailed Findings

### Phase A: Learner Quiz Attempt (mock.learner)
- **Status:** Completed
- **Attempt Duration:** ~13 minutes
- **Score:** 5.00 / 10.00
- **Grade:** 50.00 / 100.00
- **Evidence:**
    - [Quiz Intro](learner_quiz_intro_1771565288843.png)
    - [Question 1](learner_quiz_q1_1771565305174.png)
    - [Question 2](learner_quiz_q2_1771565351691.png)
    - [Question 3](learner_quiz_q3_actual_1771565391151.png)
    - [Question 4](learner_quiz_q4_actual_2_1771565434262.png)
    - [Question 5](learner_quiz_q5_actual_final_1771565505214.png)
    - [Question 6](learner_quiz_q6_actual_final_1771565547731.png)
    - [Question 7](learner_quiz_q7_actual_final_1771565609918.png)
    - [Question 8](learner_quiz_q8_actual_finaly_last_one_1771565652259.png)
    - [Question 9](learner_quiz_q9_1771565813721.png)
    - [Question 10](learner_quiz_q10_1771565939033.png)
    - [Grade Summary](learner_quiz_grade_summary_1771566116157.png)

### Phase B: Trainer Grading flow (mock.trainer)
- **Status:** Verified
- **Findings:** The learner's attempt was correctly listed in the quiz results and the grade (50.00) was reflected in the course gradebook.
- **Evidence:**
    - [Trainer Results View](trainer_quiz_results_view_1771566624188.png)
    - [Trainer Gradebook View](trainer_gradebook_view_1771566707643.png)

### Phase C: Permission Boundary Check (mock.learner)
- **Status:** Verified
- **Findings:** Learner cannot see the "01. Normal Eye - Lesson plan" (trainer-only resource). Learner can access "01. Normal Eye - Content".
- **Evidence:**
    - [Permission Check](learner_course_permission_check_1771567010563.png)
