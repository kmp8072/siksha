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
 * @copyright 2015 onwards shezar Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@shezarlms.com>
 * @author    Petr Skoda <petr.skoda@shezarlms.com>
 * @package   core
 */

namespace core\output;

use \pix_icon;

defined('MOODLE_INTERNAL') || die();

/**
 * Flexible icon class. Provides a flexible framework for outputting icons via fonts.
 *
 * @copyright 2015 onwards shezar Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@shezarlms.com>
 * @author    Petr Skoda <petr.skoda@shezarlms.com>
 * @package   core
 */
class flex_icon extends \pix_icon {
    /** @deprecated do not use in flex icons */
    var $pix;

    /** @deprecated do not use in flex icons */
    var $component;

    /** @deprecated do not use in flex icons */
    var $attributes;

    /**
     * @var string Flex icon identifier
     */
    public $identifier;

    /**
     * @var array Flex icon custom template data, such as 'alt' and 'classes'
     */
    public $customdata;

    /**
     * Create a flexible icon data structure using one of identifier
     * defined in one of pix/flex_icons.php files.
     *
     * @param string $identifier icon identifier, ex: 'edit', 'mod_book|icon'
     * @param array $customdata Optional data to be passed to the rendering (template) context.
     */
    public function __construct($identifier, array $customdata = null) {
        $this->identifier = (string)$identifier;
        $this->customdata = (array)$customdata;

        if (!self::exists($this->identifier)) {
            debugging("Flex icon '{$this->identifier}' not found", DEBUG_DEVELOPER);
        }

        // Emulate the legacy pix_icon data for constructor.
        $alt = '';
        if (isset($customdata['alt'])) {
            $alt = $customdata['alt'];
        }
        $component = 'core';
        $pos = strpos($identifier, '|');
        if ($pos > 0) {
            $component = substr($identifier, 0, $pos);
        }
        $attributes = array();
        if (isset($customdata['classes'])) {
            $attributes['class'] = $customdata['classes'];
        }
        parent::__construct('flexicon', $alt, $component, $attributes);
    }

    /**
     * Retrieve the template name which should be used to render this icon.
     *
     * @return string
     */
    public function get_template() {
        global $PAGE;
        return flex_icon_helper::get_template_by_identifier($PAGE->theme->name, $this->identifier);
    }

    /**
     * Export data to be used as the context for a mustache template to render this icon.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        global $PAGE;
        $icondata = flex_icon_helper::get_data_by_identifier($PAGE->theme->name, $this->identifier);
        $icondata['identifier'] = $this->identifier;
        $icondata['customdata'] = $this->customdata;
        return $icondata;
    }

    /**
     * Does a flex icon with this identifier exist?
     *
     * @param string $identifier Flex icon identifier.
     * @return bool
     */
    public static function exists($identifier) {
        global $PAGE, $CFG;
        // We can use any theme here because we load all flex icons
        // from all plugins and core to build the cached map.
        $theme = $CFG->theme;
        if (isset($PAGE->theme->name)) {
            $theme = $PAGE->theme->name;
        }
        $icons = flex_icon_helper::get_icons($theme);
        return isset($icons[$identifier]);
    }

    /**
     * Create a flex icon from legacy pix_icon if possible.
     *
     * @param pix_icon $icon
     * @param string|array $customclasses list of custom classes added to flex icon
     * @return flex_icon|null returns null if flex matching flex icon cannot be found
     */
    public static function create_from_pix_icon(pix_icon $icon, $customclasses = null) {
        if ($icon instanceof flex_icon) {
            return $icon;
        }

        $flexidentifier = self::get_identifier_from_pix_icon($icon);
        if (!self::exists($flexidentifier)) {
            return null;
        }

        $customdata = array();

        if (isset($customclasses)) {
            self::add_class_to_customdata($customdata, $customclasses);
        }

        if (!empty($icon->attributes['class'])) {
            $blacklist = array('smallicon', 'iconsmall', 'iconlarge', 'icon-pre', 'icon-post', 'icon', 'iconhelp',
                'navicon', 'spacer', 'actionmenu', 'msgicon', 'itemicon', '');

            $newclasses = array_diff(explode(' ',$icon->attributes['class']), $blacklist);
            if (count($newclasses) > 0) {
                self::add_class_to_customdata($customdata, implode(' ', $newclasses));
            }
        }

        if (isset($icon->attributes['alt'])) {
            $customdata['alt'] = $icon->attributes['alt'];
        }

        return new flex_icon($flexidentifier, $customdata);
    }

    /**
     * Create a flex icon from legacy pix_icon if possible.
     *
     * @param string|\moodle_url $pixurl
     * @param string|array $customdata list of custom classes added to flex icon
     * @return flex_icon|null returns null if flex matching flex icon cannot be found
     */
    public static function create_from_pix_url($pixurl, $customdata = null) {
        $pixurl = (string)$pixurl;

        if (strpos($pixurl, 'image=') !== false) {
            // Slasharguments disabled.
            $pixurl = urldecode($pixurl);
            if (!preg_match('|component=([0-9a-z_]+).*image=([0-9a-z_/]+)|', $pixurl, $matches)) {
                return null;
            }
            $flexidentifier = \core_component::normalize_componentname($matches[1]) . '|' . $matches[2];

        } else {
            if (!preg_match('|\.php/(_s/)?[a-z0-9_]+/([0-9a-z_]+)/-?[0-9]+/([0-9a-z_/]+)|', $pixurl, $matches)) {
                return null;
            }
            $flexidentifier = \core_component::normalize_componentname($matches[2]) . '|' . $matches[3];
        }

        if (!self::exists($flexidentifier)) {
            return null;
        }

        return new flex_icon($flexidentifier, $customdata);
    }

    /**
     * Add classes to custom data.
     *
     * @param array $customdata
     * @param string|array $classes the CSS class or classes to be added to $customdata['classes']
     * @return void $customdata['classes'] is modified
     */
    protected static function add_class_to_customdata(&$customdata, $classes) {
        if (is_array($classes)) {
            $classes = implode(' ', $classes);
        }
        $classes = trim($classes);
        if ($classes === '') {
            return;
        }
        if (!isset($customdata['classes']) or trim($customdata['classes']) === '') {
            $customdata['classes'] = $classes;
        } else {
            $customdata['classes'] .= ' ' . $classes;
        }
    }

    /**
     * Convert pix icon into expected flex icon identifier format.
     *
     * @param pix_icon $icon
     * @return string
     */
    protected static function get_identifier_from_pix_icon(pix_icon $icon) {
        $pixpath = $icon->pix;

        // Remove the size suffix if present - 'f/pdf-256' will become 'f/pdf'.
        if (preg_match('/^f\/.+(-\d+)$/', $pixpath) === 1) {
            $pixpath =  preg_replace('/-\d+$/', '', $pixpath);
        }

        // Cast to string before normalisation because it might be null.
        $component = \core_component::normalize_componentname((string)$icon->component);

        return "{$component}|{$pixpath}";
    }
}
