# Requirements Document

## Introduction

This document defines the requirements for implementing a competency-based learning management system using Moodle as the foundation platform. The system will enable program owners to define competency frameworks, trainers to deliver structured learning, learners to progress through competency-based paths, and administrators to monitor program effectiveness.

## Glossary

- **Program**: A structured learning initiative with defined outcomes and competency requirements (implemented as Moodle courses)
- **Competency**: A specific skill, knowledge area, or capability that can be demonstrated and assessed (using Moodle Competency Framework)
- **Learning_Path**: An ordered sequence of competencies that guides learner progression (using Learning Plans plugin)
- **Stream**: A specialized track within a program that focuses on specific competency subsets
- **Cohort**: A group of learners progressing through a program together (using Moodle cohorts)
- **Core_Competency**: Essential competencies required for program completion
- **Allied_Competency**: Supporting competencies that enhance but are not required for completion
- **Assessment**: Evaluation mechanism to measure competency achievement (using Quiz and Assignment modules)
- **Content_Asset**: Reusable learning materials that can be referenced across multiple programs (using activities and resources)
- **Trainer**: Facilitator responsible for delivering learning and providing feedback (Teacher role)
- **Program_Owner**: Learning architect responsible for defining program structure and competencies (Manager/Course Creator role)
- **System**: The Moodle-based competency learning management platform

## Recommended Moodle Plugins

### Core Functionality (Built-in)
- **Competency Framework** - Core competency definition and tracking
- **Completion Tracking** - Activity and course completion
- **YouTube Repository** - Direct YouTube video embedding
- **Cohorts** - Learner group management
- **Gradebook** - Assessment and progress tracking
- **Badges System** - Open Badges 2.0 compliant digital badges

### Additional Plugins Required
- **Learning Plans Plugin** - Structured competency-based learning paths
- **Vimeo Repository Plugin** - Vimeo video integration
- **Configurable Reports Plugin** - Advanced analytics and reporting
- **Custom Certificate Plugin** - Competency-based certification
- **Competency-based Cohort Assignment** - Automatic cohort progression
- **Attendance Plugin** - Session attendance tracking and management
- **Level Up! Plugin** - Gamification with XP points and leveling
- **Stash Plugin** - Collectible items and engagement rewards
- **Feedback Activity Plugin** - Kirkpatrick Level 1 satisfaction surveys
- **Questionnaire Plugin** - Advanced survey capabilities for evaluation
- **Portfolio Plugin** - Evidence collection and case logbook management
- **External Database Plugin** - Integration with hospital systems for Level 4 data
- **Database Activity Plugin** - Structured data collection for credentialing sheets and research tracking
- **Scheduler Plugin** - Rotation scheduling and mentor meeting management
- **Payment Gateway Plugins** - Registration fee collection (Razorpay/Stripe/PayPal)

## Requirements

### Requirement 1: Program Management

**User Story:** As a Program Owner, I want to create and manage learning programs with clear structure and versioning, so that learning initiatives can be systematically designed and maintained.

#### Acceptance Criteria

1. WHEN a Program Owner creates a program, THE System SHALL store the program name, description, intended audience, and owner information
2. WHEN a Program Owner defines program outcomes, THE System SHALL allow documentation and updates without affecting existing learner progress
3. WHEN a Program Owner creates a new program version, THE System SHALL maintain existing cohorts on their original version while allowing new cohorts to use the latest version
4. THE System SHALL allow programs to exist without enrolled cohorts or learners

### Requirement 2: Competency Framework Management

**User Story:** As a Program Owner, I want to define and manage competencies with relationships and classifications using Moodle's built-in competency framework, so that learning can be structured around measurable capabilities.

#### Acceptance Criteria

1. WHEN a Program Owner creates competencies, THE System SHALL use Moodle's competency framework to store them independently of specific courses for reuse across programs
2. WHEN a Program Owner defines competency prerequisites, THE System SHALL use competency framework relationships and prevent circular dependencies
3. WHEN a Program Owner classifies competencies, THE System SHALL use competency framework contexts to mark as core or allied within specific program contexts
4. WHEN a competency is removed from a program, THE System SHALL preserve the competency definition in the framework for other programs
5. THE System SHALL allow the same competency to have different evidence requirements in different program contexts

### Requirement 3: Learning Path and Stream Definition

**User Story:** As a Program Owner, I want to create structured learning paths with branching options, so that learners can follow intentional and differentiated learning trajectories.

#### Acceptance Criteria

