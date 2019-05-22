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
 * Test our webservice modifications.
 */
class shezar_core_webservice_testcase extends advanced_testcase {
    public function test_webservice_function_called() {
        $function = 'abc';

        $params = array(
            'other' => array(
                'function' => $function
            )
        );
        $event = \core\event\webservice_function_called::create($params);
        $event->set_legacy_logdata(array(SITEID, 'webservice', $function . ' 127.0.0.1' , 0, 2));
        $event->trigger();

        $event2 = \core\event\webservice_function_called::create_from_data($function);
        $event2->set_legacy_logdata(array(SITEID, 'webservice', $function . ' 127.0.0.1' , 0, 2));
        $event2->trigger();

        $this->assertSame($event->get_data(), $event2->get_data());
    }
}
