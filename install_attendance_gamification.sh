#!/bin/bash

# Installation script for Attendance and Gamification plugins
# Task 2.5: Install attendance and gamification plugins
# Requirements: 14.1, 15.2, 16.1

set -e

echo "=========================================="
echo "Installing Attendance and Gamification Plugins"
echo "=========================================="

# Get Moodle container ID
MOODLE_CONTAINER=$(docker ps --filter "name=moodle" --format "{{.ID}}" | head -n 1)

if [ -z "$MOODLE_CONTAINER" ]; then
    echo "Error: Moodle container not found. Please ensure Docker containers are running."
    exit 1
fi

echo "Found Moodle container: $MOODLE_CONTAINER"

# Install git in the container if not present
echo ""
echo "Checking for git installation..."
docker exec $MOODLE_CONTAINER bash -c "
    if ! command -v git &> /dev/null; then
        echo 'Git not found, installing...'
        apt-get update -qq
        apt-get install -y -qq git
        echo 'Git installed successfully'
    else
        echo 'Git already installed'
    fi
"

# Function to install plugin from Git
install_plugin() {
    local plugin_name=$1
    local git_url=$2
    local plugin_type=$3
    local plugin_dir=$4
    
    echo ""
    echo "Installing $plugin_name..."
    echo "Repository: $git_url"
    
    docker exec $MOODLE_CONTAINER bash -c "
        cd /bitnami/moodle/$plugin_type
        if [ -d '$plugin_dir' ]; then
            echo 'Plugin directory already exists, removing...'
            rm -rf $plugin_dir
        fi
        git clone $git_url $plugin_dir
        chown -R daemon:daemon $plugin_dir
        echo '$plugin_name installed successfully'
    "
}

# 1. Install Attendance Plugin (mod_attendance)
# Requirement 14.1: Session attendance tracking
echo ""
echo "1. Installing Attendance Plugin..."
install_plugin \
    "Attendance Plugin" \
    "https://github.com/danmarsden/moodle-mod_attendance.git" \
    "mod" \
    "attendance"

# 2. Install Level Up! Plugin (block_xp)
# Requirement 16.1: Gamification with XP points and leveling
echo ""
echo "2. Installing Level Up! Plugin..."
install_plugin \
    "Level Up! Plugin" \
    "https://github.com/FMCorz/moodle-block_xp.git" \
    "blocks" \
    "xp"

# 3. Install Stash Plugin (block_stash)
# Requirement 16.1: Collectible items and engagement rewards
echo ""
echo "3. Installing Stash Plugin..."
install_plugin \
    "Stash Plugin" \
    "https://github.com/branchup/moodle-block_stash.git" \
    "blocks" \
    "stash"

# 4. Install Custom Certificate Plugin (mod_customcert)
# Requirement 15.2: Competency-based certification
echo ""
echo "4. Installing Custom Certificate Plugin..."
install_plugin \
    "Custom Certificate Plugin" \
    "https://github.com/mdjnelson/moodle-mod_customcert.git" \
    "mod" \
    "customcert"

echo ""
echo "=========================================="
echo "Plugin Installation Complete"
echo "=========================================="
echo ""
echo "Next Steps:"
echo "1. Access Moodle admin interface at http://localhost:8080"
echo "2. Login as administrator"
echo "3. Navigate to Site administration > Notifications"
echo "4. Complete the plugin installation wizard for each plugin"
echo "5. Configure plugin settings as needed"
echo ""
echo "Installed Plugins:"
echo "  - Attendance Plugin (mod_attendance) - Session management"
echo "  - Level Up! Plugin (block_xp) - XP points and gamification"
echo "  - Stash Plugin (block_stash) - Collectible rewards"
echo "  - Custom Certificate Plugin (mod_customcert) - Digital credentials"
echo ""
echo "=========================================="
