# Pragmatic Implementation Guide: UX Simplification & RBAC

**Date:** 2026-02-13  
**Status:** Approved Approach  
**Duration:** 5 weeks  
**Principle:** Work with Moodle, not against it

---

## Executive Summary

This guide explains how to implement role-based access control and simplified UX by leveraging Moodle's existing features rather than building custom systems. This approach delivers 80% of the value in 20% of the time.

**Key Insight:** Moodle already has most of what we need. We just need to configure it correctly and hide the complexity.

---

## Understanding Moodle's Core Concepts

### Courses
Containers for learning content with:
- Sections (like chapters)
- Activities (videos, quizzes, assignments)
- Enrolled users
- Completion tracking

### Roles & Capabilities
What users can do:
- Each action requires a capability (e.g., `moodle/course:create`)
- Roles are collections of capabilities
- Roles can be assigned at different contexts (system, course, user)

### Cohorts
Groups of users for:
- Bulk enrollment
- Access control
- Reporting

### Competency Framework
Skills/knowledge hierarchy:
- Define competencies (e.g., "Cataract Surgery" → "Pre-op Assessment")
- Link to course activities
- Track user achievement
- Already implemented and in use in this project


---

## The Problem We're Solving

### Current State (Broken)
```
Manager role = System Admin + Program Owner (conflated!)
Teacher role = Curriculum Designer + Trainer (conflated!)
Student role = Learner (correct)
```

This violates the 3-layer responsibility model:
1. **Learning Design Authority** - Who designs curriculum
2. **Delivery & Enablement** - Who teaches
3. **Oversight & Insight** - Who manages the system

