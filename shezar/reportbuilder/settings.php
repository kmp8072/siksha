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
 * @author Simon Coggins <simon.coggins@shezarlms.com>
 * @package shezar
 * @subpackage reportbuilder
 */

/**
 * Add reportbuilder administration menu settings
 */

$ADMIN->add('reports', new admin_category('shezar_reportbuilder', get_string('reportbuilder','shezar_reportbuilder')), 'comments');

// Main report builder settings.
$rb = new admin_settingpage('rbsettings',
                            new lang_string('globalsettings','shezar_reportbuilder'),
                            array('shezar/reportbuilder:managereports'));

if ($ADMIN->fulltree) {
    $rb->add(new shezar_reportbuilder_admin_setting_configexportoptions());

    $rb->add(new admin_setting_configcheckbox('reportbuilder/exporttofilesystem', new lang_string('exporttofilesystem', 'shezar_reportbuilder'),
        new lang_string('reportbuilderexporttofilesystem_help', 'shezar_reportbuilder'), false));

    $rb->add(new admin_setting_configdirectory('reportbuilder/exporttofilesystempath', new lang_string('exportfilesystempath', 'shezar_reportbuilder'),
        new lang_string('exportfilesystempath_help', 'shezar_reportbuilder'), ''));

    $rb->add(new shezar_reportbuilder_admin_setting_configdaymonthpicker('reportbuilder/financialyear', new lang_string('financialyear', 'shezar_reportbuilder'),
        new lang_string('reportbuilderfinancialyear_help', 'shezar_reportbuilder'), array('d' => 1, 'm' => 7)));

    // NOTE: for performance reasons do not use constants here.
    $options = array(
        0 => get_string('noactiverestrictionsbehaviournone', 'shezar_reportbuilder'), // == rb_global_restriction_set::NO_ACTIVE_NONE
        1 => get_string('noactiverestrictionsbehaviourall', 'shezar_reportbuilder'),  // == rb_global_restriction_set::NO_ACTIVE_ALL
    );
    $rb->add(new admin_setting_configselect('reportbuilder/noactiverestrictionsbehaviour',
        new lang_string('noactiverestrictionsbehaviour', 'shezar_reportbuilder'),
        new lang_string('noactiverestrictionsbehaviour_desc', 'shezar_reportbuilder'),
        1, $options));

    // NOTE: do not use constants here for performance reasons.
    //  0 == reportbuilder::GLOBAL_REPORT_RESTRICTIONS_DISABLED
    //  1 == reportbuilder::GLOBAL_REPORT_RESTRICTIONS_ENABLED
    $rb->add(new admin_setting_configcheckbox('reportbuilder/globalrestrictiondefault',
        new lang_string('globalrestrictiondefault', 'shezar_reportbuilder'),
        new lang_string('globalrestrictiondefault_desc', 'shezar_reportbuilder'), 1));

    $rb->add(new admin_setting_configtext('reportbuilder/globalrestrictionrecordsperpage',
        new lang_string('globalrestrictionrecordsperpage', 'shezar_reportbuilder'),
        new lang_string('globalrestrictionrecordsperpage_desc', 'shezar_reportbuilder'), 40, PARAM_INT));
}

// Add all above settings to the report builder settings node.
$ADMIN->add('shezar_reportbuilder', $rb);

// Add links to Global Reports Restrictions.
$ADMIN->add('shezar_reportbuilder', new admin_externalpage('rbmanageglobalrestrictions', new lang_string('manageglobalrestrictions','shezar_reportbuilder'),
    new moodle_url('/shezar/reportbuilder/restrictions/index.php'), array('shezar/reportbuilder:managereports'), empty($CFG->enableglobalrestrictions)));

// Add links to report builder reports.
$ADMIN->add('shezar_reportbuilder', new admin_externalpage('rbmanagereports', new lang_string('managereports','shezar_reportbuilder'),
            new moodle_url('/shezar/reportbuilder/index.php'), array('shezar/reportbuilder:managereports')));
