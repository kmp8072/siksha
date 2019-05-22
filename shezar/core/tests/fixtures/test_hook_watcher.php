<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2013 onwards shezar Learning Solutions LTD
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
 * @author  Petr Skoda <petr.skoda@shezarlearning.com>
 * @package shezar_core
 */

/**
 * Tests for hook manager, base class and callbacks.
 *
 * @author  Petr Skoda <petr.skoda@shezarlearning.com>
 * @package shezar_core
 */

defined('MOODLE_INTERNAL') || die();

class shezar_core_test_hook_watcher {
    public static function listen0(shezar_core_test_hook $hook) {
        $hook->info[] = 0;
    }

    public static function listen1(shezar_core_test_hook $hook) {
        $hook->info[] = 1;
    }

    public static function listen2(shezar_core_test_hook $hook) {
        $hook->info[] = 2;
    }

    public static function listen3(shezar_core_test_hook $hook) {
        $hook->info[] = 3;
        throw new \Exception('some problem');
    }
}
