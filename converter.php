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

/*
 * A script to convert resources from the old equella moodle modules
 * (tleplan, tlecontribute, tleresource) to the new equella module.
 *
 * To run this script, navigate to http://MOODLE.SERVER/mod/equella/converter.php 
 * and it will process all of the old equella items, adding a new item, resetting 
 * the values, then deleting the old one.
 */

require_once("../../config.php");
require_once("../../course/lib.php");
require_once("lib.php");

require_login();

$items = get_records("resource");

if (empty($items))
{
	print "<h3>No items found</h3><br/>\n";
	exit;
}



//Loop over all existing resources
foreach ($items as $item)
{

	$urlparts = parse_url($item->reference);

	
	//Only process old equella resources
	//skip others
	if 	(	!stristr($CFG->wwwroot, $urlparts['host']))
	{
		continue;
	}
	
	if (	!stristr($urlparts['path'], "mod/tleplan") &&
			!stristr($urlparts['path'], "mod/tlecontribute") &&
			!stristr($urlparts['path'], "mod/tleresource")
	)
	{
		continue;
	}

	$course = get_record("course", "id", $item->course);
	$cm = get_coursemodule_from_instance("resource", $item->id, $course->id);

	if (! $module = get_record("modules", "name", "equella")) {
		error("This module type doesn't exist");
	}


	//extract the query part of the url
	$query= array();
	foreach (explode('&',$urlparts['query']) as $part){
		list($key, $value) = explode('=', $part, 2);
		$query[$key] = $value;
	}


	if ($query['url'])
	{
		$url=$query['url'];
	}
	else
	{
		$url=$item->reference; //no 'url=' so use the full reference
	}


	//get the section of the old item
	$section = get_record("course_sections", "id", $cm->section, "course", $course->id);

	print "<h3>Found old item: </h3>\n";
	print "Name: $item->name<br/>\n";
	print "Reference: $item->reference<br/>\n";
	print "Url: $url<br/>\n";
	print "Course: $course->id<br/>\n";
	print "Module: $cm->module<br/>\n";
	print "Section: $cm->section<br/>\n";
	print "Section ID: $section->section<br/>\n";
	print "Cource Module: $cm->id<br/>\n";
	print "Visible: $cm->visible<br/>\n";
	print "Group Mode: $cm->groupmode<br/><br/>\n";



	//We are creating a new item so the cm and instance start
	//as blank
	$newItem->coursemodule = '';
	$newItem->instance = '';

	$newItem->course = $course->id;
	$newItem->section = $section->section;

	$newItem->module     = $module->id;
	$newItem->modulename = $module->name;

	$newItem->name = $item->name;
	$newItem->url = $url;
	$newItem->summary = $item->summary;
	$newItem->timemodified = $item->timemodified;
	$newItem->popup = $item->popup;
	$newItem->visible =$cm->visible;
	$newItem->groupmode =$cm->groupmode;


	print "Adding new item<br/>\n";
	$newItem = addslashes_object($newItem);
	$newItem = addToNew($newItem);

	resetDates($newItem, $item->timemodified);
	setPopup($newItem, $item->popup);

	if (isset($cm->indent))
	{
		setIndent($newItem->coursemodule, $cm->indent);
	}

	if (isset($cm->score))
	{
		setScore($newItem->coursemodule, $cm->score);
	}

	print "Deleting old item<br/>\n";
	deleteOld($cm->id);

	print "Done<br/><br/>\n\n";

}

print "<h3>All resources processed.</h3><br/><br/>\n\n";


