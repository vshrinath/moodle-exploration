# Card System: Pragmatic Implementation Guide

## Codex's Grounded Decisions

This document refines the card system specification with pragmatic scope decisions to prevent over-engineering.

### Scope Lock for Phase 1

**Implement Only:**
- ✅ Helpers: `icon`, `badge`, `button`, `stat`
- ✅ Molecules: `card_header`, `status_indicator`
- ✅ Templates: `simple`, `metric`, `list`, `detail`
- ✅ CSS: Grid + status colors (Bootstrap-based)

**Defer Completely:**
- ❌ Chart card (requires charting library)
- ❌ Activity card (time formatting complexity)
- ❌ Full atom/molecule class split (add only if duplication appears)

### Single Ownership

**Put card renderers in ONE place:**
- ✅ `local_sceh_rules/classes/output/sceh_card.php` (recommended)
- ✅ Single source of truth
- ❌ Don't split ownership across plugins
- ❌ Don't create separate atom/molecule classes until duplication appears

**Why local_sceh_rules?**
- Rules pages are first use case
- Central location for shared components
- Dashboard block can depend on it
- Other plugins can use it too

### Don't Over-Atomize First

**Start with one reusable renderer class:**
```php
// local_sceh_rules/classes/output/sceh_card.php
class sceh_card {
    // Private helper methods
    private static function render_icon() { }
    private static function render_badge() { }
    private static function render_button() { }
    private static function render_stat() { }
    private static function render_card_header() { }
    private static function get_status_color() { }
    
    // Public card templates
    public static function simple($config) { }
    public static function metric($config) { }
    public static function list($config) { }
    public static function detail($config) { }
}
```

**Add full atom/molecule class split only if:**
- Helper methods get duplicated across multiple files
- Other plugins need to use atoms independently
- Card templates become too complex (>200 lines each)

**For now: Keep it simple with one class.**


### Use Existing Moodle Patterns

