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
 * @author Andrew Hancox <andrewdchancox@googlemail.com> on behalf of Synergy Learning
 * @package shezar
 * @subpackage enrol_shezar_facetoface
 */

/**
 * Face-to-Face Direct enrolment plugin version specification
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2016092000;       // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2015111606;       // Requires this Moodle version.
$plugin->component = 'enrol_shezar_facetoface';      // Full name of the plugin (used for diagnostics).
$plugin->cron      = 600;