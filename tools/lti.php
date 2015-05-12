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

require_once("../../../config.php");
require_once("$CFG->libdir/formslib.php");
require_once(dirname(dirname(__FILE__)) . "/lib.php");
require_once(dirname(__FILE__) . "/ltilib.php");

require_login();

$context = context_system::instance();
$key = $CFG->equella_lti_oauth_key;
$secret = $CFG->equella_lti_oauth_secret;
$returnurl = optional_param('launch_presentation_return_url', '', PARAM_URL);
$sourcedid = optional_param('lis_result_sourcedid', '', PARAM_RAW);
$outcomeurl = optional_param('lis_outcome_service_url', '', PARAM_URL);
$title = 'IMS Learning Tools Interoperability 1.1';
$heading = 'IMS LTI 1.1 PHP Provider';
$PAGE->set_context($context);
$PAGE->set_pagelayout('embedded');
$PAGE->set_url('/mod/equella/tools/lti.php');

echo $OUTPUT->header();
// Initialize, all secrets are 'secret', do not set session, and do not redirect
$lticontext = new BLTI($key, $secret, false, false);
echo $OUTPUT->heading($heading, 5);

if ( $lticontext->valid ) {
    if (!empty($returnurl)) {
        $links = array();
        $msg = 'A message from the tool provider.';
        $error_msg = 'An error message from the tool provider.';
        $url = new moodle_url($returnurl);
        $links[] = html_writer::link($url, 'Return to tool consumer');
        $url->param('lti_msg', $msg);
        $url->param('lti_log', 'LTI log entry');
        $links[] = html_writer::link($url, 'Return to tool consumer with a message');
        $url->remove_params(array('lti_msg', 'lti_log'));
        $url->param('lti_errormsg', $error_msg);
        $url->param('lti_errorlog', 'LTI error entry: ' . $error_msg);
        $links[] = html_writer::link($url, 'Return to tool consumer with an error');

        if (!empty($sourcedid) and !empty($outcomeurl)) {
            $gradingurl = new moodle_url('/mod/equella/tools/ltigrading.php', array('lis_result_sourcedid'=>$sourcedid, 'lis_outcome_service_url'=>$outcomeurl));
            $links[] = html_writer::link($gradingurl, 'Send grade via LTI');
        }

        echo html_writer::alist($links);
    }

    echo $OUTPUT->heading('Context Information:', 6);
    //$lticontext->dump(false);
    echo html_writer::tag('pre', $lticontext->dump());
} else {
    echo $OUTPUT->notification("Could not establish context: ".$lticontext->message);
}
echo $OUTPUT->heading('Base String:', 6);
echo html_writer::tag('pre', $lticontext->basestring);

if (!empty($_POST)) {
    echo $OUTPUT->heading("Raw POST Parameters:", 6);
    echo html_writer::start_tag('pre');
    ksort($_POST);
    foreach($_POST as $key => $value ) {
        print "$key=$value (".mb_detect_encoding($value).")\n";
    }
    echo html_writer::end_tag('pre');
}

if (!empty($_GET)) {
    echo $OUTPUT->heading("Raw GET Parameters:", 6);
    echo html_writer::start_tag('pre');
    ksort($_GET);
    foreach($_GET as $key => $value ) {
        print "$key=$value (".mb_detect_encoding($value).")\n";
    }
    echo html_writer::end_tag('pre');
}

echo $OUTPUT->footer();
