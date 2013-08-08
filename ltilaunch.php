<?php
require_once('../../config.php');
require_once('lib.php');
require_once('locallib.php');

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

echo '<html><body>';
$params = equella_lti_params($equella, $course);
echo equella_launch_form($equella->url, $params);
echo '</body></html>';

