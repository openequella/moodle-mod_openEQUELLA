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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

namespace mod_equella\hooks;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../lib.php');

/**
 * Hook callback for core\hook\output\before_footer_html_generation.
 *
 * Injects the DnD upload AMD module on course-view pages when editing is on
 * and the intercept mode is set to META.
 */
class before_footer {

    /**
     * Callback invoked by the Moodle hook dispatcher.
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     * @return void
     */
    public static function callback(\core\hook\output\before_footer_html_generation $hook): void {
        global $PAGE, $COURSE, $CFG;

        // Only run on course-view pages.
        if (strpos($PAGE->pagetype, 'course-view') !== 0) {
            return;
        }

        // Only run when editing is enabled.
        if (!$PAGE->user_is_editing()) {
            return;
        }

        $intercept_setting = (int) get_config('equella', 'equella_intercept_files');

        if ($intercept_setting !== EQUELLA_CONFIG_INTERCEPT_META) {
            return;
        }

        $maxbytes = get_user_max_upload_file_size($PAGE->context, $CFG->maxbytes, $COURSE->maxbytes);
        if ($maxbytes === USER_CAN_IGNORE_FILE_SIZE_LIMITS) {
            $maxbytes = get_max_upload_file_size();
        }

        $config = [
            'courseId' => $COURSE->id,
            'maxBytes' => $maxbytes,
        ];

        $PAGE->requires->js_call_amd('mod_equella/dndupload', 'init', [$config]);
    }
}