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
 * @copyright 2016 onwards shezar Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@shezarlearning.com>
 * @package   theme_roots
 */

namespace theme_roots\output;

defined('MOODLE_INTERNAL') || die();

class site_logo implements \renderable, \templatable {

    /**
     * Implements export_for_template().
     * 
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        global $PAGE, $SITE, $OUTPUT, $CFG;

        $templatecontext = array(
            'siteurl' => $CFG->wwwroot . '/',
            'shortname' => $SITE->shortname,
        );

        $templatecontext['logourl'] = $PAGE->theme->setting_file_url('logo', 'logo');
        $templateocontext['logoalt'] = get_string('logo', 'theme_standardshezarresponsive', $SITE->fullname);

        if (!empty($PAGE->theme->settings->logo)) {
            $templatecontext['logourl'] = $PAGE->theme->setting_file_url('logo', 'logo');
            $templatecontext['logoalt'] = get_string('logo', 'theme_standardshezarresponsive', $SITE->fullname);
        } else {
            $templatecontext['logourl'] = $OUTPUT->pix_url('logo', 'theme');
            $templatecontext['logoalt'] = get_string('shezarlogo', 'theme_standardshezarresponsive');
        }

        if (!empty($PAGE->theme->settings->alttext)) {
            $templatecontext['logoalt'] = format_string($PAGE->theme->settings->alttext);
        }

        if (!empty($PAGE->theme->settings->favicon)) {
            $templatecontext['faviconurl'] = $PAGE->theme->setting_file_url('favicon', 'favicon');
        } else {
            $templatecontext['faviconurl'] = $OUTPUT->favicon();
        }

        return $templatecontext;
    }

}
