<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2015 onwards shezar Learning Solutions LTD
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
 * @author Petr Skoda <petr.skdoa@shezarlms.com>
 * @package shezar_connect
 */

/**
 * This lists events observed by TC server.
 */

defined('MOODLE_INTERNAL') || die();

$observers = array (
    array(
        'eventname' => '\core\event\user_loggedin',
        'callback'  => '\shezar_connect\observer::user_loggedin',
    ),
    array(
        'eventname' => '\core\event\user_loggedout',
        'callback'  => '\shezar_connect\observer::user_loggedout',
    ),
    array(
        'eventname' => '\core\event\course_created',
        'callback'  => '\shezar_connect\observer::course_created',
    ),
    array(
        'eventname' => '\core\event\cohort_created',
        'callback'  => '\shezar_connect\observer::cohort_created',
    ),
);