**Reuse what Moodle provides:**
- ✅ `html_writer` for HTML generation
- ✅ Bootstrap utility classes (`badge`, `btn`, `text-success`, etc.)
- ✅ Existing plugin styles (don't reinvent)
- ❌ Don't introduce new frontend framework
- ❌ Don't add build step (webpack, npm, etc.)

**Example:**
```php
// Good: Uses Moodle patterns
return html_writer::tag('span', $text, ['class' => 'badge badge-success']);

// Bad: Custom HTML string concatenation
return '<span class="custom-badge green">' . $text . '</span>';
```

### Validation Criteria for "Done"

**Convert exactly two pages first:**
1. `attendance_rules.php`
2. `roster_rules.php`

**Requirements:**
- ✅ Preserve functionality 1:1 (no feature changes)
- ✅ Responsive behavior (desktop → tablet → mobile)
- ✅ Keyboard navigation (tab order, focus indicators)
- ✅ Status colors work (green/yellow/red based on data)
- ✅ No new dependencies added
- ✅ No console errors or warnings
- ✅ Passes accessibility checks (WCAG AA)

**Test with:**
- Desktop browser (Chrome/Firefox)
- Tablet viewport (768px)
- Mobile viewport (375px)
- Keyboard only (no mouse)
- Screen reader (NVDA/JAWS)

---

## Implementation Plan (2 Days)

### Day 1: Single Renderer Class + CSS (6-8 hours)

**Morning (3-4 hours):**
1. Create `local_sceh_rules/classes/output/sceh_card.php`
2. Implement private helper methods:
   - `render_icon($name, $color, $size)`
   - `render_badge($text, $type)`
   - `render_button($text, $url, $style)`
   - `render_stat($value, $label, $icon)`
   - `render_card_header($config)`
   - `get_status_color($value, $thresholds)`

**Afternoon (3-4 hours):**
3. Implement public card templates:
   - `simple($config)` - Navigation/metrics
   - `metric($config)` - KPIs with thresholds
   - `list($config)` - Items with actions
   - `detail($config)` - Rich content
4. Create `local_sceh_rules/styles/sceh_card_system.css`
5. Test with sample data in isolated test page

### Day 2: Convert Rules Pages (6-8 hours)

**Morning (3-4 hours):**
1. Convert `attendance_rules.php`:
   - Replace table with card grid
   - Add status colors based on affected learners
   - Preserve all existing functionality
   - Test responsive behavior

**Afternoon (3-4 hours):**
2. Convert `roster_rules.php`:
   - Replace table with card grid
   - Add status colors
   - Preserve all existing functionality
   - Test responsive behavior
3. Final testing:
   - Test with mock users
   - Keyboard navigation
   - Mobile/tablet viewports
   - Accessibility checks

---

## Code Structure

### sceh_card.php (Single Class)

```php
<?php
namespace local_sceh_rules\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Card rendering system for SCEH Learning Platform.
 * 
 * Provides consistent card-based UI across all pages.
 * Uses Bootstrap classes and Moodle html_writer for compatibility.
 * 
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sceh_card {
    
    // ============================================
    // PRIVATE HELPER METHODS
    // ============================================
    
    /**
     * Render an icon using FontAwesome.
     * 
     * @param string $name Icon name (without 'fa-' prefix)
     * @param string|null $color Bootstrap color class (success, danger, etc.)
     * @param string $size Icon size (default, lg, 2x, 3x)
     * @return string HTML
     */
    private static function render_icon($name, $color = null, $size = 'default') {
        $classes = ['fa', 'fa-' . $name];
        if ($color) {
            $classes[] = 'text-' . $color;
        }
        if ($size !== 'default') {
            $classes[] = 'fa-' . $size;
        }
        return \html_writer::tag('i', '', [
            'class' => implode(' ', $classes),
            'aria-hidden' => 'true'
        ]);
    }
    
    /**
     * Render a badge.
     * 
     * @param string $text Badge text
     * @param string $type Bootstrap badge type (success, info, warning, danger, secondary)
     * @return string HTML
     */
    private static function render_badge($text, $type = 'info') {
        return \html_writer::tag('span', $text, [
            'class' => 'badge badge-' . $type
        ]);
    }
    
    /**
     * Render a button/link.
     * 
     * @param string $text Button text
     * @param \moodle_url|string $url Button URL
     * @param string $style Bootstrap button style (primary, secondary, success, danger, link)
     * @return string HTML
     */
    private static function render_button($text, $url, $style = 'primary') {
        return \html_writer::link($url, $text, [
            'class' => 'btn btn-' . $style
        ]);
    }
    
    /**
     * Render a stat display (value + label).
     * 
     * @param string $value Stat value
     * @param string $label Stat label
     * @param string|null $icon Optional icon name
     * @return string HTML
     */
    private static function render_stat($value, $label, $icon = null) {
        $html = '';
        if ($icon) {
            $html .= self::render_icon($icon, null, 'lg') . ' ';
        }
        $html .= \html_writer::div($value, 'sceh-stat-value');
        $html .= \html_writer::div($label, 'sceh-stat-label text-muted');
        return \html_writer::div($html, 'sceh-stat');
    }
    
    /**
     * Render card header with title, badges, subtitle.
     * 
     * @param array $config Header configuration
     * @return string HTML
     */
    private static function render_card_header($config) {
        $html = '';
        
        // Title with optional icon
        $title = '';
        if (!empty($config['icon'])) {
            $title .= self::render_icon($config['icon']) . ' ';
        }
        $title .= $config['title'];
        
        // Badges
        if (!empty($config['badges'])) {
            foreach ($config['badges'] as $badge) {
                $title .= ' ' . self::render_badge($badge['text'], $badge['type']);
            }
        }
        
        $html .= \html_writer::tag('h3', $title, ['class' => 'sceh-card-title']);
        
        // Subtitle
        if (!empty($config['subtitle'])) {
            $html .= \html_writer::div($config['subtitle'], 'sceh-card-subtitle text-muted');
        }
        
        return $html;
    }
    
    /**
     * Determine status color based on value and thresholds.
     * 
     * @param float $value Current value
     * @param array $thresholds Threshold configuration
     * @return string Bootstrap color class (success, info, warning, danger)
     */
    private static function get_status_color($value, $thresholds) {
        if (empty($thresholds)) {
            return 'info';
        }
        
        if ($value >= $thresholds['excellent']) {
            return 'success';
        }
        if ($value >= $thresholds['good']) {
            return 'info';
        }
        if ($value >= $thresholds['warning']) {
            return 'warning';
        }
        return 'danger';
    }
    
    // ============================================
    // PUBLIC CARD TEMPLATES
    // ============================================
    
    /**
     * Simple card: Navigation or quick metric.
     * 
     * Config:
     * - size: 'small', 'medium', 'large', 'full'
     * - status: 'success', 'info', 'warning', 'danger', 'neutral'
     * - icon: Icon name
     * - title: Card title
     * - value: Metric value
     * - label: Metric label
     * - url: Link URL
     * 
     * @param array $config Card configuration
     * @return string HTML
     */
    public static function simple($config) {
        // Implementation
    }
    
    /**
     * Metric card: KPI with threshold-based status.
     * 
     * Config:
     * - size: Card size
     * - title: Metric title
     * - value: Current value
     * - threshold: Threshold value
     * - trend: Trend indicator (e.g., '+5%')
     * - icon: Optional icon
     * 
     * @param array $config Card configuration
     * @return string HTML
     */
    public static function metric($config) {
        // Implementation
    }
    
    /**
     * List card: Items with actions.
     * 
     * Config:
     * - size: Card size
     * - status: Overall status
     * - title: Card title
     * - count: Item count
     * - items: Array of items (icon, text, subtext, actions)
     * - footer_actions: Array of footer buttons
     * 
     * @param array $config Card configuration
     * @return string HTML
     */
    public static function list($config) {
        // Implementation
    }
    
    /**
     * Detail card: Rich content with sections.
     * 
     * Config:
     * - size: Card size
     * - status: Card status
     * - title: Card title
     * - badges: Array of badges
     * - stats: Array of stats
     * - sections: Array of content sections
     * - actions: Array of action buttons
     * 
     * @param array $config Card configuration
     * @return string HTML
     */
    public static function detail($config) {
        // Implementation
    }
}
```

---

## CSS Structure

### sceh_card_system.css

```css
/* Card Grid Layout */
.sceh-card-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
}

/* Card Base Styles */
.sceh-card {
    background: #fff;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: box-shadow 0.2s;
}

.sceh-card:hover {
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

/* Card Sizes */
.sceh-card-small {
    min-height: 120px;
    max-height: 200px;
}

.sceh-card-medium {
    min-height: 200px;
}

.sceh-card-large {
    min-height: 300px;
}

.sceh-card-full {
    grid-column: 1 / -1;
}

/* Status Colors (Bootstrap-based) */
.sceh-card.border-success {
    border-left: 4px solid #28a745;
    background: linear-gradient(to right, #f0f9f4 0%, #ffffff 100%);
}

.sceh-card.border-info {
    border-left: 4px solid #17a2b8;
    background: linear-gradient(to right, #f0f8fb 0%, #ffffff 100%);
}

.sceh-card.border-warning {
    border-left: 4px solid #ffc107;
    background: linear-gradient(to right, #fffbf0 0%, #ffffff 100%);
}

.sceh-card.border-danger {
    border-left: 4px solid #dc3545;
    background: linear-gradient(to right, #fdf4f5 0%, #ffffff 100%);
}

.sceh-card.border-secondary {
    border-left: 4px solid #6c757d;
    background: linear-gradient(to right, #f8f9fa 0%, #ffffff 100%);
}

/* Card Typography */
.sceh-card-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.sceh-card-subtitle {
    font-size: 0.875rem;
    margin-bottom: 1rem;
}

/* Stat Display */
.sceh-stat {
    text-align: center;
    padding: 0.5rem;
}

.sceh-stat-value {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
}

.sceh-stat-label {
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

/* Responsive */
@media (max-width: 767px) {
    .sceh-card-grid {
        grid-template-columns: 1fr;
    }
    
    .sceh-card-small,
    .sceh-card-medium,
    .sceh-card-large {
        min-height: auto;
    }
}
```

---

## Summary

**This pragmatic approach:**
- ✅ Starts simple (one class, not three)
- ✅ Uses existing Moodle patterns
- ✅ Defers complexity (charts, activity cards)
- ✅ Validates with two real pages
- ✅ Leaves room to grow (split classes if needed)

**Estimated effort: 2 days**
- Day 1: Build renderer + CSS
- Day 2: Convert rules pages

**This is the simplest path that still fits the architecture.**
