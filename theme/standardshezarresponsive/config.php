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
 * This theme has been deprecated.
 * We strongly recommend basing all new themes on roots and basis.
 * This theme will be removed from core in a future release at which point
 * it will no longer receive updates from shezar.
 *
 * @deprecated since shezar 9
 * @author Paul Walker <paul.walker@catalyst-eu.net>
 * @package shezar
 * @subpackage theme
 */

$THEME->name = 'standardshezarresponsive';
$THEME->parents = array('bootstrapbase', 'base');
$THEME->sheets = array(
    'core',     // Must come first.
    'navigation',
    'appraisal',
    'admin',
    'blocks',
    'calendar',
    'course',
    'user',
    'dock',
    'grade',
    'message',
    'modules',
    'pagelayout',
    'question',
    'plugins',
    'shezar_jquery_treeview',
    'shezar_jquery_datatables',
    'shezar_jquery_ui_dialog',
    'shezar',
    'css3',     // Sets up CSS 3 + browser specific styles.
    'badges'    // Remove in t2-integration as not needed.
);

$THEME->layouts = array(
    'base' => array(
        'file' => 'columns2.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array('langmenu' => true)
    ),
    'noblocks' => array(
        'file' => 'columns1.php',
        'regions' => array(),
        'options' => array('noblocks' => true, 'langmenu' => true),
    ),
    'login' => array(
        'file' => 'columns1.php',
        'regions' => array(),
        'options' => array('nologininfo' => true, 'nocustommenu' => true, 'nonavbar' => true, 'langmenu' => true),
    ),
    'popup' => array(
        'file' => 'popup.php',
        'regions' => array(),
        'options' => array('nofooter' => true, 'nonavbar' => true),
    ),
    // The pagelayout used for reports.
    'report' => array(
        'file' => 'columns2.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array('langmenu' => true),
    ),
    // Should display the content and basic headers only.
    'print' => array(
        'file' => 'columns1.php',
        'regions' => array(),
        'options' => array('nofooter' => true, 'nonavbar' => false, 'nocustommenu' => true),
    ),
);

$THEME->enable_dock = true;
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->javascripts_footer = array(
    'core'
);
