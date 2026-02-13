# SCEH Card System Specification

## Overview

This document defines the pragmatic card component system for the SCEH Learning Platform. Phase 1 uses one renderer class with internal helper methods to build flexible, consistent UI cards.

## Design Philosophy

**Pragmatic Composition Approach (Phase 1):**
- **Helpers (private methods)**: Basic building blocks (icon, text, badge, button, stat)
- **Template methods (public)**: Complete cards assembled from helper methods
- **Card grid layouts**: Page-level composition

**Key Principles:**
1. **Composability**: Build complex cards from simple components
2. **Consistency**: Same visual language across all pages
3. **Flexibility**: Support multiple content types and layouts
4. **Simplicity**: Easy to use, hard to misuse
5. **Responsiveness**: Works on all screen sizes

---

## Card Sizes

### Size Classes

```css
/* Small: Quick metrics, navigation */
.sceh-card-small {
    min-height: 120px;
    max-height: 200px;
}

/* Medium: Standard cards with moderate content */
.sceh-card-medium {
    min-height: 200px;
    max-height: 400px;
}

/* Large: Detailed cards with rich content */
.sceh-card-large {
    min-height: 300px;
    max-height: 600px;
}

/* Auto: Content-driven height */
.sceh-card-auto {
    min-height: 120px;
    max-height: none;
}

/* Full-width: Spans entire grid width */
.sceh-card-full {
    grid-column: 1 / -1;
}
```


### Responsive Behavior

```css
/* Desktop: 3 columns */
@media (min-width: 992px) {
    .sceh-card-grid { grid-template-columns: repeat(3, 1fr); }
    .sceh-card-small { grid-column: span 1; }
    .sceh-card-medium { grid-column: span 1; }
    .sceh-card-large { grid-column: span 2; }
}

/* Tablet: 2 columns */
@media (min-width: 768px) and (max-width: 991px) {
    .sceh-card-grid { grid-template-columns: repeat(2, 1fr); }
    .sceh-card-small { grid-column: span 1; }
    .sceh-card-medium { grid-column: span 1; }
    .sceh-card-large { grid-column: span 2; }
}

/* Mobile: 1 column */
@media (max-width: 767px) {
    .sceh-card-grid { grid-template-columns: 1fr; }
    .sceh-card-small,
    .sceh-card-medium,
    .sceh-card-large { grid-column: span 1; }
}
```

---

## Phase 1 Renderer API (Single Class)

Phase 1 uses one renderer class (`local_sceh_rules/classes/output/sceh_card.php`) with private helper methods and four public templates. This is the implementation source of truth.

**Helper methods (private):**
```php
render_icon($name, $color = null, $size = 'default')
render_badge($text, $type = 'info')
render_button($text, $url, $style = 'primary')
render_stat($value, $label, $icon = null)
render_card_header($config)
status_indicator($status, $text, $details = null)
```

**Moodle/Bootstrap compatibility note:**
- Prefer Moodle-compatible utility classes (`btn`, `btn-primary`, `text-success`, etc.)
- For badge styles, verify class names against the active Moodle theme before implementation.

---

## Card Templates (Phase 1)

### Template 1: Simple Card (Navigation/Metrics)
**Use for:** Dashboard navigation, quick metrics

```php
sceh_card::simple([
    'size' => 'small',
    'status' => 'success',
    'icon' => 'fa-users',
    'title' => 'Manage Cohorts',
    'value' => '24',
    'label' => 'Active cohorts',
    'url' => '/cohort/index.php'
]);
```

**Renders:**
```
┌─────────────────┐
│ 👥 Manage       │
│    Cohorts      │
│                 │
│     24          │
│ Active cohorts  │
└─────────────────┘
```

### Template 2: Metric Card (Stats Display)
**Use for:** KPIs, performance metrics

```php
sceh_card::metric([
    'size' => 'small',
    'status' => 'warning',
    'title' => 'Attendance Rate',
    'value' => '78%',
    'trend' => '+5%',
    'threshold' => 80,
    'details' => '2 learners below threshold'
]);
```

**Renders:**
```
┌─────────────────┐
│ Attendance Rate │
│                 │
│      78%        │
│    ↑ +5%        │
│                 │
│ 🟡 Below target │
└─────────────────┘
```

### Template 3: List Card (Items with Actions)
**Use for:** Rules, cohorts, at-risk learners

