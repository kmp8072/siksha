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
 * @author Aaron Barnes <aaron.barnes@shezarlms.com>
 * @author Francois Marier <francois@catalyst.net.nz>
 * @package modules
 * @subpackage facetoface
 */
defined('MOODLE_INTERNAL') || die();


require_once("{$CFG->libdir}/formslib.php");
require_once("{$CFG->dirroot}/mod/facetoface/lib.php");


class mod_facetoface_session_form extends moodleform {
    /** @var context_module */
    protected $context;

    function definition() {
        global $CFG;

        $mform =& $this->_form;
        $session = (isset($this->_customdata['session'])) ? $this->_customdata['session'] : false;
        $sessiondata = $this->_customdata['sessiondata'];

        $this->context = context_module::instance($this->_customdata['cm']->id);

        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->addElement('hidden', 'f', $this->_customdata['f']);
        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->addElement('hidden', 'c', $this->_customdata['c']);
        $mform->setType('id', PARAM_INT);
        $mform->setType('f', PARAM_INT);
        $mform->setType('s', PARAM_INT);
        $mform->setType('c', PARAM_INT);
        $mform->addElement('hidden', 'backtoallsessions', $this->_customdata['backtoallsessions']);
        $mform->setType('backtoallsessions', PARAM_BOOL);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $editoroptions = $this->_customdata['editoroptions'];

        self::add_date_render_fields($this, $this->_customdata['defaulttimezone'], $this->_customdata['s'], $sessiondata);

        $mform->addElement('date_time_selector', 'registrationtimestart', get_string('registrationtimestart', 'facetoface'), array('optional' => true, 'showtimezone' => true));
        $mform->addHelpButton('registrationtimestart', 'registrationtimestart', 'facetoface');
        $mform->addElement('date_time_selector', 'registrationtimefinish', get_string('registrationtimefinish', 'facetoface'), array('optional' => true, 'showtimezone' => true));
        $mform->addHelpButton('registrationtimefinish', 'registrationtimefinish', 'facetoface');

        $mform->addElement('text', 'capacity', get_string('maxbookings', 'facetoface'), array('size' => 5));
        $mform->addRule('capacity', null, 'required', null, 'client');
        $mform->setType('capacity', PARAM_INT);
        $mform->setDefault('capacity', 10);
        $mform->addRule('capacity', null, 'numeric', null, 'client');
        $mform->addHelpButton('capacity', 'maxbookings', 'facetoface');

        $mform->addElement('checkbox', 'allowoverbook', get_string('allowoverbook', 'facetoface'));
        $mform->addHelpButton('allowoverbook', 'allowoverbook', 'facetoface');

        if (has_capability('mod/facetoface:configurecancellation', $this->context)) {
            // User cancellation settings.
            $radioarray = array();
            $radioarray[] = $mform->createElement('radio', 'allowcancellations', '', get_string('allowcancellationanytime', 'facetoface'), 1);
            $radioarray[] = $mform->createElement('radio', 'allowcancellations', '', get_string('allowcancellationnever', 'facetoface'), 0);
            $radioarray[] = $mform->createElement('radio', 'allowcancellations', '', get_string('allowcancellationcutoff', 'facetoface'), 2);
            $mform->addGroup($radioarray, 'allowcancellations', get_string('allowbookingscancellations', 'facetoface'), array('<br/>'), false);
            $mform->setType('allowcancellations', PARAM_INT);
            $mform->addHelpButton('allowcancellations', 'allowbookingscancellations', 'facetoface');

            // Cancellation cutoff.
            $cutoffnotegroup = array();
            $cutoffnotegroup[] =& $mform->createElement('duration', 'cancellationcutoff', '', array('defaultunit' => HOURSECS, 'optional' => false));
            $cutoffnotegroup[] =& $mform->createElement('static', 'cutoffnote', null, get_string('cutoffnote', 'facetoface'));
            $mform->addGroup($cutoffnotegroup, 'cutoffgroup', '', '&nbsp;', false);
            $mform->disabledIf('cancellationcutoff[number]', 'allowcancellations', 'notchecked', 2);
            $mform->disabledIf('cancellationcutoff[timeunit]', 'allowcancellations', 'notchecked', 2);
        }

        $facetoface_allowwaitlisteveryone = get_config(null, 'facetoface_allowwaitlisteveryone');
        if ($facetoface_allowwaitlisteveryone) {
            $mform->addElement('checkbox', 'waitlisteveryone', get_string('waitlisteveryone', 'facetoface'));
            $mform->addHelpButton('waitlisteveryone', 'waitlisteveryone', 'facetoface');
        }

        // Show minimum capacity and cut-off (for when this should be reached).
        $mform->addElement('text', 'mincapacity', get_string('minbookings', 'facetoface'), array('size' => 5));
        $mform->setType('mincapacity', PARAM_INT);
        $mform->setDefault('mincapacity', get_config('facetoface', 'defaultminbookings'));
        $mform->addRule('mincapacity', null, 'numeric', null, 'client');
        $mform->addHelpButton('mincapacity', 'mincapacity', 'facetoface');

        $cutoffdurationgroup = array();
        $cutoffdurationgroup[] =& $mform->createElement('checkbox', 'sendcapacityemail', '');
        $cutoffdurationgroup[] =& $mform->createElement('duration', 'cutoff', '', array('defaultunit' => HOURSECS, 'optional' => false));
        $cutoffdurationgroup[] =& $mform->createElement('static', 'cutoffnote', null, get_string('cutoffnote', 'facetoface'));
        $mform->addGroup($cutoffdurationgroup, 'cutoffdurationgroup', get_string('enablemincapacitynotification', 'facetoface'), '&nbsp;', false);

        $mform->setDefault('sendcapacityemail', 0);
        $mform->addHelpButton('cutoffdurationgroup', 'enablemincapacitynotification', 'facetoface');

        $mform->setType('cutoff', PARAM_INT);
        $mform->disabledIf('cutoff[number]', 'sendcapacityemail');
        $mform->disabledIf('cutoff[timeunit]', 'sendcapacityemail');


        if (!get_config(NULL, 'facetoface_hidecost')) {
            $formarray  = array();
            $formarray[] = $mform->createElement('text', 'normalcost', get_string('normalcost', 'facetoface'), 'size="5"');
            $formarray[] = $mform->createElement('static', 'normalcosthint', '', html_writer::tag('span', get_string('normalcosthinttext','facetoface'), array('class' => 'hint-text')));
            $mform->addGroup($formarray,'normalcost_group', get_string('normalcost','facetoface'), array(' '),false);
            $mform->setType('normalcost', PARAM_TEXT);
            $mform->addHelpButton('normalcost_group', 'normalcost', 'facetoface');

            if (!get_config(NULL, 'facetoface_hidediscount')) {
                $formarray  = array();
                $formarray[] = $mform->createElement('text', 'discountcost', get_string('discountcost', 'facetoface'), 'size="5"');
                $formarray[] = $mform->createElement('static', 'discountcosthint', '', html_writer::tag('span', get_string('discountcosthinttext','facetoface'), array('class' => 'hint-text')));
                $mform->addGroup($formarray,'discountcost_group', get_string('discountcost','facetoface'), array(' '),false);
                $mform->setType('discountcost', PARAM_TEXT);
                $mform->addHelpButton('discountcost_group', 'discountcost', 'facetoface');
            }
        }

        $mform->addElement('editor', 'details_editor', get_string('details', 'facetoface'), null, $editoroptions);
        $mform->setType('details_editor', PARAM_RAW);
        $mform->addHelpButton('details_editor', 'details', 'facetoface');

        // Choose users for trainer roles
        $roles = facetoface_get_trainer_roles($this->context);

        if ($roles) {
            // Get current trainers
            $current_trainers = facetoface_get_trainers($this->_customdata['s']);
            // Get course context and roles
            $rolenames = role_get_names($this->context);
            // Loop through all selected roles
            $header_shown = false;
            foreach ($roles as $role) {
                $rolename = $rolenames[$role->id]->localname;

                // Attempt to load users with this role in this context.
                $usernamefields = get_all_user_name_fields(true, 'u');
                $rs = get_role_users($role->id, $this->context, true, "u.id, {$usernamefields}", 'u.id ASC');

                if (!$rs) {
                    continue;
                }

                $choices = array();
                foreach ($rs as $roleuser) {
                    $choices[$roleuser->id] = fullname($roleuser);
                }

                // Show header (if haven't already)
                if ($choices && !$header_shown) {
                    $mform->addElement('header', 'trainerroles', get_string('sessionroles', 'facetoface'));
                    $mform->addElement('static', 'roleapprovalerror');
                    $header_shown = true;
                }

                // If only a few, use checkboxes
                if (count($choices) < 4) {
                    $role_shown = false;
                    foreach ($choices as $cid => $choice) {
                        // Only display the role title for the first checkbox for each role
                        if (!$role_shown) {
                            $roledisplay = $rolename;
                            $role_shown = true;
                        } else {
                            $roledisplay = '';
                        }

                        $mform->addElement('advcheckbox', 'trainerrole['.$role->id.']['.$cid.']', $roledisplay, $choice, null, array('', $cid));
                        $mform->setType('trainerrole['.$role->id.']['.$cid.']', PARAM_INT);
                    }
                } else {
                    $mform->addElement('select', 'trainerrole['.$role->id.']', $rolename, $choices, array('multiple' => 'multiple'));
                    $mform->setType('trainerrole['.$role->id.']', PARAM_SEQUENCE);
                }

                // Select current trainers
                if ($current_trainers) {
                    foreach ($current_trainers as $roleid => $trainers) {
                        $t = array();
                        foreach ($trainers as $trainer) {
                            $t[] = $trainer->id;
                            $mform->setDefault('trainerrole['.$roleid.']['.$trainer->id.']', $trainer->id);
                        }

                        $mform->setDefault('trainerrole['.$roleid.']', implode(',', $t));
                    }
                }
            }
        }

        // If conflicts are disabled
        if (!empty($CFG->facetoface_allowschedulingconflicts)) {
            $mform->addElement('selectyesno', 'allowconflicts', get_string('allowschedulingconflicts', 'facetoface'));
            $mform->setDefault('allowconflicts', 0); // defaults to 'no'
            $mform->addHelpButton('allowconflicts', 'allowschedulingconflicts', 'facetoface');
            $mform->setType('allowconflicts', PARAM_BOOL);
        }

        // Show all custom fields. Customfield support.
        if (!$session) {
            $session = new stdClass();
        }
        if (empty($session->id)) {
            $session->id = 0;
        }
        customfield_definition($mform, $session, 'facetofacesession', 0, 'facetoface_session');

        $this->add_action_buttons();

        $this->set_data($sessiondata);
    }

