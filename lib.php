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
require_once ($CFG->dirroot . '/mod/equella/common/lib.php');
require_once ($CFG->dirroot . '/lib/filelib.php');
require_once ($CFG->dirroot . '/course/lib.php');
require_once ($CFG->libdir.'/gradelib.php');
require_once (dirname(__FILE__) . '/equella_rest_api.php');

define('EQUELLA_ITEM_TYPE', 'mod');
define('EQUELLA_ITEM_MODULE', 'equella');
define('EQUELLA_SOURCE', 'mod/equella');

// This must be FALSE in released code
define('EQUELLA_DEV_DEBUG_MODE', false);

define('EQUELLA_CONFIG_SELECT_RESTRICT_NONE', 'none');

define('EQUELLA_CONFIG_SELECT_RESTRICT_ITEMS_ONLY', 'itemonly');
define('EQUELLA_CONFIG_SELECT_RESTRICT_ATTACHMENTS_ONLY', 'attachmentonly');
define('EQUELLA_CONFIG_SELECT_RESTRICT_PACKAGES_ONLY', 'packageonly');

define('EQUELLA_CONFIG_INTERCEPT_NONE', 0);
define('EQUELLA_CONFIG_INTERCEPT_ASK', 1);
define('EQUELLA_CONFIG_INTERCEPT_FULL', 2);
define('EQUELLA_CONFIG_INTERCEPT_META', 3);

define('EQUELLA_ACTION_SELECTORADD', 'selectOrAdd');
define('EQUELLA_ACTION_STRUCTURED', 'structured');

// The default width is the size of equella resource page
define('EQUELLA_DEFAULT_WINDOW_WIDTH', 860);
define('EQUELLA_DEFAULT_WINDOW_HEIGHT', 450);

/**
 * Returns the information if the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function equella_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPMEMBERSONLY:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;

        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;

        default:
            return null;
    }
}
function equella_get_window_options() {
    $width = EQUELLA_DEFAULT_WINDOW_WIDTH;
    if (!empty(equella_get_config('equella_default_window_width'))) {
        $width = equella_get_config('equella_default_window_width');
    }

    $height = EQUELLA_DEFAULT_WINDOW_HEIGHT;
    if (!empty(equella_get_config('equella_default_window_height'))) {
        $height = equella_get_config('equella_default_window_height');
    }

    return array('width' => $width,'height' => $height,'resizable' => 1,'scrollbars' => 1,'directories' => 0,'location' => 0,'menubar' => 0,'toolbar' => 0,'status' => 0);
}

/**
 * Return course code (aka course id number)
 *
 * @param int $courseid
 * @return string
 */
function equella_get_coursecode($courseid) {
    global $CFG;
    require_once ($CFG->dirroot . '/mod/equella/locallib.php');

    return equella_get_course($courseid)->idnumber;
}

function equella_add_instance($equella, $mform = null) {
    global $DB, $CFG;
    $equella->timecreated = time();
    $equella->timemodified = time();
    if (!empty(equella_get_config('equella_open_in_new_window'))) {
        $equella->windowpopup = 1;
    }
    $equella = equella_postprocess($equella);
    return $DB->insert_record("equella", $equella);
}

/**
 * Validate and process EQUELLA options
 *
 * @param stdClass $resource
 * @return stdClass
 */
