# System Architecture Documentation

**Purpose:** Visual and technical documentation of system architecture  
**Audience:** Developers, System Admins, Technical Leadership  
**Last Updated:** 2026-02-23

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                         SCEH Moodle LMS                         │
│                     (Allied Health Training)                     │
└─────────────────────────────────────────────────────────────────┘
                                 │
                    ┌────────────┴────────────┐
                    │                         │
            ┌───────▼────────┐       ┌───────▼────────┐
            │  Web Container │       │ Cron Container │
            │   (Apache/PHP) │       │   (Scheduled)  │
            └───────┬────────┘       └───────┬────────┘
                    │                         │
                    └────────────┬────────────┘
                                 │
                    ┌────────────▼────────────┐
                    │   MySQL Container       │
                    │   (MariaDB/MySQL 8.0)   │
                    └────────────┬────────────┘
                                 │
                    ┌────────────▼────────────┐
                    │   Docker Volumes        │
                    │  - mysql_data           │
                    │  - moodledata           │
                    └─────────────────────────┘
```

---

## Container Architecture

### Web Container (moodle_web)

**Base Image:** `moodlehq/moodle-php-apache:8.2`

**Responsibilities:**
- Serve Moodle web application
- Handle HTTP requests
- Execute PHP code
- Manage user sessions

**Mounted Volumes:**
```yaml
volumes:
  - ./local_sceh_rules:/var/www/html/public/local/sceh_rules
  - ./block_sceh_dashboard:/var/www/html/public/blocks/sceh_dashboard
  - ./local_kirkpatrick_level4:/var/www/html/public/local/kirkpatrick_level4
  - ./local_sceh_importer:/var/www/html/public/local/sceh_importer
  - moodledata:/var/www/moodledata
```

**Environment Variables:**
- `SCEH_DB_HOST`: Database hostname
- `SCEH_DB_NAME`: Database name
- `SCEH_DB_USER`: Database username
- `SCEH_DB_PASSWORD`: Database password
- `SCEH_WWWROOT`: Public URL
- `SCEH_DATAROOT`: Moodledata path

**Ports:**
- `8081:80` (HTTP, configurable via `MOODLEHQ_WEB_PORT`)

**Health Check:** None (recommended to add)

---

### Cron Container (moodle_cron)

**Base Image:** `moodlehq/moodle-php-apache:8.2`

**Responsibilities:**
- Execute scheduled tasks
- Process background jobs
- Run maintenance tasks
- Badge issuance
- Competency evaluations

**Mounted Volumes:**
```yaml
volumes:
  - ./local_sceh_rules:/var/www/html/public/local/sceh_rules
  - ./block_sceh_dashboard:/var/www/html/public/blocks/sceh_dashboard
  - moodledata:/var/www/moodledata
```

**Environment Variables:**
- `SCEH_DB_HOST`, `SCEH_DB_PORT`, `SCEH_DB_NAME`, `SCEH_DB_USER`, `SCEH_DB_PASSWORD` (added Feb 2026)
- `SCEH_CRON_INTERVAL` (default: 60s)

**Execution:**
- Verifies DB connectivity at startup
- Runs `php admin/cli/cron.php` every 60 seconds (configurable)
- Errors logged to stderr (no longer swallowed with `|| true`)

**Health Check:**
```yaml
healthcheck:
  test: ["CMD-SHELL", "test -f /var/www/html/config.php"]
  interval: 30s
  timeout: 5s
  retries: 5
```

---

### Database Container (mysql)

**Base Image:** `mysql:8.4`

**Responsibilities:**
- Store all Moodle data
- User accounts
- Course content
- Grades and competencies
- Audit logs

**Mounted Volumes:**
```yaml
volumes:
  - mysql_data:/var/lib/mysql
```

**Environment Variables:**
- `MYSQL_ROOT_PASSWORD`: Root password
- `MYSQL_DATABASE`: Database name
- `MYSQL_USER`: Application user
- `MYSQL_PASSWORD`: Application password

**Ports:**
- `3306:3306` (MySQL)

**Health Check:**
```yaml
healthcheck:
  test: ["CMD-SHELL", "mysqladmin ping -h 127.0.0.1 -u$MYSQL_USER -p$MYSQL_PASSWORD --silent"]
  interval: 10s
  timeout: 5s
  retries: 20
