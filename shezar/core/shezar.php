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
 * @author Jonathan Newman <jonathan.newman@catalyst.net.nz>
 * @author Aaron Barnes <aaron.barnes@shezarlms.com>
 * @package shezar
 * @subpackage shezar_core
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

define('PUBLIC_KEY_PATH', $CFG->dirroot . '/shezar_public.pem');
define('shezar_SHOWFEATURE', 1);
define('shezar_HIDEFEATURE', 2);
define('shezar_DISABLEFEATURE', 3);

define('COHORT_ALERT_NONE', 0);
define('COHORT_ALERT_AFFECTED', 1);
define('COHORT_ALERT_ALL', 2);

define('COHORT_COL_STATUS_ACTIVE', 0);
define('COHORT_COL_STATUS_DRAFT_UNCHANGED', 10);
define('COHORT_COL_STATUS_DRAFT_CHANGED', 20);
define('COHORT_COL_STATUS_OBSOLETE', 30);

define('COHORT_BROKEN_RULE_NONE', 0);
define('COHORT_BROKEN_RULE_NOT_NOTIFIED', 1);
define('COHORT_BROKEN_RULE_NOTIFIED', 2);

define('COHORT_MEMBER_SELECTOR_MAX_ROWS', 1000);

define('COHORT_OPERATOR_TYPE_COHORT', 25);
define('COHORT_OPERATOR_TYPE_RULESET', 50);

define('COHORT_ASSN_ITEMTYPE_CATEGORY', 40);
define('COHORT_ASSN_ITEMTYPE_COURSE', 50);
define('COHORT_ASSN_ITEMTYPE_PROGRAM', 45);
define('COHORT_ASSN_ITEMTYPE_CERTIF', 55);
define('COHORT_ASSN_ITEMTYPE_MENU', 65);

// This should be extended when adding other tabs.
define ('COHORT_ASSN_VALUE_VISIBLE', 10);
define ('COHORT_ASSN_VALUE_ENROLLED', 30);
define ('COHORT_ASSN_VALUE_PERMITTED', 50);

// Visibility constants.
define('COHORT_VISIBLE_ENROLLED', 0);
define('COHORT_VISIBLE_AUDIENCE', 1);
define('COHORT_VISIBLE_ALL', 2);
define('COHORT_VISIBLE_NOUSERS', 3);

/**
 * Returns true or false depending on whether or not this course is visible to a user.
 *
 * @param int $courseid
 * @param int $userid
 * @return bool
 */
function shezar_course_is_viewable($courseid, $userid = null) {
    global $USER, $CFG, $DB;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $coursecontext = context_course::instance($courseid);

    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    if (empty($CFG->audiencevisibility)) {
        // This check is moved from require_login().
        if (!$course->visible && !has_capability('moodle/course:viewhiddencourses', $coursecontext, $userid)) {
            return false;
        }
    } else {
        require_once($CFG->dirroot . '/shezar/cohort/lib.php');
        return check_access_audience_visibility('course', $course, $userid);
    }

    return true;
}

/**
 * This function loads the program settings that are available for the user
 *
 * @param object $navinode The navigation_node to add the settings to
 * @param context $context
 * @param bool $forceopen If set to true the course node will be forced open
 * @return navigation_node|false
 */
function shezar_load_program_settings($navinode, $context, $forceopen = false) {
    global $CFG;

    $program = new program($context->instanceid);
    $exceptions = $program->get_exception_count();
    $exceptioncount = $exceptions ? $exceptions : 0;

    $adminnode = $navinode->add(get_string('programadministration', 'shezar_program'), null, navigation_node::TYPE_COURSE, null, 'progadmin');
    if ($forceopen) {
        $adminnode->force_open();
    }
    // Standard tabs.
    if (has_capability('shezar/program:viewprogram', $context)) {
        $url = new moodle_url('/shezar/program/edit.php', array('id' => $program->id, 'action' => 'view'));
        $adminnode->add(get_string('overview', 'shezar_program'), $url, navigation_node::TYPE_SETTING, null,
                    'progoverview', new pix_icon('i/settings', get_string('overview', 'shezar_program')));
    }
    if (has_capability('shezar/program:configuredetails', $context)) {
        $url = new moodle_url('/shezar/program/edit.php', array('id' => $program->id, 'action' => 'edit'));
        $adminnode->add(get_string('details', 'shezar_program'), $url, navigation_node::TYPE_SETTING, null,
                    'progdetails', new pix_icon('i/settings', get_string('details', 'shezar_program')));
    }
    if (has_capability('shezar/program:configurecontent', $context)) {
        $url = new moodle_url('/shezar/program/edit_content.php', array('id' => $program->id));
        $adminnode->add(get_string('content', 'shezar_program'), $url, navigation_node::TYPE_SETTING, null,
                    'progcontent', new pix_icon('i/settings', get_string('content', 'shezar_program')));
    }
    if (has_capability('shezar/program:configureassignments', $context)) {
        $url = new moodle_url('/shezar/program/edit_assignments.php', array('id' => $program->id));
        $adminnode->add(get_string('assignments', 'shezar_program'), $url, navigation_node::TYPE_SETTING, null,
                    'progassignments', new pix_icon('i/settings', get_string('assignments', 'shezar_program')));
    }
    if (has_capability('shezar/program:configuremessages', $context)) {
        $url = new moodle_url('/shezar/program/edit_messages.php', array('id' => $program->id));
        $adminnode->add(get_string('messages', 'shezar_program'), $url, navigation_node::TYPE_SETTING, null,
                    'progmessages', new pix_icon('i/settings', get_string('messages', 'shezar_program')));
    }
    if (($exceptioncount > 0) && has_capability('shezar/program:handleexceptions', $context)) {
        $url = new moodle_url('/shezar/program/exceptions.php', array('id' => $program->id, 'page' => 0));
        $adminnode->add(get_string('exceptions', 'shezar_program', $exceptioncount), $url, navigation_node::TYPE_SETTING, null,
                    'progexceptions', new pix_icon('i/settings', get_string('exceptionsreport', 'shezar_program')));
    }
    if ($program->certifid && has_capability('shezar/certification:configurecertification', $context)) {
        $url = new moodle_url('/shezar/certification/edit_certification.php', array('id' => $program->id));
        $adminnode->add(get_string('certification', 'shezar_certification'), $url, navigation_node::TYPE_SETTING, null,
                    'certification', new pix_icon('i/settings', get_string('certification', 'shezar_certification')));
    }
    if (!empty($CFG->enableprogramcompletioneditor) && has_capability('shezar/program:editcompletion', $context)) {
        // Certification/Program completion editor. Added Feb 2016 to 2.5.36, 2.6.29, 2.7.12, 2.9.4.
        $url = new moodle_url('/shezar/program/completion.php', array('id' => $program->id));
        $adminnode->add(get_string('completion', 'shezar_program'), $url, navigation_node::TYPE_SETTING, null,
            'certificationcompletion', new pix_icon('i/settings', get_string('completion', 'shezar_program')));
    }
    // Roles and permissions.
    $usersnode = $adminnode->add(get_string('users'), null, navigation_node::TYPE_CONTAINER, null, 'users');
    // Override roles.
    if (has_capability('moodle/role:review', $context)) {
        $url = new moodle_url('/admin/roles/permissions.php', array('contextid' => $context->id));
    } else {
        $url = null;
    }
    $permissionsnode = $usersnode->add(get_string('permissions', 'role'), $url, navigation_node::TYPE_SETTING, null, 'override');
    // Add assign or override roles if allowed.
    if (is_siteadmin()) {
        if (has_capability('moodle/role:assign', $context)) {
            $url = new moodle_url('/admin/roles/assign.php', array('contextid' => $context->id));
            $permissionsnode->add(get_string('assignedroles', 'role'), $url, navigation_node::TYPE_SETTING, null,
                    'roles', new pix_icon('t/assignroles', get_string('assignedroles', 'role')));
        }
    }
    // Check role permissions.
    if (has_any_capability(array('moodle/role:assign', 'moodle/role:safeoverride', 'moodle/role:override', 'moodle/role:assign'), $context)) {
        $url = new moodle_url('/admin/roles/check.php', array('contextid' => $context->id));
        $permissionsnode->add(get_string('checkpermissions', 'role'), $url, navigation_node::TYPE_SETTING, null,
                    'permissions', new pix_icon('i/checkpermissions', get_string('checkpermissions', 'role')));
    }
    // Just in case nothing was actually added.
    $usersnode->trim_if_empty();
    $adminnode->trim_if_empty();
}

/**
 * Returns the major shezar version of this site (which may be different from Moodle in older versions)
 *
 * shezar version numbers consist of three numbers (four for emergency releases)separated by a dot,
 * for example 1.9.11 or 2.0.2. The first two numbers, like 1.9 or 2.0, represent so
 * called major version. This function extracts the major version from
 * the $shezar->version variable defined in the main version.php.
 *
 * @return string|false the major version like '2.3', false if could not be determined
 */
function shezar_major_version() {
    global $CFG;

    $release = null;
    require($CFG->dirroot.'/version.php');
    if (empty($shezar)) {
        return false;
    }

    // Starting in shezar 9 we do not return decimals here.
    if (preg_match('/^[0-9]+/', $shezar->version, $matches)) {
        return $matches[0];
    } else {
        return false;
    }
}

/**
 * Setup version information for installs and upgrades
 *
 * Moodle and shezar version numbers consist of three numbers (four for emergency releases)separated by a dot,
 * for example 1.9.11 or 2.0.2. The first two numbers, like 1.9 or 2.0, represent so
 * called major version. This function extracts the Moodle and shezar version info for use in checks and messages
 * @return stdClass containing moodle and shezar version info
 */
function shezar_version_info() {
    global $CFG;

    // Fetch version infos.
    $version = null;
    $release = null;
    $branch = null;
    $shezar = new stdClass();
    require("$CFG->dirroot/version.php");

    $a = new stdClass();
    $a->existingshezarversion = false;
    $a->newshezarversion = $shezar->version;
    $a->upgradecore = false;
    $a->newversion = "shezar {$shezar->release}";
    $a->oldversion = '';

    if (empty($CFG->version)) {
        // New install.
        return $a;
    }

    if (!empty($CFG->shezar_release)) {
        // Existing shezar install.
        $a->oldversion = "shezar {$CFG->shezar_release}";
    } else if (!empty($CFG->release)) {
        // Must be upgrade from Moodle.
        // Do not mention Moodle unless we are upgrading from it!
        $a->oldversion = "Moodle {$CFG->release}";
    }

    // Detect core downgrades.
    if ($version < $CFG->version) {
        if (!empty($CFG->shezar_release)) {
            // Somebody is trying to downgrade shezar.
            $a->shezarupgradeerror = 'error:cannotupgradefromnewershezar';
            return $a;

        } else {
            // The original Moodle install is newer than shezar.
            // Hack oldversion because the lang string cannot be changed easily.
            $a->oldversion = $CFG->version;
            $a->shezarupgradeerror = 'error:cannotupgradefromnewermoodle';
            return $a;
        }
    }

    // Find out if we should upgrade the core.
    if ($version > $CFG->version) {
        // Moodle core version upgrade.
        $a->upgradecore = true;
    } else if ($a->newversion !== $a->oldversion) {
        // Different shezar release - build or version changed.
        $a->upgradecore = true;
    }

    return $a;
}

