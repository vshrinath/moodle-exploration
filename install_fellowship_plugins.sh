#!/bin/bash

# Installation script for Ophthalmology Fellowship Plugins
# This script installs and configures plugins for case logbooks, scheduling, payments, and registration

set -e

echo "=========================================="
echo "Ophthalmology Fellowship Plugins Installer"
echo "=========================================="
echo ""

# Check if we're in a Moodle directory
if [ ! -f "version.php" ]; then
    echo "Error: This script must be run from the Moodle root directory"
    exit 1
fi

# Database Activity Plugin (Core - should already be installed)
echo "Step 1: Verifying Database Activity plugin..."
if [ -d "mod/data" ]; then
    echo "✓ Database Activity plugin is available (core module)"
else
    echo "✗ Database Activity plugin not found - this is a core module and should be present"
    exit 1
fi

# Scheduler Plugin Installation
echo ""
echo "Step 2: Installing Scheduler plugin..."
if [ -d "mod/scheduler" ]; then
    echo "✓ Scheduler plugin already installed"
else
    echo "Downloading Scheduler plugin..."
    cd mod
    git clone https://github.com/bostelm/moodle-mod_scheduler.git scheduler
    cd ..
    echo "✓ Scheduler plugin downloaded"
fi

# Payment Gateway Plugins
echo ""
echo "Step 3: Setting up Payment Gateway plugins..."

# PayPal Payment Gateway (Core)
if [ -d "payment/gateway/paypal" ]; then
    echo "✓ PayPal payment gateway available (core)"
else
    echo "⚠ PayPal payment gateway not found - may need Moodle 4.0+"
fi

# Note: Razorpay and Stripe require third-party plugins
echo ""
echo "Note: For Razorpay and Stripe payment gateways:"
echo "  - Razorpay: Install from https://github.com/razorpay/moodle-payment_razorpay"
echo "  - Stripe: Install from https://github.com/catalyst/moodle-paygw_stripe"
echo "  These can be installed manually based on your requirements"

# Custom User Profile Fields (Core functionality)
echo ""
echo "Step 4: Verifying custom user profile fields capability..."
if [ -d "user/profile" ]; then
    echo "✓ Custom user profile fields functionality available (core)"
else
    echo "✗ Custom user profile fields not found"
    exit 1
fi

# Run Moodle upgrade to install plugins
echo ""
echo "Step 5: Running Moodle upgrade to install plugins..."
echo "Please run the following command or visit /admin in your browser:"
echo "  php admin/cli/upgrade.php --non-interactive"
echo ""

# Provide next steps
echo "=========================================="
echo "Installation Complete!"
echo "=========================================="
echo ""
echo "Next Steps:"
echo "1. Run: php admin/cli/upgrade.php --non-interactive"
echo "2. Run: php configure_fellowship_plugins.php"
echo "3. Run: php verify_fellowship_setup.php"
echo ""
echo "Manual Configuration Required:"
echo "- Set up payment gateway accounts (PayPal/Razorpay/Stripe)"
echo "- Configure custom user profile fields via Site administration > Users > User profile fields"
echo "- Create Database Activity templates for case logbooks and credentialing"
echo ""
