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
 * @author Joby Harding <joby.harding@shezarlearning.com>
 * @package core_elementlibrary
 */
define(['jquery', 'core_elementlibrary/animate_scroll'], function($) {
    return {
        init: function() {
            // Page fragment links animation.
            $('.pattern-library__nav a, a[href^="#"]').animateScroll();

            // Prevent tab example jumping to top of page.
            $('.nav-tabs li a').on('click',  function(event) {
                event.preventDefault();
            });
        }
    };
});
