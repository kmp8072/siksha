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
 * @author Simon Coggins <simon.coggins@shezarlms.com>
 * @package shezar
 * @subpackage shezar_hierarchy
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/shezar/hierarchy/prefix/competency/adminsettings_form.php');

require_login();

global $USER;

require_capability('shezar/hierarchy:updatecompetency', context_system::instance());

admin_externalpage_setup('competencyglobalsettings');
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('globalsettings', 'shezar_hierarchy'));

$form = new competency_global_settings_form();

if ($data = $form->get_data()) {
    // Save settings
    set_config('competencyuseresourcelevelevidence', empty($data->competencyuseresourcelevelevidence) ? 0 : $data->competencyuseresourcelevelevidence);
}

$data = new stdClass();
$data->competencyuseresourcelevelevidence = get_config(null, 'competencyuseresourcelevelevidence');

$form->set_data($data);

$form->display();

echo $OUTPUT->footer();
