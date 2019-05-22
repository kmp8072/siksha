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
 * @author Jon Sharp <jon.sharp@catalyst-eu.net>
 * @author David Curry <david.curry@shezarlms.com>
 * @package shezar
 * @subpackage certification
 *
 *
 * Events handler: http://docs.moodle.org/dev/Events_API
 *
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\shezar_program\event\program_assigned',
        'callback' => 'certification_event_handler::assigned',
        'includefile' => 'shezar/certification/lib.php',
    ),
    array(
        'eventname' => '\shezar_program\event\program_unassigned',
        'callback' => 'certification_event_handler::unassigned',
        'includefile' => 'shezar/certification/lib.php',
    ),
    array(
        'eventname' => '\shezar_program\event\program_completed',
        'callback' => 'certification_event_handler::completed',
        'includefile' => 'shezar/certification/lib.php',
    ),
    array(
        'eventname' => '\shezar_certification\event\certification_updated',
        'callback' => 'certification_event_handler::certification_updated',
        'includefile' => 'shezar/certification/lib.php',
    ),
);
