<?php
// This file is part of The Bootstrap Moodle theme
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
 * Core maintenance renderer
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs. (http://www.wisdmlabs.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class  theme_remui_core_renderer_maintenance extends core_renderer_maintenance {

    public function notification($message, $classes = 'notifyproblem') {
        $message = clean_text($message);

        if ($classes == 'notifyproblem') {
            return html_writer::div($message, 'alert alert-danger');
        }
        if ($classes == 'notifywarning') {
            return html_writer::div($message, 'alert alert-warning');
        }
        if ($classes == 'notifysuccess') {
            return html_writer::div($message, 'alert alert-success');
        }
        if ($classes == 'notifymessage') {
            return html_writer::div($message, 'alert alert-info');
        }
        if ($classes == 'redirectmessage') {
            return html_writer::div($message, 'alert alert-block alert-info');
        }
        return html_writer::div($message, $classes);
    }

    /**
     * The standard tags that should be included in the <head> tag
     * i
     *
     * @return string HTML fragment.
     */
    public function standard_head_html() {
        $output = parent::standard_head_html();
        if (get_config('theme_remui', 'fontselect') === "2") {
            // Get the theme font from setting
            $fontnameheading = get_Config('theme_remui', 'fontnameheading');
            $fontnameheading = ucwords($fontnameheading);
            $fontnamebody = get_config('theme_remui', 'fontnamebody');
            $fontnamebody = ucwords($fontnamebody);
            $output .= "<link href='https://fonts.googleapis.com/css?family=".$fontnameheading."|".$fontnamebody."' rel='stylesheet' type='text/css'>";
        }

        // add google analytics code
        $ga_js_async = "<!-- Google Analytics --><script>window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments)};ga.l=+new Date;ga('create', 'UA-CODE-X', 'auto');ga('send', 'pageview');</script><script async src='https://www.google-analytics.com/analytics.js'></script><!-- End Google Analytics -->";

        $ga_tracking_code = trim(get_config('theme_remui', 'googleanalytics'));
        if (!empty($ga_tracking_code)) {
            $output .= str_replace("UA-CODE-X", $ga_tracking_code, $ga_js_async);
        }

        return $output;
    }

    private function debug_listing($message) {
        $message = str_replace('<ul style', '<ul class="list-unstyled" style', $message);
        return html_writer::tag('pre', $message, array('class' => 'alert alert-info'));
    }

    public function custom_menu($custommenuitems = '') {
        // The custom menu is always shown, even if no menu items
        // are configured in the global theme settings page.
        global $CFG;

        if (empty($custommenuitems) && !empty($CFG->custommenuitems)) { // MDL-45507.
            $custommenuitems = $CFG->custommenuitems;
        }
        $custommenu = new custom_menu($custommenuitems, current_language());
        return $this->render_custom_menu($custommenu);
    }

    protected function render_custom_menu(custom_menu $menu) {

        global $CFG;

        // add language selector as a menu item
        $langs = get_string_manager()->get_list_of_translations();
        $haslangmenu = $this->lang_menu() != '';

        if ($haslangmenu) {
            $strlang = get_string('language');
            $currentlang = current_language();
            if (isset($langs[$currentlang])) {
                $currentlang = $langs[$currentlang];
            } else {
                $currentlang = $strlang;
            }
            $this->language = $menu->add($currentlang, new moodle_url('#'), $strlang, 10000);
            foreach ($langs as $langtype => $langname) {
                $this->language->add($langname, new moodle_url($this->page->url, array('lang' => $langtype)), $langname);
            }
        }

        // prepare custom menu
        $content = '<ul class="nav navbar-nav horizontal-main-menu">';

        // add showhide more menu
        $content .= '<li class="hideshow dropdown pull-right"><a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-ellipsis-h"></i></a><ul class="hideshow-ul dropdown-menu"></ul></li>';

        foreach ($menu->get_children() as $item) {
            $content .= $this->render_custom_menu_item($item, 1);
        }

        return $content.'</ul>';
    }

    protected function render_custom_menu_item(custom_menu_item $menunode, $level = 0, $direction = '' ) {
        static $submenucount = 0;

        if ($menunode->has_children()) {

            if ($level == 1) {
                $dropdowntype = 'dropdown';
            } else {
                $dropdowntype = 'dropdown-submenu';
            }

            $content = html_writer::start_tag('li', array('class' => $dropdowntype));
            // If the child has menus render it as a sub menu.
            $submenucount++;
            if ($menunode->get_url() !== null) {
                $url = $menunode->get_url();
            } else {
                $url = '#cm_submenu_'.$submenucount;
            }
            $linkattributes = array(
                'href' => $url,
                'class' => 'dropdown-toggle',
                'data-toggle' => 'dropdown',
                'title' => $menunode->get_title(),
            );
            $content .= html_writer::start_tag('a', $linkattributes);
            $content .= $menunode->get_text();
            if ($level == 1) {
                $content .= '<b class="caret"></b>';
            }
            $content .= '</a>';
            $content .= '<ul class="dropdown-menu '.$direction.'">';
            foreach ($menunode->get_children() as $menunode) {
                $content .= $this->render_custom_menu_item($menunode, 0);
            }
            $content .= '</ul>';
        } else {
            $content = '<li>';
            // The node doesn't have children so produce a final menuitem.
            $class = $menunode->get_title();
            if (preg_match("/^#+$/", $menunode->get_text())) {
                $content = '<li class="divider" role="presentation">';
            } else {
                $content = '<li>';
                // The node doesn't have children so produce a final menuitem.
                if ($menunode->get_url() !== null) {
                    $url = $menunode->get_url();
                } else {
                    $url = '#';
                }
                $content .= html_writer::link($url, $menunode->get_text(), array('class' => $class,
                    'title' => $menunode->get_title()));
            }
        }
        return $content;
    }

    public function box($contents, $classes = 'generalbox', $id = null, $attributes = array()) {
        if (isset($attributes['data-rel']) && $attributes['data-rel'] === 'fatalerror') {
            return html_writer::div($contents, 'alert alert-danger', $attributes);
        }
        return parent::box($contents, $classes, $id, $attributes);
    }

     /**
      * Outputs the opening section of a box.
      *
      * @param string $classes A space-separated list of CSS classes
      * @param string $id An optional ID
      * @param array $attributes An array of other attributes to give the box.
      * @return string the HTML to output.
      */
    public function box_start($classes = 'generalbox', $id = null, $attributes = array()) {
        $this->opencontainers->push('box', html_writer::end_tag('div'));
        $attributes['id'] = $id;
        $attributes['class'] = renderer_base::prepare_classes($classes);
        return html_writer::start_tag('div', $attributes);
    }

    /**
     * Returns the CSS classes to apply to the body tag.
     *
     * @since Moodle 2.5.1 2.6
     * @param array $additionalclasses Any additional classes to apply.
     * @return string
     */
    public function body_css_classes(array $additionalclasses = array()) {
        // Add a class for each block region on the page.
        // We use the block manager here because the theme object makes get_string calls.
        $usedregions = array();
        foreach ($this->page->blocks->get_regions() as $region) {
            $additionalclasses[] = 'has-region-'.$region;
            if ($this->page->blocks->region_has_content($region, $this)) {
                $additionalclasses[] = 'used-region-'.$region;
                $usedregions[] = $region;
            } else {
                $additionalclasses[] = 'empty-region-'.$region;
            }
            if ($this->page->blocks->region_completely_docked($region, $this)) {
                $additionalclasses[] = 'docked-region-'.$region;
            }
        }
        if (!$usedregions) {
            // No regions means there is only content, add 'content-only' class.
            $additionalclasses[] = 'content-only';
        } else if (count($usedregions) === 1) {
            // Add the -only class for the only used region.
            $region = array_shift($usedregions);
            $additionalclasses[] = $region . '-only';
        }
        foreach ($this->page->layout_options as $option => $value) {
            if ($value) {
                $additionalclasses[] = 'layout-option-'.$option;
            }
        }

        // Custom classes in body tag for remUI
        global $PAGE, $OUTPUT;
        
        // Creating the layouts array to avoid using the control-sidebar-open class
        $layout_array = array('login', 'popup', 'frametop', 'embedded', 'maintenance', 'print', 'redirect');

        // fixed header or default and theme skin
        $current_themestyle     = get_config('theme_remui', 'layout');
        $current_colorscheme    = get_config('theme_remui', 'colorscheme');

        if (!empty($current_colorscheme)) {
            $additionalclasses[] = $current_colorscheme;
        } else {
            $additionalclasses[] = "skin-blue";
            $additionalclasses[] = "dark-skin";
        }

        if (!empty($current_themestyle)) {
            $additionalclasses[] = $current_themestyle;
        }

        // control-sidebar-open class will be appended to the 3columns pages only
        if (!in_array($PAGE->pagelayout, $layout_array) && $PAGE->blocks->region_has_content('side-post', $OUTPUT)) {
            
            if (get_config('theme_remui', 'rightsidebarslide') == 1) {
                if ($PAGE->pagetype == 'site-index' && !isloggedin()) {
                    $skin .= " ";
                } else {
                    $additionalclasses[] = "control-sidebar-open";
                }
            }
        }

        if (get_config('theme_remui', 'leftsidebarslide') == 1 || ($PAGE->pagetype == 'site-index' && !isloggedin())) {
            $additionalclasses[] = "sidebar-collapse";
        }

        $css = $this->page->bodyclasses .' '. join(' ', $additionalclasses);
        return $css;
    }

    /**
     * Returns the url of the custom favicon.
     */
    public function favicon() {
        global $PAGE;
        $favicon = $PAGE->theme->setting_file_url('faviconurl', 'faviconurl');
        if (empty($favicon)) {
            return $this->page->theme->pix_url('favicon', 'theme');
        } else {
            return $favicon;
        }
    }

    /**
     * Returns lang menu or '', this method also checks forcing of languages in courses.
     *
     * This function calls {@link core_renderer::render_single_select()} to actually display the language menu.
     *
     * @return string The lang menu HTML or empty string
     */
    public function lang_menu() {
        global $CFG;

        if (empty($CFG->langmenu)) {
            return '';
        }

        if ($this->page->course != SITEID and !empty($this->page->course->lang)) {
            // do not show lang menu if language forced
            return '';
        }

        $currlang = current_language();
        $langs = get_string_manager()->get_list_of_translations();

        if (count($langs) < 2) {
            return '';
        }

        $s = new single_select($this->page->url, 'lang', $langs, $currlang, null);
        $s->label = get_accesshide(get_string('language'));
        $s->class = 'langmenu';
        return $this->render($s);
    }
}
