# SCEH LMS — Installation Guide

Last updated: 2026-02-22

---

## Prerequisites

- Docker and Docker Compose
- Git

---

## 1. Clone the Repository

```bash
git clone https://github.com/vshrinath/moodle-exploration.git
cd moodle-exploration
git checkout front-end-explorations
```

---

## 2. Configure Environment

Copy the example env file and set the required passwords:

```bash
cp .env.example .env
```

Edit `.env` and set at minimum:
- `MOODLEHQ_DB_PASSWORD` — MySQL user password
- `MOODLEHQ_DB_ROOT_PASSWORD` — MySQL root password
- `MOODLEHQ_ADMIN_PASS` — Moodle site admin password

Optional overrides:
- `MOODLEHQ_WEB_PORT` — Web port (default: `8081`)
- `MOODLEHQ_WWWROOT` — Full site URL (default: `http://127.0.0.1:8081`, change for Azure/production)

---

## 3. Start Services

```bash
docker compose -f docker-compose.moodlehq.yml up -d
```

Three containers will start:
- `moodlehq-dev-moodle-1` — Moodle web server (PHP 8.2 + Apache)
- `moodlehq-dev-mysql-1` — MySQL 8.4
- `moodlehq-dev-moodle_cron-1` — Background cron runner

Moodle will be available at **http://127.0.0.1:8081** (or your configured port/domain).

---

## 4. Apply Baseline Configuration

After first startup, run these scripts to set up roles, mock users, cohorts, and completion tracking:

```bash
# Create mock users, roles, cohorts, and capabilities
docker exec moodlehq-dev-moodle-1 \
  php /var/www/html/public/scripts/config/configure_workflow_simulation_baseline.php --mode=local

# Enable completion tracking on all courses and activities
docker exec moodlehq-dev-moodle-1 \
  php /var/www/html/public/scripts/config/configure_completion_tracking.php --apply
```

Both scripts are idempotent — safe to run multiple times.

---

## 5. Test Accounts

Four mock accounts are created by the baseline script:

| Username | Role | Dashboard |
|---|---|---|
| `mock.sysadmin` | System Admin | Cohorts, Reports, Badges, Competency Framework, system health |
| `mock.programowner` | Program Owner | Manage Courses, Stream Setup Check, Content Import, Competencies |
| `mock.trainer` | Trainer | My Courses (expandable), Attendance Reports, Training Evaluation |
| `mock.learner` | Learner | Your Stream, My Progress, Deadlines, Competencies, Badges |

### Accessing mock accounts

1. Log in as site admin (`admin` + your configured password)
2. Site administration → Users → browse to the mock user
3. Click **"Log in as"** to switch to that user's view
4. Alternative: reset a mock user's password from their profile, then log in directly

---

## 6. Creating a Persistent System Admin

Mock users are for testing. To create a real admin account:

### Via Moodle UI

1. Log in as site admin
2. Site administration → Users → **Add a new user**
3. Fill in username, name, email, set a password
4. Go to Site administration → Users → Permissions → **Assign system roles**
5. Select **sceh_system_admin** → add the new user

### Via CLI

```bash
# Create the user
docker exec moodlehq-dev-moodle-1 \
  php /var/www/html/public/admin/cli/create_user.php \
  --username=real.admin \
  --password='SecurePass!2026' \
  --firstname=First \
  --lastname=Last \
  --email=admin@sceh.org

# Assign system admin role
docker exec moodlehq-dev-moodle-1 php -r '
define("CLI_SCRIPT", true);
require("/var/www/html/public/config.php");
require_once($CFG->libdir . "/accesslib.php");
global $DB;
$user = $DB->get_record("user", ["username"=>"real.admin"], "id", MUST_EXIST);
$role = $DB->get_record("role", ["shortname"=>"sceh_system_admin"], "id", MUST_EXIST);
role_assign($role->id, $user->id, context_system::instance()->id);
echo "Done.\n";
'
```

---

## 7. Navigation

- **SCEH logo** (top-left) — always returns to your dashboard
- **Help** link in the nav bar — role-aware FAQ page
- Each dashboard card is a direct shortcut — click to open
- Content is stored in the database and survives platform upgrades

---

## 8. Custom Plugins

All custom code is volume-mounted into the container from the repo:

| Repo directory | Container path | Purpose |
|---|---|---|
| `block_sceh_dashboard/` | `/var/www/html/public/blocks/sceh_dashboard/` | Role-based dashboard cards |
| `local_sceh_rules/` | `/var/www/html/public/local/sceh_rules/` | Stream logic, progress, help page, card renderer |
| `local_sceh_importer/` | `/var/www/html/public/local/sceh_importer/` | Course content ZIP importer |
| `theme_sceh/` | `/var/www/html/public/theme/sceh/` | SCEH branding and theme |
| `scripts/` | `/var/www/html/public/scripts/` | Setup and config scripts |

Changes to these directories are immediately reflected in the running container (no rebuild needed). Purge caches after changes:

```bash
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/admin/cli/purge_caches.php
```

---

## 9. Azure Deployment

The same `docker-compose.moodlehq.yml` works on Azure. Key changes:

1. Set `MOODLEHQ_WWWROOT` to your Azure domain (e.g., `https://sceh-lms.azurewebsites.net`)
2. Use Azure Database for MySQL instead of the local MySQL container (update DB env vars)
3. Use Azure Blob Storage or a persistent volume for `moodledata`
4. Set strong passwords for all `MOODLEHQ_*` vars

---

## 10. Reference Docs

| File | Contents |
|---|---|
| `docs/USER_FAQ.md` | End-user guide by role |
| `docs/SYSTEM_FAQ.md` | Architecture and technical decisions |
| `docs/MOCK_USERS_SETUP.md` | Mock user details, CLI commands, troubleshooting |
| `docs/RELEASE_NOTES.md` | Full change history |
| `docs/COURSE_PACKAGE_IMPORT_BLUEPRINT.md` | Content import system design |
