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

function equella_process_courseid($courseid) {
    return 'C' . $courseid;
}

/**
 * Library of functions for EQUELLA internal
 */

function equella_get_course_contents($courseid, $sectionid) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');

    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

    if (!file_exists($CFG->dirroot . '/course/format/' . $course->format . '/lib.php')) {
        throw new moodle_exception('cannotgetcoursecontents', 'webservice', '', null, get_string('courseformatnotfound', 'error', '', $course->format));
    } else {
        require_once($CFG->dirroot . '/course/format/' . $course->format . '/lib.php');
    }

    $context = context_course::instance($course->id, IGNORE_MISSING);

    $coursecontents = new stdClass;
    $coursecontents->id = equella_process_courseid($course->id);
    $coursecontents->code = $course->idnumber;
    $coursecontents->name = $course->fullname;
    $coursecontents->folders = array();

    if ($course->visible or has_capability('moodle/course:viewhiddencourses', $context)) {

        //retrieve sections
        $sections = get_all_sections($course->id);

        //for each sections (first displayed to last displayed)
        foreach ($sections as $key => $section) {

            $sectionvalues = new stdClass;

            if ((int)$section->section == (int)$sectionid) {
                $sectionvalues->selected = true;
            }
            $sectionvalues->id = $section->section;
            $sectionvalues->name = get_section_name($course, $section);
            $sectionvalues->folders = array();
            $sectioncontents = array();

            if (!isset($modinfo->sections[$section->section])) {
                $modinfo->sections[$section->section] = array();
            }
            //foreach ($modinfo->sections[$section->section] as $cmid) {
                //$cm = $modinfo->cms[$cmid];

                //if (!$cm->uservisible) {
                    //continue;
                //}

                //$module = array();

                //$module['id'] = $cm->id;
                //$module['name'] = format_string($cm->name, true);
                //$sectioncontents[] = $module;
            //}
            //$sectionvalues->folders = $sectioncontents;

            $coursecontents->folders[] = $sectionvalues;
        }
    }
    return $coursecontents;
}

/**
 * Returns general link or file embedding html.
 * @param string $fullurl
 * @param string $clicktoopen
 * @param string $mimetype
 * @return string html
 */
function equella_embed_form($courseid, $sectionid, $equellaurl) {
    global $CFG, $PAGE;

    $redirecturl = new moodle_url('/mod/equella/redirectselection.php', array('equellaurl'=>$equellaurl, 'courseid'=>$courseid, 'sectionid'=>$sectionid));

    $iframe = false;
    // IE can not embed stuff properly, that is why we use iframe instead.
    // Unfortunately this tag does not validate in xhtml strict mode,
    // but in any case it is undeprecated in HTML 5 - we will use it everywhere soon!
    if (check_browser_version('MSIE', 5)) {
        $iframe = true;
    }

    if ($iframe) {
        $code = <<<EOT
<div class="resourcecontent resourcegeneral">
  <iframe id="resourceobject" src="$redirecturl">
  </iframe>
</div>
EOT;
    } else {
        $param = '<param name="src" value="'.$redirecturl.'" />';

        $code = <<<EOT
<div class="resourcecontent resourcegeneral">
  <object id="resourceobject" data="$redirecturl" width="800" height="600" type="text/html">
    $param
  </object>
</div>
EOT;
    }

    // the size is hardcoded in the object above intentionally because it is adjusted by the following function on-the-fly
    $PAGE->requires->js_init_call('M.util.init_maximised_embed', array('resourceobject'), true);

    return $code;
}

/**
 * Returns general link or file embedding html.
 * @param string $fullurl
 * @param string $clicktoopen
 * @param string $mimetype
 * @return string html
 */
function equella_embed_general($fullurl, $clicktoopen, $mimetype = null) {
    global $CFG, $PAGE;

    if ($fullurl instanceof moodle_url) {
        $fullurl = $fullurl->out();
    }

    $iframe = false;

    // IE can not embed stuff properly, that is why we use iframe instead.
    // Unfortunately this tag does not validate in xhtml strict mode,
    // but in any case it is undeprecated in HTML 5 - we will use it everywhere soon!
    if (check_browser_version('MSIE', 5)) {
        $iframe = true;
    }

    if ($iframe) {
        $code = <<<EOT
<div class="resourcecontent resourcegeneral">
  <iframe id="resourceobject" src="$fullurl">
    $clicktoopen
  </iframe>
</div>
EOT;
    } else {
        $param = '<param name="src" value="'.$fullurl.'" />';

        $code = <<<EOT
<div class="resourcecontent resourcegeneral">
  <object id="resourceobject" data="$fullurl" width="800" height="600">
    $param
    $clicktoopen
  </object>
</div>
EOT;
    }

    // the size is hardcoded in the object above intentionally because it is adjusted by the following function on-the-fly
    $PAGE->requires->js_init_call('M.util.init_maximised_embed', array('resourceobject'), true);

    return $code;
}

/**
 * Returns general link or file embedding html.
 * @param string $fullurl
 * @param string $clicktoopen
 * @param string $mimetype
 * @return string html
 */
function equella_modal_dialog($courseid, $sectionid, $equellaurl) {
    global $CFG, $PAGE;

    $redirecturl = new moodle_url('/mod/equella/redirectselection.php', array('equellaurl'=>$equellaurl, 'courseid'=>$courseid, 'sectionid'=>$sectionid));

    $equellatitle = get_string('chooseeqeullaresources', 'mod_equella');
    $equellacontainer = 'equellacontainer';
    $cancel = get_string('cancel');
    $html = <<<EOF
<div>
    <button id="openequellachooser">$equellatitle</button>
    <a href="">$cancel</a>
</div>
EOF;
    $PAGE->requires->js_init_call('M.mod_equella.display_equella', array($equellacontainer, 1040, 600, $equellatitle, $redirecturl->out()), true);

    return $html;
    //return $code;
}
