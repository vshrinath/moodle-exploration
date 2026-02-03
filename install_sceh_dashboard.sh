#!/bin/bash

echo "=========================================="
echo "SCEH Fellowship Dashboard Block Installer"
echo "=========================================="
echo ""

# Check if we're in a Moodle installation
if [ ! -f "config.php" ]; then
    echo "ERROR: This script must be run from your Moodle root directory"
    exit 1
fi

# Check if blocks directory exists
if [ ! -d "blocks" ]; then
    echo "ERROR: blocks directory not found"
    exit 1
fi

echo "Installing SCEH Dashboard Block..."

# Copy block to Moodle blocks directory
if [ -d "block_sceh_dashboard" ]; then
    cp -r block_sceh_dashboard blocks/
    echo "✓ Block files copied to blocks/sceh_dashboard"
else
    echo "ERROR: block_sceh_dashboard directory not found"
    exit 1
fi

echo ""
echo "=========================================="
echo "Installation Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Visit: Site Administration → Notifications"
echo "2. Click 'Upgrade Moodle database now'"
echo "3. Add the block to your homepage:"
echo "   - Turn editing on"
echo "   - Click 'Add a block'"
echo "   - Select 'Fellowship Training Dashboard'"
echo ""
echo "The block will automatically show role-appropriate cards:"
echo "  - Trainees: Case Logbook, Competencies, Attendance, etc."
echo "  - Admins: Cohorts, Reports, Training Evaluation, etc."
echo ""
