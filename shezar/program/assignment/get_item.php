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
 * @author Ben Lobo <ben.lobo@kineo.com>
 * @package shezar
 * @subpackage program
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot.'/shezar/program/lib.php');

$PAGE->set_context(context_system::instance());
require_login();

$cat = required_param('cat', PARAM_ALPHA); // The category name, such as positions, organisations
$itemids = required_param_array('itemid', PARAM_INT);
$programid = required_param('programid', PARAM_INT);

$program = new program($programid);
require_capability('shezar/program:configureassignments', $program->get_context());
$program->check_enabled();

$classname = "{$cat}_category";

if (!class_exists($classname) || !is_subclass_of($classname, 'prog_assignment_category')) {
    throw new moodle_exception('error', '', '', null, 'invalid classname');
}

/** @var prog_assignment_category $category */
$category = new $classname();

$items = array();
$rows = array();
$users = 0;
foreach ($itemids as $itemid) {
    $item = $category->get_item(intval($itemid));
    $users += $category->user_affected_count($item);
    $items[] = $item;

    $row = $category->build_row($item);
    $rowhtml = '<tr>';
    $colcount = 0;
    foreach ($row as $cell) {
        $rowhtml .= '<td class="col'.$colcount.'">'.$cell.'</td>';
        $colcount++;
    }
    $rowhtml .= '</tr>';
    $rows[] = $rowhtml;
}

// Build the html to display in the confirmation dialog
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
$html = get_string('youhaveadded', 'shezar_program', $a);

$data = array(
    'html'      => $html,
    'rows'      => $rows
);
echo json_encode($data);
