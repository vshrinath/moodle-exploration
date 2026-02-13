# UX Simplification & RBAC Enhancement - Design Document

## Executive Summary

Based on the PRD and user stories, the current Moodle implementation has **fundamental architectural misalignment**:

**Current State:**
- Roles: Admin, Teacher, Student (Moodle defaults)
- Structure: Courses, Cohorts, Competencies (LMS-centric)
- Navigation: Standard Moodle menus

**Required State:**
- Roles: System Admin, Program Owner, Trainer, Trainer Coach, Learner (medical hierarchy)
- Structure: Programs, Streams, Learning Paths, Cohorts (learning-centric)
- Navigation: Role-based dashboards with task-oriented workflows

## Gap Analysis

### Critical Gaps

| Requirement | Current State | Gap Severity |
|-------------|---------------|--------------|
| **3-Layer Responsibility Model** | Not implemented | 🔴 Critical |
| **Program Owner role** | Mapped to Manager (wrong) | 🔴 Critical |
| **Trainer vs Trainer Coach** | Both mapped to Teacher | 🔴 Critical |
| **Focus Streams** | Not implemented | 🔴 Critical |
| **Learning Paths** | Exists but not stream-aware | 🟡 Medium |
| **Content as reusable assets** | Partially (activities) | 🟡 Medium |
| **Versioned programs** | Not implemented | 🟡 Medium |
| **Self-paced programs** | Supported but not role-aware | 🟢 Low |

### Role Mapping Issues

**Current Moodle Roles → Required Roles**

```
❌ WRONG MAPPING:
Manager → Program Owner (Manager has too many system permissions)
Teacher → Trainer (Teacher can create courses, shouldn't)
Student → Learner (Correct, but limited)

✅ CORRECT MAPPING NEEDED:
System Admin → Custom role with governance only
Program Owner → Custom role with curriculum design only
Trainer → Custom role with delivery only
Trainer Coach → Custom role with cross-cohort trainer oversight
Learner → Enhanced student role
```

## Design Approach

### Phase 1: Role Architecture (Foundation)

**Goal:** Implement the 3-layer responsibility model with proper role separation

#### 1.1 Create Custom Moodle Roles

**New Roles to Create:**

1. **sceh_system_admin**
   - Capabilities: User management, system reports, governance
   - Cannot: Create programs, define competencies, deliver training
   - Maps to: System / Org Admin

2. **sceh_program_owner**
   - Capabilities: Create programs, define competencies, design learning paths
   - Cannot: Deliver training, manage users, see all cohorts
   - Maps to: Program Owner / Learning Architect

3. **sceh_trainer**
   - Capabilities: View assigned cohorts, mark attendance, provide feedback
   - Cannot: Create programs, define competencies, see other trainers' cohorts
   - Maps to: Trainer / Facilitator

4. **sceh_trainer_coach**
   - Capabilities: View all trainers, review session feedback, coach trainers
   - Cannot: Create programs, deliver training directly
   - Maps to: Trainer Coach / Faculty Lead

5. **sceh_learner**
   - Capabilities: View own progress, submit assessments, access content
   - Cannot: See other learners' data, access admin functions
   - Maps to: Student / Learner

#### 1.2 Capability Matrix

```
Capability                          | Sys Admin | Prog Owner | Trainer | Trainer Coach | Learner
------------------------------------|-----------|------------|---------|---------------|--------
moodle/site:config                  | ✓         | ✗          | ✗       | ✗             | ✗
moodle/user:create                  | ✓         | ✗          | ✗       | ✗             | ✗
local/program:create                | ✗         | ✓          | ✗       | ✗             | ✗
local/program:define_competencies   | ✗         | ✓          | ✗       | ✗             | ✗
local/program:create_learning_path  | ✗         | ✓          | ✗       | ✗             | ✗
local/cohort:deliver                | ✗         | ✗          | ✓       | ✗             | ✗
local/cohort:mark_attendance        | ✗         | ✗          | ✓       | ✗             | ✗
local/trainer:view_all              | ✗         | ✗          | ✗       | ✓             | ✗
local/trainer:coach                 | ✗         | ✗          | ✗       | ✓             | ✗
local/learner:view_own_progress     | ✗         | ✗          | ✗       | ✗             | ✓
```

### Phase 2: Program & Stream Architecture

**Goal:** Implement Programs, Streams, and Learning Paths as first-class entities

