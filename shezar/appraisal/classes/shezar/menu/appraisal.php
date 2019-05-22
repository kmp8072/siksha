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
 * @author     Oleg Demeshev <oleg.demeshev@shezarlms.com>
 */

namespace shezar_appraisal\shezar\menu;

use \shezar_core\shezar\menu\menu as menu;

class appraisal extends \shezar_core\shezar\menu\item {

    protected function get_default_title() {
        return get_string('performance', 'shezar_appraisal');
    }

    protected function get_default_url() {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/shezar/appraisal/lib.php');

        $isappraisalenabled = shezar_feature_visible('appraisals');
        $viewownappraisals = $isappraisalenabled && \appraisal::can_view_own_appraisals($USER->id);
        $viewappraisals = $isappraisalenabled && ($viewownappraisals || \appraisal::can_view_staff_appraisals($USER->id));

        $feedbackmenu = new \shezar_feedback360\shezar\menu\feedback360(array());
        $viewfeedback = $feedbackmenu->get_visibility();

        $goalmenu = new \shezar_hierarchy\shezar\menu\mygoals(array());
        $viewgoals = $goalmenu->get_visibility();

        if ($viewownappraisals) {
            return '/shezar/appraisal/myappraisal.php?latest=1';
        } else if ($viewappraisals) {
            return '/shezar/appraisal/index.php';
        } else if ($viewfeedback) {
            return '/shezar/feedback360/index.php';
        } else if ($viewgoals) {
            return '/shezar/hierarchy/prefix/goal/mygoals.php';
        }
    }

    public function get_default_sortorder() {
        return 40000;
    }

    public function get_default_visibility() {
        return menu::SHOW_WHEN_REQUIRED;
    }

    protected function check_visibility() {
        global $CFG, $USER;

        static $cache = null;
        if (isset($cache)) {
            return $cache;
        }

        if (shezar_feature_visible('goals')) {
            // Start checking from least consuming requests.
            $goalmenu = new \shezar_hierarchy\shezar\menu\mygoals(array());
            $show = $goalmenu->get_visibility();
            if ($show != menu::HIDE_ALWAYS) {
                $cache = menu::SHOW_ALWAYS;
                return $cache;
            }
        }

        if (shezar_feature_visible('feedback360')) {
            $feedbackmenu = new \shezar_feedback360\shezar\menu\feedback360(array());
            $show = $feedbackmenu->get_visibility();
            if ($show != menu::HIDE_ALWAYS) {
                $cache = menu::SHOW_ALWAYS;
                return $cache;
            }
        }

        if (shezar_feature_visible('appraisals')) {
            require_once($CFG->dirroot . '/shezar/appraisal/lib.php');
            $show = (\appraisal::can_view_own_appraisals($USER->id) || \appraisal::can_view_staff_appraisals($USER->id));
            if ($show) {
                $cache = menu::SHOW_ALWAYS;
                return $cache;
            }
        }

        // Nothing to display here.
        $cache = menu::HIDE_ALWAYS;
        return $cache;
    }

    /**
     * Is this menu item completely disabled?
     *
     * @return bool
     */
    public function is_disabled() {
        return (shezar_feature_disabled('appraisals') && shezar_feature_disabled('goals') && shezar_feature_disabled('feedback360'));
    }
}
