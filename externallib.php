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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->libdir . '/authlib.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/enrol/externallib.php');

require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');

class equella_external extends external_api {

    const READ_PERMISSION = 'moodle/course:view';
    const WRITE_PERMISSION = 'moodle/course:manageactivities';
    const DEVMODE = 0; //DO-NOT-COMMI

    public static function find_usage_for_item_parameters() {
        return new external_function_parameters(
            array(
                'user'       => new external_value(PARAM_RAW, 'Username'),
                'uuid'       => new external_value(PARAM_RAW, 'Item UUID'),
                'version'       => new external_value(PARAM_INT, 'Item version'),
                'isLatest'       => new external_value(PARAM_BOOL, 'The supplied version param is the latest version of this item'),
                'archived'       => new external_value(PARAM_BOOL, 'Include hidden items and courses'),
                'allVersion'       => new external_value(PARAM_BOOL, 'Show all versions of this item'),
            )
        );
    }

    public static function find_all_usage_parameters() {
        return new external_function_parameters(
            array(
                'user'       => new external_value(PARAM_RAW, 'Username'),
                'query'       => new external_value(PARAM_RAW, 'Freetext query'),
                'courseid'       => new external_value(PARAM_INT, 'Course ID'),
                'sectionid'       => new external_value(PARAM_INT, 'Section ID'),
                'archived'       => new external_value(PARAM_BOOL, 'Include hidden items and courses'),
                'offset'       => new external_value(PARAM_INT, 'Results paging'),
                'count'       => new external_value(PARAM_INT, 'Results paging'),
                'sortcolumn'       => new external_value(PARAM_RAW, 'The name of the sort column: name, course or timecreated (default is timecreated)'),
                'sortasc'       => new external_value(PARAM_BOOL, 'Sort ascending'),
            )
        );
    }

    public static function unfiltered_usage_count_parameters() {
        return new external_function_parameters(
            array(
                'user'       => new external_value(PARAM_RAW, 'Username'),
                'query'       => new external_value(PARAM_RAW, 'Freetext query'),
                'archived'       => new external_value(PARAM_BOOL, 'Include hidden items and courses'),
            )
        );
    }

    public static function list_courses_for_user_parameters() {
        return new external_function_parameters(
            array(
                'user'       => new external_value(PARAM_RAW, 'Username'),
                'modifiable' => new external_value(PARAM_BOOL, 'Only return courses user can add content to'),
                'archived' => new external_value(PARAM_BOOL, 'Show hidden courses as well'),
            )
        );
    }

    public static function get_course_code_parameters() {
        return new external_function_parameters(
            array(
                'user'       => new external_value(PARAM_RAW, 'Username'),
                'courseid'       => new external_value(PARAM_RAW, 'Course id'),
            )
        );
    }


    public static function list_sections_for_course_parameters() {
        return new external_function_parameters(
            array(
                'user'       => new external_value(PARAM_RAW, 'Username'),
                'courseid'       => new external_value(PARAM_RAW, 'Course ID')
            )
        );
    }

    public static function edit_item_parameters() {
        return new external_function_parameters(
            array(
                'user'       => new external_value(PARAM_RAW, 'Username'),
                'itemid'       => new external_value(PARAM_RAW, 'Item ID'),
                'title'       => new external_value(PARAM_RAW, 'Title'),
                'description'       => new external_value(PARAM_RAW, 'Description')
            )
        );
    }

    public static function move_item_parameters() {
        return new external_function_parameters(
            array(
                'user'       => new external_value(PARAM_RAW, 'Username'),
                'itemid'       => new external_value(PARAM_RAW, 'Item ID'),
                'courseid'       => new external_value(PARAM_RAW, 'Course ID'),
                'locationid'       => new external_value(PARAM_RAW, 'Location ID')
            )
        );
    }

    public static function delete_item_parameters() {
        return new external_function_parameters(
            array(
                'user'       => new external_value(PARAM_RAW, 'Username'),
                'itemid'       => new external_value(PARAM_RAW, 'Item ID')
            )
        );
    }

