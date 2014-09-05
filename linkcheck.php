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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once ($CFG->dirroot . '/mod/equella/common/lib.php');
require_once ($CFG->dirroot . '/course/lib.php');

// ///////////////////////////////////////////////////////////
// ///////////////////////////////////////////////////////////

// Finds all equella resource items and checks them for integrity. If found any links are found to be
// bad, and email is sent to someone who can do something about it. This is best setup as a cron job
// to run once a day/week/month similar to how the standard Moodle cron jobs are run, for example:
//
// wget -q -O /dev/null http://moodle.somewhere.edu/mod/equella/linkcheck.php?password=passwordbelow
// If you want a log of the actions, redirect the output to a real file rather than /dev/null.
//
// The "password" parameter for the URL must match the following value. If the value is blank, this
// functionality is disabled.
//
$password = '';

// Should invalid links be disabled?
//
$disable_links = false;

// Who should be notified of invalid links? Specify an array of Moodle user names
//
$notify = array('some.user');

// ///////////////////////////////////////////////////////////

if (empty($password)) {
    echo 'EQUELLA link checking has not been configured.  Please see the source code for this page.';
    exit();
}

$password_param = required_param('password', PARAM_RAW);
if ($password_param != $password) {
    echo 'Password doesn\'t match.';
    exit();
}

$http = new curl(array('cookie'=>true));

echo '<style>.ok {color: green;} .bad {color: red;}</style><ul>';

foreach($DB->get_records('equella') as $resource) {

    $url = equella_appendtoken($resource->url, equella_getssotoken_api());
    $http->head($url);

    $info = $http->get_info();
    $statuscode = $info['http_code'];

    echo '<li>Checking <a href="' . $resource->url . '">' . $resource->url . '</a><br>';
    if ((int)$statuscode == 200) {
        echo '<span class="ok">OK</span>';
    } else {
        echo '<span class="bad">Could not find in EQUELLA</span><br>';
        // tell someone - get users with course edit perms for the course in question
        $recipients = $DB->get_records_list('user', 'username', $notify);
        if ($recipients) {
            $from = get_admin();
            $subject = get_string('checker.subject', 'equella');
            $course = $DB->get_record('course', array('id' => $resource->course));
            $courseurl = new moodle_url('/course/view.php', array('id'=>$course->id));
            $message = get_string('checker.message', 'equella', array(
                            'name' => $resource->name,
                            'url' => $resource->url,
                            'coursename' => $course->shortname,
                            'courseurl' => $courseurl
                        )
                );

            echo 'Emailing the following users:<ul>';
            foreach($recipients as $recipient) {
                $result = email_to_user($recipient, $from, $subject, $message, $message);
                echo "<li>$recipient->username (result was $result)</li>";
            }
            echo '</ul>';
        } else {
            echo 'WARNING: Nobody configured to be notified!';
        }

        // now mark the resource as hidden
        $sql = "SELECT cm.*
                  FROM {course_modules} cm
                       INNER JOIN {modules} m ON cm.module = m.id
                 WHERE m.name = :modname AND cm.course = :course AND cm.instance = :resource";
        if ($disable_links) {
            $cms = $DB->get_records_sql($sql, array('modname' => 'equella','course' => $resource->course,'resource' => $resource->id));
            foreach($cms as $cm) {
                set_coursemodule_visible($cm->id, 0);
            }
            rebuild_course_cache($resource->course);
        }
    }
    echo '</li>';
}

echo '</ul>';
