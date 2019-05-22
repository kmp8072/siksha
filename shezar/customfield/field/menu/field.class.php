<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2010 onwards shezar Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@shezarlms.com>
 * @package shezar
 * @subpackage shezar_customfield
 */

class customfield_menu extends customfield_base {
    var $options;
    var $datakey;

    /**
     * Get the choose option for the menu of choices.
     *
     * @return string
     */
    public function get_choose_option() {
        return get_string('choosedots');
    }

    /**
     * Constructor method.
     * Pulls out the options for the menu from the database and sets the
     * the corresponding key for the data if it exists
     */
    function __construct($fieldid=0, $itemid=0, $prefix, $tableprefix, $addsuffix = false) {
        // First call parent constructor.
        parent::__construct($fieldid, $itemid, $prefix, $tableprefix, $addsuffix);

        /// Param 1 for menu type is the options.
        $options = explode("\n", $this->field->param1);
        $this->options = array();
        // Include the choose option at the beginning.
        $this->options[''] = $this->get_choose_option();
        foreach($options as $key => $option) {
            $this->options[$key] = format_string($option);// Multilang formatting.
        }

        // Set the data key.
        if (empty($this->data)) {
            // Set default value to the choosedots option.
            $this->datakey = '';
        } else if ($this->data !== NULL) {
            $this->datakey = (int)array_search($this->data, $this->options);
        }
    }

    /**
     * Create the code snippet for this field instance
     * Overwrites the base class method
     * @param   object   moodleform instance
     */
    function edit_field_add(&$mform) {
        $mform->addElement('select', $this->inputname, format_string($this->field->fullname), $this->options);
    }

    /**
     * Set the default value for this field instance
     * Overwrites the base class method
     */
    function edit_field_set_default(&$mform) {
        if (FALSE !==array_search($this->field->defaultdata, $this->options)){
            $defaultkey = (int)array_search($this->field->defaultdata, $this->options);
        } else {
            $defaultkey = '';
        }
        $mform->setDefault($this->inputname, $defaultkey);
    }

    /**
     * Changes the customfield value from a string to the key that matches
     * the string in the array of options.
     *
     * @param  object $syncitem     The original syncitem to be processed.
     * @return object               The syncitem with the customfield data processed.
     *
     */
    public function sync_data_preprocess($syncitem) {
        // Get the sync value out of the item.
        $fieldname = $this->inputname;

        if (!isset($syncitem->$fieldname)) {
            return $syncitem;
        }

        $value = $syncitem->$fieldname;

        // Now get the corresponding option for that value.
        foreach ($this->options as $key => $option) {
            if ($option == $value) {
                $selected = $key;
            }
        }

        // If no matching option is found set it to empty.
        if (!isset($selected)) {
            $selected = NULL;
        }

        $syncitem->$fieldname = $selected;
        return $syncitem;
    }

    /**
     * The data from the form returns the key. This should be converted to the
     * respective option string to be saved in database.
     * Don't save data if the option chosen is the default "choose" option as it does not
     * represent a real option.
     * Overwrites base class accessor method
     * @param   integer $key the key returned from the select input in the form
     * @return mixed|null
     */
    function edit_save_data_preprocess($key) {
        return (isset($this->options[$key]) && $this->options[$key] != $this->get_choose_option()) ? $this->options[$key] : NULL;
    }

    /**
     * When passing the type object to the form class for the edit custom page
     * we should load the key for the saved data
     * Overwrites the base class method
     * @param   object   item object
     */
    function edit_load_item_data(&$item) {
        $item->{$this->inputname} = $this->datakey;
    }

    /**
     * HardFreeze the field if locked.
     * @param   object   instance of the moodleform class
     */
    function edit_field_set_locked(&$mform) {
        if (!$mform->elementExists($this->inputname)) {
            return;
        }
        if ($this->is_locked()) {
            $mform->hardFreeze($this->inputname);
            $mform->setConstant($this->inputname, $this->datakey);
        }
    }

    /**
     * Changes the customfield value from a file data to the key and value.
     *
     * @param  object $syncitem The original syncitem to be processed.
     * @return object The syncitem with the customfield data processed.
     */
    public function sync_filedata_preprocess($syncitem) {

        $value = $syncitem->{$this->field->shortname};
        unset($syncitem->{$this->field->shortname});

        $value = core_text::strtolower($value);
        $options = explode("\n", core_text::strtolower($this->field->param1));
        foreach($options as $key => $option) {
            if ($option == $value) {
                $value = (string)$key;
                break;
            }
        }
        $syncitem->{$this->inputname} = $value;

        return $syncitem;
    }
}
