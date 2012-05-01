<?php

// This file is part of the EQUELLA Moodle Integration - http://code.google.com/p/equella-moodle-module/
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

function xmldb_equella_upgrade($oldversion=0)
{
	$result = 1;
	if ($result && $oldversion < 2009101300) {

		/// Changing type of field name on table equella to text
		$table = new XMLDBTable('equella');
		$field = new XMLDBField('name');
		$field->setAttributes(XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null, 'course');

		/// Launch change of type for field name
		$result = $result && change_field_type($table, $field);
	}
	if ($result && $oldversion < 2009101300) {

		/// Define field summary to be added to equella
		$table = new XMLDBTable('equella');
		$field = new XMLDBField('summary');
		$field->setAttributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null, 'url');

		/// Launch add field summary
		$result = $result && add_field($table, $field);
	}
	if ($result && $oldversion < 2009101300) {

		/// Define field popup to be added to equella
		$table = new XMLDBTable('equella');
		$field = new XMLDBField('popup');
		$field->setAttributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null, 'summary');

		/// Launch add field popup
		$result = $result && add_field($table, $field);
	}
	if ($result && $oldversion < 2009102700) {

		/// Define field activation to be added to equella
        $table = new XMLDBTable('equella');
        $field = new XMLDBField('activation');
        $field->setAttributes(XMLDB_TYPE_CHAR, '40', null, null, null, null, null, null, 'popup');

		/// Launch add field activation
        $result = $result && add_field($table, $field);
    }
	return $result;
}
