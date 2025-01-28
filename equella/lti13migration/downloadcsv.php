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

require_once('../../../config.php');
require_once($CFG->dirroot . '/mod/lti/classes/external.php');
require_once($CFG->dirroot . '/lib/datalib.php');
require_once($CFG->dirroot . '/mod/equella/lib.php');

global $DB;

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=courses.csv');

// Cannot use `$DB0->get_field` due to issue <https://tracker.moodle.org/browse/MDL-27629>.
$oeqMoodleModuleID = $DB->get_field_sql("SELECT id FROM {modules} WHERE name = 'equella'");

// Moodle does not provide a function that can return a list of courses that contain certain specified modules.
// One would have to get a full list of courses and then filter the list by checking whether each course has
// the specified modules.
// So using the below SQL should be better.
$courseSql = "SELECT c.id, c.fullname FROM {course} c, {course_modules} cm" .
    " WHERE cm.module = " . $oeqMoodleModuleID .
    " AND c.id = cm.course" .
    " GROUP BY c.id, c.fullname HAVING count(*) > 0";
$courses = $DB->get_records_sql($courseSql);

$csvHeaders = array('Course ID', 'Course name', 'Course URL');
$csvContent = array_map(function ($course) {
    $courseId = $course->id;
    $courseUrl = new moodle_url('/course/view.php', array('id' => $courseId));
    return array($courseId, $course->fullname, $courseUrl->out(false));
}, $courses);

ob_start();

$outputBuffer = fopen('php://output', 'w');
fputcsv($outputBuffer, $csvHeaders);

foreach ($csvContent as $i => $row) {
    fputcsv($outputBuffer, $row);
    if ($i % 100 == 0) {
        ob_flush();
        flush();
    }
}

fclose($outputBuffer);
exit;
