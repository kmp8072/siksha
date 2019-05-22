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
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @package shezar
 * @subpackage plan
 */

/**
 * This script will perform general plan actions that can be posted from a number of pages
 * This script can also later be used by AJAX requests
 */

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->dirroot.'/shezar/message/messagelib.php');

// Check if Learning plans are enabled.
check_learningplan_enabled();

require_login();

///
/// Params
///

// Plan param
$id = required_param('id', PARAM_INT);      // the plan id

// Action params
$approve = optional_param('approve', 0, PARAM_BOOL);
$decline = optional_param('decline', 0, PARAM_BOOL);
$activate = optional_param('activate', 0, PARAM_BOOL);
$approvalrequest = optional_param('approvalrequest', 0, PARAM_BOOL);
$delete = optional_param('delete', 0, PARAM_BOOL);
$complete = optional_param('complete', 0, PARAM_BOOL);
$reactivate = optional_param('reactivate', 0, PARAM_BOOL);
$reasonfordecision = optional_param('reasonfordecision', '', PARAM_TEXT);

// Is this an ajax call?
$ajax = optional_param('ajax', 0, PARAM_BOOL);
$referer = optional_param('referer', get_local_referer(false), PARAM_URL);
//making sure that we redirect to somewhere inside platform
//in case passed param is invalid or even HTTP_REFERER is bogus
$referer = clean_param($referer, PARAM_LOCALURL);

if (!confirm_sesskey()) {
    if (empty($ajax)) {
        redirect($referer);
    } else {
        return;
    }
}

$context = context_system::instance();
$PAGE->set_context($context);
$pageparams = array('id' => $id, 'sesskey' => sesskey());
if (!empty($approve)) {$pageparams['approve'] = $approve;}
if (!empty($decline)) {$pageparams['decline'] = $decline;}
if (!empty($activate)) {$pageparams['activate'] = $activate;}
if (!empty($approvalrequest)) {$pageparams['approvalrequest'] = $approvalrequest;}
if (!empty($delete)) {$pageparams['delete'] = $delete;}
if (!empty($complete)) {$pageparams['complete'] = $complete;}
if (!empty($reactivate)) {$pageparams['reactivate'] = $reactivate;}
if (!empty($ajax)) {$pageparams['ajax'] = $ajax;}
if (!empty($referer)) {$pageparams['referer'] = $referer;}
$PAGE->set_url(new moodle_url('/shezar/plan/action.php', $pageparams));

///
/// Load plan
///
$plan = new development_plan($id);
$PAGE->set_heading(format_string($SITE->fullname));

///
/// Permissions check
///
if (!dp_can_view_users_plans($plan->userid)) {
    print_error('error:nopermissions', 'shezar_plan');
}

if (!dp_can_manage_users_plans($plan->userid)) {
    print_error('error:nopermissions', 'shezar_plan');
}
// @todo: handle action failure alerts
///
/// Approve
///
if (!empty($approve)) {
    if (in_array($plan->get_setting('approve'), array(DP_PERMISSION_ALLOW, DP_PERMISSION_APPROVE))) {
        $plan->set_status(DP_PLAN_STATUS_APPROVED, DP_PLAN_REASON_MANUAL_APPROVE, $reasonfordecision);
        \shezar_plan\event\approval_approved::create_from_plan($plan)->trigger();
        $plan->send_approved_alert($reasonfordecision);
        $a = new stdClass;
        $a->name = $plan->name;
        $a->user = fullname($USER);
        shezar_set_notification(get_string('planapprovedby', 'shezar_plan', $a), $referer, array('class' => 'notifysuccess'));
    } else {
        if (empty($ajax)) {
            shezar_set_notification(get_string('nopermission', 'shezar_plan'), $referer, array('class' => 'notifysuccess'));
        }
    }
}


///
/// Decline
///
if (!empty($decline)) {
    if (in_array($plan->get_setting('approve'), array(DP_PERMISSION_ALLOW, DP_PERMISSION_APPROVE))) {
        $plan->set_status(DP_PLAN_STATUS_UNAPPROVED, DP_PLAN_REASON_MANUAL_DECLINE, $reasonfordecision);
        \shezar_plan\event\approval_declined::create_from_plan($plan)->trigger();
        $plan->send_declined_alert($reasonfordecision);
        $a = new stdClass;
        $a->name = $plan->name;
        $a->user = fullname($USER);
        shezar_set_notification(get_string('plandeclinedby', 'shezar_plan', $a), $referer, array('class' => 'notifysuccess'));
    } else {
        if (empty($ajax)) {
            shezar_set_notification(get_string('nopermission', 'shezar_plan'), $referer);
        }
    }
}


