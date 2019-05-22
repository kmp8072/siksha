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
 * @subpackage hierarchy
 */

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
require_once($CFG->dirroot.'/shezar/core/utils.php');
require_once($CFG->dirroot.'/shezar/reportbuilder/filters/lib.php');
require_once($CFG->dirroot.'/shezar/reportbuilder/filters/hierarchy_multi.php');

$ids = required_param('ids', PARAM_SEQUENCE);
$ids = explode(',', $ids);
$filtername = required_param('filtername', PARAM_ALPHANUMEXT);

require_login();
$PAGE->set_context(context_system::instance());

// All hierarchy items can be viewed by any real user.
if (isguestuser()) {
    echo html_writer::tag('div', get_string('noguest', 'error'), array('class' => 'notifyproblem'));
    die;
}

// Check if Competencies are enabled.
if (shezar_feature_disabled('competencies')) {
    echo html_writer::tag('div', get_string('competenciesdisabled', 'shezar_hierarchy'), array('class' => 'notifyproblem'));
    die();
}

echo $OUTPUT->container_start('list-' . $filtername);
list($in_sql, $in_params) = $DB->get_in_or_equal($ids);
if (!empty($ids) && $items = $DB->get_records_select('comp', "id {$in_sql}", $in_params)) {
    foreach ($items as $item) {
        echo display_selected_hierarchy_item($item, $filtername);
    }
}
echo $OUTPUT->container_end();