#### 2.1 Data Model

```
Program (new entity)
├── id
├── name
├── description
├── version
├── owner_id (sceh_program_owner)
├── status (draft, active, archived)
├── created_at
└── updated_at

Stream (new entity)
├── id
├── program_id
├── name (e.g., "Common Foundation", "Domain A")
├── type (common, focus)
├── sequence_order
└── description

Learning_Path (enhanced)
├── id
├── program_id
├── stream_id (nullable for common paths)
├── name
├── sequence
└── competencies[] (ordered)

Program_Competency_Mapping (new)
├── program_id
├── competency_id
├── stream_id (nullable)
├── classification (core, allied)
└── sequence_order
```

#### 2.2 Stream-Aware Learning Paths

**User Story 7 & 8 Implementation:**

```php
class learning_path {
    // Story 7: Ordered sequence
    public function get_ordered_competencies($stream_id = null) {
        // Returns competencies in sequence
        // Respects prerequisites
        // Filters by stream if specified
    }
    
    // Story 8: Branching paths
    public function get_available_streams($learner_id) {
        // Returns streams learner can branch into
        // Based on completed common foundation
    }
}
```

### Phase 3: Dashboard Redesign

**Goal:** Role-specific, task-oriented interfaces

#### 3.1 Dashboard Views by Role

**Program Owner Dashboard:**
```
┌─────────────────────────────────────────┐
│ My Programs                             │
│ ┌─────────┐ ┌─────────┐ ┌─────────┐   │
│ │ Program │ │ Program │ │ Create  │   │
│ │ A       │ │ B       │ │ New     │   │
│ │ Active  │ │ Draft   │ │ Program │   │
│ └─────────┘ └─────────┘ └─────────┘   │
│                                         │
│ Quick Actions                           │
│ • Define Competencies                   │
│ • Create Learning Path                  │
│ • Version Program                       │
│                                         │
│ Recent Activity                         │
│ • Program A: 3 cohorts in progress     │
│ • Program B: Awaiting review           │
└─────────────────────────────────────────┘
```

**Trainer Dashboard:**
```
┌─────────────────────────────────────────┐
│ My Cohorts                              │
│ ┌─────────────────────────────────────┐ │
│ │ Cohort A - Advanced Ops             │ │
│ │ Next Session: Today 2PM             │ │
│ │ 3 submissions pending review        │ │
│ │ [Mark Attendance] [Review Work]     │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ ┌─────────────────────────────────────┐ │
│ │ Cohort B - Clinical Skills          │ │
│ │ Next Session: Tomorrow 10AM         │ │
│ │ All caught up ✓                     │ │
│ │ [View Schedule] [Session Notes]     │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ Teaching Resources                      │
│ • Session Plans                         │
│ • Assessment Rubrics                    │
│ • Learner Progress Reports              │
└─────────────────────────────────────────┘
```

**Learner Dashboard:**
```
┌─────────────────────────────────────────┐
│ My Learning Journey                     │
│                                         │
│ Current Program: Advanced Operations    │
│ Stream: Patient Experience              │
│                                         │
│ Progress: ████████░░ 80%                │
│                                         │
│ Next Up                                 │
│ ┌─────────────────────────────────────┐ │
│ │ 📚 Module: Communication Skills     │ │
│ │ ⏱️  Estimated: 2 hours              │ │
│ │ [Start Learning]                    │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ Pending Tasks                           │
│ • Submit Case Study (Due: 2 days)      │
│ • Complete Assessment (Due: 5 days)    │
│                                         │
│ My Achievements                         │
│ 🏆 5 competencies completed             │
│ ⭐ 3 badges earned                      │
└─────────────────────────────────────────┘
```

#### 3.2 Navigation Simplification

**Current:** Standard Moodle menu → Courses → Activities → Competencies
**New:** Dashboard → Task → Complete (max 2 clicks)

**Examples:**
- Trainer: Dashboard → "Review Submissions" → Feedback form
- Learner: Dashboard → "Next Module" → Content
- Program Owner: Dashboard → "Edit Learning Path" → Drag-drop interface

### Phase 4: React Component Strategy

**Goal:** Interactive components where they add value, not everywhere

#### 4.1 React Components (High Value)

**1. Program Builder (Program Owner)**
```jsx
<ProgramBuilder>
  <StreamEditor />
  <LearningPathDesigner>
    <CompetencySelector />
    <SequenceEditor />
    <PrerequisiteMapper />
  </LearningPathDesigner>
  <VersionControl />
</ProgramBuilder>
```

