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
$functions = array(
    'equella_list_courses_for_user' => array(
        'classname'   => 'equella_external',
        'methodname'  => 'list_courses_for_user',
        'classpath'   => 'mod/equella/externallib.php',
        'description' => 'List the courses for the given user.',
        'type'        => 'read',
        'capabilities' => 'moodle/course:view'
    ),
    'equella_list_sections_for_course' => array(
        'classname'   => 'equella_external',
        'methodname'  => 'list_sections_for_course',
        'classpath'   => 'mod/equella/externallib.php',
        'description' => 'List the sections for the given course.',
        'type'        => 'read',
        'capabilities' => 'moodle/course:view'
    ),
    'equella_add_item_to_course' => array(
        'classname'   => 'equella_external',
        'methodname'  => 'add_item_to_course',
        'classpath'   => 'mod/equella/externallib.php',
        'description' => 'Add an EQUELLA item to a given course by a given user.',
        'type'        => 'write',
        'capabilities' => 'moodle/course:manageactivities'
    ),
    'equella_test_connection' => array(
        'classname'   => 'equella_external',
        'methodname'  => 'test_connection',
        'classpath'   => 'mod/equella/externallib.php',
        'description' => 'Tests the connection from EQUELLA to Moodle.  Returns success=>{param} if successful. (Where {param} is supplied when calling).',
        'type'        => 'read',
    ),
    'equella_find_usage_for_item' => array(
        'classname'   => 'equella_external',
        'methodname'  => 'find_usage_for_item',
        'classpath'   => 'mod/equella/externallib.php',
        'description' => 'List all the locations that the supplied item is used.',
        'type'        => 'read',
        'capabilities' => 'moodle/course:view'
    ),
    'equella_find_all_usage' => array(
        'classname'   => 'equella_external',
        'methodname'  => 'find_all_usage',
        'classpath'   => 'mod/equella/externallib.php',
        'description' => 'List all the locations that Equella content is used.',
        'type'        => 'read',
        'capabilities' => 'moodle/course:view'
    ),
    'equella_unfiltered_usage_count' => array(
        'classname'   => 'equella_external',
        'methodname'  => 'unfiltered_usage_count',
        'classpath'   => 'mod/equella/externallib.php',
        'description' => 'Get the number of results that would be returned by equella_find_all_usages without a course ID and folder ID value, and with an unlimited count',
        'type'        => 'read',
        'capabilities' => 'moodle/course:view'
    ),
    'equella_get_course_code' => array(
        'classname'   => 'equella_external',
        'methodname'  => 'get_course_code',
        'classpath'   => 'mod/equella/externallib.php',
        'description' => 'Returns the course code for the supplied course id',
        'type'        => 'read',
        'capabilities' => 'moodle/course:view'
    ),
    'equella_edit_item' => array(
        'classname'   => 'equella_external',
        'methodname'  => 'edit_item',
        'classpath'   => 'mod/equella/externallib.php',
        'description' => 'Modify an item in moodle',
        'type'        => 'write',
        'capabilities' => 'moodle/course:manageactivities'
    ),
    'equella_move_item' => array(
        'classname'   => 'equella_external',
        'methodname'  => 'move_item',
        'classpath'   => 'mod/equella/externallib.php',
        'description' => 'Move an item in moodle',
        'type'        => 'write',
        'capabilities' => 'moodle/course:manageactivities'
    ),
    'equella_delete_item' => array(
        'classname'   => 'equella_external',
        'methodname'  => 'delete_item',
        'classpath'   => 'mod/equella/externallib.php',
        'description' => 'Deletes an item in moodle',
        'type'        => 'write',
        'capabilities' => 'moodle/course:manageactivities'
    )
);

$functionnames = array_keys($functions);

$services = array(
    'equellaservice' => array(
        'functions' => $functionnames,
        'requiredcapability' => 'moodle/course:manageactivities',
        'restrictedusers' => 1,
        'enabled' => 1,
    )
);
