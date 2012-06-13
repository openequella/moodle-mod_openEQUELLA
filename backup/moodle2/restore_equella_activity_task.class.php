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

require_once($CFG->dirroot.'/mod/equella/backup/moodle2/restore_equella_stepslib.php');
 
class restore_equella_activity_task extends restore_activity_task { 
	protected function define_my_settings() {
		// Nothing to do
	}

	protected function define_my_steps() {
		$this->add_step(new restore_equella_activity_structure_step('equella_structure', 'equella.xml'));
	}

    static public function define_decode_contents() {
        $contents = array();
        $contents[] = new restore_decode_content('equella', array('intro'), 'equella');
        return $contents;
    }

    static public function define_decode_rules() {
        $rules = array();
        $rules[] = new restore_decode_rule('EQUELLAINDEX', '/mod/equella/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('EQUELLAVIEWBYID', '/mod/equella/view.php?id=$1', 'course_module');
        return $rules;
    }

    static public function define_restore_log_rules() {
        $rules = array();
        $rules[] = new restore_log_rule('equella', 'add', 'view.php?id={course_module}', '{equella}');
        $rules[] = new restore_log_rule('equella', 'update', 'view.php?id={course_module}', '{equella}');
        $rules[] = new restore_log_rule('equella', 'view', 'view.php?id={course_module}', '{equella}');
        return $rules;
    }

    static public function define_restore_log_rules_for_course() {
        $rules = array();
        $rules[] = new restore_log_rule('equella', 'view all', 'index.php?id={course}', null);
        return $rules;
    }
}
