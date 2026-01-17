# Implementation Plan: Competency-Based Learning Management System

## Overview

This implementation plan focuses on configuring and extending Moodle's existing competency framework and plugin ecosystem to create a comprehensive competency-based learning platform. The approach prioritizes using proven Moodle plugins and core functionality over custom development, ensuring maintainability and compatibility.

## Tasks

- [x] 1. Environment Setup and Core Configuration
  - Set up Moodle development environment with required plugins
  - Configure Docker containers for development and testing
  - Install and configure core competency framework
  - _Requirements: 11.1, 11.2, 11.3_

- [-] 2. Plugin Installation and Configuration
  - [x] 2.1 Enable core competency and learning plans
    - Enable Moodle's built-in competency framework
    - Enable Moodle's built-in learning plans functionality
    - Configure Configurable Reports plugin for analytics
    - _Requirements: 2.1, 3.1, 9.1_

  - [x] 2.2 Write integration tests for plugin compatibility
    - Test competency framework with learning plans integration
    - Verify configurable reports data access
    - _Requirements: 2.1, 3.1_

  - [x] 2.3 Install and configure video repository plugins
    - Install YouTube Repository plugin
    - Install Vimeo Repository plugin  
    - Configure external video embedding capabilities
    - _Requirements: 7.2_

  - [ ] 2.4 Write property test for video integration
    - **Property 13: External Content Integration**
    - **Validates: Requirements 7.2**

  - [x] 2.5 Install attendance and gamification plugins
    - Install Attendance plugin for session management
    - Install Level Up! plugin for gamification
    - Install Stash plugin for collectible rewards
    - Configure Custom Certificate plugin for credentialing
    - _Requirements: 14.1, 15.2, 16.1_

  - [x] 2.6 Install Kirkpatrick Model evaluation plugins
    - Install Feedback Activity plugin for Level 1 reaction data
    - Install Questionnaire plugin for advanced surveys
    - Install Portfolio plugin for Level 3 behavior tracking
    - Configure External Database plugin for Level 4 results integration
    - _Requirements: 17.1, 17.2, 17.3, 17.4_

  - [x] 2.7 Install ophthalmology fellowship plugins
    - Configure Database Activity plugin for case logbooks and credentialing
    - Install Scheduler plugin for rotation and mentor meeting management
    - Configure payment gateway plugins (Razorpay/Stripe/PayPal)
    - Set up custom user profile fields for trainee registration
    - _Requirements: 18.1, 19.1, 20.1, 21.1, 25.1_

- [x] 3. Competency Framework Setup
  - [x] 3.1 Create competency framework structure
    - Define competency categories and hierarchies
    - Implement prerequisite relationship management
    - Configure core vs allied competency classifications
    - _Requirements: 2.1, 2.2, 2.3_

  - [x] 3.2 Write property test for competency reusability
    - **Property 4: Competency Reusability**
    - **Validates: Requirements 2.1**

  - [x] 3.3 Write property test for circular dependency prevention
    - **Property 5: Circular Dependency Prevention**
    - **Validates: Requirements 2.2**

  - [x] 3.4 Configure competency evidence collection
    - Set up automatic evidence collection from assessments
    - Configure manual evidence submission workflows
    - Implement competency rating and approval processes
    - _Requirements: 4.6, 5.3_

- [x] 4. Program and Cohort Management
  - [x] 4.1 Implement program structure using course categories
    - Create program templates with metadata storage
    - Configure program versioning through backup/restore
    - Set up program owner role assignments
    - _Requirements: 1.1, 1.2, 1.3_

  - [x] 4.2 Write property test for program data persistence
    - **Property 1: Program Data Persistence**
    - **Validates: Requirements 1.1**

  - [x] 4.3 Write property test for version isolation
    - **Property 3: Version Isolation**
    - **Validates: Requirements 1.3**

  - [x] 4.4 Configure advanced cohort management
    - Set up cohort types (technical, management, trainer-led, self-paced)
    - Implement cohort-specific access rules and content
    - Configure mixed delivery mode support within cohorts
    - _Requirements: 4.1, 6.1_

  - [x] 4.5 Write property test for role-based access control
    - **Property 8: Role-Based Access Control**
    - **Validates: Requirements 4.1, 6.4, 11.4**

