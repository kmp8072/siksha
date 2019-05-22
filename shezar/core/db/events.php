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
 * @author Jonathan Newman <jonathan.newman@catalyst.net.nz>
 * @package shezar_core
 */

/**
 * This lists event observers.
 */

defined('MOODLE_INTERNAL') || die();

$observers = array (
    array(
        'eventname' => '\shezar_core\event\module_completion',
        'callback'  => 'shezar_core_observer::criteria_course_calc',
    ),
    array(
        'eventname' => '\core\event\user_enrolment_created',
        'callback'  => 'shezar_core_observer::user_enrolment',
    ),
    array(
        'eventname' => '\shezar_core\event\bulk_enrolments_started',
        'callback'  => 'shezar_core_observer::bulk_enrolments_started',
    ),
    array(
        'eventname' => '\shezar_core\event\bulk_enrolments_ended',
        'callback'  => 'shezar_core_observer::bulk_enrolments_ended',
    ),
    array(
        'eventname' => '\core\event\course_completed',
        'callback'  => 'shezar_core_observer::course_criteria_review',
    ),
    array(
        'eventname' => '\core\event\user_deleted',
        'callback'  => 'shezar_core_observer::user_deleted'
    ),

    // Resetting of shezar menu caches.
    array(
        'eventname' => '\shezar_core\event\menuitem_created',
        'callback'  => 'shezar_core_observer::reset_shezar_menu',
    ),
    array(
        'eventname' => '\shezar_core\event\menuitem_deleted',
        'callback'  => 'shezar_core_observer::reset_shezar_menu',
    ),
    array(
        'eventname' => '\shezar_core\event\menuitem_setparent',
        'callback'  => 'shezar_core_observer::reset_shezar_menu',
    ),
    array(
        'eventname' => '\shezar_core\event\menuitem_sortorder',
        'callback'  => 'shezar_core_observer::reset_shezar_menu',
    ),
    array(
        'eventname' => '\shezar_core\event\menuitem_sync',
        'callback'  => 'shezar_core_observer::reset_shezar_menu',
    ),
    array(
        'eventname' => '\shezar_core\event\menuitem_updated',
        'callback'  => 'shezar_core_observer::reset_shezar_menu',
    ),
    array(
        'eventname' => '\shezar_core\event\menuitem_visibility',
        'callback'  => 'shezar_core_observer::reset_shezar_menu',
    ),
    array(
        'eventname' => '\shezar_reportbuilder\event\report_created',
        'callback'  => 'shezar_core_observer::reset_shezar_menu',
    ),
    array(
        'eventname' => '\shezar_reportbuilder\event\report_deleted',
        'callback'  => 'shezar_core_observer::reset_shezar_menu',
    ),
    array(
        'eventname' => '\shezar_reportbuilder\event\report_updated',
        'callback'  => 'shezar_core_observer::reset_shezar_menu',
    ),
    array(
        'eventname' => '\shezar_program\event\program_completed',
        'callback'  => 'shezar_core_observer::reset_shezar_menu',
    ),

);