/**
 * Import the latest timezone information - code taken from admin/tool/timezoneimport
 * @deprecated since shezar 2.7.2
 * @return bool success or failure
 */
function shezar_import_timezonelist() {
    return true;
}

/**
 * gets a clean timezone array compatible with PHP DateTime, DateTimeZone etc functions
 * @param bool $assoc return a simple numerical index array or an associative array
 * @return array a clean timezone list that can be used safely
 */
function shezar_get_clean_timezone_list($assoc=false) {
    $zones = array();
    foreach (DateTimeZone::listIdentifiers() as $zone) {
        if ($assoc) {
            $zones[$zone] = $zone;
        } else {
            $zones[] = $zone;
        }
    }
    return $zones;
}

/**
 * gets a list of bad timezones with the most likely proper named location zone
 * @return array a bad timezone list key=>bad value=>replacement
 */
function shezar_get_bad_timezone_list() {
    $zones = array();
    //unsupported but common abbreviations
    $zones['EST'] = 'America/New_York';
    $zones['EDT'] = 'America/New_York';
    $zones['EST5EDT'] = 'America/New_York';
    $zones['CST'] = 'America/Chicago';
    $zones['CDT'] = 'America/Chicago';
    $zones['CST6CDT'] = 'America/Chicago';
    $zones['MST'] = 'America/Denver';
    $zones['MDT'] = 'America/Denver';
    $zones['MST7MDT'] = 'America/Denver';
    $zones['PST'] = 'America/Los_Angeles';
    $zones['PDT'] = 'America/Los_Angeles';
    $zones['PST8PDT'] = 'America/Los_Angeles';
    $zones['HST'] = 'Pacific/Honolulu';
    $zones['WET'] = 'Europe/London';
    $zones['GMT'] = 'Europe/London';
    $zones['EET'] = 'Europe/Kiev';
    $zones['FET'] = 'Europe/Minsk';
    $zones['CET'] = 'Europe/Amsterdam';
    //now the stupid Moodle offset zones. If an offset does not really exist then set to nearest
    $zones['-13.0'] = 'Pacific/Apia';
    $zones['-12.5'] = 'Pacific/Apia';
    $zones['-12.0'] = 'Pacific/Kwajalein';
    $zones['-11.5'] = 'Pacific/Niue';
    $zones['-11.0'] = 'Pacific/Midway';
    $zones['-10.5'] = 'Pacific/Rarotonga';
    $zones['-10.0'] = 'Pacific/Honolulu';
    $zones['-9.5'] = 'Pacific/Marquesas';
    $zones['-9.0'] = 'America/Anchorage';
    $zones['-8.5'] = 'America/Anchorage';
    $zones['-8.0'] = 'America/Los_Angeles';
    $zones['-7.5'] = 'America/Los_Angeles';
    $zones['-7.0'] = 'America/Denver';
    $zones['-6.5'] = 'America/Denver';
    $zones['-6.0'] = 'America/Chicago';
    $zones['-5.5'] = 'America/Chicago';
    $zones['-5.0'] = 'America/New_York';
    $zones['-4.5'] = 'America/Caracas';
    $zones['-4.0'] = 'America/Santiago';
    $zones['-3.5'] = 'America/St_Johns';
    $zones['-3.0'] = 'America/Sao_Paulo';
    $zones['-2.5'] = 'America/Sao_Paulo';
    $zones['-2.0'] = 'Atlantic/South_Georgia';
    $zones['-1.5'] = 'Atlantic/Cape_Verde';
    $zones['-1.0'] = 'Atlantic/Cape_Verde';
    $zones['-0.5'] = 'Europe/London';
    $zones['0.0'] = 'Europe/London';
    $zones['0.5'] = 'Europe/London';
    $zones['1.0'] = 'Europe/Amsterdam';
    $zones['1.5'] = 'Europe/Amsterdam';
    $zones['2.0'] = 'Europe/Helsinki';
    $zones['2.5'] = 'Europe/Minsk';
    $zones['3.0'] = 'Asia/Riyadh';
    $zones['3.5'] = 'Asia/Tehran';
    $zones['4.0'] = 'Asia/Dubai';
    $zones['4.5'] = 'Asia/Kabul';
    $zones['5.0'] = 'Asia/Karachi';
    $zones['5.5'] = 'Asia/Kolkata';
    $zones['6.0'] = 'Asia/Dhaka';
    $zones['6.5'] = 'Asia/Rangoon';
    $zones['7.0'] = 'Asia/Bangkok';
    $zones['7.5'] = 'Asia/Singapore';
    $zones['8.0'] = 'Australia/Perth';
    $zones['8.5'] = 'Australia/Perth';
    $zones['9.0'] = 'Asia/Tokyo';
    $zones['9.5'] = 'Australia/Adelaide';
    $zones['10.0'] = 'Australia/Sydney';
    $zones['10.5'] = 'Australia/Lord_Howe';
    $zones['11.0'] = 'Pacific/Guadalcanal';
    $zones['11.5'] = 'Pacific/Norfolk';
    $zones['12.0'] = 'Pacific/Auckland';
    $zones['12.5'] = 'Pacific/Auckland';
    $zones['13.0'] = 'Pacific/Apia';
    return $zones;
}
/**
 * gets a clean timezone attempting to compensate for some Moodle 'special' timezones
 * where the returned zone is compatible with PHP DateTime, DateTimeZone etc functions
 * @param string/float $tz either a location identifier string or, in some Moodle special cases, a number
 * @return string a clean timezone that can be used safely
 */
function shezar_get_clean_timezone($tz=null) {
    global $CFG, $DB;

    $cleanzones = DateTimeZone::listIdentifiers();
    if (empty($tz)) {
        $tz = get_user_timezone();
    }

    //if already a good zone, return
    if (in_array($tz, $cleanzones, true)) {
        return $tz;
    }
    //for when all else fails
    $default = 'Europe/London';
    //try to handle UTC offsets, and numbers including '99' (server local time)
    //note: some old versions of moodle had GMT offsets stored as floats
    if (is_numeric($tz)) {
        if (intval($tz) == 99) {
            //check various config settings to try and resolve to something useful
            if (isset($CFG->forcetimezone) && $CFG->forcetimezone != 99) {
                $tz = $CFG->forcetimezone;
            } else if (isset($CFG->timezone) && $CFG->timezone != 99) {
                $tz = $CFG->timezone;
            }
        }
        if (intval($tz) == 99) {
            //no useful CFG settings, try a system call
            $tz = date_default_timezone_get();
            // From PHP 5.4 this may return UTC if no info is set in php.ini etc.
            $tz = ($tz == 'UTC') ? $default : $tz;
        }
        //do we have something useful yet?
        if (in_array($tz, $cleanzones, true)) {
            return $tz;
        }
        //check the bad timezone replacement list
        if (is_float($tz)) {
            $tz = number_format($tz, 1);
        }
        $badzones = shezar_get_bad_timezone_list();
        //does this exist in our replacement list?
        if (in_array($tz, array_keys($badzones))) {
            return $badzones[$tz];
        }
    }
    //everything has failed, set to London
    return $default;
}

/**
 * checks the md5 of the zip file, grabbed from download.moodle.org,
 * against the md5 of the local language file from last update
 * @param string $lang
 * @param string $md5check
 * @return bool
 */
function local_is_installed_lang($lang, $md5check) {
    global $CFG;
    $md5file = $CFG->dataroot.'/lang/'.$lang.'/'.$lang.'.md5';
    if (file_exists($md5file)){
        return (file_get_contents($md5file) == $md5check);
    }
    return false;
}

/**
 * Runs on every upgrade to get the latest language packs from shezar language server
 *
 * Code mostly refactored from admin/tool/langimport/index.php
 *
 * @return  void
 */
function shezar_upgrade_installed_languages() {
    global $CFG, $OUTPUT;
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->libdir.'/filelib.php');
    require_once($CFG->libdir.'/componentlib.class.php');
    core_php_time_limit::raise(0);
    $notice_ok = array();
    $notice_error = array();
    $installer = new lang_installer();

    // Do not download anything if there is only 'en' lang pack.
    $currentlangs = array_keys(get_string_manager()->get_list_of_translations(true));
    if (count($currentlangs) === 1 and in_array('en', $currentlangs)) {
        echo $OUTPUT->notification(get_string('nolangupdateneeded', 'tool_langimport'), 'notifysuccess');
        return;
    }

    if (!$availablelangs = $installer->get_remote_list_of_languages()) {
        echo $OUTPUT->notification(get_string('cannotdownloadshezarlanguageupdatelist', 'shezar_core'), 'notifyproblem');
        return;
    }
    $md5array = array();    // (string)langcode => (string)md5
    foreach ($availablelangs as $alang) {
        $md5array[$alang[0]] = $alang[1];
    }

    // filter out unofficial packs
    $updateablelangs = array();
    foreach ($currentlangs as $clang) {
        if (!array_key_exists($clang, $md5array)) {
            $notice_ok[] = get_string('langpackupdateskipped', 'tool_langimport', $clang);
            continue;
        }
        $dest1 = $CFG->dataroot.'/lang/'.$clang;
        $dest2 = $CFG->dirroot.'/lang/'.$clang;

        if (file_exists($dest1.'/langconfig.php') || file_exists($dest2.'/langconfig.php')){
            $updateablelangs[] = $clang;
        }
    }

    // then filter out packs that have the same md5 key
    $neededlangs = array();   // all the packs that needs updating
    foreach ($updateablelangs as $ulang) {
        if (!local_is_installed_lang($ulang, $md5array[$ulang])) {
            $neededlangs[] = $ulang;
        }
    }

    make_temp_directory('');
    make_upload_directory('lang');

    // install all needed language packs
    $installer->set_queue($neededlangs);
    $results = $installer->run();
    $updated = false;    // any packs updated?
    foreach ($results as $langcode => $langstatus) {
        switch ($langstatus) {
        case lang_installer::RESULT_DOWNLOADERROR:
            $a       = new stdClass();
            $a->url  = $installer->lang_pack_url($langcode);
            $a->dest = $CFG->dataroot.'/lang';
            echo $OUTPUT->notification(get_string('remotedownloaderror', 'error', $a), 'notifyproblem');
            break;
        case lang_installer::RESULT_INSTALLED:
            $updated = true;
            $notice_ok[] = get_string('langpackinstalled', 'tool_langimport', $langcode);
            break;
        case lang_installer::RESULT_UPTODATE:
            $notice_ok[] = get_string('langpackuptodate', 'tool_langimport', $langcode);
            break;
        }
    }

    if ($updated) {
        $notice_ok[] = get_string('langupdatecomplete', 'tool_langimport');
    } else {
        $notice_ok[] = get_string('nolangupdateneeded', 'tool_langimport');
    }

    unset($installer);
    get_string_manager()->reset_caches();
    //display notifications
    $delimiter = (CLI_SCRIPT) ? "\n" : html_writer::empty_tag('br');
    if (!empty($notice_ok)) {
        $info = implode($delimiter, $notice_ok);
        echo $OUTPUT->notification($info, 'notifysuccess');
    }

    if (!empty($notice_error)) {
        $info = implode($delimiter, $notice_error);
        echo $OUTPUT->notification($info, 'notifyproblem');
    }
}

