# Engineering Handover — Moodle Fellowship System

This document provides technical instructions for setting up and building the SCEH Moodle Fellowship Training System.

## Stack Overview
- **Moodle 5.0.1** (PHP/MySQL)
- **Docker & Docker Compose** (Primary development environment)
- **MariaDB** (Database)
- **Apache 2.4** (Web Server)

---

## 💻 Windows Setup (Important)

The project is optimized for Docker, but relies on **Bash scripts** for initial setup. Windows engineers should use:

1.  **Docker Desktop** (with WSL2 backend).
2.  **WSL2** (Ubuntu/Debian) or **Git Bash** to run the `./scripts/` directory files.
3.  **RAM**: Ensure at least 4GB of RAM is allocated to Docker.

---

## 🚀 Quick Start Instructions

Follow these steps to get a local development instance running:

### 1. Generate Environment File
Run the following from the project root:
```bash
./scripts/generate-env.sh
```
*Note: This creates a `.env` file with secure random passwords. DO NOT commit this file.*

### 2. Bootstrap Moodle Core
The repository contains custom plugins and configuration, but not Moodle core itself. Clone it using:
```bash
./scripts/moodlehq/bootstrap-core.sh
```
*This clones Moodle 5.0.1 into the `moodle-core` directory.*

### 3. Start the Stack
```bash
docker compose -f docker-compose.moodlehq.yml up -d
```
*Wait ~3 minutes for the first-run database installation to complete. Monitor with `docker compose -f docker-compose.moodlehq.yml logs -f moodle`.*

### 4. Verify & Access
- **URL**: http://127.0.0.1:8081
- **Admin**: Credentials generated in `.env` (`MOODLEHQ_ADMIN_USER`/`MOODLEHQ_ADMIN_PASS`)

---

## 🛠 Project Structure & Conventions

- **Custom Plugins**: 
    - `local_sceh_rules/` (Rules engine)
    - `theme_sceh/` (Branding)
    - `block_sceh_dashboard/` (LMS dashboard)
- **Coding Standards**: See [CONVENTIONS.md](CONVENTIONS.md) for Moodle-specific patterns.
- **Database Templates**: See `database_templates/` for pre-configured XML presets.

## 🧪 Testing
- **PHPUnit**: Initialize and run via the `moodle` container.
- **Verification Scripts**: Use `docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/verify/verify_...` to validate your setup.

---

## 🆘 Troubleshooting
- **Permissions**: If you see directory creation errors, run:
  `docker exec moodlehq-dev-moodle-1 chown -R daemon:daemon /bitnami/moodledata`
- **Cache**: When modifying UI/string files, run:
  `docker exec moodlehq-dev-moodle-1 php /var/www/html/public/admin/cli/purge_caches.php`
