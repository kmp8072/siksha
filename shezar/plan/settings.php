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
 * @author  Simon Coggins <simon.coggins@shezarlms.com>
 * @package shezar
 * @subpackage plan
 */

/**
 * Add learning plans administration menu settings
 */
defined('MOODLE_INTERNAL') || die;

    $ADMIN->add('shezar_plan',
        new admin_externalpage('managetemplates',
            new lang_string('managetemplates', 'shezar_plan'),
            "$CFG->wwwroot/shezar/plan/template/index.php",
            array('shezar/plan:configureplans'),
            shezar_feature_disabled('learningplans')
        )
    );

    $ADMIN->add('shezar_plan',
        new admin_externalpage('priorityscales',
            new lang_string('priorityscales', 'shezar_plan'),
            "$CFG->wwwroot/shezar/plan/priorityscales/index.php",
            array('shezar/plan:configureplans'),
            shezar_feature_disabled('learningplans')
        )
    );

    $ADMIN->add('shezar_plan',
        new admin_externalpage('objectivescales',
            new lang_string('objectivescales', 'shezar_plan'),
            "$CFG->wwwroot/shezar/plan/objectivescales/index.php",
            array('shezar/plan:configureplans'),
            shezar_feature_disabled('learningplans')
        )
    );

    $ADMIN->add('shezar_plan',
        new admin_externalpage('evidencetypes',
            new lang_string('evidencetypes', 'shezar_plan'),
            "$CFG->wwwroot/shezar/plan/evidencetypes/index.php",
            array('shezar/plan:configureplans'),
            shezar_feature_disabled('learningplans')
        )
    );

    $ADMIN->add('shezar_plan',
        new admin_externalpage('evidencetypemanage',
            new lang_string('evidencecustomfields', 'shezar_plan'),
            "$CFG->wwwroot/shezar/customfield/index.php?prefix=evidence",
            array('shezar/plan:evidencemanagecustomfield'),
            shezar_feature_disabled('learningplans')
        )
    );

