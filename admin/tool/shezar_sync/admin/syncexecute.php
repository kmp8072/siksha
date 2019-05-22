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
 * @package tool
 * @subpackage shezar_sync
 */
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
require_once($CFG->dirroot.'/admin/tool/shezar_sync/lib.php');

require_login();

$systemcontext = context_system::instance();
require_capability('tool/shezar_sync:manage', $systemcontext);

$pagetitle = get_string('syncexecute', 'tool_shezar_sync');
$PAGE->set_context($systemcontext);
$PAGE->set_url('/admin/tool/shezar_sync/admin/syncexecute.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title($pagetitle);
$PAGE->set_heading(format_string($SITE->fullname));
$execute = optional_param('execute', null, PARAM_BOOL);

echo $OUTPUT->header();

if ($execute) {
    require_sesskey();
    // Increase memory limit
    raise_memory_limit(MEMORY_EXTRA);
    // Stop time outs, this might take a while
    core_php_time_limit::raise(0);
    // Run the sync
    $msg = get_string('runsynccronstart', 'tool_shezar_sync');
    $msg .= get_string('runsynccronend', 'tool_shezar_sync');
    if (!($succeed = tool_shezar_sync_run())) {
        $msg .= ' ' . get_string('runsynccronendwithproblem', 'tool_shezar_sync');
    }
    $url = new moodle_url('/admin/tool/shezar_sync/admin/synclog.php');
    $msg .= html_writer::empty_tag('br') . get_string('viewsynclog', 'tool_shezar_sync', $url->out());
    echo $succeed ? $OUTPUT->notification($msg, 'notifysuccess') : $OUTPUT->notification($msg, 'notifynotice');
}

// Check enabled sync element objects.
$elements = shezar_sync_get_elements(true);
if (empty($elements)) {
    echo $OUTPUT->notification(get_string('noenabledelements', 'tool_shezar_sync'), 'notifyproblem');
    echo $OUTPUT->footer();
    exit();
}
// Display Run Sync table.
$configured = true;
$table = new html_table();
$table->data = array();
$table->head  = array(get_string('element', 'tool_shezar_sync'), get_string('source', 'tool_shezar_sync'), get_string('configuresource', 'tool_shezar_sync'));
foreach ($elements as $element) {
    $cells = array();
    $elname = $element->get_name();
    $elnametext = get_string('displayname:'.$elname, 'tool_shezar_sync');
    $cells[] = new html_table_cell($elnametext);
    // Check a source is enabled.
    if (!$sourceclass = get_config('shezar_sync', 'source_' . $elname)) {
        $configured = false;
        $url = new moodle_url('/admin/tool/shezar_sync/admin/elementsettings.php', array('element' => $elname));
        $link = html_writer::link($url, get_string('sourcenotfound', 'tool_shezar_sync', $elnametext));
        $cells[] = new html_table_cell($link);
        $cells[] = new html_table_cell('');
    } else {
        $source = get_string('displayname:'.$sourceclass, 'tool_shezar_sync');
        $cells[] = new html_table_cell($source);
    }
    // Check source has configs - note get_config returns an object.
    if ($sourceclass) {
        // Sanity checks.
        $nosourceconfigurl = new moodle_url('/admin/tool/shezar_sync/admin/sourcesettings.php',
            array('element' => $elname, 'source' => $sourceclass));
        $nosourceconfiglink = html_writer::link($nosourceconfigurl, get_string('nosourceconfig', 'tool_shezar_sync', $elnametext));
        if (core_text::strtolower($source) == 'csv') {
            $fileaccess = get_config('shezar_sync', 'fileaccess');
            $encoding = get_config('shezar_sync_source_' . $elname . '_csv', 'csv' . $elname . 'encoding');
            if (empty($encoding)) {
                // If the encoding config key doesn't exist then the configuration settings have not been saved.
                $configured = false;
                $cells[] = new html_table_cell($nosourceconfiglink);
            } else if ($fileaccess == FILE_ACCESS_DIRECTORY && !$filesdir = get_config('shezar_sync', 'filesdir')) {
                $configured = false;
                $url = new moodle_url('/admin/tool/shezar_sync/admin/settings.php');
                $link = html_writer::link($url, get_string('nofilesdir', 'tool_shezar_sync'));
                $cells[] = new html_table_cell($link);
            } else {
                $cells[] = new html_table_cell(get_string('sourceconfigured', 'tool_shezar_sync'));
            }
        } else {
            $dbtype = get_config('shezar_sync_source_' . $elname . '_database', 'database_dbtype');
            if (empty($dbtype)) {
                // If the dbtype config key doesn't exist then the configuration settings have not been saved.
                $configured = false;
                $cells[] = new html_table_cell($nosourceconfiglink);
            } else {
                $cells[] = new html_table_cell(get_string('sourceconfigured', 'tool_shezar_sync'));
            }
        }
    }
    $row = new html_table_row($cells);
    $table->data[] = $row;
}
echo html_writer::table($table);

if ($configured) {
    echo $OUTPUT->single_button(new moodle_url('/admin/tool/shezar_sync/admin/syncexecute.php', array('execute' => 1)), get_string('syncexecute', 'tool_shezar_sync'), 'post');
} else {
    // Some problem with configuration.
    echo $OUTPUT->notification(get_string('syncnotconfigured', 'tool_shezar_sync'), 'notifyproblem');
}
echo $OUTPUT->footer();
