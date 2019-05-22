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
 * @author Brian Quinn <brian@learningpool.com>
 * @author Finbar Tracey <finbar@learningpool.com>
 * @package block_shezar_report_table
 */
define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');

// Check access.
require_login();
require_sesskey();

$reportid = required_param('reportid', PARAM_INT);

$params = array('reportid' => $reportid, 'ispublic' => 1);

$options = $DB->get_records_menu('report_builder_saved', $params, 'id', 'id, name');

echo $OUTPUT->header();
echo json_encode($options);
