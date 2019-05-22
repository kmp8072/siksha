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
 * @author Simon Coggins <simon.coggins@shezarlms.com>
 * @author Aaron Barnes <aaron.barnes@shezarlms.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package shezar_hierarchy
 */

namespace shezar_hierarchy\task;
use shezar_job\job_assignment;

/**
 * Update competency evidence
 */
class update_competencies_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('updatecompetenciestask', 'shezar_hierarchy');
    }

    /**
     * Update competency evidence
     *
     * The order in which we do things is important
     *  1) update all competency items evidence
     *  2) aggregate competency hierarchy depth levels
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/shezar/hierarchy/prefix/competency/lib.php');
        require_once($CFG->dirroot.'/shezar/hierarchy/prefix/competency/evidence/evidence.php');
        require_once($CFG->dirroot.'/shezar/hierarchy/prefix/competency/evidence/lib.php');
        require_once($CFG->dirroot.'/shezar/plan/development_plan.class.php');

        $this->competency_cron_evidence_items();

        // Save time started.
        $timestarted = time();

        // Loop through each depth level, lowest levels first, processing individually.
        $sql = "
            SELECT
                DISTINCT " . $DB->sql_concat_join("'|'", array(sql_cast2char('depthlevel'), sql_cast2char('frameworkid')))
                . " AS depthkey, depthlevel, frameworkid
            FROM
                {comp}
            ORDER BY
                frameworkid,
                depthlevel DESC
        ";

        if ($rs = $DB->get_recordset_sql($sql)) {

            foreach ($rs as $record) {
                // Aggregate this depth level.
                $this->competency_cron_aggregate_evidence($timestarted, $record);
            }

            $rs->close();
        }

        // Mark only aggregated evidence as aggregated.
        if (debugging()) {
            mtrace('Mark all aggregated evidence as aggregated');
        }

        $sql = "
            UPDATE
                {comp_record}
            SET
                reaggregate = 0
            WHERE
                reaggregate <= ?
            AND reaggregate > 0
        ";

        $DB->execute($sql, array($timestarted));
    }

    /**
     * Run installed competency evidence type's aggregation methods
     *
     * Loop through each installed evidence type and run the
     * cron() method if it exists
     *
     * @return  void
     */
    protected function competency_cron_evidence_items() {

        // Process each evidence type.
        global $CFG, $COMPETENCY_EVIDENCE_TYPES;

        foreach ($COMPETENCY_EVIDENCE_TYPES as $type) {

            $object = '\competency_evidence_type_'.$type;
            $source = $CFG->dirroot.'/shezar/hierarchy/prefix/competency/evidenceitem/type/'.$type.'.php';

            if (!file_exists($source)) {
                continue;
            }

            require_once($source);
            $class = new $object();

            // Run the evidence type's cron method, if it has one.
            if (method_exists($class, 'cron')) {

                if (debugging()) {
                    mtrace('Running '.$object.'->cron()');
                }
                $class->cron();
            }
        }
    }

    /**
     * Aggregate competency's evidence items
     *
     * @param   $timestarted    int         Time we started aggregating
     * @param   $depth          object      Depth level record
     * @return  void
     */
    protected function competency_cron_aggregate_evidence($timestarted, $depth) {
        global $DB, $CFG, $COMP_AGGREGATION;

        require_once($CFG->dirroot.'/shezar/plan/lib.php');

        if (debugging()) {
            mtrace('Aggregating competency evidence for depth level '.$depth->depthlevel. ' and frameworkid '. $depth->frameworkid);
        }

        // Grab all competency scale values.
        $scale_values = $DB->get_records('comp_scale_values');

        // Grab all competency evidence items for a depth level
        //
        // A little discussion on what is happening in this horrendous query:
        // In order to keep the number of queries run down, we try grab everything
        // we need in one query, and in an intelligent order.
        //
        // By running a query for each depth level, starting at the "lowest" depth
        // we are using up-to-date data when aggregating any competencies with children.
        //
        // This query will return a group of rows for every competency a user needs
        // reaggregating in. The SQL knows the user needs reaggregating by looking
        // for a competency_evidence field with the reaggregate field set.
        //
        // The group of rows for each competency/user includes one for each of the
        // evidence items, or child competencies for this competency. If either the
        // evidence item or the child competency has data relating to this particular
        // user's competency state in it, we try grab that data too and add it to the
        // related row.
        //
        // Cols returned:
        // evidenceid = the user's competency evidence record id
        // userid = the userid this all relates to
        // competencyid = the competency id
        // path = the competency's path, shows competency and parents, / delimited
        // aggregationmethod = the competency's aggregation method
        // proficienyexpected = the proficiency scale value for this competencies scale
        // itemid = the competencies evidence item id (if we are selecting an evidence item)
        // itemstatus = the competency evidence item status for this user
        // itemproficiency = the competency evidence item proficiency for this user
        // itemmodified = the competency evidence item's last modified time
        // childid = the competencies child id (this is either a child comp or an evidence item)
        // childmodified = the child competency evidence last modified time
        // childproficieny = the child competency evidence proficieny for this user.
        //
        $sql = "
            SELECT DISTINCT
                cr.id AS evidenceid,
                cr.userid,
                cr.proficiency AS currentproficiency,
                c.id AS competencyid,
                c.path,
                c.aggregationmethod,
                proficient.proficient AS proficiencyexpected,
                cc.evidenceid AS itemid,
                ccr.status AS itemstatus,
                ccr.proficiencymeasured AS itemproficiency,
                ccr.timemodified AS itemmodified,
                cc.childid AS childid,
                chldcr.timemodified AS childmodified,
                chldcr.proficiency AS childproficiency
            FROM
                (
                    SELECT
                        id AS evidenceid,
                        competencyid,
                        NULL AS childid
                    FROM
                        {comp_criteria}
                    UNION
                    SELECT
                        NULL AS evidenceid,
                        parentid AS competencyid,
                        id AS childid
                    FROM
                        {comp}
                    WHERE
                        parentid <> 0
                    AND frameworkid = ?
                    AND depthlevel <> ?
                ) cc
            INNER JOIN
                {comp} c
             ON cc.competencyid = c.id
            INNER JOIN
                {comp_record} cr
             ON cr.competencyid = c.id
            INNER JOIN
                {comp_scale_assignments} csa
             ON c.frameworkid = csa.frameworkid
            INNER JOIN
            (
                SELECT csv.scaleid, csv.id AS proficient
                FROM {comp_scale_values} csv
                INNER JOIN
                (
                    SELECT scaleid, MAX(sortorder) AS maxsort
                    FROM {comp_scale_values}
                    WHERE proficient = 1
                    GROUP BY scaleid
                ) grouped
                ON csv.scaleid = grouped.scaleid AND csv.sortorder = grouped.maxsort
            ) proficient
            ON csa.scaleid = proficient.scaleid
            LEFT JOIN
                {comp_criteria_record} ccr
             ON cc.evidenceid = ccr.itemid
            AND cr.userid = ccr.userid
            LEFT JOIN
                {comp_record} chldcr
             ON chldcr.competencyid = cc.childid
            AND cr.userid = chldcr.userid
            WHERE
                cr.reaggregate > 0
            AND cr.reaggregate <= ?
            AND cr.manual = 0
            AND c.depthlevel = ?
            AND c.aggregationmethod <> ?
            ORDER BY
                competencyid,
                userid
        ";

        $params = array($depth->frameworkid, $depth->depthlevel, $timestarted, $depth->depthlevel, $COMP_AGGREGATION['OFF']);

        $rs = $DB->get_recordset_sql($sql, $params);

        $current_user = null;
        $current_competency = null;
        $item_evidence = array();

        while (1) {
            $record_count = 0;

            // Grab records for current user/competency.
            foreach ($rs as $record) {
                // If we are still grabbing the same users evidence.
                $record = (object)$record;
                if ($record->userid === $current_user && $record->competencyid === $current_competency) {
                    $item_evidence[] = $record;
                } else {
                    // If this record is not for the current user/competency, break out of this loop.
                    break;
                }
            }

            // Aggregate.
            if (!empty($item_evidence)) {

                if (debugging()) {
                    mtrace('Aggregating competency items evidence for user '.$current_user.' for competency '.$current_competency);
                }

                $aggregated_status = null;

                // Check each of the items.
                foreach ($item_evidence as $params) {
                    $record_count++;
                    // Get proficiency.
                    $proficiency = max($params->itemproficiency, $params->childproficiency);

                    if (!isset($scale_values[$params->proficiencyexpected])) {
                        if (debugging()) {
                            mtrace('Could not find proficiency expected scale value');
                        }

                        $aggregated_status = null;
                        break;
                    }

                    // Get item's scale value.
                    if (isset($scale_values[$proficiency])) {
                        $item_value = $scale_values[$proficiency];
                    } else {
                        $item_value = null;
                    }

                    // Get item's current proficiency scale value so we know if a proficiency has been set.
                    if (isset($scale_values[$params->currentproficiency])) {
                        $current_value = $scale_values[$params->currentproficiency];
                    } else {
                        $current_value = null;
                    }

                    // Get the competencies minimum proficiency.
                    $min_value = $scale_values[$params->proficiencyexpected];

                    // Flag to break out of aggregation loop (if we already have enough info).
                    $stop_agg = false;

                    // Handle different aggregation types.
                    switch ($params->aggregationmethod) {
                        case $COMP_AGGREGATION['ALL']:
                            // Learner is not yet proficient so no action required.
                            if (!$item_value || $item_value->proficient == 0) {
                                $aggregated_status = null;
                                $stop_agg = true;
                            // If a proficiency level has already been set - don't update it.
                            } else if ($current_value && $current_value->proficient == 1) {
                                $aggregated_status = null;
                                $stop_agg = true;
                            // User is now proficient so set status to minimum proficiency value.
                            } else {
                                $aggregated_status = $min_value->id;
                            }

                            break;

                        case $COMP_AGGREGATION['ANY']:
                            // Check for a proficient flag.
                            if ($item_value && $item_value->proficient == 1) {
                                $aggregated_status = $min_value->id;
                                $stop_agg = true;
                            }

                            break;

                        default:
                            if (debugging()) {
                                mtrace('Aggregation method not supported: '.$params->aggregationmethod);
                                mtrace('Skipping user...');
                                $aggregated_status = null;
                                $stop_agg = true;
                            }
                    }

                    if ($stop_agg) {
                        break;
                    }
                }

                // If aggregated status is not null, update competency evidence.
                if ($aggregated_status !== null) {
                    if (debugging()) {
                        mtrace('Update proficiency to '.$aggregated_status);
                    }

                    // Update the competency evidence.
                    $details = new \stdClass();
                    // Get user's current first job assignment position and organisation (if any).
                    $jobassignment = job_assignment::get_first($current_user, false);
                    if ($jobassignment) {
                        $details->positionid = $jobassignment->positionid;
                        $details->organisationid = $jobassignment->organisationid;
                        unset($posrec);
                    }

                    // Because we do not want to send the alerts, set $notify to false.
                    // We also pass null as the component as we don't have that here.
                    hierarchy_add_competency_evidence($current_competency, $current_user, $aggregated_status, null, $details, true, false);
                    // Hook for plan auto completion.
                    dp_plan_item_updated($current_user, 'competency', $current_competency);
                }
            }

            // If this is the end of the recordset, break the loop.
            if (!$rs->valid()) {
                $rs->close();
                break;
            }

            // New/next user, update user details, reset evidence.
            $current_user = $record->userid;
            $current_competency = $record->competencyid;
            $item_evidence = array();
            $item_evidence[] = $record;
        }

        // Get total records returned.
        if (debugging()) {
            mtrace($record_count . ' records returned');
        }
    }
}
