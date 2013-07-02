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
    $blockinstance = $DB->get_record_sql(
        "SELECT * FROM {block_instances} bi
        WHERE bi.blockname = :name and bi.parentcontextid = :parentcontextid",
        array(
            'name' => $blockname,
            'parentcontextid' => $PAGE->context->id
        )
    );
    return unserialize(base64_decode($blockinstance->configdata));
}

function equella_soap_endpoint() {
    return equella_full_url('services/SoapService41');
}

function equella_full_url($urlpart) {
    global $CFG;
    return str_ireplace('signon.do', $urlpart, $CFG->equella_url);
}

function equella_getssotoken($readwrite = 'read') {
    global $USER, $CFG, $COURSE;

    if( $readwrite == 'write' ) {
        $context_sys = get_context_instance(CONTEXT_SYSTEM, 0);
        $context_cc = get_context_instance(CONTEXT_COURSECAT, $COURSE->category);
        $context_c = get_context_instance(CONTEXT_COURSE, $COURSE->id);

        foreach( get_all_editing_roles() as $role ) {
            //does user have this role?
            if( user_has_role_assignment($USER->id,$role->id,$context_sys->id) ||
                user_has_role_assignment($USER->id,$role->id,$context_cc->id) ||
                user_has_role_assignment($USER->id,$role->id,$context_c->id)
            ) {
                //see if the user has a role that is linked to an equella role
                $shareid = $CFG->{"equella_{$role->shortname}_shareid"};
                if( !empty($shareid) ) {
                    return equella_getssotoken_raw($USER->username, $shareid, $CFG->{"equella_{$role->shortname}_sharedsecret"});
                }
            }
        }
    }
    //if we are only reading, use the unadorned shareid and secret
    $shareid = $CFG->equella_shareid;
    if( !empty($shareid) ){
        return  equella_getssotoken_raw($USER->username, $shareid, $CFG->equella_sharedsecret);
    }
}

function equella_getssotoken_raw($username, $shareid, $sharedsecret) {
    $time = time() . '000';
    return urlencode($username)
        . ':'
        . $shareid
        . ':'
        . $time
        . ':'
        . base64_encode(pack('H*', md5($username . $shareid . $time . $sharedsecret)));
}

function equella_appendtoken($url, $readwrite = null) {
    return equella_append_with_token($url, equella_getssotoken($readwrite));
}

function equella_append_with_token($url, $token) {
    return $url
        . (strpos($url, '?') != false ? '&' : '?')
        . 'token='
        . urlencode($token);
}

function equella_getssotoken_api() {
    global $CFG;
    return equella_getssotoken_raw($CFG->equella_admin_username, $CFG->equella_shareid, $CFG->equella_sharedsecret);
}

function get_all_editing_roles(){
    global $DB;
    return $DB->get_records_sql(
        "SELECT r.* FROM {role_capabilities} rc
        INNER JOIN {role} r ON rc.roleid = r.id
        WHERE capability = :capability
        AND permission = 1
        ORDER BY r.shortname",
        array(
            'capability' => 'moodle/course:manageactivities'
        )
    );
}
