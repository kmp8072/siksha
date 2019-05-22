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
 * A checkboxes element helper.
 *
 * @author Sam Hemelryk <sam.hemelryk@shezarlearning.com>
 * @copyright 2016 shezar Learning Solutions Ltd {@link http://www.shezarlms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package shezar_form
 */
class checkboxes implements base {

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
     * Constructs a checkboxes behat element helper.
     *
     * @param \Behat\Mink\Element\NodeElement $node
     * @param \behat_shezar_form $context
     */
    public function __construct(\Behat\Mink\Element\NodeElement $node, \behat_shezar_form $context) {
        $this->node = $node;
        $this->context = $context;
    }

    /**
     * Returns the checkboxes input.
     *
     * @return \Behat\Mink\Element\NodeElement[]
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    protected function get_checkboxes_input() {
        $checkboxes = $this->node->findAll('xpath', "//input[@type='checkbox']");
        if (empty($checkboxes)) {
            throw new ExpectationException('Could not find expected checkbox inputs', $this->context->getSession());
        }
        return $checkboxes;
    }

    /**
     * Returns the value of the checkboxes if it is checked, or the unchecked value otherwise.
     *
     * @return string
     */
    public function get_value() {
        $checkboxes = $this->get_checkboxes_input();
        $values = array();
        foreach ($checkboxes as $checkbox) {
            if ($checkbox->isChecked()) {
                $values[] = $checkbox->getAttribute('value');
            } else {
                $values[] = $checkbox->getAttribute('data-value-unchecked');
            }
        }
        return $values;
    }

    /**
     * Checks or unchecks the checkboxes based on the given value.
     *
     * @param string $value A comma separate list of checkbox values and/or labels.
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function set_value($value) {
        $checkboxes = $this->get_checkboxes_input();
        $values = $this->split_values($value);
        $labels = array();
        foreach ($values as $value) {
            $valueliteral = $this->context->getSession()->getSelectorsHandler()->xpathLiteral($value);
            /** @var \Behat\Mink\Element\NodeElement[] $label_nodes */
            $label_nodes = $this->node->findAll('xpath', "//label[contains(text(), {$valueliteral})]");
            foreach ($label_nodes as $node) {
                $labels[] = $node->getAttribute('for');
            }
        }
        if (!$this->context->running_javascript()) {
            foreach ($checkboxes as $checkbox) {
                $checkboxid = (string)$checkbox->getAttribute('id');
                $checkboxvalue = (string)$checkbox->getAttribute('value');
                if (in_array($checkboxvalue, $values) || in_array($checkboxid, $labels)) {
                    if (!$checkbox->isChecked()) {
                        $checkbox->check();
                    }
                } else if ($checkbox->isChecked()) {
                    $checkbox->uncheck();
                }
            }
            return;
        }
        foreach ($checkboxes as $checkbox) {
            if (!$checkbox->isVisible()) {
                throw new ExpectationException('Attempting to change a checkboxes that is not visible: '.$checkbox->getAttribute('name'), $this->context->getSession());
            }

            $changed = false;
            $checkboxvalue = (string)$checkbox->getAttribute('value');
            $checkboxid = (string)$checkbox->getAttribute('id');
            if (in_array($checkboxvalue, $values)|| in_array($checkboxid, $labels)) {
                if (!$checkbox->isChecked()) {
                    $checkbox->check();
                    $changed = true;
                }
            } else if ($checkbox->isChecked() || !empty($checkbox->getAttribute('checked'))) {
                $checkbox->click();
                $changed = true;
            }
            if ($changed) {
                // Trigger the onchange event as triggered when 'checking' the checkboxes.
                $this->context->getSession()->getDriver()->triggerSynScript(
                    $checkbox->getXPath(),
                    "Syn.trigger('change', {}, {{ELEMENT}})"
                );
            }
        }
    }

    /**
     * Splits a string into multiple values. A comma is used to separate and can be escaped with a backslash.
     * @param string $value
     * @return string[]
     */
    protected function split_values($value) {
        $values = preg_split('#(?<!\\\)\,#', $value);
        $values = array_map('trim', $values);
        return $values;
    }

}