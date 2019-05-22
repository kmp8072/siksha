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
 * @author Maria Torres <maria.torres@shezarlms.com>
 * @package core_completion
 * @subpackage test
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Data generator.
 *
 * @package    core_completion
 * @category   test
 */
class core_completion_generator extends component_generator_base {
    /**
     * Set activity completion for the course.
     *
     * @param int $courseid The course id
     * @param array $activities Array of activity objects that will be set for the course completion
     * @param int $activityaggregation One of COMPLETION_AGGREGATION_ALL or COMPLETION_AGGREGATION_ANY
     */
    public function set_activity_completion($courseid, $activities, $activityaggregation = COMPLETION_AGGREGATION_ALL) {
        global $CFG;
        require_once($CFG->dirroot.'/completion/criteria/completion_criteria_activity.php');
        require_once($CFG->dirroot.'/completion/criteria/completion_criteria.php');

        $criteriaactivity = array();
        foreach ($activities as $activity) {
            $criteriaactivity[$activity->cmid] = 1;
        }

        if (!empty($criteriaactivity)) {
            $data = new stdClass();
            $data->id = $courseid;
            $data->activity_aggregation = $activityaggregation;
            $data->criteria_activity_value = $criteriaactivity;

            // Set completion criteria activity.
            $criterion = new completion_criteria_activity();
            $criterion->update_config($data);

            // Handle activity aggregation.
            $aggdata = array(
                'course'        => $data->id,
                'criteriatype'  => COMPLETION_CRITERIA_TYPE_ACTIVITY
            );

            $aggregation = new completion_aggregation($aggdata);
            $aggregation->setMethod($data->activity_aggregation);
            $aggregation->save();
        }
    }

    /**
     * Sets one or more courses as criteria for completion of another course.
     *
     * @param stdClass $course - the course that we are setting completion criteria for.
     * @param int[] $criteriacourseids - array of course ids to be completion criteria.
     * @param int $aggregationmethod - COMPLETION_AGGREGATION_ALL or COMPLETION_AGGREGATION_ANY.
     * @return void.
     */
    public function set_course_criteria_course_completion($course, $criteriacourseids, $aggregationmethod = COMPLETION_AGGREGATION_ALL) {
        global $CFG;
        require_once($CFG->dirroot.'/completion/criteria/completion_criteria_course.php');
        require_once($CFG->dirroot.'/completion/criteria/completion_criteria.php');

        if (!empty($criteriacourseids)) {
            $data = new stdClass();
            $data->id = $course->id;
            $data->criteria_course_value = $criteriacourseids;

            // Set completion criteria course.
            $criterion = new completion_criteria_course();
            $criterion->update_config($data);

            // Handle course aggregation.
            $aggdata = array(
                'course'        => $data->id,
                'criteriatype'  => COMPLETION_CRITERIA_TYPE_COURSE
            );

            $aggregation = new completion_aggregation($aggdata);
            $aggregation->setMethod($aggregationmethod);
            $aggregation->save();
        }
    }

    /**
     * Enable completion tracking for this course.
     *
     * @param object $course
     */
    public function enable_completion_tracking($course) {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        // Update course completion settings.
        $course->enablecompletion = COMPLETION_ENABLED;
        $course->completionstartonenrol = 1;
        $course->completionprogressonview = 1;
        update_course($course);
    }

    /**
     * Complete a course as a user at a given time.
     *
     * @param stdClass $course - the course to complete.
     * @param stdClass $user - the user completing the course.
     * @param int|null $time - timestamp for completion time. If null, will use current time.
     */
    public function complete_course($course, $user, $time = null) {
        if (!isset($time)) {
            $time = time();
        }
        $coursecompletion = new completion_completion(array(
            'course' => $course->id,
            'userid' => $user->id
        ));
        $coursecompletion->mark_complete($time);
    }
}
