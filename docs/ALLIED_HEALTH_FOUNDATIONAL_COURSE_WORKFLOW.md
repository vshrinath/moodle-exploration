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
| Quiz/Assignment access | Manually shown by Trainer after marking attendance (visibility control) |
| Trainer resources | Lesson plans, roleplay scripts, facilitator guides — always hidden from learners |
| Learner content | Teaching material, reference guides — Trainer controls when to release |
| External resources | YouTube videos, URLs — embedded or linked within days |
| Cross-day assessments | OJT/practical evaluations spanning multiple days, scanned reports uploaded as feedback |
| Cohort model | One permanent course, cohorts enrolled and unenrolled each cycle |
| Roles involved | Program Owner, Trainer, Learner, System Admin |
| Completion leads to | Stream course access (Nursing / OT / Optometry Tech) |

---

## Automation Test Input (No Bulk Import)

For this phase, use local content directly from:

- `test_content/Allied Health Program`

Do not use bulk import yet. Use the Week/Day folder assets in that path and map them manually in Moodle during test runs with mock roles.

---

## Terminology Mapping (Current Test Phase)

To preserve historical detail while using the new folder pattern, apply this mapping throughout this document:

- `Module [N]` => `Week/Day bundle` (for example `01. Week 1 / 01. Day 1`)
- `Module Content` => `Day Content`
- `Cross-Module Assessment` => `Cross-Day Assessment`

For automation and test scripting in this phase, treat **module**, **week**, and **day unit** as the same execution unit.
When a step references older module labels, execute it against the equivalent Week/Day folder structure.

---

## Course Structure

```text
Allied Health — Foundational  [one permanent course]
│
├── 01. Week 1/
│   ├── 01. Day 1/
│   │   ├── content/
│   │   ├── lesson_plan/
│   │   ├── quiz/
│   │   ├── assignment/
│   │   ├── roleplay/
│   │   └── rubric/
│   └── 02. Day 2/
│       ├── content/
│       ├── lesson_plan/
│       ├── quiz/
│       ├── assignment/
│       ├── roleplay/
│       └── rubric/
├── 02. Week 2/
│   └── 01. Day 1/
│       ├── content/
│       ├── lesson_plan/
│       ├── quiz/
│       ├── assignment/
│       ├── roleplay/
│       └── rubric/
└── Final Assessment
    └── Foundational Final MCQ (Trainer-controlled visibility)
```

---

**Grading weightage:**

| Component | Weight | Type |
|---|---|---|
| Attendance (all sessions) | Completion gate | Pass/Fail |
| Day quizzes | 20% | Online, auto-graded |
| Practical assignments | 20% | Offline, Trainer-graded |
| Cross-day OJT assessments | 20% | Offline, Trainer-graded + scanned report |
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

### Step 2 — Create Week/Day Sections

Turn editing on and create sections by week:
- `01. Week 1`
- `02. Week 2`
- continue as needed

Within each week, add day labels (`01. Day 1`, `02. Day 2`, etc.) and place day resources/activities in that order.

### Step 3 — Add Trainer Resources Folder (one per day)

First item in each day block — above all learner-facing content.

1. **Add activity → Folder**
2. Configure:
   - Name: `Trainer Resources — Week [W] Day [D]`
   - Display: On a separate page
3. Upload files: lesson plan, roleplay script, facilitator guide
4. **Restrict access → Add restriction → User profile → Role contains Trainer**

> Completely invisible to learners — no lock icon shown. Program Owner can update files at any time without changing settings.

> **Permission check:** Your `sceh_trainer` role must include `moodle/course:activityvisibility` and `moodle/course:manageactivities` so Trainers can show/hide Day Content folders and activities using the eye icon.

### Step 4 — Add Day Content Folder (one per day)

Second item in each day block — below Trainer Resources, above optional day URLs.

1. **Add activity → Folder**
2. Configure:
   - Name: `Day Content — Week [W] Day [D]`
   - Display: On a separate page
   - Availability: **Hide from learners** (eye icon — set to hidden at setup)
3. Upload files: teaching slides, reference PDFs
4. Add URL resources inside the folder description or as separate URL activities if external links need to be tracked individually
5. No Restrict Access needed — visibility is controlled by the hide/show toggle

> **Trainer releases this folder** by clicking the eye icon on the activity when they choose — before, during, or after the session. Once visible, learners can use it as a reference while completing assessments. Program Owner can also release it if needed.

