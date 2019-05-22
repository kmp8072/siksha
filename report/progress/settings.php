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
 * @package report
 * @subpackage progress
 */

defined('MOODLE_INTERNAL') || die;

// Show completion information for inactive enrolments in course activity completion report.
$settings->add(new admin_setting_configcheckbox('report_progress/showcompletiononlyactiveenrols',
    new lang_string('showonlyactiveenrols', 'report_progress'),
    new lang_string('showonlyactiveenrols_help', 'report_progress'), 1));
