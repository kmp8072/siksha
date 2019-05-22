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
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package shezar_dashboard
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/my/lib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/shezar/dashboard/lib.php');

$id = required_param('id', PARAM_INT);
admin_externalpage_setup('shezardashboard', '', array('id' => $id),
    new moodle_url('/shezar/dashboard/layout.php'), array('pagelayout' => 'shabcreation'));

// Check shezar Dashboard is enable.
shezar_dashboard::check_feature_enabled();

$header = $SITE->shortname . ': ' . get_string('editdashboard', 'shezar_dashboard');

$PAGE->set_blocks_editing_capability('moodle/my:configsyspages');
// Override pagetype to show blocks properly.
$PAGE->set_pagetype('my-shezar-dashboard-' . $id);
$PAGE->set_subpage('default');

$dashboard = new shezar_dashboard($id);
$PAGE->navbar->add($dashboard->name);
$PAGE->set_title($header);
$PAGE->set_heading($header);
$PAGE->blocks->add_region('content');

// Reset all dashboards.
$reseturl = new moodle_url("/shezar/dashboard/manage.php", array('action' => 'reset', 'id' => $id));
$resetbutton = $OUTPUT->single_button($reseturl, get_string('resetalldashboard', 'shezar_dashboard'));

// Edit settings.
$settingsurl = new moodle_url("/shezar/dashboard/edit.php", array('id' => $id));
$settingsbutton = $OUTPUT->single_button($settingsurl, get_string('editdashboardsettings', 'shezar_dashboard'));

// Add block editing button.
if ($PAGE->user_allowed_editing()) {
    $editvalue = $PAGE->user_is_editing() ? 'off' : 'on';
    $editstring = $PAGE->user_is_editing() ? get_string('blockseditoff') : get_string('blocksediton');
    $editurl = new moodle_url('/shezar/dashboard/layout.php', array('id' => $dashboard->get_id(),
        'adminedit' => $editvalue));
    $editbutton = $OUTPUT->single_button($editurl, $editstring);
}
$PAGE->set_button($resetbutton . $settingsbutton . $editbutton);

echo $OUTPUT->header();

echo $OUTPUT->custom_block_region('content');

echo $OUTPUT->footer();
?>
<script>
$(document).ready(function(){
	$(".messages-menu").click(function(){
		$(".messages-menu").toggleClass("open");		
		});
		
		$(".notifications-menu").click(function(){
		$(".notifications-menu").toggleClass("open");	
		});
		
		$(".tasks-menu").click(function(){
		$(".tasks-menu").toggleClass("open");		
		});
		
		$(".user-menu").click(function(){
		$(".user-menu").toggleClass("open");			
		});
		
		$('.toggle-right').click(function(){
		$(".shab-menu").toggleClass("open");
		});
		
	});
</script>
