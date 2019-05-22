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
 * @author Sam Hemelryk <sam.hemelryk@shezarlearning.com>
 * @package shezar_form
 */

namespace shezar_form\form\element\behat_helper;

use Behat\Mink\Exception\ExpectationException;

/**
 * A checkbox element helper.
 *
 * @author Sam Hemelryk <sam.hemelryk@shezarlearning.com>
 * @copyright 2016 shezar Learning Solutions Ltd {@link http://www.shezarlms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package shezar_form
 */
class checkbox implements base {

    /**
     * The element node, containing the whole element markup.
     * @var \Behat\Mink\Element\NodeElement
     */
    protected $node;

    /**
     * The context that is currently working with this element.
     * @var \behat_shezar_form
     */
    protected $context;

    /**
     * Constructs a checkbox behat element helper.
     *
     * @param \Behat\Mink\Element\NodeElement $node
     * @param \behat_shezar_form $context
     */
    public function __construct(\Behat\Mink\Element\NodeElement $node, \behat_shezar_form $context) {
        $this->node = $node;
        $this->context = $context;
    }

    /**
     * Returns the checkbox input.
     *
     * @return \Behat\Mink\Element\NodeElement
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    protected function get_checkbox_input() {
        $id = $this->node->getAttribute('data-element-id');
        $idliteral = $this->context->getSession()->getSelectorsHandler()->xpathLiteral($id);
        $checkboxes = $this->context->getSession()->getPage()->findAll('xpath', "//input[@type='checkbox' and @id={$idliteral}]");
        if (!is_array($checkboxes) || empty($checkboxes)) {
            throw new ExpectationException('Could not find expected checkbox input', $this->context->getSession());
        }
        if (count($checkboxes) > 1) {
            throw new ExpectationException('Found multiple checkbox inputs where only one was expected', $this->context->getSession());
        }
        // We expect only a single checkbox, so return just the first.
        return reset($checkboxes);
    }

    /**
     * Returns the value of the checkbox if it is checked, or the unchecked value otherwise.
     *
     * @return string
     */
    public function get_value() {
        $checkbox = $this->get_checkbox_input();
        if ($checkbox->isChecked()) {
            return $checkbox->getAttribute('value');
        } else {
            return $checkbox->getAttribute('data-value-unchecked');
        }
    }

    /**
     * Checks or unchecks the checkbox based on the given value.
     *
     * @param bool $value True if the checkbox should be checked, false otherwise.
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function set_value($value) {
        $checkbox = $this->get_checkbox_input();
        if (!$this->context->running_javascript()) {
            // This is just about surely not going to work.
            // The Goutte driver expects the checkbox to have a value of 1.
            // If it does not then too bad, the logic for get/set_value, isChecked, and (un)check will not work.
            // If you get here because you can't get your checkbox to work there is only one solution.
            // Add the @javascript tag.
            if (!empty($value) && !$checkbox->isChecked()) {
                $checkbox->check();
            } else if (empty($value) && $checkbox->isChecked()) {
                $checkbox->uncheck();
            } else {
                // If you arrive here with the intention of doing the world a favour by allowing developers to check a checked checkbox then stop.
                // DO NOT REMOVE THESE EXCEPTIONS
                // They are here for a very good reason, your behat tests must be accurate.
                // Accurate tests plus these exceptions ensure that we capture the exact state of fairs, and if anything goes wrong
                // or defaults unexpectedly change then we know about it.
                // So please - leave them be.
                if (empty($value)) {
                    throw new ExpectationException('Attempting to uncheck an unchecked checkbox: '.$checkbox->getAttribute('name'), $this->context->getSession());
                } else {
                    throw new ExpectationException('Attempting to check a checked checkbox: '.$checkbox->getAttribute('name'), $this->context->getSession());
                }
            }
            return;
        }
        if (!$checkbox->isVisible()) {
            throw new ExpectationException('Attempting to change a checkbox that is not visible', $this->context->getSession());
        }
        if ((!empty($value) && !$checkbox->isChecked()) || (empty($value) && $checkbox->isChecked())) {
            // OK click the checkbox, the value does match the current state.
            $checkbox->click();
            // Trigger the onchange event as triggered when 'checking' the checkbox.
            $this->context->getSession()->getDriver()->triggerSynScript(
                $checkbox->getXPath(),
                "Syn.trigger('change', {}, {{ELEMENT}})"
            );
        } else {
            // If you arrive here with the intention of doing the world a favour by allowing developers to check a checked checkbox then stop.
            // DO NOT REMOVE THESE EXCEPTIONS
            // They are here for a very good reason, your behat tests must be accurate.
            // Accurate tests plus these exceptions ensure that we capture the exact state of fairs, and if anything goes wrong
            // or defaults unexpectedly change then we know about it.
            // So please - leave them be.
            if (empty($value)) {
                throw new ExpectationException('Attempting to uncheck an unchecked checkbox: '.$checkbox->getAttribute('name'), $this->context->getSession());
            } else {
                throw new ExpectationException('Attempting to check a checked checkbox: '.$checkbox->getAttribute('name'), $this->context->getSession());
            }
        }
    }

}