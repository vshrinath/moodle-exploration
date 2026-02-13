# **User Stories and Acceptance Criteria**

This section defines the functional requirements of the system through **role-based user stories with acceptance criteria**. The intent is to translate the conceptual model into clear, testable behaviors.

### **Roles Covered**

* **Learning Design Authority (Program Owner / Learning Architect)**

   Responsible for defining program intent, competency structures, learning paths, and progression logic.

* **Trainer / Facilitator**

   Responsible for delivering learning to cohorts, supporting learners, and providing evidence and feedback against defined competencies.

* **Learner / Student**

   Responsible for engaging with learning content, completing assessments, and progressing along defined learning paths.

* **Oversight / Admin**

   Responsible for governance, visibility across programs and cohorts, compliance tracking, and access control.

### **Goals of This Section**

* Establish a shared understanding of **what each role can do** in the system

* Provide a basis for **detailed requirements, estimation, and implementation**

* Enable **evaluation of alternate solutions** against the same functional expectations

---

## **1\. Learning Design Authority (Program Owner / Learning Architect)**

### **Program Definition**

**Story 1**

As a Program Owner, I want to create a program with a clear purpose and target audience so that all learning design decisions are grounded in intent.

**Acceptance Criteria**

* A program can be created with a name, description, and intended audience.

* A program has an identifiable owner.

* A program can exist without cohorts or learners attached.

---

**Story 2**

As a Program Owner, I want to define high-level outcomes for a program so that completion can be evaluated meaningfully.

**Acceptance Criteria**

* Program outcomes can be documented and updated.

* Outcomes are visible when defining learning paths and reports.

* Updating outcomes does not affect existing learner progress.

---

**Story 3**

As a Program Owner, I want to version a program so that updates do not disrupt cohorts already in progress.

**Acceptance Criteria**

* A new version of a program can be created.

* Existing cohorts remain tied to their original version.

* New cohorts can be associated with the latest version.

---

### **Competency Definition and Mapping**

**Story 4**

As a Program Owner, I want to define competencies independently of programs so that they can be reused across learning initiatives.

**Acceptance Criteria**

* Competencies can be created and edited independently.

* Competencies are not deleted if removed from a program.

* Competencies can be referenced by multiple programs.

---

**Story 5**

As a Program Owner, I want to define relationships between competencies so that foundational capabilities are respected.

**Acceptance Criteria**

* Competencies can be linked with prerequisite relationships.

* Circular dependencies are prevented.

* Dependency changes do not retroactively invalidate completed competencies.

---

**Story 6**

As a Program Owner, I want to classify competencies as core or allied within a program context so that expectations are explicit.

**Acceptance Criteria**

* Competencies can be marked as core or allied per program or stream.

* The same competency may have different classifications in different programs.

* Core/allied classification is visible in reporting.

---

### **Learning Paths and Streams**

**Story 7**

As a Program Owner, I want to define a learning path as an ordered sequence of competencies so that learning unfolds intentionally.

**Acceptance Criteria**

* A learning path can be created and linked to a program.

* Competencies can be ordered within the path.

* Ordering respects prerequisite relationships.

---

**Story 8**

As a Program Owner, I want to define branching paths or streams so that learners can follow differentiated trajectories.

**Acceptance Criteria**

* Streams can be defined within a program.

* Streams reference subsets of competencies or distinct paths.

* Learners can be associated with a stream.

---

## **2\. Delivery and Enablement (Trainer / Facilitator)**

**Story 9**

As a Trainer, I want to view the cohorts assigned to me so that I know which learners and sessions I am responsible for.

**Acceptance Criteria**

* A trainer sees only cohorts they are assigned to.

* Cohort details include program, schedule, and learner count.

---

**Story 10**

As a Trainer, I want to view the learning path and competencies for my cohort so that delivery aligns with design intent.

**Acceptance Criteria**

* Trainers can view program learning paths in read-only mode.

* Core and allied competencies are clearly indicated.

---

**Story 11**

As a Trainer, I want to record attendance and session notes so that delivery activity is captured.