> Unlike Trainer Resources (which uses Role restriction and is permanently hidden), the Content folder uses simple show/hide — Trainer controls timing, not permanent access.

### Step 5 — Add Day URL/Video Resources

For each day block, add pre-session and post-session reference materials:

1. **Add activity → URL**
2. Configure:
   - Name: e.g., `Pre-session: Introduction to Eye Anatomy`
   - External URL: paste YouTube or external link
   - Display:
     - **Embed** — for YouTube (plays inline within Moodle)
     - **Open in new tab** — for external websites, PDFs, or guidelines
3. Completion tracking: optional — set "Learner must view" if tracking is needed, leave untracked if supplementary

**YouTube embed note:** Use full YouTube URLs (`https://www.youtube.com/watch?v=VIDEO_ID`) for best compatibility. Shortened `youtu.be` links also work but may require manual embed code in some Moodle versions. Moodle auto-detects and embeds the player when Display is set to Embed.

### Step 6 — Add Attendance Activities (one per day/session)

For each teachable day/session:

1. **Add activity → Attendance**
2. Configure:
   - Name: e.g., `Attendance — Week 1 Day 1`
   - Grade: pass/fail
   - Student recording: **Disabled**
3. Statuses: P = Present, A = Absent, L = Late, E = Excused
4. Completion tracking: **Student must receive a grade to complete this activity**
5. Save — Trainer adds sessions before each class

> **Note:** Attendance activities are used for tracking and reporting only. Trainers manually show/hide quizzes/assignments after marking attendance, giving them control over when content becomes visible regardless of attendance status.

### Step 7 — Build Question Bank

1. Course → **Question Bank → Categories** → create categories by week/day and final assessment:
   - e.g., `Week 1 Day 1`, `Week 1 Day 2`, `Week 2 Day 1`, `Final Assessment`
2. Add 30–40 questions per category
3. Question types: Multiple Choice (primary), True/False, Matching

### Step 8 — Add Day Quiz Activities

1. **Add activity → Quiz**
2. Configure:
   - Open/close dates: **leave blank**
   - Time limit: 30 minutes, Attempts: 2, Pass grade: 60%
   - Review: show answers only after all attempts used
   - Availability: **Hide from learners** (Trainer will show after marking attendance)
3. Add 20 random questions from category pool
4. Completion tracking: must achieve passing grade

> **Note:** Quizzes start hidden. Trainer shows them after marking attendance, giving control over when learners can attempt based on attendance status and session readiness.

### Step 9 — Add Day Assignment Activities

1. **Add activity → Assignment**
2. Configure:
   - Submission type: **None**
   - Grading method: Rubric (recommended), Pass grade: 60%
   - Availability: **Hide from learners** (Trainer will show after marking attendance)
3. Define rubric criteria per day activity
4. Completion tracking: must receive a grade

> **Note:** Assignments start hidden. Trainer shows them after marking attendance, giving control over when learners can be evaluated based on attendance status and session readiness.

### Step 10 — Add Cross-Day OJT Assessment Activities

Place these after completing the first set of day activities.

**Example: Cross-Day Assessment 1 (after Week 1 Day 2):**

1. **Add activity → Assignment**
2. Configure:
   - Name: `OJT Assessment — Week 1 (Day 1 + Day 2)`
   - Description: criteria being evaluated, OJT context (on-ward, simulation, etc.)
   - Submission type: **None** — Trainer evaluates and uploads scanned report
   - Grading method: **Rubric** spanning criteria from both days
   - Maximum grade: 100, Pass grade: 60%
   - Availability: **Hide from learners** (Trainer will show when learners are ready)
3. Completion tracking: must receive a grade

> **Note:** OJT assessments start hidden. Trainer shows them when both prerequisite day activities are complete and learners are ready for cross-day evaluation.

**Additional cross-day assessments:** Follow the same pattern for subsequent week/day combinations as needed.

### Step 11 — Add Final Assessment Quiz

1. **Add activity → Quiz**
2. Configure:
   - Name: `Foundational Final Assessment`
   - Open/close dates: **leave blank**
   - Time limit: 45 minutes, Attempts: 2, Pass grade: 60%
   - Availability: **Hide from learners** (Trainer will show when all prerequisites complete)
3. Add 30 random questions drawing from all 4 question bank categories
4. Completion tracking: must achieve passing grade

> **Note:** Final Assessment starts hidden. Trainer shows it when all module activities and both OJT assessments are complete, giving control over final assessment timing.

### Step 12 — Configure Gradebook

