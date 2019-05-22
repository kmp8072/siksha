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
 * Overriding core rendering functions for shezar.
 *
 * @deprecated   since shezar 9
 */
class theme_standardshezarresponsive_core_renderer extends theme_bootstrapbase_core_renderer {
    /**
     * Return the navbar content so that it can be echoed out by the layout
     *
     * @return string XHTML navbar
     */
    public function navbar() {
        // shezar: Original Moodle 3.0 code
        $items = $this->page->navbar->get_items();
        $itemcount = count($items);
        if ($itemcount === 0) {
            return '';
        }

        $htmlblocks = array();
        // Iterate the navarray and display each node
        $separator = get_separator();
        for ($i=0;$i < $itemcount;$i++) {
            $item = $items[$i];
            $item->last = false;
            $item->hideicon = true;
            if ($i===0) {
                $content = html_writer::tag('li', $this->render($item));
            } else if ($i === $itemcount - 1) {
                $item->last = true;
                $content = html_writer::tag('li', $separator . $this->render($item));
            } else {
                $content = html_writer::tag('li', $separator.$this->render($item));
            }
            $htmlblocks[] = $content;
        }

        //accessibility: heading for navbar list  (MDL-20446)
        $navbarcontent = html_writer::tag('span', get_string('pagepath'),
            array('class' => 'accesshide', 'id' => 'navbar-label'));
        $navbarcontent .= html_writer::tag('nav',
            html_writer::tag('ul', join('', $htmlblocks)),
            array('aria-labelledby' => 'navbar-label'));
        // XHTML
        return $navbarcontent;
    }

    /**
     * Renders tabtree
     *
     * @param tabtree $tabtree
     * @return string
     */
    protected function render_tabtree(tabtree $tabtree) {
        if (empty($tabtree->subtree)) {
            return '';
        }

        // Check to see if this tree has a second level on the activated root.
        $classes = 'tabtree';
        foreach ($tabtree->subtree as $node) {
            if ($node->activated && count($node->subtree)) {
                $classes .= ' tabtree2';
            }
        }

        $str = '';
        $str .= html_writer::start_tag('div', array('class' => $classes));
        $str .= $this->render_tabobject($tabtree);
        $str .= html_writer::end_tag('div').
                html_writer::tag('div', ' ', array('class' => 'clearer'));
        return $str;
    }

    /**
     * Renders tabobject (part of tabtree)
     *
     * This function is called from {@link core_renderer::render_tabtree()}
     * and also it calls itself when printing the $tabobject subtree recursively.
     *
     * Property $tabobject->level indicates the number of row of tabs.
     *
     * @param tabobject $tabobject
     * @return string HTML fragment
     */
    protected function render_tabobject(tabobject $tabobject) {
        // shezar: Original Moodle 3.0 code
        $str = '';

        // Print name of the current tab.
        if ($tabobject instanceof tabtree) {
            // No name for tabtree root.
        } else if ($tabobject->inactive || $tabobject->activated || ($tabobject->selected && !$tabobject->linkedwhenselected)) {
            // Tab name without a link. The <a> tag is used for styling.
            $str .= html_writer::tag('a', html_writer::span($tabobject->text), array('class' => 'nolink moodle-has-zindex'));
        } else {
            // Tab name with a link.
            if (!($tabobject->link instanceof moodle_url)) {
                // backward compartibility when link was passed as quoted string
                $str .= "<a href=\"$tabobject->link\" title=\"$tabobject->title\"><span>$tabobject->text</span></a>";
            } else {
                $str .= html_writer::link($tabobject->link, html_writer::span($tabobject->text), array('title' => $tabobject->title));
            }
        }

        if (empty($tabobject->subtree)) {
            if ($tabobject->selected) {
                $str .= html_writer::tag('div', '&nbsp;', array('class' => 'tabrow'. ($tabobject->level + 1). ' empty'));
            }
            return $str;
        }

        // Print subtree.
        if ($tabobject->level == 0 || $tabobject->selected || $tabobject->activated) {
            $str .= html_writer::start_tag('ul', array('class' => 'tabrow'. $tabobject->level));
            $cnt = 0;
            foreach ($tabobject->subtree as $tab) {
                $liclass = '';
                if (!$cnt) {
                    $liclass .= ' first';
                }
                if ($cnt == count($tabobject->subtree) - 1) {
                    $liclass .= ' last';
                }
                if ((empty($tab->subtree)) && (!empty($tab->selected))) {
                    $liclass .= ' onerow';
                }

                if ($tab->selected) {
                    $liclass .= ' here selected';
                } else if ($tab->activated) {
                    $liclass .= ' here active';
                }

                // This will recursively call function render_tabobject() for each item in subtree.
                $str .= html_writer::tag('li', $this->render($tab), array('class' => trim($liclass)));
                $cnt++;
            }
            $str .= html_writer::end_tag('ul');
        }

        return $str;
    }

    /**
     * Renders the paging bar.
     * NOTE: Reflect any changes here in ajax version below.
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

     /**
      * Renders the header bar.
      *
      * @param context_header $contextheader Header bar object.
      * @return string HTML for the header bar.
      */
    protected function render_context_header(context_header $contextheader) {

        // All the html stuff goes here.
        $html = html_writer::start_div('page-context-header');

        // Image data.
        if (isset($contextheader->imagedata)) {
            // Header specific image.
            $html .= html_writer::div($contextheader->imagedata, 'page-header-image');
        }

        // Headings.
        if (isset($contextheader->heading)) {
            $headings = $this->heading($contextheader->heading, $contextheader->headinglevel);
            $html .= html_writer::tag('div', $headings, array('class' => 'page-header-headings'));
        }

        // Buttons.
        if (isset($contextheader->additionalbuttons)) {
            $html .= html_writer::start_div('btn-group header-button-group');
            foreach ($contextheader->additionalbuttons as $button) {
                if (!isset($button->page)) {
                    // Include js for messaging.
                    if ($button['buttontype'] === 'message') {
                        message_messenger_requirejs();
                    }
                    $image = $this->pix_icon($button['formattedimage'], $button['title'], 'moodle', array(
                        'class' => 'iconsmall',
                        'role' => 'presentation'
                    ));
                    $image .= html_writer::span($button['title'], 'header-button-title');
                } else {
                    $image = html_writer::empty_tag('img', array(
                        'src' => $button['formattedimage'],
                        'role' => 'presentation'
                    ));
                }
                $html .= html_writer::link($button['url'], html_writer::tag('span', $image), $button['linkattributes']);
            }
            $html .= html_writer::end_div();
        }
        $html .= html_writer::end_div();

        return $html;
    }

}
