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
 * @author Jonathan Newman <jonathan.newman@catalyst.net.nz>
 * @author Ciaran Irvine <ciaran.irvine@shezarlms.com>
 * @package shezar
 * @subpackage shezar_core
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

    // Managing course custom fields
    'shezar/core:coursemanagecustomfield' => array(
        'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'shezar/core:updatecoursecustomfield',
    ),
    // Managing program custom fields.
    'shezar/core:programmanagecustomfield' => array(
        'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'shezar/core:updateprogramcustomfield',
    ),
    'shezar/core:undeleteuser' => array(
        'riskbitmask'   => RISK_CONFIG,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
            'manager'   => CAP_ALLOW
        )
    ),
    'shezar/core:seedeletedusers' => array(
        'riskbitmask'   => RISK_PERSONAL | RISK_CONFIG,
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
            'manager' => CAP_ALLOW
        )
    ),
    'shezar/core:appearance' => array(
        'riskbitmask'   => RISK_CONFIG,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
        ),
        'clonepermissionsfrom' => 'moodle/site:config'
    ),

    // Unlock course completion.
    'moodle/course:unlockcompletion' => array(
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/course:update'
    ),

    'moodle/course:managereminders' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

    // Manage audience visibility.
    'shezar/coursecatalog:manageaudiencevisibility' => array(
        'riskbitmask'  => RISK_CONFIG | RISK_SPAM,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => array(
            'manager' => CAP_ALLOW
        )
    ),

    // Assign own temporary manager.
    'shezar/core:delegateownmanager' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes'   => array(
            'manager'       => CAP_ALLOW
        ),
        'clonepermissionsfrom' => ' shezar/hierarchy:assignselfposition'
    ),
    // Assign temporary manager to users.
    'shezar/core:delegateusersmanager' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes'   => array(
            'manager'       => CAP_ALLOW,
        )
    ),
    // Update user ID number.
    'shezar/core:updateuseridnumber' => array(
        'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),
    ),
    // View Record of Learning for other users.
    'shezar/core:viewrecordoflearning' => array(
        'riskbitmask'   => RISK_PERSONAL,
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_USER,
        'archetypes' => array(
            'staffmanager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'shezar/plan:accessanyplan'
    ),
    // Customise the main navigation menu.
    'shezar/core:editmainmenu' => array(
        'riskbitmask'   => RISK_CONFIG,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),
    ),
    // Allows for the user to configure activity module settings. No one gets this by default.
    'shezar/core:modconfig' => array(
        'riskbitmask' => RISK_CONFIG,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
        ),
    ),
    // Allows the user to install and uninstall languages for the site.
    'shezar/core:langconfig' => array(
        'riskbitmask' => RISK_CONFIG,
        'captype'     => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
        ),
    ),

    // Allows for the user to manage user profile custom fields.
    'shezar/core:manageprofilefields' => array(
        'riskbitmask' => RISK_CONFIG,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
        ),
    ),

    // Allows for the user to mark another user's courses as complete
    'shezar/core:markusercoursecomplete' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_USER,
        'archetypes'    => array(
        ),
    ),
);
