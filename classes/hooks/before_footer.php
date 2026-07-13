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

        if (!self::is_intercept_active() || !self::is_suitable_page_state()) {
            return;
        }

        $maxbytes = self::calculate_max_file_size();

        $config = [
            'courseId' => $COURSE->id,
            'maxBytes' => $maxbytes,
        ];

        $PAGE->requires->js_call_amd('mod_equella/dndupload', 'init', [$config]);
    }

    /**
     * Returns true if the equella intercept setting is set to META.
     *
     * @return bool
     */
    private static function is_intercept_active(): bool {
        $intercept_setting = (int) get_config('equella', 'equella_intercept_files');
        return $intercept_setting === EQUELLA_CONFIG_INTERCEPT_META;
    }

    /**
     * Returns true if the current page is a course-view page with editing enabled.
     *
     * @return bool
     */
    private static function is_suitable_page_state(): bool {
        global $PAGE;
        return strpos($PAGE->pagetype, 'course-view') === 0 && $PAGE->user_is_editing();
    }

    /**
     * Returns the effective maximum upload file size for the current user and course.
     *
     * @return int
     */
    private static function calculate_max_file_size(): int {
        global $PAGE, $COURSE, $CFG;

        $maxbytes = get_user_max_upload_file_size($PAGE->context, $CFG->maxbytes, $COURSE->maxbytes);
        if ($maxbytes === USER_CAN_IGNORE_FILE_SIZE_LIMITS) {
            $maxbytes = get_max_upload_file_size();
        }

        return $maxbytes;
    }
}