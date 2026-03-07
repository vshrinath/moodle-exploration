#!/bin/bash
# Generate secure .env file with random passwords
# Usage: ./scripts/generate-env.sh

set -e

ENV_FILE=".env"

# Check if .env already exists
if [ -f "$ENV_FILE" ]; then
    echo "⚠️  .env file already exists!"
    read -p "Do you want to overwrite it? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Aborted. Existing .env file preserved."
        exit 0
    fi
fi

# Generate secure random passwords
echo "🔐 Generating secure passwords..."
MOODLEHQ_DB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-32)
MOODLEHQ_DB_ROOT_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-32)
MOODLEHQ_ADMIN_PASS="Test@2026!"

# Create .env file
cat > "$ENV_FILE" << EOF
# Moodle Docker Environment Configuration
# Generated on $(date)
# NEVER commit this file to version control!

# MoodleHQ + MySQL Dev Stack
MOODLEHQ_DB_NAME=moodle
MOODLEHQ_DB_USER=moodle
MOODLEHQ_DB_PASSWORD=${MOODLEHQ_DB_PASSWORD}
MOODLEHQ_DB_ROOT_PASSWORD=${MOODLEHQ_DB_ROOT_PASSWORD}
MOODLEHQ_WEB_PORT=8081
MOODLEHQ_WWWROOT=http://127.0.0.1:8081
MOODLEHQ_ADMIN_USER=admin
MOODLEHQ_ADMIN_PASS=${MOODLEHQ_ADMIN_PASS}
MOODLEHQ_ADMIN_EMAIL=admin@example.com
MOODLEHQ_SITE_FULLNAME=SCEH Fellowship Training
MOODLEHQ_SITE_SHORTNAME=SCEH LMS
MOODLEHQ_CRON_INTERVAL=60
EOF

# Set restrictive permissions
chmod 600 "$ENV_FILE"

echo "✅ .env file created successfully!"
echo ""
echo "📋 Configuration:"
echo "   Database User: moodle"
echo "   Database Name: moodle"
echo "   MoodleHQ Port: 8081"
echo ""
echo "🔒 Passwords have been generated and saved to .env"
echo "   File permissions set to 600 (owner read/write only)"
echo ""
echo "⚠️  IMPORTANT:"
echo "   1. Keep .env file secure and never commit it to git"
echo "   2. Backup your passwords in a secure password manager"
echo "   3. To view passwords: cat .env"
echo ""
echo "🚀 You can now start the stack with:"
echo "   docker compose -f docker-compose.moodlehq.yml up -d"
