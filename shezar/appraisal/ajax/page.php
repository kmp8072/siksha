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

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot . '/shezar/core/utils.php');
require_once($CFG->dirroot . '/shezar/appraisal/lib.php');
require_once($CFG->dirroot . '/shezar/appraisal/appraisal_forms.php');

$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ACTION);

require_login(null, true, null, true, true);
$systemcontext = context_system::instance();
require_capability('shezar/appraisal:managepageelements', $systemcontext);
$PAGE->set_context($systemcontext);

$page = new appraisal_page($id);
if ($page->appraisalstageid < 1) {
    $stageid = required_param('appraisalstageid', PARAM_INT);
} else {
    $stageid = $page->appraisalstageid;
}

$stage = new appraisal_stage($stageid);

$output = $PAGE->get_renderer('shezar_appraisal');
$returnurl = new moodle_url('/shezar/appraisal/stage.php', array('id' => $stageid));
if (!appraisal::is_draft($stage->appraisalid)) {
    shezar_set_notification(get_string('error:appraisalnotdraft', 'shezar_appraisal'), $returnurl);
}

switch($action) {
    case 'pos':
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', 'error');
        }
        $pos = required_param('pos', PARAM_INT);
        appraisal_page::reorder($id, $pos);
        if (is_ajax_request($_SERVER)) {
            echo 'success';
            return;
        }
        shezar_set_notification(get_string('pageupdated', 'shezar_appraisal'), $returnurl, array('class' => 'notifysuccess'));
        break;
    case 'posup':
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', 'error');
        }
        appraisal_page::reorder($id, $page->sortorder - 1);
        shezar_set_notification(get_string('pageupdated', 'shezar_appraisal'), $returnurl, array('class' => 'notifysuccess'));
        break;
    case 'posdown':
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', 'error');
        }
        appraisal_page::reorder($id, $page->sortorder + 1);
        shezar_set_notification(get_string('pageupdated', 'shezar_appraisal'), $returnurl, array('class' => 'notifysuccess'));
        break;
    case 'move':
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', 'error');
        }
        $stageid = required_param('target', PARAM_INT);
        $page->move($stageid);
        appraisal_page::reorder($page->id, 0);
        if (is_ajax_request($_SERVER)) {
            echo 'success';
            return;
        }
        shezar_set_notification(get_string('pageupdated', 'shezar_appraisal'), $returnurl, array('class' => 'notifysuccess'));
        break;
    case 'delete':
        if ($page->id < 1) {
            shezar_set_notification(get_string('error:pagenotfound', 'shezar_appraisal'), $returnurl,
                    array('class' => 'notifyproblem'));
        }
        $appraisal = new appraisal($stage->appraisalid);
        if ($appraisal->status == appraisal::STATUS_DRAFT) {
            $confirm = optional_param('confirm', 0, PARAM_INT);
            if ($confirm == 1) {
                if (!confirm_sesskey()) {
                    print_error('confirmsesskeybad', 'error');
                }
                appraisal_page::delete($id);
                if (is_ajax_request($_SERVER)) {
                    echo 'success';
                    return;
                }
                shezar_set_notification(get_string('deletedpage', 'shezar_appraisal'), $returnurl,
                        array('class' => 'notifysuccess'));
            }
        } else {
            shezar_set_notification(get_string('error:appraisalmustdraft', 'shezar_appraisal'), $returnurl,
                    array('class' => 'notifyproblem'));
        }
        $output = $PAGE->get_renderer('shezar_appraisal');
        echo $output->confirm_delete_page($page->id, $page->appraisalstageid);
        break;
    default:
        $defaults = $page->get();
        $defaults->appraisalstageid = $stage->id;
        $mform = new appraisal_stage_page_edit_form(null, array('stageid' => $stage->id, 'page' => $defaults));
        if ($mform->is_cancelled()) {
            redirect($returnurl);
        }
        if ($fromform = $mform->get_data()) {
            if (!confirm_sesskey()) {
                print_error('confirmsesskeybad', 'error');
            }
            $page->set($fromform)->save();

            shezar_set_notification(get_string('pageupdated', 'shezar_appraisal'), $returnurl,
                    array('class' => 'notifysuccess'));
        }

        echo $mform->display();
}
