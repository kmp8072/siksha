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
 * @package shezar_connect
 */

defined('MOODLE_INTERNAL') || die();

$hidden = empty($CFG->enableconnectserver);

$ADMIN->add('accounts', new admin_category('shezarconnect', new lang_string('server', 'shezar_connect'), $hidden));

$settingspage = new admin_settingpage('shezarconnectsettings',
    new lang_string('settingspage', 'shezar_connect'),
    'moodle/site:config',
    $hidden);

$settingspage->add(new admin_setting_configcheckbox('shezar_connect/syncpasswords',
    new lang_string('syncpasswords', 'shezar_connect'),  new lang_string('syncpasswords_desc', 'shezar_connect'),
    0));

// NOTE TL-7406: add setting for sync of user preferences and custom profile fields here, off by default for performance reasons.

$ADMIN->add('shezarconnect', $settingspage);

$ADMIN->add('shezarconnect', new admin_externalpage('shezarconnectclients',
    new lang_string('clients', 'shezar_connect'),
    new moodle_url('/shezar/connect/index.php'),
    'shezar/connect:manage',
    $hidden)
);
