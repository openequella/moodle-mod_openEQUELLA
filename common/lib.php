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
 * This file contains function that are used and/or useful for other
 * integration points in Moodle like the Blocks and Repository APIs.
 */

function get_block_configdata($blockname) {
    global $DB, $PAGE;
    $sql = "SELECT *
              FROM {block_instances} bi
             WHERE bi.blockname = :name AND bi.parentcontextid = :parentcontextid";
    $params =  array(
        'name' => $blockname,
        'parentcontextid' => $PAGE->context->id
    );
    $blockinstance = $DB->get_record_sql($sql, $params);
    return unserialize(base64_decode($blockinstance->configdata));
}
/**
 * Create EQUELLA full url
 *
 * @return string
 */
function equella_full_url($urlpart) {
    global $CFG;
    return str_ireplace('signon.do', $urlpart, $CFG->equella_url);
}

/**
 * Return EQUELLA SOAP endpoing
 *
 * @return string
 */
function equella_soap_endpoint() {
    return equella_full_url('services/SoapService41');
}

/**
 * Create EQUELLA single sign on token for current user
 *
 * @return string
 */
function equella_getssotoken($course = null) {
    global $USER, $CFG, $COURSE;

    if (empty($course)) {
        $course = $COURSE;
    }

    $context_sys = get_context_instance(CONTEXT_SYSTEM, 0);
    $context_cc  = get_context_instance(CONTEXT_COURSECAT, $course->category);
    $context_c   = get_context_instance(CONTEXT_COURSE, $course->id);

    // roles are ordered by shortname
    $editingroles = get_all_editing_roles();
    foreach($editingroles as $role) {
        $hassystemrole = false;
        if (!empty($context_sys)) {
            $hassystemrole = user_has_role_assignment($USER->id,  $role->id,$context_sys->id);
        }
        $hascategoryrole = false;
        if (!empty($context_cc)) {
            $hascategoryrole = user_has_role_assignment($USER->id,$role->id,$context_cc->id);
        }
        $hascourserole = false;
        if (!empty($context_c)) {
            $hascourserole = user_has_role_assignment($USER->id,  $role->id,$context_c->id);
        }

        if( $hassystemrole || $hascategoryrole || $hascourserole) {
            //see if the user has a role that is linked to an equella role
            $shareid = $CFG->{"equella_{$role->shortname}_shareid"};
            if( !empty($shareid) ) {
                return equella_getssotoken_raw($USER->username, $shareid, $CFG->{"equella_{$role->shortname}_sharedsecret"});
            }
        }
    }

    // no roles found, use the default shareid and secret
    $shareid = $CFG->equella_shareid;
    if( !empty($shareid) ){
        return equella_getssotoken_raw($USER->username, $shareid, $CFG->equella_sharedsecret);
    }
}

/**
 * Create token by providing shared secret
 *
 * @internal this method should only be used inside this file
 *
 * @param string $username
 * @param string $shareid
 * @param string $sharedsecret
 *
 * @return string
 */
function equella_getssotoken_raw($username, $shareid, $sharedsecret) {
    $time = time() . '000';
    $hash = md5($username . $shareid . $time . $sharedsecret);
    $params = array();
    $params[] = urlencode($username);
    $params[] = $shareid;
    $params[] = $time;
    $params[] = base64_encode(pack('H*', $hash));
    $token = implode(':', $params);

    return $token;
}

/**
 * Append token to existing url
 *
 * @param string $url
 * @param string $token
 *
 * @return string
 */
function equella_appendtoken($url, $token = null) {
    if ($token === null) {
        $token = equella_getssotoken();
    }
    $url .= (strpos($url, '?') != false) ? '&' : '?';
    $url .= 'token=' . urlencode($token);
    return $url;
}

function equella_getssotoken_api() {
    global $CFG;
    return equella_getssotoken_raw($CFG->equella_admin_username, $CFG->equella_shareid, $CFG->equella_sharedsecret);
}

/**
 * Get all existing editing roles
 *
 * @return array
 */
function get_all_editing_roles(){
    global $DB;
    $sql = "SELECT r.* FROM {role_capabilities} rc
        INNER JOIN {role} r ON rc.roleid = r.id
             WHERE capability = :capability
               AND permission = 1
          ORDER BY r.shortname";
    return $DB->get_records_sql($sql, array('capability' => 'moodle/course:manageactivities'));
}
