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

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot.'/shezar/appraisal/lib.php');
require_once($CFG->dirroot.'/shezar/appraisal/appraisal_forms.php');
require_once($CFG->dirroot.'/shezar/question/lib.php');
require_once($CFG->libdir.'/adminlib.php');

ajax_require_login();

$sytemcontext = context_system::instance();
require_capability('shezar/appraisal:managepageelements', $sytemcontext);
$PAGE->set_context($sytemcontext);
$PAGE->set_url('/shezar/appraisal/ajax/question.php');

$action = optional_param('action', '', PARAM_ACTION);
$pageid = optional_param('appraisalstagepageid', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);

// Set up the page and question.
$question = null;
$page = null;
if ($id > 0) {
    // The question already exists, so load it.
    $question = new appraisal_question($id);
    $pageid = $question->appraisalstagepageid;
    $page = new appraisal_page($pageid);
}
if ($pageid > 0 && !$question) {
    // This is a new question, so create it.
    $page = new appraisal_page($pageid);
    $question = new appraisal_question();
}
if (!$page && !$question) {
    // Normally this should never happen.
    throw new appraisal_exception('Page not found', 23);
}

$stage = new appraisal_stage($page->appraisalstageid);

$returnurl = new moodle_url('/shezar/appraisal/ajax/question.php', array('appraisalstagepageid' => $pageid));
if (is_ajax_request($_SERVER)) {
    $returnurl = null;
}

// Check that we are allowed to perform the specified action (view = '', edit, delete).
$isdraft = appraisal::is_draft($stage->appraisalid);
if (!$isdraft) {
    if (!in_array($action, array('', 'edit'))) {
        shezar_set_notification(get_string('error:appraisalnotdraft', 'shezar_appraisal'), $returnurl);
        return;
    }
} else if (!in_array($action, array('', 'edit', 'delete')) && !confirm_sesskey()) {
    print_error('confirmsesskeybad', 'error');
}

// If this is a new question then calculate the previous question's permissions.
$previousquestion = null;
$previous_quest_perms = '';
if ($id == 0) {
    $previousquestion = appraisal::get_last_question($stage, $page);

    if (isset($previousquestion)) {
        $permissions = array();
        foreach ($previousquestion->roles as $role => $permission) {
            if (0 != ($permission & 1)) {
                $permissions[] = '"id_roles_' . $role . '_1"';
            }

            if (0 != ($permission & 2)) {
                $permissions[] = '"id_roles_' . $role . '_2"';
            }
            if (0 != ($permission & 4)) {
                $permissions[] = '"id_roles_' . $role . '_6"';
            }
        }
        $previous_quest_perms = implode(',', $permissions);
    }
}

// Set up the new question form?
$mnewform = new appraisal_add_quest_form(null, array('pageid' => $pageid, 'prev_perms' => $previous_quest_perms));

