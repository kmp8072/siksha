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
 * @package shezar_core
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ElementNotFoundException,
    Behat\Mink\Exception\ExpectationException;

/**
 * The shezar core definitions class.
 *
 * This class contains the definitions for core shezar functionality.
 * Any definitions that belong to separ
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright Copyright (C) 2010-2013 shezar Learning Solutions LTD
 */
class behat_shezar_core extends behat_base {

    /**
     * A tab should be visible but disabled.
     *
     * @Given /^I should see the "([^"]*)" tab is disabled$/
     */
    public function i_should_see_the_tab_is_disabled($text) {
        $text = $this->getSession()->getSelectorsHandler()->xpathLiteral($text);
        $xpath = "//div[contains(concat(' ', normalize-space(@class), ' '), ' tabtree ')]//a[contains(concat(' ', normalize-space(@class), ' '), ' nolink ') and not(@href)]/*[contains(text(), {$text})]";
        // Bootstrap 3 has different markup.
        $xpath .= "| //*[contains(concat(' ', normalize-space(@class), ' '), ' tabtree ')]//li[contains(concat(' ', normalize-space(@class), ' '), ' disabled ')]/a[not(@href) and contains(text(), {$text})]";
        $this->find(
            'xpath',
            $xpath,
            new \Behat\Mink\Exception\ExpectationException('Tab "'.$text.'" could not be found or was not disabled', $this->getSession())
        );
    }

    /**
     * We expect to be on a shezar site.
     *
     * @Given /^I am on a shezar site$/
     */
    public function i_am_on_a_shezar_site() {
        global $DB;
        // Set shezar defaults. This is to undo the work done in /lib/behat/classes/util.php around line 90
        set_config('enablecompletion', 1);
        set_config('forcelogin', 1);
        set_config('guestloginbutton', 0);
        set_config('enablecompletion', 1, 'moodlecourse');
        set_config('completionstartonenrol', 1, 'moodlecourse');
        set_config('enrol_plugins_enabled', 'manual,guest,self,cohort,shezar_program');
        set_config('enhancedcatalog', 1);
        set_config('preventexecpath', 1);
        set_config('debugallowscheduledtaskoverride', 1); // Include dev interface for resetting scheduled task "Next run".
        $DB->set_field('role', 'name', 'Site Manager', array('shortname' => 'manager'));
        $DB->set_field('role', 'name', 'Editing Trainer', array('shortname' => 'editingteacher'));
        $DB->set_field('role', 'name', 'Trainer',array('shortname' => 'teacher'));
        $DB->set_field('role', 'name', 'Learner', array('shortname' => 'student'));
        $DB->set_field('modules', 'visible', 0, array('name'=>'workshop'));
        $DB->set_field('modules', 'visible', 0, array('name'=>'feedback'));
    }

    /**
     * Finds a shezar menu item and returns the node.
     *
     * @param string $text
     * @param bool $ensurevisible
     * @return \Behat\Mink\Element\NodeElement
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    protected function find_shezar_menu_item($text) {
        $text = $this->getSession()->getSelectorsHandler()->xpathLiteral($text);
        $xpath = "//*[@id = 'shezarmenu']//a[contains(normalize-space(.),{$text})]";
        $node = $this->find(
            'xpath',
            $xpath,
            new \Behat\Mink\Exception\ExpectationException('shezar menu item "'.$text.'" could not be found', $this->getSession())
        );
        return $node;
    }

    /**
     * Check you can see the expected menu item.
     *
     * @Given /^I should see "([^"]*)" in the shezar menu$/
     */
    public function i_should_see_in_the_shezar_menu($text) {
        $this->find_shezar_menu_item($text);
    }

    /**
     * Check the menu item is not there as expected.
     *
     * @Given /^I should not see "([^"]*)" in the shezar menu$/
     */
    public function i_should_not_see_in_the_shezar_menu($text) {
        try {
            $this->find_shezar_menu_item($text);
        } catch (\Behat\Mink\Exception\ExpectationException $ex) {
            // This is the desired outcome.
            return true;
        }
        throw new \Behat\Mink\Exception\ExpectationException('shezar menu item "'.$text.'" has been found and is visible', $this->getSession());
    }

    /**
     * Click on an item in the shezar menu.
     *
     * @Given /^I click on "([^"]*)" in the shezar menu$/
     */
    public function i_click_on_in_the_shezar_menu($text) {
        $node = $this->find_shezar_menu_item($text);
        $this->getSession()->visit($this->locate_path($node->getAttribute('href')));
    }

