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
defined('MOODLE_INTERNAL') || die();

require_once ($CFG->libdir . '/externallib.php');
require_once ($CFG->libdir . '/enrollib.php');
require_once ($CFG->libdir . '/accesslib.php');
require_once ($CFG->libdir . '/authlib.php');
require_once ($CFG->libdir . '/moodlelib.php');
require_once ($CFG->dirroot . '/course/lib.php');
require_once ($CFG->dirroot . '/enrol/externallib.php');

require_once (dirname(__FILE__) . '/lib.php');
require_once (dirname(__FILE__) . '/locallib.php');
class equella_external extends external_api {
    const READ_PERMISSION = 'moodle/course:view';
    const WRITE_PERMISSION = 'moodle/course:manageactivities';
    const DEVMODE = 0; // DO-NOT-COMMI
    private static $enrollmentcount = array();
    private static $instructors = array();
    private static $coursesections = array();
    public static function find_usage_for_item_parameters() {
        return new external_function_parameters(array(

            'user' => new external_value(PARAM_RAW, 'Username'),
            'uuid' => new external_value(PARAM_RAW, 'Item UUID'),
            'version' => new external_value(PARAM_INT, 'Item version'),
            'isLatest' => new external_value(PARAM_BOOL, 'The supplied version param is the latest version of this item'),
            'archived' => new external_value(PARAM_BOOL, 'Include hidden items and courses'),
            'allVersion' => new external_value(PARAM_BOOL, 'Show all versions of this item')));
    }
    public static function find_all_usage_parameters() {
        return new external_function_parameters(array(

            'user' => new external_value(PARAM_RAW, 'Username'),
            'query' => new external_value(PARAM_RAW, 'Freetext query'),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'sectionid' => new external_value(PARAM_INT, 'Section ID'),
            'archived' => new external_value(PARAM_BOOL, 'Include hidden items and courses'),
            'offset' => new external_value(PARAM_INT, 'Results paging'),
            'count' => new external_value(PARAM_INT, 'Results paging'),
            'sortcolumn' => new external_value(PARAM_RAW, 'The name of the sort column: name, course or timecreated (default is timecreated)'),
            'sortasc' => new external_value(PARAM_BOOL, 'Sort ascending')));
    }
    public static function unfiltered_usage_count_parameters() {
        return new external_function_parameters(array(

        'user' => new external_value(PARAM_RAW, 'Username'),'query' => new external_value(PARAM_RAW, 'Freetext query'),'archived' => new external_value(PARAM_BOOL, 'Include hidden items and courses')));
    }
    public static function list_courses_for_user_parameters() {
        return new external_function_parameters(array(

        'user' => new external_value(PARAM_RAW, 'Username'),'modifiable' => new external_value(PARAM_BOOL, 'Only return courses user can add content to'),'archived' => new external_value(PARAM_BOOL, 'Show hidden courses as well')));
    }
    public static function get_course_code_parameters() {
        return new external_function_parameters(array(

        'user' => new external_value(PARAM_RAW, 'Username'),'courseid' => new external_value(PARAM_RAW, 'Course id')));
    }
    public static function list_sections_for_course_parameters() {
        return new external_function_parameters(array(

        'user' => new external_value(PARAM_RAW, 'Username'),'courseid' => new external_value(PARAM_RAW, 'Course ID')));
    }
    public static function edit_item_parameters() {
        return new external_function_parameters(array(

        'user' => new external_value(PARAM_RAW, 'Username'),'itemid' => new external_value(PARAM_RAW, 'Item ID'),'title' => new external_value(PARAM_RAW, 'Title'),'description' => new external_value(PARAM_RAW, 'Description')));
    }
    public static function move_item_parameters() {
        return new external_function_parameters(array(

        'user' => new external_value(PARAM_RAW, 'Username'),'itemid' => new external_value(PARAM_RAW, 'Item ID'),'courseid' => new external_value(PARAM_RAW, 'Course ID'),'locationid' => new external_value(PARAM_RAW, 'Location ID')));
    }
    public static function delete_item_parameters() {
        return new external_function_parameters(array(

        'user' => new external_value(PARAM_RAW, 'Username'),'itemid' => new external_value(PARAM_RAW, 'Item ID')));
    }
    public static function add_item_to_course_parameters() {
        return new external_function_parameters(array(

            'user' => new external_value(PARAM_RAW, 'Username'),
            'courseid' => new external_value(PARAM_RAW, 'Course ID'),
            'sectionid' => new external_value(PARAM_RAW, 'Section ID'),
            'itemUuid' => new external_value(PARAM_RAW, 'Item UUID'),
            'itemVersion' => new external_value(PARAM_INT, 'Item Version'),
            'url' => new external_value(PARAM_RAW, 'URL'),
            'title' => new external_value(PARAM_RAW, 'Title'),
            'description' => new external_value(PARAM_RAW, 'Description'),
            'attachmentUuid' => new external_value(PARAM_RAW, 'Attachment UUID')));
    }
    public static function test_connection_parameters() {
        return new external_function_parameters(array(

        'param' => new external_value(PARAM_RAW, 'Parameter to echo back')));
    }
    public static function find_usage_for_item_returns() {
        return new external_single_structure(array(

            'results' => new external_multiple_structure(new external_single_structure(array(

                'id' => new external_value(PARAM_RAW, 'id of content'),
                'coursename' => new external_value(PARAM_RAW, 'name of course'),
                'courseid' => new external_value(PARAM_RAW, 'id of the course'),
                'section' => new external_value(PARAM_RAW, 'location of resource'),
                'sectionid' => new external_value(PARAM_RAW, 'id of location of resource'),
                'dateAdded' => new external_value(PARAM_FLOAT, 'Date the item was added'),
                'dateModified' => new external_value(PARAM_FLOAT, 'Date the item details were modified in Moodle'),
                'uuid' => new external_value(PARAM_RAW, 'The uuid of the item link to.'),
                'version' => new external_value(PARAM_INT, 'The version of the item linked to.  Will be zero in the case of "Always latest"'),
                'attachment' => new external_value(PARAM_RAW, 'The attachment name, if any, that is linked to.'),
                'attachmentUuid' => new external_value(PARAM_RAW, 'The attachment UUID, if any, that is linked to.'),
                'moodlename' => new external_value(PARAM_RAW, 'The name of the resource in Moodle'),
                'moodledescription' => new external_value(PARAM_RAW, 'The description of the resource in Moodle'),
                'coursecode' => new external_value(PARAM_RAW, 'Course code e.g. MOO101'),
                'instructor' => new external_value(PARAM_RAW, 'The name of the course instructor'),
                'dateAccessed' => new external_value(PARAM_FLOAT, 'Last accessed date'),
                'enrollments' => new external_value(PARAM_FLOAT, 'Number of students enrolled in the course'),
                'visible' => new external_value(PARAM_BOOL, 'Whether the content is visible.  False if either the content itself or the course is not visible.'),
                'attributes' => new external_multiple_structure(new external_single_structure(array(

                'key' => new external_value(PARAM_RAW, 'Attribute key'),'value' => new external_value(PARAM_RAW, 'Attribute value'))), '', false))))));
    }
    public static function find_all_usage_returns() {
        return new external_single_structure(array(

            'available' => new external_value(PARAM_INT, 'Number of results available'),
            'results' => new external_multiple_structure(new external_single_structure(array(

                'id' => new external_value(PARAM_RAW, 'id of content'),
                'coursename' => new external_value(PARAM_RAW, 'name of course'),
                'courseid' => new external_value(PARAM_RAW, 'id of the course'),
                'section' => new external_value(PARAM_RAW, 'name of location of resource'),
                'sectionid' => new external_value(PARAM_RAW, 'id of location of resource'),
                'dateAdded' => new external_value(PARAM_FLOAT, 'Date the item was added'),
                'dateModified' => new external_value(PARAM_FLOAT, 'Date the item details were modified in Moodle'),
                'uuid' => new external_value(PARAM_RAW, 'The uuid of the item link to.'),
                'version' => new external_value(PARAM_INT, 'The version of the item linked to.  Will be zero in the case of "Always latest"'),
                'attachment' => new external_value(PARAM_RAW, 'The attachment name, if any, that is linked to.'),
                'attachmentUuid' => new external_value(PARAM_RAW, 'The attachment UUID, if any, that is linked to.'),
                'moodlename' => new external_value(PARAM_RAW, 'The name of the resource in Moodle'),
                'moodledescription' => new external_value(PARAM_RAW, 'The description of the resource in Moodle'),
                'coursecode' => new external_value(PARAM_RAW, 'Course code e.g. MOO101'),
                'instructor' => new external_value(PARAM_RAW, 'The name of the course instructor'),
                'dateAccessed' => new external_value(PARAM_FLOAT, 'Last accessed date'),
                'enrollments' => new external_value(PARAM_FLOAT, 'Number of students enrolled in the course'),
                'visible' => new external_value(PARAM_BOOL, 'Whether the content is visible.  False if either the content itself or the course is not visible.'),
                'attributes' => new external_multiple_structure(new external_single_structure(array(

                'key' => new external_value(PARAM_RAW, 'Attribute key'),'value' => new external_value(PARAM_RAW, 'Attribute value'))), '', false))))));
    }
    public static function unfiltered_usage_count_returns() {
        return new external_single_structure(array(

        'available' => new external_value(PARAM_INT, 'Number of results available')));
    }
    public static function list_courses_for_user_returns() {
        return new external_multiple_structure(new external_single_structure(array(

        'courseid' => new external_value(PARAM_INT, 'id of course'),'coursename' => new external_value(PARAM_RAW, 'name of course'),'archived' => new external_value(PARAM_BOOL, 'visibility of course'))));
    }
    public static function list_sections_for_course_returns() {
        return new external_multiple_structure(new external_single_structure(array(

        'sectionid' => new external_value(PARAM_INT, 'id of section'),'sectionname' => new external_value(PARAM_RAW, 'name of section'))));
    }
    public static function add_item_to_course_returns() {
        return new external_single_structure(array(

        'courseid' => new external_value(PARAM_INT, 'id of course'),'coursename' => new external_value(PARAM_RAW, 'name of course'),'sectionid' => new external_value(PARAM_INT, 'id of section'),'sectionname' => new external_value(PARAM_RAW, 'name of section')));
    }
    public static function test_connection_returns() {
        return new external_single_structure(array(

        'success' => new external_value(PARAM_RAW, 'success')));
    }
    public static function edit_item_returns() {
        return new external_single_structure(array(

        'success' => new external_value(PARAM_BOOL, 'success')));
    }
    public static function move_item_returns() {
        return new external_single_structure(array(

        'success' => new external_value(PARAM_BOOL, 'success')));
    }
    public static function delete_item_returns() {
        return new external_single_structure(array(

        'success' => new external_value(PARAM_BOOL, 'success')));
    }
    public static function get_course_code_returns() {
        return new external_single_structure(array(

        'coursecode' => new external_value(PARAM_RAW, 'Course code')));
    }
    public static function list_courses_for_user($user, $modifiable, $archived) {
        global $DB, $CFG;

        $result = array();
        $params = self::validate_parameters(self::list_courses_for_user_parameters(), array('user' => $user,'modifiable' => $modifiable,'archived' => $archived));

        if ($modifiable) {
            $userobj = self::get_user($params['user']);
        } else {
            $userobj = null;
        }
        $coursefields = " c.id,c.fullname,c.visible ";
        $contextfields = " ctx.id AS contextid,ctx.contextlevel,ctx.instanceid,ctx.path,ctx.depth ";
        $sql = "SELECT $coursefields,$contextfields FROM {context} ctx
                                     JOIN {course} c
                                          ON c.id=ctx.instanceid
                                    WHERE ctx.contextlevel=? ";

        $courses = $DB->get_recordset_sql($sql, array(CONTEXT_COURSE));
        foreach($courses as $course) {
            // Ignore site level course
            if ($course->id == SITEID) {
                continue;
            }

            if ($modifiable) {
                $contextrecord = new stdclass();
                $contextrecord->id = $course->contextid;
                $contextrecord->contextlevel = CONTEXT_COURSE;
                $contextrecord->instanceid = $course->id;
                $contextrecord->path = $course->path;
                $contextrecord->depth = $course->depth;
                $coursecontext = eq_context_course::get_from_record($contextrecord);
                try {
                    $canedit = has_capability(self::WRITE_PERMISSION, $coursecontext, $userobj);
                } catch(Exception $ex) {
                }
                if (!$canedit) {
                    continue;
                }
            }

            if ($archived || $course->visible) {
                $result[] = array('courseid' => $course->id,'coursename' => $course->fullname,'archived' => !($course->visible));
            }
        }

        return $result;
    }
    public static function list_sections_for_course($user, $courseid) {
        global $DB;

        $params = self::validate_parameters(self::list_sections_for_course_parameters(), array('user' => $user,'courseid' => $courseid));

        self::check_modify_permissions($params['user'], $params['courseid']);

        $courseid = $params['courseid'];

        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $sections = $DB->get_records('course_sections', array('course' => $courseid), 'section', 'section, id, course, name, summary, summaryformat, sequence, visible');

        $result = array();
        foreach($sections as $section) {
            $sectionname = get_section_name($course, $section);
            $result[] = array('sectionid' => $section->section,'sectionname' => $sectionname);
        }

        return $result;
    }
    public static function find_usage_for_item($user, $uuid, $version, $isLatest, $archived, $allVersion) {
        global $DB;

        $params = self::validate_parameters(self::find_usage_for_item_parameters(), array('user' => $user,'uuid' => $uuid,'version' => $version,'isLatest' => $isLatest,'archived' => $archived,'allVersion' => $allVersion));

        $eqfields = "e.id AS eqid,e.name AS eqname, e.intro AS eqintro,e.uuid,e.path,e.attachmentuuid,e.version,e.activation,e.mimetype,e.timecreated,e.timemodified";
        $coursefields = "c.id,c.id AS courseid, c.shortname,c.fullname,c.idnumber,c.visible AS coursevisible,c.format";
        $cmfields = "cm.section AS section,cm.visible AS cmvisible,cm.id AS cmid";
        $sectionfields = "cs.name,cs.section,cs.id AS sectionid";

        $sql = "SELECT $coursefields,$cmfields,$sectionfields,$eqfields
                  FROM {equella} e
                       LEFT JOIN {course} c ON c.id=e.course
                       INNER JOIN {course_modules} cm ON cm.instance=e.id
                       INNER JOIN {course_sections} cs ON cs.id=cm.section AND cs.course=c.id
                       INNER JOIN {modules} md ON md.id=cm.module ";

        if ($params['allVersion']) {
            $sqlparams = array($uuid);
            $sql .= " WHERE e.uuid = ? AND c.id IS NOT NULL
                  ORDER BY e.timecreated DESC";
            $equellaitems = $DB->get_recordset_sql($sql, $sqlparams);
        } else if ($params['isLatest']) {
            list($insql, $inparams) = $DB->get_in_or_equal(array(0,$version));
            $sql .= " WHERE e.version $insql AND e.uuid = ? AND c.id IS NOT NULL
                  ORDER BY e.timecreated DESC";
            $inparams[] = $uuid;
            $equellaitems = $DB->get_recordset_sql($sql, $inparams);
        } else {
            $sqlparams = array($uuid,$version);
            $sql .= " WHERE e.uuid = ? AND e.version = ?
                  ORDER BY e.timecreated DESC";
            $equellaitems = $DB->get_recordset_sql('equella', $sqlparams);
        }

        $results = array();
        foreach($equellaitems as $item) {
            $courseid = $item->courseid;
            if (!$params['archived'] && (!$item->coursevisible || !$item->cmvisible)) {
                continue;
            }
            $results[] = self::build_item($item, $params['archived']);
        }

        return array('results' => $results);
    }
    private static function convert_item($item, &$itemViews, $course, $courseModule, $archived, $instructor = '', $enrollments = 0) {
        global $DB;
        static $sectionsMap = array();
        if (isset($sectionsMap[$courseModule->section])) {
            $section = $sectionsMap[$section->id];
            $section_name = $section->sectionname;
        } else {
            $section = $DB->get_record('course_sections', array(

            'course' => $courseModule->course,'id' => $courseModule->section), '*', MUST_EXIST);
            $section_name = get_section_name($course, $section);
            $section->sectionname = $section_name;
            $sectionsMap[$section->id] = $section;
        }
        if (!array_key_exists($course->id, $itemViews)) {
            $sql = "SELECT cm.id, COUNT('x') AS numviews, MAX(time) AS lasttime
                      FROM {course_modules} cm
                           JOIN {modules} m ON m.id = cm.module
                           JOIN {log} l ON l.cmid = cm.id
                     WHERE cm.course = ? AND l.action LIKE 'view%' AND m.visible = 1 GROUP BY cm.id";
            $itemViewInfo = $DB->get_records_sql($sql, array($course->id));

            $itemViews[$course->id] = $itemViewInfo;
        } else {
            $itemViewInfo = $itemViews[$course->id];
        }

        $attributes = array();

        $visible = ($course->visible && $courseModule->visible);

        if (!array_key_exists($courseModule->id, $itemViewInfo)) {
            $views = "0";
            $dateAccessed = null;
        } else {
            $views = $itemViewInfo[$courseModule->id]->numviews;
            $dateAccessed = $itemViewInfo[$courseModule->id]->lasttime * 1000;
        }
        $attributes[] = array('key' => 'views','value' => $views);

        return array(

            'id' => $item->id,
            'coursename' => $course->fullname,
            'courseid' => $course->id,
            'section' => $section_name,
            'sectionid' => $section->section,
            'dateAdded' => $item->timecreated * 1000,
            'dateModified' => $item->timemodified * 1000,
            'uuid' => $item->uuid,
            'version' => $item->version,
            'attributes' => $attributes,
            'attachment' => $item->path,
            'attachmentUuid' => $item->attachmentuuid,
            'moodlename' => $item->name,
            'moodledescription' => strip_tags($item->intro),
            'coursecode' => $course->idnumber,
            'instructor' => $instructor,
            'dateAccessed' => $dateAccessed,
            'enrollments' => $enrollments,
            'visible' => $visible);
    }
    public static function find_all_usage($user, $query, $courseid, $sectionid, $archived, $offset, $count, $sortcolumn, $sortasc) {
        global $DB, $CFG;

        $params = self::validate_parameters(self::find_all_usage_parameters(), array(

        'user' => $user,'query' => $query,'courseid' => $courseid,'sectionid' => $sectionid,'archived' => $archived,'offset' => $offset,'count' => $count,'sortcolumn' => $sortcolumn,'sortasc' => $sortasc));
        equella_debug_log("find_all_usage($user, $query, $courseid, $sectionid, $archived, $offset, $count)");

        $equella = $DB->get_record('modules', array('name' => 'equella'), '*', MUST_EXIST);
        $sortcol = $params['sortcolumn'];
        if (empty($sortcol)) {
            $sortcol = 'timecreated';
        } else if ($sortcol == 'course') {
            $sortcol = 'coursename';
        } else if ($sortcol == 'name' || $sortcol == 'timecreated') {
            // all good
        } else {
            $sortcol = 'timecreated';
        }
        $sortord = $params['sortasc'] ? 'ASC' : 'DESC';

        $args = array($equella->id,'%' . $params['query'] . '%');

        // compose 2 sql statements, one to fetch the requested records in a recordeset ...
        $sqlselect = 'SELECT e.id AS id, c.id AS course, c.visible AS coursevisible,
                       c.fullname AS coursename, e.name AS name,
                       m.visible AS cmvisible, m.section as section,
                       e.timecreated AS timecreated, e.timemodified AS timemodified,
                       e.uuid AS uuid, e.version AS version, e.path AS path, e.intro as intro, e.attachmentuuid as attachmentuuid ';

        // and a simple SELECT COUNT query to get the total available
        $sqlcount = 'SELECT COUNT(*) AS avail_count ';

        $sqlfrom = ' FROM {equella} e
                       INNER JOIN {course} c ON e.course = c.id
                       INNER JOIN {course_modules} m ON m.instance = e.id AND m.module = ?
                 WHERE LOWER(e.name) LIKE LOWER(?)';
        if (!empty($params['courseid'])) {
            $sqlfrom .= ' AND c.id = ? ';
            $args[] = $params['courseid'];
        }
        if (!empty($params['sectionid'])) {
            $sqlfrom .= ' AND m.section = ? ';
            $args[] = $params['sectionid'];
        }
        if (empty($params['archived'])) {
            $sqlfrom .= ' AND (c.visible = ? AND m.visible = ?) ';
            $args[] = 1;
            $args[] = 1;
        }
        $sqlselect = $sqlselect . $sqlfrom . ' ORDER BY ' . $sortcol . ' ' . $sortord;
        $equella_items = $DB->get_recordset_sql($sqlselect, $args, $offset, $count);

        $sqlcount = $sqlcount . $sqlfrom;

        $avail_items = $DB->count_records_sql($sqlcount, $args);

        $content = array();

        $itemViews = array();
        $courseMap = array();
        $enrollmentsMap = array();

        foreach($equella_items as $item) {
            if (!array_key_exists($item->course, $courseMap)) {
                $course = $DB->get_record('course', array('id' => $item->course), '*', MUST_EXIST);
                $courseMap[$item->course] = $course;
            } else {
                $course = $courseMap[$item->course];
            }

            $instructor = self::get_instructors($item->course);

            if (!isset($enrollmentsMap[$item->course])) {
                $enrolledusers = core_enrol_external::get_enrolled_users($item->course);
                $enrollmentsMap[$item->course] = count($enrolledusers);
            }
            $enrollments = $enrollmentsMap[$item->course];

            $courseModule = new stdClass();
            $courseModule->course = $item->course;
            $courseModule->section = $item->section;

            $content[] = self::convert_item($item, $itemViews, $course, $courseModule, $params['archived'], $instructor, $enrollments);
        }

        return array(
            'available' => $avail_items,
            'results' => $content);
    }
    public static function unfiltered_usage_count($user, $query, $archived) {
        global $DB;
        $params = self::validate_parameters(self::unfiltered_usage_count_parameters(), array(

        'user' => $user,'query' => $query,'archived' => $archived));
        equella_debug_log("unfiltered_usage_count($user, $query, $archived)");

        $available = 0;
        $equella = $DB->get_record('modules', array(

        'name' => 'equella'), '*', MUST_EXIST);
        $sql = "SELECT e.id, e.course AS course FROM {equella} e WHERE LOWER(e.name) LIKE LOWER(?)";
        $items = $DB->get_records_sql($sql, array('%' . $params['query'] . '%'));

        /*
         * foreach ($items as $item) { $courseModule = $DB->get_record('course_modules', array('module' => $equella->id, 'instance' => $item->id), '*', MUST_EXIST); if (!$params['archived'] && (!$course->visible || !$courseModule->visible)) { continue; } $available++; }
         */

        $result = array('available' => count($items));
        return $result;
    }
    public static function add_item_to_course($user, $courseid, $sectionid, $itemUuid, $itemVersion, $url, $title, $description, $attachmentUuid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::add_item_to_course_parameters(), array(

        'user' => $user,'courseid' => $courseid,'sectionid' => $sectionid,'itemUuid' => $itemUuid,'itemVersion' => $itemVersion,'url' => $url,'title' => $title,'description' => $description,'attachmentUuid' => $attachmentUuid));

