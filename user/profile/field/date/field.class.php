<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2015 onwards shezar Learning Solutions LTD
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
 * @package profilefield_date
 */

/**
 * Handles displaying and editing the date field.
 *
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package profilefield_date
 */
class profile_field_date extends profile_field_base {

    /**
     * Handles editing date fields.
     *
     * @param MoodleQuickForm $mform
     */
    public function edit_field_add($mform) {
        // Check if the field is required.
        if ($this->field->required) {
            $optional = false;
        } else {
            $optional = true;
        }

        $attributes = array(
            'optional'  => $optional,
            'timezone'  => 'UTC',
        );

        $mform->addElement('date_selector', $this->inputname, format_string($this->field->name), $attributes);

        $mform->setDefault($this->inputname, time());
    }

    /**
     * If timestamp is in YYYY-MM-DD or YYYY-MM-DD-HH-MM-SS format, then convert it to timestamp.
     *
     * @param string|int $datetime datetime to be converted.
     * @param stdClass $datarecord The object that will be used to save the record
     * @return int timestamp
     */
    public function edit_save_data_preprocess($datetime, $datarecord) {
        $isstring = strpos($datetime, '-');
        if (empty($isstring)) {
            $datetime = userdate($datetime, '%Y-%m-%d', 'UTC');
        }

        $datetime = explode('-', $datetime);

        // Use UTC noon so that it covers the same day in most timezones.
        return make_timestamp($datetime[0], $datetime[1], $datetime[2], 12, 0, 0, 'UTC');
    }

    /**
     * Display the data for this field.
     *
     * @return string
     */
    public function display_data() {
        // Check if a date has been specified.
        if (empty($this->data)) {
            return get_string('notset', 'profilefield_date');
        } else {
            return userdate($this->data, get_string('strftimedate', 'langconfig'), 'UTC');
        }
    }

    /**
     * The Datetime field needs extra logic for saving
     * so override edit_save_data in the lib file.
     *
     * @param stdClass $usernew data coming from the form
     * @return void
     */
    public function edit_save_data($usernew) {
        global $DB;

        $fieldname = $this->inputname;

        // If a date is disabled then remove any existing data
        if (isset($usernew->$fieldname) && empty($usernew->$fieldname)) {
            $DB->delete_records('user_info_data', array('userid' => $usernew->id, 'fieldid' => $this->field->id));
            return;
        }

        parent::edit_save_data($usernew);
    }

    /**
     * Loads a user object with data for this field ready for the export, such as a spreadsheet.
     *
     * @param stdClass $user a user object
     */
    public function export_load_user_data($user) {
        // Check if a date has been specified.
        if (empty($this->data)) {
            $user->{$this->inputname} = get_string('notset', 'profilefield_date');
        } else {
            $user->{$this->inputname} = userdate($this->data, get_string('strftimedate', 'langconfig'), 'UTC');
        }
    }
}
