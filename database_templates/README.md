# Database Activity Templates for Ophthalmology Fellowship

## Overview

This directory contains pre-configured Database Activity templates for the ophthalmology fellowship management system. These templates provide structured data collection for case logbooks, credentialing sheets, and research tracking.

## Templates Included

### 1. Case and Surgical Logbook Template
**File:** `case_logbook_template.xml`

**Purpose:** Track patient cases and surgical procedures with mentor approval workflow

**Key Features:**
- Subspecialty categorization (7 ophthalmology subspecialties)
- Detailed procedure documentation
- Surgical role tracking (Primary Surgeon, Assistant, Observer, Supervised Practice)
- Outcomes and complications recording
- Learning points documentation
- Monthly submission workflow
- Mentor approval and feedback system

**Fields:**
- Case date
- Subspecialty (dropdown)
- Procedure type and details
- Patient age and diagnosis
- Surgical role
- Outcomes and complications
- Learning points
- Approval status and mentor feedback

### 2. Credentialing Sheet Template
**File:** `credentialing_sheet_template.xml`

**Purpose:** Monthly documentation of surgical procedures and competency achievements

**Key Features:**
- Comprehensive procedure count tracking across all subspecialties
- Competency achievement documentation
- Mentor verification workflow
- Historical data maintenance
- Accreditation-ready reporting

**Procedure Categories:**
- Cataract (Phaco, SICS/ECCE, IOL)
- Retina (Vitrectomy, Laser, Injections)
- Cornea (Keratoplasty, Pterygium, Cross-linking)
- Glaucoma (Trabeculectomy, Laser, Tube Shunt)
- Oculoplasty (Lid Surgery, DCR/DCT, Orbital)
- Pediatric (Strabismus, Pediatric Cataract)

### 3. Research and Publications Template
**File:** `research_publications_template.xml`

**Purpose:** Track research projects, publications, and academic contributions

**Key Features:**
- Multiple research types (proposals, articles, presentations, case reports)
- Complete publication metadata
- Mentor review workflow
- Searchable research library
- Status tracking from proposal to publication

**Research Types:**
- Research proposals
- Journal articles (published/submitted)
- Conference presentations (oral/poster)
- Book chapters and reviews
- Case reports
- Thesis/dissertation

## Installation Instructions

### Prerequisites
- Moodle 3.9 or higher
- Database Activity module enabled
- Appropriate permissions (Teacher or Manager role)

### Step-by-Step Import Process

#### 1. Access Database Activity

1. Log in to Moodle as an administrator or course creator
2. Navigate to the course where you want to add the database
3. Turn editing on
4. Click "Add an activity or resource"
5. Select "Database" from the activity list
6. Click "Add"

#### 2. Import Template

**Method A: Import During Creation**
1. In the Database activity settings, enter a name (e.g., "Case Logbook")
2. Save and display
3. Click on the "Presets" tab
4. Click "Import"
5. Choose "Upload preset"
6. Upload the corresponding XML file (e.g., `case_logbook_template.xml`)
7. Click "Import"
8. Confirm the import

**Method B: Import to Existing Database**
1. Open an existing Database activity
2. Click on the "Presets" tab
3. Click "Import"
4. Choose "Upload preset"
5. Upload the XML file
6. Click "Import"
7. Confirm (this will overwrite existing structure)

#### 3. Configure Settings

After importing, configure these settings:

**General Settings:**
- Approval: Enable "Require approval" for mentor review workflow
- Comments: Enable if you want additional commenting capability
- Entries: Set required entries if needed (e.g., 1 per month for credentialing)

**Availability:**
- Set open/close dates if needed for monthly submissions
- Configure group mode if using cohorts

**Permissions:**
- Ensure mentors have "mod/data:approve" capability
- Ensure trainees have "mod/data:writeentry" capability
- Configure "mod/data:viewentry" based on privacy requirements

#### 4. Customize (Optional)

You can customize the templates after import:

**Fields Tab:**
- Add, remove, or modify fields
- Adjust field descriptions
- Change dropdown options

**Templates Tab:**
- Modify single entry template (how individual entries display)
- Modify list template (how the list of entries displays)
- Customize CSS for styling
- Add JavaScript for advanced functionality

### Template-Specific Configuration

#### Case Logbook Configuration

1. **Monthly Submission Workflow:**
   - Set "Entries required" to minimum expected cases per month
   - Enable "Require approval" 
   - Set "Available from" and "Available to" for monthly windows

