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
 * @author Maria Torres <maria.torres@shezarlms.com>
 * @package shezar
 * @subpackage cohort/rules
 */
/**
 * This class is an ajax back-end for updating operators AND/OR
 */
define('AJAX_SCRIPT', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot.'/cohort/lib.php');

$id = required_param('id', PARAM_INT);
$type = required_param('type', PARAM_INT);
$value = required_param('value', PARAM_INT);
$cohortid = required_param('cohortid', PARAM_INT);

require_login();
require_sesskey();

$cohort = $DB->get_record('cohort', array('id' => $cohortid));
$context = context::instance_by_id($cohort->contextid, MUST_EXIST);
require_capability('shezar/cohort:managerules', $context);

$result = shezar_cohort_update_operator($cohortid, $id, $type, $value);
if ($type === COHORT_OPERATOR_TYPE_COHORT) {
    echo json_encode(array('action' => 'updcohortop', 'ruleid' => $id, 'value' => $value, 'result' => $result));
} else if ($type === COHORT_OPERATOR_TYPE_RULESET) {
    echo json_encode(array('action' => 'updrulesetop', 'ruleid' => $id, 'value' => $value, 'result' => $result));
}

exit();