**2. Trainer Cohort Dashboard**
```jsx
<CohortDashboard>
  <SessionSchedule />
  <AttendanceMarker />
  <SubmissionQueue>
    <FeedbackForm />
  </SubmissionQueue>
  <LearnerProgressCards />
</CohortDashboard>
```

**3. Learner Progress Tracker**
```jsx
<LearnerJourney>
  <ProgressVisualization />
  <NextSteps />
  <CompetencyTree />
  <AchievementGallery />
</LearnerJourney>
```

#### 4.2 Keep as PHP/Mustache (Low Complexity)

- User management pages
- System configuration
- Simple list views
- Static content pages
- Reports (unless interactive filtering needed)

### Phase 5: Content & Assessment Architecture

**Goal:** Implement reusable content library (Story 30-34)

#### 5.1 Content Asset Library

```
Content_Asset (new entity)
├── id
├── title
├── type (video, document, interactive, assessment)
├── version
├── created_by (program_owner_id)
├── status (draft, published, archived)
└── metadata (tags, duration, difficulty)

Content_Reference (new entity)
├── learning_path_id
├── content_asset_id
├── content_version
├── sequence_order
├── is_mandatory
└── completion_criteria
```

**Benefits:**
- Same content used across multiple programs
- Version history preserved
- Update once, reflects everywhere (or version-locked)

#### 5.2 Assessment Generation (Story 33)

```php
class ai_assessment_generator {
    public function generate_from_content($content_asset_id) {
        // AI generates questions from content
        // Returns draft assessment for review
        // Does NOT auto-publish
    }
    
    public function suggest_reinforcement($learner_id, $competency_id) {
        // Analyzes weak areas
        // Suggests additional practice
        // Requires Program Owner approval
    }
}
```

## Implementation Roadmap

### Sprint 1-2: Role Foundation (2 weeks)
- Create 5 custom Moodle roles
- Define capability matrix
- Migrate existing users to new roles
- Test permission boundaries

### Sprint 3-4: Program & Stream Entities (2 weeks)
- Create database schema for Programs, Streams
- Build Program creation UI (React)
- Implement stream-aware learning paths
- Migration script for existing courses → programs

### Sprint 5-6: Dashboard Redesign (2 weeks)
- Build role-specific dashboard layouts
- Implement task-based navigation
- Add quick actions
- Mobile responsive design

### Sprint 7-8: Content Library (2 weeks)
- Build content asset management
- Implement versioning
- Create content reference system
- Migration for existing content

### Sprint 9-10: React Components (2 weeks)
- Program Builder (React)
- Cohort Dashboard (React)
- Learner Journey (React)
- Integration testing

### Sprint 11-12: Polish & Training (2 weeks)
- User acceptance testing
- Documentation
- Training materials
- Rollout plan

**Total: 12 sprints (24 weeks / 6 months)**

## Success Criteria

### Functional
- ✅ All 5 roles implemented with correct permissions
- ✅ Programs can be created with streams
- ✅ Learning paths are stream-aware
- ✅ Content is reusable across programs
- ✅ Dashboards are role-specific

### User Experience
- ✅ Common tasks complete in ≤2 clicks
- ✅ No Moodle jargon in primary interfaces
- ✅ Mobile-friendly (responsive design)
- ✅ User satisfaction >4.5/5

### Technical
- ✅ No breaking changes to existing data
- ✅ Migration path for current users
- ✅ Performance: page load <2s
- ✅ Accessibility: WCAG AA compliant

## Risk Mitigation

| Risk | Mitigation |
|------|------------|
| User resistance to new roles | Gradual migration, training, clear communication |
| Data migration complexity | Automated scripts with rollback, extensive testing |
| React integration issues | Use AMD wrapper, progressive enhancement |
| Performance degradation | Lazy loading, caching, code splitting |
| Scope creep | Strict adherence to user stories, phase gates |

## Next Steps

1. **Review & Approve** this design document
2. **Prioritize** user stories for Sprint 1
3. **Set up** React development environment
4. **Create** detailed technical specifications for custom roles
5. **Begin** Sprint 1: Role Foundation

---

**Document Status:** Draft for Review  
**Last Updated:** 2026-02-13  
**Owner:** Product Management  
**Reviewers:** Development Team, Stakeholders
