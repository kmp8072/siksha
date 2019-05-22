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
 * @author Ciaran Irvine <ciaran.irvine@shezarlms.com>
 * @package enrol
 * @subpackage shezar_program
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_shezar_program_settings', '', get_string('pluginname_desc', 'enrol_shezar_program')));


    $settings->add(new admin_setting_configcheckbox('enrol_shezar_program/defaultenrol',
            get_string('defaultenrol', 'enrol'), get_string('defaultenrol_desc', 'enrol'), 1));
    //--- enrol instance defaults ----------------------------------------------------------------------------
    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect('enrol_shezar_program/roleid',
            get_string('defaultrole', 'role'), '', $student->id, $options));
    }

    $settings->add(new admin_setting_configtext('enrol_shezar_program/enrolperiod',
            get_string('enrolperiod', 'enrol_shezar_program'), get_string('enrolperiod_desc', 'enrol_shezar_program'), 0, PARAM_INT));

    $options = array(
            ENROL_EXT_REMOVED_UNENROL        => get_string('extremovedunenrol', 'enrol'),
            ENROL_EXT_REMOVED_SUSPEND        => get_string('extremovedsuspend', 'enrol'),
            ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol'));
    $settings->add(new admin_setting_configselect('enrol_shezar_program/unenrolaction', get_string('extremovedaction', 'enrol'), get_string('extremovedaction_help', 'enrol'), ENROL_EXT_REMOVED_SUSPEND, $options));
}
