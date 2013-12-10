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

require_once($CFG->dirroot.'/mod/equella/lib.php');
require_once('adminsettings.class.php');
require_once('equella_rest_api.php');

// Horrible hack to avoid errors displaying error pages
if( !function_exists('ecs') ) {
    function ecs($configoption, $params = null) {
        return get_string('config.'.$configoption, 'equella', $params);
    }
}

if( $ADMIN->fulltree ) {
    /////////////////////////////////////////////////////////////////////////////////
    // GENERAL SETTINGS
    //
    $settings->add(new admin_setting_heading('equella_general_settings', ecs('general.heading'), ''));

    $settings->add(new admin_setting_configtext('equella_url', ecs('url.title'), ecs('url.desc'), ''));
    $settings->add(new admin_setting_configtext('equella_action', ecs('action.title'), ecs('action.desc'), ''));

    $restrictionOptions = array(
        EQUELLA_CONFIG_SELECT_RESTRICT_NONE => trim(ecs('restriction.none')),
        EQUELLA_CONFIG_SELECT_RESTRICT_ITEMS_ONLY => trim(ecs('restriction.itemsonly')),
        EQUELLA_CONFIG_SELECT_RESTRICT_ATTACHMENTS_ONLY => trim(ecs('restriction.attachmentsonly')),
        EQUELLA_CONFIG_SELECT_RESTRICT_PACKAGES_ONLY => trim(ecs('restriction.packagesonly'))
    );

    $settings->add(new admin_setting_configselect('equella_select_restriction', ecs('restriction.title'), ecs('restriction.desc'), EQUELLA_CONFIG_SELECT_RESTRICT_NONE, $restrictionOptions));

    $settings->add(new admin_setting_configtext('equella_options', ecs('options.title'), ecs('options.desc'), ''));

    $settings->add(new admin_setting_configtext('equella_admin_username', ecs('adminuser.title'), ecs('adminuser.desc'), ''));
    $settings->add(new admin_setting_configcheckbox('equellaopeninnewwindow', ecs('open.newwindow'), '', 1));


    $settings->add(new admin_setting_configtext('equella_default_window_width', ecs('window.width'), '', EQUELLA_DEFAULT_WINDOW_WIDTH));

    $settings->add(new admin_setting_configtext('equella_default_window_height', ecs('window.height'), '', EQUELLA_DEFAULT_WINDOW_HEIGHT));

    /////////////////////////////////////////////////////////////////////////////////
    //
    // LTI
    //
    $settings->add(new admin_setting_heading('equella_lti_settings', ecs('lti.heading'), ecs('lti.help')));
    $settings->add(new admin_setting_configcheckbox('equella_enable_lti', ecs('enablelti'), ecs('enablelti.desc'), 0));
    $settings->add(new admin_setting_configtext('equella_lti_oauth_key', ecs('lti.key.title'), ecs('lti.key.help'), ''));
    $settings->add(new admin_setting_configtext('equella_lti_oauth_secret', ecs('lti.secret.title'), ecs('lti.secret.help'), ''));

    /////////////////////////////////////////////////////////////////////////////////
    //
    // SHARED SECRETS
    //
    $settings->add(new admin_setting_heading('equella_sharedsecrets_settings', ecs('sharedsecrets.heading'), ecs('sharedsecrets.help')));

    $settings->add(new equella_setting_left_heading('equella_default_group', ecs('group', ecs('group.default')), ''));
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

        $sectionname = 'equella_' . $role->shortname . 'role_group';
        if (!empty($role->name)) {
            $heading = ecs('group', format_string($role->name));
        } else {
            $heading = ecs('group.noname', format_string($role->shortname));
        }
        $settings->add(new equella_setting_left_heading($sectionname, $heading, ''));

        $settings->add(new admin_setting_configtext("equella_{$role->shortname}_shareid", ecs('sharedid.title'), '', $role->shortname, PARAM_TEXT));
        $settings->add(new admin_setting_configtext("equella_{$role->shortname}_sharedsecret", ecs('sharedsecret.title'), '', $defaultsecretvalue, PARAM_TEXT));
    }
    /////////////////////////////////////////////////////////////////////////////////
    //
    // Drag and drop
    //
    $settings->add(new admin_setting_heading('equella_dnd_settings', ecs('dnd.heading'), ecs('dnd.help')));
    $choices = array(
        EQUELLA_CONFIG_INTERCEPT_NONE => get_string('interceptnone', 'equella'),
        EQUELLA_CONFIG_INTERCEPT_ASK  => get_string('interceptask', 'equella'),
        EQUELLA_CONFIG_INTERCEPT_FULL => get_string('interceptauto', 'equella'),
    );
    $intercepttype = new admin_setting_configselect('equella_intercept_files', get_string('interceptfiles', 'equella'), get_string('interceptfilesintro', 'equella'), 0, $choices);

    $settings->add($intercepttype);

}
