# Manual Installation Guide for Stash Plugin

## Issue
The Stash plugin (block_stash) could not be automatically installed due to GitHub authentication requirements in the Docker container environment.

## Manual Installation Steps

### Option 1: Install via Moodle Web Interface (Recommended)

1. Access Moodle admin interface at http://localhost:8080
2. Login as administrator
3. Navigate to: **Site administration > Plugins > Install plugins**
4. Search for "Stash" or go to: https://moodle.org/plugins/block_stash
5. Download the appropriate version for your Moodle installation
6. Upload the ZIP file through the web interface
7. Follow the installation wizard

### Option 2: Manual File Installation

1. Download Stash plugin from: https://moodle.org/plugins/block_stash
2. Extract the ZIP file on your local machine
3. Copy the extracted `stash` folder to the Moodle container:
   ```bash
   docker cp stash/ moodle-exploration-moodle-1:/bitnami/moodle/blocks/
   docker exec moodle-exploration-moodle-1 chown -R daemon:daemon /bitnami/moodle/blocks/stash
   ```
4. Access Moodle admin interface
5. Navigate to: **Site administration > Notifications**
6. Complete the installation wizard

### Option 3: Alternative Gamification Plugin

If Stash installation continues to be problematic, consider using alternative gamification approaches:

1. **Level Up! Plugin (Already Installed)**: Provides comprehensive gamification with:
   - XP points and leveling system
   - Visual progress indicators
   - Achievement tracking
   - Leaderboards

2. **Moodle Badges (Core Feature)**: Native badge system that provides:
   - Digital credentials
   - Achievement recognition
   - External sharing capabilities

3. **Custom Rewards System**: Build custom rewards using:
   - Moodle's completion tracking
   - Badge criteria
   - Custom user profile fields
   - Activity restrictions

## Requirement Coverage

Even without Stash, Requirement 16.1 (Gamification) can be satisfied through:

- **Level Up! Plugin**: XP points, levels, visual progress (✓ Installed)
- **Moodle Badges**: Achievement recognition and rewards (✓ Core feature)
- **Attendance Plugin**: Session tracking and engagement (✓ Installed)
- **Custom Certificate**: Milestone recognition (✓ Installed)

## Next Steps

1. Proceed with configuring the three successfully installed plugins
2. Test the gamification features with Level Up! and Badges
3. Evaluate if Stash is still needed for the specific use case
4. If needed, install Stash manually using Option 1 or 2 above
