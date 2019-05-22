<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace theme_remui;

require_once($CFG->dirroot.'/mod/assign/locallib.php');

// Note: PHP Storm is reporting this unused but it is!
use \theme_remui\activity_meta;

/**
 * Activity functions.
 * These functions are in a class purely for auto loading convenience.
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs. (http://www.wisdmlabs.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class activity {

    /**
     * Get all assignments (for all courses) waiting to be graded.
     *
     * @param $assignmentid
     * @return array $ungraded
     */
    public static function assign_ungraded($courseids) {
        global $DB;

        $ungraded = array();

        $sixmonthsago = time() - YEARSECS / 2;

        // Limit to assignments with grades.
        $gradetypelimit = 'AND gi.gradetype NOT IN (' . GRADE_TYPE_NONE . ',' . GRADE_TYPE_TEXT . ')';

        foreach ($courseids as $courseid) {

            // Get the assignments that need grading.
            list($esql, $params) = get_enrolled_sql(\context_course::instance($courseid), 'mod/assign:submit', 0, true);
            $params['courseid'] = $courseid;

            $sql = "-- remui sql
                    SELECT cm.id AS coursemoduleid, a.id AS instanceid, a.course,
                           a.allowsubmissionsfromdate AS opentime, a.duedate AS closetime,
                           count(DISTINCT sb.userid) AS ungraded
                      FROM {assign} a
                      JOIN {course} c ON c.id = a.course
                      JOIN {modules} m ON m.name = 'assign'

                      JOIN {course_modules} cm
                        ON cm.module = m.id
                       AND cm.instance = a.id

                      JOIN {assign_submission} sb
                        ON sb.assignment = a.id
                       AND sb.latest = 1

                      JOIN ($esql) e
                        ON e.id = sb.userid

 -- Start of join required to make assignments marked via gradebook not show as requiring grading
 -- Note: This will lead to disparity between the assignment page (mod/assign/view.php[questionmark]id=[id])
 -- and the module page will still say that 1 item requires grading.

                 LEFT JOIN {assign_grades} ag
                        ON ag.assignment = sb.assignment
                       AND ag.userid = sb.userid
                       AND ag.attemptnumber = sb.attemptnumber

                 LEFT JOIN {grade_items} gi
                        ON gi.courseid = a.course
                       AND gi.itemtype = 'mod'
                       AND gi.itemmodule = 'assign'
                       AND gi.itemnumber = 0
                       AND gi.iteminstance = cm.instance

                 LEFT JOIN {grade_grades} gg
                        ON gg.itemid = gi.id
                       AND gg.userid = sb.userid

-- End of join required to make assignments classed as graded when done via gradebook

                     WHERE sb.status = 'submitted'
                       AND a.course = :courseid

                       AND (
                           sb.timemodified > gg.timemodified
                           OR gg.finalgrade IS NULL
                       )

                       AND (a.duedate = 0 OR a.duedate > $sixmonthsago)
                 $gradetypelimit
                 GROUP BY instanceid, a.course, opentime, closetime, coursemoduleid ORDER BY a.duedate ASC";
            $rs = $DB->get_records_sql($sql, $params);
            $ungraded = array_merge($ungraded, $rs);
        }

        return $ungraded;
    }

    /**
     * Get Quizzes waiting to be graded.
     *
     * @param $assignmentid
     * @return array $ungraded
     */
    public static function quiz_ungraded($courseids) {
        global $DB;

        $sixmonthsago = time() - YEARSECS / 2;

        $ungraded = array();

        foreach ($courseids as $courseid) {

            // Get people who are typically not students (people who can view grader report) so that we can exclude them!
            list($graderids, $params) = get_enrolled_sql(\context_course::instance($courseid), 'moodle/grade:viewall');
            $params['courseid'] = $courseid;

            $sql = "-- remui SQL
                    SELECT cm.id AS coursemoduleid, q.id AS instanceid, q.course,
                           q.timeopen AS opentime, q.timeclose AS closetime,
                           count(DISTINCT qa.userid) AS ungraded
                      FROM {quiz} q
                      JOIN {course} c ON c.id = q.course AND q.course = :courseid
                      JOIN {modules} m ON m.name = 'quiz'
                      JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = q.id

-- Get ALL ungraded attempts for this quiz

					  JOIN {quiz_attempts} qa ON qa.quiz = q.id
					   AND qa.sumgrades IS NULL

-- Exclude those people who can grade quizzes

                     WHERE qa.userid NOT IN ($graderids)
                       AND qa.state = 'finished'
                       AND (q.timeclose = 0 OR q.timeclose > $sixmonthsago)
                  GROUP BY instanceid, q.course, opentime, closetime, coursemoduleid
                  ORDER BY q.timeclose ASC";

            $rs = $DB->get_records_sql($sql, $params);
            $ungraded = array_merge($ungraded, $rs);
        }

        return $ungraded;
    }

    // The lesson_ungraded function has been removed as it was very tricky to implement.
    // This was because it creates a grade record as soon as a student finishes the lesson.

    /**
     * Get number of ungraded submissions for specific assignment
     * Based on count_submissions_need_grading() in mod/assign/locallib.php
     *
     * @param int $courseid
     * @param int $modid
     * @return int
     */
    public static function assign_num_submissions_ungraded($courseid, $modid) {
        global $DB;

        static $hasgrades = null;
        static $totalsbyid;

        // Use cache to see if assign has grades.
        if ($hasgrades != null && !isset($hasgrades[$modid])) {
            return 0;
        }

        // Use cache to return number of assigns yet to be graded.
        if (!empty($totalsbyid)) {
            if (isset($totalsbyid[$modid])) {
                return intval($totalsbyid[$modid]->total);
            } else {
                return 0;
            }
        }

        // Check to see if this assign is graded.
        $params = array(
            'courseid'      => $courseid,
            'itemtype'      => 'mod',
            'itemmodule'    => 'assign',
            'gradetypenone' => GRADE_TYPE_NONE,
            'gradetypetext' => GRADE_TYPE_TEXT,
        );

        $sql = 'SELECT iteminstance
                FROM {grade_items}
                WHERE courseid = ?
                AND itemtype = ?
                AND itemmodule = ?
                AND gradetype <> ?
                AND gradetype <> ?';

        $hasgrades = $DB->get_records_sql($sql, $params);

        if (!isset($hasgrades[$modid])) {
            return 0;
        }

        // Get grading information for remaining of assigns.
        $coursecontext = \context_course::instance($courseid);
        list($esql, $params) = get_enrolled_sql($coursecontext, 'mod/assign:submit', 0, true);

        $params['submitted'] = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $params['courseid'] = $courseid;

        $sql = "-- remui sql
                 SELECT sb.assignment, count(sb.userid) AS total
                   FROM {assign_submission} sb

                   JOIN {assign} an
                     ON sb.assignment = an.id

              LEFT JOIN {assign_grades} ag
                     ON sb.assignment = ag.assignment
                    AND sb.userid = ag.userid
                    AND sb.attemptnumber = ag.attemptnumber

-- Start of join required to make assignments marked via gradebook not show as requiring grading
-- Note: This will lead to disparity between the assignment page (mod/assign/view.php[questionmark]id=[id])
-- and the module page will still say that 1 item requires grading.

              LEFT JOIN {grade_items} gi
                     ON gi.courseid = an.course
                    AND gi.itemtype = 'mod'
                    AND gi.itemmodule = 'assign'
                    AND gi.itemnumber = 0
                    AND gi.iteminstance = an.id

              LEFT JOIN {grade_grades} gg
                     ON gg.itemid = gi.id
                    AND gg.userid = sb.userid

-- End of join required to make assignments classed as graded when done via gradebook

-- Start of enrolment join to make sure we only include students that are allowed to submit. Note this causes an ALL
-- join on mysql!
                   JOIN ($esql) e
                     ON e.id = sb.userid
-- End of enrolment join

                  WHERE an.course = :courseid
                    AND sb.timemodified IS NOT NULL
                    AND sb.status = :submitted
                    AND sb.latest = 1

                    AND (
                        sb.timemodified > gg.timemodified
                        OR gg.finalgrade IS NULL
                    )

                GROUP BY sb.assignment
               ";

        $totalsbyid = $DB->get_records_sql($sql, $params);
        return isset($totalsbyid[$modid]) ? intval($totalsbyid[$modid]->total) : 0;
    }

    /**
     * Get number of ungraded quiz attempts for specific quiz
     *
     * @param int $courseid
     * @param int $quizid
     * @return int
     */
    public static function quiz_num_submissions_ungraded($courseid, $quizid) {
        global $DB;

        static $totalsbyquizid;

        $coursecontext = \context_course::instance($courseid);
        // Get people who are typically not students (people who can view grader report) so that we can exclude them!
        list($graderids, $params) = get_enrolled_sql($coursecontext, 'moodle/grade:viewall');
        $params['courseid'] = $courseid;

        if (!isset($totalsbyquizid)) {
            // Results are not cached.
            $sql = "-- remui sql
                    SELECT q.id, count(DISTINCT qa.userid) as total
                      FROM {quiz} q

-- Get ALL ungraded attempts for this quiz

					  JOIN {quiz_attempts} qa ON qa.quiz = q.id
					   AND qa.sumgrades IS NULL

-- Exclude those people who can grade quizzes

                     WHERE qa.userid NOT IN ($graderids)
                       AND qa.state = 'finished'
                       AND q.course = :courseid
                     GROUP BY q.id";
            $totalsbyquizid = $DB->get_records_sql($sql, $params);
        }

        if (!empty($totalsbyquizid)) {
            if (isset($totalsbyquizid[$quizid])) {
                return intval($totalsbyquizid[$quizid]->total);
            }
        }

        return 0;
    }
}
