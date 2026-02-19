# Allied Health — Foundational Course Workflow
**Shroff Charitable Eye Hospital | Moodle LMS**
*Using Attendance Plugin | Single course, multiple cohorts over time*

---

## Overview

| Field | Details |
|---|---|
| Course | Allied Health — Foundational |
| Category | Allied Health Workers |
| Delivery | Instructor-Led Training (ILT) with online and offline assessments |
| Attendance tracking | Moodle Attendance plugin (presence recording only) |
| Session scheduling | Outside Moodle — email, calendar, or notice board |
| Quiz/Assignment access | Unlocked by Trainer via attendance marking (Restrict Access) |
| Trainer resources | Lesson plans, roleplay scripts, facilitator guides — always hidden from learners |
| Learner content | Teaching material, reference guides — Trainer controls when to release |
| External resources | YouTube videos, URLs — embedded or linked within modules |
| Cross-module assessments | OJT/practical evaluations spanning multiple modules, scanned reports uploaded as feedback |
| Cohort model | One permanent course, cohorts enrolled and unenrolled each cycle |
| Roles involved | Program Owner, Trainer, Learner, System Admin |
| Completion leads to | Stream course unlock (Nursing / OT / Optometry Tech) |

---

## Course Structure

```
Allied Health — Foundational  [one permanent course]
│
├── Module 1: Eye Anatomy
│     ├── 📁 Trainer Resources        👁 Trainer only — always invisible to learners
│     │     ├── Lesson Plan: Eye Anatomy (PDF)
│     │     ├── Roleplay Script: Patient Consultation (PDF)
│     │     └── Facilitator Guide: Module 1 (PDF)
│     ├── 📁 Module 1 Content         👁 Hidden until Trainer releases to learners
│     │     ├── Teaching Slides: Eye Anatomy (PDF)
│     │     ├── Reference: Eye Anatomy Diagram (PDF)
│     │     └── Reference: Ocular Terminology Guide (PDF)
│     ├── 🔗 Pre-session: Introduction to Eye Anatomy (YouTube — Embed)
│     ├── Attendance: Eye Anatomy Class
│     ├── 🔗 Post-session: Eye Anatomy Reference Chart (URL — New tab)
│     └── Quiz: Eye Anatomy MCQ  🔒 unlocks when attendance marked
│
├── Module 2: Patient Handling
│     ├── 📁 Trainer Resources        👁 Trainer only — always invisible to learners
│     │     ├── Lesson Plan: Patient Handling (PDF)
│     │     ├── Roleplay Script: Transfer Technique (PDF)
│     │     └── Assessment Rubric Guide (PDF)
│     ├── 📁 Module 2 Content         👁 Hidden until Trainer releases to learners
│     │     ├── Teaching Slides: Patient Handling (PDF)
│     │     ├── Reference: Safe Transfer Techniques (PDF)
│     │     └── Reference: WHO Safe Patient Handling Guidelines (URL)
│     ├── 🔗 Pre-session: Safe Patient Handling Video (YouTube — Embed)
│     ├── Attendance: Patient Handling Class
│     └── Assignment: Practical Checklist  🔒 unlocks when attendance marked
│
├── ── Cross-Module Assessment 1: Modules 1 + 2 ──────────────────
│     └── Assignment: OJT Assessment — Eye Anatomy & Patient Handling
│           🔒 unlocks when Module 1 Quiz AND Module 2 Assignment complete
│           📎 Trainer uploads scanned OJT report as feedback file
│
├── Module 3: Infection Control
│     ├── 📁 Trainer Resources        👁 Trainer only — always invisible to learners
│     │     ├── Lesson Plan: Infection Control (PDF)
│     │     └── Facilitator Guide: Module 3 (PDF)
│     ├── 📁 Module 3 Content         👁 Hidden until Trainer releases to learners
│     │     ├── Teaching Slides: Infection Control (PDF)
│     │     ├── Reference: Hospital Infection Control Protocol (PDF)
│     │     └── Reference: WHO Hand Hygiene Guidelines (URL)
│     ├── 🔗 Pre-session: Infection Control in Clinical Settings (YouTube — Embed)
│     ├── Attendance: Infection Control Class
│     └── Quiz: Infection Control MCQ  🔒 unlocks when attendance marked
│
├── Module 4: Documentation & Compliance
│     ├── 📁 Trainer Resources        👁 Trainer only — always invisible to learners
│     │     ├── Lesson Plan: Documentation (PDF)
│     │     └── Sample Completed Forms (PDF)
│     ├── 📁 Module 4 Content         👁 Hidden until Trainer releases to learners
│     │     ├── Teaching Slides: Documentation Standards (PDF)
│     │     ├── Reference: Blank Form Templates (PDF)
│     │     └── Reference: Compliance Checklist (PDF)
│     ├── 🔗 Pre-session: Documentation Standards Overview (URL — New tab)
│     ├── Attendance: Documentation Class
│     └── Assignment: Sample Form Completion  🔒 unlocks when attendance marked
│
├── ── Cross-Module Assessment 2: Modules 3 + 4 ──────────────────
│     └── Assignment: OJT Assessment — Infection Control & Documentation
│           🔒 unlocks when Module 3 Quiz AND Module 4 Assignment complete
│           📎 Trainer uploads scanned OJT report as feedback file
│
└── Final Assessment
      └── Quiz: Foundational Final MCQ  🔒 unlocks when all modules and OJT assessments complete
```