/**
 * Save a notification message for displaying on the subsequent page view
 *
 * Optionally supply a url for redirecting to before displaying the message
 * and/or an options array.
 *
 * Currently the options array only supports a 'class' entry for passing as
 * the second parameter to notification()
 *
 * @param string $message Message to display
 * @param string $redirect Url to redirect to (optional)
 * @param array $options An array of options to pass to shezar_queue_append (optional)
 * @param bool $immediatesend If set to true the notification is immediately sent
 * @return void
 */
function shezar_set_notification($message, $redirect = null, $options = array(), $immediatesend = true) {

    // Check options is an array
    if (!is_array($options)) {
        print_error('error:notificationsparamtypewrong', 'shezar_core');
    }

    // Add message to options array
    $options['message'] = $message;

    // Add to notifications queue
    shezar_queue_append('notifications', $options);

    // Redirect if requested
    if ($redirect !== null) {
        // Cancel redirect for AJAX scripts.
        if (is_ajax_request($_SERVER)) {
            if (!$immediatesend) {
                ajax_result(true);
            } else {
                ajax_result(true, shezar_queue_shift('notifications'));
            }
        } else {
            redirect($redirect);
        }
        exit();
    }
}

/**
 * Return an array containing any notifications in $SESSION
 *
 * Should be called in the theme's header
 *
 * @return  array
 */
function shezar_get_notifications() {
    return shezar_queue_shift('notifications', true);
}


/**
 * Add an item to a shezar session queue
 *
 * @param   string  $key    Queue key
 * @param   mixed   $data   Data to add to queue
 * @return  void
 */
function shezar_queue_append($key, $data) {
    global $SESSION;

    if (!isset($SESSION->shezar_queue)) {
        $SESSION->shezar_queue = array();
    }

    if (!isset($SESSION->shezar_queue[$key])) {
        $SESSION->shezar_queue[$key] = array();
    }

    $SESSION->shezar_queue[$key][] = $data;
}


/**
 * Return part or all of a shezar session queue
 *
 * @param   string  $key    Queue key
 * @param   boolean $all    Flag to return entire session queue (optional)
 * @return  mixed
 */
function shezar_queue_shift($key, $all = false) {
    global $SESSION;

    // Value to return if no items in queue
    $return = $all ? array() : null;

    // Check if an items in queue
    if (empty($SESSION->shezar_queue) || empty($SESSION->shezar_queue[$key])) {
        return $return;
    }

    // If returning all, grab all and reset queue
    if ($all) {
        $return = $SESSION->shezar_queue[$key];
        $SESSION->shezar_queue[$key] = array();
        return $return;
    }

    // Otherwise pop oldest item from queue
    return array_shift($SESSION->shezar_queue[$key]);
}



/**
 *  Calls module renderer to return markup for displaying a progress bar for a user's course progress
 *
 * Optionally with a link to the user's profile if they have the correct permissions
 *
 * @access  public
 * @param   $userid     int
 * @param   $courseid   int
 * @param   $status     int     COMPLETION_STATUS_ constant
 * @return  string
 */
function shezar_display_course_progress_icon($userid, $courseid, $status) {
    global $PAGE, $COMPLETION_STATUS;

    $renderer = $PAGE->get_renderer('shezar_core');
    $content = $renderer->course_progress_bar($userid, $courseid, $status);
    return $content;
}

/**
 *  Adds the current icon and icon select dropdown to a moodle form
 *  replaces all the old shezar/icon classes
 *
 * @access  public
 * @param   object $mform Reference to moodle form object.
 * @param   string $action Form action - add, edit or view.
 * @param   string $type Program, course or message icons.
 * @param   string $currenticon Value currently stored in db.
 * @param   int    $nojs 1 if Javascript is disabled.
 * @param   bool   $fieldset If true, include a 'header' around the icon picker.
 * @return  void
*/
function shezar_add_icon_picker(&$mform, $action, $type, $currenticon='default', $nojs=0, $fieldset=true) {
    global $CFG;
    //get all icons of this type from core
    $replace = array('.png' => '', '_' => ' ', '-' => ' ');
    $iconhtml = shezar_icon_picker_preview($type, $currenticon);

    if ($fieldset) {
        $mform->addElement('header', 'iconheader', get_string($type.'icon', 'shezar_core'));
    }
    if ($nojs == 1) {
        $mform->addElement('static', 'currenticon', get_string('currenticon', 'shezar_core'), $iconhtml);
        if ($action=='add' || $action=='edit') {
            $path = $CFG->dirroot . '/shezar/core/pix/' . $type . 'icons';
            foreach (scandir($path) as $icon) {
                if ($icon == '.' || $icon == '..') { continue;}
                $iconfile = str_replace('.png', '', $icon);
                $iconname = strtr($icon, $replace);
                $icons[$iconfile] = ucwords($iconname);
            }
            $mform->addElement('select', 'icon', get_string('icon', 'shezar_core'), $icons);
            $mform->setDefault('icon', $currenticon);
            $mform->setType('icon', PARAM_TEXT);
        }
    } else {
        $buttonhtml = '';
        if ($action=='add' || $action=='edit') {
            $buttonhtml = html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('chooseicon', 'shezar_program'), 'id' => 'show-icon-dialog'));
            $mform->addElement('hidden', 'icon');
            $mform->setType('icon', PARAM_TEXT);
        }
        $mform->addElement('static', 'currenticon', get_string('currenticon', 'shezar_core'), $iconhtml . $buttonhtml);
    }
    if ($fieldset) {
        $mform->setExpanded('iconheader');
    }
}

/**
 *  Adds the current icon and icon select dropdown to a moodle form
 *  replaces all the old shezar/icon classes
 *
 * @access  public
 * @param   object $mform Reference to moodle form object.
 * @param   string $action Form action - add, edit or view.
 * @param   string $type Program, course or message icons.
 * @param   string $currenticon Value currently stored in db.
 * @param   int    $nojs 1 if Javascript is disabled.
 * @param   mixed  $ind index to add to icon title
 * @return  array of created elements
 */
function shezar_create_icon_picker(&$mform, $action, $type, $currenticon = '', $nojs = 0, $ind = '') {
    global $CFG;
    $return = array();
    if ($currenticon == '') {
        $currenticon = 'default';
    }
    // Get all icons of this type from core.
    $replace = array('.png' => '', '_' => ' ', '-' => ' ');
    $iconhtml = shezar_icon_picker_preview($type, $currenticon, $ind);

    if ($nojs == 1) {
        $return['currenticon'.$ind] = $mform->createElement('static', 'currenticon',
                get_string('currenticon', 'shezar_core'), $iconhtml);
        if ($action == 'add' || $action == 'edit') {
            $path = $CFG->dirroot . '/shezar/core/pix/' . $type . 'icons';
            foreach (scandir($path) as $icon) {
                if ($icon == '.' || $icon == '..') {
                    continue;
                }
                $iconfile = str_replace('.png', '', $icon);
                $iconname = strtr($icon, $replace);
                $icons[$iconfile] = ucwords($iconname);
            }
            $return['icon'.$ind] = $mform->createElement('select', 'icon',
                    get_string('icon', 'shezar_core'), $icons);
            $mform->setDefault('icon', $currenticon);
        }
    } else {
        $linkhtml = '';
        if ($action == 'add' || $action == 'edit') {
            $linkhtml = html_writer::tag('a', get_string('chooseicon', 'shezar_program'),
                    array('href' => '#', 'data-ind'=> $ind, 'id' => 'show-icon-dialog' . $ind,
                          'class' => 'show-icon-dialog'));
            $return['icon'.$ind] = $mform->createElement('hidden', 'icon', '',
                    array('id'=>'icon' . $ind));
        }
        $return['currenticon' . $ind] = $mform->createElement('static', 'currenticon', '',
                get_string('icon', 'shezar_program') . $iconhtml . $linkhtml);
    }
    return $return;
}

/**
 * Render preview of icon
 *
 * @param string $type type of icon (course or program)
 * @param string $currenticon current icon
 * @param string $ind index of icon on page (when several icons previewed)
 * @param string $alt alternative text for icon
 * @return string HTML
 */
function shezar_icon_picker_preview($type, $currenticon, $ind = '', $alt = '') {
    list($src, $alt) = shezar_icon_url_and_alt($type, $currenticon, $alt);

    $iconhtml = html_writer::empty_tag('img', array('src' => $src, 'id' => 'icon_preview' . $ind,
            'class' => "course_icon", 'alt' => $alt, 'title' => $alt));

    return $iconhtml;
}

/**
 * Get the url and alternate text of icon.
 *
 * @param string $type type of icon (course or program)
 * @param string $icon icon key (name for built-in icon or hash for user image)
 * @param string $alt alternative text for icon (overrides calculated alt text)
 * @return string HTML
 */
function shezar_icon_url_and_alt($type, $icon, $alt = '') {
    global $OUTPUT, $DB, $PAGE;

    $component = 'shezar_core';
    $src = '';

    // See if icon is a custom icon.
    if ($customicon = $DB->get_record('files', array('pathnamehash' => $icon))) {
        $fs = get_file_storage();
        $context = context_system::instance();
        if ($file = $fs->get_file($context->id, $component, $type, $customicon->itemid, '/', $customicon->filename)) {
            $icon = $customicon->filename;
            $src = moodle_url::make_pluginfile_url($file->get_contextid(), $component,
                $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $customicon->filename, true);
        }
    }

    if (empty($src)) {
        $iconpath = $type . 'icons/';
        $imagelocation = $PAGE->theme->resolve_image_location($iconpath. $icon, $component);
        if (empty($icon) || empty($imagelocation)) {
            $icon = 'default';
        }
        $src = $OUTPUT->pix_url('/' . $iconpath . $icon, $component);
    }

    $replace = array('.png' => '', '_' => ' ', '-' => ' ');
    $alt = ($alt != '') ? $alt : ucwords(strtr($icon, $replace));

    return array($src, $alt);
}

/**
* print out the shezar My Team nav section
*/
function shezar_print_my_team_nav() {
    global $CFG, $USER, $PAGE;

    $managerroleid = $CFG->managerroleid;

    // return users with this user as manager
    $staff = \shezar_job\job_assignment::get_staff_userids($USER->id);
    $teammembers = count($staff);

    //call renderer
    $renderer = $PAGE->get_renderer('shezar_core');
    $content = $renderer->my_team_nav($teammembers);
    return $content;
}