    /**
     * Adds html hidden fields and html rendered table to display in session date form
     * @param moodleform $form Form where to add fields and set values
     * @param string $defaulttimezone
     * @param int $sessionid
     * @param stdClass $sessiondata
     */
    public static function add_date_render_fields(moodleform $form, $defaulttimezone, $sessionid, $sessiondata) {
        $mform = $form->_form;

        $mform->addElement('hidden', "cntdates");
        $mform->setType("cntdates", PARAM_INT);

        $table = new html_table();
        $table->attributes['class'] = 'generaltable fullwidth f2fmanagedates';
        $table->head = array(
            get_string('dateandtime', 'facetoface'),
            get_string('room', 'facetoface'),
            get_string('assets', 'facetoface'),
            ''
        );
        $table->data = array();

        $mform->addElement('static', 'errors');

        for ($i = 0; $i < $sessiondata->cntdates; $i++) {
            $row = self::date_render_mixin($mform, $i, $sessiondata, $defaulttimezone);
            $table->data[] = $row;
        }
        $dateshtmlcontent = html_writer::table($table);

        // Render this content hidden. Then it will be displayed by js during init.
        $html = html_writer::div($dateshtmlcontent, 'sessiondates hidden', array('id'=>'sessiondates_' . $sessionid));
        $mform->addElement('static', 'sessiondates', get_string('sessiondates', 'facetoface'), $html);
        $mform->addElement('submit','date_add_fields', get_string('dateadd', 'facetoface'));
        $mform->registerNoSubmitButton('date_add_fields');
    }

