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

require_once(dirname(__FILE__) . "/../../config.php");
require_once($CFG->dirroot . '/mod/equella/locallib.php');

$httpbody = file_get_contents("php://input");

if (equella_lti_oauth::verify_message($httpbody)) {
    $handler = new equella_lti_grading($httpbody);
    $handler->process();
} else {
    throw new invalid_parameter_exception('OAuth signature invalid');
}
