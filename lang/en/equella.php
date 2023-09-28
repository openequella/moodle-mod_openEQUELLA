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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

$string['pluginname'] = 'openEQUELLA Resource';
$string['pluginadministration'] = 'openEQUELLA module administration';
$string['modulename'] = 'openEQUELLA Resource';
$string['modulename_help'] = 'The openEQUELLA module enables a teacher to link to content stored in an openEQUELLA repository. Users are automatically authenticated to the openEQUELLA repository when they choose a resource.';
$string['modulenameplural'] = 'openEQUELLA Resources';
$string['description'] = 'Content';
$string['chooseeqeullaresources'] = 'Choose openEQUELLA resources';
$string['noinstances'] = 'There are no openEQUELLA Resources in this course';
$string['modulenameplural'] = 'openEQUELLA Resources';
$string['notsubmittedyet'] = 'openEQUELLA resource not submitted yet';
$string['clicktoopen'] = 'Click {$a} link to open resource.';
$string['crontask'] = 'openEQUELLA cron task';
// Course page drag and drop dialog
$string['dnduploadresource'] = 'Contribute to openEQUELLA';
$string['dnduploadresourcemetadata'] = 'Contribute to openEQUELLA with meta data';
$string['mustloggintoview'] = 'Please log in to view this page.';

////////////////////////////////////////////////////////
// Permissions
$string['equella:addinstance'] = 'Add a new openEQUELLA resource';
$string['equella:manage'] = 'Manage an openEQUELLA resource';
$string['equella:view'] = 'View openEQUELLA resource';
$string['equella:manage'] = 'Manage openEQUELLA resource';

////////////////////////////////////////////////////////
// OPTIONS
$string['option.pagewindow.header'] = 'Display options';
$string['option.pagewindow'] = 'Display';
$string['option.pagewindow.same'] = 'Same window';
$string['option.pagewindow.new'] = 'New window';
$string['option.popup.width'] = 'Default window width (in pixels)';
$string['option.popup.height'] = 'Default window height (in pixels)';
$string['option.popup.resizable'] = 'Allow the window to be resized';
$string['option.popup.scrollbars'] = 'Allow the window to be scrolled';
$string['option.popup.directories'] = 'Show the directory links';
$string['option.popup.location'] = 'Show the location bar';
$string['option.popup.menubar'] = 'Show the menu bar';
$string['option.popup.toolbar'] = 'Show the toolbar';
$string['option.popup.status'] = 'Show the status bar';

////////////////////////////////////////////////////////
// CONFIGURATION: General Settings

$string['config.general.heading'] = 'General Settings';

$string['config.changelog.title'] = 'View change log';
$string['config.changelog.desc'] = 'View bug fixes, new features added to current openEQUELLA module release.';

$string['config.url.title'] = 'openEQUELLA URL';
$string['config.url.desc'] = 'The URL to openEQUELLA. Should end with /signon.do (e.g. http://lcms.institution.edu.au/signon.do)';

$string['config.action.title'] = 'openEQUELLA action';
$string['config.action.desc'] = 'The action string for openEQUELLA. Please note:
   1. Use "selectOrAdd" for EQUELLA 6.0 and older, for EQUELLA 6.1 onward please use "structured"
   2. There should not be a ? or a & at the start or end of the string.';

$string['config.userfield.title'] = 'User field to use';
$string['config.userfield.desc'] = 'Choose the user field to be used instead of username';
$string['config.userfield.username'] = 'Username';

$string['config.restriction.title'] = 'Restrict selections';
$string['config.restriction.desc'] = 'Choose whether course editors should only be able to select items, attachments, packages or anything. Please note that the restrictions only working for EQUELLA 6.0 and higher.';
$string['config.restriction.none'] = 'No restrictions';

$string['config.restriction.itemsonly'] = 'Items only';
$string['config.restriction.attachmentsonly'] = 'Attachments only';
$string['config.restriction.packagesonly'] = 'Packages only';

$string['config.options.title'] = 'openEQUELLA options';
$string['config.options.desc'] = 'The options string for openEQUELLA (e.g. allPowerSearches=true&contributionCollectionIds=uuid1,uuid2). Please note that there should not be a ? or a & at the start or end of the string.  This field is optional.';

$string['config.adminuser.title'] = 'openEQUELLA administrator username';
$string['config.adminuser.desc'] = 'The username of an administrative account in openEQUELLA.  This account is used by high-level admin functions such as the Backup Course To openEQUELLA block, and also for background tasks that don\'t have a user session such as openEQUELLA Resource Checker.  When an activity requires an session with this user, they will be logged in using the Default shared secret values configured below.';

$string['config.open.newwindow'] = 'Open openEQUELLA resource in new window';
$string['config.window.width'] = 'Default window width';
$string['config.window.height'] = 'Default window height';

