# SCEH Card System Specification

## Overview

This document defines the atomic card component system for the SCEH Learning Platform. The system uses composable components (atoms → molecules → organisms) to build flexible, consistent UI cards.

## Design Philosophy

**Atomic Design Approach:**
- **Atoms**: Basic building blocks (icon, text, badge, button)
- **Molecules**: Simple combinations (stat display, action bar, list item)
- **Organisms**: Complete cards assembled from molecules
- **Templates**: Page layouts using card grids

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

## Atomic Components (Level 1: Atoms)

### 1. Icon
```php
sceh_atom::icon($name, $color = null, $size = 'default')
// Returns: <i class="fa fa-{$name} {$color} {$size}"></i>
```

### 2. Badge
```php
sceh_atom::badge($text, $type = 'info')
// Types: success, info, warning, danger, neutral
// Returns: <span class="badge badge-{$type}">{$text}</span>
```

### 3. Button
```php
sceh_atom::button($text, $url, $style = 'primary')
// Styles: primary, secondary, success, danger, link
// Returns: <a href="{$url}" class="btn btn-{$style}">{$text}</a>
```

### 4. Progress Bar
```php
sceh_atom::progress($percentage, $color = 'info')
// Returns: <div class="progress">...</div>
```

### 5. Stat Value
```php
sceh_atom::stat($value, $label, $icon = null)
// Returns: <div class="sceh-stat">...</div>
```

---

## Molecular Components (Level 2: Molecules)

### 1. Card Header
```php
sceh_molecule::card_header($config)
// Config: title, subtitle, icon, badges, actions
// Returns: Complete card header HTML
```

### 2. Stat Grid
```php
sceh_molecule::stat_grid($stats)
// Stats: array of [value, label, icon, color]
// Returns: Grid of stat displays
```

### 3. List Item
```php
sceh_molecule::list_item($config)
// Config: icon, text, subtext, actions, status
// Returns: Single list item HTML
```

### 4. Action Bar
```php
sceh_molecule::action_bar($buttons)
// Buttons: array of button configs
// Returns: Row of action buttons
```

### 5. Status Indicator
```php
sceh_molecule::status_indicator($status, $text, $details = null)
// Status: success, warning, danger, info
// Returns: Colored status display with icon
```


---

## Card Templates (Level 3: Organisms)

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
            'content' => sceh_atom::progress(80, 'success')
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

### Template 5: Chart Card (Visualizations)
**Use for:** Trends, comparisons, analytics

```php
sceh_card::chart([
    'size' => 'large',
    'title' => 'Attendance Trend',
    'chart_type' => 'line',
    'data' => [...],
    'footer' => 'Last 6 months'
]);
```

### Template 6: Activity Card (Timeline/Feed)
**Use for:** Recent activity, notifications, logs

```php
sceh_card::activity([
    'size' => 'medium',
    'title' => 'Recent Activity',
    'items' => [
        ['time' => '2 hours ago', 'icon' => 'check', 'text' => 'Dr. Patel submitted Case #45', 'status' => 'success'],
        ['time' => '5 hours ago', 'icon' => 'warning', 'text' => 'Attendance below threshold', 'status' => 'warning']
    ]
]);
```

---

## Simplest Implementation: Atomic Library

### Core Philosophy
**Build complex from simple** - Every card is assembled from atoms and molecules.

### Implementation Layers

**Layer 1: Atoms (5 components)**
```php
// local_sceh_rules/classes/output/sceh_atom.php
class sceh_atom {
    public static function icon($name, $color = null, $size = 'default') { }
    public static function badge($text, $type = 'info') { }
    public static function button($text, $url, $style = 'primary') { }
    public static function progress($percentage, $color = 'info') { }
    public static function stat($value, $label, $icon = null) { }
}
```
**Complexity: LOW** - Simple HTML generation, no logic

**Layer 2: Molecules (5 components)**
```php
// local_sceh_rules/classes/output/sceh_molecule.php
class sceh_molecule {
    public static function card_header($config) { }
    public static function stat_grid($stats) { }
    public static function list_item($config) { }
    public static function action_bar($buttons) { }
    public static function status_indicator($status, $text, $details = null) { }
}
```
**Complexity: LOW-MEDIUM** - Combines atoms, minimal logic


