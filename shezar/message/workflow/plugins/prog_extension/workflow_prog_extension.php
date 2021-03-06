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
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @package shezar
 * @subpackage message
 */

defined('MOODLE_INTERNAL') || die();


require_once($CFG->dirroot.'/shezar/program/lib.php');

/**
* Extend the base plugin class
* This class contains the action for facetoface onaccept/onreject message processing
*/
class shezar_message_workflow_prog_extension extends shezar_message_workflow_plugin_base {

    /**
     * Action called on accept for a program extension action
     *
     * @param array $eventdata
     * @param object $msg
     */
    function onaccept($eventdata, $msg) {
        $extensionid = $eventdata['extensionid'];
        $reasonfordecision = (isset($eventdata['reasonfordecision'])) ? $eventdata['reasonfordecision'] : '';

        $extensions = array($extensionid => 1);  // 1 = grant, 2 = deny
        $reason = array($extensionid => $reasonfordecision);

        // Approve extensions
        $result = prog_process_extensions($extensions, $reason);

        if (isset($result['failcount']) && $result['failcount'] === 1) {
            // Print error message.
            print_error('error:extensionnotprocessed', 'shezar_program');
        }

        return true;
    }


    /**
     * Action called on reject of a program extension action
     *
     * @param array $eventdata
     * @param object $msg
     */
    function onreject($eventdata, $msg) {
        // Can manipulate the language by setting $SESSION->lang temporarily.
        $extensionid = $eventdata['extensionid'];
        $reasonfordecision = (isset($eventdata['reasonfordecision'])) ? $eventdata['reasonfordecision'] : '';

        $extensions = array($extensionid => 2);  // 1 = grant, 2 = deny
        $reason = array($extensionid => $reasonfordecision);

        // Decline extensions.
        $result = prog_process_extensions($extensions, $reason);

        if (isset($result['failcount']) && $result['failcount'] === 1) {
            // Print error message.
            print_error('error:extensionnotprocessed', 'shezar_program');
        }

        return true;
    }
}
