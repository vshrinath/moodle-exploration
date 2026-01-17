# Unified Kirkpatrick Dashboard Plugin

## Overview

This Moodle local plugin provides a comprehensive, unified dashboard for viewing training evaluation data across all four Kirkpatrick levels. It aggregates data from Level 1 (Reaction), Level 2 (Learning), Level 3 (Behavior), and Level 4 (Results) into a single, interactive interface.

## Features

- **Unified View**: Single dashboard showing all four Kirkpatrick evaluation levels
- **Interactive Filtering**: Filter by program and time period
- **Real-time Metrics**: Key performance indicators for each level
- **Visual Analytics**: Charts and graphs for trend analysis
- **Drill-down Capabilities**: Navigate from organizational to individual level
- **Comparative Analysis**: Compare programs, cohorts, and time periods
- **Export Functionality**: Export data as PDF or Excel for stakeholders
- **Role-based Access**: Different views for managers, program owners, and trainers

## Installation

1. Copy the plugin directory to `moodle/local/kirkpatrick_dashboard/`
2. Visit Site Administration > Notifications to install the plugin
3. Configure dashboard permissions for user roles

## Prerequisites

The dashboard requires the following data collection to be configured:

- **Level 1**: Feedback Activity plugin with satisfaction surveys
- **Level 2**: Competency framework and assessment tracking
- **Level 3**: Portfolio plugin and follow-up surveys
- **Level 4** (Optional): local_kirkpatrick_level4 plugin for external data integration

## Dashboard Components

### Level 1: Reaction Metrics
- Average satisfaction score
- Average engagement rating
- Total responses collected
- Low satisfaction alerts

**Visualizations**:
- Satisfaction trend over time
- Content relevance distribution
- Engagement heatmap

### Level 2: Learning Metrics
- Average knowledge gain
- Competencies achieved
- Badges earned
- At-risk learners

**Visualizations**:
- Pre/post assessment comparison
- Competency achievement rates
- Learning progress trends

### Level 3: Behavior Metrics
- Average workplace performance
- Behavior change tracking
- Evidence submission rate
- Follow-up completion rate

**Visualizations**:
- Behavior sustainability over time
- Performance rating trends
- Evidence type distribution

### Level 4: Results Metrics
- Total cost savings
- Average ROI
- Productivity improvement
- Programs measured

**Visualizations**:
- ROI comparison across programs
- Organizational impact trends
- Cost-benefit analysis

### Integrated View
- Evaluation funnel showing progression through levels
- Cross-level correlation analysis
- Program effectiveness comparison

## Usage

### For Managers

1. Access dashboard from Site Administration menu
2. View organization-wide training effectiveness
3. Compare programs and identify best practices
4. Export reports for executive stakeholders

### For Program Owners

1. Filter dashboard by specific program
2. Analyze learner progression through Kirkpatrick levels
3. Identify areas for improvement
4. Track ROI and organizational impact

### For Trainers

1. View Level 1 and Level 2 data for assigned courses
2. Monitor learner satisfaction and engagement
3. Track competency achievement
4. Identify at-risk learners

## Filtering Options

### Program Filter
- Select specific program or view all programs
- Automatically updates all metrics and charts

### Time Period Filter
- Last 30 days
- Last 90 days
- Last 6 months
- Last year
- All time

## Export Options

### PDF Export
- Executive summary report
- All four Kirkpatrick levels
- Charts and visualizations
- Formatted for printing

### Excel Export
- Raw data for further analysis
- Separate sheets for each level
- Pivot-ready format
- Includes metadata

## Customization

### Adding Custom Metrics

Edit `index.php` to add custom metric cards:

```php
$custom_metric = $DB->get_field_sql("SELECT ...");
echo html_writer::start_div('metric-card col-md-3');
echo html_writer::tag('h4', 'Custom Metric');
echo html_writer::tag('div', $custom_metric, ['class' => 'metric-value']);
echo html_writer::end_div();
```

### Adding Custom Charts

Edit `amd/src/dashboard.js` to add custom visualizations:

```javascript
loadCustomChart: function(programId, period) {
    // Chart implementation using Chart.js or similar
}
```

## Permissions

### local/kirkpatrick_dashboard:view
- View the dashboard
- Default: Manager, Course Creator, Editing Teacher

### local/kirkpatrick_dashboard:export
- Export dashboard data
- Default: Manager, Course Creator

## Technical Details

### Data Sources
- kirkpatrick_level1_reaction
- kirkpatrick_level2_learning
- kirkpatrick_level3_behavior
- kirkpatrick_level4_results (if installed)

### Performance Considerations
- Dashboard queries are optimized with indexes
- Large datasets may require caching
- Consider scheduled report generation for very large installations

### Browser Compatibility
- Modern browsers (Chrome, Firefox, Safari, Edge)
- Responsive design for mobile and tablet
- JavaScript required for interactive features

## Troubleshooting

### No Data Displayed
- Verify data collection is configured for all levels
- Check that learners have completed training activities
- Ensure database tables exist and are populated

### Slow Performance
- Review database indexes
- Consider implementing caching
- Limit time period filter for large datasets

### Charts Not Loading
- Check JavaScript console for errors
- Verify Chart.js library is loaded
- Ensure browser JavaScript is enabled

## Support

For issues or questions:
- Review plugin documentation
- Check Moodle logs for errors
- Contact system administrator

## Future Enhancements

- Real-time data updates
- Predictive analytics
- Custom dashboard layouts
- Mobile app integration
- Automated report scheduling

## License

GNU GPL v3 or later

## Credits

Developed for the Competency-Based Learning Management System
Builds on Configurable Reports plugin data
