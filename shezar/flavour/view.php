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
 * @author Sam Hemelryk <sam.hemelryk@shezarlms.com>
 * @package shezar_flavour
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('flavouroverview');

$overview = new \shezar_flavour\overview();
$renderer = $PAGE->get_renderer('shezar_flavour');

$activatebutton = '';
if (is_siteadmin()) {
    $component = $overview->get_flavour_to_enforce();
    if ($component !== null) {
        $enforce = optional_param('enforce', 0, PARAM_BOOL);
        if ($enforce) {
            require_sesskey();
            \shezar_flavour\helper::set_active_flavour($component);
            redirect($PAGE->url);
        }
        $url = new moodle_url($PAGE->url, array('enforce' => 1, 'sesskey' => sesskey()));
        $activatebutton = $OUTPUT->single_button($url, get_string('enforceflavour', 'shezar_flavour'));
    }
}

echo $renderer->header();
echo $activatebutton;
echo $renderer->render($overview);
echo $renderer->footer();
