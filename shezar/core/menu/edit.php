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
 * shezar navigation edit page.
 *
 * @package    shezar
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@shezarlms.com>
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/shezar/core/menu/edit_form.php');

// Item id.
$id    = optional_param('id', 0, PARAM_INT);

admin_externalpage_setup('shezarnavigation');

$PAGE->set_context(\context_system::instance());
$renderer = $PAGE->get_renderer('shezar_core');

$item = \shezar_core\shezar\menu\menu::get($id);
$property = $item->get_property();
$node = \shezar_core\shezar\menu\menu::node_instance($property);

$cancelurl = new moodle_url('/shezar/core/menu/index.php');

$mform = new edit_form(null, array('item' => $item));
if ($mform->is_cancelled()) {
    redirect($cancelurl);
}
if ($data = $mform->get_data()) {
    try {
        $redirect = new moodle_url('/shezar/core/menu/index.php', array());

        if ((int)$id > 0) {
            // Get the old visiblity before updating.
            $oldvisibility = $item->__get('visibility');

            $item->update($data);

            // Only redirect if a user turned on custom access rules.
            if ($oldvisibility != $data->visibility &&
                $data->visibility == \shezar_core\shezar\menu\menu::SHOW_CUSTOM) {
                    $redirect = new moodle_url('/shezar/core/menu/rules.php', array('id' => $item->id));
            }
        } else {
            $item = $item->create($data);
            // Redirect to the visibility settings page so they can set the visibility rules.
            if ($data->visibility == \shezar_core\shezar\menu\menu::SHOW_CUSTOM) {
                $redirect = new moodle_url('/shezar/core/menu/rules.php', array('id' => $item->id));
            }
        }

        shezar_set_notification(get_string('menuitem:updatesuccess', 'shezar_core'),
            $redirect, array('class' => 'notifysuccess'));
    } catch (moodle_exception $e) {
        shezar_set_notification($e->getMessage());
    }
}

$url = new moodle_url('/shezar/core/menu/edit.php', array('id' => $id));
$PAGE->set_url($url);
$title = ($id ? get_string('menuitem:editingx', 'shezar_core', $node->get_title()) : get_string('menuitem:addnew', 'shezar_core'));
$PAGE->set_title($title);
$PAGE->navbar->add($title, $url);
$PAGE->set_heading($title);

// Display page header.
echo $renderer->header();
echo $renderer->heading($title);

// Set up tabs for access controls and detail editing.
// Don't show them when creating a new item.
if (!empty($id)) {
    echo $renderer->shezar_menu_tabs('edit', $item);
}

echo $mform->display();
echo $renderer->footer();
