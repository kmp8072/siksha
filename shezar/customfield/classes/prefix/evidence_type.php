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
 * @author Simon Player <simon.player@shezarlms.com>
 * @package shezar_customfield
 */

namespace shezar_customfield\prefix;
defined('MOODLE_INTERNAL') || die();

/**
 * Class evidence_type
 */
class evidence_type extends type_base {

    /**
     * evidence_type constructor.
     *
     * @param string $prefix
     * @param string $context
     * @param array $extrainfo
     */
    public function __construct($prefix, $context, $extrainfo = array()) {
        parent::__construct($prefix, 'dp_plan_evidence', 'dp_plan_evidence', $context, $extrainfo);
    }

    /**
     * Returns the capability that is required in order to manage evidence custom fields.
     * @return string
     */
    public function get_capability_managefield() {
        return 'shezar/plan:evidencemanagecustomfield';
    }
}