    /**
     * Create one or more menu items for the shezar main menu
     *
     * @Given /^I create the following shezar menu items:$/
     */
    public function i_create_the_following_shezar_menu_items(TableNode $table) {
        $possiblemenufields = array('Parent item', 'Menu title', 'Visibility', 'Menu default url address', 'Open link in new window');
        $first = false;

        $steps = array();
        $menufields = array();
        $rulefields = array();

        // We are take table c
        foreach ($table->getRows() as $row) {
            $menutable = new TableNode();
            $ruletable = new TableNode();

            if ($first === false) {
                // The first row is the headings.
                foreach ($row as $key => $field) {
                    if (in_array($field, $possiblemenufields)) {
                        $menufields[$field] = $key;
                    } else {
                        $rulefields[$field] = $key;
                    }
                }
                $first = true;
                continue;
            }

            foreach ($row as $key => $value) {
                $menurow = array();
                $rulerow = array();
                if (in_array($key, $menufields)) {
                    $menurow[] = array_search($key, $menufields);
                    $menurow[] = $row[$key];
                    $menutable->addRow($menurow);
                } else {
                    $rulerow[] = array_search($key, $rulefields);
                    $rulerow[] = $row[$key];
                    $ruletable->addRow($rulerow);
                }
            }

            $steps[] = new Given('I navigate to "Main menu" node in "Site administration > Appearance"');
            $steps[] = new Given('I press "Add new menu item"');
            $steps[] = new Given('I set the following fields to these values:', $menutable);
            $steps[] = new Given('I press "Add new menu item"');
            $steps[] = new Given('I should see "Edit menu item"');
            $steps[] = new Given('I click on "Access" "link"');
            $steps[] = new Given('I expand all fieldsets');
            $steps[] = new Given('I set the following fields to these values:', $ruletable);
            $steps[] = new Given('I press "Save changes"');
        }

        return $steps;
    }

    /**
     * Edit a shezar main menu item via the Admin interface.
     *
     * @Given /^I edit "([^"]*)" shezar menu item$/
     */
    public function i_edit_shezar_menu_item($text) {
        $text = $this->getSession()->getSelectorsHandler()->xpathLiteral($text);
        $xpath = "//table[@id='shezarmenutable']//td[contains(concat(' ', normalize-space(@class), ' '), ' name ')]/*[contains(text(),{$text})]//ancestor::tr//a[@title='Edit']";
        $node = $this->find(
            'xpath',
            $xpath,
            new \Behat\Mink\Exception\ExpectationException('Could not find Edit action for "'.$text.'" menu item', $this->getSession())
        );
        $node->click();
    }

    /**
     * Generic focus action.
     *
     * @When /^I set self completion for "([^"]*)" in the "([^"]*)" category$/
     * @param string $course The fullname of the course we are setting up
     * @param string $category The fullname of the category containing the course
     */
    public function i_set_self_completion_for($course, $category) {

        $steps = array();
        $steps[] = new Given('I navigate to "Manage courses and categories" node in "Site administration > Courses"');
        $steps[] = new Given('I click on "' . $category . '" "link" in the ".category-listing" "css_element"');
        $steps[] = new Given('I click on "' . $course .'" "link" in the ".course-listing" "css_element"');
        $steps[] = new Given('I click on "View" "link" in the ".course-detail-listing-actions" "css_element"');
        $steps[] = new Given('I click on "Course completion" "link"');
        $steps[] = new Given('I expand all fieldsets');
        $steps[] = new Given('I click on "criteria_self_value" "checkbox"');
        $steps[] = new Given('I press "Save changes"');
        $steps[] = new Given('I press "Turn editing on"');
        $steps[] = new Given('I add the "Self completion" block');
        $steps[] = new Given('I press "Turn editing off"');

        return $steps;
    }

    /**
     * Check the program progress bar meets a given percentage.
     *
     * @Then /^I should see "([^"]*)" program progress$/
     */
    public function i_should_see_program_progress($text) {

        $text = $this->getSession()->getSelectorsHandler()->xpathLiteral($text);
        $xpath = "//div[@id = 'progressbar']//img[contains(@alt,{$text})]";
        $node = $this->find(
            'xpath',
            $xpath,
            new \Behat\Mink\Exception\ExpectationException('Program progress bar "'.$text.'" could not be found', $this->getSession())
        );

        if (!$node->isVisible()) {
            throw new \Behat\Mink\Exception\ExpectationException('Program progress bar "'.$text.'" is not visible visible', $this->getSession());
        }
        return $node;
    }