- [x] 5. Learning Path Implementation
  - [x] 5.1 Create learning plan templates
    - Design competency-based learning path templates
    - Configure prerequisite enforcement in learning paths
    - Set up automatic learner assignment to appropriate paths
    - _Requirements: 3.1, 3.2_

  - [x] 5.2 Write property test for learning path ordering
    - **Property 7: Learning Path Ordering Consistency**
    - **Validates: Requirements 3.1, 3.2**

  - [x] 5.3 Implement progress tracking and completion
    - Configure automatic progress updates from activities
    - Set up competency completion criteria and thresholds
    - Implement progress preservation during cohort changes
    - _Requirements: 5.1, 5.2, 10.1_

  - [x] 5.4 Write property test for progress preservation
    - **Property 2: Progress Preservation Under Updates**
    - **Validates: Requirements 1.2, 10.1**

- [ ] 6. Assessment and Content Management
  - [ ] 6.1 Configure competency-mapped assessments
    - Set up quiz and assignment modules with competency mapping
    - Configure rubric-based assessment aligned to competencies
    - Implement immediate feedback mechanisms
    - _Requirements: 7.5, 8.1_

  - [ ] 6.2 Implement content asset management
    - Configure reusable content through activity templates
    - Set up content sharing across multiple programs
    - Implement content versioning through backup/restore
    - _Requirements: 7.1, 7.3, 7.4_

  - [ ] 6.3 Write property test for feedback visibility workflow
    - **Property 12: Feedback Visibility Workflow**
    - **Validates: Requirements 5.3**

- [ ] 7. Attendance Tracking and Session Management
  - [ ] 7.1 Configure attendance tracking system
    - Set up Attendance plugin for session management
    - Configure attendance status options and bulk marking
    - Create attendance report templates for compliance
    - _Requirements: 14.1, 14.2_

  - [ ] 7.2 Implement mobile attendance capabilities
    - Configure mobile-optimized attendance marking interface
    - Test attendance functionality in Moodle mobile app
    - Set up QR code attendance options for clinical environments
    - _Requirements: 14.2, 14.3_

  - [ ] 7.3 Write property test for attendance integration
    - **Property 16: Attendance-Competency Integration**
    - **Validates: Requirements 14.5, 14.6**

- [ ] 8. Digital Credentialing and Badge System
  - [ ] 8.1 Configure digital badge system
    - Set up Open Badges 2.0 compliant badge framework
    - Create basic competency-based badge criteria
    - Set up external badge sharing capabilities
    - _Requirements: 15.1, 15.3_

  - [ ] 8.2 Implement certificate generation system
    - Configure Custom Certificate plugin for professional credentials
    - Create certificate templates linked to competency achievements
    - Implement long-term credential tracking across programs
    - _Requirements: 15.2, 15.5_

  - [ ] 8.3 Write property test for credential workflow
    - **Property 17: Automated Badge Awarding**
    - **Validates: Requirements 15.1, 15.4**

- [ ] 9. Gamification and Engagement Enhancement
  - [ ] 9.1 Configure gamification system
    - Set up Level Up! plugin for XP points and progression
    - Configure Stash plugin for collectible items and rewards
    - Create visual progress indicators and achievement galleries
    - Set up optional leaderboards with privacy controls
    - _Requirements: 16.1, 16.2, 16.3, 16.4_

  - [ ] 9.2 Implement engagement tracking
    - Configure engagement metrics collection and analysis
    - Set up personalized achievement recommendations
    - Create motivation-enhancing features while maintaining educational focus
    - _Requirements: 16.5, 16.6_

  - [ ] 9.3 Write unit tests for gamification features
    - Test XP point calculation and level progression
    - Test badge unlocking and reward distribution
    - Test leaderboard privacy and engagement metrics
    - _Requirements: 16.1, 16.3, 16.4_

