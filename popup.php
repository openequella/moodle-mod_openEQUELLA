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
require_once($CFG->dirroot . '/mod/equella/lib.php');
require_login();

$cmid = required_param('cmid', PARAM_INT);

if (equella_get_config('equella_enable_lti')) {
    if(empty(mod_equella_get_sso_userfield_value())){
        $PAGE->set_url(new moodle_url('/mod/equella/popup.php', array('cmid' => $cmid)));
        $context = context_module::instance($cmid);
        $PAGE->set_context($context);
        $PAGE->set_pagelayout('popup');

        echo $OUTPUT->header();
        $errparams = new stdClass();
        $errparams->field = \mod_equella\user_field::get_equella_userfield_display_name();
        echo $OUTPUT->notification(get_string('erroruserfieldempty', 'mod_equella', $errparams), 'notifyproblem');
        echo $OUTPUT->footer();
        exit;
    }
    $url = new moodle_url('/mod/equella/ltilaunch.php', array('cmid' => $cmid));
} else {
    $url = new moodle_url('/mod/equella/view.php', array('id' => $cmid,'inpopup' => true));
}

redirect($url);
