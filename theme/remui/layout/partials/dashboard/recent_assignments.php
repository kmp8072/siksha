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
 * Dashboard - Recent Assignments
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $COURSE, $DB, $USER, $CFG;

?>
<div id="recent_assignments">
<?php
$recentassignments = \theme_remui\controller\theme_controller::grading();
if ($recentassignments) {

  // get and set box state
  user_preference_allow_ajax_update("recentassignments", PARAM_TEXT);
  $box_state = json_decode(get_user_preferences("recentassignments", 0));

?>
    <div class="box<?php echo ($box_state)?' collapsed-box':'';?>" data-name="recentassignments">
      <div class="box-header ui-sortable-handle">
        <i class="fa fa-book" aria-hidden="true"></i>
        <h3 class="box-title"><?php echo get_string('assignmentstobegraded', 'theme_remui'); ?></h3>

        <div class="pull-right box-tools">
            <?php if ($this->page->user_is_editing()) { ?>
         <button class="btn btn-box-tool" ><i class="fa fa-arrows"></i></button>
            <?php } ?>
          <button class="btn btn-box-tool" data-widget="collapse"><i class="<?php echo ($box_state)?'fa fa-plus':'fa fa-minus'; ?>"></i></button>
        </div>
      </div>
      <!-- /.box-header -->
      <div class="box-body list-group"  <?php echo ($box_state)?'style="display:none;"':'';?>>
<?php
    $i = 0;
    foreach ($recentassignments as $ungraded) {
        $modinfo = get_fast_modinfo($ungraded->course);
        $course = $modinfo->get_course();
        $cm = $modinfo->get_cm($ungraded->coursemoduleid);
?>
        <a href="<?php echo $cm->url; ?>" class="list-group-item">
          <span>
            <?php echo get_string('activity', 'moodle') . ": " . $cm->name; ?>
          </span>
          <br>
          <span class="text-muted">
            <?php echo get_string('course', 'moodle') . ": " . $course->fullname; ?>
          </span>

        </a>
<?php
        if (++$i == 5) {
            break;
        }
    }
?>
      </div>
    </div>
<?php
} else {
    
    // get and set box state
    user_preference_allow_ajax_update("recentfeedback", PARAM_TEXT);
    $box_state = json_decode(get_user_preferences("recentfeedback", 0));

    $grades = \theme_remui\controller\theme_controller::graded();
    if (!empty($grades)) {
        ?>
    <div class="box<?php echo ($box_state)?' collapsed-box':'';?>" data-name="recentfeedback">
      <div class="box-header ui-sortable-handle">
        <i class="fa fa-book" aria-hidden="true"></i>
        <h3 class="box-title"><?php echo get_string('recentfeedback', 'theme_remui'); ?></h3>

        <div class="pull-right box-tools">
            <?php if ($this->page->user_is_editing()) { ?>
         <button class="btn btn-box-tool" ><i class="fa fa-arrows"></i></button>
            <?php } ?>
          <button class="btn btn-box-tool" data-widget="collapse"><i class="<?php echo ($box_state)?'fa fa-plus':'fa fa-minus'; ?>"></i></button>
        </div>
      </div>
      <!-- /.box-header -->
      <div class="box-body table-responsive"  <?php echo ($box_state)?'style="display:none;"':'';?>>
        <table class="table table-hover">
          <tbody>
            <tr>
              <th><?php echo get_string('course'); ?></th>
              <th><?php echo get_string('activity'); ?></th>
              <th><?php echo get_string('grade'); ?></th>
              <th><?php echo get_string('time'); ?></th>
            </tr>
        <?php
foreach ($grades as $grade) {
    $modinfo = get_fast_modinfo($grade->courseid);
    $course = $modinfo->get_course();

    $modtype = $grade->itemmodule;
    $cm = $modinfo->instances[$modtype][$grade->iteminstance];

    $coursecontext = \context_course::instance($grade->courseid);
    $canviewhiddengrade = has_capability('moodle/grade:viewhidden', $coursecontext);
    $url = new \moodle_url('/grade/report/user/index.php', ['id' => $grade->courseid]);
    if (in_array($modtype, ['quiz', 'assign'])
                  && (!empty($grade->rawgrade) || !empty($grade->feedback))
    ) {
                // Only use the course module url if the activity was graded in the module, not in the gradebook, etc.
        $url = $cm->url;
    }

    $gradetitle = "$course->fullname / $cm->name";
    $releasedon = isset($grade->timemodified) ? $grade->timemodified : $grade->timecreated;
    $grade = new \grade_grade(array('itemid' => $grade->itemid, 'userid' => $USER->id));
    if (!$grade->is_hidden() || $canviewhiddengrade) {
        $courseurl = new moodle_url('/course/view.php?id=' . $grade->grade_item->courseid);
        $assignurl = $cm->url;
        // $teacherurl = new moodle_url('/user/profile.php?id=' . $teacherid);
        $timemodified = \theme_remui\controller\theme_controller::get_time_format(time() - $grade->timemodified);

        ?>
            <tr>
              <td><a href="<?php echo $courseurl; ?>"><?php echo $course->shortname; ?></a></td>
              <td><a href="<?php echo $assignurl; ?>"><?php echo $grade->grade_item->itemname; ?></a></td>
              <td><?php echo intval($grade->rawgrade) . "/" . intval($grade->rawgrademax); ?></td>
              <td><span class="label text-blue">
                <i class="fa fa-clock-o"></i>
                <?php echo get_string('ago', 'message', $timemodified); ?></span></td>
            </tr>
      <!-- /.box-body -->

        <?php
    }
}
        ?>
          </tbody>
        </table>
      </div>
    </div>
        <?php
    }
}

?>
<!-- /.box-body -->
</div>