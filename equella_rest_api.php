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

class equella_curl {
    /** @var bool Caches http request contents */
    public  $cache    = false;
    /** @var bool Uses proxy */
    public  $proxy    = false;
    /** @var string library version */
    public  $version  = '0.4 dev';
    /** @var array http's response */
    public  $response = array();
    /** @var array http header */
    public  $header   = array();
    /** @var string cURL information */
    public  $info;
    /** @var string error */
    public  $error;
    /** @var int error code */
    public  $errno;

    /** @var array cURL options */
    private $options;
    /** @var string Proxy host */
    private $proxy_host = '';
    /** @var string Proxy auth */
    private $proxy_auth = '';
    /** @var string Proxy type */
    private $proxy_type = '';
    /** @var bool Debug mode on */
    private $debug    = false;
    /** @var bool|string Path to cookie file */
    private $cookie   = false;

    /**
     * Constructor
     *
     * @global stdClass $CFG
     * @param array $options
     */
    public function __construct($options = array()){
        global $CFG;
        if (!function_exists('curl_init')) {
            $this->error = 'cURL module must be enabled!';
            trigger_error($this->error, E_USER_ERROR);
            return false;
        }
        // the options of curl should be init here.
        $this->resetopt();
        if (!empty($options['debug'])) {
            $this->debug = true;
        }
        if(!empty($options['cookie'])) {
            if($options['cookie'] === true) {
                $this->cookie = $CFG->dataroot.'/curl_cookie.txt';
            } else {
                $this->cookie = $options['cookie'];
            }
        }
        if (!empty($options['cache'])) {
            if (class_exists('curl_cache')) {
                if (!empty($options['module_cache'])) {
                    $this->cache = new curl_cache($options['module_cache']);
                } else {
                    $this->cache = new curl_cache('misc');
                }
            }
        }
        if (!empty($CFG->proxyhost)) {
            if (empty($CFG->proxyport)) {
                $this->proxy_host = $CFG->proxyhost;
            } else {
                $this->proxy_host = $CFG->proxyhost.':'.$CFG->proxyport;
            }
            if (!empty($CFG->proxyuser) and !empty($CFG->proxypassword)) {
                $this->proxy_auth = $CFG->proxyuser.':'.$CFG->proxypassword;
                $this->setopt(array(
                            'proxyauth'=> CURLAUTH_BASIC | CURLAUTH_NTLM,
                            'proxyuserpwd'=>$this->proxy_auth));
            }
            if (!empty($CFG->proxytype)) {
                if ($CFG->proxytype == 'SOCKS5') {
                    $this->proxy_type = CURLPROXY_SOCKS5;
                } else {
                    $this->proxy_type = CURLPROXY_HTTP;
                    $this->setopt(array('httpproxytunnel'=>false));
                }
                $this->setopt(array('proxytype'=>$this->proxy_type));
            }
        }
        if (!empty($this->proxy_host)) {
            $this->proxy = array('proxy'=>$this->proxy_host);
        }
    }
    /**
     * Resets the CURL options that have already been set
     */
    public function resetopt(){
        $this->options = array();
        $this->options['CURLOPT_USERAGENT']         = 'MoodleBot/1.0';
        // True to include the header in the output
        $this->options['CURLOPT_HEADER']            = 0;
        // True to Exclude the body from the output
        $this->options['CURLOPT_NOBODY']            = 0;
        // TRUE to follow any "Location: " header that the server
        // sends as part of the HTTP header (note this is recursive,
        // PHP will follow as many "Location: " headers that it is sent,
        // unless CURLOPT_MAXREDIRS is set).
        //$this->options['CURLOPT_FOLLOWLOCATION']    = 1;
        $this->options['CURLOPT_MAXREDIRS']         = 10;
        $this->options['CURLOPT_ENCODING']          = '';
        // TRUE to return the transfer as a string of the return
        // value of curl_exec() instead of outputting it out directly.
        $this->options['CURLOPT_RETURNTRANSFER']    = 1;
        $this->options['CURLOPT_BINARYTRANSFER']    = 0;
        $this->options['CURLOPT_SSL_VERIFYPEER']    = 0;
        $this->options['CURLOPT_SSL_VERIFYHOST']    = 2;
        $this->options['CURLOPT_CONNECTTIMEOUT']    = 30;

        if ($cacert = self::get_cacert()) {
            $this->options['CURLOPT_CAINFO'] = $cacert;
        }
    }

