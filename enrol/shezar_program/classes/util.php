<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2015 onwards shezar Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package enrol_shezar_program
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Various utility methods for program enrolment.
 *
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package enrol_shezar_program
 */
class enrol_shezar_program_util {
    public static function feature_setting_updated_callback() {
        global $CFG;

        if (!isset($CFG->enrol_plugins_enabled)) {
            // Not installed yet.
            return;
        }

        $resetcaches = false;
        $enabled = explode(',', $CFG->enrol_plugins_enabled);

        if (shezar_feature_visible('programs')) {
            // Make sure the program enrol plugin is enabled.
            if (!in_array('shezar_program', $enabled)) {
                $enabled[] = 'shezar_program';
                set_config('enrol_plugins_enabled', implode(',', $enabled));
                $resetcaches = true;
            }
        } else {
            // Make sure the program enrol plugin is disabled.
            if (in_array('shezar_program', $enabled)) {
                $enabled = array_flip($enabled);
                unset($enabled['shezar_program']);
                $enabled = array_flip($enabled);
                set_config('enrol_plugins_enabled', implode(',', $enabled));
                $resetcaches = true;
            }
        }

        if ($resetcaches) {
            // Reset enrol and plugin caches.
            core_plugin_manager::reset_caches();
            $syscontext = context_system::instance();
            $syscontext->mark_dirty();
        }
    }
}
