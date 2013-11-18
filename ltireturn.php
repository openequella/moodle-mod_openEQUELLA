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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
require_once('../../config.php');
require_once($CFG->dirroot.'/mod/equella/lib.php');
require_once($CFG->dirroot.'/mod/equella/locallib.php');

$courseid = required_param('courseid', PARAM_INT);
$instanceid = required_param('instanceid', PARAM_INT);
$ltierrormsg = optional_param('lti_errormsg', '', PARAM_RAW);
$ltimsg = optional_param('lti_msg', '', PARAM_RAW);

$course = $DB->get_record('course', array('id' => $courseid));

require_login($course);

if (!empty($ltierrormsg) || !empty($ltimsg)) {
    $message = '';
    if (!empty($ltierrormsg)) {
        $message = $ltierrormsg;
    } else {
        $message = $ltimsg;
    }
    $url = new moodle_url('/mod/equella/ltireturn.php', array('courseid' => $courseid, 'instanceid'=> $instanceid));
    $PAGE->set_url($url);

    $pagetitle = strip_tags($course->shortname);
    $PAGE->set_title($pagetitle);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_pagelayout('embedded');

    echo $OUTPUT->header();
    echo htmlspecialchars($message);
    echo $OUTPUT->footer();
} else {
    $courseurl = new moodle_url('/course/view.php', array('id' => $courseid));
    $url = $courseurl->out();
    echo '<html><head>';
    echo html_writer::script("if(window != top){ top.location.href = '{$url}' } else {close();};");
    echo '</head><body></body></html>';
}
