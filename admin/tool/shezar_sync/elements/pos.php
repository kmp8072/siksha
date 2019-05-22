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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package shezar
 * @subpackage shezar_sync
 */

require_once($CFG->dirroot.'/admin/tool/shezar_sync/elements/classes/hierarchy.element.class.php');
require_once($CFG->dirroot.'/shezar/hierarchy/prefix/position/lib.php');

class shezar_sync_element_pos extends shezar_sync_hierarchy {

    /**
     * Add Pos fields.
     *
     * @param MoodleQuickForm $mform
     */
    public function config_form(&$mform) {
        parent::config_form($mform);
        // Disable the field when nothing is selected, and when database is selected.
        $mform->disabledIf('csvsaveemptyfields', 'source_pos', 'eq', '');
        $mform->disabledIf('csvsaveemptyfields', 'source_pos', 'eq', 'shezar_sync_source_pos_database');
    }

    function get_hierarchy() {
        return new position();
    }
}
