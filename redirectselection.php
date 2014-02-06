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

/**
 * Generate course structure in json and POST to EQUELLA
 *
 */
header('Content-Type:text/html;charset=UTF-8');
require_once('../../config.php');
require_once($CFG->dirroot.'/mod/equella/common/lib.php');
require_once($CFG->dirroot.'/mod/equella/locallib.php');
$equellaurl = required_param('equellaurl', PARAM_RAW);
$courseid = required_param('courseid', PARAM_INT);
$sectionid = required_param('sectionid', PARAM_INT);

require_login();
$PAGE->set_context(get_system_context());
$PAGE->set_pagelayout('embedded');
$PAGE->requires->js('/mod/equella/module.js', true);
$params = array('courseid'=>$courseid, 'equellaurl'=>$equellaurl);
$PAGE->set_url('/mod/equella/redirectselection.php', $params);

echo $OUTPUT->header();

$contents = equella_get_course_contents($courseid, $sectionid);
$json = json_encode($contents);

$formid = 'equellaselectionform';

echo html_writer::start_tag('form', array('method'=>'post', 'id'=>$formid, 'action'=>$equellaurl));
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'structure', 'value'=>$json));
echo html_writer::end_tag('form');

$PAGE->requires->js_init_call('M.mod_equella.submitform', array($formid), true);
echo $OUTPUT->footer();
