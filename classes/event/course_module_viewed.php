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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

namespace mod_equella\event;

defined('MOODLE_INTERNAL') || die();

if (class_exists('core\\event\\course_module_viewed')) {
    class course_module_viewed extends \core\event\course_module_viewed {

        /**
         * Init method.
         *
         * @return void
         */
        protected function init() {
            $this->data['objecttable'] = 'equella';
            $this->data['crud'] = 'r';
            $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        }
    }
}
