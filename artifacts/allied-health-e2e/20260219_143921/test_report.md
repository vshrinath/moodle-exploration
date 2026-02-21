# E2E Test Report: Allied Health Programs Activity Release
**Timestamp:** 2026-02-19 14:39:21
**Environment:** Local Moodle Instance (http://127.0.0.1:8081)
**Test Result:** PASS ✅

## 1. Executive Summary
The end-to-end (E2E) test for the Allied Health Programs workflow was successfully completed. The test verified that a Program Owner can set up a course and hidden activities, a Trainer can selectively release them, and a Learner sees only the released items while trainer-facing materials remain hidden.

---

## 2. Test Execution Details

### Phase A: Sysadmin Category Verification
- **User:** `mock.sysadmin`
- **Action:** Verified existence of "Allied Health Programs" category.
- **Outcome:** Category found and validated.

### Phase B: Program Owner Course Setup
- **User:** `mock.programowner`
- **Course Name:** Allied Health - Foundational (Automation)
- **Short Name:** `AHW-FOUND-AUTO-PO` (Alternate used as `AHW-FOUND-AUTO` was taken)
- **Actions:**
    - Created course and hidden it from students.
    - Added sections: "01. Normal Eye" and "02. Refractive errors".
    - Added activities (content, lesson plan, quiz) to both sections.
    - Verified all items are "Hidden from students".

**PO View Setup Screenshot:**
![PO Setup](/Users/shri/.gemini/antigravity/brain/9464dcef-8f00-49f3-ad86-4e6818b61e20/po_course_setup_1771494212702.png)

### Phase C: Trainer Selective Release
- **User:** `mock.trainer`
- **Actions:**
    - Released "01. Normal Eye Quiz" and "01. Normal Eye - Content" in Section 01.
    - Kept "01. Normal Eye - Lesson plan" hidden.
- **Outcome:** Visibility updated successfully.

**Trainer View Visibility Screenshot:**
![Trainer Release](/Users/shri/.gemini/antigravity/brain/9464dcef-8f00-49f3-ad86-4e6818b61e20/trainer_release_1771494596056.png)

### Phase D: Learner Visibility Assertions
- **User:** `mock.learner`
- **Verification:**
    - **Quiz:** Visible ✅
    - **Content:** Visible ✅
    - **Lesson Plan:** Hidden ✅
- **Outcome:** Permission hierarchy and selectively visibility confirmed.

**Learner View Final Screenshot:**
![Learner Visibility](/Users/shri/.gemini/antigravity/brain/9464dcef-8f00-49f3-ad86-4e6818b61e20/learner_visibility_1771495600134.png)

---

## 3. Findings & Notes
- The initial course shortname `AHW-FOUND-AUTO` was already present in the system, suggesting a previous automation run or manual setup. Fallback shortname `AHW-FOUND-AUTO-PO` was used to ensure a clean test.
- Course visibility (Hide/Show) was toggled at the course level by the trainer to unblock learner access.

---
**Audit Complete**
