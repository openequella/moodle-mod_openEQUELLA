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
//
require_once(dirname(dirname(__FILE__)) . '/' . 'oauthlocallib.php');

class BLTI {

    public $valid = false;
    public $complete = false;
    public $message = false;
    public $basestring = false;
    public $info = false;
    public $context_id = false;  // Override context_id
    public $consumer_id = false;
    public $user_id = false;
    public $course_id = false;
    public $resource_id = false;
    private function is_lti_request() {
        $good_message_type = $_REQUEST["lti_message_type"] == "basic-lti-launch-request";
        $good_lti_version = $_REQUEST["lti_version"] == "LTI-1p0";
        $resource_link_id = $_REQUEST["resource_link_id"];
        if ($good_message_type and $good_lti_version and isset($resource_link_id) ) return(true);
        return false;
    }

    function __construct($oauth_consumer_key='', $secret='') {

        // Insure we have a valid launch
        if ( empty($oauth_consumer_key) ) {
            $this->message = "Missing oauth_consumer_key in request";
            return;
        }

        // Verify the message signature
        $store = new moodle\mod\equella\TrivialOAuthDataStore();
        $store->add_consumer($oauth_consumer_key, $secret);

        $server = new moodle\mod\equella\OAuthServer($store);

        $method = new moodle\mod\equella\OAuthSignatureMethod_HMAC_SHA1();
        $server->add_signature_method($method);
        $request = moodle\mod\equella\OAuthRequest::from_request();

        $this->basestring = $request->get_signature_base_string();

        try {
            $server->verify_request($request);
            $this->valid = true;
        } catch (Exception $e) {
            $this->message = $e->getMessage();
            return;
        }

        // Store the launch information in the session for later
        $newinfo = array();
        foreach($_POST as $key => $value ) {
            if ( $key == "basiclti_submit" ) continue;
            if ( strpos($key, "oauth_") === false ) {
                $newinfo[$key] = $value;
                continue;
            }
            if ( $key == "oauth_consumer_key" ) {
                $newinfo[$key] = $value;
                continue;
            }
        }

        $this->info = $newinfo;
    }

    function isInstructor() {
        $roles = $this->info['roles'];
        $roles = strtolower($roles);
        if ( ! ( strpos($roles,"instructor") === false ) ) return true;
        if ( ! ( strpos($roles,"administrator") === false ) ) return true;
        return false;
    }

    function getUserEmail() {
        $email = $this->info['lis_person_contact_email_primary'];
        if ( strlen($email) > 0 ) return $email;
        # Sakai Hack
        $email = $this->info['lis_person_contact_emailprimary'];
        if ( strlen($email) > 0 ) return $email;
        return false;
    }

    function getUserShortName() {
        $email = $this->getUserEmail();
        $givenname = $this->info['lis_person_name_given'];
        $familyname = $this->info['lis_person_name_family'];
        $fullname = $this->info['lis_person_name_full'];
        if ( strlen($email) > 0 ) return $email;
        if ( strlen($givenname) > 0 ) return $givenname;
        if ( strlen($familyname) > 0 ) return $familyname;
        return $this->getUserName();
    }

    function getUserName() {
        $givenname = $this->info['lis_person_name_given'];
        $familyname = $this->info['lis_person_name_family'];
        $fullname = $this->info['lis_person_name_full'];
        if ( strlen($fullname) > 0 ) return $fullname;
        if ( strlen($familyname) > 0 and strlen($givenname) > 0 ) return $givenname + $familyname;
        if ( strlen($givenname) > 0 ) return $givenname;
        if ( strlen($familyname) > 0 ) return $familyname;
        return $this->getUserEmail();
    }

    // Name spaced
    function getUserKey() {
        $oauth = $this->info['oauth_consumer_key'];
        $id = $this->info['user_id'];
        if ( strlen($id) > 0 and strlen($oauth) > 0 ) return $oauth . ':' . $id;
        return false;
    }

    // Un-Namespaced
    function getUserLKey() {
        $id = $this->info['user_id'];
        if ( strlen($id) > 0 ) return $id;
        return false;
    }

    function setUserID($new_id) {
        $this->user_id = $new_id;
    }

    function getUserID() {
        return $this->user_id;
    }