```php
sceh_card::list([
    'size' => 'medium',
    'status' => 'danger',
    'title' => 'At-Risk Learners',
    'count' => 5,
    'items' => [
        ['icon' => 'user', 'text' => 'Dr. Kumar', 'subtext' => '45% attendance', 'actions' => [...]],
        ['icon' => 'user', 'text' => 'Dr. Singh', 'subtext' => '3 overdue tasks', 'actions' => [...]]
    ],
    'footer_actions' => [
        ['text' => 'View All', 'url' => '...'],
        ['text' => 'Send Reminders', 'url' => '...']
    ]
]);
```

**Renders:**
```
┌─────────────────────────────┐
│ 🔴 At-Risk Learners (5)     │
│ ─────────────────────────── │
│ 👤 Dr. Kumar                │
│    45% attendance           │
│    [View] [Message]         │
│                             │
│ 👤 Dr. Singh                │
│    3 overdue tasks          │
│    [View] [Message]         │
│ ─────────────────────────── │
│ [View All] [Send Reminders] │
└─────────────────────────────┘
```


### Template 4: Detail Card (Rich Content)
**Use for:** Rules, cohort details, program info

```php
sceh_card::detail([
    'size' => 'large',
    'status' => 'success',
    'title' => 'Attendance Rule: Clinical',
    'badges' => [
        ['text' => 'Active', 'type' => 'success'],
        ['text' => 'Auto-Apply', 'type' => 'info']
    ],
    'stats' => [
        ['value' => '75%', 'label' => 'Threshold'],
        ['value' => '0', 'label' => 'Blocking'],
        ['value' => '45', 'label' => 'Applied to']
    ],
    'sections' => [
        [
            'title' => 'Applied to',
            'content' => 'Allied Health Programs'
        ],
        [
            'title' => 'Trend (30 days)',
            'content' => '80% completion in last 30 days'
        ]
    ],
    'actions' => [
        ['text' => 'Edit', 'url' => '...'],
        ['text' => 'View Affected', 'url' => '...'],
        ['text' => 'Disable', 'url' => '...', 'style' => 'secondary']
    ]
]);
```

**Renders:**
```
┌─────────────────────────────────────────┐
│ 🟢 Attendance Rule: Clinical            │
│ [Active] [Auto-Apply]                   │
│ ─────────────────────────────────────── │
│ 75%         0           45              │
│ Threshold   Blocking    Applied to      │
│ ─────────────────────────────────────── │
│ Applied to: Allied Health Programs      │
│                                         │
│ Trend (30 days):                        │
│ ████████████████░░░░ 80%                │
│ ─────────────────────────────────────── │
│ [Edit] [View Affected] [Disable]        │
└─────────────────────────────────────────┘
```

### Deferred Templates (Not in Phase 1)
**Deferred:** Chart and Activity cards

- `chart` template is intentionally deferred until a charting requirement and library decision are confirmed.
- `activity` template is intentionally deferred until timeline/feed behavior is needed.

---

## Simplest Implementation: Single Renderer Class

### Core Philosophy
**Build complex from simple** - Every card is assembled from internal helper methods.

### Implementation Layers

**Layer 1: Helper Methods (private, in `sceh_card`)**
```php
// local_sceh_rules/classes/output/sceh_card.php
class sceh_card {
    private static function render_icon($name, $color = null, $size = 'default') { }
    private static function render_badge($text, $type = 'info') { }
    private static function render_button($text, $url, $style = 'primary') { }
    private static function render_stat($value, $label, $icon = null) { }
    private static function render_card_header($config) { }
    private static function status_indicator($status, $text, $details = null) { }
}
```
**Complexity: LOW** - Simple HTML generation, no extra class split

**Layer 2: Card Templates (4 templates in Phase 1)**
```php
// local_sceh_rules/classes/output/sceh_card.php
class sceh_card {
    public static function simple($config) { }      // Navigation/metrics
    public static function metric($config) { }      // KPIs with thresholds
    public static function list($config) { }        // Items with actions
    public static function detail($config) { }      // Rich content
    
    // Helper for rendering any card
    private static function render($config, $content) {
        $classes = self::get_card_classes($config);
        return html_writer::div($content, implode(' ', $classes));
    }
}
```
**Complexity: MEDIUM** - Assembles molecules, handles layout

### Complexity Analysis

**What's Simple:**
1. ✅ **Helpers** - Just HTML wrappers (1-2 hours total)
2. ✅ **Simple/Metric cards** - Straightforward assembly (1 hour)
3. ✅ **CSS** - Grid + status colors (2 hours)

**What's Moderate:**
4. ⚠️ **List card** - Iteration logic (2 hours)
5. ⚠️ **Detail card** - Multiple sections (2 hours)
6. ⚠️ **Status logic** - Threshold calculations (1 hour)

