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
 * @author Petr Skoda <petr.skoda@shezarlearning.com>
 * @package shezar_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Detect common problems in all db/access.php files
 */
class shezar_core_access_testcase extends advanced_testcase {
    public function test_access_files() {
        global $CFG;

        // Please make sure that any added capabilities here are really needed BEFORE creating a new course,
        // the creator gets assigned a new teacher level role in the new course right after creation.
        $allowedcreatorcaps = array(
            'moodle/restore:rolldates', 'moodle/category:viewhiddencategories', 'moodle/course:create',
            'moodle/course:viewhiddencourses', 'repository/coursefiles:view', 'repository/filesystem:view',
            'repository/local:view', 'repository/webdav:view', 'shezar/certification:viewhiddencertifications',
            'shezar/program:viewhiddenprograms');

        $files['core'] = "$CFG->dirroot/lib/db/access.php";

        $types = core_component::get_plugin_types();
        foreach ($types as $type => $unused) {
            $plugins = core_component::get_plugin_list($type);
            foreach ($plugins as $name => $fulldir) {
                $file = "$fulldir/db/access.php";
                if (file_exists($file)) {
                    $files[$type . '_' . $name] = $file;
                }
            }
        }

        foreach ($files as $file) {
            $capabilities = array();
            include($file);
            foreach ($capabilities as $capname => $data) {
                if (isset($data['archetypes'])) {
                    foreach ($data['archetypes'] as $archetype => $permission) {
                        $this->assertNotEquals(CAP_PREVENT, $permission, "Do not use CAP_PREVENT in $file, it does nothing");
                        $this->assertNotEquals(CAP_INHERIT, $permission, "Do not use CAP_INHERIT in $file, it does nothing");
                        if ($archetype !== 'guest') {
                            $this->assertNotEquals(CAP_PROHIBIT, $permission, "CAP_PROHIBIT in $file is wrong, when defining roles use it only for guest archetype");
                        }
                        if ($archetype === 'coursecreator' and !in_array($capname, $allowedcreatorcaps)) {
                            $this->assertContains($capname, $allowedcreatorcaps, "Course creator archetype is intended for course creation only");
                        }
                    }
                }
            }
        }
    }
}

