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
 * @author Sam Hemelryk <sam.hemelryk@shezarlms.com>
 * @package shezar_form
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2016 shezar Learning Solutions Ltd {@link http://www.shezarlms.com/}
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException;

/**
 * shezar form behat definitions.
 *
 * @package shezar_form
 * @copyright 2016 shezar Learning Solutions Ltd {@link http://www.shezarlms.com/}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Sam Hemelryk <sam.hemelryk@shezarlearning.com>
 */
class behat_shezar_form extends behat_base {

    /**
     * Navigates directly to the shezar test form.
     *
     * This page is only used for acceptance testing and does not appear in the navigation.
     * For that reason we must navigate directly to it.
     *
     * @Given /^I navigate to the shezar test form$/
     */
    public function i_navigate_to_the_shezar_test_form() {
        $url = new moodle_url('/shezar/form/tests/fixtures/test_acceptance.php');
        $this->getSession()->visit($url->out(false));
    }

    /**
     * Fills a shezar form with field/value data.
     *
     * This can only be used for shezar forms.
     *
     * @Given /^I set the following shezar form fields to these values:$/
     * @param TableNode $data
     */
    public function i_set_the_following_shezar_form_fields_to_these_values(TableNode $data) {

        if ($this->running_javascript()) {
            // If there are multiple sections we need to click the expand all link.
            $this->wait_for_pending_js();
            $nodes = $this->getSession()->getPage()->findAll('xpath', "//form[@data-shezar-form]//a[contains(text(), 'Expand all')]");
            if (is_array($nodes)) {
                foreach ($nodes as $node) {
                    if ($node->isVisible()) {
                        $node->click();
                    }
                }
            }
        }

        $datahash = $data->getRowsHash();

        // The action depends on the field type.
        foreach ($datahash as $locator => $value) {
            $this->i_set_shezar_form_field_value($locator, $value);
        }
    }

    /**
     * Sets the value of a shezar form field.
     *
     * This can only be used for shezar form fields.
     *
     * @Given /^I set the "(?P<locator>(?:[^"]|\\")*)" shezar form field to "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function i_set_shezar_form_field_value($locator, $value) {
        $field = $this->get_field_element_given_locator($locator);
        $field->set_value($value);
    }

    /**
     * Returns a behat_helper instance for the given node.
     *
     * @param string $locator
     * @return shezar_form\form\element\behat_helper\base
     * @throws ExpectationException
     */
    protected function get_field_element_given_locator($locator) {
        // Locator could be a label, an input name, or a field.
        if ($this->running_javascript()) {
            $this->wait_for_pending_js();
        }
        $locatorliteral = $this->getSession()->getSelectorsHandler()->xpathLiteral($locator);
        $xpath = "//form[@data-shezar-form]//*[label[contains(text(), {$locatorliteral})] or *[@name={$locatorliteral}] or *[@id={$locatorliteral}] or *[@data-element-label and contains(text(), {$locatorliteral})]]//ancestor::*[@data-element-type][1]";
        $nodes = $this->getSession()->getPage()->findAll('xpath', $xpath);
        if (empty($nodes)) {
            throw new ExpectationException('Unable to find an element using '.$locatorliteral, $this->getSession());
        }
        $node = reset($nodes);

        $type = $node->getAttribute('data-element-type');
        if (!preg_match('#^([^\\\\]+)\\\\form\\\\element\\\\([^\\\\]+)$#', $type, $matches)) {
            throw new ExpectationException('Unrecognised element type '.$type, $this->getSession());
        }
        $component = $matches[1];
        $elementtype = $matches[2];
        $behatelement = $component.'\\form\\element\\behat_helper\\'.$elementtype;
        if (!class_exists($behatelement)) {
            throw new ExpectationException('No behat element equivalent for '.$type, $this->getSession());
        }
        return new $behatelement($node, $this);
    }

    /**
     * Exposes the running_javascript method.
     *
     * @return bool
     */
    public function running_javascript() {
        return parent::running_javascript();
    }

}
