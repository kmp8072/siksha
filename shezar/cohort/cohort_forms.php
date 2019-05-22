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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package shezar
 * @subpackage cohort
 */
/**
 * This file defines the form for editing the list of rules for a dynamic cohort.
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once($CFG->dirroot . '/lib/formslib.php');

class cohort_rules_form extends moodleform {
    function definition() {
        global $CFG, $OUTPUT;
        $mform =& $this->_form;
        $strdelete = get_string('delete');
        $cohort = $this->_customdata['cohort'];
        $rulesets = $this->_customdata['rulesets'];

        $mform->addElement('hidden', 'id', $cohort->id);
        $mform->setType('id', PARAM_INT);

        $addremovegroup = array();
        $addremovegroup[] =& $mform->createElement('advcheckbox', 'addnewmembers', '', get_string('addnewmembers', 'shezar_cohort'),
                                                    array('class' => 'memberoptions'));
        $addremovegroup[] =& $mform->createElement('advcheckbox', 'removeoldmembers', '', get_string('removeoldmembers',
                                                    'shezar_cohort'), array('class' => 'memberoptions'));

        // Set all checkboxs to be checked by default
        $mform->setDefault('addnewmembers', 1);
        $mform->setDefault('removeoldmembers', 1);

        $mform->addGroup($addremovegroup, 'addremove', get_string('addremovelabel', 'shezar_cohort'), html_writer::empty_tag('br'), false);
        $mform->addHelpButton('addremove', 'addremovehelp', 'shezar_cohort');

        // The menu for the operator between rulesets.
        $radiogroup = array();
        $radiogroup[] =& $mform->createElement('radio', 'cohortoperator', '', get_string('cohortoperatorandlabel', 'shezar_cohort'), COHORT_RULES_OP_AND);
        $radiogroup[] =& $mform->createElement('radio', 'cohortoperator', '', get_string('cohortoperatororlabel', 'shezar_cohort'), COHORT_RULES_OP_OR);
        $mform->addGroup($radiogroup, 'cohortoperator', get_string('cohortoperatorlabel', 'shezar_cohort'), html_writer::empty_tag('br'), false);
        $mform->addHelpButton('cohortoperator', 'cohortoperatorlabel', 'shezar_cohort');
        $mform->setDefault('cohortoperator', COHORT_RULES_OP_AND);
        $mform->setType('cohortoperator', PARAM_INT);

        $firstruleset = true;

        foreach ($rulesets as $ruleset) {
            $id = $ruleset->id;

            if ($firstruleset) {
                $firstruleset = false;
            } else {
                $opstr = '<div class="cohort-oplabel" id="oplabel'.$id.'">';
                switch ($cohort->rulesetoperator) {
                    case COHORT_RULES_OP_AND:
                        $opstr .= get_string('andcohort', 'shezar_cohort');
                        break;
                    case COHORT_RULES_OP_OR:
                        $opstr .= get_string('orcohort', 'shezar_cohort');
                        break;
                    default:
                        $opstr .= $cohort->rulesetoperator;
                }
                $opstr .= '</div>';
                $mform->addElement('static', "operator{$id}", $opstr, '');
                $mform->closeHeaderBefore("operator{$id}");
            }

            $mform->addElement('header', "cohort-ruleset-header{$id}", $ruleset->name);

            // The menu for the operator in this ruleset.
            $radiogroup = array();
            $radioname = "rulesetoperator[{$id}]";
            $radiogroup[] =& $mform->createElement('radio', $radioname, '', get_string('cohortoperatorandlabel', 'shezar_cohort'), COHORT_RULES_OP_AND);
            $radiogroup[] =& $mform->createElement('radio', $radioname, '', get_string('cohortoperatororlabel', 'shezar_cohort'), COHORT_RULES_OP_OR);
            $mform->addGroup($radiogroup, $radioname, get_string('rulesetoperatorlabel', 'shezar_cohort'), '<br />', false);
            $mform->setType($radioname, PARAM_INT);

            $ruledata = cohort_ruleset_form_template_object($ruleset);
            $mform->addElement('html', $OUTPUT->render_from_template('shezar_cohort/editing_ruleset', $ruledata));

            // todo: what should the label for this select be?
            $mform->addElement(
                'selectgroups',
                "addrulemenu{$id}",
                '',
                cohort_rules_get_menu_options(),
                array(
                    'class' => 'rule_selector new_rule_selector ignoredirty',
                    'data-idtype' => 'ruleset',
                    'data-id' => $ruleset->id,
                )
            );
        }

        // The menu to add a new ruleset
        $mform->addElement('header', 'addruleset', get_string('addruleset', 'shezar_cohort'));
        $mform->addElement(
            'selectgroups',
            'addrulesetmenu',
            '',
            cohort_rules_get_menu_options(),
            array(
                'class' => 'rule_selector new_rule_selector ignoredirty',
                'data-idtype' => 'cohort',
                'data-id' => $cohort->id,
            )
        );
        $mform->setDefault('addrulesetmenu', 'default');

        // todo: Need to ajaxify the and/or radios so that we can get rid of these buttons altogether
        $this->add_action_buttons(true, get_string('updateoperatorsbutton', 'shezar_cohort'));
    }
}

/**
 * Formslib template for cohort learning plan settings from
 */
