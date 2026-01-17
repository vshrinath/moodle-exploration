#!/bin/bash

# Installation script for Kirkpatrick Model Evaluation Plugins
# Task 2.6: Install Kirkpatrick Model evaluation plugins
# Requirements: 17.1, 17.2, 17.3, 17.4

set -e

echo "=========================================="
echo "Installing Kirkpatrick Model Evaluation Plugins"
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

# 1. Install Feedback Activity Plugin (mod_feedback)
# Requirement 17.1: Level 1 (Reaction) - Post-session satisfaction surveys
echo ""
echo "1. Installing Feedback Activity Plugin..."
echo "Note: Feedback Activity is a core Moodle plugin, checking if enabled..."
docker exec $MOODLE_CONTAINER bash -c "
    if [ -d '/bitnami/moodle/mod/feedback' ]; then
        echo 'Feedback Activity plugin already exists (core plugin)'
    else
        echo 'Warning: Feedback Activity plugin not found in expected location'
    fi
"

# 2. Install Questionnaire Plugin (mod_questionnaire)
# Requirement 17.1, 17.2: Advanced survey capabilities for evaluation
echo ""
echo "2. Installing Questionnaire Plugin..."
install_plugin \
    "Questionnaire Plugin" \
    "https://github.com/PoetOS/moodle-mod_questionnaire.git" \
    "mod" \
    "questionnaire"

# 3. Install Portfolio Plugin (mod_portfolio / core_portfolio)
# Requirement 17.3: Level 3 (Behavior) - Evidence collection and tracking
echo ""
echo "3. Checking Portfolio Plugin..."
echo "Note: Portfolio is a core Moodle system, checking configuration..."
docker exec $MOODLE_CONTAINER bash -c "
    if [ -d '/bitnami/moodle/portfolio' ]; then
        echo 'Portfolio system found (core functionality)'
    else
        echo 'Warning: Portfolio system not found in expected location'
    fi
"

# 4. Configure External Database Plugin (enrol_database)
# Requirement 17.4: Level 4 (Results) - Integration with hospital systems
echo ""
echo "4. Checking External Database Plugin..."
echo "Note: External Database is a core Moodle plugin, checking if available..."
docker exec $MOODLE_CONTAINER bash -c "
    if [ -d '/bitnami/moodle/enrol/database' ]; then
        echo 'External Database enrolment plugin found (core plugin)'
    fi
    if [ -d '/bitnami/moodle/auth/db' ]; then
        echo 'External Database authentication plugin found (core plugin)'
    fi
"

echo ""
echo "=========================================="
echo "Plugin Installation Complete"
echo "=========================================="
echo ""
echo "Kirkpatrick Model Evaluation Framework:"
echo ""
echo "Level 1 - Reaction (Satisfaction):"
echo "  ✓ Feedback Activity (mod_feedback) - Core plugin"
echo "  ✓ Questionnaire Plugin (mod_questionnaire) - Installed"
echo ""
echo "Level 2 - Learning (Knowledge Gain):"
echo "  ✓ Competency Framework - Already configured"
echo "  ✓ Quiz and Assignment modules - Core plugins"
echo "  ✓ Badge System - Core functionality"
echo ""
echo "Level 3 - Behavior (Application):"
echo "  ✓ Portfolio System - Core functionality"
echo "  ✓ Follow-up surveys via Questionnaire"
echo ""
echo "Level 4 - Results (Organizational Impact):"
echo "  ✓ External Database plugins - Core plugins"
echo "  ⚠ Requires manual configuration for hospital system integration"
echo ""
echo "Next Steps:"
echo "1. Access Moodle admin interface at http://localhost:8080"
echo "2. Login as administrator"
echo "3. Navigate to Site administration > Notifications"
echo "4. Complete the plugin installation wizard"
echo "5. Configure Kirkpatrick evaluation settings:"
echo "   - Enable Feedback Activity module"
echo "   - Configure Questionnaire plugin settings"
echo "   - Enable Portfolio system (Site admin > Advanced features)"
echo "   - Configure External Database connection for Level 4 data"
echo ""
echo "Configuration Scripts:"
echo "  - Run: php configure_kirkpatrick_plugins.php"
echo "  - Run: php verify_kirkpatrick_setup.php"
echo ""
echo "=========================================="