```

---

## Data Flow Architecture

### User Request Flow

```
┌──────────┐
│  User    │
│ (Browser)│
└────┬─────┘
     │ HTTP (8081)
     ▼
┌────────────────┐
│  Web Container │
│   Apache/PHP   │
└────┬───────────┘
     │
     ├─────────────────┐
     │                 │
     ▼                 ▼
┌─────────────┐   ┌──────────────┐
│   MySQL     │   │  Moodledata  │
│  Database   │   │  (Files)     │
└─────────────┘   └──────────────┘
```

**Steps:**
1. User accesses Moodle via browser (http://localhost:8081)
2. Apache receives HTTP request
3. PHP processes request
4. Queries MySQL for data
5. Reads/writes files in moodledata volume
6. Returns HTML response to user

---

### Rules Engine Data Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    Event Trigger                            │
│  (Attendance marked, Roster completed, Manual evaluation)   │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│              Moodle Event System                            │
│         (mod_attendance\event\attendance_taken)             │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│           local_sceh_rules Observer                         │
│  - attendance_observer or roster_observer                   │
│  - Queues an adhoc task (non-blocking)                      │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│           Adhoc Task (evaluate_rules_task)                  │
│  1. Acquire lock (lock_factory, per-scope key)              │
│  2. If lock unavailable → skip (dedup)                      │
│  3. Evaluate rules for course/user                          │
│  4. Record metrics (success/failure counters)               │
│  5. Release lock                                            │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│              Rule Evaluator                                 │
│  - Fetch enabled rules for course/roster type               │
│  - Check conditions (attendance %, roster type)             │
│  - Determine action (lock/unlock competency)                │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│           Competency Framework + Audit + Metrics            │
│  - Update competency status via core_competency API        │
│  - Record evidence                                          │
│  - Log to audit trail (local_sceh_rules_audit)              │
│  - Increment metrics (local_sceh_rules_metrics)             │
└─────────────────────────────────────────────────────────────┘
```

**Key Tables:**
- `mdl_local_sceh_attendance_rules`: Attendance rule definitions
- `mdl_local_sceh_roster_rules`: Roster rule definitions
- `mdl_local_sceh_rules_audit`: Evaluation history (PII: userid)
- `mdl_local_sceh_rules_metrics`: Daily-bucketed telemetry counters
- `mdl_competency`: Competency definitions
- `mdl_competency_usercomp`: User competency status
- `mdl_competency_evidence`: Competency evidence records

---

### Badge Issuance Flow

```
┌─────────────────────────────────────────────────────────────┐
│           Competency Completion Event                       │
│     (User achieves required competency level)               │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│              Badge Criteria Evaluation                      │
│  1. Check badge criteria (competency-based)                 │
│  2. Verify all required competencies achieved               │
│  3. Check if badge already awarded                          │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│              Badge Issuance                                 │
│  - Create badge_issued record                               │
│  - Generate badge image                                     │
│  - Send notification to user                                │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│           User Dashboard                                    │
│  - Display badge in profile                                 │
│  - Show in block_sceh_dashboard                             │
│  - Allow download/share                                     │
└─────────────────────────────────────────────────────────────┘
```

**Key Tables:**
- `mdl_badge`: Badge definitions
- `mdl_badge_criteria`: Badge criteria (competency-based)
- `mdl_badge_issued`: Issued badges
- `mdl_badge_manual_award`: Manual badge awards

---

## Plugin Architecture

### Custom Plugins

```
┌─────────────────────────────────────────────────────────────┐
│                    Moodle Core                              │
└────────────────────────┬────────────────────────────────────┘
                         │
        ┌────────────────┼────────────────┐
        │                │                │
        ▼                ▼                ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│local_sceh_   │  │block_sceh_   │  │local_kirk-   │
│rules         │  │dashboard     │  │patrick_level4│
└──────┬───────┘  └──────┬───────┘  └──────┬───────┘
       │                 │                 │
       │                 │                 │
       ▼                 ▼                 ▼
┌──────────────────────────────────────────────────┐
│         Moodle Event System                      │
│  - Observers                                     │
│  - Scheduled Tasks                               │
│  - Adhoc Tasks                                   │
└──────────────────────────────────────────────────┘
```

