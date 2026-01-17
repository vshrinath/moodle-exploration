# Ophthalmology Fellowship Plugins Guide

## Overview

This guide covers the installation, configuration, and usage of plugins required for the Ophthalmology Fellowship Management System. These plugins enable case logbook management, rotation scheduling, trainee registration, payment processing, and research tracking.

## Requirements Addressed

- **Requirement 18.1**: Case and Surgical Logbook Management
- **Requirement 19.1**: Credentialing Sheet and Performance Documentation
- **Requirement 20.1**: Rotation and Roster Management
- **Requirement 21.1**: Registration and Onboarding System
- **Requirement 25.1**: Research and Publications Management

## Plugins Overview

### 1. Database Activity Plugin (Core)

**Purpose**: Structured data collection for case logbooks, credentialing sheets, and research tracking

**Status**: Built-in Moodle core module

**Key Features**:
- Custom field types (text, number, date, menu, textarea, URL)
- Approval workflows for mentor review
- Export capabilities for accreditation
- Template creation and reuse
- Integration with competency framework

**Use Cases**:
- Case Logbook: Track patient cases with subspecialty categorization
- Credentialing Sheet: Monthly procedure counts and competency tracking
- Research Publications: Track research projects and publications

### 2. Scheduler Plugin

**Purpose**: Rotation scheduling and mentor meeting management

**Status**: Third-party plugin (requires installation)

**Repository**: https://github.com/bostelm/moodle-mod_scheduler

**Key Features**:
- Appointment slot management
- Group scheduling support
- Email notifications and reminders
- Calendar integration
- Bulk operations for roster management

**Use Cases**:
- Morning class schedules
- Night duty rosters
- Training OT rotations
- Satellite visit schedules
- Department posting schedules
- One-on-one mentor meetings

### 3. Payment Gateway Plugins

**Purpose**: Registration fee collection and payment processing

**Available Options**:

#### PayPal (Core - Moodle 4.0+)
- Built-in payment gateway
- No additional installation required
- Requires PayPal business account

#### Razorpay (Third-party)
- Repository: https://github.com/razorpay/moodle-payment_razorpay
- Popular in India
- Supports multiple payment methods

#### Stripe (Third-party)
- Repository: https://github.com/catalyst/moodle-paygw_stripe
- International payment processing
- Credit card and digital wallet support

**Use Cases**:
- Long-term fellowship registration fees
- Short-term training course fees
- Workshop and event payments

### 4. Custom User Profile Fields (Core)

**Purpose**: Extended trainee profile information

**Status**: Built-in Moodle functionality

**Key Features**:
- Multiple field types (text, menu, date, checkbox)
- Required/optional field configuration
- Signup form integration
- Visibility controls

**Profile Fields Created**:
1. Fellowship Type (Long-term/Short-term/Observer)
2. Primary Subspecialty (7 ophthalmology subspecialties)
3. Secondary Subspecialty (optional)
4. Medical Registration Number
5. Emergency Contact
6. Training Start Date
7. Training End Date
8. Assigned Mentor
9. Alumni Status

## Installation Instructions

### Step 1: Run Installation Script

```bash
bash install_fellowship_plugins.sh
```

This script will:
- Verify Database Activity plugin (core)
- Install Scheduler plugin from GitHub
- Check for payment gateway availability
- Verify custom profile fields capability

### Step 2: Run Moodle Upgrade

```bash
php admin/cli/upgrade.php --non-interactive
```

This installs the plugins into the Moodle database.

### Step 3: Configure Plugins

```bash
php configure_fellowship_plugins.php
```

This script will:
- Enable Database Activity module
- Configure Scheduler plugin settings
- Enable payment subsystem (if available)
- Create custom user profile fields
- Generate template documentation

### Step 4: Verify Installation

```bash
php verify_fellowship_setup.php
```

This verifies all plugins are properly installed and configured.

### Step 5: Run Integration Tests

```bash
php test_fellowship_integration.php
```

This tests the integration between all fellowship plugins.

## Configuration Details

### Database Activity Configuration

**Settings**:
- Max file size: 10MB (configurable)
- RSS feeds: Disabled
- Approval workflow: Enabled
- Comments: Enabled

**Templates to Create**:

#### 1. Case Logbook Template

Create a Database Activity with these fields:

| Field Name | Type | Options/Settings |
|------------|------|------------------|
| Date | Date | Required |
| Subspecialty | Menu | Cataract, Retina, Cornea, Glaucoma, Oculoplasty, Pediatric, Neuro |
| Procedure Type | Text | Required |
| Procedure Details | Textarea | Required |
| Patient Outcome | Menu | Excellent, Good, Fair, Poor |
| Complications | Textarea | Optional |
| Learning Points | Textarea | Required |
| Mentor Approval | Menu | Pending, Approved, Needs Revision |
| Mentor Feedback | Textarea | Optional |