    /**
     * Set a field within a program coursesets dynamically generated (and prefixed) form.
     *
     * @Then /^I set "([^"]*)" for courseset "([^"]*)" to "([^"]*)"$/
     */
    public function i_set_courseset_variable($varname, $courseset, $value) {

        $xpath = "";
        $xpath .= "//div[@id = 'course_sets_ce' or @id = 'course_sets_rc']";
        $xpath .= "//fieldset[descendant::legend[contains(.,'$courseset ')]]";
        $xpath .= "//div[@class='fitem' and descendant::label[contains(.,'$varname ')]]";
        $xpath .= "//div[@class='felement']//input";
        $node = $this->find(
            'xpath',
            $xpath,
            new \Behat\Mink\Exception\ExpectationException('Courseset setting "'.$varname.'" could not be found', $this->getSession())
        );

        if ($node->isVisible()) {
            $node->setValue($value);
        } else {
            throw new \Behat\Mink\Exception\ExpectationException('Courseset setting "'.$varname.'" is not visible', $this->getSession());
        }

        return $node;
    }

    /**
     * Winds back the timestamps for certifications so you can trigger recerts.
     *
     * @Then /^I wind back certification dates by (\d+) months$/
     */
    public function i_wind_back_certification_dates_by_months($windback) {
        global $DB;

        $windback = (int)$windback * (4 * WEEKSECS); // Assuming 4 weeks per month (close enough).

        // A list of all the places we need to windback, table => fields.
        $databasefields = array(
            'prog_completion' => array('timestarted', 'timedue', 'timecompleted'),
            'certif_completion' => array('timewindowopens', 'timeexpires', 'timecompleted'),
            'certif_completion_history' => array('timewindowopens', 'timeexpires', 'timecompleted', 'timemodified'),
        );

        // Windback all the timestamps by the specified amount, but don't fall into negatives.
        foreach ($databasefields as $table => $fields) {
            foreach ($fields as $field) {
                $sql = "UPDATE {{$table}}
                           SET {$field} = {$field} - {$windback}
                         WHERE {$field} > {$windback}";
                $DB->execute($sql);
            }
        }

        return true;
    }

    /**
     * Force waiting for X seconds without javascript in shezar.
     *
     * Usually needed when things need to have different timestamps and GoutteDriver is too fast.
     *
     * @Then /^I force sleep "(?P<seconds_number>\d+)" seconds$/
     * @param int $seconds
     */
    public function i_force_sleep($seconds) {
        if ($this->running_javascript()) {
            throw new \Behat\Mink\Exception\DriverException('Use \'I wait "X" seconds\' with Javascript support');
        }
        sleep($seconds);
    }

    /**
     * Force waiting for the next second.
     *
     * This is intended for places that need different timestamp in database.
     *
     * @Then /^I wait for the next second$/
     */
    public function i_wait_for_next_second() {
        $now = microtime(true);
        $sleep = ceil($now) - $now;
        if ($sleep > 0) {
            usleep($sleep * 1000000);
        } else {
            usleep(1000000);
        }
    }

    /**
     * Expect to see a specific image (by alt or title) within the given thing.
     *
     * @Then /^I should see the "([^"]*)" image in the "([^"]*)" "([^"]*)"$/
     */
    public function i_should_see_the_x_image_in_the_y_element($titleoralt, $containerelement, $containerselectortype) {
        // Get the container node; here we throw an exception
        // if the container node does not exist.
        $containernode = $this->get_selected_node($containerselectortype, $containerelement);

        $xpathliteral = $this->getSession()->getSelectorsHandler()->xpathLiteral($titleoralt);
        $locator = "//img[@alt={$xpathliteral} or @title={$xpathliteral}]";

        // Will throw an ElementNotFoundException if it does not exist, but, actually
        // it should not exist, so we try & catch it.
        try {
            // Would be better to use a 1 second sleep because the element should not be there,
            // but we would need to duplicate the whole find_all() logic to do it, the benefit of
            // changing to 1 second sleep is not significant.
            $this->find('xpath', $locator, false, $containernode, self::REDUCED_TIMEOUT);
        } catch (ElementNotFoundException $e) {
            throw new ExpectationException('The "' . $titleoralt . '" image was not found exists in the "' .
                $containerelement . '" "' . $containerselectortype . '"', $this->getSession());
        }

    }