class cohort_learning_plan_settings_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        $cohort = $this->_customdata['data'];

        $mform->addElement('hidden', 'cohortid', $cohort->id);
        $mform->setType('cohortid', PARAM_INT);

        $templates = dp_get_templates();

        $default_template = dp_get_default_template();

        $template_options = array();
        foreach ($templates as $template) {
            $template_options[$template->id] = format_string($template->fullname);
        }

        $mform->addElement('select', 'plantemplateid', get_string('plantemplate', 'shezar_plan'), $template_options);
        $mform->setDefault('plantemplateid', $default_template->id);

        $excludegroup = array();
        $excludegroup[] =& $mform->createElement('advcheckbox', 'excludecreatedmanual', '', get_string('createforexistingmanualplan', 'shezar_cohort'));
        $excludegroup[] =& $mform->createElement('advcheckbox', 'excludecreatedauto', '', get_string('createforexistingautoplan', 'shezar_cohort'));
        $excludegroup[] =& $mform->createElement('advcheckbox', 'excludecompleted', '', get_string('createforexistingcompleteplan', 'shezar_cohort'));

        // Set all checkboxes to be checked by default.
        $mform->setDefault('excludecreatedmanual', 1);
        $mform->setDefault('excludecreatedauto', 1);
        $mform->setDefault('excludecompleted', 1);

        $mform->addGroup($excludegroup, 'exclude', get_string('excludeuserswho', 'shezar_cohort'), html_writer::empty_tag('br'), false);
        $mform->addHelpButton('exclude', 'excludeuserswho', 'shezar_cohort');

        $plan_statuses = array (
            DP_PLAN_STATUS_UNAPPROVED => get_string('unapproved', 'shezar_plan'),
            DP_PLAN_STATUS_APPROVED => get_string('approved', 'shezar_plan')
        );
        $mform->addElement('select', 'planstatus', get_string('createplanstatus', 'shezar_cohort'), $plan_statuses);

        $autocreatenewgrp = array();
        $autocreatenewgrp[] = $mform->createElement('advcheckbox', 'autocreatenew', '', get_string('createplansfornewmembers', 'shezar_cohort'));

        $mform->addGroup($autocreatenewgrp, 'autocreatenew', get_string('autocreatenew', 'shezar_cohort'), '', false);
        $mform->addHelpButton('autocreatenew', 'autocreatenew', 'shezar_cohort');

        $mform->disabledIf('autocreatenew', 'excludecreatedauto', 'notchecked');

        $this->add_action_buttons(false, get_string('saveandcreateplans', 'shezar_cohort'));
    }
}