---

### local_sceh_rules (Rules Engine)

**Purpose:** Automate competency progression based on attendance and roster completion

**Components:**

```
local_sceh_rules/
├── classes/
│   ├── engine/
│   │   ├── rule_evaluator.php       # Abstract base evaluator
│   │   └── event_handler.php        # Abstract base event handler
│   ├── rules/
│   │   ├── attendance_rule.php      # Attendance rule evaluation
│   │   └── roster_rule.php          # Roster rule evaluation
│   ├── observer/
│   │   ├── attendance_observer.php  # Queues adhoc tasks on attendance events
│   │   └── roster_observer.php      # Queues adhoc tasks on roster events
│   ├── task/
│   │   └── evaluate_rules_task.php  # Adhoc task with lock_factory locking
│   ├── helper/
│   │   ├── transaction_helper.php   # DB transaction wrapper
│   │   └── metrics_collector.php    # Telemetry (daily counters)
│   ├── privacy/
│   │   └── provider.php             # GDPR Privacy API
│   └── form/
│       └── rule_form.php            # Rule creation form
├── db/
│   ├── access.php                   # Capabilities
│   ├── events.php                   # Event observers
│   └── install.xml                  # Database schema (4 tables)
└── version.php                      # Plugin metadata (deps: mod_attendance)
```

**Database Tables:**
- `mdl_local_sceh_attendance_rules`: Attendance-based rules
  - `id`, `competencyid`, `courseid`, `threshold`, `enabled`, `timecreated`, `timemodified`
- `mdl_local_sceh_roster_rules`: Roster-to-competency rules
  - `id`, `rostertype`, `competencyid`, `evidencedesc`, `enabled`, `timecreated`, `timemodified`
- `mdl_local_sceh_rules_audit`: Evaluation history (PII: userid)
  - `id`, `ruletype`, `ruleid`, `userid`, `action`, `details`, `timecreated`
- `mdl_local_sceh_rules_metrics`: Telemetry counters
  - `id`, `ruletype`, `ruleid`, `metric_date`, `success_count`, `failure_count`, `total_duration_ms`, `last_error`, `timemodified`

**Event Observers:**
- `\mod_attendance\event\attendance_taken` → `attendance_observer::attendance_taken()`
- `\mod_scheduler\event\appointment_added` → `roster_observer::appointment_added()`

**Capabilities:**
- `local/sceh_rules:manage` - Create/edit rules
- `local/sceh_rules:view` - View rules and audit logs

---

### block_sceh_dashboard (Dashboard Block)

**Purpose:** Display learner progress, competencies, and badges

**Components:**

```
block_sceh_dashboard/
├── block_sceh_dashboard.php         # Block class
├── classes/
│   ├── output/
│   │   ├── dashboard_view.php       # Renderable
│   │   └── renderer.php             # Renderer
│   └── privacy/
│       └── provider.php             # GDPR compliance
├── templates/
│   ├── dashboard.mustache           # Main template
│   ├── competency_card.mustache     # Competency display
│   └── badge_card.mustache          # Badge display
├── db/
│   └── access.php                   # Capabilities
└── version.php                      # Plugin metadata
```

**Displayed Data:**
- Enrolled courses
- Competency progress (per course)
- Badges earned
- Upcoming activities
- Recent grades

**Capabilities:**
- `block/sceh_dashboard:myaddinstance` - Add to My Moodle
- `block/sceh_dashboard:addinstance` - Add to course

---

### local_kirkpatrick_level4 (ROI Tracking)

**Purpose:** Track Level 4 Kirkpatrick evaluation (business impact/ROI)

**Components:**

```
local_kirkpatrick_level4/
├── classes/
│   ├── task/
│   │   └── sync_external_data.php   # Sync external ROI data
│   ├── external/
│   │   └── api.php                  # External API integration
│   └── report/
│       └── roi_report.php           # ROI reporting
├── db/
│   ├── install.xml                  # Database schema
│   └── tasks.php                    # Scheduled tasks
└── version.php                      # Plugin metadata
```