    /**
     * Returns fields and html code required for one date (or new date if no session data provided)
     * Used also to dynamically inject new or cloned session date (event)
     * @param $mform
     * @param int $offset
     * @param stdClass $sessiondata
     * @param string $defaulttimezone Default timezone if date not set
     * @return
     */
    public static function date_render_mixin(MoodleQuickForm $mform, $offset, $sessiondata, $defaulttimezone) {
        global $OUTPUT;

        $dateid = !empty($sessiondata->{"sessiondateid[$offset]"}) ? $sessiondata->{"sessiondateid[$offset]"} : 0;
        $roomid = !empty($sessiondata->{"roomid[$offset]"}) ? $sessiondata->{"roomid[$offset]"} : '';
        $assetids = !empty($sessiondata->{"assetids[$offset]"}) ? $sessiondata->{"assetids[$offset]"} : '';

        // Add per-date form elements.
        // Clonable fields also must be listed in session.js.
        $mform->addElement('hidden', "sessiondateid[$offset]", $dateid);
        $mform->setType("sessiondateid[$offset]", PARAM_INT);
        $mform->addElement('hidden', "roomcapacity[$offset]");
        $mform->setType("roomcapacity[$offset]", PARAM_INT);
        $mform->addElement('hidden', "roomid[$offset]", $roomid);
        $mform->setType("roomid[$offset]", PARAM_INT);
        $mform->addElement('hidden', "assetids[$offset]", $assetids);
        $mform->setType("assetids[$offset]", PARAM_SEQUENCE);
        $mform->addElement('hidden', "timestart[$offset]");
        $mform->setType("timestart[$offset]", PARAM_INT);
        $mform->addElement('hidden', "timefinish[$offset]");
        $mform->setType("timefinish[$offset]", PARAM_INT);
        $mform->addElement('hidden', "sessiontimezone[$offset]");
        $mform->setType("sessiontimezone[$offset]", PARAM_TIMEZONE);
        $mform->addElement('hidden', "datedelete[$offset]");
        $mform->setType("datedelete[$offset]", PARAM_INT);

        $row = array();
        $displaytimezones = get_config(null, 'facetoface_displaysessiontimezones');

        // Dates.
        if (empty($sessiondata->{"timestart[$offset]"})
                || empty($sessiondata->{"timefinish[$offset]"})
                || empty($sessiondata->{"sessiontimezone[$offset]"})) {
            list($timestart, $timefinish) = session_date_form::get_default_dates();
            $sessiontimezone = $defaulttimezone;
        } else {
            $timestart = $sessiondata->{"timestart[$offset]"};
            $timefinish = $sessiondata->{"timefinish[$offset]"};
            $sessiontimezone = $sessiondata->{"sessiontimezone[$offset]"};
        }

        $mform->setDefault("timestart[$offset]", $timestart);
        $mform->setDefault("timefinish[$offset]", $timefinish);
        $mform->setDefault("sessiontimezone[$offset]", $sessiontimezone);

        $dateshtml = session_date_form::render_dates(
            $timestart,
            $timefinish,
            $sessiontimezone,
            $displaytimezones
        );

        $strcopy = get_string('copy');
        $strdelete = get_string('delete');
        $streditdate = get_string('editdate', 'facetoface');

        $editicon = $OUTPUT->action_icon('#', new pix_icon('t/edit', $streditdate), null,
                array('id' => "show-selectdate{$offset}-dialog", 'class' => 'action-icon show-selectdate-dialog', 'data-offset' => $offset));
        $row[] = $editicon . html_writer::span($dateshtml, 'timeframe-text', array('id' => 'timeframe-text' . $offset));

        // Room.
        $selectroom = html_writer::link("#", get_string('selectroom', 'facetoface'),
                array('id' => "show-selectroom{$offset}-dialog", 'class' => 'show-selectroom-dialog', 'data-offset' => $offset));

        // Room name and capacity will be loaded by js.
        $row[] = html_writer::div('', 'roomname', array('id' => 'roomname' . $offset))
                . $selectroom;

        // Assets.
        $selectassets = html_writer::link("#", get_string('selectassets', 'facetoface'), array(
            'id' => "show-selectassets{$offset}-dialog",
            'class' => 'show-selectassets-dialog',
            'data-offset' => $offset
        ));

        // Assets items will be loaded by js.
        $row[] =  html_writer::tag('ul', '', array(
            'id' => 'assetlist' . $offset,
            'class' => 'assetlist nonempty',
            'data-offset' => $offset
        )) . $selectassets;

        // Options.
        $cloneicon = $OUTPUT->action_icon('#', new pix_icon('t/copy', $strcopy), null,
            array('class' => 'action-icon dateclone', 'data-offset' => $offset));
        $deleteicon = $OUTPUT->action_icon('#', new pix_icon('t/delete', $strdelete), null,
            array('class' => 'action-icon dateremove', 'data-offset' => $offset));
        $row[] = $cloneicon . $deleteicon;

        return $row;
    }

