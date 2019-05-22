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
 * @package tool_task
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\DriverException as DriverException;

/**
 * Scheduled tasks steps definitions.
 *
 * @package   tool_task
 * @category  test
 * @author    Petr Skoda
 */
class behat_tool_task extends behat_base {
    /**
     * Runs a scheduled task immediately, given full class name.
     *
     * Note that the browser goes to admin/cron.php and then back to current page URL.
     *
     * shezar: our implementation actually works, Moodle HQ does not understand behat and cron.
     *         Behat steps must never use settings or call shezar APIs, the only things they can do
     *         is to modify database and reset caches!!!
     *
     * @Given /^I run the scheduled task "(?P<task_name>[^"]+)"$/
     * @param string $taskname Name of task e.g. 'mod_whatever\task\do_something'
     */
    public function i_run_the_scheduled_task($taskname) {
        global $CFG;

        $task = \core\task\manager::get_scheduled_task($taskname);
        if (!$task) {
            throw new DriverException('The "' . $taskname . '" scheduled task does not exist');
        }
        $taskname = get_class($task);

        $previousurl = $this->getSession()->getCurrentUrl();
        $this->getSession()->visit("$CFG->wwwroot/$CFG->admin/cron.php?behat_task=" . rawurlencode($taskname));

        /** @var behat_general $general */
        $general = behat_context_helper::get('behat_general');

        $cronend = 'Cron completed at ';
        $result = 'Scheduled task complete: ' . $task->get_name() . ' (' . $taskname . ')';

        if ($general->running_javascript()) {
            $general->assert_page_contains_text($cronend);
            $general->assert_page_contains_text($result);
        } else {
            // For some weird reason the assert_page_contains_text does not work here,
            // maybe because of the plain text emulation on cron page.
            // Let's work around it here.
            $content = $this->getSession()->getDriver()->getContent();
            if (strpos($content, $cronend) === false) {
                throw new ExpectationException('"' . $cronend . '" text was not found in the page', $this->getSession());
            }
            if (strpos($content, $result) === false) {
                throw new ExpectationException('"' . $result . '" text was not found in the page', $this->getSession());
            }
        }

        $this->getSession()->visit($previousurl);

        if ($general->running_javascript()) {
            $general->wait_until_the_page_is_ready();
        }
    }

    /**
     * Run the specified scheduled task.
     * You need to specify the class name, as shown in Site administration -> Server -> Scheduled tasks.
     *
     * @deprecated
     *
     * @Then /^I run the "([^"]*)" task$/
     */
    public function i_run_the_task($taskname) {
        $this->i_run_the_scheduled_task($taskname);
    }
}
