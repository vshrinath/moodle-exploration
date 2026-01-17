# SCEH Rules Engine - Administrator Guide

## Table of Contents
1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Managing Attendance Rules](#managing-attendance-rules)
5. [Managing Roster Rules](#managing-roster-rules)
6. [Monitoring and Audit](#monitoring-and-audit)
7. [Troubleshooting](#troubleshooting)

## Introduction

The SCEH Rules Engine automates business logic for competency-based learning that cannot be achieved with Moodle's core features. It provides two main capabilities:

1. **Attendance-Based Competency Locking**: Automatically blocks learner access to competencies when attendance falls below configured thresholds
2. **Roster-to-Competency Progression**: Automatically awards competency evidence when learners complete specific roster types

## Installation

### Prerequisites
- Moodle 4.0 or higher
- Competency framework enabled
- Attendance plugin installed (for attendance rules)
- Scheduler plugin installed (for roster rules)

### Installation Steps

1. **Upload Plugin Files**
   ```bash
   cd /path/to/moodle
   cp -r local_sceh_rules local/sceh_rules
   ```

2. **Run Installation**
   - Navigate to: Site Administration → Notifications
   - Click "Upgrade Moodle database now"
   - Confirm the installation

3. **Verify Installation**
   - Navigate to: Site Administration → Plugins → Local plugins
   - Confirm "SCEH Rules Engine" appears in the list

## Configuration

### Initial Setup

1. Navigate to: **Site Administration → Plugins → Local plugins → SCEH Rules Engine**

2. **Enable Rules Engine**
   - Check "Enable Attendance Rules" to activate attendance-based locking
   - Check "Enable Roster Rules" to activate roster-to-competency progression

3. **Configure Permissions**
   - Assign `local/sceh_rules:managerules` capability to administrators who will configure rules
   - Assign `local/sceh_rules:viewaudit` capability to users who need audit trail access

## Managing Attendance Rules

### Creating Attendance Rules

1. Navigate to: **Site Administration → Plugins → Local plugins → SCEH Rules Engine**
2. Click "Attendance Rules"
3. Click "Add Attendance Rule"
4. Configure the rule:
   - **Competency**: Select the competency to lock
   - **Course**: Select the course where attendance is tracked
   - **Minimum Attendance (%)**: Enter threshold (0-100)
   - **Enabled**: Check to activate the rule

### Example Use Cases

**Scenario 1: Clinical Skills Competency**
- Competency: "Surgical Technique - Cataract"
- Course: "Clinical Rotation - Ophthalmology"
- Threshold: 80%
- Result: Learners must attend 80% of clinical sessions before accessing the surgical competency

**Scenario 2: Theory Competency**
- Competency: "Anatomy Fundamentals"
- Course: "Basic Sciences"
- Threshold: 75%
- Result: Learners need 75% lecture attendance to progress to anatomy assessments

### Editing and Deleting Rules

- Click "Edit" next to any rule to modify settings
- Click "Delete" to remove a rule (requires confirmation)
- Disabled rules remain in the system but don't affect learners

### How Attendance Rules Work

1. **Evaluation Trigger**: Rules are evaluated when:
   - Attendance is taken for any session
   - Learner attempts to access a competency

2. **Calculation**: System calculates attendance percentage:
   ```
   Attendance % = (Sessions Attended / Total Sessions) × 100
   ```

3. **Blocking**: If attendance < threshold:
   - Competency access is blocked
   - Learner sees message: "This competency is locked due to insufficient attendance (X% of Y% required)"

4. **Audit**: All evaluations are logged with:
   - User ID
   - Current attendance percentage
   - Required threshold
   - Action taken (allowed/blocked)

## Managing Roster Rules

### Creating Roster Rules

1. Navigate to: **Site Administration → Plugins → Local plugins → SCEH Rules Engine**
2. Click "Roster Rules"
3. Click "Add Roster Rule"
4. Configure the rule:
   - **Roster Type**: Select from:
     - Morning Class
     - Night Duty
     - Training OT
     - Satellite Visits
     - Postings Schedule
   - **Target Competency**: Select competency to award
   - **Evidence Description**: Enter description for audit trail
   - **Enabled**: Check to activate the rule

### Example Use Cases

**Scenario 1: Night Duty Competency**
- Roster Type: Night Duty
- Competency: "Emergency Response - Night Shift"
- Evidence: "Completed night duty roster - emergency department"
- Result: Automatic competency evidence when learner completes night duty

**Scenario 2: Surgical Training**
- Roster Type: Training OT
- Competency: "Surgical Exposure - Operating Theatre"
- Evidence: "Completed training OT rotation"
- Result: Automatic evidence for surgical exposure competency

### How Roster Rules Work

1. **Trigger**: When a roster entry is completed in the Scheduler plugin

2. **Evaluation**: System checks for active rules matching the roster type

3. **Award**: For each matching rule:
   - Creates competency evidence record
   - Links evidence to the competency
   - Records evidence description
   - Logs action in audit trail

4. **Proficiency Check**: System re-evaluates if learner has achieved proficiency based on accumulated evidence

## Monitoring and Audit

### Viewing Audit Log

1. Navigate to: **Site Administration → Plugins → Local plugins → SCEH Rules Engine**
2. Click "Rules Engine Audit Log"
3. Review recent actions (last 100 entries)

### Audit Log Information

Each entry shows:
- **Timestamp**: When the action occurred
- **Rule Type**: attendance or roster
- **User**: Affected learner
- **Action**: What happened (blocked, allowed, evidence_awarded, error)
- **Details**: Additional context (JSON format)

### Interpreting Audit Entries

**Attendance Rule - Blocked**
```
Timestamp: 2026-01-17 10:30:00
Rule Type: attendance
User: John Doe
Action: blocked
Details: {
  "competencyid": 123,
  "courseid": 45,
  "attendance": 65.5,
  "threshold": 75.0
}
```
Interpretation: John Doe was blocked from accessing competency 123 because attendance (65.5%) is below threshold (75%)

**Roster Rule - Evidence Awarded**
```
Timestamp: 2026-01-17 14:15:00
Rule Type: roster
User: Jane Smith
Action: evidence_awarded
Details: {
  "competencyid": 456,
  "rostertype": "morning",
  "evidence": "Completed morning class roster"
}
```
Interpretation: Jane Smith received competency evidence for completing morning class roster

### Exporting Audit Data

For compliance or reporting:
1. Access the database directly
2. Query `local_sceh_rules_audit` table
3. Export to CSV for analysis

```sql
SELECT 
    FROM_UNIXTIME(timecreated) as timestamp,
    ruletype,
    u.firstname,
    u.lastname,
    action,
    details
FROM mdl_local_sceh_rules_audit a
JOIN mdl_user u ON u.id = a.userid
WHERE timecreated > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))
ORDER BY timecreated DESC;
```

## Troubleshooting

### Rules Not Working

**Problem**: Attendance rules not blocking competencies

**Solutions**:
1. Verify rules engine is enabled in settings
2. Check "Enable Attendance Rules" is checked
3. Confirm rule is enabled (not disabled)
4. Verify attendance plugin is installed and configured
5. Check audit log for evaluation entries

**Problem**: Roster rules not awarding evidence

**Solutions**:
1. Verify "Enable Roster Rules" is checked
2. Confirm Scheduler plugin is installed
3. Check roster type matches rule configuration exactly
4. Review audit log for errors
5. Verify competency framework is properly configured

### Performance Issues

**Problem**: Slow page loads when accessing competencies

**Solutions**:
1. Review number of active rules (reduce if excessive)
2. Check database indexes are present
3. Consider disabling rules temporarily to isolate issue
4. Review server logs for database query performance

### Audit Log Issues

**Problem**: Audit log not showing entries

**Solutions**:
1. Verify `local/sceh_rules:viewaudit` capability is assigned
2. Check database table `local_sceh_rules_audit` exists
3. Confirm rules are actually being triggered
4. Review Moodle error logs for database issues

### Data Integrity

**Problem**: Duplicate rules or conflicts

**Solutions**:
1. Database has unique constraints to prevent duplicates
2. If error occurs, check for existing rule with same parameters
3. Delete conflicting rule before creating new one
4. Review audit log to identify when duplicate was attempted

## Best Practices

### Rule Design

1. **Start Conservative**: Begin with lower thresholds and adjust based on data
2. **Document Rules**: Use clear evidence descriptions for audit trail
3. **Test First**: Create rules in test environment before production
4. **Monitor Impact**: Review audit log regularly to ensure rules work as intended

### Attendance Thresholds

- **Clinical Training**: 80-90% (high stakes)
- **Theory Classes**: 70-80% (moderate stakes)
- **Optional Sessions**: 60-70% (low stakes)

### Roster Rules

- **One Rule Per Roster Type**: Avoid multiple rules for same roster type and competency
- **Clear Evidence**: Write descriptive evidence text for transparency
- **Regular Review**: Audit roster completions monthly to verify automation

### Security

1. **Limit Access**: Only assign management capabilities to trusted administrators
2. **Audit Regularly**: Review audit log for unusual patterns
3. **Backup Rules**: Export rule configurations before major changes
4. **Test Changes**: Always test rule modifications in staging environment

## Support

For technical support:
- Review Moodle error logs: Site Administration → Reports → Logs
- Check plugin version: Site Administration → Plugins → Local plugins
- Consult README.md for architecture details
- Contact system administrator for database access

## Appendix: Database Schema

### Tables

**local_sceh_attendance_rules**
- Stores attendance-based competency locking rules
- Unique index on (competencyid, courseid)

**local_sceh_roster_rules**
- Stores roster-to-competency progression rules
- Unique index on (rostertype, competencyid)

**local_sceh_rules_audit**
- Comprehensive audit trail for all rule actions
- Indexed on (ruletype, ruleid) and (userid, timecreated)
