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
defined('MOODLE_INTERNAL') || die();

function xmldb_equella_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    if ($oldversion < 2011012700) {
        // Rename summary to intro
        $table = new xmldb_table('equella');
        $field = new xmldb_field('summary', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'name');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'intro');
            upgrade_mod_savepoint(true, 2011012700, 'equella');
        }
    }

    if ($oldversion < 2011012701) {
        // Add field introformat
        $table = new xmldb_table('equella');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', 'intro');
        if (!$dbman->field_exists($table, $field)) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            upgrade_mod_savepoint(true, 2011012701, 'equella');
        }
    }

    if ($oldversion < 2011072600) {
        $table = new xmldb_table('equella');
        $field1 = new xmldb_field('uuid', XMLDB_TYPE_TEXT, '40', null, null, null, null, 'activation');
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        $field2 = new xmldb_field('version', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'uuid');
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        $equella_items = $DB->get_records('equella');
        $pattern = "/(?P<uuid>[\w]{8}-[\w]{4}-[\w]{4}-[\w]{4}-[\w]{12})\/(?P<version>[0-9]*)/";

        foreach($equella_items as $item) {
            $url = $item->url;
            preg_match($pattern, $url, $matches);
            $item->uuid = $matches['uuid'];
            $item->version = $matches['version'];
            $DB->update_record("equella", $item);
        }

        upgrade_mod_savepoint(true, 2011072600, 'equella');
    }

    if ($oldversion < 2011080500) {
        $table = new xmldb_table('equella');
        $field = new xmldb_field('path', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'version');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $equella_items = $DB->get_records('equella');
        $pattern = "/(?P<uuid>[\w]{8}-[\w]{4}-[\w]{4}-[\w]{4}-[\w]{12})\/(?P<version>[0-9]*)\/(?P<path>.*)/";

        foreach($equella_items as $item) {
            $url = $item->url;
            preg_match($pattern, $url, $matches);
            $item->path = $matches['path'];
            $DB->update_record("equella", $item);
        }

        upgrade_mod_savepoint(true, 2011080500, 'equella');
    }

    if ($oldversion < 2012010901) {
        $table = new xmldb_table('equella');
        $field = new xmldb_field('attachmentuuid', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'path');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2012010901, 'equella');
    }

    if ($oldversion < 2012082806) {
        require_once ($CFG->dirroot . '/mod/equella/lib.php');
        $records = $DB->get_recordset('equella');
        $pattern = "/(?P<uuid>[\w]{8}-[\w]{4}-[\w]{4}-[\w]{4}-[\w]{12})\/(?P<version>[0-9]*)\/(?P<path>.*)/";

        foreach($records as $resource) {
            preg_match($pattern, $resource->url, $matches);
            if (empty($resource->uuid) && !empty($matches['uuid'])) {
                $resource->uuid = $matches['uuid'];
            }
            if (empty($resource->version) && !empty($matches['version'])) {
                $resource->version = $matches['version'];
            }
            if (empty($resource->path) && !empty($matches['path'])) {
                $resource->path = $matches['path'];
            }

            $DB->update_record('equella', $resource);
        }

        upgrade_mod_savepoint(true, 2012082806, 'equella');
    }

    if ($oldversion < 2013080800) {

        // Define field ltisalt to be added to equella.
        $table = new xmldb_table('equella');
        $field = new xmldb_field('ltisalt', XMLDB_TYPE_CHAR, '40', null, null, null, null, 'attachmentuuid');

        // Conditionally launch add field ltisalt.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $records = $DB->get_recordset('equella');
        foreach($records as $eq) {
            $eq->ltisalt = uniqid('', true);
            $DB->update_record("equella", $eq);
        }

        upgrade_mod_savepoint(true, 2013080800, 'equella');
    }

    if ($oldversion < 2013080801) {

        $records = $DB->get_recordset('equella');
        foreach($records as $eq) {
            // check if attachmentuuid field exists
            if (preg_match('/[\w]{8}-[\w]{4}-[\w]{4}-[\w]{4}-[\w]{12}/', $eq->attachmentuuid)) {
                // not using attachment.uuid, fixing it up
                if (!strpos($eq->url, 'attachment.uuid')) {
                    $pattern = "#(.*)/([\w]{8}-[\w]{4}-[\w]{4}-[\w]{4}-[\w]{12})\/([0-9]*)\/(.*)#";
                    $replacement = '${1}/${2}/${3}/?attachment.uuid=' . $eq->attachmentuuid;
                    $attachementurl = preg_replace($pattern, $replacement, $eq->url);
                    $eq->url = $attachementurl;
                    $DB->update_record('equella', $eq);
                }
            }
        }

        upgrade_mod_savepoint(true, 2013080801, 'equella');
    }

    if ($oldversion < 2013100100) {
        $records = $DB->get_recordset('equella');
        foreach($records as $eq) {
            if (!empty($eq->popup) && !strpos($eq->popup, 'resizable')) {
                $eq->popup .= ',resizable=1';
                $DB->update_record('equella', $eq);
                rebuild_course_cache($eq->course, true);
            }
        }
        upgrade_mod_savepoint(true, 2013100100, 'equella');
    }

    if ($oldversion < 2013112501) {
        require_once ("$CFG->libdir/filelib.php");

        // Define field mimetype to be added to equella.
        $table = new xmldb_table('equella');
        $field = new xmldb_field('mimetype', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'url');

        // Conditionally launch add field mimetype.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $records = $DB->get_recordset('equella');
        foreach($records as $eq) {
            $mimetype = mimeinfo('type', $eq->url);
            $eq->mimetype = $mimetype;
            $DB->update_record('equella', $eq);
            rebuild_course_cache($eq->course, true);
        }
        // Newmodule savepoint reached.
        upgrade_mod_savepoint(true, 2013112501, 'equella');
    }

    $newversion = 2014061103;
    if ($oldversion < $newversion) {
        require_once ($CFG->libdir . "/datalib.php");
        $records = $DB->get_recordset('equella', array('course'=>0));
        foreach($records as $eq) {
            if ($cm = get_coursemodule_from_instance('equella', $eq->id)) {
                $eq->course = $cm->course;
                $DB->update_record('equella', $eq);
            }
        }
        upgrade_mod_savepoint(true, $newversion, 'equella');
    }

    if ($oldversion < 2014090902) {
        if (!empty($CFG->equellaopeninnewwindow) && empty($CFG->equella_open_in_new_window)) {
            set_config('equella_open_in_new_window', $CFG->equellaopeninnewwindow);
            unset_config('equellaopeninnewwindow');
        }
        upgrade_mod_savepoint(true, 2014090902, 'equella');
    }

    if ($oldversion < 2014091800) {
        require_once ($CFG->dirroot . '/mod/equella/lib.php');
        if ((int)$CFG->equella_intercept_files == EQUELLA_CONFIG_INTERCEPT_FULL) {
            set_config('equella_intercept_files', EQUELLA_CONFIG_INTERCEPT_ASK);
        }

        upgrade_mod_savepoint(true, 2014091800, 'equella');
    }

    if ($oldversion < 2015041401) {

        // Define field metadata to be added to equella.
        $table = new xmldb_table('equella');

        $field = new xmldb_field('filename', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'ltisalt');

        // Conditionally launch add field filename.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('metadata', XMLDB_TYPE_TEXT, null, null, null, null, null, 'filename');

        // Conditionally launch add field metadata.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Equella savepoint reached.
        upgrade_mod_savepoint(true, 2015041401, 'equella');
    }

    if($oldversion < 2025021101){
        // These keys are not part of admin settings but are stored in $CFG
        $excluded_keys = [
            'equella_soap_disable_ssl_check',
            'equella_oauth_access_token',
            'equella_lti_lis_callback',
            'equella_oauth_client_id'
        ];
        foreach ($CFG as $x => $y){
            if(strpos($x, 'equella')!==false){
                if (!in_array($x, $excluded_keys, true)) {
                    set_config($x, $y, 'equella');
                    unset_config($x);
                }

            }
        }

        upgrade_mod_savepoint(true, 2025021101, 'equella');
    }

    return true;
}
