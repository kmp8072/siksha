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
 * @subpackage shezar_hierarchy
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/shezar/core/dialogs/dialog_content_hierarchy.class.php');

require_once($CFG->dirroot.'/shezar/hierarchy/prefix/competency/lib.php');
require_once($CFG->dirroot.'/shezar/hierarchy/prefix/organisation/lib.php');
require_once($CFG->dirroot.'/shezar/core/js/lib/setup.php');

// Page title
$pagetitle = 'assigncompetencytemplates';

///
/// Params
///

// Assign to id
$assignto = required_param('assignto', PARAM_INT);

// Framework id
$frameworkid = optional_param('frameworkid', 0, PARAM_INT);

// Only return generated tree html
$treeonly = optional_param('treeonly', false, PARAM_BOOL);

// No javascript parameters
$nojs = optional_param('nojs', false, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$s = optional_param('s', '', PARAM_TEXT);

// string of params needed in non-js url strings
$urlparams = array('assignto' => $assignto, 'frameworkid' => $frameworkid, 'nojs' => $nojs, 'returnurl' => urlencode($returnurl), 's' => $s);

///
/// Permissions checks
///

// Setup page
admin_externalpage_setup('organisationmanage');

// Load currently assigned competency templates
$organisations = new organisation();
if (!$currentlyassigned = $organisations->get_assigned_competency_templates($assignto, $frameworkid)) {
    $currentlyassigned = array();
}

///
/// Display page
///

if (!$nojs) {
    // Load dialog content generator
    $dialog = new shezar_dialog_content_hierarchy_multi('competency', $frameworkid);

    // Templates only
    $dialog->templates_only = true;

    // Toggle treeview only display
    $dialog->show_treeview_only = $treeonly;

    // Load competency templates to display
    $dialog->items = $dialog->hierarchy->get_templates();

    // Set disabled items
    $dialog->disabled_items = $currentlyassigned;

    // Set strings
    $dialog->string_nothingtodisplay = 'notemplateinframework';
    $dialog->select_title = 'locatecompetencytemplate';
    $dialog->selected_title = 'selectedcompetencytemplates';

    // Disable framework picker
    $dialog->disable_picker = true;

    // Display
    echo $dialog->generate_markup();

} else {
    // non JS version of page
    // Check permissions
    $sitecontext = context_system::instance();
    require_capability('shezar/hierarchy:updateorganisation', $sitecontext);

    // Setup hierarchy object
    $hierarchy = new competency();

    // Load framework
    if (!$framework = $hierarchy->get_framework($frameworkid)) {
        print_error('competencyframeworknotfound', 'shezar_hierarchy');
    }

    // Load competency templates to display
    $items = $hierarchy->get_templates();

    echo $OUTPUT->header();
    $out = html_writer::tag('h2', get_string('assigncompetencytemplate', 'shezar_hierarchy'));
    $link = html_writer::link($returnurl, get_string('cancelwithoutassigning','shezar_hierarchy'));
    $out .= html_writer::tag('p', $link);

    if (empty($frameworkid) || $frameworkid == 0) {

        echo build_nojs_frameworkpicker(
            $hierarchy,
            '/shezar/hierarchy/prefix/organisation/assigncompetencytemplate/find.php',
            array(
                'returnurl' => $returnurl,
                's' => $s,
                'nojs' => 1,
                'assignto' => $assignto,
                'frameworkid' => $frameworkid,
            )
        );

    } else {
        $out .= html_writer::start_tag('div', array('id' => 'nojsinstructions'));
        $out .= build_nojs_breadcrumbs(
            $hierarchy,
            $parentid=0,
            '/shezar/hierarchy/prefix/organisation/assigncompetencytemplate/find.php',
            array(
                'assignto' => $assignto,
                'returnurl' => $returnurl,
                's' => $s,
                'nojs' => $nojs,
                'frameworkid' => $frameworkid,
            )
        );
        $out .= html_writer::tag('p', get_string('clicktoassign', 'shezar_hierarchy') . ' ' . get_string('clicktoviewchildren', 'shezar_hierarchy'));
        $out .= html_writer::end_tag('div');

        $out .= html_writer::start_tag('div', array('class' => 'nojsselect'));
        $out .= build_nojs_treeview(
            $items,
            get_string('nounassignedcompetencytemplates', 'shezar_hierarchy'),
            '/shezar/hierarchy/prefix/organisation/assigncompetencytemplate/assign.php',
            array(
                's' => $s,
                'returnurl' => $returnurl,
                'nojs' => 1,
                'frameworkid' => $frameworkid,
                'assignto' => $assignto,
            ),
            $CFG->wwwroot.'/shezar/hierarchy/prefix/organisation/assigncompetencytemplate/find.php',
            $urlparams,
            $hierarchy->get_all_parents()
        );
        $out .= html_writer::end_tag('div');
    }
    echo $out;
    echo $OUTPUT->footer();
}
