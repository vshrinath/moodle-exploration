# Theme Visual Library (Moodle-native)

This project uses `theme_sceh` as the visual layer.  
We borrow visual direction from existing Moodle themes, but implement our own reusable primitives.

## Location
- `theme_sceh/scss/tokens.scss` — shared design tokens
- `theme_sceh/scss/components.scss` — reusable UI primitives
- `theme_sceh/scss/internal.scss` — light-touch internal page styling
- `theme_sceh/scss/login.scss` — login/front (logged-out) experience

## Rules
- Keep business logic in plugins (`local_*`, `block_*`), not in theme files.
- Keep theme overrides small and page-specific.
- Prefer additive styles over replacing Moodle core templates everywhere.
- Reuse `sceh-ui-*` classes for new custom pages/components.

## Borrowing from other Moodle themes
Allowed:
- visual patterns (spacing, hierarchy, card treatment, color systems)
- CSS conventions that do not require deep behavior rewrites

Avoid:
- copying full renderer stacks
- hard coupling to third-party theme internals
- replacing core page behavior when style changes are sufficient

