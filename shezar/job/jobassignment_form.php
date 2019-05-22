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
 * @author Nathan Lewis <nathan.lewis@shezarlearning.com>
 * @package shezar_job
 */

require_once($CFG->dirroot . '/lib/formslib.php');

class job_assignment_form extends moodleform {

    function definition () {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/user/lib.php');

        $mform = $this->_form;
        $jobassignment = $this->_customdata['jobassignment'];
        $submitted = $this->_customdata['submitted'];
        $submittedpositionid = $this->_customdata['submittedpositionid'];
        $submittedorganisationid = $this->_customdata['submittedorganisationid'];
        $submittedmanagerid = $this->_customdata['submittedmanagerid'];
        $submittedmanagerjaid = $this->_customdata['submittedmanagerjaid'];
        $submittedappraiserid = $this->_customdata['submittedappraiserid'];
        $submittedtempmanagerid = $this->_customdata['submittedtempmanagerid'];
        $submittedtempmanagerjaid = $this->_customdata['submittedtempmanagerjaid'];
        $editoroptions = $this->_customdata['editoroptions'];
        $canedit = $this->_customdata['canedit'];
        $canedittempmanager = $this->_customdata['canedittempmanager'];
        $userid = $this->_customdata['userid'];
        /** @var bool $canviewemail - whether the current user can view another user's email when viewing user details. */
        $canviewemail = in_array('email', get_extra_user_fields(context_system::instance()));

        if ($submitted) {
            $positionid = $submittedpositionid;
            $organisationid = $submittedorganisationid;
            $appraiserid = $submittedappraiserid;
            $managerid = $submittedmanagerid;
            $managerjaid = $submittedmanagerjaid;
        } else if ($jobassignment) {
            $positionid = $jobassignment->positionid;
            $organisationid = $jobassignment->organisationid;
            $appraiserid = $jobassignment->appraiserid;
            $managerid = $jobassignment->managerid;
            $managerjaid = $jobassignment->managerjaid;
        } else {
            $positionid = null;
            $organisationid = null;
            $appraiserid = null;
            $managerid = null;
            $managerjaid = null;
        }

        if (empty($managerjaid)) {
            // Todo: put this in a static method to create an empty and not saved assignment.
            $managerja = new stdClass();
            // The id will exist but might just be null, let's add to the object anyway.
            $managerja->id =  $managerjaid;
            $managerja->fullname = '';
            // Might be null, but if it's not, we'll have a use for it.
            $managerja->userid = $managerid;
        } else {
            $managerja = \shezar_job\job_assignment::get_with_id($managerjaid);
        }

        // Get position title.
        $positiontitle = '';
        if ($positionid) {
            $positiontitle = $DB->get_field('pos', 'fullname', array('id' => $positionid));
        }

        // Get organisation title.
        $organisationtitle = '';
        if ($organisationid) {
            $organisationtitle = $DB->get_field('org', 'fullname', array('id' => $organisationid));
        }

        // The fields required to display the name of a user.
        $usernamefields = get_all_user_name_fields(true);

        // Get manager title.
        $managertitle = '';
        if ($managerid) {
            $manager = $DB->get_record('user', array('id' => $managerid), 'id, email,' . $usernamefields);
            if ($manager) {
                $managertitle = shezar_job_display_user_job($manager, $managerja, $canviewemail);
            } else {
                $managerid = 0;
            }
        }

        // Get appraiser title.
        $appraisertitle = '';
        if ($appraiserid) {
            $appraiser = $DB->get_record('user', array('id' => $appraiserid), 'id, ' . $usernamefields);
            if ($appraiser) {
                $appraisertitle = fullname($appraiser);
            } else {
                $appraiserid = 0;
            }
        }

        // Add some extra hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('header', 'general', get_string('jobassignment', 'shezar_job'));

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);
        $mform->setDefault('userid', $userid);