1. WHEN a Program Owner creates a learning path, THE System SHALL allow ordering of competencies within the path
2. WHEN competencies are ordered, THE System SHALL respect existing prerequisite relationships
3. WHEN a Program Owner defines streams, THE System SHALL allow association of learners with specific streams
4. THE System SHALL allow streams to reference subsets of competencies or distinct learning paths

### Requirement 4: Trainer Delivery Support

**User Story:** As a Trainer, I want to manage my assigned cohorts and track learning delivery, so that I can effectively support learner progression.

#### Acceptance Criteria

1. WHEN a Trainer accesses the system, THE System SHALL display only cohorts assigned to that trainer
2. WHEN a Trainer views cohort details, THE System SHALL show program information, schedule, and learner count
3. WHEN a Trainer views learning paths, THE System SHALL display competencies in read-only mode with core and allied classifications clearly indicated
4. WHEN a Trainer records attendance, THE System SHALL save attendance data per session
5. WHEN a Trainer adds session notes, THE System SHALL store notes and make them visible to authorized roles
6. WHEN a Trainer reviews submissions, THE System SHALL allow feedback recording that updates learner progress

### Requirement 5: Learner Experience

**User Story:** As a Learner, I want to understand my learning journey and progress at my own pace, so that I can effectively work toward competency achievement.

#### Acceptance Criteria

1. WHEN a Learner accesses their dashboard, THE System SHALL display enrolled programs and learning paths in sequence
2. WHEN a Learner accesses self-paced content, THE System SHALL save progress automatically
3. WHEN a Learner submits assessments, THE System SHALL make feedback visible once provided by trainers
4. WHEN a Learner views competency progress, THE System SHALL distinguish between completed and pending competencies
5. THE System SHALL clearly indicate core and allied competency classifications to learners

### Requirement 6: Administrative Oversight

**User Story:** As an Admin, I want comprehensive program monitoring and role-based access controls, so that I can ensure program effectiveness and data governance.

#### Acceptance Criteria

1. WHEN an Admin views program dashboards, THE System SHALL display completion rates filterable by program, cohort, or stream
2. WHEN an Admin compares cohorts, THE System SHALL provide cohort-level comparisons without exposing individual learner rankings
3. WHEN an Admin tracks compliance programs, THE System SHALL show completion status per required learner group and flag non-completion
4. THE System SHALL enforce role-based permissions consistently across all data access
5. THE System SHALL ensure sensitive data is visible only to authorized roles

### Requirement 7: Content and Assessment Management

**User Story:** As a Program Owner, I want to create reusable content and assessments with external video integration, so that learning materials can be efficiently managed without complex hosting infrastructure.

#### Acceptance Criteria

1. WHEN a Program Owner creates content, THE System SHALL store it as reusable assets independent of specific programs using Moodle's activity and resource system
2. WHEN adding video content, THE System SHALL support embedding from YouTube and Vimeo through repository plugins
3. WHEN content is referenced, THE System SHALL allow the same content to be used across multiple learning paths through course templates
4. WHEN content is updated, THE System SHALL maintain version history through Moodle's backup and restore functionality
5. WHEN a Program Owner creates assessments, THE System SHALL allow mapping to competencies using Moodle's competency framework
6. THE System SHALL allow assessments to be placed at defined points within learning paths
7. THE System SHALL allow content assets to be marked as mandatory or optional within specific paths using completion criteria

### Requirement 8: AI-Enhanced Assessment Generation

**User Story:** As a Program Owner, I want AI assistance in creating assessments and analyzing performance, so that assessment development is accelerated and learning gaps can be identified.

#### Acceptance Criteria

1. WHEN a Program Owner requests AI-generated assessments, THE System SHALL derive assessments from selected content
2. WHEN AI generates assessments, THE System SHALL make them editable before publishing
3. THE System SHALL NOT auto-publish AI-generated assessments without Program Owner review
4. WHEN analyzing performance patterns, THE System SHALL provide insights at cohort or program level
5. WHEN AI suggests additional assessments, THE System SHALL require Program Owner review before adding to learning paths

### Requirement 9: Advanced Reporting and Analytics

**User Story:** As an Admin, I want detailed analytics on program performance and learner progress, so that I can identify improvement opportunities and systemic issues.

#### Acceptance Criteria

1. WHEN an Admin accesses program analytics, THE System SHALL show enrollment, progress, and completion data filterable by time period
2. WHEN analyzing competency progress, THE System SHALL display completion status for core and allied competencies without individual learner rankings
3. WHEN identifying learning bottlenecks, THE System SHALL show drop-off points aggregated at cohort or program level
4. WHEN analyzing assessment performance, THE System SHALL provide question-level and competency-level aggregated performance data
5. THE System SHALL compare learner outcomes across cohorts and trainers while emphasizing trends rather than individual rankings