/**
* print out the table of visible reports
*/
function shezar_print_report_manager() {
    global $CFG, $USER, $PAGE;
    require_once($CFG->dirroot.'/shezar/reportbuilder/lib.php');

    $context = context_system::instance();
    $canedit = has_capability('shezar/reportbuilder:managereports',$context);

    $reportbuilder_permittedreports = get_my_reports_list();

    if (count($reportbuilder_permittedreports) > 0) {
        $renderer = $PAGE->get_renderer('shezar_core');
        $returnstr = $renderer->report_list($reportbuilder_permittedreports, $canedit);
    } else {
        $returnstr = get_string('nouserreports', 'shezar_reportbuilder');
    }
    return $returnstr;
}


function get_my_reports_list() {
    $reportbuilder_permittedreports = reportbuilder::get_user_permitted_reports();

    foreach ($reportbuilder_permittedreports as $key => $reportrecord) {
        if ($reportrecord->embedded) {
            try {
                new reportbuilder($reportrecord->id);
            } catch (moodle_exception $e) {
                if ($e->errorcode == "nopermission") {
                    // The report creation failed, almost certainly due to a failed is_capable check in an embedded report.
                    // In this case, we just skip it.
                    unset($reportbuilder_permittedreports[$key]);
                } else {
                    throw ($e);
                }
            }
        }
    }

    return $reportbuilder_permittedreports;
}


/**
* Returns markup for displaying saved scheduled reports
*
* Optionally without the options column and add/delete form
* Optionally with an additional sql WHERE clause
* @access public
* @param boolean $showoptions SHow icons to edit and delete scheduled reports.
* @param boolean $showaddform Show a simple form to allow reports to be scheduled.
* @param array $sqlclause In the form array($where, $params)
*/
function shezar_print_scheduled_reports($showoptions=true, $showaddform=true, $sqlclause=array()) {
    global $CFG, $PAGE;

    require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');
    require_once($CFG->dirroot . '/shezar/core/lib/scheduler.php');
    require_once($CFG->dirroot . '/calendar/lib.php');
    require_once($CFG->dirroot . '/shezar/reportbuilder/scheduled_forms.php');

    $scheduledreports = get_my_scheduled_reports_list();

    // If we want the form generate the content so it can be used into the templated.
    if ($showaddform) {
        $mform = new scheduled_reports_add_form($CFG->wwwroot . '/shezar/reportbuilder/scheduled.php', array());
        $addform = $mform->render();
    } else {
        $addform = '';
    }

    $renderer = $PAGE->get_renderer('shezar_core');
    echo $renderer->scheduled_reports($scheduledreports, $showoptions, $addform);
}


/**
 * Build a list of scheduled reports for display in a table.
 *
 * @param array $sqlclause In the form array($where, $params)
 * @return array
 * @throws coding_exception
 */
function get_my_scheduled_reports_list($sqlclause=array()) {
    global $DB, $REPORT_BUILDER_EXPORT_FILESYSTEM_OPTIONS, $USER;

    $myreports = reportbuilder::get_user_permitted_reports();

    $sql = "SELECT rbs.*, rb.fullname
            FROM {report_builder_schedule} rbs
            JOIN {report_builder} rb
            ON rbs.reportid=rb.id
            WHERE rbs.userid=?";

    $parameters = array($USER->id);

    if (!empty($sqlclause)) {
        list($conditions, $params) = $sqlclause;
        $parameters = array_merge($parameters, $params);
        $sql .= " AND " . $conditions;
    }
    //note from M2.0 these functions return an empty array, not false
    $scheduledreports = $DB->get_records_sql($sql, $parameters);
    //pre-process before sending to renderer
    foreach ($scheduledreports as $sched) {
        if (!isset($myreports[$sched->reportid])) {
            // Cannot access this report.
            unset($scheduledreports[$sched->id]);
            continue;
        }
        //data column
        if ($sched->savedsearchid != 0){
            $sched->data = $DB->get_field('report_builder_saved', 'name', array('id' => $sched->savedsearchid));
        }
        else {
            $sched->data = get_string('alldata', 'shezar_reportbuilder');
        }
        // Format column.
        $format = \shezar_core\tabexport_writer::normalise_format($sched->format);
        $allformats = \shezar_core\tabexport_writer::get_export_classes();
        if (isset($allformats[$format])) {
            $classname = $allformats[$format];
            $sched->format = $classname::get_export_option_name();
        } else {
            $sched->format = get_string('error');
        }
        // Export column.
        $key = array_search($sched->exporttofilesystem, $REPORT_BUILDER_EXPORT_FILESYSTEM_OPTIONS);
        $sched->exporttofilesystem = get_string($key, 'shezar_reportbuilder');
        //schedule column
        if (isset($sched->frequency) && isset($sched->schedule)){
            $schedule = new scheduler($sched, array('nextevent' => 'nextreport'));
            $formatted = $schedule->get_formatted();
            if ($next = $schedule->get_scheduled_time()) {
                if ($next < time()) {
                    // As soon as possible.
                    $next = time();
                }
                $formatted .= '<br />' . userdate($next);
            }
        } else {
            $formatted = get_string('schedulenotset', 'shezar_reportbuilder');
        }
        $sched->schedule = $formatted;
    }

    return $scheduledreports;
}

function shezar_print_my_courses() {
    global $CFG, $OUTPUT;

    // Report builder lib is required for the embedded report.
    require_once($CFG->dirroot.'/shezar/reportbuilder/lib.php');

    echo $OUTPUT->heading(get_string('mycurrentprogress', 'shezar_core'));

    $sid = optional_param('sid', '0', PARAM_INT);
    $debug  = optional_param('debug', 0, PARAM_INT);

    if (!$report = reportbuilder_get_embedded_report('course_progress', array(), false, $sid)) {
        print_error('error:couldnotgenerateembeddedreport', 'shezar_reportbuilder');
    }

    if ($debug) {
        $report->debug($debug);
    }

    $report->include_js();
    $report->display_table();
}


/**
 * Check if a user is a manager of another user
 *
 * If managerid is not set, uses the current user
 *
 * @deprecated since 9.0
 * @param int $userid       ID of user
 * @param int $managerid    ID of a potential manager to check (optional)
 * @param int $postype      Type of the position to check (POSITION_TYPE_* constant). Defaults to all positions (optional)
 * @return boolean true if user $userid is managed by user $managerid
 **/
function shezar_is_manager($userid, $managerid = null, $postype = null) {
    global $USER;

    debugging('The function shezar_is_manager has been deprecated since 9.0. Please use \shezar_job\job_assignment::is_managing instead.', DEBUG_DEVELOPER);

    if (empty($managerid)) {
        $managerid = $USER->id;
    }

    $staffjaid = null;

    if (!empty($postype)) {
        if (!in_array($postype, array(POSITION_TYPE_PRIMARY, POSITION_TYPE_SECONDARY))) {
            // Position type not recognised. Or if it was for an aspiration position, manager assignments were not possible.
            return false;
        }
        // If postype has been included then we'll look according to sortorder. We're only getting job assignments
        // where there's a manager.
        $jobassignments = \shezar_job\job_assignment::get_all($userid, true);
        foreach($jobassignments as $jobassignment) {
            if ($jobassignment->sortorder == $postype) {
                $staffjaid = $jobassignment->id;
                break;
            }
        }

        if (empty($staffjaid)) {
            // None found with that $postype, meaning there is no manager at all for that postype.
            return false;
        }
    }

    return \shezar_job\job_assignment::is_managing($managerid, $userid, $staffjaid);
}

/**
 * Returns the staff of the specified user
 *
 * @deprecated since 9.0
 * @param int $managerid ID of a user to get the staff of, If $managerid is not set, returns staff of current user
 * @param mixed $postype Type of the position to check (POSITION_TYPE_* constant). Defaults to primary position (optional)
 * @param bool $sort optional ordering by lastname, firstname
 * @return array|bool Array of userids of staff who are managed by user $userid , or false if none
 **/
function shezar_get_staff($managerid = null, $postype = null, $sort = false) {
    global $USER;

    debugging('shezar_get_staff has been deprecated since 9.0. Use \shezar_job\job_assignment::get_staff_userids instead.', DEBUG_DEVELOPER);

    if ($sort) {
        debugging('Warning: The $sort argument in deprecated function shezar_get_staff is no longer valid. Returned ids will not be sorted according to last name and first name.',
            DEBUG_DEVELOPER);
    }

    if (!empty($postype)) {
        if (!in_array($postype, array(POSITION_TYPE_PRIMARY, POSITION_TYPE_SECONDARY))) {
            // Position type not recognised. Or if it was for an aspiration position, manager assignments were not possible.
            return false;
        }
    } else {
        $postype = POSITION_TYPE_PRIMARY;
    }

    if (empty($managerid)) {
        $managerid = $USER->id;
    }

    $jobassignments = \shezar_job\job_assignment::get_all($managerid);
    $result = false;
    foreach ($jobassignments as $jobassignment) {
        if (!empty($postype) && $jobassignment->sortorder != $postype) {
            // If $postype was specified, closest to backwards-compatibility we can achieve is to base it on sortorder.
            continue;
        }

        $result = \shezar_job\job_assignment::get_staff_userids($managerid, $jobassignment->id, true);
        break;
    }

    if (empty($result)) {
        return false;
    } else {
        return $result;
    }
}

/**
 * Find out a user's manager.
 *
 * @deprecated since 9.0
 * @param int $userid Id of the user whose manager we want
 * @param int $postype Type of the position we want the manager for (POSITION_TYPE_* constant). Defaults to primary position (i.e. sortorder=1).
 * @param boolean $skiptemp Skip check and return of temporary manager
 * @param boolean $skipreal Skip check and return of real manager
 * @return mixed False if no manager. Manager user object from mdl_user if the user has a manager.
 */
function shezar_get_manager($userid, $postype = null, $skiptemp = false, $skipreal = false) {
    global $CFG, $DB;

    debugging('shezar_get_manager has been deprecated since 9.0. You will need to use methods from \shezar_job\job_assignment instead.', DEBUG_DEVELOPER);

    if (!empty($postype)) {
        if (!in_array($postype, array(POSITION_TYPE_PRIMARY, POSITION_TYPE_SECONDARY))) {
            // Position type not recognised. Or if it was for an aspiration position, manager assignments were not possible.
            return false;
        }
    } else {
        $postype = POSITION_TYPE_PRIMARY;
    }

    $jobassignments = \shezar_job\job_assignment::get_all($userid);

    $managerid = false;
    foreach ($jobassignments as $jobassignment) {
        if (!empty($postype) && $jobassignment->sortorder != $postype) {
            // If $postype was specified, closest to backwards-compatibility we can achieve is to base it on sortorder.
            continue;
        }
        if (!$skiptemp && $jobassignment->tempmanagerjaid && !empty($CFG->enabletempmanagers)) {
            $managerid = $jobassignment->tempmanagerid;
            break;
        }
        if (!$skipreal && $jobassignment->managerjaid) {
            $managerid = $jobassignment->managerid;
            break;
        }
    }

    if ($managerid) {
        return $DB->get_record('user', array('id' => $managerid));
    } else {
        return false;
    }
}

