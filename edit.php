<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * TODO describe file edit
 *
 * @package    block_lessonmenu
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot . '/mod/lesson/locallib.php');

require_login(null, true);

$id = required_param('id', PARAM_INT);
$structure = optional_param('structure', '', PARAM_RAW);

// Validate the block instance.
$context = context_block::instance($id);
require_capability('block/lessonmenu:addinstance', $context);

$params = ['id' => $id];
$baseurl = new moodle_url('/blocks/lessonmenu/edit.php', $params);

// Get parent context and see if is a course or a mod.
$parentcontext = $context->get_parent_context();
if ($parentcontext->contextlevel !== CONTEXT_MODULE) {
    throw new moodle_exception('invalidcontext');
}

$coursemodule = $DB->get_record('course_modules', ['id' => $parentcontext->instanceid]);
$module = $DB->get_record('modules', ['id' => $coursemodule->module]);

if ($module->name !== 'lesson') {
    throw new moodle_exception('invalidcontext');
}

$cm = get_coursemodule_from_id($module->name, $coursemodule->id);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$lesson = new lesson($DB->get_record('lesson', ['id' => $cm->instance], '*', MUST_EXIST), $cm, $course);

if (!empty($structure)) {
    $newconfigdata = base64_encode(serialize($configdata));
    $DB->set_field('block_instances', 'configdata', $newconfigdata, ['id' => $id]);
}

$PAGE->set_cm($cm);
$cmurl = new moodle_url('/mod/lesson/view.php', ['id' => $coursemodule->id]);
$PAGE->navbar->add($cm->name, $cmurl);
$PAGE->navbar->add(get_string('editstructure', 'block_lessonmenu'));

$PAGE->set_context($parentcontext);
$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('report'); // Minimal layout.
$PAGE->set_heading($cm->name);
$PAGE->set_pagetype('block_lessonmenu-edit');
$PAGE->set_title(get_string('editstructure', 'block_lessonmenu'));

$PAGE->requires->js_call_amd('block_lessonmenu/structureeditor', 'init');

// Todo: Calcular "completed" para cada sección.

$renderable = new \block_lessonmenu\output\editstructure($id, $lesson);
$renderer = $PAGE->get_renderer('block_lessonmenu');

echo $OUTPUT->header();

echo $renderer->render($renderable);

echo $OUTPUT->footer();