1. Course → **Grades → Gradebook setup**
2. Set aggregation: **Weighted mean of grades**
3. Assign weights based on your program structure:
   - Day quizzes: 20%
   - Day practical assignments: 20%
   - Cross-day OJT assessments: 20%
   - Final assessment: 40%

### Step 13 — Set Course Completion Conditions

Course → **Course completion** → add conditions:
- All Attendance activities complete
- All day quizzes at passing grade
- All day assignments graded
- All OJT cross-day assessments graded
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

1. Open **Trainer Resources — Week [W] Day [D]** folder → review lesson plan, roleplay script, facilitator guide
2. Add session to the **Attendance activity**:
   - Date, time, duration, room description
   - Add separate session for each batch if running simultaneously

### Releasing Day Content to Learners

Trainer decides when to make the **Day Content — Week [W] Day [D]** folder visible:

- **Before the session** — learners can preview teaching slides as preparation
- **During the session** — Trainer reveals content live as a teaching reference
- **After the session** — content becomes a reference while learners complete assessments

To release: Course → Turn editing on → click the **eye icon** on the Day Content folder → folder becomes visible to learners immediately.

To hide again if needed: click the eye icon again — folder returns to hidden state.

### On the Day — Mark Attendance and Show Content

1. Attendance activity → open the session → mark each learner: P / A / L / E → save
2. After marking attendance, manually show/hide quizzes and assignments:
   - Turn editing on → click eye icon on quiz/assignment to make visible
   - Eye icon visibility is activity-wide (applies to all enrolled learners)
   - If absentees need a delayed attempt, keep the activity hidden for everyone until makeup, or use a separate makeup activity/attempt policy
3. This gives Trainer control over when content becomes visible regardless of attendance status

### Grading Day Assignments

1. Course → Assignment → **View all submissions**
2. Click learner → select rubric levels → add feedback → save
3. Or use **Quick grading** for inline entry across the cohort
4. Learner notified by email

### Conducting and Grading Cross-Day OJT Assessments

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
- If passed: Final Assessment becomes available (if all other prerequisites are also met)

### Monitoring Quiz Performance

Course → Quiz → **Results → Grades** — scores per learner
Course → Quiz → **Results → Statistics** — question-level failure analysis

---

## 3. Learner Journey — Login to Certificate

### Step 1 — Login and Dashboard

- Access Moodle via hospital URL
- Dashboard shows enrolled course with progress bar
- Trainer Resources folders are completely invisible
- Day Content folders appear hidden until Trainer releases them

### Step 2 — Pre-session Preparation

- If Trainer has released Day Content folder: review teaching slides and reference material
- Watch pre-session YouTube video if available (plays inline in Moodle)
- Both are optional — no completion condition on reference materials unless set by Program Owner

### Step 3 — Attend Physical Session

- Attend at communicated date, time, and room
- No Moodle action required
- Trainer marks attendance and manually shows quiz or assignment when ready

### Step 4 — Access Day Content as Reference

- If Trainer has released the Day Content folder (during or after session): folder is now visible
- Learner can open teaching slides, reference PDFs, external guidelines as ready reference
- Particularly useful when completing practical assignments — learner can refer to content while Trainer evaluates

### Step 5 — Attempt Day Quiz

- Trainer shows quiz after marking attendance
- 30-minute limit, 2 attempts, 60% pass grade
- Score shown immediately after submission

### Step 6 — Practical Assignment

- Trainer shows assignment after marking attendance
- Trainer observes and grades — no learner submission
- Learner receives email with score and rubric feedback once graded

### Step 7 — Cross-Day OJT Assessment

- Trainer shows OJT assessment when both prerequisite day activities are complete
- Learner participates in OJT or simulation (scheduled outside Moodle)
- Trainer grades and uploads scanned evaluation report in Moodle
- Learner receives email notification → can view grade, feedback, and download the scanned report

### Step 8 — Final Assessment

- Trainer shows Final Assessment when all day activities and OJT assessments are complete
- 45-minute limit, 2 attempts, 60% pass grade
- Draws from all topic areas

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
| Day Content | Open folder → add/replace files | Immediate — visible to learners if folder is already released |
| URL resources | Edit activity → update URL | Immediate |
| Question bank | Question Bank → add/edit questions | Applies to next quiz attempt |
| OJT rubric criteria | Assignment settings → edit rubric | Applies to ungraded submissions |

### During Cohort — Regular Monitoring