2. **Mentor Assignment:**
   - Assign mentors as teachers in the course
   - Grant "mod/data:approve" capability
   - Configure notifications for new submissions

3. **Integration with Competency Framework:**
   - Link database to relevant competencies
   - Set completion criteria based on approved entries
   - Configure grade settings if needed

#### Credentialing Sheet Configuration

1. **Monthly Submission:**
   - Set "Maximum entries" to 1 per month per user
   - Configure submission windows
   - Enable approval workflow

2. **Reporting:**
   - Use "Export" feature for accreditation reports
   - Configure custom reports using Configurable Reports plugin
   - Set up automated monthly reminders

3. **Cumulative Tracking:**
   - Use Moodle's reporting features to aggregate data
   - Create custom SQL reports for cumulative counts
   - Export to Excel for external analysis

#### Research Publications Configuration

1. **Mentor Review Workflow:**
   - Enable approval for research proposals
   - Configure notifications for mentor reviews
   - Set up multi-stage approval if needed

2. **Searchable Library:**
   - Enable search functionality
   - Configure advanced search fields
   - Set up tags or categories

3. **Portfolio Integration:**
   - Link to Portfolio plugin for evidence collection
   - Configure export to CV/resume formats
   - Enable external sharing if appropriate

## Admin Training Materials

### Quick Start Guide for Administrators

#### Creating a New Database from Template

1. **Preparation** (5 minutes)
   - Download template XML files
   - Identify target course
   - Verify permissions

2. **Import** (10 minutes)
   - Add Database activity
   - Import template
   - Configure basic settings

3. **Customization** (15 minutes)
   - Adjust fields if needed
   - Customize templates
   - Set permissions

4. **Testing** (10 minutes)
   - Create test entry
   - Test approval workflow
   - Verify notifications

**Total Time:** ~40 minutes per template

#### Common Customizations

**Adding Custom Fields:**
```
1. Go to Database activity
2. Click "Fields" tab
3. Click "Add field"
4. Select field type
5. Configure field settings
6. Save
```

**Modifying Templates:**
```
1. Go to "Templates" tab
2. Select template type (Single, List, Add, etc.)
3. Edit HTML/CSS
4. Use field tags: [[fieldname]]
5. Save template
```

**Setting Up Approval Workflow:**
```
1. Go to Database settings
2. Enable "Require approval"
3. Set "Manage approved entries"
4. Assign approvers (mentors)
5. Configure notifications
```

### Troubleshooting

#### Import Fails

**Problem:** Template import fails with error
**Solution:**
- Verify XML file is not corrupted
- Check Moodle version compatibility
- Ensure Database module is up to date
- Try importing to a fresh database activity

#### Fields Not Displaying

**Problem:** Fields don't show in entry form
**Solution:**
- Check "Add entry" template is configured
- Verify fields are not hidden
- Clear Moodle cache
- Check browser console for JavaScript errors

#### Approval Workflow Not Working

**Problem:** Mentors cannot approve entries
**Solution:**
- Verify "mod/data:approve" capability
- Check role assignments
- Ensure "Require approval" is enabled
- Verify mentor is enrolled in course

#### Notifications Not Sending

**Problem:** Users not receiving notifications
**Solution:**
- Check Moodle notification settings
- Verify user email addresses
- Check message output configuration
- Test with site administrator account

### Best Practices

#### Data Management

1. **Regular Backups:**
   - Export database entries monthly
   - Backup entire database activity
   - Store exports securely

2. **Data Validation:**
   - Review entries regularly
   - Check for incomplete submissions
   - Verify approval workflow compliance

3. **Performance:**
   - Archive old entries annually
   - Limit file uploads size
   - Use pagination for large datasets

#### User Training

1. **Trainee Orientation:**
   - Provide written instructions
   - Conduct hands-on training session
   - Create video tutorials
   - Offer ongoing support

2. **Mentor Training:**
   - Explain approval workflow
   - Demonstrate feedback process
   - Clarify expectations
   - Provide rubrics if applicable

3. **Documentation:**
   - Maintain user guides
   - Create FAQ documents
   - Update regularly based on feedback

#### Workflow Optimization

1. **Submission Reminders:**
   - Set up automated reminders
   - Use calendar events
   - Send weekly status updates

2. **Approval Efficiency:**
   - Batch review entries
   - Use filters and search
   - Set approval deadlines
   - Delegate when appropriate

3. **Reporting:**
   - Schedule regular reports
   - Automate where possible
   - Share with stakeholders
   - Use for continuous improvement

