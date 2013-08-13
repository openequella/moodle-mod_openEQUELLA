<?php
require_once('../../config.php');
require_once('lib.php');
require_once('locallib.php');

$action = optional_param('action', 'view', PARAM_ACTION);

if ($action == 'view') {
    $cmid = required_param('cmid', PARAM_INT);
    $cm = get_coursemodule_from_id('equella', $cmid, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $equella = $DB->get_record('equella', array('id' => $cm->instance), '*', MUST_EXIST);
    $context = context_module::instance($cm->id);

    $PAGE->set_context($context);
    $PAGE->set_pagelayout('embedded');

    require_course_login($course, true, $cm);
    require_capability('mod/equella:view', $context);

    $equella->cmid = $cmid;
    $params = equella_lti_params($equella, $course);
    echo '<html><body>';
    echo equella_lti_launch_form($equella->url, $params);
    echo '</body></html>';
} elseif ($action == 'select') {
    $args = new stdClass;
    $args->course = required_param('course', PARAM_INT);
    $args->section = required_param('section', PARAM_INT);
    $course = $DB->get_record('course', array('id' => $args->course), '*', MUST_EXIST);
    $context = context_course::instance($args->course);

    $PAGE->set_context($context);

    require_course_login($course, false);
    require_capability('moodle/course:manageactivities', $context);

    $url = equella_build_integration_url($args, false);
    $query = $url->params();

    $equella = new stdClass;
    $equella->id = 0;
    $equella->course = $args->course;
    $equella->url = $CFG->equella_url;
    $params = equella_lti_params($equella, $course, $query);
    echo '<html><body>';
    echo equella_lti_launch_form($equella->url, $params);
    echo '</body></html>';
}
