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
 * Layout - Dashboard
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* Default globals */
global $CFG, $PAGE, $USER, $SITE, $COURSE;
$enabledashboardelements = get_config('theme_remui', 'enabledashboardelements');
$PAGE->set_popup_notification_allowed(false);
echo $OUTPUT->doctype();
?>

<html <?php echo $OUTPUT->htmlattributes(); ?>>
  <head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon() ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimal-ui">
    <?php echo $OUTPUT->standard_head_html(); ?>
  </head>

  <body <?php echo $OUTPUT->body_attributes(); ?>>

    <div class="wrapper"> <!-- main page wrapper -->

    <?php
      echo $OUTPUT->standard_top_of_body_html();

      // Include header navigation
      require_once(\theme_remui\controller\theme_controller::get_partial_element('header'));

      // Include main sidebar.
      require_once(\theme_remui\controller\theme_controller::get_partial_element('pre-aside'));
    ?>

      <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">

        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="heading"><?php echo $OUTPUT->page_heading(); ?></div>

            <div class="action-buttons">
                <?php echo $OUTPUT->page_heading_button(); ?>
                <?php echo $OUTPUT->course_header(); ?>
          </div>
        </section>

        <section class="content-breadcrumb">
          <ol class="breadcrumb">
            <?php echo $OUTPUT->navbar(); ?>
          </ol>
        </section>

        <!-- Main content -->
        <section class="content">

            <?php
              // only show custom theme elements if current page is dashboard
if ( $PAGE->pagetype === 'my-index' && $enabledashboardelements ) {
    // Include overview
    echo '<div class="row">';
    require_once(\theme_remui\controller\theme_controller::get_partial_element('dashboard/overview'));
    echo '</div>';
}
            ?>

          <div id="region-main" >
                <?php
                echo $OUTPUT->course_content_header();
                echo $OUTPUT->main_content();
                echo $OUTPUT->course_content_footer();
                ?>
          </div>

            <?php
            // only show custom theme elements if current page is dashboard
if ( $PAGE->pagetype === 'my-index' && $enabledashboardelements) {

    user_preference_allow_ajax_update("layout_7", PARAM_TEXT);
    user_preference_allow_ajax_update("layout_5", PARAM_TEXT);

    $layout7 = json_decode(get_user_preferences("layout_7", '["default_layout7_element", "recent_events", "recent_active_forum",
      "enrolled_users_stats", "quiz_stats", "add_notes"]'));
    $layout5 = json_decode(get_user_preferences("layout_5", '["default_layout5_element", "latest_members", "deadlines", "quick_message", "recent_assignments"]'));
?>
<!-- Visitors report -->
<div class="row dashboard-elements" <?php if($this->page->user_is_editing()) { echo 'style="overflow-y: hidden;"';
} ?>>
<?php if ( $this->page->user_is_editing()) { // The elements can be dragged. ?>
          <section class="col-lg-7"  id="col7">
            <?php } else { // The elements can not be dragged. ?>
          <section class="col-lg-7">
            <?php }?>
<?php
 echo '<div id="default_layout7_element"> <br /> </div>'; // We are adding this div, SO element can be draggable even if there is no div.
// Include in Left Coluumns
foreach ($layout7 as $value) {
    // echo $value;
    if ($value != 'default_layout7_element') {
        require_once(\theme_remui\controller\theme_controller::get_partial_element('dashboard/'.$value));
    }
}
?>
</section>
<?php if ( $this->page->user_is_editing()) { // The elements can be dragged. ?>
          <section class="col-lg-5" id="col5">
            <?php } else { // The elements can not be dragged. ?>
          <section class="col-lg-5">
            <?php }?>
<?php
echo '<div id="default_layout5_element"> <br /> </div>'; // We are adding this div, SO element can be draggable even if there is no div. // Include in Right Coluumns
foreach ($layout5 as $value) {
    if ($value != 'default_layout5_element') {
        require_once(\theme_remui\controller\theme_controller::get_partial_element('dashboard/'.$value));
    }
}
    ?>
</section>
</div><!-- /.row -->
<?php             } ?>

        </section><!-- /.content -->

      </div><!-- /.content-wrapper -->

        <?php
        // Include post sidebar
        require_once(\theme_remui\controller\theme_controller::get_partial_element('post-aside'));

        // Include footer
        require_once(\theme_remui\controller\theme_controller::get_partial_element('footer'));

        echo $OUTPUT->standard_end_of_body_html();
if ( $PAGE->pagetype === 'my-index' && $enabledashboardelements ) {
    $params = array('contextid' => $PAGE->context->id);
    $this->page->requires->js_call_amd('theme_remui/dashboard', 'initialise', $params);
    $PAGE->requires->strings_for_js(array('entermessage', 'selectcontact', 'messagesent', 'messagenotsent', 'messagenotsenterror', 'sendingmessage', 'sendmoremessage',
    'selectastudent', 'total', 'nousersenrolledincourse', 'selectcoursetodisplayusers'
    ), 'theme_remui');
}?>

    </div> <!-- ./wrapper -->
  </body>
</html>