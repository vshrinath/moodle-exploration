# SCEH Login Branding Theme (Moodle-native)

## What this implements
- A Moodle child theme (`theme_sceh`) that customizes only the **login page**.
- No React runtime, no frontend build tooling, no new infrastructure components.
- Responsive behavior for desktop, tablet, and mobile.

## Mapping from `brand/assets/shroff.pen`

### Mapped elements
- Branded header/identity block
  - `SCEH Learning Platform`
  - `Dr. Shroff's Charity Eye Hospital`
- Two-column hero + login composition
  - Left: brand message and bullets
  - Right: native Moodle login form (`core/loginform`)
- Distinct CTA styling
  - Primary login button color and card treatment
- Atmospheric background
  - Gradient/radial treatment to match the visual direction from the `.pen` design

### Intentionally not mapped (for maintainability)
- React component system
- External image dependencies
- Frontpage internal-page redesign

## Files
- `theme_sceh/config.php` — Theme registration and login layout override
- `theme_sceh/layout/login.php` — Login layout entrypoint
- `theme_sceh/templates/login.mustache` — Branded login page structure
- `theme_sceh/scss/login.scss` — Responsive styling
- `theme_sceh/lib.php` — SCSS callback integrating with Boost styles
- `theme_sceh/lang/en/theme_sceh.php` — Branding copy strings
- `docker-compose.moodlehq.yml` — Mounts the custom theme into Moodle container

## Responsive behavior
- `>=1024px`: two-column split hero + login card
- `576px - 1023px`: stacked layout, hero first, login card second
- `<576px`: compact spacing and tighter typography

## Activate in current local stack
1. Restart containers so theme files are mounted:
   - `docker compose -f docker-compose.moodlehq.yml up -d`
2. Run Moodle upgrade to register theme:
   - `docker exec moodlehq-dev-moodle-1 php /var/www/html/public/admin/cli/upgrade.php --non-interactive`
3. Set active theme:
   - `docker exec moodlehq-dev-moodle-1 php /var/www/html/public/admin/cli/cfg.php --name=theme --set=sceh`
4. Purge caches:
   - `docker exec moodlehq-dev-moodle-1 php /var/www/html/public/admin/cli/purge_caches.php`

