<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * remUI AJAX handler
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use theme_remui\controller\kernel;
use theme_remui\controller\router;

define('AJAX_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);

require_once(__DIR__.'/../../config.php');
// require_once('/var/www/html/mdltheme.local/public/config.php');

$systemcontext = context_system::instance();

$action    = required_param('action', PARAM_ALPHAEXT);
$contextid = optional_param('contextid', $systemcontext->id, PARAM_INT);


list($context, $course, $cm) = get_context_info_array($contextid);

require_login($course, false, $cm, false, true);
// @var $PAGE moodle_page
$PAGE->set_context($context);
switch ($action) {
    case 'send_quickmessage':
        $contactid = optional_param('contactid', 0, PARAM_INT);
        $message = optional_param('message', '', PARAM_ALPHAEXT);
        $PAGE->set_url('/theme/remui/rest.php', array('action' => $action, 'contextid' => $context->id, 'contactid' => $contactid, 'message' => $message));
        break;
    case 'get_userlist':
        $courseid = optional_param('courseid', 0, PARAM_INT);
        $PAGE->set_url('/theme/remui/rest.php', array('action' => $action, 'contextid' => $context->id,
            'courseid' => $courseid));
        break;
    case 'set_user_preference_role':
        $role = optional_param('role', 'student', PARAM_ALPHAEXT);
        $PAGE->set_url('/theme/remui/rest.php', array('action' => $action, 'role' => $role));
        break;
    case 'set_contact':
        $otheruserid = required_param('otheruserid', PARAM_INT);
        $type = required_param('type', PARAM_ALPHAEXT);

        $PAGE->set_url('/theme/remui/rest.php', array('action' => $action, 'type' => $type, 'otheruserid' => $otheruserid));
        break;
    case 'save_user_profile_settings':
        $fname = required_param('fname', PARAM_ALPHAEXT);
        $lname = required_param('lname', PARAM_ALPHAEXT);
        $emailid = required_param('emailid', PARAM_EMAIL);
        $description = required_param('description', PARAM_TEXT);
        $city = required_param('city', PARAM_TEXT);
        $country = required_param('country', PARAM_ALPHAEXT);
        $PAGE->set_url('/theme/remui/rest.php', array('action' => $action,'fname' => $fname, 'lname' => $lname,
                'emailid' => $emailid, 'description' => $description, 'city' => $city, 'country' => $country));
        break;
    case 'get_add_activity_course_list':
        $courseid = required_param('courseid', PARAM_INT);
        $PAGE->set_url('/theme/remui/rest.php', array('action' => $action, 'courseid' => $courseid));
        break;
    case 'get_course_by_category':
        $categoryid = required_param('categoryid', PARAM_INT);
        $PAGE->set_url('/theme/remui/rest.php', array('action' => $action, 'categoryid' => $categoryid));
        break;
    case 'get_courses_for_quiz':
        $courseid = required_param('courseid', PARAM_INT);
        $PAGE->set_url('/theme/remui/rest.php', array('action' => $action, 'courseid' => $courseid));
        break;
    default:
        $PAGE->set_url('/theme/remui/rest.php', array('action' => $action, 'contextid' => $context->id));
        break;
}


$router = new router();

// Add controllers automatically.
$controllerdir = __DIR__.'/classes/controller';
$contfiles = scandir($controllerdir);
foreach ($contfiles as $contfile) {
    if ($contfile === 'addsection_controller.php') {
        continue;
    }
    $pattern = '/_controller.php$/i';
    if (preg_match($pattern, $contfile) !== 1) {
        continue;
    } else {
        $classname = '\\theme_remui\\controller\\'.str_ireplace('.php', '', $contfile);
        if (class_exists($classname)) {
            $rc = new ReflectionClass($classname);
            if ($rc->isSubclassOf('\\theme_remui\\controller\\controller_abstract')) {
                $router->add_controller(new $classname());
            }
        }
    }
}

$kernel = new kernel($router);
$kernel->handle($action);
