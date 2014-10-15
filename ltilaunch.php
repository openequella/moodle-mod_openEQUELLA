<?php

// This file is part of the EQUELLA Moodle Integration - https://github.com/equella/moodle-module
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
require_once ($CFG->libdir . '/completionlib.php');

require_login();

$action = optional_param('action', 'view', PARAM_ACTION);

if ($action == 'view') {
    $cmid = required_param('cmid', PARAM_INT);
    $cm = get_coursemodule_from_id('equella', $cmid, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $equella = $DB->get_record('equella', array('id' => $cm->instance), '*', MUST_EXIST);
    $context = context_module::instance($cm->id);

    $PAGE->set_context($context);
    $PAGE->set_pagelayout('embedded');

    require_capability('mod/equella:view', $context);

    $equella->cmid = $cmid;
    $equella->course = $course->id;
    $params = equella_lti_params($equella, $course);
    
    add_to_log($course->id, "equella", "view equella resource", "view.php?id=$cm->id", $equella->id, $cm->id);

    // Update 'viewed' state if required by completion system
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);

    echo '<html><body>';
    echo equella_lti_launch_form($equella->url, $params);
    echo '</body></html>';
} elseif ($action == 'select') {
    $args = new stdClass();
    $args->course = required_param('course', PARAM_INT);
    $args->section = required_param('section', PARAM_INT);
    $course = $DB->get_record('course', array('id' => $args->course), '*', MUST_EXIST);
    $context = context_course::instance($args->course);

    $PAGE->set_context($context);

    require_capability('moodle/course:manageactivities', $context);

    $url = equella_build_integration_url($args, false);
    $extraparams = $url->params();
    if ($CFG->equella_action == EQUELLA_ACTION_STRUCTURED) {
        $contents = equella_get_course_contents($course->id, $args->section);
        $json = json_encode($contents);
        $extraparams['structure'] = $json;
    }

    $equella = new stdClass();
    $equella->id = 0;
    $equella->course = $args->course;
    $equella->url = $CFG->equella_url;
    $params = equella_lti_params($equella, $course, $extraparams);

    echo '<html><body>';
    echo equella_lti_launch_form($equella->url, $params);
    echo '</body></html>';
}
