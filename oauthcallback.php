<?php

require_once(dirname(dirname(__DIR__)).'/config.php');
require_once(dirname(dirname(__DIR__)).'/lib/filelib.php');

require_login();

/// Parameters
$code   = required_param('code', PARAM_ALPHANUMEXT); // Repository ID
require_once('equella_rest_api.php');
$redirect_url = equella_rest_api::get_redirect_url();

$endpoint = equella_rest_api::get_end_point();
if (empty($CFG->equella_oauth_client_id)) {
    throw new moodle_exception('equella client id not set');
}

$options = array('client_id'=>$CFG->equella_oauth_client_id, 'code'=>$code, 'endpoint'=>$endpoint, 'redirect_uri'=>$redirect_url->out());
$info = equella_rest_api::get_access_token($options);
if (empty($info->access_token)) {
    die($info->error_description);
}

$token = $info->access_token;

try {
    $bool = set_config('equella_oauth_access_token', $token);
} catch (Exception $ex) {
}

$modulesettingurl = new moodle_url('/admin/settings.php', array('section'=>'modsettingequella'));
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

