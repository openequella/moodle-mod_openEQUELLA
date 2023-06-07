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

namespace mod_equella\task;

use core\task\adhoc_task;
use dml_exception;
use stdClass;

global $CFG;
require_once($CFG->dirroot . '/mod/lti/lib.php');
require_once($CFG->dirroot . '/mod/lti/locallib.php');

class lti13_migration_task extends adhoc_task
{
    /**
     * Create a new LTI external tool instance based on the provided course module info, details of the OEQ instance used in this
     * course module and the configurations of an LTI external tool.
     *
     * @param $courseModule stdClass An instance of CourseModule to be updated to use LTI 1.3 as its module.
     * @param $ltiToolDetails stdClass Details of the LTI external tool to be used to generate a new LTI instance.
     *
     * @throws dml_exception
     */
    private function createLtiInstance($courseModule, $ltiToolDetails): int
    {
        global $DB;

        $oeqInstance = $DB->get_record('equella', array('id' => $courseModule->instance));
        $ltiConfigurations = $ltiToolDetails->configurations;
        $ltiToolID = $ltiToolDetails->id;

        $lti = new stdClass();

        $lti->typeid = $ltiToolID;

        $lti->course = $courseModule->course;
        $lti->showdescriptionlaunch = $courseModule->showdescription;
        $lti->coursemodule = $courseModule->id;

        $lti->icon = unserialize($oeqInstance->metadata)['thumbnail'];
        $lti->intro = $oeqInstance->intro;
        $lti->introformat = $oeqInstance->introformat;
        $lti->name = $oeqInstance->name;
        $lti->timecreated = $oeqInstance->timecreated;
        $lti->timemodified = $oeqInstance->timemodified;
        $lti->toolurl = $oeqInstance->url;

        $lti->instructorchoicesendname = $ltiConfigurations['sendname'] ?? 1;
        $lti->instructorchoicesendmailaddr = $ltiConfigurations['sendemailaddr'] ?? 1;
        $lti->instructorchoiceacceptgrades = $ltiConfigurations['acceptgrades'] == LTI_SETTING_ALWAYS ? $ltiConfigurations['acceptgrades'] : 0;
        $lti->instructorchoiceallowsetting = $ltiConfigurations['ltiservice_toolsettings'] ?? null;
        $lti->instructorchoiceallowroster = $ltiConfigurations['allowroster ltiservice_memberships'] ?? null;
        $lti->instructorcustomparameters = $ltiConfigurations['customparameters'] ?? "";
        $lti->launchcontainer = $ltiConfigurations['launchcontainer'];

        return lti_add_instance($lti, null); // The second parameter is not used at all in this function.;
    }

    /**
     * Update an existing course module to use a new LTI instance. To achieve this, the value of `module` needs to be
     * updated to the ID of LTI module, and the value of instance needs to be updated to the ID of a new LTI instance.
     *
     * @param $courseModule stdClass An instance of CourseModule to be updated to use LTI 1.3 as its module.
     * @param $ltiToolDetails stdClass Details of the LTI external tool to be used to generate a new LTI instance.
     * @param $ltiModuleID int ID of the LTI module.
     *
     * @throws dml_exception
     */
    private function updateCourseModule($courseModule, $ltiToolDetails, $ltiModuleID): void
    {
        global $DB;

        $courseModule->module = $ltiModuleID;
        $courseModule->instance = $this->createLtiInstance($courseModule, $ltiToolDetails);
        $DB->update_record("course_modules", $courseModule);
    }

    /**
     * Return the ID of Moodle module for the provided module name.
     *
     * @param $moduleName string Name of a Moodle module.
     *
     * @throws dml_exception
     */
    private  function getModuleId($moduleName) {
        global $DB;
        return $DB->get_field_sql("SELECT id FROM {modules} WHERE name = '$moduleName'");
    }

    public function execute()
    {
        global $DB;

        try {
            $oeqMoodleModuleID = $this->getModuleId('equella');
            $ltiModuleID = $this->getModuleId('lti');

            $ltiTool = new stdClass();
            $ltiTypeName = $this->get_custom_data();
            $ltiTypeID = $DB->get_field_sql("SELECT id FROM {lti_types} WHERE name = '" . $ltiTypeName . "'");
            $ltiTool->id = $ltiTypeID;
            $ltiTool->configurations = lti_get_type_config($ltiTypeID);

            $courseModuleList = $DB->get_records_sql("SELECT * FROM {course_modules} cm WHERE cm.module = " . $oeqMoodleModuleID);

            foreach ($courseModuleList as $cm) {
                echo "Processing course ID: " . $cm->course . " openEQUELLA resource ID: " . $cm->instance . "\n";
                $this->updateCourseModule($cm, $ltiTool, $ltiModuleID);
            }

            echo "LTI 1.3 migration has been successfully completed!";
        } catch (dml_exception $e) {
            echo "LTI 1.3 migration failed: $e";
        }
    }


}