/**
 * Find the manager of the user's 'first' job.
 *
 * @deprecated since version 9.0
 * @param int|bool $userid Id of the user whose manager we want
 * @return mixed False if no manager. Manager user object from mdl_user if the user has a manager.
 */
function shezar_get_most_primary_manager($userid = false) {
    global $DB, $USER;

    debugging("shezar_get_most_primary_manager is deprecated. Use \\shezar_job\\job_assignment methods instead.", DEBUG_DEVELOPER);

    if ($userid === false) {
        $userid = $USER->id;
    }

    $managers = \shezar_job\job_assignment::get_all_manager_userids($userid);
    if (!empty($managers)) {
        $managerid = reset($managers);
        return $DB->get_record('user', array('id' => $managerid));
    }
    return false;
}

/**
 * Update/set a temp manager for the specified user
 *
 * @deprecated since 9.0
 * @param int $userid Id of user to set temp manager for
 * @param int $managerid Id of temp manager to be assigned to user.
 * @param int $expiry Temp manager expiry epoch timestamp
 */
function shezar_update_temporary_manager($userid, $managerid, $expiry) {
    global $CFG, $DB, $USER;

    debugging('shezar_update_temporary_manager is deprecated. Use \shezar_job\job_assignment::update instead.', DEBUG_DEVELOPER);

    if (!$user = $DB->get_record('user', array('id' => $userid))) {
        return false;
    }

    // With multiple job assignments, we'll only consider the first job assignment for this function.
    $jobassignment = \shezar_job\job_assignment::get_first($userid, false);
    if (empty($jobassignment)) {
        return false;
    }

    if (empty($jobassignment->managerid)) {
        $realmanager = false;
    } else {
        $realmanager = $DB->get_record('user', array('id' => $jobassignment->managerid));
    }

    if (empty($jobassignment->tempmanagerid)) {
        $oldtempmanager = false;
    } else {
        $oldtempmanager = $DB->get_record('user', array('id' => $jobassignment->tempmanagerid));
    }

    if (!$newtempmanager = $DB->get_record('user', array('id' => $managerid))) {
        return false;
    }

    // Set up messaging.
    require_once($CFG->dirroot.'/shezar/message/messagelib.php');
    $msg = new stdClass();
    $msg->userfrom = $USER;
    $msg->msgstatus = shezar_MSG_STATUS_OK;
    $msg->contexturl = $CFG->wwwroot.'/shezar/job/jobassignment.php?jobassignmentid='.$this->id;
    $msg->contexturlname = get_string('xpositions', 'shezar_core', fullname($user));
    $msgparams = (object)array('staffmember' => fullname($user), 'tempmanager' => fullname($newtempmanager),
        'expirytime' => userdate($expiry, get_string('datepickerlongyearphpuserdate', 'shezar_core')), 'url' => $msg->contexturl);

    if (!empty($oldtempmanager) && $newtempmanager->id == $oldtempmanager->tempmanagerid) {
        if ($jobassignment->tempmanagerexpirydate == $expiry) {
            // Nothing to do here.
            return true;
        } else {
            // Update expiry time.
            $jobassignment->update(array('tempmanagerexpirydate' => $expiry));

            // Expiry change notifications.

            // Notify staff member.
            $msg->userto = $user;
            $msg->subject = get_string('tempmanagerexpiryupdatemsgstaffsubject', 'shezar_core', $msgparams);
            $msg->fullmessage = get_string('tempmanagerexpiryupdatemsgstaff', 'shezar_core', $msgparams);
            $msg->fullmessagehtml = get_string('tempmanagerexpiryupdatemsgstaff', 'shezar_core', $msgparams);
            tm_alert_send($msg);

            // Notify real manager.
            if (!empty($realmanager)) {
                $msg->userto = $realmanager;
                $msg->subject = get_string('tempmanagerexpiryupdatemsgmgrsubject', 'shezar_core', $msgparams);
                $msg->fullmessage = get_string('tempmanagerexpiryupdatemsgmgr', 'shezar_core', $msgparams);
                $msg->fullmessagehtml = get_string('tempmanagerexpiryupdatemsgmgr', 'shezar_core', $msgparams);
                $msg->roleid = $CFG->managerroleid;
                tm_alert_send($msg);
            }

            // Notify temp manager.
            $msg->userto = $newtempmanager;
            $msg->subject = get_string('tempmanagerexpiryupdatemsgtmpmgrsubject', 'shezar_core', $msgparams);
            $msg->fullmessage = get_string('tempmanagerexpiryupdatemsgtmpmgr', 'shezar_core', $msgparams);
            $msg->fullmessagehtml = get_string('tempmanagerexpiryupdatemsgtmpmgr', 'shezar_core', $msgparams);
            $msg->roleid = $CFG->managerroleid;
            tm_alert_send($msg);

            return true;
        }
    }

    $newtempmanagerja = \shezar_job\job_assignment::get_first($newtempmanager->id);
    if (empty($newtempmanagerja)) {
        $newtempmanagerja = \shezar_job\job_assignment::create_default($newtempmanager->id);
    }
    // Assign/update temp manager role assignment.
    $jobassignment->update(array('tempmanagerjaid' => $newtempmanagerja->id, 'tempmanagerexpirydate' => $expiry));

    // Send assignment notifications.

    // Notify staff member.
    $msg->userto = $user;
    $msg->subject = get_string('tempmanagerassignmsgstaffsubject', 'shezar_core', $msgparams);
    $msg->fullmessage = get_string('tempmanagerassignmsgstaff', 'shezar_core', $msgparams);
    $msg->fullmessagehtml = get_string('tempmanagerassignmsgstaff', 'shezar_core', $msgparams);
    tm_alert_send($msg);

    // Notify real manager.
    if (!empty($realmanager)) {
        $msg->userto = $realmanager;
        $msg->subject = get_string('tempmanagerassignmsgmgrsubject', 'shezar_core', $msgparams);
        $msg->fullmessage = get_string('tempmanagerassignmsgmgr', 'shezar_core', $msgparams);
        $msg->fullmessagehtml = get_string('tempmanagerassignmsgmgr', 'shezar_core', $msgparams);
        $msg->roleid = $CFG->managerroleid;
        tm_alert_send($msg);
    }

    // Notify temp manager.
    $msg->userto = $newtempmanager;
    $msg->subject = get_string('tempmanagerassignmsgtmpmgrsubject', 'shezar_core', $msgparams);
    $msg->fullmessage = get_string('tempmanagerassignmsgtmpmgr', 'shezar_core', $msgparams);
    $msg->fullmessagehtml = get_string('tempmanagerassignmsgtmpmgr', 'shezar_core', $msgparams);
    $msg->roleid = $CFG->managerroleid;
    tm_alert_send($msg);
}

/**
 * Unassign the temporary manager of the specified user
 *
 * @deprecated since 9.0
 * @param int $userid
 * @return boolean true on success
 * @throws Exception
 */
function shezar_unassign_temporary_manager($userid) {
    global $DB, $CFG;

    debugging('shezar_unassign_temporary_manager is deprecated. Use \shezar_job\job_assignment::update instead.', DEBUG_DEVELOPER);

    // We'll use first job assignment only.
    $jobassignment = \shezar_job\job_assignment::get_first($userid, false);
    if (empty($jobassignment)) {
        return false;
    }

    if (empty($jobassignment->tempmanagerid)) {
        // Nothing to do.
        return true;
    }
    $jobassignment->update(array('tempmanagerjaid' => null, 'tempmanagerexpirydate' => null));

    return true;
}

/**
 * Find out a user's teamleader (manager's manager).
 *
 * @deprecated since 9.0
 * @param int $userid Id of the user whose teamleader we want
 * @param int $postype Type of the position we want the teamleader for (POSITION_TYPE_* constant).  Defaults to primary position (i.e. sortorder=1).
 * @return mixed False if no teamleader. Teamleader user object from mdl_user if the user has a teamleader.
 */
function shezar_get_teamleader($userid, $postype = null) {

    debugging('shezar_get_teamleader is deprecated. Use \shezar_job\job_assignment methods instead.', DEBUG_DEVELOPER);

    if (!empty($postype)) {
        if (!in_array($postype, array(POSITION_TYPE_PRIMARY, POSITION_TYPE_SECONDARY))) {
            // Position type not recognised. Or if it was for an aspiration position, manager assignments were not possible.
            return false;
        }
    } else {
        $postype = POSITION_TYPE_PRIMARY;
    }

    $manager = shezar_get_manager($userid, $postype);

    if (empty($manager)) {
        return false;
    } else {
        return shezar_get_manager($manager->id, $postype);
    }
}


/**
 * Find out a user's appraiser.
 *
 * @deprecated since 9.0
 * @param int $userid Id of the user whose appraiser we want
 * @param int $postype Type of the position we want the appraiser for (POSITION_TYPE_* constant).
 *                     Defaults to primary position(optional)
 * @return mixed False if no appraiser. Appraiser user object from mdl_user if the user has a appraiser.
 */
function shezar_get_appraiser($userid, $postype = null) {
    global $DB;

    debugging('shezar_get_appraiser is deprecated. Use \shezar_job\job_assignment methods instead.', DEBUG_DEVELOPER);

    if (!empty($postype)) {
        if (!in_array($postype, array(POSITION_TYPE_PRIMARY, POSITION_TYPE_SECONDARY))) {
            // Position type not recognised. Or if it was for an aspiration position, appraiser assignments were not possible.
            return false;
        }
    } else {
        $postype = POSITION_TYPE_PRIMARY;
    }

    $jobassignments = \shezar_job\job_assignment::get_all($userid);

    $appraiserid = false;
    foreach ($jobassignments as $jobassignment) {
        if (!empty($postype) && $jobassignment->sortorder != $postype) {
            // If $postype was specified, closest to backwards-compatibility we can achieve is to base it on sortorder.
            continue;
        }
        $appraiserid = $jobassignment->appraiserid;
    }

    if ($appraiserid) {
        return $DB->get_record('user', array('id' => $appraiserid));
    } else {
        return false;
    }
}


/**
 * Returns unix timestamp from a date string depending on the date format
 * for the current $USER or server timezone.
 *
 * Note: timezone info in $format is not supported
 *
 * @param string $format e.g. "d/m/Y" - see date_parse_from_format for supported formats
 * @param string $date a date to be converted e.g. "12/06/12"
 * @param bool $servertimezone
 * @param string $forcetimezone force one specific timezone, $servertimezone is ignored if specified
 * @return int unix timestamp (0 if fails to parse)
 */
