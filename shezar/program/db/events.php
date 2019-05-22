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
 * @author Ciaran Irvine <ciaran.irvine@shezarlms.com>
 * @author David Curry <david.curry@shezarlms.com>
 * @package shezar
 * @subpackage program
 */

/**
 * this file should be used for all program event definitions and handers.
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

$observers = array(
    array(
        'eventname' => '\shezar_program\event\program_unassigned',
        'callback' => 'shezar_program_observer::unassigned',
    ),
    array(
        'eventname' => '\shezar_program\event\program_completed',
        'callback' => 'shezar_program_observer::completed',
    ),
    array(
        'eventname' => '\shezar_program\event\program_courseset_completed',
        'callback' => 'shezar_program_observer::courseset_completed',
    ),
    array(
        'eventname' => '\core\event\user_loggedin',
        'callback' => 'shezar_program_observer::assignments_firstlogin',
    ),
    array(
        'eventname' => '\core\event\user_deleted',
        'callback' => 'shezar_program_observer::user_deleted',
    ),
    array(
        'eventname' => '\core\event\course_deleted',
        'callback'  => 'shezar_program_observer::course_deleted',
    ),
    array(
        'eventname' => '\shezar_cohort\event\members_updated',
        'callback'  => 'shezar_program_observer::cohort_members_updated',
    ),
    array(
        'eventname' => '\core\event\cohort_member_added',
        'callback'  => 'shezar_program_observer::cohort_members_updated',
    ),
    array(
        'eventname' => '\core\event\cohort_member_removed',
        'callback'  => 'shezar_program_observer::cohort_members_updated',
    ),
    array(
        'eventname' => '\shezar_job\event\job_assignment_updated',
        'callback'  => 'shezar_program_observer::job_assignment_updated',
    ),
);
