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

defined('MOODLE_INTERNAL') || die();

class backup_equella_activity_structure_step extends backup_activity_structure_step {
	protected function define_structure() {
		$equella = new backup_nested_element('equella', array('id'),
			array('course','name', 'intro', 'introformat', 'timecreated',
			'timemodified', 'url', 'popup', 'activation', 'uuid', 'version', 'path', 'attachmentuuid'));
		$equella->set_source_table('equella', array('id' => backup::VAR_ACTIVITYID));
		$equella->annotate_files('mod_equella', 'intro', null);
		return $this->prepare_activity_structure($equella);
    }
}
