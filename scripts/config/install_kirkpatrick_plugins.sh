#!/bin/bash
# Install Kirkpatrick Model Evaluation Plugins
# This script clones the required plugins from their official repositories.

set -e

PROJECT_ROOT=$(pwd)

echo "=== Installing Kirkpatrick Model Evaluation Plugins ==="

# 1. mod_questionnaire
echo "Installing mod_questionnaire..."
mkdir -p "$PROJECT_ROOT/mod"
if [ ! -d "$PROJECT_ROOT/mod/questionnaire" ]; then
    git clone --depth 1 https://github.com/PoetOS/moodle-mod_questionnaire.git "$PROJECT_ROOT/mod/questionnaire"
    echo "✓ mod_questionnaire cloned"
else
    echo "✓ mod_questionnaire already exists"
fi

# 2. block_configurable_reports
echo "Installing block_configurable_reports..."
mkdir -p "$PROJECT_ROOT/blocks"
if [ ! -d "$PROJECT_ROOT/blocks/configurable_reports" ]; then
    git clone --depth 1 https://github.com/jleyva/moodle-block_configurablereports.git "$PROJECT_ROOT/blocks/configurable_reports"
    echo "✓ block_configurable_reports cloned"
else
    echo "✓ block_configurable_reports already exists"
fi

echo "=== Installation Complete ==="
