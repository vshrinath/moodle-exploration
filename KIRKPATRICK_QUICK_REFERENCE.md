# Kirkpatrick Model Evaluation Framework - Quick Reference

## Overview
The Kirkpatrick Model evaluates training effectiveness across four levels: Reaction, Learning, Behavior, and Results. This guide provides quick access to the installed plugins and their usage.

## Four Levels of Evaluation

### Level 1: Reaction (Satisfaction)
**Question:** Did learners like the training?

**Tools:**
- **Feedback Activity** (mod_feedback)
  - Location: Add activity > Feedback
  - Use for: Post-session satisfaction surveys
  - Features: Anonymous responses, multiple question types, instant results
  
- **Questionnaire** (mod_questionnaire)
  - Location: Add activity > Questionnaire
  - Use for: Advanced evaluation surveys
  - Features: Complex branching, detailed analytics, export to CSV/XLS

**Metrics to Collect:**
- Overall satisfaction rating
- Content relevance
- Instructor effectiveness
- Learning environment quality
- Engagement level

### Level 2: Learning (Knowledge Gain)
**Question:** Did learners acquire knowledge and skills?

**Tools:**
- **Competency Framework**
  - Location: Site administration > Competencies
  - Use for: Skill tracking and verification
  
- **Badges System**
  - Location: Site administration > Badges
  - Use for: Achievement recognition
  
- **Quiz Module**
  - Location: Add activity > Quiz
  - Use for: Knowledge assessments
  
- **Assignment Module**
  - Location: Add activity > Assignment
  - Use for: Skill demonstrations

**Metrics to Collect:**
- Pre/post assessment scores
- Competency achievement rates
- Badge earning rates
- Assessment completion rates
- Skill demonstration quality

### Level 3: Behavior (Application)
**Question:** Are learners applying what they learned?

**Tools:**
- **Portfolio System**
  - Location: Site administration > Advanced features > Enable portfolios
  - Use for: Evidence collection from workplace
  
- **Questionnaire** (follow-up surveys)
  - Location: Add activity > Questionnaire
  - Use for: Post-training behavior surveys

**Metrics to Collect:**
- Evidence of skill application
- Supervisor feedback
- Behavior change indicators
- Workplace performance improvements
- Skill transfer consistency

### Level 4: Results (Organizational Impact)
**Question:** What organizational results were achieved?

**Tools:**
- **External Database Plugin**
  - Location: Site administration > Plugins > Enrolments > External database
  - Use for: Hospital system integration
  
- **Configurable Reports** (recommended)
  - Use for: ROI calculations and impact analysis

**Metrics to Collect:**
- Patient outcomes
- Cost savings
- Quality improvements
- Productivity gains
- Safety indicators
- ROI calculations

## Quick Start Guide

### Setting Up Level 1 Evaluation

1. **Create a Feedback Activity:**
   ```
   Course > Turn editing on > Add activity > Feedback
   - Name: "Post-Session Satisfaction Survey"
   - Enable anonymous responses
   - Add questions for satisfaction, relevance, effectiveness
   ```

2. **Create a Questionnaire:**
   ```
   Course > Turn editing on > Add activity > Questionnaire
   - Name: "Detailed Training Evaluation"
   - Add multiple question types
   - Configure branching logic if needed
   ```

### Setting Up Level 2 Evaluation

1. **Enable Competency Framework:**
   ```
   Site administration > Competencies > Add new competency framework
   - Define competencies for your program
   - Link competencies to course activities
   ```

2. **Create Badges:**
   ```
   Site administration > Badges > Add new badge
   - Link to competency completion
   - Configure automatic awarding
   ```

3. **Create Assessments:**
   ```
   Course > Add activity > Quiz or Assignment
   - Map to competencies
   - Set completion criteria
   ```

### Setting Up Level 3 Evaluation

