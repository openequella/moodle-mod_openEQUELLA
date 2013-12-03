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

/**
 * Library of functions for EQUELLA internal
 */

require_once($CFG->libdir.'/oauthlib.php');

define('EQUELLA_ITEM_TYPE', 'mod');
define('EQUELLA_ITEM_MODULE', 'equella');
define('EQUELLA_SOURCE', 'mod/equella');

function equella_get_course_contents($courseid, $sectionid) {
    global $DB, $CFG;

    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

    if (!file_exists($CFG->dirroot . '/course/format/' . $course->format . '/lib.php')) {
        throw new moodle_exception('cannotgetcoursecontents', 'webservice', '', null, get_string('courseformatnotfound', 'error', '', $course->format));
    } else {
        require_once($CFG->dirroot . '/course/format/' . $course->format . '/lib.php');
    }

    $context = context_course::instance($course->id, IGNORE_MISSING);

    $coursecontents = new stdClass;
    $coursecontents->id = $course->id;
    $coursecontents->code = $course->idnumber;
    $coursecontents->name = $course->fullname;
    $coursecontents->targetable = false;
    $coursecontents->folders = array();

    if ($course->visible or has_capability('moodle/course:viewhiddencourses', $context)) {

        //retrieve sections
        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();

        //for each sections (first displayed to last displayed)
        foreach ($sections as $key => $section) {

            $sectionvalues = new stdClass;

            if ((int)$section->section == (int)$sectionid) {
                $sectionvalues->selected = true;
            }
            $sectionvalues->id = $section->section;
            $sectionvalues->name = get_section_name($course, $section);
            $sectionvalues->folders = array();
            $sectioncontents = array();

            //foreach ($modinfo->sections[$section->section] as $cmid) {
                //$cm = $modinfo->cms[$cmid];

                //if (!$cm->uservisible) {
                    //continue;
                //}

                //$module = array();

                //$module['id'] = $cm->id;
                //$module['name'] = format_string($cm->name, true);
                //$sectioncontents[] = $module;
            //}
            //$sectionvalues->folders = $sectioncontents;

            $coursecontents->folders[] = $sectionvalues;
        }
    }
    return $coursecontents;
}

/**
 * Returns general link or file embedding html.
 * @param string $fullurl
 * @param string $clicktoopen
 * @param string $mimetype
 * @return string html
 */
function equella_embed_general($equella) {
    global $CFG, $PAGE;
    if ($CFG->equella_enable_lti) {
        $launchurl = new moodle_url('/mod/equella/ltilaunch.php', array('cmid'=>$equella->cmid, 'action'=>'view'));
        $url = $launchurl->out();
    } else {
        $url = equella_appendtoken($equella->url);
    }
    $link = html_writer::tag('a', $equella->name, array('href'=>str_replace('&amp;', '&', $url)));
    $clicktoopen = get_string('clicktoopen', 'equella', $link);

    $iframe = false;

    // IE can not embed stuff properly, that is why we use iframe instead.
    // Unfortunately this tag does not validate in xhtml strict mode,
    // but in any case it is undeprecated in HTML 5 - we will use it everywhere soon!
    if (check_browser_version('MSIE', 5) || $CFG->equella_enable_lti) {
        $iframe = true;
    }

    if ($iframe) {
        $code = <<<EOT
<div class="resourcecontent resourcegeneral">
  <iframe id="resourceobject" src="$url">
    $clicktoopen
  </iframe>
</div>
EOT;
    } else {
        $param = '<param name="src" value="'.$url.'" />';

        $code = <<<EOT
<div class="resourcecontent resourcegeneral">
  <object id="resourceobject" data="$url" width="800" height="600">
    $param
    $clicktoopen
  </object>
</div>
EOT;
    }

    // the size is hardcoded in the object above intentionally because it is adjusted by the following function on-the-fly
    $PAGE->requires->js_init_call('M.util.init_maximised_embed', array('resourceobject'), true);

    return $code;
}

/**
 * Returns general link or file embedding html.
 * @param string $fullurl
 * @param string $clicktoopen
 * @param string $mimetype
 * @return string html
 */
