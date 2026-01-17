# Fellowship Plugins Quick Start Guide

## Installation (5 Steps)

```bash
# Step 1: Install plugins
bash install_fellowship_plugins.sh

# Step 2: Run Moodle upgrade
php admin/cli/upgrade.php --non-interactive

# Step 3: Configure plugins
php configure_fellowship_plugins.php

# Step 4: Verify installation
php verify_fellowship_setup.php

# Step 5: Run integration tests
php test_fellowship_integration.php
```

## What Gets Installed

### 1. Database Activity Plugin (Core)
- ✓ Already included in Moodle
- Used for: Case logbooks, credentialing sheets, research tracking

### 2. Scheduler Plugin (Third-party)
- Installed from: https://github.com/bostelm/moodle-mod_scheduler
- Used for: Rotation scheduling, mentor meetings

### 3. Payment Gateways
- PayPal (core, Moodle 4.0+)
- Razorpay (optional, install separately)
- Stripe (optional, install separately)
- Used for: Registration fee collection

### 4. Custom Profile Fields (9 fields)
- Fellowship Type
- Primary Subspecialty
- Secondary Subspecialty
- Medical Registration Number
- Emergency Contact
- Training Start/End Dates
- Assigned Mentor
- Alumni Status

## Key Files Created

| File | Purpose |
|------|---------|
| `install_fellowship_plugins.sh` | Automated installation |
| `configure_fellowship_plugins.php` | Plugin configuration |
| `verify_fellowship_setup.php` | Verification checks |
| `test_fellowship_integration.php` | Integration testing |
| `FELLOWSHIP_PLUGINS_GUIDE.md` | Complete documentation |
| `DATABASE_ACTIVITY_TEMPLATES.md` | Template specifications |

## Database Activity Templates to Create

### 1. Case Logbook Template
**Fields**: Date, Subspecialty, Procedure Type, Details, Outcome, Complications, Learning Points, Mentor Approval, Feedback

**Use**: Track surgical cases with mentor approval workflow

### 2. Credentialing Sheet Template
**Fields**: Month/Year, Procedure counts (7 subspecialties), Competencies Achieved, Mentor Verification, Comments

**Use**: Monthly procedure tracking and competency verification

### 3. Research Publications Template
**Fields**: Title, Year, Journal, Authors, Link, Research Type, Status, Mentor Review, Comments

**Use**: Track research projects and publications

## Five Key Workflows

### 1. Trainee Registration
Trainee → Profile Fields → Payment → Admin Approval → Enrollment

### 2. Case Logbook
Trainee Logs Case → Mentor Reviews → Approval → Competency Evidence

### 3. Rotation Scheduling
Admin Creates Roster → Bulk Upload → Reminders → Attendance → Competency

### 4. Credentialing
Monthly Submission → Procedure Counts → Mentor Verification → Historical Record

### 5. Research Tracking
Project Submission → Mentor Review → Status Updates → Portfolio Generation

## Integration Points

- **Competency Framework**: All activities link to competencies
- **Attendance Plugin**: Rotation attendance tracked
- **Badge System**: Milestones earn badges
- **Kirkpatrick Evaluation**: Evidence for all 4 levels

## Troubleshooting

### Scheduler not found?
```bash
cd mod
git clone https://github.com/bostelm/moodle-mod_scheduler.git scheduler
cd ..
php admin/cli/upgrade.php --non-interactive
```

### Profile fields not showing?
Run: `php configure_fellowship_plugins.php`

### Payment gateway not available?
Requires Moodle 4.0+ or use enrolment plugins

## Next Steps

1. ✓ Plugins installed (Task 2.7)
2. → Create Database Activity templates (Task 11.1)
3. → Configure competency framework (Task 3)
4. → Set up fellowship courses
5. → Test complete workflows

## Support

- Full Guide: `FELLOWSHIP_PLUGINS_GUIDE.md`
- Completion Report: `TASK_2.7_COMPLETION_REPORT.md`
- Template Specs: `DATABASE_ACTIVITY_TEMPLATES.md`

## Requirements Addressed

✓ Requirement 18.1 - Case and Surgical Logbook Management  
✓ Requirement 19.1 - Credentialing Sheet Documentation  
✓ Requirement 20.1 - Rotation and Roster Management  
✓ Requirement 21.1 - Registration and Onboarding  
✓ Requirement 25.1 - Research and Publications Management
