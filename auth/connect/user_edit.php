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
 * @package auth_connect
 */

require(__DIR__ . '/../../config.php');

require_login();

if (!is_enabled_auth('connect')) {
    redirect(new moodle_url('/'));
}

if ($USER->auth !== 'connect') {
    redirect(new moodle_url('/user/edit.php'));
}

$session = $DB->get_record('auth_connect_sso_sessions', array('userid' => $USER->id, 'sid' => session_id()), '*', MUST_EXIST);

$server = $DB->get_record('auth_connect_servers', array('id' => $session->serverid), '*', MUST_EXIST);

redirect(new moodle_url($server->serverurl . '/user/edit.php', array('id' => $session->serveruserid)));
