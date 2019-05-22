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
 * Dashboard - Overview
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$newuserscount;

// Check if user is admin.
if (is_siteadmin()) {

    // If we should show or hide empty courses.
    if (!defined('REPORT_COURSESIZE_SHOWEMPTYCOURSES')) {
        define('REPORT_COURSESIZE_SHOWEMPTYCOURSES', false);
    }
    // How many users should we show in the User list.
    if (!defined('REPORT_COURSESIZE_NUMBEROFUSERS')) {
        define('REPORT_COURSESIZE_NUMBEROFUSERS', 10);
    }
    // How often should we update the total sitedata usage.
    if (!defined('REPORT_COURSESIZE_UPDATETOTAL')) {
        define('REPORT_COURSESIZE_UPDATETOTAL', 1 * DAYSECS);
    }

    $reportconfig = get_config('report_coursesize');
    if (!empty($reportconfig->filessize) && !empty($reportconfig->filessizeupdated) && ($reportconfig->filessizeupdated > time() - REPORT_COURSESIZE_UPDATETOTAL)) {
        // Total files usage has been recently calculated, and stored by another process - use that.
        $totalusage = $reportconfig->filessize;
        $totaldate = date("Y-m-d H:i", $reportconfig->filessizeupdated);
    } else {
        // Total files usage either hasn't been stored, or is out of date.
        $totaldate = date("Y-m-d H:i", time());
        $totalusage = get_directory_size($CFG->dataroot);
        set_config('filessize', $totalusage, 'report_coursesize');
        set_config('filessizeupdated', time(), 'report_coursesize');
    }

    $totalusagereadable = number_format(ceil($totalusage / 1048576)) . " MB";

    $newuserscount = \theme_remui\controller\theme_controller::get_new_members_count(time() - 604800);

    // Get count of active members since last week.
    $activeuserscount = \theme_remui\controller\theme_controller::get_active_members_count(time() - 604800);

    ?>
    <!-- /.col -->
    <div class="col-md-4 col-sm-12 col-xs-12">
      <div class="info-box">
        <span class="info-box-icon"><i class="fa fa-pie-chart text-red" aria-hidden="true"></i></span>

        <div class="info-box-content">
          <span class="info-box-text"><?php echo get_string('totaldiskusage', 'theme_remui');?></span>
          <span class="info-box-number"><?php echo $totalusagereadable; ?></span>
        </div>
        <!-- /.info-box-content -->
      </div>
      <!-- /.info-box -->
    </div>
    <!-- /.col -->

    <!-- fix for small devices only -->
    <div class="clearfix visible-sm-block"></div>

    <div class="col-md-4 col-sm-12 col-xs-12">
      <div class="info-box">
        <span class="info-box-icon"><i class="fa fa-users text-green"></i></span>

        <div class="info-box-content">
          <span class="info-box-text"><?php echo get_string('activemembers', 'theme_remui'); ?></span>
          <span class="info-box-number"><?php echo $activeuserscount; ?></span>
        </div>
        <!-- /.info-box-content -->
      </div>
      <!-- /.info-box -->
    </div>
    <!-- /.col -->
    <div class="col-md-4 col-sm-12 col-xs-12">
      <div class="info-box">
        <span class="info-box-icon"><i class="fa fa-user-plus text-yellow"></i></span>

        <div class="info-box-content">
          <span class="info-box-text"><?php echo get_string('newmembers', 'theme_remui'); ?></span>
          <span class="info-box-number"><?php echo $newuserscount; ?></span>
        </div>
        <!-- /.info-box-content -->
      </div>
      <!-- /.info-box -->
    </div>
    <!-- /.col -->
<?php
}