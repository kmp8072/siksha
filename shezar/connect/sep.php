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

/**
 * This is the public API, the sep_services class is internal implementation.
 *
 * The APU version support can be implemented:
 *  - either here by mapping the $service to different methods in sep_services
 *  - or the methods in sep-services can do it too
 *  - or it can be a mix
 */

define('NO_MOODLE_COOKIES', true); // Session not used here.
define('AJAX_SCRIPT', true);       // Eliminates redirects and adds json header.

use \shezar_connect\util;
use \shezar_core\jsend;

require(__DIR__ . '/../../config.php');
jsend::init_output(); // Override exception handler and setup other things.

// Prevent GET parameters here, because we do not want them in apache logs.
$parameters = data_submitted();
if (empty($parameters->serversecret) or empty($parameters->serveridnumber) or empty($parameters->service)) {
    jsend::send_error('invalid parameters');
}

$serversecret   = clean_param($parameters->serversecret, PARAM_ALPHANUM);
$serveridnumber = clean_param($parameters->serveridnumber, PARAM_ALPHANUM);
$service        = clean_param($parameters->service, PARAM_ALPHANUMEXT);

unset($parameters->serversecret);
unset($parameters->serveridnumber);
unset($parameters->service);

$idnumber = get_config('shezar_connect', 'serveridnumber');
$client = $DB->get_record('shezar_connect_clients', array('serversecret' => $serversecret));

// First make sure they know the right 'username' and password',
// do not tell them if enabled yet.
if (!$client or !$idnumber or $serveridnumber !== $idnumber) {
    jsend::send_error('invalid server secret or idnumber');
}

if (empty($CFG->enableconnectserver)) {
    jsend::send_error('connect server not enabled');
}

if ($client->status != util::CLIENT_STATUS_OK) {
    jsend::send_error('connect client is not active');
}

if ($client->apiversion < util::MIN_API_VERSION or $client->apiversion > util::MAX_API_VERSION) {
    jsend::send_error('unsupported api version');
}

if (!method_exists('shezar_connect\sep_services', $service)) {
    jsend::send_error('invalid server service name: ' . $service);
}

// The returned data may be large.
raise_memory_limit(MEMORY_EXTRA);

// The service methods must do all parameter cleaning and validation.
// For now the methods are responsible for API versions too.
$result = \shezar_connect\sep_services::$service($client, (array)$parameters);
jsend::send_result($result);
