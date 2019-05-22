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
 * Dashboard - Recent Active Forum
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// get and set box state
user_preference_allow_ajax_update("recentactiveforum", PARAM_TEXT);
$box_state = json_decode(get_user_preferences("recentactiveforum", 0));

?>
<!-- <div class="col-md-5" style="position: relative;"> -->
<div id="recent_active_forum">
<?php
$recentforums = \theme_remui\controller\theme_controller::recent_forum_activity($USER->id, 5);
if ($recentforums) {
?>
<div class="box<?php echo ($box_state)?' collapsed-box':'';?>" data-name="recentactiveforum">
  <div class="box-header ui-sortable-handle">
    <i class="fa fa-microphone" aria-hidden="true"></i>
    <h3 class="box-title"><?php echo get_string('recentlyactiveforums', 'theme_remui'); ?></h3>
    <div class="box-tools pull-right">
            <?php if ($this->page->user_is_editing()) { ?>
        <button class="btn btn-box-tool"><i class="fa fa-arrows"></i></button>
            <?php } ?>
        <button class="btn btn-box-tool" data-widget="collapse"><i class="<?php echo ($box_state)?'fa fa-plus':'fa fa-minus'; ?>"></i></button>
        <!-- <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>  -->
    </div>
  </div><!-- /.box-header -->
  <div class="box-body list-group"  <?php echo ($box_state)?'style="display:none;"':'';?>>
<?php

foreach ($recentforums as $recentforum) {
    // Check whether user has capability to view Forum.
    // $discussion = $DB->get_record('forum_discussions', array('id' => $recentforum->id), '*', MUST_EXIST);
    // $cm = get_coursemodule_from_instance('forum', $recentforum->forum, $recentforum->courseid, false, MUST_EXIST);
    // $modcontext = context_module::instance($cm->id);
    $timemodified = \theme_remui\controller\theme_controller::get_time_format(time() - $recentforum->timestamp);
    $discussionurl = new moodle_url('/mod/forum/discuss.php?d=' . $recentforum->content->discussion . '#p' . $recentforum->content->id);
    ?>
                <a href="<?php echo $discussionurl; ?>" class="list-group-item"><?php echo $recentforum->forumname; ?> <span class="label text-blue pull-right">
                <i class="fa fa-clock-o"></i> <?php echo get_string('startedsince', 'theme_remui') . " " . $timemodified  ?></span>
                <p class="text-muted">
                <?php echo get_string('course') . ' : ' . $recentforum->courseshortname; ?>
                </p>
              </a>
<?php
}
?>
      </ul>
    </div><!-- /.box-body -->
</div>
<?php
}
?>
  <!-- </div> -->
</div>
