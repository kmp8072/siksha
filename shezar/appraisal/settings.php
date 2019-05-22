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
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package shezar
 * @subpackage shezar_appraisal
 */

global $SITE, $CFG;

$appraisalcaps = array(
    'shezar/appraisal:manageappraisals',
    'shezar/appraisal:cloneappraisal',
    'shezar/appraisal:assignappraisaltogroup',
    'shezar/appraisal:managenotifications',
    'shezar/appraisal:manageactivation',
    'shezar/appraisal:managepageelements'
);

$feedbackcaps = array(
    'shezar/feedback360:managefeedback360',
    'shezar/feedback360:clonefeedback360',
    'shezar/feedback360:assignfeedback360togroup',
    'shezar/feedback360:manageactivation',
    'shezar/feedback360:managepageelements'
);

if ($hassiteconfig || has_any_capability($appraisalcaps, $systemcontext) || has_any_capability($feedbackcaps, $systemcontext)) {

    $ADMIN->add('appraisals',
        new admin_externalpage('manageappraisals',
            new lang_string('manageappraisals', 'shezar_appraisal'),
            new moodle_url('/shezar/appraisal/manage.php'),
            $appraisalcaps,
            shezar_feature_disabled('appraisals')
        )
    );

    $ADMIN->add('appraisals',
        new admin_externalpage('managefeedback360',
            new lang_string('managefeedback360', 'shezar_feedback360'),
            new moodle_url('/shezar/feedback360/manage.php'),
            $feedbackcaps,
            shezar_feature_disabled('feedback360')
        )
    );

    $ADMIN->add('appraisals',
        new admin_externalpage('reportappraisals',
            new lang_string('reportappraisals', 'shezar_appraisal'),
            new moodle_url('/shezar/appraisal/reports.php'),
            $appraisalcaps,
            shezar_feature_disabled('appraisals')
        )
    );
}
