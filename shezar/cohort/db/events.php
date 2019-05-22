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
 * @author Jake Salmon <jake.salmon@kineo.com>
 * @package shezar
 * @subpackage cohort
 */

/**
 * this file should be used for all the custom event definitions and handers.
 * event names should all start with shezar_.
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

$observers = array(
    array(
        'eventname' => '\shezar_customfield\event\profilefield_deleted',
        'callback' => 'shezarcohort_event_handler::profilefield_deleted',
        'includefile' => '/shezar/cohort/lib.php',
    ),
    array( // Call the updated function as these need to do the same thing.
        'eventname' => '\shezar_hierarchy\event\position_deleted',
        'callback' => 'shezarcohort_event_handler::position_updated',
        'includefile' => '/shezar/cohort/lib.php',
    ),
    array(
        'eventname' => '\shezar_hierarchy\event\position_updated',
        'callback' => 'shezarcohort_event_handler::position_updated',
        'includefile' => '/shezar/cohort/lib.php',
    ),
    array( // Call the updated function as these need to do the same thing.
        'eventname' => '\shezar_hierarchy\event\organisation_deleted',
        'callback' => 'shezarcohort_event_handler::organisation_updated',
        'includefile' => '/shezar/cohort/lib.php',
    ),
    array(
        'eventname' => '\shezar_hierarchy\event\organisation_updated',
        'callback' => 'shezarcohort_event_handler::organisation_updated',
        'includefile' => '/shezar/cohort/lib.php',
    ),
    array(
        'eventname'   => '\shezar_cohort\event\members_updated',
        'callback' => 'shezarcohort_event_handler::members_updated',
        'includefile' => '/shezar/cohort/lib.php',
    ),
);
