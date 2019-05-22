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
 * @author Tom Black <thomas.black@kineo.com>
 * @package admin
 * @subpackage cli
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/clilib.php');      // Include cli only functions.
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/adminlib.php');

cli_heading(get_string('search', 'shezar_core'));
$prompt = get_string('replaceenterfindstring', 'shezar_core');
$search = cli_input($prompt);

$prompt = get_string('replaceenternewstring', 'shezar_core');
$replace = cli_input($prompt);

$a = new stdClass();
$a->search = $search;
$a->replace = $replace;

$prompt = get_string('replaceareyousure', 'shezar_core', $a);
$sure = cli_input($prompt) == 'y';
if (!$sure) {
    die;
}

$prompt = get_string('replacereallysure', 'shezar_core', $a);
$sure = cli_input($prompt) == 'y';
if (!$sure) {
    die;
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('pageheader', 'tool_replace'));

$dbfamily = $DB->get_dbfamily();
if (!in_array($dbfamily, array('mysql', 'postgres', 'mssql'))) {
    cli_problem(get_string('notimplementedshezar', 'shezar_core'));
    die;
}

$developerdebugging = (($CFG->debug & DEBUG_DEVELOPER) === DEBUG_DEVELOPER);
if (!$developerdebugging) {
    cli_problem(get_string('replacedevdebuggingrequired', 'shezar_core'));
    cli_problem(get_string('replacedonotrunlive', 'shezar_core'));
    die;
}

if (!$search || !$replace) {   // Print a form.
    cli_problem(get_string('replacemissingparam', 'shezar_core'));
    die;
}

cli_separator();
db_replace($search, $replace);
cli_separator();

echo get_string('notifyfinished', 'tool_replace');

?>
