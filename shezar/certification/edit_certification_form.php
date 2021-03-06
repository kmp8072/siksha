<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2010 onwards shezar Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jon Sharp <jonathans@catalyst-eu.net>
 * @package shezar
 * @subpackage certification
 */

require_once("{$CFG->libdir}/formslib.php");

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

// Define a form class to edit the program messages.
class edit_certification_form extends moodleform {

    public function definition() {

        $mform =& $this->_form;

        $timeallowance = $this->_customdata['timeallowance'];

        $certification = $this->_customdata['certification'];
        if (empty($certification->activeperiod)) {
            $active = array('', 'day');
        } else {
            $active = explode(' ', $certification->activeperiod);
        }

        if (empty($certification->minimumactiveperiod)) {
            $minimumactive = array('', 'day');
        } else {
            $minimumactive = explode(' ', $certification->minimumactiveperiod);
        }

        if (empty($certification->windowperiod)) {
            $window = array('', 'day');
        } else {
            $window = explode(' ', $certification->windowperiod);
        }

        if (empty($certification->recertifydatetype)) {
            $recertifydatetype = CERTIFRECERT_EXPIRY;
        } else {
            $recertifydatetype = $certification->recertifydatetype;
        }

        $mform->addElement('header', 'editdetailshdr', get_string('editdetailshdr', 'shezar_certification'));
        $mform->addElement('html', html_writer::start_tag('p', array('class' => 'instructions')) .
                             get_string('editdetailsdesc', 'shezar_certification') . html_writer::end_tag('p'));

        $dateperiodoptions = array(
            'day' => get_string('days', 'shezar_certification'),
            'week' => get_string('weeks', 'shezar_certification'),
            'month' => get_string('months', 'shezar_certification'),
            'year' => get_string('years', 'shezar_certification'),
        );

        // Recert datetype.
        $recertoptions = array(
            CERTIFRECERT_COMPLETION => get_string('editdetailsrccmpl', 'shezar_certification'),
            CERTIFRECERT_EXPIRY => get_string('editdetailsrcexp', 'shezar_certification'),
            CERTIFRECERT_FIXED => get_string('editdetailsrcfixed', 'shezar_certification')
        );
        $mform->addElement('select', 'recertifydatetype', get_string('editdetailsrcopt', 'shezar_certification'), $recertoptions);
        $mform->setDefault('recertifydatetype', $recertifydatetype);
        $mform->addHelpButton('recertifydatetype', 'editdetailsrcopt', 'shezar_certification');

        // Active period num.
        $mform->addElement('html', html_writer::start_tag('p', array('class' => 'subheader')) .
                             get_string('editdetailsactivep', 'shezar_certification') . html_writer::end_tag('p'));
        $mform->addElement('html', html_writer::start_tag('p', array('class' => 'instructions')) .
                             get_string('editdetailsvalid', 'shezar_certification') . html_writer::end_tag('p'));
        $activegrp = array();
        $activegrp[] =  $mform->createElement('text', 'activenum', '', array('size' => 4, 'maxlength' => 4));
        $mform->setType('activenum', PARAM_INT);
        $mform->setdefault('activenum', $active[0]);

        // Active period timeselect.
        $activegrp[] = $mform->createElement('select', 'activeperiod', '', $dateperiodoptions);
        $mform->setDefault('activeperiod', $active[1]);
        $mform->addGroup($activegrp, 'activegrp', get_string('editdetailsactive', 'shezar_certification'), ' ', false);
        $mform->addHelpButton('activegrp', 'editdetailsactive', 'shezar_certification');

        $mform->registerRule('activeperiod_validation', 'function', 'activeperiod_validation');
        $mform->addRule('activegrp',
                get_string('error:minimumactiveperiod', 'shezar_certification'),
                'activeperiod_validation',
                $mform);

        // Minimum active period num.
        $minimumactivegrp = array();
        $minimumactivegrp[] =  $mform->createElement('text', 'minimumactivenum', '', array('size' => 4, 'maxlength' => 4));
        $mform->setType('minimumactivenum', PARAM_INT);
        $mform->setdefault('minimumactivenum', $minimumactive[0]);

        // Minimum active period timeselect.
        $minimumactivegrp[] = $mform->createElement('select', 'minimumactiveperiod', '', $dateperiodoptions);
        $mform->setDefault('minimumactiveperiod', $minimumactive[1]);
        $mform->addGroup($minimumactivegrp, 'minimumactivegrp', get_string('editdetailsminimumactive', 'shezar_certification'), ' ', false);
        $mform->addHelpButton('minimumactivegrp', 'editdetailsminimumactive', 'shezar_certification');

        $mform->registerRule('minimumactiveperiod_windowperiod_validation', 'function', 'minimumactiveperiod_windowperiod_validation');
        $mform->addRule('minimumactivegrp',
            get_string('error:minimumactiveperiodwindow', 'shezar_certification'),
            'minimumactiveperiod_windowperiod_validation',
            $mform);
        $mform->registerRule('minimumactiveperiod_activeperiod_validation', 'function', 'minimumactiveperiod_activeperiod_validation');
        $mform->addRule('minimumactivegrp',
            get_string('error:minimumactiveperiodactive', 'shezar_certification'),
            'minimumactiveperiod_activeperiod_validation',
            $mform);
        $mform->disabledIf('minimumactivenum', 'recertifydatetype', 'ne', CERTIFRECERT_FIXED);
        $mform->disabledIf('minimumactiveperiod', 'recertifydatetype', 'ne', CERTIFRECERT_FIXED);

        // Recert window period num.
        $mform->addElement('html', html_writer::start_tag('p', array('class' => 'subheader')) .
                             get_string('editdetailsrcwin', 'shezar_certification') . html_writer::end_tag('p'));
        $windowgrp = array();
        $windowgrp[] = $mform->createElement('text', 'windownum', '', array('size' => 4, 'maxlength' => 4));
        $mform->setType('windownum', PARAM_INT);
        $mform->setDefault('windownum', $window[0]);

        // Recert window period timeselect.
        $windowgrp[] = $mform->createElement('select', 'windowperiod', '', $dateperiodoptions);
        $mform->setDefault('windowperiod', $window[1]);
        $mform->addGroup($windowgrp, 'windowgrp', get_string('editdetailswindow', 'shezar_certification'), ' ', false);
        $mform->addHelpButton('windowgrp', 'editdetailswindow', 'shezar_certification');

        $mform->registerRule('windowperiod_validation', 'function', 'windowperiod_validation');
        $mform->addRule('windowgrp',
                get_string('error:minimumwindowperiod', 'shezar_certification', $timeallowance->timestring),
                'windowperiod_validation',
                $timeallowance->seconds);

        if ($timeallowance->seconds > 0) {
            $mform->addElement('html', html_writer::tag('p',
                    get_string('timeallowance', 'shezar_certification', $timeallowance),
                    array('class' => 'timeallowance')));
        }

        // Buttons.
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'savechanges', get_string('savechanges'), 'class="certification-add"');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }


    /**
     * Carries out validation of submitted form values
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        foreach ($data as $elementname => $elementvalue) {
            // Check for negative integer issues.
            if ($elementname == 'activenum' || $elementname == 'windownum') {
                if ($elementvalue < 0) {
                    $errors[$elementname] = get_string('error:mustbepositive', 'shezar_certification');
                }
            }
        }
        return $errors;
    }

}

/**
 * Validates that the window period is greater than or equal to the required time for recertification
 *
 * @param string $element Element name
 * @param array $value Value of windowgrp
 * @param int $timeallowance time allowance in seconds
 * @return boolean
 */
