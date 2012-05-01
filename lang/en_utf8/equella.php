<?php

// This file is part of the EQUELLA Moodle Integration - http://code.google.com/p/equella-moodle-module/
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

$string['modulename'] = 'EQUELLA Resource';
$string['description'] = 'Content';
$string['noinstances'] = 'There are no EQUELLA Resources in this course';
$string['modulenameplural'] = 'EQUELLA Resources';
$string['notsubmittedyet'] = 'EQUELLA resource not submitted yet';
$string['solutionname'] = 'Name';

////////////////////////////////////////////////////////
// CONFIGURATION: General Settings

$string['config.general.heading'] = 'General Settings';

$string['config.url.title'] = 'EQUELLA URL';
$string['config.url.desc'] = 'The URL to EQUELLA. Should end with /signon.do (e.g. http://lcms.institution.edu.au/signon.do)';

$string['config.action.title'] = 'EQUELLA action';
$string['config.action.desc'] = 'The action string for EQUELLA (the default is: action=selectOrAdd). Please note that there should not be a ? or a & at the start or end of the string.';

$string['config.restriction.title'] = 'Restrict selections';
$string['config.restriction.desc'] = 'Choose whether course editors should only be able to select items, attachments or anything';
$string['config.restriction.none'] = 'No restrictions';
$string['config.restriction.itemsonly'] = 'Items only';
$string['config.restriction.attachmentsonly'] = 'Attachments only';

$string['config.options.title'] = 'EQUELLA options';
$string['config.options.desc'] = 'The options string for EQUELLA (e.g. allPowerSearches=true&contributionCollectionIds=uuid1,uuid2). Please note that there should not be a ? or a & at the start or end of the string.  This field is optional.';

$string['config.location.title'] = 'Integration location';
$string['config.location.desc'] = 'Choose which drop-down control the \'EQUELLA Resource\' entry will be available in.';
$string['config.location.option.resource'] = 'Resource drop down';
$string['config.location.option.activity'] = 'Activity drop down';

$string['config.adminuser.title'] = 'EQUELLA administrator username';
$string['config.adminuser.desc'] = 'The username of an administrative account in EQUELLA.  This account is used by high-level admin functions such as the Backup Course To EQUELLA block, and also for background tasks that don\'t have a user session such as EQUELLA Resource Checker.  When an activity requires an session with this user, they will be logged in using the Default shared secret values configured below.';

////////////////////////////////////////////////////////
// CONFIGURATION: Shared Secrets

$string['config.sharedsecrets.heading'] = 'Shared Secret Settings';
$string['config.sharedsecrets.help'] =  '<p>Below you can set a default EQUELLA shared secret for single signing-on users.  You can configure different shared secrets for general (read) usage, and a specialised role based shared secret for each <em>write</em> role in your Moodle site.  If a shared secret ID is not configured for a role then the default shared secret ID and shared secret are used.</p><p>All shared secret IDs and shared secrets must also be configured within EQUELLA and the shared secret module enabled.  This configuration is found in the EQUELLA Administration Console under User Management > Shared Secrets.</p>';
$string['config.group'] = '{$a} role settings';
$string['config.group.default'] = 'Default';
$string['config.sharedid.title'] = 'Shared secret ID';
$string['config.sharedsecret.title'] = 'Shared secret';

////////////////////////////////////////////////////////
// EQUELLA Resource Checker

$string['checker.subject'] = 'EQUELLA Resource Checker';
$string['checker.message'] = 'The URL for one of the EQUELLA resources was found to be unavailable:<br>
<br>
<a href="$a->url">$a->url</a><br>
Name: $a->name<br>
Unit: <a href="$a->courseurl">$a->coursename</a><br>
<br>
You have received this email because you have sufficient permission to fix this.';

?>
