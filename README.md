# Moodle Fellowship Training System

Comprehensive Moodle-based Learning Management System for medical fellowship training programs, implementing competency-based education with Kirkpatrick evaluation framework.

## Quick Start (MoodleHQ + MySQL)

### Prerequisites

- Docker and Docker Compose installed
- 4GB+ RAM available
- Port 8081 available

### Setup

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd moodle-exploration
   ```

2. **Generate secure environment configuration:**
   ```bash
   ./scripts/generate-env.sh
   ```
   This creates a `.env` file with secure random passwords.

3. **Bootstrap Moodle core (one-time):**
   ```bash
   ./scripts/moodlehq/bootstrap-core.sh
   ```

4. **Start the MoodleHQ stack:**
   ```bash
   docker compose -f docker-compose.moodlehq.yml up -d
   ```

5. **Wait for initialization (first run takes 2-3 minutes):**
   ```bash
   docker compose -f docker-compose.moodlehq.yml logs -f moodle
   ```
   Wait for install/upgrade completion and Apache startup.

6. **Access Moodle:**
   - URL: http://127.0.0.1:8081
   - Admin username: from `.env` (`MOODLEHQ_ADMIN_USER`, default `admin`)
   - Admin password: from `.env` (`MOODLEHQ_ADMIN_PASS`)

### Configuration Scripts

Run configuration scripts to set up features:

```bash
# Configure competency framework
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_badge_system.php

# Verify setup
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/verify/verify_competency_framework_structure.php
```

## Project Structure

```
moodle-exploration/
├── block_sceh_dashboard/          # Dashboard block plugin
├── local_kirkpatrick_dashboard/   # Kirkpatrick evaluation dashboard
├── local_kirkpatrick_level4/      # Level 4 ROI tracking
├── local_sceh_rules/              # Rules engine
├── database_templates/            # Database activity templates
├── scripts/
│   ├── config/                    # Configuration scripts
│   ├── verify/                    # Verification scripts
│   └── test/                      # Test scripts
├── docs/                          # Documentation
├── docker-compose.moodlehq.yml    # Docker configuration
├── .env.example                   # Environment template
└── README.md                      # This file
```

## Documentation

- [Docker Security Configuration](docs/DOCKER_SECURITY.md) - Environment variables and security best practices
- [MoodleHQ + MySQL Dev Stack](docs/MOODLEHQ_MYSQL_DEV_STACK.md) - Azure-friendly development stack
- [Conventions](CONVENTIONS.md) - Coding standards and project conventions
- [Implementation Summary](MOODLE_IMPLEMENTATION_SUMMARY.md) - Complete system overview

## Security

This project uses environment variables for sensitive configuration. See [docs/DOCKER_SECURITY.md](docs/DOCKER_SECURITY.md) for details.

**Important:**
- Never commit `.env` file to version control
- Use `./scripts/generate-env.sh` to create secure passwords

### Recent Security Improvements (2026-02-18)

All critical and high severity security issues have been addressed:

- **CSRF Protection**: POST forms with sesskey validation for destructive operations
- **Input Validation**: MIME type validation for file uploads (extension + content type)
- **Session Security**: 30-minute timeout for import preview workflows
- **Audit Logging**: Moodle event system tracks all package imports
- **Temp File Cleanup**: Automatic cleanup after successful imports
- **Error Handling**: Comprehensive exception handling with Throwable catch
- **Config Security**: Centralized config helper with fallback paths

See [CHECKPOINT_14_VALIDATION_COMPLETE.md](CHECKPOINT_14_VALIDATION_COMPLETE.md) for validation details.

## Development

### Running Tests

```bash
# PHPUnit tests
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/admin/tool/phpunit/cli/init.php
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/vendor/bin/phpunit /var/www/html/public/local/sceh_rules/tests/

# Property-based tests
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/test/property_test_competency_reusability.php
```

### Common Commands

```bash
# View logs
docker compose -f docker-compose.moodlehq.yml logs -f

# Access Moodle container
docker exec -it moodlehq-dev-moodle-1 bash

# Purge caches
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/admin/cli/purge_caches.php
```

## Features

- Competency-based learning framework
- Kirkpatrick 4-level evaluation
- Automated badge system
- Attendance tracking with rules engine
- Fellowship-specific features (case logbook, credentialing)
- Gamification and engagement tracking
- Custom dashboard with role-based views

## Tech Stack

- MoodleHQ Docker + MySQL 8.4
- Docker & Docker Compose

## License

This project follows Moodle's GPL v3 license.

## Support

For issues and questions, see the documentation in the `docs/` directory.
