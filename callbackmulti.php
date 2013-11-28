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


$links = required_param('tlelinks', PARAM_RAW);
$courseid = required_param('course', PARAM_INT);
$sectionnum = optional_param('section', 0, PARAM_INT);

require_login($courseid);
$coursecontext = context_course::instance($courseid);
require_capability('moodle/course:manageactivities', $coursecontext);

$links = json_decode($links, true);
$mod = new stdClass;
$mod->course = $courseid;
$mod->modulename = 'equella';
$module = $DB->get_record('modules', array('name'=>$mod->modulename));
$mod->module = $module->id;

foreach ($links as $link) {

    $mod->name = htmlspecialchars($link['name'], ENT_COMPAT, 'UTF-8');
    $mod->intro = $link['description'];
    $mod->introformat = FORMAT_HTML;
    $mod->attachmentuuid = $link['attachmentUuid'];
    $mod->url = $link['url'];
    // if equella returns section id, overwrite moodle section parameter
    if (isset($link['folder']) && $link['folder'] != null) {
        $mod->section = clean_param($link['folder'], PARAM_INT);
    } else {
        $mod->section = $sectionnum;;
    }


    if (isset($link['mimetype'])) {
        $mod->mimetype = $link['mimetype'];
    } else {
        $mod->mimetype = mimeinfo('type', $mod->url);
    }

    if (isset($link['activationUuid'])) {
        $mod->activation = $link['activationUuid'];
    }
    $equellaid = equella_add_instance($mod);

    $mod->instance = $equellaid;

    // course_modules and course_sections each contain a reference
    // to each other, so we have to update one of them twice.
    if (! $mod->coursemodule = add_course_module($mod) ) {
        print_error('cannotaddcoursemodule');
    }

    $modcontext = get_context_instance(CONTEXT_MODULE, $mod->coursemodule);

    if (! $addedsectionid = add_mod_to_section($mod) ) {
        print_error('cannotaddcoursemoduletosection');
    }

    if (! $DB->set_field('course_modules', 'section', $addedsectionid, array('id' => $mod->coursemodule))) {
        print_error('Could not update the course module with the correct section');
    }

    set_coursemodule_visible($mod->coursemodule, true);

    $eventdata = new stdClass();
    $eventdata->modulename = $mod->modulename;
    $eventdata->name       = $mod->name;
    $eventdata->cmid       = $mod->coursemodule;
    $eventdata->courseid   = $mod->course;
    $eventdata->userid     = $USER->id;
    events_trigger('mod_created', $eventdata);

    $url = "view.php?id={$mod->coursemodule}";
    add_to_log($mod->course, $mod->modulename, 'add EQUELLA', $url, "$mod->modulename ID: $mod->instance", $mod->instance);
}

$courseurl = new moodle_url('/course/view.php', array('id'=>$courseid));
$courseurl = $courseurl->out(false);
rebuild_course_cache($courseid);
echo '<html><body>';
echo html_writer::script("window.parent.document.location='$courseurl';");
echo '</body></html>';
