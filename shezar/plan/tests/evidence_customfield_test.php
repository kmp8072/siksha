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
 * @author Simon Player <simon.player@shezarlms.com>
 * @package shezar_plan
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Test evidence customfield creation.
 */
class evidence_customfield_testcase extends advanced_testcase {

    protected $plangenerator = null;

    protected function setUp() {
        parent::setup();
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->preventResetByRollback();

        $generator = $this->getDataGenerator();

        // Set shezar_plangenerator.
        $this->plangenerator = $generator->get_plugin_generator('shezar_plan');
    }

    public function test_customfield_new_installation_creation() {
        global $DB;

        // On a fresh installation a textarea datatype field should be created.
        $fullname = get_string('evidencedescription', 'shezar_plan');
        $shortname = str_replace(' ', '', get_string('evidencedescriptionshort', 'shezar_plan'));
        $this->assertTrue($DB->record_exists('dp_plan_evidence_info_field', array('shortname' => $shortname, 'fullname' => $fullname)));

        // On a fresh installation a file datatype field should be created.
        $fullname = get_string('evidencefileattachments', 'shezar_plan');
        $shortname = str_replace(' ', '', get_string('evidencefileattachmentsshort', 'shezar_plan'));
        $this->assertTrue($DB->record_exists('dp_plan_evidence_info_field', array('shortname' => $shortname, 'fullname' => $fullname)));

        // On a fresh installation a datetime datatype field should be created.
        $fullname = get_string('evidencedatecompleted', 'shezar_plan');
        $shortname = str_replace(' ', '', get_string('evidencedatecompletedshort', 'shezar_plan'));
        $this->assertTrue($DB->record_exists('dp_plan_evidence_info_field', array('shortname' => $shortname, 'fullname' => $fullname)));
    }

}