function equella_select_dialog($args) {
    global $CFG, $PAGE;

    $equrl = equella_build_integration_url($args);

    if ($CFG->equella_enable_lti) {
        $args->action = 'select';
        $launchurl = new moodle_url('/mod/equella/ltilaunch.php', (array)$args);
        $objecturl = $launchurl->out();
    } else {
        if ($CFG->equella_action == EQUELLA_ACTION_STRUCTURED) {
            $redirecturl = new moodle_url('/mod/equella/redirectselection.php', array('equellaurl'=>$equrl->out(false), 'courseid'=>$args->course, 'sectionid'=>$args->section));
            $objecturl = $redirecturl->out(false);
        } else {
            $objecturl = $equrl->out();
        }
    }

    $equellatitle = get_string('chooseeqeullaresources', 'mod_equella');
    $equellacontainer = 'equellacontainer';
    $cancel = get_string('cancel');
    $cancelurl = new moodle_url('/course/view.php', array('id'=>$args->course));
    $link = html_writer::link($cancelurl, $cancel);
    $html = <<<EOF
<div>
    <button id="openequellachooser">$equellatitle</button>
    $link
</div>
EOF;
    $PAGE->requires->js_init_call('M.mod_equella.display_equella', array($equellacontainer, 880, 600, $equellatitle, $objecturl), true);

    return $html;
}

function equella_lti_launch_form($endpoint, $params) {
    $attributes = array(
        'method'=>'post',
        'action'=>$endpoint,
        'id'=>'eqLaunchForm',
        'enctype'=>'application/x-www-form-urlencoded',
        'name'=>'eqLaunchForm');
    $html = html_writer::start_tag('form', $attributes);

    foreach ($params as $key => $value) {
        $field = array('name'=>$key, 'value'=>$value, 'type'=>'hidden');
        // moodle will encode speical characters
        $html .= html_writer::empty_tag('input', $field);
    }

    $html .= html_writer::end_tag('form');

    $html .= html_writer::script('document.eqLaunchForm.submit();');
    return $html;
}

function equella_parse_query($str) {
    $op = array();
    $pairs = explode("&", $str);
    foreach ($pairs as $pair) {
        list($k, $v) = array_map("urldecode", explode("=", $pair));
        $op[$k] = $v;
    }
    return $op;
}

function equella_build_integration_url($args, $appendtoken = true) {
    global $USER, $CFG, $DB;

    $callbackurlparams = array(
        'course' => $args->course,
        'section' => $args->section,
    );

    if (!empty($args->cmid)) {
        $callbackurlparams['coursemodule'] = $args->cmid;
    }

    if (!empty($args->module)) {
        $callbackurlparams['module'] = $args->module;
    }

    if (!empty($args->instance)) {
        $callbackurlparams['instance'] = $args->instance;
    }

    if (!empty($args->module)) {
        $callbackurlparams['modulename'] = $args->modulename;
    } else {
        $callbackurlparams['modulename'] = 'equella';
    }

    $callbackurl = new moodle_url('/mod/equella/callbackmulti.php', $callbackurlparams);
    $cancelurl = new moodle_url('/mod/equella/cancel.php', array('course'=>$args->course));

    $equrlparams = array(
        'method'=>'lms',
        'attachmentUuidUrls'=>'true',
        'returnprefix'=>'tle',
        'template'=>'standard',
        'courseId'=>equella_get_courseId($args->course),
        'action'=>$CFG->equella_action,
        'selectMultiple'=>'true',
        'returnurl'=>$callbackurl->out(false),
        'cancelurl'=>$cancelurl->out(false),
    );
    if ($appendtoken) {
        $course = $DB->get_record('course', array('id' => $args->course), '*', MUST_EXIST);
        $equrlparams['token'] = equella_getssotoken($course);
    }
    if (!empty($CFG->equella_options)) {
        $equrlparams['options'] = $CFG->equella_options;
    }
    if( $CFG->equella_select_restriction && $CFG->equella_select_restriction != EQUELLA_CONFIG_SELECT_RESTRICT_NONE ) {
        $equrlparams[$CFG->equella_select_restriction] = 'true';
    }

    return new moodle_url($CFG->equella_url, $equrlparams);
}

