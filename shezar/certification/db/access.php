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
 * @author Jon Sharp <jon.sharp@catalyst-eu.net>
 * @package shezar
 * @subpackage certification
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    // View hidden certifications.
    'shezar/certification:viewhiddencertifications' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_PROGRAM,
        'archetypes' => array(
            'coursecreator' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),
    // Create new certifications.
    'shezar/certification:createcertification' => array(
        'riskbitmask' => RISK_XSS | RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        )
    ),
    // Delete certifications.
    'shezar/certification:deletecertification' => array(
        'riskbitmask' => RISK_DATALOSS |
            RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_PROGRAM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        ),
        'clonepermissionsfrom' => 'shezar/certification:createcertification'
    ),
    // Ability to edit and delete certifications.
    'shezar/certification:configurecertification' => array(
        'riskbitmask' => RISK_DATALOSS | RISK_XSS |
            RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_PROGRAM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        )
    ),
    // Ability to edit the certification details tab.
    'shezar/certification:configuredetails' => array(
        'riskbitmask' => RISK_DATALOSS | RISK_XSS |
            RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_PROGRAM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        ),
        'clonepermissionsfrom' => 'shezar/certification:configurecertification'
    ),
);