## Integration with Competency Framework

### Linking Database Entries to Competencies

1. **Activity Completion:**
   - Set completion criteria based on approved entries
   - Link to competency achievement
   - Configure automatic competency evidence

2. **Grading:**
   - Enable grading if needed
   - Link grades to competency ratings
   - Use rubrics for consistency

3. **Evidence Collection:**
   - Database entries serve as competency evidence
   - Link to learning plans
   - Track progression over time

### Automated Competency Awards

Configure rules engine (if implemented) to:
- Award competencies based on procedure counts
- Trigger badges on milestone achievements
- Update learning plans automatically

## Support and Maintenance

### Regular Maintenance Tasks

**Weekly:**
- Monitor new submissions
- Check approval queue
- Respond to user questions

**Monthly:**
- Export data for backup
- Review analytics
- Update documentation if needed

**Quarterly:**
- Review and optimize templates
- Gather user feedback
- Plan improvements

**Annually:**
- Archive old data
- Update for new academic year
- Review and update training materials

### Getting Help

**Moodle Documentation:**
- https://docs.moodle.org/en/Database_activity
- https://docs.moodle.org/en/Database_presets

**Community Support:**
- Moodle forums: https://moodle.org/forums
- Database activity forum
- Local Moodle user groups

**Technical Support:**
- Contact your Moodle administrator
- Consult with IT department
- Engage Moodle partners if needed

## Version History

**Version 1.0** (Current)
- Initial release
- Three core templates
- Basic approval workflows
- Standard ophthalmology subspecialties

**Planned Updates:**
- Additional subspecialty options
- Enhanced reporting templates
- Mobile-optimized views
- Integration with external systems

## License and Attribution

These templates are provided as part of the Competency-Based Learning Management System for ophthalmology fellowship training. They are designed to work with Moodle's Database Activity module and follow Moodle's licensing terms.

**Customization:** These templates can be freely customized to meet specific institutional needs.

**Sharing:** Modified templates can be shared with other institutions following Moodle's open-source principles.

## Appendix: Field Reference

### Case Logbook Fields

| Field Name | Type | Required | Description |
|------------|------|----------|-------------|
| case_date | Date | Yes | Date of case |
| subspecialty | Menu | Yes | Ophthalmology subspecialty |
| procedure_type | Text | Yes | Type of procedure |
| procedure_details | Textarea | Yes | Detailed description |
| patient_age | Number | No | Patient age |
| diagnosis | Text | Yes | Primary diagnosis |
| surgical_role | Menu | Yes | Trainee's role |
| outcomes | Textarea | Yes | Surgical outcomes |
| complications | Textarea | Yes | Complications if any |
| learning_points | Textarea | Yes | Key learnings |
| approval_status | Menu | Yes | Approval status |
| mentor_feedback | Textarea | No | Mentor comments |

### Credentialing Sheet Fields

| Field Name | Type | Required | Description |
|------------|------|----------|-------------|
| month | Menu | Yes | Month of credentialing |
| year | Number | Yes | Year |
| submission_date | Date | Yes | Submission date |
| [procedure]_count | Number | Yes | Count for each procedure type |
| competencies_achieved | Textarea | Yes | Competencies achieved |
| skills_demonstrated | Textarea | Yes | Skills demonstrated |
| approval_status | Menu | Yes | Verification status |
| mentor_comments | Textarea | No | Mentor comments |
| approval_date | Date | No | Approval date |

### Research Publications Fields

| Field Name | Type | Required | Description |
|------------|------|----------|-------------|
| title | Text | Yes | Research/publication title |
| research_type | Menu | Yes | Type of research |
| status | Menu | Yes | Current status |
| subspecialty | Menu | Yes | Subspecialty area |
| authors | Textarea | Yes | Author list |
| publication_year | Number | Yes | Year |
| journal_conference | Text | No | Journal/conference name |
| volume_issue | Text | No | Volume and issue |
| pages | Text | No | Page numbers |
| doi_link | URL | No | DOI or link |
| impact_factor | Text | No | Impact factor |
| abstract | Textarea | Yes | Abstract/summary |
| keywords | Text | Yes | Keywords |
| methodology | Textarea | No | Research methodology |
| key_findings | Textarea | No | Key findings |
| primary_mentor | Text | Yes | Mentor name |
| review_status | Menu | Yes | Review status |
| mentor_feedback | Textarea | No | Mentor feedback |
| review_date | Date | No | Review date |
