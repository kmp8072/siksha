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
 * @copyright 2016 onwards shezar Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@shezarlms.com>>
 * @package   core_output
 */

use core\output\flex_icon;
use core\output\mustache_flex_icon_helper;

defined('MOODLE_INTERNAL') || die();

class mustache_flex_icon_helper_testcase extends basic_testcase {

    /**
     * @var core_renderer
     */
    protected static $renderer;

    /**
     * @var \Mustache_Engine
     */
    protected static $engine;


    public static function setUpBeforeClass() {

        global $CFG;

        require_once("{$CFG->dirroot}/lib/mustache/src/Mustache/Autoloader.php");
        Mustache_Autoloader::register();

        self::$renderer = new \core_renderer(new moodle_page(), '/');
        // Get the engine from the renderer. We do this once cause its mad.
        $class = new ReflectionClass(self::$renderer);
        $method = $class->getMethod('get_mustache');
        $method->setAccessible(true);
        self::$engine = $method->invoke(self::$renderer);
    }

    /**
     * Returns a LambdaHelper populated with the given contextdata.
     *
     * @param array|stdClass $contextdata
     * @return Mustache_LambdaHelper
     */
    protected function get_lambda_helper($contextdata = []) {
        return new \Mustache_LambdaHelper(self::$engine, new \Mustache_Context($contextdata));
    }

    /**
     * It should generate the same output as rendering the renderable without customdata.
     *
     * @covers \core\output\mustache_flex_icon_helper::__construct
     * @covers \core\output\mustache_flex_icon_helper::flex_icon
     */
    public function test_flex_icon_output_without_customdata() {

        $identifier = 'permissions-check';
        $mustachehelper = new mustache_flex_icon_helper(self::$renderer);

        $expected = self::$renderer->render(new flex_icon($identifier));
        $actual = $mustachehelper->flex_icon($identifier, $this->get_lambda_helper());

        $this->assertEquals($expected, $actual);

    }

    /**
     * It should generate the same output as rendering the renderable with customdata.
     *
     * @covers \core\output\mustache_flex_icon_helper::flex_icon
     */
    public function test_flex_icon_output_with_customdata() {

        $identifier = 'permissions-check';
        $customdata = array(
            'classes' => 'ft-state-success ft-size-700'
        );
        $helperstring = "{$identifier}, " . json_encode($customdata);

        $mustachehelper = new mustache_flex_icon_helper(self::$renderer);

        $expected = self::$renderer->render(new flex_icon($identifier, $customdata));
        $actual = $mustachehelper->flex_icon($helperstring, $this->get_lambda_helper());

        $this->assertEquals($expected, $actual);

    }

    /**
     * It should throw an exception if helper JSON cannot be parsed.
     *
     * @covers \core\output\mustache_flex_icon_helper::flex_icon
     */
    public function test_flex_icon_throws() {

        $malformedjson = '{ this # is not valid JSON : 7 \ }';
        $helperstring = "cog, {$malformedjson}";

        $mustachehelper = new mustache_flex_icon_helper(self::$renderer);

        $this->setExpectedException('\coding_exception');
        $mustachehelper->flex_icon($helperstring, $this->get_lambda_helper());

    }

    /**
     * It should generate the same output as rendering the renderable with customdata.
     *
     * @covers \core\output\mustache_flex_icon_helper::flex_icon
     */
    public function test_flex_icon_output_with_variable_identifier() {

        $actualidentifier = 'settings';
        $variableidentifier = '{{test_icon}}';
        $customdata = array(
            'classes' => 'ft-state-success ft-size-700'
        );
        $helperstring = "{$variableidentifier}, " . json_encode($customdata);

        $expected = self::$renderer->render(new flex_icon($actualidentifier, $customdata));

        $lambdahelper = $this->get_lambda_helper(['test_icon' => $actualidentifier]);
        $mustachehelper = new mustache_flex_icon_helper(self::$renderer);
        $actual = $mustachehelper->flex_icon($helperstring, $lambdahelper);

        $this->assertEquals($expected, $actual);
    }

    /**
     * It should generate the same output as rendering the renderable with customdata.
     *
     * @covers \core\output\mustache_flex_icon_helper::flex_icon
     */
    public function test_flex_icon_output_with_variable_alt() {

        $actualidentifier = 'settings';
        $helperstring = '{{test_icon}}, { "alt": "{{alt}}" }';

        $expected = self::$renderer->render(new flex_icon($actualidentifier, ['alt' => get_string('settings')]));

        $lambdahelper = $this->get_lambda_helper(['test_icon' => $actualidentifier, 'alt' => get_string('settings')]);
        $mustachehelper = new mustache_flex_icon_helper(self::$renderer);
        $actual = $mustachehelper->flex_icon($helperstring, $lambdahelper);

        $this->assertEquals($expected, $actual);

    }

    /**
     * It should generate the same output as rendering the renderable with customdata.
     *
     * @covers \core\output\mustache_flex_icon_helper::flex_icon
     */
    public function test_flex_icon_output_with_complex_structure() {

        $actualidentifier = 'core|i/edit';
        $helperstring = '{{test_icon}}, { "alt": "{{alt}}", "classes": "{{classes}}" }';

        $expected = self::$renderer->render(new flex_icon($actualidentifier, ['alt' => get_string('settings'), 'classes' => 'test testing']));

        $lambdahelper = $this->get_lambda_helper(['test_icon' => $actualidentifier, 'alt' => get_string('settings'), 'classes' => 'test testing']);
        $mustachehelper = new mustache_flex_icon_helper(self::$renderer);
        $actual = $mustachehelper->flex_icon($helperstring, $lambdahelper);

        $this->assertEquals($expected, $actual);

    }
}
