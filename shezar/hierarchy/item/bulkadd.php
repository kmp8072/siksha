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
 * @subpackage shezar_hierarchy
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/shezar/hierarchy/item/bulkadd_form.php');
require_once($CFG->dirroot.'/shezar/hierarchy/lib.php');

///
/// Setup / loading data
///

$prefix = required_param('prefix', PARAM_ALPHA);
$shortprefix = hierarchy::get_short_prefix($prefix);

$frameworkid = required_param('frameworkid', PARAM_INT);
$page       = optional_param('page', 0, PARAM_INT);

// Check hierarchy item is enabled.
hierarchy::check_enable_hierarchy($prefix);

$hierarchy = hierarchy::load_hierarchy($prefix);

// Make this page appear under the manage competencies admin item
admin_externalpage_setup($prefix.'manage', '', array('prefix' => $prefix));

$context = context_system::instance();

require_capability('shezar/hierarchy:create'.$prefix, $context);

// Load framework
if (!$framework = $DB->get_record($shortprefix.'_framework', array('id' => $frameworkid))) {
    print_error('invalidframeworkid', 'shezar_hierarchy', $prefix);
}


///
/// Display page
///

// create form
$mform = new item_bulkadd_form(null, compact('prefix', 'frameworkid', 'page'));

// cancelled
if ($mform->is_cancelled()) {

    redirect("{$CFG->wwwroot}/shezar/hierarchy/index.php?prefix=$prefix&amp;frameworkid={$frameworkid}&amp;page={$page}");

// Update data
} else if ($formdata = $mform->get_data()) {

    $error = '';
    $items_to_add = hierarchy::construct_items_to_add($formdata, $error);
    if (!$items_to_add) {
        shezar_set_notification(get_string('bulkaddfailed', 'shezar_hierarchy', $error), "{$CFG->wwwroot}/shezar/hierarchy/index.php?prefix=$prefix&amp;frameworkid={$frameworkid}&amp;page={$page}");
    }

    if ($new_ids = $hierarchy->add_multiple_hierarchy_items($formdata->parentid, $items_to_add, $frameworkid)) {
        shezar_set_notification(get_string('bulkaddsuccess', 'shezar_hierarchy', count($new_ids)), "{$CFG->wwwroot}/shezar/hierarchy/index.php?prefix=$prefix&amp;frameworkid={$frameworkid}&amp;page={$page}", array('class' => 'notifysuccess'));
    } else {
        shezar_set_notification(get_string('bulkaddfailed', 'shezar_hierarchy'), "{$CFG->wwwroot}/shezar/hierarchy/index.php?prefix=$prefix&amp;frameworkid={$frameworkid}&amp;page={$page}");
    }
}

$PAGE->navbar->add(get_string("{$prefix}frameworks", 'shezar_hierarchy'), new moodle_url('/shezar/hierarchy/framework/index.php', array('prefix' => $prefix)));
$PAGE->navbar->add(format_string($framework->fullname), new moodle_url('/shezar/hierarchy/index.php', array('prefix' => $prefix, 'frameworkid' => $framework->id)));
$PAGE->navbar->add(get_string('addmultiplenew'.$prefix, 'shezar_hierarchy'));

/// Display page header
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('addmultiplenew'.$prefix, 'shezar_hierarchy'));

/// Finally display the form
$mform->display();

echo $OUTPUT->footer();

