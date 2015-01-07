<?php
namespace mod_equella\task;

if (class_exists('core\\task\\scheduled_task')) {
    class cron_task extends \core\task\scheduled_task {
        public function get_name() {
            return get_string('crontask', 'mod_equella');
        }

        public function execute() {
            global $CFG;
            require_once($CFG->dirroot . '/mod/equella/lib.php');
        }
    }
}
