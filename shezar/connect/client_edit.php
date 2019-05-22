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

use \shezar_connect\util;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);

$PAGE->set_url('/shezar/connect/client_edit.php', array('id' => $id));

admin_externalpage_setup('shezarconnectclients');

if (empty($CFG->enableconnectserver)) {
    die;
}

$client = $DB->get_record('shezar_connect_clients', array('id' => $id), '*', MUST_EXIST);
$client->cohortid = (int)$client->cohortid; // Use 0 for no cohort.

$form = new shezar_connect_form_client_edit(null, $client);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/shezar/connect/index.php'));

} else if ($data = $form->get_data()) {
    \shezar_connect\util::edit_client($data);
    redirect(new moodle_url('/shezar/connect/index.php'));
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('clientedit', 'shezar_connect'));
echo util::warn_if_not_https();

$form->display();

echo $OUTPUT->footer();