function equella_lti_params($equella, $course, $extra = array()) {
    global $USER, $CFG;

    if (empty($equella->cmid)) {
        $equella->cmid = 0;
    }
    if (empty($equella->intro)) {
        $equella->intro = '';
    }
    if (empty($equella->name)) {
        $equella->name = '';
    }

    $role = equella_lti_roles($USER, $equella->cmid, $equella->course);

    $requestparams = array(
        'resource_link_id' => $equella->id,
        'resource_link_title' => $equella->name,
        'resource_link_description' => $equella->intro,
        'user_id' => $USER->id,
        'roles' => $role,
        'context_id' => $course->id,
        'context_label' => $course->shortname,
        'context_title' => $course->fullname,
        'launch_presentation_locale' => current_language()
    );
    if( !empty($equella->popup) ) {
        $requestparams['launch_presentation_document_target'] = 'window';
    } else {
        $requestparams['launch_presentation_document_target'] = 'iframe';
    }
    $requestparams = array_merge($requestparams, $extra);

    $requestparams['lis_person_name_given'] =  $USER->firstname;
    $requestparams['lis_person_name_family'] =  $USER->lastname;
    $requestparams['lis_person_name_full'] = fullname($USER);
    $requestparams['lis_person_contact_email_primary'] = $USER->email;
    $requestparams["ext_lms"] = "moodle-2";
    $requestparams['tool_consumer_info_product_family_code'] = 'moodle';
    $requestparams['tool_consumer_info_version'] = strval($CFG->version);
    $requestparams['oauth_callback'] = 'about:blank';
    $requestparams['lti_version'] = 'LTI-1p0';
    $requestparams['lti_message_type'] = 'basic-lti-launch-request';

    if (!empty($equella->id)) {
        $sourcedid = json_encode(equella_lti_build_sourcedid($equella->id, $USER->id, null));
        $requestparams['lis_result_sourcedid'] = $sourcedid;

        $returnurlparams = array('courseid' => $course->id, 'instanceid' => $equella->id);
        $url = new moodle_url('/mod/equella/ltireturn.php', $returnurlparams);
        $returnurl = $url->out(false);
        $requestparams['launch_presentation_return_url'] = $returnurl;
        if (!empty($CFG->equella_lti_lis_callback)) {
            $requestparams['lis_outcome_service_url'] = $CFG->equella_lti_lis_callback;
        } else {
            $ltilisparams = array(
                'courseid'=>$course->id,
                'cmid'=>$equella->cmid,
            );
            $ltilisurl = new moodle_url('/mod/equella/ltilis.php', $ltilisparams);
            $ltilisurl = $ltilisurl->out(false);
            $requestparams['lis_outcome_service_url'] = $ltilisurl;
        }
    }

    $params = equella_lti_oauth::sign_params($equella->url, $requestparams, 'POST');

    return $params;
}

function equella_is_instructor($user, $cm, $courseid) {
    global $CFG, $DB;

    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

    $context_sys = get_context_instance(CONTEXT_SYSTEM, 0);
    $context_cc  = get_context_instance(CONTEXT_COURSECAT, $course->category);
    $context_c   = get_context_instance(CONTEXT_COURSE, $courseid);

    // roles are ordered by shortname
    $editingroles = get_all_editing_roles();
    $isinstructor = false;
    foreach($editingroles as $role) {
        $hassystemrole = user_has_role_assignment($user->id,  $role->id,  $context_sys->id);
        $hascategoryrole = user_has_role_assignment($user->id, $role->id, $context_cc->id);
        $hascourserole = user_has_role_assignment($user->id,  $role->id,  $context_c->id);

        if( $hassystemrole || $hascategoryrole || $hascourserole) {
            return true;
        }
    }
    return false;
}

function equella_lti_roles($user, $cmid, $courseid) {
    global $USER, $CFG, $COURSE;
    $roles = array();

    if (equella_is_instructor($user, $cmid, $courseid)) {
        array_push($roles, 'Instructor');
    } else {
        array_push($roles, 'Learner');
    }

    if (is_siteadmin($user)) {
        array_push($roles, 'urn:lti:sysrole:ims/lis/Administrator');
    }

    return join(',', $roles);
}

function equella_lti_build_sourcedid($instanceid, $userid, $launchid = null) {
    global $DB;
    $equella = $DB->get_record('equella', array('id' => $instanceid));

    $data = new stdClass();
    $data->instanceid = $instanceid;
    $data->userid = $userid;
    if (!empty($launchid)) {
        $data->launchid = $launchid;
    } else {
        $data->launchid = uniqid('mod_equella_');
    }

    $json = json_encode($data);

    $hash = hash('sha256', $json . $equella->ltisalt, false);

    $container = new stdClass();
    $container->data = $data;
    $container->hash = $hash;

    return $container;
}

function equella_debug_log($data) {
    global $CFG;
    if (defined('EQUELLA_DEV_DEBUG_MODE') && EQUELLA_DEV_DEBUG_MODE == true) {
        file_put_contents("{$CFG->dataroot}/equella_error.log", time() . " => " . var_export($data, true) . "\n", FILE_APPEND);
    }
}

/**
 * Signing and verifying
 */
class equella_lti_oauth extends oauth_helper {
    private static $instance = null;

    public static function get_instance($key, $secret) {
        if (self::$instance == null) {
            self::$instance = new equella_lti_oauth($key, $secret);
        }
        return self::$instance;
    }

