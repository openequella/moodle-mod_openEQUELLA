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

require_once($CFG->dirroot.'/config.php');
require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->libdir.'/resourcelib.php');
require_once('lib.php');
require_once('locallib.php');

class mod_equella_mod_form extends moodleform_mod {
    var $form;

    function definition() {
        global $CFG;
        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->add_intro_editor();

        $mform->addElement('text', 'url', get_string('location'), array('size'=>'80'));

        $mform->addElement('hidden', 'activation', '');

        $mform->addElement('header', 'optionssection', get_string('option.pagewindow.header', 'equella'));

        $woptions = array(0 => get_string('option.pagewindow.same', 'equella'), 1 => get_string('option.pagewindow.new', 'equella'));
        $mform->addElement('select', 'windowpopup', get_string('option.pagewindow', 'equella'), $woptions);
        $mform->setDefault('windowpopup', !empty($CFG->resource_popup));

        foreach( equella_get_window_options() as $option ) {
            $label = get_string('option.popup.'.$option, 'equella');
            if ($option == 'height' or $option == 'width') {
                $mform->addElement('text', $option, $label, array('size'=>'4'));
            } else {
                $mform->addElement('checkbox', $option, $label);
            }

            if( isset($CFG->{"resource_popup".$option}) ) {
                $mform->setDefault($option, $CFG->{"resource_popup".$option});
            }
            $mform->disabledIf($option, 'windowpopup', 'eq', 0);
            $mform->setAdvanced($option);
        }

        $this->standard_coursemodule_elements(false);
        $this->add_action_buttons();
    }

    function set_data($data)
    {
        $this->form = $data;
        parent::set_data($data);
    }

    function data_preprocessing(&$defaults){
        if (!isset($defaults['popup'])) {
            // use form defaults

        } else if (!empty($defaults['popup'])) {
            $defaults['windowpopup'] = 1;
            if (array_key_exists('popup', $defaults)) {
                $rawoptions = explode(',', $defaults['popup']);
                foreach ($rawoptions as $rawoption) {
                    $option = explode('=', trim($rawoption));
                    $defaults[$option[0]] = $option[1];
                }
            }
        }
    }

    function display()
    {
        global $CFG,$USER;
        $form = $this->form;
        if (isset($form->add))
        {
            $callback = $CFG->wwwroot.'/mod/equella/callbackmulti.php'
                . '?sesskey='.urlencode($USER->sesskey)
                . '&course='.urlencode($form->course)
                . '&coursemodule='.urlencode($form->coursemodule)
                . '&section='.urlencode($form->section)
                . '&module='.urlencode($form->module)
                . '&modulename='.urlencode($form->modulename)
                . '&instance='.urlencode($form->instance);

            $cancelurl = $CFG->wwwroot.'/mod/equella/cancel.php?course='.urlencode($form->course);

            $url = $CFG->equella_url
                . '?method=lms'
                . '&attachmentUuidUrls=true'
                . '&returnurl='.urlencode ($callback)
                . '&returnprefix=tle'
                . '&template=standard'
                . '&token='.urlencode(equella_getssotoken())
                . '&cancelurl='.urlencode($cancelurl)
                . '&courseId='.urlencode(equella_get_courseId($form->course))
                . '&action='.urlencode($CFG->equella_action)
                . '&options='.urlencode($CFG->equella_options)
                . '&selectMultiple=true';

            if( $CFG->equella_select_restriction && $CFG->equella_select_restriction != EQUELLA_CONFIG_SELECT_RESTRICT_NONE ) {
                $url .= '&'.$CFG->equella_select_restriction.'=true';
            }

            echo equella_modal_dialog($form->course, $form->section, $url);
        }
        else
        {
            parent::display();
        }
    }
}
