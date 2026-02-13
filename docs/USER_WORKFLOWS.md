# User Workflows & Journeys

**Purpose:** Complete reference for understanding how each role interacts with the system  
**Audience:** Trainers, administrators, developers, and stakeholders  
**Last Updated:** 2026-02-13

---

## Table of Contents

1. [Overview](#overview)
2. [System Admin Workflows](#system-admin-workflows)
3. [Program Owner Workflows](#program-owner-workflows)
4. [Trainer Workflows](#trainer-workflows)
5. [Learner Workflows](#learner-workflows)
6. [Missing Pieces & Dependencies](#missing-pieces--dependencies)
7. [Critical Dependencies](#critical-dependencies)
8. [Workflow Sequence](#workflow-sequence)
9. [Detailed Workflows](#detailed-workflows)

---

## Overview

### Four Primary Roles (+ Optional Trainer Coach)

**System Admin** - Oversight & Insight
- Manages users and system configuration
- Monitors organization-wide performance
- Handles permissions and access control

**Program Owner** - Learning Design Authority
- Creates programs and defines curriculum
- Manages competency frameworks
- Sets completion criteria and grading rules

**Trainer** - Delivery & Enablement
- Delivers training to assigned cohorts
- Grades assessments and provides feedback
- Awards badges and tracks learner progress

**Trainer Coach** (Optional) - Delivery Quality Oversight
- Monitors trainer performance across cohorts
- Provides coaching and professional development
- Creates trainer training materials
- Same as Trainer role + enhanced dashboard view

**Learner** - Participant
- Completes learning activities
- Achieves competencies
- Chooses specialization streams

---

## System Admin Workflows

### Initial Setup (One-Time)
1. Create user accounts
2. Assign roles (Program Owner, Trainer, Learner)
3. Create cohorts
4. System configuration (competencies, completion, badges)

### Ongoing Operations
1. User management (add, deactivate, reset passwords)
2. Cohort management (create, assign trainers, enroll in programs)
3. Monitoring & reporting (organization-wide metrics)
4. Support (permission issues, access problems)
5. Trainer performance monitoring
6. Automated report configuration

---

## Program Owner Workflows

### Creating a New Program (One-Time per Program)
1. Create program structure
2. Define competency framework
3. Structure learning path (sections for streams)
4. Add content to Common Foundation
5. Add stream choice activity
6. Add content to each stream
7. Configure conditional access
8. Set completion criteria
9. Create assessments
10. Set grading rules
11. Create badges (define criteria)
12. Publish program

### Ongoing Maintenance
1. Update content
2. Monitor program health
3. Iterate on design
4. Version programs
5. Review trainer feedback
6. Analyze cohort performance data

---

## Trainer Workflows

### Before Cohort Starts (One-Time per Cohort)
1. Review program structure
2. Review learner roster

### During Cohort (Ongoing)
1. Mark attendance
2. Review submissions
3. Grade assessments
4. Award badges (manual)
5. Monitor learner progress
6. Facilitate sessions
7. Communicate with learners

### After Cohort Ends
1. Final grading
2. Award completion badges
3. Provide feedback to Program Owner

---

## Trainer Coach Workflows (Optional Role)

**Note:** Trainer Coach is not a separate role. It's the Trainer role + enhanced dashboard view for those in the "Trainer Coaches" cohort.

### As Program Owner (Creating Training Materials)
1. Create "Trainer Excellence Program" (meta-course)
2. Add teaching videos, best practices, pedagogy content
3. Update training materials based on feedback

### As Trainer (Delivering to Trainers)
1. Facilitate trainer workshops
2. Lead trainer discussions
3. Grade trainer assignments (teaching portfolios, peer observations)
4. Provide coaching feedback

### As Coach (Monitoring Performance)
1. View trainer performance dashboard
   - All trainers' completion rates
   - Average grades per trainer
   - Time to grade metrics
   - At-risk learner counts
2. Identify struggling trainers
3. Schedule coaching sessions
4. Observe teaching sessions
5. Pair struggling trainers with high performers
6. Track improvement over time

### Weekly Review
1. Check trainer performance dashboard
2. Identify red flags:
   - Completion rate <70% (vs. >85% target)
   - Average grade <75% (vs. >80% target)
   - Time to grade >7 days (vs. <3 days target)
   - >30% at-risk learners (vs. <15% target)
3. Contact trainers needing support
4. Document coaching interventions

---

## Learner Workflows

### Getting Started (First Week)
1. Log in and explore dashboard
2. View program overview
3. Start Common Foundation

### During Common Foundation
1. Progress through content
2. Engage with trainer
3. Choose stream (permanent decision)

### During Specialized Stream
1. Continue learning in chosen stream
2. Track progress and competencies
3. Complete program and earn badges

---

## Missing Pieces & Dependencies

### 1. Enrollment Process
- **Who:** System Admin
- **What:** Enroll cohorts in programs
- **When:** Before cohort starts
- **How:** Cohort enrollment feature in Moodle

### 2. Communication Setup
- **Who:** System Admin or Program Owner
- **What:** Forums, announcements, messaging
- **When:** During program creation
- **How:** Enable forum, announcement block

### 3. Backup & Recovery
- **Who:** System Admin
- **What:** Regular backups of program content
- **When:** Weekly/monthly automated
- **How:** Moodle backup system + external storage

### 4. Reporting Setup
- **Who:** System Admin
- **What:** Configure custom reports
- **When:** Initial setup
- **How:** Moodle report builder + scheduled tasks

### 5. Badge System
- **Who creates:** Program Owner (defines criteria)
- **Who awards:** Trainers (manual) OR System (automatic)
- **What:** Visual recognition of achievement
- **When:** After learner meets criteria
- **How:** Moodle badges system

### 6. Attendance Rules (Optional)
- **Who:** Program Owner
- **What:** Configure attendance requirements
- **When:** During program creation
- **How:** Rules engine (local_sceh_rules)

### 7. Competency Evidence Rules
- **Who:** Program Owner
- **What:** Define what counts as evidence
- **When:** During competency framework setup
- **How:** Competency framework settings

### 8. Trainer Performance Monitoring
- **Who monitors:** System Admin or Trainer Coach
- **What to track:**
  - Cohort completion rates vs. other cohorts
  - Average grades per cohort
  - Time to grade submissions
  - Learner feedback/ratings
  - Attendance patterns
- **Reports:** Weekly/monthly automated
- **How:** Custom reports + scheduled tasks

### 9. Automated Reporting
- **Who sets up:** System Admin
- **What reports:**
  - Weekly cohort progress summary (to trainers)
  - Monthly program health report (to Program Owner)
  - Weekly trainer performance (to Trainer Coaches)
  - At-risk learner alerts (to trainers)
- **Delivery:** Email or dashboard notifications
- **How:** Scheduled tasks + email notifications

### 10. Trainer Coach Capability (Optional)
- **Who:** Trainers in "Trainer Coaches" cohort
- **What:** Enhanced dashboard showing all trainers' performance
- **When:** Week 6 (optional, after base system)
- **How:** Cohort-based detection + enhanced dashboard view
- **Includes:**
  - Trainer Training Program (meta-course for trainer development)
  - Cross-cohort performance visibility (read-only)
  - Coaching queue and alerts
  - Comparative trainer analytics

---

## Critical Dependencies

### Before Program Owner Can Create Program
- ✅ System Admin must create their account
- ✅ System Admin must assign "Program Owner" role
- ✅ Competency framework must be enabled
- ✅ Badge system must be enabled (if using)

### Before Trainers Can Deliver
- ✅ Program Owner must create and publish program
- ✅ System Admin must create trainer accounts
- ✅ System Admin must assign "Trainer" role
- ✅ System Admin must create cohorts
- ✅ System Admin must assign trainers to cohorts
- ✅ System Admin must enroll cohorts in programs
- ✅ Trainers must have badge awarding permissions

### Before Learners Can Start
- ✅ Program Owner must publish program
- ✅ System Admin must create learner accounts
- ✅ System Admin must add learners to cohorts
- ✅ System Admin must enroll cohorts in programs

---

## Workflow Sequence (New Program Launch)

```
Week -4: System Admin
├── Create user accounts (Program Owner, Trainers, Learners)
├── Assign roles
├── Create cohorts (including "Trainer Coaches" if using)
└── Configure reporting system

Week -3 to -1: Program Owner
├── Create program structure
├── Define competencies
├── Add content
├── Configure streams
├── Set completion criteria
├── Create badges
└── Publish program

Week -1: System Admin
├── Enroll cohorts in program
├── Assign trainers to cohorts
└── Verify trainer badge permissions

Week -1 (Optional): Trainer Coach
├── Create "Trainer Excellence Program"
├── Add trainer training content
└── Enroll all trainers as learners

Week 0: Trainers
├── Review program
├── Review badge criteria
└── Prepare for delivery

Week 1+: Learners
└── Start learning

Ongoing: All roles
├── System Admin: Monitor, support, review trainer performance
├── Program Owner: Update content, monitor health, review reports
├── Trainers: Deliver, grade, award badges, support
├── Trainer Coaches (optional): Monitor trainer performance, provide coaching
└── Learners: Learn, complete, achieve, earn badges
```

---


## Detailed Workflows

---

### SYSTEM ADMIN: Initial Setup

#### 1. Create User Accounts

**Goal:** Set up all users in the system

**Steps:**
1. Navigate to: Site administration → Users → Accounts → Add a new user
2. For each user, enter:
   - Username (e.g., jsmith)
   - Password (temporary, user will change)
   - First name, Last name
   - Email address
   - ID number (employee/student ID)
3. Click "Create user"

**Bulk Import Option:**
1. Prepare CSV file with columns: username, firstname, lastname, email, idnumber
2. Navigate to: Site administration → Users → Accounts → Upload users
3. Upload CSV file
4. Map columns
5. Preview and confirm

**Time:** 5 minutes per user (manual) or 30 minutes for 100 users (bulk)

---

#### 2. Assign Roles

**Goal:** Give users appropriate permissions

**Steps:**
1. Navigate to: Site administration → Users → Permissions → Assign system roles
2. Click on role to assign (e.g., "Program Owner")
3. Search for user
4. Click "Add" to assign role
5. Repeat for all users

**Role Assignments:**
- **Program Owner:** Curriculum designers, learning architects
- **Trainer:** Facilitators, instructors, mentors
- **Learner:** Students, fellows, residents

**Time:** 2 minutes per user

---

#### 3. Create Cohorts

**Goal:** Group users for bulk enrollment and reporting

**Steps:**
1. Navigate to: Site administration → Users → Accounts → Cohorts
2. Click "Add new cohort"
3. Enter:
   - Name: "2024 Fellows - Dr. Smith"
   - Cohort ID: "2024-fellows-smith"
   - Description: "Ophthalmology fellows for 2024 batch, trainer Dr. Smith"
4. Click "Save changes"
5. Click "Assign" next to cohort name
6. Search for users
7. Add users to cohort

**Naming Convention:**
- Format: `[Year] [Program] - [Trainer Name]`
- Examples:
  - "2024 Fellows - Dr. Smith"
  - "2025 Residents - Dr. Jones"

**Time:** 5 minutes per cohort + 1 minute per user assignment

---

#### 4. System Configuration

**Goal:** Enable required features

**Steps:**

**Enable Competency Framework:**
1. Navigate to: Site administration → Competencies → Settings
2. Check "Enable competencies"
3. Check "Push course ratings to user plans"
4. Save changes

**Enable Completion Tracking:**
1. Navigate to: Site administration → Advanced features
2. Check "Enable completion tracking"
3. Save changes

**Enable Badges:**
1. Navigate to: Site administration → Badges → Badges settings
2. Check "Enable badges"
3. Set badge issuer details (organization name, contact)
4. Save changes

**Configure Notifications:**
1. Navigate to: Site administration → Messaging → Notification settings
2. Enable relevant notifications:
   - Assignment notifications
   - Forum posts
   - Badge awards
3. Save changes

**Time:** 30 minutes (one-time)

---

### SYSTEM ADMIN: Create Course Categories & Assign Program Owners

**Goal:** Enable Program Owners to create programs autonomously in their assigned categories

**Why:** Without this, System Admin becomes a bottleneck for every program creation.

#### Steps

**1. Create Course Categories** (10 minutes)

1. Navigate to: Site administration → Courses → Manage courses and categories
2. Click "Create new category"
3. Enter category details:
   - Name: "Allied Health Programs"
   - Category ID: "allied-health"
   - Description: "Programs for allied health professionals"
4. Click "Create category"
5. Repeat for other categories:
   - "Surgical Fellowships"
   - "Optometry Programs"
   - "Continuing Education"
   - (Add more as needed)

**Category Structure Example:**
```
Root
├── Allied Health Programs
├── Surgical Fellowships
├── Optometry Programs
└── Continuing Education
```

**2. Assign Program Owners to Categories** (5 minutes per owner)

For each category:
1. Navigate to: Site administration → Courses → Manage courses and categories
2. Click on category name (e.g., "Allied Health Programs")
3. Click "Assign roles"
4. Select "Program Owner" (sceh_program_owner)
5. Search for user (e.g., Dr. Sarah)
6. Click "Add"

**Example Assignments:**
```
Allied Health Programs → Dr. Sarah (Program Owner)
Surgical Fellowships → Dr. Ahmed (Program Owner)
Optometry Programs → Dr. Jones (Program Owner)
Continuing Education → Dr. Patel (Program Owner)
```

**3. Verify Assignments**

For each Program Owner:
1. Log in as that user (or use "Log in as" feature)
2. Verify they see only their assigned category
3. Verify they can click "Create New Program"
4. Verify default category is their assigned category
5. Log out

**Result:**
- Dr. Sarah sees only "Allied Health Programs" and can create programs there
- Dr. Ahmed sees only "Surgical Fellowships" and can create programs there
- They cannot see each other's categories or programs

**Time:** 10 minutes setup + 5 minutes per Program Owner

---

### SYSTEM ADMIN: Assign Multiple Program Owners to Same Category (Optional)

**Goal:** Allow multiple people to manage the same category

**Scenario:** Allied Health Programs has 3 Program Owners

**Steps:**
1. Navigate to: Site administration → Courses → Manage courses and categories
2. Click on "Allied Health Programs"
3. Click "Assign roles"
4. Select "Program Owner"
5. Add Dr. Sarah
6. Add Dr. Lisa
7. Add Dr. Michael

**Result:**
- All three can see all programs in "Allied Health Programs"
- All three can create new programs in this category
- All three can edit any program in this category
- They collaborate on the same set of programs

**Time:** 2 minutes per additional owner

---

### SYSTEM ADMIN: Move Program Between Categories (Rare)

**Goal:** Reorganize programs when needed

**Scenario:** "Dental Assistant Program" was created in wrong category

**Steps:**
1. Navigate to: Site administration → Courses → Manage courses and categories
2. Find "Dental Assistant Program"
3. Click "Edit" icon
4. Change "Category" dropdown to correct category
5. Save changes

**Note:** Program Owner permissions follow the category, so moving a program changes who can manage it.

**Time:** 2 minutes

---

### SYSTEM ADMIN: Enroll Cohorts in Programs

**Goal:** Give cohorts access to programs

**Steps:**
1. Navigate to program (course)
2. Click gear icon → More → Users → Enrollment methods
3. Click "Add method" → "Cohort sync"
4. Select cohort from dropdown
5. Select role: "Student" (for learners) or "Teacher" (for trainers)
6. Click "Add method"
7. Cohort members are now enrolled

**Time:** 2 minutes per cohort per program

---

### SYSTEM ADMIN: Configure Automated Reporting

**Goal:** Set up scheduled reports for monitoring

**Steps:**

**1. Create Custom Report: Cohort Performance Comparison**
1. Navigate to: Site administration → Reports → Report builder → Custom reports
2. Click "New report"
3. Name: "Cohort Performance Comparison"
4. Source: "Courses"
5. Add columns:
   - Cohort name
   - Course name
   - Enrolled users count
   - Completion rate
   - Average grade
   - Trainer name
6. Add filters:
   - Date range
   - Program (course)
7. Save report

**2. Create Custom Report: Trainer Effectiveness**
1. New report
2. Name: "Trainer Effectiveness Dashboard"
3. Source: "Users"
4. Add columns:
   - Trainer name
   - Number of cohorts
   - Average cohort completion rate
   - Average cohort grade
   - Average time to grade (days)
   - Number of at-risk learners
5. Add filters:
   - Date range
   - Trainer
6. Save report

**3. Schedule Report Delivery**
1. Open report
2. Click "Schedule"
3. Set frequency: Weekly (Monday 8am)
4. Set recipients:
   - Cohort Performance → Trainers + Program Owners
   - Trainer Effectiveness → System Admin only
5. Save schedule

**Time:** 2 hours (one-time setup)

---

### SYSTEM ADMIN: Monitor Trainer Performance

**Goal:** Identify high-performing and struggling trainers

**Steps:**

**Weekly Review:**
1. Open "Trainer Effectiveness Dashboard"
2. Sort by "Average cohort completion rate" (ascending)
3. Identify trainers with <70% completion rate
4. Review "Average time to grade" - flag if >7 days
5. Check "Number of at-risk learners" - flag if >30% of cohort

**Red Flags:**
- Completion rate <70% (vs. program average >85%)
- Average grade <75% (vs. program average >80%)
- Time to grade >7 days (vs. program average <3 days)
- >30% of learners at-risk (vs. program average <15%)

**Actions:**
1. Contact trainer to understand issues
2. Offer support or additional training
3. Pair with high-performing trainer for mentoring
4. If persistent issues, reassign cohort

**Time:** 30 minutes per week

---

### PROGRAM OWNER: Create Program Structure

**Goal:** Set up the program container in your assigned category

**Prerequisites:** System Admin has assigned you to a category (e.g., "Allied Health Programs")

#### Steps

**1. Access Your Dashboard**
1. Log in
2. Dashboard shows: "My Programs" section
3. You see your assigned category (e.g., "Allied Health Programs")
4. You see existing programs in your category (if any)

**2. Create New Program**
1. Click "Create New Program in [Your Category]"
2. Form opens with category pre-selected
3. Enter program details:
   - Course full name: "Dental Assistant Program"
   - Course short name: "DENTAL-ASSIST-2024"
   - Course category: "Allied Health Programs" (pre-selected, cannot change)
   - Course start date: Set start date
   - Course end date: Set end date (optional)
   - Description: Brief overview of program
4. Course format: "Topics" (not "Weekly")
5. Number of sections: 4 (1 common + 3 streams, adjust as needed)
6. Click "Save and display"

**3. Verify Program Created**
1. Program appears in your dashboard under your category
2. You can now add content, competencies, etc.

**Important Notes:**
- ✅ You can create programs in your assigned category
- ✅ You can see all programs in your category
- ✅ You can edit any program in your category
- ❌ You cannot see programs in other categories
- ❌ You cannot create programs in other categories
- ❌ You cannot change the category of your programs

**Time:** 10 minutes

---

### PROGRAM OWNER: View Your Programs

**Goal:** See all programs you manage

**What You See:**

```
Dashboard: My Programs
┌─────────────────────────────────────────┐
│ Allied Health Programs                   │
├─────────────────────────────────────────┤
│ Allied Assist Program                    │
│ ├── 45 learners enrolled                │
│ ├── 3 cohorts active                    │
│ └── 87% completion rate                 │
│                                          │
│ Medical Assistant Program                │
│ ├── 32 learners enrolled                │
│ ├── 2 cohorts active                    │
│ └── 91% completion rate                 │
│                                          │
│ Dental Assistant Program                 │
│ ├── 0 learners (new program)            │
│ └── Draft                                │
│                                          │
│ [Create New Program in Allied Health]   │
└─────────────────────────────────────────┘
```

**You DO NOT see:**
- Programs in "Surgical Fellowships" (Dr. Ahmed's category)
- Programs in "Optometry Programs" (Dr. Jones's category)
- Any other categories

**Time:** Instant view

---

### PROGRAM OWNER: Define Competency Framework

**Goal:** Create the skills/knowledge hierarchy

**Steps:**

**1. Create Framework:**
1. Navigate to: Site administration → Competencies → Competency frameworks
2. Click "Add new competency framework"
3. Enter:
   - Name: "Ophthalmology Fellowship Competencies"
   - ID number: "OPHTHAL-FELLOW-2024"
   - Description: "Core and specialized competencies for ophthalmology fellows"
   - Scale: Select or create scale (e.g., "Not competent, Competent, Proficient")
4. Click "Save changes"

**2. Add Competencies:**
1. Click on framework name
2. Click "Add new competency"
3. Enter:
   - Name: "Cataract Surgery"
   - ID number: "OPHTHAL-CAT-001"
   - Description: "Ability to perform cataract surgery independently"
   - Scale: Use framework scale
4. Click "Save changes"
5. To add child competency:
   - Click "Add new competency" under parent
   - Name: "Pre-operative Assessment"
   - ID number: "OPHTHAL-CAT-001-01"
6. Repeat for all competencies

**Hierarchy Example:**
```
Cataract Surgery (OPHTHAL-CAT-001)
├── Pre-operative Assessment (OPHTHAL-CAT-001-01)
│   ├── Patient History (OPHTHAL-CAT-001-01-01)
│   └── Diagnostic Tests (OPHTHAL-CAT-001-01-02)
├── Surgical Technique (OPHTHAL-CAT-001-02)
└── Post-operative Care (OPHTHAL-CAT-001-03)
```

**Time:** 2-4 hours depending on complexity

---

### PROGRAM OWNER: Structure Learning Path

**Goal:** Create sections for common foundation and streams with weekly organization

#### Understanding the Structure

**Hierarchy:**
```
PROGRAM (Course) → STREAMS (Sections) → WEEKS (Labels)
```

**Example: Allied Assist Program**
```
Program: Allied Assist Program
├── Section 1: Common Foundation (Weeks 1-3)
│   ├── Week 1 Label + Activities
│   ├── Week 2 Label + Activities
│   └── Week 3 Label + Choice Activity
├── Section 2: Front Desk Management Stream (Weeks 4-12)
│   ├── Week 4 Label + Activities
│   ├── Week 5 Label + Activities
│   └── ... (continue)
├── Section 3: Doctor Assistance Stream (Weeks 4-12)
└── Section 4: Medical Records Stream (Weeks 4-12)
```

#### Steps

**1. Create Section Structure**

1. Navigate to program (course)
2. Turn editing on
3. Rename sections:
   - Section 1: "Common Foundation (Weeks 1-3)"
   - Section 2: "Front Desk Management (Weeks 4-12)"
   - Section 3: "Doctor Assistance (Weeks 4-12)"
   - Section 4: "Medical Records (Weeks 4-12)"
4. Add section descriptions explaining what each stream covers
5. Save changes

**2. Add Weekly Labels to Organize Content**

Within Section 1 (Common Foundation):
1. Click "Add an activity or resource"
2. Select "Label"
3. Enter text: "Week 1: Professional Foundations"
4. Format as heading (H3)
5. Save and return to course

Repeat for each week:
- Week 1: Professional Foundations
- Week 2: Compliance & Terminology
- Week 3: Integration & Choice

Within Section 2 (Front Desk Stream):
- Week 4: Scheduling Systems
- Week 5: Insurance & Billing
- Week 6: Patient Interaction
- ... (continue through Week 12)

**Benefits of Labels:**
- ✅ Clear weekly progression
- ✅ Easy to move content between weeks
- ✅ Visual organization without affecting functionality
- ✅ Flexible - add/remove weeks easily

**Time:** 30 minutes

---

### PROGRAM OWNER: Add Content with Competency Mapping

**Goal:** Create learning activities linked to competencies

#### Competency Framework Structure

**Example: Allied Assist Competencies**
```
Core Competencies (All Streams):
├── Professional Ethics
├── Patient Communication
├── HIPAA Compliance
└── Medical Terminology

Front Desk Competencies:
├── Appointment Scheduling
├── Insurance Verification
├── Patient Check-in Procedures
└── Phone Etiquette

Doctor Assistance Competencies:
├── Vital Signs Measurement
├── Medical Equipment Handling
├── Examination Room Preparation
└── Clinical Documentation

Medical Records Competencies:
├── Electronic Health Records Management
├── Medical Coding Basics
├── Records Retention Policies
└── Data Privacy & Security
```

#### Steps

**Add Video (with Competency Link):**
1. In Section 1, below "Week 1" label, click "Add an activity or resource"
2. Select "File" or "URL" (for external videos)
3. Name: "Professional Ethics"
4. Upload video file or paste URL
5. Completion tracking: "Students must view this to complete it"
6. Click "Competencies" tab
7. Select competency: "Professional Ethics"
8. Save and return to course

**Add Quiz (with Competency Link):**
1. Below video, click "Add an activity or resource"
2. Select "Quiz"
3. Name: "Week 1 Assessment"
4. Add questions
5. Set passing grade: 80%
6. Completion tracking: "Students must receive a grade to complete this"
7. Link to competencies:
   - Click "Competencies" tab
   - Select: "Professional Ethics", "Patient Communication"
   - Save

**Add Assignment (with Competency Link):**
1. Click "Add an activity or resource"
2. Select "Assignment"
3. Name: "First Case Analysis"
4. Description: Instructions
5. Submission type: File upload
6. Due date: Set date
7. Grading: Use rubric (create rubric)
8. Link to competency
9. Save

**Repeat for all Common Foundation content**

**Time:** 2-4 hours depending on content volume

---

### PROGRAM OWNER: Add Stream Choice Activity

**Goal:** Let learners choose their specialization

**Steps:**
1. At end of Section 1 (Common Foundation), click "Add an activity or resource"
2. Select "Choice"
3. Enter:
   - Choice name: "Choose Your Specialization Domain"
   - Description: "Select the domain you want to specialize in. This decision is permanent."
4. Add options:
   - Option 1: "Domain A - Cataract Surgery"
   - Option 2: "Domain B - Retinal Surgery"
   - Option 3: "Domain C - Glaucoma Management"
5. Settings:
   - Allow choice to be updated: No (permanent decision)
   - Limit: No limit
   - Display mode: Display vertically
6. Completion tracking: "Students must make a choice to complete this"
7. Restrict access:
   - Activity completion: All activities in Section 1 must be completed
8. Save and return to course

**Time:** 10 minutes

---

### PROGRAM OWNER: Configure Conditional Access for Streams

**Goal:** Show only relevant stream to each learner

**Steps:**

**For Section 2 (Domain A):**
1. Click gear icon next to section name
2. Click "Edit section"
3. Expand "Restrict access"
4. Click "Add restriction"
5. Select "Activity completion"
6. Choose: "Choice - Choose Your Specialization Domain"
7. Condition: "must be marked complete"
8. Click "Add restriction" again
9. Select "Activity completion" again
10. Choose: Same choice activity
11. Condition: "Student must match the following"
12. Select: "Domain A - Cataract Surgery"
13. Eye icon: Click to "Hide entirely" if conditions not met
14. Save changes

**Repeat for Section 3 (Domain B) and Section 4 (Domain C)**

**Time:** 5 minutes per stream section

---

### PROGRAM OWNER: Set Completion Criteria

**Goal:** Define what "completing the program" means

**Steps:**
1. Navigate to program (course)
2. Click gear icon → More → Course completion
3. Condition: Activity completion
4. Check boxes for required activities:
   - All Common Foundation activities
   - All activities in at least one stream
5. Condition: Competency completion
6. Select required competencies
7. Set proficiency level: "Competent" or higher
8. Save changes

**Time:** 15 minutes

---

### PROGRAM OWNER: Create Badges

**Goal:** Visual recognition of achievement

**Steps:**

**1. Create Badge:**
1. Navigate to program (course)
2. Click gear icon → More → Badges → Add a new badge
3. Enter:
   - Name: "Ophthalmology Fellowship - Domain A Completion"
   - Description: "Awarded for completing Domain A specialization"
   - Upload badge image (PNG, 256x256px)
4. Click "Create badge"

**2. Set Criteria:**
1. Click "Criteria" tab
2. Add criteria type: "Course completion"
3. Select: This course
4. Add criteria type: "Activity completion"
5. Select: All Domain A activities
6. Add criteria type: "Competency"
7. Select: Domain A competencies
8. Save changes

**3. Enable Badge:**
1. Click "Enable access"
2. Confirm

**Badge is now automatic:** System awards when criteria met

**Manual Badge Option:**
1. Create badge without automatic criteria
2. Trainers manually award via "Award badge" button

**Time:** 20 minutes per badge

---

### TRAINER: Mark Attendance

**Goal:** Track learner participation

**Steps:**
1. Navigate to program (course)
2. Click on "Attendance" activity
3. Click "Take attendance" for today's session
4. For each learner:
   - Mark: Present (P), Absent (A), Late (L), Excused (E)
   - Add remarks if needed
5. Click "Save attendance"

**Time:** 2 minutes for 20 learners

---

### TRAINER: Review and Grade Submissions

**Goal:** Provide feedback on learner work

**Steps:**
1. Dashboard shows: "Review 5 Submissions" card
2. Click card to see pending assignments
3. Click on assignment
4. Click on learner name
5. View submission (download file if needed)
6. Provide feedback:
   - Add comments
   - Use rubric to grade
   - Record competency evidence if applicable
7. Enter grade
8. Click "Save changes"
9. Click "Next" to review next submission

**Time:** 5-10 minutes per submission

---

### TRAINER: Award Badges (Manual)

**Goal:** Recognize learner achievement

**Steps:**
1. Navigate to program (course)
2. Click gear icon → More → Badges → Manage badges
3. Click on badge name
4. Click "Award badge"
5. Search for learner
6. Click "Award badge"
7. Learner receives notification

**When to Award:**
- After learner completes all requirements
- After exceptional performance
- After milestone achievements

**Time:** 1 minute per badge

---

### TRAINER: Monitor Cohort Performance

**Goal:** Identify struggling learners early

**Steps:**
1. Dashboard shows: "My Cohorts" card
2. Click on cohort
3. View cohort summary:
   - Completion rate: 75% (target: >85%)
   - Average grade: 78% (target: >80%)
   - At-risk learners: 5 (>2 weeks behind)
4. Click "At-risk learners" to see list
5. For each at-risk learner:
   - Review progress
   - Identify bottlenecks
   - Send message offering support
   - Schedule 1-on-1 session if needed

**Weekly Review:**
- Check cohort performance vs. other cohorts
- If significantly lower, request support from Program Owner

**Time:** 30 minutes per week

---

### LEARNER: Choose Stream

**Goal:** Select specialization path

**Steps:**
1. Complete all Common Foundation activities
2. See "Choose Your Specialization Domain" activity
3. Read descriptions of each domain
4. Consider:
   - Career goals
   - Interests
   - Strengths
5. Make selection
6. Confirm choice (permanent)
7. Only chosen stream sections now visible

**Time:** 15 minutes (decision-making)

---

### LEARNER: Track Progress

**Goal:** Understand current position and next steps

**Steps:**
1. Dashboard shows: "My Learning Path" card
2. View visual progress tracker:
   - Common Foundation: 100% complete ✓
   - Domain A: 60% complete (in progress)
   - Competencies achieved: 15/25
3. See "Next Steps" section:
   - "Complete: Cataract Surgery Simulation"
   - "Due in 3 days: Case Analysis Assignment"
4. Click on activity to continue

**Time:** 2 minutes (daily check)

---

### LEARNER: Earn Badge

**Goal:** Receive recognition for achievement

**Steps:**
1. Complete all requirements for badge
2. System automatically awards badge (if automatic)
3. Receive notification: "You've earned a badge!"
4. View badge in profile
5. Share badge:
   - Download badge image
   - Share on LinkedIn
   - Add to resume/CV

**Time:** Automatic (no action needed)

---

## Summary

This document provides complete workflows for all user roles. Use it as a reference when:
- Training new users
- Troubleshooting issues
- Understanding dependencies
- Planning program launches
- Monitoring system health

For technical implementation details, see `docs/PRAGMATIC_IMPLEMENTATION_GUIDE.md`.


---

### TRAINER COACH: Setup (Optional - Week 6)

**Goal:** Enable trainer performance monitoring and coaching

**Prerequisites:**
- Base system implemented (Weeks 1-5)
- Identified who will coach trainers
- Trainer Coaches should also have Program Owner role (to create training materials)

#### Steps

**1. Create Trainer Coaches Cohort** (5 minutes)

1. Navigate to: Site administration → Users → Accounts → Cohorts
2. Click "Add new cohort"
3. Enter:
   - Name: "Trainer Coaches"
   - Cohort ID: "trainer-coaches"
   - Description: "Trainers who coach and monitor other trainers"
4. Click "Save changes"
5. Click "Assign" next to cohort name
6. Search for and add trainer coaches (e.g., Dr. Sarah, Dr. Ahmed)

**Time:** 5 minutes

---

**2. Verify Trainer Coaches Have Program Owner Role** (2 minutes per user)

Trainer Coaches need Program Owner role to create training materials:

1. Navigate to: Site administration → Users → Permissions → Assign system roles
2. Click "Program Owner"
3. Verify trainer coaches are in the list
4. If not, search and add them

**Time:** 2 minutes per user

---

**3. Update Dashboard Code** (Done by developer)

Developer updates `block_sceh_dashboard` to detect Trainer Coaches and show enhanced view.

**Time:** 2 hours (developer task)

---

### TRAINER COACH: Create Trainer Training Program

**Goal:** Provide professional development for trainers

**Steps:**

**1. Create Program Structure**

1. Navigate to: Site home → Add a new course
2. Enter:
   - Course full name: "Trainer Excellence Program"
   - Course short name: "TRAINER-EXCEL"
   - Course category: "Professional Development"
3. Course format: "Topics"
4. Number of sections: 4
5. Click "Save and display"

**2. Add Content**

**Section 1: Teaching Fundamentals**
- Video: "Effective Feedback Techniques" (10 min)
- Video: "Managing Difficult Conversations" (8 min)
- Document: "Teaching Best Practices Guide"
- Quiz: "Teaching Principles Assessment"

**Section 2: Platform Skills**
- Video: "Using the Trainer Dashboard" (5 min)
- Video: "Grading with Rubrics" (7 min)
- Assignment: "Practice Grading Exercise"

**Section 3: Cohort Management**
- Video: "Identifying At-Risk Learners" (6 min)
- Video: "Time Management for Trainers" (5 min)
- Case Study: "Handling Low Performance Cohort"

**Section 4: Continuous Improvement**
- Document: "Peer Observation Guidelines"
- Document: "Self-Reflection Template"
- Assignment: "Teaching Portfolio"

**3. Enroll All Trainers**

1. Click gear icon → Users → Enrollment methods
2. Add method: "Cohort sync"
3. Select cohort: "All Trainers" (create this cohort if needed)
4. Role: Student
5. Save

**4. Assign Trainer Coaches as Facilitators**

1. Click gear icon → Users → Enrolled users
2. Click "Enrol users"
3. Search for each Trainer Coach
4. Assign role: Teacher
5. Enrol

**Time:** 2-4 hours

---

### TRAINER COACH: Monitor Trainer Performance

**Goal:** Identify trainers needing support

**Steps:**

**1. View Trainer Performance Dashboard**

1. Log in
2. Dashboard shows additional section: "Trainer Performance"
3. View table:
   ```
   Trainer          | Cohorts | Completion | Avg Grade | Status
   Dr. Smith        | 3       | 87%        | 82%       | ✓ Good
   Dr. Jones        | 2       | 65%        | 74%       | ⚠ Alert
   Dr. Ahmed        | 4       | 91%        | 85%       | ✓ Good
   ```

**2. Identify Red Flags**

Look for:
- ⚠ Completion rate <70% (target: >85%)
- ⚠ Average grade <75% (target: >80%)
- ⚠ Status: Alert

**3. Drill Down on Struggling Trainer**

1. Click on trainer name (e.g., "Dr. Jones")
2. View detailed metrics:
   - Individual cohort performance
   - Pending submissions (how long waiting)
   - Quality of feedback given
   - Grading patterns
   - Attendance records

**4. Review Specific Cohort**

1. Click on cohort name
2. View (read-only):
   - Learner progress
   - Pending reviews (oldest: 15 days)
   - Recent feedback quality
   - Attendance patterns

**Time:** 30 minutes per week

---

### TRAINER COACH: Provide Coaching

**Goal:** Support struggling trainers

**Steps:**

**1. Contact Trainer**

1. From dashboard, click "Message" next to trainer name
2. Send message:
   ```
   Hi Dr. Jones,
   
   I noticed your cohort completion rate is lower than average.
   I'd like to schedule a coaching session to discuss how I can
   support you. Are you available this week?
   
   Best,
   Dr. Sarah
   ```

**2. Schedule Coaching Session**

Meet with trainer to discuss:
- What challenges are they facing?
- Are learners struggling with specific content?
- Is grading taking too long? Why?
- Do they need additional resources or support?

**3. Observe Teaching Session** (Optional)

1. Request permission to observe
2. Attend session (in-person or virtual)
3. Take notes using "Peer Observation Form"
4. Provide constructive feedback

**4. Pair with High Performer**

1. Identify high-performing trainer (e.g., Dr. Ahmed - 91% completion)
2. Arrange peer mentoring:
   - Dr. Jones observes Dr. Ahmed's session
   - Dr. Ahmed shares teaching strategies
   - Regular check-ins

**5. Follow Up**

1. Check trainer's performance in 2 weeks
2. Look for improvement
3. Continue support if needed
4. Celebrate improvements

**Time:** 1-2 hours per struggling trainer

---

### TRAINER COACH: Generate Reports

**Goal:** Track trainer effectiveness over time

**Steps:**

**1. Monthly Trainer Performance Report**

1. Navigate to: Site administration → Reports → Custom reports
2. Select: "Trainer Effectiveness Dashboard"
3. Set date range: Last 30 days
4. Generate report
5. Review trends:
   - Are trainers improving?
   - Are interventions working?
   - Are new trainers struggling?

**2. Share Best Practices**

1. Identify high-performing trainers
2. Document what they do well:
   - Fast grading turnaround
   - Detailed feedback
   - High engagement
3. Share in "Trainer Excellence Program"
4. Discuss in trainer workshops

**3. Quarterly Review**

1. Generate comprehensive report
2. Present to System Admin or leadership
3. Highlight:
   - Overall trainer performance trends
   - Successful coaching interventions
   - Areas needing additional support
   - Recommendations for trainer professional development

**Time:** 2 hours per month

---

### TRAINER COACH: What They Can See

**Aggregate Metrics (All Trainers):**
- ✅ Number of cohorts per trainer
- ✅ Completion rates per trainer
- ✅ Average grades per trainer
- ✅ Time to grade metrics
- ✅ At-risk learner counts
- ✅ Performance status (good/alert)

**Individual Trainer Drill-Down:**
- ✅ Each trainer's cohorts
- ✅ Learner progress in those cohorts (read-only)
- ✅ Pending submissions and delays
- ✅ Quality of feedback given (length, specificity)
- ✅ Grading patterns (too harsh? too lenient?)
- ✅ Attendance records

**Comparative Analysis:**
- ✅ How trainers compare to each other
- ✅ Program averages vs. individual trainers
- ✅ Trends over time

**What They CANNOT Do:**
- ❌ Grade submissions (that's the trainer's job)
- ❌ Modify grades
- ❌ Change course content
- ❌ Enroll/remove learners
- ❌ Assign trainers to cohorts (that's System Admin's job)

**They observe and coach, not deliver (unless also assigned as trainer).**

---

## Summary

This document provides complete workflows for all user roles including the optional Trainer Coach capability. Use it as a reference when:
- Training new users
- Troubleshooting issues
- Understanding dependencies
- Planning program launches
- Monitoring system health
- Implementing trainer coaching

For technical implementation details, see `docs/PRAGMATIC_IMPLEMENTATION_GUIDE.md`.


---

## Complete Example: Allied Assist Program Structure

This example shows how to structure a program with streams and weekly organization.

### Program Overview

**Program:** Allied Assist Program (12 weeks)  
**Target Audience:** Allied health professionals  
**Structure:** Common foundation (3 weeks) + Specialized streams (9 weeks)

---

### Section Structure

```
PROGRAM: Allied Assist Program (ONE Moodle Course)
│
├── Section 1: Common Foundation (Weeks 1-3)
│   │   All learners complete this section
│   │
│   ├── LABEL: "Week 1: Professional Foundations"
│   ├── Video: Professional Ethics → Competency: "Professional Ethics"
│   ├── Video: Patient Communication → Competency: "Patient Communication"
│   ├── Quiz: Week 1 Assessment
│   │
│   ├── LABEL: "Week 2: Compliance & Terminology"
│   ├── Video: HIPAA Compliance → Competency: "HIPAA Compliance"
│   ├── Video: Medical Terminology → Competency: "Medical Terminology"
│   ├── Quiz: Week 2 Assessment
│   │
│   └── LABEL: "Week 3: Integration & Choice"
│       ├── Assignment: Case Study (integrates all core competencies)
│       └── CHOICE: "Choose Your Specialization"
│           ├── Option 1: Front Desk Management
│           ├── Option 2: Doctor Assistance
│           └── Option 3: Medical Records
│
├── Section 2: Front Desk Management (Weeks 4-12)
│   │   Only visible if chose "Front Desk Management"
│   │
│   ├── LABEL: "Week 4: Scheduling Systems"
│   ├── Video: Appointment Scheduling → Competency: "Appointment Scheduling"
│   ├── Assignment: Scheduling Practice
│   │
│   ├── LABEL: "Week 5: Insurance & Billing"
│   ├── Video: Insurance Verification → Competency: "Insurance Verification"
│   ├── Assignment: Insurance Exercise
│   │
│   ├── LABEL: "Week 6: Patient Interaction"
│   ├── Video: Patient Check-in → Competency: "Patient Check-in Procedures"
│   ├── Assignment: Check-in Simulation
│   │
│   ├── LABEL: "Week 7: Communication Skills"
│   ├── Video: Phone Etiquette → Competency: "Phone Etiquette"
│   ├── Assignment: Phone Scenarios
│   │
│   ├── LABEL: "Week 8-11: Advanced Topics"
│   ├── [Additional content]
│   │
│   └── LABEL: "Week 12: Final Assessment"
│       └── Final Exam: Front Desk Competency Assessment
│
├── Section 3: Doctor Assistance (Weeks 4-12)
│   │   Only visible if chose "Doctor Assistance"
│   │
│   ├── LABEL: "Week 4: Clinical Basics"
│   ├── Video: Vital Signs → Competency: "Vital Signs Measurement"
│   ├── Assignment: Vitals Practice
│   │
│   ├── LABEL: "Week 5: Equipment & Safety"
│   ├── Video: Medical Equipment → Competency: "Medical Equipment Handling"
│   ├── Assignment: Equipment Handling
│   │
│   ├── LABEL: "Week 6: Examination Support"
│   ├── Video: Room Preparation → Competency: "Examination Room Preparation"
│   ├── Assignment: Room Prep Checklist
│   │
│   ├── LABEL: "Week 7: Documentation"
│   ├── Video: Clinical Documentation → Competency: "Clinical Documentation"
│   ├── Assignment: Documentation Practice
│   │
│   ├── LABEL: "Week 8-11: Advanced Clinical Skills"
│   ├── [Additional content]
│   │
│   └── LABEL: "Week 12: Final Assessment"
│       └── Final Exam: Clinical Assistance Competency Assessment
│
└── Section 4: Medical Records (Weeks 4-12)
        Only visible if chose "Medical Records"
    │
    ├── LABEL: "Week 4: EHR Fundamentals"
    ├── Video: Electronic Health Records → Competency: "EHR Management"
    ├── Assignment: EHR Navigation
    │
    ├── LABEL: "Week 5: Medical Coding"
    ├── Video: Coding Basics → Competency: "Medical Coding Basics"
    ├── Assignment: Coding Exercise
    │
    ├── LABEL: "Week 6: Compliance & Privacy"
    ├── Video: Records Retention → Competency: "Records Retention Policies"
    ├── Video: Data Security → Competency: "Data Privacy & Security"
    ├── Assignment: Privacy Scenarios
    │
    ├── LABEL: "Week 7-11: Advanced Records Management"
    ├── [Additional content]
    │
    └── LABEL: "Week 12: Final Assessment"
        └── Final Exam: Medical Records Competency Assessment
```

---

### Competency Achievement by Stream

**Learner A (Front Desk Management):**
```
Core Competencies (Weeks 1-3):
✓ Professional Ethics
✓ Patient Communication
✓ HIPAA Compliance
✓ Medical Terminology

Stream Competencies (Weeks 4-12):
✓ Appointment Scheduling
✓ Insurance Verification
✓ Patient Check-in Procedures
✓ Phone Etiquette

Total: 8 competencies
```

**Learner B (Doctor Assistance):**
```
Core Competencies (Weeks 1-3):
✓ Professional Ethics
✓ Patient Communication
✓ HIPAA Compliance
✓ Medical Terminology

Stream Competencies (Weeks 4-12):
✓ Vital Signs Measurement
✓ Medical Equipment Handling
✓ Examination Room Preparation
✓ Clinical Documentation

Total: 8 competencies (different from Learner A)
```

---

### Cohort Organization

**Multiple cohorts can run the same program:**

```
Program: Allied Assist Program

Cohort 1: "2024 Allied Assist - Trainer Dr. Smith"
├── 15 learners
├── 5 chose Front Desk Management
├── 6 chose Doctor Assistance
└── 4 chose Medical Records

Cohort 2: "2024 Allied Assist - Trainer Dr. Jones"
├── 12 learners
├── 4 chose Front Desk Management
├── 5 chose Doctor Assistance
└── 3 chose Medical Records

Cohort 3: "2025 Allied Assist - Trainer Dr. Smith"
├── 18 learners (next year's batch)
└── (Will choose streams in Week 3)
```

---

### Key Takeaways

**Programs vs Streams:**
- ONE program = ONE Moodle course
- Streams = Sections within the course
- Use streams when learners share common foundation

**Weekly Organization:**
- Use Labels to organize content by week
- Labels are flexible (easy to move content)
- Clear progression for learners

**Competency Mapping:**
- Core competencies in common foundation
- Stream-specific competencies in each stream
- Each activity links to relevant competencies

**Cohorts:**
- Multiple cohorts can use the same program
- Each cohort has a trainer
- Learners within a cohort can choose different streams

---

