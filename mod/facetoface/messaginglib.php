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
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @author Aaron Barnes <aaron.barnes@shezarlms.com>
 * @author Francois Marier <francois@catalyst.net.nz>
 * @package modules
 * @subpackage facetoface
 */
defined('MOODLE_INTERNAL') || die();


/**
 * Return list of marked submissions that have not been mailed out for currently enrolled students
 */
function facetoface_get_unmailed_reminders() {
    global $CFG, $DB;

    $submissions = $DB->get_records_sql("
        SELECT
            su.*,
            f.course,
            f.id as facetofaceid,
            f.name as facetofacename,
            se.normalcost,
            se.discountcost,
            se.details
        FROM {facetoface_signups} su
        INNER JOIN {facetoface_signups_status} sus ON su.id = sus.signupid AND sus.superceded = 0 AND sus.statuscode = ?
        JOIN {facetoface_sessions} se ON su.sessionid = se.id
        JOIN {facetoface} f ON se.facetoface = f.id
        JOIN (
            SELECT DISTINCT sessionid FROM {facetoface_sessions_dates}
        ) dates ON dates.sessionid = se.id
        WHERE
            su.mailedreminder = 0
    ", array(MDL_F2F_STATUS_BOOKED));

    if ($submissions) {
        foreach ($submissions as $key => $value) {
            $submissions[$key]->sessiondates = facetoface_get_session_dates($value->sessionid);
        }
    }

    return $submissions;
}


/**
 * Returns the ICAL data for a facetoface meeting.
 *
 * @param integer $method The method, @see {{MDL_F2F_INVITE}}
 * @param stdClass $facetoface instance
 * @param stdClass $session instance
 * @param stdClass $user instance
 * @param array $olddates previous session dates
 * @param int $onedate Provide ical attachment only for one specified date
 * @return stdClass Object that contains a filename in dataroot directory and ical template
 */
function facetoface_get_ical_attachment($method, $facetoface, $session, $user, array $olddates = array(), $onedate = -1) {
    global $CFG, $DB;

    // Get user object if only id is given
    $user = (is_object($user) ? $user : $DB->get_record('user', array('id' => $user)));

    // Handle a lack of session dates gracefully, there should atleast be an empty record.
    if (empty($session->sessiondates)) {
        $session->sessiondates = $DB->get_records('facetoface_sessions_dates', array('sessionid' => $session->id), 'timestart');
    }

    $icalmethod = ($method & MDL_F2F_INVITE) ? 'REQUEST' : 'CANCEL';

    // First, generate all the VEVENT blocks
    $VEVENTS = '';
    $rooms = facetoface_get_session_rooms($session->id);
    $newdates = empty($session->sessiondates) ? array() : $session->sessiondates;
    $maxdates = max(count($newdates), count($olddates));
    if ($maxdates == 0) {
        return null;
    }

    // It is important to sort dates by id, becuase sequence number depends on it.
    uasort($olddates, function($a, $b) {
        if ($a->id == $b->id) {
            return 0;
        }
        return ($a->id < $b->id) ? -1 : 1;
    });
    uasort($newdates, function($a, $b) {
        if ($a->id == $b->id) {
            return 0;
        }
        return ($a->id < $b->id) ? -1 : 1;
    });


    // Count user signup changes.
    $sql = "SELECT COUNT(*)
        FROM {facetoface_signups} su
        INNER JOIN {facetoface_signups_status} sus ON su.id = sus.signupid
        WHERE su.userid = ?
            AND su.sessionid = ?
            AND sus.superceded = 1";
    $params = array($user->id, $session->id, MDL_F2F_STATUS_USER_CANCELLED);
    $usercnt = $DB->count_records_sql($sql, $params);

    for ($i = 0; $i < $maxdates; $i++) {
        // Take dates.
        $newdate = null;
        $olddate = null;
        if (!empty($newdates)) {
            $newdate = array_shift($newdates);
        }
        if(!empty($olddates)) {
            $olddate = array_shift($olddates);
        }

        // Skip if we need only one date and this is not that date.
        if ($onedate >= 0 && $onedate != $i) {
            continue;
        }

        // This is possible only when $olddates are larger than $newdates. Cancel extra dates.
        if (is_null($newdate)) {
            $date = $olddate;
            // Cancel all the rest.
            $method = MDL_F2F_CANCEL;
            // So we need to increase sequnce without increasing date id or signup count,
            // but not make it equal or larger than next increase.
            $SEQUENCE = ($date->id + $usercnt) * 2 + 1;
        } else {
            $date = $newdate;
            // This will allow to increase sequence in both cases: when status changes for individual user
            // and when date changes for all.
            $SEQUENCE = ($date->id + $usercnt) * 2;
        }

        // Date that this representation of the calendar information was created -
        // we use the time the session was created
        // http://www.kanzaki.com/docs/ical/dtstamp.html
        $DTSTAMP = facetoface_ical_generate_timestamp($session->timecreated);

        // UIDs should be globally unique
        $urlbits = parse_url($CFG->wwwroot);

        $UID =
            $DTSTAMP .
            '-' . substr(md5($CFG->siteidentifier . $session->id . $user->id), -8) . // Unique identifier, salted with site identifier
            '-' . $i .
            '@' . $urlbits['host']; // Hostname for this moodle installation

        $DTSTART = facetoface_ical_generate_timestamp($date->timestart);
        $DTEND   = facetoface_ical_generate_timestamp($date->timefinish);

        $SUMMARY     = str_replace("\\n", "\\n ", facetoface_ical_escape($facetoface->name, true));
        $icaldescription = get_string('icaldescription', 'facetoface', $facetoface);
        if (!empty($session->details)) {
            $icaldescription .= $session->details;
        }
        $DESCRIPTION = facetoface_ical_escape($icaldescription, true);

        // Get the location data from custom fields if they exist.
        $locationstring = '';
        $delimiter = get_string('icallocationstringdelimiter', 'facetoface');
        if (!empty($date->roomid) && isset($rooms[$date->roomid])) {
            $room = $rooms[$date->roomid];
            if (!empty($room->name)) {
                $locationstring .= $room->name;
            }
            if (!empty($room->customfield_building)) {
                if (!empty($locationstring)) {
                    $locationstring .= $delimiter."\n";
                }
                $locationstring .= $room->customfield_building;
            }
            if (!empty($room->customfield_location->address)) {
                if (!empty($locationstring)) {
                    $locationstring .= $delimiter."\n";
                }
                $locationstring .= $room->customfield_location->address;
            }
        }
        // NOTE: Newlines are meant to be encoded with the literal sequence
        // '\n'. But evolution presents a single line text field for location,
        // and shows the newlines as [0x0A] junk. So we switch it for commas
        // here. Remember commas need to be escaped too.
        $locationstring = str_replace('\n', $delimiter, facetoface_ical_escape($locationstring, true));
        // Possibility of multiple commas, replaced with the single one.
        $pattern = "/{$delimiter}+/";
        $LOCATION = preg_replace($pattern, $delimiter, $locationstring);

        $ORGANISEREMAIL = $CFG->facetoface_fromaddress;

        $ROLE = 'REQ-PARTICIPANT';
        $CANCELSTATUS = '';
        if ($method & MDL_F2F_CANCEL) {
            $ROLE = 'NON-PARTICIPANT';
            $CANCELSTATUS = "\nSTATUS:CANCELLED";
        }

        // FIXME: if the user has input their name in another language, we need
        // to set the LANGUAGE property parameter here
        $USERNAME = fullname($user);
        $MAILTO   = $user->email;

        $VEVENTS .= "BEGIN:VEVENT\r\n";
        $VEVENTS .= "ORGANIZER;CN={$ORGANISEREMAIL}:MAILTO:{$ORGANISEREMAIL}\r\n";
        $VEVENTS .= "DTSTART:{$DTSTART}\r\n";
        $VEVENTS .= "DTEND:{$DTEND}\r\n";
        $VEVENTS .= "LOCATION:{$LOCATION}\r\n";
        $VEVENTS .= "TRANSP:OPAQUE{$CANCELSTATUS}\r\n";
        $VEVENTS .= "SEQUENCE:{$SEQUENCE}\r\n";
        $VEVENTS .= "UID:{$UID}\r\n";
        $VEVENTS .= "DTSTAMP:{$DTSTAMP}\r\n";
        $VEVENTS .= "DESCRIPTION:{$DESCRIPTION}\r\n";
        $VEVENTS .= "SUMMARY:{$SUMMARY}\r\n";
        $VEVENTS .= "PRIORITY:5\r\n";
        $VEVENTS .= "CLASS:PUBLIC\r\n";
        $VEVENTS .= "ATTENDEE;CUTYPE=INDIVIDUAL;ROLE={$ROLE};PARTSTAT=NEEDS-ACTION;\r\n";
        $VEVENTS .= " RSVP=FALSE;CN={$USERNAME};LANGUAGE=en:MAILTO:{$MAILTO}\r\n";
        $VEVENTS .= "END:VEVENT\r\n";
    }

    $template  = "BEGIN:VCALENDAR\r\n";
    $template .= "VERSION:2.0\r\n";
    $template .= "PRODID:-//Moodle//NONSGML Facetoface//EN\r\n";
    $template .= "METHOD:{$icalmethod}\r\n";
    $template .= "{$VEVENTS}";
    $template .= "END:VCALENDAR\r\n";

    // TODO: this is stolen from file_get_unused_draft_itemid(), replace once messaging accepts real files or strings.
    $contextid = context_user::instance($user->id)->id;
    $fs = get_file_storage();
    $draftitemid = rand(1, 999999999);
    while ($files = $fs->get_area_files($contextid, 'user', 'draft', $draftitemid)) {
        $draftitemid = rand(1, 999999999);
    }
    // TODO: let's just fake the draft area here because it will get automatically cleanup up later in cron if necessary.
    $file = $fs->create_file_from_string(
        array('contextid' => $contextid, 'component' => 'user', 'filearea' => 'draft',
              'itemid' => $draftitemid, 'filepath' => '/', 'filename' => 'ical.ics'),
        $template
    );

    $ical = new stdClass();
    $ical->file = $file;
    $ical->content = $template;
    return $ical;
}


/**
 * Used by facetoface_get_ical_attachment
 * @seconds string signed number, e.g. -343242 or +343242
 * Convert no. of seconds to hhmmss format
 */
function facetoface_format_secs_to_his($seconds) {
    if ( '-' == substr($seconds, 0, 1)) {
        $prefix  = '-';
        $seconds = substr($seconds, 1);
    } else if ( '+' == substr($seconds, 0, 1)) {
        $prefix  = '+';
        $seconds = substr($seconds, 1);
    } else {
        $prefix  = '+';
    }

    $output = '';
    $hour = (int)floor($seconds/3600);
    if (10 > $hour) {
      $hour  = '0'.$hour;
    }

    $seconds = $seconds % 3600;

    $min = (int)floor($seconds/60);
    if (10 > $min) {
      $min = '0'.$min;
    }

    $output  = $hour.$min;
    $seconds = $seconds % 60;
    if (0 < $seconds) {
        if (9 < $seconds) {
            $output .= $seconds;
        } else {
            $output .= '0'.$seconds;
        }
    }

    return $prefix.$output;
}


/**
 * Generates a timestamp for Ical
 *
 */
function facetoface_ical_generate_timestamp($timestamp) {
    return gmdate('Ymd', $timestamp) . 'T' . gmdate('His', $timestamp) . 'Z';
}


/**
 * Escapes data of the text datatype in ICAL documents.
 *
 * See RFC2445 or http://www.kanzaki.com/docs/ical/text.html or a more readable definition
 */
function facetoface_ical_escape($text, $converthtml=false) {
    if (empty($text)) {
        return '';
    }

    if ($converthtml) {
        $text = html_to_text($text, 0);
    }

    $text = str_replace(
        array('\\',   "\n", ';',  ',', '"'),
        array('\\\\', '\n', '\;', '\,', '\"'),
        $text
    );

    // Text should be wordwrapped at 75 octets, and there should be one
    // whitespace after the newline that does the wrapping.
    // More info: http://tools.ietf.org/html/rfc5545#section-3.1
    // For spacing issues see http://php.net/wordwrap#52532
    $text = str_replace(' ', chr(26), $text);
    $text = wordwrap($text, 74, "\r\n\t", true);
    $text = str_replace(chr(26), ' ', $text);

    return $text;
}
