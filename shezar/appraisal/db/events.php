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
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @author David Curry <david.curry@shezarlms.com>
 * @package shezar
 * @subpackage shezar_appraisal
 */

/**
 * This file should be used for all appraisal event definitions and handers.
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

$observers = array(
    array(
        'eventname' => '\shezar_appraisal\event\appraisal_activation',
        'callback' => 'shezar_appraisal_observer::appraisal_activation',
    ),
    array(
        'eventname' => '\shezar_appraisal\event\appraisal_stage_completion',
        'callback' => 'shezar_appraisal_observer::appraisal_stage_completion',
    ),
    array(
        'eventname' => '\core\event\user_deleted',
        'callback' => 'shezar_appraisal_observer::user_deleted',
    ),
);