**Acceptance Criteria**

* Attendance can be recorded per session.

* Session notes are saved and visible to authorized roles.

---

**Story 12**

As a Trainer, I want to review learner submissions and provide feedback so that competency evidence is captured.

**Acceptance Criteria**

* Trainers can view submissions for assigned cohorts.

* Feedback can be recorded against submissions.

* Feedback updates learner progress where applicable.

---

## **3\. Learner / Student**

**Story 13**

As a Learner, I want to see the program and learning path I am enrolled in so that I understand what lies ahead.

**Acceptance Criteria**

* Learners can view their enrolled programs.

* Learning paths are displayed in sequence.

---

**Story 14**

As a Learner, I want to complete learning activities at my own pace so that I can progress asynchronously where allowed.

**Acceptance Criteria**

* Learners can access self-paced content.

* Progress is saved automatically.

---

**Story 15**

As a Learner, I want to submit assessments and receive feedback so that I know how I am progressing.

**Acceptance Criteria**

* Learners can submit required assessments.

* Feedback is visible once provided.

---

**Story 16**

As a Learner, I want to view my competency progress so that I understand my current state.

**Acceptance Criteria**

* Learners can view completed and pending competencies.

* Core and allied competencies are distinguishable.

---

## **4\. Oversight and Insight (Admin / Governance)**

**Story 17**

As an Admin, I want to view program-level completion and progress so that I can assess effectiveness.

**Acceptance Criteria**

* Admins can view completion rates by program.

* Data can be filtered by cohort or stream.

---

**Story 18**

As an Admin, I want to compare outcomes across cohorts so that systemic issues can be identified.

**Acceptance Criteria**

* Cohort-level comparisons are available.

* No individual learner is ranked publicly.

---

**Story 19**

As an Admin, I want to track compliance-oriented programs so that adoption can be monitored.

**Acceptance Criteria**

* Completion status is visible per required learner group.

* Non-completion can be flagged.

---

**Story 20**

As an Admin, I want role-appropriate access controls so that data visibility aligns with governance requirements.

**Acceptance Criteria**

* Permissions are enforced consistently.

* Sensitive data is visible only to authorized roles.

---

## **8\. Content and Assessment Management**

### **Content as a Reusable Asset Library**

**Story 30**

As a Program Owner, I want to create and manage learning content as reusable assets so that the same content can be referenced across multiple programs and learning paths.

**Acceptance Criteria**

* Content can be created independently of programs and paths.

* Content assets can be referenced by multiple learning paths.

* Content assets can be marked as mandatory or optional within a path.

---

**Story 31**

As a Program Owner, I want to version content so that updates do not invalidate historical learner progress or reporting.

**Acceptance Criteria**

* Content versions are maintained when updates are made.

* Existing learner records remain linked to the version they consumed.

* New references can use the latest content version.

---

### **Assessment Creation**

**Story 32**

As a Program Owner, I want to create planned assessments aligned to competencies so that learner progress can be evaluated intentionally.

**Acceptance Criteria**

* Assessments can be created independently of delivery mode.

* Assessments can be mapped to one or more competencies.

* Assessments can be placed at defined points within a learning path.

---

**Story 33**

As a Program Owner, I want the system to generate short assessments from content so that comprehension can be checked with minimal effort.

**Acceptance Criteria**

* AI-generated assessments are derived from selected content.

* Generated assessments are editable before publishing.

* Generated assessments do not auto-publish without review.

---

**Story 34**

As a Learner, I want to receive immediate feedback on short assessments so that I can understand gaps and reinforce learning.

**Acceptance Criteria**

* Feedback is shown after assessment submission.

* Feedback references relevant content or concepts.

---

## **9\. AI Enablement (Assistive)**

**Story 35**

As a Program Owner, I want AI assistance in creating and transforming content across formats so that content development is accelerated.

**Acceptance Criteria**

* Text content can be transformed into summaries or multimedia drafts.

* Generated outputs are editable and reviewable before use.

---

**Story 36**

