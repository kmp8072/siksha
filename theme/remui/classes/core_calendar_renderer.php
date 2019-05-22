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
 * Core calender renderer
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs. (http://www.wisdmlabs.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The primary renderer for the calendar.
 */
class theme_remui_core_calendar_renderer extends core_calendar_renderer {
    /**
     * Displays a month in detail
     *
     * @param calendar_information $calendar
     * @param moodle_url $returnurl the url to return to
     * @return string
     */
    public function show_month_detailed(calendar_information $calendar, moodle_url $returnurl  = null) {

        $this->construct_modal();

        global $CFG;
        $params = array();
        $this->page->requires->js_call_amd('theme_remui/calendar', 'initialise', $params);
        $this->page->requires->strings_for_js(array('redirectingtocourse', 'nopermissiontoaddactivityinanycourse',
            'nopermissiontoaddactivityincourse', 'selectyouroption'), 'theme_remui');
        if (empty($returnurl)) {
            $returnurl = $this->page->url;
        }

        // Get the calendar type we are using.
        $calendartype = \core_calendar\type_factory::get_calendar_instance();

        // Store the display settings.
        $display = new stdClass;
        $display->thismonth = false;

        // Get the specified date in the calendar type being used.
        $date = $calendartype->timestamp_to_date_array($calendar->time);
        $thisdate = $calendartype->timestamp_to_date_array(time());
        if ($date['mon'] == $thisdate['mon'] && $date['year'] == $thisdate['year']) {
            $display->thismonth = true;
            $date = $thisdate;
            $calendar->time = time();
        }

        // Get Gregorian date for the start of the month.
        $gregoriandate = $calendartype->convert_to_gregorian($date['year'], $date['mon'], 1);
        // Store the gregorian date values to be used later.
        list($gy, $gm, $gd, $gh, $gmin) = array($gregoriandate['year'], $gregoriandate['month'], $gregoriandate['day'],
            $gregoriandate['hour'], $gregoriandate['minute']);

        // Get the starting week day for this month.
        $startwday = dayofweek(1, $date['mon'], $date['year']);
        // Get the days in a week.
        $daynames = calendar_get_days();
        // Store the number of days in a week.
        $numberofdaysinweek = $calendartype->get_num_weekdays();

        $display->minwday = calendar_get_starting_weekday();
        $display->maxwday = $display->minwday + ($numberofdaysinweek - 1);
        $display->maxdays = calendar_days_in_month($date['mon'], $date['year']);

        // These are used for DB queries, so we want unixtime, so we need to use Gregorian dates.
        $display->tstart = make_timestamp($gy, $gm, $gd, $gh, $gmin, 0);
        $display->tend = $display->tstart + ($display->maxdays * DAYSECS) - 1;

        // Align the starting weekday to fall in our display range
        // This is simple, not foolproof.
        if ($startwday < $display->minwday) {
            $startwday += $numberofdaysinweek;
        }
        // Get events from database
        $events = calendar_get_events($display->tstart, $display->tend, $calendar->users, $calendar->groups, $calendar->courses);
        if (!empty($events)) {
            foreach ($events as $eventid => $event) {
                $event = new calendar_event($event);
                if (!empty($event->modulename)) {
                    $cm = get_coursemodule_from_instance($event->modulename, $event->instance);
                    if (!\core_availability\info_module::is_user_visible($cm, 0, false)) {
                        unset($events[$eventid]);
                    }
                }
            }
        }

        // Extract information: events vs. time
        calendar_events_by_day($events, $date['mon'], $date['year'], $eventsbyday, $durationbyday, $typesbyday, $calendar->courses);

        $output  = html_writer::start_tag('div', array('class' => 'header'));
        $output .= $this->course_filter_selector($returnurl, get_string('detailedmonthviewfor', 'calendar'));
        if (calendar_user_can_add_event($calendar->course)) {
            $output .= $this->add_event_button($calendar->course->id, 0, 0, 0, $calendar->time);
        }
        $output .= html_writer::end_tag('div', array('class' => 'header'));
        // Controls
        $output .= html_writer::tag('div', calendar_top_controls('month', array('id' => $calendar->courseid, 'time' => $calendar->time)), array('class' => 'controls'));

        $table = new html_table();
        $table->attributes = array('class' => 'calendarmonth calendartable');
        $table->summary = get_string('calendarheading', 'calendar', userdate($calendar->time, get_string('strftimemonthyear')));
        $table->data = array();

        // Get the day names as the header.
        $header = array();
        for ($i = $display->minwday; $i <= $display->maxwday; ++$i) {
            $header[] = $daynames[$i % $numberofdaysinweek]['shortname'];
        }
        $table->head = $header;

        // For the table display. $week is the row; $dayweek is the column.
        $week = 1;
        $dayweek = $startwday;

        $row = new html_table_row(array());

        // Paddding (the first week may have blank days in the beginning)
        for ($i = $display->minwday; $i < $startwday; ++$i) {
            $cell = new html_table_cell('&nbsp;');
            $cell->attributes = array('class' => 'nottoday dayblank');
            $row->cells[] = $cell;
        }

        // Now display all the calendar
        $weekend = CALENDAR_DEFAULT_WEEKEND;
        if (isset($CFG->calendar_weekend)) {
            $weekend = intval($CFG->calendar_weekend);
        }

        $daytime = strtotime('-1 day', $display->tstart);
        for ($day = 1; $day <= $display->maxdays; ++$day, ++$dayweek) {
            $viewalleventsurl = "";
            $daytime = strtotime('+1 day', $daytime);
            if ($dayweek > $display->maxwday) {
                // We need to change week (table row)

                $table->data[] = $row;

                $row = new html_table_row(array());
                $dayweek = $display->minwday;
                ++$week;
            }

            // Reset vars
            $cell = new html_table_cell();
            $dayhref = calendar_get_link_href(new moodle_url(CALENDAR_URL.'view.php', array('view' => 'day', 'course' => $calendar->courseid)), 0, 0, 0, $daytime);
            $viewalleventsurl = clone $dayhref;

            $cellclasses = array();

            if ($weekend & (1 << ($dayweek % $numberofdaysinweek))) {
                // Weekend. This is true no matter what the exact range is.
                $cellclasses[] = 'weekend';
            }

            // Special visual fx if an event is defined
            if (isset($eventsbyday[$day])) {
                if (count($eventsbyday[$day]) == 1) {
                    $title = get_string('oneevent', 'calendar');
                } else {
                    $title = get_string('manyevents', 'calendar', count($eventsbyday[$day]));
                }
                $cell->text = html_writer::tag('div', html_writer::link($dayhref, $day, array('title' => $title)), array('class' => 'day'));
            } else {
                $cell->text = html_writer::tag('div', $day, array('class' => 'day'));
            }

            // Special visual fx if an event spans many days
            $durationclass = false;
            if (isset($typesbyday[$day]['durationglobal'])) {
                $durationclass = 'duration_global';
            } else if (isset($typesbyday[$day]['durationcourse'])) {
                $durationclass = 'duration_course';
            } else if (isset($typesbyday[$day]['durationgroup'])) {
                $durationclass = 'duration_group';
            } else if (isset($typesbyday[$day]['durationuser'])) {
                $durationclass = 'duration_user';
            }
            if ($durationclass) {
                $cellclasses[] = 'duration';
                $cellclasses[] = $durationclass;
            }

            // Special visual fx for today
            if ($display->thismonth && $day == $date['mday']) {
                $cellclasses[] = 'day today';
            } else {
                $cellclasses[] = 'day nottoday';
            }

            if (!isset($eventsbyday[$day]) && !isset($durationbyday[$day])) {
                $cellclasses[] = 'blank-div';
            }
            $cell->attributes = array('class' => join(' ', $cellclasses));

            if (isset($eventsbyday[$day])) {
                $eventcount = 0;
                $cell->text .= html_writer::start_tag('ul', array('class' => 'events-new'));
                foreach ($eventsbyday[$day] as $eventindex) {
                    $eventcount++;
                    if ($eventcount >= 3) {
                        break;
                    }
                    // If event has a class set then add it to the event <li> tag
                    $attributes = array();
                    if (!empty($events[$eventindex]->class)) {
                        $attributes['class'] = $events[$eventindex]->class;
                    }
                    $dayhref->set_anchor('event_'.$events[$eventindex]->id);
                    $link = html_writer::link($dayhref, format_string($events[$eventindex]->name, true));
                    $cell->text .= html_writer::tag('li', $link, $attributes);
                }
                $cell->text .= html_writer::end_tag('ul');
            }
            $eventcount = 0;
            if (isset($durationbyday[$day])) {
                $cell->text .= html_writer::start_tag('ul', array('class' => 'events-underway'));
                foreach ($durationbyday[$day] as $eventindex) {
                    $eventcount++;
                    if ($eventcount >= 4) {
                        break;
                    }
                    $cell->text .= html_writer::tag('li', '['.format_string($events[$eventindex]->name, true).']', array('class' => 'events-underway'));
                }
                $cell->text .= html_writer::end_tag('ul');
            }
            $neweventurl = new moodle_url('/calendar/event.php?cal_d=' . $day . '&cal_m=' . $date['mon'] . '&cal_y=' . $date['year'] . '&course=' . $calendar->course->id);

            $cell->text .= html_writer::start_tag('div', array('class' => 'add-activities'));
            $cell->text .= html_writer::link($neweventurl, '<i class="fa fa-calendar-check-o aria-hidden="true"></i>',
                array('class' => 'new-event-url', 'data-toggle' => 'tooltip1', 'title' => get_string('addnewevent', 'theme_remui')));
            $newactivityhtml = html_writer::link('#', '<i class="fa fa-book" aria-hidden="true"></i>',
                array('class' => 'new-activity-modal', 'data-toggle' => 'tooltip2', 'title' => get_string('addnewactivity', 'theme_remui'), 'data-id' => $calendar->course->id));
            $cell->text .= html_writer::span($newactivityhtml, '', array('data-toggle' => 'modal', 'data-target' => '#calendarModal'));
            $cell->text .= html_writer::link($viewalleventsurl, '<i class="fa fa-calendar aria-hidden="true"></i>',
                array('class' => 'view-all-events', 'data-toggle' => 'tooltip3', 'title' => get_string('viewallevents', 'theme_remui')));
            $cell->text .= html_writer::end_tag('div');
            $row->cells[] = $cell;
        }

        // Paddding (the last week may have blank days at the end)
        for ($i = $dayweek; $i <= $display->maxwday; ++$i) {
            $cell = new html_table_cell('&nbsp');
            $cell->attributes = array('class' => 'nottoday dayblank');
            $row->cells[] = $cell;
        }
        $table->data[] = $row;
        $output .= html_writer::table($table);

        return $output;
    }


    public function show_day(calendar_information $calendar, moodle_url $returnurl = null) {
        if ($returnurl === null) {
            $returnurl = $this->page->url;
        }

        $events = calendar_get_upcoming($calendar->courses, $calendar->groups, $calendar->users, 1, 100, $calendar->timestamp_today());

        $output  = html_writer::start_tag('div', array('class' => 'header'));
        $output .= $this->course_filter_selector($returnurl, get_string('dayviewfor', 'calendar'));
        if (calendar_user_can_add_event($calendar->course)) {
            $output .= $this->add_event_button($calendar->course->id, 0, 0, 0, $calendar->time);
        }
        $output .= html_writer::end_tag('div');
        // Controls
        $output .= html_writer::tag('div', calendar_top_controls('day', array('id' => $calendar->courseid,
            'time' => $calendar->time)), array('class' => 'controls'));

        if (empty($events)) {
            // There is nothing to display today.
            $output .= html_writer::span(get_string('daywithnoevents', 'calendar'), 'calendar-information calendar-no-results');
        } else {
            $output .= html_writer::start_tag('div', array('class' => 'eventlist'));
            $underway = array();
            // First, print details about events that start today
            foreach ($events as $event) {
                $event = new calendar_event($event);
                $event->calendarcourseid = $calendar->courseid;
                if ($event->timestart >= $calendar->timestamp_today() && $event->timestart <= $calendar->timestamp_tomorrow() - 1) {  // Print it now
                    $event->time = calendar_format_event_time($event, time(), null, false, $calendar->timestamp_today());
                    $output .= $this->event($event);
                } else {                                                                 // Save this for later
                    $underway[] = $event;
                }
            }

            // Then, show a list of all events that just span this day
            if (!empty($underway)) {
                $output .= html_writer::span(get_string('spanningevents', 'calendar'),
                    'calendar-information calendar-span-multiple-days');
                foreach ($underway as $event) {
                    $event->time = calendar_format_event_time($event, time(), null, false, $calendar->timestamp_today());
                    $output .= $this->event($event);
                }
            }

            $output .= html_writer::end_tag('div');
        }

        return $output;
    }

    public function event(calendar_event $event, $showactions=true) {
        global $CFG;

        $event = calendar_add_event_metadata($event);
        $context = $event->context;
        $output = '';

        if (calendar_edit_event_allowed($event) && $showactions) {
            if (empty($event->cmid)) {
                $editlink = new moodle_url(CALENDAR_URL.'event.php', array('action' => 'edit', 'id' => $event->id));
                $deletelink = new moodle_url(CALENDAR_URL.'delete.php', array('id' => $event->id));
                if (!empty($event->calendarcourseid)) {
                    $editlink->param('course', $event->calendarcourseid);
                    $deletelink->param('course', $event->calendarcourseid);
                }
            } else {
                $editlink = new moodle_url('/course/mod.php', array('update' => $event->cmid, 'return' => true, 'sesskey' => sesskey()));
                $deletelink = null;
            }

            $commands  = html_writer::start_tag('div', array('class' => 'commands pull-right'));
            $commands .= html_writer::start_tag('a', array('href' => $editlink));
            $commands .= html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/edit'),
                'alt' => get_string('tt_editevent', 'calendar'), 'title' => get_string('tt_editevent', 'calendar')));
            $commands .= html_writer::end_tag('a');
            if ($deletelink != null) {
                $commands .= html_writer::start_tag('a', array('href' => $deletelink));
                $commands .= html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/delete'),
                    'alt' => get_string('tt_deleteevent', 'calendar'), 'title' => get_string('tt_deleteevent', 'calendar')));
                $commands .= html_writer::end_tag('a');
            }
            $commands .= html_writer::end_tag('div');
            $output .= $commands;
        }

        $output .= html_writer::start_tag('div');
        if (!empty($event->icon)) {
            $output .= $event->icon;
        } else {
            $output .= $this->output->spacer(array('height' => 16, 'width' => 16));
        }

        if (!empty($event->referer)) {
            $output .= $this->output->heading($event->referer, 3, array('class' => 'referer'));
        } else {
            $output .= $this->output->heading(
                format_string($event->name, false, array('context' => $context)),
                3,
                array('class' => 'name')
            );
        }
        $output .= html_writer::end_tag('div');
        if (!empty($event->courselink)) {
            $output .= html_writer::tag('div', $event->courselink, array('class' => 'course'));
        }
        // Show subscription source if needed.
        if (!empty($event->subscription) && $CFG->calendar_showicalsource) {
            if (!empty($event->subscription->url)) {
                $source = html_writer::link($event->subscription->url, get_string('subsource', 'calendar', $event->subscription));
            } else {
                // File based ical.
                $source = get_string('subsource', 'calendar', $event->subscription);
            }
            $output .= html_writer::tag('div', $source, array('class' => 'subscription'));
        }
        if (!empty($event->time)) {
            $output .= html_writer::tag('span', $event->time, array('class' => 'date'));
        } else {
            $output .= html_writer::tag('span', calendar_time_representation($event->timestart), array('class' => 'date'));
        }

        $eventdetailshtml = '';
        $eventdetailsclasses = '';

        $eventdetailshtml .= format_text($event->description, $event->format, array('context' => $context));
        $eventdetailsclasses .= 'description';
        if (isset($event->cssclass)) {
            $eventdetailsclasses .= ' '.$event->cssclass;
        }

        $output .= html_writer::tag('div', $eventdetailshtml, array('class' => $eventdetailsclasses));
        
        return html_writer::tag('div', $output , array('class' => 'event', 'id' => 'event_' . $event->id));
    }


    public function construct_modal() {

?>
<!-- Modal -->
<div id="calendarModal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><?php echo get_string('selectcoursetoaddactivity', 'theme_remui'); ?></h4>
      </div>
      <div class="modal-body">
        <select class="form-control calendar-modal-select">
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo get_string('closebuttontitle'); ?></button>
      </div>
    </div>

  </div>
</div>

<?php
    }
}