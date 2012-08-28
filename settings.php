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

defined('MOODLE_INTERNAL') || die;

require_once('adminsettings.class.php');
require_once('equella_rest_api.php');

// Horrible hack to avoid errors displaying error pages
if( !function_exists('ecs') ) {
	function ecs($configoption, $params = null) {
		return get_string('config.'.$configoption, 'equella', $params);
	}
}

if( $ADMIN->fulltree ) {
	require_once($CFG->dirroot.'/mod/equella/lib.php');

	/////////////////////////////////////////////////////////////////////////////////
	// GENERAL SETTINGS
	//
	$settings->add(new admin_setting_heading('equella_dummy_general', ecs('general.heading'), ''));

	$settings->add(new admin_setting_configtext('equella_url', ecs('url.title'), ecs('url.desc'), ''));
	$settings->add(new admin_setting_configtext('equella_action', ecs('action.title'), ecs('action.desc'), 'selectOrAdd'));

	$restrictionOptions = array(EQUELLA_CONFIG_SELECT_RESTRICT_NONE => trim(ecs('restriction.none')),
					 EQUELLA_CONFIG_SELECT_RESTRICT_ITEMS_ONLY => trim(ecs('restriction.itemsonly')),
					 EQUELLA_CONFIG_SELECT_RESTRICT_ATTACHMENTS_ONLY => trim(ecs('restriction.attachmentsonly')));
	$settings->add(new admin_setting_configselect('equella_select_restriction', ecs('restriction.title'), ecs('restriction.desc'), EQUELLA_CONFIG_SELECT_RESTRICT_NONE, $restrictionOptions));

	$settings->add(new admin_setting_configtext('equella_options', ecs('options.title'), ecs('options.desc'), ''));

	$settings->add(new admin_setting_configtext('equella_admin_username', ecs('adminuser.title'), ecs('adminuser.desc'), ''));

	/////////////////////////////////////////////////////////////////////////////////
	//
	// SHARED SECRETS
	//
	$settings->add(new admin_setting_heading('equella_dummy_sharedsecrets', ecs('sharedsecrets.heading'), ecs('sharedsecrets.help')));


  	$settings->add(new equella_setting_left_heading('equella_dummy_default', ecs('group', ecs('group.default')), ''));
	$settings->add(new admin_setting_configtext('equella_shareid', ecs('sharedid.title'), '', ''));
	$settings->add(new admin_setting_configtext('equella_sharedsecret', ecs('sharedsecret.title'), '', ''));

	$defaultsharedsecret = '';
	if( isset($CFG->equella_sharedsecret) ) {
		$defaultsharedsecret = $CFG->equella_sharedsecret;
	}

	foreach( get_all_editing_roles() as $role ) {
		$defaultsecretvalue = '';
		if( $defaultsharedsecret == '' || $defaultsharedsecret == '0' ) {
			$defaultsecretvalue = $role->shortname . $defaultsharedsecret;
		}

		$settings->add(new equella_setting_left_heading('equella_dummy_'.$role->shortname, ecs('group', format_string($role->name)), ''));
		$settings->add(new admin_setting_configtext("equella_{$role->shortname}_shareid", ecs('sharedid.title'), '', $role->shortname, PARAM_TEXT));
		$settings->add(new admin_setting_configtext("equella_{$role->shortname}_sharedsecret", ecs('sharedsecret.title'), '', $defaultsecretvalue, PARAM_TEXT));
	}
	/////////////////////////////////////////////////////////////////////////////////
	//
	// OAuth
	//
	$settings->add(new admin_setting_heading('equella_dummy_shareiiiidsecrets', ecs('oauth.heading'), ''));

        //$options = array('client_id'=>'moodle23', 'redirect_uri'=>$url->out(), 'endpoint'=>'http://localhost:9090/vanilla/', 'response_type'=>'code');
        //$url = equella_restapi::get_auth_code_url($options);
        //$mform->addElement('static', null, get_string('oauthurl', 'block_equella_contribute'), "<a href='$url' target='_blank'>" . 'auth' . '</a>');


	$settings->add(new admin_setting_configtext('equella_oauth_client_id', ecs('oauth.clientid'), '', ''));

        if (!empty($CFG->equella_oauth_client_id) && !empty($CFG->equella_url) && empty($CFG->equella_oauth_access_token)) {
            $settings->add(new admin_setting_openlink('equella_oauth_url', ecs('oauth.url'), '', ''));
        }

        if (!empty($CFG->equella_oauth_access_token)) {
            $settings->add(new admin_setting_configtext('equella_oauth_access_token', ecs('sharedsecret.title'), '', ''));
        }
        $settings->add(new admin_setting_configcheckbox('equella_intercept_moodle_files', get_string('interceptfiles', 'equella'), get_string('interceptfilesintro', 'equella'), 0));


}