**Database Tables:**
- `mdl_local_kirkpatrick_l4_data`: External ROI data
  - `id`, `userid`, `courseid`, `metric`, `value`, `timecreated`

**Scheduled Tasks:**
- `sync_external_data` - Runs daily, syncs external ROI data

---

### local_sceh_importer (Course Package Importer)

**Purpose:** Import course packages with versioning and conflict detection

**Components:**

```
local_sceh_importer/
├── index.php                        # Main UI (1,287 lines - needs refactor)
├── classes/
│   ├── importer.php                 # Import logic
│   ├── validator.php                # Package validation
│   └── version_manager.php          # Version conflict detection
├── db/
│   ├── access.php                   # Capabilities
│   └── install.xml                  # Database schema
└── version.php                      # Plugin metadata
```

**Capabilities:**
- `local/sceh_importer:manage` - Import course packages

**Known Issues:**
- Monolithic `index.php` (1,287 lines)
- Hardcoded role checks bypass capability system

---

## Third-Party Plugins

### mod_attendance

**Purpose:** Track learner attendance in sessions

**Integration:**
- Rules engine watches `attendance_taken` events
- Attendance % used in rule conditions
- Triggers competency locks/unlocks

**Database Tables:**
- `mdl_attendance`: Attendance instances
- `mdl_attendance_sessions`: Individual sessions
- `mdl_attendance_log`: Attendance records

---

### mod_scheduler

**Purpose:** Schedule appointments (roster sessions)

**Integration:**
- Rules engine watches `appointment_added` events
- Roster type determines competency progression
- Placeholder implementation (needs completion)

**Database Tables:**
- `mdl_scheduler`: Scheduler instances
- `mdl_scheduler_slots`: Available time slots
- `mdl_scheduler_appointment`: Booked appointments

---

### block_xp / block_stash

**Purpose:** Gamification (XP, levels, items)

**Integration:**
- Standalone gamification layer
- No direct integration with rules engine
- Displays in learner dashboard

**Considerations:**
- Third-party plugins (upgrade lag risk)
- Vendor lock-in (data schema specific)
- Alternative: Native badges + competencies

---

## Database Schema

### Core Tables (Moodle)

```
mdl_user                    # User accounts
├── id
├── username
├── email
├── firstname
├── lastname
└── lastaccess

mdl_course                  # Courses (programs)
├── id
├── fullname
├── shortname
├── category
└── visible

mdl_role                    # Roles
├── id
├── shortname
├── name
└── archetype

mdl_role_assignments        # User role assignments
├── id
├── roleid
├── userid
├── contextid
└── timemodified

mdl_competency              # Competency definitions
├── id
├── shortname
├── description
├── competencyframeworkid
└── parentid

mdl_competency_usercomp     # User competency status
├── id
├── userid
├── competencyid
├── status
├── proficiency
└── timemodified

mdl_badge                   # Badge definitions
├── id
├── name
├── description
├── courseid
└── status

mdl_badge_issued            # Issued badges
├── id
├── badgeid
├── userid
├── dateissued
└── dateexpire
```

---

### Custom Tables (SCEH Plugins)

```
mdl_local_sceh_attendance_rules  # Attendance-based rules
├── id
├── competencyid            # FK → competency
├── courseid                # FK → course
├── threshold               # Minimum attendance % (0-100)
├── enabled
├── timecreated
└── timemodified

mdl_local_sceh_roster_rules     # Roster-to-competency rules
├── id
├── rostertype              # 'morning', 'night', 'training', etc.
├── competencyid            # FK → competency
├── evidencedesc            # Auto-generated evidence text
├── enabled
├── timecreated
└── timemodified

mdl_local_sceh_rules_audit      # Rule evaluation history
├── id
├── ruletype                # 'attendance' or 'roster'
├── ruleid
├── userid                  # PII — handled by Privacy API
├── action                  # 'blocked', 'awarded', etc.
├── details                 # JSON
└── timecreated

mdl_local_sceh_rules_metrics    # Telemetry (daily buckets)
├── id
├── ruletype
├── ruleid
├── metric_date             # 'YYYY-MM-DD'
├── success_count
├── failure_count
├── total_duration_ms
├── last_error
└── timemodified

mdl_local_kirkpatrick_l4_data   # ROI data
├── id
├── userid
├── courseid
├── metric                  # 'job_placement', 'salary_increase'
├── value
├── timecreated
└── source                  # 'external_api', 'manual'
```

