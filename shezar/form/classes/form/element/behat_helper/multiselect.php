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
 * A multiselect element helper.
 *
 * @author Sam Hemelryk <sam.hemelryk@shezarlearning.com>
 * @copyright 2016 shezar Learning Solutions Ltd {@link http://www.shezarlms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package shezar_form
 */
class multiselect extends select {

    /**
     * Returns the value of the select input.
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
     * Selects one or more values in the multiselect element.
     *
     * @param string $value Comma separated list of values to select
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function set_value($value) {
        $select = $this->get_select_input();
        if ($this->context->running_javascript() && !$select->isVisible()) {
            throw new ExpectationException('Attempting to change a ' . $this->mytype . ' that is not visible', $this->context->getSession());
        }

        $values = $this->split_values($value);
        $first = true;
        foreach ($values as $value) {
            // Select the option.
            $select->selectOption($value, !$first);
            $first = false;
        }

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

    /**
     * Splits a string into multiple values. A comma is used to separate and can be escaped with a backslash.
     * @param string $value
     * @return string[]
     */
    protected function split_values($value) {
        $values = preg_split('#(?<!\\\)\,#', $value);
        $values = array_map('trim', $values);
        if (!is_array($values)) {
            $values = array($value);
        }
        return $values;
    }
}