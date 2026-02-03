# SCEH Fellowship Training Dashboard Block

A visual navigation dashboard for the SCEH Fellowship Training System that provides quick access to key features through colorful, clickable cards.

## Features

- **Role-based cards**: Different cards appear based on user role (trainee, mentor, admin)
- **Modern UI**: Colorful gradient cards with icons
- **Responsive design**: Works on desktop, tablet, and mobile
- **Upgrade-safe**: Standard Moodle block plugin architecture

## Installation

1. Copy the `block_sceh_dashboard` folder to your Moodle's `/blocks/` directory
2. Visit Site Administration → Notifications to complete installation
3. Add the block to your homepage or dashboard

## User Roles

### Trainees See:
- Case Logbook
- My Competencies
- Attendance
- My Badges
- Credentialing Sheet
- Video Library
- My Progress

### Admins/Mentors See:
- Manage Cohorts
- Competency Framework
- Attendance Reports
- Training Evaluation (Kirkpatrick Dashboard)
- Badge Management
- Program Structure
- Custom Reports
- Roster Rules

## Configuration

No configuration needed - the block automatically detects user roles and displays appropriate cards.

## Customization

To modify cards or add new ones, edit:
- `block_sceh_dashboard.php` - Card definitions and logic
- `lang/en/block_sceh_dashboard.php` - Card titles and labels
- `styles.css` - Colors and styling

## Version

1.0.0 - Initial release