---

**Grading weightage:**

| Component | Weight | Type |
|---|---|---|
| Attendance (all 4 sessions) | Completion gate | Pass/Fail |
| Module quizzes (Modules 1 + 3) | 20% | Online, auto-graded |
| Practical assignments (Modules 2 + 4) | 20% | Offline, Trainer-graded |
| Cross-module OJT assessments (x2) | 20% | Offline, Trainer-graded + scanned report |
| Final assessment | 40% | Online, auto-graded |

**Minimum pass grade: 60% overall**

> **Important:** The Attendance plugin records presence only. Session dates, times, rooms, and batch schedules must be communicated outside Moodle — via email, calendar invite, or notice board.

---

## 1. Setup Workflow — Program Owner

> **When:** One-time setup. This course is permanent — does not need to be recreated per cohort.

### Step 1 — Create the Course

1. Navigate to **Allied Health Workers** category → **Create new course**
2. Configure:
   - Full name: `Allied Health — Foundational`
   - Short name: `AHW-FOUND`
   - Visibility: **Hidden**
   - Course format: **Topics format**
   - Enable completion tracking: **Yes**
   - Start date: leave as today — permanent course, not date-bound
3. Save and enter the course

### Step 2 — Create Course Sections

Turn editing on → rename sections:
- Module 1: Eye Anatomy
- Module 2: Patient Handling
- Cross-Module Assessment 1
- Module 3: Infection Control
- Module 4: Documentation & Compliance
- Cross-Module Assessment 2
- Final Assessment

### Step 3 — Add Trainer Resources Folder (one per module)

First item in each module section — above all learner-facing content.

1. **Add activity → Folder**
2. Configure:
   - Name: `Trainer Resources — Module [N]`
   - Display: On a separate page
3. Upload files: lesson plan, roleplay script, facilitator guide
4. **Restrict access → Add restriction → User profile → Role contains Trainer**

> Completely invisible to learners — no lock icon shown. Program Owner can update files at any time without changing settings.

> **Permission check:** Trainers need the `editingteacher` role (or equivalent with `moodle/course:manageactivities` capability) to show/hide the Module Content folders in Step 4. This is standard in Moodle — no additional capability configuration needed.

### Step 4 — Add Module Content Folder (one per module)

Second item in each module section — below Trainer Resources, above the pre-session URL.

1. **Add activity → Folder**
2. Configure:
   - Name: `Module [N] Content`
   - Display: On a separate page
   - Availability: **Hide from learners** (eye icon — set to hidden at setup)
3. Upload files: teaching slides, reference PDFs
4. Add URL resources inside the folder description or as separate URL activities if external links need to be tracked individually
5. No Restrict Access needed — visibility is controlled by the hide/show toggle

