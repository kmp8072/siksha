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
 * @author Chris Wharton <chrisw@catalyst.net.nz>
 * @package shezar
 * @subpackage enrol_shezar_learningplan
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_shezar_learningplan_settings', '', get_string('pluginname_desc', 'enrol_shezar_learningplan')));

    $settings->add(new admin_setting_configcheckbox('enrol_shezar_learningplan/defaultenrol',
            get_string('defaultenrol', 'enrol'), get_string('defaultenrol_desc', 'enrol'), 1));

    //--- enrol instance defaults ----------------------------------------------------------------------------
    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect('enrol_shezar_learningplan/roleid',
            get_string('defaultrole', 'role'), '', $student->id, $options));
    }
}
