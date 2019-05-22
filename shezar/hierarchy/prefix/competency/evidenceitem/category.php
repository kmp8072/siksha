<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2010 onwards shezar Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Coggins <simon.coggins@shezarlms.com>
 * @package shezar
 * @subpackage shezar_hierarchy
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/course/lib.php');


///
/// Setup / loading data
///

// category id
$id = required_param('id', PARAM_INT);

// Check if Competencies are enabled.
competency::check_feature_enabled();

// Check perms
admin_externalpage_setup('competencymanage', '', array(), '/shezar/hierarchy/item/edit.php');

$sitecontext = context_system::instance();
require_capability('shezar/hierarchy:updatecompetency', $sitecontext);

// Load category
if (!$category = $DB->get_record('course_categories', array('id' => $id))) {
    print_error('incorrectcategoryid', 'shezar_hierarchy');
}

// Load courses in category
$courses = get_courses($category->id, "c.sortorder ASC", 'c.id, c.fullname');

if ($courses) {
    $len = count($courses);
    $i = 0;
    foreach ($courses as $course) {
        $i++;

        $attr = array('id' => "course_{$course->id}");

        if ($i == $len) {
            $attr['class'] = 'last';
        }

        $list = array(html_writer::tag('span', format_string($course->fullname), array('class' => "clickable")));
        echo html_writer::alist($list, $attr);
    }
}
else {
    echo html_writer::alist(array(get_string('nocourses')));
}