**What's Complex:**
7. ❌ **Chart card** - Requires charting library (4-6 hours)
8. ❌ **Activity card** - Time formatting, icons (2-3 hours)

### Recommended Phased Approach

**Phase 1: Foundation (1 day)**
- Helper methods (in `sceh_card`)
- CSS system
- Simple + Metric cards

**Phase 2: Core Cards (1 day)**
- List card
- Detail card
- Status logic
- Apply to rules pages

**Phase 3: Advanced (Optional, 1 day)**
- Chart card (if needed)
- Activity card (if needed)
- Additional templates

---

## Usage Examples

### Example 1: Rules Management Page

```php
// attendance_rules.php

$cards = [];

foreach ($rules as $rule) {
    // Determine status based on affected learners
    $affected = get_affected_learners($rule->id);
    $status = count($affected) == 0 ? 'success' : 
              (count($affected) < 5 ? 'warning' : 'danger');
    
    $cards[] = sceh_card::detail([
        'size' => 'large',
        'status' => $status,
        'title' => 'Attendance Rule: ' . $rule->name,
        'badges' => [
            ['text' => $rule->enabled ? 'Active' : 'Disabled', 
             'type' => $rule->enabled ? 'success' : 'secondary']
        ],
        'stats' => [
            ['value' => $rule->threshold . '%', 'label' => 'Threshold'],
            ['value' => count($affected), 'label' => 'Blocking'],
            ['value' => $rule->applied_count, 'label' => 'Applied to']
        ],
        'sections' => [
            [
                'title' => 'Applied to',
                'content' => $rule->competency_name
            ]
        ],
        'actions' => [
            ['text' => 'Edit', 'url' => new moodle_url('/local/sceh_rules/edit_attendance_rule.php', ['id' => $rule->id])],
            ['text' => 'View Affected', 'url' => '...'],
            ['text' => 'Delete', 'url' => '...', 'style' => 'danger']
        ]
    ]);
}

// Render grid
echo html_writer::start_div('sceh-card-grid');
foreach ($cards as $card) {
    echo $card;
}
echo html_writer::end_div();
```


### Example 2: Trainer Dashboard

```php
// Trainer dashboard with mixed card sizes

$cards = [];

// Small metric cards
$cards[] = sceh_card::metric([
    'size' => 'small',
    'status' => 'success',
    'title' => 'Total Learners',
    'value' => '24',
    'icon' => 'users'
]);

$cards[] = sceh_card::metric([
    'size' => 'small',
    'status' => 'warning',
    'title' => 'Attendance',
    'value' => '78%',
    'threshold' => 80,
    'trend' => '+5%'
]);

$cards[] = sceh_card::metric([
    'size' => 'small',
    'status' => 'danger',
    'title' => 'At Risk',
    'value' => '2',
    'icon' => 'exclamation-triangle'
]);

// Full-width at-risk learners card
$at_risk_items = [];
foreach ($at_risk_learners as $learner) {
    $at_risk_items[] = [
        'icon' => 'user',
        'text' => fullname($learner),
        'subtext' => $learner->attendance . '% attendance',
        'actions' => [
            ['text' => 'View', 'url' => '...'],
            ['text' => 'Message', 'url' => '...']
        ]
    ];
}

$cards[] = sceh_card::list([
    'size' => 'full',
    'status' => 'danger',
    'title' => 'At-Risk Learners',
    'count' => count($at_risk_learners),
    'items' => $at_risk_items,
    'footer_actions' => [
        ['text' => 'View All', 'url' => '...'],
        ['text' => 'Send Reminders', 'url' => '...']
    ]
]);

// Medium cohort cards
foreach ($cohorts as $cohort) {
    $status = $cohort->attendance >= 90 ? 'success' :
              ($cohort->attendance >= 75 ? 'info' : 'danger');
    
    $cards[] = sceh_card::detail([
        'size' => 'medium',
        'status' => $status,
        'title' => $cohort->name,
        'stats' => [
            ['value' => $cohort->learner_count, 'label' => 'Learners', 'icon' => 'users'],
            ['value' => $cohort->attendance . '%', 'label' => 'Attendance', 'icon' => 'calendar-check'],
            ['value' => $cohort->completion . '%', 'label' => 'Complete', 'icon' => 'check-circle']
        ],
        'actions' => [
            ['text' => 'View Details', 'url' => new moodle_url('/course/view.php', ['id' => $cohort->courseid])]
        ]
    ]);
}

echo html_writer::start_div('sceh-card-grid');
foreach ($cards as $card) {
    echo $card;
}
echo html_writer::end_div();
```

