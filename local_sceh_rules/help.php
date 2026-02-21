<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * SCEH Help & FAQ page — role-aware, accordion-style.
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/sceh_rules/help.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('help_title', 'local_sceh_rules'));
$PAGE->set_heading(get_string('help_title', 'local_sceh_rules'));
$PAGE->requires->css(new moodle_url('/local/sceh_rules/styles/sceh_card_system.css'));

// Detect user role for role-specific content.
$userid = (int) $USER->id;
$issysadmin = has_capability('local/sceh_rules:systemadmin', $context);
$istrainer = has_capability('local/sceh_rules:trainer', $context);
$ispo = false;
// Check program owner by cohort membership.
$pocohorts = $DB->get_records('cohort', ['idnumber' => 'program-owners']);
foreach ($pocohorts as $cohort) {
    if ($DB->record_exists('cohort_members', ['cohortid' => $cohort->id, 'userid' => $userid])) {
        $ispo = true;
        break;
    }
}
$islearner = !$issysadmin && !$istrainer && !$ispo;

// Determine role label.
if ($issysadmin) {
    $rolelabel = 'System Admin';
} else if ($ispo) {
    $rolelabel = 'Program Owner';
} else if ($istrainer) {
    $rolelabel = 'Trainer';
} else {
    $rolelabel = 'Learner';
}

// ─── FAQ Sections ────────────────────────────────────────────────

$sections = [];

// 1. Getting Started (all roles).
$sections[] = [
    'title' => 'Getting Started',
    'icon' => 'fa-rocket',
    'items' => [
        [
            'q' => 'Where is my dashboard?',
            'a' => 'Click the <strong>SCEH</strong> logo in the top-left corner of any page. That takes you to your dashboard.',
        ],
        [
            'q' => 'What do the cards on my dashboard do?',
            'a' => 'Each card is a shortcut to a key area of the system. Click any card to open that feature. '
                 . 'Cards with a <strong>number badge</strong> show a count (e.g. deadlines, badges earned).',
        ],
        [
            'q' => 'How do I get back after clicking a card?',
            'a' => 'Click the <strong>SCEH</strong> logo in the top-left. It always takes you home.',
        ],
    ],
];

// 2. Learner-specific.
if ($islearner) {
    $sections[] = [
        'title' => 'Your Course & Progress',
        'icon' => 'fa-graduation-cap',
        'items' => [
            [
                'q' => 'How do I see my course content?',
                'a' => 'Click the <strong>Your Stream</strong> card on the dashboard to open your assigned course.',
            ],
            [
                'q' => 'How do I track my progress?',
                'a' => 'Click <strong>My Progress</strong> on the dashboard. It shows all your course activities and whether '
                     . 'each one is complete or incomplete. Click any activity to open it directly.',
            ],
            [
                'q' => 'What does "Incomplete" mean?',
                'a' => 'It means you haven\'t finished that activity yet. For <strong>quizzes</strong>, '
                     . 'complete the quiz and receive a grade. For <strong>resources</strong> (PDFs, content), just open and view them.',
            ],
            [
                'q' => 'How do I see my competencies?',
                'a' => 'Click <strong>My Competencies</strong> on the dashboard. It shows the competency framework for your course.',
            ],
            [
                'q' => 'What is stream selection?',
                'a' => 'Some courses have specialization streams (e.g. Front Desk, Doctor Assistance). '
                     . 'You select your stream inside the course using a "Choice" activity. After that, your dashboard shows your selected stream.',
            ],
        ],
    ];
}

// 3. Trainer-specific.
if ($istrainer) {
    $sections[] = [
        'title' => 'Teaching & Course Access',
        'icon' => 'fa-chalkboard-user',
        'items' => [
            [
                'q' => 'How do I access my courses?',
                'a' => 'Click <strong>My Courses</strong> on the dashboard. If you have one course, it opens directly. '
                     . 'If you have multiple, click to expand and choose one.',
            ],
            [
                'q' => 'How do I take attendance?',
                'a' => 'Click <strong>Attendance Reports</strong> on the dashboard to manage and review attendance.',
            ],
            [
                'q' => 'I can\'t see a course I was assigned to.',
                'a' => 'Check with your System Admin that you\'re enrolled in the course with the Teacher role. '
                     . 'Hidden or draft courses are visible to trainers.',
            ],
        ],
    ];
}

// 4. Program Owner specific.
if ($ispo) {
    $sections[] = [
        'title' => 'Program Management',
        'icon' => 'fa-sitemap',
        'items' => [
            [
                'q' => 'How do I check my stream setup?',
                'a' => 'Click <strong>Stream Setup Check</strong> on the dashboard. It validates that your stream sections, '
                     . 'naming, and choice activities are correctly configured.',
            ],
            [
                'q' => 'How do I import course content?',
                'a' => 'Click <strong>Import Content</strong> on the dashboard. Upload a ZIP file with your course materials '
                     . '(PDFs, quiz CSVs, links), preview, and confirm the import.',
            ],
            [
                'q' => 'How do I map competencies to activities?',
                'a' => 'Open the activity settings in your course, go to the Competencies tab, and select the competencies '
                     . 'that the activity maps to.',
            ],
        ],
    ];
}

