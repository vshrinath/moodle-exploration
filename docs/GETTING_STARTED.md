# Getting Started — Moodle Fellowship Training System

**Quick start guide for new team members**

---

## Prerequisites

- Docker and Docker Compose installed
- Git installed
- Basic understanding of Moodle concepts (courses, cohorts, roles)
- Terminal/command line access

---

## 1. Clone and Setup (5 minutes)

```bash
# Clone repository
git clone <repository-url>
cd moodle-exploration

# Copy environment template
cp .env.example .env

# Review and update .env if needed
# Default values work for local development
```

---

## 2. Start the System (2 minutes)

```bash
# Start Docker containers
docker-compose up -d

# Wait for services to initialize (~30 seconds)
docker-compose logs -f moodle
# Press Ctrl+C when you see "Apache started"
```

**Access Moodle:**
- URL: http://localhost:8080
- Admin credentials: Check `.env` file or container logs

---

## 3. Verify Installation (5 minutes)

```bash
# Check system health
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/scripts/verify/verify_moodle_setup.php

# Expected output: System checks passing
```

**In browser:**
1. Log in as admin
2. Navigate to: Site administration → Notifications
3. Verify no critical errors

---

## 4. Understand the Structure (10 minutes)

### Key Directories

```
moodle-exploration/
├── docs/                          # All documentation
│   ├── GETTING_STARTED.md        # This file
│   ├── ALLIED_HEALTH_FOUNDATIONAL_COURSE_WORKFLOW.md  # Main workflow guide
│   ├── OPERATIONS_GUIDE.md       # Backup, reporting, grading
│   └── PHASE_3_COHORT_LIFECYCLE_TEST_REPORT.md  # Latest test results
├── scripts/
│   ├── config/                   # Configuration scripts
│   ├── verify/                   # Verification scripts
│   ├── test/                     # Test scripts
│   └── lib/                      # Shared utilities
├── test_content/                 # Course content for testing
│   └── Allied Health Program/    # Allied Health course materials
└── .env                          # Environment configuration
```

### Key Documentation

**Start here:**
1. `ALLIED_HEALTH_FOUNDATIONAL_COURSE_WORKFLOW.md` — Complete workflow for Allied Health course
2. `OPERATIONS_GUIDE.md` — Backup, reporting, grading procedures
3. `MOCK_USERS_SETUP.md` — Test users and roles

**Reference:**
- `SYSTEM_FAQ.md` — Common questions
- `USER_FAQ.md` — End-user questions
- `PHASE_3_COHORT_LIFECYCLE_TEST_REPORT.md` — Latest validation results

---

## 5. Run Your First Test (10 minutes)

### Test Trainer Visibility Permissions

```bash
# Configure trainer permissions
docker exec moodle-exploration-moodle-1 \
  php /bitnami/moodle/scripts/config/configure_trainer_visibility_permissions.php

# Verify configuration
docker exec moodle-exploration-moodle-1 \
  php /bitnami/moodle/scripts/verify/verify_trainer_visibility_permissions.php

# Expected output: All checks passing
```

### Test Allied Health Quiz Workflow

```bash
# Run quiz workflow test
docker exec moodle-exploration-moodle-1 \
  php /bitnami/moodle/scripts/test/test_allied_health_quiz_workflow.php

# Expected output: Test passed with quiz attempt recorded
```

---

## 6. Access Mock Users (5 minutes)

**Test users are pre-configured:**

| Username | Password | Role | Purpose |
|----------|----------|------|---------|
| mock.learner | Test@123 | Student | Test learner workflows |
| mock.trainer | Test@123 | Trainer | Test trainer workflows |
| mock.owner | Test@123 | Program Owner | Test admin workflows |

**Test login:**
1. Open http://localhost:8080
2. Log in as `mock.learner` / `Test@123`
3. Verify dashboard shows enrolled courses
4. Log out and try other users

---

## 7. Explore the Allied Health Course (10 minutes)

**As mock.trainer:**
1. Navigate to: Allied Health - Foundational (Automation)
2. Turn editing on
3. Observe structure:
   - Trainer Resources folders (role-restricted)
   - Day Content folders (visibility-controlled)
   - Quiz and assignment activities
4. Try toggling visibility on a Day Content folder (eye icon)

**As mock.learner:**
1. Navigate to same course
2. Verify Trainer Resources are completely invisible
3. Verify Day Content folders show as hidden
4. Attempt a quiz if visible

---

## 8. Common Tasks

### View Logs

```bash
# Moodle application logs
docker-compose logs -f moodle

# Database logs
docker-compose logs -f mariadb

# All services
docker-compose logs -f
```

### Restart Services

```bash
# Restart all services
docker-compose restart

# Restart specific service
docker-compose restart moodle
```

### Access Database

```bash
# MySQL client
docker exec -it moodle-exploration-mariadb-1 mysql -u bn_moodle -p bitnami_moodle
# Password from .env file

# Run query
SELECT id, username, email FROM mdl_user WHERE deleted=0 LIMIT 5;
```

