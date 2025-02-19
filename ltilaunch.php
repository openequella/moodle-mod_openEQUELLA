<?php
// This file is part of the EQUELLA module - http://git.io/vUuof
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
require_once ('../../config.php');
require_once ('lib.php');
require_once ('locallib.php');
require_once ($CFG->libdir . '/completionlib.php');

require_login();

$action = optional_param('action', 'view', PARAM_ACTION);

if ($action == 'view') {
    $cmid = required_param('cmid', PARAM_INT);
    $cm = get_coursemodule_from_id('equella', $cmid, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $equella = $DB->get_record('equella', array('id' => $cm->instance), '*', MUST_EXIST);
    $context = context_module::instance($cm->id);

    $PAGE->set_context($context);
    $PAGE->set_pagelayout('embedded');

    require_capability('mod/equella:view', $context);

    $equella->cmid = $cmid;
    $equella->course = $course->id;
    $params = equella_lti_params($equella, $course);

    if (class_exists('mod_equella\\event\\course_module_viewed')) {
        $eventparams = array(
            'context' => $context,
            'objectid' => $equella->id
        );
        $event = \mod_equella\event\course_module_viewed::create($eventparams);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('equella', $equella);
        $event->trigger();
    } else {
        add_to_log($course->id, "equella", "view equella resource", "view.php?id=$cm->id", $equella->id, $cm->id);
    }

    // Update 'viewed' state if required by completion system
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);

    echo '<html><body>';
    echo equella_lti_launch_form($equella->url, $params);
    echo '</body></html>';

} elseif ($action == 'select') {
    $args = new stdClass();
    $args->course = required_param('course', PARAM_INT);
    $args->section = required_param('section', PARAM_INT);
    $course = $DB->get_record('course', array('id' => $args->course), '*', MUST_EXIST);
    $context = context_course::instance($args->course);

    $PAGE->set_context($context);

    require_capability('moodle/course:manageactivities', $context);

    $url = equella_build_integration_url($args, false);
    $extraparams = $url->params();
    if (equella_get_config('equella_action') == EQUELLA_ACTION_STRUCTURED) {
        $contents = equella_get_course_contents($course->id, $args->section);
        $json = json_encode($contents);
        $extraparams['structure'] = $json;
    }

    $extraparams['itemXml'] = get_item_xml($course, $args->section);

    $equella = new stdClass();
    $equella->id = 0;
    $equella->course = $args->course;
    $equella->url = equella_get_config('equella_url');
    $params = equella_lti_params($equella, $course, $extraparams);

    echo '<html><body>';
    echo equella_lti_launch_form($equella->url, $params);
    echo '</body></html>';
}

// This is the same data as equella_add_lmsinfo_parameters
function get_item_xml($course, $sectionid) {
    global $USER, $DB;

    $xml = new SimpleXMLElement('<xml />');
    $integxml = $xml->addChild('integration');
    $integxml->addChild('lms', 'Moodle');
    $integxml->addChild('contributiontype', 'integration');
    $integmoodlexml = $integxml->addChild('moodle');

    // User
    $equellauserfield = mod_equella_get_userfield_value();

    $integuserxml = $integxml->addChild('user');
    $integuserxml->addChild('username', $equellauserfield);
    $integuserxml->addChild('firstname', $USER->firstname);
    $integuserxml->addChild('lastname', $USER->lastname);

    // Generic course info
    $integcoursexml = $integxml->addChild('course');
    $integcoursexml->addChild('fullname', $course->fullname);
    $integcoursexml->addChild('shortname', $course->shortname);
    $integcoursexml->addChild('code', $course->idnumber);

    // Moodle specific course info
    $integmoodlecoursexml = $integmoodlexml->addChild('course');
    $integmoodlecoursexml->addChild('idnumber', $course->idnumber);
    $integmoodlecoursexml->addChild('id', $course->id);

    // Moodle section info
    $integmoodlexml->addChild('section', get_section_name($course, $sectionid));

    // Moodle specific course categories (there is probably a more optimal way to do this)
    $catparentxml = $integmoodlexml;
    $catid = $course->category;
    while ($catid !== 0){
        if ($category = $DB->get_record('course_categories', array('id' => $catid))){
            $catxml = $catparentxml->addChild('category');
            $catxml->addChild('name', $category->name);

            $catparentxml = $catxml;
            $catid = $category->parent;
        }
        else {
            $catid = 0;
        }
    }

    return str_replace(array("\r", "\n"),'',$xml->asXML());
}
