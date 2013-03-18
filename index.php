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

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);   // course

if (! $course = $DB->get_record("course", array("id" => $id))) {
    print_error('invalidcourseid', '', '', $id);
}

require_course_login($course);
$PAGE->set_pagelayout('incourse');
add_to_log($course->id, "equella", "view all", "index.php?id=$course->id", "");

$strnoinst = get_string("noinstances", "equella");
$strequellas = get_string("modulenameplural", "equella");
$strequella = get_string("modulename", "equella");
$strweek = get_string("week");
$strtopic = get_string("topic");
$strname = get_string("name");

$PAGE->set_url('/mod/equella/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$strequellas);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strequellas);
echo $OUTPUT->header();

if (! $equellas = get_all_instances_in_course("equella", $course)) {
    notice($strnoinst, "../../course/view.php?id=$course->id");
    die;
}

$timenow = time();
$table = new html_table();
if ($course->format == "weeks") {
    $table->head  = array ($strweek, $strname);
    $table->align = array ("center", "left");
} else if ($course->format == "topics") {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ("center", "left");
} else {
    $table->head  = array ($strname);
    $table->align = array ("left");
}

$currentgroup = get_current_group($course->id);
if ($currentgroup and isteacheredit($course->id)) {
    $group = $DB->get_record("groups", array("id" => $currentgroup));
    $groupname = " ($group->name)";
} else {
    $groupname = "";
}

$currentsection = "";

foreach ($equellas as $equella) {
    if (!$equella->visible) {
        //Show dimmed if the mod is hidden
        $link = "<a class=\"dimmed\" href=\"view.php?id=$equella->coursemodule\">$equella->name</a>";
    } else {
        //Show normal if the mod is visible
        $link = "<a href=\"view.php?id=$equella->coursemodule\">$equella->name</a>";
    }

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
        $table->data[] = array ($printsection, $link);
    } else {
        $table->data[] = array ($link);
    }
}

echo "<br />";

echo html_writer::table($table);
echo $OUTPUT->footer();