    function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);
        $facetofaceid = $this->_customdata['f'];
        $dates = array();
        $dateids = $data['sessiondateid'];
        $datecount = count($dateids);
        $deletecount = 0;
        $errdates = array();
        for ($i=0; $i < $datecount; $i++) {
            if (!empty($data['datedelete'][$i])) {
                // Ignore dates marked for deletion.
                $deletecount++;
                continue;
            }

            $starttime = $data["timestart"][$i];
            $endtime = $data["timefinish"][$i];
            $roomid = $data["roomid"][$i];
            $assetids = $data["assetids"][$i];
            $assetlist = array();
            if (!empty($assetids)) {
                $assetlist = explode(',', $assetids);
            }
            // If event is a cloning then remove session id and behave as a new event to get rooms availability.
            $sessid = ($data['c'] ? 0 : $data['s']);
            $errdate = session_date_form::dates_validate($starttime, $endtime, $roomid, $assetlist, $sessid, $facetofaceid);

            if (!empty($errdate['timestart'])) {
                $errdates[] = $errdate['timestart'];
            }
            if (!empty($errdate['timefinish'])) {
                $errdates[] = $errdate['timefinish'];
            }
            if (!empty($errdate['roomid'])) {
                $errdates[] = $errdate['roomid'];
            }

            if (!empty($errdate['assetids'])) {
                $errdates[] = $errdate['assetids'];
            }

            //Check this date does not overlap with any previous dates - time overlap logic from a Stack Overflow post
            if (!empty($dates)) {
                foreach ($dates as $existing) {
                    if (($endtime > $existing->timestart) && ($existing->timefinish > $starttime) ||
                        ($endtime == $existing->timefinish) || ($starttime == $existing->timestart)) {
                        // This date clashes with an existing date - either they overlap or
                        // one of them is zero minutes and they start at the same time or end at the same time.
                        $errdates[] = get_string('error:sessiondatesconflict', 'facetoface');
                    }
                }
            }

            // Registration cannot open once session has started.
            if (!empty($data['registrationtimestart'])) {
                if ($data['registrationtimestart'] >= $starttime) {
                    $errors['registrationtimestart'] = get_string('registrationstartsession', 'facetoface');
                }
            }

            // Registration close date must be on or before session has started.
            if (!empty($data['registrationtimefinish'])) {
                if ($data['registrationtimefinish'] > $starttime) {
                    $errors['registrationtimefinish'] = get_string('registrationfinishsession', 'facetoface');
                }
            }

            // If valid date, add to array.
            $date = new stdClass();
            $date->timestart = $starttime;
            $date->timefinish = $endtime;
            $date->roomid = $roomid;
            $dates[] = $date;
        }

        if(!empty($data['registrationtimestart']) && !empty($data['registrationtimefinish'])) {
            $start = $data['registrationtimestart'];
            $finish = $data['registrationtimefinish'];
            if ($start >= $finish) {
                // Registration opening time cannot be after registration close time.
                $errors['registrationtimestart'] = get_string('registrationerrorstartfinish', 'facetoface');
                $errors['registrationtimefinish'] = get_string('registrationerrorstartfinish', 'facetoface');
            }
        }

        // Check the availabilty of trainers if scheduling not allowed
        $trainerdata = !empty($data['trainerrole']) ? $data['trainerrole'] : array();
        $allowconflicts = !empty($data['allowconflicts']);

        if ($dates && !$allowconflicts && is_array($trainerdata)) {
            $wheresql = '';
            $whereparams = array();
            if (!empty($this->_customdata['s'])) {
                $wheresql = ' AND s.id != ?';
                $whereparams[] = $this->_customdata['s'];
            }

            // Seminar approval by role is set, required at least one role selected.
            $hasconflicts = 0;
            $selectedroleids = array();
            $usernamefields = get_all_user_name_fields(true, 'u');
            // Loop through roles.
            foreach ($trainerdata as $roleid => $trainers) {
                // Attempt to load users with this role in this context.
                $trainerlist = get_role_users($roleid, $this->context, true, "u.id, {$usernamefields}", 'u.id ASC');

                // Initialize error variable.
                $trainererrors = '';
                // Loop through trainers in this role.
                foreach ($trainers as $trainer) {

                    if (!$trainer) {
                        continue;
                    } else {
                        $selectedroleids[] = $roleid;
                    }

                    // Check their availability.
                    $availability = facetoface_get_sessions_within($dates, $trainer, $wheresql, $whereparams);
                    if (!empty($availability)) {
                        // Verify if trainers come in form of checkboxes or dropdown list to properly place the errors.
                        if (isset($this->_form->_types["trainerrole[{$roleid}][{$trainer}]"])) {
                            $errors["trainerrole[{$roleid}][{$trainer}]"] = facetoface_get_session_involvement($trainerlist[$trainer], $availability);
                        } else if (isset($this->_form->_types["trainerrole[{$roleid}]"])) {
                            $trainererrors .= html_writer::tag('div', facetoface_get_session_involvement($trainerlist[$trainer], $availability));
                        }
                        ++$hasconflicts;
                    }
                }

                if (isset($this->_form->_types["trainerrole[{$roleid}]"]) && $trainererrors != '') {
                    $errors["trainerrole[{$roleid}]"] = $trainererrors;
                }
            }
            $facetoface = $DB->get_record('facetoface', array('id' => $facetofaceid));
            // Check if default role approval is selected.
            if ($facetoface->approvaltype == APPROVAL_ROLE && !in_array($facetoface->approvalrole, $selectedroleids)) {
                $rolenames = role_get_names($this->context);
                $errors['roleapprovalerror'] = get_string('error:rolerequired', 'facetoface', $rolenames[$facetoface->approvalrole]->localname);
            }
            // If there are conflicts, add a help message to checkbox
            if ($hasconflicts) {
                if ($hasconflicts > 1) {
                    $errors['allowconflicts'] = get_string('error:therearexconflicts', 'facetoface', $hasconflicts);
                } else {
                    $errors['allowconflicts'] = get_string('error:thereisaconflict', 'facetoface');
                }
            }
        }

        //check capcity is a number
        if (empty($data['capacity'])) {
            $errors['capacity'] = get_string('error:capacityzero', 'facetoface');
        } else {
            $capacity = $data['capacity'];
            if (!(is_numeric($capacity) && (intval($capacity) == $capacity) && $capacity > 0)) {
                $errors['capacity'] = get_string('error:capacitynotnumeric', 'facetoface');
            }
        }

        // Check the minimum capacity and cut-off.
        $mincapacity = $data['mincapacity'];
        if (!is_numeric($mincapacity) || (intval($mincapacity) != $mincapacity)) {
            $errors['mincapacity'] = get_string('error:mincapacitynotnumeric', 'facetoface');
        } else if ($mincapacity > $data['capacity']) {
            $errors['mincapacity'] = get_string('error:mincapacitytoolarge', 'facetoface');
        }

        // Check the cut-off is at least the day before the earliest start time.
        if (!empty($data['sendcapacityemail'])) {
            $cutoff = $data['cutoff'];
            if ($cutoff < DAYSECS) {
                $errors['cutoffdurationgroup'] = get_string('error:cutofftooclose', 'facetoface');
            } else {
                $now = time();
                foreach ($dates as $dateid => $date) {
                    // If the date is being deleted then we don't need to test against it.
                    if (!empty($data['datedelete'][$dateid])) {
                        continue;
                    }

                    $cutofftimestamp = $date->timestart - $cutoff;
                    if ($cutofftimestamp < $now) {
                        $errors['cutoffdurationgroup'] = get_string('error:cutofftoolate', 'facetoface');
                        break;
                    }
                }
            }
        }
        // Consolidate date errors.
        if (!empty($errdates)) {
            $errors['errors'] = implode(html_writer::empty_tag('br'), $errdates);
        }
        return $errors;
    }
}

