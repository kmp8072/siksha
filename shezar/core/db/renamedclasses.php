<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2016 onwards shezar Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@shezarlearning.com>
 * @package shezar_core
 */

/**
 * This assists with autoloading when a class or its namespace has been renamed.
 * See lib/db/renamedclasses.php for further information on this type of file.
 */

defined('MOODLE_INTERNAL') || die();

// The old class name is the key, the new class name is the value.
// The array must be called $renamedclasses.
$renamedclasses = array(
    'shezar_core\task\update_temporary_managers_task' => 'shezar_job\task\update_temporary_managers_task',
);