### Requirement 10: System Administration and Data Management

**User Story:** As an Admin, I want robust data management capabilities and audit trails, so that the system can handle operational changes and maintain compliance.

#### Acceptance Criteria

1. WHEN reassigning learners between cohorts, THE System SHALL preserve learner progress and maintain historical cohort data
2. WHEN handling incomplete programs, THE System SHALL allow learners to be marked as inactive or withdrawn
3. WHEN calculating completion metrics, THE System SHALL exclude inactive learners where appropriate
4. WHEN archiving programs, THE System SHALL prevent new cohort enrollment while maintaining historical data access
5. THE System SHALL log all changes to programs, competencies, and learning paths with timestamps
6. THE System SHALL make audit logs accessible to authorized roles
7. THE System SHALL ensure learner completion and assessment events are timestamped and auditable

### Requirement 11: Performance and Security

**User Story:** As a System Owner, I want the platform to perform reliably under load with appropriate security measures, so that users can depend on the system for critical learning activities.

#### Acceptance Criteria

1. WHEN learners access dashboards under normal and peak usage, THE System SHALL load within acceptable response times
2. WHEN progress is updated, THE System SHALL reflect changes without requiring manual refresh
3. WHEN large cohorts access the system concurrently, THE System SHALL maintain core workflow performance
4. THE System SHALL enforce role-based access controls preventing unauthorized data access
5. THE System SHALL prevent learners from viewing other learners' personal data
6. THE System SHALL prevent trainers from accessing programs or cohorts they are not assigned to

### Requirement 12: Multilingual Support and User Guidance

**User Story:** As a user, I want the system to support Hindi language and provide comprehensive help resources, so that I can effectively use the platform regardless of my language preference or technical expertise.

#### Acceptance Criteria

1. WHEN a user selects Hindi as their language preference, THE System SHALL display all interface elements and content in Hindi
2. WHEN a user accesses help resources, THE System SHALL provide context-sensitive guidance appropriate to their current role and page
3. WHEN a new user first accesses the system, THE System SHALL offer interactive tours to guide them through key features
4. THE System SHALL support custom translations for organization-specific terminology and competency descriptions
5. WHEN AI generates content, THE System SHALL support translation to the user's preferred language

### Requirement 13: Advanced Analytics and Learner Insights

**User Story:** As an Admin and Program Owner, I want comprehensive analytics on learner behavior and program effectiveness, so that I can optimize learning outcomes and identify improvement opportunities.

#### Acceptance Criteria

1. WHEN analyzing learner progress, THE System SHALL provide competency-level analytics including time-to-completion and difficulty patterns
2. WHEN comparing cohorts, THE System SHALL show performance differences while maintaining learner privacy
3. WHEN identifying at-risk learners, THE System SHALL use predictive analytics to provide early warnings
4. WHEN evaluating content effectiveness, THE System SHALL analyze which materials work best for different learner demographics
5. THE System SHALL provide engagement metrics including time spent, interaction patterns, and content preferences
6. WHEN generating insights, THE System SHALL offer actionable recommendations for learning path optimization

### Requirement 14: Attendance Tracking and Session Management

**User Story:** As a Trainer, I want to track learner attendance at sessions and link attendance to competency progression, so that I can ensure compliance with training requirements and monitor engagement.

#### Acceptance Criteria

1. WHEN creating training sessions, THE System SHALL allow trainers to set up attendance tracking for face-to-face and virtual sessions
2. WHEN marking attendance, THE System SHALL provide quick interfaces for bulk attendance marking with multiple status options (Present, Late, Excused, Absent)
3. WHEN learners access their records, THE System SHALL display their attendance history and statistics
4. WHEN generating reports, THE System SHALL provide attendance analytics at individual, cohort, and program levels
5. THE System SHALL integrate attendance data with competency progression and grading systems
6. WHEN minimum attendance requirements exist, THE System SHALL prevent competency advancement until requirements are met

### Requirement 15: Digital Credentialing and Badge System

**User Story:** As a Learner, I want to earn digital badges and credentials for competency achievements, so that I can demonstrate my skills and track my professional development over extended training periods.

#### Acceptance Criteria

1. WHEN learners complete competencies, THE System SHALL automatically award appropriate digital badges using Open Badges 2.0 standards
2. WHEN learners achieve milestones, THE System SHALL generate professional PDF certificates linked to specific competency achievements
3. WHEN badges are earned, THE System SHALL allow learners to share credentials on external platforms like LinkedIn and professional portfolios
4. THE System SHALL support multi-level badge progression (Bronze, Silver, Gold) for advanced competency demonstration
5. THE System SHALL support long-term credential tracking across multi-month training programs

