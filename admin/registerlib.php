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
 * @subpackage core
 *
 * This file contains functions used by the registration pages
 */
defined('MOODLE_INTERNAL') || die();
define('SITE_REGISTRATION_EMAIL', 'registrations@shezarlms.com');

/**
 *  Collect information to be sent to register.shezarlms.com
 *
 *  @return array Associative array of data to return
 */
function get_registration_data() {
    global $CFG, $SITE, $DB;
    include($CFG->dirroot . '/version.php');
    require_once($CFG->libdir . '/environmentlib.php');
    require_once($CFG->libdir . '/badgeslib.php');
    $dbinfo = $DB->get_server_info();
    $db_version = normalize_version($dbinfo['version']);

    $data['siteidentifier'] = $CFG->siteidentifier;
    $data['wwwroot'] = $CFG->wwwroot;
    $data['siteshortname'] = $SITE->shortname;
    $data['sitefullname'] = $SITE->fullname;
    $data['orgname'] = $CFG->orgname;
    $data['techsupportphone'] = $CFG->techsupportphone;
    $data['techsupportemail'] = $CFG->techsupportemail;
    $data['moodlerelease'] = $CFG->release;
    $data['shezarversion'] = $shezar->version;
    $data['shezarbuild'] = $shezar->build;
    $data['shezarrelease'] = $shezar->release;
    $data['phpversion'] = phpversion();
    $data['dbtype'] = $CFG->dbfamily . ' ' . $db_version;
    $data['webserversoftware'] = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';
    $data['usercount'] = $DB->count_records('user', array('deleted' => '0'));
    $data['coursecount'] = $DB->count_records_select('course', 'format <> ?', array('site'));
    $oneyearago = time() - 60*60*24*365;
    // See MDL-22481 for why currentlogin is used instead of lastlogin
    $data['activeusercount'] = $DB->count_records_select('user', "currentlogin > ?", array($oneyearago));
    $data['badgesnumber'] = $DB->count_records_select('badge', 'status <> ' . BADGE_STATUS_ARCHIVED);
    $data['issuedbadgesnumber'] = $DB->count_records('badge_issued');
    $data['debugstatus'] = (isset($CFG->debug) ? $CFG->debug : DEBUG_NONE);
    $data['edition'] = 2;
    return $data;
}

/**
 * Send registration data to shezarlms.com
 *
 * @param array $data Associative array of data to send
 */
function send_registration_data($data) {
    global $CFG;
    require_once($CFG->libdir . '/filelib.php');

    set_config('registrationattempted', time());

    $ch = new curl();
    $options = array(
            'FOLLOWLOCATION' => true,
            'RETURNTRANSFER' => true, // RETURN THE CONTENTS OF THE CALL
            'SSL_VERIFYPEER' => true,
            'SSL_VERIFYHOST' => 2,
            'HEADER' => 0 // DO NOT RETURN HTTP HEADERS
    );

    // Send registration data directly via curl.
    $recdata = $ch->post('https://register.shezarlms.com/register/report.php', $data, $options);
    if ($recdata === '') {
        set_config('registered', time());
        return;
    }

    // Fall back to email notification.
    $recdata = send_registration_data_email($data);
    if ($recdata === true) {
        set_config('registered', time());
    }

}

/**
 * Send registration data to shezarlms.com using curl
 *
 * @param array $data Associative array of data to send
 * @return bool Result of operation
 */
function send_registration_data_email($data) {
    global $CFG;

    $options = defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0;
    $sdata = json_encode($data, $options);
    $encrypted = encrypt_data($sdata);

    $attachment = md5('register' . microtime(true));
    $attachmentpath = $CFG->dataroot . '/' . $attachment;
    file_put_contents($attachmentpath, $encrypted);

    $attachmentfilename = 'site_registration.ttr';
    $subject = "[SITE REGISTRATION] Site: " . $data['sitefullname'];
    $message = get_string('siteregistrationemailbody', 'shezar_core', $data['sitefullname']);
    $fromaddress = $CFG->noreplyaddress;

    $touser = \shezar_core\shezar_user::get_external_user(SITE_REGISTRATION_EMAIL);
    $emailed = email_to_user($touser, $fromaddress, $subject, $message, '', $attachment, $attachmentfilename);

    if (!unlink($attachmentpath)) {
        mtrace(get_string('error:failedtoremovetempfile', 'shezar_reportbuilder'));
    }

    return $emailed;
}