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

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // newmodule ID

if ($id) {
	if (! $cm = get_coursemodule_from_id("equella", $id)) {
		error("Course Module ID was incorrect");
	}

	if (! $course = get_record("course", "id", $cm->course)) {
		error("Course is misconfigured");
	}

	if (! $equella = get_record("equella", "id", $cm->instance)) {
		error("Course module is incorrect");
	}

} else {
	if (! $equella = get_record("equella", "id", $a)) {
		error("Course module is incorrect");
	}
	if (! $course = get_record("course", "id", $equella->course)) {
		error("Course is misconfigured");
	}
	if (! $cm = get_coursemodule_from_instance("equella", $equella->id, $course->id)) {
		error("Course Module ID was incorrect");
	}
}

require_course_login($course);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
require_capability('moodle/course:viewparticipants', $context);

if (!$cm->visible and !has_capability('moodle/course:viewhiddenactivities', $context)) {
	notice(get_string("activityiscurrentlyhidden"));
}

add_to_log($course->id, "equella", "view", "view.php?id=$cm->id", $equella->id, $cm->id);

$url = equella_appendtoken($equella->url);
if (optional_param('inpopup', 0, PARAM_BOOL))
{
	redirect($url);
}
else
{
    $navigation = build_navigation(array(), $cm);          
	print_header_simple($equella->name, "", $navigation, "", "", true, 
		update_module_button($cm->id, $course->id, get_string("modulename", "equella")), navmenu($course, $cm));

	$formatoptions = new object();
	$formatoptions->noclean = true;

	if( trim($equella->summary) ) {
		print_simple_box(format_text($equella->summary, FORMAT_MOODLE, $formatoptions, $course->id), "center");
	}

	print_simple_box_start("CENTER");

	if($CFG->version >=  2007021560) {
		$width = "99%";
	} else {
		$width = "750px";
	}
	echo '<iframe id="ifm" src="'.htmlentities ($url).'" width="'.$width.'" height="500"></iframe>';
	echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/equella/iframe.js"></script>';

	print_simple_box_end();
	echo "<br />";

	print_footer($course);
}
?>
<script language="JavaScript" type="text/javascript">
	<!--
		try {
			document.getElementById('ifm').onload=resizeIframe;
			window.onresize = resizeIframe;
		} catch (e) {
			// ignore
		}
	//-->
</script>

