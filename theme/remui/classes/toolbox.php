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
 * This is built using the bootstrapbase template to allow for new theme's using
 * Moodle's new Bootstrap theme engine
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs. (http://www.wisdmlabs.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_remui;

class toolbox {

    protected $corerenderer = null;
    protected static $instance;

    private function __construct() {
    }

    static public function set_font($css, $type, $fontname) {
        $familytag = '[[setting:' . $type . 'font]]';
        
        if (empty($fontname)) {
            $familyreplacement = 'Verdana';
        } else {
            $familyreplacement = '"'.$fontname.'"';            
        }

        $css = str_replace($familytag, $familyreplacement, $css);        

        return $css;
    }

    static public function set_color($css, $themecolor, $tag, $defaultcolour) {
        if (!($themecolor)) {
            $replacement = $defaultcolour;
        } else {
            $replacement = $themecolor;
        }
        
        $css = str_replace($tag, $replacement, $css);
        return $css;
    }

    static public function set_customcss($css, $customcss) {
        $tag = '[[setting:customcss]]';
        $replacement = $customcss;
        $css = str_replace($tag, $replacement, $css);
        return $css;
    }

    static public function set_logo($css, $logo) {
        $tag = '[[setting:logo]]';
        if (!($logo)) {
            $replacement = 'none';
        } else {
            $replacement = 'url(\''.$logo.'\')';
        }
        $css = str_replace($tag, $replacement, $css);
        return $css;
    }

    static public function set_logoheight($css, $logoheight) {
        $tag = '[[setting:logoheight]]';
        if (!($logoheight)) {
            $replacement = '65px';
        } else {
            $replacement = $logoheight;
        }
        $css = str_replace($tag, $replacement, $css);
        return $css;
    }

    // /**
    //  * States if the browser is not IE9 or less.
    //  */
    // static public function not_lte_ie9() {
    //     $properties = self::ie_properties();
    //     if (!is_array($properties)) {
    //         return true;
    //     }
    //     // We have properties, it is a version of IE, so is it greater than 9?
    //     return ($properties['version'] > 9.0);
    // }

    // /**
    //  * States if the browser is IE9 or less.
    //  */
    // static public function lte_ie9() {
    //     $properties = self::ie_properties();
    //     if (!is_array($properties)) {
    //         return false;
    //     }
    //     // We have properties, it is a version of IE, so is it greater than 9?
    //     return ($properties['version'] <= 9.0);
    // }

    // /**
    //  * States if the browser is IE by returning properties, otherwise false.
    //  */
    // static protected function ie_properties() {
    //     $properties = \core_useragent::check_ie_properties(); // In /lib/classes/useragent.php.
    //     if (!is_array($properties)) {
    //         return false;
    //     } else {
    //         return $properties;
    //     }
    // }
}
