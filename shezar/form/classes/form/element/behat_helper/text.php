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
 * A text element helper.
 *
 * @author Sam Hemelryk <sam.hemelryk@shezarlearning.com>
 * @copyright 2016 shezar Learning Solutions Ltd {@link http://www.shezarlms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package shezar_form
 */
class text implements base {

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
     * Constructs a text behat element helper.
     *
     * @param \Behat\Mink\Element\NodeElement $node
     * @param \behat_shezar_form $context
     */
    public function __construct(\Behat\Mink\Element\NodeElement $node, \behat_shezar_form $context) {
        $this->node = $node;
        $this->context = $context;
        $this->mytype = str_replace('shezar_form\\form\\element\\behat_helper\\', '', get_class($this));
    }

    /**
     * Returns the text input.
     *
     * @return \Behat\Mink\Element\NodeElement
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    protected function get_text_input() {
        $id = $this->node->getAttribute('data-element-id');
        $idliteral = $this->context->getSession()->getSelectorsHandler()->xpathLiteral($id);
        $texts = $this->node->findAll('xpath', "//input[@id={$idliteral}]");
        if (empty($texts) || !is_array($texts)) {
            throw new ExpectationException('Could not find expected ' . $this->mytype . ' input ('.$idliteral.')', $this->context->getSession());
        }
        if (count($texts) > 1) {
            throw new ExpectationException('Found multiple ' . $this->mytype . ' inputs where only one was expected', $this->context->getSession());
        }
        return reset($texts);
    }

    /**
     * Returns the value of the text if it is checked, or the unchecked value otherwise.
     *
     * @return string
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function get_value() {
        $text = $this->get_text_input();
        if ($this->context->running_javascript() && !$text->isVisible()) {
            throw new ExpectationException('Attempting to change a ' . $this->mytype . ' that is not visible', $this->context->getSession());
        }
        return $text->getValue();
    }

    /**
     * Sets the value of the text input
     *
     * @param string $value
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function set_value($value) {
        $text = $this->get_text_input();
        if ($this->context->running_javascript() && !$text->isVisible()) {
            throw new ExpectationException('Attempting to change a ' . $this->mytype . ' that is not visible', $this->context->getSession());
        }
        $text->setValue($this->normalise_value_pre_set($value));
    }

    /**
     * Normalises the given value prior to setting it.
     *
     * @param string $value
     * @return string
     */
    protected function normalise_value_pre_set($value) {
        return $value;
    }

}