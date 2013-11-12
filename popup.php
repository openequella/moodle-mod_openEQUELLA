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

require_once('../../config.php');

$cmid = required_param('cmid', PARAM_INT);

if ($CFG->equella_enable_lti) {
    $url = new moodle_url('/mod/equella/ltilaunch.php', array('cmid'=>$cmid));
} else {
    $url = new moodle_url('/mod/equella/view.php', array('id'=>$cmid, 'inpopup'=>true));
}

redirect($url);
