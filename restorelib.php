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

require_once ($CFG->dirroot . '/mod/equella/lib.php');

function equella_restore_mods($mod, $restore) {

	global $CFG;

	$status = true;

	//Get record from backup_ids
	$data = backup_getid($restore->backup_unique_code, $mod->modtype, $mod->id);

	if ($data) {
		//Now get completed xmlized object
		$info = $data->info;
		$eqres->course = $restore->course_id;
		$eqres->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
		$eqres->url = backup_todb($info['MOD']['#']['URL']['0']['#']);
		$eqres->summary = backup_todb($info['MOD']['#']['SUMMARY']['0']['#']);
		$eqres->popup = backup_todb($info['MOD']['#']['POPUP']['0']['#']);
		$eqres->timecreated = backup_todb($info['MOD']['#']['TIMECREATED']['0']['#']);
		$eqres->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

		$newid = insert_record("equella", $eqres);
		if ($newid) {
			backup_putid($restore->backup_unique_code, $mod->modtype, $mod->id, $newid);
		}
	}
	return $status;
}

function equella_decode_content_links($content, $restore) {
	global $CFG;

	$result = $content;

	//Link to the list of resources

	$searchstring = '/\$@(EQUELLAINDEX)\*([0-9]+)@\$/';
	//We look for it
	preg_match_all($searchstring, $content, $foundset);
	//If found, then we are going to look for its new id (in backup tables)
	if ($foundset[0]) {
		//print_object($foundset);                                     //Debug
		//Iterate over foundset[2]. They are the old_ids
		foreach ($foundset[2] as $old_id) {
			//We get the needed variables here (course id)
			$rec = backup_getid($restore->backup_unique_code, "course", $old_id);
			//Personalize the searchstring
			$searchstring = '/\$@(EQUELLAINDEX)\*(' . $old_id . ')@\$/';
			//If it is a link to this course, update the link to its new location
			if ($rec->new_id) {
				//Now replace it
				$result = preg_replace($searchstring, $CFG->wwwroot . '/mod/equella/index.php?id=' . $rec->new_id, $result);
			} else {
				//It's a foreign link so leave it as original
				$result = preg_replace($searchstring, $restore->original_wwwroot . '/mod/equella/index.php?id=' . $old_id, $result);
			}
		}
	}

	//Link to resource view by moduleid

	$searchstring = '/\$@(EQUELLAVIEWBYID)\*([0-9]+)@\$/';
	//We look for it
	preg_match_all($searchstring, $result, $foundset);
	//If found, then we are going to look for its new id (in backup tables)
	if ($foundset[0]) {
		//Iterate over foundset[2]. They are the old_ids
		foreach ($foundset[2] as $old_id) {
			//We get the needed variables here (course_modules id)
			$rec = backup_getid($restore->backup_unique_code, "course_modules", $old_id);
			//Personalize the searchstring
			$searchstring = '/\$@(EQUELLAVIEWBYID)\*(' . $old_id . ')@\$/';
			//If it is a link to this course, update the link to its new location
			if ($rec->new_id) {
				//Now replace it
				$result = preg_replace($searchstring, $CFG->wwwroot . '/mod/equella/view.php?id=' . $rec->new_id, $result);
			} else {
				//It's a foreign link so leave it as original
				$result = preg_replace($searchstring, $restore->original_wwwroot . '/mod/equella/view.php?id=' . $old_id, $result);
			}
		}
	}

	return $result;
}
?>
