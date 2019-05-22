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
 * @package theme
 * @subpackage customshezarresponsive
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Makes our changes to the CSS
 *
 * @param string $css
 * @param theme_config $theme
 * @return string
 */
function theme_customshezarresponsive_process_css($css, $theme) {

    $substitutions = array(
        'linkcolor' => '#087BB1',
        'linkvisitedcolor' => '#087BB1',
        'headerbgc' => '#F5F5F5',
        'buttoncolor' => '#E6E6E6'
    );
    $css = shezar_theme_generate_autocolors($css, $theme, $substitutions);

    // Set the custom CSS
    if (!empty($theme->settings->customcss)) {
        $customcss = $theme->settings->customcss;
    } else {
        $customcss = null;
    }
    $css = theme_customshezarresponsive_set_customcss($css, $customcss);

    // Do some post-processing for the background images.
    $backgroundsubstitutions = array(
        '[[setting:contentbackground]]' => isset($theme->settings->contentbackground) ? $theme->settings->contentbackground : '#FFFFFF',
        '[[setting:bodybackground]]' => isset($theme->settings->bodybackground) ? $theme->settings->bodybackground : '#FFFFFF',
        '[[setting:backgroundrepeat]]' => isset($theme->settings->backgroundrepeat) ? $theme->settings->backgroundrepeat : 'none',
        '[[setting:backgroundposition]]' => isset($theme->settings->backgroundposition) ? str_replace('_', ' ', $theme->settings->backgroundposition) : '0 0',
        '[[setting:backgroundimage]]' => isset($theme->settings->backgroundimage) ? 'url(' . $theme->setting_file_url('backgroundimage', 'backgroundimage') . ')' : 'none',
        '[[setting:backgroundfixed]]' => isset($theme->settings->backgroundfixed) && $theme->settings->backgroundfixed ? 'fixed' : 'initial',
        '[[setting:textcolor]]' => isset($theme->settings->textcolor) ? $theme->settings->textcolor : '#333366'
    );
    $css = str_replace(array_keys($backgroundsubstitutions), $backgroundsubstitutions, $css);

    return $css;
}

/**
 * Sets the custom css variable in CSS
 *
 * @param string $css
 * @param mixed $customcss
 * @return string
 */
function theme_customshezarresponsive_set_customcss($css, $customcss) {
    $tag = '[[setting:customcss]]';
    $replacement = $customcss;
    if (is_null($replacement)) {
        $replacement = '';
    }
    $css = str_replace($tag, $replacement, $css);
    return $css;
}

/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function theme_customshezarresponsive_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    if ($context->contextlevel == CONTEXT_SYSTEM && ($filearea === 'logo' || $filearea === 'favicon' || $filearea === 'backgroundimage')) {
        $theme = theme_config::load('customshezarresponsive');
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    } else {
        send_file_not_found();
    }
}