function equella_postprocess($resource) {
    if (!empty($resource->windowpopup)) {
        $optionlist = array();
        foreach(equella_get_window_options() as $option => $value) {
            if (($option == 'width' or $option == 'height') and empty($resource->$option)) {
                $resource->$option = $value;
            }
            if (isset($resource->$option)) {
                $optionlist[] = ($option . "=" . $resource->$option);
                unset($resource->$option);
            } else {
                $optionlist[] = ($option . "=" . $value);
            }
        }
        $resource->popup = implode(',', $optionlist);
        unset($resource->windowpopup);
    } else {
        $resource->popup = '';
    }

    $pattern = "/(?P<uuid>[\w]{8}-[\w]{4}-[\w]{4}-[\w]{4}-[\w]{12})\/(?P<version>[0-9]*)\/(?P<path>.*)/";

    $url = $resource->url;
    preg_match($pattern, $url, $matches);
    if (!empty($matches['uuid'])) {
        $resource->uuid = $matches['uuid'];
    }
    // version could be 0, so don't test it with !empty()
    if (isset($matches['version'])) {
        $resource->version = $matches['version'];
    }
    if (!empty($matches['path'])) {
        $resource->path = $matches['path'];
    }
    return $resource;
}
function equella_update_instance($equella) {
    global $DB;
    // Given an object containing all the necessary data,
    // will update an existing instance with new data.
    $equella->timemodified = time();
    $equella->id = $equella->instance;
    $equella = equella_postprocess($equella);
    return $DB->update_record("equella", $equella);
}
function equella_delete_instance($id) {
    global $DB, $CFG;
    require_once ($CFG->dirroot . '/mod/equella/locallib.php');

    if (!$equella = equella_get_activity($id, false)) {
        return false;
    }

    if ($equella->activation) {
        $url = str_replace("signon.do", "access/activationwebservice.do", equella_get_config('equella_url'));
        $url = equella_appendtoken($url) . "&activationUuid=" . rawurlencode($equella->activation);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($curl);
        curl_close($curl);
    }

    $DB->delete_records("equella", array("id" => $equella->id));

    return true;
}

/**
 * Given a coursemodule object, this function returns the extra
 * information needed to print this activity in various places.
 *
 * @param cm_info $coursemodule
 * @return cached_cm_info info
 */
function equella_get_coursemodule_info($coursemodule) {
    global $CFG;
    require_once ($CFG->dirroot . '/mod/equella/locallib.php');

    $info = new cached_cm_info();

    if ($resource = equella_get_activity($coursemodule->instance, false)) {
        $info->icon = equella_guess_icon($resource, 24);
        if ($coursemodule->showdescription) {
            $info->content = format_module_intro('equella', $resource, $coursemodule->id, false);
        }

        if (!empty($resource->popup)) {
            $url = new moodle_url('/mod/equella/popup.php', array('cmid' => $coursemodule->id));
            $url = $url->out(false);
            $info->onclick = "window.open('{$url}', '','{$resource->popup}'); return false;";
        }
    }

    return $info;
}

/**
 * Optimised mimetype detection from general URL.
 * Copied from /mod/url/locallib.php
 *
 * @param $equella stdclass
 * @param $size int
 * @return string
 */
function equella_guess_icon($equella, $size = 24) {
    global $CFG;

    $icon = null;
    if (!empty($equella->filename)) {
        if ('document/unknown' != mimeinfo('type', $equella->filename)) {
            $icon = 'f/' . mimeinfo('icon' . $size, $equella->filename);
        }
    }

    if (empty($icon)) {
        $mimetype = $equella->mimetype;
        $icon = file_mimetype_icon($mimetype, $size);
    }

    return $icon;
}

/**
 * Capture moodle files in modules
 *
 * @param stdClass $event
 * @param array
 */
function equella_capture_files($event) {
    global $CFG;
    require_once ($CFG->dirroot . '/mod/equella/locallib.php');

    $fs = get_file_storage();
    if (!$cm = get_coursemodule_from_id($event->other['modulename'], $event->objectid)) {
        return array();
    }
    if (!$course = equella_get_course($cm->course, false)) {
    }
    $context = context_module::instance($cm->id);
    if ($event->other['modulename'] != 'folder' && $event->other['modulename'] != 'resource') {
        return array();
    }

    require_once ($CFG->dirroot . '/mod/' . $event->other['modulename'] . '/lib.php');
    // find out all supported areas
    $functionname = 'mod_' . $event->other['modulename'] . '_get_file_areas';
    $functionname_old = $event->other['modulename'] . '_get_file_areas';

    if (function_exists($functionname)) {
        $areas = $functionname($course, $cm, $context);
    } else if (function_exists($functionname_old)) {
        $areas = $functionname_old($course, $cm, $context);
    } else {
        $areas = array();
    }

    $files = array();
    foreach($areas as $area => $name) {
        $area_files = $fs->get_area_files($context->id, 'mod_' . $event->other['modulename'], $area, false, 'sortorder, itemid', false);
        $files = array_merge($files, $area_files);
    }
    return $files;
}

