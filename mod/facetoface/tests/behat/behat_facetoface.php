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
 * @author Maria Torres <maria.torres@shezarlms.com>
 * @package mod_facetoface
 */

// NOTE: no MOODLE_INTERNAL used, this file may be required by behat before including /config.php.
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given;
use Behat\Gherkin\Node\TableNode as TableNode;

/**
 * Contains functions used by behat to test functionality.
 *
 * @package    mod_facetoface
 * @category   test
 */
class behat_facetoface extends behat_base {

    /**
     * Create a session in the future based on the current date.
     *
     * @Given /^I fill seminar session with relative date in form data:$/
     * @param TableNode $data
     * @return array the list of actions to perform
     */
    public function i_fill_seminar_session_with_relative_date_in_form_data(TableNode $data) {

        $dataclone = clone $data;
        $rows = array();
        $timestartday = '';
        $timestartmonth = '';
        $timestartyear = '';
        $timestarthour = '';
        $timestartmin = '';
        $timestartzone = '';
        $timefinishday = '';
        $timefinishmonth = '';
        $timefinishyear = '';
        $timefinishhour = '';
        $timefinishmin = '';
        $timefinishzone = '';

        foreach ($dataclone->getRows() as $row) {
            switch ($row[0]) {
                case 'timestart[day]':
                    $timestartday = (!empty($row[1]) ? $row[1] . ' days': '');
                    break;
                case 'timestart[month]':
                    $timestartmonth = (!empty($row[1]) ? $row[1] . ' months': '');
                    break;
                case 'timestart[year]':
                    $timestartyear = (!empty($row[1]) ? $row[1] . ' years' : '');
                    break;
                case 'timestart[hour]':
                    $timestarthour = (!empty($row[1]) ? $row[1] . ' hours': '');
                    break;
                case 'timestart[minute]':
                    $timestartmin = (!empty($row[1]) ? $row[1] . ' minutes': '');
                    break;
                case 'timestart[timezone]':
                    $timestartzone = (!empty($row[1]) ? $row[1] : '');
                    $rows[] = $row;
                    break;
                case 'timefinish[day]':
                    $timefinishday = (!empty($row[1]) ? $row[1]  . ' days': '');
                    break;
                case 'timefinish[month]':
                    $timefinishmonth = (!empty($row[1]) ? $row[1]  . ' months': '');
                    break;
                case 'timefinish[year]':
                    $timefinishyear = (!empty($row[1]) ? $row[1]  . ' years' : '');
                    break;
                case 'timefinish[hour]':
                    $timefinishhour = (!empty($row[1]) ? $row[1] . ' hours': '');
                    break;
                case 'timefinish[minute]':
                    $timefinishmin = (!empty($row[1]) ? $row[1] . ' minutes': '');
                    break;
                case 'timefinish[timezone]':
                    $timefinishzone = (!empty($row[1]) ? $row[1] : '');
                    $rows[] = $row;
                    break;
                default:
                    $rows[] = $row;
                    break;
            }
        }

        if ($timestartzone !== '') {
            date_default_timezone_set($timestartzone);
        }
        $now = time();
        $newdate = strtotime("{$timestartmonth} {$timestartday} {$timestartyear} {$timestarthour} {$timestartmin}" , $now) ;
        $startdate = new DateTime(date('Y-m-d H:i' , $newdate));

        // Values for the minutes field should be multiple of 5 (from 00 to 55). So we need to fix these values.
        $startmin = $startdate->format("i");
        $minutes = (($startmin % 5 ) !== 0) ? floor($startmin / 5) * 5 + 5 : ($startmin / 5) * 5;

        if ($minutes > 55) {
            $minutes = 0;
            $startdate->add(new DateInterval('PT1H'));
        }

        $startdate->setTime($startdate->format('H'), $minutes);

        if ($timefinishzone !== '') {
            date_default_timezone_set($timefinishzone);
        }
        $newdate = strtotime("{$timefinishmonth} {$timefinishday} {$timefinishyear} {$timefinishhour} {$timefinishmin}" , $now) ;
        $finishdate = new DateTime(date('Y-m-d H:i' , $newdate));

        $finishmin = $finishdate->format('i');
        $minutes = (($finishmin % 5 ) !== 0) ? floor($finishmin / 5) * 5 + 5 : ($finishmin / 5) * 5;
        if ($minutes > 55) {
            $minutes = 0;
            $finishdate->add(new DateInterval('PT1H'));
        }
        $finishdate->setTime($finishdate->format('H'), $minutes);

        // Replace values for timestart.
        $rows[] = array('timestart[month]', (int) $startdate->format('m'));
        $rows[] = array('timestart[day]', (int) $startdate->format('d'));
        $rows[] = array('timestart[year]', (int) $startdate->format('Y'));
        $rows[] = array('timestart[hour]', (int) $startdate->format('H'));
        $rows[] = array('timestart[minute]', (int) $startdate->format('i'));

        // Replace values for timefinish.
        $rows[] = array('timefinish[month]', (int) $finishdate->format('m'));
        $rows[] = array('timefinish[day]', (int) $finishdate->format('d'));
        $rows[] = array('timefinish[year]', (int) $finishdate->format('Y'));
        $rows[] = array('timefinish[hour]', (int) $finishdate->format('H'));
        $rows[] = array('timefinish[minute]', (int) $finishdate->format('i'));

        // Set the the rows back to data.
        $dataclone->setRows($rows);

        return array(
            new Given('I set the following fields to these values:', $dataclone),
        );
    }