        $mform->addElement('text', 'fullname', get_string('jobassignmentfullname', 'shezar_job'));
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addHelpButton('fullname', 'jobassignmentfullname', 'shezar_job');

        $mform->addElement('text', 'shortname', get_string('jobassignmentshortname', 'shezar_job'));
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addHelpButton('shortname', 'jobassignmentshortname', 'shezar_job');

        $mform->addElement('text', 'idnumber', get_string('jobassignmentidnumber', 'shezar_job'));
        $mform->setType('idnumber', PARAM_TEXT);
        $mform->addHelpButton('idnumber', 'jobassignmentidnumber', 'shezar_job');
        $mform->addRule('idnumber', null, 'required');

        $mform->addElement('editor', 'description_editor', get_string('description'), null, $editoroptions);
        $mform->setType('description_editor', PARAM_CLEANHTML);

        $mform->addElement('date_selector', 'startdate', get_string('jobassignmentstartdate', 'shezar_job'),
            array('optional' => true));
        $mform->addHelpButton('startdate', 'jobassignmentstartdate', 'shezar_job');
        $mform->setDefault('startdate', 0);

        $mform->addElement('date_selector', 'enddate', get_string('jobassignmentenddate', 'shezar_job'),
            array('optional' => true));
        $mform->addHelpButton('enddate', 'jobassignmentenddate', 'shezar_job');
        $mform->setDefault('enddate', 0);