> **Trainer releases this folder** by clicking the eye icon on the activity when they choose — before, during, or after the session. Once visible, learners can use it as a reference while completing assessments. Program Owner can also release it if needed.

> Unlike Trainer Resources (which uses Role restriction and is permanently hidden), the Content folder uses simple show/hide — Trainer controls timing, not permanent access.

### Step 5 — Add Learner URL/Video Resources

For each module, add pre-session and post-session reference materials:

1. **Add activity → URL**
2. Configure:
   - Name: e.g., `Pre-session: Introduction to Eye Anatomy`
   - External URL: paste YouTube or external link
   - Display:
     - **Embed** — for YouTube (plays inline within Moodle)
     - **Open in new tab** — for external websites, PDFs, or guidelines
3. Completion tracking: optional — set "Learner must view" if tracking is needed, leave untracked if supplementary

**YouTube embed note:** Use full YouTube URLs (`https://www.youtube.com/watch?v=VIDEO_ID`), not shortened `youtu.be` links. Moodle auto-detects and embeds the player when Display is set to Embed.

### Step 6 — Add Attendance Activities (one per module)

For each of the 4 modules:

1. **Add activity → Attendance**
2. Configure:
   - Name: e.g., `Eye Anatomy Class`
   - Grade: pass/fail
   - Student recording: **Disabled**
3. Statuses: P = Present, A = Absent, L = Late, E = Excused
4. Completion tracking: **Student must receive a grade**
5. Save — Trainer adds sessions before each class

### Step 7 — Build Question Bank

1. Course → **Question Bank → Categories** → create 5 categories:
   - Eye Anatomy, Patient Handling, Infection Control, Documentation & Compliance, Final Assessment
2. Add 30–40 questions per category
3. Question types: Multiple Choice (primary), True/False, Matching

### Step 8 — Add Module Quiz Activities (Modules 1 and 3)

1. **Add activity → Quiz**
2. Configure:
   - Open/close dates: **leave blank**
   - Time limit: 30 minutes, Attempts: 2, Pass grade: 60%
   - Review: show answers only after all attempts used
3. Add 20 random questions from category pool
4. Completion tracking: must achieve passing grade
5. **Restrict access → Activity completion → corresponding Attendance activity must be complete**

### Step 9 — Add Module Assignment Activities (Modules 2 and 4)

1. **Add activity → Assignment**
2. Configure:
   - Submission type: **None**
   - Grading method: Rubric (recommended), Pass grade: 60%
3. Define rubric criteria per module
4. Completion tracking: must receive a grade
5. **Restrict access → Activity completion → corresponding Attendance activity must be complete**

### Step 10 — Add Cross-Module OJT Assessment Activities

Place these in the dedicated Cross-Module Assessment sections.

**Cross-Module Assessment 1 (after Module 2):**

1. **Add activity → Assignment**
2. Configure:
   - Name: `OJT Assessment — Eye Anatomy & Patient Handling`
   - Description: criteria being evaluated, OJT context (on-ward, simulation, etc.)
   - Submission type: **None** — Trainer evaluates and uploads scanned report
   - Grading method: **Rubric** spanning criteria from both Module 1 and Module 2
   - Maximum grade: 100, Pass grade: 60%
3. Completion tracking: must receive a grade
4. **Restrict access → All of the following must be complete:**
   - Module 1 Quiz (Eye Anatomy MCQ) ✅
   - Module 2 Assignment (Practical Checklist) ✅

**Cross-Module Assessment 2 (after Module 4):**

Same setup, spanning Module 3 and Module 4 criteria.

- **Restrict access → All of the following must be complete:**
  - Module 3 Quiz (Infection Control MCQ) ✅
  - Module 4 Assignment (Sample Form Completion) ✅

### Step 11 — Add Final Assessment Quiz

1. **Add activity → Quiz**
2. Configure:
   - Name: `Foundational Final Assessment`
   - Open/close dates: **leave blank**
   - Time limit: 45 minutes, Attempts: 2, Pass grade: 60%
