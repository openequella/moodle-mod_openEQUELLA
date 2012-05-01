<?php

// This file is part of the EQUELLA Moodle Integration - http://code.google.com/p/equella-moodle-module/
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

$id = required_param('id');   // course

if (! $course = get_record("course", "id", $id)) {
    error("Course ID is incorrect");
}

require_course_login($course);
add_to_log($course->id, "equella", "view all", "index.php?id=$course->id", "");

$strnoinst = get_string("noinstances", "equella");
$strequellas = get_string("modulenameplural", "equella");
$strequella = get_string("modulename", "equella");
$strweek = get_string("week");
$strtopic = get_string("topic");
$strname = get_string("name");

$navlinks = array();
$navlinks[] = array('name' => $strequellas, 'link' => '', 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);
    
print_header_simple($strequellas, "", $navigation, "", "", true, "", navmenu($course));

if (! $equellas = get_all_instances_in_course("equella", $course)) {
    notice($strnoinst, "../../course/view.php?id=$course->id");
    die;
}

$timenow = time();

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
    $group = get_record("groups", "id", $currentgroup);
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

print_table($table);

print_footer($course);
?>
