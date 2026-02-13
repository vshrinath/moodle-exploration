# **Role Architecture, Streams & Learning Paths**

## **Learning System Philosophy**

This platform is intentionally designed around **three distinct layers of responsibility**. These layers are foundational and should not be collapsed, even if roles are merged operationally in early phases.

1. **Learning design authority**  
   *What should be learned, in what sequence, to what standard*  
   Owns curriculum intent, competency frameworks, learning paths, and completion criteria.  
2. **Delivery & enablement**  
   *Who runs cohorts, sessions, and assessments*  
   Owns execution quality, learner experience at cohort level, and day-to-day teaching effectiveness.  
3. **Oversight & insight**  
   *How well the system is working overall*  
   Owns cross-program visibility, quality assurance, trend analysis, and continuous improvement.

These layers provide the conceptual spine for all roles, entities, permissions, and reports defined below.

### **Mapping Roles to Layers of Responsibility**

| Role | Learning Design Authority | Delivery & Enablement | Oversight & Insight |
| ----- | ----- | ----- | ----- |
| **System / Org Admin** | ◐ (policy, governance) | ◐ (enables operations) | ● (primary owner) |
| **Program Owner / Learning Architect** | ● (primary owner) | ◐ (supports delivery design) | ◐ (program-level insight) |
| **Trainer / Facilitator** | – | ● (primary owner) | ◐ (feeds execution data) |
| **Trainer Coach / Faculty Lead** | ◐ (teaching standards) | ● (trainer enablement) | ◐ (quality insights) |
| **Student / Learner** | – | ◐ (participates) | – |

**Legend:** ● Primary ownership · ◐ Contributing responsibility · – Not applicable

This mapping clarifies that roles may span multiple layers, but each layer has a clear primary owner. No single role should dominate all three layers simultaneously.

---

---

# **Role Architecture, Streams & Learning Paths**

This section clarifies **roles, responsibilities, and structural distinctions** in the LMS. The goal is to avoid role ambiguity, support scale, and enable clean PRD-to-user-story translation.

---

## **1\. Core Roles (Who Logs In)**

### **1\. System / Org Admin**

**Purpose:** Platform governance and cross-program oversight

**Responsibilities**

* Manage users and roles  
* View organization-wide reports  
* Monitor program health and outcomes  
* Manage announcements and support workflows

**Owns**

* System configuration  
* Governance  
* Cross-program insights

---

### **2\. Program Owner / Learning Architect**

**Purpose:** Define *what* is taught and *why*

**Responsibilities**

* Create and version programs  
* Define competency frameworks  
* Define focus streams  
* Design learning paths and sequencing  
* Define assessments and completion criteria

**Owns**

* Curriculum intent  
* Program structure  
* Academic integrity

---

### **3\. Trainer / Facilitator (Delivery Role)**

*(This is what the existing system currently labels as “Teacher”)*

**Purpose:** Execute learning delivery at cohort level

**Responsibilities**

* Run assigned cohorts  
* Deliver sessions  
* Mark attendance  
* Evaluate submissions against predefined criteria  
* Provide learner feedback

**Does NOT**

* Create programs  
* Define competencies  
* Design learning paths  
* Enroll learners

**Owns**

* Execution quality  
* Cohort experience

---

### **4\. Trainer Coach / Faculty Lead (Teacher of Trainers)**

**Purpose:** Ensure trainer quality and pedagogical consistency

**Responsibilities**

* Onboard and certify trainers  
* Coach trainers on delivery quality  
* Review session feedback and ratings  
* Improve teaching playbooks

**Scope**

* Cross-cohort  
* Cross-program

**Owns**

* Teaching standards  
* Trainer capability

---

### **5\. Student / Learner**

**Purpose:** Progress through learning pathways and demonstrate competency

**Responsibilities**

* Participate in cohorts  
* Consume learning content  
* Submit assessments  
* Track progress and feedback

---

## **2\. Structural Distinctions (How Learning Is Organized)**

### **A. Cohort**

* Operational grouping  
* Time-bound  
* Used for delivery, sessions, attendance

A cohort defines *how* learning is delivered.

---

### **B. Focus Stream**

* Conceptual grouping within a program  
* Represents specialization or pathway choice  
* May cut across cohorts

Examples:

* Common Foundation  
* Domain A  
* Domain B

A learner may start in a common stream and later branch into focus streams.

---

### **C. Learning Path**

