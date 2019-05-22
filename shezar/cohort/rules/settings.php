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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @package shezar
 * @subpackage cohort/rules
 */
/**
 * This file defines all the legal rule options for dynamic cohorts
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
require_once($CFG->dirroot . '/shezar/cohort/rules/ui.php');
require_once($CFG->dirroot . '/shezar/cohort/rules/sqlhandler.php');
require_once($CFG->dirroot . '/shezar/cohort/rules/sqlhandlers/inlist.php');
require_once($CFG->dirroot . '/shezar/cohort/rules/sqlhandlers/date.php');
require_once($CFG->dirroot . '/shezar/cohort/rules/sqlhandlers/completion.php');
require_once($CFG->dirroot . '/shezar/cohort/rules/sqlhandlers/manager.php');
require_once($CFG->dirroot . '/shezar/cohort/rules/sqlhandlers/userstatus.php');
require_once($CFG->dirroot . '/shezar/cohort/rules/sqlhandlers/cohortmember.php');
require_once($CFG->dirroot . '/shezar/cohort/rules/option.php');
require_once($CFG->dirroot . '/shezar/cohort/rules/sqlhandlers/custom_fields/custom_field_sqlhandler.php');

/* Constants to identify if the rule comes from a menu or a text input */
define('COHORT_RULES_TYPE_MENU', 1);
define('COHORT_RULES_TYPE_TEXT', 0);

/**
 * Get the list of defined cohort rules
 *
 * @param bool $reset Set to true to reset the cache.
 */
