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

require_once($CFG->dirroot.'/mod/equella/backup/moodle2/backup_equella_stepslib.php');
 
class backup_equella_activity_task extends backup_activity_task { 
    protected function define_my_settings() {
        // Nothing to do
    }
 
    protected function define_my_steps() {
        $this->add_step(new backup_equella_activity_structure_step('equella_structure', 'equella.xml'));
    }
 
    static public function encode_content_links($content) {
		global $CFG;

		$base = preg_quote($CFG->wwwroot.'/mod/equella','#');

		$pattern = '#('.$base.'/index\.php\?id=)([0-9]+)#';
		$replacement = '$@EQUELLAINDEX*$2@$';
		$content = preg_replace($pattern, $replacement, $content);

		$pattern = '#('.$base.'/view\.php\?id=)([0-9]+)#';
		$replacement = '$@EQUELLAVIEWBYID*$2@$';
		$content = preg_replace($pattern, $replacement, $content);

		return $content;
    }
}
