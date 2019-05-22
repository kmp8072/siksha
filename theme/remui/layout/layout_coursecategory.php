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
 * Layout - Course Category
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* Default globals */
global $CFG, $PAGE, $USER, $SITE, $COURSE;
$hassidepost = $PAGE->blocks->region_has_content('side-post', $OUTPUT);
$options = coursecat::make_categories_list();

// PARAM_ALPHA can be used when we want get rid of other characters like . or $ or * and retruns only string.
// PARAM_RAW return the string as it is.
// PARAM_INT returns the int value only.
$search = optional_param('search', '', PARAM_RAW);
$category = optional_param('categoryid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);

// Prepare search url.
$pageurl = new moodle_url('/course/index.php');
if (!empty($search)) {
    $pageurl->param('search', $search);
}
if (!empty($category)) {
    $pageurl->param('categoryid', $category);
}

// Preapare pagination parameters.
$courseperpage = get_config('theme_remui', 'courseperpage');
if (empty($courseperpage)) {
    $courseperpage = 12;
}

$startfrom = $page * $courseperpage;
$totalcourse = count(\theme_remui\controller\theme_controller::get_courses($search, $category));

// Count number of records.
$totalpages = ceil($totalcourse / $courseperpage);

// Get courses
$featuredcourses = \theme_remui\controller\theme_controller::get_courses($search, $category, $startfrom, $courseperpage);

// Get course renederer
$courserenderer = $PAGE->get_renderer('core', 'course');

$PAGE->set_popup_notification_allowed(false);
echo $OUTPUT->doctype();
?>

<html <?php echo $OUTPUT->htmlattributes(); ?>>
  <head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon() ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimal-ui">
    <?php echo $OUTPUT->standard_head_html() ?>
  </head>

  <body <?php echo $OUTPUT->body_attributes(); ?>>

    <div class="wrapper"> <!-- main page wrapper -->

        <?php
        echo $OUTPUT->standard_top_of_body_html();

        // Include header navigation.
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
        </section><!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
          <div id="region-main">
            <div class="row course-filter">
              <div class="col-md-4 col-xs-12 hidden-xs">
                  <form method="get" action='<?php echo $pageurl; ?>'>
                    <input type='hidden' name="search" value="<?php echo $search; ?>">
                     <select onchange="this.form.submit()" name="categoryid" id="category">
                      <option value=''><?php echo get_string('allcategory', 'theme_remui'); ?></option>
                      <?php
                        foreach ($options as $key => $coursecategory) {
                            if ( $category == $key) {
                                echo "<option selected value='{$key}'>{$coursecategory}</option>";
                            } else {
                                  echo "<option value='{$key}'>{$coursecategory}</option>";
                            }
                                        // echo $course_category;
                      } ?>
                    </select>
                  </form>
              </div>
              <div class="col-md-4 col-xs-12 pull-right wdm_search" >
                <?php
                echo $courserenderer->course_search_form($search);
                    ?>
              </div>
            </div>
            
            <!-- print catgory desc -->
            <?php
              $chelper = new coursecat_helper();
              $coursecat = coursecat::get($category);
              if ($description = $chelper->get_category_formatted_description($coursecat)) {
                echo "<div class='row course-cat-desc'>
                    <div class='col-xs-12 text-muted'>".$description."</div>
                </div>";
              }
            ?>
            
            <div class="row course-grid">
              <?php if (!empty($featuredcourses)) {
                $countcourse = 0;
                foreach ($featuredcourses as $key => $value) {
                    // echo $key.'dsad';
                     $countcourse ++;
                ?>
                  <div class="col-lg-3 col-md-4 col-sm-6 col-xs-12">
                    <div class="box box-shadow">
                      <div class="box-header no-padding wdm-course-img" style="background-image: url(<?php echo $value['courseimage']; ?>);
                                    background-size: cover;
                                    background-position: center">
                          <div class="wdm-course-img-info">
                            <h3><a href="<?php  echo $value['courselink']; ?>" ><?php echo get_string('viewcours', 'theme_remui'); ?></a></h3>
                          </div>
                      </div>

                      <div class="box-body text-muted">
                        <h4>
                        <a class="wdm_course" href="<?php  echo $value['courselink']; ?>" title="<?php  echo $value['coursename'];?>"><?php echo $value['coursename'];?>
                        </a>
                        </h4>
                          <?php echo $value['coursesummary'] ?>
                      </div>
                      <!-- /.box-body -->
                      <div class="box-footer no-padding">
                        <div class="m-b-5">
                          <?php
                          $coursecontext = context_course::instance($value['courseid']);
                          if (has_capability('moodle/course:enrolconfig', $coursecontext)) {?>
                              <a class="" href="<?php echo $value['enroledusers'];  ?>" title ="Enrol User" >
                            <i class="fa fa-user fa-lg"></i>
                              </a>
                              <?php
                          }
                          if (has_capability('mod/assign:grade', $coursecontext)) {
                              ?>
                            <a class="" href="<?php  echo $value['grader']; ?>" title="Grader Report">
                              <i class="fa fa-graduation-cap fa-lg"></i>
                            </a>
                              <?php
                          }
                          if (has_capability('moodle/course:activityvisibility', $coursecontext)) {
                              ?>
                              <a class="" href="<?php  echo $value['activity']; ?>" title="Activity Report">
                            <i class="fa fa-binoculars fa-lg" ></i>
                              </a>
                              <?php
                          }
                          if (has_capability('moodle/course:update', $coursecontext)) {
                              ?>
                             <a class="" href="<?php  echo $value['editcourse']; ?>" title="Edit Course">
                           <i class="fa fa fa-cog fa-lg"></i>
                             </a>
                              <?php
                          }
                          if (has_capability('moodle/course:view', $coursecontext)) {
                              ?>
                            <a class="" href="<?php  echo $value['courselink']; ?>" title="<?php  echo $value['coursename'];?>">
                              <i class="fa fa-arrow-circle-o-right fa-lg"></i>
                            </a>
                              <?php
                          } ?>
                        </div>
                      </div>
                      <!-- /.box-footer-->
                    </div>
                </div>
    <?php
        if ($countcourse == 1) {
            echo '<div class="clearfix visible-xs-block"></div>';
        } else if ($countcourse == 2) {
            echo '<div class="clearfix visible-sm-block"></div>';
        } else if ($countcourse == 3) {
            echo '<div class="clearfix visible-md-block"></div>';
        } else if ($countcourse == 4) {
            echo '<div class="clearfix visible-lg-block"></div>';
            $countcourse = 0;
        }
    }
} else {
    echo "<div class='col-md-12'><h2>".get_string('nocoursefound', 'theme_remui')."</h2></div>";
}?>
  </div>
<?php
    // Output pagination bar
    $pagingbar = new paging_bar($totalcourse, $page, $courseperpage, $pageurl, 'page');
    // $pagingbar->maxdisplay = 4;
    echo $OUTPUT->render($pagingbar);
?>
              <div class="row" style="display:none" >
                <?php
                 echo $OUTPUT->course_content_header();
                 echo $OUTPUT->main_content();
                 echo $OUTPUT->course_content_footer();
                ?>
              </div>
        </div><!-- /.row -->
        </section><!-- /.content -->
      </div><!-- /.content-wrapper -->
        <?php
if ($hassidepost) {
    // Include post sidebar
    require_once(\theme_remui\controller\theme_controller::get_partial_element('post-aside'));
}

        // Include footer
        require_once(\theme_remui\controller\theme_controller::get_partial_element('footer'));

        echo $OUTPUT->standard_end_of_body_html();
        ?>
    </div> <!-- ./wrapper -->
  </body>
</html>