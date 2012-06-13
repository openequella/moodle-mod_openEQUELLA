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
require_once("../../course/lib.php");
require_once("lib.php");

require_login();
$links = stripslashes(required_param('tlelinks', PARAM_RAW));
$links = json_decode($links, true);
$mod->course = required_param('course', PARAM_INT);
$mod->module = required_param('module', PARAM_INT);
$mod->coursemodule = required_param('coursemodule', PARAM_INT);
$mod->section = required_param('section', PARAM_INT);
$mod->modulename = "equella";
foreach ($links as $link)
{
	$mod->name = addslashes(htmlentities($link["name"], ENT_COMPAT, 'UTF-8'));
	$mod->summary = addslashes($link["description"]);
	$mod->url = $link["url"];
	if (isset($link["activationUuid"]))
	{
		$mod->activation = $link["activationUuid"];
	}
	$return = equella_add_instance($mod);

	$mod->instance = $return;

	// course_modules and course_sections each contain a reference
	// to each other, so we have to update one of them twice.
	if (! $mod->coursemodule = add_course_module($mod) ) {
		error("Could not add a new course module");
	}
	
	$modcontext = get_context_instance(CONTEXT_MODULE, $mod->coursemodule);
	
	if (! $sectionid = add_mod_to_section($mod) ) {
		error("Could not add the new course module to that section");
	}

	if (! set_field("course_modules", "section", $sectionid, "id", $mod->coursemodule)) {
		error("Could not update the course module with the correct section");
	}
	set_coursemodule_visible($mod->coursemodule, true);

    add_to_log($mod->course, "course", "add mod",
               "../mod/$mod->modulename/view.php?id=$mod->coursemodule",
               "$mod->modulename $mod->instance");
    add_to_log($mod->course, $mod->modulename, "add",
               "view.php?id=$mod->coursemodule",
               "$mod->instance", $mod->coursemodule);
}

rebuild_course_cache($mod->course);
?>

<html>
<head>
<title>Adding item...</title>
<script>
window.parent.document.location = '<?php print "$CFG->wwwroot/course/view.php?id=$mod->course" ?>';
</script>
</head>
<body>
</body>
</html>