function shezar_date_parse_from_format($format, $date, $servertimezone = false, $forcetimezone = null) {
    $dateArray = date_parse_from_format($format, $date);
    if (!is_array($dateArray) or !empty($dateArray['error_count'])) {
        return 0;
    }
    if ($dateArray['is_localtime']) {
        // Not timezone support, sorry.
        return 0;
    }

    if (!is_null($forcetimezone)) {
        $tzobj = new DateTimeZone(core_date::normalise_timezone($forcetimezone));
    } else if ($servertimezone) {
        $tzobj = core_date::get_server_timezone_object();
    } else {
        $tzobj = core_date::get_user_timezone_object();
    }

    $date = new DateTime('now', $tzobj);
    $date->setDate($dateArray['year'], $dateArray['month'], $dateArray['day']);
    $date->setTime($dateArray['hour'], $dateArray['minute'], $dateArray['second']);

    return $date->getTimestamp();
}


/**
 * Check if the HTTP request was of type POST
 *
 * This function is useful as sometimes the $_POST array can be empty
 * if it's size exceeded post_max_size
 *
 * @access  public
 * @return  boolean
 */
function shezar_is_post_request() {
    return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST';
}


/**
 * Download stored errorlog as a zip
 *
 * @access  public
 * @return  void
 */
function shezar_errors_download() {
    global $DB;

    // Load errors from database
    $errors = $DB->get_records('errorlog');
    if (!$errors) {
        $errors = array();
    }

    // Format them nicely as strings
    $content = '';
    foreach ($errors as $error) {
        $error = (array) $error;
        foreach ($error as $key => $value) {
            $error[$key] = str_replace(array("\t", "\n"), ' ', $value);
        }

        $content .= implode("\t", $error);
        $content .= "\n";
    }

    send_temp_file($content, 'shezar-error.log', true);
}


/**
 * Generate markup for search box
 *
 * Gives ability to specify courses, programs and/or categories in the results
 * as well as the ability to limit by category
 *
 * @access  public
 * @param   string  $value      Search value
 * @param   bool    $return     Return results (always true in M2.0, param left until all calls elsewhere cleaned up!)
 * @param   string  $type       Type of results ('all', 'course', 'program', 'certification', 'category')
 * @param   int     $category   Parent category (0 means all, -1 means global search)
 * @return  string|void
 */
function print_shezar_search($value = '', $return = true, $type = 'all', $category = -1) {

    global $CFG, $DB, $PAGE;
    $return = ($return) ? $return : true;

    static $count = 0;

    $count++;

    $id = 'shezarsearch';

    if ($count > 1) {
        $id .= '_'.$count;
    }

    $action = "{$CFG->wwwroot}/course/search.php";

    // If searching in a category, indicate which category
    if ($category > 0) {
        // Get category name
        $categoryname = $DB->get_field('course_categories', 'name', array('id' => $category));
        if ($categoryname) {
            $strsearch = get_string('searchx', 'shezar_core', $categoryname);
        } else {
            $strsearch = get_string('search');
        }
    } else {
        if ($type == 'course') {
            $strsearch = get_string('searchallcourses', 'shezar_coursecatalog');
        } elseif ($type == 'program') {
            $strsearch = get_string('searchallprograms', 'shezar_coursecatalog');
        } elseif ($type == 'certification') {
            $strsearch = get_string('searchallcertifications', 'shezar_coursecatalog');
        } elseif ($type == 'category') {
            $strsearch = get_string('searchallcategories', 'shezar_coursecatalog');
        } else {
            $strsearch = get_string('search');
            $type = '';
        }
    }

    $hiddenfields = array(
        'viewtype' => $type,
        'category' => $category,
    );
    $formid = 'searchshezar';
    $inputid = 'navsearchbox';
    $value = s($value, true);
    $strsearch = s($strsearch);

    $renderer = $PAGE->get_renderer('shezar_core');
    $output = $renderer->print_shezar_search($action, $hiddenfields, $strsearch, $value, $formid, $inputid);

    return $output;
}


/**
 * Displays a generic editing on/off button suitable for any page
 *
 * @param string $settingname Name of the $USER property used to determine if the button should display on or off
 * @param array $params Associative array of additional parameters to pass (optional)
 *
 * @return string HTML to display the button
 */
function shezar_print_edit_button($settingname, $params = array()) {
    global $CFG, $USER, $OUTPUT;

    $currentstate = isset($USER->$settingname) ?
        $USER->$settingname : null;

    // Work out the appropriate action.
    if (empty($currentstate)) {
        $label = get_string('turneditingon');
        $edit = 'on';
    } else {
        $label = get_string('turneditingoff');
        $edit = 'off';
    }

    // Generate the button HTML.
    $params[$settingname] = $edit;
    return $OUTPUT->single_button(new moodle_url(qualified_me(), $params), $label, 'get');
}


/**
 * Return a language string in the local language for a given user
 *
 * @deprecated Use get_string() with 4th parameter instead
 *
 */
function get_string_in_user_lang($user, $identifier, $module='', $a=NULL, $extralocations=NULL) {
    debugging('get_string_in_user_lang() is deprecated. Use get_string() with 4th param instead', DEBUG_DEVELOPER);
    return get_string($identifier, $module, $a, $user->lang);
}

/**
 * Returns the SQL to be used in order to CAST one column to CHAR
 *
 * @param string fieldname the name of the field to be casted
 * @return string the piece of SQL code to be used in your statement.
 */
function sql_cast2char($fieldname) {

    global $DB;

    $sql = '';

    switch ($DB->get_dbfamily()) {
        case 'mysql':
            $sql = ' CAST(' . $fieldname . ' AS CHAR) COLLATE utf8_bin';
            break;
        case 'postgres':
            $sql = ' CAST(' . $fieldname . ' AS VARCHAR) ';
            break;
        case 'mssql':
            $sql = ' CAST(' . $fieldname . ' AS NVARCHAR(MAX)) ';
            break;
        case 'oracle':
            $sql = ' TO_CHAR(' . $fieldname . ') ';
            break;
        default:
            $sql = ' ' . $fieldname . ' ';
    }

    return $sql;
}


/**
 * Returns the SQL to be used in order to CAST one column to FLOAT
 *
 * @param string fieldname the name of the field to be casted
 * @return string the piece of SQL code to be used in your statement.
 */
function sql_cast2float($fieldname) {
    global $DB;

    $sql = '';

    switch ($DB->get_dbfamily()) {
        case 'mysql':
            $sql = ' CAST(' . $fieldname . ' AS DECIMAL(20,2)) ';
            break;
        case 'mssql':
        case 'postgres':
            $sql = ' CAST(' . $fieldname . ' AS FLOAT) ';
            break;
        case 'oracle':
            $sql = ' TO_BINARY_FLOAT(' . $fieldname . ') ';
            break;
        default:
            $sql = ' ' . $fieldname . ' ';
    }

    return $sql;
}

/**
 * Returns as case sensitive field name.
 *
 * @param string $field table field name
 * @return string SQL code fragment
 */
function sql_collation($field) {
    global $DB;

    $namefield = $field;
    switch ($DB->get_dbfamily()) {
        case('sqlsrv'):
        case('mssql'):
            $namefield  = "{$field} COLLATE " . mssql_get_collation(). " AS {$field}";
            break;
        case('mysql'):
            $namefield = "(BINARY {$field}) AS {$field}";
            break;
        case('postgres'):
            $namefield = $field;
            break;
    }

    return $namefield;
}

/**
 * Returns 'collation' part of a query.
 *
 * @param bool $casesensitive use case sensitive search
 * @param bool $accentsensitive use accent sensitive search
 * @return string SQL code fragment
 */
function mssql_get_collation($casesensitive = true, $accentsensitive = true) {
    global $DB, $CFG;

    // Make some default.
    $collation = 'Latin1_General_CI_AI';

    $sql = "SELECT CAST(DATABASEPROPERTYEX('{$CFG->dbname}', 'Collation') AS varchar(255)) AS SQLCollation";
    $record = $DB->get_record_sql($sql, null, IGNORE_MULTIPLE);
    if ($record) {
        $collation = $record->sqlcollation;
        if ($casesensitive) {
            $collation = str_replace('_CI', '_CS', $collation);
        } else {
            $collation = str_replace('_CS', '_CI', $collation);
        }
        if ($accentsensitive) {
            $collation = str_replace('_AI', '_AS', $collation);
        } else {
            $collation = str_replace('_AS', '_AI', $collation);
        }
    }

    return $collation;
}

/**
 * Assign a user a position assignment and create/delete role assignments as required
 *
 * @deprecated since 9.0.
 * @param $assignment
 * @param bool $unittest set to true if using for unit tests (optional)
 */
function assign_user_position($assignment, $unittest=false) {
    throw new coding_exception('assign_user_position has been deprecated since 9.0. You will need to use \shezar_job\job_assignment methods instead.');
}

/**
* Loops through the navigation options and returns an array of classes
*
* The array contains the navigation option name as a key, and a string
* to be inserted into a class as the value. The string is either
* ' selected' if the option is currently selected, or an empty string ('')
*
* @param array $navstructure A nested array containing the structure of the menu
* @param string $primary_selected The name of the primary option
* @param string $secondary_selected The name of the secondary option
*
* @return array Array of strings, keyed on option names
*/
function shezar_get_nav_select_classes($navstructure, $primary_selected, $secondary_selected) {

    $selectedstr = ' selected';
    $selected = array();
    foreach($navstructure as $primary => $secondaries) {
        if($primary_selected == $primary) {
            $selected[$primary] = $selectedstr;
        } else {
            $selected[$primary] = '';
        }
        foreach($secondaries as $secondary) {
            if($secondary_selected == $secondary) {
                $selected[$secondary] = $selectedstr;
            } else {
                $selected[$secondary] = '';
            }
        }
    }
    return $selected;
}

/**
 * Reset shezar menu caching.
 */
function shezar_menu_reset_cache() {
    global $SESSION;
    unset($SESSION->mymenu);
}

/**
 * Builds shezar menu, returns an array of objects that
 * represent the stucture of the menu
 *
 * The parents must be defined before the children so we
 * can correctly figure out which items should be selected
 *
 * @return Array of menu item objects
 */
