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
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package shezar_core
 */

/**
 * List of strings that are expected to have cases-insensitive 'Moodle' word in them.
 */
$exceptions = array(
    'core_admin' => array(
        'configallowoverride2', 'configallowswitch', 'configallowuserswitchrolestheycantassign', 'showuseridentity_desc', // Caps.
        'configenablemobilewebservice', // Broken mobile app.
        'requiredentrieschanged', // Upgrade notes.
        'unsupporteddbtablerowformat', // Link to docs
    ),
    'core_cohort' => array('visible_help'), // Caps.
    'core_completion' => array('err_noroles', 'manualcompletionbynote', 'err_noroles'), // Caps.
    'core_error' => array('mimetexisnotexist', 'mssqlrcsmodemissing', 'pluginrequirementsnotmet'),
    'core_hub' => true,
    'core_install' => array('welcomep40'), // Placeholders.
    'core_moodle' => array('backupnonisowarning', 'gpl', 'gpl3', 'moodleversion', 'moodlerelease', 'registrationinfo', 'registrationsend'),
    'core_question' => array('cwrqpfsinfo'),
    'core_rating' => array('rolewarning_help'), // Caps.
    'core_webservice' => array('testauserwithtestclientdescription'), // Caps.
    'tool_xmldb' => array('confirmcheckoraclesemantics'),
    'shezar_core' => array(
        'error:cannotupgradefrommoodle', 'error:cannotupgradefromnewermoodle', 'moodlecore', 'shezarcopyright',
    ),
    'tool_behat' => array('fieldvalueargument_help'), // Docs link.
    'tool_installaddon' => true, // Mentions "Moodle plugin repo"
    'message_airnotifier' => array( // Broken mobile stuff.
        'configairnotifiermobileappname', 'errorretrievingkey', 'nodevices', 'sitemustberegistered'
    ),
    'enrol_ldap' => array('autocreate'),
    'mod_facetoface' => array('mincapacity_help'),
    'auth_ldap' => array('auth_ntlmsso_enabled'),
    'theme_bootstrapbase' => array('choosereadme'),
);
