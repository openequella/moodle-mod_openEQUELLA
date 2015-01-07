<?php

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'mod_equella\task\cron_task',
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
);