3. Add 30 random questions drawing from all 4 question bank categories
4. Completion tracking: must achieve passing grade
5. **Restrict access → All of the following must be complete:**
   - Module 1 Quiz ✅
   - Module 2 Assignment ✅
   - Cross-Module OJT Assessment 1 ✅
   - Module 3 Quiz ✅
   - Module 4 Assignment ✅
   - Cross-Module OJT Assessment 2 ✅

### Step 12 — Configure Gradebook

1. Course → **Grades → Gradebook setup**
2. Set aggregation: **Weighted mean of grades**
3. Assign weights:
   - Module quizzes (1 + 3): 20%
   - Module practical assignments (2 + 4): 20%
   - Cross-module OJT assessments (x2): 20%
   - Final assessment: 40%

### Step 13 — Set Course Completion Conditions

Course → **Course completion** → add conditions:
- All 4 Attendance activities complete
- All module quizzes at passing grade
- All module assignments graded
- Both OJT cross-module assessments graded
- Final assessment at passing grade
- Overall grade ≥ 60%

### Step 14 — Configure Badge

1. Course → **More → Badges → Add a new badge**
2. Configure:
   - Name: `Allied Health Foundational — Completion`
   - Description: Awarded upon successful completion of the Allied Health Foundational course
   - Upload badge image (PNG, 256x256px recommended)
3. Click **Create badge**
4. **Criteria** tab → Add criteria:
   - **Course completion** → Select this course
5. **Enable access** → Confirm
6. Badge is now automatic — awarded when course completion conditions are met

> **Note:** Moodle core badges are used. If you need printable certificates with custom layouts, install the `mod_customcert` plugin separately.

### Step 15 — First Cohort: Assign Cohort and Trainer

1. Participants → **Enrollment methods → Cohort sync** → add active cohorts
2. Participants → **Enrol users** → assign Trainer role
3. Make course **Visible**

### Step 16 — Communicate Schedule Outside Moodle

- Send batch schedules via email or calendar invite
- Maintain a shared schedule document for learners and Trainers

---

## 2. Trainer Workflow — Sessions, Content Release, and Grading

### Before Each Session

1. Open **Trainer Resources — Module [N]** folder → review lesson plan, roleplay script, facilitator guide
2. Add session to the **Attendance activity**:
   - Date, time, duration, room description
   - Add separate session for each batch if running simultaneously

### Releasing Module Content to Learners

Trainer decides when to make the **Module [N] Content** folder visible:

- **Before the session** — learners can preview teaching slides as preparation
- **During the session** — Trainer reveals content live as a teaching reference
- **After the session** — content becomes a reference while learners complete assessments

To release: Course → Turn editing on → click the **eye icon** on the Module Content folder → folder becomes visible to learners immediately.

To hide again if needed: click the eye icon again — folder returns to hidden state.

### On the Day — Mark Attendance

**This triggers quiz/assignment unlock for each learner.**

1. Attendance activity → open the session → mark each learner: P / A / L / E → save
2. Learners marked Present → relevant quiz or assignment unlocks immediately
3. Learners marked Absent → stays locked until makeup session

### Grading Module Assignments (Modules 2 and 4)

1. Course → Assignment → **View all submissions**
2. Click learner → select rubric levels → add feedback → save
3. Or use **Quick grading** for inline entry across the cohort
4. Learner notified by email

### Conducting and Grading Cross-Module OJT Assessments

**Conducting the OJT:**
- Schedule the on-job or simulation assessment outside Moodle (ward, clinical setting, simulation lab)
- Use the rubric criteria defined in the assignment as the evaluation framework
- Complete a paper-based evaluation form or generate a report during the assessment

**Grading in Moodle:**
1. Course → **OJT Assessment** assignment → **View all submissions**
2. Click learner name
3. Enter overall score (or select rubric levels if rubric is configured)
4. Upload the scanned evaluation report as a **feedback file**:
   - Feedback section → **Feedback files** → upload scanned PDF
5. Add any additional written feedback comments
6. Save → learner receives email notification with grade and scanned report attached

**What the learner sees:**
- OJT Assignment grade and feedback comment
- Downloadable scanned report PDF
- If passed: Final Assessment unlocks (if all other prerequisites are also met)

### Monitoring Quiz Performance

