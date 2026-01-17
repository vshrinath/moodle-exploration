#!/bin/bash

# Install Configurable Reports Plugin for Moodle
echo "Installing Configurable Reports Plugin..."

# Download the plugin
cd /tmp
wget -O configurable_reports.zip "https://github.com/jleyva/moodle-block_configurable_reports/archive/refs/heads/MOODLE_405_STABLE.zip"

# Extract the plugin
unzip configurable_reports.zip

# Move to the correct location
mv moodle-block_configurable_reports-MOODLE_405_STABLE /opt/bitnami/moodle/blocks/configurable_reports

# Set proper permissions
chown -R daemon:daemon /opt/bitnami/moodle/blocks/configurable_reports
chmod -R 755 /opt/bitnami/moodle/blocks/configurable_reports

echo "Configurable Reports plugin installed successfully!"
echo "Please run the Moodle upgrade process to complete installation."