function windowperiod_validation($element, $value, $timeallowance) {
    $timewindowperiod = strtotime($value['windownum'] . ' ' . $value['windowperiod'], 0);
    return ($timewindowperiod && ($timewindowperiod >= $timeallowance));
}

/**
 * Validates that the active period is greater than or equal to the recertification window period
 *
 * @param string $element Element name
 * @param array $value Value of windowgrp
 * @param object $mform
 * @return boolean
 */
function activeperiod_validation($element, $value, $mform) {
    $timeactiveperiod = strtotime($value['activenum'] . ' ' . $value['activeperiod'], 0);
    $windowgrp = $mform->getElementValue('windowgrp');
    $timewindowperiod = strtotime($windowgrp['windownum'] . ' ' . $windowgrp['windowperiod'][0], 0);
    return ($timewindowperiod && $timeactiveperiod && ($timeactiveperiod >= $timewindowperiod));
}

/**
 * Validates that the minimum active period is greater than or equal to the recertification window period
 *
 * @param string $element Element name
 * @param array $value Value of windowgrp
 * @param object $mform
 * @return boolean
 */
function minimumactiveperiod_activeperiod_validation($element, $value, $mform) {
    $recertmethodgrp = $mform->getElementValue('recertifydatetype');
    if ($recertmethodgrp[0] != CERTIFRECERT_FIXED) {
        return true;
    }
    $timeminimumperiod = strtotime($value['minimumactivenum'] . ' ' . $value['minimumactiveperiod'], 0);
    $activegrp = $mform->getElementValue('activegrp');
    $timeactiveperiod = strtotime($activegrp['activenum'] . ' ' . $activegrp['activeperiod'][0], 0);
    return ($timeminimumperiod && $timeactiveperiod && ($timeminimumperiod <= $timeactiveperiod));
}

/**
 * Validates that the minimum active period is less than or equal to the active period
 *
 * @param string $element Element name
 * @param array $value Value of windowgrp
 * @param object $mform
 * @return boolean
 */
function minimumactiveperiod_windowperiod_validation($element, $value, $mform) {
    $recertmethodgrp = $mform->getElementValue('recertifydatetype');
    if ($recertmethodgrp[0] != CERTIFRECERT_FIXED) {
        return true;
    }
    $timeminimumperiod = strtotime($value['minimumactivenum'] . ' ' . $value['minimumactiveperiod'], 0);
    $windowgrp = $mform->getElementValue('windowgrp');
    $timewindowperiod = strtotime($windowgrp['windownum'] . ' ' . $windowgrp['windowperiod'][0], 0);
    return ($timewindowperiod && $timeminimumperiod && ($timewindowperiod <= $timeminimumperiod));
}