// Learner activates their own learning plan.
if (!empty($activate)) {
    if (in_array($plan->get_setting('approve'), array(DP_PERMISSION_ALLOW, DP_PERMISSION_APPROVE))) {
        $plan->set_status(DP_PLAN_STATUS_APPROVED, DP_PLAN_REASON_MANUAL_APPROVE, null);
        \shezar_plan\event\approval_approved::create_from_plan($plan)->trigger();
        $plan->send_activated_alert();
        shezar_set_notification(get_string('planactivated', 'shezar_plan', $plan->name), $referer, array('class' => 'notifysuccess'));
    } else if (empty($ajax)) {
        shezar_set_notification(get_string('nopermission', 'shezar_plan'), $referer);
    }
}


///
/// Approval request
///
if (!empty($approvalrequest)) {
    global $DB;
    $managers = $plan->get_all_managers();

    // If plan is a draft, must be asking for plan approval
    if ($plan->status == DP_PLAN_STATUS_UNAPPROVED) {
        if ($plan->get_setting('approve') == DP_PERMISSION_REQUEST) {
            // If a learner is updating their plan and now needs approval, notify manager
            if ($USER->id == $plan->userid) {
                if (!empty($managers)) {
                    foreach($managers as $manager) {
                        //check for existing approval requests for that plan
                        $sql = 'SELECT id
                            FROM {message}
                            WHERE useridfrom = ?
                            AND useridto = ?
                            AND ' . $DB->sql_compare_text('contexturlname', 255) . ' = ?
                            AND ' . $DB->sql_like('contexturl', '?');

                        $duplicates = $DB->get_records_sql($sql, array($plan->userid, $manager->id, $plan->name, "%view.php?id={$plan->id}"));
                        if (empty($duplicates)) {
                            //only send email/task if there is not already one for that learning plan
                            $plan->send_manager_plan_approval_request();
                        } else {
                            $plan->set_status(DP_PLAN_STATUS_PENDING, DP_PLAN_REASON_APPROVAL_REQUESTED);
                        }
                    }
                }
                else {
                    shezar_set_notification(get_string('nomanager', 'shezar_plan'), $referer);
                }
            }
            shezar_set_notification(get_string('approvalrequestsent', 'shezar_plan', $plan->name), $referer, array('class' => 'notifysuccess'));
            // @todo: send approval request email to relevant user(s)
        } else {
            if (empty($ajax)) {
                shezar_set_notification(get_string('nopermission', 'shezar_plan'), $referer);
            }
        }
    }
    // If plan is active, must be asking for item approval
    else if ($plan->status == DP_PLAN_STATUS_APPROVED) {

        // Check this is the owner of the plan
        if ($plan->role !== 'learner') {
            if (empty($ajax)) {
                shezar_set_notification(get_string('nopermission', 'shezar_plan'), $referer);
            }
        }

        // Get unapproved items
        $unapproved = $plan->get_unapproved_items();
        if ($unapproved) {
            if (!empty($managers)) {
                foreach($managers as $manager) {
                    $plan->send_manager_item_approval_request($unapproved);
                }
            } else {
                shezar_set_notification(get_string('nomanager', 'shezar_plan'), $referer);
            }

            if (empty($ajax)) {
                shezar_set_notification(get_string('approvalrequestsent', 'shezar_plan', $plan->name), $referer, array('class' => 'notifysuccess'));
            }

        }
    }
}

