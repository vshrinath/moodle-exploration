# Checkpoint 13: Quick Fix Guide

## Critical Issues - Immediate Resolution Required

### Issue 1: Competency Framework Not Enabled

**Problem:** Core competency tracking functionality is unavailable.

**Solution:**

1. Log in to Moodle as administrator
2. Navigate to: **Site administration → Advanced features**
3. Find: **Enable competencies** checkbox
4. Check the box to enable
5. Click **Save changes**

**Verification:**
```bash
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/checkpoint_validation.php 2>&1 | grep "Framework Enabled"
```

Expected output: `✓ PASS: [Competency] Framework Enabled`

---

### Issue 2: Rules Engine Database Tables Not Created

**Problem:** The local_sceh_rules plugin is installed but database tables haven't been created.

**Solution:**

1. Log in to Moodle as administrator
2. Navigate to: **Site administration → Notifications**
3. You should see a notification about plugin updates
4. Click: **Upgrade Moodle database now**
5. Wait for the upgrade process to complete
6. Confirm success message

**Alternative Solution (CLI):**
```bash
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/admin/cli/upgrade.php
```

**Verification:**
```bash
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/checkpoint_validation.php 2>&1 | grep "Rules Engine"
```

Expected output:
```
✓ PASS: [Rules Engine] Plugin Installed
✓ PASS: [Rules Engine] Plugin Registered
✓ PASS: [Rules Engine] Attendance Rules Table
✓ PASS: [Rules Engine] Roster Rules Table
✓ PASS: [Rules Engine] Event Observers
```

---

### Issue 3: Rules Engine Plugin Not Registered

**Problem:** Plugin files are present but Moodle hasn't registered the plugin.

**Solution:**

This will be resolved automatically when you complete Issue 2 (database upgrade).

**Manual Verification:**
1. Navigate to: **Site administration → Plugins → Plugins overview**
2. Search for: **local_sceh_rules**
3. Confirm it appears in the list with version information

---

## Quick Validation Command

After completing the fixes, run the full validation:

```bash
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/checkpoint_validation.php
```

**Expected Result:**
- ✓ Passed: 31 tests (up from 28)
- ⚠ Warnings: 11 tests (configuration optional)
- ✗ Failed: 0 tests (down from 3)

---

## Troubleshooting

### If Competency Framework Won't Enable

**Check Moodle version:**
```bash
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/admin/cli/cfg.php --name=version
```

Competency framework requires Moodle 3.1 or higher.

### If Database Upgrade Fails

**Check for errors:**
```bash
docker exec moodle-exploration-moodle-1 tail -100 /bitnami/moodle/moodledata/error.log
```

**Common issues:**
- Database permissions
- Syntax errors in install.xml
- Missing dependencies

**Force plugin reinstall:**
```bash
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/admin/cli/uninstall_plugins.php --plugins=local_sceh_rules --run
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/admin/cli/upgrade.php
```

### If Plugin Still Not Registered

**Clear Moodle caches:**
```bash
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/admin/cli/purge_caches.php
```

**Check plugin version file:**
```bash
docker exec moodle-exploration-moodle-1 cat /bitnami/moodle/local/sceh_rules/version.php
```

Ensure it contains valid PHP and version information.

---

## Post-Fix Validation Checklist

- [ ] Competency framework enabled
- [ ] Rules engine database tables created
- [ ] Rules engine plugin registered
- [ ] Event observers active
- [ ] Full validation script passes with 0 failures
- [ ] Admin can access rules configuration pages
- [ ] Test attendance rule creation
- [ ] Test roster rule creation

---

## Support

If issues persist after following this guide:

1. Check the full validation report: `CHECKPOINT_13_VALIDATION_REPORT.md`
2. Review Moodle error logs
3. Verify Docker container is running properly
4. Ensure database connectivity

---

**Last Updated:** January 17, 2026  
**Validation Script:** checkpoint_validation.php
