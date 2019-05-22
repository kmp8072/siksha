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

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/classes/rb_global_restriction_set.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/report_forms.php');

$reportid = optional_param('reportid', false, PARAM_INT);
require_sesskey();
require_login();
$context = context_system::instance();

// Legacy shezar HTML ajax, this should be converted to json + AJAX_SCRIPT.
send_headers('text/html; charset=utf-8', false);

if ($reportid) {
    // A report id should be provided in all situations ideally.
    if (!reportbuilder::is_capable($reportid)) {
        // This is a silly hack to get around how poor the shezar dialogs are.
        $PAGE->set_pagelayout('embedded');
        throw new moodle_exception('nopermissionstoviewrestrictions', 'shezar_reportbuilder');
    }
} else {
    // In the rare circumstance we don't have a report id use the managereports cap.
    // This will be extremely restrictive but that is why you should always use a report id.
    // This should only really occur with embedded reports and we will need to find a better solution to that
    // if it is a problem.
    require_capability('shezar/reportbuilder:managereports', $context);
}

$PAGE->set_context($context);

// Get all restrictions applicable for user.
$restrictions = rb_global_restriction_set::get_user_all_restrictions($USER->id);

// Add multilang support.
foreach ($restrictions as $restriction) {
    $restriction->name = format_string($restriction->name);
}

$selected = optional_param('selected', array(), PARAM_SEQUENCE);
if (!is_array($selected)) {
    if (strlen($selected)) {
        $selected = explode(',', $selected);
    } else {
        $selected = array();
    }
}

$form = new report_builder_choose_restriction_form(
        null,
        array('restrictions' => $restrictions, 'selected' => $selected),
        'post',
        '',
        array('class' => 'chooserestriction'));

echo get_string('chooserestrictiondesc', 'shezar_reportbuilder');
echo html_writer::div(get_string('error:globalrestrictionrequired', 'shezar_reportbuilder'), 'notifyproblem error-required');
$selectallhtml = html_writer::link('#', get_string('selectall'), array('class' => 'selectall'));
$selectnonehtml = html_writer::link('#', get_string('none'), array('class' => 'selectnone'));
echo html_writer::div($selectallhtml . ' / ' . $selectnonehtml, 'dialog-nobind selectallnone');

$form->display();

echo html_writer::script('
    $("#chooserestriction .selectallnone a.selectall").on("click", function(event) {
        event.preventDefault();
        $("form.chooserestriction input[type=checkbox]").prop("checked", true);
        $("form.chooserestriction input[type=checkbox]:first").trigger("change");
    });
    $("#chooserestriction .selectallnone a.selectnone").on("click", function(event) {
        event.preventDefault();
        $("form.chooserestriction input[type=checkbox]").prop("checked", false);
        $("form.chooserestriction input[type=checkbox]:first").trigger("change");
    });
');
