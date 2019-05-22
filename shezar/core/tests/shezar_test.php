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
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package shezar_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test function from shezar/core/shezar.php file.
 */
class shezar_core_shezar_testcase extends advanced_testcase {
    public function test_shezar_major_version() {
        global $CFG;

        $majorversion = shezar_major_version();
        $this->assertInternalType('string', $majorversion);
        $this->assertRegExp('/^[0-9]+$/', $majorversion);

        $shezar = null;
        require("$CFG->dirroot/version.php");
        $this->assertSame(0, strpos($shezar->version, $majorversion));

        // Make sure the shezar_major_version() is actually used in lang pack downloads.
        require_once("$CFG->dirroot/lib/componentlib.class.php");
        $installer = new lang_installer();
        $this->assertSame('https://download.shezarlms.com/lang/T' . $majorversion . '/', $installer->lang_pack_url());
    }
}

