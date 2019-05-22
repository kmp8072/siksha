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
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package shezar_form
 */

namespace shezar_form\form\element;

use shezar_form\element,
    shezar_form\form\validator\attribute_required,
    shezar_form\form\validator\valid_selection,
    shezar_form\item,
    shezar_form\model;

/**
 * Checkboxes input element.
 *
 * NOTE: The number of options needs to be relatively low, otherwise it will take lots of screen space.
 *
 * @package   shezar_form
 * @copyright 2016 shezar Learning Solutions Ltd {@link http://www.shezarlms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@shezarlms.com>
 */
class checkboxes extends element {
    /** @var string[] $options */
    private $options;

    /**
     * Checkboxes input constructor.
     *
     * @throws \coding_exception if the list of options is empty.
     * @param string $name
     * @param string $label
     * @param string[] $options associative array "option value"=>"option text"
     */
    public function __construct($name, $label, array $options) {
        if (func_num_args() > 3) {
            debugging('Extra unused constructor parameters detected.', DEBUG_DEVELOPER);
        }

        parent::__construct($name, $label);
        $this->attributes = array(
            'size' => null,
            'required' => false, // This is a group of elements, 'required' means user must select at least one.
        );

        if (empty($options)) {
            throw new \coding_exception('List of options cannot be empty');
        }

        // Normalise the values that are stored as keys a bit.
        $this->options = array();
        foreach ($options as $k => $v) {
            $this->options[(string)$k] = $v;
        }

        // Add validators.
        $this->add_validator(new attribute_required());
        $this->add_validator(new valid_selection());
    }

    /**
     * Called by parent before adding this element
     * or after removing element from parent.
     *
     * @param item $parent
     */
    public function set_parent(item $parent = null) {
        parent::set_parent($parent);

        if ($parent) {
            // Validate the current value is valid if present.
            $this->get_current_value(true);
        }
    }

    /**
     * Get submitted data without validation.
     *
     * @return array
     */
    public function get_data() {
        $model = $this->get_model();
        $name = $this->get_name();

        if ($this->is_frozen()) {
            return array($name => $this->get_current_value());
        }

        $data = $model->get_raw_post_data($name);
        if ($data === null or !is_array($data)) {
            // Malformed or missing data, this should not happen!
            return array($name => $this->get_initial_value());
        }

        // Get rid of special hidden input that makes sure we always get array.
        unset($data[-1]);

        // Selection values are validated on submission only.
        return array($name => array_values($data));
    }

    /**
     * Compare element value.
     *
     * @param string $operator open of model::OP_XXX operators
     * @param mixed $value2
     * @param bool $finaldata true means use get_data(), false means use get_field_value()
     * @return bool result
     */
    public function compare_value($operator, $value2 = null, $finaldata = true) {
        if ($finaldata) {
            $data = $this->get_data();
            $name = $this->get_name();
            $value1 = $data[$name];
        } else {
            $value1 = $this->get_field_value();
        }

        // Empty array here means no checkbox was checked.
        if ($operator === model::OP_FILLED or $operator === model::OP_NOT_EMPTY) {
            return !empty($value1);
        }
        if ($operator === model::OP_NOT_FILLED or $operator === model::OP_EMPTY) {
            return empty($value1);
        }

        return $this->get_model()->compare($value1, $operator, $value2);
    }

    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        $model = $this->get_model();
        $model->require_finalised();

        $id = $this->get_id();

        $result = array(
            'form_item_template' => 'shezar_form/element_checkboxes',
            'name__' . $this->get_name() => true,
            'name' => $this->get_name(),
            'id' => $id,
            'label' => (string)$this->label,
            'frozen' => $this->is_frozen(),
            'options' => array(),
            'amdmodule' => 'shezar_form/form_element_checkboxes',
        );

        $selected = $this->get_field_value();
        $i = 0;
        foreach ($this->options as $value => $text) {
            $value = (string)$value; // PHP converts type of numeric keys it seems.
            $text = clean_param($text, PARAM_CLEANHTML); // No JS allowed in checkbox labels!
            $oid = $id . '___chb_' . $i;
            $result['options'][] = array('value' => $value, 'oid' => $oid, 'text' => $text, 'checked' => in_array($value, $selected, true));
            $i++;
        }

        $attributes = $this->get_attributes();
        $this->set_attribute_template_data($result, $attributes);

        // Add errors if found, tweak attributes by validators.
        $this->set_validator_template_data($result, $output);

        // Add help button data.
        $this->set_help_template_data($result, $output);

        return $result;
    }

    /**
     * Get the value of text input element.
     *
     * @return array
     */
    public function get_field_value() {
        $model = $this->get_model();
        $name = $this->get_name();

        if ($model->is_form_submitted() and !$this->is_frozen()) {
            $data = $this->get_data();
            return $data[$name];
        }

        return $this->get_initial_value();
    }

    /**
     * Is the element data ok?
     *
     * NOTE: to be used from element_checkboxes validator only.
     *
     * @param array $data from self::get_dta()
     * @return bool
     */
    public function is_valid_selection($data) {
        $name = $this->get_name();
        if (!isset($data[$name])) {
            return false;
        }
        if (!is_array($data[$name])) {
            return false;
        }
        foreach ($data[$name] as $option) {
            if (is_array($option)) {
                return false;
            }
            if (!$this->is_valid_option($option)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Is this a valid option value?
     *
     * @param string $value
     * @return bool
     */
    protected function is_valid_option($value) {
        return array_key_exists($value, $this->options);
    }

    /**
     * Returns current selected checkboxes value.
     *
     * @param bool $debuggingifinvalid true means print debugging message if value invalid
     * @return array|null null means incorrect current value or not specified
     */
    protected function get_current_value($debuggingifinvalid = false) {
        $name = $this->get_name();
        $model = $this->get_model();

        $current = $model->get_current_data($name);
        if (array_key_exists($name, $current)) {
            $current = $current[$name];

            if ($current !== null and is_array($current)) {
                foreach ($current as $k => $value) {
                    $value = (string)$value;
                    if ($this->is_valid_option($value)) {
                        $current[$k] = $value;
                    } else {
                        if ($debuggingifinvalid) {
                            debugging('Invalid current value detected in checkboxes element ' . $this->get_name(), DEBUG_DEVELOPER);
                        }
                        unset($current[$k]);
                    }
                }
                return array_values($current);
            }

            if ($debuggingifinvalid) {
                debugging('Invalid current values detected in checkboxes element ' . $this->get_name(), DEBUG_DEVELOPER);
            }
        }

        return null;
    }

    /**
     * Returns current value or nothing.
     *
     * @return array
     */
    protected function get_initial_value() {
        $current = $this->get_current_value();
        if ($current === null) {
            return array();
        } else {
            return $current;
        }
    }
}
