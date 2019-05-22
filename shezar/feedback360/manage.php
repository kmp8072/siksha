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
 * @author David Curry <david.curry@shezarlms.com>
 * @package shezar
 * @subpackage shezar_feedback360
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/shezar/feedback360/lib.php');

// Check if 360 Feedbacks are enabled.
feedback360::check_feature_enabled();

$action = optional_param('action', '', PARAM_ACTION);

admin_externalpage_setup('managefeedback360');
$systemcontext = context_system::instance();
require_capability('shezar/feedback360:managefeedback360', $systemcontext);

$renderer = $PAGE->get_renderer('shezar_feedback360');

$feedback360s = feedback360::get_manage_list();

switch ($action) {
    case 'delete':
        $returnurl = new moodle_url('/shezar/feedback360/manage.php');
        $id = required_param('id', PARAM_INT);
        $feedback360 = new feedback360($id);

        if (in_array($feedback360->status, array(feedback360::STATUS_DRAFT, feedback360::STATUS_CLOSED))) {
            $confirm = optional_param('confirm', 0, PARAM_INT);
            if ($confirm == 1) {
                require_sesskey();
                $feedback360->delete();
                shezar_set_notification(get_string('deletedfeedback360', 'shezar_feedback360'), $returnurl,
                        array('class' => 'notifysuccess'));
            }
        } else {
            shezar_set_notification(get_string('error:feedback360isactive', 'shezar_feedback360'), $returnurl,
                    array('class' => 'notifyproblem'));
        }
        break;
    case 'copy':
        $id = required_param('id', PARAM_INT);

        $cloned_feedback360_id = feedback360::duplicate($id);

        $returnurl = new moodle_url('/shezar/feedback360/general.php', array('id' => $cloned_feedback360_id));

        shezar_set_notification(get_string('feedback360cloned', 'shezar_feedback360'), $returnurl,
                array('class' => 'notifysuccess'));

        break;
}

echo $renderer->header();
switch ($action) {
    case 'delete':
        echo $renderer->heading(get_string('deletefeedback360s', 'shezar_feedback360', $feedback360->name));
        echo $renderer->confirm_delete_feedback360($feedback360);
        break;
    default:
        echo $renderer->heading(get_string('managefeedback360s', 'shezar_feedback360'));
        echo $renderer->create_feedback360_button();
        echo $renderer->feedback360_manage_table($feedback360s);
}
echo $renderer->footer();
