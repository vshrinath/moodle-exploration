# Operations Guide: Backup, Reporting, Grading & Audit

**Purpose:** Operational procedures for system maintenance, reporting, and compliance  
**Audience:** System Admins, Program Owners  
**Last Updated:** 2026-02-13

---

## Table of Contents

1. [Backup & Disaster Recovery](#backup--disaster-recovery)
2. [Reporting & Analytics](#reporting--analytics)
3. [Assessment & Grading](#assessment--grading)
4. [Audit Logs](#audit-logs)
5. [Scaling for 2000 Users](#scaling-for-2000-users)

---

## Backup & Disaster Recovery

### Backup Strategy

**System Specifications:**
- Users: 2000
- Content: Mostly text and PowerPoint
- Videos: Hosted externally (YouTube/Vimeo)
- Database: ~5-10 GB estimated
- Files: ~20-50 GB estimated

### Automated Backup Schedule

#### Daily Backups (Automated)

**What to Backup:**
- Database (all tables)
- User-uploaded files (moodledata directory)
- Configuration files

**Retention:**
- Keep daily backups for 7 days
- Rotate automatically

**Implementation:**

```bash
#!/bin/bash
# /opt/bitnami/moodle/backup/daily_backup.sh

DATE=$(date +%Y%m%d)
BACKUP_DIR="/backup/moodle/daily"
MOODLE_DATA="/opt/bitnami/moodledata"
DB_NAME="bitnami_moodle"
DB_USER="bn_moodle"
DB_PASS="${MARIADB_PASSWORD}"

# Create backup directory
mkdir -p ${BACKUP_DIR}

# Backup database
mysqldump -u ${DB_USER} -p${DB_PASS} ${DB_NAME} | gzip > ${BACKUP_DIR}/db_${DATE}.sql.gz

# Backup moodledata (exclude cache and temp)
tar -czf ${BACKUP_DIR}/moodledata_${DATE}.tar.gz \
    --exclude='cache' \
    --exclude='temp' \
    --exclude='sessions' \
    ${MOODLE_DATA}

# Delete backups older than 7 days
find ${BACKUP_DIR} -name "*.gz" -mtime +7 -delete

echo "Daily backup completed: ${DATE}"
```

**Schedule with cron:**
```bash
# Run daily at 2 AM
0 2 * * * /opt/bitnami/moodle/backup/daily_backup.sh >> /var/log/moodle_backup.log 2>&1
```

**Time:** Automated, ~30-60 minutes per backup

---

#### Weekly Backups (Automated)

**What to Backup:**
- Full system backup (database + files + config)
- Course backups (Moodle format)

**Retention:**
- Keep weekly backups for 4 weeks

**Implementation:**

```bash
#!/bin/bash
# /opt/bitnami/moodle/backup/weekly_backup.sh

DATE=$(date +%Y%m%d)
BACKUP_DIR="/backup/moodle/weekly"
MOODLE_DATA="/opt/bitnami/moodledata"
DB_NAME="bitnami_moodle"
DB_USER="bn_moodle"
DB_PASS="${MARIADB_PASSWORD}"

# Create backup directory
mkdir -p ${BACKUP_DIR}

# Full database backup
mysqldump -u ${DB_USER} -p${DB_PASS} ${DB_NAME} | gzip > ${BACKUP_DIR}/db_full_${DATE}.sql.gz

# Full moodledata backup
tar -czf ${BACKUP_DIR}/moodledata_full_${DATE}.tar.gz ${MOODLE_DATA}

# Backup Moodle code directory
tar -czf ${BACKUP_DIR}/moodle_code_${DATE}.tar.gz /opt/bitnami/moodle

# Delete backups older than 28 days
find ${BACKUP_DIR} -name "*.gz" -mtime +28 -delete

echo "Weekly backup completed: ${DATE}"
```

**Schedule with cron:**
```bash
# Run weekly on Sunday at 3 AM
0 3 * * 0 /opt/bitnami/moodle/backup/weekly_backup.sh >> /var/log/moodle_backup.log 2>&1
```

**Time:** Automated, ~1-2 hours per backup

---

#### Monthly Backups (Manual + Automated)

**What to Backup:**
- Full system snapshot
- Individual course backups (Moodle format)
- Competency frameworks
- User data export

**Retention:**
- Keep monthly backups for 12 months
- Store off-site (cloud storage)

**Implementation:**

```bash
#!/bin/bash
# /opt/bitnami/moodle/backup/monthly_backup.sh

DATE=$(date +%Y%m)
BACKUP_DIR="/backup/moodle/monthly"
OFFSITE_DIR="/mnt/cloud-storage/moodle-backups"
MOODLE_DATA="/opt/bitnami/moodledata"
DB_NAME="bitnami_moodle"
DB_USER="bn_moodle"
DB_PASS="${MARIADB_PASSWORD}"

# Create backup directory
mkdir -p ${BACKUP_DIR}

# Full system backup
mysqldump -u ${DB_USER} -p${DB_PASS} ${DB_NAME} | gzip > ${BACKUP_DIR}/db_${DATE}.sql.gz
tar -czf ${BACKUP_DIR}/moodledata_${DATE}.tar.gz ${MOODLE_DATA}
tar -czf ${BACKUP_DIR}/moodle_code_${DATE}.tar.gz /opt/bitnami/moodle

# Copy to off-site storage
rsync -avz ${BACKUP_DIR}/ ${OFFSITE_DIR}/

# Delete local backups older than 12 months
find ${BACKUP_DIR} -name "*.gz" -mtime +365 -delete

echo "Monthly backup completed: ${DATE}"
```

**Schedule with cron:**
```bash
# Run monthly on 1st day at 4 AM
0 4 1 * * /opt/bitnami/moodle/backup/monthly_backup.sh >> /var/log/moodle_backup.log 2>&1
```

**Time:** Automated, ~2-3 hours per backup

---

### Disaster Recovery Procedures

#### Scenario 1: Database Corruption

**Symptoms:**
- Moodle shows database errors
- Users cannot log in
- Data appears missing

**Recovery Steps:**

1. **Stop web access (keep database running)**
```bash
cd /opt/bitnami
./ctlscript.sh stop apache
```

2. **Ensure MariaDB is running**
```bash
./ctlscript.sh start mariadb
```

3. **Restore Database from Backup**
```bash
# Find latest backup
ls -lh /backup/moodle/daily/

# Restore database
gunzip < /backup/moodle/daily/db_20260213.sql.gz | mysql -u bn_moodle -p bitnami_moodle
```

4. **Restart web server**
```bash
./ctlscript.sh start apache
```

4. **Verify**
- Log in as admin
- Check recent data
- Verify user access

**Time:** 30-60 minutes

---

#### Scenario 2: File System Corruption

**Symptoms:**
- Uploaded files missing
- Images not displaying
- Course content unavailable

**Recovery Steps:**

1. **Stop Moodle**
```bash
cd /opt/bitnami
./ctlscript.sh stop apache
```

2. **Restore Moodledata**
```bash
# Backup current (corrupted) data
mv /opt/bitnami/moodledata /opt/bitnami/moodledata.corrupted

# Restore from backup
tar -xzf /backup/moodle/daily/moodledata_20260213.tar.gz -C /opt/bitnami/

# Fix permissions
chown -R daemon:daemon /opt/bitnami/moodledata
```

3. **Restart Moodle**
```bash
./ctlscript.sh start apache
```

4. **Verify**
- Check uploaded files
- Verify course content
- Test file uploads

**Time:** 1-2 hours

---

#### Scenario 3: Complete System Failure

**Symptoms:**
- Server crashed
- Hardware failure
- Need to restore on new server

**Recovery Steps:**

1. **Set up new Moodle instance**
- Install Moodle 5.0.1 (same version)
- Configure database

2. **Restore Database**
```bash
gunzip < /backup/moodle/weekly/db_full_20260210.sql.gz | mysql -u bn_moodle -p bitnami_moodle
```

3. **Restore Files**
```bash
tar -xzf /backup/moodle/weekly/moodledata_full_20260210.tar.gz -C /opt/bitnami/
tar -xzf /backup/moodle/weekly/moodle_code_20260210.tar.gz -C /opt/bitnami/
```

4. **Update Configuration**
```bash
# Edit config.php with new server details
vi /opt/bitnami/moodle/config.php
```

5. **Fix Permissions**
```bash
chown -R daemon:daemon /opt/bitnami/moodle
chown -R daemon:daemon /opt/bitnami/moodledata
```

6. **Start Services**
```bash
cd /opt/bitnami
./ctlscript.sh start
```

7. **Verify**
- Full system test
- User login test
- Course access test

**Time:** 4-8 hours

---

### Backup Verification

**Monthly Verification (Required):**

1. **Test Database Restore**
```bash
# Restore latest daily backup to test database
LATEST_DB_BACKUP=$(ls -t /backup/moodle/daily/db_*.sql.gz | head -1)
gunzip < "${LATEST_DB_BACKUP}" | mysql -u bn_moodle -p test_moodle

# Verify tables
mysql -u bn_moodle -p test_moodle -e "SHOW TABLES;"

# Check record counts
mysql -u bn_moodle -p test_moodle -e "SELECT COUNT(*) FROM mdl_user;"
```

2. **Test File Restore**
```bash
# Extract latest daily moodledata backup to test directory
LATEST_FILE_BACKUP=$(ls -t /backup/moodle/daily/moodledata_*.tar.gz | head -1)
tar -xzf "${LATEST_FILE_BACKUP}" -C /tmp/test_restore/

# Verify file count
find /tmp/test_restore -type f | wc -l
```

3. **Document Results**
- Date of test
- Backup file tested
- Success/failure
- Issues found

**Time:** 30 minutes per month

---

### Backup Monitoring

**Daily Checks (Automated):**

```bash
#!/bin/bash
# /opt/bitnami/moodle/backup/check_backup.sh

# Check if backup completed today
TODAY=$(date +%Y%m%d)
BACKUP_FILE="/backup/moodle/daily/db_${TODAY}.sql.gz"

if [ -f "$BACKUP_FILE" ]; then
    SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    echo "✓ Backup successful: ${BACKUP_FILE} (${SIZE})"
else
    echo "✗ Backup FAILED: ${BACKUP_FILE} not found"
    # Send alert email
    echo "Backup failed on ${TODAY}" | mail -s "Moodle Backup Alert" admin@example.com
fi
```

**Schedule with cron:**
```bash
# Check daily at 6 AM (after backup completes)
0 6 * * * /opt/bitnami/moodle/backup/check_backup.sh >> /var/log/moodle_backup_check.log 2>&1
```

---

### Off-Site Backup Storage

**Recommended: Cloud Storage**

**Options:**
1. AWS S3
2. Google Cloud Storage
3. Azure Blob Storage
4. Backblaze B2 (cost-effective)

**Implementation Example (AWS S3):**

```bash
# Install AWS CLI
apt-get install awscli

# Configure AWS credentials
aws configure

# Sync backups to S3
aws s3 sync /backup/moodle/monthly/ s3://your-bucket/moodle-backups/ --storage-class GLACIER
```

**Cost Estimate (2000 users):**
- Storage: ~100 GB total
- AWS S3 Glacier: ~$0.40/month
- Retrieval: ~$10 if needed

---

## Reporting & Analytics

### Required Reports

#### 1. Cohort Performance Comparison (Weekly)

**Purpose:** Compare trainer effectiveness across cohorts

**Metrics:**
- Completion rate per cohort
- Average grade per cohort
- Time to complete per cohort
- At-risk learner count

**Setup:**

1. Navigate to: Site administration → Reports → Report builder → Custom reports
2. Click "New report"
3. Name: "Cohort Performance Comparison"
4. Source: "Courses"
5. Add columns:
   - Course name
   - Cohort name
   - Enrolled users count
   - Completion rate (%)
   - Average grade (%)
   - Trainer name
6. Add filters:
   - Date range
   - Program (course)
   - Cohort
7. Save report

**Schedule:**
- Frequency: Weekly (Monday 8 AM)
- Recipients: Trainers, Program Owners, System Admin
- Format: PDF + CSV

**Time:** 1 hour setup, automated thereafter

---

#### 2. Trainer Effectiveness Dashboard (Weekly)

**Purpose:** Monitor trainer performance

**Metrics:**
- Number of cohorts per trainer
- Average completion rate
- Average grade
- Average time to grade submissions
- Number of at-risk learners

**Setup:**

1. New report
2. Name: "Trainer Effectiveness Dashboard"
3. Source: "Users"
4. Add columns:
   - Trainer name
   - Number of cohorts
   - Avg completion rate (%)
   - Avg grade (%)
   - Avg time to grade (days)
   - At-risk learners count
5. Add filters:
   - Date range
   - Trainer
   - Program category
6. Save report

**Schedule:**
- Frequency: Weekly (Monday 8 AM)
- Recipients: Trainer Coaches, System Admin
- Format: PDF

**Red Flags:**
- Completion rate <70%
- Average grade <75%
- Time to grade >7 days
- At-risk learners >30%

**Time:** 1 hour setup, automated thereafter

---

#### 3. Program Health Report (Monthly)

**Purpose:** Monitor overall program performance

**Metrics:**
- Total enrollments
- Active learners
- Completion rate
- Competency achievement rate
- Drop-off points (where learners get stuck)

**Setup:**

1. New report
2. Name: "Program Health Report"
3. Source: "Courses"
4. Add columns:
   - Program name
   - Total enrollments
   - Active learners
   - Completion rate (%)
   - Avg time to complete (days)
   - Competency achievement rate (%)
6. Add filters:
   - Date range
   - Program category
7. Save report

**Schedule:**
- Frequency: Monthly (1st day, 9 AM)
- Recipients: Program Owners, System Admin
- Format: PDF + Excel

**Time:** 1 hour setup, automated thereafter

---

#### 4. Learner Progress Report (On-Demand)

**Purpose:** Track individual learner progress

**Metrics:**
- Activities completed
- Competencies achieved
- Current grade
- Time spent
- Last activity date

**Setup:**

1. New report
2. Name: "Learner Progress Report"
3. Source: "Users"
4. Add columns:
   - Learner name
   - Program name
   - Progress (%)
   - Competencies achieved
   - Current grade (%)
   - Last activity date
5. Add filters:
   - Learner
   - Program
   - Date range
6. Save report

**Usage:**
- Trainers run for their cohorts
- Program Owners run for their programs
- On-demand, not scheduled

**Time:** 1 hour setup

---

#### 5. Competency Achievement Report (Monthly)

**Purpose:** Track competency achievement across programs

**Metrics:**
- Competency name
- Number of learners achieved
- Average proficiency level
- Time to achieve

**Setup:**

1. New report
2. Name: "Competency Achievement Report"
3. Source: "Competencies"
4. Add columns:
   - Competency name
   - Program name
   - Learners achieved count
   - Avg proficiency level
   - Avg time to achieve (days)
5. Add filters:
   - Date range
   - Program
   - Competency framework
6. Save report

**Schedule:**
- Frequency: Monthly (1st day, 10 AM)
- Recipients: Program Owners
- Format: Excel

**Time:** 1 hour setup, automated thereafter

---

#### 6. Attendance Report (Weekly)

**Purpose:** Monitor attendance patterns

**Metrics:**
- Cohort name
- Session date
- Attendance rate
- Absent learners
- Late arrivals

**Setup:**

1. New report
2. Name: "Attendance Report"
3. Source: "Attendance"
4. Add columns:
   - Cohort name
   - Session date
   - Total learners
   - Present count
   - Absent count
   - Late count
   - Attendance rate (%)
5. Add filters:
   - Date range
   - Cohort
   - Trainer
6. Save report

**Schedule:**
- Frequency: Weekly (Friday 5 PM)
- Recipients: Trainers, Trainer Coaches
- Format: PDF

**Time:** 1 hour setup, automated thereafter

---

### Analytics Dashboard

**System Admin Dashboard:**
```
Organization-Wide Metrics
├── Total Users: 2000
├── Active Learners: 1500
├── Active Programs: 25
├── Overall Completion Rate: 82%
└── System Health: ✓ Good

Recent Activity (Last 7 Days)
├── New Enrollments: 45
├── Completions: 23
├── Badges Awarded: 67
└── Active Trainers: 18
```

**Program Owner Dashboard:**
```
My Programs Performance
├── Allied Assist Program
│   ├── Enrollments: 145
│   ├── Completion: 87%
│   └── Avg Grade: 84%
├── Medical Assistant Program
│   ├── Enrollments: 98
│   ├── Completion: 91%
│   └── Avg Grade: 86%
```

**Trainer Dashboard:**
```
My Cohorts Performance
├── 2024 Fellows - Dr. Smith
│   ├── Learners: 15
│   ├── Completion: 80%
│   ├── Pending Reviews: 3
│   └── At-Risk: 2
```

---

## Assessment & Grading

### Grading Scales

#### 1. Percentage Scale (Default)

**Range:** 0-100%

**Pass Threshold:** 80%

**Usage:** Quizzes, assignments, exams

**Setup:**
1. Navigate to: Site administration → Grades → Scales
2. Default scale is percentage (0-100)
3. Set passing grade: 80% in each activity

**Display:**
- 90-100%: Excellent
- 80-89%: Good
- 70-79%: Satisfactory (Below Pass)
- <70%: Needs Improvement

---

#### 2. Competency Scale

**Levels:**
1. Not Yet Competent
2. Developing
3. Competent
4. Proficient

**Pass Threshold:** Competent (Level 3)

**Usage:** Competency-based assessments

**Setup:**
1. Navigate to: Site administration → Competencies → Competency frameworks
2. Click on framework
3. Edit scale
4. Add levels:
   - Not Yet Competent (value: 1)
   - Developing (value: 2)
   - Competent (value: 3) ← Default proficiency
   - Proficient (value: 4)
5. Set default proficiency: Competent
6. Save

**Usage in Activities:**
- Link activity to competency
- When learner completes activity, competency is marked
- Trainer can override competency level if needed

---

#### 3. Pass/Fail Scale

**Levels:**
- Pass
- Fail

**Usage:** Attendance, simple assessments

**Setup:**
1. Navigate to: Site administration → Grades → Scales
2. Click "Add a new scale"
3. Name: "Pass/Fail"
4. Scale: "Fail,Pass"
5. Description: "Simple pass/fail grading"
6. Save

**Usage:**
- Select "Pass/Fail" scale in activity grading settings
- Trainer marks as Pass or Fail

---

### Rubrics

#### What is a Rubric?

A rubric is a scoring guide with criteria and performance levels.

**Example: Case Analysis Assignment Rubric**

| Criteria | Excellent (4) | Good (3) | Satisfactory (2) | Poor (1) |
|----------|---------------|----------|------------------|----------|
| **Clinical Reasoning** | Demonstrates exceptional analysis | Shows good understanding | Basic analysis present | Lacks critical thinking |
| **Evidence-Based** | Cites 5+ relevant sources | Cites 3-4 sources | Cites 1-2 sources | No sources cited |
| **Communication** | Clear, professional, well-organized | Mostly clear and organized | Somewhat unclear | Difficult to understand |
| **Recommendations** | Comprehensive, actionable | Good recommendations | Basic recommendations | Weak or missing |

**Total:** 16 points possible  
**Pass Threshold:** 12 points (75%)

---

#### Creating a Rubric

**Steps:**

1. Navigate to assignment
2. Click "Edit settings"
3. Expand "Grade" section
4. Grading method: Select "Rubric"
5. Click "Define new rubric from scratch"
6. Enter rubric name: "Case Analysis Rubric"
7. Add criteria:
   - Click "Add criterion"
   - Name: "Clinical Reasoning"
   - Description: "Ability to analyze clinical scenarios"
8. Add levels for each criterion:
   - Excellent (4 points)
   - Good (3 points)
   - Satisfactory (2 points)
   - Poor (1 point)
9. Repeat for all criteria
10. Save rubric
11. Save assignment settings

**Time:** 30 minutes per rubric

---

#### Using a Rubric to Grade

**Trainer Steps:**

1. Navigate to assignment
2. Click "View all submissions"
3. Click "Grade" for a learner
4. Rubric appears
5. Click on appropriate level for each criterion
6. Rubric automatically calculates total score
7. Add feedback comments (optional)
8. Click "Save changes"
9. Learner sees rubric with selected levels

**Benefits:**
- ✅ Consistent grading across learners
- ✅ Clear expectations for learners
- ✅ Faster grading (click levels vs. calculating scores)
- ✅ Detailed feedback

**Time:** 5-10 minutes per submission with rubric

---

### Peer Assessment

#### What is Peer Assessment?

Learners review and grade each other's work.

**Benefits:**
- Develops critical thinking
- Reduces trainer workload
- Learners learn from peers
- Encourages reflection

**Use Cases:**
- Case presentations
- Teaching demonstrations
- Peer observations
- Group projects

---

#### Setting Up Peer Assessment

**Using Workshop Activity:**

1. Add activity → Workshop
2. Name: "Peer Case Presentation Review"
3. Description: Instructions for learners
4. Submission settings:
   - Submission types: File upload
   - Maximum file size: 10 MB
5. Assessment settings:
   - Grading strategy: "Rubric"
   - Number of peer reviews: 3 (each learner reviews 3 peers)
6. Create assessment rubric:
   - Criteria: Clarity, Evidence, Recommendations
   - Levels: Excellent, Good, Satisfactory, Poor
7. Grading settings:
   - Grade for submission: 70% (their own work)
   - Grade for assessment: 30% (quality of their peer reviews)
8. Save

**Workflow:**

**Phase 1: Setup (Trainer)**
- Create workshop activity
- Define rubric
- Set deadlines

**Phase 2: Submission (Learners)**
- Learners submit their work
- Deadline: Week 8, Friday

**Phase 3: Assessment Allocation (Automatic)**
- System randomly assigns 3 submissions to each learner
- Learners cannot review their own work

**Phase 4: Peer Review (Learners)**
- Learners review assigned submissions
- Use rubric to grade
- Provide written feedback
- Deadline: Week 9, Friday

**Phase 5: Grading (Automatic + Trainer)**
- System calculates grades:
  - 70% from peer reviews of their submission
  - 30% from quality of their peer reviews
- Trainer reviews and can override if needed

**Phase 6: Feedback (Learners)**
- Learners see peer feedback
- Learners see their grade

**Time:** 2 hours setup, automated thereafter

---

#### Peer Assessment Best Practices

**Do:**
- ✅ Provide clear rubric
- ✅ Give examples of good reviews
- ✅ Set realistic deadlines
- ✅ Monitor for inappropriate reviews
- ✅ Trainer reviews final grades

**Don't:**
- ❌ Use for high-stakes assessments
- ❌ Make peer grade 100% of final grade
- ❌ Skip trainer oversight
- ❌ Allow anonymous reviews (accountability)

---

#### Monitoring Peer Assessments

**Trainer Dashboard:**

```
Workshop: Peer Case Presentation Review
├── Submissions: 15/15 (100%)
├── Peer Reviews Completed: 42/45 (93%)
├── Pending Reviews: 3
├── Flagged Reviews: 1 (inappropriate feedback)
└── Average Grade: 82%
```

**Trainer Actions:**
1. Review flagged submissions
2. Override grades if needed
3. Provide additional feedback
4. Release grades to learners

**Time:** 30 minutes per workshop

---

### Grading Workflow

**Standard Assignment Grading:**

1. **Learner submits** → Assignment appears in "Pending Reviews"
2. **Trainer receives notification** → Email or dashboard alert
3. **Trainer grades** → Uses rubric, adds feedback
4. **Learner receives notification** → Email with grade and feedback
5. **Learner views feedback** → Can respond or request clarification

**Timeline:**
- Submission: Week 5, Friday 11:59 PM
- Grading deadline: Within 3 days (Monday 11:59 PM)
- Learner feedback: Immediate upon grading

---

### Grade Book Configuration

**Setup:**

1. Navigate to program (course)
2. Click "Grades"
3. Click "Setup" → "Gradebook setup"
4. Configure:
   - Aggregation: Weighted mean of grades
   - Weights:
     - Quizzes: 20%
     - Assignments: 40%
     - Final Exam: 30%
     - Participation: 10%
5. Set passing grade: 80%
6. Save

**Learner View:**
```
My Grades - Allied Assist Program
├── Quizzes (20%)
│   ├── Week 1 Quiz: 85%
│   ├── Week 2 Quiz: 90%
│   └── Average: 87.5%
├── Assignments (40%)
│   ├── Case Analysis: 82%
│   ├── Simulation: 88%
│   └── Average: 85%
├── Final Exam (30%): 86%
├── Participation (10%): 95%
└── Final Grade: 86.3% (Pass)
```

---

## Audit Logs

### What is Logged

Moodle automatically logs all user actions:

**User Actions:**
- Login/logout
- Course access
- Activity views
- Assignment submissions
- Grade changes
- Role assignments
- User creation/modification

**System Actions:**
- Configuration changes
- Plugin installations
- Backup/restore operations
- Database modifications

**Retention:** 90 days by default (configurable)

---

### Accessing Audit Logs

#### System-Wide Logs (System Admin Only)

**Steps:**
1. Navigate to: Site administration → Reports → Logs
2. Select filters:
   - Course: All courses
   - User: All users
   - Date: Last 7 days
   - Activity: All actions
3. Click "Get these logs"
4. View results

**Export:**
- Click "Download" → CSV or Excel
- Save for compliance records

**Time:** 2 minutes

---

#### Course-Specific Logs (Program Owner)

**Steps:**
1. Navigate to program (course)
2. Click gear icon → More → Reports → Logs
3. Select filters:
   - User: All participants
   - Date: Last 30 days
   - Activity: All actions
4. Click "Get these logs"

**Use Cases:**
- Track learner activity
- Identify inactive learners
- Verify submission times
- Investigate issues

**Time:** 2 minutes

---

#### User-Specific Logs (Trainer, Program Owner)

**Steps:**
1. Navigate to user profile
2. Click "Reports" → "Logs"
3. View all actions by this user
4. Filter by date, course, activity

**Use Cases:**
- Verify learner participation
- Check submission history
- Investigate academic integrity issues
- Support troubleshooting

**Time:** 2 minutes

---

### Important Log Events

#### Security Events

**Monitor for:**
- Failed login attempts (>5 in 10 minutes)
- Role assignment changes
- Permission changes
- User account modifications

**Alert Triggers:**
```
Event: Failed login
Threshold: 5 attempts in 10 minutes
Action: Email System Admin
```

**Setup:**
1. Navigate to: Site administration → Reports → Logs
2. Create saved search: "Failed Logins"
3. Schedule: Check hourly
4. Alert: Email if threshold exceeded

---

#### Academic Integrity Events

**Monitor for:**
- Multiple submissions in short time
- Unusual activity patterns
- Grade changes after submission
- Suspicious quiz attempts

**Example Log Entry:**
```
Time: 2026-02-13 14:35:22
User: John Doe (ID: 1234)
Event: Assignment submitted
Course: Allied Assist Program
Activity: Case Analysis Assignment
IP Address: 192.168.1.100
```

**Use Case:**
- Learner claims they submitted on time
- Check logs for submission timestamp
- Verify IP address and activity

---

#### Compliance Events

**Required for Audit:**
- User enrollments
- Course completions
- Competency achievements
- Certificate issuances
- Grade changes

**Export for Compliance:**
1. Navigate to: Site administration → Reports → Logs
2. Filter:
   - Event: Course completed
   - Date range: Last year
3. Export to CSV
4. Store in compliance records

**Retention:** 7 years (adjust based on regulations)

---

### Audit Log Reports

#### 1. User Activity Report

**Purpose:** Track user engagement

**Metrics:**
- Login frequency
- Time spent in system
- Activities completed
- Last activity date

**Setup:**
1. Navigate to: Site administration → Reports → Report builder
2. New report: "User Activity Report"
3. Source: "Logs"
4. Add columns:
   - User name
   - Login count (last 30 days)
   - Time spent (hours)
   - Activities completed
   - Last activity date
5. Save

**Schedule:** Weekly

---

#### 2. Grade Change Audit

**Purpose:** Track all grade modifications

**Metrics:**
- Who changed grade
- When changed
- Old grade
- New grade
- Reason (if provided)

**Setup:**
1. New report: "Grade Change Audit"
2. Source: "Logs"
3. Filter: Event = "Grade updated"
4. Add columns:
   - Date/time
   - Learner name
   - Activity name
   - Grader name
   - Old grade
   - New grade
5. Save

**Schedule:** Monthly (for compliance)

---

#### 3. System Configuration Changes

**Purpose:** Track system modifications

**Metrics:**
- Who made change
- What changed
- When changed
- Previous value
- New value

**Setup:**
1. New report: "System Configuration Changes"
2. Source: "Logs"
3. Filter: Event = "Configuration updated"
4. Add columns:
   - Date/time
   - User name
   - Setting name
   - Old value
   - New value
5. Save

**Schedule:** Monthly

---

### Log Retention Policy

**Default:** 90 days

**Recommended for 2000 users:**
- Standard logs: 180 days (6 months)
- Security logs: 365 days (1 year)
- Compliance logs: 2555 days (7 years)

**Configuration:**
1. Navigate to: Site administration → Server → Cleanup
2. Set "Keep logs for": 180 days
3. Save

**Archive Old Logs:**
```bash
# Export logs older than 180 days
mysql -u bn_moodle -p bitnami_moodle -e \
  "SELECT * FROM mdl_logstore_standard_log WHERE timecreated < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 180 DAY))" \
  > /backup/moodle/logs/archived_logs_$(date +%Y%m%d).csv

# Delete old logs
mysql -u bn_moodle -p bitnami_moodle -e \
  "DELETE FROM mdl_logstore_standard_log WHERE timecreated < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 180 DAY))"
```

**Schedule:** Monthly

---

## Scaling for 2000 Users

### System Requirements

**Current Specifications (Bitnami Moodle):**
- Users: 2000
- Concurrent users: ~200 (10% of total)
- Content: Text, PowerPoint, external videos
- Database size: ~10 GB
- File storage: ~50 GB

**Recommended Server Specs:**

```
CPU: 4-8 cores
RAM: 16 GB minimum, 32 GB recommended
Storage: 200 GB SSD
Database: MariaDB 10.6+ or PostgreSQL 13+
PHP: 8.1+
Web Server: Apache 2.4+ or Nginx
```

**Current Bitnami Setup:** Likely adequate for 2000 users with optimizations

---

### Performance Optimization

#### 1. Enable Caching

**Moodle Caching:**
1. Navigate to: Site administration → Plugins → Caching → Configuration
2. Enable caching stores:
   - File cache: Enabled (default)
   - APCu cache: Enable if available
   - Redis cache: Enable for best performance
3. Save

**Redis Setup (Recommended):**
```bash
# Install Redis
apt-get install redis-server

# Configure Moodle to use Redis
# Edit config.php
$CFG->session_handler_class = '\core\session\redis';
$CFG->session_redis_host = '127.0.0.1';
$CFG->session_redis_port = 6379;
$CFG->session_redis_database = 0;
$CFG->session_redis_prefix = 'moodle_';
```

**Impact:** 30-50% performance improvement

---

#### 2. Database Optimization

**MariaDB only: Enable Query Cache**
```sql
# Edit MariaDB config
[mysqld]
query_cache_type = 1
query_cache_size = 256M
query_cache_limit = 2M
```

If using PostgreSQL, skip this section and use PostgreSQL-specific tuning instead.

**Optimize Tables Monthly:**
```bash
#!/bin/bash
# /opt/bitnami/moodle/maintenance/optimize_db.sh

mysql -u bn_moodle -p bitnami_moodle -e "OPTIMIZE TABLE mdl_logstore_standard_log;"
mysql -u bn_moodle -p bitnami_moodle -e "OPTIMIZE TABLE mdl_grade_grades;"
mysql -u bn_moodle -p bitnami_moodle -e "OPTIMIZE TABLE mdl_user;"
mysql -u bn_moodle -p bitnami_moodle -e "OPTIMIZE TABLE mdl_course;"

echo "Database optimization completed"
```

**Schedule:** Monthly (1st Sunday, 3 AM)

**Impact:** 10-20% performance improvement

---

#### 3. File Storage Optimization

**External Video Hosting:**
- ✅ YouTube (free, unlimited)
- ✅ Vimeo (paid, better privacy)
- ✅ Embed videos via URL (no storage used)

**PowerPoint Optimization:**
- Convert to PDF (smaller file size)
- Or upload to Google Slides/OneDrive and embed

**File Upload Limits:**
```php
# config.php
$CFG->maxbytes = 10485760; // 10 MB max file size
```

**Impact:** Reduces storage needs by 80%

---

#### 4. Content Delivery Network (CDN)

**For Static Assets:**
- CSS, JavaScript, images
- Serve from CDN for faster loading

**Setup (Optional):**
1. Sign up for Cloudflare (free tier)
2. Point domain to Cloudflare
3. Enable caching for static assets
4. Automatic optimization

**Impact:** 20-30% faster page loads

---

### Monitoring

#### System Health Checks

**Daily Automated Checks:**

```bash
#!/bin/bash
# /opt/bitnami/moodle/monitoring/health_check.sh

# Check disk space
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "WARNING: Disk usage at ${DISK_USAGE}%"
fi

# Check database size
DB_SIZE=$(mysql -u bn_moodle -p -e "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' FROM information_schema.TABLES WHERE table_schema='bitnami_moodle';" | tail -1)
echo "Database size: ${DB_SIZE} MB"

# Check active users
ACTIVE_USERS=$(mysql -u bn_moodle -p bitnami_moodle -e "SELECT COUNT(*) FROM mdl_user WHERE lastaccess > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY));" | tail -1)
echo "Active users (last 7 days): ${ACTIVE_USERS}"

# Check failed logins
FAILED_LOGINS=$(mysql -u bn_moodle -p bitnami_moodle -e "SELECT COUNT(*) FROM mdl_logstore_standard_log WHERE eventname LIKE '%failed%' AND timecreated > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY));" | tail -1)
if [ $FAILED_LOGINS -gt 50 ]; then
    echo "WARNING: ${FAILED_LOGINS} failed logins in last 24 hours"
fi

echo "Health check completed: $(date)"
```

**Schedule:** Daily at 6 AM

---

#### Performance Metrics

**Monitor:**
- Page load time: <2 seconds
- Database query time: <100ms average
- Concurrent users: <200 (10% of 2000)
- Server CPU: <70% average
- Server RAM: <80% usage
- Disk I/O: <80% capacity

**Tools:**
- Moodle Performance Overview: Site administration → Reports → Performance overview
- Server monitoring: htop, iotop, netstat
- External monitoring: UptimeRobot, Pingdom

---

### Scaling Beyond 2000 Users

**If you grow to 5000+ users:**

**Option 1: Vertical Scaling (Easier)**
- Upgrade server: 8 cores → 16 cores
- Increase RAM: 32 GB → 64 GB
- Faster storage: SSD → NVMe SSD
- Cost: ~$200-500/month

**Option 2: Horizontal Scaling (More Complex)**
- Separate database server
- Multiple web servers (load balanced)
- Shared file storage (NFS or S3)
- Redis cluster for caching
- Cost: ~$500-1000/month

**Recommendation:** Start with vertical scaling, move to horizontal if needed.

---

### Capacity Planning

**Current: 2000 users**
- Storage growth: ~5 GB/month
- Database growth: ~1 GB/month
- Backup size: ~60 GB total

**Projected: 3 years**
- Users: 3000-4000
- Storage: 230 GB
- Database: 46 GB
- Backups: 300 GB

**Action:** Monitor quarterly, upgrade when reaching 70% capacity

---

## Summary

This operations guide covers:

✅ **Backup & Disaster Recovery**
- Daily, weekly, monthly automated backups
- Off-site storage
- Recovery procedures for all scenarios
- Backup verification

✅ **Reporting & Analytics**
- 6 essential reports (cohort performance, trainer effectiveness, program health, learner progress, competency achievement, attendance)
- Automated scheduling
- Dashboard views for all roles

✅ **Assessment & Grading**
- 3 grading scales (percentage, competency, pass/fail)
- Rubric creation and usage
- Peer assessment setup and workflow
- Grade book configuration

✅ **Audit Logs**
- What is logged
- How to access logs
- Important events to monitor
- Compliance reporting
- Log retention policy

✅ **Scaling for 2000 Users**
- System requirements
- Performance optimization (caching, database, CDN)
- Monitoring and health checks
- Capacity planning

**Next Steps:**
1. Implement backup automation (Week 1)
2. Set up essential reports (Week 2)
3. Configure grading scales and rubrics (Week 3)
4. Enable audit logging (Week 4)
5. Optimize performance (Week 5)

For technical implementation, see `docs/PRAGMATIC_IMPLEMENTATION_GUIDE.md`.  
For user workflows, see `docs/USER_WORKFLOWS.md`.
