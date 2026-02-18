# Scripts Directory

This directory contains utility scripts for configuring, verifying, and testing the Moodle fellowship system.

## Directory Structure

- `config/` - Configuration scripts (configure_*.php)
- `verify/` - Verification scripts (verify_*.php)
- `test/` - Test scripts (property_test_*.php, test_*_integration.php, create_*.php)
- Root level - Utility scripts (check_*.php, fix_*.php, install_*.sh, etc.)

## Usage

All scripts should be run from the project root or inside the Docker container:

```bash
# From host
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_badge_system.php

# Inside container
php scripts/verify/verify_competency_framework_structure.php
```

## Script Categories

### Configuration Scripts (config/)
Set up Moodle features and plugins. Run these to initialize system components.

Example (workflow simulation baseline):
```bash
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_workflow_simulation_baseline.php --mode=local
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_workflow_simulation_baseline.php --mode=verify-real-env --dry-run --category-idnumber=allied-health
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_workflow_simulation_baseline.php --mode=apply-real-env --category-idnumber=allied-health
```

### Verification Scripts (verify/)
Validate that configurations are correct and features are working as expected.

### Test Scripts (test/)
Property-based tests and integration tests to ensure system integrity.

### Utility Scripts (root level)
Helper scripts for troubleshooting, installation, and maintenance tasks.
