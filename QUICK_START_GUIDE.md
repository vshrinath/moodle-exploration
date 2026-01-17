# Quick Start Guide: Attendance and Gamification Plugins

## ✓ Task 2.5 Complete

Three plugins have been successfully installed and are ready for activation:

1. **Attendance Plugin** - Session management and tracking
2. **Level Up! Plugin** - XP points and gamification
3. **Custom Certificate Plugin** - Digital credentials

## Activate Plugins (Required)

The plugin files are installed but need database activation:

### 1. Access Moodle Admin
```
URL: http://localhost:8080
Login: Use your administrator credentials
```

### 2. Navigate to Notifications
```
Site administration > Notifications
```

### 3. Click "Upgrade Moodle database now"
This will detect the new plugins and install them into the database.

### 4. Follow Installation Wizard
Complete the installation for each plugin:
- Attendance Plugin (mod_attendance)
- Level Up! Plugin (block_xp)
- Custom Certificate Plugin (mod_customcert)

## Verify Installation

After activation, verify plugins are working:

```bash
docker exec moodle-exploration-moodle-1 php /tmp/verify_attendance_gamification.php
```

Expected output: All 3 plugins should show as "INSTALLED"

## Configure Plugins

### Attendance Plugin
1. Go to: Site administration > Plugins > Activity modules > Attendance
2. Configure default attendance statuses
3. Set up gradebook integration

### Level Up! Plugin
1. Add "Level Up!" block to a course
2. Configure XP rules and point values
3. Set up level progression thresholds
4. Enable/disable leaderboards

### Custom Certificate Plugin
1. Go to: Site administration > Plugins > Activity modules > Custom certificate
2. Create certificate templates
3. Link templates to competency completion
4. Enable certificate verification

## Quick Configuration Script

Run the automated configuration helper:

```bash
docker cp configure_attendance_gamification.php moodle-exploration-moodle-1:/tmp/
docker exec moodle-exploration-moodle-1 php /tmp/configure_attendance_gamification.php
```

## Requirements Met

✓ **14.1** - Attendance tracking for session management  
✓ **15.2** - Custom Certificate for credentialing  
✓ **16.1** - Level Up! and Badges for gamification

## Optional: Stash Plugin

If you need collectible rewards (Stash plugin), see:
- `install_stash_manual.md` for manual installation steps

Note: Stash is optional - Level Up! + Moodle Badges provide comprehensive gamification.

## Integration with Competency Framework

Once activated, these plugins integrate with the competency framework:

- **Attendance** → Competency completion requirements
- **Level Up!** → XP for competency achievements
- **Certificates** → Issued upon competency completion
- **Badges** → Awarded for competency milestones

## Support Files

- `install_attendance_gamification.sh` - Installation script
- `verify_attendance_gamification.php` - Verification script
- `test_attendance_gamification_integration.php` - Integration tests
- `configure_attendance_gamification.php` - Configuration helper
- `TASK_2.5_COMPLETION_REPORT.md` - Detailed completion report
- `install_stash_manual.md` - Manual Stash installation guide

## Next Steps

1. ✓ Plugins installed (DONE)
2. → Activate via Moodle admin interface (YOU ARE HERE)
3. → Configure plugin settings
4. → Test in a sample course
5. → Integrate with competency framework
6. → Move to next task (2.6 or 3.1)

## Need Help?

- Review: `TASK_2.5_COMPLETION_REPORT.md` for detailed information
- Check: Integration test results for troubleshooting
- Consult: Moodle documentation for plugin-specific guidance
