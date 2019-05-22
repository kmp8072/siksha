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
 * Partial - Post Sidebar
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
?>
<!-- Post Sidebar -->
<?php
$sidebarskin = " control-sidebar-dark dark-sidebar";
if (get_config('theme_remui', 'rightsidebarskin') == 1) {
    $sidebarskin = " control-sidebar-light light-sidebar";
}
?>
<aside class='control-sidebar <?php echo $sidebarskin ?>'>
  <div class="controlsidebarpost">
    <?php if (is_siteadmin()) {
    ?>
      <div class="btn-group btn-group-justified text-center quick-links" style="" role="group" aria-label="...">
        <div class="btn-group" role="group">
          <a href="<?php echo $CFG->wwwroot ?>/admin/settings.php?section=theme_remui_dashboard" title="Dashboard Settings" class="quick-link">
            <i class="fa fa-paint-brush" aria-hidden="true"></i>
          </a>
        </div>
        <div class="btn-group" role="group">
          <a href="<?php echo $CFG->wwwroot ?>/course/edit.php?category=1" title="Create a New Course" class="quick-link">
            <i class="fa fa-file-text-o" aria-hidden="true"></i>
          </a>
        </div>
        <div class="btn-group" role="group">
          <a href="<?php echo $CFG->wwwroot ?>/course/index.php" title="Course Archive Page" class="quick-link">
            <i class="fa fa-book" aria-hidden="true"></i>
          </a>
        </div>
        <div class="btn-group" role="group">
          <a href="<?php echo $CFG->wwwroot ?>/blog/index.php" title="Site Blog" class="quick-link">
            <i class="fa fa-comments" aria-hidden="true"></i>
          </a>
        </div>
      </div>
    <?php } ?>

    <?php echo $OUTPUT->blocks('side-post', array(), 'div'); ?>
  </div>
</aside><!-- /.post-sidebar -->

<!-- Add the sidebar's background. This div must be placed
     immediately after the control sidebar -->
<div class="control-sidebar-bg"></div>