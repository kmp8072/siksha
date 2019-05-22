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
 * Dashboard - Quiz stats
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// get and set box state
user_preference_allow_ajax_update("quizstats", PARAM_TEXT);
$box_state = json_decode(get_user_preferences("quizstats", 0));

?>
<div id="quiz_stats">
<?php

    global $DB;
    $sqlq = ("

SELECT DISTINCT q.course courseid, c.shortname shortname, c.fullname fullname

FROM {quiz} q
JOIN {course} c ON q.course = c.id

    ");
$courses = $DB->get_records_sql($sqlq);
foreach ($courses as $course) {
    $context = context_course::instance($course->courseid);
    if (!has_capability('mod/quiz:preview', $context)) {
        unset($courses[$course->courseid]);
    }
}
if ($courses) {
?>

<!-- <div class="col-md-7" style="position: relative;"> -->
<div class="box<?php echo ($box_state)?' collapsed-box':'';?>" data-name="quizstats">
   <div class="box-header ui-sortable-handle" >
    <i class="fa fa-bar-chart"></i> <h3 class="box-title"><?php echo get_string('quizstats', 'theme_remui'); ?></h3>
    <div class="box-tools pull-right">
            <?php if ( $this->page->user_is_editing()) { ?>
       <button class="btn btn-box-tool" ><i class="fa fa-arrows"></i></button>
            <?php } ?>
      <button class="btn btn-box-tool" data-widget="collapse"><i class="<?php echo ($box_state)?'fa fa-plus':'fa fa-minus'; ?>"></i></button>
  <!--         <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button> -->
    </div>
  </div><!-- /.box-header -->
  <div class="box-body"  <?php echo ($box_state)?'style="display:none;"':'';?>>
    <select id="quiz-course-list" class="pull-right form-control">
<?php
foreach ($courses as $course) {
    echo "<option data-id=" . $course->courseid . ">" . $course->shortname . "</option>";
}
?>
    </select>
      <div id="quiz-chart-area">
        <div class="chart">
          <canvas id="barChart"></canvas>
        </div>
        <i class="fa fa-square text-green"></i> <?php echo get_string('totalusersattemptedquiz', 'theme_remui'); ?><br>
        <i class="fa fa-square text-red"></i> <?php echo get_string('totalusersnotattemptedquiz', 'theme_remui'); ?>
      </div><!-- /.box-body -->

    <div class="box-footer no-padding">

    </div><!-- /.footer -->
    <br>
    <div class="quiz-stats-error alert alert-danger" style="display:none"><?php echo get_string('problemwhileloadingdata', 'theme_remui'); ?>
    </div>
  </div>
</div>
<!-- </div> -->
<?php
}
?>
</div>