<?php

// This file is part of Moodle - http://moodle.org/
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
/**
 * Library to handle drag and drop course reading uploads
 *
 * @package    mod_equella drag and drop
 * @copyright  2015 Lei Zhang
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/repository/upload/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/dnduploadlib.php');

class equella_dndupload_ajax_processor extends dndupload_ajax_processor {

    protected $metadata = null;

    protected $displayname = null;
    protected $copyright = null;
    protected $itemdescription = null;
    protected $itemkeyword = null;

    /**
     * Set up some basic information needed to handle the upload
     *
     * @param int $courseid The ID of the course we are uploading to
     * @param int $section The section number we are uploading to
     * @param string $type The type of upload (as reported by the browser)
     * @param string $modulename The name of the module requested to handle this upload
     */
    public function __construct($courseid, $section, $type, $metadata) {
        global $DB;

        parent::__construct($courseid, $section, $type, 'equella');
        $this->metadata = $metadata;
    }

    /**
     * Process the upload - creating the module in the course and returning the result to the browser
     *
     * @param string $displayname optional the name (from the browser) to give the course module instance
     * @param string $content optional the content of the upload (for non-file uploads)
     */
    public function process($displayname = null, $content = null) {
        require_capability('moodle/course:manageactivities', $this->context);

        if ($this->is_file_upload()) {
            require_capability('moodle/course:managefiles', $this->context);
            if ($content != null) {
                throw new moodle_exception('fileuploadwithcontent', 'moodle');
            }
        } else {
            if (empty($content)) {
                throw new moodle_exception('dnduploadwithoutcontent', 'moodle');
            }
        }
        require_sesskey();
        $this->displayname = $this->metadata->eqdndtitle;
        $this->copyright = $this->metadata->eqdndcopyright;
        $this->itemdescription = $this->metadata->eqdnddesc;
        $this->itemkeyword = $this->metadata->eqdndkw;

        if ($this->is_file_upload()) {
            $this->handle_file_upload();
        } else {
            throw new coding_exception("Equella drag-n-drop module should not be requested to handle non-file uploads");
        }
    }

      /**
     * Gather together all the details to pass on to the mod, so that it can initialise it's
     * own database tables
     *
     * @param int $draftitemid optional the id of the draft area containing the file (for file uploads)
     * @param string $content optional the content dropped onto the course (for non-file uploads)
     * @return object data to pass on to the mod, containing:
     *              string $type the 'type' as registered with dndupload_handler (or 'Files')
     *              object $course the course the upload was for
     *              int $draftitemid optional the id of the draft area containing the files
     *              int $coursemodule id of the course module that has already been created
     *              string $displayname the name to use for this activity (can be overriden by the mod)
     */
    protected function prepare_module_data($draftitemid = null, $content = null) {
        $data = new stdClass();
        $data->type = $this->type;
        $data->course = $this->course;
        if ($draftitemid) {
            $data->draftitemid = $draftitemid;
        } else if ($content) {
            $data->content = $content;
        }
        $data->coursemodule = $this->cm->id;
        $data->displayname = $this->displayname;
        $data->copyright = $this->copyright;
        $data->itemdescription = $this->itemdescription;
        $data->itemkeyword = $this->itemkeyword;

        return $data;
    }

}
