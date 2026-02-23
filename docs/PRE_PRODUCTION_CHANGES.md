# Pre-Production Changes

Items to address before handing off to the production team. Not needed during prototyping.

---

## 1. Custom Docker Image (Bake Plugins Into Image)

**Current state:** All custom plugins are bind-mounted from the host repo. The production team cannot `docker pull` and run — they need the git repo at the exact commit SHA plus correct directory layout.

**Target state:** A single tagged Docker image containing all plugins.

### Dockerfile

```dockerfile
FROM moodlehq/moodle-php-apache:8.2

# Custom plugins
COPY ./local_sceh_rules       /var/www/html/public/local/sceh_rules
COPY ./local_sceh_importer     /var/www/html/public/local/sceh_importer
COPY ./local_kirkpatrick_dashboard /var/www/html/public/local/kirkpatrick_dashboard
COPY ./local_kirkpatrick_level4    /var/www/html/public/local/kirkpatrick_level4
COPY ./block_sceh_dashboard    /var/www/html/public/blocks/sceh_dashboard
COPY ./theme_sceh              /var/www/html/public/theme/sceh
COPY ./mod/attendance          /var/www/html/public/mod/attendance
COPY ./scripts/moodlehq/start-web.sh  /opt/sceh/start-web.sh
COPY ./scripts/moodlehq/start-cron.sh /opt/sceh/start-cron.sh

RUN chown -R www-data:www-data /var/www/html/public
```

### Compose Split (dev vs prod)

```yaml
# docker-compose.yml (production — uses baked image)
services:
  moodle:
    image: sceh-moodle:${GIT_SHA:-latest}
    # No bind-mounts for plugins

# docker-compose.override.yml (dev only — auto-loaded by compose)
services:
  moodle:
    volumes:
      - ./local_sceh_rules:/var/www/html/public/local/sceh_rules
      - ./local_sceh_importer:/var/www/html/public/local/sceh_importer
      # ... etc (keeps live-reload for developers)
```

### Build & Push

```bash
docker build -t sceh-moodle:$(git rev-parse --short HEAD) .
docker push sceh-moodle:$(git rev-parse --short HEAD)
```

### Azure Deployment

Recommended target: **Azure App Service for Containers + Azure Database for MySQL Flexible Server**.

- Managed MySQL (~$25-35/mo burstable) — automatic backups, HA, patching
- App Service (~$30-40/mo B2s) — TLS, zero OS patching
- Cron as a WebJob or sidecar container
- Moodledata on Azure Files mount
- Total: ~$60-80/mo

### Cleanup

Once the Dockerfile controls filesystem layout, remove the Bitnami symlinks from `start-web.sh` (lines 23-28).

---

## 2. Secrets Management

Migrate from `.env` to Azure Key Vault (or equivalent) for production:
- DB passwords
- Admin credentials
- Secret rotation on 90-day cycle
- Audit logging for secret access

---

## 3. Backup Automation

Add a backup sidecar or host cron:
```bash
# Daily DB backup
mysqldump -h $SCEH_DB_HOST -u $SCEH_DB_USER -p$SCEH_DB_PASSWORD $SCEH_DB_NAME | gzip > /backups/moodle_$(date +%Y%m%d).sql.gz

# Weekly moodledata backup
tar -czf /backups/moodledata_$(date +%Y%m%d).tar.gz /var/www/moodledata
```

Document and test a restore runbook.

---

**Document Version:** 1.0
**Created:** 2026-02-23
**Trigger:** Before first production deployment
