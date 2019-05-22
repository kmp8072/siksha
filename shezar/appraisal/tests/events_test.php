<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2010 onwards shezar Learning Solutions LTD
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
 * @author David Curry <david.curry@shezarlms.com>
 * @package shezar_appraisal
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/shezar/appraisal/tests/appraisal_testcase.php');

class appraisal_event_test extends appraisal_testcase {

    public function test_appraisal_events() {
        $this->resetAfterTest();
        $sink = $this->redirectEvents();

        // Create an appraisal to throw a creation event.
        $appraisal = new appraisal(0);
        $appraisal->name = 'Test Appraisal';
        $appraisal->save();
        $appraisalid = $appraisal->id;

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal created expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'shezar_appraisal');
        $this->assertEquals($eventdata['eventname'], '\shezar_appraisal\event\appraisal_created');
        $this->assertEquals($eventdata['action'], 'created');
        $this->assertEquals($eventdata['objecttable'], 'appraisal');
        $this->assertEquals($eventdata['objectid'], $appraisalid);

        // Update the appraisal description to throw an updated event.
        $appraisal->description = 'Test Description';
        $appraisal->save();

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal updated expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'shezar_appraisal');
        $this->assertEquals($eventdata['eventname'], '\shezar_appraisal\event\appraisal_updated');
        $this->assertEquals($eventdata['action'], 'updated');
        $this->assertEquals($eventdata['objecttable'], 'appraisal');
        $this->assertEquals($eventdata['objectid'], $appraisalid);

        // Delete the appraisal to throw a deleted event.
        $appraisal->delete();

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal deletion expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'shezar_appraisal');
        $this->assertEquals($eventdata['eventname'], '\shezar_appraisal\event\appraisal_deleted');
        $this->assertEquals($eventdata['action'], 'deleted');
        $this->assertEquals($eventdata['objecttable'], 'appraisal');
        $this->assertEquals($eventdata['objectid'], $appraisalid);

        $sink->close();
    }

    public function test_stage_events() {
        $this->resetAfterTest();
        $sink = $this->redirectEvents();

        // Create an appraisal to add the stage to.
        $appraisal = new appraisal(0);
        $appraisal->name = 'Test Appraisal';
        $appraisal->save();

        $sink->clear();

        $stage = new appraisal_stage(0);
        $stage->appraisalid = $appraisal->id;
        $stage->name = 'Test Stage';
        $stage->save();
        $stageid = $stage->id;

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal_stage created expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'shezar_appraisal');
        $this->assertEquals($eventdata['eventname'], '\shezar_appraisal\event\stage_created');
        $this->assertEquals($eventdata['action'], 'created');
        $this->assertEquals($eventdata['objecttable'], 'appraisal_stage');
        $this->assertEquals($eventdata['objectid'], $stageid);
        $this->assertEquals($eventdata['other']['appraisalid'], $appraisal->id);

        // Update the stage description to throw an updated event.
        $stage->description = 'Test Description';
        $stage->save();

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal_stage deletion expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'shezar_appraisal');
        $this->assertEquals($eventdata['eventname'], '\shezar_appraisal\event\stage_updated');
        $this->assertEquals($eventdata['action'], 'updated');
        $this->assertEquals($eventdata['objecttable'], 'appraisal_stage');
        $this->assertEquals($eventdata['objectid'], $stageid);
        $this->assertEquals($eventdata['other']['appraisalid'], $appraisal->id);

        // Delete the stage to throw a deleted event.
        $stage->delete();

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal_stage updated expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'shezar_appraisal');
        $this->assertEquals($eventdata['eventname'], '\shezar_appraisal\event\stage_deleted');
        $this->assertEquals($eventdata['action'], 'deleted');
        $this->assertEquals($eventdata['objecttable'], 'appraisal_stage');
        $this->assertEquals($eventdata['objectid'], $stageid);
        $this->assertEquals($eventdata['other']['appraisalid'], $appraisal->id);

        $sink->close();
    }

    public function test_page_events() {
        $this->resetAfterTest();
        $sink = $this->redirectEvents();

        // Create an appraisal to add the stage to.
        $appraisal = new appraisal(0);
        $appraisal->name = 'Test Appraisal';
        $appraisal->save();

        $stage = new appraisal_stage(0);
        $stage->appraisalid = $appraisal->id;
        $stage->name = 'Test Stage';
        $stage->save();

        $sink->clear();

        $page = new appraisal_page(0);
        $page->appraisalstageid = $stage->id;
        $page->name = 'Test Page';
        $page->save();
        $pageid = $page->id;

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal_page created expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'shezar_appraisal');
        $this->assertEquals($eventdata['eventname'], '\shezar_appraisal\event\page_created');
        $this->assertEquals($eventdata['action'], 'created');
        $this->assertEquals($eventdata['objecttable'], 'appraisal_stage_page');
        $this->assertEquals($eventdata['objectid'], $pageid);
        $this->assertEquals($eventdata['other']['stageid'], $stage->id);

        // Update the page name to throw an updated event.
        $page->name = 'Test Page (changed)';
        $page->save();

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal_page updated expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'shezar_appraisal');
        $this->assertEquals($eventdata['eventname'], '\shezar_appraisal\event\page_updated');
        $this->assertEquals($eventdata['action'], 'updated');
        $this->assertEquals($eventdata['objecttable'], 'appraisal_stage_page');
        $this->assertEquals($eventdata['objectid'], $pageid);
        $this->assertEquals($eventdata['other']['stageid'], $stage->id);

        // Delete the stage to throw a deleted event.
        appraisal_page::delete($page->id);

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal_page deletion expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'shezar_appraisal');
        $this->assertEquals($eventdata['eventname'], '\shezar_appraisal\event\page_deleted');
        $this->assertEquals($eventdata['action'], 'deleted');
        $this->assertEquals($eventdata['objecttable'], 'appraisal_stage_page');
        $this->assertEquals($eventdata['objectid'], $pageid);
        $this->assertEquals($eventdata['other']['stageid'], $stage->id);

        $sink->close();
    }

    public function test_question_events() {
        $this->resetAfterTest();
        $sink = $this->redirectEvents();

        // Set up an appraisal, stage, and page for the question.
        $appraisal = new appraisal(0);
        $appraisal->name = 'Test Appraisal';
        $appraisal->save();

        $stage = new appraisal_stage(0);
        $stage->appraisalid = $appraisal->id;
        $stage->name = 'Test Stage';
        $stage->save();

        $page = new appraisal_page(0);
        $page->appraisalstageid = $stage->id;
        $page->name = 'Test Page';
        $page->save();

        $sink->clear();

        $question = new appraisal_question(0);
        $question->appraisalstagepageid = $page->id;
        $question->datatype = 'longtext';
        $question->name = 'Test Question';
        $question->save();
        $questionid = $question->id;

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets appraisal_question created expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'shezar_appraisal');
        $this->assertEquals($eventdata['eventname'], '\shezar_appraisal\event\question_created');
        $this->assertEquals($eventdata['action'], 'created');
        $this->assertEquals($eventdata['objecttable'], 'appraisal_quest_field');
        $this->assertEquals($eventdata['objectid'], $questionid);
        $this->assertEquals($eventdata['other']['pageid'], $page->id);

        $question->name = 'Test Question (changed)';
        $question->save();

        $events = $sink->get_events();
        $sink->clear();

        // Check the event data meets appraisal_question updated expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'shezar_appraisal');
        $this->assertEquals($eventdata['eventname'], '\shezar_appraisal\event\question_updated');
        $this->assertEquals($eventdata['action'], 'updated');
        $this->assertEquals($eventdata['objecttable'], 'appraisal_quest_field');
        $this->assertEquals($eventdata['objectid'], $questionid);
        $this->assertEquals($eventdata['other']['pageid'], $page->id);

        appraisal_question::delete($question->id);

        $events = $sink->get_events();
        $sink->clear();

        // Check the event data meets appraisal_question deletion expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals($eventdata['component'], 'shezar_appraisal');
        $this->assertEquals($eventdata['eventname'], '\shezar_appraisal\event\question_deleted');
        $this->assertEquals($eventdata['action'], 'deleted');
        $this->assertEquals($eventdata['objecttable'], 'appraisal_quest_field');
        $this->assertEquals($eventdata['objectid'], $questionid);
        $this->assertEquals($eventdata['other']['pageid'], $page->id);
    }

    /**
     * Test the legacy data for old add_to_log calls, events to test:
     *      - appraisal_updated
     *      - stage_updated
     *      - page_updated
     */
    public function test_legacy_events() {
        $this->resetAfterTest();
        $sink = $this->redirectEvents();

        // Set up an appraisal, stage, and page to edit.
        $appraisal = new appraisal(0);
        $appraisal->name = 'Test Appraisal';
        $appraisal->save();

        $stage = new appraisal_stage(0);
        $stage->appraisalid = $appraisal->id;
        $stage->name = 'Test Stage';
        $stage->save();

        $page = new appraisal_page(0);
        $page->appraisalstageid = $stage->id;
        $page->name = 'Test Page';
        $page->save();

        $sink->clear();

        // Test the legacy data for the appraisal_updated event.
        $appraisal->name = 'Test Appraisal (changed)';
        $appraisal->save();

        $events = $sink->get_events();
        $sink->clear();

        $oldurl = new moodle_url('/shezar/appraisal/general.php', array('id' => $appraisal->id));
        $olddata = array(SITEID, 'appraisal', 'update appraisal', $oldurl, 'General Settings: Appraisal ID=' . $appraisal->id);
        $legacydata = $events[0]->get_legacy_logdata();

        $this->assertEquals($legacydata[0], $olddata[0]);
        $this->assertEquals($legacydata[1], $olddata[1]);
        $this->assertEquals($legacydata[2], $olddata[2]);
        $this->assertEquals($legacydata[3]->out(), $olddata[3]->out());
        $this->assertEquals($legacydata[4], $olddata[4]);

        // Test the legacy data for the stage_updated event.
        $stage->name = 'Test Stage (changed)';
        $stage->save();

        $events = $sink->get_events();
        $sink->clear();

        $params = array('appraisalid' => $appraisal->id, 'action' => 'stageedit', 'id' => $stage->id);
        $oldurl = new moodle_url('/shezar/appraisal/stage.php', $params);
        $olddata = array(SITEID, 'appraisal', 'update stage', $oldurl, 'General Settings: Appraisal ID=' . $appraisal->id);
        $legacydata = $events[0]->get_legacy_logdata();

        $this->assertEquals($legacydata[0], $olddata[0]);
        $this->assertEquals($legacydata[1], $olddata[1]);
        $this->assertEquals($legacydata[2], $olddata[2]);
        $this->assertEquals($legacydata[3]->out(), $olddata[3]->out());
        $this->assertEquals($legacydata[4], $olddata[4]);

        // Test the legacy data for the page_updated event.
        $page->name = 'Test Page (changed)';
        $page->save();

        $events = $sink->get_events();
        $sink->clear();

        $params = array('appraisalstageid' => $stage->id, 'id' => $page->id);
        $oldurl = new moodle_url('/shezar/appraisal/ajax/page.php', $params);
        $olddata = array(SITEID, 'appraisal', 'update page', $oldurl, 'General Settings: Page ID=' . $page->id);
        $legacydata = $events[0]->get_legacy_logdata();

        $this->assertEquals($legacydata[0], $olddata[0]);
        $this->assertEquals($legacydata[1], $olddata[1]);
        $this->assertEquals($legacydata[2], $olddata[2]);
        $this->assertEquals($legacydata[3]->out(), $olddata[3]->out());
        $this->assertEquals($legacydata[4], $olddata[4]);
    }
}
