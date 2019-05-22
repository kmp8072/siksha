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

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/shezar/hierarchy/prefix/competency/evidenceitem/lib.php');

///
/// Setup / loading data
///

// course id
$id = required_param('id', PARAM_INT);

// competency id
$competency_id = required_param('add', PARAM_INT);

// No javascript parameters
$nojs = optional_param('nojs', false, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$s = optional_param('s', '', PARAM_TEXT);

// Check if Competencies are enabled.
competency::check_feature_enabled();

// string of params needed in non-js url strings
$nojsparams = 'nojs='.$nojs.'&amp;returnurl='.urlencode($returnurl).'&amp;s='.$s;

// Check perms
admin_externalpage_setup('competencymanage', '', array(), '/shezar/hierarchy/item/edit.php');

$sitecontext = context_system::instance();
require_capability('shezar/hierarchy:updatecompetency', $sitecontext);

// Load course
if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('incorrectcourseid', 'shezar_hierarchy');
}

// Display page
$out = '';
if ($nojs) {
    // include header/footer for non JS version
    echo $OUTPUT->header();
    $out = html_writer::tag('p', html_writer::link($returnurl, get_string('cancelwithoutassigning', 'shezar_hierarchy')));
}
$out .= html_writer::start_tag('div', array('class' => 'selectcompetencies'));
$out .= html_writer::tag('p', get_string('chooseevidencetype','shezar_hierarchy'));
$out .= html_writer::tag('h3', format_string($course->fullname));
echo $out;
comp_evitem_print_course_evitems( $course, $competency_id, "{$CFG->wwwroot}/shezar/hierarchy/prefix/competency/course/save.php?competency={$competency_id}&course={$course->id}&{$nojsparams}" );

if ($nojs) {
    // include footer for non JS version
    echo $OUTPUT->footer();
}
