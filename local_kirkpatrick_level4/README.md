# Kirkpatrick Level 4 External Database Integration Plugin

## Overview

This Moodle local plugin integrates external organizational data sources to provide Level 4 (Results) evaluation data for the Kirkpatrick training evaluation model. It synchronizes data from hospital systems, calculates ROI, and correlates learner training with organizational outcomes.

## Features

- **External Data Synchronization**: Scheduled tasks to pull data from hospital databases, REST APIs, or CSV imports
- **ROI Calculation**: Automated calculation of training return on investment
- **Learner-Outcome Correlation**: Statistical correlation between individual learner training and organizational results
- **Data Normalization**: Handles different external data formats and structures
- **Impact Measurement**: Tracks patient outcomes, cost savings, quality metrics, and productivity improvements

## Installation

1. Copy the plugin directory to `moodle/local/kirkpatrick_level4/`
2. Visit Site Administration > Notifications to install the plugin
3. Configure external data sources in plugin settings

## Configuration

### External Data Sources

Configure external data sources in the plugin settings:

1. **Hospital Database**: Direct database connection for real-time data
   - Connection string
   - Query configuration
   - Sync frequency

2. **REST API**: API-based integration
   - API endpoint URL
   - Authentication credentials
   - Data mapping configuration

3. **CSV Import**: File-based data import
   - Import directory
   - File format specification
   - Processing schedule

### Scheduled Tasks

The plugin includes three scheduled tasks:

1. **Sync External Data** (Daily at 2:00 AM)
   - Pulls data from configured external sources
   - Normalizes and stores organizational metrics

2. **Calculate ROI** (Monthly on 1st at 3:30 AM)
   - Calculates training costs vs. organizational benefits
   - Updates ROI metrics for all programs

3. **Correlate Learner Outcomes** (Monthly on 1st at 4:00 AM)
   - Links individual learners to organizational outcomes
   - Calculates contribution scores

## Database Tables

### kirkpatrick_level4_results
Stores organizational results data:
- Patient outcomes
- Cost savings
- Quality metrics
- ROI calculations
- Productivity improvements
- Safety indicators

### kirkpatrick_external_sources
Configuration for external data sources:
- Source name and type
- Connection details
- Sync frequency
- Status tracking

### kirkpatrick_learner_outcomes
Maps learners to organizational outcomes:
- User and program IDs
- Contribution scores
- Correlation strength
- Timestamps

## Usage

### For Administrators

1. Configure external data sources
2. Test data synchronization
3. Review ROI calculations
4. Monitor correlation results

### For Program Owners

1. View Level 4 results in Kirkpatrick reports
2. Analyze organizational impact
3. Compare program effectiveness
4. Export data for stakeholders

### For Executives

1. Access executive dashboards
2. Review ROI metrics
3. Compare training investments
4. Make data-driven decisions

## Data Privacy

The plugin respects Moodle's privacy API and:
- Stores only aggregated organizational data
- Links learners through secure IDs
- Provides data export capabilities
- Supports data deletion requests

## Requirements

- Moodle 4.0 or higher
- PHP 7.4 or higher
- Access to external data sources
- Kirkpatrick Level 1-3 data collection configured

## Support

For issues or questions:
- Review plugin documentation
- Check Moodle logs for errors
- Contact system administrator

## License

GNU GPL v3 or later

## Credits

Developed for the Competency-Based Learning Management System
