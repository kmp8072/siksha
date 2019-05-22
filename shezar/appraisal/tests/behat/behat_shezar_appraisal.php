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
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @package shezar_appraisal
 * @category  test
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

class behat_shezar_appraisal extends behat_base {

    /**
     * Creates number of questions on an appraisal page.
     *
     * @Given /^I create "([0-9]*)" appraisal questions on the page "([^"]*)"$/
     *
     * @param string    $numberofquestions number of questions
     * @param string    $page page number
     */
    public function create_appraisal_questions_on_page($numberofquestions, $page) {
        global $DB;

        /** @var shezar_appraisal_generator $datagenerator */
        $datagenerator = testing_util::get_data_generator()->get_plugin_generator('shezar_appraisal');

        $page = $DB->get_record('appraisal_stage_page', array('name' => $page));

        // NOTE: MySQL has relatively low limits on number of varchar table columns, we cannot use 'text' here.
        $data = array('datatype' => 'datepicker', 'startyear' => 1975, 'stopyear' => 2020, 'withtime' => 0);
        for ($i = 1; $i <= $numberofquestions; $i++) {
            $datagenerator->create_complex_question($page->id, $data);
        }
    }


}