1. Course → **Reports → Activity completion** — grid view, filter by cohort
2. Course → **Grades → Grader report** — full grade breakdown, identify ungraded OJT assessments
3. Course → **Attendance → Report** — attendance summary per learner

### Actions on Exceptions

| Situation | Action |
|---|---|
| Day Content folder not yet released | Check with Trainer — learners can't access reference material |
| Attendance not marked after session | Follow up with Trainer — Trainer needs attendance record to decide when to show activities |
| Quiz/assignment still hidden after attendance marked | Check with Trainer — manual visibility control may be intentional (e.g., waiting for makeup session) |
| OJT assessment not graded | Follow up with Trainer — Final Assessment can't be shown until OJT is complete |
| Scanned report not uploaded | Remind Trainer — learner grade is complete but report is missing from record |
| Assignment not graded | Follow up with Trainer |
| Learner failed quiz twice | Allow additional attempt (System Admin) or flag for remediation |
| Learner absent | Arrange makeup session — Trainer shows quiz/assignment after makeup attendance is marked |

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
5. Day Content folders revert to their saved state — if hidden, they stay hidden for the new cohort until Trainer releases them again
6. Communicate new schedule outside Moodle
7. Refresh question bank periodically

---

## Troubleshooting

### Common Issues and Solutions

