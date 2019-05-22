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
 * @author Sam Hemelryk <sam.hemelryk@shezarlms.com>
 * @package shezar_reportbuilder
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use \Behat\Behat\Context\Step\Given;
use \Behat\Mink\Exception\ExpectationException;

class behat_shezar_reportbuilder extends behat_base {

    /**
     * Adds the given column to the report.
     *
     * This definition requires the user to already be editing a report and to be on the Columns tab.
     *
     * @Given /^I add the "([^"]*)" column to the report$/
     */
    public function i_add_the_column_to_the_report($columnname) {
        return array(
            new Given('I set the field "newcolumns" to "'.$columnname.'"'),
            new Given('I press "Save changes"'),
            new Given('I should see "Columns updated"'),
            new Given('I should see "'.$columnname.'"'),
        );
    }

    /**
     * Navigates to a given report that the user has created.
     *
     * @Given /^I navigate to my "([^"]*)" report$/
     */
    public function i_navigate_to_my_report($reportname) {
        return array(
            new Given('I click on "Reports" in the shezar menu'),
            new Given('I click on "'.$reportname.'" "link" in the ".reportmanager" "css_element"'),
            new Given('I should see "'.$reportname.'" in the "h2" "css_element"'),
        );
    }

    /**
     * Confirms the the given value exists in the report for the given row+column.
     *
     * @Then /^I should see "([^"]*)" in the "([^"]*)" report column for "([^"]*)"$/
     */
    public function i_should_see_in_the_report_column_for($value, $column, $rowcontent) {
        $rowsearch = $this->getSession()->getSelectorsHandler()->xpathLiteral($rowcontent);
        $valuesearch = $this->getSession()->getSelectorsHandler()->xpathLiteral($value);
        // Find the table.
        $xpath  = "//table[contains(concat(' ', normalize-space(@class), ' '), ' reportbuilder-table ')]";
        // Find the row
        $xpath .= "//td/*[contains(text(),{$rowsearch})]//ancestor::tr";
        // Find the column
        $xpath .= "/td[contains(concat(' ', normalize-space(@class), ' '), ' {$column} ')]";
        // Find the row
        $xpath .= "/self::*[child::text()[contains(.,{$valuesearch})] or *[child::text()[contains(.,{$valuesearch})]]]";

        $this->find(
            'xpath',
            $xpath,
            new ExpectationException('The given value could not be found within the report builder report', $this->getSession())
        );
        return true;
    }

    /**
     * Confirms the the given value does not exist in the report for the given row+column.
     *
     * @Then /^I should not see "([^"]*)" in the "([^"]*)" report column for "([^"]*)"$/
     */
    public function i_should_not_see_in_the_report_column_for($value, $column, $rowcontent) {
        try {
            $this->i_should_see_in_the_report_column_for($value, $column, $rowcontent);
        } catch (ExpectationException $ex) {
            return true;
        }
        throw new ExpectationException('The given value was found within the report builder report', $this->getSession());
    }
}