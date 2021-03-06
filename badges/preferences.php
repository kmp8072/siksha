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
 * Badges user preferences page.
 *
 * @package    core
 * @subpackage badges
 * @copyright  2013 onwards shezar Learning Solutions Ltd {@link http://www.shezarlms.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Yuliya Bozhko <yuliya.bozhko@shezarlms.com>
 */

require_once(dirname(dirname(__FILE__)) . '/config.php');
require_once('preferences_form.php');

$url = new moodle_url('/badges/preferences.php');

require_login();
$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

if (empty($CFG->enablebadges)) {
    print_error('badgesdisabled', 'badges');
}

$mform = new badges_preferences_form();
$mform->set_data(array('badgeprivacysetting' => get_user_preferences('badgeprivacysetting')));

if (!$mform->is_cancelled() && $data = $mform->get_data()) {
    $setting = $data->badgeprivacysetting;
    set_user_preference('badgeprivacysetting', $setting);
}

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/user/preferences.php');
}

$strpreferences = get_string('preferences');
$strbadges      = get_string('badges');

$title = "$strbadges: $strpreferences";
$PAGE->set_title($title);
$PAGE->set_heading(fullname($USER));

echo $OUTPUT->header();
echo $OUTPUT->heading("$strbadges: $strpreferences", 2);

$mform->display();

echo $OUTPUT->footer();
