<?php
/**
 *
 * @author  Piers Harding  piers@catalyst.net.nz
 * @version 0.0.1
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package local
 * @subpackage message
 *
 */

defined('MOODLE_INTERNAL') || die();


require_once($CFG->dirroot.'/mod/facetoface/lib.php');

/**
* Extend the base plugin class
* This class contains the action for facetoface onaccept/onreject message processing
*/
class shezar_message_workflow_facetoface extends shezar_message_workflow_plugin_base {

    /**
     * Action called on accept for face to face action
     *
     * @param array $eventdata
     * @param object $msg
     */
    function onaccept($eventdata, $msg) {
        global $DB;

        // Load course
        $userid = $eventdata['userid'];
        $session = $eventdata['session'];
        $facetoface = $eventdata['facetoface'];
        if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
            print_error('error:coursemisconfigured', 'facetoface');
            return false;
        }
        if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
            print_error('error:incorrectcoursemodule', 'facetoface');
            return false;
        }
        $form = new stdClass();
        $form->s = $session->id;
        $form->requests = array($userid => 2);  // 2 = approve, 1 = decline

        // Approve requests
        $errors = facetoface_approve_requests($form);

        // If there are any errors return false;
        if (!empty($errors)) {
            return false;
        }

        // issue notification that registration has been accepted
        return $this->acceptreject_notification($userid, $facetoface, $session, 'status_approved');
    }


    /**
     * Action called on reject of a face to face action
     *
     * @param array $eventdata
     * @param object $msg
     */
    function onreject($eventdata, $msg) {
        global $DB;

        // can manipulate the language by setting $SESSION->lang temporarily
        // Load course
        $userid = $eventdata['userid'];
        $session = $eventdata['session'];
        $facetoface = $eventdata['facetoface'];
        if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
            print_error('error:coursemisconfigured', 'facetoface');
            return false;
        }
        if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
            print_error('error:incorrectcoursemodule', 'facetoface');
            return false;
        }
        $form = new stdClass();
        $form->s = $session->id;
        $form->requests = array($userid => 1);  // 2 = approve, 1 = decline
        error_log(var_export($form, true));

        // Decline requests
        $errors = facetoface_approve_requests($form);

        if (!empty($errors)) {
            return false;
        }

        // issue notification that registration has been declined
        return $this->acceptreject_notification($userid, $facetoface, $session, 'status_declined');
    }

    /**
     * Send the accept or reject notification to the user
     *
     * @param int $userid
     * @param object $facetoface
     * @param object $session
     * @param string $langkey
     */
    private function acceptreject_notification($userid, $facetoface, $session, $langkey) {
        global $CFG, $DB, $USER;

        $stringmanager = get_string_manager();
        $newevent = new stdClass();
        $newevent->userfrom    = NULL;
        $user = $DB->get_record('user', array('id' => $userid));
        $newevent->userto      = $user;
        $url = new moodle_url('/mod/facetoface/view.php', array('f' => $facetoface->id));
        $a = new stdClass();
        $a->name = $facetoface->name;
        $a->status = $stringmanager->get_string($langkey, 'facetoface', null, $user->lang);
        $a->user   = fullname($USER);
        $a->linkname = html_writer::link($url, $facetoface->name);
        $newevent->fullmessage = $stringmanager->get_string("requestattendsession_message", 'facetoface', $a, $user->lang);
        $newevent->subject     = $stringmanager->get_string("requestattendsession_subject", 'facetoface', $a, $user->lang);
        $newevent->urgency     = shezar_MSG_URGENCY_NORMAL;
        $newevent->icon        = 'facetoface-regular';
        $newevent->msgtype     = shezar_MSG_TYPE_FACE2FACE;
        return tm_alert_send($newevent);
    }
}
