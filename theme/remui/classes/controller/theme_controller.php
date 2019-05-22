<?php
// This file is part of remUI Moodle theme.
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
 * This is built using the bootstrapbase template to allow for new theme's using
 * Moodle's new Bootstrap theme engine
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_remui\controller;

use moodle_url;
use coursecat;
use coursecat_helper;

use user_picture;
use context_course;
use context_system;
use user_forums;

require_once(__DIR__.'/../user_forums.php');
require_once(__DIR__.'/../activity.php');
require_once($CFG->dirroot.'/calendar/lib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/coursecatlib.php');
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/grade/report/user/lib.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/grade/querylib.php');

class theme_controller
{
    /**
     * This function is used to get the data for either slider or static at a time.
     *
     * @return array of sliding data
     */
    public static function slider_data() {
        global $PAGE, $OUTPUT;
        $sliderdata = array();
        $sliderdata['isslider'] = false;
        $sliderdata['isvideo']  = false;
        if (get_config('theme_remui', 'sliderautoplay') == 1) {
            $sliderdata['slideinterval'] = get_config('theme_remui', 'slideinterval');
        } else {
            $sliderdata['slideinterval'] = "false";
        };
        $numberofsliders = get_config('theme_remui', 'slidercount');
        // Get the content details either static or slider.
        $frontpagecontenttype = get_config('theme_remui', 'frontpageimagecontent');

        if ($frontpagecontenttype === "1") { // Dynamic image slider.
            $sliderdata['isslider'] = true;
            if ($numberofsliders >= 1) {
                for ($count = 1; $count <= $numberofsliders; $count++) {
                    $sliderimageurl = $PAGE->theme->setting_file_url('slideimage'.$count, 'slideimage'.$count);
                    if ($sliderimageurl == "" || $sliderimageurl == null) {
                        $sliderimageurl = $OUTPUT->pix_url('slide', 'theme');
                    }
                    $sliderimagetext = get_config('theme_remui', 'slidertext'.$count);
                    $sliderimagelink = get_config('theme_remui', 'sliderurl'.$count);
                    $sliderbuttontext = get_config('theme_remui', 'sliderbuttontext'.$count);
                    if ($count == 1) {
                        $active = true;
                    } else {
                        $active = false;
                    }
                    $sliderdata['slides'][] = array(
                    'img' => $sliderimageurl,
                    'img_txt' => $sliderimagetext,
                    'btn_link' => $sliderimagelink,
                    'btn_txt' => $sliderbuttontext,
                    'active' => $active,
                    'img_count' => $count - 1);
                }
            }
        } else if ($frontpagecontenttype === "0") { // Static data.
            // Get the static front page settings
            $sliderdata['addtxt'] = get_config('theme_remui', 'addtext');
            $sliderdata['addlink'] = get_config('theme_remui', 'addlink');
            $contenttype = get_config('theme_remui', 'contenttype');
            if ($contenttype === "0") {
                $sliderdata['isvideo'] = true;
                $sliderdata['video'] = get_config('theme_remui', 'video');
                $sliderdata['videoalignment'] = get_config('theme_remui', 'frontpagevideoalignment');
            } else if ($contenttype === "1") {
                $staticimage = $PAGE->theme->setting_file_url('staticimage', 'staticimage');
                if ($staticimage == "" || $staticimage == null) {
                    $sliderdata['staticimage'] = $OUTPUT->pix_url('slide', 'theme');
                } else {
                    $sliderdata['staticimage'] = $staticimage;
                }
            }
        }
        return $sliderdata;
    }

