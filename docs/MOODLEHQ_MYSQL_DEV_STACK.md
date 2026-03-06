# MoodleHQ + MySQL Dev Stack (Azure-Friendly)

This project now includes a parallel development stack that avoids legacy Bitnami images and aligns with future Azure hosting.

## Why this stack

- Uses maintained Moodle app image line: `moodlehq/moodle-php-apache`
- Uses MySQL (recommended future target: Azure Database for MySQL Flexible Server)
- Keeps existing plugin development flow in this repo (plugins are bind-mounted)

## What gets created

- Moodle core code in `./moodle-core` (cloned from Moodle upstream branch)
- MySQL data in Docker volume `moodlehq_mysql_data`
- Moodle data in Docker volume `moodlehq_moodledata`

## First-time setup

### One-command provisioning (recommended)

```bash
./scripts/moodlehq/provision.sh
```

This runs the full sequence in order:
- generates `.env` if missing (`./scripts/generate-env.sh`)
- bootstraps Moodle core
- starts Docker services
- reapplies reproducible custom state (`restore-custom-state.sh`)

### Step-by-step provisioning

1. Ensure `.env` exists (`./scripts/generate-env.sh` if needed).
2. Add MoodleHQ variables to `.env` (see `.env.example`).
3. Bootstrap Moodle core:

```bash
./scripts/moodlehq/bootstrap-core.sh
```

Default branch is `MOODLE_501_STABLE`. Override if needed:

```bash
MOODLE_GIT_BRANCH=MOODLE_500_STABLE ./scripts/moodlehq/bootstrap-core.sh
```

4. Start stack:

```bash
docker compose -f docker-compose.moodlehq.yml up -d
```

5. Open Moodle:

- URL: `http://127.0.0.1:${MOODLEHQ_WEB_PORT}` (default `8081`)
- Admin user: `${MOODLEHQ_ADMIN_USER}` (default `admin`)
- Admin password: `${MOODLEHQ_ADMIN_PASS}`

6. Register mounted custom plugins (first run):

```bash
docker exec moodlehq-dev-moodle-1 php /var/www/html/admin/cli/upgrade.php --non-interactive
```

7. Re-apply committed baseline customizations after a reset/new environment:

```bash
./scripts/moodlehq/restore-custom-state.sh
```

This script restores the reproducible parts of your customized setup:
- Ensures `mod/questionnaire` and `block_configurable_reports` exist
- Runs Moodle upgrade
- Finalizes admin setup from `.env` credentials
- Applies workflow baseline config (`--mode=local`)
- Re-adds `block_sceh_dashboard` to homepage/dashboard
- Sets active theme to `sceh`
- Purges caches

## Notes

- This stack runs in parallel with the legacy stack by using port `8081` by default.
- Existing custom plugins are mounted from this repo into Moodle core paths.
- On Moodle 5.1+, plugin code is mounted under `moodle-core/public/...` because `dirroot` is `/var/www/html/public`.
- Existing scripts using `/bitnami/moodle` keep working via compatibility symlinks created at container startup.
- This flow works in WSL2 local development when Docker Desktop integration with your WSL distro is enabled.
- On WSL2, run scripts from the Linux filesystem path (for example `/home/<user>/...`) and not from a Windows-mounted path for best bind-mount reliability.

## Stop / restart

```bash
docker compose -f docker-compose.moodlehq.yml down
docker compose -f docker-compose.moodlehq.yml up -d
```

## Reset this dev stack

```bash
docker compose -f docker-compose.moodlehq.yml down -v
rm -rf moodle-core
```

This removes MySQL + moodledata volumes for the MoodleHQ stack.

After reset, run:

```bash
./scripts/moodlehq/bootstrap-core.sh
./scripts/moodlehq/restore-custom-state.sh
```