**Settings**:
- Approval required: Yes
- Comments enabled: Yes
- Entries per page: 10

#### 2. Credentialing Sheet Template

| Field Name | Type | Options/Settings |
|------------|------|------------------|
| Month/Year | Date | Required |
| Cataract Procedures | Number | Min: 0 |
| Retina Procedures | Number | Min: 0 |
| Cornea Procedures | Number | Min: 0 |
| Glaucoma Procedures | Number | Min: 0 |
| Oculoplasty Procedures | Number | Min: 0 |
| Pediatric Procedures | Number | Min: 0 |
| Neuro Procedures | Number | Min: 0 |
| Competencies Achieved | Textarea | Optional |
| Mentor Verification | Menu | Pending, Verified, Rejected |
| Mentor Comments | Textarea | Optional |

**Settings**:
- Approval required: Yes
- One entry per month: Yes

#### 3. Research Publications Template

| Field Name | Type | Options/Settings |
|------------|------|------------------|
| Title | Text | Required |
| Publication Year | Number | Min: 2000, Max: Current year |
| Journal Name | Text | Required |
| Authors | Textarea | Required |
| Publication Link | URL | Optional |
| Research Type | Menu | Original Research, Case Report, Review Article, Meta-analysis |
| Submission Status | Menu | Draft, Submitted, Under Review, Accepted, Published |
| Mentor Review | Menu | Pending, Approved, Needs Revision |
| Mentor Comments | Textarea | Optional |

**Settings**:
- Approval required: Yes
- Comments enabled: Yes

### Scheduler Configuration

**Settings**:
- All teachers grading: Disabled (only assigned teachers)
- Show email in plain text: Enabled
- Group scheduling: Enabled

**Rotation Types to Configure**:
1. Morning Class Schedule
2. Night Duty Roster
3. Training OT Schedule
4. Satellite Visit Schedule
5. Department Posting Schedule

**Reminder Settings**:
- Send reminders: 48 hours before scheduled time
- Email notifications: Enabled

### Payment Gateway Configuration

#### For PayPal:
1. Go to: Site administration > Payments > Payment gateways
2. Enable PayPal gateway
3. Enter PayPal business account email
4. Configure currency and environment (sandbox/live)

#### For Razorpay:
1. Install plugin from GitHub
2. Configure API keys (Key ID and Key Secret)
3. Set webhook URL for payment notifications
4. Test with sandbox credentials first

#### For Stripe:
1. Install plugin from GitHub
2. Configure API keys (Publishable and Secret keys)
3. Set webhook endpoint
4. Test with test mode credentials

### Custom Profile Fields Configuration

All fields are automatically created by the configuration script. To modify:

1. Go to: Site administration > Users > User profile fields
2. Edit existing fields or add new ones
3. Configure visibility and required settings
4. Set signup form inclusion

## Usage Workflows

### Workflow 1: Trainee Registration

1. **Trainee Registration**:
   - Trainee fills registration form
   - Custom profile fields capture fellowship type, subspecialty, medical registration
   - Payment gateway processes registration fee

2. **Admin Processing**:
   - Admin reviews application
   - Assigns mentor using profile field
   - Sets training start date
   - Enrolls trainee in fellowship course

3. **Onboarding**:
   - Trainee receives welcome email
   - Access to induction materials
   - Profile completion checklist

### Workflow 2: Case Logbook Management

1. **Case Entry**:
   - Trainee logs case in Database Activity
   - Selects subspecialty and procedure type
   - Enters procedure details and outcomes
   - Submits for mentor approval

2. **Mentor Review**:
   - Mentor receives notification
   - Reviews case details
   - Provides feedback
   - Approves or requests revision

3. **Competency Tracking**:
   - Approved cases count toward competency requirements
   - Evidence automatically linked to competency framework
   - Progress tracked in learner dashboard

### Workflow 3: Rotation Scheduling

1. **Roster Creation**:
   - Admin creates Scheduler activity for each rotation type
   - Configures time slots and capacity
   - Sets up automated reminders

2. **Bulk Assignment**:
   - Admin uploads monthly roster (CSV/Excel)
   - Trainees automatically assigned to slots
   - Calendar entries created

3. **Notifications**:
   - Trainees receive 48-hour reminders
   - Email and calendar notifications
   - Mobile app notifications (if enabled)

4. **Attendance Tracking**:
   - Attendance marked per rotation
   - Integration with Attendance plugin
   - Completion tracked for competency requirements

### Workflow 4: Credentialing Sheet Submission

1. **Monthly Submission**:
   - Trainee submits credentialing sheet
   - Procedure counts by subspecialty
   - Competencies achieved listed

2. **Mentor Verification**:
   - Mentor reviews procedure counts
   - Verifies competency achievements
   - Approves or rejects submission

3. **Historical Tracking**:
   - All submissions maintained
   - Progress visualization over time
   - Export for accreditation purposes