1. **Enable Portfolio System:**
   ```
   Site administration > Advanced features
   - Check "Enable portfolios"
   - Save changes
   ```

2. **Configure Portfolio Instances:**
   ```
   Site administration > Plugins > Portfolios
   - Enable desired portfolio plugins
   - Configure submission settings
   ```

3. **Create Follow-up Surveys:**
   ```
   Course > Add activity > Questionnaire
   - Schedule 30/60/90 days post-training
   - Ask about workplace application
   ```

### Setting Up Level 4 Evaluation

1. **Configure External Database:**
   ```
   Site administration > Plugins > Enrolments > External database
   - Database driver: mysqli (or appropriate)
   - Database host: [hospital system host]
   - Database name: [hospital database]
   - Configure table mappings
   ```

2. **Test Connection:**
   ```
   - Use test connection feature
   - Verify data synchronization
   - Check data mapping accuracy
   ```

## Scripts Reference

### Installation
```bash
# Install all Kirkpatrick plugins
bash install_kirkpatrick_plugins.sh
```

### Configuration
```bash
# Configure plugins
docker exec [container_id] php /tmp/configure_kirkpatrick_plugins.php
```

### Verification
```bash
# Verify installation
docker exec [container_id] php /tmp/verify_kirkpatrick_setup.php

# Run integration tests
docker exec [container_id] php /tmp/test_kirkpatrick_integration.php
```

## Best Practices

### Level 1 (Reaction)
- Collect feedback immediately after sessions
- Use anonymous surveys for honest responses
- Include both quantitative and qualitative questions
- Monitor engagement metrics in real-time

### Level 2 (Learning)
- Use pre/post assessments to measure knowledge gain
- Link all assessments to competencies
- Award badges for milestone achievements
- Track skill progression over time

### Level 3 (Behavior)
- Schedule follow-up surveys at 30, 60, 90 days
- Collect workplace evidence through portfolios
- Gather supervisor feedback
- Monitor long-term behavior change

### Level 4 (Results)
- Integrate with organizational data systems
- Calculate ROI using cost and benefit data
- Track patient outcomes and quality metrics
- Provide executive-level dashboards

## Reporting

### Available Reports
1. **Feedback Reports** - Satisfaction and engagement data
2. **Competency Reports** - Skill achievement tracking
3. **Portfolio Reports** - Evidence collection summaries
4. **External Data Reports** - Organizational impact metrics

### Creating Custom Reports
```
Site administration > Reports > Configurable reports
- Create new report
- Select data sources (feedback, competency, portfolio)
- Add filters and columns
- Schedule automated delivery
```

## Troubleshooting

### Common Issues

**Feedback not collecting responses:**
- Check anonymous settings
- Verify activity is visible to students
- Ensure completion tracking is enabled

**Competencies not linking to activities:**
- Verify competency framework is enabled
- Check course competency settings
- Ensure activities have competency mappings

**Portfolio not accepting submissions:**
- Verify portfolios are enabled globally
- Check portfolio plugin configuration
- Ensure users have submission permissions

**External database not connecting:**
- Verify database credentials
- Check network connectivity
- Review table mapping configuration

## Support Resources

### Documentation
- Moodle Feedback Activity: https://docs.moodle.org/en/Feedback_activity
- Moodle Competencies: https://docs.moodle.org/en/Competencies
- Moodle Portfolios: https://docs.moodle.org/en/Portfolios
- Questionnaire Plugin: https://github.com/PoetOS/moodle-mod_questionnaire

### Scripts Location
- Installation: `install_kirkpatrick_plugins.sh`
- Configuration: `configure_kirkpatrick_plugins.php`
- Verification: `verify_kirkpatrick_setup.php`
- Integration Test: `test_kirkpatrick_integration.php`
- Completion Report: `TASK_2.6_COMPLETION_REPORT.md`

---

**Last Updated:** January 17, 2026  
**Version:** 1.0  
**Status:** Production Ready