function shezar_build_menu() {
    global $SESSION, $USER, $CFG;

    $lang = current_language();
    if (!empty($CFG->menulifetime) and !empty($SESSION->mymenu['lang'])) {
        if ($SESSION->mymenu['id'] == $USER->id and $SESSION->mymenu['lang'] === $lang) {
            if ($SESSION->mymenu['c'] + $CFG->menulifetime > time()) {
                $tree = $SESSION->mymenu['tree'];
                foreach ($tree as $k => $node) {
                    $node = clone($node);
                    $node->url = \shezar_core\shezar\menu\menu::replace_url_parameter_placeholders($node->url);
                    $tree[$k] = $node;
                }
                return $tree;
            }
        }
    }
    unset($SESSION->mymenu);

    $rs = \shezar_core\shezar\menu\menu::get_nodes();
    $tree = array();
    $parentree = array();
    foreach ($rs as $id => $item) {

        if (!isset($parentree[$item->parentid])) {
            $node = \shezar_core\shezar\menu\menu::get($item->parentid);
            // Silently ignore bad nodes - they might have been removed
            // from the code but not purged from the DB yet.
            if ($node === false) {
                continue;
            }
            $parentree[$item->parentid] = $node;
        }
        $node = $parentree[$item->parentid];

        switch ((int)$item->parentvisibility) {
            case \shezar_core\shezar\menu\menu::HIDE_ALWAYS:
                if (!is_null($item->parentvisibility)) {
                    continue 2;
                }
                break;
            case \shezar_core\shezar\menu\menu::SHOW_WHEN_REQUIRED:
                $classname = $item->parent;
                if (!is_null($classname) && class_exists($classname)) {
                    $parentnode = new $classname($node);
                    if ($parentnode->get_visibility() != \shezar_core\shezar\menu\menu::SHOW_ALWAYS) {
                        continue 2;
                    }
                }
                break;
            case \shezar_core\shezar\menu\menu::SHOW_ALWAYS:
                break;
            case \shezar_core\shezar\menu\menu::SHOW_CUSTOM:
                $classname = $item->parent;
                if (!is_null($classname) && class_exists($classname)) {
                    $parentnode = new $classname($node);
                    if (!$parentnode->get_visibility()) {
                        continue 2;
                    }
                }
                break;
            default:
                // Silently ignore bad nodes - they might have been removed
                // from the code but not purged from the DB yet.
                continue 2;
        }

        $node = \shezar_core\shezar\menu\menu::node_instance($item);
        // Silently ignore bad nodes - they might have been removed
        // from the code but not purged from the DB yet.
        if ($node === false) {
            continue;
        }
        // Check each node's visibility.
        if ($node->get_visibility() != \shezar_core\shezar\menu\menu::SHOW_ALWAYS) {
            continue;
        }

        $tree[] = (object)array(
            'name'     => $node->get_name(),
            'linktext' => $node->get_title(),
            'parent'   => $node->get_parent(),
            'url'      => $node->get_url(false),
            'target'   => $node->get_targetattr()
        );
    }

    if (!empty($CFG->menulifetime)) {
        $SESSION->mymenu = array(
            'id' => $USER->id,
            'lang' => $lang,
            'c' => time(),
            'tree' => $tree,
        );
    }

    foreach ($tree as $k => $node) {
        $node = clone($node);
        $node->url = \shezar_core\shezar\menu\menu::replace_url_parameter_placeholders($node->url);
        $tree[$k] = $node;
    }

    return $tree;
}

function shezar_upgrade_menu() {
    shezar_menu_reset_cache();
    $shezarMENU = new \shezar_core\shezar\menu\build();
    $plugintypes = core_component::get_plugin_types();
    foreach ($plugintypes as $plugin => $path) {
        $pluginname = core_component::get_plugin_list_with_file($plugin, 'db/shezarmenu.php');
        if (!empty($pluginname)) {
            foreach ($pluginname as $name => $file) {
                // This is NOT a library file!
                require($file);
            }
        }
    }
    $shezarMENU->upgrade();
}

/**
 * Color functions used by shezar themes for auto-generating colors
 */

/**
 * Given a hex color code lighten or darken the color by the specified
 * percentage and return the hex code of the new color
 *
 * @param string $color Hex color code in format '#abc' or '#aabbcc'
 * @param integer $percent Number between -100 and 100, negative to darken
 * @return string 6 digit hex color code for resulting color
 */
function shezar_brightness($color, $percent) {
    // convert 3 digit color codes into 6 digit form
    $pattern = '/^#([[:xdigit:]])([[:xdigit:]])([[:xdigit:]])$/';
    $color = preg_replace($pattern, '#$1$1$2$2$3$3', $color );

    // don't change if color format not recognised
    $pattern = '/^#([[:xdigit:]]{2})([[:xdigit:]]{2})([[:xdigit:]]{2})$/';
    if (!preg_match($pattern, $color, $matches)) {
        debugging("Bad hex color '{$color}' passed to shezar_brightness().", DEBUG_DEVELOPER);
        return $color;
    }
    $red = hexdec($matches[1]);
    $green = hexdec($matches[2]);
    $blue = hexdec($matches[3]);

    if ($percent >= 0) {
        $red = floor($red + (255 - $red) * $percent / 100);
        $green = floor($green + (255 - $green) * $percent / 100);
        $blue = floor($blue + (255 - $blue) * $percent / 100);
    } else {
        // remember $percent is negative
        $red = floor($red + $red * $percent / 100);
        $green = floor($green + $green * $percent / 100);
        $blue = floor($blue + $blue * $percent / 100);
    }

    return '#' .
        str_pad(dechex($red), 2, '0', STR_PAD_LEFT) .
        str_pad(dechex($green), 2, '0', STR_PAD_LEFT) .
        str_pad(dechex($blue), 2, '0', STR_PAD_LEFT);
}


/**
 * Given a hex color code lighten or darken the color by the specified
 * number of hex points and return the hex code of the new color
 *
 * This differs from {@link shezar_brightness} in that the scaling is
 * linear (until pure white or black is reached). *
 *
 * @param string $color Hex color code in format '#abc' or '#aabbcc'
 * @param integer $amount Number between -255 and 255, negative to darken
 * @return string 6 digit hex color code for resulting color
 */
function shezar_brightness_linear($color, $amount) {
    // convert 3 digit color codes into 6 digit form
    $pattern = '/^#([[:xdigit:]])([[:xdigit:]])([[:xdigit:]])$/';
    $color = preg_replace($pattern, '#$1$1$2$2$3$3', $color );

    // don't change if color format not recognised
    $pattern = '/^#([[:xdigit:]]{2})([[:xdigit:]]{2})([[:xdigit:]]{2})$/';
    if (!preg_match($pattern, $color, $matches)) {
        debugging("Bad hex color '{$color}' passed to shezar_brightness_linear().", DEBUG_DEVELOPER);
        return $color;
    }
    $red = hexdec($matches[1]);
    $green = hexdec($matches[2]);
    $blue = hexdec($matches[3]);

    // max and min ensure colour remains within range
    $red = max(min($red + $amount, 255), 0);
    $green = max(min($green + $amount, 255), 0);
    $blue = max(min($blue + $amount, 255), 0);

    return '#' .
        str_pad(dechex($red), 2, '0', STR_PAD_LEFT) .
        str_pad(dechex($green), 2, '0', STR_PAD_LEFT) .
        str_pad(dechex($blue), 2, '0', STR_PAD_LEFT);
}

/**
 * Given a hex color code return the hex code for either white or black,
 * depending on which color will have the most contrast compared to $color
 *
 * @param string $color Hex color code in format '#abc' or '#aabbcc'
 * @return string 6 digit hex color code for resulting color
 */
function shezar_readable_text($color) {
    // convert 3 digit color codes into 6 digit form
    $pattern = '/^#([[:xdigit:]])([[:xdigit:]])([[:xdigit:]])$/';
    $color = preg_replace($pattern, '#$1$1$2$2$3$3', $color );

    // don't change if color format not recognised
    $pattern = '/^#([[:xdigit:]]{2})([[:xdigit:]]{2})([[:xdigit:]]{2})$/';
    if (!preg_match($pattern, $color, $matches)) {
        debugging("Bad hex color '{$color}' passed to shezar_readable_text().", DEBUG_DEVELOPER);
        return $color;
    }
    $red = hexdec($matches[1]);
    $green = hexdec($matches[2]);
    $blue = hexdec($matches[3]);

    // get average intensity
    $avg = array_sum(array($red, $green, $blue)) / 3;
    return ($avg >= 153) ? '#000000' : '#FFFFFF';
}

/**
 * Given a hex color code return the rgba shadow that will work best on text
 * that is the readable-text color
 *
 * This is useful for adding shadows to text that uses the readable-text color.
 *
 * @param string $color Hex color code in format '#abc' or '#aabbcc'
 * @return string rgba() colour to provide an appropriate shadow for readable-text
 */
function shezar_readable_text_shadow($color) {
    if (shezar_readable_text($color) == '#FFFFFF') {
        return 'rgba(0, 0, 0, 0.75)';
    } else {
        return 'rgba(255, 255, 255, 0.75)';
    }
}
/**
 * Given a hex color code return the hex code for a desaturated version of
 * $color, which has the same brightness but is greyscale
 *
 * @param string $color Hex color code in format '#abc' or '#aabbcc'
 * @return string 6 digit hex color code for resulting greyscale color
 */
function shezar_desaturate($color) {
    // convert 3 digit color codes into 6 digit form
    $pattern = '/^#([[:xdigit:]])([[:xdigit:]])([[:xdigit:]])$/';
    $color = preg_replace($pattern, '#$1$1$2$2$3$3', $color );

    // don't change if color format not recognised
    $pattern = '/^#([[:xdigit:]]{2})([[:xdigit:]]{2})([[:xdigit:]]{2})$/';
    if (!preg_match($pattern, $color, $matches)) {
        debugging("Bad hex color '{$color}' passed to desaturate().", DEBUG_DEVELOPER);
        return $color;
    }
    $red = hexdec($matches[1]);
    $green = hexdec($matches[2]);
    $blue = hexdec($matches[3]);

    // get average intensity
    $avg = array_sum(array($red, $green, $blue)) / 3;

    return '#' . str_repeat(str_pad(dechex($avg), 2, '0', STR_PAD_LEFT), 3);
}

/**
 * Given an array of the form:
 * array(
 *   // setting name => default value
 *   'linkcolor' => '#dddddd',
 * );
 * perform substitutions on the css provided
 *
 * @param string $css CSS to substitute settings variables
 * @param object $theme Theme object
 * @param array $substitutions Array of settingname/defaultcolor pairs
 * @return string CSS with replacements
 */
function shezar_theme_generate_autocolors($css, $theme, $substitutions) {

    // each element added here will generate a new color
    // with the key appended to the existing setting name
    // and with the color passed through the function with the arguments
    // supplied via an array:
    $autosettings = array(
        'lighter' => array('brightness_linear', 15),
        'darker' => array('brightness_linear', -15),
        'light' => array('brightness_linear', 25),
        'dark' => array('brightness_linear', -25),
        'lighter-perc' => array('brightness', 15),
        'darker-perc' => array('brightness', -15),
        'light-perc' => array('brightness', 25),
        'dark-perc' => array('brightness', -25),
        'readable-text' => array('readable_text'),
        'readable-text-shadow' => array('readable_text_shadow'),
    );

    $find = array();
    $replace = array();
    foreach ($substitutions as $setting => $defaultcolor) {
        $value = isset($theme->settings->$setting) ? $theme->settings->$setting : $defaultcolor;
        if (substr($value, 0, 1) == '#') {
            $find[] = "[[setting:{$setting}]]";
            $replace[] = $value;

            foreach ($autosettings as $suffix => $modification) {
                if (!is_array($modification) || count($modification) < 1) {
                    continue;
                }
                $function_name = 'shezar_' . array_shift($modification);
                $function_args = $modification;
                array_unshift($function_args, $value);

                $find[] = "[[setting:{$setting}-$suffix]]";
                $replace[] = call_user_func_array($function_name, $function_args);
            }
        }

    }
    if (isset($theme->settings->headerbgc)) {
        $find[] = "[[setting:heading-on-headerbgc]]";
        $replace[] = (shezar_readable_text($theme->settings->headerbgc) == '#000000' ? '#444444' : '#b3b3b3');

        $find[] = "[[setting:text-on-headerbgc]]";
        $replace[] = (shezar_readable_text($theme->settings->headerbgc) == '#000000' ? '#444444' : '#cccccc');
    }
    return str_replace($find, $replace, $css);
}

