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

/**
 * This file replaces the legacy STATEMENTS section in db/install.xml,
 * lib.php/modulename_install() post installation hook and partially defaults.php
 *
 * @package    mod
 * @subpackage equella
 * @author     Dongsheng Cai
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This is called at the beginning of the uninstallation process to give the module
 * a chance to clean-up its hacks, bits etc. where possible.
 *
 * @return bool true if success
 */
function xmldb_equella_uninstall() {
    global $DB;

    //$dbman = $DB->get_manager();

    // XXX
    // unset_all_config_for_plugin() in adminlib.php only deletes
    // options start with 'equella_', we had 'equellaopeninnewwindow'
    // before, so we need to delete options start with 'equella' only
    $like = $DB->sql_like('name', '?', true, true, false, '|');
    $params = array($DB->sql_like_escape('equella', '|') . '%');
    $DB->delete_records_select('config', $like, $params);

    return true;
}