function equella_find_repository() {
    global $CFG;
    require_once ($CFG->dirroot . '/repository/lib.php');
    $instances = repository::get_instances(array('type' => 'equella'));
    foreach($instances as $e) {
        if ($e->get_option('equella_url') == equella_get_config('equella_url')) {
            return $e;
        }
    }
    return null;
}
function equella_replace_contents_with_references($file, $info) {
    if (empty($info->attachments)) {
        return;
    }
    $items = $info->attachments;
    $fs = get_file_storage();
    // replace moodle files with equella references
    if ($equellarepository = equella_find_repository()) {
        $item = array_pop($items);
        $repositoryid = $equellarepository->id;
        $record = new stdClass();
        $record->filepath = $file->get_filepath();
        $record->filename = $item->filename;
        $record->component = $file->get_component();
        $record->filearea = $file->get_filearea();
        $record->itemid = $file->get_itemid();
        $record->license = $file->get_license();
        $record->source = $source = base64_encode(serialize((object)array('url' => $item->links->view,'filename' => $item->filename)));
        $record->contextid = $file->get_contextid();
        $record->userid = $file->get_userid();
        $now = time();
        $record->timecreated = $now;
        $record->timemodified = $now;
        $reference = $equellarepository->get_file_reference($source);
        $record->referencelifetime = $equellarepository->get_reference_file_lifetime($reference);
        // delete before create reference to avoid pathnamehash conflict
        $file->delete();
        $fs->create_file_from_reference($record, $repositoryid, $reference);
    }
}
function equella_module_event_handler($event) {
    global $CFG;
    require_once ($CFG->dirroot . '/mod/equella/locallib.php');

    if (empty(equella_get_config('equella_intercept_files'))) {
        return;
    }
    if ((int)equella_get_config('equella_intercept_files') != EQUELLA_CONFIG_INTERCEPT_FULL) {
        return;
    }
    $course = equella_get_course($event->courseid);
    $params = array();
    equella_add_lmsinfo_parameters($params, $course, 'quick');
    $params['moodle/module/type'] = $event->other['modulename'];
    $params['moodle/module/name'] = $event->other['name'];
    $params['moodle/module/id'] = $event->objectid;

    // Legacy.  Deprecated
    $params['moodlemoduletype'] = $event->other['modulename'];
    $params['moodlemodulename'] = $event->other['name'];
    $params['moodlemoduleid'] = $event->objectid;
    $params['moodlecoursefullname'] = $course->fullname;
    $params['moodlecourseshortname'] = $course->shortname;
    $params['moodlecourseid'] = $course->id;
    $params['moodlecourseidnumber'] = $course->idnumber;

    $files = equella_capture_files($event);
    foreach($files as $file) {
        $handle = $file->get_content_file_handle();
        equella_add_fileinfo_parameters($params, $file);
        // pushing files to equella
        $info = equella_rest_api::contribute_file_with_shared_secret($file->get_filename(), $handle, $params);
        // replace contents
        equella_replace_contents_with_references($file, $info);
    }
    return true;
}

/**
 * Handle module created event
 */
function equella_handle_mod_created($event) {
    return equella_module_event_handler($event);
}

/**
 * Handle module updated event
 */
function equella_handle_mod_updated($event) {
    return equella_module_event_handler($event);
}

/**
 * Register the ability to handle drag and drop file uploads
 *
 * @return array containing details of the files / types the mod can handle
 */
if ((int)equella_get_config( 'equella_intercept_files') == EQUELLA_CONFIG_INTERCEPT_ASK) {
    function equella_dndupload_register() {
        return array('files' => array(array('extension' => '*','message' => get_string('dnduploadresource', 'mod_equella'))));
    }
}

/**
 * Register the ability to handle drag and drop file uploads with meta data
 *
 * @return array containing details of the files / types the mod can handle
 */