        if (!shezar_feature_disabled('positions')) {
            $pos_class = strlen($positiontitle) ? 'nonempty' : '';
            $mform->addElement('static', 'positionselector', get_string('position', 'shezar_job'),
                html_writer::tag('span', format_string($positiontitle), array('class' => $pos_class, 'id' => 'positiontitle')).
                ($canedit ? html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('chooseposition', 'shezar_job'), 'id' => 'show-position-dialog')) : '')
            );
            $mform->addElement('hidden', 'positionid');
            $mform->setType('positionid', PARAM_INT);
            $mform->setDefault('positionid', 0);
            $mform->addHelpButton('positionselector', 'chooseposition', 'shezar_job');
        }

        $org_class = strlen($organisationtitle) ? 'nonempty' : '';
        $mform->addElement('static', 'organisationselector', get_string('organisation', 'shezar_job'),
            html_writer::tag('span', format_string($organisationtitle), array('class' => $org_class, 'id' => 'organisationtitle')) .
            ($canedit ? html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('chooseorganisation', 'shezar_job'), 'id' => 'show-organisation-dialog')) : '')
        );

        $mform->addElement('hidden', 'organisationid');
        $mform->setType('organisationid', PARAM_INT);
        $mform->setDefault('organisationid', 0);
        $mform->addHelpButton('organisationselector', 'chooseorganisation', 'shezar_job');

        if (!shezar_feature_disabled('appraisals')) {
            // Show appraiser.
            // If we can edit, show button. Else show link to appraiser's profile.
            if ($canedit) {
                $appraiser_class = strlen($appraisertitle) ? 'nonempty' : '';
                $mform->addElement(
                    'static',
                    'appraiserselector',
                    get_string('appraiser', 'shezar_job'),
                    html_writer::tag('span', format_string($appraisertitle),
                        array('class' => $appraiser_class, 'id' => 'appraisertitle')) .
                    html_writer::empty_tag('input', array('type' => 'button',
                        'value' => get_string('chooseappraiser', 'shezar_job'), 'id' => 'show-appraiser-dialog'))
                );
            } else {
                if (!empty($appraiserid)) {
                    $usercontext = context_user::instance($appraiserid, MUST_EXIST);
                    $testuser = new stdClass();
                    $testuser->id = $appraiserid;
                    $testuser->deleted = false;
                    $showlink = user_can_view_profile($testuser, null, $usercontext);
                } else {
                    $showlink = false;
                }

                if ($showlink) {
                    $mform->addElement(
                        'static',
                        'appraiserselector',
                        get_string('appraiser', 'shezar_job'),
                        html_writer::tag('span', html_writer::link(new moodle_url('/user/view.php',
                            array('id' => $appraiserid)), format_string($appraisertitle)), array('id' => 'appraisertitle'))
                    );
                } else {
                    $mform->addElement(
                        'static',
                        'appraiserselector',
                        get_string('appraiser', 'shezar_job'),
                        html_writer::tag('span', format_string($appraisertitle), array('id' => 'appraisertitle'))
                    );
                }
            }

            $mform->addElement('hidden', 'appraiserid');
            $mform->setType('appraiserid', PARAM_INT);
            $mform->setDefault('appraiserid', $appraiserid);
            $mform->addHelpButton('appraiserselector', 'chooseappraiser', 'shezar_job');
        }

        // Show manager
        // If we can edit, show button. Else show link to manager's profile.
        if ($canedit) {
            $manager_class = strlen($managertitle) ? 'nonempty' : '';
            $mform->addElement(
                'static',
                'managerselector',
                get_string('manager', 'shezar_job'),
                html_writer::tag('span', format_string($managertitle), array('class' => $manager_class, 'id' => 'managertitle'))
                . html_writer::empty_tag('input',
                    array('type' => 'button', 'value' => get_string('choosemanager', 'shezar_job'), 'id' => 'show-manager-dialog'))
            );
            $mform->addElement('hidden', 'manageridjaid');
            $mform->setType('manageridjaid', PARAM_ALPHANUMEXT);
            $mform->setDefault('manageridjaid', $managerid . '-' .$managerjaid);
        } else {
            if (!empty($managerid)) {
                $usercontext = context_user::instance($managerid, MUST_EXIST);
                $testuser = new stdClass();
                $testuser->id = $managerid;
                $testuser->deleted = false;
                $showlink = user_can_view_profile($testuser, null, $usercontext);
            } else {
                $showlink = false;
            }

            if ($showlink) {
                $mform->addElement(
                    'static',
                    'managerselector',
                    get_string('manager', 'shezar_job'),
                    html_writer::tag('span', html_writer::link(new moodle_url('/user/view.php',
                        array('id' => $managerid)), format_string($managertitle)), array('id' => 'managertitle'))
                );
            } else {
                $mform->addElement(
                    'static',
                    'managerselector',
                    get_string('manager', 'shezar_job'),
                    html_writer::tag('span', format_string($managertitle), array('id' => 'managertitle'))
                );
            }
        }

        $mform->addElement('hidden', 'managerid');
        $mform->setType('managerid', PARAM_INT);
        $mform->setDefault('managerid', $managerid);
        $mform->addElement('hidden', 'managerjaid');
        $mform->setType('managerjaid', PARAM_INT);
        $mform->setDefault('managerjaid', $managerjaid);
        $mform->addHelpButton('managerselector', 'choosemanager', 'shezar_job');

        if (!empty($CFG->enabletempmanagers)) {
            // Temporary manager.
            if ($submitted) {
                $tempmanagerid = $submittedtempmanagerid;
                $tempmanagerjaid = $submittedtempmanagerjaid;
            } else if ($jobassignment) {
                $tempmanagerid = $jobassignment->tempmanagerid;
                $tempmanagerjaid = $jobassignment->tempmanagerjaid;
            } else {
                $tempmanagerid = null;
                $tempmanagerjaid = null;
            }

            if (empty($tempmanagerjaid)) {
                // Todo: put this in a static method to create an empty and not saved assignment.
                $tempmanagerja = new stdClass();
                // The id will exist but might just be null, let's add to the object anyway.
                $tempmanagerja->id =  $tempmanagerjaid;
                $tempmanagerja->fullname = '';
                // Might be null, but if it's not, we'll have a use for it.
                $tempmanagerja->userid = $tempmanagerid;
            } else {
                $tempmanagerja = \shezar_job\job_assignment::get_with_id($tempmanagerjaid);
            }

            $tempmanagertitle = '';
            if ($tempmanagerid) {
                $tempmanager = $DB->get_record('user', array('id' => $tempmanagerid), 'id, email,' . $usernamefields);
                if ($tempmanager) {
                    $tempmanagertitle = shezar_job_display_user_job($tempmanager, $tempmanagerja, $canviewemail);
                } else {
                    $tempmanagerid = 0;
                }
            }

            // If we can edit, show button, else show link to manager's profile.
            if ($canedittempmanager) {
                $tempmanagerclass = strlen($tempmanagertitle) ? 'nonempty' : '';
                $mform->addElement(
                    'static',
                    'tempmanagerselector',
                    get_string('tempmanager', 'shezar_job'),
                    html_writer::tag('span', format_string($tempmanagertitle),
                            array('class' => $tempmanagerclass, 'id' => 'tempmanagertitle')) .
                    html_writer::empty_tag('input', array('type' => 'button',
                            'value' => get_string('choosetempmanager', 'shezar_job'), 'id' => 'show-tempmanager-dialog'))
                );
                $mform->addElement('hidden', 'tempmanageridjaid');
                $mform->setType('tempmanageridjaid', PARAM_ALPHANUMEXT);
                $mform->setDefault('tempmanageridjaid', $tempmanagerid . '-' .$tempmanagerjaid);
            } else {
                if (!empty($tempmanagerid)) {
                    $usercontext = context_user::instance($tempmanagerid, MUST_EXIST);
                    $testuser = new stdClass();
                    $testuser->id = $tempmanagerid;
                    $testuser->deleted = false;
                    $showlink = user_can_view_profile($testuser, null, $usercontext);
                } else {
                    $showlink = false;
                }

                if ($showlink) {
                    $mform->addElement(
                        'static',
                        'tempmanagerselector',
                        get_string('tempmanager', 'shezar_job'),
                        html_writer::tag('span', html_writer::link(new moodle_url('/user/view.php',
                                array('id' => $tempmanagerid)), format_string($tempmanagertitle)),
                                array('id' => 'tempmanagertitle'))
                    );
                } else {
                    $mform->addElement(
                            'static',
                            'tempmanagerselector',
                            get_string('tempmanager', 'shezar_job'),
                            html_writer::tag('span', format_string($tempmanagertitle),
                                    array('id' => 'tempmanagertitle'))
                        );
                }
            }

            $mform->addElement('hidden', 'tempmanagerid');
            $mform->setType('tempmanagerid', PARAM_INT);
            $mform->setDefault('tempmanagerid', $tempmanagerid);
            $mform->addElement('hidden', 'tempmanagerjaid');
            $mform->setType('tempmanagerjaid', PARAM_INT);
            $mform->setDefault('tempmanagerjaid', $tempmanagerjaid);
            $mform->addHelpButton('tempmanagerselector', 'choosetempmanager', 'shezar_job');

            $mform->addElement('date_selector', 'tempmanagerexpirydate', get_string('tempmanagerexpirydate', 'shezar_job'),
                array('optional' => true));
            $mform->setDefault('tempmanagerexpirydate', 0);
            $mform->addHelpButton('tempmanagerexpirydate', 'tempmanagerexpirydate', 'shezar_job');
        }

        if ($jobassignment) {
            $this->add_action_buttons(true, get_string('updatejobassignment', 'shezar_job'));
        } else {
            $this->add_action_buttons(true, get_string('addjobassignment', 'shezar_job'));
        }

    }

    function definition_after_data() {
        $canedit = $this->_customdata['canedit'];
        // Freeze the form if appropriate.
        if (!$canedit) {
            $this->freezeForm();
        }
    }

    function freezeForm() {
        $mform = $this->_form;

        // Tempmanager - skip some elements.
        $skipelements = array();
        $canedittempmanager = $this->_customdata['canedittempmanager'];
        if ($canedittempmanager) {
            // Freeze the form except for temp manager functionality.
            $skipelements = array('tempmanagerselector', 'tempmanagerid', 'tempmanagerjaid', 'tempmanagerexpirydate', 'buttonar');
        }
        $mform->hardFreezeAllVisibleExcept($skipelements);

        // Get date format with abstract values to match to date_selector value format.
        $dateformat = array('day' => date('d'), 'month' => date('n'), 'year' => date('Y'), 'enabled' => true);
        // Hide elements with no values
        foreach (array_keys($mform->_elements) as $key) {
            $element =& $mform->_elements[$key];
            if (in_array($element->getName(), $skipelements)) {
                continue;
            }
            // Check static elements differently
            if ($element->getType() == 'static') {
                // Check if it is a js selector
                if (substr($element->getName(), -8) == 'selector') {
                    // Get id element
                    $elementid = $mform->getElement(substr($element->getName(), 0, -8).'id');
                    if (!$elementid || !$elementid->getValue()) {
                        $mform->removeElement($element->getName());
                    }
                    continue;
                }
            }
            // Get element value
            $value = $element->getValue();
            // Check groups
            // (matches date groups and action buttons)
            if (is_array($value)) {
                $diff = array_diff_key($dateformat, $value);
                if (empty($diff)) {
                    // This is a date_selector value which we do not want to remove.
                    continue;
                }
                // If values are strings (e.g. buttons, or date format string), remove
                foreach ($value as $k => $v) {
                    if (!is_numeric($v)) {
                        $mform->removeElement($element->getName());
                        break;
                    }
                }
            }
            // Otherwise check if empty
            elseif (!$value) {
                $mform->removeElement($element->getName());
            }
        }
    }

    function validation($data, $files) {
        $mform = $this->_form;

        $result = array();

        if (isset($data['startdate']) && isset($data['enddate'])) {
            // Enforce start date before finish date.
            if ($data['startdate'] > $data['enddate'] && $data['startdate'] !== 0 && $data['enddate'] !== 0) {
                $errstr = get_string('error:startafterfinish', 'shezar_job');
                $result['startdate'] = $errstr;
                $result['enddate'] = $errstr;
                unset($errstr);
            }
        }

        // If setting a temporary manager, check that an expiry date is set.
        $canedittempmanager = $this->_customdata['canedittempmanager'];
        if ($canedittempmanager && $mform->getElement('tempmanagerid')->getValue()) {
            if (empty($data['tempmanagerexpirydate'])) {
                $result['tempmanagerexpirydate'] = get_string('error:tempmanagerexpirynotset', 'shezar_job');
            } else {
                if (time() >  $data['tempmanagerexpirydate'] && $data['tempmanagerexpirydate'] !== 0) {
                    $result['tempmanagerexpirydate'] = get_string('error:datenotinfuture', 'shezar_job');
                }
            }
        }

        if ($data['id']) {
            $jobassignment = \shezar_job\job_assignment::get_with_id($data['id']);
            $matchingidnumber = \shezar_job\job_assignment::get_with_idnumber($jobassignment->userid, $data['idnumber'], false);
            if ($matchingidnumber && $matchingidnumber->id != $jobassignment->id) {
                $result['idnumber'] = get_string('error:jobassignmentidnumberunique', 'shezar_job');
            }
        } else {
            $matchingidnumber = \shezar_job\job_assignment::get_with_idnumber($data['userid'], $data['idnumber'], false);
            if (!empty($matchingidnumber)) {
                $result['idnumber'] = get_string('error:jobassignmentidnumberunique', 'shezar_job');
            }
        }

        if (!empty($result)) {
            shezar_set_notification(get_string('error:positionvalidationfailed', 'shezar_job'));
        }

        return $result;
    }
}