    public static function add_item_to_course_parameters() {
        return new external_function_parameters(
            array(
                'user'       => new external_value(PARAM_RAW, 'Username'),
                'courseid'       => new external_value(PARAM_RAW, 'Course ID'),
                'sectionid'       => new external_value(PARAM_RAW, 'Section ID'),
                'itemUuid'     => new external_value(PARAM_RAW, 'Item UUID'),
                'itemVersion'  => new external_value(PARAM_INT, 'Item Version'),
                'url'       => new external_value(PARAM_RAW, 'URL'),
                'title'       => new external_value(PARAM_RAW, 'Title'),
                'description'       => new external_value(PARAM_RAW, 'Description'),
                'attachmentUuid'       => new external_value(PARAM_RAW, 'Attachment UUID')
            )
        );
    }

    public static function test_connection_parameters() {
        return new external_function_parameters(
            array(
                'param'       => new external_value(PARAM_RAW, 'Parameter to echo back')
            )
        );
    }

        /*
         $RESULTS_STRUCTURE = new external_multiple_structure(
        new external_single_structure(
        array(
        'id' => new external_value(PARAM_RAW, 'id of content'),
        'coursename' => new external_value(PARAM_RAW, 'name of course'),
        'courseid' => new external_value(PARAM_RAW, 'id of the course'),
        'section' => new external_value(PARAM_RAW, 'location of resource'),
        'dateAdded' => new external_value(PARAM_FLOAT, 'Date the item was added'),
        'dateModified' => new external_value(PARAM_FLOAT, 'Date the item details were modified in Moodle'),
        'uuid' => new external_value(PARAM_RAW, 'The uuid of the item link to.'),
        'version' => new external_value(PARAM_INT, 'The version of the item linked to.  Will be zero in the case of "Always latest"'),
        'attachment' => new external_value(PARAM_RAW, 'The attachment name, if any, that is linked to.'),
        'moodlename' => new external_value(PARAM_RAW, 'The name of the resource in Moodle'),
        'moodledescription' => new external_value(PARAM_RAW, 'The description of the resource in Moodle'),
        'attributes' => new external_multiple_structure(new external_single_structure(
        array(
        'key' => new external_value(PARAM_RAW, 'Attribute key'),
        'value' => new external_value(PARAM_RAW, 'Attribute value')
        )), '', false
        )
        )
        )
        );*/

    public static function find_usage_for_item_returns() {
        return new external_single_structure(
            array(
                'results' => new external_multiple_structure(
                    new external_single_structure(
                        array(
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
                            'attributes' => new external_multiple_structure(new external_single_structure(
                                array(
                                    'key' => new external_value(PARAM_RAW, 'Attribute key'),
                                    'value' => new external_value(PARAM_RAW, 'Attribute value')
                                )), '', false
                            )
                        )
                    )
                )
            )
        );
    }

    public static function find_all_usage_returns() {
        return new external_single_structure(
            array(
                'available' => new external_value(PARAM_INT, 'Number of results available'),
                'results' => new external_multiple_structure(
                    new external_single_structure(
                        array(
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
                            'attributes' => new external_multiple_structure(new external_single_structure(
                                array(
                                    'key' => new external_value(PARAM_RAW, 'Attribute key'),
                                    'value' => new external_value(PARAM_RAW, 'Attribute value')
                                )), '', false
                            )
                        )
                    )
                )
            )
        );
    }

    public static function unfiltered_usage_count_returns() {
        return new external_single_structure(
            array(
                'available' => new external_value(PARAM_INT, 'Number of results available')
            ));
    }

