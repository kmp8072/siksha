<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2014 onwards shezar Learning Solutions LTD
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
 * @package block_shezar_report_graph
 */


class restore_shezar_report_graph_block_structure_step extends restore_structure_step {

    protected function define_structure() {

        $paths = array();

        $paths[] = new restore_path_element('block', '/block', true);
        $paths[] = new restore_path_element('shezar_report_graph', '/block/shezar_report_graph');

        return $paths;
    }

    public function process_block($data) {
        global $DB;

        if (!$this->task->get_blockid()) {
            return;
        }

        if ($this->get_task()->is_samesite()) {
            return;
        }

        // Clear settings for restores from other sites because the reportid cannot match,
        // the userids are fine when restoring older backups because it cannot be reused or changed in one site.

        $configdata = $DB->get_field('block_instances', 'configdata', array('id' => $this->task->get_blockid()));

        $config = unserialize(base64_decode($configdata));
        if (empty($config)) {
            $config = new stdClass();
        }
        unset($config->reportorsavedid);
        unset($config->reportfor);

        $configdata = base64_encode(serialize($config));
        $DB->set_field('block_instances', 'configdata', $configdata, array('id' => $this->task->get_blockid()));
    }
}
