<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2015 onwards shezar Learning Solutions LTD
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
 * @package shezar_reportbuilder
 */

namespace shezar_reportbuilder;

/**
 * Representation of tabular data.
 *
 * @package shezar_core
 */
class tabexport_source extends \shezar_core\tabexport_source {
    /** @var string $format how the data should be formatted */
    protected $format;

    /** @var \moodle_recordset $rs */
    protected $rs;

    /** @var \reportbuilder $report */
    protected $report;

    /** @var \rb_column[] $headings */
    protected $columns;

    /** @var array $cache data caching info */
    protected $cache;

    public function __construct(\reportbuilder $report) {
        global $DB;
        $this->report = $report;

        // Increasing the execution time to no limit.
        \core_php_time_limit::raise(0);
        raise_memory_limit(MEMORY_HUGE);

        list($sql, $params, $cache) = $this->report->build_query(false, true, true);
        $this->cache = $cache;
        $order = $report->get_report_sort();

        foreach ($this->report->columns as $column) {
            // check that column should be included
            if ($column->display_column(true)) {
                $this->columns[] = $column;
            }
        }

        $this->rs = $DB->get_recordset_sql($sql . $order, $params);
    }

    /**
     * Returns full name of this source.
     *
     * @return string
     */
    public function get_fullname() {
        return format_string($this->report->fullname);
    }

    /**
     * BLock of extra frontpage information.
     * @return array
     */
    public function get_extra_information() {
        $result = array();

        $restrictions = $this->report->get_restriction_descriptions();
        if (is_array($restrictions) && count($restrictions) > 0) {
            $result[] = get_string('reportcontents', 'shezar_reportbuilder');
            foreach ($restrictions as $restriction) {
                $result[] = $restriction;
            }
        }

        if ($this->cache) {
            $a = userdate($this->cache['lastreport']);
            $result[] = get_string('report:cachelast', 'shezar_reportbuilder', $a);
        }

        return $result;
    }

    /**
     * Get the list of headings.
     *
     * @return string[]
     */
    public function get_headings() {
        $result = array();
        $plaintext = ($this->format !== 'html');
        foreach ($this->columns as $column) {
            $result[] = $this->report->format_column_heading($column, $plaintext);
        }
        if (right_to_left()) {
            $result = array_reverse($result);
        }
        return $result;
    }

    /**
     * Return graph image if present.
     * @param int $w
     * @param int $h
     * @return string SVG file content
     */
    public function get_svg_graph($w, $h) {
        global $DB;

        $graphrecord = $DB->get_record('report_builder_graph', array('reportid' => $this->report->_id));
        if (empty($graphrecord->type)) {
            return null;
        }
        $graph = new \shezar_reportbuilder\local\graph($graphrecord, $this->report, false);

        list($sql, $params) = $this->report->build_query(false, true, true);

        $rs = $DB->get_recordset_sql($sql, $params, 0, $graphrecord->maxrecords);
        foreach($rs as $record) {
            $graph->add_record($record);
        }
        $rs->close();

        $svgdata = $graph->fetch_export_svg($w, $h);
        if (!$svgdata) {
            return null;
        }

        return $svgdata;
    }

    /**
     * Doest the source have custom header?
     *
     * NOTE: The data should be cast to string[][]
     *
     * @return mixed null if standard header used, anything else is data for custom header
     */
    public function get_custom_header() {
        if ($this->report->embedded) {
            return $this->report->embedobj->get_custom_export_header($this->report, $this->format);
        } else {
            return $this->report->src->get_custom_export_header($this->report, $this->format);
        }
    }

    /**
     * Returns current row of data formatted according to specified type.
     * @return array rows of tabular data
     */
    public function current() {
        $record = $this->rs->current();
        $row = $this->report->src->process_data_row($record, $this->format, $this->report);
        if (right_to_left()) {
            $row = array_reverse($row);
        }
        return $row;
    }

    /**
     * Returns the key of current row
     * @return int current row
     */
    public function key() {
        return $this->rs->key();
    }

    /**
     * Moves forward to next row
     * @return void
     */
    public function next() {
        $this->rs->next();
    }

    /**
     * Did we reach the end?
     * @return boolean
     */
    public function valid() {
        return $this->rs->valid();
    }

    /**
     * Free resources, source can not be used anymore.
     * @return void
     */
    public function close() {
        if ($this->rs) {
            $this->rs->close();
        }
    }
}
