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

require_once($CFG->dirroot.'/mod/equella/common/lib.php');
require_once($CFG->dirroot.'/lib/filelib.php');

define('EQUELLA_CONFIG_LOCATION_RESOURCE', 'resource');
define('EQUELLA_CONFIG_LOCATION_ACTIVITY', 'activity');

define('EQUELLA_CONFIG_SELECT_RESTRICT_NONE', 'none');
define('EQUELLA_CONFIG_SELECT_RESTRICT_ITEMS_ONLY', 'itemonly');
define('EQUELLA_CONFIG_SELECT_RESTRICT_ATTACHMENTS_ONLY', 'attachmentonly');

function equella_get_window_options() {
	return array('width', 'height', 'resizable', 'scrollbars', 'directories', 'location', 'menubar', 'toolbar', 'status');
}

function equella_get_courseId($courseid) {
	$record = get_record("course", 'id', $courseid);
	return $record->idnumber;
}

function equella_get_types()
{
	global $CFG;

	$type = new object();
	$type->modclass = $CFG->equella_location == EQUELLA_CONFIG_LOCATION_RESOURCE ? MOD_CLASS_RESOURCE : MOD_CLASS_ACTIVITY;
	$type->name = "equella";
	$type->type = "equella";
	$type->typestr = get_string("modulename", "equella");

	$types = array();
	$types[] = $type;
	return $types;
}

function equella_add_instance($equella) {
	// Given an object containing all the necessary data,
	// (defined by the form in mod.html) this function
	// will create a new instance and return the id number
	// of the new instance.
	global $USER;
	$equella->timecreated = time();
	$equella->timemodified = time();
	equella_postprocess($equella);
	return insert_record("equella", $equella);
}

function equella_postprocess(&$resource) {
	if( isset($resource->windowpopup) && $resource->windowpopup ) {
		$optionlist = array();
		foreach( equella_get_window_options() as $option ) {
			$optionlist[] = $option."=".$resource->$option;
			unset($resource->$option);
		}
		$resource->popup = implode(',', $optionlist);
		unset($resource->windowpopup);
	} else {
		$resource->popup = '';
	}
}

function equella_update_instance($equella) {
	// Given an object containing all the necessary data,
	// will update an existing instance with new data.

	$equella->timemodified = time();
	$equella->id = $equella->instance;
	equella_postprocess($equella);

	return update_record("equella", $equella);
}


function equella_delete_instance($id) {
	global $CFG;
	// Given an ID of an instance of this module,
	// this function will permanently delete the instance
	// and any data that depends on it.

	if (! $equella = get_record("equella", "id", $id)) {
		return false;
	}

	if ($equella->activation)
	{
		$url = str_replace("signon.do", "access/activationwebservice.do", $CFG->equella_url);
		$url = equella_appendtoken($url, 'write')."&activationUuid=".urlencode($equella->activation);
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$res = curl_exec($curl);
		curl_close($curl);
	}
	$result = true;


	if (! delete_records("equella", "id", $equella->id)) {
		$result = false;
	}

	return $result;
}


function equella_user_outline($course, $user, $mod, $equella) {
	$result = NULL;
	return $result;
}

function equella_user_complete($course, $user, $mod, $equella) {
	print_string("notsubmittedyet", "equella");
}

function equella_get_coursemodule_info($coursemodule) {
	global $CFG;

	$info = NULL;

	if( $resource = get_record("equella", "id", $coursemodule->instance) ) {
		require_once($CFG->libdir.'/filelib.php');

		$url = $resource->url;
		if( $ind = strrpos($url, '?') ) {
			$url = substr($url, 0, $ind);
		}

		$icon = mimeinfo("icon", $url);
		if( $icon != 'unknown.gif' ) {
			$info->icon ="f/$icon";
		} else {
			$info->icon ="f/web.gif";
		}

		if( !empty($resource->popup) ) {
           $info->extra = urlencode("onclick=\"this.target='equella{$resource->id}'; return openpopup('/mod/equella/view.php?inpopup=true&amp;id={$coursemodule->id}', 'equella{$resource->id}','{$resource->popup}');\"");
		}
	}

	return $info;
}

?>