// 5. System Admin specific.
if ($issysadmin) {
    $sections[] = [
        'title' => 'System Administration',
        'icon' => 'fa-gears',
        'items' => [
            [
                'q' => 'How do I manage cohorts?',
                'a' => 'Click <strong>Manage Cohorts</strong> on the dashboard. Create cohorts, add users, '
                     . 'and connect them to courses via cohort enrollment.',
            ],
            [
                'q' => 'How do I enroll a cohort into a course?',
                'a' => 'Go to the course → Participants → Enrollment methods → Add "Cohort sync" and select the cohort and role.',
            ],
            [
                'q' => 'How do I set up reports?',
                'a' => 'Click <strong>Custom Reports</strong> on the dashboard. Use Moodle\'s report builder to create, '
                     . 'schedule, and share reports.',
            ],
            [
                'q' => 'How do I check system health?',
                'a' => 'The status row on your dashboard shows <strong>Cron Tasks</strong> (failed task count), '
                     . '<strong>Active Users</strong>, and <strong>Overdue Events</strong> at a glance.',
            ],
        ],
    ];
}

// 6. Common Questions (all roles).
$sections[] = [
    'title' => 'Common Questions',
    'icon' => 'fa-circle-question',
    'items' => [
        [
            'q' => 'Something is not loading or showing an error.',
            'a' => 'Try refreshing the page. If the issue persists, check that you have the correct role assigned. '
                 . 'Contact your System Admin if you believe your access is incorrect.',
        ],
        [
            'q' => 'I see a "permission denied" or "access denied" error.',
            'a' => 'This means your role doesn\'t have access to that page. Contact your System Admin to verify your role and capabilities.',
        ],
        [
            'q' => 'Who do I contact for help?',
            'a' => 'Reach out to your Program Owner for content questions, or your System Admin for access and technical issues.',
        ],
    ],
];

// ─── Render ──────────────────────────────────────────────────────

echo $OUTPUT->header();

// Role badge.
echo html_writer::div(
    html_writer::tag('i', '', ['class' => 'fa fa-user-circle fa-lg me-2']) .
    'You are logged in as: <strong>' . $rolelabel . '</strong>',
    'alert alert-info d-flex align-items-center mb-4',
    ['style' => 'font-size: 0.95rem;']
);

// Render accordion.
echo html_writer::start_div('sceh-help-faq');
$sectionindex = 0;
foreach ($sections as $section) {
    $sectionid = 'faq-section-' . $sectionindex;
    echo html_writer::start_div('card mb-3', ['style' => 'border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;']);

    // Section header.
    echo html_writer::tag('div',
        html_writer::tag('button',
            html_writer::tag('i', '', ['class' => 'fa ' . $section['icon'] . ' me-2']) .
            html_writer::tag('strong', $section['title']),
            [
                'class' => 'btn btn-link text-start w-100 py-3 px-4',
                'type' => 'button',
                'data-bs-toggle' => 'collapse',
                'data-bs-target' => '#' . $sectionid,
                'aria-expanded' => $sectionindex === 0 ? 'true' : 'false',
                'style' => 'font-size: 1.05rem; text-decoration: none; color: #1a365d;',
            ]
        ),
        ['class' => 'card-header', 'style' => 'background: #f8f9fa; border-bottom: 1px solid #e0e0e0;']
    );

    // Section body.
    $collapseclass = $sectionindex === 0 ? 'collapse show' : 'collapse';
    echo html_writer::start_div($collapseclass, ['id' => $sectionid]);
    echo html_writer::start_div('card-body px-4 py-3');

    foreach ($section['items'] as $item) {
        echo html_writer::start_div('mb-3');
        echo html_writer::tag('div',
            html_writer::tag('i', '', ['class' => 'fa fa-circle-question me-1', 'style' => 'color: #2563eb;']) . ' ' .
            html_writer::tag('strong', s($item['q'])),
            ['style' => 'font-size: 0.95rem; margin-bottom: 4px;']
        );
        echo html_writer::tag('div',
            $item['a'],
            ['class' => 'text-muted', 'style' => 'font-size: 0.9rem; padding-left: 1.5rem;']
        );
        echo html_writer::end_div();
    }

    echo html_writer::end_div(); // card-body
    echo html_writer::end_div(); // collapse
    echo html_writer::end_div(); // card
    $sectionindex++;
}
echo html_writer::end_div(); // sceh-help-faq

echo $OUTPUT->footer();
