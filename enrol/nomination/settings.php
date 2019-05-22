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
 * @package    enrol_apply
 * @copyright  emeneo.com (http://emeneo.com/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     emeneo.com (http://emeneo.com/)
 * @author     Johannes Burk <johannes.burk@sudile.com>
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading('enrol_nomination_enrolname', '', get_string('pluginname_desc', 'enrol_nomination')));

    // Confirm mail settings...
    $settings->add(new admin_setting_heading(
        'enrol_nomination_confirmmail',
        get_string('confirmmail_heading', 'enrol_nomination'),
        get_string('confirmmail_desc', 'enrol_nomination')));
    $settings->add(new admin_setting_configtext(
        'enrol_nomination/confirmmailsubject',
        get_string('confirmmailsubject', 'enrol_nomination'),
        get_string('confirmmailsubject_desc', 'enrol_nomination'),
        null,
        PARAM_TEXT,
        60));
    $settings->add(new admin_setting_confightmleditor(
        'enrol_nomination/confirmmailcontent',
        get_string('confirmmailcontent', 'enrol_nomination'),
        get_string('confirmmailcontent_desc', 'enrol_nomination'),
        null,
        PARAM_RAW));

    // Wait mail settings.
    $settings->add(new admin_setting_heading(
        'enrol_nomination_waitmail',
        get_string('waitmail_heading', 'enrol_nomination'),
        get_string('waitmail_desc', 'enrol_nomination')));
    $settings->add(new admin_setting_configtext(
        'enrol_nomination/waitmailsubject',
        get_string('waitmailsubject', 'enrol_nomination'),
        get_string('waitmailsubject_desc', 'enrol_nomination'),
        null,
        PARAM_TEXT,
        60));
    $settings->add(new admin_setting_confightmleditor(
        'enrol_nomination/waitmailcontent',
        get_string('waitmailcontent', 'enrol_nomination'),
        get_string('waitmailcontent_desc', 'enrol_nomination'),
        null,
        PARAM_RAW));

    // Cancel mail settings...
    $settings->add(new admin_setting_heading(
        'enrol_nomination_cancelmail',
        get_string('cancelmail_heading', 'enrol_nomination'),
        get_string('cancelmail_desc', 'enrol_nomination')));
    $settings->add(new admin_setting_configtext(
        'enrol_nomination/cancelmailsubject',
        get_string('cancelmailsubject', 'enrol_nomination'),
        get_string('cancelmailsubject_desc', 'enrol_nomination'),
        null,
        PARAM_TEXT,
        60));
    $settings->add(new admin_setting_confightmleditor(
        'enrol_nomination/cancelmailcontent',
        get_string('cancelmailcontent', 'enrol_nomination'),
        get_string('cancelmailcontent_desc', 'enrol_nomination'),
        null,
        PARAM_RAW));

    // Notification settings...
    $settings->add(new admin_setting_heading(
        'enrol_nomination_notify',
        get_string('notify_heading', 'enrol_nomination'),
        get_string('notify_desc', 'enrol_nomination')));
    $settings->add(new admin_setting_users_with_capability(
        'enrol_nomination/notifyglobal',
        get_string('notifyglobal', 'enrol_nomination'),
        get_string('notifyglobal_desc', 'enrol_nomination'),
        array(),
        'enrol/nomination:manageapplications'));

    // Enrol instance defaults...
    $settings->add(new admin_setting_heading('enrol_manual_defaults',
        get_string('enrolinstancedefaults', 'admin'), get_string('enrolinstancedefaults_desc', 'admin')));

    $settings->add(new admin_setting_configcheckbox('enrol_nomination/defaultenrol',
        get_string('defaultenrol', 'enrol'), get_string('defaultenrol_desc', 'enrol'), 0));

    $options = array(ENROL_INSTANCE_ENABLED => get_string('yes'),
                     ENROL_INSTANCE_DISABLED  => get_string('no'));
    $settings->add(new admin_setting_configselect('enrol_nomination/status',
        get_string('status', 'enrol_nomination'), get_string('status_desc', 'enrol_nomination'), ENROL_INSTANCE_ENABLED, $options));

    $options = array(1 => get_string('yes'),
                     0  => get_string('no'));
    $settings->add(new admin_setting_configselect('enrol_nomination/show_standard_user_profile',
        get_string('show_standard_user_profile', 'enrol_nomination'), '', 1, $options));

    $options = array(1 => get_string('yes'),
                     0  => get_string('no'));
    $settings->add(new admin_setting_configselect('enrol_nomination/show_extra_user_profile',
        get_string('show_extra_user_profile', 'enrol_nomination'), '', 1, $options));

    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect('enrol_nomination/roleid',
            get_string('defaultrole', 'role'), '', $student->id, $options));
    }

    $settings->add(new admin_setting_configcheckbox(
        'enrol_nomination/notifycoursebased',
        get_string('notifycoursebased', 'enrol_nomination'),
        get_string('notifycoursebased_desc', 'enrol_nomination'),
        0));
}

if ($hassiteconfig) { // Needs this condition or there is error on login page.
    $ADMIN->add('courses', new admin_externalpage('enrol_nomination',
            get_string('nominationmanage', 'enrol_nomination'),
            new moodle_url('/enrol/nomination/manage.php')));
}