### Requirement 16: Gamification and Engagement Enhancement

**User Story:** As a Learner, I want engaging gamification features that motivate continued learning, so that I remain motivated throughout extended training programs.

#### Acceptance Criteria

1. WHEN learners complete activities, THE System SHALL award experience points and track progression through competency levels
2. WHEN learners achieve milestones, THE System SHALL unlock new content, badges, and learning opportunities
3. WHEN viewing progress, THE System SHALL display visual progress bars, achievement galleries, and competency completion status
4. THE System SHALL provide optional leaderboards and peer comparison features while maintaining privacy controls
5. WHEN learners engage with content, THE System SHALL track engagement metrics and provide personalized achievement recommendations
6. THE System SHALL support collectible items and rewards that enhance the learning experience without compromising educational objectives

### Requirement 17: Kirkpatrick Model Training Evaluation

**User Story:** As an Admin and Program Owner, I want comprehensive training evaluation using the Kirkpatrick Model, so that I can measure training effectiveness from learner satisfaction through organizational impact.

#### Acceptance Criteria

1. WHEN learners complete training sessions, THE System SHALL collect Level 1 (Reaction) data through satisfaction surveys, engagement metrics, and feedback forms
2. WHEN learners complete assessments, THE System SHALL measure Level 2 (Learning) through competency achievements, pre/post assessments, and skill demonstrations
3. WHEN tracking post-training performance, THE System SHALL collect Level 3 (Behavior) data through portfolio evidence, follow-up surveys, and workplace application tracking
4. WHEN measuring organizational impact, THE System SHALL integrate Level 4 (Results) data from external systems including patient outcomes, cost metrics, and quality indicators
5. THE System SHALL provide integrated Kirkpatrick dashboards showing all four evaluation levels with drill-down capabilities
6. WHEN generating reports, THE System SHALL calculate ROI metrics and provide comparative analysis across training programs and time periods

### Requirement 18: Case and Surgical Logbook Management

**User Story:** As a Learner, I want to maintain a digital logbook of patient cases and surgical procedures, so that I can track my clinical experience and receive mentor feedback on my performance.

#### Acceptance Criteria

1. WHEN recording cases, THE System SHALL allow learners to log patient cases with procedure details, outcomes, and complications
2. WHEN categorizing cases, THE System SHALL support subspecialty classification including cataract, retina, cornea, glaucoma, oculoplasty, pediatric, and neuro-ophthalmology
3. WHEN submitting logbooks, THE System SHALL require monthly submission with mentor approval workflow
4. WHEN mentors review logbooks, THE System SHALL provide structured feedback forms for case handling and surgical performance
5. THE System SHALL generate analytics on surgical exposure, case volume by subspecialty, and skill progression
6. WHEN exporting logbooks, THE System SHALL provide formatted reports for accreditation and certification purposes

### Requirement 19: Credentialing Sheet and Performance Documentation

**User Story:** As a Learner, I want to submit monthly credentialing sheets documenting my surgical procedures and competencies, so that my training progress is formally tracked and verified.

#### Acceptance Criteria

1. WHEN submitting credentialing sheets, THE System SHALL accept monthly submissions with procedure counts and competency achievements
2. WHEN mentors review credentials, THE System SHALL provide approval workflow with competency verification
3. THE System SHALL maintain historical credentialing data for the entire training duration
4. WHEN generating reports, THE System SHALL provide credentialing summaries for accreditation bodies
5. THE System SHALL track competency progression over time with visual analytics
6. WHEN exporting credentials, THE System SHALL generate professional PDF certificates and documentation

### Requirement 20: Rotation and Roster Management

**User Story:** As an Admin, I want to manage trainee rotations and duty rosters across departments, so that training schedules are organized and trainees receive proper notifications.

#### Acceptance Criteria

1. WHEN creating rosters, THE System SHALL support five roster types: morning class, night duty, training OT, satellite visits, and postings schedule
2. WHEN uploading rosters, THE System SHALL accept monthly bulk uploads by administrators with Excel/CSV import
3. WHEN viewing schedules, THE System SHALL display rosters in calendar format with color-coding by rotation type
4. THE System SHALL send automated reminders to trainees 48 hours before scheduled duties
5. WHEN managing rotations, THE System SHALL track rotation completion, attendance, and competency achievement per rotation
6. THE System SHALL detect and alert on scheduling conflicts and capacity violations
7. WHEN trainees access schedules, THE System SHALL provide mobile-optimized views and calendar export capabilities

