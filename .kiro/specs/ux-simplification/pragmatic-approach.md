# Pragmatic Approach: What We Can Actually Do

**Date:** 2026-02-13  
**Status:** Analysis  
**Rule Applied:** RULE 1 - Simplest Solution First

---

## The Reality Check

The requirements document proposes a 6-month, 12-sprint rebuild. Let's be honest about what Moodle already does and what we actually need to build.

---

## What Moodle Already Has (Use It)

### ✅ Competency Framework
**Status:** Already implemented and in use

```php
// We're already using this extensively
core_competency\api
core_competency\competency_framework
core_competency\template (learning plan templates)
core_competency\user_competency
```

**What it gives us:**
- Hierarchical competencies (parent/child)
- Competency frameworks (containers)
- Learning plan templates
- User competency tracking
- Evidence collection
- Course-competency linking

**What we DON'T need to build:**
- ❌ Custom competency tables
- ❌ Competency hierarchy logic
- ❌ Evidence tracking system
- ❌ Competency completion logic

---

### ✅ Cohorts
**Status:** Core Moodle feature

**What it gives us:**
- User grouping
- Bulk enrollment
- Cohort-based access rules

**What we DON'T need to build:**
- ❌ Custom cohort tables
- ❌ Cohort enrollment logic

---

### ✅ Custom Roles & Capabilities
**Status:** Core Moodle feature, easy to implement

**What it gives us:**
- Define custom roles
- Granular capability control
- Context-aware permissions (system, course, user)
- Role assignment

**What we DON'T need to build:**
- ❌ Custom permission system
- ❌ Role management UI (Moodle has it)

---

### ✅ Courses
**Status:** Core Moodle feature

**Reality:** Courses ARE our programs. Stop fighting it.

**What it gives us:**
- Content container
- Enrollment management
- Activity tracking
- Completion criteria
- Versioning (via course backup/restore)

**What we DON'T need to build:**
- ❌ Separate "Program" entity
- ❌ Content versioning system
- ❌ Enrollment logic

---

## What Moodle DOESN'T Have (Build This)

### 🔴 Focus Streams
**Gap:** No concept of branching paths within a course

**Simplest Solution:**
Use course sections + conditional access

```
Course: Ophthalmology Fellowship
├── Section 1: Common Foundation (everyone)
├── Section 2: Domain A (conditional: completed Section 1 + chosen Domain A)
├── Section 3: Domain B (conditional: completed Section 1 + chosen Domain B)
```

**What to build:**
- Custom field on user enrollment: `chosen_stream` (Domain A, Domain B, etc.)
- Conditional access rules based on stream choice
- Dashboard filter to show "My Stream" activities

**Cost:** Streams are not first-class entities, just filtered views
**Benefit:** Works with existing Moodle completion system

---

### 🔴 Role-Based Dashboards
**Gap:** Everyone sees standard Moodle dashboard

**Simplest Solution:**
Custom dashboard blocks that show different content based on role

**What to build:**
- Enhance `block_sceh_dashboard` with role detection
- Show different cards based on role
- Hide standard Moodle blocks for non-admins

