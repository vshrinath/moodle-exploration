#!/bin/bash
# Install Kirkpatrick Model Evaluation Plugins
# This script clones the required plugins from their official repositories.

set -e

PROJECT_ROOT=$(pwd)

echo "=== Installing Kirkpatrick Model Evaluation Plugins ==="

# 1. mod_questionnaire
echo "Installing mod_questionnaire..."
mkdir -p "$PROJECT_ROOT/mod"

# Cleanup empty directory if it exists (potential Docker volume race condition)
if [ -d "$PROJECT_ROOT/mod/questionnaire" ] && [ ! -f "$PROJECT_ROOT/mod/questionnaire/version.php" ]; then
    echo "Found empty mod/questionnaire directory, removing to allow clone..."
    rm -rf "$PROJECT_ROOT/mod/questionnaire"
fi

if [ ! -d "$PROJECT_ROOT/mod/questionnaire" ]; then
    git clone --depth 1 https://github.com/PoetOS/moodle-mod_questionnaire.git "$PROJECT_ROOT/mod/questionnaire"
    echo "✓ mod_questionnaire cloned"
else
    echo "✓ mod_questionnaire already exists and appears valid"
fi

# 2. block_configurable_reports
echo "Installing block_configurable_reports..."
mkdir -p "$PROJECT_ROOT/blocks"

# Cleanup empty directory if it exists
if [ -d "$PROJECT_ROOT/blocks/configurable_reports" ] && [ ! -f "$PROJECT_ROOT/blocks/configurable_reports/version.php" ]; then
    echo "Found empty blocks/configurable_reports directory, removing to allow clone..."
    rm -rf "$PROJECT_ROOT/blocks/configurable_reports"
fi

if [ ! -d "$PROJECT_ROOT/blocks/configurable_reports" ]; then
    git clone --depth 1 https://github.com/jleyva/moodle-block_configurablereports.git "$PROJECT_ROOT/blocks/configurable_reports"
    echo "✓ block_configurable_reports cloned"
else
    echo "✓ block_configurable_reports already exists and appears valid"
fi

echo "=== Installation Complete ==="
