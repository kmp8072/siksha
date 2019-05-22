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
 * @package shezar_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

class shezar_reportbuilder_graph_testcase extends advanced_testcase {
    use shezar_reportbuilder\phpunit\report_testing;

    public function test_normalize_numeric_value() {
        $this->assertSame(111,    shezar_reportbuilder\local\graph::normalize_numeric_value('111'));
        $this->assertSame(-1e10,  shezar_reportbuilder\local\graph::normalize_numeric_value('-1e10'));
        $this->assertSame(111,    shezar_reportbuilder\local\graph::normalize_numeric_value(111));
        $this->assertSame(11.1,   shezar_reportbuilder\local\graph::normalize_numeric_value(11.1));
        $this->assertSame(0,      shezar_reportbuilder\local\graph::normalize_numeric_value(0));
        $this->assertSame(0,      shezar_reportbuilder\local\graph::normalize_numeric_value('0'));
        $this->assertSame(0.0,    shezar_reportbuilder\local\graph::normalize_numeric_value('0.0'));
        $this->assertSame(111,    shezar_reportbuilder\local\graph::normalize_numeric_value(' 111 '));
        $this->assertSame(111.11, shezar_reportbuilder\local\graph::normalize_numeric_value('111.11'));
        $this->assertSame(111.11, shezar_reportbuilder\local\graph::normalize_numeric_value('111,11'));
        $this->assertSame(99,     shezar_reportbuilder\local\graph::normalize_numeric_value('99%'));
        $this->assertSame(99,     shezar_reportbuilder\local\graph::normalize_numeric_value('99 %'));
        $this->assertSame(0,      shezar_reportbuilder\local\graph::normalize_numeric_value('1 111'));
        $this->assertSame(0,      shezar_reportbuilder\local\graph::normalize_numeric_value('111,111.111'));
        $this->assertSame(0,      shezar_reportbuilder\local\graph::normalize_numeric_value('%99'));
        $this->assertSame(0,      shezar_reportbuilder\local\graph::normalize_numeric_value('  '));
        $this->assertSame(0,      shezar_reportbuilder\local\graph::normalize_numeric_value(''));
        $this->assertSame(0,      shezar_reportbuilder\local\graph::normalize_numeric_value(null));
        $this->assertSame(0,      shezar_reportbuilder\local\graph::normalize_numeric_value('abc'));
        $this->assertSame(0,      shezar_reportbuilder\local\graph::normalize_numeric_value(true));
        $this->assertSame(0,      shezar_reportbuilder\local\graph::normalize_numeric_value(false));
        $this->assertSame(10,     shezar_reportbuilder\local\graph::normalize_numeric_value(012));
        $this->assertSame(12.0,   shezar_reportbuilder\local\graph::normalize_numeric_value('012')); // No octal support in strings - cast to float.
        $this->assertSame(800.0,  shezar_reportbuilder\local\graph::normalize_numeric_value('0800')); // No octal support in strings - cast to float.
        $this->assertSame(496,    shezar_reportbuilder\local\graph::normalize_numeric_value(0x1f0));
        $this->assertEquals(0,    shezar_reportbuilder\local\graph::normalize_numeric_value('0x1f0')); // No hexadecimal support in strings - cast to float. PHP7 returns 0, older 0.0.
        $this->assertSame(255,    shezar_reportbuilder\local\graph::normalize_numeric_value(0b11111111));
        $this->assertSame(0,      shezar_reportbuilder\local\graph::normalize_numeric_value('0b11111111')); // No binary support in strings.
    }

    public function test_is_graphable() {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $user->firstaccess  = strtotime('2013-01-10 10:00:00 UTC');
        $user->timemodified = strtotime('2013-01-10 10:00:00 UTC');
        $user->lastlogin    = 0;
        $user->currentlogin = strtotime('2013-01-10 10:00:00 UTC'); // This is the lastlogin in reports.
        $user->timecreated  = strtotime('2013-01-10 10:00:00 UTC');
        $user->firstname  = 'řízek';
        $DB->update_record('user', $user);

        context_user::instance($user->id);

        $rid = $this->create_report('user', 'Test user report 1');

        $report = new reportbuilder($rid, null, false, null, null, true);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, null, null, 0);
        $this->add_column($report, 'user', 'firstaccess', 'month', null, null, 0);
        $this->add_column($report, 'user', 'timemodified', null, null, null, 0);
        $this->add_column($report, 'user', 'lastlogin', null, null, null, 0);
        $this->add_column($report, 'user', 'firstname', null, 'countany', null, 0);
        $this->add_column($report, 'user', 'timecreated', 'weekday', null, null, 0);
        $this->add_column($report, 'statistics', 'coursescompleted', null, null, null, 0);
        $this->add_column($report, 'user', 'namewithlinks', null, null, null, 0);

        $report = new reportbuilder($rid);

        // Let's hack the column options in memory only, hopefully this will continue working in the future...
        $report->columns['user-firstaccess']->displayfunc = 'month';
        $report->columns['user-timemodified']->displayfunc = 'nice_date';
        $report->columns['user-lastlogin']->displayfunc = 'nice_datetime';
        $report->columns['user-firstname']->displayfunc = 'ucfirst';
        $report->columns['user-timecreated']->displayfunc = 'weekday';

        $column = $report->columns['user-id'];
        $this->assertFalse($column->is_graphable($report));

        $column = $report->columns['user-username'];
        $this->assertFalse($column->is_graphable($report));

        $column = $report->columns['user-firstaccess'];
        $this->assertTrue($column->is_graphable($report));

        $column = $report->columns['user-timemodified'];
        $this->assertFalse($column->is_graphable($report));

        $column = $report->columns['user-lastlogin'];
        $this->assertFalse($column->is_graphable($report));

        $column = $report->columns['user-firstname'];
        $this->asserttrue($column->is_graphable($report));

        $column = $report->columns['user-timecreated'];
        $this->assertTrue($column->is_graphable($report));

        $column = $report->columns['statistics-coursescompleted'];
        $this->assertTrue($column->is_graphable($report));

        $column = $report->columns['user-namewithlinks'];
        $this->assertFalse($column->is_graphable($report));
    }
}
