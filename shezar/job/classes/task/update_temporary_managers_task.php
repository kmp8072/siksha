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
 * @author Nathan Lewis <nathan.lewis@shezarlearning.com>
 * @package shezar_job
 */

/**
 * Update temporary managers.
 */
namespace shezar_job\task;

use shezar_job\job_assignment;

class update_temporary_managers_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('updatetemporarymanagerstask', 'shezar_job');
    }

    public function execute() {
        job_assignment::update_temporary_managers();
    }
}
