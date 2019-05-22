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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package shezar
 * @subpackage shezar_sync
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . "/{$CFG->admin}/tool/shezar_sync/admin/forms.php");
require_once($CFG->dirroot . '/shezar/core/lib/scheduler.php');
require_once($CFG->dirroot . "/{$CFG->admin}/tool/shezar_sync/locallib.php");

admin_externalpage_setup('shezarsyncsettings');

// Schedule.
$taskname = 'shezar_core\task\tool_shezar_sync_task';
$task = \core\task\manager::get_scheduled_task($taskname);

list($complexscheduling, $scheduleconfig) = get_schedule_form_data($task);

$form = new shezar_sync_config_form(null, array('complexscheduling' => $complexscheduling));

// Process actions.
if ($data = $form->get_data()) {
    // File access.
    if (isset($data->fileaccess)) {
        set_config('fileaccess', $data->fileaccess, 'shezar_sync');
    }
    if (isset($data->filesdir)) {
        set_config('filesdir', trim($data->filesdir), 'shezar_sync');
    }

    // Notifications.
    set_config('notifymailto', $data->notifymailto, 'shezar_sync');

    $notifytypes = !empty($data->notifytypes) ? implode(',', array_keys($data->notifytypes)) : '';
    set_config('notifytypes', $notifytypes, 'shezar_sync');

    save_scheduled_task_from_form($data);

    shezar_set_notification(get_string('settingssaved', 'tool_shezar_sync'), $PAGE->url, array('class'=>'notifysuccess'));
}

// Set form data.
$config = get_config('shezar_sync');
if (!empty($config->notifytypes)) {
    $config->notifytypes = explode(',', $config->notifytypes);
    foreach ($config->notifytypes as $index => $issuetype) {
        $config->notifytypes[$issuetype] = 1;
        unset($config->notifytypes[$index]);
    }
}

// Set schedule form elements.
$config->schedulegroup = $scheduleconfig;
$config->cronenable = $task->get_disabled() ? false : true;

$form->set_data($config);

// Output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('generalsettings', 'tool_shezar_sync'));

$form->display();

echo $OUTPUT->footer();