### Requirement 21: Registration and Onboarding System

**User Story:** As an Admin, I want to manage the complete registration and onboarding process for both long-term and short-term trainees, so that enrollment is streamlined and all required information is captured.

#### Acceptance Criteria

1. WHEN applicants register, THE System SHALL support separate workflows for long-term fellowship and short-term training registration
2. WHEN collecting trainee data, THE System SHALL capture comprehensive profile information including demographics, education, family details, and emergency contacts
3. WHEN processing applications, THE System SHALL support interview scheduling, specialty selection, and application status tracking
4. THE System SHALL integrate with payment gateways for registration fee collection with automated confirmation
5. WHEN onboarding trainees, THE System SHALL provide induction schedules, joining formalities checklist, and document upload capabilities
6. THE System SHALL track training duration, extensions, dropout reasons, and exit procedures
7. WHEN trainees exit, THE System SHALL automatically transition them to alumni status with appropriate access restrictions

### Requirement 22: Subspecialty Organization and Management

**User Story:** As a Program Owner, I want to organize training content and competencies by ophthalmology subspecialties, so that trainees can follow specialized learning paths.

#### Acceptance Criteria

1. WHEN organizing content, THE System SHALL support subspecialty categories: cataract, retina, cornea, glaucoma, oculoplasty, pediatric ophthalmology, and neuro-ophthalmology
2. WHEN defining competencies, THE System SHALL allow subspecialty-specific competency frameworks with unique skill requirements
3. WHEN tracking progress, THE System SHALL provide subspecialty-specific dashboards showing competency achievement and case volume
4. THE System SHALL allow trainees to be assigned to primary and secondary subspecialty tracks
5. WHEN generating reports, THE System SHALL provide subspecialty-level analytics on trainee performance and program effectiveness
6. THE System SHALL support subspecialty-specific content libraries, assessments, and learning resources

### Requirement 23: Alumni Portal and Lifecycle Management

**User Story:** As an Admin, I want to manage the complete trainee lifecycle from registration through alumni status, so that former trainees remain engaged and can access relevant services.

#### Acceptance Criteria

1. WHEN trainees complete training, THE System SHALL automatically transition them to alumni status with modified access permissions
2. WHEN alumni access the system, THE System SHALL provide limited dashboard with short-term training applications and communication features
3. THE System SHALL allow alumni to apply for short-term training courses and workshops
4. WHEN alumni need consultations, THE System SHALL provide tele-consultation and second opinion case posting capabilities
5. THE System SHALL send alumni notifications for events, courses, and institutional updates
6. WHEN processing exits, THE System SHALL enforce no-dues clearance workflow and collect exit feedback
7. THE System SHALL maintain comprehensive alumni database with follow-up tracking and career progression data

### Requirement 24: Enhanced Mentorship and Feedback System

**User Story:** As a Mentor, I want structured tools for providing feedback and tracking my assigned trainees, so that I can effectively guide their professional development.

#### Acceptance Criteria

1. WHEN assigning mentors, THE System SHALL support mentor-trainee assignment with workload balancing and specialty matching
2. WHEN providing feedback, THE System SHALL offer structured feedback forms for case reviews, surgical performance, and competency assessments
3. THE System SHALL enable scheduling of one-on-one review meetings and feedback sessions with calendar integration
4. WHEN mentors review progress, THE System SHALL provide comprehensive trainee profiles with training history, case logs, and competency status
5. THE System SHALL send alerts to mentors for pending approvals, inactive trainees, and performance concerns
6. WHEN analyzing effectiveness, THE System SHALL provide mentor analytics on feedback frequency, trainee outcomes, and mentorship quality
7. THE System SHALL support mentor recommendations for improvement areas with automated follow-up tracking

### Requirement 25: Research and Publications Management

**User Story:** As a Learner, I want to track my research projects and publications, so that my academic contributions are documented and accessible.

#### Acceptance Criteria

1. WHEN submitting research, THE System SHALL accept research project proposals with mentor review workflow
2. WHEN documenting publications, THE System SHALL capture publication metadata including title, year, journal, authors, and links
3. THE System SHALL provide a research library with searchable publications and clinical studies
4. WHEN tracking progress, THE System SHALL monitor research milestones, submission status, and publication outcomes
5. THE System SHALL generate research portfolios for individual trainees with publication lists and impact metrics
6. WHEN generating reports, THE System SHALL provide institutional research analytics and publication trends