    /**
     * Get the location of ca certificates.
     * @return string absolute file path or empty if default used
     */
    public static function get_cacert() {
        global $CFG;

        // Bundle in dataroot always wins.
        if (is_readable("$CFG->dataroot/moodleorgca.crt")) {
            return realpath("$CFG->dataroot/moodleorgca.crt");
        }

        // Next comes the default from php.ini
        $cacert = ini_get('curl.cainfo');
        if (!empty($cacert) and is_readable($cacert)) {
            return realpath($cacert);
        }

        // Windows PHP does not have any certs, we need to use something.
        if ($CFG->ostype === 'WINDOWS') {
            if (is_readable("$CFG->libdir/cacert.pem")) {
                return realpath("$CFG->libdir/cacert.pem");
            }
        }

        // Use default, this should work fine on all properly configured *nix systems.
        return null;
    }

    /**
     * Reset Cookie
     */
    public function resetcookie() {
        if (!empty($this->cookie)) {
            if (is_file($this->cookie)) {
                $fp = fopen($this->cookie, 'w');
                if (!empty($fp)) {
                    fwrite($fp, '');
                    fclose($fp);
                }
            }
        }
    }

    /**
     * Set curl options.
     *
     * Do not use the curl constants to define the options, pass a string
     * corresponding to that constant. Ie. to set CURLOPT_MAXREDIRS, pass
     * array('CURLOPT_MAXREDIRS' => 10) or array('maxredirs' => 10) to this method.
     *
     * @param array $options If array is null, this function will reset the options to default value.
     * @return void
     * @throws coding_exception If an option uses constant value instead of option name.
     */
    public function setopt($options = array()) {
        if (is_array($options)) {
            foreach ($options as $name => $val){
                if (!is_string($name)) {
                    throw new coding_exception('Curl options should be defined using strings, not constant values.');
                }
                if (stripos($name, 'CURLOPT_') === false) {
                    $name = strtoupper('CURLOPT_'.$name);
                }
                $this->options[$name] = $val;
            }
        }
    }

    /**
     * Reset http method
     */
    public function cleanopt(){
        unset($this->options['CURLOPT_HTTPGET']);
        unset($this->options['CURLOPT_POST']);
        unset($this->options['CURLOPT_POSTFIELDS']);
        unset($this->options['CURLOPT_PUT']);
        unset($this->options['CURLOPT_INFILE']);
        unset($this->options['CURLOPT_INFILESIZE']);
        unset($this->options['CURLOPT_CUSTOMREQUEST']);
        unset($this->options['CURLOPT_FILE']);
    }

    /**
     * Resets the HTTP Request headers (to prepare for the new request)
     */
    public function resetHeader() {
        $this->header = array();
    }

    /**
     * Set HTTP Request Header
     *
     * @param array $header
     */
    public function setHeader($header) {
        if (is_array($header)){
            foreach ($header as $v) {
                $this->setHeader($v);
            }
        } else {
            $this->header[] = $header;
        }
    }

    /**
     * Set HTTP Response Header
     *
     */
    public function getResponse(){
        return $this->response;
    }

    /**
     * private callback function
     * Formatting HTTP Response Header
     *
     * @param resource $ch Apparently not used
     * @param string $header
     * @return int The strlen of the header
     */
    private function formatHeader($ch, $header)
    {
        if (strlen($header) > 2) {
            list($key, $value) = explode(" ", rtrim($header, "\r\n"), 2);
            $key = rtrim($key, ':');
            if (!empty($this->response[$key])) {
                if (is_array($this->response[$key])){
                    $this->response[$key][] = $value;
                } else {
                    $tmp = $this->response[$key];
                    $this->response[$key] = array();
                    $this->response[$key][] = $tmp;
                    $this->response[$key][] = $value;

                }
            } else {
                $this->response[$key] = $value;
            }
        }
        return strlen($header);
    }

