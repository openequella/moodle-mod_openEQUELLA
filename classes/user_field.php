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

namespace mod_equella;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * This class handles the retrieval and management of user fields that are used
 * for Single Sign-On (SSO) identification.
 *
 * @package    mod_equella
 */
class user_field {

    /**
     * A list of supported fields from {user} table which are default name fields to be used
     */
    const DEFAULT_SUPPORTED_FIELDS = [
        'username',
        'idnumber',
        'email',
    ];

    /**
     * A list of supported types of custom profile fields.
     */
    const SUPPORTED_PROFILE_FIELDS_TYPES = [
        'text',
    ];

    /**
     * Prefix used for custom profile fields in the conf.
     */
    const CUSTOM_FIELD_PREFIX = 'profile_field_';

    /**
     * Return an associative array of supported user fields (default and custom)
     *
     * @return string[]
     */
    public static function get_supported_fields(): array {
        $supportedfields = [];

        foreach (self::DEFAULT_SUPPORTED_FIELDS as $name) {
            $supportedfields[$name] = get_string($name);
        }

        $customfields = profile_get_custom_fields(true);

        if (!empty($customfields)) {
            $customfieldoptions = [];
            foreach ($customfields as $customfield) {
                if (in_array($customfield->datatype, self::SUPPORTED_PROFILE_FIELDS_TYPES)) {
                    $prefixedKey = self::prefix_custom_profile_field($customfield->shortname);
                    $customfieldoptions[$prefixedKey] = $customfield->name;
                }
            }

            $supportedfields = array_merge($supportedfields, $customfieldoptions);
        }

        return $supportedfields;
    }

    /**
     * Prefix the custom profile field shortname with CUSTOM_FIELD_PREFIX
     *
     * @param string $shortname Short name of the profile field.
     * @return string
     */
    protected static function prefix_custom_profile_field(string $shortname): string {
        return self::CUSTOM_FIELD_PREFIX . $shortname;
    }

    /**
     * Check if a given field name is a custom profile field.
     *
     * @param string $fieldname User field name.
     * @return bool
     */
    public static function is_custom_profile_field(string $fieldname): bool {
        return strpos($fieldname, self::CUSTOM_FIELD_PREFIX) === 0;
    }

    /**
     * Retrieve the base shortname from a profile field name (without prefix)
     *
     * @param string $fieldname Profile field name from config.
     * @return string
     */
    public static function get_field_short_name(string $fieldname): string {
        if (self::is_custom_profile_field($fieldname)) {
            $fieldname = substr($fieldname, strlen(self::CUSTOM_FIELD_PREFIX), strlen($fieldname));
        }

        return $fieldname;
    }

    /**
     * Get the display name for the user field used by oEQ for SSO
     *
     * @return string
     */
    public static function get_equella_userfield_display_name(): string {
        $userfield = equella_get_config('equella_userfield');
        $options = self::get_supported_fields();
        return $options[$userfield] ?? '';
    }
}
