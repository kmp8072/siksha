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

// require_once('/var/www/html/mdltheme.local/public/config.php');
require_once(__DIR__.'/../../config.php');
// admin_externalpage_setup('External Page');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('overview', 'theme_remui'));
$PAGE->set_heading(get_string('overview', 'theme_remui'));
$PAGE->set_url($CFG->wwwroot.'/theme/remui/about_remui.php');

echo $OUTPUT->header();
echo get_string('choosereadme', 'theme_remui');
echo $OUTPUT->footer();