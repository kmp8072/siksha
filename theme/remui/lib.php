<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * CSS Processor
 *
 * @param string $css
 * @param theme_config $theme
 * @return string
 */
function theme_remui_process_css($css, theme_config $theme) {
    global $OUTPUT;
    // Set the background image for the logo.
    $logo = $theme->setting_file_url('loginsettingpic', 'loginsettingpic');
    $tag = '[[setting:loginsettingpic]]';
    if (is_null($logo)) {
        $logo = $OUTPUT->pix_url('login_texture', 'theme');
        $replacement = "#page-login-index {background-image: url($logo); background-color: #eee;}";
    } else {
        $replacement = "#page-login-index {background-image: url($logo);  background-size: auto 100%; background-position: center top; background-color: #eee;}";
    }
    $css = str_replace($tag, $replacement, $css);

    // Set the signup panel text color
    $signuptextcolor = get_config('theme_remui', 'signuptextcolor');
    $css = \theme_remui\toolbox::set_color($css, $signuptextcolor, '[[setting:signuptextcolor]]', '#FFFFFF');

    // Set the theme colour.
    $colorscheme = get_config('theme_remui', 'colorscheme');
    switch ($colorscheme) {
        case 'skin-blue dark-skin ':
            $themecolor = '#3c8dbc'; // setting the blue color
            break;
        case 'skin-purple dark-skin ':
            $themecolor = '#605ca8';
            break;
        case 'skin-green dark-skin ':
            $themecolor = '#00a65a';
            break;
        case 'skin-red dark-skin ':
            $themecolor = '#dd4b39';
            break;
        case 'skin-yellow dark-skin ':
            $themecolor = '#f39c12';
            break;
            // For light skin
        case 'skin-blue-light light-skin ':
            $themecolor = '#3c8dbc'; // setting the blue light color
            break;
        case 'skin-purple-light light-skin ':
            $themecolor = '#605ca8';
            break;
        case 'skin-green-light light-skin ':
            $themecolor = '#00a65a';
            break;
        case 'skin-red-light light-skin ':
            $themecolor = '#dd4b39';
            break;
        case 'skin-yellow-light light-skin ':
            $themecolor = '#f39c12';
            break;
        case 'skin-custom dark-skin ' :
            $themecolor = get_config('theme_remui', 'customskin_color');
            if(!$themecolor) {
                $themecolor = '#3c8dbc';
            }
            break;
        case 'skin-custom-light light-skin ' :
            $themecolor = get_config('theme_remui', 'customskin_color');
            if(!$themecolor) {
                $themecolor = '#3c8dbc';
            }
            break;
        default:
            $themecolor = '#3c8dbc'; // setting the default color blue
            break;
    }

       $css = \theme_remui\toolbox::set_color($css, $themecolor, '[[setting:themecolor]]', '#3c8dbc');

       $activecolor = new \theme_remui\Color($themecolor);

       // calculate default dark and light
       $css = \theme_remui\toolbox::set_color($css, '#'.$activecolor->lighten(2), '[[setting:themecolorlight]]', '#3c8dbc');
       $css = \theme_remui\toolbox::set_color($css, '#'.$activecolor->darken(2), '[[setting:themecolordark]]', '#3c8dbc');

       // calculate darker based on above dark color output
       $new_dark    = new \theme_remui\Color('#'.$activecolor->darken(2));
       $css = \theme_remui\toolbox::set_color($css, '#'.$new_dark->darken(1), '[[setting:themecolordarker]]', '#3c8dbc');

        // calculate lighter based on above light color output
       $new_light    = new \theme_remui\Color('#'.$activecolor->lighten(2));
       $css = \theme_remui\toolbox::set_color($css, '#'.$new_light->lighten(1), '[[setting:themecolorlighter]]', '#3c8dbc');


    if (get_config('theme_remui', 'fontselect') === "2") {
        // Get the theme font from setting
        $fontnameheading = get_Config('theme_remui', 'fontnameheading');
        $fontnameheading = ucwords($fontnameheading);
        $fontnamebody = get_config('theme_remui', 'fontnamebody');
        $fontnamebody = ucwords($fontnamebody);
        // Set the theme font.
        $css = \theme_remui\toolbox::set_font($css, 'heading', $fontnameheading);
        $css = \theme_remui\toolbox::set_font($css, 'body', $fontnamebody);
    }

    // Set the theme text colour.
    $themetextcolor = get_config('theme_remui', 'themetextcolor');
    $css = \theme_remui\toolbox::set_color($css, $themetextcolor, '[[setting:themetextcolor]]', '#047797');

    // Set custom CSS.
    $customcss = get_config('theme_remui', 'customcss');
    $css = \theme_remui\toolbox::set_customcss($css, $customcss);

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
function theme_remui_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    static $theme;
    $course = $course;
    $cm = $cm;
    if (empty($theme)) {
        $theme = theme_config::load('remui');
    }
    if ($context->contextlevel == CONTEXT_SYSTEM) {
        if ($filearea === 'frontpageaboutusimage') {
            return $theme->setting_file_serve('frontpageaboutusimage', $args, $forcedownload, $options);
        } else if ($filearea === 'testimonialsimage1') {
            return $theme->setting_file_serve('testimonialsimage1', $args, $forcedownload, $options);
        } else if ($filearea === 'testimonialsimage2') {
            return $theme->setting_file_serve('testimonialsimage2', $args, $forcedownload, $options);
        } else if ($filearea === 'testimonialsimage3') {
            return $theme->setting_file_serve('testimonialsimage3', $args, $forcedownload, $options);
        } else if ($filearea === 'loginsettingpic') {
            return $theme->setting_file_serve('loginsettingpic', $args, $forcedownload, $options);
        } else if ($filearea === 'logo') {
            return $theme->setting_file_serve('logo', $args, $forcedownload, $options);
        } else if ($filearea === 'slideimage1') {
            return $theme->setting_file_serve('slideimage1', $args, $forcedownload, $options);
        } else if ($filearea === 'slideimage2') {
            return $theme->setting_file_serve('slideimage2', $args, $forcedownload, $options);
        } else if ($filearea === 'slideimage3') {
            return $theme->setting_file_serve('slideimage3', $args, $forcedownload, $options);
        } else if ($filearea === 'slideimage4') {
            return $theme->setting_file_serve('slideimage4', $args, $forcedownload, $options);
        } else if ($filearea === 'slideimage5') {
            return $theme->setting_file_serve('slideimage5', $args, $forcedownload, $options);
        } else if ($filearea === 'faviconurl') {
            return $theme->setting_file_serve('faviconurl', $args, $forcedownload, $options);
        } else if ($filearea === 'staticimage') {
            return $theme->setting_file_serve('staticimage', $args, $forcedownload, $options);
        } else if ($filearea === 'layoutimage') {
            return $theme->setting_file_serve('layoutimage', $args, $forcedownload, $options);
        } else {
            send_file_not_found();
        }
    } else {
        send_file_not_found();
    }
}
