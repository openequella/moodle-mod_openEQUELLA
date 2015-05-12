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

$sourcedid = optional_param('lis_result_sourcedid', '', PARAM_RAW);
$outcomeurl = optional_param('lis_outcome_service_url', '', PARAM_URL);

$moodleurl = new moodle_url($outcomeurl);
$cmid = $moodleurl->param('cmid');
$cm = get_coursemodule_from_id('equella', $cmid, 0, false, MUST_EXIST);

$homeurl = new moodle_url('/mod/equella/popup.php', array('cmid'=>$cmid));
$graderurl = new moodle_url('/grade/report/grader/index.php', array('id'=>$cm->course));

$coursecontext = context_course::instance($cm->course);
require_capability('moodle/course:manageactivities', $coursecontext);

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_pagelayout('embedded');
$gradingurl = new moodle_url('/mod/equella/tools/ltigrading.php', array('lis_result_sourcedid'=>$sourcedid, 'lis_outcome_service_url'=>$outcomeurl));
$PAGE->set_url($gradingurl);

class equella_lti_grading_form extends moodleform {
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('header', 'ltigrading', 'LTI Grading Form');

        $mform->addElement('text', 'lis_outcome_service_url', 'Service URL', array('size' => '80'));
        $mform->setType('lis_outcome_service_url', PARAM_URL);

        $mform->addElement('textarea', 'lis_result_sourcedid', 'lis_result_sourcedid', array('cols'=>80, 'rows'=>5, 'readonly'=>true));
        $mform->setType('lis_result_sourcedid', PARAM_RAW);

        $mform->addElement('text', 'grade', 'Grade to send to LMS', array('size'=>4));
        $mform->setType('grade', PARAM_FLOAT);
        $mform->setDefault('grade', '0.9999');

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'replaceResultRequest', 'Send Grade');
        $buttonarray[] = &$mform->createElement('submit', 'readResultRequest', 'Read Grade');
        $buttonarray[] = &$mform->createElement('submit', 'deleteResultRequest', 'Delete Grade');
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->setType('buttonar', PARAM_RAW);
        $mform->closeHeaderBefore('buttonar');
    }
    function add_data($data, $postdata) {
        $mform = $this->_form;
        $this->form = $data;
        foreach($postdata as $key=>$value) {
            $mform->addElement('hidden', $key);
            $mform->setDefault($key, $value);
            $mform->setType($key, PARAM_RAW);
        }
        parent::set_data($data);
    }
}

$mform = new equella_lti_grading_form();
if ($mform->is_cancelled()) {
    redirect($homeurl);
}

echo $OUTPUT->header();
if (!empty($sourcedid) and !empty($outcomeurl)) {
    if ($formdata = $mform->get_data()) {
        if (isset($formdata->replaceResultRequest)) {
            $operation = 'replaceResultRequest';
            $postBody = str_replace(
                array('SOURCEDID', 'GRADE', 'OPERATION','MESSAGE'),
                array($sourcedid, $_REQUEST['grade'], $operation, uniqid()),
                getPOXGradeRequest());
        } else if (isset($formdata->readResultRequest)) {
            $operation = 'readResultRequest';
            $postBody = str_replace(
                array('SOURCEDID', 'OPERATION','MESSAGE'),
                array($sourcedid, $operation, uniqid()),
                getPOXRequest());
        } else if (isset($formdata->deleteResultRequest)) {
            $operation = 'deleteResultRequest';
            $postBody = str_replace(
                array('SOURCEDID', 'OPERATION','MESSAGE'),
                array($sourcedid, $operation, uniqid()),
                getPOXRequest());
        }
        $response = sendOAuthBodyPOST('POST', $outcomeurl, $postBody);
        echo html_writer::link($graderurl, "Check in grade report");

        try {
            $retval = parseResponse($response);
        } catch(Exception $e) {
            $retval = $e->getMessage();
        }
        echo $OUTPUT->heading('Grading Results', 5);
        echo html_writer::start_tag('pre');
        print_r($retval);
        echo html_writer::end_tag('pre');

        echo $OUTPUT->heading('RAW Response', 5);
        echo $OUTPUT->box_start();
        echo html_writer::tag('textarea', $response, array('cols'=>80, 'rows'=>10));
        echo $OUTPUT->box_end();
    }
    $mform->set_data(array('lis_result_sourcedid'=>$sourcedid, 'lis_outcome_service_url'=>$outcomeurl));
    echo $OUTPUT->container_start();
    $mform->display();
    echo $OUTPUT->container_end();
}
echo $OUTPUT->footer();
