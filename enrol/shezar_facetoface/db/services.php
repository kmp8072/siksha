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
 * Self enrol plugin external functions and service definitions.
 */

$functions = array(
    'enrol_shezar_facetoface_get_instance_info' => array(
        'classname'   => 'enrol_shezar_facetoface_external',
        'methodname'  => 'get_instance_info',
        'classpath'   => 'enrol/shezar_facetoface/externallib.php',
        'description' => 'shezar_facetoface enrolment instance information.',
        'type'        => 'read'
    )
);
