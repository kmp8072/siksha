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
 * @package shezar_customfield
 */

defined('MOODLE_INTERNAL') || die();

class shezar_customfield_evidence_delete_testcase extends advanced_testcase {

    protected $evidence1 = null;
    protected $evidence2 = null;

    public function setUp() {
        parent::setUp();

        // Create evidence customfields.
        $cfgenerator = $this->getDataGenerator()->get_plugin_generator('shezar_customfield');
        $textids = $cfgenerator->create_text('dp_plan_evidence', array('text1'));
        $multids = $cfgenerator->create_multiselect('dp_plan_evidence', array('multi1'=>array('opt1', 'opt2')));

        $plangenerator = $this->getDataGenerator()->get_plugin_generator('shezar_plan');
        $user = $this->getDataGenerator()->create_user();
        $evidencetype = $plangenerator->create_evidence_type();

        // Create evidence 1.
        $this->evidence1 = $plangenerator->create_evidence(array('evidencetypeid' => $evidencetype->id, 'userid' => $user->id));

        // Add customfields data to evidence 1.
        $cfgenerator->set_text($this->evidence1, $textids['text1'], 'value1', 'evidence', 'dp_plan_evidence');
        $cfgenerator->set_multiselect($this->evidence1, $multids['multi1'], array('opt1', 'opt2'), 'evidence', 'dp_plan_evidence');

        // Create evidence 2.
        $this->evidence2 = $plangenerator->create_evidence(array('evidencetypeid' => $evidencetype->id, 'userid' => $user->id));

        // Add customfields data to evidence 2.
        $cfgenerator->set_text($this->evidence2, $textids['text1'], 'value1', 'evidence', 'dp_plan_evidence');
        $cfgenerator->set_multiselect($this->evidence2, $multids['multi1'], array('opt1', 'opt2'), 'evidence', 'dp_plan_evidence');
    }

    /**
     * Test that customfield data removed with the evidence
     */
    public function test_customfield_deleted_on_event() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/shezar/plan/record/evidence/lib.php');

        $this->resetAfterTest();

        // Assert that records exist.
        $before = $DB->get_records('dp_plan_evidence_info_data', array('evidenceid' => $this->evidence1->id));
        $this->assertCount(2, $before);

        // Get data_param before deletion.
        list($sqlin, $paramin) = $DB->get_in_or_equal(array_keys($before));
        $parambefore = $DB->get_records_sql('SELECT id FROM {dp_plan_evidence_info_data_param} WHERE dataid ' . $sqlin, $paramin);
        $this->assertCount(2, $parambefore);

        // Delete evidence 1.
        evidence_delete($this->evidence1->id);

        // Check that data of customfields for evidence 1 are deleted.
        $afterc1 = $DB->get_records('dp_plan_evidence_info_data', array('evidenceid' => $this->evidence1->id));
        $this->assertCount(0, $afterc1);

        // Check that data of customfields for evidence 2 still exist.
        $afterc2 = $DB->get_records('dp_plan_evidence_info_data', array('evidenceid' => $this->evidence2->id));
        $this->assertCount(2, $afterc2);

        // Check that data_param of customfield for evidence 1 are deleted.
        $paramsafter = $DB->get_records_sql('SELECT id FROM {dp_plan_evidence_info_data_param} WHERE dataid ' . $sqlin, $paramin);
        $this->assertEmpty($paramsafter);

        // Check that data_param of customfield for evidence 2 still exist.
        $program2data =  $DB->get_records('dp_plan_evidence_info_data', array('evidenceid' => $this->evidence2->id));
        list($sql2in, $param2in) = $DB->get_in_or_equal(array_keys($program2data));
        $evidence2dataparam = $DB->get_records_sql('SELECT id FROM {dp_plan_evidence_info_data_param} WHERE dataid ' . $sql2in, $param2in);
        $this->assertCount(2, $evidence2dataparam);
    }
}
