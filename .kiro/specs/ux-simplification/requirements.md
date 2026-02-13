# UX Simplification & RBAC Enhancement - Requirements

**Based on:** 
- `docs/PRD - Role, architecture and more.md`
- `docs/User stories and acceptance criteria.md`

**Last Updated:** 2026-02-13  
**Status:** Approved for Implementation

---

## Problem Statement

The current Moodle implementation has **fundamental architectural misalignment** with the required learning system:

### Current State Issues

1. **Wrong Role Model**: Uses generic LMS roles (Manager, Teacher, Student) instead of learning-specific roles
2. **Missing 3-Layer Responsibility Model**: Learning Design, Delivery, and Oversight responsibilities are conflated
3. **No Program/Stream Architecture**: Uses Moodle courses instead of Programs with Focus Streams
4. **Content Not Reusable**: Content tied to courses, not available as versioned assets
5. **Trainer ≠ Curriculum Designer**: Current "Teacher" role can both design and deliver, violating separation of concerns
6. **No Trainer Coaching**: No role for cross-cohort trainer quality oversight
7. **Technical Terminology**: LMS jargon instead of learning-appropriate language

### Impact

- Program Owners cannot design curriculum without system admin access
- Trainers see curriculum design tools they shouldn't use
- No way to track trainer quality across cohorts
- Learners cannot follow branching learning paths (streams)
- Content must be duplicated across programs
- Self-paced and facilitated programs treated differently

---

## Foundational Principles

### Three Layers of Responsibility

**These layers are non-negotiable and must not be collapsed:**

1. **Learning Design Authority**
   - *What should be learned, in what sequence, to what standard*
   - Owns: Curriculum intent, competency frameworks, learning paths, completion criteria
   - Primary Owner: Program Owner / Learning Architect

2. **Delivery & Enablement**
   - *Who runs cohorts, sessions, and assessments*
   - Owns: Execution quality, learner experience, teaching effectiveness
   - Primary Owner: Trainer / Facilitator

3. **Oversight & Insight**
   - *How well the system is working overall*
   - Owns: Cross-program visibility, quality assurance, trend analysis
   - Primary Owner: System / Org Admin

**Key Insight:** Roles may span multiple layers, but each layer has a clear primary owner. No single role should dominate all three layers simultaneously.

---

## User Roles (Aligned to PRD)

### 1. System / Org Admin

**Layer:** Oversight & Insight (primary)

**Purpose:** Platform governance and cross-program oversight

**Responsibilities:**
- Manage users and role assignments
- View organization-wide reports
- Monitor program health and outcomes
- Manage system configuration and governance
- Track compliance-oriented programs

**Does NOT:**
- Create programs or define curriculum
- Deliver training or run cohorts
- Design learning paths

**User Stories:** 17, 18, 19, 20, 22, 23, 24, 25, 39, 40

---

### 2. Program Owner / Learning Architect

**Layer:** Learning Design Authority (primary)

**Purpose:** Define *what* is taught and *why*

**Responsibilities:**
- Create and version programs (Story 1, 2, 3)
- Define competency frameworks independently (Story 4, 5, 6)
- Design learning paths and sequencing (Story 7, 8)
- Define focus streams (common + specialized)
- Create and manage reusable content assets (Story 30, 31)
- Create assessments aligned to competencies (Story 32, 33)
- Use AI assistance for content transformation (Story 35, 36)

**Does NOT:**
- Run cohorts or deliver sessions
- Manage users or system configuration
- Enroll learners or assign trainers

**User Stories:** 1-8, 26, 30-36

---

### 3. Trainer / Facilitator

**Layer:** Delivery & Enablement (primary)

**Purpose:** Execute learning delivery at cohort level

**Responsibilities:**
- View assigned cohorts only (Story 9)
- View learning paths in read-only mode (Story 10)
- Deliver sessions and mark attendance (Story 11)
- Review learner submissions and provide feedback (Story 12)
- Support learners through their journey

**Does NOT:**
- Create programs or define competencies
- Design learning paths or modify curriculum
- See other trainers' cohorts
- Enroll learners

**User Stories:** 9-12

---

### 4. Trainer Coach / Faculty Lead

**Layer:** Delivery & Enablement (quality oversight)