**What NOT to build:**
- ❌ Separate dashboard pages (use one page with conditional content)
- ❌ React SPA (PHP blocks work fine)
- ❌ Custom navigation system (use Moodle's)

---

### 🔴 Trainer-Specific Views
**Gap:** Teachers see all courses, not just assigned cohorts

**Simplest Solution:**
Filter course list by cohort assignment

**What to build:**
- Custom capability: `local/sceh:viewassignedcohortsonly`
- Override course list query to filter by user's cohort assignments
- Dashboard shows "My Cohorts" instead of "All Courses"

**What NOT to build:**
- ❌ Custom cohort management system (use Moodle's)
- ❌ Separate "Trainer" interface (filter existing interface)

---

### 🔴 Program Owner vs System Admin
**Gap:** Both use "Manager" role currently

**Simplest Solution:**
Create two custom roles with different capabilities

```php
// System Admin (sceh_system_admin)
- moodle/site:config
- moodle/user:create
- moodle/role:assign
- Cannot: moodle/course:create

// Program Owner (sceh_program_owner)  
- moodle/course:create
- moodle/competency:competencymanage
- moodle/competency:templatemanage
- Cannot: moodle/site:config, moodle/user:create
```

**What to build:**
- Define 2 custom roles
- Assign capabilities
- Update dashboard to detect role

**What NOT to build:**
- ❌ Custom permission system
- ❌ New database tables

---

## The Simplest Path Forward

### Phase 1: Role Separation (1 week)

**Goal:** Stop conflating System Admin and Program Owner

**Tasks:**
1. Define 3 custom roles (not 5):
   - `sceh_system_admin` - User management, reports
   - `sceh_program_owner` - Course/competency design
   - `sceh_trainer` - Delivery only (no course creation)
   
2. Update `block_sceh_dashboard` role detection:
```php
// OLD (broken)
$is_admin = has_capability('moodle/site:config', $context);
$is_teacher = has_capability('moodle/course:update', $context);

// NEW (correct)
$is_system_admin = has_capability('local/sceh:systemadmin', $context);
$is_program_owner = has_capability('local/sceh:programowner', $context);
$is_trainer = has_capability('local/sceh:trainer', $context);
```

3. Show different dashboard cards based on role

**Deliverable:** 3 distinct roles with proper separation

**Cost:** Trainer Coach and enhanced Learner roles deferred
**Benefit:** Solves 80% of the RBAC problem

---

### Phase 2: Trainer Cohort Filtering (1 week)

**Goal:** Trainers see only assigned cohorts

**Tasks:**
1. Add custom capability: `local/sceh:viewassignedcohortsonly`
2. Override course list to filter by cohort membership
3. Update dashboard to show "My Cohorts" section

**Deliverable:** Trainers see only their cohorts

**Cost:** Still uses course list, not custom UI
**Benefit:** Solves the "trainer sees everything" problem

---

### Phase 3: Stream Support via Sections (2 weeks)

**Goal:** Support branching learning paths

**Tasks:**
1. Add custom user enrollment field: `stream_choice`
2. Create conditional access rules based on stream
3. Add "Choose Your Stream" activity at branch point
4. Update dashboard to show "My Stream" filter

**Deliverable:** Learners can branch into specializations

**Cost:** Streams are not first-class entities
**Benefit:** Works with existing completion system

---

### Phase 4: Dashboard Polish (1 week)

**Goal:** Hide Moodle complexity

**Tasks:**
1. Hide standard Moodle blocks for non-admins
2. Add task-oriented cards ("Review 3 Submissions")
3. Improve terminology (Course → Program in UI strings)
4. Mobile-responsive cards

**Deliverable:** Cleaner, role-appropriate interface

**Cost:** Still looks like Moodle underneath
**Benefit:** Reduces cognitive load

---

## What We're NOT Building

### ❌ Separate Program Entity
**Why:** Courses already do this. Renaming them "Programs" in the UI is enough.

### ❌ Content Asset Library
**Why:** Moodle activities are already reusable via backup/restore. Versioning is overkill.

### ❌ Custom Learning Path System
**Why:** Learning plan templates already exist. Use them.

### ❌ React Components
**Why:** PHP blocks work fine. React adds complexity without clear benefit for this use case.

### ❌ WhatsApp Integration
**Why:** External service, not core to learning system. Defer until core features work.

### ❌ AI Assessment Generation
**Why:** Nice-to-have, not need-to-have. Defer.

### ❌ Trainer Coach Role
**Why:** Can be added later. Start with 3 roles, not 5.

---

## The Tradeoffs

### What We Get
- ✅ 3-layer responsibility model (System Admin, Program Owner, Trainer)
- ✅ Trainers see only assigned cohorts
- ✅ Branching learning paths (streams via sections)
- ✅ Role-based dashboards
- ✅ Works with existing Moodle features
- ✅ 5 weeks instead of 24 weeks

### What We Give Up
- ❌ Streams as first-class entities (they're filtered views)
- ❌ Content versioning (use backup/restore)
- ❌ Trainer Coach role (defer to Phase 2)
- ❌ React UI (use PHP blocks)
- ❌ WhatsApp integration (defer)
- ❌ AI features (defer)

### When This Breaks
- If you need >5 streams per program (section-based approach gets messy)
- If you need content to exist independent of courses (would need asset library)
- If you need real-time collaborative editing (would need React)
- If you need complex stream dependencies (would need custom logic)

---

## Recommendation

**Start with Phase 1-4 (5 weeks total)**

This gives you:
1. Proper role separation
2. Trainer cohort filtering  
3. Stream support (good enough)
4. Cleaner dashboards

Then **stop and evaluate**:
- Are users happy?
- What's still painful?
- Do we actually need the complex stuff?

**Don't build the 24-week solution until you've proven the 5-week solution isn't enough.**

---

## Next Steps

1. Review this pragmatic approach
2. Decide: 5-week pragmatic path or 24-week comprehensive rebuild?
3. If pragmatic: Start with Phase 1 (role separation)
4. If comprehensive: Acknowledge we're building a custom LMS on top of Moodle

