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

/**
 * Service End Point.
 *
 * This is the public API, the sep_services class is internal implementation.
 *
 * The API version support can be implemented:
 *  - either here by mapping the $service to different methods in sep_services
 *  - or the methods in sep-services can do it too
 *  - or it can be a mix
 */

define('NO_MOODLE_COOKIES', true); // Session not used here.
define('AJAX_SCRIPT', true);       // Eliminates redirects and adds json header.

use \auth_connect\util;
use \shezar_core\jsend;

require(__DIR__ . '/../../config.php');
jsend::init_output(); // Override exception handler and setup other things.

// Prevent GET parameters here, because we do not want them in apache logs.
$parameters = data_submitted();
if (empty($parameters->clientsecret) or empty($parameters->clientidnumber) or empty($parameters->service)) {
    jsend::send_error('invalid parameters');
}

$clientsecret   = clean_param($parameters->clientsecret, PARAM_ALPHANUM);
$clientidnumber = clean_param($parameters->clientidnumber, PARAM_ALPHANUM);
$service        = clean_param($parameters->service, PARAM_ALPHANUMEXT);

unset($parameters->clientsecret);
unset($parameters->clientidnumber);
unset($parameters->service);

$server = $DB->get_record('auth_connect_servers', array('clientsecret' => $clientsecret, 'clientidnumber' => $clientidnumber));

if (!$server) {
    jsend::send_error('invalid client secret or idnumber');
}

if (!is_enabled_auth('connect')) {
    jsend::send_error('connect client not enabled');
}

if ($server->status != util::SERVER_STATUS_OK) {
    jsend::send_error('connect server is not active');
}

if ($server->apiversion < util::MIN_API_VERSION or $server->apiversion > util::MAX_API_VERSION) {
    jsend::send_error('unsupported api version');
}

if (!method_exists('auth_connect\sep_services', $service)) {
    jsend::send_error('invalid client service name: ' . $service);
}

// The service methods must do all parameter cleaning and validation.
// For now the methods are responsible for API versions too.
$result = \auth_connect\sep_services::$service($server, (array)$parameters);
jsend::send_result($result);
