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
 * Layout - 1 Column
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* Default globals */
global $CFG, $PAGE, $USER, $SITE, $COURSE;

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

    <div class="wrapper sidebar-none"> <!-- main page wrapper -->

        <?php
          echo $OUTPUT->standard_top_of_body_html();

          // Include header navigation
          require_once(\theme_remui\controller\theme_controller::get_partial_element('header-alternate'));
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
            </section><!-- /.content-header -->

            <!-- Main content -->
            <section class="content">
                <div id="region-main" >
                    <?php echo $OUTPUT->main_content(); ?>
                </div>
                <div id="region-main-custom">

                </div>
            </section>
        </div>

        <?php
            // Include footer
            require_once(\theme_remui\controller\theme_controller::get_partial_element('footer'));

            echo $OUTPUT->standard_end_of_body_html();
        ?>

    </div> <!-- ./wrapper -->
  </body>
</html>