---

## Security Architecture

### Authentication Flow

```
┌──────────┐
│  User    │
└────┬─────┘
     │ Username/Password
     ▼
┌────────────────┐
│  Moodle Auth   │
│   (Manual)     │
└────┬───────────┘
     │
     ├─────────────────┐
     │                 │
     ▼                 ▼
┌─────────────┐   ┌──────────────┐
│   Session   │   │  Role Check  │
│   Cookie    │   │  (RBAC)      │
└─────────────┘   └──────────────┘
```

**Authentication Methods:**
- Manual accounts (default)
- Email-based self-registration (optional)
- LDAP/AD integration (future)
- OAuth2/SAML (future)

---

### Authorization (RBAC)

**Custom Roles:**

```
sceh_program_owner
├── Capabilities:
│   ├── moodle/course:create
│   ├── moodle/course:update
│   ├── moodle/competency:competencymanage
│   ├── local/sceh_rules:manage
│   └── local/sceh_importer:manage
└── Context: Category

sceh_trainer
├── Capabilities:
│   ├── moodle/course:view
│   ├── moodle/course:activityvisibility
│   ├── mod/attendance:takeattendances
│   ├── mod/scheduler:manage
│   └── moodle/grade:edit
└── Context: Course

sceh_fellow (Learner)
├── Capabilities:
│   ├── moodle/course:view
│   ├── mod/attendance:view
│   ├── mod/scheduler:viewslots
│   └── block/sceh_dashboard:myaddinstance
└── Context: Course
```

---

### Secrets Management

**Current State (.env files):**
```
SCEH_DB_PASSWORD=<password>
SCEH_DB_ROOT_PASSWORD=<root_password>
SCEH_ADMIN_PASSWORD=<admin_password>
```

**Issues:**
- Plaintext on disk
- No encryption at rest
- No audit trail
- No rotation mechanism

**Recommended (Production):**
- Azure Key Vault
- AWS Secrets Manager
- HashiCorp Vault

---

## Deployment Architecture

### Development Environment

```
Developer Machine
├── Docker Desktop
│   ├── moodle_web (localhost:8080)
│   ├── moodle_cron
│   └── mysql (localhost:3306)
├── Git Repository
│   ├── Custom plugins (bind-mounted)
│   └── Configuration scripts
└── .env file (secrets)
```

**Characteristics:**
- Bind-mounted plugin code (live editing)
- Local MySQL (no persistence across rebuilds)
- No SSL/HTTPS
- Debug mode enabled

---

### Production Environment (Recommended)

```
Production Server
├── Docker Compose
│   ├── moodle_web (behind reverse proxy)
│   ├── moodle_cron
│   └── mysql (persistent volume)
├── Nginx Reverse Proxy
│   ├── SSL/TLS termination
│   └── Load balancing (future)
├── Backup System
│   ├── Daily DB backups
│   ├── Weekly full backups
│   └── Off-site storage
└── Monitoring
    ├── Health checks
    ├── Log aggregation
    └── Alerting
```

**Characteristics:**
- Plugins baked into custom Docker image
- Persistent volumes for data
- SSL/HTTPS enabled
- Debug mode disabled
- Automated backups
- Health monitoring

---

## Scaling Architecture

### Vertical Scaling (5,000-10,000 Users)

```
Single Server (Upgraded)
├── 16 cores, 64GB RAM
├── NVMe SSD storage
├── Redis cache
└── Optimized MySQL
```

**Changes:**
- Upgrade server resources
- Enable Redis caching
- Optimize database queries
- Implement CDN for static assets

---

### Horizontal Scaling (10,000-20,000 Users)

