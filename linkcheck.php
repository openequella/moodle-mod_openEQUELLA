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

/////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////

// Finds all equella resource items and checks them for integrity. If found any links are found to be
// bad, and email is sent to someone who can do something about it.  This is best setup as a cron job
// to run once a day/week/month similar to how the standard Moodle cron jobs are run, for example:
//
// 		wget -q -O /dev/null http://moodle.somewhere.edu/mod/equella/linkcheck.php?password=passwordbelow
// If you want a log of the actions, redirect the output to a real file rather than /dev/null.
//
// The "password" parameter for the URL must match the following value.  If the value is blank, this
// functionality is disabled.
//
$password = '';

// Should invalid links be disabled?
//
$disable_links = false;

// Who should be notified of invalid links? Specify an array of Moodle user names
//
$notify = array('some.user');

/////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////

include '../../config.php';
require_once($CFG->dirroot.'/mod/equella/common/lib.php');
require_once($CFG->dirroot.'/course/lib.php');

if( $password == '' ) {
	echo 'EQUELLA link checking has not been configured.  Please see the source code for this page.';
	exit;
}

$password_param = required_param('password', PARAM_RAW);
if( $password_param != $password ) {
	echo 'Password doesn\'t match.';
	exit;
}

global $DB, $CFG;

// Re-use the same CURL instance to maintain the same EQUELLA session for all URL checks
$ch = curl_init();
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_FAILONERROR, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$temp = tmpfile();
curl_setopt($ch, CURLOPT_COOKIEFILE, $temp);
curl_setopt($ch, CURLOPT_COOKIEJAR, $temp);

echo '<style>.ok {color: green;} .bad {color: red;}</style><ul>';

foreach( $DB->get_records('equella') as $resource ) {

	curl_setopt($ch, CURLOPT_URL, equella_appendtoken($resource->url, equella_getssotoken_api()));

	echo '<li>Checking <a href="'.$resource->url.'">'.$resource->url.'</a><br>';
	if( curl_exec($ch) ) {
		echo '<span class="ok">OK</span>';
	} else {
		echo '<span class="bad">Could not find in EQUELLA</span><br>';

		//tell someone - get users with course edit perms for the course in question
		$recipients = $DB->get_records_list('user', 'username', $notify);
		if( $recipients ) {
			$from = get_admin();
			$subject = get_string('checker.subject', 'equella');
			$course = $DB->get_record('course', array('id' => $resource->course));
			$message = get_string('checker.message', 'equella',	array(
				'name' => $resource->name,
				'url' => $resource->url,
				'coursename' => $course->shortname,
				'courseurl' => $CFG->wwwroot.'/course/view.php?id='.$course->id
			));

			echo 'Emailing the following users:<ul>';
			foreach( $recipients as $recipient ) {
				$result = email_to_user($recipient, $from, $subject, $message, $message);
				echo "<li>$recipient->username (result was $result)</li>";
			}
			echo '</ul>';
		} else {
			echo 'WARNING: Nobody configured to be notified!';
		}

		//now mark the resource as hidden
		if( $disable_links ) {
			$cms = $DB->get_records_sql(
				"SELECT cm.* FROM {course_modules} cm
				INNER JOIN {modules} m ON cm.module = m.id
				WHERE m.name = :modname
				AND cm.course = :course
				AND cm.instance = :resource",
				array(
					'modname' => 'equella',
					'course' => $resource->course,
					'resource' => $resource->id
				)
			);

			foreach( $cms as $cm ) {
				set_coursemodule_visible($cm->id, 0);
			}
			rebuild_course_cache($resource->course);
		}
	}
	echo '</li>';
}

curl_close($ch);

echo '</ul>';


// The following method is currently disabled/deprecated due to incorrect users being emailed in some cases.
// Future investigations will be made to determine what the problem is here and correct it.
function get_course_editors($courseid) {
	global $DB;

	//get the 'course editors' with the lowest level context
	$capability = 'moodle/course:manageactivities';

	//course level editors
	$course_editors = $DB->get_records_sql(
		"SELECT u.* FROM {user} u
		INNER JOIN {role_assignments} ra ON u.id = ra.userid
		INNER JOIN {context} x ON ra.contextid = x.id
		INNER JOIN {course} c ON x.instanceid = c.id
		INNER JOIN {role} r ON ra.roleid = r.id
		INNER JOIN {role_capabilities} rc ON r.id = rc.roleid AND ra.contextid = rc.contextid
		WHERE rc.capability = :capability
		AND x.contextlevel = :contextlevel
		AND c.id = :courseid
		AND u.deleted = 0
		AND u.emailstop = 0",
		array(
			'contextlevel' => CONTEXT_COURSE,
			'capability' => $capability,
			'courseid' => $courseid,
		)
	);
	if( $course_editors ) {
		return $course_editors;
	}

	//cat level editors
	$course_editors = $DB->get_records_sql(
		"SELECT u.* FROM {user} u
		INNER JOIN {role_assignments} ra ON u.id = ra.userid
		INNER JOIN {context} x ON ra.contextid = x.id
		INNER JOIN {course_categories} cc ON x.instanceid = cc.id
		INNER JOIN {course} c ON cc.id = c.category
		INNER JOIN {role} r ON ra.roleid = r.id
		INNER JOIN {role_capabilities} rc ON r.id = rc.roleid AND ra.contextid = rc.contextid
		WHERE rc.capability = :capability
		AND x.contextlevel = :contextlevel
		AND c.id = :courseid
		AND u.deleted = 0
		AND u.emailstop = 0",
		array(
			'contextlevel' => CONTEXT_COURSECAT,
			'capability' => $capability,
			'courseid' => $courseid,
		)
	);
	if( $course_editors ) {
		return $course_editors;
	}

	//global level editors not admin
	$course_editors = $DB->get_records_sql(
		"SELECT u.* FROM {user} u
		INNER JOIN {role_assignments} ra ON u.id = ra.userid
		INNER JOIN {context} x ON ra.contextid = x.id
		INNER JOIN {role} r ON ra.roleid = r.id
		INNER JOIN {role_capabilities} rc ON r.id = rc.roleid AND ra.contextid = rc.contextid
		WHERE rc.capability = :capability
		AND x.contextlevel = :contextlevel
		AND r.name != 'Administrator'
		AND u.deleted = 0
		AND u.emailstop = 0",
		array(
			'contextlevel' => CONTEXT_SYSTEM,
			'capability' => $capability,
		)
	);
	if( $course_editors ) {
		return $course_editors;
	}

	//global admins
	$course_editors = $DB->get_records_sql(
		"SELECT u.* FROM {user} u
		INNER JOIN {role_assignments} ra ON u.id = ra.userid
		INNER JOIN {context} x ON ra.contextid = x.id
		INNER JOIN {role} r ON ra.roleid = r.id
		INNER JOIN {role_capabilities} rc ON r.id = rc.roleid AND ra.contextid = rc.contextid
		WHERE rc.capability = :capability
		AND x.contextlevel = :contextlevel
		AND r.name = 'Administrator'
		AND u.deleted = 0
		AND u.emailstop = 0",
		array(
			'contextlevel' => CONTEXT_SYSTEM,
			'capability' => $capability,
		)
	);
	if( $course_editors ) {
		return $course_editors;
	}

	return false;
}