    /* This function will get the featured coursce .*/
    public static function get_courses($search=null, $category=null, $limitfrom=0, $limitto=0) {
        
        global $DB, $CFG;

        require_once($CFG->libdir. '/coursecatlib.php');

        $coursedetailsarray = array();
        $where = '';
        if ( !empty($seessentialarch)) {
                 $where .= " AND fullname like '%$search%' ";
        }
        if (!empty($category)) {
                $where .= " AND category ='$category' ";
        }
        if ($where == '') {
            $course = $DB->get_records_sql('SELECT c.* FROM {course} c where id != ? ', array(1), $limitfrom, $limitto);
        } else {
            $course = $DB->get_records_sql("SELECT c.* FROM {course} c where id != ? $where", array(1), $limitfrom, $limitto);
        }
            $count = 0;
        foreach ($course as $key => $coursevalue) {

            $chelper = new coursecat_helper();
            $course = new \course_in_list($coursevalue);

            $key = ""; // Just used for removing error
            $coursedetailsarray[$count]["courseid"] = $coursevalue->id;
            $coursedetailsarray[$count]["courselink"] = $CFG->wwwroot."/course/view.php?id=".$coursevalue->id;
            $coursedetailsarray[$count]["enroledusers"] = $CFG->wwwroot."/enrol/users.php?id=".$coursevalue->id;
            $coursedetailsarray[$count]["editcourse"] = $CFG->wwwroot."/course/edit.php?id=".$coursevalue->id;
            $coursedetailsarray[$count]["grader"] = $CFG->wwwroot."/grade/report/grader/index.php?id=".$coursevalue->id;
            $coursedetailsarray[$count]["activity"] = $CFG->wwwroot."/report/outline/index.php?id=".$coursevalue->id;
            $coursedetailsarray[$count]["coursename"] = $coursevalue->fullname;
            $coursesummary = strip_tags($chelper->get_course_formatted_summary($course,
                    array('overflowdiv' => false, 'noclean' => false, 'para' => false)));
            $summarystring = strlen($coursesummary) > 100 ? substr($coursesummary, 0, 100)."..." : $coursesummary;
            $coursedetailsarray[$count]["coursesummary"] = $summarystring;
            $coursedetailsarray[$count]["coursestartdate"] = $coursevalue->startdate;
            
            $course1 = get_course($coursevalue->id);
            if ($course1 instanceof \stdClass) {
                    $course1 = new \course_in_list($course1);
            }
            foreach ($course1->get_course_overviewfiles() as $file) {
                $isimage = $file->is_valid_image();
                $courseimage = file_encode_url("$CFG->wwwroot/pluginfile.php",
                                          '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                                          $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
                if (!$isimage) {
                    $courseimage = $CFG->wwwroot."/theme/remui/data/nopic.jpg";
                }

            }
            if (!empty($courseimage)) {
                $coursedetailsarray[$count]["courseimage"] = $courseimage;
            } else {
                $coursedetailsarray[$count]["courseimage"] = $CFG->wwwroot."/theme/remui/data/nopic.jpg";
            }
            $courseimage = '';

            $count++;
        }
         return $coursedetailsarray;
    }

    function eexternal_format_text($text, $textformat, $contextid, $component, $filearea, $itemid, $options = null) {
    global $CFG;

    // Get settings (singleton).
    $settings = external_settings::get_instance();

    if ($settings->get_fileurl()) {
        require_once($CFG->libdir . "/filelib.php");
        $text = file_rewrite_pluginfile_urls($text, $settings->get_file(), $contextid, $component, $filearea, $itemid);
    }

    if (!$settings->get_raw()) {
        $options = (array)$options;

        // If context is passed in options, check that is the same to show a debug message.
        if (isset($options['context'])) {
            if ((is_object($options['context']) && $options['context']->id != $contextid)
                    || (!is_object($options['context']) && $options['context'] != $contextid)) {
                debugging('Different contexts found in external_format_text parameters. $options[\'context\'] not allowed.
                    Using $contextid parameter...', DEBUG_DEVELOPER);
            }
        }

        $options['filter'] = isset($options['filter']) && !$options['filter'] ? false : $settings->get_filter();
        $options['para'] = isset($options['para']) ? $options['para'] : false;
        $options['context'] = context::instance_by_id($contextid);
        $options['allowid'] = isset($options['allowid']) ? $options['allowid'] : true;

        $text = format_text($text, $textformat, $options);
        $textformat = FORMAT_HTML; // Once converted to html (from markdown, plain... lets inform consumer this is already HTML).
    }

    return array($text, $textformat);
}


    // get any partial
    public static function get_partial_element($elementname) {
        global $CFG;
        $themename = $CFG->theme;
        $elementname .= '.php';
        if (file_exists("$CFG->dirroot/theme/$themename/layout/partials/$elementname")) {
            return "$CFG->dirroot/theme/$themename/layout/partials/$elementname";
        } else if (file_exists("$CFG->dirroot/theme/remui/layout/partials/$elementname")) {
            return "$CFG->dirroot/theme/remui/layout/partials/$elementname";
        } else if (!empty($CFG->themedir) && file_exists("$CFG->themedir/remui/layout/partials/$elementname")) {
            return "$CFG->themedir/remui/layout/partials/$elementname";
        }
    }

    // get user profile pic link
    public static function get_user_image_link($userid, $imgsize) {
        global $USER;
        if (!$userid) {
            $userid = $USER->id;
        }
        global $DB, $PAGE;
        $user = $DB->get_record('user', array('id' => $userid));
        $userimg = new user_picture($user);
        $userimg->size = $imgsize;
        return  $userimg->get_url($PAGE);
    }

    public static function get_time_format($time) {
        if ($time >= 31536000) {
            return intval($time / 31536000) . " " .  get_string('years', 'theme_remui');
        } else if ($time >= 2592000) {
            return intval($time / 2592000) . " " . get_string('months', 'theme_remui');;
        } else if ($time >= 86400) {
            return intval($time / 86400) . " " . get_string('days', 'theme_remui');
        } else if ($time >= 3600) {
            return intval($time / 3600) . " " . get_string('hours', 'theme_remui');
        } else {
            return intval($time / 60) . " " . get_string('mins', 'theme_remui');
        }
    }

    public static function get_events() {
        global $CFG;

        require_once($CFG->dirroot.'/calendar/lib.php');

        $filtercourse    = array();
        // Being displayed at site level. This will cause the filter to fall back to auto-detecting
        // the list of courses it will be grabbing events from.
        $filtercourse = calendar_get_default_courses();

        list($courses, $group, $user) = calendar_set_filters($filtercourse);

        $defaultlookahead = CALENDAR_DEFAULT_UPCOMING_LOOKAHEAD;
        if (isset($CFG->calendar_lookahead)) {
            $defaultlookahead = intval($CFG->calendar_lookahead);
        }
        $lookahead = get_user_preferences('calendar_lookahead', $defaultlookahead);
        // echo $lookahead;
        $defaultmaxevents = CALENDAR_DEFAULT_UPCOMING_MAXEVENTS;
        if (isset($CFG->calendar_maxevents)) {
            $defaultmaxevents = intval($CFG->calendar_maxevents);
        }
        $maxevents = get_user_preferences('calendar_maxevents', $defaultmaxevents);
        // echo $maxevents;
        $events = calendar_get_upcoming($courses, $group, $user, $lookahead, $maxevents);
        return $events;
    }

    // check whether user is authorized for viewing other user's description
    public static function get_user_description($user) {
        global $CFG, $DB, $USER;

        $userdescription = '';
        $currentuser = ($user->id == $USER->id);
        $context = \context_user::instance($user->id);
        $hiddenfields = array();
        $viewhiddenuserfields = has_capability('moodle/user:viewhiddendetails', $context);

        // add hidden fields in array
        if (!$viewhiddenuserfields) {
            $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
        }

        if ($user->description && !isset($hiddenfields['description'])) {
            if (!empty($CFG->profilesforenrolledusersonly) && !$currentuser &&
                !$DB->record_exists('role_assignments', array('userid' => $user->id))) {
                $userdescription = get_string('profilenotshown', 'moodle');
            } else {
                $user->description = file_rewrite_pluginfile_urls($user->description, 'pluginfile.php', $context->id, 'user', 'profile', null);
                $userdescription .= format_text($user->description, $user->descriptionformat);
            }
        }
        return $userdescription;
    }

    // Get the recently added users
    public static function get_recent_user() {
        global  $DB;
        $userdata = array();
        $limitfrom = 0;
        $limitto = 8;
        $users = $DB->get_records_sql('SELECT u.* FROM {user} u  WHERE u.deleted = 0 ORDER BY timecreated desc', array(1), $limitfrom, $limitto);
        $count = 0;
        foreach ($users as $value) {
            $date = date('d/m/Y', $value->timecreated);
            if ($date == date('d/m/Y')) {
                     $date = 'Today';
            } else if ($date == date('d/m/Y', time() - (24 * 60 * 60))) {
                 $date = 'Yesterday';
            } else {
                $date = date('jS F Y', $value->timecreated);
            }
             $userdata[$count]['img'] = self::get_user_image_link($value->id, 100);
             $userdata[$count]['name'] = $value->firstname .' '.$value->lastname;
             $userdata[$count]['register_date'] = $date;
             $userdata[$count]['id'] = $value->id;
            // echo $userpic;
            // echo $value->id ;
            // echo $value->email ;
            $count++;
        }
        return $userdata;
    }

    /**
     * Returns user object by passing user id.
     *
     * @param int $userid.
     * @return object user
     */
    public static function get_user($userorid = false) {
        global $USER, $DB;

        if ($userorid === false) {
            return $USER;
        }

        if (is_object($userorid)) {
            return $userorid;
        } else if (is_number($userorid)) {
            if (intval($userorid) === $USER->id) {
                $user = $USER;
            } else {
                $user = $DB->get_record('user', ['id' => $userorid]);
            }
        } else {
            throw new coding_exception(get_string('parametermustbeobjectorintegerorstring', 'theme_remui', $userorid));
        }

        return $user;
    }

    /**
     * Some moodle functions don't work correctly with specific userids and this provides a hacky workaround.
     *
     * Temporarily swaps global USER variable.
     * @param bool|stdClass|int $userorid
     */
    public static function swap_global_user($userorid = false) {
        global $USER;
        static $origuser = [];
        $user = self::get_user($userorid);
        if ($userorid !== false) {
            $origuser[] = $USER;
            $USER = $user;
        } else {
            $USER = array_pop($origuser);
        }
    }

    /**
     * Get deadlines string.
     * @return string
     */
    public static function deadlines() {
        global $USER;

        $events = self::upcoming_deadlines($USER->id);

        return $events;

    }

    /**
     * Return user's upcoming deadlines from the calendar.
     *
     * All deadlines from today, then any from the next 12 months up to the
     * max requested.
     * @param \stdClass|integer $userorid
     * @param integer $maxdeadlines
     * @return array
     */
    public static function upcoming_deadlines($userorid, $maxdeadlines = 5) {

        $user = self::get_user($userorid);
        if (!$user) {
            return [];
        }

        $courses = enrol_get_users_courses($user->id, true);

        if (empty($courses)) {
            return [];
        }

        $courseids = array_keys($courses);

        $events = self::get_todays_deadlines($user, $courseids);

        if (count($events) < $maxdeadlines) {
            $maxaftercurrentday = $maxdeadlines - count($events);
            $moreevents = self::get_upcoming_deadlines($user, $courseids, $maxaftercurrentday);
            $events = $events + $moreevents;
        }
        foreach ($events as $event) {
            if (isset($courses[$event->courseid])) {
                $course = $courses[$event->courseid];
                $event->coursefullname = $course->fullname;
            }
        }
        return $events;
    }

    /**
     * Return user's deadlines for today from the calendar.
     *
     * @param \stdClass|int $userorid
     * @param array $courses ids of all user's courses.
     * @return array
     */
    private static function get_todays_deadlines($userorid, $courses) {
        // Get all deadlines for today, assume that will never be higher than 100.
        return self::get_upcoming_deadlines($userorid, $courses, 100, true);
    }

    /**
     * Return user's deadlines from the calendar.
     *
     * Usually called twice, once for all deadlines from today, then any from the next 12 months up to the
     * max requested.
     *
     * Based on the calender function calendar_get_upcoming.
     *
     * @param \stdClass|int $userorid
     * @param array $courses ids of all user's courses.
     * @param int $maxevents to return
     * @param bool $todayonly true if only the next 24 hours to be returned
     * @return array
     */
    private static function get_upcoming_deadlines($userorid, $courses, $maxevents, $todayonly=false) {

        $user = self::get_user($userorid);
        if (!$user) {
            return [];
        }

        // We need to do this so that we can calendar events and mod visibility for a specific user.
        self::swap_global_user($user);

        $now = time();

        if ($todayonly === true) {
            $starttime = usergetmidnight($now);
            $daysinfuture = 1;
        } else {
            $starttime = usergetmidnight($now + DAYSECS + 3 * HOURSECS); // Avoid rare DST change issues.
            $daysinfuture = 365;
        }

        $endtime = $starttime + ($daysinfuture * DAYSECS) - 1;

        $userevents = false;
        $groupevents = false;
        $events = calendar_get_events($starttime, $endtime, $userevents, $groupevents, $courses);

        $processed = 0;
        $output = array();
        foreach ($events as $event) {
            if ($event->eventtype === 'course') {
                // Not an activity deadline.
                continue;
            }
            if (!empty($event->modulename)) {
                $modinfo = get_fast_modinfo($event->courseid);
                $mods = $modinfo->get_instances_of($event->modulename);
                if (isset($mods[$event->instance])) {
                    $cminfo = $mods[$event->instance];
                    if (!$cminfo->uservisible) {
                        continue;
                    }
                }
            }

            $output[$event->id] = $event;
            ++$processed;

            if ($processed >= $maxevents) {
                break;
            }
        }

        self::swap_global_user(false);

        return $output;
    }

    // Returns count of newly registered users
    public static function get_new_members_count($time) {
        global  $DB;
        $userscount = $DB->get_records_sql("SELECT COUNT(*) FROM {user} WHERE timecreated >= ?", array($time));
        $key = array_keys($userscount);

        return $key[0];
    }

    public static function get_active_members_count($time) {
        global $DB;
        $userscount = $DB->get_records_sql("SELECT COUNT(*) FROM {user} WHERE lastaccess >= ?", array($time));
        $key = array_keys($userscount);

        return $key[0];
    }

    /**
     * Extract first image from html
     *
     * @param int $contactid (Message to be sent to user).
     * @param string $message [Actual Message to be sent].
     * @return array | bool (false)
     */
    public function quickmessage($contactid, $message) {

        global $USER, $DB;

        $otheruserid = $contactid;
        $otheruserobj = $DB->get_record('user', array('id' => $otheruserid));
        $messagebody = $message;
        if (!empty($message) && !empty($otheruserobj)) {
            message_post_message($USER, $otheruserobj, $messagebody, FORMAT_MOODLE);
            return 'success';
        } else {
            return 'failed';
        }
    }

    /**
     * Gets list of courses where user has teacher role
     *
     * @return array with course id as index and course name as value.
     */
    public static function get_courses_add_activity($courseid) {

        if ($courseid === 1) {
            // Get list of all courses.
            $courses = get_courses();
            unset($courses[1]);
            $courselist = array();
            foreach ($courses as $course) {
                $coursecontext = context_course::instance($course->id);
                $hascapability = has_capability('moodle/course:manageactivities', $coursecontext);
                if ($hascapability) {
                    $courselist[$course->id] = $course->shortname;
                }
            }
            return $courselist;
        } else {
            $coursecontext = context_course::instance($courseid);
            $hascapability = has_capability('moodle/course:manageactivities', $coursecontext);
            if ($hascapability) {
                return $courselist[$courseid] = "has_capability";
            } else {
                return $courselist[$courseid] = "no_capability";
            }
        }
    }

    /**
     * Extract first image from html
     *
     * @param string $html (must be well formed)
     * @return array | bool (false)
     */
    public static function extract_first_image($html) {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true); // Required for HTML5.
        $doc->loadHTML($html);
        libxml_clear_errors(); // Required for HTML5.
        $imagetags = $doc->getElementsByTagName('img');
        if ($imagetags->item(0)) {
            $src = $imagetags->item(0)->getAttribute('src');
            $alt = $imagetags->item(0)->getAttribute('alt');
            return array('src' => $src, 'alt' => $alt);
        } else {
            return false;
        }
    }

    public static function get_course_firstimage($courseid) {
        $fs      = get_file_storage();
        $context = \context_course::instance($courseid);
        $files   = $fs->get_area_files($context->id, 'course', 'summary', false, 'filename', false);

        if (count($files) > 0) {
            foreach ($files as $file) {
                if ($file->is_valid_image()) {
                    return $file;
                }
            }
        }
        return false;
    }

    /**
     * Returns whether user has a role of a teacher or student.
     *
     * @return array
     */
    public static function check_user_course_role() {
        global $USER;
        // Get list of courses user is enrolled in.
        $courses = enrol_get_my_courses();
        $userrole = array();
        $userrole['teacher'] = 0;
        $userrole['student'] = 0;
        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            $roles = get_user_roles($context, $USER->id, false);
            $keys = array_keys($roles);
            // Check if user has teacher role.
            // 3 = Editing Teacher, 4 = Teacher.
            if ($roles && ($roles[$keys[0]]->roleid == 3 || $roles[$keys[0]]->roleid == 4)) {
                $userrole['teacher'] = 1;
            } else if ($roles && ($roles[$keys[0]]->roleid == 5)) {
                $userrole['student'] = 1;
            }
        }
        return $userrole;
    }