**Learner says quiz is still hidden after attendance marked:**
- Verify Trainer has clicked the eye icon to show the quiz (attendance marking alone doesn't make it visible)
- Check quiz is set to visible (eye icon should be open, not crossed out)
- Refresh learner's browser (Ctrl+F5 or Cmd+Shift+R)
- Verify learner is enrolled in the course

**Quiz visible for absent learner:**
- Manual visibility model means Trainer controls visibility regardless of attendance status
- This is expected behavior — Trainer can choose to show content to absent learners if needed
- If this was unintentional, Trainer can hide the quiz again by clicking the eye icon

**Day Content folder not visible to learners:**
- Trainer must click the eye icon to release (folder is hidden by default)
- Editing mode must be ON when clicking eye icon
- Verify folder is not role-restricted (only Trainer Resources should have role restriction)

**OJT scanned report not uploading:**
- File size limit: 10MB default (check site settings if larger files needed)
- Accepted formats: PDF recommended (PNG/JPG also work but PDF is standard for compliance)
- Upload in **Feedback files** section, not in Comments field
- Ensure file is selected before clicking "Save changes"

**Final Assessment not visible:**
- Verify Trainer has clicked the eye icon to show the Final Assessment
- Check all prerequisites are complete (day quizzes, assignments, and OJT assessments)
- Trainer controls when Final Assessment becomes visible — may be intentionally waiting for all learners to complete prerequisites

**Trainer can't see Trainer Resources folder:**
- Verify user has `sceh_trainer` role and is enrolled in course
- Verify role capabilities include `moodle/course:activityvisibility` and `moodle/course:manageactivities`
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
- [ ] Week/Day sections created following folder structure
- [ ] Completion tracking enabled at course level

### Trainer Resources (one per day)
- [ ] Trainer Resources folders created for each day with role restriction
- [ ] Test: Trainer can see all folders
- [ ] Test: Learner cannot see any Trainer Resources folders (completely invisible)

### Day Content (one per day)
- [ ] Day Content folders created for each day and set to Hidden
- [ ] Test: Trainer can toggle visibility (eye icon works)
- [ ] Test: Learner sees "Not available" when hidden

### Activities
- [ ] Attendance activities created (one per day/session)
- [ ] Quiz activities created as needed per day
- [ ] Assignment activities created as needed per day
- [ ] OJT Assessment assignments created (cross-day sections)
- [ ] 1 Final Assessment quiz created
- [ ] URL/YouTube resources added per day (optional but recommended)

### Question Bank
- [ ] Question categories created by week/day and final assessment
- [ ] 30-40 questions added per category
- [ ] Questions reviewed for accuracy and clarity

### Activity Visibility Settings
- [ ] All day quizzes: set to Hidden (Trainer shows after attendance)
- [ ] All day assignments: set to Hidden (Trainer shows after attendance)
- [ ] All cross-day OJT assessments: set to Hidden (Trainer shows when prerequisites complete)
- [ ] Final Assessment: set to Hidden (Trainer shows when all prerequisites complete)

### Grading Configuration
- [ ] Gradebook aggregation set to Weighted mean
- [ ] Weights configured: Day quizzes 20%, Day assignments 20%, OJT assessments 20%, Final 40%
- [ ] Pass grade set to 60% for all graded activities
- [ ] Course passing grade set to 60%

### Course Completion
- [ ] All Attendance activities required
- [ ] All quizzes require passing grade
- [ ] All assignments require grade
- [ ] All OJT assessments require grade
- [ ] Final assessment requires passing grade
- [ ] Overall grade ≥60% required

### Badge Configuration
- [ ] Badge created with appropriate name and image
- [ ] Badge criteria set to Course completion
- [ ] Badge enabled

### Testing (Critical)
- [ ] Test as Trainer: can see Trainer Resources, can toggle Day Content visibility
- [ ] Test as Learner: cannot see Trainer Resources, Day Content shows as hidden
- [ ] Test manual visibility flow: create test attendance session → mark test learner Present → Trainer shows quiz via eye icon → verify learner can access
- [ ] Test OJT workflow: grade OJT assignment → upload test PDF as feedback file → verify learner can download
- [ ] Test completion: mark all activities complete for test learner → verify badge awarded

### Before First Cohort
- [ ] External schedule communicated to trainers and learners (email/calendar/notice board)
- [ ] Cohort created and enrolled in course
- [ ] Trainer assigned to course
- [ ] Course made Visible
- [ ] Trainer briefed on content release workflow (eye icon for Day Content folders)

---

## Role Responsibility Summary

| Task | Program Owner | Trainer | Learner | System Admin |
|---|---|---|---|---|
| Create course structure (one-time) | ✅ | | | |
| Upload Trainer Resources (lesson plans, roleplay scripts) | ✅ | | | |
| Upload Day Content (teaching slides, reference PDFs) | ✅ | | | |
| Add URL and YouTube resources | ✅ | | | |
| Configure OJT assessment rubrics | ✅ | | | |
| Configure activity visibility settings (one-time) | ✅ | | | |
| Configure completion conditions (one-time) | ✅ | | | |
| Configure badge (one-time) | ✅ | | | |
| Assign cohort and Trainer each cycle | ✅ | | | |
| Update course content between cohorts | ✅ | | | |
| Access Trainer Resources | | ✅ | | |
| Release Day Content folder to learners | | ✅ | | |
| Add attendance sessions each cycle | | ✅ | | |
| Conduct training and OJT assessments | | ✅ | | |
| Mark attendance and show/hide activities | | ✅ | | |
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
| Teaching slides (PDF) | File in Day Content folder | Hidden until Trainer releases | Trainer | In-session and post-session reference |
| Reference PDFs | File in Day Content folder | Hidden until Trainer releases | Trainer | Learner ready reference for assessments |
| External reference (URL) | URL in Day Content folder | Hidden until Trainer releases | Trainer | Guidelines, protocols, standards |
| YouTube video | URL activity (Embed) | Always visible to learners | Program Owner | Pre/post-session viewing |
| External article/website | URL activity (New tab) | Always visible to learners | Program Owner | Reading reference |
| OJT scanned report | Feedback file on Assignment | Learner (own report only) | Trainer | Evaluation record, compliance documentation |

---

## Access and Visibility Logic

| Event | Trigger | Result |
|---|---|---|
| Trainer marks attendance | Attendance activity graded | Attendance recorded — no automatic visibility change |
| Trainer shows quiz/assignment | Eye icon toggled to visible after attendance | Module quiz or assignment becomes visible to learners |
| Makeup session attended | Trainer marks Present in makeup session | Trainer can show the activity for the cohort once makeup is complete, or use separate makeup activity/attempt handling |
| Trainer releases Day Content folder | Eye icon toggled to visible | Folder and all contents become visible to learners |
| Trainer shows OJT assessment | Eye icon toggled to visible | Cross-Day OJT Assessment becomes visible when Trainer determines learners are ready |
| Trainer shows Final Assessment | Eye icon toggled to visible | Final Assessment becomes visible when Trainer determines all prerequisites are complete |
| Final Assessment passed | Grade ≥ 60% | Course complete, badge awarded, stream course access granted (after enrollment) |
| New cohort enrolled | Previous cohort unenrolled | Same course, clean slate — all activities revert to hidden state |

---

## Key Moodle Navigation Reference

| Task | Path |
|---|---|
| Add Trainer Resources folder | Add activity → Folder → Restrict access by Trainer role |
| Add Day Content folder | Add activity → Folder → set Availability to Hidden |
| Release/hide Day Content | Course → Turn editing on → eye icon on folder |
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

*Document version: 1.5 | Shroff Charitable Eye Hospital | Moodle 5.x | Attendance plugin | Single course model*