function addToNew($input)
{
	global $CFG, $USER;

	$section = $input->section;
	$course = $input->course;


	if (! $course = get_record("course", "id", $course)) {
		error("This course doesn't exist");
	}

	if (! $module = get_record("modules", "name", $input->modulename)) {
		error("This module type doesn't exist");
	}


	if (!course_allowed_module($course, $module->id)) {
		error("This module has been disabled for this particular course");
	}


	if (!course_allowed_module($course,$input->modulename)) {
		error("This module ($input->modulename) has been disabled for this particular course");
	}

	if (!isset($input->name) || trim($input->name) == '') {
		$input->name = get_string("modulename", $input->modulename);
	}

	$addinstancefunction    = $input->modulename."_add_instance";
	$returnfromfunc = $addinstancefunction($input);;

	if (!$returnfromfunc) {
		error("Could not add a new instance of $input->modulename", "view.php?id=$course->id");
	}

	if (is_string($returnfromfunc)) {
		error($returnfromfunc, "view.php?id=$course->id");
	}

	if (!isset($input->groupmode)) { // to deal with pre-1.5 modules
		$input->groupmode = $course->groupmode;  /// Default groupmode the same as course
	}

	$input->instance = $returnfromfunc;


	// course_modules and course_sections each contain a reference
	// to each other, so we have to update one of them twice.

	if (! $input->coursemodule = add_course_module($input) ) {
		error("Could not add a new course module");
	}


	if (! $sectionid = add_mod_to_section($input) ) {
		error("Could not add the new course module to that section");
	}

	if (! set_field("course_modules", "section", $sectionid, "id", $input->coursemodule)) {
		error("Could not update the course module with the correct section");
	}



	if (!isset($input->visible)) {   // We get the section's visible field status
		$input->visible = get_field("course_sections","visible","id",$sectionid);
	}
	// make sure visibility is set correctly (in particular in calendar)
	set_coursemodule_visible($input->coursemodule, $input->visible);

	add_to_log($course->id, "course", "add mod",
                       "../mod/$input->modulename/view.php?id=$input->coursemodule",
                       "$input->modulename $input->instance");
	add_to_log($course->id, $input->modulename, "add",
                       "view.php?id=$input->coursemodule",
                       "$input->instance", $input->coursemodule);

	rebuild_course_cache($course->id);

	return $input;

}

function setPopup($item, $popup)
{
	set_field("equella", "popup", $popup, "id", $item->instance);
}


//reset the date
function resetDates($item, $time)
{
	set_field("equella", "timemodified", $time, "id", $item->instance);
	set_field("equella", "timecreated", $time, "id", $item->instance);
}


function setIndent($cm, $indent)
{
	if (!set_field("course_modules", "indent", $indent, "id", $cm)) {
		error("Could not update the indent level on that course module");
	}
}

function setScore($cm, $score)
{
	if (!set_field("course_modules", "score", $score, "id", $cm)) {
		error("Could not update the score on that course module");
	}
}


function deleteOld($delete)
{
	global $USER, $CFG;
	if (! $cm = get_record("course_modules", "id", $delete)) {
		error("This course module doesn't exist");
	}

	if (! $course = get_record("course", "id", $cm->course)) {
		error("This course doesn't exist");
	}

	if (! $module = get_record("modules", "id", $cm->module)) {
		error("This module doesn't exist");
	}

	if (! $instance = get_record($module->name, "id", $cm->instance)) {
		// Delete this module from the course right away
		if (! delete_mod_from_section($cm->id, $cm->section)) {
			notify("Could not delete the $module->name from that section");
		}
		if (! delete_course_module($cm->id)) {
			notify("Could not delete the $module->name (coursemodule)");
		}
		error("The required instance of this module didn't exist.  Module deleted.",
                  "$CFG->wwwroot/course/view.php?id=$course->id");
	}


	$mod->coursemodule = $cm->id;
	$mod->section      = $cm->section;
	$mod->course       = $cm->course;
	$mod->instance     = $cm->instance;
	$mod->modulename   = $module->name;
	$mod->fullmodulename  = $fullmodulename;
	$mod->instancename = $instance->name;
	$mod->sesskey      = !empty($USER->id) ? $USER->sesskey : '';


	if (!isteacheredit($course->id)) {
		error("You can't modify this course!");
	}

	$mod->course = $course->id;
	$mod->modulename = clean_param($mod->modulename, PARAM_SAFEDIR);  // For safety
	$modlib = "$CFG->dirroot/mod/$mod->modulename/lib.php";

	if (file_exists($modlib)) {
		include_once($modlib);
	} else {
		error("This module is missing important code! ($modlib)");
	}


	$deleteinstancefunction = $mod->modulename."_delete_instance";

	if (! $deleteinstancefunction($mod->instance)) {
		notify("Could not delete the $mod->modulename (instance)");
	}
	if (! delete_course_module($mod->coursemodule)) {
		notify("Could not delete the $mod->modulename (coursemodule)");
	}
	if (! delete_mod_from_section($mod->coursemodule, "$mod->section")) {
		notify("Could not delete the $mod->modulename from that section");
	}


	add_to_log($course->id, "course", "delete mod",
                           "view.php?id=$mod->course",
                           "$mod->modulename $mod->instance", $mod->coursemodule);

	rebuild_course_cache($course->id);
}

?>