Course → Quiz → **Results → Grades** — scores per learner
Course → Quiz → **Results → Statistics** — question-level failure analysis

---

## 3. Learner Journey — Login to Certificate

### Step 1 — Login and Dashboard

- Access Moodle via hospital URL
- Dashboard shows enrolled course with progress bar
- Trainer Resources folders are completely invisible
- Module Content folders appear hidden until Trainer releases them

### Step 2 — Pre-session Preparation

- If Trainer has released Module Content folder: review teaching slides and reference material
- Watch pre-session YouTube video if available (plays inline in Moodle)
- Both are optional — no completion condition on reference materials unless set by Program Owner

### Step 3 — Attend Physical Session

- Attend at communicated date, time, and room
- No Moodle action required
- Trainer marks attendance → quiz or assignment for that module unlocks automatically

### Step 4 — Access Module Content as Reference

- If Trainer has released the Module Content folder (during or after session): folder is now visible
- Learner can open teaching slides, reference PDFs, external guidelines as ready reference
- Particularly useful when completing practical assignments — learner can refer to content while Trainer evaluates

### Step 5 — Attempt Module Quiz (Modules 1 and 3)

- Unlocks after attendance is marked
- 30-minute limit, 2 attempts, 60% pass grade
- Score shown immediately after submission

### Step 6 — Practical Assignment (Modules 2 and 4)

- Unlocks after attendance is marked
- Trainer observes and grades — no learner submission
- Learner receives email with score and rubric feedback once graded

### Step 7 — Cross-Module OJT Assessment

- Unlocks after both prerequisite module activities are complete
- Learner participates in OJT or simulation (scheduled outside Moodle)
- Trainer grades and uploads scanned evaluation report in Moodle
- Learner receives email notification → can view grade, feedback, and download the scanned report

### Step 8 — Final Assessment

- Unlocks only after all module activities and both OJT assessments are complete
- 45-minute limit, 2 attempts, 60% pass grade
- Draws from all 4 topic areas

### Step 9 — Completion and Badge

- All conditions met → course marked Complete
- Badge awarded automatically → appears in learner profile
- Learner can download badge image or share to LinkedIn/social media

**Stream Course Access:**
After completing the Foundational course, learners choose their specialization stream (Nursing / OT / Optometry Tech). Access to stream courses is managed by:

1. **System Admin** enrolls learner in chosen stream course (manual or cohort-based)
2. Stream courses have **Restrict Access** configured:
   - Course completion: Allied Health — Foundational must be complete
3. Learner sees stream course on dashboard once enrolled and prerequisite is met

---

## 4. Admin and Monitoring Workflow — Program Owner

### Updating Course Content

| Content type | How to update | Effect |
|---|---|---|
| Trainer Resources | Open folder → add/replace files | Immediate — Trainer sees updated files |
| Module Content | Open folder → add/replace files | Immediate — visible to learners if folder is already released |
| URL resources | Edit activity → update URL | Immediate |
| Question bank | Question Bank → add/edit questions | Applies to next quiz attempt |
| OJT rubric criteria | Assignment settings → edit rubric | Applies to ungrades submissions |

### During Cohort — Regular Monitoring

1. Course → **Reports → Activity completion** — grid view, filter by cohort
2. Course → **Grades → Grader report** — full grade breakdown, identify ungraded OJT assessments
3. Course → **Attendance → Report** — attendance summary per learner

### Actions on Exceptions

| Situation | Action |
|---|---|
| Module Content folder not yet released | Check with Trainer — learners can't access reference material |
| Attendance not marked after session | Follow up with Trainer — all downstream activities stay locked |
| OJT assessment not graded | Follow up with Trainer — Final Assessment remains locked for affected learners |
| Scanned report not uploaded | Remind Trainer — learner grade is complete but report is missing from record |
| Assignment not graded | Follow up with Trainer |
| Learner failed quiz twice | Allow additional attempt (System Admin) or flag for remediation |
| Learner absent | Arrange makeup session — all locks clear after makeup attendance is marked |

### End of Cohort

