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
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package shezar_form
 */

namespace shezar_form;

/**
 * Trait for item value cleaning type.
 *
 * @package   shezar_form
 * @copyright 2016 shezar Learning Solutions Ltd {@link http://www.shezarlms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@shezarlms.com>
 */
trait trait_item_paramtype {
    /**
     * @internal do not use directly!
     *
     * @var string PARAM_XXX constant for param cleaning
     */
    private $paramtype;

    /**
     * Set new parameter type cleaning.
     *
     * @throws \coding_exception if the form structure has been finalised and the type cannot be changed.
     * @param string $paramtype PARAM_XXX constant
     */
    public function set_type($paramtype) {
        /** @var item $this */
        if ($this->is_finalised()) {
            throw new \coding_exception('Form structure cannot be changed any more!');
        }
        /** @var trait_item_paramtype $this */
        $this->paramtype = $paramtype;
    }

    /**
     * Get parameter type cleaning.
     *
     * @return string PARAM_XXX constant
     */
    public function get_type() {
        if ($this->paramtype === null) {
            return PARAM_RAW;
        }
        return $this->paramtype;
    }
}
