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
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'courseid' => new external_value(PARAM_INT, 'Course ID'),
                    'coursecode' => new external_value(PARAM_RAW, 'Course code'),
                    'coursename' => new external_value(PARAM_RAW, 'Course name'),
                    'archived' => new external_value(PARAM_BOOL, 'Visibility of course'),
                )
            )
        );
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
        return new external_single_structure(
            array(
                'coursecode' => new external_value(PARAM_RAW, 'Course code')
            )
        );
    }

    /**
     * List courses for given user
     *
     * @param string $username
     * @param boolean $modifiable
     * @param boolean $archived
     * @return array
     */
    public static function list_courses_for_user($username, $modifiable, $archived) {
        global $DB, $CFG;

        $courselist = array();
        $params = self::validate_parameters(self::list_courses_for_user_parameters(), array('user' => $username,'modifiable' => $modifiable,'archived' => $archived));

        if ($modifiable) {
            $userobj = self::get_user_by_username($params['user']);
        } else {
            $userobj = null;
        }
        $coursefields = "c.id,c.fullname,c.visible,c.idnumber";
        $contextfields = "ctx.id AS contextid,ctx.contextlevel,ctx.instanceid,ctx.path,ctx.depth";
        $sql = "SELECT $coursefields,$contextfields
                  FROM {context} ctx
                       JOIN {course} c ON c.id=ctx.instanceid
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
                if (!has_capability(self::WRITE_PERMISSION, $coursecontext, $userobj)) {
                    continue;
                }
            }

            if ($archived || $course->visible) {
                $courselist[] = array(
                    'courseid' => $course->id,
                    'coursecode' => $course->idnumber,
                    'coursename' => $course->fullname,
                    'archived' => !($course->visible)
                );
            }
        }
        return $courselist;
    }
    /**
     * List sections in given course
     *
     * @param string $username
     * @param int $courseid
     * @return array
     */
    public static function list_sections_for_course($username, $courseid) {
        global $DB;

        $params = self::validate_parameters(self::list_sections_for_course_parameters(), array('user' => $username,'courseid' => $courseid));

        self::check_modify_permissions($params['user'], $params['courseid']);

        $courseid = $params['courseid'];

        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $sections = $DB->get_records('course_sections', array('course' => $courseid), 'section', 'section, id, course, name, summary, summaryformat, sequence, visible');

        $result = array();
        foreach($sections as $section) {
            // this triggers context_modinfo->instance, it will rebuild modinfo
            $sectionname = get_section_name($course, $section);
            $result[] = array('sectionid' => $section->section,'sectionname' => $sectionname);
        }

        return $result;
    }

    /**
     *
     * @param string $username
     * @param string $uuid
     * @param int $version
     * @param boolean $isLatest
     * @param boolean $archived
     * @param boolean $allVersion
     * @return array
     */
    public static function find_usage_for_item($username, $uuid, $version, $isLatest, $archived, $allVersion) {
        global $DB;

        $params = self::validate_parameters(self::find_usage_for_item_parameters(), array('user' => $username,'uuid' => $uuid,'version' => $version,'isLatest' => $isLatest,'archived' => $archived,'allVersion' => $allVersion));

        $eqfields = "e.id AS eqid,e.name AS eqname, e.intro AS eqintro,e.uuid,e.path,e.attachmentuuid,e.version,e.activation,e.mimetype,e.timecreated,e.timemodified";
        $coursefields = "c.id,c.id AS courseid,c.idnumber,c.shortname,c.fullname,c.idnumber,c.visible AS coursevisible,c.format";
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
        } else if ($params['isLatest']) {
            list($insql, $sqlparams) = $DB->get_in_or_equal(array(0,$version));
            $sql .= " WHERE e.version $insql AND e.uuid = ? AND c.id IS NOT NULL
                  ORDER BY e.timecreated DESC";
            $sqlparams[] = $uuid;
        } else {
            $sqlparams = array($uuid,$version);
            $sql .= " WHERE e.uuid = ? AND e.version = ?
                  ORDER BY e.timecreated DESC";
        }
        try {
            $equellaitems = $DB->get_recordset_sql($sql, $sqlparams);
        } catch (Exception $ex) {
            throw new moodle_exception('webserviceerror', 'equella', '', $ex->error, $ex->debuginfo);
        }

        $results = array();
        $count = 0;
        foreach($equellaitems as $item) {
            $courseid = $item->courseid;
            if (!$params['archived'] && (!$item->coursevisible || !$item->cmvisible)) {
                continue;
            }
            $results[] = self::build_item($item, $params['archived']);
            $count++;
        }
        return array('results' => $results);
    }

    /**
     *
     * @param unknown $user
     * @param unknown $query
     * @param unknown $courseid
     * @param unknown $sectionid
     * @param unknown $archived
     * @param unknown $offset
     * @param unknown $count
     * @param unknown $sortcolumn
     * @param unknown $sortasc
     * @return array
     */
    public static function find_all_usage($user, $query, $courseid, $sectionid, $archived, $offset, $count, $sortcolumn, $sortasc) {
        global $DB, $CFG;

        $input = self::validate_parameters(self::find_all_usage_parameters(), array(
            'user' => $user,
            'query' => $query,
            'courseid' => $courseid,
            'sectionid' => $sectionid,
            'archived' => $archived,
            'offset' => $offset,
            'count' => $count,
            'sortcolumn' => $sortcolumn,
            'sortasc' => $sortasc)
        );

        $sortcol = $input['sortcolumn'];
        if (empty($sortcol)) {
            $sortcol = 'timecreated';
        }
        if ($sortcol == 'course') {
            $sortcol = 'fullname';
        } else if ($sortcol == 'name') {
            $sortcol = 'eqname';
        } else if ($sortcol == 'timecreated') {
            // all good
        } else {
            $sortcol = 'timecreated';
        }
        $sortord = $input['sortasc'] ? 'ASC' : 'DESC';


        $eqfields = "e.id AS eqid,e.name AS eqname, e.intro AS eqintro,e.uuid,e.path,e.attachmentuuid,e.version,e.activation,e.mimetype,e.timecreated,e.timemodified";
        $coursefields = "c.id,c.id AS courseid, c.shortname,c.fullname,c.idnumber,c.visible AS coursevisible,c.format";
        $cmfields = "cm.section AS section,cm.visible AS cmvisible,cm.id AS cmid";
        $sectionfields = "cs.name,cs.section,cs.id AS sectionid";

        $sql = "SELECT $eqfields,$coursefields,$cmfields,$sectionfields
                  FROM {equella} e
                       INNER JOIN {course} c ON e.course = c.id
                       INNER JOIN {course_modules} cm ON cm.instance = e.id
                       INNER JOIN {course_sections} cs ON cs.id=cm.section AND cs.course=c.id
                       INNER JOIN {modules} md ON md.id = cm.module
                 WHERE LOWER(e.name) LIKE LOWER(?)";
        $countsql = "SELECT count(e.id) AS eqid
                  FROM {equella} e
                       INNER JOIN {course} c ON e.course = c.id
                       INNER JOIN {course_modules} cm ON cm.instance = e.id
                       INNER JOIN {course_sections} cs ON cs.id=cm.section AND cs.course=c.id
                       INNER JOIN {modules} md ON md.id = cm.module
                 WHERE LOWER(e.name) LIKE LOWER(?)";

        $params = array('%' . $input['query'] . '%');

        if (!empty($input['courseid'])) {
            $sql .= ' AND c.id = ? ';
            $params[] = $input['courseid'];
        }
        if (!empty($input['sectionid'])) {
            $sql .= ' AND cm.section = ? ';
            $params[] = $input['sectionid'];
        }
        if (empty($input['archived'])) {
            $sql .= ' AND (c.visible = ? AND cm.visible = ?) ';
            $params[] = 1;
            $params[] = 1;
        }
        $sql = $sql . ' ORDER BY ' . $sortcol . ' ' . $sortord;

        $availablecount = 0;
        try {
            $equellaitems = $DB->get_recordset_sql($sql, $params, $offset, $count);
            $availablecount = $DB->count_records_sql($countsql, $params);
        } catch (Exception $ex) {
            throw new moodle_exception('webserviceerror', 'equella', '', $ex->error, $ex->debuginfo);
        }

        $content = array();
        foreach($equellaitems as $item) {
            $content[] = self::build_item($item, $input['archived']);
        }

        return array('available' => $availablecount, 'results' => $content);
    }
    /**
     *
     * @param string $username
     * @param string $query
     * @param boolean $archived
     * @return array
     */
    public static function unfiltered_usage_count($username, $query, $archived) {
        global $DB;
        $params = self::validate_parameters(self::unfiltered_usage_count_parameters(), array(
            'user' => $username,'query' => $query,'archived' => $archived));
        $sql = "SELECT count(e.id) FROM {equella} e WHERE LOWER(e.name) LIKE LOWER(?)";
        $count = $DB->count_records_sql($sql, array('%' . $params['query'] . '%'));
        return array('available' => $count);
    }

    /**
     *
     * @param unknown $user
     * @param unknown $courseid
     * @param unknown $sectionid
     * @param unknown $itemUuid
     * @param unknown $itemVersion
     * @param unknown $url
     * @param unknown $title
     * @param unknown $description
     * @param unknown $attachmentUuid
     * @return array
     */
    public static function add_item_to_course($username, $courseid, $sectionnum, $itemUuid, $itemVersion, $url, $title, $description, $attachmentUuid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::add_item_to_course_parameters(), array(
            'user' => $username,
            'courseid' => $courseid,
            'sectionid' => $sectionnum,
            'itemUuid' => $itemUuid,
            'itemVersion' => $itemVersion,
            'url' => $url,
            'title' => $title,
            'description' => $description,
            'attachmentUuid' => $attachmentUuid
        ));

        self::check_modify_permissions($username, $courseid);
        $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);

        $modname = 'equella';
        $module = $DB->get_record('modules', array('name' => $modname));

        $eq = new stdClass();
        $eq->course = $courseid;
        $eq->module = $module->id;
        $eq->name = $title;
        $eq->intro = $description;
        $eq->introformat = FORMAT_HTML;
        $eq->url = $url;
        $eq->uuid = $itemUuid;
        $eq->version = $itemVersion;
        $eq->mimetype = mimeinfo('type', $title);
        if (!empty($attachmentUuid)) {
            $eq->filename = $title;
            $eq->attachmentuuid = $attachmentUuid;
        }

        $eqid = equella_add_instance($eq);

        $cmid = null;

        $mod = new stdClass;
        $mod->course = $courseid;
        $mod->module = $module->id;
        $mod->instance = $eqid;
        $mod->modulename = $modname;
        $mod->section = 0;
        if (!$cmid = add_course_module($mod)) {
            throw new moodle_exception('cannotaddcoursemodule');
        }
        if (!$addedsectionid = course_add_cm_to_section($courseid, $cmid, $sectionnum)) {
            throw new moodle_exception('cannotaddcoursemoduletosection');
        }

        set_coursemodule_visible($cmid, true);

        if (class_exists('core\\event\\course_module_created')) {
            $cm = get_coursemodule_from_id('equella', $cmid, 0, false, MUST_EXIST);
            $event = \core\event\course_module_created::create_from_cm($cm);
            $event->trigger();
        } else {
            $eventdata = new stdClass();
            $eventdata->modulename = $modname;
            $eventdata->name = $eq->name;
            $eventdata->cmid = $cmid;
            $eventdata->courseid = $eq->course;
            $eventdata->userid = $USER->id;
            events_trigger('mod_created', $eventdata);

            add_to_log($eq->course, "course", "add mod", "../mod/$modname/view.php?id=$cmid", "$modname $eqid");
            add_to_log($eq->course, $modname, "add equella resource", "view.php?id=$cmid", "$eqid", $cmid);
        }

        $result = array(
            'courseid' => $courseid,
            'coursename' => $course->fullname,
            'sectionid' => $params['sectionid'],
            'sectionname' => get_section_name($courseid, $sectionnum)
        );
        //flog("DB queries: " . $DB->perf_get_queries());

        return $result;
    }
    public static function test_connection($param) {
        $params = self::validate_parameters(self::test_connection_parameters(), array('param' => $param));

        return array('success' => $params['param']);
    }
    public static function get_course_code($user, $courseid) {
        $params = self::validate_parameters(self::get_course_code_parameters(),
            array(
                'user' => $user,
                'courseid' => $courseid
            )
        );

        $coursecode = equella_get_coursecode($params['courseid']);

        return array('coursecode' => $coursecode);
    }
    public static function edit_item($user, $itemid, $title, $description) {
        global $DB;
        $params = self::validate_parameters(self::edit_item_parameters(), array(
            'user' => $user,
            'itemid' => $itemid,
            'title' => $title,
            'description' => $description));

        $eq = $DB->get_record('equella', array('id' => $params['itemid']), '*', MUST_EXIST);
        self::check_modify_permissions($params['user'], $eq->course);

        $cm = get_coursemodule_from_instance('equella', $eq->id, $eq->course, false, MUST_EXIST);

        $eq->name = $params['title'];
        $eq->intro = $params['description'];
        $eq->instance = $cm->instance;

        $success = equella_update_instance($eq);

        if (class_exists('core\\event\\course_module_updated')) {
            $event = \core\event\course_module_updated::create_from_cm($cm);
            $event->trigger();
        } else {
            $eventdata = new stdClass();
            $eventdata->modulename = 'equella';
            $eventdata->name = $eq->name;
            $eventdata->cmid = $cm->id;
            $eventdata->courseid = $eq->course;
            $eventdata->userid = $USER->id;
            events_trigger('mod_updated', $eventdata);

            add_to_log($eq->course, "course", "update mod", "../mod/equella/view.php?id=$cm->id", "equella $eq->instance");
            add_to_log($eq->course, "equella", "update equella resource", "view.php?id=$cm->id", "$eq->instance", $cm->id);
        }

        rebuild_course_cache($eq->course);
        return array('success' => $success);
    }
    public static function move_item($user, $itemid, $courseid, $locationid) {
        global $DB, $USER;
        $params = self::validate_parameters(self::move_item_parameters(), array(
            'user' => $user,'itemid' => $itemid,'courseid' => $courseid,'locationid' => $locationid));

        $item = $DB->get_record('equella', array('id' => $params['itemid']), '*', MUST_EXIST);
        self::check_modify_permissions($params['user'], $item->course);

        $cm = get_coursemodule_from_instance('equella', $item->id, $item->course, false, MUST_EXIST);

        $oldCourse = $cm->course;
        $newCourse = $params['courseid'];

        $newSection = $DB->get_record('course_sections', array(
            'course' => $newCourse,'section' => $params['locationid']), '*', MUST_EXIST);

        delete_mod_from_section($cm->id, $cm->section);

        $cm->section = $newSection->id;
        $cm->course = $newCourse;
        $item->course = $newCourse;
        $item->section = $newSection->section;
        $item->instance = $cm->instance;
        $item->cm = $cm->id;

        $success = $DB->update_record("course_modules", $cm);

        if ($success) {
            $success = equella_update_instance($item);

            if (!$sectionid = course_add_cm_to_section($newCourse, $cm->id, $newSection->section)) {
                print_error('cannotaddcoursemoduletosection');
                return null;
            }

            if (class_exists('core\\event\\course_module_updated')) {
                $event = \core\event\course_module_updated::create_from_cm($cm);
                $event->trigger();
            } else {
                $eventdata = new stdClass();
                $eventdata->modulename = 'equella';
                $eventdata->name = $item->name;
                $eventdata->cmid = $cm->id;
                $eventdata->courseid = $item->course;
                $eventdata->userid = $USER->id;
                events_trigger('mod_updated', $eventdata);

                add_to_log($item->course, "course", "update mod", "../mod/equella/view.php?id=$cm->id", "equella $item->instance");
                add_to_log($item->course, "equella", "update equella resource", "view.php?id=$cm->id", "$item->instance", $cm->id);
            }

            rebuild_course_cache($oldCourse);
            rebuild_course_cache($newCourse);
        }
        return array('success' => $success);
    }
    public static function delete_item($user, $itemid) {
        global $DB, $USER;
        $params = self::validate_parameters(self::delete_item_parameters(), array(
            'user' => $user,
            'itemid' => $itemid));

        $item = $DB->get_record('equella', array('id' => $params['itemid']), '*', MUST_EXIST);
        self::check_modify_permissions($params['user'], $item->course);

        $cm = get_coursemodule_from_instance('equella', $item->id, $item->course, false, MUST_EXIST);

        $success = true;
        try {
            course_delete_module($cm->id);
        } catch (Exception $ex) {
            $success = false;
            throw $ex;
        }

        return array('success' => $success);
    }
    private static function get_user_by_username($username) {
        global $CFG;

        $user = get_complete_user_data('username', $username, $CFG->mnet_localhost_id);

        if ($user == null) {
            throw new moodle_exception("UserNotFound/" . $username);
        }
        return $user;
    }
    public static function is_enrolled($user, $courseid) {
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
        $user = self::get_user_by_username($username);
        $coursecontext = context_course::instance($courseid);

        require_capability(self::WRITE_PERMISSION, $coursecontext, $user->id);
    }
    static private function build_item($item, $archived = false) {
        global $DB;
        $attributes = array();

        $cmid = $item->cmid;
        $visible = ($item->coursevisible && $item->cmvisible);

        $instructors = self::get_instructors($item->courseid);
        $enrollmentcount = self::count_enrolled_users($item->courseid);
        $sectionname = self::get_section_name_from_item($item);
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
            'section' => $sectionname,
            'sectionid' => $item->section,
            'dateAdded' => $item->timecreated * 1000,
            'dateModified' => $item->timemodified * 1000,
            'uuid' => $item->uuid,
            'version' => $item->version,
            'attributes' => $attributes,
            'attachment' => $item->path,
            'attachmentUuid' => $item->attachmentuuid,
            'moodlename' => $item->eqname,
            'moodledescription' => html_to_text($item->eqintro),
            'coursecode' => $item->idnumber,
            'instructor' => $instructors,
            'dateAccessed' => $dateAccessed,
            'enrollments' => $enrollmentcount,
            'visible' => $visible);
    }
    static private function get_section_name_from_item($item, $fast=false) {
        if (isset(self::$coursesections[$item->sectionid])) {
            $section = self::$coursesections[$item->sectionid];
            $section_name = $section->sectionname;
        } else {
            $section = new stdclass();
            $section->course = $item->courseid;
            $section->section = $item->section;
            $section->id = $item->sectionid;
            if ($fast) {
                $section_name = get_string('sectionname', 'format_'.$item->format) . ' ' . $item->section;
            } else {
                $section_name = get_section_name($item->courseid, $section);
            }
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
            try {
                $itemViewInfo = $DB->get_records_sql($sql, array($courseid));
            } catch (Exception $ex) {
                throw new moodle_exception('webserviceerror', 'equella', '', $ex->error, $ex->debuginfo);
            }
            $itemViews[$courseid] = $itemViewInfo;
        } else {
            $itemViewInfo = $itemViews[$courseid];
        }

        return $itemViewInfo;
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

        // XXX get_all_user_name_fields() exists 2.6 onward
        if (function_exists('get_all_user_name_fields')) {
            $ufields = get_all_user_name_fields(true, 'u');
        } else {
            $ufields = 'u.firstname,u.lastname';
        }
        if (!isset(self::$instructors[$courseid])) {
            $sql = "SELECT u.id,$ufields
                      FROM {role_assignments} ra
                           INNER JOIN {context} c on ra.contextid = c.id
                           INNER JOIN {role} r on r.id = ra.roleid
                           INNER JOIN {user} u ON ra.userid = u.id
                     WHERE (r.shortname = ? OR r.shortname = ?) AND c.instanceid = ? AND c.contextlevel <= ?";
            try {
                $users = $DB->get_records_sql($sql, array('teacher','editingteacher',$courseid,CONTEXT_COURSE));
            } catch (Exception $ex) {
                throw new moodle_exception('webserviceerror', 'equella', '', $ex->error, $ex->debuginfo);
            }
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
