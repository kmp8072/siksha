<?php

require_once($CFG->libdir.'/formslib.php');

class reminder_edit_form extends moodleform {

    function definition() {
        global $USER, $CFG;

        $mform    =& $this->_form;

        $course   = $this->_customdata['course'];
        $reminder = $this->_customdata['reminder'];

        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid', null);
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', $course->id);

        // Get activities with completion enabled
        $completion = new completion_info($course);
        $activities = $completion->get_activities();

        $choices = array();
        $choices[0] = get_string('coursecompletion');

        // Get modules that are part of completion criteria.
        require_once($CFG->dirroot.'/completion/criteria/completion_criteria_activity.php');
        $completioncriteria = completion_criteria_activity::fetch_all(array('course' => $course->id, 'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY));

        $coursemodinfo = get_fast_modinfo($course);

        if ($completioncriteria) {
            foreach ($completioncriteria as $criterion) {
                $module = $coursemodinfo->get_cm($criterion->moduleinstance);
                $choices[$module->id] = get_string('modulename', $module->modname) . ' - ' . $module->name;
            }
        }

        // Get feedback activities in the course
        $feedbackmods = $coursemodinfo->get_instances_of('feedback');
        $rchoices = array('' => get_string('select').'...');
        if (!empty($feedbackmods)) {
            foreach ($feedbackmods as $feedbackmod) {
                $rchoices[$feedbackmod->id] = $feedbackmod->name;
            }
        }

/// form definition
//--------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('reminder', 'shezar_coursecatalog'));

        $mform->addElement('text', 'title', get_string('title', 'shezar_coursecatalog'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addHelpButton('title', 'title', 'shezar_coursecatalog');

        $mform->addRule('title', get_string('missingtitle', 'shezar_coursecatalog'), 'required', null, 'client');

        $mform->addElement('select', 'tracking', get_string('completiontotrack', 'shezar_coursecatalog'), $choices);
        $mform->addHelpButton('tracking', 'tracking', 'shezar_coursecatalog');
        $mform->addRule('tracking', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('tracking', PARAM_INT);

        $mform->addElement('select', 'requirement', get_string('requirement', 'shezar_coursecatalog'), $rchoices);
        $mform->addHelpButton('requirement', 'requirement', 'shezar_coursecatalog');
        $mform->addRule('requirement', get_string('required'), 'required', null, 'client');
        $mform->setType('requirement', PARAM_INT);

//--------------------------------------------------------------------------------
        $mform->addElement('header', 'invitation', get_string('invitation', 'shezar_coursecatalog'));

        $options = range(2, 30);
        array_unshift($options, get_string('nextday', 'shezar_coursecatalog'));
        array_unshift($options, get_string('sameday', 'shezar_coursecatalog'));
        $mform->addElement('select', 'invitationperiod', get_string('period', 'shezar_coursecatalog'), $options);
        $mform->setType('invitationperiod', PARAM_INT);
        $mform->addHelpButton('invitationperiod', 'invitationperiod', 'shezar_coursecatalog');
        $mform->setDefault('invitationperiod', 0);

        $mform->addElement('text', 'invitationsubject', get_string('subject', 'shezar_coursecatalog'), 'maxlength="254" size="80"');
        $mform->addHelpButton('invitationsubject', 'invitationsubject', 'shezar_coursecatalog');
        $mform->setDefault('invitationsubject', get_string('invitationsubjectdefault', 'shezar_coursecatalog'));
        $mform->setType('invitationsubject', PARAM_MULTILANG);

        $mform->addElement('textarea', 'invitationmessage', get_string('message', 'shezar_coursecatalog'), 'rows="15" cols="70"');
        $mform->addHelpButton('invitationmessage', 'invitationmessage', 'shezar_coursecatalog');
        $mform->setDefault('invitationmessage', get_string('invitationmessagedefault', 'shezar_coursecatalog'));
        $mform->setType('invitationmessage', PARAM_MULTILANG);

//--------------------------------------------------------------------------------
        $mform->addElement('header', 'reminder', get_string('reminder', 'shezar_coursecatalog'));

        $mform->addElement('select', 'reminderperiod', get_string('period', 'shezar_coursecatalog'), $options);
        $mform->setType('reminderperiod', PARAM_INT);
        $mform->addHelpButton('reminderperiod', 'reminderperiod', 'shezar_coursecatalog');
        $mform->setDefault('reminderperiod', 1);

        $mform->addElement('text', 'remindersubject', get_string('subject', 'shezar_coursecatalog'), 'maxlength="254" size="80"');
        $mform->addHelpButton('remindersubject', 'remindersubject', 'shezar_coursecatalog');
        $mform->setDefault('remindersubject', get_string('remindersubjectdefault', 'shezar_coursecatalog'));
        $mform->setType('remindersubject', PARAM_MULTILANG);

        $mform->addElement('textarea', 'remindermessage', get_string('message', 'shezar_coursecatalog'), 'rows="15" cols="70"');
        $mform->addHelpButton('remindermessage', 'remindermessage', 'shezar_coursecatalog');
        $mform->setDefault('remindermessage', get_string('remindermessagedefault', 'shezar_coursecatalog'));
        $mform->setType('remindermessage', PARAM_MULTILANG);

//--------------------------------------------------------------------------------
        $mform->addElement('header', 'escalation', get_string('escalation', 'shezar_coursecatalog'));

        $mform->addElement('checkbox', 'escalationdontsend', get_string('dontsend', 'shezar_coursecatalog'));
        $mform->setType('escalationdontsend', PARAM_INT);
        $mform->setDefault('escalationdontsend', 0);

        $mform->addElement('checkbox', 'escalationskipmanager', get_string('skipmanager', 'shezar_coursecatalog'));
        $mform->setType('escalationskipmanager', PARAM_INT);
        $mform->setDefault('escalationskipmanager', 0);
        $mform->disabledIf('escalationskipmanager', 'escalationdontsend', 'checked');

        $mform->addElement('select', 'escalationperiod', get_string('period', 'shezar_coursecatalog'), $options);
        $mform->setType('escalationperiod', PARAM_INT);
        $mform->addHelpButton('escalationperiod', 'reminderperiod', 'shezar_coursecatalog');
        $mform->setDefault('escalationperiod', 1);
        $mform->disabledIf('escalationperiod', 'escalationdontsend', 'checked');

        $mform->addElement('text', 'escalationsubject', get_string('subject', 'shezar_coursecatalog'), 'maxlength="254" size="80"');
        $mform->addHelpButton('escalationsubject', 'invitationsubject', 'shezar_coursecatalog');
        $mform->setDefault('escalationsubject', get_string('escalationsubjectdefault', 'shezar_coursecatalog'));
        $mform->setType('escalationsubject', PARAM_MULTILANG);
        $mform->disabledIf('escalationsubject', 'escalationdontsend', 'checked');

        $mform->addElement('textarea', 'escalationmessage', get_string('message', 'shezar_coursecatalog'), 'rows="15" cols="70"');
        $mform->addHelpButton('escalationmessage', 'remindermessage', 'shezar_coursecatalog');
        $mform->setDefault('escalationmessage', get_string('escalationmessagedefault', 'shezar_coursecatalog'));
        $mform->setType('escalationmessage', PARAM_MULTILANG);
        $mform->disabledIf('escalationmessage', 'escalationdontsend', 'checked');

//--------------------------------------------------------------------------------
        $this->add_action_buttons();

//--------------------------------------------------------------------------------
    }

    function definition_after_data() {

        $mform    =& $this->_form;

        if (!$mform->getElementValue('id')) {
            $mform->setDefault('id', -1);
        }
    }
}
