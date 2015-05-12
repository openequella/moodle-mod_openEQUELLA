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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once('../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/equella/locallib.php');
require_once($CFG->dirroot . '/mod/equella/lib.php');
require_once($CFG->libdir . '/formslib.php');

require_login();
$courseid = required_param('id', PARAM_INT);
$PAGE->set_pagelayout('incourse');

$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);
require_capability('moodle/course:manageactivities', $coursecontext);
$PAGE->set_context($coursecontext);
$url = new moodle_url('/mod/equella/tools/generator.php', array('id' => $course->id));
$PAGE->set_url($url);
$PAGE->set_heading($course->fullname);

class equella_generator_form extends moodleform {
    var $form;
    private $course;

    function equella_generator_form($actionurl, $course) {
        global $CFG;
        $this->course = $course;
        parent::moodleform($actionurl);
    }
    function definition() {
        global $CFG;
        $mform =& $this->_form;
        $mform->addElement('hidden', 'id', $this->course->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'equrl', get_string('url'), array('size'=>'128'));
        $mform->setType('equrl', PARAM_URL);

        $mform->addElement('text', 'uuid', 'attachmentUuid');
        $mform->setType('uuid', PARAM_TEXT);


        $mform->addElement('text', 'count', 'Number of resources');
        $mform->setType('count', PARAM_INT);

        $this->add_action_buttons();
    }
}

$form = new equella_generator_form($url, $course);
if ($data = $form->get_data()) {
    $courseid = $data->id;
    $equrl = $data->equrl;
    $count = $data->count;
    $attachmentuuid = $data->uuid;
    $module = $DB->get_record('modules', array('name'=>'equella'));
    for ($i=1; $i<=$count;$i++) {
        $eq = new stdclass;
        $eq->name = uniqid() . '. ' . $equrl;
        $eq->intro = $equrl;
        $eq->introformat = FORMAT_HTML;
        $eq->url = $equrl;
        $eq->course = $courseid;
        $eq->modulename = 'equella';
        $eq->module = $module->id;
        $eq->attachmentuuid = $attachmentuuid;
        $eq->mimetype = mimeinfo('type', $equrl);
        try {
            $moduleid = equella_add_instance($eq);
        } catch (Exception $ex) {
            throw new equella_exception('Failed to create EQUELLA resource.');
        }
        $eq->instance = $moduleid;
        if (! $eq->coursemodule = add_course_module($eq) ) {
            print_error('cannotaddcoursemodule');
        }

        if (! $addedsectionid = course_add_cm_to_section($eq->course, $eq->coursemodule, 0) ) {
            print_error('cannotaddcoursemoduletosection');
        }
        if (! $DB->set_field('course_modules', 'section', $addedsectionid, array('id' => $eq->coursemodule))) {
            print_error('Could not update the course module with the correct section');
        }
        set_coursemodule_visible($eq->coursemodule, true);
    }
    $courseurl = new moodle_url('/course/view.php', array('id'=>$courseid));
    redirect($courseurl);
}
echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