// Perform the action.
switch ($action) {
    case 'duplicate':
        $question->duplicate($pageid);
        $questions = appraisal_question::get_list_with_redisplay($page->id);
        if (is_ajax_request($_SERVER)) {
            echo 'success';
            return;
        }
        break;
    case 'pos':
        $pos = required_param('pos', PARAM_INT);
        appraisal_question::reorder($id, $pos);
        if (is_ajax_request($_SERVER)) {
            return;
        }
        shezar_set_notification(get_string('pageupdated', 'shezar_appraisal'), $returnurl, array('class' => 'notifysuccess'));
        break;
    case 'posup':
        appraisal_question::reorder($id, $question->sortorder - 1);
        shezar_set_notification(get_string('pageupdated', 'shezar_appraisal'), $returnurl, array('class' => 'notifysuccess'));
        break;
    case 'posdown':
        appraisal_question::reorder($id, $question->sortorder + 1);
        shezar_set_notification(get_string('pageupdated', 'shezar_appraisal'), $returnurl, array('class' => 'notifysuccess'));
        break;
    case 'move':
        switch (required_param('type', PARAM_ALPHA)) {
            case 'page':
                $pageid = required_param('target', PARAM_INT);
                $question->move($pageid);
                appraisal_question::reorder($question->id, 0);
                if (is_ajax_request($_SERVER)) {
                    echo 'success';
                    return;
                }
                shezar_set_notification(get_string('pageupdated', 'shezar_appraisal'), $returnurl,
                        array('class' => 'notifysuccess'));
                break;
            case 'stage':
                $stageid = required_param('target', PARAM_INT);
                $pages = appraisal_page::get_list($stageid);

                $page = reset($pages);
                if (empty($page)) {
                    // Create a new page.
                    $page = new appraisal_page();
                    $page->name = get_string('temporarypage', 'shezar_appraisal');
                    $page->appraisalstageid = $stageid;
                    $page->save();
                }

                $question->move($page->id);
                appraisal_question::reorder($question->id, 0);
                if (is_ajax_request($_SERVER)) {
                    echo 'success';
                    return;
                }
                shezar_set_notification(get_string('pageupdated', 'shezar_appraisal'), $returnurl,
                        array('class' => 'notifysuccess'));
                break;
            default:
                return;
                break;
        }
        break;
    case 'delete':
        if ($question->id < 1) {
            shezar_set_notification(get_string('error:elementnotfound', 'shezar_question'), $returnurl,
                    array('class' => 'notifyproblem'));
            return;
        }

        $confirm = optional_param('confirm', 0, PARAM_INT);
        if ($confirm == 1) {
            if (!confirm_sesskey()) {
                print_error('confirmsesskeybad', 'error');
            }
            appraisal_question::delete($id);
            if (is_ajax_request($_SERVER)) {
                echo 'success';
                return;
            }
            shezar_set_notification(get_string('deletedquestion', 'shezar_question'), $returnurl,
                    array('class' => 'notifysuccess'));
        }
        break;
    case 'edit':

        if ($id == 0) {
            // Set element.
            if ($fromaddelemform = $mnewform->get_data()) {
                $question->attach_element($fromaddelemform);
            } else {
                $datatype = required_param('datatype', PARAM_ACTION);
                $question->attach_element($datatype);
            }
        }

        $header = question_base_form::get_header($question->get_element(), !appraisal::is_draft($stage->appraisalid));

        $preset = array('page' => $page, 'stage' => $stage, 'question' => $question, 'id' => $id,
            'notfirst' => isset($previousquestion), 'readonly' => !$isdraft);

        // Element edit form.
        $meditform = new appraisal_quest_edit_form(null, $preset);
        $meditform->set_header($header);
        if ($meditform->is_cancelled()) {
            redirect($returnurl);
        }
        if ($isdraft && $fromform = $meditform->get_data()) {
            if (!confirm_sesskey()) {
                print_error('confirmsesskeybad', 'error');
            }
            if (isset($fromform->cloneprevroles) && $fromform->cloneprevroles && is_object($previousquestion)) {
                $fromform->roles = $previousquestion->roles;
            }
            $question->set($fromform)->save();
            if (is_ajax_request($_SERVER)) {
                ajax_result();
                return;
            } else {
                shezar_set_notification(get_string('pageupdated', 'shezar_appraisal'), $returnurl,
                        array('class' => 'notifysuccess'));
            }
        } else {
            $meditform->set_data($question->get(true));
        }
        break;
    default:
        $questions = appraisal_question::get_list_with_redisplay($page->id);
}

$output = $PAGE->get_renderer('shezar_appraisal');
if (!is_ajax_request($_SERVER)) {
    admin_externalpage_setup('manageappraisals');
    echo $output->header();
}

switch($action) {
    case 'delete':
        echo $output->confirm_delete_question($question->id, $page->id);
        break;
    case 'edit':
        $meditform->display();
        break;
    default:
        if (appraisal::is_draft($stage->appraisalid)) {
            echo $mnewform->display();
        }
        echo $output->list_questions($questions, $stage);
}

if (!is_ajax_request($_SERVER)) {
    echo $output->footer();
} else {
    // Get the end code, but do not initialise AMD.
    // We need the form JS, but not the AMD config and init.
    echo $PAGE->requires->get_end_code(false);
}
