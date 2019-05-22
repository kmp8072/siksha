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
 * @author       Simon Coggins <simon.coggins@shezarlms.com>
 * @author       Brian Barnes <brian.barnes@shezarlms.com>
 * @package      theme_standardshezarresponsive
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Overriding core ajax rendering functions for shezar.
 *
 * @deprecated   since shezar 9
 */
class theme_standardshezarresponsive_core_renderer_ajax extends core_renderer_ajax {
    /**
     * Renders the paging bar.
     *
     * @param paging_bar $pagingbar
     * @return string
     */
    protected function render_paging_bar(paging_bar $pagingbar) {
        $output = '';
        $pagingbar = clone($pagingbar);
        $pagingbar->prepare($this, $this->page, $this->target);

        if ($pagingbar->totalcount > $pagingbar->perpage) {
            $output .= get_string('page') . ':';

            if (!empty($pagingbar->previouslink)) {
                $output .= $pagingbar->previouslink;
            }

            if (!empty($pagingbar->firstlink)) {
                $output .= $pagingbar->firstlink . '...';
            }

            foreach ($pagingbar->pagelinks as $link) {
                $output .= $link;
            }

            if (!empty($pagingbar->lastlink)) {
                $output .= '...' . $pagingbar->lastlink;
            }

            if (!empty($pagingbar->nextlink)) {
                $output .= $pagingbar->nextlink;
            }
        }

        return html_writer::tag('div', $output, array('class' => 'paging'));
    }
}