### Workflow 5: Research Tracking

1. **Project Submission**:
   - Trainee submits research project details
   - Mentor review workflow initiated
   - Status tracked (draft to published)

2. **Publication Recording**:
   - Publication details captured
   - Links to papers maintained
   - Authors and journal information stored

3. **Portfolio Generation**:
   - Research portfolio automatically generated
   - Publication list with metadata
   - Institutional analytics on research output

## Integration with Competency Framework

All fellowship plugins integrate with Moodle's competency framework:

### Database Activity Integration
- Case logbook entries can be linked to specific competencies
- Approved entries serve as competency evidence
- Automatic evidence collection enabled

### Scheduler Integration
- Rotation completion can trigger competency awards
- Attendance requirements linked to competency progression
- Mentor meetings tracked as competency evidence

### Profile Fields Integration
- Subspecialty selection determines competency framework
- Training dates track competency timeline
- Alumni status affects competency visibility

## Troubleshooting

### Database Activity Issues

**Problem**: Cannot create Database Activity
**Solution**: 
- Verify module is enabled: Site administration > Plugins > Activity modules
- Check course editing is turned on
- Verify user has teacher/manager role

**Problem**: Approval workflow not working
**Solution**:
- Check Database Activity settings > Approval required
- Verify mentor has appropriate role
- Check notification settings

### Scheduler Issues

**Problem**: Scheduler plugin not found
**Solution**:
```bash
cd mod
git clone https://github.com/bostelm/moodle-mod_scheduler.git scheduler
cd ..
php admin/cli/upgrade.php --non-interactive
```

**Problem**: Reminders not sending
**Solution**:
- Check cron is running: php admin/cli/cron.php
- Verify email settings in Site administration
- Check Scheduler notification settings

### Payment Gateway Issues

**Problem**: Payment gateway not available
**Solution**:
- Verify Moodle version (4.0+ for payment subsystem)
- Enable payment subsystem: Site administration > Payments
- Install required gateway plugin

**Problem**: Payment fails
**Solution**:
- Check API credentials are correct
- Verify webhook URLs are configured
- Test with sandbox/test mode first
- Check payment gateway logs

### Profile Fields Issues

**Problem**: Profile fields not showing on signup
**Solution**:
- Edit field: Set "Display on signup page" to Yes
- Check field visibility settings
- Verify authentication plugin allows profile fields

**Problem**: Cannot edit profile fields
**Solution**:
- Check user has capability: moodle/user:update
- Verify field is not locked
- Check field visibility settings

## Maintenance

### Regular Tasks

1. **Weekly**:
   - Review pending case logbook approvals
   - Check rotation schedules for conflicts
   - Monitor payment transactions

2. **Monthly**:
   - Export credentialing sheets for records
   - Review trainee progress reports
   - Update rotation rosters

3. **Quarterly**:
   - Audit competency evidence
   - Review research publication status
   - Generate accreditation reports

4. **Annually**:
   - Update subspecialty competency frameworks
   - Review and update Database Activity templates
   - Archive completed training cohorts

### Backup Recommendations

1. **Database Activity Templates**:
   - Export templates using Moodle backup
   - Store in version control
   - Document template changes

2. **Scheduler Configurations**:
   - Backup rotation schedules
   - Export roster templates
   - Document scheduling rules

3. **Profile Field Definitions**:
   - Export profile field configurations
   - Document field purposes
   - Maintain field change log

## Support and Resources

### Documentation
- Database Activity: https://docs.moodle.org/en/Database_activity
- Scheduler: https://github.com/bostelm/moodle-mod_scheduler/wiki
- Payment Gateways: https://docs.moodle.org/en/Payment_gateways
- User Profile Fields: https://docs.moodle.org/en/User_profile_fields

### Community Support
- Moodle Forums: https://moodle.org/forums
- Scheduler Issues: https://github.com/bostelm/moodle-mod_scheduler/issues

### Custom Development
For features requiring custom development:
- Rules Engine (Task 12): Attendance-based competency locking, roster automation
- Kirkpatrick Dashboard (Task 10.6): Unified evaluation reporting
- Database Activity Templates (Task 11.1): Pre-configured templates

## Next Steps

After completing this task:

1. ✓ Database Activity plugin configured
2. ✓ Scheduler plugin installed
3. ✓ Payment gateways configured
4. ✓ Custom profile fields created
5. → Create Database Activity templates (Task 11.1)
6. → Configure competency framework (Task 3)
7. → Set up fellowship courses
8. → Test complete trainee workflow

## Conclusion

The ophthalmology fellowship plugins provide a comprehensive foundation for managing medical training programs. By leveraging Moodle's core capabilities and proven third-party plugins, the system supports case logbook management, rotation scheduling, trainee registration, and research tracking without requiring extensive custom development.

The integration with Moodle's competency framework ensures that all fellowship activities contribute to measurable learning outcomes and accreditation requirements.
