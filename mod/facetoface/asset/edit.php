<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2016 onwards shezar Learning Solutions LTD
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
 * @package mod_facetoface
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

$id = optional_param('id', 0, PARAM_INT);

admin_externalpage_setup('modfacetofaceassets');

if ($id) {
    $asset = $DB->get_record('facetoface_asset', array('id' => $id, 'custom' => 0), '*', MUST_EXIST);
} else {
    $asset = false;
}

$assetlisturl = new moodle_url('/mod/facetoface/asset/manage.php');

$form = facetoface_process_asset_form($asset, false, false,
    function() use ($assetlisturl, $id) {
        if (!$id) {
            $successstr = 'assetcreatesuccess';
        } else {
            $successstr = 'assetupdatesuccess';
        }
        shezar_set_notification(get_string($successstr, 'facetoface'), $assetlisturl, array('class' => 'notifysuccess'));
    },
    function() use ($assetlisturl) {
        redirect($assetlisturl);
    }
);

$url = new moodle_url('/admin/settings.php', array('section' => 'modsettingfacetoface'));

if ($id == 0) {
    $pageheading = get_string('addasset', 'facetoface');
} else {
    $pageheading = get_string('editasset', 'facetoface');
}

echo $OUTPUT->header();

echo $OUTPUT->heading($pageheading);

$form->display();

echo $OUTPUT->footer();
