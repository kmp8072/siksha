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
 * @package shezar_dashboard
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/shezar/dashboard/lib.php');
require_once($CFG->libdir.'/adminlib.php');

$action = optional_param('action', null, PARAM_ALPHANUMEXT);

admin_externalpage_setup('shezardashboard', '', array('action' => $action), new moodle_url('/shezar/dashboard/manage.php'));

// Check shezar Dashboard is enable.
shezar_dashboard::check_feature_enabled();

/** @var shezar_dashboard_renderer $output */
$output = $PAGE->get_renderer('shezar_dashboard');

$dashboards = shezar_dashboard::get_manage_list();

$dashboard = null;
if ($action != '') {
    $id = required_param('id', PARAM_INT);
    $dashboard = new shezar_dashboard($id);
    $returnurl = new moodle_url('/shezar/dashboard/manage.php');
}

switch ($action) {
    case 'clone':
        // This operation clones the given dashboard as well as the blocks it uses and any assigned audiences.
        // It does not clone any user customisations of the dashboard.
        $confirm = optional_param('confirm', null, PARAM_INT);
        if ($confirm) {
            require_sesskey();
            $newid = $dashboard->clone_dashboard();
            $clone = new shezar_dashboard($newid);
            $args = array(
                'original' => $dashboard->name,
                'clone' => $clone->name
            );
            shezar_set_notification(get_string('dashboardclonesuccess', 'shezar_dashboard', $args), $returnurl,
                array('class' => 'notifysuccess'));
        }
        break;
    case 'delete':
        $confirm = optional_param('confirm', null, PARAM_INT);
        if ($confirm) {
            require_sesskey();
            $dashboard->delete($id);
            shezar_set_notification(get_string('dashboarddeletesuccess', 'shezar_dashboard'), $returnurl,
                    array('class' => 'notifysuccess'));
        }
        break;
    case 'up':
        require_sesskey();
        $dashboard->move_up();
        redirect($returnurl);
        break;
    case 'down':
        require_sesskey();
        $dashboard->move_down();
        redirect($returnurl);
        break;
    case 'reset':
        $confirm = optional_param('confirm', null, PARAM_INT);
        if ($confirm) {
            require_sesskey();
            $dashboard->reset_all();
            shezar_set_notification(get_string('dashboardresetsuccess', 'shezar_dashboard'), $returnurl,
                    array('class' => 'notifysuccess'));
        }
        break;
}

$requiresconfirmation = array('delete', 'reset', 'clone');
if (in_array($action, $requiresconfirmation)) {
    switch ($action) {
        case 'delete':
            $confirmtext = get_string('deletedashboardconfirm', 'shezar_dashboard', $dashboard->name);
            break;
        case 'reset':
            $confirmtext = get_string('resetdashboardconfirm', 'shezar_dashboard', $dashboard->name);
            break;
        case 'clone':
            $confirmtext = get_string('clonedashboardconfirm', 'shezar_dashboard', $dashboard->name);
            break;
        default:
            throw new coding_exception('Invalid action passed to confirmation.');
            break;
    }

    $url = new moodle_url('/shezar/dashboard/manage.php', array('action'=> $action, 'id' => $id, 'confirm' => 1));
    $continue = new single_button($url, get_string('continue'), 'post');
    $cancel = new single_button($returnurl, get_string('cancel'), 'get');

    echo $output->header();
    echo $output->confirm(format_text($confirmtext), $continue, $cancel);
    echo $output->footer();
    exit;
}

echo $output->header();
echo $output->heading(get_string('managedashboards', 'shezar_dashboard'));
echo $output->create_dashboard_button();
echo $output->dashboard_manage_table($dashboards);
echo $output->footer();