/**
 * Form for choosing dates and associated information: room, and assets
 */
class session_date_form extends moodleform {
    /**
     * Get rendered start date, finish date and timestamp.
     * @param int $timestart start timestamp
     * @param int $timefinish finish timestamp
     * @param string $sesiontimezone
     * @param bool $displaytimezone should timezone be displayed
     * @return string
     */
    public static function render_dates($timestart, $timefinish, $sesiontimezone, $displaytimezone = true) {
        $sessionobj = facetoface_format_session_times(
            $timestart,
            $timefinish,
            $sesiontimezone
        );

        if (empty($displaytimezone)) {
            $sessionobj->timezone = '';
        }

        return get_string('sessiondatecolumn_html', 'facetoface', $sessionobj);
    }

    /**
     * Return default start and end date/time of session
     * @return array($defaultstart, $defaultfinish)
     */
    public static function get_default_dates() {
        $config = get_config('facetoface');
        $now = time();
        $defaultstart = $now;

        if (!empty($config->defaultdaystosession)) {
            if (!empty($config->defaultdaysskipweekends)) {
                $defaultstart = strtotime("+{$config->defaultdaystosession} weekdays", $defaultstart);
            } else {
                $defaultstart = strtotime("+{$config->defaultdaystosession} days", $defaultstart);
            }
        }

        $defaultfinish = $defaultstart;

        if (!empty($config->defaultdaysbetweenstartfinish)) {
            $days = (int)$config->defaultdaysbetweenstartfinish;
            if (!empty($config->defaultdaysskipweekends)) {
                $defaultfinish = strtotime("+{$days} weekdays", $defaultfinish);
            } else {
                $defaultfinish = strtotime("+{$days} days", $defaultfinish);
            }
        }

        // Adjust for start time hours.
        if (!empty($config->defaultstarttime_hours)) {
            $defaultstart = strtotime(date('Y-m-d', $defaultstart).' 00:00:00');
            $defaultstart += HOURSECS * (int)$config->defaultstarttime_hours;
        }

        // Adjust for finish time hours.
        if (!empty($config->defaultfinishtime_hours)) {
            $defaultfinish = strtotime(date('Y-m-d', $defaultfinish).' 00:00:00');
            $defaultfinish += HOURSECS * (int)$config->defaultfinishtime_hours;
        }

        // Adjust for start time minutes.
        if (!empty($config->defaultstarttime_minutes)) {
            $defaultstart += MINSECS * (int)$config->defaultstarttime_minutes;
        }

        // Adjust for finish time minutes.
        if (!empty($config->defaultfinishtime_minutes)) {
            $defaultfinish += MINSECS * (int)$config->defaultfinishtime_minutes;
        }
        return array($defaultstart, $defaultfinish);
    }