### Example 3: System Admin Dashboard

```php
// Mixed layout with different card types

$cards = [];

// Top row: Quick metrics (small cards)
$cards[] = sceh_card::simple([
    'size' => 'small',
    'status' => 'info',
    'icon' => 'users',
    'title' => 'Total Users',
    'value' => '245',
    'label' => '+12 this month',
    'url' => '/admin/user.php'
]);

$cards[] = sceh_card::simple([
    'size' => 'small',
    'status' => 'success',
    'icon' => 'graduation-cap',
    'title' => 'Programs',
    'value' => '12',
    'label' => '3 draft',
    'url' => '/course/index.php'
]);

$cards[] = sceh_card::simple([
    'size' => 'small',
    'status' => 'info',
    'icon' => 'users',
    'title' => 'Cohorts',
    'value' => '18',
    'label' => '450 learners',
    'url' => '/cohort/index.php'
]);

// Full-width system health card
$cards[] = sceh_card::detail([
    'size' => 'full',
    'status' => 'info',
    'title' => 'System Health - Last 30 Days',
    'sections' => [
        [
            'title' => 'Attendance',
            'content' => '82% (last 30 days)'
        ],
        [
            'title' => 'Completion',
            'content' => '67% (last 30 days)'
        ],
        [
            'title' => 'Engagement',
            'content' => '89% (last 30 days)'
        ]
    ],
    'actions' => [
        ['text' => 'View Detailed Reports', 'url' => '...']
    ]
]);

// Program health cards (medium)
foreach ($programs as $program) {
    $status = $program->completion >= 80 ? 'success' :
              ($program->completion >= 60 ? 'warning' : 'danger');
    
    $cards[] = sceh_card::detail([
        'size' => 'medium',
        'status' => $status,
        'title' => $program->name,
        'stats' => [
            ['value' => $program->course_count, 'label' => 'Courses'],
            ['value' => $program->learner_count, 'label' => 'Learners'],
            ['value' => $program->completion . '%', 'label' => 'Completion'],
            ['value' => number_format($program->satisfaction, 1) . '/5', 'label' => 'Satisfaction']
        ],
        'actions' => [
            ['text' => 'View Details', 'url' => '...']
        ]
    ]);
}

echo html_writer::start_div('sceh-card-grid');
foreach ($cards as $card) {
    echo $card;
}
echo html_writer::end_div();
```


---

## Implementation Roadmap (REVISED - PRAGMATIC)

### Phase 1: Minimum Viable Card System (2 days)

**SCOPE LOCK - Implement Only:**
- **Helpers**: icon, badge, button, stat
- **Molecules**: card_header, status_indicator
- **Templates**: simple, metric, list, detail
- **CSS**: Grid + status colors (Bootstrap-based)

**DEFER COMPLETELY:**
- ❌ Chart card (requires charting library)
- ❌ Activity card (time formatting complexity)
- ❌ Full atom/molecule class split (add only if duplication appears)

**Day 1: Single Renderer Class + CSS**
- Create `local_sceh_rules/classes/output/sceh_card.php` (ONE class)
- Add helper methods: `render_icon()`, `render_badge()`, `render_button()`, `render_stat()`
- Add card methods: `simple()`, `metric()`, `list()`, `detail()`
- Create `local_sceh_rules/styles/sceh_card_system.css`
- Use existing Moodle patterns: `html_writer`, Bootstrap classes
- Test with sample data

**Day 2: Convert Rules Pages**
- Convert `attendance_rules.php` to use cards
- Convert `roster_rules.php` to use cards
- Preserve functionality 1:1 (no feature changes)
- Test responsive behavior (desktop → tablet → mobile)
- Test keyboard navigation (tab order, focus indicators)
- Test with mock users

**Validation Criteria for "Done":**
1. ✅ Both rules pages use card layout
2. ✅ All existing functionality preserved
3. ✅ Responsive on mobile/tablet/desktop
4. ✅ Keyboard accessible (tab navigation works)
5. ✅ Status colors work (green/yellow/red based on data)
6. ✅ No new dependencies added
7. ✅ No console errors or warnings

### Future: Progressive Enhancement (As Needed)

**Only add when duplication appears:**
- Split into atom/molecule classes if helper methods get duplicated
- Add chart card if visualization becomes requirement
- Add activity card if timeline/feed becomes requirement

