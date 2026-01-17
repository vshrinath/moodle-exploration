# SCEH Rules Engine - Training Materials

## Administrator Training Workshop

### Session 1: Introduction and Installation (30 minutes)

#### Learning Objectives
- Understand the purpose of the rules engine
- Successfully install and configure the plugin
- Verify installation is working correctly

#### Activities

**1. Overview Presentation (10 min)**
- What problems does the rules engine solve?
- Attendance-based competency locking use cases
- Roster-to-competency automation benefits
- Architecture overview

**2. Installation Demo (10 min)**
- Upload plugin files
- Run database upgrade
- Verify tables created
- Check plugin appears in admin menu

**3. Initial Configuration (10 min)**
- Enable rules engine
- Configure attendance rules setting
- Configure roster rules setting
- Assign capabilities to test user

#### Hands-On Exercise
Participants install the plugin in their test environment and verify it appears in the admin menu.

---

### Session 2: Attendance Rules Configuration (45 minutes)

#### Learning Objectives
- Create attendance rules
- Understand threshold calculations
- Test rule evaluation
- Interpret audit log entries

#### Activities

**1. Creating First Attendance Rule (15 min)**

Step-by-step walkthrough:
1. Navigate to Attendance Rules page
2. Click "Add Attendance Rule"
3. Select competency: "Clinical Skills - Basic"
4. Select course: "Clinical Rotation 101"
5. Set threshold: 75%
6. Enable rule
7. Save

**2. Understanding Calculations (15 min)**

Example scenarios:
- Learner attends 8 of 10 sessions = 80% (passes 75% threshold)
- Learner attends 7 of 10 sessions = 70% (blocked by 75% threshold)
- Excused absences handling (depends on attendance plugin config)

**3. Testing Rules (15 min)**

Test procedure:
1. Create test learner account
2. Enroll in course with attendance tracking
3. Mark attendance for several sessions
4. Attempt to access locked competency
5. Verify block message appears
6. Check audit log for evaluation entry

#### Hands-On Exercise
Participants create an attendance rule, test it with a learner account, and verify the audit log entry.

---

### Session 3: Roster Rules Configuration (45 minutes)

#### Learning Objectives
- Create roster rules
- Map roster types to competencies
- Test automatic evidence creation
- Verify competency awards

#### Activities

**1. Creating First Roster Rule (15 min)**

Step-by-step walkthrough:
1. Navigate to Roster Rules page
2. Click "Add Roster Rule"
3. Select roster type: "Morning Class"
4. Select competency: "Attendance - Morning Sessions"
5. Enter evidence: "Completed morning class roster for [month]"
6. Enable rule
7. Save

**2. Understanding Roster Types (15 min)**

Review each roster type:
- **Morning Class**: Regular morning sessions
- **Night Duty**: Emergency/night shift coverage
- **Training OT**: Operating theatre rotations
- **Satellite Visits**: Community/satellite clinic visits
- **Postings**: Department-specific rotations

Map to competencies:
- Which roster types should award which competencies?
- How to write clear evidence descriptions
- Avoiding duplicate rules

**3. Testing Automation (15 min)**

Test procedure:
1. Create test learner account
2. Create roster entry in Scheduler
3. Mark roster as completed
4. Check learner's competency profile
5. Verify evidence was created automatically
6. Review audit log for award entry

#### Hands-On Exercise
Participants create a roster rule, simulate a roster completion, and verify automatic competency evidence creation.

---

### Session 4: Monitoring and Troubleshooting (30 minutes)

#### Learning Objectives
- Navigate and interpret audit log
- Troubleshoot common issues
- Export audit data for reporting
- Optimize rule performance

#### Activities

**1. Audit Log Navigation (10 min)**

Demonstrate:
- Accessing audit log
- Filtering by rule type
- Filtering by user
- Understanding JSON details field
- Identifying error entries

**2. Common Issues and Solutions (10 min)**

Troubleshooting scenarios:
- Rule not triggering → Check enabled status
- Wrong threshold → Edit rule settings
- Performance slow → Review number of active rules
- Duplicate awards → Check for multiple rules

**3. Reporting and Analytics (10 min)**

Show how to:
- Export audit data to CSV
- Generate monthly reports
- Identify trends in competency blocks
- Measure automation effectiveness

#### Hands-On Exercise
Participants review audit log entries, identify an error, and resolve it.

---

## Trainer Training Workshop

### Session 1: Understanding the Rules Engine (20 minutes)

#### Learning Objectives
- Understand how attendance affects competency access
- Know which rosters trigger automatic awards
- Communicate rules to learners effectively

#### Activities

