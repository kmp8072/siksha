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
 * @author Ciaran Irvine <ciaran@catalyst.net.nz>
 * @package shezar
 * @subpackage hierarchy
 */

/*
 * The capabilities are loaded into the database table when the module is
 * installed or updated. Whenever the capability definitions are updated,
 * the module version number should be bumped up.
 *
 * The system has four possible values for a capability:
 * CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
 */

$capabilities = array(

        // Viewing and managing a competency.
        'shezar/hierarchy:viewcompetency' => array(
            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'user' => CAP_ALLOW
                ),
            ),
        'shezar/hierarchy:createcompetency' => array(
            'captype'       => 'write',
            'contextlevel'  => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
                ),
            ),
        'shezar/hierarchy:updatecompetency' => array(
            'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
            'captype'       => 'write',
            'contextlevel'  => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
                ),
            ),
        'shezar/hierarchy:deletecompetency' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:createcompetencytype' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:updatecompetencytype' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:deletecompetencytype' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:createcompetencyframeworks' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:updatecompetencyframeworks' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:deletecompetencyframeworks' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:createcompetencytemplate' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:updatecompetencytemplate' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:competencymanagecustomfield' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                'clonepermissionsfrom' => 'shezar/hierarchy:updatecompetencycustomfield',
                ),
        'shezar/hierarchy:viewcompetencyscale' => array(
                'captype'       => 'read',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                'clonepermissionsfrom' => 'shezar/hierarchy:viewcompetencyframeworks'
                ),
        'shezar/hierarchy:createcompetencyscale' => array(
                'riskbitmask'   => RISK_SPAM,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                'clonepermissionsfrom' => 'shezar/hierarchy:createcompetencyframeworks'
                ),
        'shezar/hierarchy:updatecompetencyscale' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                'clonepermissionsfrom' => 'shezar/hierarchy:updatecompetencyframeworks'
                ),
        'shezar/hierarchy:deletecompetencyscale' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                'clonepermissionsfrom' => 'shezar/hierarchy:deletecompetencyframeworks'
                ),

        // Viewing and managing positions.
        'shezar/hierarchy:viewposition' => array(
                'riskbitmask' => RISK_PERSONAL,
                'captype'      => 'read',
                'contextlevel' => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW,
                    'student' => CAP_ALLOW,
                    'user' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:createposition' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:updateposition' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:deleteposition' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:createpositiontype' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:updatepositiontype' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:deletepositiontype' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:createpositionframeworks' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:updatepositionframeworks' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:deletepositionframeworks' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:positionmanagecustomfield' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                'clonepermissionsfrom' => 'shezar/hierarchy:updatepositioncustomfield',
                ),

        // Viewing and managing organisations.
        'shezar/hierarchy:vieworganisation' => array(
                'riskbitmask' => RISK_PERSONAL,
                'captype'      => 'read',
                'contextlevel' => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW,
                    'student' => CAP_ALLOW,
                    'user' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:createorganisation' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:updateorganisation' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:deleteorganisation' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:createorganisationtype' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:updateorganisationtype' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:deleteorganisationtype' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:createorganisationframeworks' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:updateorganisationframeworks' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:deleteorganisationframeworks' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:organisationmanagecustomfield' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                'clonepermissionsfrom' => 'shezar/hierarchy:updateorganisationcustomfield',
                ),

        // Assign a position to yourself.
        'shezar/hierarchy:assignselfposition' => array(
            'captype' => 'write',
            'contextlevel' => CONTEXT_SYSTEM,
            ),

        // Assign a position to a user.
        'shezar/hierarchy:assignuserposition' => array(
            'captype' => 'write',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
                ),
            ),

        // Goals permissions - Management.
        'shezar/hierarchy:viewgoal' => array(
            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'user' => CAP_ALLOW
                ),
            'clonepermissionsfrom' => 'shezar/hierarchy:viewcompetency'
            ),
        'shezar/hierarchy:creategoal' => array(
            'riskbitmask' => RISK_SPAM,
            'captype'       => 'write',
            'contextlevel'  => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
                ),
            'clonepermissionsfrom' => 'shezar/hierarchy:createcompetency'
            ),
        'shezar/hierarchy:updategoal' => array(
            'riskbitmask'   => RISK_DATALOSS,
            'captype'       => 'write',
            'contextlevel'  => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
                ),
            'clonepermissionsfrom' => 'shezar/hierarchy:updatecompetency'
            ),
        'shezar/hierarchy:deletegoal' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
            'clonepermissionsfrom' => 'shezar/hierarchy:deletecompetency'
                ),
        'shezar/hierarchy:creategoaltype' => array(
            'riskbitmask' => RISK_SPAM,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
            'clonepermissionsfrom' => 'shezar/hierarchy:createcompetencytype'
                ),
        'shezar/hierarchy:updategoaltype' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
            'clonepermissionsfrom' => 'shezar/hierarchy:updatecompetencytype'
                ),
        'shezar/hierarchy:deletegoaltype' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
            'clonepermissionsfrom' => 'shezar/hierarchy:deletecompetencytype'
                ),
        'shezar/hierarchy:creategoalframeworks' => array(
            'riskbitmask' => RISK_SPAM,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
            'clonepermissionsfrom' => 'shezar/hierarchy:createcompetencyframeworks'
                ),
        'shezar/hierarchy:updategoalframeworks' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
            'clonepermissionsfrom' => 'shezar/hierarchy:updatecompetencyframeworks'
                ),
        'shezar/hierarchy:deletegoalframeworks' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'wrireadte',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
            'clonepermissionsfrom' => 'shezar/hierarchy:deletecompetencyframeworks'
                ),
        'shezar/hierarchy:goalmanagecustomfield' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                'clonepermissionsfrom' => 'shezar/hierarchy:updategoalcustomfield'
                ),
        'shezar/hierarchy:viewgoalscale' => array(
                'captype'       => 'read',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:creategoalscale' => array(
                'riskbitmask'   => RISK_SPAM,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:updategoalscale' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:deletegoalscale' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'shezar/hierarchy:viewgoalreport' => array(
                'riskbitmask' => RISK_PERSONAL,
                'captype' => 'read',
                'contextlevel' => CONTEXT_SYSTEM,
                'archetypes' => array('manager' => CAP_ALLOW),
                'clonepermissionsfrom' => 'shezar/hierarchy:viewgoal'
        ),
        'shezar/hierarchy:editgoalreport' => array(
                'riskbitmask' => RISK_PERSONAL | RISK_CONFIG,
                'captype' => 'write',
                'contextlevel' => CONTEXT_SYSTEM,
                'archetypes' => array('manager' => CAP_ALLOW),
                'clonepermissionsfrom' => 'shezar/hierarchy:updategoal'
        ),

        // User goals self management permissions.
        'shezar/hierarchy:viewownpersonalgoal' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_USER,
            'archetypes' => array(
                'user' => CAP_ALLOW
                )
            ),
        'shezar/hierarchy:viewowncompanygoal' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_USER,
            'archetypes' => array(
                'user' => CAP_ALLOW
                )
            ),
        'shezar/hierarchy:manageownpersonalgoal' => array(
            'captype' => 'write',
            'contextlevel' => CONTEXT_USER,
            'archetypes' => array(
                'user' => CAP_ALLOW
                )
            ),
        'shezar/hierarchy:manageowncompanygoal' => array(
            'captype' => 'write',
            'contextlevel' => CONTEXT_USER,
            'archetypes' => array(
                'user' => CAP_ALLOW
                )
            ),

        // Manager team goal management permissions.
        'shezar/hierarchy:viewstaffpersonalgoal' => array(
            'riskbitmask'   => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_USER,
                'archetypes' => array(
                    'staffmanager' => CAP_ALLOW
                    ),
            ),
        'shezar/hierarchy:viewstaffcompanygoal' => array(
            'riskbitmask'   => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_USER,
                'archetypes' => array(
                    'staffmanager' => CAP_ALLOW
                    ),
            ),
        'shezar/hierarchy:managestaffpersonalgoal' => array(
            'riskbitmask'   => RISK_PERSONAL | RISK_SPAM | RISK_DATALOSS,
            'captype' => 'write',
            'contextlevel' => CONTEXT_USER,
                'archetypes' => array(
                    'staffmanager' => CAP_ALLOW
                    ),
            ),
        'shezar/hierarchy:managestaffcompanygoal' => array(
            'riskbitmask'   => RISK_SPAM | RISK_DATALOSS,
            'captype' => 'write',
            'contextlevel' => CONTEXT_USER,
                'archetypes' => array(
                    'staffmanager' => CAP_ALLOW
                    ),
            ),

        // Admin site goal management permissions.
        'shezar/hierarchy:managegoalassignments' => array(
            'riskbitmask'   => RISK_SPAM,
            'captype' => 'write',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
                ),
            ),

        // Additional view framework permissions.
        'shezar/hierarchy:viewcompetencyframeworks' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'shezar/hierarchy:updatecompetencyframeworks'
        ),
        'shezar/hierarchy:viewpositionframeworks' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'shezar/hierarchy:updatepositionframeworks'
        ),
        'shezar/hierarchy:vieworganisationframeworks' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'shezar/hierarchy:updateorganisationframeworks'
        ),

        'shezar/hierarchy:viewgoalframeworks' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW,
                'staffmanager' => CAP_ALLOW,
                'user' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'shezar/hierarchy:updategoalframeworks'
        ),
    );
