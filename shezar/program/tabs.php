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
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @package shezar
 * @subpackage program
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

$id = optional_param('id', 0, PARAM_INT);
$edit = optional_param('edit', 'off', PARAM_TEXT);
$iscertif = optional_param('iscertif', 0, PARAM_BOOL);
if ($id) {
    $iscertif = ($DB->get_field('prog', 'certifid', array('id' => $id)) ? 1 : 0);
}

if (!isset($currenttab)) {
    $currenttab = 'details';
}

if (isset($programcontext)) {
    $context = $programcontext;
} else if (isset($program)) {
    $context = $program->get_context();
} else if (isset($systemcontext)) {
    $context = $systemcontext;
} else {
    $context = context_system::instance();
}

$toprow = array();
$secondrow = array();
$activated = array();
$inactive = array();

// Overview Tab
$toprow[] = new tabobject('overview', $CFG->wwwroot.'/shezar/program/edit.php?id='.$id, get_string('overview', 'shezar_program'));
if (substr($currenttab, 0, 7) == 'overview'){
    $activated[] = 'overview';
}

// Details Tab
if (has_capability('shezar/program:configuredetails', $context)) {
    //disable details link if creating a new program to avoid fatal error
    $url = ($id == 0) ? '#' : $CFG->wwwroot.'/shezar/program/edit.php?id='.$id.'&amp;action=edit';
    $toprow[] = new tabobject('details', $url, get_string('details', 'shezar_program'));
    if (substr($currenttab, 0, 7) == 'details'){
        $activated[] = 'details';
    }
}

// Content Tab
if (has_capability('shezar/program:configurecontent', $context)) {
    $toprow[] = new tabobject('content', $CFG->wwwroot.'/shezar/program/edit_content.php?id='.$id, get_string('content', 'shezar_program'));
    if (substr($currenttab, 0, 7) == 'content'){
        $activated[] = 'content';
    }
}

// Assignments Tab
if (has_capability('shezar/program:configureassignments', $context)) {
    $toprow[] = new tabobject('assignments', $CFG->wwwroot.'/shezar/program/edit_assignments.php?id='.$id, get_string('assignments', 'shezar_program'));
    if (substr($currenttab, 0, 11) == 'assignments'){
        $activated[] = 'assignments';
    }
}

// Messages Tab
if (has_capability('shezar/program:configuremessages', $context)) {
    $toprow[] = new tabobject('messages', $CFG->wwwroot.'/shezar/program/edit_messages.php?id='.$id, get_string('messages', 'shezar_program'));
    if (substr($currenttab, 0, 8) == 'messages'){
        $activated[] = 'messages';
    }
}

// Certification Tab
if ($iscertif && has_capability('shezar/certification:configurecertification', $context)
    && shezar_feature_visible('certifications')) {
    $toprow[] = new tabobject('certification', $CFG->wwwroot.'/shezar/certification/edit_certification.php?id='.$id,
                    get_string('certification', 'shezar_certification'));
    if (substr($currenttab, 0, 13) == 'certification') {
        $activated[] = 'certification';
    }
}

if (!empty($CFG->enableprogramcompletioneditor) &&
    has_capability('shezar/program:editcompletion', $context)) {
    $toprow[] = new tabobject('completion', $CFG->wwwroot.'/shezar/program/completion.php?id='.$id,
        get_string('completion', 'shezar_program'));
    if (substr($currenttab, 0, 10) == 'completion') {
        $activated[] = 'completion';
    }
}

// Exceptions Report Tab
// Only show if there are exceptions or you are on the exceptions tab already
if (has_capability('shezar/program:handleexceptions', $context) && ($exceptions || (substr($currenttab, 0, 10) == 'exceptions'))) {
    $exceptioncount = $exceptions ? $exceptions : '0';
    $toprow[] = new tabobject('exceptions', $CFG->wwwroot.'/shezar/program/exceptions.php?id='.$id, get_string('exceptions', 'shezar_program', $exceptioncount));
    if (substr($currenttab, 0, 10) == 'exceptions'){
        $activated[] = 'exceptions';
    }
}

if (!$id) {
    $inactive += array('overview', 'content', 'assignments', 'messages', 'certification', 'completion');
}

$tabs = array($toprow);

// print out tabs
print_tabs($tabs, $currenttab, $inactive, $activated);
