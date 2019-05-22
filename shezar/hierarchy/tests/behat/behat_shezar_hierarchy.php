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
 * @package shezar
 * @subpackage hierarchy
 */

/**
 * Behat steps to generate hierarchies
 *
 * @package   shezar_hierarchy
 * @copyright 2014 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use Behat\Gherkin\Node\TableNode;

class behat_shezar_hierarchy extends behat_base {

    protected static $generator = null;

    protected function get_data_generator() {
        global $CFG;
        if (self::$generator === null) {
            require_once($CFG->libdir.'/testing/generator/lib.php');
            require_once($CFG->dirroot.'/shezar/hierarchy/tests/generator/lib.php');
            self::$generator = new shezar_hierarchy_generator(testing_util::get_data_generator());
        }
        return self::$generator;
    }

    /**
     * Create the requested framework
     *
     * @Given /^the following "(?P<prefix_string>(?:[^"]|\\")*)" frameworks exist:$/
     * @param string $prefix
     * @param TableNode $table
     * @throws Exception
     */
    public function the_following_frameworks_exist($prefix, TableNode $table) {
        $required = array(
            'idnumber'
        );
        $optional = array(
            'visible',
            'fullname',
            'description',
            'scale'
        );

        $data = $table->getHash();
        $firstrow = reset($data);

        // Check required fields are present.
        foreach ($required as $reqname) {
            if (!isset($firstrow[$reqname])) {
                throw new Exception('Frameworks require the field '.$reqname.' to be set');
            }
        }

        // Copy values, ready to pass on to the generator.
        foreach ($data as $row) {
            $record = array();
            foreach ($row as $fieldname => $value) {
                if (in_array($fieldname, $required)) {
                    $record[$fieldname] = $value;
                } else if (in_array($fieldname, $optional)) {
                    $record[$fieldname] = $value;
                } else {
                    throw new Exception('Unknown field '.$fieldname.' in framework definition');
                }
            }
            $this->get_data_generator()->create_framework($prefix, $record);
        }
    }

    /**
     * Create the requested hierarchy element
     *
     * @Given /^the following "(?P<prefix_string>(?:[^"]|\\")*)" hierarchy exists:$/
     * @param string $prefix
     * @param TableNode $table
     * @throws Exception
     */
    public function the_following_hierarchy_exists($prefix, TableNode $table) {
        global $DB;

        $required = array(
            'framework',
            'idnumber'
        );
        $optional = array(
            'fullname',
            'description', // This will be cleared to 'null' inside the data generator code.
            'visible',
            'parent', // ID number.
        );

        $data = $table->getHash();
        $firstrow = reset($data);

        // Check required fields are present.
        foreach ($required as $reqname) {
            if (!isset($firstrow[$reqname])) {
                throw new Exception('Hierarchy elements require the field '.$reqname.' to be set');
            }
        }

        foreach ($data as $row) {
            // Copy values, ready to pass on to the generator.
            $record = array();
            foreach ($row as $fieldname => $value) {
                if (in_array($fieldname, $required)) {
                    $record[$fieldname] = $value;
                } else if (in_array($fieldname, $optional)) {
                    $record[$fieldname] = $value;
                } else {
                    throw new Exception('Unknown field '.$fieldname.' in hierarchy definition');
                }
            }

            // Pre-process any fields that require transforming.
            $shortprefix = hierarchy::get_short_prefix($prefix);
            if (!$frameworkid = $DB->get_field("{$shortprefix}_framework", 'id', array('idnumber' => $record['framework']))) {
                throw new Exception("Unknown {$prefix} framework ID Number {$record['framework']}");
            }
            unset($record['framework']);
            if (!empty($record['parent'])) {
                if (!$parentid = $DB->get_field($shortprefix, 'id', array('idnumber' => $record['parent']))) {
                    throw new Exception("Unknown {$prefix} ID Number {$record['parentid']}");
                }
                $record['parentid'] = $parentid;
            }
            unset($record['parent']);

            $this->get_data_generator()->create_hierarchy($frameworkid, $prefix, $record);
        }
    }

    /**
     * Create or update the requested job assignment
     *
     * @Given /^the following job assignments exist:$/
     * @param TableNode $table
     * @throws Exception
     * @throws coding_exception
     */
    public function the_following_job_assignments_exist(TableNode $table) {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/shezar/hierarchy/prefix/position/lib.php');

        $required = array(
            'user', // Username.
        );
        $optional = array(
            'fullname',
            'shortname',
            'idnumber',
            'startdate',
            'enddate',
            'organisation', // ID number.
            'position', // ID number.
            'manager', // Username.
            'managerjaidnumber', // String.
            'appraiser', // Username.
            'tempmanager', // Username.
            'tempmanagerjaidnumber', // String.
            'tempmanagerexpirydate', // Unix datetime
        );

        $data = $table->getHash();
        $firstrow = reset($data);

        // Check required fields are present.
        foreach ($required as $reqname) {
            if (!isset($firstrow[$reqname])) {
                throw new Exception('Job assignments require the field '.$reqname.' to be set');
            }
        }

        foreach ($data as $row) {
            // Copy values, ready to pass on to the generator.
            $record = array();
            foreach ($row as $fieldname => $value) {
                if (in_array($fieldname, $required)) {
                    $record[$fieldname] = $value;
                } else if (in_array($fieldname, $optional)) {
                    $record[$fieldname] = $value;
                } else {
                    throw new Exception('Unknown field '.$fieldname.' in job assignment definition');
                }
            }

            // Pre-process any fields that require transforming.
            if (!$userid = $DB->get_field('user', 'id', array('username' => $record['user']))) {
                throw new Exception('Unknown user '.$record['user'].' in job assignment definition');
            }
            unset($record['user']);

            // Map Manager and managershortname to a user.
            if (!empty($record['managerjaidnumber'])) {
                if (empty($record['manager'])) {
                    throw new Exception('Must provide manager when specifying managerjaidnumber in job assignment definition');
                }
                if (!$managerid = $DB->get_field('user', 'id', array('username' => $record['manager']))) {
                    throw new Exception('Unknown manager '.$record['manager'].' in job assignment definition');
                }
                $managerja = \shezar_job\job_assignment::get_with_idnumber($managerid, $record['managerjaidnumber']);
                if (empty($managerja)) {
                    throw new Exception('Unknown managerjaidnumber '.$record['managerjaidnumber'].' for manager '.$record['manager'].' in job assignment definition');
                }
                $record['managerjaid'] = $managerja->id;
            } else if (!empty($record['manager'])) {
                if (!$managerid = $DB->get_field('user', 'id', array('username' => $record['manager']))) {
                    throw new Exception('Unknown manager '.$record['manager'].' in job assignment definition');
                }
                $managerja = \shezar_job\job_assignment::get_first($managerid, false);
                if (empty($managerja)) {
                    $managerja = \shezar_job\job_assignment::create_default($managerid);
                }
                $record['managerjaid'] = $managerja->id;
            }
            unset($record['managerjaidnumber']);
            unset($record['manager']);

            // Map Temp Manager and managershortname to a user.
            if (!empty($record['tempmanagerjaidnumber'])) {
                if (empty($record['tempmanager'])) {
                    throw new Exception('Must provide tempmanager when specifying tempmanagerjaidnumber in job assignment definition');
                }
                if (!$tempmanagerid = $DB->get_field('user', 'id', array('username' => $record['tempmanager']))) {
                    throw new Exception('Unknown tempmanager '.$record['tempmanager'].' in job assignment definition');
                }
                $tempmanagerja = \shezar_job\job_assignment::get_with_idnumber($tempmanagerid, $record['tempmanagerjaidnumber']);
                if (empty($tempmanagerja)) {
                    throw new Exception('Unknown tempmanagerjaidnumber '.$record['tempmanagerjaidnumber'].' for tempmanager '.$record['tempmanager'].' in job assignment definition');
                }
                $record['tempmanagerjaid'] = $tempmanagerja->id;
            } else if (!empty($record['tempmanager'])) {
                if (!$tempmanagerid = $DB->get_field('user', 'id', array('username' => $record['tempmanager']))) {
                    throw new Exception('Unknown tempmanager '.$record['tempmanager'].' in job assignment definition');
                }
                $tempmanagerja = \shezar_job\job_assignment::get_first($tempmanagerid, false);
                if (empty($tempmanagerja)) {
                    $tempmanagerja = \shezar_job\job_assignment::create_default($tempmanagerid);
                }
                $record['tempmanagerjaid'] = $tempmanagerja->id;
            }
            unset($record['tempmanagerjaidnumber']);
            unset($record['tempmanager']);

            // Map Appraiser to a user.
            if (!empty($record['appraiser'])) {
                if (!$appraiserid = $DB->get_field('user', 'id', array('username' => $record['appraiser']))) {
                    throw new Exception('Unknown appraiser '.$record['appraiser'].' in job assignment definition');
                }
                $record['appraiserid'] = $appraiserid;
            }
            unset($record['appraiser']);

            // Map Organisation ID Number to an organisation.
            if (!empty($record['organisation'])) {
                if (!$organisationid = $DB->get_field('org', 'id', array('idnumber' => $record['organisation']))) {
                    throw new Exception('Unknown organisation '.$record['organisation'].' in job assignment definition');
                }
                $record['organisationid'] = $organisationid;
            }
            unset($record['organisation']);

            // Map Position ID Number to a position.
            if (!empty($record['position'])) {
                if (!$positionid = $DB->get_field('pos', 'id', array('idnumber' => $record['position']))) {
                    throw new Exception('Unknown position '.$record['position'].' in job assignment definition');
                }
                $record['positionid'] = $positionid;
            }
            unset($record['position']);

            // Check if this is an update.
            if (!empty($record['idnumber'])) {
                $ja = \shezar_job\job_assignment::get_with_idnumber($userid, $record['idnumber'], false);
                if (!empty($ja)) {
                    $ja->update($record);
                    continue; // Don't need to create new job assignment.
                }
            }

            // Create using the default function because it will set default idnumber if it is not specified.
            \shezar_job\job_assignment::create_default($userid, $record);
        }
    }
}