**Layer 3: Card Templates (6 templates)**
```php
// local_sceh_rules/classes/output/sceh_card.php
class sceh_card {
    public static function simple($config) { }      // Navigation/metrics
    public static function metric($config) { }      // KPIs with thresholds
    public static function list($config) { }        // Items with actions
    public static function detail($config) { }      // Rich content
    public static function chart($config) { }       // Visualizations
    public static function activity($config) { }    // Timeline/feed
    
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
1. ✅ **Atoms** - Just HTML wrappers (1-2 hours total)
2. ✅ **Molecules** - Combine atoms (2-3 hours total)
3. ✅ **Simple/Metric cards** - Straightforward assembly (1 hour)
4. ✅ **CSS** - Grid + status colors (2 hours)

**What's Moderate:**
5. ⚠️ **List card** - Iteration logic (2 hours)
6. ⚠️ **Detail card** - Multiple sections (2 hours)
7. ⚠️ **Status logic** - Threshold calculations (1 hour)

**What's Complex:**
8. ❌ **Chart card** - Requires charting library (4-6 hours)
9. ❌ **Activity card** - Time formatting, icons (2-3 hours)

### Recommended Phased Approach

**Phase 1: Foundation (1 day)**
- Atoms (5 components)
- Molecules (5 components)
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
            'content' => sceh_atom::progress(82, 'success') . ' 82%'
        ],
        [
            'title' => 'Completion',
            'content' => sceh_atom::progress(67, 'warning') . ' 67%'
        ],
        [
            'title' => 'Engagement',
            'content' => sceh_atom::progress(89, 'success') . ' 89%'
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

## Implementation Roadmap

### Week 5: Card System Foundation (2 days)

**Day 1: Atomic Components + CSS**
- Create `sceh_atom.php` (5 atoms)
- Create `sceh_molecule.php` (5 molecules)
- Create `sceh_card_system.css` (grid + status colors)
- Create `sceh_card.php` (simple + metric templates)
- Test with sample data

**Day 2: Core Templates + Rules Pages**
- Add list template to `sceh_card.php`
- Add detail template to `sceh_card.php`
- Convert `attendance_rules.php` to use cards
- Convert `roster_rules.php` to use cards
- Test with mock users

### Week 6: Dashboard Enhancement (Optional, 1-2 days)

**Extend to dashboards:**
- Trainer dashboard with cohort cards
- System admin dashboard with program health cards
- Add activity card template (if needed)
- Add chart card template (if needed)

### Future: Progressive Enhancement

**As needed:**
- Cohort management card view
- Category/course drill-down cards
- Badge gallery cards
- Competency browser cards
- Learner progress cards

---

## File Structure

```
local_sceh_rules/
├── classes/
│   └── output/
│       ├── sceh_atom.php          # 5 atomic components
│       ├── sceh_molecule.php      # 5 molecular components
│       └── sceh_card.php          # 6 card templates
├── styles/
│   └── sceh_card_system.css       # Card styling + grid
└── tests/
    └── card_system_test.php       # Component tests

block_sceh_dashboard/
└── styles.css                      # Existing dashboard styles (keep)
```

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

**Atomic library is the simplest approach because:**

1. ✅ **Small, focused components** - Each does one thing well
2. ✅ **Easy to test** - Test atoms, molecules, then cards
3. ✅ **Easy to extend** - Add new atoms/molecules as needed
4. ✅ **Consistent output** - Same atoms = same look everywhere
5. ✅ **Low maintenance** - Fix once, works everywhere
6. ✅ **Easy to learn** - Developers understand composition
7. ✅ **Flexible** - Combine atoms differently for new cards

**Complexity breakdown:**
- Atoms: SIMPLE (just HTML wrappers)
- Molecules: SIMPLE (combine atoms)
- Simple/Metric cards: SIMPLE (straightforward assembly)
- List/Detail cards: MODERATE (iteration + sections)
- Chart/Activity cards: COMPLEX (optional, add later)

**Recommended start:**
- Phase 1 (2 days): Atoms + Molecules + Simple/Metric/List/Detail cards
- Apply to rules pages immediately
- Extend to other pages as needed

**This gives you:**
- ✅ 80% of use cases covered
- ✅ Foundation for future cards
- ✅ Consistent visual language
- ✅ Easy to maintain and extend

