<?php // $Id$
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
 * @subpackage shezar_hierarchy
 */

// This file defines settingpages and externalpages under the "hierarchies" category


    // Positions.
    if (!shezar_feature_disabled('positions')) {
        $ADMIN->add('hierarchies', new admin_category('positions', get_string('positions', 'shezar_hierarchy')));

        $ADMIN->add('positions', new admin_externalpage('positionmanage', get_string('positionmanage', 'shezar_hierarchy'),
            "{$CFG->wwwroot}/shezar/hierarchy/framework/index.php?prefix=position",
            array('shezar/hierarchy:createpositionframeworks', 'shezar/hierarchy:updatepositionframeworks', 'shezar/hierarchy:deletepositionframeworks',
                'shezar/hierarchy:createposition', 'shezar/hierarchy:updateposition', 'shezar/hierarchy:deleteposition',
                'shezar/hierarchy:viewpositionframeworks')));

        $ADMIN->add('positions', new admin_externalpage('positiontypemanage', get_string('managepositiontypes', 'shezar_hierarchy'),
            "{$CFG->wwwroot}/shezar/hierarchy/type/index.php?prefix=position",
            array('shezar/hierarchy:createpositiontype', 'shezar/hierarchy:updatepositiontype', 'shezar/hierarchy:deletepositiontype')));
    }

    // Organisations.
    $ADMIN->add('hierarchies', new admin_category('organisations', get_string('organisations', 'shezar_hierarchy')));

    $ADMIN->add('organisations', new admin_externalpage('organisationmanage', get_string('organisationmanage', 'shezar_hierarchy'),
            "{$CFG->wwwroot}/shezar/hierarchy/framework/index.php?prefix=organisation",
            array('shezar/hierarchy:createorganisationframeworks', 'shezar/hierarchy:updateorganisationframeworks', 'shezar/hierarchy:deleteorganisationframeworks',
                  'shezar/hierarchy:createorganisation', 'shezar/hierarchy:updateorganisation', 'shezar/hierarchy:deleteorganisation',
                  'shezar/hierarchy:vieworganisationframeworks')));

    $ADMIN->add('organisations', new admin_externalpage('organisationtypemanage', get_string('manageorganisationtypes', 'shezar_hierarchy'),
            "{$CFG->wwwroot}/shezar/hierarchy/type/index.php?prefix=organisation",
            array('shezar/hierarchy:createorganisationtype', 'shezar/hierarchy:updateorganisationtype', 'shezar/hierarchy:deleteorganisationtype')));


    // Competencies.
    $ADMIN->add('hierarchies', new admin_category('competencies', get_string('competencies', 'shezar_hierarchy'),
        shezar_feature_disabled('competencies')
    ));

    $ADMIN->add('competencies', new admin_externalpage('competencymanage', get_string('competencymanage', 'shezar_hierarchy'),
        "{$CFG->wwwroot}/shezar/hierarchy/framework/index.php?prefix=competency",
        array('shezar/hierarchy:createcompetencyframeworks', 'shezar/hierarchy:updatecompetencyframeworks', 'shezar/hierarchy:deletecompetencyframeworks',
              'shezar/hierarchy:createcompetency', 'shezar/hierarchy:updatecompetency', 'shezar/hierarchy:deletecompetency',
              'shezar/hierarchy:createcompetencyscale', 'shezar/hierarchy:updatecompetencyscale', 'shezar/hierarchy:deletecompetencyscale'),
        shezar_feature_disabled('competencies')
    ));

    $ADMIN->add('competencies', new admin_externalpage('competencytypemanage', get_string('managecompetencytypes', 'shezar_hierarchy'),
        "{$CFG->wwwroot}/shezar/hierarchy/type/index.php?prefix=competency",
        array('shezar/hierarchy:createcompetencytype', 'shezar/hierarchy:updatecompetencytype', 'shezar/hierarchy:deletecompetencytype'),
        shezar_feature_disabled('competencies')
    ));

//    $ADMIN->add('competencies', new admin_externalpage('competencyglobalsettings', get_string('globalsettings', 'competency'), "$CFG->wwwroot/hierarchy/prefix/competency/adminsettings.php",
//            array('shezar/hierarchy:updatecompetency')));

    // Goals.

    $ADMIN->add('hierarchies', new admin_category('goals', get_string('goals', 'shezar_hierarchy'),
        shezar_feature_disabled('goals')
    ));

    $ADMIN->add('goals', new admin_externalpage('goalmanage', get_string('goalmanage', 'shezar_hierarchy'),
            "{$CFG->wwwroot}/shezar/hierarchy/framework/index.php?prefix=goal",
            array('shezar/hierarchy:creategoalframeworks', 'shezar/hierarchy:updategoalframeworks', 'shezar/hierarchy:deletegoalframeworks',
                  'shezar/hierarchy:creategoal', 'shezar/hierarchy:updategoal', 'shezar/hierarchy:deletegoal',
                  'shezar/hierarchy:creategoalscale', 'shezar/hierarchy:updategoalscale', 'shezar/hierarchy:deletegoalscale'),
            shezar_feature_disabled('goals')));

    $ADMIN->add('goals', new admin_externalpage('companygoaltypemanage', get_string('managecompanygoaltypes', 'shezar_hierarchy'),
            "{$CFG->wwwroot}/shezar/hierarchy/type/index.php?prefix=goal&class=company",
            array('shezar/hierarchy:creategoaltype', 'shezar/hierarchy:updategoaltype', 'shezar/hierarchy:deletegoaltype'),
            shezar_feature_disabled('goals')));

    $ADMIN->add('goals', new admin_externalpage('personalgoaltypemanage', get_string('managepersonalgoaltypes', 'shezar_hierarchy'),
            "{$CFG->wwwroot}/shezar/hierarchy/type/index.php?prefix=goal&class=personal",
            array('shezar/hierarchy:creategoaltype', 'shezar/hierarchy:updategoaltype', 'shezar/hierarchy:deletegoaltype'),
            shezar_feature_disabled('goals')));

    $ADMIN->add('goals', new admin_externalpage('goalreport', get_string('goalreports', 'shezar_hierarchy'),
            "{$CFG->wwwroot}/shezar/hierarchy/prefix/goal/reports.php",
            array('shezar/hierarchy:viewgoalreport'), shezar_feature_disabled('goals')));
