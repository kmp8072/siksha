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
 * @author Oleg Demeshev <oleg.demeshev@shezarlms.com>
 * @package enrol_shezar_facetoface
 */

defined('MOODLE_INTERNAL') || die();

class enrol_shezar_facetoface_session_order_by_time_testcase extends advanced_testcase {

    public function test_session_order_by_time() {

        $this->resetAfterTest();
        $this->setAdminUser();
        $this->enable_plugin();

        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id));

        $shezar_facetoface = enrol_get_plugin('shezar_facetoface');

        // Session 1
        $session = new stdClass();
        $session->facetoface = $facetoface->id;
        $time = time();
        $sessiondate = new stdClass();
        $sessiondate->timestart = $time + DAYSECS;
        $sessiondate->timefinish = $time + (DAYSECS * 3);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $session->sessiondates = array($sessiondate);
        $sid = $facetofacegenerator->add_session($session);

        // Session 2
        $session = new stdClass();
        $session->facetoface = $facetoface->id;
        $session->sessiondates = array();
        $sid = $facetofacegenerator->add_session($session);

        // Session 3
        $session = new stdClass();
        $session->facetoface = $facetoface->id;
        $time = time();
        $sessiondate = new stdClass();
        $sessiondate->timestart = $time + WEEKSECS;
        $sessiondate->timefinish = $time + (WEEKSECS * 2);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $session->sessiondates = array($sessiondate);
        $sid = $facetofacegenerator->add_session($session);

        // Session 4
        $session = new stdClass();
        $session->facetoface = $facetoface->id;
        $time = time();
        $sessiondate = new stdClass();
        $sessiondate->timestart = $time + MINSECS;
        $sessiondate->timefinish = $time + (DAYSECS * 2);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $session->sessiondates = array($sessiondate);
        $sid = $facetofacegenerator->add_session($session);

        $sessions = facetoface_get_sessions($facetoface->id);
        $enrolablesessions = $shezar_facetoface->get_enrolable_sessions($course->id, null, $facetoface->id);

        foreach ($sessions as $session) {
            // Enrolable sessions don't need info about assets, just add it to compare all other values.
            if (count($enrolablesessions[$session->id]->sessiondates)) {
                $enrolablesessions[$session->id]->sessiondates[0]->assetids = null;
            }

            $this->assertEquals($session->sessiondates, $enrolablesessions[$session->id]->sessiondates);
        }
    }

    private function enable_plugin() {
        $enabled = enrol_get_plugins(true);
        $enabled['shezar_facetoface'] = true;
        $enabled = array_keys($enabled);
        set_config('enrol_plugins_enabled', implode(',', $enabled));
    }
}
