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
 * @author David Curry <david.curry@toraralearning.com>
 * @package shezar_cohort
 */

/**
 * Move old pos_assignment rules to job_assignment rules.
 *
 * POSITION FIELD RULES
 * pos | name               => primaryjobassign | posname
 * pos | id                 => primaryjobassign | posid
 * pos | idnumber           => primaryjobassign | posidnumber
 * pos | postype            => primaryjobassign | postype
 * pos | startdate          => primaryjobassign | posassigndate
 *
 * ORGANISATION FIELD RULES
 * org | id                 => primaryjobassign | orgid
 * org | idnumber           => primaryjobassign | orgidnumber
 * org | orgtype            => primaryjobassign | orgtype
 *
 * POS/JOB ASSIGNMENT FIELD RULES
 * pos | timevalidfrom      => primaryjobassign | startdate
 * pos | timevalidto        => primaryjobassign | enddate
 *
 * MANAGER FIELD RULES
 * pos | hasdirectreports   => primaryjobassign | hasdirectreports
 * pos | reportsto          => primaryjobassign | manager
 */
function shezar_cohort_migrate_position_rules() {
    global $DB;

    // Update all of the cohort_rules.name fields first.
    $rulenames = array(
        array('new' => 'positions', 'rtype' => 'pos', 'rname' => 'id'),
        array('new' => 'posidnumbers', 'rtype' => 'pos', 'rname' => 'idnumber'),
        array('new' => 'posassigndates', 'rtype' => 'pos', 'rname' => 'startdate'),
        array('new' => 'posnames', 'rtype' => 'pos', 'rname' => 'name'),
        array('new' => 'postypes', 'rtype' => 'pos', 'rname' => 'postype'),
        array('new' => 'organisations', 'rtype' => 'org', 'rname' => 'id'),
        array('new' => 'orgidnumbers', 'rtype' => 'org', 'rname' => 'idnumber'),
        array('new' => 'orgtypes', 'rtype' => 'org', 'rname' => 'orgtype'),
        array('new' => 'startdates', 'rtype' => 'pos', 'rname' => 'timevalidfrom'),
        array('new' => 'enddates', 'rtype' => 'pos', 'rname' => 'timevalidto'),
        array('new' => 'managers', 'rtype' => 'pos', 'rname' => 'reportsto'),
    );

    foreach ($rulenames as $params) {
        $sqlrulename = "UPDATE {cohort_rules}
                           SET name = :new
                         WHERE ruletype = :rtype
                           AND name = :rname";

        $DB->execute($sqlrulename, $params);
    }

    // Now something a little special for pos/org customfield rules since they get dynamic names.
    $sqlcustrule = "UPDATE {cohort_rules}
                       SET name = " . $DB->sql_concat('ruletype', 'name') . "
                     WHERE name LIKE 'customfield%'
                       AND (ruletype = 'pos' OR ruletype = 'org')";
    $DB->execute($sqlcustrule);

    // Finally update the cohort_rules.ruletype field.
    $sqlruletype = "UPDATE {cohort_rules}
                       SET ruletype = 'alljobassign'
                     WHERE ruletype = 'pos'
                        OR ruletype = 'org'";
    $DB->execute($sqlruletype);

    return true;
}
