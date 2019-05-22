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
 * @author Alastair Munro <alastair.munro@shezarlearning.com>
 * @package shezar_core
 */

namespace shezar_core\output;

defined('MOODLE_INTERNAL') || die();

class shezar_menu implements \renderable, \templatable {

    public $menuitems = array();

    public function __construct($menudata, $parent=null, $selected_items=array()) {
        global $PAGE;

        // Gets selected items, only done first time
        if (!$selected_items && $PAGE->shezar_menu_selected) {
            $relationships = array();
            foreach ($menudata as $item) {
                $relationships[$item->name] = array($item->name);
                if ($item->parent) {
                    $relationships[$item->name][] = $item->parent;
                    if (!empty($relationships[$item->parent])) {
                        $relationships[$item->name] = array_merge($relationships[$item->name], $relationships[$item->parent]);
                    } elseif (!isset($relationships[$item->parent])) {
                        throw new \coding_exception('shezar menu definition is incorrect');
                    }
                }
            }

            if (array_key_exists($PAGE->shezar_menu_selected, $relationships)) {
                $selected_items = $relationships[$PAGE->shezar_menu_selected];
            }
        }

        $currentlevel = array();
        foreach ($menudata as $menuitem) {
            if ($menuitem->parent == $parent) {
                $currentlevel[] = $menuitem;
            }
        }

        $numitems = count($currentlevel);

        $count = 0;
        if ($numitems > 0) {
            // Create Structure
            foreach ($currentlevel as $menuitem) {
                $url = new \moodle_url($menuitem->url);

                $class_isfirst = ($count == 0 ? true : false);
                $class_islast = ($count == $numitems - 1 ? true : false);
                // If the menu item is known to be selected or it if its a direct match to the current pages URL.
                $class_isselected = (in_array($menuitem->name, $selected_items) || $PAGE->url->compare($url) ? true : false);

                $children = new self($menudata, $menuitem->name, $selected_items);
                $haschildren = ($children->has_children() ? true : false);

                $this->menuitems[] = array(
                    'class_name' => $menuitem->name,
                    'class_isfirst' => $class_isfirst,
                    'class_islast' => $class_islast,
                    'class_isselected' => $class_isselected,
                    'linktext' => $menuitem->linktext,
                    'url' => (string) new \moodle_url($menuitem->url),
                    'target' => $menuitem->target,
                    'haschildren' => $haschildren,
                    'children' => $children->get_items()
                );
                $count++;
            }
        }
    }

    /**
     * Returns the menu item for this level of menu
     *
     * @return array Array of menu items
     *
     */
    private function get_items() {
        return $this->menuitems;
    }

    /**
     * Has this menu item got children
     *
     * @return bool Returns true if the item has children
     */
    private function has_children() {
        return !empty($this->menuitems);
    }

    /**
     * Export data to be used as the context for a mustache template to the menu.
     *
     * @param \renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output) {

        $menudata = new \stdClass();
        $menudata->menuitems = $this->menuitems;

        return $menudata;
    }
}
