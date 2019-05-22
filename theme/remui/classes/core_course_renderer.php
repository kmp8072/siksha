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
 * Core course renderer
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs. (http://www.wisdmlabs.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class theme_remui_core_course_renderer extends core_course_renderer {

    protected function coursecat_coursebox(coursecat_helper $chelper, $course, $additionalclasses = '') {
        global $CFG;
        if (!isset($this->strings->summary)) {
            $this->strings->summary = get_string('summary');
        }
        if ($chelper->get_show_courses() <= self::COURSECAT_SHOW_COURSES_COUNT) {
            return '';
        }
        if ($course instanceof stdClass) {
            require_once($CFG->libdir. '/coursecatlib.php');
            $course = new course_in_list($course);
        }
        $content = '';
        $classes = trim('panel panel-default coursebox clearfix '. $additionalclasses);
        if ($chelper->get_show_courses() < self::COURSECAT_SHOW_COURSES_EXPANDED) {
            $classes .= ' collapsed';
        }
        // Start .coursebox div.
        $content .= html_writer::start_tag('div', array(
            'class' => $classes,
            'data-courseid' => $course->id,
            'data-type' => self::COURSECAT_TYPE_COURSE,
        ));
        $content .= html_writer::start_tag('div', array('class' => 'panel-heading info'));
        // Course name.
        $coursename = $chelper->get_course_formatted_name($course);
        $coursenamelink = html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)),
                                            $coursename, array('class' => $course->visible ? '' : 'dimmed'));
        $content .= html_writer::tag('span', $coursenamelink, array('class' => 'coursename'));
        // If we display course in collapsed form but the course has summary or course contacts, display the link to the info page.
        $content .= html_writer::start_tag('span', array('class' => 'moreinfo'));
        if ($chelper->get_show_courses() < self::COURSECAT_SHOW_COURSES_EXPANDED) {
            if ($course->has_summary() || $course->has_course_contacts() || $course->has_course_overviewfiles()) {
                $url = new moodle_url('/course/info.php', array('id' => $course->id));
                $image = html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/info'),
                    'alt' => $this->strings->summary));
                $content .= html_writer::link($url, $image, array('title' => $this->strings->summary));
                // Make sure JS file to expand course content is included.
                $this->coursecat_include_js();
            }
        }
        $content .= html_writer::end_tag('span'); // End .moreinfo span.
        // Print enrolmenticons.
        if ($icons = enrol_get_course_info_icons($course)) {
            $content .= html_writer::start_tag('div', array('class' => 'enrolmenticons'));
            foreach ($icons as $pixicon) {
                $content .= $this->render($pixicon);
            }
            $content .= html_writer::end_tag('div'); // End .enrolmenticons div.
        }
        $content .= html_writer::end_tag('div'); // End .info div.
        $content .= html_writer::start_tag('div', array('class' => 'content panel-body'));
        $content .= $this->coursecat_coursebox_content($chelper, $course);
        $content .= html_writer::end_tag('div'); // End .content div.
        $content .= html_writer::end_tag('div'); // End .coursebox div.
        return $content;
    }
    protected function coursecat_coursebox_content(coursecat_helper $chelper, $course) {
        global $CFG;
        if ($chelper->get_show_courses() < self::COURSECAT_SHOW_COURSES_EXPANDED) {
            return '';
        }
        if ($course instanceof stdClass) {
            require_once($CFG->libdir. '/coursecatlib.php');
            $course = new course_in_list($course);
        }
        $content = '';
        // Display course overview files.
        $contentimages = $contentfiles = '';
        foreach ($course->get_course_overviewfiles() as $file) {
            $isimage = $file->is_valid_image();
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                    '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                    $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
            if ($isimage) {
                    $contentimages .= html_writer::start_tag('div', array('class' => 'imagebox'));
                    $images = html_writer::empty_tag('img', array('src' => $url, 'alt' => 'Course Image '. $course->fullname,
                        'class' => 'courseimage'));
                    $contentimages .= html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)), $images);
                    $contentimages .= html_writer::end_tag('div');
            } else {
                $image = $this->output->pix_icon(file_file_icon($file, 24), $file->get_filename(), 'moodle');
                $filename = html_writer::tag('span', $image, array('class' => 'fp-icon')).
                        html_writer::tag('span', $file->get_filename(), array('class' => 'fp-filename'));
                $contentfiles .= html_writer::tag('span',
                        html_writer::link($url, $filename),
                        array('class' => 'coursefile fp-filename-icon'));
            }
        }
        $content .= $contentimages. $contentfiles;
        // Display course summary.
        if ($course->has_summary()) {
            $content .= $chelper->get_course_formatted_summary($course);
        }
        // Display course contacts. See course_in_list::get_course_contacts().
        if ($course->has_course_contacts()) {
            $content .= html_writer::start_tag('ul', array('class' => 'teachers'));
            foreach ($course->get_course_contacts() as $userid => $coursecontact) {
                $name = $coursecontact['rolename'].': '.
                        html_writer::link(new moodle_url('/user/view.php',
                                array('id' => $userid, 'course' => SITEID)),
                            $coursecontact['username']);
                $content .= html_writer::tag('li', $name);
            }
            $content .= html_writer::end_tag('ul'); // End .teachers div.
        }
        // Display course category if necessary (for example in search results).
        if ($chelper->get_show_courses() == self::COURSECAT_SHOW_COURSES_EXPANDED_WITH_CAT) {
            require_once($CFG->libdir. '/coursecatlib.php');
            if ($cat = coursecat::get($course->category, IGNORE_MISSING)) {
                $content .= html_writer::start_tag('div', array('class' => 'coursecat'));
                $content .= get_string('category').': '.
                        html_writer::link(new moodle_url('/course/index.php', array('categoryid' => $cat->id)),
                                $cat->get_formatted_name(), array('class' => $cat->visible ? '' : 'dimmed'));
                $content .= html_writer::end_tag('div'); // End .coursecat div.
            }
        }
        return $content;
    }

    /**
     * Renders HTML to display one course module in a course section
     *
     * This includes link, content, availability, completion info and additional information
     * that module type wants to display (i.e. number of unread forum posts)
     *
     * This function calls:
     * {@link core_course_renderer::course_section_cm_name()}
     * {@link core_course_renderer::course_section_cm_text()}
     * {@link core_course_renderer::course_section_cm_availability()}
     * {@link core_course_renderer::course_section_cm_completion()}
     * {@link course_get_cm_edit_actions()}
     * {@link core_course_renderer::course_section_cm_edit_actions()}
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) {
        global $CFG;
        $output = '';
        // We return empty string (because course module will not be displayed at all)
        // if:
        // 1) The activity is not visible to users
        // and
        // 2) The 'availableinfo' is empty, i.e. the activity was
        // hidden in a way that leaves no info, such as using the
        // eye icon.
        if (!$mod->uservisible && empty($mod->availableinfo)) {
            return $output;
        }

        $indentclasses = 'mod-indent';
        if (!empty($mod->indent)) {
            $indentclasses .= ' mod-indent-'.$mod->indent;
            if ($mod->indent > 15) {
                $indentclasses .= ' mod-indent-huge';
            }
        }

        $output .= html_writer::start_tag('div');

        if ($this->page->user_is_editing()) {
            $output .= course_get_cm_move($mod, $sectionreturn);
        }

        $output .= html_writer::start_tag('div', array('class' => 'mod-indent-outer'));

        // This div is used to indent the content.
        $output .= html_writer::div('', $indentclasses);

        // Start a wrapper for the actual content to keep the indentation consistent
        $output .= html_writer::start_tag('div');

        // Display the link to the module (or do nothing if module has no url)
        $cmname = $this->course_section_cm_name($mod, $displayoptions);

        if (!empty($cmname)) {
            // Start the div for the activity title, excluding the edit icons.
            $output .= html_writer::start_tag('div', array('class' => 'activityinstance'));
            $output .= $cmname;
            // Checking Moodle version for theme compatibility.
            if ($CFG->version / 1000000 < 2016 ) {
                if ($this->page->user_is_editing()) {
                    $output .= ' ' . course_get_cm_rename_action($mod, $sectionreturn);
                }
            }

            // Module can put text after the link (e.g. forum unread)
            $output .= $mod->afterlink;

            // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
            $output .= html_writer::end_tag('div'); // .activityinstance
        }

        // If there is content but NO link (eg label), then display the
        // content here (BEFORE any icons). In this case cons must be
        // displayed after the content so that it makes more sense visually
        // and for accessibility reasons, e.g. if you have a one-line label
        // it should work similarly (at least in terms of ordering) to an
        // activity.
        $contentpart = $this->course_section_cm_text($mod, $displayoptions);
        $url = $mod->url;
        if (empty($url)) {
            $output .= $contentpart;
        }

        $modicons = '';
        if ($this->page->user_is_editing()) {
            $editactions = course_get_cm_edit_actions($mod, $mod->indent, $sectionreturn);
            $modicons .= ' '. $this->course_section_cm_edit_actions($editactions, $mod, $displayoptions);
            $modicons .= $mod->afterediticons;
        }

        $modicons .= $this->course_section_cm_completion($course, $completioninfo, $mod, $displayoptions);

        if (!empty($modicons)) {
            $output .= html_writer::span($modicons, 'actions');
        }

        // If there is content AND a link, then display the content here
        // (AFTER any icons). Otherwise it was displayed before
        if (!empty($url)) {
            $output .= $contentpart;
        }

        // show availability info (if module is not available)
        $output .= $this->course_section_cm_availability($mod, $displayoptions);

        $output .= html_writer::end_tag('div'); // $indentclasses

        // End of indentation div.
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');
        return $output;
    }
    
    /**
     * Renders html to display a course search form
     *
     * @param string $value default value to populate the search field
     * @param string $format display format - 'plain' (default), 'short' or 'navbar'
     * @return string
     */
    public function course_search_form($value = '', $format = 'plain') {
        static $count = 0;
        $formid = 'coursesearch';
        if ((++$count) > 1) {
            $formid .= $count;
        }

        switch ($format) {
            case 'navbar' :
                $formid = 'coursesearchnavbar';
                $inputid = 'navsearchbox';
                $inputsize = 20;
                break;
            case 'short' :
                $inputid = 'shortsearchbox';
                $inputsize = 12;
                break;
            default :
                $inputid = 'coursesearchbox';
                $inputsize = 20;
        }

        $strsearchcourses = get_string("searchcourses");

        $searchurl = new moodle_url('/course/index.php');
        $category = optional_param('category', 0, PARAM_INT);
        $output  = html_writer::start_tag('div', array('class' => 'course-search-form pad'));
        $output .= html_writer::start_tag('form', array('id' => $formid, 'action' => $searchurl, 'method' => 'get', 'class' => 'sidebar-form', 'style' => 'margin: 0;'));
        $output .= html_writer::start_tag('div', array('class' => 'input-group'));
        $output .= html_writer::empty_tag('input', array('type' => 'text', 'placeholder' => $strsearchcourses, 'class' => 'form-control', 'id' => $inputid,
            'size' => $inputsize, 'name' => 'search', 'value' => s($value)));
        $output .= html_writer::start_tag('span', array('class' => 'input-group-btn'));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'category', 'value' => $category));
        $output .= html_writer::start_tag('button', array('type' => 'submit', 'id' => 'search-btn', 'class' => 'btn btn-flat'));
        $output .= html_writer::start_tag('i', array('class' => 'fa fa-search'));
        $output .= html_writer::end_tag('i');
        $output .= html_writer::end_tag('button');
        $output .= html_writer::end_tag('span');
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('form');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Returns HTML to print list of available courses for the frontpage
     *
     * @return string
     */
    public function frontpage_available_courses() {
        /* available courses */
        global $CFG, $OUTPUT, $DB;
        require_once($CFG->libdir. '/coursecatlib.php');

        $chelper = new coursecat_helper();
        $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED)->set_courses_display_options(array(
                                                        'recursive' => true,
                                                        'limit' => $CFG->frontpagecourselimit,
                                                        'viewmoreurl' => new moodle_url('/course/index.php'),
                                                        'viewmoretext' => new lang_string('fulllistofcourses')));

        $chelper->set_attributes(array('class' => 'frontpage-course-list-all'));
        $courses = coursecat::get(0)->get_courses($chelper->get_courses_display_options());
        $totalcount = coursecat::get(0)->get_courses_count($chelper->get_courses_display_options());

        $courseids = array_keys($courses);
        $newcourses = get_string('availablecourses');

        $header = '<div id="frontpage-course-list">
                <h2>'.$newcourses.'</h2>
                    <div class="courses frontpage-course-list-all">
                        <div class="row-fluid"  id="course-carousel">';

        $footer = '</div>
                    </div>
            </div>';
        $coursecount = 1;
        $content = '';

        if ($ccc = get_courses('all', 'c.sortorder ASC', 'c.id,c.shortname,c.visible')) {
            $ccc = $ccc;
            foreach ($courseids as $courseid) {

                $course = get_course($courseid);
                $noimgurl = $OUTPUT->pix_url('400x300', 'theme');
                $courseurl = new moodle_url('/course/view.php', array('id' => $courseid ));

                require_once($CFG->libdir.'/coursecatlib.php');
                $clist = new course_in_list($course);
                $teachers = $clist->get_course_contacts();

                $courseteachers = '';
                if (!empty($teachers)) {

                    // Get all teacher user records in one go.
                    $teacherids = array();
                    foreach ($teachers as $teacher) {
                        $teacherids[] = $teacher['user']->id;
                    }
                    $teacherusers = $DB->get_records_list('user', 'id', $teacherids);

                    // Create string for teachers.
                    $courseteachers .= '<div class="teacher-wrapper text-center"><div class="taught-by text-muted">'.get_string("taught-by", "theme_remui").'</div>';
                    $courseteachers .= '<div id=course_teachers>';

                    foreach ($teachers as $teacher) {
                        if (!isset($teacherusers[$teacher['user']->id])) {
                            continue;
                        }
                        $teacheruser = $teacherusers [$teacher['user']->id];
                        $courseteachers .= $this->print_teacher_profile($teacheruser);
                    }
                   $courseteachers .= "</div>  </div>";
                }

                if ($course instanceof stdClass) {
                    require_once($CFG->libdir. '/coursecatlib.php');
                    $course = new course_in_list($course);
                }
                
                $imgurl = '';
                foreach ($course->get_course_overviewfiles() as $file) {
                    $isimage = $file->is_valid_image();
                    $imgurl = file_encode_url("$CFG->wwwroot/pluginfile.php",
                                                    '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                                                    $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
                    if (!$isimage) {
                        $imgurl = $noimgurl;
                    }
                }

                if (empty($imgurl)) {
                    $imgurl = $noimgurl;
                }

                $coursesummary = strip_tags($chelper->get_course_formatted_summary($course,
                    array('overflowdiv' => false, 'noclean' => false, 'para' => false)));

                $summarystring = strlen($coursesummary) > 100 ? substr($coursesummary, 0, 100) : $coursesummary;
                $content .= '<div class="card">
                        <div class="fp-coursebox">
                            <div class="fp-coursethumb card-image text-center" style="background-image: url('.$imgurl.'); background-size: cover; background-position: center; height:175px">
                            </div>
                            <div class="fp-courseinfo card-content frontpage-course text-muted">
                                <h4 class="card-title"><a href="'.$courseurl.'">'.$course->fullname.'</a></h4>
                                '.$summarystring.'<p class="read-more hide"><a href="'.$courseurl.'" class="btn btn-default btn-flat">Read More</a></p>
                            </div>
                            '. $courseteachers.'
                        </div>
                    </div>';
                $coursecount++;
            }
        }

        $coursehtml = $header.$content.$footer;
        echo $coursehtml;

        if (!$totalcount && !$this->page->user_is_editing() && has_capability('moodle/course:create', context_system::instance())) {
            // Print link to create a new course, for the 1st available category.
            echo $this->add_new_course_button();
        }
    }

    /**
     * Print teacher profile
     * Prints a media object with the techers photo, name (links to profile) and desctiption.
     *
     * @param stdClass $user
     * @return string
     */
    public function print_teacher_profile($user) {
        global $CFG;

        $userpicture = new user_picture($user);
        $userpicture->link = false;
        $userpicture->alttext = false;
        $userpicture->size = 100;
        $picture = $this->render($userpicture);

        $fullname = '<a href="'.$CFG->wwwroot.'/user/profile.php?id='.$user->id.'" title="Picture of '.format_string(fullname($user)).'">'.$picture.'</a>';

        return "<div class='wdm-media-object clearfix'>
                $fullname
                </div>";
    }

    /**
     * Front page categories list customization
     */


}