        equella_debug_log("add_item_to_course($user, $courseid, $sectionid, $itemUuid, $itemVersion, $url, $title, $description, $attachmentUuid)");
        self::check_modify_permissions($params['user'], $params['courseid']);

        $module = $DB->get_record('modules', array('name' => 'equella'));

        $mod = new stdClass();
        $mod->course = $params['courseid'];
        $mod->module = $module->id;
        $mod->coursemodule = '';
        $mod->section = $params['sectionid'];
        $mod->modulename = 'equella';
        $mod->name = $params['title'];
        $mod->intro = $params['description'];
        $mod->introformat = FORMAT_HTML;
        $mod->url = $params['url'];
        $mod->uuid = $params['itemUuid'];
        $mod->version = $params['itemVersion'];
        $mod->attachmentuuid = $params['attachmentUuid'];
        $mod->instance = equella_add_instance($mod);

        $success = true;
        // course_modules and course_sections each contain a reference
        // to each other, so we have to update one of them twice.
        if (!$mod->coursemodule = add_course_module($mod)) {
            print_error('cannotaddcoursemodule');
            $success = false;
        }
        $modcontext = context_module::instance($mod->coursemodule);
        if (!$sectionid = add_mod_to_section($mod)) {
            print_error('cannotaddcoursemoduletosection');
            return null;
        }