- [ ] 10. Kirkpatrick Model Training Evaluation System
  - [ ] 10.1 Configure Level 1 (Reaction) data collection
    - Set up Feedback Activity for post-session satisfaction surveys
    - Configure engagement metrics tracking and analytics
    - Create satisfaction dashboards and real-time alerts
    - Implement qualitative feedback collection systems
    - _Requirements: 17.1_

  - [ ] 10.2 Implement Level 2 (Learning) assessment framework
    - Configure pre/post assessment comparison systems
    - Set up competency-based learning measurement
    - Integrate badge system with learning verification
    - Create detailed learning analytics and progress visualization
    - _Requirements: 17.2_

  - [ ] 10.3 Configure Level 3 (Behavior) application tracking
    - Set up Portfolio plugin for evidence collection
    - Implement follow-up survey systems for behavior monitoring
    - Configure workplace performance data integration
    - Create longitudinal tracking capabilities
    - _Requirements: 17.3_

  - [ ] 10.4 Configure Kirkpatrick reporting with Configurable Reports plugin
    - Create Level 1 reaction reports (satisfaction, engagement)
    - Create Level 2 learning reports (competency achievement, assessments)
    - Create Level 3 behavior reports (portfolio evidence, follow-ups)
    - Create Level 4 results reports (organizational metrics)
    - Test report generation and data accuracy
    - _Requirements: 17.1, 17.2, 17.3, 17.4_
    - _Effort: 1 week_

  - [ ] 10.5 Develop Level 4 (Results) external database integration plugin (CUSTOM DEV - OPTIONAL)
    - Create scheduled tasks for hospital system data synchronization
    - Build data normalization layer for external systems
    - Implement learner-outcome correlation engine
    - Develop ROI calculation and impact measurement systems
    - Create executive-level dashboards for organizational metrics
    - _Requirements: 17.4_
    - _Effort: 3-4 weeks_
    - _Priority: Nice-to-Have_

  - [ ] 10.6 Develop unified Kirkpatrick dashboard (CUSTOM DEV)
    - Build unified data aggregation layer across all four levels
    - Create interactive visualization components
    - Implement drill-down from organizational to individual level
    - Develop comparative analysis across programs, cohorts, and time periods
    - Add export capabilities for stakeholders and accreditation
    - Configure automated reporting for stakeholders
    - _Requirements: 17.5, 17.6_
    - _Effort: 2-3 weeks_
    - _Priority: Must-Have_
    - _Note: Builds on reports from Task 10.4_

  - [ ] 10.7 Write property test for Kirkpatrick data integration
    - **Property 18: Kirkpatrick Data Consistency**
    - **Validates: Requirements 17.1, 17.2, 17.3, 17.4**

