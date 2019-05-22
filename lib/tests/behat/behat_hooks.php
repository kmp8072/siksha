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
 * Behat hooks steps definitions.
 *
 * This methods are used by Behat CLI command.
 *
 * @package    core
 * @category   test
 * @copyright  2012 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../behat/behat_base.php');

use Behat\Behat\Event\SuiteEvent as SuiteEvent,
    Behat\Behat\Event\ScenarioEvent as ScenarioEvent,
    Behat\Behat\Event\FeatureEvent as FeatureEvent,
    Behat\Behat\Event\OutlineExampleEvent as OutlineExampleEvent,
    Behat\Behat\Event\StepEvent as StepEvent,
    Behat\Mink\Exception\DriverException as DriverException,
    WebDriver\Exception\NoSuchWindow as NoSuchWindow,
    WebDriver\Exception\UnexpectedAlertOpen as UnexpectedAlertOpen,
    WebDriver\Exception\UnknownError as UnknownError,
    WebDriver\Exception\CurlExec as CurlExec,
    WebDriver\Exception\NoAlertOpenError as NoAlertOpenError;

/**
 * Hooks to the behat process.
 *
 * Behat accepts hooks after and before each
 * suite, feature, scenario and step.
 *
 * They can not call other steps as part of their process
 * like regular steps definitions does.
 *
 * Throws generic Exception because they are captured by Behat.
 *
 * @package   core
 * @category  test
 * @copyright 2012 David Monllaó
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_hooks extends behat_base {

    /** @var bool shezar: Force restart before the next scenario */
    public static $forcerestart = false;

    /**
     * @var Last browser session start time.
     */
    protected static $lastbrowsersessionstart = 0;

    /**
     * @var For actions that should only run once.
     */
    protected static $initprocessesfinished = false;

    /**
     * If we are saving any kind of dump on failure we should use the same parent dir during a run.
     *
     * @var The parent dir name
     */
    protected static $faildumpdirname = false;

    /**
     * Keeps track of time taken by feature to execute.
     *
     * @var array list of feature timings
     */
    protected static $timings = array();

    /**
     * Gives access to moodle codebase, ensures all is ready and sets up the test lock.
     *
     * Includes config.php to use moodle codebase with $CFG->behat_*
     * instead of $CFG->prefix and $CFG->dataroot, called once per suite.
     *
     * @param SuiteEvent $event event before suite.
     * @static
     * @throws Exception
     * @BeforeSuite
     */
    public static function before_suite(SuiteEvent $event) {
        global $CFG;

        // Defined only when the behat CLI command is running, the moodle init setup process will
        // read this value and switch to $CFG->behat_dataroot and $CFG->behat_prefix instead of
        // the normal site.
        define('BEHAT_TEST', 1);

        define('CLI_SCRIPT', 1);

        // With BEHAT_TEST we will be using $CFG->behat_* instead of $CFG->dataroot, $CFG->prefix and $CFG->wwwroot.
        require_once(__DIR__ . '/../../../config.php');

        // Now that we are MOODLE_INTERNAL.
        require_once(__DIR__ . '/../../behat/classes/behat_command.php');
        require_once(__DIR__ . '/../../behat/classes/behat_selectors.php');
        require_once(__DIR__ . '/../../behat/classes/behat_context_helper.php');
        require_once(__DIR__ . '/../../behat/classes/util.php');
        require_once(__DIR__ . '/../../testing/classes/test_lock.php');
        require_once(__DIR__ . '/../../testing/classes/nasty_strings.php');

        // Avoids vendor/bin/behat to be executed directly without test environment enabled
        // to prevent undesired db & dataroot modifications, this is also checked
        // before each scenario (accidental user deletes) in the BeforeScenario hook.

        if (!behat_util::is_test_mode_enabled()) {
            throw new Exception('Behat only can run if test mode is enabled. More info in ' . behat_command::DOCS_URL . '#Running_tests');
        }

        // Reset all data, before checking for check_server_status.
        // If not done, then it can return apache error, while running tests.
        behat_util::reset_all_data();

        // Check if server is running and using same version for cli and apache.
        behat_util::check_server_status();

        // Prevents using outdated data, upgrade script would start and tests would fail.
        if (!behat_util::is_test_data_updated()) {
            $commandpath = 'php admin/tool/behat/cli/init.php';
            throw new Exception("Your behat test site is outdated, please run\n\n    " .
                    $commandpath . "\n\nfrom your moodle dirroot to drop and install the behat test site again.");
        }
        // Avoid parallel tests execution, it continues when the previous lock is released.
        test_lock::acquire('behat');

        // Store the browser reset time if reset after N seconds is specified in config.php.
        if (!empty($CFG->behat_restart_browser_after)) {
            // Store the initial browser session opening.
            self::$lastbrowsersessionstart = time();
        }

        if (!empty($CFG->behat_faildump_path) && !is_writable($CFG->behat_faildump_path)) {
            throw new Exception('You set $CFG->behat_faildump_path to a non-writable directory');
        }

        // Handle interrupts on PHP7.
        if (extension_loaded('pcntl')) {
            $disabled = explode(',', ini_get('disable_functions'));
            if (!in_array('pcntl_signal', $disabled)) {
                declare(ticks = 1);
            }
        }
    }

    /**
     * Gives access to moodle codebase, to keep track of feature start time.
     *
     * @param FeatureEvent $event event fired before feature.
     * @BeforeFeature
     */
    public static function before_feature(FeatureEvent $event) {
        if (!defined('BEHAT_FEATURE_TIMING_FILE')) {
            return;
        }
        $file = $event->getFeature()->getFile();
        self::$timings[$file] = microtime(true);
    }

    /**
     * Gives access to moodle codebase, to keep track of feature end time.
     *
     * @param FeatureEvent $event event fired after feature.
     * @AfterFeature
     */
    public static function after_feature(FeatureEvent $event) {
        if (!defined('BEHAT_FEATURE_TIMING_FILE')) {
            return;
        }
        $file = $event->getFeature()->getFile();
        self::$timings[$file] = microtime(true) - self::$timings[$file];
        // Probably didn't actually run this, don't output it.
        if (self::$timings[$file] < 1) {
            unset(self::$timings[$file]);
        }
    }

    /**
     * Gives access to moodle codebase, to keep track of suite timings.
     *
     * @param SuiteEvent $event event fired after suite.
     * @AfterSuite
     */
    public static function after_suite(SuiteEvent $event) {
        if (!defined('BEHAT_FEATURE_TIMING_FILE')) {
            return;
        }
        $realroot = realpath(__DIR__.'/../../../').'/';
        foreach (self::$timings as $k => $v) {
            $new = str_replace($realroot, '', $k);
            self::$timings[$new] = round($v, 1);
            unset(self::$timings[$k]);
        }
        if ($existing = @json_decode(file_get_contents(BEHAT_FEATURE_TIMING_FILE), true)) {
            self::$timings = array_merge($existing, self::$timings);
        }
        arsort(self::$timings);
        @file_put_contents(BEHAT_FEATURE_TIMING_FILE, json_encode(self::$timings, JSON_PRETTY_PRINT));
    }

    /**
     * Resets the test environment.
     *
     * @param OutlineExampleEvent|ScenarioEvent $event event fired before scenario.
     * @throws coding_exception If here we are not using the test database it should be because of a coding error
     * @BeforeScenario
     */
    public function before_scenario($event) {
        global $DB, $SESSION, $CFG;

        // As many checks as we can.
        if (!defined('BEHAT_TEST') ||
               !defined('BEHAT_SITE_RUNNING') ||
               php_sapi_name() != 'cli' ||
               !behat_util::is_test_mode_enabled() ||
               !behat_util::is_test_site()) {
            throw new coding_exception('Behat only can modify the test database and the test dataroot!');
        }

        // shezar: Reset the browser if specified in config.php before we do anything.
        if (!empty($CFG->behat_restart_browser_after)) {
            if (self::$lastbrowsersessionstart + $CFG->behat_restart_browser_after < time()) {
                self::$forcerestart = true;
            }
        }

        // shezar: use any means to get things back up and running after any problems, browser restart should do the trick.
        if (self::$forcerestart) {
            for ($count = 0; $count < 3; $count++) {
                try {
                    $this->getMink()->stopSessions();
                    break;
                } catch (Exception $e) {
                    sleep(6);
                }
            }
            self::$forcerestart = false;
            self::$lastbrowsersessionstart = time();
        }

        $moreinfo = 'More info in ' . behat_command::DOCS_URL . '#Running_tests';
        $driverexceptionmsg = 'Selenium server is not running, you need to start it to run tests that involve Javascript. ' . $moreinfo;
        try {
            try {
                $session = $this->getSession();
            } catch (Exception $e) {
                // shezar: Let's try restarting the driver again, maybe this time it will work.
                try {
                    $this->getMink()->stopSessions();
                } catch (Exception $e) {
                }
                sleep(6);
                $session = $this->getSession();
            }
        } catch (CurlExec $e) {
            // Exception thrown by WebDriver, so only @javascript tests will be caugth; in
            // behat_util::check_server_status() we already checked that the server is running.
            throw new Exception($driverexceptionmsg, 0, $e);
        } catch (DriverException $e) {
            throw new Exception($driverexceptionmsg, 0, $e);
        } catch (UnknownError $e) {
            // Generic 'I have no idea' Selenium error. Custom exception to provide more feedback about possible solutions.
            $this->throw_unknown_exception($e);
        } catch (Exception $e) {
            throw new Exception(get_string('unknownexceptioninfo', 'tool_behat'), 0, $e);
        }


        // We need the Mink session to do it and we do it only before the first scenario.
        if (self::is_first_scenario()) {
            behat_selectors::register_moodle_selectors($session);
            behat_context_helper::set_main_context($event->getContext()->getMainContext());
        }

        // Reset mink session between the scenarios.
        try {
            $session->reset();
        } catch (Exception $e) {
            // shezar: This point is reached when Chrome driver freezes, let's gite it one more kick.
            try {
                sleep(5);
                $session->restart();
                sleep(5);
                $session->reset();
            } catch (Exception $e) {
                throw new Exception('Resetting of Mink session failed', 0, $e);
            }
        }

        // Reset $SESSION.
        \core\session\manager::init_empty_session();

        behat_util::reset_all_data();

        // Assign valid data to admin user (some generator-related code needs a valid user).
        $user = $DB->get_record('user', array('username' => 'admin'));
        \core\session\manager::set_user($user);

        // Start always in the the homepage.
        try {
            // Let's be conservative as we never know when new upstream issues will affect us.
            $session->visit($this->locate_path('/'));
        } catch (Exception $e) {
            // shezar: Something weird is going on, wait a bit and retry before stopping the whole run.
            try {
                $session->restart();
                sleep(5);
                $session->visit($this->locate_path('/'));
            } catch (Exception $e) {
                throw new Exception('Visiting of the main page failed', 0, $e);
            }
        }

        raise_memory_limit(MEMORY_EXTRA); // shezar includes very many files.
        // Checking that the root path is a Moodle test site.
        if (self::is_first_scenario()) {
            $notestsiteexception = new Exception('The base URL (' . $CFG->wwwroot . ') is not a behat test site, ' .
                'ensure you started the built-in web server in the correct directory or your web server is correctly started and set up');
            $this->find("xpath", "//head/child::title[normalize-space(.)='" . behat_util::BEHATSITENAME . "']", $notestsiteexception);

            self::$initprocessesfinished = true;
        }
        // Run all test with medium (1024x768) screen size, to avoid responsive problems.
        try {
            $this->resize_window('medium');
        } catch (Exception $e) {
            throw new Exception('Error resizing the main page', 0, $e);
        }

        try {
            $this->wait_for_pending_js();
        } catch (Exception $e) {
            try {
                // We get here when devs do not close changed form at end of test and unsaved form data alert pops up.
                $session->restart();
                sleep(5);
                $session->visit($this->locate_path('/'));
                sleep(2);
                $this->wait_for_pending_js();
            } catch (Exception $e) {
                throw new Exception('Main JS page not initialied properly', 0, $e);
            }
        }
    }

    /**
     * shezar: nothing to do
     *
     * @param StepEvent $event event fired before step.
     * @BeforeStep @javascript
     */
    public function before_step_javascript(StepEvent $event) {
        // shezar: we do not know if the previous step failed or not,
        //         doing JS stuff makes no sense, do it after the step when we know the result!
    }

    /**
     * Wait for JS to complete after finishing the step.
     *
     * With this we ensure that there are not AJAX calls
     * still in progress.
     *
     * Executed only when running against a real browser. We wrap it
     * all in a try & catch to forward the exception to i_look_for_exceptions
     * so the exception will be at scenario level, which causes a failure, by
     * default would be at framework level, which will stop the execution of
     * the run.
     *
     * @param StepEvent $event event fired after step.
     * @AfterStep @javascript
     */
    public function after_step_javascript(StepEvent $event) {
        global $CFG;

        // Save a screenshot if the step failed.
        if (!empty($CFG->behat_faildump_path) &&
                $event->getResult() === StepEvent::FAILED) {
            $this->take_screenshot($event);
        }

        // shezar: do not try to wait for anything here if not success, we are going to
        //         restart the browser anyway to make sure there are no leftovers.
        if ($event->getResult() === StepEvent::PASSED) {
            try {
                $this->wait_for_pending_js();
            } catch (Exception $e) {
                // If there is any problem the next step will fail
                // and we will restart the browser afterwards.
                // Do NOT close any alerts here, we want to know when stuff goes wrong!
                behat_hooks::$forcerestart = true;
            }
        }
    }

    /**
     * Execute any steps required after the step has finished.
     *
     * This includes creating an HTML dump of the content if there was a failure.
     *
     * @param StepEvent $event event fired after step.
     * @AfterStep
     */
    public function after_step(StepEvent $event) {
        global $CFG, $DB;

        // shezar: better restart browser after any failure to prevent cascading problems.
        if ($event->getResult() === StepEvent::FAILED) {
            self::$forcerestart = true;
        } else if ($event->getResult() === StepEvent::UNDEFINED) {
            self::$forcerestart = true;
        }

        // Save the page content if the step failed.
        if (!empty($CFG->behat_faildump_path) &&
                $event->getResult() === StepEvent::FAILED) {
            $this->take_contentdump($event);
        }

        // Abort any open transactions to prevent subsequent tests hanging.
        // This does the same as abort_all_db_transactions(), but doesn't call error_log() as we don't
        // want to see a message in the behat output.
        if ($event->hasException()) {
            if ($DB && $DB->is_transaction_started()) {
                $DB->force_transaction_rollback();
            }
        }
    }

    /**
     * shezar: nothing to do
     *
     * @param ScenarioEvent $event event fired after scenario.
     * @AfterScenario @_switch_window
     */
    public function after_scenario_switchwindow(ScenarioEvent $event) {
        // shezar: let's use our own session restart tricks in the switch_to_window() step itself.
    }

    /**
     * Getter for self::$faildumpdirname
     *
     * @return string
     */
    protected function get_run_faildump_dir() {
        return self::$faildumpdirname;
    }

    /**
     * Take screenshot when a step fails.
     *
     * @throws Exception
     * @param StepEvent $event
     */
    protected function take_screenshot(StepEvent $event) {
        // Goutte can't save screenshots.
        if (!$this->running_javascript()) {
            return false;
        }

        list ($dir, $filename) = $this->get_faildump_filename($event, 'png');
        try {
            $this->saveScreenshot($filename, $dir);
        } catch (Exception $e) {
            // shezar: this must not throw exception!!!
        }
        // shezar: fix new file permissions!
        global $CFG;
        @chmod($dir . DIRECTORY_SEPARATOR . $filename, $CFG->filepermissions);
    }

    /**
     * Take a dump of the page content when a step fails.
     *
     * @throws Exception
     * @param StepEvent $event
     */
    protected function take_contentdump(StepEvent $event) {
        list ($dir, $filename) = $this->get_faildump_filename($event, 'html');

        $fh = fopen($dir . DIRECTORY_SEPARATOR . $filename, 'w');
        try {
            fwrite($fh, $this->getSession()->getPage()->getContent());
        } catch (Exception $e) {
            // shezar: this must not throw exception!!!
            fwrite($fh, $e->getMessage() . "\n" . $e->getTraceAsString());
        }
        fclose($fh);
        // shezar: fix new file permissions!
        global $CFG;
        @chmod($dir . DIRECTORY_SEPARATOR . $filename, $CFG->filepermissions);
    }

    /**
     * Determine the full pathname to store a failure-related dump.
     *
     * This is used for content such as the DOM, and screenshots.
     *
     * @param StepEvent $event
     * @param String $filetype The file suffix to use. Limited to 4 chars.
     */
    protected function get_faildump_filename(StepEvent $event, $filetype) {
        global $CFG;

        // All the contentdumps should be in the same parent dir.
        if (!$faildumpdir = self::get_run_faildump_dir()) {
            $faildumpdir = self::$faildumpdirname = date('Ymd_His');

            $dir = $CFG->behat_faildump_path . DIRECTORY_SEPARATOR . $faildumpdir;

            if (!is_dir($dir) && !mkdir($dir, $CFG->directorypermissions, true)) {
                // It shouldn't, we already checked that the directory is writable.
                throw new Exception('No directories can be created inside $CFG->behat_faildump_path, check the directory permissions.');
            }
        } else {
            // We will always need to know the full path.
            $dir = $CFG->behat_faildump_path . DIRECTORY_SEPARATOR . $faildumpdir;
        }

        // The scenario title + the failed step text.
        // We want a i-am-the-scenario-title_i-am-the-failed-step.$filetype format.
        $filename = $event->getStep()->getParent()->getTitle() . '_' . $event->getStep()->getText();
        $filename = preg_replace('/([^a-zA-Z0-9\_]+)/', '-', $filename);

        // File name limited to 255 characters. Leaving 4 chars for the file
        // extension as we allow .png for images and .html for DOM contents.
        $filename = substr($filename, 0, 250) . '.' . $filetype;

        return array($dir, $filename);
    }

    /**
     * Internal step definition to find exceptions, debugging() messages and PHP debug messages.
     *
     * Part of behat_hooks class as is part of the testing framework, is auto-executed
     * after each step so no features will splicitly use it.
     *
     * @Given /^I look for exceptions$/
     * @throw Exception Unknown type, depending on what we caught in the hook or basic \Exception.
     * @see Moodle\BehatExtension\Tester\MoodleStepTester
     */
    public function i_look_for_exceptions() {

        // Look for exceptions displayed on page.
        $this->look_for_exceptions();

    }

    /**
     * Returns whether the first scenario of the suite is running
     *
     * @return bool
     */
    protected static function is_first_scenario() {
        return !(self::$initprocessesfinished);
    }

    /**
     * Throws an exception after appending an extra info text.
     *
     * @throws Exception
     * @param UnknownError $exception
     * @return void
     */
    protected function throw_unknown_exception(UnknownError $exception) {
        $text = get_string('unknownexceptioninfo', 'tool_behat');
        throw new Exception($text . PHP_EOL . $exception->getMessage(), 0, $exception);
    }

}