    public function definition() {
        global $PAGE;
        $mform = $this->_form;

        $displaytimezones = get_config(null, 'facetoface_displaysessiontimezones');

        $defaulttimezone = $this->_customdata['timezone'];
        $defaultstart = $this->_customdata['start'];
        $defaultfinish = $this->_customdata['finish'];

        $mform->addElement('hidden', 'sessiondateid', $this->_customdata['sessiondateid']);
        $mform->setType('sessiondateid', PARAM_INT);
        $mform->addElement('hidden', 'sessionid', $this->_customdata['sessionid']);
        $mform->setType('sessionid', PARAM_INT);
        $mform->addElement('hidden', 'roomid', $this->_customdata['roomid']);
        $mform->setType('roomid', PARAM_INT);
        $mform->addElement('hidden', 'assetids', $this->_customdata['assetids']);
        $mform->setType('assetids', PARAM_SEQUENCE);
        $mform->addElement('static', 'dateunavailable', "");

        if ($displaytimezones) {
            $timezones = array('99' => get_string('timezoneuser', 'shezar_core')) + core_date::get_list_of_timezones();
            $mform->addElement('select', 'sessiontimezone', get_string('sessiontimezone', 'facetoface'), $timezones);
        } else {
            $mform->addElement('hidden', 'sessiontimezone', '99');
        }
        $mform->addHelpButton('sessiontimezone', 'sessiontimezone','facetoface');
        $mform->setDefault('sessiontimezone', $defaulttimezone);
        $mform->setType('sessiontimezone', PARAM_TIMEZONE);

        if (empty($defaultstart)) {
            list($defaultstart, $defaultfinish) = self::get_default_dates();
        }
        // NOTE: Do not set type for date elements because it borks timezones!
        $mform->addElement('date_time_selector', 'timestart', get_string('timestart', 'facetoface'), array('defaulttime' => $defaultstart, 'showtimezone' => true));
        $mform->addHelpButton('timestart', 'sessionstarttime', 'facetoface');
        $mform->setDefault('timestart', $defaultstart);
        $mform->addElement('date_time_selector', 'timefinish', get_string('timefinish', 'facetoface'), array('defaulttime' => $defaultfinish, 'showtimezone' => true));
        $mform->addHelpButton('timefinish', 'sessionfinishtime', 'facetoface');
        $mform->setDefault('timefinish', $defaultfinish);

        if ($displaytimezones) {
            $tz = $defaulttimezone;
            // Really nasty default timezone hackery.
            $el = $mform->getElement("timestart");
            $el->set_option('timezone', $tz);
            $el = $mform->getElement("timefinish");
            $el->set_option('timezone', $tz);
        }
        // Date selector put calendar above fields. And in dialog box it effectively pushes it over top of edge of screen.
        // It doesn't support position settings, so hack it's instance to put it in position below.
        // Better way is to fix dateselector form element allowing to choose position, but it will require changes in upstream code.
        $PAGE->requires->yui_module('moodle-form-dateselector', '
            (function() {
                M.form.dateselector.fix_position = function() {
                    if (this.currentowner) {
                        var alignpoints = [
                            Y.WidgetPositionAlign.TL,
                            Y.WidgetPositionAlign.BL
                        ];

                        // Change the alignment if this is an RTL language.
                        if (window.right_to_left()) {
                            alignpoints = [
                                Y.WidgetPositionAlign.TR,
                                Y.WidgetPositionAlign.BR
                            ];
                        }


                        this.panel.set(\'align\', {
                            node: this.currentowner.get(\'node\').one(\'select\'),
                            points: alignpoints
                        });
                    };
                };
            })');
    }

    /**
     * Validate dates and room availability
     * @param int $timestart
     * @param int $timefinish
     * @param int $roomid
     * @param array $assetids
     * @param int $sessionid ignore room conflicts within current session (as it is covered by dates and some dates can be marked as deleted)
     * @param int $facetofaceid
     * @return array errors ('timestart' => string, 'timefinish' => string, 'assetids' => string, 'roomid' => string)
     */
    public static function dates_validate($timestart, $timefinish, $roomid, $assetids, $sessionid, $facetofaceid) {
        $errors = array();
        // Validate start time.
        if ($timestart > $timefinish) {
            $errstr = get_string('error:sessionstartafterend', 'facetoface');
            $errors['timestart'] = $errstr;
            $errors['timefinish'] = $errstr;
        }

        // Validate room.
        if (!empty($roomid)) {
            $roomproblemfound = false;
            // Check if the room is available.
            $room = facetoface_get_room($roomid);
            if (!$room) {
                $errors['roomid'] = get_string('roomdeleted', 'facetoface');
            } else if (!facetoface_is_room_available($timestart, $timefinish, $room, $sessionid, $facetofaceid)) {
                 $link = html_writer::link(new moodle_url('/mod/facetoface/room.php', array('roomid' => $roomid)), $room->name,
                        array('target' => '_blank'));
                // We should not get here because users should be able to select only available slots.
                $errors['roomid'] = get_string('error:isalreadybooked', 'facetoface', $link);
            }
        }

        // Validate assets.
        if (!empty($assetids)) {
            foreach ($assetids as $assetid) {
                $asset = facetoface_get_asset($assetid);
                if (!$asset) {
                    $errors['assetid'][] = get_string('assetdeleted', 'facetoface');
                } else if (!facetoface_is_asset_available($timestart, $timefinish, $asset, $sessionid, $facetofaceid)) {
                    $link = html_writer::link(new moodle_url('/mod/facetoface/asset.php', array('assetid' => $assetid)), $asset->name,
                        array('target' => '_blank'));
                    // We should not get here because users should be able to select only available slots.
                    $errors['assetid'][] = get_string('error:isalreadybooked', 'facetoface', $link);
                }
            }
            if (!empty($errors['assetid'])) {
                $errors['assetids'] = implode(html_writer::empty_tag('br'), $errors['assetid']);
            }
        }

        // Consolidate error message.
        if (!empty($errors['roomid']) || !empty($errors['assetid'])) {
            $items = array();
            if (!empty($errors['roomid'])) {
                $items[] = html_writer::tag('li', $errors['roomid']);
                // Don't show duplicate error.
                unset($errors['roomid']);
            }
            if (!empty($errors['assetid'])) {
                foreach ($errors['assetid'] as $asseterror) {
                    $items[] = html_writer::tag('li', $asseterror);
                }
                // Don't show duplicate error.
                unset($errors['assetid']);
            }
            $details = html_writer::tag('ul', implode('', $items));
            $errors['timestart'] = get_string('error:datesunavailablestuff', 'facetoface', $details);
        }
        return $errors;
    }

    function validation($data, $files) {
        $assetids = array();
        if (!empty($data['assetids'])) {
            $assetids = explode(',', $data['assetids']);
        }
        $facetofaceid = $this->_customdata['facetofaceid'];
        $errors = session_date_form::dates_validate($data['timestart'], $data['timefinish'], $data['roomid'], $assetids,
                $data['sessionid'], $facetofaceid);
        return $errors;
    }

}
