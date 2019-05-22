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
 * @author Dan Marsden <dan@catalyst.net.nz>
 * @package shezar
 * @subpackage blocks_shezar_stats
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/blocks/shezar_stats/locallib.php');
if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('block_shezar_stats_minutesbetweensession', get_string('minutesbetweensession', 'block_shezar_stats'),
                       get_string('minutesbetweensessiondesc', 'block_shezar_stats'), 30, PARAM_INT));
    $settings->add(new admin_setting_configtime('block_shezar_stats_sche_hour', 'block_shezar_stats_sche_minute', get_string('executeat'),
                                                 get_string('executeathelp', 'block_shezar_stats'), array('h' => 0, 'm' => 0)));
}
?>