* Ordered sequence of learning experiences  
* Defined by Program Owner  
* Can include common and stream-specific modules  
* Supports progression rules and gates

A learning path defines *what* unfolds over time.

---

## **3\. Concrete Examples**

### **Example 1: Single Program with Common \+ Focus Streams**

**Program:** Advanced Operations Program (AOP)

**Structure**

* Common Foundation Stream  
  * Personal grooming  
  * Communication Basics  
* Focus Streams  
  * Patient Experience  
  * Clinical assistance

**Learning Path**

1. Common Foundation (mandatory)  
2. Stream Selection  
3. Stream-Specific Modules  
4. Capstone Assessment

**Roles in Action**

* Program Owner defines streams and sequencing  
* Trainers deliver cohorts for each stream  
* Trainer Coach reviews delivery quality  
* Admin compares outcomes across streams

---

### **Example 2: Trainer Coaching Across Multiple Cohorts**

**Context**

* Same program run across 5 cohorts  
* 8 trainers involved

**Trainer Coach Role**

* Reviews session ratings across cohorts  
* Identifies variance in learner feedback  
* Runs monthly trainer enablement sessions

**Outcome**

* Improved consistency in delivery  
* Reduced learner drop-off

---

### **Example 3: Learner Progression Across Streams**

**Learner Journey**

1. Enrolled into Cohort A (Common Stream)  
2. Completes foundational competencies  
3. Branches into Domain B focus stream  
4. Assigned to Cohort C for advanced modules

**Key Insight**

* Cohorts change, learning path continuity remains

---

## **4\. Why These Distinctions Matter**

* Prevents role confusion (trainer ≠ curriculum designer)  
* Enables scale without quality loss  
* Allows system-level insights without conflating causes  
* Supports multiple learning pathways without duplication

---

This section should be treated as **foundational** for all subsequent PRD modules and user stories.

---

## **5\. Applicability Across Program Types**

The role architecture and responsibility layers defined above are intentionally **agnostic to delivery format**. The same structure holds across instructor-led, self-paced, and hybrid programs. The following use cases illustrate how the system accommodates different training contexts without requiring structural changes.

---

### **Use Case 1: Self-Paced Middle Management Program**

**Context**  
A middle-management program is rolled out as a self-learning experience with modular content and assessments. There are no live cohorts or facilitators involved.

**How the system holds**

* **Learning design authority** defines the program structure, learning path, and assessment criteria.  
* **Delivery & enablement** is system-led through self-paced modules and assessments.  
* **Oversight & insight** provides visibility into completion, assessment outcomes, and participation patterns.

**Key note**  
The absence of a trainer does not alter the role model. Delivery responsibility exists but is fulfilled by the platform rather than a facilitator.

---

### **Use Case 2: Policy or Rule Change Across Divisions**

**Context**  
A change in HR policy or operational rules needs to be communicated and acknowledged across different divisions.

**How the system holds**

* The update is defined as a short program with a minimal learning path.  
* Divisions are represented as cohorts for rollout and tracking.  
* Assessments may take the form of acknowledgements or short checks.  
* Oversight focuses on adoption and compliance rather than skill progression.

**Key note**  
Cohorts function as an operational grouping mechanism, independent of instructional delivery.

---

### **Use Case 3: Domain-Specific Upskilling (e.g., Clinicians Using AI Tools)**

**Context**  
Clinicians are required to learn basic AI-enabled productivity practices through short modules and safety guidelines.

**How the system holds**

* Learning design authority defines scope, sequencing, and acceptable usage boundaries.  
* Delivery is self-paced and asynchronous.  
* Oversight focuses on completion, engagement, and readiness rather than performance ranking.

**Key note**  
The system supports domain-specific training where learning objectives are bounded and outcome definitions are conservative.

---

### **Use Case 4: Mixed Programs with Optional Facilitation**

**Context**  
A program includes foundational self-learning modules followed by optional facilitated sessions for select cohorts.

**How the system holds**

* A single learning path spans both self-paced and facilitated components.  
* Facilitators participate only in the delivery layer where required.  
* Oversight spans both modes without conflating delivery format with learning intent.

**Key note**  
Facilitation is treated as a mode of delivery, not a prerequisite for program definition.

---

Across these scenarios, the system remains consistent because responsibilities are anchored to layers, not to specific personas or delivery mechanics. This allows new program types to be introduced without reworking the underlying role architecture.

