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
 * Dashboard - Latest Members
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// get and set box state
user_preference_allow_ajax_update("latestmembers", PARAM_TEXT);
$box_state = json_decode(get_user_preferences("latestmembers", 0));

?>
<div id="latest_members">
<?php
if (is_siteadmin()) {
    $userdata = \theme_remui\controller\theme_controller::get_recent_user();
?>
<!-- <div class="col-md-7" style="position: relative;"> -->
<!-- USERS LIST -->
<div class="box<?php echo ($box_state)?' collapsed-box':'';?>" data-name="latestmembers">
   <div class="box-header ui-sortable-handle">
    <i class="fa fa-users" aria-hidden="true"></i>
    <h3 class="box-title"><?php echo get_string('latestmembers', 'theme_remui'); ?></h3>
    <div class="box-tools pull-right">
 <!--      <span class="label label-danger"><?php // echo count($userdata); ?> New Members</span> -->
<?php
    if ($this->page->user_is_editing()) {
?>
         <button class="btn btn-box-tool"><i class="fa fa-arrows"></i></button>
<?php
    }
?>
      <button class="btn btn-box-tool" data-widget="collapse"><i class="<?php echo ($box_state)?'fa fa-plus':'fa fa-minus'; ?>"></i></button>
      <!-- <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button> -->
    </div>
  </div><!-- /.box-header -->
  <div class="box-body no-padding"  <?php echo ($box_state)?'style="display:none;"':'';?>>
    <ul class="users-list clearfix">
<?php
    foreach ($userdata as $value) {
?>
    <li>
   <img src="<?php  echo $value['img']; ?>" alt="<?php echo get_string('userimage', 'theme_remui'); ?>">
   <a class="users-list-name" href="<?php echo new moodle_url('/user/profile.php?id='.$value['id']); ?>"> <?php echo $value['name']; ?></a>
   <span class="users-list-date"> <?php echo $value['register_date']; ?></span>
   </li>
<?php
    }
?>
    </ul><!-- /.users-list -->
  </div><!-- /.box-body -->
<?php
    // Check if user is admin.
    if (is_siteadmin()) {
?>
  <div class="box-footer text-center">
    <a href="<?php echo new moodle_url('/admin/user.php'); ?>" class="uppercase"><?php echo get_string('viewallusers', 'theme_remui'); ?></a>
  </div><!-- /.box-footer -->
  </div>
<?php
    }
}
?>
</div><!--/.box -->
<!-- </div> -->