- [ ] 11. Ophthalmology Fellowship Features
  - [ ] 11.1 Create Database Activity templates (CONFIGURATION)
    - Create Case Logbook template with subspecialty fields and approval workflow
    - Create Credentialing Sheet template with procedure counts and competency tracking
    - Create Research Publications template with metadata fields and mentor review
    - Export templates for easy deployment
    - Document template import procedures for admins
    - Create admin training materials for template usage
    - _Requirements: 18.1, 19.1, 25.1_
    - _Effort: 1 week_
    - _Priority: Must-Have_

  - [ ] 11.2 Configure case and surgical logbook system
    - Import Case Logbook Database Activity template
    - Configure monthly submission workflow with mentor approval
    - Create surgical exposure analytics and reporting templates
    - Integrate logbook with competency framework
    - _Requirements: 18.1, 18.2, 18.3, 18.4_

  - [ ] 11.3 Implement credentialing sheet management
    - Import Credentialing Sheet Database Activity template
    - Set up mentor verification and approval workflow
    - Create credentialing report templates and PDF export
    - Implement competency progression tracking
    - _Requirements: 19.1, 19.2, 19.4, 19.5_

  - [ ] 11.4 Configure rotation and roster management
    - Set up Scheduler plugin for five roster types
    - Implement bulk Excel/CSV upload for monthly rosters
    - Configure calendar visualization with color-coding
    - Set up automated 48-hour reminders
    - Implement conflict detection and capacity management
    - _Requirements: 20.1, 20.2, 20.3, 20.4, 20.6_

  - [ ] 11.5 Implement registration and onboarding system
    - Configure custom user profile fields for trainee data
    - Set up separate workflows for long-term and short-term registration
    - Integrate payment gateway for registration fees
    - Create induction schedule and onboarding checklist
    - Implement automated alumni transition on exit
    - _Requirements: 21.1, 21.2, 21.4, 21.5, 21.7_

  - [ ] 11.6 Configure subspecialty organization
    - Create course categories for seven ophthalmology subspecialties
    - Set up subspecialty-specific competency frameworks
    - Configure subspecialty dashboards and analytics
    - Implement primary/secondary subspecialty track assignments
    - _Requirements: 22.1, 22.2, 22.3, 22.4_

  - [ ] 11.7 Implement alumni portal and lifecycle management
    - Create custom "Alumni" role with restricted permissions
    - Configure automated cohort transition on completion
    - Set up alumni dashboard with limited access
    - Implement no-dues clearance workflow
    - Configure alumni communication and notification system
    - _Requirements: 23.1, 23.2, 23.5, 23.6, 23.7_

  - [ ] 11.8 Configure enhanced mentorship system
    - Implement mentor-trainee assignment with workload balancing
    - Create structured feedback forms for case reviews
    - Configure Scheduler for one-on-one meeting booking
    - Set up automated alerts for pending approvals
    - Implement mentor effectiveness analytics
    - _Requirements: 24.1, 24.2, 24.3, 24.5, 24.6_

  - [ ] 11.9 Implement research and publications management
    - Import Research Publications Database Activity template
    - Set up mentor review workflow for research proposals
    - Create searchable research library
    - Implement research portfolio generation
    - Configure institutional research analytics
    - _Requirements: 25.1, 25.2, 25.3, 25.5, 25.6_

  - [ ] 11.10 Write unit tests for fellowship features
    - Test case logbook submission and approval workflow
    - Test roster upload and conflict detection
    - Test alumni transition automation
    - Test mentor assignment and feedback workflows
    - _Requirements: 18.3, 20.6, 21.7, 24.1_

- [ ] 12. Unified Rules Engine Development (CUSTOM DEV)
  - [ ] 12.1 Create rules engine plugin structure
    - Set up local_sceh_rules plugin directory structure
    - Create base rule evaluator and event handler classes
    - Configure database tables for rule storage
    - Set up admin configuration interface
    - _Effort: 1 week_
    - _Priority: Must-Have_

  - [ ] 12.2 Implement attendance-based competency locking
    - Create attendance rule class with threshold evaluation
    - Build event observer for attendance updates
    - Implement competency blocking logic
    - Create admin interface for attendance rule configuration
    - _Requirements: 14.5, 14.6_
    - _Effort: 1 week_
    - _Note: Cannot be achieved with core conditional access alone_

  - [ ] 12.3 Implement automated roster-to-competency progression
    - Create roster competency rule class
    - Build event observer for roster uploads and completions
    - Implement roster type to competency mapping
    - Create automatic competency evidence generation
    - Build audit trail for automated awards
    - _Requirements: 20.5_
    - _Effort: 1-2 weeks_
    - _Note: Requires custom event handling beyond core capabilities_

  - [ ] 12.4 Write unit tests for rules engine
    - Test attendance rule evaluation
    - Test roster-to-competency automation
    - _Effort: 1 week_

  - [ ] 12.5 Integration testing and documentation
    - Test rules engine with all Moodle plugins
    - Create admin documentation
    - Create user training materials
    - _Effort: 1 week_

- [ ] 13. Checkpoint - Core Functionality and Rules Engine Validation
  - Ensure all core competency and learning path functionality works
  - Verify cohort management and access controls
  - Test content and assessment integration
  - Validate attendance tracking and badge system integration
  - Verify rules engine functionality (attendance locking, badge progression, roster automation)
  - Verify Kirkpatrick evaluation data collection across all levels
  - Test ophthalmology fellowship features (logbooks, rosters, credentialing)
  - Ask the user if questions arise

