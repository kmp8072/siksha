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
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @package shezar
 * @subpackage plan
 */

/**
 * Generate header including plan details
 *
 * Only included via development_plan::print_header()
 *
 * The following variables will be set:
 *
 * - $this              Plan instance
 * - $CFG               Config global
 * - $currenttab        Current tab
 * - $navlinks          Additional breadcrumbs (optional)
 */
global $PAGE, $OUTPUT, $SITE;
(defined('MOODLE_INTERNAL') && isset($this)) || die();
require_once($CFG->dirroot.'/shezar/core/js/lib/setup.php');

// Check if this is a component
if (array_key_exists($currenttab, $this->get_components())) {
    $component = $this->get_component($currenttab);
    $is_component = true;
}
else {
    $is_component = false;
}

$fullname = $this->name;
if ($is_component) {
    $titleargs = new stdclass();
    $titleargs->name = $fullname;
    $titleargs->tab = get_string($currenttab . 'plural', 'shezar_plan');
    $pagetitle = format_string(get_string('learningplan:name:tab', 'shezar_plan', $titleargs));
} else {
    $pagetitle = format_string(get_string('learningplan', 'shezar_plan').': '.$fullname);
}

$PAGE->set_title($pagetitle);
$PAGE->set_heading(format_string($SITE->fullname));

// Plan menu
dp_display_plans_menu($this->userid, $this->id, $this->role);

echo $OUTPUT->header();

// Run post header hook (if this is a component)
if ($is_component) {
    $component->post_header_hook();
}

// Plan page content
echo $OUTPUT->container_start('', 'dp-plan-content');

echo $this->display_plan_message_box();

$heading = html_writer::tag('span', get_string('plan', 'shezar_plan') . ':', array('class' => 'dp-plan-prefix'));
echo $OUTPUT->heading($heading . ' ' . $fullname);

print $this->display_tabs($currenttab);

if ($printinstructions) {
    //
    // Display instructions
    //
    $instructions = '';
    if ($this->role == 'manager') {
        $instructions .= get_string($currenttab.'_instructions_manager', 'shezar_plan') . ' ';
    } else {
        $instructions .= get_string($currenttab.'_instructions_learner', 'shezar_plan') . ' ';
    }

    // If this a component
    if ($is_component) {
        $instructions .= get_string($currenttab.'_instructions_detail', 'shezar_plan') . ' ';
        if ($component->get_setting('update'.$currenttab) > DP_PERMISSION_DENY) {
            if (!$this->is_active()) {
                $instructions .= get_string($currenttab . '_instructions_add11', 'shezar_plan') . ' ';
            } else {
                $instructions .= get_string($currenttab . '_instructions_request', 'shezar_plan') . ' ';
            }
        }
    }

    print $OUTPUT->container($instructions, "instructional_text");
}
