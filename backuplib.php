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

function equella_backup_mods($bf, $preferences) {

	global $CFG;

	$status = true;

	//Iterate over chat table
	$records = get_records("equella", "course", $preferences->backup_course, "id");
	if ($records) {
		foreach ($records as $eqres) {
			if (backup_mod_selected($preferences, 'equella', $eqres->id)) {
				$status = chat_backup_one_mod($bf, $preferences, $eqres);
			}
		}
	}
	return $status;
}

function equella_backup_one_mod($bf, $preferences, $eqres) {

	global $CFG;

	if (is_numeric($eqres)) {
		$eqres = get_record('equella', 'id', $eqres);
	}

	$status = true;
	
	//Start mod
	fwrite($bf, start_tag("MOD", 3, true));
	//Print chat data
	fwrite($bf, full_tag("ID", 4, false, $eqres->id));
	fwrite($bf, full_tag("MODTYPE", 4, false, "equella"));
	fwrite($bf, full_tag("NAME", 4, false, $eqres->name));
	fwrite($bf, full_tag("URL", 4, false, $eqres->url));
	fwrite($bf, full_tag("SUMMARY", 4, false, $eqres->summary));
	fwrite($bf, full_tag("POPUP", 4, false, $eqres->popup));
	fwrite($bf, full_tag("TIMEMODIFIED", 4, false, $eqres->timemodified));
	fwrite($bf, full_tag("TIMECREATED", 4, false, $eqres->timecreated));
	fwrite($bf, full_tag("EQUELLAURL", 4, false, $CFG->equella_url));

	//End mod
	$status = fwrite($bf, end_tag("MOD", 3, true));

	return $status;
}

function equella_encode_content_links($content, $preferences) {

	global $CFG;

    $base = preg_quote($CFG->wwwroot,"/");

    //Link to the list of resources
    $buscar="/(".$base."\/mod\/equella\/index.php\?id\=)([0-9]+)/";
    $result= preg_replace($buscar,'$@EQUELLAINDEX*$2@$',$content);

    //Link to view
    $buscar="/(".$base."\/mod\/equella\/view.php\?id\=)([0-9]+)/";
    $result= preg_replace($buscar,'$@EQUELLAVIEWBYID*$2@$',$result);

    return $result;
}

function equella_check_backup_mods($course, $user_data = false, $backup_unique_code, $instances = null) {
	$info = array ();
	$info[0][0] = get_string("modulenameplural","equella");
	$info[0][1] = count_records("equella", "course", $course);
	return $info;
}
?>
