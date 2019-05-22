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
 * @author  Joby Harding <joby.harding@shezarlms.com>
 * @author  Petr Skoda <petr.skoda@shezarlms.com>
 * @package mod_data
 */

/* Developer documentation is in /pix/flex_icons.php file. */

$icons = array(
    'mod_data|field/latlong' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-globe',
                ),
        ),
    'mod_data|field/radiobutton' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-dot-circle-o',
                ),
        ),
);

$aliases = array(
    'mod_data|field/checkbox' => 'check-square-o',
    'mod_data|field/date' => 'calendar',
    'mod_data|field/file' => 'file-general',
    'mod_data|field/picture' => 'image',
    'mod_data|field/url' => 'link',
    'mod_data|icon' => 'database',
);
