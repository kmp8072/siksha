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
 * A radios element helper.
 *
 * @author Sam Hemelryk <sam.hemelryk@shezarlearning.com>
 * @copyright 2016 shezar Learning Solutions Ltd {@link http://www.shezarlms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package shezar_form
 */
class radios implements base {

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
     * The type of this instance.
     * @var string
     */
    protected $mytype;

    /**
     * Constructs a radios behat element helper.
     *
     * @param \Behat\Mink\Element\NodeElement $node
     * @param \behat_shezar_form $context
     */
    public function __construct(\Behat\Mink\Element\NodeElement $node, \behat_shezar_form $context) {
        $this->node = $node;
        $this->context = $context;
        $this->mytype = get_class($this);
    }

    /**
     * Returns the radios input.
     *
     * @return \Behat\Mink\Element\NodeElement[]
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    protected function get_radios_inputs() {
        $id = $this->node->getAttribute('data-element-id');
        $idliteral = $this->context->getSession()->getSelectorsHandler()->xpathLiteral($id);
        $radios = $this->node->findAll('xpath', "//*[@id={$idliteral}]//input[@type='radio']");
        if ($radios === null) {
            throw new ExpectationException('Could not find expected ' . $this->mytype . ' input', $this->context->getSession());
        }
        return $radios;
    }

    /**
     * Ensures that this element is visible and throws an exception if it is not.
     *
     * @throws ExpectationException
     */
    protected function ensure_visible() {
        $id = $this->node->getAttribute('data-element-id');
        $idliteral = $this->context->getSession()->getSelectorsHandler()->xpathLiteral($id);
        $containers = $this->node->findAll('xpath', "//*[@id={$idliteral}]");
        if (empty($containers) || !is_array($containers)) {
            throw new ExpectationException('Attempting to get the value of a ' . $this->mytype . ' without a container', $this->context->getSession());
        }
        $container = reset($containers);
        if (!$container->isVisible()) {
            throw new ExpectationException('Attempting to get the value of a ' . $this->mytype . ' that is not visible', $this->context->getSession());
        }
    }

    /**
     * Returns the value of the radios if it is checked, or the unchecked value otherwise.
     *
     * @return string|null
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function get_value() {
        $radios = $this->get_radios_inputs();
        if ($this->context->running_javascript()) {
            $this->ensure_visible();
        }
        foreach ($radios as $radio) {
            if ($radio->getAttribute('checked')) {
                return $radio->getValue();
            }
        }
        // No radios were checked.
        return null;
    }

    /**
     * Checks or unchecks the radios based on the given value.
     *
     * @param bool $value True if the radios should be checked, false otherwise.
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function set_value($value) {
        $radios = $this->get_radios_inputs();
        if ($this->context->running_javascript()) {
            $this->ensure_visible();
        }
        $valueliteral = $this->context->getSession()->getSelectorsHandler()->xpathLiteral($value);
        /** @var \Behat\Mink\Element\NodeElement[] $label_nodes */
        $label_nodes = $this->node->findAll('xpath', "//label[contains(text(), {$valueliteral})]");
        $labels = array();
        foreach ($label_nodes as $node) {
            $labels[] = $node->getAttribute('for');
        }
        foreach ($radios as $radio) {
            if ((string)$radio->getAttribute('value') === (string)$value || in_array($radio->getAttribute('id'), $labels)) {
                // Goutte is weird sometimes.
                if ($radio->isChecked()) {
                    throw new ExpectationException('Attempting to select an already selected ' . $this->mytype . ' value', $this->context->getSession());
                }
                if ($this->context->running_javascript()) {
                    $radio->check();
                } else {
                    // Goutte is weird sometimes.
                    $radio->setValue($radio->getAttribute('value'));
                }
                return;
            }
        }
    }

}