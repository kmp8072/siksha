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
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package shezar_reportbuilder
 */

/**
 * Page containing list of available reports and new report form
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/report_forms.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/classes/rb_global_restriction.php');

$id = optional_param('id', null, PARAM_INT); // Restriction id.
$action = optional_param('action', null, PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);

admin_externalpage_setup('rbmanageglobalrestrictions');

$restriction = new rb_global_restriction($id);
$returnurl = new moodle_url('/shezar/reportbuilder/restrictions/index.php', array('page' => $page));

if ($action && $restriction->id) {
    require_sesskey();
    switch ($action) {
        case 'up':
            $restriction->up();
            redirect($returnurl);
            break;

        case 'down':
            $restriction->down();
            redirect($returnurl);
            break;

        case 'activate':
            $restriction->activate();
            shezar_set_notification(get_string('restrictionactivated', 'shezar_reportbuilder', $restriction->name),
                $returnurl, array('class' => 'notifysuccess'));
            break;

        case 'deactivate':
            $restriction->deactivate();
            shezar_set_notification(get_string('restrictiondeactivated', 'shezar_reportbuilder', $restriction->name),
                $returnurl, array('class' => 'notifysuccess'));
            break;

        case 'delete':
            if ($confirm) {
                $restriction->delete();
                shezar_set_notification(get_string('restrictiondeleted', 'shezar_reportbuilder', $restriction->name),
                    $returnurl, array('class' => 'notifysuccess'));
            }
            break;
    }
}

/** @var shezar_reportbuilder_renderer|core_renderer $output */
$output = $PAGE->get_renderer('shezar_reportbuilder');
echo $output->header();

if ($action === 'delete' && !$confirm) {

    echo $output->heading(get_string('confirmdeleterestrictionheader', 'shezar_reportbuilder', $restriction->name));
    echo html_writer::tag('p', get_string('confirmdeleterestriction', 'shezar_reportbuilder'));

    $buttons = $output->single_button(
        new moodle_url('/shezar/reportbuilder/restrictions/index.php', array('action' => 'delete', 'confirm' => 1, 'id' => $id, 'page' => $page)),
        get_string('delete', 'shezar_reportbuilder'), 'post'
    );
    $buttons .= $output->single_button(
        new moodle_url('/shezar/reportbuilder/restrictions/index.php'),
        get_string('cancel', 'moodle'), 'get'
    );
    echo html_writer::tag('div', $buttons, array('class' => 'buttons'));

} else {

    echo $output->heading(get_string('globalrestrictions', 'shezar_reportbuilder'));

    // Get list of unsupported source and display if any.
    $unsupportedlist = rb_global_restriction::get_unsupported_sources();
    if ($unsupportedlist) {
        $warnstr = get_string('nonglobalrestrictionsources', 'shezar_reportbuilder', '"' . implode('", "', $unsupportedlist) . '"');
        echo html_writer::div($warnstr, 'notice');
    }

    echo html_writer::tag('p', get_string('globalrestrictiondescription', 'shezar_reportbuilder'));
    echo $output->single_button(
        new moodle_url('/shezar/reportbuilder/restrictions/edit_general.php'),
        get_string('globalrestrictionnew', 'shezar_reportbuilder')
    );


    $count = 0;
    $perpage = get_config('reportbuilder', 'globalrestrictionrecordsperpage');
    $globalrestrictions = rb_global_restriction::get_all($page, $perpage, $count);
    $paging = new paging_bar($count, $page, $perpage, $returnurl);
    echo $output->render($paging);
    echo $output->global_restrictions_table($globalrestrictions);
    echo $output->render($paging);
}

echo $output->footer();