    public static function list_courses_for_user_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'courseid' => new external_value(PARAM_INT, 'id of course'),
                    'coursename' => new external_value(PARAM_RAW, 'name of course'),
                    'archived' => new external_value(PARAM_BOOL, 'visibility of course')
                )
            )
        );
    }

    public static function list_sections_for_course_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'sectionid' => new external_value(PARAM_INT, 'id of section'),
                    'sectionname' => new external_value(PARAM_RAW, 'name of section')
                )
            )
        );
    }

    public static function add_item_to_course_returns()
    {
        return new external_single_structure(
            array(
                'courseid'  => new external_value(PARAM_INT, 'id of course'),
                'coursename' => new external_value(PARAM_RAW, 'name of course'),
                'sectionid' => new external_value(PARAM_INT, 'id of section'),
                'sectionname' => new external_value(PARAM_RAW, 'name of section')
            ));
    }

    public static function test_connection_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_RAW, 'success'),
            )
        );
    }

    public static function edit_item_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'success'),
            )
        );
    }

    public static function move_item_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'success'),
            )
        );
    }

    public static function delete_item_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'success'),
            )
        );
    }


    public static function get_course_code_returns()
    {
        return new external_single_structure(
            array(
                'coursecode' => new external_value(PARAM_RAW, 'Course code'),
            )
        );
    }



    public static function list_courses_for_user($user, $modifiable, $archived) {
        global $DB, $CFG;
        $result = array();

        $params = self::validate_parameters(self::list_courses_for_user_parameters(),
            array(
                'user' => $user,
                'modifiable' => $modifiable,
                'archived' => $archived,
            ));

        equella_debug_log("list_courses_for_user($user, $modifiable, $archived)");

        if ($modifiable)
        {
            $userobj = self::get_user($params['user']);
        }
        else
        {
            $userobj = null;
        }

        $courses = $DB->get_recordset('course');
        foreach ($courses as $course)
        {
            // Ignore site level course
            if ($course->id == SITEID)
            {
                continue;
            }
            //Ugh
            if ($userobj != null && !self::has_modify_permissions($userobj, $course->id))
            {
                equella_debug_log("no modify permissions for course $course->fullname");
                continue;
            }

            if ($archived || $course->visible)
            {
                $result[] = array('courseid' => $course->id,
                    'coursename' => $course->fullname,
                    'archived' => !($course->visible)
                );
            }
        }

        return $result;
    }

    public static function list_sections_for_course($user, $courseid)
    {
        global $DB;

        $params = self::validate_parameters(self::list_sections_for_course_parameters(),
            array(
                'user' => $user,
                'courseid' => $courseid
            )
        );
        //FIXME: can list_sections_for_course ever be called for non-write purposes?
        self::check_modify_permissions($params['user'], $params['courseid']);

        $result = array();
        $sections = $DB->get_records('course_sections', array('course' => $params['courseid']),
            'section', 'section, id, course, name, summary, summaryformat, sequence, visible');
        foreach ($sections as $section)
        {
            $course = $DB->get_record('course', array('id' => $params['courseid']), '*', MUST_EXIST);
            $section_name = get_section_name($course, $section);
            $result[] = array('sectionid' => $section->section,
                'sectionname' => $section_name);
        }

        return $result;
    }

    public static function find_usage_for_item($user, $uuid, $version, $isLatest, $archived, $allVersion)
    {
        global $DB;

        $params = self::validate_parameters(self::find_usage_for_item_parameters(),
            array(
                'user' => $user,
                'uuid' => $uuid,
                'version' => $version,
                'isLatest' => $isLatest,
                'archived' => $archived,
                'allVersion' => $allVersion
            ));
        equella_debug_log("find_usage_for_item($user, $uuid, $version, $isLatest, $archived, $allVersion)");


        if ($params['allVersion'])
        {
            $equella_items = $DB->get_recordset('equella', array('uuid'=>$uuid), 'timecreated DESC');
        }
        else if ($params['isLatest'])
        {
            list($insql, $inparams) = $DB->get_in_or_equal(array(0, $version));
            $sql = "SELECT *
                      FROM {equella}
                     WHERE version $insql
                           AND uuid = ?
                  ORDER BY timecreated DESC";
            $inparams[] = $uuid;
            $equella_items = $DB->get_recordset_sql($sql, $inparams);
        }
        else
        {
            $equella_items = $DB->get_recordset('equella', array('uuid'=>$uuid, 'version'=>$version));
        }

        $content = array();
        $itemViews = array();
        $coursecaches = array();
        $enrollmentsMap = array();
        foreach ($equella_items as $item)
        {
            $cm = get_coursemodule_from_instance('equella', $item->id);
            if (!empty($coursecaches[$cm->course])) {
                $course = $coursecaches[$cm->course];
            } else {
                $course = $DB->get_record("course", array("id" => $cm->course));
                $coursecaches[$cm->course] = $course;
            }

            if (!isset($enrollmentsMap[$item->course])) {
                $enrolledusers = core_enrol_external::get_enrolled_users($item->course);
                $enrollmentsMap[$item->course] = count($enrolledusers);
            }
            $enrollments = $enrollmentsMap[$item->course];

            $instructor = self::get_instructor($item->course, $instructorMap);

            if (!$params['archived'] && (!$course->visible || !$cm->visible))
            {
                continue;
            }

            $content[] = self::convert_item($item, $itemViews, $course, $cm, $params['archived'], $instructor, $enrollments);
            // reset course
            $course = null;
        }

        return array('results' => $content);
    }


    public static function find_all_usage($user, $query, $courseid, $sectionid, $archived, $offset, $count, $sortcolumn, $sortasc)
    {
        global $DB, $CFG;

        $params = self::validate_parameters(self::find_all_usage_parameters(),
            array(
                'user' => $user,
                'query' => $query,
                'courseid' => $courseid,
                'sectionid' => $sectionid,
                'archived' => $archived,
                'offset' => $offset,
                'count' => $count,
                'sortcolumn' => $sortcolumn,
                'sortasc' => $sortasc
            ));
        equella_debug_log("find_all_usage($user, $query, $courseid, $sectionid, $archived, $offset, $count)");

        $equella = $DB->get_record('modules', array('name' => 'equella'), '*', MUST_EXIST);

        $sortcol = $params['sortcolumn'];
        if (empty($sortcol))
        {
            $sortcol = 'timecreated';
        }
        else if ($sortcol == 'course')
        {
            $sortcol = 'coursename';
        }
        else if ($sortcol == 'name' || $sortcol == 'timecreated')
        {
            //all good
        }
        else
        {
            $sortcol = 'timecreated';
        }
        $sortord = $params['sortasc'] ? 'ASC' : 'DESC';

        $args = array($equella->id, '%'.$params['query'].'%');
        $sql = 'SELECT
            e.id AS id, c.id AS course, c.fullname AS coursename, e.name AS name,
            e.timecreated AS timecreated, e.timemodified AS timemodified,
            e.uuid AS uuid, e.version AS version, e.path AS path, e.intro as intro, e.attachmentuuid as attachmentuuid
            FROM {equella} e
            INNER JOIN {course} c ON e.course = c.id
            INNER JOIN {course_modules} m ON m.instance = e.id and m.module = ?
            WHERE LOWER(e.name) like LOWER(?)';
        if (!empty($params['courseid']))
        {
            $sql = $sql . 'AND c.id = ?';
            $args[]=$params['courseid'];
        }
        if (!empty($params['sectionid']))
        {
            $sql = $sql . 'AND m.section = ?';
            $args[]=$params['sectionid'];
        }
        $sql = $sql . 'ORDER BY '.$sortcol.' '.$sortord;


        $equella_items = $DB->get_records_sql($sql, $args);

        $index = 0;
        $content = array();

        $itemViews = array();
        $courseMap = array();
        $instructorMap = array();
        $enrollmentsMap = array();

        foreach ($equella_items as $item)
        {
            if (!array_key_exists($item->course, $courseMap))
            {
                $course = $DB->get_record('course', array('id' => $item->course), '*', MUST_EXIST);
                $courseMap[$item->course] = $course;
            }
            else
            {
                $course = $courseMap[$item->course];
            }

            $instructor = self::get_instructor($item->course, $instructorMap);

            if (!isset($enrollmentsMap[$item->course])) {
                $enrolledusers = core_enrol_external::get_enrolled_users($item->course);
                $enrollmentsMap[$item->course] = count($enrolledusers);
            }
            $enrollments = $enrollmentsMap[$item->course];

            $courseModule = $DB->get_record('course_modules', array('module' => $equella->id, 'instance' => $item->id), '*', MUST_EXIST);
            if (!$params['archived'] && (!$course->visible || !$courseModule->visible))
            {
                continue;
            }

            if ($index >= $offset && ($count == -1 || $index < $offset + $count))
            {
                $content[] = self::convert_item($item, $itemViews, $course, $courseModule, $params['archived'], $instructor, $enrollments);
            }
            $index = $index + 1;
        }



        return array('available' => $index, 'results' => $content);
    }

    private static function convert_item($item, &$itemViews, $course, $courseModule, $archived, $instructor='', $enrollments=0)
    {
        global $DB;
        $section = $DB->get_record('course_sections', array('course' => $courseModule->course, 'id' => $courseModule->section), '*', MUST_EXIST);
        $section_name = get_section_name($course, $section);

        if (!array_key_exists($course->id, $itemViews))
        {
            $sql = "SELECT cm.id, COUNT('x') AS numviews, MAX(time) AS lasttime
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {log} l     ON l.cmid = cm.id
                WHERE cm.course = ? AND l.action LIKE 'view%' AND m.visible = 1
                GROUP BY cm.id";
            $itemViewInfo = $DB->get_records_sql($sql, array($course->id));

            $itemViews[$course->id] = $itemViewInfo;
        }
        else
        {
            $itemViewInfo = $itemViews[$course->id];
        }

        $attributes = array();

        $visible = ($course->visible && $courseModule->visible);

        if (!array_key_exists($courseModule->id, $itemViewInfo))
        {
            $views = "0";
            $dateAccessed = null;
        }
        else
        {
            $views = $itemViewInfo[$courseModule->id]->numviews;
            $dateAccessed = $itemViewInfo[$courseModule->id]->lasttime*1000;
        }
        $attributes[] = array('key' => 'views', 'value' => $views);

        return array(
            'id' => $item->id,
            'coursename' => $course->fullname,
            'courseid' => $course->id,
            'section' => $section_name,
            'sectionid' => $section->section,
            'dateAdded' => $item->timecreated*1000,
            'dateModified' => $item->timemodified*1000,
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
            'visible' => $visible

        );
    }

    private static function get_instructor($courseid, &$instructorMap) {
        global $DB;

        if (!isset($instructorMap[$courseid])) {
            //TODO: by default the teacher and editingteacher roles can only be assigned as high up
            //as CONTEXT_COURSE.  If they have edited these inbuilt roles then this query will break
                        /*
                         select ra.id as raid, ra.userid, c.id as contextid, c.contextlevel, c.instanceid, c.path, c.depth, r.id as roleid, r.name, u.firstname, u.lastname
                        from mdl_role_assignments ra
                        inner join mdl_context c on ra.contextid = c.id
                        inner join mdl_role r on r.id = ra.roleid
                        inner join mdl_user u on ra.userid = u.id
                        where (r.shortname = 'teacher' or r.shortname = 'editingteacher') and c.contextlevel <= 50
                         */
            $sql = 'SELECT c.instanceid, u.firstname, u.lastname
                      FROM {role_assignments} ra
                           INNER JOIN {context} c on ra.contextid = c.id
                           INNER JOIN {role} r on r.id = ra.roleid
                           INNER JOIN {user} u ON ra.userid = u.id
                     WHERE (r.shortname = ? OR r.shortname = ?) AND c.instanceid = ? AND c.contextlevel <= ?';
            $instructors = $DB->get_records_sql($sql, array('teacher', 'editingteacher', $courseid, CONTEXT_COURSE));
            $instructor = '';
            $first = true;
            foreach ($instructors as $user)
            {
                if (!$first)
                {
                    $instructor = $instructor.', ';
                }
                $instructor = $instructor . fullname($user);
                $first = false;
            }

            $instructorMap[$courseid] = $instructor;
        }
        else
        {
            $instructor = $instructorMap[$courseid];
        }
        return $instructor;
    }

    public static function unfiltered_usage_count($user, $query, $archived)
    {
        global $DB;
        $params = self::validate_parameters(self::unfiltered_usage_count_parameters(),
            array(
                'user' => $user,
                'query' => $query,
                'archived' => $archived
            ));
        equella_debug_log("unfiltered_usage_count($user, $query, $archived)");

        $available = 0;
        $equella = $DB->get_record('modules', array('name' => 'equella'), '*', MUST_EXIST);
        $items = $DB->get_records_sql('SELECT e.id, e.course AS course
            FROM {equella} e
            WHERE LOWER(e.name) like LOWER(?)'
            , array('%'.$params['query'].'%'));

                /*
                foreach ($items as $item)
                {
                        $courseModule = $DB->get_record('course_modules', array('module' => $equella->id, 'instance' => $item->id), '*', MUST_EXIST);
                        if (!$params['archived'] && (!$course->visible || !$courseModule->visible))
                        {
                                continue;
                        }
                        $available++;
                }*/

        $result = array('available' => count($items));
        return $result;
    }

    public static function add_item_to_course($user, $courseid, $sectionid, $itemUuid, $itemVersion, $url, $title, $description, $attachmentUuid)
    {
        global $DB, $USER;

        $params = self::validate_parameters(self::add_item_to_course_parameters(),
            array(
                'user' => $user,
                'courseid' => $courseid,
                'sectionid' => $sectionid,
                'itemUuid' => $itemUuid,
                'itemVersion' => $itemVersion,
                'url' => $url,
                'title' => $title,
                'description' => $description,
                'attachmentUuid' => $attachmentUuid
            )
        );

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
        if (! $mod->coursemodule = add_course_module($mod) )
        {
            print_error('cannotaddcoursemodule');
            $success = false;
        }
        $modcontext = get_context_instance(CONTEXT_MODULE, $mod->coursemodule);
        if (! $sectionid = add_mod_to_section($mod) )
        {
            print_error('cannotaddcoursemoduletosection');
            return null;
        }

        if (! $DB->set_field('course_modules', 'section', $sectionid, array('id' => $mod->coursemodule)))
        {
            print_error("Could not update the course module with the correct section");
            return null;
        }

        set_coursemodule_visible($mod->coursemodule, true);

        $eventdata = new stdClass();
        $eventdata->modulename = $mod->modulename;
        $eventdata->name       = $mod->name;
        $eventdata->cmid       = $mod->coursemodule;
        $eventdata->courseid   = $mod->course;
        $eventdata->userid     = $USER->id;
        events_trigger('mod_created', $eventdata);

        add_to_log($mod->course, "course", "add mod",
            "../mod/$mod->modulename/view.php?id=$mod->coursemodule",
            "$mod->modulename $mod->instance");
        add_to_log($mod->course, $mod->modulename, "add",
            "view.php?id=$mod->coursemodule",
            "$mod->instance", $mod->coursemodule);

        rebuild_course_cache($mod->course);

        $section = $DB->get_record('course_sections', array('course' => $courseid, 'section' => $params['sectionid']));

        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $result = array('courseid' => $courseid, 'coursename' => $course->fullname,
            'sectionid' => $params['sectionid'], 'sectionname' => get_section_name($course, $section) );
        return $result;
    }

    public static function test_connection($param)
    {
        $params = self::validate_parameters(self::test_connection_parameters(),
            array(
                'param' => $param,
            ));

        $result = array('success' => $params['param']);
        return $result;
    }

    public static function get_course_code($user, $courseid)
    {
        $params = self::validate_parameters(self::get_course_code_parameters(),
            array(
                'user' => $user,
                'courseid' => $courseid
            ));

        $coursecode = equella_get_courseId($params['courseid']);

        $result = array('coursecode' => $coursecode);
        return $result;
    }

    public static function edit_item($user, $itemid, $title, $description)
    {
        global $DB;
        $params = self::validate_parameters(self::edit_item_parameters(),
            array(
                'user' => $user,
                'itemid' => $itemid,
                'title' => $title,
                'description' => $description
            ));

        $item = $DB->get_record('equella', array('id' => $params['itemid']), '*', MUST_EXIST);
        self::check_modify_permissions($params['user'], $item->course);

        $equella = $DB->get_record('modules', array('name' => 'equella'));
        $courseModule = $DB->get_record('course_modules', array('module' => $equella->id, 'instance' => $item->id), '*', MUST_EXIST);

        $item->name = $params['title'];
        $item->intro = $params['description'];
        $item->instance = $courseModule->instance;


        $success =  equella_update_instance($item);

        $eventdata = new stdClass();
        $eventdata->modulename = 'equella';
        $eventdata->name       = $item->name;
        $eventdata->cmid       = $courseModule->instance;
        $eventdata->courseid   = $item->course;
        $eventdata->userid     = $USER->id;
        events_trigger('mod_updated', $eventdata);

        add_to_log($item->course, "course", "update mod",
            "../mod/equella/view.php?id=$courseModule->id",
            "equella $item->instance");
        add_to_log($item->course, "equella", "update",
            "view.php?id=$courseModule->id",
            "$item->instance", $courseModule->id);

        rebuild_course_cache($item->course);
        $result = array('success' => $success);
        return $result;
    }

    public static function move_item($user, $itemid, $courseid, $locationid)
    {
        global $DB;
        global $USER;
        $params = self::validate_parameters(self::move_item_parameters(),
            array(
                'user' => $user,
                'itemid' => $itemid,
                'courseid' => $courseid,
                'locationid' => $locationid
            ));

        $item = $DB->get_record('equella', array('id' => $params['itemid']), '*', MUST_EXIST);
        self::check_modify_permissions($params['user'], $item->course);
        self::check_modify_permissions($params['user'], $params['courseid']);

        $equella = $DB->get_record('modules', array('name' => 'equella'));
        $courseModule = $DB->get_record('course_modules', array('module' => $equella->id, 'instance' => $item->id), '*', MUST_EXIST);

        $oldCourse = $courseModule->course;
        $newCourse = $params['courseid'];

        $newSection = $DB->get_record('course_sections', array('course' => $newCourse, 'section' => $params['locationid']), '*', MUST_EXIST);

        delete_mod_from_section($courseModule->id, $courseModule->section);



        $courseModule->section = $newSection->id;
        $courseModule->course = $newCourse;
        $item->course = $newCourse;
        $item->section = $newSection->section;
        $item->instance = $courseModule->instance;
        $item->coursemodule = $courseModule->id;


        $success = $DB->update_record("course_modules", $courseModule);

        if ($success)
        {
            $success = equella_update_instance($item);

            if (! $sectionid = add_mod_to_section($item) )
            {
                print_error('cannotaddcoursemoduletosection');
                return null;
            }

            $eventdata = new stdClass();
            $eventdata->modulename = 'equella';
            $eventdata->name       = $item->name;
            $eventdata->cmid       = $courseModule->instance;
            $eventdata->courseid   = $item->course;
            $eventdata->userid     = $USER->id;
            events_trigger('mod_updated', $eventdata);

            add_to_log($item->course, "course", "update mod",
                "../mod/equella/view.php?id=$courseModule->id",
                "equella $item->instance");
            add_to_log($item->course, "equella", "update",
                "view.php?id=$courseModule->id",
                "$item->instance", $courseModule->id);

            rebuild_course_cache($oldCourse);
            rebuild_course_cache($newCourse);
        }
        $result = array('success' => $success);
        return $result;
    }

    public static function delete_item($user, $itemid)
    {
        global $DB;
        global $USER;
        $params = self::validate_parameters(self::delete_item_parameters(),
            array(
                'user' => $user,
                'itemid' => $itemid,
            ));

        $item = $DB->get_record('equella', array('id' => $params['itemid']), '*', MUST_EXIST);
        self::check_modify_permissions($params['user'], $item->course);

        $equella = $DB->get_record('modules', array('name' => 'equella'));
        $courseModule = $DB->get_record('course_modules', array('module' => $equella->id, 'instance' => $item->id), '*', MUST_EXIST);

        $success = equella_delete_instance($params['itemid']);


        if ($success)
        {
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
            $eventdata->cmid       = $courseModule->instance;
            $eventdata->courseid   = $item->course;
            $eventdata->userid     = $USER->id;
            events_trigger('mod_delete', $eventdata);

            add_to_log($item->course, "course", "delete mod",
                "view.php?id=$courseModule->course",
                "equella $courseModule->instance", $courseModule->id);

            rebuild_course_cache($item->course);
        }
        $result = array('success' => $success);
        return $result;
    }

    public static function get_user($username)
    {
        global $CFG;

        $user = get_complete_user_data('username', $username, $CFG->mnet_localhost_id);

        if ($user == null)
        {
            throw new moodle_exception("UserNotFound/" . $username);
        }
        return $user;
    }

    public static function is_enrolled($user, $courseid)
    {
        equella_debug_log("is_enrolled($user->id, $courseid)");
        $coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);
        return is_enrolled($coursecontext, $user->id);
    }

    public static function has_view_permissions($user, $courseid)
    {
        $coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);
        return has_capability(self::READ_PERMISSION, $coursecontext, $user->id);
    }

    public static function has_modify_permissions($user, $courseid)
    {
        $coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);
        return has_capability(self::WRITE_PERMISSION, $coursecontext, $user->id);
    }

    public static function check_modify_permissions($username, $courseid)
    {
        $user = self::get_user($username);
        $coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);

        require_capability(self::WRITE_PERMISSION, $coursecontext, $user->id);
    }
}