////////////////////////////////////////////////////////
// CONFIGURATION: LTI
$string['config.enablelti'] = 'Enable LTI';
$string['config.enablelti.desc'] = 'When LTI is enabled, Shared secrets are disabled for openEQUELLA selection sessions. LTI must be enabled to store QTI quiz scores in the Moodle gradebook when QTI 2.1 quizzes linked to courses from openEQUELLA are launched. If this functionality is not required, LTI doesn’t have to be enabled, and shared secrets can still be used. Shared secrets are stored regardless, as they are still used for the Moodle blocks and Drag and Drop functions.';
$string['config.lti.heading'] = 'LTI Settings';
$string['config.lti_oauth_heading'] = 'LTI OAuth client settings';
$string['config.lti.help'] =  '';
$string['config.lti.key.title'] = 'Client ID';
$string['config.lti.key.help'] = 'Client ID is required if LTI is enabled.';
$string['config.lti.secret.title'] = 'Client secret';
$string['config.lti.secret.help'] = 'Client secret is required if LTI is enabled.';
$string['config.lti.liscallback.title'] = 'Outcome callback URL';

////////////////////////////////////////////////////////
// LTI 1.3 migration
$string['config.lti13.migration.title'] = 'LTI 1.3 Migration';
$string['config.lti13.migration.description'] = 'Migrate openEQUELLA resources to use LTI 1.3';

////////////////////////////////////////////////////////
// CONFIGURATION: Shared Secrets

$string['config.sharedsecrets.heading'] = 'Shared Secret Settings';
$string['config.sharedsecrets.help'] =  '<p>Below you can set a default openEQUELLA shared secret for single signing-on users.  You can configure different shared secrets for general (read) usage, and a specialised role based shared secret for each <em>write</em> role in your Moodle site.  If a shared secret ID is not configured for a role then the default shared secret ID and shared secret are used.</p><p>All shared secret IDs and shared secrets must also be configured within openEQUELLA and the shared secret module enabled.  This configuration is found in the openEQUELLA Administration Console under User Management > Shared Secrets.</p>';

$string['config.group'] = '{$a} role settings';
$string['config.group.default'] = 'Default';
$string['config.group.noname'] = 'Role "{$a}" settings';
$string['config.group.manager'] = 'Manager role settings';
$string['config.group.coursecreator'] = 'Course creator role settings';
$string['config.group.editingteacher'] = 'Editing teacher role settings';
$string['config.group.teacher'] = 'Teacher role settings';
$string['config.group.student'] = 'Student role settings';
$string['config.group.guest'] = 'Guest role settings';
$string['config.group.user'] = 'User role settings';
$string['config.group.frontpage'] = 'Frontpage role settings';

$string['config.sharedid.title'] = 'Shared secret ID';
$string['config.sharedsecret.title'] = 'Shared secret';

////////////////////////////////////////////////////////
// CONFIGURATION: Drag and drop
//
$string['config.dnd.heading'] = 'Drag and drop options';
$string['config.dnd.help'] = '';
$string['interceptfiles'] = 'Intercept drag and drop files';
$string['interceptfilesintro'] = 'Select the action required when dragging and dropping files onto the course page';
$string['interceptnone'] = 'Don\'t intercept files';
$string['interceptauto'] = 'Auto contribute files in openEQUELLA';
$string['interceptask']  = 'Display file destination dialog';
$string['interceptmetadata']  = 'Auto contribute file to openEQUELLA with meta data';

////////////////////////////////////////////////////////
// EQUELLA Resource Checker

$string['checker.subject'] = 'openEQUELLA Resource Checker';
$string['checker.message'] = 'The URL for one of the openEQUELLA resources was found to be unavailable:<br>
<br>
<a href="{$a->url}">{$a->url}</a><br>
Name: {$a->name}<br>
Unit: <a href="{$a->courseurl}">{$a->coursename}</a><br>
<br>
You have received this email because you have sufficient permission to fix this.';

////////////////////////////////////////////////////////
// Errors and stuff
$string['restapinolocation'] = 'No location returned';

////////////////////////////////////////////////////////
// EQUELLA LMS PUSH
/*
$string['push.name'] = 'Name';
$string['push.description'] = 'Description';
$string['push.views'] = 'Total views';
$string['push.version'] = 'Version';
$string['push.version.latest'] = 'Latest version';
$string['push.archived'] = 'Visible';
$string['push.archived.yes'] = 'yes';
$string['push.archived.no'] = 'no';
$string['push.attachment'] = 'Selected attachment';
*/

$string['webserviceerror'] = '{$a}';

////////////////////////////////////////////////////////
// GDPR compliance

$string['privacy:metadata:lti_client'] = 'In order to integrate with a remote openEQUELLA LTI service, user data needs to be exchanged with that service. Contact your openEQUELLA administrator for more information.';
$string['privacy:metadata:lti_client:userid'] = 'The userid is sent from Moodle to allow you to access your data on the remote system.';
$string['privacy:metadata:lti_client:givenname'] = 'Your given name is sent to the openEQUELLA system for SSO login';
$string['privacy:metadata:lti_client:familyname'] = 'Your family name is sent to the openEQUELLA system for SSO login';
$string['privacy:metadata:lti_client:fullname'] = 'Your full name is sent to the openEQUELLA system for SSO login';
$string['privacy:metadata:lti_client:email'] = 'Your email address is sent to the openEQUELLA system for SSO login';
$string['privacy:metadata:lti_client:roles'] = 'Your Moodle roles are sent to the openEQUELLA system, which allows you to access your data on the remote system.';