    /**
     * Expect to not see a specific image (by alt or title) within the given thing.
     *
     * @Then /^I should not see the "([^"]*)" image in the "([^"]*)" "([^"]*)"$/
     */
    public function i_should_not_see_the_x_image_in_the_y_element($titleoralt, $containerelement, $containerselectortype) {
        // Get the container node; here we throw an exception
        // if the container node does not exist.
        $containernode = $this->get_selected_node($containerselectortype, $containerelement);

        $xpathliteral = $this->getSession()->getSelectorsHandler()->xpathLiteral($titleoralt);
        $locator = "//img[@alt={$xpathliteral} or @title={$xpathliteral}]";

        // Will throw an ElementNotFoundException if it does not exist, but, actually
        // it should not exist, so we try & catch it.
        try {
            // Would be better to use a 1 second sleep because the element should not be there,
            // but we would need to duplicate the whole find_all() logic to do it, the benefit of
            // changing to 1 second sleep is not significant.
            $node = $this->find('xpath', $locator, false, $containernode, self::REDUCED_TIMEOUT);
            if ($this->running_javascript() && !$node->isVisible()) {
                // It passes it is there but is not visible.
                return;
            }
        } catch (ElementNotFoundException $e) {
            // It passes.
            return;
        }
        throw new ExpectationException('The "' . $titleoralt . '" image was found in the "' .
            $containerelement . '" "' . $containerselectortype . '"', $this->getSession());
    }

    /**
     * Convenience step to force a Behat scenario to be skipped. Use anywhere in
     * a Behat scenario; all steps after this step will be skipped but the test
     * will not count as "failed". If you use it in a background section, then
     * the entire feature will be skipped.
     *
     * This is meant to be used in the situation where there are known bugs in
     * the code under test but the bugs have not been fixed yet. Another reason
     * for this step is to force the test to indicate an issue tracker reference
     * that will resolve the bugs(s).
     *
     * @Given /^I skip the scenario until issue "([^"]*)" lands$/
     */
    public function i_skip_the_scenario_until_issue_lands($issue) {
        if (!empty($issue)) {
            $msg = "THIS SCENARIO IS SKIPPED UNTIL '$issue' LANDS.";
            throw new \Moodle\BehatExtension\Exception\SkippedException($msg);
        }

        throw new ExpectationException(
            'No associated issue given for skipped scenario', $this->getSession()
        );
    }

    /**
     * Am I on the right page? This is intended to be used
     * instead of 'I should see "Course 1"' when on course page.
     *
     * @Then /^I should see "([^"]*)" in the page title$/
     */
    public function i_should_see_in_the_page_title($text) {
        $text = $this->getSession()->getSelectorsHandler()->xpathLiteral($text);
        $xpath = "//title[contains(text(), {$text})]";
        $this->find(
            'xpath',
            $xpath,
            new ExpectationException('Text "'.$text.'" was not found in page header', $this->getSession())
        );
    }

    /**
     * Searches for a specific term in the shezar dialog.
     *
     * @Given /^I search for "([^"]*)" in the "([^"]*)" shezar dialogue$/
     * @param string $term
     * @throws ExpectationException
     */
    public function i_search_for_in_the_shezar_dialogue($term, $dialog) {
        $dialog = $this->get_selected_node('shezardialogue', $dialog);
        if (!$dialog) {
            throw new ExpectationException('Unable to find the "'.$dialog.'" shezar dialog', $this->getSession());
        }
        $node = $dialog->find('xpath', '//div[@id="search-tab"]//input[@name="query" and @type="text"]');
        if (!$node) {
            throw new ExpectationException('Unable to find the query input for searching within the "'.$dialog.'" shezar dialog', $this->getSession());
        }
        $node->setValue($term);

        $node = $dialog->find('xpath', '//input[@type="submit" and @value="Search"]');
        if (!$node) {
            throw new ExpectationException('Unable to find the search button within the "'.$dialog.'" shezar dialog', $this->getSession());
        }
        $node->press();

        // Its now loading some content via AJAX.
        $this->wait_for_pending_js();
    }

    /**
     * Clicks on a specific result in the shezar dialog search results.
     *
     * @Given /^I click on "([^"]*)" from the search results in the "([^"]*)" shezar dialogue$/
     * @param string $term
     * @throws ExpectationException
     */
    public function i_click_on_from_the_search_results_in_the_shezar_dialogue($term, $dialog) {
        $dialog = $this->get_selected_node('shezardialogue', $dialog);
        if (!$dialog) {
            throw new ExpectationException('Unable to find the "'.$dialog.'" shezar dialog', $this->getSession());
        }
        $results = $dialog->find('xpath', '//*[@id="search-tab"]');

        $node = $results->findLink($term);
        $this->ensure_node_is_visible($node);
        $node->click();
    }
}
