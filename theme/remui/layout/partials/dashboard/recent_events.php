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
 * Dashboard - Recent Events
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$eventslist = \theme_remui\controller\theme_controller::get_events();
$seealleventslink = new moodle_url('/calendar/view.php?view=upcoming');
$addneweventurl = new moodle_url('/calendar/event.php?action=new');

// get and set box state
user_preference_allow_ajax_update("recentevents", PARAM_TEXT);
$box_state = json_decode(get_user_preferences("recentevents", 0));

?>
<div id="recent_events">

  <div class="box<?php echo ($box_state)?' collapsed-box':'';?>" data-name="recentevents">
     <div class="box-header ui-sortable-handle">
       <i class="fa fa-calendar"></i>
      <h3 class="box-title"><?php echo get_string('upcomingevents', 'theme_remui'); ?></h3>
      <div class="box-tools pull-right">
            <?php if ( $this->page->user_is_editing()) { ?>
         <button class="btn btn-box-tool"><i class="fa fa-arrows"></i></button>
            <?php } ?>
         <button class="btn btn-box-tool" data-widget="collapse"><i class="<?php echo ($box_state)?'fa fa-plus':'fa fa-minus'; ?>"></i></button>
      <!-- <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button> -->
      </div>
    </div><!-- /.box-header -->
    <div class="box-body"  <?php echo ($box_state)?'style="display:none;"':'';?>>
<?php
if ($eventslist) {
?>
<div class="list-group">
<?php
    foreach ($eventslist as $event) {
        $eventhref = new moodle_url('/calendar/view.php?view=day&time=' . $event->timestart . '#event_' . $event->id);
?>
        <a class="list-group-item" href="<?php echo $eventhref; ?>">
        <?php echo $event->name; ?>
        <span class="label text-blue pull-right"><i class="fa fa-clock-o"></i>
        <?php
        if ($event->timestart < time()) {
                echo get_string('startedsince', 'theme_remui') . " " . \theme_remui\controller\theme_controller::get_time_format(time() - $event->timestart);
        } else {
                echo get_string('startingin', 'theme_remui') . \theme_remui\controller\theme_controller::get_time_format($event->timestart - time());
        }
        ?>
      </span>
        <p class="text-muted">
        <?php
        $eventdescription = strip_tags($event->description);
        if (strlen($eventdescription) > 69) {
            echo substr($eventdescription, 0, 70) . '..';
        } else {
            echo $eventdescription;
        }
        ?>
      </p>
      </a>
<?php
    }
?>
</div>
<?php
} else {
    echo $OUTPUT->notification(get_string('noupcomingeventstoshow', 'theme_remui'), 'notifymessage');
}
?>
    </div><!-- /.box-body -->
    <div class="box-footer text-center">
      <a href="<?php echo $seealleventslink; ?>" class="uppercase"><?php echo get_string('viewallevents', 'theme_remui'); ?></a>
    </div>
  </div><!-- /.box-footer -->
</div>