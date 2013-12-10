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

require_once($CFG->libdir.'/adminlib.php');

class admin_setting_statictext extends admin_setting {
    public $text;
    public function __construct($name, $visiblename, $description, $text) {
        parent::__construct($name, $visiblename, $description, null);
        $this->text = $text;
    }

    public function write_setting($data) {
        // do not write any setting
        return '';
    }

    public function get_setting() {
        return true;
    }

    public function output_html($data, $query='') {
        return format_admin_setting($this, $this->visiblename, $this->text, $this->description, true);
    }
}

class admin_setting_radiobuttons extends admin_setting {

    public $text;
    private $options;
    public function __construct($name, $visiblename, $description, $defaultsetting, $options) {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
        $this->options = $options;
    }

    public function write_setting($data) {
        return ($this->config_write($this->name, $data) ? '' : get_string('errorsetting', 'admin'));
    }

    public function get_setting() {
        return $this->config_read($this->name);
    }

    public function output_select_html($data, $current, $default, $extraname = '') {

        $warning = '';
        $output = '';

        if (empty($current)) {
            $current = $default;
        }

        foreach ($this->options as $value=>$label) {

            $attributes = array();
            $attributes['id'] = html_writer::random_id('checkbox_');
            $attributes['type']    = 'radio';
            $attributes['value']   = $value;
            $attributes['name']    = $this->get_full_name();
            $attributes['class']   = 'form-checkbox';
            if ($current == $value) {
                $attributes['checked'] = 'checked';
            }

            $radiohtml = html_writer::empty_tag('input', $attributes);
            $labelhtml = html_writer::tag('label', ' ' . $label, array('for'=>$attributes['id']));
            $output .= $radiohtml;
            $output .= $labelhtml;
            $output .= html_writer::empty_tag('br');
        }

        return array($output, $warning);
    }

    public function output_html($data, $query='') {
        global $CFG;
        $default = $this->get_defaultsetting();
        $current = $this->get_setting();
        list($selecthtml, $warning) = $this->output_select_html($data, $current, $default);
        return format_admin_setting($this, $this->visiblename, $selecthtml, $this->description, true);
    }
}

class admin_setting_openlink extends admin_setting {

    public $url;
    public function __construct($name, $visiblename, $description, $url) {
        parent::__construct($name, $visiblename, $description, null);
        $this->url = $url;
    }
    public function write_setting($data) {
        // do not write any setting
        return '';
    }
    public function get_setting() {
        return true;
    }
    public function output_html($data, $query='') {
        global $CFG;

        $attributes = array('onclick'=>'window.open(\'' . $this->url . '\'); return false;');
        $link = html_writer::link($this->url, $this->visiblename, $attributes);

        return format_admin_setting($this, $this->visiblename, $link, $this->description, true);
    }
}

/**
 * Provides some custom settings classes for the manage_users global settings
 * page
 *
 * @author Michael Avelar <michaela@moodlerooms.com>
 * @version $Id: adminsettings.class.php,v 1.1 2010/03/05 03:40:02 dev Exp $
 **/
class equella_setting_left_heading extends admin_setting {
    /**
     * not a setting, just text
     * @param string $name of setting
     * @param string $heading heading
     * @param string $information text in box
     */
    public function __construct($name, $heading, $information) {
        $this->nosave = true;
        parent::__construct($name, $heading, $information, '');
    }

    function get_setting() {
        return true;
    }

    function get_defaultsetting() {
        return true;
    }

    function write_setting($data) {
        // do not write any setting
        return '';
    }

    function output_html($data, $query='') {
        global $OUTPUT;
        $return = '';
        if ($this->visiblename != '') {
            $return .= $OUTPUT->heading($this->visiblename, 3, '', true);
        }
        if ($this->description != '') {
            $return .= $OUTPUT->box(highlight($query, markdown_to_html($this->description)), 'generalbox formsettingheading', '', true);
        }
        return $return;
    }
}
