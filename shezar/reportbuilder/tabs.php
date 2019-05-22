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
 * @author Simon Coggins <simon.coggins@shezarlms.com>
 * @package shezar
 * @subpackage reportbuilder
 */

/**
 * Display tabs on report settings pages
 *
 * Included in each settings page
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

// assumes the report id variable has been set in the page
if (!isset($currenttab)) {
    $currenttab = 'general';
}

$tabs = array();
$row = array();
$activated = array();
$inactive = array();

$row[] = new tabobject('general', $CFG->wwwroot . '/shezar/reportbuilder/general.php?id=' . $id, get_string('general'));
$row[] = new tabobject('columns', $CFG->wwwroot . '/shezar/reportbuilder/columns.php?id=' . $id, get_string('columns', 'shezar_reportbuilder'));

if (!shezar_feature_disabled('reportgraphs')) {
    $row[] = new tabobject('graph', $CFG->wwwroot . '/shezar/reportbuilder/graph.php?reportid=' . $id, get_string('graph', 'shezar_reportbuilder'));
}

$row[] = new tabobject('filters', $CFG->wwwroot . '/shezar/reportbuilder/filters.php?id=' . $id, get_string('filters', 'shezar_reportbuilder'));
$row[] = new tabobject('content', $CFG->wwwroot . '/shezar/reportbuilder/content.php?id=' . $id, get_string('content', 'shezar_reportbuilder'));
// hide access tab for embedded reports
if (!$report->embeddedurl) {
    $row[] = new tabobject('access', $CFG->wwwroot . '/shezar/reportbuilder/access.php?id=' . $id, get_string('access', 'shezar_reportbuilder'));
}
$row[] = new tabobject('performance', $CFG->wwwroot . '/shezar/reportbuilder/performance.php?id=' . $id, get_string('performance', 'shezar_reportbuilder'));

$tabs[] = $row;
$activated[] = $currenttab;

// print out tabs
print_tabs($tabs, $currenttab, $inactive, $activated);