**1. Attendance Impact (10 min)**
- How attendance marking triggers rule evaluation
- What learners see when competency is locked
- Importance of timely attendance marking

**2. Roster Automation (10 min)**
- Which roster types award competencies
- How to verify automatic awards
- What to tell learners about automation

#### Takeaways
- Attendance marking checklist
- Roster-to-competency mapping reference
- Learner communication templates

---

### Session 2: Supporting Learners (15 minutes)

#### Learning Objectives
- Help learners understand locked competencies
- Verify attendance calculations
- Escalate issues appropriately

#### Activities

**1. Learner Support Scenarios (10 min)**

Role-play common situations:
- Learner: "Why is this competency locked?"
- Learner: "I attended all sessions but still blocked"
- Learner: "I didn't get credit for my roster"

**2. Escalation Process (5 min)**
- When to contact administrator
- What information to provide
- How to check audit log (if access granted)

---

## Learner Orientation

### Session: Understanding Competency Rules (15 minutes)

#### Learning Objectives
- Understand attendance requirements
- Know how to unlock competencies
- Recognize automatic competency awards

#### Activities

**1. Attendance Requirements (5 min)**
- Which competencies have attendance thresholds
- How to check your attendance percentage
- What happens when threshold not met

**2. Unlocking Competencies (5 min)**
- Attend more sessions to improve percentage
- System updates automatically
- No manual action needed once threshold met

**3. Automatic Awards (5 min)**
- Which rosters award competencies
- Evidence appears automatically
- Check your competency profile regularly

#### Handout
- Competency-to-attendance mapping
- Roster-to-competency mapping
- FAQ sheet

---

## Quick Reference Cards

### For Administrators

**Creating Attendance Rule**
1. Admin → Plugins → SCEH Rules Engine
2. Click "Attendance Rules"
3. Click "Add Attendance Rule"
4. Select competency, course, threshold
5. Enable and save

**Creating Roster Rule**
1. Admin → Plugins → SCEH Rules Engine
2. Click "Roster Rules"
3. Click "Add Roster Rule"
4. Select roster type, competency
5. Enter evidence description
6. Enable and save

**Viewing Audit Log**
1. Admin → Plugins → SCEH Rules Engine
2. Click "Rules Engine Audit Log"
3. Review recent entries
4. Filter by rule type or user

---

### For Trainers

**Marking Attendance**
1. Navigate to Attendance activity
2. Select session
3. Mark Present/Absent/Excused
4. Save
5. System evaluates rules automatically

**Verifying Roster Awards**
1. Navigate to learner profile
2. Click "Competencies"
3. View "Evidence" tab
4. Look for automatic award entries

---

### For Learners

**Checking Attendance**
1. Navigate to course
2. Click Attendance activity
3. View "Summary" tab
4. Check your percentage

**Viewing Locked Competencies**
1. Navigate to Learning Plan
2. Look for 🔒 icon
3. Read lock message
4. Attend more sessions to unlock

**Checking Automatic Awards**
1. Navigate to your profile
2. Click "Competencies"
3. View evidence list
4. Look for roster completion entries

---

## Assessment and Certification

### Administrator Certification Quiz

1. What are the two main features of the rules engine?
2. How do you create an attendance rule?
3. What does a 75% threshold mean?
4. Where do you view the audit log?
5. How do you troubleshoot a rule that's not working?

**Passing Score**: 4/5 correct

### Trainer Competency Checklist

- [ ] Can explain attendance impact to learners
- [ ] Knows which rosters award competencies
- [ ] Can verify automatic competency awards
- [ ] Understands when to escalate issues
- [ ] Can access and interpret audit log (if granted)

### Learner Understanding Check

- [ ] Knows which competencies have attendance requirements
- [ ] Understands how to unlock competencies
- [ ] Recognizes automatic competency awards
- [ ] Knows where to check attendance percentage
- [ ] Knows who to contact with questions

---

## Training Resources

### Video Tutorials (To Be Created)

1. **Admin: Installing the Rules Engine** (5 min)
2. **Admin: Creating Your First Attendance Rule** (7 min)
3. **Admin: Setting Up Roster Automation** (7 min)
4. **Admin: Using the Audit Log** (5 min)
5. **Trainer: Understanding Attendance Impact** (4 min)
6. **Learner: Unlocking Competencies** (3 min)

### Documentation

- README.md - Technical overview
- ADMIN_GUIDE.md - Comprehensive admin documentation
- USER_GUIDE.md - Learner and trainer guide
- This file - Training materials

### Support

- Moodle forums: Search "SCEH Rules Engine"
- GitHub issues: Report bugs and feature requests
- Email support: admin@sceh.edu (example)