    public function __construct($key, $secret) {
        $args = array();
        $args['oauth_consumer_key'] = $key;
        $args['oauth_consumer_secret'] = $secret;
        parent::__construct($args);
        $this->sign_secret = $this->consumer_secret . '&';
    }

    public function sign($http_method, $url, $params, $secret) {
        // Remove query from URL to build basestring
        $baseurl = strtok($url, '?');
        $parts = parse_url($url);
        if (!empty($parts['query'])) {
            $query = $parts['query'];
            $urlparams = equella_parse_query($query);
            $params = array_merge($params, $urlparams);
        }

        $parts = array(
            strtoupper($http_method),
            preg_replace('/%7E/', '~', rawurlencode($baseurl)),
            rawurlencode($this->get_signable_parameters($params)),
        );

        $basestring = implode('&', $parts);
        $sig = base64_encode(hash_hmac('sha1', $basestring, $secret, true));
        return $sig;
    }

    public static function sign_params($url, $params, $method) {
        global $CFG;
        $key = $CFG->equella_lti_oauth_key;
        $secret = $CFG->equella_lti_oauth_secret;
        if (empty($key) || empty($secret)) {
            return $params;
        }
        return self::get_instance($key, $secret)->prepare_oauth_parameters($url, $params, $method);
    }