if ((int)equella_get_config('equella_intercept_files') == EQUELLA_CONFIG_INTERCEPT_META) {
    function equella_dndupload_register() {
        global $PAGE, $CFG, $COURSE;
        $config = [
            [
                'courseid' => $COURSE->id,
                'maxbytes' => get_user_max_upload_file_size($PAGE->context, $CFG->maxbytes, $COURSE->maxbytes)
            ]
        ];
        $PAGE->requires->yui_module('moodle-mod_equella-dndupload', 'M.mod_equella.dndupload.init', $config);
        return array('files' => array(
            array('extension' => '*', 'message' => get_string('dnduploadresourcemetadata', 'mod_equella'))
        ));
    }
}

//https://github.com/equella/moodle-mod_equella/issues/60
if ((int)equella_get_config( 'equella_intercept_files') == EQUELLA_CONFIG_INTERCEPT_NONE) {
    function equella_dndupload_register() {
        return null;
    }
}

/**
 * Handle a file that has been uploaded
 *
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function equella_dndupload_handle($uploadinfo) {
    global $USER, $CFG;
    require_once ($CFG->dirroot . '/mod/equella/locallib.php');

    $fs = get_file_storage();
    // Gather the required info.
    $courseid = $uploadinfo->course->id;
    $coursemodule = $uploadinfo->coursemodule;

    $usercontext = context_user::instance($USER->id);
    $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $uploadinfo->draftitemid, 'id', false);

    $moduleid = null;
    foreach($draftfiles as $file) {
        $handle = $file->get_content_file_handle();
        // pushing files to equella
        $params = array();
        equella_add_lmsinfo_parameters($params, $uploadinfo->course, 'quick');
        /*
        $params['moodle/module/type'] = $event->other['modulename'];
        $params['moodle/module/name'] = $event->other['name'];
        $params['moodle/module/id'] = $event->objectid;

        // Legacy.  Deprecated
        $params['moodlemoduletype'] = $event->other['modulename'];
        $params['moodlemodulename'] = $event->other['name'];
        $params['moodlemoduleid'] = $event->objectid;
        $params['moodlecoursefullname'] = $course->fullname;
        $params['moodlecourseshortname'] = $course->shortname;
        $params['moodlecourseid'] = $course->id;
        $params['moodlecourseidnumber'] = $course->idnumber;
        */
        equella_add_fileinfo_parameters($params, $file);
        $mimetype = $file->get_mimetype();

        if (isset($uploadinfo->displayname)) {
            $params['item/title'] = $uploadinfo->displayname;
            // Legacy. Deprecated
        	$params['displayname'] = $uploadinfo->displayname;
        }
        if (isset($uploadinfo->itemdescription)) {
            $params['item/description'] = $uploadinfo->itemdescription;
            // Legacy. Deprecated
        	$params['itemdescription'] = $uploadinfo->itemdescription;
        }
        if (isset($uploadinfo->copyright)){
            $params['item/iscopyright'] = $uploadinfo->copyright;
            // Legacy. Deprecated
        	$params['copyrightflag'] = $uploadinfo->copyright;
        }
    	if (isset($uploadinfo->itemkeyword)) {
            $params['item/keyword'] = $uploadinfo->itemkeyword;
            // Legacy. Deprecated
        	$params['itemkeyword'] = $uploadinfo->itemkeyword;
        }

        $info = equella_rest_api::contribute_file_with_shared_secret($file->get_filename(), $handle, $params);
        if (isset($info->error)) {
            throw new equella_exception($info->error_description);
        }
        $modulename = '';
        if (!empty($info->name)) {
            $modulename = $info->name;
        } else {
            if (!empty($info->description)) {
                $modulename = $info->description;
            } else {
                $modulename = $info->uuid;
            }
        }
        $eqresource = new stdClass;
        $eqresource->course = $courseid;
        $eqresource->name = $modulename;
        $eqresource->intro = $info->description;
        $eqresource->introformat = FORMAT_HTML;
        $eqresource->mimetype = $mimetype;
        $eqresource->filename = $file->get_filename();
        $item = array_pop($info->attachments);
        $eqresource->attachmentuuid = $item->uuid;
        $eqresource->url = $item->links->view;
        try {
            $eqresourceid = equella_add_instance($eqresource, null);
        } catch(Exception $ex) {
            throw new equella_exception('Failed to create EQUELLA resource.');
        }
    }
    return $eqresourceid;
}

