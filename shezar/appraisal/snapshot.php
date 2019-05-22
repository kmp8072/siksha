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
require_once($CFG->dirroot . '/shezar/appraisal/lib.php');
require_once($CFG->dirroot . '/shezar/appraisal/appraisal_forms.php');
require_once($CFG->dirroot . '/shezar/core/js/lib/setup.php');

// Check if Appraisals are enabled.
appraisal::check_feature_enabled();

require_login();

// Set system context.
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

// Load parameters and objects required for checking permissions.
$subjectid = optional_param('subjectid', $USER->id, PARAM_INT);
$role = optional_param('role', appraisal::ROLE_LEARNER, PARAM_INT);
if ($role == 0) {
    $role = appraisal::ROLE_LEARNER;
}
$roles = appraisal::get_roles();


$appraisalid = required_param('appraisalid', PARAM_INT);
$spaces = optional_param('spaces', 0, PARAM_INT);
$stageschecked = optional_param_array('stages', null, PARAM_BOOL);
$action = optional_param('action', '', PARAM_ALPHANUMEXT);

$subject = $DB->get_record('user', array('id' => $subjectid));
if ($action == 'stages') {
    if ($subjectid == $USER->id) {
        require_capability('shezar/appraisal:printownappraisals', $systemcontext);
    } else {
        $usercontext = context_user::instance($subjectid);
        require_capability('shezar/appraisal:printstaffappraisals', $usercontext);
    }
}

$appraisal = new appraisal($appraisalid);

if ($action == 'stages') {
    // Show dialog box with stages select.
    $stageslist = appraisal_stage::get_stages($appraisal->id, array($role));
    $stagesform = new appraisal_print_stages_form(null, array('appraisalid' => $appraisalid, 'stages' => $stageslist,
        'subjectid' => $subjectid, 'role' => $role), 'post', '', array('id' => 'printform', 'class' => 'print-stages-form'));
    $stagesform->display();
    exit();
}

// Check that the subject/role are valid in the given appraisal.
$roleassignment = appraisal_role_assignment::get_role($appraisal->id, $subjectid, $USER->id, $role);
$userassignment = $roleassignment->get_user_assignment();
if (!$appraisal->can_access($roleassignment)) {
    throw new appraisal_exception(get_string('error:cannotaccessappraisal', 'shezar_appraisal'));
}
$assignments = $appraisal->get_all_assignments($subjectid);
$otherassignments = $assignments;

unset($otherassignments[$roleassignment->appraisalrole]);

$PAGE->set_url(new moodle_url('/shezar/appraisal/snapshot.php', array('role' => $role,
    'subjectid' => $subjectid, 'appraisalid' => $appraisalid, 'action' => $action)));

$PAGE->set_pagelayout('popup');

/** @var \shezar_appraisal_renderer|core_renderer $renderer */
$renderer = $PAGE->get_renderer('shezar_appraisal');
$heading = get_string('myappraisals', 'shezar_appraisal');
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

if ($action == 'snapshot') {
    require_sesskey();
    // The renderer must not be used after footer.
    $strsource = new stdClass();
    $strsource->link = $renderer->action_link(new moodle_url('/shezar/appraisal/index.php'),
        get_string('allappraisals', 'shezar_appraisal'));

    $file = make_request_directory() . '/appraisal_'.$appraisal->id.'_'.date("Y-m-d_His").'_'.$roles[$role].'.pdf';

    core_php_time_limit::raise(0);
    \core\session\manager::write_close();

    if (!empty($CFG->pathtowkhtmltopdf) and file_exists($CFG->pathtowkhtmltopdf) and is_executable($CFG->pathtowkhtmltopdf)) {
        $wkhtmltopdf = escapeshellarg($CFG->pathtowkhtmltopdf); // Do not use escapeshellcmd() here.
        $sessioname = escapeshellarg(session_name());
        $sessioncookie = escapeshellarg(session_id());
        $pageurl = new moodle_url($PAGE->url, array('action' => 'generatepdf'));
        $pageurl = escapeshellarg($pageurl->out(false));
        $escapedfile = escapeshellarg($file);

        // Note: let's hope executables may access it's own web server directly.
        $command = "$wkhtmltopdf --cookie $sessioname $sessioncookie $pageurl $escapedfile";

        exec($command);

    } else {
        // This may throw various warnings, keep it in error logs only.
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');

        require_once($CFG->libdir . '/dompdf/lib.php');

        $out = "";
        $out .= $renderer->snapshot_header();
        $out .= $renderer->display_snapshot($appraisal, $subject, $userassignment, $roleassignment, $spaces, true, $stageschecked);
        $out .= $renderer->snapshot_footer();

        $content = null;
        try {
            $pdf = new shezar_dompdf();
            $pdf->load_html($out);
            $pdf->render();
            $content = $pdf->output();
        } catch (Exception $e) {
            // Ignore.
        } catch (Throwable $e) {
            // Ignore.
        }
        if ($content === null) {
            try {
                $out = shezar_dompdf::hack_html($out);
                $pdf = new shezar_dompdf();
                $pdf->load_html($out);
                $pdf->render();
                $content = $pdf->output();
            } catch (Exception $e) {
                // Ignore.
            } catch (Throwable $e) {
                // Ignore.
            }
        }

        if ($content) {
            file_put_contents($file, $content);
        }
    }

    if (!file_exists($file)) {
        echo html_writer::tag('div', get_string('snapshoterror', 'shezar_appraisal'),
            array('class'=>'notifyproblem dialog-nobind'));
        die;
    }

    // Save into db.
    $downloadurl = $appraisal->save_snapshot($file, $roleassignment->id);

    // Message for dialog.
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'downloadurl', 'id' => 'downloadurl',
            'value' => $downloadurl));
    echo html_writer::tag('div', get_string('snapshotdone', 'shezar_appraisal', $strsource),
            array('class'=>'notifysuccess dialog-nobind'));
    die;
}

// Print the html snapshot as the last option.
if ($action !== 'generatepdf') {
    $PAGE->requires->js_init_code('window.print()', true);
}

echo $renderer->header();
echo $renderer->display_snapshot($appraisal, $subject, $userassignment, $roleassignment, $spaces, false, $stageschecked);
echo $renderer->footer();
