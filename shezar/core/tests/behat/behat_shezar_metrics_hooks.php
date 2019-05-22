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
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package shezar_core
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/behat_shezar_metrics.php');

use Behat\Behat\Event\SuiteEvent as SuiteEvent,
    Behat\Behat\Event\FeatureEvent as FeatureEvent,
    Behat\Behat\Event\StepEvent as StepEvent;

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
 * @package   shezar_core
 * @category  test
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @copyright Copyright (C) 2015 shezar Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_shezar_metrics_hooks extends behat_base {
    /**
     * behat_shezar_metrics_interface saving state
     * @var integer
     */
    protected $metrics_state = behat_shezar_metrics_interface::TURN_OFF;

    /**
     * behat_shezar_metrics_interface custom name
     * @var string
     */
    protected  $metrics_name = '';

    /**
     * behat_shezar_metrics_interface data
     *
     * @var array
     */
    protected static $metrics = array();

    /**
     * Suite initialized
     *
     * @var bool
     */
    private static $suitestarted = false;

    /**
     * Filenames
     */
    const METRICS_FILE = 'metrics.json';
    const VERSION_FILE = 'version.txt';
    const CURRENT_PATH = '/current/';

    /**
     * Prepare environment for taking metrics: clean previous metrics, set version of shezar LMS, and clarify tag.
     * Cannot use before_suite as it will be executed before Moodle bootstrap.
     * @beforeFeature
     */
    public static function before_first_feature(FeatureEvent $event) {
        global $CFG;
        if (self::$suitestarted || !defined("MDL_PERF") || !MDL_PERF) {
            return;
        }
        self::$suitestarted = true;

        // Custom tag to take metrics.
        if (empty($CFG->behat_metrics_tag)) {
            $CFG->behat_metrics_tag = 'shezarmetrics';
        }
        // Clean previous metrics.
        self::metrics_path_must_exist();

        $metricsfile = $CFG->behat_metrics_path . self::CURRENT_PATH . self::METRICS_FILE;
        if (file_exists($metricsfile)) {
            unlink($metricsfile);
        }

        foreach (glob($CFG->behat_metrics_path . self::CURRENT_PATH . 'z_*.part.json') as $partfile) {
            unlink($partfile);
        }

        // Add version.
        $verfile = $CFG->behat_metrics_path . self::CURRENT_PATH . self::VERSION_FILE;
        $verfp = fopen($verfile, 'w');
        if (!$verfp) {
            throw new \Exception("Cannot open $verfile for writing");
        }

        require($CFG->dirroot . '/version.php');

        fwrite($verfp, $shezar->version . "\n");
        fwrite($verfp, $shezar->build . "\n");
        fclose($verfp);
    }

    /**
     * Reset metrics state
     *
     * @BeforeScenario
     */
    public function before_scenario($event) {
        $this->metrics_state = behat_shezar_metrics_interface::TURN_OFF;
    }

    /**
     * Execute any steps required after the step has finished.
     *
     * This includes creating an HTML dump of the content if there was a failure.
     *
     * @AfterStep
     */
    public function after_step(StepEvent $event) {
        global $CFG;

        if (!defined("MDL_PERF") || !MDL_PERF) {
            return;
        }

        $step = $event->getSnippet();
        if ($step instanceof behat_shezar_metrics_interface) {
            $change = $step->get_metrics_state_change();
            if ($change != behat_shezar_metrics_interface::NO_CHANGE) {
                $this->metrics_state = $change;
            }
            $this->metrics_name = $step->get_metrics_name();
        }
        $scenario = $event->getLogicalParent();

        $save = $scenario->hasTag($CFG->behat_metrics_tag);
        if ($save) {
            $this->add_metrics($event);
        }
    }

    /**
     * Flush metrics to json file
     *
     * @AfterFeature
     */
    public static function after_feature(FeatureEvent $event) {
        if (!defined("MDL_PERF") || !MDL_PERF) {
            return;
        }

        if (self::$metrics) {
            self::save_metrics();
        }
        self::$metrics = array();
    }

    /**
     * Collect all metrics into one json file
     *
     * @AfterSuite
     */
    public static function after_suite(SuiteEvent $event) {
        global $CFG;

        if (!defined("MDL_PERF") || !MDL_PERF) {
            return;
        }

        self::metrics_path_must_exist();
        $metricsfile = $CFG->behat_metrics_path . self::CURRENT_PATH . self::METRICS_FILE;

        $metrics = array();

        foreach (glob($CFG->behat_metrics_path . self::CURRENT_PATH . '*.part.json') as $partfile) {
            $partmetrics = json_decode(file_get_contents($partfile), true);
            $metrics = array_merge($metrics, $partmetrics);
            unlink($partfile);
        }

        file_put_contents($metricsfile, json_encode($metrics));
    }

    /**
     * Does actual saving of metrics taken from current page
     *
     * @param StepEvent $event
     */
    protected function add_metrics(StepEvent $event) {
        // TODO: Optimize by removing metrics that differs only by step (and page is the same)
        self::$metrics[] = $this->get_metrics($event);
    }

    /**
     * Get metrics data
     *
     * @param StepEvent $event
     * @return array metrics data
     */
    protected function get_metrics(StepEvent $event) {
        $driver = $this->getSession()->getDriver();

        $metrics = array();
        $matches = array();
        try {
            $dbqueriesstr = $driver->getText("//footer//span[@class='dbqueries']");
            preg_match("/DB reads\/writes: (\d+)\/(\d+)/", $dbqueriesstr, $matches);
            $metrics['dbreads'] = $matches[1];
            $metrics['dbwrites'] = $matches[2];

            $sessionstr = $driver->getText("//footer//span[@class='sessionsize']");
            preg_match("/Session \(core\\\\session\\\\file\): (\d+|\d*\.\d+)KB/", $sessionstr, $matches);
            $metrics['sessionsize'] = $matches[1];

            $includedstr = $driver->getText("//footer//span[@class='included']");
            preg_match("/Included (\d+) files/", $includedstr, $matches);
            $metrics['included'] = $matches[1];

        } catch (\Behat\Mink\Exception\ExpectationException $ex) {
            throw new \Exception("Metrics require forced performance data output. Do this by adding define('MDL_PERF', true); into config.php file");
        } catch (\InvalidArgumentException $ex) {
            if ($ex->getMessage() === 'The current node list is empty.') {
                throw new \Exception("Metrics require forced performance data output. Do this by adding define('MDL_PERF', true); into config.php file");
            }
            throw $ex;
        }

        // Getting backtrace to get Scenario and Step information.
        $step = $event->getStep();

        $scenario = $event->getLogicalParent();
        $feature = $scenario->getFeature();
        $file = $feature->getFile();

        $data = array(
            'file' => $file,
            'feature' => $feature->getTitle(),
            'scenario' => $scenario->getTitle(),
            'step' => $step->getText(),
            'name' => $this->metrics_name,
            'url' => $driver->getCurrentUrl(),
            'metrics' => $metrics,
        );
        return $data;
    }

    /**
     * Saves recorded metrics.
     *
     * @throws Exception
     */
    protected static function save_metrics() {
        global $CFG;
        self::metrics_path_must_exist();

        if (empty(self::$metrics)) {
            return;
        }
        $path = $CFG->behat_metrics_path . self::CURRENT_PATH;

        $basename = self::$metrics[0]['file'];

        // Remove dirroot from path.
        $dirpath = str_replace('//', '/', $CFG->dirroot . '/');
        $dirpathlen = strlen($dirpath);
        foreach(self::$metrics as &$item) {
            if (strpos($item['file'], $dirpath) === 0) {
                $item['file'] = substr($item['file'], $dirpathlen);
            }
        }

        // Generate unique name for json part file.
        $ind = 0;
        $format = "%sz_%s_%d.part.json";
        while (file_exists(sprintf($format, $path, sha1($basename), $ind))) {
            $ind++;
        }
        file_put_contents(sprintf($format, $path, sha1($basename), $ind), json_encode(self::$metrics));
    }

    /**
     * Check that metrics path is set, exists, and writable
     */
    protected static function metrics_path_must_exist() {
        global $CFG;
        if (isset($CFG->behat_metrics_path)) {
            $path = $CFG->behat_metrics_path . self::CURRENT_PATH;
            if (!empty($path) && is_dir($path) && is_writable($path)) {
                return;
            }
            throw new \Exception("Metrics path '$path' is not writable");
        }
        throw new \Exception('Metrics path variable $CFG->behat_metrics_path is not set');
    }
}