class equella_exception extends Exception {
    function __construct($message, $debuginfo = null) {
        global $CFG;
        parent::__construct($message, 0);
        require_once ($CFG->dirroot . '/mod/equella/locallib.php');
        equella_debug_log($debuginfo);
    }
}

/**
 *
 * @return array
 */
function equella_get_view_actions() {
    return array('view all','view','view equella resource');
}

/**
 *
 * @return array
 */
function equella_get_post_actions() {
    return array('add','update','delete','update equella resource','delete equella resource','add equella resource');
}

/**
 * Get equella module recent activities
 *
 * @global object
 * @global object
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 * @param array $activities Passed by reference
 * @param int $index Passed by reference
 * @param int $timemodified Timestamp
 * @param int $courseid
 * @param int $cmid
 * @param int $userid
 * @param int $groupid
 * @return void
 */
function equella_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0)  {
    global $CFG, $COURSE;
    require_once ($CFG->dirroot . '/mod/equella/locallib.php');

    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = equella_get_course($courseid);
    }

    $modinfo = get_fast_modinfo($course);
    $cm = $modinfo->cms[$cmid];

    $equella = equella_get_activity($cm->instance, true);
    if ($equella->timemodified < $timestart) {
        // Remove the activity
        unset($activities[$index--]);
    }


    return;
}

/**
 * Create grade item for given equella.
 *
 * @param stdClass $equella record
 * @param array $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function equella_grade_item_update($eq, $grades=null) {
    global $CFG;

    if (!isset($eq->courseid)) {
        $eq->courseid = $eq->course;
    }

    $params = array('itemname'=>$eq->name, 'idnumber'=>$eq->cmidnumber);

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }
    return grade_update(EQUELLA_SOURCE,
                        $eq->courseid,
                        EQUELLA_ITEM_TYPE,
                        EQUELLA_ITEM_MODULE,
                        $eq->id,
                        0,
                        $grades,
                        $params);
}

function equella_get_user_grades($equella, $userid = 0) {
    $grades = grade_get_grades($equella->course, EQUELLA_ITEM_TYPE, EQUELLA_ITEM_MODULE,
        $equella->id, $userid);

    if (isset($grades) && isset($grades->items[0]) && is_array($grades->items[0]->grades)) {
        foreach($grades->items[0]->grades as $agrade) {
            $grade = $agrade->grade;
            return $grade;
            break;
        }
    }
    return null;
}

/**
 * delete all grades
 */
function equella_grade_item_delete($eq) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update(EQUELLA_SOURCE, $eq->courseid, EQUELLA_ITEM_TYPE, EQUELLA_ITEM_MODULE, $eq->id, 0, NULL, array('deleted'=>1));
}

/**
 * Retrieve an Equella configuration setting.
 *
 * @param string $configname The name of the configuration setting.
 * @return mixed The value of the configuration setting.
 */
function equella_get_config($configname){
    return get_config('equella', $configname);
}

/**
 * Retrieve the userfield/username for a current user.
 *
 * @return string
 */
function mod_equella_get_userfield_value(): string {
    global $USER;

    // Fall back to username.
    $fieldvalue = $USER->username;

    // Figuring out user field from the configuration.
    $userfield = equella_get_config('equella_userfield');
    if (\mod_equella\user_field::is_custom_profile_field($userfield)) {
        $shortname = mod_equella\user_field::get_field_short_name($userfield);
        if (!empty($USER->profile[$shortname])) {
            $fieldvalue = $USER->profile[$shortname];
        }
    } else if (!empty($USER->$userfield)) {
        $fieldvalue = $USER->$userfield;
    }

    return $fieldvalue;
}