function cohort_rules_list($reset = false){
    global $CFG, $DB;
    static $rules = false;

    if (!$rules || $reset) {
        $rules = array();

        // User's idnumber
        $rules[] = new cohort_rule_option(
            'user',
            'idnumber',
            new cohort_rule_ui_text(
                get_string('ruledesc-user-idnumber', 'shezar_cohort'),
                get_string('rulehelp-user-idnumber', 'shezar_cohort')
            ),
            new cohort_rule_sqlhandler_in_userfield_char('idnumber', COHORT_RULES_TYPE_TEXT)
        );
        // User's username
        $rules[] = new cohort_rule_option(
            'user',
            'username',
            new cohort_rule_ui_text(
                get_string('ruledesc-user-username', 'shezar_cohort'),
                get_string('rulehelp-user-username', 'shezar_cohort')
            ),
            new cohort_rule_sqlhandler_in_userfield_char('username', COHORT_RULES_TYPE_TEXT)
        );
        // User's email address
        $rules[] = new cohort_rule_option(
            'user',
            'email',
            new cohort_rule_ui_text(
                get_string('ruledesc-user-email', 'shezar_cohort'),
                get_string('rulehelp-user-email', 'shezar_cohort')
            ),
            new cohort_rule_sqlhandler_in_userfield_char('email', COHORT_RULES_TYPE_TEXT)
        );
        // User's lang preference
        $rules[] = new cohort_rule_option(
            'user',
            'lang',
            new cohort_rule_ui_menu(
                get_string('ruledesc-user-lang', 'shezar_cohort'),
                get_string_manager()->get_list_of_translations()
            ),
            new cohort_rule_sqlhandler_in_userfield_char('lang', COHORT_RULES_TYPE_MENU)
        );
        // User's First Name
        $rules[] = new cohort_rule_option(
            'user',
            'firstname',
            new cohort_rule_ui_text(
                get_string('ruledesc-user-firstname', 'shezar_cohort'),
                get_string('separatemultiplebycommas', 'shezar_cohort')
            ),
            new cohort_rule_sqlhandler_in_userfield_char('firstname', COHORT_RULES_TYPE_TEXT)
        );
        // User's last name
        $rules[] = new cohort_rule_option(
            'user',
            'lastname',
            new cohort_rule_ui_text(
                get_string('ruledesc-user-lastname', 'shezar_cohort'),
                get_string('separatemultiplebycommas', 'shezar_cohort')
            ),
            new cohort_rule_sqlhandler_in_userfield_char('lastname', COHORT_RULES_TYPE_TEXT)
        );
        // User's city
        $rules[] = new cohort_rule_option(
            'user',
            'city',
            new cohort_rule_ui_text(
                get_string('ruledesc-user-city', 'shezar_cohort'),
                get_string('separatemultiplebycommas', 'shezar_cohort')
            ),
            new cohort_rule_sqlhandler_in_userfield_char('city', COHORT_RULES_TYPE_TEXT)
        );
        // User's country
        $rules[] = new cohort_rule_option(
            'user',
            'country',
            new cohort_rule_ui_menu(
                get_string('ruledesc-user-country', 'shezar_cohort'),
                get_string_manager()->get_list_of_countries()
            ),
            new cohort_rule_sqlhandler_in_userfield_char('country', COHORT_RULES_TYPE_MENU)
        );
        // User's institution
        $rules[] = new cohort_rule_option(
            'user',
            'institution',
            new cohort_rule_ui_text(
                get_string('ruledesc-user-institution', 'shezar_cohort'),
                get_string('separatemultiplebycommas', 'shezar_cohort')
            ),
            new cohort_rule_sqlhandler_in_userfield_char('institution', COHORT_RULES_TYPE_TEXT)
        );
        // User's department
        $rules[] = new cohort_rule_option(
            'user',
            'department',
            new cohort_rule_ui_text(
                get_string('ruledesc-user-department', 'shezar_cohort'),
                get_string('separatemultiplebycommas', 'shezar_cohort')
            ),
            new cohort_rule_sqlhandler_in_userfield_char('department', COHORT_RULES_TYPE_TEXT)
        );
        // User has a suspended status.
        $rules[] = new cohort_rule_option(
            'user',
            'suspendedusers',
            new cohort_rule_ui_checkbox(
                get_string('ruledesc-user-suspendedusers', 'shezar_cohort'),
                array(
                    0 => get_string('no'),
                    1 => get_string('yes')
                )
            ),
            new cohort_rule_sqlhandler_suspended_user_account()
        );
        // User custom fields
        $usercustomfields = $DB->get_records_sql(
            "SELECT usinfi.id, usinfi.name, usinfi.datatype, usinfi.param1
               FROM {user_info_field} usinfi
         INNER JOIN {user_info_category} usinca
                 ON usinfi.categoryid = usinca.id
              WHERE usinfi.datatype != ?
           ORDER BY usinca.sortorder, usinfi.sortorder",
            array('textarea')
        );
        if (!$usercustomfields) {
            $usercustomfields = array();
        }
        foreach ($usercustomfields as $id => $field) {
            $dialogs = array();
            switch($field->datatype) {
                case 'menu':
                    $options = explode("\n", $field->param1);
                    $dialogs[] = new cohort_rule_ui_menu(
                        get_string('usersx', 'shezar_cohort', format_string($field->name)),
                        array_combine($options, $options)
                    );
                    $sqlhandler = new custom_field_sqlhandler($id, $field->datatype, true);
                    break;
                case 'text':
                    // text input
                    $dialogui = new cohort_rule_ui_text(
                        get_string('usersx', 'shezar_cohort', format_string($field->name)),
                        get_string('separatemultiplebycommas', 'shezar_cohort')
                    );
                    $dialogui->selectoptionstr = format_string($field->name) . ' (' . get_string('text', 'shezar_cohort') . ')';
                    $dialogs[] = $dialogui;
                    $sqlhandler_text = new custom_field_sqlhandler($id, $field->datatype, false);

                    // choose from distinct customfield values
                    $sql = new stdClass;
                    $sql->select = "DISTINCT {$DB->sql_compare_text('data', 255)} AS mkey, {$DB->sql_compare_text('data', 255)} AS mval";
                    $sql->from = "{user_info_data}";
                    $sql->where = "fieldid = ?";
                    $sql->orderby = "{$DB->sql_compare_text('data', 255)}";
                    $sql->valuefield = "{$DB->sql_compare_text('data', 255)}";
                    $sql->sqlparams = array($id);
                    $dialogui = new cohort_rule_ui_menu(
                        get_string('usersx', 'shezar_cohort', format_string($field->name)),
                        $sql
                    );
                    $dialogui->selectoptionstr = format_string($field->name) . ' (' . get_string('choose', 'shezar_cohort') . ')';
                    $dialogs[] = $dialogui;

                    $sqlhandler = new custom_field_sqlhandler($id, $field->datatype, true);
                    unset($dialogui);
                    break;
                case 'datetime':
                    $dialogs[] = new cohort_rule_ui_date(
                        get_string('usersx', 'shezar_cohort', format_string($field->name))
                    );
                    $sqlhandler = new cohort_rule_sqlhandler_date_usercustomfield($id);
                    break;
                case 'checkbox':
                    $dialogs[] = new cohort_rule_ui_checkbox(
                        get_string('usersx', 'shezar_cohort', format_string($field->name)),
                        array(
                            1 => get_string('checkboxyes','shezar_cohort'),
                            0 => get_string('checkboxno', 'shezar_cohort')
                        )
                    );
                    $sqlhandler = new custom_field_sqlhandler($id, $field->datatype, true);
                    break;
                case 'date':
                    $dialogs[] = new cohort_rule_ui_date_no_timezone(
                        get_string('usersx', 'shezar_cohort', format_string($field->name))
                    );
                    $sqlhandler = new cohort_rule_sqlhandler_date_usercustomfield_no_timezone($id);
                    break;
                default:
                    // Skip fields that we haven't defined a rule type for
                    unset($dialogs);
                    unset($sqlhandler);
                    continue 2;
            }
            foreach ($dialogs as $i => $dialog) {
                $rules[] = new cohort_rule_option(
                    'usercustomfields',
                    "customfield{$id}_{$i}",
                    $dialog,
                    (get_class($dialog) == 'cohort_rule_ui_text' ) ? $sqlhandler_text : $sqlhandler,
                    !empty($dialog->selectoptionstr) ? $dialog->selectoptionstr : format_string($field->name)
                );
            }
        }

        // Audience rules applied across all job assignments.
        $rules[] = new cohort_rule_option(
            'alljobassign',
            'jobtitles',
            new cohort_rule_ui_text(
                get_string('ruledesc-alljobassign-titles', 'shezar_cohort'),
                get_string('rulehelp-job-title', 'shezar_cohort')
            ),
            new cohort_rule_sqlhandler_in_alljobassignfield('fullname', true)
        );
        // User's job assignment startdate.
        $rules[] = new cohort_rule_option(
            'alljobassign',
            'startdates',
            new cohort_rule_ui_date(
                get_string('ruledesc-alljobassign-startdates', 'shezar_cohort')
            ),
            new cohort_rule_sqlhandler_date_alljobassignments('startdate')
        );
        // User's job assignment enddate.
        $rules[] = new cohort_rule_option(
            'alljobassign',
            'enddates',
            new cohort_rule_ui_date(
                get_string('ruledesc-alljobassign-enddates', 'shezar_cohort')
            ),
            new cohort_rule_sqlhandler_date_alljobassignments('enddate')
        );

        // Position Rules.
        $rules[] = new cohort_rule_option(
            'alljobassign',
            'positions',
            new cohort_rule_ui_picker_hierarchy(
                get_string('ruledesc-alljobassign-posid', 'shezar_cohort'),
                'position'
            ),
            new cohort_rule_sqlhandler_in_listofids_allpos()
        );
        // User's position's name.
        $rules[] = new cohort_rule_option(
            'alljobassign',
            'posnames',
            new cohort_rule_ui_text(
                get_string('ruledesc-alljobassign-posnames', 'shezar_cohort'),
                get_string('rulehelp-pos-name', 'shezar_cohort')
            ),
            new cohort_rule_sqlhandler_in_posfield('fullname', true)
        );
        // User's position's idnumber.
        $rules[] = new cohort_rule_option(
            'alljobassign',
            'posidnumbers',
            new cohort_rule_ui_text(
                get_string('ruledesc-alljobassign-posidnumbers', 'shezar_cohort'),
                get_string('rulehelp-pos-idnumber', 'shezar_cohort')
            ),
            new cohort_rule_sqlhandler_in_posfield('idnumber', true)
        );
        // User's position assignment date.
        $rules[] = new cohort_rule_option(
            'alljobassign',
            'posassigndates',
            new cohort_rule_ui_date(
                get_string('ruledesc-alljobassign-posassigndates', 'shezar_cohort')
            ),
            new cohort_rule_sqlhandler_date_alljobassignments('positionassignmentdate')
        );
        // User's position's type.
        $pos = new position();
        $postypes = $pos->get_types();
        array_walk($postypes, function(&$item) { $item = $item->fullname; });
        $postypes[0] = get_string('unclassified', 'shezar_hierarchy');
        $rules[] = new cohort_rule_option(
            'alljobassign',
            'postypes',
            new cohort_rule_ui_menu(
                get_string('ruledesc-alljobassign-postypes', 'shezar_cohort'),
                $postypes
            ),
            new cohort_rule_sqlhandler_in_posfield('typeid', false)
        );
        // Custom fields for user's position.
        $poscustomfields = $DB->get_records_sql(
            "SELECT potyinfi.id, potyinfi.fullname as name, potyinfi.datatype, potyinfi.param1
               FROM {pos_type_info_field} potyinfi
         INNER JOIN {pos_type} poty
                 ON potyinfi.typeid = poty.id
              WHERE potyinfi.datatype != ?
           ORDER BY poty.fullname, potyinfi.sortorder", array('textarea')
        );
        if (!$poscustomfields) {
            $poscustomfields = array();
        }
        foreach ($poscustomfields as $id=>$field) {
            switch ($field->datatype) {
                case 'menu':
                    $options = explode("\n", $field->param1);
                    $dialog = new cohort_rule_ui_menu(
                        get_string('usersposx', 'shezar_cohort', $field->name),
                        array_combine($options, $options)
                    );
                    $sqlhandler = new cohort_rule_sqlhandler_in_poscustomfield($id, $field->datatype);
                    break;
                case 'text':
                    $dialog = new cohort_rule_ui_text(
                        get_string('usersposx', 'shezar_cohort', $field->name),
                        get_string('separatemultiplebycommas', 'shezar_cohort')
                    );
                    $sqlhandler = new cohort_rule_sqlhandler_in_poscustomfield($id, $field->datatype);
                    break;
                case 'datetime':
                    $dialog = new cohort_rule_ui_date(
                        get_string('usersposx', 'shezar_cohort', $field->name)
                    );
                    $sqlhandler = new cohort_rule_sqlhandler_date_poscustomfield($id, $field->datatype);
                    break;

                case 'checkbox':
                    $dialog = new cohort_rule_ui_checkbox(
                        get_string('usersposx', 'shezar_cohort', $field->name),
                        // Because it may result in major dataloss change the strings vice versa.
                        array(
                            1 => get_string('checkboxno', 'shezar_cohort'),
                            0 => get_string('checkboxyes', 'shezar_cohort')
                        )
                    );
                    $sqlhandler = new cohort_rule_sqlhandler_in_poscustomfield($id, $field->datatype);
                    break;
                default:
                    // Skip field types we haven't defined a rule for yet.
                    unset($dialog);
                    unset($sqlhandler);
                    continue 2;
            }

            $rules[] = new cohort_rule_option(
                'alljobassign',
                "poscustomfield{$id}",
                $dialog,
                $sqlhandler,
                s($field->name)
            );
        }

        // Organisation Rules.
        $rules[] = new cohort_rule_option(
            'alljobassign',
            'organisations',
            new cohort_rule_ui_picker_hierarchy(
                get_string('ruledesc-alljobassign-orgid', 'shezar_cohort'),
                'organisation'
            ),
            new cohort_rule_sqlhandler_in_listofids_allorg()
        );
        // User's organisation's name
        $rules[] = new cohort_rule_option(
            'alljobassign',
            'orgnames',
            new cohort_rule_ui_text(
                get_string('ruledesc-alljobassign-orgnames', 'shezar_cohort'),
                get_string('rulehelp-org-name', 'shezar_cohort')
            ),
            new cohort_rule_sqlhandler_in_orgfield('fullname', true)
        );
        // User's organisation's idnumber
        $rules[] = new cohort_rule_option(
            'alljobassign',
            'orgidnumbers',
            new cohort_rule_ui_text(
                get_string('ruledesc-alljobassign-orgidnumbers', 'shezar_cohort'),
                get_string('rulehelp-org-idnumber', 'shezar_cohort')
            ),
            new cohort_rule_sqlhandler_in_orgfield('idnumber', true)
        );
        // User's organisation's type.
        $org = new organisation();
        $orgtypes = $org->get_types();
        array_walk($orgtypes, function(&$item) { $item = $item->fullname; });
        $orgtypes[0] = get_string('unclassified', 'shezar_hierarchy');
        $rules[] = new cohort_rule_option(
            'alljobassign',
            'orgtypes',
            new cohort_rule_ui_menu(
                get_string('ruledesc-alljobassign-orgtypes', 'shezar_cohort'),
                $orgtypes
            ),
            new cohort_rule_sqlhandler_in_orgfield('typeid', false)
        );
        // Custom fields for user's organization.
        $orgcustomfields = $DB->get_records_sql(
            "SELECT ortyinfi.id, ortyinfi.fullname as name, ortyinfi.datatype, ortyinfi.param1
               FROM {org_type_info_field} ortyinfi
         INNER JOIN {org_type} orty
                 ON ortyinfi.typeid = orty.id
              WHERE ortyinfi.datatype != ?
           ORDER BY orty.fullname, ortyinfi.sortorder", array('textarea')
        );
        if (!$orgcustomfields) {
            $orgcustomfields = array();
        }
        foreach ($orgcustomfields as $id=>$field) {
            switch ($field->datatype) {
                case 'menu':
                    $options = explode("\n", $field->param1);
                    $dialog = new cohort_rule_ui_menu(
                        get_string('usersorgx', 'shezar_cohort', $field->name),
                        array_combine($options, $options)
                    );
                    $sqlhandler = new cohort_rule_sqlhandler_in_orgcustomfield($id, $field->datatype);
                    break;
                case 'text':
                    $dialog = new cohort_rule_ui_text(
                        get_string('usersorgx', 'shezar_cohort', $field->name),
                        get_string('separatemultiplebycommas', 'shezar_cohort')
                    );
                    $sqlhandler = new cohort_rule_sqlhandler_in_orgcustomfield($id, $field->datatype);
                    break;
                case 'datetime':
                    $dialog = new cohort_rule_ui_date(
                        get_string('usersorgx', 'shezar_cohort', $field->name)
                    );
                    $sqlhandler = new cohort_rule_sqlhandler_date_orgcustomfield($id, $field->datatype);
                    break;
                case 'checkbox':
                    $dialog = new cohort_rule_ui_checkbox(
                        get_string('usersorgx', 'shezar_cohort', $field->name),
                        // Because it may result in major dataloss change the strings vice versa.
                        array(
                            1 => get_string('checkboxno', 'shezar_cohort'),
                            0 => get_string('checkboxyes','shezar_cohort')
                        )
                    );
                    $sqlhandler = new cohort_rule_sqlhandler_in_orgcustomfield($id, $field->datatype);
                    break;
                default:
                    // Skip field types we haven't defined a rule for yet.
                    unset($dialog);
                    unset($sqlhandler);
                    continue 2;
            }

            $rules[] = new cohort_rule_option(
                'alljobassign',
                "orgcustomfield{$id}",
                $dialog,
                $sqlhandler,
                s($field->name)
            );
        }

        // Manager Rules.
        // TODO - it would be good to let people select the managers job assignments here. (separate rule).
        $rules[] = new cohort_rule_option(
            'alljobassign',
            'managers',
            new cohort_rule_ui_reportsto(),
            new cohort_rule_sqlhandler_allstaff()
        );
        // If the user is a manager in any of their job assignments.
        $rules[] = new cohort_rule_option(
            'alljobassign',
            'hasdirectreports',
            new cohort_rule_ui_checkbox(
                get_string('ruledesc-alljobassign-hasdirectreports', 'shezar_cohort'),
                array(
                    1 => get_string('directreportsyes', 'shezar_cohort'),
                    0 => get_string('directreportsno', 'shezar_cohort')
                )
            ),
            new cohort_rule_sqlhandler_hasreports()
        );

        // Learning (i.e. course & program completion)
        // Completion of all/any/none/not-all courses in a list
        $rules[] = new cohort_rule_option(
            'learning',
            'coursecompletionlist',
            new cohort_rule_ui_picker_course_allanynotallnone(
                get_string('ruledesc-learning-coursecompletionlist', 'shezar_cohort'),
                COHORT_PICKER_COURSE_COMPLETION
            ),
            new cohort_rule_sqlhandler_completion_list_course()
        );
        // Completion of all courses in a list before/after a fixed date
        $rules[] = new cohort_rule_option(
            'learning',
            'coursecompletiondate',
            new cohort_rule_ui_picker_course_program_date(
                get_string('ruledesc-learning-coursecompletiondate', 'shezar_cohort'),
                COHORT_PICKER_COURSE_COMPLETION
            ),
            new cohort_rule_sqlhandler_completion_date_course()
        );
        // Completion of all courses in a list within a given duration
        $rules[] = new cohort_rule_option(
            'learning',
            'coursecompletionduration',
            new cohort_rule_ui_picker_course_duration(
                get_string('ruledesc-learning-coursecompletionduration', 'shezar_cohort'),
                COHORT_PICKER_COURSE_COMPLETION
            ),
            new cohort_rule_sqlhandler_completion_duration_course()
        );
        if (shezar_feature_visible('programs')) {
            // Completion of all/any/not-all/none of programs in a list
            $rules[] = new cohort_rule_option(
                'learning',
                'programcompletionlist',
                new cohort_rule_ui_picker_program_allanynotallnone(
                    get_string('ruledesc-learning-programcompletionlist', 'shezar_cohort'),
                    COHORT_PICKER_PROGRAM_COMPLETION
                ),
                new cohort_rule_sqlhandler_completion_list_program()
            );
            // Completion of all programs in list before/after a fixed date
            $rules[] = new cohort_rule_option(
                'learning',
                'programcompletiondate',
                new cohort_rule_ui_picker_course_program_date(
                    get_string('ruledesc-learning-programcompletiondate', 'shezar_cohort'),
                    COHORT_PICKER_PROGRAM_COMPLETION
                ),
                new cohort_rule_sqlhandler_completion_date_program()
            );
            // Completion of all programs in a list within a given duration
            $rules[] = new cohort_rule_option(
                'learning',
                'programcompletionduration',
                new cohort_rule_ui_picker_program_duration(
                    get_string('ruledesc-learning-programcompletionduration', 'shezar_cohort'),
                    COHORT_PICKER_PROGRAM_COMPLETION
                ),
                new cohort_rule_sqlhandler_completion_duration_program()
            );
        }

        // Cohort member
        $rules[] = new cohort_rule_option(
            'cohort',
            'cohortmember',
            new cohort_rule_ui_cohortmember(),
            new cohort_rule_sqlhandler_cohortmember()
        );

        // System access! (though I think maybe this is better group under "user"?
        // User's first login date
        $rules[] = new cohort_rule_option(
            'systemaccess',
            'firstlogin',
            new cohort_rule_ui_date(
                get_string('ruledesc-systemaccess-firstlogin', 'shezar_cohort')
            ),
            new cohort_rule_sqlhandler_date_userfield('firstaccess')
        );
        // User's last login date
        $rules[] = new cohort_rule_option(
            'systemaccess',
            'lastlogin',
            new cohort_rule_ui_date(
                get_string('ruledesc-systemaccess-lastlogin', 'shezar_cohort')
            ),
            new cohort_rule_sqlhandler_date_userfield('currentlogin')
        );

        $indexedrules = array();
        foreach ($rules as $option) {
            $group = $option->group;
            $name = $option->name;
            if (!array_key_exists($group, $indexedrules)) {
                $indexedrules[$group] = array();
            }
            $indexedrules[$group][$name] = $option;
        }
        $rules = $indexedrules;
    }

    return $rules;
}
