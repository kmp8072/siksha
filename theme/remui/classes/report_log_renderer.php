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
 * Report log renderer
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs. (http://www.wisdmlabs.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class  theme_remui_report_log_renderer extends report_log_renderer
{
    public function report_selector_form(report_log_renderable $reportlog) {
        echo html_writer::start_tag('div', array('class' => 'container-fluid'));
        echo html_writer::start_tag('form', array('class' => 'logselecform', 'action' => $reportlog->url, 'method' => 'get'));
        echo html_writer::start_tag('div', array('class' => 'row span12'));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'chooselog', 'value' => '1'));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'showusers', 'value' => $reportlog->showusers));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'showcourses',
            'value' => $reportlog->showcourses));

        $selectedcourseid = empty($reportlog->course) ? 0 : $reportlog->course->id;
        // Add course selector.
        $sitecontext = context_system::instance();
        $courses = $reportlog->get_course_list();
        if (!empty($courses) && $reportlog->showcourses) {
            echo html_writer::label(get_string('selectacourse'), 'menuid', false, array('class' => 'accesshide'));
            echo html_writer::select($courses, "id", $selectedcourseid, null, array('class' => 'col-xs-12 col-sm-4 col-md-4 col-lg-4'));
        } else {
            $courses = array();
            $courses[$selectedcourseid] = get_course_display_name_for_list($reportlog->course) . (($selectedcourseid == SITEID) ? ' (' . get_string('site') . ') ' : '');
            echo html_writer::label(get_string('selectacourse'), 'menuid', false, array('class' => 'accesshide'));
            echo html_writer::select($courses, "id", $selectedcourseid, false, array('class' => 'col-xs-12 col-sm-4 col-md-4 col-lg-4'));
            // Check if user is admin and this came because of limitation on number of courses to show in dropdown.
            if (has_capability('report/log:view', $sitecontext)) {
                $obj = new stdClass();
                $obj->url = new moodle_url('/report/log/index.php', array('chooselog' => 0,
                    'group' => $reportlog->get_selected_group(), 'user' => $reportlog->userid,
                    'id' => $selectedcourseid, 'date' => $reportlog->date, 'modid' => $reportlog->modid,
                    'showcourses' => 1, 'showusers' => $reportlog->showusers));
                $obj->url = $obj->url->out(false);
                print_string('logtoomanycourses', 'moodle', $obj);
            }
        }

        // Add group selector.
        $groups = $reportlog->get_group_list();
        if (!empty($groups)) {
            echo html_writer::label(get_string('selectagroup'), 'menugroup', false, array('class' => 'accesshide'));
            echo html_writer::select($groups, "group", $reportlog->groupid, get_string("allgroups"), array('class' => 'col-xs-12 col-sm-4 col-md-4 col-lg-4'));
        }

        // Add user selector.
        $users = $reportlog->get_user_list();

        if ($reportlog->showusers) {
            echo html_writer::label(get_string('selctauser'), 'menuuser', false, array('class' => 'accesshide'));
            echo html_writer::select($users, "user", $reportlog->userid, get_string("allparticipants"), array('class' => 'col-xs-12 col-sm-4 col-md-4 col-lg-4'));
        } else {
            $users = array();
            if (!empty($reportlog->userid)) {
                $users[$reportlog->userid] = $reportlog->get_selected_user_fullname();
            } else {
                $users[0] = get_string('allparticipants');
            }
            echo html_writer::label(get_string('selctauser'), 'menuuser', false, array('class' => 'accesshide'));
            echo html_writer::select($users, "user", $reportlog->userid, false, array('class' => 'col-xs-12 col-sm-4 col-md-4 col-lg-4'));
        }

        // Add date selector.
        $dates = $reportlog->get_date_options();
        echo html_writer::label(get_string('date'), 'menudate', false, array('class' => 'accesshide'));
        echo html_writer::select($dates, "date", $reportlog->date, get_string("alldays"), array('class' => 'col-xs-12 col-sm-4 col-md-4 col-lg-4'));

        // Add activity selector.
        $activities = $reportlog->get_activities_list();
        echo html_writer::label(get_string('activities'), 'menumodid', false, array('class' => 'accesshide'));
        echo html_writer::select($activities, "modid", $reportlog->modid, get_string("allactivities"), array('class' => 'col-xs-12 col-sm-4 col-md-4 col-lg-4'));

        // Add actions selector.
        echo html_writer::label(get_string('actions'), 'menumodaction', false, array('class' => 'accesshide'));
        echo html_writer::select($reportlog->get_actions(), 'modaction', $reportlog->action, get_string("allactions"), array('class' => 'col-xs-12 col-sm-4 col-md-4 col-lg-4'));

        // Add edulevel.
        $edulevel = $reportlog->get_edulevel_options();
        echo html_writer::label(get_string('edulevel'), 'menuedulevel', false, array('class' => 'accesshide'));
        echo html_writer::select($edulevel, 'edulevel', $reportlog->edulevel, false, array('class' => 'col-xs-12 col-sm-4 col-md-4 col-lg-4'));

        // Add reader option.
        // If there is some reader available then only show submit button.
        $readers = $reportlog->get_readers(true);
        if (!empty($readers)) {
            if (count($readers) == 1) {
                $attributes = array('type' => 'hidden', 'name' => 'logreader', 'value' => key($readers));
                echo html_writer::empty_tag('input', $attributes);
            } else {
                echo html_writer::label(get_string('selectlogreader', 'report_log'), 'menureader', false,
                        array('class' => 'accesshide'));
                echo html_writer::select($readers, 'logreader', $reportlog->selectedlogreader, false, array('class' => 'col-xs-12 col-sm-4 col-md-4 col-lg-4'));
            }
            echo html_writer::end_tag('div');
            echo html_writer::start_tag('center');
            $obj = new stdClass();
            $obj->url = new moodle_url('/report/log/index.php', array('chooselog' => 0,
                'group' => $reportlog->get_selected_group(), 'user' => $reportlog->userid,
                'id' => $selectedcourseid, 'date' => $reportlog->date, 'modid' => $reportlog->modid,
                'showusers' => 1, 'showcourses' => $reportlog->showcourses));
            $obj->url = $obj->url->out(false);
            print_string('logtoomanyusers', 'moodle', $obj);
            echo html_writer::start_tag('div');
            echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('gettheselogs')));
            echo html_writer::end_tag('div');
            echo html_writer::end_tag('center');
        }
        echo html_writer::end_div();
        echo html_writer::end_tag('form');
    }
}