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
 * A select element helper.
 *
 * @author Sam Hemelryk <sam.hemelryk@shezarlearning.com>
 * @copyright 2016 shezar Learning Solutions Ltd {@link http://www.shezarlms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package shezar_form
 */
class select implements base {

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
     * Constructs a select behat element helper.
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
     * Returns the select input.
     *
     * @return \Behat\Mink\Element\NodeElement
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    protected function get_select_input() {
        $id = $this->node->getAttribute('data-element-id');
        $idliteral = $this->context->getSession()->getSelectorsHandler()->xpathLiteral($id);
        $selects = $this->node->findAll('xpath', "//select[@id={$idliteral}]");
        if (empty($selects) || !is_array($selects)) {
            throw new ExpectationException('Could not find expected ' . $this->mytype . ' input', $this->context->getSession());
        }
        if (count($selects) > 1) {
            throw new ExpectationException('Found multiple ' . $this->mytype . ' select elements where only one was expected', $this->context->getSession());
        }
        return reset($selects);
    }

    /**
     * Returns the value of the select.
     *
     * @return string
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function get_value() {
        $select = $this->get_select_input();
        if ($this->context->running_javascript() && !$select->isVisible()) {
            throw new ExpectationException('Attempting to change a ' . $this->mytype . ' that is not visible', $this->context->getSession());
        }
        return $select->getValue();
    }

    /**
     * Selects the given value in the select element.
     *
     * @param string $value The value to select
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function set_value($value) {
        $select = $this->get_select_input();
        if ($this->context->running_javascript() && !$select->isVisible()) {
            throw new ExpectationException('Attempting to change a ' . $this->mytype . ' that is not visible', $this->context->getSession());
        }

        // Select the option.
        $select->selectOption($value);

        if (!$this->context->running_javascript()) {
            // No JavaScript, we are done.
            return;
        }

        // Trigger the onchange event as triggered when selecting a real value.
        $this->context->getSession()->getDriver()->triggerSynScript(
            $select->getXPath(),
            "Syn.trigger('change', {}, {{ELEMENT}})"
        );
    }

}