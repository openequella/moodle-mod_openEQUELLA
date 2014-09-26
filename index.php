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
require_once ("../../config.php");
require_once ("lib.php");

require_login();

$id = required_param('id', PARAM_INT); // course

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

$context = context_course::instance($id);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

if (class_exists('mod_equella\\event\\course_module_instance_list_viewed')) {
    $params = array(
        'context' => context_course::instance($course->id)
    );
    $event = \mod_equella\event\course_module_instance_list_viewed::create($params);
    $event->add_record_snapshot('course', $course);
    $event->trigger();
} else {
    add_to_log($course->id, "equella", "view all", "index.php?id=$course->id", "");
}

$strnoinst = get_string("noinstances", "equella");
$strequellas = get_string("modulenameplural", "equella");
$strequella = get_string("modulename", "equella");
$strweek = get_string("week");
$strtopic = get_string("topic");
$strname = get_string("name");

$PAGE->set_url('/mod/equella/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname . ': ' . $strequellas);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strequellas);
echo $OUTPUT->header();

if (!$equellas = get_all_instances_in_course("equella", $course)) {
    $url = new moodle_url('/course/view.php', array('id'=>$course->id));
    notice($strnoinst, $url);
}

$timenow = time();
$table = new html_table();
if ($course->format == "weeks") {
    $table->head = array($strweek,$strname);
    $table->align = array("center","left");
} else if ($course->format == "topics") {
    $table->head = array($strtopic,$strname);
    $table->align = array("center","left");
} else {
    $table->head = array($strname);
    $table->align = array("left");
}

if (function_exists('groups_get_all_groups')) {
    $currentgroup = groups_get_all_groups($course->id);
} else {
    $currentgroup = get_current_group($course->id);
}

if ($currentgroup and isteacheredit($course->id)) {
    $group = $DB->get_record("groups", array("id" => $currentgroup));
    $groupname = " ($group->name)";
} else {
    $groupname = "";
}

$currentsection = "";

foreach($equellas as $equella) {
    $url = new moodle_url('/mod/equella/view.php', array('id'=>$equella->coursemodule));
    $attr = array();
    if (!$equella->visible) {
        $attr = array('class'=>'dimmed');
    }
    $link = html_writer::link($url, $equella->name, $attr);

    $printsection = "";
    if ($equella->section !== $currentsection) {
        if ($equella->section) {
            $printsection = $equella->section;
        }
        if ($currentsection !== "") {
            $table->data[] = 'hr';
        }
        $currentsection = $equella->section;
    }

    if ($course->format == "weeks" or $course->format == "topics") {
        $table->data[] = array($printsection,$link);
    } else {
        $table->data[] = array($link);
    }
}

echo html_writer::empty_tag('br');

echo html_writer::table($table);
echo $OUTPUT->footer();