**Extend to other pages:**
- Trainer dashboard cohort cards
- System admin program health cards
- Cohort management card view
- Category/course drill-down cards

---

## File Structure (REVISED - SINGLE OWNERSHIP)

```
local_sceh_rules/
├── classes/
│   └── output/
│       └── sceh_card.php          # ONE class with all card logic
├── styles/
│   └── sceh_card_system.css       # Card styling + grid
└── tests/
    └── card_system_test.php       # Component tests

block_sceh_dashboard/
└── styles.css                      # Existing dashboard styles (keep, don't touch)
```

**Ownership Decision:**
- ✅ All card renderers in `local_sceh_rules/classes/output/sceh_card.php`
- ✅ Single source of truth for card system
- ✅ Dashboard block uses cards by calling `sceh_card::simple()` etc.
- ❌ Don't split ownership across plugins
- ❌ Don't create separate atom/molecule classes until duplication appears

**Why local_sceh_rules?**
- Rules pages are first use case
- Central location for shared components
- Dashboard block can depend on it
- Other plugins can use it too

---

## Design Tokens (CSS Variables)

```css
:root {
    /* Status colors (Bootstrap-based) */
    --sceh-success: #28a745;
    --sceh-info: #17a2b8;
    --sceh-warning: #ffc107;
    --sceh-danger: #dc3545;
    --sceh-neutral: #6c757d;
    
    /* Background tints */
    --sceh-success-bg: #f0f9f4;
    --sceh-info-bg: #f0f8fb;
    --sceh-warning-bg: #fffbf0;
    --sceh-danger-bg: #fdf4f5;
    --sceh-neutral-bg: #f8f9fa;
    
    /* Card spacing */
    --sceh-card-padding: 1.5rem;
    --sceh-card-gap: 1rem;
    --sceh-card-border-radius: 0.5rem;
    --sceh-card-border-width: 4px;
    
    /* Typography */
    --sceh-card-title-size: 1.25rem;
    --sceh-card-value-size: 2rem;
    --sceh-card-label-size: 0.875rem;
}
```

---

## Accessibility Requirements

### Keyboard Navigation
- All cards with actions must be keyboard accessible
- Tab order follows visual order
- Focus indicators visible

### Screen Readers
- Card titles use proper heading levels
- Status indicators include sr-only text
- Icons have aria-hidden="true" with text alternatives

### Color Contrast
- All text meets WCAG AA standards (4.5:1 minimum)
- Status not conveyed by color alone (icons + text)
- Focus indicators have 3:1 contrast

### Example Accessible Card
```html
<div class="sceh-card sceh-card-medium border-danger" role="region" aria-labelledby="card-title-123">
    <h3 id="card-title-123">
        <i class="fa fa-exclamation-triangle text-danger" aria-hidden="true"></i>
        <span class="sr-only">Warning:</span>
        At-Risk Learners
    </h3>
    <!-- Card content -->
</div>
```

---

## Testing Strategy

### Unit Tests
- Test each atom renders correct HTML
- Test molecules combine atoms correctly
- Test card templates assemble correctly

### Integration Tests
- Test cards render in grid layout
- Test responsive behavior
- Test status color logic

### Visual Regression Tests
- Screenshot comparison for each card type
- Test across different screen sizes
- Test with different content lengths

### Accessibility Tests
- Automated WCAG checks
- Keyboard navigation testing
- Screen reader testing

---

## Summary: Simplest Solution

**Single renderer class is the simplest Phase 1 approach because:**

1. ✅ **Small, focused components** - Each does one thing well
2. ✅ **Easy to test** - Test helper output, then card templates
3. ✅ **Easy to extend** - Add helper methods as needed
4. ✅ **Consistent output** - Same helper methods = same look everywhere
5. ✅ **Low maintenance** - Fix once, works everywhere
6. ✅ **Easy to learn** - Developers understand composition
7. ✅ **Flexible** - Reuse helpers across templates

**Complexity breakdown:**
- Helpers: SIMPLE (just HTML wrappers)
- Simple/Metric cards: SIMPLE (straightforward assembly)
- List/Detail cards: MODERATE (iteration + sections)
- Chart/Activity cards: COMPLEX (optional, add later)

**Recommended start:**
- Phase 1 (2 days): Single `sceh_card` class + Simple/Metric/List/Detail cards
- Apply to rules pages immediately
- Extend to other pages as needed

**This gives you:**
- ✅ 80% of use cases covered
- ✅ Foundation for future cards
- ✅ Consistent visual language
- ✅ Easy to maintain and extend