///
/// Delete
///
if (!empty($delete)) {
    if ($plan->get_setting('delete') == DP_PERMISSION_ALLOW) {
        $confirm = optional_param('confirm', 0, PARAM_BOOL);

        if (!$confirm && empty($ajax)) {
            // Show confirmation message
            $PAGE->set_title(get_string('confirmdeleteplantitle', 'shezar_plan', $plan->name));
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('deleteplan', 'shezar_plan'));
            $confirmurl = new moodle_url(qualified_me());
            $confirmurl->param('confirm', 'true');
            $confirmurl->param('referer', $referer);
            $strdelete = get_string('checkplandelete', 'shezar_plan', $plan->name);
            $strbreak = html_writer::empty_tag('br') . html_writer::empty_tag('br');
            echo $OUTPUT->confirm("{$strdelete}{$strbreak}".format_string($plan->name), $confirmurl->out(), $referer);

            echo $OUTPUT->footer();
            exit;
        } else {
            // Delete the plan
            $is_active = $plan->is_active();
            $plan->delete();

            if ($plan->userid == $USER->id) {
                // don't bother unless the plan was active
                if ($is_active) {
                    // User was deleting their own plan, notify their managers.
                    $learner = $plan->get_learner();
                    $a = new stdClass();
                    $a->name = format_string($plan->name);
                    $a->learner = fullname($learner);
                    $plan->send_alert_to_managers($learner, 'learningplan-remove','plan-remove-manager-short','plan-remove-manager-long', $a);
                }
            } else {
                // Someone else was deleting the learner's plan, notify the learner
                $manager = clone($USER);
                $a = new stdClass();
                $a->plan = format_string($plan->name);
                $a->manager = fullname($manager);
                $plan->send_alert_to_learner($manager, 'learningplan-remove', 'plan-remove-learner-short', 'plan-remove-learner-long', $a);
            }
            shezar_set_notification(get_string('plandeletesuccess', 'shezar_plan', $plan->name), $referer, array('class' => 'notifysuccess'));
        }
    } else {
        if (empty($ajax)) {
            shezar_set_notification(get_string('nopermission', 'shezar_plan'), $referer);
        }
    }
}


///
/// Complete
///
if (!empty($complete)) {
    if ($plan->get_setting('completereactivate') >= DP_PERMISSION_ALLOW) {
        $confirm = optional_param('confirm', 0, PARAM_BOOL);

        if (!$confirm && empty($ajax)) {
            // Show confirmation message
            echo $OUTPUT->header();
            $confirmurl = new moodle_url(qualified_me());
            $confirmurl->param('confirm', 'true');
            $confirmurl->param('referer', $referer);
            $strcomplete = get_string('checkplancomplete11', 'shezar_plan', $plan->name);
            $strbreak = html_writer::empty_tag('br') . html_writer::empty_tag('br');
            echo $OUTPUT->confirm("{$strcomplete}{$strbreak}", $confirmurl->out(), $referer);

            echo $OUTPUT->footer();
            exit;
        } else {
            // Set plan status to complete
            $plan->set_status(DP_PLAN_STATUS_COMPLETE, DP_PLAN_REASON_MANUAL_COMPLETE);
            \shezar_plan\event\plan_completed::create_from_plan($plan)->trigger();
            $plan->send_completion_alert();
            shezar_set_notification(get_string('plancompletesuccess', 'shezar_plan', $plan->name), $referer, array('class' => 'notifysuccess'));
        }
    } else {
        if (empty($ajax)) {
            shezar_set_notification(get_string('nopermission', 'shezar_plan'), $referer);
        }
    }
}

///
/// Reactivate
///
if (!empty($reactivate)) {
    require_once($CFG->dirroot . '/shezar/plan/reactivate_form.php');
    require_once($CFG->dirroot . '/shezar/core/js/lib/setup.php');

    if ($plan->get_setting('completereactivate') >= DP_PERMISSION_ALLOW) {
        $form = new plan_reactivate_form(null, compact('id','referer'));

        if ($form->is_cancelled()) {
            redirect($referer);
        }

        if ($data = $form->get_data()) {

            if (isset($data->enddate)) {
                $new_date = $data->enddate;
            } else {
                $new_date = null;
            }

            $referer = $data->referer;

            // Reactivate plan
            if (!$plan->reactivate_plan($new_date)) {
                shezar_set_notification(get_string('planreactivatefail', 'shezar_plan', $plan->name), $referer);
            } else {
                 \shezar_plan\event\plan_reactivated::create_from_plan($plan)->trigger();
                shezar_set_notification(get_string('planreactivatesuccess', 'shezar_plan', $plan->name), $referer, array('class' => 'notifysuccess'));
            }
        }

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('planreactivate', 'shezar_plan'), 2, 'reactivateheading');

        $form->display();

        echo build_datepicker_js('#id_enddate');

        echo $OUTPUT->footer();
        exit;

    } else {
        if (empty($ajax)) {
            shezar_set_notification(get_string('nopermission', 'shezar_plan'), $referer);
        }
    }
}

if (empty($ajax)) {
    shezar_set_notification(get_string('error:incorrectparameters', 'shezar_plan'), $referer);
}