    /**
     * Set options for individual curl instance
     *
     * @param resource $curl A curl handle
     * @param array $options
     * @return resource The curl handle
     */
    private function apply_opt($curl, $options) {
        // Clean up
        $this->cleanopt();
        // set cookie
        if (!empty($this->cookie) || !empty($options['cookie'])) {
            $this->setopt(array('cookiejar'=>$this->cookie,
                            'cookiefile'=>$this->cookie
                             ));
        }

        // set proxy
        if (!empty($this->proxy) || !empty($options['proxy'])) {
            $this->setopt($this->proxy);
        }
        $this->setopt($options);
        // reset before set options
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, array(&$this,'formatHeader'));
        // set headers
        if (empty($this->header)){
            $this->setHeader(array(
                'User-Agent: MoodleBot/1.0',
                'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
                'Connection: keep-alive'
                ));
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);

        // Bypass proxy (for this request only) if required.
        if (!empty($this->options['CURLOPT_URL']) &&
                is_proxybypass($this->options['CURLOPT_URL'])) {
            unset($this->options['CURLOPT_PROXY']);
        }

        if ($this->debug){
            echo '<h1>Options</h1>';
            var_dump($this->options);
            echo '<h1>Header</h1>';
            var_dump($this->header);
        }

        // Set options.
        foreach($this->options as $name => $val) {
            $name = constant(strtoupper($name));
            curl_setopt($curl, $name, $val);
        }
        return $curl;
    }

    /**
     * Download multiple files in parallel
     *
     * Calls {@link multi()} with specific download headers
     *
     * <code>
     * $c = new curl();
     * $file1 = fopen('a', 'wb');
     * $file2 = fopen('b', 'wb');
     * $c->download(array(
     *     array('url'=>'http://localhost/', 'file'=>$file1),
     *     array('url'=>'http://localhost/20/', 'file'=>$file2)
     * ));
     * fclose($file1);
     * fclose($file2);
     * </code>
     *
     * or
     *
     * <code>
     * $c = new curl();
     * $c->download(array(
     *              array('url'=>'http://localhost/', 'filepath'=>'/tmp/file1.tmp'),
     *              array('url'=>'http://localhost/20/', 'filepath'=>'/tmp/file2.tmp')
     *              ));
     * </code>
     *
     * @param array $requests An array of files to request {
     *                  url => url to download the file [required]
     *                  file => file handler, or
     *                  filepath => file path
     * }
     * If 'file' and 'filepath' parameters are both specified in one request, the
     * open file handle in the 'file' parameter will take precedence and 'filepath'
     * will be ignored.
     *
     * @param array $options An array of options to set
     * @return array An array of results
     */
    public function download($requests, $options = array()) {
        $options['CURLOPT_BINARYTRANSFER'] = 1;
        $options['RETURNTRANSFER'] = false;
        return $this->multi($requests, $options);
    }

    /**
     * Mulit HTTP Requests
     * This function could run multi-requests in parallel.
     *
     * @param array $requests An array of files to request
     * @param array $options An array of options to set
     * @return array An array of results
     */
    protected function multi($requests, $options = array()) {
        $count   = count($requests);
        $handles = array();
        $results = array();
        $main    = curl_multi_init();
        for ($i = 0; $i < $count; $i++) {
            if (!empty($requests[$i]['filepath']) and empty($requests[$i]['file'])) {
                // open file
                $requests[$i]['file'] = fopen($requests[$i]['filepath'], 'w');
                $requests[$i]['auto-handle'] = true;
            }
            foreach($requests[$i] as $n=>$v){
                $options[$n] = $v;
            }
            $handles[$i] = curl_init($requests[$i]['url']);
            $this->apply_opt($handles[$i], $options);
            curl_multi_add_handle($main, $handles[$i]);
        }
        $running = 0;
        do {
            curl_multi_exec($main, $running);
        } while($running > 0);
        for ($i = 0; $i < $count; $i++) {
            if (!empty($options['CURLOPT_RETURNTRANSFER'])) {
                $results[] = true;
            } else {
                $results[] = curl_multi_getcontent($handles[$i]);
            }
            curl_multi_remove_handle($main, $handles[$i]);
        }
        curl_multi_close($main);

        for ($i = 0; $i < $count; $i++) {
            if (!empty($requests[$i]['filepath']) and !empty($requests[$i]['auto-handle'])) {
                // close file handler if file is opened in this function
                fclose($requests[$i]['file']);
            }
        }
        return $results;
    }

    /**
     * Single HTTP Request
     *
     * @param string $url The URL to request
     * @param array $options
     * @return bool
     */
    protected function request($url, $options = array()){
        // create curl instance
        $curl = curl_init($url);
        $options['url'] = $url;
        $this->apply_opt($curl, $options);
        if ($this->cache && $ret = $this->cache->get($this->options)) {
            return $ret;
        } else {
            $ret = curl_exec($curl);
            if ($this->cache) {
                $this->cache->set($this->options, $ret);
            }
        }

        $this->info  = curl_getinfo($curl);
        $this->error = curl_error($curl);
        $this->errno = curl_errno($curl);

        if ($this->debug){
            echo '<h1>Return Data</h1>';
            var_dump($ret);
            echo '<h1>Info</h1>';
            var_dump($this->info);
            echo '<h1>Error</h1>';
            var_dump($this->error);
        }

        curl_close($curl);

        if (empty($this->error)){
            return $ret;
        } else {
            return $this->error;
            // exception is not ajax friendly
            //throw new moodle_exception($this->error, 'curl');
        }
    }

    /**
     * HTTP HEAD method
     *
     * @see request()
     *
     * @param string $url
     * @param array $options
     * @return bool
     */
    public function head($url, $options = array()){
        $options['CURLOPT_HTTPGET'] = 0;
        $options['CURLOPT_HEADER']  = 1;
        $options['CURLOPT_NOBODY']  = 1;
        return $this->request($url, $options);
    }

    /**
     * HTTP POST method
     *
     * @param string $url
     * @param array|string $params
     * @param array $options
     * @return bool
     */
    public function post($url, $params = '', $options = array()){
        $options['CURLOPT_POST']       = 1;
        if (is_array($params)) {
            $this->_tmp_file_post_params = array();
            foreach ($params as $key => $value) {
                if ($value instanceof stored_file) {
                    $value->add_to_curl_request($this, $key);
                } else {
                    $this->_tmp_file_post_params[$key] = $value;
                }
            }
            $options['CURLOPT_POSTFIELDS'] = $this->_tmp_file_post_params;
            unset($this->_tmp_file_post_params);
        } else {
            // $params is the raw post data
            $options['CURLOPT_POSTFIELDS'] = $params;
        }
        return $this->request($url, $options);
    }

    /**
     * HTTP GET method
     *
     * @param string $url
     * @param array $params
     * @param array $options
     * @return bool
     */
    public function get($url, $params = array(), $options = array()){
        $options['CURLOPT_HTTPGET'] = 1;

        if (!empty($params)){
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= http_build_query($params, '', '&');
        }
        return $this->request($url, $options);
    }

    /**
     * Downloads one file and writes it to the specified file handler
     *
     * <code>
     * $c = new curl();
     * $file = fopen('savepath', 'w');
     * $result = $c->download_one('http://localhost/', null,
     *   array('file' => $file, 'timeout' => 5, 'followlocation' => true, 'maxredirs' => 3));
     * fclose($file);
     * $download_info = $c->get_info();
     * if ($result === true) {
     *   // file downloaded successfully
     * } else {
     *   $error_text = $result;
     *   $error_code = $c->get_errno();
     * }
     * </code>
     *
     * <code>
     * $c = new curl();
     * $result = $c->download_one('http://localhost/', null,
     *   array('filepath' => 'savepath', 'timeout' => 5, 'followlocation' => true, 'maxredirs' => 3));
     * // ... see above, no need to close handle and remove file if unsuccessful
     * </code>
     *
     * @param string $url
     * @param array|null $params key-value pairs to be added to $url as query string
     * @param array $options request options. Must include either 'file' or 'filepath'
     * @return bool|string true on success or error string on failure
     */
    public function download_one($url, $params, $options = array()) {
        $options['CURLOPT_HTTPGET'] = 1;
        $options['CURLOPT_BINARYTRANSFER'] = true;
        if (!empty($params)){
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= http_build_query($params, '', '&');
        }
        if (!empty($options['filepath']) && empty($options['file'])) {
            // open file
            if (!($options['file'] = fopen($options['filepath'], 'w'))) {
                $this->errno = 100;
                return get_string('cannotwritefile', 'error', $options['filepath']);
            }
            $filepath = $options['filepath'];
        }
        unset($options['filepath']);
        $result = $this->request($url, $options);
        if (isset($filepath)) {
            fclose($options['file']);
            if ($result !== true) {
                unlink($filepath);
            }
        }
        return $result;
    }

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


    /**
     * HTTP DELETE method
     *
     * @param string $url
     * @param array $param
     * @param array $options
     * @return bool
     */
    public function delete($url, $param = array(), $options = array()){
        $options['CURLOPT_CUSTOMREQUEST'] = 'DELETE';
        if (!isset($options['CURLOPT_USERPWD'])) {
            $options['CURLOPT_USERPWD'] = 'anonymous: noreply@moodle.org';
        }
        $ret = $this->request($url, $options);
        return $ret;
    }

    /**
     * HTTP TRACE method
     *
     * @param string $url
     * @param array $options
     * @return bool
     */
    public function trace($url, $options = array()){
        $options['CURLOPT_CUSTOMREQUEST'] = 'TRACE';
        $ret = $this->request($url, $options);
        return $ret;
    }

    /**
     * HTTP OPTIONS method
     *
     * @param string $url
     * @param array $options
     * @return bool
     */
    public function options($url, $options = array()){
        $options['CURLOPT_CUSTOMREQUEST'] = 'OPTIONS';
        $ret = $this->request($url, $options);
        return $ret;
    }

    /**
     * Get curl information
     *
     * @return string
     */
    public function get_info() {
        return $this->info;
    }

    /**
     * Get curl error code
     *
     * @return int
     */
    public function get_errno() {
        return $this->errno;
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

    public static function contribute_file_with_oauth($filename, $fp, $params = array()) {
        return self::contribute_file($filename, $fp, $params, true);
    }

    public static function contribute_file_with_shared_secret($filename, $fp, $params = array()) {
        $params['token'] = equella_getssotoken();
        return self::contribute_file($filename, $fp, $params, false);
    }

    private static function contribute_file($filename, $fp, $params = array(), $useoauth = false) {
        global $CFG;
        $endpoint = self::get_end_point() . 'api/item/quick/' . urlencode($filename);
        $quickcontributeurl = new moodle_url($endpoint, $params);

        $curl = new equella_curl();
        if ($useoauth) {
            $curl->setHeader(array(
                'X-Authorization: access_token=' . $CFG->equella_oauth_access_token,
            ));
        }
        $result = $curl->put($quickcontributeurl->out(false), array('filehandle'=>$fp, 'filesize'=>$params['filesize']));
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
        $itemurl = $resp['Location'];
        if (!$useoauth) {
            $itemurl = equella_appendtoken($itemurl);
        }
        $json = $curl->get($itemurl);
        $info = json_decode($json);
        if (!empty($info)) {
            return $info;
        } else {
            return null;
        }
    }
}
