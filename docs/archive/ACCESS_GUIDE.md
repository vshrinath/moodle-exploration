# Moodle System Access Guide

## 🚀 System Status: RUNNING ✅

Your Moodle competency-based learning system is up and running!

## 🌐 Access Information

### Web Interface
- **URL:** http://localhost:8080
- **HTTPS URL:** https://localhost:8443 (if SSL needed)

### Default Bitnami Credentials
For Bitnami Moodle installations, the default credentials are typically:
- **Username:** `user`
- **Password:** `bitnami`

**OR**

- **Username:** `admin`
- **Password:** Check the container logs or use the password reset

## 🔑 Get Admin Credentials

If you need to retrieve or reset the admin password, run:

```bash
# Check for credentials in container environment
docker exec moodle-exploration-moodle-1 env | grep -i moodle

# Or check the initial setup logs
docker logs moodle-exploration-moodle-1 2>&1 | grep -i "password\|credential" | head -20
```

## 🔐 Reset Admin Password (If Needed)

If you can't log in, reset the admin password:

```bash
docker exec -it moodle-exploration-moodle-1 php /bitnami/moodle/admin/cli/reset_password.php
```

Follow the prompts to reset the password for the admin user.

## ✅ What's Already Configured

### Core Features Enabled
- ✅ Competency framework enabled
- ✅ Learning plans enabled
- ✅ Badge system enabled
- ✅ Attendance tracking enabled
- ✅ Portfolio system enabled
- ✅ Completion tracking enabled

### Custom Plugins Installed
- ✅ **local_sceh_rules** (v2026011700) - Rules Engine
  - Attendance-based competency locking
  - Roster-to-competency automation
  - Access: Site administration → Plugins → Local plugins → SCEH Rules Engine

- ✅ **local_kirkpatrick_dashboard** - Unified Evaluation Dashboard
  - Access: Site administration → Reports → Kirkpatrick Dashboard

- ✅ **local_kirkpatrick_level4** - External Data Integration
  - Access: Site administration → Plugins → Local plugins → Kirkpatrick Level 4

### Data Available
- 1 competency framework
- 15 competencies
- 3 learning plans
- 6 cohorts
- 5 attendance sessions

## 🎯 Quick Start Testing

### 1. Log In
1. Open http://localhost:8080 in your browser
2. Click "Log in" (top right)
3. Enter your credentials
4. You should see the Moodle dashboard

### 2. Access Competency Framework
```
Site administration → Competencies → Competency frameworks
```

### 3. View Rules Engine
```
Site administration → Plugins → Local plugins → SCEH Rules Engine
```

### 4. Check Kirkpatrick Dashboard
```
Site administration → Reports → Kirkpatrick Dashboard
```

### 5. View Attendance
```
Navigate to any course → Turn editing on → Add an activity → Attendance
```

## 🧪 Test Features

### Test Competency Framework
1. Go to: Site administration → Competencies → Competency frameworks
2. Click on the existing framework
3. View the 15 competencies
4. Test creating a new competency

### Test Rules Engine
1. Go to: Site administration → Plugins → Local plugins → SCEH Rules Engine
2. Click "Attendance Rules"
3. Try creating a new attendance rule
4. Set a competency and attendance threshold

### Test Learning Plans
1. Go to: Site administration → Competencies → Learning plan templates
2. View existing templates
3. Create a new learning plan template

### Test Cohorts
1. Go to: Site administration → Users → Cohorts
2. View the 6 existing cohorts
3. Test cohort enrollment

## 📊 Run Validation

To verify everything is working:

```bash
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/checkpoint_validation.php
```

Expected result: **32/32 tests passing** ✅

## 🔧 Troubleshooting

### Can't Access Web Interface
```bash
# Check if containers are running
docker ps

# Restart containers if needed
docker-compose restart
```

### Forgot Admin Password
```bash
# Reset password via CLI
docker exec -it moodle-exploration-moodle-1 php /bitnami/moodle/admin/cli/reset_password.php
```

### Need to Clear Cache
```bash
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/admin/cli/purge_caches.php
```

### Check System Status
```bash
# View container logs
docker logs moodle-exploration-moodle-1 --tail 50

# Check database connection
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/admin/cli/check_database_schema.php
```

## 📚 Available Documentation

- **CHECKPOINT_13_SUCCESS_REPORT.md** - Complete validation results
- **CHECKPOINT_13_QUICK_FIX_GUIDE.md** - Troubleshooting guide
- **local_sceh_rules/README.md** - Rules engine documentation
- **local_sceh_rules/USER_GUIDE.md** - User guide for rules
- **local_sceh_rules/ADMIN_GUIDE.md** - Admin configuration guide
- **FELLOWSHIP_CONFIGURATION_GUIDE.md** - Fellowship features setup
- **database_templates/README.md** - Database templates guide

## 🎓 Fellowship Features

### Database Templates Available
Located in `/bitnami/moodle/database_templates/`:
- **case_logbook_template.xml** - Case and surgical logbook
- **credentialing_sheet_template.xml** - Monthly credentialing
- **research_publications_template.xml** - Research tracking

### Import Templates
1. Go to a course
2. Add activity → Database
3. Use "Import preset" to load templates

## 🚀 Next Steps

1. **Log in and explore** the Moodle interface
2. **Test competency framework** features
3. **Configure rules engine** for your needs
4. **Import database templates** for fellowship features
5. **Create test courses** and learning paths
6. **Set up badges** for competency achievements

## 💡 Tips

- Use **Chrome/Firefox** for best compatibility
- Enable **developer tools** (F12) to debug issues
- Check **Site administration → Notifications** for plugin updates
- Review **Site administration → Reports → Logs** for activity tracking

## 📞 Support

If you encounter issues:
1. Check the validation report: `CHECKPOINT_13_SUCCESS_REPORT.md`
2. Review container logs: `docker logs moodle-exploration-moodle-1`
3. Run validation script to identify problems
4. Check Moodle error logs in the container

---

**System Version:** Moodle 5.0.1  
**Validation Status:** ✅ 100% Pass Rate (32/32 tests)  
**Last Updated:** January 17, 2026
