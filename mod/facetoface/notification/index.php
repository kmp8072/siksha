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
 * @author Aaron Barnes <aaron.barnes@shezarlms.com>
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @package shezar
 * @subpackage facetoface
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/mod/facetoface/notification/lib.php');

$update = required_param('update', PARAM_INT);
$display = optional_param('display', '', PARAM_ALPHANUM);
$deactivate = optional_param('deactivate', 0, PARAM_INT);
$activate = optional_param('activate', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$restoredefaults = optional_param('restoredefaults', 0, PARAM_BOOL);


if (!$cm = get_coursemodule_from_id('facetoface', $update)) {
    print_error('error:incorrectcoursemoduleid', 'facetoface');
}

if (!$course = $DB->get_record("course", array('id' => $cm->course))) {
    print_error('error:coursemisconfigured', 'facetoface');
}

if (!$facetoface = $DB->get_record('facetoface', array('id' => $cm->instance))) {
    print_error('error:incorrectcoursemodule', 'facetoface');
}

$url = new moodle_url('/mod/facetoface/notification/index.php', array('update' => $cm->id));
$PAGE->set_url($url);

$redirectto = new moodle_url('/mod/facetoface/notification/index.php', array('update' => $update));

if ($display !== '' && !in_array($display, array(MDL_F2F_NOTIFICATION_MANUAL, MDL_F2F_NOTIFICATION_SCHEDULED, MDL_F2F_NOTIFICATION_AUTO))) {
    redirect($redirectto);
    die();
}

require_login($course, true, $cm); // needed to setup proper $COURSE
$context = context_module::instance($cm->id);
require_capability('moodle/course:manageactivities', $context);


// Get all notifications.
$notifications = $DB->get_records('facetoface_notification', array('facetofaceid' => $facetoface->id), 'title,type');

// Count the number of system notifications by type.
// We want to allow duplicate system notifications to be deleted.
$autonotifications = array();
$foundduplicates = false;
foreach ($notifications as $note) {
    if (!isset($autonotifications[$note->conditiontype])) {
        $autonotifications[$note->conditiontype] = 0;
    }
    if ($note->type != MDL_F2F_NOTIFICATION_AUTO) {
        continue;
    }
    $autonotifications[$note->conditiontype]++;

    if ($autonotifications[$note->conditiontype] > 1) {
        $foundduplicates = true;
    }
}

// Check for actions
if ($deactivate || $activate) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    $id = max($deactivate, $activate);
    $notification = new facetoface_notification(array('id' => $id), true);
    if (!$notification->id) {
        print_error('error:notificationdoesnotexist', 'facetoface');
    }

    $notification->status = $deactivate ? 0 : 1;
    $notification->update();

    redirect($redirectto);
}

// Check if we are deleting
if ($delete && $confirm) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    $notification = new facetoface_notification(array('id' => $delete), true);
    if (!$notification->id) {
        print_error('error:notificationdoesnotexist', 'facetoface');
    }

    // If its not an auto notification OR if it is but there are duplicates allow the notification to be deleted.
    if ($notification->type != MDL_F2F_NOTIFICATION_AUTO || $autonotifications[$notification->conditiontype] > 1) {
        // Delete the notification and associated sent and history records.
        $notification->delete();
        shezar_set_notification(get_string('notificationdeleted', 'facetoface'), $redirectto, array('class' => 'notifysuccess'));
    }

    shezar_set_notification(get_string('error:notificationnonduplicate', 'facetoface'), $redirectto);
}

if ($restoredefaults && $confirm) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    // Get all current notifications.
    $currentnotifications = $DB->get_records('facetoface_notification', array('facetofaceid' => $facetoface->id));

    // Recreate all default notifications.
    $defaultnotifications = facetoface_get_default_notifications($facetoface->id)[0];

    // Remove all defaults that exist already.
    foreach ($currentnotifications as $current) {
        unset($defaultnotifications[$current->conditiontype]);
    }

    // Create missing defaults.
    foreach ($defaultnotifications as $default) {
        $default->save();
    }

    shezar_set_notification(get_string('notificationssuccessfullyreset', 'facetoface'), $redirectto, array('class' => 'notifysuccess'));
}

$streditinga = get_string('editinga', 'moodle', 'facetoface');
$strmodulenameplural = get_string('modulenameplural', 'facetoface');

if ($foundduplicates) {
    $url = new moodle_url('/mod/facetoface/notification/index.php', array('update' => $cm->id));
    shezar_set_notification(get_string('notificationduplicatesfound', 'facetoface', $url->out()));
}

$PAGE->set_pagelayout('standard');
$PAGE->set_title($streditinga);
$PAGE->set_heading(format_string($SITE->fullname));
echo $OUTPUT->header();

$icon = '<img src="'.$OUTPUT->pix_url('/facetoface/icon') . '" alt=""/>';


// Print delete confirmation page
if ($delete) {
    $notification = new facetoface_notification(array('id' => $delete), true);
    if (!$notification->id) {
        print_error('error:notificationdoesnotexist', 'facetoface');
    }

    $confirmurl = clone($redirectto);
    $confirmurl->param('delete', $delete);
    $confirmurl->param('sesskey', sesskey());
    $confirmurl->param('confirm', '1');
    echo $OUTPUT->confirm(get_string('deletenotificationconfirm', 'facetoface', format_string($notification->title)), $confirmurl, $redirectto);
    echo $OUTPUT->footer($course);
    unset($confirmurl);
    die();
}

