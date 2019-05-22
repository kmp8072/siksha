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
 * @author Piers Harding <piers@catalyst.net.nz>
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @package shezar
 * @subpackage message
 */

defined('MOODLE_INTERNAL') || die();

class block_shezar_alerts extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_shezar_alerts');
    }

    // Only one instance of this block is required.
    function instance_allow_multiple() {
      return false;
    }

    // Label and button values can be set in admin.
    function has_config() {
      return true;
    }

    function get_content() {
        global $CFG, $PAGE;

        // Cache block contents.
        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();

        // Initialise jquery requirements.
        require_once($CFG->dirroot.'/shezar/message/messagelib.php');
        require_once($CFG->dirroot.'/shezar/reportbuilder/lib.php');
        require_once($CFG->dirroot.'/shezar/core/js/lib/setup.php');

        $code = array();
        $code[] = shezar_JS_DIALOG;
        local_js($code);
        $PAGE->requires->js_init_call('M.shezar_message.init');

        // Just get the alerts for this user.
        $total = tm_messages_count('shezar_alert', false);
        $this->msgs = tm_messages_get('shezar_alert', 'timecreated DESC ', false, true);
        $this->title = get_string('alerts', 'block_shezar_alerts');

        if (empty($this->instance)) {
            return $this->content;
        }

        $renderer = $this->page->get_renderer('block_shezar_alerts');
        $this->content->text = $renderer->display_alerts($this->msgs, $total, $this->config);

        return $this->content;
    }
}

?>
