# Moodle Fellowship Training System

Comprehensive Moodle-based Learning Management System for medical fellowship training programs, implementing competency-based education with Kirkpatrick evaluation framework.

## Quick Start

### Prerequisites

- Docker and Docker Compose installed
- 4GB+ RAM available
- Ports 8080 and 8443 available

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

3. **Start the containers:**
   ```bash
   docker-compose up -d
   ```

4. **Wait for initialization (first run takes 2-3 minutes):**
   ```bash
   docker-compose logs -f moodle
   ```
   Wait for "Apache started" message.

5. **Access Moodle:**
   - URL: http://localhost:8080
   - Admin username: `user`
   - Admin password: Check your Moodle admin panel

### Configuration Scripts

Run configuration scripts to set up features:

```bash
# Configure competency framework
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/scripts/config/configure_badge_system.php

# Verify setup
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/scripts/verify/verify_competency_framework_structure.php
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
├── docker-compose.yml             # Docker configuration
├── .env.example                   # Environment template
└── README.md                      # This file
```

## Documentation

- [Docker Security Configuration](docs/DOCKER_SECURITY.md) - Environment variables and security best practices
- [Quick Start Guide](QUICK_START_GUIDE.md) - Getting started with the system
- [Conventions](CONVENTIONS.md) - Coding standards and project conventions
- [Implementation Summary](MOODLE_IMPLEMENTATION_SUMMARY.md) - Complete system overview

## Security

This project uses environment variables for sensitive configuration. See [docs/DOCKER_SECURITY.md](docs/DOCKER_SECURITY.md) for details.

**Important:**
- Never commit `.env` file to version control
- Use `./scripts/generate-env.sh` to create secure passwords
- Set `BITNAMI_DEBUG=false` in production

## Development

### Running Tests

```bash
# PHPUnit tests
docker exec moodle-exploration-moodle-1 vendor/bin/phpunit local/sceh_rules/tests/

# Property-based tests
docker exec moodle-exploration-moodle-1 php /bitnami/moodle/scripts/test/property_test_competency_reusability.php
```

### Common Commands

```bash
# View logs
docker-compose logs -f

# Access Moodle container
docker exec -it moodle-exploration-moodle-1 bash

# Purge caches
docker exec moodle-exploration-moodle-1 php admin/cli/purge_caches.php

# Fix permissions (if needed)
docker exec moodle-exploration-moodle-1 chown -R daemon:daemon /bitnami/moodledata
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

- Moodle 5.0.1
- PHP (Bitnami image)
- MariaDB
- Apache 2.4.64
- Docker & Docker Compose

## License

This project follows Moodle's GPL v3 license.

## Support

For issues and questions, see the documentation in the `docs/` directory or check the task completion reports.
