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
 * EQUELLA REST API for PHP
 *
 * @author Dongsheng Cai
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');
require_once(dirname(__FILE__) . '/lib.php');

class equella_curl extends curl {
    /**
     * HTTP PUT method
     * Overwrite put() method in parent class to support file handle
     *
     * @param string $url
     * @param array $params
     * @param array $options
     * @return bool
     */
    public function put($url, $params = array(), $options = array()){
        $fp = $params['filehandle'];
        $options['CURLOPT_PUT']    = 1;
        $options['CURLOPT_INFILE'] = $fp;
        // load balancers and reverse prixies require filesize
        $options['CURLOPT_INFILESIZE'] = $params['filesize'];
        $ret = $this->request($url, $options);
        return $ret;
    }

}

class equella_rest_api {
    const OAUTH_URI = 'oauth/authorise';
    const TOKEN_URI = 'oauth/access_token';

    public static function get_end_point() {
        global $CFG;
        if (empty($CFG->equella_url)) {
            throw new moodle_exception('equella url not set');
        }
        $url = substr($CFG->equella_url, 0, strlen($CFG->equella_url)-strlen('signon.do'));
        $url = rtrim($url, '/') . '/';
        return $url;
    }

    public static function get_redirect_url() {
        $url = new moodle_url('/mod/equella/oauthcallback.php');
        return $url;
    }

    public static function get_auth_code_url($options = array()) {
        $institutionurl = rtrim($options['endpoint'], '/') . '/';
        $oauthurl = $institutionurl . self::OAUTH_URI;

        if (empty($options['redirect_uri'])) {
            $options['redirect_uri'] = 'default';
        }
        if (empty($options['response_type'])) {
            $options['response_type'] = 'code';
        }
        $authurl = $oauthurl . '?' . http_build_query($options, '', '&');
        return $authurl;
    }

    public static function get_access_token($options = array()) {
        global $CFG;
        $parameters = array(
            'grant_type' => 'authorization_code',
            'client_id' => $options['client_id'],
            'code'=>$options['code'],
            'redirect_uri'=>$options['redirect_uri']
        );
        foreach ($parameters as $key=>$value) {
            if (!empty($options[$key])) {
                $parameters[$key] = $options[$key];
            }
        }
        $tokenurl = $options['endpoint'] . self::TOKEN_URI;
        $tokenurl = $tokenurl . '?' . http_build_query($parameters, '', '&');
        $curl = new curl;
        $json = $curl->get($tokenurl);
        $info = json_decode($json);
        return $info;
    }

    public static function contribute_file($filename, $fp, $params = array()) {
        global $CFG;
        $endpoint = self::get_end_point() . 'api/item/quick/' . urlencode($filename);
        $pairs = array();
        foreach ($params as $name=>$value) {
            $pairs[] = (urlencode($name) . '=' . urlencode($value));
        }
        $endpoint .= ('?' . implode('&', $pairs));

        $curl = new equella_curl();
        $curl->setHeader(array(
            'X-Authorization: access_token=' . $CFG->equella_oauth_access_token,
        ));
        $result = $curl->put($endpoint, array('filehandle'=>$fp, 'filesize'=>$params['filesize']));
        fclose($fp);

        if (!empty($result)) {
            $resultjson = json_decode($result);
            if (empty($resultjson) && debugging()) {
                throw new moodle_exception(html_to_text($result));
            }
            if (!empty($resultjson->error_description)) {
                throw new equella_exception('EQUELLA: ' . $resultjson->error_description);
            } else if (!empty($resultjson->error)) {
                throw new equella_exception('EQUELLA: ' . $resultjson->error);
            } else {
                if (debugging()) {
                    throw new moodle_exception($resultjson);
                } else {
                    throw new moodle_exception('error');
                }
            }
        }
        // URL is in HTTP header
        $resp = $curl->getResponse();
        if (empty($resp['Location'])) {
            throw new moodle_exception('restapinolocation', 'equella');
        }
        $json = $curl->get($resp['Location']);
        $info = json_decode($json);
        if (!empty($info)) {
            return $info;
        } else {
            return null;
        }
    }
}
