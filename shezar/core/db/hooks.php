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
 * @author Sam Hemelryk <sam.hemelryk@shezarlearning.com>
 * @package shezar_core
 */

$watchers = [
    [
        // Called at the end of course_edit_form::definition.
        // Used by shezar to add shezar specific elements to the course definition.
        'hookname' => '\core_course\hook\edit_form_definition_complete',
        'callback' => 'shezar_core\watcher\core_course_edit_form::extend_form',
        'priority' => 100,
    ],
    [
        // Called immediately before the course_edit_form instance is displayed.
        // Used by shezar to add any required JS for the custom elements we've added.
        'hookname' => '\core_course\hook\edit_form_display',
        'callback' => 'shezar_core\watcher\core_course_edit_form::display_form',
        'priority' => 100,
    ],
    [
        // Called after the initial form data has been saved, before redirect.
        // Used by shezar to save data from our custom elements.
        'hookname' => '\core_course\hook\edit_form_save_changes',
        'callback' => 'shezar_core\watcher\core_course_edit_form::save_form',
        'priority' => 100,
    ]
];