        if (!$DB->set_field('course_modules', 'section', $sectionid, array(

        'id' => $mod->coursemodule))) {
            print_error("Could not update the course module with the correct section");
            return null;
        }

        set_coursemodule_visible($mod->coursemodule, true);

        $eventdata = new stdClass();
        $eventdata->modulename = $mod->modulename;
        $eventdata->name = $mod->name;
        $eventdata->cmid = $mod->coursemodule;
        $eventdata->courseid = $mod->course;
        $eventdata->userid = $USER->id;
        events_trigger('mod_created', $eventdata);

        add_to_log($mod->course, "course", "add mod", "../mod/$mod->modulename/view.php?id=$mod->coursemodule", "$mod->modulename $mod->instance");
        add_to_log($mod->course, $mod->modulename, "add equella resource", "view.php?id=$mod->coursemodule", "$mod->instance", $mod->coursemodule);

        rebuild_course_cache($mod->course);

        $section = $DB->get_record('course_sections', array(

        'course' => $courseid,'section' => $params['sectionid']));

        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $result = array(

        'courseid' => $courseid,'coursename' => $course->fullname,'sectionid' => $params['sectionid'],'sectionname' => get_section_name($course, $section));
        return $result;
    }
    public static function test_connection($param) {
        $params = self::validate_parameters(self::test_connection_parameters(), array('param' => $param));

        $result = array('success' => $params['param']);
        return $result;
    }
    public static function get_course_code($user, $courseid) {
        $params = self::validate_parameters(self::get_course_code_parameters(), array(

        'user' => $user,'courseid' => $courseid));

        $coursecode = equella_get_courseId($params['courseid']);

        $result = array('coursecode' => $coursecode);
        return $result;
    }
    public static function edit_item($user, $itemid, $title, $description) {
        global $DB;
        $params = self::validate_parameters(self::edit_item_parameters(), array(

        'user' => $user,'itemid' => $itemid,'title' => $title,'description' => $description));

        $item = $DB->get_record('equella', array('id' => $params['itemid']), '*', MUST_EXIST);
        self::check_modify_permissions($params['user'], $item->course);

        $equella = $DB->get_record('modules', array('name' => 'equella'));
        $courseModule = $DB->get_record('course_modules', array(

        'module' => $equella->id,'instance' => $item->id), '*', MUST_EXIST);

        $item->name = $params['title'];
        $item->intro = $params['description'];
        $item->instance = $courseModule->instance;

        $success = equella_update_instance($item);

        $eventdata = new stdClass();
        $eventdata->modulename = 'equella';
        $eventdata->name = $item->name;
        $eventdata->cmid = $courseModule->instance;
        $eventdata->courseid = $item->course;
        $eventdata->userid = $USER->id;
        events_trigger('mod_updated', $eventdata);

        add_to_log($item->course, "course", "update mod", "../mod/equella/view.php?id=$courseModule->id", "equella $item->instance");
        add_to_log($item->course, "equella", "update equella resource", "view.php?id=$courseModule->id", "$item->instance", $courseModule->id);

        rebuild_course_cache($item->course);
        $result = array('success' => $success);
        return $result;
    }
    public static function move_item($user, $itemid, $courseid, $locationid) {
        global $DB;
        global $USER;
        $params = self::validate_parameters(self::move_item_parameters(), array(

        'user' => $user,'itemid' => $itemid,'courseid' => $courseid,'locationid' => $locationid));

        $item = $DB->get_record('equella', array('id' => $params['itemid']), '*', MUST_EXIST);
        self::check_modify_permissions($params['user'], $item->course);
        self::check_modify_permissions($params['user'], $params['courseid']);

        $equella = $DB->get_record('modules', array('name' => 'equella'));
        $courseModule = $DB->get_record('course_modules', array(

        'module' => $equella->id,'instance' => $item->id), '*', MUST_EXIST);

        $oldCourse = $courseModule->course;
        $newCourse = $params['courseid'];

        $newSection = $DB->get_record('course_sections', array(

        'course' => $newCourse,'section' => $params['locationid']), '*', MUST_EXIST);

        delete_mod_from_section($courseModule->id, $courseModule->section);

        $courseModule->section = $newSection->id;
        $courseModule->course = $newCourse;
        $item->course = $newCourse;
        $item->section = $newSection->section;
        $item->instance = $courseModule->instance;
        $item->coursemodule = $courseModule->id;

        $success = $DB->update_record("course_modules", $courseModule);

        if ($success) {
            $success = equella_update_instance($item);

            if (!$sectionid = add_mod_to_section($item)) {
                print_error('cannotaddcoursemoduletosection');
                return null;
            }

            $eventdata = new stdClass();
            $eventdata->modulename = 'equella';
            $eventdata->name = $item->name;
            $eventdata->cmid = $courseModule->instance;
            $eventdata->courseid = $item->course;
            $eventdata->userid = $USER->id;
            events_trigger('mod_updated', $eventdata);

            add_to_log($item->course, "course", "update mod", "../mod/equella/view.php?id=$courseModule->id", "equella $item->instance");
            add_to_log($item->course, "equella", "update equella resource", "view.php?id=$courseModule->id", "$item->instance", $courseModule->id);

            rebuild_course_cache($oldCourse);
            rebuild_course_cache($newCourse);
        }
        $result = array(

        'success' => $success);
        return $result;
    }
    public static function delete_item($user, $itemid) {
        global $DB, $USER;
        $params = self::validate_parameters(self::delete_item_parameters(), array(

        'user' => $user,'itemid' => $itemid));

        $item = $DB->get_record('equella', array('id' => $params['itemid']), '*', MUST_EXIST);
        self::check_modify_permissions($params['user'], $item->course);

        $equella = $DB->get_record('modules', array('name' => 'equella'));
        $courseModule = $DB->get_record('course_modules', array(

        'module' => $equella->id,'instance' => $item->id), '*', MUST_EXIST);

        $success = equella_delete_instance($params['itemid']);

        if ($success) {
            if (!delete_course_module($courseModule->id)) {
                print_error('deletednot', '', '', "the {$courseModule->modname} (coursemodule)");
                $success = false;
            }

            if (!delete_mod_from_section($courseModule->id, $courseModule->section)) {
                print_error('deletednot', '', '', "the {$courseModule->modname} from that section");
                $success = false;
            }

            $eventdata = new stdClass();
            $eventdata->modulename = 'equella';
            $eventdata->cmid = $courseModule->instance;
            $eventdata->courseid = $item->course;
            $eventdata->userid = $USER->id;
            events_trigger('mod_delete', $eventdata);

            add_to_log($item->course, "course", "delete mod", "view.php?id=$courseModule->course", "equella $courseModule->instance", $courseModule->id);

            rebuild_course_cache($item->course);
        }
        $result = array('success' => $success);
        return $result;
    }
    public static function get_user($username) {
        global $CFG;

        $user = get_complete_user_data('username', $username, $CFG->mnet_localhost_id);

        if ($user == null) {
            throw new moodle_exception("UserNotFound/" . $username);
        }
        return $user;
    }
    public static function is_enrolled($user, $courseid) {
        equella_debug_log("is_enrolled($user->id, $courseid)");
        $coursecontext = context_course::instance($courseid);
        return is_enrolled($coursecontext, $user->id);
    }
    public static function has_view_permissions($user, $courseid) {
        $coursecontext = context_course::instance($courseid);
        return has_capability(self::READ_PERMISSION, $coursecontext, $user->id);
    }
    public static function has_modify_permissions($user, $courseid) {
        $coursecontext = context_course::instance($courseid);
        return has_capability(self::WRITE_PERMISSION, $coursecontext, $user->id);
    }
    public static function check_modify_permissions($username, $courseid) {
        $user = self::get_user($username);
        $coursecontext = context_course::instance($courseid);

        require_capability(self::WRITE_PERMISSION, $coursecontext, $user->id);
    }
    static private function get_section_name($item) {
        if (isset(self::$coursesections[$item->sectionid])) {
            $section = self::$coursesections[$item->sectionid];
            $section_name = $section->sectionname;
        } else {
            $section = new stdclass();
            $section->course = $item->courseid;
            $section->section = $item->section;
            $section->id = $item->sectionid;
            $section_name = get_section_name($item, $section);
            $section->sectionname = $section_name;
            self::$coursesections[$item->sectionid] = $section;
        }
        return $section_name;
    }
    static private function get_item_views($courseid) {
        global $DB;
        static $itemViews = array();
        if (!isset($itemViews[$courseid])) {
            $sql = "SELECT cm.id, COUNT('x') AS numviews, MAX(time) AS lasttime
                      FROM {course_modules} cm
                           JOIN {modules} m ON m.id = cm.module
                           JOIN {log} l ON l.cmid = cm.id
                     WHERE cm.course = ? AND l.action LIKE 'view%' AND m.visible = 1 GROUP BY cm.id";
            $itemViewInfo = $DB->get_records_sql($sql, array($courseid));

            $itemViews[$courseid] = $itemViewInfo;
        } else {
            $itemViewInfo = $itemViews[$courseid];
        }

        return $itemViewInfo;
    }
    static private function build_item($item, $archived) {
        global $DB;
        $attributes = array();

        $cmid = $item->cmid;
        $visible = ($item->coursevisible && $item->cmvisible);

        $itemViewInfo = self::get_item_views($item->courseid);
        if (!array_key_exists($cmid, $itemViewInfo)) {
            $views = "0";
            $dateAccessed = null;
        } else {
            $views = $itemViewInfo[$cmid]->numviews;
            $dateAccessed = $itemViewInfo[$cmid]->lasttime * 1000;
        }
        $attributes[] = array('key' => 'views','value' => $views);

        return array(
            'id' => $item->eqid,
            'coursename' => $item->fullname,
            'courseid' => $item->courseid,
            'section' => self::get_section_name($item),
            'sectionid' => $item->section,
            'dateAdded' => $item->timecreated * 1000,
            'dateModified' => $item->timemodified * 1000,
            'uuid' => $item->uuid,
            'version' => $item->version,
            'attributes' => $attributes,
            'attachment' => $item->path,
            'attachmentUuid' => $item->attachmentuuid,
            'moodlename' => $item->eqname,
            'moodledescription' => strip_tags($item->eqintro),
            'coursecode' => $item->idnumber,
            'instructor' => self::get_instructors($item->courseid),
            'dateAccessed' => $dateAccessed,
            'enrollments' => self::count_enrolled_users($item->courseid),
            'visible' => $visible);
    }
    static private function count_enrolled_users($courseid) {
        global $DB;
        if (!isset(self::$enrollmentcount[$courseid])) {
            $coursecontext = context_course::instance($courseid, IGNORE_MISSING);
            if ($courseid == SITEID) {
                $context = context_system::instance();
            } else {
                $context = $coursecontext;
            }
            if ($courseid == SITEID) {
                require_capability('moodle/site:viewparticipants', $context);
            } else {
                require_capability('moodle/course:viewparticipants', $context);
            }
            list($enrolledsql, $enrolledparams) = get_enrolled_sql($coursecontext);
            $enrolledparams['contextlevel'] = CONTEXT_USER;
            $sql = "SELECT count(u.id)
                      FROM {user} u
                     WHERE u.id IN ($enrolledsql)";
            self::$enrollmentcount[$courseid] = $DB->count_records_sql($sql, $enrolledparams);
        }
        return self::$enrollmentcount[$courseid];
    }

    /**
     * Return course instructors
     *
     * @param int $courseid
     * @return string
     */
    static private function get_instructors($courseid) {
        global $DB;

        if (!isset(self::$instructors[$courseid])) {
            $sql = 'SELECT u.id, u.firstname, u.lastname
                      FROM {role_assignments} ra
                           INNER JOIN {context} c on ra.contextid = c.id
                           INNER JOIN {role} r on r.id = ra.roleid
                           INNER JOIN {user} u ON ra.userid = u.id
                     WHERE (r.shortname = ? OR r.shortname = ?) AND c.instanceid = ? AND c.contextlevel <= ?';
            $users = $DB->get_records_sql($sql, array('teacher','editingteacher',$courseid,CONTEXT_COURSE));
            $first = true;
            $return = '';
            foreach($users as $user) {
                if (!$first) {
                    $return .= ', ';
                }
                $return .= fullname($user);
                $first = false;
            }

            self::$instructors[$courseid] = $return;
        }

        return self::$instructors[$courseid];
    }
}