    public static function set_user_contact($otheruserid, $type) {
        if ($type === 'add') {
            return message_add_contact($otheruserid);
        } else if ($type === 'remove') {
            return message_remove_contact($otheruserid);
        } else if ($type === 'block') {
            return message_block_contact($otheruserid);
        } else if ($type === 'unblock') {
            return message_unblock_contact($otheruserid);
        }
    }

    /**
     * Get course completion progress for specific course.
     * NOTE: It is by design that even teachers get course completion progress, this is so that they see exactly the
     * same as a student would in the personal menu.
     *
     * @param $course
     * @return stdClass | null
     */
    public static function course_completion_progress($course, $userid = null) {
        global $USER;
        if (!isloggedin() || isguestuser()) {
            return null; // Can't get completion progress for users who aren't logged in.
        }
        if (!$userid){
            $userid = $USER->id;
        } else {
            $userid = $userid;
        }
        // Security check - are they enrolled on course.
        $context = \context_course::instance($course->id);
        if (!is_siteadmin() && !is_enrolled($context, null, '', true)) {
            return null;
        }
        $completioninfo = new \completion_info($course);
        $trackcount = 0;
        $compcount = 0;
        if ($completioninfo->is_enabled()) {
            $modinfo = get_fast_modinfo($course);
            foreach ($modinfo->cms as $thismod) {
                if (!is_siteadmin() && !$thismod->uservisible) {
                    // Skip when mod is not user visible.
                    continue;
                }

                $completioninfo->get_data($thismod, true, $userid);

               // echo $completioninfo->userid;
                if ($completioninfo->is_enabled($thismod) != COMPLETION_TRACKING_NONE) {
                    $trackcount++;
                    $completiondata = $completioninfo->get_data($thismod, true, $userid);
                /*    echo '<pre>';
                    print_r($completiondata);
                    echo '<pre>';*/
                    if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                        $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                        $compcount++;
                    }
                }
            }
        }

