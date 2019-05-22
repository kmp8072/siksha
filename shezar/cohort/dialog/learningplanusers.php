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
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @author Simon Player <simon.player@shezarlearning.com>
 * @package shezar_cohort
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot.'/cohort/lib.php');

defined('MOODLE_INTERNAL') || die();

$PAGE->set_context(context_system::instance());
require_login();
require_capability('moodle/cohort:manage', context_system::instance());

$config = \shezar_cohort\learning_plan_config::get_config(required_param('id', PARAM_INT));
$config->plantemplateid = required_param('plantemplate', PARAM_INT);
$config->excludecreatedmanual = required_param('manual', PARAM_BOOL);
$config->excludecreatedauto = required_param('auto', PARAM_BOOL);
$config->excludecompleted = required_param('complete', PARAM_BOOL);
// If users are not excluded from previous auto plan creation we do not want autocreate to be true.
$config->autocreatenew = ($config->excludecreatedauto == 0) ? 0 : required_param('autocreatenew', PARAM_BOOL);

$html = '';

// Display text on number of users affected for initial plan creation.
$count = \shezar_cohort\learning_plan_helper::get_affected_users($config, true);
if ($count > 0) {
    $html .= html_writer::tag('p', get_string('confirmcreateplansmessage', 'shezar_cohort', $count));
} else {
    $html .= html_writer::tag('p', get_string('confirmnousers', 'shezar_cohort'));
}

// Display text on auto creation of plans when nes members are added to teh audience.
if ($config->autocreatenew == 1) {
    $html .= html_writer::tag('p', get_string('autocreatenewon', 'shezar_cohort'));
} else {
    $html .= html_writer::tag('p', get_string('autocreatenewoff', 'shezar_cohort'));
}

$html .= html_writer::tag('p', get_string('continue', 'shezar_cohort'));

echo json_encode(array('html' => $html));