// Print reset confirmation page
if ($restoredefaults) {
    $f2fname = $facetoface->name;

    $confirmurl = clone($redirectto);
    $confirmurl->param('restoredefaults', $restoredefaults);
    $confirmurl->param('sesskey', sesskey());
    $confirmurl->param('confirm', '1');

    echo $OUTPUT->heading(get_string('restoremissingdefaultnotifications', 'facetoface'));
    echo $OUTPUT->confirm(get_string('restoremissingdefaultnotificationsconfirm', 'facetoface', format_string($f2fname)), $confirmurl, $redirectto);
    echo $OUTPUT->footer($course);
    unset($confirmurl);
    die();

}

$heading = get_string('notifications', 'facetoface');
$report_data = array(
    'display'       => $display,
    'update'        => $update,
    'facetofaceid'  => $facetoface->id
);

echo $OUTPUT->heading_with_help($heading, 'notifications', 'facetoface');

// Detect missing default notifications.
$defaultnotifications = facetoface_get_default_notifications($facetoface->id)[0];

foreach ($notifications as $note) {
    unset($defaultnotifications[$note->conditiontype]);
}

if (!empty($defaultnotifications)) {
    $message = get_string('missingdefaultnotifications', 'facetoface', count($defaultnotifications));
    $addmissingdefaulturl = new moodle_url('/mod/facetoface/notification/index.php', array('update' => $cm->id, 'restoredefaults' => 1));
    $link = html_writer::link($addmissingdefaulturl, get_string('missingdefaultsfix', 'facetoface'));
    echo $OUTPUT->notification($message . ' ' . $link, 'notifymessage');
}

$str_edit = get_string('edit', 'moodle');
$str_active = get_string('setactive', 'facetoface');
$str_inactive = get_string('setinactive', 'facetoface');
$str_duplicate = get_string('duplicate');
$str_delete = get_string('delete');
$str_warn_icon = $OUTPUT->pix_icon('i/warning', get_string('notificationduplicatesmessage', 'facetoface'), 'moodle');

$columns = array();
$headers = array();
$columns[] = 'title';
$headers[] = get_string('notificationtitle', 'facetoface');
$columns[] = 'recipients';
$headers[] = get_string('recipients', 'facetoface');
$columns[] = 'type';
$headers[] = get_string('type', 'facetoface');
$columns[] = 'status';
$headers[] = get_string('status', 'facetoface');
$columns[] = 'options';
$headers[] = get_string('options', 'facetoface');

$title = 'facetoface_notifications';
$table = new flexible_table($title);
$table->define_baseurl($CFG->wwwroot . '/mod/facetoface/notification/index.php');
$table->define_columns($columns);
$table->define_headers($headers);
$table->set_attribute('class', 'generalbox mod-facetoface-notification-list');
$table->setup();

foreach ($notifications as $note) {
    $row = array();
    $buttons = array();

    // If its not an auto notification OR if it is but there are duplicates allow the notification to be deleted.
    $warn = ($note->type == MDL_F2F_NOTIFICATION_AUTO && $autonotifications[$note->conditiontype] > 1) ? $str_warn_icon : '';

    $row[] = $warn . $note->title;

    // Create a notification object so we can figure out
    // the recipient string
    $notification = new facetoface_notification();
    $notification->booked = $note->booked;
    $notification->waitlisted = $note->waitlisted;
    $notification->cancelled = $note->cancelled;

    $row[] = $notification->get_recipient_description();

    //Type
    switch ($note->type) {
        case MDL_F2F_NOTIFICATION_MANUAL:
            $typestr = get_string('notificationtype_1', 'facetoface');
            break;

        case MDL_F2F_NOTIFICATION_SCHEDULED:
            $typestr = get_string('notificationtype_2', 'facetoface');
            break;

        case MDL_F2F_NOTIFICATION_AUTO:
            $typestr = get_string('notificationtype_4', 'facetoface');
            break;

        default:
            $typestr = '';
    }

    //Status
    if ($note->status == 1) {
        $statusstr = get_string('active');
    } else {
        $statusstr = get_string('inactive');
    }

    $row[] = $typestr;
    $row[] = $statusstr;

    $buttons[] = $OUTPUT->action_icon(new moodle_url('/mod/facetoface/notification/edit.php', array('f' => $facetoface->id, 'id' => $note->id)), new pix_icon('t/edit', $str_edit));

    if ($note->status == 1) {
        $buttons[] = $OUTPUT->action_icon(new moodle_url('/mod/facetoface/notification/index.php', array('update' => $update, 'deactivate' => $note->id, 'sesskey' => sesskey())), new pix_icon('t/hide', $str_inactive));
    } else {
        $buttons[] = $OUTPUT->action_icon(new moodle_url('/mod/facetoface/notification/index.php', array('update' => $update, 'activate' => $note->id, 'sesskey' => sesskey())), new pix_icon('t/show', $str_active));
    }

    if ($note->type != MDL_F2F_NOTIFICATION_AUTO) {
        $buttons[] = $OUTPUT->action_icon(new moodle_url('/mod/facetoface/notification/edit.php', array('f' => $facetoface->id, 'id' => $note->id, 'duplicate' => '1')), new pix_icon('t/copy', $str_duplicate));
    }

    // If its not an auto notification OR if it is but there are duplicates allow the notification to be deleted.
    if ($note->type != MDL_F2F_NOTIFICATION_AUTO || $autonotifications[$note->conditiontype] > 1) {
        $buttons[] = $OUTPUT->action_icon(new moodle_url('/mod/facetoface/notification/index.php', array('update' => $update, 'delete' => $note->id, 'sesskey' => sesskey())), new pix_icon('t/delete', $str_delete));
    }

    $row[] = implode(' ', $buttons);

    $table->add_data($row);
}

$table->finish_html();

$addlink = new moodle_url('/mod/facetoface/notification/edit.php');

echo $OUTPUT->single_button(new moodle_url($addlink, array('f' => $cm->instance)), get_string('add'), 'get');
echo $OUTPUT->footer($course);
