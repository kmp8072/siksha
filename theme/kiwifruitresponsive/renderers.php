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
 * This theme has been deprecated.
 * We strongly recommend basing all new themes on roots and basis.
 * This theme will be removed from core in a future release at which point
 * it will no longer receive updates from shezar.
 *
 * @deprecated since shezar 9
 * @author Brian Barnes <brian.barnes@shezarlms.com>
 * @package shezar
 * @subpackage theme
 */

/**
 * Overriding core rendering functions for kiwifruitresponsive
 *
 * @deprecated since shezar 9
 */
class theme_kiwifruitresponsive_core_renderer extends theme_standardshezarresponsive_core_renderer {
    public function kiwifruit_header() {
        global $OUTPUT, $PAGE, $CFG, $SITE;
        $output = '';
        $output .= html_writer::start_tag('header');
        $output .= html_writer::start_tag('div', array('id' => 'main-menu'));

        // Small responsive button.
        $output .= $this->responsive_button();

        // Find the logo.
        if (!empty($PAGE->theme->settings->frontpagelogo)) {
            $logourl = $PAGE->theme->setting_file_url('frontpagelogo', 'frontpagelogo');
            $logoalt = get_string('logoalt', 'theme_kiwifruitresponsive', $SITE->fullname);
        } else if (!empty($PAGE->theme->settings->logo)) {
            $logourl = $PAGE->theme->setting_file_url('logo', 'logo');
            $logoalt = get_string('logoalt', 'theme_kiwifruitresponsive', $SITE->fullname);
        } else {
            $logourl = $OUTPUT->pix_url('logo', 'theme');
            $logoalt = get_string('shezarlogo', 'theme_standardshezarresponsive');
        }

        if (!empty($PAGE->theme->settings->alttext)) {
            $logoalt = format_string($PAGE->theme->settings->alttext);
        }

        if ($logourl) {
            $logo = html_writer::empty_tag('img', array('src' => $logourl, 'alt' => $logoalt));
            $output .= html_writer::tag('a', $logo, array('href' => $CFG->wwwroot, 'class' => 'logo'));
        }

        // The menu.
        $output .= html_writer::start_tag('div', array('id' => 'shezarmenu', 'class' => 'nav-collapse'));
        if (empty($PAGE->layout_options['nocustommenu'])) {
            $menudata = shezar_build_menu();
            $shezar_core_renderer = $PAGE->get_renderer('shezar_core');
            $shezarmenu = $shezar_core_renderer->shezar_menu($menudata);
            $output .= $shezarmenu;
        }

        // Add profile menu (for logged in) or language menu (not logged in).
        $haslangmenu = (!isset($PAGE->layout_options['langmenu']) || $PAGE->layout_options['langmenu'] );
        $output.= ($haslangmenu && (!isloggedin() || isguestuser()) ? $OUTPUT->lang_menu() : '') . $OUTPUT->user_menu();

        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('header');
        return $output;
    }

    public function responsive_button() {
        $attrs = array(
            'class' => 'btn btn-navbar',
            'data-toggle' => 'collapse',
            'data-target' => '.nav-collapse, .langmenu',
            'href' => '#'
        );
        $output = html_writer::start_tag('a', $attrs);
        $output .= html_writer::tag('span', '', array('class' => 'icon-bar')); // Chrome doesn't like self closing spans.
        $output .= html_writer::tag('span', '', array('class' => 'icon-bar'));
        $output .= html_writer::tag('span', '', array('class' => 'icon-bar'));
        $output .= html_writer::tag('span', get_string('expand'), array('class' => 'accesshide'));
        $output .= html_writer::end_tag('a');

        return $output;
    }

    /**
     * Gets HTML for the page heading.
     *
     * @since Moodle 2.5.1 2.6
     * @param string $tag The tag to encase the heading in. h1 by default.
     * @return string HTML.
     */
    public function page_heading($tag = 'h1') {
        return html_writer::tag($tag, $this->page->heading, array('id' => 'pageheading'));
    }
}