    function getUserImage() {
        if (!empty($this->info['user_image'])) {
            $image = $this->info['user_image'];
            return $image;
        }
        $email = $this->getUserEmail();
        if ( $email === false ) return false;
        $size = 40;
        $grav_url = 'http://';
        if (!empty($_SERVER['HTTPS'])) {
            $grav_url = 'https://';
        }
        $grav_url = $grav_url . "www.gravatar.com/avatar.php?gravatar_id=".md5( strtolower($email) )."&size=".$size;
        return $grav_url;
    }

    function getResourceKey() {
        $oauth = $this->info['oauth_consumer_key'];
        $id = $this->info['resource_link_id'];
        if ( strlen($id) > 0 and strlen($oauth) > 0 ) return $oauth . ':' . $id;
        return false;
    }

    function getResourceLKey() {
        $id = $this->info['resource_link_id'];
        if ( strlen($id) > 0 ) return $id;
        return false;
    }

    function setResourceID($new_id) {
        $this->resource_id = $new_id;
    }

    function getResourceID() {
        return $this->resource_id;
    }

    function getResourceTitle() {
        $title = $this->info['resource_link_title'];
        if ( strlen($title) > 0 ) return $title;
        return false;
    }

    function getConsumerKey() {
        $oauth = $this->info['oauth_consumer_key'];
        return $oauth;
    }

    function setConsumerID($new_id) {
        $this->consumer_id = $new_id;
    }

    function getConsumerID() {
        return $this->consumer_id;
    }

    function getCourseLKey() {
        if ( $this->context_id ) return $this->context_id;
        $id = $this->info['context_id'];
        if ( strlen($id) > 0 ) return $id;
        return false;
    }

    function getCourseKey() {
        if ( $this->context_id ) return $this->context_id;
        $oauth = $this->info['oauth_consumer_key'];
        $id = $this->info['context_id'];
        if ( strlen($id) > 0 and strlen($oauth) > 0 ) return $oauth . ':' . $id;
        return false;
    }

    function setCourseID($new_id) {
        $this->course_id = $new_id;
    }

    function getCourseID() {
        return $this->course_id;
    }

    function getCourseName() {
        $label = $this->info['context_label'];
        $title = $this->info['context_title'];
        $id = $this->info['context_id'];
        if ( strlen($label) > 0 ) return $label;
        if ( strlen($title) > 0 ) return $title;
        if ( strlen($id) > 0 ) return $id;
        return false;
    }

    function getCSS() {
        $list = $this->info['launch_presentation_css_url'];
        if ( strlen($list) < 1 ) return array();
        return explode(',',$list);
    }

    function getOutcomeService() {
        $retval = $this->info['lis_outcome_service_url'];
        if ( strlen($retval) > 1 ) return $retval;
        return false;
    }

    function getOutcomeSourceDID() {
        $retval = $this->info['lis_result_sourcedid'];
        if ( strlen($retval) > 1 ) return $retval;
        return false;
    }

    function dump($return=true) {
        if ( ! $this->valid or $this->info == false ) return "Context not valid\n";
        if (!$return) {
            print_r($this->info);
        }
        $ret = "";
        if ( $this->isInstructor() ) {
            $ret .= "isInstructor() = true\n";
        } else {
            $ret .= "isInstructor() = false\n";
        }
        $ret .= "getConsumerKey() = ".$this->getConsumerKey()."\n";
        $ret .= "getUserLKey() = ".$this->getUserLKey()."\n";
        $ret .= "getUserKey() = ".$this->getUserKey()."\n";
        $ret .= "getUserID() = ".$this->getUserID()."\n";
        $ret .= "getUserEmail() = ".$this->getUserEmail()."\n";
        $ret .= "getUserShortName() = ".$this->getUserShortName()."\n";
        $ret .= "getUserName() = ".$this->getUserName()."\n";
        $ret .= "getUserImage() = ".$this->getUserImage()."\n";
        $ret .= "getResourceKey() = ".$this->getResourceKey()."\n";
        $ret .= "getResourceID() = ".$this->getResourceID()."\n";
        $ret .= "getResourceTitle() = ".$this->getResourceTitle()."\n";
        $ret .= "getCourseName() = ".$this->getCourseName()."\n";
        $ret .= "getCourseKey() = ".$this->getCourseKey()."\n";
        $ret .= "getCourseID() = ".$this->getCourseID()."\n";
        $ret .= "getOutcomeSourceDID() = ".$this->getOutcomeSourceDID()."\n";
        $ret .= "getOutcomeService() = ".$this->getOutcomeService()."\n";
        return $ret;
    }

}

