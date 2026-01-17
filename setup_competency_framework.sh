#!/bin/bash

# Setup script for Moodle Competency Framework Configuration
# This script enables and configures the core competency framework in Moodle

echo "Setting up Moodle Competency Framework..."

# Enable competency framework
docker-compose exec moodle php /bitnami/moodle/admin/cli/cfg.php --name=enablecompetencies --set=1

# Enable learning plans (depends on competencies)
docker-compose exec moodle php /bitnami/moodle/admin/cli/cfg.php --name=enablelearningplans --set=1

# Enable badges system (integrates with competencies)
docker-compose exec moodle php /bitnami/moodle/admin/cli/cfg.php --name=enablebadges --set=1

# Enable completion tracking (required for competency evidence)
docker-compose exec moodle php /bitnami/moodle/admin/cli/cfg.php --name=enablecompletion --set=1

# Enable conditional activities (for competency-based access)
docker-compose exec moodle php /bitnami/moodle/admin/cli/cfg.php --name=enableavailability --set=1

# Enable cohorts (for learner group management)
docker-compose exec moodle php /bitnami/moodle/admin/cli/cfg.php --name=enablecohorts --set=1

# Set competency framework settings
# Allow competency frameworks to be created at site level
docker-compose exec moodle php /bitnami/moodle/admin/cli/cfg.php --name=competencyframeworksperpage --set=20

# Enable competency evidence collection
docker-compose exec moodle php /bitnami/moodle/admin/cli/cfg.php --name=competencyevidenceautoremove --set=0

# Configure learning plan settings
docker-compose exec moodle php /bitnami/moodle/admin/cli/cfg.php --name=learningplansperpage --set=20

echo "Competency framework configuration completed!"

# Verify the settings
echo "Verifying configuration..."
docker-compose exec moodle php /bitnami/moodle/admin/cli/cfg.php --name=enablecompetencies
docker-compose exec moodle php /bitnami/moodle/admin/cli/cfg.php --name=enablelearningplans
docker-compose exec moodle php /bitnami/moodle/admin/cli/cfg.php --name=enablebadges
docker-compose exec moodle php /bitnami/moodle/admin/cli/cfg.php --name=enablecompletion

echo "Setup complete! Moodle competency framework is now enabled."