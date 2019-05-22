<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Single select form field class.
 *
 * @package    core_form
 * @category   test
 * @copyright  2012 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__  . '/behat_form_field.php');

/**
 * Single select form field.
 *
 * @package    core_form
 * @category   test
 * @copyright  2012 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_form_select extends behat_form_field {

    /**
     * Sets the value(s) of a select element.
     *
     * Seems an easy select, but there are lots of combinations
     * of browsers and operative systems and each one manages the
     * autosubmits and the multiple option selects in a different way.
     *
     * @param string $value plain value or comma separated values if multiple. Commas in values escaped with backslash.
     * @return void
     */
    public function set_value($value) {
        // shezar: Moodle hacks were prone to double clicking which breaks shezar tests very badly...

        // Multiple-selects should not have any JS magic attached, let's use the selectOption() always.
        $multiple = $this->field->hasAttribute('multiple');

        if ($multiple or !$this->running_javascript()) {
            if ($multiple) {
                // Split and decode values. Comma separated list of values allowed. With valuable commas escaped with backslash.
                $options = preg_replace('/\\\,/', ',',  preg_split('/(?<!\\\),/', $value));
                // This is a multiple select, let's pass the multiple flag after first option.
                $afterfirstoption = false;
                foreach ($options as $option) {
                    $this->field->selectOption(trim($option), $afterfirstoption);
                    $afterfirstoption = true;
                }
            } else {
                // This is a single select, let's pass the last one specified.
                $this->field->selectOption(trim($value));
            }

            return;
        }

        $autosubmit = $this->field->hasClass('autosubmit');
        if ($autosubmit) {
            $this->session->wait(behat_base::TIMEOUT * 1000, behat_base::PAGE_READY_JS);
        }

        // Make sure the option actually exists!
        $selectxpath = $this->field->getXpath();
        $optionxpath = $this->get_option_xpath(trim($value), $selectxpath);
        if (!$optionnodes = $this->session->getDriver()->find($optionxpath)) {
            // We do want to know when tests contain invalid select options!
            throw new \Behat\Mink\Exception\ExpectationException('Cannot find select value "' . $value .'" in field' . $selectxpath, $this->session);
        }
        /** @var \Behat\Mink\Element\NodeElement $option */
        $option = reset($optionnodes);

        $browser = '';
        $driver = $this->session->getDriver();
        if (method_exists($driver, 'getBrowser')) {
            $browser = $driver->getBrowser();
        }

        if ($browser === 'firefox') {
            // Firefox is a total nightmare, recent versions cannot even click properly,
            // sometimes first click on select option breaks click on the next element,
            // the reason is that the select box is kept expanded after the click on option.

            $currentelementid = $this->get_internal_field_id();
            $option->click();
            try {
                $this->session->wait(behat_base::TIMEOUT * 1000, behat_base::PAGE_READY_JS);
                if ($currentelementid === $this->get_internal_field_id()) {
                    if ($option->hasAttribute('selected')) {
                        // This click on the select field should not change value, it should only collapse the select box.
                        $this->field->click();
                    }
                }
            } catch (Exception $e) {
                // Do nothing, we are likely on a different page now.
            }

        } else {
            // Do a click because selectOption() may not always trigger all JS events.
            $option->click();
        }

        // In case there was some ajax or redirect triggered make sure we are ready to continue.
        $this->session->wait(behat_base::TIMEOUT * 1000, behat_base::PAGE_READY_JS);
    }

    /**
     * Returns the text of the currently selected options.
     *
     * @return string Comma separated if multiple options are selected. Commas in option texts escaped with backslash.
     */
    public function get_value() {
        return $this->get_selected_options();
    }

    /**
     * Returns whether the provided argument matches the current value.
     *
     * @param mixed $expectedvalue
     * @return bool
     */
    public function matches($expectedvalue) {

        $multiple = $this->field->hasAttribute('multiple');

        // Same implementation as the parent if it is a single select.
        if (!$multiple) {
            $cleanexpectedvalue = trim($expectedvalue);
            $selectedtext = trim($this->get_selected_options());
            $selectedvalue = trim($this->get_selected_options(false));
            if ($cleanexpectedvalue != $selectedvalue && $cleanexpectedvalue != $selectedtext) {
                return false;
            }
            return true;
        }

        // We are dealing with a multi-select.

        // Can pass multiple comma separated, with valuable commas escaped with backslash.
        $expectedarr = array(); // Array of passed text options to test.

        // Unescape + trim all options and flip it to have the expected values as keys.
        $expectedoptions = $this->get_unescaped_options($expectedvalue);

        // Get currently selected option's texts.
        $texts = $this->get_selected_options(true);
        $selectedoptiontexts = $this->get_unescaped_options($texts);

        // Get currently selected option's values.
        $values = $this->get_selected_options(false);
        $selectedoptionvalues = $this->get_unescaped_options($values);

        // Precheck to speed things up.
        if (count($expectedoptions) !== count($selectedoptiontexts) ||
                count($expectedoptions) !== count($selectedoptionvalues)) {
            return false;
        }

        // We check against string-ordered lists of options.
        if ($expectedoptions != $selectedoptiontexts &&
                $expectedoptions != $selectedoptionvalues) {
            return false;
        }

        return true;
    }

    /**
     * Cleans the list of options and returns it as a string separating options with |||.
     *
     * @param string $value The string containing the escaped options.
     * @return string The options
     */
    protected function get_unescaped_options($value) {

        // Can be multiple comma separated, with valuable commas escaped with backslash.
        $optionsarray = array_map(
            'trim',
            preg_replace('/\\\,/', ',',
                preg_split('/(?<!\\\),/', $value)
           )
        );

        // Sort by value (keeping the keys is irrelevant).
        core_collator::asort($optionsarray, SORT_STRING);

        // Returning it as a string which is easier to match against other values.
        return implode('|||', $optionsarray);
    }

    /**
     * Returns the field selected values.
     *
     * Externalized from the common behat_form_field API method get_value() as
     * matches() needs to check against both values and texts.
     *
     * @param bool $returntexts Returns the options texts or the options values.
     * @return string
     */
    protected function get_selected_options($returntexts = true) {

        $method = 'getHtml';
        if ($returntexts === false) {
            $method = 'getValue';
        }

        // Is the select multiple?
        $multiple = $this->field->hasAttribute('multiple');

        $selectedoptions = array(); // To accumulate found selected options.

        // Selenium getValue() implementation breaks - separates - values having
        // commas within them, so we'll be looking for options with the 'selected' attribute instead.
        if ($this->running_javascript()) {
            // shezar: try to make this faster by looking at the initially selected option first.
            if (!$multiple) {
                $initialselected = $this->field->find('xpath', '//option[@selected=\'selected\']');
                if ($initialselected and $initialselected->hasAttribute('selected')) {
                    return trim($initialselected->{$method}());
                }
            }
            // Get all the options in the select and extract their value/text pairs.
            $alloptions = $this->field->findAll('xpath', '//option');
            foreach ($alloptions as $option) {
                // Is it selected?
                if ($option->hasAttribute('selected')) {
                    if ($multiple) {
                        // If the select is multiple, text commas must be encoded.
                        $selectedoptions[] = trim(str_replace(',', '\,', $option->{$method}()));
                    } else {
                        $selectedoptions[] = trim($option->{$method}());
                        // shezar: single selects may have only one value, there is no point in continuing looking for more selected options.
                        break;
                    }
                }
            }

        } else {
            // Goutte does not keep the 'selected' attribute updated, but its getValue() returns
            // the selected elements correctly, also those having commas within them.

            // Goutte returns the values as an array or as a string depending
            // on whether multiple options are selected or not.
            $values = $this->field->getValue();
            if (!is_array($values)) {
                $values = array($values);
            }

            // Get all the options in the select and extract their value/text pairs.
            $alloptions = $this->field->findAll('xpath', '//option');
            foreach ($alloptions as $option) {
                // Is it selected?
                if (in_array($option->getValue(), $values)) {
                    if ($multiple) {
                        // If the select is multiple, text commas must be encoded.
                        $selectedoptions[] = trim(str_replace(',', '\,', $option->{$method}()));
                    } else {
                        $selectedoptions[] = trim($option->{$method}());
                        // shezar: single selects may have only one value, there is no point in continuing looking for more selected options.
                        break;
                    }
                }
            }
        }

        return implode(', ', $selectedoptions);
    }

    /**
     * Returns the opton XPath based on it's select xpath.
     *
     * @param string $option
     * @param string $selectxpath
     * @return string xpath
     */
    protected function get_option_xpath($option, $selectxpath) {
        $valueliteral = $this->session->getSelectorsHandler()->xpathLiteral(trim($option));
        return $selectxpath . "/descendant::option[(./@value=$valueliteral or normalize-space(.)=$valueliteral)]";
    }
}
