<?php
define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . "/../../config.php");
require_once($CFG->dirroot . '/mod/equella/locallib.php');

$httpbody = file_get_contents("php://input");

if (equella_lti_oauth::verify_message($httpbody)) {
    $handler = new equella_lti_grading($httpbody);
    $handler->process();
} else {
    throw new invalid_parameter_exception('OAuth signature invalid');
}
