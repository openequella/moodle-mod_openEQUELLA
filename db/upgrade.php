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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die;

function xmldb_equella_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    if ($oldversion < 2011012700) {
	    // Rename summary to intro
        $table = new xmldb_table('equella');
        $field = new xmldb_field('summary', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'name');
        if ($dbman->field_exists($table, $field))
        {
        	$dbman->rename_field($table, $field, 'intro');
        	upgrade_mod_savepoint(true, 2011012700, 'equella');
        }
    }

    if ($oldversion < 2011012701) {
		// Add field introformat
        $table = new xmldb_table('equella');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', 'intro');
        if (!$dbman->field_exists($table, $field))
        {
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

		foreach ($equella_items as $item)
		{
			$url = $item->url;
			preg_match($pattern, $url, $matches);
			$item->uuid = $matches['uuid'];
			$item->version=$matches['version'];
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

		foreach ($equella_items as $item)
		{
			$url = $item->url;
			preg_match($pattern, $url, $matches);
			$item->path=$matches['path'];
			$DB->update_record("equella", $item);
		}

        upgrade_mod_savepoint(true, 2011080500, 'equella');
    }

    if ($oldversion < 2012010901)
    {
    	$table = new xmldb_table('equella');
    	$field = new xmldb_field('attachmentuuid', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'path');
    	if (!$dbman->field_exists($table, $field))
    	{
    		$dbman->add_field($table, $field);
    	}

    	upgrade_mod_savepoint(true, 2012010901, 'equella');
    }

    if ($oldversion < 2012082806)
    {
        require_once($CFG->dirroot . '/mod/equella/lib.php');
        $records = $DB->get_recordset('equella');
        $pattern = "/(?P<uuid>[\w]{8}-[\w]{4}-[\w]{4}-[\w]{4}-[\w]{12})\/(?P<version>[0-9]*)\/(?P<path>.*)/";

        foreach ($records as $resource)
        {
            preg_match($pattern, $resource->url, $matches);
            if (empty($resource->uuid)) {
                $resource->uuid = $matches['uuid'];
            }
            if (empty($resource->version)) {
                $resource->version = $matches['version'];
            }
            if (empty($resource->path)) {
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

        $records = $DB->get_records('equella');
        foreach ($records as $eq) {
            $eq->ltisalt = uniqid('', true);
            $DB->update_record("equella", $eq);
        }

        upgrade_mod_savepoint(true, 2013080800, 'equella');
    }

    if ($oldversion < 2013080801) {

        $records = $DB->get_records('equella');
        foreach ($records as $eq) {
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

    return true;
}
