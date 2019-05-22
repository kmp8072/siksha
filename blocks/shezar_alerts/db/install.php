<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2016 onwards shezar Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package block_shezar_tasks
 */

function xmldb_block_shezar_alerts_install() {
    global $DB;

    // Get the id of the default mymoodle page.
    $mypageid = $DB->get_field_sql('SELECT id FROM {my_pages} WHERE userid IS null AND private = 1');

    // A separate set up for a quicklinks block as it needs additional data to be added on install.
    $blockinstance = (object)array(
        'blockname'=> 'shezar_alerts',
        'parentcontextid' => SITEID,
        'showinsubcontexts' => 0,
        'pagetypepattern' => 'my-index',
        'subpagepattern' => $mypageid,
        'defaultweight' => 1,
        'configdata' => '',
        'defaultregion' => 'content',
    );

    $blockinstance->id = $DB->insert_record('block_instances', $blockinstance);

    // Ensure the block context is created.
    context_block::instance($blockinstance->id);
}