- [ ] 14. AI Integration and Enhancement
  - [ ] 14.1 Develop AI microservice integration plugin (CUSTOM DEV - OPTIONAL)
    - Create local plugin for Moodle-AI communication
    - Implement REST API client for AI microservice
    - Build scheduled tasks to pull AI insights
    - Create dashboard widgets for AI recommendations
    - Implement approval workflows for AI-generated content
    - _Requirements: 8.1, 8.2, 13.3, 13.6_
    - _Effort: 1-2 weeks_
    - _Priority: Nice-to-Have_

  - [ ] 14.2 Configure AI microservice (SEPARATE PROJECT - OPTIONAL)
    - Set up external AI microservice infrastructure
    - Implement question generation engine (OpenAI/Azure OpenAI)
    - Build predictive analytics engine (Azure ML/AWS SageMaker)
    - Create learning path optimizer
    - Implement feedback clustering and sentiment analysis
    - Develop REST API for Moodle integration
    - _Requirements: 8.1, 8.2, 13.3, 13.6_
    - _Effort: 6-10 weeks (separate parallel project)_
    - _Priority: Nice-to-Have_

  - [ ] 14.3 Write property test for AI assessment workflow
    - **Property 14: AI Assessment Generation Workflow**
    - **Validates: Requirements 8.1, 8.2**

- [ ] 15. Multilingual Support and Help System
  - [ ] 15.1 Configure Hindi language support
    - Install and configure Hindi language pack
    - Set up user language preference management
    - Configure custom translation capabilities
    - _Requirements: 12.1, 12.4_

  - [ ] 15.2 Implement comprehensive help system
    - Configure interactive tours for new user onboarding
    - Set up context-sensitive help blocks
    - Create embedded video tutorial system
    - Configure built-in documentation wiki
    - _Requirements: 12.2, 12.3_

- [ ] 16. Advanced Analytics and Reporting
  - [ ] 16.1 Configure comprehensive learner analytics
    - Set up competency progression analytics with time tracking
    - Implement engagement metrics and interaction patterns
    - Configure learning path optimization analysis
    - _Requirements: 13.1, 13.5_

  - [ ] 16.2 Implement cohort comparison analytics
    - Configure cross-cohort performance analysis
    - Set up trainer effectiveness metrics
    - Implement content effectiveness analysis
    - Configure ROI analysis for training programs
    - _Requirements: 13.2, 13.4_

  - [ ] 16.3 Write unit tests for analytics calculations
    - Test competency progression calculations
    - Test engagement metric computations
    - Test cohort comparison algorithms
    - _Requirements: 13.1, 13.2_

- [ ] 17. System Administration and Data Management
  - [ ] 17.1 Implement advanced admin functions
    - Configure learner reassignment with progress preservation
    - Set up program archiving with historical data access
    - Implement audit logging for all critical operations
    - _Requirements: 10.1, 10.4, 10.5_

  - [ ] 17.2 Write property test for archive functionality
    - **Property 15: Archive Functionality**
    - **Validates: Requirements 10.4**

  - [ ] 17.3 Configure security and performance optimization
    - Implement comprehensive role-based access controls
    - Configure performance optimization for large cohorts
    - Set up automated backup and recovery procedures
    - _Requirements: 11.4, 11.1, 11.2_

- [ ] 18. Cloud Deployment Preparation
  - [ ] 18.1 Prepare Docker containers for cloud deployment
    - Optimize Docker images for Azure/AWS deployment
    - Configure environment variables for cloud services
    - Set up database migration scripts for cloud databases
    - _Requirements: 11.1, 11.3_

  - [ ] 18.2 Configure cloud storage integration
    - Set up S3/Azure Blob storage for Moodle file storage
    - Configure CDN integration for video content delivery
    - Implement backup strategies for cloud environments
    - _Requirements: 7.2, 11.1_

  - [ ] 18.3 Write integration tests for cloud deployment
    - Test container deployment procedures
    - Test database migration and backup/restore
    - Test external service integrations in cloud environment
    - _Requirements: 11.1, 11.2_