As a Program Owner, I want AI assistance to suggest additional assessments based on learner performance so that weak areas can be reinforced.

**Acceptance Criteria**

* Performance patterns can be analyzed at cohort or program level.

* Suggested assessments are reviewed before being added to paths.

---

## **10\. Channels and Distribution (WhatsApp)**

**Story 37**

As a Program Owner, I want to push small lessons and reminders to learners via WhatsApp so that engagement is improved.

**Acceptance Criteria**

* Content snippets can be sent via WhatsApp.

* Messages link back to the platform for full context.

---

**Story 38**

As a Learner, I want to receive reminders and simple prompts on WhatsApp so that I stay aware of pending learning activities.

**Acceptance Criteria**

* Reminders are sent for incomplete activities.

* Learners can opt in or out of WhatsApp notifications.

---

## **11\. Insight Loops and Diagnosis**

**Story 39**

As an Admin, I want to identify assessment questions where many learners struggle so that content or delivery issues can be diagnosed.

**Acceptance Criteria**

* Aggregated performance is available at question or competency level.

* Individual learner identities are not exposed in aggregate views.

---

**Story 40**

As an Admin, I want to compare learner outcomes across cohorts and trainers so that systemic issues can be identified.

**Acceptance Criteria**

* Comparisons are available at cohort and trainer level.

* Comparisons emphasize trends rather than individual rankings.

---

## **This set of user stories and acceptance criteria provides a complete, role-aligned functional foundation for translating the conceptual model into a formal requirements document.**

## **5\. Non-Functional Requirements**

### **Performance**

**Story 21**

As a System Owner, I want core learner and trainer actions to perform reliably under load so that the platform remains usable at scale.

**Acceptance Criteria**

* Learner dashboards load within acceptable response times under normal and peak usage.

* Progress updates are reflected without manual refresh.

* Concurrent access by large cohorts does not degrade core workflows.

---

### **Security**

**Story 22**

As an Admin, I want role-based access controls so that users can only access data appropriate to their role.

**Acceptance Criteria**

* Access to programs, cohorts, and reports is governed by role permissions.

* Learners cannot view other learners’ personal data.

* Trainers cannot access programs or cohorts they are not assigned to.

---

### **Audit and Traceability**

**Story 23**

As an Admin, I want key learning and administrative actions to be auditable so that governance and compliance requirements are met.

**Acceptance Criteria**

* Changes to programs, competencies, and learning paths are logged.

* Learner completion and assessment events are timestamped.

* Audit logs are accessible to authorized roles.

---

## **6\. Reporting and Analytics**

**Story 24**

As an Admin, I want a consolidated view of program performance so that I can assess overall effectiveness.

**Acceptance Criteria**

* Program-level dashboards show enrollment, progress, and completion.

* Data can be filtered by cohort, stream, or time period.

---

**Story 25**

As an Admin, I want to analyze competency-level progress so that gaps and strengths can be identified.

**Acceptance Criteria**

* Reports show completion status for core and allied competencies.

* Aggregated views do not expose individual learner rankings.

---

**Story 26**

As a Program Owner, I want insight into where learners slow down or drop off so that learning paths can be improved.

**Acceptance Criteria**

* Drop-off points along learning paths are visible.

* Data is aggregated at cohort or program level.

---

## **7\. Edge Cases and Exception Handling**

**Story 27**

As an Admin, I want to reassign learners between cohorts without losing progress so that operational changes can be handled smoothly.

**Acceptance Criteria**

* Learner progress is preserved when cohort assignment changes.  
* Historical cohort data remains intact.

---

**Story 28**

As an Admin, I want to handle incomplete or abandoned programs so that reporting remains accurate.

**Acceptance Criteria**

* Learners can be marked as inactive or withdrawn.  
* Inactive learners are excluded from completion metrics where appropriate.

---

**Story 29**

As a Program Owner, I want to retire or archive programs and competencies so that obsolete content does not affect active learning.

**Acceptance Criteria**

* Archived programs are no longer available for new cohorts.  
* Historical data remains accessible for reporting.

---

