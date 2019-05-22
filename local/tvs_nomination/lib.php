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

/**
 * Add new custom options in Navigation Menu.
 *
 * @package    local_navigation
 * @author     Carlos Escobedo <http://www.twitter.com/carlosagile>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

function local_tvs_nomination_extend_navigation(global_navigation $navigation) {
    global $CFG, $PAGE, $USER , $DB;
     $ismanager = $DB->get_record_sql("SELECT id FROM {job_assignment} WHERE userid = ?",array($USER->id));
     $uid = $DB->get_records_sql("SELECT * FROM {job_assignment} WHERE managerjaid = ?",array($ismanager->id));
    $count = count($uid);
    if($count > 0){
	
        $navigation->add(get_string('name', 'local_tvs_nomination'), new moodle_url($CFG->wwwroot . '/local/tvs_nomination/dashboard.php'));
    }

}