/**
 * Encrypt any string using shezar public key
 *
 * @param string $plaintext
 * @param string $key Public key If not set shezar public key will be used
 * @return string Encrypted data
 */
function encrypt_data($plaintext, $key = '') {
    global $CFG;
    require_once($CFG->dirroot . '/shezar/core/lib/phpseclib/Crypt/RSA.php');
    require_once($CFG->dirroot . '/shezar/core/lib/phpseclib/Crypt/Hash.php');
    require_once($CFG->dirroot . '/shezar/core/lib/phpseclib/Crypt/Random.php');
    require_once($CFG->dirroot . '/shezar/core/lib/phpseclib/Math/BigInteger.php');

    $rsa = new \phpseclib\Crypt\RSA();
    if ($key === '') {
        $key = file_get_contents(PUBLIC_KEY_PATH);
    }
    if (!$key) {
        return false;
    }
    $rsa->loadKey($key);
    $rsa->setEncryptionMode(\phpseclib\Crypt\RSA::ENCRYPTION_PKCS1);
    $ciphertext = $rsa->encrypt($plaintext);
    return $ciphertext;
}

/**
 * Get course/program icon for displaying in course/program page.
 *
 * @param $instanceid
 * @return string icon URL.
 */
function shezar_get_icon($instanceid, $icontype) {
    global $DB, $OUTPUT, $PAGE;

    $component = 'shezar_core';
    $urlicon = '';

    if ($icontype == shezar_ICON_TYPE_COURSE) {
        $icon = $DB->get_field('course', 'icon', array('id' => $instanceid));
    } else {
        $icon = $DB->get_field('prog', 'icon', array('id' => $instanceid));
    }

    if ($customicon = $DB->get_record('files', array('pathnamehash' => $icon))) {
        $fs = get_file_storage();
        $context = context_system::instance();
        if ($file = $fs->get_file($context->id, $component, $icontype, $customicon->itemid, '/', $customicon->filename)) {
            $urlicon = moodle_url::make_pluginfile_url($file->get_contextid(), $component,
                $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $customicon->filename, true);
        }
    }

    if (empty($urlicon)) {
        $iconpath = $icontype . 'icons/';
        $imagelocation = $PAGE->theme->resolve_image_location($iconpath . $icon, $component);
        if (empty($icon) || empty($imagelocation)) {
            $icon = 'default';
        }
        $urlicon = $OUTPUT->pix_url('/' . $iconpath . $icon, $component);
    }

    return $urlicon->out();
}

/**
 * Determine if the current request is an ajax request
 *
 * @param array $server A $_SERVER array
 * @return boolean
 */
function is_ajax_request($server) {
    return (isset($server['HTTP_X_REQUESTED_WITH']) && strtolower($server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
}

/**
 * shezar specific initialisation
 * Currently needed only for AJAX scripts
 * Caution: Think before change to avoid conflict with other $CFG->moodlepageclass affecting code (for example installation scripts)
 */
function shezar_setup() {
    global $CFG;
    if (is_ajax_request($_SERVER)) {
        $CFG->moodlepageclassfile = $CFG->dirroot.'/shezar/core/pagelib.php';
        $CFG->moodlepageclass = 'shezar_page';
    }
}

/**
 * Checks if idnumber already exists.
 * Used when adding new or updating exisiting records.
 *
 * @param string $table Name of the table
 * @param string $idnumber IDnumber to check
 * @param int $itemid Item id. Zero value means new item.
 *
 * @return bool True if idnumber already exists
 */
function shezar_idnumber_exists($table, $idnumber, $itemid = 0) {
    global $DB;

    if (!$itemid) {
        $duplicate = $DB->record_exists($table, array('idnumber' => $idnumber));
    } else {
        $duplicate = $DB->record_exists_select($table, 'idnumber = :idnumber AND id != :itemid',
                                               array('idnumber' => $idnumber, 'itemid' => $itemid));
    }

    return $duplicate;
}

/**
 * List of strings which can be used with 'shezar_feature_*() functions'.
 *
 * Update this list if you add/remove settings in admin/settings/subsystems.php.
 *
 * @return array Array of strings of supported features (should have a matching "enable{$feature}" config setting).
 */
function shezar_advanced_features_list() {
    return array(
        'goals',
        'competencies',
        'appraisals',
        'feedback360',
        'learningplans',
        'programs',
        'certifications',
        'shezardashboard',
        'reportgraphs',
        'myteam',
        'recordoflearning',
        'positions',
    );
}

/**
 * Check the state of a particular shezar feature against the specified state.
 *
 * Used by the shezar_feature_*() functions to see if some shezar functionality is visible/hidden/disabled.
 *
 * @param string $feature Name of the feature to check, must match options from {@link shezar_advanced_features_list()}.
 * @param integer $stateconstant State to check, must match one of shezar_*FEATURE constants defined in this file.
 * @return bool True if the feature's config setting is in the specified state.
 */
function shezar_feature_check_state($feature, $stateconstant) {
    global $CFG;

    if (!in_array($feature, shezar_advanced_features_list())) {
        throw new coding_exception("'{$feature}' not supported by shezar feature checking code.");
    }

    $cfgsetting = "enable{$feature}";
    return (isset($CFG->$cfgsetting) && $CFG->$cfgsetting == $stateconstant);
}

/**
 * Check to see if a feature is set to be visible in Advanced Features
 *
 * @param string $feature The name of the feature from the list in {@link shezar_feature_check_support()}.
 * @return bool True if the feature is set to be visible.
 */
function shezar_feature_visible($feature) {
    return shezar_feature_check_state($feature, shezar_SHOWFEATURE);
}

/**
 * Check to see if a feature is set to be disabled in Advanced Features
 *
 * @param string $feature The name of the feature from the list in {@link shezar_feature_check_support()}.
 * @return bool True if the feature is disabled.
 */
function shezar_feature_disabled($feature) {
    return shezar_feature_check_state($feature, shezar_DISABLEFEATURE);
}

/**
 * Check to see if a feature is set to be hidden in Advanced Features
 *
 * @param string $feature The name of the feature from the list in {@link shezar_feature_check_support()}.
 * @return bool True if the feature is hidden.
 */
function shezar_feature_hidden($feature) {
    return shezar_feature_check_state($feature, shezar_HIDEFEATURE);
}

/**
 * A centralised location for getting all name fields. Returns an array or sql string snippet.
 * Moodle's get_all_user_name_fields function is faulty - it ignores the $tableprefix and $fieldprefix
 * when $returnsql is false. This wrapper function uses get_all_user_name_fields to get the list of fields,
 * then applies the given parameters to the raw list.
 *
 * @param bool $returnsql True for an sql select field snippet.
 * @param string $tableprefix table query prefix to use in front of each field.
 * @param string $prefix prefix added to the name fields e.g. authorfirstname.
 * @param string $fieldprefix sql field prefix e.g. id AS userid.
 * @param bool $onlyused true to only return the fields used by fullname() (and sorted as they appear)
 * @return array|string All name fields.
 */
function shezar_get_all_user_name_fields($returnsql = false, $tableprefix = null, $prefix = null, $fieldprefix = null, $onlyused = false) {
    global $CFG, $SESSION;

    $fields = get_all_user_name_fields();

    // Find the fields that are used by fullname() and sort them as they would appear.
    if ($onlyused) {
        // Get the setting for user name display format.
        if (!empty($SESSION->fullnamedisplay)) {
            $CFG->fullnamedisplay = $SESSION->fullnamedisplay;
        }
        $fullnamedisplay = $CFG->fullnamedisplay;

        // Find the fields that are used.
        $usedfields = array();
        foreach ($fields as $field) {
            $posfound = strpos($fullnamedisplay, $field);
            if ($posfound !== false) {
                $usedfields[$posfound] = $field;
            }
        }

        // Sorts the fields.
        ksort($usedfields);
        $fields = $usedfields;

        // Make sure that something is returned.
        if (empty($fields)) {
            $fields = array('firstname', 'lastname');
        }
    }

    // Add the prefix if provided.
    if ($prefix) {
        foreach ($fields as $key => $field) {
            $fields[$key] = $prefix . $field;
        }
    }

    if ($tableprefix) {
        $tableprefix = $tableprefix . ".";
    }

    // Add the tableprefix and fieldprefix and set up the sql. Do this even if tableprefix, fieldprefix and
    // returnsql are all unused, as this will set the correct array keys (field aliases).
    $prefixedfields = array();
    foreach ($fields as $field) {
        if ($returnsql && $fieldprefix) {
            $prefixedfields[$fieldprefix . $field] = $tableprefix . $field . ' AS ' . $fieldprefix . $field;
        } else {
            $prefixedfields[$fieldprefix . $field] = $tableprefix . $field;
        }
    }

    if ($returnsql) {
        return implode(',', $prefixedfields);
    } else {
        return $prefixedfields;
    }
}

/**
 * SQL concat ready option of shezar_get_all_user_name_fields function
 * This function return null-safe field names for concatentation into one field using $DB->sql_concat_join()
 *
 * @param string $tableprefix table query prefix to use in front of each field.
 * @param string $prefix prefix added to the name fields e.g. authorfirstname.
 * @param bool $onlyused true to only return the fields used by fullname() (and sorted as they appear)
 * @return array|string All name fields.
 */
function shezar_get_all_user_name_fields_join($tableprefix = null, $prefix = null, $onlyused = false) {
    $fields = shezar_get_all_user_name_fields(false, $tableprefix, $prefix, null, $onlyused);
    foreach($fields as $key => $field) {
        $fields[$key] = "COALESCE($field,'')";
    }
    return $fields;
}

/**
 * Creates a unique value within given table column
 *
 * @param string $table The database table.
 * @param string $column The database column to search within for uniqueness.
 * @param string $prefix A prefix to the name.
 * @return string a unique sha1
 */
function shezar_core_generate_unique_db_value($table, $column, $prefix = null) {
    global $DB;
    $exists = true;
    $name = null;
    while ($exists) {
        $name = sha1(uniqid(rand(), true));
        if ($prefix) {
            $name = $prefix . '_' . $name;
        }
        $exists = $DB->record_exists($table, array($column => $name));
    }
    return $name;
}