- [ ] 19. Final Integration and Testing
  - [ ] 19.1 Comprehensive system integration testing
    - Test end-to-end learner journey through competency completion
    - Verify trainer workflows and cohort management
    - Test admin analytics and reporting functionality
    - Validate Kirkpatrick evaluation data flow across all levels
    - Test ophthalmology fellowship workflows (logbooks, rosters, credentialing)
    - _Requirements: All requirements_

  - [ ] 19.2 Write property test for automatic progress persistence
    - **Property 11: Automatic Progress Persistence**
    - **Validates: Requirements 5.2**

  - [ ] 19.3 Write property test for learner dashboard accuracy
    - **Property 10: Learner Dashboard Accuracy**
    - **Validates: Requirements 5.1**

- [ ] 20. Final Checkpoint - Complete System Validation
  - Ensure all requirements are met and tested
  - Verify cloud deployment readiness
  - Validate multilingual support and help systems
  - Confirm Kirkpatrick evaluation framework functionality
  - Validate ophthalmology fellowship features
  - Ask the user if questions arise

## Notes

- Tasks marked with **(CUSTOM DEV)** require custom plugin development
- Tasks marked with **(CUSTOM DEV - OPTIONAL)** are nice-to-have features that can be deferred
- Tasks marked with **(SEPARATE PROJECT)** are external microservices developed independently
- Must-have custom development: Task 12 (rules engine), Task 10.6 (dashboard) - 5-8 weeks
- Configuration work (not custom dev): Task 11.1 (templates) - 1 week
- Nice-to-have custom development: Task 10.5 (Level 4 integration), Task 14 (AI microservice) - 4-7 weeks Moodle + 6-10 weeks AI service
- All other tasks use Moodle core and plugins (configuration-based)
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation and user feedback
- Property tests validate universal correctness properties
- Unit tests validate specific examples and edge cases
- Focus on configuration and plugin integration over custom development where possible
- Cloud deployment tasks prepare for Azure/AWS migration
- Multilingual support enhances accessibility for diverse user bases
- Database Activity templates reduce admin configuration complexity
- Rules engine consolidates all business logic in one maintainable plugin
- AI microservice keeps AI logic external and maintainable independently

## Custom Development Summary

**Phase 1 - Must-Have Custom Development** (4-6 weeks):
1. Database Activity Templates (Task 11.1) - 1 week (CONFIGURATION, not custom dev)
2. Unified Rules Engine Plugin (Task 12) - 3-5 weeks
   - Attendance-based competency locking (cannot use core conditional access)
   - Automated roster-to-competency progression (requires custom event handling)
3. Unified Kirkpatrick Dashboard (Task 10.6) - 2-3 weeks (builds on Task 10.4 reports)

**Phase 2 - Nice-to-Have Custom Development** (4-7 weeks Moodle + 6-10 weeks AI):
1. Kirkpatrick Level 4 external DB integration (Task 10.5) - 3-4 weeks
2. AI Microservice Integration Plugin (Task 14.1) - 1-2 weeks
3. AI Microservice Development (Task 14.2) - 6-10 weeks (separate parallel project)

**Architecture Principles**:
1. **No Core Modifications**: All custom code as local plugins
2. **Consolidated Logic**: Single rules engine instead of scattered plugins
3. **External AI**: Keep AI logic outside Moodle in microservices
4. **Template-Based**: Use Database Activity templates to reduce configuration complexity
5. **API-First**: Moodle as workflow + storage, external services for heavy processing

**Recommended Approach**: 
1. Complete Phase 1 custom development alongside plugin configuration (weeks 1-8)
2. Validate with users and gather feedback (weeks 9-10)
3. Decide on Phase 2 features based on budget and user needs
4. If AI features needed, develop microservice as separate parallel project (can start anytime)

**Note on Rules Engine Scope**: Multi-level badge progression and competency overrides can be achieved using Moodle's core conditional access and badge criteria features, so they are NOT included in the custom rules engine. The rules engine focuses only on features that truly require custom event handling beyond core capabilities.