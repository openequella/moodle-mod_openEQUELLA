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

require_once($CFG->dirroot.'/mod/equella/common/lib.php');
require_once($CFG->dirroot.'/lib/filelib.php');
require_once($CFG->dirroot.'/course/lib.php');

define('EQUELLA_CONFIG_SELECT_RESTRICT_NONE', 'none');
define('EQUELLA_CONFIG_SELECT_RESTRICT_ITEMS_ONLY', 'itemonly');
define('EQUELLA_CONFIG_SELECT_RESTRICT_ATTACHMENTS_ONLY', 'attachmentonly');

function equella_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;

        default: return null;
    }
}

function equella_get_window_options() {
	return array('width', 'height', 'resizable', 'scrollbars', 'directories', 'location', 'menubar', 'toolbar', 'status');
}

function equella_get_courseId($courseid) {
	global $DB;
	$record = $DB->get_record("course", array('id' => $courseid));
	return $record->idnumber;
}

function equella_add_instance($equella) {
	// Given an object containing all the necessary data,
	// (defined by the form in mod.html) this function
	// will create a new instance and return the id number
	// of the new instance.
	global $DB, $USER;
	$equella->timecreated = time();
	$equella->timemodified = time();
	equella_postprocess($equella);
	return $DB->insert_record("equella", $equella);
}

function equella_postprocess(&$resource) {
	if( isset($resource->windowpopup) && $resource->windowpopup ) {
		$optionlist = array();
		foreach( equella_get_window_options() as $option ) {
			if (isset($resource->$option))
			{
				$optionlist[] = $option."=".$resource->$option;
				unset($resource->$option);
			}
		}
		$resource->popup = implode(',', $optionlist);
		unset($resource->windowpopup);
	} else {
		$resource->popup = '';
	}

	$pattern = "/(?P<uuid>[\w]{8}-[\w]{4}-[\w]{4}-[\w]{4}-[\w]{12})\/(?P<version>[0-9]*)\/(?P<path>.*)/";

	$url = $resource->url;
	preg_match($pattern, $url, $matches);
	$resource->uuid = $matches['uuid'];
	$resource->version=$matches['version'];
	$resource->path=$matches['path'];
}

function equella_update_instance($equella) {
	// Given an object containing all the necessary data,
	// will update an existing instance with new data.

	$equella->timemodified = time();
	$equella->id = $equella->instance;
	equella_postprocess($equella);

	global $DB;
	return $DB->update_record("equella", $equella);
}


function equella_delete_instance($id) {
	global $DB, $CFG;
	// Given an ID of an instance of this module,
	// this function will permanently delete the instance
	// and any data that depends on it.

	if (! $equella = $DB->get_record("equella", array("id" => $id))) {
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


	if (! $DB->delete_records("equella", array("id" => $equella->id))) {
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
	global $DB, $CFG;

	$info = new stdClass;

	if( $resource = $DB->get_record("equella", array("id" => $coursemodule->instance)) ) {
		require_once($CFG->libdir.'/filelib.php');

		$url = $resource->url;
		if( $ind = strrpos($url, '?') ) {
			$url = substr($url, 0, $ind);
		}
		$info->icon = equella_guess_icon($url);

		if( !empty($resource->popup) ) {
           $info->extra = "onclick=\"window.open('$CFG->wwwroot/mod/equella/view.php?inpopup=true&amp;id={$coursemodule->id}', '','{$resource->popup}'); return false;\"";
		}
	}

	return $info;
}

/**
 * Optimised mimetype detection from general URL.  Copied from /mod/url/locallib.php
 *
 * @param $fullurl
 * @return string mimetype
 */
function equella_guess_icon($fullurl) {
    global $CFG;
    require_once("$CFG->libdir/filelib.php");

    if (substr_count($fullurl, '/') < 3 or substr($fullurl, -1) === '/') {
        // most probably default directory - index.php, index.html, etc.
        return file_extension_icon('.htm');
    }

    $icon = file_extension_icon($fullurl);

    if ($icon === file_extension_icon('')) {
        return file_extension_icon('.htm');
    }

    return $icon;
}

?>
