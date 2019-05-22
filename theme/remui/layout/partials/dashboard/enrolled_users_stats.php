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
 * Dashboard - Enrolled users stats
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// get and set box state
user_preference_allow_ajax_update("enrolledusersstats", PARAM_TEXT);
$box_state = json_decode(get_user_preferences("enrolledusersstats", 0));

?>
<div id="enrolled_users_stats">
<?php
if (is_siteadmin()) {
    global $DB;
    $categorylist = coursecat::make_categories_list();
    $inquery = implode(", ", array_keys($categorylist));
    $sqlq = 'SELECT DISTINCT category from {course} where category IN (' . $inquery . ')';
    $result = $DB->get_records_sql($sqlq);

?>

<!-- <div class="col-md-7" style="position: relative;"> -->
<div class="box<?php echo ($box_state)?' collapsed-box':'';?>" data-name="enrolledusersstats">
   <div class="box-header ui-sortable-handle" >
   <i class="fa fa-pie-chart"></i>
    <h3 class="box-title"><?php echo get_string('enrolleduserstats', 'theme_remui'); ?></h3>
    <div class="box-tools pull-right">
            <?php if ( $this->page->user_is_editing()) { ?>
       <button class="btn btn-box-tool" ><i class="fa fa-arrows"></i></button>
            <?php } ?>
      <button class="btn btn-box-tool" data-widget="collapse"><i class="<?php echo ($box_state)?'fa fa-plus':'fa fa-minus'; ?>"></i></button>
  <!--         <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button> -->
    </div>
  </div><!-- /.box-header -->
  <div class="box-body"  <?php echo ($box_state)?'style="display:none;"':'';?>>
<?php
if ($result) {
?>
    <div class="row">
      <div class="col-md-7">
        <div class="chart-responsive">
          <canvas id="pieChart"></canvas>
        </div><!-- ./chart-responsive -->
      </div><!-- /.col -->
      <div class="col-md-5">
<?php
    echo "<select id='coursecategorylist' class='coursecategorylist form-control'>";

    foreach ($result as $key => $res) {
        echo "<option data-id=" . $key . ">" . $categorylist[$key] . "</option>";
    }

    echo "</select>";
?>
        <ul class="chart-legend clearfix">

        </ul>
      </div><!-- /.col -->
    </div><!-- /.row -->
    <div class="enroll-stats-error alert alert-danger" style="display:none"><?php echo get_string('problemwhileloadingdata', 'theme_remui'); ?></div>
    <div class="enroll-stats-nouserserror alert alert-info" style="display:none"><?php echo get_string('nousersincoursecategoryfound', 'theme_remui'); ?></div>
<?php
} else {
?>
    <div class="enroll-stats-error alert alert-info"><?php echo get_string('nocoursecategoryfound', 'theme_remui'); ?>
    </div>
<?php
}
?>
  </div><!-- /.box-body -->
</div>
<!-- </div> -->
<?php
}
?>
</div>