    /**
     * Click on a selected link that is located in a table row.
     *
     * @Given /^I click on the link "([^"]*)" in row (\d+)$/
     */
    public function i_click_on_the_link_in_row($text, $row) {
        $xpath = "//table//tbody//tr[{$row}]//a[text()='{$text}']";
        $node = $this->find(
            'xpath',
            $xpath,
            new \Behat\Mink\Exception\ExpectationException('Could not find specific link "'.$text.'" in the row' . $row . $xpath, $this->getSession())
        );
        $node->click();
    }

    /**
     * Select to approve the given user.
     *
     * @Given /^I select to approve "([^"]*)"$/
     */
    public function i_select_to_approve($user) {
        return array(
            new Given('I click on "input[value=\'2\']" "css_element" in the "'.$user.'" "table_row"')
        );
    }

    /**
     * Use magic to alter facetoface cut off to value which is not allowed in UI so that we do not have to wait in tests.
     *
     * @Given /^I use magic to set Seminar "([^"]*)" to send capacity notification two days ahead$/
     */
    public function i_use_magic_to_set_seminar_cutoff_one_day_back($facetofacename) {
        global $DB;

        $facetoface = $DB->get_record('facetoface', array('name' => $facetofacename), '*', MUST_EXIST);
        $session = $DB->get_record('facetoface_sessions', array('facetoface' => $facetoface->id), '*', MUST_EXIST);
        $session->sendcapacityemail = 1;
        $session->cutoff = DAYSECS * 2;
        $DB->update_record('facetoface_sessions', $session);
    }

    /**
     * Make duplicates of notification title (in all seminar activities of all courses). Titles must match exactly.
     *
     * @Given /^I make duplicates of seminar notification "([^"]*)"$/
     */
    public function i_make_duplicates_of_seminar_notification($title) {
        global $DB;
        $notifications = $DB->get_records('facetoface_notification', array('title' => $title));
        foreach ($notifications as $note) {
            $note->id = null;
            $DB->insert_record('facetoface_notification', $note);
        }
    }

    /**
     * Checks if a custom room of the given name exists in the database.
     *
     * @Given /^a seminar custom room called "([^"]*)" (should not|should) exist$/
     *
     * @throws \Behat\Mink\Exception\ExpectationException
     * @param string $roomname
     * @param string $should
     */
    public function a_seminar_custom_room_called_should_exist($roomname, $should) {
        global $DB;

        $params = array(
            'custom' => 1,
            'name' => $roomname
        );
        $exists = $DB->record_exists('facetoface_room', $params);
        if ($should === 'should') {
            if (!$exists) {
                throw new \Behat\Mink\Exception\ExpectationException('Seminar custom room by the name of "' . $roomname . '" does not exist', $this->getSession());
            }
        } else {
            if ($exists) {
                throw new \Behat\Mink\Exception\ExpectationException('Seminar custom room by the name of "' . $roomname . '" still exists', $this->getSession());
            }
        }
    }

    /**
     * Checks if a custom asset of the given name exists in the database.
     *
     * @Given /^a seminar custom asset called "([^"]*)" (should not|should) exist$/
     *
     * @throws \Behat\Mink\Exception\ExpectationException
     * @param string $assetname
     * @param string $should
     */
    public function a_seminar_custom_asset_called_should_exist($assetname, $should) {
        global $DB;

        $params = array(
            'custom' => 1,
            'name' => $assetname
        );
        $exists = $DB->record_exists('facetoface_asset', $params);
        if ($should === 'should') {
            if (!$exists) {
                throw new \Behat\Mink\Exception\ExpectationException('Seminar custom asset by the name of "' . $assetname . '" does not exist', $this->getSession());
            }
        } else {
            if ($exists) {
                throw new \Behat\Mink\Exception\ExpectationException('Seminar custom asset by the name of "' . $assetname . '" still exists', $this->getSession());
            }
        }
    }
}