**Purpose:** Ensure trainer quality and pedagogical consistency

**Responsibilities:**
- View all trainers across cohorts
- Review session feedback and ratings
- Coach trainers on delivery quality
- Improve teaching playbooks
- Identify delivery issues across cohorts

**Scope:** Cross-cohort, cross-program

**Does NOT:**
- Create programs or define curriculum
- Deliver training directly (unless also a Trainer)
- Manage system configuration

**User Stories:** (Implied in PRD, not explicit in user stories)

---

### 5. Learner / Student

**Layer:** Delivery & Enablement (participant)

**Purpose:** Progress through learning pathways and demonstrate competency

**Responsibilities:**
- View enrolled programs and learning paths (Story 13)
- Complete learning activities at own pace (Story 14)
- Submit assessments and receive feedback (Story 15)
- View own competency progress (Story 16)
- Participate in cohorts
- Receive WhatsApp reminders (Story 38)

**Does NOT:**
- See other learners' data
- Access admin or design functions
- Modify learning paths

**User Stories:** 13-16, 34, 38

---

## Structural Entities (How Learning Is Organized)

### Program

**Definition:** A complete learning initiative with defined outcomes and target audience

**Attributes:**
- Name, description, intended audience
- Identifiable owner (Program Owner)
- Version number (for updates without disrupting active cohorts)
- Status (draft, active, archived)
- High-level outcomes

**Key Insight:** Programs exist independently of cohorts or learners. They define *what* should be learned.

**User Stories:** 1, 2, 3

---

### Focus Stream

**Definition:** Conceptual grouping within a program representing specialization or pathway choice

**Types:**
- **Common Foundation:** Mandatory baseline for all learners
- **Focus Streams:** Specialized pathways (e.g., Domain A, Domain B)

**Attributes:**
- Name, description
- Type (common, focus)
- Sequence order
- Parent program

**Key Insight:** Streams are conceptual, not operational. A learner may start in common stream and branch into focus streams. Streams can cut across cohorts.

**User Stories:** 8

---

### Learning Path

**Definition:** Ordered sequence of learning experiences within a program/stream

**Attributes:**
- Ordered competencies
- Stream association (nullable for common paths)
- Progression rules and gates
- Prerequisites respected

**Key Insight:** Learning paths define *what* unfolds over time. They are designed by Program Owners, consumed by Learners, and viewed (read-only) by Trainers.

**User Stories:** 7, 8, 10, 13

---

### Cohort

**Definition:** Operational grouping for delivery, time-bound

**Attributes:**
- Program/stream assignment
- Trainer assignment
- Schedule
- Enrolled learners

**Key Insight:** Cohorts define *how* learning is delivered. They are operational, not conceptual. A learner may move between cohorts while maintaining learning path continuity.

**User Stories:** 9, 27

---

### Content Asset

**Definition:** Reusable learning content independent of programs

**Types:**
- Video, document, interactive, assessment

**Attributes:**
- Title, type, version
- Created by (Program Owner)
- Status (draft, published, archived)
- Metadata (tags, duration, difficulty)

**Key Insight:** Content is created once, referenced many times. Versioning preserves historical learner records while allowing updates.

**User Stories:** 30, 31

---

## Current System Gaps

### Critical Architectural Gaps

| Required Entity | Current Moodle Equivalent | Gap |
|-----------------|---------------------------|-----|
| **Program** | Course | ❌ Courses are delivery containers, not curriculum definitions |
| **Focus Stream** | None | ❌ Not implemented |
| **Learning Path** | Competency Template | ⚠️ Exists but not stream-aware |
| **Cohort** | Cohort | ✅ Exists but not program-aware |
| **Content Asset** | Activity | ⚠️ Tied to courses, not reusable |

### Role Mapping Gaps