1. Course → **Reports → Course completion** → export CSV for HR records
2. Course → **More → Badges → Recipients** → verify and export
3. Course → **Attendance → Export** → attendance records per batch
4. Collect OJT scanned reports as compliance documentation — these are stored in the assignment feedback files and can be exported per learner
5. **Set calendar reminder** to review badge validity 11 months from cohort end (Moodle badges don't auto-expire or send reminders — manual tracking required)

### New Cohort Cycle

1. Unenroll completed cohort: Participants → Enrollment methods → remove cohort sync
2. Enroll new cohort: add new cohort sync
3. Assign Trainer if changed
4. Trainer adds new attendance sessions for new cohort dates
5. Module Content folders revert to their saved state — if hidden, they stay hidden for the new cohort until Trainer releases them again
6. Communicate new schedule outside Moodle
7. Refresh question bank periodically

---

## Troubleshooting

### Common Issues and Solutions

**Learner says quiz is still locked after attendance marked:**
- Verify attendance was saved (not just previewed in "Take attendance" screen)
- Check learner was marked Present (P) — Absent (A) or Late (L) won't unlock
- Refresh learner's browser (Ctrl+F5 or Cmd+Shift+R)
- Check Restrict Access conditions on quiz: Attendance activity must show "complete"

**Module Content folder not visible to learners:**
- Trainer must click the eye icon to release (folder is hidden by default)
- Editing mode must be ON when clicking eye icon
- Verify folder is not role-restricted (only Trainer Resources should have role restriction)

**OJT scanned report not uploading:**
- File size limit: 10MB default (check site settings if larger files needed)
- Accepted formats: PDF recommended (PNG/JPG also work but PDF is standard for compliance)
- Upload in **Feedback files** section, not in Comments field
- Ensure file is selected before clicking "Save changes"

**Final Assessment not unlocking:**
- All 6 prerequisites must show green checkmark in Restrict Access section
- Check both OJT assessments are graded (not just submitted — no submission required)
- Verify all module quizzes show passing grade (≥60%)
- Verify all module assignments are graded

**Trainer can't see Trainer Resources folder:**
- Verify Trainer has `editingteacher` role (not just `teacher`)
- Check folder has correct Restrict Access: User profile → Role contains Trainer
- Trainer must be enrolled in course with correct role

**Learner can see Trainer Resources folder:**
- This should never happen — indicates misconfigured Restrict Access
- Edit folder → Restrict Access → verify "Role contains Trainer" is set
- Remove any other access conditions that might override role restriction

**Attendance session not appearing:**
- Verify session was saved (not just previewed)
- Check session date is correct (future sessions may not show in "Take attendance" list)
- Refresh Attendance activity page

**Badge not awarded after course completion:**
- Course → More → Badges → verify badge is enabled
- Check badge criteria: Course completion must be selected
- Verify learner meets all course completion conditions
- Badge award can take up to 1 hour (cron job) — check later or run cron manually

**Stream course not accessible after Foundational completion:**
- Verify learner is enrolled in stream course (System Admin task)
- Check stream course Restrict Access: Foundational course completion must be configured
- Verify Foundational course shows "Complete" in learner's course list

---

## First-Time Setup Checklist

Use this checklist when setting up the course for the first time:

### Course Structure
- [ ] Course created in Allied Health Workers category
- [ ] Course set to Hidden (will be made visible after first cohort enrollment)
- [ ] 7 sections created and renamed (4 modules + 2 cross-module + 1 final)
- [ ] Completion tracking enabled at course level

### Trainer Resources (one per module)
- [ ] Module 1 Trainer Resources folder created with role restriction
- [ ] Module 2 Trainer Resources folder created with role restriction
- [ ] Module 3 Trainer Resources folder created with role restriction
- [ ] Module 4 Trainer Resources folder created with role restriction
- [ ] Test: Trainer can see all 4 folders
- [ ] Test: Learner cannot see any Trainer Resources folders (completely invisible)

### Module Content (one per module)
- [ ] Module 1 Content folder created and set to Hidden
- [ ] Module 2 Content folder created and set to Hidden
- [ ] Module 3 Content folder created and set to Hidden
- [ ] Module 4 Content folder created and set to Hidden
- [ ] Test: Trainer can toggle visibility (eye icon works)
- [ ] Test: Learner sees "Not available" when hidden

### Activities
- [ ] 4 Attendance activities created (one per module)
- [ ] 2 Quiz activities created (Modules 1 and 3)
- [ ] 2 Assignment activities created (Modules 2 and 4)
- [ ] 2 OJT Assessment assignments created (cross-module sections)
- [ ] 1 Final Assessment quiz created
- [ ] URL/YouTube resources added per module (optional but recommended)

### Question Bank
- [ ] 5 question categories created
- [ ] 30-40 questions added per category (Eye Anatomy, Patient Handling, Infection Control, Documentation, Final)
- [ ] Questions reviewed for accuracy and clarity

### Restrict Access Conditions
- [ ] Module 1 Quiz: unlocks when Module 1 Attendance complete
- [ ] Module 2 Assignment: unlocks when Module 2 Attendance complete
- [ ] Module 3 Quiz: unlocks when Module 3 Attendance complete
- [ ] Module 4 Assignment: unlocks when Module 4 Attendance complete
- [ ] Cross-Module OJT 1: unlocks when Module 1 Quiz AND Module 2 Assignment complete
- [ ] Cross-Module OJT 2: unlocks when Module 3 Quiz AND Module 4 Assignment complete
- [ ] Final Assessment: unlocks when all 6 prerequisites complete

### Grading Configuration
- [ ] Gradebook aggregation set to Weighted mean
- [ ] Weights configured: Module quizzes 20%, Module assignments 20%, OJT assessments 20%, Final 40%
- [ ] Pass grade set to 60% for all graded activities
- [ ] Course passing grade set to 60%

### Course Completion
- [ ] All 4 Attendance activities required
- [ ] All quizzes require passing grade
- [ ] All assignments require grade
- [ ] Both OJT assessments require grade
- [ ] Final assessment requires passing grade
- [ ] Overall grade ≥60% required

### Badge Configuration
- [ ] Badge created with appropriate name and image
- [ ] Badge criteria set to Course completion
- [ ] Badge enabled

### Testing (Critical)
- [ ] Test as Trainer: can see Trainer Resources, can toggle Module Content visibility
- [ ] Test as Learner: cannot see Trainer Resources, Module Content shows as hidden
- [ ] Test unlock flow: create test attendance session → mark test learner Present → verify quiz unlocks
- [ ] Test OJT workflow: grade OJT assignment → upload test PDF as feedback file → verify learner can download
- [ ] Test completion: mark all activities complete for test learner → verify badge awarded

### Before First Cohort
- [ ] External schedule communicated to trainers and learners (email/calendar/notice board)
- [ ] Cohort created and enrolled in course
- [ ] Trainer assigned to course
- [ ] Course made Visible
- [ ] Trainer briefed on content release workflow (eye icon for Module Content folders)

---

## Role Responsibility Summary

| Task | Program Owner | Trainer | Learner | System Admin |
|---|---|---|---|---|
| Create course structure (one-time) | ✅ | | | |
| Upload Trainer Resources (lesson plans, roleplay scripts) | ✅ | | | |
| Upload Module Content (teaching slides, reference PDFs) | ✅ | | | |
| Add URL and YouTube resources | ✅ | | | |
| Configure OJT assessment rubrics | ✅ | | | |
| Configure restrict access conditions (one-time) | ✅ | | | |
| Configure completion conditions (one-time) | ✅ | | | |
| Configure badge (one-time) | ✅ | | | |
| Assign cohort and Trainer each cycle | ✅ | | | |
| Update course content between cohorts | ✅ | | | |
| Access Trainer Resources | | ✅ | | |
| Release Module Content folder to learners | | ✅ | | |
| Add attendance sessions each cycle | | ✅ | | |
| Conduct training and OJT assessments | | ✅ | | |
| Mark attendance (triggers unlock) | | ✅ | | |
| Grade module assignments | | ✅ | | |
| Grade OJT assessments + upload scanned report | | ✅ | | |
| View pre/post-session reference materials | | | ✅ | |
| Attend sessions and OJT | | | ✅ | |
| Attempt quizzes | | | ✅ | |
| View OJT feedback and scanned report | | | ✅ | |
| Download badge | | | ✅ | |
| Monitor activity completion | ✅ | | | |
| Export completion and attendance records | ✅ | | | |
| Reset quiz attempts if needed | | | | ✅ |
| Create site-level cohorts | | | | ✅ |
| Enroll learners in stream courses | | | | ✅ |
| Install plugins | | | | ✅ |
| Manage user accounts | | | | ✅ |

---

## Resource and Content Types Reference

| Content | Moodle activity | Visible to | Controlled by | Best used for |
|---|---|---|---|---|
| Lesson plan (PDF) | File in Trainer Resources folder | Trainer only (role restriction) | Program Owner | Session delivery guide |
| Roleplay script (PDF) | File in Trainer Resources folder | Trainer only (role restriction) | Program Owner | Role-based activities |
| Facilitator guide (PDF) | File in Trainer Resources folder | Trainer only (role restriction) | Program Owner | Timing, facilitation notes |
| Teaching slides (PDF) | File in Module Content folder | Hidden until Trainer releases | Trainer | In-session and post-session reference |
| Reference PDFs | File in Module Content folder | Hidden until Trainer releases | Trainer | Learner ready reference for assessments |
| External reference (URL) | URL in Module Content folder | Hidden until Trainer releases | Trainer | Guidelines, protocols, standards |
| YouTube video | URL activity (Embed) | Always visible to learners | Program Owner | Pre/post-session viewing |
| External article/website | URL activity (New tab) | Always visible to learners | Program Owner | Reading reference |
| OJT scanned report | Feedback file on Assignment | Learner (own report only) | Trainer | Evaluation record, compliance documentation |

---

## Access and Unlock Logic

| Event | Trigger | Result |
|---|---|---|
| Trainer marks attendance — Present | Attendance activity graded | Module quiz or assignment unlocks for that learner |
| Trainer marks attendance — Absent | Attendance activity graded | Quiz/assignment stays locked |
| Makeup session attended | Trainer marks Present in makeup session | Quiz/assignment unlocks |
| Trainer releases Module Content folder | Eye icon toggled to visible | Folder and all contents become visible to learners |
| Module 1 Quiz + Module 2 Assignment complete | Both activities complete | Cross-Module OJT Assessment 1 unlocks |
| Module 3 Quiz + Module 4 Assignment complete | Both activities complete | Cross-Module OJT Assessment 2 unlocks |
| All modules + both OJT assessments complete | All activity completions recorded | Final Assessment unlocks |
| Final Assessment passed | Grade ≥ 60% | Course complete, badge awarded, stream course access granted (after enrollment) |
| New cohort enrolled | Previous cohort unenrolled | Same course, clean slate — content folders revert to saved visibility state |

---

## Key Moodle Navigation Reference

| Task | Path |
|---|---|
| Add Trainer Resources folder | Add activity → Folder → Restrict access by Trainer role |
| Add Module Content folder | Add activity → Folder → set Availability to Hidden |
| Release/hide Module Content | Course → Turn editing on → eye icon on folder |
| Add YouTube/URL resource | Add activity → URL → Display: Embed or New tab |
| Add Attendance session | Attendance activity → Add session |
| Mark attendance | Attendance activity → Session → Take attendance |
| Grade OJT + upload scanned report | Assignment → View all submissions → learner → Feedback files → upload PDF |
| Set restrict access on activity | Activity settings → Restrict access → Add restriction |
| Attendance report | Attendance activity → Report |
| Activity completion report | Course → Reports → Activity completion |
| Course completion report | Course → Reports → Course completion |
| Gradebook | Course → Grades → Grader report |
| Question bank | Course → Question Bank → Questions |
| Enroll / unenroll cohort | Course → Participants → Enrollment methods |
| Badges | Course → More → Badges |
| Badge recipients | Course → More → Badges → Manage badges → [badge name] → Recipients |
| Reset quiz attempt (System Admin only) | Course → Quiz → Results → Grades → learner → Delete attempt |

---

*Document version: 1.4 | Shroff Charitable Eye Hospital | Moodle 5.x | Attendance plugin | Single course model*