### Clear Moodle Cache

```bash
docker exec moodle-exploration-moodle-1 \
  php /bitnami/moodle/admin/cli/purge_caches.php
```

### Run Configuration Script

```bash
# Pattern: docker exec <container> php /bitnami/moodle/scripts/config/<script>.php
docker exec moodle-exploration-moodle-1 \
  php /bitnami/moodle/scripts/config/configure_attendance_tracking.php
```

### Run Verification Script

```bash
# Pattern: docker exec <container> php /bitnami/moodle/scripts/verify/<script>.php
docker exec moodle-exploration-moodle-1 \
  php /bitnami/moodle/scripts/verify/verify_attendance_tracking.php
```

---

## 9. Development Workflow

### Making Changes

1. **Edit files locally** — Changes sync to container automatically
2. **Test changes** — Run verification scripts
3. **Clear cache** — If needed: `purge_caches.php`
4. **Verify in browser** — Test as different users

### Adding New Configuration

1. Create script in `scripts/config/`
2. Follow existing patterns (see `configure_trainer_visibility_permissions.php`)
3. Create matching verification script in `scripts/verify/`
4. Document in relevant workflow guide
5. Test with mock users

### Running Tests

```bash
# Run all property tests
for test in scripts/test/property_test_*.php; do
  echo "Running $(basename $test)..."
  docker exec moodle-exploration-moodle-1 php /bitnami/moodle/$test
done

# Run specific test
docker exec moodle-exploration-moodle-1 \
  php /bitnami/moodle/scripts/test/property_test_role_based_access_control.php
```

---

## 10. Troubleshooting

### Container won't start

```bash
# Check container status
docker-compose ps

# View error logs
docker-compose logs moodle

# Restart from scratch
docker-compose down
docker-compose up -d
```

### Can't access Moodle web interface

```bash
# Check if port 8080 is available
lsof -i :8080

# Try alternative port (edit docker-compose.yml)
# Change "8080:8080" to "8081:8080"
docker-compose up -d
```

### Database connection errors

```bash
# Verify database is running
docker-compose ps mariadb

# Check database logs
docker-compose logs mariadb

# Verify credentials in .env match config.php
```

### Permission errors in container

```bash
# Fix file permissions
docker exec moodle-exploration-moodle-1 \
  chown -R daemon:daemon /bitnami/moodledata
```

### Script not found

```bash
# Verify script exists
docker exec moodle-exploration-moodle-1 \
  ls -la /bitnami/moodle/scripts/config/

# Check file is executable
docker exec moodle-exploration-moodle-1 \
  chmod +x /bitnami/moodle/scripts/config/your_script.php
```

---

## 11. Next Steps

**After completing this guide:**

1. **Read workflow documentation**
   - `ALLIED_HEALTH_FOUNDATIONAL_COURSE_WORKFLOW.md` — Understand the complete workflow
   - `OPERATIONS_GUIDE.md` — Learn backup, reporting, grading procedures

2. **Explore test content**
   - Review `test_content/Allied Health Program/` structure
   - Understand Week/Day organization

3. **Run validation suite**
   - Execute all verification scripts
   - Review test reports in `docs/`

4. **Set up your first course**
   - Follow Allied Health workflow guide
   - Use mock users for testing
   - Validate with verification scripts

5. **Join team discussions**
   - Review `PHASE_3_COHORT_LIFECYCLE_TEST_REPORT.md` for latest findings
   - Check `KNOWN_LIMITATIONS.md` for current constraints

---

## Quick Reference

### Essential Commands

```bash
# Start system
docker-compose up -d

# Stop system
docker-compose down

# View logs
docker-compose logs -f moodle

# Run script
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/scripts/<path>/<script>.php

# Clear cache
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/admin/cli/purge_caches.php

# Access database
docker exec -it moodle-exploration-mariadb-1 mysql -u bn_moodle -p bitnami_moodle
```

### Essential URLs

- Moodle: http://localhost:8080
- Site administration: http://localhost:8080/admin
- Course management: http://localhost:8080/course/management.php
- User management: http://localhost:8080/admin/user.php

### Essential Documentation

- Workflow: `docs/ALLIED_HEALTH_FOUNDATIONAL_COURSE_WORKFLOW.md`
- Operations: `docs/OPERATIONS_GUIDE.md`
- Test Results: `docs/PHASE_3_COHORT_LIFECYCLE_TEST_REPORT.md`
- FAQ: `docs/SYSTEM_FAQ.md`

---

## Getting Help

**Check documentation first:**
1. `docs/SYSTEM_FAQ.md` — Common questions
2. `docs/TROUBLESHOOTING.md` — Known issues (if exists)
3. Test reports in `docs/` — Latest validation results

**Still stuck?**
- Review container logs: `docker-compose logs -f`
- Check Moodle logs: Site administration → Reports → Logs
- Verify system health: Run verification scripts

---

**System Version**: Moodle 5.0.1  
**Last Updated**: 2026-02-21  
**Estimated Setup Time**: 45 minutes