| Required Role | Current Moodle Role | Gap |
|---------------|---------------------|-----|
| **System Admin** | Manager | ⚠️ Manager has too many permissions |
| **Program Owner** | Manager | ❌ Wrong - Manager is operational, not design |
| **Trainer** | Teacher | ❌ Teacher can create courses (shouldn't) |
| **Trainer Coach** | None | ❌ Not implemented |
| **Learner** | Student | ✅ Mostly correct |

### Current Role Detection (Broken)

```php
// Current implementation in block_sceh_dashboard
$is_admin = has_capability('moodle/site:config', $context);
$is_teacher = has_capability('moodle/course:update', $context);
$is_student = !$is_admin && !$is_teacher;
```

**Problems:**
- No distinction between System Admin and Program Owner
- No Trainer Coach role
- Trainers can create courses (wrong layer)
- No context-aware permissions (trainer sees all cohorts, not just assigned)

---

## Functional Requirements

### FR-1: Role Architecture

**FR-1.1: Create Custom Moodle Roles**

Create 5 custom roles with capabilities aligned to the 3-layer responsibility model:

1. `sceh_system_admin` - Oversight & Insight layer
2. `sceh_program_owner` - Learning Design Authority layer
3. `sceh_trainer` - Delivery & Enablement layer
4. `sceh_trainer_coach` - Delivery quality oversight
5. `sceh_learner` - Enhanced student role

**Acceptance Criteria:**
- Each role has distinct capability set
- No role can perform actions from all 3 layers
- Roles can be assigned to users
- Role assignments are auditable (Story 23)

**User Stories:** 20, 22

---

**FR-1.2: Context-Aware Permissions**

Implement permission checks that respect context:

- Trainers see only assigned cohorts (Story 9)
- Program Owners see only their programs
- Learners see only own data (Story 16)
- Trainer Coaches see all trainers but cannot deliver
- System Admins see aggregate data, not individual learner details (Story 18)

**Acceptance Criteria:**
- Permission checks enforce context boundaries
- Attempting to access unauthorized data returns 403
- Audit log captures permission violations

**User Stories:** 9, 16, 18, 20, 22

---

**FR-1.3: Role-Based Dashboard Views**

Each role sees a different dashboard on login:

- System Admin: Organization-wide metrics, user management
- Program Owner: My programs, design tools, program insights
- Trainer: My cohorts, pending reviews, session schedule
- Trainer Coach: Trainer performance, coaching queue
- Learner: My journey, next steps, achievements

**Acceptance Criteria:**
- Dashboard content matches role responsibilities
- No Moodle standard menus visible
- All role-appropriate actions accessible from dashboard

**User Stories:** 9, 13, 17, 24

---

### FR-2: Program & Stream Architecture

**FR-2.1: Program Entity**

Create Program as first-class entity separate from Moodle courses:

**Database Schema:**
```sql
CREATE TABLE mdl_sceh_program (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    description TEXT,
    version INT,
    owner_id BIGINT, -- references user with sceh_program_owner role
    status ENUM('draft', 'active', 'archived'),
    outcomes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Acceptance Criteria:**
- Programs can be created without cohorts (Story 1)
- Programs have identifiable owners (Story 1)
- Programs can be versioned (Story 3)
- Existing cohorts remain on original version when program updated (Story 3)

**User Stories:** 1, 2, 3

---

**FR-2.2: Focus Stream Entity**

Implement streams within programs:

**Database Schema:**
```sql
CREATE TABLE mdl_sceh_stream (
    id BIGINT PRIMARY KEY,
    program_id BIGINT,
    name VARCHAR(255),
    type ENUM('common', 'focus'),
    sequence_order INT,
    description TEXT
);
```

**Acceptance Criteria:**
- Streams can be defined within programs (Story 8)
- Streams can be marked as common or focus
- Learners can be associated with streams (Story 8)
- Learning paths can reference streams

**User Stories:** 8

---

**FR-2.3: Stream-Aware Learning Paths**

Enhance learning paths to support streams:

**Database Schema:**
```sql
CREATE TABLE mdl_sceh_learning_path (
    id BIGINT PRIMARY KEY,
    program_id BIGINT,
    stream_id BIGINT NULL, -- NULL for common paths
    name VARCHAR(255),
    sequence INT
);

CREATE TABLE mdl_sceh_path_competency (
    path_id BIGINT,
    competency_id BIGINT,
    sequence_order INT,
    is_prerequisite BOOLEAN
);
```

**Acceptance Criteria:**
- Learning paths can be ordered sequences (Story 7)
- Paths respect prerequisite relationships (Story 7)
- Paths can be stream-specific or common (Story 8)
- Learners can branch into streams after common foundation (Story 8)

**User Stories:** 7, 8

---

**FR-2.4: Competency Classification**

Implement core/allied classification per program context:

**Database Schema:**
```sql
CREATE TABLE mdl_sceh_program_competency (
    program_id BIGINT,
    competency_id BIGINT,
    stream_id BIGINT NULL,
    classification ENUM('core', 'allied'),
    sequence_order INT
);
```

**Acceptance Criteria:**
- Competencies can be marked core or allied per program (Story 6)
- Same competency can have different classifications in different programs (Story 6)
- Classification is visible in reporting (Story 6, 25)

**User Stories:** 6, 25

---

### FR-3: Content Asset Library

**FR-3.1: Reusable Content Assets**

Create content as independent, versioned assets:

**Database Schema:**
```sql
CREATE TABLE mdl_sceh_content_asset (
    id BIGINT PRIMARY KEY,
    title VARCHAR(255),
    type ENUM('video', 'document', 'interactive', 'assessment'),
    version INT,
    created_by BIGINT, -- Program Owner
    status ENUM('draft', 'published', 'archived'),
    metadata JSON,
    content_data TEXT
);

CREATE TABLE mdl_sceh_content_reference (
    learning_path_id BIGINT,
    content_asset_id BIGINT,
    content_version INT,
    sequence_order INT,
    is_mandatory BOOLEAN,
    completion_criteria JSON
);
```

**Acceptance Criteria:**
- Content can be created independently of programs (Story 30)
- Content can be referenced by multiple learning paths (Story 30)
- Content can be versioned (Story 31)
- Historical learner records remain linked to consumed version (Story 31)

**User Stories:** 30, 31

---

**FR-3.2: AI Assessment Generation**

Implement AI-assisted assessment creation:

**Acceptance Criteria:**
- AI can generate assessments from content (Story 33)
- Generated assessments are editable before publishing (Story 33)
- Generated assessments do NOT auto-publish (Story 33)
- AI can suggest reinforcement based on performance (Story 36)
- Suggestions require Program Owner approval (Story 36)

**User Stories:** 33, 35, 36

---

### FR-4: Simplified Navigation

**FR-4.1: Single Entry Point**

All features accessible from role-based dashboard:

**Acceptance Criteria:**
- Dashboard is landing page after login
- No standard Moodle menus visible in primary interface
- Breadcrumb trail shows current location
- All role-appropriate actions accessible from dashboard

---

**FR-4.2: Task-Oriented Interface**

Replace feature-based navigation with task-based:

**Examples:**
- Trainer: "Review 3 Submissions" (not "Grade Assignments")
- Learner: "Continue Learning" (not "View Course")
- Program Owner: "Design Learning Path" (not "Manage Competencies")

**Acceptance Criteria:**
- Common tasks complete in ≤2 clicks
- Pending tasks show counts
- Next action is always clear

**User Stories:** 9, 11, 12, 13, 14, 15

---

### FR-5: Terminology & Language

**FR-5.1: Learning-Appropriate Terminology**

Replace LMS jargon with learning-appropriate terms:

| Current (LMS) | New (Learning) |
|---------------|----------------|
| Course | Program |
| Cohort | Cohort (keep - operationally correct) |
| Competency | Competency (keep - educationally correct) |
| Learning Plan | Learning Path |
| Assignment | Assessment |
| Grade | Feedback |
| Teacher | Trainer |
| Manager | Program Owner / System Admin |

**Acceptance Criteria:**
- All user-facing text uses new terminology
- Help documentation updated
- No LMS jargon in primary interfaces

---

**FR-5.2: Contextual Help**

Provide in-context help for learning concepts:

**Acceptance Criteria:**
- Tooltips explain key concepts
- "What is this?" links to help articles
- Video tutorials for complex workflows
- Help content is role-specific

---

### FR-6: React Component Integration

**FR-6.1: Program Builder (Program Owner)**

Interactive program design interface:

**Components:**
- Stream editor (drag-drop)
- Learning path designer (visual sequencing)
- Competency selector (searchable, filterable)
- Prerequisite mapper (visual graph)
- Version control interface

**Acceptance Criteria:**
- Program Owners can design programs visually
- Changes save without page refresh
- Undo/redo functionality
- Preview mode before publishing

**User Stories:** 1, 4, 5, 6, 7, 8

---

**FR-6.2: Trainer Cohort Dashboard**

Real-time cohort management:

**Components:**
- Session schedule (calendar view)
- Attendance marker (quick check-in)
- Submission queue (pending reviews)
- Feedback form (inline)
- Learner progress cards

**Acceptance Criteria:**
- Trainers see real-time updates
- Attendance can be marked inline
- Feedback submitted without page reload
- Mobile-responsive

**User Stories:** 9, 10, 11, 12

---

**FR-6.3: Learner Journey Tracker**

Visual progress tracking:

**Components:**
- Progress visualization (path with milestones)
- Next steps (clear call-to-action)
- Competency tree (hierarchical view)
- Achievement gallery (badges, certificates)

**Acceptance Criteria:**
- Learners see visual progress
- Next action is always clear
- Achievements are celebrated
- Mobile-responsive

**User Stories:** 13, 14, 15, 16

---

**FR-6.4: Interactive Analytics**

Charts and reports with drill-down:

**Components:**
- Program performance dashboard
- Cohort comparison charts
- Competency progress heatmaps
- Drop-off analysis

**Acceptance Criteria:**
- Charts are interactive (click to drill down)
- Data can be filtered by date, cohort, stream
- Export to PDF/Excel
- No individual learner rankings visible (Story 18)

**User Stories:** 17, 18, 24, 25, 26, 39, 40

---

### FR-7: Mobile & Channel Support

**FR-7.1: Responsive Design**

All interfaces work on mobile:

**Acceptance Criteria:**
- Cards stack vertically on mobile
- Touch-friendly buttons (min 44px)
- Swipe gestures for navigation
- Performance score >90 on mobile

---

**FR-7.2: WhatsApp Integration**

Push learning content and reminders:

**Acceptance Criteria:**
- Content snippets can be sent via WhatsApp (Story 37)
- Messages link back to platform (Story 37)
- Reminders sent for incomplete activities (Story 38)
- Learners can opt in/out (Story 38)

**User Stories:** 37, 38

---

## Non-Functional Requirements

### NFR-1: Performance

**NFR-1.1: Response Time**

**Acceptance Criteria:**
- Learner dashboards load within 2 seconds (Story 21)
- Progress updates reflect without manual refresh (Story 21)
- Concurrent access by large cohorts does not degrade performance (Story 21)

**User Stories:** 21

---

### NFR-2: Security & Privacy

**NFR-2.1: Role-Based Access Control**

**Acceptance Criteria:**
- Access governed by role permissions (Story 22)
- Learners cannot view other learners' personal data (Story 22)
- Trainers cannot access unassigned cohorts (Story 22)
- All permission checks enforced server-side

**User Stories:** 20, 22

---

**NFR-2.2: Audit Trail**

**Acceptance Criteria:**
- Changes to programs, competencies, paths are logged (Story 23)
- Learner completion and assessment events timestamped (Story 23)
- Audit logs accessible to authorized roles (Story 23)

**User Stories:** 23

---

### NFR-3: Data Integrity

**NFR-3.1: Version Isolation**

**Acceptance Criteria:**
- Updating program outcomes does not affect existing learner progress (Story 2)
- Dependency changes do not retroactively invalidate completed competencies (Story 5)
- Content version updates preserve historical learner records (Story 31)

**User Stories:** 2, 3, 5, 31

---

**NFR-3.2: Referential Integrity**

**Acceptance Criteria:**
- Competencies not deleted if removed from program (Story 4)
- Circular dependencies prevented (Story 5)
- Learner progress preserved when cohort assignment changes (Story 27)

**User Stories:** 4, 5, 27

---

### NFR-4: Usability

**NFR-4.1: Task Completion Efficiency**

**Acceptance Criteria:**
- Common tasks complete in ≤2 clicks
- Next action always clear
- No dead ends (always a way forward)

---

**NFR-4.2: Accessibility**

**Acceptance Criteria:**
- WCAG AA compliant
- Screen reader compatible
- Keyboard navigation supported
- Color contrast ratios meet standards

---

## Technical Approach

### Moodle + React Integration

**Strategy: AMD Module Wrapper**

```javascript
// Moodle AMD module that loads React
define(['jquery', 'react', 'react-dom'], function($, React, ReactDOM) {
    return {
        init: function(rootId, props) {
            const root = document.getElementById(rootId);
            ReactDOM.render(<ProgramBuilder {...props} />, root);
        }
    };
});
```

**Benefits:**
- Works with Moodle's existing JS system
- Progressive enhancement (works without JS)
- No iframe isolation issues
- Access to Moodle's AJAX APIs

**Where to Use React:**
- Program Builder (complex interactions)
- Cohort Dashboard (real-time updates)
- Learner Journey (visual progress)
- Interactive charts (drill-down)

**Where to Keep PHP:**
- Simple list pages
- Admin configuration
- Static content
- Reports (unless interactive)

## Success Metrics

### Role Adoption (Stories 1-20)
- 100% of Program Owners can create programs without system admin help (Story 1)
- 100% of Trainers see only assigned cohorts (Story 9)
- 100% of Learners can view their learning path (Story 13)
- 90% of users understand their role within first login
- Zero cross-role permission violations

### Program & Stream Architecture (Stories 1-8, 26-29)
- 100% of programs have identifiable owners (Story 1)
- Programs can be versioned without disrupting active cohorts (Story 3)
- Competencies can be reused across programs (Story 4)
- Learning paths respect prerequisite relationships (Story 7)
- Learners can branch into focus streams (Story 8)
- Cohorts can be reassigned without losing learner progress (Story 27)

### Content Reusability (Stories 30-31)
- Content assets can be referenced by multiple programs (Story 30)
- Content can be versioned without breaking historical records (Story 31)
- 80% reduction in content duplication across programs

### User Experience (Stories 9-16, 21)
- Learner dashboards load within 2 seconds (Story 21)
- Common tasks complete in ≤2 clicks
- 90% of users complete primary task without help documentation
- Mobile usage accounts for >40% of learner interactions

### Data Integrity & Audit (Stories 2, 5, 23, 31)
- Zero retroactive invalidation of completed competencies (Story 2, 5)
- 100% of program/competency changes logged (Story 23)
- Historical learner records preserved across content versions (Story 31)

### Reporting & Analytics (Stories 17-19, 24-26, 39-40)
- System Admins can view organization-wide metrics (Story 17, 18)
- Program Owners can view program-specific insights (Story 24, 25, 26)
- Reports generated in <5 seconds (Story 39)
- No individual learner rankings visible (Story 18)

### Mobile & Channel Support (Stories 37-38)
- WhatsApp content delivery functional (Story 37)
- Learners receive reminders for incomplete activities (Story 38)
- Mobile performance score >90

### Technical
- Page load time <2 seconds (Story 21)
- Accessibility score (WCAG AA) >95
- Zero critical security issues
- All permission checks enforced server-side (Story 20, 22)

## Out of Scope

### Explicitly NOT Included
- Native mobile apps (mobile web only)
- Integration with hospital EMR systems
- Video conferencing integration (use external tools)
- Complete Moodle theme redesign (use existing theme with custom components)
- Automated grading of subjective assessments
- Real-time video streaming infrastructure

### Deferred to Future Phases
- Advanced AI features beyond assessment generation (Stories 33, 35, 36)
- Multi-language support (English only initially)
- Offline mobile app functionality
- Advanced analytics (predictive modeling, ML-based insights)
- Integration with external credentialing systems

## Dependencies

### Technical Dependencies
- Moodle 5.0+ (current: 5.0.1) ✅
- PHP 8.1+ ✅
- PostgreSQL 13+ or MySQL 8.0+ ✅
- React 18+ (to be added)
- Chart.js or Recharts for interactive analytics (to be added)
- WhatsApp Business API for messaging (Stories 37-38)

### Data Dependencies
- Custom role definitions (sceh_system_admin, sceh_program_owner, sceh_trainer, sceh_trainer_coach, sceh_learner)
- New database tables for Programs, Streams, Learning Paths, Content Assets
- Migration scripts for existing data
- User role migration (existing users need role reassignment)

### Process Dependencies
- Program Owner training on new design tools
- Trainer training on cohort-focused interface
- Learner onboarding for new navigation
- System Admin training on new reporting

### External Dependencies
- WhatsApp Business API account and approval (Stories 37-38)
- AI service for assessment generation (Stories 33, 35, 36) - provider TBD
- Mobile device testing lab or service

## Risks & Mitigation

| Risk | Impact | Probability | Mitigation | Related Stories |
|------|--------|-------------|------------|-----------------|
| **React conflicts with Moodle JS** | High | Medium | Use AMD wrapper, namespace components, progressive enhancement | All React components |
| **Users resist role separation** | High | High | Clear communication of benefits, gradual rollout, training sessions, feedback loop | 1-20 |
| **Data migration breaks existing records** | Critical | Medium | Comprehensive backup, staged migration, rollback plan, parallel run period | 2, 3, 5, 27, 31 |
| **Performance degradation with large cohorts** | High | Medium | Lazy loading, code splitting, caching, database indexing, load testing | 21, 39 |
| **Accessibility issues** | High | Medium | WCAG testing from Sprint 1, screen reader testing, keyboard navigation testing | All |
| **Mobile browser compatibility** | Medium | Low | Progressive enhancement, polyfills, cross-browser testing | 21, 37, 38 |
| **WhatsApp API rate limits** | Medium | Medium | Message queuing, batch processing, opt-in only, fallback to email | 37, 38 |
| **AI assessment quality concerns** | Medium | High | Human review required before publish, quality metrics, feedback loop | 33, 35, 36 |
| **Circular competency dependencies** | Medium | Low | Validation logic, visual dependency graph, automated cycle detection | 5, 7 |
| **Trainer Coach role adoption** | Medium | High | Clear value proposition, coaching playbooks, success stories | Trainer Coach stories |
| **Program versioning complexity** | High | Medium | Clear versioning UI, version comparison tools, rollback capability | 3, 31 |
| **Content asset orphaning** | Low | Medium | Reference tracking, "where used" reports, archive warnings | 30, 31 |

## Timeline

**Total Duration:** 12 sprints (24 weeks / 6 months)

### Sprint 1-2: Foundation & Role Architecture (4 weeks)
**Focus:** Custom roles, database schema, basic dashboards

**Deliverables:**
- 5 custom Moodle roles defined with capabilities
- Database tables for Programs, Streams, Learning Paths, Content Assets
- Basic role-based dashboard routing
- Permission enforcement framework

**User Stories:** 20, 22, 23

---

### Sprint 3-4: Program & Stream Management (4 weeks)
**Focus:** Program Owner tools for curriculum design

**Deliverables:**
- Program creation and versioning (React component)
- Stream definition (common + focus)
- Competency framework management
- Core/allied classification per program

**User Stories:** 1, 2, 3, 4, 5, 6, 8

---

### Sprint 5-6: Learning Path Designer (4 weeks)
**Focus:** Visual learning path creation with prerequisites

**Deliverables:**
- Learning path designer (React component)
- Prerequisite mapper (visual graph)
- Stream-aware path assignment
- Path preview mode

**User Stories:** 7, 8

---

### Sprint 7-8: Trainer & Learner Interfaces (4 weeks)
**Focus:** Cohort management and learner journey

**Deliverables:**
- Trainer cohort dashboard (React component)
- Learner journey tracker (React component)
- Attendance marking
- Submission review workflow
- Progress visualization

**User Stories:** 9, 10, 11, 12, 13, 14, 15, 16

---

### Sprint 9-10: Content Asset Library & AI (4 weeks)
**Focus:** Reusable content and AI-assisted assessment

**Deliverables:**
- Content asset creation and versioning
- Content reference system
- AI assessment generation (with human review)
- AI reinforcement suggestions

**User Stories:** 30, 31, 32, 33, 35, 36

---

### Sprint 11: Analytics & Reporting (2 weeks)
**Focus:** Interactive dashboards for all roles

**Deliverables:**
- System Admin organization-wide dashboard
- Program Owner program insights
- Trainer Coach cross-cohort view
- Interactive charts with drill-down
- Export functionality

**User Stories:** 17, 18, 19, 24, 25, 26, 39, 40

---

### Sprint 12: Mobile & WhatsApp Integration (2 weeks)
**Focus:** Mobile optimization and channel support

**Deliverables:**
- Responsive design for all interfaces
- WhatsApp content delivery
- WhatsApp reminders
- Mobile performance optimization
- Cross-browser testing

**User Stories:** 21, 37, 38

---

### Post-Launch: Cohort Management Enhancements (Future)
**Deferred to post-launch based on feedback:**
- Cohort reassignment workflow (Story 27)
- Cohort cloning (Story 28)
- Bulk learner enrollment (Story 29)
- Advanced cohort analytics

**User Stories:** 27, 28, 29

---

### Parallel Activities (Throughout)
- User acceptance testing with each role
- Accessibility testing (WCAG AA)
- Performance testing and optimization
- Security audits
- Documentation and training materials
- Feedback collection and iteration

## Next Steps

1. Review and approve requirements with stakeholders
2. Review design.md for detailed technical architecture
3. Set up React development environment (AMD wrapper)
4. Create database migration scripts
5. Define custom Moodle roles and capabilities
6. Begin Sprint 1: Foundation & Role Architecture

---

## Traceability Matrix

**User Story → Functional Requirement Mapping**

| User Story | Functional Requirement | Sprint |
|------------|------------------------|--------|
| 1 - Create program with owner | FR-2.1 | 3-4 |
| 2 - Update program outcomes | FR-2.1, NFR-3.1 | 3-4 |
| 3 - Version programs | FR-2.1, NFR-3.1 | 3-4 |
| 4 - Reuse competencies | FR-2.1, NFR-3.2 | 3-4 |
| 5 - Define dependencies | FR-2.1, NFR-3.2 | 3-4 |
| 6 - Classify competencies | FR-2.4 | 3-4 |
| 7 - Order learning paths | FR-2.3 | 5-6 |
| 8 - Define focus streams | FR-2.2, FR-2.3 | 3-4, 5-6 |
| 9 - View assigned cohorts | FR-1.2, FR-1.3, FR-6.2 | 7-8 |
| 10 - View learning paths (read-only) | FR-1.2, FR-6.2 | 7-8 |
| 11 - Mark attendance | FR-6.2 | 7-8 |
| 12 - Review submissions | FR-6.2 | 7-8 |
| 13 - View enrolled programs | FR-1.3, FR-6.3 | 7-8 |
| 14 - Complete activities | FR-6.3 | 7-8 |
| 15 - Submit assessments | FR-6.3 | 7-8 |
| 16 - View competency progress | FR-1.2, FR-6.3 | 7-8 |
| 17 - View org-wide metrics | FR-1.3, FR-6.4 | 11 |
| 18 - No learner rankings | FR-1.2, FR-6.4, NFR-2.1 | 11 |
| 19 - Manage users | FR-1.3 | 1-2 |
| 20 - Role-based access | FR-1.1, FR-1.2, NFR-2.1 | 1-2 |
| 21 - Performance | FR-1.3, NFR-1.1 | 12 |
| 22 - Context permissions | FR-1.2, NFR-2.1 | 1-2 |
| 23 - Audit trail | NFR-2.2 | 1-2 |
| 24 - Program insights | FR-6.4 | 11 |
| 25 - Competency reports | FR-2.4, FR-6.4 | 11 |
| 26 - Program performance | FR-6.4 | 11 |
| 27 - Reassign cohorts | NFR-3.2 | Post-launch |
| 28 - Clone cohorts | (Future) | Post-launch |
| 29 - Bulk enrollment | (Future) | Post-launch |
| 30 - Reusable content | FR-3.1 | 9-10 |
| 31 - Version content | FR-3.1, NFR-3.1 | 9-10 |
| 32 - Create assessments | FR-3.1 | 9-10 |
| 33 - AI generate assessments | FR-3.2 | 9-10 |
| 34 - Learner feedback | FR-6.3 | 7-8 |
| 35 - AI transform content | FR-3.2 | 9-10 |
| 36 - AI suggest reinforcement | FR-3.2 | 9-10 |
| 37 - WhatsApp content | FR-7.2 | 12 |
| 38 - WhatsApp reminders | FR-7.2 | 12 |
| 39 - Fast reports | FR-6.4, NFR-1.1 | 11 |
| 40 - Compliance tracking | FR-6.4 | 11 |

**Coverage:** 40/40 user stories mapped (100%)
