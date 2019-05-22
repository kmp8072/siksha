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
 * @author Brian Quinn <brian@learningpool.com>
 * @author Finbar Tracey <finbar@learningpool.com>
 * @package block_shezar_report_table
 */

defined('MOODLE_INTERNAL') || die();

/**
 * shezar report table block.
 *
 * This block display tabular data from the report in the block content.
 *
 * @author Brian Quinn <brian@learningpool.com>
 * @author Finbar Tracey <finbar@learningpool.com>
 * @package block_shezar_report_table
 */
class block_shezar_report_table extends block_base {

    /**
     * Returns true if this block instance has been configured.
     *
     * In this case the block is considered to have been configured if a report has been selected.
     *
     * @return bool
     */
    protected function is_configured() {
        if (empty($this->config->reportid)) {
            // Nothing to do - not configured yet.
            return false;
        }

        return true;
    }

    /**
     * Where can this block be displayed - everywhere.
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'all' => true,
        );
    }

    /**
     * Can multiple instance of this block appear on the same page?
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Can you configure this block?
     *
     * @return bool
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Initialises this block instance.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_shezar_report_table');
    }

    /**
     * Initialise any JavaScript required by this block.
     */
    public function get_required_javascript() {
        // Always execute the parent block JS just in case.
        parent::get_required_javascript();

        $this->page->requires->js_call_amd('block_shezar_report_table/module', 'change_links', array($this->get_uniqueid(), $this->instance->id));
    }

    /**
     * Get uniqueid for the reportbuilder
     *
     * @return string
     */
    protected function get_uniqueid() {
        return 'block_shezar_report_table_' . $this->instance->id;
    }

    /**
     * Return an array of HTML attributes that should be added to this block.
     * @return array
     */
    public function html_attributes() {
        // Always call the parent first.
        $attrs = parent::html_attributes();
        $attrs['class'] .= ' ' . $this->get_uniqueid();
        return $attrs;
    }

    /**
     * Prepare and return the content for this block.
     *
     * @return stdClass
     */
    public function get_content() {
        global $DB, $SESSION, $CFG;

        // Include report builder here.
        require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');

        if ($this->content !== null) {
            return $this->content;
        }

        // Init block with empty data.
        $this->content = new stdClass();
        $this->content->text = '';

        if (!$this->is_configured()) {
            return $this->content;
        }

        $id = $this->config->reportid;
        $sid = null;
        $savedfiltername = null;

        // Performance: Temporarily turn off block in session for some time if no caps.
        if (isset($SESSION->nocapsblockshezarreporttable[$id])) {
            if ($SESSION->nocapsblockshezarreporttable[$id] > time()) {
                return $this->content;
            } else {
                unset($SESSION->nocapsblockshezarreporttable[$id]);
            }
        }

        if (!empty($this->config->savedsearch)) {
            $sid = $this->config->savedsearch;

            // Get the name of the saved filter, if it exists, and is public.
            $select = 'id = :id AND ispublic = 1';
            $params = array('id' => $sid, 'ispublic' => 1);
            $savedfiltername = $DB->get_field_select('report_builder_saved', 'name', $select, $params);

            // Cannot view this report if filter is not found or not public.
            if ($savedfiltername === false) {
                return $this->content;
            }
        }

        // Check if report still exists.
        $reportrecord = $DB->get_record('report_builder', array('id' => $id), '*');
        if (!$reportrecord) {
            return $this->content;
        }

        // Verify global restrictions.
        $globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);

        // Instantiate a new report object.
        try {
            reportbuilder::overrideuniqueid($this->get_uniqueid());
            reportbuilder::overrideignoreparams(true);
            $report = new reportbuilder($id, null, false, $sid, null, false, array(), $globalrestrictionset);
        } catch (moodle_exception $e) {
            // Don't break page if report became unavailable.
            return $this->content;
        }

        if (!reportbuilder::is_capable($id)) {
            // Performance: Temporarily turn off block in session for some time if no caps.
            if (empty($SESSION->nocapsblockshezarreporttable) || !is_array($SESSION->nocapsblockshezarreporttable)) {
                $SESSION->nocapsblockshezarreporttable = array();
            }
            $SESSION->nocapsblockshezarreporttable[$id] = time() + 300;
            return $this->content;
        }

        if (!$sid) {
            // Ensure that filters are not applied if no saved search has been selected.
            $SESSION->reportbuilder[$report->get_uniqueid()] = null;
        }

        // Ensure that the toolbar search is disabled, as this will not work from the report block.
        // Only sorting and paging are supported.
        $report->toolbarsearch = false;

        \shezar_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

        if (!empty($this->config->title)) {
            $this->title = format_string($this->config->title);
        } else {
            $this->title = format_string($report->fullname);

            if (!empty($savedfiltername)) {
                $this->title .= ': ' . format_string($savedfiltername);
            }
        }

        $countfiltered = $report->get_filtered_count();
        $countall = $report->get_full_count();

        if ($this->config->hideifnoresults && ((isset($sid) && $countfiltered == 0) || $countall == 0)) {
            return $this->content;
        }

        $params = array('id' => $id);
        if ($sid) {
            $params['sid'] = $sid;
        }
        $reporturl = new moodle_url('/shezar/reportbuilder/report.php', $params);

        // Use output buffering so we can call the existing display_table() function.
        ob_start();
        $report->set_baseurl($reporturl);
        $report->display_table();
        $output = ob_get_contents();
        ob_end_clean();

        // The table has already been rendered so just return the class.
        $this->content->text = $output;
        $this->content->footer = html_writer::link($reporturl, get_string('viewfullreport', 'block_shezar_report_table'));

        return $this->content;
    }
}
