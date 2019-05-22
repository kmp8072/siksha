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
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package shezar_core
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * The shezar metrics interface
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright Copyright (C) 2010-2013 shezar Learning Solutions LTD
 */
interface behat_shezar_metrics_interface {
    /**
     * State change constants.
     */
    const NO_CHANGE = 0;
    const ONCE = 1;
    const TURN_ON = 2;
    const TURN_OFF = 3;

    /**
     * Returns whether the metric recording state has changed.
     * @return int
     */
    public function get_metrics_state_change();

    /**
     * Returns the name of the metrics.
     * @return string
     */
    public function get_metrics_name();
}

/**
 * The shezar metrics definitions class.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright Copyright (C) 2010-2013 shezar Learning Solutions LTD
 */
class behat_shezar_metrics extends behat_base implements behat_shezar_metrics_interface {

    /**
     * Whether metric gathering has changed.
     * @var int
     */
    protected $metrics_change = self::NO_CHANGE;

    /**
     * The name for this metric.
     * @var string
     */
    protected $metrics_name = '';

    /**
     * Save performance metrics of current page for future analysis
     * Includes: db reads/writes, sessionfile size, etc
     *
     * @Given /^I save metrics$/
     */
    public function i_save_metrics() {
        $this->i_save_metrics_as("");
    }

    /**
     * Save performance metrics of current page for future analysis
     * Includes: db reads/writes, sessionfile size, etc
     *
     * @Given /^I save metrics as "([^"]*)"$/
     * @param string $name metric identification within scenario
     */
    public function i_save_metrics_as($name) {
        $this->metrics_change = self::ONCE;
        $this->metrics_name = $name;
    }

    /**
     * Start saving performance metrics of next and following pages
     *
     * @Given /^I start saving metrics as "([^"]*)"$/
     * @param string $name metric identification within scenario
     */
    public function i_start_saving_metrics_as($name) {
        $this->metrics_change = self::TURN_ON;
        $this->metrics_name = $name;
    }

    /**
     * Start saving performance metrics of next and following pages
     *
     * @Given /^I start saving metrics$/
     */
    public function i_start_saving_metrics() {
        $this->i_start_saving_metrics_as("");
    }

    /**
     * Stop saving metrics that was started before
     *
     * @Given /^I stop saving metrics$/
     */
    public function iStopSavingMetrics() {
        $this->metrics_change = self::TURN_OFF;
        $this->metrics_name = "";
    }

    /**
     * Returns the metrics state in regards to change.
     *
     * Will be one of self::NO_CHANGE, self::ONCE, self::TURN_ON, self::TURN_OFF
     *
     * @return int
     */
    public function get_metrics_state_change() {
        return $this->metrics_change;
    }

    /**
     * Returns the new of the metrics.
     *
     * @return string
     */
    public function get_metrics_name() {
        return $this->metrics_name;
    }
}
