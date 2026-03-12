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

/**
 * openEQUELLA REST API for PHP
 *
 * @author Dongsheng Cai
 */
defined('MOODLE_INTERNAL') || die();

require_once ($CFG->libdir . '/filelib.php');
require_once (dirname(__FILE__) . '/lib.php');

/**
 * Subclass of Moodle's cURL that exposes request() for file handle uploads.
 */
class equella_curl extends curl {
    /**
     * Performs a PUT request using an already-open file handle.
     */
    public function put_with_handle(string $url, $fp, int $filesize): string {
        return $this->request($url, [
            'CURLOPT_PUT'        => 1,
            'CURLOPT_INFILE'     => $fp,
            'CURLOPT_INFILESIZE' => $filesize,
        ]);
    }
}

class equella_rest_api {
    const OAUTH_URI = 'oauth/authorise';
    const TOKEN_URI = 'oauth/access_token';
    const QUICK_CONTRIBUTE_PATH = 'api/item/quick/';

    /**
     * Returns the base institution URL with a trailing slash.
     */
    public static function get_end_point(): string {
        return rtrim(equella_full_url(''), '/') . '/';
    }

    /**
     * Returns the OAuth redirect URL.
     */
    public static function get_redirect_url(): moodle_url {
        return new moodle_url('/mod/equella/oauthcallback.php');
    }

    /**
     * Builds and returns the OAuth authorisation-code URL.
     */
    public static function get_auth_code_url(array $options = []): string {
        $institutionurl = rtrim($options['endpoint'], '/') . '/';

        $defaults = [
            'redirect_uri'  => 'default',
            'response_type' => 'code',
        ];
        $options = array_merge($defaults, $options);

        return $institutionurl . self::OAUTH_URI . '?' . http_build_query($options, '', '&');
    }

    /**
     * Retrieves an access token using the provided authorisation code.
     */
    public static function get_access_token(array $options = []) {
        $params = [
            'grant_type'   => $options['grant_type']   ?? 'authorization_code',
            'client_id'    => $options['client_id'],
            'code'         => $options['code'],
            'redirect_uri' => $options['redirect_uri'],
        ];

        $tokenurl = $options['endpoint'] . self::TOKEN_URI . '?' . http_build_query($params, '', '&');

        $curl = new curl();
        $json = $curl->get($tokenurl);

        return json_decode($json);
    }

    /**
     * Contribute a file using OAuth authentication.
     */
    public static function contribute_file_with_oauth(string $filename, $fp, array $params = []) {
        return self::contribute_file($filename, $fp, $params, true);
    }

    /**
     * Contribute a file using shared-secret SSO token.
     */
    public static function contribute_file_with_shared_secret(string $filename, $fp, array $params = []) {
        $params['token'] = equella_getssotoken();
        return self::contribute_file($filename, $fp, $params, false);
    }

    /**
     * Handles the file contribution process.
     */
    private static function contribute_file(string $filename, $fp, array $params, bool $useoauth) {
        $url  = self::build_quick_contribute_url($filename, $params);
        $curl = self::create_authenticated_curl($useoauth);

        $result = self::perform_upload($curl, $url->out(false), $fp, $params['file/size']);

        self::validate_upload_response($result);

        $itemurl = self::extract_item_location($curl, $useoauth);

        return self::fetch_item_metadata($curl, $itemurl);
    }

    /**
     * Constructs the Quick Contribute API endpoint URL.
     */
    private static function build_quick_contribute_url(string $filename, array $params): moodle_url {
        $endpoint = self::get_end_point() . self::QUICK_CONTRIBUTE_PATH . rawurlencode($filename);
        return new moodle_url($endpoint, $params);
    }

    /**
     * Creates a cURL client with appropriate auth headers.
     */
    private static function create_authenticated_curl(bool $useoauth): equella_curl {
        $curl = new equella_curl(['ignoresecurity' => true]);

        if ($useoauth) {
            global $CFG;
            $curl->setHeader([
                'X-Authorization: access_token=' . $CFG->equella_oauth_access_token,
            ]);
        }

        return $curl;
    }

    /**
     * Executes the file upload via PUT and ensures the file handle is closed.
     */
    private static function perform_upload(equella_curl $curl, string $url, $fp, int $filesize): string {
        try {
            return $curl->put_with_handle($url, $fp, $filesize);
        } finally {
            if (is_resource($fp)) {
                fclose($fp);
            }
        }
    }

    /**
     * Validates the response from the upload request, throwing exceptions on error.
     */
    private static function validate_upload_response(string $result): void {
        if (empty($result)) {
            return;
        }

        $decodedResult = json_decode($result);

        if (empty($decodedResult)) {
            throw new moodle_exception('restapiinvalidresponse', 'mod_equella', '', null, html_to_text($result));
        }

        $message = $decodedResult->error_description ?? $decodedResult->error ?? null;
        if (!empty($message)) {
            throw new equella_exception('openEQUELLA: ' . $message);
        }

        throw new moodle_exception('restapiinvalidresponse', 'mod_equella', '', null,
            debugging() ? print_r($decodedResult, true) : null
        );
    }

    /**
     * Reads the 'Location'/'location' header from the cURL response to find the new item URL.
     */
    private static function extract_item_location(equella_curl $curl, bool $useoauth): string {
        $headers = array_change_key_case($curl->getResponse());

        if (empty($headers['location'])) {
            throw new moodle_exception('restapinolocation', 'mod_equella');
        }

        $itemurl = $headers['location'];
        if (!$useoauth) {
            $itemurl = equella_appendtoken($itemurl);
        }
        return $itemurl;
    }

    /**
     * GETs the openEQUELLA item metadata JSON from the given item URL.
     */
    private static function fetch_item_metadata(equella_curl $curl, string $itemurl) {
        $json = $curl->get($itemurl);
        return json_decode($json) ?: null;
    }
}