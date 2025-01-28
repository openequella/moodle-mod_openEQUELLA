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
require_once (dirname(dirname(__DIR__)) . '/config.php');
require_once (dirname(dirname(__DIR__)) . '/lib/filelib.php');

require_login();

// / Parameters
$code = required_param('code', PARAM_ALPHANUMEXT); // Repository ID
require_once ('equella_rest_api.php');
$redirect_url = equella_rest_api::get_redirect_url();

$endpoint = equella_rest_api::get_end_point();
if (empty($CFG->equella_oauth_client_id)) {
    throw new moodle_exception('equella client id not set');
}

$options = array('client_id' => $CFG->equella_oauth_client_id,'code' => $code,'endpoint' => $endpoint,'redirect_uri' => $redirect_url->out());
$info = equella_rest_api::get_access_token($options);
if (empty($info->access_token)) {
    die($info->error_description);
}

$token = $info->access_token;

try {
    $bool = set_config('equella_oauth_access_token', $token);
} catch(Exception $ex) {
}

$modulesettingurl = new moodle_url('/admin/settings.php', array('section' => 'modsettingequella'));
?>
<html>
<head>
<script type="text/javascript">
if (window.opener) {
    // refresh parent window
    window.opener.location.href = '<?php echo $modulesettingurl?>';
}
window.close();
</script>
</head>
<body></body>
</html>

