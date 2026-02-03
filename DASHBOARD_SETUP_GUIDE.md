# SCEH Fellowship Training Dashboard - Setup Guide

## What You Get

A colorful, card-based navigation dashboard on your Moodle homepage that provides quick access to all fellowship training features. Cards automatically adapt based on user role.

### For Trainees:
- 📋 Case Logbook
- ✓ My Competencies  
- 📅 Attendance
- 🏆 My Badges
- 📜 Credentialing Sheet
- 🎥 Video Library
- 📈 My Progress

### For Admins/Mentors:
- 👥 Manage Cohorts
- 🌳 Competency Framework
- 📊 Attendance Reports
- 📉 Training Evaluation (Kirkpatrick)
- 🏅 Badge Management
- 🗂️ Program Structure
- 📋 Custom Reports
- ⚙️ Roster Rules

## Installation

### Step 1: Install the Block

```bash
# From your Moodle root directory
chmod +x install_sceh_dashboard.sh
./install_sceh_dashboard.sh
```

Or manually:
```bash
cp -r block_sceh_dashboard /path/to/moodle/blocks/sceh_dashboard
```

### Step 2: Upgrade Moodle Database

1. Log in as admin
2. Go to: **Site Administration → Notifications**
3. Click **"Upgrade Moodle database now"**
4. Wait for completion

### Step 3: Add Block to Homepage

1. Go to your Moodle homepage or Dashboard
2. Click **"Turn editing on"** (top right)
3. Find **"Add a block"** dropdown
4. Select **"Fellowship Training Dashboard"**
5. Position the block where you want it
6. Click **"Turn editing off"**

### Step 4: Verify Installation

```bash
php verify_sceh_dashboard.php
```

## Customization

### Change Card Colors

Edit `blocks/sceh_dashboard/styles.css`:

```css
.sceh-card-blue {
    background: linear-gradient(135deg, #YOUR_COLOR_1 0%, #YOUR_COLOR_2 100%);
}
```

### Add New Cards

Edit `blocks/sceh_dashboard/block_sceh_dashboard.php` in the `get_dashboard_cards()` method:

```php
$cards[] = [
    'title' => get_string('yourcardname', 'block_sceh_dashboard'),
    'icon' => 'fa-your-icon',
    'color' => 'blue',
    'url' => new moodle_url('/your/page.php')
];
```

### Change Card Labels

Edit `blocks/sceh_dashboard/lang/en/block_sceh_dashboard.php`:

```php
$string['yourcardname'] = 'Your Card Title';
```

## Available FontAwesome Icons

Use any FontAwesome 5 icon:
- `fa-clipboard-list` - Clipboard with list
- `fa-check-circle` - Check mark
- `fa-calendar-check` - Calendar
- `fa-trophy` - Trophy
- `fa-certificate` - Certificate
- `fa-video` - Video
- `fa-chart-line` - Line chart
- `fa-users` - Users
- `fa-sitemap` - Sitemap
- `fa-chart-bar` - Bar chart
- `fa-analytics` - Analytics
- `fa-award` - Award
- `fa-project-diagram` - Diagram
- `fa-cogs` - Settings

Browse more at: https://fontawesome.com/v5/search

## Troubleshooting

### Block doesn't appear in "Add a block" list
- Clear Moodle cache: Site Administration → Development → Purge all caches
- Check file permissions: `chmod -R 755 blocks/sceh_dashboard`

### Cards show but links don't work
- Verify the target features are installed
- Check the URLs in `get_dashboard_cards()` method
- Run `verify_sceh_dashboard.php` to check dependencies

### Styling looks broken
- Clear browser cache
- Check that `styles.css` is in the block directory
- Verify FontAwesome is loaded (should be by default in Moodle 4.0+)

### Wrong cards showing for user role
- Check user's role assignments
- Verify capabilities in Site Administration → Users → Permissions

## Upgrade Safety

This block is upgrade-safe because:
- It's a standard Moodle plugin (not a core modification)
- Stored in `/blocks/` directory (separate from core)
- Uses Moodle's plugin API
- Survives Moodle version upgrades

After upgrading Moodle:
1. Just update the version number in `version.php` if needed
2. Visit Site Administration → Notifications
3. Block continues working

## Support

For issues or questions:
1. Run `verify_sceh_dashboard.php` for diagnostics
2. Check Moodle error logs: Site Administration → Reports → Logs
3. Review the README.md in the block directory
