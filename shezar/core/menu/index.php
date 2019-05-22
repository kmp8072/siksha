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
 * shezar navigation page.
 *
 * @package    shezar
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@shezarlms.com>
 */

use \shezar_core\shezar\menu\menu as menu;

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');

// Actions to manage categories.
$moveup   = optional_param('moveup',   0, PARAM_INT);
$movedown = optional_param('movedown', 0, PARAM_INT);
$hideid   = optional_param('hideid',   0, PARAM_INT);
$showid   = optional_param('showid',   0, PARAM_INT);
$reset    = optional_param('reset',    0, PARAM_INT);
$confirm  = optional_param('confirm',  0, PARAM_BOOL);

admin_externalpage_setup('shezarnavigation');

$url = new moodle_url('/shezar/core/menu/index.php');
if (!empty($movedown)) {
    require_sesskey();
    menu::change_sortorder($movedown, false);
    shezar_set_notification(get_string('menuitem:movesuccess', 'shezar_core'), $url, array('class' => 'notifysuccess'));
}

if (!empty($moveup)) {
    require_sesskey();
    menu::change_sortorder($moveup, true);
    shezar_set_notification(get_string('menuitem:movesuccess', 'shezar_core'), $url, array('class' => 'notifysuccess'));
}

if (!empty($hideid)) {
    require_sesskey();
    menu::change_visibility($hideid, true);
    shezar_set_notification(get_string('menuitem:updatesuccess', 'shezar_core'), $url, array('class' => 'notifysuccess'));
}

if (!empty($showid)) {
    require_sesskey();
    menu::change_visibility($showid, false);
    shezar_set_notification(get_string('menuitem:updatesuccess', 'shezar_core'), $url, array('class' => 'notifysuccess'));
}

if (!empty($reset)) {
    if (empty($confirm)) {
        $message  = get_string('menuitem:resettodefaultconfirm', 'shezar_core');
        $options  = array('confirm' => 1, 'reset' => 1, 'sesskey' => sesskey());
        $continue = new moodle_url('/shezar/core/menu/index.php', $options);
        $cancel   = $url;
        echo $OUTPUT->header();
        echo $OUTPUT->confirm($message, $continue, $cancel);
        echo $OUTPUT->footer();
        exit;
    } else {
        require_sesskey();
        menu::reset_menu();
        shezar_set_notification(get_string('menuitem:resettodefaultcomplete', 'shezar_core'), $url, array('class' => 'notifysuccess'));
    }
}

$event = \shezar_core\event\menuadmin_viewed::create(array('context' => \context_system::instance()));
$event->trigger();

// Display page header.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('shezarnavigation', 'shezar_core'));
$editurl = new moodle_url('/shezar/core/menu/edit.php', array('id' => '0'));
echo $OUTPUT->single_button($editurl, get_string('menuitem:addnew', 'shezar_core'), 'get');

// Print table header.
$table = new html_table;
$table->id = 'shezarmenutable'; // Must not be same as the id of real shezar menu!
$table->attributes['class'] = 'admintable generaltable editcourse';

$table->head = array(
                get_string('menuitem:title', 'shezar_core'),
                get_string('menuitem:url', 'shezar_core'),
                get_string('menuitem:visibility', 'shezar_core'),
                get_string('edit'),
);
$table->colclasses = array(
                'leftalign name',
                'centeralign count',
                'centeralign icons',
                'leftalign actions'
);
$table->data = array();

$node = menu::get();
shezar_menu_table_load($table, $node);
echo html_writer::table($table);

echo $OUTPUT->single_button($editurl, get_string('menuitem:addnew', 'shezar_core'), 'get');
// Reset button.
$url = new moodle_url('/shezar/core/menu/index.php', array('reset' => 1));
echo $OUTPUT->single_button($url, get_string('menuitem:resettodefault', 'shezar_core'), 'get');

echo $OUTPUT->footer();
