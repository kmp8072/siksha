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
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package shezar
 * @subpackage shezar_appraisal
 */


require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/shezar/appraisal/lib.php');
require_once($CFG->dirroot . '/shezar/appraisal/appraisal_forms.php');

// Check if Appraisals are enabled.
appraisal::check_feature_enabled();

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ACTION);
$messageid = optional_param('messageid', 0, PARAM_INT);

admin_externalpage_setup('manageappraisals');
$systemcontext = context_system::instance();
require_capability('shezar/appraisal:managenotifications', $systemcontext);

$appraisal = new appraisal($id);
$messages = appraisal_message::get_list($id);
$isdraft = appraisal::is_draft($appraisal->id);

$returnurl = new moodle_url('/shezar/appraisal/message.php', array('id' => $id));

switch ($action) {
    case 'edit':
        $form = new appraisal_message_form(null, array('appraisalid' => $id, 'messageid' => $messageid, 'readonly' => !$isdraft));
        if ($form->is_cancelled()) {
            redirect($returnurl);
        }

        if ($isdraft && $data = $form->get_data()) {
            $msg = new appraisal_message($data->messageid);
            $stageid = $data->eventid;
            if ($stageid < 1) {
                $msg->event_appraisal($id);
                $stageiscompleted = 0;
            } else {
                $msg->event_stage($stageid, $data->eventtype);
                $isstagedue = ($data->eventtype == appraisal_message::EVENT_STAGE_DUE);
                $stageiscompleted = ($stageid && $isstagedue && $data->completegrp['stageis']) ? $data->completegrp['complete'] : 0;
            }
            if (isset($data->delta)) {
                $msg->set_delta($data->delta * $data->timinggrp['timing'], $data->deltaperiod);
            } else {
                $msg->set_delta(0, 0);
            }
            $roles = array_keys(array_filter($data->rolegrp));

            $msg->set_roles($roles, $stageiscompleted);

            if ($data->messagetoall == 'all') {
                $msg->set_message(0, $data->messagetitle[0], $data->messagebody[0]);
            } else {
                foreach ($roles as $role) {
                    $msg->set_message($role, $data->messagetitle[$role], $data->messagebody[$role]);
                }
            }
            $msg->save();
            shezar_set_notification(get_string('messagesaved', 'shezar_appraisal'), $returnurl, array('class' => 'notifysuccess'));
        } else if (!$form->is_submitted()) {
            // Load form.
            $data = new stdClass();
            $msg = new appraisal_message($messageid);
            $data->eventid = $msg->stageid;
            $data->eventtype = $msg->type;
            $data->delta = abs($msg->delta);
            $data->timinggrp['timing'] = ($msg->delta == 0) ? 0 : (int)($msg->delta/abs($msg->delta));
            $data->deltaperiod = $msg->deltaperiod;
            $data->rolegrp = array_flip($msg->roles);
            array_walk($data->rolegrp, function(&$val) {
                $val = 1;
            });
            $data->completegrp = array('stageis' => abs($msg->stageiscompleted), 'complete' => $msg->stageiscompleted);
            $messages = $msg->messages;
            $data->messagetoall = 'all';
            foreach ($messages as $role => $message) {
                if ($role > 0) {
                    $data->messagetoall = 'each';
                }
                $data->messagetitle[$role] = $message->name;
                $data->messagebody[$role] = $message->content;
            }
            $form->set_data($data);
            $form->filter_frozen_messages();
        }

        // Init form core js before appraisal.
        $args = $form->_form->getLockOptionObject();
        if (count($args[1]) > 0) {
            $PAGE->requires->js_init_call('M.form.initFormDependencies', $args, true, moodleform::get_js_module());
        }

        $jsmodule = array(
            'name' => 'shezar_appraisal_message',
            'fullpath' => '/shezar/appraisal/js/message.js',
            'requires' => array('json'));
        $PAGE->requires->js_init_call('M.shezar_appraisal_message.init', array($form->_form->getAttribute('id')),
                true, $jsmodule);
        break;
    case 'delete':
        $confirm = optional_param('confirm', 0, PARAM_INT);
        if ($messageid && $confirm) {
            appraisal_message::delete($messageid);
            shezar_set_notification(get_string('messagedeleted', 'shezar_appraisal'), $returnurl,
                    array('class' => 'notifysuccess'));
        }
        break;

}

$output = $PAGE->get_renderer('shezar_appraisal');

$title = $PAGE->title . ': ' . $appraisal->name;
$PAGE->set_title($title);
$PAGE->set_heading($appraisal->name);
$PAGE->navbar->add($appraisal->name);
echo $output->header();
echo $output->heading(format_string($appraisal->name));
echo $output->appraisal_additional_actions($appraisal->status, $appraisal->id);

echo $output->appraisal_management_tabs($appraisal->id, 'messages');

switch ($action) {
    case 'edit':
        if ($messageid) {
            echo $output->heading(get_string('messageedit', 'shezar_appraisal'), 3);
        } else {
            echo $output->heading(get_string('messagecreate', 'shezar_appraisal'), 3);
        }
        $form->display();
        break;
    case 'delete':
        if (!$confirm) {
             echo $output->confirm_delete_message($messageid, $id);
        }
        break;
    default:
        echo $output->heading(get_string('messagesheading', 'shezar_appraisal'), 3);
        if ($isdraft) {
            echo $output->create_message_button($id);
        }
        echo $output->appraisal_message_table($messages);
}
echo $output->footer();