### Issues
- Program Owners need system admin access to create courses
- Trainers can modify curriculum (shouldn't)
- System Admins can design curriculum (shouldn't)
- Trainers see all courses, not just assigned cohorts
- No support for branching learning paths (streams)

---

## The Pragmatic Solution

### Core Strategy: Map PRD Concepts to Moodle Features

| PRD Concept | Moodle Feature | Implementation |
|-------------|----------------|----------------|
| **Program** | Course | Rename "Course" to "Program" in UI |
| **Stream** | Course Section + Conditional Access | Use visibility rules |
| **Learning Path** | Learning Plan Template | Already exists, use it |
| **Content Asset** | Activity + Backup/Restore | Reuse via backup |
| **Cohort** | Cohort | Already exists, use it |
| **Custom Roles** | Custom Roles + Capabilities | Define 3 new roles |

**Key Insight:** Stop trying to build "Programs" as separate entities. A Moodle Course IS a Program.


---

## Implementation: 5-Week Plan (+ Optional Week 6)

### Week 1: Role Separation

**Goal:** Implement 3-layer responsibility model with custom roles

#### Tasks

1. **Define Custom Roles**

Create 3 custom roles:

**sceh_system_admin** (Oversight & Insight)
```php
Capabilities:
✓ moodle/user:create
✓ moodle/user:update
✓ moodle/role:assign
✓ moodle/site:viewreports
✗ moodle/course:create
✗ moodle/competency:competencymanage
```

**sceh_program_owner** (Learning Design Authority)
```php
Capabilities:
✓ moodle/course:create
✓ moodle/course:update
✓ moodle/competency:competencymanage
✓ moodle/competency:templatemanage
✗ moodle/user:create
✗ moodle/site:config
```

**sceh_trainer** (Delivery & Enablement)
```php
Capabilities:
✓ moodle/course:view (assigned courses only)
✓ moodle/course:viewhiddenactivities
✓ moodle/grade:edit
✓ mod/attendance:takeattendances
✗ moodle/course:create
✗ moodle/course:update
✗ moodle/competency:competencymanage
```

2. **Update Dashboard Role Detection**

Edit `block_sceh_dashboard/block_sceh_dashboard.php`:

> Implementation note: the current dashboard code in this repository still uses core capability checks (`moodle/site:config`, `moodle/course:update`). The snippet below is target state and depends on Week 1 capability additions in `local_sceh_rules/db/access.php`.

```php
// OLD (broken)
$is_admin = has_capability('moodle/site:config', $context);
$is_teacher = has_capability('moodle/course:update', $context);
$is_student = !$is_admin && !$is_teacher;

// NEW (correct, aligned to local_sceh_rules component)
$is_system_admin = has_capability('local/sceh_rules:systemadmin', $context);
$is_program_owner = has_capability('local/sceh_rules:programowner', $context);
$is_trainer = has_capability('local/sceh_rules:trainer', $context);
$is_learner = !$is_system_admin && !$is_program_owner && !$is_trainer;
```

3. **Create Role-Specific Dashboard Views**

```php
if ($is_system_admin) {
    // Show: User Management, System Reports, Role Assignments
    echo $this->render_system_admin_dashboard();
    
} elseif ($is_program_owner) {
    // Show: My Programs, Create New Program, Competency Framework
    echo $this->render_program_owner_dashboard();
    
} elseif ($is_trainer) {
    // Show: My Cohorts, Pending Reviews, Session Schedule
    echo $this->render_trainer_dashboard();
    
} else {
    // Show: My Learning Path, Next Steps, Progress
    echo $this->render_learner_dashboard();
}
```

#### Deliverables
- 3 custom roles defined in Moodle
- Dashboard shows different content per role
- System Admin cannot create courses
- Program Owner cannot manage users
- Trainer cannot modify competencies

#### Testing
- Assign test users to each role
- Verify capability restrictions work
- Verify dashboard shows correct content

---

### Week 1.5: Category-Based Program Ownership (Critical)

**Goal:** Enable Program Owners to create programs autonomously while maintaining separation

**Why This Matters:** Without this, System Admin becomes a bottleneck for every program creation.

#### Concept: Category-Based Permissions

```
System Admin creates categories:
├── Allied Health Programs (Dr Parul manages)
├── Surgical Fellowships (Dr Sima Das manages)
└── Optometry Programs (Dr Imtiyaz manages)

Program Owners can:
✓ Create programs in their category
✓ See only programs in their category
✓ Edit only programs in their category
✗ Cannot see other categories
```

#### Tasks

**1. System Admin: Create Course Categories** (10 minutes)

1. Navigate to: Site administration → Courses → Manage courses and categories
2. Click "Create new category"
3. Create categories based on your organizational structure:
   - Name: "Allied Health Programs"
   - Name: "Surgical Fellowships"
   - Name: "Optometry Programs"
   - Name: "Continuing Education"
4. Save each category

**2. System Admin: Assign Program Owners to Categories** (5 minutes per owner)

For each category:
1. Navigate to: Site administration → Courses → Manage courses and categories
2. Click on category name (e.g., "Allied Health Programs")
3. Click "Assign roles"
4. Select "Program Owner" (sceh_program_owner)
5. Search for user (e.g., Dr Parul)
6. Click "Add"

Repeat for each Program Owner and their category.

**3. Update Program Owner Dashboard to Show Categories**

Edit `block_sceh_dashboard/block_sceh_dashboard.php`:

```php
private function render_program_owner_dashboard() {
    global $USER;
    
    $html = '';
    
    // Get categories where user has Program Owner role
    $my_categories = $this->get_user_categories($USER->id);
    
    if (empty($my_categories)) {
        $html .= html_writer::tag('p', 'No program categories assigned yet. Contact System Admin.');
        return $html;
    }
    
    // Show programs grouped by category
    foreach ($my_categories as $category) {
        $html .= html_writer::tag('h3', $category->name);
        
        // Get programs in this category
        $programs = $this->get_category_programs($category->id);
        
        if (empty($programs)) {
            $html .= html_writer::tag('p', 'No programs in this category yet.');
        } else {
            foreach ($programs as $program) {
                $html .= $this->render_program_card($program);
            }
        }
        
        // Add "Create Program" button for this category
        $html .= html_writer::link(
            new moodle_url('/course/edit.php', ['category' => $category->id]),
            'Create New Program in ' . $category->name,
            ['class' => 'btn btn-primary']
        );
    }
    
    return $html;
}

private function get_user_categories($userid) {
    global $DB;
    
    // Get categories where user has Program Owner role
    $sql = "SELECT DISTINCT cc.*
            FROM {course_categories} cc
            JOIN {context} ctx ON ctx.instanceid = cc.id AND ctx.contextlevel = 40
            JOIN {role_assignments} ra ON ra.contextid = ctx.id
            JOIN {role} r ON r.id = ra.roleid
            WHERE ra.userid = :userid
            AND r.shortname = 'sceh_program_owner'
            ORDER BY cc.name";
    
    return $DB->get_records_sql($sql, ['userid' => $userid]);
}

private function get_category_programs($categoryid) {
    global $DB;
    
    // Get courses in this category
    return $DB->get_records('course', ['category' => $categoryid], 'fullname');
}
```

#### Example Structure

```
Root
│
├── Allied Health Programs (Dr Parul)
│   ├── Allied Assist Program
│   ├── Medical Assistant Program
│   └── Phlebotomy Program
│
├── Surgical Fellowships (Dr Sima Das)
│   ├── Cataract Fellowship
│   ├── Glaucoma Fellowship
│   └── Retinal Fellowship
│
└── Optometry Programs (Dr Imtiyaz)
    ├── Pediatric Optometry
    ├── Geriatric Optometry
    └── Sports Vision
```

#### Deliverables
- Course categories created
- Program Owners assigned to categories
- Dashboard shows only assigned categories
- Program Owners can create programs in their category
- Program Owners cannot see other categories

#### Testing
- Log in as Dr Parul (Allied Health)
- Verify she sees only Allied Health programs
- Create test program in her category
- Log in as Dr Sima Das (Surgical Fellowships)
- Verify they do NOT see Dr Parul's programs
- Verify he can create programs in his category

#### Benefits
- ✅ Program Owners are autonomous (no bottleneck)
- ✅ Clear separation between program areas
- ✅ Scalable as organization grows
- ✅ System Admin only manages categories (not every program)


---

### Week 2: Trainer Cohort Filtering

**Goal:** Trainers see only assigned cohorts, not all courses

#### Tasks

1. **Add Custom Capability**

Define in `local_sceh_rules/db/access.php`:

```php
$capabilities = [
    'local/sceh_rules:viewassignedcohortsonly' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW
        ]
    ]
];
```

Then assign this capability to your custom Trainer role (`sceh_trainer`) from the role permissions UI.

Also define the role-detection capabilities used in Week 1 dashboard logic:

```php
'local/sceh_rules:systemadmin' => [...],
'local/sceh_rules:programowner' => [...],
'local/sceh_rules:trainer' => [...],
```

2. **Create Cohort Filter Helper**

Create `local_sceh_rules/classes/helper/cohort_filter.php`:

```php
class cohort_filter {
    
    public static function get_trainer_courses($userid) {
        global $DB;
        
        // Get cohorts where user is assigned as trainer
        $cohorts = self::get_trainer_cohorts($userid);
        
        // Get courses enrolled by these cohorts
        $courses = [];
        foreach ($cohorts as $cohort) {
            $cohort_courses = self::get_cohort_courses($cohort->id);
            $courses = array_merge($courses, $cohort_courses);
        }
        
        return array_unique($courses, SORT_REGULAR);
    }
    
    private static function get_trainer_cohorts($userid) {
        global $DB;
        
        // Get cohorts where user has trainer role
        $sql = "SELECT DISTINCT c.*
                FROM {cohort} c
                JOIN {cohort_members} cm ON cm.cohortid = c.id
                WHERE cm.userid = :userid";
        
        return $DB->get_records_sql($sql, ['userid' => $userid]);
    }
    
    private static function get_cohort_courses($cohortid) {
        global $DB;
        
        // Get courses where cohort is enrolled
        $sql = "SELECT DISTINCT c.*
                FROM {course} c
                JOIN {enrol} e ON e.courseid = c.id
                JOIN {cohort} co ON co.id = e.customint1
                WHERE e.enrol = 'cohort'
                AND co.id = :cohortid";
        
        return $DB->get_records_sql($sql, ['cohortid' => $cohortid]);
    }
}
```

3. **Update Trainer Dashboard**

Edit `block_sceh_dashboard/block_sceh_dashboard.php`:

```php
private function render_trainer_dashboard() {
    global $USER;
    
    $html = '';
    
    // Get only assigned cohorts
    $courses = \local_sceh_rules\helper\cohort_filter::get_trainer_courses($USER->id);
    
    $html .= html_writer::tag('h3', 'My Cohorts');
    
    foreach ($courses as $course) {
        $html .= $this->render_cohort_card($course);
    }
    
    return $html;
}

private function render_cohort_card($course) {
    // Show: Course name, enrolled count, pending reviews
    $enrolled = count_enrolled_users(context_course::instance($course->id));
    $pending = $this->get_pending_reviews($course->id);
    
    return html_writer::div(
        html_writer::tag('h4', $course->fullname) .
        html_writer::tag('p', "{$enrolled} learners") .
        html_writer::tag('p', "{$pending} pending reviews"),
        'cohort-card'
    );
}
```

#### Deliverables
- Trainers see only courses where they're assigned via cohort
- Dashboard shows "My Cohorts" instead of "All Courses"
- Cohort cards show enrolled count and pending reviews

#### Testing
- Create 2 cohorts with different trainers
- Enroll cohorts in different courses
- Verify each trainer sees only their cohorts


---

### Weeks 3-4: Stream Support via Sections

**Goal:** Support branching learning paths (Common Foundation → Domain A or Domain B)

#### Concept: Programs, Streams, and Weekly Structure

**Understanding the Hierarchy:**

```
PROGRAM = Moodle Course = Complete learning journey
├── STREAM = Section = Specialization path
    └── WEEKS = Labels within section = Time-based organization
```

**Example: Allied Assist Program**

```
PROGRAM: Allied Assist Program (ONE Moodle Course)
│
├── Section 1: Common Foundation (Weeks 1-3)
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
├── Section 2: STREAM - Front Desk Management (Weeks 4-12)
│   │   (Only visible if chose "Front Desk Management")
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
│   ├── LABEL: "Week 7-11: [Additional Content]"
│   │
│   └── LABEL: "Week 12: Final Assessment"
│       └── Final Exam: Front Desk Competency Assessment
│
├── Section 3: STREAM - Doctor Assistance (Weeks 4-12)
│   │   (Only visible if chose "Doctor Assistance")
│   │
│   ├── LABEL: "Week 4: Clinical Basics"
│   ├── Video: Vital Signs → Competency: "Vital Signs Measurement"
│   ├── Assignment: Vitals Practice
│   │
│   ├── LABEL: "Week 5: Equipment & Safety"
│   ├── Video: Medical Equipment → Competency: "Medical Equipment Handling"
│   ├── Assignment: Equipment Handling
│   │
│   └── LABEL: "Week 6-12: [Additional Content]"
│
└── Section 4: STREAM - Medical Records (Weeks 4-12)
        (Only visible if chose "Medical Records")
    │
    ├── LABEL: "Week 4: EHR Fundamentals"
    ├── Video: Electronic Health Records → Competency: "EHR Management"
    ├── Assignment: EHR Navigation
    │
    └── LABEL: "Week 5-12: [Additional Content]"
```

**Key Benefits of This Structure:**

✅ **Flexible:** Move content between weeks without affecting other sections
✅ **Clear:** Learners see weekly progression
✅ **Stream-Specific Competencies:** Each stream has its own competency map
✅ **Scalable:** Easy to add/remove weeks or streams

---

#### When to Use Streams vs Separate Programs

**Use STREAMS (Sections in ONE Course) When:**
- ✅ Learners start with common foundation
- ✅ Specializations are related (e.g., all allied health roles)
- ✅ Learners choose ONE path
- ✅ Shared competencies across streams

**Examples:** Allied Assist Program, Optometry Certification

**Use SEPARATE PROGRAMS (Different Courses) When:**
- ✅ Completely different audiences
- ✅ No common foundation
- ✅ Learners might take multiple programs
- ✅ Different competency frameworks

**Examples:** Cataract Surgery Fellowship vs. Glaucoma Surgery Fellowship

---

#### Tasks

1. **Add Stream Choice Activity**

At the end of Section 1 (Common Foundation):
- Add a "Choice" activity: "Which domain do you want to specialize in?"
- Options: Domain A, Domain B, Domain C, etc.
- Required: Yes
- Completion: Student must make a choice

2. **Add Weekly Labels to Organize Content**

Within each section:
- Add "Label" activity: "Week 1: [Topic Name]"
- Add content activities below the label
- Add another "Label" activity: "Week 2: [Topic Name]"
- Continue for all weeks

**How to Add Labels:**
1. Turn editing on
2. Click "Add an activity or resource"
3. Select "Label"
4. Enter text: "Week 4: Scheduling Systems"
5. Format as heading (H3 or H4)
6. Save

3. **Link Activities to Competencies**

For each activity:
1. Edit activity
2. Click "Competencies" tab
3. Select relevant competency
4. Save

**Example:**
- Video: "Appointment Scheduling" → Competency: "Appointment Scheduling"
- Assignment: "Scheduling Practice" → Competency: "Appointment Scheduling"

4. **Configure Conditional Access for Streams**

For Section 2 (Domain A):
- Restriction 1: Activity completion → Section 1 → All activities completed
- Restriction 2: Activity completion → Choice activity → "Domain A" selected
- Display: Hide entirely if conditions not met

For Section 3 (Domain B):
- Restriction 1: Activity completion → Section 1 → All activities completed
- Restriction 2: Activity completion → Choice activity → "Domain B" selected
- Display: Hide entirely if conditions not met

5. **Optional: Add Date Restrictions for Pacing**

For each week's content:
1. Edit activity
2. Restrict access → Add restriction → Date
3. Set "Available from" date (e.g., Week 4 start date)
4. Optional: Require previous week's completion

6. **Add Stream Indicator to Learner Dashboard**

Edit `block_sceh_dashboard/block_sceh_dashboard.php`:

```php
private function render_learner_dashboard() {
    global $USER;
    
    $html = '';
    
    // Get enrolled courses
    $courses = enrol_get_users_courses($USER->id);
    
    foreach ($courses as $course) {
        // Get chosen stream
        $stream = $this->get_user_stream($course->id, $USER->id);
        
        if ($stream) {
            $html .= html_writer::tag('p', 
                "Your stream: " . html_writer::tag('strong', $stream),
                ['class' => 'stream-indicator']
            );
        }
        
        $html .= $this->render_learning_path($course, $stream);
    }
    
    return $html;
}

private function get_user_stream($courseid, $userid) {
    global $DB;
    
    // Find the stream choice activity
    $choice = $DB->get_record_sql(
        "SELECT c.* FROM {choice} c
         JOIN {course_modules} cm ON cm.instance = c.id
         WHERE cm.course = :courseid
         AND c.name LIKE '%specialization%'",
        ['courseid' => $courseid]
    );
    
    if (!$choice) {
        return null;
    }
    
    // Get user's choice
    $answer = $DB->get_record('choice_answers', [
        'choiceid' => $choice->id,
        'userid' => $userid
    ]);
    
    if (!$answer) {
        return null;
    }
    
    // Get option text
    $option = $DB->get_record('choice_options', [
        'id' => $answer->optionid
    ]);
    
    return $option ? $option->text : null;
}
```

7. **Add Stream Filter to Progress View**

```php
private function render_learning_path($course, $stream) {
    $html = '';
    
    // Get course sections
    $sections = get_fast_modinfo($course)->get_section_info_all();
    
    foreach ($sections as $section) {
        // Skip if section is for different stream
        if ($stream && !$this->section_matches_stream($section, $stream)) {
            continue;
        }
        
        $html .= $this->render_section_progress($section);
    }
    
    return $html;
}

private function section_matches_stream($section, $stream) {
    // Common Foundation is always shown
    if (stripos($section->name, 'common') !== false) {
        return true;
    }
    
    // Check if section name matches stream
    return stripos($section->name, $stream) !== false;
}
```

#### Deliverables
- Learners complete Common Foundation
- Learners choose a stream via Choice activity
- Only chosen stream sections are visible
- Dashboard shows "Your stream: Domain A"
- Progress view filters to show only relevant sections

#### Testing
- Enroll test learner
- Complete Common Foundation
- Choose Domain A
- Verify only Domain A sections visible
- Verify Domain B sections hidden


---

### Week 5: Dashboard Polish

**Goal:** Hide Moodle complexity, improve terminology, add proactive alerts, mobile-responsive

#### Tasks

1. **Hide Standard Moodle Blocks**

Edit theme or use block visibility settings:
- Hide "Navigation" block for non-admins
- Hide "Administration" block for non-admins
- Hide "Latest announcements" (use custom dashboard cards instead)
- Keep only: Custom dashboard block, Calendar, Upcoming events

2. **Add Attendance Alerts for Trainers**

Create proactive attendance monitoring card:

```php
// In block_sceh_dashboard/block_sceh_dashboard.php

private function render_trainer_dashboard() {
    $html = '';
    
    // Existing cards
    $html .= $this->render_my_cohorts();
    $html .= $this->render_pending_reviews();
    $html .= $this->render_todays_sessions();
    
    // NEW: Attendance alerts
    $html .= $this->render_attendance_alerts();
    
    return $html;
}

private function render_attendance_alerts() {
    global $USER, $DB;
    
    // Get trainer's courses
    $courses = \local_sceh_rules\helper\cohort_filter::get_trainer_courses($USER->id);
    
    $at_risk_learners = [];
    $threshold = 75; // Configurable: learners below 75% attendance
    
    foreach ($courses as $course) {
        // Get enrolled learners
        $context = \context_course::instance($course->id);
        $learners = get_enrolled_users($context, 'mod/attendance:canttakeattendances', 0, 'u.id, u.firstname, u.lastname');
        
        // Use existing attendance rule to calculate percentage
        $attendance_rule = new \local_sceh_rules\rules\attendance_rule();
        
        foreach ($learners as $learner) {
            // Reuse existing method from attendance_rule.php
            $attendance = $this->get_user_attendance_percentage_for_dashboard($learner->id, $course->id, $attendance_rule);
            
            if ($attendance < $threshold) {
                $at_risk_learners[] = [
                    'name' => fullname($learner),
                    'course' => $course->fullname,
                    'attendance' => round($attendance, 1),
                    'userid' => $learner->id,
                    'courseid' => $course->id
                ];
            }
        }
    }
    
    if (empty($at_risk_learners)) {
        return ''; // No alerts, don't show card
    }
    
    // Render alert card
    $html = html_writer::start_div('action-card alert-card');
    $html .= html_writer::tag('i', '', ['class' => 'icon-alert']);
    $html .= html_writer::tag('h4', 'Attendance Alerts');
    $html .= html_writer::tag('p', count($at_risk_learners) . ' learners below ' . $threshold . '% attendance');
    
    // List at-risk learners (top 5)
    $html .= html_writer::start_tag('ul', ['class' => 'at-risk-list']);
    foreach (array_slice($at_risk_learners, 0, 5) as $learner) {
        $html .= html_writer::start_tag('li');
        $html .= html_writer::tag('strong', $learner['name']);
        $html .= ' - ' . $learner['attendance'] . '% in ' . $learner['course'];
        $html .= html_writer::end_tag('li');
    }
    $html .= html_writer::end_tag('ul');
    
    if (count($at_risk_learners) > 5) {
        $html .= html_writer::tag('p', '+ ' . (count($at_risk_learners) - 5) . ' more', ['class' => 'more-count']);
    }
    
    // Link to full attendance report
    $url = new \moodle_url('/mod/attendance/index.php');
    $html .= html_writer::link($url, 'View All →', ['class' => 'btn btn-primary']);
    
    $html .= html_writer::end_div();
    
    return $html;
}

private function get_user_attendance_percentage_for_dashboard($userid, $courseid, $attendance_rule) {
    global $DB;
    
    // Use reflection to call protected method from attendance_rule
    // Or make the method public in attendance_rule.php
    $reflection = new \ReflectionClass($attendance_rule);
    $method = $reflection->getMethod('get_user_attendance_percentage');
    $method->setAccessible(true);
    
    return $method->invoke($attendance_rule, $userid, $courseid);
}
```

**Alternative: Make method public in attendance_rule.php**

Edit `local_sceh_rules/classes/rules/attendance_rule.php`:

```php
// Change from:
protected function get_user_attendance_percentage($userid, $courseid)

// To:
public function get_user_attendance_percentage($userid, $courseid)
```

This allows dashboard to call it directly without reflection.

**Optional: Add caching for performance**

```php
private function get_cached_attendance_alerts() {
    global $USER;
    
    $cache = \cache::make('block_sceh_dashboard', 'attendance_alerts');
    $cachekey = 'trainer_' . $USER->id;
    
    $alerts = $cache->get($cachekey);
    
    if ($alerts === false) {
        // Cache miss, calculate
        $alerts = $this->calculate_attendance_alerts();
        
        // Cache for 1 hour (3600 seconds)
        $cache->set($cachekey, $alerts);
    }
    
    return $alerts;
}
```

**Why this matters:**
- Trainers can proactively identify struggling learners
- Prevents waiting for competency blocks (reactive)
- Uses existing attendance infrastructure
- Low effort, high value

**Time:** 4 hours

3. **Update Terminology**

Create language customization file `local_sceh_rules/lang/en/local_sceh_rules.php`:

```php
$string['pluginname'] = 'SCEH Rules';

// Terminology changes
$string['course'] = 'Program';
$string['courses'] = 'Programs';
$string['mycourses'] = 'My Programs';
$string['coursecreate'] = 'Create New Program';
$string['assignment'] = 'Assessment';
$string['grade'] = 'Feedback';
$string['teacher'] = 'Trainer';
$string['learningplan'] = 'Learning Path';
```

Override core strings in `local_sceh_rules/lang/en/moodle.php`:

```php
$string['course'] = 'Program';
$string['courses'] = 'Programs';
$string['mycourses'] = 'My Programs';
```

**Time:** 4 hours

4. **Add Task-Oriented Cards**

Update dashboard to show actionable items:

```php
private function render_trainer_dashboard() {
    $html = '';
    
    // Pending reviews card
    $pending = $this->get_pending_reviews();
    if ($pending > 0) {
        $html .= $this->render_action_card(
            'Review Submissions',
            "{$pending} submissions waiting for feedback",
            '/local/sceh_rules/pending_reviews.php',
            'review'
        );
    }
    
    // Today's sessions card
    $sessions = $this->get_todays_sessions();
    if (!empty($sessions)) {
        $html .= $this->render_action_card(
            'Today\'s Sessions',
            count($sessions) . ' sessions scheduled',
            '/mod/attendance/view.php',
            'calendar'
        );
    }
    
    // My cohorts card
    $html .= $this->render_action_card(
        'My Cohorts',
        'View all assigned cohorts',
        '/local/sceh_rules/my_cohorts.php',
        'group'
    );
    
    return $html;
}

private function render_action_card($title, $description, $url, $icon) {
    return html_writer::div(
        html_writer::tag('i', '', ['class' => "icon-{$icon}"]) .
        html_writer::tag('h4', $title) .
        html_writer::tag('p', $description) .
        html_writer::link($url, 'Go →', ['class' => 'btn btn-primary']),
        'action-card'
    );
}
```

**Time:** 6 hours

5. **Mobile-Responsive CSS**

Add to `block_sceh_dashboard/styles.css`:

```css
.action-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.action-card h4 {
    margin: 8px 0;
    font-size: 18px;
}

.action-card p {
    color: #666;
    margin: 8px 0;
}

.action-card .btn {
    margin-top: 12px;
    display: inline-block;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .action-card {
        padding: 16px;
    }
    
    .action-card h4 {
        font-size: 16px;
    }
    
    .action-card .btn {
        display: block;
        width: 100%;
        text-align: center;
    }
}

/* Touch-friendly buttons */
@media (hover: none) {
    .action-card .btn {
        min-height: 44px;
        line-height: 44px;
    }
}
```

**Time:** 4 hours

6. **Add Breadcrumb Context**

```php
private function set_page_context($role) {
    global $PAGE;
    
    $PAGE->set_context(context_system::instance());
    
    switch ($role) {
        case 'system_admin':
            $PAGE->set_title('System Administration');
            $PAGE->set_heading('System Administration');
            break;
        case 'program_owner':
            $PAGE->set_title('Program Management');
            $PAGE->set_heading('Program Management');
            break;
        case 'trainer':
            $PAGE->set_title('My Cohorts');
            $PAGE->set_heading('My Cohorts');
            break;
        default:
            $PAGE->set_title('My Learning');
            $PAGE->set_heading('My Learning');
    }
}
```

#### Deliverables
- Standard Moodle blocks hidden for non-admins
- Attendance alerts card shows at-risk learners proactively
- Terminology changed (Course → Program, etc.)
- Task-oriented action cards
- Mobile-responsive layout
- Touch-friendly buttons (44px min height)
- Clear page context and breadcrumbs

#### Testing
- Test on desktop, tablet, mobile
- Verify attendance alerts show correct data
- Test with learners at various attendance levels (100%, 80%, 60%)
- Verify terminology changes throughout
- Verify action cards are clickable
- Verify touch targets are adequate (44px)
- Test with screen reader for accessibility

**Time:** 2.5 days (20 hours total)

---

### Week 6 (Optional): Trainer Coach Capability

**Goal:** Add trainer performance monitoring and coaching capability

**Note:** This is optional. Start with 3 roles (Weeks 1-5), then add this if needed.

#### Approach: Enhanced Trainer Role (No New Role)

Instead of creating a 4th role, we enhance the existing Trainer role:
- Regular trainers see only their cohorts
- Trainer Coaches see all trainers' performance + their own cohorts
- Same role, different view based on cohort membership

#### Tasks

1. **Create Trainer Coaches Cohort** (5 minutes)

```
Navigate to: Site administration → Users → Cohorts
Click "Add new cohort"
- Name: "Trainer Coaches"
- ID: "trainer-coaches"
- Description: "Trainers who coach other trainers"
Add members: Dr Parul, Dr Sima Das (whoever coaches trainers)
```

2. **Update Dashboard to Detect Trainer Coaches** (2 hours)

Edit `block_sceh_dashboard/block_sceh_dashboard.php`:

```php
private function render_trainer_dashboard() {
    $html = '';
    
    // Regular trainer view (everyone sees this)
    $html .= $this->render_my_cohorts();
    $html .= $this->render_pending_reviews();
    $html .= $this->render_todays_sessions();
    
    // Additional view for Trainer Coaches
    if ($this->is_trainer_coach()) {
        $html .= html_writer::tag('h3', 'Trainer Performance');
        $html .= $this->render_all_trainers_overview();
        $html .= $this->render_performance_alerts();
        $html .= $this->render_coaching_queue();
    }
    
    return $html;
}

private function is_trainer_coach() {
    global $USER, $DB;
    
    // Check if user is in "Trainer Coaches" cohort
    $coach_cohort = $DB->get_record('cohort', ['idnumber' => 'trainer-coaches']);
    if (!$coach_cohort) {
        return false;
    }
    
    return $DB->record_exists('cohort_members', [
        'cohortid' => $coach_cohort->id,
        'userid' => $USER->id
    ]);
}
```

3. **Create Trainer Performance Views** (1 day)

```php
private function render_all_trainers_overview() {
    global $DB;
    
    $html = html_writer::start_tag('table', ['class' => 'trainer-performance-table']);
    $html .= html_writer::start_tag('thead');
    $html .= html_writer::start_tag('tr');
    $html .= html_writer::tag('th', 'Trainer');
    $html .= html_writer::tag('th', 'Cohorts');
    $html .= html_writer::tag('th', 'Completion Rate');
    $html .= html_writer::tag('th', 'Avg Grade');
    $html .= html_writer::tag('th', 'Status');
    $html .= html_writer::end_tag('tr');
    $html .= html_writer::end_tag('thead');
    
    $html .= html_writer::start_tag('tbody');
    
    // Get all trainers
    $trainers = $this->get_all_trainers();
    
    foreach ($trainers as $trainer) {
        $metrics = $this->get_trainer_metrics($trainer->id);
        
        $status = $this->get_performance_status($metrics);
        $status_icon = $status == 'good' ? '✓' : '⚠';
        
        $html .= html_writer::start_tag('tr');
        $html .= html_writer::tag('td', fullname($trainer));
        $html .= html_writer::tag('td', $metrics->cohort_count);
        $html .= html_writer::tag('td', round($metrics->completion_rate) . '%');
        $html .= html_writer::tag('td', round($metrics->avg_grade) . '%');
        $html .= html_writer::tag('td', $status_icon . ' ' . ucfirst($status));
        $html .= html_writer::end_tag('tr');
    }
    
    $html .= html_writer::end_tag('tbody');
    $html .= html_writer::end_tag('table');
    
    return $html;
}

private function get_trainer_metrics($trainerid) {
    global $DB;
    
    // Get trainer's cohorts
    $sql = "SELECT COUNT(DISTINCT co.id) as cohort_count
            FROM {cohort} co
            JOIN {cohort_members} cm ON cm.cohortid = co.id
            WHERE cm.userid = :trainerid";
    $cohort_count = $DB->get_field_sql($sql, ['trainerid' => $trainerid]);
    
    // Get completion rate across all trainer's cohorts
    $sql = "SELECT AVG(completion_rate) as avg_completion
            FROM (
                SELECT COUNT(DISTINCT cc.userid) * 100.0 / NULLIF(COUNT(DISTINCT ue.userid), 0) as completion_rate
                FROM {cohort} co
                JOIN {cohort_members} cm ON cm.cohortid = co.id
                JOIN {enrol} e ON e.customint1 = co.id AND e.enrol = 'cohort'
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                LEFT JOIN {course_completions} cc ON cc.userid = ue.userid AND cc.course = e.courseid
                WHERE cm.userid = :trainerid
                GROUP BY co.id
            ) subquery";
    $completion_rate = $DB->get_field_sql($sql, ['trainerid' => $trainerid]) ?: 0;
    
    // Get average grade across all trainer's cohorts
    $sql = "SELECT AVG(gg.finalgrade) as avg_grade
            FROM {cohort} co
            JOIN {cohort_members} cm ON cm.cohortid = co.id
            JOIN {enrol} e ON e.customint1 = co.id AND e.enrol = 'cohort'
            JOIN {user_enrolments} ue ON ue.enrolid = e.id
            JOIN {grade_grades} gg ON gg.userid = ue.userid
            WHERE cm.userid = :trainerid";
    $avg_grade = $DB->get_field_sql($sql, ['trainerid' => $trainerid]) ?: 0;
    
    return (object)[
        'cohort_count' => $cohort_count,
        'completion_rate' => $completion_rate,
        'avg_grade' => $avg_grade
    ];
}

private function get_performance_status($metrics) {
    // Red flags: completion <70%, grade <75%
    if ($metrics->completion_rate < 70 || $metrics->avg_grade < 75) {
        return 'alert';
    }
    return 'good';
}
```

4. **Create Trainer Training Program** (2-4 hours)

Trainer Coaches (who should also have Program Owner role) create:

```
Program: "Trainer Excellence Program"
├── Section 1: Teaching Fundamentals
│   ├── Video: Effective Feedback Techniques
│   ├── Video: Managing Difficult Conversations
│   └── Quiz: Teaching Principles
│
├── Section 2: Platform Skills
│   ├── Video: Using the Dashboard
│   ├── Video: Grading with Rubrics
│   └── Assignment: Practice Grading
│
├── Section 3: Cohort Management
│   ├── Video: Identifying At-Risk Learners
│   └── Case Study: Handling Low Performance
│
└── Section 4: Continuous Improvement
    ├── Peer Observation Guidelines
    └── Teaching Portfolio Assignment
```

Enroll all trainers as learners in this program.

#### Deliverables
- "Trainer Coaches" cohort created
- Dashboard shows trainer performance for coaches
- Trainer performance metrics visible (completion, grades, status)
- Trainer Training Program created
- All trainers enrolled in training program

#### Testing
- Assign test user to Trainer Coaches cohort
- Verify they see trainer performance section
- Verify regular trainers don't see this section
- Verify metrics are accurate

#### What Trainer Coaches Can See

**Aggregate Metrics:**
- All trainers' completion rates
- All trainers' average grades
- Number of cohorts per trainer
- Performance status (good/alert)

**Drill-Down (Click on Trainer):**
- Individual trainer's cohorts
- Learner progress in those cohorts (read-only)
- Pending submissions and grading delays
- Quality of feedback given
- Attendance patterns

**Comparative Analysis:**
- How trainers compare to each other
- Program averages vs. individual trainers
- Red flags (underperforming trainers)

**What They CANNOT Do:**
- Grade submissions (that's the trainer's job)
- Modify grades
- Change course content
- Enroll/remove learners

**They observe and coach, not deliver.**

---

## Benefits of This Approach

### 1. Fast Delivery
- 5 weeks vs. 24 weeks (6 months)
- Delivers core functionality quickly
- Users can provide feedback early

### 2. Low Risk
- Uses Moodle's proven features
- Less custom code = fewer bugs
- Easier to maintain and upgrade

### 3. Maintainable
- Moodle upgrades won't break custom systems
- Standard Moodle documentation applies
- Other developers can understand the code

### 4. Proven at Scale
- Custom roles: Used by thousands of Moodle sites
- Conditional access: Core feature, well-tested
- Cohort enrollment: Standard practice

### 5. Incremental
- Can add complexity later if needed
- Start simple, add features based on real user feedback
- Avoid building features nobody uses

---

## Tradeoffs & Limitations

### 1. Streams Aren't First-Class Entities

**Limitation:** Streams are sections with visibility rules, not database entities

**Impact:**
- Can't easily query "How many learners in Domain A?"
- Need to query Choice activity responses
- Reporting requires joins across multiple tables

**Workaround:**
- Create custom report: "Stream Enrollment Report"
- Query Choice activity responses
- Cache results for performance

**When this breaks:**
- If you need >5 streams per program (too many sections)
- If streams need complex dependencies (Domain A requires Domain B)

---

### 2. Courses ARE Programs

**Limitation:** Can't have a "program" without content

**Impact:**
- Program Owner must create course content immediately
- Can't define program structure separately from delivery
- Versioning requires course backup/restore

**Workaround:**
- Create "template" courses that can be duplicated
- Use course backup/restore for versioning
- Document versioning process

**When this breaks:**
- If you need programs to exist independently of content
- If you need complex program versioning with branching

---

### 3. Limited Stream Reporting

**Limitation:** No built-in "stream analytics"

**Impact:**
- Can't easily see: "Domain A completion rate vs. Domain B"
- Need custom SQL queries
- Reports require manual setup

**Workaround:**
- Create custom reports using Moodle's report builder
- Query: Choice responses + Completion data
- Schedule reports to run weekly

**When this breaks:**
- If you need real-time stream analytics
- If you need complex cross-stream comparisons

---

### 4. Still Looks Like Moodle

**Limitation:** UI is cleaner but still recognizably Moodle

**Impact:**
- Users familiar with Moodle will see similarities
- Can't completely rebrand the interface
- Some Moodle terminology remains in admin areas

**Workaround:**
- Hide standard blocks
- Override language strings
- Custom CSS for branding

**When this breaks:**
- If you need a completely custom UI
- If Moodle's navigation paradigm doesn't fit

---

### 5. Content Reusability is Manual

**Limitation:** No automatic content asset library

**Impact:**
- Reusing content requires backup/restore
- No version tracking for content
- Manual process to update content across programs

**Workaround:**
- Document backup/restore process
- Create "content library" course with reusable activities
- Use Moodle's "Import" feature

**When this breaks:**
- If you need automatic content versioning
- If you need content to exist independent of courses
- If you need complex content dependency tracking

---

## What We're NOT Building

### ❌ Separate Program Database Table
**Why:** Courses already do this. Just rename in UI.

### ❌ Content Asset Library
**Why:** Activities can be backed up/restored. Good enough.

### ❌ Custom Learning Path System
**Why:** Learning Plan Templates already exist.

### ❌ React Components
**Why:** PHP blocks work fine. React adds complexity.

### ❌ WhatsApp Integration
**Why:** External service. Get core working first.

### ❌ AI Assessment Generation
**Why:** Nice-to-have, not need-to-have. Defer.

### ❌ Trainer Coach Role
**Why:** Start with 3 roles. Add 4th and 5th later if needed.

### ❌ Enhanced Learner Role
**Why:** Standard student role works. Enhance later if needed.

---

## Decision Point: After 5 Weeks

After implementing this pragmatic approach, evaluate:

### Success Criteria
- ✅ System Admin can manage users but not create programs
- ✅ Program Owner can create programs but not manage users
- ✅ Trainer sees only assigned cohorts
- ✅ Learners can branch into streams
- ✅ Dashboards show role-appropriate content
- ✅ Mobile-responsive interface
- ✅ Terminology changed (Course → Program)

### Questions to Ask
1. **Are users happy with the interface?**
   - If yes: Success! Stop here.
   - If no: What specific pain points remain?

2. **Is stream reporting adequate?**
   - If yes: Section-based approach works.
   - If no: Consider building stream entity.

3. **Is content reusability working?**
   - If yes: Backup/restore is sufficient.
   - If no: Consider content asset library.

4. **Do we need Trainer Coach role?**
   - If yes: Add 4th role (1 week).
   - If no: Three roles are sufficient.

5. **Is the UI clean enough?**
   - If yes: PHP blocks are sufficient.
   - If no: Consider React components for specific areas.

### Next Steps Based on Feedback

**If 80% satisfied:**
- Stop here
- Focus on content creation and user training
- Iterate on small improvements

**If specific pain points identified:**
- Build only those specific features
- Don't rebuild everything
- Example: "Stream reporting is painful" → Build stream analytics, keep everything else

**If fundamental issues:**
- Re-evaluate whether Moodle is the right platform
- Consider comprehensive rebuild (24-week plan)
- But this is unlikely if you follow this guide

---

## Technical Implementation Notes

### File Structure

```
local_sceh_rules/
├── classes/
│   └── helper/
│       ├── cohort_filter.php (Week 2)
│       └── stream_helper.php (Week 3-4)
├── db/
│   └── access.php (Week 1 - custom capabilities)
├── lang/
│   └── en/
│       ├── local_sceh_rules.php (Week 5 - terminology)
│       └── moodle.php (Week 5 - core overrides)
└── version.php

block_sceh_dashboard/
├── block_sceh_dashboard.php (Weeks 1-5 - all dashboard logic)
├── styles.css (Week 5 - mobile responsive)
└── version.php
```

### Database Changes

**None required!** This approach uses existing Moodle tables:
- `{role}` - Custom roles
- `{role_capabilities}` - Role permissions
- `{cohort}` - User groups
- `{cohort_members}` - Cohort membership
- `{course}` - Programs (courses)
- `{course_sections}` - Streams (sections)
- `{choice}` - Stream selection
- `{choice_answers}` - User stream choices

### Moodle Version Compatibility

This approach works with:
- ✅ Moodle 4.0+
- ✅ Moodle 4.1+
- ✅ Moodle 4.2+
- ✅ Moodle 4.3+
- ✅ Moodle 5.0+ (current version)

All features used are core Moodle functionality.

---

## Summary

This pragmatic approach delivers:
- ✅ 3-layer responsibility model
- ✅ Role-based access control
- ✅ Trainer cohort filtering
- ✅ Branching learning paths (streams)
- ✅ Simplified dashboards
- ✅ Mobile-responsive UI
- ✅ 5 weeks instead of 24 weeks

By working with Moodle instead of against it.

**Next Step:** Review this guide with stakeholders and decide whether to proceed with pragmatic approach or comprehensive rebuild.
