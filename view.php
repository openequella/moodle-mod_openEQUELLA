<?php
// This file is part of the EQUELLA module - http://git.io/vUuof
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
require_once ('../../config.php');
require_once ('lib.php');
require_once ('locallib.php');
require_once ($CFG->libdir . '/resourcelib.php');
require_once ($CFG->libdir . '/completionlib.php');
require_login();

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$a = optional_param('a', 0, PARAM_INT); // EQUELLA instance ID

if ($id) { // Two ways to specify the module
    $cm = get_coursemodule_from_id('equella', $id, 0, false, MUST_EXIST);
    $equella = $DB->get_record('equella', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $equella = $DB->get_record('equella', array('id' => $a), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('equella', $equella->id, $equella->course, false, MUST_EXIST);
}

$equella->cmid = $cm->id;

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

$context = context_module::instance($cm->id);
$PAGE->set_context($context);
require_capability('mod/equella:view', $context);

if (!$cm->visible and !has_capability('moodle/course:viewhiddenactivities', $context)) {
    notice(get_string("activityiscurrentlyhidden"));
}

if (class_exists('mod_equella\\event\\course_module_viewed')) {
    $eventparams = array(
        'context' => $context,
        'objectid' => $equella->id
    );
    $event = \mod_equella\event\course_module_viewed::create($eventparams);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('equella', $equella);
    $event->trigger();
} else {
    add_to_log($course->id, "equella", "view equella resource", "view.php?id=$cm->id", $equella->id, $cm->id);
}

// Update 'viewed' state if required by completion system
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/equella/view.php', array('id' => $cm->id));

$url = equella_appendtoken($equella->url);
if (optional_param('inpopup', 0, PARAM_BOOL)) {
    redirect($url);
}

$PAGE->set_title($course->shortname . ': ' . $equella->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_cm($cm);
echo $OUTPUT->header();

if (trim(strip_tags($equella->intro))) {
    echo $OUTPUT->box_start('mod_introbox', 'equellaintro');
    echo format_module_intro('equella', $equella, $cm->id);
    echo $OUTPUT->box_end();
}

echo equella_embed_general($equella);

echo $OUTPUT->footer($course);