```
┌─────────────────────────────────────────────────────────────┐
│                    Load Balancer                            │
└────────────────────────┬────────────────────────────────────┘
                         │
        ┌────────────────┼────────────────┐
        │                │                │
        ▼                ▼                ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│ Web Server 1 │  │ Web Server 2 │  │ Web Server 3 │
└──────┬───────┘  └──────┬───────┘  └──────┬───────┘
       │                 │                 │
       └────────────────┬┴────────────────┘
                        │
        ┌───────────────┼───────────────┐
        │               │               │
        ▼               ▼               ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│   Database   │  │ Redis Cache  │  │ Shared Files │
│   (Primary)  │  │              │  │   (NFS/S3)   │
└──────────────┘  └──────────────┘  └──────────────┘
```

**Changes:**
- Multiple web servers (load balanced)
- Dedicated database server
- Redis cache cluster
- Shared file storage (NFS or S3)
- Separate cron server

---

## Monitoring & Observability

### Health Checks (Recommended)

**Web Container:**
```yaml
healthcheck:
  test: ["CMD", "curl", "-f", "http://localhost/login/index.php"]
  interval: 30s
  timeout: 10s
  retries: 3
  start_period: 60s
```

**Cron Container:**
```yaml
healthcheck:
  test: ["CMD", "test", "-f", "/var/www/html/config.php"]
  interval: 60s
  timeout: 5s
  retries: 3
```

**Database Container:**
```yaml
healthcheck:
  test: ["CMD-SHELL", "mysqladmin ping -h 127.0.0.1 -u$MYSQL_USER -p$MYSQL_PASSWORD --silent"]
  interval: 10s
  timeout: 5s
  retries: 20
```

---

### Metrics to Monitor

**System Metrics:**
- CPU usage (<70% average)
- RAM usage (<80%)
- Disk usage (<80%)
- Disk I/O (<80% capacity)
- Network bandwidth

**Application Metrics:**
- Page load time (<2 seconds)
- Database query time (<100ms average)
- Concurrent users
- Failed login attempts
- Error rate (<1%)

**Business Metrics:**
- Active users (daily/weekly)
- Course enrollments
- Completion rate
- Badge issuance rate
- Rules engine success rate

---

## Disaster Recovery

### Backup Strategy

**Daily Backups:**
- Database dump (compressed)
- Moodledata (incremental)
- Retention: 7 days

**Weekly Backups:**
- Full database dump
- Full moodledata backup
- Plugin code backup
- Retention: 4 weeks

**Monthly Backups:**
- Full system backup
- Off-site storage (cloud)
- Retention: 12 months

---

### Recovery Procedures

**Database Corruption:**
1. Stop web container
2. Restore database from latest backup
3. Restart web container
4. Verify data integrity

**File System Corruption:**
1. Stop web container
2. Restore moodledata from latest backup
3. Fix permissions
4. Restart web container
5. Verify file access

**Complete System Failure:**
1. Provision new server
2. Install Docker + Docker Compose
3. Restore database from backup
4. Restore moodledata from backup
5. Restore plugin code from git
6. Update config.php with new server details
7. Start containers
8. Verify system functionality

**RTO (Recovery Time Objective):** 4-8 hours  
**RPO (Recovery Point Objective):** 24 hours (daily backups)

---

## Next Steps

### Immediate Improvements
1. ✅ Add health checks to cron container
2. ☐ Bake plugins into custom Docker image (see `docs/PRE_PRODUCTION_CHANGES.md`)
3. ☐ Remove Bitnami symlinks from start-web.sh (deferred to pre-production)
4. ✅ Add `SCEH_DB_*` env vars to cron container
5. ✅ Add Privacy API provider to local_sceh_rules
6. ✅ Convert observers to async adhoc tasks with locking
7. ✅ Add telemetry metrics table

### Short-Term Improvements
1. ☐ Implement Redis caching
2. ☐ Add monitoring dashboard (Prometheus + Grafana)
3. ☐ Automate backup verification
4. ☐ Create system architecture diagram (visual)

### Long-Term Improvements
1. ☐ Migrate to horizontal scaling architecture
2. ☐ Implement proper secrets management (Key Vault)
3. ☐ Add comprehensive integration tests
4. ☐ Plan for Moodle 6.x upgrade

---

**Document Version:** 1.0  
**Last Updated:** 2026-02-23  
**Next Review:** 2026-05-23 (Quarterly)