function getPOXGradeRequest() {
    $xml =<<<XML
<?xml version = "1.0" encoding = "UTF-8"?>
<imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
  <imsx_POXHeader>
    <imsx_POXRequestHeaderInfo>
      <imsx_version>V1.0</imsx_version>
      <imsx_messageIdentifier>MESSAGE</imsx_messageIdentifier>
    </imsx_POXRequestHeaderInfo>
  </imsx_POXHeader>
  <imsx_POXBody>
    <OPERATION>
      <resultRecord>
        <sourcedGUID>
          <sourcedId>SOURCEDID</sourcedId>
        </sourcedGUID>
        <result>
          <resultScore>
            <language>en-us</language>
            <textString>GRADE</textString>
          </resultScore>
        </result>
      </resultRecord>
    </OPERATION>
  </imsx_POXBody>
</imsx_POXEnvelopeRequest>
XML;
    return $xml;
}

function getPOXRequest() {
    $xml =<<<XML
<?xml version = "1.0" encoding = "UTF-8"?>
<imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
  <imsx_POXHeader>
    <imsx_POXRequestHeaderInfo>
      <imsx_version>V1.0</imsx_version>
      <imsx_messageIdentifier>MESSAGE</imsx_messageIdentifier>
    </imsx_POXRequestHeaderInfo>
  </imsx_POXHeader>
  <imsx_POXBody>
    <OPERATION>
      <resultRecord>
        <sourcedGUID>
          <sourcedId>SOURCEDID</sourcedId>
        </sourcedGUID>
      </resultRecord>
    </OPERATION>
  </imsx_POXBody>
</imsx_POXEnvelopeRequest>
XML;
    return $xml;
}

function sendOAuthBodyPOST($method, $endpoint, $body) {
    global $CFG;
    require_once($CFG->libdir . '/filelib.php');
    $hash = base64_encode(sha1($body, TRUE));
    $oauth_consumer_key = $CFG->equella_lti_oauth_key;
    $oauth_consumer_secret = $CFG->equella_lti_oauth_secret;
    $parms = array('oauth_body_hash' => $hash);

    $test_token = '';
    $hmac_method = new moodle\mod\equella\OAuthSignatureMethod_HMAC_SHA1();
    $test_consumer = new moodle\mod\equella\OAuthConsumer($oauth_consumer_key, $oauth_consumer_secret, NULL);

    $acc_req = moodle\mod\equella\OAuthRequest::from_consumer_and_token($test_consumer, $test_token, $method, $endpoint, $parms);
    $acc_req->sign_request($hmac_method, $test_consumer, $test_token);

    global $LastOAuthBodyBaseString;
    $LastOAuthBodyBaseString = $acc_req->get_signature_base_string();

    $oauthheader = $acc_req->to_header();
    $contenttypeheader = "Content-Type: application/xml";

    $http = new curl;
    $http->setHeader(array($oauthheader, $contenttypeheader));
    return $http->post($endpoint, $body);
}

function parseResponse($response) {
    $retval = Array();
    try {
        $xml = new SimpleXMLElement($response);
        $imsx_header = $xml->imsx_POXHeader->children();
        $parms = $imsx_header->children();
        $status_info = $parms->imsx_statusInfo;
        $retval['imsx_codeMajor'] = (string) $status_info->imsx_codeMajor;
        $retval['imsx_severity'] = (string) $status_info->imsx_severity;
        $retval['imsx_description'] = (string) $status_info->imsx_description;
        $retval['imsx_messageIdentifier'] = (string) $parms->imsx_messageIdentifier;
        $imsx_body = $xml->imsx_POXBody->children();
        $operation = $imsx_body->getName();
        $retval['response'] = $operation;
        $parms = $imsx_body->children();
    } catch (Exception $e) {
        throw new Exception('Error: Unable to parse XML response' . $e->getMessage());
    }

    if ( $operation == 'readResultResponse' ) {
       try {
           $retval['language'] =(string) $parms->result->resultScore->language;
           $retval['textString'] = (string) $parms->result->resultScore->textString;
       } catch (Exception $e) {
            throw new Exception("Error: Body parse error: ".$e->getMessage());
       }
    }
    return $retval;
}