    public static function verify_message($message) {
        global $CFG;
        // TODO: Switch to core oauthlib once implemented - MDL-30149
        require_once($CFG->dirroot . '/mod/lti/OAuthBody.php');
        try {
            moodle\mod\lti\handleOAuthBodyPOST($CFG->equella_lti_oauth_key, $CFG->equella_lti_oauth_secret, $message);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
}

/**
 * LTI grading support
 *
 */
class equella_lti_grading {
    private $xml;
    private $messagetype;
    private $messageid;
    const LTI_LIS_GRADE_FACTOR = 100;
    public function __construct($xml) {
        $this->xml = new SimpleXMLElement($xml);
    }

    private function get_response_xml($codemajor, $description = null) {
        $messageid = uniqid('mod_equella');
        if (empty($description)) {
            $description = $this->messagetype;
        }
        $codemajor = strtolower($codemajor);
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeResponse xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
    <imsx_POXHeader>
        <imsx_POXResponseHeaderInfo>
            <imsx_version>V1.0</imsx_version>
            <imsx_messageIdentifier>{$messageid}</imsx_messageIdentifier>
            <imsx_statusInfo>
                <imsx_codeMajor>{$codemajor}</imsx_codeMajor>
                <imsx_severity>status</imsx_severity>
                <imsx_description>{$description}</imsx_description>
                <imsx_messageRefIdentifier>{$this->messageid}</imsx_messageRefIdentifier>
            </imsx_statusInfo>
        </imsx_POXResponseHeaderInfo>
    </imsx_POXHeader>
    <imsx_POXBody></imsx_POXBody>
</imsx_POXEnvelopeResponse>
XML;

        return new SimpleXMLElement($xml);
    }

    private function get_message_type() {
        $body = $this->xml->imsx_POXBody;
        foreach ($body->children() as $child) {
            $this->messagetype = $child->getName();
        }
        return $this->messagetype;
    }

    private function get_message_id() {
        $this->messageid = (string)$this->xml->imsx_POXHeader->imsx_POXRequestHeaderInfo->imsx_messageIdentifier;
        return $this->messageid;
    }

    private function read_sourcedid() {
        $sourcedid = $this->xml->imsx_POXBody->{$this->messagetype}->resultRecord->sourcedGUID->sourcedId;
        $sourcedid = html_entity_decode($sourcedid);
        $entity = json_decode($sourcedid);
        return $entity;
    }

    private function verify_sourcedid($equella, $data) {
        $sourcedid = equella_lti_build_sourcedid($data->instanceid, $data->userid, $data->launchid);
        if ($sourcedid->hash != $data->sourcedidhash) {
            throw new invalid_parameter_exception('Invalid sourcedid hash');
        }
    }

    private function parse_replace_message() {
        $entity = $this->read_sourcedid();
        $rawgrade = floatval($this->xml->imsx_POXBody->replaceResultRequest->resultRecord->result->resultScore->textString);

        if ($rawgrade < 0 || $rawgrade >1) {
            throw new invalid_parameter_exception('Invalid grade, it must be float between 0 and 1');
        }

        $data = new stdClass();
        $data->rawgrade = $rawgrade;
        $data->instanceid = $entity->data->instanceid;
        $data->userid = $entity->data->userid;
        $data->launchid = $entity->data->launchid;
        $data->sourcedidhash = $entity->hash;
        return $data;
    }

    private function parse_grade_message() {
        $entity = $this->read_sourcedid();

        $data = new stdClass();
        $data->instanceid = $entity->data->instanceid;
        $data->userid = $entity->data->userid;
        $data->launchid = $entity->data->launchid;
        $data->sourcedidhash = $entity->hash;

        return $data;
    }

    private function handle_replace_message($data) {
        global $DB;
        $equella = $DB->get_record('equella', array('id' => $data->instanceid));
        $this->verify_sourcedid($equella, $data);

        $status = $this->update_grade($equella, $data);

        $responsexml = $this->get_response_xml($status ? 'success' : 'failure');
        $responsexml->imsx_POXBody->addChild($this->messagetype);
        echo $responsexml->asXML();
    }

    private function handle_delete_message($data) {
        global $DB;
        $equella = $DB->get_record('equella', array('id' => $data->instanceid));
        $this->verify_sourcedid($equella, $data);

        $gradestatus = $this->delete_grade($equella, $data);

        $responsexml = $this->get_response_xml($gradestatus ? 'success' : 'failure');
        $responsexml->imsx_POXBody->addChild($this->messagetype);
        echo $responsexml->asXML();

    }
    private function handle_read_message($data) {
        global $DB, $PAGE;
        $equella = $DB->get_record('equella', array('id' => $data->instanceid));

        $context = context_course::instance($equella->course);
        $PAGE->set_context($context);
        $this->verify_sourcedid($equella, $data);

        $grade = $this->read_grade($equella, $data);

        $responsexml = $this->get_response_xml(!empty($grade) ? 'success' : 'failure');
        $node = $responsexml->imsx_POXBody;
        $node = $node->addChild($this->messagetype);
        $node = $node->addChild('result')->addChild('resultScore');
        $node->addChild('language', 'en');
        $node->addChild('textString', !empty($grade) ? $grade : '');
        echo $responsexml->asXML();
    }

    /**
     * Transfrom LTI grade to moodle grade
     *
     * @return int moodle grade
     */
    private function transform_grade($rawgrade) {
        return $rawgrade * self::LTI_LIS_GRADE_FACTOR;
    }

    private function update_grade($equella, $data) {
        global $CFG, $DB;
        require_once($CFG->libdir . '/gradelib.php');

        $item = array();
        $item['itemname'] = $equella->name;

        $grade = new stdClass();
        $grade->userid   = $data->userid;
        $grade->rawgrade = $this->transform_grade($data->rawgrade);

        $status = grade_update(EQUELLA_SOURCE, $equella->course, EQUELLA_ITEM_TYPE, EQUELLA_ITEM_MODULE, $equella->id, 0, $grade, $item);

        return $status == GRADE_UPDATE_OK;
    }

    private function read_grade($equella, $data) {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $grades = grade_get_grades($equella->course, EQUELLA_ITEM_TYPE, EQUELLA_ITEM_MODULE, $equella->id, $data->userid);

        if (isset($grades) && isset($grades->items[0]) && is_array($grades->items[0]->grades)) {
            foreach ($grades->items[0]->grades as $agrade) {
                $grade = $agrade->grade;
                return $grade;
                break;
            }
        }

        return null;
    }

    private function delete_grade($equella, $data) {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $item = new stdClass();
        $item->userid   = $data->userid;
        $item->rawgrade = null;

        $status = grade_update(EQUELLA_SOURCE, $equella->course, EQUELLA_ITEM_TYPE, EQUELLA_ITEM_MODULE, $equella->id, 0, $item, array('deleted'=>1));

        return $status == GRADE_UPDATE_OK;
    }

    public function process() {
        $this->get_message_id();
        switch ($this->get_message_type()) {
            case 'replaceResultRequest':
                try {
                    $data = $this->parse_replace_message();
                    $this->handle_replace_message($data);
                } catch (Exception $ex) {
                    $responsexml = $this->get_response_xml('error', $ex->getMessage());
                    echo $responsexml->asXML();
                }
                break;
            case 'readResultRequest':
                try {
                    $data = $this->parse_grade_message();
                    $this->handle_read_message($data);
                } catch (Exception $ex) {
                    $responsexml = $this->get_response_xml('error', $ex->getMessage());
                    echo $responsexml->asXML();
                }
                break;
            case 'deleteResultRequest':
                try {
                    $data = $this->parse_grade_message();
                    $this->handle_delete_message($data);
                } catch (Exception $ex) {
                }
                break;
            default:
                $responsexml = $this->get_response_xml('unsupported', 'Unsupported request');
                echo $responsexml->asXML();
                break;
        }
    }
}
