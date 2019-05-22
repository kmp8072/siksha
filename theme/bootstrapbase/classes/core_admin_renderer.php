<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2016 onwards shezar Learning Solutions LTD
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
 * @copyright 2016 onwards shezar Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@shezarlearning.com>
 * @package   theme_bootstrapbase
 */

defined('MOODLE_INTERNAL') || die();

require_once("{$CFG->dirroot}/admin/renderer.php");

class theme_bootstrapbase_core_admin_renderer extends core_admin_renderer {

    public function plugins_check_table(core_plugin_manager $pluginman, $version, array $options = array()) {
        $html = parent::plugins_check_table($pluginman, $version, $options);

        // Replace Bootstrap 3 label classes with Bootstrap 2 equivalents.
        return str_replace('label-danger', 'label-important', $html);
    }

    public function plugins_control_panel(core_plugin_manager $pluginman, array $options = array()) {
        $html = parent::plugins_control_panel($pluginman, $options);

        // Replace Bootstrap 3 label classes with Bootstrap 2 equivalents.
        return str_replace('label-danger', 'label-important', $html);
    }

    /**
     * Display the admin notifications page.
     * @param int $maturity
     * @param bool $insecuredataroot warn dataroot is invalid
     * @param bool $errorsdisplayed warn invalid dispaly error setting
     * @param bool $cronoverdue warn cron not running
     * @param bool $dbproblems warn db has problems
     * @param bool $maintenancemode warn in maintenance mode
     * @param bool $buggyiconvnomb warn iconv problems
     * @param array|null $availableupdates array of \core\update\info objects or null
     * @param int|null $availableupdatesfetch timestamp of the most recent updates fetch or null (unknown)
     * @param string[] $cachewarnings An array containing warnings from the Cache API.
     *
     * @return string HTML to output.
     */
    public function admin_notifications_page($maturity, $insecuredataroot, $errorsdisplayed,
                                             $cronoverdue, $dbproblems, $maintenancemode, $availableupdates, $availableupdatesfetch,
                                             $buggyiconvnomb, $registered, array $cachewarnings = array(), $latesterror, $activeusers, $shezar_release) {

        $output = parent::admin_notifications_page($maturity, $insecuredataroot, $errorsdisplayed,
            $cronoverdue, $dbproblems, $maintenancemode, $availableupdates, $availableupdatesfetch,
            $buggyiconvnomb, $registered, $cachewarnings, $latesterror, $activeusers, $shezar_release);

        $output = str_replace('class="copyright-acknowledgements"', 'class="box generalbox adminwarning copyright-acknowledgements"', $output);
        return $output;
    }

    public function environment_check_table($result, $environment) {
        // shezar: original function from Moodle 3
        $html = parent::environment_check_table($result, $environment);

        $replacements = array(
            '<span class="label label-success">' => '<span class="ok">',
            '<span class="label label-warning">' => '<span class="warn">',
            '<span class="label label-danger">' => '<span class="error">',
            '<p class="text-success">' => '<p class="ok">',
            '<p class="text-warning">' => '<p class="warn">',
            '<p class="text-danger">' => '<p class="error">',
        );

        $find = array_keys($replacements);
        $replace = array_values($replacements);

        return str_replace($find, $replace, $html);
    }
}
