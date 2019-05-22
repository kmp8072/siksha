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
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @package shezar_sync
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/shezar/core/lib/scheduler.php');

/**
 * Take scheduled task config and construct an array to set the form data.
 *
 * @param \core\task\scheduled_task $task The scheduled task
 *
 * @return mixed Complex schedule and data for $form->set_data eg. array(true, array('frequency' => 1, 'schedule'=> 4))
 */
function get_schedule_form_data (\core\task\scheduled_task $task) {
    // Detect if this is to complex to display.
    $count = 0;

    if ($task->get_minute() != '*') {
        $count++;
    }
    if ($task->get_hour() != '*') {
        $count++;
    }
    if ($task->get_day() != '*') {
        $count++;
    }
    if ($task->get_month() != '*') {
        $count++;
    }
    if ($task->get_day_of_week() != '*') {
        $count++;
    }

    $complexscheduling = $count > 1 ? true : false;
    $scheduleconfig = array();

    if (!$complexscheduling) {
        // If not a complex setting fill form elements.
        if ($task->get_minute() != '*') {
            $minute = $task->get_minute();
            // Every x minutes;
            preg_match('/^\*\/[0-9]*/', $minute, $matches);
            if (isset($matches[0]) && $matches[0] == $minute) {
                $validminutes = array(1,2,3,4,5,10,15,20,30);
                $minute = substr($minute, strpos($minute, '/') + 1);
                if (in_array($minute, $validminutes)) {
                    $scheduleconfig['frequency'] = 5;
                    $scheduleconfig['schedule'] =  $minute;
                } else {
                    $complexscheduling = true;
                }
            } else {
                $complexscheduling = true;
            }
        } else if ($task->get_hour() != '*') {
            $hour = $task->get_hour();
            preg_match('/^\*\/[0-9]*/', $hour, $matches);
            if (isset($matches[0]) && $matches[0] == $hour) {
                $validhours = array(1,2,3,4,6,8,12);
                $hour = substr($hour, strpos($hour, '/') + 1);
                if (in_array($hour, $validhours)) {
                    $scheduleconfig['frequency'] = 4;
                    $scheduleconfig['schedule'] = $hour;
                } else {
                    $complexscheduling = true;
                }
            } else if (count(explode(',', $hour)) == 1 && is_numeric($hour)) {
                // Only one hour is specified, we can display this.
                $scheduleconfig['frequency'] = 1;
                $scheduleconfig['schedule'] = $hour;
            } else {
                $complexscheduling = true;
            }
        } else if ($task->get_day() != '*') {
            $day = $task->get_day();
            if (count(explode(',', $day)) == 1 && is_numeric($day)) {
                $scheduleconfig['frequency'] = 3;
                $scheduleconfig['schedule'] = $day;
            } else {
                $complexscheduling = true;
            }
        } else if ($task->get_month() != '*') {
            // We cannot display this in the basic scheduler.
            $complexscheduling = true;
        } else if ($task->get_day_of_week() != '*') {
            $dow = $task->get_day_of_week();
            if (count(explode(',', $dow)) == 1 && is_numeric($dow)) {
                $scheduleconfig['frequency'] = 2;
                $scheduleconfig['schedule'] = $dow;
            } else {
                $complexscheduling = true;
            }
        }
    }

    return array($complexscheduling, $scheduleconfig);
}


/**
 * Save the shezar_sync scheduled task given form data.
 *
 * @param object $data Object containing the frequency and schedule.
 *
 */
function save_scheduled_task_from_form ($data) {
    // Create instance of the task so we can change config.
    $task = \core\task\manager::get_scheduled_task('\shezar_core\task\tool_shezar_sync_task');

    if (isset($data->frequency) && isset($data->schedule)) {
        switch ($data->frequency) {
            case scheduler::DAILY:
                $hour = $data->schedule;
                $task->set_hour($hour);
                // Set all other schedule variables to '*'.
                $task->set_day('*');
                $task->set_minute('*');
                $task->set_day_of_week('*');
                $task->set_month('*');
                break;
            case scheduler::WEEKLY:
                $dayofweek = $data->schedule;
                $task->set_day_of_week($dayofweek);
                // Set all other schedule variables to '*'.
                $task->set_hour('*');
                $task->set_minute('*');
                $task->set_day('*');
                $task->set_month('*');
                break;
            case scheduler::MONTHLY:
                $day = $data->schedule;
                $task->set_day($day);
                // Set all other schedule variables to '*'.
                $task->set_hour('*');
                $task->set_minute('*');
                $task->set_day_of_week('*');
                $task->set_month('*');
                break;
            case scheduler::HOURLY:
                $hour = '*/' . $data->schedule;
                $task->set_hour($hour);
                // Set all other schedule variables to '*'.
                $task->set_day('*');
                $task->set_minute('*');
                $task->set_day_of_week('*');
                $task->set_month('*');
                break;
            case scheduler::MINUTELY:
                $minute = '*/' . $data->schedule;
                $task->set_minute($minute);
                // Set all other schedule variables to '*'.
                $task->set_hour('*');
                $task->set_day('*');
                $task->set_day_of_week('*');
                $task->set_month('*');
                break;
        }
    }

    // Set scheduled task to enabled/disabled.
    $crondisabled = $data->cronenable == 1 ? false : true;
    $task->set_disabled($crondisabled);

    // The task is customised.
    $task->set_customised(true);

    // Write settings to database.
    \core\task\manager::configure_scheduled_task($task);
}
