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
 * Dashboard - Add Notes
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// get and set box state
user_preference_allow_ajax_update("addnotes", PARAM_TEXT);
$box_state = json_decode(get_user_preferences("addnotes", 0));

?>
<div id="add_notes">
<?php
$courses = get_courses();
unset($courses[1]);

foreach ($courses as $courseid => $course) {
    $coursecontext = context_course::instance($course->id);
    // Check whether user has capability to edit notes.
    $hascapability = has_capability('moodle/notes:manage', $coursecontext);
    if (! $hascapability) {
        unset($courses[$courseid]);
    }
}
if ($courses) {
?>
  <div class="box<?php echo ($box_state)?' collapsed-box':'';?>" data-name="addnotes">
    <div class="box-header ui-sortable-handle" >
      <i class="fa fa-file-text" aria-hidden="true"></i>
      <h3 class="box-title"><?php echo get_string('addnotes', 'theme_remui'); ?></h3>
      <!-- tools box -->
      <div class="pull-right box-tools">
        <?php if ($this->page->user_is_editing()) { ?>
        <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-arrows"></i></button> 
        <?php } ?>
        <button class="btn btn-box-tool" data-widget="collapse"><i class="<?php echo ($box_state)?'fa fa-plus':'fa fa-minus'; ?>"></i></button>
      </div><!-- /. tools -->
    </div>
    <div class="box-body"  <?php echo ($box_state)?'style="display:none;"':'';?>>
      <div class="add-notes-select">
        <select class="form-control">
          <option value=""><?php echo get_string('selectacourse', 'theme_remui'); ?></option>
<?php

foreach ($courses as $course) {
?>
          <option id="<?php echo $course->id; ?>"><?php echo $course->shortname; ?></option>
<?php
}
?>
        </select>
      </div>
      <br>
      <select class="select2-studentlist form-control">

      </select>
      <br><br>
      <div class="row">
        <div class="add-notes-button col-lg-4 col-md-12 col-sm-12 col-xs-12">
          <a href="#" class="btn btn-sm btn-info site-note btn-flat" ><?php echo get_string('addsitenote', 'theme_remui'); ?></a>
        </div>
        <div class="add-notes-button col-lg-4 col-md-12 col-sm-12 col-xs-12">
          <a href="#" class="btn btn-sm btn-info course-note btn-flat" ><?php echo get_string('addcoursenote', 'theme_remui'); ?></a>
        </div>
        <div class="add-notes-button col-lg-4 col-md-12 col-sm-12 col-xs-12">
          <a href="#" class="btn btn-sm btn-info personal-note btn-flat" ><?php echo get_string('addpersonalnote', 'theme_remui'); ?></a>
        </div>
      </div>
    </div>
  </div>
<?php
}
?>
</div>
