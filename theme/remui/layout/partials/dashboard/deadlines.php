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
 * Dashboard - Deadlines
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$deadlineevents = \theme_remui\controller\theme_controller::deadlines();
$calendarurl = new moodle_url('/calendar/view.php?view=month');

// get and set box state
user_preference_allow_ajax_update("deadlines", PARAM_TEXT);
$box_state = json_decode(get_user_preferences("deadlines", 0));

?>
<div id="deadlines">
  <div class="box<?php echo ($box_state)?' collapsed-box':'';?>" data-name="deadlines">
    <div class="box-header ui-sortable-handle">
     <i class="fa fa-bell" aria-hidden="true"></i>
      <h3 class="box-title"><?php echo get_string('deadlines', 'theme_remui'); ?></h3>

      <div class="box-tools pull-right">
    <?php if ($this->page->user_is_editing()) { ?>
         <button class="btn btn-box-tool"><i class="fa fa-arrows"></i></button>
    <?php } ?>
        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="<?php echo ($box_state)?'fa fa-plus':'fa fa-minus'; ?>"></i>
        </button>
      </div>
    </div>
    <!-- /.box-header -->
    <div class="box-body list-group"  <?php echo ($box_state)?'style="display:none;"':'';?>>
    <?php
if ($deadlineevents) {
    foreach ($deadlineevents as $deadlineevent) {
        // Used for getting assignment id to redirect user on that particular assignment.
        $modinfo = get_fast_modinfo($deadlineevent->courseid);
        $cm = $modinfo->instances[$deadlineevent->modulename][$deadlineevent->instance];
        $startingtime = $deadlineevent->timestart;
        if ($startingtime >= time()) {
            $startingtime -= time();
            $startingtime = get_string('in', 'theme_remui') . " " . \theme_remui\controller\theme_controller::get_time_format($startingtime);
        } else {
            $startingtime = time() - $startingtime;
            $startingtime = get_string('since', 'theme_remui') . " " . \theme_remui\controller\theme_controller::get_time_format($startingtime);
        }
        ?>
        <a class="list-group-item" href="<?php echo $cm->url; ?>">
            <?php echo $deadlineevent->name; ?>
          <span class="label text-blue pull-right"><i class="fa fa-clock-o"></i> <?php echo $startingtime; ?></span>
          <p class="text-muted">
            <?php echo get_string('course') . ": " . $deadlineevent->coursefullname; ?>
          </p>
        </a>
        <?php
    }
} else {
    global $OUTPUT;
    echo $OUTPUT->notification(get_string('noupcomingdeadlines', 'theme_remui'), 'notifymessage');
}
?>
    </div>
    <!-- /.box-body -->
    <div class="box-footer text-center">
      <a href="<?php echo $calendarurl; ?>" class="uppercase"><?php echo get_string('gotocalendar', 'theme_remui'); ?></a>
    </div>
    <!-- /.box-footer -->
    </div>

</div>