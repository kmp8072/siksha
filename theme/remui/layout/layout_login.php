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
 * Layout - Login Page
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* Default globals */
global $CFG, $PAGE, $USER, $SITE, $COURSE;

// used variables

$logoorsitename = get_config('theme_remui', 'logoorsitename');
$siteicon = get_config('theme_remui', 'siteicon');
$checklogo = $PAGE->theme->setting_file_url('logo', 'logo');
if (!empty($checklogo)) {
    $logo = $PAGE->theme->setting_file_url('logo', 'logo');
} else {
    $logo = $CFG->wwwroot.'/theme/remui/pix/logo.png';
}

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

    <div class="remui-wrapper" > <!-- main page wrapper -->
        <?php
          echo $OUTPUT->standard_top_of_body_html();
        ?>
        <!-- Content Wrapper. Contains page content -->
        <div class="remui-content-wrapper">
            <!-- Main content -->
            <section id="region-main" class="content wdm-login-content">
            <div class="row" id="login-img">
            <?php if (!empty($logoorsitename)) {
            ?>
              <div class="wdm-logo-wrapper">  <!-- adding the wrapper class so only image will be clickable instal whole header. -->
                    <?php if ($logoorsitename == 'logo') { ?>
                    <a href="<?php echo $CFG->wwwroot; ?>" class="logo">
                      <span class="logo-lg"><img alt="<?php echo format_string($SITE->shortname); ?>" src="<?php echo $logo;?>" /></span>
                    </a>
                <?php } else if ($logoorsitename == 'sitename') { ?>
                  <h1>
                     <a class="logo" href="<?php echo $CFG->wwwroot; ?>">
                      <span class="logo-lg">
                        <?php echo format_string($SITE->shortname); ?>
                     </span>
                     </a>
                  </h1>
        <?php } else {  ?>
                <h1>
                    <a class="logo" href="<?php echo $CFG->wwwroot; ?>">
                      <span class="logo-lg">
                         <i class="fa fa-<?php echo $siteicon; ?>"></i>
                        <?php echo format_string($SITE->shortname); ?>
                     </span>
                     </a>
                </h1>
        <?php } ?>
                    </div>
                    <?php } ?>
                        <?php echo $OUTPUT->course_content_header(); ?>
                        <?php echo $OUTPUT->main_content(); ?>
                        <?php echo $OUTPUT->course_content_footer(); ?>
            </div>
            </section>
        </div>
        
        <?php
            echo $OUTPUT->standard_end_of_body_html();
        ?>
    </div> <!-- ./wrapper -->
  </body>
</html>