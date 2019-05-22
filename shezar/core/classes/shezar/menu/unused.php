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
* shezar navigation edit page.
*
* @package    shezar
* @subpackage navigation
* @author     Oleg Demeshev <oleg.demeshev@shezarlearning.com>
*/
namespace shezar_core\shezar\menu;

use \shezar_core\shezar\menu\menu as menu;

class unused extends \shezar_core\shezar\menu\item {

    protected function get_default_title() {
        return get_string('unused', 'shezar_core');
    }

    protected function get_default_url() {
        return ''; //'/index.php';
    }

    public function get_default_sortorder() {
        return 80000;
    }

    public function get_default_visibility() {
        return menu::HIDE_ALWAYS;
    }

}