        $compobj = (object) array('complete' => $compcount, 'total' => $trackcount, 'progresshtml' => '');
        if ($trackcount > 0) {
                $progress = get_string('progresstotal', 'completion', $compobj);
             // TODO - we should be putting our HTML in a renderer.
                $progresspercent = ceil(($compcount / $trackcount) * 100);
             /* $progressinfo = '<div class="completionstatus outoftotal">'.$progress.'<span class="pull-right">'.$progresspercent.'%</span></div>
             <div class="completion-line" style="width:'.$progresspercent.'%"></div>
             '; */
            $progressinfo = '<div id="course-info">'.$progress.'</div><div class="progress" style="display:inline-block; width:100%">
                <div class="progress-bar active" role="progressbar" aria-valuenow='.$progresspercent.' aria-valuemin="0" aria-valuemax="100" style="width:'.$progresspercent.'%">
                    '.$progresspercent.'%<span class="sr-only">'.$progresspercent.'% Complete</span>
                </div>
             </div>';
            $compobj->progresshtml = $progressinfo;
        }
        return $compobj;
    }

    /**
     * Get information for array of courseids
     *
     * @param $courseids
     * @return bool | array
     */
    public static function courseinfo($courseids, $userid = null ) {
        global $DB, $USER;
        if (!$userid){
            $userid = $USER->id;
        }
        $courseinfo = array();
        foreach ($courseids as $courseid) {
            $course = $DB->get_record('course', array('id' => $courseid));
            $grades = grade_get_course_grade($userid, $courseid);
            /* echo '<pre>'; print_r($grades);echo '</pre>';*/
            //echo $grades->grade;
            $context = \context_course::instance($courseid);

            if (!is_siteadmin() && !is_enrolled($context, null, '', true)) {
                // Skip this course, don't have permission to view.
                continue;
            }
            
            $courseinfo[$courseid] = (object) array(
                'courseid' => $courseid,
                'coursename' => $course->fullname,
                'progress' => self::course_completion_progress($course,$userid),
                'str_long_grade' => $grades->str_long_grade
            );
        }
        return $courseinfo;
    }

    public static function save_user_profile_info($fname, $lname, $emailid, $description, $city, $country) {
        global $USER;
        $user = $USER;
        $user->firstname = $fname;
        $user->lastname = $lname;
        $user->email = urldecode($emailid);
        $user->description = $description;
        $user->city = $city;
        $user->country = $country;
        user_update_user($user);
    }

    /**
     * Get items which have been graded.
     *
     * @param bool $onlyactive - only show grades in courses actively enrolled on if true.
     * @return string
     * @throws \coding_exception
     */
    public static function graded() {

        $grades = self::events_graded();
        return $grades;

    }

    public static function grading() {
        global $USER, $PAGE;

        $grading = self::all_ungraded($USER->id);
        return $grading;
    }

    /**
     * Get everything graded from a specific date to the current date.
     *
     * @param bool $onlyactive - only show grades in courses actively enrolled on if true.
     * @param null|int $showfrom - timestamp to show grades from. Note if not set defaults to 1 month ago.
     * @return mixed
     */
    public static function events_graded() {
        global $DB, $USER;

        $params = [];
        $coursesql = '';
        $courses = enrol_get_my_courses();
        $courseids = array_keys($courses);
        $courseids[] = SITEID;
        list ($coursesql, $params) = $DB->get_in_or_equal($courseids);
        $coursesql = 'AND gi.courseid '.$coursesql;

        $onemonthago = time() - (DAYSECS * 31);
        $showfrom = $onemonthago;

        $sql = "SELECT gg.*, gi.itemmodule, gi.iteminstance, gi.courseid, gi.itemtype
                  FROM {grade_grades} gg
                  JOIN {grade_items} gi
                    ON gg.itemid = gi.id $coursesql
                 WHERE gg.userid = ?
                   AND (gg.timemodified > ?
                    OR gg.timecreated > ?)
                   AND (gg.finalgrade IS NOT NULL
                    OR gg.rawgrade IS NOT NULL
                    OR gg.feedback IS NOT NULL)
                   AND gi.itemtype = 'mod'
                 ORDER BY timemodified DESC";

        $params = array_merge($params, [$USER->id, $showfrom, $showfrom]);
        $grades = $DB->get_records_sql($sql, $params, 0, 5);

        $eventdata = array();
        foreach ($grades as $grade) {
            $eventdata[] = $grade;
        }

        return $eventdata;
    }

    /**
     * Get all ungraded items.
     * @param int $userid
     * @return array
     */
    public static function all_ungraded($userid) {
        $courseids = self::gradeable_courseids($userid);

        if (empty($courseids)) {
            return array();
        }

        $mods = \core_plugin_manager::instance()->get_installed_plugins('mod');
        $mods = array_keys($mods);

        $grading = [];
        foreach ($mods as $mod) {
            $class = '\theme_remui\activity';
            $method = $mod.'_ungraded';
            if (method_exists($class, $method)) {
                $grading = array_merge($grading, call_user_func([$class, $method], $courseids));
            }
        }

        usort($grading, array('self', 'sort_graded'));

        return $grading;
    }

    /**
     * Get courses where user has the ability to view the gradebook.
     *
     * @param int $userid
     * @return array
     * @throws \coding_exception
     */
    public static function gradeable_courseids($userid) {
        $courses = enrol_get_all_users_courses($userid);
        $courseids = [];
        $capability = 'gradereport/grader:view';
        foreach ($courses as $course) {
            if (has_capability($capability, \context_course::instance($course->id), $userid)) {
                $courseids[] = $course->id;
            }
        }
        return $courseids;
    }

    /**
     * Sort function for ungraded items in the teachers personal menu.
     *
     * Compare on closetime, but fall back to openening time if not present.
     * Finally, sort by unique coursemodule id when the dates match.
     *
     * @return int
     */
    public static function sort_graded($left, $right) {
        if (empty($left->closetime)) {
            $lefttime = $left->opentime;
        } else {
            $lefttime = $left->closetime;
        }

        if (empty($right->closetime)) {
            $righttime = $right->opentime;
        } else {
            $righttime = $right->closetime;
        }

        if ($lefttime === $righttime) {
            if ($left->coursemoduleid === $right->coursemoduleid) {
                return 0;
            } else if ($left->coursemoduleid < $right->coursemoduleid) {
                return -1;
            } else {
                return 1;
            }
        } else if ($lefttime < $righttime) {
            return  -1;
        } else {
            return 1;
        }
    }

    /**
     * Returns list of courses of passed course category id.
     *
     * @param int $categoryid
     * @return array
     */
    public static function get_courses_by_category($categoryid) {
        global $DB;
        $query = "SELECT id, fullname, shortname from {course} where category = " . $categoryid;
        $courselist = $DB->get_records_sql($query);
        $totalcount = 0;
        foreach ($courselist as $course) {
            $context = context_course::instance($course->id);
            $query = "select count(u.id) as count from  {role_assignments} as a, {user} as u where contextid=" . $context->id . " and roleid=5 and a.userid=u.id;";
            $count = $DB->get_records_sql( $query );
            $count = key($count);
            $totalcount += $count;
            $courselist[$course->id]->count = $count;
        }
        usort($courselist, function($variable1, $variable2) {
            return $variable2->count - $variable1->count;
        });
        if ($totalcount === 0) {
            $courselist['totalusercount'] = $totalcount;
            return $courselist;
        } else {
            return $courselist;
        }
    }

    public static function get_quiz_participation_data($courseid) {
        global $DB;
        $sqlq = ("

            SELECT COUNT(DISTINCT u.id)

            FROM {course} c
            JOIN {context} ct ON c.id = ct.instanceid
            JOIN {role_assignments} ra ON ra.contextid = ct.id
            JOIN {user} u ON u.id = ra.userid
            JOIN {role} r ON r.id = ra.roleid

            WHERE c.id = ?

        ");
        $totalcount = $DB->get_records_sql($sqlq, array($courseid));
        $totalcount = key($totalcount);
        $sqlq = ("

            SELECT q.name labels , COUNT(qa.userid) attempts
            FROM {quiz} q
            LEFT JOIN {quiz_attempts} qa ON q.id = qa.quiz

            WHERE q.course = ?
            GROUP BY q.name

        ");
        $quizdata = $DB->get_records_sql($sqlq, array($courseid));
        $chartdata = array();
        $index = 0;
        $chartdata['datasets'][0]['fillColor'] = "#00a65a";
        $chartdata['datasets'][1]['fillColor'] = "#f56954";
        foreach ($quizdata as $key => $quiz) {
            $chartdata['labels'][$index] = $key;
            $chartdata['datasets'][0]['data'][$index] = intval($quiz->attempts);
            $chartdata['datasets'][1]['data'][$index] = intval($totalcount - $quiz->attempts);
            if ($chartdata['datasets'][1]['data'][$index] < 0) {
                $chartdata['datasets'][1]['data'][$index] = 0;
            }
            // $quizdata[$key]->noattempts = $totalcount - $quiz->attempts;
            $index++;
        }
        return $chartdata;
    }

    /**
     * Return the recent blog.
     *
     * This function helps in retrieving the recent blog.
     *
     * @param int $start how many blog should be skipped if specified 0 no recent blog will be skipped.
     * @param int $blogcount number of blog to be return.
     * @param string $filearea file area
     * @return array $blog returns array of blog data.
     */

    public static function get_recent_blog($start = 0, $blogcount = 10) {
        Global $CFG, $OUTPUT;
        $blog = array();
        require_once($CFG->dirroot.'/blog/locallib.php');
        $bloglisting = new \blog_listing();
        require_once($CFG->libdir.'/filelib.php');
        $syscontext = \context_system::instance();
        $fs = get_file_storage();
        $blogentries = $bloglisting->get_entries($start, $blogcount);
        foreach ($blogentries as $blogentry) {

            $files = $fs->get_area_files($syscontext->id, 'blog', 'attachment', $blogentry->id);
            // Adding a blog_entry_attachment for each non-directory file.
            $attachments = array();
            $attrs = array();
            foreach ($files as $file) {
                if ($file->is_directory()) {
                    continue;
                }
                $attachments[] = new \blog_entry_attachment($file, $blogentry->id);
                foreach ($attachments as $attachment) {
                              // Image attachments don't get printed as links.
                    if (file_mimetype_in_typegroup($attachment->file->get_mimetype(), 'web_image')) {
                        $attrs = array('src' => $attachment->url, 'alt' => '');
                        break;
                    }
                }
            }
            // $blogdata['blog'][] = array(
            // 'id' => $blogentry->id,
            // 'summary' => $blogentry->summary,
            // 'subject' => $blogentry->subject,
            // 'imagesrc' =>  $attrs['src'],
            // 'imagealt' => $attrs['alt']);
            $coursesummary = strip_tags($blogentry->summary);
            $summarystring = strlen($coursesummary) > 100 ? substr($coursesummary, 0, 100)."..." : $coursesummary;
            $blog[$blogentry->id]['id'] = $blogentry->id;
            $blog[$blogentry->id]['summary'] = $summarystring;
            $blog[$blogentry->id]['subject'] = $blogentry->subject;
            if (!$attrs) {
                $blog[$blogentry->id]['imagesrc'] = $OUTPUT->pix_url('400x300', 'theme');
                $blog[$blogentry->id]['imagealt'] = "No image found.";
            } else {
                $blog[$blogentry->id]['imagesrc'] = $attrs['src'];
                $blog[$blogentry->id]['imagealt'] = $attrs['alt'];
            }
        }
        return $blog;
    }

    /**
     * Sort recent forum activity by timestamp.
     *
     * @param int $a
     * @param int $b
     * @return int
     */
    private static function sort_timestamp($a, $b) {
        if ($a->timestamp === $b->timestamp) {
            return 0;
        }
        return ($a->timestamp > $b->timestamp ? -1 : 1);
    }

    /**
     * Get recent forum activity for all accessible forums across all courses.
     * @param bool|int|stdclass $userorid
     * @param int $limit
     * @return array
     * @throws \coding_exception
     */
    public static function recent_forum_activity($userorid = false, $limit = 10, $since = null) {
        global $CFG, $DB;

        if (file_exists($CFG->dirroot.'/mod/hsuforum')) {
            require_once($CFG->dirroot.'/mod/hsuforum/lib.php');
        }

        $user = self::get_user($userorid);
        if (!$user) {
            return [];
        }

        if ($since === null) {
            $since = time() - (12 * WEEKSECS);
        }

        // Get all relevant forum ids for SQL in statement.
        // We use the post limit for the number of forums we are interested in too -
        // as they are ordered by most recent post.
        $userforums = new \theme_remui\user_forums($user, $limit);
        $forumids = $userforums->forumids();
        $forumidsallgroups = $userforums->forumidsallgroups();
        $hsuforumids = $userforums->hsuforumids();
        $hsuforumidsallgroups = $userforums->hsuforumidsallgroups();

        if (empty($forumids) && empty($hsuforumids)) {
            return [];
        }

        $sqls = [];
        $params = [];

        if ($limit > 0) {
            $limitsql = self::limit_sql(0, $limit); // Note, this is here for performance optimisations only.
        } else {
            $limitsql = '';
        }

        if (!empty($forumids)) {
            list($finsql, $finparams) = $DB->get_in_or_equal($forumids, SQL_PARAMS_NAMED, 'fina');
            $params = $finparams;
            $params = array_merge($params,
                                 [
                                     'sepgps1a' => SEPARATEGROUPS,
                                     'sepgps2a' => SEPARATEGROUPS,
                                     'user1a'   => $user->id,
                                     'user2a'   => $user->id

                                 ]
            );

            $fgpsql = '';
            if (!empty($forumidsallgroups)) {
                // Where a forum has a group mode of SEPARATEGROUPS we need a list of those forums where the current
                // user has the ability to access all groups.
                // This will be used in SQL later on to ensure they can see things in any groups.
                list($fgpsql, $fgpparams) = $DB->get_in_or_equal($forumidsallgroups, SQL_PARAMS_NAMED, 'allgpsa');
                $fgpsql = ' OR f1.id '.$fgpsql;
                $params = array_merge($params, $fgpparams);
            }

            $params['user2a'] = $user->id;

            $sqls[] = "(SELECT ".$DB->sql_concat("'F'", 'fp1.id')." AS id, 'forum' AS type, fp1.id AS postid,
                               fd1.forum, fp1.discussion, fp1.parent, fp1.userid, fp1.modified, fp1.subject,
                               fp1.message, 0 AS reveal, cm1.id AS cmid,
                               0 AS forumanonymous, f1.course, f1.name AS forumname,
                               u1.firstnamephonetic, u1.lastnamephonetic, u1.middlename, u1.alternatename, u1.firstname,
                               u1.lastname, u1.picture, u1.imagealt, u1.email,
                               c.shortname AS courseshortname, c.fullname AS coursefullname
                          FROM {forum_posts} fp1
                          JOIN {user} u1 ON u1.id = fp1.userid
                          JOIN {forum_discussions} fd1 ON fd1.id = fp1.discussion
                          JOIN {forum} f1 ON f1.id = fd1.forum AND f1.id $finsql
                          JOIN {course_modules} cm1 ON cm1.instance = f1.id
                          JOIN {modules} m1 ON m1.name = 'forum' AND cm1.module = m1.id
                          JOIN {course} c ON c.id = f1.course
                          LEFT JOIN {groups_members} gm1
                            ON cm1.groupmode = :sepgps1a
                           AND gm1.groupid = fd1.groupid
                           AND gm1.userid = :user1a
                         WHERE (cm1.groupmode <> :sepgps2a OR (gm1.userid IS NOT NULL $fgpsql))
                           AND fp1.userid <> :user2a
                           AND fp1.modified > $since
                      ORDER BY fp1.modified DESC
                               $limitsql
                        )
                         ";
            // TODO - when moodle gets private reply (anonymous) forums, we need to handle this here.
        }

        if (!empty($hsuforumids)) {
            list($afinsql, $afinparams) = $DB->get_in_or_equal($hsuforumids, SQL_PARAMS_NAMED, 'finb');
            $params = array_merge($params, $afinparams);
            $params = array_merge($params,
                                  [
                                      'sepgps1b' => SEPARATEGROUPS,
                                      'sepgps2b' => SEPARATEGROUPS,
                                      'user1b'   => $user->id,
                                      'user2b'   => $user->id,
                                      'user3b'   => $user->id,
                                      'user4b'   => $user->id
                                  ]
            );

            $afgpsql = '';
            if (!empty($hsuforumidsallgroups)) {
                // Where a forum has a group mode of SEPARATEGROUPS we need a list of those forums where the current
                // user has the ability to access all groups.
                // This will be used in SQL later on to ensure they can see things in any groups.
                list($afgpsql, $afgpparams) = $DB->get_in_or_equal($hsuforumidsallgroups, SQL_PARAMS_NAMED, 'allgpsb');
                $afgpsql = ' OR f2.id '.$afgpsql;
                $params = array_merge($params, $afgpparams);
            }

            $sqls[] = "(SELECT ".$DB->sql_concat("'A'", 'fp2.id')." AS id, 'hsuforum' AS type, fp2.id AS postid,
                               fd2.forum, fp2.discussion, fp2.parent, fp2.userid, fp2.modified, fp2.subject,
                               fp2.message, fp2.reveal, cm2.id AS cmid,
                               f2.anonymous AS forumanonymous, f2.course, f2.name AS forumname,
                               u2.firstnamephonetic, u2.lastnamephonetic, u2.middlename, u2.alternatename, u2.firstname,
                               u2.lastname, u2.picture, u2.imagealt, u2.email,
                               c.shortname AS courseshortname, c.fullname AS coursefullname
                          FROM {hsuforum_posts} fp2
                          JOIN {user} u2 ON u2.id = fp2.userid
                          JOIN {hsuforum_discussions} fd2 ON fd2.id = fp2.discussion
                          JOIN {hsuforum} f2 ON f2.id = fd2.forum AND f2.id $afinsql
                          JOIN {course_modules} cm2 ON cm2.instance = f2.id
                          JOIN {modules} m2 ON m2.name = 'hsuforum' AND cm2.module = m2.id
                          JOIN {course} c ON c.id = f2.course
                          LEFT JOIN {groups_members} gm2
                            ON cm2.groupmode = :sepgps1b
                           AND gm2.groupid = fd2.groupid
                           AND gm2.userid = :user1b
                         WHERE (cm2.groupmode <> :sepgps2b OR (gm2.userid IS NOT NULL $afgpsql))
                           AND (fp2.privatereply = 0 OR fp2.privatereply = :user2b OR fp2.userid = :user3b)
                           AND fp2.userid <> :user4b
                           AND fp2.modified > $since
                      ORDER BY fp2.modified DESC
                               $limitsql
                        )
                         ";
        }

        $sql = '-- remui sql'. "\n".implode ("\n".' UNION ALL '."\n", $sqls);
        if (count($sqls)>1) {
            $sql .= "\n".' ORDER BY modified DESC';
        }
        $posts = $DB->get_records_sql($sql, $params, 0, $limit);

        $activities = [];

        if (!empty($posts)) {
            foreach ($posts as $post) {
                $postuser = (object)[
                    'id' => $post->userid,
                    'firstnamephonetic' => $post->firstnamephonetic,
                    'lastnamephonetic' => $post->lastnamephonetic,
                    'middlename' => $post->middlename,
                    'alternatename' => $post->alternatename,
                    'firstname' => $post->firstname,
                    'lastname' => $post->lastname,
                    'picture' => $post->picture,
                    'imagealt' => $post->imagealt,
                    'email' => $post->email
                ];

                if ($post->type === 'hsuforum') {
                    $postuser = hsuforum_anonymize_user($postuser, (object)array(
                        'id' => $post->forum,
                        'course' => $post->course,
                        'anonymous' => $post->forumanonymous
                    ), $post);
                }

                $activities[] = (object)[
                    'type' => $post->type,
                    'cmid' => $post->cmid,
                    'name' => $post->subject,
                    'courseshortname' => $post->courseshortname,
                    'coursefullname' => $post->coursefullname,
                    'forumname' => $post->forumname,
                    'sectionnum' => null,
                    'timestamp' => $post->modified,
                    'content' => (object)[
                        'id' => $post->postid,
                        'discussion' => $post->discussion,
                        'subject' => $post->subject,
                        'parent' => $post->parent
                    ],
                    'user' => $postuser
                ];
            }
        }

        return $activities;
    }

    /**
     * Moodle does not provide a helper function to generate limit sql (it's baked into get_records_sql).
     * This function is useful - e.g. improving performance of UNION statements.
     * Note, it will return empty strings for unsupported databases.
     *
     * @param int $from
     * @param int $to
     *
     * @return string
     */
    public static function limit_sql($from, $num) {
        global $DB;
        switch ($DB->get_dbfamily()) {
            case 'mysql' :
                $sql = "LIMIT $from, $num";
                break;
            case 'postgres' :
                $sql = "LIMIT $num OFFSET $from";
                break;
            case 'mssql' :
            case 'oracle' :
            default :
                // Not supported.
                $sql = '';
        }
        return $sql;
    }

}