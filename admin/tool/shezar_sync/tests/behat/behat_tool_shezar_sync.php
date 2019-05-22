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
 * @author Sam Hemelryk <sam.hemelryk@shezarlms.com>
 * @package tool_shezar_sync
 * @copyright 2015 shezar Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Test definitions for the HR Import tool.
 *
 * @author Sam Hemelryk <sam.hemelryk@shezarlms.com>
 * @copyright 2015 shezar Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_shezar_sync extends behat_base {

    /**
     * Toggle the state of an HR Import element.
     *
     * @Given /^I "(Enable|Disable)" the "([^"]*)" HR Import element$/
     */
    public function i_the_hr_import_element($state, $element) {
        $xpath = "//table[@id='elements']//descendant::text()[contains(.,'{$element}')]//ancestor::tr//a[@title='{$state}']";
        $exception = new ElementNotFoundException($this->getSession(), 'Could not find state switch for the given HR Import element');
        $node = $this->find('xpath', $xpath, $exception);
        if ($node) {
            $node->click();
        }
    }
}