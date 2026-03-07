<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');

global $DB, $USER, $PAGE;
$PAGE->set_url(new moodle_url('/my/index.php'));
$PAGE->set_context(context_system::instance());

$USER = $DB->get_record('user', ['username' => 'mock.learner']);
require_once(__DIR__ . '/../blocks/sceh_dashboard/block_sceh_dashboard.php');
$block = new block_sceh_dashboard();
$block->init();
$content = $block->get_content();
echo $content->text;
