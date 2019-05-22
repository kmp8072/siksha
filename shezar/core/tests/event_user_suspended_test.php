<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2014 onwards shezar Learning Solutions LTD
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
 * @package shezar_core
 */

defined('MOODLE_INTERNAL') || die();

class shezar_core_event_user_suspended_testcase extends advanced_testcase {
    public function test_event() {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $user->suspended = 1;
        $DB->set_field('user', 'suspended', 1, array('id' => $user->id));

        $event = \shezar_core\event\user_suspended::create_from_user($user);
        $event->trigger();

        $this->assertSame('user', $event->objecttable);
        $this->assertSame($user->id, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame(CONTEXT_USER, $event->contextlevel);
        $this->assertSame($user->id, $event->contextinstanceid);
        $this->assertSame($user->username, $event->other['username']);

        $this->assertEventContextNotUsed($event);
        $this->assertEventLegacyData($user, $event);
        $this->assertEventLegacyLogData(array(SITEID, 'user', 'suspended', "view.php?id=".$user->id, $user->firstname.' '.$user->lastname), $event);

        try {
            $data = array(
                'objectid' => $user->id,
                'context' => \context_user::instance($user->id),
                'other' => array(
                )
            );
            $event = \shezar_core\event\user_suspended::create($data);
            $event->trigger();
            $this->fail('coding_exception expected when username missing');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }
    }
}
