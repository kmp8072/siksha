<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2015 onwards shezar Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package shezar_connect
 */

use \shezar_connect\util;

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot .'/course/lib.php');

require_login();
require_sesskey();

$instanceid = required_param('instanceid', PARAM_INT);
$itemids = required_param('itemid', PARAM_SEQUENCE);
$itemids = explode(',', $itemids);

$context = context_system::instance();
require_capability('shezar/connect:manage', $context);

if ($instanceid == -1) {
    $client = null;
} else {
    $client = $DB->get_record('shezar_connect_clients', array('status' => util::CLIENT_STATUS_OK, 'id' => $instanceid), '*', MUST_EXIST);
}

$PAGE->set_context($context);
$PAGE->set_url('/shezar/connect/dialog/course_item.php');

$ccourse = new shezar_connect_courses($client);

$items = array();
$rows = array();
$users = 0;
foreach ($itemids as $itemid) {
    $item = $ccourse->get_item(intval($itemid));
    $users += $ccourse->user_affected_count($item);

    $items[] = $item;
    $row = $ccourse->build_row($item);

    $rowhtml = html_writer::start_tag('tr');
    $colcount = 0;
    foreach ($row as $cell) {
        $rowhtml .= html_writer::tag('td', $cell, array('class' => 'cell' . $colcount));
        $colcount++;
    }
    $rowhtml .= html_writer::end_tag('tr');

    $rows[] = $rowhtml;
}

// Build the html to display in the confirmation dialog.
$num = count($items);
$itemnames = '';
if ($num == 1) {
    $itemnames .= '"'.$items[0]->fullname.'"';
} else {
    for ($i = 0; $i < $num; $i++) {
        // If not last item
        if ($i == 0) {
            $itemnames .= ' "'.$items[$i]->fullname.'"';
        } else if ($i != $num-1) {
            $itemnames .= ', "'.$items[$i]->fullname.'"';
        } else {
            $itemnames .= ' and "'.$items[$i]->fullname.'"';
        }
    }
}
$a = new stdClass();
$a->itemnames = $itemnames;
$a->affectedusers = $users;
$html = get_string('youhaveadded', 'shezar_cohort', $a);

$data = array(
    'html' => $html,
    'rows' => $rows
);

echo json_encode($data);
