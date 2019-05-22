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
 * @author Nathan Lewis <nathan.lewis@shezarlms.com>
 * @package shezar_certification
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/shezar/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/shezar/certification/lib.php');
require_once($CFG->dirroot . '/shezar/program/lib.php');

/**
 * Certification module PHPUnit archive test class.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose shezar_certification_lib_testcase shezar/certification/tests/lib_test.php
 */
class shezar_certification_lib_testcase extends reportcache_advanced_testcase {

    public function test_find_courses_for_certif() {
        $this->resetAfterTest(true);

        // Set up some courses and certifications.
        $courses = array();
        $certifications = array();
        for ($i = 1; $i <= 10; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course();
            $certifications[$i] = $this->getDataGenerator()->create_certification();
        }

        // Set up some courses in the certifications.
        $this->getDataGenerator()->add_courseset_program($certifications[2]->id,
            array($courses[2]->id, $courses[3]->id, $courses[4]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certifications[2]->id,
            array($courses[3]->id, $courses[4]->id, $courses[5]->id), CERTIFPATH_RECERT);

        $this->getDataGenerator()->add_courseset_program($certifications[3]->id,
            array($courses[2]->id, $courses[3]->id, $courses[4]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certifications[3]->id,
            array($courses[3]->id, $courses[4]->id, $courses[5]->id), CERTIFPATH_RECERT);

        $this->getDataGenerator()->add_courseset_program($certifications[4]->id,
            array($courses[2]->id, $courses[3]->id, $courses[4]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certifications[4]->id,
            array($courses[2]->id, $courses[3]->id, $courses[4]->id), CERTIFPATH_RECERT);

        $this->getDataGenerator()->add_courseset_program($certifications[5]->id,
            array($courses[8]->id, $courses[9]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certifications[5]->id,
            array($courses[8]->id, $courses[9]->id), CERTIFPATH_RECERT);

        $this->getDataGenerator()->add_courseset_program($certifications[6]->id,
            array($courses[6]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certifications[6]->id,
            array($courses[7]->id), CERTIFPATH_RECERT);

        $this->getDataGenerator()->add_courseset_program($certifications[7]->id,
            array($courses[6]->id), CERTIFPATH_CERT);

        $this->getDataGenerator()->add_courseset_program($certifications[8]->id,
            array($courses[7]->id), CERTIFPATH_RECERT);

        // Call find_courses_for_certif with each of the three params and ensure that the correct courses are returned.
        $found = array_keys(find_courses_for_certif($certifications[2]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id, $courses[5]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[2]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[2]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array($courses[3]->id, $courses[4]->id, $courses[5]->id), $found);

        $found = array_keys(find_courses_for_certif($certifications[3]->certifid));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id, $courses[5]->id), $found); // Note default fields.
        $found = array_keys(find_courses_for_certif($certifications[3]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[3]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array($courses[3]->id, $courses[4]->id, $courses[5]->id), $found);

        $found = array_keys(find_courses_for_certif($certifications[4]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[4]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[4]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id), $found);

        $found = array_keys(find_courses_for_certif($certifications[5]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array($courses[8]->id, $courses[9]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[5]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array($courses[8]->id, $courses[9]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[5]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array($courses[8]->id, $courses[9]->id), $found);

        $found = array_keys(find_courses_for_certif($certifications[6]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array($courses[6]->id, $courses[7]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[6]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array($courses[6]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[6]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array($courses[7]->id), $found);

        $found = array_keys(find_courses_for_certif($certifications[7]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array($courses[6]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[7]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array($courses[6]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[7]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array(), $found);

        $found = array_keys(find_courses_for_certif($certifications[8]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array($courses[7]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[8]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array(), $found);
        $found = array_keys(find_courses_for_certif($certifications[8]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array($courses[7]->id), $found);

        $found = array_keys(find_courses_for_certif($certifications[9]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array(), $found);
        $found = array_keys(find_courses_for_certif($certifications[9]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array(), $found);
        $found = array_keys(find_courses_for_certif($certifications[9]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array(), $found);
    }

    /**
     * Note that this function is looking at course SET completion dates. A user might have completed the courses required
     * for a couse set, but if they weren't on that path then they won't have a course set completion record for it.
     */
    public function test_certif_get_content_completion_time() {
        $this->resetAfterTest(true);

        // Set up some courses and certifications.
        $courses = array();
        $certifications = array();
        for ($i = 1; $i <= 20; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course();
        }
        for ($i = 1; $i <= 10; $i++) {
            $certifications[$i] = $this->getDataGenerator()->create_certification();
        }

        // Set up some courses in the certifications.
        $this->getDataGenerator()->add_courseset_program($certifications[2]->id,
            array($courses[2]->id, $courses[3]->id, $courses[4]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certifications[2]->id,
            array($courses[5]->id, $courses[6]->id), CERTIFPATH_RECERT);

        $this->getDataGenerator()->add_courseset_program($certifications[4]->id,
            array($courses[7]->id, $courses[8]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certifications[4]->id,
            array($courses[8]->id, $courses[9]->id), CERTIFPATH_RECERT);

        $this->getDataGenerator()->add_courseset_program($certifications[6]->id,
            array($courses[11]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certifications[6]->id,
            array($courses[12]->id), CERTIFPATH_RECERT);

        // Set up some users.
        $users = array();
        for ($i = 1; $i <= 5; $i++) {
            $users[$i] = $this->getDataGenerator()->create_user();
        }

        // Assign all users to all certification as individuals.
        foreach ($certifications as $certification) {
            foreach ($users as $user) {
                $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
            }
        }

        // Mark some users as complete in some courses, with identifiable completion dates.
        $completiondatas = array(
            // Array(userindex, courseindex, time).
            array(1, 1, 1),
            array(1, 2, 1000),
            array(1, 3, 3000),
            array(1, 4, 2000),
            array(1, 5, 1),
            array(1, 6, 99999),
            array(1, 7, 1),
            array(1, 8, 99999),
            array(1, 9, 99999),
            array(1, 11, 1),
            array(1, 12, 99999),
            array(1, 13, 99999),

            array(2, 2, 8000),
            array(2, 3, 6000),
            array(2, 4, 7000),

            array(3, 2, 1000),
            array(3, 3, 4000),
            array(3, 4, 6000),
            array(3, 5, 1),
            array(3, 6, 99999),
            array(3, 7, 5000),
            array(3, 8, 3000),
            array(3, 9, 99999),
            array(3, 11, 1),
            array(3, 12, 99999),

            array(4, 2, 2000),
            array(4, 3, 3000),
            array(4, 4, 4000),
            array(4, 7, 1000),
            array(4, 8, 2000),
            array(4, 9, 99999),
            array(4, 11, 99999),
            array(4, 12, 5000),

            array(5, 2, 1),
            array(5, 4, 99999),
            array(5, 6, 1),
            array(5, 7, 99999),
        );

        // Put user 4 onto recert path for cert 6 (only complete recert path).
        list($certcompletion, $progcompletion) = certif_load_completion($certifications[6]->id, $users[4]->id);
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = 100;
        $certcompletion->timewindowopens = 200;
        $certcompletion->timeexpires = 300;
        $progcompletion->timedue = 300;
        $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
        certif_write_completion($certcompletion, $progcompletion);

        foreach ($completiondatas as $completiondata) {
            list($user, $course, $time) = $completiondata;
            $completion = new completion_completion(array('userid' => $users[$user]->id, 'course' => $courses[$course]->id));
            $completion->mark_complete($time);
        }

        // Put user 4 onto recert path for cert 2 (complete primary and recert paths).
        list($certcompletion, $progcompletion) = certif_load_completion($certifications[2]->id, $users[4]->id);
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = 100;
        $certcompletion->timewindowopens = 200;
        $certcompletion->timeexpires = 300;
        $progcompletion->timedue = 300;
        $progcompletion->timecompleted = 0;
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
        certif_write_completion($certcompletion, $progcompletion);

        $completion = new completion_completion(array('userid' => $users[4]->id, 'course' => $courses[5]->id));
        $completion->mark_complete(1000);
        $completion = new completion_completion(array('userid' => $users[4]->id, 'course' => $courses[6]->id));
        $completion->mark_complete(6000);

        // Call the function, checking if the correct times are returned.

        // These two check that the correct user's results are being returned.
        $this->assertEquals(3000, certif_get_content_completion_time($certifications[2]->certifid, $users[1]->id));
        $this->assertEquals(8000, certif_get_content_completion_time($certifications[2]->certifid, $users[2]->id));

        // These two check that the correct certification's results are being returned (on primary path).
        $this->assertEquals(6000, certif_get_content_completion_time($certifications[2]->certifid, $users[3]->id));
        $this->assertEquals(5000, certif_get_content_completion_time($certifications[4]->certifid, $users[3]->id));

        // These check that the correct certification path results are being returned.
        $this->assertEquals(2000, certif_get_content_completion_time($certifications[4]->certifid, $users[4]->id, CERTIFPATH_CERT));
        $this->assertNull(        certif_get_content_completion_time($certifications[4]->certifid, $users[4]->id, CERTIFPATH_RECERT));
        $this->assertNull(        certif_get_content_completion_time($certifications[6]->certifid, $users[4]->id, CERTIFPATH_CERT));
        $this->assertEquals(5000, certif_get_content_completion_time($certifications[6]->certifid, $users[4]->id, CERTIFPATH_RECERT));
        $this->assertEquals(4000, certif_get_content_completion_time($certifications[2]->certifid, $users[4]->id, CERTIFPATH_CERT));
        $this->assertEquals(6000, certif_get_content_completion_time($certifications[2]->certifid, $users[4]->id, CERTIFPATH_RECERT));

        // Check result when course set not complete (user completed recert path courses, but is on primary path).
        $this->assertNull(certif_get_content_completion_time($certifications[2]->certifid, $users[5]->id));
    }

    /**
     * This tests that write_certif_completion is getting the timecompleted from the correct path courses.
     */
    public function test_write_certif_completion_timecompleted_course_path() {
        global $DB;

        $this->resetAfterTest(true);

        // Set up some stuff.
        $user = $this->getDataGenerator()->create_user();
        $certification = $this->getDataGenerator()->create_certification();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();

        // Add the courses to the certification.
        $this->getDataGenerator()->add_courseset_program($certification->id,
            array($course1->id, $course2->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certification->id,
            array($course3->id, $course4->id), CERTIFPATH_RECERT);

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);

        // Mark all the courses complete, with traceable time completed.
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course1->id));
        $completion->mark_complete(1000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course2->id));
        $completion->mark_complete(2000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course3->id));
        $completion->mark_complete(4000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course4->id));
        $completion->mark_complete(3000);

        // Check the existing data.
        $this->assertEquals(1, $DB->count_records('prog_completion', array('coursesetid' => 0)));
        $this->assertEquals(1, $DB->count_records('certif_completion'));
        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        $this->assertEquals(2000, $progcompletion->timecompleted);
        $this->assertEquals(2000, $certcompletion->timecompleted);

        // The user is now certified. Update the certification so that the window is open.
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        certif_write_completion($certcompletion, $progcompletion); // Contains data validation, so we don't need to check it here.

        // Indirectly call write_certif_completion, causing the user to be marked certified again.
        prog_update_completion($user->id);

        // Verify the the user was marked complete using the dates in the recert path courses.
        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        $this->assertEquals(4000, $progcompletion->timecompleted);
        $this->assertEquals(4000, $certcompletion->timecompleted);

        // Update the certification so that the user is expired.
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $certcompletion->status = CERTIFSTATUS_EXPIRED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_EXPIRED;
        $certcompletion->certifpath = CERTIFPATH_CERT;
        $certcompletion->timecompleted = 0;
        $certcompletion->timewindowopens = 0;
        $certcompletion->timeexpires = 0;
        certif_write_completion($certcompletion, $progcompletion); // Contains data validation, so we don't need to check it here.

        // Indirectly call write_certif_completion, causing the user to be marked certified again.
        prog_update_completion($user->id);

        // Verify the the user was marked complete using the dates in the primary cert path courses.
        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        $this->assertEquals(2000, $certcompletion->timecompleted);
        $this->assertEquals(2000, $progcompletion->timecompleted);
    }

    /**
     * Test getting the progress of a certification with a single course set.
     */
    public function test_prog_display_progress_single_then_courseset() {
        $this->resetAfterTest(true);
        $now = time();

        $certification = $this->getDataGenerator()->create_certification();
        $user = $this->getDataGenerator()->create_user();
        $courses = array();;
        for ($i = 1; $i <= 5; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course();
        }

        // Set up some courses in the certifications.
        $this->getDataGenerator()->add_courseset_program($certification->id,
            array($courses[1]->id, $courses[2]->id, $courses[5]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certification->id,
            array($courses[4]->id, $courses[1]->id), CERTIFPATH_RECERT);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);

        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[1]->id));
        $completion->mark_complete($now);

        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[2]->id));
        $completion->mark_complete($now);

        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[5]->id));
        $completion->mark_complete($now);

        // This is stupid - now its not a float!
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
    }

    /**
     * Test getting the progress of a certification with two course sets.
     */
    public function test_prog_display_progress_two_then_coursesets() {
        $this->resetAfterTest(true);
        $now = time();

        $certification = $this->getDataGenerator()->create_certification();
        $user = $this->getDataGenerator()->create_user();
        $courses = array();;
        for ($i = 1; $i <= 7; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course();
        }

        // Set up some courses in the certifications.
        $this->getDataGenerator()->add_courseset_program($certification->id,
            array($courses[1]->id, $courses[2]->id, $courses[5]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certification->id,
            array($courses[4]->id, $courses[3]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certification->id,
            array($courses[6]->id, $courses[7]->id), CERTIFPATH_RECERT);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);

        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[1]->id));
        $completion->mark_complete($now);

        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[2]->id));
        $completion->mark_complete($now);

        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[5]->id));
        $completion->mark_complete($now);

        $this->assertSame(50.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[4]->id));
        $completion->mark_complete($now);

        $this->assertSame(50.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[3]->id));
        $completion->mark_complete($now);

        // This is stupid - now its not a float!
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
    }

    /**
     * Completes the given course and then asserts that cert progress is as expected.
     *
     * @param float|int $expectedprogress
     * @param program $program
     * @param stdClass $user
     * @param stdClass $course
     * @param int $path
     */
    protected function assert_program_progress_after_course_completion($expectedprogress, program $program, stdClass $user, stdClass $course, $path = CERTIFPATH_CERT) {
        $completion = new completion_completion(['userid' => $user->id, 'course' => $course->id]);
        $completion->mark_complete(time());

        $this->assertTrue($completion->is_complete());

        $actualprogress = prog_display_progress($program->id, $user->id, $path, true);

        $this->assertSame($expectedprogress, $actualprogress, "Progress found to be $actualprogress but expected $expectedprogress");
    }

    /**
     * Assert that the parsed courseset groups are as you would expect.
     *
     * @param program $certification
     * @param array $expectednames An array of expected course set groups, each of which is an array containing the set names.
     */
    protected function assert_courseset_groups_contain_expected_names(program $certification, array $expectednames) {

        $certification = new program($certification->id);
        $courseset_groups = $certification->get_content()->get_courseset_groups(CERTIFPATH_CERT, true);

        $this->assertCount(count($expectednames), $courseset_groups);

        $courseset_group_names = [];
        foreach ($courseset_groups as $group) {
            $coursesetnames = [];
            foreach ($group as $courseset) {
                $coursesetnames[] = $courseset->label;
            }
            $courseset_group_names[] = $coursesetnames;
        }
        $this->assertSame($courseset_group_names, $expectednames);
    }

    /**
     * Test getting the progress of a certification with three course sets.
     *
     *  - Course set 1 (A, B, C)
     *      THEN
     *  - Course set 2 (D, E)
     *      THEN
     *  - Course set 3 (F)
     */
    public function test_prog_display_progress_three_then_coursesets() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 6; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }
        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                    $courses[3],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[4],
                    $courses[5],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[6]
                ]
            ]
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
            ],
            [
                'Course set 2',
            ],
            [
                'Course set 3',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);

        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion((1/3)*100, $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion((1/3)*100, $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion((2/3)*100, $certification, $user, $courses[5]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[6]);
    }

    /**
     * Test getting the progress of a certification with two coursesets using the OR operator.
     */
    public function test_prog_display_progress_two_or_coursesets() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 4; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ]
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[4]);
    }

    /**
     * Test getting the progress of a certification with two course sets using the AND operator.
     */
    public function test_prog_display_progress_two_and_coursesets() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 4; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ]
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
    }

    /**
     * Test getting the progress of a certification with AND plus OR operators.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2
     *     THEN
     *   Course set 3
     *      OR
     *   Course set 4
     *     THEN
     *   Course set 5
     */
    public function test_prog_display_progress_and_plus_or_coursesets() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 10; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[5],
                    $courses[6],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[7],
                    $courses[8],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[9],
                    $courses[10],
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ],
            [
                'Course set 3',
                'Course set 4',
            ],
            [
                'Course set 5',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion((1/3)*100, $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion((1/3)*100, $certification, $user, $courses[5]);
        $this->assert_program_progress_after_course_completion((2/3)*100, $certification, $user, $courses[6]);
        $this->assert_program_progress_after_course_completion((2/3)*100, $certification, $user, $courses[7]);
        $this->assert_program_progress_after_course_completion((2/3)*100, $certification, $user, $courses[8]);
        $this->assert_program_progress_after_course_completion((2/3)*100, $certification, $user, $courses[9]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[10]);
    }

    /**
     * Test getting the progress of a certification with OR plus AND operators.
     *
     * This uses:
     *   Course set 1
     *      OR
     *   Course set 2
     *     THEN
     *   Course set 3
     *      AND
     *   Course set 4
     *     THEN
     *   Course set 5
     */
    public function test_prog_display_progress_or_plus_and_coursesets() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 10; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[5],
                    $courses[6],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[7],
                    $courses[8],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[9],
                    $courses[10],
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ],
            [
                'Course set 3',
                'Course set 4',
            ],
            [
                'Course set 5',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((1/3)*100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion((1/3)*100, $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion((1/3)*100, $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion((1/3)*100, $certification, $user, $courses[5]);
        $this->assert_program_progress_after_course_completion((1/3)*100, $certification, $user, $courses[6]);
        $this->assert_program_progress_after_course_completion((1/3)*100, $certification, $user, $courses[7]);
        $this->assert_program_progress_after_course_completion((2/3)*100, $certification, $user, $courses[8]);
        $this->assert_program_progress_after_course_completion((2/3)*100, $certification, $user, $courses[9]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[10]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *     THEN
     *   Course set 2 (Optional)
     *     THEN
     *   Course set 3
     *
     * Here we are testing three course sets of which one is optional.
     */
    public function test_prog_display_progress_simple_optional_coursesets() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();
        for ($i = 1; $i <= 6; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[5],
                    $courses[6],
                ]
            ]
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1'
            ],
            [
                'Course set 3'
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(50.0, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(50.0, $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion(50.0, $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion(50.0, $certification, $user, $courses[5]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[6]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1 (Optional)
     *
     * Here we are testing that a single optional course set is fine.
     */
    public function test_prog_display_progress_single_optional_courseset() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $course = $generator->create_course(['summary' => 'A short summary']);

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $course
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $course);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1 (Optional)
     *      OR
     *   Course set 2 (Optional)
     *
     * Here we are testing optional or optional.
     */
    public function test_prog_display_progress_OoO() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 2; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        // This is a little skrewy, but is correct.
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1 (Optional)
     *      AND
     *   Course set 2 (Optional)
     *
     * Here we are testing optional and optional.
     */
    public function test_prog_display_progress_OaO() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 2; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2 (Optional)
     *
     * Here we are testing required and optional.
     */
    public function test_prog_display_progress_RaO() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 2; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2'
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1 (Optional)
     *      AND
     *   Course set 2
     *
     * Here we are testing optional and required.
     */
    public function test_prog_display_progress_OaR() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 2; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1 (Optional)
     *      OR
     *   Course set 2
     *
     * Here we are testing optional or required.
     */
    public function test_prog_display_progress_OoR() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 2; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2  (Optional)
     *      AND
     *   Course set 3  (Optional)
     *
     * Here we are testing required and optional and optional.
     */
    public function test_prog_display_progress_RaOaO() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
                'Course set 3',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1  (Optional)
     *      AND
     *   Course set 2  (Optional)
     *      AND
     *   Course set 3
     *
     * Here we are testing optional and optional and required.
     */
    public function test_prog_display_progress_OaOaR() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
                'Course set 3',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1  (Optional)
     *      AND
     *   Course set 2
     *      AND
     *   Course set 3  (Optional)
     *
     * Here we are testing optional and required and optional.
     */
    public function test_prog_display_progress_OaRaO() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
                'Course set 3',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2  (Optional)
     *      AND
     *   Course set 3
     *
     * Here we are testing required and optional and required.
     */
    public function test_prog_display_progress_RaOaR() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
                'Course set 3',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2 (Optional)
     *     THEN
     *   Course set 3 (Optional)
     *      AND
     *   Course set 4
     *     THEN
     *   Course set 5 (Optional)
     *
     * Here we are testing three course sets groups each with an optional courseset.
     */
    public function test_prog_display_progress_RaOtOaRto() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 10; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[5],
                    $courses[6],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[7],
                    $courses[8],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[9],
                    $courses[10],
                ]
            ],
        ]);

        $certification = new program($certification->id);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ],
            [
                'Course set 3',
                'Course set 4',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(50.0, $certification, $user, $courses[2]); // Complete set 1. Set 2 + 3 skipped as optional.
        $this->assert_program_progress_after_course_completion(50.0, $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion(50.0, $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion(50.0, $certification, $user, $courses[5]);
        $this->assert_program_progress_after_course_completion(50.0, $certification, $user, $courses[6]);
        $this->assert_program_progress_after_course_completion(50.0, $certification, $user, $courses[7]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[8]); // Completed set 4. Set 5 skipped as optional.
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[9]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[10]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2 (Some - 0)
     *     THEN
     *   Course set 3 (Some - 0)
     *      AND
     *   Course set 4
     *     THEN
     *   Course set 5 (Some - 0)
     *
     * Here we are testing three course set groups each with a some courses = 0 courseset.
     */
    public function test_prog_display_progress_RaStSaRtS_mincourses_0() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 10; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_SOME,
                'certifpath' => CERTIFPATH_STD,
                'mincourses' => 0,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_SOME,
                'mincourses' => 0,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[5],
                    $courses[6],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[7],
                    $courses[8],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_SOME,
                'mincourses' => 0,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[9],
                    $courses[10],
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ],
            [
                'Course set 3',
                'Course set 4',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(50.0, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(50.0, $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion(50.0, $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion(50.0, $certification, $user, $courses[5]);
        $this->assert_program_progress_after_course_completion(50.0, $certification, $user, $courses[6]);
        $this->assert_program_progress_after_course_completion(50.0, $certification, $user, $courses[7]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[8]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[9]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[10]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2 (Some - 1)
     *     THEN
     *   Course set 3 (Some - 1)
     *      AND
     *   Course set 4
     *     THEN
     *   Course set 5 (Some - 1)
     *
     * Here we are testing three course sets groups each with a some courses = 1 courseset.
     */
    public function test_prog_display_progress_RaStSaRtS_mincourses_1() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 10; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_SOME,
                'certifpath' => CERTIFPATH_STD,
                'mincourses' => 1,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_SOME,
                'mincourses' => 1,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[5],
                    $courses[6],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[7],
                    $courses[8],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_SOME,
                'mincourses' => 1,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[9],
                    $courses[10],
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ],
            [
                'Course set 3',
                'Course set 4',
            ],
            [
                'Course set 5',
            ],
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(0.0, $certification, $user, $courses[2]); // Course set 1 complete now.

        $this->assert_program_progress_after_course_completion((1/3)*100, $certification, $user, $courses[3]); // Course set 2 complete now.
        $this->assert_program_progress_after_course_completion((1/3)*100, $certification, $user, $courses[4]);

        $this->assert_program_progress_after_course_completion((1/3)*100, $certification, $user, $courses[5]); // Course set 3 complete now.
        $this->assert_program_progress_after_course_completion((1/3)*100, $certification, $user, $courses[6]);

        $this->assert_program_progress_after_course_completion((1/3)*100, $certification, $user, $courses[7]);
        $this->assert_program_progress_after_course_completion((2/3)*100, $certification, $user, $courses[8]); // Course set 4 complete now.

        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[9]); // Course set 5 complete now.
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[10]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2  (Optional)
     *      OR
     *   Course set 3  (Optional)
     *
     * Here we are testing required and optional or optional.
     */
    public function test_prog_display_progress_RaOoO() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1  (Optional)
     *      AND
     *   Course set 2  (Optional)
     *      OR
     *   Course set 3
     *
     * Here we are testing optional and optional or required.
     */
    public function test_prog_display_progress_OaOoR() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1  (Optional)
     *      AND
     *   Course set 2
     *      OR
     *   Course set 3  (Optional)
     *
     * Here we are testing optional and required or optional.
     */
    public function test_prog_display_progress_OaRoO() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2  (Optional)
     *      OR
     *   Course set 3
     *
     * Here we are testing required and optional or required.
     */
    public function test_prog_display_progress_RaOoR() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'shezar_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
                'Course set 3'
            ]
        ]);

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0.0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }
}
