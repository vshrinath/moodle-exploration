# Database Templates - Admin Quick Start Guide

## 5-Minute Setup Guide

### Step 1: Download Templates (1 minute)
- Locate the three XML files in `database_templates/` directory:
  - `case_logbook_template.xml`
  - `credentialing_sheet_template.xml`
  - `research_publications_template.xml`

### Step 2: Create Database Activity (2 minutes)
1. Go to your Moodle course
2. Turn editing on
3. Click "Add an activity or resource"
4. Select "Database"
5. Enter activity name (e.g., "Case Logbook")
6. Click "Save and display"

### Step 3: Import Template (2 minutes)
1. Click "Presets" tab
2. Click "Import"
3. Choose "Upload preset"
4. Select the XML file
5. Click "Import"
6. Click "Continue" to confirm

**Done!** Your database is ready to use.

## Essential Settings Checklist

After importing, configure these critical settings:

### For Case Logbook
- ✅ Enable "Require approval" (Settings → Approval)
- ✅ Assign mentors as teachers with approval rights
- ✅ Set monthly submission windows (Settings → Availability)
- ✅ Enable notifications for new submissions

### For Credentialing Sheet
- ✅ Enable "Require approval"
- ✅ Set "Maximum entries" to 1 per month
- ✅ Configure monthly submission deadlines
- ✅ Set up automated reminders

### For Research Publications
- ✅ Enable "Require approval" for proposals
- ✅ Enable search functionality
- ✅ Configure mentor review notifications
- ✅ Set up tags/categories for organization

## Common Tasks

### Adding a Mentor/Approver
1. Go to course participants
2. Enroll user with "Teacher" role
3. Verify they have "mod/data:approve" capability
4. Test approval workflow

### Exporting Data
1. Open Database activity
2. Click "Export" tab
3. Select export format (CSV, Excel, ODS)
4. Choose fields to export
5. Download file

### Creating Reports
1. Install Configurable Reports plugin (if not installed)
2. Create new report
3. Select Database activity as source
4. Add desired fields and filters
5. Schedule automated generation

### Backing Up Database
1. Go to course administration
2. Click "Backup"
3. Select Database activity
4. Include user data
5. Execute backup
6. Download backup file

## Troubleshooting Quick Fixes

### Problem: Import fails
**Fix:** Ensure you're using Moodle 3.9+ and Database module is enabled

### Problem: Mentors can't approve
**Fix:** Check role permissions → Ensure "mod/data:approve" is allowed

### Problem: No notifications
**Fix:** Site administration → Messaging → Enable database notifications

### Problem: Fields not showing
**Fix:** Go to Templates tab → Check "Add entry" template is configured

## Training Resources

### For Trainees
- Show them how to add entries
- Explain required vs optional fields
- Demonstrate submission process
- Clarify approval workflow

### For Mentors
- Explain approval process
- Show how to provide feedback
- Demonstrate bulk approval
- Clarify expectations

## Monthly Maintenance Checklist

- [ ] Check pending approvals
- [ ] Review submission compliance
- [ ] Export data for backup
- [ ] Send reminders for overdue submissions
- [ ] Update documentation if needed

## Need More Help?

- **Full Documentation:** See `README.md` in this directory
- **Moodle Docs:** https://docs.moodle.org/en/Database_activity
- **Video Tutorials:** Search "Moodle Database Activity" on YouTube
- **Support:** Contact your Moodle administrator

## Quick Reference: Template Features

| Template | Key Feature | Primary Use |
|----------|-------------|-------------|
| Case Logbook | Subspecialty tracking | Daily case documentation |
| Credentialing | Procedure counts | Monthly competency tracking |
| Research | Publication metadata | Academic portfolio |

## Next Steps

1. ✅ Import all three templates
2. ✅ Configure settings
3. ✅ Train users
4. ✅ Test workflows
5. ✅ Go live!

**Estimated Total Setup Time:** 30-40 minutes per template

---

*Last Updated: January 2026*
*Version: 1.0*
