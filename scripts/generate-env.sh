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
MARIADB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-32)
MARIADB_ROOT_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-32)

# Create .env file
cat > "$ENV_FILE" << EOF
# Moodle Docker Environment Configuration
# Generated on $(date)
# NEVER commit this file to version control!

# Database Configuration
MARIADB_USER=bn_moodle
MARIADB_DATABASE=bitnami_moodle
MARIADB_PASSWORD=${MARIADB_PASSWORD}
MARIADB_ROOT_PASSWORD=${MARIADB_ROOT_PASSWORD}

# Moodle Configuration
BITNAMI_DEBUG=false
EOF

# Set restrictive permissions
chmod 600 "$ENV_FILE"

echo "✅ .env file created successfully!"
echo ""
echo "📋 Configuration:"
echo "   Database User: bn_moodle"
echo "   Database Name: bitnami_moodle"
echo "   Debug Mode: false"
echo ""
echo "🔒 Passwords have been generated and saved to .env"
echo "   File permissions set to 600 (owner read/write only)"
echo ""
echo "⚠️  IMPORTANT:"
echo "   1. Keep .env file secure and never commit it to git"
echo "   2. Backup your passwords in a secure password manager"
echo "   3. To view passwords: cat .env"
echo ""
echo "🚀 You can now start the containers with: docker-compose up -d"
