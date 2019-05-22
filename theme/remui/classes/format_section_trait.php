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
 * Trait - format section
 * Code that is shared between course_format_topic_renderer.php and course_format_weeks_renderer.php
 * Used for section outputs.
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs. (http://www.wisdmlabs.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// include_once('general_section_trait.php');

trait format_section_trait {

    // use general_section_trait;

    /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;
            $sections = $sections;
            $mods = $mods;
            $modnames = $modnames;
            $modnamesused = $modnamesused;
        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        echo $this->start_section_list();

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section == 0) {
                // 0-section is displayed a little different then the others
                if ($thissection->summary or !empty($modinfo->sections[0]) or $PAGE->user_is_editing()) {
                    echo $this->section_header($thissection, $course, false, 0);
                    echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                    echo $this->courserenderer->course_section_add_cm_control($course, 0, 0);
                    echo $this->section_footer();
                }
                continue;
            }
            if ($section > $course->numsections) {
                // activities inside this section are 'orphaned', this section will be printed as 'stealth' below
                continue;
            }
            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display.
            $showsection = $thissection->uservisible ||
                    ($thissection->visible && !$thissection->available &&
                    !empty($thissection->availableinfo));
            if (!$showsection) {
                // If the hiddensections option is set to 'show hidden sections in collapsed
                // form', then display the hidden section message - UNLESS the section is
                // hidden by the availability system, which is set to hide the reason.
                if (!$course->hiddensections && $thissection->available) {
                    echo $this->section_hidden($section, $course->id);
                }

                continue;
            }

            if (!$PAGE->user_is_editing() && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                // Display section summary only.
                echo $this->section_summary($thissection, $course, null);
            } else {
                echo $this->section_header($thissection, $course, false, 0);
                if ($thissection->uservisible) {
                    echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                    echo $this->courserenderer->course_section_add_cm_control($course, $section, 0);
                }
                echo $this->section_footer();
            }
        }

        if ($PAGE->user_is_editing() and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $course->numsections or empty($modinfo->sections[$section])) {
                    // this is not stealth section or it is empty
                    continue;
                }
                echo $this->stealth_section_header($section);
                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->stealth_section_footer();
            }

            echo $this->end_section_list();

            echo html_writer::start_tag('div', array('id' => 'changenumsections', 'class' => 'mdl-right'));
            // Increase number of sections.
            $straddsection = get_string('increasesections', 'moodle');
            $url = new moodle_url('/course/changenumsections.php',
                array('courseid' => $course->id,
                      'increase' => true,
                      'sesskey' => sesskey()));
            $icon = $this->output->pix_icon('t/switch_plus', $straddsection);
            echo html_writer::link($url, $icon.get_accesshide($straddsection), array('class' => 'increase-sections'));

            if ($course->numsections > 0) {
                // Reduce number of sections sections.
                $strremovesection = get_string('reducesections', 'moodle');
                $url = new moodle_url('/course/changenumsections.php',
                    array('courseid' => $course->id,
                          'increase' => false,
                          'sesskey' => sesskey()));
                $icon = $this->output->pix_icon('t/switch_minus', $strremovesection);
                echo html_writer::link($url, $icon.get_accesshide($strremovesection), array('class' => 'reduce-sections'));
            }

            echo html_writer::end_tag('div');
        } else {
            echo $this->end_section_list();
        }

    }

    /**
     * Generate a summary of a section for display on the 'coruse index page'
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param array    $mods (argument not used)
     * @return string HTML to output.
     */
    protected function section_summary($section, $course, $mods) {
        $mods = $mods;
        $classattr = 'section main section-summary box clearfix';
        $linkclasses = '';

        // If section is hidden then display grey section link
        if (!$section->visible) {
            $classattr .= ' hidden';
            $linkclasses .= ' dimmed_text';
        } else if (course_get_format($course)->is_section_current($section)) {
            $classattr .= ' current';
        }

        $title = get_section_name($course, $section);
        $op = '';
        $op .= html_writer::start_tag('li', array('id' => 'section-'.$section->section,
            'class' => $classattr, 'role' => 'region', 'aria-label' => $title));
        $op .= html_writer::start_tag('div', array('class' => 'content box-body row'));

        if ($section->uservisible) {
            $title = html_writer::tag('a', $title,
                    array('href' => course_get_url($course, $section->section), 'class' => $linkclasses));
        }
        $op .= $this->format_summary($section, $course, null);
        $op .= $this->section_activity_summary($section, $course, null);

        $context = context_course::instance($course->id);
        $op .= $this->section_availability_message($section,
                has_capability('moodle/course:viewhiddensections', $context));

        $op .= html_writer::end_tag('div');
        $op .= html_writer::end_tag('li');

        return $op;
    }


    /**
     * Generate the content to displayed on the right part of a section
     * before course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return string HTML to output.
     */
    protected function section_right_content($section, $course, $onsectionpage) {
        // $o = $this->output->spacer();
        $op = html_writer::start_tag('div', array('class' => 'box-tools pull-right section-actions'));
        $controls = $this->section_edit_control_items($course, $section, $onsectionpage);
        $op .= $this->section_edit_control_menu($controls, $course, $section);
        $op .= html_writer::end_tag('div');
        return $op;
    }

    /**
     * Generate the content to displayed on the left part of a section
     * before course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return string HTML to output.
     */
    protected function section_left_content($section, $course, $onsectionpage) {
        $onsectionpage = $onsectionpage;
        // $o = $this->output->spacer();
        $op = html_writer::start_tag('h3', array('class' => 'box-title'));
        if ($section->section != 0) {
            // Only in the non-general sections.
            if (course_get_format($course)->is_section_current($section)) {
                $op = get_accesshide(get_string('currentsection', 'format_'.$course->format));
            }
        }
        $op .= html_writer::end_tag('h3');
        return $op;
    }

        /**
         * Generate the starting container html for a list of sections
         * @return string HTML to output.
         */
    protected function start_section_list() {
        global $COURSE;
        $op = $this->change_num_sections($COURSE);
        $op .= html_writer::start_tag('ul', array('class' => 'topics'));
        return $op;
    }

            /**
             * Render a form to create a new course section, prompting for basic info.
             *
             * @return string
             */
    private function change_num_sections($course) {

        $course = course_get_format($course)->get_course();
        $context = context_course::instance($course->id);
        
        $url = new moodle_url('/theme/remui/index.php', array(
            'sesskey'  => sesskey(),
            'action' => 'addsection',
            'contextid' => $context->id,
        ));

        $required = '';
        if ($course->format === 'topics') {
            $required = 'required';
        }

        $heading = get_string('addanewsection', 'theme_remui');

        $output = html_writer::start_tag('div', array('class' => 'modal fade', 'id' => 'myModal', 'role' => 'dialog'));
        $output .= html_writer::start_tag('div', array('class' => 'modal-dialog'));
        $output .= html_writer::start_tag('div', array('class' => 'modal-content'));
        $output .= html_writer::start_tag('form', array(
                'method' => 'post',
                'action' => $url->out_omit_querystring()
                ));
        $output .= html_writer::start_tag('div', array('class' => 'modal-header'));
        $output .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
        $output .= "<h4 class='modal-title'>$heading</h4>";
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'modal-body'));
        $output .= html_writer::input_hidden_params($url);
        $output .= html_writer::start_tag('div', array('class' => 'form-group'));
        $output .= html_writer::label(get_string('title', 'theme_remui'), 'newsection', false, array('class' => 'sr-only'));
        $output .= html_writer::empty_tag('input',
         array('type' => 'text',
            'class' => 'h3', 'id' => 'newsection', 'maxlength' => '250', 'name' => 'newsection',
            'required' => $required, 'placeholder' => get_string('title', 'theme_remui') ));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'form-group'));
        $output .= html_writer::label(get_string('contents', 'theme_remui'), 'summary');
        $output .= print_textarea(true, 10, 150, "100%", "auto", "summary", '', $course->id, true);
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'modal-footer'));
        $output .= html_writer::empty_tag('input', array(
        'type' => 'submit',
        'name' => 'addtopic',
        'value' => get_string('createsection', 'theme_remui'),
        ));
        $output .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $output .= html_writer::empty_tag('input', array(
        'class' => 'btn btn-default',
        'data-dismiss' => 'modal',
        'type' => 'button',
        'name' => 'addtopic',
        'value' => 'Close',
        ));
        $output  .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('form');
        $output  .= html_writer::end_tag('div');

        $output  .= html_writer::end_tag('div');
        $output  .= html_writer::end_tag('div');
        $output  .= '<div class="row"> <div class="col-lg-12 pad text-right">';
        if (has_capability('moodle/course:update', $context)) { // If not a admin or techer then Don't show the 'add section' button.
            $output  .= '<button class="btn btn-default" data-toggle="modal" data-target="#myModal" type="button">
                        <i class="fa fa-plus"></i>&nbsp&nbsp '.get_string("addsection", "theme_remui").'
                    </button>';
        }
        $output  .= '<button class="btn btn-default toggle-section-btn" type="button">
                        <span>'.get_string("showsections", "theme_remui").'</span>
                        &nbsp&nbsp <i class="fa fa-angle-down"></i>
                    </button>
                    </div> </div>';

        return $output;
    }
     /**
      * Generate the display of the header part of a section before
      * course modules are included
      *
      * @param stdClass $section The course_section entry from DB
      * @param stdClass $course The course entry from DB
      * @param bool $onsectionpage true if being printed on a single-section page
      * @param int $sectionreturn The section to return to after an action
      * @return string HTML to output.
      */
    protected function section_header($section, $course, $onsectionpage, $sectionreturn=null) {
        global $PAGE;
        $sectionreturn = $sectionreturn;
        $op = '';
        // $currenttext = '';
        $sectionstyle = '';

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            } else if (course_get_format($course)->is_section_current($section)) {
                $sectionstyle = ' current';
            }
        }

        $op .= html_writer::start_tag('li', array('id' => 'section-'.$section->section,
            'class' => 'section main box clearfix'.$sectionstyle, 'role' => 'region',
            'aria-label' => get_section_name($course, $section)));

        // Create a span that contains the section title to be used to create the keyboard section move menu.
        $op .= html_writer::tag('span', $this->section_title($section, $course), array('class' => 'hidden sectionname'));

        $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
        $rightcontent = $this->section_right_content($section, $course, $onsectionpage);

        if ($PAGE->user_is_editing()) {
            $op .= html_writer::start_tag('div', array('class' => 'box-header nopadding'));

            $op .= $leftcontent;
            $op .= $rightcontent;

            $op .= html_writer::end_tag('div');

        }

        $op .= html_writer::start_tag('div', array('class' => 'content box-body row'));
         // Get the data for section with all the text, images and other data field.
        $op .= $this->format_summary($section, $course, $onsectionpage);
        $modinfo = get_fast_modinfo($course);
        if (!empty($modinfo->sections[$section->section])) {
            // right
            $op .= html_writer::start_tag('div', array('class' => 'toggle-btn-wrapper col-xs-12'));
            $op .= '<button class="material-button material-button-toggle closed" type="button">
                        <i class="fa fa-angle-down fa-lg"> </i>&nbsp&nbsp <span>'.get_string("showsection", "theme_remui").' </span>
                    </button>';
            $op .= html_writer::end_tag('div');
        }
        return $op;
    }

    /**
     * Extract first image from html
     *
     * @param string $html (must be well formed)
     * @return array | bool (false)
     */
    public static function extract_first_image($html) {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true); // Required for HTML5.
        $doc->loadHTML($html);
        libxml_clear_errors(); // Required for HTML5.
        $imagetags = $doc->getElementsByTagName('img');
        if ($imagetags->item(0)) {
            $src = $imagetags->item(0)->getAttribute('src');
            $alt = $imagetags->item(0)->getAttribute('alt');
            return array('src' => $src, 'alt' => $alt);
        } else {
            return false;
        }
    }

    /**
     * Generate the display of the footer part of a section
     *
     * @return string HTML to output.
     */
    protected function section_footer() {
        $op = html_writer::end_tag('div');
        $op .= html_writer::end_tag('li');

        return $op;
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the edit control action menu
     *
     * @param array $controls The edit control items from section_edit_control_items
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @return string HTML to output.
     */
    protected function section_edit_control_menu($controls, $course, $section) {
        $course = $course;
        $section = $section;
        $op = "";
        if (!empty($controls)) {

            $op .= html_writer::start_tag('div', array('class' => "btn-group"));

            foreach ($controls as $value) {
                $url = empty($value['url']) ? '' : $value['url'];
                $icon = empty($value['icon']) ? '' : $value['icon'];
                $nametitle = empty($value['name']) ? '' : $value['name'];
                $name = "";
                // $attr = empty($value['attr']) ? array() : $value['attr'];
                $class = empty($item['pixattr']['class']) ? '' : $item['pixattr']['class'];
                $alt = empty($item['pixattr']['alt']) ? '' : $item['pixattr']['alt'];
                
                $pixicon = $this->render(new pix_icon($icon, $name, null, array('class' => "smallicon " . $class, 'alt' => $alt)));

                $link = html_writer::link($url, $pixicon, array('class' => 'btn btn-box-tool ', 'data-toggle' => 'tooltip', 'title' => $nametitle));
                
                $op .= $link;
            }
            $op .= html_writer::end_tag('div');
        }

        return $op;
    }

    /**
     * Generate html for a section summary text
     *
     * @param stdClass $section The course_section entry from DB
     * @return string HTML to output.
     */
    protected function format_summary($section, $course, $onsectionpage) {
        global $CFG;
        // When not on a section page, we display the section titles except the general section if null
        $hasnamenotsecpg = (!$onsectionpage && ($section->section != 0 || !is_null($section->name)));

        // When on a section page, we only display the general section title, if title is not the default one
        $hasnamesecpg = ($onsectionpage && ($section->section == 0 && !is_null($section->name)));

        $classes = ' ';
        if ($hasnamenotsecpg || $hasnamesecpg) {
            $classes = '';
        }

        if ($onsectionpage) {
            $sectionname = get_section_name($course, $section);
        } else {
            $sectionname = $this->section_title($section, $course);
        }
        $context = context_course::instance($section->course);
        $summarytext = file_rewrite_pluginfile_urls($section->summary, 'pluginfile.php',
           $context->id, 'course', 'section', $section->id);

        $options = new stdClass();
        $options->noclean = false;
        $options->overflowdiv = false;
        $format_summarytext = format_text($summarytext, $section->summaryformat, $options);

        $op  = html_writer::start_tag('div', array('class' => 'clearfix'));

        if (get_config('theme_remui', 'enableimgsinglecourse') === "1") {
        if (!empty($summarytext)) {
                $image = $this->extract_first_image($summarytext);
            if ($image) {
                    $imagesrc = $image['src'];
                    $imagealt = $image['alt'];
            } else {
                    $imagesrc = $CFG->wwwroot.'/theme/remui/data/no_pic_100X100.png';
                    $imagealt = get_string('defaultimg', 'theme_remui');
            }
        } else {
            $imagesrc = $CFG->wwwroot.'/theme/remui/data/no_pic_100X100.png';
            $imagealt = get_string('defaultimg', 'theme_remui');
            $summarytext = "<div>".get_string('nosummary', 'theme_remui')."</div>";
        }
        // To remove the first image from the html as we are adding it(first image) to the media-left class
        $formatedext = preg_replace('/<p[^>]*><\\/p[^>]*>/', '', preg_replace('/<img[\S\s]*?>/i', '', $format_summarytext, 1), 1);
        // left Customised
        $op .= html_writer::start_tag('div', array('class' => 'wdm_img col-xs-12 col-sm-3 col-md-3 col-lg-2 hidden-xs'));
        $op .= html_writer::empty_tag('img', array('class' => 'media-left-img', 'src' => $imagesrc, 'alt' => $imagealt));
           // $op .= '<a href="#"><img class="media-object" src="http://placehold.it/100x100" alt="image"></a>';
        $op .= html_writer::end_tag('div');
         // body
        $op .= html_writer::start_tag('div', array('class' => 'wdm_content col-xs-12 col-sm-9 col-md-9 col-lg-10'));
        $op .= $this->output->heading($sectionname, 4, 'sectionname  media-heading' . $classes);
        $op .= $formatedext;
      } else {
        $op .= html_writer::start_tag('div', array('class' => 'wdm_content col-xs-12 col-sm-12 col-md-12 col-lg-12'));
        $op .= $this->output->heading($sectionname, 4, 'sectionname  media-heading' . $classes);
        $op .= $format_summarytext;
      }
        // we don't need to call this function now $this->format_summary_text($section);
        $op .= html_writer::end_tag('div');
        $op .= html_writer::end_tag('div');
        return $op;
    }

    /**
     * Generate html for a section summary text
     *
     * @param stdClass $section The course_section entry from DB
     * @return string HTML to output.
     */
     protected function format_summary_text($section) {
        $context = context_course::instance($section->course);
        $summarytext = file_rewrite_pluginfile_urls($section->summary, 'pluginfile.php',
            $context->id, 'course', 'section', $section->id);

        $options = new stdClass();
        $options->noclean = true;
        $options->overflowdiv = true;
        return format_text($summarytext, $section->summaryformat, $options);
    }

     /**
      * single section mode
      *
      * @param stdClass $course The course entry from DB
      * @param array $sections (argument not used)
      * @param array $mods (argument not used)
      * @param array $modnames (argument not used)
      * @param array $modnamesused (argument not used)
      * @param int $displaysection The section number in the course which is being displayed
      */
    function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {
        // return $this->print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection);
            $sections = $sections;
            $mods = $mods;
            $modnames = $modnames;
            $modnamesused = $modnamesused;
        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        // Can we view the section in question?
        // if (!($sectioninfo = $modinfo->get_section_info($displaysection))) {
        if (!($modinfo->get_section_info($displaysection))) {
            // This section doesn't exist
            print_error('unknowncoursesection', 'error', null, $course->fullname);
            return;
        }

        // Start single-section div
        echo html_writer::start_tag('div', array('class' => 'single-section'));

        // The requested section page.
        $thissection = $modinfo->get_section_info($displaysection);

        // Title with section navigation links.
        $sectionnavlinks = $this->get_nav_links($course, $modinfo->get_section_info_all(), $displaysection);
        $sectiontitle = '';
        $sectiontitle .= html_writer::start_tag('div', array('class' => 'section-navigation navigationtitle'));
        $sectiontitle .= html_writer::tag('span', $sectionnavlinks['previous'], array('class' => 'mdl-left'));
        $sectiontitle .= html_writer::tag('span', $sectionnavlinks['next'], array('class' => 'mdl-right'));

        // Title attributes
        $classes = 'sectionname';
        if (!$thissection->visible) {
            $classes .= ' dimmed_text';
        }
        // $sectionname = html_writer::tag('span', get_section_name($course, $displaysection));
        // $sectiontitle .= $this->output->heading($sectionname, 3, $classes);

        $sectiontitle .= html_writer::end_tag('div');
        echo $sectiontitle;
        echo html_writer::tag('div', $this->section_nav_selection($course, $sections, $displaysection),
            array('class' => 'mdl-align'));
        // Now the list of sections..
        echo $this->start_section_list();

        echo $this->section_header($thissection, $course, true, $displaysection);
        // Show completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();

        echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
        echo $this->courserenderer->course_section_add_cm_control($course, $displaysection, $displaysection);
        echo $this->section_footer();
        echo $this->end_section_list();

        // Display section bottom navigation.
        $sectionbottomnav = '';
        $sectionbottomnav .= html_writer::start_tag('div', array('class' => 'section-navigation mdl-bottom'));
        $sectionbottomnav .= html_writer::tag('span', $sectionnavlinks['previous'], array('class' => 'mdl-left'));
        $sectionbottomnav .= html_writer::tag('span', $sectionnavlinks['next'], array('class' => 'mdl-right'));
        $sectionbottomnav .= html_writer::end_tag('div');
        echo $sectionbottomnav;

        // Close single-section div.
        echo html_writer::end_tag('div');
    }

}