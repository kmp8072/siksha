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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Output renderer for shezar_dashboard
 */
class shezar_dashboard_renderer extends plugin_renderer_base {
    /**
     * Return a button that when clicked, takes the user to new dashboard layout editor
     *
     * @return string HTML to display the button
     */
    public function create_dashboard_button() {
        $url = new moodle_url('/shezar/dashboard/edit.php', array('action' => 'new', 'adminedit' => 1));
        return $this->output->single_button($url, get_string('createdashboard', 'shezar_dashboard'), 'get');
    }

    /**
     * Renders a table containing dashboard list
     *
     * @param array $dashboards array of shezar_dashboard object
     * @return string HTML table
     */
    public function dashboard_manage_table($dashboards) {
        if (empty($dashboards)) {
            return get_string('nodashboards', 'shezar_dashboard');
        }

        $tableheader = array(get_string('name', 'shezar_dashboard'),
                             get_string('availability', 'shezar_dashboard'),
                             get_string('options', 'shezar_dashboard'));

        $dashboardstable = new html_table();
        $dashboardstable->summary = '';
        $dashboardstable->head = $tableheader;
        $dashboardstable->data = array();
        $dashboardstable->attributes = array('class' => 'generaltable fullwidth');

        $strpublish = get_string('publish', 'shezar_dashboard');
        $strunpublish = get_string('unpublish', 'shezar_dashboard');
        $strdelete = get_string('delete', 'shezar_dashboard');
        $stredit = get_string('editdashboard', 'shezar_dashboard');
        $strclone = get_string('clonedashboard', 'shezar_dashboard');

        $data = array();
        foreach ($dashboards as $dashboard) {
            $id = $dashboard->get_id();
            $name = format_string($dashboard->name);
            $urllayout = new moodle_url('/shezar/dashboard/layout.php', array('id' => $id));
            $urledit = new moodle_url('/shezar/dashboard/edit.php', array('id' => $id));
            $urlclone = new moodle_url('/shezar/dashboard/manage.php', array('action' => 'clone', 'id' => $id, 'sesskey' => sesskey()));
            $urlpublish = new moodle_url('/shezar/dashboard/manage.php', array('action' => 'publish', 'id' => $id, 'sesskey' => sesskey()));
            $urlunpublish = new moodle_url('/shezar/dashboard/manage.php', array('action' => 'unpublish', 'id' => $id, 'sesskey' => sesskey()));
            $urlup = new moodle_url('/shezar/dashboard/manage.php', array('action' => 'up', 'id' => $id, 'sesskey' => sesskey()));
            $urldown = new moodle_url('/shezar/dashboard/manage.php', array('action' => 'down', 'id' => $id, 'sesskey' => sesskey()));
            $deleteurl = new moodle_url('/shezar/dashboard/manage.php', array('action' => 'delete', 'id' => $id));

            $row = array();
            $row[] = html_writer::link($urllayout, $name);

            switch ($dashboard->get_published()) {
                case shezar_dashboard::NONE:
                    $row[] = get_string('availablenone', 'shezar_dashboard');
                    break;
                case shezar_dashboard::AUDIENCE:
                    $cnt = count($dashboard->get_cohorts());
                    $row[] = get_string('availableaudiencecnt', 'shezar_dashboard', $cnt);
                    break;
                case shezar_dashboard::ALL:
                    $row[] = get_string('availableall', 'shezar_dashboard');
                    break;
                default:
                    $row[] = get_string('availableunknown', 'shezar_dashboard');
            }

            $options = '';
            $options .= $this->output->action_icon($urledit, new pix_icon('/t/edit', $stredit, 'moodle'), null,
                    array('class' => 'action-icon edit'));

            $options .= $this->output->action_icon($urlclone, new pix_icon('/t/copy', $strclone, 'moodle'), null,
                array('class' => 'action-icon clone'));

            if (!$dashboard->is_first()) {
                $options .= $this->output->action_icon($urlup, new pix_icon('/t/up', 'moveup', 'moodle'), null,
                        array('class' => 'action-icon up'));
            }
            if (!$dashboard->is_last()) {
                $options .= $this->output->action_icon($urldown, new pix_icon('/t/down', 'movedown', 'moodle'), null,
                        array('class' => 'action-icon down'));
            }

            $options .= $this->output->action_icon($deleteurl, new pix_icon('/t/delete', $strdelete, 'moodle'), null,
                        array('class' => 'action-icon delete'));
            $row[] = $options;

            $data[] = $row;
        }
        $dashboardstable->data = $data;

        return html_writer::table($dashboardstable);
    }
}